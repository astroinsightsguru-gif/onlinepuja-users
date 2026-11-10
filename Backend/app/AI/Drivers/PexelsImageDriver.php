<?php

namespace App\AI\Drivers;

use App\AI\Contracts\ImageProvider;
use App\Brain\Vault;
use Illuminate\Support\Facades\Http;

/**
 * Pexels Authentic Photography Driver — 100% Free (20,000 requests/month).
 * Fetches authentic real-world 4K photographs of Indian temples, Ganga Aarti,
 * brass diyas, and Vedic rituals.
 */
class PexelsImageDriver implements ImageProvider
{
    protected const ENDPOINT = 'https://api.pexels.com/v1/search';

    public function slug(): string
    {
        return 'pexels';
    }

    public function label(): string
    {
        return 'Pexels Authentic Devotional Photography (Free)';
    }

    public function isConfigured(): bool
    {
        $creds = Vault::aiCredentials();
        return !empty($creds['pexels_api_key']);
    }

    public function create(string $prompt, array $args = []): array
    {
        $fail = fn (string $e) => ['ok' => false, 'binary' => '', 'mime' => '', 'credit' => '', 'error' => $e];

        $creds = Vault::aiCredentials();
        $apiKey = $creds['pexels_api_key'] ?? env('PEXELS_API_KEY');

        if (empty($apiKey)) {
            return $fail('Pexels API Key missing in Vault.');
        }

        // Simplify prompt for search query (Pexels keyword matching works best with concise terms)
        $searchQuery = $this->extractKeywords($prompt);

        $width = (int) ($args['width'] ?? 1024);
        $height = (int) ($args['height'] ?? 1024);
        $orientation = 'square';
        if ($width > $height) {
            $orientation = 'landscape';
        } elseif ($height > $width) {
            $orientation = 'portrait';
        }

        try {
            $res = Http::timeout(20)
                ->withHeaders([
                    'Authorization' => $apiKey,
                ])
                ->get(self::ENDPOINT, [
                    'query'       => $searchQuery,
                    'orientation' => $orientation,
                    'per_page'    => 5,
                    'page'        => rand(1, 3),
                ]);

            if ($res->failed()) {
                return $fail('Pexels API HTTP ' . $res->status() . ': ' . substr($res->body(), 0, 150));
            }

            $data = $res->json();
            $photos = $data['photos'] ?? [];

            if (empty($photos)) {
                // Fallback search term if specific query had 0 results
                $res = Http::timeout(20)
                    ->withHeaders(['Authorization' => $apiKey])
                    ->get(self::ENDPOINT, [
                        'query'       => 'hindu temple worship varanasi',
                        'orientation' => $orientation,
                        'per_page'    => 3,
                    ]);
                $photos = $res->json('photos') ?? [];
            }

            if (empty($photos)) {
                return $fail('No devotional photos found on Pexels for query: ' . $searchQuery);
            }

            $chosen = $photos[array_rand($photos)];
            $imgUrl = $chosen['src']['large2x'] ?? $chosen['src']['original'] ?? $chosen['src']['large'] ?? null;

            if (empty($imgUrl)) {
                return $fail('No valid photo URL returned by Pexels.');
            }

            $dl = Http::timeout(30)->get($imgUrl);
            if ($dl->failed()) {
                return $fail('Failed to download image from Pexels CDN.');
            }

            $photographer = $chosen['photographer'] ?? 'Pexels Contributor';

            return [
                'ok'     => true,
                'binary' => $dl->body(),
                'mime'   => 'image/jpeg',
                'credit' => "Photo by {$photographer} on Pexels",
                'model'  => 'pexels-curated-4k',
                'error'  => '',
            ];
        } catch (\Throwable $e) {
            return $fail('Pexels exception: ' . $e->getMessage());
        }
    }

    protected function extractKeywords(string $prompt): string
    {
        $clean = preg_replace('/—.*/', '', $prompt); // strip style anchor
        $clean = strtolower(trim($clean));

        // Spiritual keyword mapping
        if (str_contains($clean, 'shiva') || str_contains($clean, 'rudra') || str_contains($clean, 'kashi') || str_contains($clean, 'lingam')) {
            return 'shiva lingam varanasi temple';
        }
        if (str_contains($clean, 'ganesh') || str_contains($clean, 'vinayaka')) {
            return 'lord ganesha temple statue';
        }
        if (str_contains($clean, 'hanuman') || str_contains($clean, 'sundarkand')) {
            return 'hanuman temple puja';
        }
        if (str_contains($clean, 'lakshmi') || str_contains($clean, 'wealth') || str_contains($clean, 'diwali')) {
            return 'diwali brass diya oil lamp puja';
        }
        if (str_contains($clean, 'durga') || str_contains($clean, 'navratri') || str_contains($clean, 'kali')) {
            return 'durga puja celebration temple';
        }
        if (str_contains($clean, 'havan') || str_contains($clean, 'yagya') || str_contains($clean, 'homa')) {
            return 'hindu sacred fire havan ritual';
        }
        if (str_contains($clean, 'ganga') || str_contains($clean, 'aarti') || str_contains($clean, 'ghat')) {
            return 'varanasi ganga aarti ghat evening';
        }

        return 'hindu sacred temple puja lamp';
    }

    public function models(): array
    {
        return [
            ['id' => 'pexels-curated-4k', 'label' => 'Pexels Authentic 4K Temple Photography (Free)'],
        ];
    }
}
