<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\FreeKundaliEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KundliApiController extends Controller
{
    /**
     * POST /api/kundli/generate-free-pdf
     * Generate 10-Page Comprehensive Free Vedic Kundali PDF for App & Web
     */
    public function generateFreePdf(Request $request)
    {
        try {
            $data = $request->json()->all() ?: $request->all();
            if (empty($data)) {
                $raw = json_decode($request->getContent(), true);
                if (is_array($raw)) $data = $raw;
            }

            $validator = Validator::make($data, [
                'name' => 'required|string|max:100',
                'dob' => 'required|string',
                'tob' => 'required|string',
                'pob' => 'required|string',
                'gender' => 'nullable|string',
                'phone' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $res = FreeKundaliEngine::generate([
                'name' => trim((string)$data['name']),
                'dob' => trim((string)$data['dob']),
                'tob' => trim((string)$data['tob']),
                'pob' => trim((string)$data['pob']),
                'gender' => trim((string)($data['gender'] ?? 'Male')),
            ]);

            if (!$res['ok']) {
                return response()->json([
                    'status' => 500,
                    'success' => false,
                    'error' => $res['error'] ?? 'Failed to generate Kundali PDF',
                ], 500);
            }

            $astro = $res['data'] ?? [];

            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => '10-Page Free Vedic Kundli generated successfully.',
                'pdf_url' => $res['url'],
                'file_name' => $res['fileName'],
                'page_count' => 10,
                'devotee' => [
                    'name' => $astro['name'] ?? $data['name'],
                    'dob' => $astro['dob'] ?? $data['dob'],
                    'tob' => $astro['tob'] ?? $data['tob'],
                    'pob' => $astro['pob'] ?? $data['pob'],
                    'gender' => $astro['gender'] ?? 'Male',
                ],
                'summary' => [
                    'lagna' => $astro['lagna']['sign'] ?? 'Mesha',
                    'moon_sign' => $astro['panchang']['moonSign'] ?? 'Simha',
                    'nakshatra' => $astro['panchang']['nakshatra'] ?? 'Magha',
                    'tithi' => $astro['panchang']['tithi'] ?? 'Shukla Paksha',
                    'manglik_status' => $astro['doshas']['manglik']['status'] ?? 'No Kuja Dosha',
                    'kaalsarp_status' => $astro['doshas']['kaalsarp']['status'] ?? 'Kaal Sarp Mukta',
                    'active_dasha' => $astro['activeDasha'] ?? 'Jupiter',
                    'lucky_gemstone' => $astro['lucky']['gemstone'] ?? 'Yellow Sapphire',
                    'rudraksha' => $astro['lucky']['rudraksha'] ?? '5-Mukhi Rudraksha',
                ],
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
