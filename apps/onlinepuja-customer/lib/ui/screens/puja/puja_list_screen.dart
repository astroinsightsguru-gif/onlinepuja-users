import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
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
  final FocusNode _searchFocusNode = FocusNode();
  final ScrollController _scrollController = ScrollController();

  final List<Map<String, String>> _deityPills = [
    {"id": "All", "label": "All Pujas", "labelHi": "समस्त पावन पूजाएं", "icon": "ॐ"},
    {"id": "Diwali", "label": "Diwali & Lakshmi", "labelHi": "दीपावली एवं महालक्ष्मी", "icon": "🪔"},
    {"id": "Dev Deepawali", "label": "Dev Deepawali", "labelHi": "देव दीपावली दीपदान", "icon": "✨"},
    {"id": "Kashi", "label": "Kashi & Ganga", "labelHi": "काशी एवं मां गंगा", "icon": "🌊"},
    {"id": "Durga", "label": "Maa Durga", "labelHi": "मां भगवती दुर्गा", "icon": "🌺"},
    {"id": "Shiva", "label": "Mahadev Shiva", "labelHi": "देवाधिदेव महादेव", "icon": "🔱"},
    {"id": "Karwa Chauth", "label": "Karwa Chauth", "labelHi": "करवा चौथ सौभाग्य", "icon": "🌕"},
    {"id": "Ganesh", "label": "Lord Ganesh", "labelHi": "विघ्नहर्ता श्री गणेश", "icon": "🐘"},
    {"id": "Vishnu", "label": "Bhagwan Vishnu", "labelHi": "भगवान श्री हरि विष्णु", "icon": "🪷"},
    {"id": "Navagraha", "label": "Graha Shanti", "labelHi": "नवग्रह शांति महायज्ञ", "icon": "🪐"},
  ];

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _searchController.dispose();
    _searchFocusNode.dispose();
    _scrollController.dispose();
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

    return ValueListenableBuilder<AppLanguage>(
      valueListenable: LocaleManager.instance.currentLanguage,
      builder: (context, lang, _) {
        final isHi = lang != AppLanguage.en;

        return Scaffold(
          backgroundColor: isDark ? scheme.surface : const Color(0xFFFBF9F5),
          appBar: AppBar(
            backgroundColor: isDark ? scheme.surface : const Color(0xFFFBF9F5),
            foregroundColor: scheme.onSurface,
            elevation: 0,
            titleSpacing: 12,
            leading: Padding(
              padding: const EdgeInsets.only(left: 12),
              child: Center(
                child: Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    color: const Color(0xFFEA580C).withValues(alpha: 0.12),
                    shape: BoxShape.circle,
                    border: Border.all(color: const Color(0xFFEA580C).withValues(alpha: 0.25)),
                  ),
                  child: const Icon(Icons.person_rounded, size: 22, color: Color(0xFFEA580C)),
                ),
              ),
            ),
            title: Text(
              isHi ? 'पूजा सेवा' : 'Puja Seva',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.w900,
                color: textPrimary,
                letterSpacing: -0.3,
              ),
            ),
            actions: [
              // Search Icon Button
              InkWell(
                onTap: () {
                  if (_scrollController.hasClients) {
                    _scrollController.animateTo(
                      0,
                      duration: const Duration(milliseconds: 300),
                      curve: Curves.easeInOut,
                    );
                  }
                  _searchFocusNode.requestFocus();
                },
                borderRadius: BorderRadius.circular(20),
                child: Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    color: isDark ? Colors.white10 : Colors.black.withValues(alpha: 0.05),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(Icons.search_rounded, size: 20, color: textPrimary),
                ),
              ),
              const SizedBox(width: 8),

              // Sri Mandir Bookings Pill
              InkWell(
                onTap: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(builder: (_) => const HistoryScreen()),
                  );
                },
                borderRadius: BorderRadius.circular(20),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFFF7ED),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: const Color(0xFFFDBA74)),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.shopping_bag_outlined, size: 14, color: Color(0xFFEA580C)),
                      const SizedBox(width: 4),
                      Text(
                        isHi ? "बुकिंग्स" : "Bookings",
                        style: const TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w800,
                          color: Color(0xFFEA580C),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 8),

              // Language Switch Pill
              GestureDetector(
                onTap: () => LocaleManager.instance.setLanguage(
                  isHi ? AppLanguage.en : AppLanguage.hi,
                ),
                child: Container(
                  margin: const EdgeInsets.only(right: 12),
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
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
            ],
          ),
          body: _error != null
              ? StatusViews.error(context, _error!, onRetry: _load)
              : RefreshIndicator(
                  onRefresh: _load,
                  color: const Color(0xFFD97706),
                  child: CustomScrollView(
                    controller: _scrollController,
                    physics: const AlwaysScrollableScrollPhysics(),
                    slivers: [
                      // 1. Sleek Search Bar at Top
                      SliverToBoxAdapter(child: _buildSearchBar(cardBg, cardBorder, textPrimary, textSecondary, isHi)),

                      // 2. Clean Deity & Category Filter Pills (Single Row)
                      SliverToBoxAdapter(child: _buildDeityFilterStrip(cardBg, cardBorder, isDark, isHi)),

                      // 3. Compact Devotional Ambient Chant Bar
                      SliverToBoxAdapter(child: _buildAmbientAudioBar(isDark, isHi)),

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
                                    _selectedDeity == "All"
                                        ? (isHi ? "आगामी पावन पर्व एवं शुभ अनुष्ठान" : "Upcoming Auspicious Pujas")
                                        : (isHi ? "$_selectedDeity विशेष पावन पूजाएं" : "$_selectedDeity Special Pujas"),
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
                                  "${displayItems.length} ${isHi ? 'उपलब्ध' : 'Available'}",
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
                                  isHi
                                      ? '"$_selectedDeity" के लिए कोई पूजा नहीं मिली'
                                      : 'No pujas found for "$_selectedDeity"',
                                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: textPrimary),
                                ),
                                const SizedBox(height: 6),
                                Text(
                                  isHi
                                      ? 'अन्य देवी-देवता चुनें या खोज फ़िल्टर हटाएं'
                                      : 'Try selecting "All Pujas" or clear search filter',
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
                                  label: Text(isHi ? "फ़िल्टर रीसेट करें" : "Reset All Filters"),
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
                              isHi,
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
        );
      },
    );
  }

  /// Compact Devotional Ambient Chant Bar
  Widget _buildAmbientAudioBar(bool isDark, [bool isHi = false]) {
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
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    isHi
                        ? "वक्रतुण्ड महाकाय • ऑनलाइन पूजा वैदिक ध्यान"
                        : "Vakratunda Mahakaya • OnlinePuja Vedic Dhyaan",
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 1),
                  Text(
                    isHi
                        ? "पावन मंत्र श्रवण • ४,२८०+ यजमान श्रवण कर रहे हैं"
                        : "Devotional Chants • 4,280 devotees listening",
                    style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 9.5),
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
  Widget _buildSearchBar(Color cardBg, Color cardBorder, Color textPrimary, Color textSecondary, [bool isHi = false]) {
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
          focusNode: _searchFocusNode,
          onChanged: (v) => setState(() => _searchQuery = v),
          style: TextStyle(fontSize: 12.5, color: textPrimary),
          decoration: InputDecoration(
            hintText: isHi
                ? "पूजा, इष्टदेव या पावन धाम खोजें (काशी, गया, हरिद्वार...)"
                : "Search Puja, Deity, or Holy Dham (Kashi, Gaya...)",
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
  Widget _buildDeityFilterStrip(Color cardBg, Color cardBorder, bool isDark, [bool isHi = false]) {
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
                    (isHi ? item["labelHi"] : item["label"]) ?? item["label"]!,
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

  String _cleanPujaTitle(String? raw, [bool isHi = false]) {
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

  String _formatTithiSriMandir(dynamic rawDate, bool isHi) {
    if (rawDate == null) return isHi ? '१ दिसम्बर, मंगलवार, मार्गशीर्ष कृष्ण अष्टमी' : '1 Dec, Tuesday, Margashirsha Ashtami';
    final s = rawDate.toString();
    if (s.length >= 10) {
      final date = s.substring(0, 10);
      return isHi ? '$date, पावन वेदोक्त तिथि व शुभ मुहूर्त' : '$date, Auspicious Vedic Tithi';
    }
    return s;
  }

  /// High Quality Sri Mandir-Grade Puja Card
  Widget _buildSriMandirPujaCard(
    BuildContext context,
    Puja p,
    Color cardBg,
    Color cardBorder,
    Color textPrimary,
    Color textSecondary,
    bool isDark, [
    bool isHi = false,
  ]) {
    final place = (p.place != null && p.place.toString().isNotEmpty)
        ? VedicHindiLocalizer.localizePlace(p.place.toString(), isHi)
        : (isHi ? "पावन तीर्थ धाम" : "Holy Pilgrimage Teerth");
    final tithi = _formatTithiSriMandir(p.startDatetime, isHi);
    final festivalTag = _getFestivalTag(p, isHi);
    final subTag = _getSubTag(p, isHi);

    return Container(
      decoration: BoxDecoration(
        color: cardBg,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: isDark ? cardBorder : const Color(0xFFE2E8F0), width: 1),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: isDark ? 0.25 : 0.05),
            blurRadius: 12,
            offset: const Offset(0, 4),
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
            // 1. Cover Image with Sri Mandir Badges
            Stack(
              children: [
                SizedBox(
                  height: 185,
                  width: double.infinity,
                  child: _buildPujaCoverImage(p),
                ),
                // Gradient Scrim for Contrast
                Positioned.fill(
                  child: DecoratedBox(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [
                          Colors.black.withValues(alpha: 0.35),
                          Colors.transparent,
                          Colors.black.withValues(alpha: 0.75),
                        ],
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        stops: const [0.0, 0.45, 1.0],
                      ),
                    ),
                  ),
                ),

                // Top Center YouTube Live Badge (Exact Sri Mandir Pill)
                Positioned(
                  top: 10,
                  left: 0,
                  right: 0,
                  child: Center(
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: const Color(0xFFE11D48),
                        borderRadius: BorderRadius.circular(14),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.3),
                            blurRadius: 6,
                          ),
                        ],
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.play_circle_fill, size: 13, color: Colors.white),
                          const SizedBox(width: 4),
                          Text(
                            isHi ? "लाइव स्ट्रीम | पूजा के दिन YouTube पर" : "Live Stream | On YouTube",
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 10,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),

                // Bottom Center Gold Festival Medallion Badge
                Positioned(
                  bottom: 8,
                  left: 0,
                  right: 0,
                  child: Center(
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 5),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFF450A0A), Color(0xFF7F1D1D), Color(0xFF450A0A)],
                        ),
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: const Color(0xFFF59E0B), width: 1.5),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.4),
                            blurRadius: 8,
                          ),
                        ],
                      ),
                      child: Text(
                        festivalTag,
                        style: const TextStyle(
                          color: Color(0xFFFDE68A),
                          fontSize: 11,
                          fontWeight: FontWeight.w900,
                          letterSpacing: 0.2,
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),

            // 2. Card Content Below Image
            Padding(
              padding: const EdgeInsets.all(14.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Pink/Crimson Category Tag
                  Text(
                    subTag,
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: Color(0xFFBE185D),
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 6),

                  // High-Impact Bold 2-Line Title
                  Text(
                    _cleanPujaTitle(p.title?.toString(), isHi),
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w900,
                      color: textPrimary,
                      height: 1.3,
                      letterSpacing: -0.2,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 8),

                  // Mandir Location Row
                  Row(
                    children: [
                      const Icon(Icons.temple_hindu_outlined, size: 16, color: Color(0xFFEA580C)),
                      const SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          place,
                          style: TextStyle(
                            fontSize: 12.5,
                            fontWeight: FontWeight.w600,
                            color: isDark ? Colors.white70 : const Color(0xFF475569),
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
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
                          tithi,
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: isDark ? Colors.white70 : const Color(0xFF475569),
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),

                  // Full-Width High-Contrast Emerald Button (Exact Sri Mandir "भाग लें ›")
                  Container(
                    width: double.infinity,
                    height: 44,
                    decoration: BoxDecoration(
                      color: const Color(0xFF0D9488),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    alignment: Alignment.center,
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          isHi ? "भाग लें" : "Participate",
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 15,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                        const SizedBox(width: 6),
                        const Icon(Icons.chevron_right_rounded, size: 18, color: Colors.white),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  /// Resilient multi-tier image loader with spiritual defaults & high-performance caching
  Widget _buildPujaCoverImage(Puja p) {
    final rawUrl = p.coverImage.trim();
    final resolvedUrl = rawUrl.isNotEmpty ? MiscApi.imageUrl(rawUrl) : '';

    if (resolvedUrl.isNotEmpty) {
      return CachedNetworkImage(
        imageUrl: resolvedUrl,
        fit: BoxFit.cover,
        placeholder: (context, url) => Container(
          color: const Color(0xFFFEF3C7),
          child: const Center(
            child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFFD97706)),
          ),
        ),
        errorWidget: (context, url, error) => _buildDivineFallbackImage(p),
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

