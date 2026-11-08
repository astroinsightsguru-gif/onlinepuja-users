<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Models\UserModel\Kundali;
use App\Models\UserModel\KundaliMatching;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class KundaliMatchingController extends Controller
{
    public function addKundaliMatching(Request $req)
    {
        try {
            // Get a id of user
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            } else {
                $id = Auth::guard('api')->user()->id;
            }

            $data = $req->only(
                'boyName',
                'boyBirthDate',
                'boyBirthTime',
                'boyBirthPlace',
                'girlName',
                'girlBirthDate',
                'girlBirthTime',
                'girlBirthPlace',
            );

            // Validate the data
            $validator = Validator::make($data, [
                'boyName' => 'required',
                'boyBirthDate' => 'required',
                'boyBirthTime' => 'required',
                'boyBirthPlace' => 'required',
                'girlName' => 'required',
                'girlBirthDate' => 'required',
                'girlBirthTime' => 'required',
                'girlBirthPlace' => 'required',
            ]);

            // Send failed response if request is not valid
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }

            // Create kundali
            $kundaliMatching = KundaliMatching::create([
                'boyName' => $req->boyName,
                'boyBirthDate' => $req->boyBirthDate,
                'boyBirthTime' => $req->boyBirthTime ?? '12:00',
                'boyBirthPlace' => $req->boyBirthPlace,
                'girlName' => $req->girlName,
                'girlBirthDate' => $req->girlBirthDate,
                'girlBirthTime' => $req->girlBirthTime ?? '12:00',
                'girlBirthPlace' => $req->girlBirthPlace,
                'createdBy' => $id,
                'modifiedBy' => $id,
            ]);

            return response()->json([
                'message' => 'Boys and girls details add sucessfully',
                'recordList' => $kundaliMatching,
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

    public function getMatchReport(Request $req)
    {
        try {
            $data = $req->only(
                'male_kundli_id',
                'female_kundli_id'
            );

            $maleKundliId = $req->male_kundli_id;
            $femaleKundliId = $req->female_kundli_id;
            $maleRcd = Kundali::where('id', $maleKundliId)->first();
            $femaleRcd = Kundali::where('id', $femaleKundliId)->first();

            if (!$maleRcd || !$femaleRcd) {
                return response()->json([
                    'error' => true,
                    'message' => 'Kundali records not found for matching',
                    'status' => 404,
                ], 404);
            }

            $api_key = DB::table('systemflag')->where('name', 'vedicAstroAPI')->first();
            $apiKeyValue = ($api_key && !empty($api_key->value)) ? $api_key->value : '';

            $responses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($maleRcd, $femaleRcd, $apiKeyValue) {
                $poolArray = [
                    $pool->as('boyManaglikRpt')->get('https://api.vedicastroapi.com/v3-json/dosha/manglik-dosh', [
                        'dob' => date('d/m/Y', strtotime($maleRcd->birthDate)),
                        'tob' => date('H:i', strtotime($maleRcd->birthTime)),
                        'tz' => $maleRcd->timezone,
                        'lat' => $maleRcd->latitude,
                        'lon' => $maleRcd->longitude,
                        'api_key' => $apiKeyValue,
                        'lang' => 'en'
                    ]),
                    $pool->as('girlMangalikRpt')->get('https://api.vedicastroapi.com/v3-json/dosha/manglik-dosh', [
                        'dob' => date('d/m/Y', strtotime($femaleRcd->birthDate)),
                        'tob' => date('H:i', strtotime($femaleRcd->birthTime)),
                        'tz' => $femaleRcd->timezone,
                        'lat' => $femaleRcd->latitude,
                        'lon' => $femaleRcd->longitude,
                        'api_key' => $apiKeyValue,
                        'lang' => 'en'
                    ])
                ];

                if (strtolower($femaleRcd->match_type) == strtolower('North')) {
                    $poolArray[] = $pool->as('dailyHorscope')->get('https://api.vedicastroapi.com/v3-json/matching/ashtakoot', [
                        'boy_dob' => date('d/m/Y', strtotime($maleRcd->birthDate)),
                        'boy_tob' => date('H:i', strtotime($maleRcd->birthTime)),
                        'boy_tz' => $maleRcd->timezone,
                        'boy_lat' => $maleRcd->latitude,
                        'boy_lon' => $maleRcd->longitude,
                        'girl_dob' => date('d/m/Y', strtotime($femaleRcd->birthDate)),
                        'girl_tob' => date('H:i', strtotime($femaleRcd->birthTime)),
                        'girl_tz' => $femaleRcd->timezone,
                        'girl_lat' => $femaleRcd->latitude,
                        'girl_lon' => $femaleRcd->longitude,
                        'api_key' => $apiKeyValue,
                        'lang' => 'en'
                    ]);
                } else {
                    $poolArray[] = $pool->as('dailyHorscope')->get('https://api.vedicastroapi.com/v3-json/matching/dashakoot', [
                        'boy_dob' => date('d/m/Y', strtotime($maleRcd->birthDate)),
                        'boy_tob' => date('H:i', strtotime($maleRcd->birthTime)),
                        'boy_tz' => $maleRcd->timezone,
                        'boy_lat' => $maleRcd->latitude,
                        'boy_lon' => $maleRcd->longitude,
                        'girl_dob' => date('d/m/Y', strtotime($femaleRcd->birthDate)),
                        'girl_tob' => date('H:i', strtotime($femaleRcd->birthTime)),
                        'girl_tz' => $femaleRcd->timezone,
                        'girl_lat' => $femaleRcd->latitude,
                        'girl_lon' => $femaleRcd->longitude,
                        'api_key' => $apiKeyValue,
                        'lang' => 'en'
                    ]);
                }

                return $poolArray;
            });

            $girlMangalikRpt = $responses['girlMangalikRpt'];
            $boyManaglikRpt = $responses['boyManaglikRpt'];
            $dailyHorscope = $responses['dailyHorscope'];
            $data = $dailyHorscope->json();

            return response()->json([
                'message' => 'Boys and girls matching details fetched sucessfully',
                'recordList' => $data,
                'girlMangalikRpt' => $girlMangalikRpt->json(),
                'boyManaglikRpt' => $boyManaglikRpt->json(),
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

    // Get Kundali Report

    public function getKundaliReport(Request $req)
    {
        try {
            $cacheKey = 'api_kundali_report_' . ($req->kundali_id ?? '0') . '_' . ($req->userId ?? '0') . '_' . ($req->lang ?? 'en');
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                return response()->json(\Illuminate\Support\Facades\Cache::get($cacheKey), 200);
            }
            // Fetching input data
            $data = $req->only(['kundali_id', 'lang', 'userId']);

            // Fetching API key from the database
            $api_key = DB::table('systemflag')->where('name', 'vedicAstroAPI')->first();
            $apiKeyValue = ($api_key && !empty($api_key->value)) ? $api_key->value : '';

            // Fetching the user's kundali details
            $maleRcd = Kundali::where('id', $req->kundali_id)->first();

            $intakeForm = DB::table('intakeform')->where('userId', $req->userId)->latest()->first();

            if ($intakeForm && $intakeForm->latitude && $intakeForm->longitude && $intakeForm->timezone) {
                $maleRcd = (object) [
                    'name' => $intakeForm->name,
                    'gender' => $intakeForm->gender,
                    'birthDate' => $intakeForm->birthDate,
                    'birthTime' => date('H:i', strtotime($intakeForm->birthTime)),
                    'latitude' => $intakeForm->latitude,
                    'longitude' => $intakeForm->longitude,
                    'birthPlace' => $intakeForm->birthPlace,
                    'timezone' => $intakeForm->timezone,
                ];
            }

            if (!$maleRcd || empty($maleRcd->birthDate)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Kundali details not found',
                    'status' => 404,
                ], 404);
            }

            // List of divisional charts to retrieve
            $divs = [
                'D1', 'D2', 'D3', 'D4', 'D5', 'D7', 'D8', 'D9', 'D10',
                'D12', 'D16', 'D20', 'D24', 'D27', 'D40', 'D45', 'D60', 'D30',
                'chalit', 'sun', 'moon', 'kp_chalit'
            ];
            $chartsPool = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($divs, $maleRcd, $apiKeyValue, $req) {
                $requests = [];
                foreach ($divs as $div) {
                    $requests[] = $pool->as($div)->get('https://api.vedicastroapi.com/v3-json/horoscope/chart-image', [
                        'name' => $maleRcd->name,
                        'dob' => date('d/m/Y', strtotime($maleRcd->birthDate)),
                        'tob' => date('H:i', strtotime($maleRcd->birthTime)),
                        'lat' => $maleRcd->latitude,
                        'lon' => $maleRcd->longitude,
                        'tz' => $maleRcd->timezone,
                        'div' => $div,
                        'api_key' => $apiKeyValue,
                        'lang' => $req->lang ?? 'en',
                        'font_size' => 14,
                        'font_style' => 'roboto',
                        'style' => 'north',
                        'color' => '#F0D08D',
                        'size' => '300',
                        'stroke' => 2,
                        'colorful_planets' => 1
                    ]);
                }
                return $requests;
            });

            $results = [];
            foreach ($divs as $div) {
                $response = $chartsPool[$div];
                if ($response->successful()) {
                    if (preg_match('/<svg.*?<\/svg>/s', $response->body(), $matches)) {
                        $results[$div] = $matches[0];
                    } else {
                        $results[$div] = "No SVG found for div $div";
                    }
                } else {
                    $results[$div] = "Error fetching chart for div $div";
                }
            }

            // Fetch other horoscope details
            $reportPool = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($maleRcd, $apiKeyValue, $req) {
                $dob = date('d/m/Y', strtotime($maleRcd->birthDate));
                $tob = date('H:i', strtotime($maleRcd->birthTime));
                $tobCarbon = \Carbon\Carbon::parse($maleRcd->birthTime)->format('H:i');

                $baseParams = [
                    'dob' => $dob, 'tob' => $tob, 'tz' => $maleRcd->timezone,
                    'lat' => $maleRcd->latitude, 'lon' => $maleRcd->longitude,
                    'api_key' => $apiKeyValue, 'lang' => $req->lang ?? 'en'
                ];
                $baseParamsCarbon = array_merge($baseParams, ['tob' => $tobCarbon]);

                return [
                    $pool->as('personal')->get('https://api.vedicastroapi.com/v3-json/horoscope/personal-characteristics', $baseParams),
                    $pool->as('ascendant')->get('https://api.vedicastroapi.com/v3-json/horoscope/ascendant-report', $baseParams),
                    $pool->as('ashtakvarga')->get('https://api.vedicastroapi.com/v3-json/horoscope/ashtakvarga', $baseParams),
                    $pool->as('binnashtakvarga')->get('https://api.vedicastroapi.com/v3-json/horoscope/binnashtakvarga', array_merge($baseParamsCarbon, ['planet' => 'Sun'])),
                    $pool->as('planet')->get('https://api.vedicastroapi.com/v3-json/horoscope/planet-details', $baseParamsCarbon),
                    $pool->as('maha_dasha')->get('https://api.vedicastroapi.com/v3-json/dashas/maha-dasha', $baseParamsCarbon),
                    $pool->as('maha_dasha_predictions')->get('https://api.vedicastroapi.com/v3-json/dashas/maha-dasha-predictions', $baseParamsCarbon),
                    $pool->as('antar_dasha')->get('https://api.vedicastroapi.com/v3-json/dashas/antar-dasha', $baseParamsCarbon),
                    $pool->as('char_dasha')->get('https://api.vedicastroapi.com/v3-json/dashas/char-dasha-current', $baseParamsCarbon),
                    $pool->as('char_dasha_main')->get('https://api.vedicastroapi.com/v3-json/dashas/char-dasha-main', $baseParamsCarbon),
                    $pool->as('yogini_dasha_main')->get('https://api.vedicastroapi.com/v3-json/dashas/yogini-dasha-main', $baseParamsCarbon),
                    $pool->as('yogini_dasha_sub')->get('https://api.vedicastroapi.com/v3-json/dashas/yogini-dasha-sub', $baseParamsCarbon),
                    $pool->as('paryantar_dasha')->get('https://api.vedicastroapi.com/v3-json/dashas/paryantar-dasha', $baseParamsCarbon),
                    $pool->as('mangal_dosh')->get('https://api.vedicastroapi.com/v3-json/dosha/mangal-dosh', $baseParamsCarbon),
                    $pool->as('kaalsarp_dosh')->get('https://api.vedicastroapi.com/v3-json/dosha/kaalsarp-dosh', $baseParamsCarbon),
                    $pool->as('manglik_dosh')->get('https://api.vedicastroapi.com/v3-json/dosha/manglik-dosh', $baseParamsCarbon),
                    $pool->as('pitra_dosh')->get('https://api.vedicastroapi.com/v3-json/dosha/pitra-dosh', $baseParamsCarbon),
                    $pool->as('papasamaya')->get('https://api.vedicastroapi.com/v3-json/dosha/papasamaya', $baseParamsCarbon),
                ];
            });

            $planetss = ['Sun', 'Moon', 'Mercury', 'Venus', 'Mars', 'Saturn', 'Jupiter', 'Rahu', 'Ketu'];
            $planetPool = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($planetss, $maleRcd, $apiKeyValue, $req) {
                $requests = [];
                $baseParamsCarbon = [
                    'dob' => date('d/m/Y', strtotime($maleRcd->birthDate)),
                    'tob' => \Carbon\Carbon::parse($maleRcd->birthTime)->format('H:i'),
                    'tz' => $maleRcd->timezone,
                    'lat' => $maleRcd->latitude,
                    'lon' => $maleRcd->longitude,
                    'api_key' => $apiKeyValue,
                    'lang' => $req->language ?? 'en',
                ];
                foreach ($planetss as $planet) {
                    $requests[] = $pool->as($planet)->get('https://api.vedicastroapi.com/v3-json/horoscope/planet-report', array_merge($baseParamsCarbon, ['planet' => $planet]));
                }
                return $requests;
            });

            $planet_reports = [];
            foreach ($planetss as $planet) {
                $planet_reports[$planet] = $planetPool[$planet]->json();
            }

            $responseData = [
                'message' => 'Kundali Report Fetched Successfully',
                'recordList' => $maleRcd,
                'personal' => $reportPool['personal']->json(),
                'ashtakvarga' => $reportPool['ashtakvarga']->json(),
                'binnashtakvarga' => $reportPool['binnashtakvarga']->json(),
                'planet' => $reportPool['planet']->json(),
                'charts' => $results,
                'mahaDasha' => $reportPool['maha_dasha']->json(),
                'mahaDashaPrediction' => $reportPool['maha_dasha_predictions']->json(),
                'antarDasha' => $reportPool['antar_dasha']->json(),
                'charDashaCurrent' => $reportPool['char_dasha']->json(),
                'charDashaMain' => $reportPool['char_dasha_main']->json(),
                'yoginiDashaMain' => $reportPool['yogini_dasha_main']->json(),
                'yoginiDashaSub' => $reportPool['yogini_dasha_sub']->json(),
                'paryantarDasha' => $reportPool['paryantar_dasha']->json(),
                'mangalDosh' => $reportPool['mangal_dosh']->json(),
                'kaalsarpDosh' => $reportPool['kaalsarp_dosh']->json(),
                'manglikDosh' => $reportPool['manglik_dosh']->json(),
                'pitraDosh' => $reportPool['pitra_dosh']->json(),
                'papasamayaDosh' => $reportPool['papasamaya']->json(),
                'ascendantReport' => $reportPool['ascendant']->json(),
                'planetReport' => $planet_reports,
                'status' => 200,
            ];


             \Illuminate\Support\Facades\Cache::put($cacheKey, $responseData, now()->addHours(24));
             return response()->json($responseData, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }
}
