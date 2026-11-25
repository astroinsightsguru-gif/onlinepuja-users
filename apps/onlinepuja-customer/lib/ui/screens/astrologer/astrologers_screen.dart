import 'package:flutter/material.dart';
import 'package:op_shared/op_shared.dart';
import 'package:provider/provider.dart';

import '../../../app.dart';
import '../../../state/app_session.dart';
import '../../theme/customer_theme.dart';
import '../notifications_screen.dart';
import '../profile/wallet_screen.dart';
import '../cosmic_ai_screen.dart';

/// Clean, Luxury & High-Conversion Astrologer Discovery & Consultation Directory.
class AstrologersScreen extends StatefulWidget {
  const AstrologersScreen({super.key});

  @override
  State<AstrologersScreen> createState() => _AstrologersScreenState();
}

class _AstrologersScreenState extends State<AstrologersScreen> {
  late Future<List<Astrologer>> _future;
  Map<String, dynamic>? _firstConsultOffer;
  String _query = '';
  String _selectedFilter = 'All';
  bool _isGridView = true;
  final TextEditingController _searchController = TextEditingController();

  static const List<String> _filters = [
    'All',
    'Online Now',
    'Vedic',
    'Tarot',
    'Kundli',
    'Love & Relations',
    'Career & Wealth',
    'Marriage',
    'Vastu',
    'Numerology',
  ];

