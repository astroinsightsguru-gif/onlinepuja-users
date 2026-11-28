@extends('frontend.layout.master')

@php
    $package = $package ?? [];
    $FAQ = $FAQ ?? [];
@endphp

@section('title', ($puja->puja_title ?? $puja->puja_name ?? 'Vedic Puja') . ' in Varanasi | Book Vedic Puja Online')
@section('meta_description', 'Book authentic ' . ($puja->puja_title ?? $puja->puja_name ?? 'Vedic Puja') . ' performed by qualified Vedic Pandits in Varanasi. Includes live sankalp video recording and sacred prasad delivery.')
@section('canonical_url', !empty($puja->slug) ? route('front.pujaDetails', $puja->slug) : url('/'))

@section('schema_markup')
@php
    $firstImg = !empty($puja->puja_images) ? $puja->puja_images[0] : 'build/assets/images/logo.png';
    $schemaImg = Str::startsWith($firstImg, ['http://','https://']) ? $firstImg : asset($firstImg);
@endphp
<script type="application/ld+json">
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": "{{ addslashes($puja->puja_title) }}",
  "image": "{{ $schemaImg }}",
  "description": "{{ addslashes(Str::limit(strip_tags($puja->long_description ?? $puja->puja_subtitle ?? $puja->puja_title), 160)) }}",
  "brand": {
    "@type": "Brand",
    "name": "OnlinePuja Varanasi"
  },
  "offers": {
    "@type": "Offer",
    "url": "{{ route('front.pujaDetails', $puja->slug) }}",
    "priceCurrency": "INR",
    "price": "{{ $puja->puja_price ?? '1100' }}",
    "priceValidUntil": "{{ date('Y-12-31') }}",
    "availability": "https://schema.org/InStock",
    "itemCondition": "https://schema.org/NewCondition"
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.9",
    "reviewCount": "89"
  }
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Home",
      "item": "{{ url('/') }}"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "Pujas",
      "item": "{{ route('front.pujaCategory') }}"
    },
    {
      "@type": "ListItem",
      "position": 3,
      "name": "{{ addslashes($puja->puja_title) }}",
      "item": "{{ route('front.pujaDetails', $puja->slug) }}"
    }
  ]
}
</script>
@endsection

@section('content')
@php
    $currencySymbol = systemflag('currencySymbol') ?? '₹';
@endphp

