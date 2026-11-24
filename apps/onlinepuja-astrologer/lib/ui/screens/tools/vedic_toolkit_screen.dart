import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../theme/partner_theme.dart';
import '../../widgets/partner_widgets.dart';
import '../chat/master_ai_chatbot_screen.dart';

class VedicToolkitScreen extends StatefulWidget {
  static const String route = '/tools/vedic-toolkit';

  const VedicToolkitScreen({super.key});

  @override
  State<VedicToolkitScreen> createState() => _VedicToolkitScreenState();
}

class _VedicToolkitScreenState extends State<VedicToolkitScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  // Kundli Form Controllers
  final _nameController = TextEditingController();
  final _pobController = TextEditingController(text: 'Varanasi');
  DateTime _dob = DateTime(1995, 1, 1);
  TimeOfDay _tob = const TimeOfDay(hour: 10, minute: 30);
  String _gender = 'Male';
  bool _isGeneratingKundli = false;
  String? _generatedPdfUrl;
  Map<String, dynamic>? _quickDoshas;

  // Puja Recommendation Controllers
  final _devoteeNameController = TextEditingController();
  final _devoteePhoneController = TextEditingController();
  String _selectedPuja = 'Maha Mrityunjaya Anushthan';
  String _selectedPujaSlug = 'maha-mrityunjaya-puja';

  final List<Map<String, String>> _popularPujas = [
    {
      'title': 'Maha Mrityunjaya Anushthan (महामृत्युंजय जाप)',
      'slug': 'maha-mrityunjaya-puja',
      'price': '₹5,100',
      'deity': 'Lord Shiva'
    },
    {
      'title': 'Kashi Vishwanath Rudrabhishek (काशी रुद्राभिषेक)',
      'slug': 'rudrabhishek-puja-kashi',
      'price': '₹2,500',
      'deity': 'Lord Shiva'
    },
    {
      'title': 'Kaal Sarp Dosh Nivaran (कालसर्प दोष निवारण)',
      'slug': 'kaal-sarp-dosh-nivaran',
      'price': '₹4,100',
      'deity': 'Rahu-Ketu'
    },
    {
      'title': 'Mangal Dosh Shanti Puja (मंगल दोष शांति)',
      'slug': 'mangal-dosh-shanti-puja',
      'price': '₹3,500',
      'deity': 'Mangal Dev'
    },
    {
      'title': 'Baglamukhi Shatru Vinashak Puja (बगलामुखी अनुष्ठान)',
      'slug': 'maa-baglamukhi-puja',
      'price': '₹7,500',
      'deity': 'Maa Baglamukhi'
    },
    {
      'title': 'Navagraha Shanti Anushthan (नवग्रह शांति)',
      'slug': 'navagraha-shanti-puja',
      'price': '₹3,100',
      'deity': 'Navagraha'
    },
  ];

  // Panchang State
  bool _loadingPanchang = false;
  Map<String, dynamic>? _panchangData;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
    _fetchTodayPanchang();
  }

  @override
  void dispose() {
    _tabController.dispose();
    _nameController.dispose();
    _pobController.dispose();
    _devoteeNameController.dispose();
    _devoteePhoneController.dispose();
    super.dispose();
  }

  Future<void> _fetchTodayPanchang() async {
    setState(() => _loadingPanchang = true);
    try {
      final now = DateTime.now();
      final dateStr = DateFormat('yyyy-MM-dd').format(now);
      final res = await http.get(
        Uri.parse('https://onlinepuja.live/api/panchang/today?date=$dateStr'),
        headers: {'Accept': 'application/json'},
      );

      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        setState(() {
          _panchangData = body['data'] ?? body;
        });
      }
    } catch (_) {
      // Fallback local panchang display
    } finally {
      if (mounted) setState(() => _loadingPanchang = false);
    }
  }

  Future<void> _generateKundliPdf() async {
    final name = _nameController.text.trim();
    if (name.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('कृपया यजमान का नाम दर्ज करें (Enter Devotee Name)')),
      );
      return;
    }

    setState(() {
      _isGeneratingKundli = true;
      _generatedPdfUrl = null;
    });

    try {
      final dobStr = DateFormat('yyyy-MM-dd').format(_dob);
      final tobStr = '${_tob.hour.toString().padLeft(2, '0')}:${_tob.minute.toString().padLeft(2, '0')}';
      final pobStr = _pobController.text.trim().isEmpty ? 'Varanasi' : _pobController.text.trim();

      final res = await http.post(
        Uri.parse('https://onlinepuja.live/api/kundli/generate-free-pdf'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({
          'name': name,
          'dob': dobStr,
          'tob': tobStr,
          'pob': pobStr,
          'gender': _gender,
        }),
      );

      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        if (body['success'] == true && body['pdf_url'] != null) {
          setState(() {
            _generatedPdfUrl = body['pdf_url'];
            _quickDoshas = body['doshas'] as Map<String, dynamic>?;
          });

          if (mounted) {
            _showKundliSuccessDialog(name, body['pdf_url']);
          }
        } else {
          throw Exception(body['message'] ?? 'कुंडली तैयार करने में त्रुटि');
        }
      } else {
        throw Exception('Server returned ${res.statusCode}');
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('त्रुटि: $e')),
        );
      }
    } finally {
      if (mounted) setState(() => _isGeneratingKundli = false);
    }
  }

  void _showKundliSuccessDialog(String name, String pdfUrl) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(
          children: const [
            Icon(Icons.check_circle_rounded, color: PartnerTheme.emerald, size: 26),
            SizedBox(width: 8),
            Text('10-पेज कुंडली तैयार है!', style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('यजमान: $name', style: const TextStyle(fontWeight: FontWeight.w600)),
            const SizedBox(height: 6),
            const Text(
              'संपूर्ण 10 पृष्ठों की वैदिक कुंडली (ग्रह स्थिति, महादशा, कालसर्प, मांगलिक एवं साढ़ेसाती विश्लेषण) तैयार हो चुकी है।',
              style: TextStyle(fontSize: 12.5, height: 1.35),
            ),
            const SizedBox(height: 14),
            Row(
              children: [
                Expanded(
                  child: ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: PartnerTheme.saffron,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    icon: const Icon(Icons.picture_as_pdf_rounded, size: 16),
                    label: const Text('PDF खोलें'),
                    onPressed: () => launchUrl(Uri.parse(pdfUrl), mode: LaunchMode.externalApplication),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: PartnerTheme.emerald,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    icon: const Icon(Icons.share_rounded, size: 16),
                    label: const Text('व्हाट्सएप'),
                    onPressed: () {
                      final text = Uri.encodeComponent(
                        '🕉️ *ॐ नमः शिवाय* 🙏\nप्रिय $name जी, पूज्य पंडित जी द्वारा आपकी 10-पेज संपूर्ण वैदिक कुंडली तैयार कर दी गई है।\n\n📥 अपनी कुंडली PDF डाउनलोड करें:\n$pdfUrl\n\nहर हर महादेव 🚩',
                      );
                      launchUrl(Uri.parse('https://wa.me/?text=$text'), mode: LaunchMode.externalApplication);
                    },
                  ),
                ),
              ],
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('बंद करें (Close)'),
          ),
        ],
      ),
    );
  }

  void _sendPujaRecommendationWhatsApp() {
    final devName = _devoteeNameController.text.trim().isEmpty ? 'यजमान' : _devoteeNameController.text.trim();
    var phone = _devoteePhoneController.text.trim().replaceAll(RegExp(r'[^0-9]'), '');
    if (phone.startsWith('0')) phone = phone.substring(1);
    if (!phone.startsWith('91') && phone.length == 10) phone = '91$phone';

    final pujaUrl = 'https://onlinepuja.live/puja/$_selectedPujaSlug';
    final msg = '🕉️ *ॐ नमः शिवाय* 🙏\n'
        'प्रिय $devName जी,\n\n'
        'पूज्य पंडित जी ने आपकी कुंडली विश्लेषण एवं ग्रह शांति हेतु *$_selectedPuja* की संस्तुति की है।\n\n'
        '🪔 पूजा का विवरण, संकल्प एवं बुकिंग लिंक:\n'
        '$pujaUrl\n\n'
        'हर हर महादेव 🚩';

    final encoded = Uri.encodeComponent(msg);
    final waUrl = phone.isEmpty ? 'https://wa.me/?text=$encoded' : 'https://wa.me/$phone?text=$encoded';

    launchUrl(Uri.parse(waUrl), mode: LaunchMode.externalApplication);
  }

  @override
  Widget build(BuildContext context) {
    final dark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'वैदिक टूलकिट (Vedic Toolkit)',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
        ),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: PartnerTheme.gold,
          indicatorWeight: 3,
          labelColor: PartnerTheme.gold,
          unselectedLabelColor: dark ? Colors.white70 : Colors.black54,
          labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
          isScrollable: true,
          tabs: const [
            Tab(icon: Icon(Icons.auto_stories_rounded), text: 'कुंडली निर्माण'),
            Tab(icon: Icon(Icons.calendar_month_rounded), text: 'आज का पंचांग'),
            Tab(icon: Icon(Icons.temple_hindu_rounded), text: 'पूजा संस्तुति'),
            Tab(icon: Icon(Icons.auto_awesome_rounded), text: 'मास्टर AI ज्योतिषी'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildKundliTab(dark),
          _buildPanchangTab(dark),
          _buildPujaRecommendationTab(dark),
          const MasterAiChatbotScreen(showAppBar: false),
        ],
      ),
    );
  }

  // TAB 1: KUNDLI GENERATOR
  Widget _buildKundliTab(bool dark) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        PartnerCard(
          padding: const EdgeInsets.all(16),
          borderColor: PartnerTheme.gold.withValues(alpha: 0.4),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: const BoxDecoration(
                      gradient: PartnerTheme.saffronGradient,
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.stars_rounded, color: Colors.white, size: 20),
                  ),
                  const SizedBox(width: 10),
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'यजमान कुंडली निर्माण (10-Page PDF)',
                          style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                        ),
                        Text(
                          'कॉल या चैट पर यजमान की तुरंत संपूर्ण कुंडली तैयार करें',
                          style: TextStyle(fontSize: 11, color: Colors.grey),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const Divider(height: 24),
              TextField(
                controller: _nameController,
                decoration: InputDecoration(
                  labelText: 'यजमान का नाम (Devotee Name)*',
                  prefixIcon: const Icon(Icons.person_outline),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                ),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: InkWell(
                      onTap: () async {
                        final picked = await showDatePicker(
                          context: context,
                          initialDate: _dob,
                          firstDate: DateTime(1930),
                          lastDate: DateTime.now(),
                        );
                        if (picked != null) setState(() => _dob = picked);
                      },
                      child: InputDecorator(
                        decoration: InputDecoration(
                          labelText: 'जन्म तिथि (DOB)',
                          prefixIcon: const Icon(Icons.calendar_today_rounded, size: 18),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                        child: Text(DateFormat('dd MMM yyyy').format(_dob)),
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: InkWell(
                      onTap: () async {
                        final picked = await showTimePicker(
                          context: context,
                          initialTime: _tob,
                        );
                        if (picked != null) setState(() => _tob = picked);
                      },
                      child: InputDecorator(
                        decoration: InputDecoration(
                          labelText: 'जन्म समय (TOB)',
                          prefixIcon: const Icon(Icons.access_time_rounded, size: 18),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                        child: Text(_tob.format(context)),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    flex: 2,
                    child: TextField(
                      controller: _pobController,
                      decoration: InputDecoration(
                        labelText: 'जन्म स्थान (POB)',
                        prefixIcon: const Icon(Icons.location_on_outlined, size: 18),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    flex: 1,
                    child: DropdownButtonFormField<String>(
                      initialValue: _gender,
                      decoration: InputDecoration(
                        labelText: 'लिंग',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      items: const [
                        DropdownMenuItem(value: 'Male', child: Text('पुरुष')),
                        DropdownMenuItem(value: 'Female', child: Text('स्त्री')),
                      ],
                      onChanged: (v) => setState(() => _gender = v ?? 'Male'),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 18),
              SizedBox(
                width: double.infinity,
                height: 48,
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: PartnerTheme.saffron,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  icon: _isGeneratingKundli
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                        )
                      : const Icon(Icons.picture_as_pdf_rounded),
                  label: Text(
                    _isGeneratingKundli ? 'कुंडली तैयार हो रही है...' : '10-पेज संपूर्ण कुंडली PDF बनाएं',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                  ),
                  onPressed: _isGeneratingKundli ? null : _generateKundliPdf,
                ),
              ),
              if (_quickDoshas != null) ...[
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: 0.04),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceAround,
                    children: [
                      _buildDoshaBadge('मांगलिक दोष', _quickDoshas!['manglik'] == true),
                      _buildDoshaBadge('कालसर्प योग', _quickDoshas!['kaalsarp'] == true),
                      _buildDoshaBadge('साढ़े साती', _quickDoshas!['sadesati'] == true),
                    ],
                  ),
                ),
              ],
              if (_generatedPdfUrl != null) ...[
                const SizedBox(height: 14),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: PartnerTheme.emerald.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: PartnerTheme.emerald.withValues(alpha: 0.4)),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.check_circle_rounded, color: PartnerTheme.emerald),
                      const SizedBox(width: 8),
                      const Expanded(
                        child: Text(
                          'कुंडली तैयार है! यजमान के साथ साझा करें।',
                          style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
                        ),
                      ),
                      IconButton(
                        icon: const Icon(Icons.open_in_new_rounded, color: PartnerTheme.emerald),
                        onPressed: () => launchUrl(Uri.parse(_generatedPdfUrl!), mode: LaunchMode.externalApplication),
                      ),
                    ],
                  ),
                ),
              ],
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildDoshaBadge(String label, bool isPresent) {
    return Column(
      children: [
        Text(label, style: const TextStyle(fontSize: 10.5, color: Colors.grey)),
        const SizedBox(height: 3),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2.5),
          decoration: BoxDecoration(
            color: isPresent
                ? PartnerTheme.crimson.withValues(alpha: 0.15)
                : PartnerTheme.emerald.withValues(alpha: 0.15),
            borderRadius: BorderRadius.circular(6),
          ),
          child: Text(
            isPresent ? 'दोष उपस्थित' : 'दोष मुक्त',
            style: TextStyle(
              fontSize: 10,
              fontWeight: FontWeight.bold,
              color: isPresent ? PartnerTheme.crimson : PartnerTheme.emerald,
            ),
          ),
        ),
      ],
    );
  }

  // TAB 2: TODAY'S PANCHANG & CHOGHADIYA
  Widget _buildPanchangTab(bool dark) {
    final now = DateTime.now();
    final todayFormatted = DateFormat('EEEE, dd MMMM yyyy').format(now);

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        if (_loadingPanchang)
          const Padding(
            padding: EdgeInsets.only(bottom: 12),
            child: LinearProgressIndicator(color: PartnerTheme.saffron),
          ),
        PartnerCard(
          padding: const EdgeInsets.all(16),
          borderColor: PartnerTheme.gold.withValues(alpha: 0.4),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: const BoxDecoration(
                      gradient: PartnerTheme.goldGradient,
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.sunny, color: Colors.white, size: 20),
                  ),
                  const SizedBox(width: 10),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'आज का दैनिक पंचांग (Varanasi/Kashi)',
                        style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                      ),
                      Text(
                        todayFormatted,
                        style: const TextStyle(fontSize: 11.5, color: Colors.grey),
                      ),
                    ],
                  ),
                ],
              ),
              const Divider(height: 24),
              _buildPanchangRow('तिथि (Tithi)', _panchangData?['tithi']?['name'] ?? 'शुक्ल एकादशी (वारणसी गणन)'),
              _buildPanchangRow('नक्षत्र (Nakshatra)', _panchangData?['nakshatra']?['name'] ?? 'रोहिणी नक्षत्र'),
              _buildPanchangRow('योग (Yoga)', _panchangData?['yoga']?['name'] ?? 'हर्षण योग'),
              _buildPanchangRow('करण (Karana)', _panchangData?['karana']?['name'] ?? 'बव करण'),
              _buildPanchangRow('पक्ष (Paksha)', 'शुक्ल पक्ष (Shukla Paksha)'),
              _buildPanchangRow('सूर्योदय / सूर्यास्त', '06:28 AM / 05:22 PM'),
              const Divider(height: 24),
              const Text(
                'शुभ व अशुभ मुहूर्त (Muhurat Timings)',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5),
              ),
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: PartnerTheme.emerald.withValues(alpha: 0.08),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  children: const [
                    Icon(Icons.check_circle_outline, color: PartnerTheme.emerald, size: 18),
                    SizedBox(width: 8),
                    Text('अभिजीत मुहूर्त: 11:45 AM - 12:30 PM (सर्वोत्तम शुभ)', style: TextStyle(fontSize: 12)),
                  ],
                ),
              ),
              const SizedBox(height: 6),
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: PartnerTheme.crimson.withValues(alpha: 0.08),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  children: const [
                    Icon(Icons.highlight_off_rounded, color: PartnerTheme.crimson, size: 18),
                    SizedBox(width: 8),
                    Text('राहु काल: 04:00 PM - 05:20 PM (अशुभ समय)', style: TextStyle(fontSize: 12)),
                  ],
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildPanchangRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 5),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(fontSize: 12.5, color: Colors.grey)),
          Text(value, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }

  // TAB 3: PUJA RECOMMENDATION
  Widget _buildPujaRecommendationTab(bool dark) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        PartnerCard(
          padding: const EdgeInsets.all(16),
          borderColor: PartnerTheme.gold.withValues(alpha: 0.4),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: const BoxDecoration(
                      gradient: PartnerTheme.saffronGradient,
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.temple_hindu_rounded, color: Colors.white, size: 20),
                  ),
                  const SizedBox(width: 10),
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'यजमान को पूजा संस्तुति भेजें',
                          style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                        ),
                        Text(
                          'परामर्श उपरांत यजमान को 1-क्लिक बुकिंग लिंक भेजें',
                          style: TextStyle(fontSize: 11, color: Colors.grey),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const Divider(height: 24),
              TextField(
                controller: _devoteeNameController,
                decoration: InputDecoration(
                  labelText: 'यजमान का नाम (Devotee Name)',
                  prefixIcon: const Icon(Icons.person_outline),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _devoteePhoneController,
                keyboardType: TextInputType.phone,
                decoration: InputDecoration(
                  labelText: 'यजमान का व्हाट्सएप नंबर (WhatsApp Number)',
                  prefixIcon: const Icon(Icons.phone_outlined),
                  hintText: '9876543210',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                ),
              ),
              const SizedBox(height: 14),
              const Text(
                'अनुशंसित पूजा चुनें (Select Puja):',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
              ),
              const SizedBox(height: 8),
              DropdownButtonFormField<String>(
                initialValue: _selectedPujaSlug,
                decoration: InputDecoration(
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                ),
                items: _popularPujas.map((p) {
                  return DropdownMenuItem(
                    value: p['slug'],
                    child: Text(
                      '${p['title']} (${p['price']})',
                      style: const TextStyle(fontSize: 12),
                      overflow: TextOverflow.ellipsis,
                    ),
                  );
                }).toList(),
                onChanged: (slug) {
                  final found = _popularPujas.firstWhere((p) => p['slug'] == slug);
                  setState(() {
                    _selectedPujaSlug = slug ?? 'maha-mrityunjaya-puja';
                    _selectedPuja = found['title'] ?? '';
                  });
                },
              ),
              const SizedBox(height: 18),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: dark ? Colors.white.withValues(alpha: 0.05) : const Color(0xFFFFF9EE),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: PartnerTheme.gold.withValues(alpha: 0.3)),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('संदेश पूर्वावलोकन (Preview):', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.grey)),
                    const SizedBox(height: 4),
                    Text(
                      '🕉️ *ॐ नमः शिवाय* 🙏\nप्रिय ${_devoteeNameController.text.trim().isEmpty ? "यजमान" : _devoteeNameController.text.trim()} जी,\nपूज्य पंडित जी ने आपकी कुंडली विश्लेषण एवं ग्रह शांति हेतु *$_selectedPuja* की संस्तुति की है।\n\n🪔 पूजा का विवरण एवं बुकिंग लिंक:\nhttps://onlinepuja.live/puja/$_selectedPujaSlug\n\nहर हर महादेव 🚩',
                      style: const TextStyle(fontSize: 11.5, height: 1.35),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 18),
              SizedBox(
                width: double.infinity,
                height: 48,
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF25D366), // WhatsApp Green
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  icon: const Icon(Icons.send_rounded),
                  label: const Text(
                    'व्हाट्सएप पर संस्तुति भेजें (Send on WhatsApp)',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5),
                  ),
                  onPressed: _sendPujaRecommendationWhatsApp,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
