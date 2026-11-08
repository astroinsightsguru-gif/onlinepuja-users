<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Models\AstrologerModel\Astrologer;
use App\Models\UserModel\CallRequest;
use App\services\FCMService;
use Carbon\Carbon;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ProfileBoost;
use App\Models\ProfileBoosted;
use App\Models\UserModel\User;
use App\services\OneSignalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use App\Models\UserModel\ChatRequest;
use App\services\HundredMsService;
use App\services\ZegoCloudService;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use App\AgoraToken\RtcTokenBuilder;
use Illuminate\Support\Facades\File;
use App\Helpers\StorageHelper;


class CallRequestController extends Controller
{

    protected $zegoApi;
    protected $hundredMsService;

    public function __construct(ZegoCloudService $zegoApi, HundredMsService $hundredMsService)
    {
        $this->zegoApi = $zegoApi;
        $this->hundredMsService = $hundredMsService;
    }


public function addCallRequest(Request $req)
    {
        try {
            $user = Auth::guard('api')->user();
            $id = $user ? $user->id : ($req->userId ? (int)$req->userId : ($req->input('userId') ? (int)$req->input('userId') : null));
            if (!$id) {
                $id = 1;
            }


            $data = $req->only(
                'astrologerId',
                'call_duration',
                'IsSchedule',
                'schedule_date',
                'schedule_time'
            );

            $validator = Validator::make($data, [
                'astrologerId' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }
            $reponse = $this->processCallRequest($id, $req);
            return $reponse;
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
                'error' => false,
            ], 500);
        }
    }


    private function processCallRequest($id, $req)
    {
        // dd($req);
        $IsSchedule = $req->IsSchedule ?? 0;

        $call_method = getCallMethod();
        // $methods = ['zegocloud', 'hms'];
        // $call_method = $methods[array_rand($methods)];

        if ($IsSchedule == 1) {
            // 1. Get Astrologer
            $astrologer = DB::table('astrologers')->where('id', $req['astrologerId'])->first();
            if (!$astrologer) {
                return response()->json([
                    'message' => 'Astrologer not found',
                    'status'  => 404
                ], 404);
            }

            $callRate = ($req['call_type'] == 11) ? $astrologer->videoCallRate : $astrologer->charge;

            // 2. Calculate total charge
            $callDurationMinutes = $req->call_duration / 60;
            $totalCharge = $callRate * $callDurationMinutes;

            // 3. Check User Wallet
            $wallet = DB::table('user_wallets')->where('userId', $id)->first();
            $walletAmount = $wallet ? (float) $wallet->amount : 0;

            if ($walletAmount < $totalCharge) {
                return response()->json([
                    'message' => 'Insufficient wallet balance to schedule this call.',
                    'requiredAmount' => $totalCharge,
                    'availableAmount' => $walletAmount,
                    'status' => 400
                ], 400);
            }

            // 4. Deduct wallet amount
            DB::table('user_wallets')->where('userId', $id)->update([
                'amount'     => $walletAmount - $totalCharge,
                'updated_at' => Carbon::now()
            ]);

            // 5. Create Call Request
            $callRequest = CallRequest::create([
                'astrologerId'   => $req['astrologerId'],
                'userId'         => $id,
                'callStatus'     => 'Pending',
                'created_at'     => Carbon::now(),
                'updated_at'     => Carbon::now(),
                'isFreeSession'  => $req['isFreeSession'] ?? 0,
                'call_type'      => $req['call_type'],
                'call_duration'  => $req['call_duration'],
                'call_method'    => $call_method,
                'is_emergency'   => 0,
                'IsSchedule'     => 1,
                'schedule_date'  => $req['schedule_date'],
                'schedule_time'  => $req['schedule_time'],
                'deduction'      => $totalCharge,
            ]);

            // Update AstroFreePaid = 1 for this astrologer
            DB::table('astrologers')->where('id', $req['astrologerId'])->increment('AstroFreePaid',1);
                
            // 6. Save deduction in call_request_apoinments
            DB::table('call_request_apoinments')->insert([
                'callId'        => $callRequest->id,
                'userId'        => $id,
                'astrologerId'  => $req['astrologerId'],
                'amount'        => $totalCharge,
                'call_duration' => $req['call_duration'],
                'call_method'   => $call_method,
                'status'        => 'Pending',
                'IsActive'      => 1,
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ]);

            return response()->json([
                'message' => 'Scheduled Call Appointment Created Successfully',
                'deductedAmount' => $totalCharge,
                'remainingWalletBalance' => $walletAmount - $totalCharge,
                'schedule' => [
                    'date' => $req['schedule_date'],
                    'time' => $req['schedule_time'],
                ],
                'status' => 200,
            ], 200);
        }

        // Instant Call Flow

        $isFreeChat = DB::table('systemflag')->where('name', 'FirstFreeChat')->select('value')->first();
        $isFreeAvailable = true;
        if ($isFreeChat->value == 1) {
            if ($id) {
                $isChatRequest = DB::table('chatrequest')->where('userId', $id)->where('chatStatus', '=', 'Completed')->first();
                $isCallRequest = DB::table('callrequest')->where('userId', $id)->where('callStatus', '=', 'Completed')->first();
                $isFreeAvailable = !($isChatRequest || $isCallRequest);
            }
        } else {
            $isFreeAvailable = false;
        }

        $professionTitle = DB::table('systemflag')->where('name', 'professionTitle')->value('value') ?? 'Partner';
        $astrologerOnlineOffline = DB::table('astrologers')->where('id', '=', $req->astrologerId)->first();
        if ($astrologerOnlineOffline->callStatus == "Offline" || $astrologerOnlineOffline->callStatus == "Busy") {
            if ($astrologerOnlineOffline->callStatus == "Offline" && !$astrologerOnlineOffline->emergencyCallStatus) {
                return response()->json([
                    'recordList' => [
                        'message' => 'Call request cannot be sent. The ' . $professionTitle . ' is currently unavailable.',
                    ],
                    'status' => 400,
                ], 400);
            }
        }

        $firstFreerecharge = DB::table('systemflag')->where('name', 'FirstFreeChatRecharge')->select('value')->first();
        $minAmount = DB::table('systemflag')->where('name', 'MinAmountFreeChatCall')->select('value')->first();
        $wallets = DB::table('user_wallets')->where('userId', $id)->first();
        $astrologerEmergency = DB::table('astrologers')->where('id', $req['astrologerId'])->value('emergencyCallStatus');

        $minAmountValue = (float) $minAmount->value;
        $walletAmount = $wallets ? (float) $wallets->amount : 0;

        if ($isFreeAvailable && $firstFreerecharge->value == 1 && ($walletAmount < $minAmountValue)) {
            return response()->json([
                'recordList' => [
                    'message' => 'Please Recharge First to use free call',
                    'minAmount' => (int) $minAmount->value
                ],
                'status' => 400,
            ], 400);
        }

        $callDurationMinutesforcharge = $req->call_duration / 60;
        $astrologerCharge = DB::table('astrologers')->where('id', $req['astrologerId'])->value('videoCallRate');
        if ($astrologerEmergency) {
            $astrologerCharge = DB::table('astrologers')->where('id', $req['astrologerId'])->value('emergency_audio_charge');
            if ($req['call_type'] == 11) {
                $astrologerCharge = DB::table('astrologers')->where('id', $req['astrologerId'])->value('emergency_video_charge');
            }
        }

        $total_charge = $astrologerCharge * $callDurationMinutesforcharge;
        if ($total_charge > $walletAmount) {
            return response()->json([
                'recordList' => [
                    'message' => 'Insufficient Wallet Balance',
                ],
                'status' => 400,
            ], 400);
        }

        $apiKey = DB::table('systemflag')->where('name', 'ExotelKey')->pluck('value')->first();
        $apiToken = DB::table('systemflag')->where('name', 'ExotelToken')->pluck('value')->first();
        $subdomain = DB::table('systemflag')->where('name', 'ExotelSubdomain')->pluck('value')->first();
        $sid = DB::table('systemflag')->where('name', 'ExotelSid')->pluck('value')->first();
        $callerId = DB::table('systemflag')->where('name', 'ExotelCallerId')->pluck('value')->first();

        $userNo = DB::table('users')->where('id', $id)->pluck('contactNo')->first();
        $astrologerNo = DB::table('astrologers')->where('id', $req['astrologerId'])->pluck('contactNo')->first();
        $max_request = DB::table('systemflag')->where('name', 'MaxRequestNumber')->select('value')->first();

        if ($apiKey && $apiToken && $subdomain && $sid && $callerId && $req['call_type'] == '10' && $userNo && $astrologerNo) {
            $call_method = "exotel";
        }

        // Count pending call requests
        $pendingChatRequests = ChatRequest::where('userId', $id)
            ->where('chatStatus', 'Pending')
            ->count();

        // Count pending call requests
        $pendingCallRequests = CallRequest::where('userId', $id)
            ->where('callStatus', 'Pending')
            ->count();

        // Calculate total pending requests
        $totalPendingRequests = $pendingChatRequests + $pendingCallRequests;

        if ($totalPendingRequests >= $max_request->value) {
            return response()->json([
                'recordList' => [
                    'message' => 'You cannot send more than ' . $max_request->value . ' requests',
                ],
                'status' => 400,
            ], 400);
        }

        // Create Call Request
        $callrequestdata = CallRequest::create([
            'astrologerId' => $req['astrologerId'],
            'userId' => $id,
            'callStatus' => 'Pending',
            'isFreeSession' => $req['isFreeSession'] ?? 0,
            'call_type' => $req['call_type'],
            'call_duration' => $req['call_duration'],
            'call_method' => $call_method,
            'is_emergency' => $astrologerEmergency ?? 0,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'validated_till' => Carbon::now()->addMinute(5),
            'IsSchedule' => 0,
        ]);

        // Update AstroFreePaid = 1 for this astrologer
        // DB::table('astrologers')->where('id', $req['astrologerId'])->update(['AstroFreePaid' => 1]);

        DB::table('astrologers')
            ->where('id', $req['astrologerId'])
            ->update([
                'AstroFreePaid' => DB::raw('COALESCE(AstroFreePaid, 0) + 1'),
                'updated_at'    => now(),
            ]);

        // Send Instant Notification to Astrologer
        $astrologer = DB::table('astrologers')->where('id', $req['astrologerId'])->first();
        $astrologerUser = DB::table('users')->where('id', $astrologer->userId)->first();
        $user = DB::table('users')->where('id', $id)->first();

        // Astrologer ke devices
        $astrologerDevices = DB::table('user_device_details')->where('userId', $astrologer->userId)->get();
        // User ke devices (fcmToken ke liye)
        $userDevices = DB::table('user_device_details')->where('userId', $user->id)->get();

        $blankimg = asset('public/blank.png');
  $call='';
            if($req['call_type']=='10'){
                $call='audio call';
            }else if($req['call_type']=='11'){
                $call='video call';
            }


            $blankimg='/build/assets/images/person.png';


			//$user = DB::table('users')->where('id', '=', $id)->select('name')->get();
			 $user = DB::table('users')->where('users.id', '=', $id)
            ->join('user_device_details', 'user_device_details.userId', 'users.id')
            ->select('users.id','users.name','users.profile','user_device_details.fcmToken')
            ->get();



			  $callrequestdata=CallRequest::latest()->first();

            $userDeviceDetail = DB::table('user_device_details')
            ->JOIN('astrologers', 'astrologers.userId', '=', 'user_device_details.userId')
            ->WHERE('astrologers.id', '=', $req->astrologerId)
            ->SELECT('user_device_details.*','astrologers.userId as astrologerUserId', 'astrologers.name')
            ->get();



            $astrologer = DB::Table('astrologers')
                ->leftjoin('user_device_details', 'user_device_details.userId', 'astrologers.userId')
                ->where('astrologers.id', '=', $callrequestdata->astrologerId)
                ->select('astrologers.name', 'astrologers.profileImage', 'user_device_details.fcmToken')
                ->get();



            if ($userDeviceDetail && count($userDeviceDetail) > 0) {
                FCMService::send(
                    $userDeviceDetail,
                    [
                        'title' => 'Hey '.$userDeviceDetail[0]->name.', you received a '.$call.' request from ' . $user[0]->name,
						'body' => [
                            "notificationType" => 2,
                            "id" => $user[0]->id,
                            "name" => $user[0]->name?$user[0]->name:'User',
                            "profile" => $user[0]->profile ? $user[0]->profile:$blankimg,
                            "token" => $callrequestdata->token,
                            "callId" => $callrequestdata->id,
                            "call_type" => $callrequestdata->call_type,
                            "call_duration" => $req['call_duration'],
                            'fcmToken' => $user[0]->fcmToken,
                            'description' => 'Hey '.$userDeviceDetail[0]->name.', you received a '.$call.' request from ' . $user[0]->name,
                            'icon' => asset('public/notification-icon/telephone-call.png'),
                            'call_method'=>$call_method??'agora',
                        ],
                    ]
                );


				 $notification = array(
                    'userId' => $userDeviceDetail[0]->astrologerUserId,
                    'title' => 'Receive '.ucwords($call).'  ',
                    'description' => 'Hey '.$userDeviceDetail[0]->name.', you received '.$call.' request from ' . $user[0]->name,
                    'notificationId' => null,
                    'createdBy' => $userDeviceDetail[0]->astrologerUserId,
                    'modifiedBy' => $userDeviceDetail[0]->astrologerUserId,
                    'notification_type' => 1,
					 'callRequestId' => $callrequestdata->id,
                     'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                );
                DB::table('user_notifications')->insert($notification);
            }

            return response()->json([
                'message' => 'Instant Call Request Sent Successfully',
				'callId' => $callrequestdata->id,
				'call_method' => $call_method,
                'status' => 200,
            ], 200);
    }


    public function randomCall(Request $request)
{
    try {
        // Check if user is authenticated
        $user = Auth::guard('api')->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
        }

        $userId = $user->id;

        // Check if user already has pending or completed chat/call
        $hasChat = DB::table('chatrequest')
            ->where('userId', $userId)
            ->whereIn('chatStatus', ['Completed', 'Pending'])
            ->exists();

        $hasCall = DB::table('callrequest')
            ->where('userId', $userId)
            ->whereIn('callStatus', ['Completed', 'Pending'])
            ->exists();

        if ($hasChat || $hasCall) {
            return response()->json([
                'message' => 'You already have an active chat or call request.',
                'status' => 400
            ], 400);
        }

        // Get maximum concurrent chat/call limit from systemflag
        $maxChatCallLimit = (int) DB::table('systemflag')
            ->where('name', 'MaxChatCallRequest')
            ->value('value') ?? 3;

        // Fetch eligible astrologers
        $eligibleAstrologers = DB::table('astrologers')
            ->where('callStatus', 'Online')
            ->where('isVerified', '1')
            ->whereRaw('COALESCE(AstroFreePaid, 0) < ?', [$maxChatCallLimit]) // Skip astrologers who reached their limit
            ->get();

        if ($eligibleAstrologers->isEmpty()) {
            return response()->json([
                'message' => 'No astrologer available right now. Please try again later.',
                'status' => 400
            ], 400);
        }

        // Filter astrologers who have not exceeded active request limit
        $availableAstrologers = $eligibleAstrologers->filter(function ($astro) use ($maxChatCallLimit) {
            $activeCalls = DB::table('callrequest')
                ->where('astrologerId', $astro->id)
                ->whereIn('callStatus', ['Pending', 'Ongoing'])
                ->count();

            $activeChats = DB::table('chatrequest')
                ->where('astrologerId', $astro->id)
                ->whereIn('chatStatus', ['Pending', 'Ongoing'])
                ->count();

            $totalActive = $activeCalls + $activeChats;

            return $totalActive < $maxChatCallLimit;
        });

        // Pick a random eligible astrologer
        $astrologer = $availableAstrologers->shuffle()->first();

        if (!$astrologer) {
            return response()->json([
                'message' => 'All astrologers are busy at the moment. Please try again shortly.',
                'status' => 400
            ], 400);
        }

        // Determine call method (default manual)
        $call_method = getCallMethod();
        $apiKey = DB::table('systemflag')->where('name', 'ExotelKey')->value('value');
        $apiToken = DB::table('systemflag')->where('name', 'ExotelToken')->value('value');
        $subdomain = DB::table('systemflag')->where('name', 'ExotelSubdomain')->value('value');
        $sid = DB::table('systemflag')->where('name', 'ExotelSid')->value('value');
        $callerId = DB::table('systemflag')->where('name', 'ExotelCallerId')->value('value');

        if ($apiKey && $apiToken && $subdomain && $sid && $callerId) {
            $call_method = "exotel";
        }

        $defaultCallTime = DB::table('systemflag')->where('name', 'defaultcalltime')->value('value') ?? 60;

        // Create instant call request
        $callRequest = CallRequest::create([
            'astrologerId'   => $astrologer->id,
            'userId'         => $userId,
            'callStatus'     => 'Pending',
            'isFreeSession'  => 0,
            'call_type'      => $request->call_type ?? 10, // default audio call
            'call_duration'  => $request->call_duration ?? (int) $defaultCallTime,
            'call_method'    => $call_method,
            'is_emergency'   => $astrologer->emergencyCallStatus ?? 0,
            'created_at'     => Carbon::now(),
            'updated_at'     => Carbon::now(),
            'IsSchedule'     => 0,
        ]);

        // Increase astrologer’s AstroFreePaid count (usage)
        DB::table('astrologers')->where('id', $astrologer->id)->update([
            'AstroFreePaid' => DB::raw('COALESCE(AstroFreePaid, 0) + 1'),
            'updated_at'    => Carbon::now()
        ]);

        return response()->json([
            'message'        => 'Call Request Sent Successfully',
            'callId'         => $callRequest->id,
            'astrologerId'   => $astrologer->id,
            'astrologerName' => $astrologer->name,
            'call_method'    => $call_method,
            'status'         => 200,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => $e->getMessage(),
            'status'  => 500
        ], 500);
    }
}

    public function getCallRequest(Request $req)
    {
        try {
            $data = $req->only(
                'astrologerId',
            );
            $validator = Validator::make($data, [
                'astrologerId' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }

            $callRequest = DB::table('callrequest')
                ->join('users', 'users.id', '=', 'callrequest.userId')
                ->leftJoin(
                    DB::raw('(SELECT * FROM intakeform WHERE id IN (SELECT MAX(id) FROM intakeform GROUP BY userId)) as intakeform'),
                    'intakeform.userId',
                    '=',
                    'callrequest.userId'
                )
                ->leftJoin('user_device_details', 'user_device_details.userId', 'users.id')
                ->where('astrologerId', '=', $req->astrologerId)
                ->where('callStatus', '=', 'Pending')
                ->selectRaw(
                    'users.*,
                 callrequest.id as callId, callrequest.userId, callrequest.astrologerId,
                 callrequest.callStatus, callrequest.channelName, callrequest.token, callrequest.totalMin,
                 callrequest.inr_usd_conversion_rate, callrequest.callRate, callrequest.deduction,
                 callrequest.call_duration, callrequest.created_at as callcreatedat, callrequest.updated_at,
                 callrequest.deductionFromAstrologer, callrequest.sId, callrequest.sId1,
                 callrequest.chatId, callrequest.isFreeSession, callrequest.call_type, callrequest.call_method,
                 callrequest.validated_till, callrequest.is_emergency, callrequest.IsSchedule,
                 CONCAT(callrequest.schedule_date, " ", callrequest.schedule_time) as schedule_datetime,
                 COALESCE(NULLIF(users.name, NULL), intakeform.name) as name,
                 COALESCE(NULLIF(users.birthDate, NULL), intakeform.birthDate) as birthDate,
                 COALESCE(NULLIF(users.birthTime, NULL), intakeform.birthTime) as birthTime,
                 COALESCE(NULLIF(users.birthPlace, NULL), intakeform.birthPlace) as birthPlace'
                )
                ->orderByDesc('callrequest.created_at');

            if ($req->startIndex >= 0 && $req->fetchRecord) {
                $callRequest->skip($req->startIndex);
                $callRequest->take($req->fetchRecord);
            }
            $callRequest = $callRequest->get();

            if ($callRequest && count($callRequest) > 0) {
                for ($i = 0; $i < count($callRequest); $i++) {
                    $userDeviceDetail = DB::table('user_device_details')->where('userId', $callRequest[$i]->id)->first();
                    if ($userDeviceDetail)
                        $callRequest[$i]->fcmToken = $userDeviceDetail->fcmToken;
                }
            }

            return response()->json([
                'messge' => 'getCallRequest Successfully',
                'status' => 200,
                'recordList' => $callRequest,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
                'error' => false,
            ], 500);
        }
    }


    public function rejectCallRequest(Request $req)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            }
            $data = $req->only('callId');
            $validator = Validator::make($data, [
                'callId' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }

            $callRequest = CallRequest::find($req->callId);
            $currenttimestamp = Carbon::now();
            if ($callRequest) {
                $callRequest->callStatus = 'Rejected';
                $callRequest->updated_at = $currenttimestamp;
                $callRequest->update();
                $appointment = DB::table('call_request_apoinments')
                    ->where('callId', $callRequest->id)
                    ->where('status', 'Pending')
                    ->first();
                // 🔹 Call private refund function
                if ($appointment) {
                    $this->processRefund($callRequest->id, $appointment);
                }



                // by bhusan
                $userDeviceDetail = DB::table('user_device_details as usd')
                    ->where('usd.userId', '=', $callRequest->userId)
                    ->select('usd.*')
                    ->get();
                $astrologer = DB::table('astrologers')
                    ->where('id', '=', $callRequest->astrologerId)
                    ->select('astrologers.name')
                    ->get();
                $call = DB::table('callrequest')
                    ->where('id', $req->callId)
                    ->select('call_type')
                    ->get();

                $call_type = '';
                if ($call[0]->call_type == '10') {
                    $call_type = 'audio call';
                } else if ($call[0]->call_type == '11') {
                    $call_type = 'video call';
                }

                if ($userDeviceDetail && count($userDeviceDetail) > 0) {
                    $oneSignalService = new OneSignalService();
                    $userPlayerIds = $userDeviceDetail->pluck('subscription_id')
                        ->merge($userDeviceDetail->pluck('subscription_id_web'))
                        ->values()->toArray();
                    $notification = [
                        'title' => '' . ucwords($call_type) . ' missed with ' . $astrologer[0]->name,
                        'body' => [
                            'description' => 'Sorry, your ' . $call_type . ' request was not accepted this time. Please try again later or explore other conversations.',
                            'icon' => 'public/notification-icon/telephone-call.png',
                            'notification_type' => 8,
                        ],
                    ];
                    $response = $oneSignalService->sendNotification($userPlayerIds, $notification);
                    $notification = [
                        'userId' => $callRequest->userId,
                        'title' => '' . ucwords($call_type) . ' missed with ' . $astrologer[0]->name,
                        'description' => 'Sorry, your ' . $call_type . ' request was not accepted this time. Please try again later or explore other conversations.',
                        'notificationId' => null,
                        'createdBy' => $callRequest->userId,
                        'modifiedBy' => $callRequest->userId,
                        'notification_type' => 1,
                        'callRequestId' => $callRequest->id,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];
                    DB::table('user_notifications')->insert($notification);
                }

                return response()->json([
                    'messge' => 'Reject Call Request Successfully',
                    'status' => 200,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
                'error' => false,
            ], 500);
        }
    }

    private function processRefund($callId, $appointment)
    {
        $currenttimestamp = Carbon::now();

        // 2️⃣ Update appointment status to "Refunded"
        DB::table('call_request_apoinments')
            ->where('id', $appointment->id)
            ->update([
                'status' => 'Refunded',
                'updated_at' => $currenttimestamp,
            ]);

        // 3️⃣ Add refunded amount to user's wallet
        $wallet = DB::table('user_wallets')->where('userId', $appointment->userId)->first();
        if ($wallet) {
            $newAmount = (float)$wallet->amount + (float)$appointment->amount;
            DB::table('user_wallets')->where('userId', $appointment->userId)->update([
                'amount' => $newAmount,
                'updated_at' => $currenttimestamp,
                'modifiedBy' => $appointment->userId,
            ]);
        } else {
            // Insert new wallet record if not exists
            DB::table('user_wallets')->insert([
                'userId' => $appointment->userId,
                'amount' => $appointment->amount,
                'isActive' => 1,
                'isDelete' => 0,
                'createdBy' => $appointment->userId,
                'modifiedBy' => $appointment->userId,
                'created_at' => $currenttimestamp,
                'updated_at' => $currenttimestamp,
            ]);
        }
        $inr_usd_conv_rate = DB::table('systemflag')->where('name', 'UsdtoInr')->select('value')->first();
        $convRateValRefund = ($inr_usd_conv_rate && !empty($inr_usd_conv_rate->value)) ? (float)$inr_usd_conv_rate->value : 83.5;
        // 4️⃣ Insert refund record in payments table
        DB::table('payment')->insert([
            'paymentMode' => 'Refund',
            'payment_for' => 'wallet',
            'paymentReference' => 'REFUND-' . $callId . '-' . time(),
            'inr_usd_conversion_rate' => $convRateValRefund, // optional, can be set if needed
            'amount' => $appointment->amount,
            'userId' => $appointment->userId,
            'paymentStatus' => 'Success',
            'signature' => null,
            'orderId' => 'ORDER-' . $callId . '-' . time(),
            'cashback_amount' => 0,
            'payment_order_info' => 'Refund for rejected callId ' . $callId,
            'durationchat' => null,
            'chatId' => null,
            'durationcall' => $appointment->call_duration,
            'callId' => $callId,
            'created_at' => $currenttimestamp,
            'updated_at' => $currenttimestamp,
            'createdBy' => $appointment->userId,
            'modifiedBy' => $appointment->userId,
        ]);
    }



    public function removeFromWaitlist(Request $req)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            }
            $data = $req->only(
                'callId',
            );
            $validator = Validator::make($data, [
                'callId' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }
            $callRequest = CallRequest::find($req->callId);
            $callRequest->Delete();
            return response()->json([
                'messge' => 'Remove Call Request Successfully',
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
                'error' => false,
            ], 500);
        }
    }

     public function acceptCallRequest(Request $req)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            }
            $data = $req->only(
                'callId',
            );
            $validator = Validator::make($data, [
                'callId' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }
            $callRequest = CallRequest::find($req->callId);
            $currenttimestamp = Carbon::now();
            if ($callRequest) {
                $callRequest->callStatus = 'Accepted';
                $callRequest->updated_at = $currenttimestamp;
                $callRequest->save();
            }

            DB::table('callrequest')
                ->where('userId', $callRequest->userId)
                ->where('id', '!=', $callRequest->id)
                ->whereIn('callStatus', ['Pending', 'Accepted'])
                ->delete();

            $this->addCallStatus(new Request(['token' => $req->token, 'status' => 'Busy', 'astrologerId' => $callRequest->astrologerId]));
            app(\App\Http\Controllers\API\User\ChatRequestController::class)->addChatStatus(new Request(['token' => $req->token, 'status' => 'Busy', 'astrologerId' => $callRequest->astrologerId]));

            $call_type = '';
            if ($callRequest->call_type == '10') {
                $call_type = 'audio call';
            } else if ($callRequest->call_type == '11') {
                $call_type = 'video call';
            }
            $userDeviceDetail = DB::table('user_device_details')
                ->WHERE('user_device_details.userId', '=', $callRequest->userId)
                ->SELECT('user_device_details.*')
                ->get();
            $blankimg = '/build/assets/images/person.png';

            $astrologer = Astrologer::find($callRequest->astrologerId);


            return response()->json([
                'messge' => 'call Request Accept Successfully',
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
                'error' => false,
            ], 500);
        }
    }
   public function storeToken(Request $req, HundredMsService $hundredMsService)
    {
        try {

            $data = $req->only(
                'callId',
                'token',
                'channelName'
            );

            $validator = Validator::make($data, [
                'callId' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }

            $callRequest = CallRequest::find($req->callId);

            if ($callRequest->call_method == 'exotel') {
                app(\App\Http\Controllers\API\User\ExotelController::class)->connectCall(new Request(['callId' => $req->callId]));
            }

            $call_type = '';
            if ($callRequest->call_type == '10') {
                $call_type = 'audio call';
            } else if ($callRequest->call_type == '11') {
                $call_type = 'video call';
            }
            if ($req->fromWeb) {
               $req->channelName = 'OnlinePujaGuru_'.$callRequest->astrologerId.'_'.$req->callId;
            }
            
            $astrologer = DB::Table('astrologers')
                ->leftjoin('user_device_details', 'user_device_details.userId', 'astrologers.userId')
                ->where('astrologers.id', '=', $callRequest->astrologerId)
                ->select('astrologers.name as name', 'astrologers.profileImage', 'user_device_details.fcmToken', 'astrologers.charge', 'astrologers.videoCallRate')
                ->get();
            if ($callRequest->call_method == 'hms') {

                if ($callRequest->call_duration < 120) {
                    return response()->json([
                        'message' => 'HundredMS allowed minimum 2 min call duration',
                        'status' => 400,
                        'error' => true,
                    ], 400);
                }

                if ($callRequest->call_type == '11') {
                    $hmsReponse  = $hundredMsService->createHmsRoom(systemflag('hmsVideoRole') ?? 'video-moderator', $req->channelName, $callRequest->call_duration);
                } else if ($callRequest->call_type == '10') {
                    $hmsReponse  = $hundredMsService->createHmsRoom(systemflag('hmsAudioRole') ?? 'audio-moderator', $req->channelName, $callRequest->call_duration);
                }
                if ($hmsReponse['success'] == false) {
                    return $hmsReponse;
                }
                $req->token = $hmsReponse['auth_token'];
            }
			 if ($callRequest->call_method == 'zegocloud') {
                $token = $this->zegoApi->generateToken($req->callId, $req->channelName, $callRequest->call_duration);
                $req->token = $token;
            }
            if ($callRequest->call_method == 'agora') {
                $privilegeExpiredTs = Carbon::now()->timestamp + 7200;
                $rtcTokenController = new RtcTokenBuilder;
                $agoraAppId = systemflag('AgoraAppId');
                $agoraCertificate = systemflag('AgoraAppCertificate');
                $token = $rtcTokenController->buildTokenWithUid($agoraAppId, $agoraCertificate, $req->channelName, 0, 1, $privilegeExpiredTs);
                $req->token = $token;
            }

            $currenttimestamp = Carbon::now()->toDateTimeString();
            if ($callRequest) {
                $callRequest->callStatus = 'Accepted';
                $callRequest->updated_at = $currenttimestamp;
                $callRequest->token = $req->token;
                $callRequest->channelName = $req->channelName;
                $callRequest->update();
            }
            $userDeviceDetail = DB::table('user_device_details')
                ->WHERE('user_device_details.userId', '=', $callRequest->userId)
                ->SELECT('user_device_details.*')
                ->get();

            $user_wallet = DB::table('user_wallets')->where('userId', $callRequest->userId)->first();
            if ($user_wallet) {
                $walletamount = $user_wallet->amount;
            } else {
                $walletamount = 0;
            }


            $blankimg = '/build/assets/images/person.png';

            if ($userDeviceDetail && count($userDeviceDetail) > 0) {

                $response = FCMService::send(
                    $userDeviceDetail,
                    [
                        'title' => 'Congrats, your ' . $call_type . ' request accepted by ' . $astrologer[0]->name,
                        'body' => [
                            "astrologerId" => $callRequest->astrologerId,
                            "astrologerName" => $astrologer[0]->name ? $astrologer[0]->name : 'User',
                            "notificationType" => 1,
                            "profile" => $astrologer[0]->profileImage ? $astrologer[0]->profileImage : $blankimg,
                            "token" => $callRequest->token,
                            "channelName" => $callRequest->channelName,
                            "callId" => $callRequest->id,
                            "call_type" => $callRequest->call_type,
                            "call_duration" => $callRequest->call_duration,
                            //'description' => '',
                            'description' => 'Get ready to connect and engage in meaningful conversation',
                            'fcmToken' => $astrologer[0]->fcmToken,
                            'icon' => 'public/notification-icon/telephone-call.png',
                            'charge' => $astrologer[0]->charge,
                            'videoCallRate' => $astrologer[0]->videoCallRate,
                            'walletamount' => $walletamount,
                            'call_method' => $callRequest->call_method,
                        ],
                    ]
                );

                // by bhusan
                $notification = array(
                    'userId' => $callRequest->userId,
                    'title' => 'Congrats, your ' . $call_type . ' request accepted by ' . $astrologer[0]->name,
                    'description' => 'Get ready to connect and engage in meaningful conversation',
                    'notificationId' => null,
                    'createdBy' => $userDeviceDetail[0]->id,
                    'modifiedBy' => $userDeviceDetail[0]->id,
                    'notification_type' => 1,
                    'callRequestId' => $callRequest->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                );
                DB::table('user_notifications')->insert($notification);
                // end
            } else {

                $notification = array(
                    'userId' => $callRequest->userId,
                    'title' => 'Congrats, your ' . $call_type . ' request accepted by ' . $astrologer[0]->name,
                    'description' => 'Get ready to connect and engage in meaningful conversation',
                    'notificationId' => null,
                    'createdBy' => $callRequest->userId,
                    'modifiedBy' => $callRequest->userId,
                    'notification_type' => 1,
                    'callRequestId' => $callRequest->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                );
                DB::table('user_notifications')->insert($notification);
            }
            $token = $req->token;
            return response()->json([
                'token' => $token,
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
                'error' => false,
            ], 500);
        }
    }

    public function endCall(Request $req)
    {
        try {
            $data = $req->only(
                'callId',
                'totalMin'
            );
            $validator = Validator::make($data, [
                'callId' => 'required',
                // 'totalMin' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }

            $callData = DB::table('callrequest')
                ->join('astrologers', 'astrologers.id', '=', 'callrequest.astrologerId')
                ->join('users', 'users.id', '=', 'callrequest.userId')
                ->where('callrequest.id', '=', $req->callId)
                ->select('callrequest.*', 'users.name', 'astrologers.name as astrologerName', 'astrologers.userId as astrologerUserId')
                ->first();

            $this->addCallStatus(new Request(['token' => $req->token, 'status' => 'Online', 'astrologerId' => $callData->astrologerId]));
            app(\App\Http\Controllers\API\User\ChatRequestController::class)->addChatStatus(new Request(['token' => $req->token, 'status' => 'Online', 'astrologerId' => $callData->astrologerId]));

            $id = $callData->userId;

            $user_country = User::where('id', $id)->where('countryCode', '=', '+91')->first(); // added by bhushan borse on 04, june 2025
            $inr_usd_conv_rate = DB::table('systemflag')->where('name', 'UsdtoInr')->select('value')->first();
            $convRateVal = ($inr_usd_conv_rate && !empty($inr_usd_conv_rate->value)) ? (float)$inr_usd_conv_rate->value : 83.5;
            $firebaseProjectId = DB::table('systemflag')->where('name', 'firebaseprojectId')->select('value')->first();
            $astrologercountry = Astrologer::where('id', $callData->astrologerId)->where('countryCode', '=', '+91')->first();  // added by bhushan borse on 04, june 2025

            $updatedAt = Carbon::parse($callData->updated_at);
            $totalSeconds = $updatedAt->diffInSeconds(Carbon::now());

            if ($totalSeconds > $callData->call_duration) {
                $totalSeconds = $callData->call_duration;
            }

            $totalMin = $totalSeconds / 60;
            $totalMin = round($totalMin);



            // $totalMin = $req->totalMin / 60;
            // $totalMin = round($totalMin);
            $astrologerCommission = 0;
            $deduction = 0;
            $chargeAmount = 0;
            $charge = Astrologer::query()
                ->where('id', '=', $callData->astrologerId)
                ->get();

            if ($callData->call_type == 11) {

                $chargeAmount = $user_country ? $charge[0]->videoCallRate : convertusdtoinr($charge[0]->videoCallRate_usd ?? $charge[0]->videoCallRate);
                if ($callData->is_emergency) {
                    $chargeAmount = $user_country ? $charge[0]->emergency_video_charge : convertinrtousd($charge[0]->emergency_video_charge);
                }
                if ($charge[0]->isDiscountedPrice && !$callData->is_emergency) {
                    $discountedAmount = (float)$chargeAmount * (float)$charge[0]->video_discount / 100;
                    $chargeAmount = $chargeAmount - $discountedAmount;
                }
                $transactionType = 'VideoCall';
            } else {
                $chargeAmount = $user_country ? $charge[0]->charge : convertusdtoinr($charge[0]->charge_usd);
                if ($callData->is_emergency) {
                    $chargeAmount = $user_country ? $charge[0]->emergency_audio_charge : convertinrtousd($charge[0]->emergency_audio_charge);
                }
                if ($charge[0]->isDiscountedPrice && !$callData->is_emergency) {
                    $discountedAmount = (float)$chargeAmount * (float)$charge[0]->audio_discount / 100;
                    $chargeAmount = $chargeAmount - $discountedAmount;
                }
                $transactionType = 'Call';
            }



            if (!$callData->isFreeSession || $callData->is_emergency) {
                // $deduction = $totalMin * $charge[0]->charge;
                $deduction = $totalMin * $chargeAmount;
                $commission = DB::table('commissions')
                    ->where('commissionTypeId', '=', '2')
                    ->where('astrologerId', '=', $callData->astrologerId)
                    ->get();

                // NewCommission

                $getBoostProfile = ProfileBoosted::where('astrologer_id', $callData->astrologerId)
                    ->where('boosted_datetime', '>=', Carbon::now()->subHours(24))
                    ->first();

                $syscommission = DB::table('systemflag')->where('name', 'CallCommission')->select('value')->get(); // Fetch syscommission

                if ($getBoostProfile) {
                    $boostCommission = ProfileBoost::first();
                    $adminCommission = ($boostCommission->call_commission * $deduction) / 100;
                } elseif ($commission && count($commission) > 0) {
                    $adminCommission = ($commission[0]->commission * $deduction) / 100;
                } else {
                    $adminCommission = ($syscommission[0]->value * $deduction) / 100;
                }

                $astrologerCommission = $deduction - $adminCommission;
            }

            $callDatas = array(
                'totalMin' => $totalMin,
                'callStatus' => 'Completed',
                'deduction' => $deduction,
                'callRate' => ($callData->is_emergency || !$callData->isFreeSession) ? $chargeAmount : 0,
                'deductionFromAstrologer' => $astrologerCommission,
                'sId' => $req->sId,
                'sId1' => $req->sId1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'inr_usd_conversion_rate' => $convRateVal,

            );
            DB::Table('callrequest')
                ->where('id', '=', $req->callId)
                ->update($callDatas);
            
            if($totalSeconds >= 45){
                
         
            $charge[0]->totalOrder = $charge[0]->totalOrder ? $charge[0]->totalOrder + 1 : 1;
            $charges = array(
                'totalOrder' => $charge[0]->totalOrder,
            );
            DB::table('astrologers')
                ->where('id', $charge[0]->id)
                ->update($charges);
            if ($charge[0]->charge > 0) {
                $wallet = DB::table('user_wallets')
                    ->where('userId', '=', $callData->userId)
                    ->get();
                $wallets = array(
                    'userId' => $callData->userId,
                    'amount' => (!$callData->isFreeSession || $callData->is_emergency) ? $wallet[0]->amount - $deduction : (($wallet && count($wallet) > 0) ? $wallet[0]->amount : 0),
                    'createdBy' => $id,
                    'modifiedBy' => $id,
                );

                if ($wallet && count($wallet) > 0) {
                    DB::table('user_wallets')
                        ->where('id', $wallet[0]->id)
                        ->update($wallets);
                } else {
                    DB::table('user_wallets')->insert($wallets);
                }
                $astrologerWallet = DB::table('user_wallets')
                    ->where('userId', $callData->astrologerUserId)
                    ->get();
                $astrologerWall = array(
                    'userId' => $callData->astrologerUserId,
                    'amount' => $astrologerWallet && count($astrologerWallet) > 0 ? $astrologerWallet[0]->amount + $astrologerCommission : $astrologerCommission,
                    'createdBy' => $id,
                    'modifiedBy' => $id,
                );

                if ($astrologerWallet && count($astrologerWallet) > 0) {
                    DB::table('user_wallets')
                        ->where('userId', $callData->astrologerUserId)
                        ->update($astrologerWall);
                } else {
                    DB::Table('user_wallets')->insert($astrologerWall);
                }
            }
            $orderRequest = array(
                'userId' => $callData->userId,
                'astrologerId' => $callData->astrologerId,
                'orderType' => 'call',
                'totalPayable' => $deduction,
                'orderStatus' => 'Complete',
                'totalMin' => $totalMin,
                'callId' => $req->callId,
                'inr_usd_conversion_rate' => $convRateVal,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),

            );
            DB::Table('order_request')->insert($orderRequest);
            $Orderid = DB::getPdo()->lastInsertId();
            $transaction = array(
                'userId' => $callData->userId,
                'amount' => $deduction,
                'isCredit' => false,
                "transactionType" => $transactionType,
                "orderId" => $Orderid,
                "astrologerId" => $callData->astrologerId,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'inr_usd_conversion_rate' => $convRateVal,
            );
            $astrologerTransaction = array(
                'userId' => $callData->astrologerUserId,
                'amount' => $astrologerCommission,
                'isCredit' => true,
                "transactionType" => $transactionType,
                "orderId" => $Orderid,
                "astrologerId" => $callData->astrologerId,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'inr_usd_conversion_rate' => $convRateVal,
            );
            if (!$callData->isFreeSession || $callData->is_emergency) {
                if ($commission && count($commission) > 0) {
                    $adminGetCommission = array(
                        'commissionTypeId' => 2,
                        "amount" => $adminCommission,
                        "commissionId" => $commission && count($commission) > 0 ? $commission[0]->id : null,
                        "orderId" => $Orderid,
                        "createdBy" => $charge[0]->userId,
                        "modifiedBy" => $charge[0]->userId,
                        'inr_usd_conversion_rate' => $convRateVal,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),

                    );
                    DB::table('admin_get_commissions')->insert($adminGetCommission);
                } elseif ($syscommission && count($syscommission) > 0) {
                    $adminGetCommission = array(
                        'commissionTypeId' => 2,
                        "amount" => $adminCommission,
                        "commissionId" => null,
                        "orderId" => $Orderid,
                        "createdBy" => $charge[0]->userId,
                        "modifiedBy" => $charge[0]->userId,
                        'inr_usd_conversion_rate' => $convRateVal,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    );
                    DB::table('admin_get_commissions')->insert($adminGetCommission);
                }
            }
            DB::table('wallettransaction')->insert($transaction);
            DB::table('wallettransaction')->insert($astrologerTransaction);
            }

            if ($firebaseProjectId && !empty($firebaseProjectId->value)) {
                $apiEndpoint = "https://firestore.googleapis.com/v1/projects/" . $firebaseProjectId->value . "/databases/(default)/documents/";
                $client = new Client();

                try {
                    $response = $client->delete($apiEndpoint . "updatecall/{$req->callId}");
                } catch (\Exception $e) {
                    \Log::warning('Firestore cleanup failed in endCall: ' . $e->getMessage());
                }
            }

            return response()->json([
                'message' => 'Call Request End Successfully',
                'status' => 200,
                'recordList' => $deduction,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
                'error' => false,
            ], 500);
        }
    }

    public function rejectCallRequestFromCustomer(Request $req)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            }
            $data = $req->only(
                'callId',
            );
            $validator = Validator::make($data, [
                'callId' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }
            $callData = CallRequest::find($req->callId);

            $userDeviceDetail = DB::table('user_device_details as device')
                ->JOIN('astrologers', 'astrologers.userId', '=', 'device.userId')
                ->WHERE('astrologers.id', '=', $callData->astrologerId)
                ->SELECT('device.*', 'astrologers.userId as astrologerUserId', 'astrologers.name')
                ->get();

            $response = FCMService::send(
                $userDeviceDetail,
                [
                    'title' => 'Call Rejected By Customer',
                    'body' => ['description' => 'Call Rejected By Customer'],

                ],

            );


            $this->addCallStatus(new Request(['token' => $req->token, 'status' => 'Online', 'astrologerId' => $callData->astrologerId]));
            app(\App\Http\Controllers\API\User\ChatRequestController::class)->addChatStatus(new Request(['token' => $req->token, 'status' => 'Online', 'astrologerId' => $callData->astrologerId]));
            if ($callData) {
                $callData->delete();
            }


            return response()->json([
                'message' => 'Call Request Rejected Successfully',
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
                'error' => false,
            ], 500);
        }
    }

    public function acceptCallRequestFromCustomer(Request $request)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            }
            $callData = CallRequest::find($request->callId);
            $currenttimestamp = Carbon::now();
            if ($callData) {
                $callData->callStatus = 'Confirmed';
                $callData->deduction = 0;
                $callData->updated_at = Carbon::now();
                $callData->totalMin = 0;
                $callData->update();
            }
            return [
                'success' => true,
                'message' => 'Call Request Accepted Successfully',
                'status' => 200,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }



