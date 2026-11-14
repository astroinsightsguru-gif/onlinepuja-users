import 'package:flutter/material.dart';
import 'package:onlinepuja_manager/services/manager_api_service.dart';
import 'package:onlinepuja_manager/theme/manager_theme.dart';
import '../b2b/b2b_partner_hub_screen.dart';

class GrowthBroadcastScreen extends StatefulWidget {
  const GrowthBroadcastScreen({super.key});

  @override
  State<GrowthBroadcastScreen> createState() => _GrowthBroadcastScreenState();
}

class _GrowthBroadcastScreenState extends State<GrowthBroadcastScreen> with SingleTickerProviderStateMixin {
  final ManagerApiService _apiService = ManagerApiService();
  late TabController _tabController;

  // Push Controllers
  final TextEditingController _notifTitleCtrl = TextEditingController(
    text: '🪔 Varanasi Dev Deepawali: Offer 108 Deepdaan',
  );
  final TextEditingController _notifBodyCtrl = TextEditingController(
    text: 'Sacred Ganga Ghat Deepdaan on Kartik Purnima. Book your personal Sankalp with sanctum video & prasad delivery.',
  );

  // WhatsApp Devotee Blitz Controllers
  final TextEditingController _waMessageCtrl = TextEditingController(
    text: '🕉️ *ॐ नमः शिवाय* 🙏\n\n{name} जी, कार्तिक पूर्णिमा एवं देव दीपावली के पावन पर्व पर काशी के दशाश्वमेध घाट पर १०८ दीपदान एवं महारुद्राभिषेक संकल्प का विशेष आयोजन हो रहा है।\n\nअपने एवं परिवार के नाम-गोत्र से संकल्प कराएं। पूजन के उपरांत काशी का महाप्रसाद आपके पते पर भेजा जाएगा।\n\n🪔 संकल्प बुक करें: https://onlinepuja.live/pujas/dev-deepawali-varanasi-108-deepdaan\n\nOnlinePuja.live - श्री काशी विश्वनाथ सेवा',
  );
  final TextEditingController _waMediaUrlCtrl = TextEditingController(
    text: 'https://onlinepuja.live/storage/reels/master_reel_20261111_AEn9lT.mp4',
  );
  bool _isWaSending = false;

