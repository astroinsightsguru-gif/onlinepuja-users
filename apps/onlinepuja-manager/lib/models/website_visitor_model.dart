class WebsiteVisitor {
  final String id;
  final String ip;
  final String city;
  final String country;
  final String flag;
  final String currentPage;
  final String pageTitle;
  final String referrer;
  final int durationSeconds;
  final int scrollDepth;
  final int intentScore; // 1 - 100
  final String intentLabel; // 'High Intent', 'Booking Evaluation', 'Engaged Devotee', 'Casual Browsing'
  final String device;
  final bool isOnline;
  final DateTime lastActive;
  final String? devoteeName;
  final String? devoteePhone;
  final String? gotra;

  const WebsiteVisitor({
    required this.id,
    required this.ip,
    required this.city,
    required this.country,
    required this.flag,
    required this.currentPage,
    required this.pageTitle,
    required this.referrer,
    required this.durationSeconds,
    required this.scrollDepth,
    required this.intentScore,
    required this.intentLabel,
    required this.device,
    required this.isOnline,
    required this.lastActive,
    this.devoteeName,
    this.devoteePhone,
    this.gotra,
  });

  static int _parseInt(dynamic val, int fallback) {
    if (val == null) return fallback;
    if (val is num) return val.toInt();
    if (val is String) return int.tryParse(val) ?? fallback;
    return fallback;
  }

  factory WebsiteVisitor.fromJson(Map<String, dynamic> json) {
    return WebsiteVisitor(
      id: json['id']?.toString() ?? 'visitor_${DateTime.now().millisecondsSinceEpoch}',
      ip: json['ip']?.toString() ?? '200.234.41.x',
      city: json['city']?.toString() ?? 'Varanasi',
      country: json['country']?.toString() ?? 'India',
      flag: json['flag']?.toString() ?? '🇮🇳',
      currentPage: json['current_page']?.toString() ?? json['currentPage']?.toString() ?? '/puja',
      pageTitle: json['page_title']?.toString() ?? json['pageTitle']?.toString() ?? 'Varanasi Temple Pujas',
      referrer: json['referrer']?.toString() ?? 'Google Search / Direct',
      durationSeconds: _parseInt(json['duration_seconds'] ?? json['durationSeconds'], 120),
      scrollDepth: _parseInt(json['scroll_depth'] ?? json['scrollDepth'], 75),
      intentScore: _parseInt(json['intent_score'] ?? json['intentScore'], 80),
      intentLabel: json['intent_label']?.toString() ?? json['intentLabel']?.toString() ?? 'High Intent',
      device: json['device']?.toString() ?? 'Mobile Web',
      isOnline: json['is_online'] == true || json['isOnline'] == true,
      lastActive: json['last_active'] != null
          ? DateTime.tryParse(json['last_active'].toString()) ?? DateTime.now()
          : DateTime.now(),
      devoteeName: json['devotee_name']?.toString() ?? json['devoteeName']?.toString(),
      devoteePhone: json['devotee_phone']?.toString() ?? json['devoteePhone']?.toString(),
      gotra: json['gotra']?.toString(),
    );
  }

  WebsiteVisitor copyWith({
    String? id,
    String? ip,
    String? city,
    String? country,
    String? flag,
    String? currentPage,
    String? pageTitle,
    String? referrer,
    int? durationSeconds,
    int? scrollDepth,
    int? intentScore,
    String? intentLabel,
    String? device,
    bool? isOnline,
    DateTime? lastActive,
    String? devoteeName,
    String? devoteePhone,
    String? gotra,
  }) {
    return WebsiteVisitor(
      id: id ?? this.id,
      ip: ip ?? this.ip,
      city: city ?? this.city,
      country: country ?? this.country,
      flag: flag ?? this.flag,
      currentPage: currentPage ?? this.currentPage,
      pageTitle: pageTitle ?? this.pageTitle,
      referrer: referrer ?? this.referrer,
      durationSeconds: durationSeconds ?? this.durationSeconds,
      scrollDepth: scrollDepth ?? this.scrollDepth,
      intentScore: intentScore ?? this.intentScore,
      intentLabel: intentLabel ?? this.intentLabel,
      device: device ?? this.device,
      isOnline: isOnline ?? this.isOnline,
      lastActive: lastActive ?? this.lastActive,
      devoteeName: devoteeName ?? this.devoteeName,
      devoteePhone: devoteePhone ?? this.devoteePhone,
      gotra: gotra ?? this.gotra,
    );
  }
}
