<?php

namespace App\Http\Controllers\API\Astrologer;

use App\Http\Controllers\Controller;
use App\Models\AstrologerModel\Astrologer;
use App\Models\AstrologerModel\LiveAstro;
use App\Models\UserModel\WaitList;
use App\Services\FCMService;
use App\Services\HundredMsService;
use App\Services\OneSignalService;
use App\Services\ZegoCloudService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Str;

class LiveAstroController extends Controller
{
    protected $zegoApi;

    public function __construct(ZegoCloudService $zegoApi)
    {
        $this->zegoApi = $zegoApi;
    }

    public function addLiveAstrologer(Request $req, HundredMsService $hundredMsService)
    {
        try {
            // ✅ Auth check
            $user = Auth::guard('api')->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            }

            $astrologerProfile = Astrologer::where('userId', $user->id)->first();
            if (!$astrologerProfile) {
                return response()->json(['error' => 'No astrologer profile found for this user', 'status' => 403], 403);
            }

            $data = $req->only('astrologerId', 'channelName', 'token', 'liveChatToken', 'allow_unscheduled', 'force');
            $validator = Validator::make($data, [
                'astrologerId' => 'required|integer',
                'channelName' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }

            if ((int) $req->astrologerId !== (int) $astrologerProfile->id) {
                return response()->json(['error' => 'Forbidden: mismatched astrologer id', 'status' => 403], 403);
            }

            // ✅ Check if any schedule exists within the valid time window
            $check = $this->checkScheduleWindow($req->astrologerId);
            $allowUnscheduled = $req->boolean('allow_unscheduled', false) || $req->boolean('force', false);

            $useSchedule = null;
            if ($check['status'] === 'allowed') {
                $useSchedule = $check['schedule'];
            }

            // ✅ Mark expired schedule
            if ($check['status'] === 'expired') {
                $schedule = $check['schedule'];
                $schedule->isActive = 2;  // expired
                $schedule->save();
            }

            // ❌ Removed the block for "too_early" — astrologer can go live anytime now

            // ✅ Create token / room
            $streaProvider = systemflag('streaming_provider');
            if ($streaProvider == 'hms') {
                $RoomResponse = $hundredMsService->createHmsRoom('livestreaming', $req->channelName, 21600);
                if (!isset($RoomResponse['room_id'])) {
                    return response()->json(['message' => 'HMS room creation failed', 'status' => 500], 500);
                }
                $roomId = $RoomResponse['room_id'];
                $userLiveStreamToken = $hundredMsService->generateHmsRoomCode($roomId, 'viewer-near-realtime');
                $token = $userLiveStreamToken['auth_token'] ?? null;
            } elseif ($streaProvider == 'zegocloud') {
                $roomId = Str::random(16);
                $token = $this->zegoApi->generateToken($req->astrologerId, $roomId, 86400);
                $zegoResponse = [
                    'token' => $token,
                    'user_id' => $req->astrologerId,
                    'roomId' => $req->channelName
                ];
            } else {
                $token = $req->token ?? null;
                $RoomResponse = null;
                $userLiveStreamToken = null;
            }

            DB::beginTransaction();

            if ($useSchedule) {
                // ✅ Use existing schedule → mark as live
                $useSchedule->channelName = $req->channelName;
                $useSchedule->token = $token;
                $useSchedule->liveChatToken = $req->liveChatToken ?? $useSchedule->liveChatToken;
                $useSchedule->isActive = 1;
                $useSchedule->schedule_live_status = 1;
                $useSchedule->save();

                $liveRecord = $useSchedule;
            } else {
                // ✅ No schedule → create live directly
                $liveRecord = LiveAstro::create([
                    'astrologerId' => $req->astrologerId,
                    'channelName' => $req->channelName,
                    'token' => $token,
                    'isActive' => 1,
                    'liveChatToken' => $req->liveChatToken ?? null,
                    'schedule_live_date' => null,
                    'stream_method' => $streaProvider,
                    'schedule_live_time' => null,
                    'schedule_live_status' => 1,
                ]);
            }

            DB::commit();

            // ✅ Fetch astrologer data
            $astrologer_data = DB::table('astrologers')
                ->join('liveastro', 'liveastro.astrologerId', 'astrologers.id')
                ->where('astrologers.id', $req->astrologerId)
                ->select('astrologers.charge', 'astrologers.videoCallRate', 'astrologers.name', 'liveastro.channelName', 'liveastro.token')
                ->first();

            $this->sendAstrologerLiveNotification($req->astrologerId, $astrologer_data, $token, $req->channelName);

            return response()->json([
                'message' => 'Live Added Successfully',
                'hms_response' => $RoomResponse ?? '',
                'user_hms_response' => $userLiveStreamToken ?? null,
                'zegocloud' => $zegoResponse ?? null,
                'status' => 200,
                'live_record' => $liveRecord,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
                'error' => true,
            ], 500);
        }
    }

