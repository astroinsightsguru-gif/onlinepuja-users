<?php

namespace App\Http\Controllers\frontend\Astrologer;

use App\Http\Controllers\Controller;
use App\Models\Puja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Models\UserModel\AstromallProduct;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Session\Session;

class ChatController extends Controller
{
    public function astrologerchat(Request $request)
    {

        if(!astroauthcheck())
            return redirect()->route('front.astrologerlogin');

        $chatrequest=DB::table('chatrequest')->where('userId',$request->partnerId)->where('id',$request->chatId)->first();


            $session = new Session();
            $token = $session->get('astrotoken');

        // Artisan::call('cache:clear'); // Removed for performance

        $uRow = DB::table('users')->where('id', $request->partnerId)->first();
        $getUser = ['status' => 200, 'recordList' => $uRow ? (array) $uRow : []];

        $astromallProduct = AstromallProduct::query();
        $astromallProduct->where('isActive', '=', true);
        $astromallProduct->where('isDelete', '=', false);
        if ($s = $request->input(key:'s')) {
            $astromallProduct->whereRaw(sql:"name LIKE '%" . $s . "%' ");
        }
        $astromallProduct= $astromallProduct->get();


        $getsystemflag = DB::table('systemflag')
        ->get();
        $currency = $getsystemflag->where('name', 'currencySymbol')->first();

        $currentDatetime = \Carbon\Carbon::now();
        $pujalists = Puja::where('puja_status', 1)->where('puja_start_datetime','>',$currentDatetime)->where('isAdminApproved','Approved')->where('created_by','astrologer')->get();


        return view('frontend.astrologers.pages.astrologer-chatpage', [
            // 'getAstrologer' => $getAstrologer,
            'chatrequest' => $chatrequest,
            // 'getUserNotification' => $getUserNotification,
            'getUser' => $getUser,
            'astromallProduct'=>$astromallProduct,
            'currency'=>$currency,
            'pujalists'=>$pujalists,
        ]);
    }

    public function chatStatus(Request $request)
    {
        $chatId = $request->query('chatId');
        $chat =DB::table('chatrequest')->where('id',$chatId)->first();
        return response()->json(['chatStatus' => $chat->chatStatus]);
    }
}
