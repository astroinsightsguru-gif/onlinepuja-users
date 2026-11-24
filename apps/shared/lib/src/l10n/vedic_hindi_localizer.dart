/// High-Quality Vedic & Devotional Hindi Localization Engine for OnlinePuja.live.
/// Eliminates partial Hindi / Hinglish across Temples, Subtitles, Inclusions, Benefits & FAQs.
class VedicHindiLocalizer {
  VedicHindiLocalizer._();

  /// Localize Temple / Ghats / Teerth Kshetra name
  static String localizePlace(String? place, bool isHi) {
    if (!isHi || place == null || place.trim().isEmpty) {
      return place ?? (isHi ? 'पावन तीर्थ क्षेत्र' : 'Holy Teerth Kshetra');
    }
    final raw = place.trim();
    final lower = raw.toLowerCase();

    // 1. Exact / Fuzzy Matches for Top Indian Teerths
    if (lower.contains('mrityunjay') || lower.contains('mahamrityunjay')) {
      return 'मृत्युंजय महादेव मंदिर एवं काशी घाट, वाराणसी';
    }
    if (lower.contains('kashi vishwanath') || (lower.contains('vishwanath') && lower.contains('varanasi'))) {
      return 'श्री काशी विश्वनाथ धाम, वाराणसी';
    }
    if (lower.contains('dashashwamedh') || lower.contains('dashaswamedh')) {
      return 'दशाश्वमेध घाट, पावन गंगा तट, वाराणसी';
    }
    if (lower.contains('assi ghat') || lower.contains('assi')) {
      return 'अस्सी घाट, पावन गंगा तट, वाराणसी';
    }
    if (lower.contains('manikarnika')) {
      return 'मणिकर्णिका महातीर्थ, वाराणसी';
    }
    if (lower.contains('kaal bhairav') && lower.contains('varanasi')) {
      return 'काशी कोतवाल श्री काल भैरव मंदिर, वाराणसी';
    }
    if (lower.contains('sankat mochan')) {
      return 'संकटमोचन श्री हनुमान मंदिर, वाराणसी';
    }
    if (lower.contains('durga kund') || (lower.contains('durga') && lower.contains('varanasi'))) {
      return 'मां दुर्गा मंदिर, दुर्गा कुंड, वाराणसी';
    }
    if (lower.contains('varanasi ghats') || lower.contains('kashi ghats') || lower.contains('ganga ghats')) {
      return 'पावन काशी गंगा घाट, वाराणसी';
    }
    if (lower.contains('mahakaleshwar') || (lower.contains('mahakal') && lower.contains('ujjain'))) {
      return 'श्री महाकालेश्वर ज्योतिर्लिंग, उज्जैन';
    }
    if (lower.contains('mangalnath') || lower.contains('mangal nath')) {
      return 'मंगलनाथ मंदिर (मंगल दोष निवारण), उज्जैन';
    }
    if (lower.contains('kaal bhairav') && lower.contains('ujjain')) {
      return 'काल भैरव मंदिर, उज्जैन';
    }
    if (lower.contains('harsiddhi')) {
      return 'मां हरसिद्धि शक्तिपीठ, उज्जैन';
    }
    if (lower.contains('shipra')) {
      return 'मोक्षदायिनी शिप्रा तट, उज्जैन';
    }
    if (lower.contains('trimbakeshwar') || lower.contains('tryambakeshwar')) {
      return 'त्र्यंबकेश्वर ज्योतिर्लिंग, नासिक, महाराष्ट्र';
    }
    if (lower.contains('kushavarta')) {
      return 'कुशावर्त पावन कुंड, त्र्यंबकेश्वर';
    }
    if (lower.contains('har ki pauri') || lower.contains('harkipauri')) {
      return 'हर की पौड़ी, पावन हरिद्वार, उत्तराखंड';
    }
    if (lower.contains('mansa devi')) {
      return 'मां मनसा देवी सिद्धपीठ, हरिद्वार';
    }
    if (lower.contains('chandi devi')) {
      return 'मां चंडी देवी सिद्धपीठ, हरिद्वार';
    }
    if (lower.contains('triveni ghat') || lower.contains('rishikesh')) {
      return 'त्रिवेणी संगम घाट, ऋषिकेश, उत्तराखंड';
    }
    if (lower.contains('neelkanth')) {
      return 'नीलकंठ महादेव मंदिर, ऋषिकेश';
    }
    if (lower.contains('vishnupad') || lower.contains('gaya')) {
      return 'विष्णुपद मंदिर एवं गयाजी मोक्ष तीर्थ, बिहार';
    }
    if (lower.contains('akshayavat')) {
      return 'अक्षयवट मोक्ष तीर्थ, गयाजी, बिहार';
    }
    if (lower.contains('ram janmabhoomi') || lower.contains('ayodhya')) {
      return 'श्री राम जन्मभूमि धाम एवं सरयू तट, अयोध्या';
    }
    if (lower.contains('hanuman garhi')) {
      return 'सिद्धपीठ श्री हनुमान गढ़ी, अयोध्या';
    }
    if (lower.contains('baglamukhi') || lower.contains('nalkheda')) {
      return 'मां पीताम्बरा बगलामुखी मंदिर, नलखेड़ा, मध्य प्रदेश';
    }
    if (lower.contains('kamakhya')) {
      return 'मां कामाख्या महाशक्तिपीठ, गुवाहाटी, असम';
    }
    if (lower.contains('vindhyavasini') || lower.contains('vindhyachal')) {
      return 'मां विंध्यवासिनी महाशक्तिपीठ, विंध्याचल';
    }
    if (lower.contains('tarapith')) {
      return 'सिद्धपीठ तारापीठ, पश्चिम बंगाल';
    }
    if (lower.contains('vaishno devi')) {
      return 'मां वैष्णो देवी धाम, कटरा, जम्मू';
    }

    // 2. Intelligent Word Substitutions for Unrecognized Teerths
    String translated = raw
        .replaceAll(RegExp(r'\bTemple\b', caseSensitive: false), 'मंदिर')
        .replaceAll(RegExp(r'\bMandir\b', caseSensitive: false), 'मंदिर')
        .replaceAll(RegExp(r'\bGhats\b', caseSensitive: false), 'घाट')
        .replaceAll(RegExp(r'\bGhat\b', caseSensitive: false), 'घाट')
        .replaceAll(RegExp(r'\bVaranasi\b', caseSensitive: false), 'वाराणसी')
        .replaceAll(RegExp(r'\bKashi\b', caseSensitive: false), 'काशी')
        .replaceAll(RegExp(r'\bUjjain\b', caseSensitive: false), 'उज्जैन')
        .replaceAll(RegExp(r'\bHaridwar\b', caseSensitive: false), 'हरिद्वार')
        .replaceAll(RegExp(r'\bRishikesh\b', caseSensitive: false), 'ऋषिकेश')
        .replaceAll(RegExp(r'\bNashik\b', caseSensitive: false), 'नासिक')
        .replaceAll(RegExp(r'\bAyodhya\b', caseSensitive: false), 'अयोध्या')
        .replaceAll(RegExp(r'\bRiver\b', caseSensitive: false), 'नदी')
        .replaceAll(RegExp(r'\bHoly\b', caseSensitive: false), 'पावन')
        .replaceAll(RegExp(r'\bTeerth\b', caseSensitive: false), 'तीर्थ')
        .replaceAll(RegExp(r'\bDham\b', caseSensitive: false), 'धाम')
        .replaceAll(RegExp(r'\bShaktipeeth\b', caseSensitive: false), 'शक्तिपीठ')
        .replaceAll(RegExp(r'\bJyotirlinga\b', caseSensitive: false), 'ज्योतिर्लिंग')
        .replaceAll(RegExp(r'\bKund\b', caseSensitive: false), 'कुंड')
        .replaceAll(RegExp(r'\b&\b'), 'एवं')
        .replaceAll(RegExp(r'\band\b', caseSensitive: false), 'एवं');

    return translated;
  }

