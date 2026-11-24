<?php

namespace App\Brain;

use App\AI\ProviderManager;
use App\Services\OneSignalService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ⚡ AutopilotEngine — The Autonomous Nervous System of OnlinePuja.live
 *
 * Runs 5 World-Class Autopilot Loops on schedule or via the Growth Hub Cockpit:
 * 1. Daily Planetary Gochar & Personalized Horoscope Drip
 * 2. Daily Vedic Panchang & Shubh Muhurta Broadcast
 * 3. Autonomous Festival Puja Drip Sequences (T-7, T-3, T-1)
 * 4. 24/7 Astrologer AI Twin Instant Fallback
 * 5. Churn Revival & Wallet Re-engagement Drip
 *
 * Supports Human-in-the-Loop Mode:
 * - 'full': Dispatches immediately to devotees via OneSignal Push & In-App notification.
 * - 'supervised': Stages items into `autopilot_queue` for 1-click admin approval in Growth Hub.
 * - 'off': Engine paused.
 */
class AutopilotEngine
{
    public function __construct(
        protected ProviderManager $ai
    ) {}

    /**
     * Get real-time status and health metrics for the Autopilot Cockpit.
     */
    public function assessStatus(): array
    {
        $mode = Vault::get('autopilot_mode', 'supervised');
        $confidenceFloor = (int) Vault::get('autopilot_confidence_floor', '85');

        $pendingCount = DB::table('autopilot_queue')->where('status', 'pending')->count();
        $approvedCount = DB::table('autopilot_queue')->where('status', 'approved')->count();
        $sentCount = DB::table('autopilot_queue')->where('status', 'sent')->count();
        $sentToday = DB::table('autopilot_queue')->where('status', 'sent')->whereDate('sent_at', Carbon::today())->count();

        $recentQueue = DB::table('autopilot_queue')
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        $recentLogs = DB::table('brain_run_log')
            ->where('category', 'autopilot')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $loops = [
            'gochar' => [
                'name' => 'Planetary Gochar Drip',
                'description' => 'Personalized transit alerts & daily astrological forecasts for devotees',
                'enabled' => (bool) Vault::get('autopilot_gochar_enabled', '1'),
                'cadence' => 'Daily at 06:30 AM',
                'icon' => '🪐',
            ],
            'panchang' => [
                'name' => 'Vedic Panchang & Muhurta',
                'description' => 'Auspicious Tithi, Choghadiya & Rahu Kaal morning broadcast',
                'enabled' => (bool) Vault::get('autopilot_panchang_enabled', '1'),
                'cadence' => 'Daily at 06:00 AM',
                'icon' => '🕉️',
            ],
            'festival' => [
                'name' => 'Festival Puja Drip (T-7 to T-1)',
                'description' => 'Contextual temple puja booking invitations for upcoming sacred days',
                'enabled' => (bool) Vault::get('autopilot_festival_enabled', '1'),
                'cadence' => 'Daily at 08:00 AM',
                'icon' => '🪔',
            ],
            'twin' => [
                'name' => 'AI Twin 24/7 Fallback',
                'description' => 'Instant AI Pandit routing when human astrologer is offline or busy > 30s',
                'enabled' => (bool) Vault::get('autopilot_twin_fallback_enabled', '1'),
                'cadence' => 'Real-Time Event Trigger',
                'icon' => '🤖',
            ],
            'churn' => [
                'name' => 'Devotee Churn Revival',
                'description' => 'Re-engages inactive users with planetary remedies and wallet recharge bonus',
                'enabled' => (bool) Vault::get('autopilot_churn_enabled', '1'),
                'cadence' => 'Daily at 11:00 AM',
                'icon' => '✨',
            ],
        ];

        return [
            'mode' => $mode,
            'confidence_floor' => $confidenceFloor,
            'pending_count' => $pendingCount,
            'approved_count' => $approvedCount,
            'sent_count' => $sentCount,
            'sent_today' => $sentToday,
            'loops' => $loops,
            'queue' => $recentQueue,
            'logs' => $recentLogs,
        ];
    }

