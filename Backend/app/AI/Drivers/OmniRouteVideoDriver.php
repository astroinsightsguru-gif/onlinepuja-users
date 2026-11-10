<?php

namespace App\AI\Drivers;

use App\AI\Contracts\VideoProvider;
use App\Brain\Vault;
use Illuminate\Support\Facades\Http;

/**
 * OmniRoute video generation gateway.
 */
class OmniRouteVideoDriver implements VideoProvider
{
    protected const POLL_INTERVAL = 6;

    protected function base(): string
    {
        $base = rtrim(Vault::aiCredentials()['omniroute_url'], '/');
        return str_ends_with($base, '/v1') ? $base : $base.'/v1';
    }

    protected function key(): string
    {
        return (string) Vault::aiCredentials()['omniroute_key'];
    }

    protected function endpoint(): string
    {
        return trim((string) Vault::get('video_endpoint', 'videos'), '/');
    }

    public function slug(): string
    {
        return 'omniroute';
    }

    public function label(): string
    {
        return 'OmniRoute';
    }

    public function isConfigured(): bool
    {
        return $this->key() !== '' && ! str_contains($this->key(), 'REPLACE_WITH');
    }

    public function create(string $prompt, array $args = []): array
    {
        $fail = fn (string $e) => [
            'ok' => false, 'binary' => '', 'url' => '', 'mime' => '', 'model' => '',
            'duration_ms' => 0, 'credit' => '', 'error' => $e,
        ];

        if (! $this->isConfigured()) {
            return $fail('OmniRoute not configured.');
        }

        $model = $args['model']
            ?? (Vault::aiCredentials()['model_omniroute_video'] ?: 'sora-2');

        $seconds = max(1, (int) ($args['seconds'] ?? 5));
        $w = (int) ($args['width'] ?? 1080);
        $h = (int) ($args['height'] ?? 1920);

        $payload = array_filter([
            'model'   => $model,
            'prompt'  => $prompt,
            'size'    => $w.'x'.$h,
            'seconds' => (string) $seconds,
            'image_url' => $args['image_url'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        try {
            $res = Http::asJson()->withToken($this->key())
                ->timeout(45)
                ->post($this->base().'/'.$this->endpoint(), $payload);
        } catch (\Throwable $e) {
            return $fail('submit: '.$e->getMessage());
        }

        if ($res->failed()) {
            return $fail('submit HTTP '.$res->status().' :: '.substr($res->body(), 0, 300));
        }

        $job = $res->json();
        $status = strtolower((string) ($job['status'] ?? ''));

        if (in_array($status, ['completed', 'succeeded', 'success'], true) || isset($job['url']) || isset($job['data'][0]['url'])) {
            return $this->collect($job, $model, $seconds, $fail);
        }

        $id = $job['id'] ?? $job['data'][0]['id'] ?? null;

        if (! $id) {
            return $fail('No job id returned from submit.');
        }

        return $this->poll((string) $id, $model, $seconds, $fail);
    }

    protected function poll(string $id, string $model, int $seconds, callable $fail): array
    {
        $deadline = time() + (int) (Vault::get('video_poll_seconds', '300') ?: 300);

        while (time() < $deadline) {
            sleep(self::POLL_INTERVAL);

            try {
                $res = Http::withToken($this->key())->timeout(30)->get($this->base().'/'.$this->endpoint()."/{$id}");
            } catch (\Throwable $e) {
                continue;
            }

            if ($res->failed()) {
                continue;
            }

            $job = $res->json();
            $status = strtolower((string) ($job['status'] ?? ''));

            if (in_array($status, ['completed', 'succeeded', 'success'], true)) {
                return $this->collect($job + ['id' => $id], $model, $seconds, $fail);
            }

            if (in_array($status, ['failed', 'error', 'cancelled', 'canceled'], true)) {
                return $fail('render '.$status.': '.substr((string) ($job['error'] ?? json_encode($job)), 0, 200));
            }
        }

        return $fail('Timed out waiting for the video to render.');
    }

    protected function collect(array $job, string $model, int $seconds, callable $fail): array
    {
        $ok = fn (array $extra) => array_merge([
            'ok' => true, 'binary' => '', 'url' => '', 'mime' => 'video/mp4',
            'model' => $model, 'duration_ms' => $seconds * 1000,
            'credit' => 'Generated via OmniRoute ('.$model.')', 'error' => '',
        ], $extra);

        $url = $job['url'] ?? $job['data'][0]['url'] ?? $job['output'][0] ?? null;

        if (is_string($url) && $url !== '') {
            try {
                $dl = Http::timeout(120)->get($url);
                if ($dl->ok() && $dl->body() !== '') {
                    return $ok(['binary' => $dl->body(), 'mime' => $dl->header('Content-Type') ?: 'video/mp4']);
                }
            } catch (\Throwable $e) {
            }

            return $ok(['url' => $url]);
        }

        if ($id = $job['id'] ?? null) {
            try {
                $bin = Http::withToken($this->key())->timeout(120)->get($this->base().'/'.$this->endpoint()."/{$id}/content");
                if ($bin->ok() && $bin->body() !== '') {
                    return $ok(['binary' => $bin->body(), 'mime' => $bin->header('Content-Type') ?: 'video/mp4']);
                }
            } catch (\Throwable $e) {
                return $fail('download: '.$e->getMessage());
            }
        }

        return $fail('Job completed but no video URL or content was returned.');
    }
}
