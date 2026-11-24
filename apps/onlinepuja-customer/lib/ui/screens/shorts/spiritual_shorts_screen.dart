import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:http/http.dart' as http;
import 'package:cached_network_image/cached_network_image.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../theme/customer_theme.dart';
import '../puja/puja_list_screen.dart';

/// Full-screen vertical swipeable Spiritual Shorts & Daily AI Reels Feed
class SpiritualShortsScreen extends StatefulWidget {
  const SpiritualShortsScreen({super.key, this.initialIndex = 0});

  static const route = '/spiritual-shorts';
  final int initialIndex;

  @override
  State<SpiritualShortsScreen> createState() => _SpiritualShortsScreenState();
}

class _SpiritualShortsScreenState extends State<SpiritualShortsScreen> {
  late PageController _pageController;
  int _currentIndex = 0;
  bool _isLoading = true;
  List<Map<String, dynamic>> _shorts = [];
  final Set<String> _likedIds = {};

  @override
  void initState() {
    super.initState();
    _currentIndex = widget.initialIndex;
    _pageController = PageController(initialPage: widget.initialIndex);
    _loadShorts();
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  Future<void> _loadShorts() async {
    setState(() => _isLoading = true);
    try {
      final res = await http.get(
        Uri.parse('https://onlinepuja.live/api/feed/shorts'),
      ).timeout(const Duration(seconds: 8));

      if (res.statusCode == 200) {
        final decoded = json.decode(res.body);
        if (decoded['success'] == true && decoded['data'] is List) {
          final items = List<Map<String, dynamic>>.from(decoded['data']);
          if (mounted) {
            setState(() {
              _shorts = items;
              _isLoading = false;
            });
            return;
          }
        }
      }
    } catch (_) {}

    if (mounted) {
      setState(() {
        _shorts = _fallbackShorts();
        _isLoading = false;
      });
    }
  }

  List<Map<String, dynamic>> _fallbackShorts() {
    return [
      {
        'id': 'live_kashi_vishwanath',
        'title': '🔱 श्री काशी विश्वनाथ महा आरती (लाइव दर्शन)',
        'category': 'Live Mandir Aarti',
        'icon': '🔱',
        'thumbnail_url': 'https://onlinepuja.live/public/storage/puja_images/puja_17642331041781434446.png',
        'caption': 'श्री काशी विश्वनाथ ज्योतिर्लिंग धाम से 24x7 पावन गर्भग्रह दर्शन एवं दैनिक मंगला, भोग व शृंगार आरती।',
        'cta_label': 'लाइव दर्शन एवं आरती करें',
        'cta_route': '/live-darshan',
        'likes_count': 9420,
        'views_count': 42100,
        'shares_count': 2840,
        'is_liked': true,
      },
      {
        'id': 'live_ganga_aarti',
        'title': '🌊 दशाश्वमेध घाट महा गंगा आरती',
        'category': 'Live Ganga Aarti',
        'icon': '🌊',
        'thumbnail_url': 'https://onlinepuja.live/public/storage/brain_media/brain_NBw7PwNZxf2gqejbRd_940x627.jpg',
        'caption': 'काशी के पावन दशाश्वमेध घाट पर विश्व-प्रसिद्ध महा गंगा आरती। वैदिक मंत्रोच्चार, शंखनाद और दिव्य दीपदान दर्शन।',
        'cta_label': 'गंगा आरती दर्शन',
        'cta_route': '/live-darshan',
        'likes_count': 8140,
        'views_count': 36800,
        'shares_count': 2190,
        'is_liked': false,
      },
      {
        'id': 'puja_lakshmi_kubera',
        'title': '💰 श्री लक्ष्मी-कुबेर महा धन लाभ पूजा व हवन',
        'category': 'वैदिक पूजा संकल्प',
        'icon': '🪔',
        'thumbnail_url': 'https://onlinepuja.live/public/storage/puja_images/puja_1764232697222466223.webp',
        'caption': 'ऋण मुक्ति, स्थिर धन संचय एवं व्यापारिक उन्नति हेतु कनकधारा स्तोत्र व श्री सूक्त के वैदिक मंत्रों द्वारा पावन हवन।',
        'cta_label': 'बुक करें पावन संकल्प ₹501',
        'cta_route': '/puja-details/shree-lakshmi-kubera-maha-dhan-labh-puja',
        'likes_count': 6100,
        'views_count': 24500,
        'shares_count': 1950,
        'is_liked': false,
      },
      {
        'id': 'puja_rudrabhishek',
        'title': '🔱 महामृत्युंजय आयुष्य हवन एवं संजीवनी महाजाप',
        'category': 'महामृत्युंजय अनुष्ठान',
        'icon': '🔱',
        'thumbnail_url': 'https://onlinepuja.live/public/storage/puja_images/puja_17642331041781434446.png',
        'caption': 'अकाल मृत्यु भय, गंभीर रोग एवं ग्रह बाधा से मुक्ति हेतु काशी व उज्जैन के सिद्ध वेदपाठी पुरोहितों द्वारा वैदिक महाजाप।',
        'cta_label': 'बुक करें महाजाप ₹1,501',
        'cta_route': '/puja-details/maha-mrityunjaya-ayushya-havan-sanjeevani-jaap',
        'likes_count': 5230,
        'views_count': 19800,
        'shares_count': 1520,
        'is_liked': false,
      },
    ];
  }

  void _toggleLike(String shortId, int index) {
    HapticFeedback.mediumImpact();
    setState(() {
      if (_likedIds.contains(shortId)) {
        _likedIds.remove(shortId);
        _shorts[index]['likes_count'] = (_shorts[index]['likes_count'] as int) - 1;
      } else {
        _likedIds.add(shortId);
        _shorts[index]['likes_count'] = (_shorts[index]['likes_count'] as int) + 1;
      }
    });

    http.post(
      Uri.parse('https://onlinepuja.live/api/feed/shorts/like'),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({'short_id': shortId}),
    ).catchError((_) => http.Response('', 500));
  }

  void _shareShort(Map<String, dynamic> item) {
    HapticFeedback.lightImpact();
    final title = item['title'] ?? 'Spiritual Reel';
    final shareText = '🕉️ Watch "$title" on OnlinePuja.live:\nhttps://onlinepuja.live/shorts?id=${item['id']}\n\nExperience divine blessings with family Gotra Sankalp.';
    final waUrl = 'https://wa.me/?text=${Uri.encodeComponent(shareText)}';
    launchUrl(Uri.parse(waUrl), mode: LaunchMode.externalApplication);
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(
        backgroundColor: Colors.black,
        body: Center(
          child: CircularProgressIndicator(color: CustomerTheme.brandGold),
        ),
      );
    }

    return Scaffold(
      backgroundColor: Colors.black,
      body: Stack(
        children: [
          // Vertical PageView
          PageView.builder(
            controller: _pageController,
            scrollDirection: Axis.vertical,
            itemCount: _shorts.length,
            onPageChanged: (idx) => setState(() => _currentIndex = idx),
            itemBuilder: (context, index) {
              final item = _shorts[index];
              return _buildShortPage(item, index);
            },
          ),

          // Top App Bar Controls
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  IconButton(
                    icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 22),
                    onPressed: () => Navigator.pop(context),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: Colors.black54,
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: Colors.white24),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Text('🪔', style: TextStyle(fontSize: 14)),
                        const SizedBox(width: 6),
                        Text(
                          'Shorts ${_currentIndex + 1}/${_shorts.length}',
                          style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 40),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildShortPage(Map<String, dynamic> item, int index) {
    final shortId = item['id'].toString();
    final isLiked = _likedIds.contains(shortId) || (item['is_liked'] == true && !_likedIds.contains('unliked_$shortId'));
    final likesCount = item['likes_count'] ?? 1000;
    final viewsCount = item['views_count'] ?? 5000;
    final thumbUrl = item['thumbnail_url'] ?? '';
    final title = item['title'] ?? 'Sacred Vedic Reel';
    final category = item['category'] ?? 'Devotional';
    final caption = item['caption'] ?? '';
    final ctaLabel = item['cta_label'] ?? 'Book Sacred Puja';

    return Stack(
      fit: StackFit.expand,
      children: [
        // Background Image / Video Poster
        if (thumbUrl.isNotEmpty)
          CachedNetworkImage(
            imageUrl: thumbUrl,
            fit: BoxFit.cover,
            placeholder: (context, url) => Container(color: const Color(0xFF0F172A)),
            errorWidget: (context, url, error) => Container(
              color: const Color(0xFF0F172A),
              child: const Center(child: Icon(Icons.temple_hindu_rounded, size: 64, color: Colors.white30)),
            ),
          )
        else
          Container(color: const Color(0xFF0F172A)),

        // High-Contrast Dark Gradient
        Container(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              colors: [
                Colors.black.withValues(alpha: 0.4),
                Colors.transparent,
                Colors.black.withValues(alpha: 0.85),
                Colors.black.withValues(alpha: 0.95),
              ],
              stops: const [0.0, 0.35, 0.75, 1.0],
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
            ),
          ),
        ),

        // Center Pulsing Play / Reel Indicator
        Center(
          child: Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.black.withValues(alpha: 0.35),
              shape: BoxShape.circle,
              border: Border.all(color: Colors.white24),
            ),
            child: const Icon(Icons.play_arrow_rounded, color: Colors.white, size: 36),
          ),
        ),

        // Right-Side Action Bar
        Positioned(
          right: 14,
          bottom: 120,
          child: Column(
            children: [
              // Like Button
              GestureDetector(
                onTap: () => _toggleLike(shortId, index),
                child: Column(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: Colors.black54,
                        shape: BoxShape.circle,
                        border: Border.all(color: isLiked ? CustomerTheme.brandGold : Colors.white24),
                      ),
                      child: Icon(
                        isLiked ? Icons.favorite_rounded : Icons.favorite_border_rounded,
                        color: isLiked ? Colors.redAccent : Colors.white,
                        size: 26,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text('$likesCount', style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
                  ],
                ),
              ),
              const SizedBox(height: 16),

              // Share Button
              GestureDetector(
                onTap: () => _shareShort(item),
                child: Column(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: Colors.black54,
                        shape: BoxShape.circle,
                        border: Border.all(color: Colors.white24),
                      ),
                      child: const Icon(Icons.share_rounded, color: Colors.white, size: 24),
                    ),
                    const SizedBox(height: 4),
                    const Text('Share', style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
                  ],
                ),
              ),
              const SizedBox(height: 16),

              // Views Count
              Column(
                children: [
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: Colors.black54,
                      shape: BoxShape.circle,
                      border: Border.all(color: Colors.white24),
                    ),
                    child: const Icon(Icons.remove_red_eye_rounded, color: Colors.white70, size: 24),
                  ),
                  const SizedBox(height: 4),
                  Text('$viewsCount', style: const TextStyle(color: Colors.white70, fontSize: 11)),
                ],
              ),
            ],
          ),
        ),

        // Bottom Info & CTA
        Positioned(
          left: 16,
          right: 80,
          bottom: 30,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Category Badge
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: CustomerTheme.brandAmber.withValues(alpha: 0.25),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: CustomerTheme.brandGold.withValues(alpha: 0.4)),
                ),
                child: Text(
                  category,
                  style: const TextStyle(color: CustomerTheme.brandGold, fontSize: 11, fontWeight: FontWeight.w800),
                ),
              ),
              const SizedBox(height: 8),

              // Title
              Text(
                title,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 16,
                  fontWeight: FontWeight.w900,
                  height: 1.25,
                ),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
              const SizedBox(height: 6),

              // Caption
              Text(
                caption,
                style: const TextStyle(color: Colors.white70, fontSize: 12, height: 1.3),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
              const SizedBox(height: 14),

              // Prominent Action Button
              SizedBox(
                height: 44,
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: CustomerTheme.brandGold,
                    foregroundColor: Colors.black,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                  ),
                  icon: const Icon(Icons.flare_rounded, size: 18),
                  label: Text(ctaLabel, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13)),
                  onPressed: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => const PujaListScreen()),
                    );
                  },
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
