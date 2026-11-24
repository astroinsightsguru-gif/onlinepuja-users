<?php

namespace App\Brain;

use Illuminate\Support\Facades\Crypt;

/**
 * Encrypted settings vault for the Brain kernel.
 * Values are AES-256-GCM encrypted (Laravel Crypt, APP_KEY-derived).
 * Any key may be overridden by an environment constant: BRAIN_<UPPER_KEY>.
 */
class Vault
{
    /** Runtime in-memory cache for all brain_settings keys */
    protected static ?array $allSettings = null;
    protected static array $decryptedCache = [];

    public static function preload(): void
    {
        if (self::$allSettings === null) {
            try {
                self::$allSettings = \DB::table('brain_settings')->pluck('value_enc', 'key')->all();
            } catch (\Throwable $e) {
                self::$allSettings = [];
            }
        }
    }

    public static function flushMemoryCache(): void
    {
        self::$allSettings = null;
        self::$decryptedCache = [];
    }

    public static function get(string $key, ?string $default = ''): string
    {
        $env = 'BRAIN_'.strtoupper(preg_replace('/[^a-z0-9]/i', '_', $key));

        if (($v = getenv($env)) !== false && $v !== '') {
            return $v;                                  // constant always wins
        }

        if (array_key_exists($key, self::$decryptedCache)) {
            return self::$decryptedCache[$key] ?? (string) $default;
        }

        if (self::$allSettings === null) {
            self::preload();
        }

        if (!array_key_exists($key, self::$allSettings) || self::$allSettings[$key] === null) {
            self::$decryptedCache[$key] = (string) $default;
            return (string) $default;
        }

        $val = (string) self::$allSettings[$key];

        if ($val === '') {
            self::$decryptedCache[$key] = (string) $default;
            return (string) $default;
        }

        // Fast payload check: Laravel encrypted strings are base64-encoded JSON {"iv":...,"value":...,"mac":...}
        if (str_starts_with($val, 'eyJ') || str_starts_with($val, '{')) {
            try {
                $dec = Crypt::decryptString($val);
                self::$decryptedCache[$key] = $dec;
                return $dec;
            } catch (\Throwable $e) {
                // If encrypted ciphertext cannot be decrypted (e.g. key changed), return default fallback
                self::$decryptedCache[$key] = (string) $default;
                return (string) $default;
            }
        }

        self::$decryptedCache[$key] = $val;
        return $val;
    }

    public static function set(string $key, ?string $value, bool $isSecret = true): void
    {
        $enc = null;
        if ($value !== null) {
            $enc = $isSecret ? Crypt::encryptString($value) : $value;
        }

        \DB::table('brain_settings')->updateOrInsert(
            ['key' => $key],
            ['value_enc' => $enc, 'is_secret' => $isSecret, 'updated_at' => now(), 'created_at' => now()]
        );

        if (self::$allSettings !== null) {
            self::$allSettings[$key] = $enc;
        }
        self::$decryptedCache[$key] = (string) $value;
    }

