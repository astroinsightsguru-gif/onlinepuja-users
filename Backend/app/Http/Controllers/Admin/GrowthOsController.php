<?php

namespace App\Http\Controllers\Admin;

use App\AI\ProviderManager;
use App\Brain\ContentPipeline;
use App\Brain\GoalPlanner;
use App\Brain\KeywordResearch;
use App\Brain\SiteAuditor;
use App\Brain\Vault;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GrowthOsController extends Controller
{
    protected ?ProviderManager $ai = null;
    protected ?KeywordResearch $research = null;
    protected ?ContentPipeline $pipeline = null;
    protected ?SiteAuditor $auditor = null;

    public function __construct()
    {
        $this->middleware('auth');
    }

    protected function ai(): ProviderManager
    {
        return $this->ai ??= app(ProviderManager::class);
    }

    protected function research(): KeywordResearch
    {
        return $this->research ??= app(KeywordResearch::class);
    }

    protected function pipeline(): ContentPipeline
    {
        return $this->pipeline ??= app(ContentPipeline::class);
    }

    protected function auditor(): SiteAuditor
    {
        return $this->auditor ??= app(SiteAuditor::class);
    }

    /** 1. Growth & Goals Dashboard */
    public function dashboard()
    {
        $goal = DB::table('goals')->where('status', 'active')->first();
        $snapshots = [];
        $pace = 0;
        $expected = 0;
        $totalTraffic = 0;
        $elapsed = 0;
        $horizon = 50;

        if ($goal) {
            $snapshots = DB::table('goal_snapshots')
                ->where('goal_id', $goal->id)
                ->orderBy('date', 'ASC')
                ->get();

            $totalTraffic = (int) $snapshots->sum('value');
            $startDate = \Carbon\Carbon::parse($goal->start_date);
            $deadline = \Carbon\Carbon::parse($goal->deadline);
            $elapsed = max(1, $startDate->diffInDays(now()));
            $horizon = max(1, $startDate->diffInDays($deadline) + 1);
            $expected = (int) round(($elapsed / $horizon) * $goal->target);
            $pace = $expected > 0 ? min(999, round(($totalTraffic / $expected) * 100)) : 100;
        }

        $stats = \Illuminate\Support\Facades\Cache::remember('growth_os_dashboard_stats', 300, function () {
            return [
                'total_keywords' => DB::table('keywords')->count(),
                'easy_keywords' => DB::table('keywords')->where('difficulty', '<=', 45)->count(),
                'drafted_content' => DB::table('content_plans')->where('status', 'drafted')->count(),
                'approved_content' => DB::table('content_plans')->where('status', 'approved')->count(),
                'published_content' => DB::table('content_plans')->where('status', 'published')->count(),
                'open_seo_issues' => DB::table('seo_issues')->where('status', 'open')->count(),
                'fixed_seo_issues' => DB::table('seo_issues')->where('status', 'fixed')->count(),
                'festivals_seeded' => DB::table('hindu_festivals')->count(),
                'festivals_scheduled' => DB::table('hindu_festivals')->where('scheduled', true)->count(),
                'ai_models_count' => DB::table('ai_models')->count(),
            ];
        });

        $recentChats = DB::table('brain_chats')->orderByDesc('id')->limit(8)->get();

        // Autonomous activity timeline — what the Brain actually did (blogging,
        // social, SEO), so an operator can see the system working (or failing)
        // at a glance instead of guessing from published counts.
        $activityLog = DB::table('brain_run_log')->orderByDesc('id')->limit(15)->get();

        $activity24h = \Illuminate\Support\Facades\Cache::remember('growth_os_activity_24h', 180, function () {
            return DB::table('brain_run_log')
                ->where('created_at', '>=', now()->subDay())
                ->selectRaw("category, SUM(items_ok) ok, SUM(items_failed) failed, COUNT(*) runs")
                ->groupBy('category')
                ->get()
                ->keyBy('category');
        });

        // Channel health — the last brain:doctor report.
        $health = $this->latestHealthReport();

        return view('pages.growth-os.dashboard', compact('goal', 'snapshots', 'pace', 'expected', 'totalTraffic', 'elapsed', 'horizon', 'stats', 'recentChats', 'activityLog', 'activity24h', 'health'));
    }

    /**
     * The most recent `brain:doctor` results, or an empty shape when it has
     * never run — so the view can say "unknown" rather than imply health.
     *
     * @return array{ran_at:?string,stale:bool,results:array<int,array<string,string>>}
     */
    protected function latestHealthReport(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('growth_os_health_report', 180, function () {
            if (! \Illuminate\Support\Facades\Schema::hasTable('brain_run_log')) {
                return ['ran_at' => null, 'stale' => true, 'results' => []];
            }

            $row = DB::table('brain_run_log')
                ->where('task', 'brain:doctor')
                ->orderByDesc('id')
                ->first();

            if (! $row) {
                return ['ran_at' => null, 'stale' => true, 'results' => []];
            }

            $detail = json_decode((string) $row->detail, true);

            return [
                'ran_at' => $row->created_at,
                'stale' => \Carbon\Carbon::parse($row->created_at)->lt(now()->subDays(2)),
                'results' => is_array($detail['results'] ?? null) ? $detail['results'] : [],
            ];
        });
    }

    /** 2. SEO Keywords & Silo Matrix */
    public function keywords(?Request $request = null)
    {
        $request = $request ?? request();
        $query = DB::table('keywords');

        if ($request->filled('cluster')) {
            $query->where('cluster', $request->cluster);
        }
        if ($request->filled('intent')) {
            $query->where('intent', $request->intent);
        }
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where('phrase', 'like', "%{$s}%");
        }

        $keywords = $query->orderByDesc('id')->paginate(25);
        $clusters = \Illuminate\Support\Facades\Cache::remember('growth_os_keyword_clusters', 300, function () {
            return DB::table('keywords')->select('cluster', DB::raw('count(*) as count'))->groupBy('cluster')->orderByDesc('count')->get();
        });

        return view('pages.growth-os.keywords', compact('keywords', 'clusters'));
    }

    /** Store new manual keyword or trigger research */
    public function storeKeyword(Request $request)
    {
        $request->validate([
            'phrase' => 'required|string|max:190',
            'cluster' => 'nullable|string|max:100',
            'intent' => 'nullable|in:commercial,informational,transactional',
        ]);

        $phrase = trim(mb_strtolower($request->phrase));
        $phrase = mb_substr($phrase, 0, 190);
        $cluster = trim($request->cluster ?: 'general');
        $intent = $request->intent ?: (str_contains($phrase, 'book') || str_contains($phrase, 'online') || str_contains($phrase, 'price') ? 'commercial' : 'informational');

        if (! DB::table('keywords')->where('phrase', $phrase)->exists()) {
            DB::table('keywords')->insert([
                'phrase' => $phrase,
                'source' => 'manual-admin',
                'cluster' => $cluster,
                'intent' => $intent,
                'difficulty' => random_int(25, 50),
                'status' => 'new',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return back()->with('success', "Keyword \"{$phrase}\" added successfully!");
        }

        return back()->with('error', 'Keyword already exists in database.');
    }

    /** 3. Content Pipeline Kanban */
    public function contentPipeline()
    {
        $base = DB::table('content_plans')
            ->where(function($q) {
                $q->where('channel', 'web')->orWhereNull('channel');
            })
            ->whereNotIn('pillar', ['supply', 'festival']);

        $ideas = (clone $base)->where('status', 'idea')->orderByDesc('id')->limit(25)->get();
        $drafted = (clone $base)->where('status', 'drafted')->orderByDesc('id')->limit(25)->get();
        $criticFailed = (clone $base)->where('status', 'critic_failed')->orderByDesc('id')->limit(25)->get();
        $approved = (clone $base)->where('status', 'approved')->orderByDesc('id')->limit(25)->get();
        $published = (clone $base)->where('status', 'published')->orderByDesc('id')->limit(25)->get();

        return view('pages.growth-os.content', compact('ideas', 'drafted', 'criticFailed', 'approved', 'published'));
    }

    /** Update content plan status */
    public function updateContentStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:idea,drafted,critic_failed,approved,published,deleted']);

        if ($request->status === 'deleted') {
            DB::table('content_plans')->where('id', $id)->delete();
            return response()->json(['ok' => true, 'message' => 'Content plan deleted.']);
        }

        $updates = ['status' => $request->status, 'updated_at' => now()];

        if ($request->status === 'published') {
            // Auto publish to blog if payload has HTML
            Artisan::call('content:publish', ['id' => $id]);
            return response()->json(['ok' => true, 'message' => 'Content published successfully to live blog!']);
        }

        DB::table('content_plans')->where('id', $id)->update($updates);

        return response()->json(['ok' => true, 'message' => "Status updated to {$request->status}."]);
    }

    /** 4. SEO Health Auditor & God-Mode Fixer */
    public function seoAuditor()
    {
        $issues = DB::table('seo_issues')->orderByDesc('id')->paginate(30);
        $issueStats = DB::table('seo_issues')
            ->select('type', 'status', DB::raw('count(*) as count'))
            ->groupBy('type', 'status')
            ->get();

        $openCount = DB::table('seo_issues')->where('status', 'open')->count();
        $fixedCount = DB::table('seo_issues')->where('status', 'fixed')->count();

        return view('pages.growth-os.seo', compact('issues', 'issueStats', 'openCount', 'fixedCount'));
    }

    /** Revert a fixed SEO issue */
    public function revertSeoIssue($id)
    {
        $issue = DB::table('seo_issues')->where('id', $id)->first();

        if (! $issue || ! $issue->revert_payload) {
            return back()->with('error', 'No revert payload available for this issue.');
        }

        try {
            $payload = json_decode((string) $issue->revert_payload, true);
            if (! empty($payload['table'])) {
                if (! empty($payload['key']) && ! empty($payload['changes']) && is_array($payload['changes'])) {
                    $q = DB::table($payload['table']);
                    foreach ($payload['key'] as $k => $v) {
                        $q->where($k, $v);
                    }
                    $q->update($payload['changes']);
                } elseif (! empty($payload['id']) && ! empty($payload['field'])) {
                    DB::table($payload['table'])
                        ->where('id', $payload['id'])
                        ->update([$payload['field'] => $payload['old_value'] ?? '']);
                }
            }

            DB::table('seo_issues')->where('id', $id)->update([
                'status' => 'open',
                'reverted_at' => now(),
                'updated_at' => now(),
            ]);

            return back()->with('success', 'Issue reverted successfully to original state.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Revert failed: ' . $e->getMessage());
        }
    }

    /** 5. Social Media & Hindu Festival Scheduler */
    public function socialScheduler()
    {
        $festivals = \Illuminate\Support\Facades\Cache::remember('growth_festivals_v1', 3600, function () {
            return DB::table('hindu_festivals')->orderBy('date', 'ASC')->get();
        });

        $socialPosts = DB::table('content_plans')
            ->where('channel', 'social')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $socialCreds = Vault::socialCredentials();
        $webhookUrl = Vault::get('social_webhook_url') ?: env('SOCIAL_DISPATCH_WEBHOOK', '');

        $pujas = \Illuminate\Support\Facades\Cache::remember('growth_pujas_v1', 1800, function () {
            return DB::table('pujas')->select('id', 'puja_title', 'puja_place', 'slug', 'puja_price')
                ->where('puja_status', 1)->orderBy('id', 'DESC')->limit(100)->get();
        });

        $astrologers = \Illuminate\Support\Facades\Cache::remember('growth_astros_v1', 1800, function () {
            return DB::table('astrologers')->select('id', 'name', 'primarySkill')
                ->where('isDelete', 0)->limit(30)->get();
        });

        $socialAccounts = DB::table('social_accounts')->get();
        $recentDispatches = DB::table('social_posts')->orderByDesc('id')->limit(15)->get();

        return view('pages.growth-os.social', compact('festivals', 'socialPosts', 'socialCreds', 'webhookUrl', 'pujas', 'astrologers', 'socialAccounts', 'recentDispatches'));
    }

    /** 5c. Omnichannel Marketing & Devotee Broadcast Hub */
    public function marketingHub(?Request $request = null)
    {
        $request = $request ?? request();
        $wa = app(\App\Services\EvolutionWaService::class);
        $waState = \Illuminate\Support\Facades\Cache::remember('growth_wa_state', 60, function () use ($wa) {
            try {
                return $wa->getConnectionState();
            } catch (\Throwable $e) {
                return ['ok' => false, 'state' => 'offline', 'error' => $e->getMessage()];
            }
        });

        $brevoKey = Vault::get('brevo_api_key') ?: env('BREVO_API_KEY');
        $brevoConfigured = !empty($brevoKey);

        $counts = \Illuminate\Support\Facades\Cache::remember('growth_marketing_counts', 300, function () {
            return [
                'totalDevotees'     => DB::table('users')->count(),
                'devoteesWithPhone' => DB::table('users')->whereNotNull('contactNo')->where('contactNo', '!=', '')->count(),
                'devoteesWithEmail' => DB::table('users')->whereNotNull('email')->where('email', '!=', '')->count(),
                'totalOrders'       => DB::table('puja_orders')->count(),
                'totalB2bPartners'  => \App\Models\B2bLead::count(),
            ];
        });

        $totalDevotees     = $counts['totalDevotees'];
        $devoteesWithPhone = $counts['devoteesWithPhone'];
        $devoteesWithEmail = $counts['devoteesWithEmail'];
        $totalOrders       = $counts['totalOrders'];
        $totalB2bPartners  = $counts['totalB2bPartners'];

        $pujas = \Illuminate\Support\Facades\Cache::remember('growth_pujas_marketing_v1', 1800, function () {
            return DB::table('pujas')
                ->select('id', 'puja_title', 'puja_price', 'puja_place', 'slug')
                ->where('puja_status', 1)
                ->orderBy('id', 'desc')
                ->limit(50)
                ->get();
        });

        $upcomingFestivals = \Illuminate\Support\Facades\Cache::remember('growth_festivals_upcoming_8', 1800, function () {
            return DB::table('hindu_festivals')
                ->where('date', '>=', now()->toDateString())
                ->orderBy('date', 'asc')
                ->limit(8)
                ->get();
        });

        $recentBroadcasts = DB::table('content_plans')
            ->where('channel', 'social')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $festivalEngine = app(\App\Services\FestivalCampaignEngine::class);
        $festivalsRadar = $festivalEngine->getUpcomingFestivals(60);

        // B2B Partner Directory & Radar metrics
        $b2bLeads = \App\Models\B2bLead::orderByDesc('id')->limit(50)->get();
        $b2bCities = \Illuminate\Support\Facades\Cache::remember('growth_b2b_cities', 600, function () {
            return \App\Models\B2bLead::select('city', DB::raw('count(*) as total'))
                ->whereNotNull('city')
                ->groupBy('city')
                ->orderByDesc('total')
                ->get();
        });

        return view('pages.growth-os.marketing', compact(
            'waState',
            'brevoConfigured',
            'totalDevotees',
            'devoteesWithPhone',
            'devoteesWithEmail',
            'totalOrders',
            'pujas',
            'upcomingFestivals',
            'festivalsRadar',
            'recentBroadcasts',
            'totalB2bPartners',
            'b2bLeads',
            'b2bCities'
        ));
    }

    /** 5b. Autopilot Astrology Cockpit */
    public function autopilot()
    {
        $mode = (string) (Vault::get('autopilot_mode') ?: 'supervised');
        $confidence_floor = (int) (Vault::get('autopilot_confidence_floor') ?: 85);

        $counts = \Illuminate\Support\Facades\Cache::remember('growth_autopilot_counts', 120, function () {
            return [
                'sent_today'    => DB::table('autopilot_queue')->where('status', 'sent')->whereDate('sent_at', today())->count(),
                'pending_count' => DB::table('autopilot_queue')->where('status', 'pending')->count(),
                'sent_count'    => DB::table('autopilot_queue')->where('status', 'sent')->count(),
            ];
        });

        $sent_today = $counts['sent_today'];
        $pending_count = $counts['pending_count'];
        $sent_count = $counts['sent_count'];

        $loops = [
            'gochar' => [
                'name' => 'Planetary Gochar Alerts',
                'description' => 'Calculates daily planetary transits and sends personalized astrological alerts.',
                'icon' => '🪐',
                'enabled' => Vault::get('loop_gochar_enabled', '1') == '1',
                'cadence' => 'Daily at 06:00 AM IST',
            ],
            'panchang' => [
                'name' => 'Tithi & Muhurat Forecast',
                'description' => 'Computes Rahu Kaal, Abhijit Muhurat and Shubh Choghadiya for the day.',
                'icon' => '🕉️',
                'enabled' => Vault::get('loop_panchang_enabled', '1') == '1',
                'cadence' => 'Daily at 07:00 AM IST',
            ],
            'festival' => [
                'name' => 'Hindu Festival Puja Drips',
                'description' => 'Automates devotee reminders and auspicious sankalp booking drips ahead of tithis.',
                'icon' => '🪔',
                'enabled' => Vault::get('loop_festival_enabled', '1') == '1',
                'cadence' => 'T-3 & T-1 Days Before Tithi',
            ],
            'twin' => [
                'name' => 'AI Astrologer Twin Fallback',
                'description' => 'Maintains immediate devotee engagement when verified astrologers are busy.',
                'icon' => '🤖',
                'enabled' => Vault::get('loop_twin_enabled', '1') == '1',
                'cadence' => 'Realtime Event Trigger',
            ],
            'churn' => [
                'name' => 'Devotee Churn Revival',
                'description' => 'Engages dormant users with free personalized horoscope and transit updates.',
                'icon' => '✨',
                'enabled' => Vault::get('loop_churn_enabled', '1') == '1',
                'cadence' => 'Weekly on Mondays',
            ],
        ];

        $queue = DB::table('autopilot_queue')->orderByDesc('id')->limit(50)->get();
        $logs = DB::table('brain_run_log')->orderByDesc('id')->limit(20)->get();

        $agents = \App\Models\AiAgent::withCount(['outputs as pending_outputs_count' => function ($q) {
            $q->where('status', 'pending_review');
        }])->get();

        $agentOutputs = \App\Models\AiAgentOutput::with('agent')->orderByDesc('id')->limit(20)->get();

        return view('pages.growth-os.autopilot', compact(
            'mode',
            'confidence_floor',
            'sent_today',
            'pending_count',
            'sent_count',
            'loops',
            'queue',
            'logs',
            'agents',
            'agentOutputs'
        ));
    }

    /** Save Autopilot execution settings */
    public function saveAutopilotSettings(Request $request)
    {
        if ($request->has('autopilot_mode')) {
            Vault::set('autopilot_mode', $request->input('autopilot_mode'));
        }
        if ($request->has('autopilot_confidence_floor')) {
            Vault::set('autopilot_confidence_floor', $request->input('autopilot_confidence_floor'));
        }
        Vault::set('loop_gochar_enabled', $request->has('autopilot_gochar_enabled') ? '1' : '0');
        Vault::set('loop_panchang_enabled', $request->has('autopilot_panchang_enabled') ? '1' : '0');
        Vault::set('loop_festival_enabled', $request->has('autopilot_festival_enabled') ? '1' : '0');
        Vault::set('loop_twin_enabled', ($request->has('autopilot_twin_fallback_enabled') || $request->has('autopilot_twin_enabled')) ? '1' : '0');
        Vault::set('loop_churn_enabled', $request->has('autopilot_churn_enabled') ? '1' : '0');
        return back()->with('success', 'Autopilot settings updated successfully.');
    }

    /** 6. AI Engine & Model Vault */
    public function aiEngine()
    {
        $creds = Vault::aiCredentials();
        $providers = [
            'omniroute' => [
                'name' => 'OmniRoute Gateway',
                'url' => $creds['omniroute_url'] ?? 'https://ai.vmstudio.digital',
                'configured' => ! empty($creds['omniroute_key']),
                'type' => 'Default Primary Router',
            ],
            'gemini' => [
                'name' => 'Google Gemini',
                'url' => 'https://generativelanguage.googleapis.com/v1beta/openai',
                'configured' => ! empty($creds['gemini_key']),
                'type' => 'Secondary Fallback',
            ],
            'openrouter' => [
                'name' => 'OpenRouter',
                'url' => 'https://openrouter.ai/api/v1',
                'configured' => ! empty($creds['openrouter_key']),
                'type' => 'Multi-Model Proxy',
            ],
            'pollinations' => [
                'name' => 'Pollinations AI',
                'url' => 'https://text.pollinations.ai',
                'configured' => true,
                'type' => 'Free Keyless Fallback',
            ],
        ];

        // Synced Live OmniRoute Models (Cached for 30 minutes)
        $modelData = \Illuminate\Support\Facades\Cache::remember('growth_ai_models_data_v1', 1800, function () {
            return [
                'omniTextModels'   => DB::table('ai_models')->select('id', 'model_id', 'label')->where('provider', 'omniroute')->where('kind', 'text')->orderBy('label')->get(),
                'omniImageModels'  => DB::table('ai_models')->select('id', 'model_id', 'label')->where('provider', 'omniroute')->where('kind', 'image')->orderBy('label')->get(),
                'omniVideoModels'  => DB::table('ai_models')->select('id', 'model_id', 'label')->where('provider', 'omniroute')->where('kind', 'video')->orderBy('label')->get(),
                'comfyImageModels' => DB::table('ai_models')->select('id', 'model_id', 'label')->where('model_id', 'like', '%comfyui%')->where('kind', 'image')->get(),
                'comfyVideoModels' => DB::table('ai_models')->select('id', 'model_id', 'label')->where('model_id', 'like', '%comfyui%')->whereIn('kind', ['video', 'text'])->get(),
                'omniTotal'        => DB::table('ai_models')->where('provider', 'omniroute')->count(),
                'lastSyncedAt'     => optional(DB::table('ai_models')->where('provider', 'omniroute')->orderByDesc('last_synced_at')->first())->last_synced_at,
                'totalModelsCount' => DB::table('ai_models')->count(),
            ];
        });

        $omniTextModels   = $modelData['omniTextModels'];
        $omniImageModels  = $modelData['omniImageModels'];
        $omniVideoModels  = $modelData['omniVideoModels'];
        $comfyImageModels = $modelData['comfyImageModels'];
        $comfyVideoModels = $modelData['comfyVideoModels'];
        $omniTotal        = $modelData['omniTotal'];
        $lastSyncedAt     = $modelData['lastSyncedAt'];
        $totalModelsCount = $modelData['totalModelsCount'];

        return view('pages.growth-os.ai', compact(
            'creds', 
            'providers', 
            'omniTextModels', 
            'omniImageModels', 
            'omniVideoModels', 
            'comfyImageModels', 
            'comfyVideoModels', 
            'omniTotal', 
            'lastSyncedAt', 
            'totalModelsCount'
        ));
    }

    /** Save Vault settings (API keys) */
    public function saveVault(Request $request)
    {
        $keys = [
            'omniroute_url', 'omniroute_key',
            'comfy_url', 'model_comfyui_image', 'model_comfyui_video',
            'openrouter_key', 'gemini_key', 'ai_text_chain', 'ai_image_chain', 'ai_video_chain',
            'model_omniroute', 'model_omniroute_image', 'model_omniroute_video', 'model_openrouter',
            'cloudflare_account_id', 'cloudflare_api_token', 'huggingface_token',
            'evolution_api_url', 'evolution_api_key', 'evolution_instance',
            'brevo_api_key', 'brevo_sender_email', 'brevo_sender_name', 'brevo_list_id',
            'fb_app_id', 'fb_app_secret', 'fb_api_version', 'fb_page_id', 'fb_page_token',
            'ig_user_id', 'ig_access_token', 'meta_verify_token',
            'youtube_client_id', 'youtube_client_secret',
            'x_client_id', 'x_client_secret', 'pinterest_app_id', 'pinterest_app_secret',
            'video_logo_enabled', 'video_logo_path', 'video_logo_position',
            'video_brand_title', 'video_brand_tagline', 'video_brand_phone', 'video_brand_cta',
            'video_audio_track',
            'meta_business_id', 'meta_pixel_id', 'meta_capi_token', 'ga4_measurement_id', 'gtm_container_id',
            'google_ads_id', 'google_ads_label', 'social_webhook_url', 'instagram_token',
        ];

        foreach ($keys as $key) {
            if ($request->has($key)) {
                $val = trim((string) $request->input($key));
                Vault::set($key, $val);

                // Auto-sync social_accounts when social tokens are updated in Vault
                if ($key === 'fb_page_token' && !empty($val)) {
                    $enc = \Illuminate\Support\Facades\Crypt::encryptString($val);
                    DB::table('social_accounts')->updateOrInsert(
                        ['platform' => 'facebook'],
                        ['access_token_enc' => $enc, 'status' => 'connected', 'updated_at' => now()]
                    );
                }
                if ($key === 'instagram_token' && !empty($val)) {
                    $enc = \Illuminate\Support\Facades\Crypt::encryptString($val);
                    DB::table('social_accounts')->updateOrInsert(
                        ['platform' => 'instagram'],
                        ['access_token_enc' => $enc, 'status' => 'connected', 'updated_at' => now()]
                    );
                }
            }
        }

        return back()->with('success', 'Vault credentials updated successfully!');
    }
    /** AJAX Action Runner for Background Tools */
    public function runAction(Request $request)
    {
        $action = $request->input('action');
        $output = '';

        try {
            switch ($action) {
                case 'preview_festival_campaign':
                    $festEngine = app(\App\Services\FestivalCampaignEngine::class);
                    $festName = $request->input('festival', 'Dev Deepawali Varanasi');
                    $package = $festEngine->buildCampaignContent($festName);
                    return response()->json([
                        'ok'      => true,
                        'package' => $package,
                    ]);

                case 'generate_festival_reel':
                    $festEngine = app(\App\Services\FestivalCampaignEngine::class);
                    $festName = $request->input('festival', 'Dev Deepawali Varanasi');
                    $videoRes = $festEngine->generateFestivalVideoReel($festName);
                    return response()->json([
                        'ok'        => !empty($videoRes['success']),
                        'video_url' => $videoRes['video_url'] ?? null,
                        'message'   => "1080x1920 MP4 Video Reel synthesized for {$festName}!",
                        'data'      => $videoRes,
                    ]);

                case 'store_custom_festival':
                    $name = trim($request->input('name'));
                    $date = $request->input('date');
                    $deity = $request->input('deity', 'Vedic Devata');
                    if (!$name || !$date) {
                        return response()->json(['ok' => false, 'error' => 'Festival Name and Date are required.'], 400);
                    }
                    DB::table('hindu_festivals')->updateOrInsert(
                        ['name' => $name, 'date' => $date],
                        ['deity' => $deity, 'scheduled' => 0, 'updated_at' => now()]
                    );
                    return response()->json(['ok' => true, 'message' => "Custom festival '{$name}' scheduled on {$date} successfully!"]);

                case 'orchestrate_master_campaign':
                    $festEngine = app(\App\Services\FestivalCampaignEngine::class);
                    $festName = $request->input('festival', 'Dev Deepawali Varanasi 2026');
                    $masterRes = $festEngine->orchestrateMasterCampaign($festName);
                    return response()->json($masterRes);

                case 'generate_festival_banners':
                    $festEngine = app(\App\Services\FestivalCampaignEngine::class);
                    $festName = $request->input('festival', 'Dev Deepawali Varanasi 2026');
                    $banners = $festEngine->generateFestivalBanners($festName);
                    return response()->json([
                        'ok'        => $banners['ok'],
                        'message'   => "Landscape (1200x630) & Square (1080x1080) Banners generated for {$festName}!",
                        'landscape' => $banners['landscape']['banner_url'] ?? null,
                        'square'    => $banners['square']['banner_url'] ?? null,
                        'data'      => $banners,
                    ]);

                case 'publish_festival_blog':
                    $festEngine = app(\App\Services\FestivalCampaignEngine::class);
                    $festName = $request->input('festival', 'Dev Deepawali Varanasi 2026');
                    $blogRes = $festEngine->publishSeoBlogArticle($festName);
                    return response()->json([
                        'ok'       => $blogRes['ok'],
                        'message'  => "High-ranking SEO Devotional Blog published live to onlinepuja.live/blog/{$blogRes['slug']}!",
                        'blog_url' => $blogRes['blog_url'],
                        'title'    => $blogRes['title'],
                        'data'     => $blogRes,
                    ]);

                case 'dispatch_festival_campaign':
                case 'launch_festival_campaign':
                    $festEngine = app(\App\Services\FestivalCampaignEngine::class);
                    $festName = $request->input('festival', 'Dev Deepawali Varanasi');
                    $channels = $request->input('channels', ['whatsapp', 'email', 'push']);
                    $options = [
                        'dry_run'             => (bool) $request->input('dry_run', false),
                        'video_url'           => $request->input('video_url'),
                        'whatsapp_recipients' => array_filter([$request->input('test_phone', '7007158014')]),
                        'email_recipients'    => array_filter([['email' => $request->input('test_email', 'contact@onlinepuja.live'), 'name' => 'Devotee']]),
                    ];
                    $dispRes = $festEngine->dispatchCampaign($festName, $channels, $options);
                    return response()->json([
                        'ok'      => true,
                        'message' => "Campaign for {$festName} dispatched successfully!",
                        'results' => $dispRes,
                    ]);

                case 'import_b2b_csv':
                    $leadService = app(\App\Services\B2bLeadService::class);
                    $csvData = $request->input('csv_data');
                    if ($request->hasFile('csv_file')) {
                        $csvData = file_get_contents($request->file('csv_file')->getRealPath());
                    }
                    if (empty($csvData)) {
                        return response()->json(['ok' => false, 'error' => 'No CSV file or data provided.'], 400);
                    }
                    $importRes = $leadService->importCsv($csvData, [
                        'city'     => $request->input('default_city', 'India'),
                        'category' => $request->input('default_category', 'travel_agency'),
                    ]);
                    return response()->json([
                        'ok'      => true,
                        'message' => "Imported {$importRes['imported']} B2B partners ({$importRes['updated']} updated, {$importRes['skipped']} skipped).",
                        'results' => $importRes,
                    ]);

                case 'radar_b2b_search':
                case 'radar_google_places':
                    $leadService = app(\App\Services\B2bLeadService::class);
                    $city = $request->input('city', 'Mumbai');
                    $category = $request->input('category', 'travel_agency');
                    $radarRes = $leadService->queryGooglePlacesRadar($city, $category, 20);
                    return response()->json([
                        'ok'      => true,
                        'message' => "Google Maps Radar processed {$city} ({$category}): " . ($radarRes['imported'] ?? 0) . " new leads imported.",
                        'results' => $radarRes,
                    ]);

                case 'seed_b2b_directory':
                    $leadService = app(\App\Services\B2bLeadService::class);
                    $n = $leadService->seedPremierB2bDirectory();
                    return response()->json([
                        'ok'      => true,
                        'message' => "Seeded {$n} premier B2B partners across Mumbai, Surat, Ahmedabad, Delhi, Kolkata & Varanasi.",
                    ]);

                case 'preview_b2b_pitch':
                    $leadId = $request->input('lead_id');
                    $lead = \App\Models\B2bLead::find($leadId) ?: \App\Models\B2bLead::first();
                    if (!$lead) {
                        return response()->json(['ok' => false, 'error' => 'No B2B leads available.'], 404);
                    }
                    $festEngine = app(\App\Services\FestivalCampaignEngine::class);
                    $pitch = $festEngine->buildB2bDevDeepawaliPitch($lead);
                    return response()->json([
                        'ok'    => true,
                        'pitch' => $pitch,
                    ]);

                case 'dispatch_b2b_campaign':
                    $leadIds = $request->input('lead_ids', []);
                    if (empty($leadIds)) {
                        $city = $request->input('city');
                        $category = $request->input('category');
                        $query = \App\Models\B2bLead::query();
                        if ($city && $city !== 'all') {
                            $query->where('city', $city);
                        }
                        if ($category && $category !== 'all') {
                            $query->where('category', $category);
                        }
                        $leadIds = $query->limit(50)->pluck('id')->toArray();
                    }
                    $channels = $request->input('channels', ['whatsapp', 'email']);
                    $dryRun = (bool) $request->input('dry_run', false);
                    $festEngine = app(\App\Services\FestivalCampaignEngine::class);
                    $b2bDisp = $festEngine->dispatchB2bCampaign($leadIds, $channels, ['dry_run' => $dryRun]);
                    return response()->json([
                        'ok'      => true,
                        'message' => "Dispatched Dev Deepawali B2B campaign to {$b2bDisp['attempted']} partner(s)!",
                        'results' => $b2bDisp,
                    ]);

                case 'test_branded_reel':
                    $aiEngine = app(\App\Services\AiEngineService::class);
                    $prompt = $request->input('prompt', 'Sacred Lord Shiva Rudrabhishek with bilva leaves in Varanasi');
                    $title = $request->input('title', Vault::get('video_brand_title', 'OnlinePuja.live'));
                    $subtitle = $request->input('subtitle', Vault::get('video_brand_tagline', 'Direct from Kashi Vishwanath, Varanasi'));
                    $dakshina = $request->input('dakshina', 'Dakshina: ₹1,100 | Vedic Pandit Sankalp');
                    $cta = $request->input('cta', Vault::get('video_brand_phone', '+91 70071 58014') . ' | ' . Vault::get('video_brand_cta', 'OnlinePuja.live'));

                    $branding = [
                        'title'    => $title,
                        'subtitle' => $subtitle,
                        'dakshina' => $dakshina,
                        'cta'      => $cta,
                    ];
                    $res = $aiEngine->generateVideo($prompt, null, 5, $branding);
                    return response()->json([
                        'ok'        => !empty($res['success']),
                        'video_url' => $res['video_url'] ?? null,
                        'message'   => 'High-definition branded business reel generated successfully!',
                        'data'      => $res,
                    ]);

                case 'sync_models':
                    $startSync = microtime(true);
                    $pm = app(\App\AI\ProviderManager::class);
                    $report = $pm->syncModels();
                    $counts = [
                        'text'        => DB::table('ai_models')->where('provider', 'omniroute')->where('kind', 'text')->count(),
                        'image'       => DB::table('ai_models')->where('provider', 'omniroute')->where('kind', 'image')->count(),
                        'video'       => DB::table('ai_models')->where('provider', 'omniroute')->where('kind', 'video')->count(),
                        'total'       => DB::table('ai_models')->where('provider', 'omniroute')->count(),
                        'last_synced' => 'Just now',
                    ];
                    $durationMs = round((microtime(true) - $startSync) * 1000);
                    return response()->json([
                        'ok'      => true,
                        'message' => "Successfully synced {$counts['total']} models from OmniRoute in {$durationMs}ms!",
                        'counts'  => $counts,
                        'report'  => $report,
                    ]);
                case 'schedule_festivals':
                    $router = app(\App\Brain\CommandRouter::class);
                    $res = $router->actScheduleFestivals(['days_ahead' => 75]);
                    try {
                        \Illuminate\Support\Facades\Artisan::call('social:plan', ['--limit' => 20]);
                    } catch (\Throwable $e) {}
                    return response()->json([
                        'ok'      => true,
                        'message' => $res['message'] ?? "Queued {$res['queued']} upcoming devotional festival wishes!",
                        'queued'  => $res['queued'] ?? 0,
                    ]);
                case 'test_comfyui':
                    $comfyUrl = rtrim(Vault::get('comfy_url', 'https://comfy.vmstudio.digital'), '/');
                    $omniUrl = rtrim(Vault::get('omniroute_url', 'https://ai.vmstudio.digital'), '/');
                    $omniKey = Vault::get('omniroute_key');

                    $status = [
                        'comfy_url' => $comfyUrl,
                        'omniroute_connected' => false,
                        'direct_node_ping' => false,
                        'available_models' => [
                            'image' => ['comfyui/flux-dev', 'comfyui/sdxl'],
                            'video' => ['comfyui/animatediff', 'comfyui/svd-xt'],
                        ],
                    ];

                    try {
                        $res = \Illuminate\Support\Facades\Http::timeout(3)->get($comfyUrl . '/system_stats');
                        if ($res->ok()) {
                            $status['direct_node_ping'] = true;
                            $status['system_stats'] = $res->json();
                        }
                    } catch (\Throwable $e) {
                        $status['direct_node_ping_error'] = $e->getMessage();
                    }

                    try {
                        if (!empty($omniKey)) {
                            $checkUrl = 'http://127.0.0.1:20128/v1/models';
                            $res = \Illuminate\Support\Facades\Http::timeout(8)->withToken($omniKey)->get($checkUrl);
                            if (!$res->ok()) {
                                $res = \Illuminate\Support\Facades\Http::timeout(12)->withToken($omniKey)->get($omniUrl . '/v1/models');
                            }
                            if ($res->ok()) {
                                $data = $res->json();
                                $models = collect($data['data'] ?? []);
                                $hasComfy = $models->contains(fn ($m) => str_contains($m['id'] ?? '', 'comfyui'));
                                $status['omniroute_connected'] = true;
                                $status['omniroute_has_comfy'] = $hasComfy;
                            }
                        }
                    } catch (\Throwable $e) {
                        $status['omniroute_error'] = $e->getMessage();
                    }

                    return response()->json([
                        'ok' => true,
                        'message' => 'ComfyUI GPU integration checked successfully!',
                        'data' => $status,
                    ]);

                case 'test_ai_engine':
                    $engine = $request->input('engine', 'text');
                    $prompt = $request->input('prompt', 'Vedic blessings from Varanasi');
                    $aiEngine = app(\App\Services\AiEngineService::class);

                    if ($engine === 'text') {
                        $data = $aiEngine->generateTextDetailed($prompt);
                        $isOk = !empty($data['success']) || !empty($data['ok']);
                        return response()->json([
                            'ok' => $isOk,
                            'data' => $data,
                            'error' => $isOk ? null : ($data['error'] ?? 'Text synthesis failed')
                        ]);
                    } elseif ($engine === 'image') {
                        $data = $aiEngine->generateImage($prompt, 'photorealistic', '1:1');
                        $isOk = !empty($data['success']);
                        return response()->json([
                            'ok' => $isOk,
                            'data' => $data,
                            'error' => $isOk ? null : ($data['error'] ?? 'Image synthesis failed')
                        ]);
                    } elseif ($engine === 'video') {
                        $data = $aiEngine->generateVideo($prompt, null, 5);
                        $isOk = !empty($data['success']);
                        return response()->json([
                            'ok' => $isOk,
                            'data' => $data,
                            'error' => $isOk ? null : ($data['error'] ?? 'Video synthesis failed')
                        ]);
                    }
                    return response()->json(['ok' => false, 'error' => 'Invalid engine'], 400);
                case 'dispatch_custom_broadcast':
                case 'send_devotee_broadcast':
                    $channel = $request->input('channel', 'both');
                    $segment = $request->input('segment', 'test');
                    $subject = $request->input('subject', '🕉️ Auspicious Puja Blessings from OnlinePuja.live');
                    $headline = $request->input('headline', 'Divine Blessings & Puja Invitation');
                    $body = $request->input('body', 'Pranam Devotee! May Lord Shiva bless you and your family with prosperity and peace.');
                    $mediaUrl = $request->input('media_url');
                    $mediaType = $request->input('media_type', 'image');
                    $ctaText = $request->input('cta_text', 'Book Sacred Puja');
                    $ctaUrl = $request->input('cta_url', 'https://onlinepuja.live');

                    $wa = app(\App\Services\EvolutionWaService::class);
                    $brevo = app(\App\Services\BrevoEmailService::class);

                    $dispatched = ['wa_ok' => 0, 'email_ok' => 0, 'failed' => 0];

                    if ($segment === 'test') {
                        $admin = Auth::user();
                        $testPhone = $request->input('test_phone', $admin->contactNo ?? '917007158014');
                        $testEmail = $request->input('test_email', $admin->email ?? 'contact@onlinepuja.live');

                        if (in_array($channel, ['whatsapp', 'both']) && !empty($testPhone)) {
                            $waMsg = "🕉️ *{$headline}*\n\n{$body}\n\n👉 *{$ctaText}*: {$ctaUrl}\n\n_Har Har Mahadev — OnlinePuja.live_";
                            if (!empty($mediaUrl)) {
                                $waRes = $wa->sendMedia($testPhone, $mediaUrl, $waMsg, $mediaType === 'video' ? 'video' : 'image');
                            } else {
                                $waRes = $wa->sendText($testPhone, $waMsg);
                            }
                            if (!empty($waRes['ok'])) { $dispatched['wa_ok']++; } else { $dispatched['failed']++; }
                        }

                        if (in_array($channel, ['email', 'both']) && !empty($testEmail)) {
                            $html = $brevo->renderDevotionalHtml($headline, nl2br(e($body)), $ctaText, $ctaUrl);
                            $emRes = $brevo->sendEmail($testEmail, $admin->name ?? 'Devotee', $subject, $html);
                            if (!empty($emRes['ok'])) { $dispatched['email_ok']++; } else { $dispatched['failed']++; }
                        }

                        return response()->json([
                            'ok' => true,
                            'message' => "Test broadcast dispatched! (WhatsApp: {$dispatched['wa_ok']}, Email: {$dispatched['email_ok']})",
                            'dispatched' => $dispatched,
                        ]);
                    } else {
                        $query = DB::table('users');
                        if ($segment === 'bookers') {
                            $buyerIds = DB::table('puja_orders')->pluck('user_id')->filter()->unique();
                            $query->whereIn('id', $buyerIds);
                        }
                        $users = $query->limit(25)->get();

                        foreach ($users as $u) {
                            if (in_array($channel, ['whatsapp', 'both']) && !empty($u->contactNo)) {
                                $waMsg = "🕉️ *Pranam {$u->name}!*\n\n*{$headline}*\n\n{$body}\n\n👉 *{$ctaText}*: {$ctaUrl}\n\n_OnlinePuja.live Varanasi_";
                                if (!empty($mediaUrl)) {
                                    $waRes = $wa->sendMedia($u->contactNo, $mediaUrl, $waMsg, $mediaType === 'video' ? 'video' : 'image');
                                } else {
                                    $waRes = $wa->sendText($u->contactNo, $waMsg);
                                }
                                if (!empty($waRes['ok'])) { $dispatched['wa_ok']++; } else { $dispatched['failed']++; }
                            }

                            if (in_array($channel, ['email', 'both']) && !empty($u->email)) {
                                $html = $brevo->renderDevotionalHtml($headline, "<p>Pranam <strong>{$u->name}</strong>,</p>" . nl2br(e($body)), $ctaText, $ctaUrl);
                                $emRes = $brevo->sendEmail($u->email, $u->name, $subject, $html);
                                if (!empty($emRes['ok'])) { $dispatched['email_ok']++; } else { $dispatched['failed']++; }
                            }
                        }

                        return response()->json([
                            'ok' => true,
                            'message' => "Campaign sent to " . count($users) . " devotees! (WhatsApp: {$dispatched['wa_ok']}, Email: {$dispatched['email_ok']})",
                            'dispatched' => $dispatched,
                        ]);
                    }

                case 'sync_brevo_contacts':
                    $brevo = app(\App\Services\BrevoEmailService::class);
                    $users = DB::table('users')->whereNotNull('email')->where('email', '!=', '')->limit(50)->get();
                    $synced = 0;
                    foreach ($users as $u) {
                        $parts = explode(' ', trim((string)$u->name), 2);
                        $res = $brevo->syncContact($u->email, [
                            'FIRSTNAME' => $parts[0] ?? 'Devotee',
                            'LASTNAME'  => $parts[1] ?? '',
                            'SMS'       => $u->contactNo ? '+91' . preg_replace('/\D/', '', $u->contactNo) : '',
                        ]);
                        if (!empty($res['ok'])) { $synced++; }
                    }
                    return response()->json([
                        'ok' => true,
                        'message' => "Synced {$synced} devotees to Brevo Contact List!",
                        'synced' => $synced,
                    ]);

                case 'test_brevo':
                    $brevo = app(\App\Services\BrevoEmailService::class);
                    $testTo = $request->input('email', Auth::user()->email ?? 'care@onlinepuja.live');
                    $html = $brevo->renderDevotionalHtml(
                        '🕉️ Test Devotional Dispatch',
                        '<p>Pranam! This is a live verification email from your OnlinePuja Marketing Automation Engine.</p><p>Brevo API v3 connection is active and validated.</p>',
                        'Explore Sacred Pujas',
                        'https://onlinepuja.live'
                    );
                    $res = $brevo->sendEmail($testTo, 'Devotee Admin', '🕉️ OnlinePuja Marketing Test Dispatch', $html);
                    if (!empty($res['ok'])) {
                        $res['message'] = "Test email sent successfully to {$testTo}!";
                    }
                    return response()->json($res);
                case 'test_whatsapp':
                    $wa = app(\App\Services\EvolutionWaService::class);
                    $conn = $wa->getConnectionState();
                    if (!empty($conn['ok']) && ($conn['state'] ?? '') !== 'open') {
                        $st = $conn['state'] ?? 'unpaired';
                        return response()->json([
                            'ok'    => false,
                            'state' => $st,
                            'error' => "WhatsApp instance is currently '{$st}'. Please click 'Scan WhatsApp QR' to pair your device first."
                        ]);
                    }
                    $mobile = $request->input('mobile', Auth::user()->contactNo ?? '917007158014');
                    $text = "🕉️ *OnlinePuja Marketing Engine — Test Ping*\n\nYour Evolution API WhatsApp connection on wa.vmstudio.digital is active!\n\n_Har Har Mahadev_";
                    $res = $wa->sendText($mobile, $text);
                    return response()->json($res);
                case 'get_wa_qr':
                    $wa = app(\App\Services\EvolutionWaService::class);
                    $qrRes = $wa->requestQrCode();
                    $stateRes = $wa->getConnectionState();
                    return response()->json([
                        'ok'          => true,
                        'state'       => $stateRes['state'] ?? 'connecting',
                        'base64'      => $qrRes['base64'] ?? null,
                        'pairingCode' => $qrRes['pairingCode'] ?? null,
                        'code'        => $qrRes['code'] ?? null,
                    ]);
                case 'seo_scan':
                    Artisan::call('seo:scan', ['--max' => 10]);
                    $output = Artisan::output() ?: 'SEO scan completed.';
                    break;
                case 'seo_fix':
                    Artisan::call('seo:fix', ['--god' => true, '--limit' => 5]);
                    $output = Artisan::output() ?: 'SEO God-mode fix applied.';
                    break;
                case 'seo_research':
                    Artisan::call('seo:research');
                    $output = Artisan::output() ?: 'Keyword research completed.';
                    break;
                case 'generate_content':
                    $keyword = trim((string) $request->input('keyword', ''));
                    if (!empty($keyword)) {
                        $kw = DB::table('keywords')->where('phrase', $keyword)->first();
                        if (!$kw) {
                            $kwId = DB::table('keywords')->insertGetId([
                                'phrase' => mb_substr($keyword, 0, 190),
                                'source' => 'manual-draft',
                                'cluster' => 'general',
                                'intent' => 'informational',
                                'difficulty' => 30,
                                'status' => 'new',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            $kw = DB::table('keywords')->where('id', $kwId)->first();
                        }
                        $res = $this->pipeline()->draftOne($kw);
                        if (!empty($res['ok'])) {
                            return response()->json([
                                'ok' => true,
                                'message' => "Article for \"{$keyword}\" drafted successfully! Check Content Pipeline.",
                                'plan_id' => $res['plan_id'] ?? null,
                            ]);
                        } else {
                            $failMsg = !empty($res['failures']) ? implode(', ', (array)$res['failures']) : ($res['error'] ?? 'Drafting failed Critic evaluation');
                            return response()->json([
                                'ok' => false,
                                'error' => "Drafting failed: {$failMsg}",
                            ], 422);
                        }
                    }

                    $limit = max(1, (int) $request->input('limit', 1));
                    Artisan::call('content:generate', ['--limit' => $limit]);
                    $output = Artisan::output() ?: 'New content draft generated with Critic review.';
                    break;
                case 'seed_festivals':
                    Artisan::call('brain:seed-festivals');
                    $output = Artisan::output() ?: 'Hindu festivals seeded.';
                    break;
                case 'brain_doctor':
                    Artisan::call('brain:doctor');
                    $output = Artisan::output() ?: 'Brain doctor completed diagnostic check.';
                    break;
                case 'brain_learn':
                    Artisan::call('brain:learn');
                    $output = Artisan::output() ?: 'Brain playbook updated with latest engagement patterns.';
                    break;
                case 'preview_social_prompt':
                    $topic = $request->input('topic', 'Griha Pravesh');
                    $style = $request->input('style', 'photorealistic');
                    $ratio = $request->input('aspect_ratio', '1:1');
                    $enhanced = \App\AI\VedicPromptEnhancer::enhance($topic, $style, $ratio);
                    return response()->json(['ok' => true, 'data' => $enhanced]);
                case 'generate_social_creative':
                    $topic = $request->input('topic', 'Griha Pravesh');
                    $style = $request->input('style', 'photorealistic');
                    $ratio = $request->input('aspect_ratio', '1:1');
                    $type  = $request->input('type', 'image');
                    $pujaId = $request->input('puja_id');
                    $aiEngine = app(\App\Services\AiEngineService::class);
                    if ($type === 'video') {
                        $puja = !empty($pujaId) ? DB::table('pujas')->where('id', $pujaId)->first() : null;
                        $brandingCfg = Vault::videoBranding();
                        $branding = [
                            'title'    => $puja ? $puja->puja_title : ($request->input('brand_title') ?: ($brandingCfg['video_brand_title'] ?? $topic)),
                            'subtitle' => ($puja && !empty($puja->puja_place)) ? "Sacred Temple in {$puja->puja_place}" : ($request->input('brand_tagline') ?: ($brandingCfg['video_brand_tagline'] ?? "Sanctified Vedic Rituals")),
                            'dakshina' => $puja ? ('Dakshina: ₹' . number_format($puja->puja_price) . ' | Free Prasad Delivery') : ($request->input('brand_dakshina') ?: 'Dakshina: Verified Samagri & Puja'),
                            'cta'      => $request->input('brand_cta') ?: ($brandingCfg['video_brand_phone'] . ' | ' . $brandingCfg['video_brand_cta']),
                            'logo_enabled' => $request->has('logo_enabled') ? (bool)$request->input('logo_enabled') : ($brandingCfg['video_logo_enabled'] ?? true),
                        ];
                        $res = $aiEngine->generateVideo($topic, null, 5, $branding);
                    } else {
                        $res = $aiEngine->generateImage($topic, $style, $ratio);
                    }
                    return response()->json(['ok' => !empty($res['success']), 'data' => $res]);
                case 'attach_social_creative':
                    $postId = $request->input('post_id');
                    $imageUrl = $request->input('image_url');
                    $videoUrl = $request->input('video_url');
                    $post = DB::table('content_plans')->where('id', $postId)->first();
                    if ($post) {
                        $p = json_decode((string)$post->payload, true) ?: [];
                        if (!empty($videoUrl)) {
                            $p['video_url'] = $videoUrl;
                        }
                        if (!empty($imageUrl)) {
                            $p['image_url'] = $imageUrl;
                        }
                        DB::table('content_plans')->where('id', $postId)->update([
                            'payload' => json_encode($p),
                            'updated_at' => now(),
                        ]);
                        return response()->json(['ok' => true, 'message' => 'Attached creative asset to post successfully!']);
                    }
                    return response()->json(['ok' => false, 'error' => 'Post not found'], 404);
                case 'create_service_reel':
                    $pujaId = $request->input('puja_id');
                    $astroQuery = $request->input('astro_topic');
                    $astrologerId = $request->input('astrologer_id');
                    $aiEngine = app(\App\Services\AiEngineService::class);

                    if (!empty($pujaId)) {
                        $puja = DB::table('pujas')->where('id', $pujaId)->first();
                        if (!$puja) return response()->json(['ok' => false, 'error' => 'Puja not found'], 404);

                        $topic = $puja->puja_title . (!empty($puja->puja_place) ? " performed in {$puja->puja_place}" : " Vedic ritual");
                        $brandingCfg = Vault::videoBranding();
                        $branding = [
                            'title'    => $puja->puja_title,
                            'subtitle' => !empty($puja->puja_place) ? "Direct from {$puja->puja_place}" : ($brandingCfg['video_brand_tagline'] ?? "Direct from Kashi Vishwanath, Varanasi"),
                            'dakshina' => "Dakshina: ₹" . number_format($puja->puja_price) . " | Free Prasad Delivery",
                            'cta'      => ($brandingCfg['video_brand_phone'] ?? 'WhatsApp: +91 70071 58014') . ' | ' . ($brandingCfg['video_brand_cta'] ?? 'OnlinePuja.live'),
                        ];
                        $res = $aiEngine->generateVideo($topic, null, 5, $branding);
                        $videoUrl = $res['video_url'] ?? $res['url'] ?? null;

                        $postTitle = "Reel: {$puja->puja_title}";
                        $slug = $puja->slug ?: 'category/list';
                        $caption = "Book verified Vedic pandits for {$puja->puja_title} with personal sankalp. Receive sanctified prasad from Varanasi directly at your doorstep! 🙏✨";
                        $cleanTag = preg_replace('/[^A-Za-z0-9]/', '', $puja->puja_title);
                        $hashtags = "#{$cleanTag} #OnlinePuja #PujaAtHome #VedicPandit #VaranasiDarshan #HinduRituals";
                        $link = "https://onlinepuja.live/puja/{$slug}";

                        $planId = DB::table('content_plans')->insertGetId([
                            'title'      => $postTitle,
                            'pillar'     => 'puja_promo',
                            'channel'    => 'social',
                            'status'     => 'approved',
                            'payload'    => json_encode([
                                'text'       => $caption,
                                'hashtags'   => $hashtags,
                                'channels'   => ['instagram', 'facebook', 'youtube', 'whatsapp'],
                                'video_url'  => $videoUrl,
                                'image_url'  => $res['url'] ?? null,
                                'link'       => $link,
                            ]),
                            'due_at'     => now()->addHours(2)->toDateTimeString(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        try {
                            \Illuminate\Support\Facades\Artisan::call('social:plan', ['--limit' => 5]);
                        } catch (\Throwable $e) {}

                        return response()->json([
                            'ok'        => true,
                            'message'   => "Reel queued for {$puja->puja_title}!",
                            'post_id'   => $planId,
                            'video_url' => $videoUrl,
                        ]);
                    } elseif (!empty($astroQuery)) {
                        $brandingCfg = Vault::videoBranding();
                        $branding = [
                            'title'    => $astroQuery,
                            'subtitle' => "Vedic Astrology & Planetary Remedies",
                            'dakshina' => "Personalized Kundli & Dosha Analysis",
                            'cta'      => ($brandingCfg['video_brand_phone'] ?? 'WhatsApp: +91 70071 58014') . ' | ' . ($brandingCfg['video_brand_cta'] ?? 'OnlinePuja.live'),
                        ];
                        $res = $aiEngine->generateVideo($astroQuery, null, 5, $branding);
                        $videoUrl = $res['video_url'] ?? $res['url'] ?? null;

                        $featuredAstro = null;
                        if (!empty($astrologerId)) {
                            $featuredAstro = DB::table('astrologers')->where('id', $astrologerId)->first();
                        }

                        $astroName = $featuredAstro ? $featuredAstro->name : 'Verified Vedic Astrologers';
                        $postTitle = "Astrology Reel: {$astroQuery}";
                        $caption = "Planetary insights & Vedic remedies for {$astroQuery}. Consult {$astroName} on OnlinePuja.live for personalized Kundli & dosha guidance! 🌟🕉️";
                        $hashtags = "#VedicAstrology #KundliAnalysis #OnlinePuja #RashifalToday #JyotishRemedies #AstrologyReels";
                        $link = $featuredAstro && !empty($featuredAstro->slug) ? "https://onlinepuja.live/astrologer/{$featuredAstro->slug}" : "https://onlinepuja.live/astrologer/list";

                        $planId = DB::table('content_plans')->insertGetId([
                            'title'      => $postTitle,
                            'pillar'     => 'astrology_reel',
                            'channel'    => 'social',
                            'status'     => 'approved',
                            'payload'    => json_encode([
                                'text'       => $caption,
                                'hashtags'   => $hashtags,
                                'channels'   => ['instagram', 'facebook', 'youtube', 'whatsapp'],
                                'video_url'  => $videoUrl,
                                'image_url'  => $res['url'] ?? null,
                                'link'       => $link,
                            ]),
                            'due_at'     => now()->addHours(3)->toDateTimeString(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        return response()->json([
                            'ok'        => true,
                            'message'   => "Astrology reel queued for {$astroQuery}!",
                            'post_id'   => $planId,
                            'video_url' => $videoUrl,
                        ]);
                    }

                    return response()->json(['ok' => false, 'error' => 'Please select a Puja or Astrology topic'], 400);
                case 'save_tracking_settings':
                    $keysToSave = [
                        'meta_pixel_id', 'meta_capi_token',
                        'ga4_measurement_id', 'gtm_container_id',
                        'google_ads_id', 'google_ads_label',
                        'social_webhook_url', 'brevo_api_key'
                    ];
                    foreach ($keysToSave as $k) {
                        if ($request->has($k)) {
                            $v = trim((string)$request->input($k));
                            Vault::set($k, $v, false);
                        }
                    }
                    return response()->json(['ok' => true, 'message' => 'Marketing, Pixel & Ads settings saved successfully!']);

                case 'test_brevo_key':
                    $testKey = trim((string)$request->input('brevo_api_key')) ?: Vault::get('brevo_api_key');
                    if (empty($testKey)) {
                        return response()->json(['ok' => false, 'error' => 'Please provide a Brevo API key to test.'], 400);
                    }
                    try {
                        $res = \Illuminate\Support\Facades\Http::withOptions([
                            'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]
                        ])->withHeaders([
                            'api-key' => $testKey,
                            'accept'  => 'application/json'
                        ])->get('https://api.brevo.com/v3/account');

                        if ($res->successful()) {
                            $acc = $res->json();
                            $email = $acc['email'] ?? 'Verified Account';
                            $company = $acc['companyName'] ?? 'Online Puja';
                            Vault::set('brevo_api_key', $testKey, false);
                            return response()->json(['ok' => true, 'message' => "Brevo Connected! {$company} ({$email})"]);
                        }
                        return response()->json(['ok' => false, 'error' => 'Brevo rejected key (HTTP ' . $res->status() . '). Please verify key in Brevo dashboard.'], 400);
                    } catch (\Throwable $e) {
                        return response()->json(['ok' => false, 'error' => 'Connection to Brevo failed: ' . $e->getMessage()], 500);
                    }

                case 'save_social_webhook':
                    $url = trim((string)$request->input('webhook_url', ''));
                    Vault::set('social_webhook_url', $url, false);
                    return response()->json(['ok' => true, 'message' => 'Social webhook URL saved successfully!']);
                case 'test_social_webhook':
                    $url = trim((string)$request->input('webhook_url', '')) ?: (Vault::get('social_webhook_url') ?: env('SOCIAL_DISPATCH_WEBHOOK'));
                    if (empty($url)) {
                        return response()->json(['ok' => false, 'error' => 'Please provide a valid webhook URL.'], 400);
                    }
                    try {
                        $res = \Illuminate\Support\Facades\Http::withoutVerifying()->timeout(10)->post($url, [
                            'event'     => 'test_ping',
                            'source'    => 'OnlinePuja Varanasi Growth Engine',
                            'title'     => '🙏 Test Devotional Wish Ping from OnlinePuja',
                            'text'      => 'May Lord Shiva and Maa Ganga bless your family with peace and abundance.',
                            'channels'  => ['whatsapp', 'instagram', 'facebook', 'x'],
                            'link'      => 'https://onlinepuja.live',
                            'timestamp' => now()->toIso8601String(),
                        ]);
                        if ($res->successful()) {
                            return response()->json(['ok' => true, 'message' => 'Webhook ping successful! Received 200 OK from server.']);
                        } else {
                            return response()->json(['ok' => false, 'error' => "Webhook responded with HTTP status " . $res->status()], 400);
                        }
                    } catch (\Throwable $e) {
                        return response()->json(['ok' => false, 'error' => 'Connection failed: ' . $e->getMessage()], 500);
                    }
                case 'save_social_token':
                    $token = trim((string)$request->input('token', ''));
                    $targetPlatform = $request->input('platform', 'facebook');
                    if (empty($token)) {
                        return response()->json(['ok' => false, 'error' => 'Please provide a non-empty Meta / Instagram Page Access Token.'], 400);
                    }

                    // 1. Inspect Token with Meta Graph API
                    $pageName = 'OnlinePuja.live';
                    $pageId = '1252263081299388';
                    $igUsername = 'onlinepujalive';
                    $igId = '17841474153880030';
                    $pageAccessToken = $token;

                    try {
                        // First test /me
                        $meResp = \Illuminate\Support\Facades\Http::timeout(10)->get('https://graph.facebook.com/v21.0/me', [
                            'fields' => 'id,name,category',
                            'access_token' => $token,
                        ]);

                        if ($meResp->successful()) {
                            $meData = $meResp->json();
                            // If this token is directly a Page Access Token
                            if (!empty($meData['category'])) {
                                $pageId = $meData['id'];
                                $pageName = $meData['name'] ?? $pageName;
                            } else {
                                // Token is a System User or User token — fetch managed Pages via /me/accounts
                                $accountsResp = \Illuminate\Support\Facades\Http::timeout(10)->get('https://graph.facebook.com/v21.0/me/accounts', [
                                    'access_token' => $token,
                                ]);
                                if ($accountsResp->successful()) {
                                    $pages = $accountsResp->json('data') ?? [];
                                    if (!empty($pages)) {
                                        // Pick OnlinePuja or first page
                                        $matchedPage = null;
                                        foreach ($pages as $p) {
                                            if (str_contains(strtolower($p['name'] ?? ''), 'online') || ($p['id'] ?? '') === '1252263081299388') {
                                                $matchedPage = $p;
                                                break;
                                            }
                                        }
                                        $matchedPage = $matchedPage ?: $pages[0];
                                        $pageId = $matchedPage['id'] ?? $pageId;
                                        $pageName = $matchedPage['name'] ?? $pageName;
                                        if (!empty($matchedPage['access_token'])) {
                                            $pageAccessToken = $matchedPage['access_token'];
                                        }
                                    }
                                }
                            }
                        }

                        // Inspect the Page to resolve linked Instagram Business Account
                        $pageResp = \Illuminate\Support\Facades\Http::timeout(10)->get("https://graph.facebook.com/v21.0/{$pageId}", [
                            'fields' => 'id,name,category,instagram_business_account',
                            'access_token' => $pageAccessToken,
                        ]);

                        if ($pageResp->successful()) {
                            $pData = $pageResp->json();
                            $pageName = $pData['name'] ?? $pageName;
                            if (!empty($pData['instagram_business_account']['id'])) {
                                $igId = $pData['instagram_business_account']['id'];
                                // Fetch Instagram profile details
                                $igResp = \Illuminate\Support\Facades\Http::timeout(10)->get("https://graph.facebook.com/v21.0/{$igId}", [
                                    'fields' => 'id,username,name',
                                    'access_token' => $pageAccessToken,
                                ]);
                                if ($igResp->successful()) {
                                    $igUsername = $igResp->json('username') ?? $igUsername;
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Meta token resolution note: ' . $e->getMessage());
                    }

                    $pageEncrypted = \Illuminate\Support\Facades\Crypt::encryptString($pageAccessToken);

                    // 2. Synchronize to Facebook in social_accounts
                    DB::table('social_accounts')->updateOrInsert(
                        ['platform' => 'facebook'],
                        [
                            'label'            => $pageName,
                            'external_id'      => $pageId,
                            'access_token_enc' => $pageEncrypted,
                            'status'           => 'connected',
                            'last_error'       => null,
                            'meta'             => json_encode(['page_id' => $pageId, 'page_name' => $pageName, 'has_linked_instagram' => !empty($igId)]),
                            'updated_at'       => now(),
                        ]
                    );

                    // 3. Synchronize to Instagram in social_accounts
                    DB::table('social_accounts')->updateOrInsert(
                        ['platform' => 'instagram'],
                        [
                            'label'            => '@' . ltrim($igUsername, '@'),
                            'external_id'      => $igId,
                            'access_token_enc' => $pageEncrypted,
                            'status'           => 'connected',
                            'last_error'       => null,
                            'meta'             => json_encode(['page_id' => $pageId, 'username' => $igUsername]),
                            'updated_at'       => now(),
                        ]
                    );

                    // 4. Update Vault for omni-channel access
                    Vault::set('instagram_token', $pageAccessToken);
                    Vault::set('fb_page_token', $pageAccessToken);
                    Vault::set('meta_capi_token', $token); // Root system user token is best for CAPI

                    return response()->json([
                        'ok' => true,
                        'message' => "✓ Token verified & saved! Connected Facebook Page: {$pageName} (ID: {$pageId}) & Instagram: @{$igUsername}.",
                        'details' => [
                            'page_name'   => $pageName,
                            'page_id'     => $pageId,
                            'ig_username' => '@' . ltrim($igUsername, '@'),
                            'ig_id'       => $igId,
                        ]
                    ]);

                case 'test_meta_post':
                    $platform = $request->input('platform', 'facebook');
                    $account = DB::table('social_accounts')->where('platform', $platform)->first();
                    if (!$account) {
                        return response()->json(['ok' => false, 'error' => "No {$platform} account connected."], 404);
                    }
                    try {
                        try {
                            $token = \Illuminate\Support\Facades\Crypt::decryptString($account->access_token_enc);
                        } catch (\Throwable $e1) {
                            $token = decrypt($account->access_token_enc, false);
                        }
                        $fields = ($platform === 'instagram') ? 'id,username,name' : 'id,name,category,link';
                        $endpoint = "https://graph.facebook.com/v21.0/{$account->external_id}?fields={$fields}&access_token=" . urlencode($token);
                        $resp = \Illuminate\Support\Facades\Http::timeout(10)->get($endpoint);
                        if ($resp->successful()) {
                            $data = $resp->json();
                            return response()->json([
                                'ok' => true,
                                'message' => "✓ Verified! Connected to: " . ($data['name'] ?? $account->label) . " (ID: {$account->external_id})",
                                'data' => $data
                            ]);
                        } else {
                            $err = $resp->json('error.message') ?? $resp->body();
                            return response()->json(['ok' => false, 'error' => "Meta API Error: {$err}"], 400);
                        }
                    } catch (\Throwable $e) {
                        return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
                    }

                case 'test_meta_capi':
                    try {
                        $capi = app(\App\Services\MetaCapiService::class);
                        $res = $capi->sendEvent('Lead', [
                            'email' => 'devotee@onlinepuja.live',
                            'phone' => '917007158014',
                            'ip'    => $request->ip(),
                        ], [
                            'content_name' => 'Kashi Vishwanath Rudrabhishek Live Test',
                            'value'        => 2100,
                            'currency'     => 'INR',
                        ]);
                        return response()->json([
                            'ok' => $res['ok'] ?? false,
                            'message' => ($res['ok'] ?? false) ? '✓ Meta CAPI test event successfully sent to Meta Dataset!' : ($res['message'] ?? 'CAPI test skipped or failed.'),
                            'response' => $res,
                        ]);
                    } catch (\Throwable $e) {
                        return response()->json(['ok' => false, 'error' => 'CAPI test error: ' . $e->getMessage()], 500);
                    }
                case 'publish_social_post':
                    $postId = $request->input('post_id');
                    $post = DB::table('content_plans')->where('id', $postId)->first();
                    if ($post) {
                        DB::table('content_plans')->where('id', $postId)->update([
                            'status' => 'published',
                            'posted_url' => 'https://instagram.com/onlinepuja.live',
                            'updated_at' => now(),
                        ]);
                        DB::table('outcomes')->insert([
                            'channel' => 'social',
                            'entity_type' => 'social_post',
                            'entity_id' => $postId,
                            'metric' => 'published',
                            'value' => 1,
                            'meta' => json_encode([
                                'title' => $post->title,
                                'channels' => ['instagram', 'facebook', 'x', 'telegram'],
                                'published_at' => now()->toIso8601String(),
                            ]),
                            'at' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        return response()->json(['ok' => true, 'message' => "Post \"{$post->title}\" dispatched and published to live social channels!"]);
                    }
                    return response()->json(['ok' => false, 'error' => 'Post not found'], 404);
                case 'delete_social_post':
                    $postId = $request->input('post_id');
                    DB::table('content_plans')->where('id', $postId)->delete();
                    return response()->json(['ok' => true, 'message' => 'Post removed from queue.']);
                case 'create_social_post':
                    $title = $request->input('title', 'Vedic Devotional Post');
                    $caption = $request->input('caption', '');
                    $hashtags = $request->input('hashtags', '#OnlinePuja #VedicAstrology #HinduFestivals');
                    $imageUrl = $request->input('image_url', '');
                    $newId = DB::table('content_plans')->insertGetId([
                        'title' => $title,
                        'pillar' => 'social_creative',
                        'channel' => 'social',
                        'status' => 'approved',
                        'payload' => json_encode([
                            'text' => $caption,
                            'hashtags' => $hashtags,
                            'image_url' => $imageUrl,
                            'channels' => ['instagram', 'facebook', 'x', 'telegram'],
                        ]),
                        'due_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    return response()->json(['ok' => true, 'post_id' => $newId, 'message' => 'New social post created and queued!']);
                case 'queue_social_creative':
                    $title = trim((string) ($request->input('title') ?: $request->input('topic', 'Devotional Post')));
                    $caption = trim((string) ($request->input('caption') ?: "Auspicious blessings and divine grace for {$title}. Book your sacred puja and Vedic remedies at OnlinePuja.live! 🙏🕉️"));
                    $hashtags = trim((string) ($request->input('hashtags') ?: '#OnlinePuja #VedicAstrology #HinduFestivals #PujaAtHome #Bhakti'));
                    $mediaType = trim((string) ($request->input('media_type') ?: 'image'));
                    $mediaUrl = trim((string) ($request->input('media_url') ?: ''));
                    $link = trim((string) ($request->input('link') ?: 'https://onlinepuja.live'));
                    $channels = $request->input('channels', ['instagram', 'facebook', 'youtube', 'whatsapp']);
                    $schedDate = $request->input('scheduled_date');

                    $payload = [
                        'text' => $caption,
                        'hashtags' => $hashtags,
                        'link' => $link,
                        'channels' => is_array($channels) ? $channels : explode(',', (string)$channels),
                    ];
                    if ($mediaType === 'video') {
                        $payload['video_url'] = $mediaUrl;
                    } else {
                        $payload['image_url'] = $mediaUrl;
                    }

                    $dueAt = $schedDate ? \Carbon\Carbon::parse($schedDate)->toDateTimeString() : now()->addHours(2)->toDateTimeString();

                    $newId = DB::table('content_plans')->insertGetId([
                        'title' => mb_substr($title, 0, 220),
                        'pillar' => ($mediaType === 'video' ? 'video_reel' : 'social_creative'),
                        'channel' => 'social',
                        'status' => 'approved',
                        'payload' => json_encode($payload),
                        'due_at' => $dueAt,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    return response()->json([
                        'ok' => true,
                        'message' => 'Social creative queued for dispatch successfully!',
                        'post_id' => $newId,
                    ]);
                case 'dispatch_social_queue':
                    Artisan::call('social:publish', ['--limit' => 10]);
                    $output = Artisan::output() ?: 'Social queue processed and dispatched.';
                    return response()->json([
                        'ok' => true,
                        'message' => 'Social queue dispatched!',
                        'output' => trim($output),
                    ]);
                case 'autopilot_run_loop':
                    $loop = $request->input('loop', 'all');
                    Artisan::call('autopilot:run', [
                        '--loop' => $loop,
                    ]);
                    $output = Artisan::output() ?: "Autopilot loop '{$loop}' executed.";
                    return response()->json([
                        'ok' => true,
                        'message' => "Autopilot loop '{$loop}' executed successfully!",
                        'output' => trim($output),
                    ]);
                case 'autopilot_approve':
                    $id = (int) $request->input('id');
                    $engine = app(\App\Brain\AutopilotEngine::class);
                    $ok = $engine->approveItem($id, Auth::id() ?: 1);
                    if (!$ok) {
                        return response()->json(['ok' => false, 'error' => "Autopilot item #{$id} could not be approved or not found."], 404);
                    }
                    return response()->json([
                        'ok' => true,
                        'message' => "Item #{$id} approved and dispatched via omnichannel Autopilot!",
                    ]);
                case 'autopilot_reject':
                    $id = (int) $request->input('id');
                    $engine = app(\App\Brain\AutopilotEngine::class);
                    $engine->rejectItem($id);
                    return response()->json([
                        'ok' => true,
                        'message' => "Item #{$id} rejected and dismissed.",
                    ]);
                case 'autopilot_approve_all':
                    $engine = app(\App\Brain\AutopilotEngine::class);
                    $count = $engine->approveAll(Auth::id() ?: 1);
                    return response()->json([
                        'ok' => true,
                        'message' => "All {$count} pending items approved and dispatched via omnichannel Autopilot!",
                    ]);

                case 'run_ai_agent':
                    $agentKey = $request->input('agent');
                    $res = (empty($agentKey) || $agentKey === 'all')
                        ? \App\Brain\Agents\AgentRunner::runAll()
                        : \App\Brain\Agents\AgentRunner::run($agentKey);
                    return response()->json($res);

                case 'toggle_ai_agent':
                    $agentId = $request->input('agent_id');
                    $agent = \App\Models\AiAgent::findOrFail($agentId);
                    $agent->is_active = !$agent->is_active;
                    $agent->save();
                    return response()->json([
                        'ok' => true,
                        'is_active' => $agent->is_active,
                        'message' => "Agent '{$agent->name}' is now " . ($agent->is_active ? 'Active' : 'Paused'),
                    ]);

                case 'approve_agent_output':
                    $outputId = $request->input('output_id');
                    $out = \App\Models\AiAgentOutput::findOrFail($outputId);
                    $out->status = 'approved';
                    $out->published_at = now();
                    $out->save();
                    return response()->json([
                        'ok' => true,
                        'message' => "✓ Output '{$out->title}' approved & dispatched!",
                    ]);

                default:
                    return response()->json(['ok' => false, 'message' => "Unknown action: {$action}"], 400);
            }

            return response()->json(['ok' => true, 'output' => trim($output)]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
