<?php

namespace App\Http\Controllers\frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\AdminModel\SystemFlag;
use App\Models\AstrologerModel\Astrologer;
use App\Models\AstrologerModel\AstrologerCategory;
use Symfony\Component\HttpFoundation\Session\Session;
use Carbon\Carbon;
use App\Models\UserModel\UserWallet;

class AstrologerChatController extends Controller
{
    public function chatList(Request $request)
    {
        $userId = '';
        if (authcheck()) {
            $userId = authcheck()['id'];
        }

        $session = new Session();
        $token = $session->get('token');

        // Artisan::call('cache:clear'); // Removed for performance

        $sortBy = $request->sortBy;
        $astrologerCategoryId = (int) $request->astrologerCategoryId;
        $searchTerm = $request->input('s');


        // getAstrologerCategory inlined

        $getAstrologerCategory = AstrologerCategory::where('isActive', 1)->get();

        try {
            $intakeData = DB::table('intakeform')->where('userId', '=', $userId)->get();
            $default_time = DB::table('systemflag')->where('name', '=', 'defaultcalltime')->value('value');
            $getIntakeForm = ['status' => 200, 'recordList' => $intakeData, 'default_time' => $default_time];
        } catch (\Throwable $e) {
            $getIntakeForm = ['status' => 200, 'recordList' => [], 'default_time' => null];
        }


        // Check if user is eligible for free chat
        $isFreeChat = DB::table('systemflag')->where('name', 'FirstFreeChat')->select('value')->first();
        $isFreeAvailable = true;

        if ($isFreeChat->value == 1 && $userId) {
            $hasCompletedChat = DB::table('chatrequest')
                ->where('userId', $userId)
                ->where('chatStatus', 'Completed')
                ->exists();

            $hasCompletedCall = DB::table('callrequest')
                ->where('userId', $userId)
                ->where('callStatus', 'Completed')
                ->exists();

            $isFreeAvailable = !($hasCompletedChat || $hasCompletedCall);
        } else {
            $isFreeAvailable = false;
        }

        $boostCutoff = Carbon::now()->subHours(24);

        // Query to get astrologers
        $astrologerQuery = Astrologer::query()
            ->select('astrologers.*', DB::raw('
            (CASE WHEN abp.astrologer_id IS NOT NULL THEN 1 ELSE 0 END) as is_boosted,
            COALESCE(AVG(user_reviews.rating), 0) as rating
        '))
            ->leftJoin('astrologer_boosted_profiles as abp', function ($join) use ($boostCutoff) {
                $join->on('astrologers.id', '=', 'abp.astrologer_id')
                    ->where('abp.boosted_datetime', '>=', $boostCutoff);
            })
            ->leftJoin('user_reviews', 'astrologers.id', '=', 'user_reviews.astrologerId')
            ->where([
                ['astrologers.isActive', true],
                ['astrologers.isVerified', true],
                ['astrologers.isDelete', false]
            ])
            ->whereNotIn('astrologers.id', function ($query) use ($request) {
                $query->select('astrologerId')
                    ->from('blockastrologer')
                    ->where('userId', '=', $request->userId);
            })
            ->groupBy('astrologers.id');

        // Apply category filter
        if ($astrologerCategoryId) {
            $astrologerQuery->whereRaw("FIND_IN_SET(?, astrologers.astrologerCategoryId)", [$astrologerCategoryId]);
        }

        // Sorting without switch or case
        $sortBy = $request->sortBy;

        if ($sortBy === 'experienceHighToLow') {
            $astrologerQuery->orderBy('astrologers.experienceInYears', 'DESC');
        } elseif ($sortBy === 'experienceLowToHigh') {
            $astrologerQuery->orderBy('astrologers.experienceInYears', 'ASC');
        } elseif ($sortBy === 'priceHighToLow') {
            $astrologerQuery->orderBy('astrologers.charge', 'DESC');
        } elseif ($sortBy === 'priceLowToHigh') {
            $astrologerQuery->orderBy('astrologers.charge', 'ASC');
        } else {
            // Default random + boosted + online + rating order
            $astrologerQuery->orderByRaw('
            CASE
                WHEN abp.astrologer_id IS NOT NULL THEN 1
                ELSE 2
            END
        ')
                ->orderByRaw('
            CASE
                WHEN astrologers.chatStatus = "Online" OR astrologers.callStatus = "Online" THEN 1
                ELSE 2
            END
        ')
                ->orderBy('rating', 'DESC');
        }

        // Search filter
        if ($s = $request->input('s')) {
            $astrologerQuery->where('name', 'LIKE', "%{$s}%");
        }

        // Apply pagination
        $astrologer = $astrologerQuery->paginate(15);


        foreach ($astrologer as $astro) {
            $astro->isFreeAvailable = $isFreeAvailable;

            // Fetch and count reviews
            $reviewCount = DB::table('user_reviews')
                ->where('astrologerId', $astro->id)
                ->count();
            $astro->reviews = $reviewCount;

            // Calculate average rating
            $astro->rating = DB::table('user_reviews')
                ->where('astrologerId', $astro->id)
                ->avg('rating') ?? 0;

            // Convert comma-separated values into arrays
            $astrologerCategoryIds = array_map('intval', explode(',', $astro->astrologerCategoryId));
            $allSkillIds = array_map('intval', explode(',', $astro->allSkill));
            $primarySkillIds = array_map('intval', explode(',', $astro->primarySkill));
            $languageIds = array_map('intval', explode(',', $astro->languageKnown));

            // Fetch related data
            $astro->astrologerCategory = DB::table('astrologer_categories')
                ->whereIn('id', $astrologerCategoryIds)
                ->pluck('name')
                ->implode(',');

            $astro->allSkill = DB::table('skills')
                ->whereIn('id', $allSkillIds)
                ->pluck('name')
                ->implode(',');

            $astro->primarySkill = DB::table('skills')
                ->whereIn('id', $primarySkillIds)
                ->pluck('name')
                ->implode(',');

            $astro->languageKnown = DB::table('languages')
                ->whereIn('id', $languageIds)
                ->pluck('languageName')
                ->implode(',');
        }

        if ($request->ajax()) {
            return response()->json([
                'getAstrologer' => $astrologer,
            ]);
        }

        $getsystemflag = SystemFlag::all();
        $currency = $getsystemflag->where('name', 'currencySymbol')->first();
        $Chatsection = $getsystemflag->where('name', 'Chatsection')->first();
        $userWalletAmount = 0;
        if (authcheck()) {
            $userWalletAmount = UserWallet::where('userId', authcheck()->id)->value('amount') ?? 0;
        }

        return view('frontend.pages.astrologer-chat-list', [
            'getAstrologer' => $astrologer,  // Kept as a paginated collection
            'getAstrologerCategory' => $getAstrologerCategory,
            'sortBy' => $sortBy,
            'astrologerCategoryId' => $astrologerCategoryId,
            'getIntakeForm' => $getIntakeForm,
            'isFreeAvailable' => $isFreeAvailable,
            'searchTerm' => $searchTerm,
            'currency' => $currency,
            'Chatsection' => $Chatsection,
            'userId' => $userId,
            'walletAmount' => $userWalletAmount
        ]);
    }


    public function chat(Request $request)
    {

        if (!authcheck())
            return redirect()->route('front.home');

        $chatrequest = DB::table('chatrequest')->where('userId', authcheck()['id'])->where('id', $request->chatId)->first();

        if ($chatrequest->chatStatus != 'Confirmed')
            return redirect()->route('front.home');

        $session = new Session();
        $token = $session->get('token');

        // Artisan::call('cache:clear'); // Removed for performance

        try {
            $apiReq = new Request(['astrologerId' => $request->astrologerId]);
            $res = app(\App\Http\Controllers\API\Astrologer\AstrologerController::class)->getAstrologerById($apiReq);
            $getAstrologer = $res->getData(true);
        } catch (\Throwable $e) {
            $getAstrologer = ['status' => 200, 'recordList' => []];
        }

        $userWalletAmount = 0;
        if (authcheck()) {
            $userWalletAmount = UserWallet::where('userId', authcheck()->id)->value('amount') ?? 0;
        }
        return view('frontend.pages.chatpage', [
            'getAstrologer' => $getAstrologer,
            'chatrequest' => $chatrequest,
            'walletAmount' => $userWalletAmount
        ]);
    }


    public function getMyChat(Request $request)
    {
        if (!authcheck())
            return redirect()->route('front.home');

        $userId = authcheck()['id'];

        try {
            $res = app(\App\Http\Controllers\API\Astrologer\AstrologerController::class)->getUserById(new Request(['userId' => $userId]));
            $getUserById = $res->getData(true);
        } catch (\Throwable $e) {
            $getUserById = ['status' => 200, 'recordList' => []];
        }

        $userWalletAmount = UserWallet::where('userId', $userId)->value('amount') ?? 0;
        $user = DB::table('users')->where('id', $userId)->first();
        $getProfile = [
            'status' => 200,
            'data' => [
                'id' => $user->id ?? $userId,
                'name' => $user->name ?? '',
                'contactNo' => $user->contactNo ?? '',
                'email' => $user->email ?? '',
                'totalWalletAmount' => (float) $userWalletAmount,
            ]
        ];

        $getsystemflag = \App\Models\AdminModel\SystemFlag::all();
        $currency = $getsystemflag->where('name', 'currencySymbol')->first();

        return view('frontend.pages.my-chats', [
            'getUserById' => $getUserById,
            'getProfile' => $getProfile,
            'currency' => $currency,
        ]);
    }


    public function getChatHistory(Request $request)
    {
        if (!authcheck())
            return redirect()->route('front.home');

        $userId = authcheck()['id'];

        try {
            $notifications = DB::table('user_notifications')->where('userId', $userId)->get();
            $getUserNotification = ['status' => 200, 'recordList' => $notifications];
        } catch (\Throwable $e) {
            $getUserNotification = ['status' => 200, 'recordList' => []];
        }

        try {
            $res = app(\App\Http\Controllers\API\Astrologer\AstrologerController::class)->getAstrologerById(new Request(['astrologerId' => $request->astrologerId]));
            $getAstrologer = $res->getData(true);
        } catch (\Throwable $e) {
            $getAstrologer = ['status' => 200, 'recordList' => []];
        }

        try {
            $res = app(\App\Http\Controllers\API\User\UserReviewController::class)->getUserHistoryReview(new Request(['userId' => $userId, 'astrologerId' => $request->astrologerId]));
            $getUserHistoryReview = $res->getData(true);
        } catch (\Throwable $e) {
            $getUserHistoryReview = ['status' => 200, 'recordList' => []];
        }

        return view('frontend.pages.chat-history', [
            'getAstrologer' => $getAstrologer,
            'getUserNotification' => $getUserNotification,
            'getUserHistoryReview' => $getUserHistoryReview,
        ]);
    }


    public function getDateTime(Request $request)
    {
        return Carbon::now()->format('Y-m-d H:i:s');
    }

    public function getMyAiChat(Request $request)
    {
        if (!authcheck())
            return redirect()->route('front.home');

        $userId = authcheck()['id'];

        try {
            $res = app(\App\Http\Controllers\API\Astrologer\AstrologerController::class)->getUserById(new Request(['userId' => $userId]));
            $getUserById = $res->getData(true);
        } catch (\Throwable $e) {
            $getUserById = ['status' => 200, 'recordList' => []];
        }

        $userWalletAmount = UserWallet::where('userId', $userId)->value('amount') ?? 0;
        $user = DB::table('users')->where('id', $userId)->first();
        $getProfile = [
            'status' => 200,
            'data' => [
                'id' => $user->id ?? $userId,
                'name' => $user->name ?? '',
                'contactNo' => $user->contactNo ?? '',
                'email' => $user->email ?? '',
                'totalWalletAmount' => (float) $userWalletAmount,
            ]
        ];

        $getsystemflag = \App\Models\AdminModel\SystemFlag::all();
        $currency = $getsystemflag->where('name', 'currencySymbol')->first();

        return view('frontend.pages.my-aichats', [
            'getUserById' => $getUserById,
            'getProfile' => $getProfile,
            'currency' => $currency,
        ]);
    }
}
