import 'package:flutter/material.dart';
import 'package:op_shared/op_shared.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../state/app_session.dart';
import '../../theme/partner_theme.dart';
import '../../widgets/partner_widgets.dart';
import '../ai_twin/ai_twin_studio_screen.dart';
import '../auth/login_screen.dart';

/// Partner profile: credentials, consultation rates, skills,
/// languages, bio edit, appearance theme, and support.
class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  static const route = '/profile';

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _bio = TextEditingController(
      text:
          'Gold medalist Vedic Astrologer & Vastu consultant with 12+ years of experience helping thousands find clarity in career, marriage, and spiritual path.');
  bool _saving = false;
  bool _initialized = false;

  // AI Twin Studio fields
  bool _aiTwinEnabled = false;
  bool _aiTwinAutoOffline = true;
  String _aiTwinTone = 'empathetic';
  final _aiTwinGreeting = TextEditingController();
  final _aiTwinPrompt = TextEditingController();
  final _aiTwinCharge = TextEditingController(text: '8');
  bool _savingAiTwin = false;
  Map<String, dynamic>? _aiTwinStats;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (!_initialized) {
      final u = context.read<PartnerSession>().user;
      _name.text = u?.name ?? '';
      _email.text = u?.email ?? '';
      _initialized = true;
      _loadAiTwinStatus();
    }
  }

  Future<void> _loadAiTwinStatus() async {
    final u = context.read<PartnerSession>().user;
    if (u?.id == null) return;
    try {
      final data = await AiTwinApi.instance.getAiTwinStatus(astrologerId: u!.id!);
      if (mounted && data.isNotEmpty) {
        setState(() {
          _aiTwinEnabled = (data['ai_twin_enabled'] == 1 || data['ai_twin_enabled'] == true);
          _aiTwinAutoOffline = (data['ai_twin_auto_offline'] == 1 || data['ai_twin_auto_offline'] == true);
          _aiTwinTone = data['ai_twin_tone']?.toString() ?? 'empathetic';
          _aiTwinGreeting.text = data['ai_twin_greeting']?.toString() ?? '';
          _aiTwinPrompt.text = data['ai_twin_prompt']?.toString() ?? '';
          _aiTwinCharge.text = (data['ai_twin_charge'] ?? 8).toString();
          _aiTwinStats = data['stats'] is Map ? Map<String, dynamic>.from(data['stats'] as Map) : null;
        });
      }
    } catch (e) {
      debugPrint('Error loading AI Twin status: $e');
    }
  }

  Future<void> _saveAiTwin() async {
    final u = context.read<PartnerSession>().user;
    if (u?.id == null) return;
    setState(() => _savingAiTwin = true);
    try {
      final chargeVal = double.tryParse(_aiTwinCharge.text.trim()) ?? 8.0;
      await AiTwinApi.instance.setupAiTwin(
        astrologerId: u!.id!,
        enabled: _aiTwinEnabled,
        tone: _aiTwinTone,
        greeting: _aiTwinGreeting.text.trim(),
        prompt: _aiTwinPrompt.text.trim(),
        charge: chargeVal,
        autoOffline: _aiTwinAutoOffline,
      );
      if (mounted) showSnack(context, 'Personal AI Twin Studio saved! 🕉️');
      await _loadAiTwinStatus();
    } catch (e) {
      if (mounted) showSnack(context, 'Failed to save AI Twin: $e', error: true);
    } finally {
      if (mounted) setState(() => _savingAiTwin = false);
    }
  }

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _bio.dispose();
    _aiTwinGreeting.dispose();
    _aiTwinPrompt.dispose();
    _aiTwinCharge.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final session = context.read<PartnerSession>();
    final u = session.user;
    if (u == null || !_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      u.name = _name.text.trim();
      u.email = _email.text.trim();
      await PartnerApi.instance.updateProfile(u);
      if (mounted) showSnack(context, 'Profile updated successfully');
    } on ApiException catch (e) {
      if (mounted) showSnack(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final dark = Theme.of(context).brightness == Brightness.dark;
    final session = context.watch<PartnerSession>();
    final u = session.user;

    return Scaffold(
      appBar: AppBar(
        title: Text(AppStrings.myProfile,
            style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 18)),
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 10, 16, 32),
        children: [
          // Astrologer Hero Badge Card
          PartnerCard(
            gradient: dark
                ? PartnerTheme.darkCardGradient
                : const LinearGradient(
                    colors: [Color(0xFFFFF9F0), Color(0xFFFDEFD9)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
            borderColor: PartnerTheme.gold.withValues(alpha: 0.5),
            padding: const EdgeInsets.all(20),
            child: Column(
              children: [
                AstrologerAvatar(
                  name: u?.displayName ?? 'Acharya',
                  radius: 40,
                  isOnline: session.chatStatus == 'Online',
                ),
                const SizedBox(height: 14),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      u?.displayName ?? 'Acharya Ji',
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                    const SizedBox(width: 6),
                    const Icon(Icons.verified_rounded,
                        color: PartnerTheme.emerald, size: 20),
                  ],
                ),
                const SizedBox(height: 3),
                Text(
                  'Verified Vedic Acharya · ID: #ASTRO-${u?.id ?? 108}',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: dark ? Colors.white60 : Colors.black54,
                  ),
                ),
                const SizedBox(height: 16),

                // Credentials Stat Row
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                  decoration: BoxDecoration(
                    color: dark ? Colors.black26 : Colors.white.withValues(alpha: 0.7),
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(
                      color: PartnerTheme.gold.withValues(alpha: 0.3),
                    ),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceAround,
                    children: [
                      _credItem('4.9 ★', '1,420+ ${AppStrings.reviews}'),
                      Container(
                          width: 1,
                          height: 24,
                          color: Colors.grey.withValues(alpha: 0.3)),
                      _credItem('12+ Yrs', AppStrings.experience),
                      Container(
                          width: 1,
                          height: 24,
                          color: Colors.grey.withValues(alpha: 0.3)),
                      _credItem('8,500+', AppStrings.devoteesServed),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 18),

          // Rates & Pricing Strip Header
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                AppStrings.consultationTariffRates,
                style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800),
              ),
              InkWell(
                borderRadius: BorderRadius.circular(10),
                onTap: () => _showRatesEditor(context, session),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: PartnerTheme.saffron.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(
                      color: PartnerTheme.saffron.withValues(alpha: 0.3),
                    ),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.tune_rounded, size: 14, color: PartnerTheme.saffron),
                      const SizedBox(width: 4),
                      Text(
                        AppStrings.setRates,
                        style: const TextStyle(
                          fontSize: 11.5,
                          fontWeight: FontWeight.w800,
                          color: PartnerTheme.saffron,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              _rateCard(
                'Audio Call',
                '₹${(u?.charge ?? 25).toInt()}/min',
                Icons.call_rounded,
                PartnerTheme.emerald,
                dark,
                onTap: () => _showRatesEditor(context, session),
              ),
              const SizedBox(width: 8),
              _rateCard(
                'Video Call',
                '₹${(u?.videoCallRate ?? 50).toInt()}/min',
                Icons.videocam_rounded,
                PartnerTheme.purple,
                dark,
                onTap: () => _showRatesEditor(context, session),
              ),
              const SizedBox(width: 8),
              _rateCard(
                'Chat Session',
                '₹${(u?.charge ?? 25).toInt()}/min',
                Icons.chat_bubble_rounded,
                PartnerTheme.saffron,
                dark,
                onTap: () => _showRatesEditor(context, session),
              ),
              const SizedBox(width: 8),
              _rateCard(
                'Report',
                '₹${(u?.reportRate ?? 199).toInt()}',
                Icons.description_rounded,
                PartnerTheme.amber,
                dark,
                onTap: () => _showRatesEditor(context, session),
              ),
            ],
          ),
          const SizedBox(height: 18),

          // 🤖 Personal AI Twin Studio (Agentic 24/7 Twin)
          PartnerCard(
            borderColor: _aiTwinEnabled
                ? const Color(0xFF8B5CF6).withValues(alpha: 0.6)
                : PartnerTheme.gold.withValues(alpha: 0.3),
            padding: const EdgeInsets.all(18),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFF7C3AED), Color(0xFFC026D3)],
                        ),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Icon(Icons.auto_awesome, color: Colors.white, size: 20),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              const Text(
                                'Personal AI Twin Studio',
                                style: TextStyle(fontSize: 15, fontWeight: FontWeight.w900),
                              ),
                              const SizedBox(width: 6),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 2),
                                decoration: BoxDecoration(
                                  color: const Color(0xFF7C3AED),
                                  borderRadius: BorderRadius.circular(4),
                                ),
                                child: const Text(
                                  '24/7 AI',
                                  style: TextStyle(fontSize: 9, fontWeight: FontWeight.w900, color: Colors.white),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 2),
                          Text(
                            'Earn 60% revenue while offline & asleep',
                            style: TextStyle(
                              fontSize: 11.5,
                              color: dark ? Colors.white60 : Colors.black54,
                            ),
                          ),
                        ],
                      ),
                    ),
                    Switch(
                      value: _aiTwinEnabled,
                      activeThumbColor: const Color(0xFF8B5CF6),
                      onChanged: (val) {
                        setState(() => _aiTwinEnabled = val);
                      },
                    ),
                  ],
                ),

                if (_aiTwinEnabled) ...[
                  const SizedBox(height: 12),
                  SizedBox(
                    width: double.infinity,
                    child: OutlinedButton.icon(
                      style: OutlinedButton.styleFrom(
                        foregroundColor: const Color(0xFF8B5CF6),
                        side: const BorderSide(color: Color(0xFF8B5CF6)),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      icon: const Icon(Icons.school_rounded, size: 16),
                      label: const Text('विस्तृत AI स्टूडियो खोलें (Open Training Studio) →', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5)),
                      onPressed: () => Navigator.pushNamed(context, AiTwinStudioScreen.route),
                    ),
                  ),
                  const SizedBox(height: 12),
                  const Divider(height: 1),
                  const SizedBox(height: 14),

                  // Analytics / Earnings badge if available
                  if (_aiTwinStats != null)
                    Container(
                      margin: const EdgeInsets.only(bottom: 14),
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                      decoration: BoxDecoration(
                        color: dark ? const Color(0xFF1E1333) : const Color(0xFFF5F3FF),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: const Color(0xFF8B5CF6).withValues(alpha: 0.3),
                        ),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceAround,
                        children: [
                          Column(
                            children: [
                              Text(
                                '${_aiTwinStats!['total_sessions'] ?? 0}',
                                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900),
                              ),
                              const SizedBox(height: 2),
                              const Text(
                                'AI Consultations',
                                style: TextStyle(fontSize: 10.5, color: Colors.grey),
                              ),
                            ],
                          ),
                          Container(width: 1, height: 26, color: Colors.grey.withValues(alpha: 0.3)),
                          Column(
                            children: [
                              Text(
                                '₹${_aiTwinStats!['total_earnings'] ?? 0}',
                                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900, color: PartnerTheme.emerald),
                              ),
                              const SizedBox(height: 2),
                              const Text(
                                'Twin Earnings (60%)',
                                style: TextStyle(fontSize: 10.5, color: Colors.grey),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),

                  // Auto-takeover when offline checkbox
                  Row(
                    children: [
                      Checkbox(
                        value: _aiTwinAutoOffline,
                        activeColor: const Color(0xFF8B5CF6),
                        onChanged: (v) => setState(() => _aiTwinAutoOffline = v ?? true),
                      ),
                      const Expanded(
                        child: Text(
                          'Auto-activate Twin when my status is Offline',
                          style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),

                  // AI Twin Rate
                  TextFormField(
                    controller: _aiTwinCharge,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(
                      labelText: 'AI Twin Chat Rate (₹/min)',
                      prefixIcon: Icon(Icons.currency_rupee_rounded, size: 18),
                      helperText: 'Recommended: ₹5 to ₹15/min (60% credited to your wallet)',
                    ),
                  ),
                  const SizedBox(height: 12),

                  // AI Personality Tone Selector
                  const Text(
                    'Speaking Persona Tone',
                    style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700),
                  ),
                  const SizedBox(height: 6),
                  Wrap(
                    spacing: 8,
                    children: [
                      {'id': 'empathetic', 'label': 'Empathetic & Caring (करुणा)'},
                      {'id': 'reverent', 'label': 'Reverent & Vedic (वैदिक)'},
                      {'id': 'scholarly', 'label': 'Scholarly & Analytical (विद्वान)'},
                      {'id': 'mystic', 'label': 'Mystic & Spiritual (गूढ़)'},
                    ].map((tone) {
                      final selected = _aiTwinTone == tone['id'];
                      return ChoiceChip(
                        label: Text(tone['label']!, style: const TextStyle(fontSize: 11)),
                        selected: selected,
                        selectedColor: const Color(0xFF8B5CF6).withValues(alpha: 0.25),
                        onSelected: (_) => setState(() => _aiTwinTone = tone['id']!),
                      );
                    }).toList(),
                  ),
                  const SizedBox(height: 12),

                  // Custom Greeting
                  TextFormField(
                    controller: _aiTwinGreeting,
                    decoration: const InputDecoration(
                      labelText: 'Personal First Greeting',
                      hintText: 'e.g. ॐ नमो नारायण। I am Acharya Ji\'s AI Twin...',
                      prefixIcon: Icon(Icons.waving_hand_rounded, size: 18),
                    ),
                  ),
                  const SizedBox(height: 12),

                  // Custom System Prompt / Philosophy
                  TextFormField(
                    controller: _aiTwinPrompt,
                    maxLines: 2,
                    decoration: const InputDecoration(
                      labelText: 'Custom Knowledge / Guiding Philosophy',
                      hintText: 'e.g. Always emphasize Satvik lifestyle and Hanuman Chalisa remedies...',
                      prefixIcon: Icon(Icons.psychology_rounded, size: 18),
                    ),
                  ),
                  const SizedBox(height: 14),

                  // Save Button
                  SizedBox(
                    width: double.infinity,
                    height: 44,
                    child: ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF7C3AED),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      onPressed: _savingAiTwin ? null : _saveAiTwin,
                      icon: _savingAiTwin
                          ? const SizedBox(
                              width: 16,
                              height: 16,
                              child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                            )
                          : const Icon(Icons.check_circle_outline_rounded, size: 18),
                      label: Text(
                        _savingAiTwin ? 'Saving Studio...' : 'Save AI Twin Settings',
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(height: 18),

          // Expertise & Specialization Tags
          PartnerCard(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Expertise & Skills',
                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 10),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    'Vedic Astrology',
                    'Kundli Milan',
                    'Vastu Shastra',
                    'Numerology',
                    'Prashna Kundli',
                    'Palmistry',
                    'Gemology',
                  ].map((s) {
                    return Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 10, vertical: 5),
                      decoration: BoxDecoration(
                        color: PartnerTheme.saffron.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(
                          color: PartnerTheme.saffron.withValues(alpha: 0.3),
                        ),
                      ),
                      child: Text(
                        s,
                        style: const TextStyle(
                          fontSize: 11.5,
                          fontWeight: FontWeight.w700,
                          color: PartnerTheme.saffron,
                        ),
                      ),
                    );
                  }).toList(),
                ),
                const SizedBox(height: 14),
                Text(
                  AppStrings.spokenLanguages,
                  style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 10),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: ['Hindi', 'English', 'Sanskrit', 'Gujarati'].map((l) {
                    return Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 10, vertical: 5),
                      decoration: BoxDecoration(
                        color: PartnerTheme.gold.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(
                          color: PartnerTheme.gold.withValues(alpha: 0.3),
                        ),
                      ),
                      child: Text(
                        l,
                        style: const TextStyle(
                          fontSize: 11.5,
                          fontWeight: FontWeight.w700,
                          color: PartnerTheme.goldDark,
                        ),
                      ),
                    );
                  }).toList(),
                ),
              ],
            ),
          ),
          const SizedBox(height: 18),

          // Edit Profile Form
          PartnerCard(
            padding: const EdgeInsets.all(18),
            child: Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    AppStrings.profileDetailsBio,
                    style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800),
                  ),
                  const SizedBox(height: 14),
                  TextFormField(
                    controller: _name,
                    decoration: InputDecoration(
                      labelText: AppStrings.fullName,
                      prefixIcon: const Icon(Icons.person_outline_rounded),
                    ),
                    validator: (v) => (v == null || v.trim().isEmpty)
                        ? 'Enter your name'
                        : null,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _email,
                    keyboardType: TextInputType.emailAddress,
                    decoration: InputDecoration(
                      labelText: AppStrings.emailAddress,
                      prefixIcon: const Icon(Icons.email_outlined),
                    ),
                    validator: (v) =>
                        (v != null && v.isNotEmpty && !v.contains('@'))
                            ? 'Enter a valid email'
                            : null,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _bio,
                    maxLines: 3,
                    decoration: InputDecoration(
                      labelText: AppStrings.aboutAcharyaBio,
                      prefixIcon: const Icon(Icons.edit_note_rounded),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Container(
                    height: 48,
                    decoration: BoxDecoration(
                      gradient: PartnerTheme.saffronGradient,
                      borderRadius: BorderRadius.circular(14),
                      boxShadow:
                          PartnerTheme.glow(PartnerTheme.saffron, blur: 8),
                    ),
                    child: Material(
                      color: Colors.transparent,
                      child: InkWell(
                        borderRadius: BorderRadius.circular(14),
                        onTap: _saving ? null : _save,
                        child: Center(
                          child: _saving
                              ? const SizedBox(
                                  width: 20,
                                  height: 20,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    valueColor: AlwaysStoppedAnimation<Color>(
                                        Colors.white),
                                  ),
                                )
                              : Text(
                                  AppStrings.saveChanges,
                                  style: const TextStyle(
                                    fontSize: 13.5,
                                    fontWeight: FontWeight.w800,
                                    color: Colors.white,
                                  ),
                                ),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
          // App Language Selector (15 Indian Languages)
          PartnerCard(
            padding: const EdgeInsets.all(16),
            child: ValueListenableBuilder<AppLanguage>(
              valueListenable: LocaleManager.instance.currentLanguage,
              builder: (context, lang, _) {
                return Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: PartnerTheme.saffron.withValues(alpha: 0.14),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Icon(Icons.translate_rounded,
                          color: PartnerTheme.saffron, size: 22),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            AppStrings.appLanguage,
                            style: const TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            '${lang.label} (${lang.englishName}) • 15 Indian Languages',
                            style: TextStyle(
                              fontSize: 11.5,
                              color: dark ? Colors.white60 : Colors.black54,
                            ),
                          ),
                        ],
                      ),
                    ),
                    OutlinedButton(
                      onPressed: () => LanguagePickerSheet.show(context),
                      style: OutlinedButton.styleFrom(
                        visualDensity: VisualDensity.compact,
                        side: BorderSide(
                          color: PartnerTheme.saffron.withValues(alpha: 0.5),
                        ),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
                      ),
                      child: Text(
                        AppStrings.change,
                        style: const TextStyle(
                          color: PartnerTheme.saffron,
                          fontWeight: FontWeight.w700,
                          fontSize: 12,
                        ),
                      ),
                    ),
                  ],
                );
              },
            ),
          ),
          const SizedBox(height: 14),

          // Appearance Theme Switcher
          PartnerCard(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    const Icon(Icons.palette_outlined,
                        size: 20, color: PartnerTheme.saffron),
                    const SizedBox(width: 8),
                    Text(
                      AppStrings.appThemeMode,
                      style:
                          const TextStyle(fontSize: 14, fontWeight: FontWeight.w800),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                SegmentedButton<ThemeMode>(
                  segments: [
                    ButtonSegment(
                      value: ThemeMode.light,
                      icon: const Icon(Icons.light_mode_rounded),
                      label: Text(AppStrings.lightTheme),
                    ),
                    ButtonSegment(
                      value: ThemeMode.dark,
                      icon: const Icon(Icons.dark_mode_rounded),
                      label: Text(AppStrings.darkTheme),
                    ),
                    ButtonSegment(
                      value: ThemeMode.system,
                      icon: const Icon(Icons.brightness_auto_rounded),
                      label: Text(AppStrings.systemTheme),
                    ),
                  ],
                  selected: {session.themeMode},
                  onSelectionChanged: (set) {
                    if (set.isNotEmpty) session.setThemeMode(set.first);
                  },
                ),
              ],
            ),
          ),
          const SizedBox(height: 18),

          // Partner Support Helpline
          PartnerCard(
            padding: const EdgeInsets.all(16),
            borderColor: PartnerTheme.gold.withValues(alpha: 0.3),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: PartnerTheme.gold.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.headset_mic_rounded,
                      color: PartnerTheme.gold, size: 22),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        AppStrings.supportDesk,
                        style: const TextStyle(
                            fontSize: 14, fontWeight: FontWeight.w800),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        AppStrings.supportDeskSubtitle,
                        style: TextStyle(
                          fontSize: 11.5,
                          color: dark ? Colors.white60 : Colors.black54,
                        ),
                      ),
                    ],
                  ),
                ),
                TextButton(
                  onPressed: () => _showSupport(context),
                  child: Text(AppStrings.contactSupport),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // Logout Button
          OutlinedButton.icon(
            onPressed: () => _logout(context),
            icon:
                const Icon(Icons.logout_rounded, color: PartnerTheme.crimson),
            label: Text(
              AppStrings.logout,
              style: const TextStyle(
                color: PartnerTheme.crimson,
                fontWeight: FontWeight.w800,
              ),
            ),
            style: OutlinedButton.styleFrom(
              minimumSize: const Size.fromHeight(50),
              side: BorderSide(
                  color: PartnerTheme.crimson.withValues(alpha: 0.4)),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(14),
              ),
            ),
          ),
          const SizedBox(height: 14),

          // Compliance & Safety Options (Google Play Mandatory)
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              TextButton.icon(
                onPressed: () => _showPrivacyPolicy(context),
                icon: const Icon(Icons.privacy_tip_outlined, size: 16, color: Colors.grey),
                label: Text(
                  AppStrings.privacyPolicy,
                  style: const TextStyle(fontSize: 12, color: Colors.grey, decoration: TextDecoration.underline),
                ),
              ),
              const Text(' • ', style: TextStyle(color: Colors.grey)),
              TextButton.icon(
                onPressed: () => _confirmDeleteAccount(context),
                icon: const Icon(Icons.delete_forever_rounded, size: 16, color: Colors.redAccent),
                label: Text(
                  AppStrings.deleteAccount,
                  style: const TextStyle(fontSize: 12, color: Colors.redAccent, decoration: TextDecoration.underline),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _credItem(String value, String label) {
    return Column(
      children: [
        Text(
          value,
          style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w900),
        ),
        const SizedBox(height: 2),
        Text(
          label,
          style: TextStyle(
            fontSize: 10.5,
            fontWeight: FontWeight.w600,
            color: Theme.of(context).brightness == Brightness.dark
                ? Colors.white60
                : Colors.black54,
          ),
        ),
      ],
    );
  }

  Widget _rateCard(
    String title,
    String rate,
    IconData icon,
    Color color,
    bool dark, {
    VoidCallback? onTap,
  }) {
    return Expanded(
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(14),
          onTap: onTap,
          child: Container(
            padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 4),
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: color.withValues(alpha: 0.3)),
            ),
            child: Column(
              children: [
                Icon(icon, color: color, size: 20),
                const SizedBox(height: 6),
                Text(
                  rate,
                  style: TextStyle(
                    fontSize: 13.5,
                    fontWeight: FontWeight.w900,
                    color: color,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  title,
                  style: TextStyle(
                    fontSize: 9.5,
                    fontWeight: FontWeight.w600,
                    color: dark ? Colors.white60 : Colors.black54,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _showRatesEditor(BuildContext context, PartnerSession session) {
    final dark = Theme.of(context).brightness == Brightness.dark;
    final u = session.user;
    final chatCtrl =
        TextEditingController(text: (u?.charge ?? 25).toInt().toString());
    final audioCtrl =
        TextEditingController(text: (u?.charge ?? 25).toInt().toString());
    final videoCtrl =
        TextEditingController(text: (u?.videoCallRate ?? 50).toInt().toString());
    final reportCtrl =
        TextEditingController(text: (u?.reportRate ?? 199).toInt().toString());
    final emerCtrl = TextEditingController(
        text: (u?.emergencyAudioCharge ?? 70).toInt().toString());
    bool acceptEmergency = u?.emergencyCallStatus ?? false;
    bool saving = false;

    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (sheetCtx) {
        return StatefulBuilder(
          builder: (ctx, setModalState) {
            return Container(
              padding: EdgeInsets.only(
                left: 20,
                right: 20,
                top: 20,
                bottom: MediaQuery.of(sheetCtx).viewInsets.bottom + 24,
              ),
              decoration: BoxDecoration(
                color: dark ? PartnerTheme.darkSurface : Colors.white,
                borderRadius:
                    const BorderRadius.vertical(top: Radius.circular(24)),
                border: Border.all(
                  color:
                      dark ? PartnerTheme.darkBorder : const Color(0xFFE2D9CC),
                ),
              ),
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Center(
                      child: Container(
                        width: 44,
                        height: 4,
                        decoration: BoxDecoration(
                          color: Colors.grey.withValues(alpha: 0.3),
                          borderRadius: BorderRadius.circular(2),
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            gradient: PartnerTheme.luxuryGold,
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: const Icon(Icons.currency_rupee_rounded,
                              size: 18, color: Colors.white),
                        ),
                        const SizedBox(width: 10),
                        const Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Set Consultation Tariffs',
                                style: TextStyle(
                                  fontSize: 17,
                                  fontWeight: FontWeight.w900,
                                ),
                              ),
                              Text(
                                'Manage what devotees pay per minute of consultation',
                                style:
                                    TextStyle(fontSize: 11, color: Colors.grey),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),
                    Row(
                      children: [
                        Expanded(
                          child: _rateInput(
                            'Audio Call Rate',
                            audioCtrl,
                            '₹/min',
                            Icons.call_rounded,
                            PartnerTheme.emerald,
                            dark,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: _rateInput(
                            'Video Call Rate',
                            videoCtrl,
                            '₹/min',
                            Icons.videocam_rounded,
                            PartnerTheme.purple,
                            dark,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: _rateInput(
                            'Chat Session Rate',
                            chatCtrl,
                            '₹/min',
                            Icons.chat_bubble_rounded,
                            PartnerTheme.saffron,
                            dark,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: _rateInput(
                            'Detailed Report Fee',
                            reportCtrl,
                            '₹/order',
                            Icons.description_rounded,
                            PartnerTheme.amber,
                            dark,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 14, vertical: 8),
                      decoration: BoxDecoration(
                        color:
                            dark ? PartnerTheme.darkBg : const Color(0xFFF9F6F0),
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(
                          color: dark
                              ? PartnerTheme.darkBorder
                              : const Color(0xFFE5DDD0),
                        ),
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'Emergency Off-Hours Rate',
                                  style: TextStyle(
                                    fontSize: 12.5,
                                    fontWeight: FontWeight.w800,
                                  ),
                                ),
                                Text(
                                  'Accept urgent sessions when offline at premium tariff',
                                  style: TextStyle(
                                    fontSize: 10.5,
                                    color:
                                        dark ? Colors.white60 : Colors.black54,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          Switch(
                            value: acceptEmergency,
                            activeThumbColor: Colors.white,
                            activeTrackColor: PartnerTheme.saffron,
                            onChanged: (v) =>
                                setModalState(() => acceptEmergency = v),
                          ),
                        ],
                      ),
                    ),
                    if (acceptEmergency) ...[
                      const SizedBox(height: 10),
                      _rateInput(
                        'Emergency Surcharge Rate',
                        emerCtrl,
                        '₹/min',
                        Icons.bolt_rounded,
                        PartnerTheme.crimson,
                        dark,
                      ),
                    ],
                    const SizedBox(height: 22),
                    Container(
                      height: 50,
                      decoration: BoxDecoration(
                        gradient: PartnerTheme.saffronGradient,
                        borderRadius: BorderRadius.circular(16),
                        boxShadow:
                            PartnerTheme.glow(PartnerTheme.saffron, blur: 12),
                      ),
                      child: Material(
                        color: Colors.transparent,
                        child: InkWell(
                          borderRadius: BorderRadius.circular(16),
                          onTap: saving
                              ? null
                              : () async {
                                  setModalState(() => saving = true);
                                  try {
                                    final chat = double.tryParse(
                                            chatCtrl.text.trim()) ??
                                        25;
                                    final audio = double.tryParse(
                                            audioCtrl.text.trim()) ??
                                        25;
                                    final video = double.tryParse(
                                            videoCtrl.text.trim()) ??
                                        50;
                                    final report = double.tryParse(
                                            reportCtrl.text.trim()) ??
                                        199;
                                    final emer = double.tryParse(
                                            emerCtrl.text.trim()) ??
                                        70;

                                    await session.updateRates(
                                      charge: chat,
                                      audioCallRate: audio,
                                      videoCallRate: video,
                                      reportRate: report,
                                      emergencyAudioCharge: emer,
                                      emergencyChatCharge: emer,
                                      emergencyVideoCharge: emer * 1.3,
                                      emergencyCallStatus: acceptEmergency,
                                      emergencyChatStatus: acceptEmergency,
                                    );
                                    if (sheetCtx.mounted) {
                                      Navigator.pop(sheetCtx);
                                      showSnack(context,
                                          'Tariff rates updated successfully!');
                                    }
                                  } catch (e) {
                                    if (sheetCtx.mounted) {
                                      showSnack(
                                          context, 'Failed to update rates: $e',
                                          error: true);
                                    }
                                  } finally {
                                    if (sheetCtx.mounted) {
                                      setModalState(() => saving = false);
                                    }
                                  }
                                },
                          child: Center(
                            child: saving
                                ? const SizedBox(
                                    width: 22,
                                    height: 22,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2.2,
                                      valueColor:
                                          AlwaysStoppedAnimation<Color>(
                                              Colors.white),
                                    ),
                                  )
                                : const Text(
                                    'SAVE TARIFF RATES',
                                    style: TextStyle(
                                      color: Colors.white,
                                      fontWeight: FontWeight.w900,
                                      fontSize: 13.5,
                                      letterSpacing: 0.6,
                                    ),
                                  ),
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }

  Widget _rateInput(
    String label,
    TextEditingController ctrl,
    String suffix,
    IconData icon,
    Color color,
    bool dark,
  ) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label.toUpperCase(),
          style: TextStyle(
            fontSize: 10,
            fontWeight: FontWeight.w800,
            letterSpacing: 0.5,
            color: color,
          ),
        ),
        const SizedBox(height: 6),
        TextFormField(
          controller: ctrl,
          keyboardType: TextInputType.number,
          style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15),
          decoration: InputDecoration(
            prefixIcon: Icon(icon, color: color, size: 18),
            suffixText: suffix,
            suffixStyle:
                const TextStyle(fontWeight: FontWeight.w700, fontSize: 12),
            filled: true,
            fillColor: dark ? PartnerTheme.darkBg : const Color(0xFFF7F5F0),
            contentPadding:
                const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(
                color: dark ? PartnerTheme.darkBorder : const Color(0xFFE2D9CC),
              ),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(
                color: dark ? PartnerTheme.darkBorder : const Color(0xFFE2D9CC),
              ),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(color: color, width: 1.6),
            ),
          ),
        ),
      ],
    );
  }

  Future<void> _logout(BuildContext context) async {
    await context.read<PartnerSession>().logout();
    if (context.mounted) {
      Navigator.of(context)
          .pushNamedAndRemoveUntil(LoginScreen.route, (r) => false);
    }
  }

  void _showSupport(BuildContext context) {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => Padding(
        padding: const EdgeInsets.all(22),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              AppStrings.supportDesk,
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 6),
            Text(
              AppStrings.supportDeskSubtitle,
              style: const TextStyle(fontSize: 13, color: Colors.grey),
            ),
            const SizedBox(height: 18),
            ListTile(
              leading: const Icon(Icons.phone_in_talk_rounded, color: Colors.blue),
              title: const Text('Helpline Call'),
              subtitle: const Text('+91 70071 58014 • 24x7 Partner Support'),
              onTap: () {
                Navigator.pop(ctx);
                launchUrl(Uri.parse('tel:+917007158014'));
              },
            ),
            ListTile(
              leading: const Icon(Icons.chat_rounded, color: Colors.green),
              title: const Text('WhatsApp Support'),
              subtitle: const Text('+91 70071 58014 • Instant Astrologer Help'),
              onTap: () {
                Navigator.pop(ctx);
                launchUrl(
                  Uri.parse('https://wa.me/917007158014'),
                  mode: LaunchMode.externalApplication,
                );
              },
            ),
            ListTile(
              leading: const Icon(Icons.email_outlined, color: Colors.orange),
              title: const Text('Astrologer Support Email'),
              subtitle: const Text('astro@onlinepuja.live'),
              onTap: () {
                Navigator.pop(ctx);
                launchUrl(Uri.parse('mailto:astro@onlinepuja.live'));
              },
            ),
          ],
        ),
      ),
    );
  }

  void _showPrivacyPolicy(BuildContext context) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Row(
          children: [
            const Icon(Icons.shield_rounded, color: PartnerTheme.gold),
            const SizedBox(width: 8),
            Text(AppStrings.privacyPolicy, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 18)),
          ],
        ),
        content: const SingleChildScrollView(
          child: Text(
            'OnlinePuja respects and protects astrologer and devotee privacy. '
            'Audio/video consultation streams are encrypted and ephemeral via WebRTC and are never recorded without consent. '
            'Payout and personal details are encrypted and securely processed.\n\n'
            'Full policy is available at:\nhttps://onlinepuja.live/privacy-policy',
            style: TextStyle(fontSize: 13, height: 1.4),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: Text(AppStrings.confirm),
          ),
        ],
      ),
    );
  }

  Future<void> _confirmDeleteAccount(BuildContext context) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Row(
          children: [
            const Icon(Icons.warning_amber_rounded, color: Colors.red),
            const SizedBox(width: 8),
            Text('${AppStrings.deleteAccount}?', style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 18)),
          ],
        ),
        content: const Text(
          'Are you sure you want to request permanent account and data deletion?\n\n'
          'In accordance with data protection policies, all your personal profile details, consultation records, and bank payout credentials will be permanently erased. This action cannot be undone.',
          style: TextStyle(fontSize: 13, height: 1.4),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(AppStrings.cancel),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red, foregroundColor: Colors.white),
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(AppStrings.confirm),
          ),
        ],
      ),
    );

    if (confirmed == true && context.mounted) {
      final messenger = ScaffoldMessenger.of(context);
      final navigator = Navigator.of(context);
      try {
        await context.read<PartnerSession>().deleteAccount();
        messenger.showSnackBar(
          const SnackBar(
            content: Text('Account deletion requested. Your profile data has been scheduled for removal.'),
            backgroundColor: Colors.black87,
          ),
        );
        navigator.pushNamedAndRemoveUntil(LoginScreen.route, (r) => false);
      } catch (e) {
        messenger.showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }
}
