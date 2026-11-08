@extends('pages.growth-os.layout')

@section('growth_content')
<style>
/* ========================================================
   GROWTH & MARKETING OS — UNIVERSAL DESIGN SYSTEM (AI ENGINE ALIGNED)
   High-contrast, zero-theme-clash, bulletproof scoped styles
   ======================================================== */
.ai-scope {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    color: #0f172a;
}

/* Card Containers */
.ai-box {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 16px !important;
    box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04), 0 2px 4px -2px rgba(15, 23, 42, 0.02) !important;
    padding: 22px !important;
    margin-bottom: 20px !important;
}

/* Hero Top & Spotlight Dark Cards */
.ai-hero-card {
    background: linear-gradient(135deg, #090d16 0%, #0f172a 50%, #1e293b 100%) !important;
    border: 1px solid #1e293b !important;
    border-radius: 18px !important;
    padding: 24px !important;
    color: #ffffff !important;
    box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.25) !important;
    margin-bottom: 20px !important;
    position: relative !important;
    overflow: hidden !important;
}

/* Primary Action Buttons */
.ai-btn-gold {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
    color: #020617 !important;
    font-weight: 800 !important;
    font-size: 13px !important;
    border-radius: 10px !important;
    padding: 10px 20px !important;
    border: none !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    cursor: pointer !important;
    box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35) !important;
    transition: all 0.15s ease !important;
    white-space: nowrap !important;
    text-decoration: none !important;
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
    font-size: 13px !important;
    border-radius: 10px !important;
    padding: 10px 18px !important;
    border: 1px solid #334155 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    cursor: pointer !important;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.18) !important;
    transition: all 0.15s ease !important;
    white-space: nowrap !important;
    text-decoration: none !important;
}
.ai-btn-dark:hover {
    background: #1e293b !important;
    border-color: #475569 !important;
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.28) !important;
    transform: translateY(-1px) !important;
    color: #ffffff !important;
}

/* Secondary & Testing Buttons */
.ai-btn-test {
    padding: 8px 14px !important;
    font-size: 12px !important;
    font-weight: 700 !important;
    border-radius: 9px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 6px !important;
    cursor: pointer !important;
    transition: all 0.15s ease !important;
    text-decoration: none !important;
}
.ai-btn-test-blue {
    background: #eff6ff !important;
    color: #1d4ed8 !important;
    border: 1.5px solid #bfdbfe !important;
}
.ai-btn-test-blue:hover {
    background: #dbeafe !important;
    color: #1e40af !important;
    border-color: #93c5fd !important;
}
.ai-btn-test-amber {
    background: #fffbeb !important;
    color: #b45309 !important;
    border: 1.5px solid #fde68a !important;
}
.ai-btn-test-amber:hover {
    background: #fef3c7 !important;
    color: #92400e !important;
    border-color: #fcd34d !important;
}
.ai-btn-test-rose {
    background: #fff1f2 !important;
    color: #be123c !important;
    border: 1.5px solid #fecdd3 !important;
}
.ai-btn-test-rose:hover {
    background: #ffe4e6 !important;
    color: #9f1239 !important;
    border-color: #fda4af !important;
}
.ai-btn-test-emerald {
    background: #ecfdf5 !important;
    color: #047857 !important;
    border: 1.5px solid #a7f3d0 !important;
}
.ai-btn-test-emerald:hover {
    background: #d1fae5 !important;
    color: #065f46 !important;
    border-color: #6ee7b7 !important;
}

/* Badges & Pills */
.ai-pill {
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    padding: 4px 10px !important;
    border-radius: 20px !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    line-height: 1 !important;
}

/* Number Step Badges */
.ai-step-badge {
    width: 24px !important;
    height: 24px !important;
    border-radius: 50% !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 11px !important;
    font-weight: 800 !important;
    color: #ffffff !important;
    flex-shrink: 0 !important;
}

/* Form Inputs, Selects & Textareas (Light Theme) */
.ai-input, .ai-select, .ai-textarea {
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
    box-sizing: border-box !important;
}
.ai-input:focus, .ai-select:focus, .ai-textarea:focus {
    border-color: #f59e0b !important;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15) !important;
}

/* Dark Form Controls (For Dark Hubs & Panels) */
.ai-input-dark, .ai-select-dark, .ai-textarea-dark {
    width: 100% !important;
    background: #090d16 !important;
    border: 1.5px solid #334155 !important;
    border-radius: 9px !important;
    padding: 9px 12px !important;
    font-size: 12.5px !important;
    font-weight: 600 !important;
    color: #ffffff !important;
    outline: none !important;
    transition: all 0.15s ease !important;
    box-sizing: border-box !important;
}
.ai-input-dark:focus, .ai-select-dark:focus, .ai-textarea-dark:focus {
    border-color: #fbbf24 !important;
    box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.18) !important;
}

/* Stat Cards */
.ai-stat-card {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 14px !important;
    padding: 16px 18px !important;
    display: flex !important;
    align-items: center !important;
    gap: 14px !important;
    box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.04) !important;
}
.ai-stat-card-dark {
    background: #090d16 !important;
    border: 1px solid #334155 !important;
    border-radius: 14px !important;
    padding: 16px 18px !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
}

/* Festival Subcard */
.ai-fest-card {
    background: #f8fafc !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 14px !important;
    padding: 16px !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: space-between !important;
    transition: all 0.15s ease !important;
}
.ai-fest-card:hover {
    border-color: #fbbf24 !important;
    background: #ffffff !important;
    box-shadow: 0 6px 16px -2px rgba(245, 158, 11, 0.1) !important;
    transform: translateY(-2px) !important;
}

/* Tables */
.ai-table {
    width: 100% !important;
    border-collapse: separate !important;
    border-spacing: 0 !important;
    font-size: 12px !important;
    color: #e2e8f0 !important;
}
.ai-table th {
    background: #0f172a !important;
    color: #94a3b8 !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    padding: 12px 14px !important;
    border-bottom: 1px solid #334155 !important;
    text-align: left !important;
}
.ai-table td {
    padding: 12px 14px !important;
    border-bottom: 1px solid #1e293b !important;
    vertical-align: middle !important;
}
.ai-table tr:hover td {
    background: rgba(30, 41, 59, 0.5) !important;
}
</style>

@php
    $brevoConfigured = $brevoConfigured ?? (!empty(config('services.brevo.key')));
    $waState = $waState ?? [];
    $campaigns = $campaigns ?? collect([]);
    $templates = $templates ?? collect([]);
    $subscribersCount = $subscribersCount ?? 0;
    $activeCampaignsCount = $activeCampaignsCount ?? 0;
    $totalDevotees = $totalDevotees ?? 0;
    $devoteesWithPhone = $devoteesWithPhone ?? 0;
    $devoteesWithEmail = $devoteesWithEmail ?? 0;
    $totalOrders = $totalOrders ?? 0;
    $totalB2bPartners = $totalB2bPartners ?? 0;
    $pujas = $pujas ?? collect([]);
    $leadPujas = $leadPujas ?? collect([]);
    $b2bCities = $b2bCities ?? collect([]);
    $b2bLeads = $b2bLeads ?? collect([]);
    $festivalsRadar = $festivalsRadar ?? collect([]);
@endphp

