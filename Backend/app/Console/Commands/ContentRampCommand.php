<?php

namespace App\Console\Commands;

use App\Brain\RunLog;
use App\Brain\Vault;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Advance `content_daily_target` on a fixed calendar — but only when the
 * keyword pool actually has the runway for the next step. This exists because
 * of the answer to a direct question: raise blogging from 3/day toward 10/day,
 * but staged (3→5→10) rather than in one jump, because this domain is young
 * (21 articles at the time this was built), has no GSC visibility yet, and
 * jumping straight to volume is exactly the pattern Google's Scaled Content
 * Abuse policy targets — independent of per-article quality.
 *
 * The schedule is deliberately a calendar (day offsets from a start date), not
 * "raise it once headroom looks fine" — a pure headroom-triggered ramp would
 * climb as fast as `seo:research` happens to produce keywords, which defeats
 * the entire point of pacing this by observed real-world signal (indexing,
 * rankings, error rate) rather than by how much content the pipe can
 * physically produce. The calendar sets the ceiling; headroom can only ever
 * hold a step back, never pull one forward.
 *
 * Runs once daily, ahead of content:generate reading the same vault key.
 */
class ContentRampCommand extends Command
{
    protected $signature = 'content:ramp {--force : Advance the stage even if headroom looks thin}';

    protected $description = 'Advance content_daily_target on its configured calendar, gated by keyword headroom';

    /**
     * day-offset => target. First entry is the floor even before day 0 has
     * passed (covers a late first run). Stored in code, not the vault — this
     * is a one-time rollout plan, not an ongoing tunable; change it here if
     * the plan changes.
     */
    protected const STAGES = [
        0 => 5,
        14 => 7,
        28 => 10,
    ];

    /** Keyword-pool days of runway required before advancing a stage. */
    protected const MIN_HEADROOM_DAYS = 21;

    public function handle(): int
    {
        $runId = RunLog::start('content:ramp', 'content');

        $startedAt = Vault::get('content_ramp_started_at');

        if ($startedAt === '') {
            Vault::set('content_ramp_started_at', now()->toDateString(), false);
            $startedAt = now()->toDateString();
            $this->info("Ramp started today ({$startedAt}).");
        }

        try {
            $parsedStart = \Carbon\Carbon::parse($startedAt)->startOfDay();
        } catch (\Throwable $e) {
            $parsedStart = now()->startOfDay();
            Vault::set('content_ramp_started_at', now()->toDateString(), false);
        }
        $daysElapsed = now()->startOfDay()->diffInDays($parsedStart);
        $current = max(1, (int) Vault::get('content_daily_target', '3'));

        $due = 1;

        foreach (self::STAGES as $dayOffset => $target) {
            if ($daysElapsed >= $dayOffset) {
                $due = $target;
            }
        }

        if ($due <= $current) {
            $this->info("No stage change due. day {$daysElapsed}, target stays at {$current}.");
            RunLog::finish($runId, 'ok', "No change (day {$daysElapsed}, target={$current})", [], 0, 0);

            return self::SUCCESS;
        }

        $headroomDays = $this->headroomDays($due);

        if ($headroomDays < self::MIN_HEADROOM_DAYS && ! $this->option('force')) {
            $this->warn("Stage {$due}/day is due (day {$daysElapsed}) but keyword headroom is only {$headroomDays} day(s) — holding at {$current}/day.");

            RunLog::finish(
                $runId,
                'partial',
                "Held at {$current}/day — stage {$due} due but headroom only {$headroomDays}d (need ".self::MIN_HEADROOM_DAYS.'d)',
                ['day' => $daysElapsed, 'due_target' => $due, 'held_at' => $current, 'headroom_days' => $headroomDays],
                0,
                0
            );

            return self::SUCCESS;
        }

        Vault::set('content_daily_target', (string) $due, false);
        $this->info("Advanced content_daily_target: {$current} -> {$due} (day {$daysElapsed}, headroom {$headroomDays}d).");

        RunLog::finish(
            $runId,
            'ok',
            "Advanced {$current}/day -> {$due}/day (day {$daysElapsed})",
            ['day' => $daysElapsed, 'from' => $current, 'to' => $due, 'headroom_days' => $headroomDays],
            1,
            0
        );

        return self::SUCCESS;
    }

    /** Same calc as BrainDoctorCommand::checkKeywordHeadroom, against a candidate target. */
    protected function headroomDays(int $candidateTarget): int
    {
        $pool = DB::table('keywords')->where('status', 'new')->where('difficulty', '<=', 60)->count();
        $clusters = DB::table('keywords')->where('status', 'new')->where('difficulty', '<=', 60)
            ->distinct('cluster')->count('cluster');

        return min(intdiv($pool, $candidateTarget), intdiv($clusters, $candidateTarget));
    }
}
