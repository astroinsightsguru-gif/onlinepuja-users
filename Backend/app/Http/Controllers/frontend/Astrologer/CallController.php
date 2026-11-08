<?php

namespace App\Http\Controllers\frontend\Astrologer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Session\Session;

class CallController extends Controller
{
    public function astrologercall(Request $request)
    {

        if(!astroauthcheck())
            return redirect()->route('front.astrologerlogin');

            $callrequest=DB::table('callrequest')->where('id',$request->callId)->first();

            if($callrequest->callStatus=='Completed' || $callrequest->callStatus=='Pending')
                return redirect()->route('front.astrologerindex');

            $session = new Session();
            $token = $session->get('astrotoken');

        // Artisan::call('cache:clear'); // Removed for performance


        $uRow = DB::table('users')->where('id', $callrequest->userId)->first();
        $getUser = ['status' => 200, 'recordList' => $uRow ? (array) $uRow : []];

        $agoraAppIdValue = DB::table('systemflag')
        ->where('name', 'AgoraAppId')
        ->select('value')
        ->first();



        return view('frontend.astrologers.pages.astrologer-callpage', [
            'getUser' => $getUser,
            'callrequest' => $callrequest,
            // 'getUserNotification' => $getUserNotification,
            'agoraAppIdValue' => $agoraAppIdValue,

        ]);
    }

    public function callStatus(Request $request)
    {
        $callId = $request->query('callId');

        $call =DB::table('callrequest')->where('id',$callId)->first();

        return response()->json(['call' => $call]);
    }
}
