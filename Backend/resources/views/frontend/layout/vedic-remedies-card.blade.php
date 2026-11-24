@php
    $text = mb_strtolower(($blog->title ?? "") . " " . ($blog->description ?? ""));
    
    // Contextual matching
    if (str_contains($text, "शिव") || str_contains($text, "mrityunjay") || str_contains($text, "रुद्राभिषेक") || str_contains($text, "shiva") || str_contains($text, "काल सर्प") || str_contains($text, "स्वास्थ्य")) {
        $recommendedPuja = [
            "badge" => "🔱 विशेष शिव अनुष्ठान & आयु कवच",
            "title" => "महामृत्युंजय आयुष्य हवन एवं संजीवनी महाजाप",
            "desc"  => "अकाल मृत्यु भय, गंभीर रोग एवं ग्रह बाधा से मुक्ति हेतु काशी व उज्जैन के सिद्ध वेदपाठी पुरोहितों द्वारा आपके नाम-गोत्र से संकल्पित वैदिक महाजाप।",
            "price" => "₹1,501",
            "slug"  => "maha-mrityunjaya-ayushya-havan-sanjeevani-jaap",
            "icon"  => "🔱"
        ];
    } elseif (str_contains($text, "धन") || str_contains($text, "लक्ष्मी") || str_contains($text, "कुबेर") || str_contains($text, "wealth") || str_contains($text, "व्यापार") || str_contains($text, "career")) {
        $recommendedPuja = [
            "badge" => "💰 धन वृद्धि एवं व्यापार समृद्धि",
            "title" => "श्री लक्ष्मी-कुबेर महा धन लाभ पूजा एवं कनकधारा हवन",
            "desc"  => "ऋण मुक्ति, स्थिर धन संचय एवं व्यापारिक उन्नति हेतु कनकधारा स्तोत्र एवं श्री सूक्त के वैदिक मंत्रों द्वारा पावन हवन।",
            "price" => "₹501",
            "slug"  => "shree-lakshmi-kubera-maha-dhan-labh-puja",
            "icon"  => "🪔"
        ];
    } elseif (str_contains($text, "पितृ") || str_contains($text, "श्राद्ध") || str_contains($text, "अमावस्या") || str_contains($text, "तर्पण") || str_contains($text, "pitru")) {
        $recommendedPuja = [
            "badge" => "🕊️ पितृ दोष निवारण एवं मोक्ष संकल्प",
            "title" => "गया जी एवं काशी में पितृ शांति तर्पण व पिंडदान महापूजा",
            "desc"  => "पूर्वजों के आशीर्वाद, वंश वृद्धि एवं गृह क्लेश मुक्ति हेतु गया तीर्थ पर शास्त्रोक्त पिंडदान एवं तर्पण अनुष्ठान।",
            "price" => "₹1,100",
            "slug"  => "pitru-paksha-shradh-tarpan-puja-at-gaya",
            "icon"  => "🕊️"
        ];
    } else {
        $recommendedPuja = [
            "badge" => "ॐ काशी विश्वनाथ धाम वैदिक संकल्प",
            "title" => "वैदिक ग्रह शांति, नवग्रह दोष निवारण एवं मनोकामना पूर्ति पूजा",
            "desc"  => "काशी के विद्वान पुरोहितों द्वारा आपके एवं परिवार के नाम-गोत्र से शास्त्रोक्त पूजा संकल्प। संपूर्ण लाइव वीडियो व सिद्ध प्रसाद घर भेजा जाएगा।",
            "price" => "₹501",
            "slug"  => "pujas",
            "icon"  => "🛕"
        ];
    }

    $pujaUrl = $recommendedPuja["slug"] === "pujas" ? url("pujas") : url("puja/" . $recommendedPuja["slug"]);
    $waText = urlencode("ॐ नमः शिवाय पंडित जी 🙏, मैं OnlinePuja.live पर '" . ($blog->title ?? "पूजा") . "' पढ़ रहा था। मुझे " . $recommendedPuja["title"] . " संकल्प के बारे में मार्गदर्शन चाहिए।");
    $waUrl = "https://wa.me/917007158014?text=" . $waText;
@endphp

