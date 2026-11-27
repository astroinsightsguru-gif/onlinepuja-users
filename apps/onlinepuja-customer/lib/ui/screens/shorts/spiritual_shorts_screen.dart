import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:http/http.dart' as http;
import 'package:cached_network_image/cached_network_image.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:video_player/video_player.dart';
import 'package:op_shared/op_shared.dart';

import '../../theme/customer_theme.dart';
import '../darshan/live_darshan_screen.dart';
import '../puja/puja_detail_screen.dart';
import '../puja/puja_list_screen.dart';

/// Full-screen vertical swipeable Spiritual Shorts & Reels Feed with real video playback
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
  bool _isMuted = false;
  List<Map<String, dynamic>> _shorts = [];
  final Set<String> _likedIds = {};
  final Set<String> _viewedIds = {};

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
            _trackView(_currentIndex);
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
      _trackView(_currentIndex);
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
        'video_url': 'https://assets.mixkit.co/videos/preview/mixkit-oil-lamps-floating-in-the-sacred-ganges-river-42861-large.mp4',
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
        'video_url': 'https://assets.mixkit.co/videos/preview/mixkit-oil-lamps-floating-in-the-sacred-ganges-river-42861-large.mp4',
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
        'video_url': 'https://assets.mixkit.co/videos/preview/mixkit-smoke-and-fire-from-a-burning-incense-during-a-ritual-42862-large.mp4',
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
        'video_url': 'https://assets.mixkit.co/videos/preview/mixkit-smoke-and-fire-from-a-burning-incense-during-a-ritual-42862-large.mp4',
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

  void _trackView(int index) {
    if (index < 0 || index >= _shorts.length) return;
    final id = _shorts[index]['id']?.toString() ?? '';
    if (id.isEmpty || _viewedIds.contains(id)) return;
    _viewedIds.add(id);

    http.post(
      Uri.parse('https://onlinepuja.live/api/feed/shorts/view'),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({'short_id': id}),
    ).catchError((_) => http.Response('', 500));
  }

  void _toggleLike(String shortId, int index) {
    HapticFeedback.mediumImpact();
    final isCurrentlyLiked = _likedIds.contains(shortId) ||
        (_shorts[index]['is_liked'] == true && !_likedIds.contains('unliked_$shortId'));

    setState(() {
      if (isCurrentlyLiked) {
        _likedIds.remove(shortId);
        _likedIds.add('unliked_$shortId');
        _shorts[index]['likes_count'] = ((_shorts[index]['likes_count'] as int?) ?? 1) - 1;
      } else {
        _likedIds.add(shortId);
        _likedIds.remove('unliked_$shortId');
        _shorts[index]['likes_count'] = ((_shorts[index]['likes_count'] as int?) ?? 0) + 1;
      }
    });

    http.post(
      Uri.parse('https://onlinepuja.live/api/feed/shorts/like'),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({
        'short_id': shortId,
        'delta': isCurrentlyLiked ? -1 : 1,
      }),
    ).catchError((_) => http.Response('', 500));
  }

  void _shareShort(Map<String, dynamic> item) {
    HapticFeedback.lightImpact();
    final title = item['title'] ?? 'Spiritual Reel';
    final shareText = '🕉️ Watch "$title" on OnlinePuja.live:\nhttps://onlinepuja.live/shorts?id=${item['id']}\n\nExperience sacred temple darshan & family Gotra Sankalp.';
    final waUrl = 'https://wa.me/?text=${Uri.encodeComponent(shareText)}';
    launchUrl(Uri.parse(waUrl), mode: LaunchMode.externalApplication);
  }

  void _handleCtaRoute(String route, Map<String, dynamic> item) {
    HapticFeedback.lightImpact();
    if (route == '/live-darshan') {
      Navigator.push(
        context,
        MaterialPageRoute(builder: (_) => const LiveDarshanScreen()),
      );
    } else if (route.startsWith('/puja-details/')) {
      final slugOrId = route.replaceFirst('/puja-details/', '');
      final rawId = int.tryParse(slugOrId);
      final thumb = item['thumbnail_url']?.toString();
      final dummyPuja = Puja(
        id: rawId,
        slug: rawId == null ? slugOrId : null,
        title: item['title']?.toString(),
        subtitle: item['caption']?.toString(),
        images: thumb != null ? [thumb] : null,
      );
      Navigator.push(
        context,
        MaterialPageRoute(builder: (_) => PujaDetailScreen(puja: dummyPuja)),
      );
    } else {
      Navigator.push(
        context,
        MaterialPageRoute(builder: (_) => const PujaListScreen()),
      );
    }
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
            onPageChanged: (idx) {
              setState(() => _currentIndex = idx);
              _trackView(idx);
            },
            itemBuilder: (context, index) {
              final item = _shorts[index];
              return _ReelPlayerCard(
                key: ValueKey(item['id'] ?? index),
                item: item,
                isActive: index == _currentIndex,
                isMuted: _isMuted,
                likedIds: _likedIds,
                onToggleLike: () => _toggleLike(item['id'].toString(), index),
                onShare: () => _shareShort(item),
                onCtaTap: (route) => _handleCtaRoute(route, item),
              );
            },
          ),

          // Top Header Controls
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
                          'Reels ${_currentIndex + 1}/${_shorts.length}',
                          style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    icon: Icon(
                      _isMuted ? Icons.volume_off_rounded : Icons.volume_up_rounded,
                      color: Colors.white,
                      size: 24,
                    ),
                    onPressed: () {
                      setState(() => _isMuted = !_isMuted);
                    },
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

