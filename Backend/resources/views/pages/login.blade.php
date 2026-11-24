@extends('../layout/' . $layout)

@section('head')
<title>Sign In - Online Puja Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<style>
    /* Mobile-first Layout & Anti-Shift Enforcements */
    html, body.login {
        overflow-x: hidden !important;
        width: 100% !important;
        min-height: 100vh !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #0f172a !important; /* Premium Slate/Vedic Dark Background */
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    /* Suppress blocking loader overlay */
    .loader {
        display: none !important;
    }

    /* Prevent Left4code template min-width: 350px horizontal overflow */
    .login .login__input {
        min-width: 0 !important;
        width: 100% !important;
        box-sizing: border-box !important;
        background-color: #f8fafc !important;
        border: 1.5px solid #cbd5e1 !important;
        border-radius: 0.5rem !important;
        color: #0f172a !important;
        font-size: 15px !important;
        transition: all 0.2s ease !important;
    }

    .login .login__input:focus {
        border-color: #0d9488 !important;
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.25) !important;
        background-color: #ffffff !important;
        outline: none !important;
    }

    /* Stop mobile horizontal shift from template translate-x */
    @media (max-width: 1023px) {
        .intro-x, .-intro-x {
            transform: none !important;
            opacity: 1 !important;
        }

        .login-wrapper {
            width: 100% !important;
            max-width: 420px !important;
            margin: 1.5rem auto !important;
            padding: 0 1rem !important;
            box-sizing: border-box !important;
        }

        .login-card {
            width: 100% !important;
            background: #ffffff !important;
            border-radius: 1.25rem !important;
            padding: 2rem 1.5rem !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
            box-sizing: border-box !important;
        }
    }

    @media (min-width: 1024px) {
        .login-wrapper {
            width: 100% !important;
            max-width: 1050px !important;
            margin: 0 auto !important;
            padding: 2rem !important;
        }

        .login-card {
            background: #ffffff !important;
            border-radius: 1.25rem !important;
            padding: 2.75rem 2.25rem !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
            width: 440px !important;
            margin-left: auto !important;
        }
    }

    .btn-submit-action {
        background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%) !important;
        color: #ffffff !important;
        border: none !important;
        font-weight: 600 !important;
        letter-spacing: 0.025em !important;
        border-radius: 0.5rem !important;
        transition: all 0.2s ease !important;
        box-shadow: 0 4px 6px -1px rgba(13, 148, 136, 0.35) !important;
    }

    .btn-submit-action:hover:not(:disabled) {
        opacity: 0.95 !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 10px 15px -3px rgba(13, 148, 136, 0.45) !important;
    }

    .btn-submit-action:disabled {
        opacity: 0.65 !important;
        cursor: not-allowed !important;
    }

    .error-alert-box {
        background-color: #fef2f2 !important;
        border: 1px solid #fecaca !important;
        border-radius: 0.5rem !important;
        padding: 0.75rem 1rem !important;
        color: #991b1b !important;
        font-size: 13.5px !important;
    }

    .field-error {
        color: #dc2626 !important;
        font-size: 12px !important;
        margin-top: 4px !important;
        font-weight: 500 !important;
    }
</style>
@endsection

@section('content')
@php
    $logo = DB::table('systemflag')
        ->where('name', 'AdminLogo')
        ->select('value')
        ->first();
    $appName = DB::table('systemflag')
        ->where('name', 'AppName')
        ->select('value')
        ->first();
    $logoSrc = (!empty($logo) && !empty($logo->value)) ? '/' . ltrim($logo->value, '/') : '/build/assets/images/logo.svg';
    $nameText = (!empty($appName) && !empty($appName->value)) ? $appName->value : 'Online Puja';
@endphp

