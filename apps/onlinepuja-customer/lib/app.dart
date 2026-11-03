import 'package:flutter/material.dart';
import 'package:op_shared/op_shared.dart';
import 'package:provider/provider.dart';

import 'state/app_session.dart';
import 'ui/screens/astrologer/astrologer_detail_screen.dart';
import 'ui/screens/auth/otp_screen.dart';
import 'ui/screens/auth/phone_login_screen.dart';
import 'ui/screens/blog/blog_detail_screen.dart';
import 'ui/screens/blog/blog_screen.dart';
import 'ui/screens/call/audio_call_screen.dart';
import 'ui/screens/charity/annadaan_screen.dart';
import 'ui/screens/chat/chat_session_screen.dart';
import 'ui/screens/cosmic_ai_screen.dart';
import 'ui/screens/darshan/live_darshan_screen.dart';
import 'ui/screens/explore/explore_screen.dart';
import 'ui/screens/explore/prashna_oracle_screen.dart';
import 'ui/screens/explore/swapna_shastra_screen.dart';
import 'ui/screens/explore/vedic_audio_screen.dart';
import 'ui/screens/history/history_screen.dart';
import 'ui/screens/horoscope/daily_horoscope_screen.dart';
import 'ui/screens/japa/japa_mala_screen.dart';
import 'ui/screens/kp/kp_calendar_screen.dart';
import 'ui/screens/kundli/kundli_detail_screen.dart';
import 'ui/screens/kundli/kundli_list_screen.dart';
import 'ui/screens/kundli/kundli_matching_screen.dart';
import 'ui/screens/main_shell.dart';
import 'ui/screens/mall/mall_screen.dart';
import 'ui/screens/mall/product_detail_screen.dart';
import 'ui/screens/notifications_screen.dart';
import 'ui/screens/orders/prasadam_tracker_screen.dart';
import 'ui/screens/panchang/choghadiya_radar_screen.dart';
import 'ui/screens/panchang/panchang_screen.dart';
import 'ui/screens/profile/family_gotra_vault_screen.dart';
import 'ui/screens/profile/wallet_screen.dart';
import 'ui/screens/puja/puja_detail_screen.dart';
import 'ui/screens/puja/puja_list_screen.dart';
import 'ui/screens/puja/sankalp_vault_screen.dart';
import 'ui/screens/shorts/spiritual_shorts_screen.dart';
import 'ui/screens/splash_screen.dart';

import 'ui/theme/customer_theme.dart';

class OnlinePujaApp extends StatelessWidget {
  const OnlinePujaApp({super.key});

