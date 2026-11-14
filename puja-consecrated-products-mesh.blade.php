@php
    $remedyProducts = \Illuminate\Support\Facades\DB::table('astromall_products')
        ->where('isActive', 1)
        ->where('isDelete', 0)
        ->orderBy('amount', 'asc')
        ->limit(4)
        ->get();
@endphp

@if($remedyProducts->isNotEmpty())
<!-- 🪔 Consecrated Remedial Samagri & Prasad Box Mesh -->
<div id="consecrated-remedies" class="section mt-5 pt-3">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden" style="background: linear-gradient(135deg, #1A1208 0%, #2A1B0E 100%); border: 1.5px solid rgba(245, 158, 11, 0.4) !important;">
        <div class="card-body p-4 p-md-5">
            <!-- Header -->
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-3 border-bottom border-warning border-opacity-25">
                <div>
                    <span class="badge px-3 py-2 rounded-pill text-dark fw-bold mb-2" style="background: linear-gradient(90deg, #F59E0B, #FBBF24); font-size: 0.8rem; letter-spacing: 0.5px;">
                        ॐ अभिमंत्रित वैदिक सामग्री एवं सिद्ध महाप्रसाद
                    </span>
                    <h3 class="h4 text-white fw-bold mb-1 d-flex align-items-center gap-2">
                        <span>सिद्ध रुद्राक्ष, रत्न एवं काशी महाप्रसाद किट</span>
                    </h3>
                    <p class="text-white-50 mb-0 small">
                        इस पूजा के साथ अपने नाम-गोत्र से अभिमंत्रित (Pran Pratishtha) कराएं। पूजा संपन्न होने पर वैदिक महाप्रसाद के साथ आपके घर स्पीड पोस्ट द्वारा भेजा जाएगा।
                    </p>
                </div>
                <div class="d-none d-lg-block text-end">
                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 px-3 py-2 rounded-pill small">
                        ✓ 100% प्राण-प्रतिष्ठित गारंटी
                    </span>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="row g-3">
                @foreach($remedyProducts as $prod)
                    <div class="col-6 col-md-3">
                        <div class="card h-100 border-0 rounded-3 text-center p-3" style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(8px); border: 1px solid rgba(245, 158, 11, 0.2) !important;">
                            <div class="position-relative mb-3 overflow-hidden rounded-3" style="height: 130px; background: #000;">
                                <img src="{{ \Illuminate\Support\Str::startsWith($prod->productImage, ['http://','https://']) ? $prod->productImage : '/' . $prod->productImage }}"
                                     alt="{{ $prod->name }}"
                                     class="w-100 h-100 object-fit-cover"
                                     onerror="this.onerror=null;this.src='/storage/images/onlinepuja-logo-web.png';">
                                <span class="position-absolute top-0 start-0 m-2 badge bg-warning text-dark fw-bold" style="font-size: 0.65rem;">
                                    सिद्ध
                                </span>
                            </div>
                            <h6 class="text-white fw-bold small text-truncate-2 mb-2" style="min-height: 38px; line-height: 1.3;">
                                {{ $prod->name }}
                            </h6>
                            <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
                                <span class="text-warning fw-bold fs-6">₹{{ number_format($prod->amount) }}</span>
                            </div>
                            <a href="/products/{{ $prod->id }}" class="btn btn-sm btn-outline-warning w-100 rounded-pill fw-semibold" style="font-size: 0.75rem;">
                                सामग्री देखें →
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- WhatsApp Pandit Assistance Bar -->
            <div class="mt-4 pt-3 border-top border-warning border-opacity-25 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2 text-white-50 small">
                    <span class="text-warning fs-5">🪔</span>
                    <span>अपनी कुंडली एवं ग्रह दोष अनुसार उपयुक्त रत्न या रुद्राक्ष परामर्श के लिए हमारे वरिष्ठ ज्योतिषी से चर्चा करें।</span>
                </div>
                <a href="https://wa.me/917007158014?text={{ urlencode('सादर प्रणाम पंडित जी! मैं ' . ($puja->puja_title ?? 'पूजा') . ' के साथ उपयुक्त अभिमंत्रित रुद्राक्ष / रत्न की जानकारी चाहता हूँ।') }}" 
                   target="_blank" 
                   class="btn btn-sm text-dark fw-bold px-3 py-2 rounded-pill" 
                   style="background: #25D366;">
                    💬 ज्योतिषी से व्हाट्सएप परामर्श
                </a>
            </div>
        </div>
    </div>
</div>
@endif
