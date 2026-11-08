<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Models\UserModel\User;
use App\Models\UserModel\UserOrder;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserOrderController extends Controller
{
 public function addUserOrder(Request $req)
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
                'productCategoryId',
                'productId',
                'orderAddressId',
                'payableAmount',
                'gstPercent',
                'paymentMethod',
                'totalPayable'
            );

            $validator = Validator::make($data, [
                'productCategoryId' => 'required',
                'productId' => 'required',
                'orderAddressId' => 'required',
                'payableAmount' => 'required',
                'paymentMethod' => 'required',
            ]);

            if ($validator->fails()) {
                if (DB::transactionLevel() > 0) { DB::rollback(); }
                return response()->json([
                    'error' => $validator->messages(),
                    'status' => 400,
                ], 400);
            }

            // $user_country=User::where('id',$id)->where('country','India')->first();
            $user_country=User::where('id',$id)->where('countryCode','+91')->first();
            $inr_usd_conv_rate = DB::table('systemflag')->where('name','UsdtoInr')->select('value')->first();
            $convRateVal = ($inr_usd_conv_rate && !empty($inr_usd_conv_rate->value)) ? $inr_usd_conv_rate->value : 83.5;

            // if($user_country){
            //     $req->payableAmount=convertinrtousd($req->payableAmount);
            //     $req->totalPayable=convertinrtousd($req->totalPayable);
            // }
            
            $req->payableAmount= $user_country ? $req->payableAmount : convertusdtoinr($req->payableAmount);
            $req->totalPayable= $user_country ? $req->totalPayable : convertusdtoinr($req->totalPayable);

            $wallet = DB::table('user_wallets')
            ->where('userId', '=', $id)
            ->get();
            if ($wallet->isEmpty()  || $wallet[0]->amount< $req->payableAmount) {
                return response()->json([
                    'message' => 'Insufficient Balance in Your Wallet !',
                    'status' => 400,
                ], 400);
            }

            $pro_reccommend=DB::table('product_recommends')->where('userId',$id)->where('productId',$req->productId)->where('recommDateTime', '>=', Carbon::now()->subDay())->latest()->first();


            $order = new UserOrder([
                'userId' => $id,
                'productCategoryId' => $req->productCategoryId,
                'productId' => $req->productId,
                'orderAddressId' => $req->orderAddressId,
                'pro_recommend_id'=> $pro_reccommend ? $pro_reccommend->id : null,
                'payableAmount' => $req->payableAmount,
                'orderType' => 'astromall',
                'gstPercent' => $req->gstPercent,
                'totalPayable' => $req->totalPayable,
                'payamentMethod' => $req->payamentMethod,
                'orderStatus' => 'Pending',
                'inr_usd_conversion_rate'=>$convRateVal,
                // 'created_at'=>Carbon::now(),
                // 'updated_at'=>Carbon::now(),
            ]);
            $order->created_at = now();
            $order->updated_at = now();
            $order->save();

            $wallet = DB::table('user_wallets')
                ->where('userId', '=', $id)
                ->get();
                $wallets = array(
                    // 'amount' => $wallet[0]->amount - ($user_country ? ($req->totalPayable * $inr_usd_conv_rate->value) : $req->totalPayable),
                    'amount' => $wallet[0]->amount - $req->totalPayable,
                );

            DB::table('user_wallets')
                ->where('id', $wallet[0]->id)
                ->update($wallets);

            $transaction = array(
                'userId' => $id,
                'amount' => $req->totalPayable,
                'isCredit' => false,
                "transactionType" => 'astromallOrder',
				'created_at' => Carbon::now(),
				'updated_at' => Carbon::now(),
                'inr_usd_conversion_rate'=>$convRateVal,
            );
            DB::table('wallettransaction')->insert($transaction);
            return response()->json([
                'message' => 'User Order add sucessfully',
                'recordList' => $order,
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

    public function cancelOrder(Request $req)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            } else {
                $id = Auth::guard('api')->user()->id;
            }

            // $user_country=User::where('id',$id)->where('country','India')->first();  // commented
            $user_country=User::where('id',$id)->where('countryCode','+91')->first();   // added
            $inr_usd_conv_rate = DB::table('systemflag')->where('name','UsdtoInr')->select('value')->first();
            $convRateVal = ($inr_usd_conv_rate && !empty($inr_usd_conv_rate->value)) ? $inr_usd_conv_rate->value : 83.5;

            $order = DB::table('order_request')->where('id', '=', $req->id)->get();
            $data = array(
                'orderStatus' => 'Cancelled',
            );
            DB::table('order_request')->where('id', '=', $req->id)->update($data);
            $wallet = DB::table('user_wallets')
                ->where('userId', '=', $id)
                ->get();
            $wallets = array(
                // 'amount' => $wallet[0]->amount + ($user_country ? ($order[0]->totalPayable * $inr_usd_conv_rate->value) : $order[0]->totalPayable),
                'amount' => $wallet[0]->amount + $order[0]->totalPayable,
            );
            DB::table('user_wallets')
                ->where('id', $wallet[0]->id)
                ->update($wallets);

            $transaction = array(
                'userId' => $id,
                'amount' => $order[0]->totalPayable,
                'isCredit' => true,
                "transactionType" => 'astromallOrder',
				'created_at' => Carbon::now(),
				'updated_at' => Carbon::now(),
                'inr_usd_conversion_rate'=>$convRateVal,
            );
            $res = array('totalPayable' => $order[0]->totalPayable);
            DB::table('wallettransaction')->insert($transaction);
            return response()->json([
                'message' => 'User Order Cancel sucessfully',
                'recordList' => [$res],
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
}