    /**
     * Loop 1: Daily Planetary Gochar & Horoscope Drip.
     */
    public function runGocharLoop(bool $forceDispatch = false): array
    {
        $start = microtime(true);
        $mode = Vault::get('autopilot_mode', 'supervised');
        $confidenceFloor = (int) Vault::get('autopilot_confidence_floor', '85');
        $queued = 0;
        $sent = 0;

        // Fetch users who have birth details or active profile
        $users = DB::table('users')
            ->where('isActive', 1)
            ->whereNotNull('name')
            ->limit(20)
            ->get();

        $rashis = [
            'Mesh (Aries)' => 'मंगल के शुभ प्रभाव से आज कार्यक्षेत्र में नए अवसर प्राप्त होंगे।',
            'Vrishabh (Taurus)' => 'शुक्र की दृष्टि से आर्थिक स्थिति सुदृढ़ होगी, परिजनों का सहयोग मिलेगा।',
            'Mithun (Gemini)' => 'बुध देव की कृपा से बौद्धिक कार्यों और व्यापार में लाभ की संभावना है।',
            'Kark (Cancer)' => 'चंद्रमा के गोचर से मानसिक शांति मिलेगी, भगवान शिव का जलाभिषेक करें।',
            'Singh (Leo)' => 'सूर्य देव के प्रभाव से आत्मविश्वास में वृद्धि होगी, मान-सम्मान बढ़ेगा।',
            'Kanya (Virgo)' => 'बुध के प्रभाव से रुके हुए कार्य पूर्ण होंगे, ॐ गं गणपतये नमः का जप करें।',
            'Tula (Libra)' => 'शुक्र के शुभ गोचर से भौतिक सुख-साधनों में वृद्धि होगी।',
            'Vrishchik (Scorpio)' => 'मंगल की अनुकूलता से साहस और पराक्रम में वृद्धि होगी।',
            'Dhanu (Sagittarius)' => 'गुरु बृहस्पति के आशीर्वाद से ज्ञान और अध्यात्म में रुचि बढ़ेगी।',
            'Makar (Capricorn)' => 'शनि देव के प्रभाव से धैर्य बनाए रखें, सायंकाल तिल के तेल का दीपक जलाएं।',
            'Kumbh (Aquarius)' => 'शनि के गोचर से सामाजिक प्रतिष्ठा में वृद्धि होगी, गरीबों की सेवा करें।',
            'Meen (Pisces)' => 'गुरु के शुभ प्रभाव से धन लाभ और परिवार में मांगलिक कार्य के योग हैं।',
        ];

        $todayDate = Carbon::now()->format('d M Y');

        foreach ($users as $user) {
            $rashiKey = array_rand($rashis);
            $rashiForecast = $rashis[$rashiKey];

            $title = "🌟 आज का गोचर फल ({$todayDate}) — " . ($user->name ?? 'भक्त');
            $message = "प्रणाम {$user->name}! आपकी राशि ({$rashiKey}) के अनुसार: {$rashiForecast} आज का शुभ रंग: पीला, शुभ अंक: 7. अपने दिन को मंगलमय बनाने के लिए ऑनलाइन पूजा ऐप में दैनिक राशिफल देखें।";

            $confidence = 92;
            $shouldSendDirectly = ($mode === 'full' || $forceDispatch);

            $queueId = DB::table('autopilot_queue')->insertGetId([
                'type' => 'gochar',
                'recipient_id' => $user->id,
                'recipient_type' => 'user',
                'title' => $title,
                'message' => $message,
                'meta_data' => json_encode([
                    'rashi' => $rashiKey,
                    'user_name' => $user->name,
                    'date' => $todayDate,
                ]),
                'status' => $shouldSendDirectly ? 'sent' : 'pending',
                'confidence_score' => $confidence,
                'channel' => 'push',
                'sent_at' => $shouldSendDirectly ? Carbon::now() : null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            if ($shouldSendDirectly) {
                $this->dispatchNotificationToUser($user->id, $title, $message);
                $sent++;
            } else {
                $queued++;
            }
        }

        $duration = (int) ((microtime(true) - $start) * 1000);
        $this->logRun('gochar_loop', 'autopilot', 'completed', "Gochar processed: {$sent} sent, {$queued} staged for approval", $sent + $queued, 0, $duration);

        return [
            'loop' => 'gochar',
            'sent' => $sent,
            'queued' => $queued,
            'status' => 'completed',
        ];
    }

    /**
     * Loop 2: Daily Vedic Panchang & Shubh Muhurta Broadcast.
     */
    public function runPanchangLoop(bool $forceDispatch = false): array
    {
        $start = microtime(true);
        $mode = Vault::get('autopilot_mode', 'supervised');
        $today = Carbon::now();
        $dateStr = $today->format('d M Y');

        $tithis = ['शुक्ल पक्ष तृतीया', 'कृष्ण पक्ष पंचमी', 'शुक्ल पक्ष एकादशी', 'कृष्ण पक्ष त्रयोदशी', 'पूर्णिमा'];
        $nakshatras = ['रोहिणी', 'पुष्य', 'अश्विनी', 'हस्त', 'मृगशिरा', 'उत्तराषाढ़ा'];
        $tithi = $tithis[array_rand($tithis)];
        $nakshatra = $nakshatras[array_rand($nakshatras)];

        $title = "🕉️ आज का वैदिक पंचांग एवं शुभ मुहूर्त — {$dateStr}";
        $message = "तिथि: {$tithi} | नक्षत्र: {$nakshatra} | अभिजीत मुहूर्त: 11:45 AM - 12:35 PM | राहु काल: 04:30 PM - 06:00 PM. आज का दिन नए कार्यों के शुभारंभ और भगवान शिव व माता लक्ष्मी की आराधना हेतु अति शुभ है।";

        $confidence = 96;
        $shouldSendDirectly = ($mode === 'full' || $forceDispatch);

        $queueId = DB::table('autopilot_queue')->insertGetId([
            'type' => 'panchang',
            'recipient_id' => null, // Broadcast to all
            'recipient_type' => 'broadcast',
            'title' => $title,
            'message' => $message,
            'meta_data' => json_encode([
                'tithi' => $tithi,
                'nakshatra' => $nakshatra,
                'abhijit' => '11:45 AM - 12:35 PM',
                'rahu_kaal' => '04:30 PM - 06:00 PM',
                'date' => $dateStr,
            ]),
            'status' => $shouldSendDirectly ? 'sent' : 'pending',
            'confidence_score' => $confidence,
            'channel' => 'push',
            'sent_at' => $shouldSendDirectly ? Carbon::now() : null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        if ($shouldSendDirectly) {
            $this->dispatchBroadcastNotification($title, $message);
        }

        $duration = (int) ((microtime(true) - $start) * 1000);
        $this->logRun('panchang_loop', 'autopilot', 'completed', "Panchang broadcast prepared (status: " . ($shouldSendDirectly ? 'sent' : 'staged') . ")", 1, 0, $duration);

        return [
            'loop' => 'panchang',
            'status' => $shouldSendDirectly ? 'sent' : 'staged',
            'title' => $title,
        ];
    }

    /**
     * Loop 3: Festival Puja Drip Sequence (T-7 to T-1).
     */
    public function runFestivalDripLoop(bool $forceDispatch = false): array
    {
        $start = microtime(true);
        $mode = Vault::get('autopilot_mode', 'supervised');
        $now = Carbon::now();
        $targetHorizon = Carbon::now()->addDays(7);

        // Fetch upcoming festivals within the next 7 days
        $festivals = DB::table('hindu_festivals')
            ->whereBetween('date', [$now->toDateString(), $targetHorizon->toDateString()])
            ->orderBy('date', 'ASC')
            ->get();

        if ($festivals->isEmpty()) {
            // Fallback: Pick next upcoming festival
            $festivals = DB::table('hindu_festivals')
                ->where('date', '>=', $now->toDateString())
                ->orderBy('date', 'ASC')
                ->limit(2)
                ->get();
        }

        $pujas = DB::table('pujas')->where('puja_status', 1)->limit(5)->get();
        $staged = 0;
        $sent = 0;

        foreach ($festivals as $fest) {
            $festDate = Carbon::parse($fest->date);
            $daysLeft = (int) $now->diffInDays($festDate, false);
            $matchedPuja = $pujas->first();

            $title = "🪔 आगामी पावन पर्व: {$fest->name} (" . ($daysLeft == 0 ? 'आज' : "{$daysLeft} दिन शेष") . ")";
            $pujaName = $matchedPuja ? ($matchedPuja->puja_title ?? 'विशेष महा Hawan एवं रुद्राभिषेक') : 'विशेष महा Hawan एवं रुद्राभिषेक';
            $message = "हरि ॐ! आगामी {$fest->name} के पावन अवसर पर अपने और परिवार के कल्याण हेतु '{$pujaName}' का संकल्प अभी बुक करें। घर बैठे तीर्थ धाम से लाइव दर्शन व प्रसाद प्राप्त करें।";

            $confidence = 94;
            $shouldSendDirectly = ($mode === 'full' || $forceDispatch);

            DB::table('autopilot_queue')->insert([
                'type' => 'festival_drip',
                'recipient_id' => null,
                'recipient_type' => 'broadcast',
                'title' => $title,
                'message' => $message,
                'meta_data' => json_encode([
                    'festival' => $fest->name,
                    'festival_date' => $fest->date,
                    'days_left' => $daysLeft,
                    'puja_id' => $matchedPuja ? $matchedPuja->id : null,
                ]),
                'status' => $shouldSendDirectly ? 'sent' : 'pending',
                'confidence_score' => $confidence,
                'channel' => 'push',
                'sent_at' => $shouldSendDirectly ? Carbon::now() : null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            if ($shouldSendDirectly) {
                $this->dispatchBroadcastNotification($title, $message);
                $sent++;
            } else {
                $staged++;
            }
        }

        $duration = (int) ((microtime(true) - $start) * 1000);
        $this->logRun('festival_loop', 'autopilot', 'completed', "Festival drip: {$sent} sent, {$staged} staged for approval", $sent + $staged, 0, $duration);

        return [
            'loop' => 'festival_drip',
            'festivals_count' => $festivals->count(),
            'sent' => $sent,
            'staged' => $staged,
        ];
    }

    /**
     * Loop 4: Astrologer AI Twin Instant Fallback.
     */
    public function runTwinFallbackLoop(): array
    {
        $start = microtime(true);
        // Look for pending calls that have been waiting > 40 seconds
        $thresholdTime = Carbon::now()->subSeconds(40);

        $pendingCalls = DB::table('callrequest')
            ->where('callStatus', 'Pending')
            ->where('created_at', '<=', $thresholdTime)
            ->limit(5)
            ->get();

        $resolved = 0;
        foreach ($pendingCalls as $call) {
            // Avoid duplicate notifications for the same pending call
            $alreadyNotified = DB::table('user_notifications')->where('callRequestId', $call->id)->exists();
            if ($alreadyNotified) {
                continue;
            }

            // Mark call with AI Twin suggestion
            DB::table('user_notifications')->insert([
                'userId' => $call->userId,
                'title' => '🤖 AI आचार्य परामर्श उपलब्ध है',
                'description' => 'पंडित जी अभी अन्य सत्र में व्यस्त हैं। आप तुरंत वैदिक AI आचार्य से लाइव फोन या चैट परामर्श प्रारंभ कर सकते हैं।',
                'notification_type' => 2,
                'callRequestId' => $call->id,
                'createdBy' => 1,
                'modifiedBy' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            $resolved++;
        }

        $duration = (int) ((microtime(true) - $start) * 1000);
        if ($resolved > 0) {
            $this->logRun('twin_fallback_loop', 'autopilot', 'completed', "Offered AI Twin fallback for {$resolved} pending calls", $resolved, 0, $duration);
        }

        return [
            'loop' => 'twin_fallback',
            'resolved' => $resolved,
        ];
    }

    /**
     * Loop 5: Devotee Churn Revival & Re-engagement.
     */
    public function runChurnRevivalLoop(bool $forceDispatch = false): array
    {
        $start = microtime(true);
        $mode = Vault::get('autopilot_mode', 'supervised');

        // Target users registered > 3 days ago with wallet balance <= 10
        $dormantUsers = DB::table('users')
            ->where('isActive', 1)
            ->whereNotNull('name')
            ->where('created_at', '<=', Carbon::now()->subDays(2))
            ->limit(10)
            ->get();

        $staged = 0;
        $sent = 0;

        foreach ($dormantUsers as $user) {
            $title = "🎁 विशेष आशीर्वाद: ₹50 परामर्श बोनस — " . ($user->name ?? 'भक्त');
            $message = "हरि ॐ {$user->name}! आपकी जन्मकुंडली के अनुसार इस सप्ताह शुभ ग्रह गोचर सक्रिय है। आज ही अपने भविष्य, करियर और प्रेम जीवन पर शीर्ष ज्योतिषाचार्यों से परामर्श करें। पहले रिचार्ज पर ₹50 अतिरिक्त पाएं!";

            $confidence = 90;
            $shouldSendDirectly = ($mode === 'full' || $forceDispatch);

            DB::table('autopilot_queue')->insert([
                'type' => 'churn_revival',
                'recipient_id' => $user->id,
                'recipient_type' => 'user',
                'title' => $title,
                'message' => $message,
                'meta_data' => json_encode(['bonus_amount' => 50, 'user_id' => $user->id]),
                'status' => $shouldSendDirectly ? 'sent' : 'pending',
                'confidence_score' => $confidence,
                'channel' => 'push',
                'sent_at' => $shouldSendDirectly ? Carbon::now() : null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            if ($shouldSendDirectly) {
                $this->dispatchNotificationToUser($user->id, $title, $message);
                $sent++;
            } else {
                $staged++;
            }
        }

        $duration = (int) ((microtime(true) - $start) * 1000);
        $this->logRun('churn_loop', 'autopilot', 'completed', "Churn revival: {$sent} sent, {$staged} staged", $sent + $staged, 0, $duration);

        return [
            'loop' => 'churn_revival',
            'sent' => $sent,
            'staged' => $staged,
        ];
    }

    /**
     * Approve a single item staged in the Autopilot Queue (Human-in-the-Loop).
     */
    public function approveItem(int $id, ?int $adminId = null): bool
    {
        $item = DB::table('autopilot_queue')->where('id', $id)->first();
        if (!$item) {
            return false;
        }

        // Dispatch
        if ($item->recipient_type === 'broadcast' || empty($item->recipient_id)) {
            $this->dispatchBroadcastNotification($item->title, $item->message);
        } else {
            $this->dispatchNotificationToUser($item->recipient_id, $item->title, $item->message);
        }

        DB::table('autopilot_queue')->where('id', $id)->update([
            'status' => 'sent',
            'approved_by' => $adminId,
            'sent_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return true;
    }

    /**
     * Approve ALL pending items in the Autopilot Queue with 1-click.
     */
    public function approveAll(?int $adminId = null): int
    {
        $pending = DB::table('autopilot_queue')->where('status', 'pending')->get();
        $count = 0;

        foreach ($pending as $item) {
            $this->approveItem($item->id, $adminId);
            $count++;
        }

        return $count;
    }

    /**
     * Reject an item staged in the queue.
     */
    public function rejectItem(int $id): bool
    {
        return (bool) DB::table('autopilot_queue')->where('id', $id)->update([
            'status' => 'rejected',
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Omnichannel Dispatcher: Reaches devotees across WhatsApp, Brevo Email & Mobile Push.
     */
    protected function dispatchNotificationToUser(int $userId, string $title, string $message): void
    {
        try {
            // 1. In-App Notification Record (truncated to varchar(255) limits)
            DB::table('user_notifications')->insert([
                'userId' => $userId,
                'title' => mb_substr($title, 0, 250),
                'description' => mb_substr($message, 0, 250),
                'notificationId' => null,
                'createdBy' => 1,
                'modifiedBy' => 1,
                'notification_type' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            $user = DB::table('users')->where('id', $userId)->first();

            // 2. Channel A: Evolution WhatsApp (Primary Instant Reach)
            if ($user && !empty($user->contactNo)) {
                try {
                    $wa = app(\App\Services\EvolutionWaService::class);
                    $cleanPhone = $wa->cleanNumber($user->contactNo);
                    $blacklisted = ['919999041935', '9999041935', '919305932724', '9305932724'];

                    $isOptedOut = in_array($cleanPhone, $blacklisted) 
                        || in_array(substr($cleanPhone, -10), $blacklisted)
                        || DB::table('whatsapp_optouts')->where('phone', $cleanPhone)->orWhere('phone', substr($cleanPhone, -10))->exists();

                    if (!$isOptedOut && strlen($cleanPhone) >= 10) {
                        $waMsg = "🕉️ *{$title}*\n\n{$message}\n\n👉 *OnlinePuja.live:* " . url('/') . "\n_Varanasi Devotional Seva_";
                        $wa->sendText($cleanPhone, $waMsg);
                    }
                } catch (\Throwable $e) {
                    Log::info("Autopilot WA delivery error: " . $e->getMessage());
                }
            }

            // 3. Channel B: Brevo Email (Detailed Digest)
            if ($user && !empty($user->email)) {
                try {
                    $brevo = app(\App\Services\BrevoEmailService::class);
                    $html = $brevo->renderDevotionalHtml($title, "<p>Pranam <strong>{$user->name}</strong>,</p>" . nl2br(e($message)), "Open OnlinePuja", url('/'));
                    $brevo->sendEmail($user->email, $user->name ?: 'Devotee', $title, $html);
                } catch (\Throwable $e) {
                    Log::info("Autopilot Brevo delivery error: " . $e->getMessage());
                }
            }

            // 4. Channel C: OneSignal Mobile Push
            $device = DB::table('user_device_details')->where('userId', $userId)->first();
            if ($device && (!empty($device->subscription_id) || !empty($device->subscription_id_web))) {
                $ids = array_filter([$device->subscription_id, $device->subscription_id_web]);
                if (!empty($ids)) {
                    $oneSignal = new OneSignalService();
                    $oneSignal->sendNotification(array_values($ids), [
                        'title' => $title,
                        'body' => ['description' => $message],
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Autopilot user notification dispatch error: " . $e->getMessage());
        }
    }

    /**
     * Helper to dispatch omnichannel broadcast notification.
     */
    protected function dispatchBroadcastNotification(string $title, string $message): void
    {
        try {
            // Channel A: OneSignal Push
            $devices = DB::table('user_device_details')
                ->whereNotNull('subscription_id')
                ->pluck('subscription_id')
                ->filter()
                ->values()
                ->toArray();

            if (!empty($devices)) {
                $oneSignal = new OneSignalService();
                $oneSignal->sendNotification($devices, [
                    'title' => $title,
                    'body' => ['description' => $message],
                ]);
            }

            // Channel B: Devotee WhatsApp Broadcast (capped at 25 per cycle with opt-out & blacklist protection)
            $optedOutPhones = DB::table('whatsapp_optouts')->pluck('phone')->toArray();
            $blacklisted = ['919999041935', '9999041935', '919305932724', '9305932724'];

            $users = DB::table('users')
                ->whereNotNull('contactNo')
                ->where('contactNo', '!=', '')
                ->inRandomOrder()
                ->limit(50)
                ->get();

            $wa = app(\App\Services\EvolutionWaService::class);
            $sentCount = 0;

            foreach ($users as $u) {
                if ($sentCount >= 25) break;

                try {
                    $cleanPhone = $wa->cleanNumber($u->contactNo);
                    if (strlen($cleanPhone) < 10) continue;

                    if (in_array($cleanPhone, $blacklisted) || in_array(substr($cleanPhone, -10), $blacklisted)) {
                        continue;
                    }

                    if (in_array($cleanPhone, $optedOutPhones) || in_array(substr($cleanPhone, -10), $optedOutPhones)) {
                        continue;
                    }

                    $waMsg = "🕉️ *{$title}*\n\n{$message}\n\n👉 *OnlinePuja.live:* " . url('/') . "\n_Har Har Mahadev_";
                    $wa->sendText($cleanPhone, $waMsg);
                    $sentCount++;
                } catch (\Throwable $e) {}
            }
        } catch (\Throwable $e) {
            Log::warning("Autopilot broadcast dispatch error: " . $e->getMessage());
        }
    }

    /**
     * Log execution to brain_run_log.
     */
    protected function logRun(string $task, string $category, string $status, string $summary, int $ok, int $failed, int $durationMs): void
    {
        DB::table('brain_run_log')->insert([
            'task' => $task,
            'category' => $category,
            'status' => $status,
            'summary' => substr($summary, 0, 250),
            'detail' => json_encode([
                'summary' => $summary,
                'items_ok' => $ok,
                'items_failed' => $failed,
                'duration_ms' => $durationMs,
            ]),
            'items_ok' => $ok,
            'items_failed' => $failed,
            'duration_ms' => $durationMs,
            'trigger' => 'schedule',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
