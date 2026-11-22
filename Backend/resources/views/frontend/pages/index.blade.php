@extends('frontend.layout.master')

@section('title', 'Online Puja Booking & Online Pandit Booking in Varanasi | Authentic Vedic Pujas')
@section('meta_description', 'India\'s #1 platform for online puja booking & online pandit booking for puja in Varanasi. Perform authentic Vedic rituals at Kashi Vishwanath with live video and sanctum Prasad delivery.')
@section('meta_keywords', 'online puja booking, online pandit booking for puja, book pandit online, pandit in varanasi for puja, kashi vishwanath rudrabhishek booking, dev deepawali deepdaan, vedic puja service')
@section('canonical_url', url('/'))

@section('schema_markup')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "WebSite",
      "@id": "{{ url('/') }}/#website",
      "url": "{{ url('/') }}",
      "name": "OnlinePuja Varanasi",
      "description": "Online Puja Booking & Online Pandit Booking for Authentic Vedic Pujas in Varanasi",
      "potentialAction": {
        "@type": "SearchAction",
        "target": "{{ url('/puja/category/list') }}?q={search_term_string}",
        "query-input": "required name=search_term_string"
      }
    },
    {
      "@type": "LocalBusiness",
      "@id": "{{ url('/') }}/#organization",
      "name": "OnlinePuja",
      "url": "{{ url('/') }}",
      "logo": "{{ asset('build/assets/images/logo.png') }}",
      "image": "{{ asset('build/assets/images/logo.png') }}",
      "telephone": "+91-9999999999",
      "priceRange": "₹500 - ₹21000",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "Dashashwamedh Ghat Road",
        "addressLocality": "Varanasi",
        "addressRegion": "Uttar Pradesh",
        "postalCode": "221001",
        "addressCountry": "IN"
      },
      "geo": {
        "@type": "GeoCoordinates",
        "latitude": 25.3109,
        "longitude": 83.0107
      },
      "openingHoursSpecification": {
        "@type": "OpeningHoursSpecification",
        "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],
        "opens": "05:00",
        "closes": "22:00"
      },
      "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.9",
        "reviewCount": "1420"
      }
    }
  ]
}
</script>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('public/frontend/css/homepage.css?v=1.1.0') }}">
@endpush

@section('content')

@php
$pt = ucfirst($professionTitle);
$an = ucfirst($appname);
$intake = $getIntakeForm['recordList'][0] ?? [];
$pRes = $Tpanchangs['response'] ?? [];
$pAdv = $pRes['advanced_details'] ?? [];
$todaysDate = date('D M d Y');
$defaultImg = asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/images/user-img-new.png');
$productColors = ['#81ecec', '#fed7aa', '#fab1a0'];

// Helper: resolve image src (absolute URL or relative path)
if (!function_exists('imgSrc')) {
function imgSrc($path, $default = null) {
if (!$path) return $default ?? asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/images/user-img-new.png');
return Str::startsWith($path, ['http://','https://']) ? $path : asset(ltrim($path, '/'));
}
}
@endphp

{{-- ─── Styles ──────────────────────────────────────────────────────────────── --}}
<style>
    .modal-content {
        background-clip: border-box !important;
        border: none !important;
        border-radius: 0 !important;
    }

    .detailed-link:hover {
        color: #fff !important;
    }

    .read-more {
        color: blue;
    }

    .decoration:hover {
        text-decoration: none !important;
        color: black !important;
    }

    .video-title {
        height: 60px;
    }

    h2.heading span[aria-expanded="true"] .fa-chevron-up {
        border: 2px solid #65a9fd;
        border-radius: 50%;
        padding: 5px;
        -webkit-text-stroke: 2px #fff5f6;
        font-size: 24px;
    }

    @media (min-width: 768px) {
        .astrology-video-carousel {
            justify-items: center;
        }
    }

    #videoModal .close {
        font-size: 2rem;
        position: absolute;
        right: 0;
        top: -2.5rem;
        z-index: 1;
        color: #fff;
        opacity: 1;
        transition: color 0.3s ease;
    }

    #videoModal .close:hover {
        color: #ccc;
    }

    #videoModal .modal-header {
        padding: 0;
        border: none;
    }

    #videoModal .modal-body {
        padding: 0;
    }

    @media (max-width: 768px) {
        #videoModal .close {
            font-size: 1.5rem;
            top: -2rem;
        }
    }

    @media (max-width: 576px) {
        #videoModal .close {
            font-size: 1.25rem;
            top: -0.5rem;
        }
    }

    /* Horoscope */
    .as_padderBottom30 {
        padding-bottom: 30px;
    }

    .as_padderTop80 {
        padding-top: 80px;
    }

    .daily_horoscope_box {
        text-align: center;
        padding-bottom: 10px;
        display: block;
        box-shadow: 0 0 12px #9289894f;
        margin-top: 65px;
        border-radius: 5px 30px 5px 30px;
        cursor: pointer;
        background: azure;
    }

    .daily_horoscope_box img {
        height: 90px;
        margin-top: -45px;
        filter: drop-shadow(0.35px 0.35px 4.4px rgba(0, 0, 0, 0.3));
        border-radius: 100px;
        border: 1px solid rosybrown;
        padding: 5px;
    }

    /* Marquee */
    .marquee-wrapper {
        width: 100%;
        overflow: hidden;
    }

    .marquee {
        display: flex;
        animation: marquee 10s linear infinite;
    }

    .marquee a {
        flex-shrink: 0;
        text-decoration: none;
    }

    .marquee a:hover,
    .marquee:hover {
        animation-play-state: paused;
    }

    @keyframes marquee {
        0% {
            transform: translateX(0);
        }

        100% {
            transform: translateX(-50%);
        }
    }

    .youtube-icon {
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 40px;
        height: 40px;
    }

    .video-card {
        position: relative;
    }
</style>

{{-- ─── Astrotalk-like Hero Banner ─────────────────────────────────────────── --}}
<section class="hero-banner-section">
    <!-- Starry & Glowing Background Nodes -->
    <div class="hero-bg-glow-nodes">
        <div class="glow-node yellow"></div>
        <div class="glow-node pink"></div>
        <div class="glow-node green"></div>
        <div class="glow-node orange"></div>
    </div>

    <div class="container hero-container">
        <div class="row align-items-center">
            <!-- Left Side: Content & CTA -->
            <div class="col-lg-6 hero-content-left text-lg-left text-center">
                <div class="badge-tagline mb-3">
                    <span class="icon">✨</span> First Session with {{ $pt }} is <strong class="ml-1"> FREE!</strong>
                </div>
                <h1 class="hero-title">
                    Talk to <span class="gradient-text">{{ $pt }}s</span><br>right now.
                </h1>
                <p class="hero-subtitle">
                    Connect with verified experts online. Get instant and accurate guidance for career, marriage, love, and finance from India's best {{ strtolower($pt) }}s.
                </p>
                <div class="hero-buttons d-flex flex-sm-row flex-column justify-content-lg-start justify-content-center align-items-center gap-3">
                    <a href="{{ route('front.chatList') }}" class="btn-hero-cta btn-chat-now">
                        <span class="btn-icon"><i class="fa-solid fa-comment-dots"></i></span>
                        <div class="btn-text">
                            <span class="title">Chat with {{ $pt }}</span>
                            <span class="subtitle">First session free</span>
                        </div>
                    </a>
                    <a href="{{ route('front.talkList') }}" class="btn-hero-cta btn-call-now">
                        <span class="btn-icon"><i class="fa-solid fa-phone"></i></span>
                        <div class="btn-text">
                            <span class="title">Call with {{ $pt }}</span>
                            <span class="subtitle">Talk to experts instantly</span>
                        </div>
                    </a>
                </div>
                <div class="hero-trust-badges d-flex flex-wrap justify-content-lg-start justify-content-center align-items-center mt-4">
                    <div class="trust-badge">
                        <i class="fa-solid fa-user-shield"></i> 100% Private
                    </div>
                    <div class="trust-badge">
                        <i class="fa-solid fa-circle-check"></i> Verified Experts
                    </div>
                    <div class="trust-badge">
                        <i class="fa-solid fa-lock"></i> Safe Payments
                    </div>
                </div>
            </div>

            <!-- Right Side: Rotating Taramandal (Zodiac Wheel) -->
            <div class="col-lg-6 hero-visual-right d-flex justify-content-center align-items-center mt-lg-0 mt-5">
                <div class="taramandal-wrapper">
                    <!-- Spinning Zodiac Ring 1 -->
                    <div class="taramandal-ring outer-ring"></div>
                    <!-- Spinning Zodiac Ring 2 with Signs -->
                    <div class="taramandal-zodiac-wheel">
                        @php
                        $signs = [
                        '♈︎' => 'Aries', '♉︎' => 'Taurus', '♊︎' => 'Gemini', '♋︎' => 'Cancer',
                        '♌︎' => 'Leo', '♍︎' => 'Virgo', '♎︎' => 'Libra', '♏︎' => 'Scorpio',
                        '♐︎' => 'Sagittarius', '♑︎' => 'Capricorn', '♒︎' => 'Aquarius', '♓︎' => 'Pisces'
                        ];
                        $angle = 0;
                        @endphp
                        @foreach ($signs as $symbol => $name)
                        <div class="zodiac-item" style="--angle: {{ $angle }}deg;">
                            <span class="symbol">{{ $symbol }}</span>
                            <span class="name">{{ $name }}</span>
                        </div>
                        @php $angle += 30; @endphp
                        @endforeach
                    </div>

                    <!-- Core Glow & centerpiece logo -->
                    <div class="taramandal-center">
                        <div class="center-glow"></div>
                        @php
                            $homeBanner = null;
                            if (isset($systemFlags)) {
                                $homeBanner = $systemFlags->get('HomeBannerImage') 
                                    ?? $systemFlags->get('homeBannerImage') 
                                    ?? $systemFlags->get('home_banner_image');
                            }
                            $centerImg = ($homeBanner && $homeBanner->value) 
                                ? imgSrc($homeBanner->value) 
                                : asset('public/frontend/homeimage/home-analyze.png');
                        @endphp
                        <img src="{{ $centerImg }}" alt="Zodiac Central" class="taramandal-logo img-fluid" width="110" height="110" fetchpriority="high" decoding="async" onerror="this.onerror=null; this.src='{{ asset('public/frontend/homeimage/home-analyze.png') }}';">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ─── Stats Ticker Bar ────────────────────────────────────────────────── --}}
