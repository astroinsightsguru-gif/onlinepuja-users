import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

/// Model representing a sacred temple live darshan feed.
class TempleFeed {
  final String id;
  final String name;
  final String deity;
  final String location;
  final String timing;
  final String thumbnailUrl;
  final String streamUrl;
  final String? officialWebUrl;
  final String description;
  final bool isActive;
  final int viewerCount;
  final bool chadhavaEnabled;
  final int chadhavaMinPrice;

  const TempleFeed({
    required this.id,
    required this.name,
    required this.deity,
    required this.location,
    required this.timing,
    required this.thumbnailUrl,
    required this.streamUrl,
    this.officialWebUrl,
    required this.description,
    this.isActive = true,
    this.viewerCount = 1420,
    this.chadhavaEnabled = true,
    this.chadhavaMinPrice = 51,
  });

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'deity': deity,
        'location': location,
        'timing': timing,
        'thumbnailUrl': thumbnailUrl,
        'streamUrl': streamUrl,
        'officialWebUrl': officialWebUrl,
        'description': description,
        'isActive': isActive,
        'viewerCount': viewerCount,
        'chadhavaEnabled': chadhavaEnabled,
        'chadhavaMinPrice': chadhavaMinPrice,
      };

  factory TempleFeed.fromJson(Map<String, dynamic> json) => TempleFeed(
        id: json['id'] as String? ?? '',
        name: json['name'] as String? ?? '',
        deity: json['deity'] as String? ?? '',
        location: json['location'] as String? ?? '',
        timing: json['timing'] as String? ?? '',
        thumbnailUrl: json['thumbnailUrl'] as String? ?? '',
        streamUrl: json['streamUrl'] as String? ?? '',
        officialWebUrl: json['officialWebUrl'] as String?,
        description: json['description'] as String? ?? '',
        isActive: json['isActive'] as bool? ?? true,
        viewerCount: (json['viewerCount'] as num?)?.toInt() ?? 1200,
        chadhavaEnabled: json['chadhavaEnabled'] as bool? ?? true,
        chadhavaMinPrice: (json['chadhavaMinPrice'] as num?)?.toInt() ?? 51,
      );

  TempleFeed copyWith({
    String? id,
    String? name,
    String? deity,
    String? location,
    String? timing,
    String? thumbnailUrl,
    String? streamUrl,
    String? officialWebUrl,
    String? description,
    bool? isActive,
    int? viewerCount,
    bool? chadhavaEnabled,
    int? chadhavaMinPrice,
  }) =>
      TempleFeed(
        id: id ?? this.id,
        name: name ?? this.name,
        deity: deity ?? this.deity,
        location: location ?? this.location,
        timing: timing ?? this.timing,
        thumbnailUrl: thumbnailUrl ?? this.thumbnailUrl,
        streamUrl: streamUrl ?? this.streamUrl,
        officialWebUrl: officialWebUrl ?? this.officialWebUrl,
        description: description ?? this.description,
        isActive: isActive ?? this.isActive,
        viewerCount: viewerCount ?? this.viewerCount,
        chadhavaEnabled: chadhavaEnabled ?? this.chadhavaEnabled,
        chadhavaMinPrice: chadhavaMinPrice ?? this.chadhavaMinPrice,
      );
}

/// Configuration for the OnlinePuja AI Chat Engine.
class AiChatConfig {
  final String botName;
  final String botTagline;
  final String welcomeGreeting;
  final String systemPrompt;
  final List<String> starterPrompts;
  final String modelName;
  final double temperature;
  final bool enableKundliContext;
  final bool enablePujaRemedies;

  const AiChatConfig({
    required this.botName,
    required this.botTagline,
    required this.welcomeGreeting,
    required this.systemPrompt,
    required this.starterPrompts,
    this.modelName = 'gemini-1.5-flash',
    this.temperature = 0.7,
    this.enableKundliContext = true,
    this.enablePujaRemedies = true,
  });

  Map<String, dynamic> toJson() => {
        'botName': botName,
        'botTagline': botTagline,
        'welcomeGreeting': welcomeGreeting,
        'systemPrompt': systemPrompt,
        'starterPrompts': starterPrompts,
        'modelName': modelName,
        'temperature': temperature,
        'enableKundliContext': enableKundliContext,
        'enablePujaRemedies': enablePujaRemedies,
      };

