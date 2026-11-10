<?php
$routesFile = '/home/onlinepujalive/htdocs/onlinepuja.live/routes/api.php';
$routesContent = file_get_contents($routesFile);

$oldRoute = "    Route::post('paperclip/emergency-halt', [\\App\\Http\\Controllers\\API\\Manager\\ManagerApiController::class, 'emergencyHaltSwarm']);";
$newRoute = "    Route::post('paperclip/emergency-halt', [\\App\\Http\\Controllers\\API\\Manager\\ManagerApiController::class, 'emergencyHaltSwarm']);\n    Route::get('email/overview', [\\App\\Http\\Controllers\\API\\Manager\\ManagerApiController::class, 'getEmailOverview']);\n    Route::post('email/sync', [\\App\\Http\\Controllers\\API\\Manager\\ManagerApiController::class, 'syncEmailContacts']);";

if (strpos($routesContent, $oldRoute) !== false && strpos($routesContent, 'email/overview') === false) {
    $routesContent = str_replace($oldRoute, $newRoute, $routesContent);
    file_put_contents($routesFile, $routesContent);
    echo "SUCCESS: Added email routes in api.php\n";
} else {
    echo "NOTE: email routes already present or not found.\n";
}

$ctrlFile = '/home/onlinepujalive/htdocs/onlinepuja.live/app/Http/Controllers/API/Manager/ManagerApiController.php';
$ctrlContent = file_get_contents($ctrlFile);

$targetBlock = '    public function getFinanceOverview(Request $request)';
$emailMethods = <<<'METHODS'
    public function getEmailOverview(Request $request)
    {
        try {
            $brevo = app(\App\Services\BrevoService::class);
            $isConfigured = $brevo->isConfigured();
            $sender = $brevo->sender();
            $lists = $isConfigured ? $brevo->lists() : [];
            $totalB2b = DB::table('b2b_leads')->whereNotNull('email')->where('email', '!=', '')->count();
            $totalDevoteesWithEmail = DB::table('users')->whereNotNull('email')->where('email', '!=', '')->count();

            return response()->json([
                'status' => 200,
                'success' => true,
                'data' => [
                    'provider' => 'Brevo (Sendinblue API v3)',
                    'is_configured' => $isConfigured,
                    'sender_email' => $sender['email'] ?? 'care@onlinepuja.live',
                    'sender_name' => $sender['name'] ?? 'Onlinepuja.live',
                    'lists' => $lists,
                    'available_devotee_emails' => $totalDevoteesWithEmail,
                    'available_b2b_emails' => $totalB2b,
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 500, 'error' => $e->getMessage()], 500);
        }
    }

    public function syncEmailContacts(Request $request)
    {
        try {
            $brevo = app(\App\Services\BrevoService::class);
            if (!$brevo->isConfigured()) {
                return response()->json(['success' => false, 'message' => 'Brevo API key not set in vault.'], 400);
            }

            $target = $request->input('target', 'devotees');
            $synced = 0;

            if ($target === 'b2b') {
                $leads = DB::table('b2b_leads')
                    ->whereNotNull('email')
                    ->where('email', '!=', '')
                    ->limit(50)
                    ->get();
                foreach ($leads as $l) {
                    $res = $brevo->subscribe($l->email, $l->contact_person ?: $l->business_name);
                    if ($res['ok'] || $res['already']) $synced++;
                }
            } else {
                $users = DB::table('users')
                    ->whereNotNull('email')
                    ->where('email', '!=', '')
                    ->limit(50)
                    ->get();
                foreach ($users as $u) {
                    $res = $brevo->subscribe($u->email, $u->name);
                    if ($res['ok'] || $res['already']) $synced++;
                }
            }

            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => "Successfully synchronized {$synced} contacts into Brevo.",
                'synced_count' => $synced,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 500, 'error' => $e->getMessage()], 500);
        }
    }

    public function getFinanceOverview(Request $request)
METHODS;

if (strpos($ctrlContent, 'public function getEmailOverview') === false && strpos($ctrlContent, $targetBlock) !== false) {
    $ctrlContent = str_replace($targetBlock, $emailMethods, $ctrlContent);
    file_put_contents($ctrlFile, $ctrlContent);
    echo "SUCCESS: Added email methods in ManagerApiController.php\n";
} else {
    echo "NOTE: email methods already present or target not found.\n";
}