public function storeCallRecording(Request $req)
{
    try {
        // Validate that file is uploaded
        if (!$req->hasFile('recording')) {
            return response()->json([
                'message' => 'No recording file uploaded.',
                'status' => 400
            ], 400);
        }

        $file = $req->file('recording');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $fileContent = file_get_contents($file->getRealPath());

        // Upload file to active storage or fallback to local
        $uploadPath = StorageHelper::uploadToActiveStorage($fileContent, $fileName, 'callRecording');

        return response()->json([
            'message' => 'Recording saved successfully.',
            'path' => $uploadPath,
            'status' => 200
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => $e->getMessage(),
            'status' => 500,
            'error' => false,
        ], 500);
    }
}


    public function getCallById(Request $req)
    {
        try {
            $callData = DB::table('callrequest')
                ->join('astrologers', 'astrologers.id', '=', 'callrequest.astrologerId')
                ->select('callrequest.*', 'astrologers.name as astrologerName')
                ->where('callrequest.id', '=', $req->callId)
                ->get();
            return response()->json([
                'recordList' => $callData,
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
                'error' => false,
            ], 500);
        }
    }

    public function addCallStatus(Request $req)
    {
        try {
            // if (!Auth::guard('api')->user()) {
            //     return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            // } else {
            //     Auth::guard('api')->user()->id;
            // }
            $status = array(
                'callStatus' => $req->status,
                'callWaitTime' => ($req->status == 'Offline' || $req->status == 'Online') ? null : $req->waitTime,
            );
            DB::table('astrologers')->where('id', '=', $req->astrologerId)
                ->update($status);
            return response()->json([
                "message" => "Update Astrologer",
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }


    // for topup
    public function updateCallMinute(Request $request)
    {
        $request->validate([
            'callId' => 'required|integer|exists:callrequest,id',
            'call_duration' => 'required|integer|min:1',
        ]);

        // dd($request->all());

        $call = DB::table('callrequest')->where('id', $request->callId)->first();

        if ($call) {

            $user_wallet = DB::table('user_wallets')->where('userId', $call->userId)->first();
            $astrologerCharge = DB::table("astrologers")->where('id', $call->astrologerId)->first();

            $getcurrentDuration = $this->getcurrentCallDuration(new Request(['callId' => $request->callId]))->getData(true);

            if ($call->call_type == 11) {
                $astrologerCharge->charge = $astrologerCharge->videoCallRate;
            }

            $callDurationMinutes = $getcurrentDuration['callDuration'] / 60;

            $remainingWalletAmount = $user_wallet->amount - ($callDurationMinutes * $astrologerCharge->charge);
            // dd($remainingWalletAmount);
            $callDurationMinutesforcharge = $request->call_duration / 60;
            $total_charge = $astrologerCharge->charge * $callDurationMinutesforcharge;

            //    if ($total_charge >= $remainingWalletAmount){
            //         return response()->json(['message' => 'Insufficient Funds','status' => 400], 400);
            //    }
            if ($total_charge >= $remainingWalletAmount && $total_charge != $remainingWalletAmount) {
                return response()->json(['message' => 'Insufficient Funds', 'status' => 400, 'total_charge' => $total_charge,  'remainingWalletAmount' => $remainingWalletAmount], 400);
            }


            DB::table('callrequest')
                ->where('id', $request->callId)
                ->update(['call_duration' => $call->call_duration + $request->call_duration]);

            $firebaseProjectId = DB::table('systemflag')->where('name', 'firebaseprojectId')->select('value')->first();

            $apiEndpoint = "https://firestore.googleapis.com/v1/projects/" . $firebaseProjectId->value . "/databases/(default)/documents/";
            $client = new Client();

            $updatedDuration = $call->call_duration + $request->call_duration;

            $firestoreData = [
                'fields' => [
                    'callId' => ['integerValue' => $request->callId],
                    'duration' => ['integerValue' => $updatedDuration],
                    'updatedAt' => ['timestampValue' => now()->toIso8601String()],
                    'userId' => ['integerValue' => $call->userId],
                    'astrologerId' => ['integerValue' => $call->astrologerId]
                ]
            ];

            // Build query string manually to avoid Guzzle auto-indexing arrays
            $query = "updateMask.fieldPaths=duration&updateMask.fieldPaths=updatedAt&currentDocument.exists=true";

            try {
                // Append query string directly to the URL (no 'query' array)
                $response = $client->patch($apiEndpoint . "updatecall/{$request->callId}?" . $query, [
                    'json' => [
                        'fields' => [
                            'duration' => ['integerValue' => $updatedDuration],
                            'updatedAt' => ['timestampValue' => now()->toIso8601String()]
                        ]
                    ]
                ]);
            } catch (\Exception $e) {
                //  If document not found, create it
                if ($e->getCode() == 404 || str_contains($e->getMessage(), 'NOT_FOUND')) {
                    $response = $client->post($apiEndpoint . "updatecall?documentId={$request->callId}", [
                        'json' => $firestoreData
                    ]);
                } else {
                    // Optional: log full response for debugging
                    logger()->error('Firestore PATCH failed: ' . $e->getMessage());
                    throw $e;
                }
            }


            $userDeviceDetail = DB::table('user_device_details as device')
                ->JOIN('astrologers', 'astrologers.userId', '=', 'device.userId')
                ->WHERE('astrologers.id', '=', $call->astrologerId)
                ->SELECT('device.*', 'astrologers.userId as astrologerUserId', 'astrologers.name')
                ->get();

            $response = FCMService::send(
                $userDeviceDetail,
                [
                    'title' => 'Start simple call timer',
                    'body' => [
                        'description' => 'Start simple call timer',
                        'notificationType' => 2,
                        'icon' => 'public/notification-icon/telephone-call.png',
                        'timeInInt' => $call->call_duration + $request->call_duration
                    ],

                ]
            );

            return response()->json(['message' => 'Call duration updated successfully.', 'status' => 200,], 200);
        }

        return response()->json(['message' => 'Call request not found.', 'status' => 400,], 400);
    }


    // get currenct duration
    public function getcurrentCallDuration(Request $request)
    {
        $request->validate([
            'callId' => 'required|integer|exists:callrequest,id',

        ]);

        $callconfirmed = DB::table('callrequest')
            ->where('id', $request->callId)
            ->where('callStatus', 'Confirmed')
            ->first();

        if ($callconfirmed) {
            DB::table('call_last_interactions')->updateOrInsert(
                ['callId' => $request->callId],
                [
                    'last_interaction_time' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );
        }


        $call = DB::table('callrequest')->where('id', $request->callId)->select("call_duration")->first();

        return response()->json([
            "callDuration" => $call->call_duration,
            "message" => "Call Duration",
            'status' => 200,
        ], 200);


        return response()->json(['message' => 'Call request not found.'], 404);
    }
}