<div class="ai-scope">

    {{-- TOP: Autonomous Marketing Hub Header Ribbon --}}
    <div class="ai-hero-card" style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 20px;">
        <div style="display: flex; flex-direction: column; md:flex-row; justify-content: space-between; align-items: flex-start; gap: 16px;">
            <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                <span style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.3); color: #fbbf24; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold; flex-shrink: 0;">
                    🕉️
                </span>
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <h2 style="font-size: 18px; font-weight: 800; color: #ffffff; letter-spacing: -0.01em; margin: 0;">
                            Autonomous Marketing & Festival Multi-Campaign Hub
                        </h2>
                        @php
                            $waStateVal = $waState['state'] ?? ($waState['instance']['state'] ?? 'connecting');
                            $isWaConnected = ($waStateVal === 'open');
                        @endphp
                        <span class="ai-pill" style="{{ $isWaConnected ? 'background: rgba(16, 185, 129, 0.2); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.4);' : 'background: rgba(245, 158, 11, 0.2); color: #fde68a; border: 1px solid rgba(245, 158, 11, 0.4);' }}">
                            <span style="width: 6px; height: 6px; border-radius: 50%; background: {{ $isWaConnected ? '#34d399' : '#f59e0b' }}; display: inline-block;"></span>
                            WhatsApp: {{ ucfirst($waStateVal) }}
                        </span>
                        <span class="ai-pill" style="{{ $brevoConfigured ? 'background: rgba(59, 130, 246, 0.2); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.4);' : 'background: rgba(244, 63, 94, 0.2); color: #fecdd3; border: 1px solid rgba(244, 63, 94, 0.4);' }}">
                            <span style="width: 6px; height: 6px; border-radius: 50%; background: {{ $brevoConfigured ? '#60a5fa' : '#f43f5e' }}; display: inline-block;"></span>
                            Brevo Email: {{ $brevoConfigured ? 'Connected' : 'Missing Key' }}
                        </span>
                    </div>
                    <p style="font-size: 12px; color: #94a3b8; margin-top: 4px; margin-bottom: 0; line-height: 1.4;">
                        Orchestrate Dev Deepawali Varanasi 2026 & parallel Hindu festivals with 1080×1920 MP4 Video Reels, SEO longform guides, and national B2B partner distribution.
                    </p>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 12px; align-self: flex-end;">
                <a href="{{ route('growth-os.ai') }}#vault" class="ai-btn-gold">
                    <span>⚙️</span>
                    <span>Manage Gateways in AI Vault ↗</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Audience Metrics Strip --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 20px;">
        <div class="ai-stat-card">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #334155; flex-shrink: 0;">
                <i data-lucide="users" style="width: 22px; height: 22px;"></i>
            </div>
            <div>
                <p style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 2px 0;">Total Devotees</p>
                <h4 style="font-size: 22px; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.1;">{{ number_format($totalDevotees) }}</h4>
                <span style="font-size: 11px; color: #94a3b8;">Registered users</span>
            </div>
        </div>

        <div class="ai-stat-card">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: #ecfdf5; border: 1px solid #a7f3d0; display: flex; align-items: center; justify-content: center; color: #059669; flex-shrink: 0;">
                <i data-lucide="phone" style="width: 22px; height: 22px;"></i>
            </div>
            <div>
                <p style="font-size: 11px; font-weight: 700; color: #059669; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 2px 0;">WhatsApp Reachable</p>
                <h4 style="font-size: 22px; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.1;">{{ number_format($devoteesWithPhone) }}</h4>
                <span style="font-size: 11px; color: #059669; font-weight: 600;">Verified Indian (+91)</span>
            </div>
        </div>

        <div class="ai-stat-card">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: #eff6ff; border: 1px solid #bfdbfe; display: flex; align-items: center; justify-content: center; color: #2563eb; flex-shrink: 0;">
                <i data-lucide="mail-check" style="width: 22px; height: 22px;"></i>
            </div>
            <div>
                <p style="font-size: 11px; font-weight: 700; color: #2563eb; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 2px 0;">Email Reachable</p>
                <h4 style="font-size: 22px; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.1;">{{ number_format($devoteesWithEmail) }}</h4>
                <span style="font-size: 11px; color: #2563eb; font-weight: 600;">Brevo syncable</span>
            </div>
        </div>

        <div class="ai-stat-card">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: #fffbeb; border: 1px solid #fde68a; display: flex; align-items: center; justify-content: center; color: #d97706; flex-shrink: 0;">
                <i data-lucide="flame" style="width: 22px; height: 22px;"></i>
            </div>
            <div>
                <p style="font-size: 11px; font-weight: 700; color: #b45309; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 2px 0;">Bookable Pujas</p>
                <h4 style="font-size: 22px; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.1;">{{ count($pujas) }} Active</h4>
                <span style="font-size: 11px; color: #b45309; font-weight: 600;">Ready to promote</span>
            </div>
        </div>
    </div>

    {{-- DEV DEEPAWALI 2026 SPOTLIGHT BANNER (DARK HERO CARD - 100% BULLETPROOF CONTRAST) --}}
    <div class="ai-hero-card" style="padding: 26px !important; margin-bottom: 24px !important;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; align-items: center;">
            {{-- Left: Event Details & Copy --}}
            <div style="display: flex; flex-direction: column; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span class="ai-pill" style="background: rgba(245, 158, 11, 0.25); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.5); font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">
                        ✨ Next Major Varanasi Pilgrimage Event • Tuesday, 24 Nov 2026
                    </span>
                </div>

                <h2 style="font-size: 24px; font-weight: 900; color: #fbbf24; letter-spacing: -0.01em; margin: 0; line-height: 1.25;">
                    Dev Deepawali & Maha Deepdaan 2026 (Kartik Purnima)
                </h2>

                <p style="font-size: 13px; color: #f1f5f9; line-height: 1.6; margin: 0;">
                    On <strong style="color: #ffffff;">Tuesday, 24 November 2026</strong>, all 84 Ghats of Varanasi illuminate with 10+ Lakh earthen lamps as the gods descend to bathe in the holy Ganga. Devotees worldwide can offer 108 Deepdaan at Dashashwamedh & Manikarnika Ghats with certified Purohits chanting their family Gotra Sankalp.
                </p>

                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 4px;">
                    <span class="ai-pill" style="background: rgba(16, 185, 129, 0.2); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.4);">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #34d399; display: inline-block;"></span>
                        WhatsApp: 1080×1920 Reel + Copy Ready
                    </span>
                    <span class="ai-pill" style="background: rgba(59, 130, 246, 0.2); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.4);">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #60a5fa; display: inline-block;"></span>
                        Brevo Email: Responsive HTML Ready
                    </span>
                    <span class="ai-pill" style="background: rgba(168, 85, 247, 0.2); color: #d8b4fe; border: 1px solid rgba(168, 85, 247, 0.4);">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #c084fc; display: inline-block;"></span>
                        Push: Broadcast Ready
                    </span>
                </div>
            </div>

            {{-- Right: Autonomous Brain Studio Box --}}
            <div style="background: #090d16; border: 1.5px solid #334155; border-radius: 16px; padding: 18px; display: flex; flex-direction: column; gap: 10px;">
                <div style="text-align: center; padding-bottom: 8px; border-bottom: 1px solid #1e293b;">
                    <span style="font-size: 11px; color: #fbbf24; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 800;">
                        ⚡ Autonomous Brain Studio
                    </span>
                </div>

                <button type="button" id="btn-master-orchestrate" onclick="orchestrateMasterCampaign('Dev Deepawali Varanasi 2026')" class="ai-btn-gold" style="width: 100%; padding: 12px 16px; font-size: 13px;">
                    <i data-lucide="sparkles" style="width: 16px; height: 16px;"></i>
                    <span>🚀 1-Tap Full Autonomous Campaign</span>
                </button>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <button type="button" id="btn-gen-banners" onclick="generateFestivalBanners('Dev Deepawali & Deepdaan Varanasi 2026')" class="ai-btn-dark" style="font-size: 12px; padding: 9px 12px; color: #fbbf24 !important;">
                        <i data-lucide="image" style="width: 14px; height: 14px;"></i>
                        <span>Banners (1200 & 1080)</span>
                    </button>
                    <button type="button" id="btn-publish-blog" onclick="publishFestivalBlog('Dev Deepawali Varanasi 2026')" class="ai-btn-dark" style="font-size: 12px; padding: 9px 12px; color: #6ee7b7 !important;">
                        <i data-lucide="file-text" style="width: 14px; height: 14px;"></i>
                        <span>Publish SEO Blog</span>
                    </button>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <button type="button" id="btn-gen-dev-reel" onclick="generateFestivalReel('Dev Deepawali Varanasi 2026')" class="ai-btn-dark" style="font-size: 12px; padding: 9px 12px; color: #d8b4fe !important;">
                        <i data-lucide="film" style="width: 14px; height: 14px;"></i>
                        <span>1080x1920 Reel</span>
                    </button>
                    <button type="button" onclick="loadFestivalCampaign('Dev Deepawali Varanasi 2026')" class="ai-btn-dark" style="font-size: 12px; padding: 9px 12px; color: #e2e8f0 !important;">
                        <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i>
                        <span>Load Composer</span>
                    </button>
                </div>

                <button type="button" id="btn-quick-launch-dev" onclick="quickLaunchFestivalCampaign('Dev Deepawali Varanasi 2026')" style="width: 100%; padding: 10px 16px; border-radius: 10px; font-size: 12px; font-weight: 800; background: linear-flex; background: linear-gradient(135deg, #e11d48 0%, #be123c 100%); color: #ffffff; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 12px rgba(225, 29, 72, 0.35);">
                    <i data-lucide="zap" style="width: 14px; height: 14px;"></i>
                    <span>Broadcast (WhatsApp + Email + Push)</span>
                </button>

                {{-- Dynamic Asset Dock --}}
                <div id="master-asset-dock" style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #1e293b; display: none;">
                    <p style="font-size: 11px; font-weight: 800; color: #fbbf24; text-transform: uppercase; margin: 0 0 6px 0;">✨ Fresh Campaign Assets Live:</p>
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; background: #020617; padding: 8px 12px; border-radius: 8px; border: 1px solid #1e293b; font-size: 12px;">
                            <span style="color: #cbd5e1;">Live SEO Blog:</span>
                            <a id="dock-blog-link" href="#" target="_blank" style="color: #34d399; font-weight: bold; text-decoration: none;">View Article ↗</a>
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between; background: #020617; padding: 8px 12px; border-radius: 8px; border: 1px solid #1e293b; font-size: 12px;">
                            <span style="color: #cbd5e1;">1200x630 Banner:</span>
                            <a id="dock-banner-link" href="#" target="_blank" style="color: #fbbf24; font-weight: bold; text-decoration: none;">View Image ↗</a>
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between; background: #020617; padding: 8px 12px; border-radius: 8px; border: 1px solid #1e293b; font-size: 12px;">
                            <span style="color: #cbd5e1;">1080x1920 Reel:</span>
                            <a id="dock-reel-link" href="#" target="_blank" style="color: #c084fc; font-weight: bold; text-decoration: none;">Watch Video ↗</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

        {{-- WORKFLOW NAVIGATION TABS: Declutters the 1900-line screen into focused cockpit views --}}
    <div class="flex items-center justify-between border-b border-slate-200/80 pb-3 mb-5 mt-2">
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-1">
            <button type="button" id="tab-btn-broadcast" onclick="switchMarketingTab('broadcast')" class="mkt-tab-btn active px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-slate-900 text-white shadow-2xs cursor-pointer">
                <i data-lucide="send" class="w-4 h-4 text-indigo-400"></i> Broadcast Studio & Smartphone Preview
            </button>
            <button type="button" id="tab-btn-festival" onclick="switchMarketingTab('festival')" class="mkt-tab-btn px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-white text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200/80 cursor-pointer">
                <i data-lucide="calendar" class="w-4 h-4 text-amber-500"></i> Sacred Festival Multi-Radar
            </button>
            <button type="button" id="tab-btn-b2b" onclick="switchMarketingTab('b2b')" class="mkt-tab-btn px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-white text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200/80 cursor-pointer">
                <i data-lucide="briefcase" class="w-4 h-4 text-blue-500"></i> B2B Partner Distribution
            </button>
            <button type="button" id="tab-btn-tracking" onclick="switchMarketingTab('tracking')" class="mkt-tab-btn px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-white text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200/80 cursor-pointer">
                <i data-lucide="target" class="w-4 h-4 text-rose-500"></i> Pixel, Ads & Tag Manager Control
            </button>
            <button type="button" id="tab-btn-triggers" onclick="switchMarketingTab('triggers')" class="mkt-tab-btn px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-white text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200/80 cursor-pointer">
                <i data-lucide="zap" class="w-4 h-4 text-emerald-500"></i> Lifecycle Automated Triggers
            </button>
        </div>
    </div>

        {{-- PANE 5: AD TRACKING, PIXEL & MARKETING CONTROL CENTER --}}
    <div id="pane-tracking" class="mkt-pane" style="display: none;">
        <div class="ai-box">
            <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                        <span>🎯</span>
                        <span>Ad Tracking, Meta Pixel & Marketing Control Center</span>
                    </h3>
                    <p style="font-size: 12px; color: #64748b; margin: 3px 0 0 0;">
                        Centrally control Meta (Facebook/Instagram) Pixel, Conversions API (CAPI), Google Analytics 4, Tag Manager (GTM), and Google Ads.
                    </p>
                </div>
                <button type="button" onclick="saveTrackingSettings(event)" id="btn-save-tracking" class="ai-btn-gold" style="font-size: 13px; padding: 10px 22px;">
                    <i data-lucide="save" style="width: 15px; height: 15px;"></i> Save All Ad & Tracking Settings
                </button>
            </div>

            <form id="form-tracking-settings" onsubmit="saveTrackingSettings(event)">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 20px;">
                    
                    {{-- Card 1: Meta (Facebook & Instagram) Pixel & CAPI --}}
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #e2e8f0;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 18px;">🔵</span>
                                <h4 style="font-size: 14px; font-weight: 800; color: #0f172a; margin: 0;">Meta (Facebook & Instagram) Ads</h4>
                            </div>
                            <span class="ai-pill" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                                Site-Wide Active
                            </span>
                        </div>

                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #1e293b; margin-bottom: 4px;">
                                Meta Pixel ID <span style="color: #ef4444;">*</span>
                            </label>
                            <input type="text" name="meta_pixel_id" id="track-meta-pixel-id" value="{{ $trackingSettings['meta_pixel_id'] ?? '1738490804042986' }}" placeholder="1738490804042986" class="ai-input" style="width: 100%; font-family: monospace;">
                            <span style="font-size: 11px; color: #64748b; margin-top: 3px; display: block;">Injected across all pages in &lt;head&gt; firing PageView, ViewContent, InitiateCheckout & Purchase.</span>
                        </div>

                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #1e293b; margin-bottom: 4px;">
                                Meta Conversions API (CAPI) Access Token
                            </label>
                            <input type="password" name="meta_capi_token" id="track-meta-capi-token" value="{{ $trackingSettings['meta_capi_token'] ?? '' }}" placeholder="EAAG... (from Meta Events Manager > Settings > Generate Access Token)" class="ai-input" style="width: 100%; font-family: monospace;">
                            <span style="font-size: 11px; color: #64748b; margin-top: 3px; display: block;">Server-to-server purchase backup that bypasses iOS 14.5+ and browser ad-blockers.</span>
                        </div>

                        <div style="margin-bottom: 10px;">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #1e293b; margin-bottom: 4px;">
                                Social Media Dispatch Webhook URL
                            </label>
                            <div style="display: flex; gap: 8px;">
                                <input type="url" name="social_webhook_url" id="track-social-webhook-url" value="{{ $trackingSettings['social_webhook_url'] ?? '' }}" placeholder="https://hook.eu1.make.com/... or Zapier/Publer" class="ai-input" style="flex: 1; font-family: monospace; font-size: 11px;">
                                <button type="button" onclick="testSocialWebhookFromPanel()" class="ai-btn-dark" style="font-size: 11px; padding: 6px 12px; white-space: nowrap;">
                                    Ping
                                </button>
                            </div>
                            <span style="font-size: 11px; color: #64748b; margin-top: 3px; display: block;">Dispatches generated daily reels & posts to Instagram, Facebook & X.</span>
                        </div>
                    </div>

                    {{-- Card 2: Google Ecosystem (GA4, GTM, Google Ads) --}}
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #e2e8f0;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 18px;">🔴</span>
                                <h4 style="font-size: 14px; font-weight: 800; color: #0f172a; margin: 0;">Google Ecosystem (Analytics, Ads & GTM)</h4>
                            </div>
                            <span class="ai-pill" style="background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca;">
                                Active in Layout
                            </span>
                        </div>

                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #1e293b; margin-bottom: 4px;">
                                Google Analytics 4 (GA4) Measurement ID
                            </label>
                            <input type="text" name="ga4_measurement_id" id="track-ga4-id" value="{{ $trackingSettings['ga4_measurement_id'] ?? 'G-7T63JY1D93' }}" placeholder="G-7T63JY1D93" class="ai-input" style="width: 100%; font-family: monospace;">
                            <span style="font-size: 11px; color: #64748b; margin-top: 3px; display: block;">Fires standard pageviews and purchase events to GA4.</span>
                        </div>

                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #1e293b; margin-bottom: 4px;">
                                Google Tag Manager (GTM) Container ID
                            </label>
                            <input type="text" name="gtm_container_id" id="track-gtm-id" value="{{ $trackingSettings['gtm_container_id'] ?? '' }}" placeholder="GTM-XXXXXXX" class="ai-input" style="width: 100%; font-family: monospace;">
                            <span style="font-size: 11px; color: #64748b; margin-top: 3px; display: block;">If provided, GTM container &lt;script&gt; and &lt;noscript&gt; iframe will load automatically.</span>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 700; color: #1e293b; margin-bottom: 4px;">
                                    Google Ads Conversion ID
                                </label>
                                <input type="text" name="google_ads_id" id="track-gads-id" value="{{ $trackingSettings['google_ads_id'] ?? '' }}" placeholder="AW-123456789" class="ai-input" style="width: 100%; font-family: monospace;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 700; color: #1e293b; margin-bottom: 4px;">
                                    Conversion Label
                                </label>
                                <input type="text" name="google_ads_label" id="track-gads-label" value="{{ $trackingSettings['google_ads_label'] ?? '' }}" placeholder="AbC-D_eFgHiJkLm" class="ai-input" style="width: 100%; font-family: monospace;">
                            </div>
                        </div>
                    </div>

                    {{-- Card 3: Brevo Email Marketing API Gateway --}}
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #e2e8f0;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 18px;">✉️</span>
                                <h4 style="font-size: 14px; font-weight: 800; color: #0f172a; margin: 0;">Brevo Email Marketing Gateway</h4>
                            </div>
                            <span id="brevo-live-badge" class="ai-pill" style="{{ $brevoConfigured ? 'background: rgba(59, 130, 246, 0.2); color: #93c5fd;' : 'background: rgba(244, 63, 94, 0.2); color: #fecdd3;' }}">
                                {{ $brevoConfigured ? 'Key Saved' : 'Key Needed' }}
                            </span>
                        </div>

                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #1e293b; margin-bottom: 4px;">
                                Brevo API v3 Key <span style="color: #ef4444;">*</span>
                            </label>
                            <div style="display: flex; gap: 8px;">
                                <input type="password" name="brevo_api_key" id="track-brevo-api-key" placeholder="xkeysib-..." class="ai-input" style="flex: 1; font-family: monospace;">
                                <button type="button" onclick="testBrevoKeyFromPanel()" id="btn-test-brevo" class="ai-btn-gold" style="font-size: 11.5px; padding: 6px 14px; white-space: nowrap;">
                                    ⚡ Test & Save
                                </button>
                            </div>
                            <div id="brevo-test-result" style="font-size: 11px; margin-top: 5px; font-weight: 600;"></div>
                            <span style="font-size: 11px; color: #64748b; margin-top: 3px; display: block;">Powers devotional HTML email newsletters, SANKALP101 voucher onboarding, and tithi updates.</span>
                        </div>

                        <div style="padding: 10px 12px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; font-size: 11.5px; color: #1e40af; line-height: 1.45;">
                            <strong>Sender Address:</strong> no-reply@onlinepuja.live | <strong>List ID:</strong> 2
                        </div>
                    </div>

                    {{-- Card 4: Evolution WhatsApp Device Status --}}
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #e2e8f0;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 18px;">💬</span>
                                <h4 style="font-size: 14px; font-weight: 800; color: #0f172a; margin: 0;">Evolution WhatsApp Session</h4>
                            </div>
                            <span class="ai-pill" style="{{ ($waState['state'] ?? '') === 'open' ? 'background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;' : 'background: #fffbeb; color: #b45309; border: 1px solid #fde68a;' }}">
                                {{ ucfirst($waState['state'] ?? 'close') }}
                            </span>
                        </div>

                        <p style="font-size: 12px; color: #475569; margin-bottom: 12px;">
                            Gateway: <code style="font-size: 11px; color: #0f172a;">https://wa.vmstudio.digital</code> | Instance: <strong style="color: #0f172a;">onlinepuja</strong>
                        </p>

                        <div style="display: flex; gap: 8px;">
                            <button type="button" onclick="openWaQrModal()" class="ai-btn-gold" style="flex: 1; font-size: 12px; padding: 9px 14px;">
                                📲 Scan WhatsApp QR Code
                            </button>
                            <button type="button" onclick="fetchWaQr()" class="ai-btn-dark" style="font-size: 12px; padding: 9px 14px;">
                                Refresh Session
                            </button>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

    {{-- PANE 2: SACRED FESTIVAL MULTI-RADAR --}}
    <div id="pane-festival" class="mkt-pane" style="display: none;">
    {{-- SECTION: Sacred Festival Calendar (Parallel Multi-Campaign Engine) --}}
    <div class="ai-box">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: 10px;">
            <div>
                <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span>🗓️</span>
                    <span>Upcoming Sacred Festival Calendar (Parallel Multi-Campaign Engine)</span>
                </h3>
                <p style="font-size: 12px; color: #64748b; margin: 3px 0 0 0;">
                    Detects approaching Hindu festivals and automates T-14, T-7, T-3, T-1, and Day-0 campaign drops in parallel.
                </p>
            </div>
            <button type="button" onclick="openScheduleModal()" class="ai-btn-dark" style="font-size: 12px; padding: 8px 14px;">
                <i data-lucide="calendar-plus" style="width: 14px; height: 14px; color: #fbbf24;"></i>
                <span>➕ Schedule New Event / Festival</span>
            </button>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
            @if(isset($festivalsRadar) && count($festivalsRadar) > 0)
                @foreach($festivalsRadar as $fest)
                    @php
                        $badgeStyle = match($fest['milestone']) {
                            't_minus_1', 'day_0' => 'background: #fff1f2; color: #be123c; border: 1px solid #fecdd3;',
                            't_minus_3' => 'background: #fffbeb; color: #b45309; border: 1px solid #fde68a;',
                            't_minus_7' => 'background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;',
                            't_minus_14' => 'background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;',
                            default => 'background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;',
                        };
                    @endphp
                    <div class="ai-fest-card">
                        <div>
                            <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px solid #e2e8f0;">
                                <span style="font-size: 11.5px; font-weight: 700; color: #475569;">{{ $fest['formatted_date'] }}</span>
                                <span class="ai-pill" style="{{ $badgeStyle }}">
                                    {{ $fest['days_left'] }}d left
                                </span>
                            </div>

                            <h4 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 10px 0 4px 0;">{{ $fest['name'] }}</h4>
                            <p style="font-size: 11.5px; color: #64748b; margin: 0;">Deity: <strong style="color: #0f172a;">{{ $fest['deity'] ?? 'Vedic Devata' }}</strong></p>

                            @if(!empty($fest['puja']))
                                <div style="margin-top: 10px; font-size: 11.5px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 8px 10px; color: #92400e; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $fest['puja']['title'] }}">
                                    🪔 {{ $fest['puja']['title'] }}
                                </div>
                            @endif
                        </div>

                        <div style="margin-top: 14px; padding-top: 10px; border-top: 1px solid #e2e8f0; display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                            <button type="button" onclick="loadFestivalCampaign('{{ addslashes($fest['name']) }}')" class="ai-btn-test-blue" style="width: 100%;">
                                Composer
                            </button>
                            <button type="button" onclick="orchestrateMasterCampaign('{{ addslashes($fest['name']) }}')" class="ai-btn-gold" style="width: 100%; font-size: 11.5px; padding: 8px 10px;">
                                ⚡ 1-Tap Brain
                            </button>
                        </div>
                    </div>
                @endforeach
            @else
                <div style="grid-column: 1 / -1; padding: 24px; text-align: center; font-size: 12px; color: #64748b; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                    No upcoming festivals found in the next 60 days. Run <code>php artisan brain:seed-festivals</code> to refresh.
                </div>
            @endif
        </div>
    </div>

        </div>

    {{-- PANE 3: B2B PARTNER NETWORK HUB --}}
    <div id="pane-b2b" class="mkt-pane" style="display: none;">
    {{-- SECTION: B2B Partner Network Hub --}}
    <div class="ai-hero-card" style="padding: 26px !important; margin-bottom: 24px !important;">
        <div style="display: flex; flex-direction: column; md:flex-row; justify-content: space-between; align-items: flex-start; gap: 16px; padding-bottom: 18px; border-bottom: 1px solid #334155;">
            <div>
                <span class="ai-pill" style="background: rgba(99, 102, 241, 0.25); color: #a5b4fc; border: 1px solid rgba(99, 102, 241, 0.5); font-weight: 800; text-transform: uppercase;">
                    💼 B2B Partner Distribution Engine
                </span>
                <h2 style="font-size: 22px; font-weight: 900; color: #ffffff; letter-spacing: -0.01em; margin: 10px 0 6px 0;">
                    Promote Dev Deepawali 2026 & Varanasi Pujas via National B2B Partners
                </h2>
                <p style="font-size: 12.5px; color: #cbd5e1; max-width: 820px; line-height: 1.5; margin: 0;">
                    Partner with Travel Agencies, Pilgrimage Tour Operators, Astrologers, and Samagri Stores across Mumbai, Surat, Ahmedabad, Delhi, Kolkata, and Varanasi. They sell your Kashi Vedic Pujas to their clients with zero logistics, and earn a lucrative <strong style="color: #6ee7b7;">25% Revenue Share</strong> per booking.
                </p>
            </div>

            <div>
                <button type="button" onclick="seedB2bDirectory()" class="ai-btn-gold">
                    <span>⚡</span>
                    <span>Seed Premier Directory</span>
                </button>
            </div>
        </div>

        {{-- B2B Stat Counters --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin: 18px 0;">
            <div class="ai-stat-card-dark">
                <span style="font-size: 11px; font-weight: 700; color: #a5b4fc; text-transform: uppercase; letter-spacing: 0.05em;">B2B Partners in Network</span>
                <h4 style="font-size: 24px; font-weight: 900; color: #ffffff; margin: 4px 0 2px 0;">{{ number_format($totalB2bPartners ?? 0) }}</h4>
                <span style="font-size: 11px; color: #94a3b8;">Travel agents & astrologers</span>
            </div>
            <div class="ai-stat-card-dark">
                <span style="font-size: 11px; font-weight: 700; color: #6ee7b7; text-transform: uppercase; letter-spacing: 0.05em;">Partner Margin / Rev Share</span>
                <h4 style="font-size: 24px; font-weight: 900; color: #6ee7b7; margin: 4px 0 2px 0;">25% Net</h4>
                <span style="font-size: 11px; color: #94a3b8;">₹300 - ₹600 profit / booking</span>
            </div>
            <div class="ai-stat-card-dark">
                <span style="font-size: 11px; font-weight: 700; color: #fde68a; text-transform: uppercase; letter-spacing: 0.05em;">Top Indian Hubs</span>
                <h4 style="font-size: 20px; font-weight: 800; color: #fef08a; margin: 4px 0 2px 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    {{ isset($b2bCities) && count($b2bCities) > 0 ? $b2bCities->take(3)->pluck('city')->implode(', ') : 'Mumbai, Surat, Delhi' }}
                </h4>
                <span style="font-size: 11px; color: #94a3b8;">{{ count($b2bCities ?? []) }} cities active</span>
            </div>
            <div class="ai-stat-card-dark">
                <span style="font-size: 11px; font-weight: 700; color: #d8b4fe; text-transform: uppercase; letter-spacing: 0.05em;">Dev Deepawali Target</span>
                <h4 style="font-size: 24px; font-weight: 900; color: #d8b4fe; margin: 4px 0 2px 0;">₹5,00,000+</h4>
                <span style="font-size: 11px; color: #94a3b8;">1,000 partner bookings goal</span>
            </div>
        </div>

        {{-- B2B Actions: Lead Ingestion & Dev Deepawali Blast Center --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 18px; margin-top: 14px;">
            {{-- Google Maps Lead Ingestion --}}
            <div style="background: #090d16; border: 1.5px solid #334155; border-radius: 16px; padding: 18px;">
                <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px; border-bottom: 1px solid #1e293b; margin-bottom: 12px;">
                    <div>
                        <h4 style="font-size: 13.5px; font-weight: 800; color: #ffffff; margin: 0;">📍 Google Maps Business Lead Ingestion</h4>
                        <p style="font-size: 11.5px; color: #94a3b8; margin: 2px 0 0 0;">Import CSV from Google Maps scraper tools or search via Live Radar</p>
                    </div>
                    <span class="ai-pill" style="background: rgba(99, 102, 241, 0.2); color: #a5b4fc; border: 1px solid rgba(99, 102, 241, 0.4); font-family: monospace;">
                        Auto +91 Cleaner
                    </span>
                </div>

                {{-- Sub-tabs --}}
                <div style="display: flex; gap: 8px; margin-bottom: 14px;">
                    <button type="button" onclick="switchB2bTab('csv')" id="tab-btn-csv" class="ai-btn-gold" style="font-size: 12px; padding: 7px 14px;">
                        📁 CSV / Excel Upload
                    </button>
                    <button type="button" onclick="switchB2bTab('radar')" id="tab-btn-radar" class="ai-btn-dark" style="font-size: 12px; padding: 7px 14px;">
                        🔍 Live City Radar
                    </button>
                </div>

                {{-- Sub-tab 1: CSV Upload --}}
                <div id="b2b-tab-csv" style="display: flex; flex-direction: column; gap: 12px;">
                    <p style="font-size: 11.5px; color: #cbd5e1; margin: 0; line-height: 1.4;">
                        Upload leads scraped from Google Maps (compatible with <strong style="color: #ffffff;">Outscraper, Apify, Instant Data Scraper</strong>). The engine automatically detects name, phone, address, and Google rating.
                    </p>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 700; color: #cbd5e1; margin-bottom: 4px;">Default City</label>
                            <input type="text" id="b2b-csv-city" placeholder="e.g. Mumbai, Surat, Delhi" value="Mumbai" class="ai-input-dark">
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 700; color: #cbd5e1; margin-bottom: 4px;">Category</label>
                            <select id="b2b-csv-category" class="ai-select-dark">
                                <option value="travel_agency">✈️ Travel Agency / Tour Operator</option>
                                <option value="astrologer">🔮 Astrologer / Jyotish Kendra</option>
                                <option value="samagri_store">🪔 Puja Samagri / Spiritual Store</option>
                                <option value="hotel">🏨 Hotels & Dharamshalas</option>
                                <option value="corporate">🏢 Business & Trade Association</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 700; color: #cbd5e1; margin-bottom: 4px;">Upload CSV File or Paste Raw CSV Text</label>
                        <input type="file" id="b2b-csv-file" accept=".csv,.txt" style="display: block; width: 100%; font-size: 11.5px; color: #94a3b8; margin-bottom: 8px;">
                        <textarea id="b2b-csv-text" rows="3" placeholder="Or paste CSV with headers: name, phone, address, city, rating..." class="ai-textarea-dark" style="font-family: monospace;"></textarea>
                    </div>

                    <button type="button" id="btn-import-csv" onclick="importB2bCsv()" class="ai-btn-test-emerald" style="width: 100%; padding: 10px; font-size: 12.5px;">
                        <i data-lucide="upload-cloud" style="width: 16px; height: 16px;"></i>
                        <span>Ingest & Validate Indian Numbers (+91)</span>
                    </button>
                </div>

                {{-- Sub-tab 2: Live City Radar --}}
                <div id="b2b-tab-radar" style="display: none; flex-direction: column; gap: 12px;">
                    <p style="font-size: 11.5px; color: #cbd5e1; margin: 0; line-height: 1.4;">
                        Query Google Places API to search verified businesses in any target Indian commercial hub.
                    </p>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 700; color: #cbd5e1; margin-bottom: 4px;">Target City</label>
                            <input type="text" id="b2b-radar-city" placeholder="e.g. Surat, Ahmedabad, Jaipur" value="Surat" class="ai-input-dark">
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 700; color: #cbd5e1; margin-bottom: 4px;">Category Query</label>
                            <select id="b2b-radar-category" class="ai-select-dark">
                                <option value="travel_agency">✈️ Tour and Travel Agencies</option>
                                <option value="astrologer">🔮 Astrologers & Vastu Consultants</option>
                                <option value="samagri_store">🪔 Puja Samagri Stores</option>
                                <option value="hotel">🏨 Dharamshalas and Hotels near Ghats</option>
                            </select>
                        </div>
                    </div>
                    <button type="button" id="btn-radar-search" onclick="runGoogleMapsRadar()" class="ai-btn-gold" style="width: 100%; padding: 10px; font-size: 12.5px;">
                        <i data-lucide="radar" style="width: 16px; height: 16px;"></i>
                        <span>Scan Google Maps Places & Ingest</span>
                    </button>
                </div>
            </div>

            {{-- B2B Dev Deepawali Blast Center --}}
            <div style="background: #090d16; border: 1.5px solid #334155; border-radius: 16px; padding: 18px; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px; border-bottom: 1px solid #1e293b;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 18px;">🪔</span>
                            <h4 style="font-size: 13.5px; font-weight: 800; color: #fbbf24; margin: 0;">B2B Dev Deepawali Campaign</h4>
                        </div>
                        <span class="ai-pill" style="background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4);">
                            24 Nov 2026
                        </span>
                    </div>

                    <div style="background: rgba(15, 23, 42, 0.9); border: 1px solid #1e293b; border-radius: 12px; padding: 12px; font-size: 12px; color: #e2e8f0; line-height: 1.5;">
                        <p style="font-weight: 800; color: #fbbf24; margin: 0 0 6px 0;">Commercial Partner Pitch Outline:</p>
                        <p style="margin: 0 0 4px 0;">• <strong>The Problem:</strong> Hotels & boats in Kashi are 100% full. Devotees cannot travel.</p>
                        <p style="margin: 0 0 4px 0;">• <strong>The Solution:</strong> Travel agency sells 108 Deepdaan on Manikarnika Ghat with client gotra.</p>
                        <p style="margin: 0 0 4px 0;">• <strong>Fulfillment:</strong> Kashi Purohit performs puja, sends HD video + couriers Mahaprasad to client.</p>
                        <p style="margin: 0;">• <strong>Profit:</strong> Partner earns <strong style="color: #6ee7b7;">25% margin</strong> (₹300 - ₹600/booking).</p>
                    </div>

                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 700; color: #cbd5e1; margin-bottom: 4px;">Filter Partner Target City</label>
                        <select id="b2b-campaign-city" class="ai-select-dark">
                            <option value="all">🇮🇳 All Cities & Partners (National Blast)</option>
                            @if(isset($b2bCities))
                                @foreach($b2bCities as $bc)
                                    <option value="{{ $bc->city }}">{{ $bc->city }} ({{ $bc->total }} leads)</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 14px; padding-top: 12px; border-top: 1px solid #1e293b;">
                    <button type="button" onclick="previewB2bPitch()" class="ai-btn-dark" style="width: 100%; color: #fbbf24 !important;">
                        <i data-lucide="eye" style="width: 14px; height: 14px;"></i>
                        <span>Preview Sample Partner Pitch</span>
                    </button>
                    <button type="button" id="btn-dispatch-b2b" onclick="dispatchB2bCampaign()" class="ai-btn-gold" style="width: 100%; padding: 12px; font-size: 13px;">
                        <i data-lucide="send" style="width: 15px; height: 15px;"></i>
                        <span>🚀 Launch B2B Dev Deepawali Campaign</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- B2B Partners Directory Table --}}
        <div style="margin-top: 22px; background: #090d16; border: 1.5px solid #334155; border-radius: 16px; padding: 18px;">
            <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px; border-bottom: 1px solid #1e293b; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <h4 style="font-size: 13.5px; font-weight: 800; color: #ffffff; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">
                        B2B Verified Leads Directory
                    </h4>
                    <span class="ai-pill" style="background: rgba(99, 102, 241, 0.2); color: #a5b4fc; border: 1px solid rgba(99, 102, 241, 0.4);">
                        {{ count($b2bLeads ?? []) }} Displayed
                    </span>
                </div>
                <div style="font-size: 11.5px; color: #94a3b8;">
                    Each partner has an auto-generated unique booking code for 25% revenue share attribution.
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="ai-table">
                    <thead>
                        <tr>
                            <th>Business Name</th>
                            <th>Category</th>
                            <th>City</th>
                            <th>Phone / WhatsApp</th>
                            <th>Google Rating</th>
                            <th>Partner Code</th>
                            <th>Status</th>
                            <th style="text-align: right;">Quick Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($b2bLeads) && count($b2bLeads) > 0)
                            @foreach($b2bLeads as $lead)
                                @php
                                    $catLabel = match($lead->category) {
                                        'travel_agency' => '✈️ Travel Agent',
                                        'astrologer' => '🔮 Astrologer',
                                        'samagri_store' => '🪔 Samagri Store',
                                        'hotel' => '🏨 Hotel/Ashram',
                                        default => '🏢 Corporate',
                                    };
                                    $statusBadge = match($lead->status) {
                                        'partner_onboarded' => 'background: rgba(16, 185, 129, 0.2); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.4);',
                                        'contacted' => 'background: rgba(59, 130, 246, 0.2); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.4);',
                                        default => 'background: #1e293b; color: #cbd5e1; border: 1px solid #334155;',
                                    };
                                @endphp
                                <tr>
                                    <td style="font-weight: 700; color: #ffffff;">
                                        {{ $lead->business_name }}
                                        @if($lead->contact_person)
                                            <span style="font-size: 11px; color: #94a3b8; font-weight: normal;">({{ $lead->contact_person }})</span>
                                        @endif
                                    </td>
                                    <td>{{ $catLabel }}</td>
                                    <td style="color: #fbbf24; font-weight: 600;">{{ $lead->city ?? 'India' }}</td>
                                    <td style="font-family: monospace;">{{ $lead->phone }}</td>
                                    <td>
                                        @if($lead->google_rating)
                                            <span style="color: #fbbf24; font-weight: 700;">★ {{ $lead->google_rating }}</span>
                                            <span style="font-size: 11px; color: #94a3b8;">({{ $lead->google_reviews_count }})</span>
                                        @else
                                            <span style="color: #64748b;">—</span>
                                        @endif
                                    </td>
                                    <td style="font-family: monospace; font-weight: 700; color: #a5b4fc;">{{ $lead->partner_code }}</td>
                                    <td>
                                        <span class="ai-pill" style="{{ $statusBadge }}">
                                            {{ ucfirst($lead->status) }}
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <button type="button" onclick="previewB2bPitch({{ $lead->id }})" class="ai-btn-test-blue" style="font-size: 11px; padding: 4px 10px;">
                                            Pitch
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="8" style="padding: 24px; text-align: center; color: #94a3b8;">
                                    No B2B leads in database yet. Click <strong>"Seed Premier Directory"</strong> or upload a Google Maps CSV above.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

        </div>

    {{-- PANE 1: OMNICHANNEL BROADCAST STUDIO & PHONE PREVIEW (DEFAULT ACTIVE) --}}
    <div id="pane-broadcast" class="mkt-pane">
    {{-- SECTION: Omnichannel Campaign Studio & Devotee Smartphone Preview --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 24px;">

        {{-- Campaign Composer (Left) --}}
        <div class="ai-box" style="margin-bottom: 0 !important;">
            <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; margin-bottom: 16px;">
                <div>
                    <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">Broadcast Campaign Studio</h3>
                    <p style="font-size: 12px; color: #64748b; margin: 3px 0 0 0;">Compose and blast devotional campaigns across WhatsApp & Brevo Email</p>
                </div>
                <span class="ai-pill" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
                    Omnichannel
                </span>
            </div>

            <form id="form-broadcast" style="display: flex; flex-direction: column; gap: 14px;" onsubmit="event.preventDefault();">
                @csrf
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="display: block; font-size: 11.5px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Target Devotee Audience</label>
                        <select id="camp-segment" class="ai-select">
                            <option value="test">📱 Test to My Own WhatsApp & Email (Safe)</option>
                            <option value="all">👥 All Registered Devotees ({{ $totalDevotees }} contacts)</option>
                            <option value="bookers">🪔 Past Puja Bookers ({{ $totalOrders }} orders)</option>
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-size: 11.5px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Dispatch Channels</label>
                        <select id="camp-channel" class="ai-select">
                            <option value="both">⚡ Omnichannel (WhatsApp + Brevo Email)</option>
                            <option value="whatsapp">📱 WhatsApp Only (Evolution API)</option>
                            <option value="email">✉️ Email Only (Brevo REST API)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label style="display: block; font-size: 11.5px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Select Bookable Puja (Auto-Fills Details)</label>
                    <select id="camp-puja-preset" class="ai-select" style="background: #fffbeb !important; border-color: #fde68a !important; color: #92400e !important; font-weight: 700;" onchange="applyPujaPreset(this)">
                        <option value="">-- Choose a Puja to Promote --</option>
                        @foreach($pujas as $p)
                            <option value="{{ $p->id }}" 
                                    data-title="{{ $p->puja_title }}" 
                                    data-place="{{ $p->puja_place }}" 
                                    data-price="{{ $p->puja_price }}"
                                    data-slug="{{ $p->slug }}">
                                {{ $p->puja_title }} (₹{{ number_format($p->puja_price) }}{{ $p->puja_place ? ' • ' . $p->puja_place : '' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="display: block; font-size: 11.5px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">WhatsApp Headline / Greeting</label>
                        <input type="text" id="camp-headline" class="ai-input" 
                               value="🕉️ Special Rudrabhishek Puja Blessings from Varanasi" oninput="updateLivePreview()">
                    </div>
                    <div>
                        <label style="display: block; font-size: 11.5px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Email Subject Line</label>
                        <input type="text" id="camp-subject" class="ai-input" 
                               value="Divine Blessings for You and Your Family — OnlinePuja.live">
                    </div>
                </div>

                <div>
                    <label style="display: block; font-size: 11.5px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Message Body & Vedic Sankalp Details</label>
                    <textarea id="camp-body" rows="4" class="ai-textarea" oninput="updateLivePreview()">Pranam Devotee!

Participate in the auspicious Vedic ritual performed by certified Varanasi pandits with your personal sankalp and gotra. Sanctified Ganga Jal, Bhasma & holy prasad will be blessed in your name and dispatched directly to your doorstep.

May Lord Shiva bestow divine health, peace, and abundance upon your household. 🙏✨</textarea>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
                    <div>
                        <label style="display: block; font-size: 11.5px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Attach Media URL (Video Reel MP4 / Poster Image)</label>
                        <input type="url" id="camp-media-url" class="ai-input" style="font-family: monospace;" 
                               placeholder="https://.../video.mp4 or poster.jpg" oninput="updateLivePreview()">
                    </div>
                    <div>
                        <label style="display: block; font-size: 11.5px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Media Type</label>
                        <select id="camp-media-type" class="ai-select" onchange="updateLivePreview()">
                            <option value="image">🖼️ Poster Image</option>
                            <option value="video">🎬 9:16 Video Reel</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="display: block; font-size: 11.5px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Button Label</label>
                        <input type="text" id="camp-cta-text" class="ai-input" 
                               value="Book Sacred Puja with Sankalp 🙏" oninput="updateLivePreview()">
                    </div>
                    <div>
                        <label style="display: block; font-size: 11.5px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Destination URL</label>
                        <input type="url" id="camp-cta-url" class="ai-input" style="font-family: monospace;" 
                               value="https://onlinepuja.live" oninput="updateLivePreview()">
                    </div>
                </div>

                <div style="padding-top: 14px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 4px;">
                    <button type="button" onclick="sendCampaign('test')" class="ai-btn-dark">
                        <i data-lucide="smartphone" style="width: 14px; height: 14px;"></i>
                        <span>Send Test to Admin</span>
                    </button>
                    <button type="button" onclick="sendCampaign('live')" class="ai-btn-gold">
                        <i data-lucide="send" style="width: 14px; height: 14px;"></i>
                        <span>Launch Broadcast Campaign</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Live Smartphone Preview Mockup (Right) --}}
        <div class="ai-box" style="margin-bottom: 0 !important; display: flex; flex-direction: column; align-items: center;">
            <div style="width: 100%; display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; margin-bottom: 16px;">
                <h4 style="font-size: 12.5px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">
                    Live WhatsApp Preview
                </h4>
                <span style="font-size: 11px; color: #64748b; font-weight: 600;">Devotee Phone View</span>
            </div>

            {{-- Phone Frame --}}
            <div style="width: 100%; max-width: 310px; background: #0f172a; border-radius: 36px; padding: 10px; box-shadow: 0 16px 40px -10px rgba(15, 23, 42, 0.4); border: 3px solid #1e293b;">
                <div style="background: #efeae2; border-radius: 28px; overflow: hidden; display: flex; flex-direction: column; min-height: 440px;">
                    {{-- WhatsApp Header --}}
                    <div style="background: #075e54; color: #ffffff; padding: 12px 14px; display: flex; align-items: center; gap: 10px;">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: #f59e0b; color: #020617; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 13px;">
                            🕉️
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 4px;">
                                <span style="font-weight: 700; font-size: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Online Puja</span>
                                <span style="color: #6ee7b7; font-size: 10px;">✓</span>
                            </div>
                            <p style="font-size: 9.5px; color: #a7f3d0; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Official Verified Business</p>
                        </div>
                    </div>

                    {{-- Chat Bubble Area --}}
                    <div style="padding: 12px; flex: 1; display: flex; flex-direction: column; justify-content: flex-end;">
                        <div style="background: #ffffff; border-radius: 12px; border-top-left-radius: 0; padding: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); font-size: 12px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; gap: 8px;">
                            {{-- Media Preview Thumbnail --}}
                            <div id="preview-media-container" style="border-radius: 8px; overflow: hidden; background: #f1f5f9; aspect-ratio: 16/9; display: none; align-items: center; justify-content: center; position: relative;">
                                <img id="preview-media-img" src="" style="width: 100%; height: 100%; object-fit: cover; display: none;" alt="Campaign Preview">
                                <div id="preview-video-badge" style="position: absolute; inset: 0; background: rgba(0,0,0,0.4); display: none; align-items: center; justify-content: center;">
                                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #ffffff; color: #0f172a; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12px;">▶</div>
                                </div>
                            </div>

                            <p style="font-weight: 800; color: #0f172a; font-size: 12px; margin: 0; line-height: 1.3;" id="preview-headline">
                                🕉️ Special Rudrabhishek Puja Blessings from Varanasi
                            </p>
                            
                            <p style="color: #334155; white-space: pre-line; line-height: 1.45; font-size: 11px; margin: 0;" id="preview-body">
                                Pranam Devotee!

Participate in the auspicious Vedic ritual performed by certified Varanasi pandits with your personal sankalp and gotra.
                            </p>

                            <div style="padding-top: 6px; border-top: 1px solid #f1f5f9;">
                                <div style="background: #ecfdf5; color: #065f46; text-align: center; padding: 7px 10px; border-radius: 8px; font-weight: 700; font-size: 11px; border: 1px solid #a7f3d0;">
                                    <span id="preview-cta-text">Book Sacred Puja with Sankalp 🙏</span>
                                </div>
                            </div>

                            <div style="font-size: 9.5px; text-align: right; color: #94a3b8; font-weight: 600; margin-top: 2px;">
                                11:45 AM • <span style="color: #3b82f6;">✓✓</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <p style="font-size: 11.5px; color: #64748b; text-align: center; margin-top: 14px; margin-bottom: 0;">
                Rendered with high-contrast formatting, bold Unicode emojis & inline media.
            </p>
        </div>

    </div>

        </div>

    {{-- PANE 4: DEVOTEE LIFECYCLE AUTOMATED TRIGGERS --}}
    <div id="pane-triggers" class="mkt-pane" style="display: none;">
    {{-- SECTION: Devotee Lifecycle Automated Triggers --}}
    <div class="ai-box">
        <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; margin-bottom: 16px;">
            <div>
                <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0;">Automated Devotee Lifecycle Journeys</h3>
                <p style="font-size: 12px; color: #64748b; margin: 3px 0 0 0;">Always-on triggers that send personalized WhatsApp & Brevo email notifications</p>
            </div>
            <span class="ai-pill" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                4 Active Flows
            </span>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
            <div style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; background: #f8fafc;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span class="ai-step-badge" style="background: #059669;">1</span>
                    <span class="ai-pill" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-size: 10px;">Auto-Active</span>
                </div>
                <h4 style="font-weight: 800; color: #0f172a; font-size: 13px; margin: 10px 0 4px 0;">Welcome & ₹101 Sankalp</h4>
                <p style="font-size: 11.5px; color: #64748b; margin: 0; line-height: 1.4;">Triggers on user registration. Sends welcome email + WhatsApp ₹101 coupon.</p>
                <div style="margin-top: 10px; font-size: 11px; color: #059669; font-family: monospace; font-weight: 700;">Coupon: SANKALP101</div>
            </div>

            <div style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; background: #f8fafc;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span class="ai-step-badge" style="background: #2563eb;">2</span>
                    <span class="ai-pill" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 10px;">Auto-Active</span>
                </div>
                <h4 style="font-weight: 800; color: #0f172a; font-size: 13px; margin: 10px 0 4px 0;">Puja Booking Confirmation</h4>
                <p style="font-size: 11.5px; color: #64748b; margin: 0; line-height: 1.4;">Triggers immediately post-checkout. Sends Pandit details and muhurat timings.</p>
                <div style="margin-top: 10px; font-size: 11px; color: #2563eb; font-family: monospace; font-weight: 700;">WhatsApp + Brevo HTML</div>
            </div>

            <div style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; background: #f8fafc;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span class="ai-step-badge" style="background: #d97706;">3</span>
                    <span class="ai-pill" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a; font-size: 10px;">Auto-Active</span>
                </div>
                <h4 style="font-weight: 800; color: #0f172a; font-size: 13px; margin: 10px 0 4px 0;">Prasad Dispatch & Tracking</h4>
                <p style="font-size: 11.5px; color: #64748b; margin: 0; line-height: 1.4;">Triggers on packing in Varanasi. Sends India Post / courier tracking to WhatsApp.</p>
                <div style="margin-top: 10px; font-size: 11px; color: #d97706; font-family: monospace; font-weight: 700;">WhatsApp Tracking</div>
            </div>

            <div style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; background: #f8fafc;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span class="ai-step-badge" style="background: #9333ea;">4</span>
                    <span class="ai-pill" style="background: #faf5ff; color: #7e22ce; border: 1px solid #e9d5ff; font-size: 10px;">Auto-Active</span>
                </div>
                <h4 style="font-weight: 800; color: #0f172a; font-size: 13px; margin: 10px 0 4px 0;">Tithi & Muhurat Nudges</h4>
                <p style="font-size: 11.5px; color: #64748b; margin: 0; line-height: 1.4;">Panchang-driven broadcast before Ekadashi, Pradosh, and Shivratri with pujas.</p>
                <div style="margin-top: 10px; font-size: 11px; color: #9333ea; font-family: monospace; font-weight: 700;">Hindu Calendar Sync</div>
            </div>
        </div>
    </div>

</div>

    </div>

{{-- MODAL: WhatsApp QR Modal --}}
<div id="modal-wa-qr" style="position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.7); display: none; align-items: center; justify-content: center; padding: 16px;">
    <div style="background: #ffffff; border-radius: 18px; max-width: 380px; width: 100%; padding: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); border: 1px solid #e2e8f0; text-align: center;">
        <div style="width: 48px; height: 48px; border-radius: 14px; background: #ecfdf5; color: #059669; margin: 0 auto 12px auto; display: flex; align-items: center; justify-content: center; font-size: 22px;">
            <i data-lucide="qr-code" style="width: 24px; height: 24px;"></i>
        </div>
        <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">Scan WhatsApp QR Code</h3>
        <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">Open WhatsApp &gt; Linked Devices &gt; Link a Device</p>

        <div id="wa-qr-container" style="margin-top: 16px; padding: 14px; background: #f8fafc; border-radius: 14px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; min-height: 220px;">
            <div style="font-size: 12px; color: #94a3b8;">Fetching live QR code…</div>
        </div>

        <div style="margin-top: 16px; display: flex; align-items: center; gap: 8px;">
            <button type="button" onclick="fetchWaQr()" class="ai-btn-dark" style="flex: 1; font-size: 12px; padding: 9px 14px;">
                <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i> Refresh QR
            </button>
            <button type="button" onclick="closeWaQrModal()" class="ai-btn-gold" style="font-size: 12px; padding: 9px 18px;">
                Done
            </button>
        </div>
    </div>
</div>

{{-- MODAL: Schedule Custom Festival / Event Modal --}}
<div id="modal-schedule-event" style="position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.7); display: none; align-items: center; justify-content: center; padding: 16px;">
    <div style="background: #ffffff; border-radius: 18px; max-width: 460px; width: 100%; padding: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); border: 1px solid #e2e8f0;">
        <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 24px;">🕉️</span>
                <div>
                    <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0;">Schedule Sacred Event / Festival</h3>
                    <p style="font-size: 11.5px; color: #64748b; margin: 2px 0 0 0;">Add any upcoming Hindu tithi or puja to parallel engine</p>
                </div>
            </div>
            <button type="button" onclick="closeScheduleModal()" style="background: none; border: none; font-size: 18px; font-weight: bold; color: #94a3b8; cursor: pointer;">✕</button>
        </div>

        <form id="form-schedule-event" onsubmit="saveCustomFestival(event)" style="margin-top: 16px; display: flex; flex-direction: column; gap: 12px;">
            <div>
                <label style="display: block; font-size: 11.5px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Festival / Sacred Occasion Name *</label>
                <input type="text" id="sched-fest-name" required placeholder="e.g. Somvati Amavasya Pitru Shanti, Vaikuntha Ekadashi, Gita Jayanti" class="ai-input">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <label style="display: block; font-size: 11.5px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Event Date *</label>
                    <input type="date" id="sched-fest-date" required class="ai-input">
                </div>
                <div>
                    <label style="display: block; font-size: 11.5px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Deity / Spiritual Theme</label>
                    <input type="text" id="sched-fest-deity" placeholder="e.g. Lord Shiva, Maa Lakshmi" value="Lord Shiva & Pitrus" class="ai-input">
                </div>
            </div>

            <div style="padding: 10px 12px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; font-size: 11.5px; color: #92400e; line-height: 1.45;">
                <strong>⚡ Parallel Multi-Campaign Engine:</strong> Once scheduled, you can immediately generate Banners, SEO Blogs, and Video Reels, and the daily autopilot radar will sweep milestones in parallel.
            </div>

            <div style="margin-top: 6px; display: flex; align-items: center; justify-content: flex-end; gap: 8px; padding-top: 12px; border-top: 1px solid #f1f5f9;">
                <button type="button" onclick="closeScheduleModal()" class="ai-btn-dark" style="font-size: 12px; padding: 8px 14px;">
                    Cancel
                </button>
                <button type="submit" id="btn-save-custom-fest" class="ai-btn-gold" style="font-size: 12px; padding: 8px 18px;">
                    <span>➕</span> Add to Parallel Radar
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: B2B Pitch Preview Modal --}}
<div id="modal-b2b-pitch" style="position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.7); display: none; align-items: center; justify-content: center; padding: 16px;">
    <div style="background: #ffffff; border-radius: 20px; max-width: 620px; width: 100%; padding: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); border: 1px solid #e2e8f0; max-height: 85vh; overflow-y: auto;">
        <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 24px;">💼</span>
                <div>
                    <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0;" id="pitch-modal-partner">Dev Deepawali 2026 B2B Partnership Proposal</h3>
                    <p style="font-size: 11.5px; color: #64748b; margin: 2px 0 0 0;">25% Commission • Zero Logistics Vedic Fulfillment</p>
                </div>
            </div>
            <button type="button" onclick="closeB2bPitchModal()" style="background: none; border: none; font-size: 18px; font-weight: bold; color: #94a3b8; cursor: pointer;">✕</button>
        </div>

        <div style="margin-top: 16px; display: flex; flex-direction: column; gap: 14px;">
            <div>
                <label style="display: block; font-size: 11.5px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">WhatsApp Proposal Message</label>
                <div id="pitch-modal-whatsapp" style="padding: 12px; background: #f8fafc; border-radius: 12px; border: 1px solid #cbd5e1; font-family: monospace; font-size: 12px; color: #0f172a; white-space: pre-wrap; line-height: 1.5; max-height: 220px; overflow-y: auto;"></div>
            </div>

            <div>
                <label style="display: block; font-size: 11.5px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Partner Attribution URL (25% Commission Tracking)</label>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <input type="text" id="pitch-modal-url" readonly class="ai-input" style="font-family: monospace; background: #f1f5f9 !important;">
                    <button type="button" onclick="copyPitchUrl()" class="ai-btn-gold" style="padding: 9px 16px; font-size: 12px;">
                        Copy
                    </button>
                </div>
            </div>
        </div>

        <div style="margin-top: 18px; padding-top: 12px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end;">
            <button type="button" onclick="closeB2bPitchModal()" class="ai-btn-dark" style="font-size: 12px; padding: 8px 18px;">
                Done
            </button>
        </div>
    </div>
</div>

<script>
function updateLivePreview() {
    var headline = document.getElementById('camp-headline').value;
    var body = document.getElementById('camp-body').value;
    var ctaText = document.getElementById('camp-cta-text').value;
    var mediaUrl = document.getElementById('camp-media-url').value;
    var mediaType = document.getElementById('camp-media-type').value;

    document.getElementById('preview-headline').innerText = headline || 'Devotional Headline';
    document.getElementById('preview-body').innerText = body || '';
    document.getElementById('preview-cta-text').innerText = ctaText || 'View Details';

    var mediaBox = document.getElementById('preview-media-container');
    var mediaImg = document.getElementById('preview-media-img');
    var videoBadge = document.getElementById('preview-video-badge');

    if (mediaUrl) {
        mediaBox.style.display = 'flex';
        mediaImg.src = mediaUrl;
        mediaImg.style.display = 'block';
        if (mediaType === 'video') {
            videoBadge.style.display = 'flex';
        } else {
            videoBadge.style.display = 'none';
        }
    } else {
        mediaBox.style.display = 'none';
    }
}

function applyPujaPreset(sel) {
    var opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;

    var title = opt.getAttribute('data-title');
    var place = opt.getAttribute('data-place');
    var price = opt.getAttribute('data-price');
    var slug = opt.getAttribute('data-slug');

    document.getElementById('camp-headline').value = '🕉️ Sacred ' + title + (place ? ' in ' + place : '');
    document.getElementById('camp-subject').value = 'Divine Blessings: Book ' + title + ' with Personal Sankalp';
    document.getElementById('camp-body').value = 'Pranam Devotee!\n\nPerform sacred ' + title + ' with authentic Vedic rituals and personal sankalp. Receive divine prasad from Varanasi at your doorstep!\n\nDakshina: ₹' + price + ' with full Vedic samagri included.';
    document.getElementById('camp-cta-text').value = 'Book ' + title + ' 🙏';
    document.getElementById('camp-cta-url').value = 'https://onlinepuja.live/pujas/' + (slug || '');

    updateLivePreview();
    function switchMarketingTab(targetTab) {
    var tabs = ['broadcast', 'festival', 'b2b', 'triggers', 'tracking'];
    tabs.forEach(function(t) {
        var pane = document.getElementById('pane-' + t);
        var btn = document.getElementById('tab-btn-' + t);
        if (pane) pane.style.display = (t === targetTab) ? 'block' : 'none';
        if (btn) {
            if (t === targetTab) {
                btn.className = 'mkt-tab-btn active px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-slate-900 text-white shadow-2xs cursor-pointer';
                var icon = btn.querySelector('i');
                if (icon) icon.className.baseVal = icon.className.baseVal.replace('text-slate-400', 'text-amber-400');
            } else {
                btn.className = 'mkt-tab-btn px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-white text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200/80 cursor-pointer';
            }
        }
    });
    if (typeof lucide !== 'undefined') lucide.createIcons();
    window.scrollTo({ top: 180, behavior: 'smooth' });
}
}

function loadFestivalCampaign(festivalName) {
    fetch('{{ route("growth-os.action") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ action: 'preview_festival_campaign', festival: festivalName })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res && res.ok && res.package) {
            var pkg = res.package;
            document.getElementById('camp-headline').value = '🪔 ' + (pkg.branding ? pkg.branding.title : pkg.festival);
            document.getElementById('camp-subject').value = pkg.email_subject || ('Sacred Invitation: ' + pkg.festival);
            document.getElementById('camp-body').value = pkg.whatsapp_copy || '';
            document.getElementById('camp-cta-text').value = 'Book Sacred Sankalp 🙏';
            document.getElementById('camp-cta-url').value = pkg.booking_url || 'https://onlinepuja.live/pujas';
            document.getElementById('camp-channel').value = 'both';

            updateLivePreview();
    function switchMarketingTab(targetTab) {
    var tabs = ['broadcast', 'festival', 'b2b', 'triggers', 'tracking'];
    tabs.forEach(function(t) {
        var pane = document.getElementById('pane-' + t);
        var btn = document.getElementById('tab-btn-' + t);
        if (pane) pane.style.display = (t === targetTab) ? 'block' : 'none';
        if (btn) {
            if (t === targetTab) {
                btn.className = 'mkt-tab-btn active px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-slate-900 text-white shadow-2xs cursor-pointer';
                var icon = btn.querySelector('i');
                if (icon) icon.className.baseVal = icon.className.baseVal.replace('text-slate-400', 'text-amber-400');
            } else {
                btn.className = 'mkt-tab-btn px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-white text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200/80 cursor-pointer';
            }
        }
    });
    if (typeof lucide !== 'undefined') lucide.createIcons();
    window.scrollTo({ top: 180, behavior: 'smooth' });
}
            var comp = document.getElementById('form-broadcast');
            if (comp) comp.scrollIntoView({ behavior: 'smooth' });
        } else {
            alert('Failed to load campaign: ' + (res.message || 'Unknown error'));
        }
    })
    .catch(function(err) {
        alert('Network error: ' + err.message);
    });
}

