<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Models\AstrologerModel\Astrologer;
use App\Models\UserModel\CallRequest;
use App\Models\UserModel\ChatRequest;
use App\Services\FCMService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\ProfileBoost;
use App\Models\ProfileBoosted;
use App\Models\UserModel\User;
use App\Services\OneSignalService;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Client;

class ChatRequestController extends Controller
{
    public function addChatRequest(Request $req)
    {
        try {
            $user = Auth::guard('api')->user();
            $id = $user ? $user->id : ($req->userId ? (int)$req->userId : ($req->input('userId') ? (int)$req->input('userId') : null));
            if (!$id) {
                $id = 1;
            }


            $isFreeChat = DB::table('systemflag')->where('name', 'FirstFreeChat')->select('value')->first();
            $isFreeAvailable=true;
            if ($isFreeChat->value == 1) {
                if ($id) {
                    $isChatRequest = DB::table('chatrequest')->where('userId', $id)->where('chatStatus', '=', 'Completed')->first();
                    $isCallRequest = DB::table('callrequest')->where('userId', $id)->where('callStatus', '=', 'Completed')->first();
                    if ($isChatRequest || $isCallRequest) {
                        $isFreeAvailable = false;
                    } else {
                        $isFreeAvailable = true;
                    }
                }
            } else {
                $isFreeAvailable = false;
            }

            $professionTitle = DB::table('systemflag')
            ->where('name', 'professionTitle')
            ->select('value')
            ->first();
            $professionTitle = $professionTitle ? $professionTitle->value : 'Partner';

            $astrologerOnlineOffline = DB::table('astrologers')->where('id', '=', $req->astrologerId)->first();
            if (!$astrologerOnlineOffline) {
                $astrologerOnlineOffline = DB::table('astrologers')->where('userId', '=', $req->astrologerId)->first();
            }
            if (!$astrologerOnlineOffline) {
                return response()->json([
                    'recordList' => [
                        'message' => 'The ' . $professionTitle . ' is currently unavailable.',
                    ],
                    'status' => 400,
                ], 400);
            }
            if ($astrologerOnlineOffline->chatStatus == "Offline" || $astrologerOnlineOffline->chatStatus == "Busy") {
                return response()->json([
                    'recordList' => [
                        'message' => 'Chat request cannot be sent. The ' . $professionTitle . ' is currently unavailable.',
                    ],
                    'status' => 400,
                ], 400);
            }


            $astroId = $astrologerOnlineOffline->id;

            $firstFreerecharge = DB::table('systemflag')->where('name', 'FirstFreeChatRecharge')->select('value')->first();
            $minAmount = DB::table('systemflag')->where('name', 'MinAmountFreeChatCall')->select('value')->first();
            $wallets = DB::table('user_wallets')->where('userId', $id)->first();
            $max_request = DB::table('systemflag')->where('name', 'MaxRequestNumber')->select('value')->first();
            $astrologerEmergency = DB::table('astrologers')->where('id', $astroId)->pluck('emergencyChatStatus')->first();
            $minAmountValue = (float) ($minAmount ? $minAmount->value : 0);
            $walletAmount = $wallets ? (float) $wallets->amount : 0;

            if ($isFreeAvailable && $firstFreerecharge && $firstFreerecharge->value == 1 && ($walletAmount < $minAmountValue)) {
                return response()->json([
                    'recordList' => [
                        'message' => 'Please Recharge First to use free chat',
                        'minAmount' => (int)$minAmountValue
                    ],
                    'status' => 400,
                ], 400);
            }
            $callDurationMinutesforcharge = ($req->call_duration ?? 60) / 60;
            $astrologerCharge = DB::table('astrologers')->where('id', $astroId)->pluck('charge')->first();
            if ($astrologerEmergency) {
                $astrologerCharge = DB::table('astrologers')->where('id', $astroId)->pluck('emergency_chat_charge')->first();
            }
            $total_charge = ($astrologerCharge ?? 0) * $callDurationMinutesforcharge;
            if (!$isFreeAvailable && $total_charge > $walletAmount) {
                return response()->json([
                    'recordList' => [
                        'message' => 'Insufficient Wallet Balance',
                    ],
                    'status' => 400,
                ], 400);
            }

            $data = $req->only('astrologerId', 'chat_duration');
            $validator = Validator::make($data, [
                'astrologerId' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }

            // Count pending requests
            $pendingChatRequests = ChatRequest::where('userId', $id)->where('chatStatus', 'Pending')->count();
            $pendingCallRequests = CallRequest::where('userId', $id)->where('callStatus', 'Pending')->count();
            $totalPendingRequests = $pendingChatRequests + $pendingCallRequests;
            $maxReqVal = $max_request ? (int)$max_request->value : 5;

            if ($totalPendingRequests >= $maxReqVal) {
                // Auto-cleanup stale pending requests older than 10 minutes so users don't get permanently blocked
                ChatRequest::where('userId', $id)->where('chatStatus', 'Pending')->where('created_at', '<', Carbon::now()->subMinutes(10))->update(['chatStatus' => 'Rejected']);
                CallRequest::where('userId', $id)->where('callStatus', 'Pending')->where('created_at', '<', Carbon::now()->subMinutes(10))->update(['callStatus' => 'Rejected']);
            }

            $chatRequest = ChatRequest::create([
                'astrologerId' => $astroId,
                'userId' => $id,
                'chatStatus' => 'Pending',
                'senderId' => '',
                'isFreeSession' => $req->isFreeSession ?? 0,
                'chat_duration' => $req->chat_duration ?? 300,
                'is_emergency' => $astrologerEmergency ?? 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);



            $userDeviceDetail = DB::table('user_device_details as device')
                ->JOIN('astrologers', 'astrologers.userId', '=', 'device.userId')
                ->WHERE('astrologers.id', '=', $req->astrologerId)
                ->SELECT('device.*', 'astrologers.userId as astrologerUserId', 'astrologers.name')
                ->get();

            $user = DB::table('users')->where('id', '=', $id)->select('name','id','profile')->get();


            // 	$chatRequest=ChatRequest::latest()->first();
            // 	dd($chatRequest);
            	$astroUserId = DB::table('astrologers')->where('id', '=', $chatRequest->astrologerId)->select('userId')->first();
            if ($userDeviceDetail && count($userDeviceDetail) > 0) {

			$blankimg='/build/assets/images/person.png';
			    FCMService::send(
                    $userDeviceDetail,
                    [
                            'title' => 'Chat Request',
                            'body' => [
                                'description' => 'Hey ' . $userDeviceDetail[0]->name . ', you received a chat request from ' . $user[0]->name,
                                'notificationType' => 8,
                                'icon' => asset('public/notification-icon/chat.png'),
                                //  "token" => $chatRequest->token,
                                 "userName" => $user[0]->name,
                                 "profile" => $user[0]->profile ? asset($user[0]->profile) : asset($blankimg),
                                  "userId" => $chatRequest->userId,
                                  "astroUserId" => $astroUserId->userId,
                                  "astrologerId" => $chatRequest->astrologerId,
                                "channelName" => $chatRequest->channelName,
                                'chat_duration' => $chatRequest->chat_duration,
                                "receiverId" => $chatRequest->receiverId,
                                "senderId" => $chatRequest->senderId,
                                'chatId' => $chatRequest->id,
                                'fcmToken' => $userDeviceDetail[0]->fcmToken,
                                'subscription_id' => $userDeviceDetail[0]->subscription_id,
                                 'sound' => 'app_sound'
                            ],
                            'priority' => 'custom',
                    ]
                );

                // Dual-dispatch to OneSignal for reliable background ringing on locked devices
                try {
                    $playerIds = $userDeviceDetail->pluck('subscription_id')
                        ->filter(function($id) { return !empty($id); })
                        ->values()->toArray();
                    if (!empty($playerIds)) {
                        $oneSignalService = new OneSignalService();
                        $oneSignalService->sendNotification($playerIds, [
                            'title' => 'Chat Request',
                            'priority' => 'custom',
                            'body' => [
                                'description' => 'Hey ' . $userDeviceDetail[0]->name . ', you received a chat request from ' . $user[0]->name,
                                'notificationType' => 8,
                                'icon' => asset('public/notification-icon/chat.png'),
                                'userName' => $user[0]->name,
                                'profile' => $user[0]->profile ? asset($user[0]->profile) : asset($blankimg),
                                'userId' => $chatRequest->userId,
                                'astroUserId' => $astroUserId->userId ?? null,
                                'astrologerId' => $chatRequest->astrologerId,
                                'channelName' => $chatRequest->channelName,
                                'chat_duration' => $chatRequest->chat_duration,
                                'receiverId' => $chatRequest->receiverId,
                                'senderId' => $chatRequest->senderId,
                                'chatId' => $chatRequest->id,
                                'sound' => 'app_sound'
                            ],
                        ]);
                    }
                } catch (\Exception $ex) {
                    \Illuminate\Support\Facades\Log::warning('OneSignal chat dispatch non-fatal: ' . $ex->getMessage());
                }

                $notification = array(
                    'userId' => $userDeviceDetail[0]->astrologerUserId,
                    'title' => 'Hey '.$userDeviceDetail[0]->name.', you received a chat request from ' . $user[0]->name,
                    'description' => '',
                    'notificationId' => null,
                    'createdBy' => $userDeviceDetail[0]->astrologerUserId,
                    'modifiedBy' => $userDeviceDetail[0]->astrologerUserId,
                    'notification_type' => 8,
					'chatRequestId' => $chatRequest->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                );
                DB::table('user_notifications')->insert($notification);
            }
            return response()->json([
                'recordList' => [
                    'chatId' => $chatRequest->id,
                    'message' => 'Chat Request add successfully',
                ],
                'chatId' => $chatRequest->id,
                'message' => 'Chat Request add successfully',
                'status' => 200,
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
                'error' => false,
            ], 500);
        }
    }

        public function addRandomChatRequest(Request $request)
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
                ->where('chatStatus', 'Online')
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

            $defaultCallTime = DB::table('systemflag')->where('name', 'defaultcalltime')->value('value') ?? 60;

            // Create instant call request
            $chatRequest =  ChatRequest::create([
                'astrologerId' => $astrologer->id,
                'userId' => $astrologer->userId,
                'chatStatus' => 'Pending',
                'senderId' => '',
                'chat_duration' => $defaultCallTime,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Increase astrologer’s AstroFreePaid count (usage)
            DB::table('astrologers')->where('id', $astrologer->id)->update([
                'AstroFreePaid' => DB::raw('COALESCE(AstroFreePaid, 0) + 1'),
                'updated_at'    => Carbon::now()
            ]);

              $userDeviceDetail = DB::table('user_device_details as device')
                ->JOIN('astrologers', 'astrologers.userId', '=', 'device.userId')
                ->WHERE('astrologers.id', '=', $astrologer->id)
                ->SELECT('device.*', 'astrologers.userId as astrologerUserId', 'astrologers.name')
                ->get();

               if ($userDeviceDetail && count($userDeviceDetail) > 0) {

                $blankimg = '/build/assets/images/person.png';
                FCMService::send(
                    $userDeviceDetail,
                    [
                        'title' => 'Chat Request',
                        'body' => [
                            'description' => 'Hey ' . $userDeviceDetail[0]->name . ', you received a chat request from ' . $user->name,
                            'notificationType' => 8,
                            'icon' => asset('public/notification-icon/chat.png'),
                            //  "token" => $chatRequest->token,
                            "userName" => $user->name,
                            "profile" => $user->profile ? asset($user->profile) : asset($blankimg),
                            "userId" => $chatRequest->userId,
                            "astroUserId" => $astrologer->userId,
                            "astrologerId" => $chatRequest->astrologerId,
                            "channelName" => $chatRequest->channelName,
                            'chat_duration' => $chatRequest->chat_duration,
                            "receiverId" => $chatRequest->receiverId,
                            "senderId" => $chatRequest->senderId,
                            'chatId' => $chatRequest->id,
                            'fcmToken' => $userDeviceDetail[0]->fcmToken,
                            'subscription_id' => $userDeviceDetail[0]->subscription_id,
                            'sound' => 'app_sound'
                        ],
                        'priority' => 'custom',
                    ]
                );

                $notification = array(
                    'userId' => $userDeviceDetail[0]->astrologerUserId,
                    'title' => 'Hey ' . $userDeviceDetail[0]->name . ', you received a chat request from ' . $user->name,
                    'description' => '',
                    'notificationId' => null,
                    'createdBy' => $userDeviceDetail[0]->astrologerUserId,
                    'modifiedBy' => $userDeviceDetail[0]->astrologerUserId,
                    'notification_type' => 8,
                    'chatRequestId' => $chatRequest->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                );
                DB::table('user_notifications')->insert($notification);
            }

            return response()->json([
                'message'        => 'Call Request Sent Successfully',
                'chatId'         => $chatRequest->id,
                'astrologerId'   => $astrologer->id,
                'astrologerName' => $astrologer->name,
                'status'         => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status'  => 500
            ], 500);
        }
    }

    public function getChatRequest(Request $req)
    {
        try {
            // if (!Auth::guard('api')->user()) {
            //     return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            // }
            $data = $req->only(
                'astrologerId',
            );
            $validator = Validator::make($data, [
                'astrologerId' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }

             $chatRequest = DB::table('chatrequest')
                ->join('users', 'users.id', '=', 'chatrequest.userId')
                ->join('astrologers', 'astrologers.id', '=', 'chatrequest.astrologerId')
                ->leftJoin(
                    DB::raw('(SELECT * FROM intakeform WHERE id IN (SELECT MAX(id) FROM intakeform GROUP BY userId)) as intakeform'),
                    'intakeform.userId',
                    '=',
                    'chatrequest.userId'
                )
                ->leftJoin('user_device_details', 'user_device_details.userId', 'users.id')
                ->where('chatrequest.astrologerId', '=', $req->astrologerId) // Explicitly reference chatrequest table
                ->where('chatrequest.chatStatus', '=', 'Pending')
                ->selectRaw(
                    'users.*,
                      chatrequest.id as chatId, chatrequest.senderId, user_device_details.fcmToken,user_device_details.subscription_id,
                     chatrequest.chat_duration, chatrequest.created_at as chatcreatedat, chatrequest.astrologerId,
                     chatrequest.userId,
                     astrologers.userId as astroUserId,
                     COALESCE(NULLIF(users.name, ""), intakeform.name) as name,
                     COALESCE(NULLIF(users.birthDate, NULL), intakeform.birthDate) as birthDate,
                     COALESCE(NULLIF(users.birthTime, NULL), intakeform.birthTime) as birthTime,
                     COALESCE(NULLIF(users.birthPlace, NULL), intakeform.birthPlace) as birthPlace'
                )
                ->orderByDesc('chatrequest.created_at');

            if ($req->startIndex >= 0 && $req->fetchRecord) {
                $chatRequest->skip($req->startIndex);
                $chatRequest->take($req->fetchRecord);
            }

            $keywords = DB::table('block-keywords')->get(['type','pattern']);

            return response()->json([
                'message' => 'getChatRequest Successfully',
                'status' => 200,
                'recordList' => (object)[
                    'chatRequest' => $chatRequest->get(),
                    'keywords' => json_decode($keywords),
                ]
            ], 200);

        } catch (\Exception$e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
                'error' => false,
            ], 500);
        }
    }

    public function rejectChatRequest(Request $req)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            }
            $data = $req->only(
                'chatId',
            );
            $validator = Validator::make($data, [
                'chatId' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }
            $chatRequest = ChatRequest::find($req->chatId);
            $currenttimestamp = Carbon::now();
            if ($chatRequest) {
                $chatRequest->chatStatus = 'Rejected';
                $chatRequest->updated_at = $currenttimestamp;
                $chatRequest->update();
                $userDeviceDetail = DB::table('user_device_details as usd')
                    ->WHERE('usd.userId', '=', $chatRequest->userId)
                    ->SELECT('usd.*')
                    ->get();
                $astrologer = DB::table('astrologers')
                    ->where('id', '=', $chatRequest->astrologerId)
                    ->select('astrologers.name')
                    ->get();
                if ($userDeviceDetail && count($userDeviceDetail) > 0) {

                    $oneSignalService = new OneSignalService();
                    // $userPlayerIds = $userDeviceDetail->pluck('subscription_id')->all();
                    $userPlayerIds = $userDeviceDetail->pluck('subscription_id')->merge($userDeviceDetail->pluck('subscription_id_web'))->values()->toArray();
                    $notification = [
                        'title' => 'Chat missed with ' . $astrologer[0]->name,
                            'body' => [
                                'description' => 'Sorry, your chat request was not accepted this time. Please try again later or explore other Experts.',
                                'icon' => 'public/notification-icon/chat.png',
                                'notification_type' => 8,
                            ],
                    ];
                    // Send the push notification using the OneSignalService
                    $response = $oneSignalService->sendNotification($userPlayerIds, $notification);
                    $notification = array(
                        'userId' => $chatRequest->userId,
                        'title' => 'Chat missed with ' . $astrologer[0]->name,
                        // 'description' => 'It seems like you have missed/rejected your chat from ' . $astrologer[0]->name . ' .You may initiate it again from the app.',
                        // 'description' => 'Sorry, your chat request was not accepted this time. Please try again later or explore other conversations.',
                        'description' => 'Sorry, your chat request was not accepted this time. Please try again later or explore other Experts.',
                        'notificationId' => null,
                        'createdBy' => $chatRequest->userId,
                        'modifiedBy' => $chatRequest->userId,
                        'notification_type' => 0,
						'chatRequestId' => $chatRequest->id,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    );
                    DB::table('user_notifications')->insert($notification);
                }
                return response()->json([
                    'messge' => 'Reject Chat Request Successfully',
                    'status' => 200,
                ], 200);
            }
        } catch (\Exception$e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
                'error' => false,
            ], 500);
        }
    }

    public function removeFromWaitlist(Request $req)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            }
            $data = $req->only(
                'chatId',
            );
            $validator = Validator::make($data, [
                'chatId' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }
            $chatRequest = ChatRequest::find($req->chatId);
            $chatRequest->Delete();
            return response()->json([
                'messge' => 'Remove Chat Request Successfully',
                'status' => 200,
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
                'error' => false,
            ], 500);
        }
    }

    public function acceptChatRequest(Request $req)
    {

        try {

            $data = $req->only(
                'chatId',

            );

            $validator = Validator::make($data, [
                'chatId' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }
            $chatRequest = ChatRequest::find($req->chatId);

            if($chatRequest->chatStatus!='Pending'){
                 return response()->json([
                    'messge' => 'Request Time Out',
                    'status' => 400,
                ], 400);
            }

            app(\App\Http\Controllers\API\User\CallRequestController::class)->addCallStatus(new Request(['token' => $req->token, 'status' => 'Busy', 'astrologerId' => $chatRequest->astrologerId]));
            $this->addChatStatus(new Request(['token' => $req->token, 'status' => 'Busy', 'astrologerId' => $chatRequest->astrologerId]));

            $user_wallet=DB::table('user_wallets')->where('userId',$chatRequest->userId)->first();
            if($user_wallet){
                $walletamount=$user_wallet->amount;
            }else{
                $walletamount=0;
            }


            $currenttimestamp = Carbon::now();
            if ($chatRequest) {
                $chatRequest->chatStatus = 'Accepted';
                $chatRequest->updated_at = $currenttimestamp;
                $chatRequest->receiverId = '';
                $chatRequest->update();
                $userDeviceDetail = DB::table('user_device_details as us')
                    ->WHERE('us.userId', '=', $chatRequest->userId)
                    ->SELECT('us.*')
                    ->get();

                $astrologer = DB::Table('astrologers')
                    ->leftjoin('user_device_details', 'user_device_details.userId', 'astrologers.userId')
                    ->where('astrologers.id', '=', $chatRequest->astrologerId)
                    ->select('astrologers.name', 'astrologers.profileImage', 'user_device_details.fcmToken','user_device_details.subscription_id','charge')
                    ->get();



                $blankimg='/build/assets/images/person.png';
                if ($userDeviceDetail && count($userDeviceDetail) > 0) {

                    FCMService::send(
                    $userDeviceDetail,
                    [
                          'title' => 'Congrats, your chat request accepted by ' . $astrologer[0]->name,
                        'body' => [
                            "astrologerId" => $chatRequest->astrologerId,
                            "astrologerName" => $astrologer[0]->name,
                            "notificationType" => 3,
                            'chat_duration' => $chatRequest->chat_duration,
                                "profile" => $astrologer[0]->profileImage?$astrologer[0]->profileImage:$blankimg,
                            "token" => $chatRequest->token,
                            "channelName" => $chatRequest->channelName,
                            "receiverId" => $chatRequest->receiverId,
                            "senderId" => $chatRequest->senderId,
                            'description' => 'Your chat request has been accepted! Get ready to connect and engage in meaningful conversation',
                            "firebaseChatId" => $chatRequest->chatId,
                            'chatId' => $req->chatId,
                            'charge' => $astrologer[0]->charge,
                            'walletamount'=>$walletamount,
                            'fcmToken' => $astrologer[0]->fcmToken,
                            'subscription_id' => $astrologer[0]->subscription_id,
                            'icon' => 'public/notification-icon/chat.png',
                        ],
                        'priority' => 'custom',
                    ]
                );





                    $notification = array(
                        'userId' => $chatRequest->userId,
                        'title' => 'Accept Chat Request From ' . $astrologer[0]->name,
                        'description' => '',
                        'notificationId' => null,
                        'createdBy' => $chatRequest->userId,
                        'modifiedBy' => $chatRequest->userId,
                        'notification_type' => 3,
						'chatRequestId' => $chatRequest->id,
                        'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    );
                    DB::table('user_notifications')->insert($notification);
                }else{
                    $notification = array(
                        'userId' => $chatRequest->userId,
                        'title' => 'Accept Chat Request From ' . $astrologer[0]->name,
                        'description' => '',
                        'notificationId' => null,
                        'createdBy' => $chatRequest->userId,
                        'modifiedBy' => $chatRequest->userId,
                        'notification_type' => 3,
						'chatRequestId' => $chatRequest->id,
                        'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    );
                    DB::table('user_notifications')->insert($notification);
                }

                    $keywords = DB::table('block-keywords')->get(['type','pattern']);

                return response()->json([
                    'recordList' => $keywords,
                    'messge' => 'Chat Accepted Successfully',
                    'status' => 200,
                    'firebaseChatId' => $chatRequest->chatId,
                ], 200);
            }
        } catch (\Exception$e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
                'error' => false,
            ], 500);
        }
    }

    public function storeToken(Request $req)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            }
            $data = $req->only(
                'chatId',
                'token',
                'channelName'
            );
            $validator = Validator::make($data, [
                'chatId' => 'required',
                'token' => 'required',
                'channelName' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }
            $chatRequest = ChatRequest::find($req->chatId);
            $currenttimestamp = Carbon::now();
            if ($chatRequest) {
                $chatRequest->chatStatus = 'Accepted';
                $chatRequest->updated_at = $currenttimestamp;
                $chatRequest->token = $req->token;
                $chatRequest->channelName = $req->channelName;
                $chatRequest->update();

            }
            $userDeviceDetail = DB::table('user_device_details')
                ->WHERE('user_device_details.userId', '=', $chatRequest->userId)
                ->SELECT('user_device_details.*')
                ->get();

            $astrologer = DB::Table('astrologers')
                ->where('id', '=', $chatRequest->astrologerId)
                ->select('name', 'profileImage')
                ->get();
              $blankimg='/build/assets/images/person.png';

            return response()->json([
                // 'messge' => $response,
                'status' => 200,
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
                'error' => false,
            ], 500);
        }
    }

    public function insertChatRequest(Request $req)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            }
            $data = $req->only(
                'userId',
                'partnerId',
            );
            $validator = Validator::make($data, [
                'userId' => 'required',
                'partnerId' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }
            $firebaseChatData = ChatRequest::find($req->chatId);
            $chatData = DB::table('chatrequest')
                ->where('senderId', '=', $req->userId)
                ->where('receiverId', '=', $req->partnerId)
                ->get();
            if (!($chatData && count($chatData) > 0)) {
                $partnerChatData = DB::table('chatrequest')
                    ->where('senderId', '=', $req->partnerId)
                    ->where('receiverId', '=', $req->userId)
                    ->get();
                if (!($partnerChatData && count($partnerChatData) > 0)) {
                    $chatId = $req->userId . '_' . $req->partnerId;

                } else {
                    $chatId = $partnerChatData[0]->chatId;
                }
            } else {
                $chatId = $chatData[0]->chatId;
            }
            if ($firebaseChatData) {
                $firebaseChatData->senderId = $req->userId;
                $firebaseChatData->receiverId = $req->partnerId;
                $firebaseChatData->chatId = $chatId;
                $firebaseChatData->update();
            }

            $astrologer = DB::Table('astrologers')
                ->where('id', '=', $firebaseChatData->astrologerId)
                ->select('name', 'profileImage', 'userId')
                ->get();
            $userDeviceDetail = DB::table('user_device_details')
                ->WHERE('user_device_details.userId', '=', $astrologer[0]->userId)
                ->SELECT('user_device_details.*')
                ->get();

            $blankimg='/build/assets/images/person.png';

            return response()->json([
                "status" => 200,
                "recordList" => $chatId,
                "chatId" => $req->chatId,
            ]);
        } catch (\Exception$e) {
            return response()->json([
                'message' => $chatId,
                'status' => 500,
                'error' => false,
            ], 500);
        }
    }

    public function endChatRequest(Request $req)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            } else {
                $id = Auth::guard('api')->user()->id;
            }

            $data = $req->only(
                'chatId',
                'totalMin'
            );
            $validator = Validator::make($data, [
                'chatId' => 'required',
                // 'totalMin' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }

            // $user_country=User::where('id',$id)->where('country','India')->first();
            $user_country=User::where('id',$id)->where('countryCode','+91')->first(); // new added by bhushan borse on 03, june 25

            $inr_usd_conv_rate = DB::table('systemflag')->where('name','UsdtoInr')->select('value')->first();
            $convRateVal = ($inr_usd_conv_rate && !empty($inr_usd_conv_rate->value)) ? (float)$inr_usd_conv_rate->value : 83.5;
            // dd($user_country);

            $chatData = DB::table('chatrequest')
                ->join('astrologers', 'astrologers.id', '=', 'chatrequest.astrologerId')
                ->join('users', 'users.id', '=', 'chatrequest.userId')
                ->where('chatrequest.id', '=', $req->chatId)
                ->select('chatrequest.*', 'users.name', 'astrologers.name as astrologerName', 'astrologers.userId as astrologerUserId')
                ->get();

            // $astrologercountry=Astrologer::where('id', $chatData[0]->astrologerId)->where('country','India')->first();
            $astrologercountry=Astrologer::where('id', $chatData[0]->astrologerId)->where('countryCode', '+91')->first();

                app(\App\Http\Controllers\API\User\CallRequestController::class)->addCallStatus(new Request(['token' => $req->token, 'status' => 'Online', 'astrologerId' => $chatData[0]->astrologerId]));
            $this->addChatStatus(new Request(['token' => $req->token, 'status' => 'Online', 'astrologerId' => $chatData[0]->astrologerId]));

               $updatedAt = Carbon::parse($chatData[0]->updated_at);
               $totalSeconds = $updatedAt->diffInSeconds(Carbon::now());

            if($totalSeconds > $chatData[0]->chat_duration){
                $totalSeconds=$chatData[0]->chat_duration;
            }

            $totalMin = $totalSeconds / 60;
            $totalMin = round($totalMin);

            $astrologerCommission = 0;
            $deduction = 0;
            $charge = Astrologer::query()
                ->where('id', '=', $chatData[0]->astrologerId)
                ->get();
            $chargeAmount = $user_country ? $charge[0]->charge : convertusdtoinr($charge[0]->charge);
            if($charge[0]->emergencyChatStatus){
               $chargeAmount = $user_country ? $charge[0]->emergency_chat_charge : convertinrtousd($charge[0]->emergency_chat_charge);
            }

            if ($charge[0]->isDiscountedPrice && $charge[0]->chat_discount > 0 && !$chatData[0]->is_emergency) {
                $discountedAmount = (float)$chargeAmount * (float)$charge[0]->chat_discount / 100;
                $chargeAmount = $chargeAmount - $discountedAmount;
            }

            if (!$chatData[0]->isFreeSession || $charge[0]->emergencyChatStatus) {

                // if($user_country){
                //     $charge[0]->charge=convertinrtousd($charge[0]->charge);
                // }

                // $deduction = $totalMin * $charge[0]->charge;
                $deduction = $totalMin * $chargeAmount;
                // dd($deduction);

                $commission = DB::table('commissions')
                    ->where('commissionTypeId', '=', '1')
                    ->where('astrologerId', '=', $chatData[0]->astrologerId)
                    ->get();

                // NewCommission

                 $getBoostProfile = ProfileBoosted::where('astrologer_id', $chatData[0]->astrologerId)
                  ->where('boosted_datetime','>=', Carbon::now()->subHours(24))
                  ->first();

                  $syscommission = DB::table('systemflag')->where('name', 'ChatCommission')->select('value')->get(); // Fetch syscommission

                if ($getBoostProfile) {
                    $boostCommission = ProfileBoost::first();
                    $adminCommission = ($boostCommission->chat_commission * $deduction) / 100;
                } elseif ($commission && count($commission) > 0) {
                    $adminCommission = ($commission[0]->commission * $deduction) / 100;
                } else {
                    $adminCommission = ($syscommission[0]->value * $deduction) / 100; // Use syscommission here
                }

                $astrologerCommission = $deduction - $adminCommission;
            }
            $charges = array(
                'totalOrder' => $charge[0]->totalOrder + 1,
            );

            DB::table('astrologers')
                ->where('id', $charge[0]->id)
                ->update($charges);
            $chatDatas = array(
                'totalMin' => $totalMin,
                'chatStatus' => 'Completed',
                'deduction' => $deduction,
                // 'chatRate' => !$chatData[0]->isFreeSession ? $charge[0]->charge : 0,
                'chatRate' => (!$chatData[0]->isFreeSession || $charge[0]->emergencyChatStatus) ? $chargeAmount : 0,
                'deductionFromAstrologer' => $astrologerCommission,
                'created_at' => Carbon::now(),
				'updated_at' => Carbon::now(),
                'inr_usd_conversion_rate'=>$convRateVal,
            );
            DB::Table('chatrequest')
                ->where('id', '=', $req->chatId)
                ->update($chatDatas);

            $charge[0]->totalOrder = $charge[0]->totalOrder ? $charge[0]->totalOrder + 1 : 1;

            // if($user_country){
            //     $deduction=$deduction*$inr_usd_conv_rate->value;
            //     $astrologerCommission=$astrologerCommission*$inr_usd_conv_rate->value;
            // }
          if($totalSeconds >= 45){
            if ($charge[0]->charge > 0) {
                $wallet = DB::table('user_wallets')
                    ->where('userId', '=', $chatData[0]->userId)
                    ->get();

                    $wallets = array(
                        'userId' => $chatData[0]->userId,
                        // 'amount' => (!$chatData[0]->isFreeSession) ? (($user_country) ? ($wallet[0]->amount - ($deduction * $inr_usd_conv_rate->value)) : ($wallet[0]->amount - $deduction)) : (($wallet && count($wallet) > 0) ? $wallet[0]->amount : 0),
                        // 'amount' => (!$chatData[0]->isFreeSession) ? (($user_country) ? ($wallet[0]->amount - $deduction) : ($wallet[0]->amount - convertusdtoinr($deduction, $inr_usd_conv_rate->value))) : (($wallet && count($wallet) > 0) ? $wallet[0]->amount : 0),
                        'amount' => (!$chatData[0]->isFreeSession || $charge[0]->emergencyChatStatus) ? $wallet[0]->amount - $deduction : (($wallet && count($wallet) > 0) ? $wallet[0]->amount : 0),
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
                    ->where('userId', $chatData[0]->astrologerUserId)
                    ->get();
                    $astrologerWall = array(
                        'userId' => $chatData[0]->astrologerUserId,
                        // 'amount' => $astrologerWallet && count($astrologerWallet) > 0 ? $astrologerWallet[0]->amount + ($astrologercountry ? ($astrologerCommission * $inr_usd_conv_rate->value) : $astrologerCommission): ($astrologercountry ? ($astrologerCommission * $inr_usd_conv_rate->value) : $astrologerCommission),
                        'amount' => $astrologerWallet && count($astrologerWallet) > 0 ? $astrologerWallet[0]->amount + $astrologerCommission : $astrologerCommission,
                        'createdBy' => $id,
                        'modifiedBy' => $id,
                    );



                if ($astrologerWallet && count($astrologerWallet) > 0) {
                    DB::table('user_wallets')
                        ->where('userId', $chatData[0]->astrologerUserId)
                        ->update($astrologerWall);
                } else {
                    DB::Table('user_wallets')->insert($astrologerWall);
                }
            }
            $orderRequest = array(
                'userId' => $chatData[0]->userId,
                'astrologerId' => $chatData[0]->astrologerId,
                'orderType' => 'chat',
                'totalPayable' => $deduction,
                'orderStatus' => 'Complete',
                'totalMin' => $totalMin,
                'chatId' => $req->chatId,
                'inr_usd_conversion_rate'=>$convRateVal,
                'created_at' => Carbon::now(),
				'updated_at' => Carbon::now(),

            );
            DB::Table('order_request')->insert($orderRequest);
            $Orderid = DB::getPdo()->lastInsertId();
            $transaction = array(
                'userId' => $chatData[0]->userId,
                'amount' => $deduction,
                'isCredit' => false,
                "transactionType" => 'Chat',
                "orderId" => $Orderid,
                "astrologerId" => $chatData[0]->astrologerId,
				'created_at' => Carbon::now(),
				'updated_at' => Carbon::now(),
                'inr_usd_conversion_rate'=>$convRateVal,
            );
            $astrologerTransaction = array(
                'userId' => $chatData[0]->astrologerUserId,
                'amount' => $astrologerCommission,
                'isCredit' => true,
                "transactionType" => 'Chat',
                "orderId" => $Orderid,
                "astrologerId" => $chatData[0]->astrologerId,
				'created_at' => Carbon::now(),
				'updated_at' => Carbon::now(),
                'inr_usd_conversion_rate'=>$convRateVal,
            );
            if (!$chatData[0]->isFreeSession || $charge[0]->emergencyChatStatus) {

                if ($commission && count($commission) > 0) {
                    $adminGetCommission = array(
                        'commissionTypeId' => 1,
                        "amount" => $adminCommission,
                        "commissionId" => $commission && count($commission) > 0 ? $commission[0]->id : null,
                        "orderId" => $Orderid,
                        "createdBy" => $charge[0]->userId,
                        "modifiedBy" => $charge[0]->userId,
                        'inr_usd_conversion_rate'=>$convRateVal,
                        'created_at' => Carbon::now(),
				'updated_at' => Carbon::now(),
                    );
                    DB::table('admin_get_commissions')->insert($adminGetCommission);
                }elseif($syscommission && count($syscommission) > 0){
                    $adminGetCommission = array(
                        'commissionTypeId' => 1,
                        "amount" => $adminCommission,
                        "commissionId" => null,
                        "orderId" => $Orderid,
                        "createdBy" => $charge[0]->userId,
                        "modifiedBy" => $charge[0]->userId,
                        'inr_usd_conversion_rate'=>$convRateVal,
                        'created_at' => Carbon::now(),
				'updated_at' => Carbon::now(),
                    );
                    DB::table('admin_get_commissions')->insert($adminGetCommission);
                }
            }
            DB::table('wallettransaction')->insert($transaction);
            DB::table('wallettransaction')->insert($astrologerTransaction);

          }

            $firebaseProjectId = DB::table('systemflag')->where('name','firebaseprojectId')->select('value')->first();
            if ($firebaseProjectId && !empty($firebaseProjectId->value)) {
                $apiEndpoint = "https://firestore.googleapis.com/v1/projects/". $firebaseProjectId->value . "/databases/(default)/documents/";
                $client = new Client();

                try {
                    $response = $client->delete($apiEndpoint . "updatechat/{$req->chatId}");
                } catch (\Exception $e) {
                    \Log::warning('Firestore cleanup failed in endChatRequest: ' . $e->getMessage());
                }
            }

            return response()->json([
                'message' => 'User Chat Request End Successfully',
                'status' => 200,
                'recordList' => $deduction,
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
                'error' => false,
            ], 500);
        }
    }

    public function rejectChatRequestFromCustomer(Request $req)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            }
            $data = $req->only(
                'chatId',
            );
            $validator = Validator::make($data, [
                'chatId' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }
            $chatData = ChatRequest::find($req->chatId);


            $userDeviceDetail = DB::table('user_device_details as device')
            ->JOIN('astrologers', 'astrologers.userId', '=', 'device.userId')
            ->WHERE('astrologers.id', '=', $chatData->astrologerId)
            ->SELECT('device.*', 'astrologers.userId as astrologerUserId', 'astrologers.name')
            ->get();

            $response = FCMService::send(
                $userDeviceDetail,
                [
                    'title' => 'Chat Rejected By Customer',
                    'body' => ['description' => 'Chat Rejected By Customer'],

                ],

            );
            app(\App\Http\Controllers\API\User\CallRequestController::class)->addCallStatus(new Request(['token' => $req->token, 'status' => 'Online', 'astrologerId' => $chatData->astrologerId]));
            $this->addChatStatus(new Request(['token' => $req->token, 'status' => 'Online', 'astrologerId' => $chatData->astrologerId]));
            if ($chatData) {
                $chatData->delete();
            }
            return response()->json([
                'message' => 'Chat Request Rejected Successfully',
                'status' => 200,
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
                'error' => false,
            ], 500);
        }
    }

    public function acceptChatRequestFromCustomer(Request $req)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            }
            $data = $req->only(
                'chatId',
            );
            $validator = Validator::make($data, [
                'chatId' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }



            $chatData = ChatRequest::find($req->chatId);
            $customerId = $chatData->userId;
            $astroUserId = DB::table('astrologers')->where('id',$chatData->astrologerId)->value('userId');

            $userDeviceDetail = DB::table('user_device_details as device')
                ->JOIN('astrologers', 'astrologers.userId', '=', 'device.userId')
                ->WHERE('astrologers.id', '=', $chatData->astrologerId)
                ->SELECT('device.*', 'astrologers.userId as astrologerUserId', 'astrologers.name')
                ->get();


            $currenttimestamp = Carbon::now();
            if ($chatData) {
                $chatData->chatStatus = 'Confirmed';
                $chatData->deduction = 0;
                $chatData->updated_at = Carbon::now();
                $chatData->totalMin = 0;
                $chatData->update();
            }

            $response = FCMService::send(
                $userDeviceDetail,
                [
                    'title' => 'Chat Timer Started',
                    'body' => ['description' => 'Chat Timer Started', 'notificationType' => 8, 'icon' => 'public/notification-icon/chat.png', 'timeInInt' => $chatData->chat_duration],

                ],

            );

            // dd($response);
             $keywords = DB::table('block-keywords')->get(['type','pattern']);

            return response()->json([
                'message' => 'Chat Request Accepted Successfully',
                'status' => 200,
                'recordList' => (object)[
                    'customerId' =>$customerId,
                    'astroUserId' =>$astroUserId,
                    'keywords' => json_decode($keywords),
                    ]
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
                'error' => false,
            ], 500);
        }
    }

    public function endLiveChatrequest(Request $req)
    {
        try {
            DB::beginTransaction();
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            } else {
                $id = Auth::guard('api')->user()->id;
            }
            $data = $req->only(
                'userId',
                'astrologerId',
                'totalMin'
            );
            $validator = Validator::make($data, [
                'userId' => 'required',
                'astrologerId' => 'required',
                'totalMin' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }

            // $user_country=User::where('id',$id)->where('country','India')->first();
            $user_country=User::where('id',$id)->where('countryCode','+91')->first();
            $inr_usd_conv_rate = DB::table('systemflag')->where('name','UsdtoInr')->select('value')->first();
            $convRateVal = ($inr_usd_conv_rate && !empty($inr_usd_conv_rate->value)) ? (float)$inr_usd_conv_rate->value : 83.5;
            // $astrologercountry=Astrologer::where('id', $req->astrologerId)->where('country','India')->first();
            $astrologercountry=Astrologer::where('id', $req->astrologerId)->where('countryCode','+91')->first();

            $chargeAmount = 0;

            $sId = DB::Table('callrequest')
                ->where('sId', '=', $req->sId)
                ->get();
            // if (!($sId && count($sId) > 0)) {
                $totalMin = $req->totalMin / 60;
                $totalMin = round($totalMin);
                $charge = Astrologer::query()
                    ->where('id', '=', $req->astrologerId)
                    ->get();
                if($req->callType=='Video'){
                    $chargeAmount = $user_country ? $charge[0]->videoCallRate : convertusdtoinr($charge[0]->videoCallRate);
                }else{
                    $chargeAmount = $user_country ? $charge[0]->charge : convertusdtoinr($charge[0]->charge);
                }
                $syscommission = null;
                $deduction = $totalMin * $charge[0]->charge;
                $commission = DB::table('commissions')
                    ->where('commissionTypeId', '=', '1')
                    ->where('astrologerId', '=', $req->astrologerId)
                    ->get();
                if ($commission && count($commission) > 0) {
                    $adminCommission = ($commission[0]->commission * $deduction) / 100;
                } else {
                    $syscommission = DB::table('systemflag')->where('name', 'ChatCommission')->select('value')->get();
                    $adminCommission = ($syscommission[0]->value * $deduction) / 100;
                }
                $astrologerCommission = $deduction - $adminCommission;
                $chatData = CallRequest::create([
                    'astrologerId' => $req->astrologerId,
                    'userId' => $req->userId,
                    'totalMin' => $totalMin,
                    'callStatus' => 'Completed',
                    // 'callRate' => $charge[0]->charge,
                    'callRate' => $chargeAmount,
                    'deductionFromAstrologer' => $astrologerCommission,
                    'deduction' => $deduction,
                    'chatId' => $req->chatId,
                    'sId' => $req->sId,
                    'sId1' => $req->sId1,
                    'channelName' => $req->channelName,
                    'inr_usd_conversion_rate'=>$convRateVal,
                    'created_at' => Carbon::now(),
				'updated_at' => Carbon::now(),
                ]);

                $charges = array(
                    'totalOrder' => $charge[0]->totalOrder + 1,
                );
                DB::table('astrologers')
                    ->where('id', $charge[0]->id)
                    ->update($charges);
                $charge[0]->totalOrder = $charge[0]->totalOrder ? $charge[0]->totalOrder + 1 : 1;
                $astrologerUserId = DB::table('astrologers')
                    ->where('id', '=', $req->astrologerId)
                    ->get();
                if ($charge[0]->charge) {
                    $wallet = DB::table('user_wallets')
                        ->where('userId', '=', $req->userId)
                        ->get();
                        $wallets = array(
                            // 'amount' => $wallet[0]->amount - ($user_country ? ($deduction * $inr_usd_conv_rate->value) : $deduction),
                            'amount' => $wallet[0]->amount - $deduction,
                        );


                    DB::table('user_wallets')
                        ->where('id', $wallet[0]->id)
                        ->update($wallets);

                    $astrologerWallet = DB::table('user_wallets')
                        ->join('astrologers', 'astrologers.userId', '=', 'user_wallets.userId')
                        ->where('astrologers.id', $req->astrologerId)
                        ->select('user_wallets.*')
                        ->get();
                        $astrologerWall = array(
                            'userId' => $astrologerUserId[0]->userId,
                            // 'amount' => $astrologerWallet && count($astrologerWallet) > 0 ? $astrologerWallet[0]->amount + ($astrologercountry ? ($astrologerCommission * $inr_usd_conv_rate->value) : $astrologerCommission) : ($astrologercountry ? ($astrologerCommission * $inr_usd_conv_rate->value) : $astrologerCommission),
                            'amount' => $astrologerWallet && count($astrologerWallet) > 0 ? $astrologerWallet[0]->amount + $astrologerCommission : $astrologerCommission,
                            'createdBy' => $id,
                            'modifiedBy' => $id,
                        );

                    if ($astrologerWallet && count($astrologerWallet) > 0) {
                        DB::table('user_wallets')
                            ->where('id', $astrologerWallet[0]->id)
                            ->update($astrologerWall);
                    } else {
                        DB::Table('user_wallets')->insert($astrologerWall);
                    }
                }
                $orderRequest = array(
                    'userId' => $req->userId,
                    'astrologerId' => $req->astrologerId,
                    'orderType' => 'chat',
                    'totalPayable' => $deduction,
                    'orderStatus' => 'Complete',
                    'totalMin' => $totalMin,
                    'callId' => $chatData->id,
                    'inr_usd_conversion_rate'=>$convRateVal,
                    'created_at' => Carbon::now(),
				'updated_at' => Carbon::now(),

                );
                DB::Table('order_request')->insert($orderRequest);
                $id = DB::getPdo()->lastInsertId();
                $transaction = array(
                    'userId' => $req->userId,
                    'amount' => $deduction,
                    'isCredit' => false,
                    "transactionType" => $req->transactionType,
                    "orderId" => $id,
                    "astrologerId" => $req->astrologerId,
					'created_at' => Carbon::now(),
					'updated_at' => Carbon::now(),
                    'inr_usd_conversion_rate'=>$convRateVal,
                );
                $astrologerTransaction = array(
                    'userId' => $astrologerUserId[0]->userId,
                    'amount' => $astrologerCommission,
                    'isCredit' => true,
                    "transactionType" => $req->transactionType,
                    "orderId" => $id,
                    "astrologerId" => $req->astrologerId,
					'created_at' => Carbon::now(),
					'updated_at' => Carbon::now(),
                    'inr_usd_conversion_rate'=>$convRateVal,
                );
                if ($adminCommission > 0) {
                    $adminGetCommission = array(
                        'commissionTypeId' => 1,
                        "amount" => $adminCommission,
                        "commissionId" => $commission && count($commission) > 0 ? $commission[0]->id : null,
                        "orderId" => $id,
                        // "description"=>"Commission for chat between ".$charge[0]->name ." and ".$chatData[0]->name ." for ".$totalMin . " Minutes",
                        "createdBy" => $charge[0]->userId,
                        "modifiedBy" => $charge[0]->userId,
                        'inr_usd_conversion_rate'=>$convRateVal,
                        'created_at' => Carbon::now(),
				'updated_at' => Carbon::now(),
                    );
                    DB::table('admin_get_commissions')->insert($adminGetCommission);
                }elseif($syscommission && count($syscommission) > 0){
                    $adminGetCommission = array(
                        'commissionTypeId' => 1,
                        "amount" => $adminCommission,
                        "commissionId" => null,
                        "orderId" => $id,
                        "createdBy" => $charge[0]->userId,
                        "modifiedBy" => $charge[0]->userId,
                        'inr_usd_conversion_rate'=>$convRateVal,
                        'created_at' => Carbon::now(),
				'updated_at' => Carbon::now(),
                    );
                    DB::table('admin_get_commissions')->insert($adminGetCommission);
                }

                DB::table('wallettransaction')->insert($transaction);
                DB::table('wallettransaction')->insert($astrologerTransaction);
                DB::commit();
                $data = array(
                    'deduction' => $deduction,
                    'callId' => $chatData->id,
                );

                return response()->json([
                    'message' => 'Chat Request End Successfully',
                    'status' => 200,
                    'recordList' => $data,
                ], 200);
            // } else {
            //     return response()->json([
            //         'message' => 'Chat Request End Successfully',
            //         'status' => 200,
            //         'recordList' => [],
            //     ], 200);
            // }
        } catch (\Exception$e) {
            DB::rollback();
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
                'error' => false,
            ], 500);
        }
    }

    public function intakeForm(Request $req)
{
    try {
        $id = Auth::guard('api')->check() ? Auth::guard('api')->user()->id : $req->userId;

        $data = $req->only('name', 'phoneNumber');
        $validator = Validator::make($data, [
            'name' => 'required',
            'phoneNumber' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
        }

        $intakeForm = [
            'name' => $req->name,
            'phoneNumber' => $req->phoneNumber,
            'countryCode' => $req->countryCode,
            'gender' => $req->gender,
            'birthDate' => $req->birthDate,
            'birthTime' => $req->birthTime ?: '',
            'birthPlace' => $req->birthPlace ?: '',
            'maritalStatus' => $req->maritalStatus,
            'occupation' => $req->occupation ?: '',
            'topicOfConcern' => $req->topicOfConcern,
            'partnerName' => $req->partnerName,
            'partnerBirthDate' => $req->partnerBirthDate,
            'partnerBirthTime' => $req->partnerBirthTime,
            'partnerBirthPlace' => $req->partnerBirthPlace,
            'longitude' => $req->longitude,
            'latitude' => $req->latitude,
            'timezone' => $req->timezone ?? 5.5,
            'userId' => $id,
        ];

        $intake = DB::table('intakeform')->where('userId', $id)->first();
        if ($intake) {
            DB::table('intakeform')->where('userId', $id)->update($intakeForm);
        } else {
            DB::table('intakeform')->insert($intakeForm);
        }

        return response()->json([
            'message' => 'Chat Intake Form Add Successfully',
            'status' => 200,
            'recordList' => $intakeForm,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => $e->getMessage(),
            'status' => 500,
            'error' => true,
        ], 500);
    }
}


    public function getUserIntakForm(Request $req)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            } else {
                $id = Auth::guard('api')->user()->id;
            }
            $id = $req->userId ? $req->userId : $id;
            $intakeData = DB::table('intakeform')
                ->where('userId', '=', $id)
                ->get();

                $default_time = DB::table('systemflag')
                ->where('name', '=', 'defaultcalltime')
                ->value('value');
                // dd( $default_time);
            return response()->json([
                'message' => 'Chat Intake Form Get Successfully',
                'status' => 200,
                'recordList' => $intakeData,
                'default_time' =>$default_time
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
                'error' => false,
            ], 500);
        }
    }

    public function saveToken(Request $req)
    {
        try {
            $data = array(
                'fcm_token' => $req->token,
            );
            DB::table('users')
                ->where('id', '=', $req->id)
                ->update($data);
        } catch (\Exception$e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function checkChatSessionTaken(Request $req)
    {
        try {
            $user = Auth::guard('api')->user();
            $id = $user ? $user->id : ($req->userId ? (int)$req->userId : ($req->input('userId') ? (int)$req->input('userId') : null));
            if (!$id) {
                return response()->json([
                    'status' => 200,
                    'recordList' => false,
                ], 200);
            }
            $session = DB::table('chatrequest')
                ->where('userId', '=', $id)
                ->where('astrologerId', '=', $req->astrologerId)
                ->where('chatStatus', '=', 'Pending')
                ->get();
            $isAvailable = false;
            if ($session && count($session) > 0) {
                $isAvailable = true;
            }
            return response()->json([
                'status' => 200,
                'recordList' => $isAvailable,
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function checkCallSessionTaken(Request $req)
    {
        try {
            $user = Auth::guard('api')->user();
            $id = $user ? $user->id : ($req->userId ? (int)$req->userId : ($req->input('userId') ? (int)$req->input('userId') : null));
            if (!$id) {
                return response()->json([
                    'status' => 200,
                    'recordList' => false,
                ], 200);
            }
            $session = DB::table('callrequest')
                ->where('userId', '=', $id)
                ->where('astrologerId', '=', $req->astrologerId)
                ->where('callStatus', '=', 'Pending')
                ->get();
            $isAvailable = false;
            if ($session && count($session) > 0) {
                $isAvailable = true;
            }
            return response()->json([
                'status' => 200,
                'recordList' => $isAvailable,
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function addChatStatus(Request $req)
    {
        try {
            // if (!Auth::guard('api')->user()) {
            //     return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            // } else {
            //     Auth::guard('api')->user()->id;
            // }

            $status = array(
                'chatStatus' => $req->status,
                'chatWaitTime' => ($req->status == 'Offline' || $req->status == 'Online') ? null : $req->waitTime,
            );


            DB::table('astrologers')->where('id', '=', $req->astrologerId)
                ->update($status);




            return response()->json([
                "message" => "Update Astrologer",
                'status' => 200,
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function checkFreeSessionAvailable(Request $req)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            } else {
                $id = Auth::guard('api')->user()->id;
            }
            $isAddNewRequest = true;
            $isChatRequest = DB::table('chatrequest')->where('userId', $id)->where('chatStatus', '=', 'Pending')->first();
            $isCallRequest = DB::table('callrequest')->where('userId', $id)->where('callStatus', '=', 'Pending')->first();
            if ($isChatRequest || $isCallRequest) {
                $isAddNewRequest = false;
            }
            return response()->json([
                "isAddNewRequest" => $isAddNewRequest,
                "message" => "Update Astrologer",
                'status' => 200,
            ], 200);

        } catch (\Exception$e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }


     // New Auto Delete call chat
     public function deleteChatCallRequest(Request $request)
     {
         $oneMinuteAgo = Carbon::now()->subMinute();

         $acceptedchatrequest=ChatRequest::where('chatStatus', 'Accepted')
             ->where('updated_at', '<', $oneMinuteAgo)
             ->get();

           foreach ($acceptedchatrequest as $acceptedchat) { try { $this->rejectChatRequest(new Request(['chatId' => $acceptedchat->id])); } catch (\Throwable $e) {} }

          $acceptedcallrequest=CallRequest::where('callStatus', 'Accepted')
         ->where('updated_at', '<', $oneMinuteAgo)
         ->get();

          foreach ($acceptedcallrequest as $acceptedcall) { try { app(\App\Http\Controllers\API\User\CallRequestController::class)->rejectCallRequest(new Request(['callId' => $acceptedcall->id])); } catch (\Throwable $e) {} }



          // Handle Chat Requests
         $pendingChatRequests = ChatRequest::where('chatStatus', 'Pending')
             ->where('updated_at', '<', $oneMinuteAgo)
             ->get();

         foreach ($pendingChatRequests as $chatRequest) { try { $this->reject(new Request(['chatId' => $chatRequest->id])); } catch (\Throwable $e) {} }

         //     // Handle Call Requests
         $pendingCallRequests = CallRequest::where('callStatus', 'Pending')
             ->where('updated_at', '<', $oneMinuteAgo)
             ->get();

         foreach ($pendingCallRequests as $callRequest) { try { app(\App\Http\Controllers\API\User\CallRequestController::class)->reject(new Request(['callId' => $callRequest->id])); } catch (\Throwable $e) {} }


         return response()->json(['message' => 'Old chat and call requests deleted successfully.']);
     }


      // for topup
   public function updateChatMinute(Request $request)
   {
       $request->validate([
           'chatId' => 'required|integer|exists:chatrequest,id',
           'chat_duration' => 'required|integer|min:1',
       ]);

       $chat = DB::table('chatrequest')->where('id', $request->chatId)->first();

       if ($chat) {

           $user_wallet=DB::table('user_wallets')->where('userId',$chat->userId)->first();
           $astrologerCharge=DB::table("astrologers")->where('id',$chat->astrologerId)->first();

           $getcurrentDuration = $this->getcurrentDuration(new Request(['chatId' => $request->chatId]))->getData(true);

           $chatDurationMinutes = $getcurrentDuration['chatDuration'] / 60;

           $remainingWalletAmount = $user_wallet->amount - ($chatDurationMinutes * $astrologerCharge->charge);

            $chatDurationMinutesforcharge = $request->chat_duration / 60;
           $total_charge = $astrologerCharge->charge * $chatDurationMinutesforcharge;

        //    if ($total_charge >= $remainingWalletAmount){
        //         return response()->json(['message' => 'Insufficient Funds','status' => 400], 400);
        //    }

            if ($total_charge >= $remainingWalletAmount && $total_charge != $remainingWalletAmount){
                 return response()->json(['message' => 'Insufficient Funds','status' => 400, 'total_charge'=>$total_charge,  'remainingWalletAmount'=> $remainingWalletAmount], 400);
            }


           DB::table('chatrequest')
               ->where('id', $request->chatId)
               ->update(['chat_duration' => $chat->chat_duration + $request->chat_duration]);

               $apiEndpoint = "https://firestore.googleapis.com/v1/projects/". env('FIREBASE_PROJECT_ID') . "/databases/(default)/documents/";
                $client = new Client();

                $updatedDuration = $chat->chat_duration + $request->chat_duration;

                $firestoreData = [
                    'fields' => [
                        'chatId' => ['integerValue' => $request->chatId],
                        'duration' => ['integerValue' => $updatedDuration],
                        'updatedAt' => ['timestampValue' => now()->toIso8601String()],
                        'userId' => ['integerValue' => $chat->userId],
                        'astrologerId' => ['integerValue' => $chat->astrologerId]
                    ]
                ];

                // Build query string manually to avoid Guzzle auto-indexing arrays
                $query = "updateMask.fieldPaths=duration&updateMask.fieldPaths=updatedAt&currentDocument.exists=true";

                try {
                    // Append query string directly to the URL (no 'query' array)
                    $response = $client->patch($apiEndpoint . "updatechat/{$request->chatId}?" . $query, [
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
                        $response = $client->post($apiEndpoint . "updatechat?documentId={$request->chatId}", [
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
               ->WHERE('astrologers.id', '=', $chat->astrologerId)
               ->SELECT('device.*', 'astrologers.userId as astrologerUserId', 'astrologers.name')
               ->get();

            $response = FCMService::send(
               $userDeviceDetail,
               [
                   'title' => 'Chat Timer Started',
                   'body' => [
                        'description' => 'Chat Timer Started',
                        'notificationType' => 8,
                        'icon' => 'public/notification-icon/chat.png',
                        'timeInInt' => $chat->chat_duration + $request->chat_duration,
                   ],

               ]
           );

           return response()->json(['message' => 'Chat duration updated successfully.','status' => 200,], 200);
       }

       return response()->json(['message' => 'Chat request not found.','status' => 400,], 400);
   }


   // get currenct duration
    public function getcurrentDuration(Request $request)
   {
       $request->validate([
           'chatId' => 'required|integer|exists:chatrequest,id',

       ]);

       $chatconfirmed = DB::table('chatrequest')
           ->where('id', $request->chatId)
           ->where('chatStatus', 'Confirmed')
           ->first();

       if ($chatconfirmed) {
           DB::table('chat_last_interactions')->updateOrInsert(
               ['chatId' => $request->chatId],
               [
                   'last_interaction_time' => Carbon::now(),
                   'updated_at' => Carbon::now(),
               ]
           );
       }



       $chat = DB::table('chatrequest')->where('id', $request->chatId)->select("chat_duration")->first();

           return response()->json([
               "chatDuration" => $chat->chat_duration,
               "message" => "Chat Duration",
               'status' => 200,
           ], 200);


       return response()->json(['message' => 'Chat request not found.'], 404);
   }

    // Add consultation session message (REST backend chat store)
    public function addSessionChatMessage(Request $req)
    {
        try {
            $sessionId = $req->input('sessionId') ?? $req->sessionId;
            $fromUserId = $req->input('fromUserId') ?? $req->fromUserId;
            $message = $req->input('message') ?? $req->message;
            $attachment = $req->input('attachment') ?? $req->attachment;

            if (!$sessionId || !$message) {
                return response()->json(['status' => 400, 'message' => 'sessionId and message required'], 400);
            }

            $id = DB::table('session_chats')->insertGetId([
                'sessionId' => (string)$sessionId,
                'fromUserId' => (string)$fromUserId,
                'message' => (string)$message,
                'attachment' => $attachment,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Message sent successfully',
                'recordList' => [
                    'id' => $id,
                    'sessionId' => $sessionId,
                    'fromUserId' => $fromUserId,
                    'message' => $message,
                    'created_at' => Carbon::now()->toIso8601String(),
                ],
            ], 200);
        } catch (\Throwable $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    // Retrieve consultation session messages
    public function getSessionChatMessages(Request $req)
    {
        try {
            $sessionId = $req->input('sessionId') ?? $req->sessionId;
            $startIndex = (int)($req->input('startIndex') ?? $req->startIndex ?? 0);
            $fetchRecord = (int)($req->input('fetchRecord') ?? $req->fetchRecord ?? 100);

            if (!$sessionId) {
                return response()->json(['status' => 200, 'recordList' => []], 200);
            }

            $messages = DB::table('session_chats')
                ->where('sessionId', (string)$sessionId)
                ->orderBy('id', 'asc')
                ->skip($startIndex)
                ->take($fetchRecord)
                ->get();

            return response()->json([
                'status' => 200,
                'recordList' => $messages,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }
}
