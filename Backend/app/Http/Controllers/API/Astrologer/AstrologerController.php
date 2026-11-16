<?php

namespace App\Http\Controllers\API\Astrologer;

use App\Helpers\StorageHelper;
use App\Http\Controllers\Controller;
use App\Models\AdminModel\DefaultProfile;
use App\Models\AdminModel\DegreeOrDiploma;
use App\Models\AdminModel\FulltimeJob;
use App\Models\AdminModel\HighestQualification;
use App\Models\AdminModel\Language;
use App\Models\AdminModel\MainSourceOfBusiness;
use App\Models\AdminModel\TravelCountry;
use App\Models\AiAstrologerModel\AiChatHistory;
use App\Models\AstrologerModel\Astrologer;
use App\Models\AstrologerModel\AstrologerAvailability;
use App\Models\AstrologerModel\AstrologerCategory;
use App\Models\AstrologerModel\AstrologerGift;
use App\Models\AstrologerModel\Skill;
use App\Models\UserModel\CallRequest;
Use App\Models\UserModel\CallRequestApoinment;
use App\Models\UserModel\ChatRequest;
use App\Models\UserModel\User;
use App\Models\UserModel\UserDeviceDetail;
use App\Models\UserModel\UserOrder;
use App\Models\UserModel\UserReport;
use App\Models\UserModel\UserRole;
use App\Models\UserModel\UserWallet;
use App\Models\AstrologerDocument;
use App\Models\CourseOrder;
use App\Models\EmailTemplate;
use App\Models\PujaOrder;
use App\Models\User as BaseUser;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Exception;
use Request as Req;

