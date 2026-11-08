<?php

namespace App\Http\Controllers\frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Session\Session;
use App\Models\UserModel\UserWallet;

class AstrologerController extends Controller
{
    public function astrologerDetails(Request $request,$slug)
    {
        $session = new Session();
        $token = $session->get('token');

    	$userId='';
		if(authcheck()){
		$userId=authcheck()['id'];
		}

        $getAstrologerId = DB::table('astrologers')->where('slug', $slug)->first();
        if (!$getAstrologerId && is_numeric($slug)) {
            $getAstrologerId = DB::table('astrologers')->where('id', (int) $slug)->first();
        }
        if (!$getAstrologerId) {
            abort(404, 'Astrologer profile not found');
        }
        $astrologerId = $getAstrologerId->id;

        try {
            $apiReq = new Request(['astrologerId' => $astrologerId, 'userId' => $userId, 'token' => $token]);
            $res = app(\App\Http\Controllers\API\Astrologer\AstrologerController::class)->getAstrologerById($apiReq);
            $getAstrologer = $res->getData(true);
        } catch (\Throwable $e) {
            $getAstrologer = ['status' => 200, 'recordList' => [(array) $getAstrologerId]];
        }

        try {
            $intakeData = DB::table('intakeform')->where('userId', '=', $userId)->get();
            $default_time = DB::table('systemflag')->where('name', '=', 'defaultcalltime')->value('value');
            $getIntakeForm = ['status' => 200, 'recordList' => $intakeData, 'default_time' => $default_time];
        } catch (\Throwable $e) {
            $getIntakeForm = ['status' => 200, 'recordList' => [], 'default_time' => null];
        }

        $getfollower = DB::table('astrologer_followers')
                ->where('userId', '=', $userId)
                ->where('astrologerId', '=', $astrologerId)
                ->first();


        $session = DB::table('callrequest')
        ->where('userId', '=', $userId)
        ->where('astrologerId', '=', $astrologerId)
        ->where('callStatus', '=', 'Pending')
        ->get();
        $isCallSessionavailable = true;
        if ($session && count($session) > 0) {
            $isCallSessionavailable = false;
        }

        $session = DB::table('chatrequest')
        ->where('userId', '=', $userId)
        ->where('astrologerId', '=', $astrologerId)
        ->where('chatStatus', '=', 'Pending')
        ->get();
            $isChatSessionavailable = true;
            if ($session && count($session) > 0) {
                $isChatSessionavailable = false;
            }


        try {
            $apiReq = new Request();
            $res = app(\App\Http\Controllers\API\User\GiftController::class)->getGifts($apiReq);
            $getGift = $res->getData(true);
        } catch (\Throwable $e) {
            $getGift = ['status' => 200, 'recordList' => []];
        }

        $getsystemflag = \App\Models\AdminModel\SystemFlag::all();
        $currency = $getsystemflag->where('name', 'currencySymbol')->first();
        $chatsection = $getsystemflag->where('name', 'Chatsection')->first();
        $callsection = $getsystemflag->where('name', 'Callsection')->first();


         $userWalletAmount = 0;
        if (authcheck()) {
            $userWalletAmount = UserWallet::where('userId', authcheck()->id)->value('amount') ?? 0;
        }
        return view('frontend.pages.astrologer-details', [
            'getAstrologer' => $getAstrologer,
            'getGift' => $getGift,
            'getIntakeForm' => $getIntakeForm,
            'isCallSessionavailable' => $isCallSessionavailable,
            'isChatSessionavailable' => $isChatSessionavailable,
            'currency' => $currency,
            'chatsection' => $chatsection,
            'callsection' => $callsection,
            'getfollower'=> $getfollower,
            'walletAmount' => $userWalletAmount

        ]);
    }

    public function getLiveAstro(Request $request)
    {
        try {
            $res = app(\App\Http\Controllers\API\User\LiveAstroController::class)->getLiveAstrologer($request);
            $liveastro = $res->getData(true);
        } catch (\Throwable $e) {
            $liveastro = ['status' => 200, 'recordList' => []];
        }

        return view('frontend.pages.live-astrologers', [
            'liveastro' => $liveastro,
        ]);
    }

    public function LiveAstroDetails(Request $request)
    {
        $liveAstrologer = DB::table('liveastro')
            ->join('astrologers', 'astrologers.id', '=', 'liveastro.astrologerId')
            ->where('liveastro.isActive', '=', true)
            ->select('astrologers.name', 'astrologers.profileImage', 'liveastro.*', 'astrologers.charge', 'astrologers.videoCallRate')
            ->orderBy('id', 'DESC')
            ->where('liveastro.astrologerId', $request->astrologerId)
            ->first();

        if (!$liveAstrologer) {
            return redirect()->route('front.home');
        }

        $wallet_amount = '';
        if (authcheck()) {
            $userWalletAmount = UserWallet::where('userId', authcheck()->id)->value('amount') ?? 0;
            $wallet_amount = $userWalletAmount;
        }

        try {
            $apiReq = new Request();
            $res = app(\App\Http\Controllers\API\User\GiftController::class)->getGifts($apiReq);
            $getGift = $res->getData(true);
        } catch (\Throwable $e) {
            $getGift = ['status' => 200, 'recordList' => []];
        }

        $recordList = \App\Models\AdminModel\SystemFlag::all();

        $agoraAppIdValue = $agorcertificateValue = $agorsecretValue = null;

        foreach ($recordList as $item) {
            switch ($item->name ?? $item['name'] ?? '') {
                case 'AgoraAppId':
                    $agoraAppIdValue = $item->value ?? $item['value'] ?? null;
                    break;
                case 'AgoraAppCertificate':
                    $agorcertificateValue = $item->value ?? $item['value'] ?? null;
                    break;
                case 'AgoraSecret':
                    $agorsecretValue = $item->value ?? $item['value'] ?? null;
                    break;
            }
        }

        try {
            $userReq = new Request(['channelName' => $liveAstrologer->channelName]);
            $res = app(\App\Http\Controllers\API\User\LiveUserController::class)->getLiveUser($userReq);
            $getLiveUser = $res->getData(true);
        } catch (\Throwable $e) {
            $getLiveUser = ['status' => 200, 'recordList' => []];
        }

        $id = '';
        if (authcheck()) {
            $id = authcheck()['id'];
        }

        try {
            $tokenReq = new Request([
                'appID' => $agoraAppIdValue,
                'appCertificate' => $agorcertificateValue,
                'user' => 'liveAstrologer_' . $id,
                'channelName' => $liveAstrologer->channelName
            ]);
            $res = app(\App\Http\Controllers\API\User\TokenGeneratorController::class)->generateRtmToken($tokenReq);
            $RtmToken = $res->getData(true);
        } catch (\Throwable $e) {
            $RtmToken = null;
        }

        $currency = $recordList->where('name', 'currencySymbol')->first();

        return view('frontend.pages.live-astrologer-details', [
            'liveAstrologer' => $liveAstrologer,
            'wallet_amount' => $wallet_amount,
            'getGift' => $getGift,
            'agoraAppIdValue' => $agoraAppIdValue,
            'getLiveUser' => $getLiveUser,
            'RtmToken' => $RtmToken,
            'currency' => $currency,
        ]);
    }
}
