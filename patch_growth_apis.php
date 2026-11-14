<?php
// Helper to append methods to ManagerApiController.php and routes/api.php safely

$controllerFile = '/home/onlinepujalive/htdocs/onlinepuja.live/app/Http/Controllers/API/Manager/ManagerApiController.php';
$routesFile = '/home/onlinepujalive/htdocs/onlinepuja.live/routes/api.php';

$codeToAppend = <<<'PHP'

    /**
     * Get real keyword intelligence telemetry, clusters, and striking-distance rankings
     */
    public function getKeywordIntelligence(\Illuminate\Http\Request $request)
    {
        try {
            $totalTracked = \Illuminate\Support\Facades\DB::table('keywords')->count();
            $strikingCount = \Illuminate\Support\Facades\DB::table('keywords')
                ->where('status', 'striking_distance')
                ->orWhere('difficulty', '<=', 35)
                ->count();
            $totalSearchVolume = (int) \Illuminate\Support\Facades\DB::table('keywords')->sum('est_volume');

            $topClusters = \Illuminate\Support\Facades\DB::table('keywords')
                ->select('cluster', \Illuminate\Support\Facades\DB::raw('count(*) as count'), \Illuminate\Support\Facades\DB::raw('sum(est_volume) as total_vol'))
                ->whereNotNull('cluster')
                ->where('cluster', '!=', '')
                ->groupBy('cluster')
                ->orderByDesc('total_vol')
                ->limit(12)
                ->get();

            $intentBreakdown = \Illuminate\Support\Facades\DB::table('keywords')
                ->select('intent', \Illuminate\Support\Facades\DB::raw('count(*) as count'), \Illuminate\Support\Facades\DB::raw('sum(est_volume) as total_vol'))
                ->whereNotNull('intent')
                ->groupBy('intent')
                ->get();

            $filter = $request->input('filter', 'all');
            $search = $request->input('search');

            $query = \Illuminate\Support\Facades\DB::table('keywords');
            if ($filter === 'striking') {
                $query->where('status', 'striking_distance');
            } elseif ($filter === 'commercial') {
                $query->where('intent', 'like', '%commercial%');
            } elseif ($filter === 'festival') {
                $query->whereIn('cluster', ['dev-deepawali-varanasi', 'chhath-puja', 'tulsi-vivah-ends-purnima', 'gita-jayanti', 'vaikuntha-chaturdashi']);
            }

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('phrase', 'like', "%{$search}%")
                      ->orWhere('cluster', 'like', "%{$search}%");
                });
            }

            $keywords = $query->orderByDesc('est_volume')
                ->limit(80)
                ->get();

            return response()->json([
                'ok' => true,
                'total_tracked' => $totalTracked,
                'striking_distance_count' => $strikingCount,
                'total_search_volume' => $totalSearchVolume,
                'top_clusters' => $topClusters,
                'intent_breakdown' => $intentBreakdown,
                'keywords' => $keywords,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get multi-scenario predictive traffic & revenue forecast (30, 60, 90 days)
     */
    public function getTrafficForecast(\Illuminate\Http\Request $request)
    {
        try {
            $days = (int) $request->input('days', 60);
            $forecast = \App\Brain\TrafficForecaster::forecast($days);

            return response()->json([
                'ok' => true,
                'forecast' => $forecast,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Run real Keyword Intelligence & SERP winnability enrichment
     */
    public function runKeywordIntelligence(\Illuminate\Http\Request $request)
    {
        try {
            $engine = app(\App\Brain\KeywordIntelligenceEngine::class);
            $report = $engine->run(28, 300);

            return response()->json([
                'ok' => true,
                'message' => 'Keyword intelligence refresh completed successfully.',
                'report' => $report,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Dispatch 1-tap WhatsApp broadcast to active devotee threads via Evolution API
     */
    public function dispatchWhatsappDevoteeBlast(\Illuminate\Http\Request $request)
    {
        try {
            $messageTemplate = $request->input('message');
            $mediaUrl = $request->input('media_url');
            $limit = (int) $request->input('limit', 25);

            if (empty($messageTemplate)) {
                return response()->json(['ok' => false, 'error' => 'Message template is required'], 422);
            }

            $threads = \Illuminate\Support\Facades\DB::table('whatsapp_threads')
                ->where('unread_count', '>=', 0)
                ->orderByDesc('last_message_at')
                ->limit($limit)
                ->get();

            if ($threads->isEmpty()) {
                return response()->json(['ok' => false, 'error' => 'No active WhatsApp devotee threads found'], 404);
            }

            $waService = app(\App\Services\EvolutionWaService::class);
            $sentCount = 0;
            $failedCount = 0;
            $logs = [];

            foreach ($threads as $th) {
                $recipientJid = $th->chat_jid;
                $devoteeName = $th->contact_name ?: 'भक्त';
                
                $text = str_replace(
                    ['{name}', '{devotee}', '{gotra}'],
                    [$devoteeName, $devoteeName, 'कश्यप'],
                    $messageTemplate
                );

                try {
                    $waService->sendPresence($recipientJid, 'composing', 2000);

                    if (!empty($mediaUrl)) {
                        $waService->sendMedia($recipientJid, $mediaUrl, $text);
                    } else {
                        $waService->sendMessage($recipientJid, $text);
                    }

                    \Illuminate\Support\Facades\DB::table('whatsapp_messages')->insert([
                        'chat_jid' => $recipientJid,
                        'message_id' => 'blast_' . uniqid(),
                        'from_me' => 1,
                        'sender_name' => 'OnlinePuja Master Brain',
                        'message_type' => !empty($mediaUrl) ? 'media' : 'text',
                        'content' => $text,
                        'media_url' => $mediaUrl,
                        'status' => 'SENT',
                        'sent_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $sentCount++;
                    $logs[] = "✓ Delivered to {$devoteeName} ({$recipientJid})";
                    usleep(1500000);
                } catch (\Throwable $e) {
                    $failedCount++;
                    $logs[] = "✗ Failed for {$recipientJid}: " . $e->getMessage();
                }
            }

            return response()->json([
                'ok' => true,
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'logs' => $logs,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }
PHP;

// 1. Patch Controller
$controllerContent = file_get_contents($controllerFile);
if (strpos($controllerContent, 'getKeywordIntelligence') === false) {
    // Find the last closing brace
    $lastBrace = strrpos($controllerContent, '}');
    if ($lastBrace !== false) {
        $controllerContent = substr($controllerContent, 0, $lastBrace) . "\n" . $codeToAppend . "\n}\n";
        file_put_contents($controllerFile, $controllerContent);
        echo "✓ ManagerApiController patched successfully.\n";
    }
} else {
    echo "• ManagerApiController already contains getKeywordIntelligence.\n";
}

// 2. Patch routes/api.php
$routesContent = file_get_contents($routesFile);
if (strpos($routesContent, 'growth/traffic-forecast') === false) {
    $searchTarget = "Route::post('growth/omnichannel-broadcast', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'omnichannelBroadcast']);";
    $replacement = $searchTarget . "\n    Route::get('growth/traffic-forecast', [\\App\\Http\\Controllers\\API\\Manager\\ManagerApiController::class, 'getTrafficForecast']);\n    Route::get('growth/keyword-intelligence', [\\App\\Http\\Controllers\\API\\Manager\\ManagerApiController::class, 'getKeywordIntelligence']);\n    Route::post('growth/run-keyword-intelligence', [\\App\\Http\\Controllers\\API\\Manager\\ManagerApiController::class, 'runKeywordIntelligence']);\n    Route::post('growth/whatsapp-devotee-blast', [\\App\\Http\\Controllers\\API\\Manager\\ManagerApiController::class, 'dispatchWhatsappDevoteeBlast']);";
    
    if (strpos($routesContent, $searchTarget) !== false) {
        $routesContent = str_replace($searchTarget, $replacement, $routesContent);
        file_put_contents($routesFile, $routesContent);
        echo "✓ routes/api.php patched with growth routes.\n";
    } else {
        echo "! Could not find growth/omnichannel-broadcast in routes/api.php.\n";
    }
} else {
    echo "• routes/api.php already contains growth/traffic-forecast.\n";
}
