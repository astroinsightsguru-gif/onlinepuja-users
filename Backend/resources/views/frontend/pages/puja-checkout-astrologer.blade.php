@extends('frontend.layout.master')

<style>

    .error {
        color: red;
        font-size: 12px;
        display: none;
        /* Hide error message initially */
    }

    .input-field:invalid {
        border-color: red;
    }

    /* Day Theme (Default) */
    .psychic-card {
        background: #ffffff !important;
        border: 1px solid rgba(0, 0, 0, 0.08) !important;
        color: #495057 !important;
        transition: all 0.3s ease !important;
    }
    .btn-report {
        background: #495057 !important;
        color: #f1f1f2 !important;
        border: 2px solid #f1f1f2 !important;
        border-radius: 7px !important;
        font-size: 15px !important;
        font-weight: 600 !important;
        height: 33px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        transition: all 0.3s ease !important;
    }
    .btn-report:hover {
        background: #343a40 !important;
        color: #ffffff !important;
        border-color: #ffffff !important;
    }
    .btn-report i {
        color: #f1f1f2 !important;
    }
    .btn-report:hover i {
        color: #ffffff !important;
    }

    /* Night Theme (Dark Mode) */
    body.home-dark-mode .psychic-card {
        background: #1a1611 !important;
        border-color: rgba(255, 255, 255, 0.08) !important;
        color: #cbd5e1 !important;
    }
    body.home-dark-mode .psychic-card span.colorblack {
        color: #ffffff !important;
    }
    body.home-dark-mode .btn-report {
        background-color: #1c1917 !important;
        border: 2px solid #f59e0b !important;
        color: #f59e0b !important;
        border-radius: 7px !important;
        height: 33px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        transition: all 0.3s ease !important;
    }
    body.home-dark-mode .btn-report i {
        color: #f59e0b !important;
        fill: #f59e0b !important;
    }
    body.home-dark-mode .btn-report:hover {
        background-color: #f59e0b !important;
        border-color: #f59e0b !important;
        color: #111111 !important;
    }
    body.home-dark-mode .btn-report:hover i {
        color: #111111 !important;
        fill: #111111 !important;
    }
    body.home-dark-mode .expert-search-section {
        background-color: #120f0b !important;
    }
    body.home-dark-mode .expert-search-form h1 {
        color: #ffffff !important;
    }
    body.home-dark-mode .expert-search-form select {
        background-color: #1a1611 !important;
        color: #ffffff !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
    body.home-dark-mode .search-box input {
        background-color: #1a1611 !important;
        color: #ffffff !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

</style>
@php
    use Symfony\Component\HttpFoundation\Session\Session;
    $session = new Session();
    $token = $session->get('token');
    $countries = DB::table('countries')
    ->orderByRaw("CASE WHEN phonecode = 91 THEN 0 ELSE 1 END")
    ->get();
@endphp

@section('content')
    <div class="pt-1 pb-1 bg-red d-none d-md-block onlinepuja-breadcrumb">
        <div class="container">
            <div class="row afterLoginDisplay">
                <div class="col-md-12 d-flex align-items-center">

                    <span style="text-transform: capitalize; ">
                        <span class="text-white breadcrumbs">
                            <a href="{{ route('front.home') }}" style="color:white;text-decoration:none">
                                <i class="fa fa-home font-18"></i>
                            </a>
                            <i class="fa fa-chevron-right"></i> <span class="breadcrumbtext">Select {{ucfirst($professionTitle)}}</span>
                        </span>
                    </span>

                </div>
            </div>
        </div>
    </div>


    <div class="py-md-2 expert-search-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12" id="experts" style="overflow:hidden;">
                    <div id="expert-search" class="my-3 my-md-0">
                        <!--For Serach Component-->
                        <div class="expert-search-form">
                            <div class="row mx-auto px-2 px-md-0 flex-md-nowrap align-items-center round">
                                <div
                                    class="col-12 col-md-3 col-sm-auto text-left d-flex justify-content-between align-items-center w-100 px-0">
                                    <h1 class="font-22 font-weight-bold">Select {{ucfirst($professionTitle)}}</h1>
                                    {{-- <img src="#" alt="Filter Experts based on Status" width="18" height="18"
                            class="img-fluid filterIcon float-right d-md-none" onClick="fnSearch()" /> --}}
                                    <div class="searchIcon1">
                                        <img id="searchIcon" src="https://img.icons8.com/ios7/600/search.png"
                                            alt="Filter Experts based on Status" width="18" height="18"
                                            class="search-icon" onClick="toggleSearchBox()" />
                                        <img id="closeIcon" src="https://img.icons8.com/ios/600/delete-sign.png"
                                            alt="Close" width="18" height="18" class="close-icon d-none"
                                            onClick="toggleSearchBox()" />
                                    </div>
                                </div>
                                <div class="col-ms-12 col-md-3 d-none d-md-block mt-3" id="searchExpert">
                                    <form action="{{route('front.pujaAstrologerList',['slug'=>$puja->slug,'package_id'=>$puja->packages->id])}}" method="GET">
                                        <div class="search-box">
                                            <input value="{{ isset($searchTerm) ? $searchTerm : '' }}"
                                                class="form-control rounded" name="s" placeholder="Search {{ucfirst($professionTitle)}}"
                                                type="search" autocomplete="off">
                                            <button type="submit" class="btn  search-btn" id="search-button">
                                                <i class="fa fa-search mt-1"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-ms-12 col-md-2 d-none d-md-flex nowrap align-items-center pl-md-0 pt-2 pb-2 "
                                    id="sortExpert">
                                    <select class="form-control font13 rounded" name="sortBy" onchange="onSortExpertList()"
                                        id="psychicOrderBy">
                                        <option value="1" {{ $sortBy == '1' ? 'selected' : '' }}>Sort Filter</option>
                                        <option value="experienceLowToHigh"
                                            {{ $sortBy == 'experienceLowToHigh' ? 'selected' : '' }}>Low Experience</option>
                                        <option value="experienceHighToLow"
                                            {{ $sortBy == 'experienceHighToLow' ? 'selected' : '' }}>High Experience
                                        </option>
                                        <option value="priceLowToHigh" {{ $sortBy == 'priceLowToHigh' ? 'selected' : '' }}>
                                            Lowest Price</option>
                                        <option value="priceHighToLow" {{ $sortBy == 'priceHighToLow' ? 'selected' : '' }}>
                                            Highest Price</option>
                                    </select>

                                </div>

                                <div class="col-ms-12 col-md-2 d-none d-md-flex nowrap align-items-center pl-md-0 pt-2 pb-2"
                                    id="filterExpertCategory">
                                    <select name="astrologerCategoryId" onchange="onFilterExpertCategoryList()"
                                        class="form-control font13 rounded" id="psychicCategories">
                                        <option value="0" {{ $astrologerCategoryId == '0' ? 'selected' : '' }}>All
                                        </option>
                                        @foreach ($getAstrologerCategory['recordList'] as $category)
                                            <option value="{{ $category['id'] }}"
                                                {{ $astrologerCategoryId == $category['id'] ? 'selected' : '' }}>
                                                {{ $category['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-ms-12 col-md-2 d-none d-md-flex nowrap align-items-center pl-md-0 pt-2 pb-2"
                                id="clear">
                                <button type="button" id="clearButton" class="btn btn-secondary">
                                    <i class="fa-solid fa-xmark"></i> Clear
                                </button>
                                 </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <div class="container">
        <div class="row">
            <div class="col-lg-12 expert-search-section-height">
                <div id="expert-list" class="py-4 ">

                    @foreach ($getAstrologer as $astrologer)
                        <div id="ATAAIOfferTile" class="psychic-card overflow-hidden expertOnline ask-guruji"
                            data-astrologer-id="{{ $astrologer['id'] }}">
                            <ul class="list-unstyled d-flex mb-0">
                                <li class="mr-3 position-relative psychic-presence status-online" data-status="online"><a
                                        href="{{ route('front.astrologerDetails', ['slug' => $astrologer['slug'] ?? ($astrologer->slug ?? ($astrologer['id'] ?? 1))]) }}">
                                        <div class="psyich-img position-relative">
                                            @if ($astrologer['profileImage'])
                                            <img src="{{ Str::startsWith($astrologer['profileImage'] , ['http://','https://']) ? $astrologer['profileImage']  : '/' . $astrologer['profileImage']  }}" onerror="this.onerror=null;this.src='/build/assets/images/person.png';" alt="Customer image" onclick="openImage('{{ $astrologer['profileImage']  }}')" width="85" height="85" style="border-radius:50%;"/>


                                            @else
                                                <img src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/images/user-img-new.png') }}"
                                                    width="85" height="85" style="border-radius:50%;">
                                            @endif
                                        </div>
                                    </a>
                                    @if ($astrologer['chatStatus'] == 'Busy')
                                        <div class="status-badge specific-Clr-Busy" title="Online"></div>
                                    @elseif($astrologer['chatStatus']=='Offline' || empty($astrologer['chatStatus']))
                                    <div class="status-badge specific-Clr-Offline" title="Offline"></div>
                                    <div class="status-badge-txt text-center specific-Clr-Offline"><span
                                        id=""title="Online"
                                        class="status-badge-txt specific-Clr-Offline tooltipex">{{ $astrologer['callStatus'] ?? 'Offline'}}</span>
                                    </div>
                                    @else
                                        <div class="status-badge specific-Clr-Online" title="Online"></div>
                                        <div class="status-badge-txt text-center specific-Clr-Online"><span
                                                id=""title="Online"
                                                class="status-badge-txt specific-Clr-Online tooltipex">{{ $astrologer['chatStatus'] }}</span>
                                        </div>
                                    @endif

                                </li>


                                <li class=" w-100">
                                    <span class="colorblack font-weight-bold font16 mt-0 ml-0 mr-0 mb-0 p-0 text-capitalize d-block" data-toggle="tooltip" title="" style="font-weight: bold;">{{ $astrologer['name'] }}
                                    <svg id="Layer_1" fill="#495057" height="16" width="16" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 106.11 122.88"><defs><style>.cls-1{fill-rule:evenodd;}</style></defs><title>secure</title><path class="cls-1" d="M56.36,2.44A104.34,104.34,0,0,0,79.77,13.9a48.25,48.25,0,0,0,19.08,2.57l6.71-.61.33,6.74c1.23,24.79-2.77,46.33-11.16,63.32C86,103.6,72.58,116.37,55.35,122.85l-4.48,0c-16.84-6.15-30.16-18.57-39-36.47C3.62,69.58-.61,47.88.07,22l.18-6.65,6.61.34A64.65,64.65,0,0,0,28.23,13.5,60.59,60.59,0,0,0,48.92,2.79L52.51,0l3.85,2.44ZM52.93,19.3C66.46,27.88,78.68,31.94,89.17,31,91,68,77.32,96.28,53.07,105.41c-23.43-8.55-37.28-35.85-36.25-75,12.31.65,24.4-2,36.11-11.11ZM45.51,61.61a28.89,28.89,0,0,1,2.64,2.56,104.48,104.48,0,0,1,8.27-11.51c8.24-9.95,5.78-9.3,17.21-9.3L72,45.12a135.91,135.91,0,0,0-11.8,15.3,163.85,163.85,0,0,0-10.76,17.9l-1,1.91-.91-1.94a47.17,47.17,0,0,0-6.09-9.87,33.4,33.4,0,0,0-7.75-7.12c1.49-4.89,8.59-2.38,11.77.31Zm7.38-53.7c17.38,11,33.07,16.22,46.55,15,2.35,47.59-15.23,82.17-46.37,93.9C23,105.82,5.21,72.45,6.53,22.18,22.34,23,37.86,19.59,52.89,7.91Z"/></svg></span>
                                    <span class="font-13 d-block">
                                        <img src="{{ asset('public/frontend/homeimage/horoscope2.svg') }}" height="16" width="16" alt="">&nbsp;
                                        {{ implode(' | ', array_slice(explode(',', $astrologer['primarySkill']), 0, 3)) }}

                                    </span>
                                    <span class="font-13 d-block exp-language">
                                        <img src="{{ asset('public/frontend/homeimage/language-icon.svg') }}" height="16" width="16" alt="">&nbsp;
                                        {{ implode(' • ',  array_slice(explode(',', $astrologer['languageKnown']), 0, 3)) }}</span>
                                    <span class="font-13 d-block">  <img src="{{ asset('public/frontend/homeimage/experience-expert-icon.svg') }}" height="16" width="16" alt="">&nbsp; Experience :{{ $astrologer['experienceInYears'] }} Years</span>



                                </li>

                            </ul>


                            <div class="d-flex align-items-end position-relative">
                                <div class="d-block">
                                    <div class="row">
                                        <div class="psy-review-section col-12">
                                            <div>
                                                <span class="colorblack font-12 m-0 p-0 d-block">
                                                    <span style="color: #495057;font-size: 14px;font-weight: bold;">{{ $astrologer['rating'] }}</span>
                                                    <span>
                                                        @for ($i = 1; $i <= 5; $i++)
                                                            @if ($i <= $astrologer['rating'])
                                                                <i class="fas fa-star filled-star" style="font-size:10px"></i>
                                                            @else
                                                                <i class="far fa-star empty-star" style="font-size:10px"></i>
                                                            @endif
                                                        @endfor
                                                    </span>
                                                </span>
                                            </div>
                                            <div><span style="color: gray;font-size: 12px">{{ $astrologer['totalOrder'] ?? 0 }} Sessions</span></div>
                                        </div>
                                        <div class="col-3 ml-5 responsiveReportBtn">

                                            <a class="btn-block btn btn-report  align-items-center " role="button"
                                                 href="{{route('front.pujacheckout',['slug'=>$astrologer['slug'],'id'=>$puja->id,'package_id'=>$puja->packages->id])}}"
                                                ><i class="fa-regular fa-circle-check"></i> &nbsp;Select
                                            </a>

                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                </div>
                @if ($getAstrologer->hasMorePages())
                <div class="text-center mb-5">
                    <button id="load-more" class="btn-load-more" data-next-page="{{ $getAstrologer->currentPage() + 1 }}">Load More</button>
                </div>
                @endif
            </div>
        </div>
    </div>



@endsection



@section('scripts')
@php
$apikey = DB::table('systemflag')->where('name', 'googleMapApiKey')->first();
@endphp
<script src="https://maps.googleapis.com/maps/api/js?key={{ $apikey->value }}&libraries=places">
</script>
<script>
    $(document).ready(function() {
        let nextPageUrl = "{{ $getAstrologer->nextPageUrl() }}";
        var pujaId = @json($puja->id);
        var packageId = @json($puja->packages->id);
    $('#load-more').click(function() {
        let $btn = $(this);
            if (!nextPageUrl) {
                console.log("No more pages to load!");
                return;
            }
            $btn.prop('disabled', true).html('<span class="loader"></span> Loading...');
            authcheck="{{ authcheck() }}";
             // Get current filters
             let sortBy = $('select[name="sortBy"]').val(); // Sorting dropdown
            let astrologerCategoryId = $('input[name="astrologerCategoryId"]').val(); // Hidden input or category filter
            let searchTerm = $('input[name="s"]').val(); // Search box

            // Add filters to the nextPageUrl if not already there
            let url = new URL(nextPageUrl, window.location.origin);
            if (sortBy) url.searchParams.set('sortBy', sortBy);
            if (astrologerCategoryId) url.searchParams.set('astrologerCategoryId', astrologerCategoryId);
            if (searchTerm) url.searchParams.set('s', searchTerm);
        // AJAX request to fetch more records
        $.ajax({
            url: url.toString(),
            type: "GET",
            success: function(response) {
                console.log("API Response:", response);

                if (response.getAstrologer && response.getAstrologer.data.length > 0) {
                    var html = '';
                    // Loop through the new records and generate HTML
                    response.getAstrologer.data.forEach(function(astrologer) {
                        html += `
                            <div id="ATAAIOfferTile" class="psychic-card overflow-hidden expertOnline ask-guruji" data-astrologer-id="${astrologer.id}">
                                <ul class="list-unstyled d-flex mb-0">
                                    <li class="mr-3 position-relative psychic-presence status-online" data-status="online">
                                        <a href="${astrologer.slug ? '/astrologer-details/' + astrologer.slug : '#'}">
                                            <div class="psyich-img position-relative">
                                                ${astrologer.profileImage ? `
                                                    <img src="/${astrologer.profileImage}" width="85" height="85" style="border-radius:50%;" loading="lazy">
                                                ` : `
                                                    <img src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/images/user-img-new.png') }}" width="85" height="85" style="border-radius:50%;">
                                                `}
                                            </div>
                                        </a>
                                        ${astrologer.chatStatus === 'Busy' ? `
                                            <div class="status-badge specific-Clr-Busy" title="Online"></div>
                                        ` : (astrologer.chatStatus === 'Offline' || !astrologer.chatStatus) ? `
                                            <div class="status-badge specific-Clr-Offline" title="Offline"></div>
                                            <div class="status-badge-txt text-center specific-Clr-Offline">
                                                <span class="status-badge-txt specific-Clr-Offline tooltipex">${astrologer.callStatus || 'Offline'}</span>
                                            </div>
                                        ` : `
                                            <div class="status-badge specific-Clr-Online" title="Online"></div>
                                            <div class="status-badge-txt text-center specific-Clr-Online">
                                                <span class="status-badge-txt specific-Clr-Online tooltipex">${astrologer.chatStatus}</span>
                                            </div>
                                        `}
                                    </li>
                                    <li class="w-100">
                                        <span class="colorblack font-weight-bold font16 mt-0 ml-0 mr-0 mb-0 p-0 text-capitalize d-block" data-toggle="tooltip" title="" style="font-weight: bold;color: #495057 !important;">
                                            ${astrologer.name}
                                            <svg id="Layer_1" fill="#495057" height="16" width="16" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 106.11 122.88">
                                                <path class="cls-1" d="M56.36,2.44A104.34,104.34,0,0,0,79.77,13.9a48.25,48.25,0,0,0,19.08,2.57l6.71-.61.33,6.74c1.23,24.79-2.77,46.33-11.16,63.32C86,103.6,72.58,116.37,55.35,122.85l-4.48,0c-16.84-6.15-30.16-18.57-39-36.47C3.62,69.58-.61,47.88.07,22l.18-6.65,6.61.34A64.65,64.65,0,0,0,28.23,13.5,60.59,60.59,0,0,0,48.92,2.79L52.51,0l3.85,2.44ZM52.93,19.3C66.46,27.88,78.68,31.94,89.17,31,91,68,77.32,96.28,53.07,105.41c-23.43-8.55-37.28-35.85-36.25-75,12.31.65,24.4-2,36.11-11.11ZM45.51,61.61a28.89,28.89,0,0,1,2.64,2.56,104.48,104.48,0,0,1,8.27-11.51c8.24-9.95,5.78-9.3,17.21-9.3L72,45.12a135.91,135.91,0,0,0-11.8,15.3,163.85,163.85,0,0,0-10.76,17.9l-1,1.91-.91-1.94a47.17,47.17,0,0,0-6.09-9.87,33.4,33.4,0,0,0-7.75-7.12c1.49-4.89,8.59-2.38,11.77.31Zm7.38-53.7c17.38,11,33.07,16.22,46.55,15,2.35,47.59-15.23,82.17-46.37,93.9C23,105.82,5.21,72.45,6.53,22.18,22.34,23,37.86,19.59,52.89,7.91Z"/>
                                            </svg>
                                        </span>
                                        <span class="font-13 d-block color-red">
                                            <img src="{{ asset('public/frontend/homeimage/horoscope2.svg') }}" height="16" width="16" alt="">&nbsp;
                                            ${astrologer.primarySkill ? astrologer.primarySkill.split(',').slice(0, 3).join(' | ') : ''}
                                        </span>
                                        <span class="font-13 d-block exp-language">
                                            <img src="{{ asset('public/frontend/homeimage/language-icon.svg') }}" height="16" width="16" alt="">&nbsp;
                                            ${astrologer.languageKnown ? astrologer.languageKnown.split(',').slice(0, 3).join(' • ') : ''}
                                        </span>
                                        <span class="font-13 d-block">
                                            <img src="{{ asset('public/frontend/homeimage/experience-expert-icon.svg') }}" height="16" width="16" alt="">&nbsp; Experience : ${astrologer.experienceInYears} Years
                                        </span>

                                    </li>
                                </ul>
                                <div class="d-flex align-items-end position-relative">
                                    <div class="d-block">
                                        <div class="row">
                                            <div class="psy-review-section col-12">
                                                <div>
                                                    <span class="colorblack font-12 m-0 p-0 d-block">
                                                        <span style="color: #495057;font-size: 14px;font-weight: bold;">${astrologer.rating}</span>
                                                        <span>
                                                            ${Array.from({ length: 5 }, (_, i) => `
                                                                ${i < astrologer.rating ? `
                                                                    <i class="fas fa-star filled-star" style="font-size:10px"></i>
                                                                ` : `
                                                                    <i class="far fa-star empty-star" style="font-size:10px"></i>
                                                                `}
                                                            `).join('')}
                                                        </span>
                                                    </span>
                                                </div>
                                                <div>
                                                    <span style="color: gray;font-size: 12px">${astrologer.totalOrder || 0} Sessions</span>
                                                </div>
                                            </div>
                                            <div class="col-3 ml-5 responsiveReportBtn">
                                              <a class="btn-block btn btn-report align-items-center" role="button"
                                                href="/puja/checkout/${astrologer.slug}/${pujaId}/${packageId}">
                                                    <i class="fa-regular fa-circle-check"></i> &nbsp;Select
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    // Append the new records to the list
                    $('#expert-list').append(html);

                   // Update the nextPageUrl for the next request
                   nextPageUrl = response.getAstrologer.next_page_url;

                    // If there's no next page, remove the button
                    if (!response.getAstrologer.next_page_url) {
                    $btn.remove();
                            } else {
                                $btn.prop('disabled', false).html('Load More');
                            }
                        } else {
                            $btn.remove();
                        }
                    },
            error: function(xhr) {
                console.log("Error:", xhr.responseText);
            }
        });
    });
});



</script>
    <script>

        function toggleSearchBox() {
            // Get the screen width to check if it's mobile
            var screenWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;

            // Toggle the visibility of the search divs and icons
            var searchExpertDiv = document.getElementById('searchExpert');
            var sortExpertDiv = document.getElementById('sortExpert');
            var filterExpertCategoryDiv = document.getElementById('filterExpertCategory');

            var searchIcon = document.getElementById('searchIcon');
            var closeIcon = document.getElementById('closeIcon');

            // Check if the screen is mobile (max-width: 576px or less)
            if (screenWidth <= 576) {
                // If the divs are hidden, show them and change the icon to 'X'
                if (searchExpertDiv.classList.contains('d-none')) {
                    searchExpertDiv.classList.remove('d-none'); // Show the search div
                    sortExpertDiv.classList.remove('d-none'); // Show the sort div
                    filterExpertCategoryDiv.classList.remove('d-none'); // Show the filter div

                    // Change the icon to 'X'
                    searchIcon.classList.add('d-none');
                    closeIcon.classList.remove('d-none');
                } else {
                    // If the divs are already visible, hide them and change the icon back to search
                    searchExpertDiv.classList.add('d-none'); // Hide the search div
                    sortExpertDiv.classList.add('d-none'); // Hide the sort div
                    filterExpertCategoryDiv.classList.add('d-none'); // Hide the filter div

                    // Change the icon back to 'search'
                    searchIcon.classList.remove('d-none');
                    closeIcon.classList.add('d-none');
                }
            }
        }
    </script>

    <script>
         @if (authcheck())
         $(document).ready(function() {
            $('.select2').select2({
                width: '100%' // Ensure Select2 dropdown takes full width of the parent
            });
        });
        @endif


        function onFilterExpertCategoryList() {
            var astrologerCategoryId = $('#psychicCategories').val();
            var url = new URL(window.location.href);
            url.searchParams.set('astrologerCategoryId', astrologerCategoryId);
            window.location.href = url.toString();
        }

        function onSortExpertList() {
            var sortBy = $('#psychicOrderBy').val();
            var url = new URL(window.location.href);
            url.searchParams.set('sortBy', sortBy);
            window.location.href = url.toString();
        }
    </script>

    <script>
        document.getElementById('clearButton').addEventListener('click', function () {
            window.location.href = "{{route('front.pujaAstrologerList',['slug'=>$puja->slug,'package_id'=>$puja->packages->id])}}";
        });
    </script>
@endsection