  /// Localize Puja Subtitle
  static String localizeSubtitle(String? subtitle, String? title, bool isHi) {
    if (!isHi) return subtitle ?? '';
    final sub = (subtitle ?? '').trim();
    final subLower = sub.toLowerCase();
    final tLower = (title ?? '').toLowerCase();

    // 1. Direct Pattern Matches on Subtitle or Title
    if (subLower.contains('sanjeevani') ||
        subLower.contains('critical illness') ||
        tLower.contains('mrityunjay') ||
        tLower.contains('mahamrityunjaya')) {
      return 'गंभीर रोग निवारण, दीर्घायु एवं आरोग्य लाभ हेतु वैदिक ब्राह्मणों द्वारा संजीवनी महामृत्युंजय जप';
    }
    if (subLower.contains('kaal sarp') || tLower.contains('kalsarp') || tLower.contains('sarpa')) {
      return 'त्र्यंबकेश्वर ज्योतिर्लिंग में वेदोक्त विधि से संपूर्ण कालसर्प दोष निवारण महापूजा';
    }
    if (subLower.contains('narayan nagbali') || (tLower.contains('narayan') && tLower.contains('nagbali'))) {
      return 'काशी गंगा तट पर पितृदोष शांति एवं मोक्ष प्राप्ति हेतु नारायण नागबलि महापूजा';
    }
    if (subLower.contains('deepdaan') || subLower.contains('deepawali') || tLower.contains('deepdaan')) {
      return 'काशी के पावन घाटों पर १०८ दीपदान, गंगा महाआरती एवं व्यक्तिगत वैदिक संकल्प';
    }
    if (subLower.contains('rudrabhishek') || tLower.contains('rudrabhishek') || tLower.contains('rudra')) {
      return 'वेदोक्त आचार्यों द्वारा गंगाजल एवं बिल्वपत्र से द्वादश ज्योतिर्लिंग षोडशोपचार रुद्राभिषेक';
    }
    if (subLower.contains('pind daan') || subLower.contains('shradh') || tLower.contains('pitru')) {
      return 'गयाजी मोक्ष धाम में पितरों की सद्गति हेतु वेदोक्त त्रिपिंडी श्राद्ध एवं पिंडदान';
    }
    if (subLower.contains('baglamukhi') || tLower.contains('baglamukhi')) {
      return 'शत्रु बाधा निवारण, विजय प्राप्ति एवं कोर्ट-कचहरी संकट निवारण हेतु मां बगलामुखी अनुष्ठान';
    }
    if (subLower.contains('navgrah') || tLower.contains('navgrah') || tLower.contains('graha shanti')) {
      return 'सर्वग्रह दोष शांति, सुख-समृद्धि एवं भाग्योदय हेतु संपूर्ण नवग्रह शांति महायज्ञ';
    }
    if (subLower.contains('lakshmi') || subLower.contains('kuber') || tLower.contains('lakshmi')) {
      return 'अखंड धन, ऐश्वर्य, व्यापार वृद्धि एवं आर्थिक संकट निवारण हेतु अष्टलक्ष्मी कुबेर महायज्ञ';
    }
    if (subLower.contains('shat chandi') || subLower.contains('durga') || tLower.contains('durga')) {
      return 'समस्त बाधा निवारण एवं मनोकामना सिद्धि हेतु दुर्गा सप्तशती शतचंडी महायज्ञ';
    }
    if (subLower.contains('satyanarayan') || tLower.contains('satyanarayan')) {
      return 'पारिवारिक शांति, आरोग्य एवं सुख-समृद्धि हेतु श्री सत्यनारायण भगवान कथा व पूजन';
    }
    if (subLower.contains('sundarkand') || tLower.contains('sundarkand') || tLower.contains('hanuman')) {
      return 'समस्त संकटों व भय से मुक्ति हेतु संकटमोचन श्री हनुमान सुंदरकांड संगीतमय अखंड पाठ';
    }

    // If English subtitle exists but didn't match specific puja, provide authentic Vedic subtitle
    if (sub.isNotEmpty) {
      if (subLower.contains('vedic') || subLower.contains('brahmins') || subLower.contains('chanting')) {
        return 'काशी के विद्वान तीर्थ पुरोहितों द्वारा वैदिक विधि-विधान एवं वेदोक्त मंत्रोच्चार सहित अनुष्ठान';
      }
      return 'काशी के सिद्ध तीर्थ पर आचार्य जी द्वारा आपके नाम-गोत्र से संपूर्ण विधि-विधान से संपन्न';
    }

    return 'काशी के सिद्ध तीर्थ पर आचार्य जी द्वारा आपके नाम-गोत्र से संपूर्ण विधि-विधान से संपन्न';
  }