  factory AiChatConfig.fromJson(Map<String, dynamic> json) => AiChatConfig(
        botName: json['botName'] as String? ?? 'Acharya Vashistha',
        botTagline: json['botTagline'] as String? ?? '24/7 Master Vedic AI Astrologer',
        welcomeGreeting: json['welcomeGreeting'] as String? ??
            '🕉️ **हरि ॐ! सादर प्रणाम।**\n\nI am **Acharya Vashistha**, your AI Vedic Guide & Astrological Companion powered by the **OnlinePuja.live Vedic AI Engine**.\n\nAsk me anything about your **Kundli, Planetary Dashas, Career, Relationships, Gemstones, or Sacred Puja Remedies**.',
        systemPrompt: json['systemPrompt'] as String? ??
            'You are Acharya Vashistha, an enlightened, compassionate, and deeply knowledgeable Vedic scholar and astrologer on OnlinePuja.live. Provide accurate Parashara, KP, and Jaimini astrological insights with practical remedies, mantras, and appropriate Pujas.',
        starterPrompts: (json['starterPrompts'] as List?)?.map((e) => e.toString()).toList() ??
            const [
              'What do my planetary transits (Gochar) indicate today?',
              'Which puja or mantra will remove obstacles in my career?',
              'Explain my Moon sign & Nakshatra characteristics',
              'Which sacred gemstone & Rudraksha is auspicious for me?',
              'Guidance on relationship harmony and Kundli Gun Milan',
            ],
        modelName: json['modelName'] as String? ?? 'gemini-1.5-flash',
        temperature: (json['temperature'] as num?)?.toDouble() ?? 0.7,
        enableKundliContext: json['enableKundliContext'] as bool? ?? true,
        enablePujaRemedies: json['enablePujaRemedies'] as bool? ?? true,
      );
}

/// Configuration for Brand Identity, Profiles & URLs.
class BrandProfileConfig {
  final String brandName;
  final String brandTagline;
  final String supportPhone;
  final String supportEmail;
  final String websiteUrl;
  final String appHubUrl;
  final String primaryColorHex;

  const BrandProfileConfig({
    this.brandName = 'OnlinePuja.live',
    this.brandTagline = 'Sacred Temple Darshan & Verified Vedic Astrologers',
    this.supportPhone = '+91 70071 58014',
    this.supportEmail = 'care@onlinepuja.live',
    this.websiteUrl = 'https://onlinepuja.live',
    this.appHubUrl = 'https://onlinepuja.live/apps',
    this.primaryColorHex = '#D97706',
  });

  Map<String, dynamic> toJson() => {
        'brandName': brandName,
        'brandTagline': brandTagline,
        'supportPhone': supportPhone,
        'supportEmail': supportEmail,
        'websiteUrl': websiteUrl,
        'appHubUrl': appHubUrl,
        'primaryColorHex': primaryColorHex,
      };

  factory BrandProfileConfig.fromJson(Map<String, dynamic> json) => BrandProfileConfig(
        brandName: json['brandName'] as String? ?? 'OnlinePuja.live',
        brandTagline: json['brandTagline'] as String? ??
            'Sacred Temple Darshan & Verified Vedic Astrologers',
        supportPhone: json['supportPhone'] as String? ?? '+91 70071 58014',
        supportEmail: json['supportEmail'] as String? ?? 'care@onlinepuja.live',
        websiteUrl: json['websiteUrl'] as String? ?? 'https://onlinepuja.live',
        appHubUrl: json['appHubUrl'] as String? ?? 'https://onlinepuja.live/apps',
        primaryColorHex: json['primaryColorHex'] as String? ?? '#D97706',
      );
}

/// Growth & Monetization settings.
class GrowthOfferConfig {
  final bool firstConsultOfferEnabled;
  final int firstConsultPrice;
  final List<int> chadhavaTiers;
  final bool liveDarshanEnabled;
  final bool dailyHoroscopeEnabled;
  final int freePrashnaPerDay;

  const GrowthOfferConfig({
    this.firstConsultOfferEnabled = true,
    this.firstConsultPrice = 1,
    this.chadhavaTiers = const [11, 21, 51, 101, 251],
    this.liveDarshanEnabled = true,
    this.dailyHoroscopeEnabled = true,
    this.freePrashnaPerDay = 1,
  });

  Map<String, dynamic> toJson() => {
        'firstConsultOfferEnabled': firstConsultOfferEnabled,
        'firstConsultPrice': firstConsultPrice,
        'chadhavaTiers': chadhavaTiers,
        'liveDarshanEnabled': liveDarshanEnabled,
        'dailyHoroscopeEnabled': dailyHoroscopeEnabled,
        'freePrashnaPerDay': freePrashnaPerDay,
      };