<div class="stats-ticker-bar">
    <div class="stats-marquee-content">
        <!-- Set 1 -->
        <div class="ticker-item">
            <span class="dot"></span>
            <strong>Available 24x7</strong> astrologers online round the clock, even at 3 AM
        </div>
        <div class="ticker-item">
            <span class="dot"></span>
            <strong>48,726+ astrologers</strong> available for chat & call consultation
        </div>
        <div class="ticker-item">
            <span class="dot"></span>
            <strong>120.2 Million+</strong> customers trust {{ $an }} for guidance
        </div>
        <!-- Set 2 -->
        <div class="ticker-item">
            <span class="dot"></span>
            <strong>Available 24x7</strong> astrologers online round the clock, even at 3 AM
        </div>
        <div class="ticker-item">
            <span class="dot"></span>
            <strong>48,726+ astrologers</strong> available for chat & call consultation
        </div>
        <div class="ticker-item">
            <span class="dot"></span>
            <strong>120.2 Million+</strong> customers trust {{ $an }} for guidance
        </div>
        <!-- Set 3 -->
        <div class="ticker-item">
            <span class="dot"></span>
            <strong>Available 24x7</strong> astrologers online round the clock, even at 3 AM
        </div>
        <div class="ticker-item">
            <span class="dot"></span>
            <strong>48,726+ astrologers</strong> available for chat & call consultation
        </div>
        <div class="ticker-item">
            <span class="dot"></span>
            <strong>120.2 Million+</strong> customers trust {{ $an }} for guidance
        </div>
        <!-- Set 4 -->
        <div class="ticker-item">
            <span class="dot"></span>
            <strong>Available 24x7</strong> astrologers online round the clock, even at 3 AM
        </div>
        <div class="ticker-item">
            <span class="dot"></span>
            <strong>48,726+ astrologers</strong> available for chat & call consultation
        </div>
        <div class="ticker-item">
            <span class="dot"></span>
            <strong>120.2 Million+</strong> customers trust {{ $an }} for guidance
        </div>
    </div>
</div>

{{-- ─── Interactive Horoscope Section ──────────────────────────────────────── --}}
<section class="interactive-horoscope-section">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h2 class="section-title">Pick your sign. Read your day.</h2>
            <p class="section-subtitle">Twelve signs, one rotation. Or click yours to lock the wheel.</p>
        </div>

        <div class="row align-items-center mt-5">
            <!-- Left Side: Interactive Zodiac Wheel -->
            <div class="col-lg-6 d-flex justify-content-center align-items-center">
                <div class="horoscope-wheel-wrapper">
                    <div class="horoscope-wheel-ring"></div>
                    <div class="horoscope-wheel">
                        @php
                        $zodiacUnicode = [
                        'Aries' => '♈︎', 'Taurus' => '♉︎', 'Gemini' => '♊︎', 'Cancer' => '♋︎',
                        'Leo' => '♌︎', 'Virgo' => '♍︎', 'Libra' => '♎︎', 'Scorpio' => '♏︎',
                        'Sagittarius' => '♐︎', 'Capricorn' => '♑︎', 'Aquarius' => '♒︎', 'Pisces' => '♓︎'
                        ];
                        $angle = 0;
                        @endphp
                        @foreach($horosign as $index => $sign)
                        <div class="zodiac-wheel-item {{ $sign->name === 'Aries' ? 'active' : '' }}"
                            data-id="{{ $sign->id }}"
                            data-name="{{ $sign->name }}"
                            data-slug="{{ $sign->slug }}"
                            data-angle="{{ $angle }}"
                            style="--angle: {{ $angle }}deg;">
                            <div class="zodiac-symbol-circle">
                                {{ $zodiacUnicode[$sign->name] ?? '♈︎' }}
                            </div>
                            <span class="zodiac-name-label">{{ $sign->name }}</span>
                        </div>
                        @php $angle += 30; @endphp
                        @endforeach
                    </div>
                    <div class="horoscope-wheel-center">
                        <div class="center-icon">♈︎</div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Horoscope Detail Card -->
            <div class="col-lg-6 mt-lg-0 mt-5">
                <div class="horoscope-detail-card shadow">
                    <div class="card-loader d-none">
                        <div class="spinner-border text-warning" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>

                    <div class="horoscope-card-content">
                        <span class="sign-meta text-uppercase font-weight-bold" id="horo-meta">FIRE · MAR 21 - APR 19</span>
                        <h3 class="sign-title font-weight-extrabold mt-1" id="horo-title">Aries ✨</h3>
                        <span class="sign-today text-muted text-uppercase d-block mb-3">Today</span>

                        <!-- Ratings list -->
                        <div class="horoscope-ratings d-flex flex-wrap gap-4 mb-4">
                            <div class="rating-item">
                                <span class="label">MOOD</span>
                                <span class="value" id="horo-mood">Active</span>
                            </div>
                            <div class="rating-item">
                                <span class="label">LOVE</span>
                                <div class="stars" id="horo-love-stars">
                                    <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                                </div>
                            </div>
                            <div class="rating-item">
                                <span class="label">CAREER</span>
                                <div class="stars" id="horo-career-stars">
                                    <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                                </div>
                            </div>
                            <div class="rating-item">
                                <span class="label">MONEY</span>
                                <div class="stars" id="horo-money-stars">
                                    <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star-half-stroke"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Reading Description -->
                        <p class="horoscope-description mb-4" id="horo-desc">
                            Your day will hold promising opportunities. Keep your focus on your professional targets.
                        </p>

                        <div class="lucky-number-info mb-2 d-flex align-items-center">
                            <span class="label mr-2 text-muted">Lucky Number:</span>
                            <span class="number-value font-weight-bold" id="horo-number-value">--</span>
                        </div>

                        <div class="lucky-color-info mb-4 d-flex align-items-center">
                            <span class="label mr-2 text-muted">Lucky Color:</span>
                            <span class="color-dot mr-2" id="horo-color-dot" style="background-color: #ef4444; width: 14px; height: 14px; border-radius: 50%; display: inline-block;"></span>
                            <span class="color-name font-weight-bold" id="horo-color-name">Red</span>
                        </div>

                        <!-- Actions -->
                        <div class="horoscope-card-actions d-flex gap-3">
                            <a href="#" class="btn-read-forecast" id="horo-read-link">Read full forecast</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ─── 13,000+ Best Astrologers Section ───────────────────────────────────── --}}
@if (!empty($getAstrologer) && count($getAstrologer) > 0)
<section class="dynamic-astrologers-section py-5">
    <div class="container">
        <div class="section-header mb-4">
            <span class="sub-badge font-weight-bold text-uppercase" style="color: #6b7280; font-size: 13px; letter-spacing: 1px;">Live Now</span>
            <h2 class="section-title mt-1 font-weight-extrabold" style="font-size: 32px; color: #111827; letter-spacing: -0.5px;">13,000+ best astrologers, ready now.</h2>
        </div>

        <div class="dynamic-astrologers-row">
            @foreach ($getAstrologer as $astro)
            @php
            $astroImg = imgSrc($astro->profileImage ?? null, $defaultImg);
            $skills = $astro->primarySkill ? implode(', ', array_slice(explode(',', $astro->primarySkill), 0, 3)) : 'Astrology';
            $langs = $astro->languageKnown ? implode(', ', array_slice(explode(',', $astro->languageKnown), 0, 2)) : 'English';
            $ratingVal = $astro->rating ? number_format($astro->rating, 2) : '5.00';
            $ordersCount = $astro->reviews ? ($astro->reviews * 8 + 120) . '+' : '100+';
            @endphp
            <div class="dyn-astro-card">
                <div class="dyn-astro-card-top d-flex gap-3 align-items-center">
                    <div class="dyn-astro-avatar-container">
                        <img src="{{ $astroImg }}" alt="{{ $astro->name }}" class="dyn-astro-avatar" width="60" height="60" loading="lazy" decoding="async" onerror="this.onerror=null; this.src='{{ $defaultImg }}';">
                        @if ($astro->is_boosted == 1)
                        <span class="dyn-celebrity-badge">CELEBRITY</span>
                        @endif
                    </div>
                    <div class="dyn-astro-basic-info">
                        <h4 class="dyn-astro-name mb-1 text-truncate" style="max-width: 220px;" title="{{ $astro->name }}">{{ $astro->name }}</h4>
                        <p class="dyn-astro-skills text-muted mb-1">{{ $skills }}</p>
                        <div class="dyn-astro-rating d-flex align-items-center gap-1">
                            <span class="star-icon">★</span>
                            <span class="rating-val font-weight-bold">{{ $ratingVal }}</span>
                            <span class="orders-count text-muted ml-2">{{ $ordersCount }} orders</span>
                        </div>
                    </div>
                </div>

                <div class="dyn-astro-card-middle my-3">
                    <div class="info-row d-flex justify-content-between">
                        <span class="info-label">Languages</span>
                        <span class="info-value text-truncate" style="max-width: 180px;" title="{{ $langs }}">{{ $langs }}</span>
                    </div>
                    <div class="info-row d-flex justify-content-between mt-2">
                        <span class="info-label">Experience</span>
                        <span class="info-value">{{ $astro->experienceInYears }} years</span>
                    </div>
                </div>

                <div class="dyn-astro-card-bottom d-flex justify-content-between align-items-center pt-2">
                    <div class="dyn-astro-price">
                        <span class="price-val font-weight-extrabold">{{ $currency->value ?? '₹' }}{{ number_format($astro->charge) }}</span><span class="price-unit">/min</span>
                    </div>
                    <a href="{{ route('front.astrologerDetails', ['slug' => $astro->slug ?: ($astro->id ?? 1)]) }}" class="dyn-chat-btn">
                        Chat
                    </a>
                </div>
            </div>
            @endforeach
        </div>

        <div class="text-center mt-4">
            <a href="{{ route('front.chatList') }}" class="btn-more-astrologers">
                More Astrologers →
            </a>
        </div>
    </div>
</section>
@endif

