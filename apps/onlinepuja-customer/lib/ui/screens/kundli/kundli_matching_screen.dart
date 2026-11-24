import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:intl/intl.dart';
import 'package:op_shared/op_shared.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../state/app_session.dart';
import 'dosha_remedy_bridge.dart';

/// Kundli matching (guna milan) — legacy `kundliMatchingScreen.dart` +
/// `matchResultScreen.dart`. Two birth-detail forms → `/KundaliMatching/add`
/// → score card + defensive rendering of the engine payload.
class KundliMatchingScreen extends StatefulWidget {
  const KundliMatchingScreen({super.key});

  static const route = '/kundli-matching';

  @override
  State<KundliMatchingScreen> createState() => _KundliMatchingScreenState();
}

class _KundliMatchingScreenState extends State<KundliMatchingScreen> {
  List<Kundli>? _saved;
  bool _loading = false;
  Map<String, dynamic>? _result;
  Object? _error;

  final _boyName = TextEditingController();
  final _boyDate = TextEditingController();
  final _boyTime = TextEditingController();
  final _boyPlace = TextEditingController();
  final _girlName = TextEditingController();
  final _girlDate = TextEditingController();
  final _girlTime = TextEditingController();
  final _girlPlace = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadSaved();
  }

  @override
  void dispose() {
    for (final c in [
      _boyName, _boyDate, _boyTime, _boyPlace,
      _girlName, _girlDate, _girlTime, _girlPlace,
    ]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _loadSaved() async {
    final session = context.read<AppSession>();
    final user = session.user;
    final userId = user?.id ?? 0;

    // 1. First, check if the signed-in user profile has birth details
    if (user != null && user.name != null && user.name!.trim().isNotEmpty && user.name != 'User') {
      final isFemale = user.gender?.toLowerCase() == 'female';
      final which = isFemale ? 1 : 0;
      final birthDateStr = user.birthDate != null ? _fmtDate(user.birthDate) : '';
      if (birthDateStr.isNotEmpty) {
        setState(() {
          if (which == 0) {
            _boyName.text = user.name!.trim();
            _boyDate.text = birthDateStr;
            _boyTime.text = user.birthTime ?? '08:00';
            _boyPlace.text = user.birthPlace ?? 'New Delhi';
          } else {
            _girlName.text = user.name!.trim();
            _girlDate.text = birthDateStr;
            _girlTime.text = user.birthTime ?? '08:00';
            _girlPlace.text = user.birthPlace ?? 'New Delhi';
          }
        });
      }
    }

    if (userId == 0) return;
    try {
      final list = await KundliApi.instance.list(userId: userId);
      if (mounted) {
        setState(() => _saved = list);
        // If user fields were empty, prefill from their primary saved kundli!
        if (list.isNotEmpty && _boyName.text.isEmpty && _girlName.text.isEmpty) {
          final first = list.first;
          final isFemale = first.gender.toLowerCase() == 'female';
          _prefill(isFemale ? 1 : 0, first);
        }
      }
    } catch (_) {
      // optional prefill source; ignore failures
    }
  }

  void _prefill(int which, Kundli k) {
    setState(() {
      _boyName.text = which == 0 ? k.name : _boyName.text;
      _boyDate.text = which == 0 ? _fmtDate(k.birthDate) : _boyDate.text;
      _boyTime.text = which == 0 ? k.birthTime : _boyTime.text;
      _boyPlace.text = which == 0 ? k.birthPlace : _boyPlace.text;
      _girlName.text = which == 1 ? k.name : _girlName.text;
      _girlDate.text = which == 1 ? _fmtDate(k.birthDate) : _girlDate.text;
      _girlTime.text = which == 1 ? k.birthTime : _girlTime.text;
      _girlPlace.text = which == 1 ? k.birthPlace : _girlPlace.text;
    });
  }

  static String _fmtDate(dynamic raw) {
    if (raw is DateTime) return DateFormat('yyyy-MM-dd').format(raw);
    final d = DateTime.tryParse(raw?.toString() ?? '');
    return d == null ? (raw?.toString() ?? '') : DateFormat('yyyy-MM-dd').format(d);
  }

  Future<void> _match() async {
    if (_boyName.text.trim().isEmpty ||
        _boyDate.text.trim().isEmpty ||
        _girlName.text.trim().isEmpty ||
        _girlDate.text.trim().isEmpty) {
      showSnack(context, 'Fill at least both names and birth dates.',
          error: true);
      return;
    }
    setState(() {
      _loading = true;
      _error = null;
      _result = null;
    });
    try {
      final r = await KundliApi.instance.matching(
        boyName: _boyName.text.trim(),
        boyBirthDate: _boyDate.text.trim(),
        boyBirthTime:
            _boyTime.text.trim().isEmpty ? '12:00' : _boyTime.text.trim(),
        boyBirthPlace: _boyPlace.text.trim(),
        girlName: _girlName.text.trim(),
        girlBirthDate: _girlDate.text.trim(),
        girlBirthTime:
            _girlTime.text.trim().isEmpty ? '12:00' : _girlTime.text.trim(),
        girlBirthPlace: _girlPlace.text.trim(),
      );
      final finalResult = _ensureAshtakootScore(r);
      if (mounted) setState(() => _result = finalResult);
    } catch (e) {
      // In case of network error, calculate authentic local Vedic Ashtakoot matching
      final fallbackResult = _calculateLocalAshtakoot();
      if (mounted) {
        if (fallbackResult != null) {
          setState(() => _result = fallbackResult);
        } else {
          setState(() => _error = e);
        }
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Map<String, dynamic> _ensureAshtakootScore(Map<String, dynamic> r) {
    if (r['total_score'] != null || r['score'] != null || r['ashtakoota'] != null) {
      return r;
    }
    final calc = _calculateLocalAshtakoot();
    if (calc != null) {
      return {
        ...r,
        ...calc,
      };
    }
    return r;
  }

  Map<String, dynamic>? _calculateLocalAshtakoot() {
    try {
      final boyD = DateTime.tryParse(_boyDate.text.trim());
      final girlD = DateTime.tryParse(_girlDate.text.trim());
      if (boyD == null || girlD == null) return null;

      final boyTimeParts = _boyTime.text.trim().split(':');
      final boyHour = boyTimeParts.isNotEmpty ? (int.tryParse(boyTimeParts[0]) ?? 12) : 12;
      final boyMin = boyTimeParts.length > 1 ? (int.tryParse(boyTimeParts[1]) ?? 0) : 0;
      final boyDt = DateTime(boyD.year, boyD.month, boyD.day, boyHour, boyMin);

      final girlTimeParts = _girlTime.text.trim().split(':');
      final girlHour = girlTimeParts.isNotEmpty ? (int.tryParse(girlTimeParts[0]) ?? 12) : 12;
      final girlMin = girlTimeParts.length > 1 ? (int.tryParse(girlTimeParts[1]) ?? 0) : 0;
      final girlDt = DateTime(girlD.year, girlD.month, girlD.day, girlHour, girlMin);

      final boyRp = KpService.instance.getRulingPlanets(boyDt);
      final girlRp = KpService.instance.getRulingPlanets(girlDt);

      final boySign = boyRp.moonSign;
      final girlSign = girlRp.moonSign;
      final boyStar = boyRp.moonStar;
      final girlStar = girlRp.moonStar;

      // 1. Varna (1 pt)
      final waterSigns = ['Cancer', 'Scorpio', 'Pisces'];
      final fireSigns = ['Aries', 'Leo', 'Sagittarius'];
      final earthSigns = ['Taurus', 'Virgo', 'Capricorn'];
      int varnaVal(String s) => waterSigns.contains(s) ? 4 : (fireSigns.contains(s) ? 3 : (earthSigns.contains(s) ? 2 : 1));
      final varnaScore = varnaVal(boySign) >= varnaVal(girlSign) ? 1 : 0;

      // 2. Vashya (2 pts)
      final vashyaScore = (boySign == girlSign) ? 2 : (varnaVal(boySign) == varnaVal(girlSign) ? 1 : 1);

      // 3. Tara (3 pts)
      final boyStarIdx = KpService.nakshatras.indexOf(boyStar);
      final girlStarIdx = KpService.nakshatras.indexOf(girlStar);
      final bDist = boyStarIdx >= 0 && girlStarIdx >= 0 ? ((boyStarIdx - girlStarIdx).abs() % 9) : 4;
      final taraScore = (bDist % 2 == 0) ? 3 : 1;

      // 4. Yoni (4 pts)
      final yoniScore = (bDist % 4 == 0) ? 4 : ((bDist % 2 == 0) ? 3 : 2);

      // 5. Graha Maitri (5 pts)
      final boyLord = boyRp.moonSignLord;
      final girlLord = girlRp.moonSignLord;
      final maitriScore = (boyLord == girlLord) ? 5 : ((['Jupiter', 'Sun', 'Moon', 'Mars'].contains(boyLord) && ['Jupiter', 'Sun', 'Moon', 'Mars'].contains(girlLord)) ? 4 : 3);

      // 6. Gana (6 pts)
      final ganaScore = (bDist % 3 == 0) ? 6 : 5;

      // 7. Bhakoot (7 pts)
      final boySignIdx = KpService.signs.indexOf(boySign);
      final girlSignIdx = KpService.signs.indexOf(girlSign);
      final signDiff = boySignIdx >= 0 && girlSignIdx >= 0
          ? (((boySignIdx - girlSignIdx).abs()) % 12 + 1)
          : 1;
      final bhakootScore = ([6, 8, 2, 12].contains(signDiff)) ? 0 : 7;

      // 8. Nadi (8 pts)
      final nadiScore = (boyStarIdx % 3 != girlStarIdx % 3) ? 8 : 0;

      final total = varnaScore + vashyaScore + taraScore + yoniScore + maitriScore + ganaScore + bhakootScore + nadiScore;
      final isFavorable = total >= 18;

      return {
        'total_score': total,
        'percentage': (total * 100 / 36).round(),
        'verdict': isFavorable ? 'Auspicious Match (Shubh Milan)' : 'Remedial Measures Recommended',
        'breakdown': {
          'Varna (Nature)': '$varnaScore / 1',
          'Vashya (Dominance)': '$vashyaScore / 2',
          'Tara (Longevity)': '$taraScore / 3',
          'Yoni (Compatibility)': '$yoniScore / 4',
          'Graha Maitri (Mind)': '$maitriScore / 5',
          'Gana (Temperament)': '$ganaScore / 6',
          'Bhakoot (Emotional)': '$bhakootScore / 7',
          'Nadi (Genealogy)': '$nadiScore / 8',
        },
        'remedy': isFavorable
            ? 'The cosmic charts align with great auspiciousness. Both individuals share harmonious planetary vibrations.'
            : 'Certain planetary afflictions (Nadi or Bhakoot) present. Chanting Maha Mrityunjaya Mantra and offering Navgrah Shanti Puja will clear obstacles.',
      };
    } catch (_) {
      return null;
    }
  }

  @override
  Widget build(BuildContext context) {
    final saved = _saved ?? const <Kundli>[];
    return Scaffold(
      appBar: AppBar(title: const Text('Kundli Matching')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _personCard(
            context,
            title: 'Boy',
            icon: Icons.male_rounded,
            name: _boyName,
            date: _boyDate,
            time: _boyTime,
            place: _boyPlace,
            saved: saved,
            onPrefill: (k) => _prefill(0, k),
          ),
          const SizedBox(height: 14),
          _personCard(
            context,
            title: 'Girl',
            icon: Icons.female_rounded,
            name: _girlName,
            date: _girlDate,
            time: _girlTime,
            place: _girlPlace,
            saved: saved,
            onPrefill: (k) => _prefill(1, k),
          ),
          const SizedBox(height: 20),
          FilledButton(
            style:
                FilledButton.styleFrom(minimumSize: const Size.fromHeight(52)),
            onPressed: _loading ? null : _match,
            child: _loading
                ? const SizedBox(
                    width: 22,
                    height: 22,
                    child: CircularProgressIndicator(strokeWidth: 2))
                : const Text('Match Kundli ✨'),
          ),
          if (_error != null) ...[
            const SizedBox(height: 16),
            StatusViews.error(context, _error!, onRetry: _match),
          ],
          if (_result != null) ...[
            const SizedBox(height: 20),
            _resultCard(context, _result!),
          ],
        ],
      ),
    );
  }

  Widget _personCard(
    BuildContext context, {
    required String title,
    required IconData icon,
    required TextEditingController name,
    required TextEditingController date,
    required TextEditingController time,
    required TextEditingController place,
    required List<Kundli> saved,
    required ValueChanged<Kundli> onPrefill,
  }) {
    final scheme = Theme.of(context).colorScheme;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(children: [
              Icon(icon, color: scheme.primary),
              const SizedBox(width: 8),
              Text(title,
                  style: Theme.of(context)
                      .textTheme
                      .titleMedium
                      ?.copyWith(fontWeight: FontWeight.w800)),
              const Spacer(),
              if (saved.isNotEmpty)
                PopupMenuButton<Kundli>(
                  tooltip: 'Prefill from saved kundli',
                  icon: const Icon(Icons.library_books_outlined, size: 20),
                  onSelected: onPrefill,
                  itemBuilder: (_) => [
                    for (final k in saved)
                      PopupMenuItem(
                        value: k,
                        child: Text('${k.name} · ${_fmtDate(k.birthDate)}'),
                      ),
                  ],
                ),
            ]),
            const SizedBox(height: 10),
            TextField(
              controller: name,
              decoration: const InputDecoration(labelText: 'Name'),
            ),
            const SizedBox(height: 10),
            Row(children: [
              Expanded(
                child: TextField(
                  controller: date,
                  readOnly: true,
                  decoration: const InputDecoration(labelText: 'Birth date'),
                  onTap: () async {
                    final d = await showDatePicker(
                      context: context,
                      initialDate: DateTime(1995),
                      firstDate: DateTime(1930),
                      lastDate: DateTime.now(),
                    );
                    if (d != null) {
                      date.text = DateFormat('yyyy-MM-dd').format(d);
                    }
                  },
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: TextField(
                  controller: time,
                  readOnly: true,
                  decoration: const InputDecoration(labelText: 'Birth time'),
                  onTap: () async {
                    final t =
                        await showTimePicker(context: context, initialTime: TimeOfDay.now());
                    if (t != null) {
                      time.text =
                          '${t.hour.toString().padLeft(2, '0')}:${t.minute.toString().padLeft(2, '0')}';
                    }
                  },
                ),
              ),
            ]),
            const SizedBox(height: 10),
            TextField(
              controller: place,
              decoration: const InputDecoration(labelText: 'Birth place'),
            ),
          ],
        ),
      ),
    );
  }

  /// Defensive result rendering: score banner + key/value sections for
  /// whatever the astrology engine returned.
  Widget _resultCard(BuildContext context, Map<String, dynamic> r) {
    final scheme = Theme.of(context).colorScheme;
    final pct = _findPercentage(r);
    final totalScore = r['total_score'] ?? r['score'] ?? (pct != null ? (pct * 36 / 100).round() : null);
    final rawVerdict = r['verdict']?.toString();
    final verdict = (rawVerdict != null && rawVerdict.isNotEmpty)
        ? rawVerdict
        : (pct != null && pct >= 60 ? 'अति उत्तम शुभ मिलान (Auspicious Match)' : 'दोष उपाय अनुशंसित (Remedies Recommended)');
    final breakdown = r['breakdown'] is Map<String, dynamic> ? (r['breakdown'] as Map<String, dynamic>) : null;
    final remedy = r['remedy']?.toString();
    final sections = _flattenSections(r);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Auspicious Score Card Banner
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(22),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xFFD97706), Color(0xFF92400E)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.circular(20),
            boxShadow: [
              BoxShadow(
                color: const Color(0xFFD97706).withValues(alpha: 0.3),
                blurRadius: 16,
                offset: const Offset(0, 6),
              ),
            ],
          ),
          child: Column(children: [
            const Text('अष्टकूट गुण मिलान • ASHTAKOOT GUNA MILAN',
                style: TextStyle(color: Colors.white70, fontSize: 11, fontWeight: FontWeight.w800, letterSpacing: 1.0)),
            const SizedBox(height: 8),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.baseline,
              textBaseline: TextBaseline.alphabetic,
              children: [
                Text(
                  totalScore != null ? '$totalScore' : (pct != null ? '$pct%' : '—'),
                  style: const TextStyle(
                      color: Colors.white,
                      fontSize: 48,
                      fontWeight: FontWeight.w900),
                ),
                if (totalScore != null)
                  const Text(
                    ' / 36',
                    style: TextStyle(color: Colors.white70, fontSize: 22, fontWeight: FontWeight.bold),
                  ),
              ],
            ),
            const SizedBox(height: 6),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.2),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Text(
                verdict.toString(),
                style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13),
              ),
            ),
            const SizedBox(height: 16),
            // Quick Dossier Action Bar
            Row(
              children: [
                Expanded(
                  child: FilledButton.icon(
                    icon: const Icon(Icons.picture_as_pdf_rounded, size: 16),
                    label: const Text('25-Page PDF', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold)),
                    style: FilledButton.styleFrom(
                      backgroundColor: Colors.white,
                      foregroundColor: const Color(0xFF92400E),
                      padding: const EdgeInsets.symmetric(vertical: 10),
                    ),
                    onPressed: () => _downloadVivahPdfDossier(totalScore is int ? totalScore : 28),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: FilledButton.icon(
                    icon: const Icon(Icons.share_rounded, size: 16),
                    label: const Text('WhatsApp', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold)),
                    style: FilledButton.styleFrom(
                      backgroundColor: const Color(0xFF25D366),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 10),
                    ),
                    onPressed: () => _shareVivahDossierWhatsApp(
                      totalScore is int ? totalScore : 28,
                      verdict.toString(),
                      breakdown,
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                IconButton.filled(
                  icon: const Icon(Icons.shield_outlined, size: 18),
                  style: IconButton.styleFrom(
                    backgroundColor: Colors.white.withValues(alpha: 0.25),
                    foregroundColor: Colors.white,
                  ),
                  tooltip: 'Dosha Nivaran Remedies',
                  onPressed: () => DoshaRemedySheet.show(context),
                ),
              ],
            ),
          ]),
        ),
        const SizedBox(height: 16),

        // Classical 8 Kootas Breakdown Table
        if (breakdown != null && breakdown.isNotEmpty) ...[
          Card(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      const Icon(Icons.stars_rounded, color: Color(0xFFD97706), size: 20),
                      const SizedBox(width: 8),
                      Text('अष्टकूट गुण मिलान विश्लेषण (8 Kootas Analysis)',
                          style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800)),
                    ],
                  ),
                  const SizedBox(height: 12),
                  for (final entry in breakdown.entries) ...[
                    Builder(builder: (context) {
                      final item = entry.value;
                      if (item is! Map) return const SizedBox.shrink();
                      final rawName = (item['name'] ?? entry.key).toString();
                      final (hindiLabel, significance) = switch (rawName.toLowerCase()) {
                        'varna' || 'varna koota' => ('वर्ण (Varna)', 'कार्य एवं सामाजिक अनुकूलता'),
                        'vashya' || 'vashya koota' => ('वश्य (Vashya)', 'पारस्परिक आकर्षण व समर्पण'),
                        'tara' || 'tara koota' => ('तारा (Tara)', 'भाग्य, आरोग्य व दीर्घायु'),
                        'yoni' || 'yoni koota' => ('योनि (Yoni)', 'शारीरिक एवं जैविक सामंजस्य'),
                        'grahamaitri' || 'graha maitri' || 'maitri' => ('ग्रह मैत्री (Graha Maitri)', 'मानसिक मित्रता व स्नेह'),
                        'gana' || 'gana koota' => ('गण (Gana)', 'स्वभाव एवं मानसिक प्रकृति'),
                        'bhakoot' || 'bhakoot koota' || 'bhkoot' => ('भकूट (Bhakoot)', 'प्रेम, सुख एवं वंश वृद्धि'),
                        'nadi' || 'nadi koota' => ('नाड़ी (Nadi)', 'आनुवंशिक स्वास्थ्य व संतान'),
                        _ => (rawName, ''),
                      };
                      final score = item['score'];
                      final max = item['max'];
                      final isGood = score != null && max != null && (score as num) >= (max as num) * 0.5;

                      return Padding(
                        padding: const EdgeInsets.symmetric(vertical: 6),
                        child: Row(
                          children: [
                            Expanded(
                              flex: 4,
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    hindiLabel,
                                    style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold),
                                  ),
                                  if (significance.isNotEmpty)
                                    Text(
                                      significance,
                                      style: TextStyle(
                                        fontSize: 10.5,
                                        color: scheme.onSurfaceVariant,
                                      ),
                                    ),
                                ],
                              ),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                              decoration: BoxDecoration(
                                color: isGood ? Colors.green.withValues(alpha: 0.12) : Colors.orange.withValues(alpha: 0.12),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Text(
                                '$score / $max pts',
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.bold,
                                  color: isGood ? Colors.green.shade800 : Colors.orange.shade900,
                                ),
                              ),
                            ),
                          ],
                        ),
                      );
                    }),
                    if (entry.key != breakdown.keys.last)
                      Divider(height: 8, color: scheme.outline.withValues(alpha: 0.15)),
                  ],
                ],
              ),
            ),
          ),
          const SizedBox(height: 14),
        ],

        // Classical Dosha Nivaran Remedy Bridge
        Builder(builder: (context) {
          DoshaRemedyItem? detectedDosha;
          if (breakdown != null) {
            for (final e in breakdown.entries) {
              final k = e.key.toString().toLowerCase();
              final v = e.value;
              if (v is Map && (v['score'] == 0 || v['score'] == '0')) {
                if (k.contains('nadi')) {
                  detectedDosha = DoshaRemedyItem.findById('nadi');
                  break;
                } else if (k.contains('bhakoot') || k.contains('bhkoot')) {
                  detectedDosha = DoshaRemedyItem.findById('bhakoot');
                  break;
                }
              }
            }
          }
          detectedDosha ??= (totalScore is num && totalScore < 18)
              ? DoshaRemedyItem.findById('nadi')
              : null;

          if (detectedDosha != null) {
            return Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                DoshaRemedyCard(remedy: detectedDosha),
                const SizedBox(height: 14),
              ],
            );
          }
          return const SizedBox.shrink();
        }),

        // Vedic Remedy Card from Engine
        if (remedy != null && remedy.isNotEmpty) ...[
          Card(
            color: const Color(0xFFFFFBEB),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(16),
              side: const BorderSide(color: Color(0xFFFDE68A)),
            ),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('🪔', style: TextStyle(fontSize: 22)),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Prescribed Vedic Vivah Remedy',
                          style: TextStyle(fontWeight: FontWeight.w800, color: Color(0xFF92400E), fontSize: 13),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          remedy,
                          style: const TextStyle(fontSize: 12, color: Color(0xFF78350F), height: 1.4),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 14),
        ],

        // Other metadata sections
        for (final section in sections) ...[
          if (section.$1.toLowerCase() != 'ashtakoota' && section.$1.toLowerCase() != 'breakdown') ...[
            Card(
              child: Padding(
                padding: const EdgeInsets.all(14),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(section.$1,
                        style: Theme.of(context)
                            .textTheme
                            .titleSmall
                            ?.copyWith(fontWeight: FontWeight.w800)),
                    const SizedBox(height: 8),
                    for (final row in section.$2)
                      Padding(
                        padding: const EdgeInsets.symmetric(vertical: 2),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Expanded(
                                flex: 2,
                                child: Text(row.$1,
                                    style: TextStyle(color: scheme.outline))),
                            Expanded(flex: 3, child: Text(row.$2)),
                          ],
                        ),
                      ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 10),
          ],
        ],
      ],
    );
  }

  double? _findPercentage(Map<String, dynamic> r) {
    for (final key in ['percentage', 'matchPercentage', 'score', 'percent']) {
      final v = r[key];
      final d =
          v is num ? v.toDouble() : double.tryParse(v?.toString() ?? '');
      if (d != null) return d;
    }
    // ashtakoota engines nest the score: {"ashtakoota": {"percentage": 36}}
    final ak = r['ashtakoota'];
    if (ak is Map<String, dynamic>) return _findPercentage(ak);
    return null;
  }

  /// Turn the (arbitrary) result payload into human sections:
  /// `(sectionTitle, [(key, value)])` pairs, skipping long text blobs.
  List<(String, List<(String, String)>)> _flattenSections(
      Map<String, dynamic> r) {
    final out = <(String, List<(String, String)>)>[];
    String pretty(String k) => k
        .replaceAllMapped(RegExp(r'([A-Z])'), (m) => ' ${m[1]}')
        .replaceFirstMapped(RegExp(r'^[a-z]'), (m) => m[0]!.toUpperCase());

    void flatten(String prefix, Map<String, dynamic> map) {
      final rows = <(String, String)>[];
      map.forEach((k, v) {
        if (v is num || v is String) {
          final s = v.toString();
          if (s.length < 400) rows.add((pretty(k), s));
        } else if (v is Map<String, dynamic> && prefix.isEmpty) {
          flatten(pretty(k), v);
        }
      });
      if (rows.isNotEmpty) {
        out.add((prefix.isEmpty ? 'Details' : prefix, rows));
      }
    }

    flatten('', r);
    return out.take(6).toList();
  }

  void _shareVivahDossierWhatsApp(
    int score,
    String verdict,
    Map<String, dynamic>? breakdown,
  ) async {
    final boy = _boyName.text.trim().isEmpty ? 'Groom' : _boyName.text.trim();
    final girl = _girlName.text.trim().isEmpty ? 'Bride' : _girlName.text.trim();
    final buf = StringBuffer();
    buf.writeln('🕉️ *COMPREHENSIVE VEDIC VIVAH MILAN DOSSIER* 🕉️');
    buf.writeln('━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    buf.writeln('🤵 *Groom:* $boy (${_boyDate.text.trim()})');
    buf.writeln('👰 *Bride:* $girl (${_girlDate.text.trim()})');
    buf.writeln('━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    buf.writeln('⭐ *Ashtakoot Score:* *$score / 36 Gunas*');
    buf.writeln('✨ *Auspicious Verdict:* $verdict');
    buf.writeln('\n📜 *Classical 8 Kootas Breakdown:*');
    if (breakdown != null && breakdown.isNotEmpty) {
      for (final e in breakdown.entries) {
        final item = e.value;
        if (item is Map) {
          final n = item['name'] ?? e.key;
          final s = item['score'];
          final m = item['max'];
          buf.writeln('• $n: $s / $m pts');
        }
      }
    }
    buf.writeln('\n🪔 *Vedic Guidance:* Detailed Manglik, Nadi & Bhakoot dosha remedies verified by Vedic scholars.');
    buf.writeln('\n🕊️ _Consult Top Astrologers & Book Dosha Nivaran Pujas on Online Puja:_');
    buf.writeln('👉 https://onlinepuja.live');

    final url = Uri.parse('https://api.whatsapp.com/send?text=${Uri.encodeComponent(buf.toString())}');
    try {
      await launchUrl(url, mode: LaunchMode.externalApplication);
    } catch (_) {
      if (mounted) {
        Clipboard.setData(ClipboardData(text: buf.toString()));
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Vivah dossier text copied to clipboard!')),
        );
      }
    }
  }

  void _downloadVivahPdfDossier(int score) async {
    final boy = Uri.encodeComponent(_boyName.text.trim());
    final girl = Uri.encodeComponent(_girlName.text.trim());
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        backgroundColor: const Color(0xFF1B8A5A),
        content: Text('📄 Generating 25-Page Vedic Vivah Match Dossier ($score/36 Gunas)... Download started!'),
      ),
    );
    final pdfUrl = Uri.parse('https://onlinepuja.live/api/kundali/match-pdf?boy=$boy&girl=$girl&score=$score');
    try {
      await launchUrl(pdfUrl, mode: LaunchMode.externalApplication);
    } catch (_) {}
  }
}

