<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Models\Puja;
use App\Models\Pujafaq;
use Illuminate\Http\Request;
use App\Models\PujaCategory;
use App\Models\PujaSubCategory;
use App\Models\UserModel\Payment;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Pujapackage;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Illuminate\Support\Facades\Cache;
use App\Models\ProfileBoost;
use App\Models\ProfileBoosted;
use App\Models\PujaOrder;
use App\Models\UserModel\User;

class PujaController extends Controller
{
    public function getPujaCategory(Request $request)
    {
        try {
            $pujaCategory = PujaCategory::where('isActive', 1)->get();

            // Verified 200 OK sacred Vedic category images
            $categoryFallbacks = [
                1  => 'https://onlinepuja.live/public/storage/brain_media/brain_UggSzg4YoXyyO7ePfv_940x627.jpg',
                2  => 'https://onlinepuja.live/public/storage/brain_media/brain_S2GwG7tPc87uMlcAeM_940x627.jpg',
                3  => 'https://onlinepuja.live/public/storage/brain_media/brain_TZjr8HWEFQHtdO2zyn_940x627.jpg',
                4  => 'https://onlinepuja.live/public/storage/brain_media/brain_kPjZPlDTdbv3GCv2wS_1024x1024.jpg',
                5  => 'https://onlinepuja.live/public/storage/pujaCategory/pujaCategory_5_1764232801.jpg',
                6  => 'https://onlinepuja.live/public/storage/pujaCategory/pujaCategory_6_1764232830.png',
                7  => 'https://onlinepuja.live/public/storage/pujaCategory/pujaCategory_7_1764232867.jpg',
                8  => 'https://onlinepuja.live/public/storage/brain_media/brain_50wl7KY3nnT2jSnsnF_940x627.jpg',
                9  => 'https://onlinepuja.live/public/storage/brain_media/brain_R1RC6DtXvUJS7v3GJd_940x627.jpg',
                10 => 'https://onlinepuja.live/public/storage/brain_media/brain_GFb49381IDqvcIOJzp_940x627.jpg',
                11 => 'https://onlinepuja.live/public/storage/brain_media/brain_dIuvZYDuuGALCxuEJd_940x627.jpg',
                12 => 'https://onlinepuja.live/public/storage/brain_media/brain_5NY2JcMWHprAIADLpK_940x627.jpg',
                13 => 'https://onlinepuja.live/public/storage/brain_media/brain_u38hGyJyley0LgWAln_940x627.jpg',
                14 => 'https://onlinepuja.live/public/storage/brain_media/brain_EChhsPGJdQb5BCADsk_940x627.jpg',
                15 => 'https://onlinepuja.live/public/storage/brain_media/brain_50wl7KY3nnT2jSnsnF_940x627.jpg',
            ];

            // Convert image paths to full URL with verified 200 OK fallbacks
            foreach ($pujaCategory as $category) {
                if (empty($category->image) || str_contains($category->image, 'images/pujaCategory_') || str_contains($category->image, 'default/image')) {
                    $category->image = $categoryFallbacks[$category->id] ?? 'https://onlinepuja.live/public/frontend/homeimage/360.png';
                } elseif (!str_starts_with($category->image, 'http://') && !str_starts_with($category->image, 'https://')) {
                    $category->image = asset($category->image);
                }
            }

            return response()->json([
                'recordList' => $pujaCategory,
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }



    public function getPujaSubCategory(Request $request)
    {
        try {

            $data = $request->only('category_id');
            $validator = Validator::make($data, [
                'category_id' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }
            $pujaSubCategory = PujaSubCategory::where('category_id', $request->category_id)->where('isActive', 1)->get();

            return response()->json([
                'recordList' => $pujaSubCategory,
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    public function getPujaList(Request $request)
    {
        try {
            $currentDatetime = \Carbon\Carbon::now();

            $categoryId = $request->input('category_id');
            $hasCatFilter = !empty($categoryId) && $categoryId !== '0' && $categoryId !== 'all';

            // Build base query
            $buildQuery = function () use ($hasCatFilter, $categoryId) {
                $q = Puja::where('puja_status', 1)->where('created_by', 'admin');
                if ($hasCatFilter) {
                    $q->where('category_id', $categoryId);
                }
                return $q;
            };

            // Upcoming puja fetch
            $Pujalist = $buildQuery()
                ->where(function ($query) use ($currentDatetime) {
                    $query->where('puja_start_datetime', '>', $currentDatetime)
                        ->orWhereNull('puja_start_datetime')
                        ->orWhereNull('puja_end_datetime');
                })
                ->whereRaw('(puja_start_datetime IS NULL OR puja_end_datetime IS NULL OR puja_start_datetime != puja_end_datetime)')
                ->orderBy('puja_start_datetime', 'asc')
                ->get()
                ->filter(function ($puja) {
                    if ($puja->puja_start_datetime && $puja->puja_end_datetime && $puja->puja_start_datetime == $puja->puja_end_datetime) {
                        return false;
                    }
                    return true;
                });

            // Fallback if upcoming puja is empty
            if ($Pujalist->isEmpty()) {
                $Pujalist = $buildQuery()
                    ->whereRaw('(puja_start_datetime IS NULL OR puja_end_datetime IS NULL OR puja_start_datetime != puja_end_datetime)')
                    ->orderBy('id', 'desc')
                    ->get()
                    ->filter(function ($puja) {
                        if ($puja->puja_start_datetime && $puja->puja_end_datetime && $puja->puja_start_datetime == $puja->puja_end_datetime) {
                            return false;
                        }
                        return true;
                    });
            }

            // Map packages and asset URLs
            $Pujalist = $Pujalist->map(function ($puja) {
                $puja->packages = $puja->package();
                $cat = $puja->category;
                $puja->category_name = $cat ? $cat->name : '';

                if ($puja->puja_images && is_array($puja->puja_images)) {
                    $puja->puja_images = array_map(function ($image) {
                        return asset($image);
                    }, $puja->puja_images);
                }

                return $puja;
            })->values();

            return response()->json([
                'recordList' => $Pujalist,
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }



    public function getPujaDetails(Request $request)
    {
        return $this->getPujaDeatails($request);
    }

    public function getPujaDeatails(Request $request)
    {
        try {

            $identifier = $request->id ?? $request->slug;
            if (!$identifier) {
                return response()->json(['error' => 'Puja id or slug is required', 'status' => 400], 400);
            }

            $PujaDetails = Puja::where('id', $identifier)
                ->orWhere('slug', $identifier)
                ->first();
            if ($PujaDetails) {
                $PujaDetails->packages = $PujaDetails->package();
            }


            return response()->json([
                'recordList' => $PujaDetails,
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }


    #-------------------------------------------------------------------------------------------------------------------------------


    public function getPujafaq(Request $request)
    {
        try {
            $pujafaq = Pujafaq::all();

            return response()->json([
                'recordList' => $pujafaq,
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    #-------------------------------------------------------------------------------------------------------------------------------------

    public function placedPujaOrder(Request $req)
    {

        try {

            $user = Auth::guard('api')->user();
            if (!$user && $req->userId) {
                $user = User::find($req->userId);
            }
            if (!$user) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            }
            $id = $user->id;


            $data = $req->only(
                'pujaId',
                'orderAddressId',
                'packageId'

            );

            $validator = Validator::make($data, [
                'pujaId' => 'required',
                // 'packageId'=>'required',
                'orderAddressId' => 'required',

            ]);



            if ($validator->fails()) {
                if (DB::transactionLevel() > 0) { DB::rollback(); }
                return response()->json([
                    'error' => $validator->messages(),
                    'status' => 400,
                ], 400);
            }

            // $user_country=User::where('id',$id)->where('country','India')->first();  // commented
            $user_country = User::where('id', $id)->where('countryCode', '+91')->first(); // added
            $inr_usd_conv_rate = DB::table('systemflag')->where('name', 'UsdtoInr')->select('value')->first();
            $convRateVal = ($inr_usd_conv_rate && !empty($inr_usd_conv_rate->value)) ? $inr_usd_conv_rate->value : 83.5;


            // $Gstpersantage = DB::table('systemflag')
            // ->where('name', 'Gst')
            // ->select('value')
            // ->first();

            if ($req->packageId) {
                $Pujapackage = Pujapackage::findOrFail($req->packageId);
                $payableAmount = $Pujapackage->package_price;
                $payableAmount = $user_country ? $payableAmount : convertusdtoinr($payableAmount);
            } else {
                $payableAmount = $req->payableAmount;
                $payableAmount = str_replace(',', '', $payableAmount);
            }

            //    $gstPercent=number_format($Pujapackage->package_price * ($Gstpersantage->value / 100), 2);
            $totalPayable = number_format($payableAmount, 2);



            $req['payableAmount'] = str_replace(',', '', $payableAmount);
            $req['totalPayable'] = str_replace(',', '', $totalPayable);
            $totalwalletchekpayable = str_replace(',', '', $totalPayable);


            // commented by bhushan borse on 03 june 2025
            /*
            if($user_country){
                $req['payableAmount']=convertinrtousd($req['payableAmount']);
                $req['totalPayable']=convertinrtousd($req['totalPayable']);
            }
            */

            // $req['gstPercent'] =  $gstPercent;

            $wallet = DB::table('user_wallets')
                ->where('userId', '=', $id)
                ->get();

            // dd($wallet[0]->amount);

            if (!$wallet->isEmpty()  && $wallet[0]->amount >= $totalwalletchekpayable) {
                $order = PlacePujaOrder(['payment_type' => 'wallet', 'payableAmount' => $payableAmount, 'totalPayable' => $totalPayable, ...$req->all()], $id);

                if ($order) {
                    // Update user wallet balance
                    $wallet = DB::table('user_wallets')->where('userId', '=', $id)->first();
                    $walletData = [
                        // 'amount' => $wallet->amount - ($user_country ? ($req['totalPayable'] * $inr_usd_conv_rate->value) : $req['totalPayable']),
                        'amount' => $wallet->amount - ($req['totalPayable']),
                    ];

                    DB::table('user_wallets')->where('id', $wallet->id)->update($walletData);

                    // Prepare transaction data as an array
                    $orderRequest = array(
                        'userId' => $id,
                        'orderType' => 'puja',
                        'puja_id' => $req->pujaId,
                        'package_id' => $req->packageId,
                        'orderAddressId' => $req->orderAddressId,
                        'payableAmount' => $req['payableAmount'],
                        // 'gstPercent' => $Gstpersantage->value,
                        'totalPayable' => $req['totalPayable'],
                        'orderStatus' => 'Complete',
                        'inr_usd_conversion_rate' => $convRateVal,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),

                    );
                    DB::Table('order_request')->insert($orderRequest);
                    $Orderid = DB::getPdo()->lastInsertId();
                    // Prepare transaction data as an array
                    $transactionData = [
                        'userId' => $id,
                        'orderId' => $Orderid,
                        'amount' => $req['totalPayable'],
                        'isCredit' => false,
                        'transactionType' => 'pujaOrder',
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                        'inr_usd_conversion_rate' => $convRateVal,
                    ];
                    DB::table('wallettransaction')->insert($transactionData);


                    return response()->json([
                        'message' => 'Order Placed sucessfully!',
                        'recordList' => $order,
                        'status' => 200,
                    ], 200);
                }

                return response()->json([
                    'error' => false,
                    'message' => 'Order Failed!',
                    'status' => 500,
                ], 500);
            }

            // Create a new payment record
            $payment = Payment::create([
                // 'amount' => $user_country ? ($req['totalPayable'] * $inr_usd_conv_rate->value) : $req['totalPayable'],
                'amount' => $req['totalPayable'],
                'cashback_amount' => 0,
                'userId' => $id,
                'paymentStatus' => 'pending',
                'payment_for' => 'puja',
                'payment_order_info' => ['payment_type' => 'online', ...$req->all()],
                'createdBy' => $id,
                'modifiedBy' => $id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            $lastPayment = Payment::where('userId', $id)->latest()->first();

            $HttpSession = new HttpSession();

            $HttpSession->set('pujaOrderRequest', ['payment_type' => 'online', ...$req->all()]);


            return response()->json([
                'message' => 'Pay Online.',
                'redirect' => url('/') . "/payment?payid={$lastPayment->id}",
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    #-----------------------------------------------------------------------------------------------------------------------------------
    public function getProfileboost(Request $request)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            }
            $profileboostListing = ProfileBoost::first();

            $monthlyBoostCount = ProfileBoosted::where('astrologer_id', $request->astrologer_id)
                ->whereYear('boosted_datetime', Carbon::now()->year)
                ->whereMonth('boosted_datetime', Carbon::now()->month)
                ->count();

            $profileboostListing->remaining_boost = $profileboostListing->profile_boost - $monthlyBoostCount;

            return response()->json([
                'recordList' => $profileboostListing,
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }
    #-----------------------------------------------------------------------------------------------------------------------------------
    public function boostProfile(Request $request)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            }
            $data = $request->only(
                'astrologer_id'
            );

            $validator = Validator::make($data, [
                'astrologer_id' => 'required'
            ]);

            if ($validator->fails()) {
                DB::rollback();
                return response()->json([
                    'error' => $validator->messages(),
                    'status' => 400,
                ], 400);
            }

            $astrologerId = $request->astrologer_id;
            $lastBoostedProfile = ProfileBoosted::where('astrologer_id', $astrologerId)->latest()->first();
            if ($lastBoostedProfile) {
                $lastBoostedTime = Carbon::parse($lastBoostedProfile->boosted_datetime);
                if ($lastBoostedTime->diffInHours(Carbon::now()) < 24) {
                    return response()->json([
                        'error' => 'You can only boost your profile once after 24 hours.',
                        'status' => 400,
                    ], 400);
                }
            }


            $profileBoost = ProfileBoost::first();


            if (!$profileBoost) {
                return response()->json(['message' => 'Profile boost not found.'], 404);
            }
            $currentMonth = Carbon::now()->format('Y-m');
            $monthlyBoostCount = ProfileBoosted::where('astrologer_id', $astrologerId)
                ->whereYear('boosted_datetime', Carbon::now()->year)
                ->whereMonth('boosted_datetime', Carbon::now()->month)
                ->count();

            $monthlyBoostLimit = $profileBoost->profile_boost;



            if ($monthlyBoostCount >= $monthlyBoostLimit) {
                return response()->json([
                    'error' => 'You have exceeded your monthly boost limit.',
                    'status' => 400,
                ], 400);
            }


            // Create a new ProfileBoosted record
            $profileBoosted = ProfileBoosted::create([
                'astrologer_id' => $astrologerId,
                'chat_commission' => $profileBoost->chat_commission,
                'call_commission' => $profileBoost->call_commission,
                'video_call_commission' => $profileBoost->video_call_commission,
                'boosted_datetime' => Carbon::now(),
            ]);

            return response()->json([
                'massage' => 'Your Profile Boosted Successfully !',
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }
    #-----------------------------------------------------------------------------------------------------------------------------------------------------
    public function Profileboosthistory(Request $request)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            }
            $data = $request->only(
                'astrologer_id'
            );

            $validator = Validator::make($data, [
                'astrologer_id' => 'required'
            ]);

            if ($validator->fails()) {
                DB::rollback();
                return response()->json([
                    'error' => $validator->messages(),
                    'status' => 400,
                ], 400);
            }

            $profileboostListing = ProfileBoosted::where('astrologer_id', $request->astrologer_id)->latest()
                ->get();

            return response()->json([
                'recordList' => $profileboostListing,
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    // Get Puja Refund

    public function getPujaRefund(Request $request)
    {
        try {
            $data = $request->only('id');
            $validator = Validator::make($data, [
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }

            $pujaorder = DB::table('puja_orders')->where('id', $request->id)
                ->where('puja_order_status', 'placed')
                ->where('puja_start_datetime', '<', Carbon::now())
                ->where('puja_refund_status', false)
                ->first();

            if (!$pujaorder) {
                return response()->json([
                    'error' => 'Puja order not found, already refunded, or does not meet refund conditions.',
                    'status' => 404
                ], 404);
            }

            // $user_country = User::where('id', $pujaorder->user_id)->where('country', 'India')->first();  // commented
            $user_country = User::where('id', $pujaorder->user_id)->where('countryCode', '+91')->first(); // added
            $inr_usd_conv_rate = DB::table('systemflag')->where('name', 'UsdtoInr')->select('value')->first();
            $convRateVal = ($inr_usd_conv_rate && !empty($inr_usd_conv_rate->value)) ? $inr_usd_conv_rate->value : 83.5;
            $wallet = DB::table('user_wallets')
                ->where('userId', '=', $pujaorder->user_id)
                ->first();

            if (!$wallet) {
                return response()->json([
                    'error' => 'User wallet not found.',
                    'status' => 404
                ], 404);
            }
            // dd($user_country);
            $wallets = [
                // 'amount' => $wallet->amount + ($user_country ? ($pujaorder->order_total_price * $inr_usd_conv_rate->value) : $pujaorder->order_total_price),
                'amount' => $wallet->amount + $pujaorder->order_total_price,
            ];

            DB::table('user_wallets')
                ->where('id', $wallet->id)
                ->update($wallets);

            $transaction = [
                'userId' => $pujaorder->user_id,
                'amount' => $pujaorder->order_total_price,
                'isCredit' => true,
                "transactionType" => 'pujaRefund',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'inr_usd_conversion_rate' => $convRateVal,
            ];

            DB::table('wallettransaction')->insert($transaction);

            // Update puja_refund_status inside the function
            DB::table('puja_orders')
                ->where('id', $request->id)
                ->update([
                    'puja_refund_status' => true
                ]);

            return response()->json([
                'message' => 'Puja Amount Refunded Successfully',
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }
}