function generateFestivalReel(festivalName) {
    var btn = document.getElementById('btn-gen-dev-reel');
    var origText = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span>⏳</span> Synthesizing 1080x1920 MP4 Reel (FFmpeg)...';
    }

    fetch('{{ route("growth-os.action") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ action: 'generate_festival_reel', festival: festivalName })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
        if (res && res.ok && res.video_url) {
            document.getElementById('camp-media-url').value = res.video_url;
            document.getElementById('camp-media-type').value = 'video';
            updateLivePreview();
    function switchMarketingTab(targetTab) {
    var tabs = ['broadcast', 'festival', 'b2b', 'triggers', 'tracking'];
    tabs.forEach(function(t) {
        var pane = document.getElementById('pane-' + t);
        var btn = document.getElementById('tab-btn-' + t);
        if (pane) pane.style.display = (t === targetTab) ? 'block' : 'none';
        if (btn) {
            if (t === targetTab) {
                btn.className = 'mkt-tab-btn active px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-slate-900 text-white shadow-2xs cursor-pointer';
                var icon = btn.querySelector('i');
                if (icon) icon.className.baseVal = icon.className.baseVal.replace('text-slate-400', 'text-amber-400');
            } else {
                btn.className = 'mkt-tab-btn px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-white text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200/80 cursor-pointer';
            }
        }
    });
    if (typeof lucide !== 'undefined') lucide.createIcons();
    window.scrollTo({ top: 180, behavior: 'smooth' });
}
            alert('🎉 Video Reel Synthesized Successfully!\n\nURL: ' + res.video_url);
        } else {
            alert('Video generation note: ' + (res.message || 'Check logs for status'));
        }
    })
    .catch(function(err) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
        alert('Error: ' + err.message);
    });
}

