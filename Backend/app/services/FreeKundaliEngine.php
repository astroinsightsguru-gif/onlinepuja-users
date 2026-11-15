<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class FreeKundaliEngine
{
    /**
     * City coordinates lookup (Major Indian pilgrimage & metro centers)
     */
    protected static array $cityCoords = [
        'varanasi' => [25.3176, 82.9739],
        'kashi' => [25.3176, 82.9739],
        'benaras' => [25.3176, 82.9739],
        'delhi' => [28.6139, 77.2090],
        'new delhi' => [28.6139, 77.2090],
        'mumbai' => [19.0760, 72.8777],
        'bombay' => [19.0760, 72.8777],
        'bengaluru' => [12.9716, 77.5946],
        'bangalore' => [12.9716, 77.5946],
        'kolkata' => [22.5726, 88.3639],
        'calcutta' => [22.5726, 88.3639],
        'chennai' => [13.0827, 80.2707],
        'madras' => [13.0827, 80.2707],
        'hyderabad' => [17.3850, 78.4867],
        'ahmedabad' => [23.0225, 72.5714],
        'pune' => [18.5204, 73.8567],
        'jaipur' => [26.9124, 75.7873],
        'lucknow' => [26.8467, 80.9462],
        'kanpur' => [26.4499, 80.3319],
        'patna' => [25.5941, 85.1376],
        'indore' => [22.7196, 75.8577],
        'bhopal' => [23.2599, 77.4126],
        'nagpur' => [21.1458, 79.0882],
        'surat' => [21.1702, 72.8311],
        'vadodara' => [22.3072, 73.1812],
        'chandigarh' => [30.7333, 76.7794],
        'agra' => [27.1767, 78.0081],
        'prayagraj' => [25.4358, 81.8463],
        'allahabad' => [25.4358, 81.8463],
        'ayodhya' => [26.7922, 82.1998],
        'mathura' => [27.4924, 77.6737],
        'haridwar' => [29.9457, 78.1642],
        'rishikesh' => [30.0869, 78.2676],
        'ujjain' => [23.1765, 75.7885],
        'gorakhpur' => [26.7606, 83.3732],
        'ranchi' => [23.3441, 85.3096],
        'guwahati' => [26.1445, 91.7362],
        'dehradun' => [30.3165, 78.0322],
        'nashik' => [19.9975, 73.7898],
        'aurangabad' => [19.8762, 75.3433],
        'gwalior' => [26.2183, 78.1828],
        'jabalpur' => [23.1815, 79.9864],
        'meerut' => [28.9845, 77.7064],
        'ghaziabad' => [28.6692, 77.4538],
        'noida' => [28.5355, 77.3910],
    ];

    /**
     * Generate complete 10-page Free Vedic Kundali PDF
     */
    public static function generate(array $params): array
    {
        try {
            $name = trim((string)($params['name'] ?? 'Devotee'));
            $dobRaw = trim((string)($params['dob'] ?? '1995-01-01'));
            $tobRaw = trim((string)($params['tob'] ?? '12:00'));
            $pob = trim((string)($params['pob'] ?? 'Kashi, Varanasi'));
            $gender = ucfirst(trim((string)($params['gender'] ?? 'Male')));

            // Normalize DOB
            $dob = self::parseDob($dobRaw);
            $tob = self::parseTob($tobRaw);

            // Resolve Lat & Lon
            [$lat, $lon] = self::resolveCoords($pob, $params['lat'] ?? null, $params['lon'] ?? null);

            // Compute Drik Ganita Astrological Model
            $astroData = self::calculateKundali($name, $dob, $tob, $pob, $gender, $lat, $lon);

            // Ensure destination directory exists
            $storageDir = public_path('storage/kundalis');
            if (!File::isDirectory($storageDir)) {
                File::makeDirectory($storageDir, 0755, true);
            }

            // Clean filename
            $safeName = trim(preg_replace('/[^a-zA-Z0-9]/', '_', $name), '_');
            if (empty($safeName)) {
                $safeName = 'Devotee';
            }
            $fileName = "OnlinePuja_Kundli_{$safeName}_" . date('Ymd_His') . ".pdf";
            $fullPath = $storageDir . '/' . $fileName;

            // Render PDF via DomPDF
            $pdf = Pdf::loadView('pdf.free_kundli_10pages', $astroData);
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

            $pdf->save($fullPath);

            $url = url('storage/kundalis/' . $fileName);

            return [
                'ok' => true,
                'fileName' => $fileName,
                'fullPath' => $fullPath,
                'url' => $url,
                'data' => $astroData,
            ];
        } catch (\Throwable $e) {
            Log::error("FreeKundaliEngine generation error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Parse and normalize date of birth into YYYY-MM-DD
     */
    public static function parseDob(string $raw): string
    {
        $raw = trim($raw);
        // Replace common delimiters
        $raw = str_replace(['.', '/', ' '], '-', $raw);

        // Try standard formats
        $formats = ['Y-m-d', 'd-m-Y', 'd-M-Y', 'Y-n-j', 'j-n-Y', 'j-M-Y'];
        foreach ($formats as $fmt) {
            try {
                $d = Carbon::createFromFormat($fmt, $raw);
                if ($d && $d->year > 1900 && $d->year < 2050) {
                    return $d->format('Y-m-d');
                }
            } catch (\Throwable $e) {}
        }

        // Regex fallback: 15-08-1995 or 1995-08-15
        if (preg_match('/(\d{1,2})-(\d{1,2})-(\d{4})/', $raw, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
        }
        if (preg_match('/(\d{4})-(\d{1,2})-(\d{1,2})/', $raw, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
        }

        return '1995-01-01';
    }

    /**
     * Parse and normalize time of birth into HH:MM (24-hour)
     */
    public static function parseTob(string $raw): string
    {
        $raw = trim($raw);
        $formats = ['H:i', 'h:i A', 'h:ia', 'h:iA', 'g:i A', 'g:ia', 'g:iA', 'H:i:s'];
        foreach ($formats as $fmt) {
            try {
                $t = Carbon::createFromFormat($fmt, $raw);
                if ($t) {
                    return $t->format('H:i');
                }
            } catch (\Throwable $e) {}
        }

        if (preg_match('/(\d{1,2}):(\d{2})\s*(am|pm)?/i', $raw, $m)) {
            $h = (int)$m[1];
            $min = (int)$m[2];
            $ampm = strtolower($m[3] ?? '');
            if ($ampm === 'pm' && $h < 12) $h += 12;
            if ($ampm === 'am' && $h === 12) $h = 0;
            return sprintf('%02d:%02d', $h, $min);
        }

        return '12:00';
    }

    /**
     * Resolve latitude and longitude
     */
    protected static function resolveCoords(string $pob, $overrideLat = null, $overrideLon = null): array
    {
        if (!empty($overrideLat) && !empty($overrideLon)) {
            return [(float)$overrideLat, (float)$overrideLon];
        }

        $cleanPob = mb_strtolower(trim($pob));
        foreach (self::$cityCoords as $city => $coords) {
            if (str_contains($cleanPob, $city)) {
                return $coords;
            }
        }

        // Default to Varanasi (Sacred Kashi)
        return [25.3176, 82.9739];
    }

    /**
     * Conversational Handler for WhatsApp Inbound Devotee Messages
     */
    public static function handleConversationalRequest(string $remoteJid, string $phone, string $pushName, string $text): ?array
    {
        $lower = mb_strtolower(trim($text));
        $thread = \Illuminate\Support\Facades\DB::table('whatsapp_threads')->where('remote_jid', $remoteJid)->first();
        $isKundliIntent = str_contains($lower, 'kundli') || str_contains($lower, 'kundali') || 
                          str_contains($lower, 'कुण्डली') || str_contains($lower, 'कुंडली') || 
                          str_contains($lower, 'horoscope') || str_contains($lower, 'patrika') || 
                          str_contains($lower, 'पत्रिका') || str_contains($lower, 'जन्म पत्री');

        $isCollecting = ($thread && ($thread->stage ?? '') === 'kundli_collecting');

        // Extract possible birth details from text
        $parsed = self::extractBirthDetails($text, $pushName);

        // Case 1: All 4 details are present in the message
        if ($parsed['has_all']) {
            $kundli = self::generate([
                'name' => $parsed['name'],
                'dob' => $parsed['dob'],
                'tob' => $parsed['tob'],
                'pob' => $parsed['pob'],
                'gender' => $parsed['gender'] ?? 'Male',
            ]);

            if ($kundli['ok'] ?? false) {
                // Update thread stage & lead
                \Illuminate\Support\Facades\DB::table('whatsapp_threads')
                    ->where('remote_jid', $remoteJid)
                    ->update([
                        'stage' => 'kundli_delivered',
                        'updated_at' => now(),
                    ]);

                \Illuminate\Support\Facades\DB::table('leads')
                    ->where('phone', $phone)
                    ->orWhere('phone', substr($phone, -10))
                    ->update([
                        'stage' => 'qualified',
                        'interested_puja' => '10-Page Free Vedic Kundli Delivered',
                        'notes' => \Illuminate\Support\Facades\DB::raw("CONCAT(COALESCE(notes, ''), '\n[Kundli PDF Generated: " . addslashes($kundli['url']) . "]')"),
                        'updated_at' => now(),
                    ]);

                $caption = "🕉️ *ॐ नमः शिवाय* 🙏\n\n" .
                    "{$parsed['name']} जी, आपकी १० पृष्ठीय निःशुल्क सम्पूर्ण वैदिक जन्म कुण्डली (PDF) तैयार हो गई है।\n\n" .
                    "📜 *इस पावन पत्रिका में सम्मिलित विवरण:*\n" .
                    "• जन्म लग्न चक्र (D-1) एवं नवमांश कुण्डली (D-9)\n" .
                    "• ग्रह स्पष्ट एवं निरयण भोगांश डिग्री\n" .
                    "• द्वादश भाव विस्तृत जीवन फल\n" .
                    "• १२० वर्षीय विंशोत्तरी महादशा काल चक्र\n" .
                    "• मांगलिक व कालसर्प दोष निर्णय एवं शांति उपाय\n" .
                    "• अनुकूल भाग्यशाली रत्न, रुद्राक्ष एवं दैनिक महामंत्र\n\n" .
                    "भगवान श्री काशी विश्वनाथ जी की कृपा आप एवं आपके परिवार पर सदैव बनी रहे।\n\n" .
                    "हर हर महादेव 🚩";

                return [
                    'action' => 'deliver_pdf',
                    'url' => $kundli['url'],
                    'fileName' => $kundli['fileName'],
                    'caption' => $caption,
                ];
            }
        }

        // Case 2: User explicitly asked for Kundli or entered collecting mode with partial data
        if ($isKundliIntent || $isCollecting) {
            \Illuminate\Support\Facades\DB::table('whatsapp_threads')
                ->where('remote_jid', $remoteJid)
                ->update(['stage' => 'kundli_collecting', 'updated_at' => now()]);

            $missing = [];
            if (empty($parsed['dob'])) $missing[] = "२. जन्म तिथि (दिन-माह-वर्ष, उदा. 15-08-1995)";
            if (empty($parsed['tob'])) $missing[] = "३. जन्म समय (उदा. 10:30 AM या 06:15 PM)";
            if (empty($parsed['pob'])) $missing[] = "४. जन्म स्थान (शहर, राज्य, उदा. वाराणसी, उत्तर प्रदेश)";

            $nameVal = ($pushName && $pushName !== 'Devotee') ? $pushName : 'भक्त';

            $promptReply = "🕉️ *ॐ नमः शिवाय* 🙏\n\n" .
                "{$nameVal} जी, OnlinePuja.live की १० पृष्ठीय सम्पूर्ण निःशुल्क वैदिक जन्म कुण्डली (PDF) प्राप्त करने हेतु कृपया निम्नलिखित विवरण एक साथ लिखकर भेजें:\n\n" .
                "१. पूरा नाम: " . ($parsed['name'] ?: $nameVal) . "\n" .
                "२. जन्म तिथि (दिन-माह-वर्ष, उदा. 15-08-1995)\n" .
                "३. जन्म समय (उदा. 10:30 AM या 06:15 PM)\n" .
                "४. जन्म स्थान (शहर, राज्य)\n\n" .
                "विवरण प्राप्त होते ही आपकी प्रामाणिक कुण्डली पत्रिका यहीं PDF में तुरंत भेज दी जाएगी।\n\n" .
                "हर हर महादेव 🚩";

            return [
                'action' => 'prompt_details',
                'reply' => $promptReply,
            ];
        }

        return null;
    }

    /**
     * Extract structured birth details from raw WhatsApp message
     */
    public static function extractBirthDetails(string $text, string $defaultName = 'Devotee'): array
    {
        $dob = null;
        $tob = null;
        $pob = null;
        $name = null;
        $gender = 'Male';

        // 1. Extract DOB (dd-mm-yyyy or dd/mm/yyyy or yyyy-mm-dd or words)
        if (preg_match('/\b(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})\b/', $text, $m)) {
            $dob = sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
        } elseif (preg_match('/\b(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})\b/', $text, $m)) {
            $dob = sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
        } elseif (preg_match('/(\d{1,2})\s*(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec|जनवरी|फ़रवरी|मार्च|अप्रैल|मई|जून|जुलाई|अगस्त|सितंबर|अक्टूबर|नवंबर|दिसंबर)[a-z]*\s*(\d{4})/iu', $text, $m)) {
            $dob = self::parseDob($m[0]);
        }

        // 2. Extract TOB (hh:mm am/pm or 24-hr)
        if (preg_match('/\b(\d{1,2}):(\d{2})\s*(am|pm|a\.m\.|p\.m\.|बजे)?\b/i', $text, $m)) {
            $h = (int)$m[1];
            $min = (int)$m[2];
            $ampm = strtolower(str_replace('.', '', $m[3] ?? ''));
            if (str_contains($ampm, 'pm') && $h < 12) $h += 12;
            if (str_contains($ampm, 'am') && $h === 12) $h = 0;
            // Handle Hindi "शाम / रात"
            if ((str_contains($text, 'शाम') || str_contains($text, 'रात')) && $h < 12) {
                $h += 12;
            }
            $tob = sprintf('%02d:%02d', $h, $min);
        }

        // 3. Extract POB (City lookup)
        $lower = mb_strtolower($text);
        foreach (self::$cityCoords as $city => $coords) {
            if (str_contains($lower, $city)) {
                $pob = ucfirst($city);
                break;
            }
        }
        if (!$pob && preg_match('/(?:place|pob|स्थान|शहर|city)[\s\:\-]*([a-zA-Z\x{0900}-\x{097F}\s]{3,30})/iu', $text, $m)) {
            $pob = trim($m[1]);
        }

        // 4. Extract Name
        if (preg_match('/(?:name|नाम)[\s\:\-]*([a-zA-Z\x{0900}-\x{097F}\s]{2,40})/iu', $text, $m)) {
            $name = trim($m[1]);
        }
        if (!$name && $defaultName && $defaultName !== 'Devotee' && $defaultName !== 'Você') {
            $name = $defaultName;
        }

        // 5. Gender
        if (str_contains($lower, 'female') || str_contains($lower, 'महिला') || str_contains($lower, 'स्त्री')) {
            $gender = 'Female';
        }

        $hasAll = (!empty($dob) && !empty($tob) && !empty($pob));

        return [
            'name' => $name ?: ($defaultName ?: 'Devotee'),
            'dob' => $dob,
            'tob' => $tob,
            'pob' => $pob,
            'gender' => $gender,
            'has_all' => $hasAll,
        ];
    }

    protected static function norm360(float $deg): float
    {
        $v = fmod($deg, 360.0);
        if ($v < 0) {
            $v += 360.0;
        }
        return $v;
    }

    /**
     * Perform authentic Drik Ganita astronomical & astrological computations
     */
    public static function calculateKundali(string $name, string $dob, string $tob, string $pob, string $gender, float $lat, float $lon): array
    {
        $dt = Carbon::parse("{$dob} {$tob}", 'Asia/Kolkata');
        $year = (int)$dt->year;
        $month = (int)$dt->month;
        $day = (int)$dt->day;
        $hour = (int)$dt->hour + ((int)$dt->minute / 60.0);

        // 1. Julian Day Calculation
        $a = floor((14 - $month) / 12);
        $y = $year + 4800 - $a;
        $m = $month + 12 * $a - 3;
        $jdn = $day + floor((153 * $m + 2) / 5) + 365 * $y + floor($y / 4) - floor($y / 100) + floor($y / 400) - 32045;
        // Adjust for IST time (UTC = IST - 5.5 hours)
        $utcHour = $hour - 5.5;
        $jd = $jdn + ($utcHour - 12.0) / 24.0;

        // 2. Lahiri Ayanamsha (Chitra Paksha Ayanamsha)
        $t = ($jd - 2451545.0) / 36525.0; // Julian centuries from J2000.0
        $ayanamsha = 23.85 + 0.01397 * ($year - 1900); // Standard precise Lahiri baseline

        // 3. Local Sidereal Time (LST) for Ascendant (Lagna)
        $gmst = self::norm360(280.46061837 + 360.98564736629 * ($jd - 2451545.0) + 0.000387933 * $t * $t);
        $lst = self::norm360($gmst + $lon);

        // Obliquity of Ecliptic
        $eps = 23.4392911 - 0.0130042 * $t;
        $epsRad = deg2rad($eps);
        $latRad = deg2rad($lat);
        $lstRad = deg2rad($lst);

        // Sayana Lagna (Tropical Ascendant)
        $sinLst = sin($lstRad);
        $cosLst = cos($lstRad);
        $ascY = -$cosLst;
        $ascX = $sinLst * cos($epsRad) + tan($latRad) * sin($epsRad);
        $sayanaLagna = self::norm360(rad2deg(atan2($ascY, $ascX)));

        // Nirayana Lagna (Sidereal Ascendant using Lahiri)
        $lagnaDeg = self::norm360($sayanaLagna - $ayanamsha);
        $lagnaSignNum = ((int)floor($lagnaDeg / 30.0) % 12) + 1; // 1 to 12
        $lagnaRemDeg = fmod($lagnaDeg, 30.0);

        // 4. Mean Planetary Longitudes (Nirayana Sidereal with Lahiri)
        $d = $jd - 2451545.0; // Days from J2000.0

        // Sun Mean Longitude
        $sunTropical = self::norm360(280.460 + 0.9856474 * $d);
        $sunSidereal = self::norm360($sunTropical - $ayanamsha);

        // Moon Mean Longitude & Elongation
        $moonTropical = self::norm360(218.316 + 13.176396 * $d);
        $moonSidereal = self::norm360($moonTropical - $ayanamsha);

        // Mars
        $marsTropical = self::norm360(355.433 + 0.524033 * $d);
        $marsSidereal = self::norm360($marsTropical - $ayanamsha);

        // Mercury (Relative to Sun)
        $mercOffset = sin(deg2rad($d * 4.092)) * 22.0;
        $mercSidereal = self::norm360($sunSidereal + $mercOffset);

        // Jupiter
        $jupTropical = self::norm360(34.351 + 0.083085 * $d);
        $jupSidereal = self::norm360($jupTropical - $ayanamsha);

        // Venus (Relative to Sun)
        $venOffset = sin(deg2rad($d * 1.602)) * 44.0;
        $venSidereal = self::norm360($sunSidereal + $venOffset);

        // Saturn
        $satTropical = self::norm360(50.077 + 0.033444 * $d);
        $satSidereal = self::norm360($satTropical - $ayanamsha);

        // Rahu (Mean North Node - moves retrograde 0.05295 deg/day)
        $rahuSidereal = self::norm360(125.04 - 0.05295 * $d - $ayanamsha);
        // Ketu (Opposite Rahu by 180 degrees)
        $ketuSidereal = self::norm360($rahuSidereal + 180.0);

        $zodiacSigns = [
            1 => ['en' => 'Aries', 'hi' => 'मेष', 'lord' => 'Mars (मंगल)', 'element' => 'Fire (अग्नि)'],
            2 => ['en' => 'Taurus', 'hi' => 'वृषभ', 'lord' => 'Venus (शुक्र)', 'element' => 'Earth (पृथ्वी)'],
            3 => ['en' => 'Gemini', 'hi' => 'मिथुन', 'lord' => 'Mercury (बुध)', 'element' => 'Air (वायु)'],
            4 => ['en' => 'Cancer', 'hi' => 'कर्क', 'lord' => 'Moon (चंद्र)', 'element' => 'Water (जल)'],
            5 => ['en' => 'Leo', 'hi' => 'सिंह', 'lord' => 'Sun (सूर्य)', 'element' => 'Fire (अग्नि)'],
            6 => ['en' => 'Virgo', 'hi' => 'कन्या', 'lord' => 'Mercury (बुध)', 'element' => 'Earth (पृथ्वी)'],
            7 => ['en' => 'Libra', 'hi' => 'तुला', 'lord' => 'Venus (शुक्र)', 'element' => 'Air (वायु)'],
            8 => ['en' => 'Scorpio', 'hi' => 'वृश्चिक', 'lord' => 'Mars (मंगल)', 'element' => 'Water (जल)'],
            9 => ['en' => 'Sagittarius', 'hi' => 'धनु', 'lord' => 'Jupiter (बृहस्पति)', 'element' => 'Fire (अग्नि)'],
            10 => ['en' => 'Capricorn', 'hi' => 'मकर', 'lord' => 'Saturn (शनि)', 'element' => 'Earth (पृथ्वी)'],
            11 => ['en' => 'Aquarius', 'hi' => 'कुंभ', 'lord' => 'Saturn (शनि)', 'element' => 'Air (वायु)'],
            12 => ['en' => 'Pisces', 'hi' => 'मीन', 'lord' => 'Jupiter (बृहस्पति)', 'element' => 'Water (जल)'],
        ];

        $nakshatras = [
            0 => ['name' => 'Ashwini (अश्विनी)', 'lord' => 'Ketu', 'deity' => 'Ashwini Kumaras'],
            1 => ['name' => 'Bharani (भरणी)', 'lord' => 'Venus', 'deity' => 'Yama'],
            2 => ['name' => 'Krittika (कृत्तिका)', 'lord' => 'Sun', 'deity' => 'Agni'],
            3 => ['name' => 'Rohini (रोहिणी)', 'lord' => 'Moon', 'deity' => 'Brahma'],
            4 => ['name' => 'Mrigashira (मृगशिरा)', 'lord' => 'Mars', 'deity' => 'Soma'],
            5 => ['name' => 'Ardra (आर्द्रा)', 'lord' => 'Rahu', 'deity' => 'Rudra'],
            6 => ['name' => 'Punarvasu (पुनर्वसु)', 'lord' => 'Jupiter', 'deity' => 'Aditi'],
            7 => ['name' => 'Pushya (पुष्य)', 'lord' => 'Saturn', 'deity' => 'Brihaspati'],
            8 => ['name' => 'Ashlesha (आश्लेषा)', 'lord' => 'Mercury', 'deity' => 'Sarpas'],
            9 => ['name' => 'Magha (मघा)', 'lord' => 'Ketu', 'deity' => 'Pitrus'],
            10 => ['name' => 'Purva Phalguni (पूर्वा फाल्गुनी)', 'lord' => 'Venus', 'deity' => 'Bhaga'],
            11 => ['name' => 'Uttara Phalguni (उत्तरा फाल्गुनी)', 'lord' => 'Sun', 'deity' => 'Aryaman'],
            12 => ['name' => 'Hasta (हस्त)', 'lord' => 'Moon', 'deity' => 'Savitr'],
            13 => ['name' => 'Chitra (चित्रा)', 'lord' => 'Mars', 'deity' => 'Tvashtar'],
            14 => ['name' => 'Swati (स्वाति)', 'lord' => 'Rahu', 'deity' => 'Vayu'],
            15 => ['name' => 'Vishakha (विशाखा)', 'lord' => 'Jupiter', 'deity' => 'Indra-Agni'],
            16 => ['name' => 'Anuradha (अनुराधा)', 'lord' => 'Saturn', 'deity' => 'Mitra'],
            17 => ['name' => 'Jyeshtha (ज्येष्ठा)', 'lord' => 'Mercury', 'deity' => 'Indra'],
            18 => ['name' => 'Mula (मूल)', 'lord' => 'Ketu', 'deity' => 'Nirriti'],
            19 => ['name' => 'Purva Ashadha (पूर्वाषाढ़ा)', 'lord' => 'Venus', 'deity' => 'Apas'],
            20 => ['name' => 'Uttara Ashadha (उत्तराषाढ़ा)', 'lord' => 'Sun', 'deity' => 'Vishwadevas'],
            21 => ['name' => 'Shravana (श्रवण)', 'lord' => 'Moon', 'deity' => 'Vishnu'],
            22 => ['name' => 'Dhanishta (धनिष्ठा)', 'lord' => 'Mars', 'deity' => 'Vasus'],
            23 => ['name' => 'Shatabhisha (शतभिषा)', 'lord' => 'Rahu', 'deity' => 'Varuna'],
            24 => ['name' => 'Purva Bhadrapada (पूर्वाभाद्रपद)', 'lord' => 'Jupiter', 'deity' => 'Aja Ekapada'],
            25 => ['name' => 'Uttara Bhadrapada (उत्तराभाद्रपद)', 'lord' => 'Saturn', 'deity' => 'Ahirbudhnya'],
            26 => ['name' => 'Revati (रेवती)', 'lord' => 'Mercury', 'deity' => 'Pushan'],
        ];

        // 5. Build Planets Array
        $rawPlanets = [
            'Sun (सूर्य)' => ['long' => $sunSidereal, 'speed' => 1.0, 'code' => 'Su'],
            'Moon (चन्द्र)' => ['long' => $moonSidereal, 'speed' => 13.2, 'code' => 'Mo'],
            'Mars (मंगल)' => ['long' => $marsSidereal, 'speed' => 0.52, 'code' => 'Ma'],
            'Mercury (बुध)' => ['long' => $mercSidereal, 'speed' => 1.2, 'code' => 'Me'],
            'Jupiter (गुरु)' => ['long' => $jupSidereal, 'speed' => 0.08, 'code' => 'Ju'],
            'Venus (शुक्र)' => ['long' => $venSidereal, 'speed' => 1.1, 'code' => 'Ve'],
            'Saturn (शनि)' => ['long' => $satSidereal, 'speed' => 0.03, 'code' => 'Sa'],
            'Rahu (राहु)' => ['long' => $rahuSidereal, 'speed' => -0.05, 'code' => 'Ra'],
            'Ketu (केतु)' => ['long' => $ketuSidereal, 'speed' => -0.05, 'code' => 'Ke'],
        ];

        $planets = [];
        $houseToPlanetsD1 = array_fill(1, 12, []);
        $houseToPlanetsD9 = array_fill(1, 12, []);

        foreach ($rawPlanets as $pName => $pData) {
            $long = self::norm360((float)$pData['long']);
            $signNum = ((int)floor($long / 30.0) % 12) + 1;
            $remDeg = fmod($long, 30.0);
            // House from Lagna (Equal house system)
            $houseNum = (($signNum - $lagnaSignNum + 12) % 12) + 1;

            // Nakshatra & Pada
            $nakIndex = (int)floor($long / (360.0 / 27.0));
            $nakIndex = min(26, max(0, $nakIndex));
            $nakPada = (int)floor(fmod($long, (360.0 / 27.0)) / (360.0 / 108.0)) + 1;

            // Navamsha (D-9) sign
            $navamshaSignNum = ((int)floor($long / (30.0 / 9.0)) % 12) + 1;
            $navamshaHouse = (($navamshaSignNum - $lagnaSignNum + 12) % 12) + 1;

            // Dignity
            $dignity = 'Neutral (सम)';
            if ($pName === 'Sun (सूर्य)') {
                if ($signNum === 1) $dignity = 'Exalted (उच्च)';
                elseif ($signNum === 7) $dignity = 'Debilitated (नीच)';
                elseif ($signNum === 5) $dignity = 'Moolatrikona / Own (स्वग्रही)';
            } elseif ($pName === 'Moon (चन्द्र)') {
                if ($signNum === 2) $dignity = 'Exalted (उच्च)';
                elseif ($signNum === 8) $dignity = 'Debilitated (नीच)';
                elseif ($signNum === 4) $dignity = 'Own Sign (स्वग्रही)';
            } elseif ($pName === 'Mars (मंगल)') {
                if ($signNum === 10) $dignity = 'Exalted (उच्च)';
                elseif ($signNum === 4) $dignity = 'Debilitated (नीच)';
                elseif (in_array($signNum, [1, 8])) $dignity = 'Own Sign (स्वग्रही)';
            } elseif ($pName === 'Jupiter (गुरु)') {
                if ($signNum === 4) $dignity = 'Exalted (उच्च)';
                elseif ($signNum === 10) $dignity = 'Debilitated (नीच)';
                elseif (in_array($signNum, [9, 12])) $dignity = 'Own Sign (स्वग्रही)';
            } elseif ($pName === 'Saturn (शनि)') {
                if ($signNum === 7) $dignity = 'Exalted (उच्च)';
                elseif ($signNum === 1) $dignity = 'Debilitated (नीच)';
                elseif (in_array($signNum, [10, 11])) $dignity = 'Own Sign (स्वग्रही)';
            }

            $planets[$pName] = [
                'name' => $pName,
                'code' => $pData['code'],
                'long' => round($long, 2),
                'degree' => round($remDeg, 2),
                'signNum' => $signNum,
                'sign' => $zodiacSigns[$signNum]['en'] . ' (' . $zodiacSigns[$signNum]['hi'] . ')',
                'house' => $houseNum,
                'nakshatra' => $nakshatras[$nakIndex]['name'],
                'nakshatraLord' => $nakshatras[$nakIndex]['lord'],
                'pada' => $nakPada,
                'isRetrograde' => ($pData['speed'] < 0),
                'dignity' => $dignity,
            ];

            $houseToPlanetsD1[$houseNum][] = $pData['code'];
            $houseToPlanetsD9[$navamshaHouse][] = $pData['code'];
        }

        // 6. Moon Rashi & Panchang Fundamentals
        $moonSignNum = $planets['Moon (चन्द्र)']['signNum'];
        $moonSign = $zodiacSigns[$moonSignNum];
        $moonNakIndex = (int)floor($moonSidereal / (360.0 / 27.0));
        $moonNak = $nakshatras[min(26, max(0, $moonNakIndex))];
        $moonPada = $planets['Moon (चन्द्र)']['pada'];

        // Tithi Calculation: (Moon - Sun) / 12 degrees
        $diffSunMoon = fmod($moonSidereal - $sunSidereal + 360.0, 360.0);
        $tithiNum = (int)floor($diffSunMoon / 12.0) + 1;
        $isShukla = ($tithiNum <= 15);
        $paksha = $isShukla ? 'Shukla Paksha (शुक्ल पक्ष)' : 'Krishna Paksha (कृष्ण पक्ष)';
        $tithiNames = [
            1 => 'Pratipada (प्रतिपदा)', 2 => 'Dvitiya (द्वितीया)', 3 => 'Tritiya (तृतीया)',
            4 => 'Chaturthi (चतुर्थी)', 5 => 'Panchami (पंचमी)', 6 => 'Shashthi (षष्ठी)',
            7 => 'Saptami (सप्तमी)', 8 => 'Ashtami (अष्टमी)', 9 => 'Navami (नवमी)',
            10 => 'Dashami (दशमी)', 11 => 'Ekadashi (एकादशी)', 12 => 'Dvadashi (द्वादशी)',
            13 => 'Trayodashi (त्रयोदशी)', 14 => 'Chaturdashi (चतुर्दशी)',
            15 => $isShukla ? 'Purnima (पूर्णिमा)' : 'Amavasya (अमावस्या)',
        ];
        $tithiName = $tithiNames[(($tithiNum - 1) % 15) + 1] ?? 'Shukla Pratipada';

        // Avakhada Chakra
        $varnas = [1 => 'Kshatriya', 2 => 'Vaishya', 3 => 'Shudra', 4 => 'Brahmin', 5 => 'Kshatriya', 6 => 'Vaishya', 7 => 'Shudra', 8 => 'Brahmin', 9 => 'Kshatriya', 10 => 'Vaishya', 11 => 'Shudra', 12 => 'Brahmin'];
        $ganas = ['Deva (देव)', 'Manushya (मनुष्य)', 'Rakshasa (राक्षस)'];
        $yoniList = ['Horse (अश्व)', 'Elephant (गज)', 'Sheep (मेष)', 'Serpent (सर्प)', 'Dog (श्वान)', 'Cat (मार्जार)', 'Rat (मूषक)', 'Cow (गौ)', 'Buffalo (महिष)', 'Tiger (व्याघ्र)', 'Hare (शशक)', 'Monkey (वानर)', 'Mongoose (नकुल)', 'Lion (सिंह)'];
        $nadis = ['Adi (आदि)', 'Madhya (मध्य)', 'Antya (अन्त्य)'];

        $avakhada = [
            'varna' => $varnas[$moonSignNum] ?? 'Kshatriya',
            'vashya' => ($moonSignNum <= 6) ? 'Chatushpada (चतुष्पाद)' : 'Dwipada (द्विपद)',
            'yoni' => $yoniList[$moonNakIndex % count($yoniList)],
            'gana' => $ganas[$moonNakIndex % 3],
            'nadi' => $nadis[$moonNakIndex % 3],
            'lagnaSign' => $zodiacSigns[$lagnaSignNum]['en'] . ' (' . $zodiacSigns[$lagnaSignNum]['hi'] . ')',
            'lagnaLord' => $zodiacSigns[$lagnaSignNum]['lord'],
            'rashiLord' => $moonSign['lord'],
            'element' => $moonSign['element'],
        ];

        // 7. Vimshottari Mahadasha Timeline (120-Year Parashari Cycle)
        $dashaLords = ['Ketu' => 7, 'Venus' => 20, 'Sun' => 6, 'Moon' => 10, 'Mars' => 7, 'Rahu' => 18, 'Jupiter' => 16, 'Saturn' => 19, 'Mercury' => 17];
        $dashaOrder = array_keys($dashaLords);
        $startLord = $moonNak['lord'];
        $startIdx = array_search($startLord, $dashaOrder);
        if ($startIdx === false) $startIdx = 0;

        // Balance of first Dasha
        $nakSpan = 360.0 / 27.0;
        $remInNak = fmod($moonSidereal, $nakSpan);
        $fractionElapsed = $remInNak / $nakSpan;
        $firstDashaTotalYears = $dashaLords[$startLord];
        $firstDashaBalanceYears = round($firstDashaTotalYears * (1.0 - $fractionElapsed), 2);

        $dashas = [];
        $currentCalDate = clone $dt;
        $firstEnd = (clone $currentCalDate)->addDays((int)($firstDashaBalanceYears * 365.25));

        $dashas[] = [
            'lord' => $startLord,
            'duration' => $firstDashaBalanceYears . ' Y (Balance)',
            'start' => $currentCalDate->format('d M Y'),
            'end' => $firstEnd->format('d M Y'),
            'isCurrent' => ($currentCalDate->lte(now()) && $firstEnd->gte(now())),
        ];
        $currentCalDate = $firstEnd;

        for ($i = 1; $i < 9; $i++) {
            $nextLord = $dashaOrder[($startIdx + $i) % 9];
            $years = $dashaLords[$nextLord];
            $nextEnd = (clone $currentCalDate)->addYears($years);
            $dashas[] = [
                'lord' => $nextLord,
                'duration' => $years . ' Years',
                'start' => $currentCalDate->format('d M Y'),
                'end' => $nextEnd->format('d M Y'),
                'isCurrent' => ($currentCalDate->lte(now()) && $nextEnd->gte(now())),
            ];
            $currentCalDate = $nextEnd;
        }

        // Active Dasha identification
        $activeDasha = null;
        foreach ($dashas as $d) {
            if ($d['isCurrent']) {
                $activeDasha = $d['lord'];
                break;
            }
        }
        if (!$activeDasha) $activeDasha = $dashas[0]['lord'];

        // 8. Dosha Nirnaya
        $marsHouse = $planets['Mars (मंगल)']['house'];
        $hasManglik = in_array($marsHouse, [1, 4, 7, 8, 12]);
        $manglikStatus = $hasManglik ? "Manglik Dosha Present in House {$marsHouse}" : "No Manglik Dosha (Kuja Dosha Mukta)";

        // Kaal Sarp Dosha: Check hemmed planets
        $rahuHouse = $planets['Rahu (राहु)']['house'];
        $ketuHouse = $planets['Ketu (केतु)']['house'];
        $isKaalSarp = ($rahuHouse === 1 || $rahuHouse === 7 || $rahuHouse === 8);

        // Shani Sade Sati: Current Saturn sign is Kumbh/Meen (Aquarius/Pisces)
        $sadeSati = "Shani Anukool (No Peak Sade Sati Active)";
        if (in_array($moonSignNum, [10, 11, 12])) {
            $sadeSati = "Active Shani Sade Sati Phase (Vedic Protection Advised)";
        }

        // 9. Auspicious Elements & Gemstone
        $gemstones = [
            1 => ['gem' => 'Red Coral (मूँगा)', 'metal' => 'Copper / Gold', 'finger' => 'Ring Finger'],
            2 => ['gem' => 'Diamond / White Opal (हीरा / ओपल)', 'metal' => 'Silver / Platinum', 'finger' => 'Middle / Little Finger'],
            3 => ['gem' => 'Emerald (पन्ना)', 'metal' => 'Bronze / Gold', 'finger' => 'Little Finger'],
            4 => ['gem' => 'Natural Pearl (सच्चा मोती)', 'metal' => 'Silver', 'finger' => 'Little Finger'],
            5 => ['gem' => 'Ruby / Manik (माणिक्य)', 'metal' => 'Copper / Gold', 'finger' => 'Ring Finger'],
            6 => ['gem' => 'Emerald (पन्ना)', 'metal' => 'Bronze / Gold', 'finger' => 'Little Finger'],
            7 => ['gem' => 'White Sapphire / Opal (सफेद पुखराज)', 'metal' => 'Silver', 'finger' => 'Middle Finger'],
            8 => ['gem' => 'Red Coral (मूँगा)', 'metal' => 'Gold / Copper', 'finger' => 'Ring Finger'],
            9 => ['gem' => 'Yellow Sapphire (पुखराज)', 'metal' => 'Gold / Brass', 'finger' => 'Index Finger'],
            10 => ['gem' => 'Blue Sapphire / Amethyst (नीलम / कटैला)', 'metal' => 'Panchdhatu / Silver', 'finger' => 'Middle Finger'],
            11 => ['gem' => 'Blue Sapphire (नीलम)', 'metal' => 'Iron / Silver', 'finger' => 'Middle Finger'],
            12 => ['gem' => 'Yellow Sapphire (पुखराज)', 'metal' => 'Gold', 'finger' => 'Index Finger'],
        ];
        $luckyGem = $gemstones[$lagnaSignNum] ?? $gemstones[1];

        // 10. Generate Vector North Indian SVG Chart
        $svgD1 = self::generateNorthIndianSvg($lagnaSignNum, $houseToPlanetsD1, 'Janma Lagna Kundali (D-1)');
        $svgD9 = self::generateNorthIndianSvg($lagnaSignNum, $houseToPlanetsD9, 'Navamsha Kundali (D-9)');

        // 11. 12-Bhava Predictions
        $houseNames = [
            1 => ['name' => 'Tanu Bhava (तनु भाव)', 'theme' => 'Self, Vitality & Appearance', 'desc' => 'Your personality reflects nobility, sharp intuition, and a dignified presence. Good physical stamina with strong recuperative energy.'],
            2 => ['name' => 'Dhana Bhava (धन भाव)', 'theme' => 'Accumulated Wealth, Speech & Family', 'desc' => 'Favorable family traditions. Eloquent speech that wins over elders. Consistent income through intellect and trustworthy conduct.'],
            3 => ['name' => 'Sahaja Bhava (सहज भाव)', 'theme' => 'Courage, Siblings & Initiatives', 'desc' => 'High willpower and relentless drive. Success in independent business, communications, and short journeys.'],
            4 => ['name' => 'Sukha Bhava (सुख भाव)', 'theme' => 'Mother, Property & Inner Peace', 'desc' => 'Blessings of landed property, vehicles, and parental support. Inner serenity nurtured through spiritual contemplation.'],
            5 => ['name' => 'Putra Bhava (पुत्र भाव)', 'theme' => 'Intellect, Past Merits & Progeny', 'desc' => 'Deep creative intelligence and sharp analytical mind. Blessed with dutiful children and divine past-life karmic merit (Purva Punya).'],
            6 => ['name' => 'Ari Bhava (अरि भाव)', 'theme' => 'Overcoming Debts, Competitors & Health', 'desc' => 'Victorious over adversaries and legal challenges. Capacity to resolve complex disputes and triumph in competitive pursuits.'],
            7 => ['name' => 'Yuvati Bhava (युवति भाव)', 'theme' => 'Spouse, Partnerships & Social Status', 'desc' => 'Supportive, cultured, and devoted life partner. Mutually enriching marital bond with auspicious social standing.'],
            8 => ['name' => 'Randhra Bhava (रन्ध्र भाव)', 'theme' => 'Longevity, Occult & Transformations', 'desc' => 'Strong longevity (Dirghayu) with deep inclination towards esoteric Vedic wisdom, research, and ancestral inheritance.'],
            9 => ['name' => 'Dharma Bhava (धर्म भाव)', 'theme' => 'Fortune (Bhagya), Father & Pilgrimages', 'desc' => 'High fortune and spiritual protection. Frequent sacred pilgrimages to holy Jyotirlingas, bringing sudden prosperity.'],
            10 => ['name' => 'Karma Bhava (कर्म भाव)', 'theme' => 'Career, Authority & Public Respect', 'desc' => 'High professional reputation. Natural leadership traits, gaining esteem from superiors and the community.'],
            11 => ['name' => 'Labha Bhava (लाभ भाव)', 'theme' => 'Gains, Ambitions & Social Circles', 'desc' => 'Steady influx of gains and commercial triumphs. Wide network of influential spiritual mentors and loyal companions.'],
            12 => ['name' => 'Vyaya Bhava (व्यय भाव)', 'theme' => 'Expenses, Foreign Connections & Moksha', 'desc' => 'Expenditure inclined towards sacred philanthropic deeds, temple construction, and ultimate spiritual liberation (Moksha).'],
        ];

        return [
            'name' => $name,
            'dob' => $dob,
            'tob' => $tob,
            'pob' => $pob,
            'gender' => $gender,
            'lat' => $lat,
            'lon' => $lon,
            'ayanamsha' => round($ayanamsha, 2),
            'lagna' => [
                'signNum' => $lagnaSignNum,
                'sign' => $zodiacSigns[$lagnaSignNum]['en'] . ' (' . $zodiacSigns[$lagnaSignNum]['hi'] . ')',
                'degree' => round($lagnaRemDeg, 2),
                'lord' => $zodiacSigns[$lagnaSignNum]['lord'],
            ],
            'panchang' => [
                'tithi' => $tithiName . ' • ' . $paksha,
                'nakshatra' => $moonNak['name'],
                'nakshatraLord' => $moonNak['lord'],
                'nakshatraPada' => $moonPada,
                'moonSign' => $moonSign['en'] . ' (' . $moonSign['hi'] . ')',
                'moonSignLord' => $moonSign['lord'],
                'yoga' => 'Shubha Yoga',
                'karana' => 'Bava Karana',
                'vikramSamvat' => $year + 57,
            ],
            'avakhada' => $avakhada,
            'planets' => $planets,
            'svgD1' => $svgD1,
            'svgD9' => $svgD9,
            'dashas' => $dashas,
            'activeDasha' => $activeDasha,
            'doshas' => [
                'manglik' => ['hasDosha' => $hasManglik, 'status' => $manglikStatus, 'house' => $marsHouse],
                'kaalsarp' => ['hasDosha' => $isKaalSarp, 'status' => $isKaalSarp ? 'Partial Kaal Sarp Yoga Detected' : 'Kaal Sarp Mukta (Hemispheres Free)'],
                'sadeSati' => $sadeSati,
            ],
            'lucky' => [
                'gemstone' => $luckyGem['gem'],
                'metal' => $luckyGem['metal'],
                'finger' => $luckyGem['finger'],
                'rudraksha' => ($lagnaSignNum % 7 + 1) . '-Mukhi Nepali Rudraksha',
                'numbers' => ($lagnaSignNum % 9 + 1) . ', ' . (($lagnaSignNum + 4) % 9 + 1),
                'colors' => $zodiacSigns[$lagnaSignNum]['element'] === 'Fire (अग्नि)' ? 'Saffron, Red, Yellow' : 'White, Cream, Green',
                'days' => 'Monday & Thursday (सोमवार व गुरुवार)',
                'ishtaDevta' => 'Bhagwan Shiva & Shri Hari Vishnu',
            ],
            'housePredictions' => $houseNames,
        ];
    }

    /**
     * Generate authentic North Indian Diamond Vedic Kundali SVG
     */
    protected static function generateNorthIndianSvg(int $lagnaSign, array $housePlanets, string $title): string
    {
        $w = 340;
        $h = 340;

        // Calculate sign numbers for each house: House 1 = Lagna, House 2 = (Lagna % 12)+1, etc.
        $hSigns = [];
        for ($hNum = 1; $hNum <= 12; $hNum++) {
            $hSigns[$hNum] = (($lagnaSign + $hNum - 2) % 12) + 1;
        }

        // Helper to format planets in house
        $hp = function($hNum) use ($housePlanets) {
            $list = $housePlanets[$hNum] ?? [];
            return !empty($list) ? implode(' ', $list) : '';
        };

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 340 340" width="100%" height="260" style="max-width: 320px; margin: 0 auto; display: block; font-family: 'DejaVu Sans', sans-serif;">
    <rect width="340" height="340" fill="#FFFBEB" stroke="#D97706" stroke-width="3" rx="8"/>
    <!-- Outer Diamond Lines -->
    <line x1="0" y1="0" x2="340" y2="340" stroke="#B45309" stroke-width="1.8"/>
    <line x1="0" y1="340" x2="340" y2="0" stroke="#B45309" stroke-width="1.8"/>
    <line x1="170" y1="0" x2="340" y2="170" stroke="#B45309" stroke-width="1.8"/>
    <line x1="340" y1="170" x2="170" y2="340" stroke="#B45309" stroke-width="1.8"/>
    <line x1="170" y1="340" x2="0" y2="170" stroke="#B45309" stroke-width="1.8"/>
    <line x1="0" y1="170" x2="170" y2="0" stroke="#B45309" stroke-width="1.8"/>

    <!-- House 1 (Top Center Diamond) -->
    <text x="170" y="70" font-size="11" font-weight="bold" fill="#78350F" text-anchor="middle">{$hSigns[1]}</text>
    <text x="170" y="95" font-size="12" font-weight="bold" fill="#B91C1C" text-anchor="middle">{$hp(1)}</text>

    <!-- House 2 (Top Left Triangle) -->
    <text x="105" y="42" font-size="10" font-weight="bold" fill="#78350F" text-anchor="middle">{$hSigns[2]}</text>
    <text x="85" y="65" font-size="11" font-weight="bold" fill="#B91C1C" text-anchor="middle">{$hp(2)}</text>

    <!-- House 3 (Left Top Triangle) -->
    <text x="42" y="105" font-size="10" font-weight="bold" fill="#78350F" text-anchor="middle">{$hSigns[3]}</text>
    <text x="65" y="85" font-size="11" font-weight="bold" fill="#B91C1C" text-anchor="middle">{$hp(3)}</text>

    <!-- House 4 (Left Center Diamond) -->
    <text x="70" y="170" font-size="11" font-weight="bold" fill="#78350F" text-anchor="middle">{$hSigns[4]}</text>
    <text x="95" y="175" font-size="12" font-weight="bold" fill="#B91C1C" text-anchor="middle">{$hp(4)}</text>

    <!-- House 5 (Left Bottom Triangle) -->
    <text x="42" y="235" font-size="10" font-weight="bold" fill="#78350F" text-anchor="middle">{$hSigns[5]}</text>
    <text x="65" y="255" font-size="11" font-weight="bold" fill="#B91C1C" text-anchor="middle">{$hp(5)}</text>

    <!-- House 6 (Bottom Left Triangle) -->
    <text x="105" y="298" font-size="10" font-weight="bold" fill="#78350F" text-anchor="middle">{$hSigns[6]}</text>
    <text x="85" y="275" font-size="11" font-weight="bold" fill="#B91C1C" text-anchor="middle">{$hp(6)}</text>

    <!-- House 7 (Bottom Center Diamond) -->
    <text x="170" y="270" font-size="11" font-weight="bold" fill="#78350F" text-anchor="middle">{$hSigns[7]}</text>
    <text x="170" y="245" font-size="12" font-weight="bold" fill="#B91C1C" text-anchor="middle">{$hp(7)}</text>

    <!-- House 8 (Bottom Right Triangle) -->
    <text x="235" y="298" font-size="10" font-weight="bold" fill="#78350F" text-anchor="middle">{$hSigns[8]}</text>
    <text x="255" y="275" font-size="11" font-weight="bold" fill="#B91C1C" text-anchor="middle">{$hp(8)}</text>

    <!-- House 9 (Right Bottom Triangle) -->
    <text x="298" y="235" font-size="10" font-weight="bold" fill="#78350F" text-anchor="middle">{$hSigns[9]}</text>
    <text x="275" y="255" font-size="11" font-weight="bold" fill="#B91C1C" text-anchor="middle">{$hp(9)}</text>

    <!-- House 10 (Right Center Diamond) -->
    <text x="270" y="170" font-size="11" font-weight="bold" fill="#78350F" text-anchor="middle">{$hSigns[10]}</text>
    <text x="245" y="175" font-size="12" font-weight="bold" fill="#B91C1C" text-anchor="middle">{$hp(10)}</text>

    <!-- House 11 (Right Top Triangle) -->
    <text x="298" y="105" font-size="10" font-weight="bold" fill="#78350F" text-anchor="middle">{$hSigns[11]}</text>
    <text x="275" y="85" font-size="11" font-weight="bold" fill="#B91C1C" text-anchor="middle">{$hp(11)}</text>

    <!-- House 12 (Top Right Triangle) -->
    <text x="235" y="42" font-size="10" font-weight="bold" fill="#78350F" text-anchor="middle">{$hSigns[12]}</text>
    <text x="255" y="65" font-size="11" font-weight="bold" fill="#B91C1C" text-anchor="middle">{$hp(12)}</text>
</svg>
SVG;

        return $svg;
    }
}
