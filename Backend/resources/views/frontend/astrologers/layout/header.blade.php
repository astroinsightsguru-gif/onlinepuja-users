@php
    use Symfony\Component\HttpFoundation\Session\Session;

    $session = new Session();
    $chatrequest = $chatRequests;

    $logo = $systemFlags->get('PartnerLogo');
    $appName = $systemFlags->get('AppName');

    $agoraAppIdValue = $systemFlags->get('AgoraAppId');
    $agorcertificateValue = $systemFlags->get('AgoraAppCertificate');

    $channel_name = 'Online PujaGuruLive_' . $astrologerId;

    $OneSignalAppId = $systemFlags->get('OneSignalAppId');
    $appId = $systemFlags->get('firebaseappId');
    $measurementId = $systemFlags->get('firebasemeasurementId');
    $messagingSenderId = $systemFlags->get('firebasemessagingSenderId');
    $storageBucket = $systemFlags->get('firebasestorageBucket');
    $projectId = $systemFlags->get('firebaseprojectId');
    $authDomain = $systemFlags->get('firebaseauthDomain');
    $databaseURL = $systemFlags->get('firebasedatabaseURL');
    $apiKey = $systemFlags->get('firebaseapiKey');
    $otplessAppId = $systemFlags->get('otplessAppId');

    $freekundali = $systemFlags->get('FreeKundali');
    $kundali_matching = $systemFlags->get('KundaliMatching');
    $panchang = $systemFlags->get('TodayPanchang');
    $blog = $systemFlags->get('Blog');
    $shop = $systemFlags->get('Astromall');
    $daily_horoscope = $systemFlags->get('DailyHoroscope');
    $Livesection = $systemFlags->get('Livesection');
    $puja = $systemFlags->get('Puja');