function orchestrateMasterCampaign(festivalName) {
    var btn = document.getElementById('btn-master-orchestrate');
    var origText = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span>⏳</span> Orchestrating Brain (Blog + Banners + Reel + Copy)...';
    }

    fetch('{{ route("growth-os.action") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ action: 'orchestrate_master_campaign', festival: festivalName })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
        if (res && res.ok) {
            var pkg = res.campaign_package;
            if (pkg) {
                document.getElementById('camp-headline').value = '🪔 ' + (pkg.branding ? pkg.branding.title : res.festival);
                document.getElementById('camp-subject').value = pkg.email_subject || ('Sacred Invitation: ' + res.festival);
                document.getElementById('camp-body').value = pkg.whatsapp_copy || '';
                document.getElementById('camp-cta-text').value = 'Book Sacred Sankalp 🙏';
                document.getElementById('camp-cta-url').value = pkg.booking_url || 'https://onlinepuja.live/pujas';
                document.getElementById('camp-media-url').value = pkg.video_reel_url || pkg.banner_landscape;
                document.getElementById('camp-media-type').value = pkg.video_reel_url ? 'video' : 'image';
                document.getElementById('camp-channel').value = 'both';
                updateLivePreview();
    function switchMarketingTab(targetTab) {
    var tabs = ['broadcast', 'festival', 'b2b', 'triggers', 'tracking'];
    tabs.forEach(function(t) {
        var pane = document.getElementById('pane-' + t);
        var btn = document.getElementById('tab-btn-' + t);
        if (pane) pane.style.display = (t === targetTab) ? 'block' : 'none';
        if (btn) {
            if (t === targetTab) {
                btn.className = 'mkt-tab-btn active px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-slate-900 text-white shadow-2xs cursor-pointer';
                var icon = btn.querySelector('i');
                if (icon) icon.className.baseVal = icon.className.baseVal.replace('text-slate-400', 'text-amber-400');
            } else {
                btn.className = 'mkt-tab-btn px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-white text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200/80 cursor-pointer';
            }
        }
    });
    if (typeof lucide !== 'undefined') lucide.createIcons();
    window.scrollTo({ top: 180, behavior: 'smooth' });
}
            }

            var dock = document.getElementById('master-asset-dock');
            if (dock) {
                dock.style.display = 'block';
                if (res.blog && res.blog.blog_url) {
                    document.getElementById('dock-blog-link').href = res.blog.blog_url;
                }
                if (res.banners && res.banners.landscape) {
                    document.getElementById('dock-banner-link').href = res.banners.landscape.banner_url;
                }
                if (res.reel && res.reel.video_url) {
                    document.getElementById('dock-reel-link').href = res.reel.video_url;
                }
            }

            alert('🎉 Autonomous Master Campaign Generated Successfully!\n\n• SEO Devotional Blog: Live on website\n• 1200x630 & 1080x1080 Banners: Ready\n• 1080x1920 MP4 Video Reel: Ready\n• Omnichannel WhatsApp & Email: Loaded in composer below');
        } else {
            alert('Campaign orchestration note: ' + (res.message || 'Check logs for details'));
        }
    })
    .catch(function(err) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
        alert('Error: ' + err.message);
    });
}

