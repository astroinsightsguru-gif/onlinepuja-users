<?php

namespace App\AI\Drivers;

use App\AI\Contracts\TextProvider;
use Illuminate\Support\Facades\Http;

/**
 * Pollinations — free, keyless text generation (OpenAI-compatible POST).
 * Used as the last-resort fallback so content never fully stops.
 */
class PollinationsDriver implements TextProvider
{
    public function slug(): string
    {
        return 'pollinations';
    }

    public function label(): string
    {
        return 'Pollinations (free)';
    }

    public function isConfigured(): bool
    {
        return true; // keyless
    }

    public function chat(string $system, string $prompt, array $args = []): array
    {
        $fail = fn (string $e) => ['ok' => false, 'text' => '', 'model' => 'openai', 'usage' => [], 'error' => $e];

        try {
            $res = Http::asJson()->timeout((int) ($args['timeout'] ?? 60))
                ->post('https://text.pollinations.ai/openai', [
                    'model'      => $args['model'] ?? 'openai-fast',
                    'messages'   => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);
        } catch (\Throwable $e) {
            return $fail($e->getMessage());
        }

        if ($res->failed()) {
            // Fallback: anonymous GET endpoint
            $full = $system."\n\n".$prompt;
            $url = 'https://text.pollinations.ai/'.rawurlencode(mb_substr($full, 0, 1000)).'?model=mistral';

            try {
                $get = Http::timeout(8)->get($url);
            } catch (\Throwable $e) {
                return $fail('POST failed + GET failed: '.$e->getMessage());
            }

            if ($get->successful() && trim((string) $get->body()) !== '') {
                return [
                    'ok'    => true,
                    'text'  => trim($get->body()),
                    'model' => 'pollinations/openai-fast (get)',
                    'usage' => [],
                    'error' => '',
                ];
            }

            return $fail('POST HTTP '.$res->status().' · GET HTTP '.$get->status());
        }

        // Response may be plain text or OpenAI-shaped.
        $text = is_string($body = $res->body()) && ! str_starts_with(trim($body), '{')
            ? trim($body)
            : ($res->json('choices.0.message.content') ?? '');

        if (trim((string) $text) === '') {
            return $fail('Empty response');
        }

        return [
            'ok'    => true,
            'text'  => (string) $text,
            'model' => 'pollinations/openai',
            'usage' => [],
            'error' => '',
        ];
    }

    public function models(): array
    {
        return [
            ['id' => 'openai', 'label' => 'Pollinations OpenAI'],
        ];
    }
}

