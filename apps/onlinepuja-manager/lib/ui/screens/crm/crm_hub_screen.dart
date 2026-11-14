import 'devotee_profile_detail_sheet.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../../../services/manager_api_service.dart';
import '../../../theme/manager_theme.dart';

class CrmHubScreen extends StatefulWidget {
  final Function(String jid, String name, String phone)? onOpenChat;

  const CrmHubScreen({super.key, this.onOpenChat});

  @override
  State<CrmHubScreen> createState() => _CrmHubScreenState();
}

class _CrmHubScreenState extends State<CrmHubScreen> with SingleTickerProviderStateMixin {
  final ManagerApiService _api = ManagerApiService();
  late TabController _tabController;

  // Contacts state
  bool _isLoadingContacts = true;
  List<Map<String, dynamic>> _contacts = [];
  Map<String, dynamic> _contactSummary = {};
  String _contactFilter = 'all'; // 'all', 'devotee', 'astrologer'
  String _contactSearch = '';

  // Leads state
  bool _isLoadingLeads = true;
  List<Map<String, dynamic>> _leads = [];
  Map<String, dynamic> _leadSummary = {};
  String _selectedStage = 'all';
  String _leadSearch = '';

  static String _formatNum(dynamic value, [int decimals = 0]) {
    if (value == null) return '0';
    if (value is num) return value.toStringAsFixed(decimals);
    final parsed = double.tryParse(value.toString());
    return (parsed ?? 0.0).toStringAsFixed(decimals);
  }

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadContacts();
    _loadLeads();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadContacts() async {
    setState(() => _isLoadingContacts = true);
    final res = await _api.fetchContacts(type: _contactFilter, search: _contactSearch);
    if (mounted) {
      setState(() {
        _contacts = List<Map<String, dynamic>>.from(res['contacts'] ?? []);
        _contactSummary = Map<String, dynamic>.from(res['summary'] ?? {});
        _isLoadingContacts = false;
      });
    }
  }

  Future<void> _loadLeads() async {
    setState(() => _isLoadingLeads = true);
    final res = await _api.fetchLeads(stage: _selectedStage, search: _leadSearch);
    if (mounted) {
      setState(() {
        _leads = List<Map<String, dynamic>>.from(res['leads'] ?? []);
        _leadSummary = Map<String, dynamic>.from(res['summary'] ?? {});
        _isLoadingLeads = false;
      });
    }
  }

