<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Models\Puja;
use App\Services\AiEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OnlinePujaAiController extends Controller
{
    protected AiEngineService $aiEngine;

    public function __construct(AiEngineService $aiEngine)
    {
        $this->aiEngine = $aiEngine;
    }

    /**
     * Handle Vedic AI Jyotish consultation chats for OnlinePuja live.
     */
    public function chat(Request $request)
    {
        return $this->consultVoice($request);
    }

    /**
     * Handle Pandit Ji (OnlinePuja AI) Voice & Chat Assistant.
     * Route: POST /api/brain/voice/consult
     */
    public function consultVoice(Request $request)
    {
        $message = trim((string) (
            $request->input('message') 
            ?? $request->input('question') 
            ?? $request->input('query_text') 
            ?? $request->json('message') 
            ?? $request->json('question') 
            ?? (json_decode($request->getContent(), true)['message'] ?? null) 
            ?? ''
        ));
        if (empty($message)) {
            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => [
                    'reply_text' => "🙏 ॐ नमः शिवाय। आयुष्मान भव!\nमैं पंडित जी (OnlinePuja AI) हूँ। अपनी कुंडली, विवाह, व्यापार, स्वास्थ्य अथवा किसी भी ग्रह दोष के निवारण हेतु पूछें।",
                    'intent' => 'greeting',
                    'recommended_puja' => null,
                ]
            ], 200);
        }

        // Detect Vedic Intent and find matching recommended Puja
        $intent = 'general';
        $recommendedPuja = null;
        $lowerMsg = mb_strtolower($message, 'UTF-8');

        // 1. Marriage / Mangal Dosha
        if (preg_match('/(विवाह|शादी|मंगल|दोष|रिश्ता|marriage|mangal|vivah|match|kundali)/iu', $lowerMsg)) {
            $intent = 'marriage_mangal_dosha';
        }
        // 2. Career / Wealth / Vyapar / Money
        elseif (preg_match('/(व्यापार|धन|पैसा|नौकरी|बिजनेस|business|career|job|money|loss|कर्ज|income|काम|रोजगार)/iu', $lowerMsg)) {
            $intent = 'career_wealth_vyapar';
        }
        // 3. Health / Rog / Long life / Mahamrityunjaya
        elseif (preg_match('/(रोग|बीमारी|स्वास्थ्य|health|pain|illness|मृत्युंजय|आयु|तबीयत|कष्ट)/iu', $lowerMsg)) {
            $intent = 'health_mrityunjaya';
        }
        // 4. Kaal Sarp / Rahu / Ketu / Shani
        elseif (preg_match('/(कालसर्प|राहु|केतु|शनि|shani|rahu|ketu|kaal\s*sarp|sadhesati|साढ़ेसाती|ग्रह)/iu', $lowerMsg)) {
            $intent = 'planetary_dosha';
        }
        // 5. Muhurat / Panchang
        elseif (preg_match('/(मुहूर्त|चौघड़िया|पंचांग|तिथी|शुभ|time|today|muhurat|panchang)/iu', $lowerMsg)) {
            $intent = 'muhurat_panchang';
        }

        // Fetch corresponding Puja dynamically from DB
        try {
            $pujaQuery = Puja::query();
            if ($intent === 'marriage_mangal_dosha') {
                $puja = (clone $pujaQuery)->where(function ($q) {
                    $q->where('puja_title', 'like', '%Mangal%')
                      ->orWhere('puja_title', 'like', '%Vivah%')
                      ->orWhere('puja_title', 'like', '%Marriage%')
                      ->orWhere('puja_title', 'like', '%मंगल%')
                      ->orWhere('puja_title', 'like', '%विवाह%');
                })->first();
            } elseif ($intent === 'career_wealth_vyapar') {
                $puja = (clone $pujaQuery)->where(function ($q) {
                    $q->where('puja_title', 'like', '%Lakshmi%')
                      ->orWhere('puja_title', 'like', '%Kubera%')
                      ->orWhere('puja_title', 'like', '%Career%')
                      ->orWhere('puja_title', 'like', '%लक्ष्मी%')
                      ->orWhere('puja_title', 'like', '%व्यापार%');
                })->first();
            } elseif ($intent === 'health_mrityunjaya') {
                $puja = (clone $pujaQuery)->where(function ($q) {
                    $q->where('puja_title', 'like', '%Mrityunjaya%')
                      ->orWhere('puja_title', 'like', '%Ayushya%')
                      ->orWhere('puja_title', 'like', '%महामृत्युंजय%');
                })->first();
            } elseif ($intent === 'planetary_dosha') {
                $puja = (clone $pujaQuery)->where(function ($q) {
                    $q->where('puja_title', 'like', '%Navagraha%')
                      ->orWhere('puja_title', 'like', '%Kaal Sarp%')
                      ->orWhere('puja_title', 'like', '%Dosh%')
                      ->orWhere('puja_title', 'like', '%कालसर्प%');
                })->first();
            } else {
                $puja = (clone $pujaQuery)->first();
            }

            if (!$puja) {
                $puja = Puja::where('isAdminApproved', 1)->first() ?? Puja::first();
            }

            if ($puja) {
                $recommendedPuja = [
                    'id' => $puja->id,
                    'title' => $puja->puja_title,
                    'place' => $puja->puja_place ?? 'वाराणसी / उज्जैन सिद्ध तीर्थ',
                    'price' => (float) ($puja->puja_price ?? 2100),
                    'booking_url' => url('/puja-details/' . ($puja->slug ?? $puja->id)),
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('Puja match query warning: ' . $e->getMessage());
        }

        if (!$recommendedPuja) {
            $recommendedPuja = [
                'id' => 0,
                'title' => ($intent === 'marriage_mangal_dosha') 
                    ? 'विशेष मंगल दोष निवारण एवं शीघ्र विवाह महापूजा' 
                    : 'सर्व दोष निवारण एवं मनोकामना पूर्ति महापूजा',
                'place' => 'उज्जैन महाकालेश्वर / विंध्याचल सिद्ध पीठ',
                'price' => 2100,
                'booking_url' => url('/puja-list'),
            ];
        }

        // Fast, high-accuracy Vedic Jyotish knowledge base (ensures response in < 0.2s without hanging)
        $reply = null;
        if ($intent === 'marriage_mangal_dosha') {
            $reply = "🙏 ॐ नमः शिवाय! आयुष्मान भव:। विवाह में विलंब अथवा बाधा प्रायः कुंडली के लग्न, चतुर्थ, सप्तम, अष्टम या द्वादश भाव में मंगल के प्रभाव (मंगल दोष) अथवा गुरु-शुक्र की दुर्बलता से उत्पन्न होती है।\n\n" .
                "✨ अचूक शास्त्रोक्त वैदिक उपाय:\n" .
                "१. प्रत्येक मंगलवार को हनुमान जी के समक्ष चमेली के तेल का दीपक प्रज्वलित करें एवं सुंदरकांड अथवा हनुमान चालीसा का पाठ करें।\n" .
                "२. नित्य प्रातः शिवलिंग पर कच्चा दूध व केसर मिश्रित जल अर्पित करें। 'ॐ नमः शिवाय' अथवा 'ॐ गौरीशंकराय नमः' का १०८ बार जप करें।\n" .
                "३. मंगलवार को तांबे के पात्र, मसूर की दाल अथवा लाल वस्त्र का किसी योग्य ब्राह्मण को दान करें।\n\n" .
                "इस दोष के संपूर्ण निवारण हेतु सिद्ध तीर्थ में विद्वान ब्राह्मणों द्वारा वैदिक संकल्प पूजा करवाएं। नीचे दी गई विशेष पूजा से शीघ्र शुभ विवाह के योग बनते हैं।";
        } elseif ($intent === 'career_wealth_vyapar') {
            $reply = "🙏 ॐ गं गणपतये नमः! कल्याणमस्तु। व्यापार अथवा आजीविका में उतार-चढ़ाव दशम भाव अथवा बुध-गुरु-शनि की स्थिति से प्रभावित होता है।\n\n" .
                "✨ व्यापार वृद्धि एवं धन लाभ उपाय:\n" .
                "१. प्रत्येक बुधवार को श्री गणेश जी को २१ दूर्वा व बेसन के लड्डू अर्पित करें।\n" .
                "२. अपने व्यावसायिक स्थल पर नित्य कनकधारा स्तोत्र अथवा श्री सूक्त का पाठ करें।\n" .
                "३. गौमाता को हरा चारा व गुड़ नित्य या बुधवार को अवश्य खिलाएं।\n\n" .
                "OnlinePuja द्वारा संपन्न सिद्ध महालक्ष्मी-कुबेर महापूजा से व्यापार में निरंतर लाभ और आर्थिक बाधाओं से मुक्ति के मार्ग प्रशस्त होते हैं।";
        } elseif ($intent === 'planetary_dosha') {
            $reply = "🙏 ॐ नमः शिवाय! हर हर महादेव। राहु, केतु, शनि अथवा कालसर्प योग के कारण जीवन में अनपेक्षित संघर्ष और अशांति उत्पन्न होती है।\n\n" .
                "✨ ग्रह शांति एवं कालसर्प दोष उपाय:\n" .
                "१. शनिवार को पीपल के वृक्ष के नीचे सरसों अथवा तिल के तेल का चौमुखा दीपक जलाएं।\n" .
                "२. 'ॐ शं शनैश्चराय नमः' तथा 'ॐ रां राहवे नमः' मंत्र का नियमित जप करें।\n" .
                "३. शनिवार को काले तिल, उड़द की दाल अथवा कंबल का जरूरतमंदों को दान करें।\n\n" .
                "नवग्रह एवं कालसर्प दोष की संपूर्ण शांति हेतु महाकालेश्वर अथवा काशी में वैदिक नवग्रह शांति महापूजा का संकल्प लें।";
        } elseif ($intent === 'health_mrityunjaya') {
            $reply = "🙏 ॐ त्र्यम्बकं यजामहे सुगन्धिं पुष्टिवर्धनम्। उर्वारुकमिव बन्धनान्मृत्योर्मुक्षीय माऽमृतात्॥\n\n" .
                "कल्याणमस्तु! स्वास्थ्य रक्षा एवं दीर्घायु हेतु भगवान शिव की आराधना सर्वोपरि है।\n\n" .
                "✨ स्वास्थ्य लाभ उपाय:\n" .
                "१. प्रतिदिन प्रातः महामृत्युंजय मंत्र का कम से कम ११ अथवा १०८ बार जप करें।\n" .
                "२. सोमवार को शिवलिंग पर जल और बेलपत्र अर्पित करें।\n" .
                "३. रोगियों की सेवा करें तथा यथाशक्ति औषध दान करें।\n\n" .
                "गंभीर स्वास्थ्य व्याधियों की शांति हेतु OnlinePuja के माध्यम से महामृत्युंजय महायज्ञ का संकल्प लें।";
        } elseif ($intent === 'muhurat_panchang') {
            $reply = "🙏 ॐ सूर्याय नमः! आज का दिन अत्यंत पावन है।\n\n" .
                "✨ शुभ मुहूर्त निर्देश:\n" .
                "• अभिजित मुहूर्त: दोपहर ११:४५ से १२:३० तक (समस्त शुभ कार्यों हेतु श्रेष्ठतम)।\n" .
                "• अमृत एवं शुभ चौघड़िया: प्रातःकाल तथा दोपहर में नए कार्य प्रारंभ करने हेतु शुभ हैं।\n" .
                "• राहु काल: सायं काल के समय कोई भी नवीन कार्य या संकल्प न लें।\n\n" .
                "किसी भी विशेष धार्मिक अनुष्ठान अथवा गृह प्रवेश के लिए OnlinePuja पर व्यक्तिगत वैदिक मुहूर्त परामर्श उपलब्ध है।";
        } else {
            $reply = "🙏 ॐ नमः शिवाय! आयुष्मान भव:। आपके प्रश्न का वैदिक शास्त्रों एवं ग्रह स्थिति के अनुसार समाधान किया जा सकता है।\n\n" .
                "✨ नित्य कल्याणकारी उपाय:\n" .
                "१. प्रतिदिन सूर्योदय के समय तांबे के लोटे से सूर्य देव को कुमकुम मिश्रित जल अर्पित करें।\n" .
                "२. 'ॐ नमः शिवाय' महामंत्र का नियमित रुद्राक्ष माला से जप करें।\n" .
                "३. गौमाता को पहली रोटी खिलाएं और बुजुर्गों का आशीर्वाद लें।\n\n" .
                "समस्त विघ्नों के निवारण हेतु सिद्ध वैदिक पूजा अत्यंत फलदायी है। नीचे दी गई विशेष पूजा का संकल्प लेकर अपनी मनोकामना पूर्ण करें।";
        }

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => [
                'reply_text' => $reply,
                'intent' => $intent,
                'recommended_puja' => $recommendedPuja,
            ]
        ], 200);
    }
}
