import 'package:flutter/material.dart';
import 'package:op_shared/op_shared.dart';

/// App-wide session state for the customer app.
class AppSession extends ChangeNotifier {
  User? user;
  SystemFlags flags = SystemFlags.empty;
  bool booted = false;

  bool get isLoggedIn => SessionStore.instance.isLoggedIn;
  bool get isAuthenticated => isLoggedIn && user != null;
  String get myId => (user?.id ?? 0).toString();
  int get userId => user?.id ?? 0;

  ThemeMode get themeMode => SessionStore.instance.themeMode;

  Future<void> setThemeMode(ThemeMode mode) async {
    await SessionStore.instance.saveThemeMode(mode);
    notifyListeners();
  }

  Future<void> init() async {
    ApiClient.instance.tokenResolver = () => SessionStore.instance.token;
    ApiClient.instance.tokenTypeResolver =
        () => SessionStore.instance.tokenType;
    await SessionStore.instance.load();
    await LocaleManager.instance.init();
    user = SessionStore.instance.user;
    flags = SessionStore.instance.flags;
    booted = true;
    notifyListeners();

    // Initialize OneSignal & sync device push token (safely guarded)
    try {
      final appId = flags.get('OneSignalAppId');
      await PushNotificationService.instance.init(appId: appId.isNotEmpty ? appId : null);
      if (user != null && user!.id != null && user!.id! > 0) {
        await PushNotificationService.instance.syncDeviceToken(userId: user!.id!);
      }
    } catch (e) {
      debugPrint('[AppSession] PushNotificationService init error (non-fatal): $e');
    }
  }

  /// Ask backend to SMS the OTP (legacy `checkContactNoExistForUser`).
  Future<String?> sendOtp({
    required String contactNo,
    required String countryCode,
  }) =>
      AuthApi.instance.sendOtp(
        contactNo: contactNo,
        countryCode: countryCode,
        isPartner: false,
      );

  /// Login / auto-register (legacy `loginAppUser`). Saves token + user.
  Future<void> login({
    required String contactNo,
    required String countryCode,
    String? name,
    String? email,
    String? fcmToken,
  }) async {
    user = await AuthApi.instance.login(
      contactNo: contactNo,
      countryCode: countryCode,
      isPartner: false,
      name: name,
      email: email,
      fcmToken: fcmToken,
    );
    flags = SessionStore.instance.flags;
    if (user != null && user!.id != null && user!.id! > 0) {
      await PushNotificationService.instance.syncDeviceToken(userId: user!.id!);
    }
    notifyListeners();
  }

  /// Refresh profile + wallet from `getUserById`.
  Future<void> refreshUser() async {
    final id = user?.id;
    if (id == null || id == 0) return;
    try {
      user = await AuthApi.instance.getUser(id);
      notifyListeners();
    } on ApiException catch (e) {
      debugPrint('refreshUser failed: $e');
    }
  }

  /// Continue as a guest devotee (allows full app browsing without phone OTP).
  Future<void> continueAsGuest() async {
    user = User(
      id: 999999,
      name: 'Guest Devotee',
      contactNo: '9999999999',
      email: 'guest@onlinepuja.live',
      walletAmount: 0.0,
    );
    await SessionStore.instance.saveSession(
      token: 'guest_token_preview',
      tokenType: 'Bearer',
      user: user!,
    );
    notifyListeners();
  }

  Future<void> deleteAccount() async {
    final uid = user?.id;
    if (uid != null && uid > 0) {
      try {
        await ApiClient.instance.post('/user/delete', body: {'id': uid, 'userId': uid});
      } catch (e) {
        debugPrint('deleteAccount api best-effort: $e');
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
