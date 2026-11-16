@php
    use Symfony\Component\HttpFoundation\Session\Session;

    $session = new Session();
    $token = $session->get('token');

    $getUserNotification = $userNotifications;
    $chatrequest = $chatRequests;

    $logo = $systemFlags->get('AdminLogo');
    $appName = $systemFlags->get('AppName');
    $OneSignalAppId = $systemFlags->get('OneSignalAppId');
    $currency = $systemFlags->get('currencySymbol');
    $appId = $systemFlags->get('firebaseappId');
    $measurementId = $systemFlags->get('firebasemeasurementId');
    $messagingSenderId = $systemFlags->get('firebasemessagingSenderId');
    $storageBucket = $systemFlags->get('firebasestorageBucket');
    $projectId = $systemFlags->get('firebaseprojectId');
    $authDomain = $systemFlags->get('firebaseauthDomain');
    $databaseURL = $systemFlags->get('firebasedatabaseURL');
    $apiKey = $systemFlags->get('firebaseapiKey');

    $freekundali = $systemFlags->get('FreeKundali');
    $kundali_matching = $systemFlags->get('KundaliMatching');
    $panchang = $systemFlags->get('TodayPanchang');
    $blog = $systemFlags->get('Blog');
    $shop = $systemFlags->get('Astromall');
    $daily_horoscope = $systemFlags->get('DailyHoroscope');
    $puja = $systemFlags->get('Puja');

    $playstore = $systemFlags->get('PlayStore');
    $appstore = $systemFlags->get('AppStore');
@endphp

<style>

    body.modal-open {
        overflow-y: scroll !important;
    }
    #otpless-login-page-parent{
        z-index: 10000;
    }

    .select2-selection__rendered {
        margin-top: 5px !important;
    }

    .pac-container {
        z-index: 10000 !important;
    }

    .pac-container:after {
        content: none !important;
    }


      /* Hide number arrows */
      input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield; /* Firefox */
        }


    .btn-chat-astro {
        background: #ffffff;
        /* box-shadow: 0 2px 3px #ffd70080; */
        font-size: 15px;
        font-weight: 600;
        border-radius: 10px;
        padding: 8px 20px;
        margin: 0 5px;
        white-space: nowrap;
    }

    .btn-chat-astro:hover {
        background: #fff;
        border: 2px solid gold;
    }


    .navbar-collapse {
        position: unset !important;
    }

    nav.navbar.navbar-expand-lg.navbar-light.top-navbar {
        background: #f4f4f5;
    }

    .scrollable-menu {
        max-height: 450px;
        /* Adjust this value as needed */
        overflow-y: auto;

    }

    .dropdown-menu.show {
        display: block;
    }

    .btn-chataccept {
        border-radius: 30px;
        border: 1px solid #5bbe2a;
        background-color: #5bbe2a !important;
        color: white !important;
    }




    .btn-chatreject {
        border-radius: 30px;
        border: 1px solid #ee4e5e;
        background-color: #ffffff !important;
        color: #ee4e5e !important;
    }

    .btn.clear-notification {
        font-size: 15px !important;
        padding: 8px 30px !important;
    }

    .btn.clear-notification:hover,
    .btn.clear-notification:focus,
    .btn.clear-notification:active {
        color: #fff !important;
        background: #ee4e5e !important;
    }

    @media screen and (max-width: 520px) {
        #notificationList {
            width: 370px !important;
        }
    }

    .navbar-expand-lg .navbar-nav .nav-link {
        padding-right: 0.9rem;
        padding-left: 0.9rem;
    }

    @media (max-width: 576px) {
        .nav-link-mobile {
            border-bottom: 1px solid #dee2e6;
            /* Add the border */
        }
    }
