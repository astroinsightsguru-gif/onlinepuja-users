<?php

namespace App\Console\Commands;

use App\AI\ProviderManager;
use Illuminate\Console\Command;

class AiSyncModelsCommand extends Command
{
    protected $signature = 'ai:sync-models';

    protected $description = 'Pull live model catalogues from every configured AI provider';

    public function handle(ProviderManager $manager): int
    {
        $report = $manager->syncModels();

        foreach ($report['providers'] as $slug => $count) {
            $this->line("$slug: $count models");
        }

        foreach ($report['errors'] as $slug => $err) {
            $this->warn("$slug ERROR: $err");
        }

        $this->info('TOTAL synced: '.$report['total']);

        // A provider that is configured but returned nothing is the case that
        // hid a four-day-stale catalogue: the run "succeeded" with 0 models and
        // no error, and nothing downstream could tell that apart from a gateway
        // that genuinely has no models.
        $empty = array_keys(array_filter($report['providers'], fn ($c) => $c === 0));

        foreach ($empty as $slug) {
            $this->warn("$slug returned 0 models while configured — its previously synced models are now stale.");
        }

        \App\Brain\RunLog::record(
            'ai:sync-models',
            'ai',
            ($report['errors'] || $empty) ? 'partial' : 'ok',
            sprintf('Synced %d model(s) across %d provider(s)%s',
                $report['total'],
                count($report['providers']),
                $report['errors'] ? '; '.count($report['errors']).' failed' : ''),
            $report,
            $report['total'],
            count($report['errors'])
        );

        return $report['errors'] ? self::FAILURE : self::SUCCESS;
    }
}
