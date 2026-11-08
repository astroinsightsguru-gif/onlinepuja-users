<?php

namespace App\Console\Commands;

use App\Brain\GoalAchievingEngine;
use App\Brain\KeywordIntelligenceEngine;
use App\Brain\TrafficForecaster;
use Illuminate\Console\Command;

class GrowthEngineCommand extends Command
{
    protected $signature = 'brain:growth
        {--action=all : Which sub-engine to execute: intel|forecast|achieve|all}
        {--days=60 : Days horizon for forecast or GSC lookback}
        {--remediate : Automatically execute corrective actions if goals are lagging}';

    protected $description = 'Run the Real Keyword Intelligence, Predictive Traffic Forecaster & Closed-Loop Goal Achieving Engine';

    public function handle(KeywordIntelligenceEngine $intel): int
    {
        $action = $this->option('action') ?: 'all';
        $days = (int) $this->option('days') ?: 60;
        $remediate = (bool) $this->option('remediate');

        $this->info("🕉️ ONLINEPUJA GROWTH & AUTONOMOUS REVENUE ENGINE");
        $this->line("Action: {$action} | Horizon: {$days} days");
        $this->line("-----------------------------------------------------------------");

        // 1. Keyword Intelligence
        if ($action === 'all' || $action === 'intel') {
            $this->info("\n[1/3] 🔍 Executing Real Keyword Intelligence Engine...");
            $res = $intel->run(28, 200);

            $this->table(['Metric', 'Count'], [
                ['GSC Queries Mined', $res['gsc_mined']],
                ['Striking Distance Keywords (Pos 6-20)', $res['striking_distance']],
                ['Missing Volume Enriched', $res['volume_enriched']],
                ['Vedic Festival Surges Applied', $res['festival_surges_applied']],
            ]);

            $striking = $intel->getStrikingDistanceKeywords(5);
            if (!empty($striking)) {
                $this->line("\n⭐ Top Striking-Distance Quick Wins:");
                foreach ($striking as $kw) {
                    $this->line(" • [Vol: {$kw->est_volume} | Diff: {$kw->difficulty}%] {$kw->phrase} ({$kw->cluster})");
                }
            }
        }

        // 2. Traffic Forecasting
        if ($action === 'all' || $action === 'forecast') {
            $this->info("\n[2/3] 📈 Generating Predictive Traffic Forecast ({$days} Days)...");
            $forecast = TrafficForecaster::forecast($days);

            $this->table(['Scenario', 'Cumulative Devotees', 'Projected Puja Orders', 'Projected Revenue'], [
                [
                    'Conservative (Current Baseline)',
                    number_format($forecast['projections']['conservative']['cumulative_visitors']),
                    number_format($forecast['projections']['conservative']['projected_puja_orders']),
                    '₹' . number_format($forecast['projections']['conservative']['projected_gross_revenue'])
                ],
                [
                    'Expected (Content Compounding)',
                    number_format($forecast['projections']['expected']['cumulative_visitors']),
                    number_format($forecast['projections']['expected']['projected_puja_orders']),
                    '₹' . number_format($forecast['projections']['expected']['projected_gross_revenue'])
                ],
                [
                    'Aggressive (Festival Surge + Reels)',
                    number_format($forecast['projections']['aggressive']['cumulative_visitors']),
                    number_format($forecast['projections']['aggressive']['projected_puja_orders']),
                    '₹' . number_format($forecast['projections']['aggressive']['projected_gross_revenue'])
                ],
            ]);
        }

        // 3. Goal Achieving
        if ($action === 'all' || $action === 'achieve') {
            $this->info("\n[3/3] 🎯 Evaluating Active Growth Goals & Closed-Loop Velocity...");
            $audit = GoalAchievingEngine::auditAndAchieve($remediate);

            if (empty($audit['audits'])) {
                $this->line("No active goals currently registered.");
            } else {
                foreach ($audit['audits'] as $g) {
                    $color = match ($g['health_status']) {
                        'ahead' => 'info',
                        'on_track' => 'info',
                        'lagging' => 'warn',
                        'critical' => 'error',
                        default => 'line',
                    };

                    $this->$color(sprintf(
                        "Goal #%d: '%s' | Status: %s | Pace: %s%% (Actual: %s / Target: %s)",
                        $g['goal_id'],
                        $g['title'],
                        strtoupper($g['health_status']),
                        $g['pace_percentage'],
                        number_format($g['actual_cumulative']),
                        number_format($g['target'])
                    ));

                    if (!empty($g['remediation_executed'])) {
                        $this->line(" ⚡ Closed-Loop Actions Executed:");
                        foreach ($g['remediation_executed'] as $act) {
                            $this->line("   - [{$act['action']}] {$act['detail']}");
                        }
                    }
                }
            }
        }

        $this->line("\n-----------------------------------------------------------------");
        $this->info("✅ Growth Engine execution completed successfully.");

        return self::SUCCESS;
    }
}
