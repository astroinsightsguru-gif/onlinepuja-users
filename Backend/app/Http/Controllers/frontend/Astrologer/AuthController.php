<?php

namespace App\Http\Controllers\frontend\Astrologer;

use App\Http\Controllers\Controller;
use App\Models\AdminModel\DegreeOrDiploma;
use App\Models\AdminModel\FulltimeJob;
use App\Models\AdminModel\HighestQualification;
use App\Models\AdminModel\Language;
use App\Models\AdminModel\MainSourceOfBusiness;
use App\Models\AdminModel\TravelCountry;
use App\Models\AdminModel\User;
use App\Models\AstrologerModel\Astrologer;
use App\Models\AstrologerModel\AstrologerAvailability;
use App\Models\UserModel\UserDeviceDetail;
use App\Models\UserModel\UserRole;
use App\Models\AstrologerCategory;
use App\Models\AstrologerDocument;
use App\Models\Country;
use App\Models\EmailTemplate;
use App\Models\Skill;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Session\Session;
use Exception;

class AuthController extends Controller
{
    public function astrologerlogin()
    {
        if (astroauthcheck())
            return redirect()->route('front.astrologerindex');

        $getsystemflag = collect(DB::table('systemflag')->get()->map(function($f) { return (array)$f; }));

        $otplessAppId = $getsystemflag->where('name', 'otplessPartnerAppId')->first();
        $logo = $getsystemflag->where('name', 'AdminLogo')->first();
        $appname = $getsystemflag->where('name', 'AppName')->first();

        return view('frontend.astrologers.pages.astrologers-login', compact('otplessAppId', 'logo', 'appname'));
    }

  public function verifyOTLAstro(Request $request)
    {
        if (authcheck()) {
            return response()->json([
                'status' => 400,
                'message' => 'A user is already logged in. Please logout before logging in as an astrologer.',
            ], 400);
        }

        if(!empty($request->fromWeb)) {

            if (!empty($request->isGoogleLogin)) {

                $login = app(\App\Http\Controllers\API\Astrologer\AstrologerController::class)->loginAstrologer(new Request(['email' => $request->email]))->getData(true);

                if($login['status']!=400){

                    $session = new Session();
                    $session->set('astrotoken',$login['token']);

                    return response()->json([
                        'status' => 200,
                        'message' => "Astrologer login Successfully",
                    ], 200);
                }else{
                    return response()->json([
                        'status' => 400,
                        'message' => $login['message'],
                    ], 400);
                }


            }  else {
                if (empty($request->validOtp) || base64_decode($request->validOtp) != $request->otp) {
                    return response()->json([
                        'status' => 400,
                        'message' => 'Invalid OTP',
                    ], 400);
                }

                $login = app(\App\Http\Controllers\API\Astrologer\AstrologerController::class)->loginAstrologer(new Request(['contactNo' => $request->contactNo]))->getData(true);

                if($login['status']!=400){

                    $session = new Session();
                    $session->set('astrotoken',$login['token']);

                    return response()->json([
                        'status' => 200,
                        'message' => "Astrologer login Successfully",
                    ], 200);
                }else{
                    return response()->json([
                        'status' => 400,
                        'message' => $login['message'],
                    ], 400);
                }
            }
        }

    }

    // registration part
    public function astrologerregister()
    {
        if (authcheck())
            return redirect()->back();

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
        $appname = $getsystemflag->where('name', 'AppName')->first();
        $documents = AstrologerDocument::query()->get();

        return view('frontend.astrologers.pages.astrologers-registration', compact('categories', 'skills', 'languages', 'mainSourceBusiness', 'qualifications', 'jobs', 'countryTravel', 'highestQualification', 'appname', 'country', 'documents'));
    }

