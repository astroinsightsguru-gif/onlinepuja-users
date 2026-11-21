import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

/// Executive Authentication & Security Session for OnlinePuja Manager
class ManagerAuthSession extends ChangeNotifier {
  static const String _prefToken = 'op_manager_token';
  static const String _prefEmail = 'op_manager_email';
  static const String _prefName = 'op_manager_name';
  static const String _prefRole = 'op_manager_role';
  static const String _prefPermissions = 'op_manager_permissions';
  static const String _prefPhoto = 'op_manager_photo';
  static const String _prefPin = 'op_manager_pin';
  static const String _prefIsLocked = 'op_manager_is_locked';

  static const String loginEndpoint = 'https://onlinepuja.live/api/manager/login';
  static const String googleLoginEndpoint = 'https://onlinepuja.live/api/manager/google-login';
  static const String googleClientId = '943854607420-9q5ro9tfc1ms9n3987ovb8vsidlcs04r.apps.googleusercontent.com';

  bool _initialized = false;
  bool _isAuthenticated = false;
  bool _isLocked = false;
  String? _token;
  String _email = 'admin@onlinepuja.live';
  String _name = 'Online Puja Admin';
  String _role = 'SUPERADMIN';
  List<String> _permissions = ['*'];
  String? _photoUrl;
  String? _masterPin;

  bool get isInitialized => _initialized;
  bool get isAuthenticated => _isAuthenticated;
  bool get isLocked => _isLocked;
  String? get token => _token;
  String get email => _email;
  String get name => _name;
  String get role => _role;
  List<String> get permissions => _permissions;
  String? get photoUrl => _photoUrl;
  bool get hasPin => _masterPin != null && _masterPin!.isNotEmpty;

  bool get isSuperAdmin =>
      _role.toUpperCase() == 'SUPERADMIN' ||
      _role.toUpperCase() == 'ADMIN' ||
      _email.toLowerCase() == 'admin@onlinepuja.live' ||
      _permissions.contains('*');

  /// Check granular permission for a specific module
  bool hasPermission(String module) {
    if (isSuperAdmin || _permissions.contains('*')) return true;
    return _permissions.contains(module);
  }

  ManagerAuthSession() {
    _loadFromStorage();
  }

  Future<void> _loadFromStorage() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      _token = prefs.getString(_prefToken);
      _email = prefs.getString(_prefEmail) ?? 'admin@onlinepuja.live';
      _name = prefs.getString(_prefName) ?? 'Online Puja Admin';
      _role = prefs.getString(_prefRole) ?? 'SUPERADMIN';
      _photoUrl = prefs.getString(_prefPhoto);
      _masterPin = prefs.getString(_prefPin);
      _isLocked = prefs.getBool(_prefIsLocked) ?? false;

      final permsJson = prefs.getString(_prefPermissions);
      if (permsJson != null) {
        try {
          final decoded = jsonDecode(permsJson);
          if (decoded is List) {
            _permissions = decoded.map((e) => e.toString()).toList();
          }
        } catch (_) {}
      }

