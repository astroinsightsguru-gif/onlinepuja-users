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
        padding: 16px 24px 20px; /* reduced top/bottom padding */
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        justify-content: space-between;
    }

    .puja-card-title {
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        font-size: 1.15rem; /* reduced from 1.2rem */
        color: #1e293b;
        line-height: 1.35;
        margin-top: 4px; /* reduced from 10px */
        margin-bottom: 4px; /* reduced from 10px */
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

    body.home-dark-mode .text-dark {
        color: #ffffff !important;
    }
</style>

@if($pujaCategories->isEmpty())
<!-- Show this section when no pujas are found -->
<div class="container mt-5 mb-5 justify-content-center">
    <div class="text-center">
        <img src="{{ asset('public/frontend/homeimage/360.png') }}" alt="No Puja Found" class="img-fluid" />
        <h3 class="mt-4">No Puja Category Found !</h3>
    </div>
</div>

@else

<div class="container mt-5 mb-5 pujalist-show">
    @foreach ($pujaCategories as $category)
    <div class="puja-card-custom">
        @php
            $image = $category->image;
            $firstImage = !empty($image) ? $image : 'public/frontend/homeimage/360.png';
            $imageSrc = Str::startsWith($firstImage, ['http://','https://']) ? $firstImage : asset($firstImage);
        @endphp

        <div class="puja-card-media">
            <img class="puja-card-img"
                 src="{{ $imageSrc }}"
                 onerror="this.onerror=null;this.src='{{ asset('public/frontend/homeimage/360.png') }}';"
                 alt="{{ $category->name }}"
                 onclick="openImage('{{ $firstImage }}')" />
        </div>

        <div class="puja-card-body">
            <div>
                <h3 class="puja-card-title text-center">{{ $category->name }}</h3>
            </div>
            <div>
                <div class="puja-card-divider"></div>
                <a href="{{ route('front.pujaList', $category->id) }}" class="puja-card-btn w-100">
                    Explore Pujas <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

<div class="mt-8 text-center pb-5">
    {{ $pujaCategories->links() }}
</div>

@endsection
