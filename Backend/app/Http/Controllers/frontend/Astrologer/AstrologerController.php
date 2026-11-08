<?php

namespace App\Http\Controllers\frontend\Astrologer;

use App\Http\Controllers\Controller;
use App\Models\AdminModel\DegreeOrDiploma;
use App\Models\AdminModel\FulltimeJob;
use App\Models\AdminModel\HighestQualification;
use App\Models\AdminModel\MainSourceOfBusiness;
use App\Models\AdminModel\SystemFlag;
use App\Models\AdminModel\TravelCountry;
use App\Models\AdminModel\User;
use App\Models\AstrologerModel\Astrologer;
use App\Models\AstrologerModel\AstrologerAvailability;
use App\Models\UserModel\AstromallProduct;
use App\Models\UserModel\UserDeviceDetail;
use App\Models\UserModel\UserWallet;
use App\Models\AstrologerCategory;
use App\Models\AstrologerDocument;
use App\Models\Country;
use App\Models\Puja;
use App\Models\Skill;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Session\Session;

class AstrologerController extends Controller
{
    public function AstrologerAccount()
    {
        if (!astroauthcheck())
            return redirect()->route('front.astrologerlogin');

        $session = new Session();
        $token = $session->get('astrotoken');

        $getAstrologer = app(\App\Http\Controllers\API\Astrologer\AstrologerController::class)->getAstrologerById(new Request(['astrologerId' => astroauthcheck()['astrologerId'], 'token' => $token]))->getData(true);
        $user = DB::table('users')->where('id', '=', $getAstrologer['recordList'][0]['userId'])->first();

        $categories = AstrologerCategory::all();
        $skills = Skill::all();
        $country = Country::all();

        $languages = DB::table('languages')->get();
        $mainSourceBusiness = MainSourceOfBusiness::query()->get();
        $highestQualification = HighestQualification::query()->get();
        $qualifications = DegreeOrDiploma::query()->get();
        $jobs = FulltimeJob::query()->get();
        $countryTravel = TravelCountry::query()->get();
        $getsystemflag = collect(DB::table('systemflag')->get()->map(function($f) { return (array)$f; }));
        $documents = AstrologerDocument::query()->get();
        $getsystemflag = collect($getsystemflag['recordList']);
        $appname = $getsystemflag->where('name', 'AppName')->first();

        return view('frontend.astrologers.pages.profileupdate', compact('getAstrologer', 'documents', 'user', 'categories', 'skills', 'languages', 'mainSourceBusiness', 'highestQualification', 'qualifications', 'jobs', 'countryTravel', 'getsystemflag', 'appname', 'country'));
    }

    // ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------

