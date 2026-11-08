<!DOCTYPE html>
<html lang="en">

<head>
    @php
    $logo = $systemFlags->get('PartnerLogo');
@endphp
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta
        content="Ask an online {{ucfirst($professionTitle)}} and get instant consultation on top Astrology portal. Accurate astrology predictions and solutions by India's best {{ucfirst($professionTitle)}}s' team."
        name="description" />
    <meta property="Keywords" content="" />

    <meta name="twitter:card" content="summary" />
    <meta name="twitter:site" content="" />
    <meta name="twitter:description"
        content="Ask an online {{$professionTitle}} and get instant consultation on top Astrology portal. Accurate astrology predictions and solutions by India's best {{ucfirst($professionTitle)}}s' team." />
    <meta name="twitter:title" content="Online Astrology Consultation, Ask an {{ucfirst($professionTitle)}} - {{ucfirst($appname)}}" />
    <meta name="twitter:image" content="{{asset($logo->value)}}" />

    <meta property="og:type" content="website" />
    <meta property="og:title" content="Online Astrology Consultation, Ask an {{ucfirst($professionTitle)}} - {{ucfirst($appname)}}" />
    <meta property="og:description"
        content="Ask an online {{$professionTitle}} and get instant consultation on top Astrology portal. Accurate astrology predictions and solutions by India's best {{ucfirst($professionTitle)}}s' team." />
    <meta property="og:image" content="{{asset($logo->value)}}" />
    <meta property="og:url" content="index.html" />
    <meta property="og:site_name" content="{{ucfirst($appname)}}" />

    <title>Online Astrology Consultation, Ask an {{ucfirst($professionTitle)}} - {{ucfirst($appname)}}</title>

    <link href="{{request()->url()}}" rel="canonical" />

    <link href="{{asset($logo->value)}}" rel="shortcut icon" type="image/x-icon" />


    <link rel="preconnect" as="font"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/fonts/fontawesome-webfont.woff2?v=4.7.0"
        type="font/woff2" crossorigin />

    <link rel="stylesheet" href="{{ asset('public/frontend/css/app.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/frontend/css/app.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />


    <link href="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/css/font/stylesheet.css') }}"
        rel="stylesheet" />




    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/fontawesome.min.css" />
    </noscript>

    <link rel="stylesheet"
        href="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/css/carousel/owl.carousel.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/css/carousel/owl.carousel.css') }}">
    <link href="{{ asset('public/frontend/select2/npm/select2@4.1.0-rc.0/dist/css/select2.min.css') }}"
        rel="stylesheet" />


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.8/sweetalert2.min.css"
        integrity="sha512-OWGg8FcHstyYFwtjfkiCoYHW2hG3PDWwdtczPAPUcETobBJOVCouKig8rqED0NMLcT9GtE4jw6IT1CSrwY87uw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script src="{{ asset('public/build/assets/jquery.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="{{ asset('public/frontend/css/newcss.css') }}">

    <link rel="preload" href="https://translate.google.com/translate_a/element.js?cb=googleTranslateInit" as="script">
	<style>
        body.Is-Offer{
            top: 0px !important;
        }
    </style>
	<script src="https://cdn.jsdelivr.net/npm/@100mslive/hms-video-store@latest/dist/index.min.js"></script>


    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&family=Rozha+One&display=swap" rel="stylesheet">
</head>
@include('frontend.astrologers.layout.header')

<body class="english country-in Is-Offer ">
    <div class="wrapper">

        @yield('content')

    </div>
</body>

@include('frontend.astrologers.layout.footer')

@yield('scripts')


<script
    src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/js/carousel/owl.carousel.min.js') }}">
</script>
<script src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/js/carousel/owl.carousel.js') }}">
</script>
<script src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/js/popper.min.js') }}"></script>


<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>
<script async src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/js/bootstrap.min.js') }}">
</script>
<script async src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/js/popper.min.js') }}">
</script>
<script async src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/js/momentum.js') }}"></script>
<script async src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/js/bootstrap-datepicker.min.js') }}">
</script>
<script async src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/js/js.cookie.min.js') }}"></script>
<script async src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/bundle/js/AfterLoginJs.js') }}">
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.8/sweetalert2.min.js"
    integrity="sha512-FbWDiO6LEOsPMMxeEvwrJPNzc0cinzzC0cB/+I2NFlfBPFlZJ3JHSYJBtdK7PhMn0VQlCY1qxflEG+rplMwGUg=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>



<script src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/js/duDatepicker.js') }}"></script>


@php
    $webLanguage = $systemFlags->get('WebLanguage');
    $selectedLanguages = json_decode($webLanguage->value ?? '[]', true) ?: [];
    $includedLanguages = implode(',', $selectedLanguages);
@endphp

{{-- <script type="text/javascript" src="https://translate.google.com/translate_a/element.js?cb=googleTranslateInit"></script> --}}


<script>
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
</scr.applyVedicHindiHeadings();
    });
</scrscript>


@if (astroauthcheck())
    @php
        $astrologerId = astroauthcheck()['astrologerId'];
        $assetBase = asset('');
        $blankProfile = asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/images/blank-profile.png');
    @endphp
    <!-- Global Notification Sound -->
    <audio id="global-notification-sound" src="{{ asset('public/sound/ringtone-126505.mp3') }}" loop></audio>

 <script>
    $(document).ready(function() {
        var chatCount = 0;
        var callCount = 0;
        var reportCount = 0;

        function manageGlobalSound() {
            var sound = document.getElementById('global-notification-sound');
            if (!sound) return;

            // Check if there's any manual override (e.g., from Test Sounds button)
            if (window.manualSoundPlaying) return;

            if (chatCount + callCount > 0) {
                if (sound.paused) {
                    sound.play().catch(function(e) {
                        console.log("Autoplay blocked. User interaction required.");
                    });
                }
            } else {
                if (!sound.paused) {
                    sound.pause();
                    sound.currentTime = 0;
                }
            }
        }

        function fetchGlobalRequests() {
            // Fetch Chat
            $.ajax({
                url: "{{ route('astrologer.chat.requests') }}",
                method: 'POST',
                data: {
                    astrologerId: "{{ $astrologerId }}"
                },
                success: function(data) {
                    chatCount = (data.recordList && data.recordList.chatRequest) ? data.recordList.chatRequest.length : 0;
                    if (typeof window.updateChatRequests === 'function') {
                        window.updateChatRequests(data.recordList.chatRequest);
                    }
                    manageGlobalSound();
                }
            });

            // Fetch Call
            $.ajax({
                url: "{{ route('astrologer.call.requests') }}",
                method: 'POST',
                data: {
                    astrologerId: "{{ $astrologerId }}"
                },
                success: function(data) {
                    callCount = 0;
                    if (data.recordList && data.recordList.length > 0) {
                        data.recordList.forEach(function(request) {
                            if (request.IsSchedule != 1) {
                                callCount++;
                            } else {
                                // It's a scheduled appointment request
                                // Check if we already showed SweetAlert for this specific callId
                                let shownAppointments = JSON.parse(localStorage.getItem('shown_appointments') || '{}');
                                if (!shownAppointments[request.callId]) {
                                    shownAppointments[request.callId] = true;
                                    localStorage.setItem('shown_appointments', JSON.stringify(shownAppointments));

                                    Swal.fire({
                                        title: '📅 Appointment Request',
                                        html: `You got an appointment request from <strong>${request.name || 'User'}</strong>.<br>Scheduled for: <strong>${request.schedule_datetime || ''}</strong>`,
                                        icon: 'info',
                                        showCancelButton: true,
                                        confirmButtonColor: '#ff8c00',
                                        cancelButtonColor: '#d33',
                                        confirmButtonText: 'View Appointments',
                                        cancelButtonText: 'Dismiss'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            window.location.href = "{{ route('astro-appointment') }}";
                                        }
                                    });
                                }

                                if (request.schedule_datetime) {
                                    var scheduleTimeStr = request.schedule_datetime.replace(/-/g, '/');
                                    var scheduleTime = new Date(scheduleTimeStr).getTime();
                                    var currentTime = new Date().getTime();
                                    if (currentTime >= scheduleTime) {
                                        callCount++;
                                    }
                                }
                            }
                        });
                    }
                    if (typeof window.updateCallRequests === 'function') {
                        window.updateCallRequests(data.recordList);
                    }
                    manageGlobalSound();
                }
            });

            // Fetch Report
            $.ajax({
                url: "{{ route('astrologer.report.requests') }}",
                method: 'POST',
                data: {
                    astrologerId: "{{ $astrologerId }}"
                },
                success: function(data) {
                    reportCount = data.recordList ? data.recordList.length : 0;
                    if (typeof window.updateReportRequests === 'function') {
                        window.updateReportRequests(data.recordList);
                    }
                    manageGlobalSound();
                }
            });
        }

        // Start polling if not on chat or call page (or even if we are, to hear other requests)
        fetchGlobalRequests();
        setInterval(fetchGlobalRequests, 6000);
    });

    // Global function to toggle test sound from any page
    window.toggleTestSound = function() {
        var sound = document.getElementById('global-notification-sound');
        if (sound.paused) {
            sound.play();
            window.manualSoundPlaying = true;
            return true;
        } else {
            sound.pause();
            sound.currentTime = 0;
            window.manualSoundPlaying = false;
            return false;
        }
    };
</script>
@endif
</html>
