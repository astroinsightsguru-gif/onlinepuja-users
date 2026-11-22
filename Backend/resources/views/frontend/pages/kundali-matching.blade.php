@extends('frontend.layout.master')
@section('content')
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
</style>
<div class="pt-1 pb-1 d-none d-md-block onlinepuja-breadcrumb">
    <div class="container">
        <div class="row afterLoginDisplay">
            <div class="col-md-12 d-flex align-items-center">
                <span style="text-transform: capitalize; ">
                    <span class="text-white breadcrumbs">
                        <a href="{{ route('front.home') }}" style="color:white;text-decoration:none">
                            <i class="fa fa-home font-18"></i>
                        </a>
                        <i class="fa fa-chevron-right"></i> <a href="{{ route('front.kundaliMatch') }}"
                            style="color:white;text-decoration:none">Kundali Matching</a>

                    </span>
                </span>
            </div>
        </div>
    </div>
</div>

<div class="onlinepuja-menu pt-2 pb-md-3 border-bottom border-pink">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 bodyHeader">
                <ul class="list-unstyled d-flex mb-0">
                    <li class="align-self-center">
                        <div class="text-left">
                            <h1 class="font-24">

                                <span class="d-block cat-heading font-weight-semi-bold">

                                    Kundali Matching
                                </span>
                            </h1>
                        </div>
                    </li>
                    <li class="compatibility d-md-block">
                        <div class="text-center">
                            <svg data-name="Group 31011" xmlns="http://www.w3.org/2000/svg" width="82.833"
                                height="76.006" viewBox="0 0 82.833 76.006">
                                <defs>
                                    <clipPath id="a">
                                        <path data-name="Rectangle 2946" fill="none" d="M0 0h82.833v76.006H0z">
                                        </path>
                                    </clipPath>
                                </defs>
                                <g data-name="Group 31010" clip-path="url(#a)">
                                    <path data-name="Path 54557"
                                        d="M47.718 67.52c9.3-11.791 10.03-23.531 2.145-35.337A35.846 35.846 0 0 0 44 35.843a20.4 20.4 0 0 1 2.128 21.694A20.669 20.669 0 0 1 7.145 45.394a20.025 20.025 0 0 1 11.292-15.586 19.494 19.494 0 0 1 12.41-1.879 1.614 1.614 0 0 0 1.548-.53c1.538-1.466 3.158-2.846 4.748-4.258.165-.146.337-.283.546-.457a26.583 26.583 0 0 0-12.936-1.8A26.476 26.476 0 0 0 8.8 28.173C1.457 35.235-1.335 43.895.591 53.86a26.012 26.012 0 0 0 12.9 18.146c8.818 5.132 17.89 5.286 27.007.689a34.918 34.918 0 0 0 7.217-5.179">
                                    </path>
                                    <path data-name="Path 54558"
                                        d="M82.668 45.458a43.305 43.305 0 0 0-.832-4.357q-4.169-14.157-18.143-19c-.219-.076-.428-.181-.732-.31l9.511-11.419C70.233 7 68.031 3.68 65.795.377a1.1 1.1 0 0 0-.8-.362q-9.789-.03-19.578.007a1.29 1.29 0 0 0-.879.513c-.83 1.162-1.6 2.367-2.391 3.555l-4.185 6.278 9.544 11.459c-.548.186-.976.325-1.4.476a27.606 27.606 0 0 0-18.255 29.552 26.531 26.531 0 0 0 4.844 12.354.8.8 0 0 0 .687.272 16.029 16.029 0 0 0 5.578-3.286c-7.881-10.316-4.116-22.72 2.464-28.148a26.687 26.687 0 0 1 6.215-3.929 20.68 20.68 0 1 1 4.778 39.729 16.965 16.965 0 0 1-1 2.658c-.527 1.163-1.06 2.32-1.57 3.49l-.174.4a27.31 27.31 0 0 0 8.351.408 27.528 27.528 0 0 0 23.829-20.382c.452-1.789.659-3.639.979-5.462V46.4c-.055-.313-.106-.627-.164-.94M65.215 5.688l3.063 4.625h-4.766l1.7-4.625m-2.891-2.165-1.653 4.491-2.521-4.491Zm-7.11 1.825 2.766 4.949h-5.552l2.786-4.949M52.279 3.51l-2.521 4.477-1.646-4.477Zm-7.054 2.175 1.693 4.608h-4.761l3.067-4.608m4.958 13.962-4.837-5.811c.986 0 1.78-.02 2.571.017a.642.642 0 0 1 .436.366c.674 1.774 1.322 3.558 1.976 5.339l-.146.089m8.1-5.158c-.683 1.87-1.378 3.735-2.051 5.609-.148.412-.276.74-.832.636-.463-.086-.954.164-1.2-.587-.685-2.09-1.487-4.142-2.267-6.273h6.512c-.059.229-.093.428-.162.616m1.793 5.14c.668-1.809 1.327-3.621 2.019-5.421a.7.7 0 0 1 .513-.359c.75-.04 1.5-.017 2.481-.017l-4.864 5.85-.149-.054"
                                        fill="#ee4e5e"></path>
                                </g>
                            </svg>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<div class="ds-head-populararticle cat-pages">
    <div class="container">
        <div class="row py-3">
            <div class="col-sm-12 mt-4">
                <div class="row">
                    <div class="col-12 mb-4">
                        <h2 class="cat-heading-match font-weight-bold">Kundli Matching | Kundali Match for Marriage |
                            Horoscope Matching</h2>
                        <div>
                            <p>Match Making - Kundli Milan & Gun Milan to Check Possibilities of Marriage:<br /><br />
                                Kundli milan or kundali matching is an important consideration to make when you decide to get married. Kundli matching, also called Gun matching or Horoscope matching is the first step towards marriage when the parents decide to match the kundlis of the girl and the boy to ensure the couple is compatible. The gun milan exercise has been a part of India's culture for 1000s of years now and continues to be so.<br />

                                So, if you too are the lucky one who is planning to get married, and hence looking for a horoscope matching with someone you have started liking, then Myastro can help you. The Kundali milan online software on Myastro has been prepared by the top astrologers of Myastro. The software caters to the free Kundli milan needs of the individuals and gives you insights; such as the number of guns matching for the girl and the boy, what they are compatible in, what their future would be like if they get married, and so much more. The online gun milan software can save you time and the hassle of going out to look for an astrologer to get the gun milan exercise done. Also, in case you have any doubts about the free kundali milan offered by Myastro, you can always connect with the astrologers on board and get those doubts sorted for yourself.<br /></p>
                        </div>
                    </div>
                    <div class="col-12 mb-4">
                        <h2 class="text-center font-24 font-weight-bold">Enter Details to Get Free Online Kundali
                            Matching Report For Marriage</h2>
                        <div>
                            <p class="pt-3 text-center">Anytime Astro is a premium online horoscope-matching site that
                                can help you check Kundali Milan by name and date of birth. Here, the team of expert
                                {{$professionTitle}}s analyze the compatibility of both the partners and present accurate results
                                based upon the Ashtakoots or eight categories considered to check the var vadhu gun
                                milan.
                            </p>
                            <p class="text-center">So, what keeps you waiting, check your marriage compatibility by
                                entering below the details of both partners, such as name, birth date, birth time, and
                                birthplace for horoscope matching by date of birth.
                            </p>
                        </div>
                    </div>
                    <div class="col-12 col-md-12">
                        <div class="show-shadow-rounded-main mb-3 h-100">
                            <h3 class="font-16 text-center py-2 font-weight-bold d-md-block d-none">
                                ENTER DETAILS</h3>
                            <form class="px-3 font-12" id="kundalimatchForm" autocomplete="off">
                                <div class="row">
                                    <div class="titleDifferent d-md-none d-block">
                                        <h4 class=" text-center font-weight-bold py-1 px-3 mt-2">
                                            Enter Boy's details
                                        </h4>
                                    </div>
                                    <div class="col-12 col-md-6 py-3 show-shadow-rounded">
                                        <div class="form-group">
                                            <label class="font-weight-semi-bold">Boy Name&nbsp;<span
                                                    class="">*</span></label>
                                            <input class="form-control border-pink" data-val="true" name="kundali[0][name]" placeholder="Enter Name" type="text"
                                                value="" pattern="^[a-zA-Z\s]{2,50}$" title="Name should contain only letters and be between 2 and 50 characters long." required
                                                oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
                                        </div>
                                        <div class=" mb-3">
                                            <label class="font-weight-semi-bold">Birth Date&nbsp;<span
                                                    class="">*</span></label>
                                            <input type="date" name="kundali[0][birthDate]" class="form-control rounded border-pink shadow-none matchInTxt ui-autocomplete-input" required>

                                        </div>
                                        <div class="form-group">
                                            <label class="font-weight-semi-bold">Birth Time&nbsp;<span
                                                    class="">*</span></label>
                                            <input type="time" id="birthTimeBoy" name="kundali[0][birthTime]" class="form-control rounded border-pink shadow-none matchInTxt ui-autocomplete-input">
                                            <div id="birthTimeErrorBoy" class="error">Please provide a birth time or select 'Don't know birth time'.</div>

                                        </div>
                                        <div class="form-group">
                                            <input type="checkbox" id="dontKnowTimeBoy"> Don't know birth time.
                                        </div>
                                        <div class="mb-3">
                                            <label class="font-weight-semi-bold">Place of
                                                Birth&nbsp;<span class="">*</span></label>
                                            <div class="input-group is-invalid">
                                                <input class="form-control border-pink ui-autocomplete-input"
                                                    placeholder="Enter City" id="boyaddress" name="kundali[0][birthPlace]" type="text" autocomplete="off" required>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="font-weight-semi-bold">Type&nbsp;<span class="">*</span></label>
                                            <select name="kundali[0][match_type]" class="form-control font-14 border-pink text-dark shadow-none matchInTxt" required>
                                                <option value="">Select Type</option>
                                                <option value="North">North</option>
                                                <option value="South">South</option>
                                            </select>
                                        </div>

                                        <input type="hidden" value="Male" name="kundali[0][gender]">
                                        <input type="hidden" id="boylatitude" name="kundali[0][latitude]">
                                        <input type="hidden" id="boypdftype" name="kundali[0][pdf_type]" value="basic">
                                        <input type="hidden" id="boylongitude" name="kundali[0][longitude]">
                                        <input type="hidden" id="boytimezone" name="kundali[0][timezone]" value="5.5">
                                        <input type="hidden" value="en" name="kundali[0][lang]">
                                        <input type="hidden" value="1" name="kundali[0][forMatch]">
                                        <input type="hidden" value="true" name="is_match">

                                        <input type="hidden" value="0" name="amount">
                                    </div>

                                    <div class="titleDifferent d-md-none d-block mt-4">
                                        <h4 class=" text-center font-weight-bold py-1 px-3 mt-2">
                                            Enter Girl's details
                                        </h4>
                                    </div>
                                    <div class="col-12 col-md-6 py-3 show-shadow-rounded">
                                        <div class="form-group">

                                            <label class="font-weight-semi-bold">Girl Name&nbsp;<span class="">*</span></label>
                                            <input class="form-control border-pink" name="kundali[1][name]"
                                                placeholder="Enter Name" type="text" value="" pattern="^[a-zA-Z\s]{2,50}$" title="Name should contain only letters and be between 2 and 50 characters long." required
                                                oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
                                        </div>
                                        <div class=" mb-3">
                                            <label class="font-weight-semi-bold">Birth Date&nbsp;<span
                                                    class="">*</span></label>
                                            <input type="date" name="kundali[1][birthDate]" class="form-control rounded border-pink shadow-none matchInTxt ui-autocomplete-input" required>

                                        </div>
                                        <div class="form-group">
                                            <label class="font-weight-semi-bold">Birth Time&nbsp;<span
                                                    class="">*</span></label>
                                            <input type="time" id="birthTimeGirl" name="kundali[1][birthTime]" class="form-control rounded border-pink shadow-none matchInTxt ui-autocomplete-input">
                                            <div id="birthTimeErrorGirl" class="error">Please provide a birth time or select 'Don't know birth time'.</div>
                                        </div>
                                        <div class="form-group">
                                            <input type="checkbox" id="dontKnowTimeGirl"> Don't know birth time.
                                        </div>
                                        <div class="mb-3">
                                            <label class="font-weight-semi-bold">Place of
                                                Birth&nbsp;<span class="">*</span></label>
                                            <input class="form-control border-pink ui-autocomplete-input" id="girladdress" name="kundali[1][birthPlace]" placeholder="Enter City" type="text" autocomplete="off" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="font-weight-semi-bold">Type&nbsp;<span class="">*</span></label>
                                            <select name="kundali[1][match_type]" class="form-control font-14 border-pink text-dark shadow-none matchInTxt" required>
                                                <option value="">Select Type</option>
                                                <option value="North">North</option>
                                                <option value="South">South</option>
                                            </select>
                                        </div>

                                        <input type="hidden" value="Female" name="kundali[1][gender]">
                                        <input type="hidden" id="girllatitude" name="kundali[1][latitude]">
                                        <input type="hidden" id="girlpdftype" name="kundali[1][pdf_type]" value="basic">
                                        <input type="hidden" id="girllongitude" name="kundali[1][longitude]">
                                        <input type="hidden" id="girltimezone" name="kundali[1][timezone]" value="5.5">
                                        <input type="hidden" value="en" name="kundali[1][lang]">
                                        <input type="hidden" value="1" name="kundali[1][forMatch]">


                                        <div class="row">
                                            <div class="col-12 col-md-12 pt-3 text-center text-md-right">
                                                <div class="">
                                                    @if (authcheck())
                                                    <button class="btn btn-chat px-5 my-2"
                                                        id="kundalimatchloaderbtn" type="button" style="display:none;"
                                                        disabled>
                                                        <span class="spinner-border spinner-border-sm" role="status"
                                                            aria-hidden="true"></span> Loading...
                                                    </button>
                                                    <button type="submit" id="showKundalimatchbtn" class="btn btn-chat px-5 my-2">Get
                                                        Report</button>
                                                    @else
                                                    <button type="submit" disabled
                                                        class="btn  btn-chat  px-md-5 mb-2">Login To
                                                        View</button>
                                                    @endif
                                                </div>
                                            </div>
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

