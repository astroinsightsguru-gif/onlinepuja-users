<style>
    /* ─── Light Mode Footer (Default Theme) ─── */
    #footer {
        background: linear-gradient(180deg, #fffbeb 0%, #fef3c7 100%);
        border-top: 1px solid rgba(245, 158, 11, 0.18);
        color: #4b5563;
        transition: background 0.3s ease, border-color 0.3s ease, color 0.3s ease;
    }

    .text-gray {
        color: #4b5563 !important;
        transition: color 0.3s ease;
    }

    .text-gray-dark {
        color: #6b7280 !important;
        transition: color 0.3s ease;
    }

    .footer-col-title {
        color: #1f2937;
        font-family: var(--primary-font);
        font-size: 14px;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        padding-bottom: 10px;
        margin-bottom: 18px;
        border-bottom: 2px solid #fbbf24;
        transition: color 0.3s ease, border-color 0.3s ease;
    }

    .footer-link {
        font-family: var(--primary-font);
        font-size: 13.5px;
        color: #4b5563;
        text-decoration: none;
        transition: all 0.25s ease;
        display: inline-block;
        padding: 3px 0;
    }

    .footer-link:hover {
        color: #b45309 !important;
        text-decoration: none !important;
        transform: translateX(4px);
    }

    .social-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(0, 0, 0, 0.08);
        margin-right: 8px;
        margin-top: 4px;
        transition: all 0.3s ease;
    }

    .social-icon:hover {
        background: rgba(245, 158, 11, 0.12);
        border-color: rgba(245, 158, 11, 0.35);
        transform: translateY(-3px);
        text-decoration: none;
    }

    .social-icon img {
        opacity: 0.85;
        transition: all 0.3s ease;
        width: 24px;
        height: 24px;
        object-fit: contain;
    }

    .social-icon:hover img {
        opacity: 1;
    }

    .footer-app-btn {
        transition: transform 0.2s ease, opacity 0.2s ease;
    }

    .footer-app-btn:hover {
        transform: translateY(-2px);
        opacity: 0.95;
    }

    .footer-item {
        display: inline-block;
    }

    .footer-links-list {
        display: inline-flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 5px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .footer-copyright-bar {
        background-color: #fef08a;
        border-top: 1px solid rgba(0, 0, 0, 0.06);
        transition: background-color 0.3s ease, border-top-color 0.3s ease;
    }

    @media (max-width: 767px) {
        .footer-links-list {
            display: flex !important;
            flex-wrap: wrap !important;
            justify-content: center !important;
            gap: 6px 14px !important;
            padding: 8px 0 0 0 !important;
            list-style: none !important;
            width: 100% !important;
        }

        .footer-item {
            display: inline-block !important;
        }

        .footer-links-list span {
            display: none !important;
        }
    }

    /* ─── Dark Mode Footer (Day/Night Theme Sync) ─── */
    body.home-dark-mode #footer {
        background: linear-gradient(180deg, #181512 0%, #0e0d0b 100%) !important;
        border-top-color: rgba(251, 191, 36, 0.08);
        color: #9ca3af;
    }

    body.home-dark-mode .text-gray {
        color: #d6d3d1 !important;
    }

    body.home-dark-mode .text-gray-dark {
        color: #78716c !important;
    }

    body.home-dark-mode .footer-col-title {
        color: #ffffff;
        border-bottom-color: rgba(251, 191, 36, 0.25);
    }

    body.home-dark-mode .footer-link {
        color: #9ca3af;
    }

    body.home-dark-mode .footer-link:hover {
        color: #fcd34d !important;
    }

    body.home-dark-mode .social-icon {
        background: rgba(255, 255, 255, 0.03);
        border-color: rgba(255, 255, 255, 0.08);
    }

    body.home-dark-mode .social-icon:hover {
        background: rgba(251, 191, 36, 0.1);
        border-color: rgba(251, 191, 36, 0.4);
    }

    body.home-dark-mode .social-icon:hover img {
        opacity: 1;
    }

    body.home-dark-mode .footer-copyright-bar {
        background-color: #0c0a08 !important;
        border-top-color: rgba(255, 255, 255, 0.05);
    }
</style>
@php
$getAstrologerCategory = $astrologerCategories;
$facebook = $systemFlags->get('Facebook');
$apple = $systemFlags->get('Apple');
$website = $systemFlags->get('Website');
$youtube = $systemFlags->get('Youtube');
$linkedIn = $systemFlags->get('LinkedIn');
$pintrest = $systemFlags->get('Pintrest');
$instagram = $systemFlags->get('Instagram');
$whatsapp = $systemFlags->get('Whatsapp');
$telegram = $systemFlags->get('Telegram');
$twitter = $systemFlags->get('Twitter');
$playstore = $systemFlags->get('PlayStore');
$appstore = $systemFlags->get('AppStore');
$aiAstrologer = $systemFlags->get('AiAstrologer');
@endphp
<!-- FOOTER START -->
<div id="footer" style="overflow: hidden;">
    <section class="pt-5 pb-4">
        <div class="container">
            <div class="row text-md-left g-4">
                <!-- MENU Column -->
                <div class="col-md-3 col-6 mb-4">
                    <h5 class="footer-col-title">MENU</h5>
                    <ul class="list-unstyled" style="font-size: 14px">
                        <li class="p-1"><a class="footer-link" href="{{ route('front.getkundali') }}">Kundli</a></li>
                        <li class="p-1"><a class="footer-link" href="{{ route('front.kundaliMatch') }}">Kundli Matching</a></li>
                        <li class="p-1"><a class="footer-link" href="{{route('front.getproducts')}}">Products</a></li>
                        <li class="p-1"><a class="footer-link" href="{{ route('front.horoScope') }}">Horoscope</a></li>
                        <li class="p-1"><a class="footer-link" href="{{route('front.getPanchang')}}">Today's Panchang</a></li>
                    </ul>
                </div>

                <!-- LINKS Column -->
                <div class="col-md-3 col-6 mb-4">
                    <h5 class="footer-col-title">LINKS</h5>
                    <ul class="list-unstyled" style="font-size: 14px">
                        <li class="p-1"><a class="footer-link" href="{{route('front.getBlog')}}">Go to Blog</a></li>
                        <li class="p-1"><a class="footer-link" href="{{route('front.contact')}}">Contact Us</a></li>
                    </ul>

                    @if(!authcheck())
                    <h5 class="footer-col-title mt-4">{{ucfirst($professionTitle)}} Section</h5>
                    <ul class="list-unstyled mt-1" style="font-size: 14px">
                        <li class="p-1"><a class="footer-link" href="{{ route('front.astrologerlogin') }}">{{ucfirst($professionTitle)}} Login</a></li>
                        <li class="p-1"><a class="footer-link" href="{{ route('front.astrologerregister') }}">{{ucfirst($professionTitle)}} Registration</a></li>
                    </ul>
                    @endif
                </div>

                <!-- FEATURES Column -->
                <div class="col-md-3 col-6 mb-4">
                    <h5 class="footer-col-title">GET ADVICE ON</h5>
                    <ul class="list-unstyled" style="font-size: 14px">
                        @foreach($getAstrologerCategory as $category)
                        <li class="p-1"><a class="footer-link" href="{{route('front.chatList',['astrologerCategoryId'=>$category->id])}}">{{$category->name}}</a></li>
                        @endforeach
                    </ul>
                </div>

                <!-- ABOUT Column -->
                <div class="col-md-3 col-6 mb-4 text-md-left">
                    <h5 class="footer-col-title">Download Our Apps</h5>
                    <a href="{{$playstore->value}}" class="d-inline-block mt-2 footer-app-btn">
                        <img src="{{asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/images/google-play.png')}}" alt="google-play" class="img-fluid" width="160" height="48" loading="lazy">
                    </a>
                    <a href="{{$appstore->value}}" class="d-inline-block mt-3 footer-app-btn">
                        <img src="{{asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/images/app-store.png')}}" alt="app-store" class="img-fluid" width="160" height="48" loading="lazy">
                    </a>

                    <div class="mt-4 f-icon justify-content-md-start">
                        @if(!empty($facebook->value))
                        <a class="social-icon" target="_blank" href="{{$facebook->value}}" rel="nofollow">
                            <img src="{{asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/images/fb.svg')}}" alt="facebook" width="24" height="24" loading="lazy">
                        </a>
                        @endif
                        @if(!empty($twitter->value))
                        <a class="social-icon" target="_blank" href="{{$twitter->value}}" rel="nofollow">
                            <img src="{{asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/images/twitter.svg')}}" alt="twitter" width="24" height="24" loading="lazy">
                        </a>
                        @endif
                        @if(!empty($linkedIn->value))
                        <a class="social-icon" target="_blank" href="{{$linkedIn->value}}" rel="nofollow">
                            <img src="{{asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/images/linkedin.svg')}}" alt="linkedin" width="24" height="24" loading="lazy">
                        </a>
                        @endif
                        @if(!empty($instagram->value))
                        <a class="social-icon" target="_blank" href="{{$instagram->value}}" rel="nofollow">
                            <img src="{{asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/images/insta.svg')}}" alt="instagram" width="24" height="24" loading="lazy">
                        </a>
                        @endif
                        @if(!empty($youtube->value))
                        <a class="social-icon" target="_blank" href="{{$youtube->value}}" rel="nofollow">
                            <img src="{{asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/images/youtube.svg')}}" alt="youtube" width="24" height="24" loading="lazy">
                        </a>
                        @endif
                        @if(!empty($pintrest->value))
                        <a class="social-icon" target="_blank" href="{{$pintrest->value}}" rel="nofollow">
                            <img src="{{asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/images/pinterest.svg')}}" alt="pinterest" width="24" height="24" loading="lazy">
                        </a>
                        @endif
                        @if(!empty($whatsapp->value))
                        <a class="social-icon" target="_blank" href="{{$whatsapp->value}}" rel="nofollow">
                            <img src="{{asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/images/whatsapp.svg')}}" alt="whatsapp" width="24" height="24" loading="lazy">
                        </a>
                        @endif
                        @if(!empty($telegram->value))
                        <a class="social-icon" target="_blank" href="{{$telegram->value}}" rel="nofollow">
                            <img src="{{asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/onlinepuja/images/telegram.svg')}}" alt="telegram" width="24" height="24" loading="lazy">
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if(!empty($aiAstrologer->value))
    <div id="sf_chat_button1" role="button" class="sf_chat_button1">
        <button data-bs-toggle="tooltip" title="Chat with master Astrologer" data-bs-placement="top" class="rounded-circle border-0 bg-transparent">
            <a class="shadow-md d-inline checkBalance" id="checkBalance">
                <img src="{{ asset($masterAstrologer->image) }}" width="50" height="53" alt="">
            </a>
        </button>
    </div>
    @endif

    <div class="footer-copyright-bar text-center py-3 d-flex flex-wrap justify-content-center align-items-center gap-3" style="padding-left: 20px; padding-right: 20px;">
        <div class="d-flex flex-wrap justify-content-center align-items-center">
            <small class="text-gray-dark">
                Copyright © 2020-{{ date('Y') }}
                {{ ucfirst($appname) }}. All Rights Reserved
            </small>

            <span class="d-none d-md-inline text-muted mx-2">|</span>

            <ul class="footer-links-list">
                @foreach($footerPages as $page)
                <li class="footer-item">
                    <a class="text-gray-dark footer-link" href="{{ $page->type ? url($page->type) : '#' }}">
                        {{ $page->title }}
                    </a>
                    @if(!$loop->last)
                    <span class="d-none d-md-inline text-muted mx-1">|</span>
                    @endif
                </li>
                @endforeach
            </ul>
        </div>

        <!-- 🕉️ Footer Language Selection -->
        <div class="footer-lang-container notranslate my-2 my-md-0 mx-md-3">
            <div class="footer-lang-row">
                <span class="text-muted font-12"><i class="fa-solid fa-globe text-warning"></i> भाषा:</span>
                <button type="button" class="footer-lang-btn active" data-lang="en" onclick="window.switchLanguage('en')">English</button>
                <span class="text-muted font-12">|</span>
                <button type="button" class="footer-lang-btn" data-lang="hi" onclick="window.switchLanguage('hi')">🇮🇳 हिन्दी</button>
            </div>
        </div>

        <div class="theme-toggle-container ml-md-auto mt-2 mt-md-0">
            <div class="theme-toggle-capsule">
                <button class="theme-toggle-btn" data-mode="auto">
                    <span class="icon">🔄</span> Auto
                </button>
                <button class="theme-toggle-btn" data-mode="light">
                    <span class="icon">☀️</span> Day
                </button>
                <button class="theme-toggle-btn" data-mode="dark">
                    <span class="icon">🌙</span> Night
                </button>
            </div>
        </div>

    </div>
</div>
<!-- FOOTER END -->

<script>
    $('.checkBalance').on('click', function(e) {
        e.preventDefault();

        var isProfileComplete = @json($isProfileComplete);

        if (!isProfileComplete) {
            // Profile incomplete, show SweetAlert
            Swal.fire({
                title: 'Profile Incomplete',
                text: 'Your profile is incomplete. Please provide your Date of Birth and Place of Birth.',
                icon: 'warning',
                confirmButtonText: 'Update Profile',
                showCancelButton: true,
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirect to profile update page
                    window.location.href = "{{route('front.getMyAccount')}}"; // Adjust to your profile update route
                }
            });

        } else {

            $.ajax({
                url: '{{ route("check.user.balance") }}',
                method: 'GET',
                success: function(response) {

                    localStorage.removeItem('masterSubmitting');
                    localStorage.removeItem('refreshRedirectMaster');
                    localStorage.removeItem('timer');
                    localStorage.removeItem('balance');
                    localStorage.removeItem('reloadAftSubmit');

                    if (response.status === 'success') {

                        console.log(response.balance)
                        if (response.balance !== null) {
                            Swal.fire({
                                icon: 'question',
                                title: 'Confirm Action',
                                text: response.message,
                                showCancelButton: true,
                                confirmButtonText: 'OK',
                                cancelButtonText: 'Cancel'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'Hold on!',
                                        text: 'Please do not refresh the page.',
                                        showCancelButton: true,
                                        confirmButtonText: 'OK',
                                        cancelButtonText: 'Cancel'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            window.location.href = "{{ route('master.chat.page') }}";
                                        }
                                    });
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Hold on!',
                                text: 'Please do not refresh the page.',
                                showCancelButton: true,
                                confirmButtonText: 'OK',
                                cancelButtonText: 'Cancel'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = "{{ route('master.chat.page') }}";
                                }
                            });
                        }
                    } else if (response.status === 'warning') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Warning',
                            text: response.message
                        });
                    } else if (response.status === 'error') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Access Denied',
                            text: response.message,
                            confirmButtonText: 'Log In',
                            showCancelButton: true,
                            cancelButtonText: 'Cancel'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $('#loginSignUp').modal('show');
                            }
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Something went wrong',
                        text: 'Please try again later.'
                    });
                }
            });
        }
    });
