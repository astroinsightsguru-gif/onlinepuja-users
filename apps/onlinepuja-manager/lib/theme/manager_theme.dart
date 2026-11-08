import 'package:flutter/material.dart';

class ManagerTheme {
  ManagerTheme._();

  // Executive Brand Colors
  static const Color brandGold = Color(0xFFF59E0B);
  static const Color brandAmber = Color(0xFFD97706);
  static const Color brandDarkGold = Color(0xFF78350F);
  static const Color brandEmerald = Color(0xFF10B981);
  static const Color brandRuby = Color(0xFFEF4444);
  static const Color brandSapphire = Color(0xFF3B82F6);

  // Backgrounds & Surfaces
  static const Color bgDark = Color(0xFF0F172A);
  static const Color backgroundDark = Color(0xFF0F172A);
  static const Color surfaceDark = Color(0xFF1E293B);
  static const Color cardDark = Color(0xFF334155);
  static const Color surfaceBorder = Color(0xFF334155);

  static const LinearGradient executiveGold = LinearGradient(
    colors: [Color(0xFFF59E0B), Color(0xFFD97706)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient darkCardGradient = LinearGradient(
    colors: [Color(0xFF1E293B), Color(0xFF0F172A)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static ThemeData get darkTheme {
    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.dark,
      scaffoldBackgroundColor: bgDark,
      colorScheme: const ColorScheme.dark(
        primary: brandGold,
        secondary: brandAmber,
        surface: surfaceDark,
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: surfaceDark,
        elevation: 0,
        centerTitle: false,
        titleTextStyle: TextStyle(
          fontSize: 18,
          fontWeight: FontWeight.w800,
          color: Colors.white,
          letterSpacing: -0.3,
        ),
      ),
      cardTheme: CardThemeData(
        color: surfaceDark,
        elevation: 2,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      ),
    );
  }
}
