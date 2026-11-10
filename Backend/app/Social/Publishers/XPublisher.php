<?php

namespace App\Social\Publishers;

use App\Brain\Vault;
use App\Social\Contracts\SocialPublisher;
use App\Social\SocialPostPayload;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/** X (Twitter) API v2 — OAuth 2.0 with PKCE, since X requires it even for a confidential client. */
class XPublisher implements SocialPublisher
{
    protected const SCOPES = 'tweet.read tweet.write users.read offline.access';
    protected const CHUNK_BYTES = 4 * 1024 * 1024; // X's documented APPEND chunk ceiling

    public function platform(): string
    {
        return 'x';
    }

    public function label(): string
    {
        return 'X (Twitter)';
    }

    public function isAppConfigured(): bool
    {
        $c = Vault::socialCredentials();

        return $c['x_client_id'] !== '' && $c['x_client_secret'] !== '';
    }

    public function authorizeUrl(string $state, string $redirectUri): string
    {
        // PKCE: cache the verifier under the state, so handleCallback (which
        // only gets code + state back from X) can retrieve it.
        $verifier = Str::random(64);
        Cache::put("x_pkce_{$state}", $verifier, now()->addMinutes(10));

        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        $q = http_build_query([
            'response_type' => 'code',
            'client_id' => Vault::socialCredentials()['x_client_id'],
            'redirect_uri' => $redirectUri,
            'scope' => self::SCOPES,
            'state' => $state,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ]);

        return "https://twitter.com/i/oauth2/authorize?{$q}";
    }

