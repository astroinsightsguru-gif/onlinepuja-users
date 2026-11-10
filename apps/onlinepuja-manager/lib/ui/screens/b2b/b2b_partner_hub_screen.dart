import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../providers/call_provider.dart';
import '../../../services/manager_api_service.dart';
import '../../../theme/manager_theme.dart';
import '../campaigns/b2b_campaign_dispatcher_screen.dart';

class B2bPartnerHubScreen extends StatefulWidget {
  const B2bPartnerHubScreen({super.key});

  @override
  State<B2bPartnerHubScreen> createState() => _B2bPartnerHubScreenState();
}

class _B2bPartnerHubScreenState extends State<B2bPartnerHubScreen> {
  final ManagerApiService _api = ManagerApiService();

  bool _isLoading = true;
  int _totalLeads = 0;
  List<Map<String, dynamic>> _leads = [];
  List<Map<String, dynamic>> _cities = [];
  List<Map<String, dynamic>> _categories = [];

  String _selectedCity = 'all';
  String _selectedCategory = 'all';
  String _searchQuery = '';
  final TextEditingController _searchCtrl = TextEditingController();
  Timer? _searchDebounce;

  @override
  void initState() {
    super.initState();
    _loadB2bData();
  }

  @override
  void dispose() {
    _searchDebounce?.cancel();
    _searchCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadB2bData() async {
    setState(() => _isLoading = true);
    final data = await _api.fetchB2bLeads(
      city: _selectedCity,
      category: _selectedCategory,
      search: _searchQuery,
    );
    if (mounted) {
      setState(() {
        _totalLeads = (data['total'] as num?)?.toInt() ?? 0;
        _leads = data['leads'] as List<Map<String, dynamic>>? ?? [];
        if (data['cities'] is List && (data['cities'] as List).isNotEmpty) {
          _cities = data['cities'] as List<Map<String, dynamic>>;
        }
        if (data['categories'] is List && (data['categories'] as List).isNotEmpty) {
          _categories = data['categories'] as List<Map<String, dynamic>>;
        }
        _isLoading = false;
      });
    }
  }

  void _onSearchChanged(String query) {
    _searchDebounce?.cancel();
    _searchDebounce = Timer(const Duration(milliseconds: 350), () {
      if (mounted) {
        setState(() => _searchQuery = query);
        _loadB2bData();
      }
    });
  }

  Future<void> _openPartnerWhatsApp(Map<String, dynamic> lead) async {
    final phone = lead['phone']?.toString().replaceAll(RegExp(r'[^0-9]'), '') ?? '';
    final businessName = lead['business_name'] ?? 'Partner';
    final partnerCode = lead['partner_code'] ?? 'B2B-OP';

    final text = 'Pranam $businessName team! 🙏 Greetings from OnlinePuja.live.\n\n'
        'We invite your agency to partner with us for the upcoming Dev Deepawali 2026 (24 Nov) in Varanasi. '
        'Hotels and boats are 100% booked, but you can offer your travelers 108 Deepdaan & Sacred Rudrabhishek performed live at Dashashwamedh & Manikarnika Ghats.\n\n'
        '• Your Margin: 25% Net Profit on every devotee booking\n'
        '• Your Official Tracking Link: https://onlinepuja.live/pujas?ref=$partnerCode\n'
        '• Video Proof & Speed Post Mahaprasad handled entirely by our Varanasi Purohit team.\n\n'
        'Would you like us to share the wholesale rate card?';

    final uri = Uri.parse('https://wa.me/91$phone?text=${Uri.encodeComponent(text)}');
    try {
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      }
    } catch (_) {}
  }

  void _callPartner(Map<String, dynamic> lead) {
    final callProvider = Provider.of<CallProvider>(context, listen: false);
    final phone = lead['phone']?.toString() ?? '';
    final businessName = lead['business_name'] ?? 'B2B Partner';
    final category = lead['category'] ?? 'Travel Agency';

    callProvider.startCall(
      phoneNumber: phone,
      customerName: businessName,
      pujaInterest: 'B2B Wholesale Partnership ($category)',
      gotra: 'Commercial Partner',
    );
  }

