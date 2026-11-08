<?php

namespace App\Http\Controllers\frontend;

use App\Http\Controllers\Controller;
use App\Models\AdminModel\SystemFlag;
use App\Models\AstrologerModel\Astrologer;
use App\Models\PujaCategory;
use App\Models\Puja;
use App\Models\Pujafaq;
use App\Models\PujaOrder;
use App\Models\Pujapackage;
use App\Models\UserModel\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\UserModel\OrderAddress;
use App\Models\UserModel\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Session;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;

class PujaController extends Controller
{
   public function pujaCategory(Request $request)
    {
        $currentDatetime = \Carbon\Carbon::now();
        $pujaCategories = PujaCategory::where('isActive',1)->paginate(6);
        return view('frontend.pages.puja-category-list', compact('pujaCategories'));
    }
    public function pujaList(Request $request, $id)
{
    $currentDatetime = \Carbon\Carbon::now();

    $pujalists = Puja::where('puja_status', 1)
        ->where('category_id', $id)
        ->where('created_by', 'admin')
        ->where(function($query) use ($currentDatetime) {
            $query->where('puja_start_datetime', '>', $currentDatetime)
                  ->orWhereNull('puja_start_datetime')
                  ->orWhereNull('puja_end_datetime');
        })
        ->whereRaw('(puja_start_datetime IS NULL OR puja_end_datetime IS NULL OR puja_start_datetime != puja_end_datetime)')
        ->paginate(6);

    if ($pujalists->isEmpty()) {
        $pujalists = Puja::where('puja_status', 1)
            ->where('category_id', $id)
            ->where('created_by', 'admin')
            ->whereRaw('(puja_start_datetime IS NULL OR puja_end_datetime IS NULL OR puja_start_datetime != puja_end_datetime)')
            ->paginate(6);
    }

    return view('frontend.pages.puja-list', compact('pujalists'));
}


    #------------------------------------------------------------------------------------------------------------------------

    public function pujaDetails($slug)
    {

        if (request()->has('partner')) {
            session(['b2b_partner_code' => trim(request('partner'))]);
        }
        $puja = Puja::where('slug',$slug)->first();
        if (!$puja) {
            abort(404, 'Puja not found');
        }

        $latestPujalists = Puja::where('slug', '!=', $slug)->latest()->take(5)->get();


        $currency = SystemFlag::where('name', 'currencySymbol')->first();

        $package = $puja->package();

        $FAQ = Pujafaq::all();


        return view('frontend.pages.puja-details', compact('puja','latestPujalists', 'currency', 'package', 'FAQ'));
    }

    #-----------------------------------------------------------------------------------------------------------------------------------
    public function pujacheckout(Request $request,$slug,$id,$package_id=0)
    {
        // Artisan::call('cache:clear'); // Removed for performance
        if (!authcheck())
            return redirect()->route('front.home');

        $astrologer=DB::table('astrologers')->where('slug',$slug)->first();
        // dd($astrologer);
        $PujaDetails = Puja::findOrFail($id);
        if ($PujaDetails && $package_id!=0) {
            $PujaDetails->packages = $PujaDetails->package()->where('id',$package_id)->first();
        }

        // dd($PujaDetails->packages->id);
        $userId = authcheck()['id'];

        // $pujaAstrologer=PujaOrder::findOrFail($id);
        $getOrderAddressed = OrderAddress::query()
            ->where('userId', '=', $userId);
        // dd($getOrderAddressed);

        if ($s = $request->input(key: 's')) {
            $getOrderAddressed->whereRaw(sql: "name LIKE '%" . $s . "%' ");
        }
        $getOrderAddressed=$getOrderAddressed->get();

        $gstvalue = DB::table('systemflag')->where('name', 'Gst')->first();
        $currency = SystemFlag::where('name', 'currencySymbol')->first();
        // dd($PujaDetails);


        return view('frontend.pages.puja-checkout', [
            'PujaDetails' => $PujaDetails,
            // 'pujaAstrologer'=>$pujaAstrologer,
            'getOrderAddressed' => $getOrderAddressed,
            'gstvalue' => $gstvalue,
            'currency' => $currency,
            'astrologer' => $astrologer,


        ]);
    }

