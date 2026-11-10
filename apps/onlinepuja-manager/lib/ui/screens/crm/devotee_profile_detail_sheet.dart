import 'package:flutter/material.dart';
import '../../../services/manager_api_service.dart';
import '../../../theme/manager_theme.dart';
import '../kundli/kundli_studio_screen.dart';

class DevoteeProfileDetailSheet extends StatefulWidget {
  final Map<String, dynamic> initialContact;
  final void Function(String jid, String name, String phone)? onOpenChat;

  const DevoteeProfileDetailSheet({
    super.key,
    required this.initialContact,
    this.onOpenChat,
  });

  static Future<void> show(
    BuildContext context,
    Map<String, dynamic> contact, {
    void Function(String jid, String name, String phone)? onOpenChat,
  }) {
    return showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => DevoteeProfileDetailSheet(
        initialContact: contact,
        onOpenChat: onOpenChat,
      ),
    );
  }

  @override
  State<DevoteeProfileDetailSheet> createState() => _DevoteeProfileDetailSheetState();
}

class _DevoteeProfileDetailSheetState extends State<DevoteeProfileDetailSheet> {
  final ManagerApiService _api = ManagerApiService();

  late Map<String, dynamic> _contact;
  bool _isLoading = true;
  bool _isSavingGotra = false;

  final TextEditingController _gotraController = TextEditingController();
  final TextEditingController _kuldevtaController = TextEditingController();
  final TextEditingController _newMemberNameController = TextEditingController();
  final TextEditingController _newMemberRelationController = TextEditingController();

  List<Map<String, dynamic>> _familyMembers = [];

  @override
  void initState() {
    super.initState();
    _contact = Map<String, dynamic>.from(widget.initialContact);
    _fetchFullProfile();
  }

  @override
  void dispose() {
    _gotraController.dispose();
    _kuldevtaController.dispose();
    _newMemberNameController.dispose();
    _newMemberRelationController.dispose();
    super.dispose();
  }

  Future<void> _fetchFullProfile() async {
    final contactId = _contact['id']?.toString() ?? '';
    if (contactId.isEmpty) {
      setState(() => _isLoading = false);
      return;
    }

    try {
      final res = await _api.fetchContactDetail(contactId);
      if (res != null && mounted) {
        setState(() {
          _contact = res;
          _gotraController.text = _contact['gotra']?.toString() ?? 'Kashyap';
          _kuldevtaController.text = _contact['kuldevta']?.toString() ?? '';
          if (_contact['family_members'] is List) {
            _familyMembers = List<Map<String, dynamic>>.from(
              (_contact['family_members'] as List).map((e) => Map<String, dynamic>.from(e as Map)),
            );
          }
          _isLoading = false;
        });
        return;
      }
    } catch (_) {}

    if (mounted) {
      setState(() {
        _gotraController.text = _contact['gotra']?.toString() ?? 'Kashyap';
        _isLoading = false;
      });
    }
  }

