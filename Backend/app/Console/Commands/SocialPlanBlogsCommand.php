<?php

namespace App\Console\Commands;

use App\Social\PublisherManager;
use App\Social\SocialCopywriter;
use App\Social\SocialTiming;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Promotes published articles to the business social accounts.
 *
 * social:plan only fans out festival wishes, so the queue was feast-or-famine —
 * six posts on Navratri, then nothing for ten days. The blog, meanwhile,
 * publishes daily and every post is already a finished idea with a hero image
 * and a URL worth driving traffic to. This walks the published articles that
 * have never been promoted and drips them into the empty days, which costs no
 * extra image generation and no extra API spend beyond one caption call.
 */
class SocialPlanBlogsCommand extends Command
{
    protected $signature = 'social:plan-blogs
        {--limit=2 : Max articles to promote this run}
        {--per-day=1 : Target posts per platform per day}
        {--horizon=21 : Days ahead to spread posts across}';

    protected $description = 'Queue social posts promoting published articles, filling gaps in the posting calendar';

    public function handle(PublisherManager $manager, SocialCopywriter $copywriter): int
    {
        $accounts = $manager->connectedAccounts(businessOnly: true)
            ->where('status', 'connected')
            // YouTube needs a real video; it is fed by the video pipeline only.
            ->reject(fn ($a) => $a->platform === 'youtube');

        if ($accounts->isEmpty()) {
            $this->warn('No connected business social accounts — nothing to promote against.');

            return self::SUCCESS;
        }

        $perDay = max(1, (int) $this->option('per-day'));
        $horizon = max(1, (int) $this->option('horizon'));

        $limit = max(1, (int) $this->option('limit'));

        // Oldest-first, so the back catalogue drains in publication order
        // instead of the newest article being promoted over and over.
        //
        // Scan on id+posted_url only (the payload column is a longtext, and
        // this walks the whole back catalogue): a plan whose article has since
        // been retired is still a row here, so a windowed query could return
        // nothing but dead links while a live article waited behind them.
        $candidates = DB::table('content_plans')
            ->where('status', 'published')
            ->whereNotNull('posted_url')
            ->where('channel', '!=', 'social')
            ->whereNotIn('posted_url', function ($q) {
                $q->from('social_posts')->select('link_url')->whereNotNull('link_url');
            })
            ->orderBy('id')
            ->get(['id', 'posted_url']);

        $skipped = 0;
        $blogs = [];   // plan id => the live blog row behind it

        foreach ($candidates as $candidate) {
            if (count($blogs) >= $limit) {
                break;
            }

            $blog = $this->blogFor($candidate->posted_url);

            if (! $blog) {
                $this->line("Plan #{$candidate->id}: article retired or missing ({$candidate->posted_url}) — skipped.");
                $skipped++;

                continue;
            }

            $blogs[$candidate->id] = $blog;
        }

        $plans = $blogs
            ? DB::table('content_plans')->whereIn('id', array_keys($blogs))->orderBy('id')->get()
            : collect();

        if ($plans->isEmpty()) {
            $this->info('Nothing left to promote'.($skipped ? " ({$skipped} retired article(s) skipped)" : '').'.');

            \App\Brain\RunLog::recordUnlessNoop(
                'social:plan-blogs', 'social', 'ok', 'No unpromoted articles',
                ['queued' => 0, 'articles' => 0, 'skipped' => $skipped], 0, 0
            );

            return self::SUCCESS;
        }

        $queued = 0;
        $articles = 0;

        foreach ($plans as $plan) {
            $blog = $blogs[$plan->id];

            $mediaAssetId = $this->mediaFor($plan->id, $blog);

            // The article's own summary is a better post idea than the title
            // alone, and it is what the copywriter anchors its variants on.
            $summary = trim((string) ($blog->meta_description ?: ''));

            if ($summary === '') {
                $summary = trim(mb_substr(strip_tags((string) $blog->description), 0, 400));
            }

            $baseText = trim($blog->title."\n\n".$summary);

            // Work out where this article could actually go before paying for
            // captions — a saturated calendar should cost nothing.
            $slots = [];

            foreach ($accounts as $account) {
                if (! $mediaAssetId && ! in_array($account->platform, ['facebook', 'x'], true)) {
                    continue; // Instagram and Pinterest reject text-only posts.
                }

                $slot = SocialTiming::nextFreeSlot($account->platform, $perDay, $horizon);

                if ($slot !== null) {
                    $slots[$account->id] = $slot;
                }
            }

            if ($slots === []) {
                $this->line("Calendar is full for the next {$horizon} days — stopping here.");

                break;
            }

            try {
                $variants = $copywriter->variants($baseText, (string) $blog->title, null, $blog->language ?? 'en');
            } catch (\Throwable $e) {
                $this->warn("Plan #{$plan->id}: caption generation failed ({$e->getMessage()}) — using the summary as-is.");
                $variants = [];
            }

            $articles++;

            foreach ($accounts as $account) {
                if (! isset($slots[$account->id])) {
                    continue;
                }

                $slot = $slots[$account->id];

                $variant = $variants[$account->platform] ?? ['caption' => null, 'hashtags' => null];
                $rawCaption = $variant['caption'] ?: null;
                if (! $rawCaption) {
                    $isHi = (($blog->language ?? 'en') === 'hi');
                    $hook = $isHi ? "॥ ॐ नमः शिवाय ॥\n\n✨ " : "🕉️ Divine Vedic Wisdom & Blessings ✨\n\n";
                    $rawCaption = $hook . $blog->title . "\n\n" . $summary;
                }
                $finalCaption = $this->buildCaptionWithCta(
                    $rawCaption,
                    $account->platform,
                    $plan->posted_url ?: "https://onlinepuja.live/blog/{$blog->slug}",
                    $blog->language ?? 'en'
                );

                DB::table('social_posts')->insert([
                    'content_plan_id' => $plan->id,
                    'social_account_id' => $account->id,
                    'platform' => $account->platform,
                    'caption' => $finalCaption,
                    'hashtags' => $variant['hashtags'] ?? null,
                    'media_asset_id' => $mediaAssetId,
                    'link_url' => $plan->posted_url,
                    'scheduled_at' => $slot,
                    'status' => 'queued',
                    // Dimensions the Learner groups engagement by.
                    'meta' => json_encode([
                        'kind' => 'blog_promo',
                        'blog_id' => $blog->id,
                        'language' => $blog->language ?? 'en',
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $queued++;
            }
        }

        $this->info("Promoted {$articles} article(s) into {$queued} queued post(s)".($skipped ? " · {$skipped} skipped" : '').'.');

        // A retired article is a skip, not a failure — it must not colour the
        // run amber on the dashboard.
        \App\Brain\RunLog::recordUnlessNoop(
            'social:plan-blogs', 'social', 'ok',
            "Queued {$queued} post(s) promoting {$articles} article(s)",
            ['queued' => $queued, 'articles' => $articles, 'skipped' => $skipped],
            $queued, 0
        );

        return self::SUCCESS;
    }

    /** The live blog row behind a plan's posted_url, matched on its slug. */
    protected function blogFor(string $postedUrl): ?object
    {
        $slug = trim((string) parse_url($postedUrl, PHP_URL_PATH), '/');
        $slug = str_starts_with($slug, 'blog/') ? substr($slug, 5) : $slug;

        if ($slug === '') {
            return null;
        }

        return DB::table('blogs')
            ->where('slug', $slug)
            ->where('isActive', 1)
            ->where('isDelete', 0)
            ->first();
    }

    /**
     * Reuses the image already generated for this article. Preferring a square
     * keeps Instagram happy; the article's own hero is the fallback, and a blog
     * with no image at all still goes out text-only on Facebook and X.
     */
    protected function mediaFor(int $planId, object $blog): ?int
    {
        $asset = DB::table('media_assets')
            ->where('content_plan_id', $planId)
            ->where('kind', 'image')
            ->orderByRaw("preset = 'instagram_square' DESC")
            ->orderByDesc('id')
            ->first();

        if ($asset) {
            return $asset->id;
        }

        $raw = trim((string) $blog->blogImage);
        $candidates = [
            base_path($raw),
            public_path($raw),
            public_path('storage/' . ltrim(preg_replace('#^(public/)?storage/#', '', $raw), '/')),
            storage_path('app/public/' . ltrim(preg_replace('#^(public/)?storage/#', '', $raw), '/')),
        ];

        $resolvedPath = null;
        $relativeAssetPath = null;
        foreach ($candidates as $candidate) {
            if ($raw !== '' && is_file($candidate)) {
                $resolvedPath = $candidate;
                $relativeAssetPath = 'public/storage/' . ltrim(preg_replace('#^(public/)?storage/#', '', $raw), '/');
                break;
            }
        }

        // Fallback to authentic temple emblem brand asset if article has no image
        if (! $resolvedPath) {
            $defaultLogo = public_path('storage/images/onlinepuja-logo-web.png');
            if (is_file($defaultLogo)) {
                $resolvedPath = $defaultLogo;
                $relativeAssetPath = 'public/storage/images/onlinepuja-logo-web.png';
            } else {
                return null;
            }
        }

        // The hero exists on disk but was never registered as a media asset
        // (older posts predate the pipeline), so adopt it rather than paying
        // to regenerate an image we already have.
        return DB::table('media_assets')->insertGetId([
            'kind' => 'image',
            'provider' => 'blog',
            'preset' => 'og',
            'prompt' => 'Hero image adopted from blog #'.$blog->id,
            'prompt_hash' => hash('sha256', 'blog-hero:'.$blog->id),
            'path' => $relativeAssetPath,
            'mime' => $this->mimeFor($resolvedPath),
            'content_plan_id' => $planId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function mimeFor(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }

    /**
     * Integrates high-intent Dual-App download CTAs and direct article link
     * into the social caption, customized per platform.
     */
    protected function buildCaptionWithCta(string $baseCaption, string $platform, string $blogUrl, string $lang = 'en'): string
    {
        $isHindi = ($lang === 'hi');

        $customerAppUrl = 'https://onlinepuja.live/downloads/onlinepuja-customer-latest.apk';
        $astrologerAppUrl = 'https://onlinepuja.live/downloads/onlinepuja-astrologer-latest.apk';

        if ($platform === 'x') {
            // Twitter 280 character limit
            $prefix = \Illuminate\Support\Str::limit(trim($baseCaption), 140);
            return $prefix . "\n\n📖 " . $blogUrl . "\n📲 " . ($isHindi ? 'ऑनलाइन पूजा ऐप डाउनलोड करें' : 'Download OnlinePuja App') . ": " . $customerAppUrl;
        }

        // Facebook, Instagram, YouTube Shorts, Pinterest
        if ($isHindi) {
            $cta = "\n\n📖 पूरा लेख पढ़ें: " . $blogUrl . "\n"
                . "━━━━━━━━━━━━━━\n"
                . "📲 ऑनलाइन पूजा ऐप डाउनलोड करें (घर बैठे पूजा और लाइव दर्शन):\n"
                . "👉 " . $customerAppUrl . "\n\n"
                . "⭐ पंडित और ज्योतिषी हमारे साथ जुड़ें (पार्टनर ऐप):\n"
                . "👉 " . $astrologerAppUrl;
        } else {
            $cta = "\n\n📖 Read Full Article: " . $blogUrl . "\n"
                . "━━━━━━━━━━━━━━\n"
                . "📲 Download Devotee App (Book Verified Pujas & Live Darshan):\n"
                . "👉 " . $customerAppUrl . "\n\n"
                . "⭐ Join as Astrologer / Pandit (Partner App):\n"
                . "👉 " . $astrologerAppUrl;
        }

        return trim($baseCaption) . $cta;
    }
}
