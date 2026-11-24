@extends('pages.growth-os.layout')

@section('growth_content')
@php
    $creds = $creds ?? [];
    $omniTextModels = $omniTextModels ?? collect();
    $omniImageModels = $omniImageModels ?? collect();
    $omniVideoModels = $omniVideoModels ?? collect();
    $comfyImageModels = $comfyImageModels ?? collect();
    $comfyVideoModels = $comfyVideoModels ?? collect();
    $omniTotal = $omniTotal ?? 0;
    $lastSyncedAt = $lastSyncedAt ?? null;
    $totalModelsCount = $totalModelsCount ?? 0;
@endphp

<style>
/* ========================================================
   AI ENGINE COCKPIT — HIGH CONTRAST & ZERO-CLUTTER STYLES
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

/* Hero Top Banner */
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
    padding: 10px 20px !important;
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
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.28) !important;
    transform: translateY(-1px) !important;
    color: #ffffff !important;
}

/* Engine Test Buttons */
.ai-btn-test {
    width: 100% !important;
    padding: 9px 14px !important;
    font-size: 12px !important;
    font-weight: 700 !important;
    border-radius: 9px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 6px !important;
    cursor: pointer !important;
    transition: all 0.15s ease !important;
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

/* Cascade Row Items */
.ai-cascade-item {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 8px 12px !important;
    border-radius: 9px !important;
    margin-bottom: 7px !important;
    font-size: 12px !important;
    transition: all 0.15s ease !important;
}
.ai-cascade-primary-text {
    background: #ecfdf5 !important;
    border: 1px solid #a7f3d0 !important;
}
.ai-cascade-primary-image {
    background: #fffbeb !important;
    border: 1px solid #fde68a !important;
}
.ai-cascade-primary-video {
    background: #fff1f2 !important;
    border: 1px solid #fecdd3 !important;
}
.ai-cascade-fallback {
    background: #f8fafc !important;
    border: 1px solid #e2e8f0 !important;
}
.ai-cascade-fallback:hover {
    background: #f1f5f9 !important;
    border-color: #cbd5e1 !important;
}

/* Step Number Badges */
.ai-step-badge {
    width: 20px !important;
    height: 20px !important;
    border-radius: 50% !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 10px !important;
    font-weight: 800 !important;
    color: #ffffff !important;
    flex-shrink: 0 !important;
}

/* Badges */
.ai-pill {
    display: inline-flex !important;
    align-items: center !important;
    gap: 4px !important;
    padding: 3px 8px !important;
    border-radius: 20px !important;
    font-size: 10.5px !important;
    font-weight: 700 !important;
    line-height: 1 !important;
}

/* Form Inputs and Selects */
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

/* Lab Mode Pills */
.ai-lab-tab {
    padding: 7px 16px !important;
    border-radius: 8px !important;
    font-size: 12px !important;
    font-weight: 700 !important;
    cursor: pointer !important;
    border: 1px solid transparent !important;
    transition: all 0.15s ease !important;
    background: transparent !important;
    color: #475569 !important;
}
.ai-lab-tab.active {
    background: #0f172a !important;
    color: #ffffff !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.18) !important;
}
.ai-lab-tab:not(.active):hover {
    background: #e2e8f0 !important;
    color: #0f172a !important;
}

/* Quick Suggestion Chips */
.ai-chip {
    background: #f1f5f9 !important;
    color: #334155 !important;
    border: 1px solid #e2e8f0 !important;
    padding: 5px 10px !important;
    border-radius: 7px !important;
    font-size: 11px !important;
    font-weight: 600 !important;
    cursor: pointer !important;
    transition: all 0.15s ease !important;
}
.ai-chip:hover {
    background: #fef3c7 !important;
    border-color: #fde68a !important;
    color: #92400e !important;
}

/* Prevent Brain AI floating bubble from overlapping action buttons */
#brain-chat-floating, #brain-toggle-btn, .brain-bubble, .brain-launcher {
    bottom: 85px !important;
    right: 18px !important;
    z-index: 45 !important;
}
@media (max-width: 768px) {
    .ai-scope {
        padding-bottom: 120px !important;
    }
}
</style>

