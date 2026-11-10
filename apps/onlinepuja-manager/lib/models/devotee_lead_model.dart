class DevoteeLeadModel {
  final String id;
  final String name;
  final String phone;
  final String email;
  final String city;
  final String gotra;
  final String rashi;
  final String pujaInterest;
  final double dakshinaValue;
  final String stage; // 'inquiry', 'consultation', 'proposal_sent', 'sankalp_booked', 'completed', 'lost'
  final String source; // 'meta_ads', 'google_ads', 'whatsapp', 'website_chat', 'direct'
  final String assignedManager;
  final int score;
  final DateTime createdAt;
  final DateTime? lastContactAt;
  final DateTime? nextFollowUpAt;
  final List<String> tags;
  final List<String> notes;
  final String? lastChatSummary;

  DevoteeLeadModel({
    required this.id,
    required this.name,
    required this.phone,
    this.email = '',
    this.city = 'Varanasi',
    this.gotra = 'Kashyap',
    this.rashi = '',
    this.pujaInterest = 'Kashi Rudrabhishek',
    this.dakshinaValue = 2100.0,
    this.stage = 'inquiry',
    this.source = 'whatsapp',
    this.assignedManager = 'Head Acharya',
    this.score = 75,
    required this.createdAt,
    this.lastContactAt,
    this.nextFollowUpAt,
    this.tags = const [],
    this.notes = const [],
    this.lastChatSummary,
  });

  String get initials {
    final parts = name.trim().split(RegExp(r'\s+'));
    if (parts.length >= 2 && parts[0].isNotEmpty && parts[1].isNotEmpty) {
      return '${parts[0][0]}${parts[1][0]}'.toUpperCase();
    } else if (parts.isNotEmpty && parts[0].isNotEmpty) {
      return parts[0].substring(0, parts[0].length >= 2 ? 2 : 1).toUpperCase();
    }
    return 'DV';
  }

  factory DevoteeLeadModel.fromJson(Map<String, dynamic> json) {
    List<String> parsedTags = [];
    if (json['tags'] is List) {
      parsedTags = List<String>.from(json['tags'].map((e) => e.toString()));
    } else if (json['tags'] is String && (json['tags'] as String).isNotEmpty) {
      parsedTags = (json['tags'] as String).split(',').map((e) => e.trim()).toList();
    }

    List<String> parsedNotes = [];
    if (json['notes'] is List) {
      parsedNotes = List<String>.from(json['notes'].map((e) => e.toString()));
    } else if (json['notes'] is String && (json['notes'] as String).isNotEmpty) {
      parsedNotes = [json['notes'].toString()];
    }

    final rawStage = (json['stage'] ?? json['status'] ?? 'inquiry').toString().trim().toLowerCase();
    final normalizedStage = () {
      if (rawStage == 'new' || rawStage.isEmpty) return 'inquiry';
      if (rawStage == 'contacted' || rawStage == 'calling') return 'consultation';
      if (rawStage == 'quote_sent') return 'proposal_sent';
      if (rawStage == 'won' || rawStage == 'paid') return 'sankalp_booked';
      return rawStage;
    }();

    return DevoteeLeadModel(
      id: json['id']?.toString() ?? 'lead_${DateTime.now().millisecondsSinceEpoch}',
      name: (json['name'] ?? json['customer_name'] ?? 'Devotee').toString(),
      phone: (json['phone'] ?? json['contactNo'] ?? '').toString(),
      email: (json['email'] ?? '').toString(),
      city: (json['city'] ?? 'Varanasi').toString(),
      gotra: (json['gotra'] ?? 'Kashyap').toString(),
      rashi: (json['rashi'] ?? '').toString(),
      pujaInterest: (json['puja_interest'] ?? json['puja_name'] ?? 'Vedic Puja').toString(),
      dakshinaValue: double.tryParse((json['dakshina_value'] ?? json['deal_value'] ?? json['amount'] ?? '2100').toString()) ?? 2100.0,
      stage: normalizedStage,
      source: (json['source'] ?? json['source_channel'] ?? 'whatsapp').toString(),
      assignedManager: (json['assigned_manager'] ?? json['owner'] ?? 'Head Acharya').toString(),
      score: int.tryParse(json['score']?.toString() ?? '75') ?? 75,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString()) ?? DateTime.now()
          : DateTime.now(),
      lastContactAt: json['last_contact_at'] != null ? DateTime.tryParse(json['last_contact_at'].toString()) : null,
      nextFollowUpAt: json['next_follow_up_at'] != null ? DateTime.tryParse(json['next_follow_up_at'].toString()) : null,
      tags: parsedTags,
      notes: parsedNotes,
      lastChatSummary: json['last_chat_summary']?.toString(),
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'phone': phone,
    'email': email,
    'city': city,
    'gotra': gotra,
    'rashi': rashi,
    'puja_interest': pujaInterest,
    'dakshina_value': dakshinaValue,
    'stage': stage,
    'source': source,
    'assigned_manager': assignedManager,
    'score': score,
    'created_at': createdAt.toIso8601String(),
    'last_contact_at': lastContactAt?.toIso8601String(),
    'next_follow_up_at': nextFollowUpAt?.toIso8601String(),
    'tags': tags,
    'notes': notes,
    'last_chat_summary': lastChatSummary,
  };
}