</script>
<script>
    window.onload = function() {
        // if (localStorage.getItem('removeRemainingTime')) {
        localStorage.removeItem('remainingTime'); // Remove the item
        localStorage.removeItem('removeRemainingTime'); // Clear the flag
        localStorage.removeItem('refreshRedirect'); // Clear the flag
        // Other initialization code...
    };
</script>

<script>
    // Theme Mode Controller (Global & Homepage Sync)
    (function() {
        var savedMode = localStorage.getItem('homepage-theme-mode') || 'auto';

        function applyTheme(mode) {
            var isDark = false;
            if (mode === 'dark') {
                isDark = true;
            } else if (mode === 'light') {
                isDark = false;
            } else {
                isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            }

            if (isDark) {
                document.body.classList.add('home-dark-mode');
            } else {
                document.body.classList.remove('home-dark-mode');
            }

            // Update UI buttons if present
            var buttons = document.querySelectorAll('.theme-toggle-btn');
            buttons.forEach(function(btn) {
                if (btn.getAttribute('data-mode') === mode) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        }

        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.theme-toggle-btn');
            if (btn) {
                var mode = btn.getAttribute('data-mode');
                localStorage.setItem('homepage-theme-mode', mode);
                applyTheme(mode);
            }
        });

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function() {
            var currentMode = localStorage.getItem('homepage-theme-mode') || 'auto';
            if (currentMode === 'auto') {
                applyTheme('auto');
            }
        });

        applyTheme(savedMode);
    })();
</script>
