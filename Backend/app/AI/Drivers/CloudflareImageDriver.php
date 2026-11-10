<?php

namespace App\AI\Drivers;

use App\AI\Contracts\ImageProvider;
use App\Brain\Vault;
use Illuminate\Support\Facades\Http;

/**
 * Cloudflare Workers AI image generation — 100% Free Daily Tier (10,000 neurons/day).
 * Generates photorealistic FLUX.1-schnell images with no credit card billing required.
 */
class CloudflareImageDriver implements ImageProvider
{
    protected const MODEL = '@cf/black-forest-labs/flux-1-schnell';

    public function slug(): string
    {
        return 'cloudflare';
    }

    public function label(): string
    {
        return 'Cloudflare Workers AI (FLUX.1 Schnell Free)';
    }

    public function isConfigured(): bool
    {
        $creds = Vault::aiCredentials();
        return !empty($creds['cloudflare_account_id']) && !empty($creds['cloudflare_api_token']);
    }

    public function create(string $prompt, array $args = []): array
    {
        $fail = fn (string $e) => ['ok' => false, 'binary' => '', 'mime' => '', 'credit' => '', 'error' => $e];

        $prompt = trim($prompt);
        if ($prompt === '') {
            return $fail('Empty prompt.');
        }

        $creds = Vault::aiCredentials();
        $accountId = $creds['cloudflare_account_id'] ?? env('CLOUDFLARE_ACCOUNT_ID');
        $apiToken  = $creds['cloudflare_api_token']  ?? env('CLOUDFLARE_API_TOKEN');

        if (empty($accountId) || empty($apiToken)) {
            return $fail('Cloudflare credentials (account_id or api_token) missing in Vault.');
        }

        $url = "https://api.cloudflare.com/client/v4/accounts/{$accountId}/ai/run/" . self::MODEL;

        try {
            $res = Http::timeout(45)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiToken,
                    'Content-Type'  => 'application/json',
                ])
                ->post($url, [
                    'prompt'    => $prompt,
                    'num_steps' => (int) ($args['num_steps'] ?? 4),
                ]);

            if ($res->failed()) {
                return $fail('Cloudflare HTTP ' . $res->status() . ': ' . substr($res->body(), 0, 200));
            }

            $binary = $res->body();
            $mime = strtolower((string) $res->header('Content-Type')) ?: 'image/jpeg';

            if (str_starts_with(trim($binary), '{')) {
                $json = json_decode($binary, true);
                if (!empty($json['errors'])) {
                    return $fail('Cloudflare error: ' . json_encode($json['errors']));
                }
            }

            if (strlen($binary) < 500) {
                return $fail('Cloudflare returned truncated binary (' . strlen($binary) . ' bytes).');
            }

            return [
                'ok'     => true,
                'binary' => $binary,
                'mime'   => explode(';', $mime)[0],
                'credit' => 'Generated via Cloudflare Workers AI FLUX.1 Schnell',
                'model'  => self::MODEL,
                'error'  => '',
            ];
        } catch (\Throwable $e) {
            return $fail('Cloudflare exception: ' . $e->getMessage());
        }
    }

    public function models(): array
    {
        return [
            ['id' => '@cf/black-forest-labs/flux-1-schnell', 'label' => 'FLUX.1 Schnell (Photorealistic, Free)'],
            ['id' => '@cf/stabilityai/stable-diffusion-xl-base-1.0', 'label' => 'SDXL 1.0 (Free)'],
        ];
    }
}
