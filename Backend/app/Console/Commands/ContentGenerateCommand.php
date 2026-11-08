<?php

namespace App\Console\Commands;

use App\Brain\ContentPipeline;
use Illuminate\Console\Command;

class ContentGenerateCommand extends Command
{
    protected $signature = 'content:generate {--limit=2} {--publish : Publish immediately instead of queueing for approval}';

    protected $description = 'AI-draft articles from researched keywords (Critic-gated)';

    public function handle(ContentPipeline $pipeline): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $this->info("Drafting up to {$limit} article(s)…");

        $out = $pipeline->run($limit);

        // A silently reduced budget looks identical to "the keyword pool was
        // thin today". Say which it was.
        if (! empty($out['gated'])) {
            $this->warn(sprintf(
                'Readiness gate: drafting %d of %d — the catalogue cannot convert this traffic yet. Supply tasks filed in the content queue.',
                $out['budget'],
                $out['requested']
            ));
        }

        foreach ($out['results'] as $r) {
            if ($r['ok']) {
                $status = '<info>drafted</info> (plan #'.($r['plan_id'] ?? '?').')';
            } else {
                // A Critic rejection carries 'failures', a provider error carries
                // 'error' — neither key is guaranteed, so never assume one.
                $why = $r['error']
                    ?? (! empty($r['failures']) ? implode('; ', (array) $r['failures']) : 'unknown')
                    ;
                $status = '<comment>'.($r['status'] ?? 'failed').': '.$why.'</comment>';
            }

            $this->line("• {$r['keyword']} — {$status}");
        }

        $this->info("Drafted: {$out['drafted']} · Failed: {$out['failed']}");

        \App\Brain\RunLog::recordUnlessNoop(
            'content:generate', 'blog',
            $out['failed'] > 0 ? ($out['drafted'] > 0 ? 'partial' : 'fail') : 'ok',
            "Drafted {$out['drafted']} article(s), {$out['failed']} failed",
            ['results' => $out['results']],
            $out['drafted'], $out['failed']
        );

        return self::SUCCESS;
    }
}