  factory GrowthOfferConfig.fromJson(Map<String, dynamic> json) => GrowthOfferConfig(
        firstConsultOfferEnabled: json['firstConsultOfferEnabled'] as bool? ?? true,
        firstConsultPrice: (json['firstConsultPrice'] as num?)?.toInt() ?? 1,
        chadhavaTiers: (json['chadhavaTiers'] as List?)?.map((e) => (e as num).toInt()).toList() ??
            const [11, 21, 51, 101, 251],
        liveDarshanEnabled: json['liveDarshanEnabled'] as bool? ?? true,
        dailyHoroscopeEnabled: json['dailyHoroscopeEnabled'] as bool? ?? true,
        freePrashnaPerDay: (json['freePrashnaPerDay'] as num?)?.toInt() ?? 1,
      );
}

/// Configuration for Social Media Automation, Webhooks & Autopilot Distribution.
class SocialMediaConfig {
  final String whatsappChannelUrl;
  final String telegramChannelUrl;
  final String instagramHandle;
  final String youtubeChannelUrl;
  final int referralBonusAmount;
  final int referralDiscountAmount;
  final bool autopilotEnabled;
  final String autopilotPanchangMorningTime;
  final String autopilotHoroscopeTime;
  final String autopilotAartiReminderTime;
  final String webhookEndpoint;
  final String telegramBotToken;
  final String telegramChatId;

  const SocialMediaConfig({
    this.whatsappChannelUrl = 'https://whatsapp.com/channel/0029VaOnlinePuja',
    this.telegramChannelUrl = 'https://t.me/OnlinePujaLive',
    this.instagramHandle = '@onlinepuja.live',
    this.youtubeChannelUrl = 'https://youtube.com/@onlinepujalive',
    this.referralBonusAmount = 51,
    this.referralDiscountAmount = 50,
    this.autopilotEnabled = true,
    this.autopilotPanchangMorningTime = '06:00 AM',
    this.autopilotHoroscopeTime = '07:00 AM',
    this.autopilotAartiReminderTime = '06:30 PM',
    this.webhookEndpoint = 'https://onlinepuja.live/api/social/webhook',
    this.telegramBotToken = '',
    this.telegramChatId = '',
  });

  Map<String, dynamic> toJson() => {
        'whatsappChannelUrl': whatsappChannelUrl,
        'telegramChannelUrl': telegramChannelUrl,
        'instagramHandle': instagramHandle,
        'youtubeChannelUrl': youtubeChannelUrl,
        'referralBonusAmount': referralBonusAmount,
        'referralDiscountAmount': referralDiscountAmount,
        'autopilotEnabled': autopilotEnabled,
        'autopilotPanchangMorningTime': autopilotPanchangMorningTime,
        'autopilotHoroscopeTime': autopilotHoroscopeTime,
        'autopilotAartiReminderTime': autopilotAartiReminderTime,
        'webhookEndpoint': webhookEndpoint,
        'telegramBotToken': telegramBotToken,
        'telegramChatId': telegramChatId,
      };

  factory SocialMediaConfig.fromJson(Map<String, dynamic> json) => SocialMediaConfig(
        whatsappChannelUrl: json['whatsappChannelUrl'] as String? ??
            'https://whatsapp.com/channel/0029VaOnlinePuja',
        telegramChannelUrl: json['telegramChannelUrl'] as String? ??
            'https://t.me/OnlinePujaLive',
        instagramHandle: json['instagramHandle'] as String? ?? '@onlinepuja.live',
        youtubeChannelUrl: json['youtubeChannelUrl'] as String? ??
            'https://youtube.com/@onlinepujalive',
        referralBonusAmount:
            (json['referralBonusAmount'] as num?)?.toInt() ?? 51,
        referralDiscountAmount:
            (json['referralDiscountAmount'] as num?)?.toInt() ?? 50,
        autopilotEnabled: json['autopilotEnabled'] as bool? ?? true,
        autopilotPanchangMorningTime:
            json['autopilotPanchangMorningTime'] as String? ?? '06:00 AM',
        autopilotHoroscopeTime:
            json['autopilotHoroscopeTime'] as String? ?? '07:00 AM',
        autopilotAartiReminderTime:
            json['autopilotAartiReminderTime'] as String? ?? '06:30 PM',
        webhookEndpoint: json['webhookEndpoint'] as String? ??
            'https://onlinepuja.live/api/social/webhook',
        telegramBotToken: json['telegramBotToken'] as String? ?? '',
        telegramChatId: json['telegramChatId'] as String? ?? '',
      );
}

