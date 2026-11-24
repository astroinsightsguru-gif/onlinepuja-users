<?php

namespace App\Console\Commands;

use App\Brain\Vault;
use App\Media\ImageStudio;
use App\Services\BrevoService;
use App\Services\OneSignalService;
use App\Support\LiteSpeedCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ContentPublishCommand extends Command
{
    // Descriptions must not contain a literal "--": Laravel's signature parser
    // scans for that token and would register a phantom option, silently
    // dropping the argument it belonged to.
    protected $signature = 'content:publish
        {id? : content_plans id; omit it in auto mode}
        {--auto : Publish every eligible plan instead of one by id}
        {--limit=3 : Max plans to publish in auto mode}
        {--no-image : Skip hero image generation}
        {--no-notify : Skip the Brevo subscriber announcement}';

    protected $description = 'Publish approved drafts into the live blogs table, with hero image and subscriber notification';

    public function handle(ImageStudio $images, BrevoService $brevo): int
    {
        $auto = (bool) $this->option('auto');
        $id = $this->argument('id');

        if (! $auto && ! $id) {
            $this->error('Pass a content_plans id, or use --auto to publish everything eligible.');

            return self::FAILURE;
        }

        $plans = $auto ? $this->eligiblePlans() : $this->singlePlan((int) $id);

        if ($plans->isEmpty()) {
            $this->info($auto ? 'Nothing eligible to publish.' : 'Plan not found or not in a publishable state.');

            return $auto ? self::SUCCESS : self::FAILURE;
        }

        $published = 0;
        $failed = 0;
        $publishedUrls = [];

        foreach ($plans as $plan) {
            $res = $this->publishOne($plan, $images, $brevo);

            if ($res['ok']) {
                $published++;
                $publishedUrls[] = $res['url'];
                $this->info("PUBLISHED: {$res['url']}".($res['notified'] ? " · notified {$res['notified']}" : ''));
            } else {
                $failed++;
                $this->error("Plan #{$plan->id}: {$res['error']}");
            }
        }

        $this->line("Published: {$published} · Failed: {$failed}");

        \App\Brain\RunLog::recordUnlessNoop(
            'content:publish', 'blog',
            $failed > 0 ? ($published > 0 ? 'partial' : 'fail') : 'ok',
            "Published {$published} article(s)".($failed ? ", {$failed} failed" : ''),
            ['urls' => $publishedUrls ?? []],
            $published, $failed
        );

        return self::SUCCESS;
    }

    /**
     * In auto mode only 'approved' publishes by default — a human gate.
     * Setting auto_publish_drafted=1 in the vault lets Critic-passed drafts
     * go straight out, which is full autonomy and should be a deliberate choice.
     */
    protected function eligiblePlans()
    {
        $statuses = ['approved'];

        if (Vault::get('auto_publish_drafted') === '1') {
            $statuses[] = 'drafted';
        }

        return DB::table('content_plans')
            ->where('channel', 'web')
            ->whereIn('status', $statuses)
            // Honour a scheduled publish time: a plan with a future due_at is
            // held back until that moment, so we can queue a burst now and let
            // it drip out on the calendar. A null due_at means "as soon as
            // eligible" — the original behaviour, unchanged.
            ->where(function ($q) {
                $q->whereNull('due_at')->orWhere('due_at', '<=', now());
            })
            ->orderByRaw('due_at IS NULL, due_at')
            ->orderBy('id')
            ->limit(max(1, (int) $this->option('limit')))
            ->get();
    }

    protected function singlePlan(int $id)
    {
        return DB::table('content_plans')
            ->where('id', $id)
            ->whereIn('status', ['drafted', 'approved'])
            ->get();
    }

    /** @return array{ok:bool,url:?string,notified:int,error:string} */
    protected function publishOne(object $plan, ImageStudio $images, BrevoService $brevo): array
    {
        $fail = fn (string $e) => ['ok' => false, 'url' => null, 'notified' => 0, 'error' => $e];

        $payload = json_decode((string) $plan->payload, true);
        $slug = trim((string) ($payload['slug'] ?? ''));
        $html = (string) ($payload['html'] ?? '');

        if ($slug === '' || $html === '') {
            return $fail('Payload missing slug/html.');
        }

        $cols = collect(DB::select('SHOW COLUMNS FROM blogs'))->pluck('Field')->flip();

        $contentCol = collect(['content', 'description', 'detail', 'body', 'post_content'])
            ->first(fn ($c) => isset($cols[$c]));

        if (! $contentCol) {
            return $fail('No content-like column found in blogs table.');
        }

        // Slug collisions would 404 the older post or break the unique index.
        $slug = $this->uniqueSlug($slug);

        // Weave in contextual internal links to related published posts before
        // the article goes live — the whole cluster benefits immediately.
        $linked = \App\Brain\InternalLinker::inject($html, $slug);
        $html = $linked['html'];

        if ($linked['links_added'] > 0) {
            $this->line("  internal links added: {$linked['links_added']}");
        }

        // Hero image — a published article with no image looks unfinished on
        // the listing page and has nothing to share to social.
        $imageUrl = null;
        $imagePath = '';

        if (! $this->option('no-image')) {
            $gen = $images->generate($plan->title, ['preset' => 'og', 'content_plan_id' => $plan->id]);

            if ($gen['ok']) {
                $imagePath = $gen['path'];
                $imageUrl = $gen['url'];
            } else {
                $this->warn("  image generation failed ({$gen['error']}) — publishing without one.");
            }
        }

        $meta = $payload['meta_description'] ?? null;

        $row = [$contentCol => $html];

        $map = [
            'title' => $plan->title,
            'slug' => $slug,
            // Carried from the keyword so the page can declare its language.
            'language' => $payload['language'] ?? 'en',
            'meta_description' => $meta,
            'status' => 'published',
            'user_id' => 1,
            // NOT NULL with no default on this table.
            'blogImage' => $imagePath,
            'author' => Vault::get('blog_default_author', 'Online Puja Editorial'),
            'createdBy' => 1,
            'modifiedBy' => 1,
            'postedOn' => now(),
            'isActive' => 1,
        ];

        foreach ($map as $col => $val) {
            if ($val !== null && isset($cols[$col])) {
                $row[$col] = $val;
            }
        }

        $row['created_at'] = $row['updated_at'] = now();

        try {
            $blogId = DB::table('blogs')->insertGetId($row);
        } catch (\Throwable $e) {
            return $fail('Insert failed: '.$e->getMessage());
        }

        $url = url('blog/'.$slug);

        DB::table('content_plans')->where('id', $plan->id)->update([
            'status' => 'published',
            'posted_url' => $url,
            'updated_at' => now(),
        ]);

        // The listing page and homepage are cached without this post.
        LiteSpeedCache::purgeFromCli('/blog/'.$slug, '/blog', '/');

        // Without this the article is live but absent from the sitemap, so
        // crawlers (and our own SiteAuditor, which reads the same file) never
        // learn it exists — which would quietly defeat the whole auto-publish
        // loop.
        $sm = \App\Support\SitemapGenerator::generate();

        if (! $sm['ok']) {
            $this->warn('  sitemap: '.$sm['error']);
        }

        DB::table('outcomes')->insert([
            'channel' => 'web', 'entity_type' => 'blog', 'entity_id' => $plan->id,
            'metric' => 'published', 'value' => 1, 'at' => now(),
            'meta' => json_encode(['url' => $url, 'blog_id' => $blogId]),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Notify subscribers. Never let this fail the publish — the article
        // is already live and the row is already committed.
        $notified = 0;

        if (! $this->option('no-notify') && $brevo->isConfigured()) {
            try {
                $excerpt = $meta ?: \Illuminate\Support\Str::limit(
                    trim(preg_replace('/\s+/', ' ', strip_tags($html))), 180
                );
                $n = $brevo->notifyBlogPublished($plan->title, $url, $excerpt, $imageUrl);
                $notified = $n['sent'];

                if (! $n['ok']) {
                    $this->warn("  Brevo notify: {$n['error']}");
                } else {
                    DB::table('outcomes')->insert([
                        'channel' => 'email', 'entity_type' => 'blog', 'entity_id' => $plan->id,
                        'metric' => 'notified', 'value' => $n['sent'], 'at' => now(),
                        'meta' => json_encode(['batches' => $n['batches']]),
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            } catch (\Throwable $e) {
                $this->warn('  Brevo notify threw: '.$e->getMessage());
            }
        }

        // Web/app push via FCM — a second, free re-engagement channel to every
        // device that opted in. Best-effort; never fails the publish.
        if (! $this->option('no-notify')) {
            try {
                $pushed = $this->pushNewArticle($plan->title, $url, $meta ?: '');

                if ($pushed > 0) {
                    $this->line("  push sent to {$pushed} device(s)");
                    DB::table('outcomes')->insert([
                        'channel' => 'push', 'entity_type' => 'blog', 'entity_id' => $plan->id,
                        'metric' => 'pushed', 'value' => $pushed, 'at' => now(),
                        'meta' => json_encode(['url' => $url]),
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            } catch (\Throwable $e) {
                $this->warn('  push threw: '.$e->getMessage());
            }
        }

        return ['ok' => true, 'url' => $url, 'notified' => $notified, 'error' => ''];
    }

    /**
     * Push a "new article" notification to every opted-in device.
     *
     * Was FCM-only, and FCM has never actually been configured on this app —
     * every FIREBASE_ / FCM_ value in .env is blank, so FCMService::send()
     * hit an empty project id and silently no-op'd on every single publish.
     * Meanwhile OneSignal is the channel this codebase already uses for every
     * other real push (puja reminders, live-schedule reminders, tickets,
     * gifts) — real AppId/RestApiKey in systemflag, real player ids already
     * landing in user_device_details.subscription_id on login. This tries
     * OneSignal first since it is the one that actually works today, and
     * falls through to FCM only if it every gets configured later — same
     * ordered-fallback shape as the OTP channel chain (App\services\OtpService).
     *
     * @return int devices targeted (0 if neither channel is configured, or none opted in)
     */
    protected function pushNewArticle(string $title, string $url, string $excerpt): int
    {
        $notification = [
            'title' => 'New on Online Puja: '.\Illuminate\Support\Str::limit($title, 80),
            'body' => [
                'type' => 'blog',
                'title' => $title,
                'description' => \Illuminate\Support\Str::limit($excerpt ?: $title, 160),
                'url' => $url,
                'click_action' => $url,
            ],
        ];

        $oneSignalCount = $this->pushViaOneSignal($notification);

        if ($oneSignalCount > 0) {
            return $oneSignalCount;
        }

        return $this->pushViaFcm($notification);
    }

    protected function pushViaOneSignal(array $notification): int
    {
        // OneSignalService reads its own config straight from systemflag
        // (OneSignalAppId / OneSignalRestApiKey) — check the same source
        // rather than a vault key this app has never used for this.
        $appId = (string) DB::table('systemflag')->where('name', 'OneSignalAppId')->value('value');
        $apiKey = (string) DB::table('systemflag')->where('name', 'OneSignalRestApiKey')->value('value');

        if ($appId === '' || $apiKey === '') {
            return 0;
        }

        $playerIds = DB::table('user_device_details')
            ->where(fn ($q) => $q->whereNotNull('subscription_id')->orWhereNotNull('subscription_id_web'))
            ->get(['subscription_id', 'subscription_id_web'])
            ->flatMap(fn ($d) => [$d->subscription_id, $d->subscription_id_web])
            ->filter()
            ->unique()
            ->values();

        try {
            $service = new OneSignalService();
            // Broadcast to all active subscribers via Total Subscriptions segment
            $res = $service->sendNotification([], [
                'title' => $notification['title'],
                'body' => $notification['body'] + ['notificationType' => 90],
                'url' => $notification['body']['url'] ?? null,
            ]);

            $pushed = (int) ($res['recipients'] ?? 0);
            if ($pushed > 0) {
                return $pushed;
            }

            // Fallback: If player IDs exist in local DB, attempt chunked dispatch
            if (!$playerIds->isEmpty()) {
                foreach ($playerIds->chunk(500) as $chunk) {
                    $r = $service->sendNotification($chunk->all(), [
                        'title' => $notification['title'],
                        'body' => $notification['body'] + ['notificationType' => 90],
                        'url' => $notification['body']['url'] ?? null,
                    ]);
                    if (!empty($r['id']) && empty($r['errors'])) {
                        $pushed += count($chunk);
                    }
                }
            }

            return $pushed;
        } catch (\Throwable $e) {
            $this->warn('  OneSignal push threw: '.$e->getMessage());

            return 0;
        }
    }

    /** Kept as the fallback in case the FIREBASE_ / FCM_ .env values ever get filled in. */
    protected function pushViaFcm(array $notification): int
    {
        $devices = DB::table('user_device_details')
            ->whereNotNull('fcmToken')
            ->where('fcmToken', '!=', '')
            ->get();

        if ($devices->isEmpty() || config('fcm.project_id', '') === '') {
            return 0;
        }

        \App\Services\FCMService::send($devices, $notification);

        return $devices->count();
    }

    protected function uniqueSlug(string $slug): string
    {
        $base = $slug;
        $i = 1;

        while (DB::table('blogs')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
