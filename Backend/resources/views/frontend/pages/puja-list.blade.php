@extends('frontend.layout.master')

@section('content')
<style>
    /* Responsive Grid Layout */
    .pujalist-show {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 24px;
        justify-content: center;
        margin-top: 30px !important;
        margin-bottom: 30px !important;
    }

    /* Premium Puja Card style */
    .puja-card-custom {
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.06);
        border-radius: 20px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        height: 100%;
        transition: transform 0.35s cubic-bezier(0.165, 0.84, 0.44, 1), box-shadow 0.35s cubic-bezier(0.165, 0.84, 0.44, 1);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.015);
        padding: 0;
        position: relative;
    }

    .puja-card-custom:hover {
        transform: translateY(-6px);
        box-shadow: 0 16px 36px rgba(64, 14, 47, 0.08);
    }

    .puja-card-media {
        position: relative;
        width: 100%;
        height: 200px;
        overflow: hidden;
        background: #000000;
    }

    .puja-card-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
        cursor: pointer;
    }

    .puja-card-custom:hover .puja-card-img {
        transform: scale(1.05);
    }

    .puja-card-body {
        padding: 20px; /* reduced from 24px */
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        justify-content: space-between;
    }

    /* Meta text at the top of card body */
    .puja-card-category {
        font-family: 'Outfit', sans-serif;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #c2410c;
        letter-spacing: 0.5px;
        margin-bottom: 4px; /* reduced from 8px */
    }

    .puja-card-title {
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        font-size: 1.12rem; /* reduced from 1.15rem */
        color: #1e293b;
        line-height: 1.35;
        margin-bottom: 4px; /* reduced from 6px */
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        height: 3.1rem; /* adjusted height */
    }

    .puja-card-subtitle {
        font-family: 'Outfit', sans-serif;
        font-size: 0.88rem; /* reduced from 0.9rem */
        color: #64748b;
        line-height: 1.4;
        margin-bottom: 8px; /* reduced from 15px */
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        height: 2.5rem; /* adjusted height */
    }

    /* Details row: location, date */
    .puja-card-detail-item {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        font-family: 'Outfit', sans-serif;
        font-size: 0.85rem; /* reduced from 0.88rem */
        color: #4b5563;
        margin-bottom: 6px; /* reduced from 12px */
    }

    .puja-card-detail-item i {
        color: #fbbf24;
        font-size: 0.95rem;
        margin-top: 2px;
        width: 16px;
        text-align: center;
    }

    .puja-card-detail-text {
        line-height: 1.35;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        text-overflow: ellipsis;
    }

    .puja-card-divider {
        height: 1px;
        background-color: rgba(0, 0, 0, 0.06);
        margin: 10px 0; /* reduced from 15px */
    }

    /* Footer button */
    .puja-card-btn {
        background: #ffd700;
        color: #0e0c0c;
        border: none;
        border-radius: 50px;
        padding: 10px 20px;
        font-family: 'Outfit', sans-serif;
        font-weight: 700;
        font-size: 0.88rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        text-decoration: none !important;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(255, 215, 0, 0.2);
    }

    .puja-card-btn:hover {
        background: #e6c200;
        color: #0e0c0c;
        box-shadow: 0 6px 16px rgba(255, 215, 0, 0.35);
        transform: translateY(-1px);
    }

    /* Scope the layout container styling only to the puja grid content container */
    @media (min-width: 1199px) {
        .container.pujalist-show {
            max-width: 1200px !important;
        }
    }

    /* ==========================================================================
       Dark Mode Overrides
       ========================================================================== */
    body.home-dark-mode .puja-card-custom {
        background: #1a1611;
        border-color: rgba(255, 255, 255, 0.08);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }

    body.home-dark-mode .puja-card-custom:hover {
        box-shadow: 0 16px 36px rgba(0, 0, 0, 0.45);
    }

    body.home-dark-mode .puja-card-title {
        color: #ffffff !important;
    }

    body.home-dark-mode .puja-card-subtitle {
        color: #94a3b8 !important;
    }

    body.home-dark-mode .puja-card-detail-item {
        color: #94a3b8 !important;
    }

    body.home-dark-mode .puja-card-detail-item i {
        color: #f1e135 !important;
    }

    body.home-dark-mode .puja-card-divider {
        background-color: rgba(255, 255, 255, 0.08);
    }

    body.home-dark-mode .puja-card-btn {
        background: #f1e135;
        color: #0c0a08;
        box-shadow: 0 4px 12px rgba(241, 225, 53, 0.2);
    }

    body.home-dark-mode .puja-card-btn:hover {
        background: #d8c92a;
        color: #0c0a08;
        box-shadow: 0 6px 16px rgba(241, 225, 53, 0.35);
        transform: translateY(-1px);
    }
