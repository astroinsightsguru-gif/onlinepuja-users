import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:op_shared/op_shared.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:webview_flutter/webview_flutter.dart';
import '../../theme/customer_theme.dart';

/// Configuration for featured sacred aartis with dedicated ₹11 pop-up diya offering.
class SacredAartiOption {
  final String id;
  final String feedId;
  final String title;
  final String hindiTitle;
  final String timing;
  final String deity;
  final String temple;
  final String streamUrl;
  final String? portalUrl;
  final String badge;
  final String mantra;
  final List<String> lyrics;

  const SacredAartiOption({
    required this.id,
    required this.feedId,
    required this.title,
    required this.hindiTitle,
    required this.timing,
    required this.deity,
    required this.temple,
    required this.streamUrl,
    this.portalUrl,
    required this.badge,
    required this.mantra,
    required this.lyrics,
  });
}

/// Live Mandir Darshan & Virtual Sanctum Hub.
/// Allows devotees worldwide to witness 24/7 official sanctum feeds of sacred Jyotirlingas & Dhams,
/// perform virtual Aarti rituals (Diya, Temple Bell, Pushpanjali), and offer sacred Chadhava.
class LiveDarshanScreen extends StatefulWidget {
  final String? initialFeedId;
  const LiveDarshanScreen({super.key, this.initialFeedId});

  static const route = '/live-darshan';

  @override
  State<LiveDarshanScreen> createState() => _LiveDarshanScreenState();
}

