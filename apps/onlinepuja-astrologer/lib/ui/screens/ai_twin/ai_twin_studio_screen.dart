import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:op_shared/op_shared.dart';
import 'package:provider/provider.dart';

import '../../../state/app_session.dart';
import '../../theme/partner_theme.dart';
import '../../widgets/partner_widgets.dart';
import '../chat/chat_session_screen.dart';
import '../social/astrologer_social_growth_screen.dart';

/// Vedic AI Twin Training Studio for Astrologers & Pandits.
/// Enables Pandits to train their autonomous AI Digital Twin with custom
/// Parampara, Jyotish rules, Q&A training pairs, puja prescriptions,
/// and manage live takeovers & social media autopilot.
class AiTwinStudioScreen extends StatefulWidget {
  const AiTwinStudioScreen({super.key});

  static const route = '/ai-twin-studio';

  @override
  State<AiTwinStudioScreen> createState() => _AiTwinStudioScreenState();
}

class _AiTwinStudioScreenState extends State<AiTwinStudioScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  bool _isLoading = true;
  bool _isSaving = false;

  // AI Twin Configuration State
  bool _aiTwinEnabled = false;
  bool _aiTwinAutoOffline = true;
  String _selectedTradition = 'parashari';
  String _aiTwinTone = 'empathetic';
  final _aiTwinGreeting = TextEditingController();
  final _aiTwinPrompt = TextEditingController();
  final _aiTwinCharge = TextEditingController(text: '8');
  Map<String, dynamic>? _aiTwinStats;

  // Custom Q&A Training Pairs
  final List<Map<String, String>> _trainingQaPairs = [
    {
      'question': 'मेरी नौकरी में पदोन्नति (प्रमोशन) कब तक संभव है?',
      'answer':
          'सूर्य देव को प्रतिदिन तांबे के लोटे से अर्घ्य दें एवं रविवार को आदित्य हृदय स्तोत्र का पाठ करें। कुंडली में दशमेश की स्थिति अनुसार शुभ योग शीघ्र बनेंगे।',
    },
    {
      'question': 'विवाह में निरंतर विलंब का क्या कारण व उपाय है?',
      'answer':
          'कुंडली में सप्तमेश व गुरु की स्थिति का परीक्षण आवश्यक है। गुरुवार को हल्दी की गांठ बहते जल में प्रवाहित करें अथवा पीली वस्तुओं का दान करें।',
    },
    {
      'question': 'क्या मेरी कुंडली में मांगलिक दोष है?',
      'answer':
          'मंगल १, ४, ७, ८ अथवा १२वें भाव में होने पर मांगलिक दोष बनता है। हम मंगलवार को मंगल शांति अथवा अंगारक दोष निवारक अनुष्ठान की संस्तुति करते हैं।',
    },
  ];

  final _newQuestionCtrl = TextEditingController();
  final _newAnswerCtrl = TextEditingController();

  // Active AI Twin Live Sessions for Human Takeover
  final List<Map<String, dynamic>> _activeTwinSessions = [
    {
      'id': 'sess_1029',
      'devotee_name': 'अमित शर्मा (Amit Sharma)',
      'topic': 'शनि की साढ़ेसाती एवं करियर बाधा',
      'duration': '३ मिनट से लाइव',
      'status': 'AI ट्विन परामर्श जारी',
      'customerId': 1029,
    },
  ];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
    _loadAiTwinData();
  }

  @override
  void dispose() {
    _tabController.dispose();
    _aiTwinGreeting.dispose();
    _aiTwinPrompt.dispose();
    _aiTwinCharge.dispose();
    _newQuestionCtrl.dispose();
    _newAnswerCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadAiTwinData() async {
    setState(() => _isLoading = true);
    final user = context.read<PartnerSession>().user;
    final astroId = user?.id ?? 1;

    try {
      final data = await AiTwinApi.instance.getAiTwinStatus(astrologerId: astroId);
      if (mounted && data.isNotEmpty) {
        setState(() {
          _aiTwinEnabled = (data['ai_twin_enabled'] == 1 || data['ai_twin_enabled'] == true);
          _aiTwinAutoOffline = (data['ai_twin_auto_offline'] == 1 || data['ai_twin_auto_offline'] == true);
          _aiTwinTone = data['ai_twin_tone']?.toString() ?? 'empathetic';
          _aiTwinGreeting.text = data['ai_twin_greeting']?.toString() ??
              'प्रणाम! मैं आचार्य जी का वैदिक AI अवतार हूँ। आपकी कुंडली व जीवन मार्गदर्शन हेतु तत्पर हूँ। ॐ नमो नारायण!';
          _aiTwinPrompt.text = data['ai_twin_prompt']?.toString() ??
              'सदा सात्विक जीवनशैली, श्री हनुमान चालीसा, एवं वैदिक पूजा-हवन पर बल दें। मारक दोषों हेतु महामृत्युंजय एवं कालभैरव अनुष्ठान सुझाएं।';
          _aiTwinCharge.text = (data['ai_twin_charge'] ?? 8).toString();
          _aiTwinStats = data['stats'] is Map
              ? Map<String, dynamic>.from(data['stats'] as Map)
              : null;
        });
      }
    } catch (e) {
      debugPrint('Error loading AI Twin studio: $e');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _saveAiTwin() async {
    final user = context.read<PartnerSession>().user;
    final astroId = user?.id ?? 1;
    setState(() => _isSaving = true);

    try {
      // Pack rules, tradition & Q&A into prompt knowledge
      final fullKnowledge = StringBuffer();
      fullKnowledge.writeln('ज्योतिष परंपरा (Tradition): ${_traditionLabel(_selectedTradition)}');
      fullKnowledge.writeln('मार्गदर्शक दर्शन एवं नियम (Philosophy & Rules): ${_aiTwinPrompt.text.trim()}');
      fullKnowledge.writeln('--- प्रशिक्षित प्रश्न-उत्तर (Training Q&A) ---');
      for (final qa in _trainingQaPairs) {
        fullKnowledge.writeln('प्र: ${qa['question']}');
        fullKnowledge.writeln('उ: ${qa['answer']}');
      }

      await AiTwinApi.instance.setupAiTwin(
        astrologerId: astroId,
        enabled: _aiTwinEnabled,
        tone: _aiTwinTone,
        greeting: _aiTwinGreeting.text.trim(),
        prompt: fullKnowledge.toString().trim(),
        charge: double.tryParse(_aiTwinCharge.text.trim()) ?? 8.0,
        autoOffline: _aiTwinAutoOffline,
      );

      if (mounted) {
        HapticFeedback.mediumImpact();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('✓ वैदिक AI ट्विन प्रशिक्षण सफलतापूर्वक सहेजा गया! (AI Twin Saved)'),
            backgroundColor: Color(0xFF059669),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('सहेजने में त्रुटि: $e'),
            backgroundColor: PartnerTheme.crimson,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  String _traditionLabel(String key) {
    switch (key) {
      case 'parashari':
        return 'पाराशरी वैदिक ज्योतिष (Parashari)';
      case 'jaimini':
        return 'जैमिनी सूत्र ज्योतिष (Jaimini)';
      case 'lal_kitab':
        return 'लाल किताब व व्यावहारिक उपाय (Lal Kitab)';
      case 'kp':
        return 'केपी कृष्णमूर्ति पद्धति (KP System)';
      case 'nadi':
        return 'भृगु नंदी नाड़ी ज्योतिष (Nadi)';
      default:
        return 'पाराशरी वैदिक';
    }
  }

  @override
  Widget build(BuildContext context) {
    final dark = Theme.of(context).brightness == Brightness.dark;
    final bg = dark ? PartnerTheme.darkBg : const Color(0xFFF8FAFC);
    final surface = dark ? PartnerTheme.darkSurface : Colors.white;
    final border = dark ? PartnerTheme.darkBorder : const Color(0xFFE2E8F0);

    return Scaffold(
      backgroundColor: bg,
      appBar: AppBar(
        title: const Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'वैदिक AI ट्विन स्टूडियो',
              style: TextStyle(fontWeight: FontWeight.w900, fontSize: 17),
            ),
            Text(
              'Digital Twin Training & Autonomous Growth',
              style: TextStyle(fontSize: 11, fontWeight: FontWeight.w500),
            ),
          ],
        ),
        actions: [
          IconButton(
            tooltip: 'सोशल मीडिया ऑटोपायलट खोलें',
            icon: const Icon(Icons.hub_rounded, color: Color(0xFFF59E0B)),
            onPressed: () => Navigator.pushNamed(context, AstrologerSocialGrowthScreen.route),
          ),
          TextButton.icon(
            onPressed: _isSaving ? null : _saveAiTwin,
            icon: _isSaving
                ? const SizedBox(
                    width: 14,
                    height: 14,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                  )
                : const Icon(Icons.check_circle_rounded, size: 16),
            label: const Text('सहेजें', style: TextStyle(fontWeight: FontWeight.w800)),
            style: TextButton.styleFrom(
              foregroundColor: const Color(0xFF10B981),
            ),
          ),
        ],
        bottom: TabBar(
          controller: _tabController,
          isScrollable: true,
          labelColor: PartnerTheme.saffron,
          unselectedLabelColor: dark ? Colors.white60 : Colors.black54,
          indicatorColor: PartnerTheme.saffron,
          indicatorWeight: 3,
          tabs: const [
            Tab(icon: Icon(Icons.psychology_rounded, size: 18), text: 'परंपरा व स्वरूप'),
            Tab(icon: Icon(Icons.menu_book_rounded, size: 18), text: 'ज्ञान कोष व नियम'),
            Tab(icon: Icon(Icons.forum_rounded, size: 18), text: 'Q&A प्रशिक्षण'),
            Tab(icon: Icon(Icons.bolt_rounded, size: 18), text: 'लाइव टेकओवर'),
          ],
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: PartnerTheme.saffron))
          : TabBarView(
              controller: _tabController,
              children: [
                _buildPersonaTab(dark, surface, border),
                _buildKnowledgeTab(dark, surface, border),
                _buildQaTrainingTab(dark, surface, border),
                _buildLiveTakeoverTab(dark, surface, border),
              ],
            ),
    );
  }

  // 1. Persona & Parampara Tab
  Widget _buildPersonaTab(bool dark, Color surface, Color border) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        // Master Toggle Card
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            gradient: LinearGradient(
              colors: [
                const Color(0xFFF59E0B).withValues(alpha: dark ? 0.2 : 0.12),
                const Color(0xFFD97706).withValues(alpha: dark ? 0.1 : 0.05),
              ],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: const Color(0xFFF59E0B).withValues(alpha: 0.4)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF59E0B),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: const Icon(Icons.smart_toy_rounded, size: 20, color: Colors.white),
                      ),
                      const SizedBox(width: 10),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'AI ट्विन 24/7 सक्रिय करें',
                            style: TextStyle(fontWeight: FontWeight.w900, fontSize: 15),
                          ),
                          Text(
                            _aiTwinEnabled ? '✓ सक्रिय (Online 24/7)' : 'निष्क्रीय (Disabled)',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w700,
                              color: _aiTwinEnabled ? const Color(0xFF10B981) : Colors.grey,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                  Switch(
                    value: _aiTwinEnabled,
                    activeColor: const Color(0xFFF59E0B),
                    onChanged: (v) => setState(() => _aiTwinEnabled = v),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              const Divider(height: 1),
              const SizedBox(height: 12),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'ऑफलाइन होने पर स्वतः टेकओवर',
                          style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13),
                        ),
                        Text(
                          'जब आप व्यस्त या ऑफलाइन होंगे, AI तुरंत उत्तर देगा',
                          style: TextStyle(fontSize: 11, color: Colors.grey),
                        ),
                      ],
                    ),
                  ),
                  Switch(
                    value: _aiTwinAutoOffline,
                    activeColor: const Color(0xFF059669),
                    onChanged: (v) => setState(() => _aiTwinAutoOffline = v),
                  ),
                ],
              ),
            ],
          ),
        ),
        const SizedBox(height: 16),

        // Passive Revenue Banner
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
          decoration: BoxDecoration(
            color: const Color(0xFF10B981).withValues(alpha: 0.12),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: const Color(0xFF10B981).withValues(alpha: 0.3)),
          ),
          child: const Row(
            children: [
              Icon(Icons.monetization_on_rounded, size: 20, color: Color(0xFF10B981)),
              SizedBox(width: 10),
              Expanded(
                child: Text(
                  '६०% निष्क्रीय आय: आपके AI ट्विन से हुए सभी परामर्श का ६०% शुल्क सीधे आपके वॉलेट में क्रेडिट होता है।',
                  style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Color(0xFF10B981)),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 20),

        // Tradition / Parampara Selector
        const Text(
          'आपकी ज्योतिषीय परंपरा (Vedic Lineage / Parampara):',
          style: TextStyle(fontWeight: FontWeight.w800, fontSize: 14),
        ),
        const SizedBox(height: 8),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 12),
          decoration: BoxDecoration(
            color: surface,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: border),
          ),
          child: DropdownButtonHideUnderline(
            child: DropdownButton<String>(
              isExpanded: true,
              value: _selectedTradition,
              items: const [
                DropdownMenuItem(value: 'parashari', child: Text('पाराशरी वेदोक्त ज्योतिष (Parashari Classic)')),
                DropdownMenuItem(value: 'jaimini', child: Text('जैमिनी सूत्र एवं चर दशा (Jaimini)')),
                DropdownMenuItem(value: 'lal_kitab', child: Text('लाल किताब एवं अचूक टोटके (Lal Kitab)')),
                DropdownMenuItem(value: 'kp', child: Text('केपी कृष्णमूर्ति पद्धति (KP Astrology)')),
                DropdownMenuItem(value: 'nadi', child: Text('भृगु नंदी नाड़ी ज्योतिष (Nadi)')),
              ],
              onChanged: (v) => setState(() => _selectedTradition = v ?? 'parashari'),
            ),
          ),
        ),
        const SizedBox(height: 20),

        // Persona Tone Selector
        const Text(
          'संवाद का स्वरूप एवं स्वर (Persona Tone):',
          style: TextStyle(fontWeight: FontWeight.w800, fontSize: 14),
        ),
        const SizedBox(height: 8),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [
            _toneChip('empathetic', 'करुणा व सहानुभूति', Icons.volunteer_activism_rounded),
            _toneChip('vedic', 'वेदोक्त व शास्त्रीय', Icons.temple_hindu_rounded),
            _toneChip('scholarly', 'विद्वान व तार्किक', Icons.menu_book_rounded),
            _toneChip('mystic', 'गूढ़ व आध्यात्मिक', Icons.auto_awesome_rounded),
          ],
        ),
        const SizedBox(height: 20),

        // Custom First Greeting
        const Text(
          'यजमान हेतु प्रथम स्वागत संदेश (First Greeting):',
          style: TextStyle(fontWeight: FontWeight.w800, fontSize: 14),
        ),
        const SizedBox(height: 6),
        TextFormField(
          controller: _aiTwinGreeting,
          maxLines: 2,
          decoration: InputDecoration(
            hintText: 'ॐ नमो नारायण! मैं आचार्य जी का वैदिक AI अवतार हूँ...',
            filled: true,
            fillColor: surface,
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
          ),
        ),
        const SizedBox(height: 20),

        // Rate per minute
        const Text(
          'AI ट्विन परामर्श दक्षिणा दर (₹ प्रति मिनट):',
          style: TextStyle(fontWeight: FontWeight.w800, fontSize: 14),
        ),
        const SizedBox(height: 6),
        TextFormField(
          controller: _aiTwinCharge,
          keyboardType: TextInputType.number,
          decoration: InputDecoration(
            prefixText: '₹ ',
            suffixText: '/ मिनट (अनुशंसित: ₹5 से ₹15)',
            filled: true,
            fillColor: surface,
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
          ),
        ),
        const SizedBox(height: 30),
      ],
    );
  }

  Widget _toneChip(String val, String label, IconData icon) {
    final sel = _aiTwinTone == val;
    return ChoiceChip(
      avatar: Icon(icon, size: 16, color: sel ? Colors.white : PartnerTheme.saffron),
      label: Text(label),
      selected: sel,
      selectedColor: PartnerTheme.saffron,
      labelStyle: TextStyle(
        color: sel ? Colors.white : Colors.black87,
        fontWeight: FontWeight.w700,
        fontSize: 12.5,
      ),
      onSelected: (_) => setState(() => _aiTwinTone = val),
    );
  }

  // 2. Knowledge Base & Rules Tab
  Widget _buildKnowledgeTab(bool dark, Color surface, Color border) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        const Text(
          'पंडित जी का ज्ञान कोष व मार्गदर्शक सिद्धांत:',
          style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15),
        ),
        const SizedBox(height: 4),
        const Text(
          'यहाँ वे सभी नियम व मान्यताएं लिखें जो आप चाहते हैं कि आपका AI ट्विन यजमानों को बताते समय हमेशा ध्यान रखे।',
          style: TextStyle(fontSize: 12, color: Colors.grey),
        ),
        const SizedBox(height: 10),
        TextFormField(
          controller: _aiTwinPrompt,
          maxLines: 6,
          decoration: InputDecoration(
            hintText: 'उदा. मांगलिक दोष में केवल कुंभ विवाह की सलाह दें। नीलम रत्न बिना ३ दिन ट्रायल के न दें। शनिवार को छाया दान व सुंदरकांड का विशेष महत्व बताएं...',
            filled: true,
            fillColor: surface,
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
          ),
        ),
        const SizedBox(height: 20),

        const Text(
          'त्वरित वैदिक नियम टेम्प्लेट्स (1-टैप में जोड़ें):',
          style: TextStyle(fontWeight: FontWeight.w800, fontSize: 14),
        ),
        const SizedBox(height: 8),
        _quickRuleTile('मांगलिक दोष नियम', 'सप्तमेश व मंगल का विश्लेषण कर केवल कुंभ विवाह व उज्जैन मंगल शांति की संस्तुति करें।'),
        _quickRuleTile('कालसर्प योग नियम', 'राहु-केतु के बीच सभी ग्रह होने पर नासिक त्र्यंबकेश्वर अथवा काशी में नारायण नागबलि पूजा का सुझाव दें।'),
        _quickRuleTile('साढ़ेसाती व शनि ढैय्या', 'शनिवार को पीपल वृक्ष पर दीपक, छाया दान एवं हनुमान चालीसा के नियमित पाठ का परामर्श दें।'),
        _quickRuleTile('महामृत्युंजय प्राण रक्षा', 'असाध्य रोग, दुर्घटना भय अथवा आईसीयू संकट में काशी में महामृत्युंजय सवा लाख महाजाप अनुष्ठान बताएं।'),
      ],
    );
  }

  Widget _quickRuleTile(String title, String rule) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      child: ListTile(
        leading: const Icon(Icons.add_circle_outline_rounded, color: Color(0xFF059669)),
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
        subtitle: Text(rule, style: const TextStyle(fontSize: 11.5)),
        trailing: const Icon(Icons.arrow_forward_ios_rounded, size: 14),
        onTap: () {
          final cur = _aiTwinPrompt.text;
          setState(() {
            _aiTwinPrompt.text = cur.isEmpty ? rule : '$cur\n\n• $rule';
          });
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('✓ $title नियम ज्ञान कोष में जोड़ा गया'), duration: const Duration(seconds: 1)),
          );
        },
      ),
    );
  }

  // 3. Q&A Training Pairs Tab
  Widget _buildQaTrainingTab(bool dark, Color surface, Color border) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'संवाद प्रशिक्षण (Q&A Pairs)',
                  style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15),
                ),
                Text(
                  'सिखाएं कि विशिष्ट प्रश्नों पर AI ट्विन को क्या उत्तर देना चाहिए',
                  style: TextStyle(fontSize: 11.5, color: Colors.grey),
                ),
              ],
            ),
            FilledButton.icon(
              onPressed: _showAddQaDialog,
              icon: const Icon(Icons.add_rounded, size: 16),
              label: const Text('नया Q&A'),
              style: FilledButton.styleFrom(backgroundColor: const Color(0xFF059669)),
            ),
          ],
        ),
        const SizedBox(height: 14),

        for (int i = 0; i < _trainingQaPairs.length; i++) ...[
          Container(
            margin: const EdgeInsets.only(bottom: 12),
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: surface,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: border),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: PartnerTheme.saffron.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: const Text('प्र:', style: TextStyle(fontWeight: FontWeight.w900, color: PartnerTheme.saffron, fontSize: 12)),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        _trainingQaPairs[i]['question'] ?? '',
                        style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13.5),
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.delete_outline_rounded, size: 18, color: Colors.red),
                      onPressed: () {
                        setState(() => _trainingQaPairs.removeAt(i));
                      },
                    ),
                  ],
                ),
                const SizedBox(height: 6),
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: const Color(0xFF059669).withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: const Text('उ:', style: TextStyle(fontWeight: FontWeight.w900, color: Color(0xFF059669), fontSize: 12)),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        _trainingQaPairs[i]['answer'] ?? '',
                        style: const TextStyle(fontSize: 12.5, height: 1.35),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ],
    );
  }

  void _showAddQaDialog() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) => Padding(
        padding: EdgeInsets.only(
          bottom: MediaQuery.of(ctx).viewInsets.bottom + 20,
          left: 16,
          right: 16,
          top: 20,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('नया संवाद प्रशिक्षण जोड़ें (Add Q&A Training)', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 16)),
            const SizedBox(height: 12),
            TextFormField(
              controller: _newQuestionCtrl,
              decoration: InputDecoration(
                labelText: 'यजमान का संभावित प्रश्न (User Question)',
                hintText: 'उदा. क्या मुझे सरकारी नौकरी मिलेगी?',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
              ),
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _newAnswerCtrl,
              maxLines: 3,
              decoration: InputDecoration(
                labelText: 'आपका अनुमोदित उत्तर (Your Approved Guidance)',
                hintText: 'उदा. सूर्य व दशम भाव की स्थिति देखें, रविवार को आदित्य हृदय स्तोत्र...',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
              ),
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: () {
                  final q = _newQuestionCtrl.text.trim();
                  final a = _newAnswerCtrl.text.trim();
                  if (q.isNotEmpty && a.isNotEmpty) {
                    setState(() {
                      _trainingQaPairs.add({'question': q, 'answer': a});
                    });
                    _newQuestionCtrl.clear();
                    _newAnswerCtrl.clear();
                    Navigator.pop(ctx);
                  }
                },
                style: FilledButton.styleFrom(backgroundColor: const Color(0xFF059669)),
                child: const Text('प्रशिक्षण सूची में जोड़ें (Save Pair)'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // 4. Live Takeover Tab
  Widget _buildLiveTakeoverTab(bool dark, Color surface, Color border) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        // Live Takeover Explainer
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: surface,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: border),
          ),
          child: const Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Icon(Icons.live_tv_rounded, size: 20, color: Color(0xFFEA580C)),
                  SizedBox(width: 8),
                  Text('ह्यूमन-इन-द-लूप लाइव टेकओवर (Live Takeover)', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 15)),
                ],
              ),
              SizedBox(height: 8),
              Text(
                'जब आपका AI ट्विन किसी यजमान से बात कर रहा हो, आप किसी भी क्षण सीधे चैट में प्रवेश कर सकते हैं। यजमान को बिना किसी रुकावट के सीधे आपसे बात करने का अनुभव मिलेगा।',
                style: TextStyle(fontSize: 12, height: 1.4, color: Colors.grey),
              ),
            ],
          ),
        ),
        const SizedBox(height: 16),

        const Text(
          'सक्रिय AI ट्विन सत्र (Active Twin Consultations):',
          style: TextStyle(fontWeight: FontWeight.w800, fontSize: 14),
        ),
        const SizedBox(height: 10),

        if (_activeTwinSessions.isEmpty) ...[
          Container(
            padding: const EdgeInsets.all(30),
            alignment: Alignment.center,
            child: const Column(
              children: [
                Icon(Icons.check_circle_outline_rounded, size: 48, color: Colors.green),
                SizedBox(height: 10),
                Text('वर्तमान में कोई सक्रिय सत्र नहीं है', style: TextStyle(fontWeight: FontWeight.bold)),
                Text('जैसे ही कोई यजमान जुड़ेगा, सत्र यहाँ प्रदर्शित होगा', style: TextStyle(fontSize: 12, color: Colors.grey)),
              ],
            ),
          ),
        ] else ...[
          for (final sess in _activeTwinSessions)
            Container(
              margin: const EdgeInsets.only(bottom: 12),
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: surface,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: const Color(0xFFEA580C).withValues(alpha: 0.4), width: 1.5),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          Container(
                            width: 10,
                            height: 10,
                            decoration: const BoxDecoration(color: Color(0xFF10B981), shape: BoxShape.circle),
                          ),
                          const SizedBox(width: 8),
                          Text(sess['devotee_name']?.toString() ?? '', style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 14)),
                        ],
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF59E0B).withValues(alpha: 0.15),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(sess['duration']?.toString() ?? '', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFFD97706))),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Text('विषय: ${sess['topic']}', style: const TextStyle(fontSize: 12, color: Colors.grey)),
                  const SizedBox(height: 12),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFFEA580C),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        padding: const EdgeInsets.symmetric(vertical: 12),
                      ),
                      icon: const Icon(Icons.touch_app_rounded, size: 18),
                      label: const Text('कंसल्टेशन टेकओवर करें (Take Over Live)'),
                      onPressed: () {
                        HapticFeedback.heavyImpact();
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => ChatSessionScreen(
                              customerId: sess['customerId'] as int,
                              customerName: sess['devotee_name'] as String,
                              sessionId: sess['id'] as String,
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                ],
              ),
            ),
        ],

        const SizedBox(height: 20),

        // Social Media Autopilot Quick Action
        Card(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
          elevation: 0,
          color: const Color(0xFFF59E0B).withValues(alpha: dark ? 0.15 : 0.08),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                const Icon(Icons.auto_graph_rounded, size: 28, color: Color(0xFFF59E0B)),
                const SizedBox(width: 12),
                const Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('सोशल मीडिया पर लाखों यजमानों तक पहुंचें', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 13.5)),
                      Text('AI रील्स व पोस्ट्स द्वारा Facebook, Instagram व YouTube पर स्वतः अपनी पहुंच बढ़ाएं', style: TextStyle(fontSize: 11, color: Colors.grey)),
                    ],
                  ),
                ),
                TextButton(
                  onPressed: () => Navigator.pushNamed(context, AstrologerSocialGrowthScreen.route),
                  child: const Text('खोलें →', style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFFF59E0B))),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}