</style>

@if($pujalists->isEmpty())
    <div class="container mt-5 mb-5 justify-content-center">
        <div class="text-center">
            <img src="{{ asset('public/frontend/homeimage/360.png') }}" alt="No Puja Found" class="img-fluid" />
            <h3 class="mt-4">No Puja Found !</h3>
        </div>
    </div>
@else
    <div class="container mt-5 mb-5 pujalist-show">
        @foreach ($pujalists as $puja)
            @php
            $startDatetime = $puja->puja_start_datetime ? \Carbon\Carbon::parse($puja->puja_start_datetime) : null;
            $endDatetime = $puja->puja_end_datetime ? \Carbon\Carbon::parse($puja->puja_end_datetime) : null;

            // Skip if start and end are exactly same
            if($startDatetime && $endDatetime && $startDatetime->eq($endDatetime)) continue;

            $images = $puja->puja_images;
            $firstImage = !empty($images) ? $images[0] : 'public/frontend/homeimage/360.png';
            $imageSrc = Str::startsWith($firstImage, ['http://','https://']) ? $firstImage : asset($firstImage);

            $startDateDisplay = $startDatetime ? $startDatetime->format('j M, D') : 'Date not available';
            $endDateDisplay = $endDatetime ? $endDatetime->format('j M, D') : '';
            $startTimeDisplay = $startDatetime ? $startDatetime->format('H:i') : '';
            $endTimeDisplay = $endDatetime ? $endDatetime->format('H:i') : '';
            $sameDate = $startDatetime && $endDatetime ? $startDatetime->isSameDay($endDatetime) : true;
            @endphp

            <div class="puja-card-custom">
                <div class="puja-card-media">
                    <img class="puja-card-img"
                         src="{{ $imageSrc }}"
                         onerror="this.onerror=null;this.src='{{ asset('public/frontend/homeimage/360.png') }}';"
                         alt="{{ $puja->puja_title }}"
                         onclick="openImage('{{ $firstImage }}')" />
                </div>

                <div class="puja-card-body">
                    <div>
                        <!-- Category Name -->
                        <div class="puja-card-category">
                            {{ \Illuminate\Support\Str::limit($puja->category->name ?? 'Puja Category', 39, '...') }}
                        </div>

                        <!-- Title -->
                        <h3 class="puja-card-title">{{ \Illuminate\Support\Str::limit($puja->puja_title, 58, '...') }}</h3>

                        <!-- Subtitle -->
                        <p class="puja-card-subtitle">{{ \Illuminate\Support\Str::limit($puja->puja_subtitle, 58, '...') }}</p>

                        <!-- Location Detail -->
                        <div class="puja-card-detail-item">
                            <i class="fa-solid fa-place-of-worship"></i>
                            <span class="puja-card-detail-text">{{ \Illuminate\Support\Str::limit($puja->puja_place, 60, '...') }}</span>
                        </div>

                        <!-- Date Detail -->
                        <div class="puja-card-detail-item" style="margin-bottom: 0;">
                            <i class="fa-regular fa-calendar"></i>
                            <span class="puja-card-detail-text">
                                {{ $startDatetime && $endDatetime ? ($sameDate ? $startDateDisplay.' '.$startTimeDisplay : $startDateDisplay.' '.$startTimeDisplay.' '.$endTimeDisplay) : 'Date not available' }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <div class="puja-card-divider"></div>
                        <a href="{{ route('front.pujaDetails', $puja->slug) }}" class="puja-card-btn w-100">
                            PARTICIPATE <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

<div class="mt-8 text-center pb-5">
    {{ $pujalists->links() }}
</div>
@endsection
