<?php

namespace App\Http\Controllers\frontend;

use App\Http\Controllers\Controller;
use App\Models\AdminModel\SystemFlag;
use App\Models\UserModel\UserWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Session\Session;

class WalletController extends Controller
{
    public function getMyWallet(Request $request)
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

        $getsystemflag = SystemFlag::all();
        $currency = $getsystemflag->where('name', 'currencySymbol')->first();

        return view('frontend.pages.my-wallet', [
            'getUserById' => $getUserById,
            'getProfile' => $getProfile,
            'currency' => $currency,
        ]);
    }

    public function walletRecharge(Request $request)
    {
        if (!authcheck())
            return redirect()->route('front.home');

        $userId = authcheck()['id'];

        try {
            $res = app(\App\Http\Controllers\API\User\PaymentController::class)->getRechargeAmount();
            $getRechargeAmount = $res->getData(true);
        } catch (\Throwable $e) {
            $getRechargeAmount = ['status' => 200, 'recordList' => []];
        }

        $getsystemflag = SystemFlag::all();
        $gstvalue = $getsystemflag->where('name', 'Gst')->first();
        $currency = $getsystemflag->where('name', 'currencySymbol')->first();

        $getamount = collect($getRechargeAmount['recordList'] ?? []);
        $selectedamount = $getamount->first();

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

        return view('frontend.pages.wallet-recharge', [
            'getRechargeAmount' => $getRechargeAmount,
            'gstvalue' => $gstvalue,
            'currency' => $currency,
            'selectedamount' => $selectedamount,
            'getProfile' => $getProfile,
        ]);
    }


}
