@extends('frontend.layout.master')
@section('content')
<style>
    .pac-container:after {
        content: none !important;
    }

    .error {
        color: red;
        font-size: 12px;
        display: none; /* Hide error message initially */
    }
    .input-field:invalid {
        border-color: red;
    }
</style>
    <div class="pt-1 pb-1 bg-red d-none d-md-block onlinepuja-breadcrumb">
        <div class="container">
            <div class="row afterLoginDisplay">
                <div class="col-md-12 d-flex align-items-center">
                    <span style="text-transform: capitalize; ">
                        <span class="text-white breadcrumbs">
                            <a href="{{ route('front.home') }}" style="color:white;text-decoration:none">
                                <i class="fa fa-home font-18"></i>
                            </a>
                            <i class="fa fa-chevron-right"></i> <a href="{{ route('front.getkundali') }}"
                                style="color:white;text-decoration:none">Kundali </a>

                        </span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="onlinepuja-menu py-2 bg-pink border-bottom border-pink">
        <div class="container">
            <div class="row">
                <div class="col-sm-12">
                    <ul class="list-unstyled d-flex mb-0">
                        <li class="align-self-center">
                            <div class="text-left">
                                <h1 class="font-24">
                                    <span class="d-block cat-heading font-weight-semi-bold">Janam Kundali</span>
                                </h1>
                            </div>
                        </li>
                        <li class="compatibility d-none d-md-block">
                            <div class="text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="74.197" height="74.198"
                                    viewBox="0 0 74.197 74.198">
                                    <g data-name="Group 19594" transform="translate(.28 .063)">
                                        <path data-name="Path 25893"
                                            d="M36.819 74.135a37.1 37.1 0 1 1 37.1-37.1 37.142 37.142 0 0 1-37.1 37.1Zm0-70.671a33.572 33.572 0 1 0 33.572 33.572A33.609 33.609 0 0 0 36.819 3.464Z"
                                            fill="#ee4e5e"></path>
                                        <path data-name="Path 25894"
                                            d="M6.884 54.761a1.763 1.763 0 0 1-1.509-2.674L35.691 1.866a1.764 1.764 0 0 1 3.029.015l29.553 50.224a1.763 1.763 0 0 1-1.52 2.658Zm56.786-3.526L37.181 6.22 10.006 51.236Z"
                                            fill="#ee4e5e"></path>
                                        <path data-name="Path 25895"
                                            d="M36.43 73.059a1.771 1.771 0 0 1-1.513-.869L5.364 21.967a1.764 1.764 0 0 1 1.52-2.658h59.869a1.763 1.763 0 0 1 1.509 2.675L37.945 72.208a1.772 1.772 0 0 1-1.508.851Zm.026-5.206L63.63 22.835H9.967Z"
                                            fill="#ee4e5e"></path>
                                        <circle data-name="Ellipse 2023" cx="7.254" cy="7.254" r="7.254"
                                            transform="translate(29.565 29.782)"></circle>
                                    </g>
                                </svg>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="ds-head-populararticle bg-white cat-pages">
        <div class="container">
            <div class="row py-3">
                <div class="col-sm-12 mt-4">
                    <div class="row">
                        <div class="col-12 mb-5">
                            <h2 class="cat-heading font-24 font-weight-bold">Get Future Predictions With Free <span
                                    class="">Online Janam Kundali</span></h2>
                            <p class="pt-3 text-center  ">Welcome to our Free Kundli page! Kundli, also known as a birth chart or horoscope, is a detailed astrological chart that represents the position of celestial bodies at the time of your birth. It provides valuable insights into your personality, life events, and future prospects.

 Our Free Kundli service allows you to generate your personalized Kundli online, without any cost. Simply input your birth details, including date, time, and place of birth, and our advanced software will generate your Kundli instantly.

 Once you have your Kundli, you can explore various aspects of your life, including:<br/><br/>

 1. Personality Traits: Discover your unique personality traits based on the positions of the planets at the time of your birth. Understand your strengths, weaknesses, and potential areas of growth.<br/>
 2. Career and Education: Get insights into suitable career paths and educational pursuits based on your Kundli. Identify your natural talents and areas where you can excel.<br/>
 3. Relationships and Marriage: Understand your compatibility with potential partners or gain insights into your existing relationships. Kundli can provide valuable information about your love life and marital prospects.<br/>
 4. Health and Well-being: Learn about potential health issues or vulnerabilities based on your Kundli. Take preventive measures and make informed lifestyle choices to maintain your well-being.<br/>
 5. Finance and Wealth: Gain insights into your financial prospects and wealth accumulation potential. Kundli can provide guidance on investment opportunities and favorable periods for financial growth.<br/><br/>

 Our Free Kundli service is designed to help you unlock the mysteries of your life and make informed decisions. However, it’s important to remember that astrology is a tool for self-reflection and guidance, and ultimately, your actions and choices shape your destiny.<br/>

 Please note that while our Free Kundli service provides a comprehensive analysis, for more detailed and personalized insights, you may consider consulting with our expert astrologers.<br/>

 Unlock the secrets of your destiny with our Free Kundli service today!.</p>
                        </div>
                        <div class="col-lg-8 col-12 ">
                            <div class="mb-3 shadow-pink">
                                <div class="bg-pink  text-center font-weight-semi-bold py-1 px-3">
                                    ENTER DETAILS
                                </div>

                                <form class="px-3 font-14" method="post" id="kundliForm">

                                    <div class="row">
                                        <div class="col-12 col-md-6 py-3">
                                            <div class="form-group mb-0">
                                                <label  class="">Name&nbsp;<span
                                                        class="">*</span></label>
                                                <input class="form-control border-pink matchInTxt shadow-none"
                                                    id="Name" name="kundali[0][name]" placeholder="Enter Name"
                                                    type="text" value="" pattern="^[a-zA-Z\s]{2,50}$" title="Name should contain only letters and be between 2 and 50 characters long." required
                                                        oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 py-3">
                                            <div class="mb-0">

                                                <label  class="">Place of Birth&nbsp;<span
                                                        class="">*</span></label>
                                                <div class="input-group is-invalid">
                                                    <input autocomplete="off"
                                                        class="form-control rounded border-pink shadow-none matchInTxt ui-autocomplete-input"
                                                        id="address" name="kundali[0][birthPlace]"
                                                        placeholder="Place of Birth" type="text" required>

                                                        <input type="hidden" id="latitude" name="kundali[0][latitude]">
                                                        <input type="hidden" id="longitude" name="kundali[0][longitude]">
                                                        <input type="hidden" id="timezone" value="5.5" name="kundali[0][timezone]">
                                                    <input type="hidden" value="en" name="kundali[0][lang]">

                                                    <input type="hidden" value="false" name="is_match">


                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 py-3">
                                            <div class=" mb-0">
                                                <label class="">Birth Date&nbsp;<span
                                                        class="">*</span></label>
                                                <label class="control-label commonerror float-right "
                                                    id="dateError"></label>
                                                <input type="date" name="kundali[0][birthDate]"
                                                    class="form-control rounded border-pink shadow-none matchInTxt ui-autocomplete-input" required>

                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 py-3">
                                            <div class="form-group mb-0">
                                                <div>
                                                    <div class="position-relative" style="display:flow-root">
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <label
                                                                    class="control-label commonerror float-right  mb-0"
                                                                    id="timeError"></label>
                                                            </div>
                                                        </div>

                                                        <label class="">Birth Time&nbsp;<span
                                                                class="">*</span></label>
                                                        <input type="time" id="birthTimeBoy" name="kundali[0][birthTime]"
                                                            class="form-control rounded border-pink shadow-none matchInTxt ui-autocomplete-input" value="12:00">
                                                            <div id="birthTimeErrorBoy" class="error">Please provide a birth time or select 'Don't know birth time'.</div>

                                                        <input type="checkbox" id="dontKnowTimeBoy"> Don't know birth time.

                                                        </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 py-3">
                                            <div>
                                                <label class="">Gender&nbsp;<span
                                                        class="">*</span></label>
                                                <div class="input-group mb-0">

                                                    <select
                                                        class="form-control font-14 border-pink text-dark shadow-none matchInTxt"
                                                         name="kundali[0][gender]" required>
                                                        <option value="">Gender</option>
                                                        <option value="Female">Female</option>
                                                        <option value="Male">Male</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        @if (authcheck())
                                        @if ($getkundaliprice['isFreeSession'] == false)
                                            <div class="col-12 col-md-6 py-3">
                                                <label class="">Select Type&nbsp;<span class="">*</span></label>
                                                <select name="kundali[0][pdf_type]" onchange="updateAmount()" class="form-control font-14 border-pink text-dark shadow-none matchInTxt" id="pdf_type">
                                                    <option>Select Type</option>
                                                    <option value="basic" data-price="0">Basic (free)</option>
                                                    <option value="small" data-price="{{ $getkundaliprice['recordList']['0']['price'] }}">Small
                                                    @if($walletType == 'coin')
                                                        <img src="{{ asset($coinIcon) }}" alt="Wallet Icon" width="15">
                                                    @else
                                                        {{ $currency['value'] }}
                                                    @endif
                                                    {{ $getkundaliprice['recordList']['0']['price'] }})</option>
                                                    <option value="medium" data-price="{{ $getkundaliprice['recordList']['1']['price'] }}">Medium
                                                       @if($walletType == 'coin')
                                                        <img src="{{ asset($coinIcon) }}" alt="Wallet Icon" width="15">
                                                    @else
                                                        {{ $currency['value'] }}
                                                    @endif
                                                    {{ $getkundaliprice['recordList']['1']['price'] }})</option>
                                                    <option value="large" data-price="{{ $getkundaliprice['recordList']['2']['price'] }}">Large
                                                       @if($walletType == 'coin')
                                                        <img src="{{ asset($coinIcon) }}" alt="Wallet Icon" width="15">
                                                    @else
                                                        {{ $currency['value'] }}
                                                    @endif
                                                    {{ $getkundaliprice['recordList']['2']['price'] }})</option>
                                                </select>
                                                <input type="hidden" value="" name="amount" id="amount">
                                            </div>

                                             <div class="col-12 col-md-6 py-3 d-none" id="languageDropdown">
                                                <div>
                                                    <label class="">Pdf Language&nbsp;<span class="">*</span></label>
                                                    <div class="input-group mb-0">
                                                        <select class="form-control font-14 border-pink text-dark shadow-none matchInTxt" name="kundali[0][lang]">
                                                            <option value="en">English</option>
                                                            <option value="hi">Hindi</option>
                                                            <option value="ta">Tamil</option>
                                                            <option value="te">Telgu</option>
                                                            <option value="ka">Kannada</option>
                                                            <option value="be">Bengali</option>
                                                            <option value="ml">Malayalam</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <input type="hidden" value="0" name="amount">
                                            <input type="hidden" value="basic" name="kundali[0][pdf_type]">
                                        @endif
                                    @endif




                                        <div class="col-12 col-md-6 py-3">
                                            <div class="row">

                                                <div class="col-12 pt-md-3 text-center mt-2">
                                                    @if (authcheck())
                                                        <button class="btn btn-block btn-chat px-4 px-md-5 mb-2"
                                                            id="kundaliloaderbtn" type="button" style="display:none;"
                                                            disabled>
                                                            <span class="spinner-border spinner-border-sm" role="status"
                                                                aria-hidden="true"></span> Loading...
                                                        </button>
                                                        <button type="submit" id="showKundalibtn"
                                                            class="btn btn-block btn-chat px-4 px-md-5 mb-2">Show
                                                            Kundali</button>
                                                    @else
                                                    <a
                                                    class="btn btn-block btn-chat px-4 px-md-5 mb-2 " data-toggle="modal" data-target="#loginSignUp">Login To
                                                    View</a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </form>
                            </div>
                        </div>

                        {{-- Saved Kundali --}}
                        @if(authcheck())
                        <div class="col-lg-4  ">
                            <div class="mb-3 shadow-pink">
                                <div class="bg-pink  text-center font-weight-semi-bold py-1 px-3">
                                    Saved Reports <a href="javascript:void(0)" id="refreshkundalies"
                                        class=" float-right" title="Kundali"><svg xmlns="http://www.w3.org/2000/svg" width="20.197" height="20.198"
                                        viewBox="0 0 74.197 74.198">
                                        <g data-name="Group 19594" transform="translate(.28 .063)">
                                            <path data-name="Path 25893"
                                                d="M36.819 74.135a37.1 37.1 0 1 1 37.1-37.1 37.142 37.142 0 0 1-37.1 37.1Zm0-70.671a33.572 33.572 0 1 0 33.572 33.572A33.609 33.609 0 0 0 36.819 3.464Z"
                                                fill="#ee4e5e"></path>
                                            <path data-name="Path 25894"
                                                d="M6.884 54.761a1.763 1.763 0 0 1-1.509-2.674L35.691 1.866a1.764 1.764 0 0 1 3.029.015l29.553 50.224a1.763 1.763 0 0 1-1.52 2.658Zm56.786-3.526L37.181 6.22 10.006 51.236Z"
                                                fill="#ee4e5e"></path>
                                            <path data-name="Path 25895"
                                                d="M36.43 73.059a1.771 1.771 0 0 1-1.513-.869L5.364 21.967a1.764 1.764 0 0 1 1.52-2.658h59.869a1.763 1.763 0 0 1 1.509 2.675L37.945 72.208a1.772 1.772 0 0 1-1.508.851Zm.026-5.206L63.63 22.835H9.967Z"
                                                fill="#ee4e5e"></path>
                                            <circle data-name="Ellipse 2023" cx="7.254" cy="7.254" r="7.254"
                                                transform="translate(29.565 29.782)"></circle>
                                        </g>
                                    </svg></a>
                                </div>

                                {{-- {{dd($getkundali['recordList'])}} --}}
                                    <div id="savedKundalies"
                                        style="height: 399px; overflow-y: overlay; overflow-x: hidden; ">
                                        <div>
                                            <ul class="list-unstyled py-2">
                                                @foreach ($getkundali['recordList'] as $getkundali)
                                                    <li class="ui-menu-item border-bottom px-3 mt-2">
                                                        <div class="row mb-2">
                                                            <div class="col-10">
                                                                <div class="row">
                                                                    <div class="col-auto d-flex">
                                                                        @php
                                                                            $first_character = substr($getkundali['name'],0,1,);
                                                                        @endphp
                                                                        <span
                                                                            class="rounded-25 font-14 text-white p-1 align-self-center text-center"
                                                                            style="background-color:#5E2329;min-width:29px;">
                                                                            {{ $first_character }}
                                                                        </span>
                                                                    </div>
                                                                    <div class="col pl-1">
                                                                        <a href="{{ route('front.kundaliReport', ['kundali_id' => $getkundali['id']]) }}" class="colorblack">
                                                                            <p
                                                                                class="mb-0 font-13 font-weight-semi-bold small">
                                                                                Name : {{ $getkundali['name'] }}</p>
                                                                            <p class="mb-0 font-12">
                                                                                Dob : {{ date("d-m-Y" ,strtotime($getkundali['birthDate'])) }}</p>
                                                                            <p class="mb-0 font-12">
                                                                                Pob :  {{ $getkundali['birthPlace'] }}</p>
                                                                            <p class="mb-0 font-12">
                                                                            Created At : {{ date("d-m-Y" ,strtotime($getkundali['created_at'])) }}</p>
                                                                        </a>
                                                                    </div>
                                                                </div>

                                                            </div>
                                                            <!--<form class="deletekundaliform">-->
                                                            <!--    <input type="hidden" value="{{ $getkundali['id']}}" name="id">-->
                                                            <!--    <div class="col-2 text-center align-self-center mt-2">-->
                                                            <!--        <a class="deletekundali">-->
                                                            <!--            <i class="fa-solid fa-trash "></i>-->
                                                            <!--        </a>-->

                                                            <!--    </div>-->
                                                            <!--</form>-->
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                            </div>
                        </div>
                        @else
                       <div class="col-lg-4  ">
                            <div class="mb-3 shadow-pink d-flex" style="height: 430px;">
                                <div class="text-center p-2 align-self-center w-100"
                                    style="overflow-y: overlay; overflow-x: hidden; ">
                                    <p class="text-center font-20 text-light-pink px-4">Login to See Your Saved Kundali!</p>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>



        <div class="bg-white astrology-services">
            <div class="container pt-5">
                <div class="row">

                    <div class="col-12">

                        <h2 class="heading text-center mt-5">WHY SHOULD YOU GET YOUR JANAM KUNDALI?</h2>
                        <p>A Janam Kundali or Birth Chart is simply a way to get clarity about your future to make better
                            decisions and choices in life. It is a blueprint of the position of the planets and the stars in
                            the Universe at the time of your birth on the basis of which the predictions about your future
                            are made. There are many ways in which your Janam kundali by date of birth and time can help you
                            in your life.</p>
                        <ul>
                            <li>Make better professional decisions and career choices.</li>
                            <li>Gain better clarity about your personality, strengths and weaknesses.</li>
                            <li>Know the favorable and unfavorable time periods of your life.</li>
                            <li>Know how to make better financial choices and attract prosperity in life,</li>
                            <li>Choose the right partner for marriage with Kundali Matching.</li>
                        </ul>

                        <h2 class="heading text-center mt-5">Create Online Janam Kundali</h2>
                        <p>An online Janam Kundli is only accurate when you have exact information about your date of birth
                            and time of birth. A lot of people make the mistake of entering the incorrect birth time, which
                            results in an incorrect Kundali and predictions that are totally irrelevant.</p>
                        <p>It is easy to get a Janam Kundli online. Just enter the appropriate information and hit the
                            submit button. It dates back to the Vedic era when Kundli was used for prediction. The Kundali
                            chart we have provided you is an authentic representation of the Vedic tradition</p>



                        <div class="mb-3">

                            <div class="row pt-3">
                                <div class="col-12">
                                    <div class="bg-pink looks-1 d-flex p-2 py-3 p-sm-3 overflow-hidden position-relative">
                                        <div
                                            class="text-center d-flex font-weight-medium align-items-center w-100 px-sm-4 pr-2 pr-sm-5">
                                            <div class="px-2 w-100">
                                                <span class="d-block font-30 heading-line"><span
                                                        class="">DOSHAS</span> IN YOUR KUNDALI AFFECTING YOUR
                                                    <span class="">LIFE</span>?</span>
                                                <span class="d-none d-md-block font-22 mt-3 pt-1">Ask an {{ucfirst($professionTitle)}} now for
                                                    solutions.</span>

                                                <a href="{{ route('front.chatList') }}"
                                                    class="btn btn-chat px-3 px-sm-4 font-small-ms mt-3">Ask An {{ucfirst($professionTitle)}}
                                                    Now</a>
                                            </div>
                                        </div>
                                        <div
                                            class="looks-image ilook2 text-center position-relative align-items-center mr-md-3 mr-lg-4">
                                            <div class="looks-img-box position-relative"><img
                                                    src="{{ asset('public/frontend/onlinepujacdn/onlinepuja/web/content/images/ads/kundali-dosha-banner.png') }}">
                                            </div>
                                        </div>
                                        <span class="looks-circle size-2 tops position-absolute"></span>
                                        <span class="looks-circle size-3 filled rights position-absolute"></span>
                                        <span
                                            class="looks-circle size-1 filled bottom-0 position-absolute d-none d-sm-block"
                                            style="margin-left:11%"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <div class="container pt-5  ">
            <div class="row">
                <div class="col-sm-12">
                    <h2 class="heading text-center">Frequently Asked Questions</h2>
                    <div class="panel-group mb-5 mt-5" id="accordion" itemscope=""
                        itemtype="https://schema.org/FAQPage">
                        <div class="panel panel-default mb-3" itemscope="" itemprop="mainEntity"
                            itemtype="https://schema.org/Question">
                            <div class="panel-heading">
                                <h3 class="panel-title mb-0" itemprop="name">
                                    <a class="accordion-toggle font-weight-semi d-block py-2 colorblack font-20"
                                        data-toggle="collapse" data-parent="#accordion" href="#collapseOne_30">Why is creating a Kundli important for me?</a>
                                </h3>
                            </div>
                            <div id="collapseOne_30" class="panel-collapse collapse show" data-parent="#accordion"
                                itemscope="" itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                                <div class="panel-body px-0 px-md-3 py-4 border-top" itemprop="text">
                                    <p>Creating a Kundli is essential for understanding various aspects of your life, including your personality, strengths, weaknesses, and potential future events. It serves as a roadmap to navigate your life’s journey with better clarity. By analyzing the planetary positions at the time of your birth, a Kundli can help uncover your natural talents, suitable career paths, relationships, and even life challenges. It’s a tool that empowers self-awareness and decision-making.</p>
                                </div>
                            </div>
                        </div>

                        <!-- #2 -->
                        <div class="panel panel-default mb-3" itemscope="" itemprop="mainEntity"
                            itemtype="https://schema.org/Question">
                            <div class="panel-heading">
                                <h3 class="panel-title mb-0" itemprop="name">
                                    <a class="accordion-toggle font-weight-semi d-block py-2 collapsed colorblack font-20"
                                        data-toggle="collapse" data-parent="#accordion" href="#collapseOne_31">How can I create a Kundli online that resonates with my personal details?</a>
                                </h3>
                            </div>
                            <div id="collapseOne_31" class="panel-collapse collapse " data-parent="#accordion"
                                itemscope="" itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                                <div class="panel-body px-0 px-md-3 py-4 border-top" itemprop="text">
                                    <p>To create an accurate Kundli online, you need to provide your exact date, time, and place of birth. These details are crucial because even minor variations can change the planetary positions in your chart. Once you enter this information, the system generates a personalized birth chart that reflects your unique astrological profile. The online process is simple, efficient, and eliminates the need to manually calculate complex astrological elements.</p>
                                </div>
                            </div>
                        </div>


                        <!-- #3 -->
                        <div class="panel panel-default mb-3" itemscope="" itemprop="mainEntity"
                            itemtype="https://schema.org/Question">
                            <div class="panel-heading">
                                <h3 class="panel-title mb-0" itemprop="name">
                                    <a class="accordion-toggle font-weight-semi d-block py-2 collapsed colorblack font-20"
                                        data-toggle="collapse" data-parent="#accordion" href="#collapseOne_34">Can an online Kundli truly reflect who I am?</a>
                                </h3>
                            </div>
                            <div id="collapseOne_34" class="panel-collapse collapse " data-parent="#accordion"
                                itemscope="" itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                                <div class="panel-body px-0 px-md-3 py-4 border-top" itemprop="text">
                                Yes, an online Kundli can provide an accurate and detailed picture of your astrological identity, provided your birth details are correct. These charts are based on precise Vedic astrology principles, which have been trusted for centuries. The insights offered often resonate deeply with an individual’s life experiences, from career and relationships to personal challenges. While interpretations can vary, the fundamental calculations are accurate and reliable.
                                </div>
                            </div>
                        </div>

                         <!-- #4 -->
                         <div class="panel panel-default mb-3" itemscope="" itemprop="mainEntity"
                            itemtype="https://schema.org/Question">
                            <div class="panel-heading">
                                <h3 class="panel-title mb-0" itemprop="name">
                                    <a class="accordion-toggle font-weight-semi d-block py-2 collapsed colorblack font-20"
                                        data-toggle="collapse" data-parent="#accordion" href="#collapseOne_35">Do I need to know astrology to understand my Kundli?</a>
                                </h3>
                            </div>
                            <div id="collapseOne_35" class="panel-collapse collapse " data-parent="#accordion"
                                itemscope="" itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                                <div class="panel-body px-0 px-md-3 py-4 border-top" itemprop="text">
                                Absolutely not! Online Kundli tools are designed to be user-friendly and accessible to everyone. You don’t need any prior knowledge of astrology. Most tools provide detailed explanations and summaries of the key elements in your chart, such as your Lagna (ascendant), planetary placements, and life periods. These interpretations are presented in simple terms, making it easy for anyone to explore and understand their astrological profile.                                </div>
                            </div>
                        </div>

                         <!-- #5 -->
                         <div class="panel panel-default mb-3" itemscope="" itemprop="mainEntity"
                            itemtype="https://schema.org/Question">
                            <div class="panel-heading">
                                <h3 class="panel-title mb-0" itemprop="name">
                                    <a class="accordion-toggle font-weight-semi d-block py-2 collapsed colorblack font-20"
                                        data-toggle="collapse" data-parent="#accordion" href="#collapseOne_36">What specific benefits will I gain from creating my Kundli?</a>
                                </h3>
                            </div>
                            <div id="collapseOne_36" class="panel-collapse collapse " data-parent="#accordion"
                                itemscope="" itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                                <div class="panel-body px-0 px-md-3 py-4 border-top" itemprop="text">
                                Creating your Kundli offers numerous benefits. It can help you understand your personality traits, identify your strengths, and discover areas where improvement is needed. You can also gain insights into your career path, financial opportunities, and compatibility with others. Many people use their Kundli to plan important life events, such as marriage or career changes, as it provides a broader perspective on the timing and likelihood of success.                            </div>
                            </div>
                        </div>

                         <!-- #6 -->
                         <div class="panel panel-default mb-3" itemscope="" itemprop="mainEntity"
                            itemtype="https://schema.org/Question">
                            <div class="panel-heading">
                                <h3 class="panel-title mb-0" itemprop="name">
                                    <a class="accordion-toggle font-weight-semi d-block py-2 collapsed colorblack font-20"
                                        data-toggle="collapse" data-parent="#accordion" href="#collapseOne_35">Is creating a Kundli online really free, and are there any hidden costs?</a>
                                </h3>
                            </div>
                            <div id="collapseOne_35" class="panel-collapse collapse " data-parent="#accordion"
                                itemscope="" itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                                <div class="panel-body px-0 px-md-3 py-4 border-top" itemprop="text">
                                Yes, the basic process of generating a Kundli online is completely free. This means you can access your birth chart, planetary positions, and basic interpretations without any charges. However, some platforms may offer optional premium services, such as detailed reports or personalized consultations, which you can choose to purchase if needed. Rest assured, the free version itself provides significant value.                            </div>
                        </div>
                </div>
            </div>
        </div>
    </div>

     <!-- Fullscreen Loader -->