/// Helper to generate high-conversion, viral social media messages and share URLs.
class SocialContentGenerator {
  const SocialContentGenerator._();

  static Uri buildWhatsAppUrl(String text) =>
      Uri.parse('https://api.whatsapp.com/send?text=${Uri.encodeComponent(text)}');

  static Uri buildTelegramUrl({required String text, String? url}) =>
      Uri.parse('https://t.me/share/url?url=${Uri.encodeComponent(url ?? '')}&text=${Uri.encodeComponent(text)}');

  static Uri buildTwitterUrl({required String text, String? url}) =>
      Uri.parse('https://twitter.com/intent/tweet?text=${Uri.encodeComponent(text)}&url=${Uri.encodeComponent(url ?? '')}');

  static Uri buildFacebookUrl(String url) =>
      Uri.parse('https://www.facebook.com/sharer/sharer.php?u=${Uri.encodeComponent(url)}');

  /// Formatted morning Panchang broadcast.
  static String formatPanchangBroadcast({
    required String tithi,
    required String nakshatra,
    required String sunrise,
    required String sunset,
    String? shubhMuhurat,
    String? rahuKaal,
  }) {
    final now = DateTime.now();
    final dateStr = '${now.day}/${now.month}/${now.year}';
    return '🌅 *शुभ प्रभात! आज का वैदिक पञ्चाङ्ग* ($dateStr)\n'
        '────────────────────\n'
        '🕉️ *तिथि:* $tithi\n'
        '⭐ *नक्षत्र:* $nakshatra\n'
        '☀️ *सूर्योदय:* $sunrise | *सूर्यास्त:* $sunset\n'
        '✨ *शुभ मुहूर्त (अभिजीत):* ${shubhMuhurat ?? '11:45 AM - 12:35 PM'}\n'
        '⚠️ *राहुकाल:* ${rahuKaal ?? '04:30 PM - 06:00 PM'}\n'
        '────────────────────\n'
        '🪔 *दैनिक वैदिक उपाय:* आज प्रातः भगवान सूर्य को तांबे के लोटे से अर्घ्य दें।\n\n'
        '📲 *लाइव दर्शन, चौघड़िया एवं कुंडली मिलान हेतु डाउनलोड करें OnlinePuja ऐप:*\n'
        'https://onlinepuja.live';
  }

  /// Formatted daily Horoscope share message.
  static String formatHoroscopeShare({
    required String signName,
    required String prediction,
    String? luckyNumber,
    String? luckyColor,
  }) {
    return '🔮 *आज का राशिफल: $signName*\n'
        '────────────────────\n'
        '✨ $prediction\n\n'
        '🔢 *शुभ अंक:* ${luckyNumber ?? '7'} | 🎨 *शुभ रंग:* ${luckyColor ?? 'पीला / केसरिया'}\n'
        '────────────────────\n'
        '🌟 *अपनी सम्पूर्ण जन्म कुंडली एवं दशा फल जानें:*\n'
        'https://onlinepuja.live/horoscope';
  }

  /// Formatted Live Mandir Darshan invite.
  static String formatDarshanShare({
    required String templeName,
    required String deity,
    required String timing,
    required String streamUrl,
  }) {
    return '🪔 *सादर प्रणाम! दिव्य दर्शन आमंत्रण*\n'
        '────────────────────\n'
        '🙏 *मंदिर:* $templeName\n'
        '🕉️ *विराजे देवता:* $deity\n'
        '⏰ *आरती समय:* $timing\n'
        '────────────────────\n'
        'घर बैठे 24/7 लाइव गर्भगृह दर्शन करें, अखंड दीप प्रज्वलित करें एवं घंटा नाद करें:\n'
        '$streamUrl\n\n'
        '✨ *OnlinePuja.live - भारत का पावन मंदिर दर्शन एवं वैदिक सेवा पोर्टल*';
  }

  /// Formatted Aarti Deepam Offering blessing share.
  static String formatDeepamShare({
    required String devoteeName,
    required String aartiName,
    required String gotra,
    required String streamUrl,
  }) {
    return '🪔 *हर हर महादेव! पावन दीपदान संपन्न*\n'
        '────────────────────\n'
        '🕉️ *यजमान:* $devoteeName\n'
        '🔱 *गोत्र:* $gotra\n'
        '✨ *पावन आरती:* $aartiName\n'
        '🪔 *संकल्प:* ₹11 पावन दीप सेवा अर्पित\n'
        '────────────────────\n'
        'घर बैठे श्री काशी विश्वनाथ एवं महा गंगा आरती का लाइव दर्शन करें व दीपदान करें:\n'
        '$streamUrl\n\n'
        '📲 *OnlinePuja.live - 24/7 लाइव गर्भगृह दर्शन व पूजा पोर्टल*';
  }

