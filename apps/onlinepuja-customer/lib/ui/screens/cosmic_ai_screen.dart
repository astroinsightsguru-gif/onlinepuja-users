import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_tts/flutter_tts.dart';
import 'package:op_shared/op_shared.dart';
import 'package:provider/provider.dart';
import '../../state/app_session.dart';
import '../theme/customer_theme.dart';
import 'call/audio_call_screen.dart';
import 'puja/puja_detail_screen.dart';
import 'puja/puja_list_screen.dart';

/// Online Puja Master Vedic AI — High Quality Intelligent Astrology Chatbot & Live AI Phone Call
/// Integrated with Growth OS AI Engine & Master Brain failover chain.
class OnlinePujaAiScreen extends StatefulWidget {
  const OnlinePujaAiScreen({
    super.key,
    this.initialAstrologerId,
    this.initialAstrologerName,
    this.initialAstrologerSkill,
    this.initialAstrologerRate,
    this.initialAiAstrologerId,
    this.startInCallMode = false,
  });

  static const route = '/onlinepuja-ai';

  final int? initialAstrologerId;
  final String? initialAstrologerName;
  final String? initialAstrologerSkill;
  final double? initialAstrologerRate;
  final int? initialAiAstrologerId;
  final bool startInCallMode;

  @override
  State<OnlinePujaAiScreen> createState() => _OnlinePujaAiScreenState();
}

/// Backward compatibility alias — merged into unified OnlinePuja AI
typedef CosmicAiScreen = OnlinePujaAiScreen;

class _AiTurn {
  _AiTurn(
    this.text, {
    required this.mine,
    this.timestamp,
    this.suggestions,
    this.recommendedPuja,
    this.agentName,
    this.agentAvatar,
    this.agentRole,
    this.isAstrologerTwin = false,
    this.realAstrologerId,
  });
  final String text;
  final bool mine;
  final DateTime? timestamp;
  final List<String>? suggestions;
  final Map<String, dynamic>? recommendedPuja;
  final String? agentName;
  final String? agentAvatar;
  final String? agentRole;
  final bool isAstrologerTwin;
  final int? realAstrologerId;
}

class _OnlinePujaAiScreenState extends State<OnlinePujaAiScreen> {
  final TextEditingController _composer = TextEditingController();
  final ScrollController _scroll = ScrollController();
  final FlutterTts _tts = FlutterTts();
  final List<_AiTurn> _turns = [];
  bool _thinking = false;
  bool _voiceModeEnabled = false;
  bool _inCallMode = false;
  int? _speakingTurnIndex;
  Timer? _callTimer;
  int _callSeconds = 0;

  // Agentic Swarm state
  List<Map<String, dynamic>> _swarmAgents = [];
  Map<String, dynamic>? _selectedAgent;
  bool _loadingAgents = false;
  int _selectedCategoryIndex = 0;

  bool get _isHindi => LocaleManager.instance.currentLanguage.value != AppLanguage.en;

  List<Map<String, dynamic>> get _astrologyCategories => _isHindi
      ? const [
          {'id': 'all', 'label': 'दिव्य मार्गदर्शन', 'icon': '🕉️'},
          {'id': 'kundli', 'label': 'कुंडली व दशा', 'icon': '🪐'},
          {'id': 'career', 'label': 'करियर व धन', 'icon': '💼'},
          {'id': 'marriage', 'label': 'प्रेम व विवाह', 'icon': '❤️'},
          {'id': 'remedy', 'label': 'रत्न व उपाय', 'icon': '💎'},
        ]
      : const [
          {'id': 'all', 'label': 'Divine Guidance', 'icon': '🕉️'},
          {'id': 'kundli', 'label': 'Kundli & Dasha', 'icon': '🪐'},
          {'id': 'career', 'label': 'Career & Wealth', 'icon': '💼'},
          {'id': 'marriage', 'label': 'Love & Marriage', 'icon': '❤️'},
          {'id': 'remedy', 'label': 'Gems & Remedies', 'icon': '💎'},
        ];

  List<Map<String, dynamic>> get _filteredSwarmAgents {
    if (_swarmAgents.isEmpty) return const [];
    if (_selectedCategoryIndex == 0) return _swarmAgents;
    final catId = _astrologyCategories[_selectedCategoryIndex]['id'];
    final list = _swarmAgents.where((agent) {
      final type = (agent['type'] ?? agent['specialty'] ?? '').toString().toLowerCase();
      final name = (agent['name'] ?? '').toString().toLowerCase();
      if (catId == 'kundli') {
        return type.contains('kundli') || type.contains('vedic') || type.contains('dasha') || name.contains('vyas');
      } else if (catId == 'career') {
        return type.contains('career') || type.contains('wealth') || type.contains('anka') || type.contains('numerology');
      } else if (catId == 'marriage') {
        return type.contains('milan') || type.contains('match') || type.contains('marriage') || type.contains('love') || type.contains('tarot');
      } else if (catId == 'remedy') {
        return type.contains('remedy') || type.contains('ratna') || type.contains('gem') || type.contains('puja') || type.contains('rudraksha') || type.contains('palm') || type.contains('rekha');
      }
      return true;
    }).toList();
    return list.isNotEmpty ? list : _swarmAgents;
  }

  List<String> get _activeInitialPrompts => _isHindi
      ? const [
          'आज का ग्रह गोचर मेरी राशि के लिए क्या संकेत दे रहा है?',
          'करियर और व्यापार में सफलता के लिए कौन सी पूजा या मंत्र करें?',
          'मेरी कुंडली की महादशा और शुभ समय के बारे में बताएं',
          'मेरे लिए कौन सा रत्न और रुद्राक्ष सबसे शुभ रहेगा?',
          'विवाह योग और दांपत्य सुख के लिए सटीक उपाय बताएं',
        ]
      : const [
          'What do my planetary transits (Gochar) indicate today?',
          'Which puja or mantra will remove obstacles in my career?',
          'Explain my Moon sign & Nakshatra characteristics',
          'Which sacred gemstone & Rudraksha is auspicious for me?',
          'Guidance on relationship harmony and Kundli Gun Milan',
        ];