      _isAuthenticated = _token != null && _token!.isNotEmpty;
    } catch (_) {
      _isAuthenticated = false;
    } finally {
      _initialized = true;
      notifyListeners();
    }
  }

  /// 1-Click Google Sign-In for Superadmin & Authorized Team Members
  Future<({bool success, String message})> loginWithGoogle() async {
    try {
      final GoogleSignIn googleSignIn = GoogleSignIn(
        scopes: ['email', 'profile'],
        clientId: kIsWeb ? googleClientId : null,
      );

      final GoogleSignInAccount? account = await googleSignIn.signIn();
      if (account == null) {
        return (success: false, message: 'Google Sign-In was cancelled.');
      }

      final client = http.Client();
      http.Response res;
      try {
        res = await client.post(
          Uri.parse(googleLoginEndpoint),
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: jsonEncode({
            'email': account.email,
            'name': account.displayName ?? '',
            'googleId': account.id,
            'photoUrl': account.photoUrl ?? '',
          }),
        ).timeout(const Duration(seconds: 25));
      } finally {
        client.close();
      }

      dynamic decoded;
      try {
        decoded = jsonDecode(res.body);
      } catch (e) {
        return (
          success: false,
          message: 'Server returned non-JSON response (HTTP ${res.statusCode}).',
        );
      }

      final Map<dynamic, dynamic> data = (decoded is Map) ? decoded : {};

      if (res.statusCode == 200 && data['success'] == true) {
        _token = data['token']?.toString() ?? 'session_ok';
        final userRaw = data['user'];
        if (userRaw is Map) {
          _email = userRaw['email']?.toString() ?? account.email;
          _name = userRaw['name']?.toString() ?? account.displayName ?? 'Team Member';
          _photoUrl = userRaw['profile']?.toString() ?? account.photoUrl;
          _role = userRaw['role']?.toString() ?? 'SUPERADMIN';
        }

        final permsRaw = data['permissions'];
        if (permsRaw is List) {
          _permissions = permsRaw.map((e) => e.toString()).toList();
        } else {
          _permissions = ['*'];
        }

        _isAuthenticated = true;
        _isLocked = false;

        try {
          final prefs = await SharedPreferences.getInstance();
          await prefs.setString(_prefToken, _token!);
          await prefs.setString(_prefEmail, _email);
          await prefs.setString(_prefName, _name);
          await prefs.setString(_prefRole, _role);
          await prefs.setString(_prefPermissions, jsonEncode(_permissions));
          if (_photoUrl != null) {
            await prefs.setString(_prefPhoto, _photoUrl!);
          }
          await prefs.setBool(_prefIsLocked, false);
        } catch (storageErr) {
          debugPrint('SharedPreferences save warning: $storageErr');
        }

        notifyListeners();
        return (
          success: true,
          message: data['message']?.toString() ?? '1-Click Google Login Verified 🙏',
        );
      } else {
        final msg = data['message']?.toString() ??
            data['error']?.toString() ??
            'Access denied. Email is not an authorized team member in OnlinePuja Manager.';
        return (success: false, message: msg);
      }
    } catch (e) {
      debugPrint('Google login exception: $e');
      return (
        success: false,
        message: 'Google Sign-In failed: $e',
      );
    }
  }

  /// Login with Superadmin Email & Password
  Future<({bool success, String message})> loginWithPassword({
    required String email,
    required String password,
    String? setPin,
  }) async {
    try {
      final client = http.Client();
      http.Response res;
      try {
        res = await client.post(
          Uri.parse(loginEndpoint),
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: jsonEncode({
            'email': email.trim(),
            'password': password,
          }),
        ).timeout(const Duration(seconds: 25));
      } finally {
        client.close();
      }

      dynamic decoded;
      try {
        decoded = jsonDecode(res.body);
      } catch (e) {
        return (
          success: false,
          message: 'Server returned non-JSON response (HTTP ${res.statusCode}).',
        );
      }

      final Map<dynamic, dynamic> data = (decoded is Map) ? decoded : {};

      if (res.statusCode == 200 && data['success'] == true) {
        _token = data['token']?.toString() ?? 'session_ok';
        final userRaw = data['user'];
        if (userRaw is Map) {
          _email = userRaw['email']?.toString() ?? email;
          _name = userRaw['name']?.toString() ?? 'Online Puja Admin';
          _role = userRaw['role']?.toString() ?? 'SUPERADMIN';
        }
        _permissions = ['*'];

        if (setPin != null && setPin.length == 4) {
          _masterPin = setPin;
        }

        _isAuthenticated = true;
        _isLocked = false;

        try {
          final prefs = await SharedPreferences.getInstance();
          await prefs.setString(_prefToken, _token!);
          await prefs.setString(_prefEmail, _email);
          await prefs.setString(_prefName, _name);
          await prefs.setString(_prefRole, _role);
          await prefs.setString(_prefPermissions, jsonEncode(_permissions));
          await prefs.setBool(_prefIsLocked, false);
          if (_masterPin != null) {
            await prefs.setString(_prefPin, _masterPin!);
          }
        } catch (storageErr) {
          debugPrint('SharedPreferences save warning: $storageErr');
        }

        notifyListeners();
        return (
          success: true,
          message: data['message']?.toString() ?? 'Authenticated 🙏',
        );
      } else {
        final msg = data['message']?.toString() ??
            data['error']?.toString() ??
            'Authentication failed (HTTP ${res.statusCode})';
        return (success: false, message: msg);
      }
    } catch (e) {
      debugPrint('Manager login exception: $e');
      return (
        success: false,
        message: 'Connection error ($e). Please check your internet connection.',
      );
    }
  }

  /// Unlock with 4-Digit Quick PIN
  bool unlockWithPin(String pin) {
    if (_masterPin != null && _masterPin == pin) {
      _isLocked = false;
      SharedPreferences.getInstance().then((p) => p.setBool(_prefIsLocked, false));
      notifyListeners();
      return true;
    }
    return false;
  }

  /// Set or update the 4-digit Master Quick PIN
  Future<void> setMasterPin(String pin) async {
    _masterPin = pin;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_prefPin, pin);
    notifyListeners();
  }

  /// Instant lock command center
  void lockApp() {
    _isLocked = true;
    SharedPreferences.getInstance().then((p) => p.setBool(_prefIsLocked, true));
    notifyListeners();
  }

  /// Full executive logout
  Future<void> logout() async {
    _token = null;
    _isAuthenticated = false;
    _isLocked = false;
    _role = 'SUPERADMIN';
    _permissions = ['*'];
    _photoUrl = null;
    try {
      final googleSignIn = GoogleSignIn();
      await googleSignIn.signOut();
    } catch (_) {}
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_prefToken);
    await prefs.remove(_prefRole);
    await prefs.remove(_prefPermissions);
    await prefs.remove(_prefPhoto);
    await prefs.remove(_prefIsLocked);
    notifyListeners();
  }
}