<div class="ai-scope">

    {{-- TOP: OmniRoute Live Model Sync & Gateway Control Strip --}}
    <div class="ai-hero-card">
        <div style="display: flex; align-items: center; gap: 14px;">
            <span style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.3); color: #fbbf24; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold; flex-shrink: 0;">
                ⚡
            </span>
            <div>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <h3 style="font-size: 16px; font-weight: 800; color: #ffffff; letter-spacing: -0.01em; margin: 0;">OmniRoute AI Gateway</h3>
                    <span class="ai-pill" style="background: rgba(16, 185, 129, 0.2); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.35);">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #34d399; display: inline-block;"></span> Primary Router Active
                    </span>
                    <span class="ai-pill" style="background: #1e293b; color: #cbd5e1; border: 1px solid #334155; font-family: monospace;">
                        ai.vmstudio.digital
                    </span>
                </div>
                <p style="font-size: 12px; color: #94a3b8; margin-top: 4px; margin-bottom: 0; line-height: 1.4;">
                    <span>Primary Router for <strong style="color: #ffffff;">Free Text</strong>, <strong style="color: #fde68a;">Photorealistic Images</strong> & <strong style="color: #fecdd3;">9:16 Video Reels</strong>.</span>
                    <span style="margin: 0 4px; color: #475569;">·</span>
                    <span>Synced: <strong style="color: #f8fafc;" id="sync-last-time">{{ $lastSyncedAt ? \Carbon\Carbon::parse($lastSyncedAt)->diffForHumans() : 'Live Synced' }}</strong></span>
                </p>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 14px; justify-content: flex-end;">
            <div style="text-align: right;">
                <span style="font-size: 10px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; display: block; font-weight: 700;">Synced Catalogue</span>
                <span style="font-size: 16px; font-weight: 800; color: #fbbf24; font-family: monospace;" id="sync-total-badge">{{ number_format($omniTotal) }}</span>
                <span style="font-size: 11px; color: #94a3b8; font-weight: 600;">Models</span>
            </div>
            <button type="button" id="btn-sync-live-models" class="ai-btn-gold">
                <i data-lucide="refresh-cw" id="sync-btn-icon" style="color: #020617;"></i>
                <span id="sync-btn-text">Sync Live Models</span>
            </button>
        </div>
    </div>

    {{-- SECTION 1: The Three Multi-Modal AI Engines (Text, Image, Video) with Cascades --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap: 20px; margin-top: 20px;">

        {{-- 1. TEXT AI ENGINE --}}
        <div class="ai-box" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="width: 36px; height: 36px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: bold;">
                            💬
                        </span>
                        <div>
                            <h3 style="font-size: 14px; font-weight: 800; color: #0f172a; margin: 0;">Text AI Engine</h3>
                            <p style="font-size: 11px; color: #64748b; margin: 2px 0 0 0;">Jyotish, Kundli, Puja Guides, Blogs</p>
                        </div>
                    </div>
                    <span class="ai-pill" style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;">
                        100% Free Tier
                    </span>
                </div>

                {{-- Cascade Ladder --}}
                <div style="margin: 14px 0;">
                    <div class="ai-cascade-item ai-cascade-primary-text">
                        <div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span class="ai-step-badge" style="background: #059669;">1</span>
                                <strong style="color: #0f172a; font-size: 12px;">OmniRoute Gateway</strong>
                            </div>
                            <div style="margin-top: 4px; font-size: 11px; color: #065f46; padding-left: 28px;">
                                Synced: <span id="omni-text-count-badge" style="font-weight: 800; font-family: monospace;">{{ $omniTextModels->count() }} Models</span>
                            </div>
                        </div>
                        <span class="ai-pill" style="background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7;">Primary</span>
                    </div>

                    <div class="ai-cascade-item ai-cascade-fallback">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="ai-step-badge" style="background: #64748b;">2</span>
                            <span style="font-weight: 600; color: #334155;">Google Gemini</span>
                        </div>
                        <span style="font-size: 11px; color: #64748b; font-family: monospace;">1.5 / 2.0 Flash</span>
                    </div>

                    <div class="ai-cascade-item ai-cascade-fallback">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="ai-step-badge" style="background: #64748b;">3</span>
                            <span style="font-weight: 600; color: #334155;">OpenRouter</span>
                        </div>
                        <span style="font-size: 11px; color: #64748b; font-family: monospace;">DeepSeek R1 :free</span>
                    </div>

                    <div class="ai-cascade-item ai-cascade-fallback">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="ai-step-badge" style="background: #64748b;">4</span>
                            <span style="font-weight: 600; color: #334155;">Pollinations Text</span>
                        </div>
                        <span style="font-size: 11px; color: #64748b;">Unlimited Keyless</span>
                    </div>
                </div>
            </div>

            <button type="button" class="ai-btn-test ai-btn-test-blue select-test-engine" data-engine="text">
                <i data-lucide="play"></i> Test Text Engine
            </button>
        </div>

        {{-- 2. IMAGE AI ENGINE --}}
        <div class="ai-box" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="width: 36px; height: 36px; border-radius: 10px; background: #fffbeb; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: bold;">
                            📸
                        </span>
                        <div>
                            <h3 style="font-size: 14px; font-weight: 800; color: #0f172a; margin: 0;">Image AI Engine</h3>
                            <p style="font-size: 11px; color: #64748b; margin: 2px 0 0 0;">Photorealistic Vedic Art & Pujas</p>
                        </div>
                    </div>
                    <span class="ai-pill" style="background: #fffbeb; color: #92400e; border: 1px solid #fde68a;">
                        FLUX.1 Ultra
                    </span>
                </div>

                {{-- Cascade Ladder --}}
                <div style="margin: 14px 0;">
                    <div class="ai-cascade-item ai-cascade-primary-image">
                        <div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span class="ai-step-badge" style="background: #d97706;">1</span>
                                <strong style="color: #0f172a; font-size: 12px;">ComfyUI & OmniRoute FLUX</strong>
                            </div>
                            <div style="margin-top: 4px; font-size: 11px; color: #92400e; padding-left: 28px;">
                                comfyui/flux-dev · comfyui/sdxl · {{ $omniImageModels->count() }} Models
                            </div>
                        </div>
                        <span class="ai-pill" style="background: #fef3c7; color: #92400e; border: 1px solid #fcd34d;">Primary GPU</span>
                    </div>

                    <div class="ai-cascade-item ai-cascade-fallback">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="ai-step-badge" style="background: #64748b;">2</span>
                            <span style="font-weight: 600; color: #334155;">Pollinations AI</span>
                        </div>
                        <span style="font-size: 11px; color: #64748b; font-family: monospace;">FLUX Free Tier</span>
                    </div>

                    <div class="ai-cascade-item ai-cascade-fallback">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="ai-step-badge" style="background: #64748b;">3</span>
                            <span style="font-weight: 600; color: #334155;">Cloudflare Workers AI</span>
                        </div>
                        <span style="font-size: 11px; color: #64748b; font-family: monospace;">@cf/flux-1-schnell</span>
                    </div>

                    <div class="ai-cascade-item ai-cascade-fallback">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="ai-step-badge" style="background: #64748b;">4</span>
                            <span style="font-weight: 600; color: #334155;">Hugging Face API</span>
                        </div>
                        <span style="font-size: 11px; color: #64748b;">FLUX Serverless</span>
                    </div>
                </div>
            </div>

            <button type="button" class="ai-btn-test ai-btn-test-amber select-test-engine" data-engine="image">
                <i data-lucide="sparkles"></i> Test Image Engine
            </button>
        </div>

        {{-- 3. VIDEO AI ENGINE --}}
        <div class="ai-box" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="width: 36px; height: 36px; border-radius: 10px; background: #fff1f2; color: #e11d48; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: bold;">
                            🎥
                        </span>
                        <div>
                            <h3 style="font-size: 14px; font-weight: 800; color: #0f172a; margin: 0;">Video AI Engine</h3>
                            <p style="font-size: 11px; color: #64748b; margin: 2px 0 0 0;">9:16 Reels & Temple Motion</p>
                        </div>
                    </div>
                    <span class="ai-pill" style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;">
                        Zero-Failure Active
                    </span>
                </div>

                {{-- Cascade Ladder --}}
                <div style="margin: 14px 0;">
                    <div class="ai-cascade-item ai-cascade-primary-video">
                        <div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span class="ai-step-badge" style="background: #e11d48;">1</span>
                                <strong style="color: #0f172a; font-size: 12px;">ComfyUI AnimateDiff & SVD</strong>
                            </div>
                            <div style="margin-top: 4px; font-size: 11px; color: #9f1239; padding-left: 28px;">
                                comfy.vmstudio.digital · GPU Motion Studio
                            </div>
                        </div>
                        <span class="ai-pill" style="background: #ffe4e6; color: #9f1239; border: 1px solid #fda4af;">Primary GPU</span>
                    </div>

                    <div class="ai-cascade-item ai-cascade-fallback">
                        <div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span class="ai-step-badge" style="background: #64748b;">2</span>
                                <strong style="color: #0f172a; font-size: 12px;">Native FFmpeg 9:16 Reel</strong>
                            </div>
                            <div style="margin-top: 4px; font-size: 11px; color: #475569; padding-left: 28px;">
                                1080x1920 HQ · HarfBuzz Devanagari · Watermark
                            </div>
                        </div>
                        <span class="ai-pill" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;">$0 Free</span>
                    </div>

                    <div class="ai-cascade-item ai-cascade-fallback">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="ai-step-badge" style="background: #64748b;">3</span>
                            <span style="font-weight: 600; color: #334155;">OmniRoute Video</span>
                        </div>
                        <span style="font-size: 11px; color: #64748b; font-family: monospace;">Synced: {{ $omniVideoModels->count() }} Models</span>
                    </div>

                    <div class="ai-cascade-item ai-cascade-fallback">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="ai-step-badge" style="background: #64748b;">4</span>
                            <span style="font-weight: 600; color: #334155;">Hugging Face Video</span>
                        </div>
                        <span style="font-size: 11px; color: #64748b; font-family: monospace;">Wan2.1-T2V Free</span>
                    </div>
                </div>
            </div>

            <button type="button" class="ai-btn-test ai-btn-test-rose select-test-engine" data-engine="video">
                <i data-lucide="video"></i> Test Video Engine
            </button>
        </div>
    </div>

    {{-- SECTION 2: Unified Interactive Synthesis & Live Fallback Lab --}}
    <div class="ai-box" style="margin-top: 20px;">
        <div style="display: flex; flex-direction: column; gap: 12px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="width: 32px; height: 32px; border-radius: 8px; background: #0f172a; color: #fbbf24; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: bold;">
                        ⚡
                    </span>
                    <div>
                        <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0;">Multi-Modal Live Synthesis & Fallback Lab</h3>
                        <p style="font-size: 12px; color: #64748b; margin: 2px 0 0 0;">Test text reasoning, photorealistic Flux imagery, and video reels with live cascade telemetry.</p>
                    </div>
                </div>

                {{-- Mode Switcher Pills --}}
                <div style="display: flex; align-items: center; background: #f1f5f9; padding: 4px; border-radius: 10px; gap: 4px;">
                    <button type="button" id="tab-mode-text" class="ai-lab-tab active mode-tab-btn" data-mode="text">
                        💬 Text Chat
                    </button>
                    <button type="button" id="tab-mode-image" class="ai-lab-tab mode-tab-btn" data-mode="image">
                        📸 Flux Image
                    </button>
                    <button type="button" id="tab-mode-video" class="ai-lab-tab mode-tab-btn" data-mode="video">
                        🎥 Video Reel
                    </button>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-top: 18px;">
            {{-- Left: Prompt Input & Quick Topic Chips --}}
            <div style="display: flex; flex-direction: column; gap: 14px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #1e293b; margin-bottom: 6px;">
                        Vedic Ceremony / Prompt to Synthesize
                    </label>
                    <input type="text" id="multi-test-prompt" value="Maha Shivratri sacred Rudrabhishek with glowing brass diyas and bilva leaves" 
                           class="ai-input" 
                           placeholder="Enter topic or prompt…">
                </div>

                {{-- Quick Suggestion Pills --}}
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #64748b; margin-bottom: 5px;">
                        Quick Inspiration Topics:
                    </label>
                    <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                        <button type="button" class="ai-chip quick-lab-pill" data-val="Maha Shivratri sacred Rudrabhishek with glowing brass diyas and bilva leaves">🔱 Rudrabhishek</button>
                        <button type="button" class="ai-chip quick-lab-pill" data-val="Kashi Vishwanath Sandhya Aarti with multi-tiered brass deepams and incense dhoop">🪔 Kashi Aarti</button>
                        <button type="button" class="ai-chip quick-lab-pill" data-val="Satyanarayan Katha with banana leaves, fresh marigold flowers, and panchamrit offerings">🍌 Satyanarayan</button>
                        <button type="button" class="ai-chip quick-lab-pill" data-val="Explain the spiritual significance and planetary remedies of reciting Hanuman Chalisa daily">📖 Astrology Advice</button>
                    </div>
                </div>

                <div style="padding-top: 6px;">
                    <button type="button" id="btn-run-synthesis" class="ai-btn-dark" style="font-size: 13px; padding: 11px 22px;">
                        <i data-lucide="zap" style="color: #fbbf24;"></i> Synthesize Now
                    </button>
                </div>
            </div>

            {{-- Right: Live Output Viewport & Telemetry Badge --}}
            <div>
                <div style="position: relative; height: 260px; border-radius: 12px; background: #f8fafc; border: 1.5px solid #e2e8f0; padding: 16px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; overflow: hidden;">
                    {{-- Status / Telemetry Strip --}}
                    <div id="telemetry-badge" class="hidden" style="position: absolute; top: 10px; right: 10px; z-index: 10; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-family: monospace; font-weight: 700; background: #ffffff; border: 1px solid #cbd5e1; color: #065f46; box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 6px;">
                        <span style="width: 7px; height: 7px; border-radius: 50%; background: #10b981; display: inline-block;"></span>
                        <span id="telemetry-provider">OmniRoute</span> · <span id="telemetry-ms">180ms</span>
                    </div>

                    {{-- Idle Placeholder --}}
                    <div id="lab-placeholder" style="color: #64748b;">
                        <span style="font-size: 28px; display: block; margin-bottom: 6px;">✨</span>
                        <div style="font-size: 13px; font-weight: 700; color: #1e293b;">Synthesis Lab Idle</div>
                        <div style="font-size: 11px; color: #94a3b8; max-width: 260px; margin-top: 4px;">Select Text, Flux Image, or Video Reel on the left and click Synthesize.</div>
                    </div>

                    {{-- Loading State --}}
                    <div id="lab-loading" class="hidden" style="color: #1e293b; text-align: center;">
                        <div style="width: 32px; height: 32px; border: 3px solid #f59e0b; border-top-color: transparent; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 10px auto;"></div>
                        <div style="font-size: 13px; font-weight: 700;">Synthesizing with active fallback chain…</div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 3px;">Querying OmniRoute primary router first</div>
                    </div>

                    {{-- Result: Image --}}
                    <img id="lab-img-result" src="" alt="AI Output" class="hidden" style="width: 100%; height: 100%; object-fit: contain; border-radius: 8px;">

                    {{-- Result: Video --}}
                    <video id="lab-video-result" src="" controls autoplay loop muted playsinline class="hidden" style="width: 100%; height: 100%; object-fit: contain; border-radius: 8px;"></video>

                    {{-- Result: Text --}}
                    <div id="lab-text-result" class="hidden" style="width: 100%; height: 100%; text-align: left; overflow-y: auto; font-size: 12.5px; color: #0f172a; line-height: 1.6; background: #ffffff; padding: 14px; border-radius: 8px; border: 1px solid #e2e8f0; font-family: sans-serif;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION 3: Live Model Pickers & AI Vault Settings --}}
    <form method="POST" action="{{ route('growth-os.ai.vault') }}" id="vault" class="ai-box">
        @csrf

        <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; margin-bottom: 18px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i data-lucide="settings-2" style="color: #f59e0b;"></i>
                <h4 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0;">OmniRoute Live Model Configuration & Fallbacks</h4>
            </div>
            <span class="ai-pill" style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;">
                AES-256 Encrypted Vault
            </span>
        </div>

        {{-- Dynamic Model Selection for OmniRoute (Text, Image, Video) --}}
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                <span style="font-size: 12px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em;">
                    Default OmniRoute Model Selectors
                </span>
                <span style="font-size: 11px; color: #64748b;">
                    Live synced from <span style="font-family: monospace; color: #0f172a; font-weight: 700;">ai.vmstudio.digital</span>
                </span>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 14px;">
                {{-- Text Model --}}
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #1e293b; margin-bottom: 5px;">
                        Default Text Model <span style="color: #64748b; font-weight: normal;">({{ $omniTextModels->count() }} synced)</span>
                    </label>
                    <select name="model_omniroute" class="ai-select">
                        <option value="auto/gemini" {{ ($creds['model_omniroute'] ?? '') == 'auto/gemini' ? 'selected' : '' }}>
                            ⭐ auto/gemini (Google Gemini Free Tier Auto)
                        </option>
                        <option value="gemini/gemini-3.1-flash-lite" {{ ($creds['model_omniroute'] ?? '') == 'gemini/gemini-3.1-flash-lite' ? 'selected' : '' }}>
                            ⭐ gemini/gemini-3.1-flash-lite (Ultra-fast 1M token free)
                        </option>
                        <option value="auto/best-fast" {{ ($creds['model_omniroute'] ?? '') == 'auto/best-fast' ? 'selected' : '' }}>
                            ⭐ auto/best-fast (Fast Multi-Model Router)
                        </option>
                        <option value="auto/best-chat" {{ ($creds['model_omniroute'] ?? '') == 'auto/best-chat' ? 'selected' : '' }}>
                            ⭐ auto/best-chat (Best Quality Chat Router)
                        </option>
                        @foreach($omniTextModels as $m)
                            <option value="{{ $m->model_id }}" {{ ($creds['model_omniroute'] ?? '') == $m->model_id ? 'selected' : '' }}>
                                {{ $m->label ?: $m->model_id }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Image Model --}}
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #1e293b; margin-bottom: 5px;">
                        Default Image Model <span style="color: #64748b; font-weight: normal;">({{ $omniImageModels->count() }} synced)</span>
                    </label>
                    <select name="model_omniroute_image" class="ai-select">
                        <option value="gemini-3.1-flash-image" {{ ($creds['model_omniroute_image'] ?? '') == 'gemini-3.1-flash-image' ? 'selected' : '' }}>
                            ⭐ gemini-3.1-flash-image (Google Gemini 3.1 Flash Image — 100% Free Photorealistic)
                        </option>
                        <option value="nvidia/black-forest-labs/flux.1-dev" {{ ($creds['model_omniroute_image'] ?? '') == 'nvidia/black-forest-labs/flux.1-dev' ? 'selected' : '' }}>
                            ⭐ nvidia/black-forest-labs/flux.1-dev (NVIDIA Photorealistic FLUX.1)
                        </option>
                        @foreach($omniImageModels as $m)
                            <option value="{{ $m->model_id }}" {{ ($creds['model_omniroute_image'] ?? '') == $m->model_id ? 'selected' : '' }}>
                                {{ $m->label ?: $m->model_id }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Video Model --}}
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #1e293b; margin-bottom: 5px;">
                        Default Video Model <span style="color: #64748b; font-weight: normal;">({{ $omniVideoModels->count() }} synced)</span>
                    </label>
                    <select name="model_omniroute_video" class="ai-select">
                        <option value="ffmpeg-h264-1080x1920-hq" {{ ($creds['model_omniroute_video'] ?? '') == 'ffmpeg-h264-1080x1920-hq' || empty($creds['model_omniroute_video']) ? 'selected' : '' }}>
                            ⭐ Native FFmpeg 9:16 Sacred Reel (1080x1920 Broadcast Quality — $0 Free)
                        </option>
                        <option value="fal-ai/xai/grok-imagine-video/text-to-video" {{ ($creds['model_omniroute_video'] ?? '') == 'fal-ai/xai/grok-imagine-video/text-to-video' ? 'selected' : '' }}>
                            grok-imagine-video (Fal.ai High Motion)
                        </option>
                        @foreach($omniVideoModels as $m)
                            <option value="{{ $m->model_id }}" {{ ($creds['model_omniroute_video'] ?? '') == $m->model_id ? 'selected' : '' }}>
                                {{ $m->label ?: $m->model_id }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Credentials Grid --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 20px;">

            {{-- 1. OmniRoute Gateway --}}
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 13px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                        <span>⚡</span> OmniRoute Gateway
                    </span>
                    <span class="ai-pill" style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;">Primary Router</span>
                </div>
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">Gateway Host URL</label>
                    <input type="text" name="omniroute_url" value="{{ $creds['omniroute_url'] ?? 'https://ai.vmstudio.digital' }}" class="ai-input" style="font-family: monospace;">
                </div>
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                        <label style="font-size: 11px; font-weight: 700; color: #475569; margin: 0;">OmniRoute API Key</label>
                        <button type="button" class="toggle-secret-btn" data-target="omniroute_key" style="background: none; border: none; font-size: 11px; color: #2563eb; font-weight: 700; cursor: pointer;">Show</button>
                    </div>
                    <input type="password" id="omniroute_key" name="omniroute_key" value="{{ $creds['omniroute_key'] ?? '' }}" class="ai-input" style="font-family: monospace;" placeholder="sk-omni-…">
                </div>
            </div>

            {{-- 1.5. ComfyUI Dedicated GPU Studio Node --}}
            <div style="background: #f8fafc; border: 1.5px solid #fed7aa; border-radius: 12px; padding: 16px; display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 13px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                        <span>🎨</span> ComfyUI GPU Node & Workflows
                    </span>
                    <span class="ai-pill" style="background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5;">GPU Node Active</span>
                </div>
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">ComfyUI Host URL (Routed via OmniRoute)</label>
                    <input type="text" name="comfy_url" value="{{ $creds['comfy_url'] ?? 'https://comfy.vmstudio.digital' }}" class="ai-input" style="font-family: monospace;" placeholder="https://comfy.vmstudio.digital">
                </div>
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">ComfyUI Image Model / Checkpoint</label>
                    <select name="model_comfyui_image" class="ai-select">
                        <option value="comfyui/flux-dev" {{ ($creds['model_comfyui_image'] ?? 'comfyui/flux-dev') == 'comfyui/flux-dev' ? 'selected' : '' }}>
                            ⚡ comfyui/flux-dev (FLUX.1-Dev Photorealistic Sacred Art)
                        </option>
                        <option value="comfyui/sdxl" {{ ($creds['model_comfyui_image'] ?? '') == 'comfyui/sdxl' ? 'selected' : '' }}>
                            🖼️ comfyui/sdxl (Stable Diffusion XL Base 1.0)
                        </option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">ComfyUI Video Animation Model</label>
                    <select name="model_comfyui_video" class="ai-select">
                        <option value="comfyui/animatediff" {{ ($creds['model_comfyui_video'] ?? 'comfyui/animatediff') == 'comfyui/animatediff' ? 'selected' : '' }}>
                            🎬 comfyui/animatediff (AnimateDiff Motion LoRA 9:16)
                        </option>
                        <option value="comfyui/svd-xt" {{ ($creds['model_comfyui_video'] ?? '') == 'comfyui/svd-xt' ? 'selected' : '' }}>
                            🎥 comfyui/svd-xt (Stable Video Diffusion XT 25-frame)
                        </option>
                    </select>
                </div>
                <div style="display: flex; gap: 8px; margin-top: 4px;">
                    <button type="button" onclick="testComfyNode()" style="flex: 1; justify-content: center; background: #ea580c; color: white; border: none; border-radius: 8px; padding: 7px 12px; font-weight: 700; font-size: 11px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                        <i data-lucide="cpu" style="width: 14px; height: 14px;"></i> Ping ComfyUI Node
                    </button>
                    <a href="https://comfy.vmstudio.digital" target="_blank" style="justify-content: center; background: #e2e8f0; color: #1e293b; border-radius: 8px; padding: 7px 12px; font-weight: 700; font-size: 11px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                        <i data-lucide="external-link" style="width: 14px; height: 14px;"></i> Open UI
                    </a>
                </div>
            </div>

            {{-- 2. Google Gemini & OpenRouter --}}
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 13px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                        <span>💬</span> Free Text Fallbacks
                    </span>
                    <span class="ai-pill" style="background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe;">Text Chain</span>
                </div>
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                        <label style="font-size: 11px; font-weight: 700; color: #475569; margin: 0;">Google Gemini Key (15 RPM Free)</label>
                        <button type="button" class="toggle-secret-btn" data-target="gemini_key" style="background: none; border: none; font-size: 11px; color: #2563eb; font-weight: 700; cursor: pointer;">Show</button>
                    </div>
                    <input type="password" id="gemini_key" name="gemini_key" value="{{ $creds['gemini_key'] ?? '' }}" class="ai-input" style="font-family: monospace;" placeholder="AIzaSy…">
                </div>
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                        <label style="font-size: 11px; font-weight: 700; color: #475569; margin: 0;">OpenRouter API Key (Free Models)</label>
                        <button type="button" class="toggle-secret-btn" data-target="openrouter_key" style="background: none; border: none; font-size: 11px; color: #2563eb; font-weight: 700; cursor: pointer;">Show</button>
                    </div>
                    <input type="password" id="openrouter_key" name="openrouter_key" value="{{ $creds['openrouter_key'] ?? '' }}" class="ai-input" style="font-family: monospace;" placeholder="sk-or-v1-…">
                </div>
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">OpenRouter Fallback Model</label>
                    <input type="text" name="model_openrouter" value="{{ $creds['model_openrouter'] ?? 'deepseek/deepseek-r1:free' }}" class="ai-input" style="font-family: monospace;" placeholder="deepseek/deepseek-r1:free">
                </div>
            </div>

            {{-- 3. Cloudflare & Hugging Face --}}
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 13px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                        <span>📸</span> Free Image / Video APIs
                    </span>
                    <span class="ai-pill" style="background: #fffbeb; color: #92400e; border: 1px solid #fde68a;">Visual Chain</span>
                </div>
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">Cloudflare Account ID (Workers AI)</label>
                    <input type="text" name="cloudflare_account_id" value="{{ $creds['cloudflare_account_id'] ?? '' }}" class="ai-input" style="font-family: monospace;" placeholder="e.g. 7c1e5…">
                </div>
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                        <label style="font-size: 11px; font-weight: 700; color: #475569; margin: 0;">Cloudflare Workers AI Token</label>
                        <button type="button" class="toggle-secret-btn" data-target="cloudflare_api_token" style="background: none; border: none; font-size: 11px; color: #2563eb; font-weight: 700; cursor: pointer;">Show</button>
                    </div>
                    <input type="password" id="cloudflare_api_token" name="cloudflare_api_token" value="{{ $creds['cloudflare_api_token'] ?? '' }}" class="ai-input" style="font-family: monospace;" placeholder="Cloudflare API Token">
                </div>
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                        <label style="font-size: 11px; font-weight: 700; color: #475569; margin: 0;">Hugging Face Token (Free Serverless)</label>
                        <button type="button" class="toggle-secret-btn" data-target="huggingface_token" style="background: none; border: none; font-size: 11px; color: #2563eb; font-weight: 700; cursor: pointer;">Show</button>
                    </div>
                    <input type="password" id="huggingface_token" name="huggingface_token" value="{{ $creds['huggingface_token'] ?? '' }}" class="ai-input" style="font-family: monospace;" placeholder="hf_…">
                </div>
            </div>

            {{-- 4. WhatsApp Gateway (Evolution API) --}}
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 13px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                        <span>📱</span> WhatsApp Gateway (Evolution API v2)
                    </span>
                    <span class="ai-pill" style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;">Messaging Node</span>
                </div>
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">Evolution API Endpoint</label>
                    <input type="text" name="evolution_api_url" value="{{ $creds['evolution_api_url'] ?? 'https://wa.vmstudio.digital' }}" class="ai-input" style="font-family: monospace;">
                </div>
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                        <label style="font-size: 11px; font-weight: 700; color: #475569; margin: 0;">Evolution API Key</label>
                        <button type="button" class="toggle-secret-btn" data-target="evolution_api_key" style="background: none; border: none; font-size: 11px; color: #2563eb; font-weight: 700; cursor: pointer;">Show</button>
                    </div>
                    <input type="password" id="evolution_api_key" name="evolution_api_key" value="{{ $creds['evolution_api_key'] ?? '' }}" class="ai-input" style="font-family: monospace;" placeholder="Global API Key">
                </div>
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">Instance Name</label>
                    <input type="text" name="evolution_instance" value="{{ $creds['evolution_instance'] ?? 'onlinepuja' }}" class="ai-input" style="font-family: monospace;">
                </div>
                <div style="display: flex; gap: 8px; margin-top: 4px;">
                    <button type="button" onclick="openWaQrModal()" class="ai-btn-sm" style="flex: 1; justify-content: center; background: #059669; color: white;">
                        <i data-lucide="qr-code" style="width: 14px; height: 14px;"></i> Scan WhatsApp QR
                    </button>
                    <button type="button" onclick="testWhatsAppPing()" class="ai-btn-sm" style="justify-content: center; background: #e2e8f0; color: #1e293b;">
                        <i data-lucide="send" style="width: 14px; height: 14px;"></i> Ping
                    </button>
                </div>
            </div>

            {{-- 5. Brevo Email Engine (v3 REST API) --}}
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 13px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                        <span>✉️</span> Brevo Email Engine (v3 REST)
                    </span>
                    <span class="ai-pill" style="background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe;">Email Node</span>
                </div>
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                        <label style="font-size: 11px; font-weight: 700; color: #475569; margin: 0;">Brevo API v3 Key</label>
                        <button type="button" class="toggle-secret-btn" data-target="brevo_api_key" style="background: none; border: none; font-size: 11px; color: #2563eb; font-weight: 700; cursor: pointer;">Show</button>
                    </div>
                    <input type="password" id="brevo_api_key" name="brevo_api_key" value="{{ $creds['brevo_api_key'] ?? '' }}" class="ai-input" style="font-family: monospace;" placeholder="xkeysib-…">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">Sender Email</label>
                        <input type="email" name="brevo_sender_email" value="{{ $creds['brevo_sender_email'] ?? 'care@onlinepuja.live' }}" class="ai-input" style="font-size: 11px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">Sender Name</label>
                        <input type="text" name="brevo_sender_name" value="{{ $creds['brevo_sender_name'] ?? 'Onlinepuja.live' }}" class="ai-input" style="font-size: 11px;">
                    </div>
                </div>
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">Devotees Contact List ID</label>
                    <input type="text" name="brevo_list_id" value="{{ $creds['brevo_list_id'] ?? '2' }}" class="ai-input" style="font-family: monospace;" placeholder="e.g. 2">
                </div>
                <div style="display: flex; gap: 8px; margin-top: 4px;">
                    <button type="button" onclick="syncDevoteesToBrevo()" class="ai-btn-sm" style="flex: 1; justify-content: center; background: #2563eb; color: white;">
                        <i data-lucide="users" style="width: 14px; height: 14px;"></i> Sync Devotees
                    </button>
                    <button type="button" onclick="testBrevoEmail()" class="ai-btn-sm" style="justify-content: center; background: #e2e8f0; color: #1e293b;">
                        <i data-lucide="send" style="width: 14px; height: 14px;"></i> Ping Email
                    </button>
                </div>
            </div>
        </div>

        {{-- Video Reels Safe Zones & Devotional Branding --}}
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="width: 32px; height: 32px; border-radius: 8px; background: #fff1f2; color: #e11d48; display: flex; align-items: center; justify-content: center; font-size: 16px;">🎬</span>
                    <div>
                        <span style="font-size: 13px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; display: block;">
                            9:16 Video Reel Devotional Branding & Safe Zones
                        </span>
                        <span style="font-size: 11px; color: #64748b;">
                            Watermark logo overlay, shaped Devanagari Sanskrit typography (HarfBuzz), and WhatsApp CTA on every synthesized 1080x1920 MP4 reel.
                        </span>
                    </div>
                </div>
                <span class="ai-pill" style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;">
                    Instagram, FB & Shorts Safe-Zone Compliant
                </span>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px;">
                {{-- Brand Title --}}
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">
                        Reel Devotional Title / Brand
                    </label>
                    <input type="text" name="video_brand_title" value="{{ $creds['video_brand_title'] ?? 'OnlinePuja.live' }}" class="ai-input" placeholder="e.g. OnlinePuja.live or श्री काशी विश्वनाथ">
                </div>

                {{-- Tagline / Subtitle --}}
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">
                        Devotional Subtitle (HarfBuzz Shaped)
                    </label>
                    <input type="text" name="video_brand_tagline" value="{{ $creds['video_brand_tagline'] ?? 'वाराणसी से सीधा लाइव संकल्प एवं दर्शन' }}" class="ai-input" placeholder="e.g. वाराणसी से सीधा लाइव संकल्प एवं दर्शन">
                </div>

                {{-- Phone / WhatsApp CTA --}}
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">
                        WhatsApp / Booking CTA Line
                    </label>
                    <input type="text" name="video_brand_phone" value="{{ $creds['video_brand_phone'] ?? '+91 70071 58014 | Book Online' }}" class="ai-input" placeholder="+91 70071 58014 | Book Online">
                </div>

                {{-- Logo Toggle & Path --}}
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                        <label style="font-size: 11px; font-weight: 700; color: #475569; margin: 0;">Watermark Logo Overlay</label>
                        <label style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; color: #065f46; cursor: pointer;">
                            <input type="hidden" name="video_logo_enabled" value="0">
                            <input type="checkbox" name="video_logo_enabled" value="1" {{ ($creds['video_logo_enabled'] ?? true) ? 'checked' : '' }}>
                            Enable Logo
                        </label>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="text" name="video_logo_path" value="{{ $creds['video_logo_path'] ?? 'assets-pg/imgs/logo-for-site.png' }}" class="ai-input" style="font-family: monospace; font-size: 11px;" placeholder="assets-pg/imgs/logo-for-site.png">
                        <div style="width: 38px; height: 38px; border-radius: 8px; background: #0f172a; display: flex; align-items: center; justify-content: center; padding: 4px; flex-shrink: 0;" title="Logo Asset Preview">
                            <img src="{{ asset('assets-pg/imgs/logo-for-site.png') }}" alt="Logo" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                        </div>
                    </div>
                </div>

                {{-- Logo Position Selector --}}
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">
                        Logo Safe Zone Placement
                    </label>
                    <select name="video_logo_position" class="ai-select">
                        <option value="top-left" {{ ($creds['video_logo_position'] ?? 'top-left') == 'top-left' ? 'selected' : '' }}>
                            📍 Top-Left Safe Zone (x=50, y=85 — Recommended)
                        </option>
                        <option value="top-center" {{ ($creds['video_logo_position'] ?? '') == 'top-center' ? 'selected' : '' }}>
                            📍 Top-Center Header Badge
                        </option>
                        <option value="bottom-left" {{ ($creds['video_logo_position'] ?? '') == 'bottom-left' ? 'selected' : '' }}>
                            📍 Bottom-Left Above Title Card
                        </option>
                    </select>
                </div>

                {{-- Ambient Audio Track --}}
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">
                        Ambient Audio Track
                    </label>
                    <select name="video_audio_track" class="ai-select">
                        <option value="sacred_bells" {{ ($creds['video_audio_track'] ?? 'sacred_bells') == 'sacred_bells' ? 'selected' : '' }}>
                            🔔 Vedic Temple Bell Chimes & Resonance (Verified 192k AAC)
                        </option>
                        <option value="silent" {{ ($creds['video_audio_track'] ?? '') == 'silent' ? 'selected' : '' }}>
                            🔇 Silent Audio Track (For User Music Replacement)
                        </option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Fallback Chain Order Configuration --}}
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
            <span style="font-size: 12px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 12px;">
                Ordered Fallback Cascade Sequences
            </span>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 14px;">
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">Text Cascade Chain (Default: OmniRoute)</label>
                    <input type="text" name="ai_text_chain" value="{{ $creds['ai_text_chain'] ?? 'omniroute,gemini,openrouter,pollinations' }}" class="ai-input" style="font-family: monospace;">
                </div>
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">Image Cascade Chain (Default: OmniRoute Gemini Flash Image)</label>
                    <input type="text" name="ai_image_chain" value="{{ $creds['ai_image_chain'] ?? 'omniroute,gemini_imagen,cloudflare,pollinations' }}" class="ai-input" style="font-family: monospace;">
                </div>
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">Video Cascade Chain (Default: Native FFmpeg 9:16)</label>
                    <input type="text" name="ai_video_chain" value="{{ $creds['ai_video_chain'] ?? 'ffmpeg_reel,omniroute,kaggle' }}" class="ai-input" style="font-family: monospace;">
                </div>
            </div>
        </div>

        <div style="padding-top: 4px;">
            <button type="submit" class="ai-btn-dark" style="font-size: 13px; padding: 12px 26px;">
                <i data-lucide="check" style="color: #fbbf24;"></i> Save AI Vault Credentials & Fallbacks
            </button>
        </div>
    </form>