  @override
  Widget build(BuildContext context) {
    final session = context.watch<AppSession>();
    return MaterialApp(
      title: 'OnlinePuja.live',
      debugShowCheckedModeBanner: false,
      theme: CustomerTheme.light(),
      darkTheme: CustomerTheme.dark(),
      themeMode: session.themeMode,
      initialRoute: SplashScreen.route,
      routes: {
        SplashScreen.route: (_) => const SplashScreen(),
        PhoneLoginScreen.route: (_) => const PhoneLoginScreen(),
        OtpScreen.route: (_) => const OtpScreen(),
        MainShell.route: (_) => const MainShell(),
        WalletScreen.route: (_) => const WalletScreen(),
        PrashnaOracleScreen.route: (_) => const PrashnaOracleScreen(),
        LiveDarshanScreen.route: (_) => const LiveDarshanScreen(),
        JapaMalaScreen.route: (_) => const JapaMalaScreen(),
        SwapnaShastraScreen.route: (_) => const SwapnaShastraScreen(),
        AnnadaanScreen.route: (_) => const AnnadaanScreen(),
        NotificationsScreen.route: (_) => const NotificationsScreen(),
        DailyHoroscopeScreen.route: (_) => const DailyHoroscopeScreen(),
        PanchangScreen.route: (_) => const PanchangScreen(),
        ChoghadiyaRadarScreen.route: (_) => const ChoghadiyaRadarScreen(),
        KundliListScreen.route: (_) => const KundliListScreen(),
        KundliMatchingScreen.route: (_) => const KundliMatchingScreen(),
        PujaListScreen.route: (_) => const PujaListScreen(),
        SankalpVaultScreen.route: (_) => const SankalpVaultScreen(),
        FamilyGotraVaultScreen.route: (_) => const FamilyGotraVaultScreen(),
        MallScreen.route: (_) => const MallScreen(),
        OnlinePujaAiScreen.route: (_) => const OnlinePujaAiScreen(),
        '/cosmic-ai': (_) => const OnlinePujaAiScreen(),
        BlogScreen.route: (_) => const BlogScreen(),
        HistoryScreen.route: (_) => const HistoryScreen(),
        KpCalendarScreen.route: (_) => const KpCalendarScreen(),
        ExploreScreen.route: (_) => const ExploreScreen(),
        VedicAudioScreen.route: (_) => const VedicAudioScreen(),
        SpiritualShortsScreen.route: (_) => const SpiritualShortsScreen(),
      },
      onGenerateRoute: (settings) {
        final args = settings.arguments;
        Map<String, dynamic> map() =>
            args is Map<String, dynamic> ? args : const {};
        int asInt(dynamic v, [int fallback = 0]) =>
            v is int ? v : (v is num ? v.toInt() : (int.tryParse(v?.toString() ?? '') ?? fallback));
        switch (settings.name) {
          case AstrologerDetailScreen.route:
            return MaterialPageRoute(
              builder: (_) =>
                  AstrologerDetailScreen(id: asInt(map()['astrologerId'])),
            );
          case ChatSessionScreen.route:
            return MaterialPageRoute(
              builder: (_) => ChatSessionScreen(
                astrologerId: asInt(map()['astrologerId']),
                astrologerName: map()['astrologerName'] as String? ?? 'Astrologer',
                ratePerMinute: ((map()['ratePerMinute'] as num?)?.toDouble()) ?? 15.0,
                sessionId: map()['sessionId'] as String?,
                isFree: map()['isFree'] as bool? ?? false,
              ),
            );
          case PrasadamTrackerScreen.route:
            return MaterialPageRoute(
              builder: (_) => PrasadamTrackerScreen(
                orderId: map()['orderId'] as String?,
                pujaName: map()['pujaName'] as String?,
                temple: map()['temple'] as String?,
                trackingCode: map()['trackingCode'] as String?,
                courierName: map()['courierName'] as String?,
                currentStep: asInt(map()['currentStep'], 3),
              ),
            );
          case AudioCallScreen.route:
            return MaterialPageRoute(
              builder: (_) => AudioCallScreen(
                astrologerId: asInt(map()['astrologerId']),
                astrologerName:
                    map()['astrologerName'] as String? ?? 'Astrologer',
                sessionId: map()['sessionId'] as String?,
                isVideo: map()['isVideo'] as bool? ?? false,
              ),
            );
          case KundliDetailScreen.route:
            if (args is Kundli) {
              return MaterialPageRoute(
                builder: (_) => KundliDetailScreen(kundli: args),
              );
            }
            if (args is Map<String, dynamic>) {
              final k = args['kundli'] is Kundli
                  ? args['kundli'] as Kundli
                  : Kundli.fromJson(args);
              return MaterialPageRoute(
                builder: (_) => KundliDetailScreen(kundli: k),
              );
            }
            break;
          case PujaDetailScreen.route:
            if (args is Puja) {
              return MaterialPageRoute(
                builder: (_) => PujaDetailScreen(puja: args),
              );
            }
            if (args is int) {
              return MaterialPageRoute(
                builder: (_) => PujaDetailScreen(puja: Puja(id: args, title: 'Vedic Sacred Puja')),
              );
            }
            if (args is Map<String, dynamic>) {
              final id = asInt(args['id'] ?? args['pujaId'] ?? args['puja_id']);
              final title = args['title']?.toString() ?? args['puja_title']?.toString() ?? 'Vedic Sacred Puja';
              return MaterialPageRoute(
                builder: (_) => PujaDetailScreen(puja: Puja(id: id, title: title)),
              );
            }
            break;
          case ProductDetailScreen.route:
            if (args is Product) {
              return MaterialPageRoute(
                builder: (_) => ProductDetailScreen(product: args),
              );
            }
            if (args is Map<String, dynamic>) {
              final p = args['product'] is Product
                  ? args['product'] as Product
                  : Product.fromJson(args);
              return MaterialPageRoute(
                builder: (_) => ProductDetailScreen(product: p),
              );
            }
            break;
          case BlogDetailScreen.route:
            if (args is Blog) {
              return MaterialPageRoute(
                builder: (_) => BlogDetailScreen(blog: args),
              );
            }
            if (args is Map<String, dynamic>) {
              final b = args['blog'] is Blog
                  ? args['blog'] as Blog
                  : Blog.fromJson(args);
              return MaterialPageRoute(
                builder: (_) => BlogDetailScreen(blog: b),
              );
            }
            break;
          case SpiritualShortsScreen.route:
            final idx = args is int
                ? args
                : (args is Map<String, dynamic> ? asInt(args['initialIndex'] ?? args['index']) : 0);
            return MaterialPageRoute(
              builder: (_) => SpiritualShortsScreen(initialIndex: idx),
            );
        }
        return MaterialPageRoute(builder: (_) => const MainShell());
      },
    );
  }
}

