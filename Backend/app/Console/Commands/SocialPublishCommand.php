<?php

namespace App\Console\Commands;

use App\Brain\Vault;
use App\Social\PublisherManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocialPublishCommand extends Command
{
    protected $signature = 'social:publish {--limit=15 : Max queued posts to attempt this run}';

    protected $description = 'Publish due social_posts through connected accounts (Meta, Instagram, etc.) and dispatch scheduled campaigns';

    public function handle(PublisherManager $manager): int
    {
        $limit = max(1, (int) $this->option('limit'));

        // 1. Publish due rows from social_posts table (direct Meta Graph, Instagram, etc.)
        $due = DB::table('social_posts')
            ->where('status', 'queued')
            ->where('attempts', '<', PublisherManager::MAX_ATTEMPTS)
            ->where(function ($q) {
                $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now());
            })
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();

        $published = 0;
        $failed = 0;

        foreach ($due as $post) {
            // Claim it first so an overlapping run cannot pick it up
            $claimed = DB::table('social_posts')->where('id', $post->id)->where('status', 'queued')
                ->update(['status' => 'publishing', 'updated_at' => now()]);

            if (! $claimed) {
                continue;
            }

            $result = $manager->publish($post);

            if ($result['ok']) {
                $published++;
                $this->line("• #{$post->id} [{$post->platform}] <info>published</info> — " . ($result['permalink'] ?? 'OK'));
            } else {
                $failed++;
                $this->line("• #{$post->id} [{$post->platform}] <comment>failed</comment>: {$result['error']}");
            }
        }

        // 2. Dispatch external webhook for channel='social' plans if configured
        $webhookUrl = Vault::get('social_webhook_url') ?: env('SOCIAL_DISPATCH_WEBHOOK');

        if (!empty($webhookUrl) && !str_contains($webhookUrl, 'dummy-webhook')) {
            $webhookPlans = DB::table('content_plans')
                ->where('channel', 'social')
                ->where('status', 'approved')
                ->where(function ($q) {
                    $q->whereNull('due_at')->orWhere('due_at', '<=', now());
                })
                ->orderBy('due_at', 'ASC')
                ->limit(5)
                ->get();

            foreach ($webhookPlans as $plan) {
                $payload = json_decode((string) $plan->payload, true) ?: [];
                $text = $payload['text'] ?? $plan->title;
                $hashtags = $payload['hashtags'] ?? '';
                $channels = $payload['channels'] ?? ['whatsapp', 'facebook', 'instagram', 'x'];
                $imageUrl = $payload['image_url'] ?? null;
                $videoUrl = $payload['video_url'] ?? null;
                $mediaType = !empty($videoUrl) ? 'video' : (!empty($imageUrl) ? 'image' : 'text');
                $link = $payload['link'] ?? 'https://onlinepuja.live/puja/category/list';

                $webhookSuccess = false;
                try {
                    $response = Http::withoutVerifying()->timeout(20)->post($webhookUrl, [
                        'id'          => $plan->id,
                        'title'       => $plan->title,
                        'text'        => $text,
                        'hashtags'    => $hashtags,
                        'channels'    => $channels,
                        'media_type'  => $mediaType,
                        'image_url'   => $imageUrl,
                        'video_url'   => $videoUrl,
                        'link'        => $link,
                        'source'      => 'OnlinePuja Varanasi Growth Engine',
                        'timestamp'   => now()->toIso8601String(),
                    ]);
                    $webhookSuccess = $response->successful();
                } catch (\Throwable $e) {
                    Log::warning("Social webhook dispatch error for plan #{$plan->id}: " . $e->getMessage());
                }

                if ($webhookSuccess) {
                    DB::table('content_plans')->where('id', $plan->id)->update([
                        'status'     => 'published',
                        'posted_url' => 'social://' . implode(',', $channels),
                        'updated_at' => now(),
                    ]);
                    $published++;
                }
            }
        }

        $this->info("Social publish completed: {$published} published, {$failed} failed.");

        \App\Brain\RunLog::recordUnlessNoop(
            'social:publish', 'social',
            $failed > 0 ? ($published > 0 ? 'partial' : 'fail') : 'ok',
            "Published {$published} social post(s)" . ($failed ? ", {$failed} failed" : ''),
            ['published' => $published, 'failed' => $failed],
            $published, $failed
        );

        return self::SUCCESS;
    }
}
