import 'package:flutter/material.dart';
import 'package:op_shared/op_shared.dart';
import 'package:provider/provider.dart';
import '../../../state/app_session.dart';

/// Full Daily Horoscope experience matching Vedic legacy specs:
/// - Horizontal Zodiac sign selector
/// - 3 Tabs: Today / Weekly / Yearly
/// - Lucky Color & Lucky Number card
/// - Comprehensive life aspects: Physique, Finances, Relationship, Career, Travel, Family
/// - Direct CTA: Chat with Astrologers / Call with Astrologers
class DailyHoroscopeScreen extends StatefulWidget {
  const DailyHoroscopeScreen({super.key});

  static const route = '/horoscope';

  @override
  State<DailyHoroscopeScreen> createState() => _DailyHoroscopeScreenState();
}

class _DailyHoroscopeScreenState extends State<DailyHoroscopeScreen>
    with SingleTickerProviderStateMixin {
  static const _glyphs = [
    '♈', '♉', '♊', '♋', '♌', '♍', '♎', '♏', '♐', '♑', '♒', '♓',
  ];

  late final TabController _tabController;
  List<HoroscopeSign>? _signs;
  Object? _error;
  HoroscopeSign? _activeSign;
  DailyHoroscope? _horoscope;
  bool _loadingDetail = false;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _tabController.addListener(() {
      if (!_tabController.indexIsChanging && _activeSign != null) {
        _loadDetail(_activeSign!);
      }
    });
    _loadSigns();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  String get _currentType {
    switch (_tabController.index) {
      case 1:
        return 'weekly';
      case 2:
        return 'yearly';
      default:
        return 'today';
    }
  }

  static final _defaultVedicSigns = const [
    'Aries', 'Taurus', 'Gemini', 'Cancer', 'Leo', 'Virgo',
    'Libra', 'Scorpio', 'Sagittarius', 'Capricorn', 'Aquarius', 'Pisces'
  ].asMap().entries.map((e) => HoroscopeSign(id: e.key + 1, name: e.value)).toList();

  HoroscopeSign _resolveUserSign(List<HoroscopeSign> signs) {
    try {
      final user = context.read<AppSession>().user;
      if (user?.birthDate != null) {
        final m = user!.birthDate!.month;
        final d = user.birthDate!.day;
        String signName = 'Aries';
        if ((m == 3 && d >= 21) || (m == 4 && d <= 19)) {
          signName = 'Aries';
        } else if ((m == 4 && d >= 20) || (m == 5 && d <= 20)) {
          signName = 'Taurus';
        } else if ((m == 5 && d >= 21) || (m == 6 && d <= 20)) {
          signName = 'Gemini';
        } else if ((m == 6 && d >= 21) || (m == 7 && d <= 22)) {
          signName = 'Cancer';
        } else if ((m == 7 && d >= 23) || (m == 8 && d <= 22)) {
          signName = 'Leo';
        } else if ((m == 8 && d >= 23) || (m == 9 && d <= 22)) {
          signName = 'Virgo';
        } else if ((m == 9 && d >= 23) || (m == 10 && d <= 22)) {
          signName = 'Libra';
        } else if ((m == 10 && d >= 23) || (m == 11 && d <= 21)) {
          signName = 'Scorpio';
        } else if ((m == 11 && d >= 22) || (m == 12 && d <= 21)) {
          signName = 'Sagittarius';
        } else if ((m == 12 && d >= 22) || (m == 1 && d <= 19)) {
          signName = 'Capricorn';
        } else if ((m == 1 && d >= 20) || (m == 2 && d <= 18)) {
          signName = 'Aquarius';
        } else if ((m == 2 && d >= 19) || (m == 3 && d <= 20)) {
          signName = 'Pisces';
        }

        final matched = signs.where((s) => (s.name?.toLowerCase() ?? '') == signName.toLowerCase());
        if (matched.isNotEmpty) {
          return matched.first;
        }
      }
    } catch (_) {}
    return signs.first;
  }

  Future<void> _loadSigns() async {
    setState(() => _error = null);
    try {
      final signs = await HoroscopeApi.instance.signs();
      final effectiveSigns = signs.isNotEmpty ? signs : _defaultVedicSigns;
      final userSign = _resolveUserSign(effectiveSigns);
      if (mounted) {
        setState(() {
          _signs = effectiveSigns;
          _activeSign = userSign;
          _loadDetail(userSign);
        });
      }
    } catch (_) {
      if (mounted) {
        final userSign = _resolveUserSign(_defaultVedicSigns);
        setState(() {
          _signs = _defaultVedicSigns;
          _activeSign = userSign;
          _loadDetail(userSign);
        });
      }
    }
  }

  Future<void> _loadDetail(HoroscopeSign sign) async {
    setState(() {
      _loadingDetail = true;
      _activeSign = sign;
    });
    try {
      final list = await HoroscopeApi.instance.daily(
        signId: sign.id,
        type: _currentType,
      );
      if (mounted) {
        setState(() => _horoscope = list.isNotEmpty ? list.first : null);
      }
    } catch (e) {
      if (mounted) {
        setState(() => _horoscope = null);
      }
    } finally {
      if (mounted) setState(() => _loadingDetail = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Daily Horoscope'),
        actions: [
          IconButton(
            tooltip: 'Share Horoscope',
            icon: const Icon(Icons.share_outlined),
            onPressed: () {
              final sign = _activeSign?.name ?? 'Horoscope';
              final pred = (_horoscope?.predictions ?? '').isNotEmpty
                  ? _horoscope!.predictions!
                  : 'Check your daily Vedic astrological forecast on OnlinePuja.';
              SacredShareSheet.show(
                context,
                title: 'Share Daily Rashifal',
                subtitle: '$sign Horoscope',
                shareText: SocialContentGenerator.formatHoroscopeShare(
                  signName: sign,
                  prediction: pred,
                  luckyNumber: _horoscope?.luckyNumber,
                  luckyColor: _horoscope?.luckyColor,
                ),
                shareUrl: 'https://onlinepuja.live/horoscope',
                category: 'Horoscope',
              );
            },
          ),
        ],
      ),
      bottomNavigationBar: _bottomActionButtons(context, scheme),
      body: _error != null
          ? StatusViews.error(context, _error!, onRetry: _loadSigns)
          : _signs == null
              ? StatusViews.loading(context)
              : Column(
                  children: [
                    _zodiacSignCarousel(context, scheme),
                    _tabsHeader(context, scheme),
                    Expanded(
                      child: _loadingDetail
                          ? const Center(child: CircularProgressIndicator())
                          : _contentView(context, scheme),
                    ),
                  ],
                ),
    );
  }

  Widget _zodiacSignCarousel(BuildContext context, ColorScheme scheme) {
    return Container(
      height: 86,
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: ListView.separated(
        padding: const EdgeInsets.symmetric(horizontal: 16),
        scrollDirection: Axis.horizontal,
        itemCount: _signs!.length,
        separatorBuilder: (_, _) => const SizedBox(width: 14),
        itemBuilder: (context, i) {
          final sign = _signs![i];
          final isSelected = _activeSign?.id == sign.id;
          final glyph = i < _glyphs.length ? _glyphs[i] : '✦';

          return GestureDetector(
            onTap: () => _loadDetail(sign),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 44,
                  height: 44,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: isSelected
                        ? scheme.primary
                        : scheme.surfaceContainerHighest,
                    border: Border.all(
                      color: isSelected ? scheme.primary : scheme.outline.withValues(alpha: 0.3),
                      width: isSelected ? 2 : 1,
                    ),
                  ),
                  child: Center(
                    child: Text(
                      glyph,
                      style: TextStyle(
                        fontSize: 20,
                        color: isSelected ? scheme.onPrimary : scheme.primary,
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  sign.name ?? '',
                  style: Theme.of(context).textTheme.labelSmall?.copyWith(
                        fontWeight: isSelected ? FontWeight.w800 : FontWeight.w500,
                        color: isSelected ? scheme.primary : scheme.onSurface,
                      ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _tabsHeader(BuildContext context, ColorScheme scheme) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      decoration: BoxDecoration(
        color: scheme.surfaceContainerHighest.withValues(alpha: 0.5),
        borderRadius: BorderRadius.circular(12),
      ),
      child: TabBar(
        controller: _tabController,
        indicatorSize: TabBarIndicatorSize.tab,
        dividerColor: Colors.transparent,
        indicator: BoxDecoration(
          borderRadius: BorderRadius.circular(10),
          color: scheme.primary,
        ),
        labelColor: scheme.onPrimary,
        unselectedLabelColor: scheme.onSurfaceVariant,
        tabs: const [
          Tab(text: 'Today'),
          Tab(text: 'Weekly'),
          Tab(text: 'Yearly'),
        ],
      ),
    );
  }

  Widget _contentView(BuildContext context, ColorScheme scheme) {
    final h = _horoscope;
    final now = DateTime.now();
    final todayStr = '${now.year}-${now.month.toString().padLeft(2, '0')}-${now.day.toString().padLeft(2, '0')}';
    final signName = _activeSign?.name ?? 'Zodiac';

    final luckyCol = (h?.luckyColor ?? '').isNotEmpty ? h!.luckyColor! : 'Gold';
    final luckyNum = (h?.luckyNumber ?? '').isNotEmpty ? h!.luckyNumber! : '7';
    final forecast = (h?.predictions ?? '').isNotEmpty
        ? h!.predictions!
        : 'The planetary alignments bring positive clarity and spiritual focus today for $signName. Focus on inner harmony and thoughtful decisions.';

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        // Summary & Lucky Details Card
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: scheme.surfaceContainerHighest.withValues(alpha: 0.4),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: scheme.outline.withValues(alpha: 0.2)),
          ),
          child: Column(
            children: [
              Text(
                h?.date ?? todayStr,
                style: Theme.of(context).textTheme.titleSmall?.copyWith(
                      fontWeight: FontWeight.bold,
                      color: scheme.outline,
                    ),
              ),
              const SizedBox(height: 4),
              Text(
                '${_currentType.toUpperCase()} HOROSCOPE · $signName',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w800,
                      color: scheme.primary,
                    ),
              ),
              const SizedBox(height: 12),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                children: [
                  _infoBadge('Lucky Color', luckyCol, scheme),
                  Container(height: 28, width: 1, color: scheme.outline.withValues(alpha: 0.3)),
                  _infoBadge('Lucky Number', luckyNum, scheme),
                ],
              ),
            ],
          ),
        ),
        const SizedBox(height: 16),

        // Forecast Description
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Icon(Icons.auto_awesome, size: 20, color: scheme.primary),
                    const SizedBox(width: 8),
                    Text(
                      '$signName Horoscope Forecast',
                      style: Theme.of(context).textTheme.titleSmall?.copyWith(
                            fontWeight: FontWeight.w800,
                          ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                Text(
                  forecast,
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        height: 1.5,
                      ),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 16),

        // Life Aspects Breakdown Grid
        Text(
          'Energy & Life Aspects',
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w800,
              ),
        ),
        const SizedBox(height: 10),
        GridView.count(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          crossAxisCount: 2,
          mainAxisSpacing: 10,
          crossAxisSpacing: 10,
          childAspectRatio: 2.2,
          children: [
            _metricCard(Icons.directions_run_rounded, 'Physique', h?.physique ?? 75, scheme),
            _metricCard(Icons.account_balance_wallet_outlined, 'Finances', h?.finances ?? 68, scheme),
            _metricCard(Icons.favorite_outline_rounded, 'Relationship', h?.relationship ?? 82, scheme),
            _metricCard(Icons.work_outline_rounded, 'Career', h?.career ?? 74, scheme),
            _metricCard(Icons.flight_takeoff_rounded, 'Travel', h?.travel ?? 60, scheme),
            _metricCard(Icons.people_outline_rounded, 'Family', h?.family ?? 88, scheme),
          ],
        ),
        const SizedBox(height: 24),
      ],
    );
  }

  Widget _infoBadge(String label, String value, ColorScheme scheme) {
    return Column(
      children: [
        Text(label, style: TextStyle(fontSize: 12, color: scheme.outline)),
        const SizedBox(height: 2),
        Text(value, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
      ],
    );
  }

  Widget _metricCard(IconData icon, String label, int percent, ColorScheme scheme) {
    final clamped = percent.clamp(0, 100);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: scheme.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: scheme.outline.withValues(alpha: 0.25)),
      ),
      child: Row(
        children: [
          Icon(icon, size: 20, color: scheme.primary),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                ),
                const SizedBox(height: 4),
                ClipRRect(
                  borderRadius: BorderRadius.circular(4),
                  child: LinearProgressIndicator(
                    value: clamped / 100.0,
                    minHeight: 5,
                    backgroundColor: scheme.surfaceContainerHighest,
                    color: scheme.primary,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Text(
            '$clamped%',
            style: TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 13,
              color: scheme.primary,
            ),
          ),
        ],
      ),
    );
  }

  Widget _bottomActionButtons(BuildContext context, ColorScheme scheme) {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 10, 16, 16),
      decoration: BoxDecoration(
        color: scheme.surface,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.08),
            blurRadius: 10,
            offset: const Offset(0, -3),
          ),
        ],
      ),
      child: SafeArea(
        top: false,
        child: Row(
          children: [
            Expanded(
              child: FilledButton.icon(
                style: FilledButton.styleFrom(
                  backgroundColor: AppTheme.brandDeep,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 12),
                ),
                icon: const Icon(Icons.chat_bubble_outline, size: 18),
                label: const Text('Chat with Astrologers'),
                onPressed: () {
                  if (Navigator.of(context).canPop()) {
                    Navigator.of(context).pop();
                  } else {
                    Navigator.of(context).pushReplacementNamed('/shell');
                  }
                },
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: FilledButton.icon(
                style: FilledButton.styleFrom(
                  backgroundColor: AppTheme.brandSaffron,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 12),
                ),
                icon: const Icon(Icons.phone_in_talk_outlined, size: 18),
                label: const Text('Call with Astrologers'),
                onPressed: () {
                  if (Navigator.of(context).canPop()) {
                    Navigator.of(context).pop();
                  } else {
                    Navigator.of(context).pushReplacementNamed('/shell');
                  }
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}
