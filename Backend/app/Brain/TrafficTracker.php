<?php

namespace App\Brain;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * First-party traffic truth until Search Console / GA4 is connected.
 *
 * Records pageviews on the public site, excluding admin/API/assets and obvious
 * bots, into `traffic_daily` — which `goal:snapshot` reads as the fallback
 * traffic number when GSC is unavailable.
 *
 * Two things were wrong with the original and both produced the same symptom,
 * a permanent zero:
 *
 *  1. This middleware sits in the 'web' group, but the entire public site
 *     (routes/website.php) is registered with no group at all, so it never ran
 *     on a single public request. It is now attached directly in
 *     RouteServiceProvider.
 *  2. The counter lived in the file cache, so any `cache:clear` on a deploy
 *     erased the day's traffic with no trace. It is a table now.
 *
 * `sessions` is a cheap distinct-visitor estimate: a coarse per-visitor hash is
 * held in the cache for 30 minutes, and only the first pageview in that window
 * counts. Losing the cache costs a little over-counting, never a lost pageview.
 */
class TrafficTracker
{
    protected const SKIP_PREFIXES = [
        'admin', 'api', 'astrologer', 'livewire', 'vendor', 'build',
        'assets', 'images', 'storage', 'frontend', 'sound', 'kundli',
        'robots.txt', 'sitemap.xml', 'favicon.ico', 'ping.txt',
    ];

    /** How long one visitor's hash suppresses further session counting. */
    protected const SESSION_WINDOW_MINUTES = 30;

    public function handle(Request $request, Closure $next)
    {
        try {
            $this->record($request);
        } catch (\Throwable $e) {
            // Measurement must never take the site down.
        }

        return $next($request);
    }

    protected function record(Request $request): void
    {
        if (! $request->isMethod('GET')) {
            return;
        }

        $path = ltrim($request->path(), '/');

        foreach (self::SKIP_PREFIXES as $skip) {
            if ($path === $skip || str_starts_with($path, $skip.'/')) {
                return;
            }
        }

        $ua = (string) $request->userAgent();

        if ($ua === '' || preg_match('/bot|crawl|spider|slurp|curl|wget|python|monitor|uptime|pingdom|headless|lighthouse|preview/i', $ua)) {
            return;
        }

        $date = now()->toDateString();
        $isNewSession = $this->markSession($request, $date);

        // One statement, no read-modify-write, so concurrent requests cannot
        // lose a count to a race.
        DB::statement(
            'INSERT INTO traffic_daily (`date`, pageviews, sessions, updated_at)
             VALUES (?, 1, ?, ?)
             ON DUPLICATE KEY UPDATE pageviews = pageviews + 1, sessions = sessions + ?, updated_at = ?',
            [$date, $isNewSession ? 1 : 0, now(), $isNewSession ? 1 : 0, now()]
        );
    }

    /** True the first time this visitor is seen today within the window. */
    protected function markSession(Request $request, string $date): bool
    {
        $key = 'traffic.sess.'.$date.'.'.substr(sha1($request->ip().'|'.$request->userAgent()), 0, 16);

        if (Cache::has($key)) {
            return false;
        }

        Cache::put($key, 1, now()->addMinutes(self::SESSION_WINDOW_MINUTES));

        return true;
    }

    public static function today(): int
    {
        return self::pageviews(now()->toDateString());
    }

    public static function pageviews(string $date): int
    {
        try {
            return (int) DB::table('traffic_daily')->where('date', $date)->value('pageviews');
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function sessions(string $date): int
    {
        try {
            return (int) DB::table('traffic_daily')->where('date', $date)->value('sessions');
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * The recorded traffic for a date, for the nightly snapshot.
     *
     * Unlike the old cache version this is non-destructive: the history stays
     * in `traffic_daily`, so a re-run of `goal:snapshot` is idempotent instead
     * of silently reading zero the second time.
     */
    public static function take(string $date): int
    {
        return self::pageviews($date);
    }
}