    #------------------------------------------------------------------------------------------------------------------------------------------------
       public function addUserPujaOrder(Request $req)
    {
        try {

            // dd($req->all());
            $data = $req->only(
                'pujaId',
                'orderAddressId',
                'astrologer_id',
            );

            $validator = Validator::make($data, [
                'pujaId' => 'required',
                'orderAddressId' => 'required',
            ]);

            if ($validator->fails()) {
                DB::rollback();
                return response()->json([
                    'error' => $validator->messages(),
                    'status' => 400,
                ], 400);
            }

            $id= authcheck()['id'];

            // $user_country=User::where('id',$id)->where('country','India')->first();  // commented
            $user_country = User::where('id', $id)->where('countryCode', '+91')->first(); // added
            $inr_usd_conv_rate = DB::table('systemflag')->where('name','UsdtoInr')->select('value')->first();


            // $Gstpersantage = DB::table('systemflag')
            // ->where('name', 'Gst')
            // ->select('value')
            // ->first();

            if($req->packageId){
                $Pujapackage = Pujapackage::findOrFail($req->packageId);
                $payableAmount=$Pujapackage->package_price;
                $payableAmount=$user_country ? $payableAmount : convertusdtoinr($payableAmount);
            }else{
                $payableAmount=$req->payableAmount;
                $payableAmount = str_replace(',', '', $payableAmount);
            }
           

            
        //    $gstAmount=number_format($Pujapackage->package_price * ($Gstpersantage->value / 100), 2);
        // $gstAmount=0;

           $totalPayable= number_format($payableAmount, 2);
            
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


            $wallet = DB::table('user_wallets')
            ->where('userId', '=', $id)
            ->get();


            if (!$wallet->isEmpty()  && $wallet[0]->amount>=$totalwalletchekpayable)
            {

                $order = PlacePujaOrder(['payment_type' => 'wallet','payableAmount'=>$payableAmount,'totalPayable'=>$totalPayable,...$req->all()],$id);
                if ($order && ($partnerCode = session('b2b_partner_code'))) {
                    try {
                        $b2bPartner = \App\Models\B2bLead::where('partner_code', $partnerCode)->first();
                        if ($b2bPartner) {
                            $commPct = $b2bPartner->commission_pct ?? 25;
                            $paid = (float) str_replace(',', '', $req['totalPayable'] ?? 0);
                            $b2bPartner->increment('total_bookings');
                            $b2bPartner->increment('total_revenue', round($paid * ($commPct / 100), 2));
                        }
                    } catch (\Throwable $e) {}
                }


                if($order)
                {
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
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                        'inr_usd_conversion_rate'=>$inr_usd_conv_rate->value,


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
                        'inr_usd_conversion_rate'=>$inr_usd_conv_rate->value,

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
                'createdBy' => $id,
                'modifiedBy' => $id,
            ]);

            $lastPayment = Payment::where('userId', $id)->latest()->first();

            $HttpSession = new HttpSession();

            $HttpSession->set('pujaOrderRequest',['payment_type' => 'online',...$req->all()]);

            return response()->json([
                    'message' => 'Pay Online.',
                    'redirect' => url('/') . "/payment?payid={$lastPayment->id}",
                    'status' => 200,
                ], 200);


        } catch (\Exception$e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }

    }
     #---------------------------------------------------------------------------------------------------------------------------------