  /// Devotee viral referral invite message.
  static String formatReferralInvite({
    required String devoteeName,
    required String referralCode,
    int bonusAmount = 51,
  }) {
    return '🙏 *सादर प्रणाम!*\n\n'
        '$devoteeName ने आपको *OnlinePuja.live* पर आमंत्रित किया है।\n\n'
        '🎁 *आपके लिए विशेष भेंट:*\n'
        '• ₹$bonusAmount निःशुल्क पूजा वॉलेट बोनस\n'
        '• मात्र ₹1 में प्रथम वैदिक ज्योतिषी परामर्श\n'
        '• काशी विश्वनाथ, महाकाल एवं सोमनाथ 24/7 लाइव दर्शन\n\n'
        '📲 *अभी जुड़ें और अपना उपहार प्राप्त करें:*\n'
        'https://onlinepuja.live/invite/$referralCode';
  }
}

/// Growth & AI OS Central Service.
/// Manages live customization of AI Chat, Brand & Profiles, Live Mandir Feeds, and Growth Offers.
class GrowthAiOsService extends ChangeNotifier {
  GrowthAiOsService._();
  static final GrowthAiOsService instance = GrowthAiOsService._();

  static const _kTempleFeeds = 'growth_ai_os_temple_feeds';
  static const _kAiConfig = 'growth_ai_os_ai_config';
  static const _kBrandConfig = 'growth_ai_os_brand_config';
  static const _kGrowthConfig = 'growth_ai_os_growth_config';
  static const _kSocialConfig = 'growth_ai_os_social_config';

  bool _initialized = false;
  List<TempleFeed> _templeFeeds = [];
  AiChatConfig _aiConfig = const AiChatConfig(
    botName: 'Acharya Vashistha',
    botTagline: '24/7 Master Vedic AI Astrologer',
    welcomeGreeting:
        '🕉️ **हरि ॐ! सादर प्रणाम।**\n\nI am **Acharya Vashistha**, your AI Vedic Guide & Astrological Companion powered by the **OnlinePuja.live Vedic AI Engine**.\n\nAsk me anything about your **Kundli, Planetary Dashas, Career, Relationships, Gemstones, or Sacred Puja Remedies**.',
    systemPrompt:
        'You are Acharya Vashistha, an enlightened, compassionate, and deeply knowledgeable Vedic scholar and astrologer on OnlinePuja.live.',
    starterPrompts: [
      'What do my planetary transits (Gochar) indicate today?',
      'Which puja or mantra will remove obstacles in my career?',
      'Explain my Moon sign & Nakshatra characteristics',
      'Which sacred gemstone & Rudraksha is auspicious for me?',
      'Guidance on relationship harmony and Kundli Gun Milan',
    ],
  );

  BrandProfileConfig _brandConfig = const BrandProfileConfig();
  GrowthOfferConfig _growthConfig = const GrowthOfferConfig();
  SocialMediaConfig _socialConfig = const SocialMediaConfig();

  List<TempleFeed> get templeFeeds => List.unmodifiable(_templeFeeds);
  List<TempleFeed> get activeTempleFeeds =>
      _templeFeeds.where((f) => f.isActive).toList();

  AiChatConfig get aiConfig => _aiConfig;
  BrandProfileConfig get brandConfig => _brandConfig;
  GrowthOfferConfig get growthConfig => _growthConfig;
  SocialMediaConfig get socialConfig => _socialConfig;