</div>

<script>
(function() {
    function initAi() {
    // 0. Ensure Lucide icons are converted
    function refreshIcons() {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    }
    refreshIcons();

    // 1. Live Model Sync from OmniRoute (ai.vmstudio.digital)
    var btnSync = document.getElementById('btn-sync-live-models');
    var syncIcon = document.getElementById('sync-btn-icon');
    var syncText = document.getElementById('sync-btn-text');
    var totalBadge = document.getElementById('sync-total-badge');
    var lastTime = document.getElementById('sync-last-time');
    var textCountBadge = document.getElementById('omni-text-count-badge');
    var imageCountBadge = document.getElementById('omni-image-count-badge');
    var videoCountBadge = document.getElementById('omni-video-count-badge');

    if (btnSync) {
        btnSync.addEventListener('click', function() {
            btnSync.disabled = true;
            syncText.textContent = 'Syncing from ai.vmstudio.digital…';
            if (syncIcon) syncIcon.style.animation = 'spin 1s linear infinite';

            var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

            fetch('{{ route("growth-os.action") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ action: 'sync_models' })
            })
            .then(function(r) {
                if (r.status === 419) {
                    alert('Session CSRF expired. Reloading page to refresh token...');
                    window.location.reload();
                    return;
                }
                if (!r.ok) {
                    return r.text().then(function(t) {
                        try { return JSON.parse(t); } catch(e) { throw new Error('HTTP ' + r.status + ': ' + t.substring(0, 150)); }
                    });
                }
                return r.json();
            })
            .then(function(res) {
                btnSync.disabled = false;
                syncText.textContent = 'Sync Live Models';
                if (syncIcon) syncIcon.style.animation = '';

                if (res.ok) {
                    if (res.counts) {
                        if (totalBadge) totalBadge.textContent = Number(res.counts.total).toLocaleString();
                        if (lastTime) lastTime.textContent = res.counts.last_synced || 'Just now';
                        if (textCountBadge) textCountBadge.textContent = res.counts.text + ' Models';
                        if (imageCountBadge) imageCountBadge.textContent = res.counts.image + ' Models';
                        if (videoCountBadge) videoCountBadge.textContent = res.counts.video + ' Models';
                    }
                    var msg = res.message || 'Models synced from OmniRoute successfully!';
                    if (window.showGrowthNotification) {
                        window.showGrowthNotification('success', msg);
                    } else if (typeof toastr !== 'undefined') {
                        toastr.success(msg);
                    } else {
                        alert(msg);
                    }
                    refreshIcons();
                    setTimeout(function() { window.location.reload(); }, 1200);
                } else {
                    var err = res.error || 'Sync failed';
                    if (window.showGrowthNotification) {
                        window.showGrowthNotification('error', err);
                    } else if (typeof toastr !== 'undefined') {
                        toastr.error(err);
                    } else {
                        alert('Sync failed: ' + err);
                    }
                }
            })
            .catch(function(err) {
                btnSync.disabled = false;
                syncText.textContent = 'Sync Live Models';
                if (syncIcon) syncIcon.style.animation = '';
                var errStr = err.message || err;
                if (window.showGrowthNotification) {
                    window.showGrowthNotification('error', 'Sync request failed: ' + errStr);
                } else if (typeof toastr !== 'undefined') {
                    toastr.error('Sync failed: ' + errStr);
                } else {
                    alert('Sync error: ' + errStr);
                }
            });
        });
    }

    // 2. Show/Hide Secrets
    document.querySelectorAll('.toggle-secret-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            var input = document.getElementById(targetId);
            if (!input) return;
            if (input.type === 'password') {
                input.type = 'text';
                this.textContent = 'Hide';
            } else {
                input.type = 'password';
                this.textContent = 'Show';
            }
        });
    });

    // 3. Mode Switcher in Lab
    var currentMode = 'text';
    var modeButtons = document.querySelectorAll('.mode-tab-btn');
    modeButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            modeButtons.forEach(function(b) {
                b.classList.remove('active');
            });
            this.classList.add('active');
            currentMode = this.getAttribute('data-mode');
        });
    });

    // 4. Select Engine from Top Cards & Automatically Run Live Test
    document.querySelectorAll('.select-test-engine').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var eng = this.getAttribute('data-engine');
            var targetBtn = document.getElementById('tab-mode-' + eng);
            if (targetBtn) targetBtn.click();

            var promptBox = document.getElementById('multi-test-prompt');
            if (promptBox) {
                if (eng === 'text') {
                    promptBox.value = 'Maha Shivratri sacred significance and planetary remedies for peace and prosperity';
                } else if (eng === 'image') {
                    promptBox.value = 'Ancient sacred black stone Shiva Lingam adorned with bilva leaves and brass diyas in temple';
                } else if (eng === 'video') {
                    promptBox.value = 'Vedic Temple Aarti with golden lamps and sacred dhoop in Varanasi';
                }
                promptBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            // Immediately trigger synthesis so the user sees real-time test execution
            var runBtn = document.getElementById('btn-run-synthesis');
            if (runBtn) {
                setTimeout(function() {
                    runBtn.click();
                }, 100);
            }
        });
    });

    // 5. Quick Suggestion Chips
    document.querySelectorAll('.quick-lab-pill').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var promptBox = document.getElementById('multi-test-prompt');
            if (promptBox) {
                promptBox.value = this.getAttribute('data-val');
                var runBtn = document.getElementById('btn-run-synthesis');
                if (runBtn) {
                    setTimeout(function() { runBtn.click(); }, 100);
                }
            }
        });
    });

    // 6. Live Synthesis Action
    var runBtn = document.getElementById('btn-run-synthesis');
    var promptInp = document.getElementById('multi-test-prompt');
    var placeholder = document.getElementById('lab-placeholder');
    var loading = document.getElementById('lab-loading');
    var imgResult = document.getElementById('lab-img-result');
    var videoResult = document.getElementById('lab-video-result');
    var textResult = document.getElementById('lab-text-result');
    var telemetryBadge = document.getElementById('telemetry-badge');
    var telemetryProvider = document.getElementById('telemetry-provider');
    var telemetryMs = document.getElementById('telemetry-ms');

    function resetOutput() {
        if (placeholder) placeholder.classList.add('hidden');
        if (imgResult) imgResult.classList.add('hidden');
        if (videoResult) videoResult.classList.add('hidden');
        if (textResult) textResult.classList.add('hidden');
        if (telemetryBadge) telemetryBadge.classList.add('hidden');
        if (loading) loading.classList.remove('hidden');
    }

    if (runBtn) {
        runBtn.addEventListener('click', function() {
            var p = promptInp.value.trim();
            if (!p) {
                if (typeof toastr !== 'undefined') toastr.warning('Please enter a prompt to test.');
                return;
            }
            resetOutput();

            var origHtml = runBtn.innerHTML;
            runBtn.disabled = true;
            runBtn.innerHTML = '<i data-lucide="loader-2" class="animate-spin" style="color: #fbbf24;"></i> Synthesizing…';
            refreshIcons();

            var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

            fetch('{{ route("growth-os.action") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    action: 'test_ai_engine',
                    engine: currentMode,
                    prompt: p
                })
            })
            .then(function(r) {
                if (r.status === 419) {
                    alert('Session CSRF expired. Reloading page to refresh token...');
                    window.location.reload();
                    return;
                }
                if (!r.ok) {
                    return r.text().then(function(t) {
                        try { return JSON.parse(t); } catch(e) { throw new Error('HTTP ' + r.status + ': ' + t.substring(0, 150)); }
                    });
                }
                return r.json();
            })
            .then(function(res) {
                runBtn.disabled = false;
                runBtn.innerHTML = origHtml;
                loading.classList.add('hidden');
                var d = res.data || {};
                var hasContent = (currentMode === 'text' && d.text) || 
                                 (currentMode === 'image' && (d.url || d.binary)) || 
                                 (currentMode === 'video' && (d.video_url || d.url));

                if (res.ok && hasContent) {
                    // Show telemetry
                    if (d.provider) {
                        telemetryProvider.textContent = d.provider;
                        telemetryMs.textContent = (d.time_ms || 120) + 'ms';
                        telemetryBadge.classList.remove('hidden');
                    }

                    if (currentMode === 'text') {
                        textResult.innerHTML = escapeHtml(d.text || '').replace(/\n/g, '<br>');
                        textResult.classList.remove('hidden');
                    } else if (currentMode === 'image') {
                        imgResult.src = d.url;
                        imgResult.classList.remove('hidden');
                    } else if (currentMode === 'video') {
                        var vUrl = d.video_url || d.url;
                        if (vUrl && (vUrl.includes('.mp4') || vUrl.includes('video'))) {
                            videoResult.src = vUrl;
                            videoResult.classList.remove('hidden');
                        } else if (vUrl) {
                            imgResult.src = vUrl;
                            imgResult.classList.remove('hidden');
                        }
                    }
                    if (typeof toastr !== 'undefined') toastr.success('Synthesized successfully via ' + (d.provider || 'AI Engine'));
                    refreshIcons();
                } else {
                    if (placeholder) placeholder.classList.remove('hidden');
                    var errMsg = res.error || (d && d.error) || 'Failed to synthesize output. All fallback tiers exhausted.';
                    if (typeof toastr !== 'undefined') {
                        toastr.error(errMsg);
                    } else {
                        alert('Synthesis Error: ' + errMsg);
                    }
                }
            })
            .catch(function(err) {
                runBtn.disabled = false;
                runBtn.innerHTML = origHtml;
                loading.classList.add('hidden');
                if (placeholder) placeholder.classList.remove('hidden');
                var errStr = err.message || err;
                if (typeof toastr !== 'undefined') toastr.error(errStr);
                else alert('Error: ' + errStr);
                refreshIcons();
            });
        });
    }

    function escapeHtml(str) {
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
    }

    if (document.readyState !== 'loading') {
        initAi();
    } else {
        document.addEventListener('DOMContentLoaded', initAi);
    }
})();
</script>
{{-- WhatsApp QR Modal --}}
<div id="modal-wa-qr" class="fixed inset-0 z-[9999] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-200 text-center">
        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center font-bold mb-3">
            <i data-lucide="qr-code" class="w-6 h-6"></i>
        </div>
        <h3 class="text-base font-bold text-slate-900">Scan WhatsApp QR Code</h3>
        <p class="text-xs text-slate-500 mt-1">Open WhatsApp on your phone &gt; Linked Devices &gt; Link a Device</p>

        <div id="wa-qr-container" class="mt-4 p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-center min-h-[220px]">
            <div class="text-xs text-slate-400 animate-pulse">Fetching live QR code…</div>
        </div>

        <div class="mt-4 flex items-center gap-2">
            <button type="button" onclick="fetchWaQr()" class="flex-1 btn btn-sm btn-outline-secondary py-2 text-xs rounded-xl">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5 mr-1"></i> Refresh QR
            </button>
            <button type="button" onclick="closeWaQrModal()" class="btn btn-sm bg-slate-900 text-white py-2 px-4 text-xs rounded-xl">
                Done
            </button>
        </div>
    </div>
