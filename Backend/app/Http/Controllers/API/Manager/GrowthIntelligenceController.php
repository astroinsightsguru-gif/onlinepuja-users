<?php

namespace App\Http\Controllers\API\Manager;

use App\Brain\GoalAchievingEngine;
use App\Brain\KeywordIntelligenceEngine;
use App\Brain\TrafficForecaster;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrowthIntelligenceController extends Controller
{
    public function __construct(
        protected KeywordIntelligenceEngine $intel
    ) {}

    /**
     * Get real keyword intelligence and striking-distance opportunities
     */
    public function getKeywordIntel(Request $request): JsonResponse
    {
        $limit = max(10, min(100, (int) $request->query('limit', 25)));
        $cluster = $request->query('cluster');

        $query = DB::table('keywords')
            ->select('id', 'phrase', 'language', 'source', 'est_volume', 'difficulty', 'intent', 'cluster', 'status', 'updated_at')
            ->orderByDesc('est_volume');

        if ($cluster) {
            $query->where('cluster', $cluster);
        }

        $keywords = $query->limit($limit)->get();

        $strikingDistance = $this->intel->getStrikingDistanceKeywords(10);

        $summary = [
            'total_keywords' => DB::table('keywords')->count(),
            'striking_distance_count' => DB::table('keywords')->where('status', 'striking_distance')->count(),
            'total_monthly_volume' => (int) DB::table('keywords')->sum('est_volume'),
            'clusters' => DB::table('keywords')->select('cluster', DB::raw('count(*) as count'))->groupBy('cluster')->get(),
        ];

        return response()->json([
            'status' => 200,
            'summary' => $summary,
            'striking_distance' => $strikingDistance,
            'keywords' => $keywords,
        ]);
    }

    /**
     * Get multi-horizon predictive traffic forecast
     */
    public function getForecast(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 60);
        $forecast = TrafficForecaster::forecast($days);

        return response()->json([
            'status' => 200,
            'data' => $forecast,
        ]);
    }

    /**
     * Get live health status of all active growth goals
     */
    public function getGoalStatus(Request $request): JsonResponse
    {
        $audit = GoalAchievingEngine::auditAndAchieve(false);

        return response()->json([
            'status' => 200,
            'data' => $audit,
        ]);
    }

    /**
     * Manually trigger closed-loop remediation sprint for lagging goals
     */
    public function triggerCatchup(Request $request): JsonResponse
    {
        $audit = GoalAchievingEngine::auditAndAchieve(true);

        return response()->json([
            'status' => 200,
            'message' => 'Autonomous remediation sprint executed successfully.',
            'data' => $audit,
        ]);
    }
}
