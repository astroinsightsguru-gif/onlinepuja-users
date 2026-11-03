/// Signed-in app user. Field names follow the legacy API contract exactly
/// (`Customer-app/lib/model/current_user_model.dart`).
class User {
  User({
    this.id,
    this.userId,
    this.astrologerId,
    this.name = 'User',
    this.contactNo,
    this.email = '',
    this.birthDate,
    this.birthTime = '',
    this.profile = '',
    this.birthPlace = '',
    this.gender = 'Male',
    this.walletAmount = 0,
    this.countryCode = '+91',
    this.isFreeChat = false,
    this.chatStatus = 'Online',
    this.callStatus = 'Online',
    this.charge = 25.0,
    this.videoCallRate = 50.0,
    this.reportRate = 199.0,
    this.emergencyChatCharge = 50.0,
    this.emergencyAudioCharge = 70.0,
    this.emergencyVideoCharge = 100.0,
    this.emergencyChatStatus = false,
    this.emergencyCallStatus = false,
    this.bankName,
    this.accountNumber,
    this.accountHolderName,
    this.ifscCode,
    this.bankBranch,
    this.accountType,
    this.upi,
    this.pancardNo,
    this.aadharNo,
  });

  int? id;
  int? userId;
  int? astrologerId;
  String? name;
  String? contactNo;
  String? email;
  DateTime? birthDate;
  String? birthTime;
  String? profile;
  String? birthPlace;
  String? gender;
  double walletAmount;
  String? countryCode;
  bool isFreeChat;
  String chatStatus;
  String callStatus;
  double charge;
  double videoCallRate;
  double reportRate;
  double emergencyChatCharge;
  double emergencyAudioCharge;
  double emergencyVideoCharge;
  bool emergencyChatStatus;
  bool emergencyCallStatus;
  String? bankName;
  String? accountNumber;
  String? accountHolderName;
  String? ifscCode;
  String? bankBranch;
  String? accountType;
  String? upi;
  String? pancardNo;
  String? aadharNo;

  static bool _b(dynamic v) {
    if (v == null) return false;
    if (v is bool) return v;
    if (v is num) return v != 0;
    final s = v.toString().trim().toLowerCase();
    return s == '1' || s == 'true' || s == 'yes';
  }

  static double _d(dynamic v, [double def = 0.0]) {
    if (v == null) return def;
    if (v is num) return v.toDouble();
    return double.tryParse(v.toString()) ?? def;
  }

  User.fromJson(Map<String, dynamic> json)
      : id = json['id'] == null
            ? null
            : (json['id'] is num
                ? (json['id'] as num).toInt()
                : int.tryParse(json['id'].toString())),
        userId = json['userId'] == null
            ? (json['user_id'] == null
                ? null
                : (json['user_id'] is num
                    ? (json['user_id'] as num).toInt()
                    : int.tryParse(json['user_id'].toString())))
            : (json['userId'] is num
                ? (json['userId'] as num).toInt()
                : int.tryParse(json['userId'].toString())),
        astrologerId = json['astrologerId'] == null
            ? (json['astrologer_id'] == null
                ? (json['userId'] != null || json['user_id'] != null
                    ? (json['id'] is num
                        ? (json['id'] as num).toInt()
                        : int.tryParse(json['id']?.toString() ?? ''))
                    : null)
                : (json['astrologer_id'] is num
                    ? (json['astrologer_id'] as num).toInt()
                    : int.tryParse(json['astrologer_id'].toString())))
            : (json['astrologerId'] is num
                ? (json['astrologerId'] as num).toInt()
                : int.tryParse(json['astrologerId'].toString())),
        name = json['name']?.toString() ?? 'User',
        contactNo = json['contactNo']?.toString() ?? '',
        email = json['email']?.toString() ?? '',
        birthDate = json['birthDate'] == null
            ? null
            : DateTime.tryParse(json['birthDate'].toString()),
        birthTime = json['birthTime']?.toString() ?? '',
        profile = (json['profile'] ?? json['profileImage'])?.toString() ?? '',
        birthPlace = json['birthPlace']?.toString() ?? '',
        gender = json['gender']?.toString() ?? 'Male',
        walletAmount = double.tryParse((json['totalWalletAmount'] ??
                    json['walletAmount'] ??
                    json['wallet_amount'] ??
                    json['wallet'])
                ?.toString() ??
            '0') ??
            0,
        countryCode = json['countryCode']?.toString() ?? '+91',
        isFreeChat = _b(json['is_freechat']),
        chatStatus = json['chatStatus']?.toString() ?? 'Online',
        callStatus = json['callStatus']?.toString() ?? 'Online',
        charge = _d(json['charge'], 25.0),
        videoCallRate = _d(json['videoCallRate'], 50.0),
        reportRate = _d(json['reportRate'], 199.0),
        emergencyChatCharge = _d(json['emergency_chat_charge'], 50.0),
        emergencyAudioCharge = _d(json['emergency_audio_charge'], 70.0),
        emergencyVideoCharge = _d(json['emergency_video_charge'], 100.0),
        emergencyChatStatus = _b(json['emergencyChatStatus']),
        emergencyCallStatus = _b(json['emergencyCallStatus']),
        bankName = json['bankName']?.toString(),
        accountNumber = json['accountNumber']?.toString(),
        accountHolderName = json['accountHolderName']?.toString(),
        ifscCode = json['ifscCode']?.toString(),
        bankBranch = json['bankBranch']?.toString(),
        accountType = json['accountType']?.toString(),
        upi = json['upi']?.toString(),
        pancardNo = json['pancardNo']?.toString(),
        aadharNo = json['aadharNo']?.toString();

  Map<String, dynamic> toJson() => {
        'id': id,
        'userId': userId ?? id,
        'astrologerId': astrologerId ?? (userId != null ? id : null),
        'name': name,
        'contactNo': contactNo,
        'email': email,
        'birthDate': birthDate?.toIso8601String(),
        'birthTime': birthTime,
        'profile': profile,
        'birthPlace': birthPlace,
        'gender': gender,
        'totalWalletAmount': walletAmount,
        'countryCode': countryCode,
        'is_freechat': isFreeChat,
        'chatStatus': chatStatus,
        'callStatus': callStatus,
        'charge': charge,
        'videoCallRate': videoCallRate,
        'reportRate': reportRate,
        'emergency_chat_charge': emergencyChatCharge,
        'emergency_audio_charge': emergencyAudioCharge,
        'emergency_video_charge': emergencyVideoCharge,
        'emergencyChatStatus': emergencyChatStatus,
        'emergencyCallStatus': emergencyCallStatus,
        'bankName': bankName,
        'accountNumber': accountNumber,
        'accountHolderName': accountHolderName,
        'ifscCode': ifscCode,
        'bankBranch': bankBranch,
        'accountType': accountType,
        'upi': upi,
        'pancardNo': pancardNo,
        'aadharNo': aadharNo,
      };

  String get displayName {
    final n = (name ?? '').trim();
    if (n.isEmpty || n.toLowerCase() == 'user') return 'Devotee';
    return n;
  }
}
