<?php

namespace App\Social\Publishers;

use App\Brain\Vault;
use App\Social\Contracts\SocialPublisher;
use App\Social\Publishers\Concerns\UsesFacebookGraph;
use App\Social\SocialPostPayload;
use Illuminate\Support\Facades\Http;

class FacebookPagePublisher implements SocialPublisher
{
    use UsesFacebookGraph;

    /**
     * Candidate post-insight metrics, best first. Unknown names are dropped at
     * runtime by fbInsights(), so leaving the retired reach metrics here costs
     * one probe and makes them work again for free if Meta reinstates them or
     * the app is granted deeper access.
     */
    private const INSIGHT_METRICS = [
        'post_impressions_unique',
        'post_impressions_organic_unique',
        'post_reactions_by_type_total',
        'post_clicks',
    ];

    public function platform(): string
    {
        return 'facebook';
    }

    public function label(): string
    {
        return 'Facebook Page';
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
            if (empty($page['id']) || empty($page['access_token'])) {
                continue;
            }

            $accounts[] = [
                'external_id' => $page['id'],
                'label' => $page['name'] ?? ('Page '.$page['id']),
                'access_token' => $page['access_token'],
                'refresh_token' => null,       // Page tokens don't rotate this way
                'expires_at' => null,          // effectively long-lived
                'scopes' => explode(',', $this->fbScopes()),
                'meta' => [
                    'has_linked_instagram' => isset($page['instagram_business_account']),
                ],
            ];
        }

        if (! $accounts) {
            return ['ok' => false, 'accounts' => [], 'error' => 'This Facebook account does not manage any Pages.'];
        }

        return ['ok' => true, 'accounts' => $accounts, 'error' => ''];
    }

    public function refresh(object $account): ?array
    {
        return null; // Page tokens do not need refreshing
    }

    public function publish(object $account, SocialPostPayload $payload): array
    {
        $fail = fn (string $e) => ['ok' => false, 'remote_id' => null, 'permalink' => null, 'error' => $e];
        $pageId = $account->external_id;
        $token = $account->access_token;
        $text = $payload->fullText(63000); // FB post text ceiling

        try {
            if ($payload->isVideo && $payload->mediaUrl) {
                // Standard Page video, not a Reel — the Reels upload-session
                // API is a separate resumable flow, left as a follow-up.
                $res = Http::asForm()->timeout(120)->post("{$this->fbBase()}/{$pageId}/videos", [
                    'file_url' => $payload->mediaUrl,
                    'description' => $text,
                    'access_token' => $token,
                ]);
            } elseif ($payload->mediaUrl) {
                $res = Http::asForm()->timeout(60)->post("{$this->fbBase()}/{$pageId}/photos", [
                    'url' => $payload->mediaUrl,
                    'caption' => $text,
                    'access_token' => $token,
                ]);
            } else {
                $res = Http::asForm()->timeout(30)->post("{$this->fbBase()}/{$pageId}/feed", array_filter([
                    'message' => $text,
                    'link' => $payload->linkUrl,
                    'access_token' => $token,
                ]));
            }
        } catch (\Throwable $e) {
            return $fail($e->getMessage());
        }

        if ($res->failed()) {
            return $fail($this->fbGraphError($res));
        }

        $id = $res->json('post_id') ?? $res->json('id');

        if (! $id) {
            return $fail('Facebook accepted the request but returned no post id: '.$res->body());
        }

        // /photos and /videos return the media id, not the feed post id —
        // post_id (present on photos) is the one that resolves as a permalink.
        $permalinkId = $res->json('post_id') ?? $id;

        return [
            'ok' => true,
            'remote_id' => $id,
            'permalink' => "https://www.facebook.com/{$permalinkId}",
            'error' => '',
        ];
    }

    /**
     * Meta retired every `post_impressions*` metric in v21, and reading a Page
     * post's own reactions/comments now needs `pages_read_user_content` on top
     * of `pages_read_engagement`. Neither is worth voiding the whole collection
     * over: the old all-or-nothing request meant one missing scope left
     * `outcomes` empty and the Learner permanently "not ready". This returns
     * whatever the current grant allows and reports the rest as a note.
     */
    public function metrics(object $account, string $remoteId): array
    {
        $out = ['ok' => false, 'reach' => null, 'likes' => null, 'comments' => null, 'shares' => null, 'error' => ''];
        $notes = [];
        $token = $account->access_token;

        // `shares` is readable with the publishing grant alone; the two
        // summaries are not, so fall back to shares-only when they are refused.
        $node = $this->fbGet("{$this->fbBase()}/{$remoteId}", [
            'fields' => 'shares,reactions.summary(true),comments.summary(true)',
            'access_token' => $token,
        ]);

        if (! $node['ok']) {
            $notes[] = 'reactions/comments unavailable ('.$node['error'].')';
            $node = $this->fbGet("{$this->fbBase()}/{$remoteId}", [
                'fields' => 'shares',
                'access_token' => $token,
            ]);
        }

        if ($node['ok']) {
            $out['ok'] = true;
            $out['shares'] = data_get($node['json'], 'shares.count');
            $out['likes'] = data_get($node['json'], 'reactions.summary.total_count');
            $out['comments'] = data_get($node['json'], 'comments.summary.total_count');
        } else {
            $notes[] = $node['error'];
        }

        $insights = $this->fbInsights($remoteId, $token, $notes);

        if ($insights !== []) {
            $out['ok'] = true;
        }

        $out['reach'] = $insights['post_impressions_unique']
            ?? $insights['post_impressions_organic_unique']
            ?? null;

        // Reactions-by-type is a dict keyed by reaction name ("like", "love",
        // ...); its total is the closest stand-in for the reactions summary
        // when that scope is missing.
        if ($out['likes'] === null && is_array($insights['post_reactions_by_type_total'] ?? null)) {
            $out['likes'] = array_sum($insights['post_reactions_by_type_total']);
        }

        $out['error'] = $out['ok'] ? implode('; ', $notes) : ($notes[0] ?? 'no metrics available');

        return $out;
    }

    /**
     * Graph rejects the entire request with code 100 when a single metric name
     * is unknown, and Meta renames these between versions — so probe once per
     * process, keep the names this Graph version actually accepts, and reuse
     * them for every remaining post in the run.
     *
     * @param  list<string>  $notes
     * @return array<string,mixed>
     */
    private function fbInsights(string $remoteId, string $token, array &$notes): array
    {
        static $supported = null;

        $fetch = fn (array $metrics) => $this->fbGet("{$this->fbBase()}/{$remoteId}/insights", [
            'metric' => implode(',', $metrics),
            'access_token' => $token,
        ]);

        if ($supported === null) {
            $res = $fetch(self::INSIGHT_METRICS);

            if ($res['ok']) {
                $supported = self::INSIGHT_METRICS;
            } elseif ($res['code'] === 100) {
                $supported = array_values(array_filter(
                    self::INSIGHT_METRICS,
                    fn (string $m) => $fetch([$m])['ok']
                ));
            } else {
                $notes[] = 'insights unavailable ('.$res['error'].')';

                return [];
            }
        }

        if ($supported === []) {
            return [];
        }

        $res = $fetch($supported);

        if (! $res['ok']) {
            $notes[] = 'insights unavailable ('.$res['error'].')';

            return [];
        }

        $values = [];

        foreach ($res['json']['data'] ?? [] as $row) {
            $values[$row['name'] ?? ''] = $row['values'][0]['value'] ?? null;
        }

        return $values;
    }
}
