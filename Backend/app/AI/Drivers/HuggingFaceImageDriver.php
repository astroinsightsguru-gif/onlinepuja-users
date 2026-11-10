<?php

namespace App\AI\Drivers;

use App\AI\Contracts\ImageProvider;
use App\Brain\Vault;
use Illuminate\Support\Facades\Http;

/**
 * Hugging Face Serverless Inference Image Driver — Free User Token.
 * Runs black-forest-labs/FLUX.1-schnell without paid subscription.
 */
class HuggingFaceImageDriver implements ImageProvider
{
    protected const MODEL = 'black-forest-labs/FLUX.1-schnell';
    protected const ENDPOINT = 'https://api-inference.huggingface.co/models/' . self::MODEL;

    public function slug(): string
    {
        return 'huggingface';
    }

    public function label(): string
    {
        return 'Hugging Face Serverless (FLUX.1 Schnell Free)';
    }

    public function isConfigured(): bool
    {
        $creds = Vault::aiCredentials();
        return !empty($creds['huggingface_token']);
    }

    public function create(string $prompt, array $args = []): array
    {
        $fail = fn (string $e) => ['ok' => false, 'binary' => '', 'mime' => '', 'credit' => '', 'error' => $e];

        $prompt = trim($prompt);
        if ($prompt === '') {
            return $fail('Empty prompt.');
        }

        $creds = Vault::aiCredentials();
        $token = $creds['huggingface_token'] ?? env('HUGGINGFACE_TOKEN');

        if (empty($token)) {
            return $fail('Hugging Face user access token missing in Vault.');
        }

        try {
            $res = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ])
                ->post(self::ENDPOINT, [
                    'inputs' => $prompt,
                ]);

            if ($res->failed()) {
                return $fail('Hugging Face HTTP ' . $res->status() . ': ' . substr($res->body(), 0, 200));
            }

            $binary = $res->body();
            $mime = strtolower((string) $res->header('Content-Type')) ?: 'image/jpeg';

            if (str_starts_with(trim($binary), '{')) {
                $json = json_decode($binary, true);
                if (!empty($json['error'])) {
                    return $fail('Hugging Face error: ' . $json['error']);
                }
            }

            if (strlen($binary) < 500) {
                return $fail('Hugging Face returned invalid payload.');
            }

            return [
                'ok'     => true,
                'binary' => $binary,
                'mime'   => explode(';', $mime)[0],
                'credit' => 'Generated via Hugging Face Serverless FLUX.1',
                'model'  => self::MODEL,
                'error'  => '',
            ];
        } catch (\Throwable $e) {
            return $fail('Hugging Face exception: ' . $e->getMessage());
        }
    }

    public function models(): array
    {
        return [
            ['id' => 'black-forest-labs/FLUX.1-schnell', 'label' => 'FLUX.1 Schnell (Hugging Face Free)'],
        ];
    }
}
