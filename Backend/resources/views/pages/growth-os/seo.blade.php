@extends('pages.growth-os.layout')

@section('growth_content')
@php
    $openCount = $openCount ?? 0;
    $fixedCount = $fixedCount ?? 0;
    $indexedCount = $indexedCount ?? 0;
    $avgScore = $avgScore ?? 0;
    $issues = $issues ?? collect([]);
    $healthChecks = $healthChecks ?? collect([]);
@endphp
<div class="grid grid-cols-12 gap-6">

    {{-- Scoreboard Cards with Vibrant High-Contrast Visuals --}}
    <div class="col-span-12 grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="ai-stat-card ai-stat-card-rose">
            <div class="flex items-center gap-3">
                <div class="ai-icon-bubble ai-icon-bubble-rose">⚠️</div>
                <div>
                    <div class="text-[11px] text-slate-600 font-bold uppercase tracking-wider">Open Technical Issues</div>
                    <div class="text-2xl font-black text-rose-700 font-mono">{{ $openCount }}</div>
                </div>
            </div>
            <span class="ai-pill ai-pill-rose">Requires Action</span>
        </div>
        <div class="ai-stat-card ai-stat-card-emerald">
            <div class="flex items-center gap-3">
                <div class="ai-icon-bubble ai-icon-bubble-emerald">🛡️</div>
                <div>
                    <div class="text-[11px] text-slate-600 font-bold uppercase tracking-wider">Healed via AI Fixer</div>
                    <div class="text-2xl font-black text-emerald-700 font-mono">{{ $fixedCount }}</div>
                </div>
            </div>
            <span class="ai-pill ai-pill-emerald">Protected</span>
        </div>
        <div class="ai-stat-card ai-stat-card-gold">
            <div class="flex items-center gap-3">
                <div class="ai-icon-bubble ai-icon-bubble-gold">⚡</div>
                <div>
                    <div class="text-[11px] text-slate-600 font-bold uppercase tracking-wider">God Mode Healer</div>
                    <div class="text-sm font-extrabold text-amber-900">Weekly Auto-Fix</div>
                </div>
            </div>
            <span class="ai-pill ai-pill-amber">Autonomous</span>
        </div>
        <div class="ai-stat-card ai-stat-card-blue">
            <div class="flex items-center gap-3">
                <div class="ai-icon-bubble ai-icon-bubble-blue">🔄</div>
                <div>
                    <div class="text-[11px] text-slate-600 font-bold uppercase tracking-wider">Safety Guarantee</div>
                    <div class="text-sm font-extrabold text-blue-900">100% Revertable</div>
                </div>
            </div>
            <span class="ai-pill ai-pill-blue">Vault Protected</span>
        </div>
    </div>

    {{-- Main Issue Ledger --}}
    <div class="col-span-12">
        <div class="ai-box">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-4 border-b border-slate-100 gap-3">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                        <i data-lucide="shield-check" class="w-5 h-5 text-rose-500"></i> Technical SEO Audit Ledger
                    </h3>
                    <div class="text-xs text-slate-500 mt-0.5">Scans sitemaps and pages for titles, meta lengths, H1 tags, alt attributes and accidental noindex.</div>
                </div>
                <div class="flex gap-2">
                    <button id="btn-scan-seo" class="ai-btn-secondary" style="font-size: 11.5px; padding: 7px 12px;">
                        <i data-lucide="scan" style="width: 13px; height: 13px; margin-right: 4px;"></i> Scan Sitemap
                    </button>
                    <button id="btn-fix-godmode" class="ai-btn-gold" style="font-size: 11.5px; padding: 7px 16px;">
                        <i data-lucide="wand" style="width: 13px; height: 13px; margin-right: 4px; color: #020617;"></i> Run AI Auto-Fix (God Mode)
                    </button>
                </div>
            </div>

            {{-- Issues Table --}}
            <div class="overflow-x-auto mt-4">
                <table class="ai-table">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600">
                            <th class="py-2.5">Issue Type</th>
                            <th class="py-2.5">Target URL / Entity</th>
                            <th class="py-2.5">Status</th>
                            <th class="py-2.5">Audit Payload</th>
                            <th class="py-2.5 text-center">Safety Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($issues as $issue)
                            <tr class="border-b border-slate-100 hover:bg-slate-50">
                                <td class="py-2.5 font-bold text-slate-700">
                                    <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-slate-100 text-slate-800">
                                        {{ str_replace('_', ' ', $issue->type) }}
                                    </span>
                                </td>
                                <td class="py-2.5 text-slate-800 font-medium">
                                    <a href="{{ $issue->page_url }}" target="_blank" class="text-primary hover:underline flex items-center gap-1">
                                        {{ Str::limit($issue->page_url, 45) }} <i data-lucide="external-link" class="w-3 h-3"></i>
                                    </a>
                                </td>
                                <td class="py-2.5">
                                    @if($issue->status === 'fixed')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            ✓ Auto-Fixed
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                            ● Open
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2.5 text-slate-500">
                                    @php
                                        $details = json_decode((string) ($issue->detail ?? ''), true);
                                    @endphp
                                    @if($details)
                                        <span class="font-mono text-[11px] text-slate-600">{{ Str::limit(json_encode($details), 50) }}</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="py-2.5 text-center">
                                    @if($issue->status === 'fixed' && $issue->revert_payload)
                                        <form method="POST" action="{{ route('growth-os.seo.revert', $issue->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="btn btn-xs btn-outline-secondary text-rose-700 hover:bg-rose-50 py-1 px-2 text-[10px]" title="Revert back to original">
                                                ↩ Revert
                                            </button>
                                        </form>
                                    @elseif($issue->status === 'open')
                                        <span class="text-[11px] text-slate-400">Heals in God-Mode run</span>
                                    @else
                                        <span class="text-[11px] text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-8 text-slate-400">
                                    No technical SEO issues found. Click "Scan Sitemap" to audit the live site.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ method_exists($issues, 'links') ? $issues->links() : '' }}
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var scanBtn = document.getElementById('btn-scan-seo');
    if (scanBtn) {
        scanBtn.addEventListener('click', function() {
            var b = this;
            b.disabled = true;
            b.innerHTML = 'Scanning…';
            fetch('{{ route("growth-os.action") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ action: 'seo_scan' })
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                b.disabled = false;
                b.innerHTML = '<i data-lucide="scan" class="w-3.5 h-3.5 mr-1"></i> Scan Sitemap';
                if (d.ok) {
                    if (window.showGrowthNotification) window.showGrowthNotification('success', d.output || 'Scan complete');
                    else if (typeof toastr !== 'undefined') toastr.success(d.output || 'Scan complete');
                    else alert(d.output || 'Scan complete');
                    setTimeout(function() { window.location.reload(); }, 1200);
                } else {
                    if (window.showGrowthNotification) window.showGrowthNotification('error', d.error || 'Scan failed');
                    else if (typeof toastr !== 'undefined') toastr.error(d.error || 'Scan failed');
                    else alert('Error: ' + (d.error || 'Scan failed'));
                }
            })
            .catch(function(err) {
                b.disabled = false;
                b.innerHTML = '<i data-lucide="scan" class="w-3.5 h-3.5 mr-1"></i> Scan Sitemap';
                if (window.showGrowthNotification) window.showGrowthNotification('error', err.message);
                else alert('Scan error: ' + err.message);
            });
        });
    }

    var fixBtn = document.getElementById('btn-fix-godmode');
    if (fixBtn) {
        fixBtn.addEventListener('click', function() {
            var b = this;
            b.disabled = true;
            b.innerHTML = '<span class="animate-spin inline-block mr-1">⏳</span> Applying AI Fixes…';
            fetch('{{ route("growth-os.action") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ action: 'seo_fix' })
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                b.disabled = false;
                b.innerHTML = '<i data-lucide="wand" class="w-3.5 h-3.5 mr-1"></i> Run AI Auto-Fix (God Mode)';
                if (d.ok) {
                    if (window.showGrowthNotification) window.showGrowthNotification('success', d.output || 'God Mode fixes applied!');
                    else if (typeof toastr !== 'undefined') toastr.success(d.output || 'God Mode fixes applied!');
                    else alert(d.output || 'God Mode fixes applied!');
                    setTimeout(function() { window.location.reload(); }, 1200);
                } else {
                    if (window.showGrowthNotification) window.showGrowthNotification('error', d.error || 'Fix failed');
                    else if (typeof toastr !== 'undefined') toastr.error(d.error || 'Fix failed');
                    else alert('Error: ' + (d.error || 'Fix failed'));
                }
            })
            .catch(function(err) {
                b.disabled = false;
                b.innerHTML = '<i data-lucide="wand" class="w-3.5 h-3.5 mr-1"></i> Run AI Auto-Fix (God Mode)';
                if (window.showGrowthNotification) window.showGrowthNotification('error', err.message);
                else alert('Fix error: ' + err.message);
            });
        });
    }
});
</script>
@endsection
