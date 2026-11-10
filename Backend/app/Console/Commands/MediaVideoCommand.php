<?php

namespace App\Console\Commands;

use App\Media\VideoStudio;
use Illuminate\Console\Command;

class MediaVideoCommand extends Command
{
    protected $signature = 'media:video
                            {prompt : What the video should show}
                            {--preset=reel : reel|short|square|landscape}
                            {--seconds= : Override clip length in seconds}
                            {--model= : Override the video model (e.g. sora-2, seedance-1-pro, veo-3)}
                            {--image= : Image URL to animate (image-to-video)}
                            {--plan= : content_plans id to attach the asset to}
                            {--fresh : Ignore the cache and regenerate}';

    protected $description = 'Generate one video through the AI video chain (OmniRoute) and store it';

    public function handle(VideoStudio $studio): int
    {
        $preset = $this->option('preset');

        if (! isset(VideoStudio::PRESETS[$preset])) {
            $this->error("Unknown preset '{$preset}'. Available: ".implode(', ', array_keys(VideoStudio::PRESETS)));

            return self::FAILURE;
        }

        $this->line("Generating <info>{$preset}</info> (".VideoStudio::PRESETS[$preset]['label'].')… this can take a few minutes.');

        $res = $studio->generate($this->argument('prompt'), [
            'preset' => $preset,
            'seconds' => $this->option('seconds') ? (int) $this->option('seconds') : null,
            'model' => $this->option('model') ?: null,
            'image_url' => $this->option('image') ?: null,
            'content_plan_id' => $this->option('plan') ? (int) $this->option('plan') : null,
            'reuse' => ! $this->option('fresh'),
        ]);

        if (! $res['ok']) {
            $this->error($res['error']);

            return self::FAILURE;
        }

        $this->info(($res['reused'] ? 'Reused existing asset' : 'Generated via '.$res['provider'].' ('.$res['model'].')')." — id {$res['asset_id']}");
        $this->line('  stored: '.$res['path']);
        $this->line('  url:    '.$res['url']);

        return self::SUCCESS;
    }
}
