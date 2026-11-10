<?php

namespace App\Console\Commands;

use App\Social\PublisherManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Pulls lightweight metrics for recently published posts into `outcomes`,
 * the only feedback loop that lets the growth engine ever learn which
 * platform/pillar/festival actually performs.
 */
class SocialCollectCommand extends Command
{
    protected $signature = 'social:collect {--days=14 : Only posts published within this many days}';

    protected $description = 'Pull reach/engagement metrics for published social posts into outcomes';

    public function handle(PublisherManager $manager): int
    {
        $posts = DB::table('social_posts')
            ->where('status', 'published')
            ->whereNotNull('remote_id')
            ->where('published_at', '>=', now()->subDays((int) $this->option('days')))
            ->get();

        if ($posts->isEmpty()) {
            $this->info('No published posts in range.');

            return self::SUCCESS;
        }

        $collected = 0;
        $failed = 0;
        $rows = 0;

        foreach ($posts as $post) {
            $publisher = $manager->publisher($post->platform);
            $account = $manager->account($post->social_account_id);

            if (! $publisher || ! $account) {
                continue;
            }

            $m = $publisher->metrics($account, $post->remote_id);

            // A publisher may return ok with a note: some metrics need scopes
            // this app has not been granted yet. Store what did come back
            // rather than discarding the post — a partial signal still teaches
            // the Learner more than an empty `outcomes` table.
            if ($m['error'] !== '') {
                $this->line("• #{$post->id} [{$post->platform}] <comment>{$m['error']}</comment>");
            }

            if (! $m['ok']) {
                $failed++;

                continue;
            }

            $now = now();

            foreach (['reach', 'likes', 'comments', 'shares'] as $metric) {
                if ($m[$metric] === null) {
                    continue;
                }

                // Attribute astrologer-owned posts to the astrologer so their
                // performance is queryable; business posts stay as social_post.
                $entityType = $post->astrologer_id ? 'astrologer' : 'social_post';
                $entityId = $post->astrologer_id ?: $post->id;

                DB::table('outcomes')->insert([
                    'channel' => $post->platform,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'metric' => $metric,
                    'value' => (int) $m[$metric],
                    'at' => $now,
                    'meta' => json_encode(['remote_id' => $post->remote_id, 'social_post_id' => $post->id]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $rows++;
            }

            $collected++;
        }

        $summary = "Collected metrics for {$collected} post(s), {$rows} metric row(s)"
            .($failed ? ", {$failed} unavailable" : '');

        $this->info($summary);

        \App\Brain\RunLog::recordUnlessNoop(
            'social:collect', 'social',
            $failed > 0 ? ($collected > 0 ? 'partial' : 'fail') : 'ok',
            $summary,
            ['posts' => $collected, 'rows' => $rows, 'unavailable' => $failed],
            $collected, $failed
        );

        return self::SUCCESS;
    }
}
