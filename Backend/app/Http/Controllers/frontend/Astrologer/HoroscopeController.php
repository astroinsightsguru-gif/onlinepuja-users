<?php
namespace App\Http\Controllers\frontend\Astrologer;

use App\Http\Controllers\Controller;
use App\Models\AdminModel\SystemFlag;
use App\Models\AstrologerModel\Blog;
use App\Models\Horoscope;
use App\Models\MstControl;
use App\Models\UserModel\Kundali;
use App\Models\Contactus;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Stevebauman\Location\Facades\Location;
use Symfony\Component\HttpFoundation\Session\Session;

class HoroscopeController extends Controller
{
    public function getkundali(Request $request)
    {
        if (!astroauthcheck())
            return redirect()->route('front.astrologerlogin');

        // Artisan::call('cache:clear'); // Removed for performance
        $session = new Session();
        $token = $session->get('astrotoken');
        $getkundaliprice = app(\App\Http\Controllers\API\User\KundaliController::class)->pdfprice(new Request(['token' => $token]))->getData(true);
        $getkundali = app(\App\Http\Controllers\API\User\KundaliController::class)->getkundali(new Request(['token' => $token]))->getData(true);
        $getsystemflag = SystemFlag::all();
        $currency = $getsystemflag->where('name', 'currencySymbol')->first();
        return view('frontend.astrologers.pages.kundali', [
            'getkundali' => $getkundali,
            'getkundaliprice' => $getkundaliprice,
            'currency' => $currency,
        ]);
    }

    public function kundaliMatch(Request $request)
    {
        if (!astroauthcheck())
            return redirect()->route('front.astrologerlogin');

        return view('frontend.astrologers.pages.kundali-matching', []);
    }

    // ------------------------------------------------------------------------------------------------------------------------------------------------------------------
    public function kundaliMatchReport(Request $request)
    {
        if (!astroauthcheck())
            return redirect()->route('front.astrologerlogin');

        $KundaliMatching = app(\App\Http\Controllers\API\User\KundaliMatchingController::class)->report(new Request(['male_kundli_id' => $request->male_kundli_id, 'female_kundli_id' => $request->female_kundli_id]))->getData(true);
        $kundalimale = Kundali::where('id', $request->male_kundli_id)->first();
        $kundalifemale = Kundali::where('id', $request->female_kundli_id)->first();

        return view('frontend.astrologers.pages.kundali-match-report', [
            'KundaliMatching' => $KundaliMatching,
            'kundalimale' => $kundalimale,
            'kundalifemale' => $kundalifemale,
        ]);
    }

    // ------------------------------------------------------------------------------------------------------------------------------------------------------
    public function kundaliReport(Request $request)
    {
        // Initialize session
        $session = new Session();

        // Create unique session key based on request parameters
        $sessionKey = 'kundali_report_' . $request->kundali_id . '_' . ($request->lang ?? 'en');

        // Check if data exists in session
        if ($session->has($sessionKey)) {
            $KundaliReport = $session->get($sessionKey);
        } else {
            // Make API call if not in session
            $KundaliReport = app(\App\Http\Controllers\API\User\KundaliController::class)->getKundaliReport(new Request(['kundali_id' => $request->kundali_id, 'lang' => $request->lang]))->getData(true);

            // Store in session for subsequent requests
            $session->set($sessionKey, $KundaliReport);
        }

        return view('frontend.astrologers.pages.kundali-report', compact('KundaliReport'));
    }