{{-- ─── Six Ways to Get Life Answers Section ────────────────────────────────── --}}
<section class="life-answers-section py-5">
    <div class="container">
        <div class="section-header text-center mb-5">
            <span class="sub-badge font-weight-bold text-uppercase" style="color: #6b7280; font-size: 13px; letter-spacing: 1px;">Everything Astrology, In One App</span>
            <h2 class="section-title mt-2 font-weight-extrabold" style="font-size: 38px; color: #111827; letter-spacing: -0.5px;">Six ways to get life answers.</h2>
        </div>

        <div class="row g-4">
            <!-- Card 1: Daily Horoscope -->
            <div class="col-lg-4 col-md-6 mb-4">
                <a href="{{ route('front.horoScope') }}" class="life-answer-card horoscope text-decoration-none d-block">
                    <div class="card-icon-wrapper bg-violet">
                        <i class="fa-solid fa-bullseye"></i>
                    </div>
                    <h3 class="card-title font-weight-bold">Daily Horoscope</h3>
                    <p class="card-desc text-muted mb-0">Your personalised daily star forecast.</p>
                </a>
            </div>

            <!-- Card 2: Today Panchang -->
            <div class="col-lg-4 col-md-6 mb-4">
                <a href="{{ route('front.getPanchang') }}" class="life-answer-card panchang text-decoration-none d-block">
                    <div class="card-icon-wrapper bg-green">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                    <h3 class="card-title font-weight-bold">Today Panchang</h3>
                    <p class="card-desc text-muted mb-0">Auspicious timings, tithi & nakshatra.</p>
                </a>
            </div>

            <!-- Card 3: Free Kundli -->
            <div class="col-lg-4 col-md-6 mb-4">
                <a href="{{ route('front.getkundali') }}" class="life-answer-card kundli text-decoration-none d-block">
                    <div class="card-icon-wrapper bg-pink">
                        <i class="fa-solid fa-dharmachakra"></i>
                    </div>
                    <h3 class="card-title font-weight-bold">Free Kundli</h3>
                    <p class="card-desc text-muted mb-0">Generate your birth chart in 30s.</p>
                </a>
            </div>

            <!-- Card 4: Kundli Match -->
            <div class="col-lg-4 col-md-6 mb-4">
                <a href="{{ route('front.kundaliMatch') }}" class="life-answer-card kundlimatch text-decoration-none d-block">
                    <div class="card-icon-wrapper bg-orange">
                        <i class="fa-solid fa-heart"></i>
                    </div>
                    <h3 class="card-title font-weight-bold">Kundli Match</h3>
                    <p class="card-desc text-muted mb-0">Compatibility scan, 36 gunas.</p>
                </a>
            </div>

            <!-- Card 5: Compatibility -->
            <div class="col-lg-4 col-md-6 mb-4">
                <a href="{{ route('front.horoScope') }}" class="life-answer-card compatibility text-decoration-none d-block">
                    <div class="card-icon-wrapper bg-yellow">
                        <i class="fa-solid fa-infinity"></i>
                    </div>
                    <h3 class="card-title font-weight-bold">Compatibility</h3>
                    <p class="card-desc text-muted mb-0">Check your zodiac compatibility.</p>
                </a>
            </div>

            <!-- Card 6: Online Puja Store -->
            <div class="col-lg-4 col-md-6 mb-4">
                <a href="{{ route('front.getproducts') }}" class="life-answer-card store text-decoration-none d-block">
                    <div class="card-icon-wrapper bg-cyan">
                        <i class="fa-solid fa-gem"></i>
                    </div>
                    <h3 class="card-title font-weight-bold">Online Puja Store</h3>
                    <p class="card-desc text-muted mb-0">Gemstones, rudraksh, remedies.</p>
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ─── Redesigned Blogs Section ───────────────────────────────────────────── --}}
@if ($blog->isNotEmpty())
<section class="journal-blogs-section py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap">
            <div class="mb-3 mb-md-0">
                <span class="sub-badge font-weight-bold text-uppercase" style="color: #6b7280; font-size: 13px; letter-spacing: 1px;">From The Journal</span>
                <h2 class="section-title mt-1 font-weight-extrabold" style="font-size: 36px; color: #111827; letter-spacing: -0.5px; margin-bottom: 0;">Read between the stars.</h2>
            </div>
            <div>
                <a href="{{ route('front.getBlog') }}" class="btn-all-blogs">
                    All blogs →
                </a>
            </div>
        </div>

        <div class="row g-4">
            @foreach ($blog as $bloglist)
            @php
            // Simulate views counter deterministically based on title length
            $views = number_format(($bloglist->id * 4721 + 12053) % 95000 + 5000);
            @endphp
            <div class="col-lg-3 col-md-6 mb-4">
                <a href="{{ route('front.getBlogDetails', ['slug' => $bloglist->slug]) }}" class="journal-blog-card text-decoration-none d-block">
                    <div class="journal-blog-img-wrapper">
                        <img class="journal-blog-img" src="{{ imgSrc($bloglist->blogImage) }}" alt="{{ $bloglist->title }}" width="400" height="240" loading="lazy" decoding="async">
                    </div>
                    <div class="journal-blog-content p-3">
                        <h3 class="journal-blog-title font-weight-bold">{{ $bloglist->title }}</h3>
                        <span class="journal-blog-views text-muted d-block mt-3">{{ $views }} views</span>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ─── Testimonials (What People Say) Section ──────────────────────────────── --}}
<section class="testimonials-section py-5">
    <div class="container-fluid px-0">
        <div class="section-header text-center mb-5">
            <span class="sub-badge font-weight-bold text-uppercase" style="color: #6b7280; font-size: 13px; letter-spacing: 1px;">What People Say</span>
            <h2 class="section-title mt-2 font-weight-extrabold" style="font-size: 38px; color: #111827; letter-spacing: -0.5px;">Stories from the other side of the chat.</h2>
        </div>

        @php
        $row1 = [
        ['name' => 'Aarav Mehta', 'loc' => 'Mumbai', 'char' => 'A', 'bg' => '#f59e0b', 'text' => 'The career guidance I received was incredibly accurate. The astrologer predicted my job promotion down to the exact month!'],
        ['name' => 'Priya Sharma', 'loc' => 'New Delhi', 'char' => 'P', 'bg' => '#10b981', 'text' => 'I was highly skeptical about online consultations, but the depth of analysis and practical remedies suggested here completely changed my perspective.'],
        ['name' => 'Rahul Krishnan', 'loc' => 'Bangalore', 'char' => 'R', 'bg' => '#3b82f6', 'text' => 'Getting my Kundli matching done before marriage was super simple. The astrologer explained everything in detail and cleared all our doubts.'],
        ['name' => 'Sneha Patel', 'loc' => 'London, UK', 'char' => 'S', 'bg' => '#ec4899', 'text' => 'Amazing interface and brilliant customer support. The daily horoscope notifications keep me motivated and positive throughout the day.'],
        ['name' => 'David Wilson', 'loc' => 'New York, USA', 'char' => 'D', 'bg' => '#8b5cf6', 'text' => 'I had a fantastic session regarding my business venture. The advice was highly practical and structured. Definitely worth the recommendation!'],
        ['name' => 'Ananya Gupta', 'loc' => 'Kolkata', 'char' => 'A', 'bg' => '#ef4444', 'text' => 'The live chat option is so convenient. Connecting with top astrologers instantly saved me a lot of time. Best app in the market!']
        ];

        $row2 = [
        ['name' => 'Vikram Singh', 'loc' => 'Jaipur', 'char' => 'V', 'bg' => '#14b8a6', 'text' => 'Their gemstone recommendation worked wonders for my health and focus. Genuine astrologers who actually listen to your problems.'],
        ['name' => 'Meera Nair', 'loc' => 'Kochi', 'char' => 'M', 'bg' => '#ef4444', 'text' => 'This app is a lifesaver. The consultation for my relationship issues was very calming and gave me exactly the guidance I needed.'],
        ['name' => 'Aditya Rao', 'loc' => 'Hyderabad', 'char' => 'A', 'bg' => '#8b5cf6', 'text' => 'Highly experienced experts! The analysis of my Dasha periods was so precise. I now have a clearer roadmap for my financial planning.'],
        ['name' => 'Elena Rostova', 'loc' => 'Dubai, UAE', 'char' => 'E', 'bg' => '#ec4899', 'text' => 'Simple, fast, and extremely accurate. Love the Clean UI and ease of choosing astrologers based on their reviews and ratings.'],
        ['name' => 'Rohan Deshmukh', 'loc' => 'Pune', 'char' => 'R', 'bg' => '#3b82f6', 'text' => 'Excellent experience with the online Pooja service. The entire process was conducted seamlessly with proper rituals. Extremely satisfied.'],
        ['name' => 'Komalpreet Kaur', 'loc' => 'Chandigarh', 'char' => 'K', 'bg' => '#10b981', 'text' => 'I consult the same astrologer every month. She is like a life coach to me. Her predictions have always guided me through tough times.']
        ];
        @endphp

        <!-- Row 1: Scrolling Left -->
        <div class="testimonial-marquee-container mb-4">
            <div class="testimonial-marquee-track left-sliding">
                @foreach (array_merge($row1, $row1, $row1) as $item)
                <div class="testimonial-card">
                    <div class="t-card-header d-flex align-items-center gap-3">
                        <div class="t-avatar d-flex align-items-center justify-content-center" style="background-color: {{ $item['bg'] }}; width: 44px; height: 44px; border-radius: 50%; color: white; font-weight: 700; font-size: 18px;">
                            {{ $item['char'] }}
                        </div>
                        <div class="t-meta ml-3">
                            <h4 class="t-name mb-0 font-weight-bold" style="font-size: 15px; color: #111827; margin: 0;">{{ $item['name'] }}</h4>
                            <span class="t-loc text-muted" style="font-size: 12px; display: block; margin: 2px 0;">{{ $item['loc'] }}</span>
                            <div class="t-stars" style="color: #fbbf24; font-size: 11px;">
                                <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                            </div>
                        </div>
                    </div>
                    <p class="t-text mt-3 mb-0 text-muted" style="font-size: 13px; line-height: 1.5;">"{{ $item['text'] }}"</p>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Row 2: Scrolling Right -->
        <div class="testimonial-marquee-container">
            <div class="testimonial-marquee-track right-sliding">
                @foreach (array_merge($row2, $row2, $row2) as $item)
                <div class="testimonial-card">
                    <div class="t-card-header d-flex align-items-center gap-3">
                        <div class="t-avatar d-flex align-items-center justify-content-center" style="background-color: {{ $item['bg'] }}; width: 44px; height: 44px; border-radius: 50%; color: white; font-weight: 700; font-size: 18px;">
                            {{ $item['char'] }}
                        </div>
                        <div class="t-meta ml-3">
                            <h4 class="t-name mb-0 font-weight-bold" style="font-size: 15px; color: #111827; margin: 0;">{{ $item['name'] }}</h4>
                            <span class="t-loc text-muted" style="font-size: 12px; display: block; margin: 2px 0;">{{ $item['loc'] }}</span>
                            <div class="t-stars" style="color: #fbbf24; font-size: 11px;">
                                <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                            </div>
                        </div>
                    </div>
                    <p class="t-text mt-3 mb-0 text-muted" style="font-size: 13px; line-height: 1.5;">"{{ $item['text'] }}"</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ─── Core Stats Panel Section ───────────────────────────────────────────── --}}
