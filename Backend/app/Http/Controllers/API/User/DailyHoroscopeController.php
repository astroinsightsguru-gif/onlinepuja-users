<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\MstControl;
use App\Models\Horoscope;
use DateTime;
if (!defined('DATEFORMAT')) define('DATEFORMAT', "(DATE_FORMAT(horoscopeDate,'%Y-%m-%d'))");

class DailyHoroscopeController extends Controller
{
     public function getDailyHoroscope(Request $req)
    {
        try {
            $dt = Carbon::now()->format('Y-m-d');
            $yesterday = Carbon::yesterday()->format('Y-m-d');
            $tomorrow = Carbon::tomorrow()->format('Y-m-d');
            $mstData = MstControl::query()->get();
            $astroApiCallType = $mstData[0]->astro_api_call_type;

                $currentDate = new DateTime();
                $currentYear = $currentDate->format('Y');
                $currentDate->setISODate((int)$currentDate->format('o'), (int)$currentDate->format('W'), 1); // Set to the first day of the current week
                $startOfWeekFormatted = $currentDate->format('Y-m-d');

                $signRcd = DB::table('hororscope_signs')->where('id', $req->horoscopeSignId)->first();
                if (!$signRcd) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Horoscope sign not found',
                        'status' => 404,
                    ], 404);
                }
                $signName = $signRcd->name;
                $currentDate->modify('+6 days'); // Move to the last day of the current week
                $endOfWeekFormatted = $currentDate->format('Y-m-d');

                $startOfYear = new DateTime("$currentYear-01-01");
                $startOfYearFormatted = $startOfYear->format('Y-m-d');
                $endOfYear = new DateTime("$currentYear-12-31");
                $endOfYearFormatted = $endOfYear->format('Y-m-d');

                $langcode=$req->langcode?$req->langcode:'en';

                $todayHoroscope = Horoscope::selectRaw('horoscopes.*, REPLACE(lucky_color_code, "#", "0xff") AS color_code')
                ->where('zodiac', $signName)
                ->where('date', $dt)
                ->where('type', config('constants.DAILY_HORSCOPE'))
                ->where('langcode', $langcode)
                ->get();

                if ($todayHoroscope->isEmpty() && $langcode !== 'en') {
                    $todayHoroscope = Horoscope::selectRaw('horoscopes.*, REPLACE(lucky_color_code, "#", "0xff") AS color_code')
                        ->where('zodiac', $signName)
                        ->where('date', $dt)
                        ->where('type', config('constants.DAILY_HORSCOPE'))
                        ->where('langcode', 'en')
                        ->get();
                }

              // Fetching weekly horoscope
                $weeklyHoroScope = Horoscope::selectRaw('horoscopes.*, REPLACE(lucky_color_code, "#", "0xff") AS color_code')
                ->where('zodiac', $signName)
                ->where('start_date', '>=', $startOfWeekFormatted)
                ->where('end_date', '<=', $endOfWeekFormatted)
                ->where('type', config('constants.WEEKLY_HORSCOPE'))
                ->where('langcode', $langcode)
                ->get();


                if ($weeklyHoroScope->isEmpty() && $langcode !== 'en') {
                $weeklyHoroScope = Horoscope::selectRaw('horoscopes.*, REPLACE(lucky_color_code, "#", "0xff") AS color_code')
                    ->where('zodiac', $signName)
                    ->where('start_date', '>=', $startOfWeekFormatted)
                    ->where('end_date', '<=', $endOfWeekFormatted)
                    ->where('type', config('constants.WEEKLY_HORSCOPE'))
                    ->where('langcode', 'en')
                    ->get();
                }

                // Fetching yearly horoscope
                $yearlyHoroScope = Horoscope::selectRaw('horoscopes.*, REPLACE(lucky_color_code, "#", "0xff") AS color_code')
                ->where('zodiac', $signName)
                ->whereYear('date', date('Y'))
                ->whereNotNull('month_range')
                ->where('type', config('constants.YEARLY_HORSCOPE'))
                ->where('langcode', $langcode)
                ->get();


                if ($yearlyHoroScope->isEmpty() && $langcode !== 'en') {
                $yearlyHoroScope = Horoscope::selectRaw('horoscopes.*, REPLACE(lucky_color_code, "#", "0xff") AS color_code')
                    ->where('zodiac', $signName)
                    ->where('start_date', '>=', $startOfYearFormatted)
                    ->where('end_date', '<=', $endOfYearFormatted)
                    ->where('type', config('constants.YEARLY_HORSCOPE'))
                    ->where('langcode', 'en')
                    ->get();
                }

                $horo2 = array(
                    'todayHoroscope' => $todayHoroscope,
                    'weeklyHoroScope' => $weeklyHoroScope,
                    'yearlyHoroScope' => $yearlyHoroScope
                );
                return response()->json([
                    "message" => "get daily Horoscope",
                    'astroApiCallType' => $astroApiCallType,
                  // "recordList" => $horo,
                    'vedicList' => $horo2,
                    'status' => 200,
                ], 200);
            // }
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
                'error' => false,
            ], 500);
        }
    }

    public function getHoroscope(Request $req)
    {
        try {

            $horoscope = DB::Table('horoscope')
                ->join('hororscope_signs', 'hororscope_signs.id', '=', 'horoscope.horoscopeSignId');
            if ($req->filterSign) {
                $horoscope = $horoscope->where('horoscope.horoscopeSignId', '=', $req->filterSign);
            } else {
                $horoscope = $horoscope->where("horoscopeSignId", '=', 1);
            }
            if ($req->horoscopeType) {
                error_log($req->horoscopeType);
                $horoscope = $horoscope->where('horoscope.horoscopeType', '=', $req->horoscopeType);
            } else {
                $horoscope = $horoscope->where('horoscope.horoscopeType', '=', 'Weekly');
            }
            error_log($req->filterSign);

            return response()->json([
                "message" => "Get Daily Horoscope Insight Successfully",
                'status' => 200,
                "recordList" => $horoscope->select('horoscope.*')->get(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
                'error' => false,
            ], 500);
        }
    }

    public function addHoroscopeFeedback(Request $req)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            } else {
                $id = Auth::guard('api')->user()->id;
            }
            $data = array(
                'userId' => $id,
                'feedback' => $req->feedback,
                'feedbacktype' => $req->feedbacktype,
            );
            DB::table('horoscopefeedback')->insert($data);
            return response()->json([
                "message" => "Add Feedback Successfully",
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
                'error' => false,
            ], 500);
        }
    }
}
