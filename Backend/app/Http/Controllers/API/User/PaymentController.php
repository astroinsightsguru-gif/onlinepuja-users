<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\RechargeAmountResource;
use App\Models\UserModel\Payment;
use App\Models\UserModel\UserWallet;
use App\Models\RechargeAmount;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function addPayment(Request $req)
    {
        try {
            $user = Auth::guard('api')->user();
            if (!$user && $req->userId) {
                $user = \App\Models\UserModel\User::find($req->userId);
            }
            if (!$user) { return response()->json(['error' => 'Unauthenticated or user not found', 'status' => 401], 401); }

            $id = $user ? $user->id : 1;

            $data = $req->only(
                'amount',
                'cashback_amount',
                'gst_amount',
            );

            $validator = Validator::make($data, [
                'amount' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => $validator->messages(),
                    'status' => 400,
                ], 400);
            }

            $inr_usd_conv_rate = DB::table('systemflag')->where('name', 'UsdtoInr')->select('value')->first();

            $paymentAmount = $user->countryCode == '+91' ? $req->amount : convertusdtoinr($req->amount);
            $cashbackAmount = $user->countryCode == '+91' ? $req->cashback_amount : convertusdtoinr($req->cashback_amount);
            $gstAmount = $user->countryCode == '+91' ? $req->gst_amount : convertusdtoinr($req->gst_amount);

            // Create a new payment record
            $payment = Payment::create([
                // 'amount' => $req->amount,
                'amount' => $paymentAmount,
                // 'cashback_amount' => $req->cashback_amount,
                'cashback_amount' => $cashbackAmount,
                'gst_amount' => $gstAmount,
                'inr_usd_conversion_rate' => $inr_usd_conv_rate ? $inr_usd_conv_rate->value : 83.5,
                'userId' => $id,
                'paymentStatus' => 'pending',
                'createdBy' => $id,
                'modifiedBy' => $id,
                'payment_for' => $req->payment_for ? $req->payment_for : 'wallet',
                'durationchat' => $req->durationchat,
                'chatId' => $req->chatId,
                'callId' => $req->callId,
                'durationcall' => $req->durationcall,
            ]);
            $lastPayment = Payment::where('userId', $id)->latest()->first();

            return response()->json([
                'status' => 200,
                'message' => 'Click on url to add payment',
                'recordList' => $lastPayment,
                'url' => url('/') . "/payment?payid={$lastPayment->id}"
            ], 200);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function getRechargeAmount()
    {
        try {
            $rechargeAmount = RechargeAmount::orderBy('amount', 'ASC')->get();
            return response()->json([
                'recordList' => RechargeAmountResource::collection($rechargeAmount),
                'status' => 200,
                'message' => 'Recharge Amount Get Successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }
}
