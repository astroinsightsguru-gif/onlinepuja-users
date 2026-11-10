enum CallDirection { inbound, outbound, missed }

class CallLogModel {
  final String id;
  final String phone;
  final String customerName;
  final CallDirection direction;
  final String status; // completed, missed, busy, dialing
  final DateTime timestamp;
  final int durationSeconds;
  final String simCard;
  final String? recordingUrl;
  final String? notes;
  final bool aiQualified;
  final String? pujaInterest;
  final String? gotra;

  CallLogModel({
    required this.id,
    required this.phone,
    required this.customerName,
    required this.direction,
    this.status = 'completed',
    required this.timestamp,
    this.durationSeconds = 0,
    this.simCard = 'OnlinePuja Official (+91 70071 58014)',
    this.recordingUrl,
    this.notes,
    this.aiQualified = false,
    this.pujaInterest,
    this.gotra,
  });

  String get formattedDuration {
    if (durationSeconds <= 0) return '0s';
    final minutes = durationSeconds ~/ 60;
    final seconds = durationSeconds % 60;
    if (minutes == 0) return '${seconds}s';
    return '${minutes}m ${seconds}s';
  }

  factory CallLogModel.fromJson(Map<String, dynamic> json) {
    CallDirection dir = CallDirection.outbound;
    final dStr = (json['direction'] ?? json['type'] ?? 'outbound').toString().toLowerCase();
    if (dStr.contains('in')) {
      dir = CallDirection.inbound;
    } else if (dStr.contains('miss')) {
      dir = CallDirection.missed;
    }

    return CallLogModel(
      id: json['id']?.toString() ?? json['call_id'] ?? 'call_${DateTime.now().millisecondsSinceEpoch}',
      phone: json['phone'] ?? '',
      customerName: json['customer_name'] ?? json['name'] ?? 'Devotee',
      direction: dir,
      status: json['status'] ?? 'completed',
      timestamp: json['timestamp'] != null
          ? DateTime.tryParse(json['timestamp'].toString()) ?? DateTime.now()
          : DateTime.now(),
      durationSeconds: int.tryParse(json['duration']?.toString() ?? '0') ?? 0,
      simCard: json['sim_card'] ?? 'OnlinePuja Official (+91 70071 58014)',
      recordingUrl: json['recording_url'],
      notes: json['notes'],
      aiQualified: json['ai_qualified'] == true || json['ai_qualified'] == '1',
      pujaInterest: json['puja_interest'] ?? json['puja_name'] ?? 'Vedic Puja Consultation',
      gotra: json['gotra'],
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'phone': phone,
    'customer_name': customerName,
    'direction': direction.name,
    'status': status,
    'timestamp': timestamp.toIso8601String(),
    'duration': durationSeconds,
    'sim_card': simCard,
    'recording_url': recordingUrl,
    'notes': notes,
    'ai_qualified': aiQualified,
    'puja_interest': pujaInterest,
    'gotra': gotra,
  };
}
