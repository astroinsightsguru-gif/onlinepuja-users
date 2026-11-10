<?php

namespace App\Console\Commands;

use App\Brain\Learner;
use App\Brain\RunLog;
use Illuminate\Console\Command;

class BrainLearnCommand extends Command
{
    protected $signature = 'brain:learn {--show : Print the current playbook without recomputing}';

    protected $description = 'Recompute what is actually working from collected engagement, and cache it as the playbook';

    public function handle(Learner $learner): int
    {
        if ($this->option('show')) {
            $this->line(json_encode($learner->playbook(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $book = $learner->rebuild();

        if (! $book['ready']) {
            $this->info("Only {$book['samples']} measured post(s) so far — keeping hand-tuned defaults until there is enough signal.");

            RunLog::recordUnlessNoop(
                'brain:learn', 'brain', 'ok',
                "Not enough data yet ({$book['samples']} measured post(s))",
                $book, 0, 0
            );

            return self::SUCCESS;
        }

        $this->info("Learned from {$book['samples']} measured post(s).");

        foreach ($book['hours'] as $platform => $hours) {
            $this->line("  best hours [{$platform}]: ".implode(', ', array_slice($hours, 0, 3)).' IST');
        }

        foreach ($book['caption'] as $trait => $value) {
            $this->line("  caption {$trait}: ".(is_bool($value) ? ($value ? 'yes' : 'no') : $value));
        }

        if ($book['kinds']) {
            $this->line('  best format: '.array_key_first($book['kinds']));
        }

        foreach ($book['astrologer_sources'] as $astrologerId => $source) {
            $this->line("  astrologer #{$astrologerId} performs best with: {$source}");
        }

        RunLog::recordUnlessNoop(
            'brain:learn', 'brain', 'ok',
            "Playbook rebuilt from {$book['samples']} measured post(s)",
            $book, $book['samples'], 0
        );

        return self::SUCCESS;
    }
}