  Future<void> _saveGotraVault() async {
    final contactId = _contact['id']?.toString() ?? widget.initialContact['id']?.toString() ?? '';
    if (contactId.isEmpty) return;

    setState(() => _isSavingGotra = true);

    final success = await _api.updateGotraVault(
      contactId,
      gotra: _gotraController.text.trim(),
      kuldevta: _kuldevtaController.text.trim(),
      familyMembers: _familyMembers,
    );

    if (mounted) {
      setState(() => _isSavingGotra = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            success ? '🕉️ Sacred Gotra Vault updated successfully!' : 'Failed to update Gotra Vault',
            style: const TextStyle(fontWeight: FontWeight.bold),
          ),
          backgroundColor: success ? Colors.green.shade800 : Colors.red.shade800,
        ),
      );
    }
  }

  void _addFamilyMember() {
    final name = _newMemberNameController.text.trim();
    final relation = _newMemberRelationController.text.trim();
    if (name.isEmpty) return;

    setState(() {
      _familyMembers.add({
        'name': name,
        'relation': relation.isNotEmpty ? relation : 'Family Member',
      });
      _newMemberNameController.clear();
      _newMemberRelationController.clear();
    });
  }

  void _removeFamilyMember(int index) {
    setState(() {
      _familyMembers.removeAt(index);
    });
  }

  void _openQuickWhatsAppTemplate(String templateKey) {
    Navigator.pop(context);
    final phone = _contact['phone']?.toString() ?? '';
    final clean = phone.replaceAll(RegExp(r'[^0-9]'), '');
    final jid = clean.length == 10 ? '91$clean@s.whatsapp.net' : '$clean@s.whatsapp.net';
    final name = _contact['name']?.toString() ?? 'Devotee';

    if (widget.onOpenChat != null) {
      widget.onOpenChat!(jid, name, phone);
    }
  }

  @override
  Widget build(BuildContext context) {
    final height = MediaQuery.of(context).size.height * 0.88;
    final isDevotee = (_contact['role'] ?? widget.initialContact['role']) == 'devotee';
    final tier = _contact['tier']?.toString() ?? (isDevotee ? 'Regular Devotee' : 'Acharya');
    final tierBadge = _contact['tier_badge']?.toString() ?? (isDevotee ? '🪔' : '🕉️');

    return Container(
      height: height,
      decoration: const BoxDecoration(
        color: ManagerTheme.surfaceDark,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        border: Border(top: BorderSide(color: ManagerTheme.brandGold, width: 1.5)),
      ),
      child: Column(
        children: [
          // Drag handle & Header
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 12, 16, 12),
            child: Row(
              children: [
                CircleAvatar(
                  radius: 26,
                  backgroundColor: ManagerTheme.brandGold.withValues(alpha: 0.15),
                  child: Text(
                    (_contact['name']?.toString() ?? 'D')[0].toUpperCase(),
                    style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: ManagerTheme.brandGold),
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Flexible(
                            child: Text(
                              _contact['name']?.toString() ?? 'Devotee Profile',
                              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          const SizedBox(width: 8),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              color: ManagerTheme.brandGold.withValues(alpha: 0.2),
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.4)),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Text(tierBadge, style: const TextStyle(fontSize: 12)),
                                const SizedBox(width: 4),
                                Text(
                                  tier,
                                  style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: ManagerTheme.brandGold),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 3),
                      Text(
                        '+${_contact['phone'] ?? 'N/A'} • ${_contact['email'] ?? 'No email'}',
                        style: TextStyle(fontSize: 12, color: Colors.white.withValues(alpha: 0.6)),
                      ),
                    ],
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.close_rounded, color: Colors.white70),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
          ),
          const Divider(height: 1, color: Colors.white10),

          // Scrollable Profile & Gotra Vault
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator(color: ManagerTheme.brandGold))
                : ListView(
                    padding: const EdgeInsets.all(20),
                    children: [
                      // 1. Devotional Stats Card
                      _buildDevotionalStatsCard(isDevotee),
                      const SizedBox(height: 14),

                      // 1b. Instant Janampatri PDF Generator Button
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton.icon(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: ManagerTheme.brandGold,
                            foregroundColor: Colors.black,
                            padding: const EdgeInsets.symmetric(vertical: 12),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          ),
                          icon: const Icon(Icons.picture_as_pdf_rounded, size: 18),
                          label: const Text('📜 Generate Vedic Janampatri PDF (WhatsApp Delivery)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5)),
                          onPressed: () {
                            Navigator.pop(context);
                            Navigator.push(
                              context,
                              MaterialPageRoute(builder: (_) => KundliStudioScreen(initialDevotee: _contact)),
                            );
                          },
                        ),
                      ),
                      const SizedBox(height: 20),

                      // 2. Sacred Gotra & Family Sankalp Vault (If Devotee)
                      if (isDevotee) ...[
                        _buildGotraVaultSection(),
                        const SizedBox(height: 20),
                      ],

                      // 3. 1-Tap WhatsApp Spiritual Action Templates
                      _buildQuickWhatsAppActions(),
                      const SizedBox(height: 20),

                      // 4. Recent Puja Orders & Sankalp History
                      if (isDevotee) ...[
                        _buildRecentOrdersHistory(),
                        const SizedBox(height: 20),
                      ],
                    ],
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildDevotionalStatsCard(bool isDevotee) {
    final totalSpent = _contact['total_spent'] ?? _contact['total_gmv'] ?? 0;
    final ordersCount = _contact['orders_count'] ?? 0;
    final city = _contact['city'] ?? 'India';

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF13181E),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            children: [
              Icon(Icons.insights_rounded, color: ManagerTheme.brandGold, size: 18),
              SizedBox(width: 8),
              Text(
                'Devotional Lifetime Matrix',
                style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.white),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Row(
            children: [
              Expanded(
                child: _buildMetricTile(
                  isDevotee ? 'Lifetime GMV' : 'Consultation Rate',
                  isDevotee ? '₹$totalSpent' : '₹${_contact['rate_per_min'] ?? 0}/min',
                  Icons.currency_rupee_rounded,
                  ManagerTheme.brandGold,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildMetricTile(
                  isDevotee ? 'Pujas Performed' : 'Availability',
                  isDevotee ? '$ordersCount Pujas' : (_contact['status'] ?? 'Online').toUpperCase(),
                  isDevotee ? Icons.auto_stories_rounded : Icons.radio_button_checked_rounded,
                  Colors.greenAccent,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildMetricTile(
                  'Location',
                  city.toString(),
                  Icons.location_on_rounded,
                  Colors.lightBlueAccent,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildMetricTile(String label, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.03),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: Colors.white10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 14, color: color),
              const SizedBox(width: 4),
              Expanded(
                child: Text(
                  label,
                  style: TextStyle(fontSize: 10, color: Colors.white.withValues(alpha: 0.6)),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            value,
            style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.white),
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }

  Widget _buildGotraVaultSection() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF13181E),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.3)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.account_balance_rounded, color: ManagerTheme.brandGold, size: 18),
              const SizedBox(width: 8),
              const Expanded(
                child: Text(
                  'Sacred Gotra & Family Sankalp Vault',
                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.white),
                ),
              ),
              if (_isSavingGotra)
                const SizedBox(
                  width: 16,
                  height: 16,
                  child: CircularProgressIndicator(strokeWidth: 2, color: ManagerTheme.brandGold),
                )
              else
                TextButton.icon(
                  icon: const Icon(Icons.check_circle_rounded, size: 16, color: ManagerTheme.brandGold),
                  label: const Text('Save Vault', style: TextStyle(color: ManagerTheme.brandGold, fontWeight: FontWeight.bold, fontSize: 12)),
                  onPressed: _saveGotraVault,
                ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _gotraController,
                  style: const TextStyle(color: Colors.white, fontSize: 13),
                  decoration: InputDecoration(
                    labelText: 'Devotee Gotra',
                    labelStyle: const TextStyle(color: ManagerTheme.brandGold, fontSize: 12),
                    filled: true,
                    fillColor: Colors.black26,
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: TextField(
                  controller: _kuldevtaController,
                  style: const TextStyle(color: Colors.white, fontSize: 13),
                  decoration: InputDecoration(
                    labelText: 'Kuldevta / Ishta Dev',
                    labelStyle: const TextStyle(color: ManagerTheme.brandGold, fontSize: 12),
                    filled: true,
                    fillColor: Colors.black26,
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          const Text(
            'Family Members for Sankalp Chanting:',
            style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white70),
          ),
          const SizedBox(height: 8),
          if (_familyMembers.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 8),
              child: Text(
                'No family members recorded yet. Add names below so pandits can chant their names in sankalps.',
                style: TextStyle(fontSize: 12, color: Colors.white.withValues(alpha: 0.5), fontStyle: FontStyle.italic),
              ),
            )
          else
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: List.generate(_familyMembers.length, (index) {
                final m = _familyMembers[index];
                return Chip(
                  backgroundColor: Colors.white.withValues(alpha: 0.05),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                    side: const BorderSide(color: Colors.white12),
                  ),
                  avatar: const Icon(Icons.person, size: 14, color: ManagerTheme.brandGold),
                  label: Text(
                    '${m['name']} (${m['relation'] ?? 'Family'})',
                    style: const TextStyle(color: Colors.white, fontSize: 12),
                  ),
                  deleteIcon: const Icon(Icons.close_rounded, size: 14, color: Colors.redAccent),
                  onDeleted: () => _removeFamilyMember(index),
                );
              }),
            ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                flex: 3,
                child: TextField(
                  controller: _newMemberNameController,
                  style: const TextStyle(color: Colors.white, fontSize: 12),
                  decoration: InputDecoration(
                    hintText: 'Member Name',
                    hintStyle: TextStyle(color: Colors.white.withValues(alpha: 0.4), fontSize: 12),
                    filled: true,
                    fillColor: Colors.black26,
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                flex: 2,
                child: TextField(
                  controller: _newMemberRelationController,
                  style: const TextStyle(color: Colors.white, fontSize: 12),
                  decoration: InputDecoration(
                    hintText: 'Relation',
                    hintStyle: TextStyle(color: Colors.white.withValues(alpha: 0.4), fontSize: 12),
                    filled: true,
                    fillColor: Colors.black26,
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              IconButton.filled(
                icon: const Icon(Icons.add_rounded, color: Colors.black),
                style: IconButton.styleFrom(backgroundColor: ManagerTheme.brandGold),
                onPressed: _addFamilyMember,
              ),
            ],
          ),
        ],
      ),
    );
  }

    Future<void> _showSankalpPatraDialog() async {
    final contactId = _contact['id']?.toString() ?? widget.initialContact['id']?.toString() ?? '';
    final patra = await _api.generateSankalpPatra(contactId);

    if (!mounted) return;

    if (patra == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Failed to generate Sankalp Patra')),
      );
      return;
    }

    final formattedText = patra['formatted_text']?.toString() ?? '';

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: ManagerTheme.surfaceDark,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
          side: const BorderSide(color: ManagerTheme.brandGold, width: 1.5),
        ),
        title: const Row(
          children: [
            Icon(Icons.description_rounded, color: ManagerTheme.brandGold),
            SizedBox(width: 8),
            Text('संकल्प पत्र (Sankalp Patra)', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
          ],
        ),
        content: SizedBox(
          width: 500,
          child: SingleChildScrollView(
            child: Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: Colors.black26,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.white10),
              ),
              child: SelectableText(
                formattedText,
                style: const TextStyle(color: Colors.white, fontSize: 13, height: 1.5),
              ),
            ),
          ),
        ),
        actions: [
          TextButton(
            child: const Text('Close', style: TextStyle(color: Colors.white60)),
            onPressed: () => Navigator.pop(ctx),
          ),
          ElevatedButton.icon(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF25D366),
              foregroundColor: Colors.black,
            ),
            icon: const Icon(Icons.copy_rounded, size: 16),
            label: const Text('Copy for Pandit WhatsApp', style: TextStyle(fontWeight: FontWeight.bold)),
            onPressed: () {
              Navigator.pop(ctx);
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('📋 Sankalp Patra copied! Ready to send to Varanasi Pandit.'),
                  backgroundColor: Color(0xFF25D366),
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  Widget _buildQuickWhatsAppActions() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF13181E),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF25D366).withValues(alpha: 0.3)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            children: [
              Icon(Icons.chat_bubble_rounded, color: Color(0xFF25D366), size: 18),
              SizedBox(width: 8),
              Text(
                '1-Tap WhatsApp Spiritual Action Center',
                style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.white),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
                            ActionChip(
                avatar: const Icon(Icons.print_rounded, size: 14, color: Colors.black),
                backgroundColor: ManagerTheme.brandGold,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                label: const Text('📜 1-Click संकल्प पत्र (Pandit Copy)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.black)),
                onPressed: _showSankalpPatraDialog,
              ),
              _buildTemplateChip('🕉️ Sankalp Verification', 'sankalp_verify'),
              _buildTemplateChip('🪔 Live Puja Link', 'live_puja_link'),
              _buildTemplateChip('📦 Prasad Dispatch Tracking', 'prasad_tracking'),
              _buildTemplateChip('✨ Auspicious Muhurta Greeting', 'muhurta_blessing'),
            ],
          ),
          const SizedBox(height: 14),
          SizedBox(
            width: double.infinity,
            height: 44,
            child: ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF25D366),
                foregroundColor: Colors.black,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
              icon: const Icon(Icons.chat_bubble_rounded, size: 18),
              label: const Text('Open Direct WhatsApp Chat Thread', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
              onPressed: () => _openQuickWhatsAppTemplate('direct_chat'),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTemplateChip(String label, String key) {
    return ActionChip(
      backgroundColor: Colors.white.withValues(alpha: 0.05),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(10),
        side: BorderSide(color: const Color(0xFF25D366).withValues(alpha: 0.3)),
      ),
      label: Text(label, style: const TextStyle(fontSize: 12, color: Colors.white)),
      onPressed: () => _openQuickWhatsAppTemplate(key),
    );
  }

  Widget _buildRecentOrdersHistory() {
    final orders = (_contact['orders'] is List) ? List<Map<String, dynamic>>.from(_contact['orders'] as List) : [];

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF13181E),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.history_rounded, color: ManagerTheme.brandGold, size: 18),
              const SizedBox(width: 8),
              Text(
                'Recent Puja Sankalps (${orders.length})',
                style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.white),
              ),
            ],
          ),
          const SizedBox(height: 12),
          if (orders.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 8),
              child: Text(
                'No recorded pujas yet for this devotee.',
                style: TextStyle(color: Colors.white.withValues(alpha: 0.5), fontSize: 12),
              ),
            )
          else
            ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: orders.length,
              separatorBuilder: (_, __) => const Divider(color: Colors.white10),
              itemBuilder: (context, i) {
                final o = orders[i];
                final status = o['puja_order_status'] ?? 'pending';
                final isCompleted = status == 'completed';
                return Padding(
                  padding: const EdgeInsets.symmetric(vertical: 6),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: isCompleted ? Colors.green.withValues(alpha: 0.2) : Colors.orange.withValues(alpha: 0.2),
                          shape: BoxShape.circle,
                        ),
                        child: Icon(
                          isCompleted ? Icons.check_circle_rounded : Icons.pending_rounded,
                          size: 16,
                          color: isCompleted ? Colors.greenAccent : Colors.orangeAccent,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              o['puja_name'] ?? 'Vedic Puja',
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Colors.white),
                            ),
                            Text(
                              'Gotra: ${o['sankalp_gotra'] ?? 'Kashyap'} • Package: ${o['package_name'] ?? 'Sanctum'}',
                              style: TextStyle(fontSize: 11, color: Colors.white.withValues(alpha: 0.6)),
                            ),
                          ],
                        ),
                      ),
                      Text(
                        '₹${o['order_total_price'] ?? 0}',
                        style: const TextStyle(fontWeight: FontWeight.bold, color: ManagerTheme.brandGold, fontSize: 14),
                      ),
                    ],
                  ),
                );
              },
            ),
        ],
      ),
    );
  }
}
