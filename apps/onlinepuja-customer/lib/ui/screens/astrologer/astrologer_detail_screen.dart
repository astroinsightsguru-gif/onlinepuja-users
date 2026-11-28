import 'package:flutter/material.dart';
import 'package:op_shared/op_shared.dart';
import 'package:provider/provider.dart';

import '../../../app.dart';
import '../../../state/app_session.dart';
import '../../theme/customer_theme.dart';
import '../../widgets/customer_widgets.dart';
import '../cosmic_ai_screen.dart';

class AstrologerDetailScreen extends StatefulWidget {
  const AstrologerDetailScreen({super.key, required this.id});

  static const route = '/astrologer';

  final int id;

  @override
  State<AstrologerDetailScreen> createState() => _AstrologerDetailScreenState();
}

class _AstrologerDetailScreenState extends State<AstrologerDetailScreen> {
  Astrologer? _astro;
  Object? _error;
  bool _following = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _error = null);
    try {
      final session = context.read<AppSession>();
      final a = await AstrologerApi.instance
          .byId(astrologerId: widget.id, userId: session.user?.id);
      if (!mounted) return;
      setState(() {
        _astro = a;
        _following = a.isFollow;
      });
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  Future<void> _toggleFollow() async {
    final session = context.read<AppSession>();
    final uid = session.user?.id;
    if (uid == null || _astro == null) return;
    final next = !_following;
    setState(() => _following = next);
    try {
      await AstrologerApi.instance.setFollow(
        userId: uid,
        astrologerId: _astro!.id ?? 0,
        follow: next,
      );
    } catch (_) {
      if (mounted) setState(() => _following = !next);
    }
  }

  static const Map<int, Map<String, String>> _astroHindiMeta = {
    1: {'name': 'पं. शशि भूषण त्रिपाठी जी', 'skill': 'वैदिक ज्योतिष • कर्मकांड • दशा विचार'},
    2: {'name': 'पं. अंकित शास्त्री', 'skill': 'वैदिक कुंडली • मुहूर्त • ग्रह शांति'},
    3: {'name': 'महंत राम दास त्यागी जी महाराज', 'skill': 'अध्यात्म • वैदिक ज्योतिष • भागवत'},
    5: {'name': 'परमहंसाचार्य दास', 'skill': 'वैदिक ज्योतिष • साधना • मंत्र रहस्य'},
    6: {'name': 'आचार्य सोमनाथ शास्त्री', 'skill': 'वैदिक ज्योतिष • कुंडली • ग्रह शांति'},
    7: {'name': 'पं. राधाकांत शर्मा', 'skill': 'प्रश्न कुंडली • मुहूर्त • वैदिक ज्योतिष'},
    9: {'name': 'आचार्य विक्रम शास्त्री', 'skill': 'वैदिक ज्योतिष • कुंडली • वास्तु शास्त्र'},
    11: {'name': 'आचार्य देवव्रत जी', 'skill': 'ज्योतिष • हस्तरेखा • मुहूर्त विचार'},
    12: {'name': 'आचार्य रामानंद शास्त्री', 'skill': 'वैदिक ज्योतिष • फलित • अंक विद्या'},
    13: {'name': 'आचार्य सुनील पाठक', 'skill': 'वैदिक ज्योतिष • कर्मकांड • कालसर्प'},
    14: {'name': 'साकेत धाम आश्रम ज्योतिषाचार्य', 'skill': 'वैदिक अनुष्ठान • कुंडली विश्लेषण'},
    15: {'name': 'आचार्य प्रांजल द्विवेदी', 'skill': 'वैदिक ज्योतिष • हस्तरेखा • कर्मकांड'},
    16: {'name': 'पं. महेश्वर दीक्षित', 'skill': 'वैदिक ज्योतिष • प्रश्न कुंडली • वास्तु'},
  };

  @override
  Widget build(BuildContext context) {
    final session = context.watch<AppSession>();
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final isHi = LocaleManager.instance.currentLanguage.value != AppLanguage.en;
    final a = _astro;
    final hiMeta = (isHi && a != null) ? _astroHindiMeta[a.id] : null;
    final displayName = hiMeta?['name'] ?? a?.name ?? (isHi ? 'वैदिक ज्योतिषाचार्य' : 'Astrologer Sanctum');

    return Scaffold(
      appBar: AppBar(
        title: Text(displayName),
        actions: [
          if (a != null)
            IconButton(
              icon: Icon(
                _following ? Icons.favorite_rounded : Icons.favorite_border_rounded,
                color: _following ? CustomerTheme.brandCrimson : null,
              ),
              onPressed: _toggleFollow,
            ),
        ],
      ),
      body: Container(
        decoration: BoxDecoration(
          gradient: isDark
              ? CustomerTheme.cosmicDarkGradient
              : const LinearGradient(
                  colors: [
                    Color(0xFFFFFBEB),
                    Color(0xFFFAF7F2),
                  ],
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                ),
        ),
        child: _error != null
            ? StatusViews.error(context, _error!, onRetry: _load)
            : a == null
                ? StatusViews.loading(context)
                : ListView(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                    children: [
                      // 1. Astrologer Profile Hero Card
                      _profileHeroCard(context, a, isDark, isHi, hiMeta),
                      const SizedBox(height: 14),

                      // 2. Stats Strip
                      _statsStrip(context, a, isDark, isHi),
                      const SizedBox(height: 16),

                      // 3. Specialties & Skills
                      _specialtiesCard(context, a, isDark, isHi),
                      const SizedBox(height: 16),

                      // 4. Vedic Bio & Experience
                      if (a.loginBio.isNotEmpty) ...[
                        _bioCard(context, a, isDark, isHi),
                        const SizedBox(height: 16),
                      ],

                      // 5. Consultation Options & Live Actions
                      _consultationOptions(context, a, session, isDark, isHi, displayName),
                      const SizedBox(height: 32),
                    ],
                  ),
      ),
    );
  }

  Widget _profileHeroCard(BuildContext context, Astrologer a, bool isDark, bool isHi, Map<String, String>? hiMeta) {
    final name = hiMeta?['name'] ?? a.name;
    final skill = hiMeta?['skill'] ?? (a.primarySkill.isNotEmpty ? a.primarySkill : (isHi ? 'वैदिक ज्योतिषी' : 'Vedic Astrologer'));

    return SacredCard(
      padding: const EdgeInsets.all(16),
      elevation: 2,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          AstrologerAvatarRing(
            imageUrl: a.imageUrl,
            radius: 38,
            isOnline: a.isChatOnline || a.isCallOnline,
            badgeText: a.isChatOnline || a.isCallOnline ? (isHi ? 'लाइव' : 'LIVE') : null,
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Flexible(
                      child: Text(
                        name,
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w900,
                          letterSpacing: -0.2,
                        ),
                      ),
                    ),
                    const SizedBox(width: 4),
                    const Icon(
                      Icons.verified_rounded,
                      size: 16,
                      color: CustomerTheme.brandGold,
                    ),
                  ],
                ),
                const SizedBox(height: 3),
                Text(
                  skill,
                  style: TextStyle(
                    fontSize: 12.5,
                    fontWeight: FontWeight.w600,
                    color: CustomerTheme.brandSaffron,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  '${isHi ? 'भाषाएं' : 'Languages'}: ${a.languageKnown.isNotEmpty ? a.languageKnown : (isHi ? 'हिंदी, अंग्रेज़ी' : 'Hindi, English')}',
                  style: TextStyle(
                    fontSize: 11.5,
                    color: isDark ? Colors.white60 : Colors.black54,
                  ),
                ),
                const SizedBox(height: 8),
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 2.5,
                      ),
                      decoration: BoxDecoration(
                        color: CustomerTheme.brandGold.withValues(alpha: 0.18),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(
                            Icons.star_rounded,
                            size: 14,
                            color: CustomerTheme.brandDarkGold,
                          ),
                          const SizedBox(width: 3),
                          Text(
                            a.rating > 0 ? a.rating.toStringAsFixed(1) : '4.9',
                            style: const TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w800,
                              color: CustomerTheme.brandDarkGold,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 8),
                    Text(
                      '${a.reviews} ${isHi ? 'समीक्षाएं' : 'Reviews'}',
                      style: TextStyle(
                        fontSize: 11,
                        color: isDark ? Colors.white54 : Colors.black45,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _statsStrip(BuildContext context, Astrologer a, bool isDark, bool isHi) {
    final expStr = '${a.experienceInYears > 0 ? a.experienceInYears : 5}+ ${isHi ? 'वर्ष' : 'Yrs'}';
    final ordersStr = '${a.reviews > 0 ? a.reviews : 480}+';

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: isDark ? CustomerTheme.cosmicCardDark : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isDark
              ? CustomerTheme.brandGold.withValues(alpha: 0.15)
              : CustomerTheme.lightBorder,
        ),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [
          _statItem(expStr, isHi ? 'अनुभव' : 'Experience', Icons.workspace_premium_rounded),
          Container(height: 24, width: 1, color: Colors.grey.withValues(alpha: 0.2)),
          _statItem(ordersStr, isHi ? 'परामर्श' : 'Orders', Icons.phone_in_talk_rounded),
          Container(height: 24, width: 1, color: Colors.grey.withValues(alpha: 0.2)),
          _statItem('₹${a.charge.toStringAsFixed(0)}/${isHi ? 'मि' : 'min'}', isHi ? 'दक्षिणा' : 'Dakshina', Icons.currency_rupee_rounded),
        ],
      ),
    );
  }

  Widget _statItem(String value, String label, IconData icon) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 14, color: CustomerTheme.brandSaffron),
            const SizedBox(width: 4),
            Text(
              value,
              style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 13),
            ),
          ],
        ),
        const SizedBox(height: 2),
        Text(
          label,
          style: const TextStyle(fontSize: 10, color: Colors.grey),
        ),
      ],
    );
  }

  Widget _specialtiesCard(BuildContext context, Astrologer a, bool isDark, bool isHi) {
    final skills = (a.allSkill.isNotEmpty ? a.allSkill : a.primarySkill)
        .split(',')
        .map((s) => s.trim())
        .where((s) => s.isNotEmpty)
        .toList();

    return SacredCard(
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            isHi ? 'वैदिक कौशल एवं विशेषज्ञता' : 'Vedic Specialties & Expertise',
            style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 10),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: skills.map((s) {
              return Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                decoration: BoxDecoration(
                  color: isDark
                      ? CustomerTheme.cosmicCardElevated
                      : const Color(0xFFFFF7ED),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: CustomerTheme.brandGold.withValues(alpha: 0.3),
                  ),
                ),
                child: Text(
                  s,
                  style: TextStyle(
                    fontSize: 11.5,
                    fontWeight: FontWeight.w600,
                    color: isDark ? Colors.white70 : CustomerTheme.brandDarkGold,
                  ),
                ),
              );
            }).toList(),
          ),
        ],
      ),
    );
  }

  Widget _bioCard(BuildContext context, Astrologer a, bool isDark, bool isHi) {
    return SacredCard(
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            isHi ? 'आचार्य जी का परिचय' : 'About Astrologer',
            style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 8),
          Text(
            a.loginBio,
            style: TextStyle(
              fontSize: 12.5,
              height: 1.45,
              color: isDark ? Colors.white70 : const Color(0xFF374151),
            ),
          ),
        ],
      ),
    );
  }

  Widget _consultationOptions(
    BuildContext context,
    Astrologer a,
    AppSession session,
    bool isDark,
    bool isHi,
    String displayName,
  ) {
    final isFree = a.isFreeAvailable && !(session.user?.isFreeChat ?? false);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          isHi ? 'परामर्श आरंभ करें' : 'Start Consultation',
          style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800),
        ),
        const SizedBox(height: 10),
        _actionTile(
          icon: Icons.chat_bubble_rounded,
          title: isHi ? 'चैट परामर्श' : 'Chat Consultation',
          subtitle: a.isChatOnline
              ? (isHi ? 'त्वरित व गोपनीय बातचीत' : 'Instant live chat')
              : (isHi ? 'आचार्य जी अभी ऑफलाइन हैं' : 'Currently offline'),
          price: isFree
              ? (isHi ? 'निःशुल्क' : 'FREE')
              : '₹${a.charge.toStringAsFixed(0)}/${isHi ? 'मि' : 'min'}',
          isFree: isFree,
          isOnline: a.isChatOnline,
          gradient: CustomerTheme.saffronGradient,
          onTap: a.isChatOnline
              ? () => context.openChat(
                    astrologerId: widget.id,
                    astrologerName: displayName,
                    ratePerMinute: a.charge > 0 ? a.charge.toDouble() : 15.0,
                    isFree: isFree,
                  )
              : null,
        ),
        const SizedBox(height: 10),
        _actionTile(
          icon: Icons.phone_rounded,
          title: isHi ? 'ऑडियो कॉल' : 'Audio Call',
          subtitle: a.isCallOnline
              ? (isHi ? 'स्पष्ट व गोपनीय वॉयस कॉल' : 'Private voice call')
              : (isHi ? 'आचार्य जी अभी व्यस्त हैं' : 'Currently busy'),
          price: '₹${a.charge.toStringAsFixed(0)}/${isHi ? 'मि' : 'min'}',
          isFree: false,
          isOnline: a.isCallOnline,
          gradient: CustomerTheme.dakshinaGradient,
          onTap: a.isCallOnline
              ? () => _startConsultationFlow(isVideo: false)
              : null,
        ),
        const SizedBox(height: 10),
        _actionTile(
          icon: Icons.videocam_rounded,
          title: isHi ? 'वीडियो परामर्श' : 'Video Consultation',
          subtitle: a.isCallOnline
              ? (isHi ? 'मुखाकृति व कुंडली दर्शन' : 'Face & Kundli analysis')
              : (isHi ? 'लाइन अभी उपलब्ध नहीं है' : 'Line unavailable'),
          price: '₹${(a.videoCallRate > 0 ? a.videoCallRate : a.charge * 1.5).toStringAsFixed(0)}/${isHi ? 'मि' : 'min'}',
          isFree: false,
          isOnline: a.isCallOnline,
          gradient: const LinearGradient(
            colors: [Color(0xFF8B5CF6), Color(0xFF6D28D9)],
          ),
          onTap: a.isCallOnline
              ? () => _startConsultationFlow(isVideo: true)
              : null,
        ),
        const SizedBox(height: 10),
        _actionTile(
          icon: Icons.auto_awesome_rounded,
          title: isHi ? 'AI अवतार चैट (24/7)' : 'AI Twin Chat (24/7)',
          subtitle: isHi ? 'वैदिक शैली • शून्य प्रतीक्षा' : 'Trained on Jyotish • Instant',
          price: '₹${(a.charge > 0 ? (a.charge * 0.5).clamp(5, 25) : 10).toStringAsFixed(0)}/${isHi ? 'मि' : 'min'}',
          isFree: false,
          isOnline: true,
          gradient: const LinearGradient(
            colors: [Color(0xFF7C3AED), Color(0xFFD97706)],
          ),
          onTap: () => Navigator.of(context).push(
            MaterialPageRoute(
              builder: (_) => OnlinePujaAiScreen(
                initialAstrologerId: widget.id,
                initialAstrologerName: displayName,
                initialAstrologerSkill: a.primarySkill,
                initialAstrologerRate: a.charge > 0 ? a.charge.toDouble() : 10.0,
              ),
            ),
          ),
        ),
      ],
    );
  }

  void _startConsultationFlow({required bool isVideo}) {
    final a = _astro;
    if (a == null) return;
    final isHi = LocaleManager.instance.currentLanguage.value != AppLanguage.en;
    final displayName = a.name.isNotEmpty ? a.name : (isHi ? 'आचार्य जी' : 'Astrologer');
    final rate = isVideo
        ? (a.videoCallRate > 0 ? a.videoCallRate.toDouble() : 25.0)
        : (a.charge > 0 ? a.charge.toDouble() : 15.0);

    final suggestedTopics = [
      isHi ? '💼 नौकरी, पदोन्नति एवं व्यापार में लाभ' : '💼 Career, Job Promotion & Business',
      isHi ? '💍 विवाह समय, वर/वधू चयन व मांगलिक योग' : '💍 Marriage Timing & Compatibility',
      isHi ? '🔱 राहु-केतु दशा, साढ़े साती व गृह शांति' : '🔱 Rahu-Ketu Dasha & Planetary Shanti',
      isHi ? '💰 धन लाभ, अटका हुआ धन एवं कर्ज मुक्ति' : '💰 Financial Growth & Debt Relief',
      isHi ? '🩺 स्वास्थ्य, दीर्घायु एवं महामृत्युंजय संस्तुति' : '🩺 Health, Vitality & Healing Remedies',
    ];

    String selectedTopic = suggestedTopics.first;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setModalState) {
          final isDark = Theme.of(ctx).brightness == Brightness.dark;
          final bg = isDark ? const Color(0xFF1E1B2E) : Colors.white;
          final textPrimary = isDark ? Colors.white : const Color(0xFF1E293B);

          return Container(
            padding: EdgeInsets.only(
              left: 20,
              right: 20,
              top: 20,
              bottom: MediaQuery.of(ctx).viewInsets.bottom + 24,
            ),
            decoration: BoxDecoration(
              color: bg,
              borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.3),
                  blurRadius: 20,
                  offset: const Offset(0, -4),
                ),
              ],
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    width: 44,
                    height: 4,
                    decoration: BoxDecoration(
                      color: Colors.grey.withValues(alpha: 0.4),
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        gradient: CustomerTheme.dakshinaGradient,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Icon(
                        isVideo ? Icons.videocam_rounded : Icons.phone_in_talk_rounded,
                        color: Colors.white,
                        size: 22,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            isHi ? 'परामर्श की मुख्य जिज्ञासा चुनें' : 'Choose Primary Consultation Focus',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w800,
                              color: textPrimary,
                            ),
                          ),
                          Text(
                            '$displayName • ₹${rate.toStringAsFixed(0)}/${isHi ? 'मि' : 'min'}',
                            style: const TextStyle(
                              fontSize: 12,
                              color: Color(0xFFD97706),
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                Text(
                  isHi
                      ? 'आचार्य जी से बातचीत शुरू करने से पूर्व अपना मुख्य प्रश्न चुनें ताकि परामर्श सटीक व फलदायी हो:'
                      : 'Select your core focus so Acharya Ji can prepare your chart instantly:',
                  style: TextStyle(
                    fontSize: 12.5,
                    color: isDark ? Colors.white70 : Colors.black87,
                  ),
                ),
                const SizedBox(height: 12),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: suggestedTopics.map((topic) {
                    final isSelected = selectedTopic == topic;
                    return InkWell(
                      onTap: () => setModalState(() => selectedTopic = topic),
                      borderRadius: BorderRadius.circular(12),
                      child: AnimatedContainer(
                        duration: const Duration(milliseconds: 150),
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        decoration: BoxDecoration(
                          color: isSelected
                              ? const Color(0xFFD97706).withValues(alpha: 0.15)
                              : (isDark ? Colors.white.withValues(alpha: 0.05) : Colors.black.withValues(alpha: 0.04)),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: isSelected ? const Color(0xFFD97706) : Colors.transparent,
                            width: 1.5,
                          ),
                        ),
                        child: Text(
                          topic,
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
                            color: isSelected ? const Color(0xFFD97706) : textPrimary,
                          ),
                        ),
                      ),
                    );
                  }).toList(),
                ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  height: 48,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFFD97706),
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                      elevation: 2,
                    ),
                    onPressed: () {
                      Navigator.pop(ctx);
                      context.openCall(
                        astrologerId: widget.id,
                        astrologerName: displayName,
                        isVideo: isVideo,
                        ratePerMinute: rate,
                      );
                    },
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(isVideo ? Icons.videocam_rounded : Icons.phone_forwarded_rounded, size: 20),
                        const SizedBox(width: 8),
                        Text(
                          isHi ? 'कॉल शुरू करें' : 'Start Call',
                          style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _actionTile({
    required IconData icon,
    required String title,
    required String subtitle,
    required String price,
    required bool isFree,
    required bool isOnline,
    required Gradient gradient,
    required VoidCallback? onTap,
  }) {
    return SacredCard(
      padding: const EdgeInsets.all(14),
      elevation: isOnline ? 2 : 0,
      onTap: onTap,
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              gradient: isOnline ? gradient : null,
              color: isOnline ? null : Colors.grey.shade400,
              shape: BoxShape.circle,
            ),
            child: Icon(icon, color: Colors.white, size: 22),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 14),
                ),
                const SizedBox(height: 2),
                Text(
                  subtitle,
                  style: TextStyle(
                    fontSize: 11,
                    color: isOnline ? Colors.grey : Colors.red.shade400,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: isFree
                      ? CustomerTheme.sacredEmerald
                      : CustomerTheme.brandGold.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  price,
                  style: TextStyle(
                    color: isFree ? Colors.white : CustomerTheme.brandDarkGold,
                    fontWeight: FontWeight.w900,
                    fontSize: 12,
                  ),
                ),
              ),
              const SizedBox(height: 4),
              Text(
                isOnline ? 'TAP TO START' : 'UNAVAILABLE',
                style: TextStyle(
                  fontSize: 9,
                  fontWeight: FontWeight.w800,
                  letterSpacing: 0.4,
                  color: isOnline ? CustomerTheme.brandSaffron : Colors.grey,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
