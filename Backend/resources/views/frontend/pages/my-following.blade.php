@extends('frontend.layout.master')
@section('content')
<style>
    .psychic-card .btn {
    min-width: 130px;
    height: 35px;
    font-size: 20px;
}
</style>
    <div class="ds-head-body">

        <div class="container">
            <div class="row">
                <div class="col-sm-12 expert-search-section-height-favourites">
                    <h1 class="h2 font-weight-bold colorblack mb-1 mb-md-4 mt-sm-3">
                        My Followings
                    </h1>
                    <div class="list py-4 " id="expert-list">

                        @foreach ($getfollowing['recordList'] as $astrologer)
                    <div id="ATAAIOfferTile" class="psychic-card overflow-hidden expertOnline ask-guruji"
                        data-astrologer-id="{{ $astrologer['id'] }}" data-astrologer-name="{{ $astrologer['name'] }}" >
                        <a  href="{{ route('front.astrologerDetails',  ['slug' => $astrologer['slug'] ?? ($astrologer->slug ?? ($astrologer['id'] ?? 1))]) }}" class="text-decoration-none">
                            {{-- {{ dd($astrologer) }} --}}

                        <ul class="list-unstyled d-flex mb-0">
                            <li class="mr-3 position-relative psychic-presence status-online" data-status="online">
                                <div class="psyich-img position-relative">
                                    @if ($astrologer['profileImage'])
                                    <img class="rounded-m" width="85" height="85" style="border-radius:50%;" src="{{ Str::startsWith($astrologer['profileImage'], ['http://','https://']) ? $astrologer['profileImage'] : '/' . $astrologer['profileImage'] }}" onerror="this.onerror=null;this.src='/build/assets/images/person.png';" alt="Customer image" onclick="openImage('{{ $astrologer['profileImage'] }}')" />
                                    @else
                                        <img src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/images/user-img-new.png') }}" width="85" height="85" style="border-radius:50%;">
                                    @endif
                                </div>
                                @if($astrologer['chatStatus'] == 'Busy')
                                    <div class="status-badge specific-Clr-Busy" title="Online"></div>
                                    <div class="status-badge-txt text-center specific-Clr-Busy"><span
                                        class="status-badge-txt specific-Clr-Busy tooltipex">{{ $astrologer['chatStatus'] }}</span>
                                    </div>
                                @elseif($astrologer['chatStatus'] == 'Offline' || empty($astrologer['chatStatus']))
                                    <div class="status-badge specific-Clr-Offline" title="Offline"></div>
                                    <div class="status-badge-txt text-center specific-Clr-Offline"><span
                                        class="status-badge-txt specific-Clr-Offline tooltipex">{{ $astrologer['chatStatus'] ?? 'Offline'}}</span>
                                    </div>
                                @else
                                    <div class="status-badge specific-Clr-Online" title="Online"></div>
                                    <div class="status-badge-txt text-center specific-Clr-Online"><span
                                        class="status-badge-txt specific-Clr-Online tooltipex">{{ $astrologer['chatStatus'] }}</span>
                                    </div>
                                @endif
                            </li>

                            <li class="w-100 colorblack">
                                <span class="colorblack font-weight-bold font16 mt-0 ml-0 mr-0 mb-0 p-0 text-capitalize d-block" data-toggle="tooltip" title="" style="font-weight: bold;">{{ $astrologer['name'] }}
                                    <svg id="Layer_1" fill="#495057" height="16" width="16" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 106.11 122.88"><defs><style>.cls-1{fill-rule:evenodd;}</style></defs><title>secure</title><path class="cls-1" d="M56.36,2.44A104.34,104.34,0,0,0,79.77,13.9a48.25,48.25,0,0,0,19.08,2.57l6.71-.61.33,6.74c1.23,24.79-2.77,46.33-11.16,63.32C86,103.6,72.58,116.37,55.35,122.85l-4.48,0c-16.84-6.15-30.16-18.57-39-36.47C3.62,69.58-.61,47.88.07,22l.18-6.65,6.61.34A64.65,64.65,0,0,0,28.23,13.5,60.59,60.59,0,0,0,48.92,2.79L52.51,0l3.85,2.44ZM52.93,19.3C66.46,27.88,78.68,31.94,89.17,31,91,68,77.32,96.28,53.07,105.41c-23.43-8.55-37.28-35.85-36.25-75,12.31.65,24.4-2,36.11-11.11ZM45.51,61.61a28.89,28.89,0,0,1,2.64,2.56,104.48,104.48,0,0,1,8.27-11.51c8.24-9.95,5.78-9.3,17.21-9.3L72,45.12a135.91,135.91,0,0,0-11.8,15.3,163.85,163.85,0,0,0-10.76,17.9l-1,1.91-.91-1.94a47.17,47.17,0,0,0-6.09-9.87,33.4,33.4,0,0,0-7.75-7.12c1.49-4.89,8.59-2.38,11.77.31Zm7.38-53.7c17.38,11,33.07,16.22,46.55,15,2.35,47.59-15.23,82.17-46.37,93.9C23,105.82,5.21,72.45,6.53,22.18,22.34,23,37.86,19.59,52.89,7.91Z"/></svg></span>
                                <span class="font-13 d-block color-red">
                                    <img src="{{ asset('public/frontend/homeimage/horoscope2.svg') }}" height="16" width="16" alt="">&nbsp;
                                    {{ implode(' | ', array_slice(explode(',', $astrologer['primarySkill']), 0, 3)) }}

                                </span>


                                <span class="font-13 d-block exp-language">
                                    <img src="{{ asset('public/frontend/homeimage/language-icon.svg') }}" height="16" width="16" alt="">&nbsp;
                                {{ implode(' • ',  array_slice(explode(',', $astrologer['languageKnown']), 0, 3)) }}</span>
                                <span class="font-13 d-block">  <img src="{{ asset('public/frontend/homeimage/experience-expert-icon.svg') }}" height="16" width="16" alt="">&nbsp; Experience :{{ $astrologer['experienceInYears'] }} Years</span>
                                @if ($astrologer['isFreeAvailable'] == true)
                                    <span class="font-13 font-weight-semi-bold d-flex">
                                        <span class="exprt-price"><img src="{{ asset('public/frontend/homeimage/rupee-coin-outline-icon.svg') }}" height="16" width="16" alt="">&nbsp; <del> {{ $currency['value'] }}{{ $astrologer['charge'] }}</del>/Min</span>
                                        <span class="free-badge text-uppercase color-red ml-2">Free</span>
                                    </span>
                                @else
                                    <span class="font-13 font-weight-semi-bold d-flex">
                                        <span class="exprt-price"><img src="{{ asset('public/frontend/homeimage/rupee-coin-outline-icon.svg') }}" height="16" width="16" alt="">&nbsp; {{ $currency['value'] }}{{ $astrologer['charge'] }}/Min</span>
                                    </span>
                                @endif
                            </li>
                        </ul>

                        <div class="d-flex align-items-end position-relative">
                            <div class="d-block">
                                <div class="row">
                                    <div class="psy-review-section col-12">
                                        <div>
                                            <span class="colorblack font-12 m-0 p-0 d-block">
                                                <span style="color: #b76700;font-size: 14px;font-weight: bold;">{{ $astrologer['rating'] }}</span>
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
                                        <div><span style="color: gray;font-size: 12px">{{ $astrologer['totalOrder'] }} Sessions</span></div>
                                    </div>
                                    <div class="col-3 responsiveChatBtn mt-1" >
                                        @if($astrologer['chatStatus'] == 'Busy' || $astrologer['chatStatus'] == 'Offline' || empty($astrologer['chatStatus']))
                                            <a class="btn-block btn btn-call align-items-center" style="font-size: 14px !important;" href="{{ route('front.astrologerDetails',  ['slug' => $astrologer['slug'] ?? ($astrologer->slug ?? ($astrologer['id'] ?? 1))]) }}" >
                                                <i class="fa-solid fa-circle-info mr-2"></i> <span>View</span></a>
                                        @elseif($astrologer['chat_sections'] == 0 || $Chatsection['value'] == 0)
                                            <a class="btn-block btn btn-call align-items-center disabled" href="{{ route('front.astrologerDetails',  ['slug' => $astrologer['slug'] ?? ($astrologer->slug ?? ($astrologer['id'] ?? 1))]) }}" >
                                                <i class="fa-solid fa-circle-info mr-2"></i> <span>View</span>
                                            </a>
                                        @else
                                            <a class="btn-block btn btn-call align-items-center"  role="button" href="{{ route('front.astrologerDetails',  ['slug' => $astrologer['slug'] ?? ($astrologer->slug ?? ($astrologer['id'] ?? 1))]) }}" >
                                                <i class="fa-solid fa-circle-info mr-2"></i> <span>View</span>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            </div>
                        </a>
                     </div>
                    @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