</div>

<script>
function openWaQrModal() {
    var m = document.getElementById('modal-wa-qr');
    if (m) m.classList.remove('hidden');
    fetchWaQr();
}

function closeWaQrModal() {
    var m = document.getElementById('modal-wa-qr');
    if (m) m.classList.add('hidden');
}

function fetchWaQr() {
    var box = document.getElementById('wa-qr-container');
    if (!box) return;
    box.innerHTML = '<div class="text-xs text-slate-400 animate-pulse">Requesting QR from Evolution API…</div>';

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
            var html = '<img src="' + res.base64 + '" class="w-48 h-48 rounded-lg shadow-sm mx-auto" alt="WhatsApp QR Code">';
            if (res.pairingCode) {
                html += '<div class="mt-3 p-2.5 bg-amber-500/10 border border-amber-500/30 rounded-xl text-center"><span class="text-[10px] text-slate-400 block uppercase font-semibold">Or use Pairing Code in WhatsApp:</span><span class="text-base font-mono font-bold text-amber-600 tracking-wider">' + res.pairingCode + '</span></div>';
            }
            box.innerHTML = html;
        } else if (res && res.pairingCode) {
            box.innerHTML = '<div class="p-4 bg-amber-500/10 border border-amber-500/30 rounded-xl text-center"><span class="text-[11px] text-slate-700 block mb-1">WhatsApp Pairing Code:</span><span class="text-2xl font-mono font-black text-amber-600 tracking-widest">' + res.pairingCode + '</span><p class="text-[10px] text-slate-500 mt-2">Enter this in WhatsApp &gt; Linked Devices &gt; Link with phone number</p></div>';
        } else if (res && (res.code || res.state === 'open')) {
            box.innerHTML = '<div class="p-3 bg-emerald-500/20 text-emerald-800 border border-emerald-500/30 text-xs rounded-lg font-semibold text-center">Instance is already paired and active!</div>';
        } else {
            box.innerHTML = '<div class="text-xs text-rose-500 font-semibold">' + (res.error || 'Failed to fetch QR') + '</div>';
        }
    })
    .catch(function(err) {
        box.innerHTML = '<div class="text-xs text-rose-500">Error: ' + err.message + '</div>';
    });
}