  Future<void> _previewAndSharePitch(Map<String, dynamic> lead) async {
    final leadId = (lead['id'] as num?)?.toInt() ?? int.tryParse(lead['id']?.toString() ?? '');
    final pitchData = await _api.fetchB2bPitch(leadId: leadId);

    final partnerName = lead['business_name'] ?? 'Partner';
    final partnerCode = lead['partner_code'] ?? 'OP-B2B';
    final whatsappCopy = pitchData?['whatsapp'] ??
        "💼 Auspicious Dev Deepawali 2026 Commercial Proposal\n\n"
        "Respected $partnerName,\n\n"
        "Hotels and boats in Varanasi are 100% booked for Dev Deepawali 2026 (24 Nov).\n"
        "Partner with OnlinePuja.live to offer your clients 108 Deepdaan on Manikarnika & Dashashwamedh Ghats.\n\n"
        "• Your Margin: 25% Net Profit (₹300 - ₹600 per booking)\n"
        "• Attribution Link: https://onlinepuja.live/pujas?ref=$partnerCode\n\n"
        "Fulfillment handled entirely by Kashi purohits with HD video proof & speed post Mahaprasad.";

    if (!mounted) return;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: ManagerTheme.surfaceDark,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (ctx) => Padding(
        padding: const EdgeInsets.all(20),
        child: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      const Text('💼', style: TextStyle(fontSize: 22)),
                      const SizedBox(width: 8),
                      Text('Proposal for $partnerName', style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold)),
                    ],
                  ),
                  IconButton(onPressed: () => Navigator.pop(ctx), icon: const Icon(Icons.close)),
                ],
              ),
              const Divider(color: Colors.white12, height: 20),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.black38,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.white12),
                ),
                child: Text(
                  whatsappCopy,
                  style: const TextStyle(fontSize: 12, height: 1.45, color: Colors.white70, fontFamily: 'monospace'),
                ),
              ),
              const SizedBox(height: 16),
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
                      icon: const Icon(Icons.copy_rounded, size: 16),
                      label: const Text('Copy WhatsApp Pitch', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                      onPressed: () {
                        Clipboard.setData(ClipboardData(text: whatsappCopy));
                        Navigator.pop(ctx);
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('✓ Copied partner proposal to clipboard!')),
                        );
                      },
                    ),
                  ),
                  const SizedBox(width: 10),
                  ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF25D366),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    icon: const Icon(Icons.send_rounded, size: 16),
                    label: const Text('Send via WhatsApp', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                    onPressed: () {
                      Navigator.pop(ctx);
                      _openPartnerWhatsApp(lead);
                    },
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _dispatchB2bCampaign() {
    Navigator.push(
      context,
      MaterialPageRoute(builder: (_) => const B2bCampaignDispatcherScreen()),
    ).then((_) => _loadB2bData());
  }

  void _showLeadRadarModal() {
    String selectedRadarCity = 'Varanasi';
    String selectedRadarCategory = 'astrologer';
    int selectedLimit = 20;
    final customQueryCtrl = TextEditingController();
    bool isScanning = false;
    String? scanStatusMessage;

    final predefinedCities = [
      'Varanasi',
      'Haridwar',
      'Ayodhya',
      'Rishikesh',
      'Ujjain',
      'Mathura',
      'Delhi',
      'Mumbai',
      'Jaipur',
      'Kolkata',
      'Bengaluru',
      'Pune',
      'Hyderabad',
      'Ahmedabad',
    ];

    final categories = [
      {'key': 'astrologer', 'label': '🕉️ Astrologers & Jyotish', 'desc': 'Verified Pandits & Kundli experts'},
      {'key': 'travel_agency', 'label': '✈️ Pilgrimage & Travel', 'desc': 'Yatra & tour operators'},
      {'key': 'samagri_store', 'label': '🌺 Puja Samagri Vendors', 'desc': 'Samagri stores & bhandars'},
      {'key': 'hotel', 'label': '🏨 Dharamshalas & Hotels', 'desc': 'Accommodations near holy temples'},
      {'key': 'temple_trust', 'label': '🏛️ Temple Trusts & Samiti', 'desc': 'Mandir management trusts'},
      {'key': 'corporate', 'label': '🎁 Corporate & Traders', 'desc': 'Business councils & HR gifting'},
    ];

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: const Color(0xFF0F172A),
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        side: BorderSide(color: ManagerTheme.brandGold, width: 1.5),
      ),
      builder: (ctx) => StatefulBuilder(
        builder: (modalCtx, setModalState) {
          return Padding(
            padding: EdgeInsets.only(
              left: 20,
              right: 20,
              top: 20,
              bottom: MediaQuery.of(modalCtx).viewInsets.bottom + 20,
            ),
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Row(
                        children: [
                          Icon(Icons.radar_rounded, color: Color(0xFF38BDF8), size: 24),
                          SizedBox(width: 10),
                          Text(
                            'Live Lead Finder Radar',
                            style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: Colors.white),
                          ),
                        ],
                      ),
                      IconButton(
                        icon: const Icon(Icons.close_rounded, color: Colors.white60),
                        onPressed: isScanning ? null : () => Navigator.pop(modalCtx),
                      ),
                    ],
                  ),
                  const Text(
                    'Mine Google Places & Directories for high-intent business leads & verified WhatsApp numbers in seconds.',
                    style: TextStyle(fontSize: 12, color: Colors.white54, height: 1.4),
                  ),
                  const SizedBox(height: 16),
                  
                  // Category selection
                  const Text('TARGET PERSONA', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF38BDF8))),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: categories.map((cat) {
                      final isSelected = selectedRadarCategory == cat['key'];
                      return ChoiceChip(
                        label: Text(cat['label']!),
                        selected: isSelected,
                        selectedColor: const Color(0xFF38BDF8).withValues(alpha: 0.25),
                        backgroundColor: const Color(0xFF1E293B),
                        labelStyle: TextStyle(
                          color: isSelected ? const Color(0xFF38BDF8) : Colors.white70,
                          fontSize: 12,
                          fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                        ),
                        side: BorderSide(color: isSelected ? const Color(0xFF38BDF8) : Colors.white12),
                        onSelected: isScanning ? null : (_) {
                          setModalState(() => selectedRadarCategory = cat['key']!);
                        },
                      );
                    }).toList(),
                  ),
                  const SizedBox(height: 16),

                  // City selection
                  const Text('TARGET INDIAN CITY / CORRIDOR', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: ManagerTheme.brandGold)),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 6,
                    runSpacing: 6,
                    children: predefinedCities.map((city) {
                      final isSelected = selectedRadarCity.toLowerCase() == city.toLowerCase();
                      return ChoiceChip(
                        label: Text(city),
                        selected: isSelected,
                        selectedColor: ManagerTheme.brandGold.withValues(alpha: 0.25),
                        backgroundColor: const Color(0xFF1E293B),
                        labelStyle: TextStyle(
                          color: isSelected ? ManagerTheme.brandGold : Colors.white70,
                          fontSize: 11.5,
                          fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                        ),
                        side: BorderSide(color: isSelected ? ManagerTheme.brandGold : Colors.white10),
                        onSelected: isScanning ? null : (_) {
                          setModalState(() => selectedRadarCity = city);
                        },
                      );
                    }).toList(),
                  ),
                  const SizedBox(height: 16),

                  // Custom Search Query
                  TextField(
                    controller: customQueryCtrl,
                    enabled: !isScanning,
                    style: const TextStyle(fontSize: 12.5, color: Colors.white),
                    decoration: InputDecoration(
                      labelText: 'Custom Search Query (Optional)',
                      hintText: 'e.g. Vedic Purohit Sangh in $selectedRadarCity',
                      prefixIcon: const Icon(Icons.search_rounded, size: 18, color: Colors.white38),
                      filled: true,
                      fillColor: const Color(0xFF1E293B),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Status message
                  if (scanStatusMessage != null)
                    Container(
                      margin: const EdgeInsets.only(bottom: 14),
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: ManagerTheme.brandEmerald.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: ManagerTheme.brandEmerald.withValues(alpha: 0.3)),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.check_circle_rounded, color: ManagerTheme.brandEmerald, size: 18),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              scanStatusMessage!,
                              style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600),
                            ),
                          ),
                        ],
                      ),
                    ),

                  // Action Button
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF38BDF8),
                        foregroundColor: Colors.black,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      icon: isScanning
                          ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.black))
                          : const Icon(Icons.radar_rounded, size: 20),
                      label: Text(
                        isScanning
                            ? 'Scanning Google Maps Radar...'
                            : 'Scan & Discover $selectedRadarCity Leads',
                        style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold),
                      ),
                      onPressed: isScanning
                          ? null
                          : () async {
                              setModalState(() {
                                isScanning = true;
                                scanStatusMessage = null;
                              });

                              final res = await _api.scanB2bRadar(
                                city: selectedRadarCity,
                                category: selectedRadarCategory,
                                limit: selectedLimit,
                                customQuery: customQueryCtrl.text.trim().isNotEmpty ? customQueryCtrl.text.trim() : null,
                              );

                              if (modalCtx.mounted) {
                                setModalState(() {
                                  isScanning = false;
                                  if (res['success'] == true) {
                                    final imported = res['radar']?['imported'] ?? 0;
                                    final totalInCity = res['city_leads'] ?? 0;
                                    scanStatusMessage = '✓ Indexed $imported new leads! Total in $selectedRadarCity: $totalInCity.';
                                  } else {
                                    scanStatusMessage = 'Notice: ${res['error'] ?? 'Radar scan finished.'}';
                                  }
                                });
                              }

                              // Refresh background list
                              _loadB2bData();
                            },
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

  String _formatCategoryLabel(String cat) {
    switch (cat.toLowerCase()) {
      case 'travel_agency':
        return '✈️ Travel Agencies';
      case 'astrologer':
        return '🕉️ Astrologers & Pandits';
      case 'dharamsala':
        return '🏨 Dharamshalas';
      case 'hotel':
        return '🛎️ Hotels';
      case 'corporate_gifting':
        return '🎁 Corporate Gifting';
      case 'event_planner':
        return '🎉 Event Planners';
      case 'samagri_store':
        return '🌺 Samagri Stores';
      default:
        return cat.replaceAll('_', ' ').toUpperCase();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Row(
          children: [
            Text('💼', style: TextStyle(fontSize: 18)),
            SizedBox(width: 8),
            Text('B2B Wholesale Network', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          ],
        ),
        actions: [
          IconButton(
            tooltip: '🔍 Live Google Maps Lead Radar',
            icon: const Icon(Icons.radar_rounded, color: Color(0xFF38BDF8)),
            onPressed: _showLeadRadarModal,
          ),
          IconButton(
            tooltip: '🚀 Anti-Ban Campaign Dispatcher',
            icon: const Icon(Icons.rocket_launch_rounded, color: ManagerTheme.brandGold),
            onPressed: _dispatchB2bCampaign,
          ),
          IconButton(
            tooltip: 'Refresh Network',
            icon: const Icon(Icons.refresh_rounded),
            onPressed: _loadB2bData,
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _loadB2bData,
        child: ListView(
          padding: const EdgeInsets.all(14),
          children: [
            // Top Master Revenue & Wholesale Strip
            _buildMasterHeaderCard(),
            const SizedBox(height: 14),

            // Live Search Bar
            _buildSearchBar(),
            const SizedBox(height: 12),

            // Category Filter Chips Strip
            _buildCategoryFilterStrip(),
            const SizedBox(height: 8),

            // City Filter Chips Strip
            _buildCityFilterStrip(),
            const SizedBox(height: 14),

            // Leads Count Bar
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Showing ${_leads.length} of $_totalLeads Partners',
                  style: const TextStyle(color: Colors.white70, fontSize: 12, fontWeight: FontWeight.bold),
                ),
                if (_isLoading)
                  const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2, color: ManagerTheme.brandGold)),
              ],
            ),
            const SizedBox(height: 10),

            // Leads List View
            if (_leads.isEmpty && !_isLoading)
              Container(
                padding: const EdgeInsets.all(32),
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: ManagerTheme.surfaceDark,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: Colors.white10),
                ),
                child: const Column(
                  children: [
                    Icon(Icons.search_off_rounded, color: Colors.white38, size: 40),
                    SizedBox(height: 10),
                    Text('No B2B partners match this filter.', style: TextStyle(color: Colors.white70, fontWeight: FontWeight.bold)),
                    SizedBox(height: 4),
                    Text('Try switching category or searching another city.', style: TextStyle(color: Colors.white38, fontSize: 11)),
                  ],
                ),
              )
            else
              ..._leads.map((lead) => _buildPartnerCard(lead)),
          ],
        ),
      ),
    );
  }

  Widget _buildMasterHeaderCard() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.3)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'B2B WHOLESALE & PILGRIMAGE NETWORK',
                style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: ManagerTheme.brandGold, letterSpacing: 0.5),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                decoration: BoxDecoration(
                  color: ManagerTheme.brandEmerald.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Text('25% COMMISSION SHARE', style: TextStyle(fontSize: 9.5, fontWeight: FontWeight.bold, color: ManagerTheme.brandEmerald)),
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
                    Text('$_totalLeads', style: const TextStyle(fontSize: 26, fontWeight: FontWeight.w900, color: Colors.white)),
                    const Text('Total Verified Partners', style: TextStyle(fontSize: 11, color: Colors.white54)),
                  ],
                ),
              ),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('₹5,00,000+', style: TextStyle(fontSize: 22, fontWeight: FontWeight.w900, color: Colors.white)),
                    Text('Dev Deepawali Target', style: TextStyle(fontSize: 11, color: Colors.white54)),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Row(
            children: [
              Expanded(
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF38BDF8),
                    foregroundColor: Colors.black,
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  icon: const Icon(Icons.radar_rounded, size: 16),
                  label: const Text('🔍 Lead Radar', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                  onPressed: _showLeadRadarModal,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: ManagerTheme.brandGold,
                    foregroundColor: Colors.black,
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  icon: const Icon(Icons.rocket_launch_rounded, size: 16),
                  label: const Text('🚀 Dispatch WhatsApp', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                  onPressed: _dispatchB2bCampaign,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSearchBar() {
    return Container(
      decoration: BoxDecoration(
        color: ManagerTheme.surfaceDark,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.white10),
      ),
      child: TextField(
        controller: _searchCtrl,
        onChanged: _onSearchChanged,
        style: const TextStyle(color: Colors.white, fontSize: 13),
        decoration: InputDecoration(
          hintText: 'Search agency, phone, city or partner code...',
          hintStyle: const TextStyle(color: Colors.white38, fontSize: 13),
          prefixIcon: const Icon(Icons.search_rounded, color: Colors.white54, size: 20),
          suffixIcon: _searchCtrl.text.isNotEmpty
              ? IconButton(
                  icon: const Icon(Icons.clear, color: Colors.white54, size: 18),
                  onPressed: () {
                    _searchCtrl.clear();
                    _onSearchChanged('');
                  },
                )
              : null,
          border: InputBorder.none,
          contentPadding: const EdgeInsets.symmetric(vertical: 12),
        ),
      ),
    );
  }

  Widget _buildCategoryFilterStrip() {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: [
          FilterChip(
            label: Text('All Categories ($_totalLeads)'),
            selected: _selectedCategory == 'all',
            selectedColor: ManagerTheme.brandGold,
            labelStyle: TextStyle(
              color: _selectedCategory == 'all' ? Colors.black : Colors.white70,
              fontSize: 11,
              fontWeight: FontWeight.bold,
            ),
            onSelected: (val) {
              setState(() => _selectedCategory = 'all');
              _loadB2bData();
            },
          ),
          const SizedBox(width: 8),
          ..._categories.map((c) {
            final cat = c['category']?.toString() ?? '';
            final count = c['total'] ?? 0;
            final isSelected = _selectedCategory == cat;
            return Padding(
              padding: const EdgeInsets.only(right: 8),
              child: FilterChip(
                label: Text('${_formatCategoryLabel(cat)} ($count)'),
                selected: isSelected,
                selectedColor: ManagerTheme.brandGold,
                labelStyle: TextStyle(
                  color: isSelected ? Colors.black : Colors.white70,
                  fontSize: 11,
                  fontWeight: FontWeight.bold,
                ),
                onSelected: (val) {
                  setState(() => _selectedCategory = val ? cat : 'all');
                  _loadB2bData();
                },
              ),
            );
          }),
        ],
      ),
    );
  }

  Widget _buildCityFilterStrip() {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: [
          FilterChip(
            label: const Text('🇮🇳 All Cities'),
            selected: _selectedCity == 'all',
            selectedColor: ManagerTheme.brandGold.withValues(alpha: 0.3),
            labelStyle: TextStyle(
              color: _selectedCity == 'all' ? ManagerTheme.brandGold : Colors.white60,
              fontSize: 10.5,
              fontWeight: FontWeight.bold,
            ),
            onSelected: (val) {
              setState(() => _selectedCity = 'all');
              _loadB2bData();
            },
          ),
          const SizedBox(width: 6),
          ..._cities.map((cityMap) {
            final city = cityMap['city']?.toString() ?? '';
            final count = cityMap['total'] ?? 0;
            final isSelected = _selectedCity == city;
            return Padding(
              padding: const EdgeInsets.only(right: 6),
              child: FilterChip(
                label: Text('$city ($count)'),
                selected: isSelected,
                selectedColor: ManagerTheme.brandGold.withValues(alpha: 0.3),
                labelStyle: TextStyle(
                  color: isSelected ? ManagerTheme.brandGold : Colors.white60,
                  fontSize: 10.5,
                  fontWeight: FontWeight.bold,
                ),
                onSelected: (val) {
                  setState(() => _selectedCity = val ? city : 'all');
                  _loadB2bData();
                },
              ),
            );
          }),
        ],
      ),
    );
  }

  Widget _buildPartnerCard(Map<String, dynamic> lead) {
    final businessName = lead['business_name'] ?? 'B2B Partner';
    final city = lead['city'] ?? 'India';
    final phone = lead['phone'] ?? '';
    final category = lead['category'] ?? 'travel_agency';
    final rating = lead['google_rating'] ?? '4.8';
    final partnerCode = lead['partner_code'] ?? 'B2B-OP';
    final address = lead['address'] ?? '';

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: ManagerTheme.surfaceDark,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Colors.white10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              CircleAvatar(
                radius: 18,
                backgroundColor: ManagerTheme.brandGold.withValues(alpha: 0.15),
                child: const Text('💼', style: TextStyle(fontSize: 16)),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      businessName,
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13.5),
                    ),
                    const SizedBox(height: 2),
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                          decoration: BoxDecoration(
                            color: Colors.white10,
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: Text(
                            city,
                            style: const TextStyle(color: Colors.white70, fontSize: 10, fontWeight: FontWeight.bold),
                          ),
                        ),
                        const SizedBox(width: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                          decoration: BoxDecoration(
                            color: ManagerTheme.brandGold.withValues(alpha: 0.15),
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: Text(
                            category.toString().replaceAll('_', ' ').toUpperCase(),
                            style: const TextStyle(color: ManagerTheme.brandGold, fontSize: 9.5, fontWeight: FontWeight.bold),
                          ),
                        ),
                        const SizedBox(width: 6),
                        Row(
                          children: [
                            const Icon(Icons.star_rounded, color: Colors.amber, size: 13),
                            Text(' $rating', style: const TextStyle(color: Colors.white60, fontSize: 10)),
                          ],
                        ),
                      ],
                    ),
                    if (address.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Text(
                        address,
                        style: const TextStyle(color: Colors.white38, fontSize: 10),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                    decoration: BoxDecoration(
                      color: const Color(0xFF25D366).withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: const Text('25% SHARE', style: TextStyle(color: Color(0xFF25D366), fontSize: 9.5, fontWeight: FontWeight.bold)),
                  ),
                  const SizedBox(height: 3),
                  Text(partnerCode, style: const TextStyle(color: Colors.white38, fontSize: 9, fontFamily: 'monospace')),
                ],
              ),
            ],
          ),
          const SizedBox(height: 10),
          const Divider(color: Colors.white10, height: 1),
          const SizedBox(height: 8),

          // Action Buttons: WhatsApp Pitch, Direct Call, View Pitch
          Row(
            children: [
              Text(
                phone,
                style: const TextStyle(color: Colors.white70, fontSize: 11, fontFamily: 'monospace'),
              ),
              const Spacer(),
              IconButton(
                visualDensity: VisualDensity.compact,
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(),
                tooltip: 'Direct Phone Call',
                icon: const Icon(Icons.phone_rounded, color: Color(0xFF25D366), size: 19),
                onPressed: () => _callPartner(lead),
              ),
              const SizedBox(width: 14),
              ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF25D366),
                  foregroundColor: Colors.white,
                  visualDensity: VisualDensity.compact,
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                ),
                icon: const Icon(Icons.send_rounded, size: 12),
                label: const Text('WhatsApp Pitch', style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold)),
                onPressed: () => _openPartnerWhatsApp(lead),
              ),
              const SizedBox(width: 8),
              OutlinedButton(
                style: OutlinedButton.styleFrom(
                  foregroundColor: Colors.white70,
                  side: const BorderSide(color: Colors.white24),
                  visualDensity: VisualDensity.compact,
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                ),
                child: const Text('Pitch Preview', style: TextStyle(fontSize: 10.5)),
                onPressed: () => _previewAndSharePitch(lead),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
