<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Collapses near-duplicate keywords — same words in a different order
 * ("puja booking online" vs "online puja booking") are the same search intent
 * and would otherwise each spawn a near-identical article. Keeps the best row
 * per group (prefers an already-worked one, else the easiest), repoints any
 * content_plans at the survivor, and deletes the rest.
 */
class KeywordsDedupCommand extends Command
{
    protected $signature = 'keywords:dedup {--dry : Report duplicates without deleting}';

    protected $description = 'Merge token-set duplicate keywords, keeping one per intent';

    public function handle(): int
    {
        $groups = [];

        foreach (DB::table('keywords')->get() as $k) {
            $groups[$this->tokenSet($k->phrase)][] = $k;
        }

        $dupeGroups = array_filter($groups, fn ($g) => count($g) > 1);

        if (! $dupeGroups) {
            $this->info('No duplicate keywords found.');

            return self::SUCCESS;
        }

        $removed = 0;

        foreach ($dupeGroups as $group) {
            // Survivor: a keyword already turned into content wins (its plans
            // stay intact); otherwise the easiest to rank.
            usort($group, function ($a, $b) {
                $aw = $a->status !== 'new' ? 0 : 1;
                $bw = $b->status !== 'new' ? 0 : 1;

                return [$aw, $a->difficulty] <=> [$bw, $b->difficulty];
            });

            $keep = array_shift($group);
            $this->line("keep #{$keep->id} \"{$keep->phrase}\" — dropping ".count($group));

            foreach ($group as $dup) {
                if ($this->option('dry')) {
                    $this->line("  would drop #{$dup->id} \"{$dup->phrase}\"");

                    continue;
                }

                DB::table('content_plans')->where('keyword_id', $dup->id)->update(['keyword_id' => $keep->id]);
                DB::table('keywords')->where('id', $dup->id)->delete();
                $removed++;
            }
        }

        $this->info($this->option('dry')
            ? count($dupeGroups).' duplicate group(s) found (dry run).'
            : "Removed {$removed} duplicate keyword(s) across ".count($dupeGroups).' group(s).');

        return self::SUCCESS;
    }

    /** Order-insensitive fingerprint of a phrase. */
    protected function tokenSet(string $phrase): string
    {
        $words = preg_split('/\s+/', strtolower(trim(preg_replace('/[^a-z0-9\s]/i', ' ', $phrase))));
        $words = array_values(array_filter($words));
        sort($words);

        return implode(' ', $words);
    }
}
