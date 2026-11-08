import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:onlinepuja_manager/services/manager_api_service.dart';
import 'package:onlinepuja_manager/theme/manager_theme.dart';

class CampaignAutomationScreen extends StatefulWidget {
  const CampaignAutomationScreen({super.key});

  @override
  State<CampaignAutomationScreen> createState() => _CampaignAutomationScreenState();
}

class _CampaignAutomationScreenState extends State<CampaignAutomationScreen> {
  final ManagerApiService _api = ManagerApiService();

  bool _isExecuting = false;
  bool _isLoading = true;

  List<Map<String, dynamic>> _channels = [];
  List<Map<String, dynamic>> _campaigns = [];

  @override
  void initState() {
    super.initState();
    _loadTelemetry();
  }

  Future<void> _loadTelemetry() async {
    setState(() => _isLoading = true);
    final data = await _api.fetchCampaignAutomations();
    if (mounted) {
      setState(() {
        if (data['channels'] is List && (data['channels'] as List).isNotEmpty) {
          _channels = List<Map<String, dynamic>>.from(data['channels']);
        }
        if (data['campaigns'] is List && (data['campaigns'] as List).isNotEmpty) {
          _campaigns = List<Map<String, dynamic>>.from(data['campaigns']);
        }
        _isLoading = false;
      });
    }
  }

