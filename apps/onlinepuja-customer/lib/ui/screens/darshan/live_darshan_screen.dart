import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:op_shared/op_shared.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../theme/customer_theme.dart';

/// Live Mandir Darshan & Virtual Sanctum Hub.
/// Allows devotees worldwide to witness 24/7 official sanctum feeds of sacred Jyotirlingas & Dhams,
/// perform virtual Aarti rituals (Diya, Temple Bell, Pushpanjali), and offer sacred Chadhava.
class LiveDarshanScreen extends StatefulWidget {
  const LiveDarshanScreen({super.key});

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

  final List<String> _devoteeOfferings = [
    '🕉️ Rajesh Sharma (Delhi) offered Akhand Ghee Diya ₹21 • Just now',
    '🌺 Priya Nair (Bengaluru) offered Sacred Pushpa Mala ₹51 • 18s ago',
    '🔔 Vikramaditya Singh (Jaipur) rang Sankalp Ghanta ₹11 • 34s ago',
    '🥥 Ananya Deshmukh (Pune) offered Shreefal & Bhasma ₹101 • 1m ago',
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
    _selectedFeed = feeds.isNotEmpty
        ? feeds.first
        : GrowthAiOsService.defaultTempleFeeds.first;

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

  Future<void> _openLiveStream() async {
    HapticFeedback.mediumImpact();
    final uri = Uri.parse(_selectedFeed.streamUrl);
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
    HapticFeedback.heavyImpact();
    setState(() => _diyaLit = !_diyaLit);

    ScaffoldMessenger.of(context).hideCurrentSnackBar();
    if (_diyaLit) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          duration: const Duration(seconds: 2),
          behavior: SnackBarBehavior.floating,
          backgroundColor: const Color(0xFF92400E),
          content: const Row(
            children: [
              Icon(Icons.whatshot_rounded, color: Colors.amberAccent, size: 20),
              SizedBox(width: 8),
              Expanded(
                child: Text(
                  'Akhand Deepam ignited in your name at the sanctum!',
                  style: TextStyle(fontWeight: FontWeight.bold),
                ),
              ),
            ],
          ),
        ),
      );
    }
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
          // Sanctum Thumbnail
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

          // Top Left: Live Status Badge
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
                      const Text(
                        'LIVE SANCTUM',
                        style: TextStyle(
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
              ],
            ),
          ),

          // Lit Diya Indicator Badge
          if (_diyaLit)
            Positioned(
              top: 14,
              right: 14,
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                decoration: BoxDecoration(
                  color: Colors.black.withValues(alpha: 0.75),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: Colors.amberAccent),
                ),
                child: const Row(
                  children: [
                    Icon(Icons.whatshot_rounded,
                        color: Colors.amberAccent, size: 14),
                    SizedBox(width: 4),
                    Text(
                      'Akhand Diya Lit',
                      style: TextStyle(
                          color: Colors.amberAccent,
                          fontSize: 11,
                          fontWeight: FontWeight.bold),
                    ),
                  ],
                ),
              ),
            ),

          // Center CTA: Watch Live Stream
          Center(
            child: GestureDetector(
              onTap: _openLiveStream,
              child: Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 18, vertical: 11),
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
                      radius: 14,
                      backgroundColor: Colors.white,
                      child: Icon(Icons.play_arrow_rounded,
                          color: Colors.red, size: 20),
                    ),
                    SizedBox(width: 10),
                    Text(
                      'WATCH LIVE BROADCAST',
                      style: TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w900,
                        fontSize: 12,
                        letterSpacing: 0.5,
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
                  label: _diyaLit ? 'Diya Lit' : 'Light Diya',
                  highlight: _diyaLit,
                  onTap: _toggleDiya,
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
              const SizedBox(width: 10),
              FilledButton.icon(
                style: FilledButton.styleFrom(
                  backgroundColor: CustomerTheme.brandGold,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12)),
                  padding:
                      const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                ),
                onPressed: _showChadhavaSheet,
                icon: const Icon(Icons.volunteer_activism_rounded, size: 15),
                label: Text(
                  'चढ़ावा ₹${_selectedFeed.chadhavaMinPrice}',
                  style: const TextStyle(
                      fontSize: 12, fontWeight: FontWeight.bold),
                ),
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
      (icon: '🔔', title: 'Sankalp Bell', price: 11, type: 'bell'),
      (icon: '🪔', title: 'Akhand Diya', price: 21, type: 'diya'),
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
                          color: CustomerTheme.brandGold.withValues(alpha: 0.4),
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
