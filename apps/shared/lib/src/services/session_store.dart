import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../models/system_flag.dart';
import '../models/user.dart';

/// Persists the auth token + user (same keys as the legacy apps so a device
/// upgraded from v1 stays logged in where possible).
class SessionStore {
  SessionStore._();
  static final SessionStore instance = SessionStore._();

  static const _kToken = 'token';
  static const _kTokenType = 'tokenType';
  static const _kUser = 'currentUser';
  static const _kFlags = 'systemFlags';
  static const _kThemeMode = 'themeMode';

  SharedPreferences? _prefs;

  Future<SharedPreferences> get _sp async =>
      _prefs ??= await SharedPreferences.getInstance();

  String? token;
  String? tokenType;
  User? user;
  SystemFlags flags = SystemFlags.empty;
  ThemeMode themeMode = ThemeMode.system;

  bool get isLoggedIn =>
      token != null &&
      token!.trim().isNotEmpty &&
      ((user?.id ?? 0) > 0 ||
          (user?.userId ?? 0) > 0 ||
          (user?.astrologerId ?? 0) > 0);

  Future<void> load() async {
    final sp = await _sp;
    token = sp.getString(_kToken);
    if (token != null && token!.trim().isEmpty) {
      token = null;
    }
    tokenType = sp.getString(_kTokenType) ?? 'Bearer';
    final raw = sp.getString(_kUser);
    if (raw != null && raw.trim().isNotEmpty) {
      try {
        final decoded = json.decode(raw);
        if (decoded is Map<String, dynamic>) {
          user = User.fromJson(decoded);
          if ((user?.id ?? 0) <= 0 &&
              (user?.userId ?? 0) <= 0 &&
              (user?.astrologerId ?? 0) <= 0) {
            debugPrint('[SessionStore] Stored user has no valid ID, treating as null');
            user = null;
          }
        }
      } catch (e) {
        debugPrint('[SessionStore] Error parsing cached user: $e');
      }
    }
    final rawFlags = sp.getString(_kFlags);
    if (rawFlags != null) {
      try {
        final list = (json.decode(rawFlags) as List)
            .map((e) => SystemFlag.fromJson(e as Map<String, dynamic>));
        flags = SystemFlags(list);
      } catch (_) {}
    }
    final rawTheme = sp.getString(_kThemeMode);
    if (rawTheme == 'light') {
      themeMode = ThemeMode.light;
    } else if (rawTheme == 'dark') {
      themeMode = ThemeMode.dark;
    } else {
      themeMode = ThemeMode.system;
    }
  }

  Future<void> saveThemeMode(ThemeMode mode) async {
    themeMode = mode;
    final sp = await _sp;
    await sp.setString(_kThemeMode, mode.name);
  }

  Future<void> saveSession({
    required String token,
    required String tokenType,
    required User user,
  }) async {
    this.token = token;
    this.tokenType = tokenType;
    this.user = user;
    final sp = await _sp;
    await sp.setString(_kToken, token);
    await sp.setString(_kTokenType, tokenType);
    await sp.setString(_kUser, json.encode(user.toJson()));
  }

  Future<void> saveUser(User user) async {
    this.user = user;
    final sp = await _sp;
    await sp.setString(_kUser, json.encode(user.toJson()));
  }

  Future<void> saveFlags(SystemFlags flags) async {
    this.flags = flags;
    final sp = await _sp;
    await sp.setString(_kFlags, json.encode(flags.toRaw()));
  }

  Future<void> clear() async {
    token = null;
    tokenType = null;
    user = null;
    final sp = await _sp;
    await sp.remove(_kToken);
    await sp.remove(_kTokenType);
    await sp.remove(_kUser);
  }
}