<div class="ds-head-populararticle cat-pages">
    <div class="container">
        <div class="row py-3">
            <div class="col-12">
                <h2 class="text-center font-24 font-weight-bold">Kundali Matching Analysis</h2>
                <div>
                    <p class="pt-3 text-center">Got the Patrika Matching analysis, but not sure about what it means.
                        Rest assured, connect with expert{{$professionTitle}}s instantly over call or chat to ensure minute
                        details about your compatibility and also receive recommendations to fix any issues that might
                        hinder in marital life.</p>
                    <p class="text-center">Connect with {{$professionTitle}}s now!</p>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="astrology-services">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h2 class="heading text-center">Online Kundali Matching Process (How it works?)</h2>
            </div>
            <div class="col-12">
                <p>The process for Kundli Milan for marriage works as follows -</p>
                <p><b>Step 1:</b> Enter the details of both partners, such as their name, birthdate, birthplace, and
                    birth time. Make sure you enter the correct details for an accurate Janam Kundli matching.</p>
                <p><b>Step 2:</b> The system will generate a horoscope matching for marriage based on the details
                    provided.</p>
                <p><b>Step 3:</b> Gun Milan by name and other birth-related details are analyzed for both potential
                    partners by the software. The compatibility score is generated based on eight factors, namely Varna,
                    Vashya, Tara, Yoni, Graha Maitri, Gana, Bhakoot, and Nadi.</p>
                <p><b>Step 4:</b> Based on the scores of each guna, the compatibility gets calculated. The higher the
                    score, the higher the compatibility, and vice versa. However, other factors are also considered
                    while analyzing, which can be done by expert {{ucfirst($professionTitle)}}s.</p>
                <p><b>Step 5:</b> The system then produces a detailed analysis concerning each factor and offers
                    precautions or recommendations if required.</p>
                <p><b>Step 6:</b> It is recommended to consult expert {{ucfirst($professionTitle)}}s for further clarifications and
                    insights.</p>
            </div>
            <div class="col-12">
                <h2 class="heading text-center">Benefits of Online Kundali Matching or Online Horoscope Matching</h2>
            </div>
            <div class="col-12">
                <p>The online free kundali matching site provides the following benefits - </p>
                <ul>
                    <li>Check your compatibility anytime, anywhere! You no longer need to book an appointment with the
                        {{ucfirst($professionTitle)}}s when you can do online kundali matching for marriage.
                    </li>
                    <li>Online kundli matching for marriage helps you save a lot of time and effort, as you don't need
                        to travel to meet the {{$professionTitle}}s traditionally in person.</li>
                    <li>Various online Kundali matching apps offer personalized consultation services, too, and give
                        clarity on compatibility concerns.</li>
                    <li>The online Janam Patrika milan can deliver results in no time, which can present a great help
                        considering arranged marriages. </li>
                    <li>The free kundali matching presents a detailed analysis of all the compatibility factors, thus
                        helping to make informed decisions. </li>
                    <li>Even the best kundli matching apps seem more affordable than the customary methods, thus making
                        them accessible to a wide range of populations.</li>
                    <li>Lesser chances of errors as online free kundali matching uses algorithms established to
                        eliminate human biases and provide transparency.</li>
                </ul>
            </div>
            <div class="mb-3">

                <div class="">
                    <div class="col-12">
                        {{-- <div class="bg-gray looks-1 d-flex p-2 py-3 p-sm-3 overflow-hidden position-relative">
                                <div class="text-center d-flex align-items-center w-100 px-sm-4 pr-2 pr-sm-5">
                                    <div class="px-2 px-xl-5 w-100">
                                        <span class="d-block font-30 heading-line px-xl-5 mx-xl-4">ARE YOU <strong
                                                class="">GETTING MARRIED</strong> TO THE RIGHT PERSON?</span>
                                        <span class="d-none d-md-block font-22 mt-3">Get answers to all your questions
                                            right here.</span>

                                        <a href="{{ route('front.chatList') }}"
                        class="btn btn-chat px-3 px-sm-4 font-small-ms mt-3">Ask An {{ucfirst($professionTitle)}} Now</a>
                    </div>
                </div>
                <div class="looks-image ilook2 text-center position-relative align-items-center mr-md-3 mr-lg-4">
                    <div class="looks-img-box position-relative"><img
                            src="{{ asset('public/frontend/onlinepujacdn/onlinepuja/web/content/images/ads/indian-bride-ads.png') }}">
                    </div>
                </div>
                <span class="looks-circle size-2 tops position-absolute"></span>
                <span class="looks-circle size-3 filled rights position-absolute"></span>
                <span class="looks-circle size-1 filled bottom-0 position-absolute d-none d-sm-block"
                    style="margin-left:11%"></span>
            </div> --}}
            <div class="looks-1 d-flex p-2 py-3 p-sm-3 overflow-hidden position-relative flex-column-reverse flex-sm-row">
                <!-- Text and Button Section -->
                <div class="text-center d-flex align-items-center w-100 px-sm-4 pr-2 pr-sm-5">
                    <div class="px-2 px-xl-5 w-100">
                        <span class="d-block font-30 heading-line px-xl-5 mx-xl-4">ARE YOU <strong class="">GETTING MARRIED</strong> TO THE RIGHT PERSON?</span>
                        <span class="d-none d-md-block font-22 mt-3">Get answers to all your questions right here.</span>

                        <a href="{{ route('front.chatList') }}" class="btn btn-chat px-3 px-sm-4 font-small-ms mt-3">Ask An {{ ucfirst($professionTitle) }} Now</a>
                    </div>
                </div>

                <!-- Image Section -->
                <div class="looks-image ilook2 text-center position-relative align-items-center mr-md-3 mr-lg-4">
                    <div class="looks-img-box position-relative">
                        <img src="{{ asset('public/frontend/onlinepujacdn/onlinepuja/web/content/images/ads/indian-bride-ads.png') }}" alt="Indian Bride Image">
                    </div>
                </div>

                <!-- Circles (Positioned for styling) -->
                <span class="looks-circle size-2 tops position-absolute"></span>
                <span class="looks-circle size-3 filled rights position-absolute"></span>
                <span class="looks-circle size-1 filled bottom-0 position-absolute d-none d-sm-block" style="margin-left:11%"></span>
            </div>

        </div>
    </div>


