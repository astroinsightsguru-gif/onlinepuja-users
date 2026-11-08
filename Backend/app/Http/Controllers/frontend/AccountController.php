<?php

namespace App\Http\Controllers\frontend;

use App\Http\Controllers\Controller;
use App\Models\AdminModel\SystemFlag;
use App\Models\ReferralSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Session\Session;

class AccountController extends Controller
{
    public function getMyAccount(Request $request)
    {
        if(!authcheck())
            return redirect()->route('front.home');

        $userId = authcheck()['id'];
        $user = DB::table('users')->where('id', $userId)->first();
        if ($user) {
            $userWallet = DB::table('user_wallets')->where('userId', '=', $userId)->first();
            $user->totalWalletAmount = $userWallet ? $userWallet->amount : 0;
        }
        $getuserdetails = [
            'status' => 200,
            'message' => 'Get Profile Successfully',
            'userDetails' => (array) $user,
        ];

        $referral_settings = ReferralSetting::first();
        $currency = SystemFlag::where('name','currencySymbol')->first();

        return view('frontend.pages.my-account', [
            'getuserdetails' => $getuserdetails,
            'referral_settings' => $referral_settings,
            'currency' => $currency,
        ]);
    }

    public function deleteAccount(Request $req)
    {
        try {
            $userId = authcheck()['id'];
            DB::table('users')->where('id', $userId)->delete();

            return redirect()->route('front.home');
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function getMyFollowing(Request $request)
    {
        if(!authcheck())
            return redirect()->route('front.home');

        $userId = authcheck()['id'];
        $followers = DB::table('astrologer_followers')
            ->join('astrologers', 'astrologers.id', '=', 'astrologer_followers.astrologerId')
            ->where('astrologer_followers.userId', '=', $userId)
            ->select('astrologers.*')
            ->get();
        $getfollowing = [
            'status' => 200,
            'recordList' => $followers->map(fn($f) => (array)$f)->toArray()
        ];

        $getsystemflag = SystemFlag::all();
        $Chatsection = $getsystemflag->where('name', 'Chatsection')->first();
        $currency = $getsystemflag->where('name', 'currencySymbol')->first();

        return view('frontend.pages.my-following', [
            'getfollowing' => $getfollowing,
            'currency' => $currency,
            'Chatsection' => $Chatsection,
        ]);
    }

    #------------------------blocked astrologer--------------------------------
    public function getblockAstrologer(Request $request)
    {
        if(!authcheck())
            return redirect()->route('front.home');

        $userId = authcheck()['id'];
        $blocked = DB::table('blockastrologer')
            ->join('astrologers', 'astrologers.id', '=', 'blockastrologer.astrologerId')
            ->where('blockastrologer.userId', '=', $userId)
            ->select('astrologers.*')
            ->get();
        $getblockastro = [
            'status' => 200,
            'recordList' => $blocked->map(fn($b) => (array)$b)->toArray()
        ];

        $getsystemflag = SystemFlag::all();
        $currency = $getsystemflag->where('name', 'currencySymbol')->first();

        return view('frontend.pages.blocked-astrologer', [
            'getblockastro' => $getblockastro,
            'currency' => $currency,
        ]);
    }

    public function updateprofile(Request $request)
    {
        if (!authcheck()) {
            return response()->json(["status" => 401, "message" => "Unauthorized"], 401);
        }

        $userId = authcheck()["id"];
        $data = $request->only("name", "email", "contactNo", "gender", "birthDate", "birthTime", "birthPlace", "addressLine1", "addressLine2", "location", "pincode");

        if ($request->hasFile("profilepic")) {
            $image = $request->file("profilepic");
            $imageName = time() . "." . $image->getClientOriginalExtension();
            $image->move(public_path("storage/images"), $imageName);
            $data["profile"] = "images/" . $imageName;
        }

        DB::table("users")->where("id", $userId)->update(array_filter($data, fn($v) => !is_null($v)));

        return response()->json([
            "status" => 200,
            "message" => "Profile Updated Successfully",
        ], 200);
    }
}