  /// Localize Package Name
  static String localizePackageName(String? name, bool isHi) {
    if (!isHi || name == null || name.trim().isEmpty) return name ?? 'Package';
    final lower = name.toLowerCase().trim();
    if (lower.contains('individual') || lower.contains('single') || lower.contains('1 person') || lower.contains('ekal')) {
      return 'एकल यजमान संकल्प';
    }
    if (lower.contains('family') || lower.contains('parivar') || lower.contains('sampurna family')) {
      return 'सपरिवार महासंकल्प';
    }
    if (lower.contains('sampoorna') || lower.contains('grand') || lower.contains('complete') || lower.contains('maha puja')) {
      return 'संपूर्ण तीर्थ महापूजा एवं महाप्रसाद';
    }
    if (lower.contains('sankalp seva') || lower.contains('vedic') || lower.contains('vishesh')) {
      return 'वैदिक संकल्प सेवा';
    }
    return name;
  }

  /// Localize Package Inclusion Bullet Point
  static String localizeInclusion(String inc, bool isHi) {
    if (!isHi) return inc;
    final lower = inc.toLowerCase().trim();

    if (lower.contains('name and gotra') ||
        lower.contains('name & gotra') ||
        lower.contains('recite your name') ||
        lower.contains('personal sankalp') ||
        lower.contains('sankalp for 1')) {
      return 'पवित्र गंगा घाट पर लाइव वैदिक संकल्प के दौरान पंडित जी आपके नाम व गोत्र का उच्चारण करेंगे।';
    }
    if (lower.contains('personalized prayers') ||
        lower.contains('health, peace') ||
        lower.contains('spiritual liberation') ||
        lower.contains('vedic ritual performed')) {
      return 'आरोग्य, शांति, समृद्धि एवं आध्यात्मिक उन्नति हेतु आपके संकल्प के अनुसार वैदिक अनुष्ठान।';
    }
    if (lower.contains('video') && (lower.contains('whatsapp') || lower.contains('recording') || lower.contains('digital'))) {
      return 'पूजा व संकल्प का संपूर्ण व्यक्तिगत वीडियो रिकॉर्डिंग सीधे आपके व्हाट्सएप पर।';
    }
    if (lower.contains('mahaprasad') || lower.contains('prasad delivered') || lower.contains('doorstep') || lower.contains('holy prasad')) {
      return 'अभिमंत्रित रक्षा सूत्र, भस्म व महाप्रसाद सीधे आपके घर तक सुरक्षित डिलीवरी।';
    }
    if (lower.contains('hawan') || lower.contains('brahmin bhojan') || lower.contains('navgrah ahuti')) {
      return 'काशी में संपूर्ण वेदोक्त हवन, नवग्रह आहुति एवं ब्राह्मण भोजन संपन्न।';
    }
    if (lower.contains('archana') || lower.contains('bilva patra') || lower.contains('pushpanjali') || lower.contains('chadhava')) {
      return 'आपके नाम से विशेष अर्चना, बिल्वपत्र अर्पण एवं प्रत्यक्ष पुष्पार्पण चढ़ावा।';
    }
    if (lower.contains('family members') || lower.contains('family') || lower.contains('all family')) {
      return 'समस्त परिवार जन व्यक्तिगत नाम व गोत्र सहित पावन संकल्प में सम्मिलित।';
    }
    if (lower.contains('raksha sutra') || lower.contains('vibhuti') || lower.contains('gangajal')) {
      return 'सिद्ध रक्षा सूत्र, पावन गंगाजल एवं अभिमंत्रित विभूति प्रसाद बॉक्स में सम्मिलित।';
    }
    if (lower.contains('mantra chanting') || lower.contains('vedic brahmins') || lower.contains('acharyas')) {
      return 'तीर्थ पुरोहितों द्वारा सस्वर वेदोक्त मंत्रोच्चार एवं वैदिक विधि-विधान।';
    }
    if (lower.contains('live darshan') || lower.contains('photo proof') || lower.contains('darshan photos')) {
      return 'संकल्प, अभिषेक एवं आरती के प्रत्यक्ष दर्शन फोटो व वीडियो।';
    }

    return inc;
  }

