import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:op_shared/op_shared.dart';

import '../../theme/customer_theme.dart';
import '../checkout/checkout_screen.dart';

/// Authentic Vedic Sacred Puja & Chadhava Detail Experience
class PujaDetailScreen extends StatefulWidget {
  static const route = '/puja-detail';
  const PujaDetailScreen({super.key, required this.puja});

  final Puja puja;

  @override
  State<PujaDetailScreen> createState() => _PujaDetailScreenState();
}

class _PujaDetailScreenState extends State<PujaDetailScreen> {
  late Puja _puja = widget.puja;
  List<Map<String, dynamic>>? _faqs;
  PujaPackage? _selectedPackage;
  int _selectedPackageIndex = 0;
  bool _loadingDetails = false;

  @override
  void initState() {
    super.initState();
    _puja = widget.puja;
    final pkgs = _puja.packages ?? const <PujaPackage>[];
    if (pkgs.isNotEmpty) {
      _selectedPackage = pkgs.first;
    }
    _loadDetails();
    _loadFaqs();
  }

  Future<void> _loadDetails() async {
    try {
      setState(() => _loadingDetails = true);
      dynamic targetId = _puja.id;
      if (targetId == null && (_puja.slug != null || _puja.title != null)) {
        final list = await PujaApi.instance.list();
        final match = list.cast<Puja?>().firstWhere(
          (p) =>
              p != null &&
              ((_puja.slug != null && p.slug == _puja.slug) ||
                  (_puja.title != null &&
                      p.title != null &&
                      p.title!.toLowerCase().trim() ==
                          _puja.title!.toLowerCase().trim())),
          orElse: () => null,
        );
        if (match != null && match.id != null) {
          targetId = match.id;
          _puja = match;
        }
      }
      if (targetId == null) return;
      final detailed = await PujaApi.instance.detail(targetId);
      if (detailed != null && mounted) {
        setState(() {
          _puja = detailed;
          final pkgs = _puja.packages ?? const <PujaPackage>[];
          if (pkgs.isNotEmpty) {
            _selectedPackage = pkgs.first;
            _selectedPackageIndex = 0;
          }
        });
      }
    } catch (_) {
    } finally {
      if (mounted) setState(() => _loadingDetails = false);
    }
  }

  Future<void> _loadFaqs() async {
    try {
      final faqs = await PujaApi.instance.faqs(pujaId: widget.puja.id);
      if (mounted) setState(() => _faqs = faqs);
    } catch (_) {
      if (mounted) setState(() => _faqs = const []);
    }
  }

  static String _formatDate(dynamic v) {
    final s = v?.toString() ?? '';
    return s.length >= 10 ? s.substring(0, 10) : s;
  }

  void _proceedToBooking() {
    final packages = _puja.packages ?? const <PujaPackage>[];
    PujaPackage? pkg = _selectedPackage ?? (packages.isNotEmpty ? packages.first : null);
    pkg ??= PujaPackage(
      id: 1,
      name: 'Vedic Sankalp Seva',
      price: '501',
      inclusions: const [
        'Personal Sankalp with Name & Gotra',
        'Vedic Mantra chanting by Acharyas',
        'Puja video recorded & sent on WhatsApp',
        'Holy Prasad delivered to your address',
      ],
    );

    Navigator.of(context).push(MaterialPageRoute(
      builder: (_) => CheckoutScreen.puja(
        puja: _puja,
        package: pkg!,
      ),
    ));
  }

  String _cleanTitle(String? raw, bool isHi) {
    if (raw == null || raw.isEmpty) {
      return isHi ? 'वैदिक महापूजा एवं अनुष्ठान' : 'Vedic Mahapuja';
    }
    if (isHi) {
      final lower = raw.toLowerCase();
      if (lower.contains('narayan nagbali') || (lower.contains('narayan') && lower.contains('nagbali'))) {
        return 'वाराणसी में नारायण नागबलि महापूजा - कालसर्प व पितृदोष निवारण';
      }
      if (lower.contains('deepdaan') || lower.contains('deepawali') || lower.contains('dev deepavali')) {
        return 'काशी देव दीपावली महामहोत्सव - 108 दीपदान एवं गंगा महाआरती';
      }
      if (lower.contains('rudrabhishek') || lower.contains('rudra')) {
        return 'द्वादश ज्योतिर्लिंग षोडशोपचार रुद्राभिषेक महायज्ञ';
      }
      if (lower.contains('mahamrityunjaya') || lower.contains('mrityunjay')) {
        return 'अकाल मृत्यु व रोग निवारक महामृत्युंजय महाजाप अनुष्ठान';
      }
      if (lower.contains('kalsarp') || lower.contains('sarpa dosha')) {
        return 'त्र्यंबकेश्वर / काशी कालसर्प दोष शांति महापूजा';
      }
      if (lower.contains('navgrah') || lower.contains('graha shanti')) {
        return 'सर्वग्रह पीड़ा निवारक संपूर्ण नवग्रह शांति महायज्ञ';
      }
      if (lower.contains('baglamukhi')) {
        return 'मां पीताम्बरा बगलामुखी शत्रु व विजय प्राप्ति अनुष्ठान';
      }
      if (lower.contains('pind daan') || lower.contains('shradh') || lower.contains('pitru')) {
        return 'गयाजी तीर्थ में त्रिपिंडी श्राद्ध एवं मोक्षदायिनी पिंडदान';
      }
      if (lower.contains('lakshmi') || lower.contains('laxmi') || lower.contains('kuber')) {
        return 'अष्टलक्ष्मी एवं कुबेर अखंड धन समृद्धि महायज्ञ';
      }
      if (lower.contains('durga') || lower.contains('chandi') || lower.contains('navratri')) {
        return 'मां भगवती दुर्गा शतचंडी महायज्ञ एवं वेदोक्त पाठ';
      }
      if (lower.contains('satyanarayan')) {
        return 'श्री सत्यनारायण भगवान व्रत कथा एवं पावन महापूजा';
      }
      if (lower.contains('sundarkand') || lower.contains('hanuman')) {
        return 'संकटमोचन श्री हनुमान सुंदरकांड संगीतमय अखंड पाठ';
      }
    }
    if (raw.contains(' - ')) return raw.split(' - ').first.trim();
    if (raw.contains(' | ')) return raw.split(' | ').first.trim();
    return raw.trim();
  }