  static const List<TempleFeed> defaultTempleFeeds = [
    TempleFeed(
      id: 'kashi',
      name: 'Shri Kashi Vishwanath Jyotirlinga',
      deity: 'Lord Shiva',
      location: 'Varanasi, Uttar Pradesh',
      timing: 'Mangala 3:00 AM • Bhog 11:30 AM • Sandhya 7:00 PM • Shayan 9:00 PM',
      thumbnailUrl:
          'https://images.unsplash.com/photo-1561361513-2d000a50f0dc?auto=format&fit=crop&w=1200&q=80',
      streamUrl: 'https://www.youtube.com/@ShriKashiVishwanathTempleTrust/live',
      officialWebUrl: 'https://shrikashivishwanath.org/frontend/home/livedarshan',
      description:
          'Official Live Sanctum Darshan of the first sacred Jyotirlinga on the holy banks of river Ganga. Witness daily Mangala Aarti (3:00 AM) and Vedic Abhishek directly from Kashi Vishwanath Dham.',
      viewerCount: 3820,
      chadhavaMinPrice: 11,
    ),
    TempleFeed(
      id: 'ganga_aarti',
      name: 'Dashashwamedh Maha Ganga Aarti',
      deity: 'Maa Ganga',
      location: 'Dashashwamedh Ghat, Varanasi',
      timing: 'Daily Sunset 6:30 PM - 7:45 PM',
      thumbnailUrl:
          'https://images.unsplash.com/photo-1596402184320-417e7178b2cd?auto=format&fit=crop&w=1200&q=80',
      streamUrl: 'https://www.youtube.com/@GangaAartiVaranasiOfficial/live',
      officialWebUrl: 'https://www.gangasevanidhi.org/',
      description:
          'The world-renowned Grand Sunset Aarti with sacred multi-tiered brass deepams, rhythmic shankha naad, and vedic hymns on the holy Dashashwamedh Ghat.',
      viewerCount: 5140,
      chadhavaMinPrice: 11,
    ),
    TempleFeed(
      id: 'mahakal',
      name: 'Shri Mahakaleshwar Jyotirlinga',
      deity: 'Lord Mahakal',
      location: 'Ujjain, Madhya Pradesh',
      timing: 'Bhasma Aarti 4:00 AM • Shringar 7:30 PM',
      thumbnailUrl:
          'https://images.unsplash.com/photo-1609766857041-ed402ea8069a?auto=format&fit=crop&w=1200&q=80',
      streamUrl: 'https://www.youtube.com/@shreemahakaleshwarmandiruj790/live',
      description:
          'Official Live holy Darshan & sacred Bhasma Aarti of the South-facing Dakshinmukhi Swayambhu Jyotirlinga in Avantika Puri.',
      viewerCount: 6200,
      chadhavaMinPrice: 51,
    ),
    TempleFeed(
      id: 'somnath',
      name: 'Somnath Mahadev Jyotirlinga',
      deity: 'Lord Shiva',
      location: 'Prabhas Patan, Gujarat',
      timing: 'Pratah 7:00 AM • Madhyahna 12:00 PM • Sandhya 7:00 PM',
      thumbnailUrl:
          'https://images.unsplash.com/photo-1621847468516-1ed5d0df56fe?auto=format&fit=crop&w=1200&q=80',
      streamUrl: 'https://www.youtube.com/@SomnathTempleOfficial/live',
      description:
          'First of the twelve sacred Aadi Jyotirlingas situated where sacred Saraswati, Hiran, and Kapila rivers meet the Arabian Sea.',
      viewerCount: 2950,
      chadhavaMinPrice: 51,
    ),
    TempleFeed(
      id: 'shirdi',
      name: 'Shirdi Sai Baba Samadhi Mandir',
      deity: 'Shirdi Sai Baba',
      location: 'Shirdi, Maharashtra',
      timing: 'Kakad 4:30 AM • Dhoop 6:30 PM • Shej 10:00 PM',
      thumbnailUrl:
          'https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=1200&q=80',
      streamUrl: 'https://www.youtube.com/@SaiBabaSansthanTrustShirdi/live',
      description:
          '24/7 Live Darshan & Aarti from the sacred Samadhi Mandir of Sai Baba. Receive divine blessings of Shraddha and Saburi.',
      viewerCount: 4180,
      chadhavaMinPrice: 51,
    ),
    TempleFeed(
      id: 'kedarnath',
      name: 'Shri Kedarnath Dham',
      deity: 'Lord Shiva',
      location: 'Rudraprayag, Uttarakhand',
      timing: 'Maha Abhishek 4:00 AM • Sandhya Aarti 6:30 PM',
      thumbnailUrl:
          'https://images.unsplash.com/photo-1626083758209-64c8dcf62678?auto=format&fit=crop&w=1200&q=80',
      streamUrl: 'https://www.youtube.com/@BadrinathKedarnathTemple/live',
      description:
          'Highest of the 12 Jyotirlingas, ensconced amidst pristine snow-covered peaks in the holy Garhwal Himalayas.',
      viewerCount: 4890,
      chadhavaMinPrice: 101,
    ),
  ];

