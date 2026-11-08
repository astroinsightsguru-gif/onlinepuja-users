<?php

namespace App\Console\Commands;

use App\Brain\RunLog;
use App\Services\FestivalCampaignEngine;
use Illuminate\Console\Command;

class FestivalCampaignRadarCommand extends Command
{
    protected $signature = 'festivals:campaign-radar
        {festival? : Target a specific festival name (e.g. "Dev Deepawali")}
        {--horizon=60 : Horizon in days to scan for upcoming festivals}
        {--festival= : Target a specific festival name (e.g. "Dev Deepawali")}
        {--channels=whatsapp,email,push : Comma-separated channels}
        {--dispatch : Actually send out messages (default: preview / dry-run)}
        {--generate-video : Synthesize genuine 1080x1920 MP4 video reel via FFmpeg}
        {--to= : Test recipient mobile number or email}';

    protected $description = 'Scan upcoming Hindu festivals and orchestrate multi-channel campaigns (WhatsApp, Brevo Email, Push)';

    public function handle(FestivalCampaignEngine $engine): int
    {
        $horizon = (int) $this->option('horizon');
        $target = $this->argument('festival') ?: $this->option('festival');
        $dispatch = (bool) $this->option('dispatch');
        $genVideo = (bool) $this->option('generate-video');
        $to = $this->option('to');
        $channels = array_filter(array_map('trim', explode(',', (string) $this->option('channels'))));

        $this->info("=== Hindu Festival Campaign Radar (Horizon: {$horizon} days) ===");

        $festivals = $engine->getUpcomingFestivals($horizon);

        if (empty($festivals)) {
            $this->warn("No upcoming festivals detected within {$horizon} days.");
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Festival Name', 'Date', 'Days Left', 'Milestone', 'Linked Puja', 'Status'],
            array_map(function ($f) {
                return [
                    $f['id'],
                    $f['name'],
                    $f['formatted_date'],
                    $f['days_left'] . ' days',
                    strtoupper($f['milestone']),
                    $f['puja']['title'] ?? '— (None linked)',
                    $f['puja']['status'] ?? 'N/A',
                ];
            }, $festivals)
        );

        // Filter if target festival specified
        $selected = $festivals;
        if ($target) {
            $selected = array_values(array_filter($festivals, function ($f) use ($target) {
                return str_contains(strtolower($f['name']), strtolower($target));
            }));

            if (empty($selected)) {
                $this->error("Festival matching '{$target}' not found in the upcoming {$horizon} days list.");
                return self::FAILURE;
            }
        }

        foreach ($selected as $fest) {
            $this->line('');
            $this->info(">>> Orchestrating Campaign Package for: [{$fest['name']}] ({$fest['formatted_date']})");

            $package = $engine->buildCampaignContent($fest['name']);

            $this->line("• WhatsApp Headline: " . strtok($package['whatsapp_copy'], "\n"));
            $this->line("• Brevo Email Subject: " . $package['email_subject']);
            $this->line("• Push Notification: " . $package['push_notification']['title']);
            $this->line("• Booking Destination: " . $package['booking_url']);

            $videoUrl = null;
            if ($genVideo) {
                $this->line("• Generating 1080x1920 MP4 Video Reel via native FFmpeg engine...");
                $videoRes = $engine->generateFestivalVideoReel($fest['name']);
                $videoUrl = $videoRes['video_url'] ?? null;
                if ($videoUrl) {
                    $this->info("  ✓ Video synthesized successfully: {$videoUrl}");
                } else {
                    $this->warn("  ! Video synthesis failed or fell back: " . ($videoRes['error'] ?? 'check logs'));
                }
            }

            if (!$dispatch) {
                $this->comment("  [DRY-RUN] Pass --dispatch to broadcast across " . implode(', ', $channels));
                continue;
            }

            $this->warn("  [DISPATCHING] Broadcasting live campaign across " . implode(', ', $channels) . "...");

            $options = [
                'dry_run'   => false,
                'video_url' => $videoUrl,
            ];

            if ($to) {
                if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
                    $options['email_recipients'] = [['email' => $to, 'name' => 'Devotee']];
                } else {
                    $options['whatsapp_recipients'] = [$to];
                }
            }

            $result = $engine->dispatchCampaign($fest['name'], $channels, $options);

            $this->info("  ✓ Dispatch completed for {$fest['name']}:");
            if (isset($result['channels']['whatsapp'])) {
                $this->line("    - WhatsApp: {$result['channels']['whatsapp']['sent']} / {$result['channels']['whatsapp']['attempted']} sent");
            }
            if (isset($result['channels']['email'])) {
                $this->line("    - Brevo Email: {$result['channels']['email']['sent']} / {$result['channels']['email']['attempted']} sent");
            }
            if (isset($result['channels']['push'])) {
                $this->line("    - Push: " . json_encode($result['channels']['push']));
            }
        }

        return self::SUCCESS;
    }
}
