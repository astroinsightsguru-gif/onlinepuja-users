<?php

namespace App\AI;

use App\AI\Contracts\TextProvider;
use App\AI\Drivers\OmniRouteDriver;
use App\Brain\Vault;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Ordered failover chain manager with per-provider circuit breaker.
 * Chain order lives in the vault (ai_text_chain / ai_image_chain).
 * After N consecutive failures a provider is benched for a cooldown,
 * so a dead key degrades quality instead of stopping the system.
 */
class ProviderManager
{
    protected const MAX_FAILS = 3;
    protected const COOLDOWN_SECONDS = 600;

    protected array $creds;

    public function __construct()
    {
        $this->creds = Vault::aiCredentials();
    }

    public function textProviders(): array
    {
        return [
            'omniroute'    => new OmniRouteDriver(),
            'openai'       => $this->openAiCompat('openai', 'https://api.openai.com/v1', $this->creds['openai_key'], 'gpt-4o'),
            'openrouter'   => $this->openAiCompat('openrouter', 'https://openrouter.ai/api/v1', $this->creds['openrouter_key'], 'openai/gpt-4o-mini'),
            'gemini'       => $this->openAiCompat('gemini', 'https://generativelanguage.googleapis.com/v1beta/openai', $this->creds['gemini_key'], 'gemini-1.5-flash'),
            'pollinations' => new \App\AI\Drivers\PollinationsDriver(),
        ];
    }

    protected function openAiCompat(string $slug, string $base, ?string $key, string $defaultModel): TextProvider
    {
        return new class($slug, $base, $key, $defaultModel) implements TextProvider
        {
            public function __construct(
                protected string $slug,
                protected string $base,
                protected ?string $key,
                protected string $defaultModel
            ) {}

            public function slug(): string
            {
                return $this->slug;
            }

            public function label(): string
            {
                return ucfirst($this->slug);
            }

            public function isConfigured(): bool
            {
                return !empty($this->key);
            }

            public function chat(string $system, string $prompt, array $args = []): array
            {
                $fail = fn (string $e) => ['ok' => false, 'text' => '', 'model' => '', 'usage' => [], 'error' => $e];

                try {
                    $res = Http::asJson()->withToken($this->key)
                        ->timeout((int) ($args['timeout'] ?? 120))
                        ->post(rtrim($this->base, '/').'/chat/completions', [
                            'model'       => $args['model'] ?? $this->defaultModel,
                            'messages'    => [
                                ['role' => 'system', 'content' => $system],
                                ['role' => 'user', 'content' => $prompt],
                            ],
                            'temperature' => (float) ($args['temperature'] ?? 0.7),
                            'max_tokens'  => (int) ($args['max_tokens'] ?? 1200),
                        ]);
                } catch (\Throwable $e) {
                    return $fail($e->getMessage());
                }

                if ($res->failed()) {
                    return $fail('HTTP '.$res->status());
                }

                $j = $res->json();

                return [
                    'ok'    => true,
                    'text'  => $j['choices'][0]['message']['content'] ?? '',
                    'model' => $j['model'] ?? $this->defaultModel,
                    'usage' => $j['usage'] ?? [],
                    'error' => '',
                ];
            }

            public function models(): array
            {
                if (empty($this->key)) {
                    return [];
                }
                try {
                    $res = Http::withToken($this->key)->timeout(12)
                        ->get(rtrim($this->base, '/').'/models');
                } catch (\Throwable $e) {
                    return [];
                }

                $out = [];

                foreach ((array) $res->json('data') as $m) {
                    if ($id = $m['id'] ?? null) {
                        $out[] = ['id' => $id, 'label' => $m['name'] ?? $id];
                    }
                }

                return $out;
            }
        };
    }

    /* ------------------------------------------- circuit breaker ---- */

    protected function benchKey(string $slug): string
    {
        return "ai.bench.$slug";
    }

