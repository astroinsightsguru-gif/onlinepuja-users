<?php

namespace App\Http\Controllers\frontend\Astrologer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Session\Session;

class ProfileBoostController extends Controller
{
    public function history(Request $request)
    {
        try {
            if(!astroauthcheck()){
                return back()->with('error','Unauthenticated');
            }
            $session = new Session();
            $token = $session->get('astrotoken');
            $profileBoostHistory = app(\App\Http\Controllers\API\User\PujaController::class)->Profileboosthistory(new Request(['token' => $token, 'astrologer_id' => astroauthcheck()['astrologerId']]))->getData(true);
            return view('frontend.astrologers.pages.profile-boost', compact('profileBoostHistory'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

     public function profileBoostStore(Request $request)
    {
        try {
            if(!astroauthcheck()){
                return back()->with('error','Unauthenticated');
            }
            $session = new Session();
            $token = $session->get('astrotoken');
            $response = app(\App\Http\Controllers\API\User\PujaController::class)->boostProfile(new Request(['token' => $token, 'astrologer_id' => astroauthcheck()['astrologerId']]))->getData(true);
            if($response['status'] == 200){
                return response()->json([
                    'success' => true,
                    'message' => 'Profile boosted successfully!',
                ]);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => $response['error'],
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
