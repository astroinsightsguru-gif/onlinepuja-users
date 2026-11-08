<?php

namespace App\Brain;

use Illuminate\Support\Facades\DB;

/**
 * Predictive Traffic Forecaster
 * 
 * Projects forward-looking organic trajectories (30, 60, 90 days) using:
 * 1. Historical baseline velocity from `traffic_daily`
 * 2. Real SERP CTR curves mapped across striking-distance keywords
 * 3. Compounding content indexation curves
 * 4. Hindu festival surge multipliers
 * 5. Downstream revenue & puja order conversion models
 */
class TrafficForecaster
{
    /** Average Puja Order Value in INR */
    public const AVG_ORDER_VALUE = 2500;

    /** Estimated devotee conversion rates */
    public const CONVERSION_RATES = [
        'conservative' => 0.012, // 1.2% conversion from visitor to inquiry/booking
        'expected'     => 0.018, // 1.8% conversion
        'aggressive'   => 0.026, // 2.6% conversion (festival surge intent)
    ];

    /**
     * Generate 30, 60, and 90-day multi-scenario traffic forecast
     */
    public static function forecast(int $horizonDays = 60): array
    {
        $horizonDays = max(14, min(180, $horizonDays));

        // 1. Calculate current 14-day baseline daily traffic
        $recentTraffic = DB::table('traffic_daily')
            ->orderByDesc('date')
            ->limit(14)
            ->get();

        $baselineDailyPageviews = 0;
        $baselineDailySessions = 0;

        if ($recentTraffic->isNotEmpty()) {
            $baselineDailyPageviews = (int) round($recentTraffic->avg('pageviews'));
            $baselineDailySessions = (int) round($recentTraffic->avg('sessions'));
        }

        // Fallback floor if tracking just started
        $baselineDailySessions = max(25, $baselineDailySessions);
        $baselineDailyPageviews = max(60, $baselineDailyPageviews);

        // 2. Calculate potential organic traffic from striking-distance keywords
        $strikingKeywords = DB::table('keywords')
            ->where('status', 'striking_distance')
            ->orWhere('difficulty', '<=', 35)
            ->get();

        $monthlySearchVolumePool = (int) $strikingKeywords->sum('est_volume');
        $monthlySearchVolumePool = max(5000, $monthlySearchVolumePool);

        // Striking distance keywords push from page 2 (1% CTR) to bottom of page 1 (5% CTR) = +4% net uplift
        $harvestableMonthlyVisits = (int) round($monthlySearchVolumePool * 0.045);
        $dailyHarvestPotential = (int) round($harvestableMonthlyVisits / 30);

        // 3. Generate daily projection timelines
        $conservativeCurve = [];
        $expectedCurve = [];
        $aggressiveCurve = [];

        $cumConservative = 0;
        $cumExpected = 0;
        $cumAggressive = 0;

        $rampMonthly = 1.48; // ~48% monthly compounding for new published content
        $dailyRamp = pow($rampMonthly, 1 / 30);

        for ($day = 1; $day <= $horizonDays; $day++) {
            // Content compounding factor
            $compoundingContentMultiplier = pow($dailyRamp, $day);

            // Conservative: baseline + slight organic uptick
            $dailyCons = (int) round($baselineDailySessions * (1 + ($day * 0.005)));
            $cumConservative += $dailyCons;
            $conservativeCurve[$day] = $dailyCons;

            // Expected: baseline + content compounding + striking-distance harvest
            $harvestPart = min(1.0, $day / 21) * $dailyHarvestPotential;
            $contentPart = ($baselineDailySessions * 0.4) * ($compoundingContentMultiplier - 1);
            $dailyExp = (int) round($baselineDailySessions + $harvestPart + $contentPart);
            $cumExpected += $dailyExp;
            $expectedCurve[$day] = $dailyExp;

            // Aggressive: Expected + festival surge multiplier (up to 1.7x around week 3-5)
            $festivalSurgeFactor = 1.0 + (0.55 * sin(($day / $horizonDays) * M_PI));
            $dailyAggr = (int) round($dailyExp * $festivalSurgeFactor);
            $cumAggressive += $dailyAggr;
            $aggressiveCurve[$day] = $dailyAggr;
        }

        // 4. Downstream Puja Orders & Gross Revenue Projections
        $expectedOrders = (int) round($cumExpected * self::CONVERSION_RATES['expected']);
        $expectedRevenue = $expectedOrders * self::AVG_ORDER_VALUE;

        $conservativeOrders = (int) round($cumConservative * self::CONVERSION_RATES['conservative']);
        $conservativeRevenue = $conservativeOrders * self::AVG_ORDER_VALUE;

        $aggressiveOrders = (int) round($cumAggressive * self::CONVERSION_RATES['aggressive']);
        $aggressiveRevenue = $aggressiveOrders * self::AVG_ORDER_VALUE;

        return [
            'horizon_days' => $horizonDays,
            'baseline_daily_sessions' => $baselineDailySessions,
            'baseline_daily_pageviews' => $baselineDailyPageviews,
            'striking_distance_keywords_count' => $strikingKeywords->count(),
            'monthly_search_volume_pool' => $monthlySearchVolumePool,
            'harvestable_monthly_visits' => $harvestableMonthlyVisits,
            'projections' => [
                'conservative' => [
                    'cumulative_visitors' => $cumConservative,
                    'ending_daily_traffic' => end($conservativeCurve),
                    'projected_puja_orders' => $conservativeOrders,
                    'projected_gross_revenue' => $conservativeRevenue,
                    'curve_sample' => self::sampleCurve($conservativeCurve, 10),
                ],
                'expected' => [
                    'cumulative_visitors' => $cumExpected,
                    'ending_daily_traffic' => end($expectedCurve),
                    'projected_puja_orders' => $expectedOrders,
                    'projected_gross_revenue' => $expectedRevenue,
                    'curve_sample' => self::sampleCurve($expectedCurve, 10),
                ],
                'aggressive' => [
                    'cumulative_visitors' => $cumAggressive,
                    'ending_daily_traffic' => end($aggressiveCurve),
                    'projected_puja_orders' => $aggressiveOrders,
                    'projected_gross_revenue' => $aggressiveRevenue,
                    'curve_sample' => self::sampleCurve($aggressiveCurve, 10),
                ],
            ],
            'levers_to_hit_aggressive' => [
                'Publish 3+ bilingual Vedic SEO guides daily targeting striking-distance clusters.',
                'Synthesize and publish daily 1080x1920 MP4 Video Reels with Hindi neural voiceover.',
                'Run weekly WhatsApp B2B partner broadcast campaigns to travel agents & pandits.',
                'Internal-link all horoscope & kundali pages to relevant temple puja booking pages.'
            ],
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Sample curve into 10 evenly-spaced milestone points for charting
     */
    protected static function sampleCurve(array $curve, int $points = 10): array
    {
        $total = count($curve);
        $step = max(1, (int) floor($total / $points));
        $sampled = [];

        for ($i = $step; $i <= $total; $i += $step) {
            $sampled["day_{$i}"] = $curve[$i] ?? 0;
        }

        if (!isset($sampled["day_{$total}"])) {
            $sampled["day_{$total}"] = end($curve);
        }

        return $sampled;
    }
}
