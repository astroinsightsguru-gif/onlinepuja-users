@extends('../layout/' . ($layout ?? 'login'))

@section('head')
    <title>Page Not Found — Online Puja</title>
@endsection

@section('content')
    <div class="container">
        @php
            $logo = DB::table('systemflag')
                ->where('name', 'AdminLogo')
                ->select('value')
                ->first();
        @endphp
        <!-- BEGIN: Error Page -->
        <div class="error-page flex flex-col lg:flex-row items-center justify-center min-h-screen text-center lg:text-left py-10">
            <div class="-intro-x lg:mr-20">
                <img alt="Online Puja" style="width: 240px;" class="h-auto mx-auto" src="/{{ $logo->value ?? 'public/frontend/images/logo.png' }}">
            </div>
            <div class="mt-8 lg:mt-0">
                <div class="intro-x text-7xl font-extrabold text-amber-500">404</div>
                <div class="intro-x text-2xl lg:text-3xl font-bold mt-4 text-slate-800">Oops. This sacred page has moved.</div>
                <div class="intro-x text-sm text-slate-500 mt-2">The requested Vedic puja or page address could not be found.</div>
                <div class="intro-x mt-8 flex flex-wrap gap-3 justify-center lg:justify-start">
                    <a class="btn btn-primary py-2.5 px-5 font-bold rounded-xl" href="{{ route('front.home') }}" style="background: #0f172a; color: #fff; border: none;">
                        Back to Home
                    </a>
                    <a class="btn btn-outline-secondary py-2.5 px-5 font-bold rounded-xl" href="/puja/category/list" style="background: #fbbf24; color: #020617; border: none;">
                        Browse All Pujas 🪔
                    </a>
                </div>
            </div>
        </div>
        <!-- END: Error Page -->
    </div>
@endsection
