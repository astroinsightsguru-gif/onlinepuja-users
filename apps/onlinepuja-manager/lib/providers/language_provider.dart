import 'package:flutter/material.dart';

class LanguageProvider extends ChangeNotifier {
  bool _isHindi = false;

  bool get isHindi => _isHindi;
  String get currentLanguageCode => _isHindi ? 'hi' : 'en';

  void toggleLanguage() {
    _isHindi = !_isHindi;
    notifyListeners();
  }

  void setHindi(bool value) {
    if (_isHindi != value) {
      _isHindi = value;
      notifyListeners();
    }
  }

  // Sacred & Operational Vocabulary Translation Dictionary
  String t(String key) {
    if (!_isHindi) {
      return _enTranslations[key] ?? key;
    }
    return _hiTranslations[key] ?? _enTranslations[key] ?? key;
  }

  static const Map<String, String> _enTranslations = {
    'app_title': 'OnlinePuja Manager',
    'radar': 'Radar',
    'dialer': 'Dialer',
    'whatsapp': 'WhatsApp',
    'devotees': 'Devotees',
    'astrologers': 'Astrologers',
    'b2b_network': 'B2B Network',
    'quotes': 'Proposals',
    'sankalp': 'Sankalp',
    'ai_squad': 'AI Squad',
    'growth_ai': 'Growth AI',
    'social_hub': 'Social Hub',
    'blogs_hub': 'Blogs & SEO',
    'finance': 'Finance',
    'email_hub': 'Email & Brevo',
    'kundli': 'Kundli PDF',
    'team_access': 'Team & Access',
    'call_now': 'Call Now',
    'whatsapp_msg': 'WhatsApp',
    'search_hint': 'Search devotee, phone, or gotra...',
    'net_revenue': 'Net Revenue',
    'gross_dakshina': 'Gross Dakshina',
    'pandit_honorarium': 'Pandit Honorarium',
    'samagri_logistics': 'Samagri & Logistics',
    'gotra': 'Gotra',
    'puja_interest': 'Puja Interest',
    'rate_per_min': 'Rate / Min',
    'online': 'Online',
    'offline': 'Offline',
    'emergency_halt': 'Emergency Halt',
    'resume': 'Resume',
    'new_enquiry': 'New Devotee Enquiry',
    'sankalp_initiated': 'Sankalp Initiated',
    'puja_conducted': 'Vedic Ritual Conducted',
    'video_proof_sent': 'Video Proof Sent on WhatsApp',
    'prasad_dispatched': 'Abhimantrit Prasad Dispatched',
    'completed': 'Sankalp Sanctified & Completed',
    'teerth_location': 'Holy Teerth Kshetra',
    'kashi': 'Kashi / Varanasi',
    'ujjain': 'Ujjain Mahakal Dham',
    'haridwar': 'Haridwar Gangaji',
    'ayodhya': 'Ayodhya Ram Janmabhoomi',
    'gaya': 'Gaya Ji Teerth',
    'trimbakeshwar': 'Trimbakeshwar Jyotirlinga',
    'sankalp_card': 'Sankalp Patra',
    'devotee_name': 'Devotee (Yajman) Name',
    'family_gotra': 'Family Gotra',
    'manokamna': 'Sankalp Wish (Manokamna)',
    'send_whatsapp_receipt': 'Send WhatsApp Receipt',
    'assign_pandit': 'Assign Acharya / Shastri',
    'select_pandit': 'Select Verified Pandit',
    'dakshina_received': 'Dakshina Received',
    'payment_pending': 'Dakshina Pending',
  };

  static const Map<String, String> _hiTranslations = {
    'app_title': 'ऑनलाइन पूजा प्रबंधक',
    'radar': 'लाइव रडार',
    'dialer': 'स्मार्ट डायलर',
    'whatsapp': 'व्हाट्सएप',
    'devotees': 'यजमान सूची',
    'astrologers': 'ज्योतिषी एवं पंडित',
    'b2b_network': 'बी2बी नेटवर्क',
    'quotes': 'दक्षिणा प्रस्ताव',
    'sankalp': 'संकल्प पूर्ति',
    'ai_squad': 'एआई दस्ता',
    'growth_ai': 'ग्रोथ एआई',
    'social_hub': 'सोशल हब',
    'blogs_hub': 'ब्लॉग्स हब',
    'finance': 'पूजा लेजर',
    'email_hub': 'ईमेल व ब्रेवो',
    'kundli': 'जन्मपत्री PDF',
    'team_access': 'टीम और एक्सेस',
    'call_now': 'कॉल करें',
    'whatsapp_msg': 'व्हाट्सएप भेजें',
    'search_hint': 'यजमान, फोन या गोत्र से खोजें...',
    'net_revenue': 'शुद्ध आय',
    'gross_dakshina': 'कुल दक्षिणा',
    'pandit_honorarium': 'पंडित दक्षिणा/मानदेय',
    'samagri_logistics': 'सामग्री व कोरियर',
    'gotra': 'गोत्र',
    'puja_interest': 'पूजा रुचि',
    'rate_per_min': 'शुल्क / मिनट',
    'online': 'उपलब्ध (ऑनलाइन)',
    'offline': 'व्यस्त (ऑफलाइन)',
    'emergency_halt': 'आपातकालीन रोक',
    'resume': 'पुनः प्रारंभ करें',
    'new_enquiry': 'नवीन यजमान पूछताछ',
    'sankalp_initiated': 'संकल्प प्रारंभ',
    'puja_conducted': 'वेदोक्त अनुष्ठान संपन्न',
    'video_proof_sent': 'व्हाट्सएप पर प्रत्यक्ष वीडियो प्रेषित',
    'prasad_dispatched': 'अभिमंत्रित महाप्रसाद प्रेषित',
    'completed': 'संकल्प पूर्ण एवं सिद्ध',
    'teerth_location': 'पावन तीर्थ क्षेत्र',
    'kashi': 'काशी / वाराणसी',
    'ujjain': 'उज्जैन महाकाल धाम',
    'haridwar': 'हरिद्वार गंगाजी',
    'ayodhya': 'अयोध्या राम जन्मभूमि',
    'gaya': 'गयाजी तीर्थ',
    'trimbakeshwar': 'त्र्यंबकेश्वर ज्योतिर्लिंग',
    'sankalp_card': 'संकल्प पत्र',
    'devotee_name': 'यजमान का नाम',
    'family_gotra': 'पारिवारिक गोत्र',
    'manokamna': 'संकल्प मनोकामना',
    'send_whatsapp_receipt': 'व्हाट्सएप रसीद भेजें',
    'assign_pandit': 'वेदमूर्ति पुरोहित नियुक्त करें',
    'select_pandit': 'प्रमाणित तीर्थ पुरोहित चुनें',
    'dakshina_received': 'दक्षिणा प्राप्त',
    'payment_pending': 'दक्षिणा प्रतीक्षित',
  };
}
