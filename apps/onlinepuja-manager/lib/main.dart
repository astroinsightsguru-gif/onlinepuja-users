import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'providers/call_provider.dart';
import 'providers/devotee_lead_provider.dart';
import 'providers/growth_ai_provider.dart';
import 'providers/language_provider.dart';
import 'providers/paperclip_provider.dart';
import 'providers/quotation_provider.dart';
import 'providers/website_radar_provider.dart';
import 'services/manager_api_service.dart';
import 'services/manager_auth_session.dart';
import 'theme/manager_theme.dart';
import 'ui/screens/auth/manager_login_screen.dart';
import 'ui/screens/auth/manager_pin_screen.dart';
import 'ui/screens/manager_shell.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const OnlinePujaManagerApp());
}

class OnlinePujaManagerApp extends StatelessWidget {
  const OnlinePujaManagerApp({super.key});

  @override
  Widget build(BuildContext context) {
    final apiService = ManagerApiService();

    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => ManagerAuthSession()),
        ChangeNotifierProvider(create: (_) => CallProvider(apiService: apiService)),
        ChangeNotifierProvider(create: (_) => DevoteeLeadProvider(apiService: apiService)),
        ChangeNotifierProvider(create: (_) => QuotationProvider(apiService: apiService)),
        ChangeNotifierProvider(create: (_) => GrowthAiProvider(apiService: apiService)),
        ChangeNotifierProvider(create: (_) => PaperclipProvider(apiService: apiService)),
        ChangeNotifierProvider(create: (_) => WebsiteRadarProvider(apiService: apiService)),
        ChangeNotifierProvider(create: (_) => LanguageProvider()),
      ],
      child: MaterialApp(
        title: 'OnlinePuja Manager',
        debugShowCheckedModeBanner: false,
        theme: ManagerTheme.darkTheme,
        home: Consumer<ManagerAuthSession>(
          builder: (context, session, _) {
            // 1. Storage Loading Splash
            if (!session.isInitialized) {
              return const Scaffold(
                backgroundColor: Color(0xFF0B0F19),
                body: Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        'ॐ',
                        style: TextStyle(
                          fontSize: 48,
                          fontWeight: FontWeight.w900,
                          color: ManagerTheme.brandGold,
                        ),
                      ),
                      SizedBox(height: 16),
                      SizedBox(
                        width: 24,
                        height: 24,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: ManagerTheme.brandGold,
                        ),
                      ),
                    ],
                  ),
                ),
              );
            }

            // 2. Unauthenticated -> Executive Login Gate
            if (!session.isAuthenticated) {
              return const ManagerLoginScreen();
            }

            // 3. Locked with Quick PIN -> PIN Unlock Screen
            if (session.isLocked && session.hasPin) {
              return const ManagerPinScreen();
            }

            // 4. Authenticated & Unlocked -> Executive Command Shell
            return const ManagerShell();
          },
        ),
      ),
    );
  }
}