@endphp

    <style>
        body.modal-open {
            overflow-y: scroll !important;
        }

        .scrollable-menu {
            max-height: 450px;
            /* Adjust this value as needed */
            overflow-y: auto;

        }

        .pac-container {
            z-index: 10000 !important;
        }

        .pac-container:after {
            content: none !important;
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


        .hidden {
            display: none;
        }


        .profilediv {
            border-radius: 50%;
            display: inline-block;
            padding: 3px;
            background: linear-gradient(#fffefe 0 0) padding-box, linear-gradient(to right, #9c20aa, #fb3570) border-box;
            border: 3px solid transparent;
        }


        .navbar-collapse {
            position: unset !important;
        }

        nav.navbar.navbar-expand-lg.navbar-light.top-navbar {
            background: #f4f4f5;
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

        .pac-container {
            z-index: 10000 !important;
        }

        .pac-container:after {
            content: none !important;
        }

        @media (max-width: 767px) {
            li .liveastro {
                display: none !important;
            }
        }
    </style>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    {{-- Modal --}}

    <div class="modal fade rounded mt-2 mt-md-5 " id="storymodal" tabindex="-1" role="dialog"
        aria-labelledby="myLargeModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title font-weight-bold">
                        Story Form
                    </h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body pt-0 pb-0">
                    <div class="bg-white body">
                        <div class="row ">
                            <div class="col-lg-12 col-12 ">
                                <div class="mb-3 ">
                                    <form class="px-3 font-14" method="post" id="storyForm" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-12 col-md-12 py-2">
                                                <div class="form-group">
                                                    <label for="Type">Select Type <span
                                                            class="color-red">*</span></label>
                                                    <select class="form-control" id="Type" name="mediaType"
                                                        onchange="toggleInputFields()">
                                                        <option value="text">Text</option>
                                                        <option value="image">Image</option>
                                                        <option value="video">Video</option>
                                                    </select>
                                                    <input type="hidden" name="astrologerId"
                                                        value="{{ $astrologerId }}">
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-12 py-2 hidden" id="text-input-group">
                                                <div class="form-group mb-0">
                                                    <label for="textInput">Text<span class="color-red">*</span></label>
                                                    <input id="textInput" name="media"
                                                        class="form-control border-pink matchInTxt shadow-none">
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-12 py-2 hidden" id="image-input-group">
                                                <div class="form-group mb-0">
                                                    <label for="fileInput">File<span class="color-red">*</span></label>
                                                    <input type="file" id="fileInput" name="media[]" multiple
                                                        accept=".jpeg, .png, .gif" style="height:44px;"
                                                        class="form-control border-pink matchInTxt shadow-none">
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-12 py-2 hidden" id="video-input-group">
                                                <div class="form-group mb-0">
                                                    <label for="videoFileInput">File<span class="color-red">*</span></label>
                                                    <input type="file" id="videoFileInput" name="videoMedia"
                                                        accept="video/*" style="height:44px;"
                                                        class="form-control border-pink matchInTxt shadow-none">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-12 py-3">
                                            <div class="row">
                                                <div class="col-12 pt-md-3 text-center mt-2">
                                                    <button class="font-weight-bold ml-0 w-100 btn btn-chat"
                                                        id="callloaderintakeBtn" type="button" style="display:none;"
                                                        disabled>
                                                        <span class="spinner-border spinner-border-sm" role="status"
                                                            aria-hidden="true"></span> Loading...
                                                    </button>
                                                    <button type="submit" class="btn btn-block btn-chat px-4 px-md-5 mb-2"
                                                        id="storyBtn">Upload Story</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- end --}}

    <!-- Story Modal -->
    <div class="modal fade" id="storyModal" tabindex="-1" aria-labelledby="storyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <img id="astrologerProfileImage" src="" alt="Astrologer Profile Image" class="rounded-circle"
                        style="height: 40px;width:40px">
                    <span class="modal-title mt-2 ml-2" id="astrologerName"></span>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
                        <ol class="carousel-indicators" id="carouselIndicators"></ol>
                        <div class="carousel-inner" id="carouselInner"></div>
                        <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button"
                            data-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="sr-only">Previous</span>
                        </a>
                        <a class="carousel-control-next" href="#carouselExampleIndicators" role="button"
                            data-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="sr-only">Next</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>



    {{-- End --}}


    <div class="header">
        <nav class="navbar navbar-expand-lg navbar-light top-navbar">
            <div class="container d-flex justify-content-between align-items-center">
                <!-- Left Side: Toggle Button + Logo + Subtext -->
                <div class="d-flex align-items-center">
                    <button class="navbar-toggler ml-2" type="button" data-toggle="collapse" data-target="#navbarNav"
                        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <a class="text-decoration-none" href="{{ route('front.astrologerindex') }}">
                        <div class="d-flex align-items-center ml-2">
                            <img src="{{ asset($logo->value) }}" alt="{{ $appName->value }}" class="img-fluid" width="50"
                                height="50">
                            <div class="onlinepuja-logo-ntext ml-2">
                                <span class="onlinepuja-logo-text notranslate">{{ $appName->value }}</span>
                                {{-- <span class="onlinepuja-logo-subtext notranslate">Consult Online {{ $professionTitle }}s Anytime</span> --}}
                            </div>
                        </div>
                    </a>
                </div>

                <div class="d-flex align-items-center">
                    <li class="list-inline-item liveastro" style="margin-right: 25px;">
                        <div class="dropdown liveastro">
                            <a class="btn btn-outline-primary dropdown-toggle px-3 py-2" href="#" role="button"
                                id="" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-sun me-1"></i> Live Astrologer
                            </a>

                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2 rounded-3"
                                aria-labelledby=""
                                style="min-width: 220px;">

                                {{-- ✅ Go Live --}}
                                <li>
                                    @if (empty($astologerliveSectionCheck) || $astologerliveSectionCheck->isActive == 0)
                                    <div class="btn-groups d-none d-lg-flex mr-md-3">
                                        <input type="hidden" name="astrologerId" value="{{ $astrologerId }}">
                                        <a href="{{ route('front.talkList') }}" id="goLiveBtn"
                                            class="btn btn-chat-astro goLiveBtn other-country">
                                            <i class="fa-solid fa-circle-play"></i> Go Live
                                        </a>
                                    </div>
                                    @endif
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>

                                {{-- ✅ Live Schedule --}}
                                <li>
                                    <form class="m-0">
                                        <input type="hidden" name="astrologerId" value="">
                                        <a href="javascript:void(0);" id="ScheduleLiveBtn"
                                            class="dropdown-item d-flex align-items-center fw-semibold text-warning ScheduleLiveBtn">
                                            <i class="fa-solid fa-video me-2"></i> &nbsp;&nbsp; Live Schedule
                                        </a>
                                    </form>
                                </li>


                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                {{-- ✅ Live Schedule List --}}
                                <li>
                                    <form class="m-0">
                                        <input type="hidden" id="astrologerId" value="{{ $astrologerId }}">
                                        <a href="{{ route('astro.schedules') }}" id="ScheduleListBtn"
                                            class="dropdown-item d-flex align-items-center fw-semibold text-info ScheduleListBtn">
                                            <i class="fa-solid fa-list-ul me-2"></i> &nbsp;&nbsp; Schedule List
                                        </a>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <button type="button" class="btn btn-success mr-3" data-bs-toggle="modal" data-bs-target="#profileBoostModal">
                        Boost My Profile
                    </button>

                    <!-- Live Astrologer Dropdown -->


                    <!-- Vedic Dual Language Switcher (Astrologer Portal) -->
                    <div class="vedic-lang-pill notranslate d-none d-md-flex align-items-center mr-3">
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


                    @if ($isLoggedIn)

                    <li class="list-inline-item">
                        <div class="dropdown ">
                            <a class="btn dropdown-toggle p-0" role="button" id="dropdownMenuLink"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">

                                @if ($user->profile)
                                <img src="{{ asset($user->profile) }}" alt="User"
                                    class="img-fluid rounded" style="height: 50px;width:50px">
                                @elseif(isset($user->profileImage) && $user->profileImage)
                                <img src="{{ asset($user->profileImage) }}" alt="User"
                                    class="img-fluid rounded" style="height: 50px;width:50px">
                                @else
                                <img src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/images/user-img.png') }}"
                                    alt="" class="psychic-img img-fluid rounded">
                                @endif


                            </a>
                            <div class="dropdown-menu user-options fadeInUp5px dropdown-menu-right dropdown-menu-lg-left"
                                aria-labelledby="dropdownMenuLink">
                                <ul>
                                    <li class="namedisplay d-block text-center">


                                        @if ($stories && count($stories) > 0)

                                        <div class="position-relative profilediv">
                                            @if ($user->profile)
                                            <img src="{{ asset($user->profile) }}" alt="User"
                                                class="img-fluid astrostory rounded-circle">
                                            @elseif(isset($user->profileImage) && $user->profileImage)
                                            <img src="{{ asset($user->profileImage) }}" alt="User"
                                                class="img-fluid astrostory rounded-circle">
                                            @else
                                            <img src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/images/user-img-new.png') }}"
                                                alt="User" class="img-fluid astrostory rounded-circle">
                                            @endif
                                            <i class="fa-solid fa-circle-plus position-absolute"
                                                style="right: -10px;" data-toggle="modal"
                                                data-target="#storymodal"></i>
                                        </div>
                                        @else
                                        <div class="position-relative ">
                                            @if ($user->profile)
                                            <img src="{{ asset($user->profile) }}" alt="User"
                                                class="img-fluid rounded">
                                            @else
                                            <img src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/images/user-img-new.png') }}"
                                                alt="User" class="img-fluid rounded">
                                            @endif
                                            <i class="fa-solid fa-circle-plus position-absolute"
                                                style="right: 63px;" data-toggle="modal"
                                                data-target="#storymodal"></i>
                                        </div>
                                        @endif


                                        <div>
                                            <h2 class="pt-3">
                                                {{ $user->name ?: 'User' }}

                                            </h2>
                                            <h3></h3>
                                        </div>
                                    </li>
                                    <li class="d-lg-block">
                                        <div>
                                            <a class="dropdown-item " href="{{ route('astro-appointment') }}">
                                                <span class="mr-2 accSet accSettingWeb">
                                                    <i class="fa-solid fa-user"></i>

                                                </span>
                                                <span>My Appointment</span>
                                            </a>
                                        </div>
                                    </li>
                                    <li class="d-lg-block">
                                        <div>
                                            <a class="dropdown-item " href="{{ route('front.profileupdate') }}">
                                                <span class="mr-2 accSet accSettingWeb">
                                                    <i class="fa-solid fa-user"></i>

                                                </span>
                                                <span>My Account</span>
                                            </a>
                                        </div>
                                    </li>

                                    <li class="d-block">
                                        <div>
                                            <a class="dropdown-item d-flex justify-content-between align-items-center pr-2"
                                                href="{{ route('front.getAstrologerWallet') }}">
                                                <span>
                                                    <span class="mr-2">
                                                        <i class="fa-solid fa-wallet"></i>
                                                    </span>

                                                    <span>My Wallet</span>
                                                </span>
                                                <span class="gWalletbalance color-red bg-pink"
                                                    style="border-radius:20px; padding:2px 10px; font-size:12px;">
                                                    @if($walletType == 'Coin')
                                                        <img src="{{ asset($coinIcon) }}" alt="Wallet Icon" width="15">
                                                    @else
                                                        {{ $currency['value'] }}
                                                    @endif
                                                    {{ number_format($getProfile['data']['totalWalletAmount'], 2) }}</span>

                                            </a>
                                        </div>
                                    </li>
                                    <li class="d-block">
                                        <div>
                                            <a class="dropdown-item" href="{{ route('front.getAstrologerChat') }}">
                                                <span class="mr-2">
                                                    <i class="fa-solid fa-comment-dots"></i>
                                                </span>
                                                <span>My Chats</span>
                                            </a>
                                        </div>
                                    </li>
                                    <li class="d-block">
                                        <div>
                                            <a class="dropdown-item" href="{{ route('front.astrologers.profile-boost-history') }}">
                                                <span class="mr-2">
                                                    <i class="fa-solid fa-comment-dots"></i>
                                                </span>
                                                <span>Profile Boost History</span>
                                            </a>
                                        </div>
                                    </li>

                                    <li class="d-block">
                                        <div>
                                            <a class="dropdown-item" href="{{ route('front.getAstrologerCall') }}">
                                                <span class="mr-2">
                                                    <i class="fa-solid fa-phone"></i>
                                                </span>
                                                <span>My Calls</span>
                                            </a>
                                        </div>
                                    </li>

                                    <li class="d-block">
                                        <div>
                                            <a class="dropdown-item" href="{{ route('front.getAstrologerReport') }}">
                                                <span class="mr-2">
                                                    <i class="fa-solid fa-file"></i>
                                                </span>
                                                <span>My Report</span>
                                            </a>
                                        </div>
                                    </li>
                                    <li class="d-block">
                                        <div>
                                            <a class="dropdown-item" href="{{ route('front.followerslist') }}">
                                                <span class="mr-2">
                                                    <i class='fas fa-user-check'></i>
                                                </span>
                                                <span>My Followers</span>
                                            </a>
                                        </div>
                                    </li>

                                   <li class="d-block">
                                        <div>
                                            <a class="dropdown-item" href="{{ route('front.astrologers.puja-list') }}">
                                                <span class="mr-2">
                                                    <i class="fa fa-list"></i>
                                                </span>
                                                <span>My Puja Lists</span>
                                            </a>
                                        </div>
                                    </li>

                                    <li class="d-block">
                                        <div>
                                            <a class="dropdown-item" href="{{ route('front.pujalist') }}">
                                                <span class="mr-2">
                                                    <i class="fa fa-list"></i>
                                                </span>
                                                <span>Puja Order Lists</span>
                                            </a>
                                        </div>
                                    </li>
                                    <li class="d-block">
                                        <div>
                                            <a class="dropdown-item" href="{{ route('front.astrologers.tickets') }}">
                                                <span class="mr-2">
                                                    <i class="fa-solid fa-headset"></i>
                                                </span>
                                                <span>Support Ticket</span>
                                            </a>
                                        </div>
                                    </li>


                                    <li class="d-block">
                                        <div>
                                            <a class="dropdown-item" id="logout" href="javascript:void()"
                                                onclick="logout()">
                                                <span class="mr-2">
                                                    <i class="fa-solid fa-right-from-bracket"></i>
                                                </span>
                                                <span>Sign Out</span>
                                            </a>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </li>


                    <li class="list-inline-item ml-4">
                        <div class="dropdown">
                            <a class="btn  p-0" style="width: 30px" role="button" id="dropdownMenuLink"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                <i class="fa-solid fa-bell"></i>
                                <span class="badge badge-danger badge-counter" id="notificationCount">0</span>
                            </a>
                            <div class="dropdown-menu user-options fadeInUp5px dropdown-menu-right dropdown-menu-lg-left scrollable-menu"
                                aria-labelledby="dropdownMenuLink" id="notificationDropdown">
                                <ul id="notificationList">
                                    @foreach ($getUserNotification['recordList'] as $notification)
                                    <li class="d-lg-block @if ($notification['chatStatus'] == 'Accepted' || $notification['callStatus'] == 'Accepted') bg-pink @endif">
                                        <div>
                                            <a class="dropdown-item"
                                                @if ($notification['chatStatus']=='Accepted' ) onclick="setIds('{{ $notification['chatId'] }}', '{{ $notification['astrologerId'] }}')" data-toggle="modal" data-target="#chatinfomodal"
                                                @elseif($notification['callStatus']=='Accepted' ) onclick="setCallIds('{{ $notification['callId'] }}', '{{ $notification['astrologerId'] }}')" data-toggle="modal" data-target="#callinfomodal" @endif>
                                                <span class="mr-2 accSet accSettingWeb">
                                                    <i class="fa-solid fa-bell"></i>
                                                </span>
                                                <span>{{ $notification['title'] }}</span>
                                            </a>
                                        </div>
                                    </li>
                                    @endforeach
                                </ul>
                                @if (count($getUserNotification['recordList']) > 0)
                                <a class="dropdown-item text-center btn clear-notification"
                                    id="clearNotifications">Clear Notifications</a>
                                @else
                                <ul id="notificationList">
                                    <li class="d-lg-block">
                                        <span class="dropdown-item text-center ">No Notification Yet</span>
                                    </li>
                                </ul>
                                @endif
                            </div>
                        </div>
                    </li>

                    @endif

                </div>
            </div>
        </nav>



        <nav class="navbar navbar-expand-lg navbar-light" style="z-index: 1">
            <div class="container">
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mx-auto">

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle font-weight-semi-bold nav-link-mobile" href="#"
                                id="matchingDropdown" role="button" data-toggle="dropdown" aria-haspopup="true"
                                aria-expanded="false">
                                <img src="{{asset($freekundali->value)}}" alt="" height="20" width="20"> Kundali
                            </a>
                            <div class="dropdown-menu" aria-labelledby="matchingDropdown">
                                <a class="dropdown-item" href="{{ route('front.astrologers.kundaliMatch') }}">Kundali
                                    Matching</a>
                                <a class="dropdown-item" href="{{ route('front.astrologers.getkundali') }}">Free Janam
                                    Kundali</a>
                            </div>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link font-weight-semi-bold nav-link-mobile"
                                href="{{ route('front.astrologers.getPanchang') }}"><img src="{{asset($panchang->value)}}" alt="" height="20" width="20"> Panchang</a>
                        </li>


                        <li class="nav-item">
                            <a class="nav-link font-weight-semi-bold nav-link-mobile"
                                href="{{ route('front.astrologers.horoScope') }}"><img src="{{asset($daily_horoscope->value)}}" alt="" height="20" width="20"> Horoscope</a>
                        </li>


                        <li class="nav-item">
                            <a class="nav-link font-weight-semi-bold nav-link-mobile"
                                href="{{ route('front.astrologers.getBlog') }}"><img src="{{asset($blog->value)}}" alt="" height="20" width="20"> Blog</a>
                        </li>

                        <div style="display: inline-flex; align-self: center;">
                            <li class="nav-item d-lg-none mb-2 mt-2 text-center">
                                <input type="hidden" name="astrologerId" value="{{ $astrologerId }}">
                                <a href="{{ route('front.talkList') }}" id="goLiveBtn"
                                    class="btn btn-chat goLiveBtn other-country"><i class="fa-solid fa-circle-play"></i>
                                    Go Live</a>
                            </li>

                            <li class="nav-item d-lg-none mb-2 mt-2 text-center">
                                <form class="m-0">
                                    <input type="hidden" name="astrologerId" value="">
                                    <a href="javascript:void(0);" id="mobileScheduleLiveBtn"
                                        class="btn btn-chat other-country">
                                        <i class="fa-solid fa-video me-2"></i> &nbsp;&nbsp; Live Schedule
                                    </a>
                                </form>
                            </li>


                            <li class="nav-item d-lg-none mb-2 mt-2 text-center">
                                <input type="hidden" name="astrologerId" value="{{ $astrologerId }}">
                                <a href="{{ route('astro.schedules') }}" id="ScheduleListBtn"
                                    class="btn btn-chat other-country"><i class="fa-solid fa-list-ul me-2"></i>
                                    Schedule List</a>
                            </li>
                        </div>
                    </ul>
                </div>
            </div>
        </nav>
    </div>


    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-3">

                <!-- Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="scheduleModalLabel">Upcomming Live Session</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                  <form id="scheduleForm">
                    @csrf
                    <input type="hidden" name="astrologerId" value="{{ $astrologerId }}">

                    <div class="mb-3">
                      <label for="scheduleDate" class="form-label">Select Date</label>
                      <input type="date" class="form-control" id="scheduleDate" name="schedule_live_date" required>
                    </div>

                    <div class="mb-3">
                      <label for="scheduleTime" class="form-label">Select Time</label>
                      <input type="time" class="form-control" id="scheduleTime" name="schedule_live_time" required>
                    </div>
                  </form>
                </div>
                <!-- Footer -->
                <div class="modal-footer">
                    <button type="submit" form="scheduleForm" class="btn btn-primary">Save Schedule</button>
                </div>

            </div>
        </div>
    </div>

    <!-- Profile Boost Modal -->
    <div class="modal fade" id="profileBoostModal" tabindex="-1" aria-labelledby="profileBoostModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

                <!-- Header -->
                <div class="modal-header text-white border-0" style="background: linear-gradient(135deg, #4F46E5, #3B82F6);">
                    <h5 class="modal-title fw-bold text-white" id="profileBoostModalLabel">✨ Confirm Profile Boost</h5>
                </div>

                <!-- Body -->
                <form id="profileBoostForm" action="{{route('front.astrologers.profile-boost-store')}}" method="post">
                    <div class="modal-body p-4">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6 mb-2"><strong>💬 Chat Commission:</strong> {{ $profileBoostData['recordList']['chat_commission'] }}%</div>
                            <div class="col-md-6 mb-2"><strong>📞 Call Commission:</strong> {{ $profileBoostData['recordList']['call_commission'] }}%</div>
                            <div class="col-md-6 mb-2"><strong>🎥 Video Call Commission:</strong> {{ $profileBoostData['recordList']['video_call_commission'] }}%</div>
                            <div class="col-md-6 mb-2"><strong>⚡ Remaining Boosts:</strong> {{ $profileBoostData['recordList']['remaining_boost'] }}</div>
                        </div>

                        <div class="p-3 rounded-3 bg-light">
                            <h6 class="fw-bold mb-3 text-primary">🌟 Profile Boost Benefits:</h6>
                            <ul class="mb-0 ps-3">
                                @foreach ($profileBoostData['recordList']['profile_boost_benefits'] as $benefit)
                                <li class="mb-2">{{ $benefit }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer border-0 d-flex justify-content-end gap-2 p-3">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-gradient px-4 fw-semibold" id="boostSubmit">Boost Now 🚀</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Gradient Button + Smooth Animation Styles -->
    <style>
        .btn-gradient {
            background: linear-gradient(135deg, #4F46E5, #3B82F6);
            color: #fff;
            border: none;
            transition: all 0.3s ease-in-out;
        }

        .btn-gradient:hover {
            background: linear-gradient(135deg, #4338CA, #2563EB);
            transform: translateY(-2px);
        }

        .modal-content {
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>

    <!-- Script -->
    <script>
        document.getElementById("ScheduleLiveBtn").addEventListener("click", function(e) {
            e.preventDefault();
            var scheduleModal = new bootstrap.Modal(document.getElementById('scheduleModal'));
            scheduleModal.show();
        });
    </script>



<!-- Add jQuery (Required for AJAX) -->
<!-- Include jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Include SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
   $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    });
    $(document).ready(function () {
    $("#profileBoostForm").on("submit", function (e) {
        e.preventDefault();

        let form = $(this);
        let url = form.attr("action");
        let formData = form.serialize() + '&_token={{ csrf_token() }}';

        $("#boostSubmit").prop("disabled", true).text("Boosting... 🚀");

        $.ajax({
            type: "POST",
            url: url,
            data: formData,
            success: function (response) {
                if(response.success){
                    toastr.success("🎉 Profile boosted successfully!");
                    $("#boostSubmit").prop("disabled", false).text("Boost Now 🚀");
                    $("#profileBoostModal").modal("hide");
                }else{
                    toastr.error(response.message);

                }

                // Close modal (replace #profileBoostModal with your modal ID)
            },
            error: function (xhr) {
                toastr.error("❌ Something went wrong. Please try again.");
                $("#boostSubmit").prop("disabled", false).text("Boost Now 🚀");
            }
        });
    });
});
  $('#scheduleForm').on('submit', function(e) {
    e.preventDefault();

    let formData = new FormData(this);
    let submitBtn = $(this).find('button[type="submit"]');
    submitBtn.prop('disabled', true).text('Saving...');

    // ✅ Add CSRF token manually (important for Laravel)
    formData.append('_token', '{{ csrf_token() }}');

    $.ajax({
      url: "{{ url('/api/addLiveScheduleweb') }}", // Laravel route
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        submitBtn.prop('disabled', false).text('Save Schedule');

        if (response.status) {
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: response.message,
            confirmButtonText: 'OK'
          }).then(() => {
            // ✅ Redirect to the schedules page after clicking OK
            window.location.href = "{{url('/astro/schedules')}}";
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: response.message
          });
        }
      },
      error: function(xhr) {
        submitBtn.prop('disabled', false).text('Save Schedule');
        let message = 'Something went wrong. Please try again.';

        if (xhr.responseJSON && xhr.responseJSON.message) {
          message = xhr.responseJSON.message;
        }

        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: message
        });
      }
    });
  });
