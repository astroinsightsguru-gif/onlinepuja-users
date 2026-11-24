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
     * Calculate Rashi and planetary ruler from Date of Birth.
     */
    protected function calculateVedicProfile(?string $dob): ?array
    {
        if (empty($dob)) return null;
        try {
            $date = \Carbon\Carbon::parse($dob);
            $month = $date->month;
            $day = $date->day;

            $signs = [
                ['name' => 'मेष (Aries)', 'ruler' => 'मंगल देव', 'element' => 'अग्नि', 'lucky_stone' => 'मूंगा', 'lucky_day' => 'मंगलवार', 'mantra' => 'ॐ भौमाय नमः'],
                ['name' => 'वृषभ (Taurus)', 'ruler' => 'शुक्र देव', 'element' => 'पृथ्वी', 'lucky_stone' => 'हीरा / ओपल', 'lucky_day' => 'शुक्रवार', 'mantra' => 'ॐ शुं शुक्राय नमः'],
                ['name' => 'मिथुन (Gemini)', 'ruler' => 'बुध देव', 'element' => 'वायु', 'lucky_stone' => 'पन्ना', 'lucky_day' => 'बुधवार', 'mantra' => 'ॐ बुं बुधाय नमः'],
                ['name' => 'कर्क (Cancer)', 'ruler' => 'चंद्र देव', 'element' => 'जल', 'lucky_stone' => 'मोती', 'lucky_day' => 'सोमवार', 'mantra' => 'ॐ सों सोमाय नमः'],
                ['name' => 'सिंह (Leo)', 'ruler' => 'सूर्य देव', 'element' => 'अग्नि', 'lucky_stone' => 'माणिक्य (Ruby)', 'lucky_day' => 'रविवार', 'mantra' => 'ॐ घृणि सूर्याय नमः'],
                ['name' => 'कन्या (Virgo)', 'ruler' => 'बुध देव', 'element' => 'पृथ्वी', 'lucky_stone' => 'पन्ना', 'lucky_day' => 'बुधवार', 'mantra' => 'ॐ बुं बुधाय नमः'],
                ['name' => 'तुला (Libra)', 'ruler' => 'शुक्र देव', 'element' => 'वायु', 'lucky_stone' => 'ओपल / हीरा', 'lucky_day' => 'शुक्रवार', 'mantra' => 'ॐ शुं शुक्राय नमः'],
                ['name' => 'वृश्चिक (Scorpio)', 'ruler' => 'मंगल देव', 'element' => 'जल', 'lucky_stone' => 'त्रिकोणीय मूंगा', 'lucky_day' => 'मंगलवार', 'mantra' => 'ॐ भौमाय नमः'],
                ['name' => 'धनु (Sagittarius)', 'ruler' => 'बृहस्पति (गुरु) देव', 'element' => 'अग्नि', 'lucky_stone' => 'पुखराज (Yellow Sapphire)', 'lucky_day' => 'गुरुवार', 'mantra' => 'ॐ बृं बृहस्पतये नमः'],
                ['name' => 'मकर (Capricorn)', 'ruler' => 'शनि देव', 'element' => 'पृथ्वी', 'lucky_stone' => 'नीलम / नीली', 'lucky_day' => 'शनिवार', 'mantra' => 'ॐ शं शनैश्चराय नमः'],
                ['name' => 'कुंभ (Aquarius)', 'ruler' => 'शनि देव', 'element' => 'वायु', 'lucky_stone' => 'नीलम / जामुनिया', 'lucky_day' => 'शनिवार', 'mantra' => 'ॐ शं शनैश्चराय नमः'],
                ['name' => 'मीन (Pisces)', 'ruler' => 'बृहस्पति (गुरु) देव', 'element' => 'जल', 'lucky_stone' => 'पीला पुखराज', 'lucky_day' => 'गुरुवार', 'mantra' => 'ॐ ग्रां ग्रीं ग्रौं सः गुरवे नमः'],
            ];

            if (($month == 3 && $day >= 21) || ($month == 4 && $day <= 19)) $r = $signs[0];
            elseif (($month == 4 && $day >= 20) || ($month == 5 && $day <= 20)) $r = $signs[1];
            elseif (($month == 5 && $day >= 21) || ($month == 6 && $day <= 20)) $r = $signs[2];
            elseif (($month == 6 && $day >= 21) || ($month == 7 && $day <= 22)) $r = $signs[3];
            elseif (($month == 7 && $day >= 23) || ($month == 8 && $day <= 22)) $r = $signs[4];
            elseif (($month == 8 && $day >= 23) || ($month == 9 && $day <= 22)) $r = $signs[5];
            elseif (($month == 9 && $day >= 23) || ($month == 10 && $day <= 22)) $r = $signs[6];
            elseif (($month == 10 && $day >= 23) || ($month == 11 && $day <= 21)) $r = $signs[7];
            elseif (($month == 11 && $day >= 22) || ($month == 12 && $day <= 21)) $r = $signs[8];
            elseif (($month == 12 && $day >= 22) || ($month == 1 && $day <= 19)) $r = $signs[9];
            elseif (($month == 1 && $day >= 20) || ($month == 2 && $day <= 18)) $r = $signs[10];
            else $r = $signs[11];

            return $r;
        } catch (\Throwable $e) {
            return null;
        }
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

        $vedicProfile = $this->calculateVedicProfile($dob);

        if (empty($message)) {
            $greetingText = "🙏 ॐ नमः शिवाय! आयुष्मान भव!\nमैं पंडित जी (OnlinePuja AI) हूँ।";
            if ($vedicProfile) {
                $greetingText .= " आपकी राशि " . $vedicProfile['name'] . " एवं स्वामी " . $vedicProfile['ruler'] . " हैं।";
            }
            $greetingText .= " अपनी कुंडली, विवाह, व्यापार, स्वास्थ्य, राहु-शनि अथवा किसी भी ग्रह दोष के निवारण हेतु पूछें।";

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => [
                    'reply_text' => $greetingText,
                    'intent' => 'greeting',
                    'vedic_profile' => $vedicProfile,
                    'recommended_puja' => null,
                    'recommended_gemstone' => null,
                    'mantra' => "ॐ नमः शिवाय",
                    'suggested_followups' => [
                        "💍 विवाह में बाधा & मंगल दोष उपाय",
                        "💼 व्यापार वृद्धि & धन लाभ के सरल उपाय",
                        "🐍 कालसर्प व नवग्रह शांति उपाय",
                        "✨ आज का शुभ मुहूर्त क्या है?",
                    ]
                ]
            ], 200);
        }

        // Multi-domain Vedic intent detection
        $intent = 'general';
        $recommendedGemstone = null;
        $mantra = "ॐ नमः शिवाय";
        $suggestedFollowups = [];
        $lowerMsg = mb_strtolower($message, 'UTF-8');

        // 1. Marriage / Mangal Dosha
        if (preg_match('/(विवाह|शादी|मंगल|दोष|रिश्ता|लड़की|लड़का|सगाई|वर|वधू|गठबंधन|सप्तम|marriage|mangal|vivah|match|kundali|manglik|late\s*marriage)/iu', $lowerMsg)) {
            $intent = 'marriage_mangal_dosha';
            $recommendedGemstone = [
                'name' => 'त्रिकोणीय रक्त मूंगा (Italian Red Coral)',
                'metal' => 'तांबे अथवा अष्टधातु की अंगूठी',
                'wearing_day' => 'मंगलवार प्रातः सूर्योदय काल में अनामिका (Ring finger) में',
                'benefit' => 'मंगल के उग्र प्रभाव को शांत कर वैवाहिक बाधाओं को दूर करता है एवं दांपत्य जीवन सुखी बनाता है।'
            ];
            $mantra = "ॐ क्रां क्रीं क्रौं सः भौमाय नमः ॥ अथवा ॐ कात्यायनि महामाये महायोगिन्यधीश्वरि । नन्दगोपसुतं देवि पतिं मे कुरु ते नमः ॥";
            $suggestedFollowups = [
                "📿 मंगल दोष शांति का सिद्ध मंत्र जप विधि?",
                "💍 क्या मुझे मूंगा या पुखराज पहनना चाहिए?",
                "📞 ज्योतिषी से 1-on-1 कुंडली मिलान कराएं",
                "✨ विवाह के लिए आगामी शुभ मुहूर्त कब हैं?"
            ];
        }
        // 2. Career / Wealth / Vyapar / Money
        elseif (preg_match('/(व्यापार|धन|पैसा|नौकरी|बिजनेस|घाटा|business|career|job|money|loss|कर्ज|income|काम|रोजगार|दुकान|बिक्री|प्रमोशन|salary|उन्नति|आर्थिक)/iu', $lowerMsg)) {
            $intent = 'career_wealth_vyapar';
            $recommendedGemstone = [
                'name' => 'शुद्ध पन्ना (Emerald) अथवा कनकधारा श्रीयंत्र',
                'metal' => 'स्वर्ण या चांदी की अंगूठी',
                'wearing_day' => 'बुधवार प्रातः कनिष्ठिका (Little finger) अंगुली में',
                'benefit' => 'व्यापारिक बुद्धि, धन आगमन, ग्राहक वृद्धि एवं ऋण से मुक्ति प्रदान करता है।'
            ];
            $mantra = "ॐ श्रीं ह्रीं क्लीं ग्लौं गं गणपतये वर वरद सर्वजनं मे वशमानय स्वाहा ॥ एवं ॐ श्रीं श्रियै नमः ॥";
            $suggestedFollowups = [
                "🏪 व्यापार स्थल पर कौन सा यंत्र स्थापित करें?",
                "💰 कर्ज मुक्ति के अचूक शास्त्रोक्त उपाय",
                "✨ नया व्यापार शुरू करने का शुभ मुहूर्त?",
                "📞 वरिष्ठ ज्योतिषी से करियर परामर्श लें"
            ];
        }
        // 3. Health / Rog / Long life / Mahamrityunjaya
        elseif (preg_match('/(रोग|बीमारी|स्वास्थ्य|health|pain|illness|मृत्युंजय|आयु|तबीयत|कष्ट|अस्पताल|दवा|operation|मानसिक|अवसाद|tension)/iu', $lowerMsg)) {
            $intent = 'health_mrityunjaya';
            $recommendedGemstone = [
                'name' => 'सिद्ध पंचमुखी रुद्राक्ष माला अथवा पारद शिवलिंग',
                'metal' => 'लाल रेशमी धागे अथवा चांदी में',
                'wearing_day' => 'सोमवार प्रातः शिवलिंग पर स्पर्श कराकर धारण करें',
                'benefit' => 'अकाल मृत्यु भय, शारीरिक व्याधि, मानसिक अशांति व नकारात्मक ऊर्जा से अचूक रक्षा।'
            ];
            $mantra = "ॐ त्र्यम्बकं यजामहे सुगन्धिं पुष्टिवर्धनम्। उर्वारुकमिव बन्धनान्मृत्योर्मुक्षीय माऽमृतात्॥";
            $suggestedFollowups = [
                "🔱 महामृत्युंजय मंत्र जप की संपूर्ण विधि क्या है?",
                "🌿 आरोग्य प्राप्ति हेतु सोमवार को क्या दान करें?",
                "🌿 मानसिक शांति हेतु कौन सा रुद्राक्ष पहनें?",
                "📞 ज्योतिषी से रोग शांति एवं स्वास्थ्य मार्गदर्शन लें"
            ];
        }
        // 4. Kaal Sarp / Rahu / Ketu / Shani / Sade Sati
        elseif (preg_match('/(कालसर्प|राहु|केतु|शनि|shani|rahu|ketu|kaal\s*sarp|sadhesati|साढ़ेसाती|ढैय्या|ग्रह|पीड़ा|शनिदेव|शनिश्चर)/iu', $lowerMsg)) {
            $intent = 'planetary_dosha';
            $recommendedGemstone = [
                'name' => 'नीलमणि (Amethyst / जामुनिया) अथवा अष्टमुखी रुद्राक्ष',
                'metal' => 'पंचधातु या काले लोहे का छल्ला',
                'wearing_day' => 'शनिवार सायं काल मध्यमा (Middle finger) अंगुली में',
                'benefit' => 'शनि की साढ़ेसाती, ढैय्या एवं राहु-केतु के क्रूर दुष्प्रभावों का तत्काल शमन।'
            ];
            $mantra = "ॐ शं शनैश्चराय नमः ॥ एवं ॐ भ्रां भ्रीं भ्रौं सः राहवे नमः ॥";
            $suggestedFollowups = [
                "🐍 कालसर्प दोष के मुख्य लक्षण व संकेत क्या हैं?",
                "🪐 साढ़ेसाती से बचने के सरल घरेलू उपाय",
                "🪔 शनिवार को पीपल पूजन की सही विधि",
                "📞 कुंडली में नवग्रह स्थिति की जांच करवाएं"
            ];
        }
        // 5. Santan / Childbirth
        elseif (preg_match('/(संतान|पुत्र|संतानहीनता|गर्भ|बच्चा|child|baby|santan|पुत्री|वंश)/iu', $lowerMsg)) {
            $intent = 'santan_gopal';
            $recommendedGemstone = [
                'name' => 'संतान गोपाल यंत्र अथवा पीला पुखराज (Yellow Sapphire)',
                'metal' => 'स्वर्ण या पंचधातु',
                'wearing_day' => 'गुरुवार प्रातः तर्जनी अंगुली में',
                'benefit' => 'पंचम भाव के दोषों का निवारण कर स्वस्थ, तेजस्वी एवं दीर्घायु संतान का योग निर्मित करता है।'
            ];
            $mantra = "ॐ श्रीं ह्रीं क्लीं ग्लौं देवकीसुत गोविन्द वासुदेव जगत्पते। देहि मे तनयं कृष्ण त्वामहं शरणं गतः॥";
            $suggestedFollowups = [
                "📿 संतान गोपाल मंत्र जप के शास्त्रोक्त नियम",
                "✨ संतान सुख हेतु कौन सा व्रत रखना चाहिए?",
                "🌿 पुत्रदा एकादशी व्रत की विधि",
                "📞 कुंडली के पंचम भाव का विशेष विश्लेषण कराएं"
            ];
        }
        // 6. Education / Exam / Memory / Studies
        elseif (preg_match('/(शिक्षा|पढ़ाई|परीक्षा|exam|study|विद्या|memory|एकाग्रता|रिजल्ट|career|बुद्धि|इंटरव्यू)/iu', $lowerMsg)) {
            $intent = 'education_saraswati';
            $recommendedGemstone = [
                'name' => 'चार मुखी रुद्राक्ष अथवा सिद्ध सरस्वती यंत्र',
                'metal' => 'पीले धागे में गले में धारण करें',
                'wearing_day' => 'गुरुवार अथवा बुधवार प्रातःकाल',
                'benefit' => 'स्मरण शक्ति, एकाग्रता, बौद्धिक कुशाग्रता एवं परीक्षाओं में शीर्ष सफलता दिलाता है।'
            ];
            $mantra = "ॐ ऐं सरस्वत्यै नमः ॥ एवं ॐ भूर्भुवः स्वः तत्सवितुर्वरेण्यं भर्गो देवस्य धीमहि धियो यो नः प्रचोदयात्॥";
            $suggestedFollowups = [
                "📖 पढ़ते समय किस दिशा में मुख रखना चाहिए?",
                "🧠 एकाग्रता बढ़ाने के लिए सरल वैदिक उपाय",
                "✨ परीक्षा में सफलता हेतु सरस्वती कवच",
                "📞 ज्योतिषी से शिक्षा व करियर भाव का परामर्श लें"
            ];
        }
        // 7. Nazar / Evil Eye / Negative Energy / Fear
        elseif (preg_match('/(नजर|टोटके|दोष|डर|भय|नकारात्मक|negative|ऊपरी|प्रेत|जादू|protection|सुरक्षा)/iu', $lowerMsg)) {
            $intent = 'protection_nazar';
            $recommendedGemstone = [
                'name' => 'सिद्ध हनुमान रक्षा कवच अथवा पंचमुखी रुद्राक्ष',
                'metal' => 'काले धागे अथवा तांबे के ताबीज में',
                'wearing_day' => 'मंगलवार अथवा शनिवार को हनुमान जी के चरणों का सिंदूर लगाकर',
                'benefit' => 'समस्त बुरी नजर, नकारात्मक ऊर्जा, भय और अज्ञात बाधाओं से अभेद्य सुरक्षा चक्र।'
            ];
            $mantra = "ॐ हं हनुमते रुद्रात्मकाय हुं फट् ॥ एवं ॐ नमो भगवते नरसिंहाय नमस्तेजस्तेजसे आविर्भव आविर्भव महिमहि रक्ष रक्ष स्वाहा ॥";
            $suggestedFollowups = [
                "🧿 नजर दोष उतारने का सबसे सरल घरेलू उपाय",
                "🏠 घर से नकारात्मक ऊर्जा दूर करने के नियम",
                "🪔 मंगलवार को हनुमान अष्टक का पाठ करें",
                "📞 वरिष्ठ पंडित जी से घर की सुरक्षा का मार्गदर्शन लें"
            ];
        }
        // 8. Love / Relationship / Husband-Wife
        elseif (preg_match('/(प्रेम|प्यार|रिश्ता|पति|पत्नी|husband|wife|love|relationship|झगड़ा|क्लेश|विवाद|मनमुटाव)/iu', $lowerMsg)) {
            $intent = 'love_harmony';
            $recommendedGemstone = [
                'name' => 'सिद्ध गौरी शंकर रुद्राक्ष अथवा रोज क्वार्ट्ज',
                'metal' => 'चांदी में पिरोकर गले में धारण करें',
                'wearing_day' => 'शुक्रवार प्रातः शिव-पार्वती मंदिर में पूजनोपरांत',
                'benefit' => 'पति-पत्नी के मध्य अगाध प्रेम, परस्पर समझ एवं वैवाहिक जीवन में शांति स्थापित करता है।'
            ];
            $mantra = "ॐ क्लीं कृष्णाय गोविंदाय गोपीजनवल्लभाय स्वाहा ॥ एवं ॐ गौरीशंकराय नमः ॥";
            $suggestedFollowups = [
                "💖 दांपत्य सुख हेतु शुक्रवार को क्या उपाय करें?",
                "🌸 पति-पत्नी के कलह को समाप्त करने के उपाय",
                "📿 कामदेव व रति मंत्र का जप कैसे करें?",
                "📞 ज्योतिषी से सप्तम भाव व शुक्र ग्रह का विश्लेषण कराएं"
            ];
        }
        // 9. Court / Legal / Enemies
        elseif (preg_match('/(कोर्ट|केस|मुकदमा|शत्रु|enemy|court|case|police|विवाद|दुश्मन)/iu', $lowerMsg)) {
            $intent = 'court_legal';
            $recommendedGemstone = [
                'name' => 'सिद्ध बगलामुखी यंत्र अथवा अष्टधातु कड़ा',
                'metal' => 'पीतल अथवा अष्टधातु',
                'wearing_day' => 'गुरुवार सायं काल पीले वस्त्र धारण कर',
                'benefit' => 'शत्रुओं की वाणी व बुद्धि का स्तंभन कर कोर्ट-कचहरी और विवादों में विजय दिलाता है।'
            ];
            $mantra = "ॐ ह्लीं बगलामुखि सर्वदुष्टानां वाचं मुखं पदं स्तम्भय जिह्वां कीलय बुद्धिं विनाशय ह्लीं ॐ स्वाहा ॥";
            $suggestedFollowups = [
                "⚖️ कोर्ट कचहरी में विजय हेतु अचूक उपाय",
                "🛡️ गुप्त शत्रुओं से रक्षा का बगलामुखी स्तोत्र",
                "📞 ज्योतिषी से षष्ठ भाव (शत्रु भाव) का विश्लेषण कराएं"
            ];
        }
        // 10. Vastu / House
        elseif (preg_match('/(वास्तु|vastu|घर|मकान|house|दिशा|griha|pravesh|दुकान|प्लॉट)/iu', $lowerMsg)) {
            $intent = 'vastu_shanti';
            $recommendedGemstone = [
                'name' => 'सिद्ध वास्तु दोष निवारण यंत्र अथवा स्फटिक श्रीयंत्र',
                'metal' => 'तांबे या भोजपत्र पर प्राण प्रतिष्ठित',
                'wearing_day' => 'पूर्णिमा अथवा गुरुवार प्रातः ईशान कोण में स्थापित करें',
                'benefit' => 'भवन के समस्त दिशा दोष, ऊर्जा असंतुलन व वास्तु दोषों का बिना तोड़-फोड़ निवारण।'
            ];
            $mantra = "ॐ वास्तोष्पते प्रति जानीह्यस्मान् त्स्वावेशो अनमीवो भवा नः । यत् त्वेमहे प्रति तन्नो जुषस्व शं नो भव द्विपदे शं चतुष्पदे ॥";
            $suggestedFollowups = [
                "🏡 घर के ईशान कोण में क्या रखना शुभ है?",
                "🪔 मुख्य द्वार पर कौन सा मांगलिक चिन्ह लगाएं?",
                "📞 विशेषज्ञ वास्तु ज्योतिषी से ऑनलाइन नक्शा दिखवाएं"
            ];
        }
        // 11. Pitra Dosha
        elseif (preg_match('/(पितृ|पितर|pitra|amavasya|तर्पण|श्राद्ध|अमावस्या|पूर्वज)/iu', $lowerMsg)) {
            $intent = 'pitra_dosha';
            $recommendedGemstone = [
                'name' => 'सिद्ध तांबे का सूर्य छल्ला अथवा एकादश मुखी रुद्राक्ष',
                'metal' => 'शुद्ध तांबा',
                'wearing_day' => 'रविवार प्रातः सूर्य देव को अर्घ्य देकर',
                'benefit' => 'पूर्वजों का आशीर्वाद प्राप्त कराकर वंश वृद्धि एवं जीवन की अप्रत्याशित रुकावटें समाप्त करता है।'
            ];
            $mantra = "ॐ देवताभ्यः पितृभ्यश्च महायोगिभ्य एव च । नमः स्वाहायै स्वधायै नित्यमेव नमो नमः ॥";
            $suggestedFollowups = [
                "🕊️ अमावस्या के दिन पितरों के निमित्त क्या दान करें?",
                "📿 पितृ दोष के मुख्य लक्षण एवं स्वप्न संकेत",
                "📞 गया तीर्थ में त्रिपिंडी श्राद्ध संकल्प की जानकारी लें"
            ];
        }
        // 12. Muhurat / Panchang / Choghadiya
        elseif (preg_match('/(मुहूर्त|चौघड़िया|पंचांग|तिथी|शुभ|time|today|muhurat|panchang|दिन|वार|आज)/iu', $lowerMsg)) {
            $intent = 'muhurat_panchang';
            $mantra = "ॐ सूर्याय नमः ॥ एवं ॐ गं गणपतये नमः ॥";
            $suggestedFollowups = [
                "✨ आज का राहु काल कब से कब तक है?",
                "🚗 वाहन या संपत्ति क्रय का श्रेष्ठ चौघड़िया?",
                "💍 आगामी विवाह एवं गृह प्रवेश के शुद्ध मुहूर्त"
            ];
        }
        else {
            $mantra = "ॐ नमो भगवते वासुदेवाय ॥";
            $suggestedFollowups = [
                "💍 विवाह में बाधा व मंगल दोष उपाय",
                "💼 व्यापार व नौकरी में उन्नति के उपाय",
                "✨ आज का शुभ मुहूर्त व राहु काल",
                "📞 वरिष्ठ ज्योतिषी से बात करें (₹15/min)"
            ];
        }

        // Fetch corresponding Puja dynamically from DB
        $recommendedPuja = null;
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
            } elseif ($intent === 'santan_gopal') {
                $puja = (clone $pujaQuery)->where(function ($q) {
                    $q->where('puja_title', 'like', '%Santan%')
                      ->orWhere('puja_title', 'like', '%Gopal%')
                      ->orWhere('puja_title', 'like', '%संतान%');
                })->first();
            } elseif ($intent === 'education_saraswati') {
                $puja = (clone $pujaQuery)->where(function ($q) {
                    $q->where('puja_title', 'like', '%Saraswati%')
                      ->orWhere('puja_title', 'like', '%सरस्वती%')
                      ->orWhere('puja_title', 'like', '%Vidya%');
                })->first();
            } elseif ($intent === 'protection_nazar') {
                $puja = (clone $pujaQuery)->where(function ($q) {
                    $q->where('puja_title', 'like', '%Hanuman%')
                      ->orWhere('puja_title', 'like', '%हनुमान%')
                      ->orWhere('puja_title', 'like', '%Baglamukhi%');
                })->first();
            } elseif ($intent === 'love_harmony') {
                $puja = (clone $pujaQuery)->where(function ($q) {
                    $q->where('puja_title', 'like', '%Gauri%')
                      ->orWhere('puja_title', 'like', '%Shiva%')
                      ->orWhere('puja_title', 'like', '%Radha%');
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
                    : (($intent === 'career_wealth_vyapar') ? 'Shree Lakshmi Kubera Maha Dhan Labh Puja' : 'Vedic Navagraha Shanti Maha Puja'),
                'place' => 'उज्जैन / विंध्याचल सिद्ध पीठ',
                'price' => 2100,
                'booking_url' => url('/puja-list'),
            ];
        }

        // Personalized Vedic Kundali Note
        $kundaliNote = "";
        if (!empty($dob)) {
            $kundaliNote = "\n\n📋 *आपके व्यक्तिगत जन्म विवरण के आधार पर:* (जन्म: {$dob}" . (!empty($tob) ? ", समय: {$tob}" : "") . (!empty($pob) ? ", स्थान: {$pob}" : "") . ")\n";
            if ($vedicProfile) {
                $kundaliNote .= "• आपकी राशि: *{$vedicProfile['name']}* (राशि स्वामी: {$vedicProfile['ruler']})\n" .
                                "• तत्व: {$vedicProfile['element']} | शुभ वार: {$vedicProfile['lucky_day']} | सिद्ध बीज मंत्र: {$vedicProfile['mantra']}\n";
            }
            $kundaliNote .= "ग्रह स्थिति का सीधा प्रभाव आपके वर्तमान जीवन चक्र पर पड़ रहा है।";
        }

        // Fast Shastric Layer (Accurate Vedic Prescription)
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
        } elseif ($intent === 'santan_gopal') {
            $reply = "🙏 ॐ क्लीं कृष्णाय नमः! प्रभु कृपा से आपकी मनोकामना अवश्य पूर्ण हो। संतान प्राप्ति में बाधा प्रायः पंचम भाव में पाप ग्रहों के प्रभाव अथवा पितृ दोष के कारण आती है।{$kundaliNote}\n\n" .
                "✨ संतान सुख हेतु अचूक उपाय:\n" .
                "१. नित्य प्रातः लड्डू गोपाल जी का पंचामृत से अभिषेक करें और माखन-मिश्री का भोग लगाएं।\n" .
                "२. संतान गोपाल स्तोत्र एवं मंत्र का तुलसी की माला से नित्य १०८ बार जप करें।\n" .
                "३. गुरुवार को गाय को चने की दाल और गुड़ खिलाएं।\n\n" .
                "सिद्ध पीठ में विद्वान ब्राह्मणों द्वारा संतान गोपाल महापूजा का संकल्प लेकर विशेष अनुष्ठान करवाएं।";
        } elseif ($intent === 'education_saraswati') {
            $reply = "🙏 ॐ ऐं सरस्वत्यै नमः! मां शारदा आपके ज्ञान व विवेक में वृद्धि करें। शिक्षा एवं एकाग्रता में रुकावट प्रायः पंचम भाव, बुध अथवा गुरु की निर्बलता से होती है।{$kundaliNote}\n\n" .
                "✨ शिक्षा व एकाग्रता वृद्धि उपाय:\n" .
                "१. प्रतिदिन अध्ययन प्रारंभ करने से पूर्व मां सरस्वती के बीज मंत्र 'ॐ ऐं सरस्वत्यै नमः' का ११ बार जप करें।\n" .
                "२. अध्ययन कक्ष में उत्तर या पूर्व दिशा की ओर मुख करके बैठें।\n" .
                "३. बुधवार को हरे फल अथवा अध्ययन सामग्री किसी जरूरतमंद छात्र को दान करें।\n\n" .
                "मां सरस्वती व ज्ञान वृद्धि महापूजा का संकल्प लेकर अपनी विद्या में शीर्ष सफलता सुनिश्चित करें।";
        } elseif ($intent === 'protection_nazar') {
            $reply = "🙏 ॐ हं हनुमते नमः! वीर बजरंगी आपके समस्त संकटों का नाश करें। किसी भी प्रकार की नकारात्मक ऊर्जा, भय या नजर दोष से मुक्ति हेतु हनुमान जी की उपासना अचूक है।{$kundaliNote}\n\n" .
                "✨ रक्षा व नजर दोष निवारण उपाय:\n" .
                "१. प्रतिदिन प्रातः अथवा सायं हनुमान चालीसा व बजरंग बाण का पाठ करें।\n" .
                "२. मंगलवार व शनिवार को हनुमान मंदिर में सिंदूर व चमेली का तेल अर्पित करें।\n" .
                "३. घर के मुख्य द्वार पर नित्य शाम को कपूर व लौंग की धूप दें।\n\n" .
                "समस्त संकटों व नकारात्मक शक्तियों के शमन हेतु OnlinePuja द्वारा संकटमोचन हनुमान महापूजा का संकल्प लें।";
        } elseif ($intent === 'love_harmony') {
            $reply = "🙏 ॐ राधाकृष्णाभ्यां नमः! कल्याणमस्तु। दांपत्य जीवन व आपसी संबंधों में कलह का कारण कुंडली में सप्तम भाव में पाप प्रभाव अथवा शुक्र-गुरु की प्रतिकूलता होती है।{$kundaliNote}\n\n" .
                "✨ प्रेम व दांपत्य मधुरता उपाय:\n" .
                "१. प्रत्येक शुक्रवार को भगवान शिव और माता पार्वती को श्वेत पुष्प व इत्र अर्पित करें।\n" .
                "२. 'ॐ गौरीशंकराय नमः' मंत्र का नियमित १०८ बार जप करें।\n" .
                "३. शुक्रवार को कन्याओं को खीर अथवा सफेद मिष्ठान्न का भोजन कराएं।\n\n" .
                "दांपत्य सौहार्द एवं प्रेम वृद्धि हेतु सिद्ध पीठ में गौरी-शंकर शांति महापूजा का संकल्प लें।";
        } elseif ($intent === 'court_legal') {
            $reply = "🙏 ॐ ह्लीं बगलामुख्यै नमः! विजयो भव। कोर्ट-कचहरी, मुकदमे अथवा शत्रु बाधा का संबंध कुंडली के षष्ठ (६ठे) भाव और मंगल-शनि-राहु के योग से होता है।{$kundaliNote}\n\n" .
                "✨ शत्रु शमन व न्याय विजय उपाय:\n" .
                "१. नित्य सायं मां बगलामुखी अथवा हनुमान जी के समक्ष सरसों के तेल का दीपक जलाएं।\n" .
                "२. मंगलवार को सुंदरकांड का पाठ करें और सिंदूर का तिलक लगाएं।\n" .
                "३. काले तिल व लोहे की वस्तु का शनिवार को दान करें।\n\n" .
                "विवादों में निश्चित विजय हेतु नलखेड़ा सिद्ध पीठ में मां बगलामुखी महा अनुष्ठान करवाएं।";
        } elseif ($intent === 'vastu_shanti') {
            $reply = "🙏 ॐ वास्तोष्पतये नमः! आपके घर में सुख, शांति व समृद्धि का वास हो। भवन में ऊर्जा संतुलन न होने पर वास्तु दोष उत्पन्न होता है।{$kundaliNote}\n\n" .
                "✨ वास्तु दोष निवारण उपाय:\n" .
                "१. घर के ईशान कोण (उत्तर-पूर्व) को सदैव स्वच्छ व हल्का रखें, वहां जल पात्र या गंगाजल रखें।\n" .
                "२. मुख्य द्वार पर नित्य प्रातः हल्दी-कुमकुम से स्वास्तिक चिन्ह बनाएं।\n" .
                "३. सायं काल संपूर्ण घर में गुग्गल व लोबान की धूप दें।\n\n" .
                "भवन के वास्तु दोष की संपूर्ण शांति हेतु विद्वान ब्राह्मणों द्वारा वास्तु शांति महायज्ञ का संकल्प लें।";
        } elseif ($intent === 'pitra_dosha') {
            $reply = "🙏 ॐ पितृभ्यो नमः! पूर्वजों की कृपा से आपके समस्त कार्य सिद्ध हों। पितृ दोष के कारण जीवन में निरंतर रुकावटें और अशांति आती है।{$kundaliNote}\n\n" .
                "✨ पितृ शांति व मुक्ति उपाय:\n" .
                "१. प्रत्येक अमावस्या को दक्षिण दिशा की ओर मुख करके पितरों के निमित्त काले तिल व जल से तर्पण करें।\n" .
                "२. अमावस्या के दिन ब्राह्मण अथवा जरूरतमंद को भोजन कराएं व श्वेत वस्त्र दान करें।\n" .
                "३. पीपल के वृक्ष में जल व कच्चा दूध अर्पित कर सात परिक्रमा करें।\n\n" .
                "पितृ शांति एवं मोक्ष प्राप्ति हेतु गया अथवा काशी में त्रिपिंडी श्राद्ध महापूजा का संकल्प लें।";
        } elseif ($intent === 'muhurat_panchang') {
            $reply = "🙏 ॐ सूर्याय नमः! आज का दिन अत्यंत पावन है।\n\n" .
                "✨ शुभ मुहूर्त निर्देश:\n" .
                "• अभिजित मुहूर्त: दोपहर ११:४५ से १२:३० तक (समस्त शुभ कार्यों हेतु श्रेष्ठतम)।\n" .
                "• अमृत एवं शुभ चौघड़िया: प्रातःकाल तथा दोपहर में नए कार्य प्रारंभ करने हेतु शुभ हैं।\n" .
                "• राहु काल: सायं काल के समय कोई भी नवीन कार्य या संकल्प न लें।\n\n" .
                "किसी भी विशेष धार्मिक अनुष्ठान अथवा गृह प्रवेश के लिए OnlinePuja पर व्यक्तिगत वैदिक मुहूर्त परामर्श उपलब्ध है।";
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

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => [
                'reply_text' => $reply,
                'intent' => $intent,
                'vedic_profile' => $vedicProfile,
                'recommended_puja' => $recommendedPuja,
                'recommended_gemstone' => $recommendedGemstone,
                'mantra' => $mantra,
                'suggested_followups' => $suggestedFollowups,
                'whatsapp_share_url' => "https://api.whatsapp.com/send?text={$cleanShareText}",
                'astrologer_url' => url('/astrologers'),
            ]
        ], 200);
    }
}
