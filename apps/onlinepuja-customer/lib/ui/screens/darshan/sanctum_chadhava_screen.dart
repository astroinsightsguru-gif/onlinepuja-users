import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../theme/customer_theme.dart';

/// Data model representing a sacred Temple Sanctum.
class TempleSanctum {
  final String id;
  final String name;
  final String deityTitle;
  final String occasion;
  final String occasionDate;
  final String templesSummary;
  final String fullDescription;
  final int devoteeCount;
  final String imageUrl;
  final String primaryCtaText;
  final List<ChadhavaTier> offerings;

  const TempleSanctum({
    required this.id,
    required this.name,
    required this.deityTitle,
    required this.occasion,
    required this.occasionDate,
    required this.templesSummary,
    required this.fullDescription,
    required this.devoteeCount,
    required this.imageUrl,
    required this.primaryCtaText,
    required this.offerings,
  });
}

/// Offering tier for micro-chadhava
class ChadhavaTier {
  final int amount;
  final String title;
  final String description;
  final String icon;
  final bool isPopular;

  const ChadhavaTier({
    required this.amount,
    required this.title,
    required this.description,
    required this.icon,
    this.isPopular = false,
  });
}

/// Zero-Clutter, Interactive Sacred Sanctum & Chadhava Screen
/// Replicating the gold-standard Sri Mandir Sanctum UX (Image 2).
class SanctumChadhavaScreen extends StatefulWidget {
  static const route = '/sanctum-chadhava';

  const SanctumChadhavaScreen({super.key});

  @override
  State<SanctumChadhavaScreen> createState() => _SanctumChadhavaScreenState();
}

