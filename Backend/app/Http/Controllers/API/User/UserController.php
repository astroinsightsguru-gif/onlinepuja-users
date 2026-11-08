<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Models\AdminModel\DefaultProfile;
use App\Models\AstrologerModel\AstrologerGift;
use App\Models\ReferralSetting;
use App\Models\UserModel\CallRequest;
use App\Models\UserModel\ChatRequest;
use App\Models\UserModel\User;
use App\Models\UserModel\UserDeviceDetail;
use App\Models\UserModel\UserOrder;
use App\Models\UserModel\UserReport;
use App\Models\UserModel\UserRole;
use App\Models\UserModel\UserWallet;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Exception;
use App\Helpers\StorageHelper;
if (!defined('DESTINATIONPATH')) define('DESTINATIONPATH', 'public/storage/images/');

class UserController extends Controller
{

    public function __construct()
    {
        $this->middleware(
            'auth:api',
            [
                'except' => [
                    'loginUser',
                    'addUser',
                    'getUsers',
                    'profile',
                    'updateUser',
                    'refreshToken',
                    'forgotPassword',
                    'loginAppUser',
                ],
            ]
        );
    }



    //Add User

    public function addUser(Request $req)
    {
    try {
        DB::beginTransaction();

        $data = $req->only(
            'name',
            'contactNo',
            'email',
            'password',
            'birthDate',
            'birthTime',
            'profile',
            'birthPlace',
            'addressLine1',
            'location',
            'pincode',
            'gender',
            'countryCode'
        );

        //Validate the data
        $validator = Validator::make($data, [
            'contactNo' => 'required|max:10|unique:users,contactNo',
            'email' => 'unique:users,email',
        ]);

        if ($validator->fails()) {
            DB::rollback();
            return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
        }

        $countryCode = !empty($req->countryCode) ? $req->countryCode : '+91';
        if (!str_starts_with($countryCode, '+')) {
            $countryCode = '+' . $countryCode;
        }

        //Create a new user
        $user = User::create([
            'name' => $req->name,
            'contactNo' => $req->contactNo,
            'email' => $req->email,
            'password' => Hash::make($req->password),
            'birthDate' => $req->birthDate,
            'birthTime' => $req->birthTime,
            'birthPlace' => $req->birthPlace,
            'addressLine1' => $req->addressLine1,
            'location' => $req->location,
            'pincode' => $req->pincode,
            'gender' => $req->gender,
            'countryCode' => $countryCode,
            'country' => $countryCode == '+91' ? 'india' : $req->country,
        ]);

        // Handle profile image via StorageHelper
        $path = null;
        if ($req->profile) {
            if (Str::contains($req->profile, 'storage')) {
                $path = $req->profile;
            } else {
                $time = Carbon::now()->timestamp;
                $imageName = 'profile_' . $user->id . '_' . $time . '.png';
                try {
                    $path = StorageHelper::uploadToActiveStorage(base64_decode($req->profile), $imageName, 'profile');
                } catch (Exception $ex) {
                    DB::rollback();
                    return response()->json(['error' => $ex->getMessage(), 'status' => 500], 500);
                }
            }
        }

        $user->profile = $path;
        $user->update();

        UserRole::create([
            'userId' => $user->id,
            'roleId' => 3,
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'token_type' => 'Bearer',
            'status' => 200,
            'message' => 'User added successfully',
            'recordList' => ['id' => $user->id],
        ], 200);

    } catch (Exception $e) {
        DB::rollback();
        return response()->json([
            'error' => false,
            'message' => $e->getMessage(),
            'status' => 500,
        ], 500);
    }
    }



