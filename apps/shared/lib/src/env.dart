/// Runtime environment for Online Puja v2.
///
/// Mirrors the legacy `Customer-app/lib/utils/config.dart` contract: same REST
/// base, same image base, so the new apps speak to the existing Laravel
/// backend at https://onlinepuja.live without any server change.
class Env {
  Env._();

  /// REST API base (no trailing slash handling needed: paths start with '/').
  static const String apiBase = 'https://onlinepuja.live/api';

  /// Base for images / uploads (profile etc. are relative to this).
  static const String imageBase = 'https://onlinepuja.live/';

  /// Public PDFs served by the backend.
  static const String pdfBase = 'https://onlinepuja.live/public';

  static const String websiteUrl = 'https://onlinepuja.live/';

  static const String privacyUrl = 'https://onlinepuja.live/privacy-policy';
  static const String termsUrl = 'https://onlinepuja.live/terms-conditions';
  static const String refundUrl = 'https://onlinepuja.live/refund-policy';

  /// LiveKit server used for free self-hosted audio/video (doc 04).
  /// Overridable at runtime once the RTC node is provisioned.
  static const String liveKitUrl = String.fromEnvironment(
    'LIVEKIT_URL',
    defaultValue: 'wss://rtc.onlinepuja.live',
  );

  /// Gemini free-tier proxy (Cosmic AI) routed through the backend.
  static const String aiChatPath = '/ai-chat/send';
}
