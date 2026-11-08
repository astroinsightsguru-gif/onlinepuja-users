@extends('frontend.layout.master')
@section('content')

@php
$countries = DB::table('countries')
->orderByRaw("CASE WHEN phonecode = 91 THEN 0 ELSE 1 END")
->get();


use Symfony\Component\HttpFoundation\Session\Session;
$session = new Session();
$token = $session->get('token');

@endphp
<div class="pt-1 pb-1 bg-red d-none d-md-block onlinepuja-breadcrumb">
    <div class="container">
        <div class="row afterLoginDisplay">
            <div class="col-md-12 d-flex align-items-center">

                <span style="text-transform: capitalize; ">
                    <span class="text-white breadcrumbs">
                        <a href="{{ route('front.home') }}" style="color:white;text-decoration:none">
                            <i class="fa fa-home font-18"></i>
                        </a>
                        <i class="fa fa-chevron-right"></i> <span class="breadcrumbtext">Talk To {{ucfirst($professionTitle)}}</span>
                    </span>
                </span>

            </div>
        </div>
    </div>
</div>


{{-- Call Intake --}}

<div class="modal fade rounded mt-2 mt-md-5 " id="callintake" tabindex="-1" role="dialog"
    aria-labelledby="myLargeModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">

                <h4 class="modal-title font-weight-bold">
                    Birth Details
                </h4>
                <button type="button" class="btn btn-sm btn-primary" data-dismiss="modal">Close</button>
                <!-- <button type="button" class="close" data-dismiss="modal">&times;</button> -->
            </div>
            <div class="modal-body pt-0 pb-0">
                <div class="bg-white body">
                    <div class="row ">

                        <div class="col-lg-12 col-12 ">
                            <div class="mb-3 ">

                                <form class="px-3 font-14" method="post" id="callintakeForm">

                                    @if (authcheck())
                                    <input type="hidden" name="userId" value="{{ authcheck()['id'] }}">
                                    @endif

                                    <input type="hidden" name="call_type" id="call_type" value="">
                                    <input type="hidden" name="astrocharge" id="astrocharge" value="">
                                    <input type="hidden" name="astrologerId" id="astroId" value="">

                                    <div class="row">
                                        <div class="col-12 col-md-6 py-2">
                                            <div class="form-group mb-0">
                                                <label for="Name">Name<span class="color-red">*</span></label>
                                                <input class="form-control border-pink matchInTxt shadow-none"
                                                    id="Name" name="name" placeholder="Enter Name"
                                                    type="text"
                                                    value="{{ $getIntakeForm['recordList'][0]['name'] ?? '' }}"
                                                    pattern="^[a-zA-Z\s]{2,50}$"
                                                    title="Name should contain only letters and be between 2 and 50 characters long."
                                                    required
                                                    oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 py-2">
                                            <label for="profileImage">Contact No*</label>
                                            <div class="d-flex inputform country-dropdown-container" style="border: 1px solid #ddd; border-radius: 4px; overflow: hidden;">
                                                <!-- Country Code Dropdown -->
                                                <select class="form-control select2" id="countryCode1" name="countryCode" style="border: none; border-right: 1px solid #ddd; border-radius: 0; width: 20%;">
                                                    @foreach ($countries as $country)
                                                    <option data-country="in" value="{{ $getIntakeForm['recordList'][0]['countryCode'] ?? $country->phonecode}}" data-ucname="India">
                                                        +{{ $country->phonecode }} {{ $country->iso }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                                <!-- Mobile Number Input -->
                                                <input class="form-control mobilenumber text-box single-line" id="contact" maxlength="12"
                                                    name="phoneNumber" type="number"
                                                    value="{{ $getIntakeForm['recordList'][0]['phoneNumber'] ?? ''}}"
                                                    style="border: none; border-radius: 0; width: 130%;" required>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6 py-2">
                                            <div class="form-group">
                                                <label for="Gender">Gender <span class="color-red">*</span></label>
                                                <select class="form-control" id="Gender" name="gender" required>
                                                    <option value="Male" {{ isset($getIntakeForm['recordList'][0]['gender']) && $getIntakeForm['recordList'][0]['gender'] == 'Male' ? 'selected' : '' }}>Male</option>
                                                    <option value="Female" {{ isset($getIntakeForm['recordList'][0]['gender']) && $getIntakeForm['recordList'][0]['gender'] == 'Female' ? 'selected' : '' }}>Female</option>
                                                    <option value="Other" {{ isset($getIntakeForm['recordList'][0]['gender']) && $getIntakeForm['recordList'][0]['gender'] == 'Other' ? 'selected' : '' }}>Other</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6 py-2">
                                            <div class="form-group mb-0">
                                                <label for="BirthDate">Birthdate<span class="color-red">*</span></label>
                                                <input class="form-control border-pink matchInTxt shadow-none"
                                                    id="BirthDate" name="birthDate" placeholder="Enter Birthdate"
                                                    type="date"
                                                    value="{{ isset($getIntakeForm['recordList'][0]['birthDate']) ? date('Y-m-d', strtotime($getIntakeForm['recordList'][0]['birthDate'])) : '' }}" required>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6 py-2">
                                            <div class="form-group mb-0">
                                                <label for="BirthTime">Birthtime</label>
                                                <input class="form-control border-pink matchInTxt shadow-none"
                                                    id="BirthTime" name="birthTime" placeholder="Enter Birthtime"
                                                    type="time"
                                                    value="{{ $getIntakeForm['recordList'][0]['birthTime'] ?? '' }}">
                                            </div>
                                        </div>

                                        <input type="hidden" id="latitude" name="latitude" value="{{ $getIntakeForm['recordList'][0]['latitude'] ?? '' }}">
                                        <input type="hidden" id="longitude" name="longitude" value="{{ $getIntakeForm['recordList'][0]['longitude'] ?? '' }}">
                                        <input type="hidden" id="timezone" name="timezone" value="{{ $getIntakeForm['recordList'][0]['timezone'] ?? '5.5' }}">

                                        <div class="col-12 col-md-6 py-2">
                                            <div class="form-group mb-0">
                                                <label for="BirthPlace">Birthplace<span class="color-red">*</span></label>
                                                <input class="form-control border-pink matchInTxt shadow-none"
                                                    id="BirthPlace" name="birthPlace" placeholder="Enter Birthplace"
                                                    type="text"
                                                    value="{{ $getIntakeForm['recordList'][0]['birthPlace'] ?? '' }}" required>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6 py-2">
                                            <div class="form-group mb-0">
                                                <label for="MaritalStatus">Marital Status<span class="color-red">*</span></label>
                                                <select class="form-control" id="MaritalStatus" name="maritalStatus" required>
                                                    <option value="Single" {{ isset($getIntakeForm['recordList'][0]['maritalStatus']) && $getIntakeForm['recordList'][0]['maritalStatus'] == 'Single' ? 'selected' : '' }}>Single</option>
                                                    <option value="Married" {{ isset($getIntakeForm['recordList'][0]['maritalStatus']) && $getIntakeForm['recordList'][0]['maritalStatus'] == 'Married' ? 'selected' : '' }}>Married</option>
                                                    <option value="Divorced" {{ isset($getIntakeForm['recordList'][0]['maritalStatus']) && $getIntakeForm['recordList'][0]['maritalStatus'] == 'Divorced' ? 'selected' : '' }}>Divorced</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6 py-2">
                                            <div class="form-group mb-0">
                                                <label for="Occupation">Occupation</label>
                                                <input class="form-control border-pink matchInTxt shadow-none"
                                                    id="Occupation" name="occupation" placeholder="Enter Occupation"
                                                    type="text"
                                                    value="{{ $getIntakeForm['recordList'][0]['occupation'] ?? '' }}">
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6 py-2">
                                            <div class="form-group mb-0">
                                                <label for="TopicOfConcern">Topic Of Concern</label>
                                                <input class="form-control border-pink matchInTxt shadow-none"
                                                    id="TopicOfConcern" name="topicOfConcern"
                                                    placeholder="Enter Topic Of Concern" type="text"
                                                    value="{{ $getIntakeForm['recordList'][0]['topicOfConcern'] ?? '' }}" required >
                                            </div>
                                        </div>

                                        @if (authcheck())
                                        @if ($isFreeAvailable == false)
                                        <input type="hidden" name="isFreeSession" value="0">
                                        <div class="col-12 py-3">
                                            <div class="form-group mb-0">
                                                <label>Select Time You want to call<span class="color-red">*</span></label><br>
                                                <div class="btn-group-toggle" data-toggle="buttons">
                                                    <label class="btn btn-info btn-sm mt-2">
                                                        <input type="radio" name="call_duration" value="300" required> 5 mins
                                                    </label>
                                                    <label class="btn btn-info btn-sm mt-2">
                                                        <input type="radio" name="call_duration" value="600" required> 10 mins
                                                    </label>
                                                    <label class="btn btn-info btn-sm mt-2">
                                                        <input type="radio" name="call_duration" value="900" required> 15 mins
                                                    </label>
                                                    <label class="btn btn-info btn-sm mt-2">
                                                        <input type="radio" name="call_duration" value="1200" required> 20 mins
                                                    </label>
                                                    <label class="btn btn-info btn-sm mt-2">
                                                        <input type="radio" name="call_duration" value="1500" required> 25 mins
                                                    </label>
                                                    <label class="btn btn-info btn-sm mt-2">
                                                        <input type="radio" name="call_duration" value="1800" required> 30 mins
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        @else
                                        <input type="hidden" name="call_duration" value="{{ $getIntakeForm['default_time'] }}">
                                        <input type="hidden" name="isFreeSession" value="1">
                                        @endif
                                        @endif
                                    </div>

                                    <!-- Call Mode -->
                                    <div class="col-12 py-3">
                                        <div class="form-group mb-0">
                                            <label>Call Mode <span class="color-red">*</span></label><br>
                                            <div class="btn-group-toggle" data-toggle="buttons">
                                                <label id="instantBtn" class="btn btn-light btn-sm mt-2 active">
                                                    <input type="radio" name="IsSchedule" value="0" checked
                                                        onclick="toggleSchedule(false)"> Instant Call
                                                </label>
                                                <label id="scheduleBtn" class="btn btn-light btn-sm mt-2">
                                                    <input type="radio" name="IsSchedule" value="1"
                                                        onclick="toggleSchedule(true)"> Schedule Appointment
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Schedule Fields -->
                                    <div id="scheduleFields" class="col-12 row py-3" style="display:none;">
                                        <div class="col-12 col-md-6 py-2">
                                            <div class="form-group mb-0">
                                                <label for="schedule_date">Schedule Date<span class="color-red">*</span></label>
                                                <input type="date" class="form-control" id="schedule_date" name="schedule_date">
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 py-2">
                                            <div class="form-group mb-0">
                                                <label for="schedule_time">Schedule Time<span class="color-red">*</span></label>
                                                <input type="time" class="form-control" id="schedule_time" name="schedule_time">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Submit Buttons -->
                                    <div class="col-12 col-md-12 py-3">
                                        <div class="row">
                                            <div class="col-12 pt-md-3 text-center mt-2">
                                                <button class="font-weight-bold ml-0 w-100 btn btn-chat"
                                                    id="callloaderintakeBtn" type="button" style="display:none;" disabled>
                                                    <span class="spinner-border spinner-border-sm" role="status"
                                                        aria-hidden="true"></span> Loading...
                                                </button>
                                                <button type="submit" class="btn btn-block btn-chat px-4 px-md-5 mb-2"
                                                    id="callintakeBtn">Start Call</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <!-- JS Logic -->


                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


{{-- End  Call --}}



<div class="py-md-3 expert-search-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-12" id="experts" style="overflow:hidden;">
                <div id="expert-search" class="my-3 my-md-0">
                    <!--For Serach Component-->
                    <div class="expert-search-form">
                        <div class="row mx-auto px-2 px-md-0 flex-md-nowrap align-items-center round">
                            <div
                                class="col-12 col-md-3 col-sm-auto text-left d-flex justify-content-between align-items-center w-100 bg-white px-0">
                                <h1 class="font-22 font-weight-bold">Talk to {{ucfirst($professionTitle)}}</h1>
                                {{-- <img src="#" alt="Filter Experts based on Status" width="18"
                                        height="18" class="img-fluid filterIcon float-right d-md-none"
                                        onClick="fnSearch()" /> --}}
                                <div class="searchIcon1">
                                    <i id="searchIcon" class="fa-solid fa-filter" onClick="toggleSearchBox()"></i>
                                    <i id="closeIcon" class="fa-solid fa-xmark close-icon d-none" onClick="toggleSearchBox()"></i>

                                </div>
                            </div>
                            <div class="col-ms-12 col-md-3 d-none d-md-block" id="searchExpert">
                                <form action="{{ route('front.talkList') }}" method="GET">
                                    <div class="search-box">
                                        <input value="{{ isset($searchTerm) ? $searchTerm : '' }}"
                                            class="form-control rounded" name="s"
                                            placeholder="Search {{ucfirst($professionTitle)}}s" type="search" autocomplete="off">
                                        <button type="submit" class="btn  search-btn" id="search-button">
                                            <i class="fa fa-search"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="col-ms-12 col-md-2 d-none d-md-flex nowrap align-items-center pl-md-0 pt-2 pb-2 "
                                id="sortExpert">
                                <select class="form-control font13 rounded" name="sortBy"
                                    onchange="onSortExpertList()" id="psychicOrderBy">
                                    <option value="1" {{ $sortBy == '1' ? 'selected' : '' }}>Sort Filter</option>
                                    <option value="experienceLowToHigh"
                                        {{ $sortBy == 'experienceLowToHigh' ? 'selected' : '' }}>Low Experience
                                    </option>
                                    <option value="experienceHighToLow"
                                        {{ $sortBy == 'experienceHighToLow' ? 'selected' : '' }}>High Experience
                                    </option>
                                    <option value="priceLowToHigh"
                                        {{ $sortBy == 'priceLowToHigh' ? 'selected' : '' }}>
                                        Lowest Price</option>
                                    <option value="priceHighToLow"
                                        {{ $sortBy == 'priceHighToLow' ? 'selected' : '' }}>
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
                <div id="ATAAIOfferTile" class="psychic-card overflow-hidden expertOnline ask-guruji" data-astrologer-id="{{ $astrologer['id'] }}">
                    <a href="{{ route('front.astrologerDetails',  ['slug' => $astrologer['slug'] ?? ($astrologer->slug ?? ($astrologer['id'] ?? 1))]) }}" class="text-decoration-none">
                        @if ($astrologer['is_boosted']== 1)
                        <span class=" must-try-badge font-10 position-absolute font-weight-semi text-center align-items-center justify-content-center text-white">Sponsored</span>
                        @endif
                        <ul class="list-unstyled d-flex mb-0">
                            <li class="mr-3 position-relative psychic-presence status-online" data-status="online">
                                <div class="psyich-img position-relative">
                                    @if ($astrologer['profileImage'])
                                    <img width="85" height="85" style="border-radius:50%;" loading="lazy" src="{{ Str::startsWith($astrologer['profileImage'], ['http://','https://']) ? $astrologer['profileImage'] : '/' . $astrologer['profileImage'] }}" onerror="this.onerror=null;this.src='/build/assets/images/person.png';" alt="Customer image" onclick="openImage('{{ $astrologer['profileImage'] }}')" />
                                    @else
                                    <img src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/images/user-img-new.png') }}" width="85" height="85" style="border-radius:50%;">
                                    @endif
                                </div>
                                @if($astrologer['callStatus']=='Busy')
                                <div class="status-badge specific-Clr-Busy" title="Online"></div>
                                <div class="status-badge-txt text-center specific-Clr-Busy"><span
                                        id="" title="Online"
                                        class="status-badge-txt specific-Clr-Busy tooltipex">{{ $astrologer['callStatus'] }}</span>
                                </div>
                                @elseif($astrologer['callStatus']=='Offline' || empty($astrologer['callStatus']))
                                @if($astrologer['callStatus']=='Offline' && $astrologer['emergencyCallStatus'])
                                <div class="status-badge specific-Clr-Busy" title="Emergency"></div>
                                <div class="status-badge-txt text-center specific-Clr-Busy"><span
                                        id="" title="Emergency"
                                        class="status-badge-txt specific-Clr-Busy tooltipex">Emergency</span>
                                </div>
                                @else
                                <div class="status-badge specific-Clr-Offline" title="Offline"></div>
                                <div class="status-badge-txt text-center specific-Clr-Offline"><span
                                        id="" title="Online"
                                        class="status-badge-txt specific-Clr-Offline tooltipex">{{ $astrologer['callStatus'] ?? 'Offline'}}</span>
                                </div>
                                @endif
                                @else

                                <div class="status-badge specific-Clr-Online" title="Online"></div>
                                <div class="status-badge-txt text-center specific-Clr-Online"><span
                                        id="" title="Online"
                                        class="status-badge-txt specific-Clr-Online tooltipex">{{ $astrologer['callStatus'] }}</span>
                                </div>
                                @endif
                            </li>

                            <li class=" w-100 colorblack">
                                <!-- @if($astrologer->AstroFreePaid == 0)
                                            <span class="badge bg-success" style="float: inline-end; color:#fff">Free</span>
                                        @elseif($astrologer->AstroFreePaid == 1)
                                            <span class="badge bg-danger" style="float: inline-end; color:#fff">Paid</span>
                                        @endif -->
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
                                <span class="font-13 d-block color-red">
                                    <img src="{{ asset('public/frontend/homeimage/horoscope2.svg') }}" height="16" width="16" alt="">&nbsp;
                                    {{ implode(' | ', array_slice(explode(',', $astrologer['primarySkill']), 0, 3)) }}

                                </span>

                                <span class="font-13 d-block exp-language">
                                    <img src="{{ asset('public/frontend/homeimage/language-icon.svg') }}" height="16" width="16" alt="">&nbsp;
                                    {{ implode(' • ',  array_slice(explode(',', $astrologer['languageKnown']), 0, 3)) }}</span>
                                <span class="font-13 d-block"> <img src="{{ asset('public/frontend/homeimage/experience-expert-icon.svg') }}" height="16" width="16" alt="">&nbsp; Experience :{{ $astrologer['experienceInYears'] }} Years</span>

                                @if($astrologer['emergencyCallStatus'])
                                <span class="font-13 font-weight-semi-bold d-flex"> &nbsp; &nbsp;
                                    <span
                                        class="exprt-price mr-2">
                                        <i class="fa-solid fa-phone mr-1"></i>
                                            @if($walletType == 'coin')
                                                        <img src="{{ asset($coinIcon) }}" alt="Wallet Icon" width="15">
                                                    @else
                                                        {{ $currency['value'] }}
                                                    @endif
                                                {{ $astrologer['emergency_audio_charge'] }}</span><i
                                        class="fa-solid fa-video mt-1 mr-1"></i>
                                            @if($walletType == 'coin')
                                                        <img src="{{ asset($coinIcon) }}" alt="Wallet Icon" width="15">
                                                    @else
                                                        {{ $currency['value'] }}
                                                    @endif
                                                {{ $astrologer['emergency_video_charge'] }}</span>
                                @elseif ($isFreeAvailable == true)
                                <span class="font-13 font-weight-semi-bold d-flex"> <span
                                        class="exprt-price">&nbsp;<del>
                                            @if($walletType == 'coin')
                                                        <img src="{{ asset($coinIcon) }}" alt="Wallet Icon" width="15">
                                                    @else
                                                        {{ $currency['value'] }}
                                                    @endif
                                        {{ $astrologer['charge'] }}</del>/Min</span>
                                    <span class="free-badge text-uppercase color-red ml-2">Free</span></span>
                                @else
                                <span class="font-13 font-weight-semi-bold d-flex"> &nbsp; &nbsp;
                                    <span
                                        class="exprt-price mr-2">
                                        <i class="fa-solid fa-phone mr-1"></i>
                                        @if($walletType == 'coin')
                                                        <img src="{{ asset($coinIcon) }}" alt="Wallet Icon" width="15">
                                                    @else
                                                        {{ $currency['value'] }}
                                                    @endif
                                    {{ $astrologer['charge'] }}</span><i
                                        class="fa-solid fa-video mt-1 mr-1"></i>
                                       @if($walletType == 'coin')
                                                        <img src="{{ asset($coinIcon) }}" alt="Wallet Icon" width="15">
                                                    @else
                                                        {{ $currency['value'] }}
                                                    @endif
                                    {{ $astrologer['videoCallRate'] }}</span>
                                @endif
                            </li>
                        </ul>

                        {{-- <div class="d-flex align-items-center justify-content-between"> --}}

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
                                    <div class="col-3 responsiveCallBtn mt-1">
                                        @if($astrologer['callStatus']=='Busy' || $astrologer['callStatus']=='Offline' || empty($astrologer['callStatus']))
                                        @if($astrologer['emergencyCallStatus'] && $astrologer['callStatus']=='Offline')
                                        <a class="btn-block btn btn-call btn-audio-call align-items-center " role="button"
                                            data-toggle="modal"
                                            @if (!authcheck()) data-target="#loginSignUp" @else data-target="#callintake" @endif
                                            id="audio-call-btn"><i class="fa-solid fa-phone"></i>&nbsp;Call
                                        </a>
                                        @else
                                        <a class="btn-block btn btn-call  align-items-center " style="font-size: 14px !important;">{{ $astrologer['callStatus'] ?? 'Offline'}}</a>
                                        @endif

                                        @else
                                        <a class="btn-block btn btn-call btn-audio-call align-items-center " role="button"
                                            data-toggle="modal"
                                            @if (!authcheck()) data-target="#loginSignUp" @else data-target="#callintake" @endif
                                            id="audio-call-btn"><i class="fa-solid fa-phone"></i>&nbsp;Call
                                        </a>
                                        @endif
                                    </div>
                                    <div class="col-3 responsiveVideoBtn mt-1">
                                        @if($astrologer['callStatus']=='Busy' || $astrologer['callStatus']=='Offline' || empty($astrologer['callStatus']))
                                        @if($astrologer['emergencyCallStatus'] && $astrologer['callStatus']=='Offline')
                                        <a class="btn-block btn btn-call btn-video-call align-items-center" role="button"
                                            data-toggle="modal"
                                            @if (!authcheck()) data-target="#loginSignUp" @else data-target="#callintake" @endif
                                            id="video-call-btn"><i class="fa-solid fa-video"></i> &nbsp;Call
                                        </a>
                                        @else
                                        <a class="btn-block btn btn-call  align-items-center " style="font-size: 14px !important;">{{ $astrologer['callStatus'] ?? 'Offline'}}</a>
                                        @endif

                                        @else
                                        <a class="btn-block btn btn-call btn-video-call align-items-center" role="button"
                                            data-toggle="modal"
                                            @if (!authcheck()) data-target="#loginSignUp" @else data-target="#callintake" @endif
                                            id="video-call-btn"><i class="fa-solid fa-video"></i> &nbsp;Call
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                    {{-- </div> --}}
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
            $.ajax({
                url: url.toString(),
                type: "GET",
                success: function(response) {

                    if (response.getAstrologer && response.getAstrologer.data.length > 0) {
                        var html = '';
                        response.getAstrologer.data.forEach(function(astrologer) {
                            html += `
                            <div id="ATAAIOfferTile" class="psychic-card overflow-hidden expertOnline ask-guruji" data-astrologer-id="${astrologer.id}">
                                <a href="${astrologer.slug ? '/astrologer-details/' + astrologer.slug : '#'}" class="text-decoration-none">
                                    ${astrologer.is_boosted == 1 ? `
                                        <span class="must-try-badge font-10 position-absolute font-weight-semi text-center align-items-center justify-content-center text-white">Sponsored</span>
                                    ` : ''}
                                    <ul class="list-unstyled d-flex mb-0">
                                        <li class="mr-3 position-relative psychic-presence status-online" data-status="online">
                                            <div class="psyich-img position-relative">
                                                ${astrologer.profileImage ? `
                                                    <img src="/${astrologer.profileImage}" width="85" height="85" style="border-radius:50%;" loading="lazy">
                                                ` : `
                                                    <img src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/images/user-img-new.png') }}" width="85" height="85" style="border-radius:50%;">
                                                `}
                                            </div>
                                               ${astrologer.callStatus === 'Busy' ? `
                                                <div class="status-badge specific-Clr-Busy" title="Online"></div>
                                                <div class="status-badge-txt text-center specific-Clr-Busy">
                                                    <span class="status-badge-txt specific-Clr-Busy tooltipex">${astrologer.callStatus}</span>
                                                </div>
                                            ` : (astrologer.callStatus === 'Offline' && astrologer.emergencyCallStatus) ?`
                                              <div class="status-badge specific-Clr-Busy" title="Online"></div>
                                                <div class="status-badge-txt text-center specific-Clr-Busy">
                                                    <span class="status-badge-txt specific-Clr-Busy tooltipex">Emergency</span>
                                                </div>
                                            ` : (astrologer.callStatus === 'Offline' || !astrologer.callStatus) ? `
                                                <div class="status-badge specific-Clr-Offline" title="Offline"></div>
                                                <div class="status-badge-txt text-center specific-Clr-Offline">
                                                    <span class="status-badge-txt specific-Clr-Offline tooltipex">${astrologer.callStatus || 'Offline'}</span>
                                                </div>

                                            ` : `
                                                <div class="status-badge specific-Clr-Online" title="Online"></div>
                                                <div class="status-badge-txt text-center specific-Clr-Online">
                                                    <span class="status-badge-txt specific-Clr-Online tooltipex">${astrologer.callStatus}</span>
                                                </div>
                                            `}
                                        </li>
                                        <li class="w-100 colorblack">
                                            <span class="colorblack font-weight-bold font16 mt-0 ml-0 mr-0 mb-0 p-0 text-capitalize d-block" data-toggle="tooltip" title="" style="font-weight: bold;">
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
                                            ${astrologer.emergencyCallStatus ? `
                                              <span class="font-13 font-weight-semi-bold d-flex">
                                                    &nbsp; &nbsp;
                                                    <span class="exprt-price mr-2">
                                                        <i class="fa-solid fa-phone mr-1"></i>${astrologer.emergency_audio_charge}
                                                    </span>
                                                    <i class="fa-solid fa-video mt-1 mr-1"></i>${astrologer.emergency_video_charge}
                                                </span>
                                            `: (astrologer.isFreeAvailable) ? `
                                                <span class="font-13 font-weight-semi-bold d-flex">
                                                    <span class="exprt-price">
                                                        &nbsp; <del> ${astrologer.charge}</del>/Min
                                                    </span>
                                                    <span class="free-badge text-uppercase color-red ml-2">Free</span>
                                                </span>
                                            ` : `
                                                <span class="font-13 font-weight-semi-bold d-flex">
                                                    &nbsp; &nbsp;
                                                    <span class="exprt-price mr-2">
                                                        <i class="fa-solid fa-phone mr-1"></i>${astrologer.charge}
                                                    </span>
                                                    <i class="fa-solid fa-video mt-1 mr-1"></i>${astrologer.videoCallRate}
                                                </span>
                                            `}
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
                                                <div class="col-3 responsiveCallBtn mt-1">
                                                      ${
                                                      astrologer.callStatus === 'Offline' && astrologer.emergencyCallStatus ? `
                                                          <a class="btn-block btn btn-call btn-audio-call align-items-center" role="button" data-toggle="modal" ${!authcheck ? 'data-target="#loginSignUp"' : 'data-target="#callintake"'}>
                                                              <i class="fa-solid fa-phone"></i>&nbsp;Call
                                                          </a>
                                                      ` : (
                                                          astrologer.callStatus === 'Busy' || astrologer.callStatus === 'Offline' || !astrologer.callStatus ? `
                                                              <a class="btn-block btn btn-call align-items-center" style="font-size: 14px !important;">
                                                                  ${astrologer.callStatus || 'Offline'}
                                                              </a>
                                                          ` : `
                                                              <a class="btn-block btn btn-call btn-audio-call align-items-center" role="button" data-toggle="modal" ${!authcheck ? 'data-target="#loginSignUp"' : 'data-target="#callintake"'}>
                                                                  <i class="fa-solid fa-phone"></i>&nbsp;Call
                                                              </a>
                                                          `
                                                      )
                                                    }
                                                </div>
                                                <div class="col-3 responsiveVideoBtn mt-1">
                                                    ${
                                                    astrologer.callStatus === 'Offline' && astrologer.emergencyCallStatus ? `
                                                        <a class="btn-block btn btn-call btn-video-call align-items-center" role="button" data-toggle="modal" ${!authcheck ? 'data-target="#loginSignUp"' : 'data-target="#callintake"'}>
                                                            <i class="fa-solid fa-video"></i>&nbsp;Call
                                                        </a>
                                                    ` : (
                                                        astrologer.callStatus === 'Busy' || astrologer.callStatus === 'Offline' || !astrologer.callStatus ? `
                                                            <a class="btn-block btn btn-call align-items-center" style="font-size: 14px !important;">
                                                                ${astrologer.callStatus || 'Offline'}
                                                            </a>
                                                        ` : `
                                                            <a class="btn-block btn btn-call btn-video-call align-items-center" role="button" data-toggle="modal" ${!authcheck ? 'data-target="#loginSignUp"' : 'data-target="#callintake"'}>
                                                                <i class="fa-solid fa-video"></i>&nbsp;Call
                                                            </a>
                                                        `
                                                    )
                                                   }
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        `;
                        });
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
        var originLatitude = document.getElementById('latitude');
        var originLongitude = document.getElementById('longitude');

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


        @if($getAstrologer)
        $(document).on('click', '.btn-audio-call', function() {

            var astrologerCard = $(this).closest('.psychic-card');
            var astrologerId = astrologerCard.data('astrologer-id');

            $('#astroId').val(astrologerId);
            var astrologerId = $('#astroId').val();

            $("#call_type").val(10);
            var astroChargeText = astrologerCard.find('.exprt-price').text().trim();

            // Extract numerical value from the charge text
            var astroCharge = parseFloat(astroChargeText.match(/[\d.]+/));

            $('#astroCharge').val(astroCharge);

        });


        $(document).on('click', '.btn-video-call', function() {
            var astrologerCard = $(this).closest('.psychic-card');
            var astrologerId = astrologerCard.data('astrologer-id');
            $('#astroId').val(astrologerId);
            var astrologerId = $('#astroId').val();

            $("#call_type").val(11);
            var astroChargeText = astrologerCard.find('.exprt-price').text().trim();

            // Extract numerical value from the charge text
            var astroCharge = parseFloat(astroChargeText.match(/[\d.]+/));

            $('#astroCharge').val(astroCharge);

        });
        @endif


        $('#callintakeBtn').click(function(e) {
            e.preventDefault();

            var form = document.getElementById('callintakeForm');
            if (form.checkValidity() === false) {
                form.reportValidity();
                return;
            }

            @php

            $session = new Session();
            $token = $session->get('token');
            @endphp



            $('#callintakeBtn').hide();
            $('#callloaderintakeBtn').show();
            setTimeout(function() {
                $('#callintakeBtn').show();
                $('#callloaderintakeBtn').hide();
            }, 3000);

            astrocharge = $("#astrocharge").val();



            <?php
            $wallet_amount = '';
            if (authcheck()) {
                $wallet_amount = $walletAmount;
            }
            ?>

            var formData = $('#callintakeForm').serialize();

            // Parse form data as URL parameters
            var urlParams = new URLSearchParams(formData);
            var call_duration = parseInt(urlParams.get('call_duration'));

            var call_duration_minutes = Math.ceil(call_duration / 60);

            var total_charge = astrocharge * call_duration_minutes;

            @if($getAstrologer)
            var isFreeAvailable = "{{ $isFreeAvailable }}";

            var wallet_amount = "{{ $wallet_amount }}";
            @endif

            $.ajax({
                url: "{{ route('api.checkCallSessionTaken', ['token' => $token]) }}",
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (!response.recordList)
                        callRequestWallet();
                    else
                        toastr.error('Your request is already there');

                },
                error: function(xhr, status, error) {
                    toastr.error(xhr.responseText);
                }
            });

            function callRequestWallet() {
                // Check if free chat is available and wallet has sufficient balance
                if (isFreeAvailable != true) {
                    if (total_charge <= wallet_amount) {
                        AddCallRequestFunc(formData)
                    } else {
                        toastr.error('Insufficient balance. Please recharge your wallet.');
                        window.location.href = "{{ route('front.walletRecharge') }}";
                    }
                } else {
                    AddCallRequestFunc(formData)
                }
            }


            function AddCallRequestFunc(formData) {
                $.ajax({
                    url: "{{ route('api.addCallRequest', ['token' => $token]) }}",
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        $.ajax({
                            url: "{{ route('api.intakeForm', ['token' => $token]) }}",
                            type: 'POST',
                            data: formData,
                            success: function(response) {

                                setTimeout(function() {
                                    toastr.success(
                                        'Call Request Sent ! you will be notified if {{strtolower($professionTitle)}} accept your request.'
                                    );
                                }, 2000);
                                $('#callintake').modal('hide');
                            },
                            error: function(xhr, status, error) {
                                toastr.error(xhr.responseText);
                            }
                        });
                    },
                    error: function(xhr, status, error) {
                        if (xhr.responseJSON && xhr.responseJSON.recordList && xhr.responseJSON.recordList.message) {
                            toastr.error(xhr.responseJSON.recordList.message);
                        } else {
                            toastr.error(xhr.responseText);
                        }
                    }
                });

            }
        });
    });

    document.getElementById('clearButton').addEventListener('click', function() {
        window.location.href = "{{ route('front.talkList') }}";
    });
</script>


<script>
    function toggleSchedule(show) {
        let scheduleFields = document.getElementById('scheduleFields');
        let scheduleDate = document.getElementById('schedule_date');
        let scheduleTime = document.getElementById('schedule_time');

        if (show) {
            scheduleFields.style.display = 'flex';
            scheduleDate.setAttribute('required', 'required');
            scheduleTime.setAttribute('required', 'required');
        } else {
            scheduleFields.style.display = 'none';
            scheduleDate.removeAttribute('required');
            scheduleTime.removeAttribute('required');
            scheduleDate.value = '';
            scheduleTime.value = '';
        }
    }
</script>
@endsection
