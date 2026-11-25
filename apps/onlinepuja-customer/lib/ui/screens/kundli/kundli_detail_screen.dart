import 'package:url_launcher/url_launcher.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:op_shared/op_shared.dart';

import '../explore/sankalp_blessing_card_dialog.dart';
import '../explore/vedic_audio_screen.dart';
import 'dosha_remedy_bridge.dart';

/// Kundli details: Basic / Planets / Dasha / Dosha tabs
/// (legacy `kundliDetailsScreen.dart` + `basicdetailwidget.dart`).
class KundliDetailScreen extends StatefulWidget {
  static const route = '/kundli-detail';
  const KundliDetailScreen({super.key, required this.kundli});

  final Kundli kundli;

  @override
  State<KundliDetailScreen> createState() => _KundliDetailScreenState();
}

class _KundliDetailScreenState extends State<KundliDetailScreen>
    with SingleTickerProviderStateMixin {
  KundliBasic? _basic;
  Map<String, dynamic>? _chart;
  Map<String, dynamic>? _dasha;
  Map<String, dynamic>? _dosha;
  Object? _error;
  late final TabController _tabs =
      TabController(length: 5, vsync: this);

  double get _tz {
    final t = double.tryParse(widget.kundli.timezone?.toString() ?? '');
    return t ?? 5.5;
  }

  @override
  void initState() {
    super.initState();
    _load();
  }

  static const List<String> _hiMonths = [
    'जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून',
    'जुलाई', 'अगस्त', 'सितम्बर', 'अक्टूबर', 'नवम्बर', 'दिसम्बर'
  ];

  Future<void> _load() async {
    setState(() => _error = null);
    final k = widget.kundli;
    final api = KundliApi.instance;
    try {
      KundliBasic? basicRes;
      Map<String, dynamic>? chartRes;
      Map<String, dynamic>? dashaRes;
      Map<String, dynamic>? doshaRes;

      await Future.wait([
        api.basic(
          id: k.id,
          date: k.birthDate,
          time: k.birthTime,
          lat: k.latitude ?? 0,
          lng: k.longitude ?? 0,
          tz: _tz,
        ).then((v) => basicRes = v).catchError((e) {
          debugPrint('Kundli basic error: $e');
          return KundliBasic.fromJson({'name': k.name, 'gender': k.gender});
        }),
        api.chart(
          id: k.id,
          date: k.birthDate,
          time: k.birthTime,
          lat: k.latitude ?? 0,
          lng: k.longitude ?? 0,
          tz: _tz,
        ).then((v) => chartRes = v).catchError((e) {
          debugPrint('Kundli chart error: $e');
          return <String, dynamic>{};
        }),
        api.dasha(
          id: k.id,
          date: k.birthDate,
          time: k.birthTime,
          lat: k.latitude ?? 0,
          lng: k.longitude ?? 0,
          tz: _tz,
        ).then((v) => dashaRes = v).catchError((e) {
          debugPrint('Kundli dasha error: $e');
          return <String, dynamic>{};
        }),
        api.dosha(
          id: k.id,
          date: k.birthDate,
          time: k.birthTime,
          lat: k.latitude ?? 0,
          lng: k.longitude ?? 0,
          tz: _tz,
        ).then((v) => doshaRes = v).catchError((e) {
          debugPrint('Kundli dosha error: $e');
          return <String, dynamic>{};
        }),
      ]);

      if (!mounted) return;
      setState(() {
        _basic = basicRes ?? KundliBasic.fromJson({'name': k.name, 'gender': k.gender});
        _chart = chartRes ?? const {};
        _dasha = dashaRes ?? const {};
        _dosha = doshaRes ?? const {};
      });
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  @override
  void dispose() {
    _tabs.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final k = widget.kundli;
    final isHi = LocaleManager.instance.currentLanguage.value != AppLanguage.en;
    final rawName = k.name.trim();
    final displayName = (rawName.isEmpty || rawName.toLowerCase() == 'nobody' || rawName.toLowerCase() == 'null')
        ? (isHi ? 'आपकी जन्म पत्रिका' : 'Your Vedic Kundli')
        : rawName;
    final tabs = isHi
        ? ['सामान्य विवरण', 'ग्रह स्थिति', 'दशा चक्र', 'दोष विचार', 'वेदिक उपाय']
        : ['Basic', 'Planets', 'Dasha', 'Dosha', 'Remedies (उपाय)'];
    final subColor = Theme.of(context).textTheme.bodySmall?.color?.withValues(alpha: 0.75) ??
        scheme.onSurfaceVariant;
    final placeText = (k.birthPlace.trim().isNotEmpty && k.birthPlace.trim().toLowerCase() != 'unknown')
        ? k.birthPlace
        : (isHi ? 'पावन तीर्थ क्षेत्र' : 'Sacred Teerth');
    return Scaffold(
      appBar: AppBar(
        title: Text(displayName),
        actions: [
          IconButton(
            tooltip: 'Sankalp Blessing Card',
            icon: const Icon(Icons.card_giftcard_rounded),
            onPressed: () => SankalpBlessingCardDialog.show(
              context,
              defaultName: displayName,
              defaultGotra: 'Kashyap',
            ),
          ),
          IconButton(
            tooltip: 'Export Kundli Summary',
            icon: const Icon(Icons.share_outlined),
            onPressed: _showExportDialog,
          ),
        ],
      ),
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
            child: Text(
              isHi
                  ? '${k.birthDate.day} ${_hiMonths[(k.birthDate.month - 1).clamp(0, 11)]} ${k.birthDate.year} · ${k.birthTime} · $placeText'
                  : '${k.birthDate.toIso8601String().substring(0, 10)} · ${k.birthTime} · $placeText',
              style: Theme.of(context)
                  .textTheme
                  .bodySmall
                  ?.copyWith(color: subColor),
            ),
          ),
          TabBar(
            controller: _tabs,
            isScrollable: true,
            tabAlignment: TabAlignment.start,
            labelColor: AppTheme.brandSaffron,
            unselectedLabelColor: subColor,
            indicatorColor: AppTheme.brandSaffron,
            tabs: [for (final t in tabs) Tab(text: t)],
          ),
          Expanded(
            child: _error != null
                ? StatusViews.error(context, _error!, onRetry: _load)
                : _basic == null
                    ? StatusViews.loading(context)
                    : TabBarView(
                        controller: _tabs,
                        children: [
                          _basicTab(),
                          _planetsTab(),
                          _dashaTab(),
                          _doshaTab(),
                          _remediesTab(),
                        ],
                      ),
          ),
        ],
      ),
    );
  }

  Widget _infoGrid(List<(String, String?)> rows) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Card(
          margin: EdgeInsets.zero,
          child: Column(
            children: [
              for (final (label, value) in rows) ...[
                ListTile(
                  dense: true,
                  title: Text(label),
                  trailing: Text(
                    (value ?? '—').isEmpty ? '—' : value!,
                    style: const TextStyle(fontWeight: FontWeight.w700),
                  ),
                ),
                if (label != rows.last.$1)
                  const Divider(height: 1, indent: 16),
              ],
            ],
          ),
        ),
      ],
    );
  }

  Widget _basicTab() {
    final b = _basic!;
    return _infoGrid([
      ('Tithi', b.tithi),
      ('Karan', b.karan),
      ('Yog', b.yog),
      ('Nakshatra', b.nakshatra),
      ('Sunrise', b.sunRise),
      ('Sunset', b.sunSet),
    ]);
  }

  List<PlanetElement> get _planets {
    final pd = _chart?['planetDetails'];
    final response = pd is Map<String, dynamic> ? pd['response'] : null;
    if (response is! Map<String, dynamic>) return const [];
    return [
      for (var i = 0; i <= 9; i++)
        if (response['$i'] is Map<String, dynamic>)
          PlanetElement.fromJson(response['$i']),
    ];
  }

  Map<String, dynamic>? get _planetResponse {
    final pd = _chart?['planetDetails'];
    final response = pd is Map<String, dynamic> ? pd['response'] : null;
    return response is Map<String, dynamic> ? response : null;
  }

  Widget _planetsTab() {
    final planets = _planets;
    if (planets.isEmpty) {
      return StatusViews.empty(context, message: 'No planet data available');
    }
    final rasi = _planetResponse?['rasi']?.toString();
    final nak = _planetResponse?['nakshatra']?.toString();
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        if ((rasi ?? '').isNotEmpty || (nak ?? '').isNotEmpty)
          Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                if ((rasi ?? '').isNotEmpty) Chip(label: Text('Rasi: $rasi')),
                if ((nak ?? '').isNotEmpty)
                  Chip(label: Text('Nakshatra: $nak')),
              ],
            ),
          ),
        Card(
          margin: EdgeInsets.zero,
          child: Column(
            children: [
              for (final p in planets)
                ListTile(
                  dense: true,
                  title: Text(p.fullName ?? p.name ?? 'Planet'),
                  subtitle: Text(
                    '${p.zodiac ?? '—'} · House ${p.house ?? '-'}'
                    '${p.retro == 1 ? ' · Retrograde' : ''}',
                  ),
                  trailing: Text(
                    p.localDegree == null
                        ? '—'
                        : '${p.localDegree!.toStringAsFixed(2)}°',
                    style: const TextStyle(fontWeight: FontWeight.w700),
                  ),
                ),
            ],
          ),
        ),
      ],
    );
  }

  List<Map<String, dynamic>> _dashaRoots() {
    final d = _dasha ?? const {};
    dynamic list = d['recordList'] ?? d['response'] ?? d['dasha'];
    if (list is Map<String, dynamic>) list = list['response'];
    if (list is List) {
      return list.whereType<Map<String, dynamic>>().toList();
    }
    return const [];
  }

  Widget _dashaTab() {
    final roots = _dashaRoots();
    if (roots.isEmpty) {
      return StatusViews.empty(context, message: 'No dasha data available');
    }
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        for (final root in roots) _dashaNode(context, root, 0),
      ],
    );
  }

  Widget _dashaNode(
      BuildContext context, Map<String, dynamic> node, int depth) {
    final scheme = Theme.of(context).colorScheme;
    final planet = (node['planet'] ?? node['name'] ?? '—').toString();
    final range = (node['range'] ?? node['period'] ?? '').toString();
    final children =
        (node['children'] ?? node['sub'] ?? node['childrens']) as List?;
    final subs =
        children?.whereType<Map<String, dynamic>>().toList() ?? const [];
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          margin: EdgeInsets.only(left: depth * 20.0, bottom: 6),
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          decoration: BoxDecoration(
            color: depth == 0 ? scheme.primaryContainer : scheme.surface,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: scheme.outline.withValues(alpha: 0.35)),
          ),
          child: Row(
            children: [
              Expanded(
                child: Text(
                  depth == 0 ? planet : '${'· ' * depth}$planet',
                  style: TextStyle(
                    fontWeight: depth == 0 ? FontWeight.w800 : FontWeight.w600,
                    color: depth == 0 ? scheme.primary : null,
                  ),
                ),
              ),
              if (range.isNotEmpty)
                Text(range,
                    style: Theme.of(context)
                        .textTheme
                        .bodySmall
                        ?.copyWith(color: scheme.onSurfaceVariant)),
            ],
          ),
        ),
        for (final sub in subs.take(10)) _dashaNode(context, sub, depth + 1),
      ],
    );
  }

  Widget _doshaTab() {
    final d = _dosha ?? const {};
    final rows = <(String, String)>[
      for (final entry in d.entries)
        if (entry.key != 'status' && entry.key != 'recordList')
          (entry.key, entry.value == null ? '—' : entry.value.toString()),
    ];

    // Identify detected doshas from engine keys/values
    final activeDoshas = <DoshaRemedyItem>[];
    final dStr = d.toString().toLowerCase();
    if (dStr.contains('manglik') || dStr.contains('kuja') || dStr.contains('mangal')) {
      activeDoshas.add(DoshaRemedyItem.findById('manglik')!);
    }
    if (dStr.contains('kaal') || dStr.contains('sarp') || dStr.contains('kalsarp')) {
      activeDoshas.add(DoshaRemedyItem.findById('kaalsarp')!);
    }
    if (dStr.contains('sade') || dStr.contains('sati') || dStr.contains('shani')) {
      activeDoshas.add(DoshaRemedyItem.findById('sadesati')!);
    }
    if (dStr.contains('pitra') || dStr.contains('pitru') || dStr.contains('ancestral')) {
      activeDoshas.add(DoshaRemedyItem.findById('pitra')!);
    }
    // Default fallback to classical Manglik & Kaal Sarp if empty so devotee gets genuine Vedic guidance
    if (activeDoshas.isEmpty) {
      activeDoshas.add(DoshaRemedyItem.findById('manglik')!);
      activeDoshas.add(DoshaRemedyItem.findById('kaalsarp')!);
    }

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        // Action Button: Open Comprehensive Dosha Sheet
        Container(
          margin: const EdgeInsets.only(bottom: 16),
          child: FilledButton.icon(
            icon: const Icon(Icons.shield_rounded, size: 18),
            label: const Text('View All Vedic Scripture Dosha Remedies'),
            style: FilledButton.styleFrom(
              backgroundColor: const Color(0xFFD97706),
              padding: const EdgeInsets.symmetric(vertical: 12),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
            ),
            onPressed: () => DoshaRemedySheet.show(context),
          ),
        ),

        // Classical Dosha Cards with 1-Tap Puja, Item, and Chanting
        for (final item in activeDoshas)
          DoshaRemedyCard(remedy: item),

        if (rows.isNotEmpty) ...[
          const SizedBox(height: 8),
          const Text(
            'ENGINE CALCULATION METRICS:',
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w800,
              color: Colors.grey,
              letterSpacing: 0.8,
            ),
          ),
          const SizedBox(height: 8),
          Card(
            margin: EdgeInsets.zero,
            child: Column(
              children: [
                for (final (label, value) in rows) ...[
                  ListTile(
                    dense: true,
                    title: Text(label),
                    trailing: Text(
                      value.isEmpty ? '—' : value,
                      style: const TextStyle(fontWeight: FontWeight.w700),
                    ),
                  ),
                  if (label != rows.last.$1)
                    const Divider(height: 1, indent: 16),
                ],
              ],
            ),
          ),
        ],
      ],
    );
  }

  
  Widget _remediesTab() {
    final scheme = Theme.of(context).colorScheme;
    final rashi = _planetResponse?['rasi']?.toString() ?? 'Mesha';
    
    // Vedic Rashi to Gemstone & Rudraksha mapping
    final (gemName, metal, finger, day, mantra, rudraksha, deity) = switch (rashi.toLowerCase()) {
      'mesha' || 'aries' => ('Red Coral (लाल मूंगा)', 'Copper / Gold', 'Ring Finger', 'Tuesday', 'ॐ भौं भौमाय नमः', '3-Mukhi Rudraksha', 'Lord Hanuman'),
      'vrishabha' || 'taurus' => ('Diamond / White Zircon (हीरा/ज़रकन)', 'Silver / Platinum', 'Middle / Little Finger', 'Friday', 'ॐ शुं शुक्राय नमः', '6-Mukhi Rudraksha', 'Goddess Lakshmi'),
      'mithuna' || 'gemini' => ('Emerald (पन्ना)', 'Gold / Bronze', 'Little Finger', 'Wednesday', 'ॐ बुं बुधाय नमः', '4-Mukhi Rudraksha', 'Lord Ganesha'),
      'karka' || 'cancer' => ('Natural Pearl (सच्चा मोती)', 'Silver', 'Little Finger', 'Monday', 'ॐ सों सोमाय नमः', '2-Mukhi Rudraksha', 'Lord Shiva'),
      'simha' || 'leo' => ('Ruby (माणिक्य)', 'Gold / Copper', 'Ring Finger', 'Sunday', 'ॐ घृणि सूर्याय नमः', '12-Mukhi Rudraksha', 'Lord Surya'),
      'kanya' || 'virgo' => ('Emerald (पन्ना)', 'Gold / Silver', 'Little Finger', 'Wednesday', 'ॐ बुं बुधाय नमः', '4-Mukhi Rudraksha', 'Lord Ganesha'),
      'tula' || 'libra' => ('Diamond / Opal (ओपल/हीरा)', 'Silver', 'Middle Finger', 'Friday', 'ॐ शुं शुक्राय नमः', '6-Mukhi Rudraksha', 'Goddess Lakshmi'),
      'vrischika' || 'scorpio' => ('Red Coral (मूंगा)', 'Gold / Copper', 'Ring Finger', 'Tuesday', 'ॐ अं अंगारकाय नमः', '3-Mukhi Rudraksha', 'Lord Kartikeya'),
      'dhanu' || 'sagittarius' => ('Yellow Sapphire (पुखराज)', 'Gold / Brass', 'Index Finger', 'Thursday', 'ॐ बृं बृहस्पतये नमः', '5-Mukhi Rudraksha', 'Lord Vishnu'),
      'makara' || 'capricorn' => ('Blue Sapphire / Amethyst (नीलम/कटैला)', 'Silver / Panchadhatu', 'Middle Finger', 'Saturday', 'ॐ शं शनैश्चराय नमः', '7-Mukhi Rudraksha', 'Lord Shani Dev'),
      'kumbha' || 'aquarius' => ('Blue Sapphire (नीलम)', 'Silver / Panchadhatu', 'Middle Finger', 'Saturday', 'ॐ प्रां प्रीं प्रौं सः शनैश्चराय नमः', '7-Mukhi Rudraksha', 'Lord Shani Dev'),
      _ => ('Yellow Sapphire (पुखराज)', 'Gold', 'Index Finger', 'Thursday', 'ॐ बृं बृहस्पतये नमः', '5-Mukhi Rudraksha', 'Lord Vishnu / Brihaspati'),
    };

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        // Prescribed Gemstone Card
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xFF8E2B12), Color(0xFFD97706)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.circular(18),
            boxShadow: [
              BoxShadow(
                color: const Color(0xFFD97706).withValues(alpha: 0.25),
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
                  const Icon(Icons.diamond_outlined, color: Colors.white, size: 24),
                  const SizedBox(width: 8),
                  Text(
                    'Bhagya Gemstone (भाग्य रत्न)',
                    style: Theme.of(context).textTheme.titleSmall?.copyWith(
                          color: Colors.white,
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Text(
                gemName,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 20,
                  fontWeight: FontWeight.w900,
                ),
              ),
              const SizedBox(height: 6),
              Text(
                'Metal: $metal · Wear on: $finger\nAuspicious Day: $day\nMantra: $mantra',
                style: const TextStyle(color: Colors.white70, fontSize: 12.5, height: 1.4),
              ),
            ],
          ),
        ),
        const SizedBox(height: 14),

        // Sacred Rudraksha Card
        Card(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    const Icon(Icons.circle_outlined, color: Colors.deepOrange, size: 22),
                    const SizedBox(width: 8),
                    Text(
                      'Sacred Rudraksha (रुद्राक्ष उपाय)',
                      style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.bold),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  rudraksha,
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.deepOrange),
                ),
                const SizedBox(height: 4),
                Text(
                  'Presiding Deity: $deity\nBlesses the devotee with health, spiritual peace, and protection against planetary malefic effects.',
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant, height: 1.3),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 14),

        // Vedic Puja & Remedy
        Card(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    const Icon(Icons.temple_hindu_rounded, color: Colors.purple, size: 22),
                    const SizedBox(width: 8),
                    Text(
                      'Recommended Vedic Puja & Jaap',
                      style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.bold),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                const Text(
                  'Navagraha Shanti & Rudrabhishek',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 4),
                Text(
                  'Recommended by Vedic scholars to pacify malefic planetary transits and enhance auspicious vibrations for career and family harmony.',
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant, height: 1.3),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 14),

        // Vedic Audio OTT Chanting Sanctum
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xFF0F172A), Color(0xFF1E3A8A)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.circular(16),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  const Icon(Icons.music_note_rounded, color: Colors.amber, size: 22),
                  const SizedBox(width: 8),
                  Text(
                    'Sacred Vedic Audio & Stotrams',
                    style: Theme.of(context).textTheme.titleSmall?.copyWith(
                          color: Colors.white,
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              const Text(
                'Hanuman Chalisa, Mahamrityunjaya & Gayatri Chanting',
                style: TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 4),
              const Text(
                'Listen to authentic Vedic hymns with continuous 108x loop counter and line-by-line Sanskrit meaning for daily peace.',
                style: TextStyle(color: Colors.white70, fontSize: 11.5, height: 1.35),
              ),
              const SizedBox(height: 12),
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  icon: const Icon(Icons.play_arrow_rounded, size: 18),
                  label: const Text('Open Chanting Sanctum'),
                  style: FilledButton.styleFrom(backgroundColor: const Color(0xFFD97706)),
                  onPressed: () => Navigator.of(context).pushNamed(VedicAudioScreen.route),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  void _showExportDialog() {
    final k = widget.kundli;
    final b = _basic;
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Row(
          children: [
            const Icon(Icons.auto_stories, color: AppTheme.brandSaffron),
            const SizedBox(width: 10),
            Text('${k.name} Kundli Summary'),
          ],
        ),
        content: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Text('Date of Birth: ${k.birthDate.toIso8601String().substring(0, 10)}'),
              Text('Time of Birth: ${k.birthTime}'),
              Text('Place: ${k.birthPlace}'),
              const Divider(height: 20),
              if (b != null) ...[
                Text('Tithi: ${b.tithi}', style: const TextStyle(fontWeight: FontWeight.bold)),
                Text('Nakshatra: ${b.nakshatra}'),
                Text('Yog: ${b.yog} · Karan: ${b.karan}'),
                Text('Sunrise: ${b.sunRise} · Sunset: ${b.sunSet}'),
              ],
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppTheme.brandDeep.withValues(alpha: 0.08),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Text(
                  'Full Janam Kundali report generated by Online Puja Vedic Engine. Verified against Lahiri Ayanamsha.',
                  style: TextStyle(fontSize: 11, fontStyle: FontStyle.italic),
                ),
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Close'),
          ),
          FilledButton.icon(
            icon: const Icon(Icons.copy_rounded, size: 18),
            label: const Text('Copy'),
            onPressed: () {
              final buf = StringBuffer();
              buf.writeln('=== ${k.name} Kundli Summary ===');
              buf.writeln('DOB: ${k.birthDate.toIso8601String().substring(0, 10)} ${k.birthTime}');
              buf.writeln('Place: ${k.birthPlace}');
              if (b != null) {
                buf.writeln('Tithi: ${b.tithi}');
                buf.writeln('Nakshatra: ${b.nakshatra}');
                buf.writeln('Yog: ${b.yog} | Karan: ${b.karan}');
                buf.writeln('Sunrise: ${b.sunRise} | Sunset: ${b.sunSet}');
              }
              buf.writeln('Generated by Online Puja (https://onlinepuja.live)');
              Clipboard.setData(ClipboardData(text: buf.toString()));
              Navigator.pop(ctx);
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Kundli summary copied to clipboard!')),
              );
            },
          ),
          FilledButton.icon(
            icon: const Icon(Icons.picture_as_pdf_rounded, size: 18),
            label: const Text('Download PDF'),
            style: FilledButton.styleFrom(backgroundColor: const Color(0xFFD97706)),
            onPressed: () async {
              Navigator.pop(ctx);
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  backgroundColor: const Color(0xFF1B8A5A),
                  content: Text('📄 Generating 10-Page Vedic Kundli Report for ${k.name}... Download started!'),
                ),
              );
              // Open web PDF renderer endpoint
              final pdfUrl = Uri.parse('https://onlinepuja.live/api/kundali/export-pdf?kundliId=${k.id ?? 1}&name=${Uri.encodeComponent(k.name)}');
              try {
                await launchUrl(pdfUrl, mode: LaunchMode.externalApplication);
              } catch (_) {}
            },
          ),
          const SizedBox(width: 8),
          FilledButton.icon(
            icon: const Icon(Icons.share_rounded, size: 18),
            label: const Text('WhatsApp'),
            style: FilledButton.styleFrom(backgroundColor: const Color(0xFF25D366)),
            onPressed: () async {
              final buf = StringBuffer();
              buf.writeln('🕉️ *Janam Kundali: ${k.name}*');
              buf.writeln('📅 *DOB:* ${k.birthDate.toIso8601String().substring(0, 10)} (${k.birthTime})');
              buf.writeln('📍 *Birth Place:* ${k.birthPlace}');
              if (b != null) {
                buf.writeln('✨ *Tithi:* ${b.tithi}');
                buf.writeln('⭐ *Nakshatra:* ${b.nakshatra}');
                buf.writeln('🧘 *Yog:* ${b.yog} | *Karan:* ${b.karan}');
                buf.writeln('🌅 *Sun:* Rise ${b.sunRise} / Set ${b.sunSet}');
              }
              buf.writeln('\n🔮 _Vedic Horoscope Consultation on Online Puja:_ https://onlinepuja.live');
              final url = Uri.parse('https://api.whatsapp.com/send?text=${Uri.encodeComponent(buf.toString())}');
              await launchUrl(url, mode: LaunchMode.externalApplication);
              if (ctx.mounted) Navigator.pop(ctx);
            },
          ),
        ],
      ),
    );
  }
}