  Future<void> _triggerCampaign(Map<String, dynamic> c) async {
    HapticFeedback.mediumImpact();
    final title = c['title']?.toString() ?? 'Campaign';
    final campaignId = c['id']?.toString() ?? 'festival_radar';

    setState(() {
      _isExecuting = true;
    });

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('🚀 Triggering $title across WhatsApp, Brevo & Push...'),
        backgroundColor: ManagerTheme.brandAmber,
      ),
    );

    final ok = await _api.triggerCampaignAutomation(campaignId);

    if (mounted) {
      setState(() {
        _isExecuting = false;
      });

      showDialog(
        context: context,
        builder: (ctx) => AlertDialog(
          backgroundColor: ManagerTheme.surfaceDark,
          title: const Row(
            children: [
              Icon(Icons.check_circle_rounded, color: ManagerTheme.brandEmerald, size: 22),
              SizedBox(width: 8),
              Text('Campaign Dispatched', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            ],
          ),
          content: Text(
            ok
                ? 'Autonomous campaign $title executed successfully through FestivalCampaignEngine and queued across WhatsApp & Devotee push.'
                : 'Campaign queued. Please check WhatsApp gateway connection.',
            style: const TextStyle(fontSize: 12, color: Colors.white70),
          ),
          actions: [
            ElevatedButton(
              style: ElevatedButton.styleFrom(backgroundColor: ManagerTheme.brandGold, foregroundColor: Colors.black),
              onPressed: () {
                Navigator.pop(ctx);
                _loadTelemetry();
              },
              child: const Text('Awesome'),
            ),
          ],
        ),
      );
    }
  }

  void _showComposeBroadcastModal() {
    final titleCtrl = TextEditingController(text: '🪔 Varanasi Dev Deepawali: Offer 108 Deepdaan');
    final bodyCtrl = TextEditingController(
      text: 'Sacred Ganga Ghat Deepdaan on Kartik Purnima. Book your personal Gotra Sankalp with sanctum video proof & consecrated prasad delivery.',
    );
    String selectedChannel = 'both';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: ManagerTheme.surfaceDark,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setModalState) => Padding(
          padding: EdgeInsets.fromLTRB(20, 20, 20, MediaQuery.of(ctx).viewInsets.bottom + 20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Row(
                children: [
                  Icon(Icons.cell_tower_rounded, color: ManagerTheme.brandGold, size: 22),
                  SizedBox(width: 8),
                  Text('Compose Omnichannel Blast', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Colors.white)),
                ],
              ),
              const SizedBox(height: 14),
              TextField(
                controller: titleCtrl,
                decoration: InputDecoration(
                  labelText: 'Campaign Headline',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: bodyCtrl,
                maxLines: 3,
                decoration: InputDecoration(
                  labelText: 'Body Copy (WhatsApp / Email HTML Hook)',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 14),
              Row(
                children: [
                  const Text('Channel: ', style: TextStyle(color: Colors.white70, fontSize: 12, fontWeight: FontWeight.bold)),
                  const SizedBox(width: 8),
                  ChoiceChip(
                    label: const Text('💬 WhatsApp'),
                    selected: selectedChannel == 'whatsapp',
                    selectedColor: ManagerTheme.brandGold,
                    labelStyle: TextStyle(fontSize: 11, color: selectedChannel == 'whatsapp' ? Colors.black : Colors.white70),
                    onSelected: (val) => setModalState(() => selectedChannel = 'whatsapp'),
                  ),
                  const SizedBox(width: 6),
                  ChoiceChip(
                    label: const Text('✉️ Brevo'),
                    selected: selectedChannel == 'email',
                    selectedColor: ManagerTheme.brandGold,
                    labelStyle: TextStyle(fontSize: 11, color: selectedChannel == 'email' ? Colors.black : Colors.white70),
                    onSelected: (val) => setModalState(() => selectedChannel = 'email'),
                  ),
                  const SizedBox(width: 6),
                  ChoiceChip(
                    label: const Text('⚡ Both'),
                    selected: selectedChannel == 'both',
                    selectedColor: ManagerTheme.brandGold,
                    labelStyle: TextStyle(fontSize: 11, color: selectedChannel == 'both' ? Colors.black : Colors.white70),
                    onSelected: (val) => setModalState(() => selectedChannel = 'both'),
                  ),
                ],
              ),
              const SizedBox(height: 18),
              SizedBox(
                width: double.infinity,
                height: 48,
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: ManagerTheme.brandGold,
                    foregroundColor: Colors.black,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  icon: const Icon(Icons.send_rounded),
                  label: const Text('Broadcast Campaign to Devotees', style: TextStyle(fontWeight: FontWeight.bold)),
                  onPressed: () async {
                    Navigator.pop(ctx);
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Broadcasting omnichannel campaign...')),
                    );
                    final res = await _api.sendOmnichannelBroadcast(
                      headline: titleCtrl.text,
                      body: bodyCtrl.text,
                      channel: selectedChannel,
                    );
                    if (mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(
                          content: Text(res['message']?.toString() ?? 'Campaign dispatched successfully!'),
                          backgroundColor: ManagerTheme.brandEmerald,
                        ),
                      );
                    }
                  },
                ),
              ),
            ],
          ),
        ),
      ),
    ).whenComplete(() {
      titleCtrl.dispose();
      bodyCtrl.dispose();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Campaign Automation Control'),
        actions: [
          IconButton(
            tooltip: 'Compose Blast',
            icon: const Icon(Icons.add_circle_outline_rounded, color: ManagerTheme.brandGold),
            onPressed: _showComposeBroadcastModal,
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: ManagerTheme.brandGold,
        foregroundColor: Colors.black,
        icon: const Icon(Icons.campaign_rounded),
        label: const Text('New Omnichannel Blast', style: TextStyle(fontWeight: FontWeight.bold)),
        onPressed: _showComposeBroadcastModal,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: ManagerTheme.brandGold))
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // 1. Channel Connectivity Health Cards
            const Text('Connected Marketing Engines', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Colors.white)),
            const SizedBox(height: 12),
            ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: _channels.length,
              separatorBuilder: (_, __) => const SizedBox(height: 10),
              itemBuilder: (context, i) {
                final ch = _channels[i];
                final dynamic rawColor = ch['color'];
                final Color color = rawColor is Color
                    ? rawColor
                    : (rawColor is int ? Color(rawColor) : const Color(0xFF25D366));

                final dynamic rawIcon = ch['icon'];
                final IconData icon = rawIcon is IconData
                    ? rawIcon
                    : (rawIcon?.toString().contains('mail') == true
                        ? Icons.mail_rounded
                        : (rawIcon?.toString().contains('notification') == true
                            ? Icons.notifications_active_rounded
                            : Icons.chat_bubble_rounded));

                final name = ch['name']?.toString() ?? 'Engine';
                final status = ch['status']?.toString() ?? 'Active';
                final sent = ch['sent_today'] ?? 0;
                final rate = ch['delivery_rate']?.toString() ?? '99%';

                return Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: ManagerTheme.surfaceDark,
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: color.withValues(alpha: 0.3)),
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: color.withValues(alpha: 0.15),
                          shape: BoxShape.circle,
                        ),
                        child: Icon(icon, color: color, size: 20),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(name, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.white)),
                            const SizedBox(height: 2),
                            Text('$status · $rate Delivery', style: const TextStyle(fontSize: 11, color: Colors.white60)),
                          ],
                        ),
                      ),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Text('$sent', style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w900, color: Colors.white)),
                          const Text('Sent Today', style: TextStyle(fontSize: 10, color: Colors.white54)),
                        ],
                      ),
                    ],
                  ),
                );
              },
            ),
            const SizedBox(height: 24),

            // 2. Active Campaign Automations
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text('Active Automation Sequences', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Colors.white)),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: ManagerTheme.brandEmerald.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: const Text('AUTO-PILOT ACTIVE', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: ManagerTheme.brandEmerald)),
                ),
              ],
            ),
            const SizedBox(height: 12),

            ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: _campaigns.length,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, i) {
                final c = _campaigns[i];
                final title = c['title'] as String;
                final subtitle = c['subtitle'] as String;
                final channels = c['channels'] as String;
                final cadence = c['cadence'] as String;
                final converted = c['converted'] as String;
                final isActive = c['is_active'] as bool;

                return Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: ManagerTheme.surfaceDark,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: Colors.white12),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Text(title, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: Colors.white)),
                          ),
                          Switch(
                            value: isActive,
                            activeThumbColor: ManagerTheme.brandGold,
                            onChanged: (val) {
                              setState(() => c['is_active'] = val);
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text('Campaign sequence ${val ? 'Activated' : 'Paused'}')),
                              );
                            },
                          ),
                        ],
                      ),
                      const SizedBox(height: 6),
                      Text(subtitle, style: const TextStyle(fontSize: 12, color: Colors.white70, height: 1.3)),
                      const SizedBox(height: 10),
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              color: Colors.indigoAccent.withValues(alpha: 0.15),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Text(channels, style: const TextStyle(fontSize: 10, color: Colors.indigoAccent, fontWeight: FontWeight.bold)),
                          ),
                          const SizedBox(width: 8),
                          Text('Cadence: $cadence', style: const TextStyle(fontSize: 11, color: Colors.white54)),
                        ],
                      ),
                      const Divider(color: Colors.white10, height: 20),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text('Attributed Value', style: TextStyle(fontSize: 10, color: Colors.white54)),
                              Text(converted, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: ManagerTheme.brandGold)),
                            ],
                          ),
                          ElevatedButton.icon(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: ManagerTheme.brandGold,
                              foregroundColor: Colors.black,
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                              visualDensity: VisualDensity.compact,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                            ),
                            icon: const Icon(Icons.bolt_rounded, size: 16),
                            label: const Text('Trigger Now', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                            onPressed: _isExecuting ? null : () => _triggerCampaign(c),
                          ),
                        ],
                      ),
                    ],
                  ),
                );
              },
            ),
            const SizedBox(height: 60),
          ],
        ),
      ),
    );
  }
}
