<!DOCTYPE html>
<html lang="en">

<head>
@php
    $logo = $systemFlags->get('AdminLogo');
    $walletTypeFlag = $systemFlags->get('walletType');

    // Dynamic Marketing, Analytics & Ad Tracking from AI OS Vault
    $gtmId = \App\Brain\Vault::get('gtm_container_id');
    $ga4Id = \App\Brain\Vault::get('ga4_measurement_id', 'G-7T63JY1D93');
    $metaPixelId = \App\Brain\Vault::get('meta_pixel_id') ?: (\App\Brain\Vault::get('fb_app_id') ?: '1738490804042986');
    $gAdsId = \App\Brain\Vault::get('google_ads_id');
    $gscVerification = \App\Brain\Vault::get('gsc_verification_code');
@endphp

@if(!empty($gscVerification))
<meta name="google-site-verification" content="{{ $gscVerification }}" />
@endif

@if(!empty($gtmId))
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','{{ $gtmId }}');</script>
<!-- End Google Tag Manager -->
@endif

@if(!empty($ga4Id) || !empty($gAdsId))
<!-- Global site tag (gtag.js) - Google Analytics 4 & Google Ads -->
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4Id ?: $gAdsId }}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  @if(!empty($ga4Id))
  gtag('config', '{{ $ga4Id }}', { 'send_page_view': true });
  @endif
  @if(!empty($gAdsId))
  gtag('config', '{{ $gAdsId }}');
  @endif
</script>
@endif

@if(!empty($metaPixelId))
<!-- Meta (Facebook) Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '{{ $metaPixelId }}');
fbq('track', 'PageView');
</script>
<!-- End Meta Pixel Code -->
@endif



    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <!-- Primary Meta Tags -->
    <title>@hasSection('title')@yield('title') - {{ ucfirst($appname) }}@else Online Puja & Pandit Booking in Varanasi | Authentic Vedic Pujas - {{ ucfirst($appname) }}@endif</title>
    <meta name="description" content="@yield('meta_description', 'Book authentic Vedic pujas in Varanasi (Kashi Vishwanath, Ganga Ghats) performed by experienced Vedic Pandits. Receive live sankalp video and sanctum prasad at your doorstep.')" />
    <meta name="keywords" content="@yield('meta_keywords', 'online puja varanasi, kashi vishwanath rudrabhishek booking, pandit in varanasi, online pandit booking, dev deepawali deepdaan, vedic puja service')" />

    <!-- Canonical URL -->
    <link rel="canonical" href="@yield('canonical_url', request()->url())" />
    <link href="{{ asset($logo->value ?? 'build/assets/images/logo.png') }}" rel="shortcut icon" type="image/x-icon" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="@yield('og_type', 'website')" />
    <meta property="og:title" content="@hasSection('title')@yield('title') - {{ ucfirst($appname) }}@else Online Puja & Pandit Booking in Varanasi | Authentic Vedic Pujas - {{ ucfirst($appname) }}@endif" />
    <meta property="og:description" content="@yield('meta_description', 'Book authentic Vedic pujas in Varanasi performed by experienced Vedic Pandits. Receive live sankalp video and sanctum prasad.')" />
    <meta property="og:image" content="@yield('og_image', asset($logo->value ?? 'build/assets/images/logo.png'))" />
    <meta property="og:url" content="{{ request()->url() }}" />
    <meta property="og:site_name" content="{{ ucfirst($appname) }}" />

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="@hasSection('title')@yield('title') - {{ ucfirst($appname) }}@else Online Puja & Pandit Booking in Varanasi | Authentic Vedic Pujas - {{ ucfirst($appname) }}@endif" />
    <meta name="twitter:description" content="@yield('meta_description', 'Book authentic Vedic pujas in Varanasi performed by experienced Vedic Pandits.')" />
    <meta name="twitter:image" content="@yield('og_image', asset($logo->value ?? 'build/assets/images/logo.png'))" />

    <!-- Dynamic Schema Markup Container -->
    @yield('schema_markup')

    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">

        <!-- Google Fonts: Outfit and Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&family=Rozha+One&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('public/frontend/css/newcss.css?v=1.1.0') }}">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('public/frontend/css/app.min.css?v=1.1.0') }}">
    <!-- 🕉️ Universal Vedic Design System -->
    <link rel="stylesheet" href="{{ asset('public/frontend/css/onlinepuja-design-system.css?v=1.0.0') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />


    <link href="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/css/font/stylesheet.css') }}"
        rel="stylesheet" />

    <link rel="stylesheet"
        href="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/css/carousel/owl.carousel.min.css') }}">
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.8/sweetalert2.min.css"
        integrity="sha512-OWGg8FcHstyYFwtjfkiCoYHW2hG3PDWwdtczPAPUcETobBJOVCouKig8rqED0NMLcT9GtE4jw6IT1CSrwY87uw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script src="{{ asset('public/build/assets/jquery.min.js') }}"></script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <link rel="preload" href="https://translate.google.com/translate_a/element.js?cb=googleTranslateInit" as="script">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <style>
        body.Is-Offer{
            top: 0px !important;
        }
    </style>