</div>
<div class="col-12">
    <h2 class="heading text-center mt-5">Finding the Right Partner Through Kundli Matching</h2>
</div>
<div class="col-12 mb-5">
    <p>The Hindu culture has always favored Kundali matching by date of birth to check the compatibility
        among the prospective partners. Let us explore the reasons why Kundli matching helps to find an
        ideal partner for having a successful and lasting marriage - </p>
    <ul>
        <li>The temperament, health, financials, intellect, and family of partners can be evaluated by
            horoscope matching by name and other birth details.</li>
        <li>In the Kundali Milan by date of birth, the eight vital aspects are evaluated by considering
            behavior, personality, and spirituality.</li>
        <li>A higher gun milan means stronger compatibility and vice between the partners. </li>
        <li>The Kundali matching by name and other birth details provides an analysis of more than just the
            compatibility score, such as the potential strengths and weaknesses.</li>
        <li>Based on the results, one can consult {{$professionTitle}}s to seek personalized guidance, yet clarifying
            concerns. </li>
    </ul>
</div>
</div>
</div>
</div>
@endsection

@section('scripts')
@php
$rawApiKey = \Illuminate\Support\Facades\DB::table('systemflag')->where('name', 'googleMapApiKey')->value('value') ?? '';
$apikey = ['value' => $rawApiKey];
$apiKey = $rawApiKey;
@endphp
<script src="https://maps.googleapis.com/maps/api/js?key={{ $apiKey }}&libraries=places">
</script>

