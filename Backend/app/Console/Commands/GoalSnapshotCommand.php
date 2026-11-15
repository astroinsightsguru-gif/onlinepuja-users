<?php

namespace App\Console\Commands;

use App\Brain\TrafficTracker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GoalSnapshotCommand extends Command
{
    protected $signature = 'goal:snapshot';

    protected $description = 'Persist yesterday’s real traffic into every active goal and report pace';

    public function handle(\App\services\SearchConsoleService $gsc): int
    {
        $date = now()->subDay()->toDateString();

        // Prefer real Search Console clicks when connected; otherwise fall back
        // to the first-party pageview counter. Either way one number is stored.
        $value = null;
        $source = 'first-party';

        // Always prioritize real first-party pageviews from traffic_daily
        $dailyPageviews = TrafficTracker::take($date);

        if ($dailyPageviews > 0) {
            $value = $dailyPageviews;
            $source = 'traffic_daily (first-party pageviews)';
        } elseif ($gsc->isConfigured()) {
            $clicks = $gsc->clicksForDate($date);
            if ($clicks !== null && $clicks > 0) {
                $value = $clicks;
                $source = 'search-console';
            }
        }

        if ($value === null) {
            $value = 0;
        }

        $this->line("Traffic source: {$source}");

        $goals = DB::table('goals')->where('status', 'active')->get();

        if ($goals->isEmpty()) {
            $this->line('No active goals.');

            return self::SUCCESS;
        }

        foreach ($goals as $g) {
            DB::table('goal_snapshots')->updateOrInsert(
                ['goal_id' => $g->id, 'date' => $date],
                ['value' => $value, 'created_at' => now()]
            );

            $elapsed = max(1, now()->parse($g->start_date)->diffInDays(now()));
            $total = (int) DB::table('goal_snapshots')->where('goal_id', $g->id)->sum('value');
            $expected = round(($elapsed / max(1, now()->parse($g->start_date)->diffInDays($g->deadline) + 1)) * $g->target);
            $pace = $expected > 0 ? round($total / $expected * 100) : 100;

            $this->line(sprintf(
                '[%s] %s — actual %s vs expected %s (%d%% pace)',
                $date, $g->title, number_format($total), number_format($expected), $pace
            ));

            if ($pace < 60 && $elapsed >= 7) {
                $this->warn('  ⚠ Behind pace — Brain should escalate content velocity / distribution.');
            }
        }

        return self::SUCCESS;
    }
}