<section class="core-stats-section py-4">
    <div class="container">
        <div class="stats-panel-box d-flex flex-wrap justify-content-around text-center py-4 px-3">
            <div class="stat-col p-3">
                <span class="stat-number">120.2M+</span>
                <span class="stat-label d-block">Total Customers</span>
            </div>
            <div class="stat-col p-3">
                <span class="stat-number">48,726+</span>
                <span class="stat-label d-block">Total Astrologers</span>
            </div>
            <div class="stat-col p-3">
                <span class="stat-number">4.7<span class="coral-star">★</span></span>
                <span class="stat-label d-block">Average Rating</span>
            </div>
            <div class="stat-col p-3">
                <span class="stat-number">1326M+</span>
                <span class="stat-label d-block">Chat/Call Minutes</span>
            </div>
        </div>
    </div>
</section>

{{-- ─── News & Press Section ────────────────────────────────────────────────── --}}
@if ($astrotalkInNews->isNotEmpty())
<section class="press-news-section py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap">
            <div class="mb-3 mb-md-0">
                <span class="sub-badge font-weight-bold text-uppercase" style="color: #6b7280; font-size: 13px; letter-spacing: 1px;">Press & Media</span>
                <h2 class="section-title mt-1 font-weight-extrabold" style="font-size: 36px; color: #111827; letter-spacing: -0.5px; margin-bottom: 0;">{{ $an }} in News</h2>
            </div>
            <!-- Slide controls (for visual consistency with mockup) -->
            <div class="scroll-controls d-none d-md-flex gap-2">
                <button class="scroll-btn prev-btn" onclick="document.querySelector('.press-news-row').scrollBy({left: -300, behavior: 'smooth'})">‹</button>
                <button class="scroll-btn next-btn" onclick="document.querySelector('.press-news-row').scrollBy({left: 300, behavior: 'smooth'})">›</button>
            </div>
        </div>

        <div class="press-news-row">
            @foreach ($astrotalkInNews as $news)
            @php
            $newsImg = imgSrc($news->bannerImage);
            $formattedDate = $news->newsDate ? \Carbon\Carbon::parse($news->newsDate)->format('d M Y') : date('d M Y');
            @endphp
            <div class="news-item-card">
                <a href="{{ $news->link ?? route('news.show', $news->id) }}" target="_blank" class="text-decoration-none d-block h-100">
                    <div class="news-img-wrapper">
                        <img class="news-img" src="{{ $newsImg }}" alt="{{ $news->channel }}" width="300" height="169" loading="lazy" decoding="async">
                    </div>
                    <div class="news-card-content p-3 d-flex flex-column justify-content-between">
                        <h3 class="news-title font-weight-bold">{{ $news->description ?? 'News Headline' }}</h3>
                        <div class="news-meta-row d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                            <span class="news-channel text-uppercase font-weight-bold">{{ $news->channel }}</span>
                            <span class="news-date text-muted">{{ $formattedDate }}</span>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ─── How It Works (Four Taps) Section ───────────────────────────────────── --}}
<section class="how-it-works-section">
    <div class="container">
        <div class="section-header text-center mb-5">
            <span class="sub-badge">How {{ $an }} Works</span>
            <h2 class="section-title">Four taps from question to clarity.</h2>
        </div>

        <div class="steps-flow-container position-relative">
            <!-- Connecting Dotted Line -->
            <div class="steps-dotted-line d-none d-md-block"></div>

            <div class="row text-center justify-content-between">
                <!-- Step 1 -->
                <div class="col-md-3 step-col mb-4 mb-md-0">
                    <div class="step-badge-wrapper position-relative d-inline-block">
                        <div class="step-number-circle">1</div>
                    </div>
                    <h3 class="step-title">Tell us a little about you.</h3>
                    <p class="step-desc">Date, time, place of birth – the three things every chart needs. Takes 30 seconds, no signup wall.</p>
                </div>

                <!-- Step 2 -->
                <div class="col-md-3 step-col mb-4 mb-md-0">
                    <div class="step-badge-wrapper position-relative d-inline-block">
                        <div class="step-number-circle">2</div>
                    </div>
                    <h3 class="step-title">Browse astrologers, live.</h3>
                    <p class="step-desc">Filter by skill, language, price, vibe. Read real reviews. See who is online right now and how they reply.</p>
                </div>

                <!-- Step 3 -->
                <div class="col-md-3 step-col mb-4 mb-md-0">
                    <div class="step-badge-wrapper position-relative d-inline-block">
                        <div class="step-number-circle">3</div>
                    </div>
                    <h3 class="step-title">Chat or call instantly.</h3>
                    <p class="step-desc">No bookings, no waiting. Tap "Connect" and you are in. Pause, resume, or switch astrologers anytime.</p>
                </div>

                <!-- Step 4 -->
                <div class="col-md-3 step-col">
                    <div class="step-badge-wrapper position-relative d-inline-block">
                        <div class="step-number-circle">4</div>
                    </div>
                    <h3 class="step-title">Get clarity, not predictions.</h3>
                    <p class="step-desc">Ask anything – career timing, relationship doubts, that gut feeling. Walk away with a plan, not a guess.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ─── Languages & Specialties Marquee Section ────────────────────────────── --}}
<section class="lang-specialties-marquee-section py-4">
    <div class="container-fluid px-0 text-center">
        <!-- Subtitle -->
        <span class="sub-badge text-uppercase mb-3 d-block">13 Languages · 40+ Specialties</span>

        <!-- Row 1: Specialties Marquee -->
        <div class="marquee-scroller-container mb-3">
            <div class="marquee-scroller-track left-running">
                @php
                $specialtiesList = [];
                if (isset($dynamicSkills) && $dynamicSkills->isNotEmpty()) {
                $specialtiesList = $dynamicSkills->pluck('name')->all();
                }
                if (empty($specialtiesList)) {
                $specialtiesList = ['Reiki Healing', 'Crystal Reading', 'Face Reading', 'Twin Flame', 'Vedic Astrology', 'Tarot Reading', 'Numerology', 'Palmistry', 'Vastu Shastra', 'Psychic Reading', 'Kundli Matching', 'KP System'];
                }
                // Duplicate elements to ensure smooth continuous scrolling marquee
                $displaySpecialties = array_merge($specialtiesList, $specialtiesList, $specialtiesList);
                @endphp
                @foreach ($displaySpecialties as $spec)
                <span class="scroller-item specialty-item">{{ $spec }} <span class="sparkle-star">✦</span></span>
                @endforeach
            </div>
        </div>

        <!-- Row 2: Languages Marquee -->
        <div class="marquee-scroller-container">
            <div class="marquee-scroller-track right-running">
                @php
                $languages = ['ગુજરાતી', 'മലയാളം', 'ଓଡ଼ିଆ', 'অসমীয়া', 'اردو', 'हिन्दी', 'English', 'தமிழ்', 'తెలుగు', 'বাংলা', 'ಕನ್ನಡ', 'मराठी', 'ਪੰਜਾਬੀ'];
                @endphp
                @foreach (array_merge($languages, $languages, $languages) as $lang)
                <span class="scroller-item language-item">{{ $lang }} <span class="bullet-dot">·</span></span>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ─── Online Puja App Download Promo Section ────────────────────────────────── --}}
