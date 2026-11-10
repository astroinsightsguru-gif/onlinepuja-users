<?php

namespace App\Console\Commands;

use App\Media\AstrologerCardComposer;
use App\Media\ImageStudio;
use App\Models\AstrologerModel\AstrologerSocialSetting;
use App\Social\AstrologerContentPlanner;
use App\Social\PublisherManager;
use App\Social\SocialCopywriter;
use App\Social\SocialTiming;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Drafts per-astrologer social posts. For every astrologer who has connected
 * at least one account and is under their weekly quota, it picks the next idea
 * (profile / service / astro-calendar), generates a branded card with their
 * face, writes captions in their voice, and queues one DRAFT per connected
 * platform. Drafts wait in the astrologer's panel for approval — only then do
 * they become 'queued' and get picked up by social:publish.
 */
class SocialPlanAstrologersCommand extends Command
{
    protected $signature = 'social:plan-astrologers
        {--limit=20 : Max astrologers to plan for this run}
        {--astrologer= : Only this astrologer id}';

    protected $description = 'Draft per-astrologer social posts from their profile, services and the astro calendar';

    public function handle(
        PublisherManager $manager,
        AstrologerContentPlanner $planner,
        ImageStudio $images,
        AstrologerCardComposer $cards,
        SocialCopywriter $copywriter,
    ): int {
        $astrologerIds = DB::table('social_accounts')
            ->where('status', 'connected')
            ->whereNotNull('astrologer_id')
            ->when($this->option('astrologer'), fn ($q) => $q->where('astrologer_id', (int) $this->option('astrologer')))
            ->distinct()
            ->pluck('astrologer_id');

        if ($astrologerIds->isEmpty()) {
            $this->info('No astrologers have connected a social account yet.');

            return self::SUCCESS;
        }

        $planned = 0;
        $drafts = 0;
        $errors = 0;
        $limit = (int) $this->option('limit');

        foreach ($astrologerIds as $astrologerId) {
            if ($planned >= $limit) {
                break;
            }

            $astrologer = DB::table('astrologers')->where('id', $astrologerId)->first();

            if (! $astrologer) {
                continue;
            }

            $setting = AstrologerSocialSetting::forAstrologer($astrologerId);

            if (! $setting->enabled) {
                continue;
            }

            $upcoming = DB::table('social_posts')
                ->where('astrologer_id', $astrologerId)
                ->whereIn('status', ['draft', 'queued'])
                ->where('scheduled_at', '>=', now())
                ->where('scheduled_at', '<=', now()->addDays(7))
                ->count();

            if ($upcoming >= $setting->weekly_quota) {
                continue;
            }

            $planned++;

            try {
                $made = $this->planOne($astrologerId, $astrologer, $manager, $planner, $images, $cards, $copywriter);
                $drafts += $made;
            } catch (\Throwable $e) {
                $errors++;
                $this->error("Astrologer #{$astrologerId}: {$e->getMessage()}");

                continue;
            }

            $setting->update(['last_planned_at' => now()]);
        }

        $this->info("Planned {$planned} astrologer(s) · {$drafts} draft post(s)".($errors ? " · {$errors} error(s)" : '').'.');

        \App\Brain\RunLog::recordUnlessNoop(
            'social:plan-astrologers', 'social',
            $errors > 0 ? ($drafts > 0 ? 'partial' : 'fail') : 'ok',
            "Drafted {$drafts} post(s) for {$planned} astrologer(s)",
            ['astrologers' => $planned, 'drafts' => $drafts, 'errors' => $errors],
            $drafts, $errors,
        );

        return self::SUCCESS;
    }

    protected function planOne(
        int $astrologerId,
        object $astrologer,
        PublisherManager $manager,
        AstrologerContentPlanner $planner,
        ImageStudio $images,
        AstrologerCardComposer $cards,
        SocialCopywriter $copywriter,
    ): int {
        $persona = $planner->personaAnchor($astrologerId);
        $idea = $planner->nextIdea($astrologerId);

        $bg = $images->generate($idea['text'].' — devotional social media artwork, no text', [
            'preset' => 'instagram_square',
        ]);

        $mediaAssetId = null;

        if ($bg['ok']) {
            $card = $cards->compose($bg['path'], $astrologer->profileImage ?? null, (string) $astrologer->name, $astrologerId);
            $mediaAssetId = $card['ok'] ? $card['asset_id'] : $bg['asset_id'];

            if (! $card['ok']) {
                $this->warn("Astrologer #{$astrologerId}: card compose failed ({$card['error']}) — using plain background.");
            }
        } else {
            $this->warn("Astrologer #{$astrologerId}: image generation failed ({$bg['error']}) — text-only where allowed.");
        }

        $variants = $copywriter->variants($idea['text'], '', $persona);
        $accounts = $manager->connectedAccounts(astrologerId: $astrologerId);

        $made = 0;
        $spread = [];

        foreach ($accounts as $account) {
            if ($account->status !== 'connected') {
                continue;
            }

            // YouTube needs a generated video — that pipeline is not live yet.
            if ($account->platform === 'youtube') {
                continue;
            }

            // Instagram cannot post without media.
            if (! $mediaAssetId && $account->platform !== 'facebook') {
                continue;
            }

            $variant = $variants[$account->platform] ?? ['caption' => $idea['text'], 'hashtags' => null];

            $idx = $spread[$account->platform] ?? 0;
            $spread[$account->platform] = $idx + 1;

            DB::table('social_posts')->insert([
                'content_plan_id' => null,
                'social_account_id' => $account->id,
                'astrologer_id' => $astrologerId,
                'platform' => $account->platform,
                'caption' => $variant['caption'] ?: $idea['text'],
                'hashtags' => $variant['hashtags'] ?: null,
                'media_asset_id' => $mediaAssetId,
                'scheduled_at' => SocialTiming::nextSlot($account->platform, now()->addDay(), $idx),
                'status' => 'draft',
                // Which idea source produced this is the whole basis for
                // learning what works for this particular astrologer.
                'meta' => json_encode(['kind' => 'astrologer', 'idea_source' => $idea['source']]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $made++;
        }

        return $made;
    }
}
