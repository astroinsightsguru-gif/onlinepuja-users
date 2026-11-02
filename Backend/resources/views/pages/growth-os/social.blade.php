@extends('pages.growth-os.layout')

@section('growth_content')
@php
    $webhookUrl = $webhookUrl ?? \App\Brain\Vault::get('social_webhook_url') ?? env('SOCIAL_DISPATCH_WEBHOOK', '');
    $festivals = $festivals ?? (\Illuminate\Support\Facades\Schema::hasTable('hindu_festivals') ? \Illuminate\Support\Facades\DB::table('hindu_festivals')->orderBy('date', 'ASC')->get() : collect());
    $socialPosts = $socialPosts ?? \Illuminate\Support\Facades\DB::table('content_plans')->where('channel', 'social')->orderByDesc('id')->get();
    $pujas = $pujas ?? (\Illuminate\Support\Facades\Schema::hasTable('pujas') ? \Illuminate\Support\Facades\DB::table('pujas')->select('id', 'puja_title', 'puja_price', 'puja_place', 'slug')->where('puja_status', 1)->orderBy('id', 'DESC')->limit(100)->get() : collect());
    $astrologers = $astrologers ?? (\Illuminate\Support\Facades\Schema::hasTable('astrologers') ? \Illuminate\Support\Facades\DB::table('astrologers')->select('id', 'name', 'primarySkill')->where('isDelete', 0)->limit(30)->get() : collect());
