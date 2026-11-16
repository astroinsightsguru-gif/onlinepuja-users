import 'package:flutter/material.dart';
import 'package:op_shared/op_shared.dart';
import 'package:provider/provider.dart';

import 'state/app_session.dart';
import 'ui/screens/auth/login_screen.dart';
import 'ui/screens/auth/otp_screen.dart';
import 'ui/screens/auth/partner_register_screen.dart';
import 'ui/screens/call/call_session_screen.dart';
import 'ui/screens/chat/chat_session_screen.dart';
import 'ui/screens/home/availability_screen.dart';
import 'ui/screens/home/home_shell.dart';
import 'ui/screens/home/requests_screen.dart';
import 'ui/screens/orders/orders_fulfillment_screen.dart';
import 'ui/screens/profile/profile_screen.dart';
import 'ui/screens/social/astrologer_social_growth_screen.dart';
import 'ui/screens/splash_screen.dart';
import 'ui/screens/tools/vedic_toolkit_screen.dart';
import 'ui/screens/wallet/wallet_screen.dart';

class OnlinePujaPartnerApp extends StatelessWidget {
  const OnlinePujaPartnerApp({super.key});

  @override
  Widget build(BuildContext context) {
    final session = context.watch<PartnerSession>();
    return ValueListenableBuilder<AppLanguage>(
      valueListenable: LocaleManager.instance.currentLanguage,
      builder: (context, currentLanguage, _) {
        return MaterialApp(
          title: 'OnlinePuja.astro',
          debugShowCheckedModeBanner: false,
          theme: AppTheme.light(),
          darkTheme: AppTheme.dark(),
          themeMode: session.themeMode,
          initialRoute: SplashScreen.route,
      routes: {
        SplashScreen.route: (_) => const SplashScreen(),
        LoginScreen.route: (_) => const LoginScreen(),
        OtpScreen.route: (_) => const OtpScreen(),
        PartnerRegisterScreen.route: (_) => const PartnerRegisterScreen(),
        HomeShell.route: (_) => const HomeShell(),
        AvailabilityScreen.route: (_) => const AvailabilityScreen(),
        RequestsScreen.route: (_) => const RequestsScreen(kind: 'all'),
        OrdersFulfillmentScreen.route: (_) => const OrdersFulfillmentScreen(),
        ProfileScreen.route: (_) => const ProfileScreen(),
        WalletScreen.route: (_) => const WalletScreen(),
        AstrologerSocialGrowthScreen.route: (_) => const AstrologerSocialGrowthScreen(),
        VedicToolkitScreen.route: (_) => const VedicToolkitScreen(),
      },
      onGenerateRoute: (settings) {
        final args = settings.arguments;
        Map<String, dynamic> map() =>
            args is Map<String, dynamic> ? args : const {};
        switch (settings.name) {
          case ChatSessionScreen.route:
            return MaterialPageRoute(
              builder: (_) => ChatSessionScreen(
                customerId: map()['customerId'] as int? ?? 0,
                customerName:
                    map()['customerName'] as String? ?? 'Customer',
                sessionId: map()['sessionId'] as String?,
              ),
            );
          case CallSessionScreen.route:
            return MaterialPageRoute(
              builder: (_) => CallSessionScreen(
                customerId: map()['customerId'] as int? ?? 0,
                customerName:
                    map()['customerName'] as String? ?? 'Customer',
                sessionId: map()['sessionId'] as String?,
                isVideo: map()['isVideo'] as bool? ?? false,
              ),
            );
        }
        return MaterialPageRoute(builder: (_) => const HomeShell());
      },
    );
      },
    );
  }
}
