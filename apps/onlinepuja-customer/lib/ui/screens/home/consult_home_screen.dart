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
                  width: 28,
                  height: 28,
                  decoration: const BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: CustomerTheme.goldGradient,
                    boxShadow: [
                      BoxShadow(
                        color: Color(0x3DF59E0B),
                        blurRadius: 6,
                        offset: Offset(0, 1),
                      ),
                    ],
                  ),
                  child: const Center(
                    child: Text('ॐ',
                        style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w900,
                            color: Color(0xFF78350F))),
                  ),
                ),
                const SizedBox(width: 6),
                RichText(
                  text: TextSpan(
                    children: [
                      TextSpan(
                        text: 'OnlinePuja',
                        style: TextStyle(
                          fontWeight: FontWeight.w900,
                          fontSize: 16.5,
                          letterSpacing: -0.4,
                          color: isDark ? Colors.white : const Color(0xFF1E1A16),
                        ),
                      ),
                      const TextSpan(
                        text: '.live',
                        style: TextStyle(
                          fontWeight: FontWeight.w900,
                          fontSize: 16.5,
                          color: Color(0xFFEA580C),
                          letterSpacing: -0.4,
                        ),
                      ),
                    ],
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
                  margin: const EdgeInsets.symmetric(vertical: 13, horizontal: 2),
                  padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3.5),
                  decoration: BoxDecoration(
                    color: isHi
                        ? const Color(0xFFD97706).withValues(alpha: 0.15)
                        : (isDark ? Colors.white10 : const Color(0xFFF1F5F9)),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: isHi ? const Color(0xFFD97706) : Colors.transparent,
                    ),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.translate_rounded, size: 11, color: Color(0xFFD97706)),
                      const SizedBox(width: 3),
                      Text(
                        isHi ? 'हिं' : 'EN',
                        style: TextStyle(
                          fontSize: 10.5,
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
                visualDensity: VisualDensity.compact,
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
                tooltip: isHi ? 'AI ज्योतिषी' : 'OnlinePuja AI',
                icon: Container(
                  padding: const EdgeInsets.all(5),
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      colors: [Color(0xFFFEF3C7), Color(0xFFFDE68A)],
                    ),
                    shape: BoxShape.circle,
                    border: Border.all(color: const Color(0xFFF59E0B)),
                  ),
                  child: const Icon(Icons.auto_awesome_rounded, size: 14, color: Color(0xFFD97706)),
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
                  margin: const EdgeInsets.symmetric(vertical: 13, horizontal: 3),
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3.5),
                  decoration: BoxDecoration(
                    color: const Color(0xFF1B8A5A),
                    borderRadius: BorderRadius.circular(14),
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
                      const Text('🪙', style: TextStyle(fontSize: 10)),
                      const SizedBox(width: 3),
                      Text(
                        '$currency${wallet.toStringAsFixed(0)}',
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.w800,
                          fontSize: 11.5,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              // Notifications Bell
              IconButton(
                visualDensity: VisualDensity.compact,
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
                tooltip: isHi ? 'सूचनाएं' : 'Notifications',
                icon: Icon(
                  Icons.notifications_none_rounded,
                  size: 21,
                  color: isDark ? Colors.white70 : const Color(0xFF475569),
                ),
                onPressed: () => Navigator.of(context).pushNamed(NotificationsScreen.route),
              ),
              const SizedBox(width: 6),
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
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: isDark
                ? [const Color(0xFF381806), const Color(0xFF1E0D03)]
                : [const Color(0xFFFFFBEB), const Color(0xFFFEF3C7)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: const Color(0xFFF59E0B).withValues(alpha: 0.4),
            width: 1.2,
          ),
          boxShadow: [
            BoxShadow(
              color: const Color(0xFFD97706).withValues(alpha: isDark ? 0.2 : 0.08),
              blurRadius: 8,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(4),
                  decoration: const BoxDecoration(
                    shape: BoxShape.circle,
                    color: Color(0xFFD97706),
                  ),
                  child: const Text('🪔', style: TextStyle(fontSize: 12)),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    isHi ? 'काशी दीपोत्सव एवं पर्व महाकुंभ 2026' : 'Kashi Festive Mahotsav 2026',
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w900,
                      letterSpacing: -0.2,
                      color: isDark ? const Color(0xFFFCD34D) : const Color(0xFF92400E),
                    ),
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2.5),
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      colors: [Color(0xFFEA580C), Color(0xFFD97706)],
                    ),
                    borderRadius: BorderRadius.circular(10),
                    boxShadow: const [
                      BoxShadow(
                        color: Color(0x3DEA580C),
                        blurRadius: 4,
                        offset: Offset(0, 1),
                      ),
                    ],
                  ),
                  child: Text(
                    isHi ? '🔥 सीमित तिथियां' : '🔥 BOOK EARLY',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 9.5,
                      fontWeight: FontWeight.w900,
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
                          color: pill.color.withValues(alpha: 0.3),
                          width: 1,
                        ),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.04),
                            blurRadius: 4,
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
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
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
                  isHi ? 'संकल्प पूजा बुक करें' : 'Book Sankalp Puja',
                  style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w800),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
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
    final isHi = LocaleManager.instance.currentLanguage.value != AppLanguage.en;
    final searchBg = isDark ? CustomerTheme.cosmicCardElevated : Colors.white;
    final searchBorder = isDark ? const Color(0x3DF59E0B) : const Color(0xFFFDE68A);

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
                border: Border.all(color: searchBorder, width: 1.2),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFFD97706).withValues(alpha: isDark ? 0.2 : 0.06),
                    blurRadius: 10,
                    offset: const Offset(0, 3),
                  ),
                ],
              ),
              child: Row(
                children: [
                  const Icon(Icons.search_rounded, color: Color(0xFFEA580C), size: 22),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      isHi
                          ? 'सर्च करें: पूजा, कुंडली, पंचांग, ज्योतिषी...'
                          : 'Search Pujas, Kundli, Astrologers...',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        color: scheme.onSurfaceVariant.withValues(alpha: 0.85),
                        fontSize: 13,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
                  GestureDetector(
                    onTap: () => Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => const OnlinePujaAiScreen()),
                    ),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        gradient: CustomerTheme.saffronGradient,
                        borderRadius: BorderRadius.circular(12),
                        boxShadow: const [
                          BoxShadow(
                            color: Color(0x3DEA580C),
                            blurRadius: 4,
                            offset: Offset(0, 1),
                          ),
                        ],
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.auto_awesome, color: Colors.white, size: 12),
                          const SizedBox(width: 3),
                          Text(
                            isHi ? 'AI गुरु' : 'AI',
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 10,
                              fontWeight: FontWeight.w900,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 7),
          // Interactive Auspicious Shubh Muhurat Ribbon
          InkWell(
            onTap: () => Navigator.of(context).push(
              MaterialPageRoute(builder: (_) => const PanchangScreen()),
            ),
            borderRadius: BorderRadius.circular(12),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: isDark
                      ? [const Color(0xFF381806), const Color(0xFF1E0D03)]
                      : [const Color(0xFFFFFBEB), const Color(0xFFFEF3C7)],
                  begin: Alignment.centerLeft,
                  end: Alignment.centerRight,
                ),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: const Color(0xFFF59E0B).withValues(alpha: 0.35),
                  width: 1,
                ),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFFD97706).withValues(alpha: 0.05),
                    blurRadius: 4,
                    offset: const Offset(0, 1),
                  ),
                ],
              ),
              child: Row(
                children: [
                  Container(
                    width: 18,
                    height: 18,
                    decoration: const BoxDecoration(
                      shape: BoxShape.circle,
                      gradient: CustomerTheme.goldGradient,
                    ),
                    child: const Center(
                      child: Text('ॐ',
                          style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w900,
                              color: Color(0xFF78350F))),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: RichText(
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      text: TextSpan(
                        children: [
                          TextSpan(
                            text: isHi ? 'आज का शुभ मुहूर्त: ' : "Today's Shubh Muhurat: ",
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w800,
                              color: isDark ? const Color(0xFFFDE68A) : const Color(0xFF92400E),
                            ),
                          ),
                          TextSpan(
                            text: isHi
                                ? 'अमृत काल सक्रिय • राहु काल समाप्त'
                                : 'Amrit Kaal Active • Rahu Kaal Ended',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                              color: isDark ? Colors.white70 : const Color(0xFFB45309),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(width: 4),
                  Icon(
                    Icons.arrow_forward_ios_rounded,
                    size: 10,
                    color: isDark ? const Color(0xFFFCD34D) : const Color(0xFFB45309),
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
        tag: isHi ? 'विशेष महापर्व' : 'SPECIAL FESTIVAL',
        icon: Icons.brightness_high_rounded,
        image: 'https://onlinepuja.live/public/storage/puja_images/puja_1764232697222466223.webp',
        gradient: const [Color(0xFF9A3412), Color(0xFFC2410C), Color(0xFFEA580C)],
        onTap: () => _goToTab(2), // Puja tab (index 2)
      ),
      (
        title: isHi ? 'प्रामाणिक ऑनलाइन पूजा एवं हवन' : 'Authentic Online Puja & Hawan',
        subtitle: isHi
            ? 'काशी व उज्जैन से लाइव संकल्प, वीडियो प्रमाण और प्रसाद'
            : 'Live Sankalp & Consecrated Prasad from Holy Dhams',
        cta: isHi ? 'पूजा बुक करें 🙏' : 'Book Puja 🙏',
        tag: isHi ? 'लाइव संकल्प' : 'LIVE SANKALP',
        icon: Icons.local_fire_department_rounded,
        image: 'https://onlinepuja.live/public/storage/puja_images/puja_17642331041781434446.png',
        gradient: const [Color(0xFF7F1D1D), Color(0xFF991B1B), Color(0xFFDC2626)],
        onTap: () => _goToTab(2), // Puja tab (index 2)
      ),
      (
        title: isHi ? 'वैदिक आचार्यों से सटीक परामर्श' : 'Accurate Predictions by Vedic Astrologers',
        subtitle: isHi
            ? 'प्रथम सत्र ₹1/मिनट · कुंडली, विवाह, करियर व दोष निवारण'
            : 'First session ₹1/min · Kundli, Marriage & Career Guidance',
        cta: isHi ? 'ज्योतिषी से बात करें 📞' : 'Consult Astrologer 📞',
        tag: isHi ? '₹1/मिनट ऑफर' : 'SPECIAL OFFER',
        icon: Icons.self_improvement_rounded,
        image: 'https://onlinepuja.live/public/storage/puja_images/puja_17642330451605364386.jpg',
        gradient: const [Color(0xFF3B0764), Color(0xFF581C87), Color(0xFF7E22CE)],
        onTap: () => _goToTab(1), // Consult tab (index 1)
      ),
      (
        title: isHi ? '24/7 लाइव मंदिर दर्शन एवं आरती' : '24/7 Live Sanctum Darshan & Aarti',
        subtitle: isHi
            ? 'काशी विश्वनाथ, महाकालेश्वर एवं गंगा आरती लाइव'
            : 'Kashi Vishwanath, Mahakaleshwar & Ganga Aarti Feeds',
        cta: isHi ? 'लाइव दर्शन 🪔' : 'Watch Live 🪔',
        tag: isHi ? '🔴 लाइव प्रसारण' : '🔴 LIVE STREAM',
        icon: Icons.temple_hindu_rounded,
        image: 'https://onlinepuja.live/public/storage/brain_media/brain_NBw7PwNZxf2gqejbRd_940x627.jpg',
        gradient: const [Color(0xFF0F172A), Color(0xFF1E293B), Color(0xFF0F766E)],
        onTap: () => Navigator.of(context).pushNamed(LiveDarshanScreen.route),
      ),
      (
        title: isHi ? 'वैदिक भण्डार एवं सिद्ध रुद्राक्ष' : 'Sacred Store & Energized Rudraksha',
        subtitle: isHi
            ? '100% प्रमाणित प्राण-प्रतिष्ठित रुद्राक्ष, रत्न एवं माला'
            : '100% Consecrated Rudraksha, Gemstones & Sphatik Malas',
        cta: isHi ? 'भण्डार देखें 📿' : 'Explore Store 📿',
        tag: isHi ? '100% प्रमाणित' : '100% CONSECRATED',
        icon: Icons.storefront_rounded,
        image: 'https://onlinepuja.live/public/storage/puja_images/puja_1764232697222466223.webp',
        gradient: const [Color(0xFF14532D), Color(0xFF166534), Color(0xFF15803D)],
        onTap: () => _goToTab(3), // Vedic Store tab (index 3)
      ),
    ];

    final totalCount = _remoteBanners.length + curatedBanners.length;

    return Column(
      children: [
        SizedBox(
          height: 174,
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
                        borderRadius: BorderRadius.circular(20),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.18),
                            blurRadius: 10,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(20),
                        child: CachedNetworkImage(
                          imageUrl: imageUrl,
                          fit: BoxFit.cover,
                          width: double.infinity,
                          height: double.infinity,
                          placeholder: (_, _) => Container(
                            color: Colors.amber.shade100,
                            child: const Center(
                              child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFFD97706)),
                            ),
                          ),
                          errorWidget: (_, error, stack) => _buildCuratedBannerCard(curatedBanners[0]),
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
        const SizedBox(height: 8),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            for (int i = 0; i < totalCount; i++)
              AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                margin: const EdgeInsets.symmetric(horizontal: 3),
                width: _bannerIndex == i ? 22 : 6,
                height: 6,
                decoration: BoxDecoration(
                  gradient: _bannerIndex == i ? CustomerTheme.goldGradient : null,
                  color: _bannerIndex == i
                      ? null
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
        borderRadius: BorderRadius.circular(20),
        gradient: LinearGradient(
          colors: b.gradient,
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        border: Border.all(
          color: Colors.white.withValues(alpha: 0.22),
          width: 1.2,
        ),
        boxShadow: [
          BoxShadow(
            color: (b.gradient.first as Color).withValues(alpha: 0.35),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 12, 14, 12),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2.5),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      b.tag,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 9,
                        fontWeight: FontWeight.w900,
                        letterSpacing: 0.4,
                      ),
                    ),
                  ),
                  const SizedBox(height: 5),
                  Text(
                    b.title,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 15,
                      fontWeight: FontWeight.w900,
                      height: 1.22,
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    b.subtitle,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: Colors.white70,
                      fontSize: 10.5,
                      height: 1.2,
                    ),
                  ),
                  const SizedBox(height: 9),
                  ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.white,
                      foregroundColor: b.gradient.first,
                      elevation: 3,
                      shadowColor: Colors.black26,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(20),
                      ),
                      padding: const EdgeInsets.symmetric(
                        horizontal: 14,
                        vertical: 5,
                      ),
                      visualDensity: VisualDensity.compact,
                    ),
                    onPressed: b.onTap,
                    child: Text(
                      b.cta,
                      style: const TextStyle(
                        fontWeight: FontWeight.w900,
                        fontSize: 11.5,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 10),
            Container(
              width: 82,
              height: 82,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(18),
                border: Border.all(color: const Color(0xFFFDE68A), width: 1.8),
                boxShadow: const [
                  BoxShadow(
                    color: Colors.black38,
                    blurRadius: 10,
                    offset: Offset(0, 4),
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
                    placeholder: (context, url) => Container(color: Colors.white.withValues(alpha: 0.15)),
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
                        colors: [Colors.transparent, Colors.black.withValues(alpha: 0.25)],
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
            height: 184,
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
                    width: 128,
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(18),
                      color: const Color(0xFF0F172A),
                      border: Border.all(
                        color: isLive
                            ? const Color(0xFFEF4444)
                            : (i == 0 ? CustomerTheme.brandGold : const Color(0x33F59E0B)),
                        width: isLive || i == 0 ? 1.5 : 1,
                      ),
                      boxShadow: [
                        BoxShadow(
                          color: (isLive ? const Color(0xFFEF4444) : CustomerTheme.brandGold)
                              .withValues(alpha: 0.2),
                          blurRadius: 10,
                          offset: const Offset(0, 4),
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
                                Colors.black.withValues(alpha: 0.15),
                                Colors.black.withValues(alpha: 0.88),
                              ],
                              stops: const [0.0, 0.52, 1.0],
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
                              color: isLive ? const Color(0xFFDC2626) : Colors.black.withValues(alpha: 0.65),
                              borderRadius: BorderRadius.circular(6),
                              border: Border.all(
                                color: isLive ? Colors.white70 : CustomerTheme.brandGold.withValues(alpha: 0.5),
                                width: 0.8,
                              ),
                              boxShadow: const [
                                BoxShadow(
                                  color: Colors.black26,
                                  blurRadius: 3,
                                  offset: Offset(0, 1),
                                ),
                              ],
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
                              color: Colors.black.withValues(alpha: 0.35),
                              shape: BoxShape.circle,
                              border: Border.all(color: Colors.white30, width: 0.8),
                            ),
                            child: const Icon(Icons.play_arrow_rounded, color: Colors.white, size: 16),
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
                                  fontSize: 11.5,
                                  fontWeight: FontWeight.w900,
                                  height: 1.25,
                                ),
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                              ),
                              const SizedBox(height: 2),
                              Text(
                                s['subtitle'] as String,
                                style: const TextStyle(
                                  color: Color(0xFFFDE68A),
                                  fontSize: 9.5,
                                  fontWeight: FontWeight.w700,
                                ),
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

  /// World-Class 4x3 Curated Vedic Sanctum Grid with sacred glow, badges, and dual-tone gradients
  Widget _curatedVedicGrid(BuildContext context, ColorScheme scheme, bool isDark) {
    final isHi = LocaleManager.instance.currentLanguage.value != AppLanguage.en;

    final tools = [
      (
        title: AppStrings.freeKundli,
        tag: isHi ? 'निःशुल्क' : 'FREE',
        tagBg: const Color(0xFF10B981),
        icon: Icons.auto_graph_rounded,
        gradient: const [Color(0xFFFF6B00), Color(0xFFDC2626)],
        destination: const KundliListScreen(),
      ),
      (
        title: AppStrings.kundliMatching,
        tag: isHi ? '३६ गुण' : '36 Gunas',
        tagBg: const Color(0xFFBE185D),
        icon: Icons.favorite_rounded,
        gradient: const [Color(0xFFE11D48), Color(0xFF9F1239)],
        destination: const KundliMatchingScreen(),
      ),
      (
        title: AppStrings.panchang,
        tag: isHi ? 'आज का' : 'Daily',
        tagBg: const Color(0xFFD97706),
        icon: Icons.wb_sunny_rounded,
        gradient: const [Color(0xFFF59E0B), Color(0xFFB45309)],
        destination: const PanchangScreen(),
      ),
      (
        title: AppStrings.horoscope,
        tag: isHi ? 'राशिफल' : 'Today',
        tagBg: const Color(0xFF7C3AED),
        icon: Icons.nightlight_round,
        gradient: const [Color(0xFF8B5CF6), Color(0xFF5B21B6)],
        destination: const DailyHoroscopeScreen(),
      ),
      (
        title: AppStrings.kpCalendar,
        tag: isHi ? 'सटीक' : 'KP System',
        tagBg: const Color(0xFF2563EB),
        icon: Icons.calendar_month_rounded,
        gradient: const [Color(0xFF3B82F6), Color(0xFF1D4ED8)],
        destination: const KpCalendarScreen(),
      ),
      (
        title: AppStrings.cosmicAi,
        tag: 'AI 24/7',
        tagBg: const Color(0xFF9333EA),
        icon: Icons.auto_awesome_rounded,
        gradient: const [Color(0xFFA855F7), Color(0xFF6B21A8)],
        destination: const CosmicAiScreen(),
      ),
      (
        title: AppStrings.japaMala,
        tag: isHi ? '१०८ जाप' : '108 Jaap',
        tagBg: const Color(0xFFB45309),
        icon: Icons.circle_outlined,
        gradient: const [Color(0xFFD97706), Color(0xFF78350F)],
        destination: const JapaMalaScreen(),
      ),
      (
        title: AppStrings.mySankalp,
        tag: isHi ? 'संकल्प' : 'Vault',
        tagBg: const Color(0xFF059669),
        icon: Icons.savings_rounded,
        gradient: const [Color(0xFF10B981), Color(0xFF047857)],
        destination: const SankalpVaultScreen(),
      ),
      (
        title: AppStrings.annadaan,
        tag: isHi ? 'पुण्य लाभ' : 'Seva',
        tagBg: const Color(0xFFEA580C),
        icon: Icons.volunteer_activism_rounded,
        gradient: const [Color(0xFFF97316), Color(0xFFC2410C)],
        destination: const AnnadaanScreen(),
      ),
      (
        title: AppStrings.prashnaKundli,
        tag: isHi ? 'तत्काल' : 'Instant',
        tagBg: const Color(0xFF0891B2),
        icon: Icons.help_outline_rounded,
        gradient: const [Color(0xFF06B6D4), Color(0xFF0E7490)],
        destination: const PrashnaOracleScreen(),
      ),
      (
        title: isHi ? 'स्वप्न विचार' : 'Swapna Shastra',
        tag: isHi ? 'फल विचार' : 'Shastra',
        tagBg: const Color(0xFF4F46E5),
        icon: Icons.bedtime_rounded,
        gradient: const [Color(0xFF6366F1), Color(0xFF3730A3)],
        destination: const SwapnaShastraScreen(),
      ),
      (
        title: isHi ? 'वैदिक ज्ञान' : 'Vedic Blogs',
        tag: isHi ? 'ज्ञान' : 'Articles',
        tagBg: const Color(0xFF65A30D),
        icon: Icons.menu_book_rounded,
        gradient: const [Color(0xFF84CC16), Color(0xFF4D7C0F)],
        destination: const BlogScreen(),
      ),
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
                height: 16,
                decoration: BoxDecoration(
                  gradient: CustomerTheme.saffronGradient,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  AppStrings.vedicSanctumTitle,
                  style: Theme.of(context).textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.w900,
                        letterSpacing: -0.2,
                      ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                decoration: BoxDecoration(
                  color: isDark ? const Color(0xFF1E1B4B) : const Color(0xFFFEF3C7),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(
                    color: const Color(0xFFF59E0B).withValues(alpha: 0.4),
                  ),
                ),
                child: Text(
                  isHi ? '१२+ नित्य सेवाएं' : '12+ Sacred Utilities',
                  style: TextStyle(
                    fontSize: 9.5,
                    fontWeight: FontWeight.w800,
                    color: isDark ? const Color(0xFFFDE68A) : const Color(0xFF92400E),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: tools.length,
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 4,
              childAspectRatio: 0.76,
              crossAxisSpacing: 10,
              mainAxisSpacing: 12,
            ),
            itemBuilder: (context, i) {
              final t = tools[i];
              return InkWell(
                onTap: () => Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => t.destination),
                ),
                borderRadius: BorderRadius.circular(16),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Stack(
                      clipBehavior: Clip.none,
                      children: [
                        Container(
                          width: 56,
                          height: 56,
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              colors: t.gradient,
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                            borderRadius: BorderRadius.circular(18),
                            border: Border.all(
                              color: Colors.white.withValues(alpha: 0.35),
                              width: 1.2,
                            ),
                            boxShadow: [
                              BoxShadow(
                                color: t.gradient.first.withValues(alpha: 0.28),
                                blurRadius: 8,
                                offset: const Offset(0, 3),
                              ),
                            ],
                          ),
                          child: Center(
                            child: Icon(
                              t.icon,
                              color: Colors.white,
                              size: 27,
                            ),
                          ),
                        ),
                        if (t.tag.isNotEmpty)
                          Positioned(
                            top: -4,
                            right: -6,
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                              decoration: BoxDecoration(
                                color: t.tagBg,
                                borderRadius: BorderRadius.circular(6),
                                border: Border.all(color: Colors.white, width: 1),
                                boxShadow: const [
                                  BoxShadow(
                                    color: Colors.black26,
                                    blurRadius: 3,
                                    offset: Offset(0, 1),
                                  ),
                                ],
                              ),
                              child: Text(
                                t.tag,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 8,
                                  fontWeight: FontWeight.w900,
                                  letterSpacing: 0.2,
                                ),
                              ),
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 7),
                    Text(
                      t.title,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w800,
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
                                      return CachedNetworkImage(
                                        imageUrl: imgUrl,
                                        height: 100,
                                        width: 210,
                                        fit: BoxFit.cover,
                                        placeholder: (_, _) => Container(
                                          height: 100,
                                          width: 210,
                                          color: const Color(0xFFFDE68A),
                                          child: const Center(
                                            child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFFD97706)),
                                          ),
                                        ),
                                        errorWidget: (_, e, s) => Container(
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
