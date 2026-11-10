<?php

namespace App\Media;

use App\AI\ProviderManager;
use App\Helpers\StorageHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Generates social/blog imagery through the AI image chain, stores it on
 * whichever storage provider the panel is configured for, and records it in
 * media_assets so the same prompt is never paid for twice.
 */
class ImageStudio
{
    /**
     * Canvas sizes per placement. Getting these wrong is the single most
     * common reason a generated asset is unusable on the platform.
     */
    public const PRESETS = [
        'instagram_square' => ['width' => 1080, 'height' => 1080, 'label' => 'Instagram feed (1:1)'],
        'instagram_portrait' => ['width' => 1080, 'height' => 1350, 'label' => 'Instagram feed (4:5)'],
        'reel' => ['width' => 1080, 'height' => 1920, 'label' => 'Reel / Story / Short (9:16)'],
        'pin' => ['width' => 1000, 'height' => 1500, 'label' => 'Pinterest pin (2:3)'],
        'youtube_thumb' => ['width' => 1280, 'height' => 720, 'label' => 'YouTube thumbnail (16:9)'],
        'og' => ['width' => 1200, 'height' => 630, 'label' => 'Blog / OpenGraph (1.91:1)'],
    ];

    /** Keeps generated art consistent with the brand instead of random stock-AI. */
    protected const STYLE_ANCHOR =
        'Devotional Indian sacred art, warm marigold and saffron palette, soft temple lighting, '.
        'brass diya glow, intricate traditional motifs, reverent and serene, photographic depth, '.
        'no text, no watermark, no distorted hands or faces.';

    public function __construct(protected ProviderManager $ai)
    {
    }

    /**
     * @param  array{preset?:string,width?:int,height?:int,style?:string,image_model?:string,
     *               content_plan_id?:int,reuse?:bool,seed?:int}  $opts
     * @return array{ok:bool,asset_id:?int,path:?string,url:?string,provider:?string,reused:bool,error:string}
     */
    public function generate(string $prompt, array $opts = []): array
    {
        $prompt = trim($prompt);

        if ($prompt === '') {
            return $this->fail('Prompt is empty.');
        }

        $presetKey = $opts['preset'] ?? 'og';
        $preset = self::PRESETS[$presetKey] ?? self::PRESETS['og'];

        $width = (int) ($opts['width'] ?? $preset['width']);
        $height = (int) ($opts['height'] ?? $preset['height']);

        $style = $opts['style'] ?? self::STYLE_ANCHOR;
        $fullPrompt = $prompt.' — '.$style;

        $hash = hash('sha256', $fullPrompt.'|'.$width.'x'.$height);

        // Reuse an identical earlier render unless explicitly told not to.
        if (($opts['reuse'] ?? true) === true) {
            $existing = DB::table('media_assets')
                ->where('prompt_hash', $hash)
                ->where('kind', 'image')
                ->orderByDesc('id')
                ->first();

            if ($existing) {
                return [
                    'ok' => true,
                    'asset_id' => (int) $existing->id,
                    'path' => $existing->path,
                    'url' => $this->publicUrl($existing->path),
                    'provider' => $existing->provider,
                    'reused' => true,
                    'error' => '',
                ];
            }
        }

        $res = $this->ai->image($fullPrompt, [
            'width' => $width,
            'height' => $height,
            'image_model' => $opts['image_model'] ?? null,
            'seed' => $opts['seed'] ?? null,
        ]);

        if (empty($res['ok'])) {
            return $this->fail($res['error'] ?? 'Image generation failed.');
        }

        // Providers do not always honour the requested size — Pollinations
        // caps it. Record what actually came back, or a later video/crop step
        // works from dimensions the file does not have.
        $actual = @getimagesizefromstring($res['binary']);
        $realWidth = $actual[0] ?? $width;
        $realHeight = $actual[1] ?? $height;

        $ext = $this->extensionFor($res['mime'] ?? 'image/png');
        $filename = 'brain_'.Str::random(18).'_'.$realWidth.'x'.$realHeight.'.'.$ext;

        try {
            $path = StorageHelper::uploadToActiveStorage($res['binary'], $filename, 'brain_media');
        } catch (\Throwable $e) {
            return $this->fail('Generated but could not be stored: '.$e->getMessage());
        }

        if (! $path) {
            return $this->fail('Storage returned no path for the generated image.');
        }

        $id = DB::table('media_assets')->insertGetId([
            'kind' => 'image',
            'provider' => $res['provider'] ?? 'unknown',
            'model' => $res['model'] ?? null,
            'prompt' => $fullPrompt,
            'prompt_hash' => $hash,
            'preset' => $presetKey,
            'path' => $path,
            'mime' => $res['mime'] ?? 'image/png',
            'width' => $realWidth,
            'height' => $realHeight,
            'bytes' => strlen((string) $res['binary']),
            'content_plan_id' => $opts['content_plan_id'] ?? null,
            'meta' => json_encode([
                'credit' => $res['credit'] ?? null,
                'subject' => $prompt,
                'requested' => ['width' => $width, 'height' => $height],
                'size_honoured' => ($realWidth === $width && $realHeight === $height),
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'ok' => true,
            'asset_id' => (int) $id,
            'path' => $path,
            'url' => $this->publicUrl($path),
            'provider' => $res['provider'] ?? null,
            'reused' => false,
            'width' => $realWidth,
            'height' => $realHeight,
            'requested' => $width.'x'.$height,
            'error' => '',
        ];
    }

    /** StorageHelper returns an absolute URL externally, a relative path locally. */
    public function publicUrl(string $path): string
    {
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return rtrim((string) config('app.url'), '/').'/'.ltrim(Str::after($path, 'public/'), '/');
    }

    protected function extensionFor(string $mime): string
    {
        return match (strtolower($mime)) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'png',
        };
    }

    protected function fail(string $error): array
    {
        return [
            'ok' => false, 'asset_id' => null, 'path' => null, 'url' => null,
            'provider' => null, 'reused' => false, 'error' => $error,
        ];
    }
}
