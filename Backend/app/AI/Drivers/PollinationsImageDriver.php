<?php

namespace App\AI\Drivers;

use App\AI\Contracts\ImageProvider;
use App\Brain\Vault;
use Illuminate\Support\Facades\Http;

/**
 * Pollinations image generation — keyless safety fallback.
 *
 *   GET https://image.pollinations.ai/prompt/{prompt}?width=&height=&model=
 */
class PollinationsImageDriver implements ImageProvider
{
    protected const ENDPOINT = 'https://image.pollinations.ai/prompt/';

    public function slug(): string
    {
        return 'pollinations';
    }

    public function label(): string
    {
        return 'Pollinations (Free Images)';
    }

    public function isConfigured(): bool
    {
        return true; // keyless
    }

    public function create(string $prompt, array $args = []): array
    {
        $fail = fn (string $e) => ['ok' => false, 'binary' => '', 'mime' => '', 'credit' => '', 'error' => $e];

        $prompt = trim($prompt);
        if ($prompt === '') {
            return $fail('Empty prompt.');
        }

        $prompt = mb_substr($prompt, 0, 900);

        $model = $args['image_model']
            ?? (Vault::aiCredentials()['image_model_pollinations'] ?? '')
            ?: 'flux';

        $query = [
            'width'    => (int) ($args['width'] ?? 1024),
            'height'   => (int) ($args['height'] ?? 1024),
            'model'    => $model,
            'nologo'   => 'true',
            'referrer' => 'onlinepuja.live',
        ];

        if (isset($args['seed'])) {
            $query['seed'] = (int) $args['seed'];
        }

        try {
            $res = Http::timeout(45)
                ->withHeaders(['Accept' => 'image/*'])
                ->get(self::ENDPOINT . rawurlencode($prompt), $query);
        } catch (\Throwable $e) {
            return $fail($e->getMessage());
        }

        if ($res->failed()) {
            return $fail('HTTP ' . $res->status() . ' :: ' . substr($res->body(), 0, 200));
        }

        $binary = $res->body();
        $mime = strtolower((string) $res->header('Content-Type')) ?: 'image/jpeg';

        if ($binary === '' || ! str_starts_with($mime, 'image/')) {
            return $fail('Non-image response (' . ($mime ?: 'unknown') . '): ' . substr($binary, 0, 160));
        }

        return [
            'ok'     => true,
            'binary' => $binary,
            'mime'   => explode(';', $mime)[0],
            'credit' => 'Generated via Pollinations AI',
            'model'  => $model,
            'error'  => '',
        ];
    }

    public function models(): array
    {
        return [
            ['id' => 'flux', 'label' => 'FLUX (default)'],
            ['id' => 'turbo', 'label' => 'Turbo (fast)'],
        ];
    }
}