<script>
    // var autocomplete;

    function initializeAutocomplete(inputId, latitudeId, longitudeId) {
        var input = document.getElementById(inputId);
        var latitude = document.getElementById(latitudeId);
        var longitude = document.getElementById(longitudeId);

        const autocomplete = new google.maps.places.Autocomplete(input);


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
    initializeAutocomplete('boyaddress', 'boylatitude', 'boylongitude');
    initializeAutocomplete('girladdress', 'girllatitude', 'girllongitude');
</script>


<script>
    $(document).ready(function() {
        $('#showKundalimatchbtn').click(function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Warning!',
                text: "you can't generate the kundali matching in demo",
                icon: 'warning',
                confirmButtonColor: '#ee4e5e',
                confirmButtonText: 'OK'
            });
            return;

            var birthTimeInputBoy = document.getElementById("birthTimeBoy");
            var dontKnowTimeRadioBoy = document.getElementById("dontKnowTimeBoy");
            const birthTimeErrorBoy = document.getElementById('birthTimeErrorBoy');


            var birthTimeInputGirl = document.getElementById("birthTimeGirl");
            var dontKnowTimeRadioGirl = document.getElementById("dontKnowTimeGirl");
            const birthTimeErrorGirl = document.getElementById('birthTimeErrorGirl');

            if (!birthTimeInputBoy.value && !dontKnowTimeRadioBoy.checked) {
                birthTimeErrorBoy.style.display = 'block';
                birthTimeInputBoy.style.borderColor = 'red';
                e.preventDefault(); // Prevent form submission
                return;
            } else {
                birthTimeErrorBoy.style.display = 'none';
                birthTimeInputBoy.style.borderColor = '';
            }

            if (!birthTimeInputGirl.value && !dontKnowTimeRadioGirl.checked) {
                birthTimeErrorGirl.style.display = 'block';
                birthTimeInputGirl.style.borderColor = 'red';
                e.preventDefault(); // Prevent form submission
                return;
            } else {
                birthTimeErrorGirl.style.display = 'none';
                birthTimeInputGirl.style.borderColor = '';
            }

            e.preventDefault();

            // var place = autocomplete.getPlace();
            // if (!place || !place.geometry) {
            //     alert("Please select a birthplace from the dropdown.");
            //     return; // Stop further execution
            // }

            var form = document.getElementById('kundalimatchForm');
            if (form.checkValidity() === false) {
                form.reportValidity();
                return;
            }

            $('#showKundalimatchbtn').hide();
            $('#kundalimatchloaderbtn').show();

            @php
            use Symfony\Component\HttpFoundation\Session\Session;
            $session = new Session();
            $token = $session->get('token');
            @endphp
            var formData = $('#kundalimatchForm').serialize();
            // console.log(formData);

            $.ajax({
                url: "{{ route('api.addKundali', ['token' => $token]) }}",
                type: 'POST',
                data: formData,
                success: function(response) {
                    toastr.success('Form Submitted Successfully');
                    $('#showKundalimatchbtn').show();
                    $('#kundalimatchloaderbtn').hide();

                    var maleKundliId = response.recordList[0].id;
                    var femaleKundliId = response.recordList[1].id;

                    var url = "{{ route('front.kundaliMatchReport') }}?male_kundli_id=" + maleKundliId + "&female_kundli_id=" + femaleKundliId;
                    window.location.href = url;
                },

                error: function(xhr, status, error) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        console.log(response);
                        // Check if the error is related to authorization
                        if (xhr.status === 401) {
                            toastr.error(response.error || "Unauthorized access. Please log in.");
                        } else {
                            toastr.error(response.message || "An unexpected error occurred. Please try again later or select location from dropdown.");
                        }
                    } catch (e) {
                        toastr.error("An unexpected error occurred. Please try again later or select location from dropdown.");
                    }

                    $('#showKundalimatchbtn').show();
                    $('#kundalimatchloaderbtn').hide();
                }
            });
        });
    });
</script>

@endsection
