@extends('../layout/' . ($layout ?? 'side-menu'))

@section('subhead')
    <title>Growth & AI OS — Online Puja</title>
    <style>
        /* ========================================================
           GROWTH & AI OS — UNIVERSAL DESIGN SYSTEM TOKENS
           ======================================================== */
        .ai-scope {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #0f172a;
        }
        .ai-box {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 16px !important;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04), 0 2px 4px -2px rgba(15, 23, 42, 0.02) !important;
            padding: 20px !important;
            margin-bottom: 20px !important;
        }
        .ai-hero-card {
            background: linear-gradient(135deg, #090d16 0%, #0f172a 50%, #1e293b 100%) !important;
            border: 1px solid #1e293b !important;
            border-radius: 18px !important;
            padding: 22px 24px !important;
            color: #ffffff !important;
            box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.25) !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 16px !important;
        }
        @media (min-width: 768px) {
            .ai-hero-card {
                flex-direction: row !important;
                align-items: center !important;
                justify-content: space-between !important;
            }
        }
        .ai-stat-card {
            border: 1px solid #e2e8f0 !important;
            border-radius: 14px !important;
            padding: 16px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
        }
        .ai-stat-card:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 20px -3px rgba(15, 23, 42, 0.08) !important;
        }
        .ai-stat-card-emerald {
            background: #ecfdf5 !important;
            border-color: #a7f3d0 !important;
        }
        .ai-stat-card-gold {
            background: #fffbeb !important;
            border-color: #fde68a !important;
        }
        .ai-stat-card-blue {
            background: #eff6ff !important;
            border-color: #bfdbfe !important;
        }
        .ai-stat-card-rose {
            background: #fff1f2 !important;
            border-color: #fecdd3 !important;
        }
        .ai-stat-card-purple {
            background: #f5f3ff !important;
            border-color: #ddd6fe !important;
        }
        .ai-icon-bubble {
            width: 42px !important;
            height: 42px !important;
            border-radius: 12px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 20px !important;
            flex-shrink: 0 !important;
        }
        .ai-icon-bubble-emerald { background: #d1fae5 !important; color: #059669 !important; border: 1px solid #a7f3d0 !important; }
        .ai-icon-bubble-gold    { background: #fef3c7 !important; color: #d97706 !important; border: 1px solid #fde68a !important; }
        .ai-icon-bubble-blue    { background: #dbeafe !important; color: #2563eb !important; border: 1px solid #bfdbfe !important; }
        .ai-icon-bubble-rose    { background: #ffe4e6 !important; color: #e11d48 !important; border: 1px solid #fecdd3 !important; }
        .ai-icon-bubble-purple  { background: #ede9fe !important; color: #7c3aed !important; border: 1px solid #ddd6fe !important; }
        .ai-btn-gold {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
            color: #020617 !important;
            font-weight: 800 !important;
            font-size: 12.5px !important;
            border-radius: 10px !important;
            padding: 9px 18px !important;
            border: none !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 8px !important;
            cursor: pointer !important;
            box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35) !important;
            transition: all 0.15s ease !important;
            white-space: nowrap !important;
        }
        .ai-btn-gold:hover {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.45) !important;
            transform: translateY(-1px) !important;
            color: #020617 !important;
        }
        .ai-btn-dark {
            background: #0f172a !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 12.5px !important;
            border-radius: 10px !important;
            padding: 9px 18px !important;
            border: 1px solid #1e293b !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 8px !important;
            cursor: pointer !important;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.18) !important;
            transition: all 0.15s ease !important;
            white-space: nowrap !important;
        }
        .ai-btn-dark:hover {
            background: #1e293b !important;
            color: #ffffff !important;
            transform: translateY(-1px) !important;
        }
        .ai-btn-success {
            background: #059669 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 12px !important;
            border-radius: 9px !important;
            padding: 8px 14px !important;
            border: none !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
            cursor: pointer !important;
            transition: all 0.15s ease !important;
        }
        .ai-btn-success:hover {
            background: #047857 !important;
            color: #ffffff !important;
        }
        .ai-btn-danger {
            background: #e11d48 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 12px !important;
            border-radius: 9px !important;
            padding: 8px 14px !important;
            border: none !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
            cursor: pointer !important;
            transition: all 0.15s ease !important;
        }
        .ai-btn-danger:hover {
            background: #be123c !important;
            color: #ffffff !important;
        }
        .ai-btn-secondary {
            background: #f1f5f9 !important;
            color: #334155 !important;
            font-weight: 700 !important;
            font-size: 12px !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 9px !important;
            padding: 8px 14px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
            cursor: pointer !important;
            transition: all 0.15s ease !important;
        }
        .ai-btn-secondary:hover {
            background: #e2e8f0 !important;
            color: #0f172a !important;
        }
        .ai-pill {
            display: inline-flex !important;
            align-items: center !important;
            gap: 4px !important;
            padding: 3px 9px !important;
            border-radius: 20px !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            line-height: 1 !important;
        }
        .ai-pill-emerald {
            background: #ecfdf5 !important;
            color: #065f46 !important;
            border: 1px solid #a7f3d0 !important;
        }
        .ai-pill-amber {
            background: #fffbeb !important;
            color: #92400e !important;
            border: 1px solid #fde68a !important;
        }
        .ai-pill-rose {
            background: #fff1f2 !important;
            color: #9f1239 !important;
            border: 1px solid #fecdd3 !important;
        }
        .ai-pill-blue {
            background: #eff6ff !important;
            color: #1e40af !important;
            border: 1px solid #bfdbfe !important;
        }
        .ai-pill-slate {
            background: #f8fafc !important;
            color: #475569 !important;
            border: 1px solid #e2e8f0 !important;
        }
        .ai-input, .ai-select {
            width: 100% !important;
            background: #ffffff !important;
            border: 1.5px solid #cbd5e1 !important;
            border-radius: 9px !important;
            padding: 9px 12px !important;
            font-size: 12.5px !important;
            font-weight: 600 !important;
            color: #0f172a !important;
            outline: none !important;
            transition: all 0.15s ease !important;
        }
        .ai-input:focus, .ai-select:focus {
            border-color: #f59e0b !important;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15) !important;
        }
        .ai-table {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 12px !important;
        }
        .ai-table th {
            background: #f8fafc !important;
            color: #64748b !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            font-size: 10.5px !important;
            padding: 10px 14px !important;
            text-align: left !important;
            border-bottom: 1px solid #e2e8f0 !important;
        }
        .ai-table td {
            padding: 12px 14px !important;
            border-bottom: 1px solid #f1f5f9 !important;
            color: #1e293b !important;
            font-size: 12px !important;
        }
        .ai-table tr:hover td {
            background: #f8fafc !important;
        }
    </style>
@endsection

@section('subcontent')
{{-- 🌟 Command Bar: High-Impact Dark Slate & Gold Cockpit Header --}}
<div class="intro-y ai-hero-card mt-5" style="padding: 16px 22px !important; margin-bottom: 12px !important;">
    <div style="display: flex; align-items: center; gap: 14px;">
        <span style="width: 44px; height: 44px; border-radius: 12px; background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.35); color: #fbbf24; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: bold; flex-shrink: 0;">
            🕉️
        </span>
        <div>
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <h2 style="font-size: 16px; font-weight: 800; color: #ffffff; letter-spacing: -0.01em; margin: 0;">Online Puja — Growth & AI Operating System</h2>
                <span class="ai-pill" style="background: rgba(16, 185, 129, 0.2); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.35); font-size: 10px;">
                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #34d399; display: inline-block;"></span> Vedic Engine Active
                </span>
                <span class="ai-pill" style="background: #1e293b; color: #cbd5e1; border: 1px solid #334155; font-family: monospace; font-size: 10px;">
                    v2.4 Production
                </span>
            </div>
            <p style="font-size: 11.5px; color: #94a3b8; margin: 3px 0 0 0;">
                Autonomous Vedic SEO healing, keyword silos, festival video reels, multi-channel devotee broadcasts & AI astrologer twin.
            </p>
        </div>
    </div>
    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
        <button id="btn-quick-scan" class="ai-btn-secondary" style="font-size: 11.5px; padding: 8px 14px; background: #1e293b; color: #e2e8f0; border-color: #334155;">
            <i data-lucide="refresh-cw" style="width: 13px; height: 13px; margin-right: 4px; color: #38bdf8;"></i> Run SEO Scan
        </button>
        <button id="btn-quick-generate" class="ai-btn-gold" style="font-size: 11.5px; padding: 8px 16px;">
            <i data-lucide="sparkles" style="width: 13px; height: 13px; margin-right: 4px; color: #020617;"></i> AI Generate
        </button>
    </div>
</div>

{{-- Sub-Navigation Tabs: Color-Coded Module Strip --}}
<div class="intro-y mt-2 mb-3">
    <div class="flex items-center gap-1.5 bg-white p-1.5 rounded-xl border border-slate-200/80 shadow-2xs overflow-x-auto no-scrollbar">
        @php
            $currentRoute = Route::currentRouteName();
        @endphp
        <a href="{{ route('growth-os.dashboard') }}" class="py-2 px-3 rounded-lg text-xs font-bold flex items-center gap-2 whitespace-nowrap transition-all {{ $currentRoute === 'growth-os.dashboard' ? 'bg-slate-900 text-white shadow-2xs border-b-2 border-emerald-400' : 'text-slate-600 hover:text-emerald-700 hover:bg-emerald-50/70' }}">
            <i data-lucide="activity" class="w-3.5 h-3.5 {{ $currentRoute === 'growth-os.dashboard' ? 'text-emerald-400' : 'text-emerald-500' }}"></i> Growth & Goals
        </a>
        <a href="{{ route('growth-os.keywords') }}" class="py-2 px-3 rounded-lg text-xs font-bold flex items-center gap-2 whitespace-nowrap transition-all {{ $currentRoute === 'growth-os.keywords' ? 'bg-slate-900 text-white shadow-2xs border-b-2 border-blue-400' : 'text-slate-600 hover:text-blue-700 hover:bg-blue-50/70' }}">
            <i data-lucide="key" class="w-3.5 h-3.5 {{ $currentRoute === 'growth-os.keywords' ? 'text-blue-400' : 'text-blue-500' }}"></i> Keywords & Silos
        </a>
        <a href="{{ route('growth-os.content') }}" class="py-2 px-3 rounded-lg text-xs font-bold flex items-center gap-2 whitespace-nowrap transition-all {{ $currentRoute === 'growth-os.content' ? 'bg-slate-900 text-white shadow-2xs border-b-2 border-purple-400' : 'text-slate-600 hover:text-purple-700 hover:bg-purple-50/70' }}">
            <i data-lucide="file-text" class="w-3.5 h-3.5 {{ $currentRoute === 'growth-os.content' ? 'text-purple-400' : 'text-purple-500' }}"></i> Content Pipeline
        </a>
        <a href="{{ route('growth-os.seo') }}" class="py-2 px-3 rounded-lg text-xs font-bold flex items-center gap-2 whitespace-nowrap transition-all {{ $currentRoute === 'growth-os.seo' ? 'bg-slate-900 text-white shadow-2xs border-b-2 border-rose-400' : 'text-slate-600 hover:text-rose-700 hover:bg-rose-50/70' }}">
            <i data-lucide="shield-check" class="w-3.5 h-3.5 {{ $currentRoute === 'growth-os.seo' ? 'text-rose-400' : 'text-rose-500' }}"></i> SEO Auditor & Fixer
        </a>
        <a href="{{ route('growth-os.social') }}" class="py-2 px-3 rounded-lg text-xs font-bold flex items-center gap-2 whitespace-nowrap transition-all {{ $currentRoute === 'growth-os.social' ? 'bg-slate-900 text-white shadow-2xs border-b-2 border-amber-400' : 'text-slate-600 hover:text-amber-700 hover:bg-amber-50/70' }}">
            <i data-lucide="share-2" class="w-3.5 h-3.5 {{ $currentRoute === 'growth-os.social' ? 'text-amber-400' : 'text-amber-500' }}"></i> Social & Festivals
        </a>
        <a href="{{ route('growth-os.marketing') }}" class="py-2 px-3 rounded-lg text-xs font-bold flex items-center gap-2 whitespace-nowrap transition-all {{ $currentRoute === 'growth-os.marketing' ? 'bg-slate-900 text-white shadow-2xs border-b-2 border-indigo-400' : 'text-slate-600 hover:text-indigo-700 hover:bg-indigo-50/70' }}">
            <i data-lucide="send" class="w-3.5 h-3.5 {{ $currentRoute === 'growth-os.marketing' ? 'text-indigo-400' : 'text-indigo-500' }}"></i> Marketing & B2B
        </a>
        <a href="{{ route('growth-os.autopilot') }}" class="py-2 px-3 rounded-lg text-xs font-bold flex items-center gap-2 whitespace-nowrap transition-all {{ $currentRoute === 'growth-os.autopilot' ? 'bg-slate-900 text-white shadow-2xs border-b-2 border-amber-400' : 'text-slate-600 hover:text-amber-700 hover:bg-amber-50/70' }}">
            <i data-lucide="zap" class="w-3.5 h-3.5 {{ $currentRoute === 'growth-os.autopilot' ? 'text-amber-400' : 'text-amber-500' }}"></i> Autopilot Loops
        </a>
        <a href="{{ route('growth-os.ai') }}" class="py-2 px-3 rounded-lg text-xs font-bold flex items-center gap-2 whitespace-nowrap transition-all {{ $currentRoute === 'growth-os.ai' ? 'bg-slate-900 text-white shadow-2xs border-b-2 border-teal-400' : 'text-slate-600 hover:text-teal-700 hover:bg-teal-50/70' }}">
            <i data-lucide="cpu" class="w-3.5 h-3.5 {{ $currentRoute === 'growth-os.ai' ? 'text-teal-400' : 'text-teal-500' }}"></i> AI Engine & Vault
        </a>
    </div>
</div>

{{-- Feedback Messages --}}
@if(session('success'))
    <div class="intro-y alert alert-success show mb-4 mt-4 bg-emerald-50 border border-emerald-200 text-emerald-800 p-3 rounded-lg flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-2 text-xs font-medium">
            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" class="btn-close text-xs" data-tw-dismiss="alert">✕</button>
    </div>
@endif
@if(session('error'))
    <div class="intro-y alert alert-danger show mb-4 mt-4 bg-rose-50 border border-rose-200 text-rose-800 p-3 rounded-lg flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-2 text-xs font-medium">
            <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600"></i>
            <span>{{ session('error') }}</span>
        </div>
        <button type="button" class="btn-close text-xs" data-tw-dismiss="alert">✕</button>
    </div>
@endif

{{-- Module Content --}}
<div class="intro-y mt-4">
    @yield('growth_content')
</div>

{{-- Action Execution Modal / Toast --}}
<div id="growth-os-notification-area" class="fixed top-5 right-5 z-[9999] flex flex-col gap-2 pointer-events-none max-w-sm"></div>

<script>
window.showGrowthNotification = function(type, message) {
    if (typeof toastr !== 'undefined' && toastr[type]) {
        toastr[type](message);
        return;
    }
    // High-visibility fallback banner
    var container = document.getElementById('growth-os-notification-area');
    if (!container) return;
    var el = document.createElement('div');
    el.className = 'pointer-events-auto p-3.5 rounded-xl text-xs font-semibold shadow-lg transition-all transform duration-300 translate-y-2 border ' + 
        (type === 'success' ? 'bg-emerald-950 text-emerald-100 border-emerald-500/50' : 'bg-rose-950 text-rose-100 border-rose-500/50');
    el.innerHTML = '<div class="flex items-center gap-2"><span>' + (type === 'success' ? '✓' : '⚠️') + '</span><span>' + (message || '') + '</span></div>';
    container.appendChild(el);
    setTimeout(function() {
        el.style.opacity = '0';
        setTimeout(function() { el.remove(); }, 300);
    }, 4000);
};

window.executeAction = function(actionName, btn, extraPayload) {
    var originalHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="animate-spin inline-block mr-1">⏳</span> Running…';
    }

    var payload = Object.assign({ action: actionName }, extraPayload || {});

    fetch('{{ route("growth-os.action") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
        if (data.ok) {
            window.showGrowthNotification('success', data.message || data.output || 'Action executed successfully!');
            setTimeout(function() { window.location.reload(); }, 1200);
        } else {
            window.showGrowthNotification('error', data.error || 'Action failed');
        }
    })
    .catch(function(err) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
        window.showGrowthNotification('error', 'Request failed: ' + err.message);
    });
};

document.addEventListener('DOMContentLoaded', function() {
    var scanBtn = document.getElementById('btn-quick-scan');
    if (scanBtn) {
        scanBtn.addEventListener('click', function() { window.executeAction('seo_scan', this); });
    }

    var genBtn = document.getElementById('btn-quick-generate');
    if (genBtn) {
        genBtn.addEventListener('click', function() { window.executeAction('generate_content', this); });
    }
});
</script>
@endsection