</style>
<style>
    .sf_chat_button {
        display: none;
        /* hide ai astrologer button from project */
        position: fixed;
        bottom: 12px;
        right: 12px;
        z-index: 99;
        font-family: Gilroy, Inter, sans-serif;
    }

    .sf_chat_button button {
        border-radius: 50%;
        /* background: rgb(255 215 0); */
        color: #FFFFFF;
        padding: 0;
        border: none;
        background: none;

    }

    .sf_chat_button svg {
        display: inline-block;
    }

    /* ----------------------------------------------FOR MASTER-----------------------------------------*/
    /* when start ai astrologer by category then comment .sf_chat_button1 */
    .sf_chat_button1 {
        position: fixed;
        bottom: 12px;
        right: 12px;
        z-index: 99;
        font-family: Gilroy, Inter, sans-serif;
    }

    .sf_chat_button1 button {
        border-radius: 50%;
        padding: 0;
        border: none;
        background: none;
    }

    .sf_chat_button1 svg {
        display: inline-block;
    }
     /* ─── Mobile Navigation Collapse Styles (Grid Card layout) ─── */
    @media (max-width: 991px) {
        .unified-navbar .navbar-collapse {
            position: absolute !important;
            top: 100% !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            background-color: #ffffff !important;
            border-radius: 0 0 24px 24px !important;
            padding: 24px 20px !important;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1) !important;
            border: none !important;
            border-top: 1px solid rgba(0, 0, 0, 0.05) !important;
            margin: 0 !important;
            z-index: 9999 !important;
        }

        body.home-dark-mode .unified-navbar .navbar-collapse {
            background-color: #110f0c !important;
            border-top: 1px solid rgba(255, 255, 255, 0.06) !important;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5) !important;
        }

        .unified-navbar .navbar-collapse .menu-links {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 12px !important;
            width: 100% !important;
            padding: 0 !important;
            margin: 20px 0 !important;
            align-items: stretch !important;
        }

        .unified-navbar .menu-links .nav-item {
            background: rgba(0, 0, 0, 0.03) !important;
            border: 1px solid rgba(0, 0, 0, 0.05) !important;
            border-radius: 12px !important;
            transition: all 0.2s ease !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            min-height: 52px !important;
            width: 100% !important;
            margin: 0 !important;
        }

        body.home-dark-mode .unified-navbar .menu-links .nav-item {
            background: rgba(255, 255, 255, 0.04) !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
        }

        .unified-navbar .menu-links .nav-item:last-child {
            grid-column: span 2 !important;
        }

        .unified-navbar .menu-links .nav-link {
            background: transparent !important;
            border: none !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            width: 100% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 8px !important;
            font-size: 15px !important;
            font-weight: 700 !important;
            padding: 12px 14px !important;
            margin: 0 !important;
            color: #374151 !important;
            transition: all 0.2s ease;
        }

        body.home-dark-mode .unified-navbar .menu-links .nav-link {
            color: #f8fafc !important;
        }

        .unified-navbar .menu-links .nav-item:hover {
            background: rgba(245, 158, 11, 0.08) !important;
            border-color: rgba(245, 158, 11, 0.25) !important;
            transform: translateY(-2px) !important;
        }

        body.home-dark-mode .unified-navbar .menu-links .nav-item:hover {
            background: rgba(251, 191, 36, 0.08) !important;
            border-color: rgba(251, 191, 36, 0.2) !important;
        }

        .unified-navbar .menu-links .nav-link img {
            margin: 0 !important;
            flex-shrink: 0 !important;
            width: 20px !important;
            height: 20px !important;
        }

        .unified-navbar .navbar-collapse .dropdown-menu {
            position: absolute !important;
            top: auto !important;
            left: 0 !important;
            right: 0 !important;
            background: #ffffff !important;
            border: 1px solid rgba(0, 0, 0, 0.08) !important;
            border-radius: 12px !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
            padding: 6px !important;
            z-index: 1000 !important;
            margin-top: 4px !important;
        }

        body.home-dark-mode .unified-navbar .navbar-collapse .dropdown-menu {
            background: #1a1713 !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
        }

        .unified-navbar .navbar-collapse .dropdown-item {
            color: #4b5563 !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            padding: 8px 16px !important;
            border-radius: 8px !important;
            text-align: center !important;
        }

        body.home-dark-mode .unified-navbar .navbar-collapse .dropdown-item {
            color: #cbd5e1 !important;
        }

        .unified-navbar .navbar-collapse .dropdown-item:hover {
            background: rgba(245, 158, 11, 0.08) !important;
            color: #b45309 !important;
        }

        body.home-dark-mode .unified-navbar .navbar-collapse .dropdown-item:hover {
            background: rgba(251, 191, 36, 0.12) !important;
            color: #fbbf24 !important;
        }

        .unified-navbar .navbar-actions-right {
            grid-column: span 2 !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            width: 100% !important;
            border-top: 1px solid rgba(0, 0, 0, 0.06) !important;
            padding-top: 18px !important;
            margin-top: 10px !important;
        }

        body.home-dark-mode .unified-navbar .navbar-actions-right {
            border-top-color: rgba(255, 255, 255, 0.08) !important;
        }
    }
</style>





