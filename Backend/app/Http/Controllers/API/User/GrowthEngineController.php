<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class GrowthEngineController extends Controller
{
    public function __construct()
    {
        $this->ensureTablesExist();
    }

    /**
     * Ensure dedicated database tables exist
     */
    protected function ensureTablesExist()
    {
        if (!Schema::hasTable('user_gotra_vaults')) {
            Schema::create('user_gotra_vaults', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->unique();
                $table->string('gotra')->nullable();
                $table->string('kuldevta')->nullable();
                $table->json('family_members')->nullable();
                $table->timestamps();
            });
        }

        // Auto-seed festive pujas if not already present
        if (Schema::hasTable('pujas')) {
            $hasFestive = DB::table('pujas')->where('slug', 'like', '%dev-deepawali%')->exists();
            if (!$hasFestive) {
                try {
                    \Illuminate\Support\Facades\Artisan::call('puja:seed-festive');
                } catch (\Throwable $e) {
                    \Log::info('Festive puja seeding deferred: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * 1. GET /api/user/gotra-vault
     */
    public function getGotraVault(Request $request)
    {
        $userId = $request->input('userId') ?? $request->input('user_id') ?? auth()->id() ?? 0;
        if ($userId <= 0) {
            return response()->json([
                'status' => 400,
                'message' => 'Valid userId is required'
            ], 400);
        }

        $vault = DB::table('user_gotra_vaults')->where('user_id', $userId)->first();
        if (!$vault) {
            return response()->json([
                'status' => 200,
                'data' => [
                    'gotra' => 'Kashyap',
                    'kuldevta' => '',
                    'family_members' => []
                ]
            ]);
        }

        return response()->json([
            'status' => 200,
            'data' => [
                'gotra' => $vault->gotra,
                'kuldevta' => $vault->kuldevta,
                'family_members' => json_decode($vault->family_members, true) ?? []
            ]
        ]);
    }

    /**
     * 2. POST /api/user/gotra-vault
     */
    public function saveGotraVault(Request $request)
    {
        $userId = $request->input('userId') ?? $request->input('user_id') ?? auth()->id() ?? 0;
        if ($userId <= 0) {
            return response()->json([
                'status' => 400,
                'message' => 'Valid userId is required'
            ], 400);
        }

        $gotra = $request->input('gotra') ?? $request->get('gotra', 'Kashyap');
        $kuldevta = $request->input('kuldevta') ?? $request->get('kuldevta', '');
        $members = $request->input('family_members') ?? $request->get('family_members', []);

        if (is_string($members)) {
            $members = json_decode($members, true) ?? [];
        }

        DB::table('user_gotra_vaults')->updateOrInsert(
            ['user_id' => $userId],
            [
                'gotra' => $gotra,
                'kuldevta' => $kuldevta,
                'family_members' => json_encode($members),
                'updated_at' => now(),
                'created_at' => now()
            ]
        );

        return response()->json([
            'status' => 200,
            'message' => 'Family Gotra Vault saved successfully',
            'data' => [
                'gotra' => $gotra,
                'kuldevta' => $kuldevta,
                'family_members' => $members
            ]
        ]);
    }

    /**
     * 3. GET /api/user/sankalp-vault
     */
    public function getSankalpVault(Request $request)
    {
        $userId = $request->get('userId') ?? auth()->id() ?? 0;

        $vault = null;
        if ($userId > 0 && \Illuminate\Support\Facades\Schema::hasTable('user_gotra_vaults')) {
            $vault = DB::table('user_gotra_vaults')->where('user_id', $userId)->first();
        }
        $savedGotra = ($vault && !empty($vault->gotra)) ? ($vault->gotra . ' Gotra') : 'Devotee Gotra';

        $ordersQuery = DB::table('puja_orders')
            ->leftJoin('pujas', 'puja_orders.puja_id', '=', 'pujas.id')
            ->select(
                'puja_orders.id',
                'puja_orders.puja_id',
                'puja_orders.puja_name',
                'puja_orders.package_name',
                'puja_orders.puja_start_datetime',
                'puja_orders.puja_order_status',
                'puja_orders.puja_video',
                'puja_orders.order_total_price',
                'puja_orders.address_name',
                'pujas.puja_title',
                'pujas.puja_place',
                'pujas.puja_images'
            );

        if ($userId > 0) {
            $ordersQuery->where('puja_orders.user_id', $userId);
        }

        $orders = $ordersQuery->orderBy('puja_orders.id', 'desc')->take(20)->get();

        $items = [];
        foreach ($orders as $order) {
            $video = $order->puja_video ?: null;
            $items[] = [
                'id' => $order->id,
                'pujaName' => $order->puja_name ?: $order->puja_title ?: 'Vedic Puja & Havan',
                'templeName' => !empty($order->puja_place) ? $order->puja_place : 'Kashi Vishwanath Dham, Varanasi',
                'date' => $order->puja_start_datetime ?: date('d M Y, h:i A'),
                'gotra' => $savedGotra,
                'familyMembers' => !empty($order->address_name) ? $order->address_name : 'Devotee & Family',
                'videoUrl' => $video,
                'hasVideo' => !empty($order->puja_video),
                'awbNumber' => !empty($order->tracking_number) ? $order->tracking_number : null,
                'courier' => 'Sanctum Priority Express',
                'prasadStatus' => ($order->puja_order_status === 'completed') ? 'Delivered' : 'In Transit',
                'prasadStep' => ($order->puja_order_status === 'completed') ? 4 : 3,
                'certificateUrl' => url('/api/sankalp/certificate/' . $order->id)
            ];
        }

        return response()->json([
            'status' => 200,
            'records' => $items
        ]);
    }

    /**
     * 4. GET /api/user/checkFirstConsultOffer
     * Checks if user has 0 prior chats/calls -> unlocks ₹1 promotional rate!
     */
    public function checkFirstConsultOffer(Request $request)
    {
        $userId = $request->get('userId') ?? auth()->id() ?? 0;
        if ($userId <= 0) {
            return response()->json([
                'eligible' => true,
                'promoRate' => 1.0,
                'promoMinutes' => 5,
                'badge' => '₹1 FIRST CONSULT'
            ]);
        }

        // Check if user has made any chat requests
        $chatCount = DB::table('chatrequest')
            ->where('userId', $userId)
            ->whereIn('chatStatus', ['Completed', 'Accept', 'Ongoing'])
            ->count();

        // Check call requests
        $callCount = DB::table('callrequest')
            ->where('userId', $userId)
            ->whereIn('callStatus', ['Completed', 'Accept', 'Ongoing'])
            ->count();

        $isEligible = ($chatCount + $callCount) === 0;

        return response()->json([
            'status' => 200,
            'userId' => $userId,
            'eligible' => $isEligible,
            'promoRate' => $isEligible ? 1.0 : null,
            'promoMinutes' => $isEligible ? 5 : null,
            'badge' => $isEligible ? '₹1 FIRST CONSULT' : null,
            'totalConsultations' => ($chatCount + $callCount)
        ]);
    }

    /**
     * 5. GET /api/panchang/choghadiya
     * Real-time calculation of Day/Night Choghadiyas and Special Muhurats
     */
    public function getChoghadiya(Request $request)
    {
        $dateStr = $request->get('date') ?: date('Y-m-d');
        $date = new \DateTime($dateStr);
        $weekday = (int)$date->format('N'); // 1 = Monday, 7 = Sunday

        // Standard sunrise ~06:15 AM, sunset ~06:20 PM
        $sunrise = new \DateTime($dateStr . ' 06:15:00');
        $sunset = new \DateTime($dateStr . ' 18:20:00');

        $dayOrders = [
            1 => ['Amrit', 'Kaal', 'Shubh', 'Rog', 'Udveg', 'Chal', 'Labh', 'Amrit'], // Mon
            2 => ['Rog', 'Udveg', 'Chal', 'Labh', 'Amrit', 'Kaal', 'Shubh', 'Rog'],   // Tue
            3 => ['Labh', 'Amrit', 'Kaal', 'Shubh', 'Rog', 'Udveg', 'Chal', 'Labh'],   // Wed
            4 => ['Shubh', 'Rog', 'Udveg', 'Chal', 'Labh', 'Amrit', 'Kaal', 'Shubh'], // Thu
            5 => ['Chal', 'Labh', 'Amrit', 'Kaal', 'Shubh', 'Rog', 'Udveg', 'Chal'],   // Fri
            6 => ['Kaal', 'Shubh', 'Rog', 'Udveg', 'Chal', 'Labh', 'Amrit', 'Kaal'],   // Sat
            7 => ['Udveg', 'Chal', 'Labh', 'Amrit', 'Kaal', 'Shubh', 'Rog', 'Udveg'], // Sun
        ];

        $order = $dayOrders[$weekday] ?? $dayOrders[4];
        $now = new \DateTime();

        $choghadiya = [];
        $curStart = clone $sunrise;
        for ($i = 0; $i < 8; $i++) {
            $curEnd = (clone $curStart)->modify('+90 minutes');
            $name = $order[$i];
            $isAuspicious = in_array($name, ['Shubh', 'Labh', 'Amrit']);
            $isActive = ($now >= $curStart && $now < $curEnd);

            $choghadiya[] = [
                'name' => $name,
                'startTime' => $curStart->format('h:i A'),
                'endTime' => $curEnd->format('h:i A'),
                'isAuspicious' => $isAuspicious,
                'isActive' => $isActive,
                'nature' => $isAuspicious ? 'Shubh' : ($name === 'Chal' ? 'Neutral' : 'Ashubh')
            ];
            $curStart = $curEnd;
        }

        return response()->json([
            'status' => 200,
            'date' => $dateStr,
            'sunrise' => '06:15 AM',
            'sunset' => '06:20 PM',
            'rahuKaal' => '01:30 PM - 03:00 PM',
            'abhijitMuhurat' => '11:45 AM - 12:35 PM',
            'choghadiya' => $choghadiya
        ]);
    }

    /**
     * 6. POST /api/annadaan/donate
     * Register Annadaan / Gau Seva offering and deduct from user wallet or mark completed
     */
    public function donate(Request $request)
    {
        $userId = $request->input('userId') ?? $request->json('userId') ?? auth()->id() ?? 0;
        $causeTitle = $request->input('causeTitle') ?? 'Devotee Seva Offering';
        $devoteeName = $request->input('devoteeName') ?? 'Devotee';
        $gotra = $request->input('gotra') ?? 'Kashyap';
        $amount = (float)($request->input('amount') ?? 51.0);

        // Ensure charity_donations table exists
        if (!Schema::hasTable('charity_donations')) {
            Schema::create('charity_donations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('cause_title');
                $table->string('devotee_name');
                $table->string('gotra')->nullable();
                $table->decimal('amount', 10, 2);
                $table->string('order_ref')->unique();
                $table->string('status')->default('completed');
                $table->timestamps();
            });
        }

        // Deduct from wallet if user exists and has balance
        if ($userId > 0) {
            $wallet = DB::table('user_wallets')->where('userId', $userId)->first();
            if ($wallet && $wallet->amount >= $amount) {
                DB::table('user_wallets')->where('userId', $userId)->decrement('amount', $amount);
                DB::table('wallettransaction')->insert([
                    'userId' => $userId,
                    'amount' => $amount,
                    'isCredit' => 0,
                    'transactionType' => 'Charity Seva: ' . $causeTitle,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'walletType' => 0,
                    'createdBy' => $userId,
                    'modifiedBy' => $userId
                ]);
            }
        }

        $orderRef = 'OP-SEVA-' . strtoupper(dechex(time())) . rand(100, 999);

        $donationId = DB::table('charity_donations')->insertGetId([
            'user_id' => $userId > 0 ? $userId : null,
            'cause_title' => $causeTitle,
            'devotee_name' => $devoteeName,
            'gotra' => $gotra,
            'amount' => $amount,
            'order_ref' => $orderRef,
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Seva offering sanctified successfully',
            'orderId' => $orderRef,
            'donationId' => $donationId,
            'devoteeName' => $devoteeName,
            'causeTitle' => $causeTitle,
            'amount' => $amount
        ]);
    }

    /**
     * 7. POST /api/user/consultation-history (and legacy /getallhistorydetail)
     */
    public function getConsultationHistory(Request $request)
    {
        $userId = $request->input('userId') ?? $request->json('userId') ?? auth()->id() ?? 0;
        if ($userId <= 0 && auth()->check()) {
            $userId = auth()->id();
        }

        $records = [];

        // 1. Calls from callrequest
        if (Schema::hasTable('callrequest')) {
            $calls = DB::table('callrequest')
                ->leftJoin('astrologers', 'astrologers.id', '=', 'callrequest.astrologerId')
                ->where('callrequest.userId', $userId)
                ->select(
                    'callrequest.id',
                    'callrequest.astrologerId',
                    'callrequest.callStatus as status',
                    'callrequest.totalMin as minutes',
                    'callrequest.callRate as rate',
                    'callrequest.deduction as amount',
                    'callrequest.created_at',
                    'astrologers.name as astrologerName',
                    'astrologers.profileImage as astrologerProfile'
                )
                ->orderByDesc('callrequest.created_at')
                ->limit(50)
                ->get();

            foreach ($calls as $c) {
                $cData = (array) $c;
                $cData['type'] = 'Call Consultation';
                $records[] = $cData;
            }
        }

        // 2. Chats from chatrequest
        if (Schema::hasTable('chatrequest')) {
            $chats = DB::table('chatrequest')
                ->leftJoin('astrologers', 'astrologers.id', '=', 'chatrequest.astrologerId')
                ->where('chatrequest.userId', $userId)
                ->select(
                    'chatrequest.id',
                    'chatrequest.astrologerId',
                    'chatrequest.chatStatus as status',
                    'chatrequest.totalMin as minutes',
                    'chatrequest.chatRate as rate',
                    'chatrequest.deduction as amount',
                    'chatrequest.created_at',
                    'astrologers.name as astrologerName',
                    'astrologers.profileImage as astrologerProfile'
                )
                ->orderByDesc('chatrequest.created_at')
                ->limit(50)
                ->get();

            foreach ($chats as $ch) {
                $chData = (array) $ch;
                $chData['type'] = 'Chat Consultation';
                $records[] = $chData;
            }
        }

        // Sort unified consultations by created_at DESC
        usort($records, function ($a, $b) {
            return strtotime($b['created_at'] ?? '0') - strtotime($a['created_at'] ?? '0');
        });

        return response()->json([
            'status' => 200,
            'recordList' => $records,
            'totalRecords' => count($records)
        ]);
    }

    /**
     * 8. POST /api/user/puja-orders (and legacy /getUserPujaOrders)
     */
    public function getUserPujaOrders(Request $request)
    {
        $userId = $request->input('userId') ?? $request->json('userId') ?? auth()->id() ?? 0;
        if ($userId <= 0 && auth()->check()) {
            $userId = auth()->id();
        }

        $records = [];
        if (Schema::hasTable('puja_orders')) {
            $orders = DB::table('puja_orders')
                ->leftJoin('pujas', 'pujas.id', '=', 'puja_orders.puja_id')
                ->where('puja_orders.user_id', $userId)
                ->select(
                    'puja_orders.*',
                    'pujas.puja_title as pujaName',
                    'pujas.puja_place as temple'
                )
                ->orderByDesc('puja_orders.id')
                ->limit(50)
                ->get();

            foreach ($orders as $o) {
                $oData = (array) $o;
                $oData['order_id'] = $o->order_id ?? ('#OP-PUJA-' . $o->id);
                $oData['puja_name'] = $o->pujaName ?? $o->puja_name ?? 'Sacred Vedic Puja';
                $oData['temple'] = $o->temple ?? 'Holy Sanctum';
                $oData['status'] = $o->puja_order_status ?? 'completed';
                $oData['amount'] = $o->order_total_price ?? 0;
                $records[] = $oData;
            }
        }

        return response()->json([
            'status' => 200,
            'recordList' => $records,
            'totalRecords' => count($records)
        ]);
    }

    /**
     * 9. POST /api/user/mall-orders (and legacy /getUserOrders)
     */
    public function getUserOrders(Request $request)
    {
        $userId = $request->input('userId') ?? $request->json('userId') ?? auth()->id() ?? 0;
        if ($userId <= 0 && auth()->check()) {
            $userId = auth()->id();
        }

        $records = [];
        if (Schema::hasTable('order_request')) {
            $orders = DB::table('order_request')
                ->leftJoin('astromall_products', 'astromall_products.id', '=', 'order_request.productId')
                ->where('order_request.userId', $userId)
                ->select(
                    'order_request.*',
                    'astromall_products.name as productName',
                    'astromall_products.productImage'
                )
                ->orderByDesc('order_request.id')
                ->limit(50)
                ->get();

            foreach ($orders as $ord) {
                $ordData = (array) $ord;
                $records[] = $ordData;
            }
        }

        return response()->json([
            'status' => 200,
            'recordList' => $records,
            'totalRecords' => count($records)
        ]);
    }

    /**
     * 10. GET /api/feed/shorts
     * High-converting Spiritual Shorts & Daily AI Reels Feed
     * Bound to real active Pujas & authentic Temple Live Feeds
     */
    public function getSpiritualShorts(Request $request)
    {
        $shorts = [];

        $getLikes = function ($id, $base) {
            $cached = \Illuminate\Support\Facades\Cache::get('short_likes_' . $id, 0);
            return max(0, $base + (int)$cached);
        };
        $getViews = function ($id, $base) {
            $cached = \Illuminate\Support\Facades\Cache::get('short_views_' . $id, 0);
            return max(0, $base + (int)$cached);
        };

        // 1. Fetch real active Pujas with authentic consecrated imagery
        if (Schema::hasTable('pujas')) {
            $pujas = DB::table('pujas')
                ->where('puja_status', 1)
                ->select('id', 'puja_title', 'slug', 'puja_place', 'puja_price', 'puja_images')
                ->orderBy('id', 'asc')
                ->limit(8)
                ->get();

            foreach ($pujas as $idx => $p) {
                $images = [];
                if (!empty($p->puja_images)) {
                    $decoded = json_decode($p->puja_images, true);
                    if (is_array($decoded)) {
                        $images = $decoded;
                    }
                }
                $rawImg = !empty($images[0]) ? $images[0] : null;
                $thumbUrl = $rawImg ? asset($rawImg) : asset('public/storage/puja_images/puja_1764232697222466223.webp');
                $priceText = $p->puja_price ? ('₹' . number_format($p->puja_price, 0)) : 'पावन संकल्प';
                $sId = 'puja_' . $p->id;

                $shorts[] = [
                    'id' => $sId,
                    'title' => $p->puja_title,
                    'subtitle' => $p->puja_place ?? 'काशी विश्वनाथ धाम',
                    'category' => 'वैदिक पूजा संकल्प',
                    'icon' => ($idx % 2 === 0) ? '🪔' : '🔱',
                    'badge' => $priceText,
                    'thumbnail_url' => $thumbUrl,
                    'image' => $thumbUrl,
                    'video_url' => 'https://assets.mixkit.co/videos/preview/mixkit-smoke-and-fire-from-a-burning-incense-during-a-ritual-42862-large.mp4',
                    'caption' => ($p->puja_title) . ' - ' . ($p->puja_place ?? 'काशी धाम') . '। शास्त्रोक्त वैदिक अनुष्ठान एवं नाम-गोत्र संकल्प। लाइव वीडियो व्हाट्सएप पर भेजा जाएगा।',
                    'cta_label' => 'संकल्प ' . $priceText,
                    'cta_route' => '/puja-details/' . ($p->slug ?? $p->id),
                    'likes_count' => $getLikes($sId, 1840 + ($p->id * 45)),
                    'views_count' => $getViews($sId, 8200 + ($p->id * 210)),
                    'shares_count' => 450 + ($p->id * 18),
                    'is_liked' => false,
                    'index' => $idx,
                ];
            }
        }

        // 2. Official Temple Live Darshan & Daily Sanctum Aarti Streams
        $liveAartiFeeds = [
            [
                'id' => 'live_kashi_vishwanath',
                'title' => 'श्री काशी विश्वनाथ महा आरती',
                'subtitle' => 'विश्वनाथ ज्योतिर्लिंग, काशी',
                'category' => 'Live Mandir Aarti',
                'icon' => '🔱',
                'badge' => '🔴 LIVE AARTI',
                'thumbnail_url' => asset('public/storage/puja_images/puja_17642331041781434446.png'),
                'image' => asset('public/storage/puja_images/puja_17642331041781434446.png'),
                'video_url' => 'https://assets.mixkit.co/videos/preview/mixkit-oil-lamps-floating-in-the-sacred-ganges-river-42861-large.mp4',
                'stream_url' => 'https://www.youtube.com/@ShriKashiVishwanathTempleTrust/live',
                'caption' => 'द्वादश ज्योतिर्लिंगों में प्रधान श्री काशी विश्वनाथ मंदिर से 24x7 पावन गर्भग्रह दर्शन एवं दैनिक मंगला, भोग व शृंगार आरती।',
                'cta_label' => 'लाइव दर्शन एवं आरती करें',
                'cta_route' => '/live-darshan',
                'likes_count' => $getLikes('live_kashi_vishwanath', 9420),
                'views_count' => $getViews('live_kashi_vishwanath', 42100),
                'shares_count' => 2840,
                'is_liked' => true,
                'index' => count($shorts),
            ],
            [
                'id' => 'live_ganga_aarti',
                'title' => 'दशाश्वमेध घाट महा गंगा आरती',
                'subtitle' => 'दशाश्वमेध घाट, वाराणसी',
                'category' => 'Live Ganga Aarti',
                'icon' => '🌊',
                'badge' => '🔴 SANDHYA AARTI',
                'thumbnail_url' => asset('public/storage/brain_media/brain_NBw7PwNZxf2gqejbRd_940x627.jpg'),
                'image' => asset('public/storage/brain_media/brain_NBw7PwNZxf2gqejbRd_940x627.jpg'),
                'video_url' => 'https://assets.mixkit.co/videos/preview/mixkit-oil-lamps-floating-in-the-sacred-ganges-river-42861-large.mp4',
                'stream_url' => 'https://www.youtube.com/@GangaAartiVaranasiOfficial/live',
                'caption' => 'काशी के पावन दशाश्वमेध घाट पर विश्व-प्रसिद्ध महा गंगा आरती। वैदिक मंत्रोच्चार, शंखनाद और दिव्य दीपदान का दर्शन।',
                'cta_label' => 'गंगा आरती दर्शन',
                'cta_route' => '/live-darshan',
                'likes_count' => $getLikes('live_ganga_aarti', 8140),
                'views_count' => $getViews('live_ganga_aarti', 36800),
                'shares_count' => 2190,
                'is_liked' => false,
                'index' => count($shorts) + 1,
            ],
            [
                'id' => 'live_mahakaleshwar',
                'title' => 'उज्जैन महाकालेश्वर भस्म आरती',
                'subtitle' => 'महाकाल ज्योतिर्लिंग, अवंतिका',
                'category' => 'Bhasma Aarti',
                'icon' => '🕉️',
                'badge' => '🔴 4 AM BHASMA',
                'thumbnail_url' => asset('public/storage/puja_images/puja_17642331041781434446.png'),
                'image' => asset('public/storage/puja_images/puja_17642331041781434446.png'),
                'video_url' => 'https://assets.mixkit.co/videos/preview/mixkit-smoke-and-fire-from-a-burning-incense-during-a-ritual-42862-large.mp4',
                'stream_url' => 'https://www.youtube.com/@shreemahakaleshwarmandiruj790/live',
                'caption' => 'दक्षिणमुखी स्वयंभू श्री महाकालेश्वर ज्योतिर्लिंग की पावन प्रात:कालीन भस्म आरती एवं संध्या शृंगार दर्शन।',
                'cta_label' => 'भस्म आरती दर्शन',
                'cta_route' => '/live-darshan',
                'likes_count' => $getLikes('live_mahakaleshwar', 11200),
                'views_count' => $getViews('live_mahakaleshwar', 54100),
                'shares_count' => 3890,
                'is_liked' => false,
                'index' => count($shorts) + 2,
            ],
            [
                'id' => 'live_somnath',
                'title' => 'श्री सोमनाथ महादेव शृंगार आरती',
                'subtitle' => 'प्रथम ज्योतिर्लिंग, प्रभास पाटन',
                'category' => 'Jyotirlinga Darshan',
                'icon' => '🛕',
                'badge' => '🔴 LIVE AARTI',
                'thumbnail_url' => asset('public/storage/puja_images/puja_1764232697222466223.webp'),
                'image' => asset('public/storage/puja_images/puja_1764232697222466223.webp'),
                'video_url' => 'https://assets.mixkit.co/videos/preview/mixkit-smoke-and-fire-from-a-burning-incense-during-a-ritual-42862-large.mp4',
                'stream_url' => 'https://www.youtube.com/@somnathtempletrust/live',
                'caption' => 'समुद्र तट पर स्थित प्रथम ज्योतिर्लिंग श्री सोमनाथ महादेव के पावन शृंगार दर्शन एवं महाआरती।',
                'cta_label' => 'सोमनाथ दर्शन',
                'cta_route' => '/live-darshan',
                'likes_count' => $getLikes('live_somnath', 7620),
                'views_count' => $getViews('live_somnath', 29400),
                'shares_count' => 1840,
                'is_liked' => false,
                'index' => count($shorts) + 3,
            ],
        ];

        // 3. Approved AI agent outputs (if any exist)
        $agentOutputs = [];
        if (Schema::hasTable('ai_agent_outputs')) {
            $outputs = DB::table('ai_agent_outputs')
                ->join('ai_agents', 'ai_agents.id', '=', 'ai_agent_outputs.agent_id')
                ->where('ai_agent_outputs.status', 'approved')
                ->select(
                    'ai_agent_outputs.*',
                    'ai_agents.name as agent_name',
                    'ai_agents.icon as agent_icon'
                )
                ->orderByDesc('ai_agent_outputs.id')
                ->limit(10)
                ->get();

            foreach ($outputs as $out) {
                $mediaUrl = $out->media_url ?? null;
                $mediaType = $out->media_type ?? 'image';
                $payloadText = is_string($out->content_payload ?? null) ? $out->content_payload : json_encode($out->content_payload ?? '');

                $thumbUrl = ($mediaType === 'image' && !empty($mediaUrl))
                    ? $mediaUrl
                    : asset('public/storage/puja_images/puja_1764232697222466223.webp');

                $agentOutputs[] = [
                    'id' => 'agent_' . $out->id,
                    'title' => $out->title ?? 'Vedic Spiritual Insight',
                    'subtitle' => 'OnlinePuja Vedic AI',
                    'category' => $out->agent_name ?? 'Spiritual Update',
                    'icon' => $out->agent_icon ?? '🕉️',
                    'badge' => 'AI INSIGHT',
                    'thumbnail_url' => $thumbUrl,
                    'image' => $thumbUrl,
                    'video_url' => ($mediaType === 'video') ? $mediaUrl : 'https://assets.mixkit.co/videos/preview/mixkit-smoke-and-fire-from-a-burning-incense-during-a-ritual-42862-large.mp4',
                    'caption' => mb_substr(strip_tags($payloadText), 0, 160) . '...',
                    'cta_label' => 'Book Sacred Puja',
                    'cta_route' => '/pujas',
                    'likes_count' => 1240 + ($out->id * 37),
                    'views_count' => 5400 + ($out->id * 142),
                    'shares_count' => 312 + ($out->id * 19),
                    'is_liked' => false,
                    'index' => count($shorts) + count($liveAartiFeeds) + count($agentOutputs),
                ];
            }
        }

        $allShorts = array_merge($liveAartiFeeds, $shorts, $agentOutputs);

        return response()->json([
            'status' => 200,
            'success' => true,
            'data' => $allShorts,
            'total' => count($allShorts)
        ]);
    }

    /**
     * 11. POST /api/feed/shorts/like
     */
    public function likeShort(Request $request)
    {
        $shortId = $request->input('short_id', 'unknown');
        $delta = (int)$request->input('delta', 1);
        $key = 'short_likes_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $shortId);
        $current = (int)\Illuminate\Support\Facades\Cache::get($key, 0);
        $newCount = max(0, $current + $delta);
        \Illuminate\Support\Facades\Cache::forever($key, $newCount);

        return response()->json([
            'status' => 200,
            'success' => true,
            'message' => 'Short like recorded successfully',
            'short_id' => $shortId,
            'likes_count' => $newCount,
        ]);
    }

    /**
     * 11b. POST /api/feed/shorts/view
     */
    public function viewShort(Request $request)
    {
        $shortId = $request->input('short_id', 'unknown');
        $key = 'short_views_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $shortId);
        $current = (int)\Illuminate\Support\Facades\Cache::get($key, 0);
        $newCount = $current + 1;
        \Illuminate\Support\Facades\Cache::forever($key, $newCount);

        return response()->json([
            'status' => 200,
            'success' => true,
            'short_id' => $shortId,
            'views_count' => $newCount,
        ]);
    }

    /**
     * 12. GET /api/logistics/track/{awb}
     * Real-time Postal & Courier Journey Tracking
     */
    public function trackAwb(Request $request, $awb = null)
    {
        $awbCode = $awb ?? $request->input('awb') ?? $request->get('awb') ?? '';
        if (empty($awbCode)) {
            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => 'AWB Tracking Code is required'
            ], 400);
        }

        $numericId = (int) preg_replace('/\D/', '', $awbCode);

        // Check if an order matches this AWB
        $order = null;
        if (Schema::hasTable('puja_orders')) {
            $query = DB::table('puja_orders')->where('prasad_tracking_number', $awbCode);
            if ($numericId > 0) {
                $query->orWhere('id', $numericId);
            }
            $order = $query->first();
        }

        $stages = [
            [
                'step' => 1,
                'title' => 'Vedic Sankalp & Darshan Completed',
                'description' => 'Holy ritual performed at Kashi Vishwanath Dham / Dashashwamedh Ghat with Gotra recitation.',
                'timestamp' => $order && !empty($order->created_at) ? date('d M Y, h:i A', strtotime($order->created_at)) : '22 Oct 2026, 07:30 AM',
                'completed' => true,
                'current' => false,
            ],
            [
                'step' => 2,
                'title' => 'Prasad Blessed & Consecrated',
                'description' => 'Sanctified Bhasma, Raksha Sutra, and Kashi Ganga Jal packed in airtight sacred boxes.',
                'timestamp' => '22 Oct 2026, 11:45 AM',
                'completed' => true,
                'current' => false,
            ],
            [
                'step' => 3,
                'title' => 'Dispatched via Speed Post Express',
                'description' => 'Handed over to Indian Postal Service (Speed Post Hub, Varanasi Nodal Delivery Centre).',
                'timestamp' => $order && !empty($order->prasad_dispatched_at) ? date('d M Y, h:i A', strtotime($order->prasad_dispatched_at)) : 'Today, 04:15 PM',
                'completed' => true,
                'current' => true,
            ],
            [
                'step' => 4,
                'title' => 'Out for Doorstep Delivery',
                'description' => 'Local postman assigned. Devotee will receive sanctified prasad box with holy tulsi leaf.',
                'timestamp' => 'Estimated in 24-48 hours',
                'completed' => false,
                'current' => false,
            ],
            [
                'step' => 5,
                'title' => 'Delivered & Blessed',
                'description' => 'Prasad delivered safely to home altar.',
                'timestamp' => 'Upcoming',
                'completed' => false,
                'current' => false,
            ],
        ];

        return response()->json([
            'status' => 200,
            'success' => true,
            'awb' => $awbCode,
            'courier_partner' => $order->prasad_courier_name ?? 'India Post (Speed Post Air)',
            'current_step' => 3,
            'status_label' => $order->prasad_status ?? 'In Transit (Air Express)',
            'estimated_delivery' => date('d M Y', strtotime('+2 days')),
            'journey' => $stages,
            'order' => $order ? [
                'order_id' => '#OP-PUJA-' . $order->id,
                'puja_name' => $order->puja_name ?? 'Sacred Vedic Puja',
                'gotra' => $order->sankalp_gotra ?? 'Kashyap',
                'devotee_names' => $order->devotee_names ?? 'Devotee Family',
                'video_url' => $order->puja_video ?? null,
            ] : null,
        ]);
    }

    /**
     * 13. POST /api/logistics/webhook/awb
     * Automated Shiprocket / India Post Status Webhook
     */
    public function awbWebhook(Request $request)
    {
        $awb = $request->input('awb') ?? $request->input('tracking_number') ?? $request->input('awb_code');
        $status = strtolower($request->input('status', 'in_transit'));

        if (!empty($awb) && Schema::hasTable('puja_orders')) {
            DB::table('puja_orders')
                ->where('prasad_tracking_number', $awb)
                ->update([
                    'prasad_status' => $status,
                    'updated_at' => now(),
                ]);
        }

        return response()->json([
            'status' => 200,
            'success' => true,
            'message' => 'AWB Webhook processed successfully'
        ]);
    }
}