    // ------------------------------------------------------------------------------------------------------------------------------------------------------
    public function getPanchang(Request $request)
    {
        if (!astroauthcheck())
            return redirect()->route('front.astrologerlogin');

        // Artisan::call('cache:clear'); // Removed for performance
        $api_key = DB::table('systemflag')->where('name', 'vedicAstroAPI')->first();
        $ip = $request->ip();
        if ($ip === '127.0.0.1' || $ip === '::1' || !$ip) {
            $ip = '103.238.108.209';
        }

        $position = Location::get($ip);
        if (!$position) {
            $position = (object) [
                'latitude' => '28.6139',
                'longitude' => '77.2090',
                'cityName' => 'New Delhi',
                'regionName' => 'Delhi',
                'countryName' => 'India',
                'timezone' => 'Asia/Kolkata',
            ];
        }

        $geoData = [
            'ip' => $ip,
            'lat' => $position->latitude,
            'lon' => $position->longitude,
            'city' => $position->cityName,
            'region' => $position->regionName,
            'country' => $position->countryName,
            'timezone' => $position->timezone,
        ];
        $latitude = $geoData['lat'];
        $longitude = $geoData['lon'];
        $timezone = $geoData['timezone'];

        $date = date('d/m/Y');
        if ($request->panchangDate) {
            $date = date('d/m/Y', strtotime($request->panchangDate));
        }
        $time = now($timezone)->format('h:i');

        try { $Todayspanchang = Http::timeout(4)->get('https://api.vedicastroapi.com/v3-json/panchang/panchang', ['date' => $date, 'time' => $time, 'tz' => $this->getTimezoneOffset($timezone), 'lat' => $latitude, 'lon' => $longitude, 'api_key' => $api_key->value, 'lang' => 'en']); $getPanchang = $Todayspanchang->json(); } catch (\Throwable $e) { \Log::warning('Astrologer Panchang API call timed out: ' . $e->getMessage()); $getPanchang = ['status' => 200, 'response' => []]; }

        return view('frontend.astrologers.pages.panchang', [
            'getPanchang' => $getPanchang,
        ]);
    }

    private function getTimezoneOffset($timezone)
    {
        $time = new \DateTime('now', new \DateTimeZone($timezone));
        return $time->getOffset() / 3600;
    }

    // --------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Get all horoscope signs (cached for 30 minutes).
     */
    private function getHoroscopeSignsCached()
    {
        return Cache::remember('horoscope_signs_list', 1800, function () {
            $signs = DB::table('hororscope_signs')->orderBy('id', 'DESC')->get();
            // Convert to array of arrays to match the old Http::json() format
            $signsArray = $signs->map(function ($sign) {
                $arr = (array) $sign;
                if (!empty($arr['image']) && !preg_match('/^https?:\/\//', $arr['image'])) {
                    $arr['image'] = asset($arr['image']);
                }
                return $arr;
            })->toArray();
            return [
                'recordList' => $signsArray,
                'status' => 200,
                'totalRecords' => count($signsArray),
            ];
        });
    }

