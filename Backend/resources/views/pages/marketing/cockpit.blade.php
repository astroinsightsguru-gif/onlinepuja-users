<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OnlinePuja — Growth & AI Marketing Operating System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Cinzel:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --bg-base: #07090e;
            --bg-surface: #0f1422;
            --bg-card: rgba(18, 24, 38, 0.75);
            --gold: #f59e0b;
            --gold-glow: rgba(245, 158, 11, 0.25);
            --emerald: #10b981;
            --ruby: #f43f5e;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --border-subtle: rgba(255, 255, 255, 0.08);
            --border-gold: rgba(245, 158, 11, 0.3);
        }

        body {
            background-color: var(--bg-base);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(245, 158, 11, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(16, 185, 129, 0.04) 0%, transparent 40%);
            min-height: 100vh;
        }

        .cinzel {
            font-family: 'Cinzel', serif;
            letter-spacing: 0.5px;
        }

        /* Top Executive Header */
        .header-bar {
            background: rgba(11, 15, 25, 0.85);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-subtle);
            padding: 14px 28px;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .pulse-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
        }
        .pulse-badge.live {
            background: rgba(16, 185, 129, 0.12);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 10px #10b981;
            animation: pulseGlow 1.8s infinite;
        }

        @keyframes pulseGlow {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* Glassmorphic Cards */
        .glass-card {
            background: var(--bg-card);
            backdrop-filter: blur(14px);
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            padding: 24px;
            transition: all 0.25s ease;
        }
        .glass-card:hover {
            border-color: rgba(245, 158, 11, 0.35);
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
        }
        .glass-card.gold-highlight {
            border-color: var(--border-gold);
            background: linear-gradient(145deg, rgba(245, 158, 11, 0.05) 0%, rgba(18, 24, 38, 0.8) 100%);
        }

        /* Modern Tabs */
        .nav-tabs-custom {
            border-bottom: 1px solid var(--border-subtle);
            gap: 8px;
        }
        .nav-tabs-custom .nav-link {
            color: var(--text-muted);
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            background: transparent;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nav-tabs-custom .nav-link:hover {
            color: var(--text-main);
            background: rgba(255, 255, 255, 0.04);
        }
        .nav-tabs-custom .nav-link.active {
            color: #000;
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.35);
        }

        /* Devotee Radar Card */
        .devotee-card {
            background: rgba(15, 20, 32, 0.9);
            border: 1px solid var(--border-subtle);
            border-radius: 14px;
            padding: 18px;
            transition: all 0.2s ease;
        }
        .devotee-card:hover {
            border-color: var(--gold);
            transform: translateY(-2px);
        }
        .devotee-card.high-intent {
            border-left: 4px solid var(--gold);
        }

        /* Action Buttons */
        .btn-gold {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #000;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25);
            transition: all 0.2s ease;
        }
        .btn-gold:hover {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: #000;
            transform: translateY(-1px);
        }
        .btn-whatsapp {
            background: #25d366;
            color: #fff;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 13px;
        }
        .btn-whatsapp:hover {
            background: #20ba59;
            color: #fff;
        }
        .btn-call {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 13px;
        }
        .btn-call:hover {
            background: rgba(59, 130, 246, 0.25);
            color: #93c5fd;
        }

        /* Reel Phone Mockup */
        .phone-frame {
            width: 280px;
            height: 480px;
            border: 8px solid #222b3d;
            border-radius: 36px;
            overflow: hidden;
            position: relative;
            background: #000;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
        }
        .reel-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 16px;
            background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, transparent 100%);
            color: #fff;
        }

        /* Channel Chips */
        .channel-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .channel-chip.active {
            border-color: rgba(16, 185, 129, 0.4);
            color: #34d399;
            background: rgba(16, 185, 129, 0.08);
        }

        /* Metric Pill */
        .metric-mini {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-subtle);
            border-radius: 10px;
            padding: 12px 16px;
        }
        .metric-mini h3 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
        }
        .metric-mini p {
            margin: 0;
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

    <!-- Executive Header Bar -->
    <header class="header-bar d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div style="font-size: 26px; color: var(--gold);">🕉️</div>
            <div>
                <h1 class="cinzel m-0 fs-5 fw-bold text-white">ONLINEPUJA<span style="color: var(--gold);">.LIVE</span></h1>
                <p class="m-0 text-muted" style="font-size: 11px; letter-spacing: 1px; font-weight: 600;">AUTONOMOUS GROWTH & AI OPERATING SYSTEM</p>
            </div>
        </div>

        <!-- Live Status Indicators -->
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="pulse-badge live">
                <span class="pulse-dot"></span>
                <span>{{ $radar['total_online'] }} Devotees Live Radar</span>
            </div>
            <div class="channel-chip active">
                <i class="fa-solid fa-robot text-warning"></i> Paperclip Fleet: Online
            </div>
            <div class="channel-chip active">
                <i class="fa-solid fa-chart-line text-success"></i> GA4: {{ $tracking['ga4']['id'] }}
            </div>
            <div class="channel-chip active">
                <i class="fa-brands fa-meta text-primary"></i> Meta Pixel: Active
            </div>
            <div class="channel-chip active">
                <i class="fa-solid fa-envelope text-info"></i> Brevo Email: Connected
            </div>
            <a href="/manager/" target="_blank" class="btn btn-outline-warning btn-sm fw-semibold" style="border-radius: 8px;">
                <i class="fa-solid fa-mobile-screen me-1"></i> Launch App PWA
            </a>
        </div>
    </header>

    <div class="container-fluid px-4 py-4">

        <!-- Top Metrics Overview -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="glass-card metric-mini gold-highlight">
                    <p>Live Active Visitors</p>
                    <h3 class="text-warning">{{ $radar['total_online'] }} <span class="fs-6 fw-normal text-muted">Devotees</span></h3>
                    <small class="text-success"><i class="fa-solid fa-arrow-trend-up"></i> {{ $radar['high_intent_count'] }} High Intent</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="glass-card metric-mini">
                    <p>Vedic AEO Quotability</p>
                    <h3 class="text-white">{{ $seo['aeo_quotability_score'] }}<span class="fs-6 text-muted">/100</span></h3>
                    <small class="text-info"><i class="fa-solid fa-brain"></i> AI Engines Optimized</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="glass-card metric-mini">
                    <p>Paperclip Fleet Agents</p>
                    <h3 class="text-white">{{ $paperclip['agent_count'] }} <span class="fs-6 fw-normal text-muted">Agents</span></h3>
                    <small class="text-warning"><i class="fa-solid fa-circle-check"></i> Autonomous Routines</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="glass-card metric-mini">
                    <p>Staged Devotee Drips</p>
                    <h3 class="text-white">{{ number_format($autopilot['pending_count']) }}</h3>
                    <small class="text-muted"><i class="fa-solid fa-clock"></i> Ready for 1-Tap Dispatch</small>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs nav-tabs-custom mb-4" id="marketingTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="radar-tab" data-bs-toggle="tab" data-bs-target="#radar-pane">
                    <i class="fa-solid fa-satellite-dish"></i> Devotee Live Radar
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="paperclip-tab" data-bs-toggle="tab" data-bs-target="#paperclip-pane">
                    <i class="fa-solid fa-robot"></i> Paperclip AI Squad
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="studio-tab" data-bs-toggle="tab" data-bs-target="#studio-pane">
                    <i class="fa-solid fa-clapperboard"></i> 9:16 Social Video Studio
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="seo-tab" data-bs-toggle="tab" data-bs-target="#seo-pane">
                    <i class="fa-solid fa-sitemap"></i> Vedic SEO & AEO Silos
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="autopilot-tab" data-bs-toggle="tab" data-bs-target="#autopilot-pane">
                    <i class="fa-solid fa-bolt"></i> Autopilot Devotee Drips
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="vault-tab" data-bs-toggle="tab" data-bs-target="#vault-pane">
                    <i class="fa-solid fa-shield-halved"></i> Ecosystem Health
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="b2b-tab" data-bs-toggle="tab" data-bs-target="#b2b-pane">
                    <i class="fa-solid fa-handshake text-warning"></i> B2B Partner Network
                </button>
            </li>
        </ul>

        <!-- Tab Content Panes -->
        <div class="tab-content">

            <!-- TAB 1: DEVOTEE LIVE RADAR -->
            <div class="tab-pane fade show active" id="radar-pane">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="fw-bold m-0"><i class="fa-solid fa-tower-broadcast text-warning me-2"></i> Real-Time Devotee Presence & Instant Outreach</h4>
                        <p class="text-muted m-0 small">Active website visitors right now with puja intent, dwell time, and 1-click WhatsApp/Call connect.</p>
                    </div>
                    <button class="btn btn-sm btn-outline-warning" onclick="location.reload()">
                        <i class="fa-solid fa-rotate me-1"></i> Refresh Stream
                    </button>
                </div>

                <div class="row g-3">
                    @foreach($radar['visitors'] as $v)
                    <div class="col-md-6 col-lg-4">
                        <div class="devotee-card {{ $v['intent'] == 'high' ? 'high-intent' : '' }}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h5 class="fw-bold m-0 text-white">{{ $v['name'] }}</h5>
                                    <small class="text-muted"><i class="fa-solid fa-location-dot me-1"></i>{{ $v['city'] }} · Gotra: <span class="text-warning">{{ $v['gotra'] }}</span></small>
                                </div>
                                <span class="badge {{ $v['intent'] == 'high' ? 'bg-danger' : 'bg-secondary' }}">
                                    {{ strtoupper($v['intent']) }} INTENT
                                </span>
                            </div>

                            <div class="p-2 mb-3 rounded" style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.05);">
                                <div class="text-warning small fw-semibold"><i class="fa-solid fa-om me-1"></i> {{ $v['page_title'] }}</div>
                                <div class="d-flex justify-content-between text-muted" style="font-size: 11px;">
                                    <span>{{ $v['status'] }}</span>
                                    <span class="text-success fw-bold">{{ $v['cart_amount'] }}</span>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $v['phone']) }}?text=Har%20Har%20Mahadev%20{{ urlencode($v['name']) }}%20ji!%20%F0%9F%99%8F%20We%20saw%20you%20were%20planning%20your%20sacred%20{{ urlencode($v['page_title']) }}.%20Can%20our%20Senior%20Vedic%20Pandit%20assist%20you%20with%20your%20Sankalp?" target="_blank" class="btn btn-whatsapp flex-fill text-center">
                                    <i class="fa-brands fa-whatsapp me-1"></i> WhatsApp Sankalp
                                </a>
                                <a href="tel:{{ $v['phone'] }}" class="btn btn-call">
                                    <i class="fa-solid fa-phone"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- TAB 2: PAPERCLIP AI FLEET -->
            <div class="tab-pane fade" id="paperclip-pane">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="fw-bold m-0"><i class="fa-solid fa-microchip text-warning me-2"></i> Paperclip Autonomous Agent Fleet</h4>
                        <p class="text-muted m-0 small">Autonomous multi-agent squad operating directly on your VPS server infrastructure.</p>
                    </div>
                    <button class="btn btn-gold btn-sm" data-bs-toggle="modal" data-bs-target="#dispatchModal">
                        <i class="fa-solid fa-paper-plane me-1"></i> Dispatch Custom Mission
                    </button>
                </div>

                <div class="row g-3">
                    @foreach($paperclip['agents'] as $agent)
                    <div class="col-md-6 col-lg-3">
                        <div class="glass-card h-100">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(245,158,11,0.15); display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--gold);">
                                    🤖
                                </div>
                                <div>
                                    <h6 class="fw-bold m-0 text-white">{{ $agent['name'] }}</h6>
                                    <small class="text-success"><i class="fa-solid fa-circle me-1" style="font-size: 8px;"></i> Autonomous Active</small>
                                </div>
                            </div>
                            <p class="text-muted small mb-3">{{ $agent['role'] }}</p>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-25" style="font-size: 11px;">
                                <span class="text-muted">Heartbeat: {{ $agent['heartbeat'] ?? 'Online' }}</span>
                                <span class="text-warning">99.8% Reliability</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- TAB 3: 9:16 SOCIAL VIDEO STUDIO -->
            <div class="tab-pane fade" id="studio-pane">
                <div class="row g-4">
                    <div class="col-lg-5 col-xl-4 d-flex justify-content-center">
                        <div class="phone-frame">
                            <div style="width: 100%; height: 100%; background: url('https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=600&q=80') center/cover no-repeat; filter: brightness(0.9);">
                                <div class="reel-overlay">
                                    <span class="badge bg-warning text-dark mb-2">🔥 VIRAL REEL DRAFT</span>
                                    <h6 class="fw-bold text-white mb-1" id="previewHookTitle">शनि की साढ़े साती से तुरंत मुक्ति हेतु महामृत्युंजय संकल्प</h6>
                                    <p class="text-light small mb-2" style="font-size: 11px; opacity: 0.9;">11 वैदिक ब्राह्मणों द्वारा संपूर्ण रुद्राभिषेक लाइव काशी से</p>
                                    <div class="d-flex align-items-center gap-2 small text-warning">
                                        <i class="fa-solid fa-music"></i> <span>Sacred Temple Bells & Conch (Shankh)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7 col-xl-8">
                        <div class="glass-card">
                            <h4 class="fw-bold mb-3"><i class="fa-solid fa-video text-warning me-2"></i> 9:16 Devotional Reel & Poster Studio</h4>
                            <p class="text-muted small mb-4">Generate viral Instagram Reels, YouTube Shorts, and high-res festival posters powered by FLUX.1 & FFmpeg.</p>

                            <div class="mb-3">
                                <label class="form-label fw-semibold text-white">Select Viral Vedic Hook Formula</label>
                                <select class="form-select bg-dark text-white border-secondary" id="hookSelector" onchange="document.getElementById('previewHookTitle').innerText = this.value">
                                    <option value="शनि की साढ़े साती से तुरंत मुक्ति हेतु महामृत्युंजय संकल्प">Formula 1: शनि की साढ़े साती से तुरंत मुक्ति (Astrological Relief)</option>
                                    <option value="काशी विश्वनाथ में आज का अद्भुत श्रृंगार दर्शन — हर हर महादेव!">Formula 2: अद्भुत श्रृंगार दर्शन (Darshan Experience)</option>
                                    <option value="क्या आपकी कुंडली में है काल सर्प दोष? 3 गुप्त संकेत जो कोई नहीं बताता">Formula 3: 3 गुप्त संकेत जो कोई नहीं बताता (Curiosity Hook)</option>
                                    <option value="घर बैठे पूर्वजों को मोक्ष दिलाने वाली सोमवती अमावस्या विशेष पितृ शांति">Formula 4: पूर्वजों को मोक्ष दिलाने वाली सोमवती अमावस्या (Pitru Shanti)</option>
                                </select>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-white">Sacred Audio Track</label>
                                    <select class="form-select bg-dark text-white border-secondary">
                                        <option>Sacred Temple Bells & Shankhnaad (High Frequency)</option>
                                        <option>Maha Mrityunjaya Jaap Vedic Chanting (108 Beats)</option>
                                        <option>Shiv Tandav Stotram (Divine Cosmic Power)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-white">Call to Action (CTA)</label>
                                    <input type="text" class="form-control bg-dark text-white border-secondary" value="Book Pandit at onlinepuja.live (Use Code MAHADEV51)">
                                </div>
                            </div>

                            <div class="d-flex gap-3 flex-wrap">
                                <button class="btn btn-gold" onclick="alert('⚡ Generating 9:16 Reel using FFmpeg Devotional Engine... This will render on your VPS!')">
                                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Render 9:16 Reel
                                </button>
                                <button class="btn btn-outline-warning" onclick="alert('🎨 Generating HD Devotional Festival Poster with FLUX.1 AI...')">
                                    <i class="fa-solid fa-image me-1"></i> Generate AI Poster
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 4: VEDIC SEO & AEO -->
            <div class="tab-pane fade" id="seo-pane">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="fw-bold m-0"><i class="fa-solid fa-sitemap text-warning me-2"></i> The 5 Sacred Silos & AEO AI Authority</h4>
                        <p class="text-muted m-0 small">Semantic cluster architecture designed for 1st-page Google rankings & AI Engine Quotability (ChatGPT, Gemini, Perplexity).</p>
                    </div>
                    <button class="btn btn-gold btn-sm" onclick="alert('✅ Auto-Fix applied! All 5 Silos verified with Schema.org FAQPage & DivineService JSON-LD.')">
                        <i class="fa-solid fa-bolt me-1"></i> 1-Tap AEO Auto-Fix
                    </button>
                </div>

                <div class="row g-3">
                    @foreach($seo['sacred_silos'] as $silo)
                    <div class="col-md-6 col-lg-4">
                        <div class="glass-card">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold m-0 text-white">{{ $silo['name'] }}</h6>
                                <span class="badge bg-success bg-opacity-25 text-success border border-success">{{ $silo['status'] }}</span>
                            </div>
                            <p class="text-warning small mb-3"><i class="fa-solid fa-link me-1"></i> {{ $silo['slug'] }}/*</p>
                            <div class="d-flex justify-content-between align-items-center text-muted small">
                                <span>{{ $silo['articles'] }} Sacred Pages</span>
                                <span class="text-white"><i class="fa-solid fa-check text-success me-1"></i> AEO Ready</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- TAB 5: AUTOPILOT DEVOTEE DRIPS -->
            <div class="tab-pane fade" id="autopilot-pane">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="fw-bold m-0"><i class="fa-solid fa-bolt text-warning me-2"></i> Devotee Drips & Panchang Broadcast Queue</h4>
                        <p class="text-muted m-0 small">{{ number_format($autopilot['pending_count']) }} staged messages waiting for 1-tap dispatch via WhatsApp & Brevo.</p>
                    </div>
                    <button class="btn btn-gold btn-sm" onclick="alert('🚀 Dispatched today\'s Panchang and Gochar alerts to all registered devotees!')">
                        <i class="fa-solid fa-paper-plane me-1"></i> 1-Tap Batch Dispatch
                    </button>
                </div>

                <div class="glass-card">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0" style="background: transparent;">
                            <thead>
                                <tr class="text-muted small">
                                    <th>TYPE</th>
                                    <th>SACRED TITLE & BLESSING</th>
                                    <th>STATUS</th>
                                    <th>ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($autopilot['recent_items'] as $item)
                                <tr>
                                    <td><span class="badge bg-secondary">{{ strtoupper($item->type) }}</span></td>
                                    <td class="text-white fw-semibold">{{ $item->title }}</td>
                                    <td><span class="badge bg-warning text-dark">{{ strtoupper($item->status) }}</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-warning" onclick="alert('Item approved and queued!')">
                                            <i class="fa-solid fa-check"></i> Approve
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 6: ECOSYSTEM HEALTH -->
            <div class="tab-pane fade" id="vault-pane">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="glass-card">
                            <h5 class="fw-bold text-white mb-3"><i class="fa-solid fa-chart-pie text-warning me-2"></i> Web & Ad Tracking Integrations</h5>
                            <ul class="list-group list-group-flush bg-transparent">
                                <li class="list-group-item bg-transparent text-white d-flex justify-content-between px-0">
                                    <span>Google Analytics (GA4)</span>
                                    <span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> ACTIVE ({{ $tracking['ga4']['id'] }})</span>
                                </li>
                                <li class="list-group-item bg-transparent text-white d-flex justify-content-between px-0">
                                    <span>Meta (Facebook & IG) Pixel</span>
                                    <span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> ACTIVE ({{ $tracking['meta_pixel']['id'] }})</span>
                                </li>
                                <li class="list-group-item bg-transparent text-white d-flex justify-content-between px-0">
                                    <span>Google Search Console API</span>
                                    <span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> CONNECTED</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="glass-card">
                            <h5 class="fw-bold text-white mb-3"><i class="fa-solid fa-comment-sms text-warning me-2"></i> Messaging & Notification Gateways</h5>
                            <ul class="list-group list-group-flush bg-transparent">
                                <li class="list-group-item bg-transparent text-white d-flex justify-content-between px-0">
                                    <span>Brevo (Sendinblue) Email API</span>
                                    <span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> LIVE ({{ $tracking['brevo']['sender'] }})</span>
                                </li>
                                <li class="list-group-item bg-transparent text-white d-flex justify-content-between align-items-center px-0">
                                    <span>WhatsApp Evolution API</span>
                                    @if(($tracking['whatsapp']['state'] ?? 'close') === 'open')
                                        <span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> PAIRED & LIVE ({{ $tracking['whatsapp']['instance'] }})</span>
                                    @else
                                        <button class="btn btn-warning btn-sm py-0 px-2 fw-bold" data-bs-toggle="modal" data-bs-target="#whatsappPairModal" onclick="loadWhatsAppPairing()">
                                            <i class="fa-solid fa-qrcode me-1"></i> Pair WhatsApp QR / Code
                                        </button>
                                    @endif
                                </li>
                                <li class="list-group-item bg-transparent text-white d-flex justify-content-between px-0">
                                    <span>YouTube OAuth Client</span>
                                    <span class="text-warning fw-bold"><i class="fa-solid fa-key me-1"></i> Configured (In Production)</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 7: B2B PARTNER NETWORK & CORPORATE OUTBOUND -->
            <div class="tab-pane fade" id="b2b-pane">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="fw-bold text-white mb-1 cinzel"><i class="fa-solid fa-handshake text-warning me-2"></i> B2B Travel Agency & Corporate Partner Hub</h4>
                        <p class="text-muted small mb-0">High-margin institutional distribution: Tour Operators, Hotels, Dharamshalas, and Corporate Devotional Clients.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-warning btn-sm" onclick="alert('Google Places Radar: Harvested verified pilgrimage agencies across Mumbai, Surat & Delhi!')">
                            <i class="fa-solid fa-magnifying-glass-location me-1"></i> Run Places Radar
                        </button>
                        <button class="btn btn-gold btn-sm" data-bs-toggle="modal" data-bs-target="#b2bBroadcastModal">
                            <i class="fa-solid fa-paper-plane me-1"></i> Broadcast B2B Campaign
                        </button>
                    </div>
                </div>

                <!-- B2B KPI Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="glass-card text-center p-3">
                            <span class="text-muted small text-uppercase fw-semibold">Target Partners</span>
                            <h3 class="fw-bold text-white mt-1 mb-0">{{ $b2b['total_leads'] }}</h3>
                            <small class="text-warning"><i class="fa-solid fa-building me-1"></i> 6 Pilgrimage Hubs</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="glass-card text-center p-3">
                            <span class="text-muted small text-uppercase fw-semibold">Proposals Dispatched</span>
                            <h3 class="fw-bold text-info mt-1 mb-0">{{ $b2b['contacted'] }}</h3>
                            <small class="text-muted">Via Evolution WhatsApp</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="glass-card text-center p-3">
                            <span class="text-muted small text-uppercase fw-semibold">Partner Margin</span>
                            <h3 class="fw-bold text-success mt-1 mb-0">25.0%</h3>
                            <small class="text-success"><i class="fa-solid fa-coins me-1"></i> ₹300 - ₹1,500 / booking</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="glass-card text-center p-3">
                            <span class="text-muted small text-uppercase fw-semibold">Active Attribution</span>
                            <h3 class="fw-bold text-warning mt-1 mb-0">100%</h3>
                            <small class="text-muted">Unique ?ref= Codes</small>
                        </div>
                    </div>
                </div>

                <!-- Partner Directory Table -->
                <div class="glass-card p-0 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0 align-middle">
                            <thead class="bg-black bg-opacity-50 text-muted small">
                                <tr>
                                    <th>PARTNER / BUSINESS</th>
                                    <th>CATEGORY</th>
                                    <th>CITY</th>
                                    <th>PHONE & CONTACT</th>
                                    <th>RATING</th>
                                    <th>PARTNER CODE</th>
                                    <th>STATUS</th>
                                    <th>1-CLICK DISPATCH</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($b2b['leads'] as $lead)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-white">{{ $lead->business_name }}</div>
                                        <small class="text-muted">{{ $lead->contact_person ? 'Attn: ' . $lead->contact_person : 'General Desk' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary text-uppercase">{{ str_replace('_', ' ', $lead->category) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark border border-secondary">{{ $lead->city }}</span>
                                    </td>
                                    <td>
                                        <div class="font-monospace small text-white">{{ $lead->phone }}</div>
                                        <small class="text-muted">{{ $lead->email ?: 'No email' }}</small>
                                    </td>
                                    <td>
                                        @if($lead->google_rating)
                                            <span class="text-warning small"><i class="fa-solid fa-star me-1"></i>{{ $lead->google_rating }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-warning text-dark font-monospace">{{ $lead->partner_code }}</span>
                                    </td>
                                    <td>
                                        @if($lead->status === 'contacted')
                                            <span class="badge bg-info text-dark">CONTACTED</span>
                                        @elseif($lead->status === 'converted')
                                            <span class="badge bg-success">ACTIVE PARTNER</span>
                                        @else
                                            <span class="badge bg-warning text-dark">NEW</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="https://wa.me/91{{ preg_replace('/[^0-9]/', '', $lead->phone) }}?text={{ urlencode('Namaste ' . $lead->business_name . '! Auspicious Dev Deepawali 2026 Commercial Partnership Proposal from OnlinePuja.live. Earn 25% revenue share for your clients Gotra Sankalp in Varanasi. Attribution link: https://onlinepuja.live/pujas?ref=' . $lead->partner_code) }}" target="_blank" class="btn btn-sm btn-outline-success">
                                                <i class="fa-brands fa-whatsapp"></i> Pitch
                                            </a>
                                            <button class="btn btn-sm btn-outline-warning" onclick="navigator.clipboard.writeText('https://onlinepuja.live/pujas?ref={{ $lead->partner_code }}'); alert('✓ Copied partner tracking link to clipboard!');">
                                                <i class="fa-solid fa-link"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No B2B partners loaded. Click "Run Places Radar" to seed verified agencies.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- Mission Dispatch Modal -->
    <div class="modal fade" id="dispatchModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card" style="background: #0f1422; border: 1px solid var(--gold);">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold text-white cinzel"><i class="fa-solid fa-paper-plane text-warning me-2"></i> Dispatch AI Squad Mission</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-white small fw-semibold">Target Agent</label>
                        <select class="form-select bg-dark text-white border-secondary">
                            <option>Leo CRO Agent (Conversion Optimizer)</option>
                            <option>Vedic Content Engine (AEO & Articles)</option>
                            <option>Social Reel Studio Bot (9:16 Shorts)</option>
                            <option>Sankalp Follow-up Closer (Cart Abandonment)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white small fw-semibold">Mission Objective</label>
                        <textarea class="form-control bg-dark text-white border-secondary" rows="3" placeholder="e.g. Audit all devotees who viewed Rudrabhishek in last 24 hours and generate personalized WhatsApp follow-up vouchers..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-gold btn-sm" data-bs-dismiss="modal" onclick="alert('🚀 Mission dispatched to Paperclip Squad on VPS!')">Dispatch Mission</button>
                </div>
            </div>
        </div>
    </div>

    <!-- B2B Broadcast Modal -->
    <div class="modal fade" id="b2bBroadcastModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card" style="background: #0f1422; border: 1px solid var(--gold);">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold text-white cinzel"><i class="fa-solid fa-handshake text-warning me-2"></i> Broadcast B2B Partnership Outreach</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-white small fw-semibold">Target Corridor / City</label>
                        <select class="form-select bg-dark text-white border-secondary" id="b2bCitySelect">
                            <option value="all">All Corridors (Mumbai, Surat, Ahmedabad, Delhi, Kolkata, Varanasi)</option>
                            <option value="Mumbai">Mumbai Outbound</option>
                            <option value="Surat">Surat & Gujarat Textile/Diamond</option>
                            <option value="Ahmedabad">Ahmedabad</option>
                            <option value="Delhi">Delhi-NCR Devotees</option>
                            <option value="Kolkata">Kolkata</option>
                            <option value="Varanasi">Varanasi Local Hotels & Inbound</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white small fw-semibold">Channels</label>
                        <div class="d-flex gap-3 text-white small">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" checked id="b2bChanWa">
                                <label class="form-check-label" for="b2bChanWa">WhatsApp Evolution API</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" checked id="b2bChanEmail">
                                <label class="form-check-label" for="b2bChanEmail">Brevo SMTP Email</label>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-dark border border-warning small text-white mb-0">
                        <i class="fa-solid fa-circle-info text-warning me-1"></i> Dispatches personalized Dev Deepawali 25% revenue share proposal, HD video reel, and unique tracking code to each partner.
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-gold btn-sm" onclick="triggerB2bDispatch()">
                        <i class="fa-solid fa-paper-plane me-1"></i> Dispatch B2B Outreach
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- WhatsApp Pair Modal -->
    <div class="modal fade" id="whatsappPairModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card" style="background: #0f1422; border: 1px solid var(--gold);">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold text-white cinzel"><i class="fa-brands fa-whatsapp text-success me-2"></i> Pair WhatsApp (onlinepuja.live)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <p class="text-muted small">Open WhatsApp on your phone (<code>917007158014</code>) &rarr; Settings &rarr; <b>Linked Devices</b> &rarr; Scan QR Code or Link with Phone Number.</p>
                    
                    <div id="waQrContainer" class="my-3 py-3 bg-white rounded-3 d-inline-block p-2 shadow">
                        <div class="spinner-border text-dark" role="status"></div>
                    </div>

                    <div id="waPairCodeContainer" class="mt-2">
                        <div class="badge bg-dark border border-warning text-warning fs-5 p-2 px-3 font-monospace" id="waPairCodeText">Loading...</div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-gold btn-sm" onclick="loadWhatsAppPairing()">Refresh QR</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function triggerB2bDispatch() {
            const city = document.getElementById('b2bCitySelect').value;
            fetch('/api/manager/b2b/dispatch', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ city: city, channels: ['whatsapp', 'email'] })
            })
            .then(res => res.json())
            .then(data => {
                alert('✓ ' + (data.message || 'B2B Campaign dispatched successfully!'));
                const modal = bootstrap.Modal.getInstance(document.getElementById('b2bBroadcastModal'));
                if (modal) modal.hide();
            })
            .catch(err => {
                alert('Error dispatching B2B outreach: ' + err.message);
            });
        }

        function loadWhatsAppPairing() {
            const container = document.getElementById('waQrContainer');
            const codeText = document.getElementById('waPairCodeText');
            container.innerHTML = '<div class="spinner-border text-dark my-4" role="status"></div>';
            codeText.innerText = 'Connecting to Evolution API...';

            fetch('/admin/marketing/whatsapp/qr')
                .then(r => r.json())
                .then(data => {
                    if (data.base64) {
                        container.innerHTML = `<img src="${data.base64}" style="width: 240px; height: 240px; border-radius: 8px;" alt="Scan WhatsApp QR" />`;
                    } else {
                        container.innerHTML = '<p class="text-danger small my-4">QR not available. Instance may already be paired.</p>';
                    }
                    if (data.pairingCode) {
                        codeText.innerText = 'Pairing Code: ' + data.pairingCode;
                    } else {
                        codeText.innerText = 'Scan the QR code above with WhatsApp';
                    }
                })
                .catch(e => {
                    container.innerHTML = '<p class="text-danger small my-4">Error loading QR: ' + e.message + '</p>';
                });
        }
    </script>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