    private function checkScheduleWindow($astrologerId)
    {
        $schedules = LiveAstro::where('astrologerId', $astrologerId)
            ->whereNotNull('schedule_live_date')
            ->whereNotNull('schedule_live_time')
            ->orderByRaw("CONCAT(schedule_live_date, ' ', schedule_live_time) ASC")
            ->get();

        $now = Carbon::now();

        foreach ($schedules as $schedule) {
            $scheduleDateTime = Carbon::parse($schedule->schedule_live_date . ' ' . $schedule->schedule_live_time);
            $startWindow = $scheduleDateTime->copy()->subMinutes(10);
            $endWindow = $scheduleDateTime->copy()->addMinutes(10);

            if ($now->between($startWindow, $endWindow)) {
                return ['status' => 'allowed', 'schedule' => $schedule];
            }

            if ($now->greaterThan($endWindow) && $schedule->schedule_live_status != 3) {
                $schedule->schedule_live_status = 3;  // expired
                $schedule->save();
            }

            if ($now->lessThan($startWindow)) {
                return [
                    'status' => 'too_early',
                    'schedule' => $schedule,
                    'startWindow' => $startWindow,
                    'scheduleDateTime' => $scheduleDateTime
                ];
            }
        }

        return ['status' => 'no_schedule', 'schedule' => null];
    }

