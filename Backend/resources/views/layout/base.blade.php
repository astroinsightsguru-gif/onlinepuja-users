<!DOCTYPE html>
<!--
Template Name: Enigma - HTML Admin Dashboard Template
Author: Left4code
Website: http://www.left4code.com/
Contact: muhammadrizki@left4code.com
Purchase: https://themeforest.net/user/left4code/portfolio
Renew Support: https://themeforest.net/user/left4code/portfolio
License: You must have a valid license purchased only from themeforest(the above link) in order to legally use the theme for your project.
-->
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ 'default' != 'default' ? ' ' . 'default' : '' }}">
<!-- BEGIN: Head -->
@php
    $logo = DB::table('systemflag')
        ->where('name', 'AdminLogo')
        ->select('value')
        ->first();
    $appName = DB::table('systemflag')
        ->where('name', 'AppName')
        ->select('value')
        ->first();
@endphp

<head>
    <title>{{$appName->value}}</title>
    <meta charset="utf-8">
    <link as="image" fetchpriority="high" href="/{{ $logo->value }}" rel="preload shortcut icon">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" defer/>
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" defer>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" defer>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description"
        content="{{ucfirst($appname)}} Admin Panel">
    <meta name="keywords"
        content="{{ucfirst($appname)}} Admin Panel">
    <meta name="author" content="{{ucfirst($appname)}}">
    <script src="https://www.gstatic.com/firebasejs/7.9.1/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/7.9.1/firebase-messaging.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.2/tinymce.min.js"></script>
    <script src="{{ asset('build/assets/jquery.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <style>
        /* Modern Editor Styles */
        .tox-tinymce {
            border-radius: 8px !important;
            border: 1px solid #e2e8f0 !important;
        }
        
        .disabled {
            pointer-events: none
        }

        .edit-modal {
            z-index: 1000 !important;
        }

        p.leading-5 {
            display: none !important;
        }

        .pagination li a:active {
            background-color: red
        }

        .sticky-top {
            position: sticky;
            top: 0;
            z-index: 1020;
            background: #fff
        }

        .list-table {
            height: calc(100vh - 282px);
            overflow-y: auto !important;
            margin-top: 10px;
            margin-bottom: 10px
        }

        .setting-page {
            height: calc(100vh - 251px);
            overflow-y: auto !important;
            margin-top: 10px;
            margin-bottom: 10px
        }

        .grid-table {
            height: calc(100vh - 292px);
            overflow-y: auto !important;
            margin-top: 10px;
            margin-bottom: 10px
        }

        .grid-table-without-search {
            height: calc(100vh - 238px);
            overflow-y: auto !important;
            margin-top: 10px;
            margin-bottom: 10px
        }

        .daily {
            height: calc(100vh - 340px);
            overflow-y: auto !important;
            margin-top: 10px;
            margin-bottom: 10px
        }

        .withoutsearch {
            height: calc(100vh - 227px);
            overflow-y: auto !important;
            margin-top: 10px;
            margin-bottom: 10px
        }

        .d-inline {
            display: inline-block
        }

        .addbtn {
            float: right
        }

        .horobtn:after {
            margin-right: 5px;
            content: "";
            background-color: #000;
            position: absolute;
            width: 2px;
            height: 45px;
            display: revert;
            left: 160px;
            margin-left: 5px;
        }

        .horo-insight:after {
            left: 207px
        }

        .horo:after {
            left: 127px
        }

        .astrologer-tab-content {
            height: calc(100vh - 440px);
            overflow-y: auto;
            overflow-x: hidden
        }

        .pagecount {
            margin-top: 10px
        }

        .text-red {
            color: red
        }

        .text-green {
            color: #78b144
        }

        .category {
            font-size: 20px;
        }

        @keyframes animName {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .loader {

            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            background: rgba(48, 48, 48, 0.75) url(/{{ $logo->value }}) no-repeat center center;
            z-index: 10000;
            background-size: 100px;
        }

        .changeorder {
            border: 1px solid #ddd;
            text-align: center;
            padding: 5px;
            border-radius: 5px;

        }

        input[type='file'] {
            color: rgba(0, 0, 0, 0)
        }

        .mastertab {
            height: calc(100vh - 420px);
            overflow: auto;
            overflow-x: hidden
        }

        .fitbox {
            height: fit-content
        }

        .setting {
            height: calc(100vh - 260px);
            overflow: auto;
            overflow-x: hidden
        }

        .mail {
            margin: auto;
            width: 600px;
        }

        @media(max-width: 695px) {

            .dailyaddbtn {
                float: left;
            }

            .dailytitle {
                margin-top: 0px !important;
            }
        }

        @media(max-width: 920px) {
            .horobtn:after {
                display: none
            }

            .dailytitle {
                margin-top: 10px !important;
            }

            .dailyhorobtn {
                float: left;
            }
        }

        @media(max-width: 830px) {
            .horedit {
                margin-top: 20px
            }
        }

        @media(max-width: 640px) {
            .horosign {
                margin-top: 0px !important
            }
        }

        .nav-link-tabs {
            overflow-x: auto
        }

        .settingimg {
            height: 150px;
            text-align: -webkit-center;
            width: 100%;
        }

        .settingimg img {
            height: 100%;
            /* width: 100%; */
            object-fit: cover
        }

        th,
        td {
            font-size: .875rem
        }

        .select2-container {
            width: 100% !important
        }

        .editastrologertab {
            height: calc(100vh - 280px);
            overflow: auto;
            overflow-x: hidden
        }

        .systooltip {
            position: relative;
            display: inline-block;
        }

        .systooltip .tooltiptext {
            visibility: hidden;
            /* width: 120px; */
            background-color: black;
            color: #fff;
            /* text-align: center; */
            border-radius: 6px;
            padding: 5px 5px;
            font-size: 12px;
            /* Position the tooltip */
            position: initial;
            z-index: 1;
            top: -5px;
            left: 105%;
        }


        .systooltip:hover .tooltiptext {
            visibility: visible;
        }

        .cke_notification.cke_notification_warning{
            display: none;
        }

        .pac-container {
    z-index: 10000 !important;
    }

    .pac-container:after {
        content: none !important;
    }
    @media (min-width: 640px) {
    .modal .modal-dialog {
        width: 60rem !important;
    }
}
    </style>
    <script></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    @yield('head')

    <!-- BEGIN: CSS Assets-->
    @vite('resources/css/app.css')
    <!-- 🕉️ Universal Vedic Design System -->
    <link rel="stylesheet" href="{{ asset('public/frontend/css/onlinepuja-design-system.css?v=1.0.0') }}">
    <!-- END: CSS Assets-->
</head>
   {{-- Start Add Wallet Modal --}}



@yield('body')

<script>
    $(window).on('load', function() {
        $('#loading').hide();
    })

$(document).on('change', '.toggle-class', function () {
    var status = $(this).prop('checked') ? 1 : 0;
    var section = $(this).data('section');
    var astroId = $(this).data('id');

    $.ajax({
        type: "POST",
        url: "{{ route('updateSections') }}",
        data: {
            status: status,
            section: section,
            astro_id: astroId,
            _token: "{{ csrf_token() }}"
        },
        success: function (data) {
            toastr.success(data.message);
        },
        error: function () {
            toastr.error('Failed to update status');
        }
    });
});
</script>
@if(session('error'))
<script>
toastr.error("{{session('error')}}")
</script>
@endif
@if(session('success'))
<script>
toastr.success("{{session('success')}}")
</script>
@endif
<script>
    function getTinyMCEConfig(selector) {
        return {
            selector: selector,
            height: 350,
            menubar: true,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | ' +
                'bold italic backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | image link media | help',
            image_title: true,
            automatic_uploads: true,
            file_picker_types: 'image',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
            // Allow image link insertion via URL field in image dialog
            image_description: true,
            image_advtab: true,
            promotion: false,
            branding: false
        };
    }

    async function initializeEditor(selector, data = '') {
        const element = document.querySelector(selector);
        if (!element) return;

        // Clean up previous instance if any
        tinymce.remove(selector);

        try {
            const config = getTinyMCEConfig(selector);
            if (data) {
                config.setup = function(editor) {
                    editor.on('init', function() {
                        editor.setContent(data);
                    });
                };
            }
            await tinymce.init(config);
            return tinymce.get(selector.replace('#', ''));
        } catch (error) {
            console.error('TinyMCE initialization failed:', error);
        }
    }

    // Export to window
    window.initializeEditor = initializeEditor;
    window.getEditorData = (selector) => {
        const editor = tinymce.get(selector.replace('#', ''));
        return editor ? editor.getContent() : '';
    };

    // CKEDITOR Shim for legacy compatibility
    window.CKEDITOR = {
        instances: {},
        replace: function(id) {
            const selector = '#' + id;
            initializeEditor(selector);
            this.instances[id] = {
                setData: function(data) {
                    const editor = tinymce.get(id);
                    if (editor && editor.initialized) {
                        editor.setContent(data || '');
                    } else {
                        // Queue the data for when the editor initializes
                        const applyContent = function(e) {
                            if (e.editor.id === id) {
                                e.editor.on('init', function() {
                                    e.editor.setContent(data || '');
                                });
                                // Remove listener after use
                                tinymce.off('addEditor', applyContent);
                            }
                        };
                        tinymce.on('addEditor', applyContent);
                        
                        // Fallback: check again in a bit in case it was already being added
                        setTimeout(() => {
                            const e = tinymce.get(id);
                            if (e && e.initialized) e.setContent(data || '');
                        }, 500);
                    }
                },
                getData: function() {
                    const editor = tinymce.get(id);
                    return editor ? editor.getContent() : '';
                },
                destroy: function() {
                    tinymce.remove('#' + id);
                }
            };
            return this.instances[id];
        }
    };

    // Automatic sync for standard form submissions
    document.addEventListener('submit', function(e) {
        if (typeof tinymce !== 'undefined') {
            tinymce.triggerSave();
        }
    }, true);

    document.addEventListener("DOMContentLoaded", function () {
        const editorElement = document.querySelector('#edescription');
        if (editorElement) {
            initializeEditor('#edescription');
        }
    });
</script>
</html>