    public function astrologerstore(Request $request)
    {
        // dd($request->all());
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'contactNo' => 'required|unique:users,contactNo',
                'email' => 'required|unique:users,email',
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
                'country' => 'required',
                'countryCode' => 'required',
                'whatsappNo' => 'required',
                'aadharNo' => 'required',
                'pancardNo' => 'required',
                'ifscCode' => 'required',
                'bankBranch' => 'required',
                'bankName' => 'required',
                'accountNumber' => 'required',
                'videoCallRate' => 'required',
                'reportRate' => 'required'
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            if (request('profileImage')) {
                $profileImage = base64_encode(file_get_contents($request->file('profileImage')));
            } else {
                $profileImage = null;
            }
            if ($profileImage) {
                if (Str::contains($profileImage, 'storage')) {
                    $path = $profileImage;
                } else {
                    $time = Carbon::now()->timestamp;
                    $destinationpath = 'public/storage/images/';
                    $imageName = 'astrologer_' . $request->id . $time;
                    $path = $destinationpath . $imageName . '.png';
                    $isFile = explode('.', $path);
                    if (!(file_exists($path) && count($isFile) > 1)) {
                        file_put_contents($path, base64_decode($profileImage));
                    }
                }
            } else {
                $path = null;
            }
            $countryCode = !empty($req->countryCode) ? $req->countryCode : '+91';

            if (!str_starts_with($countryCode, '+')) {
                $countryCode = '+' . $countryCode;
            }

            // Create User
            $user = new User();
            $user->name = $request->name;
            $user->contactNo = $request->contactNo;
            $user->email = $request->email;
            $user->birthDate = $request->birthDate;
            $user->profile = $path;
            $user->gender = $request->gender;
            $user->location = $request->currentCity;
            $user->countryCode = $countryCode;
            $user->country = $request->country;
            $user->save();

            // Get the last inserted ID of the user
            $userId = $user->id;

            // Generate a random 6-character alphanumeric string for the prefix
            $referral_token = 'REF' . numberToCharacterString($user->id);
            $user->update([
                'referral_token' => $referral_token,
            ]);

            UserRole::create([
                'userId' => $userId,
                'roleId' => 2,
            ]);

            $slug = Str::slug($request->name, '-');
            $originalSlug = $slug;
            $counter = 1;
            while (DB::table('astrologers')->where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            // Create Astrologer
            $astrologer = new Astrologer();
            $astrologer->name = $request->name;
            $astrologer->slug = $slug;
            $astrologer->userId = $userId;
            $astrologer->email = $request->email;
            $astrologer->contactNo = $request->contactNo;
            $astrologer->gender = $request->gender;
            $astrologer->birthDate = $request->birthDate;
            $astrologer->primarySkill = implode(',', $request->primarySkill);
            $astrologer->allSkill = implode(',', $request->allSkill);
            $astrologer->languageKnown = implode(',', $request->languageKnown);
            $astrologer->profileImage = $path;
            $astrologer->charge = $request->charge;
            $astrologer->experienceInYears = $request->experienceInYears;
            $astrologer->dailyContribution = $request->dailyContribution;
            $astrologer->hearAboutAstroguru = $request->hearAboutAstroguru;
            $astrologer->isWorkingOnAnotherPlatform = $request->isWorkingOnAnotherPlatform;
            $astrologer->whyOnBoard = $request->whyOnBoard;
            $astrologer->interviewSuitableTime = $request->interviewSuitableTime;
            $astrologer->currentCity = $request->currentCity;
            $astrologer->mainSourceOfBusiness = $request->mainSourceOfBusiness;
            $astrologer->highestQualification = $request->highestQualification;
            $astrologer->degree = $request->degree;
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
            $astrologer->videoCallRate = $request->videoCallRate;
            $astrologer->reportRate = $request->reportRate;
            $astrologer->nameofplateform = $request->nameofplateform;
            $astrologer->monthlyEarning = $request->monthlyEarning;
            $astrologer->referedPerson = $request->referedPerson;
            $astrologer->country = $request->country;
            $astrologer->charge_usd = $request->charge_usd;
            $astrologer->videoCallRate_usd = $request->videoCallRate_usd;
            $astrologer->reportRate_usd = $request->reportRate_usd;
            $astrologer->countryCode = $request->countryCode;

            $astrologer->whatsappNo = $request->whatsappNo;
            $astrologer->aadharNo = $request->aadharNo;
            $astrologer->pancardNo = $request->pancardNo;
            $astrologer->ifscCode = $request->ifscCode;
            $astrologer->bankBranch = $request->bankBranch;
            $astrologer->bankName = $request->bankName;
            $astrologer->accountNumber = $request->accountNumber;
            $astrologer->accountHolderName = $request->accountHolderName;
            $astrologer->upi = $request->upi;

            $documents = AstrologerDocument::query()->get();
            foreach ($documents as $document) {
                $columnName = Str::snake($document->name);

                if (!Schema::hasColumn('astrologers', $columnName)) {
                    Schema::table('astrologers', function (Blueprint $table) use ($columnName) {
                        $table->string($columnName)->nullable();
                    });
                }

                if ($request->hasFile($columnName)) {
                    $docImage = base64_encode(file_get_contents($request->file($columnName)));
                    if ($docImage) {
                        $time = Carbon::now()->timestamp;
                        $destinationpath = 'public/storage/images/documents/';
                        $imageName = $columnName . '_' . $request->id . '_' . $time;
                        $docPath = $destinationpath . $imageName . '.png';
                        if (!file_exists($docPath)) {
                            file_put_contents($docPath, base64_decode($docImage));
                        }
                        $astrologer->$columnName = $docPath;
                    }
                }
            }

            $astrologer->save();

            $astroId = $astrologer->id;

            // Additional processing for availability if required

            if ($request->astrologerAvailability) {
                $availability = DB::Table('astrologer_availabilities')
                    ->where('astrologerId', '=', $request->id)
                    ->delete();
                foreach ($request->astrologerAvailability as $astrologeravailable) {
                    if (array_key_exists('time', $astrologeravailable)) {
                        foreach ($astrologeravailable['time'] as $availability) {
                            if ($availability['fromTime']) {
                                $availability['fromTime'] = Carbon::createFromFormat('H:i', $availability['fromTime'])->format('h:i A');
                            }
                            if ($availability['toTime']) {
                                $availability['toTime'] = Carbon::createFromFormat('H:i', $availability['toTime'])->format('h:i A');
                            }
                            AstrologerAvailability::create([
                                'astrologerId' => $astroId,
                                'day' => $astrologeravailable['day'],
                                'fromTime' => $availability['fromTime'],
                                'toTime' => $availability['toTime'],
                                'createdBy' => $astroId,
                                'modifiedBy' => $astroId,
                            ]);
                        }
                    }
                }
            }

            if ($request->userDeviceDetails) {
                UserDeviceDetail::create([
                    'userId' => $user->id,
                    'appId' => $request->appId,
                    'deviceId' => $request->deviceId,
                    'fcmToken' => $request->fcmToken,
                    'deviceLocation' => $request->deviceLocation,
                    'deviceManufacturer' => $request->deviceManufacturer,
                    'deviceModel' => $request->deviceModel,
                    'appVersion' => $request->appVersion,
                ]);
            } else {
                UserDeviceDetail::create([
                    'userId' => $user->id,
                    'appId' => 1,
                ]);
            }

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
            return redirect()->back()->with('success', 'Form Submitted Successfully ,You can login after verification');
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        if (!astroauthcheck())
            return redirect()->route('front.astrologerindex');
        $session = new Session();
        $token = $session->get('astrotoken');
        try { $logout = app(\App\Http\Controllers\API\User\UserController::class)->logout(new Request(['token' => $token]))->getData(true); } catch (\Throwable $e) { $logout = ['status' => 200]; }
        $userDeviceDetail = UserDeviceDetail::where('userId', astroauthcheck()['id'])->first();
        if ($userDeviceDetail) {
            $userDeviceDetail->subscription_id_web = null;
            $userDeviceDetail->fcmToken = null;
            $userDeviceDetail->updated_at = Carbon::now()->timestamp;
            $userDeviceDetail->update();
        }
        $session = new Session();
        $session->remove('astrotoken');
        return response()->json([
            'message' => 'Logout User Successfully',
        ], 200);
    }

    public function register(Request $request)
    {
        try {
            // Validate the incoming request
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:astrologers',
                'contactNo' => 'required|string|max:20',
                'password' => 'required|string|min:8|confirmed',
                'degree' => 'required|string|max:255',
                'qualification' => 'required|string|max:255',
                'experience' => 'required|integer|min:0',
                'hourly_rate' => 'required|numeric|min:0',
                'business_name' => 'required|string|max:255',
                'business_address' => 'required|string|max:500',
                'languages' => 'required|string',
                'available_days' => 'required|string',
                'available_time' => 'required|string',
                'document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'certificate' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'profile_image' => 'required|file|mimes:jpg,jpeg,png|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if email already exists
            if (Astrologer::where('email', $request->email)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email already registered'
                ], 409);
            }

            DB::beginTransaction();

            // Handle file uploads
            $documentPath = null;
            $certificatePath = null;
            $profileImagePath = null;

            if ($request->hasFile('document')) {
                $documentPath = $request->file('document')->store('astrologer_documents', 'public');
            }
            if ($request->hasFile('certificate')) {
                $certificatePath = $request->file('certificate')->store('astrologer_certificates', 'public');
            }
            if ($request->hasFile('profile_image')) {
                $profileImagePath = $request->file('profile_image')->store('astrologer_profiles', 'public');
            }

            // Create the astrologer
            $astrologer = Astrologer::create([
                'name' => $request->name,
                'email' => $request->email,
                'contactNo' => $request->contactNo,
                'password' => bcrypt($request->password),
                'degree' => $request->degree,
                'qualification' => $request->qualification,
                'experience' => $request->experience,
                'hourly_rate' => $request->hourly_rate,
                'business_name' => $request->business_name,
                'business_address' => $request->business_address,
                'languages' => $request->languages,
                'available_days' => $request->available_days,
                'available_time' => $request->available_time,
                'document' => $documentPath,
                'certificate' => $certificatePath,
                'profile_image' => $profileImagePath,
                'status' => 'pending', // Pending approval
                'email_verified_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Astrologer registered successfully',
                'data' => [
                    'id' => $astrologer->id,
                    'name' => $astrologer->name,
                    'email' => $astrologer->email,
                    'contactNo' => $astrologer->contactNo,
                    'business_name' => $astrologer->business_name,
                    'status' => $astrologer->status,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function registerPage()
    {
        $getsystemflag = collect(DB::table('systemflag')->get()->map(function($f) { return (array)$f; }));

        $otplessAppId = $getsystemflag->where('name', 'otplessPartnerAppId')->first();
        $logo = $getsystemflag->where('name', 'AdminLogo')->first();
        $appname = $getsystemflag->where('name', 'AppName')->first();

        return view('frontend.astrologers.pages.astrologers-registration', compact('otplessAppId', 'logo', 'appname'));
    }
}