    private function sendAstrologerLiveNotification($astrologerId, $astrologer_data, $token, $channelName)
    {
        // Followers
        $followers = DB::table('astrologer_followers')
            ->join('user_device_details', 'user_device_details.userId', 'astrologer_followers.userId')
            ->join('user_roles', 'user_roles.userId', 'astrologer_followers.userId')
            ->where('astrologer_followers.astrologerId', $astrologerId)
            ->where('user_roles.roleId', 3)
            ->select('user_device_details.*', 'astrologer_followers.userId')
            ->get();

        // Reminder users
        $reminderUsers = DB::table('user_reminders')
            ->join('user_device_details', 'user_device_details.userId', '=', 'user_reminders.userId')
            ->where('user_reminders.astrologerId', $astrologerId)
            ->select('user_device_details.*', 'user_reminders.userId')
            ->get();

        // Merge both
        $allUsers = $followers->merge($reminderUsers);

        // Prepare notification
        $notification = [
            'title' => ($astrologer_data->name ?? 'Astrologer') . ' is Online!',
            'body' => [
                'astrologerId' => $astrologerId,
                'notificationType' => 4,
                'description' => 'Join before their waitlist grows!',
                'isFollow' => 1,
                'name' => $astrologer_data->name ?? null,
                'charge' => $astrologer_data->charge ?? null,
                'videoCallRate' => $astrologer_data->videoCallRate ?? null,
                'channelName' => $astrologer_data->channelName ?? $channelName,
                'token' => $astrologer_data->token ?? $token,
                'icon' => 'public/notification-icon/instagram-live.png',
            ],
        ];

        // Collect OneSignal IDs
        $userPlayerIds = $allUsers
            ->pluck('subscription_id')
            ->merge($allUsers->pluck('subscription_id_web'))
            ->filter()
            ->values()
            ->toArray();

        if (!empty($userPlayerIds)) {
            $oneSignalService = new OneSignalService();
            $oneSignalService->sendNotification($userPlayerIds, $notification);
        }

        // Save in DB
        foreach ($allUsers as $user) {
            DB::table('user_notifications')->insert([
                'userId' => $user->userId,
                'title' => $notification['title'],
                'description' => $notification['body']['description'],
                'notificationId' => null,
                'createdBy' => $user->userId,
                'modifiedBy' => $user->userId,
                'notification_type' => 4,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    public function getLiveAstrologer(Request $req)
    {
        try {
            // Fetch live astrologers
            $liveAstrologer = Astrologer::join('liveastro', 'liveastro.astrologerId', '=', 'astrologers.id')
                ->where('liveastro.isActive', true)
                ->orderBy('liveastro.id', 'DESC')
                ->get();

            // Check if authorization header exists
            if ($req->header('Authorization')) {
                if (!Auth::guard('api')->user()) {
                    return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
                }

                $id = Auth::guard('api')->user()->id;

                foreach ($liveAstrologer as $astrologer) {
                    $isFollow = DB::table('astrologer_followers')
                        ->where('userId', $id)
                        ->where('astrologerId', $astrologer->astrologerId)
                        ->exists();

                    $astrologer->isFollow = $isFollow;

                    $astrologer->chat_discounted_rate = $astrologer->isDiscountedPrice
                        ? ($astrologer->charge - ($astrologer->charge * $astrologer->chat_discount / 100))
                        : 0;

                    $astrologer->audio_discounted_rate = $astrologer->isDiscountedPrice
                        ? ($astrologer->charge - ($astrologer->charge * $astrologer->audio_discount / 100))
                        : 0;

                    $astrologer->video_discounted_rate = $astrologer->isDiscountedPrice
                        ? ($astrologer->videoCallRate - ($astrologer->videoCallRate * $astrologer->video_discount / 100))
                        : 0;
                }
            }

            // ⭐ Image Path Conversion Function
            $convertToAsset = function ($value) {
                if (empty($value))
                    return null;
                if (Str::startsWith($value, ['http://', 'https://'])) {
                    return $value;
                }
                return asset($value);
            };

            // Convert astrologer image fields for each astrologer
            $fieldsToConvert = ['profileImage', 'aadhar_card', 'pan_card', 'certificate', 'astro_video'];
            foreach ($liveAstrologer as $astrologer) {
                foreach ($fieldsToConvert as $field) {
                    if (isset($astrologer->$field)) {
                        $astrologer->$field = $convertToAsset($astrologer->$field);
                    }
                }
            }

            return response()->json([
                'message' => 'Get Live Astrologer Successfully',
                'status' => 200,
                'recordList' => $liveAstrologer,
                'call_method' => systemflag('streaming_provider')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function endLiveSession(Request $req)
    {
        try {
            $liveAstrologer = LiveAstro::query();
            $liveAstrologer
                ->where('astrologerId', '=', $req->astrologerId)
                // ✅ Delete only active or scheduled lives
                ->where(function ($query) {
                    $query
                        ->where('isActive', 1)
                        ->orWhere('schedule_live_status', 1);
                });

            if ($liveAstrologer) {
                $liveAstrologer->delete();
                $waitList = DB::table('waitlist')
                    ->where('astrologerId', $req->astrologerId)
                    ->delete();
            }

            return response()->json([
                'message' => 'Live Session End Successfully',
                'status' => 200,
                'recordList' => $liveAstrologer,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function addLiveChatToken(Request $req)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            }
            $data = $req->only(
                'astrologerId',
                'liveChatToken',
            );

            // Validate the data
            $validator = Validator::make($data, [
                'astrologerId' => 'required',
                'liveChatToken' => 'required',
            ]);

            // Send failed response if request is not valid
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }

            $chat = LiveAstro::query()
                ->where('astrologerId', '=', $req->astrologerId)
                ->get();

            if ($chat) {
                $chat[0]->liveChatToken = $req->liveChatToken;
                $chat[0]->update();
            }
            return response()->json([
                'message' => 'Live Chat Token Successfully',
                'status' => 200,
                'recordList' => $chat,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function getToken(Request $req)
    {
        try {
            $token = DB::table('liveastro')
                ->where('channelName', '=', $req->channelName)
                ->get();
            return response()->json([
                'message' => 'Get Token Successfully',
                'status' => 200,
                'recordList' => $token && count($token) > 0 ? $token[0]->token : null,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function getUpcomingAstrologer(Request $req)
    {
        try {
            $upcomingAstrologer = DB::table('astrologers')
                ->select(
                    'astrologers.*',
                )
                ->whereNotExists(function ($query) {
                    $query
                        ->select(DB::raw(1))
                        ->from('liveastro')
                        ->whereRaw('astrologers.id = liveastro.astrologerId');
                })
                ->where('astrologers.isVerified', 1)
                ->get();

            if ($upcomingAstrologer && count($upcomingAstrologer) > 0) {
                foreach ($upcomingAstrologer as $upcoming) {
                    $astrologerAvailability = DB::table('astrologer_availabilities')
                        ->where('astrologerId', '=', $upcoming->id)
                        ->get();

                    $working = [];
                    if ($astrologerAvailability && count($astrologerAvailability) > 0) {
                        $day = [];

                        $day = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                        foreach ($day as $days) {
                            $day = array(
                                'day' => $days,
                            );
                            $currentday = $days;
                            $result = array_filter(json_decode($astrologerAvailability), function ($event) use ($currentday) {
                                return $event->day === $currentday;
                            });
                            $ti = [];

                            foreach ($result as $available) {
                                $time = array(
                                    'fromTime' => $available->fromTime,
                                    'toTime' => $available->toTime,
                                );
                                array_push($ti, $time);
                            }
                            $weekDay = array(
                                'day' => $days,
                                'time' => $ti,
                            );
                            array_push($working, $weekDay);
                        }
                    }
                    $upcoming->availability = $working;
                }
            }
            return response()->json([
                'recordList' => $upcomingAstrologer,
                'status' => 200,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function searchLiveAstrologer(request $req)
    {
        try {
            $upcomingAstrologer = DB::table('astrologers')
                ->select(
                    'astrologers.*',
                )
                ->whereNotExists(function ($query) {
                    $query
                        ->select(DB::raw(1))
                        ->from('liveastro')
                        ->whereRaw('astrologers.id = liveastro.astrologerId');
                })
                ->whereRaw(sql: "astrologers.name LIKE '%" . $req->searchString . "%' ")
                ->get();

            if ($upcomingAstrologer && count($upcomingAstrologer) > 0) {
                foreach ($upcomingAstrologer as $upcoming) {
                    $astrologerAvailability = DB::table('astrologer_availabilities')
                        ->where('astrologerId', '=', $upcoming->id)
                        ->get();

                    $working = [];
                    if ($astrologerAvailability && count($astrologerAvailability) > 0) {
                        $day = [];

                        $day = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                        foreach ($day as $days) {
                            $day = array(
                                'day' => $days,
                            );
                            $currentday = $days;
                            $result = array_filter(json_decode($astrologerAvailability), function ($event) use ($currentday) {
                                return $event->day === $currentday;
                            });
                            $ti = [];

                            foreach ($result as $available) {
                                $time = array(
                                    'fromTime' => $available->fromTime,
                                    'toTime' => $available->toTime,
                                );
                                array_push($ti, $time);
                            }
                            $weekDay = array(
                                'day' => $days,
                                'time' => $ti,
                            );
                            array_push($working, $weekDay);
                        }
                    }
                    $upcoming->availability = $working;
                }
            }

            return response()->json([
                'recordList' => $upcomingAstrologer,
                'status' => 200,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    // ---------------------------------------------------------------------------------------------------------------------------------
    public function addLiveAstrologerWeb(Request $req)
    {
        try {
            $data = $req->only('astrologerId', 'channelName', 'token', 'schedule_id');

            $validator = Validator::make($data, [
                'astrologerId' => 'required',
                'channelName' => 'required',
                'token' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }

            LiveAstro::query()
                ->where('astrologerId', $req->astrologerId)
                ->where(function ($query) {
                    $query
                        ->where('isActive', 1)
                        ->orWhere('schedule_live_status', 1);
                })
                ->delete();

            $streamProvider = systemflag('streaming_provider');

            if (!empty($req->schedule_id)) {
                DB::table('liveastro')
                    ->where('id', $req->schedule_id)
                    ->update(['schedule_live_status' => 1]);
            }

            LiveAstro::create([
                'astrologerId' => $req->astrologerId,
                'channelName' => $req->channelName,
                'token' => $req->token,
                'isActive' => true,
                'stream_method' => $streamProvider,
                'liveChatToken' => $req->liveChatToken ?? null,
                'schedule_id' => $req->schedule_id ?? null,
            ]);

            return response()->json([
                'message' => 'Astrologer is now live',
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function addLiveSchedule(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
        }

        try {
            // Get astrologer profile from astrologers table
            $astrologer = Astrologer::where('userId', $user->id)->first();

            if (!$astrologer) {
                return response()->json([
                    'status' => false,
                    'message' => 'No astrologer profile found for this user'
                ], 404);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'schedule_live_date' => 'required|date',
                'schedule_live_time' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            $streaProvider = systemflag('streaming_provider');
            // Create new LiveAstro schedule
            $schedule = LiveAstro::create([
                'astrologerId' => $astrologer->id,
                'schedule_live_date' => $request->schedule_live_date,
                'schedule_live_time' => $request->schedule_live_time,
                'isActive' => false,
                'stream_method' => $streaProvider,
            ]);

            // Send notification to reminder users
            $this->sendAstrologerScheduleNotification($astrologer, $schedule);

            return response()->json([
                'status' => true,
                'message' => 'Schedule saved successfully!',
                'data' => $schedule
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // 🔹 Private function: Schedule notification
    private function sendAstrologerScheduleNotification($astrologer, $schedule)
    {
        // Followers
        $followers = DB::table('astrologer_followers')
            ->join('user_device_details', 'user_device_details.userId', '=', 'astrologer_followers.userId')
            ->where('astrologer_followers.astrologerId', $astrologer->id)
            ->select('user_device_details.*', 'astrologer_followers.userId')
            ->get();

        $reminderUsers = DB::table('user_reminders')
            ->join('user_device_details', 'user_device_details.userId', '=', 'user_reminders.userId')
            ->where('user_reminders.astrologerId', $astrologer->id)
            ->select('user_device_details.*', 'user_reminders.userId')
            ->get();

        $allUsers = $followers->merge($reminderUsers);

        $userPlayerIds = $allUsers
            ->pluck('subscription_id')
            ->merge($allUsers->pluck('subscription_id_web'))
            ->filter()
            ->values()
            ->toArray();

        if (empty($userPlayerIds)) {
            return;
        }

        $notification = [
            'title' => $astrologer->name . ' scheduled a Live!',
            'body' => [
                'astrologerId' => $astrologer->id,
                'notificationType' => 5,
                'description' => 'Live on ' . $schedule->schedule_live_date . ' at ' . $schedule->schedule_live_time,
                'name' => $astrologer->name,
                'icon' => 'public/notification-icon/calendar.png',
            ],
        ];

        $oneSignalService = new OneSignalService();
        $oneSignalService->sendNotification($userPlayerIds, $notification);

        foreach ($allUsers as $user) {
            DB::table('user_notifications')->insert([
                'userId' => $user->userId,
                'title' => $notification['title'],
                'description' => $notification['body']['description'],
                'notificationId' => null,
                'createdBy' => $user->userId,
                'modifiedBy' => $user->userId,
                'notification_type' => 5,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    public function addLiveScheduleweb(Request $req)
    {
        try {
            $data = $req->only(
                'astrologerId',
                'schedule_live_date',
                'schedule_live_time'
            );

            $validator = Validator::make($data, [
                'astrologerId' => 'required|integer',
                'schedule_live_date' => 'required|date',
                'schedule_live_time' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $streamProvider = systemflag('streaming_provider');

            LiveAstro::create([
                'astrologerId' => $req->astrologerId,
                'schedule_live_date' => $req->schedule_live_date,
                'schedule_live_time' => $req->schedule_live_time,
                'isActive' => false,
                'stream_method' => $streamProvider
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Schedule saved successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function scheduleList(Request $request)
    {
        try {
            // Check condition if user login
            $user = Auth::guard('api')->user();
            $today = Carbon::now()->format('Y-m-d');
            if ($user) {
                // Check astrologer profile
                $astrologer = Astrologer::where('userId', $user->id)->first();

                if ($astrologer) {
                    // Only Astrologer login
                    $schedules = LiveAstro::select(
                        'liveastro.id',
                        'liveastro.astrologerId',
                        'astrologers.name as astrologerName',
                        'astrologers.profileImage',
                        DB::raw("CASE
                                    WHEN liveastro.isActive = 0 THEN 'Upcoming'
                                    WHEN liveastro.isActive = 1 THEN 'Live'
                                    ELSE 'Unknown'
                                 END as statusLabel"),
                        'liveastro.schedule_live_date',
                        'liveastro.schedule_live_time'
                    )
                        ->join('astrologers', 'astrologers.id', '=', 'liveastro.astrologerId')
                        ->where('liveastro.schedule_live_date', '>', $today)
                        ->orWhere(function ($q) use ($today) {
                            $q
                                ->where('liveastro.schedule_live_date', '=', $today)
                                ->where('liveastro.schedule_live_time', '>=', date('H:i'));
                        })
                        ->where('liveastro.astrologerId', $astrologer->id)  // only login astrologers
                        ->orderBy('liveastro.schedule_live_date', 'desc')
                        ->get();

                    return response()->json([
                        'status' => 200,
                        'message' => 'Schedule list fetched successfully (astrologer only)',
                        'data' => $schedules
                    ], 200);
                }
            }

            //  if not login astrologer (all astrologers list)
            $today = date('Y-m-d');

            $schedules = LiveAstro::select(
                'liveastro.id',
                'liveastro.astrologerId',
                'astrologers.name as astrologerName',
                'astrologers.profileImage',
                DB::raw("CASE
                            WHEN liveastro.isActive = 0 THEN 'Upcoming'
                            WHEN liveastro.isActive = 1 THEN 'Live'
                            ELSE 'Unknown'
                         END as statusLabel"),
                'liveastro.schedule_live_date',
                'liveastro.schedule_live_time'
            )
                ->join('astrologers', 'astrologers.id', '=', 'liveastro.astrologerId')
                ->where('liveastro.schedule_live_date', '>', $today)
                ->orWhere(function ($q) use ($today) {
                    $q
                        ->where('liveastro.schedule_live_date', '=', $today)
                        ->where('liveastro.schedule_live_time', '>=', date('H:i'));
                })
                ->orderBy('liveastro.schedule_live_date', 'asc')
                ->get();

            $schedules = $schedules->map(function ($item) {
                $value = $item->profileImage;

                if (!Str::startsWith($value, ['http://', 'https://'])) {
                    $value = ltrim($value, '/');
                    $item->profileImage = asset($value);
                } else {
                    $item->profileImage = $value;
                }

                return $item;
            });

            return response()->json([
                'status' => 200,
                'message' => 'Upcoming schedules fetched successfully (all astrologers)',
                'data' => $schedules
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function scheduleDelete(Request $request)
    {
        try {
            //  Login check
            $user = Auth::guard('api')->user();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            //  Astrologer profile check
            $astrologer = Astrologer::where('userId', $user->id)->first();
            if (!$astrologer) {
                return response()->json([
                    'status' => false,
                    'message' => 'No astrologer profile found for this user'
                ], 404);
            }

            //  Validation
            $validator = Validator::make($request->all(), [
                'scheduleId' => 'required|integer|exists:liveastro,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            //  Schedule find karo
            $schedule = LiveAstro::where('id', $request->scheduleId)
                ->where('astrologerId', $astrologer->id)
                ->first();

            if (!$schedule) {
                return response()->json([
                    'status' => false,
                    'message' => 'Schedule not found or does not belong to you'
                ], 404);
            }

            //  Delete schedule
            $schedule->delete();

            return response()->json([
                'status' => true,
                'message' => 'Schedule deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function userReminder(Request $request)
    {
        try {
            //  User login check
            $user = Auth::guard('api')->user();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            //  Validation
            $validator = Validator::make($request->all(), [
                'astrologerId' => 'required|integer|exists:astrologers,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $astrologerId = $request->astrologerId;

            //  Check if already reminder set
            $reminder = DB::table('user_reminders')
                ->where('userId', $user->id)
                ->where('astrologerId', $astrologerId)
                ->first();

            if ($reminder) {
                return response()->json([
                    'status' => true,
                    'message' => 'Reminder already set for this astrologer'
                ], 200);
            }

            //  Insert reminder
            DB::table('user_reminders')->insert([
                'userId' => $user->id,
                'astrologerId' => $astrologerId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Reminder set successfully. You will now get notifications for this astrologer.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // --- LIST SCHEDULES ---
    public function scheduleListweb(Request $request)
    {
        $astroAuth = astroauthcheck();

        if (!$astroAuth || empty($astroAuth['astrologerId'])) {
            if ($request->expectsJson()) {
                return response()->json([]);
            }
            return redirect()->back()->with('error', 'Astrologer not logged in.');
        }

        $astrologerId = $astroAuth['astrologerId'];

        $schedules = LiveAstro::where('astrologerId', $astrologerId)
            ->orderBy('schedule_live_date', 'desc')
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => true,
                'data' => $schedules
            ]);
        }

        return view('frontend.astrologers.pages.astrologer-live-schedule-list', compact('schedules'));
    }

    public function updateLiveSchedule(Request $req, $id)
    {
        $validator = Validator::make($req->all(), [
            'schedule_live_date' => 'required|date',
            'schedule_live_time' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ]);
        }

        $schedule = LiveAstro::find($id);

        if (!$schedule) {
            return response()->json([
                'status' => false,
                'message' => ' Schedule not found!',
            ]);
        }

        $schedule->schedule_live_date = $req->schedule_live_date;
        $schedule->schedule_live_time = $req->schedule_live_time;
        $schedule->save();

        return response()->json([
            'status' => true,
            'message' => 'Schedule updated successfully!',
            'data' => $schedule
        ]);
    }

    public function deleteLiveSchedule($id)
    {
        $schedule = LiveAstro::find($id);

        if (!$schedule) {
            return response()->json([
                'status' => false,
                'message' => 'Schedule not found!'
            ]);
        }

        $schedule->delete();

        return response()->json([
            'status' => true,
            'message' => ' Schedule deleted successfully!'
        ]);
    }

    public function sendNotificationForliveAstro(Request $req)
    {
        // Fetch the astrologer's details
        $astrologer_data = DB::table('astrologers')
            ->join('liveastro', 'liveastro.astrologerId', 'astrologers.id')
            ->where('astrologers.id', $req->astrologerId)
            ->select('astrologers.charge', 'astrologers.videoCallRate', 'astrologers.name', 'liveastro.channelName', 'liveastro.token')
            ->first();  // Use `first()` instead of `get()` since we expect a single result

        if (!$astrologer_data) {
            return response()->json(['error' => 'Astrologer not found'], 404);
        }

        // Fetch users who follow the astrologer and have device details
        $followers = DB::table('astrologer_followers')
            ->join('user_device_details', 'user_device_details.userId', 'astrologer_followers.userId')
            ->join('user_roles', 'user_roles.userId', 'astrologer_followers.userId')
            ->where('astrologer_followers.astrologerId', $req->astrologerId)
            ->where('user_roles.roleId', 3)  // Assuming roleId = 3 is for users
            ->select('user_device_details.*')
            ->get();

        if ($followers->isEmpty()) {
            return response()->json(['message' => 'No followers found for this astrologer'], 200);
        }

        // Prepare notification data
        $notification = [
            'title' => $astrologer_data->name . ' is Online!',
            'body' => [
                'astrologerId' => $req->astrologerId,
                'notificationType' => 4,
                'description' => 'Join before their waitlist grows!',
                'isFollow' => 1,  // Since we're only sending to followers, this is always 1
                'name' => $astrologer_data->name,
                'charge' => $astrologer_data->charge,
                'videoCallRate' => $astrologer_data->videoCallRate,
                'channelName' => $astrologer_data->channelName,
                'token' => $astrologer_data->token,
                'icon' => 'public/notification-icon/instagram-live.png',
            ],
        ];

        // Collect all player IDs (subscription IDs) for OneSignal
        $userPlayerIds = $followers
            ->pluck('subscription_id')
            ->merge($followers->pluck('subscription_id_web'))
            ->filter()  // Remove null or empty values
            ->values()
            ->toArray();

        // Send the push notification using the OneSignalService
        $oneSignalService = new OneSignalService();
        $response = $oneSignalService->sendNotification($userPlayerIds, $notification);

        // Save notifications in the database for each follower
        foreach ($followers as $follower) {
            DB::table('user_notifications')->insert([
                'userId' => $follower->userId,
                'title' => $notification['title'],
                'description' => $notification['body']['description'],
                'notificationId' => null,
                'createdBy' => $follower->userId,
                'modifiedBy' => $follower->userId,
                'notification_type' => 4,
            ]);
        }

        return response()->json(['message' => 'Notifications sent successfully', 'response' => $response], 200);
    }
}