    protected function isBenched(string $slug): bool
    {
        return Cache::get($this->benchKey($slug), 0) > time();
    }

    protected function recordFailure(string $slug): void
    {
        $k = "ai.fails.$slug";
        $fails = Cache::get($k, 0) + 1;
        Cache::put($k, $fails, 3600);

        if ($fails >= self::MAX_FAILS) {
            Cache::put($this->benchKey($slug), time() + self::COOLDOWN_SECONDS, self::COOLDOWN_SECONDS);
            Cache::forget($k);
        }
    }

    protected function recordSuccess(string $slug): void
    {
        Cache::forget("ai.fails.$slug");
        Cache::forget($this->benchKey($slug));
    }

    /* --------------------------------------------------- chat ------- */

    /**
     * Run a chat down the ordered chain; first success wins.
     */
    public function chat(string $system, string $prompt, array $args = []): array
    {
        $chain = array_filter(array_map('trim', explode(',', $this->creds['ai_text_chain'])));
        $providers = $this->textProviders();
        $errors = [];

        foreach ($chain as $slug) {
            if (! isset($providers[$slug]) || $this->isBenched($slug)) {
                continue;
            }

            /** @var TextProvider $p */
            $p = $providers[$slug];

            if (! $p->isConfigured()) {
                continue;
            }

            $res = $p->chat($system, $prompt, $args);

            if ($res['ok'] && trim($res['text']) !== '') {
                $this->recordSuccess($slug);

                return $res + ['provider' => $slug, 'error' => ''];
            }

            $this->recordFailure($slug);
            $errors[$slug] = $res['error'];
        }

        return [
            'ok' => false, 'text' => '', 'model' => '', 'provider' => '',
            'usage' => [], 'error' => 'All providers failed: '.implode(' | ', $errors),
        ];
    }

    /* --------------------------------------- live model sync -------- */