<main role="main" class="margin-top-header">
    <!-- Breadcrumb Header -->
    <div class="pt-1 pb-1 bg-red d-md-block onlinepuja-breadcrumb">
        <div class="container">
            <div class="row afterLoginDisplay">
                <div class="col-12 d-flex align-items-center">
                    <span style="text-transform: capitalize;">
                        <span class="text-white breadcrumbs">
                            <a href="/" class="text-white text-decoration-none">
                                <i class="fa fa-home font-18"></i>
                            </a>
                            <i class="fa fa-chevron-right"></i>
                            <a href="{{ route('front.pujaList', $puja->category_id ?? 1) }}" class="text-white text-decoration-none">Puja</a>
                            <i class="fa fa-chevron-right"></i>
                            Puja Details - {{$puja->puja_title}}
                        </span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Detail Area -->
    <div class="product-detail py-4">
        <div class="container py-5 px-3">
            <!-- Product Card -->
            <div class="puja-detail-card p-4 d-flex flex-column flex-md-row gap-4">

                <!-- Left: Main Image -->
                <div class="col-md-6 d-flex flex-column align-items-center">
                    <div class="w-100 main-image-wrapper">
                        @php
                            $firstImage = !empty($puja->puja_images) ? $puja->puja_images[0] : 'public/frontend/homeimage/360.png';
                            $mainImageSrc = Str::startsWith($firstImage, ['http://','https://']) ? $firstImage : asset($firstImage);
                        @endphp
                        <img id="mainImage" class="w-100 h-100 object-fit-cover transition-all"
                             src="{{ $mainImageSrc }}"
                             onerror="this.onerror=null;this.src='/public/frontend/homeimage/360.png';"
                             alt="{{ $puja->puja_title }}" />
                    </div>

                    <!-- Thumbnails -->
                    @if(count($puja->puja_images) > 1)
                        <div class="d-flex gap-3 mt-3 overflow-auto w-100 justify-content-center py-2">
                            @foreach ($puja->puja_images as $index => $image)
                                <img class="thumb-img"
                                     src="{{ Str::startsWith($image, ['http://','https://']) ? $image : asset($image) }}"
                                     onerror="this.onerror=null;this.src='/public/frontend/homeimage/360.png';"
                                     alt="Puja image thumbnail"
                                     onclick="changeImage(this)" />
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Right: Product Details -->
                <div class="col-md-6 d-flex flex-column justify-content-between">
                    <div>
                        <h2 class="puja-category-title fs-5 text-uppercase mb-2">{{ $puja->category->name ?? 'Vedic Puja' }}</h2>
                        <h1 class="puja-main-title h3 fw-bold mb-2">{{ $puja->puja_title }}</h1>
                        <h5 class="puja-sub-title mb-3">{{ $puja->puja_subtitle }}</h5>

                        <div class="puja-place-text d-flex align-items-center gap-2 mb-4">
                            <i class="fa-solid fa-place-of-worship text-warning"></i>
                            <strong>{{ $puja->puja_place }}</strong>
                        </div>

                        @php
                            $startDatetime = !empty($puja->puja_start_datetime) ? \Carbon\Carbon::parse($puja->puja_start_datetime) : \Carbon\Carbon::now();
                            $endDatetime = !empty($puja->puja_end_datetime) ? \Carbon\Carbon::parse($puja->puja_end_datetime) : \Carbon\Carbon::now()->addHours(2);

                            $startDateDisplay = $startDatetime->format('j M, D');
                            $endDateDisplay = $endDatetime->format('j M, D');
                            $startTimeDisplay = $startDatetime->format('H:i');
                            $endTimeDisplay = $endDatetime->format('H:i');
                            $sameDate = $startDatetime->isSameDay($endDatetime);

                            $now = \Carbon\Carbon::now();
                            $isFutureEvent = $now->lt($startDatetime);
                        @endphp

                        <div class="d-flex align-items-center mb-3">
                            <span class="badge badge-date py-2 px-3 fs-6">
                                <i class="fa-regular fa-calendar-days me-2"></i>
                                @if($sameDate)
                                    {{ $startDateDisplay }} {{ $startTimeDisplay }} - {{ $endTimeDisplay }}
                                @else
                                    {{ $startDateDisplay }} {{ $startTimeDisplay }} to {{ $endDateDisplay }} {{ $endTimeDisplay }}
                                @endif
                            </span>
                        </div>

                        @if($isFutureEvent)
                            <div class="countdown-timer mt-2"
                                 data-start-datetime="{{ $startDatetime->toIso8601String() }}">
                                <div class="badge-countdown d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3">
                                    <i class="fa-regular fa-clock text-warning"></i>
                                    <span class="fw-semibold">Puja starts in:</span>
                                    <span class="fw-bold">
                                        <span class="days">0</span>d
                                        <span class="hours">0</span>h
                                        <span class="minutes">0</span>m
                                        <span class="seconds">0</span>s
                                    </span>
                                </div>
                            </div>
                        @else
                            <div class="mt-3">
                                <div class="status-ongoing-capsule d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 text-success">
                                    <span class="status-dot-blink bg-success"></span>
                                    <span class="fw-bold">Puja is ongoing</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    <a href="#packages" id="selectPackageBtn" class="btn btn-select-package w-100 mt-4 fw-semibold d-flex justify-content-center align-items-center">
                        Select Puja Package <i class="fa-solid fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 🕉️ Standardized to Universal Vedic Design System (onlinepuja-design-system.css) -->

    <!-- Tabbed Information Container -->
    <div class="container py-4">

        <!-- ===== TAB MENU ===== -->
        <div class="py-2 product-info justify-content-center d-flex w-100 mt-3 rounded-3">
            <ul class="nav flex-wrap justify-content-center" id="pujaTabs">
                <li class="nav-item px-2">
                    <a class="nav-link active" data-target="about">About Puja</a>
                </li>
                <li class="nav-item px-2">
                    <a class="nav-link" data-target="benefits">Benefits</a>
                </li>
                <li class="nav-item px-2">
                    <a class="nav-link" data-target="process">Process</a>
                </li>
                <li class="nav-item px-2">
                    <a class="nav-link" data-target="packages">Packages</a>
                </li>
                <li class="nav-item px-2">
                    <a class="nav-link" data-target="faqs">FAQs</a>
                </li>
            </ul>
        </div>

        <!-- ===== TAB CONTENT ===== -->
        <div class="tab-content mt-4">

            <!-- About -->
            <div id="about" class="section active">
                <h2 class="section-title">About Puja</h2>
                <p class="section-text text-justify mt-3">
                    {{ $puja->long_description ?? 'Detailed description about the Puja will appear here.' }}
                </p>
            </div>

            <!-- Benefits -->
            <div id="benefits" class="section">
                <h2 class="section-title">Puja Benefits</h2>
                <div class="row mt-4">
                    @foreach (($puja->puja_benefits ?? []) as $benefit)
                        <div class="col-md-4 mb-3">
                            <div class="benefit-card d-flex p-3 align-items-start">
                                <div class="benefit-icon-wrapper me-3">
                                    <i class="fa fa-star text-warning fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="benefit-title fw-bold mb-1">{{ $benefit['title'] }}</h6>
                                    <p class="benefit-desc mb-0">{{ $benefit['description'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Process -->
            <div id="process" class="section">
                <h2 class="section-title">Puja Process</h2>
                <div class="row mt-4">
                    <div class="col-md-4 mb-3">
                        <div class="process-card p-4">
                            <div class="process-step">1</div>
                            <h5 class="process-title fw-bold mt-3">Select Puja</h5>
                            <p class="process-desc text-muted mb-0">Choose from the puja packages listed below.</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="process-card p-4">
                            <div class="process-step">2</div>
                            <h5 class="process-title fw-bold mt-3">Add Offerings</h5>
                            <p class="process-desc text-muted mb-0">Enhance your experience with optional offerings like Deep Daan or Anna Daan.</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="process-card p-4">
                            <div class="process-step">3</div>
                            <h5 class="process-title fw-bold mt-3">Sankalp Details</h5>
                            <p class="process-desc text-muted mb-0">Provide Name and Gotra for Sankalp.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Packages -->
            <div id="packages" class="section">
                <h2 class="section-title">Select Puja Package</h2>
                <div class="row mt-4">
                    @foreach ($package as $packageDetail)
                        <div class="col-md-4 mb-4">
                            <div class="package-card">
                                <div class="package-header text-center">
                                    <h3 class="package-title">{{ $packageDetail['title'] }}</h3>
                                    <span class="package-audience">For {{ $packageDetail['person'] }} Person</span>
                                </div>

                                <div class="package-price-box text-center py-3 my-3">
                                    <span class="price-value">
                                        @if(systemflag('walletType') == 'Coin')
                                            <img src="{{ asset($coinIcon) }}" alt="Wallet Icon" width="18" class="me-1">
                                        @else
                                            <span class="currency-symbol me-1">{{ $currencySymbol ?? '₹' }}</span>
                                        @endif
                                        {{ $packageDetail['package_price'] }}
                                    </span>
                                </div>

                                <div class="package-features my-2">
                                    <ul class="text-start list-unstyled">
                                        @foreach ($packageDetail['description'] as $point)
                                            <li>
                                                <i class="fa-solid fa-circle-check text-success me-2 mt-1"></i>
                                                <span>{{ $point }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>

                                <div class="package-footer text-center mt-auto pt-3">
                                    <a @if(!authcheck()) data-toggle="modal" data-target="#loginSignUp" @else href="{{route('front.pujaAstrologerList',['slug'=>$puja->slug,'package_id'=>$packageDetail['id']])}}" @endif class="btn btn-participate w-100">Participate Now</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- FAQs -->
            <div id="faqs" class="section">
                <h2 class="section-title">FAQs</h2>
                <div class="accordion mt-4" id="faqAccordion">
                    @foreach (($FAQ ?? []) as $index => $faqItem)
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading{{ $index }}">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}" aria-expanded="false">
                                    {{ $faqItem->title }}
                                </button>
                            </h2>
                            <div id="collapse{{ $index }}" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    {{ $faqItem->description }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div><!-- End tab-content -->
    </div><!-- End container -->

    <!-- ===== JS ===== -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      function changeImage(el) {
          document.getElementById('mainImage').src = el.src;
      }

      // Handle tab switching
      const tabs = document.querySelectorAll('#pujaTabs .nav-link');
      const sections = document.querySelectorAll('.section');

      tabs.forEach(tab => {
        tab.addEventListener('click', function() {
          // Remove active class from tabs
          tabs.forEach(t => t.classList.remove('active'));
          this.classList.add('active');

          // Hide all sections
          sections.forEach(s => s.classList.remove('active'));

          // Show selected section
          const targetId = this.getAttribute('data-target');
          document.getElementById(targetId).classList.add('active');

          // Scroll to section smoothly
          window.scrollTo({ top: document.querySelector('.product-info').offsetTop - 100, behavior: 'smooth' });
        });
      });

      // Handle Select Puja Package button click
      const selectPackageBtn = document.getElementById('selectPackageBtn');
      if (selectPackageBtn) {
        selectPackageBtn.addEventListener('click', function(e) {
          e.preventDefault();
          const packageTab = document.querySelector('#pujaTabs .nav-link[data-target="packages"]');
          if (packageTab) {
            packageTab.click();
          }
        });
      }
    </script>

    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const readMoreElements = document.querySelectorAll('.read-more');

        readMoreElements.forEach(element => {
            element.addEventListener('click', function () {
                const fullDescription = this.nextElementSibling;
                const readLess = this.nextElementSibling.nextElementSibling;
                fullDescription.style.display = 'inline';
                this.style.display = 'none';
                readLess.style.display = 'inline';
            });
        });

        const readLessElements = document.querySelectorAll('.read-less');

        readLessElements.forEach(element => {
            element.addEventListener('click', function () {
                const fullDescription = this.previousElementSibling;
                const readMore = this.previousElementSibling.previousElementSibling;
                fullDescription.style.display = 'none';
                this.style.display = 'none';
                readMore.style.display = 'inline';
            });
        });
    });

    $(document).ready(function () {
        $('.owl-carousel').owlCarousel({
            loop: true,
            margin: 20,
            nav: false,
            dots: true,
            center: true,
            responsive: {
                0: {
                    items: 1
                },
                600: {
                    items: 2
                },
                1000: {
                    items: 3
                }
            }
        });
    });

    document.querySelectorAll('.scrollable-container').forEach(container => {
        container.addEventListener('wheel', (event) => {
            const atTop = container.scrollTop === 0;
            const atBottom = container.scrollHeight - container.scrollTop === container.clientHeight;

            if ((event.deltaY < 0 && !atTop) || (event.deltaY > 0 && !atBottom)) {
                event.preventDefault();
                container.scrollBy({
                    top: event.deltaY,
                    behavior: 'auto'
                });
            }
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const countdownElements = document.querySelectorAll('.countdown-timer');

        function updateCountdown() {
            countdownElements.forEach(element => {
                const startDatetime = new Date(element.dataset.startDatetime);
                const now = new Date();
                const diff = startDatetime - now;

                if (diff <= 0) {
                    element.innerHTML = '<div class="status-ongoing-capsule d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 text-success"><span class="status-dot-blink bg-success"></span><span class="fw-bold">Puja is ongoing</span></div>';
                    return;
                }

                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                const daysEl = element.querySelector('.days');
                const hoursEl = element.querySelector('.hours');
                const minutesEl = element.querySelector('.minutes');
                const secondsEl = element.querySelector('.seconds');

                if(daysEl) daysEl.textContent = days;
                if(hoursEl) hoursEl.textContent = hours;
                if(minutesEl) minutesEl.textContent = minutes;
                if(secondsEl) secondsEl.textContent = seconds;
            });
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);
    });
</script>

@endsection
