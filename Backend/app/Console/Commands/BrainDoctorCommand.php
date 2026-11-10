<?php

namespace App\Console\Commands;

use App\AI\ProviderManager;
use App\Brain\RunLog;
use App\Brain\Vault;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * One honest health report for the whole Growth OS.
 *
 * Every failure this command reports was already happening — silently. Search
 * Console had an OAuth client key pasted where a service-account key belongs
 * and reported "not configured"; Meta refused every metrics field with error
 * code 10 and `social:collect` logged it to a console nobody reads; the web
 * process could not write the file cache, so the circuit breaker was a no-op
 * and the AI Engine screen showed all providers healthy. The system had no
 * single place that said "this is broken and here is what to do about it".
 *
 * Rules this command follows:
 *  - A check reports FAIL only when the thing is genuinely unusable, never
 *    because a value is merely empty vs. saved-but-wrong — those are different
 *    diagnoses and the operator needs to be told which.
 *  - Every FAIL carries a concrete next action.
 *  - It never writes anything except its own RunLog row.
 */
class BrainDoctorCommand extends Command
{
    protected $signature = 'brain:doctor {--json : Emit machine-readable JSON}';

    protected $description = 'Diagnose every Growth OS subsystem and report what is actually broken';

    /** @var array<int,array{area:string,status:string,detail:string,action:string}> */
    protected array $results = [];