<div class="header">
    <nav class="navbar navbar-expand-lg navbar-light top-navbar unified-navbar">
        <div class="container navbar-container d-flex justify-content-between align-items-center">
            <!-- Left Side: Brand Logo & Name -->
            <a class="navbar-brand-link text-decoration-none" href="{{ route('front.home') }}">
                <div class="d-flex align-items-center">
                    <img src="{{ asset($logo->value) }}" alt="{{ $appName->value }}" class="brand-logo-img img-fluid" width="45" height="45">
                    <div class="brand-text-container ml-2">
                        <span class="brand-name notranslate font-weight-bold font-20 text-dark">{{ $appName->value }}</span>
                    </div>
                </div>
            </a>

            <!-- Right Actions on Mobile -->
            <div class="d-flex align-items-center mobile-actions d-lg-none">
                <!-- 🕉️ Vedic Quick Language Switcher (Mobile Header) -->
                <div class="vedic-lang-mobile notranslate mr-2">
                    <button type="button" class="btn-lang-mobile" id="mobileQuickLangBtn" onclick="window.toggleLanguage()" title="Change Language / भाषा बदलें">
                        <span class="flag-emoji">🇮🇳</span> <span id="mobileQuickLangText">हिन्दी</span>
                    </button>
                </div>
                @if (authcheck())
                    <div class="dropdown mr-2">
                        <a class="btn p-0" style="width: 25px" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fa-solid fa-bell"></i>
                            <span class="badge badge-danger badge-counter" id="notificationCountMobile">0</span>
                        </a>
                    </div>
                @endif
                <button class="navbar-toggler ml-2" type="button" data-toggle="collapse" data-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>

            <!-- Navbar Links (Center) and Actions (Right) -->
            <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
                <!-- 🕉️ Mobile Language Drawer Card -->
                <div class="mobile-lang-drawer-card notranslate d-lg-none mb-3 p-2 rounded">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="font-13 font-weight-bold text-dark"><i class="fa-solid fa-language text-warning mr-1"></i> भाषा (Language):</span>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-sm lang-drawer-btn active" data-lang="en" onclick="window.switchLanguage('en')">English</button>
                            <button type="button" class="btn btn-sm lang-drawer-btn" data-lang="hi" onclick="window.switchLanguage('hi')">🇮🇳 हिन्दी</button>
                        </div>
                    </div>
                </div>

                <!-- Center Links -->
                <ul class="navbar-nav mx-auto menu-links align-items-center">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle font-weight-semi-bold nav-link-mobile" href="#" id="matchingDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <img src="{{ asset($freekundali->value) }}" alt="" height="20" width="20" class="mr-1">
                            Kundali
                        </a>
                        <div class="dropdown-menu" aria-labelledby="matchingDropdown">
                            <a class="dropdown-item" href="{{ route('front.kundaliMatch') }}">Kundali Matching</a>
                            <a class="dropdown-item" href="{{ route('front.getkundali') }}">Free Janam Kundali</a>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-semi-bold nav-link-mobile" href="{{ route('front.getPanchang') }}">
                            <img src="{{ asset($panchang->value) }}" alt="" height="20" width="20" class="mr-1"> Panchang
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-semi-bold nav-link-mobile" href="{{ route('front.pujaCategory') }}">
                            <img src="{{ asset($puja->value) }}" alt="" height="20" width="20" class="mr-1"> Puja
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-semi-bold nav-link-mobile" href="{{ route('front.reportList') }}">
                            <img src="{{ asset('public/frontend/homeimage/report.png') }}" alt="" height="20" width="20" class="mr-1"> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-semi-bold nav-link-mobile" href="{{ route('front.horoScope') }}">
                            <img src="{{ asset($daily_horoscope->value) }}" alt="" height="20" width="20" class="mr-1"> Horoscope
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-semi-bold nav-link-mobile" href="{{ route('front.getproducts') }}">
                            <img src="{{ asset($shop->value) }}" alt="" height="20" width="20" class="mr-1"> Shop
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-semi-bold nav-link-mobile" href="{{ route('front.getBlog') }}">
                            <img src="{{ asset($blog->value) }}" alt="" height="20" width="20" class="mr-1"> Blog
                        </a>
                    </li>
                </ul>

                <!-- Right Actions -->
                <div class="navbar-actions-right d-flex align-items-center">
                    <!-- Vedic Dual Language Switcher (Desktop) -->
                    <div class="vedic-lang-pill notranslate d-none d-lg-flex align-items-center mr-3">
                        <button type="button" class="lang-pill-btn active" data-lang="en" onclick="window.switchLanguage('en')" title="Switch to English">
                            <span>EN</span>
                        </button>
                        <button type="button" class="lang-pill-btn" data-lang="hi" onclick="window.switchLanguage('hi')" title="हिन्दी में देखें">
                            <span class="flag-icon">🇮🇳</span> <span>हिन्दी</span>
                        </button>
                    </div>

                    <!-- Hidden Host for Google Translate API -->
                    <div id="google_translate_host" style="display:none;position:absolute;top:-9999px;left:-9999px;">
                        <div id="google_translate_button"></div>
                    </div>

                    @if (authcheck())
                        <!-- Notification Dropdown -->
                        <div class="dropdown mr-3 d-none d-lg-block">
                            <a class="btn p-0 position-relative" style="width: 30px" role="button" id="dropdownMenuLinkNotification" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                <i class="fa-solid fa-bell font-18"></i>
                                <span class="badge badge-danger badge-counter" id="notificationCount" style="position: absolute; top: -5px; right: -5px;">0</span>
                            </a>
                            <div class="dropdown-menu user-options fadeInUp5px dropdown-menu-right scrollable-menu" aria-labelledby="dropdownMenuLinkNotification" id="notificationDropdown">
                                <ul id="notificationList">
                                    @foreach ($getUserNotification as $notification)
                                        <li class="d-lg-block @if ($notification->chatStatus == 'Accepted' || $notification->callStatus == 'Accepted') bg-pink @endif">
                                            <div>
                                                <a class="dropdown-item"
                                                    @if ($notification->chatStatus == 'Accepted') onclick="setIds('{{ $notification->chatId }}', '{{ $notification->astrologerId }}')" data-toggle="modal" data-target="#chatinfomodal"
                                                    @elseif($notification->callStatus == 'Accepted') onclick="setCallIds('{{ $notification->callId }}', '{{ $notification->astrologerId }}')" @if ($notification->call_method != 'exotel') data-toggle="modal"  data-target="#callinfomodal" @endif
                                                    @endif>
                                                    <span class="mr-2 accSet accSettingWeb">
                                                        <i class="fa-solid fa-bell"></i>
                                                    </span>
                                                    <span>{{ $notification->title }}</span>
                                                </a>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                                @if (count($getUserNotification) > 0)
                                    <a class="dropdown-item text-center btn clear-notification" id="clearNotifications">Clear Notifications</a>
                                @else
                                    <ul id="notificationList">
                                        <li class="d-lg-block">
                                            <span class="dropdown-item text-center ">No Notification Yet</span>
                                        </li>
                                    </ul>
                                @endif
                            </div>
                        </div>

                        <!-- User Profile Dropdown -->
                        <div class="dropdown">
                            <a class="btn dropdown-toggle p-0 d-flex align-items-center" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                @if (authcheck()['profile'])
                                    <img src="{{ asset(authcheck()['profile']) }}" alt="User" class="img-fluid rounded-circle" style="height: 40px; width: 40px; object-fit: cover;">
                                @else
                                    <img src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/images/user-img.png') }}" alt="User" class="img-fluid rounded-circle" style="height: 40px; width: 40px; object-fit: cover;">
                                @endif
                            </a>
                            <div class="dropdown-menu user-options fadeInUp5px dropdown-menu-right scrollable-menu" aria-labelledby="dropdownMenuLink">
                                <ul>
                                    <li class="namedisplay d-block text-center p-3">
                                        @if (authcheck()['profile'])
                                            <img src="{{ asset(authcheck()['profile']) }}" alt="User" class="img-fluid rounded-circle mb-2" style="height: 60px; width: 60px; object-fit: cover;">
                                        @else
                                            <img src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/images/user-img-new.png') }}" alt="User" class="img-fluid rounded-circle mb-2" style="height: 60px; width: 60px; object-fit: cover;">
                                        @endif
                                        <div>
                                            <h2 class="pt-2 font-16 font-weight-bold">{{ authcheck()['name'] ?: 'User' }}</h2>
                                        </div>
                                    </li>
                                    <!-- Options -->
                                    <li class="d-block"><a class="dropdown-item" href="{{ route('front.getMyAccount') }}"><i class="fa-solid fa-user mr-2"></i>My Account</a></li>
                                    <li class="d-block"><a class="dropdown-item d-flex justify-content-between align-items-center pr-2" href="{{ route('front.getMyWallet') }}"><span><i class="fa-solid fa-wallet mr-2"></i>My Wallet</span><span class="badge badge-danger">@if($walletType == 'Coin')<img src="{{ asset($coinIcon) }}" alt="Wallet Icon" width="12">@else{{ $currency['value'] }}@endif {{ $userWalletAmount }}</span></a></li>
                                    <li class="d-block"><a class="dropdown-item" href="{{ route('front.getMyChat') }}"><i class="fa-solid fa-comment-dots mr-2"></i>My Chats</a></li>
                                    <li class="d-block"><a class="dropdown-item" href="{{ route('front.getMyAiChat') }}"><i class="fa-solid fa-robot mr-2"></i>My Ai Chats</a></li>
                                    <li class="d-block"><a class="dropdown-item" href="{{ route('front.getMyCall') }}"><i class="fa-solid fa-phone mr-2"></i>My Calls</a></li>
                                    <li class="d-block"><a class="dropdown-item" href="{{ route('front.myOrders') }}"><i class="fa-solid fa-cart-shopping mr-2"></i>My Orders</a></li>
                                    <li class="d-block"><a class="dropdown-item" href="{{ route('front.getMypujalist') }}"><i class="fa fa-list mr-2"></i>My Puja Order</a></li>
                                    <li class="d-block"><a class="dropdown-item" href="{{ route('front.getMyReport') }}"><i class="fa-solid fa-file mr-2"></i>My Reports</a></li>
                                    <li class="d-block"><a class="dropdown-item" href="{{ route('front.getMyFollowing') }}"><i class="fa-solid fa-circle-user mr-2"></i>My Following</a></li>
                                    <li class="d-block"><a class="dropdown-item" href="{{ route('front.getblockAstrologer') }}"><i class="fa-solid fa-ban mr-2"></i>Blocked {{ $professionTitle }}</a></li>
                                    <li class="d-block"><a class="dropdown-item" href="{{ route('front.myAstrologerPuja') }}"><i class="fa fa-list mr-2"></i>{{ $professionTitle }} Puja</a></li>
                                    <li class="d-block"><a class="dropdown-item" id="logout" href="javascript:void(0)" onclick="logout()"><i class="fa-solid fa-right-from-bracket mr-2"></i>Sign Out</a></li>
                                </ul>
                            </div>
                        </div>
                    @else
                        <!-- Sign In Button -->
                        <a class="btn btn-signin-pill loginSignUp" data-toggle="modal" data-target="#loginSignUp">
                            <i class="fa-solid fa-circle-user mr-2"></i>Sign In
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </nav>
</div>

