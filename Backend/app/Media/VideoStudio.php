<?php

namespace App\Media;

use App\AI\ProviderManager;
use App\Helpers\StorageHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Generates short-form video (Reels / Shorts / vertical social clips) through
 * the AI video chain or native FFmpeg reel synthesizer, stores the file on active
 * storage, and records it in media_assets so an identical prompt is never re-rendered.
 */
class VideoStudio
{
    /** Canvas + length per placement. 9:16 is the workhorse for Reels/Shorts. */
    public const PRESETS = [
        'reel'      => ['width' => 1080, 'height' => 1920, 'seconds' => 8,  'label' => 'Reel / Short (9:16)'],
        'short'     => ['width' => 1080, 'height' => 1920, 'seconds' => 15, 'label' => 'YouTube Short (9:16)'],
        'square'    => ['width' => 1080, 'height' => 1080, 'seconds' => 8,  'label' => 'Feed video (1:1)'],
        'landscape' => ['width' => 1920, 'height' => 1080, 'seconds' => 10, 'label' => 'YouTube / landscape (16:9)'],
    ];

    /** Keeps generated footage on-brand instead of generic stock AI. */
    protected const STYLE_ANCHOR =
        'Cinematic devotional Indian sacred scene, warm marigold and saffron tones, glowing brass diya '.
        'and temple lamps, drifting incense smoke, gentle camera motion, reverent and serene mood, '.
        'no on-screen text, no watermark, no distorted hands or faces.';

    public function __construct(protected ProviderManager $ai)
    {
    }

    /**
     * @param  array{preset?:string,width?:int,height?:int,seconds?:int,style?:string,
     *               model?:string,image_url?:string,content_plan_id?:int,reuse?:bool,
     *               title?:string,subtitle?:string}  $opts
     * @return array{ok:bool,asset_id:?int,path:?string,url:?string,provider:?string,model:?string,reused:bool,error:string}
     */
    public function generate(string $prompt, array $opts = []): array
    {
        $prompt = trim($prompt);

        if ($prompt === '') {
            return $this->fail('Prompt is empty.');
        }

        $presetKey = $opts['preset'] ?? 'reel';
        $preset = self::PRESETS[$presetKey] ?? self::PRESETS['reel'];

        $width = (int) ($opts['width'] ?? $preset['width']);
        $height = (int) ($opts['height'] ?? $preset['height']);
        $seconds = (int) ($opts['seconds'] ?? $preset['seconds']);

        $style = $opts['style'] ?? self::STYLE_ANCHOR;
        $fullPrompt = $prompt.' — '.$style;

        $hash = hash('sha256', $fullPrompt.'|'.$width.'x'.$height.'|'.$seconds.'s');

        if (($opts['reuse'] ?? true) === true) {
            $existing = DB::table('media_assets')
                ->where('prompt_hash', $hash)
                ->where('kind', 'video')
                ->orderByDesc('id')
                ->first();

            if ($existing) {
                return [
                    'ok' => true,
                    'asset_id' => (int) $existing->id,
                    'path' => $existing->path,
                    'url' => $this->publicUrl($existing->path),
                    'provider' => $existing->provider,
                    'model' => $existing->model,
                    'reused' => true,
                    'error' => '',
                ];
            }
        }

        $res = $this->ai->video($fullPrompt, [
            'width' => $width,
            'height' => $height,
            'seconds' => $seconds,
            'model' => $opts['model'] ?? null,
            'image_url' => $opts['image_url'] ?? null,
            'title' => $opts['title'] ?? null,
            'subtitle' => $opts['subtitle'] ?? null,
        ]);

        if (empty($res['ok'])) {
            return $this->fail($res['error'] ?? 'Video generation failed.');
        }

        $binary = (string) ($res['binary'] ?? '');

        // If the provider returned a local public path or URL
        if ($binary === '' && ! empty($res['url'])) {
            if (str_starts_with($res['url'], 'storage/') || str_starts_with($res['url'], '/storage/')) {
                // Already stored locally on disk
                $path = ltrim($res['url'], '/');
            } elseif (filter_var($res['url'], FILTER_VALIDATE_URL)) {
                try {
                    $dl = Http::timeout(180)->get($res['url']);
                    if ($dl->ok()) {
                        $binary = $dl->body();
                    }
                } catch (\Throwable $e) {
                    // keep URL as fallback
                }
            }
        }

        $ext = $this->extensionFor($res['mime'] ?? 'video/mp4');

        if ($binary !== '') {
            $filename = 'brain_'.Str::random(18).'_'.$width.'x'.$height.'.'.$ext;

            try {
                $path = StorageHelper::uploadToActiveStorage($binary, $filename, 'brain_media');
            } catch (\Throwable $e) {
                return $this->fail('Generated but could not be stored: '.$e->getMessage());
            }
        } elseif (empty($path) && ! empty($res['url'])) {
            $path = $res['url'];
        }

        if (empty($path)) {
            return $this->fail('Provider returned neither video bytes nor a valid path/URL.');
        }

        $id = DB::table('media_assets')->insertGetId([
            'kind' => 'video',
            'provider' => $res['provider'] ?? 'unknown',
            'model' => $res['model'] ?? null,
            'prompt' => $fullPrompt,
            'prompt_hash' => $hash,
            'preset' => $presetKey,
            'path' => $path,
            'mime' => $res['mime'] ?? 'video/mp4',
            'width' => $width,
            'height' => $height,
            'duration_ms' => (int) ($res['duration_ms'] ?? $seconds * 1000),
            'bytes' => strlen($binary),
            'content_plan_id' => $opts['content_plan_id'] ?? null,
            'meta' => json_encode([
                'credit' => $res['credit'] ?? null,
                'subject' => $prompt,
                'remote_only' => ($binary === ''),
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
            'model' => $res['model'] ?? null,
            'reused' => false,
            'error' => '',
        ];
    }

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
            'video/webm' => 'webm',
            'video/quicktime', 'video/mov' => 'mov',
            default => 'mp4',
        };
    }

    protected function fail(string $error): array
    {
        return [
            'ok' => false, 'asset_id' => null, 'path' => null, 'url' => null,
            'provider' => null, 'model' => null, 'reused' => false, 'error' => $error,
        ];
    }
}
