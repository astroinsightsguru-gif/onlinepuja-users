<?php

namespace App\Http\Controllers\frontend;

use App\Http\Controllers\Controller;
use App\Models\AdminModel\SystemFlag;
use App\Models\AstrologerModel\Astrologer;
use App\Models\AstrologerModel\AstrologerCategory;
use App\Models\UserModel\UserWallet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Session\Session;

class ReportController extends Controller
{
    public function reportList(Request $request)
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

        // dd( $getAstrologer);
        $getAstrologerCategory = AstrologerCategory::where('isActive', 1)->get();
        try {
            $res = app(\App\Http\Controllers\API\User\ReportTypeController::class)->getReportTypes(new Request());
            $getReportType = $res->getData(true);
        } catch (\Throwable $e) {
            $getReportType = ['status' => 200, 'recordList' => []];
        }

        $boostCutoff = Carbon::now()->subHours(24);

        // Query to get astrologers
        $astrologerQuery = Astrologer::query()
            ->select('astrologers.*', DB::raw('
                (CASE WHEN abp.astrologer_id IS NOT NULL THEN 1 ELSE 0 END) as is_boosted,
                COALESCE(AVG(user_reviews.rating), 0) as rating
            '))
            ->leftJoin('astrologer_boosted_profiles as abp', function ($join) use ($boostCutoff) {
                $join
                    ->on('astrologers.id', '=', 'abp.astrologer_id')
                    ->where('abp.boosted_datetime', '>=', $boostCutoff);
            })
            ->leftJoin('user_reviews', 'astrologers.id', '=', 'user_reviews.astrologerId')
            ->where([
                ['astrologers.isActive', true],
                ['astrologers.isVerified', true],
                ['astrologers.isDelete', false]
            ])
            ->whereNotIn('astrologers.id', function ($query) use ($request) {
                $query
                    ->select('astrologerId')
                    ->from('blockastrologer')
                    ->where('userId', '=', $request->userId);
            })
            ->groupBy('astrologers.id');

        // Apply category filter
        if ($astrologerCategoryId) {
            $astrologerQuery->whereRaw('FIND_IN_SET(?, astrologers.astrologerCategoryId)', [$astrologerCategoryId]);
        }

        // Sorting options — placed at the END to override any earlier ordering
        $sortOptions = [
            'experienceHighToLow' => ['astrologers.experienceInYears', 'DESC'],
            'experienceLowToHigh' => ['astrologers.experienceInYears', 'ASC'],
            'priceHighToLow' => ['astrologers.reportRate', 'DESC'],
            'priceLowToHigh' => ['astrologers.reportRate', 'ASC']
        ];

        if ($request->sortBy && isset($sortOptions[$request->sortBy])) {
            [$column, $direction] = $sortOptions[$request->sortBy];
            $astrologerQuery->orderBy($column, $direction);
        } else {
            // Default random + boosted + online + rating order if no sorting is applied
            $astrologerQuery
                ->orderByRaw('
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

        // Removed redundant internal HTTP loopback
        $getsystemflag = SystemFlag::all();
        $currency = $getsystemflag->where('name', 'currencySymbol')->first();
        $walletAmount = auth()->check() ? UserWallet::where('userId', auth()->id())->value('amount') ?? 0 : 0;

        return view('frontend.pages.astrologer-report-list', [
            'getAstrologer' => $astrologer,
            'getAstrologerCategory' => $getAstrologerCategory,
            'sortBy' => $sortBy,
            'astrologerCategoryId' => $astrologerCategoryId,
            'searchTerm' => $searchTerm,
            'currency' => $currency,
            'getReportType' => $getReportType,
            'userId' => $userId,
            'walletAmount' => $walletAmount
        ]);
    }

    public function getMyReport(Request $request)
    {
        // Artisan::call('cache:clear'); // Removed for performance

        if (!authcheck())
            return redirect()->route('front.home');

        $userId = authcheck()['id'];

        try {
            $res = app(\App\Http\Controllers\API\Astrologer\AstrologerController::class)->getUserById(new Request(['userId' => $userId]));
            $getUserById = $res->getData(true);
        } catch (\Throwable $e) {
            $getUserById = ['status' => 200, 'recordList' => []];
        }

        $userWalletAmount = \App\Models\UserModel\UserWallet::where('userId', $userId)->value('amount') ?? 0;
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
        $currency = $getsystemflag->where('name', 'walletType')->first();

        return view('frontend.pages.my-report', [
            'getUserById' => $getUserById,
            'getProfile' => $getProfile,
            'currency' => $currency,
        ]);
    }
}
