import 'dart:async';
import 'package:flutter/material.dart';
import 'package:op_shared/op_shared.dart';

/// App-wide session state for the partner (astrologer) app.
///
/// Mirrors the customer `AppSession` but authenticates against the
/// astrologer login endpoints (`loginAppAstrologer`), so one JWT flows
/// through the same backend.
class PartnerSession extends ChangeNotifier {
  User? user;
  SystemFlags flags = SystemFlags.empty;
  bool booted = false;

  bool get isLoggedIn => SessionStore.instance.isLoggedIn;
  String get myId => (user?.astrologerId ?? user?.id ?? 0).toString();
  int get astrologerId => user?.astrologerId ?? user?.id ?? 0;

  ThemeMode get themeMode => SessionStore.instance.themeMode;

  Future<void> setThemeMode(ThemeMode mode) async {
    await SessionStore.instance.saveThemeMode(mode);
    notifyListeners();
  }

  /// Availability state surfaced in the home shell (legacy default online).
  String chatStatus = 'Online';
  String callStatus = 'Online';
  String videoCallStatus = 'Online';
  Timer? _breakTimer;
  int? activeBreakMinutes;

  Future<void> init() async {
    ApiClient.instance.tokenResolver = () => SessionStore.instance.token;
    ApiClient.instance.tokenTypeResolver =
        () => SessionStore.instance.tokenType;
    await SessionStore.instance.load();
    await LocaleManager.instance.init();
    user = SessionStore.instance.user;
    flags = SessionStore.instance.flags;
    chatStatus = user?.chatStatus ?? 'Online';
    callStatus = user?.callStatus ?? 'Online';
    videoCallStatus = user?.callStatus ?? 'Online';
    booted = true;
    notifyListeners();

    // Initialize OneSignal & sync astrologer device push token (using users.id)
    final appId = flags.get('OneSignalAppId');
    await PushNotificationService.instance.init(appId: appId.isNotEmpty ? appId : null);
    final targetUid = user?.userId ?? user?.id ?? user?.astrologerId;
    if (targetUid != null && targetUid > 0) {
      await PushNotificationService.instance.syncDeviceToken(userId: targetUid);
    }
  }

  /// Ask backend to SMS the OTP (legacy `checkContactNoExist`).
  Future<String?> sendOtp({
    required String contactNo,
    required String countryCode,
  }) =>
      AuthApi.instance.sendOtp(
        contactNo: contactNo,
        countryCode: countryCode,
        isPartner: true,
      );

  /// Login / auto-register (legacy `loginAppAstrologer`).
  Future<void> login({
    required String contactNo,
    required String countryCode,
    String? name,
    String? fcmToken,
  }) async {
    user = await AuthApi.instance.login(
      contactNo: contactNo,
      countryCode: countryCode,
      isPartner: true,
      name: name,
      fcmToken: fcmToken,
    );
    flags = SessionStore.instance.flags;
    chatStatus = user?.chatStatus ?? 'Online';
    callStatus = user?.callStatus ?? 'Online';
    videoCallStatus = user?.callStatus ?? 'Online';

    final targetUid = user?.userId ?? user?.id ?? user?.astrologerId;
    if (targetUid != null && targetUid > 0) {
      await PushNotificationService.instance.syncDeviceToken(userId: targetUid);
    }
    notifyListeners();
  }

  /// Toggle chat/call/video availability via `astrologer/update`.
  Future<void> setStatus({
    String? chat,
    String? call,
    String? videoCall,
  }) async {
    if (videoCall != null) {
      videoCallStatus = videoCall;
    }
    await PartnerApi.instance.setStatus(
      astrologerId: astrologerId,
      chatStatus: chat,
      callStatus: call,
    );
    chatStatus = chat ?? chatStatus;
    callStatus = call ?? callStatus;
    notifyListeners();
  }

  /// Start a timed break (DND) that automatically restores Online status.
  void startBreak(int minutes) {
    cancelBreak();
    activeBreakMinutes = minutes;
    setStatus(chat: 'Offline', call: 'Offline', videoCall: 'Offline');
    _breakTimer = Timer(Duration(minutes: minutes), () {
      activeBreakMinutes = null;
      setStatus(chat: 'Online', call: 'Online', videoCall: 'Online');
    });
    notifyListeners();
  }

  /// Cancel active break and return online.
  void cancelBreak() {
    _breakTimer?.cancel();
    _breakTimer = null;
    activeBreakMinutes = null;
    setStatus(chat: 'Online', call: 'Online', videoCall: 'Online');
  }

