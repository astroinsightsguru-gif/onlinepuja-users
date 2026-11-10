<?php

namespace App\Social\Publishers;

use App\Brain\Vault;
use App\Social\Contracts\SocialPublisher;
use App\Social\SocialPostPayload;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * YouTube Data API v3. Unlike the others, this endpoint will not fetch a
 * remote URL for you — the video's bytes must be streamed to Google via a
 * resumable upload session, so publish() needs $payload->mediaPath (a local
 * file), not just mediaUrl.
 */
class YouTubeShortsPublisher implements SocialPublisher
{
    protected const SCOPES = 'https://www.googleapis.com/auth/youtube.upload https://www.googleapis.com/auth/youtube.readonly';

    public function platform(): string
    {
        return 'youtube';
    }

    public function label(): string
    {
        return 'YouTube';
    }

    public function isAppConfigured(): bool
    {
        $c = Vault::socialCredentials();

        return $c['youtube_client_id'] !== '' && $c['youtube_client_secret'] !== '';
    }

    public function authorizeUrl(string $state, string $redirectUri): string
    {
        $q = http_build_query([
            'client_id' => Vault::socialCredentials()['youtube_client_id'],
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => self::SCOPES,
            'access_type' => 'offline',   // required to receive a refresh_token
            'prompt' => 'consent',         // force one even on a re-connect
            'state' => $state,
        ]);

        return "https://accounts.google.com/o/oauth2/v2/auth?{$q}";
    }

