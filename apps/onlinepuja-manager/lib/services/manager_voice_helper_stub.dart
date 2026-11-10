import 'package:flutter/foundation.dart';

class ManagerVoiceHelper {
  static void init({
    required Function(String text) onResult,
    required VoidCallback onEnd,
  }) {
    // Stub for non-web platforms (Android / iOS)
  }

  static bool startListening() {
    return false;
  }

  static void stopListening() {}

  static void speak(String text) {}
}
