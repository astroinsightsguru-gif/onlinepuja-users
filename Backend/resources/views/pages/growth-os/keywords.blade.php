@extends('pages.growth-os.layout')

@section('growth_content')
@php
    $clusters = $clusters ?? collect([]);
    $keywords = $keywords ?? collect([]);
    $untapped = $untapped ?? collect([]);
@endphp
<div class="grid grid-cols-12 gap-6">

    {{-- Left: Keyword List & Clusters --}}
    <div class="col-span-12 lg:col-span-8">
        <div class="ai-box">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-4 border-b border-slate-100 gap-3">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                        <i data-lucide="key" class="w-5 h-5 text-amber-500"></i> Researched Keywords & Clusters
                    </h3>
                    <div class="text-xs text-slate-500 mt-0.5">Tri-source research from Google Autocomplete, Intent Analysis & AI expansion.</div>
                </div>
                <button id="btn-trigger-research" class="ai-btn-gold" style="font-size: 11.5px; padding: 7px 14px;">
                    <i data-lucide="compass" style="width: 13px; height: 13px; margin-right: 4px; color: #020617;"></i> Trigger Tri-Source Research
                </button>
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('growth-os.keywords') }}" class="flex flex-wrap gap-2 mt-4">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search phrase…" class="form-control form-control-sm text-xs w-48 rounded-lg">
                <select name="cluster" class="form-select form-select-sm text-xs w-40 rounded-lg">
                    <option value="">All Clusters</option>
                    @foreach($clusters as $c)
                        <option value="{{ $c->cluster }}" {{ request('cluster') == $c->cluster ? 'selected' : '' }}>{{ ucfirst($c->cluster ?: 'General') }} ({{ $c->count }})</option>
                    @endforeach
                </select>
                <select name="intent" class="form-select form-select-sm text-xs w-36 rounded-lg">
                    <option value="">All Intents</option>
                    <option value="commercial" {{ request('intent') == 'commercial' ? 'selected' : '' }}>Commercial</option>
                    <option value="informational" {{ request('intent') == 'informational' ? 'selected' : '' }}>Informational</option>
                </select>
                <button type="submit" class="btn btn-sm btn-secondary text-xs px-3">Filter</button>
                @if(request()->hasAny(['search', 'cluster', 'intent']))
                    <a href="{{ route('growth-os.keywords') }}" class="btn btn-sm btn-outline-secondary text-xs px-2">Reset</a>
                @endif
            </form>

            {{-- Keywords Table --}}
            <div class="overflow-x-auto mt-4">
                <table class="ai-table">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600">
                            <th class="py-2.5">Search Phrase</th>
                            <th class="py-2.5">Cluster</th>
                            <th class="py-2.5">Intent</th>
                            <th class="py-2.5">Difficulty</th>
                            <th class="py-2.5">Source</th>
                            <th class="py-2.5 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($keywords as $kw)
                            <tr class="border-b border-slate-100 hover:bg-slate-50">
                                <td class="py-2.5 font-semibold text-slate-800">{{ $kw->phrase }}</td>
                                <td class="py-2.5">
                                    <span class="px-2 py-0.5 rounded text-[11px] bg-slate-100 text-slate-700 font-medium">{{ ucfirst($kw->cluster ?: 'general') }}</span>
                                </td>
                                <td class="py-2.5">
                                    <span class="px-2 py-0.5 rounded text-[11px] font-medium {{ $kw->intent === 'commercial' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                                        {{ ucfirst($kw->intent ?: 'informational') }}
                                    </span>
                                </td>
                                <td class="py-2.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-14 bg-slate-200 rounded-full h-1.5">
                                            <div class="h-1.5 rounded-full {{ $kw->difficulty <= 40 ? 'bg-emerald-500' : ($kw->difficulty <= 60 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ $kw->difficulty }}%"></div>
                                        </div>
                                        <span class="text-[11px] font-medium text-slate-600">{{ $kw->difficulty }}/100</span>
                                    </div>
                                </td>
                                <td class="py-2.5 text-slate-500">{{ $kw->source ?: 'autocomplete' }}</td>
                                <td class="py-2.5 text-center">
                                    <button class="btn btn-xs btn-outline-primary py-1 px-2 text-[11px] btn-draft-article" data-kw="{{ $kw->phrase }}" title="AI Draft Article">
                                        ✍️ Draft
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-8 text-slate-400">
                                    No keywords found. Click "Trigger Tri-Source Research" to sweep for new high-volume Indian puja search phrases.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ method_exists($keywords, 'links') ? $keywords->links() : '' }}
            </div>
        </div>
    </div>

    {{-- Right: Add Keyword & Silo Clusters Overview --}}
    <div class="col-span-12 lg:col-span-4 space-y-6">

        {{-- Add Manual Keyword --}}
        <div class="ai-box">
            <h4 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-3">Add Target Keyword</h4>
            <form method="POST" action="{{ route('growth-os.keywords.store') }}" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block text-slate-600 font-medium mb-1">Target Search Phrase</label>
                    <input type="text" name="phrase" required placeholder="e.g. online pandit for griha pravesh puja" class="form-control form-control-sm text-xs rounded-lg">
                </div>
                <div>
                    <label class="block text-slate-600 font-medium mb-1">Cluster / Topic</label>
                    <input type="text" name="cluster" placeholder="e.g. griha pravesh, satyanarayan puja" class="form-control form-control-sm text-xs rounded-lg">
                </div>
                <div>
                    <label class="block text-slate-600 font-medium mb-1">Search Intent</label>
                    <select name="intent" class="form-select form-select-sm text-xs rounded-lg">
                        <option value="commercial">Commercial (Booking / Price)</option>
                        <option value="informational">Informational (Vidhi / Meaning)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-sm w-full bg-slate-900 hover:bg-slate-800 border-0 text-white font-semibold py-2 rounded-lg shadow-2xs">
                    + Add Keyword to Pipeline
                </button>
            </form>
        </div>

        {{-- Cluster Breakdown --}}
        <div class="ai-box">
            <h4 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-3">Silo Cluster Breakdown</h4>
            <div class="space-y-2 max-h-72 overflow-y-auto pr-1 text-xs">
                @foreach($clusters as $cluster)
                    <div class="flex justify-between items-center p-2 rounded-lg bg-slate-50 border border-slate-100">
                        <span class="font-medium text-slate-700">{{ ucfirst($cluster->cluster ?: 'General') }}</span>
                        <span class="px-2 py-0.5 bg-white text-slate-700 font-bold rounded shadow-xs border border-slate-200">{{ $cluster->count }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var researchBtn = document.getElementById('btn-trigger-research');
    if (researchBtn) {
        researchBtn.addEventListener('click', function() {
            var btn = this;
            btn.disabled = true;
            btn.innerHTML = '<span class="animate-spin inline-block mr-1">⏳</span> Sweeping Autocomplete…';
            fetch('{{ route("growth-os.action") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ action: 'seo_research' })
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="compass" class="w-3.5 h-3.5 mr-1"></i> Trigger Tri-Source Research';
                if (typeof lucide !== 'undefined') lucide.createIcons();
                if (d.ok) {
                    window.showGrowthNotification('success', d.output || 'Keywords added!');
                    setTimeout(function() { window.location.reload(); }, 1200);
                } else {
                    window.showGrowthNotification('error', d.error || 'Failed');
                }
            })
            .catch(function(err) {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="compass" class="w-3.5 h-3.5 mr-1"></i> Trigger Tri-Source Research';
                if (typeof lucide !== 'undefined') lucide.createIcons();
                window.showGrowthNotification('error', err.message || 'Keyword research failed');
            });
        });
    }

    Array.prototype.forEach.call(document.querySelectorAll('.btn-draft-article'), function(btn) {
        btn.addEventListener('click', function() {
            var kw = this.getAttribute('data-kw');
            var b = this;
            b.disabled = true;
            b.innerHTML = 'Drafting…';
            fetch('{{ route("growth-os.action") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ action: 'generate_content', keyword: kw })
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                b.disabled = false;
                b.innerHTML = '✍️ Draft';
                if (d.ok) {
                    window.showGrowthNotification('success', 'Article drafted! Check Content Pipeline.');
                    setTimeout(function() { window.location.href = '{{ route("growth-os.content") }}'; }, 1000);
                } else {
                    window.showGrowthNotification('error', d.error || 'Failed');
                }
            })
            .catch(function(err) {
                b.disabled = false;
                b.innerHTML = '✍️ Draft';
                window.showGrowthNotification('error', err.message || 'Draft failed');
            });
        });
    });
});
</script>
@endsection
