<?php

namespace App\Console\Commands;

use App\Brain\BusinessReadiness;
use App\Brain\RunLog;
use App\Brain\TrafficTracker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Record the real business funnel into `outcomes`, once a day.
 *
 * `outcomes` already held social reach and likes, so the Learner could compare
 * two Instagram captions — but nothing in the system could compare an article
 * to an order, because orders were never written anywhere the engine could see
 * them. That is why a "100,000 visitors" goal survived for weeks next to a
 * catalogue with nothing bookable in it: the only number on the dashboard was
 * the one that could not tell success from motion.
 *
 * These rows are the denominator the growth engine was missing. Traffic is a
 * cost until it becomes an order, and now both sit in the same table on the
 * same footing, so a planner can eventually rank a channel by money instead of
 * by volume.
 *
 * Idempotent: re-running for the same date overwrites that date's rows rather
 * than double-counting.
 */
class BusinessSnapshotCommand extends Command
{
    protected $signature = 'business:snapshot {--date= : YYYY-MM-DD, defaults to yesterday}';

    protected $description = 'Record the daily business funnel (traffic -> orders -> revenue) into outcomes';

    public function handle(BusinessReadiness $readiness): int
    {
        $date = $this->option('date') ?: now()->subDay()->toDateString();
        $runId = RunLog::start('business:snapshot', 'business');

        $orders = $this->ordersFor($date);
        $assessment = $readiness->assess();

        $metrics = [
            'pageviews' => TrafficTracker::pageviews($date),
            'sessions' => TrafficTracker::sessions($date),
            'orders' => $orders['count'],
            'orders_paid' => $orders['paid'],
            'revenue' => (int) round($orders['revenue']),
            'bookable_pujas' => $assessment['bookable'],
            'readiness_score' => $assessment['score'],
        ];

        foreach ($metrics as $metric => $value) {
            DB::table('outcomes')->updateOrInsert(
                [
                    'channel' => 'business',
                    'entity_type' => 'site',
                    'entity_id' => 0,
                    'metric' => $metric,
                    'at' => $date.' 00:00:00',
                ],
                [
                    'value' => $value,
                    'meta' => json_encode(['date' => $date, 'source' => 'business:snapshot']),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        // Conversion rate is the number that makes traffic meaningful, and it
        // is the one an operator will look for first.
        $cvr = $metrics['sessions'] > 0
            ? round($metrics['orders'] / $metrics['sessions'] * 100, 3)
            : 0.0;

        $summary = sprintf(
            '%s — %d sessions, %d orders (%.3f%%), revenue %d, %d bookable pujas',
            $date, $metrics['sessions'], $metrics['orders'], $cvr, $metrics['revenue'], $metrics['bookable_pujas']
        );

        $this->line($summary);

        if ($metrics['bookable_pujas'] === 0 && $metrics['sessions'] > 0) {
            $this->warn(sprintf(
                'Every one of those %d sessions arrived at a catalogue with nothing bookable. That is the entire conversion problem.',
                $metrics['sessions']
            ));
        }

        RunLog::finish($runId, 'ok', $summary, $metrics, count($metrics), 0);

        return self::SUCCESS;
    }

    /**
     * Orders placed on a date. `puja_orders` is the real booking table;
     * `user_orders` is the astromall product side and carries no price of its
     * own, so it counts toward volume only.
     *
     * @return array{count:int,paid:float,revenue:float}
     */
    protected function ordersFor(string $date): array
    {
        $out = ['count' => 0, 'paid' => 0, 'revenue' => 0.0];

        try {
            $rows = DB::table('puja_orders')->whereDate('created_at', $date)->get(['order_total_price', 'puja_order_status']);

            $out['count'] = $rows->count();
            $out['paid'] = $rows->whereIn('puja_order_status', ['placed', 'ongoing', 'completed'])->count();
            $out['revenue'] = (float) $rows
                ->whereIn('puja_order_status', ['placed', 'ongoing', 'completed'])
                ->sum('order_total_price');
        } catch (\Throwable $e) {
            $this->warn('puja_orders unreadable: '.$e->getMessage());
        }

        try {
            $out['count'] += DB::table('user_orders')
                ->whereDate('created_at', $date)
                ->where('isDelete', 0)
                ->count();
        } catch (\Throwable $e) {
            // Optional table; volume only.
        }

        return $out;
    }
}