<section class="app-download-promo-section py-5">
    <div class="container">
        <div class="app-promo-card position-relative overflow-hidden p-md-5">
            <div class="row align-items-center">
                <!-- Left Details -->
                <div class="col-lg-7 promo-text-col">
                    <span class="sub-badge mb-2 d-block">{{ $an }} for iOS & Android</span>
                    <h2 class="promo-title">India’s #1 astrology app.<br>Always with you.</h2>
                    <p class="promo-subtitle">Chat with astrologers anytime. Get daily horoscopes, free kundli, compatibility reports & muhurat alerts – all in one app.</p>

                    <!-- App Badges -->
                    <div class="app-download-buttons">
                        <!-- App Store Badge -->
                        <a href="{{ isset($systemFlags) && $systemFlags->get('AppStore') ? $systemFlags->get('AppStore')->value : '#' }}" class="store-badge-btn app-store-btn" target="_blank">
                            <img src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/images/app-store.png') }}" alt="Download on the App Store" width="140" height="42" loading="lazy">
                        </a>
                        <!-- Play Store Badge -->
                        <a href="{{ isset($systemFlags) && $systemFlags->get('PlayStore') ? $systemFlags->get('PlayStore')->value : '#' }}" class="store-badge-btn google-play-btn" target="_blank">
                            <img src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/images/google-play.png') }}" alt="Get it on Google Play" width="140" height="42" loading="lazy">
                        </a>
                    </div>

                    <!-- Metrics Row -->
                    <div class="promo-metrics-row">
                        <div class="metric-item">
                            <h3 class="metric-value">4.5 <span class="star-gold">★</span></h3>
                            <p class="metric-label">Play Store · 2M+ reviews</p>
                        </div>
                        <div class="metric-item">
                            <h3 class="metric-value">120.2M+</h3>
                            <p class="metric-label">customers</p>
                        </div>
                    </div>
                </div>

                <!-- Right Device Mockup -->
                <div class="col-lg-5 text-center position-relative promo-device-col">
                    <div class="phone-mockup-frame">
                        <div class="phone-notch"></div>
                        <div class="phone-screen" style="background-image: url('{{ asset('public/frontend/homeimage/astrologer_portrait.png') }}');">
                            <div class="phone-app-overlay">
                                <div class="phone-app-header-text">
                                    India's <span>#1</span><br>Astrology App
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ─── Why Astrology? Reader Section ──────────────────────────────────────── --}}
<section class="why-astrology-reader-section">
    <div class="container">
        {{-- ── Section Hero Header ──────────────────────────────────── --}}
        <div class="reader-hero-header">
            <span class="reader-eyebrow">THE {{ strtoupper($an) }} READER</span>
            <h2 class="reader-main-title">WHY ASTROLOGY?<br>Astrology Reveals the Will of God</h2>
            <p class="reader-intro-desc">A short read on the philosophy behind Vedic guidance, how an online consultation actually works, and what makes our {{ $pt }}s worth your time.</p>
        </div>

        {{-- ── Chapter 01 · The Why ─────────────────────────────────── --}}
        <div class="reader-chapter-block">
            <div class="row align-items-start">
                <div class="col-lg-7 order-lg-1 order-2">
                    <span class="chapter-label">CHAPTER 01 · THE WHY</span>
                    <h3 class="chapter-title">Astrology reveals the will of God.</h3>
                    <p class="chapter-body chapter-dropcap">Have you ever felt that things in life sometimes happen at just the right time, like someone is silently guiding you? Astrology helps us understand this. It shows how God's energy flows through planets and stars, shaping our daily lives. This old and trusted knowledge explains that nothing is random; everything has a reason.</p>
                    <p class="chapter-body">The stars often hold clues about our purpose and future. Astrology invites us to see these signs, giving comfort, direction, and a deeper sense of meaning to those who feel unsure about life choices — whether it's work, love, family, or spiritual growth.</p>
                </div>
                <div class="col-lg-5 order-lg-2 order-1 mb-4 mb-lg-0 d-flex justify-content-center">
                    <div class="chapter-illustration ch-illus-sunrise">
                        <div class="ch-illus-orb"></div>
                        <span class="ch-illus-sparkle s1">✦</span>
                        <span class="ch-illus-sparkle s2">+</span>
                        <span class="ch-illus-sparkle s3">✦</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Chapter 02 · The How ─────────────────────────────────── --}}
        <div class="reader-chapter-block">
            <div class="row align-items-start">
                <div class="col-lg-5 order-lg-1 order-1 mb-4 mb-lg-0 d-flex justify-content-center">
                    <div class="chapter-illustration ch-illus-cosmos">
                        <div class="ch-illus-ring ring-1"></div>
                        <div class="ch-illus-ring ring-2"></div>
                        <div class="ch-illus-center-dot"></div>
                    </div>
                </div>
                <div class="col-lg-7 order-lg-2 order-2">
                    <span class="chapter-label">CHAPTER 02 · THE HOW</span>
                    <h3 class="chapter-title">How an online consultation works.</h3>
                    <p class="chapter-body">At {{ $an }}, connecting with a trusted {{ $pt }} takes just a few taps. Share your date, time, and place of birth — and our experts will prepare your personalised birth chart (Kundli) in seconds.</p>
                    <p class="chapter-body">Choose a {{ $pt }} by specialty, language, or ratings. Then start an instant chat, call, or video session. No appointments needed — our {{ $pt }}s are available around the clock, ready to guide you through career decisions, relationships, health concerns, and more.</p>
                </div>
            </div>
        </div>

        {{-- ── Chapter 03 · The Shift ───────────────────────────────── --}}
        <div class="reader-chapter-block">
            <div class="row align-items-start">
                <div class="col-lg-7 order-lg-1 order-2">
                    <span class="chapter-label">CHAPTER 03 · THE SHIFT</span>
                    <h3 class="chapter-title">Why Should You Choose an Online {{ ucfirst($pt) }}?</h3>
                    <p class="chapter-body">Online {{ $pt }}s are just as wise as traditional ones, but give you the comfort of easy access and pocket-friendly prices that suit today's busy life. Connecting with an {{ $pt }} online brings both ancient knowledge and modern convenience into your spiritual practice.</p>
                    <p class="chapter-body">With online {{ $pt }} services, you can connect with experts from all over the country, even if you don't have any nearby. These digital consultations usually cost less than face-to-face meetings, yet the quality of advice stays just as good.</p>
                    <p class="chapter-body">{{ $an }}'s free chat lets you try out different {{ $pt }}s to see who matches your energy and style before choosing a full session. The {{ $an }} free chat feature is perfect for first-timers who want to explore options comfortably and without pressure.</p>
                    <p class="chapter-body">This way, you feel more sure and relaxed about who's guiding you. Most online platforms also check the {{ $pt }}s' qualifications and experience, so you know you're in safe hands.</p>
                    <p class="chapter-body">When using astrology online, your privacy is protected, which helps when dealing with personal matters like love or work. You don't have to worry about others finding out. Many sessions are also recorded, so you can replay and understand them better later.</p>
                </div>
                <div class="col-lg-5 order-lg-2 order-1 mb-4 mb-lg-0">
                    {{-- Astrologer Store Sidebar --}}
                    <div class="reader-sidebar-card">
                        <span class="sidebar-card-eyebrow">THE {{ strtoupper($an) }} STORE</span>
                        <div class="sidebar-astrologer-list">
                            @php $sidebarAstros = $getAstrologer->take(4); @endphp
                            @foreach($sidebarAstros as $sa)
                            @php
                            $saImg = imgSrc($sa->profileImage ?? null, $defaultImg);
                            $saSkills = $sa->primarySkill ? implode(', ', array_slice(explode(',', $sa->primarySkill), 0, 3)) : 'Vedic Astrology';
                            $saLangs = $sa->languageKnown ? implode(', ', array_slice(explode(',', $sa->languageKnown), 0, 3)) : 'English, Hindi';
                            @endphp
                            <div class="sidebar-astro-row">
                                <img src="{{ $saImg }}" alt="{{ $sa->name }}" class="sidebar-astro-avatar" width="46" height="46" loading="lazy" decoding="async">
                                <div class="sidebar-astro-info">
                                    <h5 class="sidebar-astro-name">{{ $sa->name }}</h5>
                                    <p class="sidebar-astro-skill">{{ $saSkills }}</p>
                                    <p class="sidebar-astro-lang">{{ $saLangs }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Chapter 04 · The Daily ───────────────────────────────── --}}
        <div class="reader-chapter-block">
            <div class="row align-items-start">
                <div class="col-lg-5 order-lg-1 order-1 mb-4 mb-lg-0 d-flex justify-content-center">
                    {{-- Zodiac Wheel Illustration --}}
                    <div class="reader-zodiac-panel">
                        <div class="reader-zodiac-wheel">
                            @php
                            $zodiacSymbols = ['♈︎','♉︎','♊︎','♋︎','♌︎','♍︎','♎︎','♏︎','♐︎','♑︎','♒︎','♓︎'];
                            @endphp
                            <div class="rzw-ring"></div>
                            <div class="rzw-center-sun"></div>
                            @foreach($zodiacSymbols as $i => $zs)
                            <span class="rzw-symbol" style="--zangle: {{ $i * 30 }}deg">{{ $zs }}</span>
                            @endforeach
                        </div>
                        <p class="reader-zodiac-caption"><em>Twelve signs · One sky</em></p>
                    </div>
                </div>
                <div class="col-lg-7 order-lg-2 order-2">
                    <span class="chapter-label">CHAPTER 04 · THE DAILY</span>
                    <h3 class="chapter-title">How to Stay Updated With Daily Horoscope Predictions & Zodiac Signs?</h3>
                    <p class="chapter-body">Daily horoscopes are a quiet ritual — a 30-second check-in with the planetary weather for your sign.</p>
                    <div class="reader-fact-cards">
                        <div class="reader-fact-card">Daily horoscope readings help you understand how the stars and planets are affecting your zodiac sign today. This helps you make better choices and avoid small troubles.</div>
                        <div class="reader-fact-card">There are 12 zodiac signs – Aries, Taurus, Gemini, Cancer, Leo, Virgo, Libra, Scorpio, Sagittarius, Capricorn, Aquarius, and Pisces. Each one reacts differently to the movements in the sky.</div>
                        <div class="reader-fact-card">These daily messages guide your actions based on your sign's natural energy and ruling planets.</div>
                        <div class="reader-fact-card">With free horoscope online tools, it's easy to begin your day with guidance about love, career, health, and even your spiritual growth.</div>
                        <div class="reader-fact-card">Many astrology apps give daily updates based on your birth details. This makes the advice feel personal and helpful.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Chapter 05 · The Team ────────────────────────────────── --}}
        <div class="reader-chapter-block">
            <div class="row align-items-start">
                <div class="col-lg-7 order-lg-1 order-2">
                    <span class="chapter-label">CHAPTER 05 · THE TEAM</span>
                    <h3 class="chapter-title">Why Choose Our Astrology Experts?</h3>
                    <p class="chapter-body">Our certified astrology experts blend the ancient wisdom of Vedic astrology with a modern understanding of life and emotions. Each online {{ $pt }} in our team is carefully selected to make sure they truly know astrology and speak with kindness. They also keep learning new things to stay updated through teamwork and regular training.</p>
                    <p class="chapter-body">At the {{ $an }} store, you'll find spiritual items like gemstones, yantras, and puja tools picked by your {{ $pt }}. These products are 100% original and help you connect better with the energies of the planets. Our experts also give you easy, step-by-step instructions to use them safely and correctly.</p>
                    <p class="chapter-body">Your satisfaction is our top priority. That's why we offer different types of consultations to match your style and budget — like simple chats, detailed reports, or quick answers. Our accurate astrology predictions are based on deep study and real experience.</p>
                    <p class="chapter-body">You can also try our free 5-minute astrology session to see how it works before spending more. With honest pricing and a smooth login system, starting your spiritual journey has never been easier.</p>
                </div>
                <div class="col-lg-5 order-lg-2 order-1 mb-4 mb-lg-0">
                    {{-- Store Products Sidebar --}}
                    <div class="reader-sidebar-card">
                        <span class="sidebar-card-eyebrow">THE {{ strtoupper($an) }} STORE</span>
                        <div class="sidebar-product-grid">
                            <div class="sidebar-product-item" style="--sp-color: #3b82f6">
                                <span class="sp-icon">💎</span>
                                <h6 class="sp-name">Gemstones</h6>
                                <p class="sp-desc">Certified, energised, originals only</p>
                            </div>
                            <div class="sidebar-product-item" style="--sp-color: #f59e0b">
                                <span class="sp-icon">🪬</span>
                                <h6 class="sp-name">Yantras</h6>
                                <p class="sp-desc">Hand-etched in copper and brass</p>
                            </div>
                            <div class="sidebar-product-item" style="--sp-color: #6b7280">
                                <span class="sp-icon">📿</span>
                                <h6 class="sp-name">Rudraksha</h6>
                                <p class="sp-desc">Sourced from verified trees in Nepal</p>
                            </div>
                            <div class="sidebar-product-item" style="--sp-color: #92400e">
                                <span class="sp-icon">🪵</span>
                                <h6 class="sp-name">Karungali</h6>
                                <p class="sp-desc">Sacred wood for prosperity and protection</p>
                            </div>
                        </div>
                        <a href="{{ route('front.chatList') }}" class="sidebar-cta-btn">
                            Talk To {{ ucfirst($pt) }}s <span>→</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ─── Reader Conclusion Section ────────────────────────────────────────── --}}
