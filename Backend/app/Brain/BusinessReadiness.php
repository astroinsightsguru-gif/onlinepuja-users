<?php

namespace App\Brain;

use Illuminate\Support\Facades\DB;

/**
 * Can this business actually absorb the traffic the Growth OS is generating?
 *
 * The engine had no concept of this and it cost the whole first campaign. On
 * 2026-09-06 the site was serving ~100 real pageviews a day, drafting three
 * articles a day toward a "100,000 visitors in 50 days" goal, and had:
 *
 *   - 3 pujas listed, every one of them with a NULL price (the price had been
 *     typed into `puja_place`, which is why it looked populated in the admin),
 *   - every one of them scheduled to end in December 2025 — nine months expired,
 *   - 0 bookable products by any honest definition, and 0 orders, ever.
 *
 * Traffic sent to a storefront that cannot be bought from is not growth, it is
 * spend. Worse, it is spend that looks like progress on a visitor-counting
 * dashboard, which is exactly why it ran for weeks unnoticed.
 *
 * So readiness is measured first, and demand generation is gated behind it.
 * The rule the whole system now follows: never scale demand past the ability to
 * serve it — emit supply tasks instead.
 */
class BusinessReadiness
{
    /** Below this many bookable products, demand work is mostly wasted. */
    public const MIN_BOOKABLE = 5;

    /** A healthy catalogue for a puja marketplace of this shape. */
    public const TARGET_BOOKABLE = 20;

    /**
     * Under this, a listing is test data rather than an offer. The seeded
     * catalogue sold every puja for ten rupees, which is both unprofitable and
     * a visible signal to any visitor that the site is not real.
     */
    public const MIN_REALISTIC_PRICE = 100;

