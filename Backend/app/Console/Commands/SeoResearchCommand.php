<?php

namespace App\Console\Commands;

use App\Brain\KeywordResearch;
use App\Brain\RunLog;
use Illuminate\Console\Command;

class SeoResearchCommand extends Command
{
    protected $signature = 'seo:research';

    protected $description = 'Tri-source keyword research: Google autocomplete + AI expansion (+GSC later)';

    public function handle(KeywordResearch $research): int
    {
        // Until this line, this command wrote nothing to brain_run_log — the
        // one keyword source with no observability at all. There was no way to
        // tell from the dashboard, or from brain:doctor, whether this had run
        // successfully in the last week or been silently broken since deploy.
        $runId = RunLog::start('seo:research', 'seo');

        $this->info('Running tri-source keyword research…');

        try {
            $report = $research->run();
        } catch (\Throwable $e) {
            RunLog::finish($runId, 'fail', 'Threw: '.$e->getMessage(), [], 0, 1);
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        foreach ($report as $source => $count) {
            $this->line(sprintf('%-14s %d new phrases', $source, $count));
        }

        $total = \DB::table('keywords')->count();
        $easy = \DB::table('keywords')->where('difficulty', '<=', 45)->count();
        $added = array_sum($report);

        $this->info("Catalogue now: {$total} keywords ({$easy} easy-win candidates)");

        RunLog::finish(
            $runId,
            $added > 0 ? 'ok' : 'partial',
            "{$added} new phrase(s) added ({$total} total, {$easy} easy-win)",
            $report,
            $added,
            0
        );

        return self::SUCCESS;
    }
}