{{-- Chat Accept Reject Model --}}
<div id="chatinfomodal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-sm h-100 d-flex align-items-center">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">

                <h4 class="modal-title font-weight-bold">
                    Accept Chat Request
                </h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">

                <form id="chatForm">
                    <input type="hidden" name="chatId" id="chatIdInput" value="">
                    <input type="hidden" id="astrologerIdInput" name="astrologerId" value="">
                    <div class="text-center">
                        <a class="btn btn-chataccept  active d-inline-block m-2" id="startchat" role="button"
                            data-toggle="modal">
                            Start Chat
                        </a>
                        <a class="btn btn-chatreject active d-inline-block m-2" id="rejectchat" role="button"
                            data-toggle="modal">
                            Reject Chat
                        </a>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

{{-- Call Accept Reject Modal --}}

<div id="callinfomodal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-sm h-100 d-flex align-items-center">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">

                <h4 class="modal-title font-weight-bold">
                    Accept Call Request
                </h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">

                <form id="callForm">
                    <input type="hidden" name="callId" id="callIdInput" value="">
                    <input type="hidden" id="astrologerIdInput" name="astrologerId" value="">
                    <input type="hidden" id="calltypeInput" name="call_type" value="">
                    <div class="text-center">
                        <a class="btn btn-chataccept  active d-inline-block m-2" id="startcall" role="button"
                            data-toggle="modal">
                            Start Call
                        </a>
                        <a class="btn btn-chatreject active d-inline-block m-2" id="rejectcall" role="button"
                            data-toggle="modal">
                            Reject Call
                        </a>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

{{-- End Model --}}

<!-- Add Select2 CSS CDN -->