<div id="overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 9999;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
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
    var input = document.getElementById('address');
    var originLatitude = document.getElementById('latitude');
    var originLongitude = document.getElementById('longitude');
    var originTimezone = document.getElementById('timezone');

    var originAutocomplete = new google.maps.places.Autocomplete(input);

    originAutocomplete.addListener('place_changed', function(event) {
        var place = originAutocomplete.getPlace();
        if (place.hasOwnProperty('place_id')) {
            if (!place.geometry) {
                return;
            }
            originLatitude.value = place.geometry.location.lat();
            originLongitude.value = place.geometry.location.lng();
            getTimezone(originLatitude.value, originLongitude.value);
        } else {
            service.textSearch({
                query: place.name
            }, function(results, status) {
                if (status == google.maps.places.PlacesServiceStatus.OK) {
                    originLatitude.value = results[0].geometry.location.lat();
                    originLongitude.value = results[0].geometry.location.lng();
                    getTimezone(originLatitude.value, originLongitude.value);
                }
            });
        }
    });

    function getTimezone(lat, lng) {
        var timestamp = Math.floor(Date.now() / 1000);
        var timezoneApiUrl = `https://maps.googleapis.com/maps/api/timezone/json?location=${lat},${lng}&timestamp=${timestamp}&key={{ $apikey['value'] }}`;

        fetch(timezoneApiUrl)
            .then(response => response.json())
            .then(data => {
                if (data.status === "OK") {
                    var rawOffsetHours = data.rawOffset / 3600;
                    var dstOffsetHours = data.dstOffset / 3600;
                    var totalOffset = rawOffsetHours + dstOffsetHours;
                    originTimezone.value = totalOffset;
                } else {
                    console.error("Timezone API error:", data.status);
                }
            })
            .catch(error => console.error("Error fetching timezone:", error));
    }