    /**
     * A full readiness assessment.
     *
     * @return array{
     *   score:int, ready:bool, bookable:int, listed:int, unpriced:int,
     *   expired:int, astrologers:int, orders:int, blockers:array<int,string>,
     *   tasks:array<int,array{title:string,why:string}>
     * }
     */
    public function assess(): array
    {
        // "Listed" means offered or awaiting review — a Rejected row is
        // de-listed and should not drag the numbers or the blockers.
        $offered = fn ($q) => $q->where('isAdminApproved', '!=', 'Rejected');

        $listed = $this->count($offered);
        // Bookable = live and purchasable right now. Priced-but-not-yet-active
        // drafts are counted separately so "in review" never reads as "broken".
        $bookable = $this->count(fn ($q) => $this->scopeBookable($offered($q)));
        $withPackage = $this->count(fn ($q) => $this->scopeHasPackage($offered($q)));
        $unpriced = max(0, $listed - $withPackage);
        $drafts = max(0, $withPackage - $bookable);
        $underpriced = $this->underpriced();
        $expired = $this->count(fn ($q) => $offered($q)->whereNotNull('puja_end_datetime')->where('puja_end_datetime', '<', now()));

        $astrologers = $this->tableCount('astrologers');
        $orders = $this->tableCount('puja_orders') + $this->tableCount('user_orders');

        $blockers = [];
        $tasks = [];

        if ($listed === 0) {
            $blockers[] = 'No pujas are listed at all.';
            $tasks[] = [
                'title' => 'Publish the first bookable pujas',
                'why' => 'There is nothing on the site to buy. Every visitor the engine earns is lost on arrival.',
            ];
        }

        if ($unpriced > 0) {
            $blockers[] = "{$unpriced} of {$listed} puja(s) have no purchasable package.";
            $tasks[] = [
                'title' => "Attach a priced package to {$unpriced} puja(s)",
                'why' => 'Checkout charges puja_package.package_price via the puja\'s package_id list. '
                    .'A puja with no active, priced package in that list cannot be bought, '
                    .'however complete the listing looks in the admin.',
            ];
        }

        if ($drafts > 0) {
            // Not a blocker — a nudge. The work is done; it just needs a click.
            $tasks[] = [
                'title' => "Activate {$drafts} reviewed puja draft(s)",
                'why' => "{$drafts} puja(s) are fully built and priced but still hidden "
                    ."(puja_status=0 / Pending). Review them in admin, then run "
                    ."`php artisan puja:catalogue --activate`.",
            ];
        }

        if ($underpriced > 0) {
            $blockers[] = "{$underpriced} puja(s) priced under Rs".self::MIN_REALISTIC_PRICE.'.';
            $tasks[] = [
                'title' => "Set real prices on {$underpriced} puja(s)",
                'why' => 'These are seeded test amounts (the catalogue shipped selling every puja for Rs10). '
                    .'They cannot cover a pandit\'s time, and a visitor reads them as a site that is not real.',
            ];
        }

        if ($expired > 0) {
            $blockers[] = "{$expired} of {$listed} puja(s) ended in the past.";
            $tasks[] = [
                'title' => "Reschedule or retire {$expired} expired puja(s)",
                'why' => 'puja_end_datetime is in the past, so these are not offerable. '
                    .'Either give them upcoming dates or take them off the catalogue.',
            ];
        }

        // A reviewed draft is a click away from bookable, so it counts toward
        // clearing the floor — the catalogue work is done, not pending.
        if ($bookable + $drafts < self::MIN_BOOKABLE) {
            $blockers[] = "Only {$bookable} bookable puja(s) (+{$drafts} in review); the floor is ".self::MIN_BOOKABLE.'.';
            $tasks[] = [
                'title' => 'Grow the catalogue to at least '.self::TARGET_BOOKABLE.' bookable pujas',
                'why' => 'A visitor arriving from a festival article needs a matching puja to book. '
                    .'Below the floor, more articles cannot convert no matter how good they are.',
            ];
        }

        if ($astrologers < 3) {
            $blockers[] = "Only {$astrologers} astrologer(s) onboarded.";
            $tasks[] = [
                'title' => 'Onboard more verified pandits',
                'why' => 'Supply depth caps how many bookings can actually be fulfilled.',
            ];
        }

        // The floor test above credits reviewed drafts, but "ready" — the gate
        // that lets the engine spend on demand — needs pujas a visitor can buy
        // *today*. Drafts awaiting a click do not count here.
        if ($bookable < self::MIN_BOOKABLE && ! collect($blockers)->contains(fn ($b) => str_contains($b, 'the floor is'))) {
            $blockers[] = $drafts > 0
                ? "{$drafts} priced puja(s) built and awaiting activation; 0 are live and bookable."
                : "Only {$bookable} live bookable puja(s); the floor is ".self::MIN_BOOKABLE.'.';
        }

        return [
            'score' => $this->score($bookable, $astrologers, $drafts),
            'ready' => $blockers === [],
            'bookable' => $bookable,
            'listed' => $listed,
            'unpriced' => $unpriced,
            'underpriced' => $underpriced,
            'drafts' => $drafts,
            'expired' => $expired,
            'astrologers' => $astrologers,
            'orders' => $orders,
            'blockers' => $blockers,
            'tasks' => $tasks,
        ];
    }

    /**
     * How many articles a day it is honest to draft right now.
     *
     * Never zero: the engine should keep a slow evergreen cadence so the site
     * does not go stale while the catalogue is being fixed. But it should not
     * run at full velocity into a storefront nobody can buy from.
     */
    public function contentBudget(int $requested): int
    {
        if (Vault::get('growth_readiness_gate', 'on') === 'off') {
            return $requested;
        }

        $a = $this->assess();

        if ($a['ready']) {
            return $requested;
        }

        return $a['bookable'] === 0 ? 1 : max(1, (int) floor($requested / 2));
    }

