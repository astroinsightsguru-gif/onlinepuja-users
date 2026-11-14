import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../providers/call_provider.dart';
import '../../../providers/language_provider.dart';
import '../../../services/manager_api_service.dart';
import '../../../theme/manager_theme.dart';
import '../agents/master_ai_assistant_sheet.dart';
import '../kundli/kundli_studio_screen.dart';

class AstrologersHubScreen extends StatefulWidget {
  const AstrologersHubScreen({super.key});

  @override
  State<AstrologersHubScreen> createState() => _AstrologersHubScreenState();
}

class _AstrologersHubScreenState extends State<AstrologersHubScreen> {
  final ManagerApiService _api = ManagerApiService();

  bool _isLoading = true;
  List<Map<String, dynamic>> _astrologers = [];
  String _searchQuery = '';
  String _statusFilter = 'all'; // 'all', 'online', 'offline'
  final TextEditingController _searchCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadAstrologers();
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadAstrologers() async {
    setState(() => _isLoading = true);
    final res = await _api.fetchContacts(type: 'astrologer', search: _searchQuery);
    if (mounted) {
      final contacts = res['contacts'] as List<dynamic>? ?? [];
      setState(() {
        _astrologers = contacts.map((c) => Map<String, dynamic>.from(c as Map)).toList();
        _isLoading = false;
      });
    }
  }

