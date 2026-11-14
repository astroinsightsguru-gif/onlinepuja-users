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
  };
}
