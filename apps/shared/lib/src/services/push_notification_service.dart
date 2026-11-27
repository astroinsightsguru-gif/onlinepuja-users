import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:onesignal_flutter/onesignal_flutter.dart';
import '../api/api_client.dart';

/// Centralized push notification service powered by OneSignal.
///
/// Handles:
/// - OneSignal SDK initialization
/// - Requesting Android 13+ / iOS notification permissions
/// - Synchronizing device token and `subscription_id` to Laravel backend (`POST /deviceDetails`)
/// - Aliasing the authenticated devotee / astrologer `userId` to OneSignal
class PushNotificationService {
  PushNotificationService._();
  static final PushNotificationService instance = PushNotificationService._();

  static const String defaultAppId = 'f80dc7c2-6ab0-4332-9e61-75d6a899a3ec';
  bool _initialized = false;
  int? _currentUserId;

  /// Optional callback invoked when a notification is tapped by the user.
  void Function(Map<String, dynamic> data)? onNotificationClick;

  /// Initialize OneSignal SDK. Safe to call multiple times.
  Future<void> init({String? appId}) async {
    if (kIsWeb) {
      debugPrint('[PushNotificationService] Push notifications are not supported on Web; skipping.');
      _initialized = true;
      return;
    }
    if (_initialized) return;

    try {
      final targetAppId = appId?.isNotEmpty == true ? appId! : defaultAppId;

      if (kDebugMode) {
        OneSignal.Debug.setLogLevel(OSLogLevel.verbose);
      } else {
        OneSignal.Debug.setLogLevel(OSLogLevel.none);
      }

      OneSignal.initialize(targetAppId);

      // Prompt for push notification permission (Android 13+ & iOS)
      await OneSignal.Notifications.requestPermission(true);

      // Listen for subscription changes (e.g. initial token grant)
      OneSignal.User.pushSubscription.addObserver((state) {
        final newSubId = state.current.id;
        if (newSubId != null && newSubId.isNotEmpty && _currentUserId != null) {
          syncDeviceToken(userId: _currentUserId!, subscriptionId: newSubId);
        }
      });

      // Handle notification clicks for deep linking / opening consultation requests
      OneSignal.Notifications.addClickListener((event) {
        final rawData = event.notification.additionalData;
        debugPrint('[PushNotificationService] Notification tapped: $rawData');
        if (rawData != null && onNotificationClick != null) {
          try {
            final map = Map<String, dynamic>.from(rawData);
            onNotificationClick!(map);
          } catch (e) {
            debugPrint('[PushNotificationService] Error parsing notification data: $e');
          }
        }
      });

      _initialized = true;
      debugPrint('[PushNotificationService] Initialized with App ID: $targetAppId');
    } catch (e) {
      debugPrint('[PushNotificationService] Initialization error: $e');
    }
  }

  /// Register or update device details on the Laravel backend (`POST /deviceDetails`).
  Future<void> syncDeviceToken({
    required int userId,
    String? subscriptionId,
  }) async {
    _currentUserId = userId;

    if (kIsWeb) {
      try {
        final deviceId = 'web_${userId}_${DateTime.now().millisecondsSinceEpoch}';
        await ApiClient.instance.post('/deviceDetails', auth: true, body: {
          'userId': userId,
          'appId': 1,
          'deviceId': deviceId,
          'subscription_id': subscriptionId ?? '',
          'deviceManufacturer': 'Web Browser',
          'deviceModel': 'Chrome/Web',
          'appVersion': '2.3.7',
        });
        debugPrint('[PushNotificationService] Web device recorded for user $userId');
      } catch (e) {
        debugPrint('[PushNotificationService] Web syncDeviceToken non-fatal error: $e');
      }
      return;
    }

    try {
      final subId = subscriptionId ?? OneSignal.User.pushSubscription.id;
      final token = OneSignal.User.pushSubscription.token;

      // Link user ID inside OneSignal
      try {
        await OneSignal.login(userId.toString());
      } catch (e) {
        debugPrint('[PushNotificationService] OneSignal.login error: $e');
      }

      final deviceId = subId?.isNotEmpty == true ? subId! : 'device_${userId}_${DateTime.now().millisecondsSinceEpoch}';

      await ApiClient.instance.post('/deviceDetails', auth: true, body: {
        'userId': userId,
        'appId': 1,
        'deviceId': deviceId,
        'subscription_id': subId ?? '',
        if (token != null && token.isNotEmpty) 'fcmToken': token,
        'deviceManufacturer': Platform.operatingSystem,
        'deviceModel': Platform.operatingSystemVersion,
        'appVersion': '2.3.7',
      });

      debugPrint('[PushNotificationService] Successfully synced device token to backend for user $userId (subId: $subId)');
    } catch (e) {
      debugPrint('[PushNotificationService] syncDeviceToken failed: $e');
    }
  }

  /// Logout cleanup when user signs out
  Future<void> logout() async {
    _currentUserId = null;
    if (kIsWeb) return;
    try {
      await OneSignal.logout();
    } catch (_) {}
  }
}