</script>

    <script>
        function updateAmount() {
            var selectElement = document.getElementById('pdf_type');
            var selectedOption = selectElement.options[selectElement.selectedIndex];
            var price = selectedOption.getAttribute('data-price');
            document.getElementById('amount').value = price;
        }

        $(document).ready(function() {

            function showLoader() {
                $('#overlay').fadeIn();
            }

            function hideLoader() {
                $('#overlay').fadeOut();
            }

            $('#showKundalibtn').click(function(e) {

                var birthTimeInputBoy = document.getElementById("birthTimeBoy");
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


                var place = originAutocomplete.getPlace();
                if (!place || !place.geometry) {
                    alert("Please select a birthplace from the dropdown.");
                    return; // Stop further execution
                }

                var form = document.getElementById('kundliForm');
                if (form.checkValidity() === false) {
                   form.reportValidity();
                   return;
                }

                // $('#showKundalibtn').hide();
                // $('#kundaliloaderbtn').show();
                // setTimeout(function() {
                //     $('#showKundalibtn').show();
                //     $('#kundaliloaderbtn').hide();
                // }, 10000);
                showLoader();

                @php
                    use Symfony\Component\HttpFoundation\Session\Session;
                    $session = new Session();
                    $token = $session->get('token');

                @endphp
                var formData = $('#kundliForm').serialize();
                // console.log(formData);

                $.ajax({
                    url: "{{ route('api.addKundali', ['token' => $token]) }}",
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        toastr.success('Form Submitted Successfully');
                        console.log(response.recordList[0].id);
                        let kundaliId = response.recordList[0].id;
                        let url = "{{ route('front.kundaliReport', ['kundali_id' => '']) }}";

                        window.location.href = url + kundaliId;

                        console.log(url);
                    },
                    error: function(xhr, status, error) {
                        // toastr.error('Something Went Wrong');
                        try {
                            // Parse the error response
                            var response = JSON.parse(xhr.responseText);

                        if(response.message=='Insufficient funds in the wallet.'){
                            window.location.href="{{ route('front.walletRecharge') }}";
                        }
                            // Check if the error response contains validation messages
                            if (response.error) {
                                var errorMessages = response.error;

                                // Loop through the error messages and display them
                                for (var field in errorMessages) {
                                    if (errorMessages.hasOwnProperty(field)) {
                                        toastr.error(errorMessages[field][0]); // Show the first error for each field
                                    }
                                }
                            } else {
                                // If no specific validation error, show a generic error
                                toastr.error('Something Went Wrong');
                            }
                        } catch (e) {
                            toastr.error('An unexpected error occurred. Please try again later.');
                        }
                    },complete: function () {
                        hideLoader();
                    }
                });
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            $('.deletekundali').click(function(e) {
                e.preventDefault();

                Swal.fire({
                    title: 'Are you sure you want to delete ',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        var formData = $(this).closest('.deletekundaliform').serialize();
                        // console.log(formData);
                        // return false;

                        $.ajax({
                            url: '{{ route("api.deleteKundali",['token' => $token]) }}',
                            type: 'POST',
                            data: formData,

                            success: function(response) {
                                toastr.success('Kundali Deleted Successfully');
                                window.location.reload();
                            },
                            error: function(xhr, status, error) {
                                var errorMessage = JSON.parse(xhr.responseText).error.paymentMethod[0];
                                toastr.error(errorMessage);
                            }
                        });
                    }
                });
            });
        });


        $(document).ready(function() {
        // Function to toggle language dropdown visibility
        function toggleLanguageDropdown() {
            const selectedType = $('#pdf_type').val();
            if (selectedType === 'basic' || selectedType === 'Select Type') {
                $('#languageDropdown').addClass('d-none'); // Hide the language dropdown
            } else {
                $('#languageDropdown').removeClass('d-none'); // Show the language dropdown
            }
        }

        // Initial check on page load
        toggleLanguageDropdown();

        // Bind the function to the change event of the PDF type dropdown
        $('#pdf_type').on('change', function() {
            toggleLanguageDropdown();
        });
    });
    </script>
@endsection