  Future<void> init() async {
    if (_initialized) return;
    try {
      final sp = await SharedPreferences.getInstance();

      // Load Temple Feeds
      final feedsJson = sp.getString(_kTempleFeeds);
      if (feedsJson != null) {
        final decoded = json.decode(feedsJson);
        if (decoded is List) {
          _templeFeeds = decoded
              .map((e) => TempleFeed.fromJson(e as Map<String, dynamic>))
              .toList();
        }
      }
      if (_templeFeeds.isEmpty) {
        _templeFeeds = List.from(defaultTempleFeeds);
      }

      // Load AI Config
      final aiJson = sp.getString(_kAiConfig);
      if (aiJson != null) {
        final decoded = json.decode(aiJson);
        if (decoded is Map<String, dynamic>) {
          _aiConfig = AiChatConfig.fromJson(decoded);
        }
      }

      // Load Brand Config
      final brandJson = sp.getString(_kBrandConfig);
      if (brandJson != null) {
        final decoded = json.decode(brandJson);
        if (decoded is Map<String, dynamic>) {
          _brandConfig = BrandProfileConfig.fromJson(decoded);
        }
      }

      // Load Growth Config
      final growthJson = sp.getString(_kGrowthConfig);
      if (growthJson != null) {
        final decoded = json.decode(growthJson);
        if (decoded is Map<String, dynamic>) {
          _growthConfig = GrowthOfferConfig.fromJson(decoded);
        }
      }

      // Load Social Media & Autopilot Config
      final socialJson = sp.getString(_kSocialConfig);
      if (socialJson != null) {
        final decoded = json.decode(socialJson);
        if (decoded is Map<String, dynamic>) {
          _socialConfig = SocialMediaConfig.fromJson(decoded);
        }
      }

      _initialized = true;
      notifyListeners();
      // Hydrate with latest backend config asynchronously
      fetchRemoteConfig();
    } catch (e) {
      if (_templeFeeds.isEmpty) {
        _templeFeeds = List.from(defaultTempleFeeds);
      }
      _initialized = true;
    }
  }

  Future<void> saveTempleFeeds(List<TempleFeed> feeds) async {
    _templeFeeds = List.from(feeds);
    notifyListeners();
    final sp = await SharedPreferences.getInstance();
    await sp.setString(
        _kTempleFeeds, json.encode(feeds.map((f) => f.toJson()).toList()));
  }

  Future<void> updateTempleFeed(TempleFeed feed) async {
    final idx = _templeFeeds.indexWhere((f) => f.id == feed.id);
    if (idx != -1) {
      _templeFeeds[idx] = feed;
    } else {
      _templeFeeds.add(feed);
    }
    await saveTempleFeeds(_templeFeeds);
  }

  Future<void> deleteTempleFeed(String id) async {
    _templeFeeds.removeWhere((f) => f.id == id);
    await saveTempleFeeds(_templeFeeds);
  }

  Future<void> saveAiConfig(AiChatConfig config) async {
    _aiConfig = config;
    notifyListeners();
    final sp = await SharedPreferences.getInstance();
    await sp.setString(_kAiConfig, json.encode(config.toJson()));
  }

  Future<void> saveBrandConfig(BrandProfileConfig config) async {
    _brandConfig = config;
    notifyListeners();
    final sp = await SharedPreferences.getInstance();
    await sp.setString(_kBrandConfig, json.encode(config.toJson()));
  }

  Future<void> saveGrowthConfig(GrowthOfferConfig config) async {
    _growthConfig = config;
    notifyListeners();
    final sp = await SharedPreferences.getInstance();
    await sp.setString(_kGrowthConfig, json.encode(config.toJson()));
  }

  Future<void> saveSocialConfig(SocialMediaConfig config) async {
    _socialConfig = config;
    notifyListeners();
    final sp = await SharedPreferences.getInstance();
    await sp.setString(_kSocialConfig, json.encode(config.toJson()));
  }

  List<Map<String, dynamic>> _remoteFestivals = [];
  List<Map<String, dynamic>> get remoteFestivals => List.unmodifiable(_remoteFestivals);