class _LiveDarshanScreenState extends State<LiveDarshanScreen>
    with SingleTickerProviderStateMixin {
  late TempleFeed _selectedFeed;
  int _flowerCount = 0;
  bool _diyaLit = false;
  int _bellChimes = 0;
  final List<Offset> _floatingFlowers = [];
  Timer? _flowerCleanup;
  Timer? _tickerTimer;
  int _tickerIndex = 0;
  String _selectedCategory = 'All';

  bool _showDiyaCelebration = false;
  String _celebrationTitle = '';
  String _celebrationDevotee = '';
  Timer? _celebrationTimer;

  WebViewController? _webViewController;
  bool _isInAppStreaming = false;
  bool _isPlayerBuffering = false;

  static const List<SacredAartiOption> _sacredAartis = [
    SacredAartiOption(
      id: 'kashi_mangala',
      feedId: 'kashi',
      title: 'Shri Kashi Vishwanath Mangala Aarti',
      hindiTitle: 'श्री काशी विश्वनाथ मंगला आरती',
      timing: 'ब्रह्म मुहूर्त 03:00 AM – 04:00 AM',
      deity: 'द्वादश ज्योतिर्लिंग भगवान विश्वनाथ',
      temple: 'काशी विश्वनाथ धाम, वाराणसी',
      streamUrl: 'https://www.youtube.com/@ShriKashiVishwanathTempleTrust/live',
      portalUrl: 'https://shrikashivishwanath.org/frontend/home/livedarshan',
      badge: '🔱 प्रथम ज्योतिर्लिंग मंगला आरती',
      mantra: 'ॐ नमः पार्वती पतये हर हर महादेव • कर्पूरगौरं करुणावतारम्',
      lyrics: [
        'कर्पूरगौरं करुणावतारं संसारसारम् भुजगेन्द्रहारम्।',
        'सदावसन्तं हृदयारविन्दे भवं भवानीसहितं नमामि॥',
        'जय शिव ओंकारा, ॐ जय शिव ओंकारा।',
        'ब्रह्मा, विष्णु, सदाशिव, अर्द्धांगी धारा॥',
        'एकानन चतुरानन पञ्चानन राजे।',
        'हंसासन गरुड़ासन वृषवाहन साजे॥',
        'दो भुज चारु चतुर्भुज दशभुज अति सोहे।',
        'तीनों रूप निरखते त्रिभुवन जन मोहे॥',
        'अक्षमाला वनमाला मुण्डमाला धारी।',
        'त्रिपुरारी कंसारी कर माला धारी॥',
        'काशी में विश्वनाथ विराजे, नंदी ब्रह्मचारी।',
        'नित उठ दर्शन पावत, महिमा अति भारी॥',
      ],
    ),
    SacredAartiOption(
      id: 'ganga_sandhya',
      feedId: 'ganga_aarti',
      title: 'Dashashwamedh Maha Ganga Aarti',
      hindiTitle: 'दशाश्वमेध घाट महा गंगा आरती',
      timing: 'दैनिक सूर्यास्त 06:30 PM – 07:45 PM',
      deity: 'पतित पावनी मां गंगा',
      temple: 'दशाश्वमेध घाट, वाराणसी',
      streamUrl: 'https://www.youtube.com/@GangaAartiVaranasiOfficial/live',
      portalUrl: 'https://www.gangasevanidhi.org/',
      badge: '🌊 विश्व प्रसिद्ध गंगा आरती',
      mantra: 'ॐ जय गंगे माता, श्री गंगे माता • पतित पावनी गंगे हर हर महादेव',
      lyrics: [
        'ॐ जय गंगे माता, मैया जय गंगे माता।',
        'जो नर तुमको ध्याता, मनवांछित फल पाता॥',
        'चन्द्र सी जोत तुम्हारी, जल निर्मल आता।',
        'शरण पड़े जो तेरी, सो नर तर जाता॥',
        'पुत्र सगर के तारे, सब जग को ज्ञाता।',
        'कृपा दृष्टि तुम्हारी, त्रिभुवन सुखदाता॥',
        'एक ही बार जो नर, मंजन है करता।',
        'यम की त्रास मिटाकर, सुरलोक में जाता॥',
        'आरती मात तुम्हारी, जो कोई नर गावे।',
        'काशी दशाश्वमेध तट, सुख-सम्पत्ति पावे॥',
      ],
    ),
  ];

  final List<String> _devoteeOfferings = [
    '🪔 Rajesh Sharma (Vatsa) offered Mangala Aarti Diya ₹11 • Just now',
    '🕉️ Amit Pandey (Kashyap) offered Kashi Vishwanath Deepam ₹11 • 14s ago',
    '🌊 Sunita Verma (Bharadwaj) offered Ganga Aarti Diya ₹11 • 28s ago',
    '🌺 Priya Nair (Bengaluru) offered Sacred Pushpa Mala ₹51 • 45s ago',
    '🔔 Vikramaditya Singh (Jaipur) rang Sankalp Ghanta ₹11 • 1m ago',
    '🥥 Ananya Deshmukh (Pune) offered Shreefal & Bhasma ₹101 • 2m ago',
  ];

  late AnimationController _pulseController;

  @override
  void initState() {
    super.initState();
    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 2),
    )..repeat(reverse: true);

    final feeds = GrowthAiOsService.instance.activeTempleFeeds;
    if (widget.initialFeedId != null) {
      _selectedFeed = feeds.firstWhere(
        (f) => f.id == widget.initialFeedId,
        orElse: () => feeds.isNotEmpty
            ? feeds.first
            : GrowthAiOsService.defaultTempleFeeds.first,
      );
    } else {
      _selectedFeed = feeds.isNotEmpty
          ? feeds.first
          : GrowthAiOsService.defaultTempleFeeds.first;
    }

    _tickerTimer = Timer.periodic(const Duration(seconds: 4), (_) {
      if (mounted && _devoteeOfferings.isNotEmpty) {
        setState(() {
          _tickerIndex = (_tickerIndex + 1) % _devoteeOfferings.length;
        });
      }
    });
  }

  @override
  void dispose() {
    _pulseController.dispose();
    _flowerCleanup?.cancel();
    _tickerTimer?.cancel();
    _celebrationTimer?.cancel();
    super.dispose();
  }

  List<TempleFeed> get _filteredFeeds {
    final feeds = GrowthAiOsService.instance.activeTempleFeeds;
    if (_selectedCategory == 'All') return feeds;
    if (_selectedCategory == 'Jyotirlinga') {
      return feeds
          .where((f) =>
              f.name.toLowerCase().contains('jyotirlinga') ||
              f.description.toLowerCase().contains('jyotirlinga'))
          .toList();
    }
    if (_selectedCategory == 'Ganga') {
      return feeds
          .where((f) =>
              f.name.toLowerCase().contains('ganga') ||
              f.location.toLowerCase().contains('varanasi'))
          .toList();
    }
    return feeds;
  }

  void _startInAppLiveStream([String? customUrl]) {
    HapticFeedback.mediumImpact();
    final url = customUrl ?? _selectedFeed.streamUrl;
    final embedUrl = _getEmbedUrl(url);

    _webViewController = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(Colors.black)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (_) {
            if (mounted) setState(() => _isPlayerBuffering = true);
          },
          onPageFinished: (_) {
            if (mounted) setState(() => _isPlayerBuffering = false);
          },
        ),
      )
      ..loadHtmlString('''
        <!DOCTYPE html>
        <html>
        <head>
          <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
          <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body, html { width: 100%; height: 100%; background: #000; overflow: hidden; display: flex; align-items: center; justify-content: center; }
            iframe { width: 100%; height: 100%; border: none; }
          </style>
        </head>
        <body>
          <iframe 
            src="$embedUrl" 
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
            allowfullscreen>
          </iframe>
        </body>
        </html>
      ''');

    setState(() => _isInAppStreaming = true);
  }

  void _stopInAppLiveStream() {
    setState(() {
      _isInAppStreaming = false;
      _webViewController = null;
    });
  }

  String _getEmbedUrl(String streamUrl) {
    if (_selectedFeed.id == 'kashi') {
      return 'https://www.youtube.com/embed/ypTPyVsbV3Y?autoplay=1&mute=0&playsinline=1&controls=1&rel=0';
    }
    if (_selectedFeed.id == 'ganga_aarti') {
      return 'https://www.youtube.com/embed/live_stream?channel=UCj8Y7tM70LgXm5j3jQ3_xZw&autoplay=1&mute=0&playsinline=1&controls=1&rel=0';
    }
    if (streamUrl.contains('youtube.com') || streamUrl.contains('youtu.be')) {
      final regExp = RegExp(r'(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})');
      final match = regExp.firstMatch(streamUrl);
      if (match != null) {
        return 'https://www.youtube.com/embed/${match.group(1)}?autoplay=1&mute=0&playsinline=1&controls=1&rel=0';
      }
    }
    return streamUrl;
  }

  Future<void> _openLiveStream({String? customUrl}) async {
    HapticFeedback.mediumImpact();
    final target = customUrl ?? _selectedFeed.streamUrl;
    final uri = Uri.parse(target);
    try {
      final launched =
          await launchUrl(uri, mode: LaunchMode.externalApplication);
      if (!launched && mounted) {
        await launchUrl(uri, mode: LaunchMode.inAppBrowserView);
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            behavior: SnackBarBehavior.floating,
            content: Text('Opening ${_selectedFeed.name} live sanctum stream…'),
          ),
        );
      }
    }
  }

  void _offerFlowers() {
    HapticFeedback.lightImpact();
    setState(() {
      _flowerCount++;
      _floatingFlowers.add(Offset(
        0.2 + (0.6 * (DateTime.now().millisecond / 1000)),
        0.8,
      ));
    });

    _flowerCleanup?.cancel();
    _flowerCleanup = Timer(const Duration(seconds: 4), () {
      if (mounted) setState(() => _floatingFlowers.clear());
    });

    ScaffoldMessenger.of(context).hideCurrentSnackBar();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        duration: const Duration(milliseconds: 1400),
        behavior: SnackBarBehavior.floating,
        backgroundColor: const Color(0xFFD97706),
        content: Row(
          children: [
            const Icon(Icons.local_florist_rounded, color: Colors.white, size: 18),
            const SizedBox(width: 8),
            Text(
              'Offered Pushpa to ${_selectedFeed.deity} (Count: $_flowerCount)',
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
          ],
        ),
      ),
    );
  }

  void _ringBell() {
    HapticFeedback.heavyImpact();
    setState(() => _bellChimes++);

    ScaffoldMessenger.of(context).hideCurrentSnackBar();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        duration: const Duration(milliseconds: 1400),
        behavior: SnackBarBehavior.floating,
        backgroundColor: const Color(0xFFB45309),
        content: Row(
          children: [
            const Icon(Icons.notifications_active_rounded, color: Colors.amberAccent, size: 18),
            const SizedBox(width: 8),
            Text(
              'Sacred Ghanta Naad awakened at ${_selectedFeed.name}!',
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
          ],
        ),
      ),
    );
  }

  void _toggleDiya() {
    _showDiyaLightingSheet();
  }

  void _showDiyaLightingSheet({String? initialAartiId}) {
    HapticFeedback.lightImpact();
    final user = SessionStore.instance.user;
    final initialName = (user != null && (user.name ?? '').isNotEmpty && user.name != 'User') ? user.name! : '';
    final nameCtrl = TextEditingController(text: initialName);
    final gotraCtrl = TextEditingController(text: 'कश्यप');
    String selectedAartiId = initialAartiId ?? (_selectedFeed.id == 'ganga_aarti' ? 'ganga_sandhya' : 'kashi_mangala');
    String selectedIntention = 'आरोग्य एवं दीर्घायु';
    bool isSubmitting = false;

    final intentions = [
      '🌿 आरोग्य एवं दीर्घायु',
      '🕉️ सुख-समृद्धि एवं शांति',
      '🔱 शिव-गंगा कृपा व मोक्ष',
      '🙏 पितृ तृप्ति एवं कुल रक्षा',
    ];

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheetState) {
          final currentAarti = _sacredAartis.firstWhere(
            (a) => a.id == selectedAartiId,
            orElse: () => _sacredAartis.first,
          );

          return Container(
            padding: EdgeInsets.only(
              top: 18,
              left: 20,
              right: 20,
              bottom: MediaQuery.of(ctx).viewInsets.bottom + 24,
            ),
            decoration: const BoxDecoration(
              color: Color(0xFF0F172A),
              borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
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
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(
                            colors: [Color(0xFFEA580C), Color(0xFFD97706)],
                          ),
                          borderRadius: BorderRadius.circular(12),
                          boxShadow: [
                            BoxShadow(
                              color: const Color(0xFFD97706).withValues(alpha: 0.4),
                              blurRadius: 8,
                            ),
                          ],
                        ),
                        child: const Text('🪔', style: TextStyle(fontSize: 22)),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'पावन दीपदान संकल्प · ₹11',
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 17,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            Text(
                              'काशी विश्वनाथ मंगला आरती एवं महा गंगा आरती',
                              style: TextStyle(
                                color: Colors.amber.shade200,
                                fontSize: 11.5,
                              ),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ],
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: const Color(0xFF10B981).withValues(alpha: 0.2),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: const Color(0xFF10B981)),
                        ),
                        child: const Text(
                          '₹11 शगुन',
                          style: TextStyle(
                            color: Color(0xFF34D399),
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 18),
                  const Text(
                    'पावन आरती का चयन करें:',
                    style: TextStyle(
                      color: Colors.white70,
                      fontSize: 12.5,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 8),
                  ..._sacredAartis.map((aarti) {
                    final isSel = aarti.id == selectedAartiId;
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: InkWell(
                        onTap: () => setSheetState(() => selectedAartiId = aarti.id),
                        borderRadius: BorderRadius.circular(12),
                        child: Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: isSel
                                ? const Color(0xFF1E293B)
                                : const Color(0xFF151C2C),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: isSel
                                  ? const Color(0xFFD97706)
                                  : Colors.white12,
                              width: isSel ? 1.8 : 1,
                            ),
                          ),
                          child: Row(
                            children: [
                              Container(
                                width: 20,
                                height: 20,
                                margin: const EdgeInsets.only(right: 10, left: 4),
                                decoration: BoxDecoration(
                                  shape: BoxShape.circle,
                                  border: Border.all(
                                    color: isSel
                                        ? const Color(0xFFD97706)
                                        : Colors.white38,
                                    width: isSel ? 6 : 2,
                                  ),
                                ),
                              ),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      children: [
                                        Expanded(
                                          child: Text(
                                            aarti.hindiTitle,
                                            style: TextStyle(
                                              color: isSel ? Colors.white : Colors.white70,
                                              fontSize: 13,
                                              fontWeight: isSel ? FontWeight.bold : FontWeight.w600,
                                            ),
                                          ),
                                        ),
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                          decoration: BoxDecoration(
                                            color: const Color(0xFFD97706).withValues(alpha: 0.15),
                                            borderRadius: BorderRadius.circular(6),
                                          ),
                                          child: Text(
                                            aarti.timing.split('(').first.trim(),
                                            style: const TextStyle(
                                              color: Color(0xFFFBBF24),
                                              fontSize: 9.5,
                                              fontWeight: FontWeight.bold,
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      '${aarti.temple} • ${aarti.deity}',
                                      style: const TextStyle(
                                        color: Colors.white54,
                                        fontSize: 10.5,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    );
                  }),
                  const SizedBox(height: 12),
                  TextField(
                    controller: nameCtrl,
                    style: const TextStyle(color: Colors.white),
                    decoration: InputDecoration(
                      labelText: 'यजमान / आपका नाम',
                      labelStyle: const TextStyle(color: Colors.white60),
                      hintText: 'उदा. राजेश शर्मा',
                      hintStyle: const TextStyle(color: Colors.white30),
                      prefixIcon: const Icon(Icons.person_rounded, color: Color(0xFFD97706), size: 18),
                      filled: true,
                      fillColor: const Color(0xFF1E293B),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                    ),
                  ),
                  const SizedBox(height: 10),
                  TextField(
                    controller: gotraCtrl,
                    style: const TextStyle(color: Colors.white),
                    decoration: InputDecoration(
                      labelText: 'गोत्र (यदि ज्ञात न हो तो कश्यप रहने दें)',
                      labelStyle: const TextStyle(color: Colors.white60),
                      hintText: 'कश्यप / भारद्वाज / पराशर',
                      hintStyle: const TextStyle(color: Colors.white30),
                      prefixIcon: const Icon(Icons.family_restroom_rounded, color: Color(0xFFD97706), size: 18),
                      filled: true,
                      fillColor: const Color(0xFF1E293B),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                    ),
                  ),
                  const SizedBox(height: 14),
                  const Text(
                    'दीपदान संकल्प मनोकामना:',
                    style: TextStyle(
                      color: Colors.white70,
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: intentions.map((it) {
                      final isSel = selectedIntention == it;
                      return ChoiceChip(
                        label: Text(
                          it,
                          style: TextStyle(
                            color: isSel ? Colors.black : Colors.white70,
                            fontSize: 11,
                            fontWeight: isSel ? FontWeight.bold : FontWeight.normal,
                          ),
                        ),
                        selected: isSel,
                        selectedColor: const Color(0xFFFBBF24),
                        backgroundColor: const Color(0xFF1E293B),
                        onSelected: (val) {
                          if (val) setSheetState(() => selectedIntention = it);
                        },
                      );
                    }).toList(),
                  ),
                  const SizedBox(height: 14),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    decoration: BoxDecoration(
                      color: const Color(0xFFD97706).withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: const Color(0xFFD97706).withValues(alpha: 0.3)),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.verified_rounded, size: 14, color: Color(0xFFFBBF24)),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            'यह पावन दीप आपके नाम व गोत्र से गर्भगृह में प्रज्वलित होगा।',
                            style: TextStyle(
                              fontSize: 11,
                              color: Colors.amber.shade200,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 18),
                  SizedBox(
                    width: double.infinity,
                    child: FilledButton(
                      style: FilledButton.styleFrom(
                        backgroundColor: const Color(0xFFD97706),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                      ),
                      onPressed: isSubmitting
                          ? null
                          : () async {
                              final yajman = nameCtrl.text.trim().isNotEmpty
                                  ? nameCtrl.text.trim()
                                  : (initialName.isNotEmpty ? initialName : 'Devotee');
                              final gotra = gotraCtrl.text.trim().isNotEmpty
                                  ? gotraCtrl.text.trim()
                                  : 'कश्यप';

                              setSheetState(() => isSubmitting = true);
                              HapticFeedback.heavyImpact();

                              try {
                                await ApiClient.instance.post('/annadaan/donate', body: {
                                  if (user?.id != null && user!.id! > 0) 'userId': user.id,
                                  'causeTitle': 'Pawan Deepam ₹11: ${currentAarti.hindiTitle} ($selectedIntention)',
                                  'devoteeName': yajman,
                                  'gotra': gotra,
                                  'amount': 11,
                                });
                              } catch (_) {}

                              if (ctx.mounted) {
                                Navigator.pop(ctx);
                              }
                              if (!mounted) return;

                              // Switch feed to the chosen Aarti temple if different
                              final matchedFeed = GrowthAiOsService.instance.activeTempleFeeds.firstWhere(
                                (f) => f.id == currentAarti.feedId,
                                orElse: () => _selectedFeed,
                              );

                              setState(() {
                                _selectedFeed = matchedFeed;
                                _diyaLit = true;
                                _showDiyaCelebration = true;
                                _celebrationDevotee = yajman;
                                _celebrationTitle = currentAarti.hindiTitle;
                                _devoteeOfferings.insert(
                                  0,
                                  '🪔 $yajman ($gotra गोत्र) ने ${currentAarti.hindiTitle} में पावन दीप (₹11) प्रज्वलित किया • Just now',
                                );
                                _tickerIndex = 0;
                              });

                              _celebrationTimer?.cancel();
                              _celebrationTimer = Timer(const Duration(seconds: 4), () {
                                if (mounted) {
                                  setState(() => _showDiyaCelebration = false);
                                }
                              });

                              _showBlessingDialog(
                                yajman: yajman,
                                gotra: gotra,
                                aarti: currentAarti,
                                intention: selectedIntention,
                              );
                            },
                      child: isSubmitting
                          ? const SizedBox(
                              height: 18,
                              width: 18,
                              child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                            )
                          : const Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Text('🪔', style: TextStyle(fontSize: 16)),
                                SizedBox(width: 8),
                                Text(
                                  'पावन दीप प्रज्वलित करें · ₹11',
                                  style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                                ),
                              ],
                            ),
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

  void _showBlessingDialog({
    required String yajman,
    required String gotra,
    required SacredAartiOption aarti,
    required String intention,
  }) {
    showDialog(
      context: context,
      builder: (ctx) => Dialog(
        backgroundColor: const Color(0xFF0F172A),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: const Color(0xFFD97706).withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                  border: Border.all(color: const Color(0xFFD97706), width: 1.5),
                ),
                child: const Text('🪔', style: TextStyle(fontSize: 42)),
              ),
              const SizedBox(height: 14),
              const Text(
                'हर हर महादेव! दीपदान संपन्न 🙏',
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 17,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                '$yajman जी ($gotra गोत्र) का ₹11 पावन दीप ${aarti.hindiTitle} में अर्पित किया गया।',
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: Colors.white70,
                  fontSize: 12.5,
                  height: 1.4,
                ),
              ),
              const SizedBox(height: 6),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                decoration: BoxDecoration(
                  color: const Color(0xFF1E293B),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  'संकल्प: $intention',
                  style: const TextStyle(color: Color(0xFFFBBF24), fontSize: 11, fontWeight: FontWeight.w600),
                ),
              ),
              const SizedBox(height: 20),
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  style: FilledButton.styleFrom(
                    backgroundColor: const Color(0xFF25D366),
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    padding: const EdgeInsets.symmetric(vertical: 12),
                  ),
                  onPressed: () {
                    final text = SocialContentGenerator.formatDeepamShare(
                      devoteeName: yajman,
                      aartiName: aarti.hindiTitle,
                      gotra: gotra,
                      streamUrl: aarti.streamUrl,
                    );
                    launchUrl(
                      SocialContentGenerator.buildWhatsAppUrl(text),
                      mode: LaunchMode.externalApplication,
                    );
                  },
                  icon: const Icon(Icons.share_rounded, size: 16),
                  label: const Text(
                    'व्हाट्सएप पर आशीर्वाद शेयर करें',
                    style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold),
                  ),
                ),
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      style: OutlinedButton.styleFrom(
                        foregroundColor: Colors.amberAccent,
                        side: const BorderSide(color: Color(0xFFD97706)),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        padding: const EdgeInsets.symmetric(vertical: 10),
                      ),
                      onPressed: () {
                        Navigator.pop(ctx);
                        _openLiveStream(customUrl: aarti.streamUrl);
                      },
                      icon: const Icon(Icons.play_circle_fill_rounded, size: 16),
                      label: const Text('लाइव आरती देखें', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold)),
                    ),
                  ),
                  if (aarti.portalUrl != null) ...[
                    const SizedBox(width: 8),
                    Expanded(
                      child: OutlinedButton.icon(
                        style: OutlinedButton.styleFrom(
                          foregroundColor: Colors.white70,
                          side: const BorderSide(color: Colors.white24),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          padding: const EdgeInsets.symmetric(vertical: 10),
                        ),
                        onPressed: () {
                          Navigator.pop(ctx);
                          _openLiveStream(customUrl: aarti.portalUrl);
                        },
                        icon: const Icon(Icons.public_rounded, size: 15),
                        label: const Text('ट्रस्ट पोर्टल', style: TextStyle(fontSize: 11.5)),
                      ),
                    ),
                  ],
                ],
              ),
              const SizedBox(height: 4),
              TextButton(
                onPressed: () => Navigator.pop(ctx),
                child: const Text('वापस दर्शन करें', style: TextStyle(color: Colors.white54, fontSize: 12)),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showChadhavaSheet() {
    final tiers = GrowthAiOsService.instance.growthConfig.chadhavaTiers;
    int selectedAmount = _selectedFeed.chadhavaMinPrice;
    final user = SessionStore.instance.user;
    final initialName = (user != null && (user.name ?? '').isNotEmpty && user.name != 'User') ? user.name! : '';
    final nameCtrl = TextEditingController(text: initialName);
    final gotraCtrl = TextEditingController();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheetState) => Container(
          padding: EdgeInsets.only(
            top: 20,
            left: 20,
            right: 20,
            bottom: MediaQuery.of(ctx).viewInsets.bottom + 24,
          ),
          decoration: const BoxDecoration(
            color: Color(0xFF0F172A),
            borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
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
                    color: Colors.white24,
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
                      color: CustomerTheme.brandGold.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(Icons.volunteer_activism_rounded,
                        color: CustomerTheme.brandGold, size: 22),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Offer Sacred Chadhava',
                          style: const TextStyle(
                              color: Colors.white,
                              fontSize: 17,
                              fontWeight: FontWeight.bold),
                        ),
                        Text(
                          _selectedFeed.name,
                          style: const TextStyle(
                              color: Colors.white60, fontSize: 12),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),
              const Text(
                'Select Seva Dakshina Tier:',
                style: TextStyle(
                    color: Colors.white70,
                    fontSize: 13,
                    fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 10),
              Wrap(
                spacing: 10,
                runSpacing: 10,
                children: tiers.map((amt) {
                  final isSel = selectedAmount == amt;
                  return ChoiceChip(
                    label: Text('₹$amt',
                        style: TextStyle(
                            color: isSel ? Colors.black : Colors.white,
                            fontWeight: FontWeight.bold)),
                    selected: isSel,
                    selectedColor: CustomerTheme.brandGold,
                    backgroundColor: const Color(0xFF1E293B),
                    onSelected: (val) {
                      if (val) setSheetState(() => selectedAmount = amt);
                    },
                  );
                }).toList(),
              ),
              const SizedBox(height: 16),
              TextField(
                controller: nameCtrl,
                style: const TextStyle(color: Colors.white),
                decoration: InputDecoration(
                  labelText: 'Devotee / Yajman Name',
                  labelStyle: const TextStyle(color: Colors.white60),
                  filled: true,
                  fillColor: const Color(0xFF1E293B),
                  border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: gotraCtrl,
                style: const TextStyle(color: Colors.white),
                decoration: InputDecoration(
                  labelText: 'Gotra (Optional)',
                  labelStyle: const TextStyle(color: Colors.white60),
                  filled: true,
                  fillColor: const Color(0xFF1E293B),
                  border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 20),
              SizedBox(
                width: double.infinity,
                child: FilledButton(
                  style: FilledButton.styleFrom(
                    backgroundColor: CustomerTheme.brandGold,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12)),
                  ),
                  onPressed: () async {
                    final yajman = nameCtrl.text.trim().isNotEmpty
                        ? nameCtrl.text.trim()
                        : (initialName.isNotEmpty ? initialName : 'Devotee');
                    final gotra = gotraCtrl.text.trim().isNotEmpty
                        ? gotraCtrl.text.trim()
                        : 'Kashyap';

                    Navigator.pop(ctx);
                    HapticFeedback.heavyImpact();

                    try {
                      final res = await ApiClient.instance.post('/annadaan/donate', body: {
                        if (user?.id != null && user!.id! > 0) 'userId': user.id,
                        'causeTitle': 'Sanctum Chadhava: ${_selectedFeed.name}',
                        'devoteeName': yajman,
                        'gotra': gotra,
                        'amount': selectedAmount,
                      });

                      final orderId = (res is Map && res['orderId'] != null)
                          ? res['orderId']
                          : 'OP-SANCTUM';

                      if (mounted) {
                        setState(() {
                          _devoteeOfferings.insert(0, '🕉️ $yajman offered Sacred Chadhava ₹$selectedAmount at ${_selectedFeed.name} • Just now');
                          _tickerIndex = 0;
                        });
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(
                            backgroundColor: const Color(0xFF10B981),
                            behavior: SnackBarBehavior.floating,
                            content: Row(
                              children: [
                                const Icon(Icons.check_circle_rounded, color: Colors.white),
                                const SizedBox(width: 10),
                                Expanded(
                                  child: Text(
                                    'Sacred Chadhava of ₹$selectedAmount recorded for $yajman! ($orderId)',
                                    style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.white),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        );
                      }
                    } catch (_) {
                      if (mounted) {
                        setState(() {
                          _devoteeOfferings.insert(0, '🕉️ $yajman offered Sacred Chadhava ₹$selectedAmount at ${_selectedFeed.name} • Just now');
                          _tickerIndex = 0;
                        });
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(
                            backgroundColor: const Color(0xFF10B981),
                            behavior: SnackBarBehavior.floating,
                            content: Text(
                              'Sacred Chadhava of ₹$selectedAmount accepted for $yajman at ${_selectedFeed.name}.',
                              style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.white),
                            ),
                          ),
                        );
                      }
                    }
                  },
                  child: Text(
                    'Confirm & Offer ₹$selectedAmount',
                    style: const TextStyle(
                        fontSize: 15, fontWeight: FontWeight.bold),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final allFeeds = _filteredFeeds;

    return Scaffold(
      backgroundColor: isDark ? const Color(0xFF090D16) : const Color(0xFFF8FAFC),
      appBar: AppBar(
        elevation: 0,
        backgroundColor: isDark ? const Color(0xFF0F172A) : Colors.white,
        foregroundColor: isDark ? Colors.white : const Color(0xFF0F172A),
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFFD97706), Color(0xFFB45309)],
                ),
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(Icons.temple_hindu_rounded,
                  color: Colors.white, size: 18),
            ),
            const SizedBox(width: 10),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                RichText(
                  text: TextSpan(
                    children: [
                      TextSpan(
                        text: GrowthAiOsService.instance.brandConfig.brandName,
                        style: TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.w900,
                          color: isDark ? Colors.white : const Color(0xFF0F172A),
                        ),
                      ),
                      const TextSpan(
                        text: ' • Live Mandir',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: Color(0xFFD97706),
                        ),
                      ),
                    ],
                  ),
                ),
                Text(
                  '24/7 Sanctum Feeds & Sacred Aarti',
                  style: TextStyle(
                    fontSize: 10.5,
                    color: isDark ? Colors.white60 : Colors.black54,
                  ),
                ),
              ],
            ),
          ],
        ),
        actions: [
          IconButton(
            tooltip: 'Share Live Darshan',
            icon: const Icon(Icons.share_outlined, size: 20),
            onPressed: () {
              SacredShareSheet.show(
                context,
                title: 'Share Sacred Darshan',
                subtitle: _selectedFeed.name,
                shareText: SocialContentGenerator.formatDarshanShare(
                  templeName: _selectedFeed.name,
                  deity: _selectedFeed.deity,
                  timing: _selectedFeed.timing,
                  streamUrl: _selectedFeed.streamUrl,
                ),
                shareUrl: _selectedFeed.streamUrl,
                category: 'LiveDarshan',
              );
            },
          ),
        ],
      ),
      body: ListView(
        padding: EdgeInsets.zero,
        children: [
          // 1. Hero Live Broadcast Video Screen
          _buildLiveBroadcastHero(context, isDark),

          // 1.5 Live Devotee Sankalp & Chadhava Social Proof Ticker
          _buildLiveOfferingTicker(isDark),

          // 1.8 Interactive Digital Chadhava Micro-Offerings Bar
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
            child: _buildDigitalChadhavaMicroBar(isDark),
          ),

          // 2. Sanctum Information & Chadhava CTA
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            child: _buildSanctumDetailsCard(isDark),
          ),

          // 3. Category Filter Tabs
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: _buildCategoryFilters(isDark),
          ),

          const SizedBox(height: 12),

          // 4. Sacred Shrines Carousel / Grid
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Sacred Shrines & Jyotirlingas',
                  style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                ),
                Text(
                  '${allFeeds.length} Shrines Live',
                  style: const TextStyle(
                      fontSize: 11.5,
                      fontWeight: FontWeight.w600,
                      color: Color(0xFFD97706)),
                ),
              ],
            ),
          ),

          const SizedBox(height: 10),

          // Horizontal Temple Carousel
          SizedBox(
            height: 180,
            child: ListView.separated(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              scrollDirection: Axis.horizontal,
              itemCount: allFeeds.length,
              separatorBuilder: (_, _) => const SizedBox(width: 12),
              itemBuilder: (context, i) {
                final feed = allFeeds[i];
                final isSelected = feed.id == _selectedFeed.id;
                return _buildTempleThumbnailCard(feed, isSelected, isDark);
              },
            ),
          ),

          const SizedBox(height: 20),

          // 4.5 Featured Sacred Aartis (Kashi Vishwanath Mangala & Dashashwamedh Ganga Aarti) - ₹11 Pop-up Diya
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: _buildFeaturedAartisCard(isDark),
          ),

          const SizedBox(height: 20),

          // 5. Daily Aarti Schedule Guide
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: _buildAartiScheduleCard(isDark),
          ),

          const SizedBox(height: 30),
        ],
      ),
    );
  }

  // =========================================================================
  // HERO LIVE BROADCAST
  // =========================================================================
  Widget _buildLiveBroadcastHero(BuildContext context, bool isDark) {
    return Container(
      width: double.infinity,
      height: 250,
      color: Colors.black,
      child: Stack(
        fit: StackFit.expand,
        children: [
          // 1. Video Player Container: In-App Live Stream vs Sanctum Snapshot
          if (_isInAppStreaming && _webViewController != null)
            Stack(
              fit: StackFit.expand,
              children: [
                WebViewWidget(controller: _webViewController!),
                if (_isPlayerBuffering)
                  const Center(
                    child: CircularProgressIndicator(
                      color: Color(0xFFD97706),
                      strokeWidth: 2.5,
                    ),
                  ),
              ],
            )
          else ...[
            Image.network(
              _selectedFeed.thumbnailUrl,
              fit: BoxFit.cover,
              errorBuilder: (_, _, _) => Container(
                color: const Color(0xFF1E293B),
                child: const Center(
                  child: Icon(Icons.temple_hindu_rounded,
                      size: 64, color: Colors.white38),
                ),
              ),
            ),
            // Gradient Vignette Overlay
            Container(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [
                    Colors.black.withValues(alpha: 0.4),
                    Colors.transparent,
                    Colors.black.withValues(alpha: 0.85),
                  ],
                ),
              ),
            ),
          ],

          // Top Left: Live Status Badge & Snapshot Toggle
          Positioned(
            top: 14,
            left: 14,
            child: Row(
              children: [
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.red.withValues(alpha: 0.9),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      FadeTransition(
                        opacity: _pulseController,
                        child: Container(
                          width: 7,
                          height: 7,
                          decoration: const BoxDecoration(
                            color: Colors.white,
                            shape: BoxShape.circle,
                          ),
                        ),
                      ),
                      const SizedBox(width: 5),
                      Text(
                        _isInAppStreaming ? 'IN-APP LIVE' : 'LIVE SANCTUM',
                        style: const TextStyle(
                            color: Colors.white,
                            fontSize: 10.5,
                            fontWeight: FontWeight.w900),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: 0.6),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.visibility_rounded,
                          color: Colors.white70, size: 12),
                      const SizedBox(width: 4),
                      Text(
                        '${_selectedFeed.viewerCount} Devotees',
                        style: const TextStyle(
                            color: Colors.white,
                            fontSize: 10.5,
                            fontWeight: FontWeight.bold),
                      ),
                    ],
                  ),
                ),
                if (_isInAppStreaming) ...[
                  const SizedBox(width: 8),
                  GestureDetector(
                    onTap: _stopInAppLiveStream,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.black.withValues(alpha: 0.75),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.white24),
                      ),
                      child: const Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.stop_circle_outlined, color: Colors.amberAccent, size: 12),
                          SizedBox(width: 3),
                          Text(
                            'Snapshot',
                            style: TextStyle(color: Colors.white, fontSize: 9.5, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ),

          // Top Right Floating Action: 🪔 दीपदान ₹11 Pop-up Button
          Positioned(
            top: 14,
            right: 14,
            child: GestureDetector(
              onTap: () => _showDiyaLightingSheet(),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFFEA580C), Color(0xFFD97706), Color(0xFFB45309)],
                  ),
                  borderRadius: BorderRadius.circular(20),
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFFF59E0B).withValues(alpha: 0.6),
                      blurRadius: 10,
                      spreadRadius: 1,
                    ),
                  ],
                  border: Border.all(color: Colors.amberAccent, width: 1.2),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    FadeTransition(
                      opacity: _pulseController,
                      child: const Text('🪔', style: TextStyle(fontSize: 14)),
                    ),
                    const SizedBox(width: 5),
                    const Text(
                      'दीपदान ₹11',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 11,
                        fontWeight: FontWeight.w900,
                        letterSpacing: 0.2,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),

          // Center CTA: In-App Live Play & 24/7 Aarti (Only shown when not streaming)
          if (!_isInAppStreaming)
            Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  GestureDetector(
                    onTap: () => _startInAppLiveStream(),
                    child: Container(
                      padding:
                          const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFFDC2626), Color(0xFF991B1B)],
                        ),
                        borderRadius: BorderRadius.circular(30),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.redAccent.withValues(alpha: 0.5),
                            blurRadius: 18,
                            spreadRadius: 2,
                          ),
                        ],
                      ),
                      child: const Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          CircleAvatar(
                            radius: 13,
                            backgroundColor: Colors.white,
                            child: Icon(Icons.play_arrow_rounded,
                                color: Colors.red, size: 18),
                          ),
                          SizedBox(width: 8),
                          Text(
                            'इन-ऐप लाइव दर्शन देखें (PLAY LIVE)',
                            style: TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.w900,
                              fontSize: 11.5,
                              letterSpacing: 0.5,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 8),
                  GestureDetector(
                    onTap: () {
                      final aarti = _sacredAartis.firstWhere(
                        (a) => a.feedId == _selectedFeed.id,
                        orElse: () => _sacredAartis.first,
                      );
                      _showRealAartiExperience(aarti);
                    },
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
                      decoration: BoxDecoration(
                        color: const Color(0xFFD97706).withValues(alpha: 0.9),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: Colors.amberAccent, width: 1),
                      ),
                      child: const Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text('🕉️', style: TextStyle(fontSize: 12)),
                          SizedBox(width: 5),
                          Text(
                            '24/7 पावन आरती दर्शन (Lyrics & Sound)',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 10.5,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  if (_selectedFeed.officialWebUrl != null) ...[
                    const SizedBox(height: 6),
                    GestureDetector(
                      onTap: () => _openLiveStream(customUrl: _selectedFeed.officialWebUrl),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                        decoration: BoxDecoration(
                          color: Colors.black.withValues(alpha: 0.7),
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: Colors.white30),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.public_rounded, color: Colors.amberAccent, size: 11),
                            const SizedBox(width: 4),
                            Text(
                              _selectedFeed.id == 'kashi'
                                  ? 'shrikashivishwanath.org पोर्टल'
                                  : 'अधिकृत ट्रस्ट पोर्टल',
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 9.5,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            ),

          // Diya Lighting Celebration Flame Overlay
          if (_showDiyaCelebration)
            Positioned.fill(
              child: Container(
                color: Colors.black.withValues(alpha: 0.8),
                child: Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          boxShadow: [
                            BoxShadow(
                              color: Colors.amberAccent.withValues(alpha: 0.6),
                              blurRadius: 36,
                              spreadRadius: 10,
                            ),
                          ],
                        ),
                        child: const Text('🪔', style: TextStyle(fontSize: 54)),
                      ),
                      const SizedBox(height: 8),
                      const Text(
                        'हर हर महादेव!',
                        style: TextStyle(
                          color: Colors.amberAccent,
                          fontSize: 19,
                          fontWeight: FontWeight.w900,
                          letterSpacing: 0.5,
                        ),
                      ),
                      const SizedBox(height: 3),
                      Text(
                        '$_celebrationDevotee जी का पावन दीप प्रज्वलित हुआ',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 13,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        _celebrationTitle,
                        style: const TextStyle(
                          color: Colors.white70,
                          fontSize: 11,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),

          // Floating Flowers Animation
          ..._floatingFlowers.map((pos) => Positioned(
                bottom: 70,
                left: MediaQuery.of(context).size.width * pos.dx,
                child: const Icon(
                  Icons.local_florist_rounded,
                  color: Colors.orangeAccent,
                  size: 26,
                ),
              )),

          // Bottom Bar: Virtual Aarti & Offering Controls
          Positioned(
            bottom: 12,
            left: 14,
            right: 14,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                _buildRitualPill(
                  icon: Icons.notifications_active_rounded,
                  label: 'Ring Bell',
                  count: _bellChimes > 0 ? '$_bellChimes' : null,
                  onTap: _ringBell,
                ),
                const SizedBox(width: 8),
                _buildRitualPill(
                  icon: Icons.local_florist_rounded,
                  label: 'Offer Pushpa',
                  count: _flowerCount > 0 ? '$_flowerCount' : null,
                  onTap: _offerFlowers,
                ),
                const SizedBox(width: 8),
                _buildRitualPill(
                  icon: Icons.whatshot_rounded,
                  label: _diyaLit ? 'Diya Lit' : 'दीपदान ₹11',
                  highlight: true,
                  onTap: () => _showDiyaLightingSheet(),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildRitualPill({
    required IconData icon,
    required String label,
    String? count,
    bool highlight = false,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: highlight
              ? Colors.amberAccent
              : Colors.black.withValues(alpha: 0.7),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: highlight ? Colors.amber : Colors.white24,
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              icon,
              size: 14,
              color: highlight ? Colors.black : Colors.white,
            ),
            const SizedBox(width: 4),
            Text(
              count != null ? '$label ($count)' : label,
              style: TextStyle(
                color: highlight ? Colors.black : Colors.white,
                fontSize: 11,
                fontWeight: FontWeight.bold,
              ),
            ),
          ],
        ),
      ),
    );
  }

  // =========================================================================
  // SANCTUM DETAILS CARD
  // =========================================================================
  Widget _buildSanctumDetailsCard(bool isDark) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E293B) : Colors.white,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: isDark ? 0.2 : 0.04),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
        border: Border.all(
          color: isDark ? const Color(0xFF334155) : const Color(0xFFE2E8F0),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      _selectedFeed.name,
                      style: const TextStyle(
                          fontSize: 17, fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 3),
                    Row(
                      children: [
                        const Icon(Icons.location_on_rounded,
                            size: 13, color: CustomerTheme.brandGold),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            _selectedFeed.location,
                            style: TextStyle(
                              fontSize: 12,
                              color: isDark ? Colors.white60 : Colors.black54,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  FilledButton.icon(
                    style: FilledButton.styleFrom(
                      backgroundColor: const Color(0xFFD97706),
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12)),
                      padding:
                          const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                    ),
                    onPressed: () => _showDiyaLightingSheet(),
                    icon: const Text('🪔', style: TextStyle(fontSize: 13)),
                    label: const Text(
                      'दीपदान ₹11',
                      style: TextStyle(
                          fontSize: 11.5, fontWeight: FontWeight.bold),
                    ),
                  ),
                  const SizedBox(width: 6),
                  FilledButton.icon(
                    style: FilledButton.styleFrom(
                      backgroundColor: CustomerTheme.brandGold,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12)),
                      padding:
                          const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                    ),
                    onPressed: _showChadhavaSheet,
                    icon: const Icon(Icons.volunteer_activism_rounded, size: 14),
                    label: Text(
                      'चढ़ावा ₹${_selectedFeed.chadhavaMinPrice}',
                      style: const TextStyle(
                          fontSize: 11.5, fontWeight: FontWeight.bold),
                    ),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 10),
          Text(
            _selectedFeed.description,
            style: TextStyle(
              fontSize: 12.5,
              height: 1.45,
              color: isDark ? Colors.white70 : const Color(0xFF334155),
            ),
          ),
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: CustomerTheme.brandGold.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(10),
              border: Border.all(
                  color: CustomerTheme.brandGold.withValues(alpha: 0.25)),
            ),
            child: Row(
              children: [
                const Icon(Icons.schedule_rounded,
                    size: 15, color: CustomerTheme.brandGold),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Sacred Aarti Timings: ${_selectedFeed.timing}',
                    style: const TextStyle(
                      fontSize: 11.5,
                      fontWeight: FontWeight.w600,
                      color: Color(0xFFD97706),
                    ),
                  ),
                ),
              ],
            ),
          ),
          if (_selectedFeed.officialWebUrl != null) ...[
            const SizedBox(height: 8),
            InkWell(
              onTap: () => _openLiveStream(customUrl: _selectedFeed.officialWebUrl),
              borderRadius: BorderRadius.circular(10),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
                decoration: BoxDecoration(
                  color: isDark ? const Color(0xFF0F172A) : const Color(0xFFF1F5F9),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(
                    color: isDark ? Colors.white12 : Colors.black12,
                  ),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.verified_user_rounded,
                        size: 14, color: Color(0xFF10B981)),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        _selectedFeed.id == 'kashi'
                            ? 'अधिकृत न्यास पोर्टल: shrikashivishwanath.org (लाइव दर्शन)'
                            : 'अधिकृत न्यास पोर्टल लाइव दर्शन',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                          color: isDark ? Colors.white70 : Colors.black87,
                        ),
                      ),
                    ),
                    const Icon(Icons.open_in_new_rounded, size: 13, color: CustomerTheme.brandGold),
                  ],
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }

  // =========================================================================
  // CATEGORY FILTERS
  // =========================================================================
  Widget _buildCategoryFilters(bool isDark) {
    const cats = ['All', 'Jyotirlinga', 'Ganga'];
    return Row(
      children: cats.map((cat) {
        final isSel = _selectedCategory == cat;
        return Padding(
          padding: const EdgeInsets.only(right: 8),
          child: ChoiceChip(
            label: Text(cat,
                style: TextStyle(
                    fontSize: 12,
                    fontWeight: isSel ? FontWeight.bold : FontWeight.w500,
                    color: isSel
                        ? Colors.white
                        : (isDark ? Colors.white70 : Colors.black87))),
            selected: isSel,
            selectedColor: CustomerTheme.brandGold,
            backgroundColor:
                isDark ? const Color(0xFF1E293B) : const Color(0xFFF1F5F9),
            onSelected: (val) {
              if (val) setState(() => _selectedCategory = cat);
            },
          ),
        );
      }).toList(),
    );
  }

  // =========================================================================
  // TEMPLE THUMBNAIL CARD
  // =========================================================================
  Widget _buildTempleThumbnailCard(
      TempleFeed feed, bool isSelected, bool isDark) {
    return InkWell(
      borderRadius: BorderRadius.circular(16),
      onTap: () {
        setState(() {
          _selectedFeed = feed;
          _flowerCount = 0;
          _diyaLit = false;
          _bellChimes = 0;
        });
      },
      child: Container(
        width: 140,
        decoration: BoxDecoration(
          color: isDark ? const Color(0xFF1E293B) : Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: isSelected
                ? CustomerTheme.brandGold
                : (isDark ? Colors.white12 : Colors.black12),
            width: isSelected ? 2 : 1,
          ),
          boxShadow: isSelected
              ? [
                  BoxShadow(
                    color: CustomerTheme.brandGold.withValues(alpha: 0.3),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ]
              : null,
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Stack(
              children: [
                ClipRRect(
                  borderRadius:
                      const BorderRadius.vertical(top: Radius.circular(14)),
                  child: Image.network(
                    feed.thumbnailUrl,
                    height: 84,
                    width: double.infinity,
                    fit: BoxFit.cover,
                    errorBuilder: (_, _, _) => Container(
                      height: 84,
                      color: Colors.amber.shade900,
                      child: const Center(
                          child: Icon(Icons.temple_hindu_rounded,
                              color: Colors.white)),
                    ),
                  ),
                ),
                if (isSelected)
                  Positioned(
                    top: 6,
                    right: 6,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: CustomerTheme.brandGold,
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: const Text('WATCHING',
                          style: TextStyle(
                              color: Colors.white,
                              fontSize: 9,
                              fontWeight: FontWeight.bold)),
                    ),
                  ),
              ],
            ),
            Padding(
              padding: const EdgeInsets.all(8),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    feed.name,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      fontWeight:
                          isSelected ? FontWeight.bold : FontWeight.w600,
                      fontSize: 11.5,
                      color: isDark ? Colors.white : const Color(0xFF0F172A),
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    feed.deity,
                    style: const TextStyle(
                        fontSize: 10,
                        color: CustomerTheme.brandGold,
                        fontWeight: FontWeight.w600),
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  // =========================================================================
  // AARTI SCHEDULE CARD
  // =========================================================================
  Widget _buildAartiScheduleCard(bool isDark) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E293B) : Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: isDark ? const Color(0xFF334155) : const Color(0xFFE2E8F0),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.auto_stories_rounded,
                  color: CustomerTheme.brandGold, size: 18),
              const SizedBox(width: 8),
              Text(
                'Sacred Darshan & Ritual Guidelines',
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.bold,
                  color: isDark ? Colors.white : const Color(0xFF0F172A),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          _buildRitualStep(
              number: '1',
              title: 'Shauch & Sanctity',
              desc:
                  'Perform physical cleansing or wash hands before attending virtual sanctum Aarti.',
              isDark: isDark),
          _buildRitualStep(
              number: '2',
              title: 'Dhyaan & Shankha Naad',
              desc:
                  'Keep audio turned on during Ganga Aarti or Jyotirlinga Bhasma Aarti to receive Vedic vibrational benefits.',
              isDark: isDark),
          _buildRitualStep(
              number: '3',
              title: 'Akhand Diya & Sankalp',
              desc:
                  'Ignite a virtual Akhand Diya or record your family Gotra for monthly Brahmin prayers.',
              isDark: isDark),
        ],
      ),
    );
  }

  Widget _buildLiveOfferingTicker(bool isDark) {
    final msg = _devoteeOfferings.isNotEmpty
        ? _devoteeOfferings[_tickerIndex % _devoteeOfferings.length]
        : '🕉️ Devotees offering Live Sankalp Chadhava at ${_selectedFeed.name}';

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: isDark
              ? [const Color(0xFF1E1B4B), const Color(0xFF311042)]
              : [const Color(0xFFFEF3C7), const Color(0xFFFFF7ED)],
        ),
        border: Border(
          bottom: BorderSide(
            color: CustomerTheme.brandGold.withValues(alpha: 0.35),
          ),
        ),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
            decoration: BoxDecoration(
              color: const Color(0xFF10B981),
              borderRadius: BorderRadius.circular(6),
            ),
            child: const Text(
              'LIVE SEVA',
              style: TextStyle(
                color: Colors.white,
                fontSize: 8.5,
                fontWeight: FontWeight.w900,
                letterSpacing: 0.5,
              ),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: AnimatedSwitcher(
              duration: const Duration(milliseconds: 350),
              child: Text(
                msg,
                key: ValueKey(msg),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  fontSize: 11.5,
                  fontWeight: FontWeight.w700,
                  color: isDark ? const Color(0xFFFDE68A) : const Color(0xFF92400E),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDigitalChadhavaMicroBar(bool isDark) {
    final items = [
      (icon: '🪔', title: 'पावन दीपदान', price: 11, type: 'diya_pop'),
      (icon: '🔔', title: 'Sankalp Bell', price: 11, type: 'bell'),
      (icon: '🌺', title: 'Pushpa Mala', price: 51, type: 'flower'),
      (icon: '🥥', title: 'Shreefal Seva', price: 101, type: 'shreefal'),
    ];

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF151C2C) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: CustomerTheme.brandGold.withValues(alpha: 0.35),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.auto_awesome_rounded,
                  size: 14, color: CustomerTheme.brandGold),
              const SizedBox(width: 6),
              Text(
                '1-Tap Live Aarti Digital Chadhava & Sankalp',
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w800,
                  color: isDark ? Colors.white : const Color(0xFF0F172A),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: items.map((it) {
              return Expanded(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 3),
                  child: InkWell(
                    onTap: () => _triggerMicroOffering(
                      title: it.title,
                      price: it.price,
                      type: it.type,
                      emoji: it.icon,
                    ),
                    borderRadius: BorderRadius.circular(12),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 4),
                      decoration: BoxDecoration(
                        color: isDark
                            ? const Color(0xFF1E293B)
                            : const Color(0xFFFFFBEB),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: it.type == 'diya_pop'
                              ? const Color(0xFFD97706)
                              : CustomerTheme.brandGold.withValues(alpha: 0.4),
                          width: it.type == 'diya_pop' ? 1.5 : 1,
                        ),
                      ),
                      child: Column(
                        children: [
                          Text(it.icon, style: const TextStyle(fontSize: 18)),
                          const SizedBox(height: 3),
                          Text(
                            it.title,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w700,
                              color: isDark ? Colors.white : Colors.black87,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            '₹${it.price}',
                            style: const TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w900,
                              color: Color(0xFFD97706),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              );
            }).toList(),
          ),
        ],
      ),
    );
  }

  void _triggerMicroOffering({
    required String title,
    required int price,
    required String type,
    required String emoji,
  }) {
    if (type == 'diya_pop') {
      _showDiyaLightingSheet();
      return;
    }

    if (type == 'bell') {
      _ringBell();
    } else if (type == 'diya') {
      if (!_diyaLit) {
        _toggleDiya();
      } else {
        HapticFeedback.heavyImpact();
      }
    } else {
      _offerFlowers();
    }

    setState(() {
      _devoteeOfferings.insert(
        0,
        '$emoji You offered $title ₹$price at ${_selectedFeed.name} • Just now',
      );
      _tickerIndex = 0;
    });
  }

  Widget _buildFeaturedAartisCard(bool isDark) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E293B) : Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: CustomerTheme.brandGold.withValues(alpha: 0.35),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: isDark ? 0.2 : 0.04),
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
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFFEA580C), Color(0xFFD97706)],
                  ),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Text('🪔', style: TextStyle(fontSize: 18)),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'पावन आरती एवं ₹11 दीपदान सेवा',
                      style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    Text(
                      'काशी विश्वनाथ मंगला आरती एवं दशाश्वमेध गंगा आरती',
                      style: TextStyle(
                        fontSize: 11,
                        color: isDark ? Colors.white60 : Colors.black54,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          ..._sacredAartis.map((aarti) => _buildFeaturedAartiRow(aarti, isDark)),
        ],
      ),
    );
  }

  Widget _buildFeaturedAartiRow(SacredAartiOption aarti, bool isDark) {
    final isCurrent = _selectedFeed.id == aarti.feedId;
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF151C2C) : const Color(0xFFFFFBEB),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: isCurrent
              ? const Color(0xFFD97706)
              : (isDark ? Colors.white10 : const Color(0xFFFDE68A)),
          width: isCurrent ? 1.5 : 1,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Flexible(
                          child: Text(
                            aarti.hindiTitle,
                            style: const TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 3),
                    Row(
                      children: [
                        const Icon(Icons.schedule_rounded, size: 12, color: Color(0xFFD97706)),
                        const SizedBox(width: 4),
                        Text(
                          aarti.timing,
                          style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                            color: Color(0xFFD97706),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 2),
                    Text(
                      '${aarti.temple} • ${aarti.deity}',
                      style: TextStyle(
                        fontSize: 10.5,
                        color: isDark ? Colors.white54 : Colors.black54,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              FilledButton.icon(
                style: FilledButton.styleFrom(
                  backgroundColor: const Color(0xFFD97706),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
                onPressed: () => _showDiyaLightingSheet(initialAartiId: aarti.id),
                icon: const Text('🪔', style: TextStyle(fontSize: 13)),
                label: const Text(
                  'दीपदान ₹11',
                  style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              InkWell(
                onTap: () {
                  final feed = GrowthAiOsService.instance.activeTempleFeeds.firstWhere(
                    (f) => f.id == aarti.feedId,
                    orElse: () => _selectedFeed,
                  );
                  setState(() => _selectedFeed = feed);
                  _startInAppLiveStream(aarti.streamUrl);
                },
                borderRadius: BorderRadius.circular(8),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
                  decoration: BoxDecoration(
                    color: Colors.red.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: Colors.redAccent.withValues(alpha: 0.4)),
                  ),
                  child: const Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.play_circle_fill_rounded, size: 13, color: Colors.redAccent),
                      SizedBox(width: 4),
                      Text(
                        '🔴 इन-ऐप लाइव देखें',
                        style: TextStyle(fontSize: 11, color: Colors.redAccent, fontWeight: FontWeight.bold),
                      ),
                    ],
                  ),
                ),
              ),
              InkWell(
                onTap: () => _showRealAartiExperience(aarti),
                borderRadius: BorderRadius.circular(8),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
                  decoration: BoxDecoration(
                    color: const Color(0xFFD97706).withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: const Color(0xFFD97706).withValues(alpha: 0.5)),
                  ),
                  child: const Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text('🕉️', style: TextStyle(fontSize: 12)),
                      SizedBox(width: 4),
                      Text(
                        '24/7 आरती (Lyrics)',
                        style: TextStyle(fontSize: 11, color: Color(0xFFFBBF24), fontWeight: FontWeight.bold),
                      ),
                    ],
                  ),
                ),
              ),
              if (aarti.portalUrl != null) ...[
                InkWell(
                  onTap: () => _openLiveStream(customUrl: aarti.portalUrl),
                  borderRadius: BorderRadius.circular(8),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
                    decoration: BoxDecoration(
                      color: isDark ? Colors.white10 : Colors.black.withValues(alpha: 0.05),
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: isDark ? Colors.white24 : Colors.black12),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.public_rounded, size: 12, color: isDark ? Colors.white70 : Colors.black87),
                        const SizedBox(width: 4),
                        Text(
                          'ट्रस्ट पोर्टल',
                          style: TextStyle(
                            fontSize: 10.5,
                            color: isDark ? Colors.white70 : Colors.black87,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }

  void _showRealAartiExperience(SacredAartiOption aarti) {
    HapticFeedback.heavyImpact();
    int lyricIndex = 0;
    Timer? lyricTimer;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheetState) {
          lyricTimer ??= Timer.periodic(const Duration(seconds: 5), (_) {
            if (ctx.mounted && aarti.lyrics.isNotEmpty) {
              setSheetState(() {
                lyricIndex = (lyricIndex + 1) % aarti.lyrics.length;
              });
            }
          });

          return Container(
            height: MediaQuery.of(ctx).size.height * 0.88,
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [Color(0xFF0F172A), Color(0xFF020617)],
              ),
              borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
            ),
            child: Column(
              children: [
                const SizedBox(height: 12),
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
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          const Text('🪔', style: TextStyle(fontSize: 22)),
                          const SizedBox(width: 8),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                aarti.hindiTitle,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 15.5,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              Text(
                                '${aarti.temple} • 24/7 पावन आरती दर्शन',
                                style: const TextStyle(
                                  color: Color(0xFFFBBF24),
                                  fontSize: 11,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                      IconButton(
                        onPressed: () {
                          lyricTimer?.cancel();
                          Navigator.pop(ctx);
                        },
                        icon: const Icon(Icons.close_rounded, color: Colors.white70),
                      ),
                    ],
                  ),
                ),
                const Divider(color: Colors.white12, height: 1),
                Expanded(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      children: [
                        // Sanctum Visual with Rotating Brass Aarti Thali
                        Stack(
                          alignment: Alignment.center,
                          children: [
                            Container(
                              width: 200,
                              height: 200,
                              decoration: BoxDecoration(
                                shape: BoxShape.circle,
                                gradient: RadialGradient(
                                  colors: [
                                    const Color(0xFFD97706).withValues(alpha: 0.35),
                                    Colors.transparent,
                                  ],
                                ),
                              ),
                            ),
                            RotationTransition(
                              turns: _pulseController,
                              child: Container(
                                width: 140,
                                height: 140,
                                decoration: BoxDecoration(
                                  shape: BoxShape.circle,
                                  gradient: const RadialGradient(
                                    colors: [Color(0xFFFEF08A), Color(0xFFB45309), Color(0xFF78350F)],
                                  ),
                                  boxShadow: [
                                    BoxShadow(
                                      color: const Color(0xFFF59E0B).withValues(alpha: 0.5),
                                      blurRadius: 28,
                                      spreadRadius: 6,
                                    ),
                                  ],
                                  border: Border.all(color: Colors.amberAccent, width: 3),
                                ),
                                child: const Center(
                                  child: Text('🪔', style: TextStyle(fontSize: 44)),
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 14),
                        // Auspicious Mantra Box
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                          decoration: BoxDecoration(
                            color: const Color(0xFF1E293B),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: const Color(0xFFD97706).withValues(alpha: 0.4)),
                          ),
                          child: Text(
                            aarti.mantra,
                            textAlign: TextAlign.center,
                            style: const TextStyle(
                              color: Color(0xFFFDE68A),
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                              height: 1.4,
                            ),
                          ),
                        ),
                        const SizedBox(height: 18),
                        // Synchronized Scrolling Devanagari Lyrics
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: const Color(0xFF0F172A),
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: Colors.white12),
                          ),
                          child: Column(
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  const Text(
                                    'पावन आरती के बोल (Lyrics):',
                                    style: TextStyle(color: Colors.white70, fontSize: 11.5, fontWeight: FontWeight.w600),
                                  ),
                                  Text(
                                    '${lyricIndex + 1}/${aarti.lyrics.length}',
                                    style: const TextStyle(color: Color(0xFFD97706), fontSize: 11, fontWeight: FontWeight.bold),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 12),
                              AnimatedSwitcher(
                                duration: const Duration(milliseconds: 350),
                                child: Text(
                                  aarti.lyrics[lyricIndex % aarti.lyrics.length],
                                  key: ValueKey(lyricIndex),
                                  textAlign: TextAlign.center,
                                  style: const TextStyle(
                                    color: Color(0xFFFBBF24),
                                    fontSize: 15.5,
                                    fontWeight: FontWeight.w900,
                                    height: 1.5,
                                    letterSpacing: 0.3,
                                  ),
                                ),
                              ),
                              const SizedBox(height: 10),
                              if (lyricIndex + 1 < aarti.lyrics.length)
                                Text(
                                  aarti.lyrics[(lyricIndex + 1) % aarti.lyrics.length],
                                  textAlign: TextAlign.center,
                                  style: const TextStyle(
                                    color: Colors.white38,
                                    fontSize: 12,
                                  ),
                                ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 16),
                        // Quick Ritual Buttons inside Aarti
                        Row(
                          children: [
                            Expanded(
                              child: OutlinedButton.icon(
                                style: OutlinedButton.styleFrom(
                                  foregroundColor: Colors.amberAccent,
                                  side: const BorderSide(color: Color(0xFFD97706)),
                                  padding: const EdgeInsets.symmetric(vertical: 10),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                                ),
                                onPressed: () {
                                  _ringBell();
                                },
                                icon: const Icon(Icons.notifications_active_rounded, size: 16),
                                label: const Text('घंटा नाद', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold)),
                              ),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: OutlinedButton.icon(
                                style: OutlinedButton.styleFrom(
                                  foregroundColor: Colors.orangeAccent,
                                  side: const BorderSide(color: Colors.orangeAccent),
                                  padding: const EdgeInsets.symmetric(vertical: 10),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                                ),
                                onPressed: () {
                                  _offerFlowers();
                                },
                                icon: const Icon(Icons.local_florist_rounded, size: 16),
                                label: const Text('पुष्प अर्पण', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold)),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        // Primary ₹11 Diya Lighting CTA
                        SizedBox(
                          width: double.infinity,
                          child: FilledButton.icon(
                            style: FilledButton.styleFrom(
                              backgroundColor: const Color(0xFFD97706),
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(vertical: 13),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                            ),
                            onPressed: () {
                              _showDiyaLightingSheet(initialAartiId: aarti.id);
                            },
                            icon: const Text('🪔', style: TextStyle(fontSize: 16)),
                            label: const Text(
                              'इस आरती में ₹11 पावन दीप प्रज्वलित करें',
                              style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold),
                            ),
                          ),
                        ),
                        const SizedBox(height: 8),
                        // Switch to In-App Live Stream
                        TextButton.icon(
                          onPressed: () {
                            lyricTimer?.cancel();
                            Navigator.pop(ctx);
                            final feed = GrowthAiOsService.instance.activeTempleFeeds.firstWhere(
                              (f) => f.id == aarti.feedId,
                              orElse: () => _selectedFeed,
                            );
                            setState(() => _selectedFeed = feed);
                            _startInAppLiveStream(aarti.streamUrl);
                          },
                          icon: const Icon(Icons.play_circle_fill_rounded, color: Colors.redAccent, size: 18),
                          label: const Text(
                            '🔴 गर्भगृह इन-ऐप लाइव वीडियो प्रसारण देखें',
                            style: TextStyle(color: Colors.white70, fontSize: 11.5),
                          ),
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

  Widget _buildRitualStep({
    required String number,
    required String title,
    required String desc,
    required bool isDark,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CircleAvatar(
            radius: 10,
            backgroundColor: CustomerTheme.brandGold.withValues(alpha: 0.2),
            child: Text(
              number,
              style: const TextStyle(
                  fontSize: 10.5,
                  fontWeight: FontWeight.bold,
                  color: CustomerTheme.brandGold),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title,
                    style: const TextStyle(
                        fontSize: 12, fontWeight: FontWeight.bold)),
                Text(
                  desc,
                  style: TextStyle(
                    fontSize: 11,
                    color: isDark ? Colors.white60 : Colors.black54,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
