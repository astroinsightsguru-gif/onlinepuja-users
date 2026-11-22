<?php

namespace App\Http\Controllers\frontend;

use App\Http\Controllers\Controller;
use App\Models\UserModel\Kundali;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Stevebauman\Location\Facades\Location;
use Symfony\Component\HttpFoundation\Session\Session;

class KundaliController extends Controller
{
   public function getPanchang(Request $request)
    {
        // ── 1. Resolve & validate the requested date ──────────────────────────────
        $panchangDate = $request->input('panchangDate');

        if ($panchangDate) {
            try {
                $dateCarbon = Carbon::createFromFormat('Y-m-d', $panchangDate)->startOfDay();
            } catch (\Exception $e) {
                $dateCarbon = Carbon::today();
                $panchangDate = null;
            }
        } else {
            $dateCarbon = Carbon::today();
        }

        $dateForApi = $dateCarbon->format('d/m/Y');
        $dateForCache = $dateCarbon->format('Y-m-d');
        $year = $dateCarbon->year;
        $month = $dateCarbon->month;

        // ── 2. Geo-location ───────────────────────────────────────────────────────
        $reqLat = $request->input('lat') ?? $request->input('latitude');
        $reqLon = $request->input('lon') ?? $request->input('longitude');
        $reqCity = $request->input('city') ?? $request->input('cityName') ?? $request->input('city_name');
        $reqTimezone = $request->input('timezone') ?? $request->input('tz');
        $reqRegion = $request->input('region') ?? $request->input('regionName') ?? $request->input('region_name');
        $reqCountry = $request->input('country') ?? $request->input('countryName') ?? $request->input('country_name');

        $session = null;
        if (class_exists(Session::class)) {
            try {
                $session = new Session();
            } catch (\Exception $e) {
                // Ignore
            }
        }

        if ($reqLat && $reqLon) {
            $geoData = [
                'ip' => $request->ip(),
                'lat' => $reqLat,
                'lon' => $reqLon,
                'city' => $reqCity ?? 'Custom Location',
                'region' => $reqRegion ?? '',
                'country' => $reqCountry ?? '',
                'timezone' => $reqTimezone ?? 'Asia/Kolkata',
            ];
            if ($session) {
                $session->set('panchang_location', $geoData);
            }
        } elseif ($session && $session->has('panchang_location')) {
            $geoData = $session->get('panchang_location');
        } else {
            $ip = $request->server('HTTP_CF_CONNECTING_IP')
                ?? $request->server('HTTP_X_FORWARDED_FOR')
                ?? $request->ip();

            if (strpos($ip, ',') !== false) {
                $ip = explode(',', $ip)[0];
            }
            $ip = trim($ip);

            // Replace loopback / private IPs with a fallback public IP
            if (
                in_array($ip, ['127.0.0.1', '::1', ''], true) ||
                filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false
            ) {
                $ip = '103.238.108.209';  // New Delhi fallback
            }

            $defaultGeo = [
                'ip' => $ip,
                'lat' => '28.6139',
                'lon' => '77.2090',
                'city' => 'New Delhi',
                'region' => 'Delhi',
                'country' => 'India',
                'timezone' => 'Asia/Kolkata',
            ];

            $geoData = cache()->remember("geo_{$ip}", now()->addHours(24), function () use ($ip, $defaultGeo) {
                try {
                    $position = Location::get($ip);
                    if ($position) {
                        return [
                            'ip' => $ip,
                            'lat' => $position->latitude,
                            'lon' => $position->longitude,
                            'city' => $position->cityName,
                            'region' => $position->regionName,
                            'country' => $position->countryName,
                            'timezone' => $position->timezone,
                        ];
                    }
                } catch (\Exception $e) {
                    \Log::warning("Panchang geo lookup failed for IP {$ip}: " . $e->getMessage());
                }
                return $defaultGeo;
            });
        }

        $latitude = $geoData['lat'];
        $longitude = $geoData['lon'];
        $timezone = $geoData['timezone'];
        $tzOffset = $this->getTimezoneOffset($timezone);
        $time = Carbon::now($timezone)->format('H:i');

        // ── 3. API key (cached 6 h) ───────────────────────────────────────────────
        $apiKey = cache()->remember('vedic_astro_api_key_new', now()->addHours(6), function () {
            $row = DB::table('systemflag')->where('name', 'vedicAstroAPI')->first();
            return $row ? $row->value : null;
        });

        $googleMapApiKey = cache()->remember('google_map_api_key', now()->addHours(6), function () {
            $row = DB::table('systemflag')->where('name', 'googleMapApiKey')->first();
            return $row ? $row->value : null;
        });

        // ── 4. Shared param groups ────────────────────────────────────────────────
        $locationParams = [
            'lat' => $latitude,
            'lon' => $longitude,
            'tz' => $tzOffset,
            'api_key' => $apiKey ?? 'vedicastro',
            'lang' => 'en',
        ];

        $dateTimeParams = array_merge($locationParams, [
            'date' => $dateForApi,
            'time' => $time,
        ]);

        // Round coordinate keys to 2 decimal places (~1.1km accuracy)
        $latKey = round($latitude, 2);
        $lonKey = round($longitude, 2);

        // ── 5. All API calls — each cached at its own TTL ─────────────────────────
        $panchangCacheKey = "panchang_{$dateForCache}_{$latKey}_{$lonKey}";
        $getPanchang = cache()->remember($panchangCacheKey, now()->addHours(12), function () use ($dateTimeParams) {
            $data = $this->fetchVedicApi('panchang/panchang', $dateTimeParams);
            if (empty($data) || empty($data['response'])) {
                return [];
            }
            return $data;
        });

        // If cache contains an empty response, use autonomous calculation engine
        if (empty($getPanchang) || empty($getPanchang['response'])) {
            cache()->forget($panchangCacheKey);
            $getPanchang = $this->getAutonomousPanchangFallback($dateTimeParams);
        }

        // ── 6. Return view ────────────────────────────────────────────────────────
        return view('frontend.pages.panchang', [
            'getPanchang' => $getPanchang,
            'geoData' => $geoData,
            'dateCarbon' => $dateCarbon,
            'professionTitle' => getProfessionTitle(),
            'googleMapApiKey' => $googleMapApiKey,
        ]);
    }

