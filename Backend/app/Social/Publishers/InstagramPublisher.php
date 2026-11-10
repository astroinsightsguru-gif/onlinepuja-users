<?php

namespace App\Social\Publishers;

use App\Brain\Vault;
use App\Social\Contracts\SocialPublisher;
use App\Social\Publishers\Concerns\UsesFacebookGraph;
use App\Social\SocialPostPayload;
use Illuminate\Support\Facades\Http;

/**
 * Instagram Graph API. There is no separate Instagram OAuth app — this runs
 * through the same Meta app as Facebook, and every call is authenticated
 * with the access token of the Facebook Page the IG Business account is
 * linked to (external_id here is the IG user id; meta.page_id is where the
 * token actually comes from).
 */
class InstagramPublisher implements SocialPublisher
{
    use UsesFacebookGraph;

    /** IG media processing can take a while — bound how long we'll wait. */
    protected const POLL_ATTEMPTS = 12;
    protected const POLL_DELAY_SECONDS = 5;

    /** An image container settles in a second or two, not half a minute. */
    protected const IMAGE_POLL_ATTEMPTS = 6;
    protected const IMAGE_POLL_DELAY_SECONDS = 2;

    public function platform(): string
    {
        return 'instagram';
    }

    public function label(): string
    {
        return 'Instagram Business';
    }

    public function isAppConfigured(): bool
    {
        $c = Vault::socialCredentials();

        return $c['fb_app_id'] !== '' && $c['fb_app_secret'] !== '';
    }

    public function authorizeUrl(string $state, string $redirectUri): string
    {
        return $this->fbAuthorizeUrl($state, $redirectUri);
    }

    public function handleCallback(string $code, string $redirectUri, string $state): array
    {
        $res = $this->fbResolvePages($code, $redirectUri);

        if (! $res['ok']) {
            return ['ok' => false, 'accounts' => [], 'error' => $res['error']];
        }

        $accounts = [];

        foreach ($res['pages'] as $page) {
            $ig = $page['instagram_business_account'] ?? null;

            if (! $ig || empty($ig['id']) || empty($page['access_token'])) {
                continue;
            }

            $accounts[] = [
                'external_id' => $ig['id'],
                'label' => '@'.($ig['username'] ?? $ig['id']),
                'access_token' => $page['access_token'], // IG calls use the linked Page's token
                'refresh_token' => null,
                'expires_at' => null,
                'scopes' => explode(',', $this->fbScopes()),
                'meta' => [
                    'page_id' => $page['id'],
                    'page_name' => $page['name'] ?? null,
                    'username' => $ig['username'] ?? null,
                ],
            ];
        }

        if (! $accounts) {
            return ['ok' => false, 'accounts' => [],
                'error' => 'No Facebook Page with a linked Instagram Business account was found. '.
                           'Link an Instagram Business/Creator account to a Facebook Page first.', ];
        }

        return ['ok' => true, 'accounts' => $accounts, 'error' => ''];
    }

    public function refresh(object $account): ?array
    {
        return null; // rides on the linked Page's token
    }

    public function publish(object $account, SocialPostPayload $payload): array
    {
        $fail = fn (string $e) => ['ok' => false, 'remote_id' => null, 'permalink' => null, 'error' => $e];

        if (! $payload->mediaUrl) {
            return $fail('Instagram requires an image or video — text-only posts are not supported.');
        }

        $igId = $account->external_id;
        $token = $account->access_token;
        $caption = $payload->fullText(2200); // IG caption ceiling

        try {
            $create = Http::asForm()->timeout(60)->post("{$this->fbBase()}/{$igId}/media", array_filter([
                'caption' => $caption,
                'image_url' => $payload->isVideo ? null : $payload->mediaUrl,
                'video_url' => $payload->isVideo ? $payload->mediaUrl : null,
                'media_type' => $payload->isVideo ? 'REELS' : null,
                'access_token' => $token,
            ]));
        } catch (\Throwable $e) {
            return $fail('Container creation failed: '.$e->getMessage());
        }

        if ($create->failed() || ! $create->json('id')) {
            return $fail('Container creation rejected: '.$this->fbGraphError($create));
        }

        $containerId = $create->json('id');

        // Every container processes asynchronously, images included: publishing
        // one before Meta has finished ingesting it comes back as
        // "OAuthException: Media ID is not available", which reads like a dead
        // token but is pure timing. Wait for FINISHED whatever the media type.
        $ready = $this->waitUntilFinished($containerId, $token, $payload->isVideo);

        if (! $ready['ok']) {
            return $fail($ready['error']);
        }

        try {
            $publish = Http::asForm()->timeout(60)->post("{$this->fbBase()}/{$igId}/media_publish", [
                'creation_id' => $containerId,
                'access_token' => $token,
            ]);
        } catch (\Throwable $e) {
            return $fail('Publish step failed: '.$e->getMessage());
        }

        if ($publish->failed() || ! $publish->json('id')) {
            return $fail('Publish step rejected: '.$this->fbGraphError($publish));
        }

        $mediaId = $publish->json('id');
        $permalink = null;

        try {
            $meta = Http::get("{$this->fbBase()}/{$mediaId}", ['fields' => 'permalink', 'access_token' => $token]);
            $permalink = $meta->json('permalink');
        } catch (\Throwable $e) {
            // Non-fatal — the post succeeded, we just couldn't fetch its link.
        }

        return ['ok' => true, 'remote_id' => $mediaId, 'permalink' => $permalink, 'error' => ''];
    }

