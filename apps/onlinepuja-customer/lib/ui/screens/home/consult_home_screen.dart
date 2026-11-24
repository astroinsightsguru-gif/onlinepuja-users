import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:op_shared/op_shared.dart';
import 'package:provider/provider.dart';

import '../../../app.dart';
import '../../../state/app_session.dart';
import '../../theme/customer_theme.dart';

import '../cosmic_ai_screen.dart';
import '../history/history_screen.dart';
import '../horoscope/daily_horoscope_screen.dart';
import '../japa/japa_mala_screen.dart';
import '../kundli/kundli_list_screen.dart';
import '../kundli/kundli_matching_screen.dart';
import '../main_shell.dart';
import '../notifications_screen.dart';
import '../panchang/panchang_screen.dart';
import '../profile/wallet_screen.dart';
import '../puja/puja_detail_screen.dart';
import '../puja/sankalp_vault_screen.dart';
import '../kp/kp_calendar_screen.dart';
import '../darshan/live_darshan_screen.dart';
import '../charity/annadaan_screen.dart';
import '../blog/blog_screen.dart';
import '../explore/prashna_oracle_screen.dart';
import '../explore/swapna_shastra_screen.dart';
import '../profile/family_gotra_vault_screen.dart';
import '../shorts/spiritual_shorts_screen.dart';

/// Clean, Human-Centered Vedic Home Portal (Consult Tab):
/// - Clean header with OnlinePuja.live branding, drawer, smart AI button, wallet & language toggle
/// - Non-cluttered search bar with daily Muhurat status
/// - Curated promotional hero banner with seamless tab navigation
/// - 4x2 Curated Vedic Sanctum Grid (Kundli, Matching, Panchang, Horoscope, KP Calendar, AI, Japa, Sankalp)
/// - Unified Live Consultation section with ₹1 offer hook, online indicators, and direct Chat/Call buttons
/// - Popular Holy Teerth Pujas preview
class ConsultHomeScreen extends StatefulWidget {
  const ConsultHomeScreen({super.key});

  @override
  State<ConsultHomeScreen> createState() => _ConsultHomeScreenState();
}

class _ConsultHomeScreenState extends State<ConsultHomeScreen> {
  late Future<List<Astrologer>> _astroFuture;
  late Future<List<Puja>> _pujaFuture;
  Map<String, dynamic>? _firstConsultOffer;
  List<Map<String, dynamic>> _prescriptions = const [];
  List<Map<String, dynamic>> _remoteBanners = const [];
  List<Map<String, dynamic>> _shortsFeed = const [];
  int _bannerIndex = 0;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  void _loadData() {
    final session = context.read<AppSession>();
    _astroFuture = AstrologerApi.instance
        .list(userId: session.user?.id, sortBy: 'rating')
        .then((l) => l.where((a) => !a.isBlock).toList());
    _pujaFuture = PujaApi.instance
        .recommended(userId: session.user?.id)
        .catchError((_) => PujaApi.instance.list());
    _loadHomeData();
  }

  Future<void> _loadHomeData() async {
    final session = context.read<AppSession>();
    try {
      final promo = await MiscApi.instance.checkFirstConsultOffer(userId: session.user?.id);
      if (mounted) {
        setState(() {
          _firstConsultOffer = promo;
        });
      }
    } catch (_) {}
    try {
      final list = await AiTwinApi.instance.fetchPrescriptions(userId: session.user?.id);
      if (mounted && list.isNotEmpty) {
        setState(() {
          _prescriptions = list;
        });
      }
    } catch (_) {}
    try {
      final homeData = await MiscApi.instance.customerHome();
      final bannerList = homeData['banner'];
      if (mounted && bannerList is List && bannerList.isNotEmpty) {
        setState(() {
          _remoteBanners = bannerList.whereType<Map<String, dynamic>>().toList();
        });
      }
    } catch (_) {}
    try {
      final res = await http.get(Uri.parse('https://onlinepuja.live/api/feed/shorts')).timeout(const Duration(seconds: 6));
      if (res.statusCode == 200) {
        final decoded = json.decode(res.body);
        if (decoded['success'] == true && decoded['data'] is List) {
          final items = List<Map<String, dynamic>>.from(decoded['data']);
          if (mounted && items.isNotEmpty) {
            setState(() {
              _shortsFeed = items;
            });
          }
        }
      }
    } catch (_) {}
  }

  void _goToTab(int tabIndex) {
    MainShellScope.of(context)?.selectTab(tabIndex);
  }

