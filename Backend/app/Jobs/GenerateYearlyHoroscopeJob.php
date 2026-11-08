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

class GenerateYearlyHoroscopeJob implements ShouldQueue
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

        // ── Guard: skip if this year's data already exists (1 COUNT query) ──
        if (Horoscope::where('type', config('constants.YEARLY_HORSCOPE'))
                ->whereYear('date', date('Y'))
                ->exists()) {
            return;
        }

        $responses = $this->fetchParallel(
            endpoint: 'https://api.vedicastroapi.com/v3-json/prediction/yearly',
            apiKey: $apiKey,
            params: fn($zodiac, $lang) => [
                'zodiac' => $zodiac,
                'year' => date('Y'),
                'show_same' => true,
                'lang' => $lang,
            ]
        );

        $insertData = [];
        foreach ($responses as $item) {
            $zodiacIndex = $item['zodiac'];
            $langvalue = $item['lang'];
            $phases = $item['data']['response'] ?? null;

            if (!$phases)
                continue;

            foreach ($phases as $phaseData) {
                if ($langvalue === 'en' && isset($phaseData['period'])) {
                    [$startDate, $endDate] = explode(' to ', $phaseData['period']);
                    $startDate = date('Y-m-d', strtotime($startDate));
                    $endDate = date('Y-m-d', strtotime($endDate));
                } else {
                    $startDate = $endDate = null;
                }

                $insertData[] = [
                    'zodiac' => self::ZODIAC_MAP[$zodiacIndex] ?? '',
                    'total_score' => isset($phaseData['score']) ? substr($phaseData['score'], 0, -1) : 0,
                    'lucky_color' => $phaseData['lucky_color'] ?? '',
                    'lucky_color_code' => $phaseData['lucky_color_code'] ?? '',
                    'lucky_number' => $phaseData['lucky_number'] ?? 0,
                    'physique' => is_array($phaseData['physique']) ? json_encode($phaseData['physique']['score']) : ($phaseData['physique'] ?? ''),
                    'status' => isset($phaseData['status']['score']) ? substr($phaseData['status']['score'], 0, -1) : 0,
                    'finances' => isset($phaseData['finances']['score']) ? substr($phaseData['finances']['score'], 0, -1) : 0,
                    'relationship' => isset($phaseData['relationship']['score']) ? substr($phaseData['relationship']['score'], 0, -1) : 0,
                    'career' => isset($phaseData['career']['score']) ? substr($phaseData['career']['score'], 0, -1) : 0,
                    'travel' => isset($phaseData['travel']['score']) ? substr($phaseData['travel']['score'], 0, -1) : 0,
                    'family' => isset($phaseData['family']['score']) ? substr($phaseData['family']['score'], 0, -1) : 0,
                    'friends' => isset($phaseData['friends']['score']) ? substr($phaseData['friends']['score'], 0, -1) : 0,
                    'health' => isset($phaseData['health']['score']) ? substr($phaseData['health']['score'], 0, -1) : 0,
                    'bot_response' => is_array($phaseData['prediction']) ? json_encode($phaseData['prediction']) : str_replace("'", '', $phaseData['prediction'] ?? ''),
                    'date' => $currDate,
                    'type' => config('constants.YEARLY_HORSCOPE'),
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'month_range' => $phaseData['period'] ?? null,
                    'health_remark' => $phaseData['health']['prediction'] ?? '',
                    'career_remark' => $phaseData['carrer']['prediction'] ?? '',
                    'relationship_remark' => $phaseData['relationship']['prediction'] ?? '',
                    'travel_remark' => $phaseData['travel']['prediction'] ?? '',
                    'family_remark' => $phaseData['family']['prediction'] ?? '',
                    'friends_remark' => $phaseData['friends']['prediction'] ?? '',
                    'finances_remark' => $phaseData['finances']['prediction'] ?? '',
                    'status_remark' => $phaseData['status']['prediction'] ?? '',
                    'langcode' => $langvalue,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($insertData, 50) as $chunk) {
            Horoscope::insert($chunk);
        }
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