  @override
  void initState() {
    super.initState();
    _future = _load();
    _loadOffer();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadOffer() async {
    try {
      final session = context.read<AppSession>();
      final offer = await MiscApi.instance.checkFirstConsultOffer(userId: session.user?.id);
      if (mounted) setState(() => _firstConsultOffer = offer);
    } catch (_) {}
  }

  static final List<Astrologer> _parallelAiAstrologers = [
    Astrologer(
      id: 41,
      name: 'Acharya Ved Vyas (AI Rishi)',
      email: 'ai@onlinepuja.live',
      primarySkill: 'Vedic Kundli, Dasha & Gochar',
      allSkill: 'Vedic, Kundli, Career & Wealth, Marriage',
      languageKnown: 'Hindi, Sanskrit, English',
      charge: 5,
      experienceInYears: 25,
      rating: 5.0,
      chatStatus: 'Online',
      callStatus: 'Online',
    ),
    Astrologer(
      id: 42,
      name: 'Pt. Hasta Rekha Shastri (AI)',
      email: 'ai@onlinepuja.live',
      primarySkill: 'Samudrika Palmistry & Mounts',
      allSkill: 'Palmistry, Vedic, Career & Wealth, Love & Relations',
      languageKnown: 'Hindi, English',
      charge: 5,
      experienceInYears: 18,
      rating: 4.9,
      chatStatus: 'Online',
      callStatus: 'Online',
    ),
    Astrologer(
      id: 43,
      name: 'Tarot & Prashna Guru (AI)',
      email: 'ai@onlinepuja.live',
      primarySkill: 'Tarot Reading & Horary Prashna',
      allSkill: 'Tarot, Love & Relations, Career & Wealth',
      languageKnown: 'English, Hindi',
      charge: 5,
      experienceInYears: 15,
      rating: 4.9,
      chatStatus: 'Online',
      callStatus: 'Online',
    ),
    Astrologer(
      id: 44,
      name: 'Acharya Anka Vidya (AI)',
      email: 'ai@onlinepuja.live',
      primarySkill: 'Vedic Numerology & Name Correction',
      allSkill: 'Numerology, Career & Wealth',
      languageKnown: 'Hindi, English',
      charge: 5,
      experienceInYears: 20,
      rating: 4.9,
      chatStatus: 'Online',
      callStatus: 'Online',
    ),
    Astrologer(
      id: 45,
      name: 'Vastu Visharad Shastri (AI)',
      email: 'ai@onlinepuja.live',
      primarySkill: 'Vastu Shastra & Sacred Directions',
      allSkill: 'Vastu, Career & Wealth',
      languageKnown: 'Hindi, English',
      charge: 5,
      experienceInYears: 22,
      rating: 4.9,
      chatStatus: 'Online',
      callStatus: 'Online',
    ),
    Astrologer(
      id: 46,
      name: 'Shri Kashi Teerth Purohit (AI)',
      email: 'ai@onlinepuja.live',
      primarySkill: 'Temple Pujas, Hawan & Dosha Nivaran',
      allSkill: 'Vedic, Kundli, Marriage',
      languageKnown: 'Sanskrit, Hindi, English',
      charge: 5,
      experienceInYears: 30,
      rating: 5.0,
      chatStatus: 'Online',
      callStatus: 'Online',
    ),
    Astrologer(
      id: 47,
      name: 'Daivajna Milan Acharya (AI)',
      email: 'ai@onlinepuja.live',
      primarySkill: 'Kundli Gun Milan & Vivah Yoga',
      allSkill: 'Marriage, Love & Relations, Kundli, Vedic',
      languageKnown: 'Hindi, English',
      charge: 5,
      experienceInYears: 21,
      rating: 4.9,
      chatStatus: 'Online',
      callStatus: 'Online',
    ),
    Astrologer(
      id: 48,
      name: 'Lal Kitab Guru (AI)',
      email: 'ai@onlinepuja.live',
      primarySkill: 'Lal Kitab Remedies & Karmic Upay',
      allSkill: 'Vedic, Kundli, Career & Wealth',
      languageKnown: 'Hindi, Punjabi, English',
      charge: 5,
      experienceInYears: 19,
      rating: 4.9,
      chatStatus: 'Online',
      callStatus: 'Online',
    ),
    Astrologer(
      id: 49,
      name: 'Ratna Vidya Acharya (AI)',
      email: 'ai@onlinepuja.live',
      primarySkill: 'Gemstones & Nepal Rudraksha',
      allSkill: 'Vedic, Kundli, Career & Wealth',
      languageKnown: 'Hindi, English',
      charge: 5,
      experienceInYears: 17,
      rating: 4.8,
      chatStatus: 'Online',
      callStatus: 'Online',
    ),
    Astrologer(
      id: 50,
      name: 'Ayur-Jyotish Rishi (AI)',
      email: 'ai@onlinepuja.live',
      primarySkill: 'Medical Astrology & Healing Mantras',
      allSkill: 'Vedic, Kundli',
      languageKnown: 'Sanskrit, Hindi, English',
      charge: 5,
      experienceInYears: 24,
      rating: 4.9,
      chatStatus: 'Online',
      callStatus: 'Online',
    ),
  ];

  Future<List<Astrologer>> _load() async {
    final session = context.read<AppSession>();
    try {
      final list = await AstrologerApi.instance.list(
        userId: session.user?.id,
        sortBy: 'rating',
      );
      final humans = list.where((a) => !a.isBlock).toList();
      final existingIds = humans.map((a) => a.id).toSet();
      final aiToMerge = _parallelAiAstrologers.where((ai) => !existingIds.contains(ai.id)).toList();
      return [...humans, ...aiToMerge];
    } catch (_) {
      return _parallelAiAstrologers;
    }
  }

  void _reload() {
    setState(() => _future = _load());
    _loadOffer();
  }

  static const Map<String, String> _filterHiLabels = {
    'All': 'सभी',
    'Online Now': 'अभी ऑनलाइन',
    'Vedic': 'वैदिक',
    'Tarot': 'टैरो',
    'Kundli': 'कुंडली',
    'Love & Relations': 'प्रेम व संबंध',
    'Career & Wealth': 'करियर व धन',
    'Marriage': 'विवाह योग',
    'Vastu': 'वास्तु',
    'Numerology': 'अंक ज्योतिष',
  };

  static const Map<int, Map<String, String>> _aiHindiMeta = {
    41: {
      'name': 'आचार्य वेद व्यास (AI ऋषि)',
      'skill': 'वैदिक कुंडली, दशा एवं गोचर',
      'lang': 'हिंदी, संस्कृत, अंग्रेज़ी',
    },
    42: {
      'name': 'पं. हस्त रेखा शास्त्री (AI)',
      'skill': 'सामुद्रिक हस्तरेखा एवं पर्वत विश्लेषण',
      'lang': 'हिंदी, अंग्रेज़ी',
    },
    43: {
      'name': 'टैरो एवं प्रश्न गुरु (AI)',
      'skill': 'टैरो कार्ड एवं प्रश्न कुंडली',
      'lang': 'हिंदी, अंग्रेज़ी',
    },
    44: {
      'name': 'आचार्य अंक विद्या (AI)',
      'skill': 'वैदिक अंक ज्योतिष एवं नाम सुधार',
      'lang': 'हिंदी, अंग्रेज़ी',
    },
    45: {
      'name': 'वास्तु विशारद शास्त्री (AI)',
      'skill': 'वास्तु शास्त्र एवं दिशा दोष निवारण',
      'lang': 'हिंदी, अंग्रेज़ी',
    },
    46: {
      'name': 'श्री काशी तीर्थ पुरोहित (AI)',
      'skill': 'मंदिर पूजा, हवन एवं दोष निवारण',
      'lang': 'संस्कृत, हिंदी, अंग्रेज़ी',
    },
    47: {
      'name': 'दैवज्ञ मिलन आचार्य (AI)',
      'skill': 'कुंडली गुण मिलान एवं विवाह योग',
      'lang': 'हिंदी, अंग्रेज़ी',
    },
    48: {
      'name': 'लाल किताब गुरु (AI)',
      'skill': 'लाल किताब अचूक उपाय एवं कर्म सुधार',
      'lang': 'हिंदी, पंजाबी, अंग्रेज़ी',
    },
    49: {
      'name': 'रत्न विद्या आचार्य (AI)',
      'skill': 'राशि रत्न एवं नेपाल रुद्राक्ष परामर्श',
      'lang': 'हिंदी, अंग्रेज़ी',
    },
    50: {
      'name': 'आयुर-ज्योतिष ऋषि (AI)',
      'skill': 'स्वास्थ्य ज्योतिष एवं आरोग्य मंत्र',
      'lang': 'संस्कृत, हिंदी, अंग्रेज़ी',
    },
  };

  @override
  Widget build(BuildContext context) {
    final session = context.watch<AppSession>();
    final currency = session.flags.currency.isNotEmpty ? session.flags.currency : '₹';
    final wallet = session.user?.walletAmount ?? 0.0;
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return ValueListenableBuilder<AppLanguage>(
      valueListenable: LocaleManager.instance.currentLanguage,
      builder: (context, lang, _) {
        final isHi = lang != AppLanguage.en;
        return Scaffold(
          backgroundColor: isDark ? const Color(0xFF0F0C1B) : const Color(0xFFF8FAFC),
          appBar: AppBar(
            backgroundColor: isDark ? const Color(0xFF161224) : Colors.white,
            elevation: 0.5,
            titleSpacing: 16,
            title: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Text(
                      'OnlinePuja',
                      style: TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.w900,
                        letterSpacing: -0.3,
                        color: isDark ? Colors.white : const Color(0xFF1E293B),
                      ),
                    ),
                    const Text(
                      '.live',
                      style: TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.w900,
                        letterSpacing: -0.3,
                        color: Color(0xFFD97706),
                      ),
                    ),
                    const SizedBox(width: 6),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1.5),
                      decoration: BoxDecoration(
                        color: const Color(0xFF10B981).withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        isHi ? 'प्रमाणित' : 'VERIFIED',
                        style: const TextStyle(
                          fontSize: 8.5,
                          fontWeight: FontWeight.w900,
                          color: Color(0xFF10B981),
                          letterSpacing: 0.5,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 1),
                Text(
                  isHi
                      ? 'प्रमाणित वैदिक आचार्य एवं ज्योतिषी'
                      : 'Certified Vedic Acharyas & Astrologers',
                  style: TextStyle(
                    fontSize: 11,
                    color: isDark ? Colors.white60 : const Color(0xFF64748B),
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
            actions: [
              // 1-Tap Hindi / English Switch
              GestureDetector(
                onTap: () => LocaleManager.instance.setLanguage(
                  isHi ? AppLanguage.en : AppLanguage.hi,
                ),
                child: Container(
                  margin: const EdgeInsets.symmetric(vertical: 11, horizontal: 4),
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
                  child: Text(
                    isHi ? 'हिंदी' : 'EN',
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w900,
                      color: isHi
                          ? const Color(0xFFD97706)
                          : (isDark ? Colors.white70 : const Color(0xFF334155)),
                    ),
                  ),
                ),
              ),
              // Wallet Balance Chip
              GestureDetector(
                onTap: () => Navigator.of(context)
                    .pushNamed(WalletScreen.route)
                    .then((_) => session.refreshUser()),
                child: Container(
                  margin: const EdgeInsets.symmetric(vertical: 10, horizontal: 4),
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: const Color(0xFF047857),
                    borderRadius: BorderRadius.circular(16),
                    boxShadow: [
                      BoxShadow(
                        color: const Color(0xFF047857).withValues(alpha: 0.25),
                        blurRadius: 4,
                      ),
                    ],
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.account_balance_wallet_rounded, size: 13, color: Colors.white),
                      const SizedBox(width: 4),
                      Text(
                        '$currency${wallet.toStringAsFixed(0)}',
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.w800,
                          fontSize: 12.5,
                        ),
                      ),
                      const SizedBox(width: 4),
                      const Text('+', style: TextStyle(color: Colors.white70, fontSize: 13, fontWeight: FontWeight.bold)),
                    ],
                  ),
                ),
              ),
              IconButton(
                tooltip: isHi ? 'सूचनाएं' : 'Notifications',
                icon: const Icon(Icons.notifications_none_rounded),
                onPressed: () => Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => const NotificationsScreen()),
                ),
              ),
              const SizedBox(width: 4),
            ],
          ),
          body: RefreshIndicator(
            onRefresh: () async => _reload(),
            child: CustomScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              slivers: [
                // 1. Promotional ₹1 Banner (Clean Ribbon)
                if (_firstConsultOffer?['eligible'] == true)
                  SliverToBoxAdapter(
                    child: _firstConsultRibbon(context, isHi),
                  ),

                // 2. Search Field
                SliverToBoxAdapter(
                  child: _searchBar(isDark, isHi),
                ),

                // 2.5 🤖 Vedic AI Astrologers Swarm Spotlight
                SliverToBoxAdapter(
                  child: _aiAstrologersSpotlight(context, isDark, isHi),
                ),

                // 3. Filter Category Pills
                SliverToBoxAdapter(
                  child: _filterPills(isDark, isHi),
                ),

                // 4. Astrologer Directory List
                FutureBuilder<List<Astrologer>>(
                  future: _future,
                  builder: (context, snap) =>
                      _buildAstrologerList(context, snap, session, currency, isDark, isHi),
                ),

                // Bottom Spacing
                const SliverToBoxAdapter(child: SizedBox(height: 32)),
              ],
            ),
          ),
        );
      },
    );
  }

  /// Compact Promotional Ribbon
  Widget _firstConsultRibbon(BuildContext context, bool isHi) {
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 12, 16, 4),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF78350F), Color(0xFFB45309)],
          begin: Alignment.centerLeft,
          end: Alignment.centerRight,
        ),
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF78350F).withValues(alpha: 0.25),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          const Icon(Icons.stars_rounded, color: Color(0xFFFDE68A), size: 20),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              isHi
                  ? 'प्रथम ज्योतिष परामर्श मात्र ₹1 में • 5 मिनट निःशुल्क मार्गदर्शन'
                  : 'First consultation at ₹1 only • 5 mins free guidance',
              style: const TextStyle(
                color: Color(0xFFFEF3C7),
                fontSize: 12,
                fontWeight: FontWeight.w700,
                letterSpacing: 0.1,
              ),
            ),
          ),
          const Icon(Icons.arrow_forward_ios_rounded, color: Color(0xFFFDE68A), size: 12),
        ],
      ),
    );
  }

  /// 🤖 Swarm of 10 Vedic AI Astrologers Spotlight Strip
  Widget _aiAstrologersSpotlight(BuildContext context, bool isDark, bool isHi) {
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 4, 16, 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: isDark
              ? [const Color(0xFF2E1065), const Color(0xFF3B0764)]
              : [const Color(0xFFFFF7ED), const Color(0xFFFEF3C7)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: const Color(0xFFF59E0B).withValues(alpha: 0.5),
          width: 1.2,
        ),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFFF59E0B).withValues(alpha: isDark ? 0.2 : 0.1),
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
                  gradient: LinearGradient(
                    colors: [Color(0xFFF59E0B), Color(0xFFD97706)],
                  ),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.auto_awesome_rounded, color: Colors.white, size: 14),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Flexible(
                          child: Text(
                            isHi ? '10 वैदिक AI आचार्य उपलब्ध' : '10 Vedic AI Astrologers Available',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w900,
                              color: isDark ? const Color(0xFFFEF3C7) : const Color(0xFF92400E),
                            ),
                          ),
                        ),
                        const SizedBox(width: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                          decoration: BoxDecoration(
                            color: const Color(0xFF10B981),
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: Text(
                            isHi ? '24/7 लाइव' : '24/7 LIVE',
                            style: const TextStyle(fontSize: 8, fontWeight: FontWeight.bold, color: Colors.white),
                          ),
                        ),
                      ],
                    ),
                    Text(
                      isHi
                          ? 'वैदिक कुंडली • हस्तरेखा • टैरो • वास्तु • ₹5/मिनट'
                          : 'Vedic Kundli • Palmistry • Tarot • Vastu • Numerology • ₹5/min',
                      style: TextStyle(
                        fontSize: 10.5,
                        color: isDark ? Colors.white70 : const Color(0xFF78350F),
                      ),
                    ),
                  ],
                ),
              ),
              Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  OutlinedButton(
                    style: OutlinedButton.styleFrom(
                      foregroundColor: const Color(0xFFD97706),
                      side: const BorderSide(color: Color(0xFFD97706)),
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
                      visualDensity: VisualDensity.compact,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    onPressed: () => Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => const OnlinePujaAiScreen(startInCallMode: false)),
                    ),
                    child: Text(
                      isHi ? 'AI चैट' : 'Chat AI',
                      style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold),
                    ),
                  ),
                  const SizedBox(width: 6),
                  ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFFD97706),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
                      visualDensity: VisualDensity.compact,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    onPressed: () => Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => const OnlinePujaAiScreen(startInCallMode: true)),
                    ),
                    icon: const Icon(Icons.phone_in_talk_rounded, size: 12),
                    label: Text(
                      isHi ? 'AI कॉल' : 'Call AI',
                      style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }

  /// Clean Search Field
  Widget _searchBar(bool isDark, bool isHi) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
      child: Container(
        decoration: BoxDecoration(
          color: isDark ? const Color(0xFF1E1830) : Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(
            color: isDark ? Colors.white12 : const Color(0xFFE2E8F0),
            width: 1,
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: isDark ? 0.2 : 0.03),
              blurRadius: 6,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: TextField(
          controller: _searchController,
          onChanged: (v) => setState(() => _query = v),
          style: TextStyle(
            fontSize: 13.5,
            color: isDark ? Colors.white : const Color(0xFF1E293B),
          ),
          decoration: InputDecoration(
            hintText: isHi
                ? 'नाम, वैदिक कौशल या भाषा से ज्योतिषी खोजें…'
                : 'Search astrologers by name, Vedic skill or language…',
            hintStyle: TextStyle(
              fontSize: 12.5,
              color: isDark ? Colors.white38 : const Color(0xFF94A3B8),
            ),
            prefixIcon: Icon(
              Icons.search_rounded,
              size: 20,
              color: isDark ? Colors.white54 : const Color(0xFF64748B),
            ),
            suffixIcon: _query.isNotEmpty
                ? IconButton(
                    icon: const Icon(Icons.clear, size: 18),
                    onPressed: () {
                      _searchController.clear();
                      setState(() => _query = '');
                    },
                  )
                : null,
            border: InputBorder.none,
            contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          ),
        ),
      ),
    );
  }

  /// Clean Horizontal Filter Pills & View Switcher Bar
  Widget _filterPills(bool isDark, bool isHi) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          height: 38,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            physics: const BouncingScrollPhysics(),
            padding: const EdgeInsets.symmetric(horizontal: 16),
            itemCount: _filters.length,
            separatorBuilder: (_, _) => const SizedBox(width: 8),
            itemBuilder: (context, i) {
              final f = _filters[i];
              final isSelected = _selectedFilter == f;
              final isOnlinePill = f == 'Online Now';
              final label = isHi ? (_filterHiLabels[f] ?? f) : f;

              return GestureDetector(
                onTap: () => setState(() => _selectedFilter = f),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 180),
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                  decoration: BoxDecoration(
                    color: isSelected
                        ? CustomerTheme.brandSaffron
                        : (isDark ? const Color(0xFF1E1830) : Colors.white),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(
                      color: isSelected
                          ? CustomerTheme.brandSaffron
                          : (isDark ? Colors.white12 : const Color(0xFFE2E8F0)),
                      width: 1,
                    ),
                    boxShadow: isSelected
                        ? [
                            BoxShadow(
                              color: CustomerTheme.brandSaffron.withValues(alpha: 0.3),
                              blurRadius: 6,
                              offset: const Offset(0, 2),
                            ),
                          ]
                        : null,
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      if (isOnlinePill) ...[
                        Container(
                          width: 7,
                          height: 7,
                          margin: const EdgeInsets.only(right: 6),
                          decoration: const BoxDecoration(
                            color: Color(0xFF10B981),
                            shape: BoxShape.circle,
                          ),
                        ),
                      ],
                      Text(
                        label,
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
                          color: isSelected
                              ? Colors.white
                              : (isDark ? Colors.white70 : const Color(0xFF475569)),
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
        ),
        const SizedBox(height: 10),

        // Section Title & View Switcher Strip (Grid vs List)
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    width: 3.5,
                    height: 14,
                    decoration: BoxDecoration(
                      color: CustomerTheme.brandSaffron,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                  const SizedBox(width: 7),
                  Text(
                    isHi ? 'प्रमाणित वैदिक ज्योतिषी' : 'Verified Vedic Astrologers',
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w800,
                      color: isDark ? Colors.white : const Color(0xFF1E293B),
                    ),
                  ),
                ],
              ),
              // View Toggle Chip
              Container(
                decoration: BoxDecoration(
                  color: isDark ? const Color(0xFF1E1830) : const Color(0xFFF1F5F9),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(
                    color: isDark ? Colors.white12 : const Color(0xFFE2E8F0),
                    width: 0.8,
                  ),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    InkWell(
                      onTap: () => setState(() => _isGridView = true),
                      borderRadius: BorderRadius.circular(8),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: _isGridView ? CustomerTheme.brandSaffron : Colors.transparent,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Row(
                          children: [
                            Icon(
                              Icons.grid_view_rounded,
                              size: 13,
                              color: _isGridView
                                  ? Colors.white
                                  : (isDark ? Colors.white60 : const Color(0xFF64748B)),
                            ),
                            const SizedBox(width: 3),
                            Text(
                              isHi ? 'ग्रिड' : 'Grid',
                              style: TextStyle(
                                fontSize: 10.5,
                                fontWeight: FontWeight.w800,
                                color: _isGridView
                                    ? Colors.white
                                    : (isDark ? Colors.white60 : const Color(0xFF64748B)),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    InkWell(
                      onTap: () => setState(() => _isGridView = false),
                      borderRadius: BorderRadius.circular(8),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: !_isGridView ? CustomerTheme.brandSaffron : Colors.transparent,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Row(
                          children: [
                            Icon(
                              Icons.view_agenda_rounded,
                              size: 13,
                              color: !_isGridView
                                  ? Colors.white
                                  : (isDark ? Colors.white60 : const Color(0xFF64748B)),
                            ),
                            const SizedBox(width: 3),
                            Text(
                              isHi ? 'सूची' : 'List',
                              style: TextStyle(
                                fontSize: 10.5,
                                fontWeight: FontWeight.w800,
                                color: !_isGridView
                                    ? Colors.white
                                    : (isDark ? Colors.white60 : const Color(0xFF64748B)),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 8),
      ],
    );
  }

  /// Astrologer Directory Cards List
  Widget _buildAstrologerList(
    BuildContext context,
    AsyncSnapshot<List<Astrologer>> snap,
    AppSession session,
    String currency,
    bool isDark,
    bool isHi,
  ) {
    if (snap.connectionState == ConnectionState.waiting) {
      return const SliverToBoxAdapter(
        child: Padding(
          padding: EdgeInsets.symmetric(vertical: 60),
          child: Center(
            child: CircularProgressIndicator(color: CustomerTheme.brandSaffron),
          ),
        ),
      );
    }

    if (snap.hasError) {
      return SliverToBoxAdapter(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 40),
          child: StatusViews.error(context, snap.error!, onRetry: _reload),
        ),
      );
    }

    final all = snap.data ?? const <Astrologer>[];
    var list = all;

    // Search query filter
    final q = _query.trim().toLowerCase();
    if (q.isNotEmpty) {
      list = list.where((a) {
        final hiMeta = _aiHindiMeta[a.id];
        return a.name.toLowerCase().contains(q) ||
            a.primarySkill.toLowerCase().contains(q) ||
            a.allSkill.toLowerCase().contains(q) ||
            a.languageKnown.toLowerCase().contains(q) ||
            (hiMeta?['name']?.contains(q) ?? false) ||
            (hiMeta?['skill']?.contains(q) ?? false);
      }).toList();
    }

    // Category / availability filter
    if (_selectedFilter == 'Online Now') {
      list = list.where((a) => a.isChatOnline || a.isCallOnline || a.email == 'ai@onlinepuja.live').toList();
    } else if (_selectedFilter != 'All') {
      final s = _selectedFilter.toLowerCase();
      list = list.where((a) {
        return a.primarySkill.toLowerCase().contains(s) || a.allSkill.toLowerCase().contains(s);
      }).toList();
    }

    if (list.isEmpty) {
      return SliverToBoxAdapter(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 60),
          child: Center(
            child: Column(
              children: [
                Icon(
                  Icons.search_off_rounded,
                  size: 48,
                  color: isDark ? Colors.white38 : const Color(0xFF94A3B8),
                ),
                const SizedBox(height: 14),
                Text(
                  isHi ? 'कोई ज्योतिषी नहीं मिले' : 'No Astrologers Found',
                  style: TextStyle(
                    fontWeight: FontWeight.w800,
                    fontSize: 16,
                    color: isDark ? Colors.white : const Color(0xFF1E293B),
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  isHi
                      ? 'कृपया फ़िल्टर रीसेट करें या किसी अन्य वैदिक कौशल की खोज करें।'
                      : 'Try resetting filters or searching for another Vedic skill.',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    color: isDark ? Colors.white60 : const Color(0xFF64748B),
                    fontSize: 12.5,
                  ),
                ),
                const SizedBox(height: 16),
                OutlinedButton(
                  onPressed: () {
                    setState(() {
                      _query = '';
                      _selectedFilter = 'All';
                      _searchController.clear();
                    });
                  },
                  child: Text(isHi ? 'फ़िल्टर रीसेट करें' : 'Reset All Filters'),
                ),
              ],
            ),
          ),
        ),
      );
    }

    final isEligible = _firstConsultOffer?['eligible'] == true;

    if (_isGridView) {
      return SliverPadding(
        padding: const EdgeInsets.symmetric(horizontal: 14),
        sliver: SliverGrid(
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 2,
            childAspectRatio: 0.74,
            crossAxisSpacing: 10,
            mainAxisSpacing: 12,
          ),
          delegate: SliverChildBuilderDelegate(
            (context, i) => _buildAstrologerGridCard(
              context,
              list[i],
              session,
              currency,
              isEligible,
              isDark,
              isHi,
            ),
            childCount: list.length,
          ),
        ),
      );
    }

    return SliverPadding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      sliver: SliverList.separated(
        itemCount: list.length,
        separatorBuilder: (_, _) => const SizedBox(height: 12),
        itemBuilder: (context, i) {
          final a = list[i];
          return _cleanAstrologerCard(context, a, session, currency, isEligible, isDark, isHi);
        },
      ),
    );
  }

  /// Sacred Vedic 2-Column Grid Card (Optimized for High Conversion & Zero Overflow)
  Widget _buildAstrologerGridCard(
    BuildContext context,
    Astrologer a,
    AppSession session,
    String currency,
    bool isEligible,
    bool isDark,
    bool isHi,
  ) {
    final isAiAcharya = a.email == 'ai@onlinepuja.live';
    final isOnline = isAiAcharya || a.isChatOnline || a.isCallOnline;
    final hiMeta = isHi ? _aiHindiMeta[a.id] : null;
    final displayName = hiMeta?['name'] ?? a.name;
    final displaySkill = hiMeta?['skill'] ??
        (a.primarySkill.isNotEmpty
            ? a.primarySkill
            : (isHi ? 'वैदिक ज्योतिषी' : 'Vedic Astrologer'));
    final displayLang = hiMeta?['lang'] ??
        (a.languageKnown.isNotEmpty
            ? a.languageKnown
            : (isHi ? 'हिंदी, अंग्रेज़ी' : 'Hindi, English'));
    final rate = a.charge > 0
        ? '$currency${a.charge.toInt()}/${isHi ? 'मि' : 'm'}'
        : (isHi ? 'निःशुल्क' : 'FREE');

    final ordersCount = a.totalOrder > 0
        ? a.totalOrder
        : ((a.experienceInYears > 0 ? a.experienceInYears : 5) * 480);
    final ordersStr = ordersCount >= 1000
        ? '${(ordersCount / 1000).toStringAsFixed(1)}k'
        : '$ordersCount';
    final expStr = '${a.experienceInYears > 0 ? a.experienceInYears : 5}+ ${isHi ? 'वर्ष' : 'yrs'}';
    final consultsLabel = isHi ? '$ordersStr परामर्श' : '$ordersStr orders';

    return Container(
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF181326) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isAiAcharya
              ? const Color(0xFFF59E0B).withValues(alpha: 0.6)
              : (isDark ? const Color(0xFF2E2448) : const Color(0xFFE2E8F0)),
          width: isAiAcharya ? 1.4 : 1,
        ),
        boxShadow: [
          BoxShadow(
            color: isAiAcharya
                ? const Color(0xFFF59E0B).withValues(alpha: isDark ? 0.2 : 0.08)
                : Colors.black.withValues(alpha: isDark ? 0.35 : 0.05),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(16),
          onTap: isAiAcharya
              ? () => Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => OnlinePujaAiScreen(
                        initialAiAstrologerId: a.id,
                        startInCallMode: false,
                      ),
                    ),
                  )
              : () => context.openAstrologer(a.id ?? 0).then((_) => _reload()),
          child: Padding(
            padding: const EdgeInsets.fromLTRB(8, 8, 8, 8),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                // 1. Top Badges Row: Rating (Left) & Offer/Status (Right)
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    // Rating Pill
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                      decoration: BoxDecoration(
                        color: isDark ? const Color(0xFF2A2038) : const Color(0xFFFEF3C7),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.star_rounded, size: 11.5, color: Color(0xFFF59E0B)),
                          const SizedBox(width: 2),
                          Text(
                            a.rating > 0 ? a.rating.toStringAsFixed(1) : '4.9',
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w900,
                              color: isDark ? const Color(0xFFFDE68A) : const Color(0xFF92400E),
                            ),
                          ),
                        ],
                      ),
                    ),

                    // Status / Offer Badge
                    if (isEligible && a.charge > 0)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(
                            colors: [Color(0xFFDC2626), Color(0xFFEA580C)],
                          ),
                          borderRadius: BorderRadius.circular(5),
                        ),
                        child: Text(
                          isHi ? '₹1 ऑफर' : '₹1 OFFER',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 8.5,
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                      )
                    else if (isAiAcharya)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                        decoration: BoxDecoration(
                          color: const Color(0xFF7C3AED),
                          borderRadius: BorderRadius.circular(5),
                        ),
                        child: const Text(
                          'AI 24/7',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 8.5,
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                      )
                    else
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Container(
                            width: 6,
                            height: 6,
                            decoration: BoxDecoration(
                              color: isOnline ? const Color(0xFF10B981) : const Color(0xFF94A3B8),
                              shape: BoxShape.circle,
                            ),
                          ),
                          const SizedBox(width: 3),
                          Text(
                            isOnline ? (isHi ? 'ऑनलाइन' : 'Online') : (isHi ? 'ऑफलाइन' : 'Offline'),
                            style: TextStyle(
                              fontSize: 8.5,
                              fontWeight: FontWeight.w700,
                              color: isOnline
                                  ? const Color(0xFF10B981)
                                  : (isDark ? Colors.white38 : const Color(0xFF94A3B8)),
                            ),
                          ),
                        ],
                      ),
                  ],
                ),
                const SizedBox(height: 5),

                // 2. Avatar with Status Ring & Verified Tick
                Stack(
                  alignment: Alignment.center,
                  clipBehavior: Clip.none,
                  children: [
                    Container(
                      width: 52,
                      height: 52,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        border: Border.all(
                          color: isOnline
                              ? const Color(0xFF10B981)
                              : (isDark ? const Color(0xFF334155) : const Color(0xFFE2E8F0)),
                          width: 2,
                        ),
                        boxShadow: isOnline
                            ? [
                                BoxShadow(
                                  color: const Color(0xFF10B981).withValues(alpha: 0.25),
                                  blurRadius: 5,
                                  spreadRadius: 0.5,
                                ),
                              ]
                            : null,
                      ),
                      child: ClipOval(
                        child: a.imageUrl.isNotEmpty
                            ? Image.network(
                                a.imageUrl,
                                fit: BoxFit.cover,
                                errorBuilder: (_, _, _) => _avatarFallback(a.name, isAi: isAiAcharya, size: 52),
                              )
                            : _avatarFallback(a.name, isAi: isAiAcharya, size: 52),
                      ),
                    ),
                    Positioned(
                      bottom: -1,
                      right: -1,
                      child: Container(
                        padding: const EdgeInsets.all(1.5),
                        decoration: BoxDecoration(
                          color: isDark ? const Color(0xFF181326) : Colors.white,
                          shape: BoxShape.circle,
                        ),
                        child: isAiAcharya
                            ? Container(
                                padding: const EdgeInsets.all(2),
                                decoration: const BoxDecoration(
                                  color: Color(0xFF7C3AED),
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(Icons.auto_awesome_rounded, size: 9, color: Colors.white),
                              )
                            : const Icon(Icons.verified_rounded, size: 13, color: Color(0xFF0284C7)),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 5),

                // 3. Name
                Text(
                  displayName,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 12.5,
                    fontWeight: FontWeight.w900,
                    letterSpacing: -0.1,
                    color: isDark ? Colors.white : const Color(0xFF0F172A),
                  ),
                ),
                const SizedBox(height: 1.5),

                // 4. Primary Vedic Skill
                Text(
                  displaySkill,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 10,
                    color: isDark ? const Color(0xFFCBD5E1) : const Color(0xFF475569),
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const SizedBox(height: 2.5),

                // 5. Experience & Consultations Social Proof Chip
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1.5),
                  decoration: BoxDecoration(
                    color: isDark ? Colors.white.withValues(alpha: 0.06) : const Color(0xFFF1F5F9),
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: Text(
                    '$expStr • $consultsLabel',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 8.5,
                      fontWeight: FontWeight.w600,
                      color: isDark ? const Color(0xFF94A3B8) : const Color(0xFF64748B),
                    ),
                  ),
                ),
                const SizedBox(height: 4),

                // 6. Pricing Strip
                isEligible && a.charge > 0
                    ? Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            isHi ? '₹1/मि' : '₹1/m',
                            style: const TextStyle(
                              fontSize: 12.5,
                              fontWeight: FontWeight.w900,
                              color: Color(0xFFDC2626),
                            ),
                          ),
                          const SizedBox(width: 4),
                          Text(
                            rate,
                            style: TextStyle(
                              fontSize: 9.5,
                              decoration: TextDecoration.lineThrough,
                              color: isDark ? Colors.white38 : const Color(0xFF94A3B8),
                            ),
                          ),
                        ],
                      )
                    : Text(
                        rate,
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w900,
                          color: a.charge > 0
                              ? (isDark ? const Color(0xFF34D399) : const Color(0xFF047857))
                              : const Color(0xFF10B981),
                        ),
                      ),
                const SizedBox(height: 5),

                // 7. Compact Dual Action Buttons: Chat & Call
                Row(
                  children: [
                    // Chat Action
                    Expanded(
                      child: SizedBox(
                        height: 28,
                        child: OutlinedButton(
                          style: OutlinedButton.styleFrom(
                            foregroundColor: (isAiAcharya || a.isChatOnline)
                                ? const Color(0xFF047857)
                                : const Color(0xFF7C3AED),
                            side: BorderSide(
                              color: (isAiAcharya || a.isChatOnline)
                                  ? const Color(0xFF10B981)
                                  : const Color(0xFF7C3AED).withValues(alpha: 0.65),
                              width: 1,
                            ),
                            padding: EdgeInsets.zero,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                          ),
                          onPressed: isAiAcharya
                              ? () => Navigator.of(context).push(
                                    MaterialPageRoute(
                                      builder: (_) => OnlinePujaAiScreen(
                                        initialAiAstrologerId: a.id,
                                        startInCallMode: false,
                                      ),
                                    ),
                                  )
                              : (a.isChatOnline
                                  ? () => context.openChat(
                                        astrologerId: a.id ?? 0,
                                        astrologerName: a.name,
                                        isFree: a.isFreeAvailable,
                                      )
                                  : () => Navigator.of(context).push(
                                        MaterialPageRoute(
                                          builder: (_) => OnlinePujaAiScreen(
                                            initialAstrologerId: a.id ?? 1,
                                            initialAstrologerName: a.name,
                                            initialAstrologerSkill: a.primarySkill,
                                            initialAstrologerRate: a.charge > 0 ? a.charge.toDouble() : 10.0,
                                          ),
                                        ),
                                      )),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(
                                (isAiAcharya || a.isChatOnline)
                                    ? Icons.chat_bubble_outline_rounded
                                    : Icons.auto_awesome_rounded,
                                size: 11,
                              ),
                              const SizedBox(width: 2.5),
                              Text(
                                isHi ? 'चैट' : 'Chat',
                                style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w800),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 5),

                    // Call Action
                    Expanded(
                      child: SizedBox(
                        height: 28,
                        child: FilledButton(
                          style: FilledButton.styleFrom(
                            backgroundColor: (isAiAcharya || a.isCallOnline)
                                ? const Color(0xFFD97706)
                                : (isDark ? const Color(0xFF261E38) : const Color(0xFFE2E8F0)),
                            foregroundColor: (isAiAcharya || a.isCallOnline)
                                ? Colors.white
                                : (isDark ? Colors.white38 : const Color(0xFF64748B)),
                            padding: EdgeInsets.zero,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                            elevation: (isAiAcharya || a.isCallOnline) ? 1 : 0,
                          ),
                          onPressed: isAiAcharya
                              ? () => Navigator.of(context).push(
                                    MaterialPageRoute(
                                      builder: (_) => OnlinePujaAiScreen(
                                        initialAiAstrologerId: a.id,
                                        startInCallMode: true,
                                      ),
                                    ),
                                  )
                              : (a.isCallOnline
                                  ? () => context.openCall(
                                        astrologerId: a.id ?? 0,
                                        astrologerName: a.name,
                                        ratePerMinute: a.charge > 0 ? a.charge.toDouble() : 15.0,
                                      )
                                  : () => Navigator.of(context).push(
                                        MaterialPageRoute(
                                          builder: (_) => OnlinePujaAiScreen(
                                            initialAstrologerId: a.id ?? 1,
                                            initialAstrologerName: a.name,
                                            initialAstrologerSkill: a.primarySkill,
                                            initialAstrologerRate: a.charge > 0 ? a.charge.toDouble() : 10.0,
                                            startInCallMode: true,
                                          ),
                                        ),
                                      )),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Icon(Icons.phone_rounded, size: 11),
                              const SizedBox(width: 2.5),
                              Text(
                                isHi ? 'कॉल' : 'Call',
                                style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w800),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  /// Clean, Modern Astrologer Card
  Widget _cleanAstrologerCard(
    BuildContext context,
    Astrologer a,
    AppSession session,
    String currency,
    bool isEligible,
    bool isDark,
    bool isHi,
  ) {
    final isAiAcharya = a.email == 'ai@onlinepuja.live';
    final isOnline = isAiAcharya || a.isChatOnline || a.isCallOnline;
    final hiMeta = isHi ? _aiHindiMeta[a.id] : null;
    final displayName = hiMeta?['name'] ?? a.name;
    final displaySkill = hiMeta?['skill'] ??
        (a.primarySkill.isNotEmpty
            ? a.primarySkill
            : (isHi ? 'वैदिक ज्योतिषी एवं आचार्य' : 'Vedic Astrologer'));
    final displayLang = hiMeta?['lang'] ??
        (a.languageKnown.isNotEmpty
            ? a.languageKnown
            : (isHi ? 'हिंदी, अंग्रेज़ी' : 'Hindi, English'));
    final rate = a.charge > 0
        ? '$currency${a.charge.toInt()}/${isHi ? 'मिनट' : 'min'}'
        : (isHi ? 'निःशुल्क' : 'FREE');

    return Container(
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1A1429) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isAiAcharya
              ? const Color(0xFFF59E0B).withValues(alpha: 0.55)
              : (isDark ? Colors.white10 : const Color(0xFFE2E8F0)),
          width: isAiAcharya ? 1.3 : 1,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: isDark ? 0.25 : 0.04),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(16),
          onTap: isAiAcharya
              ? () => Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => OnlinePujaAiScreen(
                        initialAiAstrologerId: a.id,
                        startInCallMode: false,
                      ),
                    ),
                  )
              : () => context.openAstrologer(a.id ?? 0).then((_) => _reload()),
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Top Row: Avatar + Details + Rate
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Avatar with Status Ring
                    Stack(
                      children: [
                        Container(
                          width: 66,
                          height: 66,
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(
                              color: isOnline
                                  ? const Color(0xFF10B981)
                                  : (isDark ? Colors.white12 : const Color(0xFFE2E8F0)),
                              width: 2,
                            ),
                          ),
                          child: ClipRRect(
                            borderRadius: BorderRadius.circular(12),
                            child: a.imageUrl.isNotEmpty
                                ? Image.network(
                                    a.imageUrl,
                                    fit: BoxFit.cover,
                                    errorBuilder: (_, _, _) => _avatarFallback(a.name),
                                  )
                                : _avatarFallback(a.name),
                          ),
                        ),
                        if (isOnline)
                          Positioned(
                            bottom: 2,
                            right: 2,
                            child: Container(
                              width: 13,
                              height: 13,
                              decoration: BoxDecoration(
                                color: const Color(0xFF10B981),
                                shape: BoxShape.circle,
                                border: Border.all(
                                  color: isDark ? const Color(0xFF1A1429) : Colors.white,
                                  width: 2,
                                ),
                              ),
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(width: 12),

                    // Information Column
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Flexible(
                                child: Text(
                                  displayName,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: TextStyle(
                                    fontWeight: FontWeight.w800,
                                    fontSize: 15,
                                    color: isDark ? Colors.white : const Color(0xFF0F172A),
                                  ),
                                ),
                              ),
                              const SizedBox(width: 4),
                              if (isAiAcharya)
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFF7C3AED),
                                    borderRadius: BorderRadius.circular(4),
                                  ),
                                  child: const Text(
                                    'AI 24/7',
                                    style: TextStyle(
                                      color: Colors.white,
                                      fontSize: 8.5,
                                      fontWeight: FontWeight.w900,
                                    ),
                                  ),
                                )
                              else
                                const Icon(Icons.verified_rounded, size: 16, color: Color(0xFF0284C7)),
                            ],
                          ),
                          const SizedBox(height: 2),
                          Text(
                            displaySkill,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              fontSize: 12,
                              color: isDark ? Colors.white70 : const Color(0xFF475569),
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            displayLang,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              fontSize: 11,
                              color: isDark ? Colors.white38 : const Color(0xFF94A3B8),
                            ),
                          ),
                          const SizedBox(height: 6),

                          // Rating and Experience Strip
                          Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFFEF3C7),
                                  borderRadius: BorderRadius.circular(5),
                                ),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    const Icon(Icons.star_rounded, size: 13, color: Color(0xFFD97706)),
                                    const SizedBox(width: 2),
                                    Text(
                                      a.rating > 0 ? a.rating.toStringAsFixed(1) : '4.8',
                                      style: const TextStyle(
                                        fontSize: 11,
                                        fontWeight: FontWeight.w800,
                                        color: Color(0xFF92400E),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              const SizedBox(width: 6),
                              Text(
                                isHi
                                    ? '${a.experienceInYears > 0 ? a.experienceInYears : 5}+ वर्ष अनुभव'
                                    : '${a.experienceInYears > 0 ? a.experienceInYears : 5}+ yrs exp',
                                style: TextStyle(
                                  fontSize: 11,
                                  color: isDark ? Colors.white60 : const Color(0xFF64748B),
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),

                    // Price Tag
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        if (isEligible && a.charge > 0) ...[
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                            decoration: BoxDecoration(
                              color: const Color(0xFFFEE2E2),
                              borderRadius: BorderRadius.circular(5),
                            ),
                            child: Text(
                              isHi ? '₹1 ऑफर' : '₹1 OFFER',
                              style: const TextStyle(
                                color: Color(0xFFB91C1C),
                                fontWeight: FontWeight.w900,
                                fontSize: 9,
                              ),
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            isHi ? '₹1/मिनट' : '₹1/min',
                            style: const TextStyle(
                              fontSize: 14.5,
                              fontWeight: FontWeight.w900,
                              color: Color(0xFFDC2626),
                            ),
                          ),
                          Text(
                            rate,
                            style: TextStyle(
                              fontSize: 10.5,
                              decoration: TextDecoration.lineThrough,
                              color: isDark ? Colors.white38 : const Color(0xFF94A3B8),
                            ),
                          ),
                        ] else ...[
                          Text(
                            rate,
                            style: TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w900,
                              color: a.charge > 0 ? const Color(0xFF047857) : const Color(0xFF10B981),
                            ),
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
                const SizedBox(height: 12),

                // Subtle Divider
                Divider(
                  height: 1,
                  color: isDark ? Colors.white10 : const Color(0xFFF1F5F9),
                ),
                const SizedBox(height: 10),

                // Clean Action Buttons: Chat (or AI Twin 24/7 when offline) & Call
                Row(
                  children: [
                    // Chat / AI Twin Action Button
                    Expanded(
                      child: OutlinedButton.icon(
                        style: OutlinedButton.styleFrom(
                          foregroundColor: (isAiAcharya || a.isChatOnline)
                              ? const Color(0xFF047857)
                              : const Color(0xFF7C3AED),
                          side: BorderSide(
                            color: (isAiAcharya || a.isChatOnline)
                                ? const Color(0xFF10B981)
                                : const Color(0xFF7C3AED).withValues(alpha: 0.65),
                            width: 1.2,
                          ),
                          padding: const EdgeInsets.symmetric(vertical: 8),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                        icon: Icon(
                          (isAiAcharya || a.isChatOnline)
                              ? Icons.chat_bubble_outline_rounded
                              : Icons.auto_awesome_rounded,
                          size: 15,
                          color: (isAiAcharya || a.isChatOnline)
                              ? const Color(0xFF047857)
                              : const Color(0xFF7C3AED),
                        ),
                        label: Text(
                          (isAiAcharya || a.isChatOnline)
                              ? (isHi ? 'चैट करें' : 'Chat Now')
                              : (isHi ? 'AI आचार्य चैट' : 'AI Twin 24/7'),
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w800,
                            color: (isAiAcharya || a.isChatOnline)
                                ? const Color(0xFF047857)
                                : const Color(0xFF7C3AED),
                          ),
                        ),
                        onPressed: isAiAcharya
                            ? () => Navigator.of(context).push(
                                  MaterialPageRoute(
                                    builder: (_) => OnlinePujaAiScreen(
                                      initialAiAstrologerId: a.id,
                                      startInCallMode: false,
                                    ),
                                  ),
                                )
                            : (a.isChatOnline
                                ? () => context.openChat(
                                      astrologerId: a.id ?? 0,
                                      astrologerName: a.name,
                                      isFree: a.isFreeAvailable,
                                    )
                                : () => Navigator.of(context).push(
                                      MaterialPageRoute(
                                        builder: (_) => OnlinePujaAiScreen(
                                          initialAstrologerId: a.id ?? 1,
                                          initialAstrologerName: a.name,
                                          initialAstrologerSkill: a.primarySkill,
                                          initialAstrologerRate: a.charge > 0 ? a.charge.toDouble() : 10.0,
                                        ),
                                      ),
                                    )),
                      ),
                    ),
                    const SizedBox(width: 8),

                    // Call Action Button
                    Expanded(
                      child: FilledButton.icon(
                        style: FilledButton.styleFrom(
                          backgroundColor: (isAiAcharya || a.isCallOnline)
                              ? const Color(0xFFD97706)
                              : (isDark ? const Color(0xFF261E38) : const Color(0xFFF1F5F9)),
                          foregroundColor: (isAiAcharya || a.isCallOnline)
                              ? Colors.white
                              : (isDark ? Colors.white38 : const Color(0xFF64748B)),
                          padding: const EdgeInsets.symmetric(vertical: 8),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          elevation: (isAiAcharya || a.isCallOnline) ? 1 : 0,
                        ),
                        icon: Icon(
                          Icons.phone_rounded,
                          size: 15,
                          color: (isAiAcharya || a.isCallOnline) ? Colors.white : const Color(0xFF94A3B8),
                        ),
                        label: Text(
                          (isAiAcharya || a.isCallOnline)
                              ? (isHi ? 'कॉल करें' : 'Call Now')
                              : (isHi ? 'AI आचार्य कॉल' : 'Call AI Twin'),
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w800,
                            color: (isAiAcharya || a.isCallOnline)
                                ? Colors.white
                                : (isDark ? Colors.white70 : const Color(0xFF475569)),
                          ),
                        ),
                        onPressed: isAiAcharya
                            ? () => Navigator.of(context).push(
                                  MaterialPageRoute(
                                    builder: (_) => OnlinePujaAiScreen(
                                      initialAiAstrologerId: a.id,
                                      startInCallMode: true,
                                    ),
                                  ),
                                )
                            : (a.isCallOnline
                                ? () => context.openCall(
                                      astrologerId: a.id ?? 0,
                                      astrologerName: a.name,
                                      ratePerMinute: a.charge > 0 ? a.charge.toDouble() : 15.0,
                                    )
                                : () => Navigator.of(context).push(
                                      MaterialPageRoute(
                                        builder: (_) => OnlinePujaAiScreen(
                                          initialAstrologerId: a.id ?? 1,
                                          initialAstrologerName: a.name,
                                          initialAstrologerSkill: a.primarySkill,
                                          initialAstrologerRate: a.charge > 0 ? a.charge.toDouble() : 10.0,
                                          startInCallMode: true,
                                        ),
                                      ),
                                    )),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _avatarFallback(String name, {bool isAi = false, double size = 52}) {
    final initial = name.isNotEmpty ? name.trim()[0].toUpperCase() : 'ॐ';
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: isAi
              ? const [Color(0xFF8B5CF6), Color(0xFF5B21B6)]
              : const [Color(0xFFF59E0B), Color(0xFFB45309)],
        ),
      ),
      child: Center(
        child: Text(
          initial,
          style: TextStyle(
            fontWeight: FontWeight.w900,
            color: Colors.white,
            fontSize: size * 0.42,
            letterSpacing: 0.5,
          ),
        ),
      ),
    );
  }
}

