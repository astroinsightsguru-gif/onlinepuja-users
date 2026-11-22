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

        // Devotee profile context (DOB, Time, Place) if provided
        $dob = trim((string) ($request->input('dob') ?? ''));
        $tob = trim((string) ($request->input('tob') ?? ''));
        $pob = trim((string) ($request->input('pob') ?? ''));
        $name = trim((string) ($request->input('name') ?? 'भक्त'));

        if (empty($message)) {
            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => [
                    'reply_text' => "🙏 ॐ नमः शिवाय! आयुष्मान भव!\nमैं पंडित जी (OnlinePuja AI) हूँ। अपनी कुंडली, विवाह, व्यापार, स्वास्थ्य अथवा किसी भी ग्रह दोष के निवारण हेतु पूछें।",
                    'intent' => 'greeting',
                    'recommended_puja' => null,
                    'recommended_gemstone' => null,
                    'mantra' => "ॐ नमः शिवाय",
                    'suggested_followups' => [
                        "💍 विवाह में बाधा & मंगल दोष उपाय",
                        "💼 व्यापार वृद्धि & धन लाभ के उपाय",
                        "🐍 कालसर्प व नवग्रह शांति उपाय",
                        "✨ आज का शुभ मुहूर्त क्या है?",
                    ]
                ]
            ], 200);
        }

        // Multi-domain Vedic intent detection
        $intent = 'general';
        $recommendedPuja = null;
        $recommendedGemstone = null;
        $mantra = "ॐ नमः शिवाय";
        $suggestedFollowups = [];
        $lowerMsg = mb_strtolower($message, 'UTF-8');

        // 1. Marriage / Mangal Dosha
        if (preg_match('/(विवाह|शादी|मंगल|दोष|रिश्ता|लड़की|लड़का|सगाई|marriage|mangal|vivah|match|kundali|manglik)/iu', $lowerMsg)) {
            $intent = 'marriage_mangal_dosha';
            $recommendedGemstone = [
                'name' => 'त्रिकोणीय रक्त मूंगा (Italian Red Coral)',
                'metal' => 'तांबे अथवा अष्टधातु की अंगूठी',
                'wearing_day' => 'मंगलवार प्रातः सूर्योदय काल में अनामिका (Ring finger) अंगुली में',
                'benefit' => 'मंगल के उग्र प्रभाव को शांत कर वैवाहिक बाधाओं को दूर करता है।'
            ];
            $mantra = "ॐ क्रां क्रीं क्रौं सः भौमाय नमः ॥ अथवा ॐ हं हनुमते नमः ॥";
            $suggestedFollowups = [
                "📿 मंगल दोष शांति का सिद्ध मंत्र क्या है?",
                "💍 क्या मुझे मूंगा रत्न धारण करना चाहिए?",
                "📞 ज्योतिषी से 1-on-1 कुंडली मिलान कराएं",
                "✨ विवाह के लिए शुभ मुहूर्त कब है?"
            ];
        }
        // 2. Career / Wealth / Vyapar / Money
        elseif (preg_match('/(व्यापार|धन|पैसा|नौकरी|बिजनेस|घाटा|business|career|job|money|loss|कर्ज|income|काम|रोजगार|दुकान|बिक्री)/iu', $lowerMsg)) {
            $intent = 'career_wealth_vyapar';
            $recommendedGemstone = [
                'name' => 'शुद्ध पन्ना (Emerald) अथवा कनकधारा यंत्र',
                'metal' => 'स्वर्ण या चांदी की अंगूठी',
                'wearing_day' => 'बुधवार प्रातः कनिष्ठिका (Little finger) अंगुली में',
                'benefit' => 'व्यापारिक बुद्धि, धन आगमन एवं व्यावसायिक रुकावटों को समाप्त करता है।'
            ];
            $mantra = "ॐ श्रीं ह्रीं क्लीं ग्लौं गं गणपतये वर वरद सर्वजनं मे वशमानय स्वाहा ॥";
            $suggestedFollowups = [
                "🏪 व्यापार स्थल पर कौन सा यंत्र स्थापित करें?",
                "💰 कर्ज मुक्ति के लिए क्या उपाय करें?",
                "✨ नया व्यापार शुरू करने का शुभ मुहूर्त?",
                "📞 वरिष्ठ ज्योतिषी से करियर परामर्श लें"
            ];
        }
        // 3. Health / Rog / Long life / Mahamrityunjaya
        elseif (preg_match('/(रोग|बीमारी|स्वास्थ्य|health|pain|illness|मृत्युंजय|आयु|तबीयत|कष्ट|अस्पताल|दवा)/iu', $lowerMsg)) {
            $intent = 'health_mrityunjaya';
            $recommendedGemstone = [
                'name' => 'सिद्ध पंचमुखी रुद्राक्ष अथवा पारद शिवलिंग',
                'metal' => 'लाल धागे अथवा चांदी में',
                'wearing_day' => 'सोमवार प्रातः शिवलिंग पर स्पर्श कराकर',
                'benefit' => 'अकाल मृत्यु भय, शारीरिक व्याधि व मानसिक तनाव से अचूक सुरक्षा।'
            ];
            $mantra = "ॐ त्र्यम्बकं यजामहे सुगन्धिं पुष्टिवर्धनम्। उर्वारुकमिव बन्धनान्मृत्योर्मुक्षीय माऽमृतात्॥";
            $suggestedFollowups = [
                "🔱 महामृत्युंजय मंत्र जप की सही विधि क्या है?",
                "🌿 आरोग्य प्राप्ति हेतु सोमवार को क्या दान करें?",
                "📞 ज्योतिषी से रोग शांति मार्गदर्शन लें"
            ];
        }
        // 4. Kaal Sarp / Rahu / Ketu / Shani / Sade Sati
        elseif (preg_match('/(कालसर्प|राहु|केतु|शनि|shani|rahu|ketu|kaal\s*sarp|sadhesati|साढ़ेसाती|ग्रह|पीड़ा|नजर|टोटके)/iu', $lowerMsg)) {
            $intent = 'planetary_dosha';
            $recommendedGemstone = [
                'name' => 'नीलमणि (Amethyst / जामुनिया) अथवा अष्टमुखी रुद्राक्ष',
                'metal' => 'पंचधातु या लोहे का छल्ला',
                'wearing_day' => 'शनिवार सायं काल मध्यमा (Middle finger) अंगुली में',
                'benefit' => 'शनि की साढ़ेसाती, ढैय्या एवं राहु-केतु के दुष्प्रभावों का शमन।'
            ];
            $mantra = "ॐ शं शनैश्चराय नमः ॥ एवं ॐ रां राहवे नमः ॥";
            $suggestedFollowups = [
                "🐍 कालसर्प दोष के मुख्य लक्षण क्या हैं?",
                "🪐 साढ़ेसाती से बचने के सरल घरेलू उपाय",
                "📞 कुंडली में ग्रह स्थिति की जांच करवाएं"
            ];
        }
        // 5. Muhurat / Panchang / Choghadiya
        elseif (preg_match('/(मुहूर्त|चौघड़िया|पंचांग|तिथी|शुभ|time|today|muhurat|panchang|दिन|वार)/iu', $lowerMsg)) {
            $intent = 'muhurat_panchang';
            $mantra = "ॐ सूर्याय नमः ॥";
            $suggestedFollowups = [
                "✨ आज का राहु काल कब से कब तक है?",
                "🚗 वाहन या संपत्ति क्रय का शुभ समय?",
                "💍 विवाह एवं गृह प्रवेश के आगामी मुहूर्त"
            ];
        }
        // 6. Santan / Childbirth
        elseif (preg_match('/(संतान|पुत्र|संतानहीनता|गर्भ|बच्चा|child|baby|santan)/iu', $lowerMsg)) {
            $intent = 'santan_gopal';
            $mantra = "ॐ श्रीं ह्रीं क्लीं ग्लौं देवकीसुत गोविन्द वासुदेव जगत्पते। देहि मे तनयं कृष्ण त्वामहं शरणं गतः॥";
            $suggestedFollowups = [
                "📿 संतान गोपाल मंत्र जप के नियम",
                "✨ संतान सुख हेतु कौन सा व्रत रखें?",
                "📞 कुंडली के पंचम भाव का विश्लेषण कराएं"
            ];
        }
        else {
            $mantra = "ॐ नमो भगवते वासुदेवाय ॥";
            $suggestedFollowups = [
                "💍 विवाह में बाधा के उपाय",
                "💼 व्यापार व नौकरी में उन्नति",
                "✨ आज का शुभ मुहूर्त",
                "📞 वरिष्ठ ज्योतिषी से बात करें (₹15/min)"
            ];
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
                'id' => 14,
                'title' => ($intent === 'marriage_mangal_dosha') 
                    ? 'Tulsi Vivah & Mangal Dosh Nivaran Maha Puja' 
                    : 'Shree Lakshmi Kubera Maha Dhan Labh Puja',
                'place' => 'उज्जैन / विंध्याचल सिद्ध पीठ',
                'price' => 2100,
                'booking_url' => url('/puja-list'),
            ];
        }

        // Fast, high-accuracy Vedic Jyotish knowledge base (ensures response in < 0.2s without hanging)
        $kundaliNote = "";
        if (!empty($dob)) {
            $kundaliNote = "\n\n📋 *आपके दर्ज जन्म विवरण के आधार पर:* (जन्म: {$dob}" . (!empty($tob) ? ", समय: {$tob}" : "") . (!empty($pob) ? ", स्थान: {$pob}" : "") . ")\nग्रह स्थिति का सूक्ष्म प्रभाव आपके जीवन पर दृष्टिगोचर हो रहा है। व्यक्तिगत कुण्डली चार्ट हेतु ज्योतिषी से लाइव संवाद करें।";
        }

        if ($intent === 'marriage_mangal_dosha') {
            $reply = "🙏 ॐ नमः शिवाय! आयुष्मान भव:। विवाह में विलंब अथवा बाधा प्रायः कुंडली के लग्न, चतुर्थ, सप्तम, अष्टम या द्वादश भाव में मंगल के प्रभाव (मंगल दोष) अथवा गुरु-शुक्र की दुर्बलता से उत्पन्न होती है।{$kundaliNote}\n\n" .
                "✨ अचूक शास्त्रोक्त वैदिक उपाय:\n" .
                "१. प्रत्येक मंगलवार को हनुमान जी के समक्ष चमेली के तेल का दीपक प्रज्वलित करें एवं सुंदरकांड अथवा हनुमान चालीसा का पाठ करें।\n" .
                "२. नित्य प्रातः शिवलिंग पर कच्चा दूध व केसर मिश्रित जल अर्पित करें। 'ॐ नमः शिवाय' अथवा 'ॐ गौरीशंकराय नमः' का १०८ बार जप करें।\n" .
                "३. मंगलवार को तांबे के पात्र, मसूर की दाल अथवा लाल वस्त्र का किसी योग्य ब्राह्मण को दान करें।\n\n" .
                "इस दोष के संपूर्ण निवारण हेतु सिद्ध तीर्थ में विद्वान ब्राह्मणों द्वारा वैदिक संकल्प पूजा करवाएं। नीचे दी गई विशेष पूजा से शीघ्र शुभ विवाह के योग बनते हैं।";
        } elseif ($intent === 'career_wealth_vyapar') {
            $reply = "🙏 ॐ गं गणपतये नमः! कल्याणमस्तु। व्यापार अथवा आजीविका में उतार-चढ़ाव दशम भाव अथवा बुध-गुरु-शनि की स्थिति से प्रभावित होता है।{$kundaliNote}\n\n" .
                "✨ व्यापार वृद्धि एवं धन लाभ उपाय:\n" .
                "१. प्रत्येक बुधवार को श्री गणेश जी को २१ दूर्वा व बेसन के लड्डू अर्पित करें।\n" .
                "२. अपने व्यावसायिक स्थल पर नित्य कनकधारा स्तोत्र अथवा श्री सूक्त का पाठ करें।\n" .
                "३. गौमाता को हरा चारा व गुड़ नित्य या बुधवार को अवश्य खिलाएं।\n" .
                "४. शनिवार की शाम व्यावसायिक प्रतिष्ठान में गुग्गल व लोबान की धूप दें।\n\n" .
                "OnlinePuja द्वारा संपन्न सिद्ध महालक्ष्मी-कुबेर महापूजा से व्यापार में निरंतर लाभ और आर्थिक बाधाओं से मुक्ति के मार्ग प्रशस्त होते हैं।";
        } elseif ($intent === 'planetary_dosha') {
            $reply = "🙏 ॐ नमः शिवाय! हर हर महादेव। राहु, केतु, शनि अथवा कालसर्प योग के कारण जीवन में अनपेक्षित संघर्ष और अशांति उत्पन्न होती है।{$kundaliNote}\n\n" .
                "✨ ग्रह शांति एवं कालसर्प दोष उपाय:\n" .
                "१. शनिवार को पीपल के वृक्ष के नीचे सरसों अथवा तिल के तेल का चौमुखा दीपक जलाएं।\n" .
                "२. 'ॐ शं शनैश्चराय नमः' तथा 'ॐ रां राहवे नमः' मंत्र का नियमित जप करें।\n" .
                "३. शनिवार को काले तिल, उड़द की दाल अथवा कंबल का जरूरतमंदों को दान करें।\n" .
                "४. पक्षियों को प्रतिदिन सात प्रकार का अनाज (सप्तधान्य) डालें।\n\n" .
                "नवग्रह एवं कालसर्प दोष की संपूर्ण शांति हेतु महाकालेश्वर अथवा काशी में वैदिक नवग्रह शांति महापूजा का संकल्प लें।";
        } elseif ($intent === 'health_mrityunjaya') {
            $reply = "🙏 ॐ त्र्यम्बकं यजामहे सुगन्धिं पुष्टिवर्धनम्। उर्वारुकमिव बन्धनान्मृत्योर्मुक्षीय माऽमृतात्॥\n\n" .
                "कल्याणमस्तु! स्वास्थ्य रक्षा एवं दीर्घायु हेतु भगवान शिव की आराधना सर्वोपरि है।{$kundaliNote}\n\n" .
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
        } elseif ($intent === 'santan_gopal') {
            $reply = "🙏 ॐ क्लीं कृष्णाय नमः! प्रभु कृपा से आपकी मनोकामना अवश्य पूर्ण हो। संतान प्राप्ति में बाधा प्रायः पंचम भाव में पाप ग्रहों के प्रभाव अथवा पितृ दोष के कारण आती है।{$kundaliNote}\n\n" .
                "✨ संतान सुख हेतु अचूक उपाय:\n" .
                "१. नित्य प्रातः लड्डू गोपाल जी का पंचामृत से अभिषेक करें और माखन-मिश्री का भोग लगाएं।\n" .
                "२. संतान गोपाल स्तोत्र एवं मंत्र का तुलसी की माला से नित्य १०८ बार जप करें।\n" .
                "३. गुरुवार को गाय को चने की दाल और गुड़ खिलाएं।\n\n" .
                "सिद्ध पीठ में विद्वान ब्राह्मणों द्वारा संतान गोपाल महापूजा का संकल्प लेकर विशेष अनुष्ठान करवाएं।";
        } else {
            $reply = "🙏 ॐ नमः शिवाय! आयुष्मान भव:। आपके प्रश्न का वैदिक शास्त्रों एवं ग्रह स्थिति के अनुसार समाधान किया जा सकता है।{$kundaliNote}\n\n" .
                "✨ नित्य कल्याणकारी उपाय:\n" .
                "१. प्रतिदिन सूर्योदय के समय तांबे के लोटे से सूर्य देव को कुमकुम मिश्रित जल अर्पित करें।\n" .
                "२. 'ॐ नमः शिवाय' महामंत्र का नियमित रुद्राक्ष माला से जप करें।\n" .
                "३. गौमाता को पहली रोटी खिलाएं और बुजुर्गों का आशीर्वाद लें।\n\n" .
                "समस्त विघ्नों के निवारण हेतु सिद्ध वैदिक पूजा अत्यंत फलदायी है। नीचे दी गई विशेष पूजा का संकल्प लेकर अपनी मनोकामना पूर्ण करें।";
        }

        // WhatsApp Share Link
        $cleanShareText = urlencode("🙏 पंडित जी (OnlinePuja AI) द्वारा सुझाए गए वैदिक उपाय:\n\n" . mb_substr($reply, 0, 300) . "\n...\nपूरा समाधान देखें: " . url('/'));
        $whatsappShareUrl = "https://api.whatsapp.com/send?text=" . $cleanShareText;

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => [
                'reply_text' => $reply,
                'intent' => $intent,
                'recommended_puja' => $recommendedPuja,
                'recommended_gemstone' => $recommendedGemstone,
                'mantra' => $mantra,
                'suggested_followups' => $suggestedFollowups,
                'whatsapp_share_url' => $whatsappShareUrl,
                'astrologer_url' => url('/astrologers'),
            ]
        ], 200);
    }
}