<div class="login-wrapper w-full">
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 items-center">
        <!-- Left Hero (Desktop Only) -->
        <div class="hidden xl:flex flex-col justify-center text-white px-8">
            <img alt="{{ $nameText }}" class="w-32 h-32 object-contain rounded-2xl shadow-xl bg-white/10 p-2 border border-white/20 mb-6" src="{{ $logoSrc }}">
            <h1 class="font-bold text-4xl leading-tight text-white">{{ $nameText }}</h1>
            <p class="mt-3 text-lg text-slate-300 font-normal">Authentic Vedic Puja, Astrology Consultation & Sanatan Services Management Portal</p>
            <div class="mt-6 flex items-center space-x-3 text-sm text-slate-400">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    Growth OS v2.0
                </span>
                <span>• Live Production System</span>
            </div>
        </div>

        <!-- Right Login Card -->
        <div class="w-full flex justify-center">
            <div class="login-card w-full">
                <!-- Mobile Logo & Header -->
                <div class="text-center xl:text-left mb-6">
                    <img alt="{{ $nameText }}" class="xl:hidden w-24 h-24 object-contain mx-auto mb-4 rounded-xl shadow-md bg-slate-50 p-1" src="{{ $logoSrc }}">
                    <h2 class="font-bold text-2xl xl:text-3xl text-slate-900 tracking-tight">Sign In</h2>
                    <p class="text-slate-500 text-sm mt-1">Enter your administrative credentials to continue</p>
                </div>

                <!-- Form Section -->
                <form id="admin-login-form" method="POST" action="javascript:void(0);" novalidate>
                    @csrf
                    
                    <!-- Alert Box for Errors -->
                    <div class="error-alert-box mb-4" id="general-error-box" style="display:none;">
                        <ul id="error-list" class="list-disc pl-5 m-0 space-y-1"></ul>
                    </div>

                    <div class="mb-4">
                        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">Email Address</label>
                        <input id="email" type="email" class="login__input form-control py-3 px-4 block"
                            placeholder="admin@onlinepuja.live" name="email" autocomplete="email" required>
                        <div class="field-error print-email-error-msg" style="display:none"></div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">Password</label>
                        <input id="password" type="password" name="password"
                            class="login__input form-control py-3 px-4 block" placeholder="••••••••" autocomplete="current-password" required>
                        <div class="field-error print-password-error-msg" style="display:none"></div>
                    </div>

                    <button id="login-btn" type="submit"
                        class="mt-6 btn btn-submit-action py-3 px-4 w-full flex items-center justify-center text-base cursor-pointer">
                        <span id="btn-text">Sign In</span>
                        <span id="btn-spinner" style="display:none;" class="ml-2">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                        </span>
                    </button>
                </form>

                <div class="mt-6 text-center text-xs text-slate-400">
                    Protected by Online Puja Security & TLS 1.3
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Forgot Password (Preserved for compatibility) -->
<div id="superlarge-modal-size-preview" class="modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="col-span-12 lg:col-span-8 2xl:col-span-9">
                <div class="intro-y box lg:mt-5">
                    <div class="flex items-center p-5 border-b border-slate-200/60 dark:border-darkmode-400">
                        <h2 class="font-medium text-base mr-auto">Forgot Password</h2>
                    </div>
                    <div class="p-5">
                        <form method="POST" enctype="multipart/form-data" id="sendmailform">
                            @csrf
                            <div>
                                <label for="resendemail" class="form-label">Email</label>
                                <input class="form-control" id="resendemail" name="resendemail" type="email" placeholder="Input text">
                                <div class="text-danger print-resendmail-error-msg mb-2" style="display:none">
                                    <ul></ul>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-4">Send Mail</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    (function() {
        // Hide loader immediately
        if (typeof jQuery !== 'undefined') {
            jQuery('.loader').hide();
        } else {
            var l = document.querySelector('.loader');
            if (l) l.style.display = 'none';
        }

        function initLogin() {
            var $ = window.jQuery;
            if (!$) {
                setTimeout(initLogin, 50);
                return;
            }

            $('.loader').hide();

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $('#admin-login-form').on('submit', function(e) {
                e.preventDefault();

                var email = $('#email').val() ? $('#email').val().trim().toLowerCase() : '';
                var password = $('#password').val() ? $('#password').val().trim() : '';

                if (!email || !password) {
                    showError(['Please enter both your email address and password.']);
                    return;
                }

                // UI loading state
                $('#login-btn').prop('disabled', true);
                $('#btn-text').text('Signing In...');
                $('#btn-spinner').show();
                hideErrors();

                $.ajax({
                    type: 'POST',
                    url: "{{ route('loginApi') }}",
                    timeout: 15000,
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        email: email,
                        password: password
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data && data.first) {
                            $('#btn-text').text('Authenticated! Redirecting...');
                            setTimeout(function() {
                                window.location.href = data.first;
                            }, 150);
                        } else if (data && data.error) {
                            showError(data.error);
                            resetButton();
                        } else {
                            setTimeout(function() {
                                window.location.href = '/admin/dashboard';
                            }, 150);
                        }
                    },
                    error: function(xhr) {
                        resetButton();
                        if (xhr.status === 419) {
                            showError(['Your session expired. Please refresh the page and try logging in again.']);
                        } else if (xhr.responseJSON && xhr.responseJSON.error) {
                            showError(xhr.responseJSON.error);
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            showError([xhr.responseJSON.message]);
                        } else {
                            showError(['Login request failed. Please check your credentials and internet connection.']);
                        }
                    }
                });
            });

            function resetButton() {
                $('#login-btn').prop('disabled', false);
                $('#btn-text').text('Sign In');
                $('#btn-spinner').hide();
            }

            function hideErrors() {
                $('#general-error-box').hide();
                $('#error-list').empty();
                $('.print-email-error-msg').hide().empty();
                $('.print-password-error-msg').hide().empty();
            }

            function showError(errors) {
                hideErrors();
                var list = $('#error-list');
                var hasGeneral = false;

                if (typeof errors === 'string') {
                    list.append('<li>' + errors + '</li>');
                    hasGeneral = true;
                } else if (Array.isArray(errors)) {
                    errors.forEach(function(msg) {
                        list.append('<li>' + msg + '</li>');
                        hasGeneral = true;
                    });
                } else if (typeof errors === 'object') {
                    $.each(errors, function(key, val) {
                        var message = Array.isArray(val) ? val.join(' ') : val;
                        if (key === 'email') {
                            $('.print-email-error-msg').text(message).show();
                        } else if (key === 'password') {
                            $('.print-password-error-msg').text(message).show();
                        } else {
                            list.append('<li>' + message + '</li>');
                            hasGeneral = true;
                        }
                    });
                }

                if (hasGeneral) {
                    $('#general-error-box').show();
                }
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initLogin);
        } else {
            initLogin();
        }
    })();
</script>
@endsection