</head>

<body class="english country-in Is-Offer ">
@if(!empty($gtmId))
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
@endif
@if(!empty($metaPixelId))
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id={{ $metaPixelId }}&ev=PageView&noscript=1"
/></noscript>
@endif
    @include('frontend.layout.header')

    <div class="wrapper">

        @yield('content')

    </div>

    @include('frontend.layout.footer')

    @yield('scripts')


<script defer
    src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/js/carousel/owl.carousel.min.js') }}">
</script>
<script defer src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/js/popper.min.js') }}"></script>
<script defer src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script defer type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>
<script defer src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/js/bootstrap.min.js') }}"></script>
<script defer src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/js/momentum.js') }}"></script>
<script defer src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/js/bootstrap-datepicker.min.js') }}"></script>
<script defer src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/js/js.cookie.min.js') }}"></script>
<script defer src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/bundle/js/AfterLoginJs.js') }}"></script>
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.8/sweetalert2.min.js"
    integrity="sha512-FbWDiO6LEOsPMMxeEvwrJPNzc0cinzzC0cB/+I2NFlfBPFlZJ3JHSYJBtdK7PhMn0VQlCY1qxflEG+rplMwGUg=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script defer src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/js/duDatepicker.js') }}"></script>
<script defer src="https://unpkg.com/aos@next/dist/aos.js"></script>


@php
    $webLanguage=$systemFlags->get('WebLanguage');
    $selectedLanguages = json_decode($webLanguage->value, true) ?: [];
    $includedLanguages = implode(',', $selectedLanguages);
@endphp


