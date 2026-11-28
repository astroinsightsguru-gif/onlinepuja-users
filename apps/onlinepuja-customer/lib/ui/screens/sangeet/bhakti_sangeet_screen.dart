import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:cached_network_image/cached_network_image.dart';

/// Data model representing a sacred Bhakti Sangeet track.
class BhaktiTrack {
  final String id;
  final String title;
  final String hindiTitle;
  final String artist;
  final String category; // 'aarti', 'bhajan', 'chalisa', 'mantra', 'paath'
  final String deity;    // 'hanuman', 'ganesh', 'shiv', 'krishna', 'durga', 'shani'
  final String duration;
  final String imageUrl;
  final String? audioUrl;
  final String lyricsSnippet;

  const BhaktiTrack({
    required this.id,
    required this.title,
    required this.hindiTitle,
    required this.artist,
    required this.category,
    required this.deity,
    required this.duration,
    required this.imageUrl,
    this.audioUrl,
    required this.lyricsSnippet,
  });
}

/// Zero-Clutter, Sacred Bhakti Sangeet & Audio Devotional Hub
/// Inspired by the gold standard Sri Mandir Sangeet UX.
class BhaktiSangeetScreen extends StatefulWidget {
  static const route = '/bhakti-sangeet';

  const BhaktiSangeetScreen({super.key});

  @override
  State<BhaktiSangeetScreen> createState() => _BhaktiSangeetScreenState();
}

