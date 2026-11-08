<?php

namespace App\Jobs;

use App\Models\Horoscope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use DateTime;

class GenerateWeeklyHoroscopeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const ALL_LANGS = ['en', 'ta', 'ka', 'te', 'hi', 'ml', 'sp', 'fr', 'be'];

    const ZODIAC_MAP = [
        1 => 'Aries',
        2 => 'Taurus',
        3 => 'Gemini',
        4 => 'Cancer',
        5 => 'Leo',
        6 => 'Virgo',
        7 => 'Libra',
        8 => 'Scorpio',
        9 => 'Sagittarius',
        10 => 'Capricorn',
        11 => 'Aquarius',
        12 => 'Pisces',
    ];

    public function handle(): void
    {
        $apiKey = $this->getApiKey();
        if (empty($apiKey) || $apiKey === 'vedicastro') {
            \Illuminate\Support\Facades\Log::info('Horoscope Job skipped: Vedic Astro API key not configured or placeholder.');
            return;
        }
        $currDate = date('Y-m-d');
        $aDate = $this->getThisWeekDate();

        // ── Guard: skip if this week's data already exists (1 COUNT query) ──
        if (Horoscope::where('type', config('constants.WEEKLY_HORSCOPE'))
                ->whereBetween('date', [$aDate['startdate'], $aDate['enddate']])
                ->exists()) {
            return;
        }

        $responses = $this->fetchParallel(
            endpoint: 'https://api.vedicastroapi.com/v3-json/prediction/weekly-moon',
            apiKey: $apiKey,
            params: fn($zodiac, $lang) => [
                'zodiac' => $zodiac,
                'week' => 'thisweek',
                'show_same' => true,
                'lang' => $lang,
            ]
        );

        $insertData = [];
        foreach ($responses as $item) {
            $r = $item['data']['response'] ?? null;
            if (!$r)
                continue;

            $insertData[] = [
                'zodiac' => $r['zodiac'],
                'total_score' => $r['total_score'],
                'lucky_color' => $r['lucky_color'],
                'lucky_color_code' => $r['lucky_color_code'],
                'lucky_number' => json_encode($r['lucky_number']),
                'physique' => $r['physique'] ?? 0,
                'status' => $r['status'],
                'finances' => $r['finances'],
                'relationship' => $r['relationship'],
                'career' => $r['career'],
                'travel' => $r['travel'],
                'family' => $r['family'],
                'friends' => $r['friends'],
                'health' => $r['health'],
                'bot_response' => $r['bot_response'],
                'date' => $currDate,
                'start_date' => $aDate['startdate'],
                'end_date' => $aDate['enddate'],
                'type' => config('constants.WEEKLY_HORSCOPE'),
                'langcode' => $item['lang'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($insertData, 50) as $chunk) {
            Horoscope::insert($chunk);
        }
    }

    public function getThisWeekDate(): array
    {
        date_default_timezone_set('Asia/Kolkata');
        $currentDate = new DateTime();
        $startDate = clone $currentDate;
        $startDate->modify('this week');
        $endDate = clone $startDate;
        $endDate->modify('this week +6 days');

        return [
            'startdate' => $startDate->format('Y-m-d'),
            'enddate' => $endDate->format('Y-m-d'),
        ];
    }

    private function fetchParallel(string $endpoint, string $apiKey, callable $params): array
    {
        $multiHandle = curl_multi_init();
        $handles = [];
        $results = [];

        foreach (range(1, 12) as $zodiac) {
            foreach (self::ALL_LANGS as $lang) {
                $query = http_build_query(array_merge(
                    $params($zodiac, $lang),
                    ['api_key' => $apiKey]
                ));

                $ch = curl_init("{$endpoint}?{$query}");
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_SSL_VERIFYPEER => false,
                ]);

                $key = "{$zodiac}_{$lang}";
                $handles[$key] = ['handle' => $ch, 'zodiac' => $zodiac, 'lang' => $lang];
                curl_multi_add_handle($multiHandle, $ch);
            }
        }

        $running = null;
        do {
            curl_multi_exec($multiHandle, $running);
            curl_multi_select($multiHandle);
        } while ($running > 0);

        foreach ($handles as $key => $meta) {
            $body = curl_multi_getcontent($meta['handle']);
            $data = $body ? json_decode($body, true) : null;

            if ($data) {
                $results[] = [
                    'zodiac' => $meta['zodiac'],
                    'lang' => $meta['lang'],
                    'data' => $data,
                ];
            } else {
                Log::warning('Horoscope API failed', ['key' => $key]);
            }

            curl_multi_remove_handle($multiHandle, $meta['handle']);
            curl_close($meta['handle']);
        }

        curl_multi_close($multiHandle);

        return $results;
    }

    private function getApiKey(): string
    {
        static $apiKey = null;
        if (!$apiKey) {
            $apiKey = DB::table('systemflag')->where('name', 'vedicAstroAPI')->value('value');
        }
        return $apiKey;
    }
}
