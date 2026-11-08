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
                        <i class="fa fa-chevron-right"></i> <span class="breadcrumbtext">Get Detailed Report</span>
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
                                <h1 class="font-22 font-weight-bold">Get Detailed Report</h1>
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
                                <form action="{{ route('front.reportList') }}" method="GET">
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
                                    @foreach ($getAstrologerCategory as $category)
                                    <option value="{{ $category['id'] }}"
                                        {{ $astrologerCategoryId == $category['id'] ? 'selected' : '' }}>
                                        {{ $category['name'] }}
                                    </option>
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
                                    <img width="85" height="85" style="border-radius:50%;" loading="lazy" src="{{ Str::startsWith($astrologer['profileImage'], ['http://','https://']) ? $astrologer['profileImage'] : '/' . $astrologer['profileImage'] }}" onerror="this.onerror=null;this.src='/public/frontend/onlinepujacdn/dashaspeaks/web/content/images/user-img-new.png';" alt="Customer image" onclick="openImage('{{ $astrologer['profileImage'] }}')" />
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
                                    id="" title="Online"
                                    class="status-badge-txt specific-Clr-Offline tooltipex">{{ $astrologer['chatStatus'] ?? 'Offline'}}</span>
                            </div>
                            @else
                            <div class="status-badge specific-Clr-Online" title="Online"></div>
                            <div class="status-badge-txt text-center specific-Clr-Online"><span
                                    id="" title="Online"
                                    class="status-badge-txt specific-Clr-Online tooltipex">{{ $astrologer['chatStatus'] }}</span>
                            </div>
                            @endif

                        </li>


                        <li class=" w-100">
                            <span class="colorblack font-weight-bold font16 mt-0 ml-0 mr-0 mb-0 p-0 text-capitalize d-block" data-toggle="tooltip" title="" style="font-weight: bold;">{{ $astrologer['name'] }}
                                <svg id="Layer_1" fill="#495057" height="16" width="16" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 106.11 122.88">
                                    <defs>
                                        <style>
                                            .cls-1 {
                                                fill-rule: evenodd;
                                            }
                                        </style>
                                    </defs>
                                    <title>secure</title>
                                    <path class="cls-1" d="M56.36,2.44A104.34,104.34,0,0,0,79.77,13.9a48.25,48.25,0,0,0,19.08,2.57l6.71-.61.33,6.74c1.23,24.79-2.77,46.33-11.16,63.32C86,103.6,72.58,116.37,55.35,122.85l-4.48,0c-16.84-6.15-30.16-18.57-39-36.47C3.62,69.58-.61,47.88.07,22l.18-6.65,6.61.34A64.65,64.65,0,0,0,28.23,13.5,60.59,60.59,0,0,0,48.92,2.79L52.51,0l3.85,2.44ZM52.93,19.3C66.46,27.88,78.68,31.94,89.17,31,91,68,77.32,96.28,53.07,105.41c-23.43-8.55-37.28-35.85-36.25-75,12.31.65,24.4-2,36.11-11.11ZM45.51,61.61a28.89,28.89,0,0,1,2.64,2.56,104.48,104.48,0,0,1,8.27-11.51c8.24-9.95,5.78-9.3,17.21-9.3L72,45.12a135.91,135.91,0,0,0-11.8,15.3,163.85,163.85,0,0,0-10.76,17.9l-1,1.91-.91-1.94a47.17,47.17,0,0,0-6.09-9.87,33.4,33.4,0,0,0-7.75-7.12c1.49-4.89,8.59-2.38,11.77.31Zm7.38-53.7c17.38,11,33.07,16.22,46.55,15,2.35,47.59-15.23,82.17-46.37,93.9C23,105.82,5.21,72.45,6.53,22.18,22.34,23,37.86,19.59,52.89,7.91Z" />
                                </svg></span>
                            <span class="font-13 d-block">
                                <img src="{{ asset('public/frontend/homeimage/horoscope2.svg') }}" height="16" width="16" alt="">&nbsp;
                                {{ implode(' | ', array_slice(explode(',', $astrologer['primarySkill']), 0, 3)) }}

                            </span>
                            <span class="font-13 d-block exp-language">
                                <img src="{{ asset('public/frontend/homeimage/language-icon.svg') }}" height="16" width="16" alt="">&nbsp;
                                {{ implode(' • ',  array_slice(explode(',', $astrologer['languageKnown']), 0, 3)) }}</span>
                            <span class="font-13 d-block"> <img src="{{ asset('public/frontend/homeimage/experience-expert-icon.svg') }}" height="16" width="16" alt="">&nbsp; Experience :{{ $astrologer['experienceInYears'] }} Years</span>

                            <span class="font-13 font-weight-semi-bold d-flex"> <span class="exprt-price">
                                    &nbsp;
                                    @if($walletType == 'Coin')
                                    <img src="{{ asset($coinIcon) }}" alt="Wallet Icon" width="15">
                                    @else
                                    {{ $currency['value'] }}
                                    @endif
                                    {{ $astrologer['reportRate'] }}/Report</span></span>

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
                                                    @if ($i <=$astrologer['rating'])
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
                                        data-toggle="modal"
                                        @if (!authcheck()) data-target="#loginSignUp" @else data-target="#intake" @endif><i class="fa-solid fa-file-lines"></i> &nbsp;Get
                                        Report
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