    public function handleCallback(string $code, string $redirectUri, string $state): array
    {
        $fail = fn (string $e) => ['ok' => false, 'accounts' => [], 'error' => $e];
        $c = Vault::socialCredentials();

        try {
            $res = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => $c['youtube_client_id'],
                'client_secret' => $c['youtube_client_secret'],
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]);
        } catch (\Throwable $e) {
            return $fail('Token exchange failed: '.$e->getMessage());
        }

        if ($res->failed() || ! $res->json('access_token')) {
            return $fail('Token exchange rejected: '.substr($res->body(), 0, 300));
        }

        $token = $res->json('access_token');
        $refresh = $res->json('refresh_token');

        if (! $refresh) {
            return $fail('Google did not return a refresh token. Revoke this app\'s access at '.
                'myaccount.google.com/permissions and connect again so it prompts for consent.');
        }

        try {
            $channel = Http::withToken($token)->get('https://www.googleapis.com/youtube/v3/channels', [
                'part' => 'snippet',
                'mine' => 'true',
            ]);
        } catch (\Throwable $e) {
            return $fail('Connected but could not read the channel: '.$e->getMessage());
        }

        $item = $channel->json('items.0');

        if (! $item) {
            return $fail('This Google account has no YouTube channel.');
        }

        return [
            'ok' => true,
            'accounts' => [[
                'external_id' => $item['id'],
                'label' => $item['snippet']['title'] ?? $item['id'],
                'access_token' => $token,
                'refresh_token' => $refresh,
                'expires_at' => $res->json('expires_in') ? Carbon::now()->addSeconds((int) $res->json('expires_in')) : null,
                'scopes' => explode(' ', self::SCOPES),
                'meta' => ['thumbnail' => $item['snippet']['thumbnails']['default']['url'] ?? null],
            ]],
            'error' => '',
        ];
    }

    public function refresh(object $account): ?array
    {
        if (! $account->refresh_token) {
            return null;
        }

        $c = Vault::socialCredentials();

        try {
            $res = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'refresh_token' => $account->refresh_token,
                'client_id' => $c['youtube_client_id'],
                'client_secret' => $c['youtube_client_secret'],
                'grant_type' => 'refresh_token',
            ]);
        } catch (\Throwable $e) {
            return null;
        }

        if ($res->failed() || ! $res->json('access_token')) {
            return null;
        }

        return [
            'access_token' => $res->json('access_token'),
            'refresh_token' => $account->refresh_token, // Google does not reissue this on refresh
            'expires_at' => $res->json('expires_in') ? Carbon::now()->addSeconds((int) $res->json('expires_in')) : null,
        ];
    }

    public function publish(object $account, SocialPostPayload $payload): array
    {
        $fail = fn (string $e) => ['ok' => false, 'remote_id' => null, 'permalink' => null, 'error' => $e];

        if (! $payload->mediaPath || ! is_file($payload->mediaPath)) {
            return $fail('YouTube needs the video file on disk (mediaPath) — a remote URL alone is not enough.');
        }

        $size = filesize($payload->mediaPath);
        $mime = $payload->mediaMime ?: 'video/mp4';
        // "#Shorts" in the title is the plainest signal to YouTube's classifier,
        // on top of the file already being vertical and under 60s.
        $title = mb_substr(trim($payload->caption !== '' ? $payload->caption : 'Online Puja'), 0, 90);
        if (! str_contains(mb_strtolower($title), 'shorts')) {
            $title = mb_substr($title, 0, 80).' #Shorts';
        }

        // Step 1: open a resumable upload session. part/uploadType are query
        // params, the snippet/status body must be JSON — both together.
        try {
            $session = Http::withToken($account->access_token)
                ->withHeaders([
                    'X-Upload-Content-Type' => $mime,
                    'X-Upload-Content-Length' => (string) $size,
                ])
                ->asJson()
                ->post('https://www.googleapis.com/upload/youtube/v3/videos?'.http_build_query(['part' => 'snippet,status', 'uploadType' => 'resumable']), [
                    'snippet' => [
                        'title' => $title,
                        'description' => $payload->fullText(4900),
                    ],
                    'status' => [
                        'privacyStatus' => 'public',
                        'selfDeclaredMadeForKids' => false,
                    ],
                ]);
        } catch (\Throwable $e) {
            return $fail('Could not start the upload session: '.$e->getMessage());
        }

        if ($session->failed()) {
            return $fail('Upload session rejected: HTTP '.$session->status().' :: '.substr($session->body(), 0, 300));
        }

        $uploadUrl = $session->header('Location');

        if (! $uploadUrl) {
            return $fail('Google did not return a resumable upload URL.');
        }

        // Step 2: stream the file bytes to that session URL.
        $stream = fopen($payload->mediaPath, 'r');

        try {
            $upload = Http::withToken($account->access_token)
                ->withHeaders(['Content-Type' => $mime, 'Content-Length' => (string) $size])
                ->withBody($stream, $mime)
                ->timeout(600)
                ->put($uploadUrl);
        } catch (\Throwable $e) {
            return $fail('Video upload failed: '.$e->getMessage());
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if ($upload->failed() || ! $upload->json('id')) {
            return $fail('Video upload rejected: HTTP '.$upload->status().' :: '.substr($upload->body(), 0, 300));
        }

        $id = $upload->json('id');

        return ['ok' => true, 'remote_id' => $id, 'permalink' => "https://www.youtube.com/shorts/{$id}", 'error' => ''];
    }

    public function metrics(object $account, string $remoteId): array
    {
        $fail = fn (string $e) => ['ok' => false, 'reach' => null, 'likes' => null, 'comments' => null, 'shares' => null, 'error' => $e];

        try {
            $res = Http::withToken($account->access_token)->get('https://www.googleapis.com/youtube/v3/videos', [
                'part' => 'statistics',
                'id' => $remoteId,
            ]);
        } catch (\Throwable $e) {
            return $fail($e->getMessage());
        }

        $stats = $res->json('items.0.statistics');

        if (! $stats) {
            return $fail('Video not found or statistics unavailable.');
        }

        return [
            'ok' => true,
            'reach' => (int) ($stats['viewCount'] ?? 0),
            'likes' => (int) ($stats['likeCount'] ?? 0),
            'comments' => (int) ($stats['commentCount'] ?? 0),
            'shares' => null,
            'error' => '',
        ];
    }
}
