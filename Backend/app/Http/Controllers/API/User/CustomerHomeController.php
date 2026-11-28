<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Models\AdminModel\Banner;
use App\Models\AstrologerModel\AstrologyVideo;
use App\Models\AstrologerModel\Blog;
use Illuminate\Http\Request;
use App\Models\UserModel\AstrotalkInNews;
use App\Models\UserModel\ProductCategory;
use Illuminate\Support\Facades\Auth;
use App\services\OneSignalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;


class CustomerHomeController extends Controller
{



 public function getCustomerHome(Request $req)
{
    try {
        // Helper closure to handle image URLs
        $formatImageUrl = function ($path) {
            if (empty($path)) return null;

            // If already a full URL (http/https), leave it as-is
            if (preg_match('/^https?:\/\//', $path)) {
                return $path;
            }

            // Otherwise convert to full URL using asset()
            return asset($path);
        };

        // Cache public sections for 5 minutes to accelerate app launch
        $staticSections = Cache::remember('customer_home_public_v1', 300, function () use ($formatImageUrl) {
            // Banner Section
            $banner = Banner::query()
                ->join('banner_types', 'banner_types.id', 'banners.bannerTypeId')
                ->where('banners.isActive', '1')
                ->whereDate('fromDate', '<=', Carbon::today())
                ->whereDate('ToDate', '>=', Carbon::today())
                ->limit(10)
                ->select('banners.*', 'banner_types.name as bannerType')
                ->orderBy('banners.id', 'DESC')
                ->get()
                ->map(function ($item) use ($formatImageUrl) {
                    $item->bannerImage = $formatImageUrl($item->bannerImage);
                    return $item;
                });

            // Blog Section
            $blog = Blog::query()
                ->where('isActive', '1')
                ->orderBy('id', 'DESC')
                ->limit(10)
                ->get()
                ->map(function ($item) use ($formatImageUrl) {
                    $item->blogImage = $formatImageUrl($item->blogImage ?? null);
                    $item->previewImage = $formatImageUrl($item->previewImage ?? null);
                    $item->coverImage = $formatImageUrl($item->coverImage ?? null);
                    return $item;
                });

            // Product Category Section
            $productCategory = ProductCategory::query()
                ->where('isActive', '1')
                ->orderBy('id', 'DESC')
                ->limit(10)
                ->get()
                ->map(function ($item) use ($formatImageUrl) {
                    $item->categoryImage = $formatImageUrl($item->categoryImage ?? null);
                    return $item;
                });

            // Astrotalk In News Section
            $astrotalkInNews = AstrotalkInNews::query()
                ->where('isActive', '1')
                ->orderBy('id', 'DESC')
                ->limit(10)
                ->get()
                ->map(function ($item) use ($formatImageUrl) {
                    $item->coverImage = $formatImageUrl($item->coverImage ?? null);
                    $item->bannerImage = $formatImageUrl($item->bannerImage ?? null);
                    return $item;
                });

            // Astrology Video Section
            $astrologyVideo = AstrologyVideo::query()
                ->where('isActive', '1')
                ->orderBy('id', 'DESC')
                ->limit(10)
                ->get()
                ->map(function ($item) use ($formatImageUrl) {
                    $item->previewImage = $formatImageUrl($item->previewImage ?? null);
                    $item->coverImage = $formatImageUrl($item->coverImage ?? null);
                    return $item;
                });

            return [
                'banner' => $banner,
                'blog' => $blog,
                'productCategory' => $productCategory,
                'astrotalkInNews' => $astrotalkInNews,
                'astrologyVideo' => $astrologyVideo,
            ];
        });

        $banner = $staticSections['banner'];
        $blog = $staticSections['blog'];
        $productCategory = $staticSections['productCategory'];
        $astrotalkInNews = $staticSections['astrotalkInNews'];
        $astrologyVideo = $staticSections['astrologyVideo'];

        // If user not logged in
        if (!Auth::guard('api')->user()) {
            return response()->json([
                'banner' => $banner,
                'blog' => $blog,
                'productCategory' => $productCategory,
                'astrotalkInNews' => $astrotalkInNews,
                'astrologyVideo' => $astrologyVideo,
                'status' => 200,
            ], 200);
        }

        // If logged in user
        $status = ['chat', 'call'];
        $id = Auth::guard('api')->user()->id;

        $topOrders = DB::table('order_request')
            ->join('astrologers', 'astrologers.id', '=', 'order_request.astrologerId')
            ->leftJoin('callrequest', function ($join) {
                $join->on('callrequest.id', '=', 'order_request.callId')
                     ->whereNotNull('order_request.callId');
            })
            ->where('order_request.userId', $id)
            ->whereIn('order_request.orderType', $status)
            ->select('order_request.*', 'astrologers.name as astrologerName', 'astrologers.profileImage', 'callrequest.call_type')
            ->orderBy('order_request.id', 'DESC')
            ->limit(3)
            ->get()
            ->map(function ($item) use ($formatImageUrl) {
                $item->profileImage = $formatImageUrl($item->profileImage ?? null);
                return $item;
            });

        if ($topOrders && count($topOrders) > 0) {
            foreach ($topOrders as $top) {
                if ($top->chatId != null) {
                    $chatId = DB::table('chatrequest')->where('id', $top->chatId)->value('chatId');
                    $top->firebaseChatId = $chatId;
                } elseif ($top->callId != null) {
                    $callChatId = DB::table('callrequest')->where('id', $top->callId)->value('chatId');
                    if ($callChatId) $top->firebaseChatId = $callChatId;
                }
            }
        }

        return response()->json([
            'banner' => $banner,
            'blog' => $blog,
            'productCategory' => $productCategory,
            'astrotalkInNews' => $astrotalkInNews,
            'astrologyVideo' => $astrologyVideo,
            'topOrders' => $topOrders,
            'status' => 200,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'error' => true,
            'message' => $e->getMessage(),
            'status' => 500,
        ], 500);
    }
}



























public function deleteAppointmentApi(Request $request)
    {
        try {
            // 🔹 Authentication check
            $user = Auth::guard('api')->user();
            if (!$user) {
                return response()->json([
                    'status' => 401,
                    'error' => true,
                    'message' => 'Unauthorized'
                ], 401);
            }

            // 🔹 Validate request
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 400,
                    'error' => true,
                    'message' => $validator->errors()->first()
                ], 400);
            }

            $userId = $user->id;
            $id = $request->id;

            // 🔹 Find appointment
            $appointment = DB::table('callrequest')
                ->where('id', $id)
                ->where('userId', $userId)
                ->first();

            if (!$appointment) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Appointment not found.'
                ], 404);
            }

            // 🔹 Check schedule restrictions
            if ($appointment->IsSchedule == 1 && $appointment->schedule_date && $appointment->schedule_time) {
                $scheduleDateTime = Carbon::parse($appointment->schedule_date . ' ' . $appointment->schedule_time);

                // Current time >= schedule time - 5 minutes
                if (Carbon::now()->greaterThanOrEqualTo($scheduleDateTime->subMinutes(5))) {
                    return response()->json([
                        'status' => 403,
                        'error' => true,
                        'message' => 'You cannot delete the appointment within 5 minutes of the scheduled time or after it.'
                    ], 403);
                }
            }

            // 🔹 Delete appointment
            DB::table('callrequest')->where('id', $id)->delete();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Appointment deleted successfully.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }



}