{{-- Intake Form --}}
<div class="modal fade rounded mt-2 mt-md-5 " id="intake" tabindex="-1" role="dialog"
    aria-labelledby="myLargeModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">

                <h4 class="modal-title font-weight-bold">
                    Report Intake Form
                </h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body pt-0 pb-0">
                <div class="body">
                    <div class="row ">

                        <div class="col-lg-12 col-12 ">
                            <div class="mb-3 ">

                                <form class="px-3 font-14" method="post" id="intakeForm">

                                    @if (authcheck())
                                    <input type="hidden" name="userId" value="{{ authcheck()['id'] }}">
                                    <input type="hidden" name="countryCode"
                                        value="{{ authcheck()['countryCode'] }}">
                                    @endif
                                    <input type="hidden" name="astrologerId" id="astroId" value="">
                                    <input type="hidden" name="charge" id="astroCharge" value="">
                                    <input type="hidden" name="reportRate" id="reportRate"
                                        value="">
                                    <div class="row">
                                        <div class="col-12 col-md-6 py-2">
                                            <div class="form-group">
                                                <label for="reportType">Report Type <span
                                                        class="color-red">*</span></label>
                                                <select class="form-control" id="reportType" name="reportType"
                                                    required>
                                                    @foreach ($getReportType['recordList'] as $getReportType)
                                                    <option value="{{ $getReportType['id'] }}">
                                                        {{ $getReportType['title'] }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6 py-2">
                                            <label for="profileImage">Contact No*</label>
                                            <div class="d-flex inputform country-dropdown-container" style="border: 1px solid #ddd; border-radius: 4px; overflow: hidden;">

                                                <!-- Country Code Dropdown -->
                                                <select class="form-control select2" id="countryCode1" name="countryCode" style="border: none; border-right: 1px solid #ddd; border-radius: 0; width: 20%;">
                                                    @foreach ($countries as $country)
                                                    <option data-country="in" value="+{{ $country->phonecode }}" data-ucname="India">
                                                        +{{ $country->phonecode }} {{ $country->iso }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                                <!-- Mobile Number Input -->
                                                <input class="form-control mobilenumber text-box single-line" id="contact" maxlength="12" name="contactNo" type="number" value="{{ old('contactNo') }}" style="border: none; border-radius: 0; width: 130%;" required>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 py-2">
                                            <div class="form-group mb-0">
                                                <label for="firstName">First Name<span
                                                        class="color-red">*</span></label>
                                                <input class="form-control border-pink matchInTxt shadow-none"
                                                    id="firstName" name="firstName" placeholder="Enter First Name"
                                                    type="text" value="" pattern="^[a-zA-Z\s]{2,50}$"
                                                    title="Name should contain only letters and be between 2 and 50 characters long."
                                                    required
                                                    oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 py-2">
                                            <div class="form-group mb-0">
                                                <label for="lastName">Last Name</label>
                                                <input class="form-control border-pink matchInTxt shadow-none"
                                                    id="lastName" name="lastName" placeholder="Enter Last Name"
                                                    type="text" value="">
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 py-2">
                                            <div class="form-group">
                                                <label for="Gender">Gender <span class="color-red">*</span></label>
                                                <select class="form-control" id="Gender" name="gender" required>
                                                    <option value="Male">
                                                        Male</option>
                                                    <option value="Female">
                                                        Female</option>
                                                    <option value="Other">
                                                        Other</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 py-2">
                                            <div class="form-group mb-0">
                                                <label for="BirthDate">Birthdate<span
                                                        class="color-red">*</span></label>
                                                <input class="form-control border-pink matchInTxt shadow-none"
                                                    id="BirthDate" name="birthDate" placeholder="Enter Birthdate"
                                                    type="date" value="" required>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6 py-2">
                                            <div class="form-group mb-0">
                                                <label for="BirthTime">Birthtime<span
                                                        class="color-red">*</span></label>
                                                <input class="form-control border-pink matchInTxt shadow-none"
                                                    id="BirthTime" name="birthTime" placeholder="Enter Birthtime"
                                                    type="time" value="">
                                            </div>
                                            <div id="birthTimeErrorBoy" class="error">Please provide a birth time or
                                                select 'Don't know birth time'.</div>
                                            <input type="checkbox" id="dontKnowTimeBoy"> Don't know birth time.
                                        </div>
                                        <div class="col-12 col-md-6 py-2">
                                            <div class="form-group mb-0">
                                                <label for="BirthPlace">Birthplace<span
                                                        class="color-red">*</span></label>
                                                <input class="form-control border-pink matchInTxt shadow-none"
                                                    id="BirthPlace" name="birthPlace" placeholder="Enter Birthplace"
                                                    type="text" value="" required>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 py-2">
                                            <div class="form-group mb-0">
                                                <label for="MaritalStatus">Marital Status</label>
                                                <select class="form-control" id="MaritalStatus" name="maritalStatus">
                                                    <option value="Single">
                                                        Single</option>
                                                    <option value="Married">
                                                        Married</option>
                                                    <option value="Divorced">
                                                        Divorced</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 py-2">
                                            <div class="form-group mb-0">
                                                <label for="Occupation">Occupation</label>
                                                <input class="form-control border-pink matchInTxt shadow-none"
                                                    id="Occupation" name="occupation" placeholder="Enter Occupation"
                                                    type="text" value="">
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 py-2">
                                            <div class="form-group mb-0">
                                                <label for="answerLanguage">I want Answer in</label>
                                                <select class="form-control" id="answerLanguage"
                                                    name="answerLanguage">
                                                    <option value="English">
                                                        English</option>
                                                    <option value="Hindi">
                                                        Hindi</option>

                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6 py-2">
                                            <div class="form-group mb-0">
                                                <label for="comments">Any Comments<span
                                                        class="color-red">*</span></label>
                                                <textarea class="form-control" id="comments" name="comments" rows="4" cols="50" required></textarea>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="col-12 col-md-12 py-3">
                                        <div class="row">

                                            <div class="col-12 pt-md-3 text-center mt-2">
                                                <button class="font-weight-bold ml-0 w-100 btn btn-chat"
                                                    id="loaderintakeBtn" type="button" style="display:none;"
                                                    disabled>
                                                    <span class="spinner-border spinner-border-sm" role="status"
                                                        aria-hidden="true"></span> Loading...
                                                </button>
                                                <button type="submit"
                                                    class="btn btn-block btn-chat px-4 px-md-5 mb-2"
                                                    id="intakeBtn">Get Report</button>
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



{{-- End Intake form --}}
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
        $('#load-more').click(function() {
            let $btn = $(this);
            if (!nextPageUrl) {
                console.log("No more pages to load!");
                return;
            }
            $btn.prop('disabled', true).html('<span class="loader"></span> Loading...');
            authcheck = "{{ authcheck() }}";
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
                                                    <img src="/${astrologer.profileImage}" width="85" height="85" style="border-radius:50%;" loading="lazy" onerror="this.onerror=null;this.src='/public/frontend/onlinepujacdn/dashaspeaks/web/content/images/user-img-new.png';">
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
                                        <span class="colorblack font-weight-bold font16 mt-0 ml-0 mr-0 mb-0 p-0 text-capitalize d-block" data-toggle="tooltip" title="" style="font-weight: bold;">
                                            ${astrologer.name}
                                            <svg id="Layer_1" fill="#495057" height="16" width="16" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 106.11 122.88">
                                                <path class="cls-1" d="M56.36,2.44A104.34,104.34,0,0,0,79.77,13.9a48.25,48.25,0,0,0,19.08,2.57l6.71-.61.33,6.74c1.23,24.79-2.77,46.33-11.16,63.32C86,103.6,72.58,116.37,55.35,122.85l-4.48,0c-16.84-6.15-30.16-18.57-39-36.47C3.62,69.58-.61,47.88.07,22l.18-6.65,6.61.34A64.65,64.65,0,0,0,28.23,13.5,60.59,60.59,0,0,0,48.92,2.79L52.51,0l3.85,2.44ZM52.93,19.3C66.46,27.88,78.68,31.94,89.17,31,91,68,77.32,96.28,53.07,105.41c-23.43-8.55-37.28-35.85-36.25-75,12.31.65,24.4-2,36.11-11.11ZM45.51,61.61a28.89,28.89,0,0,1,2.64,2.56,104.48,104.48,0,0,1,8.27-11.51c8.24-9.95,5.78-9.3,17.21-9.3L72,45.12a135.91,135.91,0,0,0-11.8,15.3,163.85,163.85,0,0,0-10.76,17.9l-1,1.91-.91-1.94a47.17,47.17,0,0,0-6.09-9.87,33.4,33.4,0,0,0-7.75-7.12c1.49-4.89,8.59-2.38,11.77.31Zm7.38-53.7c17.38,11,33.07,16.22,46.55,15,2.35,47.59-15.23,82.17-46.37,93.9C23,105.82,5.21,72.45,6.53,22.18,22.34,23,37.86,19.59,52.89,7.91Z"/>
                                            </svg>
                                        </span>
                                        <span class="font-13 d-block">
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
                                        <span class="font-13 font-weight-semi-bold d-flex">
                                            <span class="exprt-price">
                                                &nbsp; ${astrologer.reportRate}/Report
                                            </span>
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
                                                    data-toggle="modal"
                                                    ${!authcheck ? 'data-target="#loginSignUp"' : 'data-target="#intake"'}>
                                                    <i class="fa-solid fa-file-lines"></i> &nbsp;Get Report
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

    function initializeAutocomplete(inputId) {
        var input = document.getElementById(inputId);
        var autocomplete = new google.maps.places.Autocomplete(input);

        autocomplete.addListener('place_changed', function(event) {
            var place = autocomplete.getPlace();
            if (place.hasOwnProperty('place_id')) {
                if (!place.geometry) {
                    return;
                }
                latitude.value = place.geometry.location.lat();
                longitude.value = place.geometry.location.lng();
            } else {
                var service = new google.maps.places.PlacesService(document.createElement('div'));
                service.textSearch({
                    query: place.name
                }, function(results, status) {
                    if (status == google.maps.places.PlacesServiceStatus.OK) {
                        latitude.value = results[0].geometry.location.lat();
                        longitude.value = results[0].geometry.location.lng();
                    }
                });
            }
        });
    }
    // Initialize when the page loads
    initializeAutocomplete('BirthPlace');
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
    @if(authcheck())
    $(document).ready(function() {
        $('.select2').select2({
            width: '100%' // Ensure Select2 dropdown takes full width of the parent
        });
    });
    @endif
    $(document).on('click', '.btn-report', function() {
        var astrologerCard = $(this).closest('.psychic-card');
        var astrologerId = astrologerCard.data('astrologer-id');
        var astroChargeText = astrologerCard.find('.exprt-price').text().trim();

        // Extract numerical value from the charge text
        var astroCharge = parseFloat(astroChargeText.match(/[\d.]+/));

        // Set values to hidden fields
        $('#astroId').val(astrologerId);
        $('#astroCharge').val(astroCharge);

    });



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
    $(document).ready(function() {
        $('#intakeBtn').click(function(e) {

            var birthTimeInputBoy = document.getElementById("BirthTime");
            var dontKnowTimeRadioBoy = document.getElementById("dontKnowTimeBoy");
            const birthTimeErrorBoy = document.getElementById('birthTimeErrorBoy');

            if (!birthTimeInputBoy.value && !dontKnowTimeRadioBoy.checked) {
                birthTimeErrorBoy.style.display = 'block';
                birthTimeInputBoy.style.borderColor = 'red';
                e.preventDefault(); // Prevent form submission

                return;
            } else {
                birthTimeErrorBoy.style.display = 'none';
                birthTimeInputBoy.style.borderColor = '';
            }

            e.preventDefault();

            var form = document.getElementById('intakeForm');
            if (form.checkValidity() === false) {
                form.reportValidity();
                return;
            }

            $('#intakeBtn').hide();
            $('#loaderintakeBtn').show();
            setTimeout(function() {
                $('#intakeBtn').show();
                $('#loaderintakeBtn').hide();
            }, 3000);


            var astrocharge = $("#astroCharge").val();

            var formData = $('#intakeForm').serialize();

            // Parse form data as URL parameters
            var urlParams = new URLSearchParams(formData);

            // var total_charge = parseInt(astrocharge);
            var total_charge = parseInt(astrocharge.trim()); // Use trim() to remove any leading or trailing whitespace

            var wallet_amount = "{{ $walletAmount }}";
            wallet_amount = parseInt(wallet_amount);


            if (total_charge <= wallet_amount) {
                $.ajax({
                    url: "{{ route('api.addReport', ['token' => $token]) }}",
                    type: 'POST',
                    data: formData,
                    success: function(response) {

                        setTimeout(function() {
                            toastr.success(
                                'Report Request Sent Successfully.'
                            );
                            $('#intake').modal('hide');

                        }, 2000);
                    },
                    error: function(xhr, status, error) {
                        toastr.error(xhr.responseText);
                    }
                });
            } else {
                toastr.error('Insufficient balance. Please recharge your wallet.');
                window.location.href = "{{ route('front.walletRecharge') }}"
            }

        });
    });

    document.getElementById('clearButton').addEventListener('click', function() {
        window.location.href = "{{ route('front.reportList') }}";
    });
</script>
@endsection