<section class="reader-conclusion-section pb-5">
    <div class="container">
        <div class="reader-conclusion-card">
            <span class="conclusion-label">IN CLOSING</span>
            <h4 class="conclusion-title">Conclusion</h4>
            <div class="conclusion-body">
                <p>Astrology helps us understand God's plan by showing how divine energy flows through our lives and the world. This ancient knowledge brings comfort in tough times and helps us make better choices in love, work, family, and spiritual growth. Thanks to online astrology services, this wisdom is now easy to access from anywhere.</p>
                <p>Your spiritual path becomes easier with the help of expert {{ $pt }}s who understand old traditions and today's challenges. They guide you in reading signs from the universe and acting in tune with cosmic energy for peace and success. Horoscope readings help you stay connected with this flow.</p>
                <p>Astrology doesn't take away your power — it helps you make wise decisions by understanding the stars and combining faith with action.</p>
            </div>
            <div class="text-center mt-4">
                <a href="{{ route('front.chatList') }}" class="btn-conclusion-chat">
                    Begin a free chat →
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ─── FAQ Section ───────────────────────────────────────────────────────── --}}
<section class="home-faq-section py-5">
    <div class="container">
        <div class="faq-header text-center mb-5">
            <span class="faq-eyebrow">FREQUENTLY ASKED</span>
            <h2 class="faq-main-title">Questions, before you ask one.</h2>
        </div>

        <div class="faq-accordion-container">
            <div class="faq-item">
                <button class="faq-trigger">
                    <span class="faq-question">Why Is Astrology So Accurate?</span>
                    <span class="faq-icon-circle"><span class="faq-icon-plus">+</span></span>
                </button>
                <div class="faq-content">
                    <div class="faq-content-inner">
                        <p>Astrology is accurate because it is based on the mathematical positions of celestial bodies at the exact moment of your birth. By mapping the stars, planets, and houses, an experienced astrologer can decode the cosmic blueprint of your life. This ancient Vedic science acts as a mirror to your karma, helping you understand your natural inclinations, strengths, and life cycles.</p>
                    </div>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-trigger">
                    <span class="faq-question">Why Should You Choose {{ $an }} For An Astrology Horoscope?</span>
                    <span class="faq-icon-circle"><span class="faq-icon-plus">+</span></span>
                </button>
                <div class="faq-content">
                    <div class="faq-content-inner">
                        <p>{{ $an }} brings you the most trusted, vetted, and verified astrology experts. Every {{ $pt }} on our platform undergoes a rigorous multi-stage screening process, including detailed background checks and live test readings. We offer 24/7 availability, 100% private and confidential consultations, transparent per-minute pricing, and direct chat/call connectivity in 13+ regional languages.</p>
                    </div>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-trigger">
                    <span class="faq-question">Is Astrology Prediction True?</span>
                    <span class="faq-icon-circle"><span class="faq-icon-plus">+</span></span>
                </button>
                <div class="faq-content">
                    <div class="faq-content-inner">
                        <p>Yes, astrology predictions are based on planetary movements and birth chart analyses. While planets indicate tendencies and influence cosmic energy, your free will and actions (Karma) also play an important role. Astrology gives you the roadmap, warning you of obstacles and highlighting opportunities so you can make informed decisions and steer your life in the right direction.</p>
                    </div>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-trigger">
                    <span class="faq-question">How Can Online Astrology Help Me In Predicting The Future?</span>
                    <span class="faq-icon-circle"><span class="faq-icon-plus">+</span></span>
                </button>
                <div class="faq-content">
                    <div class="faq-content-inner">
                        <p>Online astrology offers instant access to top astrological minds from the comfort of your home. By sharing your birth details (date, time, and place), an astrologer can analyze your Dasha periods and transit paths. This helps predict key events in your career, relationship dynamics, financial health, and overall well-being, helping you plan ahead and make optimal choices.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ─── Live Astrologers ───────────────────────────────────────────────────── --}}
@if ($liveAstrologer->isNotEmpty())
<div class="onlinepuja-live-astrologers slider-bullets py-2 my-md-5 pt-md-5">
    <div class="container">
        <div class="row pb-2">
            <div class="col-sm-12">
                <h2 class="text-center text-black py-3 font-28">LIVE SESSIONS</h2>
                <p class="text-md-center mb-1">
                    Connect with top-rated {{ $pt }}s through live sessions for instant solutions
                </p>
            </div>
        </div>
        <div class="row pt-3">
            <div class="col-sm-12">
                <div class="owl-carousel owl-theme owl-blur owl-mobile">
                    @foreach ($liveAstrologer as $live)
                    @php $liveImg = imgSrc($live->profileImage, $defaultImg); @endphp
                    <div class="item gif-animation-enable mb-3" style="background:url('{{ $liveImg }}')">
                        <a @if(authcheck()) href="{{ route('front.LiveAstroDetails', ['astrologerId' => $live->astrologerId]) }}"
                            @else data-toggle="modal" data-target="#loginSignUp" href="#" @endif
                            class="text-white">
                            <div class="position-relative live-expert">
                                <div class="position-absolute top-part">
                                    <span class="bg-red px-2 text-white d-inline-flex align-items-center rounded font-12">
                                        <i class="fa fa-circle font-11 mr-1"></i>Live
                                    </span>
                                </div>
                                <div class="position-absolute bottom-part w-100 p-2">
                                    <div class="d-flex h-100 align-items-center">
                                        <div class="position-relative profile-pic bg-white d-none d-md-flex align-items-center justify-content-center">
                                            <img src="{{ $liveImg }}"

                                                width="38" height="38" loading="lazy">
                                        </div>
                                        <div class="ml-2">
                                            <p class="mb-0 pb-0 text-white font-16 text-capitalize">{{ $live->name }}</p>
                                            <p class="mb-0 pb-0 text-yellow font-12 text-capitalize">{{ explode(',', $live->skill_names)[0] }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    @endforeach
                </div>
                <div class="text-center pt-2">
                    <a href="{{ route('front.getLiveAstro') }}" class="btn view-more colorblack font-weight-semi-bold">View More</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endif


{{-- ─── Stories ────────────────────────────────────────────────────────────── --}}
@if (isset($stories) && $stories->isNotEmpty())
<div class="container mt-5 mb-5{{ empty($liveAstrologer) ? ' mb-5' : '' }}">
    <h2 class="text-center text-black py-3 heading font-28">Stories</h2>
    <p class="text-center mb-4">See Stories of top-rated {{ $pt }}s</p>
    <div class="stories-container">
        @foreach ($stories as $story)
        @php $storyImg = imgSrc($story->profileImage ?? null, $defaultImg); @endphp
        <div class="story {{ ($story->allStoriesViewed ?? 0) > 0 ? 'viewed' : '' }}"
            data-astrologer-id="{{ $story->astrologerId }}"
            data-astrologer-name="{{ $story->name }}"
            data-astrologer-profile="{{ $story->profileImage }}">
            <img src="{{ $storyImg }}" alt="{{ $story->name }}" width="80" height="80" loading="lazy" decoding="async">
            <p>{{ $story->name }}</p>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Story Modal --}}
<div class="modal fade" id="storyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <img id="astrologerProfileImage" src="" alt="Astrologer Profile Image" class="rounded-circle" width="40" height="40" style="height:40px;width:40px" loading="lazy" decoding="async">
                <span class="modal-title mt-2 ml-2" id="astrologerName"></span>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
                    <ol class="carousel-indicators" id="carouselIndicators"></ol>
                    <div class="carousel-inner" id="carouselInner"></div>
                    <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button" data-slide="prev" style="margin-top:82px;">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="sr-only">Previous</span>
                    </a>
                    <a class="carousel-control-next" href="#carouselExampleIndicators" role="button" data-slide="next" style="margin-top:82px;">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="sr-only">Next</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')