  /// Localize Puja Benefit
  static String localizeBenefit(String b, bool isHi) {
    if (!isHi) return b;
    final lower = b.toLowerCase().trim();

    if (lower.contains('protection') || lower.contains('accidents') || lower.contains('untimely') || lower.contains('fatal')) {
      return 'अकाल मृत्यु, दुर्घटना एवं गंभीर संकटों से दैवीय रक्षा';
    }
    if (lower.contains('fear of death') || lower.contains('vitality') || lower.contains('mental peace')) {
      return 'मृत्यु भय व मानसिक अशांति से मुक्ति एवं नवजीवन ऊर्जा की प्राप्ति';
    }
    if (lower.contains('chronic ailments') || lower.contains('illness') || lower.contains('disease') || lower.contains('hospitalization')) {
      return 'असाध्य रोगों, शारीरिक कष्टों एवं लंबे समय की बीमारियों से आरोग्य लाभ';
    }
    if (lower.contains('lord shiva') || lower.contains('shiva') || lower.contains('spiritual strength')) {
      return 'भगवान भोलेनाथ की असीम कृपा, मानसिक शांति एवं आत्मिक बल की प्राप्ति';
    }
    if (lower.contains('progeny') || lower.contains('child') || lower.contains('marriage') || lower.contains('vivah')) {
      return 'योग्य संतान सुख, शीघ्र विवाह योग एवं दांपत्य जीवन में मधुरता';
    }
    if (lower.contains('kaal sarp') || lower.contains('rahu') || lower.contains('ketu') || lower.contains('sarpa')) {
      return 'जन्मकुंडली से कालसर्प, राहु-केतु एवं सर्प दोष का समूल निवारण';
    }
    if (lower.contains('pitru') || lower.contains('ancestor') || lower.contains('moksha') || lower.contains('salvation')) {
      return 'पितरों की आत्मिक शांति, तृप्ति एवं पूर्वजों के मोक्ष का आशीर्वाद';
    }
    if (lower.contains('business') || lower.contains('career') || lower.contains('debt') || lower.contains('wealth') || lower.contains('financial')) {
      return 'व्यापारिक रुकावटों, करियर मंदी एवं ऋण संकट से मुक्ति तथा धन वृद्धि';
    }
    if (lower.contains('durga') || lower.contains('adversaries') || lower.contains('enemies') || lower.contains('shatru')) {
      return 'मां भगवती की कृपा से समस्त गुप्त शत्रुओं, कोर्ट केस व बाधाओं पर विजय';
    }
    if (lower.contains('negative energy') || lower.contains('evil eye') || lower.contains('nazar') || lower.contains('shield')) {
      return 'समस्त परिवार की नकारात्मक ऊर्जा, तंत्र बाधा व बुरी नजर से रक्षा कवच';
    }

    return b;
  }