    public function handle(ProviderManager $ai): int
    {
        $this->checkScheduler();
        $this->checkCacheWritable();
        $this->checkTraffic();
        $this->checkSearchConsole();
        $this->checkAiProviders($ai);
        $this->checkSocialAccounts();
        $this->checkContentPipeline();
        $this->checkBusinessReadiness();
        $this->checkPaymentGateway();
        $this->checkHttpStatus();

        if ($this->option('json')) {
            $this->line(json_encode($this->results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->render();

        $fails = count(array_filter($this->results, fn ($r) => $r['status'] === 'fail'));
        $warns = count(array_filter($this->results, fn ($r) => $r['status'] === 'warn'));

        RunLog::record(
            'brain:doctor',
            'health',
            $fails ? 'partial' : 'ok',
            sprintf('%d ok, %d warn, %d fail', count($this->results) - $fails - $warns, $warns, $fails),
            ['results' => $this->results],
            count($this->results) - $fails,
            $fails,
            'manual'
        );

        return $fails > 0 ? self::FAILURE : self::SUCCESS;
    }

    /* ------------------------------------------------------------ checks */

    protected function checkScheduler(): void
    {
        $last = DB::table('brain_run_log')->orderByDesc('id')->first();

        if (! $last) {
            $this->fail('Scheduler', 'No run has ever been logged.', 'Confirm the cron entry running `schedule:run` exists for this site user.');
            return;
        }

        $age = now()->diffInMinutes(\Carbon\Carbon::parse($last->created_at));

        // social:collect is hourly, so anything past ~90 minutes means the
        // scheduler itself has stopped, not that the queue is merely quiet.
        $age > 90
            ? $this->fail('Scheduler', "Last run was {$age} min ago ({$last->task}).", 'Check the crontab for `schedule:run` and the site user it runs as.')
            : $this->ok('Scheduler', "Last run {$age} min ago ({$last->task}).");
    }

    /**
     * The failure that made the circuit breaker a lie: artisan and the web
     * server are different users, and the file cache's shard directories are
     * created 0755 by whoever writes first.
     */
    protected function checkCacheWritable(): void
    {
        $store = config('cache.default');
        $probe = 'brain.doctor.probe.'.bin2hex(random_bytes(4));

        try {
            Cache::put($probe, 'x', 60);
            $readBack = Cache::get($probe) === 'x';
            Cache::forget($probe);
        } catch (\Throwable $e) {
            $this->fail('Cache', "Store '{$store}' threw: ".$e->getMessage(), 'Switch CACHE_DRIVER to `database` so cron and web share one writable store.');
            return;
        }

        if (! $readBack) {
            $this->fail('Cache', "Store '{$store}' accepted a write but read back nothing.", 'Switch CACHE_DRIVER to `database`.');
            return;
        }

        if ($store !== 'file') {
            $this->ok('Cache', "Store '{$store}' is writable and shared.");
            return;
        }

        // Writable from the CLI proves nothing about the web user, which is the
        // half that was actually broken. The file store's shards are created
        // 0755 by whoever writes first, so the failure appears only when two
        // different users write — one silently cannot overwrite the other's.
        // A single consistent owner is fine; more than one is the bug.
        $this->reportOwnership(
            'Cache',
            glob(storage_path('framework/cache/data/*'), GLOB_ONLYDIR) ?: [],
            "Using the file store (CACHE_DRIVER=file).",
            'Switch CACHE_DRIVER to `database` so cron and web cannot diverge, or run artisan only as the site user.'
        );

        // Compiled views are the same trap with a louder symptom: a stale file
        // owned by another user cannot be recompiled, so editing the template
        // 500s the page. That is exactly what took the AI Engine screen down.
        $this->reportOwnership(
            'Compiled views',
            glob(storage_path('framework/views/*.php')) ?: [],
            'Blade cache.',
            'Run artisan as the site user (runuser -u <site user> -- php artisan ...). A root-owned compiled view 500s the page the moment its template changes.'
        );
    }

    /**
     * Flag a storage directory whose contents are split across users — the
     * shape of every "it 500s only sometimes" bug on this box.
     *
     * @param  array<int,string>  $paths
     */
    protected function reportOwnership(string $area, array $paths, string $context, string $action): void
    {
        if ($paths === [] || ! function_exists('posix_getpwuid')) {
            $this->ok($area, $context.' Nothing cached yet.');

            return;
        }

        $owners = [];

        foreach (array_slice($paths, 0, 200) as $p) {
            $name = posix_getpwuid(fileowner($p))['name'] ?? null;

            if ($name !== null) {
                $owners[$name] = ($owners[$name] ?? 0) + 1;
            }
        }

        if (count($owners) <= 1) {
            $this->ok($area, $context.' All files owned by '.(array_key_first($owners) ?: 'one user').'.');

            return;
        }

        $summary = implode(', ', array_map(fn ($u, $n) => "{$u}({$n})", array_keys($owners), $owners));

        $this->fail($area, $context.' Files split across users: '.$summary.'.', $action);
    }

    protected function checkTraffic(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('traffic_daily')) {
            $this->fail('Traffic', 'traffic_daily table is missing.', 'Run `php artisan migrate`.');
            return;
        }

        $today = (int) DB::table('traffic_daily')->where('date', now()->toDateString())->value('pageviews');
        $week = (int) DB::table('traffic_daily')->where('date', '>=', now()->subDays(7)->toDateString())->sum('pageviews');

        if ($week === 0) {
            $this->fail('Traffic', 'No pageview recorded in 7 days.', 'Confirm TrafficTracker is attached to routes/website.php in RouteServiceProvider, and that the site is reachable.');
            return;
        }

        $this->ok('Traffic', "{$today} pageviews today, {$week} in the last 7 days.");
    }

    protected function checkSearchConsole(): void
    {
        $gsc = app(\App\services\SearchConsoleService::class);

        if (! $gsc->isConfigured()) {
            $this->fail('Search Console', $gsc->configError(), 'Paste a service-account JSON (not an OAuth client key) into the AI Engine vault, then share the GSC property with its client_email.');
            return;
        }

        $totals = $gsc->totals(now()->subDays(7)->toDateString(), now()->subDay()->toDateString());

        $totals['ok']
            ? $this->ok('Search Console', "{$totals['clicks']} clicks / {$totals['impressions']} impressions in the last 7 days.")
            : $this->fail('Search Console', $totals['error'], 'Confirm the property URL matches exactly and the service account has at least Restricted access.');

        if (Vault::get('ga4_property_id') === '') {
            $this->warn_('GA4', 'No ga4_property_id set.', 'Optional, but it is the only way to see sessions and conversions rather than search clicks alone.');
        }
    }

    protected function checkAiProviders(ProviderManager $ai): void
    {
        $breaker = method_exists($ai, 'breakerState') ? $ai->breakerState() : [];
        $configured = [];

        foreach ($ai->textProviders() as $slug => $driver) {
            try {
                if ($driver->isConfigured()) {
                    $configured[] = $slug;
                }
            } catch (\Throwable $e) {
                // A driver that cannot even answer is not configured.
            }
        }

        if ($configured === []) {
            $this->fail('AI providers', 'No text provider is configured.', 'Add at least one key in the AI Engine vault.');
            return;
        }

        $benched = array_keys(array_filter($breaker, fn ($b) => $b['benched'] ?? false));

        $benched
            ? $this->warn_('AI providers', 'Configured: '.implode(', ', $configured).'. Benched: '.implode(', ', $benched).'.', 'A benched provider is failing repeatedly — check its key and quota.')
            : $this->ok('AI providers', 'Configured: '.implode(', ', $configured).'.');

        // A provider that syncs zero models is not usable no matter what the
        // key looks like — that is how the primary gateway sat dead in the
        // chain while openrouter quietly carried every request.
        foreach ($configured as $slug) {
            $count = (int) DB::table('ai_models')->where('provider', $slug)->count();

            if ($count === 0) {
                $this->fail("AI · {$slug}", 'Key is set but the catalogue synced 0 models.', "Run `php artisan ai:sync-models` and read the error; the endpoint or key for {$slug} is probably wrong.");

                continue;
            }

            // A stale catalogue is more dangerous than an empty one: the UI
            // lists thousands of models that were real four days ago and may
            // not be any more, and nothing about the screen says so.
            $last = DB::table('ai_models')->where('provider', $slug)->max('last_synced_at');

            if ($last && \Carbon\Carbon::parse($last)->lt(now()->subDays(2))) {
                $this->fail(
                    "AI · {$slug}",
                    sprintf('%d models listed but last synced %s.', $count, \Carbon\Carbon::parse($last)->diffForHumans()),
                    "The sync is failing while old rows stay on display. Run `php artisan ai:sync-models` and read the error for {$slug}."
                );
            }
        }

        // The chain is a comma list edited by hand in the vault; an empty slot
        // (from deleting a name and leaving its comma) is skipped silently.
        $rawChain = Vault::aiCredentials()['ai_text_chain'];

        if (preg_match('/,\s*,|^\s*,|,\s*$/', $rawChain)) {
            $this->warn_('AI chain', "ai_text_chain has an empty entry: '{$rawChain}'.", 'Harmless today because empty names are filtered, but it means a provider was removed by hand — confirm the order is what you intend.');
        }

        $this->checkMediaChains($ai);
    }

    /**
     * Image and video are configured entirely in the vault, which is what made
     * them fail invisibly for weeks: `ai_image_chain` named a provider with no
     * driver, and `ai_video_models` held bare ids the gateway rejects outright.
     * Neither produced an error anywhere — the chain simply skipped every entry
     * and reported "chain failed".
     */
    protected function checkMediaChains(ProviderManager $ai): void
    {
        $creds = Vault::aiCredentials();

        foreach ([
            'image' => [$creds['ai_image_chain'], array_keys($ai->imageProviders())],
            'video' => [$creds['ai_video_chain'] ?? '', array_keys($ai->videoProviders())],
        ] as $kind => [$chain, $have]) {
            $named = array_filter(array_map('trim', explode(',', (string) $chain)));
            $orphans = array_values(array_diff($named, $have));

            if ($named === []) {
                $this->fail(ucfirst($kind).' chain', "ai_{$kind}_chain is empty.", "Nothing can generate {$kind} content until a provider is listed.");

                continue;
            }

            $orphans
                ? $this->fail(ucfirst($kind).' chain', 'Names a provider with no driver: '.implode(', ', $orphans).'.', 'Chain entries without a driver are skipped in silence — remove them or add the driver.')
                : $this->ok(ucfirst($kind).' chain', implode(' -> ', $named).'.');
        }

        // OmniRoute rejects a bare video id ("Invalid video model: sora-2. Use
        // format: provider/model"), and every id must exist in the synced
        // catalogue or the whole chain burns its attempts on 400s.
        $videoModels = array_filter(array_map('trim', explode(',', (string) ($creds['ai_video_models'] ?? ''))));

        if ($videoModels === []) {
            $this->warn_('Video models', 'ai_video_models is empty.', 'The chain falls back to a single model id; list several so one dead upstream does not stop reels entirely.');

            return;
        }

        $bare = array_values(array_filter($videoModels, fn ($m) => ! str_contains($m, '/')));
        $unknown = array_values(array_filter(
            $videoModels,
            fn ($m) => str_contains($m, '/') && ! DB::table('ai_models')
                ->where('provider', 'omniroute')->where('kind', 'video')->where('model_id', $m)->exists()
        ));

        if ($bare || $unknown) {
            $this->fail(
                'Video models',
                trim(($bare ? 'Bare ids the gateway will reject: '.implode(', ', $bare).'. ' : '')
                    .($unknown ? 'Not in the synced video catalogue: '.implode(', ', $unknown).'.' : '')),
                'Use provider/model ids that appear in ai_models with kind=video, e.g. from `ai:sync-models`.'
            );

            return;
        }

        $this->ok('Video models', count($videoModels).' model(s), all present in the synced video catalogue.');

        $chain = array_filter(array_map('trim', explode(',', Vault::aiCredentials()['ai_text_chain'])));
        $missing = array_values(array_diff($chain, array_keys($ai->textProviders())));

        if ($missing) {
            $this->warn_('AI chain', 'Chain names a provider with no driver: '.implode(', ', $missing).'.', 'Fix ai_text_chain in the vault — these entries are skipped silently.');
        }
    }

    /**
     * Requested scopes are not granted scopes. Meta grants a subset and errors
     * on nothing, so the only truthful source is debug_token.
     */
    protected function checkSocialAccounts(): void
    {
        $accounts = DB::table('social_accounts')->where('status', 'connected')->get();

        if ($accounts->isEmpty()) {
            $this->fail('Social', 'No connected accounts.', 'Connect at least one account under Growth OS -> Social.');
            return;
        }

        $creds = Vault::socialCredentials();
        $fbAppId = $creds['fb_app_id'] ?? ($creds['meta_app_id'] ?? null);
        $fbSecret = $creds['fb_app_secret'] ?? ($creds['meta_app_secret'] ?? null);
        $appToken = (!empty($fbAppId) && !empty($fbSecret))
            ? $fbAppId.'|'.$fbSecret
            : '';

        foreach ($accounts as $acc) {
            if (! in_array($acc->platform, ['facebook', 'instagram'], true) || $appToken === '') {
                $this->ok('Social · '.$acc->platform, $acc->label.' connected.');

                continue;
            }

            try {
                $token = \Illuminate\Support\Facades\Crypt::decryptString($acc->access_token_enc);
            } catch (\Throwable $e) {
                $this->fail('Social · '.$acc->platform, 'Stored token could not be decrypted.', 'Reconnect the account.');

                continue;
            }

            try {
                $res = Http::timeout(20)->get('https://graph.facebook.com/debug_token', [
                    'input_token' => $token,
                    'access_token' => $appToken,
                ]);
            } catch (\Throwable $e) {
                $this->warn_('Social · '.$acc->platform, 'debug_token unreachable: '.$e->getMessage(), 'Retry; this is a network problem, not a config one.');

                continue;
            }

            $granted = (array) $res->json('data.scopes', []);
            $needed = ['read_insights', 'pages_read_user_content', 'instagram_manage_insights'];
            $absent = array_values(array_diff($needed, $granted));

            if ($absent) {
                $this->fail(
                    'Social · '.$acc->platform,
                    $acc->label.' is missing granted scopes: '.implode(', ', $absent).'.',
                    'These need Advanced Access via Meta App Review. Until they are granted, reach/reactions stay empty and the Learner cannot rank anything. Reconnect the account after approval.'
                );

                continue;
            }

            $this->ok('Social · '.$acc->platform, $acc->label.' has all metrics scopes granted.');
        }
    }

    protected function checkContentPipeline(): void
    {
        // All-time included the early chaotic period (429s from a since-fixed
        // provider chain, a Critic still being tuned) permanently baked into
        // the percentage — it read ~32% failed for weeks after the pipeline
        // had actually settled down, which is exactly the number an operator
        // would look at before deciding whether to raise the daily volume.
        // Report recent alongside all-time so a real regression isn't masked
        // by old history, and old history isn't mistaken for today's rate.
        $recentCutoff = now()->subDays(14);

        $allTime = DB::table('content_plans')->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');
        $recent = DB::table('content_plans')->where('created_at', '>=', $recentCutoff)
            ->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');

        $allFailed = (int) ($allTime['failed'] ?? 0) + (int) ($allTime['critic_failed'] ?? 0);
        $allTotal = max(1, array_sum($allTime->toArray()));
        $allRate = round($allFailed / $allTotal * 100);

        $recentFailed = (int) ($recent['failed'] ?? 0) + (int) ($recent['critic_failed'] ?? 0);
        $recentTotal = array_sum($recent->toArray());
        $recentRate = $recentTotal > 0 ? round($recentFailed / $recentTotal * 100) : null;

        $summary = $recentTotal > 0
            ? "last 14d: {$recentRate}% ({$recentFailed}/{$recentTotal})  ·  all-time: {$allRate}% ({$allFailed}/{$allTotal})"
            : "no plans in the last 14 days  ·  all-time: {$allRate}% ({$allFailed}/{$allTotal})";

        ($recentRate ?? $allRate) >= 25
            ? $this->warn_('Content pipeline', "{$summary}.", 'Read the critic_note on recent failures — usually a model returning prose instead of JSON, or drafts landing under the length floor.')
            : $this->ok('Content pipeline', "{$summary}.");

        $this->checkKeywordHeadroom();
    }

    /**
     * Does the keyword pool actually have the runway to sustain the configured
     * daily draft rate? Volume was raised without checking this once already
     * (this exact gap was found and fixed 2026-09-12) — the pool was 220
     * keywords across 22 clusters, which covers roughly 3 weeks of drafting
     * before the diversity guard runs out of distinct clusters and starts
     * relaxing to same-cluster picks, reviving the near-duplicate risk this
     * pipeline has already been burned by once.
     */
    protected function checkKeywordHeadroom(): void
    {
        $dailyTarget = max(1, (int) \App\Brain\Vault::get('content_daily_target', '3'));

        $pool = DB::table('keywords')->where('status', 'new')->where('difficulty', '<=', 60)->count();
        $clusters = DB::table('keywords')->where('status', 'new')->where('difficulty', '<=', 60)
            ->distinct('cluster')->count('cluster');

        // A month of headroom is the bar: below it, the pool empties or the
        // cluster diversity collapses before the next scheduled top-up can
        // plausibly refill it.
        $daysOfPool = intdiv($pool, $dailyTarget);
        $daysOfClusters = intdiv($clusters, $dailyTarget);
        $days = min($daysOfPool, $daysOfClusters);

        // The early `return` this branch used to have meant checkContentRamp()
        // below never ran whenever headroom was WARNing — which, working as
        // intended, is most of the time during the ramp. Both checks now
        // always run; nether should ever gate the other.
        if ($days < 30) {
            $this->warn_(
                'Keyword headroom',
                sprintf(
                    'At %d/day: %d fresh keyword(s) across %d cluster(s) ≈ %d day(s) of runway before repeats or a stalled pipeline.',
                    $dailyTarget, $pool, $clusters, $days
                ),
                'Run `php artisan seo:research` (now daily-scheduled) more often, or widen brain_seed_keywords further, before raising content_daily_target.'
            );
        } else {
            $this->ok('Keyword headroom', sprintf('%d fresh keyword(s), %d cluster(s) ≈ %d+ days at %d/day.', $pool, $clusters, $days, $dailyTarget));
        }

        $this->checkContentRamp();
    }

    /**
     * Surface the staged 3->5->7->10/day plan's actual state — otherwise the
     * only record of "held back this stage, headroom too thin" lives in a
     * RunLog row nobody would think to go read.
     */
    protected function checkContentRamp(): void
    {
        $lastRun = DB::table('brain_run_log')->where('task', 'content:ramp')->orderByDesc('id')->first();

        if (! $lastRun) {
            return; // not scheduled to have run yet on a fresh box
        }

        $ageHours = now()->diffInHours(\Carbon\Carbon::parse($lastRun->created_at));

        if ($ageHours > 30) {
            $this->warn_('Content ramp', "content:ramp last ran {$ageHours}h ago — should be daily.", 'Check the schedule; content_daily_target may be stuck on an old stage.');

            return;
        }

        $lastRun->status === 'partial'
            ? $this->warn_('Content ramp', $lastRun->summary, 'Expected while the keyword pool catches up — seo:research runs daily now and will unblock the next stage on its own.')
            : $this->ok('Content ramp', $lastRun->summary);
    }

    /**
     * The check the whole system was missing: is there anything to sell, and is
     * anyone buying? Growth work that outruns the catalogue is wasted spend,
     * and nothing in the OS could see that.
     */
    protected function checkBusinessReadiness(): void
    {
        $a = app(\App\Brain\BusinessReadiness::class)->assess();

        if ($a['ready']) {
            $this->ok('Catalogue', "{$a['bookable']} bookable pujas, {$a['astrologers']} astrologers (readiness {$a['score']}/100).");
        } elseif (($a['drafts'] ?? 0) > 0 && $a['bookable'] === 0) {
            // A distinct state: the catalogue work is done, it just needs the
            // operator to review and flip it live. Not the same as "broken".
            $this->warn_(
                'Catalogue',
                "{$a['drafts']} priced puja draft(s) built and awaiting review; 0 are live yet (readiness {$a['score']}/100).",
                'Review them in the admin puja list, then: php artisan puja:catalogue --activate'
            );
        } else {
            // "3 pujas listed" reads as a small catalogue. "0 of 3 bookable,
            // all unpriced and expired" reads as a storefront that cannot take
            // money — which is what it actually was, unnoticed for weeks.
            $this->fail(
                'Catalogue',
                sprintf(
                    '%d of %d puja(s) reach checkout (%d with no priced package, %d priced like test data, %d expired). Readiness %d/100.',
                    $a['bookable'], $a['listed'], $a['unpriced'], $a['underpriced'] ?? 0, $a['expired'], $a['score']
                ),
                implode(' ', $a['blockers']).' Demand generation is throttled until this clears — traffic sent to an unbuyable catalogue is spend, not growth.'
            );
        }

        $a['orders'] === 0
            ? $this->fail('Conversion', 'Zero orders recorded, ever.', 'With pujas live and traffic arriving, a persistent zero points at the checkout itself — see the Payment gateway check below before spending more on demand.')
            : $this->ok('Conversion', "{$a['orders']} order(s) recorded.");
    }

    /**
     * The check that reframes "zero orders". The catalogue can be perfect and
     * the traffic real, and the funnel still converts nobody if the payment
     * step throws. `PaymentMode` in `systemflag` names the active gateway; its
     * keys are shipped as the literal string "Enter your ... Key" and the
     * checkout initialises the gateway SDK with whatever is there — an
     * unreplaced placeholder means the pay modal never opens, and there is no
     * error anywhere the operator would see.
     */
    protected function checkPaymentGateway(): void
    {
        $mode = (string) DB::table('systemflag')->where('name', 'PaymentMode')->value('value');

        if ($mode === '') {
            $this->fail('Payment gateway', 'No PaymentMode set in systemflag.', 'Set PaymentMode (e.g. RazorPay) and its live keys in the admin, or no online payment can be taken.');

            return;
        }

        // The key flag names per gateway, live first then test.
        $keyFlags = [
            'RazorPay'   => ['razorpayLivekeyId', 'razorpayLiveSecretkey', 'razorpayTestingkeyId'],
            'PayU'       => ['merchantLiveKey', 'merchantLiveSalt', 'merchantTestKey'],
            'Paytm'      => ['paytmMerchantLiveMidKey'],
            'Stripe'     => ['stripeLiveKey', 'stripeLiveSecretKey'],
            'Instamojo'  => ['instamojoLiveApiKey', 'instamojoLiveAuthTokenKey'],
            'Paypal'     => ['paypalLiveClientKey', 'paypalLiveSecretKey'],
            'Paystack'   => ['paystackLiveSecretKey'],
        ];

        $flags = $keyFlags[$mode] ?? [];

        if ($flags === []) {
            $this->warn_('Payment gateway', "PaymentMode is '{$mode}' — no key check wired for it.", 'Confirm its live keys are real in the admin.');

            return;
        }

        $values = DB::table('systemflag')->whereIn('name', $flags)->pluck('value', 'name');
        $bad = [];

        foreach ($flags as $flag) {
            $v = trim((string) ($values[$flag] ?? ''));

            // Placeholder ("Enter your ..."), empty, or an obvious sample value.
            if ($v === '' || stripos($v, 'enter your') !== false || stripos($v, 'your ') === 0) {
                $bad[] = $flag;
            }
        }

        if ($bad) {
            $this->fail(
                'Payment gateway',
                "PaymentMode is '{$mode}' but its keys are still placeholders: ".implode(', ', $bad).'.',
                'THIS is why orders are zero. A visitor can browse, pick a puja, log in and reach checkout — then the '
                    .$mode.' modal fails to open because the key is the literal "Enter your..." string. Paste real live keys '
                    .'in Admin → Settings → Payment. Nothing in the funnel converts until this is done.'
            );

            return;
        }

        $this->ok('Payment gateway', "{$mode} configured with non-placeholder keys.");
    }

    /**
     * A handler that returns 200 for everything keeps dead URLs indexed
     * forever — an invisible, compounding SEO wound.
     */
    protected function checkHttpStatus(): void
    {
        $url = rtrim(config('app.url') ?: 'https://onlinepuja.live', '/').'/this-page-should-not-exist-'.bin2hex(random_bytes(4));

        try {
            $res = Http::timeout(20)->withoutVerifying()->withHeaders(['User-Agent' => 'BrainDoctor/1.0'])->get($url);
        } catch (\Throwable $e) {
            $this->warn_('HTTP status', 'Could not probe a 404: '.$e->getMessage(), 'Check the site is reachable from the server itself.');
            return;
        }

        $res->status() === 404
            ? $this->ok('HTTP status', 'Unknown URLs correctly return 404.')
            : $this->fail('HTTP status', "An unknown URL returned HTTP {$res->status()} instead of 404.", 'Retired and mistyped pages stay indexed forever at 200. Fix the exception handler.');
    }

    /* ----------------------------------------------------------- helpers */

    protected function tableCount(string $table): int
    {
        try {
            return (int) DB::table($table)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function ok(string $area, ?string $detail = ''): void
    {
        $this->results[] = ['area' => $area, 'status' => 'ok', 'detail' => (string) ($detail ?? ''), 'action' => ''];
    }

    protected function warn_(string $area, ?string $detail = '', ?string $action = ''): void
    {
        $this->results[] = ['area' => $area, 'status' => 'warn', 'detail' => (string) ($detail ?? ''), 'action' => (string) ($action ?? '')];
    }

    protected function fail(string $area, ?string $detail = '', ?string $action = ''): void
    {
        $this->results[] = ['area' => $area, 'status' => 'fail', 'detail' => (string) ($detail ?? ''), 'action' => (string) ($action ?? '')];
    }

    protected function render(): void
    {
        $icon = ['ok' => '<fg=green>PASS</>', 'warn' => '<fg=yellow>WARN</>', 'fail' => '<fg=red>FAIL</>'];

        $this->newLine();
        $this->line('  <options=bold>Growth OS health report</> — '.now()->toDateTimeString());
        $this->newLine();

        foreach ($this->results as $r) {
            $this->line(sprintf('  %s  <options=bold>%s</>', $icon[$r['status']], $r['area']));
            $this->line('        '.$r['detail']);

            if ($r['action'] !== '') {
                $this->line('        <fg=cyan>-> '.$r['action'].'</>');
            }

            $this->newLine();
        }
    }
}
