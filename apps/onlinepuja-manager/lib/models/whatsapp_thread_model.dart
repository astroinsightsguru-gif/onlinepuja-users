class WhatsAppMessage {
  final String id;
  final String text;
  final bool isFromCustomer;
  final DateTime timestamp;
  final String status; // 'sent', 'delivered', 'read'
  final String? mediaUrl;
  final String? mediaType; // 'image', 'video', 'pdf', 'audio'
  final bool isInternalNote;

  WhatsAppMessage({
    required this.id,
    required this.text,
    required this.isFromCustomer,
    required this.timestamp,
    this.status = 'delivered',
    this.mediaUrl,
    this.mediaType,
    this.isInternalNote = false,
  });

  factory WhatsAppMessage.fromJson(Map<String, dynamic> json) {
    return WhatsAppMessage(
      id: json['id']?.toString() ?? 'msg_${DateTime.now().millisecondsSinceEpoch}',
      text: (json['text'] ?? json['message'] ?? json['body'] ?? '').toString(),
      isFromCustomer: json['is_from_customer'] == true ||
          (json['is_from_customer'] == null && (json['sender'] == 'customer' || json['from'] != 'me')),
      timestamp: json['timestamp'] != null
          ? DateTime.tryParse(json['timestamp'].toString())?.toLocal() ?? DateTime.now()
          : DateTime.now(),
      status: (json['status'] ?? 'delivered').toString(),
      mediaUrl: json['media_url']?.toString(),
      mediaType: (json['media_type'] ?? json['media'])?.toString(),
      isInternalNote: json['is_internal_note'] == true,
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'text': text,
    'is_from_customer': isFromCustomer,
    'timestamp': timestamp.toIso8601String(),
    'status': status,
    'media_url': mediaUrl,
    'media_type': mediaType,
    'is_internal_note': isInternalNote,
  };
}

class WhatsAppThread {
  final String id;
  final String remoteJid;
  final String customerPhone;
  final String customerName;
  final String lastMessage;
  final bool lastMessageFromMe;
  final DateTime lastMessageTime;
  final int unreadCount;
  final String pujaInterest;
  final String gotra;
  final bool isAiAssisted;

  WhatsAppThread({
    required this.id,
    required this.remoteJid,
    required this.customerPhone,
    required this.customerName,
    required this.lastMessage,
    required this.lastMessageFromMe,
    required this.lastMessageTime,
    this.unreadCount = 0,
    this.pujaInterest = 'Vedic Puja',
    this.gotra = 'Kashyap',
    this.isAiAssisted = true,
  });

  bool get isSlaBreached {
    // If last message is from devotee and unanswered for > 15 mins
    if (!lastMessageFromMe && unreadCount > 0) {
      final diff = DateTime.now().difference(lastMessageTime);
      return diff.inMinutes >= 15;
    }
    return false;
  }

  String get timeAgo {
    final diff = DateTime.now().difference(lastMessageTime);
    if (diff.inMinutes < 1) return 'Just now';
    if (diff.inMinutes < 60) return '${diff.inMinutes}m ago';
    if (diff.inHours < 24) return '${diff.inHours}h ago';
    return '${diff.inDays}d ago';
  }

  factory WhatsAppThread.fromJson(Map<String, dynamic> json) {
    return WhatsAppThread(
      id: json['id']?.toString() ?? json['remote_jid'] ?? '',
      remoteJid: json['remote_jid'] ?? json['jid'] ?? '',
      customerPhone: json['phone'] ?? json['customer_phone'] ?? '',
      customerName: json['name'] ?? json['customer_name'] ?? 'Devotee',
      lastMessage: json['last_message'] ?? json['message'] ?? '',
      lastMessageFromMe: json['last_message_from_me'] == true || json['from_me'] == true,
      lastMessageTime: json['last_message_time'] != null
          ? DateTime.tryParse(json['last_message_time'].toString()) ?? DateTime.now()
          : DateTime.now(),
      unreadCount: int.tryParse(json['unread_count']?.toString() ?? '0') ?? 0,
      pujaInterest: json['puja_interest'] ?? 'Kashi Rudrabhishek',
      gotra: json['gotra'] ?? 'Kashyap',
      isAiAssisted: json['is_ai_assisted'] != false,
    );
  }
}