  /// Localize FAQ Question & Answer
  static String localizeFaq(String text, bool isHi) {
    if (!isHi) return text;
    final lower = text.toLowerCase().trim();

    // Questions
    if (lower.contains('how will i know') || lower.contains('performed for me') || lower.contains('proof')) {
      return 'मुझे कैसे ज्ञात होगा कि पूजा मेरे नाम-गोत्र से ही संपन्न हुई है?';
    }
    if (lower.contains('what do i need to provide') || lower.contains('details required') || lower.contains('sankalp details')) {
      return 'पूजा संकल्प हेतु मुझे क्या-क्या विवरण प्रदान करना होगा?';
    }
    if (lower.contains('when will i receive') || lower.contains('prasad delivery') || lower.contains('how many days')) {
      return 'अभिमंत्रित महाप्रसाद मुझे कितने दिनों में प्राप्त होगा?';
    }
    if (lower.contains('attend') || lower.contains('online live') || lower.contains('watch live')) {
      return 'क्या मैं पूजा का प्रत्यक्ष दर्शन ऑनलाइन कर सकता हूँ?';
    }
    if (lower.contains('not know my gotra') || lower.contains('don\'t know gotra') || lower.contains('unknown gotra')) {
      return 'यदि मुझे अपना गोत्र ज्ञात न हो, तो क्या मेरा संकल्प हो सकता है?';
    }

    // Answers
    if (lower.contains('video') && lower.contains('whatsapp') && lower.contains('gotra')) {
      return 'पवित्र तीर्थ पर आचार्य जी आपके नाम, गोत्र व मनोकामना का सस्वर संकल्प करेंगे। इसका संपूर्ण व्यक्तिगत वीडियो रिकॉर्डिंग एवं दर्शन फोटो पूजा संपन्न होने के बाद आपके व्हाट्सएप पर भेजा जाएगा।';
    }
    if (lower.contains('name') && lower.contains('gotra') && (lower.contains('date of birth') || lower.contains('wish'))) {
      return 'चेकआउट के समय आपको यजमान का नाम, गोत्र, जन्म तिथि (यदि ज्ञात हो) एवं अपनी विशेष मनोकामना दर्ज करनी होगी। परिवार पैकेज में आप परिवार के अन्य सदस्यों के नाम भी जोड़ सकते हैं।';
    }
    if (lower.contains('3-5') || lower.contains('courier') || lower.contains('tracking') || lower.contains('prasad box')) {
      return 'पूजा संपन्न होने के उपरांत तीर्थ से अभिमंत्रित रक्षा सूत्र, भस्म व महाप्रसाद को पवित्रता से पैक कर स्पीड पोस्ट/कूरियर द्वारा भेजा जाता है, जो सामान्यतः ३ से ५ कार्यदिवसों में आपके पते पर पहुंच जाता है।';
    }
    if (lower.contains('kashyap') || lower.contains('brahmin') || lower.contains('shastras')) {
      return 'शास्त्रों के अनुसार यदि गोत्र ज्ञात न हो तो वैदिक परंपरा अनुसार "कश्यप गोत्र" मानकर संकल्प लिया जाता है, जिसका पूर्ण फल प्राप्त होता है।';
    }

    return text;
  }
}
