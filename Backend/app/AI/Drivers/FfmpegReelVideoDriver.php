<?php

namespace App\AI\Drivers;

use App\AI\Contracts\VideoProvider;
use App\Brain\Vault;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Native FFmpeg 9:16 Broadcast-Ready Reel Synthesizer — High Definition (4-6 Mbps H.264).
 * Uses native FFmpeg on the VPS with Lanczos antialiasing, Gemini high-res visual frames,
 * Devanagari Sanskrit typography, and sacred bell chimes.
 */
class FfmpegReelVideoDriver implements VideoProvider
{
    public function slug(): string
    {
        return 'ffmpeg_reel';
    }

    public function label(): string
    {
        return 'Native FFmpeg 9:16 Sacred Reel Synthesizer (HQ Free)';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function create(string $prompt, array $args = []): array
    {
        $fail = fn (string $e) => [
            'ok'          => false,
            'binary'      => '',
            'url'         => '',
            'mime'        => '',
            'model'       => 'ffmpeg-reel',
            'duration_ms' => 0,
            'credit'      => '',
            'error'       => $e,
        ];

        $duration = (int) ($args['seconds'] ?? 6);
        if ($duration < 3) $duration = 3;
        if ($duration > 30) $duration = 30;

        $width = (int) ($args['width'] ?? 1080);
        $height = (int) ($args['height'] ?? 1920);

        $outDir = public_path('storage/reels');
        if (!is_dir($outDir)) {
            @mkdir($outDir, 0775, true);
        }

        $id = 'reel_' . Str::random(12);
        $tmpImg = sys_get_temp_dir() . '/' . $id . '.jpg';
        $outMp4 = $outDir . '/' . $id . '.mp4';
        $publicUrl = asset('storage/reels/' . $id . '.mp4');

        try {
            // 1. Resolve or Generate High-Definition Visual Frame
            $imageUrl = $args['image_url'] ?? null;

            if ($imageUrl && str_starts_with($imageUrl, 'data:image')) {
                $b64 = substr($imageUrl, strpos($imageUrl, ',') + 1);
                file_put_contents($tmpImg, base64_decode($b64));
            } elseif ($imageUrl && (str_contains($imageUrl, 'storage/') || str_contains($imageUrl, 'onlinepuja.live'))) {
                $pos = strpos($imageUrl, 'storage/');
                $rel = ($pos !== false) ? substr($imageUrl, $pos) : '';
                if ($rel && file_exists(public_path($rel))) {
                    copy(public_path($rel), $tmpImg);
                }
            } elseif ($imageUrl && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                try {
                    $dl = Http::withoutVerifying()->timeout(30)->get($imageUrl);
                    if ($dl->ok() && strlen($dl->body()) > 500) {
                        file_put_contents($tmpImg, $dl->body());
                    }
                } catch (\Throwable $e) {}
            } elseif ($imageUrl && file_exists($imageUrl)) {
                copy($imageUrl, $tmpImg);
            }

            // If no valid image frame, generate one via OmniRoute Gemini (crystal clear 1024x1024)
            if (!file_exists($tmpImg) || filesize($tmpImg) < 500) {
                try {
                    $omniKey = Vault::get('omniroute_key');
                    $omniUrl = Vault::get('omniroute_url', 'https://ai.vmstudio.digital');
                    
                    if (!empty($omniKey)) {
                        $cleanPrompt = preg_replace('/—.*/', '', $prompt);
                        $res = Http::timeout(40)->withToken($omniKey)->post(rtrim($omniUrl, '/') . '/v1/images/generations', [
                            'model'  => 'gemini-3.1-flash-image',
                            'prompt' => $cleanPrompt . ' — Cinematic devotional Indian sacred Vedic scene, golden diya lighting, 8k photographic depth',
                            'n'      => 1,
                            'size'   => '1024x1024',
                        ]);

                        if ($res->ok()) {
                            $b64 = $res->json('data.0.b64_json');
                            if ($b64) {
                                file_put_contents($tmpImg, base64_decode($b64));
                            }
                        }
                    }
                } catch (\Throwable $e) {}
            }

            // Fallback: Pick highest resolution existing asset in database
            if (!file_exists($tmpImg) || filesize($tmpImg) < 500) {
                $existing = DB::table('media_assets')
                    ->where('kind', 'image')
                    ->whereNotNull('path')
                    ->orderByDesc('bytes')
                    ->first();

                if ($existing && !empty($existing->path)) {
                    $clean = Str::after($existing->path, 'public/');
                    $localPath = public_path($clean);
                    if (file_exists($localPath) && filesize($localPath) > 500) {
                        copy($localPath, $tmpImg);
                    }
                }
            }

            if (!file_exists($tmpImg) || filesize($tmpImg) < 500) {
                return $fail('Could not acquire valid source visual frame for reel synthesis.');
            }

            // 2. Audio track resolution
            $audioFile = public_path('storage/audio/vedic_bell_chime.aac');
            $hasAudio = file_exists($audioFile);
            $audioInput = $hasAudio
                ? sprintf('-stream_loop -1 -i %s', escapeshellarg($audioFile))
                : '-f lavfi -i anullsrc=channel_layout=stereo:sample_rate=44100';

            // 3. Devanagari Sanskrit Fonts
            $fontDeva = '/usr/share/fonts/truetype/noto/NotoSerifDevanagari-Bold.ttf';
            if (!file_exists($fontDeva)) {
                $fontDeva = '/usr/share/fonts/truetype/lohit-devanagari/Lohit-Devanagari.ttf';
            }
            if (!file_exists($fontDeva)) {
                $fontDeva = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
            }

            // 4. Branding & Titles
            $brandCfg = Vault::videoBranding();
            $title = addslashes($args['title'] ?? $brandCfg['video_brand_title'] ?? 'OnlinePuja.live');
            $subtitle = addslashes($args['subtitle'] ?? $brandCfg['video_brand_tagline'] ?? 'वाराणसी से सीधा लाइव संकल्प एवं दर्शन');
            $cta = addslashes($args['cta'] ?? ($brandCfg['video_brand_phone'] . ' | ' . $brandCfg['video_brand_cta']));

            // 5. Logo safe-zone overlay resolution
            $logoPath = public_path('assets-pg/imgs/logo-for-site.png');
            $customLogo = $brandCfg['video_logo_path'] ?? '';
            if (!empty($customLogo) && file_exists(public_path($customLogo))) {
                $logoPath = public_path($customLogo);
            }
            $logoEnabled = ($brandCfg['video_logo_enabled'] ?? true) && file_exists($logoPath);

            $fps = 30;
            $totalFrames = $duration * $fps;

            // Broadcast-quality Lanczos scaling + Antialiased Ken-Burns zoom + HarfBuzz shaped Devanagari typography
            if ($logoEnabled) {
                $filter = "[0:v]scale=2160:3840:force_original_aspect_ratio=increase:flags=lanczos,crop=2160:3840,zoompan=z='min(zoom+0.0008,1.14)':d={$totalFrames}:x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)':s={$width}x{$height}:fps={$fps}[bg];" .
                          "[1:v]scale=220:-1[logo];" .
                          "[bg][logo]overlay=50:85[vlog];" .
                          "[vlog]drawbox=y=ih-420:color=black@0.65:width=iw:height=420:t=fill," .
                          "drawbox=y=ih-422:color=0xFFD700@0.8:width=iw:height=3:t=fill," .
                          "drawtext=fontfile='{$fontDeva}':text='{$title}':text_shaping=1:fontcolor=0xFFD700:fontsize=48:x=(w-text_w)/2:y=h-340:shadowcolor=black@0.8:shadowx=2:shadowy=2," .
                          "drawtext=fontfile='{$fontDeva}':text='{$subtitle}':text_shaping=1:fontcolor=0xFFFFFF:fontsize=28:x=(w-text_w)/2:y=h-240," .
                          "drawtext=fontfile='{$fontDeva}':text='{$cta}':text_shaping=1:fontcolor=0x10B981:fontsize=28:x=(w-text_w)/2:y=h-140[vout]";

                $cmd = sprintf(
                    'ffmpeg -y -loop 1 -i %s -i %s %s -filter_complex %s -map "[vout]" -map 2:a -c:v libx264 -preset fast -crf 18 -b:v 5000k -maxrate 7000k -bufsize 10000k -c:a aac -b:a 192k -shortest -t %d -pix_fmt yuv420p -movflags +faststart %s 2>&1',
                    escapeshellarg($tmpImg),
                    escapeshellarg($logoPath),
                    $audioInput,
                    escapeshellarg($filter),
                    $duration,
                    escapeshellarg($outMp4)
                );
            } else {
                $vf = "scale=2160:3840:force_original_aspect_ratio=increase:flags=lanczos,crop=2160:3840," .
                      "zoompan=z='min(zoom+0.0008,1.14)':d={$totalFrames}:x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)':s={$width}x{$height}:fps={$fps}," .
                      "drawbox=y=ih-420:color=black@0.65:width=iw:height=420:t=fill," .
                      "drawbox=y=ih-422:color=0xFFD700@0.8:width=iw:height=3:t=fill," .
                      "drawtext=fontfile='{$fontDeva}':text='{$title}':text_shaping=1:fontcolor=0xFFD700:fontsize=48:x=(w-text_w)/2:y=h-340:shadowcolor=black@0.8:shadowx=2:shadowy=2," .
                      "drawtext=fontfile='{$fontDeva}':text='{$subtitle}':text_shaping=1:fontcolor=0xFFFFFF:fontsize=28:x=(w-text_w)/2:y=h-240," .
                      "drawtext=fontfile='{$fontDeva}':text='{$cta}':text_shaping=1:fontcolor=0x10B981:fontsize=28:x=(w-text_w)/2:y=h-140";

                $cmd = sprintf(
                    'ffmpeg -y -loop 1 -i %s %s -vf %s -map 0:v -map 1:a -c:v libx264 -preset fast -crf 18 -b:v 5000k -maxrate 7000k -bufsize 10000k -c:a aac -b:a 192k -shortest -t %d -pix_fmt yuv420p -movflags +faststart %s 2>&1',
                    escapeshellarg($tmpImg),
                    $audioInput,
                    escapeshellarg($vf),
                    $duration,
                    escapeshellarg($outMp4)
                );
            }

            exec($cmd, $output, $ret);
            @unlink($tmpImg);

            if ($ret === 0 && file_exists($outMp4) && filesize($outMp4) > 1000) {
                return [
                    'ok'          => true,
                    'binary'      => '',
                    'url'         => 'storage/reels/' . $id . '.mp4',
                    'mime'        => 'video/mp4',
                    'model'       => 'ffmpeg-h264-1080x1920-hq',
                    'duration_ms' => $duration * 1000,
                    'credit'      => 'Synthesized via Native FFmpeg 9:16 Sacred Video Engine (HQ)',
                    'error'       => '',
                ];
            }

            Log::error('FFmpeg reel synthesis failed (' . $ret . '): ' . implode("\n", array_slice($output, -10)));
            return $fail('FFmpeg execution failed with exit code ' . $ret);
        } catch (\Throwable $e) {
            @unlink($tmpImg);
            return $fail('FFmpeg reel error: ' . $e->getMessage());
        }
    }
}
