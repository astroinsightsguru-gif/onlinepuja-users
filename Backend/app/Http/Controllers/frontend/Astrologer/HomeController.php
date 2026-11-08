<?php

namespace App\Http\Controllers\frontend\Astrologer;

use App\Http\Controllers\Controller;
use App\Models\UserModel\UserDeviceDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        if (!astroauthcheck()) {
            return redirect()->route('front.astrologerlogin');
        }

        //  dd(astroauthcheck()['astrologerId']);
        $astrologerId = astroauthcheck()['astrologerId'];
        // Artisan::call('cache:clear'); // Removed for performance

        $getChatRequest = app(\App\Http\Controllers\API\User\ChatRequestController::class)->getChatRequest(new Request(['astrologerId' => $astrologerId]))->getData(true);

        $getCallRequest = app(\App\Http\Controllers\API\User\CallRequestController::class)->getCallRequest(new Request(['astrologerId' => $astrologerId]))->getData(true);
        // return $getCallRequest;

        // dd($getCallRequest);
        $getUserReport = app(\App\Http\Controllers\API\User\UserReportController::class)->getUserReportRequest(new Request(['astrologerId' => $astrologerId]))->getData(true);

        $agoraAppIdValue = DB::table('systemflag')
            ->where('name', 'AgoraAppId')
            ->select('value')
            ->first();

        $agorcertificateValue = DB::table('systemflag')
            ->where('name', 'AgoraAppCertificate')
            ->select('value')
            ->first();

        $channel_name = 'onlinepujaGuruLive_' . astroauthcheck()['astrologerId'] . '';

        $getUserReportRequestById = app(\App\Http\Controllers\API\User\UserReportController::class)->getUserReportRequestById($request->id ?? 0)->getData(true);

        return view('frontend.astrologers.pages.index', compact('getChatRequest', 'getCallRequest', 'getUserReport', 'agoraAppIdValue', 'agorcertificateValue', 'channel_name', 'getUserReportRequestById'));
    }

    public function getChatRequests(Request $request)
    {
        // Artisan::call('cache:clear'); // Removed for performance
        $astrologerId = astroauthcheck()['astrologerId'];

        $getChatRequest = app(\App\Http\Controllers\API\User\ChatRequestController::class)->getChatRequest(new Request(['astrologerId' => $astrologerId]))->getData(true);

        return response()->json($getChatRequest);
    }

    public function getCallRequests(Request $request)
    {
        // Artisan::call('cache:clear'); // Removed for performance
        $astrologerId = astroauthcheck()['astrologerId'];
        $getCallRequest = app(\App\Http\Controllers\API\User\CallRequestController::class)->getCallRequest(new Request(['astrologerId' => $astrologerId]))->getData(true);

        return response()->json($getCallRequest);
    }

    public function getReportRequests(Request $request)
    {
        // Artisan::call('cache:clear'); // Removed for performance
        $astrologerId = astroauthcheck()['astrologerId'];
        $getUserReport = app(\App\Http\Controllers\API\User\UserReportController::class)->getUserReportRequest(new Request(['astrologerId' => $astrologerId]))->getData(true);

        return response()->json($getUserReport);
    }

    public function storeSubscriptionIdForAstro(Request $request)
    {
        if (astroauthcheck()) {
            $userId = astroauthcheck()['id'];
            // dd($userId);
            // Find the user's device details
            $userDeviceDetails = DB::table('user_device_details')->where('userId', $userId)->first();

            if ($userDeviceDetails) {
                DB::table('user_device_details')
                    ->where('userId', $userId)
                    ->update([
                        'subscription_id_web' => $request->subscription_id_web,
                        'updated_at' => now()
                    ]);
            } else {
                $userDeviceDetail = UserDeviceDetail::create([
                    'userId' => $userId,
                    'appId' => 1,
                    'subscription_id_web' => $request->subscription_id_web,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return response()->json(['message' => 'Subscription ID stored successfully.'], 200);
        }
    }

    public function astroAppointment(Request $request)
    {
        if (!astroauthcheck()) {
            return redirect()->route('front.astrologerlogin');
        }

        $astrologerId = astroauthcheck()['astrologerId'];

        $session = new \Symfony\Component\HttpFoundation\Session\Session();
        $token = $session->get('astrotoken');

        $appointments = DB::table('call_request_apoinments')
            ->join('callrequest', 'callrequest.id', '=', 'call_request_apoinments.callId')
            ->join('astrologers', 'astrologers.id', '=', 'call_request_apoinments.astrologerId')
            ->leftJoin('users', 'users.id', '=', 'call_request_apoinments.userId')
            ->where('call_request_apoinments.astrologerId', $astrologerId)
            ->select(
                // call_request_apoinments se
                'call_request_apoinments.id as id',
                'call_request_apoinments.callId',
                'call_request_apoinments.astrologerId',
                'call_request_apoinments.userId',
                'call_request_apoinments.amount',
                'call_request_apoinments.call_method',
                'call_request_apoinments.status as appointmentStatus',
                'call_request_apoinments.IsActive',
                'call_request_apoinments.created_at',
                'call_request_apoinments.updated_at',

                // callrequest se
                'callrequest.callStatus',
                'callrequest.IsSchedule',
                'callrequest.channelName',
                'callrequest.call_type',
                'callrequest.totalMin',
                'callrequest.call_duration',
                'callrequest.schedule_date',
                'callrequest.schedule_time',

                // astrologer info
                'astrologers.name as astrologerName',
                'astrologers.profileImage',

                // user info
                'users.name as userName',
                'users.profile as userProfile'
            )
            ->orderBy('call_request_apoinments.id', 'DESC')
            ->get();

        $agoraAppIdValue = DB::table('systemflag')
            ->where('name', 'AgoraAppId')
            ->select('value')
            ->first();

        $agorcertificateValue = DB::table('systemflag')
            ->where('name', 'AgoraAppCertificate')
            ->select('value')
            ->first();

        return view('frontend.astrologers.pages.astro-appointments', compact(
            'appointments',
            'token',
            'astrologerId',
            'agoraAppIdValue',
            'agorcertificateValue'
        ));
    }

    public function deleteAstroAppointment($id)
    {

        if (!astroauthcheck()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized. Please login again.'], 401);
        }

        $astrologerId = astroauthcheck()['astrologerId'];

        $appointment = DB::table('callrequest')
            ->where('id', $id)
            ->where('astrologerId', $astrologerId)
            ->first();

        if (!$appointment) {
            return response()->json(['status' => 'error', 'message' => 'Appointment not found.']);
        }

        $scheduleDateTime = \Carbon\Carbon::parse($appointment->schedule_date . ' ' . $appointment->schedule_time);
        $now = \Carbon\Carbon::now();
        $diffMinutes = $now->diffInMinutes($scheduleDateTime, false); // negative if past

        $duration = ($appointment->call_duration / 60) ?? 15;
        $endDateTime = $scheduleDateTime->copy()->addMinutes($duration);

        if ($now->greaterThan($endDateTime)) {
            // If the expired appointment is still pending, refund it
            $apoinmentDetails = DB::table('call_request_apoinments')
                ->where('callId', $id)
                ->first();

            if ($apoinmentDetails && $apoinmentDetails->status === 'Pending') {
                $currenttimestamp = \Carbon\Carbon::now();

                // 1. Update callrequest status to Rejected
                DB::table('callrequest')->where('id', $id)->update([
                    'callStatus' => 'Rejected',
                    'updated_at' => $currenttimestamp
                ]);

                // 2. Update call_request_apoinments status to Refunded
                DB::table('call_request_apoinments')->where('id', $apoinmentDetails->id)->update([
                    'status' => 'Refunded',
                    'updated_at' => $currenttimestamp
                ]);

                // 3. Add refunded amount to user's wallet
                $wallet = DB::table('user_wallets')->where('userId', $appointment->userId)->first();
                if ($wallet) {
                    DB::table('user_wallets')->where('userId', $appointment->userId)->update([
                        'amount' => (float)$wallet->amount + (float)$apoinmentDetails->amount,
                        'updated_at' => $currenttimestamp,
                        'modifiedBy' => $appointment->userId,
                    ]);
                } else {
                    DB::table('user_wallets')->insert([
                        'userId' => $appointment->userId,
                        'amount' => $apoinmentDetails->amount,
                        'isActive' => 1,
                        'isDelete' => 0,
                        'createdBy' => $appointment->userId,
                        'modifiedBy' => $appointment->userId,
                        'created_at' => $currenttimestamp,
                        'updated_at' => $currenttimestamp,
                    ]);
                }

                // 4. Insert refund record in wallettransaction table
                $inr_usd_conv_rate = DB::table('systemflag')->where('name', 'UsdtoInr')->select('value')->first();
                DB::table('wallettransaction')->insert([
                    'userId' => $appointment->userId,
                    'amount' => $apoinmentDetails->amount,
                    'isCredit' => true,
                    'transactionType' => 'Refund',
                    'callId' => $id,
                    'astrologerId' => $astrologerId,
                    'created_at' => $currenttimestamp,
                    'updated_at' => $currenttimestamp,
                    'inr_usd_conversion_rate' => $inr_usd_conv_rate ? $inr_usd_conv_rate->value : 1,
                ]);

                // 5. Send notification to user about the refund
                $astrologer = DB::table('astrologers')->where('id', $astrologerId)->first();
                $astrologerName = $astrologer ? $astrologer->name : 'Astrologer';
                DB::table('user_notifications')->insert([
                    'userId' => $appointment->userId,
                    'title' => 'Appointment Refunded',
                    'description' => 'Your expired appointment with ' . $astrologerName . ' has been refunded to your wallet.',
                    'notificationId' => null,
                    'createdBy' => $appointment->userId,
                    'modifiedBy' => $appointment->userId,
                    'notification_type' => 1,
                    'callRequestId' => $id,
                    'created_at' => $currenttimestamp,
                    'updated_at' => $currenttimestamp,
                ]);

                return response()->json(['status' => 'success', 'message' => 'Expired appointment refunded successfully.']);
            }

            // Otherwise, delete physically
            DB::table('call_request_apoinments')->where('callId', $id)->where('astrologerId', $astrologerId)->delete();
            DB::table('callrequest')->where('id', $id)->delete();
            return response()->json(['status' => 'success', 'message' => 'Expired appointment deleted successfully.']);
        } else {
            // Active and far in the future: REJECT & REFUND (do NOT delete physically)
            $apoinmentDetails = DB::table('call_request_apoinments')
                ->where('callId', $id)
                ->first();

            $currenttimestamp = \Carbon\Carbon::now();

            // 1. Update callrequest status to Rejected
            DB::table('callrequest')->where('id', $id)->update([
                'callStatus' => 'Rejected',
                'updated_at' => $currenttimestamp
            ]);

            // Insert notification for the user to trigger the popup on their side
            $astrologer = DB::table('astrologers')->where('id', $appointment->astrologerId)->first();
            $astrologerName = $astrologer ? $astrologer->name : 'Astrologer';

            $call_type = '';
            if ($appointment->call_type == '10') {
                $call_type = 'audio call';
            } else if ($appointment->call_type == '11') {
                $call_type = 'video call';
            } else {
                $call_type = 'call';
            }

            DB::table('user_notifications')->insert([
                'userId' => $appointment->userId,
                'title' => 'Appointment Rejected',
                'description' => 'Sorry, your scheduled ' . $call_type . ' appointment with ' . $astrologerName . ' has been rejected/cancelled by the astrologer.',
                'notificationId' => null,
                'createdBy' => $appointment->userId,
                'modifiedBy' => $appointment->userId,
                'notification_type' => 1,
                'callRequestId' => $id,
                'created_at' => $currenttimestamp,
                'updated_at' => $currenttimestamp,
            ]);

            if ($apoinmentDetails && $apoinmentDetails->status === 'Pending') {
                // 2. Update call_request_apoinments status to Refunded
                DB::table('call_request_apoinments')->where('id', $apoinmentDetails->id)->update([
                    'status' => 'Refunded',
                    'updated_at' => $currenttimestamp
                ]);

                // 3. Add refunded amount to user's wallet
                $wallet = DB::table('user_wallets')->where('userId', $appointment->userId)->first();
                if ($wallet) {
                    DB::table('user_wallets')->where('userId', $appointment->userId)->update([
                        'amount' => (float)$wallet->amount + (float)$apoinmentDetails->amount,
                        'updated_at' => $currenttimestamp,
                        'modifiedBy' => $appointment->userId,
                    ]);
                } else {
                    DB::table('user_wallets')->insert([
                        'userId' => $appointment->userId,
                        'amount' => $apoinmentDetails->amount,
                        'isActive' => 1,
                        'isDelete' => 0,
                        'createdBy' => $appointment->userId,
                        'modifiedBy' => $appointment->userId,
                        'created_at' => $currenttimestamp,
                        'updated_at' => $currenttimestamp,
                    ]);
                }

                // 4. Insert refund record in wallettransaction table
                $inr_usd_conv_rate = DB::table('systemflag')->where('name', 'UsdtoInr')->select('value')->first();
                DB::table('wallettransaction')->insert([
                    'userId' => $appointment->userId,
                    'amount' => $apoinmentDetails->amount,
                    'isCredit' => true,
                    'transactionType' => 'Refund',
                    'callId' => $id,
                    'astrologerId' => $appointment->astrologerId,
                    'created_at' => $currenttimestamp,
                    'updated_at' => $currenttimestamp,
                    'inr_usd_conversion_rate' => $inr_usd_conv_rate ? $inr_usd_conv_rate->value : 1,
                ]);
            }

            return response()->json(['status' => 'success', 'message' => 'Appointment rejected and refunded successfully.']);
        }
    }
}
