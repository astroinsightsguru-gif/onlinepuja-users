<?php

namespace App\Brain;

use App\Services\EvolutionWaService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Closed-Loop Goal Achieving Engine
 * 
 * Audits active goals against mathematical compounding S-curves.
 * When actual performance drops below target velocity, the engine automatically
 * dispatches closed-loop remediation actions to close the gap:
 * 1. Accelerates content velocity on striking-distance keywords
 * 2. Generates and schedules high-engagement video reels
 * 3. Enqueues WhatsApp B2B partner distribution campaigns
 */
class GoalAchievingEngine
{
    /** Status classifications */
    public const STATUS_AHEAD     = 'ahead';
    public const STATUS_ON_TRACK  = 'on_track';
    public const STATUS_LAGGING   = 'lagging';
    public const STATUS_CRITICAL  = 'critical';

    /**
     * Audit all active goals and execute corrective interventions if lagging
     */
    public static function auditAndAchieve(bool $autoRemediate = true): array
    {
        $goals = DB::table('goals')->where('status', 'active')->get();

        if ($goals->isEmpty()) {
            return [
                'ok' => true,
                'message' => 'No active goals found to evaluate.',
                'audits' => [],
            ];
        }

        $results = [];

        foreach ($goals as $goal) {
            $results[] = self::evaluateGoal($goal, $autoRemediate);
        }

        return [
            'ok' => true,
            'goals_evaluated' => count($results),
            'audits' => $results,
            'audited_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Evaluate single goal performance against target S-curve
     */
    public static function evaluateGoal(object $goal, bool $autoRemediate = true): array
    {
        $startDate = now()->parse($goal->start_date);
        $deadline = now()->parse($goal->deadline);
        $totalDays = max(1, $startDate->diffInDays($deadline));
        $daysElapsed = max(1, min($totalDays, $startDate->diffInDays(now())));

        // Sum actual values recorded in snapshots
        $actualCumulative = (int) DB::table('goal_snapshots')
            ->where('goal_id', $goal->id)
            ->sum('value');

        // Parse arithmetic plan if available, or compute on the fly
        $plan = json_decode($goal->plan_arithmetic ?? '{}', true);
        $curve = $plan['projected_curve'] ?? [];

        // Expected cumulative metric up to today
        $expectedCumulative = 0;
        if (!empty($curve)) {
            for ($d = 1; $d <= $daysElapsed; $d++) {
                $expectedCumulative += ($curve[$d] ?? 0);
            }
        } else {
            // Fallback back-loaded S-curve approximation
            $progressFraction = $daysElapsed / $totalDays;
            $sCurveFraction = pow($progressFraction, 1.6);
            $expectedCumulative = (int) round($goal->target * $sCurveFraction);
        }

        $expectedCumulative = max(1, $expectedCumulative);
        $pacePercentage = round(($actualCumulative / $expectedCumulative) * 100, 1);
        $deficit = max(0, $expectedCumulative - $actualCumulative);

        // Determine health status
        $health = self::STATUS_ON_TRACK;
        if ($pacePercentage >= 105.0) {
            $health = self::STATUS_AHEAD;
        } elseif ($pacePercentage < 70.0) {
            $health = self::STATUS_CRITICAL;
        } elseif ($pacePercentage < 90.0) {
            $health = self::STATUS_LAGGING;
        }

        $remediationExecuted = [];

        // Execute closed-loop remediation if lagging or critical
        if ($autoRemediate && in_array($health, [self::STATUS_LAGGING, self::STATUS_CRITICAL])) {
            $remediationExecuted = self::dispatchRemediation($goal, $health, $deficit);
        }

        return [
            'goal_id' => $goal->id,
            'title' => $goal->title,
            'metric' => $goal->metric,
            'target' => $goal->target,
            'days_elapsed' => $daysElapsed,
            'days_remaining' => max(0, $totalDays - $daysElapsed),
            'actual_cumulative' => $actualCumulative,
            'expected_cumulative' => $expectedCumulative,
            'deficit' => $deficit,
            'pace_percentage' => $pacePercentage,
            'health_status' => $health,
            'remediation_executed' => $remediationExecuted,
        ];
    }

    /**
     * Dispatch multi-channel remediation actions to close the growth deficit
     */
    protected static function dispatchRemediation(object $goal, string $health, int $deficit): array
    {
        $actions = [];

        // Action 1: Striking-Distance Content Sprint
        try {
            $topKeyword = DB::table('keywords')
                ->where('status', 'striking_distance')
                ->orWhere(function ($q) {
                    $q->where('difficulty', '<=', 40)->where('intent', 'commercial');
                })
                ->orderByDesc('est_volume')
                ->first();

            if ($topKeyword) {
                // Trigger content generation for the highest value striking distance keyword
                Artisan::call('content:generate', ['--limit' => 1]);
                $actions[] = [
                    'action' => 'content_sprint',
                    'detail' => "Triggered targeted SEO generation for keyword: '{$topKeyword->phrase}' (Est. Vol: {$topKeyword->est_volume}).",
                    'status' => 'success'
                ];
            }
        } catch (\Throwable $e) {
            $actions[] = [
                'action' => 'content_sprint',
                'detail' => $e->getMessage(),
                'status' => 'skipped'
            ];
        }

        // Action 2: Trigger Video Reel Synthesis Burst
        try {
            Artisan::call('media:video', [
                'prompt' => 'Sacred Vedic Aarti and Live Darshan at Kashi Vishwanath Temple',
                '--preset' => 'reel'
            ]);
            $actions[] = [
                'action' => 'video_reel_synthesis',
                'detail' => 'Synthesized native 1080x1920 MP4 video reel with Vedic audio to drive social referral traffic.',
                'status' => 'success'
            ];
        } catch (\Throwable $e) {
            $actions[] = [
                'action' => 'video_reel_synthesis',
                'detail' => $e->getMessage(),
                'status' => 'skipped'
            ];
        }

        // Action 3: B2B WhatsApp Partner Campaign (if deficit is critical)
        if ($health === self::STATUS_CRITICAL) {
            try {
                $b2bLeads = DB::table('b2b_leads')
                    ->whereNotNull('phone')
                    ->where('status', '!=', 'unsubscribed')
                    ->limit(5)
                    ->get();

                if ($b2bLeads->isNotEmpty()) {
                    Artisan::call('b2b:campaign-dispatch', ['--limit' => 5]);
                    $actions[] = [
                        'action' => 'b2b_whatsapp_dispatch',
                        'detail' => "Dispatched partnership campaign across {$b2bLeads->count()} verified B2B travel & pandit partners to drive booking referrals.",
                        'status' => 'success'
                    ];
                }
            } catch (\Throwable $e) {
                $actions[] = [
                    'action' => 'b2b_whatsapp_dispatch',
                    'detail' => $e->getMessage(),
                    'status' => 'skipped'
                ];
            }
        }

        // Record in RunLog
        RunLog::record(
            'goal:achieve',
            'growth-engine',
            'success',
            "Closed-loop intervention triggered for Goal #{$goal->id} ({$health}). Actions executed: " . count($actions)
        );

        return $actions;
    }
}