<div class="modal fade rounded mt-2 mt-md-5 login-offer" id="loginSignUp" tabindex="-1" role="dialog"
    aria-labelledby="myLargeModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body pt-0 pb-0">
                <div class="login-offer-bg d-none">
                    <p class="text-white font-22 text-center font-weight-bold p-0 m-0 offertxt1">Get
                        Consultation from Experts</p>
                    <p class="text-center p-0 m-0 offertxt2 ">First Chat Free</p>
                </div>
                <button type="button" class="close login-sig-close-btn loginCloseBut" data-dismiss="modal"
                    aria-hidden="true">
                    ×
                </button>
                <div class="bg-white body">
                    <div class="row ">
                        <div class="col-md-12 px-4 py-5">
                            <ul class="nav nav-tabs"></ul>
                            <!-- Tab panes -->
                            <div class="tab-content">
                                <div class="tab-pane active" id="LoginRegisterWithOTP">
                                    <div class="col-md-12 text-center font-22 ">
                                        <h3 class="font-weight-bold">Sign In</h3>
                                    </div>
                                    <div>
                                        <p class="colorblack text-center pb-md-0 pb-2 mb-0">Enter your mobile number to continue</p>
                                    </div>
                                    <div class="pt-4">
                                        <div class="row">
                                            <div class="col-md-12 mb-4">
                                                <div class="d-flex inputform country-dropdown-container" id="header-country-dropdown-container">
                                                    <!-- Country Code Dropdown -->
                                                    <select class="form-control select2" id="countryCode"name="countryCode">
                                                        @foreach ($countries as $country)
                                                            <option data-country="in" value="+{{ $country->phonecode }}" data-ucname="India">
                                                                +{{ $country->phonecode }} {{ $country->iso }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <!-- Mobile Number Input -->
                                                    <input class="form-control mobilenumber border-left text-box single-line" id="contactNo" maxlength="12" name="contactNo" placeholder="Enter Mobile Number." type="number">
                                                    <input type="hidden" id="validOtp" value="" />
                                                </div>
                                                <!--<span class="text-danger field-validation-error  ContactMobile-error" style="display: none">Please Enter Your Mobile Number</span>-->
                                                <span class="text-danger field-validation-error otp-error" id="mobileMessage"></span>
                                            </div>
                                        </div>

                                        <!-- Get OTP Button -->
                                        <div class="form-group text-center">
                                            <button class="font-weight-bold ml-0 w-100 btn btn-chat" id="loaderOtpLogin" type="button" style="display:none;" disabled="">
                                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                                Loading...
                                            </button>
                                            <!--<input type="button" id="getOtp" value="Get OTP" class="font-weight-bold ml-0 w-100 btn btn-chat valid" aria-invalid="false" onclick="phoneAuth()" >-->
                                            <input type="button" id="sendOtpBtn" value="Send OTP" class="font-weight-bold ml-0 w-100 btn btn-chat valid" aria-invalid="false" >
                                        </div>
                                    </div>
                                    <div class="container mt-3 mb-3">
                                        <div class="row">
                                            {{-- <div class="col-md-6">
                                                <button style="font-size: 14px" class="btn btn-success w-100 d-flex align-items-center justify-content-center" onclick="oauth('WHATSAPP')">
                                                    <i class="fa-brands fa-whatsapp mr-2"></i>
                                                    <span>WhatsApp Login</span>
                                                </button>
                                            </div> --}}

                                            <div class="col-md-12">
                                                <button
                                                    class="btn btn-danger w-100 d-flex align-items-center justify-content-center"
                                                    id="googleLoginBtn">
                                                    <i class="fa-solid fa-envelope mr-2"></i>
                                                    <span>Continue With Gmail</span>
                                                </button>
                                            </div>
                                            <!--<div class="col-md-12 mt-2">-->
                                            <!--    <button-->
                                            <!--        class="btn btn-success w-100 d-flex align-items-center justify-content-center"-->
                                            <!--        onclick="oauth('WHATSAPP')">-->
                                            <!--        <i class="fa-brands fa-whatsapp mr-2" style="font-size: 21px"></i>-->
                                            <!--        <span>Continue With Whatsapp</span>-->
                                            <!--    </button>-->
                                            <!--</div>-->
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-md-11 list-inline-item ml-md-3 ml-sm-0">
                                            <p class="text-dark font-13 text-center pb-md-0 pb-2 mb-0">
                                                By signing in, you agree to our&nbsp;<a class="font-13 login-policy-link" href="{{route('front.termscondition')}}" target="_blank">Terms Of Use</a>&nbsp;and&nbsp;<a class="font-13 login-policy-link" href="{{route('front.privacyPolicy')}}" target="_blank">Privacy Policy</a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <!-- OTP Input (Initially Hidden) -->
                                <div class="row">
                                    <div class="col-md-12 mb-4">
                                        <!--<div id="otpInputGroup" style="display: none;">-->
                                        <div id="otpInputGroup" class="d-none">
                                            <div class="col-md-12 text-center pb-2 pb-md-4">
                                                <h3 class="font-22 font-weight-bold">OTP Verification</h3>
                                            </div>
                                            <div class="otpheader pb-2 align-items-center">
                                                <!--Enter 6 digit code sent to Your Number.<a href="#" onclick="editMobile()" class="pl-1  font-14 text-danger">Edit</a>-->
                                                Enter 6 digit code sent to Your Number.<a href="#" onclick="editMobileNumber()" class="pl-1  font-14 text-danger">Edit</a>
                                            </div>
                                            <div class="form-group">
                                                <!--<input class="form-control" id="otp" name="otp" placeholder="Enter OTP" type="number">-->
                                                <input class="form-control" id="otpCode" name="otp" placeholder="Enter OTP" type="number">
                                            </div>
                                            <div class="form-group float-right">
                                                <button id="resendOtpBtn" class="btn btn-sm text-primary" onclick="startOtpTimer()">Resend OTP</button>
                                            </div>
                                            <span class="text-danger" id="otpLoginMessage"></span>

                                            <form method="post" action="{{ route('front.verifyOTL') }}"
                                                id="OtpLesslogin">
                                                @csrf
                                                <input type="hidden" name="otl_token" id="otl_token">
                                                <input id="veifycontactNo" name="contactNo" type="hidden"
                                                    value="" />
                                                <input id="countryCode" name="countryCode" type="hidden"
                                                    value="" />
                                                <input id="country" name="country" type="hidden"
                                                    value="" />
                                                <input id="name" name="name" type="hidden"
                                                    value="" />
                                            </form>

                                            <div class="form-group text-center">
                                                <div class="my-0 w-100">
                                                    <button class="font-weight-bold w-100 btn btn-chat ml-0" id="loaderVerifyLogin" type="button" style="display:none" disabled="">
                                                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                                        Loading...
                                                    </button>

                                                    <!--<input type="submit" value="Submit" class="btn btn-chat font-weight-bold w-100 ml-0 mt-3" id="btnVerify" onclick="verifyOTP()">-->
                                                    <input type="button" value="Submit" id="verifyOtpBtn" class="btn btn-chat font-weight-bold w-100 ml-0 mt-3">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <p>Download Our App For Better Experience</p>
                                    <a href="{{$playstore->value}}" class="mt-2"><img src="{{asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/images/google-play.png')}}" alt="google-play" class="mt-2 img-fluid" width="183" height="54" loading="lazy"></a>
                                    <a href="{{$appstore->value}}"><img src="{{asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/images/app-store.png')}}" alt="google-play" class="img-fluid mt-2" width="183" height="54" loading="lazy"></a>
                                  </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="astroLoginModal" tabindex="-1" role="dialog" aria-labelledby="astroLoginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header border-0">
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center pb-5 px-4">
                <div class="mb-4">
                    <i class="fa-solid fa-circle-exclamation text-warning" style="font-size: 60px;"></i>
                </div>
                <h4 class="font-weight-bold mb-3">Astrologer Account Detected</h4>
                <p class="text-muted mb-4" id="astroLoginMessage">
                    This contact information is already registered as an astrologer. Please use the astrologer portal to login.
                </p>
                <div class="d-flex flex-column gap-2">
                    <a href="{{route('front.astrologerlogin')}}" class="btn btn-chat w-100 py-3 font-weight-bold">
                        Go to Astrologer Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


<!-- Added by bhushan borse on 12, June 2025 -->
<script>
$(document).ready(function () {

    $('#sendOtpBtn').click(function () {
        const mobile = $('#contactNo').val();
        const countryCode = $('#countryCode').val().trim().replace('+', '');
         const fullNumber = countryCode + mobile;
        $('#otpLoginMessage').text('');
        $('#mobileMessage').text('')
        $("#validOtp").val("");
        $("#otpCode").val("")


        if (mobile?.length <= 0) {
            $('#mobileMessage').text('Please Enter Your Mobile Number');
            return
        }

        console.log("mobile :: ", mobile)
        $.ajax({
            url: '{{ route("api.checkContactAndSendOTP") }}',
            method: 'POST',
            data: {
                contactNo: mobile,
                fromApp: "user",
                countryCode: countryCode,
                type: "login",
                fromWeb: 1
            },
            success: function (res) {
                makeAction(res.status == 200)
                if (res.status == 200) {
                    $('#mobileMessage').text('');
                    $("#validOtp").val(res.otp)
                    $('#otpInputGroup').removeClass('d-none');
                    $('#sendOtpBtn').addClass('d-none');
                } else {
                    if (res.message === 'This number is registered as an astrologer') {
                        $('#loginSignUp').modal('hide');
                        $('#astroLoginModal').modal('show');
                    } else {
                        $('#mobileMessage').text(res.message);
                    }
                    $("#validOtp").val("")
                    $('#otpInputGroup').addClass('d-none');
                    $('#sendOtpBtn').removeClass('d-none');
                }
            },
            error: function (e) {
                console.log("mobile error :: ", mobile, e)
                toastr.error(e?.responseJSON?.message);
                $('#mobileMessage').html(e?.responseJSON?.message);
            }
        });
    });
     $('#resendOtpBtn').click(function () {
        const mobile = $('#contactNo').val();
        const countryCode = $('#countryCode').val().trim().replace('+', '');
        const fullNumber = countryCode + mobile;
        $('#otpLoginMessage').text('');
        $('#mobileMessage').text('')
        $.ajax({
            url: '{{ route("api.resendOtp") }}',
            method: 'POST',
            data: {
                contactNo: fullNumber,
                fromWeb: 1,
            },
            success: function (res) {
                console.log("ddd :: ", res)
                makeAction(res.status == 200)
                if (res.status == 200) {
                     toastr.success('OTP resend successfully');
                    $('#mobileMessage').text('');
                    $('#otpInputGroup').removeClass('d-none');
                    $('#sendOtpBtn').addClass('d-none');
                } else {
                     toastr.error(res.message);
                    $('#otpInputGroup').addClass('d-none');
                    $('#sendOtpBtn').removeClass('d-none');
                }
            },
            error: function (e) {
                toastr.error(e?.responseJSON?.message);
            }
        });
    });

    $('#verifyOtpBtn').click(function () {
        const mobile = $('#contactNo').val();
        const otpCode = $('#otpCode').val();
        const code = $('#validOtp').val();
        const countryCode = $('#countryCode').val().trim();

        if (otpCode?.length <= 0) {
            $("#otpLoginMessage").html("Enter OTP")
            return
        }


        if (atob(code) != otpCode) {
            $("#otpLoginMessage").html("Invalid OTP")
            return
        }

        $("#otpLoginMessage").html("")


        $.ajax({
            url: '{{ route("front.verifyOTL") }}',
            method: 'POST',
            data: {
                contactNo: mobile,
                otp: otpCode,
                validOtp: code,
                countryCode: countryCode,
                country: '',
                fromWeb: 1
            },
            success: function (res) {
                console.log(" res :: ", res)
                if (res.status == 200) {
                    location.reload();
                } else {
                    toastr.error(res.message);
                    $('#mobileMessage').text(res.message);
                    console.log("Invalid OTP.")
                }
            },
            error: function (e) {
                console.log("Error verifying OTP.")
                toastr.error(e?.responseJSON?.message);
                $('#mobileMessage').html(e?.responseJSON?.message);
            }
        });
    });

});

function makeAction(action = false) {
    if (action) {
        $("#contactNo").attr("readonly", true)
        $("#contactNo").attr("disabled", true)
        $("#countryCode").attr("readonly", true)
        $("#countryCode").attr("disabled", true)
        $("#header-country-dropdown-container").css('background-color', '#e9ecef')
    }
    if (!action) {
        $("#contactNo").removeAttr("readonly");
        $("#contactNo").removeAttr("disabled");
        $("#countryCode").removeAttr("readonly");
        $("#countryCode").removeAttr("disabled");
        $("#header-country-dropdown-container").css('background-color', '')
    }
}

function editMobileNumber() {
    $("#validOtp").val("")
    $('#otpInputGroup').addClass('d-none');
    $('#sendOtpBtn').removeClass('d-none');
    makeAction(false)
}
</script>
<!-- Added by bhushan borse on 12, June 2025 -->

<script>
    $(document).ready(function() {
        // Initialize Select2

        $('#countryCode').select2({
            dropdownAutoWidth: true,
            width: 'resolve',
            minimumResultsForSearch: 0
        });
    });

</script>


    <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
    <script>
        window.OneSignalDeferred = window.OneSignalDeferred || [];
        OneSignalDeferred.push(async function(OneSignal) {
            await OneSignal.init({
                appId: "{{ $OneSignalAppId->value ?? 'f80dc7c2-6ab0-4332-9e61-75d6a899a3ec' }}",
                allowLocalhostAsSecureOrigin: true,
                notifyButton: {
                    enable: false,
                },
            });

            function syncSubscription(subId) {
                if (!subId) return;
                $.ajax({
                    url: '{{ route('storeSubscriptionId') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        subscription_id_web: subId,
                    },
                    dataType: 'JSON',
                    success: function(response) {
                        console.log('OneSignal web push registered successfully:', subId);
                    },
                    error: function(err) {
                        console.warn('OneSignal registration error:', err);
                    }
                });
            }

            // Sync if already subscribed
            if (OneSignal.User && OneSignal.User.PushSubscription && OneSignal.User.PushSubscription.id) {
                syncSubscription(OneSignal.User.PushSubscription.id);
            }

            // Listen for permission change / subscription creation
            if (OneSignal.User && OneSignal.User.PushSubscription) {
                OneSignal.User.PushSubscription.addEventListener('change', function(event) {
                    if (event.current && event.current.id) {
                        syncSubscription(event.current.id);
                    }
                });
            }

            // Auto-prompt visitor gently after page load
            if (window.Notification && Notification.permission === 'default') {
                setTimeout(async function() {
                    try {
                        await OneSignal.Slidedown.promptPush();
                    } catch (e) {
                        console.log('OneSignal prompt error:', e);
                    }
                }, 3500);
            }
        });
    </script>


