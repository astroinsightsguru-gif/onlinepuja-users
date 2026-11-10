<?php

namespace App\Console\Commands;

use App\Media\ImageStudio;
use Illuminate\Console\Command;

class MediaImageCommand extends Command
{
    protected $signature = 'media:image
                            {prompt : What the image should show}
                            {--preset=og : instagram_square|instagram_portrait|reel|pin|youtube_thumb|og}
                            {--model= : Override the image model}
                            {--plan= : content_plans id to attach the asset to}
                            {--fresh : Ignore the cache and regenerate}';

    protected $description = 'Generate one image through the AI image chain and store it';

    public function handle(ImageStudio $studio): int
    {
        $preset = $this->option('preset');

        if (! isset(ImageStudio::PRESETS[$preset])) {
            $this->error("Unknown preset '{$preset}'. Available: ".implode(', ', array_keys(ImageStudio::PRESETS)));

            return self::FAILURE;
        }

        $this->line("Generating <info>{$preset}</info> (".ImageStudio::PRESETS[$preset]['label'].')…');

        $res = $studio->generate($this->argument('prompt'), [
            'preset' => $preset,
            'image_model' => $this->option('model') ?: null,
            'content_plan_id' => $this->option('plan') ? (int) $this->option('plan') : null,
            'reuse' => ! $this->option('fresh'),
        ]);

        if (! $res['ok']) {
            $this->error($res['error']);

            return self::FAILURE;
        }

        $this->info(($res['reused'] ? 'Reused existing asset' : 'Generated via '.$res['provider'])." — id {$res['asset_id']}");
        $this->line('  stored: '.$res['path']);
        $this->line('  url:    '.$res['url']);

        if (isset($res['width'], $res['requested'])) {
            $actual = $res['width'].'x'.$res['height'];
            $this->line('  size:   '.$actual);

            if ($actual !== $res['requested']) {
                $this->warn("  Provider returned {$actual}, not the requested {$res['requested']} — ".
                            'crop or upscale before posting to a size-strict placement.');
            }
        }

        return self::SUCCESS;
    }
}