function testWhatsAppPing() {
    fetch('{{ route("growth-os.action") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ action: 'test_whatsapp' })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        alert(res.ok ? '✅ ' + (res.message || 'WhatsApp ping successful!') : '❌ ' + (res.error || res.message));
    })
    .catch(function(err) { alert('Error: ' + err.message); });
}

function testBrevoEmail() {
    fetch('{{ route("growth-os.action") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ action: 'test_brevo' })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        alert(res.ok ? '✅ ' + (res.message || 'Brevo email sent!') : '❌ ' + (res.error || res.message));
    })
    .catch(function(err) { alert('Error: ' + err.message); });
}

function testComfyNode() {
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    fetch('{{ route("growth-os.action") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ action: 'test_comfyui' })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.ok) {
            var d = res.data || {};
            var msg = '🎨 ComfyUI GPU Node & OmniRoute Audit:\n';
            msg += '• Host: ' + (d.comfy_url || 'https://comfy.vmstudio.digital') + '\n';
            msg += '• OmniRoute Gateway: ' + (d.omniroute_connected ? '✅ Connected' : '⚠️ Offline') + '\n';
            msg += '• Direct Node Health: ' + (d.direct_node_ping ? '✅ Active' : 'ℹ️ Routed via OmniRoute/Cloudflare') + '\n';
            msg += '• Available Workflows:\n  - Image: ' + (d.available_models?.image || []).join(', ') + '\n  - Video: ' + (d.available_models?.video || []).join(', ');
            alert(msg);
        } else {
            alert('❌ ' + (res.error || res.message));
        }
    })
    .catch(function(err) { alert('Error: ' + err.message); });
}

function syncDevoteesToBrevo() {
    fetch('{{ route("growth-os.action") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ action: 'sync_brevo_contacts' })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        alert(res.ok ? '✅ ' + (res.message || 'Contacts synced!') : '❌ ' + (res.error || res.message));
    })
    .catch(function(err) { alert('Error: ' + err.message); });
}
</script>

@endsection
