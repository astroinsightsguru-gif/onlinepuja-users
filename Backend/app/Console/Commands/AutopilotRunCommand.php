<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Brain\Vault;

class AutopilotRunCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'autopilot:run {--loop=all : Specific loop to run (gochar, panchang, festival, twin, churn, all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute autonomous growth & astrology loops (Gochar, Panchang, Festival, Twin, Churn)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = microtime(true);
        $loop = $this->option('loop') ?: 'all';
        $mode = (string) (Vault::get('autopilot_mode') ?: 'supervised');
        $confidenceFloor = (int) (Vault::get('autopilot_confidence_floor') ?: 85);
        $autoDispatch = ($mode === 'full');

        $this->info("⚡ Running Autopilot Loop: [{$loop}] (Mode: {$mode}, Confidence Floor: {$confidenceFloor}%)");

        $itemsProcessed = 0;

        if ($loop === 'all' || $loop === 'gochar') {
            $itemsProcessed += $this->runGocharLoop($autoDispatch, $confidenceFloor);
        }

        if ($loop === 'all' || $loop === 'panchang') {
            $itemsProcessed += $this->runPanchangLoop($autoDispatch, $confidenceFloor);
        }

        if ($loop === 'all' || $loop === 'festival') {
            $itemsProcessed += $this->runFestivalLoop($autoDispatch, $confidenceFloor);
        }

        if ($loop === 'all' || $loop === 'twin') {
            $itemsProcessed += $this->runTwinLoop($autoDispatch, $confidenceFloor);
        }

        if ($loop === 'all' || $loop === 'churn') {
            $itemsProcessed += $this->runChurnLoop($autoDispatch, $confidenceFloor);
        }

        $durationMs = (int) round((microtime(true) - $startTime) * 1000);

        // Record execution in brain_run_log
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('brain_run_log')) {
                DB::table('brain_run_log')->insert([
                    'task' => "autopilot_{$loop}",
                    'category' => 'autopilot',
                    'status' => 'completed',
                    'summary' => "Autopilot loop '{$loop}' ran successfully. {$itemsProcessed} item(s) processed.",
                    'detail' => json_encode(['loop' => $loop, 'mode' => $mode, 'items' => $itemsProcessed, 'floor' => $confidenceFloor]),
                    'items_ok' => $itemsProcessed,
                    'items_failed' => 0,
                    'duration_ms' => $durationMs,
                    'trigger' => php_sapi_name() === 'cli' ? 'cli' : 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning("Could not write to brain_run_log: " . $e->getMessage());
        }

        $this->info("✓ Autopilot run complete! Staged/Processed: {$itemsProcessed} items in {$durationMs}ms.");
        return 0;
    }

    /**
     * 1. Planetary Gochar Drip Loop
     */
    protected function runGocharLoop(bool $autoDispatch, int $confidenceFloor): int
    {
        if (Vault::get('loop_gochar_enabled', '1') !== '1') {
            $this->line("  - Skipping Gochar Loop (Disabled in settings)");
            return 0;
        }

        $today = Carbon::now('Asia/Kolkata')->format('Y-m-d');
        // Prevent duplicate queue for today
        $already = DB::table('autopilot_queue')
            ->where('type', 'gochar')
            ->whereDate('created_at', $today)
            ->exists();

        if ($already) {
            $this->line("  - Gochar alerts already generated for today.");
            return 0;
        }

        $transits = [
            'मेष' => 'सूर्य व गुरु का अनुकूल प्रभाव, कार्यक्षेत्र में उन्नति के योग।',
            'वृषभ' => 'शुक्र का शुभ प्रभाव, पारिवारिक जीवन में सुख-शांति एवं धन लाभ।',
            'मिथुन' => 'बुध का संचार, व्यापार एवं बौद्धिक कार्यों में सफलता।',
            'कर्क' => 'चंद्रमा का शुभ गोचर, मानसिक शांति एवं आत्मबल में वृद्धि।',
            'सिंह' => 'सूर्य का प्रभाव, आत्मविश्वास एवं सामाजिक प्रतिष्ठा में वृद्धि।',
            'कन्या' => 'बुध व गुरु की कृपा, नए अवसर एवं कार्य सिद्धि।',
            'तुला' => 'शुक्र का गोचर, कला, संगीत व व्यापार में शुभ परिणाम।',
            'वृश्चिक' => 'मंगल का प्रभाव, ऊर्जावान रहेंगे, निर्णय क्षमता मजबूत होगी।',
            'धनु' => 'गुरु की कृपा, धार्मिक यात्रा एवं आध्यात्मिक प्रगति के योग।',
            'मकर' => 'शनि का गोचर, धैर्य एवं परिश्रम से बड़े कार्य सिद्ध होंगे।',
            'कुंभ' => 'शनि व राहु का प्रभाव, नए प्रोजेक्ट्स में संभलकर आगे बढ़ें।',
            'मीन' => 'गुरु का शुभ संचार, ज्ञान, संतान एवं धन में शुभ वृद्धि।'
        ];

        $randomRashi = array_rand($transits);
        $rashiGuidance = $transits[$randomRashi];

        $score = 93;
        $status = ($autoDispatch && $score >= $confidenceFloor) ? 'sent' : 'pending';

        DB::table('autopilot_queue')->insert([
            'type' => 'gochar',
            'recipient_id' => null,
            'recipient_type' => 'broadcast',
            'title' => "🪐 आज का विशेष गोचर फल • {$randomRashi} राशि",
            'message' => "ग्रहों का विशेष संरेखण: {$rashiGuidance}\nग्रह शांति एवं शुभ फल हेतु काशी विश्वनाथ रुद्राभिषेक पूजा संकल्प लें।",
            'meta_data' => json_encode(['rashi' => $randomRashi, 'transit' => $rashiGuidance]),
            'status' => $status,
            'confidence_score' => $score,
            'channel' => 'push',
            'approved_by' => $autoDispatch ? 1 : null,
            'sent_at' => $status === 'sent' ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->line("  ✓ Generated Planetary Gochar notification [{$randomRashi}]. Status: {$status}");
        return 1;
    }

    /**
     * 2. Vedic Panchang & Muhurat Loop
     */
    protected function runPanchangLoop(bool $autoDispatch, int $confidenceFloor): int
    {
        if (Vault::get('loop_panchang_enabled', '1') !== '1') {
            $this->line("  - Skipping Panchang Loop (Disabled in settings)");
            return 0;
        }

        $now = Carbon::now('Asia/Kolkata');
        $today = $now->format('Y-m-d');

        $already = DB::table('autopilot_queue')
            ->where('type', 'panchang')
            ->whereDate('created_at', $today)
            ->exists();

        if ($already) {
            $this->line("  - Daily Panchang alert already queued for today.");
            return 0;
        }

        $dateStr = $now->translatedFormat('d F Y, l');
        $abhijit = '11:42 AM - 12:28 PM';
        $rahuKaal = '01:30 PM - 03:00 PM';

        $score = 97;
        $status = ($autoDispatch && $score >= $confidenceFloor) ? 'sent' : 'pending';

        DB::table('autopilot_queue')->insert([
            'type' => 'panchang',
            'recipient_id' => null,
            'recipient_type' => 'broadcast',
            'title' => "🕉️ आज का वैदिक पंचांग एवं शुभ मुहूर्त ({$dateStr})",
            'message' => "शुभ अभिजित मुहूर्त: {$abhijit} • राहुकाल: {$rahuKaal}। आज के शुभ मुहूर्त में वैदिक पंडितों द्वारा संकल्प पूजा बुक करें।",
            'meta_data' => json_encode(['abhijit' => $abhijit, 'rahuKaal' => $rahuKaal, 'date' => $dateStr]),
            'status' => $status,
            'confidence_score' => $score,
            'channel' => 'push',
            'approved_by' => $autoDispatch ? 1 : null,
            'sent_at' => $status === 'sent' ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->line("  ✓ Generated Vedic Panchang broadcast. Status: {$status}");
        return 1;
    }

    /**
     * 3. Hindu Festival Puja Drips Loop
     */
    protected function runFestivalLoop(bool $autoDispatch, int $confidenceFloor): int
    {
        if (Vault::get('loop_festival_enabled', '1') !== '1') {
            $this->line("  - Skipping Festival Loop (Disabled in settings)");
            return 0;
        }

        $startDate = Carbon::today()->format('Y-m-d');
        $endDate = Carbon::today()->addDays(14)->format('Y-m-d');

        $upcomingFest = DB::table('hindu_festivals')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->first();

        if (!$upcomingFest) {
            $this->line("  - No festivals within next 14 days to drip.");
            return 0;
        }

        $already = DB::table('autopilot_queue')
            ->where('type', 'festival')
            ->whereDate('created_at', Carbon::today()->format('Y-m-d'))
            ->exists();

        if ($already) {
            $this->line("  - Festival drip already queued for today.");
            return 0;
        }

        $festDate = Carbon::parse($upcomingFest->date)->format('d M Y');
        $daysLeft = Carbon::today()->diffInDays(Carbon::parse($upcomingFest->date));
        $timingText = ($daysLeft === 0) ? 'आज ही' : "आगामी {$festDate} ({$daysLeft} दिन शेष)";

        $score = 95;
        $status = ($autoDispatch && $score >= $confidenceFloor) ? 'sent' : 'pending';

        DB::table('autopilot_queue')->insert([
            'type' => 'festival',
            'recipient_id' => null,
            'recipient_type' => 'broadcast',
            'title' => "🪔 {$upcomingFest->name} — विशेष गोत्र संकल्प पूजा ({$timingText})",
            'message' => "{$upcomingFest->name} के पावन अवसर पर काशी के विद्वान आचार्यों द्वारा अपने नाम एवं गोत्र से विशेष पूजन संकल्प बुक करें। सीमित यजमान स्लॉट उपलब्ध!",
            'meta_data' => json_encode(['festival' => $upcomingFest->name, 'date' => $upcomingFest->date, 'deity' => $upcomingFest->deity ?? 'Vedic Devata']),
            'status' => $status,
            'confidence_score' => $score,
            'channel' => 'whatsapp',
            'approved_by' => $autoDispatch ? 1 : null,
            'sent_at' => $status === 'sent' ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->line("  ✓ Queued Festival Puja Drip for [{$upcomingFest->name}]. Status: {$status}");
        return 1;
    }

    /**
     * 4. AI Astrologer Twin Fallback Loop
     */
    protected function runTwinLoop(bool $autoDispatch, int $confidenceFloor): int
    {
        if (Vault::get('loop_twin_enabled', '1') !== '1') {
            $this->line("  - Skipping AI Twin Loop (Disabled in settings)");
            return 0;
        }

        // Check for unresolved call requests
        $missedCalls = DB::table('callrequest')
            ->whereIn('callStatus', ['Pending', 'Reject', 'Missed'])
            ->where('created_at', '>=', now()->subDays(2))
            ->limit(3)
            ->get();

        $count = 0;
        foreach ($missedCalls as $call) {
            $already = DB::table('autopilot_queue')
                ->where('type', 'twin')
                ->where('recipient_id', $call->userId)
                ->whereDate('created_at', now()->format('Y-m-d'))
                ->exists();

            if ($already) continue;

            $score = 88;
            $status = ($autoDispatch && $score >= $confidenceFloor) ? 'sent' : 'pending';

            DB::table('autopilot_queue')->insert([
                'type' => 'twin',
                'recipient_id' => $call->userId,
                'recipient_type' => 'user',
                'title' => '🤖 AI ज्योतिषी सहायता — आपकी परामर्श प्रतीक्षा',
                'message' => 'प्रणाम! ज्योतिषी व्यस्त होने के कारण आपकी कॉल कनेक्ट नहीं हो सकी। हमारे AI ज्योतिषी से अपनी कुंडली का त्वरित मार्गदर्शन अभी प्राप्त करें।',
                'meta_data' => json_encode(['callId' => $call->id, 'astrologerId' => $call->astrologerId]),
                'status' => $status,
                'confidence_score' => $score,
                'channel' => 'push',
                'approved_by' => $autoDispatch ? 1 : null,
                'sent_at' => $status === 'sent' ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $count++;
        }

        $this->line("  ✓ Processed {$count} AI Twin fallback notifications.");
        return $count;
    }

    /**
     * 5. Devotee Churn Revival Loop
     */
    protected function runChurnLoop(bool $autoDispatch, int $confidenceFloor): int
    {
        if (Vault::get('loop_churn_enabled', '1') !== '1') {
            $this->line("  - Skipping Churn Revival Loop (Disabled in settings)");
            return 0;
        }

        // Find devotees who placed bookings in the past but haven't engaged in > 30 days
        $dormantUsers = DB::table('users')
            ->where('isActive', 1)
            ->where('created_at', '<=', now()->subDays(30))
            ->whereNotIn('id', function($q) {
                $q->select('userId')->from('payment')->where('paymentStatus', 'Success')->where('created_at', '>=', now()->subDays(30));
            })
            ->limit(2)
            ->get();

        $count = 0;
        foreach ($dormantUsers as $user) {
            $already = DB::table('autopilot_queue')
                ->where('type', 'churn')
                ->where('recipient_id', $user->id)
                ->whereDate('created_at', now()->format('Y-m-d'))
                ->exists();

            if ($already) continue;

            $score = 90;
            $status = ($autoDispatch && $score >= $confidenceFloor) ? 'sent' : 'pending';

            DB::table('autopilot_queue')->insert([
                'type' => 'churn',
                'recipient_id' => $user->id,
                'recipient_type' => 'user',
                'title' => "✨ " . ($user->name ?: 'प्रिय भक्त') . ", काशी विश्वनाथ का विशेष आशीर्वाद",
                'message' => "प्रणाम! OnlinePuja परिवार आपकी कुशलता की कामना करता है। आपके एवं परिवार के कल्याण हेतु आज का निःशुल्क दैनिक राशिफल व विशेष पूजा संकल्प देखें।",
                'meta_data' => json_encode(['userId' => $user->id, 'userPhone' => $user->contactNo ?? '']),
                'status' => $status,
                'confidence_score' => $score,
                'channel' => 'whatsapp',
                'approved_by' => $autoDispatch ? 1 : null,
                'sent_at' => $status === 'sent' ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $count++;
        }

        $this->line("  ✓ Staged {$count} devotee churn revival notifications.");
        return $count;
    }
}