    public function updateAstrologer(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = User::find($request->userId);
            $validator = Validator::make($request->all(), [
                'contactNo' => 'required|unique:users,contactNo,' . $user->id,
                'email' => 'required|email|unique:users,email,' . $user->id,
                'dailyContribution' => 'required',
                'languageKnown' => 'required',
                'primarySkill' => 'required',
                'allSkill' => 'required',
                'mainSourceOfBusiness' => 'required',
                'minimumEarning' => 'required',
                'maximumEarning' => 'required',
                'whyOnBoard' => 'required',
                'whatsappNo' => 'required',
                'aadharNo' => 'required',
                'pancardNo' => 'required',
                'ifscCode' => 'required',
                'bankBranch' => 'required',
                'bankName' => 'required',
                'accountNumber' => 'required',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            if (request('profileImage')) {
                // Encode the new profile image
                $profileImage = base64_encode(file_get_contents($request->file('profileImage')));
            } else {
                // No new profile image provided
                $profileImage = null;
            }

            if ($profileImage) {
                if (Str::contains($profileImage, 'storage')) {
                    $path = $profileImage;
                } else {
                    $time = Carbon::now()->timestamp;
                    $destinationPath = 'public/storage/images/';
                    $imageName = 'astrologer_' . $request->id . '_' . $time;
                    $path = $destinationPath . $imageName . '.png';

                    $isFile = explode('.', $path);
                    if (!(file_exists($path) && count($isFile) > 1)) {
                        file_put_contents($path, base64_decode($profileImage));
                    }

                    if (request('oldProfileImage')) {
                        $oldImagePath = request('oldProfileImage');
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                }
            } else {
                $path = request('oldProfileImage');
            }
            $countryCode = !empty($req->countryCode) ? $req->countryCode : '+91';

            if (!str_starts_with($countryCode, '+')) {
                $countryCode = '+' . $countryCode;
            }

            if ($user) {
                $user->name = $request->name;
                $user->contactNo = $request->contactNo;
                $user->email = $request->email;
                $user->birthDate = $request->birthDate;
                $user->profile = $path;
                $user->gender = $request->gender;
                $user->location = $request->currentCity;
                $user->countryCode = $countryCode;
                // $user->country = $request->country;
                $user->update();
            }

            $slug = Str::slug($request->name, '-');
            $originalSlug = $slug;
            $counter = 1;
            while (DB::table('astrologers')->where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            $astrologer = Astrologer::find($request->id);
            if ($astrologer) {
                $astrologer->userId = $request->userId;
                $astrologer->primarySkill = implode(',', ($request->primarySkill));
                $astrologer->allSkill = implode(',', $request->allSkill);
                $astrologer->languageKnown = implode(',', $request->languageKnown);
                $astrologer->profileImage = $path;
                $astrologer->experienceInYears = $request->experienceInYears;
                $astrologer->name = $request->name;
                $astrologer->slug = $slug;
                $astrologer->dailyContribution = $request->dailyContribution;
                $astrologer->hearAboutAstroguru = $request->hearAboutAstroguru;
                $astrologer->isWorkingOnAnotherPlatform = $request->isWorkingOnAnotherPlatform;
                $astrologer->whyOnBoard = $request->whyOnBoard;
                $astrologer->currentCity = $request->currentCity;
                $astrologer->mainSourceOfBusiness = $request->mainSourceOfBusiness;
                $astrologer->college = $request->college;
                $astrologer->learnAstrology = $request->learnAstrology;
                $astrologer->astrologerCategoryId = implode(',', $request->astrologerCategoryId);
                $astrologer->instaProfileLink = $request->instaProfileLink;
                $astrologer->linkedInProfileLink = $request->linkedInProfileLink;
                $astrologer->facebookProfileLink = $request->facebookProfileLink;
                $astrologer->websiteProfileLink = $request->websiteProfileLink;
                $astrologer->youtubeChannelLink = $request->youtubeChannelLink;
                $astrologer->isAnyBodyRefer = $request->isAnyBodyRefer;
                $astrologer->minimumEarning = $request->minimumEarning;
                $astrologer->maximumEarning = $request->maximumEarning;
                $astrologer->loginBio = $request->loginBio;
                $astrologer->NoofforeignCountriesTravel = $request->NoofforeignCountriesTravel;
                $astrologer->currentlyworkingfulltimejob = $request->currentlyworkingfulltimejob;
                $astrologer->goodQuality = $request->goodQuality;
                $astrologer->biggestChallenge = $request->biggestChallenge;
                $astrologer->whatwillDo = $request->whatwillDo;
                $astrologer->nameofplateform = $request->nameofplateform;
                $astrologer->monthlyEarning = $request->monthlyEarning;
                $astrologer->referedPerson = $request->referedPerson;
                $astrologer->charge = $request->charge;
                $astrologer->charge_usd = $request->charge_usd;
                $astrologer->videoCallRate_usd = $request->videoCallRate_usd;
                $astrologer->reportRate_usd = $request->reportRate_usd;
                $astrologer->videoCallRate = $request->videoCallRate;
                $astrologer->reportRate = $request->reportRate;

                // $astrologer->country = $request->country;
                $astrologer->whatsappNo = $request->whatsappNo;
                $astrologer->aadharNo = $request->aadharNo;
                $astrologer->pancardNo = $request->pancardNo;
                $astrologer->ifscCode = $request->ifscCode;
                $astrologer->bankBranch = $request->bankBranch;
                $astrologer->bankName = $request->bankName;
                $astrologer->accountNumber = $request->accountNumber;
                $astrologer->accountType = $request->accountType;
                $astrologer->upi = $request->upi;
                $astrologer->accountHolderName = $request->accountHolderName;

                $documents = AstrologerDocument::all();
                foreach ($documents as $document) {
                    $inputName = Str::snake($document->name);

                    // Check if column exists, create if not
                    if (!Schema::hasColumn('astrologers', $inputName)) {
                        Schema::table('astrologers', function (Blueprint $table) use ($inputName) {
                            $table->string($inputName)->nullable();
                        });
                    }

                    if ($request->hasFile($inputName)) {
                        $docImage = base64_encode(file_get_contents($request->file($inputName)));
                        $time = Carbon::now()->timestamp;
                        $destinationpath = 'public/storage/images/documents/';
                        $imageName = $inputName . '_' . $astrologer->id . $time;
                        $docPath = $destinationpath . $imageName . '.png';
                        file_put_contents($docPath, base64_decode($docImage));
                        $astrologer->$inputName = $docPath;
                    } elseif (!$request->hasFile($inputName) && $astrologer->$inputName) {
                        // Keep existing document if no new file uploaded
                        $astrologer->$inputName = $astrologer->$inputName;
                    } else {
                        $astrologer->$inputName = null;
                    }
                }

                $astrologer->update();
                if ($request->userDeviceDetails) {
                    $userDeviceDetails = UserDeviceDetail::find($request->userId);
                    if ($userDeviceDetails) {
                        $userDeviceDetails->userId = $user->id;
                        $userDeviceDetails->appId = $request->appId;
                        $userDeviceDetails->deviceId = $request->deviceId;
                        $userDeviceDetails->fcmToken = $request->fcmToken;
                        $userDeviceDetails->deviceLocation = $request->deviceLocation;
                        $userDeviceDetails->deviceManufacturer = $request->deviceManufacturer;
                        $userDeviceDetails->deviceModel = $request->deviceModel;
                        $userDeviceDetails->appVersion = $request->appVersion;
                        $userDeviceDetails->update();
                    } else {
                        $userDeviceDetails = UserDeviceDetail::create([
                            'userId' => $request->userId,
                            'appId' => $request->appId,
                            'deviceId' => $request->deviceId,
                            'fcmToken' => $request->fcmToken,
                            'deviceLocation' => $request->deviceLocation,
                            'deviceManufacturer' => $request->deviceManufacturer,
                            'deviceModel' => $request->deviceModel,
                            'appVersion' => $request->appVersion,
                        ]);
                    }
                }
                if ($request->astrologerAvailability) {
                    $availability = DB::Table('astrologer_availabilities')
                        ->where('astrologerId', '=', $request->id)
                        ->delete();
                    $request->astrologerId = $astrologer->id;
                    foreach ($request->astrologerAvailability as $astrologeravailable) {
                        foreach ($astrologeravailable['time'] as $availability) {
                            AstrologerAvailability::create([
                                'astrologerId' => $request->id,
                                'day' => $astrologeravailable['day'],
                                'fromTime' => $availability['fromTime'],
                                'toTime' => $availability['toTime'],
                                'createdBy' => $astrologer['id'],
                                'modifiedBy' => $astrologer['id'],
                            ]);
                        }
                    }
                }
                $astrologer->astrologerAvailability = $request->astrologerAvailability;

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
                DB::commit();
                return redirect()->back()->with('success', 'Your Profile Update Successfully !');
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    // -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

    public function getAstrologerWallet(Request $request)
    {
        // Artisan::call('cache:clear'); // Removed for performance
        if (!astroauthcheck())
            return redirect()->route('front.astrologerindex');
        $session = new Session();
        $token = $session->get('astrotoken');
        $getAstrologer = app(\App\Http\Controllers\API\Astrologer\AstrologerController::class)->getAstrologerById(new Request(['astrologerId' => astroauthcheck()['astrologerId'], 'token' => $token]))->getData(true);
        $astroUser = DB::table('users')->where('id', astroauthcheck()['id'] ?? 0)->first();
        $getProfile = ['status' => 200, 'recordList' => $astroUser ? (array) $astroUser : [], 'data' => $astroUser ? (array) $astroUser : []];
        $withdrawlrequest = app(\App\Http\Controllers\API\Astrologer\WithDrawController::class)->getWithdrawRequest(new Request(['astrologerId' => astroauthcheck()['astrologerId'], 'token' => $token]))->getData(true);

        $withdrawMethod = DB::table('withdrawmethods')->where('isActive', 1)->get();

        $getsystemflag = SystemFlag::all();
        $currency = $getsystemflag->where('name', 'currencySymbol')->first();
        return view('frontend.astrologers.pages.astrologer-wallet', [
            'getAstrologer' => $getAstrologer,
            'getProfile' => $getProfile,
            'currency' => $currency,
            'withdrawMethod' => $withdrawMethod,
            'withdrawlrequest' => $withdrawlrequest,
        ]);
    }

    // --------------------------------------------------------------------------------------------------------------------------------------------------------------
    public function AstrologerWalletRecharge(Request $request)
    {
        // Artisan::call('cache:clear'); // Removed for performance
        if (!astroauthcheck())
            return redirect()->route('front.astrologerindex');
        $session = new Session();
        $token = $session->get('astrotoken');
        $getRechargeAmount = app(\App\Http\Controllers\API\User\WalletController::class)->getRechargeAmount(new Request(['token' => $token]))->getData(true);
        $getsystemflag = SystemFlag::all();
        $gstvalue = $getsystemflag->where('name', 'Gst')->first();
        $currency = $getsystemflag->where('name', 'currencySymbol')->first();
        $getamount = collect($getRechargeAmount['recordList']);
        $selectedamount = $getamount->first();
        $astroUser = DB::table('users')->where('id', astroauthcheck()['id'] ?? 0)->first();
        $getProfile = ['status' => 200, 'recordList' => $astroUser ? (array) $astroUser : [], 'data' => $astroUser ? (array) $astroUser : []];
        return view('frontend.astrologers.pages.astrologer-wallet-recharge', [
            'getRechargeAmount' => $getRechargeAmount,
            'gstvalue' => $gstvalue,
            'currency' => $currency,
            'selectedamount' => $selectedamount,
            'getProfile' => $getProfile,
        ]);
    }

    // ----------------------------------------------------------------------------------------------------------------------------------------------------------
    public function getAstrologerChat(Request $request)
    {
        // Artisan::call('cache:clear'); // Removed for performance
        if (!astroauthcheck())
            return redirect()->route('front.astrologerindex');
        $session = new Session();
        $token = $session->get('astrotoken');
        $getAstrologerChat = app(\App\Http\Controllers\API\Astrologer\AstrologerController::class)->getAstrologerById(new Request(['astrologerId' => astroauthcheck()['astrologerId'], 'token' => $token]))->getData(true);
        // dd($getAstrologerChat);
        $astroUser = DB::table('users')->where('id', astroauthcheck()['id'] ?? 0)->first();
        $getProfile = ['status' => 200, 'recordList' => $astroUser ? (array) $astroUser : [], 'data' => $astroUser ? (array) $astroUser : []];

        $pujas = Puja::where('astrologerId', astroauthcheck()['astrologerId'])
            ->where('created_by', 'astrologer')
            ->where('puja_start_datetime', '>', Carbon::now())
            ->select('id', 'puja_title', 'puja_place', 'puja_price', 'long_description', 'puja_start_datetime', 'puja_end_datetime', 'puja_images')
            ->get();
        $astrologerId = astroauthcheck()['astrologerId'];

        $currentDatetime = \Carbon\Carbon::now();
        $pujas = Puja::where(function ($query) use ($astrologerId, $currentDatetime) {
            $query
                ->where(function ($q) use ($astrologerId, $currentDatetime) {
                    $q
                        ->where('astrologerId', $astrologerId)
                        ->where('puja_status', 1)
                        ->where('puja_start_datetime', '>', $currentDatetime);
                })
                ->orWhere(function ($q) use ($currentDatetime) {
                    $q
                        ->where('created_by', 'admin')
                        ->where('puja_status', 1)
                        ->where('puja_start_datetime', '>', $currentDatetime);
                });
        })
            ->select(
                'id',
                'puja_title',
                'puja_place',
                'puja_price',
                'long_description',
                'puja_start_datetime',
                'puja_end_datetime',
                'puja_images'
            )
            ->get();

        $getsystemflag = SystemFlag::all();
        $currency = $getsystemflag->where('name', 'currencySymbol')->first();
        return view('frontend.astrologers.pages.astrologer-chats', [
            'getAstrologerChat' => $getAstrologerChat,
            'getProfile' => $getProfile,
            'currency' => $currency,
            'pujas' => $pujas
        ]);
    }

    // ----------------------------------------------------------------------------------------------------------------------------------------------------------
    public function getAstrologerCall(Request $request)
    {
        // Artisan::call('cache:clear'); // Removed for performance
        if (!astroauthcheck())
            return redirect()->route('front.astrologerindex');
        $session = new Session();
        $token = $session->get('astrotoken');
        $getAstrologerCall = app(\App\Http\Controllers\API\Astrologer\AstrologerController::class)->getAstrologerById(new Request(['astrologerId' => astroauthcheck()['astrologerId'], 'token' => $token]))->getData(true);
        $astroUser = DB::table('users')->where('id', astroauthcheck()['id'] ?? 0)->first();
        $getProfile = ['status' => 200, 'recordList' => $astroUser ? (array) $astroUser : [], 'data' => $astroUser ? (array) $astroUser : []];
        $pujas = Puja::where('astrologerId', astroauthcheck()['astrologerId'])
            ->where('created_by', 'astrologer')
            ->where('puja_start_datetime', '>', Carbon::now())
            ->select('id', 'puja_title', 'puja_place', 'puja_price', 'long_description', 'puja_start_datetime', 'puja_end_datetime', 'puja_images')
            ->get();
        $getsystemflag = SystemFlag::all();
        $currency = $getsystemflag->where('name', 'currencySymbol')->first();
        return view('frontend.astrologers.pages.astrologer-calls', [
            'getAstrologerCall' => $getAstrologerCall,
            'getProfile' => $getProfile,
            'currency' => $currency,
            'pujas' => $pujas
        ]);
    }

    // ----------------------------------------------------------------------------------------------------------------------------------------------------------
    public function getAstrologerReport(Request $request)
    {
        // Artisan::call('cache:clear'); // Removed for performance
        if (!astroauthcheck())
            return redirect()->route('front.astrologerindex');
        $session = new Session();
        $token = $session->get('astrotoken');
        $getAstrologerReport = app(\App\Http\Controllers\API\Astrologer\AstrologerController::class)->getAstrologerById(new Request(['astrologerId' => astroauthcheck()['astrologerId'], 'token' => $token]))->getData(true);
        $astroUser = DB::table('users')->where('id', astroauthcheck()['id'] ?? 0)->first();
        $getProfile = ['status' => 200, 'recordList' => $astroUser ? (array) $astroUser : [], 'data' => $astroUser ? (array) $astroUser : []];
        $getsystemflag = SystemFlag::all();
        $currency = $getsystemflag->where('name', 'currencySymbol')->first();
        return view('frontend.astrologers.pages.astrologer-reports', [
            'getAstrologerReport' => $getAstrologerReport,
            'getProfile' => $getProfile,
            'currency' => $currency,
        ]);
    }

    // --------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    public function LiveAstrologers(Request $request)
    {
        // Artisan::call('cache:clear'); // Removed for performance
        $liveAstrologer = DB::table('liveastro')
            ->join('astrologers', 'astrologers.id', '=', 'liveastro.astrologerId')
            ->where('liveastro.isActive', '=', true)
            ->select('astrologers.name', 'astrologers.profileImage', 'liveastro.*', 'astrologers.charge', 'astrologers.videoCallRate')
            ->orderBy('id', 'DESC')
            ->where('liveastro.astrologerId', astroauthcheck()['astrologerId'])
            ->first();
        if (!$liveAstrologer)
            return redirect()->route('front.astrologerindex');
        $wallet_amount = '';
        if (astroauthcheck())
            $userWalletAmount = UserWallet::where('userId', astroauthcheck()['id'] ?? 0)->value('amount') ?? 0;
        $wallet_amount = $userWalletAmount;
        $getGift = app(\App\Http\Controllers\API\User\GiftController::class)->getGift(new Request())->getData(true);
        $agoraAppIdValue = DB::table('systemflag')
            ->where('name', 'AgoraAppId')
            ->select('value')
            ->first();
        $agorcertificateValue = DB::table('systemflag')
            ->where('name', 'AgoraAppCertificate')
            ->select('value')
            ->first();
        $RtmToken = app(\App\Http\Controllers\API\User\TokenGeneratorController::class)->generateToken(new Request(['appID' => $agoraAppIdValue->value ?? '', 'appCertificate' => $agorcertificateValue->value ?? '', 'user' => 'liveAstrologer_' . (astroauthcheck()['id'] ?? 0), 'channelName' => $liveAstrologer->channelName]))->getData(true);
        $getsystemflag = SystemFlag::all();
        $currency = $getsystemflag->where('name', 'currencySymbol')->first();

        $astromallProduct = AstromallProduct::query();
        $astromallProduct->where('isActive', '=', true);
        $astromallProduct->where('isDelete', '=', false);
        if ($s = $request->input(key: 's')) {
            $astromallProduct->whereRaw(sql: "name LIKE '%" . $s . "%' ");
        }
        $astromallProduct = $astromallProduct->get();

        $getsystemflag = DB::table('systemflag')
            ->get();

        return view('frontend.astrologers.pages.live-astrologer', [
            'liveAstrologer' => $liveAstrologer,
            'wallet_amount' => $wallet_amount,
            'getGift' => $getGift,
            'agoraAppIdValue' => $agoraAppIdValue->value,
            'currency' => $currency,
            'RtmToken' => $RtmToken,
            'astromallProduct' => $astromallProduct
        ]);
    }


    #--------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    public function getSupportTickets(Request $request)
    {
        if (!astroauthcheck())
            return redirect()->route('front.astrologerlogin');

        $user = astroauthcheck();

        $tickets = DB::table('tickets')
            ->leftJoin('help_supports', 'help_supports.id', '=', 'tickets.helpSupportId')
            ->where('tickets.userId', '=', $user->id)
            ->where('tickets.sender_type', '=', 'Astrologer')
            ->select(
                'tickets.*',
                'help_supports.name as categoryName'
            )
            ->orderBy('tickets.id', 'DESC')
            ->paginate(10);

        return view('frontend.astrologers.pages.support-tickets', compact('tickets'));
    }

    #--------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    public function createSupportTicket(Request $request)
    {
        if (!astroauthcheck())
            return redirect()->route('front.astrologerlogin');

        $categories = DB::table('help_supports')->where('isActive', 1)->get();

        return view('frontend.astrologers.pages.support-ticket-create', compact('categories'));
    }

    #--------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    public function storeSupportTicket(Request $request)
    {
        if (!astroauthcheck())
            return redirect()->route('front.astrologerlogin');

        $request->validate([
            'helpSupportId' => 'required|exists:help_supports,id',
            'subject'       => 'required|string|max:255',
            'description'   => 'required|string',
        ]);

        $user = astroauthcheck();

        // Generate unique 6-char ticket number
        do {
            $ticketNumber = strtoupper(\Illuminate\Support\Str::random(6));
        } while (DB::table('tickets')->where('ticketNumber', $ticketNumber)->exists());

        $ticketId = DB::table('tickets')->insertGetId([
            'helpSupportId' => $request->helpSupportId,
            'subject'       => $request->subject,
            'description'   => $request->description,
            'ticketNumber'  => $ticketNumber,
            'userId'        => $user->id,
            'createdBy'     => $user->id,
            'modifiedBy'    => $user->id,
            'sender_type'   => 'Astrologer',
            'ticketStatus'  => 'WAITING',
            'chatId'        => '',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // Update chatId using ticketId + userId
        DB::table('tickets')->where('id', $ticketId)->update([
            'chatId' => $ticketId . '_' . $user->id,
        ]);

        return redirect()->route('front.astrologers.tickets')
            ->with('success', 'Support ticket submitted successfully! Ticket #' . $ticketNumber);
    }

    #--------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    public function ticketChat(Request $request, $id)
    {
        if (!astroauthcheck())
            return redirect()->route('front.astrologerlogin');

        $user = astroauthcheck();

        // Get the ticket ensuring it belongs to this astrologer
        $ticket = DB::table('tickets')
            ->where('id', $id)
            ->where('userId', $user->id)
            ->where('sender_type', 'Astrologer')
            ->first();

        if (!$ticket) {
            return redirect()->route('front.astrologers.tickets')
                ->with('error', 'Ticket not found.');
        }

        $chatId = $ticket->chatId ?: ($id . '_' . $user->id);

        // Fetch messages from Firestore REST API
        $firebaseProjectId = DB::table('systemflag')->where('name', 'firebaseprojectId')->select('value')->first();
        $messages = [];

        if ($firebaseProjectId && $firebaseProjectId->value) {
            try {
                $response = Http::get("https://firestore.googleapis.com/v1/projects/{$firebaseProjectId->value}/databases/(default)/documents/supportChat/{$chatId}/userschat/{$id}/messages");
                $apiResponse = $response->json();

                if (isset($apiResponse['documents'])) {
                    $messages = $apiResponse['documents'];
                    usort($messages, function ($a, $b) {
                        return strtotime($a['createTime']) - strtotime($b['createTime']);
                    });
                }
            } catch (\Exception $e) {
                // If Firestore fails, show empty messages
            }
        }

        $data = [
            'chatId' => $chatId,
            'ticketId' => $id,
            'userId' => $user->id,
            'ticketStatus' => $ticket->ticketStatus,
            'ticketNumber' => $ticket->ticketNumber,
            'subject' => $ticket->subject,
        ];

        return view('frontend.astrologers.pages.support-ticket-chat', compact('messages', 'data'));
    }

    #--------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    public function sendTicketMessage(Request $request)
    {
        if (!astroauthcheck())
            return response()->json(['error' => 'Unauthorized'], 401);

        $request->validate([
            'message' => 'required|string|max:2000',
            'chatId' => 'required',
            'ticketId' => 'required',
        ]);

        $user = astroauthcheck();

        // Verify ticket belongs to this astrologer
        $ticket = DB::table('tickets')
            ->where('id', $request->ticketId)
            ->where('userId', $user->id)
            ->where('sender_type', 'Astrologer')
            ->first();

        if (!$ticket) {
            return response()->json(['error' => 'Ticket not found'], 404);
        }

        if ($ticket->ticketStatus === 'CLOSED') {
            return response()->json(['error' => 'Ticket is closed'], 400);
        }

        $firebaseProjectId = DB::table('systemflag')->where('name', 'firebaseprojectId')->select('value')->first();

        if (!$firebaseProjectId || !$firebaseProjectId->value) {
            return response()->json(['error' => 'Firebase not configured'], 500);
        }

        $apiEndpoint = "https://firestore.googleapis.com/v1/projects/{$firebaseProjectId->value}/databases/(default)/documents/";

        // Admin convention:
        // - userId2 stored as stringValue = ticketId  → admin shows LEFT  (received from astrologer/user)
        // - userId2 stored as integerValue = userId   → admin shows RIGHT (sent by admin)
        // We store userId2 = ticketId as stringValue so admin correctly shows our message on LEFT.
        $postData = [
            'fields' => [
                'message'   => ['stringValue'   => $request->message],
                'createdAt' => ['timestampValue' => \Carbon\Carbon::now()->toIso8601String()],
                'updatedAt' => ['timestampValue' => \Carbon\Carbon::now()->toIso8601String()],
                'userId1'   => ['integerValue'   => (int) $request->ticketId],
                'userId2'   => ['stringValue'    => (string) $request->ticketId],
                'status'    => ['stringValue'    => 'OPEN'],
            ],
        ];

        try {
            // Write ONLY to userschat/{ticketId} — single source of truth read by both sides
            Http::post($apiEndpoint . "supportChat/{$request->chatId}/userschat/{$request->ticketId}/messages", [
                'fields' => $postData['fields'],
            ]);

            // Update ticket status to OPEN if currently WAITING
            if ($ticket->ticketStatus === 'WAITING') {
                DB::table('tickets')->where('id', $request->ticketId)->update([
                    'ticketStatus' => 'OPEN',
                    'updated_at' => now(),
                ]);
            }

            return response()->json(['success' => true, 'message' => 'Message sent successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send message: ' . $e->getMessage()], 500);
        }
    }
}