</script>





    <!-- Script -->
    <script>
        document.getElementById("mobileScheduleLiveBtn").addEventListener("click", function(e) {
            e.preventDefault();
            var mobilescheduleModal = new bootstrap.Modal(document.getElementById('mobilescheduleModal'));
            mobilescheduleModal.show();
        });
    </script>

    <script src="https://www.gstatic.com/firebasejs/7.9.1/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/7.9.1/firebase-auth.js"></script>
    <script src="https://www.gstatic.com/firebasejs/7.9.1/firebase-firestore.js"></script>
    <script src="https://www.gstatic.com/firebasejs/7.9.1/firebase-storage.js"></script>

    <script>
        function toggleInputFields() {
            var type = document.getElementById("Type").value;
            var textInputGroup = document.getElementById("text-input-group");
            var imageInputGroup = document.getElementById("image-input-group");
            var videoInputGroup = document.getElementById("video-input-group");

            textInputGroup.classList.add("hidden");
            imageInputGroup.classList.add("hidden");
            videoInputGroup.classList.add("hidden");

            if (type === "text") {
                textInputGroup.classList.remove("hidden");
            } else if (type === "image") {
                imageInputGroup.classList.remove("hidden");
            } else if (type === "video") {
                videoInputGroup.classList.remove("hidden");
            }
        }

        // Initialize the form with the correct input fields shown based on the default selection
        window.onload = function() {
            toggleInputFields();
        }
    </script>

    @if ($isLoggedIn)
    <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize OneSignal
            window.OneSignalDeferred = window.OneSignalDeferred || [];
            OneSignalDeferred.push(async function(OneSignal) {
                await OneSignal.init({
                    appId: "{{ $OneSignalAppId->value }}",
                });
            });

            // Check and request notification permission using the browser API
            const checkNotificationPermission = async () => {
                const permission = Notification.permission;

                if (permission === 'default') {
                    try {
                        const newPermission = await Notification.requestPermission();

                        if (newPermission === 'granted') {
                            const subscriptionId = OneSignal.User.PushSubscription.id;

                            if (subscriptionId) {
                                // Send the subscription ID to the server
                                $.ajax({
                                    url: "{{ route('storeSubscriptionIdForAstro') }}",
                                    type: 'POST',
                                    data: {
                                        subscription_id_web: subscriptionId,
                                    },
                                    dataType: 'JSON',
                                    success: function(response) {
                                        console.log('Subscription ID stored:', response);
                                    },
                                    error: function(err) {
                                        console.error('Error storing subscription ID:', err);
                                    },
                                });
                            }
                        } else {
                            console.log('Permission not granted or blocked:', newPermission);
                        }
                    } catch (error) {
                        console.error('Error requesting notification permission:', error);
                    }
                } else if (permission === 'granted') {
                    console.log('Notification permission already granted.');
                } else {
                    console.log('Notification permission denied or blocked.');
                }
            };

            // Automatically check notification permission when the page loads
            checkNotificationPermission();
        });
    </script>
    @endif



    <script>
        var firebaseConfig = {
            apiKey: "{{ $apiKey['value'] }}",
            databaseURL: "{{ $databaseURL['value'] }}",
            authDomain: "{{ $authDomain['value'] }}",
            projectId: "{{ $projectId['value'] }}",
            storageBucket: "{{ $storageBucket['value'] }}",
            messagingSenderId: "{{ $messagingSenderId['value'] }}",
            appId: "{{ $appId['value'] }}",
            measurementId: "{{ $measurementId['value'] }}"
        };

        firebase.initializeApp(firebaseConfig);
    </script>

    <script>
        function logout() {
            $.ajax({
                url: "{{ route('front.astrologerlogout') }}", // URL of your logout route
                type: 'GET',
                success: function(response) {

                    toastr.success('Logged out successfully');

                    setTimeout(function() {
                        window.location.href = "{{ route('front.home') }}";
                    }, 1000);
                },
                error: function(xhr, status, error) {
                    toastr.error(error);
                }
            });
        }
    </script>
  <script>
      $(document).ready(function() {
    $('.goLiveBtn').click(function(e) {
        e.preventDefault();

        Swal.fire({
            title: 'Do you want to start a live session?',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Go Live'
        }).then((result) => {
            if (result.isConfirmed) {

                // Step 1: Get RTC Token
                $.ajax({
                    url: '{{ route('api.generateRtcToken') }}',
                    type: 'POST',
                    data: {
                        appID: '<?= $agoraAppIdValue->value ?>',
                        appCertificate: '<?= $agorcertificateValue->value ?>',
                        channelName: '{{ $channel_name }}'
                    },
                    success: function(response) {
                        var RtcToken = response.rtcToken;

                        // Step 2: Add Live Astrologer
                        $.ajax({
                            url: '{{ route('api.addLiveAstrologerWeb') }}',
                            type: 'POST',
                            data: {
                                channelName: '{{ $channel_name }}',
                                token: RtcToken,
                                astrologerId: '{{ $astrologerId }}'
                            },
                            success: function(response_live) {
                                toastr.success('Go live successfully');
                                console.log(response_live);

                                // Step 3: Redirect immediately without waiting for notification to complete
                                window.location.href = "{{ route('front.LiveAstrologers') }}";

                                // Step 4: Trigger background process to send notification
                                setTimeout(function() {
                                    $.ajax({
                                        url: '{{ route('api.sendNotificationForliveAstro') }}',
                                        type: 'POST',
                                        data: {
                                            astrologerId: '{{ $astrologerId }}'
                                        },
                                        success: function(response_live) {
                                            console.log('Background process completed:', response_live);
                                        },
                                        error: function(xhr, status, error) {
                                            console.error('Error in background process:', xhr.responseText);
                                        }
                                    });
                                }, 1000); // Small delay to ensure redirection happens first
                            },
                            error: function(xhr, status, error) {
                                var errorMessage = JSON.parse(xhr.responseText).error.paymentMethod[0];
                                toastr.error(errorMessage);
                            }
                        });

                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });

            }
        });
    });
});



    // Notification Update
    @php
        $token = $session->get('astrotoken');
    @endphp
   // Retrieve last processed ID from localStorage or set it to 0 if not present
let lastProcessedId = parseInt(localStorage.getItem('lastProcessedId')) || 0;

setInterval(function () {
    fetch("{{ route('api.getUserNotification', ['token' => $token]) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            const notificationList = document.getElementById('notificationList');
            const notificationCount = document.getElementById('notificationCount');

            // Retrieve the ID of the first notification in the current list
            let firstChildId = $("#notificationList li:first-child").attr('not-id');
            firstChildId = firstChildId == undefined ? 0 : parseInt(firstChildId);

            notificationList.innerHTML = ''; // Clear the notification list
            notificationCount.innerText = data.recordList.length; // Update notification count

            data.recordList.forEach(notification => {
                const ChatStatus = notification.chatStatus === 'Pending';
                const CallStatus = notification.callStatus === 'Pending';

                // Check if the notification is new and play sound
                if (notification.id > lastProcessedId ) {
                    playSound("{{ asset('public/sound/livechat-129007.mp3') }}");
                    lastProcessedId = notification.id; // Update the lastProcessedId
                    localStorage.setItem('lastProcessedId', lastProcessedId); // Save to localStorage
                }

                // Determine route and background class based on status
                let route = '#';
                let bgClass = '';
                if (ChatStatus || CallStatus) {
                    route = "{{ route('front.astrologerindex') }}";
                    bgClass = 'bg-pink';
                }

                // Append notification to the list
                notificationList.innerHTML += `
                    <li class="d-lg-block ${bgClass}" not-id="${notification.id}">
                        <div>
                            <a class="dropdown-item" href="${route}">
                                <span class="mr-2 accSet accSettingWeb">
                                    <i class="fa-solid fa-bell"></i>
                                </span>
                                <span>${notification.title}</span>
                            </a>
                        </div>
                    </li>
                `;
            });
        })
        .catch(error => console.error('Error fetching notifications:', error));
}, 4000);

    // Clear Notification
    $('#clearNotifications').click(function(e) {
        e.preventDefault();

        @php
            $token = $session->get('astrotoken');
        @endphp


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

    // Upload Srory
    $('#storyBtn').click(function(e) {
        e.preventDefault();

        @php
            $token = $session->get('astrotoken');
        @endphp

        var formData = new FormData($('#storyForm')[0]);

        if (formData.get('mediaType') == 'video') {
            formData.append('media', formData.get('videoMedia'));
        }

        $.ajax({
            url: "{{ route('api.addStory', ['token' => $token]) }}",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                toastr.success('Story Added Successfully');
                window.location.reload();
            },
            error: function(xhr, status, error) {
                toastr.error(xhr.responseText);
            }
        });
    });
</script>


    <script>
        function playSound(url) {
            const audio = new Audio(url);
            audio.play();
        }

        $(document).ready(function() {
            $('.astrostory').on('click', function() {
                var astrologerId = "{{ $astrologerId }}";
                var astrologerName = "{{ $user->name }}";
                var astrologerProfile = "/{{ $user->profile }}";
                // console.log(astrologerProfile);

                if (!astrologerProfile) {
                    astrologerProfile =
                        'public/frontend/onlinepujacdn/dashaspeaks/web/content/images/user-img-new.png';
                }
                // Make an AJAX request to get the stories
                $.ajax({
                    url: '/astrologer/' + astrologerId + '/stories',
                    method: 'GET',
                    success: function(response) {
                        openStoryModal(response, astrologerName, astrologerProfile);
                    },
                    error: function(error) {
                        console.error('Error fetching stories:', error);
                    }
                });
            });
        });

        function openStoryModal(stories, name, profileImage) {
            var modal = $('#storyModal');
            var astrologerProfileImage = $('#astrologerProfileImage');
            var astrologerName = $('#astrologerName');
            var carouselIndicators = $('#carouselIndicators');
            var carouselInner = $('#carouselInner');
            var modalTitle = $('#astrologerName');

            // Clear existing slides and indicators
            carouselIndicators.empty();
            carouselInner.empty();

            // Add new slides and indicators
            stories.forEach((story, index) => {
                var indicator = $('<li>')
                    .attr('data-target', '#carouselExampleIndicators')
                    .attr('data-slide-to', index);
                if (index === 0) {
                    indicator.addClass('active');
                }
                carouselIndicators.append(indicator);

                var carouselItem = $('<div>')
                    .addClass('carousel-item position-relative');
                if (index === 0) {
                    carouselItem.addClass('active');
                }

                // Corrected media path handling
                var mediaPath = story.media

                if (story.mediaType === 'image') {
                    var img = $('<img>')
                        .addClass('d-block w-100')
                        .attr('src', '/' + mediaPath); // Only add / at beginning
                    carouselItem.append(img);
                } else if (story.mediaType === 'video') {
                    var video = $('<video>')
                        .addClass('d-block w-100')
                        .attr('controls', true);
                    var source = $('<source>')
                        .attr('src', '/' + mediaPath)
                        .attr('type', 'video/mp4');
                    video.append(source);
                    carouselItem.append(video);
                } else if (story.mediaType === 'text') {
                    var text = $('<div>')
                        .addClass('d-block w-100 text-center')
                        .css({
                            'padding': '20px',
                            'font-size': calculateFontSize(story.media)
                        })
                        .text(story.media);
                    carouselItem.append(text);
                }

                // Add delete icon
                var deleteIcon = $('<button>')
                    .addClass('btn btn-danger delete-story-btn position-absolute')
                    .css({
                        top: '10px',
                        right: '10px',
                        'z-index': 10
                    })
                    .attr('data-id', story.id)
                    .html('<i class="fas fa-trash"></i>');
                deleteIcon.on('click', function() {
                    deleteStory($(this).data('id'));
                });
                carouselItem.append(deleteIcon);

                carouselInner.append(carouselItem);
            });

            modalTitle.text(name);
            astrologerProfileImage.attr('src', profileImage);

            modal.modal('show');

            // Stop auto sliding
            $('.carousel').carousel('pause');


            function calculateFontSize(text) {
                var baseFontSize = 30;
                var maxLength = 200;
                var fontSize = baseFontSize;

                if (text.length > maxLength) {
                    fontSize = baseFontSize - ((text.length - maxLength) / 10);
                }

                return fontSize + 'px';
            }


            function trackStoryView(storyId) {
                $.ajax({
                    url: "{{ route('front.viewstory') }}",
                    method: 'POST',
                    data: {
                        storyId: storyId
                    },
                    success: function(response) {
                        console.log(response.message);
                    },
                    error: function(error) {
                        console.error('Error viewing story:', error);
                    }
                });
            }
        }


        function deleteStory(storyId) {
            if (confirm('Are you sure you want to delete this story?')) {
                $.ajax({
                    url: "{{ route('api.deleteStory') }}", // Use your route here
                    method: 'POST',
                    data: {
                        StoryId: storyId
                    },
                    success: function(response) {
                        alert(response.message || 'Story deleted successfully.');
                        $('#storyModal').modal('hide'); // Close modal
                        location.reload(); // Reload the page to update the stories
                    },
                    error: function(error) {
                        console.error('Error deleting story:', error);
                        alert('Failed to delete the story. Please try again.');
                    }
                });
            }
        }
    </script>
