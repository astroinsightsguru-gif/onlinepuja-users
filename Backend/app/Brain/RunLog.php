<?php

namespace App\Brain;

use Illuminate\Support\Facades\DB;

/**
 * Thin recorder for the brain_run_log timeline. Every autonomous command
 * wraps its work in RunLog so the Command Center has one honest record of what
 * ran, when, and how it went — including partial and failed runs, which are
 * exactly the ones an operator needs to see and today cannot.
 *
 * Usage:
 *   $id = RunLog::start('content:publish', 'blog', 'manual');
 *   ... do work ...
 *   RunLog::finish($id, 'ok', "Published 3", ['ids'=>[...]], okCount, failCount);
 *
 * Or, for a one-shot record:
 *   RunLog::record('social:publish', 'social', 'ok', 'Published 2', [...], 2, 0);
 *
 * Logging must never break the job it observes — every call swallows its own
 * errors (the table may not exist yet on a box that hasn't migrated).
 */
class RunLog
{
    /** @return int|null run id, or null if logging is unavailable */
    public static function start(string $task, string $category, string $trigger = 'schedule'): ?int
    {
        try {
            return (int) DB::table('brain_run_log')->insertGetId([
                'task' => $task,
                'category' => $category,
                'status' => 'running',
                'trigger' => $trigger,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function finish(
        ?int $id,
        string $status,
        string $summary,
        array $detail = [],
        int $itemsOk = 0,
        int $itemsFailed = 0
    ): void {
        if ($id === null) {
            return;
        }

        try {
            $started = DB::table('brain_run_log')->where('id', $id)->value('created_at');
            $ms = $started ? max(0, (int) (now()->getPreciseTimestamp(3) - \Carbon\Carbon::parse($started)->getPreciseTimestamp(3))) : 0;

            DB::table('brain_run_log')->where('id', $id)->update([
                'status' => $status,
                'summary' => mb_substr($summary, 0, 255),
                'detail' => json_encode($detail),
                'items_ok' => $itemsOk,
                'items_failed' => $itemsFailed,
                'duration_ms' => $ms,
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // ignore — logging must not break the job
        }
    }

    /** One-shot record for work whose duration doesn't matter. */
    public static function record(
        string $task,
        string $category,
        string $status,
        string $summary,
        array $detail = [],
        int $itemsOk = 0,
        int $itemsFailed = 0,
        string $trigger = 'schedule'
    ): void {
        try {
            DB::table('brain_run_log')->insert([
                'task' => $task,
                'category' => $category,
                'status' => $status,
                'summary' => mb_substr($summary, 0, 255),
                'detail' => json_encode($detail),
                'items_ok' => $itemsOk,
                'items_failed' => $itemsFailed,
                'trigger' => $trigger,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Skip writing a row for a no-op run (nothing was due). Otherwise the
     * every-5-minute social:publish would bury the log in "nothing to do".
     */
    public static function recordUnlessNoop(
        string $task,
        string $category,
        string $status,
        string $summary,
        array $detail = [],
        int $itemsOk = 0,
        int $itemsFailed = 0,
        string $trigger = 'schedule'
    ): void {
        if ($itemsOk === 0 && $itemsFailed === 0) {
            return;
        }

        self::record($task, $category, $status, $summary, $detail, $itemsOk, $itemsFailed, $trigger);
    }
}
