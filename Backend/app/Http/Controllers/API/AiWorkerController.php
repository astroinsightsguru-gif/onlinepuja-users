<?php

namespace App\Http\Controllers\API;

use App\Brain\Vault;
use App\Helpers\StorageHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiWorkerController extends Controller
{
    protected function verifyWorkerToken(Request $request): bool
    {
        $expectedToken = Vault::get('ai_worker_token', 'onlinepuja_kgl_worker_sec_2026');
        $givenToken = $request->input('token') ?? $request->header('X-AI-Worker-Token') ?? $request->bearerToken();

        return !empty($givenToken) && hash_equals($expectedToken, (string) $givenToken);
    }

    /**
     * Ping endpoint to check worker API health and count of pending tasks.
     * GET /api/v1/ai-worker/ping
     */
    public function ping(Request $request): JsonResponse
    {
        if (!$this->verifyWorkerToken($request)) {
            return response()->json(['ok' => false, 'error' => 'Unauthorized worker token'], 401);
        }

        $pendingVideos = DB::table('media_assets')
            ->where('kind', 'video')
            ->where('provider', 'kaggle')
            ->where('path', 'like', 'pending_kaggle_%')
            ->count();

        $pendingPlans = DB::table('content_plans')
            ->whereNotIn('id', function ($query) {
                $query->select('content_plan_id')->from('media_assets')->whereNotNull('content_plan_id');
            })
            ->whereIn('status', ['approved', 'draft', 'idea'])
            ->count();

        return response()->json([
            'ok'               => true,
            'message'          => 'OnlinePuja AI Worker Gateway Active',
            'server_time'      => now()->toIso8601String(),
            'pending_videos'   => $pendingVideos,
            'pending_images'   => $pendingPlans,
        ]);
    }

    /**
     * Retrieve pending jobs for Kaggle or external GPU workers.
     * GET /api/v1/ai-worker/jobs
     */
    public function jobs(Request $request): JsonResponse
    {
        if (!$this->verifyWorkerToken($request)) {
            return response()->json(['ok' => false, 'error' => 'Unauthorized worker token'], 401);
        }

        $type = $request->input('type', 'all');
        $limit = min(50, max(1, (int) $request->input('limit', 10)));
        $jobs = [];

        // 1. Pending videos queued in media_assets
        if ($type === 'all' || $type === 'video') {
            $pendingAssets = DB::table('media_assets')
                ->where('kind', 'video')
                ->where('provider', 'kaggle')
                ->where('path', 'like', 'pending_kaggle_%')
                ->orderBy('id', 'asc')
                ->limit($limit)
                ->get();

            foreach ($pendingAssets as $asset) {
                $meta = json_decode((string) $asset->meta, true) ?: [];
                $jobs[] = [
                    'job_id'          => $asset->id,
                    'type'            => 'video',
                    'prompt'          => $asset->prompt,
                    'width'           => $asset->width ?: 1080,
                    'height'          => $asset->height ?: 1920,
                    'seconds'         => $asset->duration_ms ? (int) ($asset->duration_ms / 1000) : 8,
                    'image_url'       => $meta['image_url'] ?? null,
                    'content_plan_id' => $asset->content_plan_id,
                ];
            }
        }

        // 2. Pending content plans needing high-res hero images
        if (($type === 'all' || $type === 'image') && count($jobs) < $limit) {
            $needed = $limit - count($jobs);
            $plans = DB::table('content_plans')
                ->whereNotIn('id', function ($query) {
                    $query->select('content_plan_id')->from('media_assets')->whereNotNull('content_plan_id');
                })
                ->whereIn('status', ['approved', 'draft', 'idea'])
                ->orderBy('id', 'asc')
                ->limit($needed)
                ->get();

            foreach ($plans as $plan) {
                $jobs[] = [
                    'job_id'          => 'plan_' . $plan->id,
                    'type'            => 'image',
                    'prompt'          => $plan->title . ($plan->pillar ? ' — ' . $plan->pillar : ''),
                    'width'           => 1200,
                    'height'          => 630,
                    'content_plan_id' => $plan->id,
                ];
            }
        }

        return response()->json([
            'ok'    => true,
            'count' => count($jobs),
            'jobs'  => $jobs,
        ]);
    }

    /**
     * Upload rendered asset from Kaggle GPU back to the platform.
     * POST /api/v1/ai-worker/upload
     */
    public function upload(Request $request): JsonResponse
    {
        if (!$this->verifyWorkerToken($request)) {
            return response()->json(['ok' => false, 'error' => 'Unauthorized worker token'], 401);
        }

        $jobId = $request->input('job_id');
        $type = $request->input('type', 'video');
        $modelName = $request->input('model', 'kaggle-dual-t4');

        $binary = null;
        $mime = 'video/mp4';

        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $binary = file_get_contents($request->file('file')->getRealPath());
            $mime = $request->file('file')->getMimeType() ?: ($type === 'image' ? 'image/webp' : 'video/mp4');
        } elseif ($request->filled('base64')) {
            $raw = $request->input('base64');
            $data = str_contains($raw, ',') ? substr($raw, strpos($raw, ',') + 1) : $raw;
            $binary = base64_decode($data);
            $mime = $type === 'image' ? 'image/jpeg' : 'video/mp4';
        }

        if (empty($binary) || strlen($binary) < 500) {
            return response()->json(['ok' => false, 'error' => 'Empty or invalid file payload received.'], 400);
        }

        $ext = ($type === 'image') ? 'webp' : 'mp4';
        $filename = 'brain_kgl_' . Str::random(16) . '.' . $ext;

        try {
            $folder = ($type === 'image') ? 'brain_media' : 'reels';
            $path = StorageHelper::uploadToActiveStorage($binary, $filename, $folder);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => 'Failed to store media: ' . $e->getMessage()], 500);
        }

        $publicUrl = Str::startsWith($path, ['http://', 'https://'])
            ? $path
            : rtrim((string) config('app.url'), '/') . '/' . ltrim(Str::after($path, 'public/'), '/');

        // Update corresponding DB record
        if (is_numeric($jobId)) {
            DB::table('media_assets')->where('id', $jobId)->update([
                'path'       => $path,
                'mime'       => $mime,
                'provider'   => 'kaggle',
                'model'      => $modelName,
                'bytes'      => strlen($binary),
                'meta'       => json_encode([
                    'status'        => 'completed',
                    'rendered_by'   => 'Kaggle Dual-T4 GPU',
                    'completed_at'  => now()->toIso8601String(),
                ]),
                'updated_at' => now(),
            ]);
        } elseif (str_starts_with((string) $jobId, 'plan_')) {
            $planId = (int) str_replace('plan_', '', $jobId);
            DB::table('media_assets')->insert([
                'kind'            => 'image',
                'provider'        => 'kaggle',
                'model'           => $modelName,
                'prompt'          => 'Rendered for content_plan #' . $planId,
                'path'            => $path,
                'mime'            => $mime,
                'bytes'           => strlen($binary),
                'content_plan_id' => $planId,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        return response()->json([
            'ok'        => true,
            'job_id'    => $jobId,
            'path'      => $path,
            'url'       => $publicUrl,
            'bytes'     => strlen($binary),
            'model'     => $modelName,
        ]);
    }
}