     public function PujaLists(Request $request)
     {
         if(!astroauthcheck())
             return redirect()->route('front.astrologerlogin');

             $astrologerId=astroauthcheck()['astrologerId'];

             $astrologerPujaList = PujaOrder::with(['user', 'package'])->where('astrologer_id', $astrologerId)->orderBy('id','DESC')->get();

             $currency = SystemFlag::where('name', 'currencySymbol')->first();
             $totalPrice=0;

             $commission = DB::table(table: 'commissions')
                     ->where('commissionTypeId', '=', '6')
                     ->where('astrologerId', '=', $astrologerId)
                     ->get();


             foreach ($astrologerPujaList as $key => $value)
             {

                 $deduction = $value->order_price;

                 if ($commission && count($commission) > 0) {
                     $adminCommission = ($commission[0]->commission * $deduction) / 100;

                 } else {
                     $syscommission = DB::table('systemflag')->where('name', 'PujaCommission')->select('value')->get();

                     $adminCommission = ($syscommission[0]->value * $deduction) / 100;

                 }
                 $astrologerCommission = $deduction - $adminCommission;


                 $value->astrologerCommission = $astrologerCommission;
             }
            //  dd($value->astrologerCommission);

             return view('frontend.astrologers.pages.puja-list',compact('astrologerPujaList','currency'));
     }

    public function getMypujalist(Request $request)
    {
        if(!authcheck())
            return redirect()->route('front.home');

            $Id=authcheck()['id'];

            $astrologerPujaList = PujaOrder::with(['astrologer', 'package'])->where('user_id', $Id)->orderBy('id','DESC')->get();

            $currency = SystemFlag::where('name', 'currencySymbol')->first();

            return view('frontend.pages.my-puja-list',compact('astrologerPujaList','currency'));
    }


    public function myAstrologerPuja(Request $request)
    {
        if(!authcheck())
            return redirect()->route('front.home');

            $Id=authcheck()['id'];
            $astrologerPujaList = Puja::join('user_pujarequest_by_astrologers','user_pujarequest_by_astrologers.puja_id','pujas.id')->where('user_pujarequest_by_astrologers.userId',$Id)->join('astrologers','astrologers.id','user_pujarequest_by_astrologers.AstrologerId')->select('pujas.*','astrologers.name as astrologername','astrologers.slug as astrologerslug')
            ->where('pujas.puja_start_datetime', '>=', Carbon::now())->orderBy('user_pujarequest_by_astrologers.id','DESC')->get();
            $currency = SystemFlag::where('name', 'currencySymbol')->first();
            return view('frontend.pages.my-astrologer-puja',compact('astrologerPujaList','currency'));
    }


    // Get Puja Astrologers

    public function pujaAstrologerList(Request $request,$slug,$package_id)
    {

        if (request()->has('partner')) {
            session(['b2b_partner_code' => trim(request('partner'))]);
        }
        $puja = Puja::where('slug',$slug)->first();
        if (!$puja) {
            abort(404, 'Puja not found');
        }
        if ($puja) {
            $puja->packages = $puja->package()->where('id',$package_id)->first();
        }

        if(!$puja){
            return redirect()->back();
        }

        // dd($puja);

        $userId='';
		if(authcheck()){
		$userId=authcheck()['id'];
		}

        $session = new HttpSession();
        $token = $session->get('token');

        // Artisan::call('cache:clear'); // Removed for performance
        $sortBy = $request->sortBy;
        $astrologerCategoryId=(int)$request->astrologerCategoryId;
        $searchTerm = $request->input('s');

        // dd( $getAstrologer);
        $getAstrologerCategory = AstrologerCategory::where('isActive', 1)->get();

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

        return view('frontend.pages.puja-checkout-astrologer', [
            'getAstrologer' => $astrologer,
            'getAstrologerCategory' => $getAstrologerCategory,
            'sortBy' => $sortBy,
            'astrologerCategoryId' => $astrologerCategoryId,
            'searchTerm' => $searchTerm,
            'currency' => $currency,
            'userId' => $userId,
            'puja' => $puja,
        ]);

    }

}