<!-- 🕉️ CONTEXTUAL VEDIC CONVERSION MESH -->
<div class="container my-4 my-md-5">
    <div class="card border-0 shadow-lg text-white" style="background: linear-gradient(135deg, #0a0f1d 0%, #172033 100%); border-radius: 18px; border: 1.5px solid #d4af37 !important; overflow: hidden; position: relative;">
        <!-- Top Golden Glow Accent -->
        <div style="height: 4px; background: linear-gradient(90deg, #d4af37, #f59e0b, #fbbf24);"></div>
        
        <div class="card-body p-4 p-md-5">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="d-inline-flex align-items-center px-3 py-1 mb-3" style="background: rgba(212, 175, 55, 0.15); border: 1px solid rgba(212, 175, 55, 0.4); border-radius: 20px;">
                        <span style="color: #fbbf24; font-size: 13px; font-weight: 700;">{{ $recommendedPuja['badge'] }}</span>
                    </div>
                    
                    <h3 class="font-weight-bold mb-2" style="color: #fbbf24; font-size: 24px; line-height: 1.4;">
                        {{ $recommendedPuja['icon'] }} {{ $recommendedPuja['title'] }}
                    </h3>
                    
                    <p class="text-light mb-4" style="font-size: 14.5px; line-height: 1.6; color: #cbd5e1 !important;">
                        {{ $recommendedPuja['desc'] }}
                        <br>
                        <strong style="color: #fcd34d;">📹 लाइव संकल्प वीडियो व्हाट्सएप पर एवं प्राण-प्रतिष्ठित प्रसाद आपके घर कूरियर द्वारा भेजा जाएगा।</strong>
                    </p>

                    <div class="d-flex flex-wrap align-items-center" style="gap: 12px;">
                        <a href="{{ $pujaUrl }}" class="btn font-weight-bold px-4 py-2" style="background: #fbbf24; color: #000; border-radius: 10px; font-size: 14px; text-decoration: none; box-shadow: 0 4px 14px rgba(251, 191, 36, 0.35);">
                            🪔 बुक करें पावन संकल्प ({{ $recommendedPuja['price'] }})
                        </a>
                        <a href="{{ $waUrl }}" target="_blank" class="btn btn-outline-success font-weight-bold px-4 py-2" style="border-radius: 10px; font-size: 14px; text-decoration: none; border-color: #22c55e; color: #4ade80;">
                            💬 WhatsApp पर पुरोहित से बात करें
                        </a>
                        <a href="{{ url('products') }}" class="btn btn-outline-warning font-weight-bold px-3 py-2" style="border-radius: 10px; font-size: 13px; text-decoration: none;">
                            📿 सिद्ध रुद्राक्ष व रत्न
                        </a>
                    </div>
                </div>

                <div class="col-lg-4 text-center mt-4 mt-lg-0">
                    <img src="{{ asset('public/storage/images/onlinepuja-logo-web.png') }}" alt="OnlinePuja.live" class="img-fluid mb-2" style="max-width: 150px; filter: drop-shadow(0px 8px 24px rgba(212, 175, 55, 0.4));">
                    <div class="small" style="color: #94a3b8; font-size: 12px; line-height: 1.6;">
                        ✓ 100% प्रामाणिक वैदिक संकल्प<br>
                        ✓ काशी विश्वनाथ के वेदपाठी ब्राह्मण<br>
                        ✓ स्पीड पोस्ट द्वारा सिद्ध प्रसाद डिलीवरी
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 📱 HIGH-CONVERTING MOBILE STICKY BOTTOM BAR -->
<div class="d-block d-md-none fixed-bottom onlinepuja-mobile-sticky-bar" style="z-index: 1040; background: rgba(11, 18, 34, 0.96); backdrop-filter: blur(10px); border-top: 2px solid #d4af37; box-shadow: 0 -4px 20px rgba(0,0,0,0.5); padding: 8px 12px calc(8px + env(safe-area-inset-bottom, 0px)) 12px;">
    <div class="d-flex align-items-center justify-content-between">
        <div style="flex: 1; min-width: 0; padding-right: 10px;">
            <div style="font-size: 10px; font-weight: 700; color: #fbbf24; text-transform: uppercase; letter-spacing: 0.5px;">
                🕉️ काशी वैदिक सेवा केंद्र
            </div>
            <div style="font-size: 12px; font-weight: 700; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                {{ $recommendedPuja['title'] }}
            </div>
        </div>
        <div class="d-flex align-items-center" style="gap: 6px;">
            <a href="{{ $waUrl }}" target="_blank" class="btn btn-sm font-weight-bold" style="background: #25D366; color: #ffffff; border-radius: 20px; font-size: 12px; padding: 6px 14px; box-shadow: 0 2px 8px rgba(37, 211, 102, 0.4); text-decoration: none; white-space: nowrap;">
                💬 WhatsApp
            </a>
            <a href="{{ $pujaUrl }}" class="btn btn-sm font-weight-bold" style="background: #fbbf24; color: #1e293b; border-radius: 20px; font-size: 12px; padding: 6px 12px; box-shadow: 0 2px 8px rgba(251, 191, 36, 0.3); text-decoration: none; white-space: nowrap;">
                संकल्प {{ $recommendedPuja['price'] }}
            </a>
        </div>
    </div>
</div>

<style>
@media (max-width: 767.98px) {
    body {
        padding-bottom: calc(72px + env(safe-area-inset-bottom, 0px)) !important;
    }
    #onlinepuja-voice-widget-btn {
        bottom: calc(env(safe-area-inset-bottom, 0px) + 68px) !important;
        right: 12px !important;
    }
    #onlinepuja-voice-modal {
        bottom: calc(env(safe-area-inset-bottom, 0px) + 64px) !important;
        max-height: calc(100vh - env(safe-area-inset-bottom, 0px) - 80px) !important;
    }
}
</style>