class AstrologerController extends Controller
{
    // Add Astrologer
    public function addAstrologer(Request $req)
    {
        try {
            DB::beginTransaction();
            $data = $req->only(
                'name',
                'email',
                'contactNo',
                'gender',
                'birthDate',
                'primarySkill',
                'allSkill',
                'languageKnown',
                'profileImage',
                'charge',
                'experienceInYears',
                'dailyContribution',
                'isWorkingOnAnotherPlatform',
                'whyOnBoard',
                'interviewSuitableTime',
                'mainSourceOfBusiness',
                'highestQualification',
                'degree',
                'college',
                'learnAstrology',
                'astrologerCategoryId',
                'instaProfileLink',
                'facebookProfileLink',
                'linkedInProfileLink',
                'youtubeChannelLink',
                'websiteProfileLink',
                'isAnyBodyRefer',
                'minimumEarning',
                'maximumEarning',
                'loginBio',
                'NoofforeignCountriesTravel',
                'currentlyworkingfulltimejob',
                'goodQuality',
                'biggestChallenge',
                'whatwillDo',
                'isVerified',
                'whatsappNo',
                'pancardNo',
                'aadharNo',
                'ifscCode',
                'bankBranch',
                'bankName',
                'accountType',
                'accountNumber',
                'upi',
                'videoCallRate',
                'reportRate',
            );

            // Validate the data
            $validator = Validator::make($data, [
                'astrologerCategoryId' => 'required',
                'name' => 'required|string',
                'email' => 'required|unique:users,email',
                'contactNo' => 'required|max:10|unique:users,contactNo',
                'gender' => 'required',
                'birthDate' => 'required',
                'dailyContribution' => 'required',
                'languageKnown' => 'required',
                'primarySkill' => 'required',
                'allSkill' => 'required',
                'languageKnown' => 'required',
                'charge' => 'required',
                'experienceInYears' => 'required',
                'interviewSuitableTime' => 'required',
                'mainSourceOfBusiness' => 'required',
                'minimumEarning' => 'required',
                'maximumEarning' => 'required',
                'charge' => 'required',
                'whyOnBoard' => 'required',
                'highestQualification' => 'required',
                'whatsappNo' => 'required',
                'aadharNo' => 'required',
                'pancardNo' => 'required',
                'ifscCode' => 'required',
                'bankBranch' => 'required',
                'bankName' => 'required',
                'accountNumber' => 'required',
                'videoCallRate' => 'required',
                'reportRate' => 'required'
                // 'country'=>'required',
            ]);
            if ($validator->fails()) {
                DB::rollback();
                return response()->json([
                    'error' => $validator->messages(),
                    'status' => 400,
                ], 400);
            }

            $countryCode = !empty($req->countryCode) ? $req->countryCode : '+91';
            if (!str_starts_with($countryCode, '+')) {
                $countryCode = '+' . $countryCode;
            }

            $user = User::create([
                'name' => $req->name,
                'contactNo' => $req->contactNo,
                'email' => $req->email,
                'birthDate' => $req->birthDate,
                'gender' => $req->gender,
                'location' => $req->currentCity,
                'countryCode' => $countryCode,
                'country' => $countryCode == '+91' ? 'india' : $req->country,
            ]);
            $referral_token = 'REF' . numberToCharacterString($user->id);
            $user->update([
                'referral_token' => $referral_token,
            ]);

            $slug = Str::slug($req->name, '-');
            $originalSlug = $slug;
            $counter = 1;
            while (DB::table('astrologers')->where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            // Create a new astrologer
            $astrologer = Astrologer::create([
                'name' => $req->name,
                'slug' => $slug,
                'userId' => $user->id,
                'email' => $req->email,
                'contactNo' => $req->contactNo,
                'gender' => $req->gender,
                'birthDate' => $req->birthDate,
                'primarySkill' => implode(',', array_column($req->primarySkill, 'id')),
                'allSkill' => implode(',', array_column($req->allSkill, 'id')),
                'languageKnown' => implode(',', array_column($req->languageKnown, 'id')),
                'charge' => $req->charge,
                'experienceInYears' => $req->experienceInYears,
                'dailyContribution' => $req->dailyContribution,
                'hearAboutAstroguru' => $req->hearAboutAstroguru,
                'isWorkingOnAnotherPlatform' => $req->isWorkingOnAnotherPlatform,
                'whyOnBoard' => $req->whyOnBoard,
                'interviewSuitableTime' => $req->interviewSuitableTime,
                'currentCity' => $req->currentCity,
                'mainSourceOfBusiness' => $req->mainSourceOfBusiness,
                'highestQualification' => $req->highestQualification,
                'degree' => $req->degree,
                'college' => $req->college,
                'learnAstrology' => $req->learnAstrology,
                'astrologerCategoryId' => implode(',', array_column($req->astrologerCategoryId, 'id')),
                'instaProfileLink' => $req->instaProfileLink,
                'linkedInProfileLink' => $req->linkedInProfileLink,
                'facebookProfileLink' => $req->facebookProfileLink,
                'websiteProfileLink' => $req->websiteProfileLink,
                'youtubeChannelLink' => $req->youtubeChannelLink,
                'isAnyBodyRefer' => $req->isAnyBodyRefer,
                'minimumEarning' => $req->minimumEarning,
                'maximumEarning' => $req->maximumEarning,
                'loginBio' => $req->loginBio,
                'NoofforeignCountriesTravel' => $req->NoofforeignCountriesTravel,
                'currentlyworkingfulltimejob' => $req->currentlyworkingfulltimejob,
                'goodQuality' => $req->goodQuality,
                'biggestChallenge' => $req->biggestChallenge,
                'whatwillDo' => $req->whatwillDo,
                'isVerified' => false,
                'highestQualification' => $req->highestQualification,
                'charge' => $req->charge,
                'hearAboutAstroguru' => $req->hearAboutAstroguru,
                'whyOnBoard' => $req->whyOnBoard,
                'currentCity' => $req->currentCity,
                'countryCode' => $countryCode,
                'country' => $countryCode == '+91' ? 'india' : $req->country,
                'videoCallRate' => $req->videoCallRate,
                'reportRate' => $req->reportRate ? $req->reportRate : 0,
                'nameofplateform' => $req->nameofplateform,
                'monthlyEarning' => $req->monthlyEarning,
                'referedPerson' => $req->referedPerson,
                'videoCallRate' => $req->videoCallRate,
                'charge_usd' => $req->charge_usd,
                'videoCallRate_usd' => $req->videoCallRate_usd,
                'reportRate_usd' => $req->reportRate_usd,
                'whatsappNo' => $req->whatsappNo,
                'aadharNo' => $req->aadharNo,
                'pancardNo' => $req->pancardNo,
                'ifscCode' => $req->ifscCode,
                'bankBranch' => $req->bankBranch,
                'accountType' => $req->accountType,
                'bankName' => $req->bankName,
                'accountNumber' => $req->accountNumber,
                'upi' => $req->upi,
                'accountHolderName' => $req->accountHolderName,
                'createdBy' => $user->id,
                'modifiedBy' => $user->id,
            ]);
            $documents = AstrologerDocument::query()->get();
            $documentMap = $req->documentMap ?? [];

            foreach ($documents as $document) {
                $columnName = Str::snake($document->name);

                // Check if the document exists in the input
                if (array_key_exists($columnName, $documentMap)) {
                    // Ensure the column exists in the astrologers table
                    if (!Schema::hasColumn('astrologers', $columnName)) {
                        Schema::table('astrologers', function (Blueprint $table) use ($columnName) {
                            $table->string($columnName)->nullable();
                        });
                    }

                    // Handle base64 document data
                    $docImage = $documentMap[$columnName] ?? null;
                    if ($docImage) {
                        $time = Carbon::now()->timestamp;
                        $destinationpath = 'public/storage/images/documents/';
                        $imageName = $columnName . '_' . $req->id . '_' . $time;
                        $docPath = $destinationpath . $imageName . '.png';

                        if (!file_exists($docPath)) {
                            file_put_contents($docPath, base64_decode($docImage));
                        }

                        $astrologer->$columnName = $docPath;
                    }
                }
            }
            if ($req->profileImage) {
                $time = Carbon::now()->timestamp;
                $destinationpath = 'public/storage/images/';
                $imageName = 'astrologer_' . $user->id . $time;
                $path = $destinationpath . $imageName . '.png';
                file_put_contents($path, base64_decode($req->profileImage));
            } else {
                $path = null;
            }
            $user->profile = $path;
            $user->update();
            $astrologer->profileImage = $path;
            $astrologer->update();
            UserRole::create([
                'userId' => $user->id,
                'roleId' => 2,
            ]);
            if ($req->userDeviceDetails) {
                UserDeviceDetail::create([
                    'userId' => $user->id,
                    'appId' => $req->appId,
                    'deviceId' => $req->deviceId,
                    'fcmToken' => $req->fcmToken,
                    'deviceLocation' => $req->deviceLocation,
                    'deviceManufacturer' => $req->deviceManufacturer,
                    'deviceModel' => $req->deviceModel,
                    'appVersion' => $req->appVersion,
                    'subscription_id' => $req->subscription_id,
                ]);
            }
            if ($req->astrologerAvailability) {
                foreach ($req->astrologerAvailability as $astrologeravailable) {
                    foreach ($astrologeravailable['time'] as $availability) {
                        AstrologerAvailability::create([
                            'astrologerId' => $astrologer['id'],
                            'day' => $astrologeravailable['day'],
                            'fromTime' => $availability['fromTime'],
                            'toTime' => $availability['toTime'],
                            'createdBy' => $astrologer['id'],
                            'modifiedBy' => $astrologer['id'],
                        ]);
                    }
                }
            }
            $astrologer->astrologerAvailability = $req->astrologerAvailability;
            $astrologer->allSkill = array_map('intval', explode(',', $astrologer->allSkill));
            $astrologer->primarySkill = array_map('intval', explode(',', $astrologer->primarySkill));
            $astrologer->languageKnown = array_map('intval', explode(',', $astrologer->languageKnown));
            $astrologer->astrologerCategoryId = array_map('intval', explode(',', $astrologer->astrologerCategoryId));
            $allSkill = DB::table('skills')
                ->whereIn('id', $astrologer->allSkill)
                ->select('name', 'id')
                ->get();
            $primarySkill = DB::table('skills')
                ->whereIn('id', $astrologer->primarySkill)
                ->select('name', 'id')
                ->get();
            $languageKnown = DB::table('languages')
                ->whereIn('id', $astrologer->languageKnown)
                ->select('languageName', 'id')
                ->get();
            $category = DB::table('astrologer_categories')
                ->whereIn('id', $astrologer->astrologerCategoryId)
                ->select('name', 'id')
                ->get();
            $astrologer->allSkill = $allSkill;
            $astrologer->primarySkill = $primarySkill;
            $astrologer->languageKnown = $languageKnown;
            $astrologer->astrologerCategoryId = $category;

            $register = EmailTemplate::where('name', 'partner_registration')->first();
            if ($register) {
                $logo = DB::table('systemflag')->where('name', 'AdminLogo')->select('value')->first();
                $astrologerName = $astrologer->name;
                $body = str_replace(
                    ['{{$username}}', '{{$logo}}'],
                    [$astrologerName, asset($logo->value)],
                    $register->description
                );

                $body = html_entity_decode($body);
                Mail::send([], [], function ($message) use ($astrologer, $register, $body) {
                    $message
                        ->to($astrologer->email)
                        ->subject($register->subject)
                        ->html($body);
                });
            }

            DB::commit();
            return response()->json([
                'message' => 'Astrologer add sucessfully',
                'recordList' => $astrologer,
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    // Login astrologer
    public function loginAstrologer(Request $req)
    {
        try {
            $dummyPassword = 'dummy@123';

            if ($req->contactNo) {
                $credentials = [
                    'contactNo' => $req->contactNo,
                    'password' => $dummyPassword,
                ];

                $astrologer = DB::table('astrologers')
                    ->join('user_roles', 'astrologers.userId', '=', 'user_roles.userId')
                    ->where('contactNo', $req->contactNo)
                    ->where('user_roles.roleId', 2)
                    ->where('astrologers.isDelete', false)
                    ->select('astrologers.*')
                    ->get();
            } elseif ($req->email) {
                $credentials = [
                    'email' => $req->email,
                    'password' => $dummyPassword,
                ];

                $astrologer = DB::table('astrologers')
                    ->join('user_roles', 'astrologers.userId', '=', 'user_roles.userId')
                    ->where('email', $req->email)
                    ->where('user_roles.roleId', 2)
                    ->where('astrologers.isDelete', false)
                    ->select('astrologers.*')
                    ->get();
            }

            if ($astrologer && count($astrologer) > 0) {
                if (!$astrologer[0]->isVerified) {
                    return response()->json([
                        'message' => 'Your Account is not verified by admin',
                        'status' => 400,
                    ], 400);
                }

                $user = BaseUser::where('id', $astrologer[0]->userId)->first();
                if ($user && !$user->password) {
                    $user->password = Hash::make($dummyPassword);
                    $user->save();
                }

                if (!$token = Auth::guard('api')->tokenById($user->id)) {
                    return response()->json([
                        'error' => false,
                        'message' => 'Invalid login credentials',
                        'status' => 401,
                    ], 401);
                }

                if ($req->userDeviceDetails) {
                    if ($req->contactNo) {
                        $userDeviceDetail = DB::table('user_device_details')
                            ->join('users', 'users.id', '=', 'user_device_details.userId')
                            ->where('users.contactNo', $req->contactNo)
                            ->select('user_device_details.*')
                            ->get();
                    } elseif ($req->email) {
                        $userDeviceDetail = DB::table('user_device_details')
                            ->join('users', 'users.id', '=', 'user_device_details.userId')
                            ->where('users.email', $req->email)
                            ->select('user_device_details.*')
                            ->get();
                    }

                    if ($userDeviceDetail && count($userDeviceDetail) == 0) {
                        $userDeviceDetail = UserDeviceDetail::create([
                            'userId' => $astrologer[0]->userId,
                            'appId' => $req->userDeviceDetails['appId'],
                            'deviceId' => $req->userDeviceDetails['deviceId'],
                            'fcmToken' => $req->userDeviceDetails['fcmToken'],
                            'deviceLocation' => $req->userDeviceDetails['deviceLocation'],
                            'deviceManufacturer' => $req->userDeviceDetails['deviceManufacturer'],
                            'deviceModel' => $req->userDeviceDetails['deviceModel'],
                            'appVersion' => $req->userDeviceDetails['appVersion'],
                            'subscription_id' => $req->userDeviceDetails['subscription_id'],
                        ]);
                    } else {
                        $device = UserDeviceDetail::find($userDeviceDetail[0]->id);
                        if ($device) {
                            $device->appId = $req->userDeviceDetails['appId'];
                            $device->deviceId = $req->userDeviceDetails['deviceId'];
                            $device->fcmToken = $req->userDeviceDetails['fcmToken'];
                            $device->deviceLocation = $req->userDeviceDetails['deviceLocation'];
                            $device->deviceManufacturer = $req->userDeviceDetails['deviceManufacturer'];
                            $device->deviceModel = $req->userDeviceDetails['deviceModel'];
                            $device->appVersion = $req->userDeviceDetails['appVersion'];
                            $device->subscription_id = $req->userDeviceDetails['subscription_id'];
                            $device->updated_at = Carbon::now()->timestamp;
                            $device->update();
                        }
                    }
                }

                $documents = AstrologerDocument::query()->get();
                $documentMap = [];
                foreach ($documents as $document) {
                    $columnName = Str::snake($document->name);
                    if (Schema::hasColumn('astrologers', $columnName)) {
                        $documentValue = $astrologer[0]->$columnName ?? null;
                        if (!empty($documentValue)) {
                            if (!str_starts_with($documentValue, 'http')) {
                                $documentValue = asset($documentValue);
                            }
                        }
                        $documentMap[$columnName] = $documentValue;
                    }
                }
                $astrologer[0]->documentMap = $documentMap;

                if (!empty($astrologer[0]->profileImage)) {
                    if (!str_starts_with($astrologer[0]->profileImage, 'http')) {
                        $astrologer[0]->profileImage = asset($astrologer[0]->profileImage);
                    }
                }

                if (!empty($astrologer[0]->aadhar_card)) {
                    if (!str_starts_with($astrologer[0]->aadhar_card, 'http')) {
                        $astrologer[0]->aadhar_card = asset($astrologer[0]->aadhar_card);
                    }
                }

                if (!empty($astrologer[0]->pan_card)) {
                    if (!str_starts_with($astrologer[0]->pan_card, 'http')) {
                        $astrologer[0]->pan_card = asset($astrologer[0]->pan_card);
                    }
                }

                if (!empty($astrologer[0]->certificate)) {
                    if (!str_starts_with($astrologer[0]->certificate, 'http')) {
                        $astrologer[0]->certificate = asset($astrologer[0]->certificate);
                    }
                }

                $astrologer[0]->allSkill = array_map('intval', explode(',', $astrologer[0]->allSkill));
                $astrologer[0]->primarySkill = array_map('intval', explode(',', $astrologer[0]->primarySkill));
                $astrologer[0]->languageKnown = array_map('intval', explode(',', $astrologer[0]->languageKnown));
                $astrologer[0]->astrologerCategoryId = array_map('intval', explode(',', $astrologer[0]->astrologerCategoryId));

                $allSkill = DB::table('skills')->whereIn('id', $astrologer[0]->allSkill)->select('name', 'id')->get();
                $primarySkill = DB::table('skills')->whereIn('id', $astrologer[0]->primarySkill)->select('name', 'id')->get();
                $languageKnown = DB::table('languages')->whereIn('id', $astrologer[0]->languageKnown)->select('languageName', 'id')->get();
                $category = DB::table('astrologer_categories')->whereIn('id', $astrologer[0]->astrologerCategoryId)->select('name', 'id')->get();

                $astrologer[0]->allSkill = $allSkill;
                $astrologer[0]->primarySkill = $primarySkill;
                $astrologer[0]->languageKnown = $languageKnown;
                $astrologer[0]->astrologerCategoryId = $category;

                $astrologerAvailability = DB::table('astrologer_availabilities')
                    ->where('astrologerId', '=', $astrologer[0]->id)
                    ->get();
                $working = [];
                if ($astrologerAvailability && count($astrologerAvailability) > 0) {
                    $daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                    foreach ($daysOfWeek as $dayName) {
                        $dayAvailability = array_filter($astrologerAvailability->toArray(), function ($entry) use ($dayName) {
                            return $entry->day === $dayName;
                        });

                        $times = [];
                        foreach ($dayAvailability as $avail) {
                            $times[] = [
                                'fromTime' => $avail->fromTime,
                                'toTime' => $avail->toTime,
                            ];
                        }

                        $working[] = [
                            'day' => $dayName,
                            'time' => $times,
                        ];
                    }
                }

                $astrologer[0]->astrologerAvailability = $working;

                return $this->respondWithTokenApp($token, $astrologer);
            } else {
                return response()->json([
                    'status' => 400,
                    'message' => $req->contactNo ? 'Contact No is Not Registered' : 'Email is Not Registered',
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    protected function respondWithTokenApp($token, $id)
    {
        try {
            return response()->json([
                'success' => true,
                'token' => $token,
                'token_type' => 'Bearer',
                'status' => 200,
                'recordList' => $id,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    // Get all the data of the astrologer
    public function getAstrologer(Request $req)
    {
        try {
            $boostCutoff = Carbon::now()->subHours(24);
            $astrologerQuery = Astrologer::query()
                ->select('astrologers.*', DB::raw('
                    (CASE WHEN abp.astrologer_id IS NOT NULL THEN 1 ELSE 0 END) as is_boosted,
                    COALESCE(AVG(user_reviews.rating), 0) as rating
                '))
                ->leftJoin('astrologer_boosted_profiles as abp', function ($join) use ($boostCutoff) {
                    $join
                        ->on('astrologers.id', '=', 'abp.astrologer_id')
                        ->where('abp.boosted_datetime', '>=', $boostCutoff);
                })
                ->leftJoin('user_reviews', 'astrologers.id', '=', 'user_reviews.astrologerId')
                ->where([
                    // ['astrologers.isActive', true],
                    ['astrologers.isVerified', true],
                    ['astrologers.isDelete', false]
                ])
                ->whereNotIn('astrologers.id', function ($query) use ($req) {
                    $query
                        ->select('astrologerId')
                        ->from('blockastrologer')
                        ->where('userId', '=', $req->userId);
                })
                ->groupBy('astrologers.id');

            // Search functionality
            if ($s = $req->input('s')) {
                $astrologerQuery->where('name', 'LIKE', "%{$s}%");
            }

            if ($req->inRandom) {
                $astrologerQuery
                    ->orderByRaw('
                CASE
                    WHEN abp.astrologer_id IS NOT NULL THEN 1
                    ELSE 2
                END
                ')
                    ->orderByRaw('
                    CASE
                        WHEN astrologers.chatStatus = "Online" OR astrologers.callStatus = "Online" THEN 1
                        ELSE 2
                    END
                ')
                    ->inRandomOrder();
            } else {
                $astrologerQuery->orderByRaw('
                    CASE
                        WHEN abp.astrologer_id IS NOT NULL THEN 1
                        ELSE 2
                    END
                ')->orderByRaw('
                    CASE
                        WHEN astrologers.chatStatus = "Online" OR astrologers.callStatus = "Online" THEN 1
                        ELSE 2
                    END
                ')->orderBy('rating', 'DESC');
            }

            // if ($req->startIndex >= 0 && $req->fetchRecord) {
            //     $astrologerQuery->skip($req->startIndex);
            //     $astrologerQuery->take($req->fetchRecord);
            // }

            $astrologer = $astrologerQuery->paginate($req->fetchRecord);

            $isFreeAvailable = true;
            $isFreeChat = DB::table('systemflag')->where('name', 'FirstFreeChat')->select('value')->first();
            if ($isFreeChat->value == 1) {
                if ($req->userId) {
                    $isChatRequest = DB::table('chatrequest')->where('userId', $req->userId)->where('chatStatus', '=', 'Completed')->first();
                    $isCallRequest = DB::table('callrequest')->where('userId', $req->userId)->where('callStatus', '=', 'Completed')->first();
                    if ($isChatRequest || $isCallRequest) {
                        $isFreeAvailable = false;
                    } else {
                        $isFreeAvailable = true;
                    }
                }
            } else {
                $isFreeAvailable = false;
            }

            // Set isFreeAvailable property for each astrologer
            foreach ($astrologer as $astro) {
                $astro->isFreeAvailable = $isFreeAvailable;
                // $course=DB::table('course_orders')->where('astrologerId', '=', $astro->id)->join('courses','courses.id','course_orders.course_id')->where('course_completion_status','completed')->select('courses.course_badge')->get();
                // $astro['courseBadges'] = $course;

                $astro->profileImage = $astro->profileImage ? asset($astro->profileImage) : null;
                $astro->aadhar_card = $astro->aadhar_card ? asset($astro->aadhar_card) : null;
                $astro->pan_card = $astro->pan_card ? asset($astro->pan_card) : null;
                $astro->certificate = $astro->certificate ? asset($astro->certificate) : null;

                $astro->chat_discounted_rate = $astro->isDiscountedPrice
                    ? ($astro->charge - ($astro->charge * $astro->chat_discount / 100))
                    : 0;

                $astro->audio_discounted_rate = $astro->isDiscountedPrice
                    ? ($astro->charge - ($astro->charge * $astro->audio_discount / 100))
                    : 0;

                $astro->video_discounted_rate = $astro->isDiscountedPrice
                    ? ($astro->videoCallRate - ($astro->videoCallRate * $astro->video_discount / 100))
                    : 0;
            }
            $astro = [];
            if ($req->astrologerCategoryId) {
                $category = $req->astrologerCategoryId;
                for ($i = 0; $i < count($astrologer); $i++) {
                    $categoryAstrologer =
                        array_filter(json_decode(json_encode(
                            array_map('intval', explode(',', $astrologer[$i]->astrologerCategoryId))
                        )),
                            function ($event) use ($category) {
                                return $event === $category;
                            });
                    if ($categoryAstrologer && count($categoryAstrologer) > 0) {
                        array_push($astro, $astrologer[$i]);
                    }
                }
                $astrologer = $astro;
            }
            $isFilter = false;
            if ($req->filterData) {
                if (array_key_exists('skills', $req->filterData) &&
                        $req->filterData['skills'] &&
                        count($req->filterData['skills']) > 0) {
                    $isFilter = true;
                    $skillAstrologer = [];
                    for ($i = 0; $i < count($astrologer); $i++) {
                        $allSkill = array_map('intval', explode(',', $astrologer[$i]->allSkill));
                        foreach ($req->filterData['skills'] as $skill) {
                            $all =
                                array_filter(json_decode(json_encode(array_map('intval',
                                        explode(',', $astrologer[$i]->allSkill)))), function ($event) use ($skill) {
                                    return $event === $skill;
                                });
                            if ($all && count($all) > 0) {
                                $ast = $astrologer[$i];
                                $allastro = array_filter($skillAstrologer, function ($event) use ($ast) {
                                    return $event->id === $ast->id;
                                });
                                if (!($allastro && count($allastro) > 0)) {
                                    array_push($skillAstrologer, $astrologer[$i]);
                                }
                            }
                        }
                    }
                    $astrologer = $skillAstrologer;
                }
                if (array_key_exists('languageKnown', $req->filterData) &&
                        $req->filterData['languageKnown'] &&
                        count($req->filterData['languageKnown']) > 0) {
                    $isFilter = true;
                    $languageAstrologer = [];
                    for ($i = 0; $i < count($astrologer); $i++) {
                        $languages = array_map('intval', explode(',', $astrologer[$i]->languageKnown));
                        foreach ($req->filterData['languageKnown'] as $language) {
                            $all =
                                array_filter(
                                    json_decode(json_encode(array_map('intval',
                                        explode(',', $astrologer[$i]->languageKnown)))),
                                    function ($event) use ($language) {
                                        return $event === $language;
                                    }
                                );
                            if ($all && count($all) > 0) {
                                $ast = $astrologer[$i];
                                $allastro = array_filter($languageAstrologer, function ($event) use ($ast) {
                                    return $event->id === $ast->id;
                                });
                                if (!($allastro && count($allastro) > 0)) {
                                    array_push($languageAstrologer, $astrologer[$i]);
                                }
                            }
                        }
                    }
                    $astrologer = $languageAstrologer;
                }
                if (array_key_exists('gender', $req->filterData) &&
                        $req->filterData['gender'] &&
                        count($req->filterData['gender']) > 0) {
                    $isFilter = true;
                    $genderAstrologer = [];
                    for ($j = 0; $j < count($req->filterData['gender']); $j++) {
                        for ($i = 0; $i < count($astrologer); $i++) {
                            if ($astrologer[$i]->gender == $req->filterData['gender'][$j]) {
                                array_push($genderAstrologer, $astrologer[$i]);
                            }
                        }
                    }
                    $astrologer = $genderAstrologer;
                }
                if (array_key_exists('country', $req->filterData) &&
                        $req->filterData['country'] &&
                        count($req->filterData['country']) > 0) {
                    $isFilter = true;
                    $countryAstrologer = [];
                    for ($i = 0; $i < count($astrologer); $i++) {
                        if ($req->filterData['country'][0] == 'India') {
                            if ($astrologer[$i]->country == 'India') {
                                array_push($countryAstrologer, $astrologer[$i]);
                            }
                        } else {
                            if ($astrologer[$i]->country != 'India') {
                                array_push($countryAstrologer, $astrologer[$i]);
                            }
                        }
                    }
                    $astrologer = $countryAstrologer;
                }
            }

            if ($req->sortBy == 'experienceHighToLow') {
                $astrologers = collect($astrologer)->sortBy('experienceInYears')->reverse()->toArray();
                $astrologer = [];
                foreach ($astrologers as $astro) {
                    array_push($astrologer, $astro);
                }
            }
            if ($req->sortBy == 'experienceLowToHigh') {
                $astrologers = collect($astrologer)->sortBy('experienceInYears')->toArray();
                $astrologer = [];
                foreach ($astrologers as $astro) {
                    array_push($astrologer, $astro);
                }
            }
            if ($req->sortBy == 'ordersHighToLow') {
                $astrologers = collect($astrologer)->sortBy('totalOrder')->reverse()->toArray();
                $astrologer = [];
                foreach ($astrologers as $astro) {
                    array_push($astrologer, $astro);
                }
            }
            if ($req->sortBy == 'ordersLowToHigh') {
                $astrologers = collect($astrologer)->sortBy('totalOrder')->toArray();
                $astrologer = [];
                foreach ($astrologers as $astro) {
                    array_push($astrologer, $astro);
                }
            }
            if ($req->sortBy == 'priceHighToLow') {
                $astrologers = collect($astrologer)->sortBy('charge')->reverse()->toArray();
                $astrologer = [];
                foreach ($astrologers as $astro) {
                    array_push($astrologer, $astro);
                }
            }
            if ($req->sortBy == 'priceLowToHigh') {
                $astrologers = collect($astrologer)->sortBy('charge')->toArray();
                $astrologer = [];
                foreach ($astrologers as $astro) {
                    array_push($astrologer, $astro);
                }
            }
            if ($req->sortBy == 'reportPriceLowToHigh') {
                $astrologers = collect($astrologer)->sortBy('reportRate')->toArray();
                $astrologer = [];
                foreach ($astrologers as $astro) {
                    array_push($astrologer, $astro);
                }
            }
            if ($req->sortBy == 'reportPriceHighToLow') {
                $astrologers = collect($astrologer)->sortBy('reportRate')->reverse()->toArray();
                $astrologer = [];
                foreach ($astrologers as $astro) {
                    array_push($astrologer, $astro);
                }
            }
            $astrologerCount = DB::table('astrologers')->where('isVerified', 1)->count();
            if ($req->astrologerCategoryId) {
                $astrologerCount = count($astrologer);
            }

            // if ($req->startIndex >= 0 && $req->fetchRecord) {
            //     if ((!Req::exists('sortBy') || $req->sortBy == null)
            //         && !$isFilter && !$req->astrologerCategoryId) {
            //         $astrologer = array_slice(json_decode($astrologer), $req->startIndex, $req->fetchRecord);
            //     } else {
            //         $astrologer = array_slice($astrologer, $req->startIndex, $req->fetchRecord);
            //     }
            // }
            $astr = [];

            if ($astrologer && count($astrologer) > 0) {
                if (!Req::exists('sortBy') || $req->sortBy == null) {
                    foreach ($astrologer as $astro) {
                        $review = DB::table('user_reviews')
                            ->where('astrologerId', '=', $astro->id)
                            ->get();

                        $astro->rating = 0;
                        if ($review && count($review) > 0) {
                            $avgRating = 0;
                            foreach ($review as $re) {
                                $avgRating += $re->rating;
                            }
                            $avgRating = $avgRating / count($review);
                            $astro->rating = $avgRating;
                        }

                        $astrologerCategory = array_map('intval', explode(',', $astro->astrologerCategoryId));
                        $allSkill = array_map('intval', explode(',', $astro->allSkill));
                        $primarySkill = array_map('intval', explode(',', $astro->primarySkill));
                        $languages = array_map('intval', explode(',', $astro->languageKnown));
                        $astro->reviews = $review ? count($review) : 0;

                        $allSkill = DB::table('skills')
                            ->whereIn('id', $allSkill)
                            ->select('name')
                            ->get();
                        $skill = $allSkill->pluck('name')->all();
                        $primarySkill = DB::table('skills')
                            ->whereIn('id', $primarySkill)
                            ->select('name')
                            ->get();
                        $primary = $primarySkill->pluck('name')->all();
                        $astrologerCategory = DB::table('astrologer_categories')
                            ->whereIn('id', $astrologerCategory)
                            ->select('name')
                            ->get();
                        $astrologerCategories = $astrologerCategory->pluck('name')->all();
                        $languageKnown = DB::table('languages')
                            ->whereIn('id', $languages)
                            ->select('languageName')
                            ->get();
                        $languageKnowns = $languageKnown->pluck('languageName')->all();

                        $astro->languageKnown = implode(',', $languageKnowns);
                        $astro->astrologerCategory = implode(',', $astrologerCategories);
                        $astro->allSkill = implode(',', $skill);
                        $astro->primarySkill = implode(',', $primary);
                        array_push($astr, $astro);
                    }
                } else {
                    foreach ($astrologer as $astro) {
                        $review = DB::table('user_reviews')
                            ->where('astrologerId', '=', $astro['id'])
                            ->get();
                        $astro['rating'] = 0;
                        if ($review && count($review) > 0) {
                            $avgRating = 0;
                            foreach ($review as $re) {
                                $avgRating += $re->rating;
                            }
                            $avgRating = $avgRating / count($review);
                            $astro['rating'] = $avgRating;
                        }

                        $astrologerCategory = array_map('intval', explode(',', $astro['astrologerCategoryId']));
                        $allSkill = array_map('intval', explode(',', $astro['allSkill']));
                        $primarySkill = array_map('intval', explode(',', $astro['primarySkill']));
                        $languages = array_map('intval', explode(',', $astro['languageKnown']));
                        $astro['reviews'] = $review ? count($review) : 0;

                        $allSkill = DB::table('skills')
                            ->whereIn('id', $allSkill)
                            ->select('name')
                            ->get();
                        $skill = $allSkill->pluck('name')->all();
                        $primarySkill = DB::table('skills')
                            ->whereIn('id', $primarySkill)
                            ->select('name')
                            ->get();
                        $primary = $primarySkill->pluck('name')->all();
                        $astrologerCategory = DB::table('astrologer_categories')
                            ->whereIn('id', $astrologerCategory)
                            ->select('name')
                            ->get();
                        $astrologerCategories = $astrologerCategory->pluck('name')->all();
                        $languageKnown = DB::table('languages')
                            ->whereIn('id', $languages)
                            ->select('languageName')
                            ->get();
                        $languageKnowns = $languageKnown->pluck('languageName')->all();

                        $astro['languageKnown'] = implode(',', $languageKnowns);
                        $astro['astrologerCategory'] = implode(',', $astrologerCategories);
                        $astro['allSkill'] = implode(',', $skill);
                        $astro['primarySkill'] = implode(',', $primary);

                        array_push($astr, $astro);
                    }
                }
            }
            if (Req::exists('sortBy') || $req->sortBy != null) {
                $astrologer = $astr;
            }
            if ($req->sortBy == 'rating') {
                $astrologer = collect($astrologer)->sortBy('rating')->reverse()->toArray();
            }
            error_log($isFreeAvailable);
            foreach ($astrologer as $astro) {
                if ($req->sortBy) {
                    $astro['isFreeAvailable'] = $isFreeAvailable;
                } else {
                    $astro->isFreeAvailable = $isFreeAvailable;
                }
            }
            return response()->json([
                'recordList' => $astrologer,
                'status' => 200,
                'totalCount' => $astrologerCount,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    // Update astrologer
    public function updateAstrologer(Request $req)
    {
        try {
            $data = $req->only(
                'id',
                'userId',
                'name',
                'email',
                'contactNo',
                'gender',
                'birthDate',
                'primarySkill',
                'allSkill',
                'languageKnown',
                'profileImage',
                'charge',
                'experienceInYears',
                'dailyContribution',
                'isWorkingOnAnotherPlatform',
                'whyOnBoard',
                'interviewSuitableTime',
                'mainSourceOfBusiness',
                'highestQualification',
                'degree',
                'college',
                'learnAstrology',
                'astrologerCategoryId',
                'instaProfileLink',
                'facebookProfileLink',
                'linkedInProfileLink',
                'youtubeChannelLink',
                'websiteProfileLink',
                'isAnyBodyRefer',
                'minimumEarning',
                'maximumEarning',
                'loginBio',
                'NoofforeignCountriesTravel',
                'currentlyworkingfulltimejob',
                'goodQuality',
                'biggestChallenge',
                'whatwillDo',
                'isVerified',
                'whatsappNo',
                'pancardNo',
                'aadharNo',
                'ifscCode',
                'bankBranch',
                'bankName',
                'accountType',
                'accountNumber',
            );

            $user = User::find($req->userId);
            $validator = Validator::make($data, [
                'id' => 'required',
                'userId' => 'required',
                'astrologerCategoryId' => 'required',
                'name' => 'required|string',
                'contactNo' => 'required|unique:users,contactNo,' . $user->id,
                'email' => 'required|email|unique:users,email,' . $user->id,
                'gender' => 'required',
                'birthDate' => 'required',
                'dailyContribution' => 'required',
                'languageKnown' => 'required',
                'primarySkill' => 'required',
                'allSkill' => 'required',
                'interviewSuitableTime' => 'required',
                'mainSourceOfBusiness' => 'required',
                'minimumEarning' => 'required',
                'maximumEarning' => 'required',
                'charge' => 'required',
                'whyOnBoard' => 'required',
                'highestQualification' => 'required',
                'whatsappNo' => 'required',
                'aadharNo' => 'required',
                'pancardNo' => 'required',
                'ifscCode' => 'required',
                'bankBranch' => 'required',
                'bankName' => 'required',
                'accountNumber' => 'required',
            ]);

            if ($validator->fails()) {
                DB::rollback();
                return response()->json([
                    'error' => $validator->messages(),
                    'status' => 400,
                ], 400);
            }

            // Handle astrologer profile image (local or external)

            $time = now()->timestamp;

            $path = $user->profileImage ?? null;  // default to existing image

            if ($req->hasFile('profileImage')) {
                $file = $req->file('profileImage');
                $imageName = 'astrologer_' . $user->id . '_' . $time . '.' . $file->getClientOriginalExtension();
                try {
                    $path = StorageHelper::uploadToActiveStorage(file_get_contents($file->getRealPath()), $imageName, 'profile');
                } catch (Exception $ex) {
                    return response()->json(['error' => $ex->getMessage(), 'status' => 500], 500);
                }
            } else if ($req->profileImage) {
                if (Str::contains($req->profileImage, 'storage')) {
                    $path = $req->profileImage;
                } else {
                    $imageName = 'astrologer_' . $user->id . '_' . $time . '.png';
                    try {
                        $path = StorageHelper::uploadToActiveStorage(base64_decode($req->profileImage), $imageName, 'profile');
                        File::delete($user->profileImage);
                    } catch (Exception $ex) {
                        return response()->json(['error' => $ex->getMessage(), 'status' => 500], 500);
                    }
                }
            }

            // For Web file upload

            if ($user) {
                $user->name = $req->name;
                $user->contactNo = $req->contactNo;
                $user->email = $req->email;
                $user->birthDate = $req->birthDate;
                $user->profile = $path;
                $user->gender = $req->gender;
                $user->location = $req->currentCity;
                $user->countryCode = $req->countryCode;
                $user->update();
            }

            $slug = Str::slug($req->name, '-');
            $originalSlug = $slug;
            $counter = 1;
            while (DB::table('astrologers')->where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $astrologer = Astrologer::find($req->id);
            if ($astrologer) {
                $astrologer->name = $req->name;
                $astrologer->slug = $slug;
                $astrologer->userId = $req->userId;
                $astrologer->email = $req->email;
                $astrologer->contactNo = $req->contactNo;
                $astrologer->gender = $req->gender;
                $astrologer->birthDate = $req->birthDate;
                $astrologer->primarySkill = implode(',', array_column($req->primarySkill, 'id'));
                $astrologer->allSkill = implode(',', array_column($req->allSkill, 'id'));
                $astrologer->languageKnown = implode(',', array_column($req->languageKnown, 'id'));
                $astrologer->profileImage = $path;
                $astrologer->charge = $req->charge;
                $astrologer->experienceInYears = $req->experienceInYears;
                $astrologer->dailyContribution = $req->dailyContribution;
                $astrologer->hearAboutAstroguru = $req->hearAboutAstroguru;
                $astrologer->isWorkingOnAnotherPlatform = $req->isWorkingOnAnotherPlatform;
                $astrologer->whyOnBoard = $req->whyOnBoard;
                $astrologer->interviewSuitableTime = $req->interviewSuitableTime;
                $astrologer->currentCity = $req->currentCity;
                $astrologer->mainSourceOfBusiness = $req->mainSourceOfBusiness;
                $astrologer->highestQualification = $req->highestQualification;
                $astrologer->degree = $req->degree;
                $astrologer->college = $req->college;
                $astrologer->learnAstrology = $req->learnAstrology;
                $astrologer->astrologerCategoryId = implode(',', array_column($req->astrologerCategoryId, 'id'));
                $astrologer->instaProfileLink = $req->instaProfileLink;
                $astrologer->linkedInProfileLink = $req->linkedInProfileLink;
                $astrologer->facebookProfileLink = $req->facebookProfileLink;
                $astrologer->websiteProfileLink = $req->websiteProfileLink;
                $astrologer->youtubeChannelLink = $req->youtubeChannelLink;
                $astrologer->isAnyBodyRefer = $req->isAnyBodyRefer;
                $astrologer->minimumEarning = $req->minimumEarning;
                $astrologer->maximumEarning = $req->maximumEarning;
                $astrologer->loginBio = $req->loginBio;
                $astrologer->NoofforeignCountriesTravel = $req->NoofforeignCountriesTravel;
                $astrologer->currentlyworkingfulltimejob = $req->currentlyworkingfulltimejob;
                $astrologer->goodQuality = $req->goodQuality;
                $astrologer->biggestChallenge = $req->biggestChallenge;
                $astrologer->whatwillDo = $req->whatwillDo;
                $astrologer->videoCallRate = $req->videoCallRate;
                $astrologer->reportRate = $req->reportRate ? $req->reportRate : 0;
                $astrologer->nameofplateform = $req->nameofplateform;
                $astrologer->monthlyEarning = $req->monthlyEarning;
                $astrologer->referedPerson = $req->referedPerson;
                $astrologer->charge_usd = $req->charge_usd;
                $astrologer->videoCallRate_usd = $req->videoCallRate_usd;
                $astrologer->reportRate_usd = $req->reportRate_usd;

                $astrologer->whatsappNo = $req->whatsappNo;
                $astrologer->aadharNo = $req->aadharNo;
                $astrologer->pancardNo = $req->pancardNo;
                $astrologer->ifscCode = $req->ifscCode;
                $astrologer->accountType = $req->accountType;
                $astrologer->bankBranch = $req->bankBranch;
                $astrologer->bankName = $req->bankName;
                $astrologer->accountNumber = $req->accountNumber;
                $astrologer->accountHolderName = $req->accountHolderName;
                $astrologer->upi = $req->upi;

                $documents = AstrologerDocument::query()->get();
                $documentMap = $req->documentMap ?? [];

                foreach ($documents as $document) {
                    $columnName = Str::snake($document->name);

                    if (array_key_exists($columnName, $documentMap)) {
                        if (!Schema::hasColumn('astrologers', $columnName)) {
                            Schema::table('astrologers', function (Blueprint $table) use ($columnName) {
                                $table->string($columnName)->nullable();
                            });
                        }

                        $docImage = $documentMap[$columnName] ?? null;
                        if ($docImage) {
                            $time = now()->timestamp;
                            $imageName = $columnName . '_' . $req->id . '_' . $time . '.png';

                            try {
                                $docPath = \App\Helpers\StorageHelper::uploadToActiveStorage(
                                    base64_decode($docImage),
                                    $imageName,
                                    'documents'
                                );
                                $astrologer->$columnName = $docPath;
                            } catch (Exception $ex) {
                                return response()->json(['error' => $ex->getMessage()], 400);
                            }
                        }
                    }
                }

                $astrologer->update();
                if ($req->userDeviceDetails) {
                    $userDeviceDetails = UserDeviceDetail::find($req->userId);
                    if ($userDeviceDetails) {
                        $userDeviceDetails->userId = $user->id;
                        $userDeviceDetails->appId = $req->appId;
                        $userDeviceDetails->deviceId = $req->deviceId;
                        $userDeviceDetails->fcmToken = $req->fcmToken;
                        $userDeviceDetails->deviceLocation = $req->deviceLocation;
                        $userDeviceDetails->deviceManufacturer = $req->deviceManufacturer;
                        $userDeviceDetails->deviceModel = $req->deviceModel;
                        $userDeviceDetails->appVersion = $req->appVersion;
                        $userDeviceDetails->subscription_id = $req->subscription_id;
                        $userDeviceDetails->update();
                    } else {
                        $userDeviceDetails = UserDeviceDetail::create([
                            'userId' => $req->userId,
                            'appId' => $req->appId,
                            'deviceId' => $req->deviceId,
                            'fcmToken' => $req->fcmToken,
                            'deviceLocation' => $req->deviceLocation,
                            'deviceManufacturer' => $req->deviceManufacturer,
                            'deviceModel' => $req->deviceModel,
                            'appVersion' => $req->appVersion,
                            'subscription_id' => $req->subscription_id,
                        ]);
                    }
                }
                if ($req->astrologerAvailability) {
                    $availability = DB::Table('astrologer_availabilities')
                        ->where('astrologerId', '=', $req->id)
                        ->delete();
                    $req->astrologerId = $astrologer->id;
                    foreach ($req->astrologerAvailability as $astrologeravailable) {
                        foreach ($astrologeravailable['time'] as $availability) {
                            AstrologerAvailability::create([
                                'astrologerId' => $req->id,
                                'day' => $astrologeravailable['day'],
                                'fromTime' => $availability['fromTime'],
                                'toTime' => $availability['toTime'],
                                'createdBy' => $astrologer['id'],
                                'modifiedBy' => $astrologer['id'],
                            ]);
                        }
                    }
                }
                $astrologer->astrologerAvailability = $req->astrologerAvailability;

                $astrologer->allSkill = array_map('intval', explode(',', $astrologer->allSkill));
                $astrologer->primarySkill = array_map('intval', explode(',', $astrologer->primarySkill));
                $astrologer->languageKnown = array_map('intval', explode(',', $astrologer->languageKnown));
                $astrologer->astrologerCategoryId =
                    array_map('intval', explode(',', $astrologer->astrologerCategoryId));
                $allSkill = DB::table('skills')
                    ->whereIn('id', $astrologer->allSkill)
                    ->select('name', 'id')
                    ->get();
                $primarySkill = DB::table('skills')
                    ->whereIn('id', $astrologer->primarySkill)
                    ->select('name', 'id')
                    ->get();
                $languageKnown = DB::table('languages')
                    ->whereIn('id', $astrologer->languageKnown)
                    ->select('languageName', 'id')
                    ->get();
                $catgory = DB::table('astrologer_categories')
                    ->whereIn('id', $astrologer->astrologerCategoryId)
                    ->select('name', 'id')
                    ->get();
                $astrologer->allSkill = $allSkill;
                $astrologer->primarySkill = $primarySkill;
                $astrologer->languageKnown = $languageKnown;
                $astrologer->astrologerCategoryId = $catgory;

                $documents = AstrologerDocument::query()->get();
                $documentMap = [];

                foreach ($documents as $document) {
                    $columnName = Str::snake($document->name);
                    if (Schema::hasColumn('astrologers', $columnName)) {
                        $documentMap[$columnName] = $astrologer->$columnName ?? null;
                    }
                }

                $astrologer->documentMap = $documentMap;

                return response()->json([
                    'message' => 'Astrologer update sucessfully',
                    'recordList' => $astrologer,
                    'status' => 200,
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Astrologer is not found',
                    'status' => 404,
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    // Update Astrologer consultation rates & pricing
    public function updateRates(Request $req)
    {
        try {
            $astroId = $req->input('astrologerId') ?? $req->input('id') ?? $req->astrologerId ?? $req->id;
            if (!$astroId && ($req->input('userId') ?? $req->userId)) {
                $astroId = DB::table('astrologers')->where('userId', $req->input('userId') ?? $req->userId)->value('id');
            }

            if (!$astroId) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Astrologer ID is required',
                ], 400);
            }

            $update = [];
            if ($req->filled('charge') || $req->has('charge')) {
                $update['charge'] = (float)($req->input('charge') ?? $req->charge);
            }
            if ($req->filled('audioCallRate') || $req->has('audioCallRate')) {
                $update['charge'] = (float)($req->input('audioCallRate') ?? $req->audioCallRate);
            }
            if ($req->filled('videoCallRate') || $req->has('videoCallRate')) {
                $update['videoCallRate'] = (float)($req->input('videoCallRate') ?? $req->videoCallRate);
            }
            if ($req->filled('reportRate') || $req->has('reportRate')) {
                $update['reportRate'] = (float)($req->input('reportRate') ?? $req->reportRate);
            }
            if ($req->filled('emergency_chat_charge') || $req->has('emergency_chat_charge')) {
                $update['emergency_chat_charge'] = (float)($req->input('emergency_chat_charge') ?? $req->emergency_chat_charge);
            }
            if ($req->filled('emergency_audio_charge') || $req->has('emergency_audio_charge')) {
                $update['emergency_audio_charge'] = (float)($req->input('emergency_audio_charge') ?? $req->emergency_audio_charge);
            }
            if ($req->filled('emergency_video_charge') || $req->has('emergency_video_charge')) {
                $update['emergency_video_charge'] = (float)($req->input('emergency_video_charge') ?? $req->emergency_video_charge);
            }
            if ($req->has('emergencyChatStatus')) {
                $update['emergencyChatStatus'] = ($req->input('emergencyChatStatus') ?? $req->emergencyChatStatus) ? 1 : 0;
            }
            if ($req->has('emergencyCallStatus')) {
                $update['emergencyCallStatus'] = ($req->input('emergencyCallStatus') ?? $req->emergencyCallStatus) ? 1 : 0;
            }
            if ($req->has('emergency_video_charge')) {
                $update['emergency_video_charge'] = (float)$req->emergency_video_charge;
            }
            if ($req->has('emergencyChatStatus')) {
                $update['emergencyChatStatus'] = $req->emergencyChatStatus ? 1 : 0;
            }
            if ($req->has('emergencyCallStatus')) {
                $update['emergencyCallStatus'] = $req->emergencyCallStatus ? 1 : 0;
            }
            if ($req->has('chat_discount')) {
                $update['chat_discount'] = (float)$req->chat_discount;
            }
            if ($req->has('video_discount')) {
                $update['video_discount'] = (float)$req->video_discount;
            }
            if ($req->has('isDiscountedPrice')) {
                $update['isDiscountedPrice'] = $req->isDiscountedPrice ? 1 : 0;
            }

            if (!empty($update)) {
                $update['updated_at'] = Carbon::now();
                DB::table('astrologers')->where('id', $astroId)->update($update);
            }

            $astro = DB::table('astrologers')->where('id', $astroId)->first();

            return response()->json([
                'status' => 200,
                'message' => 'Tariff rates updated successfully',
                'recordList' => $astro,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // Lightweight update status (chatStatus, callStatus)
    public function updateStatus(Request $req)
    {
        try {
            $astroId = $req->input('astrologerId') ?? $req->input('id') ?? $req->astrologerId ?? $req->id;
            if (!$astroId && ($req->input('userId') ?? $req->userId)) {
                $astroId = DB::table('astrologers')->where('userId', $req->input('userId') ?? $req->userId)->value('id');
            }
            if (!$astroId) {
                return response()->json(['status' => 400, 'message' => 'Astrologer ID required'], 400);
            }

            $update = [];
            if ($req->filled('chatStatus') || $req->has('chatStatus')) {
                $update['chatStatus'] = $req->input('chatStatus') ?? $req->chatStatus;
            }
            if ($req->filled('callStatus') || $req->has('callStatus')) {
                $update['callStatus'] = $req->input('callStatus') ?? $req->callStatus;
            }

            if (!empty($update)) {
                $update['updated_at'] = Carbon::now();
                DB::table('astrologers')->where('id', $astroId)->update($update);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Availability status updated successfully',
            ], 200);
        } catch (\Throwable $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    // Update Astrologer Bank & UPI payout KYC details
    public function updateBankDetails(Request $req)
    {
        try {
            $astroId = $req->input('astrologerId') ?? $req->input('id') ?? $req->astrologerId ?? $req->id;
            if (!$astroId && ($req->input('userId') ?? $req->userId)) {
                $astroId = DB::table('astrologers')->where('userId', $req->input('userId') ?? $req->userId)->value('id');
            }
            if (!$astroId) {
                return response()->json(['status' => 400, 'message' => 'Astrologer ID required'], 400);
            }

            $update = [];
            if ($req->has('bankName')) $update['bankName'] = $req->input('bankName') ?? $req->bankName;
            if ($req->has('accountNumber')) $update['accountNumber'] = $req->input('accountNumber') ?? $req->accountNumber;
            if ($req->has('accountHolderName')) $update['accountHolderName'] = $req->input('accountHolderName') ?? $req->accountHolderName;
            if ($req->has('ifscCode')) $update['ifscCode'] = strtoupper($req->input('ifscCode') ?? $req->ifscCode);
            if ($req->has('bankBranch')) $update['bankBranch'] = $req->input('bankBranch') ?? $req->bankBranch;
            if ($req->has('accountType')) $update['accountType'] = $req->input('accountType') ?? $req->accountType;
            if ($req->has('upi')) $update['upi'] = $req->input('upi') ?? $req->upi;
            if ($req->has('pancardNo')) $update['pancardNo'] = strtoupper($req->input('pancardNo') ?? $req->pancardNo);
            if ($req->has('aadharNo')) $update['aadharNo'] = $req->input('aadharNo') ?? $req->aadharNo;

            if (!empty($update)) {
                $update['updated_at'] = Carbon::now();
                DB::table('astrologers')->where('id', $astroId)->update($update);
            }

            $astro = DB::table('astrologers')->where('id', $astroId)->first();

            return response()->json([
                'status' => 200,
                'message' => 'Bank & UPI payout details updated successfully',
                'recordList' => $astro,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    // Delete astrologer
    public function deleteAstrologer(Request $req)
    {
        try {
            $astrologer = Astrologer::find($req->id);
            if ($astrologer) {
                // $data = array(
                //     'isDelete' => true,
                //     'updated_at' => Carbon::now(),
                // );

                if ($astrologer->userId > 0) {
                    DB::table('users')->where('id', $astrologer->userId)->delete();
                }

                $astrologer->delete();
                return response()->json([
                    'message' => 'Astrologer delete Sucessfully',
                    'status' => 200,
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Astrologer is not found',
                    'status' => 404,
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    // Verify astrologer
    public function verifyAstrologer(Request $req, $id)
    {
        try {
            $astrologer = Astrologer::find($id);
            if ($astrologer) {
                $astrologer->isVerified = !$astrologer->isVerified;
                $astrologer->update();
                return response()->json([
                    'message' => 'Astrologer is verify',
                    'recordList' => $astrologer,
                    'status' => 200,
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Astrologer is not verify',
                    'status' => 404,
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    // Get the master data of astrologer
    public function masterAstrologer()
    {
        try {
            $skill = Skill::query();
            $skill->where('isActive', '=', true);
            $skill->where('isDelete', '=', false);
            $language = Language::query();
            $mainSourceBusiness = MainSourceOfBusiness::query();
            $highestQualification = HighestQualification::query();
            $qualifications = DegreeOrDiploma::query();
            $jobs = FulltimeJob::query();
            $countryTravel = TravelCountry::query();
            $astrologerCategory = AstrologerCategory::query();
            $astrologerCategory->where('isActive', '=', true);
            $astrologerCategory->where('isDelete', '=', false);
            return response()->json([
                'skill' => $skill->get(),
                'language' => $language->get(),
                'mainSourceBusiness' => $mainSourceBusiness->get(),
                'highestQualification' => $highestQualification->get(),
                'qualifications' => $qualifications->get(),
                'jobs' => $jobs->get(),
                'countryTravel' => $countryTravel->get(),
                'astrolgoerCategory' => $astrologerCategory->get(),
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

    public function getCounsellor(Request $req)
    {
        try {
            $skillId = DB::table('skills')
                ->where('name', '=', 'Psychologist')
                ->get();
            $id = $skillId[0]->id;
            $counsellor = DB::table('astrologers')
                ->whereRaw("find_in_set($id,allSkill)")
                ->orwhere('primarySkill', '=', $skillId[0]->id)
                ->where('isActive', '=', true)
                ->where('isDelete', '=', false)
                ->where('isVerified', '=', true);

            if ($req->startIndex >= 0 && $req->fetchRecord) {
                $counsellor->skip($req->startIndex);
                $counsellor->take($req->fetchRecord);
            }
            $isFreeAvailable = true;
            $isFreeChat = DB::table('systemflag')->where('name', 'FirstFreeChat')->select('value')->first();
            if ($isFreeChat->value == 1) {
                if ($req->userId) {
                    $isChatRequest = DB::table('chatrequest')->where('userId', $req->userId)->where('chatStatus', '=', 'Completed')->first();
                    $isCallRequest = DB::table('callrequest')->where('userId', $req->userId)->where('callStatus', '=', 'Completed')->first();
                    if ($isChatRequest || $isCallRequest) {
                        $isFreeAvailable = false;
                    } else {
                        $isFreeAvailable = true;
                    }
                }
            } else {
                $isFreeAvailable = false;
            }
            $counsellor = $counsellor->get();
            if ($counsellor && count($counsellor) > 0) {
                foreach ($counsellor as $coun) {
                    $astrologerCategory = array_map('intval', explode(',', $coun->astrologerCategoryId));
                    $allSkill = array_map('intval', explode(',', $coun->allSkill));
                    $primarySkill = array_map('intval', explode(',', $coun->primarySkill));
                    $languages = array_map('intval', explode(',', $coun->languageKnown));
                    $allSkill = DB::table('skills')
                        ->whereIn('id', $allSkill)
                        ->select('name')
                        ->get();
                    $skill = $allSkill->pluck('name')->all();
                    $primarySkill = DB::table('skills')
                        ->whereIn('id', $primarySkill)
                        ->select('name')
                        ->get();
                    $primary = $primarySkill->pluck('name')->all();
                    $astrologerCategory = DB::table('astrologer_categories')
                        ->whereIn('id', $astrologerCategory)
                        ->select('name')
                        ->get();
                    $astrologerCategories = $astrologerCategory->pluck('name')->all();
                    $languageKnown = DB::table('languages')
                        ->whereIn('id', $languages)
                        ->select('languageName')
                        ->get();
                    $languageKnowns = $languageKnown->pluck('languageName')->all();

                    $coun->languageKnown = implode(',', $languageKnowns);
                    $coun->astrologerCategory = implode(',', $astrologerCategories);
                    $coun->allSkill = implode(',', $skill);
                    $coun->primarySkill = implode(',', $primary);
                    $coun->isFreeAvailable = $isFreeAvailable;
                }
            }
            $counsellorCount = DB::table('astrologers')
                ->whereRaw("find_in_set($id,allSkill)")
                ->orwhere('primarySkill', '=', $skillId[0]->id)
                ->where('isActive', '=', true)
                ->where('isDelete', '=', false)
                ->where('isVerified', '=', true)
                ->count();

            return response()->json([
                'recordList' => $counsellor,
                'status' => 200,
                'totalRecords' => $counsellorCount,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function checkContactNoExist(Request $req)
    {
        try {
            $data = $req->only(
                'contactNo'
            );
            $validator = Validator::make($data, [
                'contactNo' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'error' => $validator->messages(),
                    'status' => 400,
                ], 400);
            }
            $id = DB::table('astrologers')
                ->where('contactNo', '=', $req->contactNo)
                ->select('astrologers.id', 'astrologers.isVerified')
                ->get();

            $userdata = DB::table('users')
                ->join('user_roles', 'users.id', '=', 'user_roles.userId')
                ->where('contactNo', '=', $req->contactNo)
                ->where('user_roles.roleId', '=', $req->roleId = 3)
                ->where('users.isDelete', '=', false)
                ->select('users.*')
                ->get();

            if ($userdata && count($userdata) > 0) {
                return response()->json([
                    'message' => 'This Mobile number is already register in user App',
                    'status' => 400,
                ], 400);
            }

            if ($id && count($id) > 0) {
                if (!$id[0]->isVerified) {
                    return response()->json([
                        'message' => 'Your Account is not verified from admin',
                        'status' => 400,
                    ], 400);
                } else {
                    return response()->json([
                        'status' => 400,
                        'message' => 'Contact Number is Already Register',
                    ], 400);
                }
            } else {
                return response()->json([
                    'status' => 200,
                    'message' => 'Contact Number is Not Register',
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    // -------------For USer----------------------------------
    public function checkContactNoExistForUser(Request $req)
    {
        try {
            $data = $req->only('contactNo', 'fromApp', 'type', 'email', 'countryCode');

            $validator = Validator::make($data, [
                'fromApp' => 'required|string',
                'type' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => $validator->messages(),
                    'status' => 400,
                ], 400);
            }

            if (empty($req->contactNo) && empty($req->email)) {
                return response()->json([
                    'message' => 'Please enter your Email or Contact Number',
                    'status' => 400,
                ], 400);
            }

            $fromApp = strtolower($req->fromApp ?? 'user');
            $type = strtolower($req->type ?? 'login');

            // 🔹 Check for astrologer registration
            if ($fromApp === 'astrologer' && $type === 'register') {
                if (!empty($req->contactNo)) {
                    $existsUser = DB::table('users')->where('contactNo', $req->contactNo)->exists();
                    $existsAstro = DB::table('astrologers')->where('contactNo', $req->contactNo)->exists();

                    if ($existsUser || $existsAstro) {
                        return response()->json([
                            'message' => 'This Mobile number is already registered',
                            'status' => 400,
                        ], 400);
                    }
                }

                if (!empty($req->email)) {
                    $existsUser = DB::table('users')->where('email', $req->email)->exists();
                    $existsAstro = DB::table('astrologers')->where('email', $req->email)->exists();

                    if ($existsUser || $existsAstro) {
                        return response()->json([
                            'message' => 'This email is already registered',
                            'status' => 400,
                        ], 400);
                    }
                }
            }

            // 🔹 Check for astrologer login
            if ($fromApp === 'astrologer' && $type === 'login') {
                $contactRaw = preg_replace('/[^0-9]/', '', (string)$req->contactNo);
                $contact10 = substr($contactRaw, -10);

                $astro = DB::table('astrologers')
                    ->where('contactNo', $req->contactNo)
                    ->orWhere('contactNo', $contactRaw)
                    ->orWhere('contactNo', $contact10)
                    ->first();

                if (!$astro) {
                    return response()->json([
                        'message' => 'This Mobile number is not registered as an Astrologer',
                        'status' => 400,
                    ], 400);
                }

                if (isset($astro->isVerified) && $astro->isVerified != 1) {
                    return response()->json([
                        'message' => 'Your Account is not verified by admin',
                        'status' => 400,
                    ], 400);
                }
            }

            // 🔹 Generate OTP
            $otp = strval(random_int(100000, 999999));

            if (!empty($req->contactNo)) {
                // ✅ Skip OTP sending for test numbers
                if (in_array($req->contactNo, ['9898989898', '9797979797'])) {
                    $otp = '111111';
                } else {
                    try {
                        $otpResult = app(\App\Services\OtpService::class)->send(
                            $req->contactNo,
                            $otp,
                            $req->countryCode ?? '91'
                        );

                        if (!$otpResult['ok']) {
                            // If all gateways fail, log and fall back to 111111 so login is never blocked
                            \Illuminate\Support\Facades\Log::warning("OTP delivery failed across all channels for {$req->contactNo}: " . ($otpResult['error'] ?? 'unknown'));
                            $otp = '111111';
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error("OtpService exception for {$req->contactNo}: " . $e->getMessage());
                        $otp = '111111';
                    }
                }

                $cleanUserContact = preg_replace('/[^0-9]/', '', (string)$req->contactNo);
                cache()->put("user_otp_{$cleanUserContact}", (string)$otp, now()->addMinutes(10));
            }

            // 🔹 Success responses
            if (!empty($req->email)) {
                return response()->json([
                    'status' => 201,
                    'message' => 'Email address is Not Registered',
                ], 200);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Contact Number is Not Registered',
                'mobile' => $req->contactNo,
                'otp' => !empty($req->fromWeb) ? base64_encode($otp) : $otp,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function getAstrologerById(Request $req)
    {
        // try {
        $data = $req->only('astrologerId');
        $validator = Validator::make($data, [
            'astrologerId' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages(),
                'status' => 400,
            ], 400);
        }

        $astrologer = Astrologer::where('id', '=', $req->astrologerId)->get();
        if (count($astrologer) == 0) {
            return response()->json([
                'message' => 'No Astrologer Found',
                'status' => 400,
            ], 400);
        }

        $course = CourseOrder::where('astrologerId', '=', $req->astrologerId)
            ->join('courses', 'courses.id', 'course_orders.course_id')
            ->where('course_completion_status', 'completed')
            ->select('courses.course_badge')
            ->get();

        $isFreeAvailable = true;
        $isFreeChat = DB::table('systemflag')->where('name', 'FirstFreeChat')->select('value')->first();
        if ($isFreeChat->value == 1) {
            if ($req->userId) {
                $isChatRequest = DB::table('chatrequest')->where('userId', $req->userId)->where('chatStatus', '=', 'Completed')->first();
                $isCallRequest = DB::table('callrequest')->where('userId', $req->userId)->where('callStatus', '=', 'Completed')->first();
                $isFreeAvailable = !($isChatRequest || $isCallRequest);
            }
        } else {
            $isFreeAvailable = false;
        }

        $block = DB::table('blockastrologer')
            ->where('userId', '=', $req->userId)
            ->where('astrologerId', '=', $req->astrologerId)
            ->get();

        foreach ($astrologer as $astro) {
            $astro->isFreeAvailable = $isFreeAvailable;
        }

        if ($astrologer) {
            $astrologer[0]->allSkill = array_map('intval', explode(',', $astrologer[0]->allSkill));
            $astrologer[0]->primarySkill = array_map('intval', explode(',', $astrologer[0]->primarySkill));
            $astrologer[0]->languageKnown = array_map('intval', explode(',', $astrologer[0]->languageKnown));
            $astrologer[0]->astrologerCategoryId = array_map('intval', explode(',', $astrologer[0]->astrologerCategoryId));

            $allSkill = DB::table('skills')->whereIn('id', $astrologer[0]->allSkill)->select('name', 'id')->get();
            $primarySkill = DB::table('skills')->whereIn('id', $astrologer[0]->primarySkill)->select('name', 'id')->get();
            $languageKnown = DB::table('languages')->whereIn('id', $astrologer[0]->languageKnown)->select('languageName', 'id')->get();
            $category = DB::table('astrologer_categories')->whereIn('id', $astrologer[0]->astrologerCategoryId)->select('name', 'id')->get();

            $astrologer[0]->isBlock = ($block && count($block) > 0);
            $astrologer[0]->allSkill = $allSkill;
            $astrologer[0]->primarySkill = $primarySkill;
            $astrologer[0]->languageKnown = $languageKnown;
            $astrologer[0]->courseBadges = $course;
            $astrologer[0]->astrologerCategoryId = $category;

            $astrologerAvailability = DB::table('astrologer_availabilities')->where('astrologerId', '=', $req->astrologerId)->get();
            if ($astrologerAvailability && count($astrologerAvailability) > 0) {
                $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                $working = [];
                foreach ($dayNames as $days) {
                    $result = array_filter(json_decode($astrologerAvailability), function ($event) use ($days) {
                        return $event->day === $days;
                    });
                    $ti = [];
                    foreach ($result as $available) {
                        $ti[] = [
                            'fromTime' => $available->fromTime,
                            'toTime' => $available->toTime,
                        ];
                    }
                    $working[] = [
                        'day' => $days,
                        'time' => $ti,
                    ];
                }
                $astrologer[0]->astrologerAvailability = $working;
            } else {
                $astrologer[0]->astrologerAvailability = [];
            }

            $chatHistory = ChatRequest::join('users', 'users.id', '=', 'chatrequest.userId')
                ->join('astrologers as astr', 'astr.id', '=', 'chatrequest.astrologerId')
                ->select(
                    'chatrequest.*',
                    'users.name',
                    'users.contactNo',
                    'users.profile',
                    'astr.name as astrologerName',
                    'astr.charge',
                    DB::raw("CONVERT_TZ(chatrequest.created_at, '+00:00', '+00:00') as created_at")
                )
                ->where('chatrequest.astrologerId', '=', $req->astrologerId)
                ->where('chatrequest.chatStatus', '=', 'Completed')
                ->orderBy('chatrequest.id', 'DESC')
                ->get();

            $callHistory = CallRequest::join('users as us', 'us.id', '=', 'callrequest.userId')
                ->join('astrologers as ae', 'ae.id', '=', 'callrequest.astrologerId')
                ->select(
                    'callrequest.*',
                    'us.name',
                    'us.contactNo',
                    'us.profile',
                    'ae.name as astrologerName',
                    'ae.charge',
                    // DB::raw("CONVERT_TZ(callrequest.created_at, '+00:00', '+01:30') as created_at")
                )
                ->where('callrequest.astrologerId', '=', $req->astrologerId)
                ->where('callrequest.callStatus', '=', 'Completed')
                ->orderBy('callrequest.id', 'DESC')
                ->get();

            $wallet = WalletTransaction::leftjoin('order_request', 'order_request.id', '=', 'wallettransaction.orderId')
                ->leftjoin('users', 'users.id', '=', 'order_request.userId')
                ->leftJoin('users as user_wallet', 'user_wallet.id', '=', 'wallettransaction.createdBy')
                ->select(
                    'wallettransaction.*',
                    'users.name',
                    'order_request.totalMin',
                    'user_wallet.name as productRefName',
                    DB::raw("CONVERT_TZ(wallettransaction.created_at, '+00:00', '+00:00') as created_at")
                )
                ->where('wallettransaction.userId', '=', $astrologer[0]->userId)
                ->orderBy('wallettransaction.id', 'DESC')
                ->get();

            if ($wallet && count($wallet) > 0) {
                foreach ($wallet as $w) {
                    if ($w->transactionType == 'Gift') {
                        $user = DB::table('users')->where('id', $w->createdBy)->select('name')->first();
                        $w->name = $user ? $user->name : 'Unknown';
                    }
                }
            }

            $review = DB::table('user_reviews as ur')
                ->leftJoin('users as us', 'us.id', '=', 'ur.userId')
                ->where('ur.astrologerId', '=', $req->astrologerId)
                ->select(
                    'ur.*',
                    'us.profile',
                    DB::raw('IFNULL(us.name, ur.user_name) as userName')
                )
                ->orderBy('ur.id', 'DESC')
                ->get();

            $reports = UserReport::join('users as u', 'u.id', '=', 'user_reports.userId')
                ->join('report_types', 'report_types.id', '=', 'user_reports.reportType')
                ->where('astrologerId', '=', $req->astrologerId)
                ->select('user_reports.*', 'u.name', 'u.profile', 'u.contactNo', 'report_types.reportImage', 'report_types.title as reportType')
                ->orderBy('user_reports.id', 'DESC')
                ->get();

            $callMin = DB::table('callrequest')->where('astrologerId', '=', $req->astrologerId)->sum('totalMin');
            $chatMin = DB::table('chatrequest')->where('astrologerId', '=', $req->astrologerId)->sum('totalMin');

            $payment = DB::table('payment')
                ->join('astrologers', 'astrologers.userId', 'payment.userId')
                ->where('astrologers.id', $req->astrologerId)
                ->select('payment.*')
                ->orderBy('payment.id', 'DESC')
                ->get();

            $pujalist = PujaOrder::join('astrologers', 'astrologers.id', 'puja_orders.astrologer_id')
                ->leftjoin('puja_package', 'puja_package.id', 'puja_orders.package_id')
                ->leftjoin('pujas', 'pujas.id', 'puja_orders.puja_id')
                ->where('astrologers.id', $req->astrologerId)
                ->select('puja_orders.*', 'puja_package.description', 'pujas.puja_images', 'puja_package.title')
                ->orderBy('puja_orders.id', 'DESC')
                ->get();

            $pujalist = $pujalist->map(function ($item) {
                $item->puja_images = json_decode($item->puja_images, true);
                $item->package = [
                    'title' => $item->title,
                    'description' => $item->description
                ];
                unset($item->description, $item->title);

                $pujaOrder = PujaOrder::find($item->id);
                $item->pujaLink = $pujaOrder->Pujabroadcast($item->astrologer_id, false);
                return $item;
            });

            // ⭐ Image Path Conversion Section ⭐
            $convertToAsset = function ($value) {
                if (empty($value))
                    return null;
                if (Str::startsWith($value, ['http://', 'https://'])) {
                    return $value;
                }
                return asset($value);
            };

            // Convert astrologer image fields
            $fieldsToConvert = ['profileImage', 'aadhar_card', 'pan_card', 'certificate', 'profile', 'astro_video'];
            foreach ($fieldsToConvert as $field) {
                if (isset($astrologer[0]->$field)) {
                    $astrologer[0]->$field = $convertToAsset($astrologer[0]->$field);
                }
            }

            // Convert nested image arrays in puja_orders
            foreach ($pujalist as $puja) {
                if (!empty($puja->puja_images) && is_array($puja->puja_images)) {
                    $puja->puja_images = array_map($convertToAsset, $puja->puja_images);
                }
            }

            // Convert report-related files
            foreach ($reports as $r) {
                $r->reportFile = $convertToAsset($r->reportFile ?? '');
                $r->reportImage = $convertToAsset($r->reportImage ?? '');
            }

            // Also convert user profile images inside review/chat/call
            foreach ($review as $rv) {
                $rv->profile = $convertToAsset($rv->profile ?? '');
            }
            foreach ($chatHistory as $ch) {
                $ch->profile = $convertToAsset($ch->profile ?? '');
            }
            foreach ($callHistory as $cl) {
                $cl->profile = $convertToAsset($cl->profile ?? '');
            }

            // Rating calculation remains unchanged
            $one = $two = $three = $four = $five = 0;
            foreach ($review as $r) {
                ${['one', 'two', 'three', 'four', 'five'][$r->rating - 1]}++;
            }
            $total = count($review);
            $rating = [
                'oneStarRating' => $total ? $one * 100 / $total : 0,
                'twoStarRating' => $total ? $two * 100 / $total : 0,
                'threeStarRating' => $total ? $three * 100 / $total : 0,
                'fourStarRating' => $total ? $four * 100 / $total : 0,
                'fiveStarRating' => $total ? $five * 100 / $total : 0,
            ];
            $avg = $total ? ($one + 2 * $two + 3 * $three + 4 * $four + 5 * $five) / $total : 0;

            $call_method = systemflag('streaming_provider');
            $astrologer[0]->chatHistory = $chatHistory;
            $astrologer[0]->callHistory = $callHistory;
            $astrologer[0]->wallet = $wallet;
            $astrologer[0]->pujaOrder = $pujalist;
            $astrologer[0]->payment = $payment;
            $astrologer[0]->review = $review;
            $astrologer[0]->report = $reports;
            $astrologer[0]->chatMin = $chatMin;
            $astrologer[0]->callMin = $callMin;
            $astrologer[0]->astrologerRating = $rating;
            $astrologer[0]->rating = $avg;
            $astrologer[0]->ratingcount = $total;
            $astrologer[0]->call_method = $call_method;

            return response()->json([
                'message' => 'get Astrologer Profile',
                'recordList' => $astrologer,
                'status' => 200,
            ], 200);
        }

        // } catch (\Exception $e) {
        //     return response()->json([
        //         'error' => false,
        //         'message' => $e->getMessage(),
        //         'status' => 500,
        //     ], 500);
        // }
    }

    public function searchAstro(Request $req)
    {
        try {
            if ($req->filterKey == 'astromall') {
                $result = DB::table('astromall_products')
                    ->whereRaw(sql: "name LIKE '%" . $req->searchString . "%' ");
                // ->get();
                if ($req->startIndex >= 0 && $req->fetchRecord) {
                    $result = $result->skip($req->startIndex);
                    $result = $result->take($req->fetchRecord);
                }
                $result = $result->get();
            } elseif ($req->filterKey == 'astrologer') {
                $boostCutoff = Carbon::now()->subHours(24);
                $astrologerQuery = Astrologer::query()
                    ->select('astrologers.*', DB::raw('
                    (CASE WHEN abp.astrologer_id IS NOT NULL THEN 1 ELSE 0 END) as is_boosted
                '))
                    ->leftJoin('astrologer_boosted_profiles as abp', function ($join) use ($boostCutoff) {
                        $join
                            ->on('astrologers.id', '=', 'abp.astrologer_id')
                            ->where('abp.boosted_datetime', '>=', $boostCutoff);
                    })
                    ->where([
                        ['astrologers.isActive', true],
                        ['astrologers.isVerified', true],
                        ['astrologers.isDelete', false]
                    ])
                    ->whereNotIn('astrologers.id', function ($query) use ($req) {
                        $query
                            ->select('astrologerId')
                            ->from('blockastrologer')
                            ->where('userId', '=', $req->userId);
                    });

                // Search functionality
                if ($s = $req->input('searchString')) {
                    $astrologerQuery->where('name', 'LIKE', "%{$s}%");
                }

                // Apply sorting by boost status and random order
                $astrologerQuery->orderByRaw('
                CASE WHEN abp.astrologer_id IS NOT NULL THEN 1 ELSE 2 END
            ')->inRandomOrder();

                if ($req->startIndex >= 0 && $req->fetchRecord) {
                    $result = $astrologerQuery->skip($req->startIndex);
                    $result = $astrologerQuery->take($req->fetchRecord);
                }

                $result = $astrologerQuery->get();

                // $result = DB::table('astrologers')
                //     ->whereRaw(sql:"name LIKE '%" . $req->searchString . "%' ");
                // // ->get();
                // if ($req->startIndex >= 0 && $req->fetchRecord) {
                //     $result = $result->skip($req->startIndex);
                //     $result = $result->take($req->fetchRecord);
                // }

                // $result = $result->get();

                if ($result && count($result) > 0) {
                    foreach ($result as $astro) {
                        // Retrieve user reviews for the current astrologer
                        $reviews = DB::table('user_reviews')
                            ->where('astrologerId', '=', $astro->id)
                            ->get();

                        if ($reviews && count($reviews) > 0) {
                            $avgRating = 0;
                            foreach ($reviews as $review) {
                                $avgRating += $review->rating;
                            }
                            $avgRating = $avgRating / count($reviews);

                            $astro->rating = $avgRating;
                        } else {
                            $astro->rating = 0;
                        }
                    }
                }
                $isFreeAvailable = true;
                $isFreeChat = DB::table('systemflag')->where('name', 'FirstFreeChat')->select('value')->first();
                if ($isFreeChat->value == 1) {
                    if ($req->userId) {
                        $isChatRequest = DB::table('chatrequest')->where('userId', $req->userId)->where('chatStatus', '=', 'Completed')->first();
                        $isCallRequest = DB::table('callrequest')->where('userId', $req->userId)->where('callStatus', '=', 'Completed')->first();
                        if ($isChatRequest || $isCallRequest) {
                            $isFreeAvailable = false;
                        } else {
                            $isFreeAvailable = true;
                        }
                    }
                } else {
                    $isFreeAvailable = false;
                }
                if ($result && count($result) > 0) {
                    for ($i = 0; $i < count($result); $i++) {
                        $astrologerCategory = array_map('intval', explode(',', $result[$i]->astrologerCategoryId));
                        $allSkill = array_map('intval', explode(',', $result[$i]->allSkill));
                        $primarySkill = array_map('intval', explode(',', $result[$i]->primarySkill));
                        $languages = array_map('intval', explode(',', $result[$i]->languageKnown));
                        $allSkill = DB::table('skills')
                            ->whereIn('id', $allSkill)
                            ->select('name')
                            ->get();
                        $skill = $allSkill->pluck('name')->all();
                        $primarySkill = DB::table('skills')
                            ->whereIn('id', $primarySkill)
                            ->select('name')
                            ->get();
                        $primarySkill = $primarySkill->pluck('name')->all();
                        $astrologerCategory = DB::table('astrologer_categories')
                            ->whereIn('id', $astrologerCategory)
                            ->select('name')
                            ->get();
                        $astrologerCategory = $astrologerCategory->pluck('name')->all();
                        $languageKnown = DB::table('languages')
                            ->whereIn('id', $languages)
                            ->select('languageName')
                            ->get();
                        $languageKnown = $languageKnown->pluck('languageName')->all();

                        $result[$i]->languageKnown = implode(',', $languageKnown);
                        $result[$i]->astrologerCategoryId = implode(',', $astrologerCategory);
                        $result[$i]->allSkill = implode(',', $skill);
                        $result[$i]->primarySkill = implode(',', $primarySkill);
                        $result[$i]->isFreeAvailable = $isFreeAvailable;
                    }
                }
            }

            return response()->json([
                'recordList' => $result,
                'status' => 200,
                'message' => 'Get Search AStro',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function getAstrologerForCustomer(Request $req)
    {
        try {
            $data = $req->only('astrologerId');

            $validator = Validator::make($data, [
                'astrologerId' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => $validator->messages(),
                    'status' => 400,
                ], 400);
            }

            $astrologer = Astrologer::where('id', $req->astrologerId)->get();

            $course = CourseOrder::where('astrologerId', $req->astrologerId)
                ->join('courses', 'courses.id', 'course_orders.course_id')
                ->where('course_completion_status', 'completed')
                ->select('courses.course_badge')
                ->get();

            if ($astrologer && count($astrologer) > 0) {
                // ⭐ Image Path Conversion Function
                $convertToAsset = function ($value) {
                    if (empty($value))
                        return null;
                    if (Str::startsWith($value, ['http://', 'https://'])) {
                        return $value;
                    }
                    return asset($value);
                };

                // Convert astrologer image fields
                $fieldsToConvert = ['profileImage', 'aadhar_card', 'pan_card', 'certificate', 'astro_video'];
                foreach ($fieldsToConvert as $field) {
                    if (isset($astrologer[0]->$field)) {
                        $astrologer[0]->$field = $convertToAsset($astrologer[0]->$field);
                    }
                }

                // Skills, Languages, Categories
                $astrologerCategory = array_map('intval', explode(',', $astrologer[0]->astrologerCategoryId));
                $allSkill = array_map('intval', explode(',', $astrologer[0]->allSkill));
                $primary = array_map('intval', explode(',', $astrologer[0]->primarySkill));
                $languages = array_map('intval', explode(',', $astrologer[0]->languageKnown));

                $allSkill = DB::table('skills')->whereIn('id', $allSkill)->pluck('name')->all();
                $primarySkill = DB::table('skills')->whereIn('id', $primary)->pluck('name')->all();
                $astrologerCategory = DB::table('astrologer_categories')->whereIn('id', $astrologerCategory)->pluck('name')->all();
                $languageKnown = DB::table('languages')->whereIn('id', $languages)->pluck('languageName')->all();

                // Follow / Block check
                if ($req->userId) {
                    $astrologer[0]->isFollow = DB::table('astrologer_followers')
                        ->where('userId', $req->userId)
                        ->where('astrologerId', $req->astrologerId)
                        ->exists();

                    $astrologer[0]->isBlock = DB::table('blockastrologer')
                        ->where('userId', $req->userId)
                        ->where('astrologerId', $req->astrologerId)
                        ->exists();
                } else {
                    $astrologer[0]->isFollow = false;
                    $astrologer[0]->isBlock = false;
                }

                // Call / Chat mins
                $astrologer[0]->callMin = DB::table('callrequest')->where('astrologerId', $req->astrologerId)->sum('totalMin');
                $astrologer[0]->chatMin = DB::table('chatrequest')->where('astrologerId', $req->astrologerId)->sum('totalMin');

                // Followers
                $astrologer[0]->follower = DB::table('astrologer_followers')->where('astrologerId', $req->astrologerId)->count();
                $astrologer[0]->fcmToken = DB::table('user_device_details')->where('userId', $astrologer[0]->userId)->value('fcmToken');

                // Reviews
                $reviews = DB::table('user_reviews')
                    ->join('users as u', 'u.id', '=', 'user_reviews.userId')
                    ->where('astrologerId', $req->astrologerId)
                    ->select('user_reviews.*', 'u.name as userName', 'u.profile')
                    ->orderBy('user_reviews.id', 'DESC')
                    ->get();

                $rating = ['oneStarRating' => 0, 'twoStarRating' => 0, 'threeStarRating' => 0, 'fourStarRating' => 0, 'fiveStarRating' => 0];
                foreach ($reviews as $r) {
                    $round = round($r->rating);
                    if ($round == 1)
                        $rating['oneStarRating']++;
                    if ($round == 2)
                        $rating['twoStarRating']++;
                    if ($round == 3)
                        $rating['threeStarRating']++;
                    if ($round == 4)
                        $rating['fourStarRating']++;
                    if ($round == 5)
                        $rating['fiveStarRating']++;
                }

                $avgRating = DB::table('user_reviews')->where('astrologerId', $req->astrologerId)->avg('rating');

                // Free availability
                $isFreeAvailable = false;
                $isFreeChat = DB::table('systemflag')->where('name', 'FirstFreeChat')->first();
                if ($isFreeChat && $isFreeChat->value == 1 && $req->userId) {
                    $hasChatOrCall = DB::table('chatrequest')->where('userId', $req->userId)->where('chatStatus', 'Completed')->exists() ||
                        DB::table('callrequest')->where('userId', $req->userId)->where('callStatus', 'Completed')->exists();
                    $isFreeAvailable = !$hasChatOrCall;
                }

                // Similar consultants
                $consultants = DB::table('astrologers')
                    ->where('isActive', 1)
                    ->where('isVerified', 1)
                    ->where('isDelete', 0)
                    ->where('id', '!=', $req->astrologerId)
                    ->select('profileImage', 'name', 'charge', 'primarySkill', 'id')
                    ->orderBy('id', 'DESC')
                    ->get();

                $similiar = [];
                foreach ($consultants as $c) {
                    $consultantPrimarySkill = array_map('intval', explode(',', $c->primarySkill));
                    if (!empty(array_intersect($primary, $consultantPrimarySkill))) {
                        $similiar[] = $c;
                    }
                    if (count($similiar) >= 3)
                        break;
                }

                $index = 0;
                while (count($similiar) < 3 && $index < count($consultants)) {
                    if (!in_array($consultants[$index]->id, array_map(fn($x) => $x->id, $similiar))) {
                        $similiar[] = $consultants[$index];
                    }
                    $index++;
                }

                // ⭐ Convert similiar consultants profileImage
                foreach ($similiar as $key => $c) {
                    if (isset($c->profileImage)) {
                        $similiar[$key]->profileImage = $convertToAsset($c->profileImage);
                    }
                }

                // Assign processed fields
                $astrologer[0]->languageKnown = implode(',', $languageKnown);
                $astrologer[0]->astrologerCategoryId = implode(',', $astrologerCategory);
                $astrologer[0]->allSkill = implode(',', $allSkill);
                $astrologer[0]->primarySkill = implode(',', $primarySkill);
                $astrologer[0]->astrologerRating = $rating;
                $astrologer[0]->rating = $avgRating;
                $astrologer[0]->isFreeAvailable = $isFreeAvailable;
                $astrologer[0]->similiarConsultant = $similiar;
                $astrologer[0]->courseBadges = $course;
                $astrologer[0]->chat_discounted_rate = $astrologer[0]->isDiscountedPrice
                    ? ($astrologer[0]->charge - ($astrologer[0]->charge * $astrologer[0]->chat_discount / 100))
                    : 0;

                $astrologer[0]->audio_discounted_rate = $astrologer[0]->isDiscountedPrice
                    ? ($astrologer[0]->charge - ($astrologer[0]->charge * $astrologer[0]->audio_discount / 100))
                    : 0;

                $astrologer[0]->video_discounted_rate = $astrologer[0]->isDiscountedPrice
                    ? ($astrologer[0]->videoCallRate - ($astrologer[0]->videoCallRate * $astrologer[0]->video_discount / 100))
                    : 0;
            }

            return response()->json([
                'message' => 'get Astrologer Profile',
                'recordList' => $astrologer,
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

    public function reportblockAstrologer(request $req)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            } else {
                $id = Auth::guard('api')->user()->id;
            }
            $data = $req->only(
                'astrologerId',
                'reason',
            );

            // Validate the data
            $validator = Validator::make($data, [
                'astrologerId' => 'required',
                'reason' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }

            $reportBlock = array(
                'astrologerId' => $req->astrologerId,
                'userId' => $id,
                'reason' => $req->reason,
            );
            DB::table('blockastrologer')->insert($reportBlock);
            return response()->json([
                'message' => 'Block Astrologer',
                'status' => 200,
            ], 200);
            // $reports = DB::table('blockastrologer')
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function unblockAstrologer(Request $req)
    {
        // dd($req->all());
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            } else {
                $id = Auth::guard('api')->user()->id;
            }
            DB::table('blockastrologer')
                ->where('userId', '=', $id)
                ->where('astrologerId', '=', $req->astrologerId)
                ->delete();
            return response()->json([
                'message' => 'UnBlock Astrologer',
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

    public function getBlockAstrologer(Request $req)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            } else {
                $id = Auth::guard('api')->user()->id;
            }
            $blockAstrologer = DB::table('blockastrologer')->where('userId', '=', $id)->get();
            if ($blockAstrologer && count($blockAstrologer) > 0) {
                foreach ($blockAstrologer as $block) {
                    $astrologer = Astrologer::find($block->astrologerId);
                    $astrologerCategory = array_map('intval', explode(',', $astrologer->astrologerCategoryId));
                    $allSkill = array_map('intval', explode(',', $astrologer->allSkill));
                    $primarySkill = array_map('intval', explode(',', $astrologer->primarySkill));
                    $languages = array_map('intval', explode(',', $astrologer->languageKnown));
                    $allSkill = DB::table('skills')
                        ->whereIn('id', $allSkill)
                        ->select('name')
                        ->get();
                    $skill = $allSkill->pluck('name')->all();
                    $primarySkill = DB::table('skills')
                        ->whereIn('id', $primarySkill)
                        ->select('name')
                        ->get();
                    $primarySkill = $primarySkill->pluck('name')->all();
                    $astrologerCategory = DB::table('astrologer_categories')
                        ->whereIn('id', $astrologerCategory)
                        ->select('name')
                        ->get();
                    $astrologerCategory = $astrologerCategory->pluck('name')->all();
                    $languageKnown = DB::table('languages')
                        ->whereIn('id', $languages)
                        ->select('languageName')
                        ->get();
                    $languageKnown = $languageKnown->pluck('languageName')->all();
                    $block->languageKnown = implode(',', $languageKnown);
                    $block->astrologerCategoryId = implode(',', $astrologerCategory);
                    $block->allSkill = implode(',', $skill);
                    $block->primarySkill = implode(',', $primarySkill);
                    $block->profile = $astrologer->profileImage;
                    $block->astrologerName = $astrologer->name;
                    $block->experienceInYears = $astrologer->experienceInYears;
                }
            }
            return response()->json([
                'message' => 'Get Block Astrologer',
                'status' => 200,
                'recordList' => $blockAstrologer,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function getUserById(Request $req)
    {
        try {
            $data = $req->only([
                'userId',
            ]);
            $validator = Validator::make($data, [
                'userId' => 'required',
            ]);

            // Send failed response if request is not valid
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors(), 'status' => 400], 400);
            }

            $user = DB::table('users')
                ->where('id', '=', $req->userId)
                ->get();
            if ($user && count($user) > 0) {
                unset($user[0]->password);
                $walletRow = DB::table('user_wallets')->where('userId', $req->userId)->first();
                $user[0]->walletAmount = $walletRow ? (float)$walletRow->amount : 0.0;
                $user[0]->totalWalletAmount = $user[0]->walletAmount;
                $user[0]->wallet_amount = $user[0]->walletAmount;
                $follower = DB::table('astrologer_followers')
                    ->join('astrologers', 'astrologer_followers.astrologerId', '=', 'astrologers.id')
                    ->where('astrologer_followers.userId', '=', $req->userId)
                    ->select('astrologers.*', 'astrologer_followers.created_at as followingDate')
                    ->orderBy('astrologer_followers.id', 'DESC')
                    ->get();
                if ($follower && count($follower) > 0) {
                    foreach ($follower as $follow) {
                        $languages = DB::table('languages')
                            ->whereIn('id', explode(',', $follow->languageKnown))
                            ->select('languageName')
                            ->get();

                        $allSkill = DB::table('skills')
                            ->whereIn('id', explode(',', $follow->languageKnown))
                            ->get('name');
                        $follow->languageKnown = $languages;
                        $follow->allSkill = $allSkill;
                    }
                }
                $orderRequest = UserOrder::join('product_categories', 'product_categories.id', '=', 'order_request.productCategoryId')
                    ->join('astromall_products', 'astromall_products.id', '=', 'order_request.productId')
                    ->join('order_addresses', 'order_addresses.id', '=', 'order_request.orderAddressId')
                    ->where('order_request.userId', '=', $req->userId)
                    ->where('order_request.orderType', '=', 'astromall');

                $orderRequestCount = $orderRequest->count();
                $orderRequest->select('order_request.*', 'product_categories.name as productCategory',
                    'astromall_products.productImage',
                    'astromall_products.amount as productAmount', 'astromall_products.description',
                    'order_addresses.name as orderAddressName', 'order_addresses.phoneNumber',
                    'order_addresses.flatNo', 'order_addresses.locality', 'order_addresses.landmark',
                    'order_addresses.city', 'order_addresses.state', 'order_addresses.country',
                    'order_addresses.pincode', 'astromall_products.name as productName')->addSelect(DB::raw("CONCAT('" . route('order.invoice', '') . "/', order_request.id) as invoice_link"));

                $orderRequest->orderBy('order_request.id', 'DESC');
                if ($req->startIndex >= 0 && $req->fetchRecord) {
                    $orderRequest->skip($req->startIndex);
                    $orderRequest->take($req->fetchRecord);
                }
                $orderRequest = $orderRequest->get();

                $giftList = AstrologerGift::join('gifts', 'gifts.id', 'astrologer_gifts.giftId')
                    ->join('astrologers', 'astrologers.id', '=', 'astrologer_gifts.astrologerId')
                    ->where('astrologer_gifts.userId', '=', $req->userId);

                $giftListCount = $giftList->count();
                $giftList->select('gifts.name as giftName', 'astrologer_gifts.*', 'astrologers.id as astrologerId', 'astrologers.name as astrolgoerName', 'astrologers.contactNo');

                $giftList->orderBy('astrologer_gifts.id', 'DESC');
                if ($req->startIndex >= 0 && $req->fetchRecord) {
                    $giftList->skip($req->startIndex);
                    $giftList->take($req->fetchRecord);
                }
                $giftList = $giftList->get();

                $chatHistory = ChatRequest::join('astrologers as astr', 'astr.id', '=', 'chatrequest.astrologerId')
                    ->where('chatrequest.userId', '=', $req->userId)
                    ->where('chatrequest.chatStatus', '=', 'Completed');

                $chatHistoryCount = $chatHistory->count();
                $chatHistory->select(
                    'chatrequest.*',
                    'astr.id as astrologerId',
                    'astr.name as astrologerName',
                    'astr.contactNo',
                    'astr.profileImage',
                    'astr.charge',
                    'chatrequest.created_at'
                );
                $chatHistory->orderBy('chatrequest.id', 'DESC');
                if ($req->startIndex >= 0 && $req->fetchRecord) {
                    $chatHistory->skip($req->startIndex);
                    $chatHistory->take($req->fetchRecord);
                }
                $chatHistory = $chatHistory->get();
                if ($chatHistory && count($chatHistory) > 0) {
                    for ($i = 0; $i < count($chatHistory); $i++) {
                        $chatHistory[$i]->isFreeSession = $chatHistory[$i]->isFreeSession ? true : false;
                    }
                }

                $AichatHistory = AiChatHistory::join('aiastrologers as astr', 'astr.id', '=', 'ai_chat_histories.ai_astrologer_id')
                    ->where('ai_chat_histories.user_id', '=', $req->userId);

                $AichatHistoryCount = $AichatHistory->count();
                $AichatHistory->select(
                    'ai_chat_histories.*', 'astr.id as astrologerId', 'astr.name as astrologerName',
                    'astr.image', 'astr.chat_charge'
                );
                $AichatHistory->orderBy('ai_chat_histories.id', 'DESC');
                if ($req->startIndex >= 0 && $req->fetchRecord) {
                    $AichatHistory->skip($req->startIndex);
                    $AichatHistory->take($req->fetchRecord);
                }
                $AichatHistory = $AichatHistory->get();
                if ($AichatHistory && count($AichatHistory) > 0) {
                    for ($i = 0; $i < count($AichatHistory); $i++) {
                        $AichatHistory[$i]->is_free = $AichatHistory[$i]->is_free ? true : false;
                    }
                }

                $callHistory = CallRequest::join('astrologers as astro', 'astro.id', '=', 'callrequest.astrologerId')
                    ->where('callrequest.userId', '=', $req->userId)
                    ->where('callrequest.callStatus', '=', 'Completed');
                $callHistoryCount = $callHistory->count();
                $callHistory->select(
                    'callrequest.*',
                    'astro.id as astrologerId',
                    'astro.name as astrologerName',
                    'astro.contactNo',
                    'astro.profileImage',
                    'astro.charge',
                    'callrequest.created_at'
                );
                $callHistory->orderBy('callrequest.id', 'DESC');

                if ($req->startIndex >= 0 && $req->fetchRecord) {
                    $callHistory->skip($req->startIndex);
                    $callHistory->take($req->fetchRecord);
                }
                $callHistory = $callHistory->get();
                if ($callHistory && count($callHistory) > 0) {
                    for ($i = 0; $i < count($callHistory); $i++) {
                        $callHistory[$i]->isFreeSession = $callHistory[$i]->isFreeSession ? true : false;
                    }
                }

                $Pendingchat = CallRequest::join('astrologers as astro', 'astro.id', '=', 'callrequest.astrologerId')
                    ->where('callrequest.userId', '=', $req->userId)
                    ->where('callrequest.callStatus', '=', 'Pending');
                $PendingchatCount = $Pendingchat->count();
                $Pendingchat->select(
                    'callrequest.*',
                    'astro.id as astrologerId',
                    'astro.name as astrologerName',
                    'astro.contactNo',
                    'astro.profileImage',
                    'astro.charge',
                    'callrequest.created_at'
                );
                $Pendingchat->orderBy('callrequest.id', 'DESC');

                if ($req->startIndex >= 0 && $req->fetchRecord) {
                    $Pendingchat->skip($req->startIndex);
                    $Pendingchat->take($req->fetchRecord);
                }
                $Pendingchat = $Pendingchat->get();
                if ($Pendingchat && count($Pendingchat) > 0) {
                    for ($i = 0; $i < count($Pendingchat); $i++) {
                        $Pendingchat[$i]->isFreeSession = $Pendingchat[$i]->isFreeSession ? true : false;
                    }
                }

                $Pendingcall = CallRequest::join('astrologers as astro', 'astro.id', '=', 'callrequest.astrologerId')
                    ->where('callrequest.userId', '=', $req->userId)
                    ->where('callrequest.callStatus', '=', 'Pending');
                $PendingcallCount = $Pendingcall->count();
                $Pendingcall->select(
                    'callrequest.*',
                    'astro.id as astrologerId',
                    'astro.name as astrologerName',
                    'astro.contactNo',
                    'astro.profileImage',
                    'astro.charge',
                    'callrequest.created_at'
                );
                $Pendingcall->orderBy('callrequest.id', 'DESC');

                if ($req->startIndex >= 0 && $req->fetchRecord) {
                    $Pendingcall->skip($req->startIndex);
                    $Pendingcall->take($req->fetchRecord);
                }
                $Pendingcall = $Pendingcall->get();
                if ($Pendingcall && count($Pendingcall) > 0) {
                    for ($i = 0; $i < count($Pendingcall); $i++) {
                        $Pendingcall[$i]->isFreeSession = $Pendingcall[$i]->isFreeSession ? true : false;
                    }
                }

                $reportHistory = UserReport::join('astrologers as astro', 'astro.id', '=', 'user_reports.astrologerId')
                    ->join('report_types', 'report_types.id', '=', 'user_reports.reportType')
                    ->where('user_reports.userId', '=', $req->userId);

                $reportHistoryCount = $reportHistory->count();

                $reportHistory->select('user_reports.*', 'astro.id as astrologerId', 'astro.name as astrologerName', 'astro.contactNo', 'report_types.title', 'astro.profileImage', 'astro.reportRate as reportPrice');

                $reportHistory->orderBy('user_reports.id', 'DESC');
                if ($req->startIndex >= 0 && $req->fetchRecord) {
                    $reportHistory->skip($req->startIndex);
                    $reportHistory->take($req->fetchRecord);
                }
                $reportHistory = $reportHistory->get();
                if ($reportHistory && count($reportHistory) > 0) {
                    for ($i = 0; $i < count($reportHistory); $i++) {
                        if (!$reportHistory[$i]->reportFile) {
                            $reportHistory[$i]->isFileUpload = false;
                        } else {
                            $reportHistory[$i]->isFileUpload = true;
                        }
                    }
                }

                $wallet = WalletTransaction::leftjoin('order_request', 'order_request.id', '=', 'wallettransaction.orderId')
                    ->leftjoin('astrologers', 'astrologers.id', '=', 'wallettransaction.astrologerId')
                    ->leftjoin('aiastrologers', 'aiastrologers.id', '=', 'wallettransaction.aiAstrologerId')
                    ->where('wallettransaction.userId', '=', $req->userId);

                $walletCount = $wallet->count();
                $wallet->select(
                    'wallettransaction.*',
                    'astrologers.name',
                    'order_request.totalMin',
                    'aiastrologers.name as aiastrologername'
                );
                $wallet->orderBy('wallettransaction.id', 'DESC');
                if ($req->startIndex >= 0 && $req->fetchRecord) {
                    $wallet->skip($req->startIndex);
                    $wallet->take($req->fetchRecord);
                }
                $wallet = $wallet->get();

                $payment = DB::table('payment')
                    ->where('userId', '=', $req->userId)
                    ->orderBy('id', 'DESC');
                $paymentCount = $payment->count();
                if ($req->startIndex >= 0 && $req->fetchRecord) {
                    $payment->skip($req->startIndex);
                    $payment->take($req->fetchRecord);
                }
                $payment = $payment->get();

                // ---------puja history ------------------------------------
                $pujahistory = PujaOrder::leftjoin('astrologers', 'puja_orders.astrologer_id', '=', 'astrologers.id')
                    ->select('puja_orders.*', 'astrologers.name as astrologer_name')
                    ->where('puja_orders.user_id', '=', $req->userId)
                    ->orderBy('puja_orders.id', 'DESC');

                $pujaCount = $pujahistory->count();

                if ($req->startIndex >= 0 && $req->fetchRecord) {
                    $pujahistory->skip($req->startIndex);
                    $pujahistory->take($req->fetchRecord);
                }

                $pujahistory = $pujahistory->get();

                $pujahistory = $pujahistory->map(function ($item) {
                    $item->puja_images = json_decode($item->puja_images, true);
                    $item->package = [
                        'title' => $item->title,
                        'description' => $item->description
                    ];
                    unset($item->description, $item->title);

                    $pujaOrder = PujaOrder::find($item->id);
                    $item->pujaLink = $pujaOrder->Pujabroadcast($item->astrologer_id, false);
                    return $item;
                });

                // ----------- CallRequestApoinments History ------------
                $appointments = DB::table('call_request_apoinments')
                    ->join('callrequest', 'callrequest.id', '=', 'call_request_apoinments.callId')
                    ->leftJoin('astrologers', 'call_request_apoinments.astrologerId', '=', 'astrologers.id')
                    ->leftJoin('users', 'call_request_apoinments.userId', '=', 'users.id')
                    ->select(
                        'call_request_apoinments.id',
                        'users.name as userName',
                        'astrologers.name as astrologerName',
                        'astrologers.contactNo',
                        'astrologers.profileImage',
                        'astrologers.charge',
                        'callrequest.callStatus',
                        'callrequest.channelName',
                        'callrequest.token',
                        'callrequest.totalMin',
                        'callrequest.inr_usd_conversion_rate',
                        'callrequest.callRate',
                        'callrequest.deduction',
                        'callrequest.call_duration',
                        'call_request_apoinments.created_at',
                        'call_request_apoinments.updated_at',
                        'callrequest.deductionFromAstrologer',
                        'callrequest.sId',
                        'callrequest.sId1',
                        'callrequest.chatId',
                        'callrequest.isFreeSession',
                        'callrequest.call_type',
                        'callrequest.call_method',
                        'callrequest.validated_till',
                        'callrequest.is_emergency',
                        'callrequest.IsSchedule',
                        'callrequest.schedule_date',
                        'callrequest.schedule_time',
                        'call_request_apoinments.amount',
                        'call_request_apoinments.call_duration as apoinment_call_duration',
                        'call_request_apoinments.call_method as apoinment_call_method',
                        'call_request_apoinments.status as apoinment_status',
                        'call_request_apoinments.IsActive as apoinment_is_active'
                    )
                    ->where('call_request_apoinments.userId', '=', $req->userId)
                    ->orderBy('call_request_apoinments.id', 'DESC');

                $appoinmentsCount = $appointments->count();

                if ($req->startIndex >= 0 && $req->fetchRecord) {
                    $appointments->skip($req->startIndex);
                    $appointments->take($req->fetchRecord);
                }

                $appointments = $appointments->get();

                if ($appointments && count($appointments) > 0) {
                    foreach ($appointments as $a) {
                        $a->isFreeSession = $a->isFreeSession ? true : false;
                        $a->isSchedule = $a->IsSchedule ? true : false;
                        $a->isEmergency = $a->is_emergency ? true : false;
                    }
                }

                $notification = DB::table('user_notifications')
                    ->where('userId', '=', $req->userId)
                    ->orderBy('id', 'DESC')
                    ->limit(20)
                    ->get();

                $orderRequests = array(
                    'totalCount' => $orderRequestCount,
                    'order' => $orderRequest,
                );
                $giftLists = array(
                    'totalCount' => $giftListCount,
                    'gifts' => $giftList,
                );
                $chatHistorys = array(
                    'totalCount' => $chatHistoryCount,
                    'chatHistory' => $chatHistory,
                );
                $AichatHistorys = array(
                    'totalCount' => $AichatHistoryCount,
                    'chatHistory' => $AichatHistory,
                );
                $callHistorys = array(
                    'totalCount' => $callHistoryCount,
                    'callHistory' => $callHistory,
                );
                $Pendingcalls = array(
                    'PendingcallCount' => $PendingcallCount,
                    'Pendingcall' => $Pendingcall,
                );
                $Pendingchats = array(
                    'PendingchatCount' => $PendingchatCount,
                    'Pendingchat' => $Pendingchat,
                );
                $reportHistorys = array(
                    'totalCount' => $reportHistoryCount,
                    'reportHistory' => $reportHistory,
                );
                $wallets = array(
                    'totalCount' => $walletCount,
                    'wallet' => $wallet,
                );
                $payments = array(
                    'totalCount' => $paymentCount,
                    'payment' => $payment,
                );
                $pujahistorys = array(
                    'totalCount' => $pujaCount,
                    'pujaHistory' => $pujahistory,
                );

                $user[0]->follower = $follower;
                $user[0]->orders = $orderRequests;
                $user[0]->sendGifts = $giftLists;
                $user[0]->chatRequest = $chatHistorys;
                $user[0]->callRequest = $callHistorys;
                $user[0]->AichatRequest = $AichatHistorys;
                $user[0]->reportRequest = $reportHistorys;
                $user[0]->walletTransaction = $wallets;
                $user[0]->paymentLogs = $payments;
                $user[0]->pujaOrder = $pujahistorys;
                $user[0]->notification = $notification;
                $user[0]->appointments = $appointments;
                $user[0]->Pendingcall = $Pendingcalls;
                $user[0]->Pendingchat = $Pendingchats;

                // ✅ Image URL conversion (only addition)
                $allCollections = [
                    $user,
                    $follower ?? [],
                    $orderRequest ?? [],
                    $giftList ?? [],
                    $chatHistory ?? [],
                    $AichatHistory ?? [],
                    $callHistory ?? [],
                    $Pendingcall ?? [],
                    $Pendingchat ?? [],
                    $reportHistory ?? [],
                    $wallet ?? [],
                    $payment ?? [],
                    $pujahistory ?? [],
                    $appointments ?? [],
                ];

                foreach ($allCollections as $collection) {
                    if (!empty($collection)) {
                        foreach ($collection as $item) {
                            foreach (['productImage', 'profile', 'profileImage', 'image'] as $imgField) {
                                if (isset($item->$imgField) && !empty($item->$imgField)) {
                                    if (!preg_match('/^https?:\/\//i', $item->$imgField)) {
                                        $item->$imgField = asset($item->$imgField);
                                    }
                                }
                            }
                        }
                    }
                }

                return response()->json([
                    'message' => 'Get User Successfully',
                    'status' => 200,
                    'recordList' => $user,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function validateSession(Request $req)
    {
        try {
            // Check if the user is authenticated via API guard
            if (!Auth::guard('api')->user()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Login credentials are invalid.',
                ], 400);
            } else {
                // Get the authenticated user
                $user = Auth::guard('api')->user();
                $user = User::find($user->id);

                // Add session token info
                $user->sessionToken = $req->header('Authorization');
                $user->token_type = 'Bearer';

                $userWalletAmount = UserWallet::where('userId', $user->id)->value('amount') ?? 0;
                $user->totalWalletAmount = $user->countryCode == '+91' ? $userWalletAmount : convertinrtousd($userWalletAmount);

                // Convert isProfileComplete to boolean
                $user->isProfileComplete = $user->isProfileComplete == 1 ? true : false;

                // ⭐ Image Path Conversion Function
                $convertToAsset = function ($value) {
                    if (empty($value))
                        return null;

                    // If already a full URL (DigitalOcean or other)
                    if (Str::startsWith($value, ['http://', 'https://'])) {
                        return $value;
                    }

                    // Otherwise convert using asset()
                    return asset($value);
                };

                // List of image fields to convert
                $fieldsToConvert = [
                    'profile',
                    'aadhar_card',
                    'pan_card',
                    'certificate',
                    'astro_video',
                    'banner',
                    'cover_photo',
                ];

                // Convert each image path
                foreach ($fieldsToConvert as $field) {
                    if (isset($user->$field)) {
                        $user->$field = $convertToAsset($user->$field);
                    }
                }

                $isFreeChat = DB::table('systemflag')->where('name', 'FirstFreeChat')->select('value')->first();
                $isFreeAvailable = true;
                if ($isFreeChat->value == 1) {
                    if ($user->id) {
                        $isChatRequest = DB::table('chatrequest')->where('userId', $user->id)->where('chatStatus', '=', 'Completed')->first();
                        $isCallRequest = DB::table('callrequest')->where('userId', $user->id)->where('callStatus', '=', 'Completed')->first();
                        $isFreeAvailable = !($isChatRequest || $isCallRequest);
                    }
                } else {
                    $isFreeAvailable = false;
                }
                $user->is_freechat = $isFreeAvailable;

                return response()->json([
                    'status' => 200,
                    'recordList' => $user,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function validateSessionForAstrologer(Request $req)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Login credentials are invalid.',
                ], 400);
            } else {
                $astrologer = DB::Table('astrologers')->where('userId', '=', Auth::guard('api')->user()->id)->get();
                if ($astrologer) {
                    if ($astrologer[0]->isVerified <= 0) {
                        return response()->json([
                            'message' => 'Your Account is not verified from admin',
                            'status' => 400,
                        ], 400);
                    }

                    $astrologer[0]->allSkill = array_map('intval', explode(',', $astrologer[0]->allSkill));
                    $astrologer[0]->primarySkill = array_map('intval', explode(',', $astrologer[0]->primarySkill));
                    $astrologer[0]->languageKnown = array_map('intval', explode(',', $astrologer[0]->languageKnown));
                    $astrologer[0]->astrologerCategoryId =
                        array_map('intval', explode(',', $astrologer[0]->astrologerCategoryId));
                    $allSkill = DB::table('skills')
                        ->whereIn('id', $astrologer[0]->allSkill)
                        ->select('name', 'id')
                        ->get();
                    $primarySkill = DB::table('skills')
                        ->whereIn('id', $astrologer[0]->primarySkill)
                        ->select('name', 'id')
                        ->get();
                    $languageKnown = DB::table('languages')
                        ->whereIn('id', $astrologer[0]->languageKnown)
                        ->select('languageName', 'id')
                        ->get();
                    $category = DB::table('astrologer_categories')
                        ->whereIn('id', $astrologer[0]->astrologerCategoryId)
                        ->select('name', 'id')
                        ->get();
                    $astrologer[0]->allSkill = $allSkill;
                    $astrologer[0]->primarySkill = $primarySkill;
                    $astrologer[0]->languageKnown = $languageKnown;
                    $astrologer[0]->astrologerCategoryId = $category;
                    $astrologerAvailability = DB::table('astrologer_availabilities')
                        ->where('astrologerId', '=', $astrologer[0]->id)
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
                            $result =
                                array_filter(json_decode($astrologerAvailability), function ($event) use ($currentday) {
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
                    $astrologer[0]->astrologerAvailability = $working;
                }
                $astrologer[0]->sessionToken = $req->header('Authorization');
                $astrologer[0]->token_type = 'Bearer';

                $documents = AstrologerDocument::query()->get();
                $documentMap = [];

                foreach ($documents as $document) {
                    $columnName = Str::snake($document->name);
                    if (Schema::hasColumn('astrologers', $columnName)) {
                        $documentMap[$columnName] = $astrologer[0]->$columnName ?? null;
                    }
                }

                $astrologer[0]->documentMap = $documentMap;

                return response()->json([
                    'status' => 200,
                    'recordList' => $astrologer[0],
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function getUserProfile(Request $req)
    {
        try {
            $userProfile = DefaultProfile::query()->where('isActive', true)->get();
            return response()->json([
                'status' => 200,
                'message' => 'Get Profile Successfully',
                'recordList' => $userProfile,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function getUserdetails()
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            }
            $data = Auth::guard('api')->user();
            $userWallet = UserWallet::query()
                ->where('userId', '=', $data['id'])
                ->get();
            if ($userWallet && count($userWallet) > 0) {
                $data->totalWalletAmount = $userWallet[0]->amount;
            } else {
                $data->totalWalletAmount = 0;
            }
            return response()->json([
                'status' => 200,
                'message' => 'Get Profile Successfully',
                'userDetails' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function getAstrologerdetails()
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            }
            $data = Auth::guard('api')->user();
            $userWallet = UserWallet::query()
                ->where('userId', '=', $data['id'])
                ->get();

            $astrologer = DB::table('astrologers')->where('astrologers.userId', '=', $data['id'])->select('astrologers.id as astrologerId')->get();

            if ($userWallet && count($userWallet) > 0) {
                $data->totalWalletAmount = $userWallet[0]->amount;
            } else {
                $data->totalWalletAmount = 0;
            }

            if ($astrologer && count($astrologer) > 0) {
                $data->astrologerId = $astrologer[0]->astrologerId;
            } else {
                $data->astrologerId = 0;
            }

            return response()->json([
                'status' => 200,
                'message' => 'Get Profile Successfully',
                'userDetails' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function getDocumentList()
    {
        try {
            $document = AstrologerDocument::query();
            return response()->json([
                'recordList' => $document->get(),
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

    // Check Email Contact Exist
    public function checkemailContactExist(Request $req)
    {
        try {
            $data = $req->only('contactNo', 'email');

            $emailExists = DB::table('users')->where('email', $req->email)->exists();
            $contactExists = DB::table('users')->where('contactNo', $req->contactNo)->exists();

            $messages = [];
            $status = 200;

            if ($emailExists && $contactExists) {
                $messages['email'] = 'Email already exists.';
                $messages['contact'] = 'Contact number already exists.';
                $status = 400;
            } elseif ($emailExists) {
                $messages['email'] = 'Email already exists.';
                $status = 400;
            } elseif ($contactExists) {
                $messages['contact'] = 'Contact number already exists.';
                $status = 400;
            }

            return response()->json([
                'messages' => $messages,
                'status' => $status,
            ], $status);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function resendOtp(Request $request)
    {
        if (!empty($request->contactNo)) {
            $mobile = (string) $request->contactNo;
            $countryCode = (string) ($request->countryCode ?? '91');
            $otp = strval(random_int(100000, 999999));
            if (in_array($mobile, ['9898989898', '9797979797'])) {
                $otp = '111111';
            } else {
                try {
                    $otpResult = app(\App\Services\OtpService::class)->send($mobile, $otp, $countryCode);
                    if (!$otpResult['ok']) {
                        $otp = '111111';
                    }
                } catch (\Throwable $e) {
                    $otp = '111111';
                }
            }

            return response()->json([
                'message' => 'OTP resent successfully!',
                'status' => 200,
                'otp' => !empty($request->fromWeb) ? base64_encode($otp) : $otp,
            ], 200);
        } else {
            return response()->json([
                'message' => 'Mobile number is empty',
                'status' => 400,
            ], 400);
        }
    }

    public function sendOtpMobileEmail(Request $req)
    {
        // 1. Input Validation and Sanitization
        $validated = $req->validate([
            'contactNo' => 'required|string',
            'email' => 'required|email',
            'countryCode' => 'nullable|string',
            'fromWeb' => 'nullable|boolean',
        ]);

        // 2. Generate OTPs
        $emailOtp = strval(random_int(100000, 999999));
        $mobileOtp = strval(random_int(100000, 999999));
        if (!empty($req->storedEmailOtp)) {
            $emailOtp = $req->storedEmailOtp;
        }
        if (!empty($req->storedMobileOtp)) {
            $mobileOtp = $req->storedMobileOtp;
        }

        try {
            if ($req->otptype == 'both') {
                // 3. Send Mobile OTP via unified OtpService (WhatsApp failover)
                if (in_array($req->contactNo, ['9898989898', '9797979797'])) {
                    $mobileOtp = '111111';
                } else {
                    try {
                        $otpResult = app(\App\Services\OtpService::class)->send(
                            $req->contactNo,
                            $mobileOtp,
                            $req->countryCode ?? '91'
                        );
                        if (!$otpResult['ok']) {
                            $mobileOtp = '111111';
                        }
                    } catch (\Throwable $e) {
                        $mobileOtp = '111111';
                    }
                }

                // 4. Send Email OTP
                $otpTemplate = EmailTemplate::where('name', 'partner_register_otp')->first();
                if (!$otpTemplate) {
                    return response()->json([
                        'message' => 'Email Template not found',
                        'status' => 400,
                    ], 400);
                }

                $logo = DB::table('systemflag')->where('name', 'AdminLogo')->value('value');
                $company_name = DB::table('systemflag')->where('name', 'AppName')->value('value');

                $body = str_replace(
                    ['{{$logo}}', '{{$company_name}}', '{{$otp}}'],
                    [asset($logo), $company_name, $emailOtp],
                    $otpTemplate->description
                );

                $body = html_entity_decode($body);

                Mail::send([], [], function ($message) use ($otpTemplate, $body, $validated) {
                    $message->to($validated['email'])
                        ->subject($otpTemplate->subject)
                        ->html($body);
                });
            } elseif ($req->otptype == 'email') {
                // 4. Send Email OTP
                $otpTemplate = EmailTemplate::where('name', 'partner_register_otp')->first();
                if (!$otpTemplate) {
                    return response()->json([
                        'message' => 'Email Template not found',
                        'status' => 400,
                    ], 400);
                }

                $logo = DB::table('systemflag')->where('name', 'AdminLogo')->value('value');
                $company_name = DB::table('systemflag')->where('name', 'AppName')->value('value');

                $body = str_replace(
                    ['{{$logo}}', '{{$company_name}}', '{{$otp}}'],
                    [asset($logo), $company_name, $emailOtp],
                    $otpTemplate->description
                );

                $body = html_entity_decode($body);

                Mail::send([], [], function ($message) use ($otpTemplate, $body, $validated) {
                    $message->to($validated['email'])
                        ->subject($otpTemplate->subject)
                        ->html($body);
                });
            } elseif ($req->otptype == 'mobile') {
                // 3. Send Mobile OTP (via MSG91)
                $msg91AuthKey = DB::table('systemflag')->where('name', 'msg91AuthKey')->value('value');
                $msg91SendOtpTemplateId = DB::table('systemflag')->where('name', 'msg91SendOtpTemplateId')->value('value');

                if (empty($msg91AuthKey) || empty($msg91SendOtpTemplateId)) {
                    return response()->json([
                        'message' => 'MSG91 credentials not found',
                        'status' => 500,
                    ], 500);
                }

                 $payload = [
                        'template_id'      => $msg91SendOtpTemplateId,
                        'short_url'        => '0',
                        'realTimeResponse' => '1',
                        'recipients'       => [
                            [
                                'mobiles' => $req->countryCode . $req->contactNo,
                                'otp'     => (string) $mobileOtp,
                            ]
                        ],
                    ];

                $curl = curl_init();
                curl_setopt_array($curl, [
                        CURLOPT_URL => 'https://control.msg91.com/api/v5/flow',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => json_encode($payload),
                        CURLOPT_HTTPHEADER => [
                            'accept: application/json',
                            "authkey: $msg91AuthKey",
                            'content-type: application/json'
                        ],
                    ]);

                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);

                if ($err) {
                    return response()->json([
                        'message' => 'cURL Error: ' . $err,
                        'status' => 500,
                    ], 500);
                }

                $resData = json_decode($response, true);
                if (!isset($resData['type']) || $resData['type'] !== 'success') {
                    return response()->json([
                        'message' => 'Failed to send Mobile OTP',
                        'status' => 400,
                        'data' => $resData,
                    ], 400);
                }
            }
            // 5. Successful Response
            return response()->json([
                'status' => 200,
                'message' => 'OTP sent successfully!',
                'mobile' => $validated['contactNo'],
                'email_otp' => $req->has('fromWeb') && $req->fromWeb ? base64_encode($mobileOtp) : $mobileOtp,
                'mobile_otp' => $req->has('fromWeb') && $req->fromWeb ? base64_encode($mobileOtp) : $mobileOtp,
            ], 200);
        } catch (\Exception $e) {
            // 6. Generic Error Handling
            return response()->json([
                'message' => 'An error occurred: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function AstrologerVideo(Request $req)
    {
        try {
            $validator = Validator::make($req->all(), [
                'astrologerId' => 'required|exists:astrologers,id',
                'astro_video' => 'required|file|mimes:mp4,mov,avi,mpeg,mpg,mkv,webm,flv,3gp|max:51200',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 400,
                    'error' => $validator->errors(),
                ], 400);
            }

            $astrologer = Astrologer::find($req->astrologerId);
            if (!$astrologer) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Astrologer not found.',
                ], 404);
            }

            $user = User::find($astrologer->userId);
            if (!$user) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Associated user not found.',
                ], 404);
            }

            $oldPath = $astrologer->astro_video;
            $newPath = $oldPath;

            if ($req->hasFile('astro_video')) {
                $file = $req->file('astro_video');

                $maxSize = 50 * 1024 * 1024;
                if ($file->getSize() > $maxSize) {
                    return response()->json([
                        'status' => 400,
                        'message' => 'The uploaded file exceeds the maximum allowed size of 50 MB.',
                    ], 400);
                }

                $fileContent = file_get_contents($file->getRealPath());
                $extension = $file->getClientOriginalExtension();
                $time = \Carbon\Carbon::now()->timestamp;
                $fileName = 'astrovideo_' . $user->id . '_' . $time . '.' . $extension;

                try {
                    $newPath = StorageHelper::uploadToActiveStorage($fileContent, $fileName, 'astrologer_videos');
                } catch (\Exception $ex) {
                    return response()->json([
                        'status' => 500,
                        'message' => 'Video upload failed: ' . $ex->getMessage(),
                    ], 500);
                }
            }

            $astrologer->astro_video = $newPath;
            $astrologer->save();

            return response()->json([
                'status' => 200,
                'message' => 'Astrologer video updated successfully!',
                'data' => [
                    'astrologer_id' => $astrologer->id,
                    'video_url' => $newPath,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function setDiscountPrice(Request $request)
    {
        try {
            $validated = $request->validate([
                'astrologerId' => 'required|integer',
            ]);

            Astrologer::where('id', $request->astrologerId)->update([
                'chat_discount' => $request->chat_discount,
                'audio_discount' => $request->audio_discount,
                'video_discount' => $request->video_discount,
                'isDiscountedPrice' => $request->isDiscountedPrice,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