    //Get user details
    public function getUsers(Request $req)
{
    try {
        $user = DB::table('users')
            ->join('user_roles', 'user_roles.userId', '=', 'users.id')
            ->where('users.isActive', '=', true)
            ->where('users.isDelete', '=', false)
            ->where('user_roles.roleId', '=', 3)
            ->select('users.*', 'user_roles.roleId')
            ->orderBy('users.id', 'DESC');

        if ($req->searchString) {
            $user->whereRaw("users.name LIKE '%" . $req->searchString . "%' ");
        }

        if ($req->startIndex >= 0 && $req->fetchRecord) {
            $user->skip($req->startIndex);
            $user->limit($req->fetchRecord);
        }

        $userCount = DB::table('users')
            ->join('user_roles', 'user_roles.userId', '=', 'users.id')
            ->where('users.isActive', '=', true)
            ->where('users.isDelete', '=', false)
            ->where('user_roles.roleId', '=', 3);

        if ($req->searchString) {
            $userCount->whereRaw("users.name LIKE '%" . $req->searchString . "%' ");
        }

        $recordList = $user->get();

        // Convert profile field to full accessible URL
        foreach ($recordList as $u) {
            if (!empty($u->profile)) {
                if (Str::startsWith($u->profile, ['http://', 'https://'])) {
                    $u->profile = $u->profile;
                } else {
                    $u->profile = asset($u->profile);
                }
            } else {
                $u->profile = asset('default/profile.png');
            }
        }

        return response()->json([
            'recordList' => $recordList,
            'status' => 200,
            'totalRecords' => $userCount->count(),
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'error' => false,
            'message' => $e->getMessage(),
            'status' => 500,
        ], 500);
    }
}



    //Update user
    public function updateUser(Request $req, $id)
    {
    try {
        $user = User::find($id);

        if ($user) {
            $time = Carbon::now()->timestamp;

            // Handle profile image via StorageHelper
            $path = $user->profile;
            if ($req->profile) {
                if (Str::contains($req->profile, 'storage')) {
                    $path = $req->profile;
                } else {
                    $imageName = 'user_' . $user->id . '_' . $time . '.png';
                    try {
                        $path = StorageHelper::uploadToActiveStorage(base64_decode($req->profile), $imageName, 'profile');
                        File::delete($user->profile);
                    } catch (Exception $ex) {
                        return response()->json(['error' => $ex->getMessage(), 'status' => 500], 500);
                    }
                }
            }

            // For Web file upload
            if ($req->hasFile('profilepic')) {
                $file = $req->file('profilepic');
                $imageName = 'user_' . $user->id . '_' . $time . '.' . $file->getClientOriginalExtension();
                try {
                    $path = StorageHelper::uploadToActiveStorage(file_get_contents($file->getRealPath()), $imageName, 'profile');
                } catch (Exception $ex) {
                    return response()->json(['error' => $ex->getMessage(), 'status' => 500], 500);
                }
            }

            // Update user fields
            $user->name = $req->name ?? $user->name;
            $user->contactNo = $req->contactNo ?? $user->contactNo;
            $user->password = $req->password ? Hash::make($req->password) : $user->password;
            $user->birthDate = date('Y-m-d', strtotime($req->birthDate)) ?? $user->birthDate;
            $user->birthTime = date('H:i:s', strtotime($req->birthTime)) ?? $user->birthTime;
            $user->birthPlace = $req->birthPlace ?? $user->birthPlace;
            $user->addressLine1 = $req->addressLine1 ?? $user->addressLine1;
            $user->location = $req->location ?? $user->location;
            $user->pincode = $req->pincode ?? $user->pincode;
            $user->gender = $req->gender ?? $user->gender;
            $user->email = $req->email ?? $user->email;
            $user->countryCode = $req->countryCode ?? $user->countryCode;
            $user->profile = $path;
            $user->isProfileComplete = 1;

            $user->update();

            return response()->json([
                'message' => 'User updated successfully',
                'isProfileComplete' => $user->isProfileComplete,
                'status' => 200
            ], 200);

        } else {
            return response()->json(['message' => 'No user found', 'status' => 404], 404);
        }

    } catch (Exception $e) {
        return response()->json([
            'error' => false,
            'message' => $e->getMessage(),
            'status' => 500,
        ], 500);
    }
    }