function generateFestivalBanners(festivalName) {
    var btn = document.getElementById('btn-gen-banners');
    var origText = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span>🎨</span> Rendering...';
    }

    fetch('{{ route("growth-os.action") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ action: 'generate_festival_banners', festival: festivalName })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
        if (res && res.ok && res.landscape) {
            document.getElementById('camp-media-url').value = res.landscape;
            document.getElementById('camp-media-type').value = 'image';
            updateLivePreview();
    function switchMarketingTab(targetTab) {
    var tabs = ['broadcast', 'festival', 'b2b', 'triggers', 'tracking'];
    tabs.forEach(function(t) {
        var pane = document.getElementById('pane-' + t);
        var btn = document.getElementById('tab-btn-' + t);
        if (pane) pane.style.display = (t === targetTab) ? 'block' : 'none';
        if (btn) {
            if (t === targetTab) {
                btn.className = 'mkt-tab-btn active px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-slate-900 text-white shadow-2xs cursor-pointer';
                var icon = btn.querySelector('i');
                if (icon) icon.className.baseVal = icon.className.baseVal.replace('text-slate-400', 'text-amber-400');
            } else {
                btn.className = 'mkt-tab-btn px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-white text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200/80 cursor-pointer';
            }
        }
    });
    if (typeof lucide !== 'undefined') lucide.createIcons();
    window.scrollTo({ top: 180, behavior: 'smooth' });
}
            alert('🎉 Landscape & Square Banners Generated!\n\nLandscape: ' + res.landscape + '\nSquare: ' + res.square);
        } else {
            alert('Banner generation note: ' + (res.message || 'Error'));
        }
    })
    .catch(function(err) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
        alert('Error: ' + err.message);
    });
}