class _SanctumChadhavaScreenState extends State<SanctumChadhavaScreen>
    with TickerProviderStateMixin {
  // Temple Sanctuaries List
  static const List<TempleSanctum> _sanctums = [
    TempleSanctum(
      id: 'bhairav_jayanti',
      name: 'काल भैरव',
      deityTitle: 'काल भैरव जयंती पर भैरव-शनि-राहु की शरण एकसाथ',
      occasion: '॥ काल भैरव जयंती ॥',
      occasionDate: '✦ 1 दिसंबर 2026',
      templesSummary: 'काल भैरव, उज्जैन • राहु पैठानी • हथला शनि मंदिर',
      fullDescription:
          'मार्गशीर्ष कृष्ण पक्ष की अष्टमी को काल भैरव जयंती मनाई जाती है। मान्यता है कि काल भैरव, शनिदेव और राहु की एकसाथ पूजा व चढ़ावा करने से सभी ग्रह दोष, भय, कालसर्प दोष और अकाल मृत्यु के संकट समाप्त होते हैं। ऑनलाइन पूजा के माध्यम से उज्जैन के प्रसिद्ध काल भैरव मंदिर में आपके नाम और गोत्र से पवित्र चढ़ावा अर्पित किया जाएगा।',
      devoteeCount: 813144,
      imageUrl:
          'https://onlinepuja.live/public/storage/brain_media/brain_j7h0s6V8Q8k3kL7L0O_940x627.jpg',
      primaryCtaText: 'सर्व दोष शांति चढ़ावा करें',
      offerings: [
        ChadhavaTier(
          amount: 11,
          title: 'दीपक व पुष्प अर्पण',
          description: 'काल भैरव जी के सम्मुख सरसों के तेल का अखंड दीपक व पुष्प माला',
          icon: '🪔',
        ),
        ChadhavaTier(
          amount: 21,
          title: 'सिंदूर, तेल व काले तिल',
          description: 'शनि व भैरव दोष मुक्ति हेतु विशेष तिल व तेल अभिषेक',
          icon: '🌺',
        ),
        ChadhavaTier(
          amount: 51,
          title: 'सम्पूर्ण कालसर्प व शनि शांति चढ़ावा',
          description: '3 मंदिरों में एकसाथ नारियल, रक्षा सूत्र व विशेष भोग अर्पण',
          icon: '🥥',
          isPopular: true,
        ),
        ChadhavaTier(
          amount: 101,
          title: 'विशेष नाम-गोत्र संकल्प व अभिमंत्रित रक्षा सूत्र',
          description: 'आचार्य द्वारा व्यक्तिगत संकल्प व घर पर पवित्र रक्षा सूत्र प्रेषण',
          icon: '📜',
        ),
      ],
    ),
    TempleSanctum(
      id: 'mahakal_darshan',
      name: 'महाकालेश्वर उज्जैन',
      deityTitle: 'द्वादश ज्योतिर्लिंग महाकाल भस्म आरती व पंचामृत चढ़ावा',
      occasion: '॥ सोमवार विशेष महाकाल दर्शन ॥',
      occasionDate: '✦ आगामी सोमवार',
      templesSummary: 'महाकालेश्वर मंदिर, उज्जैन • शिप्रा तट',
      fullDescription:
          'उज्जैन के राजाधिराज महाकाल के पावन दरबार में रुद्राभिषेक व भस्म आरती का विशेष पुण्य प्राप्त करें। आपके नाम से बिल्वपत्र, भस्म व गंगाजल अर्पित किया जाता है।',
      devoteeCount: 1240500,
      imageUrl:
          'https://onlinepuja.live/public/storage/brain_media/brain_FUrvDLYSVY9DFZJ9gt_940x627.jpg',
      primaryCtaText: 'महाकाल भस्म चढ़ावा करें',
      offerings: [
        ChadhavaTier(
          amount: 21,
          title: 'बिल्वपत्र व गंगाजल अर्पण',
          description: '१०८ पवित्र बिल्वपत्र व महाकाल जलधारा अर्पण',
          icon: '🍃',
        ),
        ChadhavaTier(
          amount: 51,
          title: 'पंचामृत व इत्र अभिषेक',
          description: 'दूध, दही, मधु, घी, शर्करा व सुगंधित इत्र से महाकाल सेवा',
          icon: '🥛',
          isPopular: true,
        ),
        ChadhavaTier(
          amount: 101,
          title: 'महाकाल विशेष भस्म व भोग चढ़ावा',
          description: 'पवित्र भस्म आरती में आहुति व सूखे मेवों का नैवेद्य',
          icon: '🔥',
        ),
      ],
    ),
    TempleSanctum(
      id: 'shani_shingnapur',
      name: 'शनि शिंगणापुर',
      deityTitle: 'शनि साढ़ेसाती व ढैय्या शांति तैलाभिषेक',
      occasion: '॥ शनिवार शनि कृपा दर्शन ॥',
      occasionDate: '✦ आगामी शनिवार',
      templesSummary: 'शनि शिंगणापुर, महाराष्ट्र',
      fullDescription:
          'स्वयंभू शनिदेव के पावन शिला पर सरसों के तेल और नीले अपराजिता पुष्पों का अर्पण करें जिससे व्यापार में हानि और मानसिक तनाव से तुरंत राहत मिलती है।',
      devoteeCount: 654210,
      imageUrl:
          'https://onlinepuja.live/public/storage/brain_media/brain_hNqK8L5mN7r9wQ2Z4V_940x627.jpg',
      primaryCtaText: 'शनि तैलाभिषेक चढ़ावा करें',
      offerings: [
        ChadhavaTier(
          amount: 11,
          title: 'नीले पुष्प व काला तिल',
          description: 'शनिदेव के चरण में अर्पण',
          icon: '🫐',
        ),
        ChadhavaTier(
          amount: 21,
          title: 'सरसों तेल अभिषेक',
          description: 'स्वयंभू शिला पर शुद्ध सरसों का तेल अर्पण',
          icon: '🛢️',
          isPopular: true,
        ),
        ChadhavaTier(
          amount: 51,
          title: 'शनि रक्षा कवच व छाता दान',
          description: 'पूर्ण साढ़ेसाती दोष निवारण',
          icon: '🛡️',
        ),
      ],
    ),
  ];

  late TempleSanctum _currentSanctum;
  bool _isAudioPlaying = true;
  int _punyaCoins = 36;

  // Bell swing animation controllers
  late AnimationController _leftBellController;
  late Animation<double> _leftBellAngle;
  late AnimationController _rightBellController;
  late Animation<double> _rightBellAngle;

  @override
  void initState() {
    super.initState();
    _currentSanctum = _sanctums.first;

    // Left Bell swing setup
    _leftBellController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1200),
    );
    _leftBellAngle = TweenSequence<double>([
      TweenSequenceItem(tween: Tween(begin: 0.0, end: 0.28).chain(CurveTween(curve: Curves.easeOut)), weight: 20),
      TweenSequenceItem(tween: Tween(begin: 0.28, end: -0.22).chain(CurveTween(curve: Curves.easeInOut)), weight: 25),
      TweenSequenceItem(tween: Tween(begin: -0.22, end: 0.14).chain(CurveTween(curve: Curves.easeInOut)), weight: 25),
      TweenSequenceItem(tween: Tween(begin: 0.14, end: -0.06).chain(CurveTween(curve: Curves.easeInOut)), weight: 15),
      TweenSequenceItem(tween: Tween(begin: -0.06, end: 0.0).chain(CurveTween(curve: Curves.easeIn)), weight: 15),
    ]).animate(_leftBellController);

    // Right Bell swing setup
    _rightBellController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1200),
    );
    _rightBellAngle = TweenSequence<double>([
      TweenSequenceItem(tween: Tween(begin: 0.0, end: -0.28).chain(CurveTween(curve: Curves.easeOut)), weight: 20),
      TweenSequenceItem(tween: Tween(begin: -0.28, end: 0.22).chain(CurveTween(curve: Curves.easeInOut)), weight: 25),
      TweenSequenceItem(tween: Tween(begin: 0.22, end: -0.14).chain(CurveTween(curve: Curves.easeInOut)), weight: 25),
      TweenSequenceItem(tween: Tween(begin: -0.14, end: 0.06).chain(CurveTween(curve: Curves.easeInOut)), weight: 15),
      TweenSequenceItem(tween: Tween(begin: 0.06, end: 0.0).chain(CurveTween(curve: Curves.easeIn)), weight: 15),
    ]).animate(_rightBellController);
  }

  @override
  void dispose() {
    _leftBellController.dispose();
    _rightBellController.dispose();
    super.dispose();
  }

  void _ringLeftBell() {
    HapticFeedback.mediumImpact();
    _leftBellController.forward(from: 0.0);
    _showSacredBlessingToast('🔔 ॐ भैरवाय नमः — घंटा नाद शुभ फलदायी!');
  }

  void _ringRightBell() {
    HapticFeedback.mediumImpact();
    _rightBellController.forward(from: 0.0);
    _showSacredBlessingToast('🔔 ॐ नमः शिवाय — दिव्य आशीर्वाद प्राप्त हुआ!');
  }

  void _toggleAudio() {
    HapticFeedback.selectionClick();
    setState(() => _isAudioPlaying = !_isAudioPlaying);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(_isAudioPlaying ? '🔊 मंदिर मंत्र ध्वनि शुरू' : '🔇 मंत्र ध्वनि म्यूट'),
        duration: const Duration(milliseconds: 1200),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _showSacredBlessingToast(String message) {
    ScaffoldMessenger.of(context).removeCurrentSnackBar();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          message,
          style: const TextStyle(fontWeight: FontWeight.w600, color: Colors.white),
        ),
        backgroundColor: const Color(0xFF8B4513),
        duration: const Duration(milliseconds: 1500),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
    );
  }

  void _openTemplePicker() {
    HapticFeedback.lightImpact();
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return Container(
          decoration: const BoxDecoration(
            color: Color(0xFF1E1510),
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 44,
                  height: 4,
                  decoration: BoxDecoration(
                    color: Colors.white24,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              const Text(
                'पवित्र धाम व मंदिर चुनें',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 18,
                  fontWeight: FontWeight.w800,
                ),
              ),
              const SizedBox(height: 12),
              ..._sanctums.map((sanctum) {
                final isSelected = sanctum.id == _currentSanctum.id;
                return ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: CircleAvatar(
                    backgroundImage: CachedNetworkImageProvider(sanctum.imageUrl),
                    radius: 22,
                  ),
                  title: Text(
                    sanctum.name,
                    style: TextStyle(
                      color: isSelected ? CustomerTheme.brandGold : Colors.white,
                      fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
                    ),
                  ),
                  subtitle: Text(
                    sanctum.occasion,
                    style: TextStyle(color: Colors.white.withValues(alpha: 0.6), fontSize: 12),
                  ),
                  trailing: isSelected
                      ? const Icon(Icons.check_circle_rounded, color: CustomerTheme.brandGold)
                      : const Icon(Icons.chevron_right_rounded, color: Colors.white38),
                  onTap: () {
                    setState(() => _currentSanctum = sanctum);
                    Navigator.pop(ctx);
                  },
                );
              }),
              const SizedBox(height: 12),
            ],
          ),
        );
      },
    );
  }

  void _openChadhavaBottomSheet() {
    HapticFeedback.mediumImpact();
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _ChadhavaModalSheet(
        sanctum: _currentSanctum,
        onSuccess: (earnedCoins) {
          setState(() => _punyaCoins += earnedCoins);
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF130D09), // Deep sacred temple bronze/black background
      body: SafeArea(
        top: false,
        child: Column(
          children: [
            // Fixed Top Header & Devotional Stories Row
            _buildTopAppBar(context),
            _buildDevotionalStoriesStrip(),

            // Scrollable Sanctum & CTA Area
            Expanded(
              child: SingleChildScrollView(
                physics: const BouncingScrollPhysics(),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    // Golden Sanctum with Bells, Arch & Deities
                    _buildGoldenSanctumView(),

                    // Occasion Details & Headline
                    _buildOccasionDetails(),

                    const SizedBox(height: 16),

                    // Social Proof Banner
                    _buildSocialProofBanner(),

                    const SizedBox(height: 14),

                    // Primary Full-Width CTA Button
                    _buildPrimaryCtaButton(),

                    const SizedBox(height: 24),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  /// Top Bar with Avatar, Temple Switcher pill and Punya Coins
  Widget _buildTopAppBar(BuildContext context) {
    final topPadding = MediaQuery.of(context).padding.top;

    return Container(
      padding: EdgeInsets.only(
        top: topPadding + 8,
        bottom: 8,
        left: 16,
        right: 16,
      ),
      color: const Color(0xFF1C130D),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          // Left Profile Avatar
          GestureDetector(
            onTap: () => HapticFeedback.lightImpact(),
            child: Container(
              padding: const EdgeInsets.all(2),
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(color: CustomerTheme.brandSaffron, width: 2),
              ),
              child: const CircleAvatar(
                radius: 16,
                backgroundColor: Color(0xFF3A2416),
                child: Icon(Icons.person_rounded, color: CustomerTheme.brandGold, size: 20),
              ),
            ),
          ),

          // Center Temple Selector Pill
          GestureDetector(
            onTap: _openTemplePicker,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
              decoration: BoxDecoration(
                color: const Color(0xFF332014),
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: CustomerTheme.brandGold.withValues(alpha: 0.35)),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    _currentSanctum.name,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(width: 4),
                  const Icon(Icons.keyboard_arrow_down_rounded, color: Colors.white70, size: 18),
                ],
              ),
            ),
          ),

          // Right Punya Coin Badge
          GestureDetector(
            onTap: () {
              HapticFeedback.lightImpact();
              _showSacredBlessingToast('🪙 आपके पास $_punyaCoins पुण्य सिक्के हैं। प्रत्येक चढ़ावे पर पुण्य प्राप्त करें!');
            },
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
              decoration: BoxDecoration(
                color: const Color(0xFF332014),
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: CustomerTheme.brandGold.withValues(alpha: 0.4)),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    '$_punyaCoins',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 13,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(width: 4),
                  Container(
                    width: 18,
                    height: 18,
                    decoration: const BoxDecoration(
                      shape: BoxShape.circle,
                      gradient: CustomerTheme.goldGradient,
                    ),
                    alignment: Alignment.center,
                    child: const Text(
                      '卐',
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF4A2600),
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

  /// Story Circles / Devotional Reels Horizontal Strip
  Widget _buildDevotionalStoriesStrip() {
    final stories = [
      {'title': 'भक्ति रील्स', 'icon': Icons.video_collection_rounded, 'color': const Color(0xFF8B1E3F)},
      {'title': 'शनि देव', 'icon': Icons.brightness_7_rounded, 'color': const Color(0xFF4A154B)},
      {'title': 'दैनिक दिव्य दर्शन', 'icon': Icons.wb_sunny_rounded, 'color': const Color(0xFF1E3A8A)},
      {'title': 'आज का पंचांग', 'icon': Icons.calendar_month_rounded, 'color': const Color(0xFF14532D)},
    ];

    return Container(
      height: 48,
      padding: const EdgeInsets.symmetric(vertical: 6),
      color: const Color(0xFF1C130D),
      child: ListView.separated(
        padding: const EdgeInsets.symmetric(horizontal: 16),
        scrollDirection: Axis.horizontal,
        itemCount: stories.length,
        separatorBuilder: (context, index) => const SizedBox(width: 8),
        itemBuilder: (context, i) {
          final story = stories[i];
          return GestureDetector(
            onTap: () {
              HapticFeedback.lightImpact();
              _showSacredBlessingToast('${story['title']} दर्शन प्रारंभ');
            },
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: (story['color'] as Color).withValues(alpha: 0.28),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: (story['color'] as Color).withValues(alpha: 0.6)),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(story['icon'] as IconData, size: 14, color: Colors.white),
                  const SizedBox(width: 6),
                  Text(
                    story['title'] as String,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 11.5,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  /// Golden Sanctum Arch with Interactive Swinging Bells and Audio Toggle
  Widget _buildGoldenSanctumView() {
    return Container(
      margin: const EdgeInsets.fromLTRB(14, 10, 14, 0),
      decoration: BoxDecoration(
        color: const Color(0xFF24150D),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: CustomerTheme.brandGold.withValues(alpha: 0.35),
          width: 1.5,
        ),
        boxShadow: [
          BoxShadow(
            color: CustomerTheme.brandGold.withValues(alpha: 0.12),
            blurRadius: 20,
            spreadRadius: 2,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      clipBehavior: Clip.antiAlias,
      child: Stack(
        alignment: Alignment.center,
        children: [
          // Sanctum Deities Image
          AspectRatio(
            aspectRatio: 0.95,
            child: CachedNetworkImage(
              imageUrl: _currentSanctum.imageUrl,
              fit: BoxFit.cover,
              placeholder: (context, url) => Container(
                color: const Color(0xFF2A1910),
                child: const Center(
                  child: CircularProgressIndicator(color: CustomerTheme.brandGold, strokeWidth: 2),
                ),
              ),
              errorWidget: (context, url, error) => Container(
                color: const Color(0xFF2A1910),
                child: const Icon(Icons.temple_hindu_rounded, color: CustomerTheme.brandGold, size: 64),
              ),
            ),
          ),

          // Golden Arch & Vignette Overlay
          Positioned.fill(
            child: IgnorePointer(
              child: Container(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [
                      Colors.black.withValues(alpha: 0.45),
                      Colors.transparent,
                      Colors.transparent,
                      Colors.black.withValues(alpha: 0.85),
                    ],
                    stops: const [0.0, 0.25, 0.70, 1.0],
                  ),
                ),
              ),
            ),
          ),

          // Decorative Golden Arch Border Frame
          Positioned(
            top: 0,
            left: 0,
            right: 0,
            child: IgnorePointer(
              child: CustomPaint(
                size: const Size(double.infinity, 80),
                painter: _GoldenArchPainter(),
              ),
            ),
          ),

          // Floating Occasion Title Tag ("॥ काल भैरव जयंती ॥")
          Positioned(
            top: 14,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 5),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFFE2B755), Color(0xFFFDE68A), Color(0xFFDF9E32)],
                ),
                borderRadius: BorderRadius.circular(20),
                boxShadow: const [
                  BoxShadow(color: Colors.black45, blurRadius: 8, offset: Offset(0, 2)),
                ],
              ),
              child: Text(
                _currentSanctum.occasion,
                style: const TextStyle(
                  color: Color(0xFF431407),
                  fontSize: 13,
                  fontWeight: FontWeight.w900,
                  letterSpacing: 0.4,
                ),
              ),
            ),
          ),

          // Left Interactive Hanging Temple Bell
          Positioned(
            top: 40,
            left: 18,
            child: GestureDetector(
              onTap: _ringLeftBell,
              child: AnimatedBuilder(
                animation: _leftBellAngle,
                builder: (context, child) {
                  return Transform(
                    transform: Matrix4.rotationZ(_leftBellAngle.value),
                    alignment: Alignment.topCenter,
                    child: child,
                  );
                },
                child: _buildTempleBellWidget(),
              ),
            ),
          ),

          // Right Interactive Hanging Temple Bell
          Positioned(
            top: 40,
            right: 18,
            child: GestureDetector(
              onTap: _ringRightBell,
              child: AnimatedBuilder(
                animation: _rightBellAngle,
                builder: (context, child) {
                  return Transform(
                    transform: Matrix4.rotationZ(_rightBellAngle.value),
                    alignment: Alignment.topCenter,
                    child: child,
                  );
                },
                child: _buildTempleBellWidget(),
              ),
            ),
          ),

          // Occasion Date Chip (Bottom-Left)
          Positioned(
            bottom: 12,
            left: 14,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: Colors.black.withValues(alpha: 0.65),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: CustomerTheme.brandGold.withValues(alpha: 0.4)),
              ),
              child: Text(
                _currentSanctum.occasionDate,
                style: const TextStyle(
                  color: Color(0xFFFDE68A),
                  fontSize: 11.5,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          ),

          // Ambient Mantra Sound Toggle (Bottom-Right)
          Positioned(
            bottom: 12,
            right: 14,
            child: GestureDetector(
              onTap: _toggleAudio,
              child: Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: Colors.black.withValues(alpha: 0.7),
                  border: Border.all(
                    color: _isAudioPlaying ? CustomerTheme.brandGold : Colors.white30,
                    width: 1.5,
                  ),
                ),
                child: Icon(
                  _isAudioPlaying ? Icons.volume_up_rounded : Icons.volume_off_rounded,
                  color: _isAudioPlaying ? CustomerTheme.brandGold : Colors.white60,
                  size: 20,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  /// Hanging Temple Bell Widget with gold texture and chain
  Widget _buildTempleBellWidget() {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        // Bell Chain link
        Container(
          width: 2.5,
          height: 30,
          color: const Color(0xFFE2B755),
        ),
        // Bell Body with Clapper
        Container(
          width: 32,
          height: 34,
          decoration: const BoxDecoration(
            gradient: RadialGradient(
              center: Alignment(-0.2, -0.3),
              radius: 0.8,
              colors: [Color(0xFFFFFBEB), Color(0xFFD97706), Color(0xFF78350F)],
            ),
            borderRadius: BorderRadius.vertical(
              top: Radius.circular(16),
              bottom: Radius.circular(6),
            ),
            boxShadow: [
              BoxShadow(color: Colors.black54, blurRadius: 6, offset: Offset(0, 3)),
            ],
          ),
          alignment: Alignment.bottomCenter,
          padding: const EdgeInsets.only(bottom: 2),
          child: Container(
            width: 8,
            height: 8,
            decoration: const BoxDecoration(
              shape: BoxShape.circle,
              color: Color(0xFF451A03),
            ),
          ),
        ),
      ],
    );
  }

  /// Devotional Headline, Temples info and brief description
  Widget _buildOccasionDetails() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Headline with Trishul
          Text(
            '🔱 ${_currentSanctum.deityTitle} - 3 मंदिरों में चढ़ावा कर दोष-बाधाओं से मुक्ति की प्रार्थना करें!',
            style: const TextStyle(
              color: Colors.white,
              fontSize: 15,
              fontWeight: FontWeight.w800,
              height: 1.35,
            ),
          ),
          const SizedBox(height: 8),

          // Temple locations pill
          GestureDetector(
            onTap: () {
              HapticFeedback.lightImpact();
              _showTempleDetailDialog();
            },
            child: Row(
              children: [
                const Icon(Icons.temple_hindu_rounded, color: CustomerTheme.brandGold, size: 16),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    '${_currentSanctum.templesSummary} ...और पढ़ें',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: Color(0xFFE2B755),
                      fontSize: 12.5,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  /// Social Proof Banner ("👥 813,144+ भक्तों ने चढ़ावा चढ़ाया")
  Widget _buildSocialProofBanner() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: BoxDecoration(
          color: const Color(0xFF332014),
          borderRadius: BorderRadius.circular(24),
          border: Border.all(color: CustomerTheme.brandGold.withValues(alpha: 0.2)),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.group_rounded, color: CustomerTheme.brandGold, size: 18),
            const SizedBox(width: 8),
            Text(
              '${_formatNumber(_currentSanctum.devoteeCount)}+ भक्तों ने चढ़ावा चढ़ाया',
              style: const TextStyle(
                color: Color(0xFFFDE68A),
                fontSize: 13,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ),
      ),
    );
  }

  /// Primary High-Contrast Orange CTA Button
  Widget _buildPrimaryCtaButton() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: SizedBox(
        height: 52,
        child: ElevatedButton(
          onPressed: _openChadhavaBottomSheet,
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFFF95721), // High-energy Sri Mandir orange
            foregroundColor: Colors.white,
            elevation: 6,
            shadowColor: const Color(0xFFF95721).withValues(alpha: 0.45),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          ),
          child: Text(
            _currentSanctum.primaryCtaText,
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w900,
              letterSpacing: 0.3,
            ),
          ),
        ),
      ),
    );
  }

  void _showTempleDetailDialog() {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: const Color(0xFF24150D),
        title: Text(
          _currentSanctum.name,
          style: const TextStyle(color: CustomerTheme.brandGold, fontWeight: FontWeight.bold),
        ),
        content: Text(
          _currentSanctum.fullDescription,
          style: const TextStyle(color: Colors.white70, height: 1.4),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('ठीक है', style: TextStyle(color: CustomerTheme.brandGold)),
          ),
        ],
      ),
    );
  }

  String _formatNumber(int n) {
    if (n >= 100000) {
      return '${(n / 1000).toStringAsFixed(0)}K';
    }
    return n.toString();
  }
}

/// Custom Painter for the Golden Temple Arch (कमान)
class _GoldenArchPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..shader = const LinearGradient(
        colors: [Color(0xFFE2B755), Color(0xFFFDE68A), Color(0xFFB45309)],
      ).createShader(Rect.fromLTWH(0, 0, size.width, size.height))
      ..style = PaintingStyle.stroke
      ..strokeWidth = 3;

    final path = Path();
    path.moveTo(0, size.height);
    path.quadraticBezierTo(size.width * 0.2, 10, size.width * 0.5, 6);
    path.quadraticBezierTo(size.width * 0.8, 10, size.width, size.height);

    canvas.drawPath(path, paint);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

/// Modal Bottom Sheet for Instant Micro-Chadhava (₹11 / ₹21 / ₹51 / ₹101)
class _ChadhavaModalSheet extends StatefulWidget {
  final TempleSanctum sanctum;
  final ValueChanged<int> onSuccess;

  const _ChadhavaModalSheet({
    required this.sanctum,
    required this.onSuccess,
  });

  @override
  State<_ChadhavaModalSheet> createState() => _ChadhavaModalSheetState();
}

class _ChadhavaModalSheetState extends State<_ChadhavaModalSheet> {
  late ChadhavaTier _selectedTier;
  final TextEditingController _nameController = TextEditingController(text: 'रोहित शर्मा');
  final TextEditingController _gotraController = TextEditingController(text: 'कश्यप');
  bool _wantPrasadamShipped = false;
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _selectedTier = widget.sanctum.offerings.firstWhere(
      (t) => t.isPopular,
      orElse: () => widget.sanctum.offerings.first,
    );
  }

  @override
  void dispose() {
    _nameController.dispose();
    _gotraController.dispose();
    super.dispose();
  }

  void _completeChadhava() async {
    final name = _nameController.text.trim();
    if (name.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('कृपया भक्त का नाम दर्ज करें')),
      );
      return;
    }

    setState(() => _isSubmitting = true);
    HapticFeedback.heavyImpact();

    await Future.delayed(const Duration(milliseconds: 1000));
    if (!mounted) return;

    setState(() => _isSubmitting = false);
    Navigator.pop(context);

    // Give user 11 Punya Coins
    widget.onSuccess(11);

    // Show celebratory confirmation
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: const Color(0xFF24150D),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Row(
          children: [
            Icon(Icons.check_circle_rounded, color: Colors.greenAccent, size: 28),
            SizedBox(width: 8),
            Text('चढ़ावा संकल्प स्वीकृत!', style: TextStyle(color: Colors.white, fontSize: 18)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'आदरणीय $name जी, ${_selectedTier.title} हेतु आपका ₹${_selectedTier.amount} का चढ़ावा ${widget.sanctum.name} में संकल्पित हो चुका है।',
              style: const TextStyle(color: Colors.white70, fontSize: 13, height: 1.4),
            ),
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: CustomerTheme.brandGold.withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: CustomerTheme.brandGold.withValues(alpha: 0.3)),
              ),
              child: const Row(
                children: [
                  Text('🪙', style: TextStyle(fontSize: 18)),
                  SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'आपको +11 पुण्य सिक्के प्राप्त हुए हैं!',
                      style: TextStyle(color: Color(0xFFFDE68A), fontWeight: FontWeight.bold, fontSize: 12),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
        actions: [
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFF95721),
              foregroundColor: Colors.white,
            ),
            child: const Text('जय श्री महाकाल'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: Color(0xFF1E1510),
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.only(
        left: 20,
        right: 20,
        top: 16,
        bottom: MediaQuery.of(context).viewInsets.bottom + 20,
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(
                width: 44,
                height: 4,
                decoration: BoxDecoration(
                  color: Colors.white24,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: 14),

            // Modal Header
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '${widget.sanctum.name} चढ़ावा अर्पण',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 18,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      const SizedBox(height: 2),
                      const Text(
                        'पवित्र नाम व गोत्र से देवस्थान में अर्पण किया जाएगा',
                        style: TextStyle(color: Colors.white60, fontSize: 12),
                      ),
                    ],
                  ),
                ),
                IconButton(
                  onPressed: () => Navigator.pop(context),
                  icon: const Icon(Icons.close_rounded, color: Colors.white60),
                ),
              ],
            ),
            const SizedBox(height: 14),

            // Offering Tier Chips
            const Text(
              'चढ़ावा सेवा चुनें:',
              style: TextStyle(color: CustomerTheme.brandGold, fontSize: 13, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            ...widget.sanctum.offerings.map((tier) {
              final isSelected = tier.amount == _selectedTier.amount;
              return GestureDetector(
                onTap: () {
                  HapticFeedback.selectionClick();
                  setState(() => _selectedTier = tier);
                },
                child: Container(
                  margin: const EdgeInsets.only(bottom: 8),
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: isSelected ? const Color(0xFF382315) : const Color(0xFF261912),
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(
                      color: isSelected ? const Color(0xFFF95721) : Colors.white12,
                      width: isSelected ? 1.8 : 1,
                    ),
                  ),
                  child: Row(
                    children: [
                      Text(tier.icon, style: const TextStyle(fontSize: 22)),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Text(
                                  tier.title,
                                  style: TextStyle(
                                    color: isSelected ? Colors.white : Colors.white70,
                                    fontSize: 13.5,
                                    fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
                                  ),
                                ),
                                if (tier.isPopular) ...[
                                  const SizedBox(width: 6),
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                    decoration: BoxDecoration(
                                      color: const Color(0xFFF95721),
                                      borderRadius: BorderRadius.circular(6),
                                    ),
                                    child: const Text(
                                      'लोकप्रिय',
                                      style: TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold),
                                    ),
                                  ),
                                ],
                              ],
                            ),
                            const SizedBox(height: 2),
                            Text(
                              tier.description,
                              style: TextStyle(color: Colors.white.withValues(alpha: 0.5), fontSize: 11),
                            ),
                          ],
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: isSelected ? const Color(0xFFF95721) : const Color(0xFF332014),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Text(
                          '₹${tier.amount}',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 14,
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              );
            }),

            const SizedBox(height: 12),

            // Devotee Name & Gotra fields
            Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _nameController,
                    style: const TextStyle(color: Colors.white, fontSize: 13),
                    decoration: InputDecoration(
                      labelText: 'भक्त का नाम',
                      labelStyle: const TextStyle(color: Colors.white60, fontSize: 12),
                      filled: true,
                      fillColor: const Color(0xFF261912),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: TextField(
                    controller: _gotraController,
                    style: const TextStyle(color: Colors.white, fontSize: 13),
                    decoration: InputDecoration(
                      labelText: 'गोत्र (ऐच्छिक)',
                      labelStyle: const TextStyle(color: Colors.white60, fontSize: 12),
                      filled: true,
                      fillColor: const Color(0xFF261912),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                  ),
                ),
              ],
            ),

            const SizedBox(height: 12),

            // Prasadam Home Delivery Switch
            SwitchListTile(
              contentPadding: EdgeInsets.zero,
              activeTrackColor: const Color(0xFFF95721),
              activeThumbColor: Colors.white,
              title: const Text(
                'घर पर अभिमंत्रित रक्षा सूत्र व प्रसाद मंगवाएं',
                style: TextStyle(color: Colors.white70, fontSize: 12.5),
              ),
              value: _wantPrasadamShipped,
              onChanged: (val) => setState(() => _wantPrasadamShipped = val),
            ),

            const SizedBox(height: 12),

            // Submit Button
            SizedBox(
              width: double.infinity,
              height: 50,
              child: ElevatedButton(
                onPressed: _isSubmitting ? null : _completeChadhava,
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFFF95721),
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
                child: _isSubmitting
                    ? const CircularProgressIndicator(color: Colors.white, strokeWidth: 2)
                    : Text(
                        '₹${_selectedTier.amount} चढ़ावा अर्पित करें',
                        style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                      ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