  /// Fetch live dynamic growth, festivals, and referral config from backend
  Future<bool> fetchRemoteConfig({String baseUrl = 'https://onlinepuja.live'}) async {
    try {
      final res = await http.get(
        Uri.parse('$baseUrl/api/growth/config'),
        headers: {'Accept': 'application/json'},
      ).timeout(const Duration(seconds: 8));

      if (res.statusCode == 200) {
        final data = json.decode(res.body);
        if (data is Map<String, dynamic> && data['status'] == 200) {
          if (data['festivals'] is List) {
            _remoteFestivals = List<Map<String, dynamic>>.from(data['festivals']);
          }

          if (data['referral'] is Map<String, dynamic>) {
            final r = data['referral'] as Map<String, dynamic>;
            final bonusInr = (r['amountInr'] as num?)?.toInt() ?? _socialConfig.referralBonusAmount;
            _socialConfig = SocialMediaConfig(
              whatsappChannelUrl: _socialConfig.whatsappChannelUrl,
              telegramChannelUrl: _socialConfig.telegramChannelUrl,
              instagramHandle: _socialConfig.instagramHandle,
              youtubeChannelUrl: _socialConfig.youtubeChannelUrl,
              referralBonusAmount: bonusInr,
              referralDiscountAmount: _socialConfig.referralDiscountAmount,
              autopilotEnabled: _socialConfig.autopilotEnabled,
              autopilotPanchangMorningTime: _socialConfig.autopilotPanchangMorningTime,
              autopilotHoroscopeTime: _socialConfig.autopilotHoroscopeTime,
              autopilotAartiReminderTime: _socialConfig.autopilotAartiReminderTime,
              webhookEndpoint: _socialConfig.webhookEndpoint,
              telegramBotToken: _socialConfig.telegramBotToken,
              telegramChatId: _socialConfig.telegramChatId,
            );
          }

          if (data['firstConsultOffer'] is Map<String, dynamic>) {
            final f = data['firstConsultOffer'] as Map<String, dynamic>;
            final enabled = f['enabled'] as bool? ?? true;
            final price = (f['rate'] as num?)?.toInt() ?? 1;
            _growthConfig = GrowthOfferConfig(
              firstConsultOfferEnabled: enabled,
              firstConsultPrice: price,
              chadhavaTiers: _growthConfig.chadhavaTiers,
              liveDarshanEnabled: _growthConfig.liveDarshanEnabled,
              dailyHoroscopeEnabled: _growthConfig.dailyHoroscopeEnabled,
              freePrashnaPerDay: _growthConfig.freePrashnaPerDay,
            );
          }

          if (data['brand'] is Map<String, dynamic>) {
            final b = data['brand'] as Map<String, dynamic>;
            _brandConfig = BrandProfileConfig(
              brandName: b['brandName'] ?? _brandConfig.brandName,
              brandTagline: b['tagline'] ?? _brandConfig.brandTagline,
              supportPhone: b['supportPhone'] ?? _brandConfig.supportPhone,
              supportEmail: b['supportEmail'] ?? _brandConfig.supportEmail,
              websiteUrl: b['websiteUrl'] ?? _brandConfig.websiteUrl,
              appHubUrl: b['appDownloadUrl'] ?? _brandConfig.appHubUrl,
            );
          }

          notifyListeners();
          return true;
        }
      }
    } catch (e) {
      debugPrint('Error fetching remote growth config: $e');
    }
    return false;
  }

  Future<void> resetToDefaults() async {
    _templeFeeds = List.from(defaultTempleFeeds);
    _aiConfig = const AiChatConfig(
      botName: 'Acharya Vashistha',
      botTagline: '24/7 Master Vedic AI Astrologer',
      welcomeGreeting:
          '🕉️ **हरि ॐ! सादर प्रणाम।**\n\nI am **Acharya Vashistha**, your AI Vedic Guide & Astrological Companion powered by the **OnlinePuja.live Vedic AI Engine**.\n\nAsk me anything about your **Kundli, Planetary Dashas, Career, Relationships, Gemstones, or Sacred Puja Remedies**.',
      systemPrompt:
          'You are Acharya Vashistha, an enlightened, compassionate, and deeply knowledgeable Vedic scholar and astrologer on OnlinePuja.live.',
      starterPrompts: [
        'What do my planetary transits (Gochar) indicate today?',
        'Which puja or mantra will remove obstacles in my career?',
        'Explain my Moon sign & Nakshatra characteristics',
        'Which sacred gemstone & Rudraksha is auspicious for me?',
        'Guidance on relationship harmony and Kundli Gun Milan',
      ],
    );
    _brandConfig = const BrandProfileConfig();
    _growthConfig = const GrowthOfferConfig();
    _socialConfig = const SocialMediaConfig();
    notifyListeners();

    final sp = await SharedPreferences.getInstance();
    await sp.remove(_kTempleFeeds);
    await sp.remove(_kAiConfig);
    await sp.remove(_kBrandConfig);
    await sp.remove(_kGrowthConfig);
    await sp.remove(_kSocialConfig);
  }
}

