<?php

namespace App\Console\Commands;

use App\Brain\RunLog;
use App\services\SearchConsoleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Mines Google Search Console for the queries people ACTUALLY use to find the
 * site, and seeds them as keywords. "Striking distance" queries — already
 * ranking around positions 8-20 with real impressions — are the single richest
 * source of quick wins, because a little targeted content pushes them onto
 * page one. Far better than guessing keywords from a heuristic.
 */
class GscMineCommand extends Command
{
    protected $signature = 'gsc:mine
        {--days=28 : Lookback window}
        {--min-impressions=20 : Ignore queries below this many impressions}
        {--limit=200 : Max queries to pull}';

    protected $description = 'Seed keywords from real Search Console queries (striking-distance first)';

    public function handle(SearchConsoleService $gsc): int
    {
        if (! $gsc->isConfigured()) {
            $this->warn('Search Console is not configured: '.$gsc->configError());
            $this->line('Then share the property with the service account\'s client_email as a '.
                'Full or Restricted user in Search Console -> Settings -> Users and permissions.');

            return self::SUCCESS;
        }

        $res = $gsc->topQueries((int) $this->option('days'), (int) $this->option('limit'));

        if (! $res['ok']) {
            $this->error($res['error']);
            RunLog::record('gsc:mine', 'seo', 'fail', $res['error']);

            return self::FAILURE;
        }

        $minImp = (int) $this->option('min-impressions');
        $added = 0;
        $skipped = 0;

        foreach ($res['queries'] as $q) {
            if ($q['impressions'] < $minImp) {
                continue;
            }

            $phrase = mb_strtolower(trim($q['query']));

            if ($phrase === '' || DB::table('keywords')->where('phrase', $phrase)->exists()) {
                $skipped++;

                continue;
            }

            DB::table('keywords')->insert([
                'phrase' => $phrase,
                'source' => 'gsc',
                'est_volume' => $q['impressions'],
                // Position IS the difficulty signal from real data: already near
                // page one = easy win; buried deep = harder.
                'difficulty' => $this->difficultyFromPosition($q['position']),
                'intent' => $this->intent($phrase),
                'cluster' => explode(' ', $phrase)[0],
                'status' => 'new',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $added++;
        }

        $this->info("Mined {$added} new keyword(s) from Search Console ({$skipped} already known/too small).");

        RunLog::recordUnlessNoop(
            'gsc:mine', 'seo', 'ok',
            "Mined {$added} new keyword(s) from real search queries",
            ['added' => $added, 'skipped' => $skipped],
            $added, 0
        );

        return self::SUCCESS;
    }

    /** Real ranking position → difficulty score (lower = easier to win). */
    protected function difficultyFromPosition(float $position): int
    {
        return match (true) {
            $position <= 0 => 50,          // no position data
            $position <= 10 => 25,         // already near page one — easy win
            $position <= 20 => 40,         // striking distance
            $position <= 40 => 60,
            default => 75,
        };
    }

    protected function intent(string $phrase): string
    {
        return preg_match('/\b(book|online|cost|price|near me|fees?|buy|hire|consultation)\b/', $phrase)
            ? 'commercial'
            : 'informational';
    }
}