<script>
    AOS.init();

    // 🕉️ Comprehensive Vedic Hindi & Multi-Language Dictionary Map
    const VEDIC_HINDI_MAP = [
        // Exact Titles & High-Impact Phrases
        { en: /Maha Mrityunjaya Jaap Benefits and Vidhi Guide/gi, hi: "महा मृत्युंजय जाप: लाभ और विधि मार्गदर्शिका" },
        { en: /Understanding the Sacred Power of Maha Mrityunjaya Jaap/gi, hi: "महा मृत्युंजय जाप की दिव्य एवं पवित्र शक्ति" },
        { en: /Maha Mrityunjaya Jaa\.\.\./gi, hi: "महा मृत्युंजय जा..." },
        { en: /Kashi Vishwanath Rudrabhishek Puja in Varanasi/gi, hi: "वाराणसी में काशी विश्वनाथ रुद्राभिषेक पूजा" },
        { en: /Kashi Vishwanath Rudrabhishek Puja/gi, hi: "काशी विश्वनाथ रुद्राभिषेक पूजा" },
        { en: /Ganga Aarti at Dashashwamedh Ghat/gi, hi: "दशाश्वमेध घाट पर गंगा आरती" },
        { en: /Dev Deepawali Maha Aarti Booking at Dashashwamedh Ghat/gi, hi: "दशाश्वमेध घाट पर देव दीपावली महा आरती बुकिंग" },
        { en: /Dev Deepawali Maha Aarti Booking/gi, hi: "देव दीपावली महा आरती बुकिंग" },
        { en: /Kaal Sarp Dosh Nivaran Puja/gi, hi: "काल सर्प दोष निवारण पूजा" },
        { en: /Mangla Gauri Temple Puja/gi, hi: "मंगला गौरी मंदिर पूजा" },
        { en: /Navgraha Shanti Puja/gi, hi: "नवग्रह शांति पूजा" },
        { en: /Free Janam Kundali/gi, hi: "मुफ्त जन्म कुंडली" },
        { en: /Kundali Matching/gi, hi: "कुंडली मिलान" },
        { en: /Today's Panchang/gi, hi: "आज का पंचांग" },
        { en: /Daily Horoscope/gi, hi: "दैनिक राशिफल" },
        { en: /Frequently Asked Questions/gi, hi: "अक्सर पूछे जाने वाले प्रश्न" },
        { en: /How it works/gi, hi: "यह कैसे काम करता है" },
        { en: /Explore More/gi, hi: "और पढ़ें" },
        { en: /Recent Posts/gi, hi: "हाल के लेख" },
        { en: /Related Pujas/gi, hi: "संबंधित पूजाएं" },
        { en: /Book Puja Now/gi, hi: "अभी पूजा बुक करें" },
        { en: /Book Puja/gi, hi: "पूजा बुक करें" },
        { en: /Book Now/gi, hi: "अभी बुक करें" },
        { en: /Talk to Astrologer/gi, hi: "ज्योतिषी से बात करें" },
        { en: /Chat with Astrologer/gi, hi: "ज्योतिषी से चैट करें" },

        // Sacred Sanskrit & Vedic Terms
        { en: /\bMaha Mrityunjaya\b/gi, hi: "महा मृत्युंजय" },
        { en: /\bMrityunjaya\b/gi, hi: "मृत्युंजय" },
        { en: /\bRudrabhishek\b/gi, hi: "रुद्राभिषेक" },
        { en: /\bVishwanath\b/gi, hi: "विश्वनाथ" },
        { en: /\bKashi\b/gi, hi: "काशी" },
        { en: /\bVaranasi\b/gi, hi: "वाराणसी" },
        { en: /\bGanga Aarti\b/gi, hi: "गंगा आरती" },
        { en: /\bDev Deepawali\b/gi, hi: "देव दीपावली" },
        { en: /\bDashashwamedh\b/gi, hi: "दशाश्वमेध" },
        { en: /\bKaal Sarp Dosh\b/gi, hi: "काल सर्प दोष" },
        { en: /\bNavgraha Shanti\b/gi, hi: "नवग्रह शांति" },
        { en: /\bMangla Gauri\b/gi, hi: "मंगला गौरी" },
        { en: /\bNivaran\b/gi, hi: "निवारण" },
        { en: /\bShanti\b/gi, hi: "शांति" },
        { en: /\bMahadev\b/gi, hi: "महादेव" },
        { en: /\bShivling\b/gi, hi: "शिवलिंग" },
        { en: /\bSankalp\b/gi, hi: "संकल्प" },
        { en: /\bPrasad\b/gi, hi: "प्रसाद" },
        { en: /\bKundali\b/gi, hi: "कुंडली" },
        { en: /\bPanchang\b/gi, hi: "पंचांग" },
        { en: /\bHoroscope\b/gi, hi: "राशिफल" },
        { en: /\bAstrologer\b/gi, hi: "ज्योतिषी" },
        { en: /\bAstrologers\b/gi, hi: "ज्योतिषी" },
        { en: /\bPandit\b/gi, hi: "पंडित" },
        { en: /\bPandits\b/gi, hi: "पंडित" },
        { en: /\bDevotee\b/gi, hi: "भक्त" },
        { en: /\bDevotees\b/gi, hi: "भक्त" },
        { en: /\bJaap\b/gi, hi: "जाप" },
        { en: /\bPuja\b/gi, hi: "पूजा" },
        { en: /\bPujas\b/gi, hi: "पूजाएं" },
        { en: /\bTemple\b/gi, hi: "मंदिर" },
        { en: /\bTemples\b/gi, hi: "मंदिरों" },
        { en: /\bGhats\b/gi, hi: "घाटों" },
        { en: /\bGhat\b/gi, hi: "घाट" },
        { en: /\bBenefits and Vidhi Guide\b/gi, hi: "लाभ और विधि मार्गदर्शिका" },
        { en: /\bBenefits & Vidhi Guide\b/gi, hi: "लाभ और विधि मार्गदर्शिका" },
        { en: /\bVidhi Guide\b/gi, hi: "विधि मार्गदर्शिका" },
        { en: /\bVidhi\b/gi, hi: "विधि" },
        { en: /\bBenefits\b/gi, hi: "लाभ एवं महत्व" },
        { en: /\bGuide\b/gi, hi: "मार्गदर्शिका" },
        { en: /\bUnderstanding the Sacred Power of\b/gi, hi: "दिव्य एवं पवित्र शक्ति:" },
        { en: /\bUnderstanding\b/gi, hi: "रहस्य और महत्व" },
        { en: /\bSacred Power\b/gi, hi: "दिव्य शक्ति" },
        { en: /\bBooking\b/gi, hi: "बुकिंग" },
        { en: /\bMantras\b/gi, hi: "मंत्रों" },
        { en: /\bMantra\b/gi, hi: "मंत्र" },
        { en: /\bPackage\b/gi, hi: "पैकेज" },
        { en: /\bPackages\b/gi, hi: "पैकेज" },
        { en: /\bProcess\b/gi, hi: "प्रक्रिया" },
        { en: /\bOverview\b/gi, hi: "अवलोकन" },
        { en: /\bOnline\b/gi, hi: "ऑनलाइन" },
        { en: /\bLive\b/gi, hi: "लाइव" },
        { en: /\b at \b/gi, hi: " पर " },
        { en: /\b in \b/gi, hi: " में " }
    ];

    // Helper: Translate all headings, breadcrumbs, titles into Hindi
    window.applyVedicHindiHeadings = function() {
        const isHindi = (window.getCurrentLanguage() === 'hi');
        const selectors = 'h1, h2, h3, h4, h5, .blog-main-title, .blog-sidebar-title, .breadcrumb-item, .recent-post-title-link, .blog-card-title, .puja-title, .card-title';

        document.querySelectorAll(selectors).forEach(el => {
            if (el.classList.contains('notranslate') || el.closest('.notranslate')) return;

            if (isHindi) {
                if (!el.hasAttribute('data-orig-en')) {
                    el.setAttribute('data-orig-en', el.innerHTML);
                }

                let html = el.innerHTML;
                if (/[a-zA-Z]{2,}/.test(html)) {
                    for (const item of VEDIC_HINDI_MAP) {
                        html = html.replace(item.en, item.hi);
                    }
                    if (html !== el.innerHTML) {
                        el.innerHTML = html;
                    }
                }
            } else {
                if (el.hasAttribute('data-orig-en')) {
                    el.innerHTML = el.getAttribute('data-orig-en');
                    el.removeAttribute('data-orig-en');
                }
            }
        });
    };

    // 🕉️ Vedic Full Hindi & Localization Engine
    (function() {
        const urlParams = new URLSearchParams(window.location.search);
        const queryLang = urlParams.get('lang') || urlParams.get('hl');

        let currentLang = 'en';
        const match = document.cookie.match(/(?:^|;\s*)googtrans=([^;]+)/);
        if (match) {
            const parts = decodeURIComponent(match[1]).split('/');
            if (parts.length >= 3 && parts[2]) {
                currentLang = parts[2];
            }
        } else {
            try {
                const stored = localStorage.getItem('onlinepuja_language');
                if (stored) {
                    currentLang = stored;
                } else if (navigator.language && navigator.language.startsWith('hi')) {
                    currentLang = 'hi';
                }
            } catch(e) {}
        }

        if (queryLang && (queryLang === 'hi' || queryLang === 'en' || queryLang === 'gu' || queryLang === 'mr')) {
            currentLang = queryLang;
        }

        if (currentLang && currentLang !== 'en') {
            const host = window.location.hostname;
            const rootDomain = host.includes('.') ? '; domain=.' + host.replace(/^www\./, '') : '';
            const cVal = '/en/' + currentLang;
            document.cookie = 'googtrans=' + cVal + '; path=/; SameSite=Lax' + rootDomain;
            document.cookie = 'googtrans=' + cVal + '; path=/; SameSite=Lax';
            document.cookie = 'googtrans=' + cVal + '; path=/' + rootDomain;
            document.cookie = 'googtrans=' + cVal + '; path=/';
            document.documentElement.lang = currentLang;
            document.addEventListener('DOMContentLoaded', () => {
                document.body.classList.add('hindi-active');
                window.applyVedicHindiHeadings();
            });
        }

        if (typeof google === 'undefined' || !google.translate || !google.translate.TranslateElement) {
            var gtScript = document.createElement('script');
            gtScript.type = 'text/javascript';
            gtScript.async = true;
            gtScript.defer = true;
            gtScript.src = 'https://translate.google.com/translate_a/element.js?cb=googleTranslateInit';
            document.head.appendChild(gtScript);
        }
    })();

    var translateInitialized = false;

    function googleTranslateInit() {
        if (translateInitialized) return;

        try {
            new google.translate.TranslateElement(
                {
                    pageLanguage: 'en',
                    includedLanguages: '{{ !empty($includedLanguages) ? $includedLanguages : "en,hi,gu,mr" }}',
                    autoDisplay: false
                },
                'google_translate_button'
            );
            translateInitialized = true;

            const observer = new MutationObserver((mutationsList, observer) => {
                const combo = document.querySelector('.goog-te-combo');
                if (combo && combo.options && combo.options.length > 0) {
                    const current = window.getCurrentLanguage();
                    if (current && combo.value !== current) {
                        combo.value = current;
                        combo.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    window.syncVedicLanguageUI(current);
                    window.applyVedicHindiHeadings();
                }
            });

            observer.observe(document.body, { childList: true, subtree: true });
            setTimeout(() => {
                window.syncVedicLanguageUI(window.getCurrentLanguage());
                window.applyVedicHindiHeadings();
            }, 600);

        } catch (error) {
            console.error('Google Translate failed to initialize:', error);
        }
    }

    window.getCurrentLanguage = function() {
        const match = document.cookie.match(/(?:^|;\s*)googtrans=([^;]+)/);
        if (match) {
            const parts = decodeURIComponent(match[1]).split('/');
            if (parts.length >= 3 && parts[2]) {
                return parts[2];
            }
        }
        try {
            const stored = localStorage.getItem('onlinepuja_language');
            if (stored) return stored;
        } catch(e) {}
        return 'en';
    };

    window.switchLanguage = function(lang) {
        if (!lang) return;
        const host = window.location.hostname;
        const rootDomain = host.includes('.') ? '; domain=.' + host.replace(/^www\./, '') : '';
        const cookieVal = '/en/' + lang;

        if (lang === 'en') {
            document.cookie = 'googtrans=/en/en; path=/; SameSite=Lax' + rootDomain;
            document.cookie = 'googtrans=/en/en; path=/; SameSite=Lax';
            document.cookie = 'googtrans=/en/en; path=/' + rootDomain;
            document.cookie = 'googtrans=/en/en; path=/';
            document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=.' + host.replace(/^www\./, '');
            document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
        } else {
            document.cookie = 'googtrans=' + cookieVal + '; path=/; SameSite=Lax' + rootDomain;
            document.cookie = 'googtrans=' + cookieVal + '; path=/; SameSite=Lax';
            document.cookie = 'googtrans=' + cookieVal + '; path=/' + rootDomain;
            document.cookie = 'googtrans=' + cookieVal + '; path=/';
        }

        try {
            localStorage.setItem('onlinepuja_language', lang);
        } catch(e) {}

        window.syncVedicLanguageUI(lang);
        window.applyVedicHindiHeadings();

        const combo = document.querySelector('.goog-te-combo');
        if (combo) {
            if (combo.value !== lang) {
                combo.value = lang;
                combo.dispatchEvent(new Event('change', { bubbles: true }));
                combo.dispatchEvent(new Event('input', { bubbles: true }));
            }
        } else {
            window.location.reload();
        }
    };

    window.toggleLanguage = function() {
        const cur = window.getCurrentLanguage();
        const target = (cur === 'hi') ? 'en' : 'hi';
        window.switchLanguage(target);
    };

    window.syncVedicLanguageUI = function(lang) {
        lang = lang || window.getCurrentLanguage() || 'en';

        document.querySelectorAll('.lang-pill-btn').forEach(btn => {
            const btnLang = btn.getAttribute('data-lang');
            if (btnLang === lang) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        const mobileQuickBtn = document.getElementById('mobileQuickLangBtn');
        const mobileQuickText = document.getElementById('mobileQuickLangText');
        if (mobileQuickText) {
            if (lang === 'hi') {
                mobileQuickText.innerHTML = 'English';
                if (mobileQuickBtn) mobileQuickBtn.title = 'Switch to English / अंग्रेज़ी में देखें';
            } else {
                mobileQuickText.innerHTML = 'हिन्दी';
                if (mobileQuickBtn) mobileQuickBtn.title = 'Switch to Hindi / हिन्दी में देखें';
            }
        }

        document.querySelectorAll('.lang-drawer-btn').forEach(btn => {
            const btnLang = btn.getAttribute('data-lang');
            if (btnLang === lang) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        document.querySelectorAll('.footer-lang-btn').forEach(btn => {
            const btnLang = btn.getAttribute('data-lang');
            if (btnLang === lang) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        document.documentElement.lang = lang;
        if (lang === 'hi') {
            document.body.classList.add('hindi-active');
        } else {
            document.body.classList.remove('hindi-active');
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        window.syncVedicLanguageUI();
        window.applyVedicHindiHeadings();
    });
</script>

@include('partials.voice_assistant_widget')

</body>
</html>
