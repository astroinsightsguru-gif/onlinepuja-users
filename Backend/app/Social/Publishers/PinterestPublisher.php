<?php

namespace App\Social\Publishers;

use App\Brain\Vault;
use App\Social\Contracts\SocialPublisher;
use App\Social\SocialPostPayload;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

/** Pinterest API v5 — OAuth 2.0 authorization-code flow. */
class PinterestPublisher implements SocialPublisher
{
    protected const SCOPES = 'boards:read,pins:read,pins:write,user_accounts:read';

    public function platform(): string
    {
        return 'pinterest';
    }

    public function label(): string
    {
        return 'Pinterest';
    }

    public function isAppConfigured(): bool
    {
        $c = Vault::socialCredentials();

        return $c['pinterest_app_id'] !== '' && $c['pinterest_app_secret'] !== '';
    }

    public function authorizeUrl(string $state, string $redirectUri): string
    {
        $q = http_build_query([
            'client_id' => Vault::socialCredentials()['pinterest_app_id'],
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => self::SCOPES,
            'state' => $state,
        ]);

        return "https://www.pinterest.com/oauth/?{$q}";
    }

    public function handleCallback(string $code, string $redirectUri, string $state): array
    {
        $fail = fn (string $e) => ['ok' => false, 'accounts' => [], 'error' => $e];
        $c = Vault::socialCredentials();

        try {
            $res = Http::asForm()
                ->withBasicAuth($c['pinterest_app_id'], $c['pinterest_app_secret'])
                ->post('https://api.pinterest.com/v5/oauth/token', [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $redirectUri,
                ]);
        } catch (\Throwable $e) {
            return $fail('Token exchange failed: '.$e->getMessage());
        }

        if ($res->failed() || ! $res->json('access_token')) {
            return $fail('Token exchange rejected: '.substr($res->body(), 0, 300));
        }

        $token = $res->json('access_token');
        $refresh = $res->json('refresh_token');
        $expiresAt = $res->json('expires_in') ? Carbon::now()->addSeconds((int) $res->json('expires_in')) : null;

        try {
            $me = Http::withToken($token)->get('https://api.pinterest.com/v5/user_account');
            $boards = Http::withToken($token)->get('https://api.pinterest.com/v5/boards', ['page_size' => 25]);
        } catch (\Throwable $e) {
            return $fail('Connected but could not read the profile: '.$e->getMessage());
        }

        $username = $me->json('username') ?: 'pinterest-account';
        $boardList = collect((array) $boards->json('items', []))
            ->map(fn ($b) => ['id' => $b['id'], 'name' => $b['name']])->values()->all();

        return [
            'ok' => true,
            'accounts' => [[
                'external_id' => $username,
                'label' => '@'.$username,
                'access_token' => $token,
                'refresh_token' => $refresh,
                'expires_at' => $expiresAt,
                'scopes' => explode(',', self::SCOPES),
                'meta' => [
                    'boards' => $boardList,
                    'default_board_id' => $boardList[0]['id'] ?? null,
                ],
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
                ->withBasicAuth($c['pinterest_app_id'], $c['pinterest_app_secret'])
                ->post('https://api.pinterest.com/v5/oauth/token', [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $account->refresh_token,
                ]);
        } catch (\Throwable $e) {
            return null;
        }

        if ($res->failed() || ! $res->json('access_token')) {
            return null;
        }

        return [
            'access_token' => $res->json('access_token'),
            'refresh_token' => $res->json('refresh_token') ?: $account->refresh_token,
            'expires_at' => $res->json('expires_in') ? Carbon::now()->addSeconds((int) $res->json('expires_in')) : null,
        ];
    }

    public function publish(object $account, SocialPostPayload $payload): array
    {
        $fail = fn (string $e) => ['ok' => false, 'remote_id' => null, 'permalink' => null, 'error' => $e];

        if (! $payload->mediaUrl) {
            return $fail('Pinterest requires an image — text-only pins are not supported.');
        }

        $meta = is_string($account->meta ?? null) ? json_decode($account->meta, true) : (array) ($account->meta ?? []);
        $boardId = $meta['default_board_id'] ?? null;

        if (! $boardId) {
            return $fail('No board is configured on this Pinterest account.');
        }

        try {
            $res = Http::withToken($account->access_token)->asJson()->timeout(60)
                ->post('https://api.pinterest.com/v5/pins', array_filter([
                    'board_id' => $boardId,
                    'title' => mb_substr($payload->caption, 0, 100),
                    'description' => $payload->fullText(500),
                    'link' => $payload->linkUrl,
                    'media_source' => [
                        'source_type' => 'image_url',
                        'url' => $payload->mediaUrl,
                    ],
                ]));
        } catch (\Throwable $e) {
            return $fail($e->getMessage());
        }

        if ($res->failed() || ! $res->json('id')) {
            return $fail('HTTP '.$res->status().' :: '.substr($res->body(), 0, 300));
        }

        $id = $res->json('id');

        return ['ok' => true, 'remote_id' => $id, 'permalink' => "https://www.pinterest.com/pin/{$id}/", 'error' => ''];
    }

    public function metrics(object $account, string $remoteId): array
    {
        $fail = fn (string $e) => ['ok' => false, 'reach' => null, 'likes' => null, 'comments' => null, 'shares' => null, 'error' => $e];

        $to = Carbon::now()->format('Y-m-d');
        $from = Carbon::now()->subDays(30)->format('Y-m-d');

        try {
            $res = Http::withToken($account->access_token)->get("https://api.pinterest.com/v5/pins/{$remoteId}/analytics", [
                'start_date' => $from,
                'end_date' => $to,
                'metric_types' => 'IMPRESSION,SAVE,PIN_CLICK',
            ]);
        } catch (\Throwable $e) {
            return $fail($e->getMessage());
        }

        if ($res->failed()) {
            return $fail('HTTP '.$res->status().' :: '.substr($res->body(), 0, 200));
        }

        $daily = $res->json('all.daily_metrics', []);
        $sum = fn (string $key) => collect($daily)->sum(fn ($d) => $d['metrics'][$key] ?? 0);

        return [
            'ok' => true,
            'reach' => $sum('IMPRESSION'),
            'likes' => $sum('SAVE'),
            'comments' => null,
            'shares' => $sum('PIN_CLICK'),
            'error' => '',
        ];
    }
}