/// Single Reel video player card with auto-play, looping, heart animations and interactive overlays
class _ReelPlayerCard extends StatefulWidget {
  const _ReelPlayerCard({
    super.key,
    required this.item,
    required this.isActive,
    required this.isMuted,
    required this.likedIds,
    required this.onToggleLike,
    required this.onShare,
    required this.onCtaTap,
  });

  final Map<String, dynamic> item;
  final bool isActive;
  final bool isMuted;
  final Set<String> likedIds;
  final VoidCallback onToggleLike;
  final VoidCallback onShare;
  final void Function(String route) onCtaTap;

  @override
  State<_ReelPlayerCard> createState() => _ReelPlayerCardState();
}

class _ReelPlayerCardState extends State<_ReelPlayerCard>
    with SingleTickerProviderStateMixin {
  VideoPlayerController? _videoController;
  bool _isInitialized = false;
  bool _isPlaying = false;
  bool _showPlayPauseIndicator = false;
  bool _showHeartAnimation = false;
  Timer? _indicatorTimer;
  Timer? _heartTimer;

  @override
  void initState() {
    super.initState();
    if (widget.isActive) {
      _initVideo();
    }
  }

  @override
  void didUpdateWidget(covariant _ReelPlayerCard oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.isActive != widget.isActive) {
      if (widget.isActive) {
        if (_videoController == null) {
          _initVideo();
        } else {
          _videoController?.play();
          setState(() => _isPlaying = true);
        }
      } else {
        _videoController?.pause();
        setState(() => _isPlaying = false);
      }
    }

    if (oldWidget.isMuted != widget.isMuted) {
      _videoController?.setVolume(widget.isMuted ? 0.0 : 1.0);
    }
  }

  @override
  void dispose() {
    _indicatorTimer?.cancel();
    _heartTimer?.cancel();
    _videoController?.dispose();
    super.dispose();
  }

  Future<void> _initVideo() async {
    final videoUrl = widget.item['video_url']?.toString() ?? '';
    if (videoUrl.isEmpty) return;

    try {
      final controller = VideoPlayerController.networkUrl(Uri.parse(videoUrl));
      await controller.initialize();
      controller.setLooping(true);
      controller.setVolume(widget.isMuted ? 0.0 : 1.0);

      if (mounted) {
        setState(() {
          _videoController = controller;
          _isInitialized = true;
          _isPlaying = true;
        });
        if (widget.isActive) {
          controller.play();
        }
      } else {
        controller.dispose();
      }
    } catch (_) {
      // Fallback gracefully to high-res poster image
    }
  }

  void _togglePlayPause() {
    if (_videoController == null || !_isInitialized) return;
    HapticFeedback.selectionClick();
    setState(() {
      if (_isPlaying) {
        _videoController?.pause();
        _isPlaying = false;
      } else {
        _videoController?.play();
        _isPlaying = true;
      }
      _showPlayPauseIndicator = true;
    });

    _indicatorTimer?.cancel();
    _indicatorTimer = Timer(const Duration(milliseconds: 700), () {
      if (mounted) setState(() => _showPlayPauseIndicator = false);
    });
  }

  void _triggerDoubleTapLike() {
    widget.onToggleLike();
    setState(() => _showHeartAnimation = true);
    _heartTimer?.cancel();
    _heartTimer = Timer(const Duration(milliseconds: 800), () {
      if (mounted) setState(() => _showHeartAnimation = false);
    });
  }

  @override
  Widget build(BuildContext context) {
    final shortId = widget.item['id'].toString();
    final isLiked = widget.likedIds.contains(shortId) ||
        (widget.item['is_liked'] == true && !widget.likedIds.contains('unliked_$shortId'));
    final likesCount = widget.item['likes_count'] ?? 1000;
    final viewsCount = widget.item['views_count'] ?? 5000;
    final thumbUrl = widget.item['thumbnail_url'] ?? '';
    final title = widget.item['title'] ?? 'Sacred Vedic Reel';
    final category = widget.item['category'] ?? 'Devotional';
    final caption = widget.item['caption'] ?? '';
    final ctaLabel = widget.item['cta_label'] ?? 'Book Sacred Puja';
    final ctaRoute = widget.item['cta_route']?.toString() ?? '/pujas';

    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: _togglePlayPause,
      onDoubleTap: _triggerDoubleTapLike,
      child: Stack(
        fit: StackFit.expand,
        children: [
          // 1. Poster Image (Always present underneath as smooth placeholder)
          if (thumbUrl.isNotEmpty)
            CachedNetworkImage(
              imageUrl: thumbUrl,
              fit: BoxFit.cover,
              placeholder: (context, url) => Container(color: const Color(0xFF0F172A)),
              errorWidget: (context, url, error) => Container(
                color: const Color(0xFF0F172A),
                child: const Center(
                  child: Icon(Icons.temple_hindu_rounded, size: 64, color: Colors.white30),
                ),
              ),
            )
          else
            Container(color: const Color(0xFF0F172A)),

          // 2. Video Player Layer (if loaded)
          if (_isInitialized && _videoController != null)
            SizedBox.expand(
              child: FittedBox(
                fit: BoxFit.cover,
                child: SizedBox(
                  width: _videoController!.value.size.width,
                  height: _videoController!.value.size.height,
                  child: VideoPlayer(_videoController!),
                ),
              ),
            ),

          // 3. Cinematic Dark Gradient for text readability
          Container(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [
                  Colors.black.withValues(alpha: 0.35),
                  Colors.transparent,
                  Colors.black.withValues(alpha: 0.8),
                  Colors.black.withValues(alpha: 0.95),
                ],
                stops: const [0.0, 0.35, 0.75, 1.0],
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
              ),
            ),
          ),

          // 4. Center Play / Pause Indicator (shows when tapped)
          if (_showPlayPauseIndicator)
            Center(
              child: AnimatedOpacity(
                opacity: _showPlayPauseIndicator ? 1.0 : 0.0,
                duration: const Duration(milliseconds: 200),
                child: Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: 0.6),
                    shape: BoxShape.circle,
                    border: Border.all(color: Colors.white30),
                  ),
                  child: Icon(
                    _isPlaying ? Icons.play_arrow_rounded : Icons.pause_rounded,
                    color: Colors.white,
                    size: 48,
                  ),
                ),
              ),
            ),

          // 5. Bursting Heart Animation on Double-Tap
          if (_showHeartAnimation)
            Center(
              child: TweenAnimationBuilder<double>(
                tween: Tween(begin: 0.4, end: 1.3),
                duration: const Duration(milliseconds: 600),
                curve: Curves.elasticOut,
                builder: (context, scale, child) {
                  return Transform.scale(
                    scale: scale,
                    child: const Icon(
                      Icons.favorite_rounded,
                      color: Colors.redAccent,
                      size: 96,
                      shadows: [
                        BoxShadow(color: Colors.black87, blurRadius: 20, spreadRadius: 4),
                      ],
                    ),
                  );
                },
              ),
            ),

          // 6. Right-Side Action Sidebar
          Positioned(
            right: 14,
            bottom: 120,
            child: Column(
              children: [
                // Like Button
                GestureDetector(
                  onTap: widget.onToggleLike,
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
                      Text(
                        '$likesCount',
                        style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),

                // Share Button
                GestureDetector(
                  onTap: widget.onShare,
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
                      const Text(
                        'Share',
                        style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                      ),
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
                    Text(
                      '$viewsCount',
                      style: const TextStyle(color: Colors.white70, fontSize: 11),
                    ),
                  ],
                ),
              ],
            ),
          ),

          // 7. Bottom Sacred Content & Action CTA
          Positioned(
            left: 16,
            right: 80,
            bottom: 30,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Category & Live Badge
                Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
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
                    if (widget.item['badge'] != null) ...[
                      const SizedBox(width: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: Colors.red.withValues(alpha: 0.8),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          widget.item['badge'].toString(),
                          style: const TextStyle(color: Colors.white, fontSize: 9.5, fontWeight: FontWeight.bold),
                        ),
                      ),
                    ],
                  ],
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

                // Interactive CTA Routing Button
                SizedBox(
                  height: 44,
                  child: ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: CustomerTheme.brandGold,
                      foregroundColor: Colors.black,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      elevation: 4,
                    ),
                    icon: const Icon(Icons.flare_rounded, size: 18),
                    label: Text(
                      ctaLabel,
                      style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13),
                    ),
                    onPressed: () => widget.onCtaTap(ctaRoute),
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
