import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../services/manager_api_service.dart';
import '../../../theme/manager_theme.dart';

class KundliStudioScreen extends StatefulWidget {
  final Map<String, dynamic>? initialDevotee;

  const KundliStudioScreen({super.key, this.initialDevotee});

  @override
  State<KundliStudioScreen> createState() => _KundliStudioScreenState();
}

class _KundliStudioScreenState extends State<KundliStudioScreen> {
  final ManagerApiService _api = ManagerApiService();

  final TextEditingController _nameCtrl = TextEditingController();
  final TextEditingController _pobCtrl = TextEditingController(text: 'Varanasi, UP');
  final TextEditingController _phoneCtrl = TextEditingController();

  String _gender = 'Male';
  DateTime _dob = DateTime(1995, 10, 15);
  TimeOfDay _tob = const TimeOfDay(hour: 6, minute: 45);
  bool _dispatchWa = true;

  bool _isGenerating = false;
  Map<String, dynamic>? _generatedResult;

  @override
  void initState() {
    super.initState();
    if (widget.initialDevotee != null) {
      _nameCtrl.text = widget.initialDevotee!['name']?.toString() ?? '';
      _phoneCtrl.text = widget.initialDevotee!['phone']?.toString() ?? '';
      final city = widget.initialDevotee!['city']?.toString();
      if (city != null && city.isNotEmpty) {
        _pobCtrl.text = city;
      }
    }
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _pobCtrl.dispose();
    _phoneCtrl.dispose();
    super.dispose();
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _dob,
      firstDate: DateTime(1920),
      lastDate: DateTime.now(),
      builder: (ctx, child) => Theme(
        data: ThemeData.dark().copyWith(
          colorScheme: const ColorScheme.dark(
            primary: ManagerTheme.brandGold,
            surface: ManagerTheme.surfaceDark,
          ),
        ),
        child: child!,
      ),
    );
    if (picked != null) {
      setState(() => _dob = picked);
    }
  }

  Future<void> _pickTime() async {
    final picked = await showTimePicker(
      context: context,
      initialTime: _tob,
      builder: (ctx, child) => Theme(
        data: ThemeData.dark().copyWith(
          colorScheme: const ColorScheme.dark(
            primary: ManagerTheme.brandGold,
            surface: ManagerTheme.surfaceDark,
          ),
        ),
        child: child!,
      ),
    );
    if (picked != null) {
      setState(() => _tob = picked);
    }
  }

  Future<void> _generateKundli() async {
    final name = _nameCtrl.text.trim();
    if (name.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please enter Devotee name')),
      );
      return;
    }

    setState(() {
      _isGenerating = true;
      _generatedResult = null;
    });

    final dobStr = "${_dob.year.toString().padLeft(4, '0')}-${_dob.month.toString().padLeft(2, '0')}-${_dob.day.toString().padLeft(2, '0')}";
    final tobStr = "${_tob.hour.toString().padLeft(2, '0')}:${_tob.minute.toString().padLeft(2, '0')}";

    final res = await _api.generateKundliPdf(
      name: name,
      gender: _gender,
      dob: dobStr,
      tob: tobStr,
      pob: _pobCtrl.text.trim(),
      phone: _phoneCtrl.text.trim(),
      dispatchWhatsapp: _dispatchWa,
    );

    if (mounted) {
      setState(() {
        _isGenerating = false;
        _generatedResult = res;
      });

      if (res != null && res['success'] == true) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('✓ Sacred Vedic Janampatri PDF Generated!'),
            backgroundColor: ManagerTheme.brandEmerald,
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Could not generate PDF. Please try again.'),
            backgroundColor: ManagerTheme.brandRuby,
          ),
        );
      }
    }
  }

  Future<void> _openPdfUrl(String url) async {
    final uri = Uri.parse(url);
    try {
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      }
    } catch (_) {}
  }

  Future<void> _shareOnWhatsApp(String pdfUrl) async {
    final name = _nameCtrl.text.trim();
    final cleanPhone = _phoneCtrl.text.replaceAll(RegExp(r'[^0-9]'), '');
    final msg = "॥ श्री गणेशाय नमः ॥ 🙏\n\nआदरणीय $name जी, आपकी सम्पूर्ण वैदिक जन्मपत्री एवं दोष विवरण रिपोर्ट तैयार है।\n\n• आधिकारिक PDF रिपोर्ट डाउनलोड करें: $pdfUrl\n\nविस्तृत उपाय एवं काशी विश्वनाथ पूजा संकल्प हेतु संपर्क करें: https://onlinepuja.live\n- OnlinePuja.live Varanasi";

    final uri = Uri.parse("https://wa.me/91$cleanPhone?text=${Uri.encodeComponent(msg)}");
    try {
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      }
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: ManagerTheme.bgDark,
      appBar: AppBar(
        title: Row(
          children: const [
            Text('📜', style: TextStyle(fontSize: 20)),
            SizedBox(width: 8),
            Text('Vedic Kundli Engine', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          ],
        ),
        backgroundColor: ManagerTheme.surfaceDark,
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // 1. Header Banner
            _buildHeaderBanner(),
            const SizedBox(height: 18),

            // 2. Devotee Details Input Form
            _buildInputForm(),
            const SizedBox(height: 20),

            // 3. Generate Action Button
            SizedBox(
              width: double.infinity,
              height: 50,
              child: ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: ManagerTheme.brandGold,
                  foregroundColor: Colors.black,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  elevation: 4,
                ),
                icon: _isGenerating
                    ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.black, strokeWidth: 2))
                    : const Icon(Icons.auto_awesome_rounded, size: 20),
                label: Text(
                  _isGenerating ? 'Generating Sanctified PDF...' : '📜 Generate Janampatri PDF & Dispatch',
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                ),
                onPressed: _isGenerating ? null : _generateKundli,
              ),
            ),
            const SizedBox(height: 24),

            // 4. Generated Result Dossier Card
            if (_generatedResult != null) _buildResultCard(_generatedResult!),
          ],
        ),
      ),
    );
  }

  Widget _buildHeaderBanner() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: ManagerTheme.surfaceDark,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.3)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: ManagerTheme.brandGold.withValues(alpha: 0.15),
              shape: BoxShape.circle,
            ),
            child: const Text('🕉️', style: TextStyle(fontSize: 26)),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: const [
                Text(
                  'Automated Vedic Janampatri Engine',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Colors.white),
                ),
                SizedBox(height: 4),
                Text(
                  'Generates gold-embossed Shastric PDF with Lagna, Navamsha, Vimshottari Dasha, Dosha Nirupana & 1-tap WhatsApp dispatch.',
                  style: TextStyle(fontSize: 11, color: Colors.white60, height: 1.35),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInputForm() {
    final dobStr = "${_dob.day.toString().padLeft(2, '0')}/${_dob.month.toString().padLeft(2, '0')}/${_dob.year}";
    final tobStr = "${_tob.hour.toString().padLeft(2, '0')}:${_tob.minute.toString().padLeft(2, '0')} IST";

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: ManagerTheme.surfaceDark,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('DEVOTEE BIRTH DOSSIER', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.white54, letterSpacing: 1.1)),
          const SizedBox(height: 14),

          // Name Field
          TextField(
            controller: _nameCtrl,
            style: const TextStyle(color: Colors.white, fontSize: 14),
            decoration: InputDecoration(
              labelText: 'Devotee Full Name (यजमान का नाम)',
              labelStyle: const TextStyle(color: Colors.white54, fontSize: 12),
              prefixIcon: const Icon(Icons.person_rounded, color: ManagerTheme.brandGold, size: 20),
              filled: true,
              fillColor: Colors.black26,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
            ),
          ),
          const SizedBox(height: 12),

          // Gender Selector
          Row(
            children: [
              const Text('Gender: ', style: TextStyle(fontSize: 12, color: Colors.white70)),
              const SizedBox(width: 8),
              ChoiceChip(
                label: const Text('Male (पुरुष)', style: TextStyle(fontSize: 11)),
                selected: _gender == 'Male',
                selectedColor: ManagerTheme.brandGold,
                labelStyle: TextStyle(color: _gender == 'Male' ? Colors.black : Colors.white70),
                onSelected: (v) { if (v) setState(() => _gender = 'Male'); },
              ),
              const SizedBox(width: 8),
              ChoiceChip(
                label: const Text('Female (महिला)', style: TextStyle(fontSize: 11)),
                selected: _gender == 'Female',
                selectedColor: ManagerTheme.brandGold,
                labelStyle: TextStyle(color: _gender == 'Female' ? Colors.black : Colors.white70),
                onSelected: (v) { if (v) setState(() => _gender = 'Female'); },
              ),
            ],
          ),
          const SizedBox(height: 12),

          // DOB & TOB Pickers
          Row(
            children: [
              Expanded(
                child: InkWell(
                  onTap: _pickDate,
                  borderRadius: BorderRadius.circular(10),
                  child: Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(color: Colors.black26, borderRadius: BorderRadius.circular(10)),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Date of Birth', style: TextStyle(fontSize: 10, color: Colors.white54)),
                        const SizedBox(height: 4),
                        Row(
                          children: [
                            const Icon(Icons.calendar_today_rounded, size: 14, color: ManagerTheme.brandGold),
                            const SizedBox(width: 6),
                            Text(dobStr, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Colors.white)),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: InkWell(
                  onTap: _pickTime,
                  borderRadius: BorderRadius.circular(10),
                  child: Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(color: Colors.black26, borderRadius: BorderRadius.circular(10)),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Time of Birth', style: TextStyle(fontSize: 10, color: Colors.white54)),
                        const SizedBox(height: 4),
                        Row(
                          children: [
                            const Icon(Icons.access_time_rounded, size: 14, color: ManagerTheme.brandGold),
                            const SizedBox(width: 6),
                            Text(tobStr, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Colors.white)),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),

          // Place of Birth
          TextField(
            controller: _pobCtrl,
            style: const TextStyle(color: Colors.white, fontSize: 14),
            decoration: InputDecoration(
              labelText: 'Place of Birth (जन्म स्थान)',
              labelStyle: const TextStyle(color: Colors.white54, fontSize: 12),
              prefixIcon: const Icon(Icons.location_on_rounded, color: ManagerTheme.brandGold, size: 20),
              filled: true,
              fillColor: Colors.black26,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
            ),
          ),
          const SizedBox(height: 12),

          // Phone Number
          TextField(
            controller: _phoneCtrl,
            keyboardType: TextInputType.phone,
            style: const TextStyle(color: Colors.white, fontSize: 14),
            decoration: InputDecoration(
              labelText: 'Devotee WhatsApp Number (+91)',
              labelStyle: const TextStyle(color: Colors.white54, fontSize: 12),
              prefixIcon: const Icon(Icons.phone_iphone_rounded, color: Color(0xFF25D366), size: 20),
              filled: true,
              fillColor: Colors.black26,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
            ),
          ),
          const SizedBox(height: 8),

          // WhatsApp dispatch switch
          SwitchListTile(
            contentPadding: EdgeInsets.zero,
            activeColor: const Color(0xFF25D366),
            title: const Text('Dispatch PDF directly to WhatsApp', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white)),
            subtitle: const Text('Evolution API v2 socket document transmission', style: TextStyle(fontSize: 10, color: Colors.white54)),
            value: _dispatchWa,
            onChanged: (v) => setState(() => _dispatchWa = v),
          ),
        ],
      ),
    );
  }

  Widget _buildResultCard(Map<String, dynamic> result) {
    final pdfUrl = result['pdf_url']?.toString() ?? '';
    final data = result['data'] as Map<String, dynamic>? ?? {};
    final doshas = data['doshas'] as Map<String, dynamic>? ?? {};
    final rashi = data['rashi'] ?? 'Mesha';
    final nakshatra = data['nakshatra'] ?? 'Rohini';
    final asc = data['ascendant'] ?? 'Scorpio';
    final sunSign = data['sun_sign'] ?? 'Sagittarius';

    final wa = result['whatsapp'] as Map<String, dynamic>? ?? {};
    final waSent = wa['sent'] == true;

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: ManagerTheme.surfaceDark,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: ManagerTheme.brandGold, width: 1.5),
        boxShadow: [
          BoxShadow(
            color: ManagerTheme.brandGold.withValues(alpha: 0.15),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: const [
                  Text('📜', style: TextStyle(fontSize: 22)),
                  SizedBox(width: 8),
                  Text('Janampatri Ready', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: ManagerTheme.brandGold)),
                ],
              ),
              if (waSent)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: ManagerTheme.brandEmerald.withValues(alpha: 0.2),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: ManagerTheme.brandEmerald),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: const [
                      Icon(Icons.check_circle_rounded, size: 12, color: ManagerTheme.brandEmerald),
                      SizedBox(width: 4),
                      Text('WhatsApp Sent', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: ManagerTheme.brandEmerald)),
                    ],
                  ),
                ),
            ],
          ),
          const Divider(color: Colors.white12, height: 24),

          // Planetary summary
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _buildPill('लग्न: $asc', ManagerTheme.brandGold),
              _buildPill('राशि: $rashi', Colors.cyanAccent),
              _buildPill('नक्षत्र: $nakshatra', Colors.purpleAccent),
              _buildPill('सूर्य: $sunSign', Colors.orangeAccent),
            ],
          ),
          const SizedBox(height: 14),

          // Dosha summary chips
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(color: Colors.black38, borderRadius: BorderRadius.circular(10)),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('DOSHA NIRUPANA SUMMARY', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.white54)),
                const SizedBox(height: 6),
                Text('• मांगलिक दोष: ${doshas['Mangal Dosha'] ?? 'Not Present'}', style: const TextStyle(fontSize: 11, color: Colors.white70)),
                Text('• कालसर्प दोष: ${doshas['Kaalsarp Dosha'] ?? 'None'}', style: const TextStyle(fontSize: 11, color: Colors.white70)),
                Text('• साढ़े साती: ${doshas['Saturn Sade Sati'] ?? 'Not Active'}', style: const TextStyle(fontSize: 11, color: Colors.white70)),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // Action Buttons
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  style: OutlinedButton.styleFrom(
                    foregroundColor: ManagerTheme.brandGold,
                    side: const BorderSide(color: ManagerTheme.brandGold),
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  icon: const Icon(Icons.picture_as_pdf_rounded, size: 16),
                  label: const Text('View PDF', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                  onPressed: () => _openPdfUrl(pdfUrl),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF25D366),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  icon: const Icon(Icons.send_rounded, size: 16),
                  label: const Text('Share WhatsApp', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                  onPressed: () => _shareOnWhatsApp(pdfUrl),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildPill(String label, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: color.withValues(alpha: 0.4)),
      ),
      child: Text(label, style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: color)),
    );
  }
}