    /**
     * Sync every configured provider's catalogue into ai_models.
     * Scheduled twice a day — mirrors VMSAI_Model_Sync.
     */
    public function syncModels(): array
    {
        $report = ['providers' => [], 'total' => 0, 'errors' => []];
        $now = now();

        foreach ($this->textProviders() as $slug => $p) {
            if (! $p->isConfigured()) {
                continue;
            }

            try {
                $models = $p->models();
            } catch (\Throwable $e) {
                $report['errors'][$slug] = $e->getMessage();
                continue;
            }

            $uniqueModels = [];
            foreach ($models as $m) {
                if (!empty($m['id'])) {
                    $uniqueModels[$m['id']] = $m;
                }
            }

            $rows = [];
            foreach ($uniqueModels as $m) {
                $rows[] = [
                    'provider'       => $slug,
                    'kind'           => 'text',
                    'model_id'       => $m['id'],
                    'label'          => $m['label'] ?? $m['id'],
                    'enabled'        => true,
                    'last_synced_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 300) as $chunk) {
                try {
                    \DB::table('ai_models')->upsert(
                        $chunk,
                        ['provider', 'kind', 'model_id'],
                        ['label', 'enabled', 'last_synced_at']
                    );
                } catch (\Throwable $e) {
                    // Fallback to row-by-row if upsert unique index is missing
                    foreach ($chunk as $row) {
                        try {
                            \DB::table('ai_models')->updateOrInsert(
                                ['provider' => $row['provider'], 'kind' => $row['kind'], 'model_id' => $row['model_id']],
                                ['label' => $row['label'], 'enabled' => $row['enabled'], 'last_synced_at' => $row['last_synced_at']]
                            );
                        } catch (\Throwable $e2) {}
                    }
                }
            }

            $report['providers'][$slug] = count($uniqueModels);
            $report['total'] += count($uniqueModels);
        }

        // Image & Video models via OmniRoute
        $omni = new OmniRouteDriver();

        if ($omni->isConfigured()) {
            $imageRows = [];
            foreach ($omni->models('image') as $m) {
                $imageRows[] = [
                    'provider'       => 'omniroute',
                    'kind'           => 'image',
                    'model_id'       => $m['id'],
                    'label'          => $m['label'] ?? $m['id'],
                    'enabled'        => true,
                    'last_synced_at' => $now,
                ];
            }
            foreach (array_chunk($imageRows, 300) as $chunk) {
                try {
                    \DB::table('ai_models')->upsert($chunk, ['provider', 'kind', 'model_id'], ['label', 'enabled', 'last_synced_at']);
                } catch (\Throwable $e) {
                    foreach ($chunk as $row) {
                        try {
                            \DB::table('ai_models')->updateOrInsert(
                                ['provider' => $row['provider'], 'kind' => $row['kind'], 'model_id' => $row['model_id']],
                                ['label' => $row['label'], 'enabled' => $row['enabled'], 'last_synced_at' => $row['last_synced_at']]
                            );
                        } catch (\Throwable $e2) {}
                    }
                }
            }
            $report['total'] += count($imageRows);

            $videoRows = [];
            foreach ($omni->models('video') as $m) {
                $videoRows[] = [
                    'provider'       => 'omniroute',
                    'kind'           => 'video',
                    'model_id'       => $m['id'],
                    'label'          => $m['label'] ?? $m['id'],
                    'enabled'        => true,
                    'last_synced_at' => $now,
                ];
            }
            foreach (array_chunk($videoRows, 300) as $chunk) {
                try {
                    \DB::table('ai_models')->upsert($chunk, ['provider', 'kind', 'model_id'], ['label', 'enabled', 'last_synced_at']);
                } catch (\Throwable $e) {
                    foreach ($chunk as $row) {
                        try {
                            \DB::table('ai_models')->updateOrInsert(
                                ['provider' => $row['provider'], 'kind' => $row['kind'], 'model_id' => $row['model_id']],
                                ['label' => $row['label'], 'enabled' => $row['enabled'], 'last_synced_at' => $row['last_synced_at']]
                            );
                        } catch (\Throwable $e2) {}
                    }
                }
            }
            $report['total'] += count($videoRows);
        }

        \DB::table('brain_memories')->updateOrInsert(
            ['k' => 'last_model_sync'],
            ['v' => json_encode($report), 'source' => 'model-sync', 'updated_at' => now(), 'created_at' => now()]
        );

        return $report;
    }

    public function imageProviders(): array
    {
        return [
            'gemini_imagen'=> new \App\AI\Drivers\GoogleImagenDriver(),
            'cloudflare'   => new \App\AI\Drivers\CloudflareImageDriver(),
            'huggingface'  => new \App\AI\Drivers\HuggingFaceImageDriver(),
            'pexels'       => new \App\AI\Drivers\PexelsImageDriver(),
            'pollinations' => new \App\AI\Drivers\PollinationsImageDriver(),
            'omniroute'    => new OmniRouteDriver(),
        ];
    }

    public function videoProviders(): array
    {
        return [
            'ffmpeg_reel' => new \App\AI\Drivers\FfmpegReelVideoDriver(),
            'kaggle'      => new \App\AI\Drivers\KaggleBatchVideoDriver(),
            'omniroute'   => new \App\AI\Drivers\OmniRouteVideoDriver(),
        ];
    }

    /**
     * Generate an image down the ordered fallback chain.
     * Default chain: cloudflare,huggingface,pexels,pollinations,omniroute
     *
     * @param  array{width?:int,height?:int,image_model?:string,seed?:int}  $args
     * @return array{ok:bool,binary:string,mime:string,credit:string,provider:string,model:string,error:string}
     */
    public function image(string $prompt, array $args = []): array
    {
        $rawChain = (string) ($this->creds['ai_image_chain'] ?? 'gemini_imagen,cloudflare,huggingface,pexels,pollinations,omniroute');
        $chain = array_filter(array_map('trim', explode(',', $rawChain)));
        $providers = $this->imageProviders();
        $errors = [];

        foreach ($chain as $slug) {
            if (! isset($providers[$slug]) || $this->isBenched($slug)) {
                continue;
            }

            /** @var \App\AI\Contracts\ImageProvider $p */
            $p = $providers[$slug];

            if (! $p->isConfigured()) {
                continue;
            }

            $res = $p->create($prompt, $args);

            if (! empty($res['ok']) && ! empty($res['binary'])) {
                $this->recordSuccess($slug);

                return $res + ['provider' => $slug, 'model' => $res['model'] ?? $slug, 'error' => ''];
            }

            $this->recordFailure($slug);
            $errors[$slug] = $res['error'] ?? 'Unknown error';
        }

        // Guaranteed safety fallback: Pollinations (keyless)
        if (isset($providers['pollinations'])) {
            $fallback = $providers['pollinations']->create($prompt, $args);
            if (! empty($fallback['ok']) && ! empty($fallback['binary'])) {
                return $fallback + ['provider' => 'pollinations', 'model' => 'flux-safety', 'error' => ''];
            }
        }

        return [
            'ok' => false, 'binary' => '', 'mime' => '', 'credit' => '',
            'provider' => '', 'model' => '',
            'error' => 'All image providers failed: ' . implode(' | ', $errors),
        ];
    }

    /**
     * Generate a short-form video/reel down the video fallback chain.
     * Default chain: ffmpeg_reel,kaggle,omniroute
     *
     * @param  array{width?:int,height?:int,seconds?:int,model?:string,image_url?:string,title?:string,subtitle?:string}  $args
     * @return array{ok:bool,binary:string,url:string,mime:string,provider:string,model:string,duration_ms:int,credit:string,error:string}
     */
    public function video(string $prompt, array $args = []): array
    {
        $rawChain = (string) ($this->creds['ai_video_chain'] ?? 'ffmpeg_reel,kaggle,omniroute');
        $chain = array_filter(array_map('trim', explode(',', $rawChain)));
        $providers = $this->videoProviders();
        $errors = [];

        foreach ($chain as $slug) {
            if (! isset($providers[$slug]) || $this->isBenched($slug)) {
                continue;
            }

            /** @var \App\AI\Contracts\VideoProvider $p */
            $p = $providers[$slug];

            if (! $p->isConfigured()) {
                continue;
            }

            $res = $p->create($prompt, $args);

            if (! empty($res['ok']) && (! empty($res['binary']) || ! empty($res['url']) || ! empty($res['queued']))) {
                $this->recordSuccess($slug);

                return $res + ['provider' => $slug, 'model' => $res['model'] ?? $slug, 'error' => ''];
            }

            $this->recordFailure($slug);
            $errors[$slug] = $res['error'] ?? 'Unknown error';
        }

        // Guaranteed safety fallback: Native FFmpeg reel
        if (isset($providers['ffmpeg_reel'])) {
            $fallback = $providers['ffmpeg_reel']->create($prompt, $args);
            if (! empty($fallback['ok'])) {
                return $fallback + ['provider' => 'ffmpeg_reel', 'model' => 'h264-reel-safety', 'error' => ''];
            }
        }

        return [
            'ok' => false, 'binary' => '', 'url' => '', 'mime' => '',
            'provider' => '', 'model' => '', 'duration_ms' => 0, 'credit' => '',
            'error' => 'All video providers failed: ' . implode(' | ', $errors),
        ];
    }

    public function breakerState(): array
    {
        $state = [];
        foreach (array_keys($this->textProviders()) as $slug) {
            $state[$slug] = [
                'benched' => $this->isBenched($slug),
                'fails' => \Illuminate\Support\Facades\Cache::get("ai.fails.$slug", 0),
            ];
        }
        return $state;
    }
}
