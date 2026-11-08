<?php

namespace App\Http\Controllers\API\User;

use App\AgoraToken\RtmTokenBuilder;
use App\AgoraToken\RtcTokenBuilder;
use App\AgoraToken\RtmTokenBuilder2;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;


class TokenGeneratorController extends Controller
{

    private function resolveAgoraCredentials($req)
    {
        $appID = $req->appID;
        $appCertificate = $req->appCertificate;

        if (empty($appID)) {
            $appID = \DB::table('systemflag')->where('name', 'AgoraAppId')->value('value');
        }
        if (empty($appCertificate)) {
            $appCertificate = \DB::table('systemflag')->where('name', 'AgoraAppCertificate')->value('value');
        }

        return [$appID, $appCertificate];
    }

    #Old Way Of Rtm Token
    public function generateToken(Request $req)
    {
        try {
            [$appID, $appCertificate] = $this->resolveAgoraCredentials($req);
            $privilegeExpiredTs = Carbon::now()->timestamp + 7200;
            $rtmTokenController = new RtmTokenBuilder;
            $rtmToken = $rtmTokenController->buildToken($appID, $appCertificate, $req->user, $privilegeExpiredTs);

            return response()->json([
                'rtmToken' => $rtmToken,
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    #New Way Of Rtm Token
    public function generateRtmToken(Request $req)
    {
        try {
            [$appID, $appCertificate] = $this->resolveAgoraCredentials($req);
            $privilegeExpiredTs = Carbon::now()->timestamp + 7200;
            $rtmTokenController = new RtmTokenBuilder2;
            $rtmToken = $rtmTokenController->buildToken($appID, $appCertificate, $req->user, $privilegeExpiredTs);

            return response()->json([
                'rtmToken' => $rtmToken,
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function generateRtcToken(Request $req)
    {
        try {
            [$appID, $appCertificate] = $this->resolveAgoraCredentials($req);
            $privilegeExpiredTs = Carbon::now()->timestamp + 7200;
            $rtcTokenController = new RtcTokenBuilder;
            $rtcToken = $rtcTokenController->buildTokenWithUid($appID, $appCertificate, $req->channelName, $req->user, 1, $privilegeExpiredTs);
            return response()->json([
                'rtcToken' => $rtcToken,
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }
}