  String _localizePackageName(String? name, bool isHi) {
    return VedicHindiLocalizer.localizePackageName(name, isHi);
  }

  String _localizeInclusion(String inc, bool isHi) {
    return VedicHindiLocalizer.localizeInclusion(inc, isHi);
  }

  void _openWhatsAppHelp() async {
    final uri = Uri.parse('https://wa.me/917007158014?text=Har%20Har%20Mahadev%2C%20I%20need%20assistance%20with%20Puja%20Sankalp');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  String _getFestivalTag(Puja p, bool isHi) {
    final t = (p.title ?? '').toLowerCase();
    if (t.contains('bhairav') || t.contains('kalbhairav')) return isHi ? 'कालभैरव जयंती 🔱' : 'Kaal Bhairav Jayanti 🔱';
    if (t.contains('mrityunjay') || t.contains('mahamrityunjay')) return isHi ? 'संजीवनी महामृत्युंजय 🔱' : 'Mahamrityunjay Mahayajna 🔱';
    if (t.contains('deepdaan') || t.contains('deepawali')) return isHi ? 'काशी देव दीपावली 🪔' : 'Dev Deepavali Mahotsav 🪔';
    if (t.contains('rudrabhishek') || t.contains('rudra')) return isHi ? 'द्वादश ज्योतिर्लिंग रुद्राभिषेक 🔱' : 'Dwadas Jyotirling Rudra 🔱';
    if (t.contains('kalsarp') || t.contains('rahu')) return isHi ? 'कालसर्प व राहु गोचर शांति 🐍' : 'Kaal Sarp Shanti 🐍';
    if (t.contains('pind daan') || t.contains('shradh')) return isHi ? 'गयाजी मोक्ष पिंडदान 🪷' : 'Gaya Teerth Pind Daan 🪷';
    if (t.contains('baglamukhi')) return isHi ? 'मां बगलामुखी शत्रु विजय ⚔️' : 'Maa Baglamukhi Seva ⚔️';
    if (t.contains('lakshmi') || t.contains('kuber')) return isHi ? 'महालक्ष्मी कुबेर अखंड धन 💰' : 'Mahalakshmi Kuber Yajna 💰';
    return isHi ? 'विशेष सिद्ध महापूजा 🪔' : 'Auspicious Vedic Puja 🪔';
  }

  String _getSubTag(Puja p, bool isHi) {
    final t = (p.title ?? '').toLowerCase();
    if (t.contains('bhairav') || t.contains('kalbhairav')) return isHi ? 'कालभैरव जयंती - काली-शनि-भैरव विशेष संगम' : 'Kaal Bhairav Jayanti - Special Trideva Sangam';
    if (t.contains('mrityunjay')) return isHi ? 'अकाल मृत्यु व रोग निवारक - संजीवनी महामंत्र जाप' : 'Sanjeevani Mahamrityunjay Japa for Critical Illness';
    if (t.contains('deepdaan') || t.contains('deepawali')) return isHi ? 'काशी देव दीपावली - गंगा घाट १०८ दीपदान व महाआरती' : 'Kashi Ghats 108 Deepdaan & Ganga Maha Aarti';
    if (t.contains('rudrabhishek')) return isHi ? 'दारिद्र्य दहन व शिव कृपा - षोडशोपचार महा रुद्राभिषेक' : 'Shiva Shodashopachar Rudrabhishek at Sacred Jyotirling';
    if (t.contains('kalsarp')) return isHi ? 'त्र्यंबकेश्वर - कालसर्प व राहु-केतु दोष निवारण महापूजा' : 'Trimbakeshwar Kaal Sarp & Rahu Dosha Nivarana';
    if (t.contains('pind daan')) return isHi ? 'गयाजी तीर्थ - फल्गु नदी व अक्षयवट वेदोक्त पिंडदान' : 'Gaya Teerth Falgu River Vedic Pind Daan';
    return isHi ? 'काशी के वेदमूर्ति तीर्थ पुरोहितों द्वारा सस्वर संकल्प' : 'Vedic Sankalp by certified Kashi Teerth Purohits';
  }

  Widget _buildSriMandirCountdownTimer(bool isDark, bool isHi) {
    return Container(
      margin: const EdgeInsets.only(top: 14, bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E293B) : const Color(0xFFFFFBEB),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFFDE68A)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.access_time_rounded, size: 15, color: Color(0xFFD97706)),
              const SizedBox(width: 6),
              Text(
                isHi ? "पूजा में समय बचा है" : "Time remaining for Puja",
                style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: Color(0xFF92400E),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              _buildCountdownBlock("06", isHi ? "दिन" : "Days"),
              _buildCountdownBlock("09", isHi ? "घंटे" : "Hours"),
              _buildCountdownBlock("20", isHi ? "मिनट" : "Mins"),
              _buildCountdownBlock("40", isHi ? "सेकंड" : "Secs"),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildCountdownBlock(String num, String label) {
    return Expanded(
      child: Container(
        margin: const EdgeInsets.symmetric(horizontal: 3),
        padding: const EdgeInsets.symmetric(vertical: 8),
        decoration: BoxDecoration(
          color: const Color(0xFFFFEDD5),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Column(
          children: [
            Text(
              num,
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w900,
                color: Color(0xFFC2410C),
              ),
            ),
            const SizedBox(height: 2),
            Text(
              label,
              style: const TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w600,
                color: Color(0xFF9A3412),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDevoteeSocialProofStrip(bool isHi) {
    return Container(
      margin: const EdgeInsets.only(top: 6, bottom: 16),
      child: Row(
        children: [
          SizedBox(
            width: 95,
            height: 30,
            child: Stack(
              children: [
                Positioned(left: 0, child: _circleAvatar('https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&fit=crop&q=80', 'AM')),
                Positioned(left: 20, child: _circleAvatar('https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100&fit=crop&q=80', 'PS')),
                Positioned(left: 40, child: _circleAvatar('https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=100&fit=crop&q=80', 'RK')),
                Positioned(left: 60, child: _circleAvatar('https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=100&fit=crop&q=80', 'SV')),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              isHi ? '२१,०९०+ भक्त पूजा में शामिल हुए' : '21,090+ Devotees Joined Puja',
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w700,
                color: Color(0xFF334155),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _circleAvatar(String url, String fallback) {
    return Container(
      width: 28,
      height: 28,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        border: Border.all(color: Colors.white, width: 2),
        color: const Color(0xFFFED7AA),
      ),
      child: ClipOval(
        child: CachedNetworkImage(
          imageUrl: url,
          fit: BoxFit.cover,
          placeholder: (_, _) => const SizedBox(),
          errorWidget: (_, _, _) => Center(
            child: Text(
              fallback,
              style: const TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: Color(0xFF9A3412)),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildSpiritualImportanceCard(Puja p, bool isHi) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFFFFFBEB),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFFDE68A)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            isHi ? 'पूजा के बारे में' : 'About this Puja',
            style: const TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w900,
              color: Color(0xFF92400E),
            ),
          ),
          const SizedBox(height: 6),
          Text(
            isHi
                ? 'महाअनुष्ठान पर देव शक्तियों की संयुक्त वेदोक्त उपासना का क्या आध्यात्मिक महत्व है?'
                : 'What is the Vedic significance of this sacred mahapuja?',
            style: const TextStyle(
              fontSize: 13.5,
              fontWeight: FontWeight.w700,
              color: Color(0xFF78350F),
            ),
          ),
          const SizedBox(height: 8),
          Text(
            isHi
                ? 'शास्त्रों के अनुसार काशी एवं सिद्ध तीर्थों पर वेदमूर्ति पुरोहितों द्वारा नाम व गोत्र से किया गया संकल्प समस्त ज्ञात-अज्ञात संकटों, रोग-बाधाओं एवं नकारात्मक ऊर्जा का समूल नाश कर सुख-शांति एवं आरोग्य प्रदान करता है।'
                : 'According to Vedic scriptures, sankalp performed with Name and Gotra at holy teerths by Vedic acharyas eliminates planetary hurdles and brings peace and well-being.',
            style: const TextStyle(
              fontSize: 12.5,
              color: Color(0xFF451A03),
              height: 1.45,
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final p = _puja;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final scheme = Theme.of(context).colorScheme;

    return ValueListenableBuilder<AppLanguage>(
      valueListenable: LocaleManager.instance.currentLanguage,
      builder: (context, lang, _) {
        final isHi = lang != AppLanguage.en;
        final packages = (p.packages != null && p.packages!.isNotEmpty)
            ? p.packages!
            : [
                PujaPackage(
                  id: 1,
                  name: isHi ? 'एकल यजमान संकल्प' : 'Individual Sankalp',
                  price: '501',
                  inclusions: isHi
                      ? const [
                          '१ यजमान का नाम व गोत्र सहित संकल्प',
                          'प्रत्यक्ष पुष्पार्पण एवं चढ़ावा अर्पण',
                          'व्यक्तिगत वीडियो रिकॉर्डिंग व्हाट्सएप पर',
                        ]
                      : const [
                          'Sankalp for 1 devotee with Name & Gotra',
                          'Live Chadhava & Pushpanjali offered',
                          'Personalized Video sent on WhatsApp',
                        ],
                ),
                PujaPackage(
                  id: 2,
                  name: isHi ? 'सपरिवार महासंकल्प' : 'Family Maha Sankalp',
                  price: '1100',
                  inclusions: isHi
                      ? const [
                          'परिवार के ४ सदस्यों हेतु संयुक्त संकल्प',
                          'विशेष अर्चना एवं नवग्रह आहुति',
                          'पूजा वीडियो + महाप्रसाद घर तक',
                        ]
                      : const [
                          'Sankalp for up to 4 family members',
                          'Special Archana & Navgrah Ahuti',
                          'Puja Video + Prasad delivered to home',
                        ],
                ),
                PujaPackage(
                  id: 3,
                  name: isHi ? 'संपूर्ण तीर्थ महापूजा' : 'Sampoorna Teerth Mahapuja',
                  price: '2100',
                  inclusions: isHi
                      ? const [
                          'संपूर्ण वैदिक हवन एवं ब्राह्मण भोजन',
                          'समस्त परिवार जन संकल्प में सम्मिलित',
                          'सिद्ध रक्षा सूत्र एवं महाप्रसाद बॉक्स',
                        ]
                      : const [
                          'Complete Vedic Hawan & Brahmin Bhojan',
                          'All family members included in Sankalp',
                          'Special Energized Raksha Sutra & Mahaprasad box',
                        ],
                ),
              ];

        if (_selectedPackage == null && packages.isNotEmpty) {
          _selectedPackage = packages.first;
        }

        final currentPrice = _selectedPackage?.priceValue ?? 501.0;
        final cardBg = isDark ? CustomerTheme.cosmicCardDark : Colors.white;
        final cardBorder = isDark ? CustomerTheme.cosmicBorderDark : const Color(0xFFE2E8F0);
        final textPrimary = isDark ? Colors.white : const Color(0xFF1E293B);
        final textSecondary = isDark ? Colors.white70 : const Color(0xFF64748B);

        return Scaffold(
          backgroundColor: scheme.surface,
          appBar: AppBar(
            backgroundColor: scheme.surface,
            foregroundColor: scheme.onSurface,
            elevation: 0.5,
            title: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  _cleanTitle(p.title?.toString(), isHi),
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w800,
                    color: textPrimary,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                Text(
                  isHi
                      ? 'ऑनलाइन पूजा द्वारा प्रमाणित वैदिक अनुष्ठान'
                      : 'OnlinePuja Verified Vedic Ritual',
                  style: TextStyle(fontSize: 11, color: textSecondary),
                ),
              ],
            ),
            bottom: _loadingDetails
                ? const PreferredSize(
                    preferredSize: Size.fromHeight(2),
                    child: LinearProgressIndicator(
                      minHeight: 2,
                      valueColor: AlwaysStoppedAnimation<Color>(CustomerTheme.brandGold),
                      backgroundColor: Colors.transparent,
                    ),
                  )
                : null,
            actions: [
              IconButton(
                tooltip: 'WhatsApp Seva Help',
                icon: Container(
                  padding: const EdgeInsets.all(5),
                  decoration: const BoxDecoration(
                    color: Color(0xFF25D366),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.chat_bubble_rounded, size: 15, color: Colors.white),
                ),
                onPressed: _openWhatsAppHelp,
              ),
              IconButton(
                icon: Icon(Icons.share_outlined, color: textSecondary),
                onPressed: () {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text(
                        isHi ? 'पूजा लिंक कॉपी किया गया' : 'Puja link copied to share',
                      ),
                    ),
                  );
                },
              ),
            ],
          ),
          bottomNavigationBar: Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(
              color: cardBg,
              border: Border(top: BorderSide(color: cardBorder, width: 1)),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: isDark ? 0.35 : 0.08),
                  blurRadius: 10,
                  offset: const Offset(0, -3),
                ),
              ],
            ),
            child: SafeArea(
              child: Row(
                children: [
                  Expanded(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          _localizePackageName(_selectedPackage?.name, isHi),
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: textSecondary,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        Row(
                          children: [
                            Text(
                              '₹${currentPrice.toStringAsFixed(0)}',
                              style: TextStyle(
                                fontSize: 22,
                                fontWeight: FontWeight.w900,
                                color: textPrimary,
                              ),
                            ),
                            const SizedBox(width: 6),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 6, vertical: 2),
                              decoration: BoxDecoration(
                                color: const Color(0xFFDCFCE7),
                                borderRadius: BorderRadius.circular(4),
                              ),
                              child: Text(
                                isHi ? 'सर्व-समावेशी' : 'All-Inclusive',
                                style: const TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.w800,
                                  color: Color(0xFF15803D),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 14),
                  ElevatedButton(
                    onPressed: _proceedToBooking,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF059669),
                      foregroundColor: Colors.white,
                      padding:
                          const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      elevation: 2,
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          isHi ? 'संकल्प लें' : AppStrings.participate,
                          style: const TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w800,
                            letterSpacing: 0.5,
                          ),
                        ),
                        const SizedBox(width: 6),
                        const Icon(Icons.arrow_forward_rounded, size: 18),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
      body: ListView(
        padding: EdgeInsets.zero,
        children: [
          // 1. Hero Image with Badges
          Stack(
            children: [
              AspectRatio(
                aspectRatio: 16 / 10,
                child: p.coverImage.isNotEmpty
                    ? CachedNetworkImage(
                        imageUrl: MiscApi.imageUrl(p.coverImage),
                        fit: BoxFit.cover,
                        placeholder: (_, _) => Container(
                          decoration: const BoxDecoration(
                            gradient: LinearGradient(
                              colors: [Color(0xFF78350F), Color(0xFFB45309)],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                          ),
                          child: const Center(
                            child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFFD97706)),
                          ),
                        ),
                        errorWidget: (_, _, _) => Container(
                          decoration: const BoxDecoration(
                            gradient: LinearGradient(
                              colors: [Color(0xFF78350F), Color(0xFFB45309)],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                          ),
                          child: const Center(
                            child: Icon(Icons.temple_hindu_rounded, size: 64, color: Colors.white38),
                          ),
                        ),
                      )
                    : Container(
                        color: const Color(0xFFFEF3C7),
                        child: const Icon(Icons.temple_hindu_rounded,
                            size: 64, color: Color(0xFFD97706)),
                      ),
              ),
              Positioned.fill(
                child: DecoratedBox(
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: [
                        Colors.black.withValues(alpha: 0.6),
                        Colors.transparent,
                        Colors.black.withValues(alpha: 0.8),
                      ],
                      begin: Alignment.topCenter,
                      end: Alignment.bottomCenter,
                      stops: const [0.0, 0.4, 1.0],
                    ),
                  ),
                ),
              ),
              // Top Holy Teerth Badge
              Positioned(
                top: 14,
                left: 14,
                child: Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: 0.7),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(
                        color: const Color(0xFFF59E0B).withValues(alpha: 0.6)),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.verified_rounded,
                          size: 14, color: Color(0xFFF59E0B)),
                      const SizedBox(width: 5),
                      Text(
                        (p.place ?? '').toString().isNotEmpty
                            ? VedicHindiLocalizer.localizePlace(p.place.toString(), isHi)
                            : (isHi ? 'पावन तीर्थ क्षेत्र' : 'Holy Teerth Kshetra'),
                        style: const TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                          color: Colors.white,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              // Bottom Devotees Sankalp Badge
              Positioned(
                bottom: 14,
                left: 14,
                right: 14,
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Wrap(
                      spacing: 8,
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: const Color(0xFF059669),
                            borderRadius: BorderRadius.circular(16),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const Icon(Icons.people_alt_rounded,
                                  size: 13, color: Colors.white),
                              const SizedBox(width: 4),
                              Text(
                                isHi
                                    ? '१४,२५०+ यजमान संकल्प ले चुके हैं'
                                    : '14,250+ Devotees Participated',
                                style: const TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w800,
                                  color: Colors.white,
                                ),
                              ),
                            ],
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 9, vertical: 4),
                          decoration: BoxDecoration(
                            gradient: const LinearGradient(
                              colors: [Color(0xFFFEF3C7), Color(0xFFFDE68A)],
                            ),
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: const Color(0xFFD97706), width: 1.2),
                          ),
                          child: Text(
                            _getFestivalTag(p, isHi),
                            style: const TextStyle(
                              fontSize: 10.5,
                              fontWeight: FontWeight.w900,
                              color: Color(0xFF78350F),
                            ),
                          ),
                        ),
                      ],
                    ),
                    if (_formatDate(p.startDatetime).isNotEmpty)
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.black.withValues(alpha: 0.7),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          '📅 ${_formatDate(p.startDatetime)}',
                          style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            color: Color(0xFFFDE68A),
                          ),
                        ),
                      ),
                  ],
                ),
              ),
            ],
          ),

          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Pink/Crimson Category Tag (Exact Sri Mandir Pill)
                Text(
                  _getSubTag(p, isHi),
                  style: const TextStyle(
                    fontSize: 12.5,
                    fontWeight: FontWeight.w700,
                    color: Color(0xFFBE185D),
                  ),
                ),
                const SizedBox(height: 6),

                // Large Bold Title
                Text(
                  _cleanTitle(p.title?.toString(), isHi),
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w900,
                    color: textPrimary,
                    height: 1.25,
                  ),
                ),
                if ((p.subtitle ?? '').toString().isNotEmpty) ...[
                  const SizedBox(height: 6),
                  Text(
                    VedicHindiLocalizer.localizeSubtitle(p.subtitle?.toString(), p.title?.toString(), isHi),
                    style: TextStyle(
                      fontSize: 13.5,
                      fontWeight: FontWeight.w500,
                      color: textSecondary,
                      height: 1.35,
                    ),
                  ),
                ],

                const SizedBox(height: 10),

                // Mandir Location Row
                Row(
                  children: [
                    const Icon(Icons.temple_hindu_outlined, size: 16, color: Color(0xFFEA580C)),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        (p.place ?? '').toString().isNotEmpty
                            ? VedicHindiLocalizer.localizePlace(p.place.toString(), isHi)
                            : (isHi ? 'पावन तीर्थ क्षेत्र' : 'Holy Teerth Kshetra'),
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: isDark ? Colors.white70 : const Color(0xFF475569),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 5),

                // Date & Tithi Row
                Row(
                  children: [
                    const Icon(Icons.calendar_today_outlined, size: 14, color: Color(0xFFEA580C)),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        _formatDate(p.startDatetime).isNotEmpty
                            ? '📅 ${_formatDate(p.startDatetime)} • ${isHi ? "पावन वेदोक्त मुहूर्त" : "Sacred Muhurta"}'
                            : (isHi ? '१ दिसम्बर, मंगलवार, मार्गशीर्ष कृष्ण अष्टमी' : '1 Dec, Auspicious Tithi'),
                        style: TextStyle(
                          fontSize: 12.5,
                          fontWeight: FontWeight.w600,
                          color: isDark ? Colors.white70 : const Color(0xFF475569),
                        ),
                      ),
                    ),
                  ],
                ),

                // Sri Mandir Live 4-Box Countdown Timer
                _buildSriMandirCountdownTimer(isDark, isHi),

                // Overlapping Devotee Social Proof Strip
                _buildDevoteeSocialProofStrip(isHi),

                // Spiritual Importance Card
                _buildSpiritualImportanceCard(p, isHi),

                // 2. Trust Strip (WhatsApp Video + Prasad + Verified Acharyas)
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: cardBg,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: cardBorder),
                  ),
                  child: Row(
                    children: [
                      _trustItem(
                        icon: Icons.video_camera_front_rounded,
                        color: const Color(0xFF25D366),
                        title: isHi ? 'प्रत्यक्ष वीडियो' : 'Video Proof',
                        subtitle: isHi ? 'व्हाट्सएप पर' : 'On WhatsApp',
                        textPrimary: textPrimary,
                        textSecondary: textSecondary,
                      ),
                      SizedBox(
                        height: 28,
                        child: VerticalDivider(
                            color: cardBorder, thickness: 1),
                      ),
                      _trustItem(
                        icon: Icons.local_shipping_rounded,
                        color: const Color(0xFFF59E0B),
                        title: isHi ? 'अभिमंत्रित महाप्रसाद' : 'Prasad Box',
                        subtitle: isHi ? 'घर तक सुरक्षित डिलीवरी' : 'SpeedPost Delivery',
                        textPrimary: textPrimary,
                        textSecondary: textSecondary,
                      ),
                      SizedBox(
                        height: 28,
                        child: VerticalDivider(
                            color: cardBorder, thickness: 1),
                      ),
                      _trustItem(
                        icon: Icons.workspace_premium_rounded,
                        color: const Color(0xFF3B82F6),
                        title: isHi ? 'तीर्थ पुरोहित' : 'Kashi Acharyas',
                        subtitle: isHi ? 'सस्वर वेदोक्त मंत्रोच्चार' : '100% Vedic Mantras',
                        textPrimary: textPrimary,
                        textSecondary: textSecondary,
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 20),

                if (packages.isNotEmpty) ...[
                  // 3. Select Package
                  Row(
                    children: [
                      const Icon(Icons.card_giftcard_rounded,
                          size: 20, color: Color(0xFFD97706)),
                      const SizedBox(width: 8),
                      Text(
                        AppStrings.choosePackage,
                        style: TextStyle(
                          fontSize: 17,
                          fontWeight: FontWeight.w800,
                          color: textPrimary,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    isHi
                        ? 'अपने परिवार के संकल्प हेतु उपयुक्त सेवा चुनें'
                        : 'Select the seva tier for your family’s sankalp',
                    style: TextStyle(fontSize: 12, color: textSecondary),
                  ),
                  const SizedBox(height: 12),

                  // Package List
                  for (int i = 0; i < packages.length; i++) ...[
                    _packageCard(packages[i], i, cardBg, cardBorder, textPrimary, textSecondary, isHi),
                    const SizedBox(height: 10),
                  ],

                  const SizedBox(height: 20),
                ],

                // 4. About the Puja
                if ((p.longDescription ?? '').toString().isNotEmpty) ...[
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: cardBg,
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: cardBorder),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            const Icon(Icons.auto_stories_rounded,
                                size: 18, color: Color(0xFFD97706)),
                            const SizedBox(width: 8),
                            Text(
                              AppStrings.significanceVidhi,
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w800,
                                color: textPrimary,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        _buildVedicDescription(
                          p.longDescription.toString(),
                          textPrimary,
                          textSecondary,
                          isHi,
                          p.title,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),
                ],

                // 5. Puja Benefits
                if ((p.benefits ?? const <String>[]).isNotEmpty) ...[
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFFFBEB),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: const Color(0xFFFDE68A)),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            const Icon(Icons.stars_rounded,
                                size: 18, color: Color(0xFFD97706)),
                            const SizedBox(width: 8),
                            Text(
                              AppStrings.divineBenefits,
                              style: const TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w800,
                                color: Color(0xFF92400E),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        for (final b in p.benefits!) ...[
                          Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Padding(
                                padding: EdgeInsets.only(top: 2),
                                child: Icon(Icons.check_circle_rounded,
                                    size: 16, color: Color(0xFF059669)),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  VedicHindiLocalizer.localizeBenefit(b, isHi),
                                  style: const TextStyle(
                                    fontSize: 13,
                                    color: Color(0xFF78350F),
                                    fontWeight: FontWeight.w600,
                                    height: 1.4,
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 8),
                        ],
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),
                ],

                // 6. 4-Step Process Guide
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: cardBg,
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: cardBorder),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        isHi ? 'ऑनलाइन पूजा विधि एवं प्रक्रिया' : 'How Online Puja Works',
                        style: TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.w800,
                          color: textPrimary,
                        ),
                      ),
                      const SizedBox(height: 14),
                      _stepRow(
                        num: '1',
                        title: isHi
                            ? 'सेवा चुनें व यजमान विवरण भरें'
                            : 'Select Seva & Fill Devotee Details',
                        desc: isHi
                            ? 'चेकआउट पर नाम, गोत्र व मनोकामना दर्ज करें।'
                            : 'Provide your Gotra, Name & Sankalp Wish at checkout.',
                      ),
                      _stepRow(
                        num: '2',
                        title: isHi
                            ? 'तीर्थ धाम पर पंडित जी द्वारा संकल्प'
                            : 'Pandit Ji performs Sankalp at Teerth',
                        desc: isHi
                            ? 'शुभ मुहूर्त में आपके नाम-गोत्र से संपूर्ण वैदिक अनुष्ठान।'
                            : 'Vedic rituals conducted strictly per your astrological timings.',
                      ),
                      _stepRow(
                        num: '3',
                        title: isHi
                            ? 'व्हाट्सएप पर प्रत्यक्ष वीडियो प्रमाण'
                            : 'Video Proof Shared on WhatsApp',
                        desc: isHi
                            ? 'संकल्प व पूजा का व्यक्तिगत वीडियो व फोटो प्राप्त करें।'
                            : 'Receive recorded personal video & chadhava darshan photos.',
                      ),
                      _stepRow(
                        num: '4',
                        title: isHi
                            ? 'अभिमंत्रित महाप्रसाद घर तक'
                            : 'Holy Prasad Delivered to Home',
                        desc: isHi
                            ? 'अभिमंत्रित रक्षा सूत्र, भस्म व महाप्रसाद ३-५ दिनों में।'
                            : 'Energized Raksha Sutra, Vibhuti & Prasad shipped in 3-5 days.',
                        isLast: true,
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 20),

                // 7. FAQs
                if (_faqs != null && _faqs!.isNotEmpty) ...[
                  Text(
                    isHi ? 'प्रायः पूछे जाने वाले प्रश्न' : 'Frequently Asked Questions',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w800,
                      color: textPrimary,
                    ),
                  ),
                  const SizedBox(height: 8),
                  for (final f in _faqs!)
                    Container(
                      margin: const EdgeInsets.only(bottom: 8),
                      decoration: BoxDecoration(
                        color: cardBg,
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: cardBorder),
                      ),
                      child: ExpansionTile(
                        tilePadding:
                            const EdgeInsets.symmetric(horizontal: 14),
                        title: Text(
                          VedicHindiLocalizer.localizeFaq((f['question'] ?? f['title'] ?? 'Q').toString(), isHi),
                          style: TextStyle(
                            fontSize: 13.5,
                            fontWeight: FontWeight.w700,
                            color: textPrimary,
                          ),
                        ),
                        childrenPadding: const EdgeInsets.only(
                            left: 14, right: 14, bottom: 12),
                        children: [
                          Align(
                            alignment: Alignment.centerLeft,
                            child: Text(
                              VedicHindiLocalizer.localizeFaq(
                                  (f['answer'] ?? f['description'] ?? '').toString(),
                                  isHi),
                              style: TextStyle(
                                fontSize: 13,
                                color: textSecondary,
                                height: 1.4,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                ],

                const SizedBox(height: 30),
              ],
            ),
          ),
        ],
      ),
    );
  },
);
}

  Widget _trustItem({
    required IconData icon,
    required Color color,
    required String title,
    required String subtitle,
    required Color textPrimary,
    required Color textSecondary,
  }) {
    return Expanded(
      child: Column(
        children: [
          Icon(icon, size: 22, color: color),
          const SizedBox(height: 4),
          Text(
            title,
            style: TextStyle(
              fontSize: 11.5,
              fontWeight: FontWeight.w800,
              color: textPrimary,
            ),
            textAlign: TextAlign.center,
          ),
          Text(
            subtitle,
            style: TextStyle(
              fontSize: 9.5,
              fontWeight: FontWeight.w500,
              color: textSecondary,
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _packageCard(
    PujaPackage pkg,
    int index,
    Color cardBg,
    Color cardBorder,
    Color textPrimary,
    Color textSecondary, [
    bool isHi = false,
  ]) {
    final isSelected = _selectedPackageIndex == index;
    final isPopular = index == 1;

    return GestureDetector(
      onTap: () {
        setState(() {
          _selectedPackageIndex = index;
          _selectedPackage = pkg;
        });
      },
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: cardBg,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(
            color: isSelected
                ? const Color(0xFF059669)
                : cardBorder,
            width: isSelected ? 2 : 1,
          ),
          boxShadow: [
            if (isSelected)
              BoxShadow(
                color: const Color(0xFF059669).withValues(alpha: 0.12),
                blurRadius: 10,
                offset: const Offset(0, 4),
              ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(
                  isSelected
                      ? Icons.radio_button_checked_rounded
                      : Icons.radio_button_off_rounded,
                  color: isSelected
                      ? const Color(0xFF059669)
                      : const Color(0xFF94A3B8),
                  size: 20,
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    _localizePackageName(pkg.name?.toString(), isHi),
                    style: TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w800,
                      color: isSelected
                          ? const Color(0xFF059669)
                          : textPrimary,
                    ),
                  ),
                ),
                if (isPopular)
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 8, vertical: 2),
                    margin: const EdgeInsets.only(right: 8),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFEF3C7),
                      borderRadius: BorderRadius.circular(6),
                      border: Border.all(color: const Color(0xFFF59E0B)),
                    ),
                    child: Text(
                      isHi ? 'सर्वाधिक लोकप्रिय' : 'MOST POPULAR',
                      style: const TextStyle(
                        fontSize: 9.5,
                        fontWeight: FontWeight.w800,
                        color: Color(0xFFB45309),
                      ),
                    ),
                  ),
                Text(
                  '₹${pkg.priceValue.toStringAsFixed(0)}',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w900,
                    color: isSelected
                        ? const Color(0xFF059669)
                        : textPrimary,
                  ),
                ),
              ],
            ),
            if (pkg.inclusions != null && pkg.inclusions!.isNotEmpty) ...[
              Divider(height: 18, color: cardBorder.withValues(alpha: 0.5)),
              for (final inc in pkg.inclusions!) ...[
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Padding(
                      padding: EdgeInsets.only(top: 2),
                      child: Icon(Icons.check_circle_outline_rounded,
                          size: 14, color: Color(0xFF059669)),
                    ),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        _localizeInclusion(inc, isHi),
                        style: TextStyle(
                          fontSize: 12,
                          color: textSecondary,
                          height: 1.3,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
              ],
            ],
          ],
        ),
      ),
    );
  }

  Widget _stepRow({
    required String num,
    required String title,
    required String desc,
    bool isLast = false,
  }) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final textPrimary = isDark ? Colors.white : const Color(0xFF1E293B);
    final textSecondary = isDark ? Colors.white70 : const Color(0xFF64748B);
    final cardBorder = isDark ? CustomerTheme.cosmicBorderDark : const Color(0xFFE2E8F0);

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Column(
          children: [
            Container(
              width: 24,
              height: 24,
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: isDark ? const Color(0xFF451A03) : const Color(0xFFFEF3C7),
                shape: BoxShape.circle,
              ),
              child: Text(
                num,
                style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w800,
                  color: Color(0xFFB45309),
                ),
              ),
            ),
            if (!isLast)
              Container(
                width: 1.5,
                height: 36,
                color: cardBorder,
              ),
          ],
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: textPrimary,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                desc,
                style: TextStyle(
                  fontSize: 11.5,
                  color: textSecondary,
                  height: 1.3,
                ),
              ),
              const SizedBox(height: 12),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildVedicDescription(
    String raw,
    Color textPrimary,
    Color textSecondary, [
    bool isHi = false,
    String? title,
  ]) {
    final localized = VedicHindiLocalizer.localizeDescription(raw, title, isHi);
    if (localized.trim().isEmpty) return const SizedBox.shrink();
    if (!localized.contains('<') || !localized.contains('>')) {
      return Text(
        localized,
        style: TextStyle(
          fontSize: 13,
          color: textSecondary,
          height: 1.5,
        ),
      );
    }

    final widgets = <Widget>[];

    // Normalize newlines and clean structural wrapping
    final cleaned = localized
        .replaceAll(RegExp(r'<div[^>]*>', caseSensitive: false), '')
        .replaceAll(RegExp(r'<\/div>', caseSensitive: false), '\n')
        .replaceAll(RegExp(r'<ul[^>]*>', caseSensitive: false), '')
        .replaceAll(RegExp(r'<\/ul>', caseSensitive: false), '\n')
        .replaceAll(RegExp(r'<ol[^>]*>', caseSensitive: false), '')
        .replaceAll(RegExp(r'<\/ol>', caseSensitive: false), '\n')
        .replaceAll(RegExp(r'<br\s*\/?>', caseSensitive: false), '\n');

    final blockRegex = RegExp(
      r'<(h2|h3|p|li)[^>]*>(.*?)<\/\1>',
      dotAll: true,
      caseSensitive: false,
    );
    final matches = blockRegex.allMatches(cleaned).toList();

    if (matches.isEmpty) {
      final plainText = cleaned.replaceAll(RegExp(r'<[^>]*>'), '').trim();
      return Text(
        plainText,
        style: TextStyle(fontSize: 13, color: textSecondary, height: 1.5),
      );
    }

    for (final m in matches) {
      final tag = m.group(1)!.toLowerCase();
      final inner = m.group(2)!.trim();
      if (inner.isEmpty) continue;

      if (tag == 'h2') {
        widgets.add(Padding(
          padding: const EdgeInsets.only(top: 14, bottom: 6),
          child: Text(
            inner.replaceAll(RegExp(r'<[^>]*>'), ''),
            style: TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w800,
              color: textPrimary,
              height: 1.3,
            ),
          ),
        ));
      } else if (tag == 'h3') {
        widgets.add(Padding(
          padding: const EdgeInsets.only(top: 10, bottom: 4),
          child: Text(
            inner.replaceAll(RegExp(r'<[^>]*>'), ''),
            style: TextStyle(
              fontSize: 13.5,
              fontWeight: FontWeight.w700,
              color: textPrimary,
              height: 1.3,
            ),
          ),
        ));
      } else if (tag == 'li') {
        widgets.add(Padding(
          padding: const EdgeInsets.only(bottom: 6),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                margin: const EdgeInsets.only(top: 6, right: 8),
                width: 6,
                height: 6,
                decoration: const BoxDecoration(
                  color: CustomerTheme.brandSaffron,
                  shape: BoxShape.circle,
                ),
              ),
              Expanded(
                child: Text.rich(
                  TextSpan(
                    children: _parseRichSpans(inner, textPrimary, textSecondary),
                  ),
                ),
              ),
            ],
          ),
        ));
      } else {
        widgets.add(Padding(
          padding: const EdgeInsets.only(bottom: 8),
          child: Text.rich(
            TextSpan(
              children: _parseRichSpans(inner, textPrimary, textSecondary),
            ),
          ),
        ));
      }
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: widgets,
    );
  }

  List<InlineSpan> _parseRichSpans(
    String html,
    Color textPrimary,
    Color textSecondary,
  ) {
    final spans = <InlineSpan>[];
    final tokenRegex = RegExp(
      r'<(strong|b)[^>]*>(.*?)<\/\1>|<[^>]*>',
      dotAll: true,
      caseSensitive: false,
    );

    int lastIndex = 0;
    for (final match in tokenRegex.allMatches(html)) {
      if (match.start > lastIndex) {
        final text = html.substring(lastIndex, match.start);
        if (text.isNotEmpty) {
          spans.add(TextSpan(
            text: text,
            style: TextStyle(fontSize: 13, color: textSecondary, height: 1.45),
          ));
        }
      }

      final boldTag = match.group(1);
      if (boldTag != null) {
        final boldContent = match.group(2) ?? '';
        final cleanBold = boldContent.replaceAll(RegExp(r'<[^>]*>'), '');
        spans.add(TextSpan(
          text: cleanBold,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w700,
            color: textPrimary,
            height: 1.45,
          ),
        ));
      }

      lastIndex = match.end;
    }

    if (lastIndex < html.length) {
      final trailing = html.substring(lastIndex);
      if (trailing.isNotEmpty) {
        spans.add(TextSpan(
          text: trailing,
          style: TextStyle(fontSize: 13, color: textSecondary, height: 1.45),
        ));
      }
    }

    return spans;
  }
}