    /**
     * Get daily/weekly/yearly horoscope data for a sign (cached for 15 minutes).
     */
    private function getDailyHoroscopeCached(int $signId, string $langcode = 'en')
    {
        $cacheKey = "daily_horoscope_{$signId}_{$langcode}_" . now()->format('Y-m-d');

        return Cache::remember($cacheKey, 900, function () use ($signId, $langcode) {
            $dt = Carbon::now()->format('Y-m-d');
            $mstData = Cache::remember('mst_control_data', 3600, function () {
                return MstControl::first();
            });
            $astroApiCallType = $mstData->astro_api_call_type ?? null;

            $signRcd = DB::table('hororscope_signs')->where('id', $signId)->first();
            $signName = $signRcd->name;

            $currentDate = new DateTime();
            $currentDate->setISODate((int) $currentDate->format('o'), (int) $currentDate->format('W'), 1);
            $startOfWeekFormatted = $currentDate->format('Y-m-d');
            $currentDate->modify('+6 days');
            $endOfWeekFormatted = $currentDate->format('Y-m-d');

            $currentYear = date('Y');
            $selectRaw = 'horoscopes.*, REPLACE(lucky_color_code, "#", "0xff") AS color_code';

            // --- Today ---
            $todayHoroscope = Horoscope::selectRaw($selectRaw)
                ->where('zodiac', $signName)
                ->where('date', $dt)
                ->where('type', config('constants.DAILY_HORSCOPE'))
                ->where('langcode', $langcode)
                ->get();

            if ($todayHoroscope->isEmpty() && $langcode !== 'en') {
                $todayHoroscope = Horoscope::selectRaw($selectRaw)
                    ->where('zodiac', $signName)
                    ->where('date', $dt)
                    ->where('type', config('constants.DAILY_HORSCOPE'))
                    ->where('langcode', 'en')
                    ->get();
            }

            // --- Weekly ---
            $weeklyHoroScope = Horoscope::selectRaw($selectRaw)
                ->where('zodiac', $signName)
                ->where('start_date', '>=', $startOfWeekFormatted)
                ->where('end_date', '<=', $endOfWeekFormatted)
                ->where('type', config('constants.WEEKLY_HORSCOPE'))
                ->where('langcode', $langcode)
                ->get();

            if ($weeklyHoroScope->isEmpty() && $langcode !== 'en') {
                $weeklyHoroScope = Horoscope::selectRaw($selectRaw)
                    ->where('zodiac', $signName)
                    ->where('start_date', '>=', $startOfWeekFormatted)
                    ->where('end_date', '<=', $endOfWeekFormatted)
                    ->where('type', config('constants.WEEKLY_HORSCOPE'))
                    ->where('langcode', 'en')
                    ->get();
            }

            // --- Yearly ---
            $yearlyHoroScope = Horoscope::selectRaw($selectRaw)
                ->where('zodiac', $signName)
                ->whereYear('date', $currentYear)
                ->whereNotNull('month_range')
                ->where('type', config('constants.YEARLY_HORSCOPE'))
                ->where('langcode', $langcode)
                ->get();

            if ($yearlyHoroScope->isEmpty() && $langcode !== 'en') {
                $startOfYearFormatted = "$currentYear-01-01";
                $endOfYearFormatted = "$currentYear-12-31";
                $yearlyHoroScope = Horoscope::selectRaw($selectRaw)
                    ->where('zodiac', $signName)
                    ->where('start_date', '>=', $startOfYearFormatted)
                    ->where('end_date', '<=', $endOfYearFormatted)
                    ->where('type', config('constants.YEARLY_HORSCOPE'))
                    ->where('langcode', 'en')
                    ->get();
            }

            // Convert to nested arrays to match the old Http::json() format
            return [
                'message' => 'get daily Horoscope',
                'astroApiCallType' => $astroApiCallType,
                'vedicList' => [
                    'todayHoroscope' => $todayHoroscope->toArray(),
                    'weeklyHoroScope' => $weeklyHoroScope->toArray(),
                    'yearlyHoroScope' => $yearlyHoroScope->toArray(),
                ],
                'status' => 200,
            ];
        });
    }

    public function horoScope(Request $request)
    {
        if (!astroauthcheck())
            return redirect()->route('front.astrologerlogin');

        $gethoroscopesign = $this->getHoroscopeSignsCached();
        return view('frontend.astrologers.pages.horoscopesign', [
            'gethoroscopesign' => $gethoroscopesign,
        ]);
    }

    // --------------------------------------------------------------------------------------------------------------------------------------------------------------------------

    public function dailyHoroscope(Request $request, $slug)
    {
        if (!astroauthcheck())
            return redirect()->route('front.astrologerlogin');

        $horoscopeSign = DB::table('hororscope_signs')->where('slug', $slug)->first();

        if (!$horoscopeSign) {
            return back()->with('error', 'Horoscope sign not found.');
        }

        $gethoroscopesign = $this->getHoroscopeSignsCached();
        $horoscope = $this->getDailyHoroscopeCached($horoscopeSign->id);
        $signRcd = collect([$horoscopeSign]);

        return view('frontend.astrologers.pages.dailyhoroscope', [
            'horoscope' => $horoscope,
            'gethoroscopesign' => $gethoroscopesign,
            'signRcd' => $signRcd,
        ]);
    }

    // --------------------------------------------------------------------------------------------------------------------------------------------------------------------------