function publishFestivalBlog(festivalName) {
    var btn = document.getElementById('btn-publish-blog');
    var origText = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span>✍️</span> Publishing...';
    }

    fetch('{{ route("growth-os.action") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ action: 'publish_festival_blog', festival: festivalName })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
        if (res && res.ok && res.blog_url) {
            alert('🎉 Devotional SEO Blog Published Live!\n\nURL: ' + res.blog_url);
            window.open(res.blog_url, '_blank');
        } else {
            alert('Blog note: ' + (res.message || 'Error'));
        }
    })
    .catch(function(err) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
        alert('Error: ' + err.message);
    });
}

function quickLaunchFestivalCampaign(festivalName) {
    if (!confirm('Broadcast campaign for ' + festivalName + ' to all reachable devotees across WhatsApp, Brevo Email, and Web/App Push?')) {
        return;
    }

    var btn = document.getElementById('btn-quick-launch-dev');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span>🚀</span> Broadcasting...';
    }

    fetch('{{ route("growth-os.action") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            action: 'dispatch_festival_campaign',
            festival: festivalName,
            channels: ['whatsapp', 'email', 'push']
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="zap" style="width: 14px; height: 14px;"></i> Broadcast (WhatsApp + Email + Push)';
        }
        if (res && res.ok) {
            alert('🎉 Multi-Channel Broadcast Complete!\n\nWhatsApp: ' + res.results.whatsapp.sent + '\nEmail: ' + res.results.email.sent + '\nPush: ' + res.results.push.sent);
        } else {
            alert('Broadcast note: ' + (res.message || 'Check logs for details'));
        }
    })
    .catch(function(err) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="zap" style="width: 14px; height: 14px;"></i> Broadcast (WhatsApp + Email + Push)';
        }
        alert('Error: ' + err.message);
    });
}

