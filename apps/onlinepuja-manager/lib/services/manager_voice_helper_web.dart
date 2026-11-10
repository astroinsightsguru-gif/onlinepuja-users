import 'package:flutter/foundation.dart';
// ignore: avoid_web_libraries_in_flutter, deprecated_member_use
import 'dart:js' as js;

class ManagerVoiceHelper {
  static void init({
    required Function(String text) onResult,
    required VoidCallback onEnd,
  }) {
    if (kIsWeb) {
      js.context['onManagerSpeechResult'] = (dynamic text) {
        onResult(text.toString());
      };
      js.context['onManagerSpeechEnd'] = () {
        onEnd();
      };
    }
  }

  static bool startListening() {
    if (kIsWeb) {
      try {
        final res = js.context.callMethod('startManagerSpeech');
        return res == true;
      } catch (e) {
        debugPrint('startManagerSpeech error: $e');
      }
    }
    return false;
  }

  static void stopListening() {
    if (kIsWeb) {
      try {
        js.context.callMethod('stopManagerSpeech');
      } catch (e) {
        debugPrint('stopManagerSpeech error: $e');
      }
    }
  }

  static void speak(String text) {
    if (kIsWeb) {
      try {
        js.context.callMethod('speakManagerResponse', [text]);
      } catch (e) {
        debugPrint('speakManagerResponse error: $e');
      }
    }
  }
}
