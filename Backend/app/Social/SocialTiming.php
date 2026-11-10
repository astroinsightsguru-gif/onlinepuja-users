<?php

namespace App\Social;

use Carbon\Carbon;

/**
 * Best-time-to-post per platform, tuned for an Indian (IST) audience. Posting
 * when the audience is actually scrolling is one of the cheapest reach
 * multipliers there is. Times are peak-engagement windows in Asia/Kolkata; the
 * scheduler snaps each post to the next such window so a batch spreads across
 * the day instead of firing all at once.
 */
class SocialTiming
{
    protected const TZ = 'Asia/Kolkata';

    /** Peak IST hours (24h) per platform, in priority order. */
    protected const PEAKS = [
        'instagram' => [12, 19, 21],
        'facebook'  => [13, 20, 9],
        'x'         => [9, 13, 18],
        'pinterest' => [21, 15, 12],
        'youtube'   => [17, 20, 11],
    ];

    /**
     * Peak hours for a platform: what live engagement has taught us if there
     * is enough of it, otherwise the hand-tuned IST windows above. The Learner
     * returns null until a platform has real evidence behind it, so this is
     * identical to the old constant on an empty database.
     *
     * @return array<int,int>
     */
    protected static function peaksFor(string $platform): array
    {
        try {
            $learned = app(\App\Brain\Learner::class)->bestHours($platform);
        } catch (\Throwable $e) {
            $learned = null; // Learning must never be able to stop posting.
        }

        return $learned ?: (self::PEAKS[$platform] ?? [12, 19]);
    }

    /**
     * Next optimal slot for a platform at or after $notBefore. $spreadIndex
     * nudges successive posts on the same platform to different peak hours (and
     * onto following days) so a fan-out doesn't stack them on one minute.
     */
    public static function nextSlot(string $platform, Carbon $notBefore, int $spreadIndex = 0): Carbon
    {
        $peaks = self::peaksFor($platform);
        $base = $notBefore->copy()->setTimezone(self::TZ);

        // Pick a peak hour by spread index so parallel posts differ.
        $hour = $peaks[$spreadIndex % count($peaks)];
        // Every full cycle through the peaks, push a day forward.
        $dayOffset = intdiv($spreadIndex, count($peaks));

        $slot = $base->copy()->addDays($dayOffset)->setTime($hour, random_int(0, 20), 0);

        // Never schedule in the past — roll to the next day's same peak.
        if ($slot->lessThan($base)) {
            $slot->addDay();
        }

        return $slot->setTimezone(config('app.timezone', 'UTC'));
    }

    /**
     * Next peak slot on the least-busy upcoming day for a platform.
     *
     * nextSlot() packs a single batch forward from one due date, which is fine
     * for a festival fan-out but leaves the calendar lumpy — the queue ends up
     * with six posts on one date and nothing for the ten days either side.
     * This instead looks at what is already scheduled and drops the post on the
     * earliest day inside the horizon that is still under $perDay, so a steady
     * cadence emerges without anything needing to know the queue's global shape.
     */
    public static function nextFreeSlot(string $platform, int $perDay = 1, int $horizonDays = 21, ?int $astrologerId = null): ?Carbon
    {
        $peaks = self::peaksFor($platform);
        $appTz = config('app.timezone', 'UTC');
        $today = Carbon::now(self::TZ)->startOfDay();

        // One grouped read rather than a query per candidate day.
        $counts = \DB::table('social_posts')
            ->where('platform', $platform)
            ->whereIn('status', ['queued', 'draft', 'publishing'])
            ->when($astrologerId === null,
                fn ($q) => $q->whereNull('astrologer_id'),
                fn ($q) => $q->where('astrologer_id', $astrologerId))
            ->where('scheduled_at', '>=', $today->copy()->setTimezone($appTz))
            ->selectRaw('DATE(scheduled_at) d, COUNT(*) c')
            ->groupBy('d')
            ->pluck('c', 'd');

        for ($offset = 0; $offset <= $horizonDays; $offset++) {
            $day = $today->copy()->addDays($offset);
            $taken = (int) ($counts[$day->format('Y-m-d')] ?? 0);

            if ($taken >= $perDay) {
                continue;
            }

            // Successive posts on the same day use successive peak hours.
            $slot = $day->copy()->setTime($peaks[$taken % count($peaks)], random_int(0, 20), 0);

            // A peak that has already passed today is not a slot.
            if ($slot->greaterThan(Carbon::now(self::TZ))) {
                return $slot->setTimezone($appTz);
            }
        }

        // Horizon is saturated. Returning a slot anyway would stack every
        // further post on one day past the edge, which is the lumpiness this
        // method exists to prevent — the caller should stop queueing instead.
        return null;
    }
}