function sendCampaign(mode) {
    var segment = document.getElementById('camp-segment').value;
    var channel = document.getElementById('camp-channel').value;
    var headline = document.getElementById('camp-headline').value;
    var subject = document.getElementById('camp-subject').value;
    var body = document.getElementById('camp-body').value;
    var mediaUrl = document.getElementById('camp-media-url').value;
    var mediaType = document.getElementById('camp-media-type').value;
    var ctaText = document.getElementById('camp-cta-text').value;
    var ctaUrl = document.getElementById('camp-cta-url').value;

    if (!headline || !body) {
        alert('Please fill out the Headline and Message Body.');
        return;
    }

    if (mode === 'live' && !confirm('Are you sure you want to blast this campaign to ' + segment + ' via ' + channel + '?')) {
        return;
    }

    var btn = event ? event.target : null;
    var origText = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span>⏳</span> Dispatching...';
    }

    fetch('{{ route("growth-os.action") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            action: 'dispatch_custom_broadcast',
            mode: mode,
            segment: segment,
            channel: channel,
            headline: headline,
            subject: subject,
            body: body,
            media_url: mediaUrl,
            media_type: mediaType,
            cta_text: ctaText,
            cta_url: ctaUrl
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
        if (res && res.ok) {
            alert('🎉 ' + res.message);
        } else {
            alert('Dispatch failed: ' + (res.message || res.error || 'Error'));
        }
    })
    .catch(function(err) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
        alert('Network Error: ' + err.message);
    });
}

function openScheduleModal() {
    var modal = document.getElementById('modal-schedule-event');
    if (modal) {
        modal.style.display = 'flex';
        var dateInput = document.getElementById('sched-fest-date');
        if (dateInput && !dateInput.value) {
            var d = new Date();
            d.setDate(d.getDate() + 7);
            dateInput.value = d.toISOString().split('T')[0];
        }
    }
}

function closeScheduleModal() {
    var modal = document.getElementById('modal-schedule-event');
    if (modal) modal.style.display = 'none';
}

function saveCustomFestival(e) {
    if (e && e.preventDefault) e.preventDefault();

    var name = document.getElementById('sched-fest-name').value.trim();
    var date = document.getElementById('sched-fest-date').value;
    var deity = document.getElementById('sched-fest-deity').value.trim() || 'Vedic Devata';

    if (!name || !date) {
        alert('Please provide Festival Name and Date.');
        return;
    }

    var btn = document.getElementById('btn-save-custom-fest');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span>⏳</span> Scheduling...';
    }

    fetch('{{ route("growth-os.action") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            action: 'store_custom_festival',
            name: name,
            date: date,
            deity: deity
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<span>➕</span> Add to Parallel Radar';
        }
        if (res && res.ok) {
            alert('🎉 ' + res.message);
            closeScheduleModal();
            location.reload();
        } else {
            alert('Error: ' + (res.error || res.message || 'Failed to schedule festival'));
        }
    })
    .catch(function(err) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<span>➕</span> Add to Parallel Radar';
        }
        alert('Network Error: ' + err.message);
    });
}

function switchB2bTab(tab) {
    var csvTab = document.getElementById('b2b-tab-csv');
    var radarTab = document.getElementById('b2b-tab-radar');
    var btnCsv = document.getElementById('tab-btn-csv');
    var btnRadar = document.getElementById('tab-btn-radar');

    if (tab === 'csv') {
        csvTab.style.display = 'flex';
        radarTab.style.display = 'none';
        btnCsv.className = 'ai-btn-gold';
        btnRadar.className = 'ai-btn-dark';
    } else {
        csvTab.style.display = 'none';
        radarTab.style.display = 'flex';
        btnCsv.className = 'ai-btn-dark';
        btnRadar.className = 'ai-btn-gold';
    }
}

function seedB2bDirectory() {
    if (!confirm('Seed premier curated directory of national travel agents, tour operators, and spiritual Kendra leads across Mumbai, Surat, Ahmedabad, and Delhi?')) {
        return;
    }
    fetch('{{ route("growth-os.action") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ action: 'seed_b2b_directory' })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        alert(res.message || 'B2B Directory Seeded Successfully');
        location.reload();
    })
    .catch(function(err) {
        alert('Network error: ' + err.message);
    });
}

function importB2bCsv() {
    var city = document.getElementById('b2b-csv-city').value.trim();
    var category = document.getElementById('b2b-csv-category').value;
    var fileInput = document.getElementById('b2b-csv-file');
    var rawText = document.getElementById('b2b-csv-text').value.trim();

    if (!rawText && (!fileInput.files || fileInput.files.length === 0)) {
        alert('Please choose a CSV file or paste raw CSV text.');
        return;
    }

    var btn = document.getElementById('btn-import-csv');
    btn.disabled = true;
    btn.innerHTML = '<span>⏳</span> Ingesting and Validating Numbers...';

    if (fileInput.files && fileInput.files.length > 0) {
        var reader = new FileReader();
        reader.onload = function(e) {
            submitCsvPayload(e.target.result, city, category, btn);
        };
        reader.readAsText(fileInput.files[0]);
    } else {
        submitCsvPayload(rawText, city, category, btn);
    }
}