<script>
    // ─── Shared config ────────────────────────────────────────────────────────────
    var TOKEN = "{{ session('token') }}";
    var IS_AUTH = {
        {
            authcheck() ? 'true' : 'false'
        }
    };
    var IS_FREE = {
        {
            $isFreeAvailable ? 'true' : 'false'
        }
    };
    var WALLET = "{{ authcheck() ? (authcheck()['totalWalletAmount'] ?? 0) : 0 }}";
    var NEXT_PAGE = "{{ $getAstrologer->nextPageUrl() ?? '' }}";

    $(document).ready(function() {

        // FAQ Accordion Toggle
        $(document).on('click', '.faq-trigger', function(e) {
            e.preventDefault();
            var $item = $(this).closest('.faq-item');
            var $content = $item.find('.faq-content');

            // Close other items
            $('.faq-item').not($item).removeClass('active').find('.faq-content').slideUp(250);

            // Toggle current item
            $item.toggleClass('active');
            $content.slideToggle(250);
        });

        // ─── Carousels ────────────────────────────────────────────────────────────
        var $astroOwl = $('.onlinepuja-astrologers .owl-carousel');
        if ($(window).width() > 767) {
            $astroOwl.owlCarousel({
                margin: 0,
                responsive: {
                    0: {
                        items: 2,
                        slideBy: 2
                    },
                    370: {
                        items: 2.3,
                        slideBy: 2
                    },
                    768: {
                        items: 2.4,
                        slideBy: 2,
                        nav: true
                    },
                    992: {
                        items: 3,
                        nav: true
                    },
                    1199: {
                        items: 5,
                        nav: true
                    }
                }
            });
        }
        $astroOwl.removeClass('owl-blur');

        $('.news-sections').owlCarousel({
            loop: false,
            nav: true,
            dots: true,
            responsive: {
                0: {
                    items: 1
                },
                600: {
                    items: 2
                },
                1000: {
                    items: 3
                }
            }
        });

        $('#main_nav').on('shown.bs.collapse', function() {
            $('#navbarDropdown').dropdown('toggle');
        });

        // ─── Video modal (marquee links) ──────────────────────────────────────────
        $('.video-link').on('click', function() {
            var url = $(this).data('video');
            var desc = $(this).data('description');
            $('#videoIframe').attr('src', url + '?autoplay=1');
            $('#videoDescription').html(desc);
        });

        // Legacy: data-video attribute links (non-marquee)
        $('a[data-video]').on('click', function(e) {
            e.preventDefault();
            var url = $(this).data('video');
            var videoId = '';
            if (url.includes('youtube.com/shorts/')) videoId = url.split('/shorts/')[1].split('?')[0];
            else if (url.includes('youtube.com/watch')) videoId = url.split('v=')[1].split('&')[0];
            else if (url.includes('youtu.be')) videoId = url.split('/').pop();
            else videoId = url;
            $('#videoIframe').attr('src', 'https://www.youtube.com/embed/' + videoId + '?autoplay=1');
            $('#videoModal').modal('show');
        });

        $('#videoModal').on('hidden.bs.modal', function() {
            $('#videoIframe').attr('src', '');
        });


        // ─── Load more astrologers ────────────────────────────────────────────────
        $('#load-more').on('click', function() {
            if (!NEXT_PAGE) return;
            var $btn = $(this).prop('disabled', true).html('<span class="loader"></span> Loading...');

            var url = new URL(NEXT_PAGE, window.location.origin);
            var sortBy = $('select[name="sortBy"]').val();
            var catId = $('input[name="astrologerCategoryId"]').val();
            var search = $('input[name="s"]').val();
            if (sortBy) url.searchParams.set('sortBy', sortBy);
            if (catId) url.searchParams.set('astrologerCategoryId', catId);
            if (search) url.searchParams.set('s', search);

            $.ajax({
                url: url.toString(),
                type: 'GET',
                success: function(response) {
                    var data = response.getAstrologer?.data;
                    if (data && data.length > 0) {
                        var html = data.map(function(a) {
                            var statusBadge = buildStatusBadge(a);
                            var priceBadge = buildPriceBadge(a);
                            var callBtn = buildCallBtn(a, 'audio');
                            var videoBtn = buildCallBtn(a, 'video');
                            var stars = buildStars(a.rating);
                            var sponsoredBadge = a.is_boosted == 1 ?
                                '<span class="must-try-badge font-10 position-absolute font-weight-semi text-center align-items-center justify-content-center text-white">Sponsored</span>' :
                                '';
                            var imgSrc = a.profileImage ?
                                '/' + a.profileImage :
                                "{{ $defaultImg }}";

                            return `
                        <div id="ATAAIOfferTile" class="psychic-card overflow-hidden expertOnline ask-guruji" data-astrologer-id="${a.id}">
                            <a href="${a.slug ? '/astrologer-details/' + a.slug : '#'}" class="text-decoration-none">
                                ${sponsoredBadge}
                                <ul class="list-unstyled d-flex mb-0">
                                    <li class="mr-3 position-relative psychic-presence status-online">
                                        <div class="psyich-img position-relative">
                                            <img src="${imgSrc}" width="85" height="85" style="border-radius:50%;" loading="lazy"
                                               >
                                        </div>
                                        ${statusBadge}
                                    </li>
                                    <li class="w-100 colorblack">
                                        <span class="colorblack font-weight-bold font16 mt-0 ml-0 mr-0 mb-0 p-0 text-capitalize d-block" style="font-weight:bold;color:#495057!important;">
                                            ${a.name}
                                        </span>
                                        <span class="font-13 d-block color-red">
                                            <img src="{{ asset('public/frontend/homeimage/horoscope2.svg') }}" height="16" width="16">
                                            ${a.primarySkill ? a.primarySkill.split(',').slice(0,3).join(' | ') : ''}
                                        </span>
                                        <span class="font-13 d-block exp-language">
                                            <img src="{{ asset('public/frontend/homeimage/language-icon.svg') }}" height="16" width="16">
                                            ${a.languageKnown ? a.languageKnown.split(',').slice(0,3).join(' • ') : ''}
                                        </span>
                                        <span class="font-13 d-block">
                                            <img src="{{ asset('public/frontend/homeimage/experience-expert-icon.svg') }}" height="16" width="16">
                                            Experience : ${a.experienceInYears} Years
                                        </span>
                                        ${priceBadge}
                                    </li>
                                </ul>
                                <div class="d-flex align-items-end position-relative">
                                    <div class="d-block">
                                        <div class="row">
                                            <div class="psy-review-section col-12">
                                                <span class="colorblack font-12 m-0 p-0 d-block">
                                                    <span style="color:#495057;font-size:14px;font-weight:bold;">${a.rating}</span>
                                                    ${stars}
                                                </span>
                                                <span style="color:gray;font-size:12px">${a.totalOrder || 0} Sessions</span>
                                            </div>
                                            <div class="col-3 responsiveCallBtn  mt-1">${callBtn}</div>
                                            <div class="col-3 responsiveVideoBtn mt-1">${videoBtn}</div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>`;
                        }).join('');

                        $('#expert-list').append(html);
                        NEXT_PAGE = response.getAstrologer.next_page_url;
                        if (!NEXT_PAGE) $btn.remove();
                        else $btn.prop('disabled', false).html('Load More');
                    } else {
                        $btn.remove();
                    }
                },
                error: function(xhr) {
                    console.error('Load more error:', xhr.responseText);
                }
            });
        });

        // ─── Stories ──────────────────────────────────────────────────────────────
        $('.story').on('click', function() {
            var id = $(this).data('astrologer-id');
            var name = $(this).data('astrologer-name');
            var profile = $(this).data('astrologer-profile') ||
                'public/frontend/onlinepujacdn/dashaspeaks/web/content/images/user-img-new.png';

            $.ajax({
                url: '/astrologer/' + id + '/stories',
                method: 'GET',
                success: function(res) {
                    openStoryModal(res, name, profile);
                },
                error: function(err) {
                    console.error('Error fetching stories:', err);
                }
            });
        });

        // ─── Misc ─────────────────────────────────────────────────────────────────
        document.getElementById('clearButton')?.addEventListener('click', function() {
            window.location.href = "{{ route('front.talkList') }}";
        });
    });

    // ─── Load-more helpers ────────────────────────────────────────────────────────
    function buildStatusBadge(a) {
        var cls, label;
        if (a.callStatus === 'Busy') {
            cls = 'specific-Clr-Busy';
            label = a.callStatus;
        } else if (a.callStatus === 'Offline' && a.emergencyCallStatus) {
            cls = 'specific-Clr-Busy';
            label = 'Emergency';
        } else if (!a.callStatus || a.callStatus === 'Offline') {
            cls = 'specific-Clr-Offline';
            label = a.callStatus || 'Offline';
        } else {
            cls = 'specific-Clr-Online';
            label = a.callStatus;
        }
        return `<div class="status-badge ${cls}"></div>
            <div class="status-badge-txt text-center ${cls}">
                <span class="status-badge-txt ${cls} tooltipex">${label}</span>
            </div>`;
    }

    function buildPriceBadge(a) {
        if (a.emergencyCallStatus) {
            return `<span class="font-13 font-weight-semi-bold d-flex">
            <img src="{{ asset('public/frontend/homeimage/rupee-coin-outline-icon.svg') }}" height="16" width="16">&nbsp;
            <span class="exprt-price mr-2"><i class="fa-solid fa-phone mr-1"></i>${a.emergency_audio_charge}</span>
            <i class="fa-solid fa-video mt-1 mr-1"></i>${a.emergency_video_charge}
        </span>`;
        }
        if (a.isFreeAvailable) {
            return `<span class="font-13 font-weight-semi-bold d-flex">
            <span class="exprt-price">
                <img src="{{ asset('public/frontend/homeimage/rupee-coin-outline-icon.svg') }}" height="16" width="16">
                <del>${a.charge}</del>/Min
            </span>
            <span class="free-badge text-uppercase color-red ml-2">Free</span>
        </span>`;
        }
        return `<span class="font-13 font-weight-semi-bold d-flex">
        <img src="{{ asset('public/frontend/homeimage/rupee-coin-outline-icon.svg') }}" height="16" width="16">&nbsp;
        <span class="exprt-price mr-2"><i class="fa-solid fa-phone mr-1"></i>${a.charge}</span>
        <i class="fa-solid fa-video mt-1 mr-1"></i>${a.videoCallRate}
    </span>`;
    }

    function buildCallBtn(a, type) {
        var isEmergency = a.callStatus === 'Offline' && a.emergencyCallStatus;
        var isUnavailable = a.callStatus === 'Busy' || a.callStatus === 'Offline' || !a.callStatus;
        var btnClass = type === 'audio' ? 'btn-audio-call' : 'btn-video-call';
        var icon = type === 'audio' ? 'fa-phone' : 'fa-video';
        var label = type === 'audio' ? 'Call' : 'Call';

        if (isEmergency || !isUnavailable) {
            var target = IS_AUTH ? '#callintake' : '#loginSignUp';
            return `<a class="btn-block btn btn-call ${btnClass} align-items-center" role="button" data-toggle="modal" data-target="${target}">
            <i class="fa-solid ${icon}"></i>&nbsp;${label}
        </a>`;
        }
        return `<a class="btn-block btn btn-call align-items-center" style="font-size:14px!important;">
        ${a.callStatus || 'Offline'}
    </a>`;
    }

    function buildStars(rating) {
        return Array.from({
                length: 5
            }, (_, i) =>
            `<i class="${i < rating ? 'fas fa-star filled-star' : 'far fa-star empty-star'}" style="font-size:10px"></i>`
        ).join('');
    }

    // ─── Story modal ──────────────────────────────────────────────────────────────
    function openStoryModal(stories, name, profileImage) {
        var $indicators = $('#carouselIndicators').empty();
        var $inner = $('#carouselInner').empty();

        stories.forEach(function(story, index) {
            $('<li>').attr({
                    'data-target': '#carouselExampleIndicators',
                    'data-slide-to': index
                })
                .toggleClass('active', index === 0).appendTo($indicators);

            var $item = $('<div>').addClass('carousel-item' + (index === 0 ? ' active' : ''));

            if (story.mediaType === 'image') {
                $('<img>').addClass('d-block w-100').attr('src', story.media).appendTo($item);
            } else if (story.mediaType === 'video') {
                $('<video>').addClass('d-block w-100').attr('controls', true)
                    .append($('<source>').attr({
                        src: story.media,
                        type: 'video/mp4'
                    })).appendTo($item);
            } else if (story.mediaType === 'text') {
                $('<div>').addClass('d-block w-100 text-center')
                    .css({
                        padding: '20px',
                        'font-size': calcFontSize(story.media)
                    })
                    .text(story.media).appendTo($item);
            }

            $item.appendTo($inner);

            @if(authcheck())
            trackStoryView(story.id);
            @endif
        });

        $('#astrologerName').text(name);
        $('#astrologerProfileImage').attr('src', profileImage);
        $('#storyModal').modal('show');
        $('.carousel').carousel('pause');
    }

    function calcFontSize(text) {
        var base = 30,
            max = 200;
        return (text.length > max ? base - ((text.length - max) / 10) : base) + 'px';
    }

    @if(authcheck())

    function trackStoryView(storyId) {
        $.ajax({
            url: "{{ route('front.viewstory') }}",
            method: 'POST',
            data: {
                storyId: storyId
            },
            success: function(r) {
                console.log(r.message);
            },
            error: function(err) {
                console.error('Error viewing story:', err);
            }
        });
    }
    @endif


    function toggleIcon(el) {
        el.style.display = 'inline-block';
        el.querySelector('i').classList.toggle('fa-chevron-down');
        el.querySelector('i').classList.toggle('fa-chevron-up');
    }