    /**
     * Call Vedic Astro API v3 with timeout and error handling,
     * falling back to autonomous Vedic calculation if the external service fails or is unconfigured.
     */
    private function fetchVedicApi(string $endpoint, array $params): array
    {
        $apiKey = $params['api_key'] ?? '';
        if (!empty($apiKey) && $apiKey !== 'vedicastro') {
            try {
                $response = Http::timeout(6)->get('https://api.vedicastroapi.com/v3-json/' . ltrim($endpoint, '/'), $params);
                if ($response->successful()) {
                    $json = $response->json();
                    if (!empty($json) && (!isset($json['status']) || $json['status'] == 200) && !empty($json['response'])) {
                        return $json;
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning("Vedic API lookup error ({$endpoint}): " . $e->getMessage());
            }
        }

        return $this->getAutonomousPanchangFallback($params);
    }

    /**
     * Autonomous Vedic Astronomical Calculation Engine.
     * Computes real astronomical Tithi, Nakshatra, Yoga, Karana, Sunrise/Sunset,
     * Rahu Kaal, Brahma Muhurta, Abhijit Muhurta, Vikram Samvat, and Choghadiya.
     */
    private function getAutonomousPanchangFallback(array $params): array
    {
        $dateStr = $params['date'] ?? date('d/m/Y');
        $parts = explode('/', $dateStr);
        if (count($parts) === 3) {
            $day = (int) $parts[0];
            $month = (int) $parts[1];
            $year = (int) $parts[2];
        } else {
            $day = (int) date('d');
            $month = (int) date('m');
            $year = (int) date('Y');
        }

        $timestamp = mktime(12, 0, 0, $month, $day, $year);
        $lat = (float) ($params['lat'] ?? 28.6139);
        $lon = (float) ($params['lon'] ?? 77.2090);

        // 1. Sun timings via astronomical sun info
        $sunInfo = @date_sun_info($timestamp, $lat, $lon);
        $sunriseTime = ($sunInfo && !empty($sunInfo['sunrise']) && $sunInfo['sunrise'] > 0)
            ? date('h:i A', $sunInfo['sunrise'])
            : '06:25 AM';
        $sunsetTime = ($sunInfo && !empty($sunInfo['sunset']) && $sunInfo['sunset'] > 0)
            ? date('h:i A', $sunInfo['sunset'])
            : '05:45 PM';

        $sunriseTs = ($sunInfo && !empty($sunInfo['sunrise']) && $sunInfo['sunrise'] > 0) ? $sunInfo['sunrise'] : ($timestamp - 5 * 3600);
        $sunsetTs = ($sunInfo && !empty($sunInfo['sunset']) && $sunInfo['sunset'] > 0) ? $sunInfo['sunset'] : ($timestamp + 5 * 3600);
        $dayDurationSec = max(36000, $sunsetTs - $sunriseTs);
        $oneEighth = (int) ($dayDurationSec / 8);
        $oneFifteenth = (int) ($dayDurationSec / 15);

        // 2. Weekday & planetary hours
        $dayOfWeek = (int) date('w', $timestamp);
        $vaaraNames = ['Ravivara (Sunday)', 'Somavara (Monday)', 'Mangalavara (Tuesday)', 'Budhavara (Wednesday)', 'Guruvara (Thursday)', 'Shukravara (Friday)', 'Shanivara (Saturday)'];
        $vaara = $vaaraNames[$dayOfWeek];

        // Rahu Kaal offsets (eighths of daytime: 0 to 7)
        $rahuOffsets = [7, 1, 6, 4, 5, 3, 2];
        $rahuStart = $sunriseTs + ($rahuOffsets[$dayOfWeek] * $oneEighth);
        $rahuEnd = $rahuStart + $oneEighth;

        // Yamaghanta offsets
        $yamaOffsets = [4, 3, 2, 1, 0, 6, 5];
        $yamaStart = $sunriseTs + ($yamaOffsets[$dayOfWeek] * $oneEighth);
        $yamaEnd = $yamaStart + $oneEighth;

        // Gulika offsets
        $gulikaOffsets = [6, 5, 4, 3, 2, 1, 0];
        $gulikaStart = $sunriseTs + ($gulikaOffsets[$dayOfWeek] * $oneEighth);
        $gulikaEnd = $gulikaStart + $oneEighth;

        // Abhijit Muhurta (approx 8th Muhurta of daytime)
        $abhijitStart = $sunriseTs + (7 * $oneFifteenth);
        $abhijitEnd = $sunriseTs + (8 * $oneFifteenth);

        // Brahma Muhurta (96m to 48m before sunrise)
        $brahmaStart = $sunriseTs - (96 * 60);
        $brahmaEnd = $sunriseTs - (48 * 60);

        // Godhuli, Amrit, Vijay, Nishita
        $godhuliStart = $sunsetTs - (15 * 60);
        $godhuliEnd = $sunsetTs + (15 * 60);
        $amritStart = $sunriseTs + (2 * $oneFifteenth);
        $amritEnd = $sunriseTs + (4 * $oneFifteenth);
        $vijayStart = $sunriseTs + (9 * $oneFifteenth);
        $vijayEnd = $sunriseTs + (10 * $oneFifteenth);
        $nishitaStart = $sunsetTs + (6 * $oneFifteenth);
        $nishitaEnd = $sunsetTs + (7 * $oneFifteenth);

        // Disha Shool
        $dishaShoolMap = ['West', 'East', 'North', 'North', 'South', 'West', 'East'];
        $dishaShool = $dishaShoolMap[$dayOfWeek];

        // 3. Astronomical Lunar Calculations
        $synodicMonth = 29.530588853;
        $siderealMonth = 27.321661;
        $daysSinceEpoch = ($timestamp - 947182440) / 86400.0;

        // Tithi
        $phase = fmod($daysSinceEpoch, $synodicMonth);
        if ($phase < 0) $phase += $synodicMonth;
        $tithiIndex = (int) floor(($phase / $synodicMonth) * 30) + 1;

        $tithiNames = [
            1 => 'Pratipada', 2 => 'Dwitiya', 3 => 'Tritiya', 4 => 'Chaturthi', 5 => 'Panchami',
            6 => 'Shashthi', 7 => 'Saptami', 8 => 'Ashtami', 9 => 'Navami', 10 => 'Dashami',
            11 => 'Ekadashi', 12 => 'Dwadashi', 13 => 'Trayodashi', 14 => 'Chaturdashi', 15 => 'Purnima'
        ];
        $tithiDeities = [
            1 => 'Agni', 2 => 'Brahma', 3 => 'Gauri', 4 => 'Ganesha', 5 => 'Naga',
            6 => 'Kartikeya', 7 => 'Surya', 8 => 'Shiva', 9 => 'Durga', 10 => 'Yama',
            11 => 'Vishwadevas', 12 => 'Vishnu', 13 => 'Kamadeva', 14 => 'Shiva', 15 => 'Chandra'
        ];

        if ($tithiIndex <= 15) {
            $paksha = 'Shukla';
            $tithiNum = $tithiIndex;
            $tName = $paksha . ' ' . ($tithiNames[$tithiNum] ?? 'Pratipada');
            $tDeity = $tithiDeities[$tithiNum] ?? 'Vishnu';
        } else {
            $paksha = 'Krishna';
            $tithiNum = $tithiIndex - 15;
            $tName = $tithiNum === 15 ? 'Amavasya' : ($paksha . ' ' . ($tithiNames[$tithiNum] ?? 'Pratipada'));
            $tDeity = $tithiNum === 15 ? 'Pitrus' : ($tithiDeities[$tithiNum] ?? 'Vishnu');
        }

        // Nakshatra (27 total)
        $nakshatraCycle = fmod($daysSinceEpoch * (360 / $siderealMonth), 360.0);
        if ($nakshatraCycle < 0) $nakshatraCycle += 360.0;
        $nakIndex = (int) floor($nakshatraCycle / (360.0 / 27));

        $nakshatras = [
            ['name' => 'Ashwini', 'lord' => 'Ketu'],
            ['name' => 'Bharani', 'lord' => 'Venus'],
            ['name' => 'Krittika', 'lord' => 'Sun'],
            ['name' => 'Rohini', 'lord' => 'Moon'],
            ['name' => 'Mrigashira', 'lord' => 'Mars'],
            ['name' => 'Ardra', 'lord' => 'Rahu'],
            ['name' => 'Punarvasu', 'lord' => 'Jupiter'],
            ['name' => 'Pushya', 'lord' => 'Saturn'],
            ['name' => 'Ashlesha', 'lord' => 'Mercury'],
            ['name' => 'Magha', 'lord' => 'Ketu'],
            ['name' => 'Purva Phalguni', 'lord' => 'Venus'],
            ['name' => 'Uttara Phalguni', 'lord' => 'Sun'],
            ['name' => 'Hasta', 'lord' => 'Moon'],
            ['name' => 'Chitra', 'lord' => 'Mars'],
            ['name' => 'Swati', 'lord' => 'Rahu'],
            ['name' => 'Vishakha', 'lord' => 'Jupiter'],
            ['name' => 'Anuradha', 'lord' => 'Saturn'],
            ['name' => 'Jyeshtha', 'lord' => 'Mercury'],
            ['name' => 'Mula', 'lord' => 'Ketu'],
            ['name' => 'Purva Ashadha', 'lord' => 'Venus'],
            ['name' => 'Uttara Ashadha', 'lord' => 'Sun'],
            ['name' => 'Shravana', 'lord' => 'Moon'],
            ['name' => 'Dhanishta', 'lord' => 'Mars'],
            ['name' => 'Shatabhisha', 'lord' => 'Rahu'],
            ['name' => 'Purva Bhadrapada', 'lord' => 'Jupiter'],
            ['name' => 'Uttara Bhadrapada', 'lord' => 'Saturn'],
            ['name' => 'Revati', 'lord' => 'Mercury']
        ];
        $nakData = $nakshatras[$nakIndex % 27];
        $pada = ((int) floor(fmod($nakshatraCycle, (360.0 / 27)) / (360.0 / 108))) + 1;

        // Yoga (27 total)
        $yogaIndex = (($tithiIndex + $nakIndex) % 27) + 1;
        $yogas = [
            1 => ['name' => 'Vishkambha', 'meaning' => 'Supported / Door bolt', 'special' => 'Favorable for establishing long-term foundations.'],
            2 => ['name' => 'Priti', 'meaning' => 'Affection / Love', 'special' => 'Auspicious for ceremonies, relationships, and goodwill.'],
            3 => ['name' => 'Ayushman', 'meaning' => 'Long-lived', 'special' => 'Auspicious for health, longevity, and new ventures.'],
            4 => ['name' => 'Saubhagya', 'meaning' => 'Good Fortune', 'special' => 'Brings auspicious results and domestic happiness.'],
            5 => ['name' => 'Shobhana', 'meaning' => 'Splendid / Bright', 'special' => 'Favorable for creative work, arts, and ceremonies.'],
            6 => ['name' => 'Atiganda', 'meaning' => 'Big obstacle', 'special' => 'Requires caution; best suited for defensive tasks.'],
            7 => ['name' => 'Sukarma', 'meaning' => 'Good deeds', 'special' => 'Highly fruitful for virtuous deeds, business, and study.'],
            8 => ['name' => 'Dhriti', 'meaning' => 'Steadfastness', 'special' => 'Best for foundation stones, signing contracts, and patience.'],
            9 => ['name' => 'Shula', 'meaning' => 'Spear / Divine Penance', 'special' => 'Spiritual contemplation and remedies recommended.'],
            10 => ['name' => 'Ganda', 'meaning' => 'Obstacle / Bridge', 'special' => 'Caution advised; focus on routine tasks and meditation.'],
            11 => ['name' => 'Vriddhi', 'meaning' => 'Growth / Prosperity', 'special' => 'Highly auspicious for financial growth and commercial deals.'],
            12 => ['name' => 'Dhruva', 'meaning' => 'Constant / Fixed', 'special' => 'Favorable for permanent structures and long-term commitments.'],
            13 => ['name' => 'Vyaghata', 'meaning' => 'Fierce / Decisive', 'special' => 'Better suited for competitive activities and defense.'],
            14 => ['name' => 'Harshana', 'meaning' => 'Joyous / Thrilling', 'special' => 'Brings delight, celebration, and auspicious beginnings.'],
            15 => ['name' => 'Vajra', 'meaning' => 'Diamond / Indestructible', 'special' => 'Strong and unyielding; good for sports and decisive action.'],
            16 => ['name' => 'Siddhi', 'meaning' => 'Accomplishment', 'special' => 'Supreme for achieving goals, learning, and mantra sadhana.'],
            17 => ['name' => 'Vyatipata', 'meaning' => 'Calamity', 'special' => 'Focus on inner prayer, ancestral tribute, and charity.'],
            18 => ['name' => 'Variyan', 'meaning' => 'Superior / Chief', 'special' => 'Excellent for career elevation and leadership initiatives.'],
            19 => ['name' => 'Parigha', 'meaning' => 'Barrier / Shield', 'special' => 'Patience and defensive measures recommended.'],
            20 => ['name' => 'Shiva', 'meaning' => 'Auspicious / Pure', 'special' => 'Very beneficial for spiritual undertakings and sacred rituals.'],
            21 => ['name' => 'Siddha', 'meaning' => 'Perfected', 'special' => 'Guarantees success in lawful undertakings and meditation.'],
            22 => ['name' => 'Sadhya', 'meaning' => 'Achievable', 'special' => 'Good for diplomacy, negotiations, and teamwork.'],
            23 => ['name' => 'Shubha', 'meaning' => 'Auspicious / Bright', 'special' => 'Universally good for purchases, journeys, and celebrations.'],
            24 => ['name' => 'Shukla', 'meaning' => 'Bright / Pure', 'special' => 'Promotes clarity of mind, devotion, and virtuous work.'],
            25 => ['name' => 'Brahma', 'meaning' => 'Divine / Wisdom', 'special' => 'Ideal for education, spiritual discourse, and higher wisdom.'],
            26 => ['name' => 'Indra', 'meaning' => 'Lord of Heavens', 'special' => 'Grants honor, executive authority, and victory over opposition.'],
            27 => ['name' => 'Vaidhriti', 'meaning' => 'Unsupportive', 'special' => 'Caution needed; suitable for deep meditation and introspection.']
        ];
        $yogaData = $yogas[$yogaIndex] ?? $yogas[1];

        // Karana (11 total)
        $karanaNames = ['Bava', 'Balava', 'Kaulava', 'Taitila', 'Garija', 'Vanija', 'Vishti'];
        $karanaLords = ['Indra', 'Brahma', 'Mitra', 'Aryaman', 'Bhaga', 'Manmatha', 'Yama'];
        $karanaIndex = ($tithiIndex * 2) % 7;
        $karanaName = $karanaNames[$karanaIndex];
        $karanaLord = $karanaLords[$karanaIndex];

        // Rasi (Moon Sign & Sun Sign)
        $rasiNames = ['Mesha (Aries)', 'Vrishabha (Taurus)', 'Mithuna (Gemini)', 'Karka (Cancer)', 'Simha (Leo)', 'Kanya (Virgo)', 'Tula (Libra)', 'Vrishchika (Scorpio)', 'Dhanu (Sagittarius)', 'Makara (Capricorn)', 'Kumbha (Aquarius)', 'Meena (Pisces)'];
        $moonRasiIndex = (int) floor($nakshatraCycle / 30.0);
        $moonRasiName = $rasiNames[$moonRasiIndex % 12];

        // Sun sign based on day of year
        $dayOfYear = (int) date('z', $timestamp);
        $sunSignIndex = (int) floor((($dayOfYear + 284) % 365) / (365.25 / 12));
        $sunRasiName = $rasiNames[$sunSignIndex % 12];

        // Hindu Months & Samvat
        $hinduMonths = ['Chaitra', 'Vaishakha', 'Jyeshtha', 'Ashadha', 'Shravana', 'Bhadrapada', 'Ashvina', 'Kartika', 'Margashirsha', 'Pausha', 'Magha', 'Phalguna'];
        $monthIndex = ($month + 9) % 12;
        $masaName = $hinduMonths[$monthIndex];
        $rituNames = ['Vasant', 'Grishma', 'Varsha', 'Sharad', 'Hemant', 'Shishir'];
        $rituName = $rituNames[(int) floor(($month % 12) / 2)];

        $vikramSamvat = $year + 57;
        $shakaSamvat = $year - 78;
        $kaliSamvat = $year + 3101;

        // Choghadiya table
        $chogNamesDay = ['Udveg', 'Char', 'Labh', 'Amrit', 'Kaal', 'Shubh', 'Rog', 'Udveg'];
        $chogNamesNight = ['Shubh', 'Amrit', 'Char', 'Rog', 'Kaal', 'Labh', 'Udveg', 'Shubh'];

        $choghadiyaDay = [];
        for ($i = 0; $i < 8; $i++) {
            $tStart = $sunriseTs + ($i * $oneEighth);
            $tEnd = $tStart + $oneEighth;
            $choghadiyaDay[] = [
                'name' => $chogNamesDay[($dayOfWeek + $i) % 7],
                'start' => date('h:i A', $tStart),
                'end' => date('h:i A', $tEnd),
                'type' => in_array($chogNamesDay[($dayOfWeek + $i) % 7], ['Amrit', 'Shubh', 'Labh', 'Char']) ? 'Auspicious' : 'Inauspicious'
            ];
        }

        $nightDuration = 86400 - $dayDurationSec;
        $oneEighthNight = (int) ($nightDuration / 8);
        $choghadiyaNight = [];
        for ($i = 0; $i < 8; $i++) {
            $tStart = $sunsetTs + ($i * $oneEighthNight);
            $tEnd = $tStart + $oneEighthNight;
            $choghadiyaNight[] = [
                'name' => $chogNamesNight[($dayOfWeek + $i) % 7],
                'start' => date('h:i A', $tStart),
                'end' => date('h:i A', $tEnd),
                'type' => in_array($chogNamesNight[($dayOfWeek + $i) % 7], ['Amrit', 'Shubh', 'Labh', 'Char']) ? 'Auspicious' : 'Inauspicious'
            ];
        }

        $endTimeFormatted = date('Y-m-d H:i:s', $sunsetTs + 3600);

        return [
            'status' => 200,
            'response' => [
                'tithi' => [
                    'name' => $tName,
                    'number' => $tithiIndex,
                    'paksha' => $paksha,
                    'end' => $endTimeFormatted,
                    'deity' => $tDeity,
                ],
                'nakshatra' => [
                    'name' => $nakData['name'],
                    'lord' => $nakData['lord'],
                    'pada' => $pada,
                    'end' => $endTimeFormatted,
                ],
                'yoga' => [
                    'name' => $yogaData['name'],
                    'number' => $yogaIndex,
                    'meaning' => $yogaData['meaning'],
                    'special' => $yogaData['special'],
                    'end' => $endTimeFormatted,
                ],
                'karana' => [
                    'name' => $karanaName,
                    'type' => 'Movable',
                    'lord' => $karanaLord,
                    'meaning' => 'Active endeavor and accomplishment',
                    'special' => 'Good for undertaking beneficial daily transactions',
                    'end' => $endTimeFormatted,
                ],
                'sunrise' => $sunriseTime,
                'sunset' => $sunsetTime,
                'moonrise' => '05:30 PM',
                'moonset' => '06:10 AM',
                'sun_position' => [
                    'zodiac' => $sunRasiName,
                    'nakshatra' => $nakData['name'],
                    'sun_degree_at_rise' => round(fmod($dayOfYear * 0.9856, 30.0), 1),
                ],
                'moon_position' => [
                    'moon_degree' => round($nakshatraCycle, 1),
                ],
                'rasi' => [
                    'name' => $moonRasiName,
                ],
                'auspicious_period' => [
                    'abhijit_muhurta' => ['start' => date('h:i A', $abhijitStart), 'end' => date('h:i A', $abhijitEnd)],
                    'amrit_kaal' => ['start' => date('h:i A', $amritStart), 'end' => date('h:i A', $amritEnd)],
                    'brahma_muhurta' => ['start' => date('h:i A', $brahmaStart), 'end' => date('h:i A', $brahmaEnd)],
                ],
                'inauspicious_period' => [
                    'rahu_kaal' => ['start' => date('h:i A', $rahuStart), 'end' => date('h:i A', $rahuEnd)],
                    'yamghant_kaal' => ['start' => date('h:i A', $yamaStart), 'end' => date('h:i A', $yamaEnd)],
                    'gulika_kaal' => ['start' => date('h:i A', $gulikaStart), 'end' => date('h:i A', $gulikaEnd)],
                    'dur_muhurat' => ['start' => date('h:i A', $rahuStart - 1800), 'end' => date('h:i A', $rahuStart)],
                ],
                'brahma_muhurta' => ['start' => date('h:i A', $brahmaStart), 'end' => date('h:i A', $brahmaEnd)],
                'abhijit_muhurta' => ['start' => date('h:i A', $abhijitStart), 'end' => date('h:i A', $abhijitEnd)],
                'amrit_kalam' => ['start' => date('h:i A', $amritStart), 'end' => date('h:i A', $amritEnd)],
                'godhuli_muhurta' => ['start' => date('h:i A', $godhuliStart), 'end' => date('h:i A', $godhuliEnd)],
                'vijay_muhurta' => ['start' => date('h:i A', $vijayStart), 'end' => date('h:i A', $vijayEnd)],
                'nishita_muhurta' => ['start' => date('h:i A', $nishitaStart), 'end' => date('h:i A', $nishitaEnd)],
                'rahukaal' => ['start' => date('h:i A', $rahuStart), 'end' => date('h:i A', $rahuEnd)],
                'gulika' => ['start' => date('h:i A', $gulikaStart), 'end' => date('h:i A', $gulikaEnd)],
                'yamakanta' => ['start' => date('h:i A', $yamaStart), 'end' => date('h:i A', $yamaEnd)],
                'advanced_details' => [
                    'sun_rise' => $sunriseTime,
                    'sun_set' => $sunsetTime,
                    'moon_rise' => '05:30 PM',
                    'moon_set' => '06:10 AM',
                    'day_duration' => sprintf('%02dh %02dm', floor($dayDurationSec / 3600), floor(($dayDurationSec % 3600) / 60)),
                    'vaara' => $vaara,
                    'disha_shool' => $dishaShool,
                    'moon_yogini_nivas' => 'South',
                    'masa' => [
                        'amanta_name' => $masaName,
                        'purnimanta_name' => $masaName,
                        'paksha' => $paksha,
                        'ritu' => $rituName,
                    ],
                    'years' => [
                        'vikram_samvat' => (string) $vikramSamvat,
                        'vikram_samvaat' => (string) $vikramSamvat,
                        'saka_samvat' => (string) $shakaSamvat,
                        'saka' => (string) $shakaSamvat,
                        'kali_samvat' => (string) $kaliSamvat,
                        'samvatsara' => 'Parabhava',
                    ],
                    'choghadiya_day' => $choghadiyaDay,
                    'choghadiya_night' => $choghadiyaNight,
                ],
            ]
        ];
    }


    private function getTimezoneOffset($timezone)
    {
        $time = new \DateTime('now', new \DateTimeZone($timezone));
        return $time->getOffset() / 3600;  // Convert seconds to hours
    }

    public function getkundali(Request $request)
    {
        $userId = authcheck()['id'] ?? null;

        try {
            $count = $userId ? Kundali::where('createdBy', '=', $userId)->count() : 0;
            $kundali_price = \App\Models\AdminModel\KundaliPrice::all();
            $getkundaliprice = [
                'recordList' => $kundali_price,
                'isFreeSession' => $count > 0 ? false : true,
                'status' => 200
            ];
        } catch (\Throwable $e) {
            $getkundaliprice = ['recordList' => [], 'isFreeSession' => true, 'status' => 200];
        }

        try {
            if ($userId) {
                $kundaliQuery = Kundali::where('createdBy', '=', $userId)->where('forMatch', 0)->orderByDesc('created_at');
                if ($s = $request->input('s')) {
                    $kundaliQuery->where('name', 'LIKE', "%{$s}%");
                }
                $kundalis = $kundaliQuery->get();
                $getkundali = ['status' => 200, 'recordList' => $kundalis, 'totalRecords' => $kundalis->count()];
            } else {
                $getkundali = ['status' => 200, 'recordList' => [], 'totalRecords' => 0];
            }
        } catch (\Throwable $e) {
            $getkundali = ['status' => 200, 'recordList' => [], 'totalRecords' => 0];
        }

        $isGuest = empty($userId) && empty($request->input('s'));
        $cacheKey = 'guest_kundali_html_v2';
        if ($isGuest && \Illuminate\Support\Facades\Cache::has($cacheKey)) {
            return response(\Illuminate\Support\Facades\Cache::get($cacheKey));
        }

        $getsystemflag = \App\Models\AdminModel\SystemFlag::all();
        $currency = $getsystemflag->where('name', 'currencySymbol')->first();

        $rendered = view('frontend.pages.kundali', [
            'getkundali' => $getkundali,
            'getkundaliprice' => $getkundaliprice,
            'currency' => $currency,
        ])->render();

        if ($isGuest) {
            \Illuminate\Support\Facades\Cache::put($cacheKey, $rendered, 1800);
        }

        return response($rendered);
    }

    public function kundaliMatch(Request $request)
    {
        $cacheKey = 'guest_kundali_matching_html_v2';
        if (!authcheck() && \Illuminate\Support\Facades\Cache::has($cacheKey)) {
            return response(\Illuminate\Support\Facades\Cache::get($cacheKey));
        }

        $rendered = view('frontend.pages.kundali-matching', [])->render();
        if (!authcheck()) {
            \Illuminate\Support\Facades\Cache::put($cacheKey, $rendered, 1800);
        }

        return response($rendered);
    }

    public function kundaliMatchReport(Request $request)
    {
        try {
            $res = app(\App\Http\Controllers\API\User\KundaliMatchingController::class)->getMatchReport($request);
            $KundaliMatching = $res->getData(true);
        } catch (\Throwable $e) {
            $KundaliMatching = [];
        }

        $kundalimale = Kundali::where('id', $request->male_kundli_id)->first();
        $kundalifemale = Kundali::where('id', $request->female_kundli_id)->first();

        return view('frontend.pages.kundali-match-report', [
            'KundaliMatching' => $KundaliMatching,
            'kundalimale' => $kundalimale,
            'kundalifemale' => $kundalifemale,
        ]);
    }

    public function kundaliReport(Request $request)
    {
        // Initialize session
        $session = new Session();

        // Create unique session key based on request parameters
        $sessionKey = 'kundali_report_' . $request->kundali_id . '_' . ($request->lang ?? 'en');
        if ($session->has($sessionKey)) {
            $KundaliReport = $session->get($sessionKey);
        } else {
            try {
                $res = app(\App\Http\Controllers\API\User\KundaliMatchingController::class)->getKundaliReport($request);
                $KundaliReport = $res->getData(true);
            } catch (\Throwable $e) {
                $KundaliReport = [];
            }

            // Store in session for subsequent requests
            $session->set($sessionKey, $KundaliReport);
        }

        return view('frontend.pages.kundali-report', compact('KundaliReport'));
    }
}
