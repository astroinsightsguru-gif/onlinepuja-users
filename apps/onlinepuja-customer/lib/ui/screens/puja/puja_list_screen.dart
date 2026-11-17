import 'package:flutter/material.dart';
import 'package:op_shared/op_shared.dart';

import '../../theme/customer_theme.dart';
import 'puja_detail_screen.dart';
import '../history/history_screen.dart';

/// Authentic OnlinePuja Sacred Vedic Puja & Chadhava Experience
class PujaListScreen extends StatefulWidget {
  const PujaListScreen({super.key});

  static const route = '/pujas';

  @override
  State<PujaListScreen> createState() => _PujaListScreenState();
}

class _PujaListScreenState extends State<PujaListScreen> {
  List<Puja>? _items;
  Object? _error;
  dynamic _categoryId;
  String _selectedDeity = "All";
  String _searchQuery = "";
  bool _isPlaying = true;

  final TextEditingController _searchController = TextEditingController();

  final List<Map<String, String>> _deityPills = [
    {"id": "All", "label": "All Pujas", "icon": "ॐ"},
    {"id": "Diwali", "label": "Diwali & Lakshmi", "icon": "🪔"},
    {"id": "Dev Deepawali", "label": "Dev Deepawali", "icon": "✨"},
    {"id": "Kashi", "label": "Kashi & Ganga", "icon": "🌊"},
    {"id": "Durga", "label": "Maa Durga", "icon": "🌺"},
    {"id": "Shiva", "label": "Mahadev Shiva", "icon": "🔱"},
    {"id": "Karwa Chauth", "label": "Karwa Chauth", "icon": "🌕"},
    {"id": "Ganesh", "label": "Lord Ganesh", "icon": "🐘"},
    {"id": "Vishnu", "label": "Bhagwan Vishnu", "icon": "🪷"},
    {"id": "Navagraha", "label": "Graha Shanti", "icon": "🪐"},
  ];

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() => _error = null);
    try {
      final items = await PujaApi.instance.list(categoryId: _categoryId);
      if (mounted) {
        setState(() {
          _items = items;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  static String _formatTithi(dynamic rawDate) {
    if (rawDate == null) return "Auspicious Vedic Tithi";
    final s = rawDate.toString();
    if (s.length >= 10) {
      return "${s.substring(0, 10)} • Sacred Tithi";
    }
    return s;
  }

  List<Puja> get _filteredItems {
    if (_items == null) return const [];
    var list = _items!;

    // 1. Deity filter
    if (_selectedDeity != "All") {
      final q = _selectedDeity.toLowerCase();
      list = list.where((p) {
        final title = (p.title ?? '').toLowerCase();
        final sub = (p.subtitle ?? '').toLowerCase();
        final desc = (p.longDescription ?? '').toString().toLowerCase();
        final combined = '$title $sub $desc';
        if (q.contains('diwali')) {
          return combined.contains('diwali') || combined.contains('lakshmi') || combined.contains('laxmi') || combined.contains('kuber') || combined.contains('dhanteras');
        }
        if (q.contains('deepawali') || q.contains('deepdaan')) {
          return combined.contains('deepawali') || combined.contains('deepdaan') || combined.contains('diwali') || combined.contains('ganga') || combined.contains('kashi');
        }
        if (q.contains('kashi')) {
          return combined.contains('kashi') || combined.contains('varanasi') || combined.contains('ganga') || combined.contains('vishwanath') || combined.contains('ghat');
        }
        if (q.contains('karwa')) {
          return combined.contains('karwa') || combined.contains('suhag') || combined.contains('parvati') || combined.contains('chauth');
        }
        if (q == 'durga') {
          return combined.contains('durga') || combined.contains('navratri') || combined.contains('kanya') || combined.contains('devi') || combined.contains('saptashati');
        }
        if (q == 'shiva') {
          return combined.contains('shiva') || combined.contains('rudra') || combined.contains('mrityunjay') || combined.contains('mahadev') || combined.contains('kashi') || combined.contains('lingam');
        }
        if (q == 'ganesh') {
          return combined.contains('ganesh') || combined.contains('vinayak') || combined.contains('ganpati') || combined.contains('modak');
        }
        if (q == 'vishnu') {
          return combined.contains('vishnu') || combined.contains('satyanarayan') || combined.contains('krishna') || combined.contains('ram') || combined.contains('ekadashi');
        }
        if (q == 'navagraha') {
          return combined.contains('navagraha') || combined.contains('graha') || combined.contains('shanti') || combined.contains('dosh') || combined.contains('mangal') || combined.contains('kaal sarp');
        }
        return combined.contains(q);
      }).toList();
    }

    // 2. Search query filter
    if (_searchQuery.trim().isNotEmpty) {
      final term = _searchQuery.trim().toLowerCase();
      list = list.where((p) {
        final title = (p.title ?? '').toLowerCase();
        final sub = (p.subtitle ?? '').toLowerCase();
        final place = (p.place ?? '').toString().toLowerCase();
        return title.contains(term) || sub.contains(term) || place.contains(term);
      }).toList();
    }

    return list;
  }

  @override
  Widget build(BuildContext context) {
    final displayItems = _filteredItems;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final scheme = Theme.of(context).colorScheme;
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
        titleSpacing: 16,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                RichText(
                  text: TextSpan(
                    children: [
                      TextSpan(
                        text: 'OnlinePuja',
                        style: TextStyle(
                          fontSize: 16.5,
                          fontWeight: FontWeight.w900,
                          color: textPrimary,
                          letterSpacing: -0.2,
                        ),
                      ),
                      const TextSpan(
                        text: '.live',
                        style: TextStyle(
                          fontSize: 16.5,
                          fontWeight: FontWeight.w900,
                          color: Color(0xFFD97706),
                          letterSpacing: -0.2,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 6),
                const Text('🛕', style: TextStyle(fontSize: 15)),
              ],
            ),
            const SizedBox(height: 1),
            Text(
              'Vedic Sankalp at 51 Holy Teerths & Shaktipeeths',
              style: TextStyle(fontSize: 10.5, color: textSecondary, fontWeight: FontWeight.w500),
            ),
          ],
        ),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 14),
            child: InkWell(
              onTap: () {
                Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => const HistoryScreen()),
                );
              },
              borderRadius: BorderRadius.circular(20),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 6),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFFFEF3C7), Color(0xFFFDE68A)],
                  ),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: const Color(0xFFF59E0B).withValues(alpha: 0.5)),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: const [
                    Text("🛍️", style: TextStyle(fontSize: 12)),
                    SizedBox(width: 4),
                    Text(
                      "Bookings",
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w800,
                        color: Color(0xFF92400E),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
      body: _error != null
          ? StatusViews.error(context, _error!, onRetry: _load)
          : RefreshIndicator(
              onRefresh: _load,
              color: const Color(0xFFD97706),
              child: CustomScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                slivers: [
                  // 1. Sleek Search Bar at Top
                  SliverToBoxAdapter(child: _buildSearchBar(cardBg, cardBorder, textPrimary, textSecondary)),

                  // 2. Clean Deity & Category Filter Pills (Single Row)
                  SliverToBoxAdapter(child: _buildDeityFilterStrip(cardBg, cardBorder, isDark)),

                  // 3. Compact Devotional Ambient Chant Bar
                  SliverToBoxAdapter(child: _buildAmbientAudioBar(isDark)),

                  // 4. Section Header
                  SliverToBoxAdapter(
                    child: Padding(
                      padding: const EdgeInsets.fromLTRB(16, 12, 16, 6),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Row(
                            children: [
                              Container(
                                width: 4,
                                height: 16,
                                decoration: BoxDecoration(
                                  color: const Color(0xFFD97706),
                                  borderRadius: BorderRadius.circular(2),
                                ),
                              ),
                              const SizedBox(width: 8),
                              Text(
                                _selectedDeity == "All" ? "Upcoming Auspicious Pujas" : "$_selectedDeity Special Pujas",
                                style: TextStyle(
                                  fontSize: 14.5,
                                  fontWeight: FontWeight.w800,
                                  color: textPrimary,
                                ),
                              ),
                            ],
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              color: isDark ? CustomerTheme.cosmicCardElevated : const Color(0xFFF1F5F9),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Text(
                              "${displayItems.length} Available",
                              style: TextStyle(
                                fontSize: 10.5,
                                fontWeight: FontWeight.w700,
                                color: textSecondary,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),

                  // 7. Sacred Puja Cards Feed
                  if (_items == null)
                    const SliverPadding(
                      padding: EdgeInsets.all(60),
                      sliver: SliverToBoxAdapter(
                        child: Center(
                          child: CircularProgressIndicator(strokeWidth: 2.6, color: Color(0xFFD97706)),
                        ),
                      ),
                    )
                  else if (displayItems.isEmpty)
                    SliverToBoxAdapter(
                      child: Container(
                        height: 280,
                        alignment: Alignment.center,
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Text("🪔", style: TextStyle(fontSize: 48)),
                            const SizedBox(height: 12),
                            Text(
                              'No pujas found for "$_selectedDeity"',
                              style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: textPrimary),
                            ),
                            const SizedBox(height: 6),
                            Text(
                              'Try selecting "All Pujas" or clear search filter',
                              style: TextStyle(fontSize: 12, color: textSecondary),
                            ),
                            const SizedBox(height: 16),
                            TextButton.icon(
                              onPressed: () {
                                setState(() {
                                  _selectedDeity = "All";
                                  _categoryId = null;
                                  _searchQuery = "";
                                  _searchController.clear();
                                });
                              },
                              icon: const Icon(Icons.refresh_rounded, size: 16),
                              label: const Text("Reset All Filters"),
                              style: TextButton.styleFrom(foregroundColor: const Color(0xFFD97706)),
                            ),
                          ],
                        ),
                      ),
                    )
                  else
                    SliverPadding(
                      padding: const EdgeInsets.fromLTRB(14, 8, 14, 120),
                      sliver: SliverList.separated(
                        itemCount: displayItems.length,
                        separatorBuilder: (_, _) => const SizedBox(height: 18),
                        itemBuilder: (context, i) => _buildSriMandirPujaCard(
                          context,
                          displayItems[i],
                          cardBg,
                          cardBorder,
                          textPrimary,
                          textSecondary,
                          isDark,
                        ),
                      ),
                    ),
                ],
              ),
            ),
    );
  }

  /// Compact Devotional Ambient Chant Bar
  Widget _buildAmbientAudioBar(bool isDark) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          color: isDark ? CustomerTheme.cosmicCardElevated : const Color(0xFF1E293B),
          borderRadius: BorderRadius.circular(14),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.1),
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
              decoration: const BoxDecoration(
                shape: BoxShape.circle,
                gradient: LinearGradient(
                  colors: [Color(0xFFF59E0B), Color(0xFFD97706)],
                ),
              ),
              child: const Center(
                child: Text("🕉️", style: TextStyle(fontSize: 16)),
              ),
            ),
            const SizedBox(width: 10),
            const Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    "Vakratunda Mahakaya • OnlinePuja Vedic Dhyaan",
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  SizedBox(height: 1),
                  Text(
                    "Devotional Chants • 4,280 devotees listening",
                    style: TextStyle(color: Color(0xFF94A3B8), fontSize: 9.5),
                  ),
                ],
              ),
            ),
            IconButton(
              padding: EdgeInsets.zero,
              constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
              icon: Icon(
                _isPlaying ? Icons.pause_circle_filled_rounded : Icons.play_circle_filled_rounded,
                color: const Color(0xFFFBBF24),
                size: 26,
              ),
              onPressed: () {
                setState(() => _isPlaying = !_isPlaying);
              },
            ),
          ],
        ),
      ),
    );
  }

  /// Instant Live Search Bar
  Widget _buildSearchBar(Color cardBg, Color cardBorder, Color textPrimary, Color textSecondary) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 8, 14, 6),
      child: Container(
        height: 42,
        decoration: BoxDecoration(
          color: cardBg,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: cardBorder),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.02),
              blurRadius: 4,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: TextField(
          controller: _searchController,
          onChanged: (v) => setState(() => _searchQuery = v),
          style: TextStyle(fontSize: 12.5, color: textPrimary),
          decoration: InputDecoration(
            hintText: "Search Puja, Deity, or Holy Dham (Kashi, Gaya...)",
            hintStyle: TextStyle(fontSize: 11.5, color: textSecondary),
            prefixIcon: const Icon(Icons.search_rounded, size: 18, color: Color(0xFFD97706)),
            suffixIcon: _searchQuery.isNotEmpty
                ? IconButton(
                    icon: Icon(Icons.clear_rounded, size: 16, color: textSecondary),
                    onPressed: () {
                      _searchController.clear();
                      setState(() => _searchQuery = "");
                    },
                  )
                : null,
            border: InputBorder.none,
            contentPadding: const EdgeInsets.symmetric(vertical: 10),
          ),
        ),
      ),
    );
  }

  /// Custom Deity Filter Strip (Zero Overflow, Animated, Elegant)
  Widget _buildDeityFilterStrip(Color cardBg, Color cardBorder, bool isDark) {
    return SizedBox(
      height: 44,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
        itemCount: _deityPills.length,
        separatorBuilder: (_, _) => const SizedBox(width: 8),
        itemBuilder: (context, index) {
          final item = _deityPills[index];
          final id = item["id"]!;
          final isSelected = _selectedDeity == id;

          return InkWell(
            onTap: () => setState(() => _selectedDeity = id),
            borderRadius: BorderRadius.circular(20),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 180),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              decoration: BoxDecoration(
                gradient: isSelected
                    ? const LinearGradient(
                        colors: [Color(0xFFD97706), Color(0xFFB45309)],
                      )
                    : null,
                color: isSelected ? null : cardBg,
                borderRadius: BorderRadius.circular(20),
                border: Border.all(
                  color: isSelected ? const Color(0xFFD97706) : cardBorder,
                  width: 1.2,
                ),
                boxShadow: isSelected
                    ? [
                        BoxShadow(
                          color: const Color(0xFFD97706).withValues(alpha: 0.3),
                          blurRadius: 6,
                          offset: const Offset(0, 2),
                        ),
                      ]
                    : null,
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(item["icon"]!, style: const TextStyle(fontSize: 12.5)),
                  const SizedBox(width: 5),
                  Text(
                    item["label"]!,
                    style: TextStyle(
                      fontSize: 11.5,
                      fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
                      color: isSelected
                          ? Colors.white
                          : (isDark ? Colors.white70 : const Color(0xFF334155)),
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

  String _cleanPujaTitle(String? raw) {
    if (raw == null || raw.isEmpty) return 'Vedic Mahapuja';
    if (raw.contains(' - ')) return raw.split(' - ').first.trim();
    if (raw.contains(' | ')) return raw.split(' | ').first.trim();
    return raw.trim();
  }

  String _cleanPujaSubtitle(Puja p) {
    final sub = p.subtitle?.toString() ?? '';
    if (sub.isNotEmpty) {
      if (sub.length > 80) return '${sub.substring(0, 77)}...';
      return sub;
    }
    final rawTitle = p.title?.toString() ?? '';
    if (rawTitle.contains(' - ')) {
      final part = rawTitle.split(' - ').sublist(1).join(' - ').trim();
      if (part.length > 80) return '${part.substring(0, 77)}...';
      return part;
    }
    return 'Vedic Sankalp & Vidhi by certified Kashi Shastris';
  }

  /// High Quality Sri Mandir-Grade Puja Card
  Widget _buildSriMandirPujaCard(
    BuildContext context,
    Puja p,
    Color cardBg,
    Color cardBorder,
    Color textPrimary,
    Color textSecondary,
    bool isDark,
  ) {
    final price = p.startingPrice ?? 501;
    final strikePrice = (price * 2.2).round();
    final place = (p.place != null && p.place.toString().isNotEmpty)
        ? p.place.toString()
        : "Holy Pilgrimage Teerth";
    final tithi = _formatTithi(p.startDatetime);

    return Container(
      decoration: BoxDecoration(
        color: cardBg,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: cardBorder),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: isDark ? 0.2 : 0.05),
            blurRadius: 14,
            offset: const Offset(0, 5),
          ),
        ],
      ),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () => Navigator.of(context).push(
          MaterialPageRoute(builder: (_) => PujaDetailScreen(puja: p)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // 1. Cover Image with Sacred Gradient & Badges
            Stack(
              children: [
                SizedBox(
                  height: 165,
                  width: double.infinity,
                  child: _buildPujaCoverImage(p),
                ),
                // Gradient Scrim for contrast
                Positioned.fill(
                  child: DecoratedBox(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [
                          Colors.black.withValues(alpha: 0.45),
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
                // Top Badges
                Positioned(
                  top: 10,
                  left: 10,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFFD97706), Color(0xFFB45309)],
                      ),
                      borderRadius: BorderRadius.circular(10),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.2),
                          blurRadius: 4,
                        ),
                      ],
                    ),
                    child: const Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text("⭐ ", style: TextStyle(fontSize: 10)),
                        Text(
                          "Vedic Mahapuja",
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 10,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                Positioned(
                  top: 10,
                  right: 10,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: Colors.black.withValues(alpha: 0.6),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: Colors.white.withValues(alpha: 0.2)),
                    ),
                    child: const Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text("🔥 1,280+ Devotees", style: TextStyle(color: Color(0xFFFDE68A), fontSize: 9.5, fontWeight: FontWeight.w700)),
                      ],
                    ),
                  ),
                ),
                // Bottom Date Pill on Image
                Positioned(
                  bottom: 10,
                  left: 12,
                  right: 12,
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: const Color(0xFF0284C7).withValues(alpha: 0.9),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.calendar_month_rounded, size: 11, color: Colors.white),
                            const SizedBox(width: 4),
                            Text(
                              tithi,
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 10,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),

            // 2. Card Content
            Padding(
              padding: const EdgeInsets.all(14.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Title (Cleaned up, no delimiter spam)
                  Text(
                    _cleanPujaTitle(p.title?.toString()),
                    style: TextStyle(
                      fontSize: 15.5,
                      fontWeight: FontWeight.w900,
                      color: textPrimary,
                      letterSpacing: -0.2,
                      height: 1.25,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),

                  // Subtitle (Concise, single-line)
                  Text(
                    _cleanPujaSubtitle(p),
                    style: TextStyle(fontSize: 11, color: textSecondary, height: 1.3),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 6),

                  // Teerth Location
                  Row(
                    children: [
                      const Icon(Icons.temple_hindu_rounded, size: 14, color: Color(0xFFD97706)),
                      const SizedBox(width: 5),
                      Expanded(
                        child: Text(
                          place,
                          style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600, color: textSecondary),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),

                  // 3 Key Guarantees Pill Strip
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                    decoration: BoxDecoration(
                      color: isDark ? CustomerTheme.cosmicCardElevated : const Color(0xFFF8FAFC),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: cardBorder),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceAround,
                      children: [
                        _PujaPerk(icon: "📹", label: "Video Proof", isDark: isDark),
                        _PujaPerk(icon: "🌸", label: "Gotra Chanted", isDark: isDark),
                        _PujaPerk(icon: "📦", label: "Home Prasad", isDark: isDark),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),

                  // Price & CTA Row
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Text("Sankalp Starts ", style: TextStyle(fontSize: 9.5, color: textSecondary)),
                              Text(
                                "₹$strikePrice",
                                style: TextStyle(
                                  fontSize: 10,
                                  color: textSecondary,
                                  decoration: TextDecoration.lineThrough,
                                ),
                              ),
                            ],
                          ),
                          Row(
                            children: [
                              Text(
                                "₹${price.toStringAsFixed(0)}",
                                style: const TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.w900,
                                  color: Color(0xFFD97706),
                                ),
                              ),
                              const SizedBox(width: 6),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFDCFCE7),
                                  borderRadius: BorderRadius.circular(4),
                                ),
                                child: const Text(
                                  "54% OFF",
                                  style: TextStyle(
                                    fontSize: 8.5,
                                    fontWeight: FontWeight.w800,
                                    color: Color(0xFF15803D),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                      ElevatedButton(
                        onPressed: () => Navigator.of(context).push(
                          MaterialPageRoute(builder: (_) => PujaDetailScreen(puja: p)),
                        ),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF059669),
                          elevation: 2,
                          shadowColor: const Color(0xFF059669).withValues(alpha: 0.4),
                          padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 11),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(22),
                          ),
                        ),
                        child: const Row(
                          children: [
                            Text(
                              "PARTICIPATE",
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 12,
                                fontWeight: FontWeight.w900,
                                letterSpacing: 0.3,
                              ),
                            ),
                            SizedBox(width: 5),
                            Icon(Icons.arrow_forward_rounded, size: 14, color: Colors.white),
                          ],
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
  }

  /// Resilient multi-tier image loader with spiritual defaults
  Widget _buildPujaCoverImage(Puja p) {
    final rawUrl = p.coverImage.trim();
    final resolvedUrl = rawUrl.isNotEmpty ? MiscApi.imageUrl(rawUrl) : '';

    if (resolvedUrl.isNotEmpty) {
      return Image.network(
        resolvedUrl,
        fit: BoxFit.cover,
        errorBuilder: (context, error, stackTrace) => _buildDivineFallbackImage(p),
        loadingBuilder: (context, child, progress) {
          if (progress == null) return child;
          return Container(
            color: const Color(0xFFFEF3C7),
            child: const Center(
              child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFFD97706)),
            ),
          );
        },
      );
    }

    return _buildDivineFallbackImage(p);
  }

  /// Gorgeous spiritual art fallback when image is unavailable
  Widget _buildDivineFallbackImage(Puja p) {
    final title = (p.title ?? '').toLowerCase();
    String symbol = "ॐ";
    Color topColor = const Color(0xFF78350F);
    Color bottomColor = const Color(0xFFB45309);

    if (title.contains('durga') || title.contains('navratri') || title.contains('kanya')) {
      symbol = "🌺";
      topColor = const Color(0xFF881337);
      bottomColor = const Color(0xFFBE123C);
    } else if (title.contains('shiva') || title.contains('rudra') || title.contains('mrityunjay')) {
      symbol = "🔱";
      topColor = const Color(0xFF0F172A);
      bottomColor = const Color(0xFF334155);
    } else if (title.contains('ganesh') || title.contains('vinayak')) {
      symbol = "🐘";
      topColor = const Color(0xFF7C2D12);
      bottomColor = const Color(0xFFEA580C);
    } else if (title.contains('lakshmi') || title.contains('kubera') || title.contains('dhanteras')) {
      symbol = "🪔";
      topColor = const Color(0xFF713F12);
      bottomColor = const Color(0xFFCA8A04);
    } else if (title.contains('shradh') || title.contains('pitru') || title.contains('gaya')) {
      symbol = "🪶";
      topColor = const Color(0xFF1C1917);
      bottomColor = const Color(0xFF44403C);
    }

    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [topColor, bottomColor],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: Stack(
        children: [
          Positioned(
            right: 20,
            bottom: -10,
            child: Opacity(
              opacity: 0.15,
              child: const Text("ॐ", style: TextStyle(fontSize: 130, color: Colors.white)),
            ),
          ),
          Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(symbol, style: const TextStyle(fontSize: 42)),
                const SizedBox(height: 6),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.2),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Text(
                    "Sacred Teerth Seva",
                    style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.w700),
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

class _PujaPerk extends StatelessWidget {
  const _PujaPerk({required this.icon, required this.label, this.isDark = false});
  final String icon;
  final String label;
  final bool isDark;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(icon, style: const TextStyle(fontSize: 10.5)),
        const SizedBox(width: 4),
        Text(
          label,
          style: TextStyle(
            fontSize: 10,
            fontWeight: FontWeight.w700,
            color: isDark ? Colors.white70 : const Color(0xFF475569),
          ),
        ),
      ],
    );
  }
}