  Future<void> _updateLeadStage(int leadId, String newStage) async {
    HapticFeedback.mediumImpact();
    final ok = await _api.updateLeadStage(leadId, newStage);
    if (!mounted) return;
    if (ok) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Lead moved to stage: ${newStage.toUpperCase()}'), duration: const Duration(seconds: 1)),
      );
      _loadLeads();
    }
  }

    Future<void> _assignPanditDialog(Map<String, dynamic> lead) async {
    final leadId = lead['id'] as int;
    final pandits = _contacts.where((c) => c['role'] == 'astrologer').toList();

    if (pandits.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No pandits or astrologers loaded. Please refresh directory.')),
      );
      return;
    }

    int? selectedPanditId = pandits.first['id'] as int?;

    await showDialog(
      context: context,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              backgroundColor: ManagerTheme.surfaceDark,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(16),
                side: const BorderSide(color: ManagerTheme.brandGold, width: 1.5),
              ),
              title: const Row(
                children: [
                  Icon(Icons.person_add_alt_1_rounded, color: ManagerTheme.brandGold),
                  SizedBox(width: 8),
                  Text('Assign Varanasi Pandit', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
                ],
              ),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Select a verified temple pandit for :',
                    style: TextStyle(color: Colors.white.withValues(alpha: 0.7), fontSize: 13),
                  ),
                  const SizedBox(height: 14),
                  DropdownButtonFormField<int>(
                    value: selectedPanditId,
                    dropdownColor: const Color(0xFF13181E),
                    style: const TextStyle(color: Colors.white, fontSize: 13),
                    decoration: InputDecoration(
                      filled: true,
                      fillColor: Colors.black26,
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    items: pandits.map((p) {
                      final pName = p['name']?.toString() ?? 'Vedic Pandit';
                      final pPhone = p['phone']?.toString() ?? '';
                      return DropdownMenuItem<int>(
                        value: p['id'] as int,
                        child: Text('$pName ($pPhone)'),
                      );
                    }).toList(),
                    onChanged: (val) {
                      if (val != null) {
                        setDialogState(() => selectedPanditId = val);
                      }
                    },
                  ),
                ],
              ),
              actions: [
                TextButton(
                  child: const Text('Cancel', style: TextStyle(color: Colors.white60)),
                  onPressed: () => Navigator.pop(ctx),
                ),
                ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: ManagerTheme.brandGold,
                    foregroundColor: Colors.black,
                  ),
                  child: const Text('Confirm Assignment', style: TextStyle(fontWeight: FontWeight.bold)),
                  onPressed: () async {
                    if (selectedPanditId != null) {
                      Navigator.pop(ctx);
                      final ok = await _api.assignPanditToLead(leadId, selectedPanditId!);
                      if (ok && mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text('🚩 Pandit assigned and lead moved to PANDIT ASSIGNED!'),
                            backgroundColor: Colors.green,
                          ),
                        );
                        _loadLeads();
                      }
                    }
                  },
                ),
              ],
            );
          },
        );
      },
    );
  }

  Future<void> _addNoteDialog(Map<String, dynamic> lead) async {
    final noteController = TextEditingController();
    await showDialog(
      context: context,
      builder: (ctx) {
        return AlertDialog(
          backgroundColor: ManagerTheme.surfaceDark,
          title: Text('Add Note for ${lead['name']}', style: const TextStyle(color: Colors.white, fontSize: 16)),
          content: TextField(
            controller: noteController,
            maxLines: 3,
            decoration: const InputDecoration(
              hintText: 'Enter call summary, requested puja, or devotee note...',
              hintStyle: TextStyle(color: Colors.white38, fontSize: 13),
              filled: true,
              fillColor: ManagerTheme.backgroundDark,
            ),
            style: const TextStyle(color: Colors.white, fontSize: 13),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Cancel', style: TextStyle(color: Colors.white54)),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(backgroundColor: ManagerTheme.brandGold),
              onPressed: () async {
                final note = noteController.text.trim();
                if (note.isNotEmpty) {
                  Navigator.pop(ctx);
                  final ok = await _api.addLeadNote(lead['id'], note);
                  if (!mounted) return;
                  if (ok) {
                    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Note added.')));
                    _loadLeads();
                  }
                }
              },
              child: const Text('Save Note', style: TextStyle(color: Colors.black, fontWeight: FontWeight.bold)),
            ),
          ],
        );
      },
    );
  }

  Future<void> _showAddLeadDialog() async {
    final nameCtrl = TextEditingController();
    final phoneCtrl = TextEditingController();
    final pujaCtrl = TextEditingController();
    final dealCtrl = TextEditingController(text: '5100');
    final gotraCtrl = TextEditingController();
    final cityCtrl = TextEditingController();
    String priority = 'hot';

    await showDialog(
      context: context,
      builder: (ctx) {
        return StatefulBuilder(builder: (ctx, setDialogState) {
          return AlertDialog(
            backgroundColor: ManagerTheme.surfaceDark,
            title: const Text('Capture Inbound Devotee Lead', style: TextStyle(color: Colors.white, fontSize: 16)),
            content: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  TextField(
                    controller: nameCtrl,
                    decoration: const InputDecoration(labelText: 'Devotee Name *', labelStyle: TextStyle(color: Colors.white70)),
                    style: const TextStyle(color: Colors.white),
                  ),
                  const SizedBox(height: 8),
                  TextField(
                    controller: phoneCtrl,
                    decoration: const InputDecoration(labelText: 'WhatsApp Phone *', labelStyle: TextStyle(color: Colors.white70)),
                    keyboardType: TextInputType.phone,
                    style: const TextStyle(color: Colors.white),
                  ),
                  const SizedBox(height: 8),
                  TextField(
                    controller: pujaCtrl,
                    decoration: const InputDecoration(labelText: 'Interested Puja / Remedy', labelStyle: TextStyle(color: Colors.white70)),
                    style: const TextStyle(color: Colors.white),
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: dealCtrl,
                          decoration: const InputDecoration(labelText: 'Est. Deal Value (₹)', labelStyle: TextStyle(color: Colors.white70)),
                          keyboardType: TextInputType.number,
                          style: const TextStyle(color: Colors.white),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: TextField(
                          controller: gotraCtrl,
                          decoration: const InputDecoration(labelText: 'Gotra', labelStyle: TextStyle(color: Colors.white70)),
                          style: const TextStyle(color: Colors.white),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  TextField(
                    controller: cityCtrl,
                    decoration: const InputDecoration(labelText: 'City / Location', labelStyle: TextStyle(color: Colors.white70)),
                    style: const TextStyle(color: Colors.white),
                  ),
                ],
              ),
            ),
            actions: [
              TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel', style: TextStyle(color: Colors.white54))),
              ElevatedButton(
                style: ElevatedButton.styleFrom(backgroundColor: ManagerTheme.brandGold),
                onPressed: () async {
                  if (nameCtrl.text.trim().isEmpty || phoneCtrl.text.trim().isEmpty) return;
                  Navigator.pop(ctx);
                  final ok = await _api.createLead({
                    'name': nameCtrl.text.trim(),
                    'phone': phoneCtrl.text.trim(),
                    'interested_puja': pujaCtrl.text.trim(),
                    'deal_value': double.tryParse(dealCtrl.text.trim()) ?? 0,
                    'gotra': gotraCtrl.text.trim(),
                    'city': cityCtrl.text.trim(),
                    'priority': priority,
                    'stage': 'new',
                  });
                  if (!mounted) return;
                  if (ok) {
                    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Lead captured successfully!')));
                    _loadLeads();
                  }
                },
                child: const Text('Create Lead', style: TextStyle(color: Colors.black, fontWeight: FontWeight.bold)),
              ),
            ],
          );
        });
      },
    );
  }

  void _showContactDetail(Map<String, dynamic> contact) {
    DevoteeProfileDetailSheet.show(
      context,
      contact,
      onOpenChat: widget.onOpenChat,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: ManagerTheme.backgroundDark,
      appBar: AppBar(
        title: const Text('CRM & Lead Management'),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: ManagerTheme.brandGold,
          labelColor: ManagerTheme.brandGold,
          unselectedLabelColor: Colors.white60,
          tabs: const [
            Tab(icon: Icon(Icons.contacts_rounded, size: 20), text: 'Contacts Directory'),
            Tab(icon: Icon(Icons.view_kanban_rounded, size: 20), text: 'Lead Pipeline'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildContactsDirectoryTab(),
          _buildLeadPipelineTab(),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: ManagerTheme.brandGold,
        foregroundColor: Colors.black,
        icon: const Icon(Icons.person_add_rounded),
        label: const Text('Add Lead', style: TextStyle(fontWeight: FontWeight.bold)),
        onPressed: _showAddLeadDialog,
      ),
    );
  }

  Widget _buildContactsDirectoryTab() {
    return Column(
      children: [
        // Filter bar & Search
        Container(
          padding: const EdgeInsets.all(12),
          color: ManagerTheme.surfaceDark,
          child: Column(
            children: [
              TextField(
                decoration: InputDecoration(
                  hintText: 'Search devotee by name, phone, city, or gotra...',
                  hintStyle: const TextStyle(color: Colors.white38, fontSize: 13),
                  prefixIcon: const Icon(Icons.search, color: Colors.white38, size: 20),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                  filled: true,
                  fillColor: ManagerTheme.surfaceBorder.withValues(alpha: 0.3),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide.none),
                ),
                style: const TextStyle(color: Colors.white, fontSize: 13),
                onChanged: (val) {
                  _contactSearch = val;
                  _loadContacts();
                },
              ),
              const SizedBox(height: 10),
              SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: Row(
                  children: [
                    _buildFilterChip('All Contacts', 'all', _contactSummary['total_devotees'] != null ? (_contactSummary['total_devotees'] + (_contactSummary['total_astrologers'] ?? 0)) : null),
                    const SizedBox(width: 8),
                    _buildFilterChip('Devotees', 'devotee', _contactSummary['total_devotees']),
                    const SizedBox(width: 8),
                    _buildFilterChip('Astrologers', 'astrologer', _contactSummary['total_astrologers']),
                  ],
                ),
              ),
            ],
          ),
        ),

        // Contacts List
        Expanded(
          child: _isLoadingContacts
              ? const Center(child: CircularProgressIndicator(color: ManagerTheme.brandGold))
              : _contacts.isEmpty
                  ? const Center(child: Text('No contacts found matching criteria.', style: TextStyle(color: Colors.white54)))
                  : RefreshIndicator(
                      onRefresh: _loadContacts,
                      color: ManagerTheme.brandGold,
                      child: ListView.separated(
                        padding: const EdgeInsets.all(12),
                        itemCount: _contacts.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 8),
                        itemBuilder: (ctx, i) {
                          final c = _contacts[i];
                          final isDevotee = c['role'] == 'devotee';
                          final hasGotra = c['gotra'] != null && c['gotra'].toString().isNotEmpty;

                          return InkWell(
                            onTap: () => _showContactDetail(c),
                            borderRadius: BorderRadius.circular(10),
                            child: Container(
                              padding: const EdgeInsets.all(14),
                              decoration: BoxDecoration(
                                color: ManagerTheme.surfaceDark,
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(color: ManagerTheme.surfaceBorder),
                              ),
                              child: Row(
                                children: [
                                  CircleAvatar(
                                    radius: 22,
                                    backgroundColor: isDevotee
                                        ? Colors.blue.withValues(alpha: 0.2)
                                        : Colors.orange.withValues(alpha: 0.2),
                                    child: Text(
                                      (c['name']?.toString() ?? 'D')[0].toUpperCase(),
                                      style: TextStyle(
                                        color: isDevotee ? Colors.lightBlueAccent : Colors.orangeAccent,
                                        fontWeight: FontWeight.bold,
                                      ),
                                    ),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Row(
                                          children: [
                                            Expanded(
                                              child: Text(
                                                c['name']?.toString() ?? 'Devotee',
                                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Colors.white),
                                                overflow: TextOverflow.ellipsis,
                                              ),
                                            ),
                                            Container(
                                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                              decoration: BoxDecoration(
                                                color: isDevotee ? Colors.blue.withValues(alpha: 0.15) : Colors.orange.withValues(alpha: 0.15),
                                                borderRadius: BorderRadius.circular(4),
                                              ),
                                              child: Text(
                                                isDevotee ? 'Devotee' : 'Pandit',
                                                style: TextStyle(
                                                  fontSize: 10,
                                                  fontWeight: FontWeight.bold,
                                                  color: isDevotee ? Colors.lightBlueAccent : Colors.orangeAccent,
                                                ),
                                              ),
                                            ),
                                          ],
                                        ),
                                        const SizedBox(height: 4),
                                        Row(
                                          children: [
                                            Text(
                                              '+${c['phone']}',
                                              style: const TextStyle(fontSize: 12, color: Colors.white60),
                                            ),
                                            const SizedBox(width: 8),
                                            Text(
                                              '• ${c['city'] ?? 'India'}',
                                              style: const TextStyle(fontSize: 12, color: Colors.white38),
                                            ),
                                            if (hasGotra) ...[
                                              const SizedBox(width: 8),
                                              Text(
                                                '• Gotra: ${c['gotra']}',
                                                style: const TextStyle(fontSize: 12, color: Colors.amberAccent),
                                              ),
                                            ],
                                          ],
                                        ),
                                      ],
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  IconButton(
                                    icon: const Icon(Icons.chat_bubble_outline_rounded, color: Color(0xFF25D366), size: 20),
                                    tooltip: 'Chat on WhatsApp',
                                    onPressed: () {
                                      if (widget.onOpenChat != null) {
                                        final phone = c['phone']?.toString() ?? '';
                                        final clean = phone.replaceAll(RegExp(r'[^0-9]'), '');
                                        final jid = clean.length == 10 ? '91$clean@s.whatsapp.net' : '$clean@s.whatsapp.net';
                                        widget.onOpenChat!(jid, c['name'] ?? 'Contact', phone);
                                      }
                                    },
                                  ),
                                ],
                              ),
                            ),
                          );
                        },
                      ),
                    ),
        ),
      ],
    );
  }

  Widget _buildFilterChip(String label, String value, int? count) {
    final isSelected = _contactFilter == value;
    return ChoiceChip(
      selected: isSelected,
      label: Text(count != null ? '$label ($count)' : label),
      selectedColor: ManagerTheme.brandGold.withValues(alpha: 0.25),
      backgroundColor: ManagerTheme.surfaceBorder.withValues(alpha: 0.3),
      labelStyle: TextStyle(
        fontSize: 12,
        fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
        color: isSelected ? ManagerTheme.brandGold : Colors.white70,
      ),
      onSelected: (_) {
        setState(() => _contactFilter = value);
        _loadContacts();
      },
    );
  }

  Widget _buildLeadPipelineTab() {
    final stages = ['all', 'new', 'contacted', 'qualified', 'proposal', 'won', 'lost'];

    return Column(
      children: [
        // Pipeline summary card
        Container(
          margin: const EdgeInsets.all(12),
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xFF2E1A47), Color(0xFF1E1430)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.3)),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Active Pipeline Value', style: TextStyle(fontSize: 12, color: Colors.white70)),
                  const SizedBox(height: 4),
                  Text(
                    '₹${_formatNum(_leadSummary['pipeline_value'])}',
                    style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: ManagerTheme.brandGold),
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                decoration: BoxDecoration(
                  color: Colors.black26,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  '${_leads.length} Deals in Flight',
                  style: const TextStyle(color: Colors.white70, fontSize: 12, fontWeight: FontWeight.bold),
                ),
              ),
            ],
          ),
        ),

        // Lead Search Bar
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
          child: TextField(
            onChanged: (val) {
              setState(() => _leadSearch = val);
              _loadLeads();
            },
            decoration: InputDecoration(
              hintText: 'Search leads by name, phone, or puja...',
              hintStyle: const TextStyle(color: Colors.white38, fontSize: 13),
              prefixIcon: const Icon(Icons.search, color: ManagerTheme.brandGold, size: 20),
              suffixIcon: _leadSearch.isNotEmpty
                  ? IconButton(
                      icon: const Icon(Icons.clear, color: Colors.white38, size: 18),
                      onPressed: () {
                        setState(() => _leadSearch = '');
                        _loadLeads();
                      },
                    )
                  : null,
              filled: true,
              fillColor: ManagerTheme.surfaceDark,
              contentPadding: const EdgeInsets.symmetric(vertical: 0, horizontal: 16),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
            ),
            style: const TextStyle(color: Colors.white, fontSize: 13),
          ),
        ),
        const SizedBox(height: 6),

        // Stage selector chips
        SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          padding: const EdgeInsets.symmetric(horizontal: 12),
          child: Row(
            children: stages.map((st) {
              final isSel = _selectedStage == st;
              return Padding(
                padding: const EdgeInsets.only(right: 8),
                child: ChoiceChip(
                  selected: isSel,
                  label: Text(st.toUpperCase()),
                  selectedColor: ManagerTheme.brandGold.withValues(alpha: 0.25),
                  backgroundColor: ManagerTheme.surfaceDark,
                  labelStyle: TextStyle(
                    fontSize: 11,
                    fontWeight: isSel ? FontWeight.bold : FontWeight.normal,
                    color: isSel ? ManagerTheme.brandGold : Colors.white70,
                  ),
                  onSelected: (_) {
                    setState(() => _selectedStage = st);
                    _loadLeads();
                  },
                ),
              );
            }).toList(),
          ),
        ),
        const SizedBox(height: 8),

        // Leads List
        Expanded(
          child: _isLoadingLeads
              ? const Center(child: CircularProgressIndicator(color: ManagerTheme.brandGold))
              : _leads.isEmpty
                  ? const Center(child: Text('No leads found in this stage.', style: TextStyle(color: Colors.white54)))
                  : RefreshIndicator(
                      onRefresh: _loadLeads,
                      color: ManagerTheme.brandGold,
                      child: ListView.separated(
                        padding: const EdgeInsets.all(12),
                        itemCount: _leads.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 10),
                        itemBuilder: (ctx, i) {
                          final l = _leads[i];
                          final stage = l['stage']?.toString() ?? 'new';
                          final isHot = l['priority'] == 'hot';

                          return Container(
                            padding: const EdgeInsets.all(14),
                            decoration: BoxDecoration(
                              color: ManagerTheme.surfaceDark,
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(
                                color: isHot ? Colors.redAccent.withValues(alpha: 0.4) : ManagerTheme.surfaceBorder,
                              ),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Row(
                                      children: [
                                        Text(
                                          l['name']?.toString() ?? 'Lead',
                                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Colors.white),
                                        ),
                                        if (isHot) ...[
                                          const SizedBox(width: 6),
                                          Container(
                                            padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                                            decoration: BoxDecoration(color: Colors.red.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(4)),
                                            child: const Text('🔥 HOT', style: TextStyle(color: Colors.redAccent, fontSize: 9, fontWeight: FontWeight.bold)),
                                          ),
                                        ],
                                      ],
                                    ),
                                    Text(
                                      '₹${_formatNum(l['deal_value'])}',
                                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: ManagerTheme.brandGold),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 4),
                                Row(
                                  children: [
                                    Text('+${l['phone']}', style: const TextStyle(color: Colors.white60, fontSize: 12)),
                                    if (l['city'] != null) Text(' • ${l['city']}', style: const TextStyle(color: Colors.white38, fontSize: 12)),
                                    if (l['gotra'] != null) Text(' • Gotra: ${l['gotra']}', style: const TextStyle(color: Colors.amberAccent, fontSize: 12)),
                                  ],
                                ),
                                if (l['interested_puja'] != null) ...[
                                  const SizedBox(height: 6),
                                  Text(
                                    '🪔 ${l['interested_puja']}',
                                    style: const TextStyle(color: Colors.white70, fontSize: 12, fontStyle: FontStyle.italic),
                                  ),
                                ],
                                if (l['notes'] != null) ...[
                                  const SizedBox(height: 6),
                                  Container(
                                    padding: const EdgeInsets.all(8),
                                    decoration: BoxDecoration(color: Colors.black26, borderRadius: BorderRadius.circular(6)),
                                    child: Text(
                                      l['notes'].toString(),
                                      style: const TextStyle(color: Colors.white54, fontSize: 11),
                                      maxLines: 2,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                ],
                                const SizedBox(height: 10),
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    _buildStageBadge(stage),
                                    Row(
                                      children: [
                                        IconButton(
                                          icon: const Icon(Icons.chat_bubble_outline_rounded, color: Color(0xFF25D366), size: 18),
                                          tooltip: 'WhatsApp Devotee',
                                          onPressed: () {
                                            if (widget.onOpenChat != null) {
                                              final phone = l['phone']?.toString() ?? '';
                                              final clean = phone.replaceAll(RegExp(r'[^0-9]'), '');
                                              final jid = clean.length == 10 ? '91$clean@s.whatsapp.net' : '$clean@s.whatsapp.net';
                                              widget.onOpenChat!(jid, l['name'] ?? 'Lead', phone);
                                            }
                                          },
                                        ),
                                        TextButton.icon(
                                          icon: const Icon(Icons.note_add_rounded, size: 16, color: Colors.white70),
                                          label: const Text('Add Note', style: TextStyle(fontSize: 12, color: Colors.white70)),
                                          onPressed: () => _addNoteDialog(l),
                                        ),
                                        PopupMenuButton<String>(
                                          tooltip: 'Lead Actions',
                                          icon: const Icon(Icons.arrow_forward_rounded, size: 18, color: ManagerTheme.brandGold),
                                          onSelected: (newSt) {
                                            if (newSt == 'assign_pandit') {
                                              _assignPanditDialog(l);
                                            } else {
                                              _updateLeadStage(l['id'], newSt);
                                            }
                                          },
                                          itemBuilder: (ctx) => [
                                            const PopupMenuItem(value: 'assign_pandit', child: Text('🔱 Assign Varanasi Pandit...')),
                                            const PopupMenuDivider(),
                                            const PopupMenuItem(value: 'new', child: Text('Move to: NEW')),
                                            const PopupMenuItem(value: 'contacted', child: Text('Move to: CONTACTED')),
                                            const PopupMenuItem(value: 'qualified', child: Text('Move to: QUALIFIED')),
                                            const PopupMenuItem(value: 'proposal', child: Text('Move to: PROPOSAL')),
                                            const PopupMenuItem(value: 'won', child: Text('Move to: WON 🎉')),
                                            const PopupMenuItem(value: 'lost', child: Text('Move to: LOST')),
                                          ],
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          );
                        },
                      ),
                    ),
        ),
      ],
    );
  }

  Widget _buildStageBadge(String stage) {
    Color col;
    switch (stage.toLowerCase()) {
      case 'won':
        col = Colors.green;
        break;
      case 'proposal':
        col = Colors.purpleAccent;
        break;
      case 'qualified':
        col = Colors.blue;
        break;
      case 'contacted':
        col = Colors.amber;
        break;
      case 'lost':
        col = Colors.grey;
        break;
      default:
        col = Colors.cyan;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: col.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(6),
        border: Border.all(color: col, width: 0.8),
      ),
      child: Text(
        stage.toUpperCase(),
        style: TextStyle(color: col, fontSize: 10, fontWeight: FontWeight.bold),
      ),
    );
  }
}
