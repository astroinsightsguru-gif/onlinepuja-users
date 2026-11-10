<?php

namespace App\Services;

use App\Brain\Vault;
use Illuminate\Support\Facades\Http;

/**
 * Google Search Console reader — real clicks/impressions and the actual queries
 * bringing people to the site. This turns the Growth OS from guessing (keyword
 * "difficulty" heuristics) into deciding from ground truth: what we already
 * rank for, what's one nudge from page one, and how traffic is really trending.
 *
 * Configure: vault google_service_account_json (see GoogleServiceAccount) and
 * gsc_site_url (e.g. "https://onlinepuja.live/" or "sc-domain:onlinepuja.live").
 */
class SearchConsoleService
{
    protected const SCOPE = 'https://www.googleapis.com/auth/webmasters.readonly';

    public function __construct(protected GoogleServiceAccount $sa)
    {
    }

    public function isConfigured(): bool
    {
        return $this->sa->isConfigured() && $this->siteUrl() !== '';
    }

    /** Actionable reason isConfigured() is false, or '' when it is fine. */
    public function configError(): string
    {
        if (($err = $this->sa->configError()) !== '') {
            return $err;
        }

        return $this->siteUrl() === ''
            ? 'No property set (vault gsc_site_url is empty) — use "https://onlinepuja.live/" or "sc-domain:onlinepuja.live".'
            : '';
    }

    protected function siteUrl(): string
    {
        return Vault::get('gsc_site_url', 'https://onlinepuja.live/');
    }

    /**
     * Aggregate clicks + impressions for a date range (inclusive).
     *
     * @return array{ok:bool,clicks:int,impressions:int,error:string}
     */
    public function totals(string $startDate, string $endDate): array
    {
        $rows = $this->query([
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dimensions' => [],
            'rowLimit' => 1,
        ]);

        if (! $rows['ok']) {
            return ['ok' => false, 'clicks' => 0, 'impressions' => 0, 'error' => $rows['error']];
        }

        $row = $rows['rows'][0] ?? [];

        return [
            'ok' => true,
            'clicks' => (int) round($row['clicks'] ?? 0),
            'impressions' => (int) round($row['impressions'] ?? 0),
            'error' => '',
        ];
    }

    /**
     * Clicks for a single day — what goal:snapshot records as real traffic.
     */
    public function clicksForDate(string $date): ?int
    {
        $t = $this->totals($date, $date);

        return $t['ok'] ? $t['clicks'] : null;
    }

    /**
     * Top search queries over a window, for keyword mining. "Striking distance"
     * queries (position 8-20, decent impressions) are the highest-value targets.
     *
     * @return array{ok:bool,queries:array<int,array{query:string,clicks:int,impressions:int,position:float}>,error:string}
     */
    public function topQueries(int $days = 28, int $limit = 200): array
    {
        $res = $this->query([
            'startDate' => now()->subDays($days)->toDateString(),
            'endDate' => now()->toDateString(),
            'dimensions' => ['query'],
            'rowLimit' => $limit,
        ]);

        if (! $res['ok']) {
            return ['ok' => false, 'queries' => [], 'error' => $res['error']];
        }

        $queries = array_map(fn ($r) => [
            'query' => $r['keys'][0] ?? '',
            'clicks' => (int) round($r['clicks'] ?? 0),
            'impressions' => (int) round($r['impressions'] ?? 0),
            'position' => round($r['position'] ?? 0, 1),
        ], $res['rows']);

        return ['ok' => true, 'queries' => array_values(array_filter($queries, fn ($q) => $q['query'] !== '')), 'error' => ''];
    }

    /**
     * Raw searchAnalytics query.
     *
     * @return array{ok:bool,rows:array,error:string}
     */
    protected function query(array $body): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'rows' => [], 'error' => 'Search Console not configured (service account + gsc_site_url).'];
        }

        $token = $this->sa->token(self::SCOPE);

        if ($token === '') {
            return ['ok' => false, 'rows' => [], 'error' => 'Could not mint a Google access token — check the service-account JSON.'];
        }

        $endpoint = 'https://searchconsole.googleapis.com/webmasters/v3/sites/'
            .rawurlencode($this->siteUrl()).'/searchAnalytics/query';

        try {
            $res = Http::withToken($token)->timeout(30)->post($endpoint, $body);
        } catch (\Throwable $e) {
            return ['ok' => false, 'rows' => [], 'error' => $e->getMessage()];
        }

        if ($res->failed()) {
            return ['ok' => false, 'rows' => [], 'error' => 'HTTP '.$res->status().' :: '.substr($res->body(), 0, 200)];
        }

        return ['ok' => true, 'rows' => (array) $res->json('rows', []), 'error' => ''];
    }
}