    /** Convenience: every credential the AI & messaging layer needs. */
    public static function aiCredentials(): array
    {
        return [
            'omniroute_url'          => rtrim(self::get('omniroute_url', 'https://ai.vmstudio.digital'), '/'),
            'omniroute_key'          => self::get('omniroute_key'),
            'openai_key'             => self::get('openai_key'),
            'openrouter_key'         => self::get('openrouter_key'),
            'gemini_key'             => self::get('gemini_key'),
            'huggingface_token'      => self::get('huggingface_token'),
            'cloudflare_account_id'  => self::get('cloudflare_account_id', env('CLOUDFLARE_ACCOUNT_ID', '')),
            'cloudflare_api_token'   => self::get('cloudflare_api_token', env('CLOUDFLARE_API_TOKEN', '')),
            'pexels_api_key'         => self::get('pexels_api_key', env('PEXELS_API_KEY', '')),
            'ai_worker_token'        => self::get('ai_worker_token', 'onlinepuja_kgl_worker_sec_2026'),
            'model_omniroute'        => self::get('model_omniroute', 'auto/gemini'),
            'model_omniroute_image'  => self::get('model_omniroute_image', 'gemini-3.1-flash-image'),
            'model_omniroute_video'  => self::get('model_omniroute_video', 'fal-ai/xai/grok-imagine-video/text-to-video'),
            'model_openrouter'       => self::get('model_openrouter', 'deepseek/deepseek-r1:free'),
            'model_gemini'           => self::get('model_gemini', 'gemini-1.5-flash'),
            'model_pollinations'     => self::get('model_pollinations', 'openai-fast'),
            'ollama_url'             => self::get('ollama_url', 'http://localhost:11434'),
            'ai_text_chain'          => self::get('ai_text_chain', 'omniroute,gemini,openrouter,pollinations,openai'),
            'ai_image_chain'         => self::get('ai_image_chain', 'omniroute,gemini_imagen,cloudflare,huggingface,pexels,pollinations'),
            'ai_video_chain'         => self::get('ai_video_chain', 'ffmpeg_reel,kaggle,omniroute'),
            'ai_video_models'        => self::get('ai_video_models', 'fal-ai/xai/grok-imagine-video/text-to-video,kling-ai/v1.6/standard/text-to-video,luma/ray-2/text-to-video'),
            'evolution_api_url'      => rtrim(self::get('evolution_api_url', config('services.evolution.url', 'https://wa.vmstudio.digital')), '/'),
            'evolution_api_key'      => self::get('evolution_api_key', config('services.evolution.key', 'bebf8554b69b4eff665465956069890dadeb84547a964ba0ac129e43e98576a2')),
            'evolution_instance'     => self::get('evolution_instance', config('services.evolution.instance', 'onlinepuja')),
            'brevo_api_key'          => self::get('brevo_api_key', config('services.brevo.key', '')),
            'brevo_sender_email'     => self::get('brevo_sender_email', config('mail.from.address', 'care@onlinepuja.live')),
            'brevo_sender_name'      => self::get('brevo_sender_name', config('mail.from.name', 'Onlinepuja.live')),
            'brevo_list_id'          => self::get('brevo_list_id', config('services.brevo.list_id', '2')),
        ];
    }

    /** Convenience: marketing, pixels & ad tracking credentials */
    public static function marketingTracking(): array
    {
        return [
            'meta_pixel_id'          => self::get('meta_pixel_id') ?: (self::get('fb_app_id') ?: '1738490804042986'),
            'meta_capi_token'        => self::get('meta_capi_token', ''),
            'ga4_measurement_id'     => self::get('ga4_measurement_id', 'G-7T63JY1D93'),
            'gtm_container_id'       => self::get('gtm_container_id', ''),
            'google_ads_id'          => self::get('google_ads_id', ''),
            'google_ads_label'       => self::get('google_ads_label', ''),
        ];
    }

    /** Convenience: social & dispatch credentials */
    public static function socialCredentials(): array
    {
        $metaId = self::get('meta_app_id') ?: self::get('fb_app_id');
        $metaSecret = self::get('meta_app_secret') ?: self::get('fb_app_secret');

        return [
            'social_webhook_url' => self::get('social_webhook_url', env('SOCIAL_DISPATCH_WEBHOOK', '')),
            'meta_app_id'        => $metaId,
            'meta_app_secret'    => $metaSecret,
            'fb_app_id'          => $metaId,
            'fb_app_secret'      => $metaSecret,
            'fb_api_version'     => self::get('fb_api_version', 'v21.0'),
            'instagram_token'    => self::get('instagram_token'),
            'youtube_client_id'  => self::get('youtube_client_id', env('YOUTUBE_CLIENT_ID', '')),
            'youtube_client_secret' => self::get('youtube_client_secret', env('YOUTUBE_CLIENT_SECRET', '')),
            'youtube_token'      => self::get('youtube_token'),
        ];
    }

    /** Convenience: video branding settings */
    public static function videoBranding(): array
    {
        return [
            'video_brand_title'   => self::get('video_brand_title', 'OnlinePuja.live'),
            'video_brand_tagline' => self::get('video_brand_tagline', 'Direct from Kashi Vishwanath, Varanasi'),
            'video_brand_phone'   => self::get('video_brand_phone', '+91 70071 58014'),
            'video_brand_cta'     => self::get('video_brand_cta', 'OnlinePuja.live'),
            'video_logo_enabled'  => self::get('video_logo_enabled', '1') === '1',
            'video_logo_path'     => self::get('video_logo_path', ''),
            'video_audio_track'   => self::get('video_audio_track', 'sacred_bells'),
        ];
    }
}