class _BhaktiSangeetScreenState extends State<BhaktiSangeetScreen>
    with SingleTickerProviderStateMixin {
  String _selectedDeity = 'top';
  String? _selectedCategory;
  BhaktiTrack? _currentTrack;
  bool _isPlaying = false;
  double _playbackProgress = 0.0;
  Timer? _playbackTimer;
  final Set<String> _bookmarkedIds = {'track_1', 'track_2'};

  // Curated master library of sacred Sanatan audio tracks
  static const List<BhaktiTrack> _allTracks = [
    BhaktiTrack(
      id: 'track_1',
      title: 'Hanuman Chalisa',
      hindiTitle: 'श्री हनुमान चालीसा',
      artist: 'शंकर महादेवन',
      category: 'chalisa',
      deity: 'hanuman',
      duration: '9:48',
      imageUrl: 'https://onlinepuja.live/public/storage/brain_media/brain_dIuvZYDuuGALCxuEJd_940x627.jpg',
      lyricsSnippet: 'जय हनुमान ज्ञान गुन सागर, जय कपीस तिहुँ लोक उजागर...',
    ),
    BhaktiTrack(
      id: 'track_2',
      title: 'Mahamrityunjay Mantra',
      hindiTitle: 'महामृत्युंजय महामंत्र (१०८ जाप)',
      artist: 'शंकर महादेवन',
      category: 'mantra',
      deity: 'shiv',
      duration: '21:15',
      imageUrl: 'https://onlinepuja.live/public/storage/brain_media/brain_FUrvDLYSVY9DFZJ9gt_940x627.jpg',
      lyricsSnippet: 'ॐ त्र्यम्बकं यजामहे सुगन्धिं पुष्टिवर्धनम्। उर्वारुकमिव बन्धनान्...',
    ),
    BhaktiTrack(
      id: 'track_3',
      title: 'Shri Ganesh Aarti',
      hindiTitle: 'जय गणेश जय गणेश देवा (आरती)',
      artist: 'अनुराधा पौडवाल',
      category: 'aarti',
      deity: 'ganesh',
      duration: '4:22',
      imageUrl: 'https://onlinepuja.live/public/storage/pujaCategory/pujaCategory_5_1764232801.jpg',
      lyricsSnippet: 'जय गणेश जय गणेश जय गणेश देवा, माता जाकी पार्वती पिता महादेवा...',
    ),
    BhaktiTrack(
      id: 'track_4',
      title: 'Shiv Tandav Stotram',
      hindiTitle: 'शिव ताण्डव स्तोत्रम्',
      artist: 'उमा मोहन',
      category: 'paath',
      deity: 'shiv',
      duration: '8:40',
      imageUrl: 'https://onlinepuja.live/public/storage/brain_media/brain_T2IxeHWXOutcgfwsUu_940x627.jpg',
      lyricsSnippet: 'जटाटवीगलज्जलप्रवाहपावितस्थले, गलेऽवलम्ब्य लम्बितां भुजङ्गतुङ्गमालिकाम्...',
    ),
    BhaktiTrack(
      id: 'track_5',
      title: 'Shani Dev Mahamantra',
      hindiTitle: 'श्री शनि रक्षा महामंत्र',
      artist: 'हरिहरन',
      category: 'mantra',
      deity: 'shani',
      duration: '11:04',
      imageUrl: 'https://onlinepuja.live/public/storage/brain_media/brain_S2GwG7tPc87uMlcAeM_940x627.jpg',
      lyricsSnippet: 'ॐ प्रां प्रीं प्रौं सः शनैश्चराय नमः। ॐ शं शनैश्चराय नमः...',
    ),
    BhaktiTrack(
      id: 'track_6',
      title: 'Achyutam Keshavam',
      hindiTitle: 'अच्युतम केशवम कृष्ण दामोदरम',
      artist: 'श्रेया घोषाल',
      category: 'bhajan',
      deity: 'krishna',
      duration: '5:32',
      imageUrl: 'https://onlinepuja.live/public/storage/brain_media/brain_u38hGyJyley0LgWAln_940x627.jpg',
      lyricsSnippet: 'अच्युतं केशवं कृष्ण दामोदरं, राम नारायणं जानकी वल्लभम्...',
    ),
    BhaktiTrack(
      id: 'track_7',
      title: 'Durga Chalisa',
      hindiTitle: 'श्री दुर्गा चालीसा',
      artist: 'अनुराधा पौडवाल',
      category: 'chalisa',
      deity: 'durga',
      duration: '7:18',
      imageUrl: 'https://onlinepuja.live/public/storage/brain_media/brain_50wl7KY3nnT2jSnsnF_940x627.jpg',
      lyricsSnippet: 'नमो नमो दुर्गे सुख करनी, नमो नमो अम्बे दुःख हरनी...',
    ),
    BhaktiTrack(
      id: 'track_8',
      title: 'Sundarkand Paath',
      hindiTitle: 'सम्पूर्ण संगीतमय सुंदरकांड पाठ',
      artist: 'अश्विन पाठक',
      category: 'paath',
      deity: 'hanuman',
      duration: '58:30',
      imageUrl: 'https://onlinepuja.live/public/storage/brain_media/brain_TZjr8HWEFQHtdO2zyn_940x627.jpg',
      lyricsSnippet: 'शान्तं शाश्वतमप्रमेयमनघं मोक्षप्रदं शान्तिदं...',
    ),
    BhaktiTrack(
      id: 'track_9',
      title: 'Om Jai Jagdish Hare',
      hindiTitle: 'ॐ जय जगदीश हरे (महा आरती)',
      artist: 'हरिहरन',
      category: 'aarti',
      deity: 'krishna',
      duration: '6:12',
      imageUrl: 'https://onlinepuja.live/public/storage/brain_media/brain_GFb49381IDqvcIOJzp_940x627.jpg',
      lyricsSnippet: 'ॐ जय जगदीश हरे, स्वामी जय जगदीश हरे। भक्त जनों के संकट...',
    ),
  ];

  @override
  void dispose() {
    _playbackTimer?.cancel();
    super.dispose();
  }

  void _togglePlay(BhaktiTrack track) {
    HapticFeedback.mediumImpact();
    if (_currentTrack?.id == track.id) {
      setState(() {
        _isPlaying = !_isPlaying;
        if (!_isPlaying) {
          _playbackTimer?.cancel();
        } else {
          _startProgressSimulation();
        }
      });
      return;
    }

    setState(() {
      _currentTrack = track;
      _isPlaying = true;
      _playbackProgress = 0.05;
    });
    _startProgressSimulation();
  }

  void _startProgressSimulation() {
    _playbackTimer?.cancel();
    _playbackTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) return;
      setState(() {
        if (_playbackProgress < 0.98) {
          _playbackProgress += 0.005;
        } else {
          _playbackProgress = 0.0;
          _isPlaying = false;
          timer.cancel();
        }
      });
    });
  }

  void _toggleBookmark(String trackId) {
    HapticFeedback.lightImpact();
    setState(() {
      if (_bookmarkedIds.contains(trackId)) {
        _bookmarkedIds.remove(trackId);
      } else {
        _bookmarkedIds.add(trackId);
      }
    });
  }

  List<BhaktiTrack> get _filteredTracks {
    return _allTracks.where((track) {
      final matchesDeity = _selectedDeity == 'top' || track.deity == _selectedDeity;
      final matchesCategory = _selectedCategory == null || track.category == _selectedCategory;
      return matchesDeity && matchesCategory;
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final bg = isDark ? const Color(0xFF0F0C1B) : const Color(0xFFFAFAF9);
    final cardBg = isDark ? const Color(0xFF1A1528) : Colors.white;
    final textPrimary = isDark ? Colors.white : const Color(0xFF1E293B);
    final textMuted = isDark ? Colors.white60 : const Color(0xFF64748B);

    return Scaffold(
      backgroundColor: bg,
      body: SafeArea(
        child: Stack(
          children: [
            CustomScrollView(
              physics: const BouncingScrollPhysics(),
              slivers: [
                // 1. Zero-Clutter Header
                SliverToBoxAdapter(
                  child: _buildHeader(isDark, textPrimary),
                ),

                // 2. Deity Quick Pills (Top 20, Hanuman, Ganesh, Shiv, Krishna...)
                SliverToBoxAdapter(
                  child: _buildDeityRow(isDark),
                ),

                // 3. Hero Feature: "आज का मंत्र"
                SliverToBoxAdapter(
                  child: _buildHeroMantraCard(isDark),
                ),

                // 4. 5 Tactile Sacred Categories (Aarti, Bhajan, Chalisa, Mantra, Paath)
                SliverToBoxAdapter(
                  child: _buildCategoryGrid(isDark),
                ),

                // 5. Section Heading: "सभी भक्ति संगीत"
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(18, 20, 18, 10),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          _selectedCategory != null
                              ? '${_categoryLabel(_selectedCategory!)} संगीत'
                              : 'सभी भक्ति संगीत',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w700,
                            color: textPrimary,
                            letterSpacing: -0.2,
                          ),
                        ),
                        if (_selectedCategory != null)
                          GestureDetector(
                            onTap: () => setState(() => _selectedCategory = null),
                            child: const Text(
                              'सभी देखें',
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                                color: Color(0xFFD97706),
                              ),
                            ),
                          ),
                      ],
                    ),
                  ),
                ),

                // 6. Sacred Track List
                SliverPadding(
                  padding: EdgeInsets.fromLTRB(16, 0, 16, _currentTrack != null ? 90 : 30),
                  sliver: SliverList(
                    delegate: SliverChildBuilderDelegate(
                      (context, index) {
                        final track = _filteredTracks[index];
                        return _buildTrackCard(track, isDark, cardBg, textPrimary, textMuted);
                      },
                      childCount: _filteredTracks.length,
                    ),
                  ),
                ),
              ],
            ),

            // 7. Floating Mini-Player (when playing or paused)
            if (_currentTrack != null)
              Positioned(
                left: 14,
                right: 14,
                bottom: 12,
                child: _buildMiniPlayer(isDark),
              ),
          ],
        ),
      ),
    );
  }

  /// Clean, Minimalist Header matching Sri Mandir
  Widget _buildHeader(bool isDark, Color textColor) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 12),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          // Left: User avatar badge
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: isDark ? const Color(0xFF261E38) : const Color(0xFFFFF7ED),
              border: Border.all(color: const Color(0xFFF59E0B), width: 1.5),
            ),
            child: const Icon(Icons.person_rounded, size: 22, color: Color(0xFFD97706)),
          ),

          // Center: Title
          Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.music_note_rounded, size: 20, color: Color(0xFFD97706)),
              const SizedBox(width: 6),
              Text(
                'श्री मंदिर संगीत',
                style: TextStyle(
                  fontSize: 19,
                  fontWeight: FontWeight.w800,
                  color: textColor,
                  letterSpacing: -0.2,
                ),
              ),
            ],
          ),

          // Right: Bookmark icon
          IconButton(
            icon: const Icon(Icons.bookmark_outline_rounded, size: 24),
            color: textColor,
            onPressed: () {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('आपके पसंदीदा भजन एवं मंत्र सहेजे गए हैं'),
                  duration: Duration(seconds: 2),
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  /// Horizontal Circular Deity Pills
  Widget _buildDeityRow(bool isDark) {
    final deities = [
      {'id': 'top', 'label': 'टॉप 20', 'isIcon': true},
      {'id': 'hanuman', 'label': 'हनुमान जी', 'img': 'https://onlinepuja.live/public/storage/brain_media/brain_dIuvZYDuuGALCxuEJd_940x627.jpg'},
      {'id': 'ganesh', 'label': 'गणेश', 'img': 'https://onlinepuja.live/public/storage/pujaCategory/pujaCategory_5_1764232801.jpg'},
      {'id': 'shiv', 'label': 'शिव जी', 'img': 'https://onlinepuja.live/public/storage/brain_media/brain_FUrvDLYSVY9DFZJ9gt_940x627.jpg'},
      {'id': 'krishna', 'label': 'श्री कृष्ण', 'img': 'https://onlinepuja.live/public/storage/brain_media/brain_u38hGyJyley0LgWAln_940x627.jpg'},
      {'id': 'durga', 'label': 'माँ दुर्गा', 'img': 'https://onlinepuja.live/public/storage/brain_media/brain_50wl7KY3nnT2jSnsnF_940x627.jpg'},
      {'id': 'shani', 'label': 'शनि देव', 'img': 'https://onlinepuja.live/public/storage/brain_media/brain_S2GwG7tPc87uMlcAeM_940x627.jpg'},
    ];

    return Container(
      height: 94,
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: ListView.separated(
        padding: const EdgeInsets.symmetric(horizontal: 16),
        scrollDirection: Axis.horizontal,
        physics: const BouncingScrollPhysics(),
        itemCount: deities.length,
        separatorBuilder: (context, index) => const SizedBox(width: 14),
        itemBuilder: (context, index) {
          final d = deities[index];
          final id = d['id'] as String;
          final isSelected = _selectedDeity == id;

          return GestureDetector(
            onTap: () {
              HapticFeedback.selectionClick();
              setState(() => _selectedDeity = id);
            },
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 54,
                  height: 54,
                  padding: const EdgeInsets.all(2.5),
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    border: Border.all(
                      color: isSelected ? const Color(0xFFD97706) : Colors.transparent,
                      width: 2,
                    ),
                    boxShadow: isSelected
                        ? [
                            BoxShadow(
                              color: const Color(0xFFD97706).withValues(alpha: 0.25),
                              blurRadius: 8,
                              spreadRadius: 1,
                            ),
                          ]
                        : null,
                  ),
                  child: ClipOval(
                    child: d['isIcon'] == true
                        ? Container(
                            decoration: const BoxDecoration(
                              gradient: LinearGradient(
                                colors: [Color(0xFFFBBF24), Color(0xFFF59E0B)],
                                begin: Alignment.topLeft,
                                end: Alignment.bottomRight,
                              ),
                            ),
                            child: const Center(
                              child: Icon(Icons.trending_up_rounded, color: Colors.white, size: 28),
                            ),
                          )
                        : CachedNetworkImage(
                            imageUrl: d['img'] as String,
                            fit: BoxFit.cover,
                            placeholder: (_, _) => Container(color: Colors.amber.shade100),
                            errorWidget: (_, _, _) => Container(
                              color: Colors.amber.shade100,
                              child: const Icon(Icons.temple_hindu, color: Color(0xFFD97706)),
                            ),
                          ),
                  ),
                ),
                const SizedBox(height: 5),
                Text(
                  d['label'] as String,
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
                    color: isSelected ? const Color(0xFFD97706) : (isDark ? Colors.white70 : const Color(0xFF475569)),
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  /// "आज का मंत्र" Hero Card
  Widget _buildHeroMantraCard(bool isDark) {
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 8, 16, 14),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF261E14) : const Color(0xFFFFFBEB),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: const Color(0xFFFDE68A),
          width: 1.2,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: isDark ? 0.25 : 0.04),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Stack(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Mantra title badge
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              color: const Color(0xFFB45309).withValues(alpha: 0.12),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: const Text(
                              'आज का मंत्र',
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w700,
                                color: Color(0xFFB45309),
                              ),
                            ),
                          ),
                          const SizedBox(width: 6),
                          const Icon(Icons.auto_awesome_rounded, size: 14, color: Color(0xFFD97706)),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'शनि मंत्र नकारात्मक प्रभावों से रक्षा के लिए',
                        style: TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.w700,
                          color: isDark ? Colors.white : const Color(0xFF1E293B),
                          height: 1.35,
                        ),
                      ),
                      const SizedBox(height: 12),
                      GestureDetector(
                        onTap: () {
                          final shaniTrack = _allTracks.firstWhere((t) => t.id == 'track_5');
                          _togglePlay(shaniTrack);
                        },
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
                          decoration: BoxDecoration(
                            color: const Color(0xFFE11D48),
                            borderRadius: BorderRadius.circular(24),
                            boxShadow: [
                              BoxShadow(
                                color: const Color(0xFFE11D48).withValues(alpha: 0.3),
                                blurRadius: 8,
                                offset: const Offset(0, 3),
                              ),
                            ],
                          ),
                          child: const Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(Icons.play_arrow_rounded, color: Colors.white, size: 18),
                              SizedBox(width: 4),
                              Text(
                                'अभी सुनें',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 12,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 12),
                ClipRRect(
                  borderRadius: BorderRadius.circular(16),
                  child: CachedNetworkImage(
                    imageUrl: 'https://onlinepuja.live/public/storage/brain_media/brain_S2GwG7tPc87uMlcAeM_940x627.jpg',
                    width: 86,
                    height: 86,
                    fit: BoxFit.cover,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  /// 5 Chunky Tactile Category Tiles
  Widget _buildCategoryGrid(bool isDark) {
    final categories = [
      {'key': 'aarti', 'title': 'आरती', 'color': const Color(0xFF5F852A), 'icon': Icons.flare_rounded},
      {'key': 'bhajan', 'title': 'भजन', 'color': const Color(0xFF7350A5), 'icon': Icons.music_note_rounded},
      {'key': 'chalisa', 'title': 'चालीसा', 'color': const Color(0xFFE75628), 'icon': Icons.menu_book_rounded},
      {'key': 'mantra', 'title': 'मंत्र', 'color': const Color(0xFFB5292B), 'icon': Icons.all_inclusive_rounded},
      {'key': 'paath', 'title': 'पाठ', 'color': const Color(0xFF2575C0), 'icon': Icons.import_contacts_rounded},
    ];

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: LayoutBuilder(
        builder: (context, constraints) {
          final itemWidth = (constraints.maxWidth - 12) / 2;

          return Column(
            children: [
              // Row 1: Aarti & Bhajan
              Row(
                children: [
                  _categoryCard(categories[0], itemWidth),
                  const SizedBox(width: 12),
                  _categoryCard(categories[1], itemWidth),
                ],
              ),
              const SizedBox(height: 10),
              // Row 2: Chalisa & Mantra
              Row(
                children: [
                  _categoryCard(categories[2], itemWidth),
                  const SizedBox(width: 12),
                  _categoryCard(categories[3], itemWidth),
                ],
              ),
              const SizedBox(height: 10),
              // Row 3: Paath (clean pill / full width or half)
              Row(
                children: [
                  _categoryCard(categories[4], itemWidth),
                ],
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _categoryCard(Map<String, dynamic> c, double width) {
    final key = c['key'] as String;
    final isSelected = _selectedCategory == key;
    final color = c['color'] as Color;

    return GestureDetector(
      onTap: () {
        HapticFeedback.selectionClick();
        setState(() {
          _selectedCategory = isSelected ? null : key;
        });
      },
      child: Container(
        width: width,
        height: 60,
        padding: const EdgeInsets.symmetric(horizontal: 14),
        decoration: BoxDecoration(
          color: color,
          borderRadius: BorderRadius.circular(16),
          border: isSelected
              ? Border.all(color: Colors.white, width: 2.2)
              : null,
          boxShadow: [
            BoxShadow(
              color: color.withValues(alpha: 0.35),
              blurRadius: 8,
              offset: const Offset(0, 3),
            ),
          ],
        ),
        child: Row(
          children: [
            Container(
              width: 32,
              height: 32,
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.2),
                shape: BoxShape.circle,
              ),
              child: Icon(c['icon'] as IconData, color: Colors.white, size: 18),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                c['title'] as String,
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                  color: Colors.white,
                  letterSpacing: 0.2,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  /// Single Track Item Card
  Widget _buildTrackCard(
    BhaktiTrack track,
    bool isDark,
    Color cardBg,
    Color textPrimary,
    Color textMuted,
  ) {
    final isCurrent = _currentTrack?.id == track.id;
    final isBookmarked = _bookmarkedIds.contains(track.id);

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: cardBg,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isCurrent
              ? const Color(0xFFD97706)
              : (isDark ? Colors.white10 : const Color(0xFFE2E8F0)),
          width: isCurrent ? 1.5 : 1,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: isDark ? 0.2 : 0.03),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          // Track Deity Artwork
          ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: CachedNetworkImage(
              imageUrl: track.imageUrl,
              width: 52,
              height: 52,
              fit: BoxFit.cover,
              placeholder: (_, _) => Container(color: Colors.amber.shade100),
            ),
          ),
          const SizedBox(width: 12),

          // Title & Artist
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  track.hindiTitle,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                    color: isCurrent ? const Color(0xFFD97706) : textPrimary,
                  ),
                ),
                const SizedBox(height: 3),
                Row(
                  children: [
                    Text(
                      track.artist,
                      style: TextStyle(
                        fontSize: 13,
                        color: textMuted,
                      ),
                    ),
                    const SizedBox(width: 6),
                    Text('•', style: TextStyle(color: textMuted, fontSize: 10)),
                    const SizedBox(width: 6),
                    Text(
                      track.duration,
                      style: TextStyle(fontSize: 12, color: textMuted),
                    ),
                  ],
                ),
              ],
            ),
          ),

          // Play / Pause Circle Button
          GestureDetector(
            onTap: () => _togglePlay(track),
            child: Container(
              width: 38,
              height: 38,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(color: const Color(0xFFEA580C), width: 1.8),
                color: (isCurrent && _isPlaying)
                    ? const Color(0xFFEA580C)
                    : Colors.transparent,
              ),
              child: Icon(
                (isCurrent && _isPlaying)
                    ? Icons.pause_rounded
                    : Icons.play_arrow_rounded,
                color: (isCurrent && _isPlaying) ? Colors.white : const Color(0xFFEA580C),
                size: 22,
              ),
            ),
          ),

          // 3-dot overflow menu
          PopupMenuButton<String>(
            icon: Icon(Icons.more_vert_rounded, color: textMuted, size: 20),
            padding: EdgeInsets.zero,
            onSelected: (val) {
              if (val == 'bookmark') {
                _toggleBookmark(track.id);
              } else if (val == 'share') {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text('${track.hindiTitle} शेयर किया गया!')),
                );
              }
            },
            itemBuilder: (context) => [
              PopupMenuItem(
                value: 'bookmark',
                child: Row(
                  children: [
                    Icon(
                      isBookmarked ? Icons.bookmark_rounded : Icons.bookmark_border_rounded,
                      color: const Color(0xFFD97706),
                      size: 20,
                    ),
                    const SizedBox(width: 8),
                    Text(isBookmarked ? 'हटाएं' : 'सहेजें'),
                  ],
                ),
              ),
              const PopupMenuItem(
                value: 'share',
                child: Row(
                  children: [
                    Icon(Icons.share_rounded, size: 20),
                    SizedBox(width: 8),
                    Text('शेयर करें'),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  /// Floating Mini-Player Bar
  Widget _buildMiniPlayer(bool isDark) {
    final track = _currentTrack!;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF231B36) : const Color(0xFF1E293B),
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.35),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Row(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(10),
                child: CachedNetworkImage(
                  imageUrl: track.imageUrl,
                  width: 42,
                  height: 42,
                  fit: BoxFit.cover,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      track.hindiTitle,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    Text(
                      track.artist,
                      style: const TextStyle(color: Colors.white70, fontSize: 12),
                    ),
                  ],
                ),
              ),
              // Play/Pause Action
              IconButton(
                icon: Icon(
                  _isPlaying ? Icons.pause_circle_filled_rounded : Icons.play_circle_filled_rounded,
                  color: const Color(0xFFF59E0B),
                  size: 34,
                ),
                onPressed: () => _togglePlay(track),
              ),
              // Close Mini Player
              IconButton(
                icon: const Icon(Icons.close_rounded, color: Colors.white60, size: 20),
                onPressed: () {
                  _playbackTimer?.cancel();
                  setState(() {
                    _isPlaying = false;
                    _currentTrack = null;
                  });
                },
              ),
            ],
          ),
          const SizedBox(height: 4),
          // Subtle Progress Bar
          ClipRRect(
            borderRadius: BorderRadius.circular(2),
            child: LinearProgressIndicator(
              value: _playbackProgress,
              minHeight: 2.5,
              backgroundColor: Colors.white12,
              valueColor: const AlwaysStoppedAnimation<Color>(Color(0xFFF59E0B)),
            ),
          ),
        ],
      ),
    );
  }

  String _categoryLabel(String key) {
    switch (key) {
      case 'aarti': return 'आरती';
      case 'bhajan': return 'भजन';
      case 'chalisa': return 'चालीसा';
      case 'mantra': return 'मंत्र';
      case 'paath': return 'पाठ';
      default: return '';
    }
  }
}