  /// Update consultation tariff rates and emergency pricing
  Future<void> updateRates({
    double? charge,
    double? audioCallRate,
    double? videoCallRate,
    double? reportRate,
    double? emergencyChatCharge,
    double? emergencyAudioCharge,
    double? emergencyVideoCharge,
    bool? emergencyChatStatus,
    bool? emergencyCallStatus,
  }) async {
    await PartnerApi.instance.updateRates(
      astrologerId: astrologerId,
      charge: charge,
      audioCallRate: audioCallRate,
      videoCallRate: videoCallRate,
      reportRate: reportRate,
      emergencyChatCharge: emergencyChatCharge,
      emergencyAudioCharge: emergencyAudioCharge,
      emergencyVideoCharge: emergencyVideoCharge,
      emergencyChatStatus: emergencyChatStatus,
      emergencyCallStatus: emergencyCallStatus,
    );
    if (user != null) {
      if (charge != null) user!.charge = charge;
      if (videoCallRate != null) user!.videoCallRate = videoCallRate;
      if (reportRate != null) user!.reportRate = reportRate;
      if (emergencyChatCharge != null) {
        user!.emergencyChatCharge = emergencyChatCharge;
      }
      if (emergencyAudioCharge != null) {
        user!.emergencyAudioCharge = emergencyAudioCharge;
      }
      if (emergencyVideoCharge != null) {
        user!.emergencyVideoCharge = emergencyVideoCharge;
      }
      if (emergencyChatStatus != null) {
        user!.emergencyChatStatus = emergencyChatStatus;
      }
      if (emergencyCallStatus != null) {
        user!.emergencyCallStatus = emergencyCallStatus;
      }
      await SessionStore.instance.saveUser(user!);
    }
    notifyListeners();
  }

  /// Update astrologer Bank & UPI payout KYC account details.
  Future<void> updateBankDetails({
    required int astrologerId,
    String? bankName,
    String? accountNumber,
    String? accountHolderName,
    String? ifscCode,
    String? bankBranch,
    String? accountType,
    String? upi,
    String? pancardNo,
    String? aadharNo,
  }) async {
    await PartnerApi.instance.updateBankDetails(
      astrologerId: astrologerId,
      bankName: bankName,
      accountNumber: accountNumber,
      accountHolderName: accountHolderName,
      ifscCode: ifscCode,
      bankBranch: bankBranch,
      accountType: accountType,
      upi: upi,
      pancardNo: pancardNo,
      aadharNo: aadharNo,
    );
    if (user != null) {
      if (bankName != null) user!.bankName = bankName;
      if (accountNumber != null) user!.accountNumber = accountNumber;
      if (accountHolderName != null) user!.accountHolderName = accountHolderName;
      if (ifscCode != null) user!.ifscCode = ifscCode;
      if (bankBranch != null) user!.bankBranch = bankBranch;
      if (accountType != null) user!.accountType = accountType;
      if (upi != null) user!.upi = upi;
      if (pancardNo != null) user!.pancardNo = pancardNo;
      if (aadharNo != null) user!.aadharNo = aadharNo;
      await SessionStore.instance.saveUser(user!);
    }
    notifyListeners();
  }

  /// Refresh profile + wallet for astrologer.
  Future<void> refreshUser() async {
    final aid = astrologerId > 0 ? astrologerId : (user?.id ?? 0);
    if (aid <= 0) return;
    try {
      final res = await ApiClient.instance.post('/getAstrologerForCustomer', body: {
        'astrologerId': aid,
      });
      if (res is Map<String, dynamic>) {
        final rl = res['recordList'];
        if (rl is List && rl.isNotEmpty && rl.first is Map<String, dynamic>) {
          user = User.fromJson(rl.first as Map<String, dynamic>);
          chatStatus = user?.chatStatus ?? chatStatus;
          callStatus = user?.callStatus ?? callStatus;
          videoCallStatus = user?.callStatus ?? videoCallStatus;
          await SessionStore.instance.saveUser(user!);
          notifyListeners();
        }
      }
    } catch (e) {
      debugPrint('[PartnerSession] refreshUser best-effort: $e');
    }
  }

  Future<void> deleteAccount() async {
    final uid = user?.id;
    if (uid != null && uid > 0) {
      try {
        final aid = astrologerId;
        await ApiClient.instance.post('/astrologer/delete', body: {'id': aid > 0 ? aid : uid, 'astrologerId': aid > 0 ? aid : uid, 'userId': uid});
      } catch (e) {
        debugPrint('deleteAstrologer api best-effort: $e');
      }
    }
    await logout();
  }

  Future<void> logout() async {
    await AuthApi.instance.logout();
    user = null;
    notifyListeners();
  }
}
