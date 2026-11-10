<?php

namespace App\AI\Drivers;

use App\AI\Contracts\ImageProvider;
use App\Brain\Vault;
use Illuminate\Support\Facades\Http;

/**
 * Google AI Studio (Gemini / Imagen 3) Image Generation Driver.
 * Uses official Google AI Studio key (https://aistudio.google.com/) — 100% Free Tier ($0).
 * Model: imagen-3.0-generate-002 (Google DeepMind photorealistic Vedic imagery).
 */
class GoogleImagenDriver implements ImageProvider
{
    protected const MODEL = 'imagen-3.0-generate-002';
    protected const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/imagen-3.0-generate-002:predict';

    public function slug(): string
    {
        return 'gemini_imagen';
    }

    public function label(): string
    {
        return 'Google AI Studio (Imagen 3 Free)';
    }

    public function isConfigured(): bool
    {
        $creds = Vault::aiCredentials();
        $key = $creds['gemini_key'] ?? env('GEMINI_API_KEY');
        return !empty($key) && !str_contains($key, 'REPLACE_WITH');
    }

    public function create(string $prompt, array $args = []): array
    {
        $fail = fn (string $e) => ['ok' => false, 'binary' => '', 'mime' => '', 'credit' => '', 'error' => $e];

        $prompt = trim($prompt);
        if ($prompt === '') {
            return $fail('Empty prompt.');
        }

        $creds = Vault::aiCredentials();
        $apiKey = $creds['gemini_key'] ?? env('GEMINI_API_KEY');

        if (empty($apiKey)) {
            return $fail('Google AI Studio GEMINI_API_KEY missing in Vault or .env');
        }

        $width = (int) ($args['width'] ?? 1024);
        $height = (int) ($args['height'] ?? 1024);

        $aspectRatio = '1:1';
        if ($width > $height * 1.3) {
            $aspectRatio = '16:9';
        } elseif ($height > $width * 1.3) {
            $aspectRatio = '9:16';
        } elseif ($width > $height) {
            $aspectRatio = '4:3';
        } elseif ($height > $width) {
            $aspectRatio = '3:4';
        }

        try {
            $res = Http::timeout(45)
                ->withHeaders([
                    'x-goog-api-key' => $apiKey,
                    'Content-Type'   => 'application/json',
                ])
                ->post(self::ENDPOINT, [
                    'instances' => [
                        ['prompt' => $prompt]
                    ],
                    'parameters' => [
                        'sampleCount' => 1,
                        'aspectRatio' => $aspectRatio,
                        'outputOptions' => [
                            'mimeType' => 'image/jpeg',
                        ]
                    ]
                ]);

            if ($res->failed()) {
                $err = $res->json('error.message') ?? substr($res->body(), 0, 200);
                return $fail('Google AI Studio HTTP ' . $res->status() . ': ' . $err);
            }

            $json = $res->json();
            $predictions = $json['predictions'] ?? [];
            if (empty($predictions)) {
                return $fail('Google Imagen returned no predictions.');
            }

            $b64 = $predictions[0]['bytesBase64Encoded'] ?? null;
            $mime = $predictions[0]['mimeType'] ?? 'image/jpeg';

            if (empty($b64)) {
                return $fail('No base64 image data returned by Google Imagen.');
            }

            $binary = base64_decode($b64);
            if (strlen($binary) < 500) {
                return $fail('Truncated image data received from Google Imagen.');
            }

            return [
                'ok'     => true,
                'binary' => $binary,
                'mime'   => $mime,
                'credit' => 'Generated via Google AI Studio Imagen 3',
                'model'  => self::MODEL,
                'error'  => '',
            ];
        } catch (\Throwable $e) {
            return $fail('Google Imagen exception: ' . $e->getMessage());
        }
    }

    public function models(): array
    {
        return [
            ['id' => 'imagen-3.0-generate-002', 'label' => 'Google Imagen 3 (Photorealistic, Free Tier)'],
        ];
    }
}