function submitCsvPayload(csvContent, city, category, btn) {
    fetch('{{ route("growth-os.action") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            action: 'import_b2b_csv',
            csv_content: csvContent,
            default_city: city,
            category: category
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="upload-cloud" style="width: 16px; height: 16px;"></i> Ingest & Validate Indian Numbers (+91)';
        if (res && res.ok) {
            alert('🎉 ' + res.message);
            location.reload();
        } else {
            alert('Import error: ' + (res.message || res.error || 'Failed'));
        }
    })
    .catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="upload-cloud" style="width: 16px; height: 16px;"></i> Ingest & Validate Indian Numbers (+91)';
        alert('Network error: ' + err.message);
    });
}

function runGoogleMapsRadar() {
    var city = document.getElementById('b2b-radar-city').value.trim();
    var category = document.getElementById('b2b-radar-category').value;

    if (!city) {
        alert('Please enter a target city.');
        return;
    }

    var btn = document.getElementById('btn-radar-search');
    btn.disabled = true;
    btn.innerHTML = '<span>📡</span> Scanning Google Places Radar in ' + city + '...';

    fetch('{{ route("growth-os.action") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            action: 'radar_b2b_search',
            city: city,
            category: category
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="radar" style="width: 16px; height: 16px;"></i> Scan Google Maps Places & Ingest';
        if (res && res.ok) {
            alert('🎉 ' + res.message);
            location.reload();
        } else {
            alert('Radar search note: ' + (res.message || res.error || 'Done'));
        }
    })
    .catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="radar" style="width: 16px; height: 16px;"></i> Scan Google Maps Places & Ingest';
        alert('Network error: ' + err.message);
    });
}

function previewB2bPitch(leadId) {
    fetch('{{ route("growth-os.action") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            action: 'preview_b2b_pitch',
            lead_id: leadId || null
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res && res.ok && res.pitch) {
            document.getElementById('pitch-modal-partner').innerText = 'Proposal for ' + (res.pitch.partner_name || 'B2B Partner');
            document.getElementById('pitch-modal-whatsapp').innerText = res.pitch.whatsapp_copy;
            document.getElementById('pitch-modal-url').value = res.pitch.booking_url;
            document.getElementById('modal-b2b-pitch').style.display = 'flex';
        } else {
            alert('Could not generate pitch: ' + (res.error || 'Unknown'));
        }
    })
    .catch(function(err) {
        alert('Network error: ' + err.message);
    });
}

function closeB2bPitchModal() {
    document.getElementById('modal-b2b-pitch').style.display = 'none';
}

function copyPitchUrl() {
    var inp = document.getElementById('pitch-modal-url');
    inp.select();
    document.execCommand('copy');
    alert('Copied partner attribution link to clipboard!');
}

function dispatchB2bCampaign() {
    var city = document.getElementById('b2b-campaign-city').value;
    var cityLabel = city === 'all' ? 'All National Partners' : city;

    if (!confirm('Broadcast B2B Dev Deepawali 2026 Campaign (WhatsApp Reel + Proposal Email) to: ' + cityLabel + '?')) {
        return;
    }

    var btn = document.getElementById('btn-dispatch-b2b');
    btn.disabled = true;
    btn.innerHTML = '<span>🚀</span> Broadcasting B2B Proposals...';

    fetch('{{ route("growth-os.action") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            action: 'dispatch_b2b_campaign',
            city: city,
            channels: ['whatsapp', 'email']
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="send" style="width: 15px; height: 15px;"></i> Launch B2B Dev Deepawali Campaign';
        if (res && res.ok) {
            alert('🎉 ' + res.message + '\n\nWhatsApp Sent: ' + (res.results ? res.results.whatsapp.sent : 0) + '\nEmail Sent: ' + (res.results ? res.results.email.sent : 0));
            location.reload();
        } else {
            alert('Dispatch failed: ' + (res.message || res.error || 'Error'));
        }
    })
    .catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="send" style="width: 15px; height: 15px;"></i> Launch B2B Dev Deepawali Campaign';
        alert('Network Error: ' + err.message);
    });
}

function openWaQrModal() {
    document.getElementById('modal-wa-qr').style.display = 'flex';
    fetchWaQr();
}

function closeWaQrModal() {
    document.getElementById('modal-wa-qr').style.display = 'none';
}

function fetchWaQr() {
    var box = document.getElementById('wa-qr-container');
    box.innerHTML = '<div style="font-size: 12px; color: #94a3b8;">Requesting QR from Evolution API…</div>';

    fetch('{{ route("growth-os.action") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ action: 'get_wa_qr' })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res && res.base64) {
            var html = '<img src="' + res.base64 + '" style="width: 192px; height: 192px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin: 0 auto;" alt="WhatsApp QR Code">';
            if (res.pairingCode) {
                html += '<div style="margin-top: 12px; padding: 10px; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 10px; text-align: center;"><span style="font-size: 10px; color: #94a3b8; display: block; text-transform: uppercase; font-weight: 700;">Or use Pairing Code in WhatsApp:</span><span style="font-size: 16px; font-family: monospace; font-weight: 800; color: #fbbf24; letter-spacing: 0.1em;">' + res.pairingCode + '</span></div>';
            }
            box.innerHTML = html;
        } else if (res && res.pairingCode) {
            box.innerHTML = '<div style="padding: 16px; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 12px; text-align: center;"><span style="font-size: 12px; color: #0f172a; display: block; margin-bottom: 4px; font-weight: 700;">WhatsApp Pairing Code:</span><span style="font-size: 24px; font-family: monospace; font-weight: 900; color: #d97706; letter-spacing: 0.15em;">' + res.pairingCode + '</span><p style="font-size: 11px; color: #64748b; margin-top: 8px;">Enter this in WhatsApp &gt; Linked Devices &gt; Link with phone number</p></div>';
        } else if (res && (res.code || res.state === 'open')) {
            box.innerHTML = '<div style="padding: 12px; background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; font-size: 12px; border-radius: 10px; font-weight: 700; text-align: center;">Instance is already paired and active!</div>';
        } else {
            box.innerHTML = '<div style="font-size: 12px; color: #e11d48; font-weight: 700;">' + (res.error || 'Failed to fetch QR') + '</div>';
        }
    })
    .catch(function(err) {
        box.innerHTML = '<div style="font-size: 12px; color: #e11d48;">Error: ' + err.message + '</div>';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    updateLivePreview();
    function switchMarketingTab(targetTab) {
    var tabs = ['broadcast', 'festival', 'b2b', 'triggers', 'tracking'];
    tabs.forEach(function(t) {
        var pane = document.getElementById('pane-' + t);
        var btn = document.getElementById('tab-btn-' + t);
        if (pane) pane.style.display = (t === targetTab) ? 'block' : 'none';
        if (btn) {
            if (t === targetTab) {
                btn.className = 'mkt-tab-btn active px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-slate-900 text-white shadow-2xs cursor-pointer';
                var icon = btn.querySelector('i');
                if (icon) icon.className.baseVal = icon.className.baseVal.replace('text-slate-400', 'text-amber-400');
            } else {
                btn.className = 'mkt-tab-btn px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-white text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200/80 cursor-pointer';
            }
        }
    });
    if (typeof lucide !== 'undefined') lucide.createIcons();
    window.scrollTo({ top: 180, behavior: 'smooth' });
}
});
</script>
<script>
window.switchMarketingTab = function(targetTab) {
    var tabs = ['broadcast', 'festival', 'b2b', 'triggers', 'tracking'];
    tabs.forEach(function(t) {
        var pane = document.getElementById('pane-' + t);
        var btn = document.getElementById('tab-btn-' + t);
        if (pane) pane.style.display = (t === targetTab) ? 'block' : 'none';
        if (btn) {
            if (t === targetTab) {
                btn.className = 'mkt-tab-btn active px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-slate-900 text-white shadow-2xs cursor-pointer';
            } else {
                btn.className = 'mkt-tab-btn px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-white text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200/80 cursor-pointer';
            }
        }
    });
    if (typeof lucide !== 'undefined') lucide.createIcons();
    window.scrollTo({ top: 180, behavior: 'smooth' });
};

window.saveTrackingSettings = function(e) {
    if (e && e.preventDefault) e.preventDefault();
    var btn = document.getElementById('btn-save-tracking');
    var oldText = btn.innerHTML;
    btn.innerHTML = 'Saving...';
    btn.disabled = true;

    var payload = {
        action: 'save_tracking_settings',
        meta_pixel_id: document.getElementById('track-meta-pixel-id').value,
        meta_capi_token: document.getElementById('track-meta-capi-token').value,
        social_webhook_url: document.getElementById('track-social-webhook-url').value,
        ga4_measurement_id: document.getElementById('track-ga4-id').value,
        gtm_container_id: document.getElementById('track-gtm-id').value,
        google_ads_id: document.getElementById('track-gads-id').value,
        google_ads_label: document.getElementById('track-gads-label').value
    };

    var brevoVal = document.getElementById('track-brevo-api-key').value;
    if (brevoVal && brevoVal.trim().length > 0) {
        payload.brevo_api_key = brevoVal.trim();
    }

    fetch('{{ route("growth-os.action") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload)
    })
    .then(function(r){ return r.json(); })
    .then(function(res){
        btn.innerHTML = oldText;
        btn.disabled = false;
        if (res && res.ok) {
            toastr.success(res.message || 'Ad tracking & Pixel settings updated site-wide!');
        } else {
            toastr.error((res && res.error) || 'Failed to save settings.');
        }
    })
    .catch(function(err){
        btn.innerHTML = oldText;
        btn.disabled = false;
        toastr.error('Network error: ' + err.message);
    });
};

window.testBrevoKeyFromPanel = function() {
    var key = document.getElementById('track-brevo-api-key').value;
    var resBox = document.getElementById('brevo-test-result');
    var btn = document.getElementById('btn-test-brevo');
    if (!key || key.trim() === '') {
        alert('Please enter a Brevo API key to test.');
        return;
    }
    btn.disabled = true;
    btn.innerText = 'Testing...';
    resBox.innerHTML = '<span style="color: #64748b;">Validating key with Brevo API...</span>';

    fetch('{{ route("growth-os.action") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ action: 'test_brevo_key', brevo_api_key: key.trim() })
    })
    .then(function(r){ return r.json(); })
    .then(function(res){
        btn.disabled = false;
        btn.innerText = '⚡ Test & Save';
        if (res && res.ok) {
            resBox.innerHTML = '<span style="color: #16a34a;">✓ ' + res.message + '</span>';
            toastr.success(res.message);
            var badge = document.getElementById('brevo-live-badge');
            if (badge) {
                badge.innerText = 'Connected';
                badge.style.background = 'rgba(59, 130, 246, 0.2)';
                badge.style.color = '#93c5fd';
            }
        } else {
            resBox.innerHTML = '<span style="color: #dc2626;">✕ ' + (res.error || 'Invalid API Key') + '</span>';
            toastr.error(res.error || 'Brevo connection failed');
        }
    })
    .catch(function(err){
        btn.disabled = false;
        btn.innerText = '⚡ Test & Save';
        resBox.innerHTML = '<span style="color: #dc2626;">Error: ' + err.message + '</span>';
    });
};

window.testSocialWebhookFromPanel = function() {
    var url = document.getElementById('track-social-webhook-url').value;
    if (!url || url.trim() === '') {
        alert('Please enter a social webhook URL.');
        return;
    }
    toastr.info('Sending test ping to webhook...');
    fetch('{{ route("growth-os.action") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ action: 'test_social_webhook', webhook_url: url.trim() })
    })
    .then(function(r){ return r.json(); })
    .then(function(res){
        if (res && res.ok) {
            toastr.success(res.message || 'Webhook ping received 200 OK!');
        } else {
            toastr.error(res.error || 'Webhook test failed');
        }
    })
    .catch(function(err){
        toastr.error('Ping error: ' + err.message);
    });
};
</script>
@endsection