<script src="https://www.gstatic.com/firebasejs/7.9.1/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/7.9.1/firebase-auth.js"></script>
<script src="https://www.gstatic.com/firebasejs/7.9.1/firebase-firestore.js"></script>
<script src="https://www.gstatic.com/firebasejs/7.9.1/firebase-storage.js"></script>


<script>
    var firebaseConfig = {
        apiKey: "{{ $apiKey->value }}",
        databaseURL: "{{ $databaseURL->value }}",
        authDomain: "{{ $authDomain->value }}",
        projectId: "{{ $projectId->value }}",
        storageBucket: "{{ $storageBucket->value }}",
        messagingSenderId: "{{ $messagingSenderId->value }}",
        appId: "{{ $appId->value }}",
        measurementId: "{{ $measurementId->value }}"
    };

    firebase.initializeApp(firebaseConfig);
</script>



<script>
    function logout() {
        $.ajax({
            url: "{{ route('front.logout') }}", // URL of your logout route
            type: 'GET',
            success: function(response) {

                toastr.success('Logged out successfully');

                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            },
            error: function(xhr, status, error) {
                toastr.error(error);
            }
        });
    }
</script>

@if (authcheck())
    <script>
        // Store the IDs of notifications that have already triggered a modal
        let processedNotifications = new Set();
        let shownRejections = [];
        try {
            shownRejections = JSON.parse(localStorage.getItem('shownRejections')) || [];
            if (!Array.isArray(shownRejections)) {
                shownRejections = [];
            }
        } catch (e) {
            shownRejections = [];
        }

        function setIds(chatId, astrologerId) {
            document.getElementById('chatIdInput').value = chatId;
            document.getElementById('astrologerIdInput').value = astrologerId;
        }

        function setCallIds(callId, astrologerId, call_type) {
            document.getElementById('callIdInput').value = callId;
            document.getElementById('astrologerIdInput').value = astrologerId;
            document.getElementById('calltypeInput').value = call_type;
        }

        // ---------------------

        // Retrieve last processed ID from localStorage or set it to 0 if not present
        let lastProcessedId = parseInt(localStorage.getItem('lastProcessedId')) || 0;

        setInterval(function() {
            fetch("{{ route('api.getUserNotification', ['token' => $token]) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    const notificationDropdown = document.getElementById('notificationDropdown');
                    const notificationCount = document.getElementById('notificationCount');

                    // Create a container for all notification content
                    let notificationContent = document.createElement('div');

                    if (data.recordList.length > 0) {
                        // Create notification list
                        const notificationList = document.createElement('ul');
                        notificationList.id = 'notificationList';

                        data.recordList.forEach(notification => {
                            const isChatAccepted = notification.chatStatus === 'Accepted';
                            const isCallAccepted = notification.callStatus === 'Accepted';
                            const isCallRejected = notification.callStatus === 'Rejected';

                            // Check if the notification is new
                            if (notification.id > lastProcessedId) {
                                playSound("{{ asset('public/sound/livechat-129007.mp3') }}");
                                localStorage.setItem('lastProcessedId', notification.id);
                                lastProcessedId = notification.id;
                            }

                            // Create notification item
                            const listItem = document.createElement('li');
                            listItem.className = 'd-lg-block';
                            listItem.setAttribute('not-id', notification.id);

                            listItem.innerHTML = `
                                <div>
                                    <a class="dropdown-item"
                                        ${isChatAccepted ?
                                            `onclick="setIds('${notification.chatId}', '${notification.astrologerId}')" data-toggle="modal" data-target="#chatinfomodal"` :
                                            (isCallAccepted ?
                                                `onclick="setCallIds('${notification.callId}', '${notification.astrologerId}', '${notification.call_type}')" ${notification.call_method !== 'exotel' ? 'data-toggle="modal" data-target="#callinfomodal"' : ''}` :
                                                '')}>
                                        <span class="mr-2 accSet accSettingWeb">
                                            <i class="fa-solid fa-bell"></i>
                                        </span>
                                        <span>${notification.title}</span>
                                    </a>
                                </div>
                            `;

                            notificationList.appendChild(listItem);

                            // Process modal opening logic
                            if ((isChatAccepted || isCallAccepted || isCallRejected) && !processedNotifications.has(notification.id)) {
                                processedNotifications.add(notification.id);
                                if (isChatAccepted) {
                                    setIds(notification.chatId, notification.astrologerId);
                                    $('#chatinfomodal').modal('show');
                                } else if (isCallAccepted) {
                                    setCallIds(notification.callId, notification.astrologerId, notification.call_type);
                                    if (notification.call_method != 'exotel') {
                                        $('#callinfomodal').modal('show');
                                    }
                                } else if (isCallRejected) {
                                    // Check if this rejection notification has already been shown
                                    if (!shownRejections.includes(notification.id)) {
                                        // Show a nice Toastr error popup notification for rejection
                                        toastr.error(notification.description || 'Your scheduled call appointment has been rejected by the astrologer.', {
                                            timeOut: 10000,
                                            closeButton: true,
                                            progressBar: true
                                        });

                                        // Mark as shown and persist in localStorage
                                        shownRejections.push(notification.id);
                                        if (shownRejections.length > 100) {
                                            shownRejections = shownRejections.slice(-100);
                                        }
                                        localStorage.setItem('shownRejections', JSON.stringify(shownRejections));

                                        // If user is currently on the appointments page, reload to show status changes
                                        if (window.location.pathname.includes('my-appointment')) {
                                            setTimeout(function() {
                                                window.location.reload();
                                            }, 3000);
                                        }
                                    }
                                }
                            }
                        });

                        notificationContent.appendChild(notificationList);

                        // Add Clear Notifications button
                        const clearButton = document.createElement('a');
                        clearButton.className = 'dropdown-item text-center btn clear-notification';
                        clearButton.id = 'clearNotifications';
                        clearButton.textContent = 'Clear Notifications';
                        clearButton.onclick = function() {
                            // Implement your clear notifications logic here
                            // For example:
                            fetch("{{ route('api.deleteAllUserNotification', ['token' => $token]) }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                            })
                            .then(() => {
                                toastr.success('Notification Cleared Successfully');
                                notificationCount.innerText = '0';
                                notificationDropdown.innerHTML = `
                                    <ul id="notificationList">
                                        <li class="d-lg-block">
                                            <span class="dropdown-item text-center">No Notification Yet</span>
                                        </li>
                                    </ul>
                                `;
                            })
                            .catch(error => console.error('Error clearing notifications:', error));
                        };
                        notificationContent.appendChild(clearButton);

                        notificationCount.innerText = data.recordList.length;
                    } else {
                        // No notifications case
                        notificationContent.innerHTML = `
                            <ul id="notificationList">
                                <li class="d-lg-block">
                                    <span class="dropdown-item text-center">No Notification Yet</span>
                                </li>
                            </ul>
                        `;
                        notificationCount.innerText = '0';
                    }

                    // Replace the dropdown content
                    notificationDropdown.innerHTML = '';
                    notificationDropdown.appendChild(notificationContent);
                })
                .catch(error => console.error('Error fetching notifications:', error));
        }, 4000);

        function playSound(url) {
            const audio = new Audio(url);
            audio.play();
        }

        $('#startchat').click(function(e) {
            e.preventDefault();
            var formData = $('#chatForm').serialize();
            var astrologerId = $("#astrologerIdInput").val();
            var chatId = $("#chatIdInput").val();

            $.ajax({
                url: "{{ route('api.acceptChatRequestFromCustomer', ['token' => $token]) }}",
                type: 'POST',
                data: formData,
                success: function(response) {
                    toastr.success('Chat Started Successfully..Wait');
                    window.location.href = "{{ route('front.chat') }}" + "?astrologerId=" +
                        astrologerId + "&chatId=" + chatId;
                },
                error: function(xhr, status, error) {
                    toastr.error(xhr.responseText);
                }
            });
        });

        // Reject Chat

        $('#rejectchat').click(function(e) {
            e.preventDefault();
            var formData = $('#chatForm').serialize();
            var astrologerId = $("#astrologerIdInput").val();

            $.ajax({
                url: "{{ route('api.rejectChatRequestFromCustomer', ['token' => $token]) }}",
                type: 'POST',
                data: formData,
                success: function(response) {
                    toastr.success('Chat Rejected Successfully.');
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                },
                error: function(xhr, status, error) {
                    toastr.error(xhr.responseText);
                }
            });
        });


        // Start Call

        $('#startcall').click(function(e) {
            e.preventDefault();
            var formData = $('#callForm').serialize();
            var astrologerId = $("#astrologerIdInput").val();
            var callId = $("#callIdInput").val();
            var call_type = $("#calltypeInput").val();


            $.ajax({
                url: "{{ route('api.acceptCallRequestFromCustomer', ['token' => $token]) }}",
                type: 'POST',
                data: formData,
                success: function(response) {
                    toastr.success('Call Started Successfully..Wait');
                    window.location.href = "{{ route('front.call') }}" + "?astrologerId=" +
                        astrologerId + "&callId=" + callId + "&call_type=" + call_type;
                },
                error: function(xhr, status, error) {
                    toastr.error(xhr.responseText);
                }
            });
        });


        // Reject Call

        $('#rejectcall').click(function(e) {
            e.preventDefault();
            var formData = $('#callForm').serialize();
            var astrologerId = $("#astrologerIdInput").val();

            $.ajax({
                url: "{{ route('api.rejectCallRequestFromCustomer', ['token' => $token]) }}",
                type: 'POST',
                data: formData,
                success: function(response) {
                    toastr.success('Call Rejected Successfully.');
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                },
                error: function(xhr, status, error) {
                    toastr.error(xhr.responseText);
                }
            });
        });
        $('#clearNotifications').click(function(e) {
            e.preventDefault();
            $.ajax({
                url: "{{ route('api.deleteAllUserNotification', ['token' => $token]) }}",
                type: 'POST',
                success: function(response) {
                    toastr.success('Notification Cleared Successfully');

                },
                error: function(xhr, status, error) {
                    toastr.error(xhr.responseText);
                }
            });
        });
    </script>