    public function handleCallback(string $code, string $redirectUri, string $state): array
    {
        $fail = fn (string $e) => ['ok' => false, 'accounts' => [], 'error' => $e];
        $verifier = Cache::pull("x_pkce_{$state}");

        if (! $verifier) {
            return $fail('The connection attempt expired — please try connecting again.');
        }

        $c = Vault::socialCredentials();

        try {
            $res = Http::asForm()
                ->withBasicAuth($c['x_client_id'], $c['x_client_secret'])
                ->post('https://api.twitter.com/2/oauth2/token', [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $redirectUri,
                    'code_verifier' => $verifier,
                    'client_id' => $c['x_client_id'],
                ]);
        } catch (\Throwable $e) {
            return $fail('Token exchange failed: '.$e->getMessage());
        }

        if ($res->failed() || ! $res->json('access_token')) {
            return $fail('Token exchange rejected: '.substr($res->body(), 0, 300));
        }

        $token = $res->json('access_token');

        try {
            $me = Http::withToken($token)->get('https://api.twitter.com/2/users/me');
        } catch (\Throwable $e) {
            return $fail('Connected but could not read the profile: '.$e->getMessage());
        }

        $user = $me->json('data');

        if (! $user) {
            return $fail('Connected but X returned no profile: '.substr($me->body(), 0, 200));
        }

        return [
            'ok' => true,
            'accounts' => [[
                'external_id' => $user['id'],
                'label' => '@'.$user['username'],
                'access_token' => $token,
                'refresh_token' => $res->json('refresh_token'),
                'expires_at' => $res->json('expires_in') ? Carbon::now()->addSeconds((int) $res->json('expires_in')) : null,
                'scopes' => explode(' ', self::SCOPES),
                'meta' => ['username' => $user['username']],
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
            $res = Http::asForm()
                ->withBasicAuth($c['x_client_id'], $c['x_client_secret'])
                ->post('https://api.twitter.com/2/oauth2/token', [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $account->refresh_token,
                    'client_id' => $c['x_client_id'],
                ]);
        } catch (\Throwable $e) {
            return null;
        }

        if ($res->failed() || ! $res->json('access_token')) {
            return null;
        }

        return [
            'access_token' => $res->json('access_token'),
            // X rotates the refresh token on every use — losing the new one
            // strands the account after the first refresh.
            'refresh_token' => $res->json('refresh_token') ?: $account->refresh_token,
            'expires_at' => $res->json('expires_in') ? Carbon::now()->addSeconds((int) $res->json('expires_in')) : null,
        ];
    }

    public function publish(object $account, SocialPostPayload $payload): array
    {
        $fail = fn (string $e) => ['ok' => false, 'remote_id' => null, 'permalink' => null, 'error' => $e];
        $mediaIds = [];

        if ($payload->mediaUrl || $payload->mediaPath) {
            $uploaded = $this->uploadMedia($account, $payload);

            if (! $uploaded['ok']) {
                return $fail($uploaded['error']);
            }

            $mediaIds[] = $uploaded['media_id'];
        }

        try {
            $res = Http::withToken($account->access_token)->asJson()->timeout(30)
                ->post('https://api.twitter.com/2/tweets', array_filter([
                    'text' => $payload->fullText(280),
                    'media' => $mediaIds ? ['media_ids' => $mediaIds] : null,
                ]));
        } catch (\Throwable $e) {
            return $fail($e->getMessage());
        }

        if ($res->failed() || ! $res->json('data.id')) {
            return $fail('HTTP '.$res->status().' :: '.substr($res->body(), 0, 300));
        }

        $id = $res->json('data.id');
        $username = is_string($account->meta ?? null)
            ? (json_decode($account->meta, true)['username'] ?? 'i')
            : (($account->meta['username'] ?? null) ?: 'i');

        return ['ok' => true, 'remote_id' => $id, 'permalink' => "https://x.com/{$username}/status/{$id}", 'error' => ''];
    }

    /** @return array{ok:bool,media_id:?string,error:string} */
    protected function uploadMedia(object $account, SocialPostPayload $payload): array
    {
        $fail = fn (string $e) => ['ok' => false, 'media_id' => null, 'error' => $e];

        $path = $payload->mediaPath;
        $tempFile = null;

        // X's upload endpoint needs raw bytes; if we only have a URL, fetch it.
        if (! $path && $payload->mediaUrl) {
            try {
                $bytes = Http::timeout(60)->get($payload->mediaUrl)->body();
            } catch (\Throwable $e) {
                return $fail('Could not fetch media for upload: '.$e->getMessage());
            }

            $tempFile = tempnam(sys_get_temp_dir(), 'x_media_');
            file_put_contents($tempFile, $bytes);
            $path = $tempFile;
        }

        if (! $path || ! is_file($path)) {
            return $fail('No media bytes available to upload.');
        }

        $size = filesize($path);
        $mime = $payload->mediaMime ?: (str_ends_with($path, '.png') ? 'image/png' : 'image/jpeg');
        $category = $payload->isVideo ? 'tweet_video' : 'tweet_image';

        try {
            $init = Http::withToken($account->access_token)->asForm()->post(
                'https://upload.twitter.com/1.1/media/upload.json',
                ['command' => 'INIT', 'media_type' => $mime, 'total_bytes' => $size, 'media_category' => $category]
            );

            if ($init->failed() || ! $init->json('media_id_string')) {
                return $fail('INIT rejected: HTTP '.$init->status().' :: '.substr($init->body(), 0, 250));
            }

            $mediaId = $init->json('media_id_string');
            $handle = fopen($path, 'rb');
            $segment = 0;

            while (! feof($handle)) {
                $chunk = fread($handle, self::CHUNK_BYTES);

                $append = Http::withToken($account->access_token)->asMultipart()->post(
                    'https://upload.twitter.com/1.1/media/upload.json',
                    [
                        ['name' => 'command', 'contents' => 'APPEND'],
                        ['name' => 'media_id', 'contents' => $mediaId],
                        ['name' => 'segment_index', 'contents' => (string) $segment],
                        ['name' => 'media', 'contents' => $chunk, 'filename' => 'chunk'],
                    ]
                );

                if ($append->failed()) {
                    fclose($handle);

                    return $fail('APPEND rejected at segment '.$segment.': HTTP '.$append->status());
                }

                $segment++;
            }

            fclose($handle);

            $finalize = Http::withToken($account->access_token)->asForm()->post(
                'https://upload.twitter.com/1.1/media/upload.json',
                ['command' => 'FINALIZE', 'media_id' => $mediaId]
            );

            if ($finalize->failed()) {
                return $fail('FINALIZE rejected: HTTP '.$finalize->status());
            }

            $processing = $finalize->json('processing_info');

            if ($processing && ($processing['state'] ?? '') !== 'succeeded') {
                $poll = $this->pollProcessing($account, $mediaId);

                if (! $poll['ok']) {
                    return $fail($poll['error']);
                }
            }

            return ['ok' => true, 'media_id' => $mediaId, 'error' => ''];
        } finally {
            if ($tempFile && is_file($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    protected function pollProcessing(object $account, string $mediaId): array
    {
        for ($i = 0; $i < 12; $i++) {
            try {
                $res = Http::withToken($account->access_token)->get(
                    'https://upload.twitter.com/1.1/media/upload.json',
                    ['command' => 'STATUS', 'media_id' => $mediaId]
                );
            } catch (\Throwable $e) {
                return ['ok' => false, 'error' => 'STATUS check failed: '.$e->getMessage()];
            }

            $state = $res->json('processing_info.state');

            if ($state === 'succeeded') {
                return ['ok' => true, 'error' => ''];
            }

            if ($state === 'failed') {
                return ['ok' => false, 'error' => 'X failed to process the media: '.($res->json('processing_info.error.message') ?? 'unknown error')];
            }

            sleep(max(1, (int) ($res->json('processing_info.check_after_secs') ?? 3)));
        }

        return ['ok' => false, 'error' => 'Media processing did not finish in time.'];
    }

    public function metrics(object $account, string $remoteId): array
    {
        $fail = fn (string $e) => ['ok' => false, 'reach' => null, 'likes' => null, 'comments' => null, 'shares' => null, 'error' => $e];

        try {
            $res = Http::withToken($account->access_token)->get("https://api.twitter.com/2/tweets/{$remoteId}", [
                'tweet.fields' => 'public_metrics',
            ]);
        } catch (\Throwable $e) {
            return $fail($e->getMessage());
        }

        $m = $res->json('data.public_metrics');

        if (! $m) {
            return $fail('HTTP '.$res->status().' :: '.substr($res->body(), 0, 200));
        }

        return [
            'ok' => true,
            'reach' => $m['impression_count'] ?? null,
            'likes' => $m['like_count'] ?? null,
            'comments' => $m['reply_count'] ?? null,
            'shares' => $m['retweet_count'] ?? null,
            'error' => '',
        ];
    }
}