/// Convenience navigation helpers.
extension OpNav on BuildContext {
  Future<void> openAstrologer(int id) => pushNamed(
      AstrologerDetailScreen.route, {'astrologerId': id});

  Future<void> openChat({
    required int astrologerId,
    required String astrologerName,
    double ratePerMinute = 15.0,
    String? sessionId,
    bool isFree = false,
  }) =>
      pushNamed(ChatSessionScreen.route, {
        'astrologerId': astrologerId,
        'astrologerName': astrologerName,
        'ratePerMinute': ratePerMinute,
        'sessionId': sessionId,
        'isFree': isFree,
      });

  Future<void> openCall({
    required int astrologerId,
    required String astrologerName,
    double ratePerMinute = 15.0,
    String? sessionId,
    bool isVideo = false,
  }) =>
      pushNamed(AudioCallScreen.route, {
        'astrologerId': astrologerId,
        'astrologerName': astrologerName,
        'ratePerMinute': ratePerMinute,
        'sessionId': sessionId,
        'isVideo': isVideo,
      });

  Future<void> openPrashnaOracle() => pushNamed(PrashnaOracleScreen.route);

  Future<void> openPrasadamTracker({
    String? orderId,
    String? pujaName,
    String? temple,
    String? trackingCode,
    String? courierName,
    int currentStep = 3,
  }) =>
      pushNamed(PrasadamTrackerScreen.route, {
        'orderId': orderId,
        'pujaName': pujaName,
        'temple': temple,
        'trackingCode': trackingCode,
        'courierName': courierName,
        'currentStep': currentStep,
      });

  Future<void> openLiveDarshan() => pushNamed(LiveDarshanScreen.route);

  Future<void> openJapaMala() => pushNamed(JapaMalaScreen.route);

  Future<void> openSwapnaShastra() => pushNamed(SwapnaShastraScreen.route);

  Future<void> openAnnadaan() => pushNamed(AnnadaanScreen.route);

  Future<void> openChoghadiyaRadar() => pushNamed(ChoghadiyaRadarScreen.route);

  Future<void> openFamilyGotraVault() => pushNamed(FamilyGotraVaultScreen.route);

  Future<void> openSankalpVault() => pushNamed(SankalpVaultScreen.route);

  Future<void> openHistory() => pushNamed(HistoryScreen.route);

  Future<void> openKpCalendar() => pushNamed(KpCalendarScreen.route);
  Future<void> openNotifications() => pushNamed(NotificationsScreen.route);
  Future<void> openVedicAudio() => pushNamed(VedicAudioScreen.route);
  Future<void> openSpiritualShorts([int initialIndex = 0]) =>
      pushNamed(SpiritualShortsScreen.route, {'initialIndex': initialIndex});

  Future<void> pushNamed(String route, [Map<String, dynamic>? args]) =>
      Navigator.of(this).pushNamed(route, arguments: args);
}