</script>

@if (request('error'))
<script>
    toastr.error("{{ request('error') }}");
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.pathname);
    }
</script>
@endif

<script>
    $(document).ready(function() {
        const signDetails = {
            'Aries': {
                element: 'FIRE',
                date: 'MAR 21 - APR 19',
                unicode: '♈︎'
            },
            'Taurus': {
                element: 'EARTH',
                date: 'APR 20 - MAY 20',
                unicode: '♉︎'
            },
            'Gemini': {
                element: 'AIR',
                date: 'MAY 21 - JUN 20',
                unicode: '♊︎'
            },
            'Cancer': {
                element: 'WATER',
                date: 'JUN 21 - JUL 22',
                unicode: '♋︎'
            },
            'Leo': {
                element: 'FIRE',
                date: 'JUL 23 - AUG 22',
                unicode: '♌︎'
            },
            'Virgo': {
                element: 'EARTH',
                date: 'AUG 23 - SEP 22',
                unicode: '♍︎'
            },
            'Libra': {
                element: 'AIR',
                date: 'SEP 23 - OCT 22',
                unicode: '♎︎'
            },
            'Scorpio': {
                element: 'WATER',
                date: 'OCT 23 - NOV 21',
                unicode: '♏︎'
            },
            'Sagittarius': {
                element: 'FIRE',
                date: 'NOV 22 - DEC 21',
                unicode: '♐︎'
            },
            'Capricorn': {
                element: 'EARTH',
                date: 'DEC 22 - JAN 19',
                unicode: '♑︎'
            },
            'Aquarius': {
                element: 'AIR',
                date: 'JAN 20 - FEB 18',
                unicode: '♒︎'
            },
            'Pisces': {
                element: 'WATER',
                date: 'FEB 19 - MAR 20',
                unicode: '♓︎'
            }
        };

        const moodEmojis = {
            'Bored': '😑',
            'Happy': '😊',
            'Energetic': '⚡',
            'Calm': '🧘',
            'Excited': '🥳',
            'Stressed': '😰',
            'Romantic': '💖',
            'Creative': '🎨'
        };

        const fallbackForecasts = [
            "A highly productive day awaits you. Focus on completing pending tasks and collaborating with teammates.",
            "Financial stability is highlighted today. It is a good time to review your budget and plan long-term investments.",
            "Love and romance are in the air. Spend quality time with your partner to strengthen your bond.",
            "Your energy levels are high. Utilize this enthusiasm to start a new fitness routine or creative project.",
            "Trust your intuition when making important career decisions today. Success is within reach.",
            "A calm and peaceful day is ahead. Reflect on your goals and practice mindfulness to stay centered."
        ];

        function useFallbackForecast(signId, signName) {
            const details = signDetails[signName] || {
                element: 'STAR',
                date: 'ALL YEAR',
                unicode: '✨'
            };
            const forecast = fallbackForecasts[signId % fallbackForecasts.length];
            const moods = ['Bored', 'Happy', 'Energetic', 'Calm', 'Excited', 'Romantic', 'Creative'];
            const moodName = moods[signId % moods.length];
            const emoji = moodEmojis[moodName] || '✨';

            const colors = [{
                    name: 'Yellow',
                    code: '#f59e0b'
                },
                {
                    name: 'Red',
                    code: '#ef4444'
                },
                {
                    name: 'Blue',
                    code: '#3b82f6'
                },
                {
                    name: 'Green',
                    code: '#10b981'
                },
                {
                    name: 'Purple',
                    code: '#8b5cf6'
                },
                {
                    name: 'Pink',
                    code: '#ec4899'
                }
            ];
            const color = colors[signId % colors.length];

            $('#horo-meta').text(`${details.element} · ${details.date}`);
            $('#horo-title').text(`${signName} ${emoji}`);
            $('#horo-mood').text(moodName);
            $('#horo-desc').text(forecast);
            const fallbackLuckyNum = (signId * 7 + 12) % 100;
            $('#horo-number-value').text(fallbackLuckyNum);
            $('#horo-color-dot').css('background-color', color.code);
            $('#horo-color-name').text(color.name);

            const loveScore = 4 + (signId % 2);
            const careerScore = 3 + ((signId + 1) % 3);
            const moneyScore = 4 + (signId % 2);

            generateStars('#horo-love-stars', loveScore);
            generateStars('#horo-career-stars', careerScore);
            generateStars('#horo-money-stars', moneyScore);
        }

        function fetchHoroscope(signId, signName, signSlug) {
            $('.card-loader').removeClass('d-none');
            const details = signDetails[signName] || {
                element: 'STAR',
                date: 'ALL YEAR',
                unicode: '✨'
            };

            // Update central wheel symbol immediately
            $('.horoscope-wheel-center .center-icon').text(details.unicode);

            // Set initial titles before request
            $('#horo-meta').text(`${details.element} · ${details.date}`);
            $('#horo-title').text(`${signName} ✨`);

            $.ajax({
                url: "{{ route('front.ajaxDailyHoroscope', '') }}/" + signId,
                method: "GET",
                success: function(res) {
                    $('.card-loader').addClass('d-none');
                    if (res && res.vedicList && res.vedicList.todayHoroscope && res.vedicList.todayHoroscope.length > 0) {
                        const horo = res.vedicList.todayHoroscope[0];

                        // Parse desc & color
                        const desc = horo.bot_response || horo.status_remark || horo.physique || horo.status || fallbackForecasts[signId % fallbackForecasts.length];
                        const luckyColor = horo.lucky_color || "Yellow";
                        const luckyColorCode = horo.lucky_color_code || "#fbbf24";

                        // Determine emoji for mood
                        let luckyNumVal = 0;
                        let luckyNum = horo.lucky_number || '--';
                        if (luckyNum && luckyNum !== '--') {
                            try {
                                const parsed = JSON.parse(luckyNum);
                                if (Array.isArray(parsed) && parsed.length > 0) {
                                    luckyNumVal = parseInt(parsed[0]);
                                    luckyNum = parsed.join(', ');
                                } else {
                                    luckyNumVal = parseInt(luckyNum);
                                    luckyNum = String(luckyNum);
                                }
                            } catch (e) {
                                luckyNumVal = parseInt(luckyNum);
                                luckyNum = String(luckyNum);
                            }
                        }
                        const moodName = luckyNumVal ? (Object.keys(moodEmojis)[luckyNumVal % 8] || "Balanced") : "Balanced";
                        const emoji = moodEmojis[moodName] || "✨";

                        // Update UI
                        $('#horo-meta').text(`${details.element} · ${details.date}`);
                        $('#horo-title').text(`${signName} ${emoji}`);
                        $('#horo-mood').text(moodName);
                        $('#horo-desc').text(desc);
                        $('#horo-number-value').text(luckyNum);
                        $('#horo-color-dot').css('background-color', luckyColorCode);
                        $('#horo-color-name').text(luckyColor);

                        // Parse stars based on score or randomize nicely
                        const score = parseInt(horo.total_score) || 4;
                        generateStars('#horo-love-stars', score);
                        generateStars('#horo-career-stars', Math.min(5, score + (signId % 2)));
                        generateStars('#horo-money-stars', Math.min(5, Math.max(3, score - (signId % 2))));
                    } else {
                        useFallbackForecast(signId, signName);
                    }

                    // Update read full forecast link
                    $('#horo-read-link').attr('href', "{{ route('front.dailyHoroscope', '') }}/" + signSlug);
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error: ", error);
                    $('.card-loader').addClass('d-none');
                    useFallbackForecast(signId, signName);
                    $('#horo-read-link').attr('href', "{{ route('front.dailyHoroscope', '') }}/" + signSlug);
                }
            });
        }

        function generateStars(elementId, count) {
            let html = '';
            for (let i = 1; i <= 5; i++) {
                if (i <= count) {
                    html += '<i class="fa-solid fa-star"></i>';
                } else if (i - 0.5 <= count) {
                    html += '<i class="fa-solid fa-star-half-stroke"></i>';
                } else {
                    html += '<i class="fa-regular fa-star"></i>';
                }
            }
            $(elementId).html(html);
        }

        // Setup CSRF token for all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        let currentRotation = 0; // Cumulative rotation of the wheel

        // Delegate Click handler dynamically to avoid binding issues
        $(document).on('click', '.zodiac-wheel-item', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $item = $(this);
            $('.zodiac-wheel-item').removeClass('active');
            $item.addClass('active');

            var id = $item.data('id');
            var name = $item.data('name');
            var slug = $item.data('slug');
            var targetAngle = parseFloat($item.data('angle')) || 0;

            console.log('Zodiac clicked:', name, id, slug, 'Angle:', targetAngle);

            // Shortest path rotation calculation
            var targetRotation = -targetAngle;
            var diff = (targetRotation - currentRotation) % 360;
            if (diff > 180) {
                diff -= 360;
            } else if (diff < -180) {
                diff += 360;
            }
            currentRotation += diff;

            // Use native style setter to ensure CSS custom properties are updated correctly
            $('.horoscope-wheel').each(function() {
                this.style.setProperty('--wheel-rotation', currentRotation + 'deg');
            });

            if (id && name && slug) {
                fetchHoroscope(id, name, slug);
            } else {
                console.error('Missing data attributes on zodiac item:', $item);
            }
        });

        // Load default sign (first item in the list)
        var defaultItem = $('.zodiac-wheel-item.active').length > 0 ? $('.zodiac-wheel-item.active').first() : $('.zodiac-wheel-item').first();
        if (defaultItem.length > 0) {
            defaultItem.addClass('active');
            var defId = defaultItem.data('id');
            var defName = defaultItem.data('name');
            var defSlug = defaultItem.data('slug');
            var defAngle = parseFloat(defaultItem.data('angle')) || 0;

            currentRotation = -defAngle;
            $('.horoscope-wheel').each(function() {
                this.style.setProperty('--wheel-rotation', currentRotation + 'deg');
            });

            console.log('Loading default zodiac:', defName, defId, 'Angle:', defAngle);
            if (defId && defName && defSlug) {
                fetchHoroscope(defId, defName, defSlug);
            }
        }
    });
</script>
@endsection