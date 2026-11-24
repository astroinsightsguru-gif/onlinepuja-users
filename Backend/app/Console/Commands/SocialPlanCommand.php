<?php

namespace App\Console\Commands;

use App\Media\ImageStudio;
use App\Social\PublisherManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fans an approved content_plans row (channel=social — festival wishes,
 * campaign posts) out into one social_posts row per connected account,
 * generating one shared devotional image for the batch so nothing goes
 * out as bare text.
 */
class SocialPlanCommand extends Command
{
    protected $signature = 'social:plan {--limit=10 : Max content_plans rows to fan out this run}';

    protected $description = 'Turn approved social content plans into per-platform queued posts';

    public function handle(PublisherManager $manager, ImageStudio $images, \App\Social\SocialCopywriter $copywriter): int
    {
        // Business accounts only — astrologer-owned accounts are driven by
        // social:plan-astrologers, not the business content_plans queue.
        $accounts = $manager->connectedAccounts(businessOnly: true);

        if ($accounts->isEmpty()) {
            $this->warn('No social accounts are connected yet — nothing to plan against. Connect one from Growth & AI OS → Social.');

            return self::SUCCESS;
        }

        $plans = DB::table('content_plans')
            ->where('channel', 'social')
            ->where('status', 'approved')
            ->orderBy('due_at')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($plans->isEmpty()) {
            $this->info('No approved social content plans waiting.');

            return self::SUCCESS;
        }

        $queued = 0;
        $skippedExisting = 0;
        $spread = []; // per-platform counter so posts land on different peak slots

        foreach ($plans as $plan) {
            $already = DB::table('social_posts')->where('content_plan_id', $plan->id)->exists();

            if ($already) {
                $skippedExisting++;

                continue;
            }

            $payload = json_decode((string) $plan->payload, true) ?: [];
            $text = trim((string) ($payload['text'] ?? $plan->title));
            $hashtags = $payload['hashtags'] ?? null;

            if ($text === '') {
                $this->warn("Plan #{$plan->id} has no text — skipped.");

                continue;
            }

            $mediaAssetId = null;
            if (!empty($payload['video_url']) || !empty($payload['image_url'])) {
                $existingMediaUrl = $payload['video_url'] ?? $payload['image_url'];
                $isVideo = !empty($payload['video_url']);
                $asset = DB::table('media_assets')->where('content_plan_id', $plan->id)->first();
                if (!$asset) {
                    $mediaAssetId = DB::table('media_assets')->insertGetId([
                        'content_plan_id' => $plan->id,
                        'kind' => $isVideo ? 'video' : 'image',
                        'path' => $existingMediaUrl,
                        'mime' => $isVideo ? 'video/mp4' : 'image/jpeg',
                        'preset' => $isVideo ? 'vertical_reel' : 'instagram_square',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $mediaAssetId = $asset->id;
                }
            } else {
                // One shared image for every platform in this batch — cheaper
                // than regenerating per platform, and prompt-hash reuse means a
                // rerun of this command costs nothing extra either.
                $imageResult = $images->generate($text, [
                    'preset' => 'instagram_square',
                    'content_plan_id' => $plan->id,
                ]);
                $mediaAssetId = $imageResult['ok'] ? $imageResult['asset_id'] : null;

                if (! $imageResult['ok']) {
                    $this->warn("Plan #{$plan->id}: image generation failed ({$imageResult['error']}) — posting text-only where the platform allows it.");
                }
            }

            $targetPlatforms = $payload['channels'] ?? null;
            $due = $plan->due_at ? \Carbon\Carbon::parse($plan->due_at) : now();

            // Platform-native copy for this idea — Instagram, X, Facebook,
            // Pinterest and YouTube each read very differently.
            $variants = $copywriter->variants($text, (string) $plan->title);

            foreach ($accounts as $account) {
                // Skip YouTube only when there is no video attached
                if ($account->platform === 'youtube' && empty($payload['video_url'])) {
                    continue;
                }

                if ($targetPlatforms && ! in_array($account->platform, $targetPlatforms, true)) {
                    continue;
                }

                if (! $mediaAssetId && $account->platform !== 'facebook' && $account->platform !== 'x') {
                    continue; // Instagram/Pinterest require media
                }

                // Per-platform copy + hashtags, falling back to the plain text
                // for any platform the copywriter didn't cover.
                $variant = $variants[$account->platform] ?? ['caption' => $text, 'hashtags' => $hashtags];

                // Snap to the platform's next peak-engagement slot (IST), and
                // spread successive posts on the same platform apart.
                $idx = $spread[$account->platform] ?? 0;
                $spread[$account->platform] = $idx + 1;
                $scheduledAt = \App\Social\SocialTiming::nextSlot($account->platform, $due, $idx);

                DB::table('social_posts')->insert([
                    'content_plan_id' => $plan->id,
                    'social_account_id' => $account->id,
                    'platform' => $account->platform,
                    'caption' => $variant['caption'] ?: $text,
                    'hashtags' => $variant['hashtags'] ?: $hashtags,
                    'media_asset_id' => $mediaAssetId,
                    'scheduled_at' => $scheduledAt,
                    'status' => 'queued',
                    // Dimensions the Learner groups engagement by.
                    'meta' => json_encode(['kind' => 'festival', 'plan_id' => $plan->id]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $queued++;
            }
        }

        $this->info("Queued {$queued} platform post(s) from ".($plans->count() - $skippedExisting)." plan(s)".
            ($skippedExisting ? " ({$skippedExisting} already planned)" : '').'.');

        \App\Brain\RunLog::recordUnlessNoop(
            'social:plan', 'social', 'ok',
            "Queued {$queued} platform post(s) from ".($plans->count() - $skippedExisting).' plan(s)',
            ['queued' => $queued, 'plans' => $plans->count(), 'skipped_existing' => $skippedExisting],
            $queued, 0
        );

        return self::SUCCESS;
    }
}
