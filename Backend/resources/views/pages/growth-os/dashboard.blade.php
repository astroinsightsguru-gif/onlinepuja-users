@extends('pages.growth-os.layout')

@section('growth_content')
@php
    $goal = $goal ?? null;
    $elapsed = $elapsed ?? 0;
    $horizon = $horizon ?? 50;
    $totalTraffic = $totalTraffic ?? 0;
    $pace = $pace ?? 0;
    $expected = $expected ?? 0;
    $dailyAvg = $dailyAvg ?? 0;
    $requiredDaily = $requiredDaily ?? 0;
    $pipeline = $pipeline ?? [];
    $recentEvents = $recentEvents ?? collect([]);
    $liveExperiments = $liveExperiments ?? collect([]);
    $snapshots = $snapshots ?? collect([]);
    $recentChats = $recentChats ?? collect([]);
    $stats = array_merge([
        'total_keywords' => 0,
        'easy_keywords' => 0,
        'drafted_content' => 0,
        'approved_content' => 0,
        'open_seo_issues' => 0,
        'fixed_seo_issues' => 0,
        'festivals_seeded' => 0,
        'festivals_scheduled' => 0,
        'ai_models_count' => 0,
    ], $stats ?? []);
@endphp
<div class="grid grid-cols-12 gap-6">

    {{-- NORTH STAR GOAL HERO COCKPIT --}}
    <div class="col-span-12 lg:col-span-8">
        <div class="ai-hero-card" style="flex-direction: column !important; align-items: stretch !important; gap: 20px !important;">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-4 border-b border-slate-700/80 gap-3">
                <div>
                    <span class="ai-pill" style="background: rgba(244, 63, 94, 0.2); color: #fda4af; border: 1px solid rgba(244, 63, 94, 0.35);">🎯 Active North-Star Goal</span>
                    <h3 class="text-xl font-black text-white mt-2">{{ $goal ? $goal->title : '100,000 visitors in 50 days' }}</h3>
                    <div class="text-xs text-slate-300 mt-1">
                        Timeline: <span class="font-bold text-amber-300">{{ $goal ? $goal->start_date : '2026-08-26' }}</span> to <span class="font-bold text-amber-300">{{ $goal ? $goal->deadline : '2026-10-15' }}</span> · Day <span class="font-bold text-rose-400">{{ $elapsed }}</span> of <span class="font-bold text-white">{{ $horizon }}</span>
                    </div>
                </div>
                <div class="text-left sm:text-right">
                    <div class="text-3xl font-black text-amber-400 font-mono">{{ number_format($totalTraffic) }} <span class="text-xs font-normal text-slate-300">/ {{ number_format($goal ? $goal->target : 100000) }}</span></div>
                    <div class="text-xs font-bold mt-1 {{ $pace >= 80 ? 'text-emerald-400' : 'text-amber-300' }}">
                        Current Pace: {{ $pace }}% (Expected: {{ number_format($expected) }})
                    </div>
                </div>
            </div>

            {{-- High-Voltage Glowing Progress Bar --}}
            <div>
                <div class="flex justify-between text-xs text-slate-300 mb-1.5 font-semibold">
                    <span>Progress to 100K Devotees</span>
                    <span class="text-amber-400 font-mono">{{ round(($totalTraffic / max(1, $goal ? $goal->target : 100000)) * 100, 2) }}%</span>
                </div>
                <div class="w-full bg-slate-800 rounded-full h-4 overflow-hidden p-0.5 border border-slate-700">
                    <div class="bg-gradient-to-r from-amber-400 via-rose-500 to-purple-600 h-full rounded-full transition-all duration-500 shadow-lg" style="width: {{ min(100, max(3, ($totalTraffic / max(1, $goal ? $goal->target : 100000)) * 100)) }}%"></div>
                </div>
            </div>

            {{-- Honest Arithmetic Levers in Glowing Vibrant Tiles --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 pt-2">
                <div class="p-3.5 bg-slate-900/80 rounded-xl border border-slate-700 text-center">
                    <div class="text-[11px] text-slate-400 font-bold uppercase tracking-wider">Daily Required Traffic</div>
                    <div class="text-lg font-black text-amber-300 mt-1 font-mono">~{{ number_format(round(($goal ? $goal->target : 100000) / max(1, $horizon))) }} / day</div>
                </div>
                <div class="p-3.5 bg-slate-900/80 rounded-xl border border-slate-700 text-center">
                    <div class="text-[11px] text-slate-400 font-bold uppercase tracking-wider">Implied Content Output</div>
                    <div class="text-lg font-black text-cyan-300 mt-1 font-mono">2-4 Articles / day</div>
                </div>
                <div class="p-3.5 bg-slate-900/80 rounded-xl border border-slate-700 text-center">
                    <div class="text-[11px] text-slate-400 font-bold uppercase tracking-wider">Engine Mode</div>
                    <div class="text-lg font-black text-emerald-400 mt-1">Autonomous Compound</div>
                </div>
            </div>
        </div>

        {{-- Traffic Snapshots Table --}}
        <div class="ai-box mt-6">
            <h4 class="text-sm font-extrabold text-slate-900 mb-3 flex items-center gap-2">
                <i data-lucide="bar-chart-2" class="w-4 h-4 text-emerald-600"></i> Nightly Traffic Snapshots
            </h4>
            <div class="overflow-x-auto">
                <table class="ai-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Actual Traffic</th>
                            <th>Target Traffic</th>
                            <th>Pace Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($snapshots as $snap)
                            <tr>
                                <td class="font-bold text-slate-900">{{ $snap->date }}</td>
                                <td class="font-mono font-bold text-blue-600">{{ number_format($snap->value) }}</td>
                                <td class="font-mono text-slate-500">{{ number_format($expected) }}</td>
                                <td>
                                    <span class="ai-pill {{ $snap->value >= $expected ? 'ai-pill-emerald' : 'ai-pill-amber' }}">
                                        {{ $expected > 0 ? round(($snap->value / $expected) * 100) : 0 }}% pace
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-6 text-slate-400">
                                    Nightly snapshots trigger automatically at 23:40 via cron (`artisan goal:snapshot`).
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- QUICK STATS & RECENT MASTER BRAIN CHATS --}}
    <div class="col-span-12 lg:col-span-4 space-y-6">

        {{-- Metrics Summary with Colorful Icon Bubbles --}}
        <div class="ai-box">
            <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                <span style="color: #f59e0b;">⚡</span> Autonomous Pipeline Stats
            </h4>
            <div class="space-y-3.5 text-xs">
                <div class="ai-stat-card ai-stat-card-gold">
                    <div class="flex items-center gap-3">
                        <div class="ai-icon-bubble ai-icon-bubble-gold">🔑</div>
                        <div>
                            <span class="text-slate-600 block text-[11px] font-semibold">Researched Keywords</span>
                            <span class="font-extrabold text-amber-900 text-sm font-mono">{{ $stats['total_keywords'] }}</span>
                        </div>
                    </div>
                    <span class="ai-pill ai-pill-amber">{{ $stats['easy_keywords'] }} easy win</span>
                </div>

                <div class="ai-stat-card ai-stat-card-blue">
                    <div class="flex items-center gap-3">
                        <div class="ai-icon-bubble ai-icon-bubble-blue">📝</div>
                        <div>
                            <span class="text-slate-600 block text-[11px] font-semibold">Content Drafts</span>
                            <span class="font-extrabold text-blue-900 text-sm font-mono">{{ $stats['drafted_content'] }}</span>
                        </div>
                    </div>
                    <span class="ai-pill ai-pill-blue">{{ $stats['approved_content'] }} approved</span>
                </div>

                <div class="ai-stat-card ai-stat-card-rose">
                    <div class="flex items-center gap-3">
                        <div class="ai-icon-bubble ai-icon-bubble-rose">🛡️</div>
                        <div>
                            <span class="text-slate-600 block text-[11px] font-semibold">Open SEO Issues</span>
                            <span class="font-extrabold text-rose-900 text-sm font-mono">{{ $stats['open_seo_issues'] }}</span>
                        </div>
                    </div>
                    <span class="ai-pill ai-pill-rose">{{ $stats['fixed_seo_issues'] }} fixed</span>
                </div>

                <div class="ai-stat-card ai-stat-card-purple">
                    <div class="flex items-center gap-3">
                        <div class="ai-icon-bubble ai-icon-bubble-purple">🪔</div>
                        <div>
                            <span class="text-slate-600 block text-[11px] font-semibold">Hindu Festivals</span>
                            <span class="font-extrabold text-purple-900 text-sm font-mono">{{ $stats['festivals_seeded'] }}</span>
                        </div>
                    </div>
                    <span class="ai-pill" style="background: #ede9fe; color: #6d28d9; border: 1px solid #ddd6fe;">{{ $stats['festivals_scheduled'] }} scheduled</span>
                </div>

                <div class="ai-stat-card ai-stat-card-emerald">
                    <div class="flex items-center gap-3">
                        <div class="ai-icon-bubble ai-icon-bubble-emerald">🤖</div>
                        <div>
                            <span class="text-slate-600 block text-[11px] font-semibold">Live AI Models</span>
                            <span class="font-extrabold text-emerald-900 text-sm font-mono">{{ $stats['ai_models_count'] }}</span>
                        </div>
                    </div>
                    <span class="ai-pill ai-pill-emerald">Synced OmniRoute</span>
                </div>
            </div>
        </div>

        {{-- Recent Brain Command Log --}}
        <div class="ai-box">
            <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider mb-3 flex items-center justify-between">
                <span class="flex items-center gap-1.5"><span style="color: #6366f1;">🧠</span> Master Brain Log</span>
                <span class="ai-pill ai-pill-slate">Recent Chats</span>
            </h4>
            <div class="space-y-3 max-h-80 overflow-y-auto pr-1 text-xs">
                @forelse($recentChats as $chat)
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                        <div class="font-bold text-slate-900 flex items-center gap-1.5">
                            <span class="text-blue-500">👤</span> {{ Str::limit($chat->message, 45) }}
                        </div>
                        <div class="text-slate-700 mt-1 pl-3 border-l-2 border-amber-400 font-medium">
                            {{ Str::limit($chat->response, 90) }}
                        </div>
                        <div class="text-[10px] text-slate-400 mt-1.5 text-right font-mono">
                            Provider: <span class="font-bold text-slate-700">{{ $chat->provider ?: 'auto' }}</span> · {{ \Carbon\Carbon::parse($chat->created_at)->diffForHumans() }}
                        </div>
                    </div>
                @empty
                    <div class="text-center py-6 text-slate-400">
                        No chatbot commands logged yet. Click the 🧠 floating icon to give commands!
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