    /**
     * Write the supply tasks into content_plans so they show up in the same
     * Command Center queue as everything else, rather than living only in a
     * report. Idempotent — a task already open is not duplicated.
     */
    public function emitSupplyTasks(): int
    {
        $created = 0;

        foreach ($this->assess()['tasks'] as $task) {
            $title = 'SUPPLY: '.$task['title'];

            $exists = DB::table('content_plans')
                ->where('title', $title)
                ->whereIn('status', ['idea', 'drafted', 'approved'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('content_plans')->insert([
                'title' => $title,
                'pillar' => 'supply',
                'channel' => 'ops',
                'status' => 'idea',
                'payload' => json_encode([
                    'why' => $task['why'],
                    'source' => 'business-readiness',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * 0-100, weighted toward catalogue depth because that is the binding
     * constraint. Reviewed-but-not-yet-activated drafts count at half weight:
     * the content exists, only the toggle is pending.
     */
    protected function score(int $bookable, int $astrologers, int $drafts = 0): int
    {
        $effective = $bookable + $drafts * 0.5;
        $catalogue = min(1.0, $effective / self::TARGET_BOOKABLE);
        $supply = min(1.0, $astrologers / 10);

        return (int) round(($catalogue * 0.75 + $supply * 0.25) * 100);
    }

    /**
     * Bookable means a visitor can actually reach checkout and pay.
     *
     * Note which price this reads. `pujas.puja_price` looks like the price and
     * is NULL on every row, but checkout never touches it: PujaController
     * resolves `pujas.package_id` (a JSON id list) through Puja::package() into
     * `puja_package`, and charges `package_price`. Gating on `puja_price` — as
     * this class first did — reported "0 bookable" for a catalogue whose
     * checkout worked fine at ten rupees. Wrong column, wrong verdict.
     *
     * Dates are deliberately not part of this. The listing page falls back to
     * showing expired pujas when nothing is upcoming, so an expired puja is
     * still reachable and still purchasable; it is a quality problem, counted
     * separately, not an availability one.
     */
    protected function scopeBookable($query)
    {
        // Match the public gate exactly, not a stricter one. The frontend puja
        // list shows `puja_status = 1 AND created_by = 'admin'`; the detail page
        // and checkout apply no status filter at all. `isAdminApproved` is an
        // internal workflow flag for astrologer-submitted pujas and does NOT
        // gate an admin-created listing — requiring 'Approved' here made the
        // dashboard report "0 bookable" while 20 pujas were live and taking
        // (attempted) checkouts on the site.
        return $this->scopeHasPackage($query)
            ->where('puja_status', 1)
            ->where('created_by', 'admin')
            ->where('isAdminApproved', '!=', 'Rejected');
    }

    /**
     * Has at least one active, priced package attached — the structural half of
     * bookable, without the live/approved gate. A priced draft in review passes
     * this but not scopeBookable(), which is how "in review" is kept distinct
     * from "misconfigured".
     */
    protected function scopeHasPackage($query)
    {
        return $query->whereExists(function ($q) {
            $q->selectRaw('1')
                ->from('puja_package')
                ->where('package_status', 1)
                ->where('package_price', '>', 0)
                // package_id is a JSON array of ids, e.g. ["2","4"].
                ->whereRaw('JSON_CONTAINS(pujas.package_id, JSON_QUOTE(CAST(puja_package.id AS CHAR)))');
        });
    }

    /**
     * Listings priced like test data. A real puja booking is not ten rupees;
     * a catalogue full of them converts nobody and signals an abandoned site
     * to anyone who lands on it.
     */
    protected function underpriced(): int
    {
        try {
            return (int) DB::table('pujas')
                ->where('puja_status', 1)
                ->whereExists(function ($q) {
                    $q->selectRaw('1')->from('puja_package')
                        ->where('package_status', 1)
                        ->where('package_price', '>', 0)
                        ->where('package_price', '<', self::MIN_REALISTIC_PRICE)
                        ->whereRaw('JSON_CONTAINS(pujas.package_id, JSON_QUOTE(CAST(puja_package.id AS CHAR)))');
                })
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function count(callable $scope): int
    {
        try {
            return (int) $scope(DB::table('pujas'))->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function tableCount(string $table): int
    {
        try {
            return (int) DB::table($table)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