    protected function waitUntilFinished(string $containerId, string $token, bool $isVideo = true): array
    {
        $attempts = $isVideo ? self::POLL_ATTEMPTS : self::IMAGE_POLL_ATTEMPTS;
        $delay = $isVideo ? self::POLL_DELAY_SECONDS : self::IMAGE_POLL_DELAY_SECONDS;
        $what = $isVideo ? 'video' : 'image';

        for ($i = 0; $i < $attempts; $i++) {
            try {
                $res = Http::get("{$this->fbBase()}/{$containerId}", [
                    'fields' => 'status_code',
                    'access_token' => $token,
                ]);
            } catch (\Throwable $e) {
                return ['ok' => false, 'error' => 'Status check failed: '.$e->getMessage()];
            }

            $status = $res->json('status_code');

            // PUBLISHED shouldn't happen before media_publish, but if Meta says
            // the container is already live there is nothing left to wait for.
            if ($status === 'FINISHED' || $status === 'PUBLISHED') {
                return ['ok' => true, 'error' => ''];
            }

            if ($status === 'ERROR' || $status === 'EXPIRED') {
                return ['ok' => false, 'error' => "Instagram failed to process the {$what} (container {$status})."];
            }

            // No status_code at all means the field isn't being served for this
            // container — don't stall the queue on a check we can't perform.
            if ($status === null) {
                return ['ok' => true, 'error' => ''];
            }

            sleep($delay);
        }

        return ['ok' => false, 'error' => "Instagram {$what} processing did not finish in time — try publishing again shortly."];
    }

    /**
     * `like_count`/`comments_count` live on the media node and need only
     * `instagram_basic`, which every connected account already has. Reach comes
     * from /insights and needs `instagram_manage_insights`, a separate App
     * Review item — so it is fetched best-effort and its absence never voids
     * the counts that are readable today.
     */
    public function metrics(object $account, string $remoteId): array
    {
        $out = ['ok' => false, 'reach' => null, 'likes' => null, 'comments' => null, 'shares' => null, 'error' => ''];
        $notes = [];
        $token = $account->access_token;

        $node = $this->fbGet("{$this->fbBase()}/{$remoteId}", [
            'fields' => 'like_count,comments_count',
            'access_token' => $token,
        ]);

        if ($node['ok']) {
            $out['ok'] = true;
            $out['likes'] = $node['json']['like_count'] ?? null;
            $out['comments'] = $node['json']['comments_count'] ?? null;
        } else {
            $notes[] = $node['error'];
        }

        $ins = $this->fbGet("{$this->fbBase()}/{$remoteId}/insights", [
            'metric' => 'reach',
            'access_token' => $token,
        ]);

        if ($ins['ok']) {
            $out['ok'] = true;

            foreach ($ins['json']['data'] ?? [] as $row) {
                if (($row['name'] ?? '') === 'reach') {
                    $out['reach'] = $row['values'][0]['value'] ?? null;
                }
            }
        } else {
            $notes[] = 'reach unavailable ('.$ins['error'].')';
        }

        $out['error'] = $out['ok'] ? implode('; ', $notes) : ($notes[0] ?? 'no metrics available');

        return $out;
    }
}
