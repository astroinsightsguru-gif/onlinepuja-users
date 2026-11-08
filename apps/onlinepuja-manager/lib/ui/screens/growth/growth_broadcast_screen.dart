import 'package:flutter/material.dart';
import 'package:onlinepuja_manager/services/manager_api_service.dart';
import 'package:onlinepuja_manager/theme/manager_theme.dart';
import '../b2b/b2b_partner_hub_screen.dart';

class GrowthBroadcastScreen extends StatefulWidget {
  const GrowthBroadcastScreen({super.key});

  @override
  State<GrowthBroadcastScreen> createState() => _GrowthBroadcastScreenState();
}

class _GrowthBroadcastScreenState extends State<GrowthBroadcastScreen> {
  final ManagerApiService _apiService = ManagerApiService();

  final TextEditingController _notifTitleCtrl = TextEditingController(
    text: '🪔 Varanasi Dev Deepawali: Offer 108 Deepdaan',
  );
  final TextEditingController _notifBodyCtrl = TextEditingController(
    text: 'Sacred Ganga Ghat Deepdaan on Kartik Purnima. Book your personal Sankalp with sanctum video & prasad delivery.',
  );

  @override
  void dispose() {
    _notifTitleCtrl.dispose();
    _notifBodyCtrl.dispose();
    super.dispose();
  }

  bool _isBrainExecuting = false;
  String _brainResponse = 'Autonomous Master Brain running in self-growth mode. 2 blog articles scheduled daily at 05:00 AM; social dispatch at 06:30 AM.';

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
        title: const Text('Confirm Push Broadcast', style: TextStyle(fontWeight: FontWeight.bold)),
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Growth & Push Broadcast'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // 1. One-Tap Push Broadcaster Card
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
                    decoration: InputDecoration(
                      labelText: 'Notification Title',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                  ),
                  const SizedBox(height: 10),
                  TextField(
                    controller: _notifBodyCtrl,
                    maxLines: 2,
                    decoration: InputDecoration(
                      labelText: 'Body Text / Festival Hook',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
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
            // 2. B2B Partner Distribution Banner Card
            Container(
              margin: const EdgeInsets.only(top: 16),
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [
                    Color(0xFF1E1B4B),
                    ManagerTheme.surfaceDark,
                  ],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Colors.indigoAccent.withValues(alpha: 0.3)),
              ),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.indigoAccent.withValues(alpha: 0.2),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.handshake_rounded, color: Colors.indigoAccent, size: 26),
                  ),
                  const SizedBox(width: 14),
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'B2B Distribution Hub',
                          style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: Colors.white),
                        ),
                        SizedBox(height: 2),
                        Text(
                          'Engage 100+ Hotels & Tour Operators with 25% revenue-share puja packages.',
                          style: TextStyle(fontSize: 11, color: Colors.white70),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.indigoAccent,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    onPressed: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(builder: (_) => const B2bPartnerHubScreen()),
                      );
                    },
                    child: const Text('Open Hub', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // 3. Autonomous Master Brain Commands
            const Text('Autonomous Master Brain Triggers', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Colors.white)),
            const SizedBox(height: 12),

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

            // 3. AI Brain Live Console
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
            const SizedBox(height: 24),

            // 4. Quick Festival Presets
            const Text('Festival Notification Presets', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: Colors.white)),
            const SizedBox(height: 10),
            _buildPresetTile(
              'Varanasi Dev Deepawali',
              'Offer 108 Deepdaan on Ganga Ghats',
              'Devotees across the world are offering sacred lamps on Dev Deepawali. Receive personal Sankalp video proof & Kashi Prasad.',
            ),
            const SizedBox(height: 8),
            _buildPresetTile(
              'Kashi Somwar Rudrabhishek',
              'Live Sanctum Rudrabhishek Seva',
              'Perform authentic Rudrabhishek at Shri Kashi Vishwanath Dham. Personal Gotra Sankalp and sacred Bhasma prasad.',
            ),
            const SizedBox(height: 8),
            _buildPresetTile(
              'Diwali Maha Lakshmi Havan',
              'Invoke Ashta Lakshmi for Wealth',
              'Consecrated Maha Lakshmi Havan performed by senior Vedic pandits. Consecrated silver coin and prasad dispatched.',
            ),
          ],
        ),
      ),
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

  Widget _buildPresetTile(String festival, String title, String body) {
    return ListTile(
      tileColor: ManagerTheme.surfaceDark,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      leading: const Icon(Icons.flare_rounded, color: ManagerTheme.brandGold),
      title: Text(festival, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.white)),
      subtitle: Text(title, style: const TextStyle(fontSize: 11, color: Colors.white60)),
      trailing: TextButton(
        onPressed: () {
          setState(() {
            _notifTitleCtrl.text = '🪔 $festival: $title';
            _notifBodyCtrl.text = body;
          });
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Preset "$festival" loaded into composer')),
          );
        },
        child: const Text('Load', style: TextStyle(color: ManagerTheme.brandGold, fontWeight: FontWeight.bold)),
      ),
    );
  }
}