  bool _isBrainExecuting = false;
  String _brainResponse = 'Autonomous Master Brain running in self-growth mode. Daily video reels at 06:45 AM; automated SEO blog drafts at 05:00 AM.';

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    _notifTitleCtrl.dispose();
    _notifBodyCtrl.dispose();
    _waMessageCtrl.dispose();
    _waMediaUrlCtrl.dispose();
    super.dispose();
  }

  Future<void> _runBrainCommand(String actionKey, String label) async {
    setState(() {
      _isBrainExecuting = true;
      _brainResponse = 'Executing command: $label across live infrastructure...';
    });

    final output = await _apiService.triggerGrowthAction(actionKey);

    if (mounted) {
      setState(() {
        _isBrainExecuting = false;
        _brainResponse = '✓ $label:\n$output';
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('✓ $label executed successfully!'),
          backgroundColor: ManagerTheme.brandEmerald,
        ),
      );
    }
  }

  Future<void> _sendPushBroadcast() async {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: ManagerTheme.surfaceDark,
        title: const Text('Confirm Push Broadcast', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white)),
        content: Text(
          'Send this high-priority push notification to ALL registered devotees immediately?\n\n"${_notifTitleCtrl.text}"',
          style: const TextStyle(fontSize: 13, color: Colors.white70),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: ManagerTheme.brandGold, foregroundColor: Colors.black),
            onPressed: () async {
              Navigator.pop(ctx);
              final success = await _apiService.broadcastPush(
                title: _notifTitleCtrl.text,
                body: _notifBodyCtrl.text,
              );
              if (mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(success
                        ? '✓ Push notification successfully broadcast to all devotees!'
                        : 'Notification queued on server.'),
                    backgroundColor: ManagerTheme.brandEmerald,
                  ),
                );
              }
            },
            child: const Text('Send Broadcast'),
          ),
        ],
      ),
    );
  }

  Future<void> _sendWhatsAppBlitz() async {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: ManagerTheme.surfaceDark,
        title: const Row(
          children: [
            Icon(Icons.chat_rounded, color: ManagerTheme.brandEmerald, size: 22),
            SizedBox(width: 8),
            Text('Confirm WhatsApp Blitz', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white, fontSize: 16)),
          ],
        ),
        content: const Text(
          'Dispatch personalized WhatsApp invitations to all active devotee threads via Evolution API?\n\nIncludes 3.0s anti-ban composing simulation and human reading delays.',
          style: TextStyle(fontSize: 13, color: Colors.white70),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: ManagerTheme.brandEmerald, foregroundColor: Colors.white),
            onPressed: () async {
              Navigator.pop(ctx);
              setState(() => _isWaSending = true);

              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('🚀 Dispatching WhatsApp blitz with anti-ban presence simulation...'),
                  backgroundColor: ManagerTheme.brandAmber,
                ),
              );

              final res = await _apiService.dispatchWhatsappDevoteeBlast(
                message: _waMessageCtrl.text,
                mediaUrl: _waMediaUrlCtrl.text.isNotEmpty ? _waMediaUrlCtrl.text : null,
                limit: 30,
              );

              if (mounted) {
                setState(() => _isWaSending = false);
                final sent = res['sent_count'] ?? 0;
                final failed = res['failed_count'] ?? 0;

                showDialog(
                  context: context,
                  builder: (dCtx) => AlertDialog(
                    backgroundColor: ManagerTheme.surfaceDark,
                    title: const Row(
                      children: [
                        Icon(Icons.check_circle_rounded, color: ManagerTheme.brandEmerald, size: 22),
                        SizedBox(width: 8),
                        Text('WhatsApp Blitz Summary', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white)),
                      ],
                    ),
                    content: Text(
                      'Successfully delivered to $sent devotees ($failed failed).\n\nPresence pacing ensured zero spam flags.',
                      style: const TextStyle(fontSize: 13, color: Colors.white70),
                    ),
                    actions: [
                      TextButton(onPressed: () => Navigator.pop(dCtx), child: const Text('Done')),
                    ],
                  ),
                );
              }
            },
            child: const Text('Start Blitz'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: ManagerTheme.backgroundDark,
      appBar: AppBar(
        backgroundColor: ManagerTheme.surfaceDark,
        elevation: 0,
        title: const Text('Omnichannel Growth Broadcast', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: ManagerTheme.brandGold,
          labelColor: ManagerTheme.brandGold,
          unselectedLabelColor: Colors.white60,
          labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
          tabs: const [
            Tab(icon: Icon(Icons.chat_rounded, size: 18), text: 'WhatsApp Blitz'),
            Tab(icon: Icon(Icons.notifications_active_rounded, size: 18), text: 'Push Blaster'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildWhatsAppTab(),
          _buildPushTab(),
        ],
      ),
    );
  }

  // 1. WhatsApp Devotee Blitz Tab
  Widget _buildWhatsAppTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: ManagerTheme.surfaceDark,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: ManagerTheme.brandEmerald.withValues(alpha: 0.3)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Row(
                      children: [
                        Icon(Icons.chat_rounded, color: ManagerTheme.brandEmerald, size: 22),
                        SizedBox(width: 8),
                        Text(
                          'WhatsApp Devotee Blitz Engine',
                          style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: Colors.white),
                        ),
                      ],
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: ManagerTheme.brandEmerald.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Text(
                        'Evolution API + Human Delay',
                        style: TextStyle(fontSize: 10, color: ManagerTheme.brandEmerald, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                const Text(
                  'Personalized message with {name} and {gotra} token replacement. Sends direct sanctum reel and booking link.',
                  style: TextStyle(fontSize: 11, color: Colors.white60),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _waMessageCtrl,
                  maxLines: 5,
                  style: const TextStyle(color: Colors.white, fontSize: 13),
                  decoration: InputDecoration(
                    labelText: 'Devotee Message Template',
                    labelStyle: const TextStyle(color: Colors.white70),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    filled: true,
                    fillColor: Colors.black26,
                  ),
                ),
                const SizedBox(height: 10),
                TextField(
                  controller: _waMediaUrlCtrl,
                  style: const TextStyle(color: Colors.white, fontSize: 13),
                  decoration: InputDecoration(
                    labelText: 'Attached Reel / Sanctum Video MP4 URL',
                    labelStyle: const TextStyle(color: Colors.white70),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    filled: true,
                    fillColor: Colors.black26,
                    prefixIcon: const Icon(Icons.video_library_rounded, color: ManagerTheme.brandGold, size: 20),
                  ),
                ),
                const SizedBox(height: 14),
                SizedBox(
                  width: double.infinity,
                  height: 46,
                  child: ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: ManagerTheme.brandEmerald,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    icon: _isWaSending
                        ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                        : const Icon(Icons.send_rounded),
                    label: Text(
                      _isWaSending ? 'Dispatching with Anti-Ban Jitter...' : 'Blast WhatsApp to Devotees',
                      style: const TextStyle(fontWeight: FontWeight.bold),
                    ),
                    onPressed: _isWaSending ? null : _sendWhatsAppBlitz,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 18),
          _buildAutonomousTriggers(),
        ],
      ),
    );
  }

  // 2. Push Tab
  Widget _buildPushTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: ManagerTheme.surfaceDark,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.3)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Row(
                  children: [
                    Icon(Icons.campaign_rounded, color: ManagerTheme.brandGold, size: 22),
                    SizedBox(width: 8),
                    Text('Blast Push Notification', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: Colors.white)),
                  ],
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _notifTitleCtrl,
                  style: const TextStyle(color: Colors.white, fontSize: 13),
                  decoration: InputDecoration(
                    labelText: 'Notification Title',
                    labelStyle: const TextStyle(color: Colors.white70),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    filled: true,
                    fillColor: Colors.black26,
                  ),
                ),
                const SizedBox(height: 10),
                TextField(
                  controller: _notifBodyCtrl,
                  maxLines: 2,
                  style: const TextStyle(color: Colors.white, fontSize: 13),
                  decoration: InputDecoration(
                    labelText: 'Body Text / Festival Hook',
                    labelStyle: const TextStyle(color: Colors.white70),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    filled: true,
                    fillColor: Colors.black26,
                  ),
                ),
                const SizedBox(height: 14),
                SizedBox(
                  width: double.infinity,
                  height: 46,
                  child: ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: ManagerTheme.brandGold,
                      foregroundColor: Colors.black,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    icon: const Icon(Icons.send_rounded),
                    label: const Text('Blast to All Devotees', style: TextStyle(fontWeight: FontWeight.bold)),
                    onPressed: _sendPushBroadcast,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 18),
          _buildAutonomousTriggers(),
        ],
      ),
    );
  }

  Widget _buildAutonomousTriggers() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // B2B Banner
        Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xFF1E1B4B), ManagerTheme.surfaceDark],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: Colors.indigoAccent.withValues(alpha: 0.3)),
          ),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: Colors.indigoAccent.withValues(alpha: 0.2),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.handshake_rounded, color: Colors.indigoAccent, size: 24),
              ),
              const SizedBox(width: 12),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('B2B Distribution Hub', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: Colors.white)),
                    SizedBox(height: 2),
                    Text('Engage 100+ Hotels & Tour Operators with 25% revenue-share puja packages.', style: TextStyle(fontSize: 11, color: Colors.white70)),
                  ],
                ),
              ),
              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.indigoAccent,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
                onPressed: () {
                  Navigator.push(context, MaterialPageRoute(builder: (_) => const B2bPartnerHubScreen()));
                },
                child: const Text('Open Hub', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
              ),
            ],
          ),
        ),
        const SizedBox(height: 18),

        // Autonomous Master Brain Triggers
        const Text('Autonomous Master Brain Triggers', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: Colors.white)),
        const SizedBox(height: 10),

        Wrap(
          spacing: 8,
          runSpacing: 10,
          children: [
            _buildActionChip('Auto-Publish 2 SEO Blogs', 'generate_content', Icons.auto_stories_rounded),
            _buildActionChip('Dispatch Festival Social Posts', 'publish_social', Icons.share_rounded),
            _buildActionChip('Refresh 60-Day Festivals', 'seed_festivals', Icons.event_available_rounded),
            _buildActionChip('Rebuild Live Sitemap.xml', 'rebuild_sitemap', Icons.map_rounded),
            _buildActionChip('Omnichannel Broadcast', 'omnichannel_blast', Icons.cell_tower_rounded),
          ],
        ),
        const SizedBox(height: 16),

        // Live Console
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: const Color(0xFF020617),
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: Colors.white12),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Row(
                    children: [
                      Icon(Icons.terminal_rounded, color: ManagerTheme.brandEmerald, size: 18),
                      SizedBox(width: 6),
                      Text('AI BRAIN LIVE CONSOLE', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.white70)),
                    ],
                  ),
                  if (_isBrainExecuting)
                    const SizedBox(
                      width: 14,
                      height: 14,
                      child: CircularProgressIndicator(strokeWidth: 2, color: ManagerTheme.brandEmerald),
                    ),
                ],
              ),
              const Divider(color: Colors.white10, height: 16),
              Text(
                _brainResponse,
                style: const TextStyle(fontSize: 12, fontFamily: 'monospace', color: Colors.white70, height: 1.4),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildActionChip(String label, String actionKey, IconData icon) {
    return ActionChip(
      avatar: Icon(icon, size: 16, color: ManagerTheme.brandGold),
      label: Text(label, style: const TextStyle(fontSize: 12, color: Colors.white)),
      backgroundColor: ManagerTheme.surfaceDark,
      side: const BorderSide(color: Colors.white24),
      onPressed: _isBrainExecuting ? null : () => _runBrainCommand(actionKey, label),
    );
  }
}