    //Login admin
    public function loginUser(Request $req)
    {
        try {
            $data = $req->only('email', 'password');

            //Valid credential
            $validator = Validator::make($data, [
                'email' => 'required',
                'password' => 'required|string|min:6|max:50',
            ]);

            //Send failed response if request is not valid
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors(), 'status' => 400], 400);
            }

            //Create token
            try {
                if (!$token = Auth::guard('api')->attempt($data)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Login credentials are invalid.',
                    ], 400);
                }
            } catch (JWTException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not create token.',
                ], 500);
            }
            if ($token) {
                $data = array(
                    'token' => $token,
                    'expirationDate' => Carbon::now()->addMonth(),
                );
                DB::table('users')->where('email', '=', $req->email)->update($data);
            }
            //Json response
            return response()->json([
                'success' => true,
                'token' => $token,
                'token_type' => 'Bearer',
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

    //Generate token
    protected function respondWithToken($token)
    {
        try {
            return response()->json([
                'success' => true,
                'token' => $token,
                'token_type' => 'Bearer',
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

    public function getProfile(Request $req)
{
    try {
        // Authenticate user using API guard
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthorized',
                'status' => 401
            ], 401);
        }

        $id = $user->id;
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'error' => 'User not found',
                'status' => 404
            ], 404);
        }

        // Wallet info
        $userWallet = UserWallet::where('userId', $id)->first();
        $walletAmount = $userWallet ? $userWallet->amount : 0;

        // Currency conversion based on country code
        $user->totalWalletAmount = $user->countryCode === '+91'
            ? $walletAmount
            : (function_exists('convertinrtousd') ? convertinrtousd($walletAmount) : $walletAmount);

        // Profile image full URL
        if (!empty($user->profile)) {
            // Ensure image path exists in public directory
            $profilePath = str_replace('\\', '/', $user->profile);
            $user->profile = asset($profilePath);
        } else {
            $user->profile = asset('build/assets/images/person.png'); // default image
        }

        // Get system flags
        $systemFlag = DB::table('systemflag')->get();

        $modifiedSystemFlag = $systemFlag->map(function ($flag) use ($user) {
            // Currency symbol adjustment
            if ($flag->name === 'currencySymbol') {
                $flag->value = $user->countryCode === '+91' ? '₹' : '$';
            }

            // Make file URLs absolute
            if (isset($flag->valueType) && strtolower($flag->valueType) === 'file' && !empty($flag->value)) {
                $flag->value = asset(str_replace('\\', '/', $flag->value));
            }

            return $flag;
        });

        $user->systemFlag = $modifiedSystemFlag;

        return response()->json([
            'success' => true,
            'data' => $user,
            'status' => 200
        ], 200);

    } catch (\Exception $e) {
        // Log the actual error for debugging
        \Log::error('getProfile error: ' . $e->getMessage());

        return response()->json([
            'error' => true,
            'message' => $e->getMessage(),
            'status' => 500
        ], 500);
    }
}



    //Active/InActive user
    public function activeUser(Request $req, $id)
    {
        try {
            $user = User::find($id);
            if ($user) {
                $user->isActive = $req->isActive;
                $user->update();
                return response()->json([
                    'message' => 'User status change sucessfully',
                    'user' => $user,
                    'status' => 200,
                ], 200);
            } else {
                return response()->json(['message' => 'User status not change.', 'status' => 404], 404);
            }
        } catch (\Exception$e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    //Delete user
    public function deleteUser(Request $req)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            } else {
                $userId = Auth::guard('api')->user()->id;
            }
            $id = $req->id ? $req->id : $userId;
            error_log($req->id);
            $user = User::find($id);

            if ($user) {
                $user->delete();
                return response()->json(['message' => 'User delete Sucessfully', 'status' => 200], 200);
            } else {
                return response()->json(['message' => 'No user found', 'status' => 404], 404);
            }
        } catch (\Exception$e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    //Login customer

 public function loginAppUser(Request $req)
{
    try {
        $dummyPassword = 'dummy@123';
        $credentials = $req->only('contactNo','email');

        //Valid credential
        if($req->contactNo){
            $validator = Validator::make($credentials, [
                'contactNo' => 'required',
            ]);
        }elseif($req->email){
            $validator = Validator::make($credentials, [
                'email' => 'required',
            ]);
        }

        if($req->contactNo){
            $credentials = [
                'contactNo' => $req->contactNo,
                'password' => $dummyPassword,
            ];
            $id = DB::table('users')
                ->join('user_roles', 'users.id', '=', 'user_roles.userId')
                ->where('contactNo', '=', $req->contactNo)
                ->where('user_roles.roleId', '=', $req->roleId = 3)
                ->select('users.id')
                ->get();
            $usrCheckPass = DB::table('users')->where('contactNo',$req->contactNo)->first();
        }elseif($req->email){
            $credentials = [
                'email' => $req->email,
                'password' => $dummyPassword,
            ];
            $id = DB::table('users')
                ->join('user_roles', 'users.id', '=', 'user_roles.userId')
                ->where('email', '=', $req->email)
                ->where('user_roles.roleId', '=', $req->roleId = 3)
                ->select('users.id')
                ->get();
            $usrCheckPass = DB::table('users')->where('email',$req->email)->first();
        }

        if (count($id) > 0) {

            if ($validator->fails()) {
                return response()->json([
                    'error' => $validator->errors(),
                    'status' => 400,
                ], 400);
            }

            if(!$usrCheckPass->password){
                DB::table('users')->where('id',$usrCheckPass->id)->update(['password' => Hash::make($dummyPassword)]);
            }

            if (!$token = Auth::guard('api')->tokenById($usrCheckPass->id)) {
                return response()->json([
                    'error' => false,
                    'message' => 'Contact number is incorrect',
                    'status' => 401,
                    'recordList' => $id,
                ], 401);
            } else {
                if ($req->userDeviceDetails) {

                    if($req->contactNo){
                        $userDeviceDetail = DB::table('user_device_details')
                            ->join('users', 'users.id', '=', 'user_device_details.userId')
                            ->where('users.contactNo', '=', $req->contactNo)
                            ->select('user_device_details.*')
                            ->get();
                    }

                    if($req->email){
                        $userDeviceDetail = DB::table('user_device_details')
                            ->join('users', 'users.id', '=', 'user_device_details.userId')
                            ->where('users.email', '=', $req->email)
                            ->select('user_device_details.*')
                            ->get();
                    }

                    if ($userDeviceDetail->isEmpty()) {
                        $userDeviceDetail = UserDeviceDetail::create([
                            'userId' => $id[0]->id,
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
                        $userDeviceDetail = UserDeviceDetail::find($userDeviceDetail[0]->id);
                        if ($userDeviceDetail) {
                            $userDeviceDetail->appId = $req->userDeviceDetails['appId'];
                            $userDeviceDetail->deviceId = $req->userDeviceDetails['deviceId'];
                            $userDeviceDetail->fcmToken = $req->userDeviceDetails['fcmToken'];
                            $userDeviceDetail->deviceLocation = $req->userDeviceDetails['deviceLocation'];
                            $userDeviceDetail->deviceManufacturer = $req->userDeviceDetails['deviceManufacturer'];
                            $userDeviceDetail->deviceModel = $req->userDeviceDetails['deviceModel'];
                            $userDeviceDetail->appVersion = $req->userDeviceDetails['appVersion'];
                            $userDeviceDetail->subscription_id = $req->userDeviceDetails['subscription_id'];
                            $userDeviceDetail->updated_at = Carbon::now()->timestamp;
                            $userDeviceDetail->update();
                        }
                    }
                }
            }

            // ✅ Get default call time from systemflag table
            $defaultCallTime = DB::table('systemflag')->where('name', 'defaultcalltime')->value('value');

            // ✅ Go to token generation (added time in response)
            $response = $this->respondWithTokenApp($token, $id);
            $data = $response->getData(true);
            $data['defaultCallTime'] = (int) $defaultCallTime;

            return response()->json($data, 200);

        } else {
            return $this->addAppUser($req, $id);
        }

    } catch (\Exception $e) {
        return response()->json([
            'error' => false,
            'message' => $e->getMessage(),
            'status' => 500,
        ], 500);
    }
}



    //Generate token
    protected function respondWithTokenApp($token, $id)
    {
        try {
            $isFreeChat = DB::table('systemflag')->where('name', 'FirstFreeChat')->select('value')->first();
            $isFreeAvailable = true;
            $userId = null;
            if ($id instanceof \Illuminate\Support\Collection) {
                $userId = $id->first()->id ?? null;
            } elseif (is_array($id)) {
                $userId = isset($id[0]->id) ? $id[0]->id : ($id['id'] ?? null);
            } elseif (is_object($id)) {
                $userId = $id->id ?? null;
            } elseif (is_numeric($id)) {
                $userId = (int)$id;
            }
            if ($isFreeChat && $isFreeChat->value == 1) {
                if ($userId) {
                    $isChatRequest = DB::table('chatrequest')->where('userId', $userId)->where('chatStatus', '=', 'Completed')->first();
                    $isCallRequest = DB::table('callrequest')->where('userId', $userId)->where('callStatus', '=', 'Completed')->first();
                    if ($isChatRequest || $isCallRequest) {
                        $isFreeAvailable = false;
                    } else {
                        $isFreeAvailable = true;
                    }
                }
            } else {
                $isFreeAvailable = false;
            }

            // Hydrate full user details and wallet
            $userRow = null;
            if ($userId) {
                $userRow = DB::table('users')->where('id', $userId)->first();
                if ($userRow) {
                    unset($userRow->password);
                    $walletRow = DB::table('user_wallets')->where('userId', $userId)->first();
                    $userRow->walletAmount = $walletRow ? (float)$walletRow->amount : 0.0;
                    $userRow->totalWalletAmount = $userRow->walletAmount;
                    $userRow->wallet_amount = $userRow->walletAmount;
                }
            }

            return response()->json([
                'success' => true,
                'token' => $token,
                'token_type' => 'Bearer',
                'status' => 200,
                'is_freechat' => $isFreeAvailable,
                'recordList' => $userRow ? [$userRow] : ($id && isset($id[0]) ? $id : [['id' => $userId]]),
            ], 200);
        } catch (\Exception$e) {
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

            //Send failed response if request is not valid
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
                    'astromall_products.productImage', 'astromall_products.amount as productAmount',
                    'astromall_products.description', 'order_addresses.name as orderAddressName',
                    'order_addresses.phoneNumber', 'order_addresses.flatNo', 'order_addresses.locality',
                    'order_addresses.landmark', 'order_addresses.city', 'order_addresses.state',
                    'order_addresses.country', 'order_addresses.pincode', 'astromall_products.name as productName'
                );
                $orderRequest->orderBy('order_request.id', 'DESC');
                if ($req->startIndex && $req->fetchRecord) {
                    $orderRequest->skip($req->startIndex);
                    $orderRequest->take($req->fetchRecord);
                }
                $orderRequest->get();

                $giftList = AstrologerGift::join('gifts', 'gifts.id', 'astrologer_gifts.giftId')
                    ->join('astrologers as astro', 'astro.id', '=', 'astrologer_gifts.astrologerId')
                    ->where('astrologer_gifts.userId', '=', $req->userId);

                $giftListCount = $giftList->count();
                $giftList->select('gifts.name as giftName', 'astrologer_gifts.*', 'astro.id as astrologerId', 'astro.name as astrolgoerName', 'astro.contactNo');

                $giftList->orderBy('astrologer_gifts.id', 'DESC');
                if ($req->startIndex && $req->fetchRecord) {
                    $giftList->skip($req->startIndex);
                    $giftList->take($req->fetchRecord);
                }
                $giftList->get();

                $chatHistory = ChatRequest::join('astrologers as astro', 'astro.id', '=', 'chatrequest.astrologerId')
                    ->where('chatrequest.userId', '=', $req->userId)
                    ->where('chatrequest.chatStatus', '=', 'Completed');

                $chatHistoryCount = $chatHistory->count();
                $chatHistory->select('chatrequest.*', 'astro.id as astrologerId', 'astro.name as astrologerName', 'astro.contactNo', 'astro.profileImage', 'astro.charge');
                $chatHistory->orderBy('chatrequest.id', 'DESC');
                if ($req->startIndex && $req->fetchRecord) {
                    $chatHistory->skip($req->startIndex);
                    $chatHistory->take($req->fetchRecord);
                }
                $chatHistory->get();

                $callHistory = CallRequest::join('astrologers', 'astrologers.id', '=', 'callrequest.astrologerId')
                    ->where('callrequest.userId', '=', $req->userId)
                    ->where('callrequest.callStatus', '=', 'Completed');
                $callHistoryCount = $callHistory->count();
                $callHistory->select('callrequest.*', 'astrologers.id as astrologerId', 'astrologers.name as astrologerName', 'astrologers.contactNo', 'astrologers.profileImage', 'astrologers.charge');
                $callHistory->orderBy('callrequest.id', 'DESC');

                if ($req->startIndex && $req->fetchRecord) {
                    $callHistory->skip($req->startIndex);
                    $callHistory->take($req->fetchRecord);
                }
                $callHistory->get();

                $Pendingcall = CallRequest::join('astrologers', 'astrologers.id', '=', 'callrequest.astrologerId')
                    ->where('callrequest.userId', '=', $req->userId)
                    ->where('callrequest.callStatus', '=', 'Pending');
                $PendingcallCount = $Pendingcall->count();
                $Pendingcall->select('callrequest.*', 'astrologers.id as astrologerId', 'astrologers.name as astrologerName', 'astrologers.contactNo', 'astrologers.profileImage', 'astrologers.charge');
                $Pendingcall->orderBy('callrequest.id', 'DESC');

                if ($req->startIndex && $req->fetchRecord) {
                    $Pendingcall->skip($req->startIndex);
                    $Pendingcall->take($req->fetchRecord);
                }
                $Pendingcall->get();

                $reportHistory = UserReport::join('astrologers', 'astrologers.id', '=', 'user_reports.astrologerId')
                    ->join('report_types', 'report_types.id', '=', 'user_reports.reportType')
                    ->where('user_reports.userId', '=', $req->userId);

                $reportHistoryCount = $reportHistory->count();

                $reportHistory->select('user_reports.*', 'astrologers.id as astrologerId', 'astrologers.name as astrologerName', 'astrologers.contactNo', 'report_types.title', 'astrologers.profileImage', 'astrologers.charge');

                $reportHistory->orderBy('user_reports.id', 'DESC');
                if ($req->startIndex && $req->fetchRecord) {
                    $reportHistory->skip($req->startIndex);
                    $reportHistory->take($req->fetchRecord);
                }
                $reportHistory->get();

                $wallet = WalletTransaction::leftjoin('order_request', 'order_request.id', '=', 'wallettransaction.orderId')
                    ->leftjoin('astrologers', 'astrologers.id', '=', 'wallettransaction.astrologerId')
                    ->where('wallettransaction.userId', '=', $req->userId);
                $walletCount = $wallet->count();
                $wallet->select('wallettransaction.*', 'astrologers.name', 'order_request.totalMin');
                $wallet->orderBy('wallettransaction.id', 'DESC');
                if ($req->startIndex && $req->fetchRecord) {
                    $wallet->skip($req->startIndex);
                    $wallet->take($req->fetchRecord);
                }
                $wallet->get();



                $payment = DB::table('payment')
                    ->where('userId', '=', $req->userId)
                    ->orderBy('id', 'DESC');
                $paymentCount = $payment->count();
                if ($req->startIndex && $req->fetchRecord) {
                    $payment->skip($req->startIndex);
                    $payment->take($req->fetchRecord);
                }
                $payment->get();

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
                $Pendingcalls = array(
                    'totalCount' => $PendingcallCount,
                    'Pendingcall' => $Pendingcall,
                );
                $callHistorys = array(
                    'totalCount' => $callHistoryCount,
                    'callHistory' => $callHistory,
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

                $user[0]->follower = $follower;
                $user[0]->orders = $orderRequests;
                $user[0]->sendGifts = $giftLists;
                $user[0]->chatRequest = $chatHistorys;
                $user[0]->callRequest = $callHistorys;
                $user[0]->reportRequest = $reportHistorys;
                $user[0]->walletTransaction = $wallets;
                $user[0]->paymentLogs = $payments;
                $user[0]->Pendingcall = $Pendingcalls;

                return response()->json([
                    "message" => "Get User Successfully",
                    "status" => 200,
                    "recordList" => $user,
                ], 200);

            }
        } catch (\Exception$e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }


    public function addAppUser(Request $req)
    {

        try {
            DB::beginTransaction();

            // Determine which field to validate
            if ($req->contactNo) {
                $data = $req->only('contactNo');
                $validator = Validator::make($data, [
                    'contactNo' => 'required|max:10|unique:users,contactNo',
                ]);
                $credentials = [
                   'contactNo' => $req->contactNo,
                   'password' => 'dummy@123',
                ];
            } else {
                $data = $req->only('email');
                $validator = Validator::make($data, [
                    'email' => 'required|email|unique:users,email',
                ]);
                   $credentials = [
                   'email' => $req->email,
                   'password' => 'dummy@123',
                ];
            }

            if ($validator->fails()) {
                DB::rollback();
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }

            $referralToken = $req->referral_token ?? '0';
            $referrer = User::where('referral_token', $referralToken)->first();

            $countryCode = !empty($req->countryCode) ? $req->countryCode : '+91';

            if (!str_starts_with($countryCode, '+')) {
                $countryCode = '+' . $countryCode;
            }

            // Create new user
            $user = User::create([
                'name' => $req->name ?? '',
                'contactNo' => $req->contactNo,
                'email' => $req->email ?? '',
                'password' => Hash::make('dummy@123'),
                'birthDate' => $req->birthDate,
                'birthTime' => $req->birthTime,
                'birthPlace' => $req->birthPlace,
                'addressLine1' => $req->addressLine1,
                'location' => $req->location,
                'pincode' => $req->pincode,
                'gender' => $req->gender,
                'countryCode' => $countryCode,
                'country' => $countryCode == '+91' ? 'india' : $req->country,
                'referral_token' => '',
                'referrer_id' => $referrer ? $referrer->id : 0,
            ]);

            // Handle profile image via StorageHelper
            $path = null;
            if ($req->profile) {
                if (Str::contains($req->profile, 'storage')) {
                    $path = $req->profile;
                } else {
                    $time = Carbon::now()->timestamp;
                    $imageName = 'user_' . $user->id . '_' . $time . '.png';
                    try {
                        $path = StorageHelper::uploadToActiveStorage(base64_decode($req->profile), $imageName, 'profile');
                    } catch (Exception $ex) {
                        DB::rollback();
                        return response()->json(['error' => $ex->getMessage(), 'status' => 500], 500);
                    }
                }
            }

            $user->profile = $path;
            $user->update();

            // Generate referral token
            $referral_token = "REF" . numberToCharacterString($user->id);
            $user->update(['referral_token' => $referral_token]);

            // Assign role
            UserRole::create([
                'userId' => $user->id,
                'roleId' => 3,
            ]);

            // Handle device details
            if ($req->userDeviceDetails && $req->userDeviceDetails['fcmToken']) {
                UserDeviceDetail::create([
                    'userId' => $user->id,
                    'appId' => 1,
                    'deviceId' => $req->userDeviceDetails['deviceId'] ?? '',
                    'fcmToken' => $req->userDeviceDetails['fcmToken'] ?? '',
                    'deviceLocation' => $req->userDeviceDetails['deviceLocation'] ?? '',
                    'deviceManufacturer' => $req->userDeviceDetails['deviceManufacturer'] ?? '',
                    'deviceModel' => $req->userDeviceDetails['deviceModel'] ?? '',
                    'appVersion' => $req->userDeviceDetails['appVersion'] ?? '',
                    'subscription_id' => $req->userDeviceDetails['subscription_id'] ?? '',
                ]);
            } else {
                UserDeviceDetail::create([
                    'userId' => $user->id,
                    'appId' => 1,
                ]);
            }

            // Handle referral wallet logic
            $referral_setting = ReferralSetting::first();
            $referrer_count = User::where('referrer_id', $referrer ? $referrer->id : 0)->count();
            $inr_usd_conv_rate = DB::table('systemflag')->where('name', 'UsdtoInr')->select('value')->first();

            if ($referral_setting && $referrer && $referrer_count < $referral_setting->max_user_limit) {
                $user_country = User::where('id', $referrer->id)->where('countryCode', '+91')->first();
                $referral_setting->amount = $user_country ? $referral_setting->amount : convertusdtoinr($referral_setting->amount);

                $wallet = DB::table('user_wallets')->where('userId', $referrer->id)->get();
                $wallets = [
                    'userId' => $referrer->id,
                    'amount' => $wallet && count($wallet) > 0 ? $wallet[0]->amount + $referral_setting->amount : $referral_setting->amount,
                    'createdBy' => $referrer->id,
                    'modifiedBy' => $referrer->id,
                ];

                if ($wallet && count($wallet) > 0) {
                    DB::table('user_wallets')->where('id', $wallet[0]->id)->update($wallets);
                } else {
                    DB::table('user_wallets')->insert($wallets);
                }

                $convRateVal = ($inr_usd_conv_rate && !empty($inr_usd_conv_rate->value)) ? $inr_usd_conv_rate->value : 83.5;
                DB::table('wallettransaction')->insert([
                    'userId' => $referrer->id,
                    'amount' => $referral_setting->amount,
                    'isCredit' => true,
                    'transactionType' => 'Referral',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'inr_usd_conversion_rate' => $convRateVal,
                ]);
            }

            // Create token
            try {
                if (!$token = Auth::guard('api')->attempt($credentials)) {
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'Login credentials are invalid.',
                        'data' => $data,
                    ], 400);
                }
            } catch (JWTException $e) {
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Could not create token.',
                ], 500);
            }

            DB::commit();

            $userRow = DB::table('users')->where('id', $user->id)->first();
            if ($userRow) {
                unset($userRow->password);
                $walletRow = DB::table('user_wallets')->where('userId', $user->id)->first();
                $userRow->walletAmount = $walletRow ? (float)$walletRow->amount : 0.0;
                $userRow->totalWalletAmount = $userRow->walletAmount;
                $userRow->wallet_amount = $userRow->walletAmount;
            }

            return response()->json([
                'success' => true,
                'token' => $token,
                'token_type' => 'Bearer',
                'status' => 200,
                'message' => 'User added successfully',
                'recordList' => $userRow ? [$userRow] : [['id' => $user->id]],
                'is_freechat' => true,
            ], 200);

        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }


    public function logout(Request $req)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            }
            $id=Auth::guard('api')->user()->id;
            $userDeviceDetail = UserDeviceDetail::where('userId', $id)->first();
            if ($userDeviceDetail) {
                $userDeviceDetail->subscription_id = null;
                $userDeviceDetail->fcmToken = null;
                $userDeviceDetail->updated_at = Carbon::now()->timestamp;
                $userDeviceDetail->update();
            }

            return response()->json([
                "message" => "Logout User Successfully",
                "status" => 200,
                "recordList" => [],
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function updateUserProfile(Request $req)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            } else {
                $id = Auth::guard('api')->user()->id;
            }
            // Handle profile upload
            $path = null;
            $time = Carbon::now()->timestamp;

            if ($req->hasFile('profile')) {
                $imageContent = file_get_contents($req->file('profile')->getRealPath());
                $extension = $req->file('profile')->getClientOriginalExtension() ?? 'png';
                $imageName = 'user_' . $user->id . '_' . $time . '.' . $extension;

                try {
                    // Upload to active storage (local / external)
                    $path = StorageHelper::uploadToActiveStorage($imageContent, $imageName, 'profile');
                } catch (Exception $ex) {
                    return response()->json(['error' => $ex->getMessage()]);
                }
            }
            $data = array(
                'profile' => $path,
            );
            DB::table('users')->where('id', '=', $id)->update($data);
            return response()->json([
                'status' => 200,
                "message" => "Update Profile Successfully",
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function addDeviceDetails(Request $req)
    {
        try {
            $data = $req->only(
                'userId',
                'appId',
                'deviceId',
                'fcmToken',
                'deviceLocation',
                'deviceManufacturer',
                'deviceModel',
                'appVersion',
                'subscription_id',
                'subscription_id_web'
            );

            $validator = Validator::make($data, [
                'userId' => 'required',
                'deviceId' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }

            $userDeviceDetail = UserDeviceDetail::where('userId', $req->userId)->first();

            if ($userDeviceDetail) {
                $userDeviceDetail->appId = $req->appId ?? $userDeviceDetail->appId;
                $userDeviceDetail->fcmToken = $req->fcmToken ?? $userDeviceDetail->fcmToken;
                $userDeviceDetail->deviceLocation = $req->deviceLocation ?? $userDeviceDetail->deviceLocation;
                $userDeviceDetail->deviceManufacturer = $req->deviceManufacturer ?? $userDeviceDetail->deviceManufacturer;
                $userDeviceDetail->deviceModel = $req->deviceModel ?? $userDeviceDetail->deviceModel;
                $userDeviceDetail->appVersion = $req->appVersion ?? $userDeviceDetail->appVersion;
                $userDeviceDetail->subscription_id = $req->subscription_id ?? $userDeviceDetail->subscription_id;
                $userDeviceDetail->subscription_id_web = $req->subscription_id_web ?? $userDeviceDetail->subscription_id_web;
                $userDeviceDetail->updated_at = Carbon::now();
                $userDeviceDetail->update();
                $message = 'Device details updated successfully';
            } else {
                $userDeviceDetail = UserDeviceDetail::create([
                    'userId' => $req->userId,
                    'appId' => $req->appId ?? 1,
                    'deviceId' => $req->deviceId,
                    'fcmToken' => $req->fcmToken,
                    'deviceLocation' => $req->deviceLocation,
                    'deviceManufacturer' => $req->deviceManufacturer,
                    'deviceModel' => $req->deviceModel,
                    'appVersion' => $req->appVersion,
                    'subscription_id' => $req->subscription_id,
                    'subscription_id_web' => $req->subscription_id_web,
                ]);
                $message = 'Device details added successfully';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => 200,
                'recordList' => $userDeviceDetail,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }


}