    public function aboutus(Request $request)
    {
        try {
            if (!astroauthcheck())
                return redirect()->route('front.astrologerlogin');

            $aboutus = DB::table('pages')->where('type', 'aboutus')->first();
            return view('frontend.astrologers.pages.aboutus', compact('aboutus'));
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    // -------------------------------------------------------------------------------------------------------------------------
    public function privacyPolicy(Request $request)
    {
        try {
            if (!astroauthcheck())
                return redirect()->route('front.astrologerlogin');

            $privacy = DB::table('pages')->where('type', 'privacy')->first();
            return view('frontend.astrologers.pages.privacy-policy', compact('privacy'));
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    // -------------------------------------------------------------------------------------------------------------------------
    public function refundPolicy(Request $request)
    {
        try {
            if (!astroauthcheck())
                return redirect()->route('front.astrologerlogin');

            $refundpolicy = DB::table('pages')->where('type', 'refundpolicy')->first();
            return view('frontend.astrologers.pages.refund-policy', compact('refundpolicy'));
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    // -------------------------------------------------------------------------------------------------------------------------
    public function termscondition(Request $request)
    {
        try {
            if (!astroauthcheck())
                return redirect()->route('front.astrologerlogin');

            $terms = DB::table('pages')->where('type', 'terms')->first();
            return view('frontend.astrologers.pages.terms-condition', compact('terms'));
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    // --------------------------------------------------------------------------------------------------------------------------------------
    public function getBlog(Request $request)
    {
        $bloglist = Blog::query()->where('isActive', 1)->orderBy('created_at', 'desc')->paginate(6);
        return view('frontend.astrologers.pages.blogs', [
            'bloglist' => $bloglist
        ]);
    }

    // --------------------------------------------------------------------------------------------------------------------------------------
    public function getBlogDetails(Request $request, $slug)
    {
        $blog = Blog::where('slug', $slug)->first();

        $blog->viewer = $blog->viewer + 1;
        $blog->update();

        $latestBlogs = Blog::where('slug', '!=', $slug)->where('isActive', 1)->latest()->take(5)->get();
        return view('frontend.astrologers.pages.blog-details', [
            'blog' => $blog,
            'latestBlogs' => $latestBlogs
        ]);
    }

    // ------------------------------------------------------------------------------------------------------------------------------------------
    public function contactUS(Request $request)
    {
        try {
            if (!astroauthcheck())
                return redirect()->route('front.astrologerlogin');

            $sitenumber = DB::table('systemflag')
                ->where('name', 'sitenumber')
                ->value('value');
            $siteemail = DB::table('systemflag')
                ->where('name', 'siteemail')
                ->value('value');
            $siteaddress = DB::table('systemflag')
                ->where('name', 'siteaddress')
                ->value('value');
            $appName = DB::table('systemflag')
                ->where('name', 'AppName')
                ->select('value')
                ->first();
            return view('frontend.astrologers.pages.contact', compact('sitenumber', 'siteemail', 'siteaddress', 'appName'));
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    // -------------------------------------------------------------------------------------------------------------------------
    public function SavecontactUS(Request $request)
    {
        $request->validate([
            'contact_name' => 'required',
            'contact_email' => 'required|email',
            'contact_message' => 'required',
        ]);
        Contactus::create([
            'contact_name' => $request->contact_name,
            'contact_email' => $request->contact_email,
            'contact_message' => $request->contact_message,
        ]);
        return response()->json(['success' => 'Form submitted successfully!'], 200);
    }

    // -------------------------------------------------------------------------------------------------------------------------
    public function followerslist(Request $request)
    {
        if (!astroauthcheck())
            return redirect()->route('front.astrologerlogin');

        $astrologerId = astroauthcheck()['astrologerId'];
        $session = new Session();
        $token = $session->get('astrotoken');
        $getastrologerfollower = app(\App\Http\Controllers\API\Astrologer\AstrologerController::class)->getAstrologerFollower(new Request(['token' => $token, 'astrologerId' => $astrologerId]))->getData(true);
        // dd($getastrologerfollower);
        return view('frontend.astrologers.pages.followers', compact('getastrologerfollower'));
    }
}
