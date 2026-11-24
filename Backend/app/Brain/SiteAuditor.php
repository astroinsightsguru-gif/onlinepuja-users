<?php

namespace App\Brain;

use Illuminate\Support\Facades\Http;

/**
 * Technical/on-page auditor for every URL in our own sitemap.
 * Writes seo_issues rows; the Fixer decides what it may heal autonomously.
 */
class SiteAuditor
{
    public function scan(int $maxUrls = 40): array
    {
        $urls = $this->sitemapUrls($maxUrls);
        $issues = 0;
        $pages = 0;
        $resolved = 0;

        foreach ($urls as $url) {
            try {
                $res = Http::withoutVerifying()->timeout(20)->get($url);
            } catch (\Throwable $e) {
                continue;
            }

            if (! $res->successful()) {
                continue;
            }

            $pages++;
            $html = $res->body();
            $found = $this->analyse($url, $html);

            foreach ($found as $issue) {
                $this->upsert($issue + ['page_url' => $url]);
                $issues++;
            }

            // upsert() only ever adds or refreshes what THIS pass detects —
            // an issue a prior scan found that no longer reproduces (fixed
            // in code, healed by seo:fix, or the page simply changed) would
            // otherwise sit 'open' forever, since nothing else ever revisits
            // it. Close exactly what this scan looked for and didn't find.
            $stillOpen = collect($found)->pluck('type')->all();

            $resolved += \DB::table('seo_issues')
                ->where('page_url', $url)
                ->where('status', 'open')
                ->whereNotIn('type', $stillOpen ?: [''])
                ->update(['status' => 'auto_resolved', 'fixed_at' => now(), 'updated_at' => now()]);
        }

        return ['pages_scanned' => $pages, 'issues' => $issues, 'auto_resolved' => $resolved];
    }

    protected function sitemapUrls(int $cap): array
    {
        $file = public_path('sitemap.xml');

        if (! file_exists($file)) {
            return [url('/')];
        }

        preg_match_all('/<loc>([^<]+)<\/loc>/i', file_get_contents($file), $m);
        $rawUrls = array_unique($m[1] ?? []);
        $urls = [];

        foreach ($rawUrls as $u) {
            $u = trim($u);
            if (empty($u)) {
                continue;
            }

            // If it's a child XML sitemap (e.g. sitemap index), extract nested page URLs from it
            if (preg_match('/\.xml(\?.*)?$/i', $u)) {
                $subPath = public_path(ltrim(parse_url($u, PHP_URL_PATH) ?: '', '/'));
                if (file_exists($subPath)) {
                    preg_match_all('/<loc>([^<]+)<\/loc>/i', file_get_contents($subPath), $subM);
                    foreach ($subM[1] ?? [] as $subU) {
                        $subU = trim($subU);
                        if (!preg_match('/\.xml(\?.*)?$/i', $subU) && !empty($subU)) {
                            $urls[] = $subU;
                        }
                    }
                }
                continue;
            }

            $urls[] = $u;
        }

        return array_slice(array_unique($urls), 0, $cap) ?: [url('/')];
    }

    protected function analyse(string $url, string $html): array
    {
        $out = [];

        // Title
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
            $len = mb_strlen(trim(html_entity_decode($m[1])));

            if ($len === 0) {
                $out[] = ['type' => 'missing_title', 'severity' => 'high', 'detail' => null];
            } elseif ($len > 65) {
                $out[] = ['type' => 'long_title', 'severity' => 'medium', 'detail' => ['length' => $len, 'title' => trim($m[1])]];
            }
        } else {
            $out[] = ['type' => 'missing_title', 'severity' => 'high', 'detail' => null];
        }

        // Meta description
        if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/is', $html, $m)) {
            $len = mb_strlen(trim(html_entity_decode($m[1])));

            if ($len < 70) {
                $out[] = ['type' => 'short_meta_description', 'severity' => 'medium', 'detail' => ['length' => $len, 'current' => trim($m[1])]];
            } elseif ($len > 170) {
                $out[] = ['type' => 'long_meta_description', 'severity' => 'low', 'detail' => ['length' => $len]];
            }
        } else {
            $out[] = ['type' => 'missing_meta_description', 'severity' => 'medium', 'detail' => null];
        }

        // H1 count
        $h1 = preg_match_all('/<h1[\s>]/i', $html);

        if ($h1 === 0) {
            $out[] = ['type' => 'missing_h1', 'severity' => 'medium', 'detail' => null];
        } elseif ($h1 > 1) {
            $out[] = ['type' => 'multiple_h1', 'severity' => 'low', 'detail' => ['count' => $h1]];
        }

        // Images missing alt
        preg_match_all('/<img\b[^>]*>/i', $html, $imgs);
        $missingAlt = 0;

        foreach ($imgs[0] as $tag) {
            if (! preg_match('/alt=/i', $tag)) {
                $missingAlt++;
            }
        }

        if ($missingAlt > 0) {
            $out[] = ['type' => 'images_missing_alt', 'severity' => 'low', 'detail' => ['count' => $missingAlt]];
        }

        // Public page accidentally noindexed
        if (preg_match('/name=["\']robots["\']\s+content=["\'][^"\']*noindex/i', $html)) {
            $out[] = ['type' => 'accidental_noindex', 'severity' => 'high', 'detail' => null];
        }

        return $out;
    }

    protected function upsert(array $issue): void
    {
        \DB::table('seo_issues')->updateOrInsert(
            ['type' => $issue['type'], 'page_url' => $issue['page_url']],
            [
                'severity' => $issue['severity'],
                'detail' => json_encode($issue['detail']),
                'status' => \DB::raw("IF(status IN ('fixed','reverted'), status, 'open')"),
                'updated_at' => now(),
            ]
        );
    }
}