  @override
  void initState() {
    super.initState();
    _inCallMode = widget.startInCallMode;
    _voiceModeEnabled = widget.startInCallMode;
    _initTts();

    _addInitialGreetingTurn();
    _loadSwarmAgents();

    if (_inCallMode) {
      _startCallTimer();
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (_turns.isNotEmpty) {
          _speakText(_turns.first.text, turnIndex: 0);
        }
      });
    }
  }

  void _addInitialGreetingTurn() {
    final session = context.read<AppSession>();
    final user = session.user;
    final isHi = _isHindi;
    final userName = (user?.name != null && user!.name!.trim().isNotEmpty && user.name != 'User')
        ? user.name!.trim()
        : null;

    if (widget.initialAstrologerId != null) {
      final twinName = widget.initialAstrologerName ?? (isHi ? 'आचार्य' : 'Acharya');
      final twinSkill = widget.initialAstrologerSkill ??
          (isHi ? 'वैदिक ज्योतिष एवं उपाय' : 'Vedic Astrology & Remedies');
      final msg = userName != null
          ? (isHi
              ? '🕉️ हरि ॐ तत्सत् $userName जी! सादर प्रणाम, मैं $twinName का पर्सनल AI अवतार हूँ ($twinSkill)। आपकी जन्म कुंडली हमारे पास लोड है। आज आप अपने जीवन, करियर या पूजा उपाय के बारे में क्या जानना चाहते हैं?'
              : '🕉️ Hari Om Tatsat, $userName ji! Pranam, I am the Personal AI Twin of $twinName ($twinSkill). Your birth chart is active. How may I guide your career, relationships, or planetary remedies today?')
          : (isHi
              ? '🕉️ हरि ॐ तत्सत्! प्रणाम, मैं $twinName का पर्सनल AI अवतार हूँ ($twinSkill)। आप अपना प्रश्न पूछें, मैं वैदिक गणना अनुसार सटीक मार्गदर्शन और उपाय बताऊंगा।'
              : '🕉️ Hari Om Tatsat! Namaste, I am the Personal AI Twin of $twinName ($twinSkill). Please ask your inquiry and I will guide you with accurate Vedic Jyotish.');
      _turns.add(_AiTurn(
        msg,
        mine: false,
        timestamp: DateTime.now(),
        suggestions: _activeInitialPrompts.take(3).toList(),
        agentName: '$twinName (AI Twin)',
        agentRole: twinSkill,
        isAstrologerTwin: true,
        realAstrologerId: widget.initialAstrologerId,
      ));
    } else {
      final matchedInitial = widget.initialAiAstrologerId != null
          ? _fallbackSwarmAgents.where((a) => a['id'] == widget.initialAiAstrologerId).toList()
          : const <Map<String, dynamic>>[];
      final initName = matchedInitial.isNotEmpty
          ? (isHi
              ? (matchedInitial.first['name_hi'] ?? matchedInitial.first['name']).toString()
              : matchedInitial.first['name'].toString())
          : (isHi ? 'आचार्य वेद व्यास' : 'Acharya Ved Vyas');
      final initSpecialty = matchedInitial.isNotEmpty
          ? (isHi
              ? (matchedInitial.first['specialty_hi'] ?? matchedInitial.first['specialty']).toString()
              : matchedInitial.first['specialty'].toString())
          : (isHi ? 'प्रधान वैदिक ज्योतिषाचार्य' : 'Master Vedic Astrologer & Rishi');

      final greeting = userName != null
          ? (isHi
              ? '🕉️ हरि ॐ तत्सत् $userName जी! सादर प्रणाम, मैं $initName ($initSpecialty) हूँ। आपकी जन्म कुंडली हमारे पास उपलब्ध है। आज आप अपने जीवन, करियर, विवाह या ग्रह शांति के बारे में क्या मार्गदर्शन चाहते हैं?'
              : '🕉️ Hari Om Tatsat, $userName ji! Pranam, I am $initName ($initSpecialty). Your birth chart is loaded and active. How may the planetary energies guide your life today?')
          : (isHi
              ? '🕉️ हरि ॐ तत्सत्! सादर प्रणाम, मैं $initName ($initSpecialty) हूँ। कृपया अपना प्रश्न पूछें, मैं ग्रहों की गणना करके आपको सटीक वैदिक मार्गदर्शन और पूजा उपाय बताऊंगा।'
              : '🕉️ Hari Om Tatsat! Pranam, I am $initName ($initSpecialty). Ask your inquiry, and I will guide you with sacred Vedic Jyotish and divine remedies.');

      _turns.add(_AiTurn(
        greeting,
        mine: false,
        timestamp: DateTime.now(),
        suggestions: _activeInitialPrompts.take(3).toList(),
        agentName: initName,
        agentRole: initSpecialty,
      ));
    }
  }

  Future<void> _toggleLanguage() async {
    HapticFeedback.selectionClick();
    final nextLang = _isHindi ? AppLanguage.en : AppLanguage.hi;
    await LocaleManager.instance.setLanguage(nextLang);
    if (!mounted) return;
    final nowHi = _isHindi;
    final agentName = _selectedAgent != null
        ? (nowHi
            ? (_selectedAgent!['name_hi'] ?? _selectedAgent!['name'] ?? 'आचार्य वेद व्यास').toString()
            : (_selectedAgent!['name'] ?? 'Acharya Ved Vyas').toString())
        : (nowHi ? 'आचार्य वेद व्यास' : 'Acharya Ved Vyas');
    final agentRole = _selectedAgent != null
        ? (nowHi
            ? (_selectedAgent!['specialty_hi'] ?? _selectedAgent!['specialty'] ?? 'वैदिक ज्योतिषाचार्य').toString()
            : (_selectedAgent!['specialty'] ?? 'Master Vedic Astrologer').toString())
        : (nowHi ? 'वैदिक ज्योतिषाचार्य' : 'Master Vedic Astrologer');

    final switchGreeting = nowHi
        ? '🕉️ हरि ॐ तत्सत्! अब मैं आपसे शुद्ध हिंदी में बात करूँगा। कृपया अपना प्रश्न पूछें।'
        : '🕉️ Hari Om Tatsat! I will now converse with you in English. Please share your question.';

    setState(() {
      _turns.add(_AiTurn(
        switchGreeting,
        mine: false,
        timestamp: DateTime.now(),
        suggestions: _activeInitialPrompts.take(3).toList(),
        agentName: agentName,
        agentRole: agentRole,
      ));
    });
    if (_voiceModeEnabled || _inCallMode) {
      await _speakText(switchGreeting, turnIndex: _turns.length - 1);
    }
    _jump();
  }

  Future<void> _initTts() async {
    try {
      try {
        final engines = await _tts.getEngines;
        if (engines is List && engines.contains('com.google.android.tts')) {
          await _tts.setEngine('com.google.android.tts');
        }
      } catch (_) {}
      try {
        await _tts.awaitSpeakCompletion(true);
      } catch (_) {}

      final targetLang = _isHindi ? 'hi-IN' : 'en-IN';
      try {
        final avail = await _tts.isLanguageAvailable(targetLang);
        if (avail == 1 || avail == true) {
          await _tts.setLanguage(targetLang);
        } else {
          await _tts.setLanguage(_isHindi ? 'hi' : 'en');
        }
      } catch (_) {
        await _tts.setLanguage(targetLang);
      }

      await _tts.setSpeechRate(0.47);
      await _tts.setPitch(0.95);
      await _tts.setVolume(1.0);

      _tts.setCompletionHandler(() {
        if (mounted) {
          setState(() => _speakingTurnIndex = null);
        }
      });
      _tts.setCancelHandler(() {
        if (mounted) {
          setState(() => _speakingTurnIndex = null);
        }
      });
      _tts.setErrorHandler((msg) {
        debugPrint('TTS runtime error: $msg');
        if (mounted) {
          setState(() => _speakingTurnIndex = null);
        }
      });
    } catch (e) {
      debugPrint('TTS init error: $e');
    }
  }

  /// Cleans any markdown tables (|---|), hashes (#), asterisks (**), or raw pipes for UI display
  String _sanitizeMarkdownForDisplay(String raw) {
    final lines = raw.split('\n');
    final List<String> cleaned = [];
    for (final l in lines) {
      final t = l.trim();
      if (t == '---' || t == '***') continue;
      if (RegExp(r'^[\|\-\:\s]+$').hasMatch(t)) continue;
      if (t.startsWith('|') && t.endsWith('|')) {
        final cells = t
            .substring(1, t.length - 1)
            .split('|')
            .map((c) => c.trim())
            .where((c) => c.isNotEmpty)
            .toList();
        if (cells.length >= 2) {
          final firstLower = cells.first.toLowerCase();
          if (firstLower == 'planet' || firstLower == 'ग्रह' || firstLower == 'aspect') {
            continue;
          }
          cleaned.add('• ${cells.first}: ${cells.sublist(1).join(" — ")}');
        }
        continue;
      }
      cleaned.add(l.replaceAll('**', '').replaceAll('*', ''));
    }
    return cleaned.join('\n').trim();
  }

  String _cleanTextForSpeech(String raw) {
    final displayClean = _sanitizeMarkdownForDisplay(raw);
    return displayClean
        .replaceAll('###', '')
        .replaceAll('##', '')
        .replaceAll('#', '')
        .replaceAll('•', '')
        .replaceAll('🕉️', _isHindi ? 'ओम ' : 'Om ')
        .replaceAll('🪔', '')
        .replaceAll('🪐', '')
        .replaceAll('✋', '')
        .replaceAll('✨', '')
        .replaceAll('🔱', '')
        .replaceAll('💍', '')
        .replaceAll('💎', '')
        .replaceAll('🌿', '')
        .replaceAll('📕', '')
        .replaceAll('🏛️', '')
        .replaceAll('🔢', '')
        .replaceAll('🃏', '')
        .replaceAll(RegExp(r'\s+'), ' ')
        .trim();
  }

  Future<void> _speakText(String rawText, {int? turnIndex}) async {
    try {
      await _tts.stop();
      final clean = _cleanTextForSpeech(rawText);
      if (clean.isEmpty) return;

      final hasHindiScript = RegExp(r'[\u0900-\u097F]').hasMatch(clean);
      final useHindiVoice = hasHindiScript || _isHindi;

      bool langSet = false;
      if (useHindiVoice) {
        for (final l in ['hi-IN', 'hi_IN', 'hi']) {
          try {
            final isAvail = await _tts.isLanguageAvailable(l);
            if (isAvail == 1 || isAvail == true) {
              await _tts.setLanguage(l);
              langSet = true;
              break;
            }
          } catch (_) {}
        }
        if (!langSet) {
          try {
            await _tts.setLanguage('hi-IN');
          } catch (_) {}
        }
      } else {
        try {
          await _tts.setLanguage('en-IN');
        } catch (_) {
          try {
            await _tts.setLanguage('en');
          } catch (_) {}
        }
      }

      await _tts.setSpeechRate(useHindiVoice ? 0.46 : 0.48);
      await _tts.setPitch(0.95);
      await _tts.setVolume(1.0);

      if (mounted) {
        setState(() => _speakingTurnIndex = turnIndex);
      }
      await _tts.speak(clean);
    } catch (e) {
      debugPrint('TTS speak error: $e');
    }
  }

  Future<void> _stopSpeaking() async {
    try {
      await _tts.stop();
    } catch (_) {}
    if (mounted) {
      setState(() => _speakingTurnIndex = null);
    }
  }

  void _startCallTimer() {
    _callTimer?.cancel();
    _callTimer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (mounted && _inCallMode) {
        setState(() => _callSeconds++);
      }
    });
  }

  void _startPhoneCall() {
    HapticFeedback.mediumImpact();
    setState(() {
      _inCallMode = true;
      _voiceModeEnabled = true;
      _callSeconds = 0;
    });
    _startCallTimer();
    if (_turns.isNotEmpty) {
      _speakText(_turns.last.text, turnIndex: _turns.length - 1);
    }
  }

  void _endPhoneCall() {
    HapticFeedback.mediumImpact();
    _callTimer?.cancel();
    _stopSpeaking();
    setState(() {
      _inCallMode = false;
    });
  }

  String get _formattedCallDuration {
    final m = (_callSeconds ~/ 60).toString().padLeft(2, '0');
    final s = (_callSeconds % 60).toString().padLeft(2, '0');
    return '$m:$s';
  }

  static const List<Map<String, dynamic>> _fallbackSwarmAgents = [
    {'id': 41, 'name': 'Acharya Ved Vyas', 'name_hi': 'आचार्य वेद व्यास', 'specialty': 'Vedic Kundli & Dasha', 'specialty_hi': 'वैदिक कुंडली व महादशा', 'icon': '🪐', 'charge': 5},
    {'id': 42, 'name': 'Pt. Hasta Rekha Shastri', 'name_hi': 'पं. हस्तरेखा शास्त्री', 'specialty': 'Palmistry & Mounts', 'specialty_hi': 'हस्तरेखा एवं सामुद्रिक शास्त्र', 'icon': '✋', 'charge': 5},
    {'id': 43, 'name': 'Tarot & Prashna Guru', 'name_hi': 'टैरो एवं प्रश्न गुरु', 'specialty': 'Horary & Tarot Cards', 'specialty_hi': 'प्रश्न कुंडली व टैरो कार्ड', 'icon': '🃏', 'charge': 5},
    {'id': 44, 'name': 'Acharya Anka Vidya', 'name_hi': 'आचार्य अंक विद्या', 'specialty': 'Vedic Numerology', 'specialty_hi': 'वैदिक अंक ज्योतिष', 'icon': '🔢', 'charge': 5},
    {'id': 45, 'name': 'Vastu Visharad Shastri', 'name_hi': 'वास्तु विशारद शास्त्री', 'specialty': 'Vastu & Directions', 'specialty_hi': 'वास्तु शास्त्र एवं दिशा दोष', 'icon': '🏛️', 'charge': 5},
    {'id': 46, 'name': 'Shri Kashi Teerth Purohit', 'name_hi': 'श्री काशी तीर्थ पुरोहित', 'specialty': 'Hawan & Temple Pujas', 'specialty_hi': 'रुद्राभिषेक, हवन व दोष निवारण', 'icon': '🪔', 'charge': 5},
    {'id': 47, 'name': 'Daivajna Milan Acharya', 'name_hi': 'दैवज्ञ मिलान आचार्य', 'specialty': 'Matchmaking & Vivah', 'specialty_hi': 'कुंडली मिलान एवं विवाह योग', 'icon': '💍', 'charge': 5},
    {'id': 48, 'name': 'Lal Kitab Guru', 'name_hi': 'लाल किताब गुरु', 'specialty': 'Miraculous Upay', 'specialty_hi': 'लाल किताब के अचूक उपाय', 'icon': '📕', 'charge': 5},
    {'id': 49, 'name': 'Ratna Vidya Acharya', 'name_hi': 'रत्न विद्या आचार्य', 'specialty': 'Gemstones & Rudraksha', 'specialty_hi': 'रत्न एवं रुद्राक्ष विशेषज्ञ', 'icon': '💎', 'charge': 5},
    {'id': 50, 'name': 'Ayur-Jyotish Rishi', 'name_hi': 'आयुर्वेद ज्योतिष ऋषि', 'specialty': 'Health & Healing Mantras', 'specialty_hi': 'स्वास्थ्य ज्योतिष एवं महामृत्युंजय मंत्र', 'icon': '🌿', 'charge': 5},
  ];

  Future<void> _loadSwarmAgents() async {
    final Map<String, dynamic>? initialTwinEntry = widget.initialAstrologerId != null
        ? {
            'id': widget.initialAstrologerId,
            'real_astrologer_id': widget.initialAstrologerId,
            'is_twin': true,
            'name': '${widget.initialAstrologerName ?? "Acharya"} (AI Twin)',
            'specialty': widget.initialAstrologerSkill ?? 'Personal Vedic AI Twin',
            'icon': '✨',
            'charge': (widget.initialAstrologerRate ?? 10).round(),
          }
        : null;

    setState(() {
      _loadingAgents = true;
      if (_swarmAgents.isEmpty) {
        _swarmAgents = [
          ?initialTwinEntry,
          ..._fallbackSwarmAgents,
        ];
        if (widget.initialAiAstrologerId != null) {
          final match = _swarmAgents.where((a) => a['id'] == widget.initialAiAstrologerId).toList();
          _selectedAgent = match.isNotEmpty ? match.first : _swarmAgents.first;
        } else {
          _selectedAgent = _swarmAgents.first;
        }
      }
    });
    try {
      final agents = await AiTwinApi.instance.fetchSwarmAgents();
      if (mounted && agents.isNotEmpty) {
        setState(() {
          final mapped = agents.map((a) {
            final type = a['type']?.toString() ?? '';
            String icon = '✨';
            if (type.contains('kundli') || type.contains('vedic')) {
              icon = '🪐';
            } else if (type.contains('palm') || type.contains('rekha')) {
              icon = '✋';
            } else if (type.contains('tarot') || type.contains('prashna')) {
              icon = '🃏';
            } else if (type.contains('numer') || type.contains('anka')) {
              icon = '🔢';
            } else if (type.contains('vastu')) {
              icon = '🏛️';
            } else if (type.contains('puja') || type.contains('pandit')) {
              icon = '🪔';
            } else if (type.contains('match') || type.contains('milan')) {
              icon = '💍';
            } else if (type.contains('lal_kitab')) {
              icon = '📕';
            } else if (type.contains('gem') || type.contains('ratna')) {
              icon = '💎';
            } else if (type.contains('med') || type.contains('swasthya') || type.contains('ayur')) {
              icon = '🌿';
            }

            return {
              ...a,
              'icon': icon,
              'charge': a['chat_charge'] ?? a['charge'] ?? 5,
            };
          }).toList();

          _swarmAgents = [
            ?initialTwinEntry,
            ...mapped,
          ];
          if (widget.initialAiAstrologerId != null) {
            final match = _swarmAgents.where((a) => a['id'] == widget.initialAiAstrologerId).toList();
            _selectedAgent = match.isNotEmpty ? match.first : _swarmAgents.first;
          } else {
            _selectedAgent = _swarmAgents.first;
          }

          // Keep initial greeting in sync with the dynamically loaded selected agent
          if (_turns.length <= 1 && _selectedAgent != null && widget.initialAstrologerId == null) {
            final nowHi = _isHindi;
            final aName = nowHi
                ? (_selectedAgent!['name_hi'] ?? _selectedAgent!['name'] ?? 'आचार्य').toString()
                : (_selectedAgent!['name'] ?? 'Acharya').toString();
            final aRole = nowHi
                ? (_selectedAgent!['specialty_hi'] ?? _selectedAgent!['specialty'] ?? 'वैदिक ज्योतिषाचार्य').toString()
                : (_selectedAgent!['specialty'] ?? 'Vedic Astrologer').toString();
            _turns.clear();
            _turns.add(_AiTurn(
              nowHi
                  ? '🕉️ हरि ॐ तत्सत्! सादर प्रणाम, मैं $aName ($aRole) हूँ। कृपया अपनी जन्म तिथि या प्रश्न बताएं, मैं ग्रहों की गणना करके आपको सटीक वैदिक मार्गदर्शन और पूजा उपाय बताऊंगा।'
                  : '🕉️ Hari Om Tatsat! Pranam, I am $aName ($aRole). Tell me your birth details or question, and I will guide you with Vedic Jyotish and sacred remedies.',
              mine: false,
              timestamp: DateTime.now(),
              suggestions: _activeInitialPrompts.take(3).toList(),
              agentName: aName,
              agentRole: aRole,
              isAstrologerTwin: _selectedAgent!['is_twin'] == true,
              realAstrologerId: _selectedAgent!['real_astrologer_id'] as int?,
            ));
          }
        });
      }
    } catch (e) {
      debugPrint('Failed to load swarm agents: $e');
    } finally {
      if (mounted) setState(() => _loadingAgents = false);
    }
  }

  void _switchAgent(Map<String, dynamic> agent) {
    if (_selectedAgent != null && _selectedAgent!['id'] == agent['id']) return;
    HapticFeedback.selectionClick();
    final nowHi = _isHindi;
    final aName = nowHi
        ? (agent['name_hi'] ?? agent['name'] ?? 'आचार्य').toString()
        : (agent['name'] ?? 'Acharya').toString();
    final aRole = nowHi
        ? (agent['specialty_hi'] ?? agent['specialty'] ?? 'वैदिक ज्योतिषाचार्य').toString()
        : (agent['specialty'] ?? 'Vedic Astrologer').toString();

    setState(() {
      _selectedAgent = agent;
      if (_turns.length <= 1) {
        _turns.clear();
        _turns.add(_AiTurn(
          nowHi
              ? '🕉️ हरि ॐ तत्सत्! सादर प्रणाम, मैं $aName ($aRole) हूँ। कृपया अपनी जन्म तिथि या प्रश्न बताएं, मैं ग्रहों की गणना करके आपको सटीक वैदिक मार्गदर्शन और पूजा उपाय बताऊंगा।'
              : '🕉️ Hari Om Tatsat! Pranam, I am $aName ($aRole). Tell me your birth details or question, and I will guide you with Vedic Jyotish and sacred remedies.',
          mine: false,
          timestamp: DateTime.now(),
          suggestions: _activeInitialPrompts.take(3).toList(),
          agentName: aName,
          agentRole: aRole,
          isAstrologerTwin: agent['is_twin'] == true,
          realAstrologerId: agent['real_astrologer_id'] as int?,
        ));
      } else {
        final switchNote = nowHi
            ? '🕉️ प्रणाम, अब मैं $aName ($aRole) आपकी सेवा में उपस्थित हूँ। कृपया अपना अगला प्रश्न पूछें।'
            : '🕉️ Pranam, I am $aName ($aRole) now assisting you. Please ask your next question.';
        _turns.add(_AiTurn(
          switchNote,
          mine: false,
          timestamp: DateTime.now(),
          suggestions: _activeInitialPrompts.take(3).toList(),
          agentName: aName,
          agentRole: aRole,
          isAstrologerTwin: agent['is_twin'] == true,
          realAstrologerId: agent['real_astrologer_id'] as int?,
        ));
      }
    });
    _jump();
  }

  void _showCallChoiceDialog(int realAstrologerId, String astrologerName) {
    final isHi = _isHindi;
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        decoration: const BoxDecoration(
          color: Color(0xFF17112B),
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(
                width: 36,
                height: 4,
                decoration: BoxDecoration(color: Colors.white24, borderRadius: BorderRadius.circular(2)),
              ),
            ),
            const SizedBox(height: 16),
            Text(
              isHi ? 'कॉल का प्रकार चुनें' : 'Choose Call Mode',
              style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 14),
            ListTile(
              tileColor: Colors.white.withValues(alpha: 0.06),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              leading: const Icon(Icons.headset_mic_rounded, color: Color(0xFF10B981), size: 28),
              title: Text(isHi ? 'AI वॉयस कॉल (तुरंत)' : 'AI Voice Call (Instant)', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
              subtitle: Text(isHi ? '24/7 वैदिक AI अवतार से बात करें' : 'Talk to 24/7 Vedic AI Twin', style: const TextStyle(color: Colors.white60, fontSize: 12)),
              onTap: () {
                Navigator.pop(ctx);
                _startPhoneCall();
              },
            ),
            const SizedBox(height: 10),
            ListTile(
              tileColor: Colors.white.withValues(alpha: 0.06),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              leading: const Icon(Icons.phone_in_talk_rounded, color: Color(0xFFD97706), size: 28),
              title: Text(isHi ? 'वास्तविक ज्योतिषाचार्य से फोन कॉल' : 'Call Real Astrologer Live', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
              subtitle: Text(isHi ? '$astrologerName से 1-on-1 परामर्श' : '1-on-1 consultation with $astrologerName', style: const TextStyle(color: Colors.white60, fontSize: 12)),
              onTap: () {
                Navigator.pop(ctx);
                _stopSpeaking();
                Navigator.of(context).push(
                  MaterialPageRoute(
                    builder: (_) => AudioCallScreen(
                      astrologerId: realAstrologerId,
                      astrologerName: astrologerName,
                    ),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  @override
  void dispose() {
    _callTimer?.cancel();
    _tts.stop();
    _composer.dispose();
    _scroll.dispose();
    super.dispose();
  }

  Future<void> _ask(String text) async {
    final q = text.trim();
    if (q.isEmpty || _thinking) return;

    HapticFeedback.lightImpact();
    _composer.clear();
    setState(() {
      _turns.add(_AiTurn(q, mine: true, timestamp: DateTime.now()));
      _thinking = true;
    });
    _jump();

    try {
      AppSession? session;
      try {
        session = context.read<AppSession>();
      } catch (_) {}

      final user = session?.user;
      final lang = LocaleManager.instance.currentLanguage.value.code;

      final contextMap = <String, dynamic>{
        'topic': _selectedAgent?['specialty'] ?? 'Vedic Astrology',
        'voice_mode': _voiceModeEnabled || _inCallMode,
        if (user != null) ...{
          'userId': user.id,
          if (user.name != null && user.name!.trim().isNotEmpty && user.name != 'User')
            'name': user.name!.trim(),
          if (user.birthDate != null)
            'birthDate': user.birthDate!.toIso8601String().split('T').first,
          if (user.birthTime != null && user.birthTime!.isNotEmpty)
            'birthTime': user.birthTime,
          if (user.birthPlace != null && user.birthPlace!.isNotEmpty)
            'birthPlace': user.birthPlace,
          if (user.gender != null && user.gender!.isNotEmpty)
            'gender': user.gender,
        },
      };

      final isTwinSelected = _selectedAgent != null && _selectedAgent!['is_twin'] == true;
      final activeTwinAstroId = isTwinSelected
          ? (_selectedAgent!['real_astrologer_id'] as int?)
          : null;
      final activeAgentId = !isTwinSelected && _selectedAgent != null
          ? (_selectedAgent!['id'] as int?)
          : null;
      final nowHi = _isHindi;
      final agentName = _selectedAgent != null
          ? (nowHi
              ? (_selectedAgent!['name_hi'] ?? _selectedAgent!['name'] ?? 'आचार्य').toString()
              : (_selectedAgent!['name'] ?? 'Acharya').toString())
          : (nowHi ? 'आचार्य वेद व्यास' : 'Acharya Ved Vyas');
      final agentRole = _selectedAgent != null
          ? (nowHi
              ? (_selectedAgent!['specialty_hi'] ?? _selectedAgent!['specialty'] ?? 'वैदिक ज्योतिषाचार्य').toString()
              : (_selectedAgent!['specialty'] ?? _selectedAgent!['title'] ?? 'Vedic Astrologer').toString())
          : (nowHi ? 'वैदिक ज्योतिषाचार्य' : 'Vedic Astrologer');
      final agentAvatar = _selectedAgent != null ? _selectedAgent!['image']?.toString() : null;

      final response = await AiTwinApi.instance.chat(
        message: q,
        astrologerId: activeTwinAstroId,
        aiAstrologerId: activeAgentId,
        context: contextMap,
        language: lang,
      );

      final replyText = (response['reply'] ?? response['message'] ?? response['answer'] ?? '').toString().trim();
      final cleanAnswer = replyText.isEmpty
          ? '🕉️ Pranam. The planetary energies are aligning. Please ask your divine inquiry again.'
          : replyText;

      Map<String, dynamic>? recommendedPuja;
      if (response['recommended_puja'] is Map) {
        recommendedPuja = Map<String, dynamic>.from(response['recommended_puja'] as Map);
      }

      final isTwin = isTwinSelected || response['is_twin'] == true || response['astrologer_id'] != null;
      final realAstroId = activeTwinAstroId ?? (response['astrologer_id'] is int ? response['astrologer_id'] as int : null);

      if (!mounted) return;
      final newTurnIdx = _turns.length;
      setState(() {
        _thinking = false;
        _turns.add(_AiTurn(
          cleanAnswer,
          mine: false,
          timestamp: DateTime.now(),
          suggestions: _generateDynamicFollowUps(q),
          recommendedPuja: recommendedPuja,
          agentName: agentName,
          agentAvatar: agentAvatar,
          agentRole: agentRole,
          isAstrologerTwin: isTwin,
          realAstrologerId: realAstroId,
        ));
      });
      if (_voiceModeEnabled || _inCallMode) {
        _speakText(cleanAnswer, turnIndex: newTurnIdx);
      }
      _jump();
    } catch (e) {
      if (mounted) {
        setState(() {
          _thinking = false;
          _turns.add(_AiTurn(
            '🕉️ Divine guidance could not be fetched: $e. Please verify connection and try again.',
            mine: false,
            timestamp: DateTime.now(),
          ));
        });
        _jump();
      }
    }
  }

  List<String> _generateDynamicFollowUps(String query) {
    final lq = query.toLowerCase();
    if (_isHindi) {
      if (lq.contains('career') || lq.contains('job') || lq.contains('करियर') || lq.contains('धन')) {
        return [
          'करियर में उन्नति के लिए कौन सा दिन शुभ है?',
          'आर्थिक समृद्धि के लिए अचूक वैदिक उपाय बताएं',
          'लक्ष्मी-कुबेर या रुद्राभिषेक पूजा की विधि बताएं',
        ];
      }
      return [
        'मेरे लिए कौन सा रत्न या रुद्राक्ष शुभ रहेगा?',
        'घर की सुख-शांति के लिए कौन सा हवन कराएं?',
        'आज का सबसे शुभ चौघड़िया मुहूर्त कौन सा है?',
      ];
    }
    if (lq.contains('career') || lq.contains('job') || lq.contains('business') || lq.contains('money')) {
      return [
        'Which day is auspicious for career interviews?',
        'Suggest remedies for financial prosperity',
        'Recommend a Kubera or Lakshmi Puja',
      ];
    }
    if (lq.contains('love') || lq.contains('marriage') || lq.contains('relationship') || lq.contains('match')) {
      return [
        'How does Venus (Shukra) influence my chart?',
        'Remedies for Mangal / Manglik dosha',
        'Suggest puja for marital bliss',
      ];
    }
    return [
      'Which gemstone or Rudraksha suits me?',
      'Suggest a sacred havan for peace & health',
      'Tell me today\'s most auspicious Choghadiya',
    ];
  }

  void _jump() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scroll.hasClients) {
        _scroll.animateTo(
          _scroll.position.maxScrollExtent,
          duration: const Duration(milliseconds: 280),
          curve: Curves.easeOut,
        );
      }
    });
  }

  void _copyToClipboard(String text) {
    Clipboard.setData(ClipboardData(text: _sanitizeMarkdownForDisplay(text)));
    HapticFeedback.mediumImpact();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(_isHindi ? 'वैदिक मार्गदर्शन कॉपी हो गया 🕉️' : 'Vedic advice copied to clipboard 🕉️'),
        duration: const Duration(seconds: 2),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final isHi = _isHindi;
    final activeTitle = _selectedAgent != null
        ? (isHi
            ? (_selectedAgent!['name_hi'] ?? _selectedAgent!['name'] ?? 'आचार्य वेद व्यास').toString()
            : (_selectedAgent!['name'] ?? 'Acharya Ved Vyas').toString())
        : (isHi ? 'आचार्य वेद व्यास' : 'Acharya Ved Vyas');
    final activeSubtitle = _selectedAgent != null
        ? (isHi
            ? (_selectedAgent!['specialty_hi'] ?? _selectedAgent!['specialty'] ?? '24/7 वैदिक ज्योतिषाचार्य').toString()
            : (_selectedAgent!['specialty'] ?? '24/7 Vedic Astrologer').toString())
        : (isHi ? '24/7 प्रधान वैदिक ज्योतिषाचार्य' : '24/7 Master Vedic AI Astrologer');

    return Scaffold(
      backgroundColor: isDark ? const Color(0xFF0E0A1C) : const Color(0xFFFBF8F2),
      // Hide Chat AppBar when in Full-Screen Phone Call Mode for a clean, distraction-free call UI
      appBar: _inCallMode
          ? null
          : AppBar(
              elevation: 0,
              backgroundColor: isDark ? const Color(0xFF130A24) : Colors.white,
              titleSpacing: 0,
              leadingWidth: 42,
              leading: IconButton(
                icon: const Icon(Icons.arrow_back_ios_new_rounded, size: 19),
                onPressed: () => Navigator.of(context).maybePop(),
              ),
              title: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(2),
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      gradient: CustomerTheme.goldGradient,
                      boxShadow: [
                        BoxShadow(
                          color: const Color(0xFFD97706).withValues(alpha: 0.3),
                          blurRadius: 6,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: CircleAvatar(
                      radius: 17,
                      backgroundColor: const Color(0xFF3B1506),
                      child: Text(
                        _selectedAgent != null ? (_selectedAgent!['icon'] ?? 'ॐ').toString() : 'ॐ',
                        style: const TextStyle(color: Color(0xFFFDE68A), fontSize: 16, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ),
                  const SizedBox(width: 9),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          activeTitle,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w800,
                            letterSpacing: 0.15,
                            color: isDark ? Colors.white : const Color(0xFF1E293B),
                          ),
                        ),
                        const SizedBox(height: 1.5),
                        Row(
                          children: [
                            Container(
                              width: 6,
                              height: 6,
                              decoration: BoxDecoration(
                                color: const Color(0xFF10B981),
                                shape: BoxShape.circle,
                                boxShadow: [
                                  BoxShadow(
                                    color: const Color(0xFF10B981).withValues(alpha: 0.6),
                                    blurRadius: 4,
                                    spreadRadius: 1,
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(width: 5),
                            Expanded(
                              child: Text(
                                activeSubtitle,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                  fontSize: 10.5,
                                  fontWeight: FontWeight.w600,
                                  letterSpacing: 0.2,
                                  color: isDark ? const Color(0xFF6EE7B7) : const Color(0xFF047857),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              actions: [
                // 1-Tap Hindi / English Language & Voice Switch
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 3),
                  child: Tooltip(
                    message: isHi ? 'Switch to English' : 'हिंदी में बदलें',
                    child: InkWell(
                      onTap: _toggleLanguage,
                      borderRadius: BorderRadius.circular(16),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: isHi
                              ? const Color(0xFFD97706).withValues(alpha: 0.22)
                              : (isDark ? Colors.white10 : const Color(0xFFF1F5F9)),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(
                            color: isHi ? const Color(0xFFF59E0B) : (isDark ? Colors.white24 : Colors.black12),
                          ),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.translate_rounded, size: 12, color: Color(0xFFF59E0B)),
                            const SizedBox(width: 3),
                            Text(
                              isHi ? 'हि' : 'EN',
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w800,
                                color: isHi
                                    ? const Color(0xFFFDE68A)
                                    : (isDark ? Colors.white : const Color(0xFF1E293B)),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
                // Compact Green AI Call Button
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 11, horizontal: 4),
                  child: Tooltip(
                    message: isHi ? 'AI वॉयस कॉल' : 'AI Voice Call',
                    child: InkWell(
                      onTap: _startPhoneCall,
                      borderRadius: BorderRadius.circular(18),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4.5),
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(
                            colors: [Color(0xFF059669), Color(0xFF10B981)],
                          ),
                          borderRadius: BorderRadius.circular(18),
                          boxShadow: [
                            BoxShadow(
                              color: const Color(0xFF10B981).withValues(alpha: 0.35),
                              blurRadius: 6,
                              offset: const Offset(0, 2),
                            ),
                          ],
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.phone_in_talk_rounded, size: 12.5, color: Colors.white),
                            const SizedBox(width: 4),
                            Text(
                              isHi ? 'कॉल' : 'Call',
                              style: const TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w800,
                                color: Colors.white,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
      body: _inCallMode
          ? _buildActivePhoneCallView(context, isDark)
          : Column(
              children: [
                Expanded(
                  child: Column(
                    children: [
                      if (_loadingAgents)
                        LinearProgressIndicator(
                          minHeight: 2,
                          backgroundColor: Colors.transparent,
                          valueColor: AlwaysStoppedAnimation<Color>(const Color(0xFFD97706).withValues(alpha: 0.6)),
                        ),
                      // Row 1: Curated Category Filter Strip
                      Container(
                        height: 38,
                        margin: const EdgeInsets.only(top: 4, bottom: 2),
                        child: ListView.separated(
                          scrollDirection: Axis.horizontal,
                          padding: const EdgeInsets.symmetric(horizontal: 12),
                          physics: const BouncingScrollPhysics(),
                          itemCount: _astrologyCategories.length,
                          separatorBuilder: (_, _) => const SizedBox(width: 6),
                          itemBuilder: (context, idx) {
                            final cat = _astrologyCategories[idx];
                            final isCatSelected = _selectedCategoryIndex == idx;
                            return InkWell(
                              onTap: () {
                                HapticFeedback.selectionClick();
                                setState(() {
                                  _selectedCategoryIndex = idx;
                                });
                              },
                              borderRadius: BorderRadius.circular(16),
                              child: Container(
                                padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 6),
                                decoration: BoxDecoration(
                                  gradient: isCatSelected ? CustomerTheme.saffronGradient : null,
                                  color: isCatSelected
                                      ? null
                                      : (isDark ? const Color(0xFF1E1635) : Colors.white),
                                  borderRadius: BorderRadius.circular(16),
                                  border: Border.all(
                                    color: isCatSelected
                                        ? Colors.transparent
                                        : (isDark ? Colors.white12 : const Color(0xFFE5E7EB)),
                                    width: 1,
                                  ),
                                  boxShadow: isCatSelected
                                      ? [
                                          BoxShadow(
                                            color: const Color(0xFFD97706).withValues(alpha: 0.35),
                                            blurRadius: 6,
                                            offset: const Offset(0, 2),
                                          ),
                                        ]
                                      : null,
                                ),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Text(cat['icon'] as String, style: const TextStyle(fontSize: 12)),
                                    const SizedBox(width: 5),
                                    Text(
                                      cat['label'] as String,
                                      style: TextStyle(
                                        fontSize: 11.5,
                                        fontWeight: isCatSelected ? FontWeight.w800 : FontWeight.w600,
                                        color: isCatSelected
                                            ? Colors.white
                                            : (isDark ? Colors.white70 : const Color(0xFF374151)),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            );
                          },
                        ),
                      ),

                      // Row 2: Fluid Astrologer Persona Cards (Minified 2-Line Cards)
                      if (_swarmAgents.isNotEmpty)
                        Container(
                          height: 52,
                          margin: const EdgeInsets.only(top: 4, bottom: 6),
                          child: ListView.separated(
                            scrollDirection: Axis.horizontal,
                            padding: const EdgeInsets.symmetric(horizontal: 12),
                            physics: const BouncingScrollPhysics(),
                            itemCount: _filteredSwarmAgents.length,
                            separatorBuilder: (_, _) => const SizedBox(width: 8),
                            itemBuilder: (context, idx) {
                              final agent = _filteredSwarmAgents[idx];
                              final isSelected = _selectedAgent != null && _selectedAgent!['id'] == agent['id'];
                              final icon = agent['icon'] ?? '✨';
                              final name = isHi
                                  ? (agent['name_hi'] ?? agent['name'] ?? 'AI ऋषि')
                                  : (agent['name'] ?? 'AI Rishi');
                              final charge = agent['charge'] ?? agent['chat_charge'] ?? 10;

                              return InkWell(
                                onTap: () => _switchAgent(agent),
                                borderRadius: BorderRadius.circular(14),
                                child: Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                                  decoration: BoxDecoration(
                                    color: isSelected
                                        ? (isDark ? const Color(0xFF281845) : const Color(0xFFFFFBEB))
                                        : (isDark ? const Color(0xFF19122C) : Colors.white),
                                    borderRadius: BorderRadius.circular(14),
                                    border: Border.all(
                                      color: isSelected
                                          ? const Color(0xFFF59E0B)
                                          : (isDark ? Colors.white10 : const Color(0xFFE5E7EB)),
                                      width: isSelected ? 1.4 : 1,
                                    ),
                                    boxShadow: isSelected
                                        ? [
                                            BoxShadow(
                                              color: const Color(0xFFF59E0B).withValues(alpha: 0.25),
                                              blurRadius: 6,
                                              offset: const Offset(0, 2),
                                            ),
                                          ]
                                        : null,
                                  ),
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Row(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          Text(icon.toString(), style: const TextStyle(fontSize: 12)),
                                          const SizedBox(width: 5),
                                          Text(
                                            name.toString(),
                                            style: TextStyle(
                                              fontSize: 11.5,
                                              fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
                                              color: isSelected
                                                  ? (isDark ? const Color(0xFFFDE68A) : const Color(0xFF92400E))
                                                  : (isDark ? Colors.white : const Color(0xFF1E293B)),
                                            ),
                                          ),
                                        ],
                                      ),
                                      const SizedBox(height: 2),
                                      Row(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          Text(
                                            '₹$charge/min',
                                            style: TextStyle(
                                              fontSize: 10,
                                              fontWeight: FontWeight.w700,
                                              color: isDark ? const Color(0xFFFBBF24) : const Color(0xFFD97706),
                                            ),
                                          ),
                                          Text(
                                            ' • 24/7',
                                            style: TextStyle(
                                              fontSize: 9.5,
                                              fontWeight: FontWeight.w600,
                                              color: isDark ? Colors.white54 : Colors.black45,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                              );
                            },
                          ),
                        ),

                      Expanded(
                        child: ListView.builder(
                          controller: _scroll,
                          padding: const EdgeInsets.fromLTRB(14, 14, 14, 14),
                          itemCount: _turns.length + (_thinking ? 1 : 0),
                          itemBuilder: (context, i) {
                            if (i == _turns.length && _thinking) {
                              return _buildThinkingBubble(isDark);
                            }
                            return _buildTurnBubble(context, _turns[i], isDark, i);
                          },
                        ),
                      ),
                    ],
                  ),
                ),

                // Quick prompt chips (only shown when the last message does not already display suggestions)
                if (!_thinking &&
                    (_turns.isEmpty ||
                        _turns.last.suggestions == null ||
                        _turns.last.suggestions!.isEmpty))
                  Container(
                    height: 40,
                    padding: const EdgeInsets.symmetric(horizontal: 8),
                    child: ListView.separated(
                      scrollDirection: Axis.horizontal,
                      padding: const EdgeInsets.symmetric(horizontal: 6),
                      itemCount: _activeInitialPrompts.length,
                      separatorBuilder: (_, _) => const SizedBox(width: 8),
                      itemBuilder: (context, i) {
                        final prompt = _activeInitialPrompts[i];
                        return ActionChip(
                          avatar: const Icon(Icons.auto_awesome, size: 12, color: Color(0xFFD97706)),
                          label: Text(prompt, style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600)),
                          backgroundColor: isDark ? const Color(0xFF21173A) : Colors.white,
                          side: BorderSide(color: isDark ? Colors.white12 : const Color(0xFFE5E7EB)),
                          padding: const EdgeInsets.symmetric(horizontal: 4),
                          onPressed: () => _ask(prompt),
                        );
                      },
                    ),
                  ),

                // Clean Modern Composer Input Bar
                SafeArea(
                  top: false,
                  child: Container(
                    padding: const EdgeInsets.fromLTRB(10, 8, 10, 10),
                    decoration: BoxDecoration(
                      color: isDark ? const Color(0xFF17112B) : Colors.white,
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: isDark ? 0.4 : 0.06),
                          blurRadius: 10,
                          offset: const Offset(0, -3),
                        ),
                      ],
                    ),
                    child: Row(
                      children: [
                        // Palm & Kundli Scanner Quick Trigger
                        Tooltip(
                          message: isHi ? 'हस्तरेखा व कुंडली स्कैनर' : 'Scan Palm / Kundli',
                          child: InkWell(
                            onTap: _showPalmKundliScannerSheet,
                            borderRadius: BorderRadius.circular(20),
                            child: Container(
                              padding: const EdgeInsets.all(10),
                              decoration: BoxDecoration(
                                color: const Color(0xFFD97706).withValues(alpha: 0.14),
                                shape: BoxShape.circle,
                                border: Border.all(color: const Color(0xFFD97706).withValues(alpha: 0.4)),
                              ),
                              child: const Icon(Icons.document_scanner_rounded, color: Color(0xFFD97706), size: 18),
                            ),
                          ),
                        ),
                        const SizedBox(width: 6),
                        // Live Voice Darbar Trigger
                        Tooltip(
                          message: isHi ? 'लाइव वॉयस दरबार' : 'Live Voice Darbar',
                          child: InkWell(
                            onTap: _showVoiceDarbarSheet,
                            borderRadius: BorderRadius.circular(20),
                            child: Container(
                              padding: const EdgeInsets.all(10),
                              decoration: BoxDecoration(
                                color: const Color(0xFF7C3AED).withValues(alpha: 0.14),
                                shape: BoxShape.circle,
                                border: Border.all(color: const Color(0xFF7C3AED).withValues(alpha: 0.4)),
                              ),
                              child: const Icon(Icons.mic_rounded, color: Color(0xFF7C3AED), size: 18),
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Container(
                            decoration: BoxDecoration(
                              color: isDark ? const Color(0xFF241B3D) : const Color(0xFFF3F4F6),
                              borderRadius: BorderRadius.circular(24),
                              border: Border.all(
                                color: isDark ? Colors.white12 : Colors.black.withValues(alpha: 0.08),
                              ),
                            ),
                            padding: const EdgeInsets.symmetric(horizontal: 14),
                            child: TextField(
                              controller: _composer,
                              minLines: 1,
                              maxLines: 4,
                              textInputAction: TextInputAction.send,
                              onSubmitted: _ask,
                              style: TextStyle(
                                color: isDark ? Colors.white : Colors.black87,
                                fontSize: 14,
                              ),
                              decoration: InputDecoration(
                                hintText: isHi
                                    ? 'आचार्य जी से अपना प्रश्न पूछें…'
                                    : 'Ask your Vedic question or scan Kundli…',
                                hintStyle: TextStyle(
                                  color: isDark ? Colors.white38 : Colors.black38,
                                  fontSize: 13,
                                ),
                                border: InputBorder.none,
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Container(
                          decoration: const BoxDecoration(
                            shape: BoxShape.circle,
                            gradient: CustomerTheme.saffronGradient,
                          ),
                          child: IconButton(
                            icon: const Icon(Icons.send_rounded, color: Colors.white, size: 20),
                            onPressed: () => _ask(_composer.text),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
    );
  }

  Widget _buildThinkingBubble(bool isDark) {
    return Align(
      alignment: Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 8),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        decoration: BoxDecoration(
          color: isDark ? const Color(0xFF21173A) : Colors.white,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: const Color(0xFFD97706).withValues(alpha: 0.3)),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SizedBox(
              width: 16,
              height: 16,
              child: CircularProgressIndicator(
                strokeWidth: 2,
                valueColor: AlwaysStoppedAnimation<Color>(Color(0xFFD97706)),
              ),
            ),
            const SizedBox(width: 12),
            Flexible(
              child: Text(
                _isHindi
                    ? 'आचार्य जी आपकी कुंडली व ग्रहों की गणना कर रहे हैं…'
                    : 'Acharya is calculating Vedic planetary positions…',
                style: TextStyle(
                  fontSize: 12.5,
                  fontWeight: FontWeight.w600,
                  color: isDark ? const Color(0xFFFDE68A) : const Color(0xFF92400E),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTurnBubble(BuildContext context, _AiTurn turn, bool isDark, int index) {
    if (turn.mine) {
      return Align(
        alignment: Alignment.centerRight,
        child: Container(
          margin: const EdgeInsets.symmetric(vertical: 6),
          constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.82),
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          decoration: BoxDecoration(
            gradient: CustomerTheme.saffronGradient,
            borderRadius: const BorderRadius.only(
              topLeft: Radius.circular(18),
              topRight: Radius.circular(18),
              bottomLeft: Radius.circular(18),
              bottomRight: Radius.circular(4),
            ),
            boxShadow: [
              BoxShadow(
                color: const Color(0xFFD97706).withValues(alpha: 0.25),
                blurRadius: 8,
                offset: const Offset(0, 3),
              ),
            ],
          ),
          child: Text(
            turn.text,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 14,
              height: 1.35,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
      );
    }

    final pujaTitle = (turn.recommendedPuja?['puja_title'] ??
            turn.recommendedPuja?['title'] ??
            (_isHindi ? 'विशेष वैदिक पूजा' : 'Special Sacred Puja'))
        .toString();
    final pujaPlace = (turn.recommendedPuja?['puja_place'] ?? turn.recommendedPuja?['teerth'])?.toString();
    final pujaId = (turn.recommendedPuja?['puja_id'] ?? turn.recommendedPuja?['id']) as int?;

    return Align(
      alignment: Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 8),
        constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.92),
        decoration: BoxDecoration(
          color: isDark ? const Color(0xFF1C1430) : Colors.white,
          borderRadius: const BorderRadius.only(
            topLeft: Radius.circular(4),
            topRight: Radius.circular(20),
            bottomLeft: Radius.circular(20),
            bottomRight: Radius.circular(20),
          ),
          border: Border.all(
            color: isDark ? const Color(0xFF4C1D95).withValues(alpha: 0.45) : const Color(0xFFFDE68A),
            width: 1.1,
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: isDark ? 0.25 : 0.04),
              blurRadius: 10,
              offset: const Offset(0, 3),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Top author header
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
              decoration: BoxDecoration(
                color: isDark ? const Color(0xFF251B40) : const Color(0xFFFFFBEB),
                borderRadius: const BorderRadius.only(
                  topRight: Radius.circular(18),
                ),
              ),
              child: Row(
                children: [
                  Text(
                    turn.isAstrologerTwin ? '✨' : '🕉️',
                    style: const TextStyle(fontSize: 15),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Flexible(
                              child: Text(
                                turn.agentName ?? (_isHindi ? 'आचार्य वेद व्यास' : 'Acharya Ved Vyas'),
                                style: TextStyle(
                                  fontSize: 12.5,
                                  fontWeight: FontWeight.w800,
                                  color: isDark ? const Color(0xFFFDE68A) : const Color(0xFF92400E),
                                ),
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                            if (turn.isAstrologerTwin) ...[
                              const SizedBox(width: 5),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                                decoration: BoxDecoration(
                                  color: const Color(0xFF7C3AED),
                                  borderRadius: BorderRadius.circular(4),
                                ),
                                child: const Text(
                                  'AI TWIN',
                                  style: TextStyle(fontSize: 8, fontWeight: FontWeight.bold, color: Colors.white),
                                ),
                              ),
                            ],
                          ],
                        ),
                        if (turn.agentRole != null)
                          Text(
                            turn.agentRole!,
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w500,
                              color: isDark ? Colors.white60 : Colors.black54,
                            ),
                            overflow: TextOverflow.ellipsis,
                          ),
                      ],
                    ),
                  ),
                  // Speak / Stop Voice Button
                  InkWell(
                    onTap: () {
                      HapticFeedback.selectionClick();
                      if (_speakingTurnIndex == index) {
                        _stopSpeaking();
                      } else {
                        _speakText(turn.text, turnIndex: index);
                      }
                    },
                    borderRadius: BorderRadius.circular(14),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: _speakingTurnIndex == index
                            ? const Color(0xFF10B981).withValues(alpha: 0.2)
                            : const Color(0xFFD97706).withValues(alpha: 0.14),
                        borderRadius: BorderRadius.circular(14),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            _speakingTurnIndex == index
                                ? Icons.stop_circle_rounded
                                : Icons.volume_up_rounded,
                            size: 14,
                            color: _speakingTurnIndex == index
                                ? const Color(0xFF10B981)
                                : const Color(0xFFD97706),
                          ),
                          const SizedBox(width: 4),
                          Text(
                            _speakingTurnIndex == index
                                ? (_isHindi ? 'रोकें' : 'Stop')
                                : (_isHindi ? 'सुनें' : 'Listen'),
                            style: TextStyle(
                              fontSize: 10.5,
                              fontWeight: FontWeight.w800,
                              color: _speakingTurnIndex == index
                                  ? const Color(0xFF10B981)
                                  : (isDark ? const Color(0xFFFDE68A) : const Color(0xFFB45309)),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  IconButton(
                    icon: const Icon(Icons.copy_rounded, size: 15),
                    tooltip: 'Copy Guidance',
                    padding: EdgeInsets.zero,
                    constraints: const BoxConstraints(),
                    color: isDark ? Colors.white60 : Colors.black45,
                    onPressed: () => _copyToClipboard(turn.text),
                  ),
                ],
              ),
            ),

            // Message text body
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
              child: _renderFormattedSpiritualText(turn.text, isDark),
            ),

            // Beautified & Minified Dual CTAs: [ 📞 कॉल पर बात करें ] + [ 🔥 पूजा उपाय बुक करें ]
            Container(
              margin: const EdgeInsets.fromLTRB(14, 4, 14, 10),
              child: Row(
                children: [
                  // CTA 1: Sleek Emerald Call Button (Minified 38px)
                  Expanded(
                    child: InkWell(
                      onTap: () {
                        HapticFeedback.mediumImpact();
                        final realId = turn.realAstrologerId ??
                            (_selectedAgent != null ? (_selectedAgent!['real_astrologer_id'] ?? _selectedAgent!['id']) as int? : null) ??
                            widget.initialAstrologerId ??
                            1;
                        final name = turn.agentName ??
                            (_selectedAgent != null ? (_selectedAgent!['name'] ?? 'ज्योतिषाचार्य') : 'ज्योतिषाचार्य');
                        _showCallChoiceDialog(realId, name.toString());
                      },
                      borderRadius: BorderRadius.circular(14),
                      child: Container(
                        height: 38,
                        decoration: BoxDecoration(
                          color: const Color(0xFF064E3B).withValues(alpha: isDark ? 0.35 : 0.08),
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(
                            color: const Color(0xFF10B981).withValues(alpha: isDark ? 0.6 : 0.5),
                            width: 1.2,
                          ),
                          boxShadow: [
                            BoxShadow(
                              color: const Color(0xFF10B981).withValues(alpha: isDark ? 0.12 : 0.06),
                              blurRadius: 4,
                              offset: const Offset(0, 1.5),
                            ),
                          ],
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(Icons.phone_in_talk_rounded, size: 14, color: Color(0xFF10B981)),
                            const SizedBox(width: 6),
                            Flexible(
                              child: Text(
                                _isHindi ? 'कॉल पर बात करें' : 'Talk on Call',
                                style: const TextStyle(
                                  fontSize: 11.5,
                                  fontWeight: FontWeight.w700,
                                  color: Color(0xFF10B981),
                                  letterSpacing: 0.1,
                                ),
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),

                  // CTA 2: Saffron-Gold Puja Remedy Button (Minified 38px)
                  Expanded(
                    child: InkWell(
                      onTap: () {
                        HapticFeedback.mediumImpact();
                        _stopSpeaking();
                        if (pujaId != null && pujaId > 0) {
                          Navigator.of(context).push(
                            MaterialPageRoute(
                              builder: (_) => PujaDetailScreen(
                                puja: Puja(
                                  id: pujaId,
                                  title: pujaTitle,
                                  place: pujaPlace,
                                ),
                              ),
                            ),
                          );
                        } else {
                          Navigator.of(context).push(
                            MaterialPageRoute(
                              builder: (_) => const PujaListScreen(),
                            ),
                          );
                        }
                      },
                      borderRadius: BorderRadius.circular(14),
                      child: Container(
                        height: 38,
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(
                            colors: [Color(0xFFD97706), Color(0xFFEA580C)],
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                          ),
                          borderRadius: BorderRadius.circular(14),
                          boxShadow: [
                            BoxShadow(
                              color: const Color(0xFFD97706).withValues(alpha: 0.35),
                              blurRadius: 6,
                              offset: const Offset(0, 2),
                            ),
                          ],
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(Icons.local_fire_department_rounded, size: 15, color: Colors.white),
                            const SizedBox(width: 5),
                            Flexible(
                              child: Text(
                                _isHindi ? 'पूजा उपाय बुक करें' : 'Book Puja Remedy',
                                style: const TextStyle(
                                  fontSize: 11.5,
                                  fontWeight: FontWeight.w700,
                                  color: Colors.white,
                                  letterSpacing: 0.1,
                                ),
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // Fluid Suggested Question Chips
            if (index == _turns.length - 1 && turn.suggestions != null && turn.suggestions!.isNotEmpty)
              Container(
                padding: const EdgeInsets.fromLTRB(14, 0, 14, 12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: turn.suggestions!.map((s) {
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 6),
                      child: InkWell(
                        onTap: () {
                          HapticFeedback.selectionClick();
                          _ask(s);
                        },
                        borderRadius: BorderRadius.circular(14),
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7.5),
                          decoration: BoxDecoration(
                            color: isDark ? const Color(0xFF22173C) : const Color(0xFFF8FAFC),
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(
                              color: isDark ? Colors.white.withValues(alpha: 0.12) : const Color(0xFFE2E8F0),
                              width: 1,
                            ),
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.chat_bubble_outline_rounded, size: 12, color: Color(0xFFF59E0B)),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  s,
                                  style: TextStyle(
                                    fontSize: 11.5,
                                    fontWeight: FontWeight.w600,
                                    color: isDark ? const Color(0xFFF1F5F9) : const Color(0xFF334155),
                                    height: 1.3,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    );
                  }).toList(),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _renderFormattedSpiritualText(String text, bool isDark) {
    final cleanedText = _sanitizeMarkdownForDisplay(text);
    final lines = cleanedText.split('\n');
    final List<Widget> list = [];

    for (final raw in lines) {
      final line = raw.trim();
      if (line.isEmpty) {
        list.add(const SizedBox(height: 6));
        continue;
      }

      if (line.startsWith('### ') || line.startsWith('## ') || line.startsWith('# ')) {
        final title = line.replaceFirst(RegExp(r'^#+\s*'), '');
        list.add(Padding(
          padding: const EdgeInsets.only(top: 8, bottom: 4),
          child: Text(
            title,
            style: TextStyle(
              fontSize: 14.5,
              fontWeight: FontWeight.w800,
              color: isDark ? const Color(0xFFFDE68A) : const Color(0xFF92400E),
            ),
          ),
        ));
      } else if (line.startsWith('•') ||
          line.startsWith('1.') ||
          line.startsWith('2.') ||
          line.startsWith('3.') ||
          line.startsWith('4.')) {
        list.add(Padding(
          padding: const EdgeInsets.symmetric(vertical: 3),
          child: Text(
            line,
            style: TextStyle(
              fontSize: 13.5,
              fontWeight: FontWeight.w600,
              color: isDark ? Colors.white : Colors.black87,
              height: 1.42,
            ),
          ),
        ));
      } else {
        list.add(Padding(
          padding: const EdgeInsets.symmetric(vertical: 2),
          child: Text(
            line,
            style: TextStyle(
              fontSize: 13.5,
              color: isDark ? Colors.white70 : const Color(0xFF374151),
              height: 1.48,
            ),
          ),
        ));
      }
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: list,
    );
  }

  /// Feature 2: Live Interactive Voice AI Acharya Darbar
  void _showVoiceDarbarSheet() {
    HapticFeedback.mediumImpact();
    final isHi = _isHindi;
    final agentName = _selectedAgent != null
        ? (isHi
            ? (_selectedAgent!['name_hi'] ?? _selectedAgent!['name'] ?? 'आचार्य वेद व्यास').toString()
            : (_selectedAgent!['name'] ?? 'Acharya Ved Vyas').toString())
        : (isHi ? 'आचार्य वेद व्यास' : 'Acharya Ved Vyas');
    final agentRole = _selectedAgent != null
        ? (isHi
            ? (_selectedAgent!['specialty_hi'] ?? _selectedAgent!['specialty'] ?? 'प्रधान वैदिक ज्योतिषाचार्य').toString()
            : (_selectedAgent!['specialty'] ?? 'Master Vedic Astrologer').toString())
        : (isHi ? 'प्रधान वैदिक ज्योतिषाचार्य' : 'Master Vedic Astrologer');

    final voicePrompts = isHi
        ? [
            '🎙️ "आचार्य जी, मेरी वर्तमान महादशा और करियर में तरक्की का योग बताएं"',
            '🎙️ "घर की सुख-शांति और क्लेश निवारण के लिए कौन सा मंत्र और पूजा करें?"',
            '🎙️ "मेरा विवाह योग और वैवाहिक जीवन कैसा रहेगा?"',
            '🎙️ "धन वृद्धि के लिए मुझे कौन सा रत्न और रुद्राक्ष धारण करना चाहिए?"',
          ]
        : [
            '🎙️ "Acharya Ji, analyze my current Mahadasha and career growth timing"',
            '🎙️ "Chant and prescribe the right Vedic Mantra & Puja for peace at home"',
            '🎙️ "Check my marriage योग (Vivah Yoga) and relationship harmony"',
            '🎙️ "Which Mukhi Rudraksha and gemstone will unlock my wealth?"',
          ];

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 28),
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [Color(0xFF1E1136), Color(0xFF0F091D)],
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
          ),
          borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: Colors.white24,
                borderRadius: BorderRadius.circular(4),
              ),
            ),
            const SizedBox(height: 20),
            Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: CustomerTheme.saffronGradient,
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFFD97706).withValues(alpha: 0.45),
                    blurRadius: 24,
                    spreadRadius: 4,
                  ),
                ],
              ),
              child: const Icon(Icons.graphic_eq_rounded, color: Colors.white, size: 38),
            ),
            const SizedBox(height: 16),
            Text(
              isHi ? '🎙️ $agentName के साथ लाइव वॉयस दरबार' : '🎙️ Live Voice Darbar with $agentName',
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 17,
                fontWeight: FontWeight.w900,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              agentRole,
              textAlign: TextAlign.center,
              style: const TextStyle(color: Colors.white60, fontSize: 12),
            ),
            const SizedBox(height: 18),
            Align(
              alignment: Alignment.centerLeft,
              child: Text(
                isHi ? 'बोलकर पूछने के लिए किसी भी प्रश्न पर टैप करें:' : 'Tap a Sacred Voice Inquiry to Speak:',
                style: const TextStyle(
                  color: Color(0xFFFDE68A),
                  fontSize: 12,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ),
            const SizedBox(height: 10),
            ...voicePrompts.map((spoken) => Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: InkWell(
                    onTap: () {
                      Navigator.pop(ctx);
                      setState(() => _voiceModeEnabled = true);
                      _ask(spoken.replaceAll('🎙️ ', '').replaceAll('"', ''));
                    },
                    borderRadius: BorderRadius.circular(14),
                    child: Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.08),
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: Colors.white12),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.record_voice_over_rounded,
                              color: Color(0xFFF59E0B), size: 16),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              spoken,
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 12.5,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                          const Icon(Icons.arrow_forward_ios_rounded,
                              color: Colors.white54, size: 12),
                        ],
                      ),
                    ),
                  ),
                )),
          ],
        ),
      ),
    );
  }

  /// Feature 3: AI Janam Kundli & Samudrika Palm Vision Scanner
  void _showPalmKundliScannerSheet() {
    HapticFeedback.mediumImpact();
    String selectedHand = 'Right Hand (Active Karma)';
    String selectedFocus = 'Heart, Fate & Life Line + Shukra/Guru Mounts';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheetState) => Container(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 28),
          decoration: const BoxDecoration(
            color: Color(0xFF140E26),
            borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: Colors.white24,
                    borderRadius: BorderRadius.circular(4),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      gradient: CustomerTheme.saffronGradient,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(Icons.document_scanner_rounded,
                        color: Colors.white, size: 22),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          _isHindi ? 'AI हस्तरेखा एवं जन्म कुंडली स्कैनर' : 'AI Palm & Janam Kundli Vision Scanner',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 16,
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                        Text(
                          _isHindi
                              ? 'सामुद्रिक शास्त्र हस्तरेखा एवं लग्न कुंडली दोष विश्लेषण'
                              : 'Samudrika Shastra Hasta Rekha & Vedic Lagna Chart AI',
                          style: const TextStyle(color: Colors.white60, fontSize: 11.5),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              Text(
                _isHindi ? '1. स्कैन मोड चुनें:' : '1. Select Palm / Chart Scan Mode:',
                style: const TextStyle(
                  color: Color(0xFFFDE68A),
                  fontSize: 12,
                  fontWeight: FontWeight.w800,
                ),
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  'Right Hand (Active Karma)',
                  'Left Hand (Prarabdha Destiny)',
                  'Janam Kundli Lagna & Dosha Scan',
                ].map((mode) {
                  final sel = selectedHand == mode;
                  return ChoiceChip(
                    label: Text(
                      mode,
                      style: TextStyle(
                        fontSize: 11.5,
                        fontWeight: FontWeight.bold,
                        color: sel ? Colors.black : Colors.white,
                      ),
                    ),
                    selected: sel,
                    selectedColor: const Color(0xFFF59E0B),
                    backgroundColor: const Color(0xFF261C42),
                    onSelected: (v) {
                      if (v) {
                        setSheetState(() => selectedHand = mode);
                      }
                    },
                  );
                }).toList(),
              ),
              const SizedBox(height: 14),
              Text(
                _isHindi ? '2. मुख्य क्षेत्र चुनें:' : '2. Select Focus Area for Deep Reading:',
                style: const TextStyle(
                  color: Color(0xFFFDE68A),
                  fontSize: 12,
                  fontWeight: FontWeight.w800,
                ),
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  'Heart, Fate & Life Line + Shukra/Guru Mounts',
                  'Wealth & Rajyoga Triangle (Money Line)',
                  'Vivah Rekha (Marriage & Partner Timing)',
                  'Mangal / Kaal Sarp / Shani Sade Sati Scan',
                ].map((focus) {
                  final sel = selectedFocus == focus;
                  return ChoiceChip(
                    label: Text(
                      focus,
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                        color: sel ? Colors.black : Colors.white70,
                      ),
                    ),
                    selected: sel,
                    selectedColor: const Color(0xFFFDE68A),
                    backgroundColor: const Color(0xFF261C42),
                    onSelected: (v) {
                      if (v) {
                        setSheetState(() => selectedFocus = focus);
                      }
                    },
                  );
                }).toList(),
              ),
              const SizedBox(height: 18),
              Row(
                children: [
                  Expanded(
                    child: FilledButton.icon(
                      style: FilledButton.styleFrom(
                        backgroundColor: const Color(0xFFD97706),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 13),
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(14)),
                      ),
                      onPressed: () {
                        Navigator.pop(ctx);
                        final isKundli = selectedHand.contains('Kundli');
                        final targetId = isKundli ? 41 : 42;
                        final match = _swarmAgents.where((a) => a['id'] == targetId).toList();
                        if (match.isNotEmpty) {
                          setState(() => _selectedAgent = match.first);
                        }
                        final scanPrompt = _isHindi
                            ? (isKundli
                                ? '🪐 [AI जन्म कुंडली स्कैन] "$selectedFocus" के आधार पर मेरी लग्न कुंडली, ग्रह दोष और महादशा का विश्लेषण करें तथा सटीक वैदिक पूजा व मंत्र उपाय बताएं।'
                                : '✋ [AI हस्तरेखा स्कैन — $selectedHand] "$selectedFocus" के अनुसार मेरी जीवन रेखा, भाग्य रेखा, हृदय रेखा और पर्वतों का विश्लेषण करें और उपाय बताएं।')
                            : (isKundli
                                ? '🪐 [AI Janam Kundli Vision Scan] Perform a complete Vedic Lagna & Planetary Dosha scan focusing on "$selectedFocus". Explain my current Dasha effects and prescribe the exact Temple Puja & Mantra Upay.'
                                : '✋ [AI Samudrika Palm Vision Scan — $selectedHand] Perform a detailed Hasta Rekha reading focusing on "$selectedFocus". Analyze my Jeevan Rekha, Bhagya Rekha, Hridaya Rekha, and planetary mounts, and recommend remedies.');
                        _ask(scanPrompt);
                      },
                      icon: const Icon(Icons.auto_awesome_rounded, size: 18),
                      label: Text(
                        _isHindi ? 'AI स्कैन और भविष्यवाणी शुरू करें' : 'Run AI Vision Scan & Reading',
                        style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 13.5),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  /// Full-Screen Clean Modern Phone Call Mode with Vedic AI Acharya / AI Twin
  Widget _buildActivePhoneCallView(BuildContext context, bool isDark) {
    final isHi = _isHindi;
    final agentName = _selectedAgent != null
        ? (isHi
            ? (_selectedAgent!['name_hi'] ?? _selectedAgent!['name'] ?? 'आचार्य वेद व्यास').toString()
            : (_selectedAgent!['name'] ?? 'Acharya Ved Vyas').toString())
        : (isHi ? 'आचार्य वेद व्यास' : 'Acharya Ved Vyas');
    final agentRole = _selectedAgent != null
        ? (isHi
            ? (_selectedAgent!['specialty_hi'] ?? _selectedAgent!['specialty'] ?? 'प्रधान वैदिक ज्योतिषाचार्य').toString()
            : (_selectedAgent!['specialty'] ?? 'Master Vedic Astrologer').toString())
        : (isHi ? 'प्रधान वैदिक ज्योतिषाचार्य' : 'Master Vedic Astrologer');
    final agentIcon = _selectedAgent != null
        ? (_selectedAgent!['icon'] ?? '🕉️').toString()
        : '🕉️';
    final isSpeakingNow = _speakingTurnIndex != null;
    final lastAiTurn = _turns.lastWhere(
      (t) => !t.mine,
      orElse: () => _AiTurn(
        isHi
            ? '🕉️ हरि ॐ तत्सत्! आप लाइव वैदिक वॉयस कॉल से जुड़ चुके हैं।'
            : '🕉️ Hari Om Tatsat. Connected to live Vedic AI voice call.',
        mine: false,
      ),
    );
    final sanitizedSpokenText = _sanitizeMarkdownForDisplay(lastAiTurn.text);

    final callQuickPrompts = isHi
        ? [
            'मेरी वर्तमान महादशा और करियर तरक्की बताएं',
            'बाधा निवारण के लिए कौन सी पूजा या मंत्र करें?',
            'मेरा विवाह और प्रेम योग कैसा है?',
            'मेरे लिए कौन सा रत्न व रुद्राक्ष शुभ है?',
          ]
        : [
            'Analyze my current Mahadasha & career growth',
            'Which Puja or Mantra will remove obstacles?',
            'Check my marriage & relationship yoga',
            'Suggest auspicious Gemstone & Rudraksha',
          ];

    return Container(
      width: double.infinity,
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [Color(0xFF130A26), Color(0xFF080412)],
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
        ),
      ),
      child: SafeArea(
        child: Column(
          children: [
            // 1. Clean Top Call Bar: Back/Chat + LIVE CALL Timer + 1-Tap Hindi/EN Switch
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 8, 12, 8),
              child: Row(
                children: [
                  IconButton(
                    icon: const Icon(Icons.keyboard_arrow_down_rounded, color: Colors.white, size: 28),
                    tooltip: isHi ? 'चैट देखें' : 'Switch to Chat',
                    onPressed: () => setState(() => _inCallMode = false),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                    decoration: BoxDecoration(
                      color: const Color(0xFF10B981).withValues(alpha: 0.16),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: const Color(0xFF10B981).withValues(alpha: 0.5)),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Container(
                          width: 7,
                          height: 7,
                          decoration: const BoxDecoration(
                            color: Color(0xFF10B981),
                            shape: BoxShape.circle,
                          ),
                        ),
                        const SizedBox(width: 6),
                        Text(
                          '${isHi ? "लाइव कॉल" : "LIVE CALL"} • $_formattedCallDuration',
                          style: const TextStyle(
                            color: Color(0xFF6EE7B7),
                            fontSize: 11,
                            fontWeight: FontWeight.w900,
                            letterSpacing: 0.3,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const Spacer(),
                  // 1-Tap Hindi / English Voice Switch on Call Screen
                  InkWell(
                    onTap: _toggleLanguage,
                    borderRadius: BorderRadius.circular(18),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                      decoration: BoxDecoration(
                        color: isHi
                            ? const Color(0xFFD97706).withValues(alpha: 0.25)
                            : Colors.white.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(18),
                        border: Border.all(
                          color: isHi ? const Color(0xFFF59E0B) : Colors.white24,
                        ),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.record_voice_over_rounded, size: 13, color: Color(0xFFFDE68A)),
                          const SizedBox(width: 5),
                          Text(
                            isHi ? '🇮🇳 हिंदी वॉयस' : '🇬🇧 English / हिंदी',
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 11,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // 1.5 Compact Acharya Switcher Strip
            if (_swarmAgents.isNotEmpty)
              SizedBox(
                height: 38,
                child: ListView.separated(
                  scrollDirection: Axis.horizontal,
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  itemCount: _swarmAgents.length,
                  separatorBuilder: (_, _) => const SizedBox(width: 8),
                  itemBuilder: (context, idx) {
                    final a = _swarmAgents[idx];
                    final sel = _selectedAgent != null && _selectedAgent!['id'] == a['id'];
                    final nm = isHi ? (a['name_hi'] ?? a['name']) : a['name'];
                    return GestureDetector(
                      onTap: () {
                        HapticFeedback.selectionClick();
                        setState(() => _selectedAgent = a);
                      },
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                        decoration: BoxDecoration(
                          color: sel ? const Color(0xFFD97706) : Colors.white.withValues(alpha: 0.07),
                          borderRadius: BorderRadius.circular(18),
                          border: Border.all(
                            color: sel ? const Color(0xFFFDE68A) : Colors.white12,
                          ),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Text((a['icon'] ?? '🕉️').toString(), style: const TextStyle(fontSize: 12)),
                            const SizedBox(width: 5),
                            Text(
                              nm.toString(),
                              style: TextStyle(
                                color: sel ? Colors.white : Colors.white70,
                                fontSize: 11,
                                fontWeight: sel ? FontWeight.w800 : FontWeight.w600,
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
              ),

            // 2. Center Avatar + Live Voice Status + Clean Spoken Vani Card
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(18, 16, 18, 12),
                child: Column(
                  children: [
                    // Pulsing Acharya Avatar Ring
                    AnimatedContainer(
                      duration: const Duration(milliseconds: 350),
                      padding: EdgeInsets.all(isSpeakingNow ? 12 : 5),
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        border: Border.all(
                          color: isSpeakingNow
                              ? const Color(0xFF10B981)
                              : const Color(0xFFD97706).withValues(alpha: 0.5),
                          width: isSpeakingNow ? 3 : 1.5,
                        ),
                        boxShadow: [
                          BoxShadow(
                            color: (isSpeakingNow
                                    ? const Color(0xFF10B981)
                                    : const Color(0xFFD97706))
                                .withValues(alpha: isSpeakingNow ? 0.45 : 0.22),
                            blurRadius: isSpeakingNow ? 28 : 14,
                            spreadRadius: isSpeakingNow ? 5 : 1,
                          ),
                        ],
                      ),
                      child: CircleAvatar(
                        radius: 38,
                        backgroundColor: const Color(0xFF2B125A),
                        child: Text(
                          agentIcon,
                          style: const TextStyle(fontSize: 34),
                        ),
                      ),
                    ),
                    const SizedBox(height: 10),
                    Text(
                      agentName,
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 18.5,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      agentRole,
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        color: Color(0xFFFDE68A),
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.07),
                        borderRadius: BorderRadius.circular(16),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            _thinking
                                ? Icons.hourglass_top_rounded
                                : (isSpeakingNow ? Icons.graphic_eq_rounded : Icons.hearing_rounded),
                            size: 14,
                            color: _thinking
                                ? const Color(0xFFF59E0B)
                                : (isSpeakingNow ? const Color(0xFF10B981) : Colors.white70),
                          ),
                          const SizedBox(width: 6),
                          Flexible(
                            child: Text(
                              _thinking
                                  ? (isHi
                                      ? 'आचार्य जी ग्रहों की गणना कर रहे हैं…'
                                      : 'Acharya is calculating Kundli positions…')
                                  : (isSpeakingNow
                                      ? (isHi
                                          ? 'आचार्य जी बोल रहे हैं (स्पीकर चालू)…'
                                          : 'Speaking out loud via phone speaker…')
                                      : (isHi
                                          ? 'सुन रहे हैं — नीचे प्रश्न चुनें या पूछें'
                                          : 'Listening — Tap a question or ask below')),
                              style: const TextStyle(
                                color: Colors.white70,
                                fontSize: 11,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 14),

                    // Clean Spoken Dialogue Card (Zero Markdown Tables!)
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(15),
                      decoration: BoxDecoration(
                        color: const Color(0xFF1C1333),
                        borderRadius: BorderRadius.circular(18),
                        border: Border.all(
                          color: const Color(0xFFD97706).withValues(alpha: 0.38),
                        ),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              const Icon(Icons.volume_up_rounded, size: 15, color: Color(0xFFFDE68A)),
                              const SizedBox(width: 6),
                              Text(
                                isHi ? 'आचार्य वाणी (लाइव वॉयस)' : 'LIVE ACHARYA VOICE',
                                style: const TextStyle(
                                  fontSize: 10.5,
                                  fontWeight: FontWeight.w900,
                                  color: Color(0xFFFDE68A),
                                  letterSpacing: 0.4,
                                ),
                              ),
                              const Spacer(),
                              InkWell(
                                onTap: () {
                                  if (isSpeakingNow) {
                                    _stopSpeaking();
                                  } else {
                                    _speakText(lastAiTurn.text, turnIndex: _turns.length - 1);
                                  }
                                },
                                child: Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFF10B981).withValues(alpha: 0.16),
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                  child: Text(
                                    isSpeakingNow
                                        ? (isHi ? 'आवाज़ रोकें' : 'Pause Voice')
                                        : (isHi ? 'फिर से सुनें 🔊' : 'Replay Voice 🔊'),
                                    style: const TextStyle(
                                      color: Color(0xFF34D399),
                                      fontSize: 10.5,
                                      fontWeight: FontWeight.w800,
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 10),
                          Text(
                            sanitizedSpokenText,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 14,
                              height: 1.5,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                          if (lastAiTurn.recommendedPuja != null) ...[
                            const SizedBox(height: 12),
                            SizedBox(
                              width: double.infinity,
                              child: ElevatedButton.icon(
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFFD97706),
                                  foregroundColor: Colors.white,
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                ),
                                onPressed: () {
                                  final pujaId = (lastAiTurn.recommendedPuja!['puja_id'] ??
                                      lastAiTurn.recommendedPuja!['id']) as int?;
                                  final pujaTitle = (lastAiTurn.recommendedPuja!['puja_title'] ??
                                          lastAiTurn.recommendedPuja!['title'] ??
                                          'Puja')
                                      .toString();
                                  final pujaPlace = (lastAiTurn.recommendedPuja!['puja_place'] ??
                                          lastAiTurn.recommendedPuja!['teerth'])
                                      ?.toString();
                                  _stopSpeaking();
                                  Navigator.of(context).push(
                                    MaterialPageRoute(
                                      builder: (_) => PujaDetailScreen(
                                        puja: Puja(
                                          id: pujaId,
                                          title: pujaTitle,
                                          place: pujaPlace,
                                        ),
                                      ),
                                    ),
                                  );
                                },
                                icon: const Icon(Icons.local_fire_department_rounded, size: 16),
                                label: Text(
                                  isHi
                                      ? 'सुझाई गई पूजा बुक करें: ${lastAiTurn.recommendedPuja!['puja_title'] ?? lastAiTurn.recommendedPuja!['title'] ?? "पूजा"}'
                                      : 'Book Prescribed ${lastAiTurn.recommendedPuja!['puja_title'] ?? lastAiTurn.recommendedPuja!['title'] ?? "Puja"}',
                                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
                                ),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                    const SizedBox(height: 14),

                    // Instant Voice Prompt Buttons during Call
                    Align(
                      alignment: Alignment.centerLeft,
                      child: Text(
                        isHi ? 'कॉल पर तुरंत पूछने के लिए टैप करें:' : 'Tap to Ask on Call:',
                        style: const TextStyle(
                          color: Color(0xFFFDE68A),
                          fontSize: 11.5,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: callQuickPrompts.map((q) {
                        return ActionChip(
                          backgroundColor: const Color(0xFF251B40),
                          side: const BorderSide(color: Colors.white12),
                          avatar: const Icon(Icons.record_voice_over_rounded,
                              size: 13, color: Color(0xFFF59E0B)),
                          label: Text(
                            q,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 11.5,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                          onPressed: () => _ask(q),
                        );
                      }).toList(),
                    ),
                  ],
                ),
              ),
            ),

            // 3. Call Input + Phone Call Controls Dock (Speaker, Scan, End Call)
            Container(
              padding: const EdgeInsets.fromLTRB(12, 10, 12, 12),
              decoration: const BoxDecoration(
                color: Color(0xFF160F29),
                border: Border(top: BorderSide(color: Colors.white10)),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 14),
                          decoration: BoxDecoration(
                            color: const Color(0xFF241A3E),
                            borderRadius: BorderRadius.circular(24),
                            border: Border.all(color: Colors.white12),
                          ),
                          child: TextField(
                            controller: _composer,
                            textInputAction: TextInputAction.send,
                            onSubmitted: _ask,
                            style: const TextStyle(color: Colors.white, fontSize: 13.5),
                            decoration: InputDecoration(
                              hintText: isHi
                                  ? 'कॉल पर आचार्य जी से पूछें…'
                                  : 'Ask Acharya on call…',
                              hintStyle: const TextStyle(color: Colors.white38, fontSize: 12.5),
                              border: InputBorder.none,
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Container(
                        decoration: const BoxDecoration(
                          shape: BoxShape.circle,
                          gradient: CustomerTheme.saffronGradient,
                        ),
                        child: IconButton(
                          icon: const Icon(Icons.send_rounded, color: Colors.white, size: 18),
                          onPressed: () => _ask(_composer.text),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                    children: [
                      // Speaker Toggle
                      _callControlButton(
                        icon: _voiceModeEnabled ? Icons.volume_up_rounded : Icons.volume_off_rounded,
                        label: _voiceModeEnabled
                            ? (isHi ? 'स्पीकर चालू' : 'Speaker ON')
                            : (isHi ? 'म्यूट' : 'Muted'),
                        active: _voiceModeEnabled,
                        activeColor: const Color(0xFF10B981),
                        onTap: () {
                          setState(() => _voiceModeEnabled = !_voiceModeEnabled);
                          if (!_voiceModeEnabled) {
                            _stopSpeaking();
                          } else {
                            _speakText(lastAiTurn.text, turnIndex: _turns.length - 1);
                          }
                        },
                      ),
                      // Palm / Kundli Vision Scan during Call
                      _callControlButton(
                        icon: Icons.document_scanner_rounded,
                        label: isHi ? 'कुंडली/हस्तरेखा' : 'Scan Kundli',
                        active: false,
                        activeColor: const Color(0xFFD97706),
                        onTap: _showPalmKundliScannerSheet,
                      ),
                      // End Call Red Button
                      InkWell(
                        onTap: () {
                          _endPhoneCall();
                          if (widget.startInCallMode) {
                            Navigator.of(context).maybePop();
                          }
                        },
                        borderRadius: BorderRadius.circular(28),
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 9),
                          decoration: BoxDecoration(
                            color: const Color(0xFFEF4444),
                            borderRadius: BorderRadius.circular(28),
                            boxShadow: [
                              BoxShadow(
                                color: const Color(0xFFEF4444).withValues(alpha: 0.4),
                                blurRadius: 12,
                                offset: const Offset(0, 3),
                              ),
                            ],
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const Icon(Icons.call_end_rounded, color: Colors.white, size: 17),
                              const SizedBox(width: 6),
                              Text(
                                isHi ? 'कॉल समाप्त' : 'End Call',
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.w900,
                                  fontSize: 12.5,
                                ),
                              ),
                            ],
                          ),
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

  Widget _callControlButton({
    required IconData icon,
    required String label,
    required bool active,
    required Color activeColor,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(20),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 8),
        decoration: BoxDecoration(
          color: active ? activeColor.withValues(alpha: 0.2) : Colors.white.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: active ? activeColor : Colors.white12,
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 15, color: active ? activeColor : Colors.white70),
            const SizedBox(width: 5),
            Text(
              label,
              style: TextStyle(
                color: active ? Colors.white : Colors.white70,
                fontSize: 11.5,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ),
      ),
    );
  }
}