  @override
  Widget build(BuildContext context) {
    final session = context.watch<AppSession>();
    final scheme = Theme.of(context).colorScheme;
    final currency = session.flags.currency.isNotEmpty ? session.flags.currency : '₹';
    final wallet = session.user?.walletAmount ?? 0.0;
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return ValueListenableBuilder<AppLanguage>(
      valueListenable: LocaleManager.instance.currentLanguage,
      builder: (context, lang, _) {
        final isHi = lang != AppLanguage.en;
        return Scaffold(
          drawer: _drawer(context, session, scheme, isHi),
          appBar: AppBar(
            titleSpacing: 0,
            title: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  padding: const EdgeInsets.all(5),
                  decoration: const BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: CustomerTheme.goldGradient,
                  ),
                  child: const Text('ॐ',
                      style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w900,
                          color: Color(0xFF78350F))),
                ),
                const SizedBox(width: 6),
                Flexible(
                  child: RichText(
                    overflow: TextOverflow.ellipsis,
                    text: TextSpan(
                      children: [
                        TextSpan(
                          text: 'OnlinePuja',
                          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                fontWeight: FontWeight.w900,
                                letterSpacing: -0.3,
                              ),
                        ),
                        const TextSpan(
                          text: '.live',
                          style: TextStyle(
                            fontWeight: FontWeight.w900,
                            fontSize: 16,
                            color: Color(0xFFD97706),
                            letterSpacing: -0.3,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
            actions: [
              // 1-Tap Hindi / English Switch Pill
              GestureDetector(
                onTap: () => LocaleManager.instance.setLanguage(
                  isHi ? AppLanguage.en : AppLanguage.hi,
                ),
                onLongPress: () => _showLanguageSelector(context),
                child: Container(
                  margin: const EdgeInsets.symmetric(vertical: 12, horizontal: 3),
                  padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
                  decoration: BoxDecoration(
                    color: isHi
                        ? const Color(0xFFD97706).withValues(alpha: 0.15)
                        : (isDark ? Colors.white10 : const Color(0xFFF1F5F9)),
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(
                      color: isHi ? const Color(0xFFD97706) : Colors.transparent,
                    ),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.translate_rounded, size: 12, color: Color(0xFFD97706)),
                      const SizedBox(width: 3),
                      Text(
                        isHi ? 'हिंदी' : 'EN',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w900,
                          color: isHi
                              ? const Color(0xFFD97706)
                              : (isDark ? Colors.white70 : const Color(0xFF334155)),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              // Smart AI Copilot Button
              IconButton(
                tooltip: isHi ? 'AI ज्योतिषी' : 'OnlinePuja AI',
                icon: Container(
                  padding: const EdgeInsets.all(6),
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      colors: [Color(0xFFFEF3C7), Color(0xFFFDE68A)],
                    ),
                    shape: BoxShape.circle,
                    border: Border.all(color: const Color(0xFFF59E0B)),
                  ),
                  child: const Icon(Icons.auto_awesome_rounded, size: 16, color: Color(0xFFD97706)),
                ),
                onPressed: () => Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => const OnlinePujaAiScreen()),
                ),
              ),
              // Clean Wallet pill
              GestureDetector(
                onTap: () => Navigator.of(context)
                    .pushNamed(WalletScreen.route)
                    .then((_) => session.refreshUser()),
                child: Container(
                  margin: const EdgeInsets.symmetric(vertical: 12, horizontal: 4),
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: const Color(0xFF1B8A5A),
                    borderRadius: BorderRadius.circular(16),
                    boxShadow: [
                      BoxShadow(
                        color: const Color(0xFF1B8A5A).withValues(alpha: 0.25),
                        blurRadius: 4,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Text('🪙', style: TextStyle(fontSize: 11)),
                      const SizedBox(width: 4),
                      Text(
                        '$currency${wallet.toStringAsFixed(0)}',
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.w800,
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              // Notifications Bell
              IconButton(
                tooltip: isHi ? 'सूचनाएं' : 'Notifications',
                icon: Icon(
                  Icons.notifications_none_rounded,
                  size: 22,
                  color: isDark ? Colors.white70 : const Color(0xFF475569),
                ),
                onPressed: () => Navigator.of(context).pushNamed(NotificationsScreen.route),
              ),
              const SizedBox(width: 4),
            ],
          ),
          body: RefreshIndicator(
            onRefresh: () async => setState(() => _loadData()),
            child: SingleChildScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _searchBar(context, scheme, isDark),
                  _heroBannerCarousel(context, scheme, isHi),
                  _varanasiFestiveMahotsavBar(context, isDark, isHi),
                  _spiritualShortsStrip(context, isDark, isHi),
                  _curatedVedicGrid(context, scheme, isDark),
                  if (_prescriptions.isNotEmpty)
                    _acharyaPrescribedRemediesSection(context, currency, isDark, isHi),
                  _liveAstrologerConsultationSection(context, scheme, currency, isDark, isHi),
                  _trendingPujasSection(context, scheme, currency, isHi),
                  const SizedBox(height: 48),
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  /// High-Converting Varanasi Festive & Dev Deepawali Quick-Access Bar
  Widget _varanasiFestiveMahotsavBar(BuildContext context, bool isDark, bool isHi) {
    final festivePills = [
      (
        label: isHi ? '🪔 दीवाली महालक्ष्मी पूजन' : '🪔 Diwali Lakshmi Puja',
        tag: isHi ? 'विशेष पर्व' : 'Most Popular',
        color: const Color(0xFFB45309),
        bg: const Color(0xFFFEF3C7),
        onTap: () => _goToTab(2),
      ),
      (
        label: isHi ? '🌊 काशी १०८ दीपदान' : '🌊 Kashi 108 Deepdaan',
        tag: isHi ? 'देव दीपावली' : 'Dev Deepawali',
        color: const Color(0xFF1D4ED8),
        bg: const Color(0xFFDBEAFE),
        onTap: () => _goToTab(2),
      ),
      (
        label: isHi ? '🌕 करवा चौथ अखंड सुहाग' : '🌕 Karwa Chauth Vrat',
        tag: isHi ? 'व्रत कथा' : 'Vrat Katha',
        color: const Color(0xFFBE185D),
        bg: const Color(0xFFFCE7F3),
        onTap: () => _goToTab(2),
      ),
      (
        label: isHi ? '🌾 काशी गो-सेवा एवं अन्नदान' : '🌾 Kashi Gau Seva (₹51)',
        tag: isHi ? 'पुण्य लाभ' : 'Seva Offering',
        color: const Color(0xFF047857),
        bg: const Color(0xFFD1FAE5),
        onTap: () => Navigator.of(context).push(
          MaterialPageRoute(builder: (_) => const AnnadaanScreen()),
        ),
      ),
    ];

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 6, 16, 10),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: isDark
                ? [const Color(0xFF3B1E08), const Color(0xFF200F05)]
                : [const Color(0xFFFFFBEB), const Color(0xFFFEF3C7)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: const Color(0xFFD97706).withValues(alpha: 0.45),
            width: 1.2,
          ),
          boxShadow: [
            BoxShadow(
              color: const Color(0xFFD97706).withValues(alpha: isDark ? 0.2 : 0.08),
              blurRadius: 6,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Text('🪔', style: TextStyle(fontSize: 16)),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    isHi ? 'काशी दीपोत्सव एवं पर्व महाकुंभ 2026' : 'Kashi Festive Mahotsav 2026',
                    style: TextStyle(
                      fontSize: 13.5,
                      fontWeight: FontWeight.w800,
                      color: isDark ? const Color(0xFFFCD34D) : const Color(0xFF92400E),
                    ),
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  decoration: BoxDecoration(
                    color: const Color(0xFFD97706),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Text(
                    isHi ? 'सीमित तिथियां' : 'BOOK EARLY',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 9.5,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 0.5,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              physics: const BouncingScrollPhysics(),
              child: Row(
                children: festivePills.map((pill) {
                  return GestureDetector(
                    onTap: pill.onTap,
                    child: Container(
                      margin: const EdgeInsets.only(right: 8),
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
                      decoration: BoxDecoration(
                        color: isDark ? const Color(0xFF1E1E2E) : Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: pill.color.withValues(alpha: 0.35),
                          width: 1,
                        ),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.04),
                            blurRadius: 3,
                            offset: const Offset(0, 1),
                          ),
                        ],
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            pill.label,
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w700,
                              color: isDark ? Colors.white : const Color(0xFF1F2937),
                            ),
                          ),
                          const SizedBox(width: 6),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                            decoration: BoxDecoration(
                              color: pill.bg,
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Text(
                              pill.tag,
                              style: TextStyle(
                                fontSize: 9.5,
                                fontWeight: FontWeight.w800,
                                color: pill.color,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                }).toList(),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _acharyaPrescribedRemediesSection(
    BuildContext context,
    String currency,
    bool isDark,
    bool isHi,
  ) {
    final latest = _prescriptions.first;
    final astroName = latest['astrologer_name']?.toString() ??
        (isHi ? 'आचार्य वेद व्यास' : 'Acharya Ved Vyas');
    final pujaTitle = latest['puja_title']?.toString() ??
        latest['remedy_title']?.toString() ??
        (isHi
            ? 'महामृत्युंजय एवं नवग्रह शांति पूजा'
            : 'Maha Mrityunjaya & Navgrah Shanti Puja');
    final pujaPlace = latest['puja_place']?.toString() ??
        (isHi ? 'श्री काशी विश्वनाथ धाम' : 'Shri Kashi Vishwanath Dham');
    final pujaPrice = latest['puja_price']?.toString() ?? '1100';
    final reason = latest['reason']?.toString() ??
        (isHi ? 'ग्रह दोष निवारण एवं शांति हेतु' : 'Planetary Dosha Nivaran & Graha Shanti');
    final mantra = latest['mantra']?.toString() ?? '';
    final gemstone = latest['gemstone']?.toString() ?? '';
    final pujaIdRaw = latest['puja_id'];
    final int? pujaId = pujaIdRaw is int ? pujaIdRaw : int.tryParse(pujaIdRaw?.toString() ?? '');

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 4, 16, 12),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: isDark
                ? [const Color(0xFF2E1065), const Color(0xFF1E1B4B)]
                : [const Color(0xFFFFFBEB), const Color(0xFFFEF3C7)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          borderRadius: BorderRadius.circular(18),
          border: Border.all(
            color: const Color(0xFFD97706).withValues(alpha: 0.55),
            width: 1.3,
          ),
          boxShadow: [
            BoxShadow(
              color: const Color(0xFFD97706).withValues(alpha: isDark ? 0.2 : 0.1),
              blurRadius: 10,
              offset: const Offset(0, 3),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(6),
                  decoration: const BoxDecoration(
                    gradient: CustomerTheme.saffronGradient,
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.temple_hindu_rounded, color: Colors.white, size: 15),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        isHi ? 'आचार्य द्वारा निर्देशित वैदिक उपाय' : 'ACHARYA PRESCRIBED VEDIC REMEDY',
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.w900,
                          letterSpacing: 0.6,
                          color: isDark ? const Color(0xFFFDE68A) : const Color(0xFFB45309),
                        ),
                      ),
                      Text(
                        isHi ? '$astroName द्वारा निर्देशित' : 'Prescribed by $astroName',
                        style: TextStyle(
                          fontSize: 11.5,
                          fontWeight: FontWeight.w600,
                          color: isDark ? Colors.white70 : const Color(0xFF78350F),
                        ),
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: const Color(0xFF10B981),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Text(
                    '$currency$pujaPrice',
                    style: const TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w900,
                      color: Colors.white,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            Text(
              pujaTitle,
              style: TextStyle(
                fontSize: 14.5,
                fontWeight: FontWeight.w900,
                color: isDark ? Colors.white : const Color(0xFF1E293B),
              ),
            ),
            const SizedBox(height: 3),
            Text(
              '📍 $pujaPlace • $reason',
              style: TextStyle(
                fontSize: 11.5,
                color: isDark ? Colors.white70 : const Color(0xFF475569),
              ),
            ),
            if (mantra.isNotEmpty || gemstone.isNotEmpty) ...[
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                decoration: BoxDecoration(
                  color: isDark ? Colors.black26 : Colors.white.withValues(alpha: 0.7),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (mantra.isNotEmpty)
                      Text(
                        '📿 ${isHi ? 'मंत्र' : 'Mantra'}: $mantra',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                          color: isDark ? const Color(0xFFFDE68A) : const Color(0xFF92400E),
                        ),
                      ),
                    if (gemstone.isNotEmpty)
                      Text(
                        '💎 ${isHi ? 'रत्न / रुद्राक्ष' : 'Ratna / Rudraksha'}: $gemstone',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                          color: isDark ? Colors.white70 : const Color(0xFF334155),
                        ),
                      ),
                  ],
                ),
              ),
            ],
            const SizedBox(height: 10),
            SizedBox(
              width: double.infinity,
              child: FilledButton.icon(
                style: FilledButton.styleFrom(
                  backgroundColor: const Color(0xFFD97706),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 10),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                onPressed: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => PujaDetailScreen(
                        puja: Puja(
                          id: pujaId,
                          title: pujaTitle,
                          place: pujaPlace,
                        ),
                      ),
                    ),
                  );
                },
                icon: const Icon(Icons.local_fire_department_rounded, size: 16),
                label: Text(
                  isHi ? 'निर्देशित संकल्प पूजा अभी बुक करें' : 'Book Prescribed Sankalp Puja Now',
                  style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w800),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showLanguageSelector(BuildContext context) {
    LanguagePickerSheet.show(context);
  }

  Widget _searchBar(BuildContext context, ColorScheme scheme, bool isDark) {
    final searchBg = isDark ? CustomerTheme.cosmicCardElevated : Colors.white;
    final searchBorder = isDark ? CustomerTheme.cosmicBorderDark : const Color(0xFFE2E8F0);

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 10, 16, 8),
      child: Column(
        children: [
          GestureDetector(
            onTap: () => _goToTab(1), // Jump to Consult tab (index 1)
            child: Container(
              height: 48,
              padding: const EdgeInsets.symmetric(horizontal: 14),
              decoration: BoxDecoration(
                color: searchBg,
                borderRadius: BorderRadius.circular(24),
                border: Border.all(color: searchBorder),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: isDark ? 0.2 : 0.04),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Row(
                children: [
                  const Icon(Icons.search_rounded, color: Color(0xFFD97706), size: 22),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      AppStrings.searchPlaceholder,
                      style: TextStyle(
                        color: scheme.onSurfaceVariant.withValues(alpha: 0.85),
                        fontSize: 13.5,
                        height: 1.25,
                      ),
                    ),
                  ),
                  GestureDetector(
                    onTap: () => Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => const OnlinePujaAiScreen()),
                    ),
                    child: Container(
                      padding: const EdgeInsets.all(6),
                      decoration: BoxDecoration(
                        color: const Color(0xFFD97706).withValues(alpha: 0.12),
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.mic_none_rounded, color: Color(0xFFD97706), size: 18),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 6),
          // Auspicious Muhurat status pill
          Align(
            alignment: Alignment.centerLeft,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: const Color(0xFFFEF3C7),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFFDE68A)),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Text("🕉️ ", style: TextStyle(fontSize: 10.5)),
                  Flexible(
                    child: Text(
                      AppStrings.shubhMuhuratActive,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 10.5,
                        fontWeight: FontWeight.w800,
                        color: Color(0xFF92400E),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _heroBannerCarousel(BuildContext context, ColorScheme scheme, bool isHi) {
    final curatedBanners = [
      (
        title: isHi
            ? '🪔 काशी देव दीपावली एवं दीपोत्सव 2026'
            : '🪔 Kashi Dev Deepawali & Diwali 2026',
        subtitle: isHi
            ? 'गंगा घाट पर 108 दीपदान, महालक्ष्मी पूजन एवं विशेष गो-सेवा'
            : '108 Deepdaan on Ganga Ghats & Maha Lakshmi Havan',
        cta: isHi ? 'पर्व संकल्प बुक करें ✨' : 'Book Festive Sankalp ✨',
        icon: Icons.brightness_high_rounded,
        image: 'https://onlinepuja.live/public/storage/puja_images/puja_1764232697222466223.webp',
        gradient: const [Color(0xFF78350F), Color(0xFFD97706)],
        onTap: () => _goToTab(2), // Puja tab (index 2)
      ),
      (
        title: isHi ? 'प्रामाणिक ऑनलाइन पूजा एवं हवन' : 'Authentic Online Puja & Hawan',
        subtitle: isHi
            ? 'काशी व उज्जैन से लाइव संकल्प, वीडियो प्रमाण और प्रसाद'
            : 'Live Sankalp & Consecrated Prasad from Holy Dhams',
        cta: isHi ? 'पूजा बुक करें 🙏' : 'Book Puja 🙏',
        icon: Icons.local_fire_department_rounded,
        image: 'https://onlinepuja.live/public/storage/puja_images/puja_17642331041781434446.png',
        gradient: const [Color(0xFF8B0000), Color(0xFFE53935)],
        onTap: () => _goToTab(2), // Puja tab (index 2)
      ),
      (
        title: isHi ? 'वैदिक आचार्यों से सटीक परामर्श' : 'Accurate Predictions by Vedic Astrologers',
        subtitle: isHi
            ? 'प्रथम सत्र ₹1/मिनट · कुंडली, विवाह, करियर व दोष निवारण'
            : 'First session ₹1/min · Kundli, Marriage & Career Guidance',
        cta: isHi ? 'ज्योतिषी से बात करें' : 'Consult Astrologer',
        icon: Icons.self_improvement_rounded,
        image: 'https://onlinepuja.live/public/storage/puja_images/puja_17642330451605364386.jpg',
        gradient: const [Color(0xFF8E2B12), Color(0xFFD97706)],
        onTap: () => _goToTab(1), // Consult tab (index 1)
      ),
      (
        title: isHi ? '24/7 लाइव मंदिर दर्शन एवं आरती' : '24/7 Live Sanctum Darshan & Aarti',
        subtitle: isHi
            ? 'काशी विश्वनाथ, महाकालेश्वर एवं गंगा आरती लाइव'
            : 'Kashi Vishwanath, Mahakaleshwar & Ganga Aarti Feeds',
        cta: isHi ? 'लाइव दर्शन 🪔' : 'Watch Live 🪔',
        icon: Icons.temple_hindu_rounded,
        image: 'https://onlinepuja.live/public/storage/brain_media/brain_NBw7PwNZxf2gqejbRd_940x627.jpg',
        gradient: const [Color(0xFF4A148C), Color(0xFF7B1FA2)],
        onTap: () => Navigator.of(context).pushNamed(LiveDarshanScreen.route),
      ),
      (
        title: isHi ? 'वैदिक भण्डार एवं सिद्ध रुद्राक्ष' : 'Sacred Store & Energized Rudraksha',
        subtitle: isHi
            ? '100% प्रमाणित प्राण-प्रतिष्ठित रुद्राक्ष, रत्न एवं माला'
            : '100% Consecrated Rudraksha, Gemstones & Sphatik Malas',
        cta: isHi ? 'भण्डार देखें 📿' : 'Explore Store 📿',
        icon: Icons.storefront_rounded,
        image: 'https://onlinepuja.live/public/storage/puja_images/puja_1764232697222466223.webp',
        gradient: const [Color(0xFF1B5E20), Color(0xFF2E7D32)],
        onTap: () => _goToTab(3), // Vedic Store tab (index 3)
      ),
    ];

    final totalCount = _remoteBanners.length + curatedBanners.length;

    return Column(
      children: [
        SizedBox(
          height: 162,
          child: PageView.builder(
            itemCount: totalCount,
            onPageChanged: (i) => setState(() => _bannerIndex = i),
            itemBuilder: (context, i) {
              if (i < _remoteBanners.length) {
                final rb = _remoteBanners[i];
                final imageUrl = rb['bannerImage']?.toString() ?? '';
                if (imageUrl.isNotEmpty) {
                  return GestureDetector(
                    onTap: () => _goToTab(2), // Navigate to Puja tab
                    child: Container(
                      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(18),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.18),
                            blurRadius: 10,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(18),
                        child: Image.network(
                          imageUrl,
                          fit: BoxFit.cover,
                          width: double.infinity,
                          height: double.infinity,
                          errorBuilder: (_, error, stack) => _buildCuratedBannerCard(curatedBanners[0]),
                        ),
                      ),
                    ),
                  );
                }
              }

              final b = curatedBanners[i - _remoteBanners.length];
              return _buildCuratedBannerCard(b);
            },
          ),
        ),
        const SizedBox(height: 6),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            for (int i = 0; i < totalCount; i++)
              AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                margin: const EdgeInsets.symmetric(horizontal: 3),
                width: _bannerIndex == i ? 18 : 6,
                height: 6,
                decoration: BoxDecoration(
                  color: _bannerIndex == i
                      ? CustomerTheme.brandSaffron
                      : scheme.outline.withValues(alpha: 0.3),
                  borderRadius: BorderRadius.circular(3),
                ),
              ),
          ],
        ),
      ],
    );
  }

  Widget _buildCuratedBannerCard(dynamic b) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(18),
        gradient: LinearGradient(
          colors: b.gradient,
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        boxShadow: [
          BoxShadow(
            color: (b.gradient.first as Color).withValues(alpha: 0.3),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    b.title,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 15,
                      fontWeight: FontWeight.w800,
                      height: 1.2,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    b.subtitle,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: Colors.white70,
                      fontSize: 11,
                      height: 1.2,
                    ),
                  ),
                  const SizedBox(height: 8),
                  ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.white,
                      foregroundColor: b.gradient.first,
                      elevation: 2,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(18),
                      ),
                      padding: const EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 4,
                      ),
                      visualDensity: VisualDensity.compact,
                    ),
                    onPressed: b.onTap,
                    child: Text(
                      b.cta,
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 11.5,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            Container(
              width: 76,
              height: 76,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Colors.white.withValues(alpha: 0.35), width: 1.5),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.25),
                    blurRadius: 8,
                    offset: const Offset(0, 3),
                  ),
                ],
              ),
              clipBehavior: Clip.antiAlias,
              child: Stack(
                fit: StackFit.expand,
                children: [
                  CachedNetworkImage(
                    imageUrl: b.image,
                    fit: BoxFit.cover,
                    placeholder: (_, __) => Container(color: Colors.white.withValues(alpha: 0.15)),
                    errorWidget: (_, error, stack) => Container(
                      color: Colors.white.withValues(alpha: 0.2),
                      child: Center(
                        child: Icon(b.icon, size: 36, color: Colors.white),
                      ),
                    ),
                  ),
                  Container(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [Colors.transparent, Colors.black.withValues(alpha: 0.35)],
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _spiritualShortsStrip(BuildContext context, bool isDark, bool isHi) {
    final defaultShorts = [
      {
        'title': isHi ? 'श्री काशी विश्वनाथ महा आरती' : 'Shri Kashi Vishwanath Maha Aarti',
        'subtitle': isHi ? 'विश्वनाथ ज्योतिर्लिंग, काशी' : 'Vishwanath Jyotirlinga, Kashi',
        'icon': '🔱',
        'badge': '🔴 LIVE AARTI',
        'image': 'https://onlinepuja.live/public/storage/puja_images/puja_17642331041781434446.png',
        'index': 0,
      },
      {
        'title': isHi ? 'दशाश्वमेध घाट महा गंगा आरती' : 'Dashashwamedh Maha Ganga Aarti',
        'subtitle': isHi ? 'दशाश्वमेध घाट, वाराणसी' : 'Dashashwamedh Ghat, Varanasi',
        'icon': '🌊',
        'badge': '🔴 LIVE AARTI',
        'image': 'https://onlinepuja.live/public/storage/brain_media/brain_NBw7PwNZxf2gqejbRd_940x627.jpg',
        'index': 1,
      },
      {
        'title': isHi ? 'श्री लक्ष्मी-कुबेर महा धन लाभ पूजा' : 'Shree Lakshmi-Kubera Maha Puja',
        'subtitle': isHi ? 'कनकधारा हवन एवं धन संचय' : 'Wealth & Prosperity Havan',
        'icon': '🪔',
        'badge': '₹501 संकल्प',
        'image': 'https://onlinepuja.live/public/storage/puja_images/puja_1764232697222466223.webp',
        'index': 2,
      },
      {
        'title': isHi ? 'महामृत्युंजय आयुष्य हवन' : 'Maha Mrityunjaya Ayushya Havan',
        'subtitle': isHi ? 'संजीवनी महाजाप एवं आरोग्य' : 'Sanjeevani Jaap & Health',
        'icon': '🔱',
        'badge': '₹1,501 संकल्प',
        'image': 'https://onlinepuja.live/public/storage/puja_images/puja_17642330451605364386.jpg',
        'index': 3,
      },
    ];

    final rawList = _shortsFeed.isNotEmpty ? _shortsFeed : defaultShorts;
    final shortsList = rawList.asMap().entries.map((entry) {
      final i = entry.key;
      final s = entry.value;
      final img = (s['image'] ?? s['thumbnail_url'] ?? '').toString();
      return {
        'title': (s['title'] ?? '').toString(),
        'subtitle': (s['subtitle'] ?? s['category'] ?? '').toString(),
        'icon': (s['icon'] ?? '🪔').toString(),
        'badge': (s['badge'] ?? 'VEDIC REEL').toString(),
        'image': img.isNotEmpty ? img : 'https://onlinepuja.live/public/storage/puja_images/puja_1764232697222466223.webp',
        'index': (s['index'] is int) ? (s['index'] as int) : i,
      };
    }).toList();

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Row(
                    children: [
                      Container(
                        width: 3.5,
                        height: 15,
                        decoration: BoxDecoration(
                          color: CustomerTheme.brandGold,
                          borderRadius: BorderRadius.circular(2),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Flexible(
                        child: Text(
                          isHi ? '🎬 वैदिक रील्स व शॉट्स' : '🎬 Daily Spiritual Shorts',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w800,
                            color: isDark ? Colors.white : const Color(0xFF1E293B),
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2.5),
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(
                            colors: [Color(0xFFFEF3C7), Color(0xFFFDE68A)],
                          ),
                          borderRadius: BorderRadius.circular(6),
                          border: Border.all(color: const Color(0xFFF59E0B)),
                        ),
                        child: Text(
                          isHi ? '✨ वैदिक रील्स' : '✨ VEDIC SHORTS',
                          style: const TextStyle(
                            color: Color(0xFF92400E),
                            fontSize: 9,
                            fontWeight: FontWeight.w900,
                            letterSpacing: 0.2,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                TextButton(
                  onPressed: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => const SpiritualShortsScreen()),
                    );
                  },
                  child: Text(
                    isHi ? 'सभी देखें →' : 'Watch All →',
                    style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: CustomerTheme.brandGold),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 6),
          SizedBox(
            height: 168,
            child: ListView.separated(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              scrollDirection: Axis.horizontal,
              itemCount: shortsList.length,
              separatorBuilder: (context, index) => const SizedBox(width: 12),
              itemBuilder: (context, i) {
                final s = shortsList[i];
                final isLive = s['badge'].toString().toUpperCase().contains('LIVE');
                return GestureDetector(
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => SpiritualShortsScreen(initialIndex: s['index'] as int)),
                    );
                  },
                  child: Container(
                    width: 125,
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(16),
                      color: const Color(0xFF0F172A),
                      border: Border.all(
                        color: isLive
                            ? const Color(0xFFEF4444)
                            : (i == 0 ? CustomerTheme.brandGold : Colors.white24),
                        width: isLive || i == 0 ? 1.5 : 0.8,
                      ),
                      boxShadow: [
                        BoxShadow(
                          color: (isLive ? const Color(0xFFEF4444) : CustomerTheme.brandGold)
                              .withValues(alpha: 0.15),
                          blurRadius: 8,
                          offset: const Offset(0, 3),
                        ),
                      ],
                    ),
                    clipBehavior: Clip.antiAlias,
                    child: Stack(
                      fit: StackFit.expand,
                      children: [
                        CachedNetworkImage(
                          imageUrl: s['image'] as String,
                          fit: BoxFit.cover,
                          placeholder: (context, url) => Container(color: const Color(0xFF1E293B)),
                          errorWidget: (context, url, error) => Container(
                            color: const Color(0xFF1E293B),
                            child: Center(
                              child: Text(s['icon'] as String, style: const TextStyle(fontSize: 28)),
                            ),
                          ),
                        ),
                        Container(
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              colors: [
                                Colors.transparent,
                                Colors.black.withValues(alpha: 0.3),
                                Colors.black.withValues(alpha: 0.92),
                              ],
                              stops: const [0.0, 0.45, 1.0],
                              begin: Alignment.topCenter,
                              end: Alignment.bottomCenter,
                            ),
                          ),
                        ),
                        Positioned(
                          top: 8,
                          left: 8,
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2.5),
                            decoration: BoxDecoration(
                              color: isLive ? const Color(0xFFDC2626) : Colors.black87,
                              borderRadius: BorderRadius.circular(6),
                              border: Border.all(
                                color: isLive ? Colors.white70 : CustomerTheme.brandGold.withValues(alpha: 0.5),
                                width: 0.8,
                              ),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                if (isLive) ...[
                                  Container(
                                    width: 5,
                                    height: 5,
                                    decoration: const BoxDecoration(
                                      color: Colors.white,
                                      shape: BoxShape.circle,
                                    ),
                                  ),
                                  const SizedBox(width: 4),
                                ],
                                Text(
                                  s['badge'] as String,
                                  style: TextStyle(
                                    color: isLive ? Colors.white : CustomerTheme.brandGold,
                                    fontSize: 8.5,
                                    fontWeight: FontWeight.w900,
                                    letterSpacing: 0.2,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                        Positioned(
                          top: 8,
                          right: 8,
                          child: Container(
                            padding: const EdgeInsets.all(4),
                            decoration: BoxDecoration(
                              color: Colors.black.withValues(alpha: 0.45),
                              shape: BoxShape.circle,
                            ),
                            child: const Icon(Icons.play_arrow_rounded, color: Colors.white, size: 18),
                          ),
                        ),
                        Positioned(
                          left: 8,
                          right: 8,
                          bottom: 8,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Text(
                                s['title'] as String,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 11,
                                  fontWeight: FontWeight.w800,
                                  height: 1.25,
                                ),
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                              ),
                              const SizedBox(height: 2),
                              Text(
                                s['subtitle'] as String,
                                style: const TextStyle(color: Colors.white70, fontSize: 9.5),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  /// Clean, 4x3 Curated Vedic Sanctum Grid reconnecting authentic spiritual tools
  Widget _curatedVedicGrid(BuildContext context, ColorScheme scheme, bool isDark) {
    final isHi = LocaleManager.instance.currentLanguage.value != AppLanguage.en;
    final tools = [
      (AppStrings.freeKundli, Icons.auto_graph_rounded, const Color(0xFFFFEBEE), const Color(0xFFC62828), const KundliListScreen()),
      (AppStrings.kundliMatching, Icons.favorite_rounded, const Color(0xFFFCE4EC), const Color(0xFFAD1457), const KundliMatchingScreen()),
      (AppStrings.panchang, Icons.wb_twilight_rounded, const Color(0xFFFFF8E1), const Color(0xFFF57F17), const PanchangScreen()),
      (AppStrings.horoscope, Icons.nightlight_round, const Color(0xFFEDE7F6), const Color(0xFF512DA8), const DailyHoroscopeScreen()),
      (AppStrings.kpCalendar, Icons.shield_moon_rounded, const Color(0xFFE8EAF6), const Color(0xFF283593), const KpCalendarScreen()),
      (AppStrings.cosmicAi, Icons.auto_awesome_rounded, const Color(0xFFFEF3C7), const Color(0xFFD97706), const CosmicAiScreen()),
      (AppStrings.japaMala, Icons.circle_outlined, const Color(0xFFF3E5F5), const Color(0xFF6A1B9A), const JapaMalaScreen()),
      (AppStrings.mySankalp, Icons.savings_rounded, const Color(0xFFE0F2F1), const Color(0xFF00695C), const SankalpVaultScreen()),
      (AppStrings.annadaan, Icons.volunteer_activism_rounded, const Color(0xFFFBE9E7), const Color(0xFFD84315), const AnnadaanScreen()),
      (AppStrings.prashnaKundli, Icons.help_outline_rounded, const Color(0xFFE0F7FA), const Color(0xFF00838F), const PrashnaOracleScreen()),
      (isHi ? 'स्वप्न विचार' : 'Swapna Shastra', Icons.bedtime_rounded, const Color(0xFFEDE7F6), const Color(0xFF4527A0), const SwapnaShastraScreen()),
      (isHi ? 'वैदिक लेख' : 'Vedic Blogs', Icons.menu_book_rounded, const Color(0xFFF1F8E9), const Color(0xFF558B2F), const BlogScreen()),
    ];

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 6),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 3.5,
                height: 15,
                decoration: BoxDecoration(
                  color: CustomerTheme.brandSaffron,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              Expanded(
                child: Text(
                  AppStrings.vedicSanctumTitle,
                  style: Theme.of(context).textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.w800,
                        letterSpacing: -0.2,
                      ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: tools.length,
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 4,
              childAspectRatio: 0.86,
              crossAxisSpacing: 8,
              mainAxisSpacing: 10,
            ),
            itemBuilder: (context, i) {
              final t = tools[i];
              return InkWell(
                onTap: () => Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => t.$5),
                ),
                borderRadius: BorderRadius.circular(16),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Container(
                      width: 52,
                      height: 52,
                      decoration: BoxDecoration(
                        color: isDark ? t.$4.withValues(alpha: 0.18) : t.$3,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(
                          color: isDark ? t.$4.withValues(alpha: 0.4) : t.$4.withValues(alpha: 0.28),
                          width: 1.2,
                        ),
                        boxShadow: [
                          BoxShadow(
                            color: t.$4.withValues(alpha: 0.12),
                            blurRadius: 8,
                            offset: const Offset(0, 3),
                          ),
                        ],
                      ),
                      child: Icon(t.$2, color: t.$4, size: 26),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      t.$1,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: isDark ? Colors.white : const Color(0xFF1E293B),
                        letterSpacing: -0.2,
                      ),
                    ),
                  ],
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  /// Unified Live Consultation section with ₹1 promo hook, online indicators, and direct Chat/Call buttons
  Widget _liveAstrologerConsultationSection(
    BuildContext context,
    ColorScheme scheme,
    String currency,
    bool isDark,
    bool isHi,
  ) {
    return FutureBuilder<List<Astrologer>>(
      future: _astroFuture,
      builder: (context, snap) {
        final list = snap.data ?? [];
        if (list.isEmpty) return const SizedBox.shrink();

        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 10),
              child: Row(
                children: [
                  Container(
                    width: 3.5,
                    height: 15,
                    decoration: BoxDecoration(
                      color: const Color(0xFFEA580C),
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                  const SizedBox(width: 7),
                  Flexible(
                    child: Text(
                      AppStrings.talkChatAstrologers,
                      style: Theme.of(context).textTheme.titleSmall?.copyWith(
                            fontWeight: FontWeight.w800,
                            letterSpacing: -0.2,
                          ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  const SizedBox(width: 6),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                    decoration: BoxDecoration(
                      color: isDark ? const Color(0xFF14532D) : const Color(0xFFDCFCE7),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.fiber_manual_record, color: Color(0xFF16A34A), size: 7),
                        const SizedBox(width: 3),
                        Text(
                          AppStrings.online,
                          style: TextStyle(
                            color: isDark ? const Color(0xFF86EFAC) : const Color(0xFF15803D),
                            fontSize: 9.5,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  GestureDetector(
                    onTap: () => _goToTab(1), // Consult / Astrologers tab (index 1)
                    child: Text(
                      '${AppStrings.viewAll} (${list.length}) →',
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w800,
                        color: CustomerTheme.brandSaffron,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            if (_firstConsultOffer?['eligible'] == true)
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 10),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: isDark
                          ? [const Color(0xFF451A03), const Color(0xFF78350F)]
                          : [const Color(0xFFFEF3C7), const Color(0xFFFDE68A)],
                    ),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: const Color(0xFFF59E0B)),
                  ),
                  child: Row(
                    children: [
                      const Text("🎉", style: TextStyle(fontSize: 18)),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          isHi
                              ? "प्रमाणित आचार्यों से प्रथम 5 मिनट परामर्श मात्र ₹1/मिनट में!"
                              : "First 5 Mins Consultation at ₹1/min only with Verified Gurus!",
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w800,
                            color: isDark ? const Color(0xFFFEF3C7) : const Color(0xFF92400E),
                          ),
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: const Color(0xFFEA580C),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          isHi ? "₹1/मिनट" : "₹1/min",
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 10.5,
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            SizedBox(
              height: 180,
              child: ListView.separated(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                scrollDirection: Axis.horizontal,
                itemCount: list.take(8).length,
                separatorBuilder: (_, _) => const SizedBox(width: 12),
                itemBuilder: (context, i) {
                  final a = list[i];
                  final rate = a.charge > 0
                      ? '$currency${a.charge.toInt()}/${isHi ? 'मि' : 'm'}'
                      : (isHi ? 'निःशुल्क' : 'FREE');

                  return Container(
                    width: 154,
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: isDark ? const Color(0xFF1E1B2E) : Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: isDark ? const Color(0xFF332B4A) : const Color(0xFFE2E8F0)),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.04),
                          blurRadius: 8,
                          offset: const Offset(0, 3),
                        ),
                      ],
                    ),
                    child: InkWell(
                      onTap: () => context.openAstrologer(a.id ?? 0),
                      borderRadius: BorderRadius.circular(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.center,
                        children: [
                          Stack(
                            children: [
                              CircleAvatar(
                                radius: 26,
                                backgroundImage: a.imageUrl.isNotEmpty ? CachedNetworkImageProvider(a.imageUrl) : null,
                                child: a.imageUrl.isEmpty ? _avatarFallback(a.name) : null,
                              ),
                              Positioned(
                                bottom: 0,
                                right: 0,
                                child: Container(
                                  width: 11,
                                  height: 11,
                                  decoration: BoxDecoration(
                                    color: const Color(0xFF16A34A),
                                    shape: BoxShape.circle,
                                    border: Border.all(color: Colors.white, width: 2),
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 5),
                          Text(
                            a.name,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            textAlign: TextAlign.center,
                            style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 12),
                          ),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Icon(Icons.star_rounded, size: 12, color: Color(0xFFF59E0B)),
                              const SizedBox(width: 2),
                              Text(
                                a.rating > 0 ? a.rating.toStringAsFixed(1) : '4.9',
                                style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w700),
                              ),
                              const SizedBox(width: 4),
                              Text(
                                isHi ? '${a.experienceInYears} वर्ष' : '${a.experienceInYears}y exp',
                                style: TextStyle(
                                  fontSize: 9.5,
                                  color: isDark ? Colors.white60 : const Color(0xFF64748B),
                                ),
                              ),
                            ],
                          ),
                          const Spacer(),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                rate,
                                style: const TextStyle(
                                  fontWeight: FontWeight.w900,
                                  fontSize: 11,
                                  color: Color(0xFFD97706),
                                ),
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                decoration: BoxDecoration(
                                  gradient: CustomerTheme.saffronGradient,
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Text(
                                  isHi ? 'जुड़ें' : 'Connect',
                                  style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: Colors.white),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _avatarFallback(String name) {
    final initial = name.isNotEmpty ? name[0].toUpperCase() : 'A';
    return Container(
      color: const Color(0xFFFFF3E0),
      child: Center(
        child: Text(
          initial,
          style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFFE65100), fontSize: 18),
        ),
      ),
    );
  }

  /// Popular Pujas & Havans horizontal preview card section
  Widget _trendingPujasSection(
    BuildContext context,
    ColorScheme scheme,
    String currency,
    bool isHi,
  ) {
    return FutureBuilder<List<Puja>>(
      future: _pujaFuture,
      builder: (context, snap) {
        final list = snap.data ?? [];
        if (list.isEmpty) return const SizedBox.shrink();

        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 18, 16, 10),
              child: Row(
                children: [
                  Container(
                    width: 3.5,
                    height: 15,
                    decoration: BoxDecoration(
                      color: const Color(0xFFB71C1C),
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                  const SizedBox(width: 7),
                  Expanded(
                    child: Text(
                      AppStrings.sacredPujasChadhava,
                      style: Theme.of(context).textTheme.titleSmall?.copyWith(
                            fontWeight: FontWeight.w800,
                            letterSpacing: -0.2,
                          ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  GestureDetector(
                    onTap: () => _goToTab(2), // Puja tab (index 2)
                    child: Text(
                      '${AppStrings.viewAll} →',
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w800,
                        color: CustomerTheme.brandSaffron,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            SizedBox(
              height: 196,
              child: ListView.separated(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                scrollDirection: Axis.horizontal,
                itemCount: list.take(6).length,
                separatorBuilder: (_, _) => const SizedBox(width: 14),
                itemBuilder: (context, i) {
                  final p = list[i];
                  final priceText = p.startingPrice != null
                      ? '$currency${p.startingPrice!.toInt()}'
                      : '${currency}501';

                  return GestureDetector(
                    onTap: () => Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => PujaDetailScreen(puja: p)),
                    ),
                    child: Container(
                      width: 210,
                      decoration: BoxDecoration(
                        color: scheme.surface,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: scheme.outline.withValues(alpha: 0.18)),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.04),
                            blurRadius: 8,
                            offset: const Offset(0, 3),
                          ),
                        ],
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          ClipRRect(
                            borderRadius: const BorderRadius.vertical(top: Radius.circular(15)),
                            child: Stack(
                              children: [
                                Builder(
                                  builder: (context) {
                                    final rawImg = p.coverImage.trim();
                                    final imgUrl = rawImg.isNotEmpty ? MiscApi.imageUrl(rawImg) : '';
                                    if (imgUrl.isNotEmpty) {
                                      return Image.network(
                                        imgUrl,
                                        height: 100,
                                        width: 210,
                                        fit: BoxFit.cover,
                                        errorBuilder: (_, e, s) => Container(
                                          height: 100,
                                          color: const Color(0xFFFDE68A),
                                          child: const Icon(Icons.temple_hindu_rounded, size: 36),
                                        ),
                                      );
                                    }
                                    return Container(
                                      height: 100,
                                      color: const Color(0xFFFDE68A),
                                      child: const Center(
                                        child: Icon(Icons.temple_hindu_rounded, size: 36),
                                      ),
                                    );
                                  },
                                ),
                                Positioned(
                                  top: 8,
                                  left: 8,
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                                    decoration: BoxDecoration(
                                      color: const Color(0xFFB71C1C),
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                    child: Text(
                                      isHi ? 'प्रमाणित पंडित' : 'VERIFIED PANDIT',
                                      style: const TextStyle(
                                        color: Colors.white,
                                        fontSize: 8,
                                        fontWeight: FontWeight.w900,
                                        letterSpacing: 0.4,
                                      ),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          Padding(
                            padding: const EdgeInsets.all(10),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  p.title.isNotEmpty ? p.title : (isHi ? 'वैदिक पूजा' : 'Vedic Puja'),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w800,
                                    fontSize: 12.5,
                                  ),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  p.place.isNotEmpty ? p.place : (isHi ? 'पवित्र तीर्थ धाम' : 'Holy Teerth Pilgrimage'),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: TextStyle(
                                    fontSize: 10.5,
                                    color: scheme.onSurfaceVariant,
                                  ),
                                ),
                                const SizedBox(height: 6),
                                Row(
                                  children: [
                                    Text(
                                      priceText,
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w900,
                                        fontSize: 13.5,
                                        color: Color(0xFF1B8A5A),
                                      ),
                                    ),
                                    const Spacer(),
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                      decoration: BoxDecoration(
                                        color: CustomerTheme.brandSaffron.withValues(alpha: 0.12),
                                        borderRadius: BorderRadius.circular(8),
                                      ),
                                      child: Text(
                                        isHi ? 'बुक करें' : 'Book Now',
                                        style: const TextStyle(
                                          color: CustomerTheme.brandSaffron,
                                          fontWeight: FontWeight.w800,
                                          fontSize: 10.5,
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _drawer(BuildContext context, AppSession session, ColorScheme scheme, bool isHi) {
    final u = session.user;
    final currency = session.flags.currency.isNotEmpty ? session.flags.currency : '₹';
    return Drawer(
      child: SafeArea(
        child: ListView(
          padding: EdgeInsets.zero,
          children: [
            UserAccountsDrawerHeader(
              currentAccountPicture: CircleAvatar(
                backgroundColor: scheme.primaryContainer,
                child: Icon(Icons.person_rounded, color: scheme.primary, size: 36),
              ),
              accountName: Text(
                u?.displayName ?? (isHi ? 'श्रद्धालु अतिथि' : 'Devotee Guest'),
                style: const TextStyle(fontWeight: FontWeight.bold),
              ),
              accountEmail: Text(
                u?.contactNo?.isNotEmpty == true
                    ? '${u?.countryCode ?? '+91'} ${u?.contactNo}'
                    : (isHi ? 'साइन इन करने हेतु टैप करें' : 'Tap to sign in'),
                style: const TextStyle(color: Colors.white70),
              ),
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [CustomerTheme.brandSaffron, CustomerTheme.brandDeepSaffron],
                ),
              ),
            ),
            ListTile(
              leading: const Icon(Icons.account_balance_wallet_outlined),
              title: Text(isHi ? 'वॉलेट बैलेंस' : 'Wallet Balance'),
              trailing: Text(
                '$currency${(u?.walletAmount ?? 0).toStringAsFixed(0)}',
                style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF1B8A5A)),
              ),
              onTap: () {
                Navigator.pop(context);
                Navigator.of(context).pushNamed(WalletScreen.route).then((_) => session.refreshUser());
              },
            ),
            ListTile(
              leading: const Icon(Icons.translate_rounded, color: Color(0xFFD97706)),
              title: Text(isHi ? 'भाषा बदलें ( हिंदी / English )' : 'Change Language ( हिंदी / English )'),
              trailing: Text(
                isHi ? 'हिंदी' : 'English',
                style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFFD97706)),
              ),
              onTap: () {
                Navigator.pop(context);
                _showLanguageSelector(context);
              },
            ),
            const Divider(),
            ListTile(
              leading: const Icon(Icons.psychology_rounded),
              title: Text(isHi ? 'ज्योतिषी परामर्श (चैट / कॉल)' : 'Consult Astrologers'),
              onTap: () {
                Navigator.pop(context);
                _goToTab(1);
              },
            ),
            ListTile(
              leading: const Icon(Icons.local_fire_department_rounded),
              title: Text(isHi ? 'पूजा एवं हवन बुक करें' : 'Book a Puja'),
              onTap: () {
                Navigator.pop(context);
                _goToTab(2);
              },
            ),
            ListTile(
              leading: const Icon(Icons.temple_hindu_rounded),
              title: Text(isHi ? 'लाइव मंदिर दर्शन' : 'Live Temple Darshan'),
              onTap: () {
                Navigator.pop(context);
                Navigator.of(context).pushNamed(LiveDarshanScreen.route);
              },
            ),
            ListTile(
              leading: const Icon(Icons.storefront_rounded),
              title: Text(isHi ? 'वैदिक भण्डार (रुद्राक्ष, रत्न व सामग्री)' : 'Sacred Store (Rudraksha & Gems)'),
              onTap: () {
                Navigator.pop(context);
                _goToTab(3);
              },
            ),
            ListTile(
              leading: const Icon(Icons.nightlight_round),
              title: Text(isHi ? 'दैनिक राशिफल' : 'Daily Horoscope'),
              onTap: () {
                Navigator.pop(context);
                Navigator.of(context).push(MaterialPageRoute(builder: (_) => const DailyHoroscopeScreen()));
              },
            ),
            ListTile(
              leading: const Icon(Icons.auto_graph_rounded),
              title: Text(isHi ? 'निःशुल्क कुंडली' : 'Free Kundli'),
              onTap: () {
                Navigator.pop(context);
                Navigator.of(context).push(MaterialPageRoute(builder: (_) => const KundliListScreen()));
              },
            ),
            ListTile(
              leading: const Icon(Icons.favorite_rounded),
              title: Text(isHi ? 'कुंडली मिलान' : 'Kundli Matching'),
              onTap: () {
                Navigator.pop(context);
                Navigator.of(context).push(MaterialPageRoute(builder: (_) => const KundliMatchingScreen()));
              },
            ),
            ListTile(
              leading: const Icon(Icons.wb_twilight_rounded),
              title: Text(isHi ? 'आज का पंचांग' : "Today's Panchang"),
              onTap: () {
                Navigator.pop(context);
                Navigator.of(context).push(MaterialPageRoute(builder: (_) => const PanchangScreen()));
              },
            ),
            ListTile(
              leading: const Icon(Icons.auto_awesome, color: Color(0xFFD97706)),
              title: Text(isHi ? 'OnlinePuja AI आचार्य (चैट / कॉल)' : 'OnlinePuja AI Astrologer'),
              onTap: () {
                Navigator.pop(context);
                Navigator.of(context).push(MaterialPageRoute(builder: (_) => const OnlinePujaAiScreen()));
              },
            ),
            ListTile(
              leading: const Icon(Icons.history_rounded),
              title: Text(isHi ? 'परामर्श इतिहास' : 'Consultation History'),
              onTap: () {
                Navigator.pop(context);
                Navigator.of(context).push(MaterialPageRoute(builder: (_) => const HistoryScreen()));
              },
            ),
            ListTile(
              leading: const Icon(Icons.volunteer_activism_rounded, color: Color(0xFFD84315)),
              title: Text(isHi ? 'अन्नदान एवं गो सेवा' : 'Digital Annadaan & Gau Seva'),
              onTap: () {
                Navigator.pop(context);
                Navigator.of(context).push(MaterialPageRoute(builder: (_) => const AnnadaanScreen()));
              },
            ),
            ListTile(
              leading: const Icon(Icons.family_restroom_rounded, color: Color(0xFFB45309)),
              title: Text(isHi ? 'परिवार गोत्र एवं कुलदेवता वॉल्ट' : 'Family Gotra & Kuldevta Vault'),
              onTap: () {
                Navigator.pop(context);
                Navigator.of(context).push(MaterialPageRoute(builder: (_) => const FamilyGotraVaultScreen()));
              },
            ),
            ListTile(
              leading: const Icon(Icons.help_outline_rounded, color: Color(0xFF00838F)),
              title: Text(isHi ? 'प्रश्न कुंडली ओरेकल' : 'Prashna Shastra Oracle'),
              onTap: () {
                Navigator.pop(context);
                Navigator.of(context).push(MaterialPageRoute(builder: (_) => const PrashnaOracleScreen()));
              },
            ),
            ListTile(
              leading: const Icon(Icons.bedtime_rounded, color: Color(0xFF4527A0)),
              title: Text(isHi ? 'स्वप्न विचार (ड्रीम इंटरप्रेटर)' : 'Swapna Shastra (Dream Interpreter)'),
              onTap: () {
                Navigator.pop(context);
                Navigator.of(context).push(MaterialPageRoute(builder: (_) => const SwapnaShastraScreen()));
              },
            ),
            ListTile(
              leading: const Icon(Icons.menu_book_rounded, color: Color(0xFF558B2F)),
              title: Text(isHi ? 'वैदिक लेख एवं ब्लॉग' : 'Vedic Blogs & Articles'),
              onTap: () {
                Navigator.pop(context);
                Navigator.of(context).push(MaterialPageRoute(builder: (_) => const BlogScreen()));
              },
            ),
            const Divider(),
            ListTile(
              leading: const Icon(Icons.palette_outlined),
              title: Text(isHi ? 'थीम / दिखावट' : 'Theme / Appearance'),
              subtitle: Text(session.themeMode.name.toUpperCase()),
              trailing: const Icon(Icons.brightness_medium_rounded),
              onTap: () {
                final next = session.themeMode == ThemeMode.dark ? ThemeMode.light : ThemeMode.dark;
                session.setThemeMode(next);
              },
            ),
          ],
        ),
      ),
    );
  }
}