@endif

<script>

    let countdownInterval;

    function startOtpTimer() {
        let countdown = 30;
        let $btn = $('#resendOtpBtn');
        $btn.prop('disabled', true).html(`Resend OTP in <span id="timer">${countdown}</span>s`);
        $btn.prop('disabled', true).removeClass('text-primary').addClass(`text-info`);

        clearInterval(countdownInterval); // clear previous
        countdownInterval = setInterval(function () {
            countdown--;
            $('#timer').text(countdown);
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                $btn.prop('disabled', false).removeClass('text-info').addClass(`text-primary`).text('Resend OTP');
            }
        }, 1000);
    }

</script>
<script>
    document.getElementById('googleLoginBtn').addEventListener('click', async function () {
        const provider = new firebase.auth.GoogleAuthProvider();
        firebase.auth().signInWithPopup(provider)
        .then(async (result) => {
            const idToken = await result.user.getIdToken();

            // Send token to backend
            console.log(" response 1539 :: ", result.user)

            $.ajax({
                url: '{{ route("front.verifyOTL") }}',
                method: 'POST',
                data: {
                    fromWeb: 1,
                    isGoogleLogin: 1,
                    email: result.user?.email,
                    name: result.user?.displayName
                },
                success: function (res) {
                    console.log(" res :: ", res)
                    if (res.status == 200) {
                        location.reload();
                    } else {
                        if (res.message === 'This email is registered as an astrologer') {
                            $('#loginSignUp').modal('hide');
                            $('#astroLoginModal').modal('show');
                        } else {
                            toastr.error("Login failed: " + res.message);
                        }
                    }
                },
                error: function (e) {
                    if (e?.responseJSON?.message === 'This email is registered as an astrologer') {
                        $('#loginSignUp').modal('hide');
                        $('#astroLoginModal').modal('show');
                    } else {
                        toastr.error(e?.responseJSON?.message);
                    }
                }
            });
        })
        .catch((error) => {
            toastr.error(error?.responseJSON?.message);
        });
    });

    // Scroll listener for sticky transparent-to-solid navbar transitions
    function handleNavbarScroll() {
        var nav = document.querySelector('.unified-navbar');
        if (!nav) return;
        if (window.scrollY > 30) {
            nav.classList.add('scrolled');
        } else {
            nav.classList.remove('scrolled');
        }
    }
    window.addEventListener('scroll', handleNavbarScroll);
    document.addEventListener('DOMContentLoaded', handleNavbarScroll);
</script>