  Future<void> _openAstrologerWhatsApp(Map<String, dynamic> astro) async {
    final phone = (astro['phone'] ?? '').toString().replaceAll(RegExp(r'[^0-9]'), '');
    final name = astro['name'] ?? 'Panditji';

    final text = '🕉️ *ॐ नमः शिवाय* 🙏\n\n$name जी, Har Har Mahadev.\n\n'
        'We have a sacred devotee Sankalp consultation request for Kashi Vishwanath Dham. '
        'Kindly confirm your availability for an upcoming phone/video Sankalp path.';

    final uri = Uri.parse('https://wa.me/91$phone?text=${Uri.encodeComponent(text)}');
    try {
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      }
    } catch (_) {}
  }

  void _callAstrologer(Map<String, dynamic> astro) {
    final callProvider = Provider.of<CallProvider>(context, listen: false);
    final phone = astro['phone'] ?? '';
    final name = astro['name'] ?? 'Panditji';
    final skill = astro['skill'] ?? 'Vedic Astrologer';

    callProvider.startCall(
      phoneNumber: phone,
      customerName: name,
      pujaInterest: 'Pandit Consultation ($skill)',
      gotra: 'Acharya Network',
    );
  }

  @override
  Widget build(BuildContext context) {
    final lang = Provider.of<LanguageProvider>(context);
    final isHindi = lang.isHindi;

    final filtered = _astrologers.where((a) {
      if (_statusFilter == 'online' && a['status'] != 'online') return false;
      if (_statusFilter == 'offline' && a['status'] == 'online') return false;
      return true;
    }).toList();

    final onlineCount = _astrologers.where((a) => a['status'] == 'online').length;

    return Scaffold(
      backgroundColor: ManagerTheme.backgroundDark,
      appBar: AppBar(
        backgroundColor: ManagerTheme.surfaceDark,
        elevation: 0,
        title: Row(
          children: [
            const Text('🕉️', style: TextStyle(fontSize: 20)),
            const SizedBox(width: 8),
            Text(
              isHindi ? 'ज्योतिषी एवं आचार्य मंडल' : 'Astrologers & Acharya Hub',
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.white),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.picture_as_pdf_rounded, color: ManagerTheme.brandGold),
            tooltip: isHindi ? 'वैदिक जन्मपत्री जनरेटर' : 'Vedic Kundli Generator',
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const KundliStudioScreen()),
              );
            },
          ),
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: ManagerTheme.brandGold),
            tooltip: 'Refresh Astrologers',
            onPressed: _loadAstrologers,
          ),
        ],
      ),
      body: RefreshIndicator(
        color: ManagerTheme.brandGold,
        backgroundColor: ManagerTheme.surfaceDark,
        onRefresh: _loadAstrologers,
        child: ListView(
          padding: const EdgeInsets.all(14),
          children: [
            // Top Master Card
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF1E293B), Color(0xFF0F172A)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.3)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        isHindi ? 'पंडित एवं आचार्य नेटवर्क' : 'VEDIC ACHARYA & PANDIT NETWORK',
                        style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: ManagerTheme.brandGold, letterSpacing: 0.5),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                        decoration: BoxDecoration(
                          color: const Color(0xFF25D366).withValues(alpha: 0.15),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          '$onlineCount ${isHindi ? 'उपलब्ध' : 'Online Now'}',
                          style: const TextStyle(color: Color(0xFF25D366), fontSize: 10, fontWeight: FontWeight.bold),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('${_astrologers.length}', style: const TextStyle(fontSize: 26, fontWeight: FontWeight.bold, color: Colors.white)),
                            Text(isHindi ? 'कुल पंजीकृत आचार्य' : 'Verified Astrologers', style: const TextStyle(fontSize: 11, color: Colors.white54)),
                          ],
                        ),
                      ),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Kashi / Ujjain', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white)),
                            Text(isHindi ? 'पवित्र धाम उपस्थिति' : 'Sacred Sanctums', style: const TextStyle(fontSize: 11, color: Colors.white54)),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: ManagerTheme.brandGold,
                        foregroundColor: Colors.black,
                        padding: const EdgeInsets.symmetric(vertical: 10),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      icon: const Icon(Icons.picture_as_pdf_rounded, size: 16),
                      label: Text(
                        isHindi ? '📜 वैदिक जन्मपत्री जनरेटर (WhatsApp डिलीवरी)' : '📜 Generate Vedic Kundli PDF (WhatsApp Delivery)',
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
                      ),
                      onPressed: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(builder: (_) => const KundliStudioScreen()),
                        );
                      },
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 14),

            // Search Bar
            Container(
              decoration: BoxDecoration(
                color: ManagerTheme.surfaceDark,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.white10),
              ),
              child: TextField(
                controller: _searchCtrl,
                style: const TextStyle(color: Colors.white, fontSize: 13),
                decoration: InputDecoration(
                  hintText: isHindi ? 'पंडित जी का नाम या फोन खोजें...' : 'Search pandit name, phone or skill...',
                  hintStyle: const TextStyle(color: Colors.white38, fontSize: 13),
                  prefixIcon: const Icon(Icons.search_rounded, color: Colors.white54, size: 20),
                  suffixIcon: _searchCtrl.text.isNotEmpty
                      ? IconButton(
                          icon: const Icon(Icons.clear, color: Colors.white54, size: 18),
                          onPressed: () {
                            _searchCtrl.clear();
                            setState(() => _searchQuery = '');
                            _loadAstrologers();
                          },
                        )
                      : null,
                  border: InputBorder.none,
                  contentPadding: const EdgeInsets.symmetric(vertical: 12),
                ),
                onSubmitted: (val) {
                  setState(() => _searchQuery = val.trim());
                  _loadAstrologers();
                },
              ),
            ),
            const SizedBox(height: 12),

            // Status Filter Chips
            Row(
              children: [
                FilterChip(
                  label: Text('${isHindi ? 'सभी' : 'All'} (${_astrologers.length})'),
                  selected: _statusFilter == 'all',
                  selectedColor: ManagerTheme.brandGold,
                  labelStyle: TextStyle(
                    color: _statusFilter == 'all' ? Colors.black : Colors.white70,
                    fontSize: 11,
                    fontWeight: FontWeight.bold,
                  ),
                  onSelected: (val) => setState(() => _statusFilter = 'all'),
                ),
                const SizedBox(width: 8),
                FilterChip(
                  label: Text('🟢 ${isHindi ? 'ऑनलाइन' : 'Online'} ($onlineCount)'),
                  selected: _statusFilter == 'online',
                  selectedColor: const Color(0xFF25D366),
                  labelStyle: TextStyle(
                    color: _statusFilter == 'online' ? Colors.white : Colors.white70,
                    fontSize: 11,
                    fontWeight: FontWeight.bold,
                  ),
                  onSelected: (val) => setState(() => _statusFilter = 'online'),
                ),
                const SizedBox(width: 8),
                FilterChip(
                  label: Text('⚪ ${isHindi ? 'ऑफलाइन' : 'Offline'} (${_astrologers.length - onlineCount})'),
                  selected: _statusFilter == 'offline',
                  selectedColor: Colors.white24,
                  labelStyle: TextStyle(
                    color: _statusFilter == 'offline' ? Colors.white : Colors.white70,
                    fontSize: 11,
                    fontWeight: FontWeight.bold,
                  ),
                  onSelected: (val) => setState(() => _statusFilter = 'offline'),
                ),
              ],
            ),
            const SizedBox(height: 14),

            // 24x7 AI Acharya Voice Consultation Hero Card
            _buildAiAcharyaHeroCard(isHindi),
            const SizedBox(height: 12),

            // Astrologer Cards List
            if (_isLoading)
              const Center(
                child: Padding(
                  padding: EdgeInsets.all(32),
                  child: CircularProgressIndicator(color: ManagerTheme.brandGold),
                ),
              )
            else if (filtered.isEmpty)
              Container(
                padding: const EdgeInsets.all(32),
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: ManagerTheme.surfaceDark,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: Colors.white10),
                ),
                child: Text(
                  isHindi ? 'कोई ज्योतिषी नहीं मिला।' : 'No astrologers match this filter.',
                  style: const TextStyle(color: Colors.white54, fontSize: 13),
                ),
              )
            else
              ...filtered.map((astro) => _buildAstrologerCard(astro, isHindi)),
          ],
        ),
      ),
    );
  }

  Widget _buildAiAcharyaHeroCard(bool isHindi) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF2E1065), Color(0xFF1E1B4B), Color(0xFF0F172A)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFA855F7).withValues(alpha: 0.6), width: 1.5),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFFA855F7).withValues(alpha: 0.25),
            blurRadius: 18,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 52,
                height: 52,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  gradient: const RadialGradient(
                    colors: [Color(0xFFF59E0B), Color(0xFF78350F)],
                  ),
                  shape: BoxShape.circle,
                  border: Border.all(color: Colors.amber, width: 2),
                ),
                child: const Text('🕉️', style: TextStyle(fontSize: 26)),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          isHindi ? 'पूज्य आचार्य AI (वैदिक वॉइस)' : 'Pujya Acharya AI (Vedic Voice)',
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.white),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: const Color(0xFF25D366).withValues(alpha: 0.2),
                            borderRadius: BorderRadius.circular(6),
                            border: Border.all(color: const Color(0xFF25D366)),
                          ),
                          child: const Text(
                            '🟢 24x7 Online',
                            style: TextStyle(color: Color(0xFF25D366), fontSize: 10, fontWeight: FontWeight.bold),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      isHindi
                          ? 'सम्पूर्ण कुण्डली, कालसर्प व मांगलिक दोष, करियर व विवाह मार्गदर्शन'
                          : 'Kundli, Kaal Sarp & Manglik Dosha, Career & Muhurat Counsel',
                      style: const TextStyle(fontSize: 11, color: Colors.white70),
                    ),
                    const SizedBox(height: 6),
                    Row(
                      children: const [
                        Icon(Icons.star_rounded, size: 14, color: Colors.amber),
                        SizedBox(width: 4),
                        Text('5.0 (2,450+ Consultations)', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.amber)),
                        SizedBox(width: 8),
                        Text('• LiveKit Audio Bridge', style: TextStyle(fontSize: 10, color: Colors.white38)),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          SizedBox(
            width: double.infinity,
            height: 44,
            child: ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFFA855F7),
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                elevation: 4,
              ),
              icon: const Icon(Icons.mic_rounded, size: 18),
              label: Text(
                isHindi ? '🎙️ बात करें पूज्य आचार्य AI से (लाइव वॉइस)' : '🎙️ Start 24x7 Vedic Voice Consultation',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5),
              ),
              onPressed: () => MasterAiAssistantSheet.show(context),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAstrologerCard(Map<String, dynamic> astro, bool isHindi) {
    final name = astro['name'] ?? 'Panditji';
    final phone = astro['phone'] ?? '';
    final city = astro['city'] ?? 'Varanasi';
    final isOnline = astro['status'] == 'online';
    final rate = astro['rate_per_min'] ?? 20;
    final avatar = astro['avatar'] ?? 'https://onlinepuja.live/images/default-pandit.png';

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: ManagerTheme.surfaceDark,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Avatar with Online dot
              Stack(
                children: [
                  CircleAvatar(
                    radius: 22,
                    backgroundColor: const Color(0xFF1E293B),
                    backgroundImage: NetworkImage(avatar),
                    onBackgroundImageError: (_, __) {},
                    child: avatar.contains('default')
                        ? const Text('🕉️', style: TextStyle(fontSize: 18))
                        : null,
                  ),
                  Positioned(
                    right: 0,
                    bottom: 0,
                    child: Container(
                      width: 11,
                      height: 11,
                      decoration: BoxDecoration(
                        color: isOnline ? const Color(0xFF25D366) : Colors.grey,
                        shape: BoxShape.circle,
                        border: Border.all(color: ManagerTheme.surfaceDark, width: 2),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      name,
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14),
                    ),
                    const SizedBox(height: 3),
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1.5),
                          decoration: BoxDecoration(
                            color: ManagerTheme.brandGold.withValues(alpha: 0.15),
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: Text(
                            city,
                            style: const TextStyle(color: ManagerTheme.brandGold, fontSize: 10, fontWeight: FontWeight.bold),
                          ),
                        ),
                        const SizedBox(width: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1.5),
                          decoration: BoxDecoration(
                            color: Colors.white10,
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: Text(
                            '₹$rate / ${isHindi ? 'मिनट' : 'min'}',
                            style: const TextStyle(color: Colors.white70, fontSize: 10, fontFamily: 'monospace'),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: isOnline
                      ? const Color(0xFF25D366).withValues(alpha: 0.15)
                      : Colors.white10,
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  isOnline ? (isHindi ? 'उपलब्ध' : 'ONLINE') : (isHindi ? 'ऑफलाइन' : 'OFFLINE'),
                  style: TextStyle(
                    color: isOnline ? const Color(0xFF25D366) : Colors.white38,
                    fontSize: 9.5,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          const Divider(color: Colors.white10, height: 1),
          const SizedBox(height: 8),

          // Actions Strip
          Row(
            children: [
              Text(
                phone,
                style: const TextStyle(color: Colors.white60, fontSize: 11, fontFamily: 'monospace'),
              ),
              const Spacer(),
              ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF25D366),
                  foregroundColor: Colors.white,
                  visualDensity: VisualDensity.compact,
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                ),
                icon: const Icon(Icons.chat_rounded, size: 13),
                label: Text(isHindi ? 'व्हाट्सएप' : 'WhatsApp', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                onPressed: () => _openAstrologerWhatsApp(astro),
              ),
              const SizedBox(width: 8),
              ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: ManagerTheme.brandGold,
                  foregroundColor: Colors.black,
                  visualDensity: VisualDensity.compact,
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                ),
                icon: const Icon(Icons.phone_rounded, size: 13),
                label: Text(isHindi ? 'कॉल करें' : 'Call Now', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                onPressed: () => _callAstrologer(astro),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