@endphp
<div class="space-y-5">

    {{-- TOP: Vibrant High-Signal Metric Strip --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Metric 1: Webhook Status --}}
        <div class="ai-stat-card ai-stat-card-blue">
            <div class="flex items-center gap-3">
                <div class="ai-icon-bubble ai-icon-bubble-blue">📡</div>
                <div>
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Dispatch Webhook</span>
                    <div class="text-sm font-black text-slate-800 mt-0.5 flex items-center gap-1.5">
                        @if(!empty($webhookUrl))
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Connected (Active)
                        @else
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span> Setup Required
                        @endif
                    </div>
                </div>
            </div>
            <button type="button" id="toggle-webhook-config" class="ai-btn-secondary" style="font-size: 11px; padding: 5px 10px;">
                Configure
            </button>
        </div>

        {{-- Metric 2: Hindu Festivals --}}
        <div class="ai-stat-card ai-stat-card-gold">
            <div class="flex items-center gap-3">
                <div class="ai-icon-bubble ai-icon-bubble-gold">🪔</div>
                <div>
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Seeded Festivals</span>
                    <div class="text-base font-black text-amber-900 mt-0.5 font-mono">
                        {{ $festivals->count() }} <span class="text-[11px] font-semibold text-slate-500">Events</span>
                    </div>
                </div>
            </div>
            <span class="ai-pill ai-pill-amber">2026–27</span>
        </div>

        {{-- Metric 3: Queued Social Posts --}}
        <div class="ai-stat-card ai-stat-card-purple">
            <div class="flex items-center gap-3">
                <div class="ai-icon-bubble ai-icon-bubble-purple">🚀</div>
                <div>
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Post Queue</span>
                    <div class="text-base font-black text-purple-900 mt-0.5 font-mono">
                        {{ $socialPosts->count() }} <span class="text-[11px] font-semibold text-slate-500">Ready</span>
                    </div>
                </div>
            </div>
            <span class="ai-pill" style="background: #ede9fe; color: #6d28d9; border: 1px solid #ddd6fe;">Approved</span>
        </div>

        {{-- Metric 4: Channels & Schedule --}}
        <div class="ai-stat-card ai-stat-card-emerald">
            <div class="flex items-center gap-3">
                <div class="ai-icon-bubble ai-icon-bubble-emerald">⏰</div>
                <div>
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Morning Schedule</span>
                    <div class="text-base font-black text-emerald-900 mt-0.5 font-mono">
                        06:30 AM <span class="text-[11px] font-semibold text-slate-500">IST Daily</span>
                    </div>
                </div>
            </div>
            <span class="ai-pill ai-pill-emerald">Automated</span>
        </div>
    </div>

    {{-- Collapsible Webhook Dispatch Connector --}}
    <div id="webhook-config-drawer" class="{{ empty($webhookUrl) ? '' : 'hidden' }} bg-white p-4 rounded-xl border border-slate-200/80 shadow-2xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-100">
            <div>
                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                    <i data-lucide="radio" class="w-3.5 h-3.5 text-rose-500"></i> Social Media Dispatch Webhook URL
                </h4>
                <p class="text-xs text-slate-500 mt-0.5">Dispatches daily festival posts & reels to your Make.com, Zapier, Publer, or Meta Graph API webhook.</p>
            </div>
            <span class="text-[11px] text-slate-400 font-mono">Payload: id, title, text, hashtags, channels, media_type, image_url, video_url, link</span>
        </div>
        <div class="mt-3 flex flex-col sm:flex-row gap-2">
            <input type="url" id="social-webhook-input" value="{{ $webhookUrl }}" placeholder="https://hook.eu1.make.com/... or Zapier / Meta webhook URL" class="w-full text-xs rounded-xl border-slate-200 px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-slate-900/10 font-mono text-slate-800">
            <button type="button" id="btn-save-social-webhook" class="px-4 py-2.5 rounded-xl bg-slate-900 text-white font-semibold text-xs hover:bg-slate-800 whitespace-nowrap transition">Save Webhook</button>
            <button type="button" id="btn-test-social-webhook" class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 border border-slate-200 font-semibold text-xs hover:bg-slate-200 whitespace-nowrap transition">Test Ping</button>
        </div>
    </div>

    {{-- SECTION SWITCHER TABS: Declutters the screen by organizing into focused workflows --}}
    <div class="flex items-center justify-between border-b border-slate-200/80 pb-3">
        <div class="flex items-center gap-2">
            <button type="button" id="tab-btn-schedule" class="section-tab-btn active px-3.5 py-2 rounded-lg text-xs font-bold transition flex items-center gap-2 bg-slate-900 text-white shadow-2xs">
                <i data-lucide="calendar" class="w-3.5 h-3.5 text-amber-400"></i> Festival Calendar & Queue
            </button>
            <button type="button" id="tab-btn-studio" class="section-tab-btn px-3.5 py-2 rounded-lg text-xs font-bold transition flex items-center gap-2 bg-white text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200/80">
                <i data-lucide="sparkles" class="w-3.5 h-3.5 text-slate-400"></i> OmniRoute AI Creative Studio (Reels & Images)
            </button>
            <button type="button" id="tab-btn-automation" class="section-tab-btn px-3.5 py-2 rounded-lg text-xs font-bold transition flex items-center gap-2 bg-white text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200/80">
                <i data-lucide="send" class="w-3.5 h-3.5 text-slate-400"></i> Marketing Channels (WhatsApp · Brevo · Push)
            </button>
        </div>
        <div class="flex items-center gap-2">
            <button id="btn-schedule-festivals" class="btn btn-sm text-xs py-1.5 px-3 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-lg shadow-2xs border-0 flex items-center gap-1.5">
                <i data-lucide="wand" class="w-3.5 h-3.5 text-amber-400"></i> Auto-Schedule Wishes
            </button>
        </div>
    </div>

    {{-- TAB PANE 1: Festival Calendar & Queued Posts (Default View) --}}
    <div id="pane-schedule" class="section-pane">
        <div class="grid grid-cols-12 gap-5">
            {{-- Left: Hindu Festival Calendar --}}
            <div class="col-span-12 lg:col-span-7">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/70 shadow-2xs">
                    <div class="flex items-center justify-between pb-3.5 border-b border-slate-100">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                <i data-lucide="calendar" class="w-4 h-4 text-amber-500"></i> Hindu Festival Calendar (2026–27)
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Seeded Hindu festival dates for automated puja promotions & devotional wishes.</p>
                        </div>
                        <button id="btn-seed-festivals" class="btn btn-sm btn-outline-secondary text-xs py-1.5 px-2.5 bg-white hover:bg-slate-50 rounded-lg border-slate-200">
                            <i data-lucide="refresh-cw" class="w-3 h-3 mr-1"></i> Refresh
                        </button>
                    </div>

                    {{-- Festivals List --}}
                    <div class="space-y-2.5 mt-3.5 max-h-[580px] overflow-y-auto pr-1">
                        @forelse($festivals as $fest)
                            <div class="p-3 rounded-xl border {{ $fest->scheduled ? 'bg-emerald-50/40 border-emerald-200/80' : 'bg-slate-50/60 border-slate-200/80' }} flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 text-xs transition hover:border-amber-300">
                                <div class="flex items-start gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-white border border-slate-200/80 flex flex-col items-center justify-center flex-shrink-0 text-center shadow-2xs">
                                        <span class="text-[9px] font-bold uppercase text-rose-600">{{ \Carbon\Carbon::parse($fest->date)->format('M') }}</span>
                                        <span class="text-sm font-extrabold text-slate-800 leading-none">{{ \Carbon\Carbon::parse($fest->date)->format('d') }}</span>
                                    </div>
                                    <div>
                                        <div class="font-bold text-xs text-slate-800 flex items-center gap-2">
                                            <span>{{ $fest->name }}</span>
                                            @if($fest->scheduled)
                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-800">Scheduled ✓</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-100 text-amber-800">Pending</span>
                                            @endif
                                        </div>
                                        <div class="text-slate-500 text-[11px] mt-0.5">
                                            Deity: <strong class="text-slate-700">{{ $fest->deity ?: 'General' }}</strong> · {{ \Carbon\Carbon::parse($fest->date)->format('l, d M Y') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 self-end sm:self-center">
                                    <button type="button" class="btn-create-for-fest px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 hover:border-amber-400 text-slate-700 hover:text-amber-700 font-semibold text-[11px] shadow-2xs flex items-center gap-1 transition"
                                            data-name="{{ $fest->name }}" data-deity="{{ $fest->deity }}">
                                        <i data-lucide="sparkles" class="w-3 h-3 text-amber-500"></i> Create Visual
                                    </button>
                                    <span class="px-2 py-1 rounded-md bg-slate-100 text-slate-600 font-medium text-[10px]">
                                        {{ \Carbon\Carbon::parse($fest->date)->diffForHumans() }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-10 text-slate-400 text-xs">
                                No Hindu festivals seeded. Click "Refresh" to populate 2026-2027 calendar.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Right: Queued Social Posts --}}
            <div class="col-span-12 lg:col-span-5">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/70 shadow-2xs">
                    <div class="flex items-center justify-between pb-3.5 border-b border-slate-100">
                        <div>
                            <h4 class="text-sm font-bold text-slate-900">Queued Devotional Posts & Reels</h4>
                            <p class="text-xs text-slate-500 mt-0.5">Scheduled for morning webhook dispatch.</p>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-800 border border-slate-200">{{ $socialPosts->count() }}</span>
                    </div>

                    <div class="space-y-3 mt-3.5 max-h-[580px] overflow-y-auto pr-1 text-xs">
                        @forelse($socialPosts as $post)
                            @php
                                $p = json_decode((string) $post->payload, true);
                            @endphp
                            <div class="p-3.5 bg-slate-50/70 rounded-xl border border-slate-200/80 space-y-2.5">
                                <div class="font-bold text-slate-900 flex justify-between items-center text-xs">
                                    <span>{{ $post->title }}</span>
                                    <span class="text-[9px] text-slate-800 font-bold uppercase tracking-wider bg-slate-200 px-2 py-0.5 rounded-full">{{ $post->status }}</span>
                                </div>

                                @if(!empty($p['video_url']))
                                    <div class="w-full h-36 rounded-lg overflow-hidden border border-slate-200 relative group bg-black">
                                        <video src="{{ $p['video_url'] }}" controls class="w-full h-full object-cover"></video>
                                        <span class="absolute top-1 left-1 bg-amber-500 text-slate-950 font-bold text-[9px] px-1.5 py-0.5 rounded">9:16 Video Reel</span>
                                    </div>
                                @elseif(!empty($p['image_url']))
                                    <div class="w-full h-32 rounded-lg overflow-hidden border border-slate-200 relative group">
                                        <img src="{{ $p['image_url'] }}" alt="Attached Creative" class="w-full h-full object-cover">
                                        <span class="absolute bottom-1 right-1 bg-black/70 text-white text-[9px] px-1.5 py-0.5 rounded">Vedic Visual Attached</span>
                                    </div>
                                @endif

                                @if($p && !empty($p['text']))
                                    <div class="text-slate-700 bg-white p-2.5 rounded-lg border border-slate-200/70 text-[11px] leading-relaxed">
                                        {{ $p['text'] }}
                                    </div>
                                @endif

                                @if($p && !empty($p['hashtags']))
                                    <div class="text-slate-600 font-medium text-[10px]">
                                        {{ $p['hashtags'] }}
                                    </div>
                                @endif

                                @if($p && !empty($p['link']))
                                    <div class="text-[10px] text-slate-500 flex items-center gap-1 font-mono">
                                        <i data-lucide="link" class="w-3 h-3 text-slate-400"></i>
                                        <a href="{{ $p['link'] }}" target="_blank" class="hover:underline text-slate-700 truncate">{{ $p['link'] }}</a>
                                    </div>
                                @endif

                                <div class="text-[10px] text-slate-400 flex justify-between items-center pt-2 border-t border-slate-200/70">
                                    <span>Due: <strong class="text-slate-700">{{ $post->due_at ?: 'Morning 06:30' }}</strong></span>
                                    <span class="font-mono text-slate-600 uppercase">{{ $post->channel }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-10 text-slate-400 text-xs italic">
                                No devotional posts currently queued. Click "Auto-Schedule Wishes" above to generate upcoming festival posts.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TAB PANE 2: OmniRoute AI Creative Studio (Reels & High Quality Visuals) --}}
    <div id="pane-studio" class="section-pane hidden">
        <div class="bg-white p-6 rounded-2xl border border-slate-200/70 shadow-2xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-5 border-b border-slate-100 gap-3">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-slate-900 flex items-center justify-center text-white shadow-2xs">
                        <i data-lucide="sparkles" class="w-5 h-5 text-amber-400"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 tracking-tight flex items-center gap-2">
                            OmniRoute AI Creative Studio
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-800 border border-amber-200 uppercase tracking-wider">Vedic Engine v2</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Generate 9:16 high-quality cinematic reels and photorealistic visuals directly linked to your Puja Booking and Astrology services.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Free Tier Flux Active
                    </span>
                </div>
            </div>

            {{-- 3-Way Mode Switcher --}}
            <div class="mt-4">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Select Campaign Source</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                    <button type="button" id="btn-mode-puja-catalog" class="preset-mode-btn active px-3.5 py-2.5 rounded-xl text-xs font-bold border border-slate-900 bg-slate-900 text-white transition flex items-center justify-center gap-2 shadow-2xs">
                        <i data-lucide="book-open" class="w-4 h-4 text-amber-400"></i> Puja Service Catalog
                    </button>
                    <button type="button" id="btn-mode-rashi" class="preset-mode-btn px-3.5 py-2.5 rounded-xl text-xs font-bold border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition flex items-center justify-center gap-2">
                        <i data-lucide="sun" class="w-4 h-4 text-amber-500"></i> Daily Rashifal & Astrology
                    </button>
                    <button type="button" id="btn-mode-custom" class="preset-mode-btn px-3.5 py-2.5 rounded-xl text-xs font-bold border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition flex items-center justify-center gap-2">
                        <i data-lucide="edit-3" class="w-4 h-4 text-slate-500"></i> Custom Prompt
                    </button>
                </div>
            </div>

            {{-- Studio Form Grid --}}
            <div class="grid grid-cols-12 gap-5 mt-4">
                {{-- Left Side: Parameters --}}
                <div class="col-span-12 lg:col-span-7 space-y-4">
                    
                    {{-- 1. Puja Catalog Mode --}}
                    <div id="wrapper-puja-catalog" class="space-y-3">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Select Puja Service to Promote</label>
                        <select id="select-active-puja" class="w-full text-xs rounded-xl border-slate-200 px-3.5 py-2.5 bg-slate-50 focus:bg-white text-slate-800 font-medium">
                            <option value="">Choose an active Puja from your catalog...</option>
                            @foreach($pujas as $pj)
                                <option value="{{ $pj->id }}" data-title="{{ $pj->puja_title }}" data-place="{{ $pj->puja_place }}" data-slug="{{ $pj->slug }}">
                                    {{ $pj->puja_title }} ({{ $pj->puja_place ?: 'Online' }}) — ₹{{ number_format($pj->puja_price) }}
                                </option>
                            @endforeach
                        </select>
                        <div class="p-3 bg-amber-50/60 rounded-xl border border-amber-200/80 text-xs text-amber-900 space-y-1">
                            <div class="font-bold flex items-center gap-1.5">
                                <i data-lucide="video" class="w-4 h-4 text-amber-600"></i> 1-Tap Puja Reel Automation
                            </div>
                            <p class="text-[11px] text-amber-800 leading-relaxed">
                                OmniRoute generates a 9:16 vertical motion reel, drafts the Hindi Sankalp promotion, includes your verified pandit booking link (<span class="font-mono text-amber-950 font-semibold">https://onlinepuja.live/puja/...</span>), and queues it for dispatch to Instagram & Facebook Reels.
                            </p>
                        </div>
                    </div>

                    {{-- 2. Daily Rashifal Mode --}}
                    <div id="wrapper-rashi-presets" class="hidden space-y-3">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Select Zodiac Sign (Astrology Reel)</label>
                        <select id="select-rashi" class="w-full text-xs rounded-xl border-slate-200 px-3.5 py-2.5 bg-slate-50 focus:bg-white text-slate-800 font-medium">
                            <option value="Aries (Mesh) Daily Rashifal & Career Forecast">♈ Mesh (Aries) — Daily Rashifal & Remedies</option>
                            <option value="Taurus (Vrishabh) Daily Rashifal & Wealth Forecast">♉ Vrishabh (Taurus) — Daily Rashifal & Remedies</option>
                            <option value="Gemini (Mithun) Daily Rashifal & Relationships">♊ Mithun (Gemini) — Daily Rashifal & Remedies</option>
                            <option value="Cancer (Kark) Daily Rashifal & Health">♋ Kark (Cancer) — Daily Rashifal & Remedies</option>
                            <option value="Leo (Singh) Daily Rashifal & Success">♌ Singh (Leo) — Daily Rashifal & Remedies</option>
                            <option value="Virgo (Kanya) Daily Rashifal & Studies">♍ Kanya (Virgo) — Daily Rashifal & Remedies</option>
                            <option value="Libra (Tula) Daily Rashifal & Harmony">♎ Tula (Libra) — Daily Rashifal & Remedies</option>
                            <option value="Scorpio (Vrishchik) Daily Rashifal & Protection">♏ Vrishchik (Scorpio) — Daily Rashifal & Remedies</option>
                            <option value="Sagittarius (Dhanu) Daily Rashifal & Luck">♐ Dhanu (Sagittarius) — Daily Rashifal & Remedies</option>
                            <option value="Capricorn (Makar) Daily Rashifal & Shani Remedy">♑ Makar (Capricorn) — Daily Rashifal & Remedies</option>
                            <option value="Aquarius (Kumbh) Daily Rashifal & Innovation">♒ Kumbh (Aquarius) — Daily Rashifal & Remedies</option>
                            <option value="Pisces (Meen) Daily Rashifal & Spirituality">♓ Meen (Pisces) — Daily Rashifal & Remedies</option>
                        </select>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Feature Verified Astrologer</label>
                            <select id="select-astrologer" class="w-full text-xs rounded-xl border-slate-200 px-3.5 py-2.5 bg-slate-50 focus:bg-white text-slate-800 font-medium">
                                <option value="">Top Verified Astrologer (Auto)</option>
                                @foreach($astrologers as $astro)
                                    <option value="{{ $astro->id }}" data-name="{{ $astro->name }}" data-skill="{{ $astro->primarySkill }}">
                                        {{ $astro->name }} ({{ $astro->primarySkill ?: 'Vedic Jyotish' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-700 space-y-1">
                            <div class="font-bold flex items-center gap-1.5 text-slate-900">
                                <i data-lucide="compass" class="w-4 h-4 text-indigo-500"></i> Astrologer Consultation Link Attached
                            </div>
                            <p class="text-[11px] text-slate-600 leading-relaxed">
                                Generates mystical Sanskrit Kundli motion video and links devotees directly to consult verified Vedic astrologers (<span class="font-mono text-slate-800 font-semibold">https://onlinepuja.live/astrologer/list</span>).
                            </p>
                        </div>
                    </div>

                    {{-- 3. Custom Topic Mode --}}
                    <div id="wrapper-custom-topic" class="hidden space-y-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Custom Topic / Ceremony Subject</label>
                        <input type="text" id="creative-topic" value="Kashi Vishwanath Rudrabhishek in Varanasi" 
                               class="w-full text-xs rounded-xl border-slate-200 px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-slate-900/10 font-medium text-slate-800" 
                               placeholder="e.g. Mahamrityunjaya Japa, Griha Pravesh, Kashi Vishwanath...">
                    </div>

                    {{-- Style & Aspect Ratio Controls --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Aesthetic Style</label>
                            <select id="creative-style" class="w-full text-xs rounded-xl border-slate-200 px-3 py-2 bg-slate-50 focus:bg-white text-slate-800 font-medium">
                                <option value="video_reel" selected>9:16 Cinematic Video Motion Reel (OmniRoute)</option>
                                <option value="photorealistic">Photorealistic Vedic Photography (Hasselblad 85mm f/1.8)</option>
                                <option value="temple_sanctum">Temple Sanctum Sanctorum (Glowing Brass Diyas)</option>
                                <option value="heritage_art">Raja Ravi Varma Heritage Fine Art (Oil on Canvas)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Aspect Ratio</label>
                            <select id="creative-ratio" class="w-full text-xs rounded-xl border-slate-200 px-3 py-2 bg-slate-50 focus:bg-white text-slate-800 font-medium">
                                <option value="9:16" selected>9:16 Vertical (Reels / Shorts / Stories)</option>
                                <option value="1:1">1:1 Square (Instagram / Facebook Post)</option>
                                <option value="4:5">4:5 Portrait (Social Feed Optimal)</option>
                                <option value="16:9">16:9 Landscape (Blog / Banner)</option>
                            </select>
                        </div>
                    </div>

                    {{-- Primary Execution Actions --}}
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 pt-2">
                        <button id="btn-generate-creative" class="btn btn-sm flex-1 bg-slate-900 hover:bg-slate-800 text-white font-bold py-2.5 rounded-xl shadow-2xs border-0 flex items-center justify-center gap-2 text-xs transition">
                            <i data-lucide="video" class="w-4 h-4 text-amber-400"></i> <span id="generate-btn-text">Generate Cinematic Video Reel (9:16)</span>
                        </button>
                        <button id="btn-auto-queue-reel" class="btn btn-sm bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold py-2.5 px-4 rounded-xl shadow-2xs border-0 flex items-center justify-center gap-1.5 text-xs transition whitespace-nowrap">
                            <i data-lucide="send" class="w-3.5 h-3.5"></i> 1-Tap Queue & Dispatch Reel
                        </button>
                    </div>

                    {{-- Inspect Prompt Drawer --}}
                    <div class="flex justify-end">
                        <button id="btn-preview-prompt" type="button" class="text-[11px] font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1">
                            <i data-lucide="eye" class="w-3.5 h-3.5"></i> Inspect Injected Vedic Prompt Tokens
                        </button>
                    </div>
                    <div id="prompt-inspector" class="hidden pt-3 border-t border-slate-100 bg-slate-50/70 p-3.5 rounded-xl text-xs space-y-2">
                        <div>
                            <span class="font-bold text-slate-700 uppercase tracking-wider text-[10px]">Injected Vedic Realism Tokens:</span>
                            <div id="inspector-positive" class="text-slate-600 mt-1 font-mono text-[11px] leading-relaxed bg-white p-2.5 rounded-lg border border-slate-200"></div>
                        </div>
                        <div>
                            <span class="font-bold text-rose-700 uppercase tracking-wider text-[10px]">Active Anti-CGI Negative Filter:</span>
                            <div id="inspector-negative" class="text-rose-600 mt-1 font-mono text-[11px] leading-relaxed bg-rose-50/50 p-2.5 rounded-lg border border-rose-200"></div>
                        </div>
                    </div>
                </div>

                {{-- Right Side: Live Creative Preview & Attachment --}}
                <div class="col-span-12 lg:col-span-5">
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 flex flex-col items-center justify-center min-h-[380px] text-center">
                        <div id="creative-preview-empty" class="text-slate-400 text-xs py-14">
                            <i data-lucide="film" class="w-10 h-10 mx-auto mb-2 text-slate-300"></i>
                            Select a Puja or Rashifal service above to preview and dispatch cinematic reels.
                        </div>

                        <div id="creative-preview-card" class="hidden w-full space-y-3">
                            <div class="w-full h-72 rounded-xl overflow-hidden bg-slate-950 flex items-center justify-center shadow-inner relative group">
                                <img id="preview-image" src="" alt="AI Generated Asset" class="w-full h-full object-cover rounded-xl hidden">
                                <video id="preview-video" src="" controls autoplay loop muted playsinline class="w-full h-full object-cover rounded-xl hidden"></video>
                                <div id="preview-loading" class="text-center text-white p-4">
                                    <div class="animate-spin inline-block w-8 h-8 border-3 border-amber-500 border-t-transparent rounded-full mb-2"></div>
                                    <div class="text-xs font-medium">Generating photorealistic Vedic creative…</div>
                                </div>
                            </div>
                            <div class="text-left text-xs text-slate-700 font-medium leading-relaxed bg-white p-3 rounded-xl border border-slate-200" id="preview-caption"></div>
                            <div class="flex items-center gap-2 pt-1">
                                <select id="select-attach-post" class="text-xs rounded-xl border-slate-200 px-3 py-2 bg-white text-slate-700 flex-1">
                                    <option value="">Select Queued Post to Attach Media...</option>
                                    @foreach($socialPosts as $post)
                                        <option value="{{ $post->id }}">{{ $post->title }} ({{ $post->status }})</option>
                                    @endforeach
                                </select>
                                <button id="btn-attach-to-post" class="btn btn-sm bg-slate-900 hover:bg-slate-800 border-0 text-white font-semibold px-3 py-2 rounded-xl text-xs flex items-center gap-1.5 whitespace-nowrap">
                                    <i data-lucide="paperclip" class="w-3.5 h-3.5 text-amber-400"></i> Attach
                                </button>
                                <a id="btn-download-creative" href="#" target="_blank" download="vedic-creative.mp4" class="btn btn-sm btn-outline-secondary text-xs p-2 rounded-xl bg-white border-slate-200">
                                    <i data-lucide="download" class="w-3.5 h-3.5 text-slate-600"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TAB PANE 3: Omnichannel Marketing Automation (WhatsApp, Brevo Email, z-Agents) --}}
    <div id="pane-automation" class="section-pane hidden">
        <div class="grid grid-cols-12 gap-5">

            {{-- 0. Meta & Instagram Business Suite Card --}}
            <div class="col-span-12 lg:col-span-6">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/70 shadow-2xs flex flex-col justify-between h-full">
                    <div>
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div class="flex items-center gap-2.5">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 via-rose-500 to-purple-600 text-white flex items-center justify-center font-bold text-lg shadow-xs">
                                    📸
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Meta & Instagram Business</h4>
                                    <span class="text-[11px] text-slate-500 font-mono">@onlinepuja.live &middot; Meta Graph v21.0</span>
                                </div>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                Native Graph API
                            </span>
                        </div>

                        <div class="mt-4 space-y-2.5 text-xs text-slate-600">
                            @php
                                $fbAcct = isset($socialAccounts) ? $socialAccounts->firstWhere('platform', 'facebook') : null;
                                $igAcct = isset($socialAccounts) ? $socialAccounts->firstWhere('platform', 'instagram') : null;
                            @endphp
                            <div class="flex items-center justify-between py-1.5 border-b border-slate-50">
                                <span class="text-slate-500 flex items-center gap-1.5">
                                    <span class="text-blue-600 font-bold">f</span> Facebook Page
                                </span>
                                <span class="font-medium text-slate-800 flex items-center gap-1">
                                    {{ $fbAcct ? $fbAcct->label : 'Online Puja' }}
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 ml-1"></span>
                                    <span class="text-[10px] text-emerald-600 font-bold">Publishing Active</span>
                                </span>
                            </div>
                            <div class="flex items-center justify-between py-1.5 border-b border-slate-50">
                                <span class="text-slate-500 flex items-center gap-1.5">
                                    <span class="text-pink-600 font-bold">📸</span> Instagram Account
                                </span>
                                <span class="font-medium text-slate-800 flex items-center gap-1">
                                    {{ $igAcct ? $igAcct->label : '@onlinepuja.live' }}
                                    <span class="w-2 h-2 rounded-full bg-amber-500 ml-1"></span>
                                    <span class="text-[10px] text-amber-600 font-bold">Token Refresh Required</span>
                                </span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-[11px] text-slate-600 leading-relaxed">
                                💡 To enable autonomous Instagram posting, connect your Meta App with <code>instagram_content_publish</code> permissions, or paste a fresh Page Access Token below.
                            </div>
                        </div>

                        {{-- Token Update Box --}}
                        <div class="mt-3 space-y-2">
                            <label class="block text-[11px] font-bold text-slate-700">Update Instagram / Meta Page Access Token</label>
                            <div class="flex items-center gap-2">
                                <input type="password" id="input-ig-token" placeholder="Paste Page Access Token (EAA...)" class="flex-1 text-xs rounded-xl border-slate-200 px-3 py-2 bg-slate-50 focus:bg-white font-mono">
                                <button type="button" id="btn-save-ig-token" class="px-3 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-bold text-xs whitespace-nowrap transition">
                                    Save Token
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center gap-2">
                        <a href="{{ route('social.connect', 'instagram') }}" class="flex-1 py-2.5 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-bold text-xs transition text-center shadow-xs">
                            🔗 Connect via Meta OAuth
                        </a>
                        <a href="https://developers.facebook.com/tools/explorer/" target="_blank" class="py-2.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold text-xs transition text-center border border-slate-200">
                            Graph Explorer ↗
                        </a>
                    </div>
                </div>
            </div>

            {{-- 1. WhatsApp Evolution API Card --}}
            <div class="col-span-12 lg:col-span-6">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/70 shadow-2xs flex flex-col justify-between h-full">
                    <div>
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                                    💬
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Evolution WhatsApp</h4>
                                    <span class="text-[11px] text-slate-500 font-mono">wa.vmstudio.digital</span>
                                </div>
                            </div>
                            <span id="badge-wa-status" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                Checking…
                            </span>
                        </div>

                        <div class="mt-4 space-y-2.5 text-xs text-slate-600">
                            <div class="flex justify-between py-1 border-b border-slate-50">
                                <span class="text-slate-400">Instance</span>
                                <span class="font-mono font-bold text-slate-800">onlinepuja</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-50">
                                <span class="text-slate-400">Connected Number</span>
                                <span class="font-mono font-medium text-slate-800">+91 70071 58014</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-50">
                                <span class="text-slate-400">Media Support</span>
                                <span class="font-medium text-emerald-700">1080×1920 MP4 Reels & Posters</span>
                            </div>
                        </div>

                        {{-- WhatsApp Quick Test Box --}}
                        <div class="mt-4 p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-2">
                            <label class="block text-[11px] font-bold text-slate-700">Send Test WhatsApp Message</label>
                            <input type="text" id="input-test-wa-mobile" placeholder="10-digit Mobile (e.g. 7007158014)" class="w-full text-xs rounded-lg border-slate-200 px-3 py-2 bg-white">
                            <button type="button" id="btn-send-test-wa" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-xs transition flex items-center justify-center gap-1.5">
                                <i data-lucide="send" class="w-3.5 h-3.5"></i> Send Test WhatsApp
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100">
                        <button type="button" id="btn-open-wa-qr" class="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-bold text-xs transition flex items-center justify-center gap-1.5 shadow-2xs">
                            <i data-lucide="qr-code" class="w-4 h-4 text-amber-400"></i> Scan WhatsApp QR Code
                        </button>
                    </div>
                </div>
            </div>

            {{-- 2. Brevo Email Automation Card --}}
            <div class="col-span-12 lg:col-span-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/70 shadow-2xs flex flex-col justify-between h-full">
                    <div>
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                                    ✉️
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Brevo Email Marketing</h4>
                                    <span class="text-[11px] text-slate-500 font-mono">api.brevo.com (v3)</span>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                Vault API Key Ready
                            </span>
                        </div>

                        <div class="mt-4 space-y-2.5 text-xs text-slate-600">
                            <div class="flex justify-between py-1 border-b border-slate-50">
                                <span class="text-slate-400">Sender Email</span>
                                <span class="font-mono font-medium text-slate-800">no-reply@onlinepuja.live</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-50">
                                <span class="text-slate-400">Sender Name</span>
                                <span class="font-medium text-slate-800">Onlinepuja Live</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-50">
                                <span class="text-slate-400">Subscriber List</span>
                                <span class="font-mono font-bold text-slate-800">List #2 (Synced)</span>
                            </div>
                        </div>

                        {{-- IP Whitelist Warning Box --}}
                        <div class="mt-3 p-3 rounded-xl bg-amber-50/70 border border-amber-200/80 text-[11px] text-amber-900 leading-relaxed">
                            <strong class="font-bold flex items-center gap-1 text-amber-800">
                                <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i> Brevo IP Security Rule
                            </strong>
                            Add VPS IP to Brevo <a href="https://app.brevo.com/security/authorised_ips" target="_blank" class="underline font-bold text-amber-950">Authorized IPs</a>:
                            <code class="font-mono block mt-1 bg-white px-1.5 py-0.5 rounded border border-amber-200 text-slate-800">200.234.41.231</code>
                            <code class="font-mono block mt-0.5 bg-white px-1.5 py-0.5 rounded border border-amber-200 text-slate-800">2a02:4780:63:d5d3::1</code>
                        </div>

                        {{-- Test Email Form --}}
                        <div class="mt-4 p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-2">
                            <label class="block text-[11px] font-bold text-slate-700">Send Test Devotional Email</label>
                            <input type="email" id="input-test-brevo-email" placeholder="devotee@example.com" class="w-full text-xs rounded-lg border-slate-200 px-3 py-2 bg-white">
                            <button type="button" id="btn-send-test-email" class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold text-xs transition flex items-center justify-center gap-1.5">
                                <i data-lucide="mail" class="w-3.5 h-3.5"></i> Send Test HTML Email
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. Push & In-App Notification Node --}}
            <div class="col-span-12 lg:col-span-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/70 shadow-2xs flex flex-col justify-between h-full">
                    <div>
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                                    <i data-lucide="bell" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Push & In-App Engine</h4>
                                    <span class="text-[11px] text-slate-500 font-mono">OneSignal & FCM</span>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                Active Node
                            </span>
                        </div>

                        <div class="mt-4 space-y-3 text-xs text-slate-600">
                            <p class="leading-relaxed">
                                Deliver real-time puja darshan reminders, live astrologer notifications, and festival tithi alerts directly to devotee lock screens.
                            </p>
                            <div class="space-y-1.5">
                                <div class="flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span><strong>Customer App:</strong> OneSignal Android & iOS notifications</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                    <span><strong>Astrologer Partner:</strong> Instant incoming consultation alerts</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                    <span><strong>Panchang Tithis:</strong> Daily Muhurat nudges at 06:00 AM</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100">
                        <a href="{{ route('growth-os.autopilot') }}" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl font-bold text-xs transition flex items-center justify-center gap-1.5 border border-slate-200">
                            <i data-lucide="sliders" class="w-4 h-4"></i> Manage Autopilot Push Loops
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- WHATSAPP QR CODE MODAL --}}
    <div id="modal-wa-qr" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-3xl max-w-sm w-full p-6 text-center shadow-2xl border border-slate-100 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <span class="text-emerald-500">💬</span> Connect WhatsApp
                </h3>
                <button type="button" id="btn-close-wa-qr" class="text-slate-400 hover:text-slate-700 text-lg font-bold leading-none">&times;</button>
            </div>
            <p class="text-xs text-slate-500 text-left leading-relaxed">
                Open WhatsApp on your business phone (+91 70071 58014), go to <strong>Linked Devices &gt; Link a Device</strong>, and scan the QR code below:
            </p>
            <div id="wa-qr-container" class="p-3 bg-slate-50 rounded-2xl border border-slate-100 flex items-center justify-center min-h-[220px]">
                <span class="animate-spin text-2xl">⏳</span>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" id="btn-refresh-wa-qr" class="flex-1 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                    🔄 Refresh QR
                </button>
                <button type="button" id="btn-done-wa-qr" class="flex-1 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition">
                    Done
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Tab Switching between Schedule, Creative Studio, and Marketing Automation
    var tabBtnSchedule = document.getElementById('tab-btn-schedule');
    var tabBtnStudio = document.getElementById('tab-btn-studio');
    var tabBtnAutomation = document.getElementById('tab-btn-automation');
    var paneSchedule = document.getElementById('pane-schedule');
    var paneStudio = document.getElementById('pane-studio');
    var paneAutomation = document.getElementById('pane-automation');

    function switchSectionTab(tab) {
        // Reset all tabs
        [paneSchedule, paneStudio, paneAutomation].forEach(function(p) { if(p) p.classList.add('hidden'); });
        [tabBtnSchedule, tabBtnStudio, tabBtnAutomation].forEach(function(b) {
            if(b) {
                b.classList.remove('bg-slate-900', 'text-white');
                b.classList.add('bg-white', 'text-slate-600');
            }
        });

        if (tab === 'studio' && paneStudio && tabBtnStudio) {
            paneStudio.classList.remove('hidden');
            tabBtnStudio.classList.add('bg-slate-900', 'text-white');
            tabBtnStudio.classList.remove('bg-white', 'text-slate-600');
        } else if (tab === 'automation' && paneAutomation && tabBtnAutomation) {
            paneAutomation.classList.remove('hidden');
            tabBtnAutomation.classList.add('bg-slate-900', 'text-white');
            tabBtnAutomation.classList.remove('bg-white', 'text-slate-600');
        } else if (paneSchedule && tabBtnSchedule) {
            paneSchedule.classList.remove('hidden');
            tabBtnSchedule.classList.add('bg-slate-900', 'text-white');
            tabBtnSchedule.classList.remove('bg-white', 'text-slate-600');
        }
    }

    if (tabBtnSchedule) tabBtnSchedule.addEventListener('click', function() { switchSectionTab('schedule'); });
    if (tabBtnStudio) tabBtnStudio.addEventListener('click', function() { switchSectionTab('studio'); });
    if (tabBtnAutomation) tabBtnAutomation.addEventListener('click', function() { switchSectionTab('automation'); });

    // 2. 3-Way Mode Switcher: Puja Catalog / Daily Rashifal / Custom
    var btnModePuja = document.getElementById('btn-mode-puja-catalog');
    var btnModeRashi = document.getElementById('btn-mode-rashi');
    var btnModeCustom = document.getElementById('btn-mode-custom');
    var wrapPuja = document.getElementById('wrapper-puja-catalog');
    var wrapRashi = document.getElementById('wrapper-rashi-presets');
    var wrapCustom = document.getElementById('wrapper-custom-topic');
    var currentCampaignMode = 'puja';

    var selectPuja = document.getElementById('select-active-puja');
    var selectRashi = document.getElementById('select-rashi');
    var topicInput = document.getElementById('creative-topic');

    function setCampaignMode(mode) {
        currentCampaignMode = mode;
        [btnModePuja, btnModeRashi, btnModeCustom].forEach(function(b) {
            b.classList.remove('bg-slate-900', 'text-white', 'border-slate-900', 'active');
            b.classList.add('bg-white', 'text-slate-700', 'border-slate-200');
        });
        [wrapPuja, wrapRashi, wrapCustom].forEach(function(w) { w.classList.add('hidden'); });

        if (mode === 'puja') {
            btnModePuja.classList.add('bg-slate-900', 'text-white', 'border-slate-900', 'active');
            wrapPuja.classList.remove('hidden');
            if (selectPuja && selectPuja.selectedIndex > 0) {
                topicInput.value = selectPuja.options[selectPuja.selectedIndex].getAttribute('data-title');
            }
        } else if (mode === 'rashi') {
            btnModeRashi.classList.add('bg-slate-900', 'text-white', 'border-slate-900', 'active');
            wrapRashi.classList.remove('hidden');
            if (selectRashi) topicInput.value = selectRashi.value;
        } else {
            btnModeCustom.classList.add('bg-slate-900', 'text-white', 'border-slate-900', 'active');
            wrapCustom.classList.remove('hidden');
        }
        updatePromptPreview();
    }

    if (btnModePuja) btnModePuja.addEventListener('click', function() { setCampaignMode('puja'); });
    if (btnModeRashi) btnModeRashi.addEventListener('click', function() { setCampaignMode('rashi'); });
    if (btnModeCustom) btnModeCustom.addEventListener('click', function() { setCampaignMode('custom'); });

    if (selectPuja) {
        selectPuja.addEventListener('change', function() {
            if (this.selectedIndex > 0) {
                var opt = this.options[this.selectedIndex];
                topicInput.value = opt.getAttribute('data-title') + ' in ' + (opt.getAttribute('data-place') || 'Varanasi');
                updatePromptPreview();
            }
        });
    }

    if (selectRashi) {
        selectRashi.addEventListener('change', function() {
            topicInput.value = this.value;
            updatePromptPreview();
        });
    }

    // 3. Webhook Drawer Toggle
    var toggleWebhookBtn = document.getElementById('toggle-webhook-config');
    var webhookDrawer = document.getElementById('webhook-config-drawer');
    if (toggleWebhookBtn && webhookDrawer) {
        toggleWebhookBtn.addEventListener('click', function() {
            webhookDrawer.classList.toggle('hidden');
        });
    }

    // 4. Creative Studio Logic
    var styleSelect = document.getElementById('creative-style');
    var ratioSelect = document.getElementById('creative-ratio');
    var btnText = document.getElementById('generate-btn-text');

    function syncBtnLabel() {
        if (!styleSelect || !btnText) return;
        if (styleSelect.value === 'video_reel') {
            btnText.textContent = 'Generate Cinematic Video Reel (9:16)';
            ratioSelect.value = '9:16';
        } else {
            btnText.textContent = 'Generate Photorealistic Vedic Image (Flux)';
        }
    }
    if (styleSelect) {
        styleSelect.addEventListener('change', function() {
            syncBtnLabel();
            updatePromptPreview();
        });
        syncBtnLabel();
    }

    // Festival row quick button -> Switch to Studio & Pre-fill
    document.querySelectorAll('.btn-create-for-fest').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var name = this.getAttribute('data-name');
            var deity = this.getAttribute('data-deity');
            setCampaignMode('custom');
            topicInput.value = deity ? (name + ' (' + deity + ')') : name;
            switchSectionTab('studio');
            updatePromptPreview();
        });
    });

    // Inspect prompt
    var promptInspector = document.getElementById('prompt-inspector');
    var btnInspect = document.getElementById('btn-preview-prompt');
    if (btnInspect) {
        btnInspect.addEventListener('click', function() {
            promptInspector.classList.toggle('hidden');
            if (!promptInspector.classList.contains('hidden')) {
                updatePromptPreview();
            }
        });
    }

    function updatePromptPreview() {
        if (!topicInput || !styleSelect || !ratioSelect) return;
        var topic = topicInput.value.trim() || 'Kashi Vishwanath Rudrabhishek';
        var style = styleSelect.value;
        var ratio = ratioSelect.value;
        fetch('{{ route("growth-os.action") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'preview_social_prompt', topic: topic, style: style, aspect_ratio: ratio })
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.ok && d.data) {
                var pEl = document.getElementById('inspector-positive');
                var nEl = document.getElementById('inspector-negative');
                if (pEl) pEl.textContent = d.data.prompt;
                if (nEl) nEl.textContent = d.data.negative_prompt;
            }
        });
    }

    // 5. Generate Visual / Video Reel via OmniRoute
    var btnGenerate = document.getElementById('btn-generate-creative');
    var previewEmpty = document.getElementById('creative-preview-empty');
    var previewCard = document.getElementById('creative-preview-card');
    var previewImg = document.getElementById('preview-image');
    var previewVid = document.getElementById('preview-video');
    var previewLoading = document.getElementById('preview-loading');
    var previewCaption = document.getElementById('preview-caption');
    var btnDownload = document.getElementById('btn-download-creative');
    var lastGeneratedUrl = null;
    var lastGeneratedType = 'image';

    if (btnGenerate) {
        btnGenerate.addEventListener('click', function() {
            var topic = topicInput.value.trim() || 'Kashi Vishwanath Rudrabhishek in Varanasi';
            var style = styleSelect.value;
            var ratio = ratioSelect.value;
            var isVideo = (style === 'video_reel');

            if (previewEmpty) previewEmpty.classList.add('hidden');
            previewCard.classList.remove('hidden');
            previewLoading.classList.remove('hidden');
            previewImg.classList.add('hidden');
            previewVid.classList.add('hidden');
            btnGenerate.disabled = true;
            btnGenerate.innerHTML = '<span class="animate-spin inline-block mr-1">⏳</span> Generating ' + (isVideo ? 'Video Reel' : 'Photorealistic Image') + '…';

            fetch('{{ route("growth-os.action") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({
                    action: 'generate_social_creative',
                    topic: topic,
                    style: style,
                    aspect_ratio: ratio,
                    type: isVideo ? 'video' : 'image'
                })
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                btnGenerate.disabled = false;
                syncBtnLabel();
                previewLoading.classList.add('hidden');

                if (d.ok && d.data) {
                    var data = d.data;
                    previewCaption.textContent = data.prompt || topic;
                    if (data.video_url || (isVideo && data.url)) {
                        lastGeneratedUrl = data.video_url || data.url;
                        lastGeneratedType = 'video';
                        previewVid.src = lastGeneratedUrl;
                        previewVid.classList.remove('hidden');
                        btnDownload.href = lastGeneratedUrl;
                        btnDownload.download = 'vedic-reel.mp4';
                        if (typeof toastr !== 'undefined') toastr.success('Devotional reel motion generated!');
                    } else if (data.url) {
                        lastGeneratedUrl = data.url;
                        lastGeneratedType = 'image';
                        previewImg.src = data.url;
                        previewImg.classList.remove('hidden');
                        btnDownload.href = data.url;
                        btnDownload.download = 'vedic-image.png';
                        if (typeof toastr !== 'undefined') toastr.success('Photorealistic Vedic creative generated successfully!');
                    }
                } else {
                    var err = (d.data && d.data.message) || d.error || 'Generation failed';
                    if (typeof toastr !== 'undefined') toastr.error(err);
                }
            })
            .catch(function(err) {
                btnGenerate.disabled = false;
                syncBtnLabel();
                previewLoading.classList.add('hidden');
                if (typeof toastr !== 'undefined') toastr.error(err.message);
            });
        });
    }

    // 6. 1-Tap Auto Queue & Dispatch Reel for Selected Service
    var btnAutoQueueReel = document.getElementById('btn-auto-queue-reel');
    if (btnAutoQueueReel) {
        btnAutoQueueReel.addEventListener('click', function() {
            var pujaId = (currentCampaignMode === 'puja' && selectPuja) ? selectPuja.value : null;
            var astroTopic = (currentCampaignMode === 'rashi' && selectRashi) ? selectRashi.value : null;

            if (currentCampaignMode === 'puja' && !pujaId) {
                alert('Please select a Puja from your catalogue dropdown first');
                return;
            }

            var b = this;
            b.disabled = true;
            b.innerHTML = '<span class="animate-spin inline-block mr-1">⏳</span> Generating & Queuing Reel…';

            fetch('{{ route("growth-os.action") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({
                    action: 'create_service_reel',
                    puja_id: pujaId,
                    astro_topic: astroTopic || topicInput.value.trim()
                })
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                b.disabled = false;
                b.innerHTML = '<i data-lucide="send" class="w-3.5 h-3.5"></i> 1-Tap Queue & Dispatch Reel';
                if (d.ok) {
                    if (typeof toastr !== 'undefined') toastr.success(d.message || 'Reel queued successfully!');
                    setTimeout(function() { window.location.reload(); }, 1200);
                } else {
                    if (typeof toastr !== 'undefined') toastr.error(d.error || 'Failed to queue reel');
                }
            })
            .catch(function(err) {
                b.disabled = false;
                b.innerHTML = '<i data-lucide="send" class="w-3.5 h-3.5"></i> 1-Tap Queue & Dispatch Reel';
                if (typeof toastr !== 'undefined') toastr.error(err.message);
            });
        });
    }

    // 7. Attach to Post (with media type awareness)
    var btnAttach = document.getElementById('btn-attach-to-post');
    if (btnAttach) {
        btnAttach.addEventListener('click', function() {
            var postId = document.getElementById('select-attach-post').value;
            if (!postId) {
                alert('Please select a queued post from the dropdown');
                return;
            }
            if (!lastGeneratedUrl) {
                alert('Please generate a visual asset first!');
                return;
            }

            btnAttach.disabled = true;
            btnAttach.textContent = 'Attaching…';
            var payload = {
                action: 'attach_social_creative',
                post_id: postId
            };
            if (lastGeneratedType === 'video') {
                payload.video_url = lastGeneratedUrl;
            } else {
                payload.image_url = lastGeneratedUrl;
            }

            fetch('{{ route("growth-os.action") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(payload)
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                btnAttach.disabled = false;
                btnAttach.innerHTML = '<i data-lucide="paperclip" class="w-3.5 h-3.5 mr-1 text-amber-400"></i> Attach';
                if (d.ok) {
                    if (typeof toastr !== 'undefined') toastr.success(d.message || 'Attached!');
                    setTimeout(function() { window.location.reload(); }, 1000);
                } else {
                    if (typeof toastr !== 'undefined') toastr.error(d.error || 'Failed');
                }
            });
        });
    }

    // 8. Seed Festivals
    var seedBtn = document.getElementById('btn-seed-festivals');
    if (seedBtn) {
        seedBtn.addEventListener('click', function() {
            var b = this;
            b.disabled = true;
            b.innerHTML = '<span class="animate-spin inline-block mr-1">⏳</span> Refreshing…';
            fetch('{{ route("growth-os.action") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ action: 'seed_festivals' })
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                b.disabled = false;
                b.innerHTML = '<i data-lucide="refresh-cw" class="w-3 h-3 mr-1"></i> Refresh';
                if (d.ok) {
                    if (window.showGrowthNotification) {
                        window.showGrowthNotification('success', d.message || d.output || 'Festivals refreshed successfully!');
                    } else if (typeof toastr !== 'undefined') {
                        toastr.success(d.output || 'Festivals refreshed');
                    } else {
                        alert(d.message || 'Festivals refreshed!');
                    }
                    setTimeout(function() { window.location.reload(); }, 1000);
                } else {
                    if (window.showGrowthNotification) {
                        window.showGrowthNotification('error', d.error || 'Failed');
                    } else if (typeof toastr !== 'undefined') {
                        toastr.error(d.error || 'Failed');
                    } else {
                        alert('Error: ' + (d.error || 'Failed'));
                    }
                }
            })
            .catch(function(err) {
                b.disabled = false;
                b.innerHTML = '<i data-lucide="refresh-cw" class="w-3 h-3 mr-1"></i> Refresh';
                if (window.showGrowthNotification) window.showGrowthNotification('error', err.message);
            });
        });
    }

    // 9. Auto Schedule Festivals
    var schedBtn = document.getElementById('btn-schedule-festivals');
    if (schedBtn) {
        schedBtn.addEventListener('click', function() {
            var b = this;
            b.disabled = true;
            b.innerHTML = '<span class="animate-spin inline-block mr-1">⏳</span> Scheduling Wishes…';
            fetch('{{ route("growth-os.action") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ action: 'schedule_festivals' })
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                b.disabled = false;
                b.innerHTML = '<i data-lucide="wand" class="w-3.5 h-3.5 mr-1 text-amber-400"></i> Auto-Schedule Wishes';
                if (d.ok) {
                    if (window.showGrowthNotification) {
                        window.showGrowthNotification('success', d.message || 'Festival wishes queued successfully!');
                    } else if (typeof toastr !== 'undefined') {
                        toastr.success(d.message || 'Wishes queued!');
                    } else {
                        alert(d.message || 'Wishes queued!');
                    }
                    setTimeout(function() { window.location.reload(); }, 1200);
                } else {
                    if (window.showGrowthNotification) {
                        window.showGrowthNotification('error', d.error || 'Scheduling failed');
                    } else if (typeof toastr !== 'undefined') {
                        toastr.error(d.error || 'Scheduling failed');
                    } else {
                        alert('Error: ' + (d.error || 'Failed'));
                    }
                }
            })
            .catch(function(err) {
                b.disabled = false;
                b.innerHTML = '<i data-lucide="wand" class="w-3.5 h-3.5 mr-1 text-amber-400"></i> Auto-Schedule Wishes';
                if (window.showGrowthNotification) {
                    window.showGrowthNotification('error', err.message);
                } else if (typeof toastr !== 'undefined') {
                    toastr.error(err.message);
                } else {
                    alert('Error: ' + err.message);
                }
            });
        });
    }

    // 10. Save & Test Social Webhook
    var btnSaveWebhook = document.getElementById('btn-save-social-webhook');
    var btnTestWebhook = document.getElementById('btn-test-social-webhook');
    var webhookInput = document.getElementById('social-webhook-input');

    if (btnSaveWebhook) {
        btnSaveWebhook.addEventListener('click', function() {
            var url = webhookInput.value.trim();
            btnSaveWebhook.disabled = true;
            btnSaveWebhook.textContent = 'Saving…';
            fetch('{{ route("growth-os.action") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ action: 'save_social_webhook', webhook_url: url })
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                btnSaveWebhook.disabled = false;
                btnSaveWebhook.textContent = 'Save Webhook';
                if (d.ok) {
                    if (typeof toastr !== 'undefined') toastr.success(d.message || 'Saved!');
                    setTimeout(function() { window.location.reload(); }, 1000);
                } else {
                    if (typeof toastr !== 'undefined') toastr.error(d.error || 'Failed');
                }
            });
        });
    }

    if (btnTestWebhook) {
        btnTestWebhook.addEventListener('click', function() {
            var url = webhookInput.value.trim();
            btnTestWebhook.disabled = true;
            btnTestWebhook.textContent = 'Pinging…';
            fetch('{{ route("growth-os.action") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ action: 'test_social_webhook', webhook_url: url })
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                btnTestWebhook.disabled = false;
                btnTestWebhook.textContent = 'Test Ping';
                if (d.ok) {
                    if (typeof toastr !== 'undefined') toastr.success(d.message || 'Ping successful!');
                } else {
                    if (typeof toastr !== 'undefined') toastr.error(d.error || 'Ping failed');
                }
            })
            .catch(function(err) {
                btnTestWebhook.disabled = false;
                btnTestWebhook.textContent = 'Test Ping';
                if (typeof toastr !== 'undefined') toastr.error(err.message);
            });
        });
    }

    var btnSaveIgToken = document.getElementById('btn-save-ig-token');
    var inputIgToken = document.getElementById('input-ig-token');
    if (btnSaveIgToken && inputIgToken) {
        btnSaveIgToken.addEventListener('click', function() {
            var tok = inputIgToken.value.trim();
            if (!tok) {
                if (typeof toastr !== 'undefined') toastr.error('Please enter a valid token.');
                return;
            }
            btnSaveIgToken.disabled = true;
            btnSaveIgToken.textContent = 'Saving…';
            fetch('{{ route("growth-os.action") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ action: 'save_social_token', platform: 'instagram', token: tok })
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                btnSaveIgToken.disabled = false;
                btnSaveIgToken.textContent = 'Save Token';
                if (d.ok) {
                    if (typeof toastr !== 'undefined') toastr.success(d.message || 'Token saved!');
                    setTimeout(function() { window.location.reload(); }, 1000);
                } else {
                    if (typeof toastr !== 'undefined') toastr.error(d.error || 'Failed to save token');
                }
            })
            .catch(function(err) {
                btnSaveIgToken.disabled = false;
                btnSaveIgToken.textContent = 'Save Token';
                if (typeof toastr !== 'undefined') toastr.error(err.message);
            });
        });
    }
                btnTestWebhook.textContent = 'Test Ping';
                if (typeof toastr !== 'undefined') toastr.error(err.message);
            });
        });
    }

    // 8. Omnichannel Marketing Handlers (WhatsApp QR, Test WA, Test Brevo)
    var modalWaQr = document.getElementById('modal-wa-qr');
    var btnOpenWaQr = document.getElementById('btn-open-wa-qr');
    var btnCloseWaQr = document.getElementById('btn-close-wa-qr');
    var btnDoneWaQr = document.getElementById('btn-done-wa-qr');
    var btnRefreshWaQr = document.getElementById('btn-refresh-wa-qr');
    var qrContainer = document.getElementById('wa-qr-container');

    function loadWaQr() {
        if (!qrContainer) return;
        qrContainer.innerHTML = '<div class="py-8"><span class="animate-spin inline-block text-2xl">⏳</span><p class="text-xs text-slate-400 mt-2">Generating WhatsApp QR code…</p></div>';
        fetch('{{ route("growth-os.action") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'get_wa_qr' })
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.ok && d.base64) {
                qrContainer.innerHTML = '<img src="' + d.base64 + '" class="w-56 h-56 mx-auto rounded-xl shadow-xs border border-slate-200" alt="WhatsApp QR Code" /><p class="text-[11px] text-emerald-700 font-bold mt-2">Scan in WhatsApp &gt; Linked Devices</p>';
            } else {
                qrContainer.innerHTML = '<div class="p-4 text-xs text-rose-600 bg-rose-50 rounded-xl">Unable to generate QR code.<br>' + (d.error || 'Please ensure wa.vmstudio.digital is reachable.') + '</div>';
            }
        })
        .catch(function(e) {
            qrContainer.innerHTML = '<div class="p-4 text-xs text-rose-600 bg-rose-50 rounded-xl">Network error loading QR: ' + e.message + '</div>';
        });
    }

    if (btnOpenWaQr) {
        btnOpenWaQr.addEventListener('click', function() {
            if (modalWaQr) modalWaQr.classList.remove('hidden');
            loadWaQr();
        });
    }
    if (btnCloseWaQr) {
        btnCloseWaQr.addEventListener('click', function() {
            if (modalWaQr) modalWaQr.classList.add('hidden');
        });
    }
    if (btnDoneWaQr) {
        btnDoneWaQr.addEventListener('click', function() {
            if (modalWaQr) modalWaQr.classList.add('hidden');
            window.location.reload();
        });
    }
    if (btnRefreshWaQr) {
        btnRefreshWaQr.addEventListener('click', function() {
            loadWaQr();
        });
    }

    // Test WhatsApp Message Dispatch
    var btnTestWa = document.getElementById('btn-send-test-wa');
    if (btnTestWa) {
        btnTestWa.addEventListener('click', function() {
            var mobile = document.getElementById('input-test-wa-mobile').value.trim();
            if (!mobile) {
                window.showGrowthNotification('warning', 'Please enter a 10-digit mobile number.');
                return;
            }
            var btn = this;
            var origHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="animate-spin inline-block">⏳</span> Sending…';

            fetch('{{ route("growth-os.action") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ action: 'test_whatsapp', mobile: mobile })
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                btn.disabled = false;
                btn.innerHTML = origHtml;
                if (typeof lucide !== 'undefined') lucide.createIcons();
                if (d.ok) {
                    window.showGrowthNotification('success', 'Test WhatsApp message sent successfully!');
                } else {
                    window.showGrowthNotification('error', d.error || 'WhatsApp delivery failed. Check QR connection.');
                }
            })
            .catch(function(err) {
                btn.disabled = false;
                btn.innerHTML = origHtml;
                if (typeof lucide !== 'undefined') lucide.createIcons();
                window.showGrowthNotification('error', err.message || 'WhatsApp network error');
            });
        });
    }

    // Test Brevo Email Dispatch
    var btnTestEmail = document.getElementById('btn-send-test-email');
    if (btnTestEmail) {
        btnTestEmail.addEventListener('click', function() {
            var email = document.getElementById('input-test-brevo-email').value.trim();
            if (!email) {
                window.showGrowthNotification('warning', 'Please enter a valid email address.');
                return;
            }
            var btn = this;
            var origHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="animate-spin inline-block">⏳</span> Sending…';

            fetch('{{ route("growth-os.action") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ action: 'test_brevo', email: email })
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                btn.disabled = false;
                btn.innerHTML = origHtml;
                if (typeof lucide !== 'undefined') lucide.createIcons();
                if (d.ok) {
                    window.showGrowthNotification('success', 'Test devotional email sent via Brevo!');
                } else {
                    window.showGrowthNotification('error', d.error || 'Brevo dispatch rejected. Did you whitelist the VPS IP?');
                }
            })
            .catch(function(err) {
                btn.disabled = false;
                btn.innerHTML = origHtml;
                if (typeof lucide !== 'undefined') lucide.createIcons();
                window.showGrowthNotification('error', err.message || 'Brevo network error');
            });
        });
    }
});
</script>
@endsection
