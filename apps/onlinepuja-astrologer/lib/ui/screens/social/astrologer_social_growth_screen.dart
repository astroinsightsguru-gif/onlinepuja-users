import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:http/http.dart' as http;
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../state/app_session.dart';
import '../../theme/partner_theme.dart';
import '../../widgets/partner_widgets.dart';

class AstrologerSocialGrowthScreen extends StatefulWidget {
  const AstrologerSocialGrowthScreen({super.key});

  static const route = '/social-growth';

  @override
  State<AstrologerSocialGrowthScreen> createState() =>
      _AstrologerSocialGrowthScreenState();
}

class _AstrologerSocialGrowthScreenState
    extends State<AstrologerSocialGrowthScreen> {
  bool _isLoading = true;
  bool _isAutopilotEnabled = true;
  String _activeFilter = 'all';

  Map<String, dynamic>? _statusData;
  List<Map<String, dynamic>> _creatives = [];

  final String _baseUrl = 'https://onlinepuja.live/api/partner/social';

  @override
  void initState() {
    super.initState();
    _loadSocialData();
  }

  Future<void> _loadSocialData() async {
    setState(() => _isLoading = true);
    final session = context.read<PartnerSession>();
    final astroId = session.astrologerId > 0 ? session.astrologerId : 1;

    try {
      final res = await http.get(
        Uri.parse('$_baseUrl/status?astrologer_id=$astroId'),
        headers: {'Accept': 'application/json'},
      );

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['success'] == true) {
          _statusData = data;
          final accounts = data['accounts'] as Map<String, dynamic>?;
          _isAutopilotEnabled =
              accounts?['instagram']?['is_autopilot_enabled'] ?? true;
        }
      }

      final cRes = await http.get(
        Uri.parse('$_baseUrl/creatives?astrologer_id=$astroId&type=$_activeFilter'),
        headers: {'Accept': 'application/json'},
      );

      if (cRes.statusCode == 200) {
        final cData = jsonDecode(cRes.body);
        if (cData['success'] == true && cData['creatives'] is List) {
          _creatives = List<Map<String, dynamic>>.from(cData['creatives']);
        }
      }
    } catch (e) {
      debugPrint('Error loading astrologer social data: $e');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _toggleAutopilot(bool val) async {
    final session = context.read<PartnerSession>();
    final astroId = session.astrologerId > 0 ? session.astrologerId : 1;

    setState(() => _isAutopilotEnabled = val);
    try {
      final res = await http.post(
        Uri.parse('$_baseUrl/toggle-autopilot'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({'astrologer_id': astroId, 'enabled': val}),
      );
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(data['message'] ?? 'Autopilot updated'),
              backgroundColor: val ? PartnerTheme.emerald : Colors.amber.shade800,
            ),
          );
        }
      }
    } catch (_) {}
  }

  Future<void> _connectPlatform(String platform) async {
    final session = context.read<PartnerSession>();
    final astroId = session.astrologerId > 0 ? session.astrologerId : 1;

    HapticFeedback.lightImpact();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Authenticating ${platform.toUpperCase()} with 1-Click Login...'),
        duration: const Duration(seconds: 1),
      ),
    );

    try {
      final res = await http.post(
        Uri.parse('$_baseUrl/connect'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({
          'astrologer_id': astroId,
          'platform': platform,
        }),
      );

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('✓ ${data['message']}'),
              backgroundColor: PartnerTheme.emerald,
            ),
          );
          _loadSocialData();
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Connection failed: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  Future<void> _disconnectPlatform(String platform) async {
    final session = context.read<PartnerSession>();
    final astroId = session.astrologerId > 0 ? session.astrologerId : 1;

    try {
      await http.post(
        Uri.parse('$_baseUrl/disconnect'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({'astrologer_id': astroId, 'platform': platform}),
      );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('$platform disconnected.')),
        );
        _loadSocialData();
      }
    } catch (_) {}
  }

  Future<void> _generateCreativeNow(String topic, String type) async {
    final session = context.read<PartnerSession>();
    final astroId = session.astrologerId > 0 ? session.astrologerId : 1;

    Navigator.pop(context); // Close bottom sheet
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Row(
          children: [
            SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)),
            SizedBox(width: 10),
            Text('Synthesizing Personalized AI Creative with your photo & QR...'),
          ],
        ),
        duration: Duration(seconds: 2),
      ),
    );

    try {
      final res = await http.post(
        Uri.parse('$_baseUrl/generate-creative'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({
          'astrologer_id': astroId,
          'type': type,
          'topic': topic,
        }),
      );

      if (res.statusCode == 200) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('✓ Creative ready! Added to your Social Studio.'),
              backgroundColor: PartnerTheme.emerald,
            ),
          );
          _loadSocialData();
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Generation failed: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  Future<void> _publishCreative(int creativeId) async {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Publishing creative to connected platforms...'),
        duration: Duration(seconds: 1),
      ),
    );

    try {
      final res = await http.post(
        Uri.parse('$_baseUrl/publish-creative'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({
          'creative_id': creativeId,
          'platforms': ['instagram', 'facebook', 'youtube'],
        }),
      );

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('✓ ${data['message']}'),
              backgroundColor: PartnerTheme.emerald,
            ),
          );
          _loadSocialData();
        }
      }
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    final dark = Theme.of(context).brightness == Brightness.dark;
    final session = context.watch<PartnerSession>();
    final accounts = _statusData?['accounts'] as Map<String, dynamic>?;

    return Scaffold(
      appBar: AppBar(
        title: const Row(
          children: [
            Icon(Icons.rocket_launch_rounded, color: PartnerTheme.gold, size: 20),
            SizedBox(width: 8),
            Text(
              'AI Social Growth Hub',
              style: TextStyle(fontWeight: FontWeight.w900, fontSize: 16),
            ),
          ],
        ),
        actions: [
          IconButton(
            tooltip: 'Refresh Status',
            icon: const Icon(Icons.refresh_rounded),
            onPressed: _loadSocialData,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: PartnerTheme.gold))
          : RefreshIndicator(
              onRefresh: _loadSocialData,
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 32),
                children: [
                  // 1. Astrologer Personal Brand Card
                  _buildBrandHeroCard(session, dark),

                  const SizedBox(height: 16),

                  // 2. 1-Click Platform Connection Cards
                  _buildPlatformConnectionsSection(accounts, dark),

                  const SizedBox(height: 16),

                  // 3. AI Autopilot OS Switch & Daily Cadence
                  _buildAutopilotControlCard(dark),

                  const SizedBox(height: 20),

                  // 4. Creative Studio & Fanout Feed
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        'Personalized AI Creatives',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.w900),
                      ),
                      ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: PartnerTheme.gold,
                          foregroundColor: Colors.black,
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                        icon: const Icon(Icons.add_rounded, size: 16),
                        label: const Text('Create New', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                        onPressed: _showCreateCreativeSheet,
                      ),
                    ],
                  ),

                  const SizedBox(height: 10),

                  // Filter Chips
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: Row(
                      children: [
                        _buildFilterPill('all', 'All Creatives'),
                        _buildFilterPill('reel', '9:16 Reels'),
                        _buildFilterPill('poster', 'Sacred Posters'),
                        _buildFilterPill('short', 'YouTube Shorts'),
                      ],
                    ),
                  ),

                  const SizedBox(height: 12),

                  // Creatives List
                  if (_creatives.isEmpty)
                    Container(
                      padding: const EdgeInsets.all(28),
                      decoration: BoxDecoration(
                        color: dark ? PartnerTheme.darkSurface : Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(
                          color: dark ? PartnerTheme.darkBorder : const Color(0xFFEBE3D7),
                        ),
                      ),
                      child: const Center(
                        child: Text(
                          'No creatives found for this filter. Tap "Create New" above to generate your first personalized reel or poster!',
                          textAlign: TextAlign.center,
                          style: TextStyle(fontSize: 12, color: Colors.grey),
                        ),
                      ),
                    )
                  else
                    ..._creatives.map((c) => _buildCreativeCard(c, dark)),
                ],
              ),
            ),
    );
  }

  Widget _buildBrandHeroCard(PartnerSession session, bool dark) {
    final slug = _statusData?['astrologer']?['slug'] ?? 'panditji';
    final name = session.user?.displayName ?? 'Acharya Ji';
    final bookingUrl = 'https://onlinepuja.live/astrologer/$slug';

    int totalReach = 0;
    final accounts = _statusData?['accounts'] as Map<String, dynamic>?;
    if (accounts != null) {
      totalReach += (accounts['facebook']?['followers_count'] as int? ?? 0);
      totalReach += (accounts['instagram']?['followers_count'] as int? ?? 0);
      totalReach += (accounts['youtube']?['followers_count'] as int? ?? 0);
    }

    return PartnerCard(
      gradient: dark
          ? const LinearGradient(
              colors: [Color(0xFF1E1B4B), Color(0xFF0F172A)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            )
          : const LinearGradient(
              colors: [Color(0xFFFEF3C7), Color(0xFFFFFBEB)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
      borderColor: PartnerTheme.gold.withValues(alpha: 0.5),
      padding: const EdgeInsets.all(18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              AstrologerAvatar(
                name: name,
                radius: 26,
                isOnline: true,
                showBadge: false,
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
                            name,
                            style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 16),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        const SizedBox(width: 6),
                        const Icon(Icons.verified_rounded, color: PartnerTheme.gold, size: 16),
                      ],
                    ),
                    const SizedBox(height: 2),
                    Text(
                      'Personal Brand Co-Pilot • Vedic Guru',
                      style: TextStyle(
                        fontSize: 11,
                        color: dark ? Colors.white70 : Colors.black87,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 4),
                    InkWell(
                      onTap: () {
                        Clipboard.setData(ClipboardData(text: bookingUrl));
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Booking URL copied to clipboard!')),
                        );
                      },
                      child: Row(
                        children: [
                          const Icon(Icons.link_rounded, size: 12, color: PartnerTheme.emerald),
                          const SizedBox(width: 3),
                          Text(
                            'onlinepuja.live/astrologer/$slug',
                            style: const TextStyle(
                              fontSize: 10.5,
                              color: PartnerTheme.emerald,
                              fontWeight: FontWeight.bold,
                              decoration: TextDecoration.underline,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          const Divider(height: 1, color: Colors.white12),
          const SizedBox(height: 12),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              _buildMetric('Audience Reach', '${totalReach > 0 ? (totalReach / 1000).toStringAsFixed(1) : "0"}k+', PartnerTheme.emerald),
              _buildMetric('Published Creatives', '${_statusData?['stats']?['published_count'] ?? 0}', PartnerTheme.gold),
              _buildMetric('AI Engine', 'Autopilot ON', Colors.cyanAccent),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildMetric(String label, String value, Color color) {
    return Column(
      children: [
        Text(
          value,
          style: TextStyle(fontSize: 16, fontWeight: FontWeight.w900, color: color),
        ),
        const SizedBox(height: 2),
        Text(
          label,
          style: const TextStyle(fontSize: 10, color: Colors.white60),
        ),
      ],
    );
  }

  Widget _buildPlatformConnectionsSection(Map<String, dynamic>? accounts, bool dark) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Connected Channels (1-Click Login)',
          style: TextStyle(fontSize: 15, fontWeight: FontWeight.w900),
        ),
        const SizedBox(height: 4),
        const Text(
          'Connect your accounts once. The AI brain publishes daily with your name, photo and QR.',
          style: TextStyle(fontSize: 11, color: Colors.grey),
        ),
        const SizedBox(height: 10),
        _buildChannelRow(
          platform: 'instagram',
          title: 'Instagram Creator',
          icon: Icons.camera_alt_rounded,
          color: const Color(0xFFE1306C),
          data: accounts?['instagram'],
          dark: dark,
        ),
        const SizedBox(height: 8),
        _buildChannelRow(
          platform: 'facebook',
          title: 'Facebook Page',
          icon: Icons.facebook_rounded,
          color: const Color(0xFF1877F2),
          data: accounts?['facebook'],
          dark: dark,
        ),
        const SizedBox(height: 8),
        _buildChannelRow(
          platform: 'youtube',
          title: 'YouTube Channel',
          icon: Icons.video_library_rounded,
          color: const Color(0xFFFF0000),
          data: accounts?['youtube'],
          dark: dark,
        ),
      ],
    );
  }

  Widget _buildChannelRow({
    required String platform,
    required String title,
    required IconData icon,
    required Color color,
    required Map<String, dynamic>? data,
    required bool dark,
  }) {
    final isConnected = data?['is_connected'] == true;
    final handle = data?['handle'] ?? 'Not Connected';
    final followers = data?['followers_count'] ?? 0;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: dark ? PartnerTheme.darkSurface : Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: isConnected
              ? PartnerTheme.emerald.withValues(alpha: 0.5)
              : (dark ? PartnerTheme.darkBorder : const Color(0xFFEBE3D7)),
        ),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
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
                Row(
                  children: [
                    Text(
                      title,
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                    ),
                    if (isConnected) ...[
                      const SizedBox(width: 6),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                        decoration: BoxDecoration(
                          color: PartnerTheme.emerald.withValues(alpha: 0.2),
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: const Text(
                          'ACTIVE',
                          style: TextStyle(color: PartnerTheme.emerald, fontSize: 8.5, fontWeight: FontWeight.bold),
                        ),
                      ),
                    ],
                  ],
                ),
                const SizedBox(height: 2),
                Text(
                  isConnected ? '$handle • $followers followers' : '1-Click Connect to auto-grow followers',
                  style: TextStyle(
                    fontSize: 11,
                    color: isConnected ? Colors.white70 : Colors.grey,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          if (isConnected)
            TextButton(
              onPressed: () => _disconnectPlatform(platform),
              style: TextButton.styleFrom(
                foregroundColor: Colors.redAccent,
                padding: const EdgeInsets.symmetric(horizontal: 8),
              ),
              child: const Text('Disconnect', style: TextStyle(fontSize: 11)),
            )
          else
            ElevatedButton(
              onPressed: () => _connectPlatform(platform),
              style: ElevatedButton.styleFrom(
                backgroundColor: color,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              ),
              child: const Text('Connect', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
            ),
        ],
      ),
    );
  }

  Widget _buildAutopilotControlCard(bool dark) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: dark ? PartnerTheme.darkSurface : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: _isAutopilotEnabled
              ? PartnerTheme.gold.withValues(alpha: 0.6)
              : (dark ? PartnerTheme.darkBorder : const Color(0xFFEBE3D7)),
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
                  Container(
                    padding: const EdgeInsets.all(6),
                    decoration: BoxDecoration(
                      color: PartnerTheme.gold.withValues(alpha: 0.2),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.auto_mode_rounded, color: PartnerTheme.gold, size: 18),
                  ),
                  const SizedBox(width: 10),
                  const Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'AI Growth Autopilot',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                      ),
                      Text(
                        'Automatic 6 AM & 5 PM broadcasts',
                        style: TextStyle(fontSize: 10.5, color: Colors.grey),
                      ),
                    ],
                  ),
                ],
              ),
              Switch(
                value: _isAutopilotEnabled,
                activeThumbColor: PartnerTheme.gold,
                onChanged: _toggleAutopilot,
              ),
            ],
          ),
          const SizedBox(height: 12),
          const Divider(height: 1, color: Colors.white12),
          const SizedBox(height: 10),
          _buildScheduleRow('🌅 06:00 AM IST', 'Daily Vedic Rashifal 9:16 Reel (दैनिक राशिफल)', dark),
          const SizedBox(height: 6),
          _buildScheduleRow('🌇 05:00 PM IST', 'Vedic Upay & Totke Short with Consultation QR', dark),
        ],
      ),
    );
  }

  Widget _buildScheduleRow(String time, String title, bool dark) {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
          decoration: BoxDecoration(
            color: dark ? Colors.white10 : Colors.black12,
            borderRadius: BorderRadius.circular(6),
          ),
          child: Text(
            time,
            style: const TextStyle(fontSize: 9.5, fontWeight: FontWeight.bold),
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            title,
            style: const TextStyle(fontSize: 11),
            overflow: TextOverflow.ellipsis,
          ),
        ),
      ],
    );
  }

  Widget _buildFilterPill(String id, String label) {
    final isSelected = _activeFilter == id;
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: InkWell(
        onTap: () {
          setState(() => _activeFilter = id);
          _loadSocialData();
        },
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
          decoration: BoxDecoration(
            color: isSelected ? PartnerTheme.gold : const Color(0xFF1E293B),
            borderRadius: BorderRadius.circular(16),
          ),
          child: Text(
            label,
            style: TextStyle(
              fontSize: 11.5,
              fontWeight: FontWeight.bold,
              color: isSelected ? Colors.black : Colors.white70,
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildCreativeCard(Map<String, dynamic> c, bool dark) {
    final title = c['title']?.toString() ?? 'Devotional Creative';
    final type = c['type']?.toString() ?? 'reel';
    final status = c['status']?.toString() ?? 'ready';
    final isPublished = status == 'published';
    final mediaUrl = c['media_url']?.toString() ?? '';
    final id = c['id'] as int? ?? 0;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: dark ? PartnerTheme.darkSurface : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: dark ? PartnerTheme.darkBorder : const Color(0xFFEBE3D7),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2.5),
                decoration: BoxDecoration(
                  color: type == 'reel'
                      ? const Color(0xFFE1306C).withValues(alpha: 0.15)
                      : const Color(0xFF38BDF8).withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  type.toUpperCase(),
                  style: TextStyle(
                    fontSize: 9.5,
                    fontWeight: FontWeight.bold,
                    color: type == 'reel' ? const Color(0xFFE1306C) : const Color(0xFF38BDF8),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  title,
                  style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: isPublished
                      ? PartnerTheme.emerald.withValues(alpha: 0.15)
                      : Colors.amber.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  isPublished ? 'PUBLISHED' : 'READY',
                  style: TextStyle(
                    fontSize: 9,
                    fontWeight: FontWeight.bold,
                    color: isPublished ? PartnerTheme.emerald : Colors.amber,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Text(
            c['caption']?.toString() ?? '',
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(fontSize: 11, color: Colors.white70),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              // Share to WhatsApp Status
              Expanded(
                child: OutlinedButton.icon(
                  style: OutlinedButton.styleFrom(
                    foregroundColor: const Color(0xFF25D366),
                    side: const BorderSide(color: Color(0xFF25D366)),
                    padding: const EdgeInsets.symmetric(vertical: 8),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                  icon: const Icon(Icons.chat_bubble_outline_rounded, size: 14),
                  label: const Text('Status 📱', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                  onPressed: () async {
                    final text = Uri.encodeComponent(
                      '${c['title']}\n\n${c['caption']}\n\nWatch here: $mediaUrl',
                    );
                    final uri = Uri.parse('whatsapp://send?text=$text');
                    if (await canLaunchUrl(uri)) launchUrl(uri);
                  },
                ),
              ),
              const SizedBox(width: 8),
              // Publish Now
              Expanded(
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: isPublished ? const Color(0xFF1E293B) : PartnerTheme.gold,
                    foregroundColor: isPublished ? Colors.white70 : Colors.black,
                    padding: const EdgeInsets.symmetric(vertical: 8),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                  icon: Icon(isPublished ? Icons.check_circle_rounded : Icons.send_rounded, size: 14),
                  label: Text(
                    isPublished ? 'Published' : 'Post to Channels',
                    style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold),
                  ),
                  onPressed: isPublished ? null : () => _publishCreative(id),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  void _showCreateCreativeSheet() {
    String selectedTopic = 'दैनिक राशिफल एवं ग्रह गोचर';
    String selectedType = 'reel';

    showModalBottomSheet(
      context: context,
      backgroundColor: const Color(0xFF0F172A),
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setSheetState) {
            return Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Row(
                    children: [
                      Icon(Icons.auto_awesome_rounded, color: PartnerTheme.gold, size: 20),
                      SizedBox(width: 8),
                      Text(
                        'Generate AI Devotional Creative',
                        style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  const Text(
                    'AI will personalize this media with your verified photo, Devanagari name, and direct consultation booking QR.',
                    style: TextStyle(color: Colors.white60, fontSize: 11.5),
                  ),
                  const SizedBox(height: 16),
                  const Text('Select Creative Format', style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      ChoiceChip(
                        label: const Text('9:16 Sacred Reel'),
                        selected: selectedType == 'reel',
                        selectedColor: PartnerTheme.gold,
                        onSelected: (v) => setSheetState(() => selectedType = 'reel'),
                      ),
                      const SizedBox(width: 8),
                      ChoiceChip(
                        label: const Text('Sacred Poster'),
                        selected: selectedType == 'poster',
                        selectedColor: PartnerTheme.gold,
                        onSelected: (v) => setSheetState(() => selectedType = 'poster'),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  const Text('Select Devotional Topic', style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 8),
                  DropdownButtonFormField<String>(
                    initialValue: selectedTopic,
                    dropdownColor: const Color(0xFF1E293B),
                    style: const TextStyle(color: Colors.white, fontSize: 13),
                    decoration: InputDecoration(
                      filled: true,
                      fillColor: const Color(0xFF1E293B),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    ),
                    items: const [
                      DropdownMenuItem(value: 'दैनिक राशिफल एवं ग्रह गोचर', child: Text('दैनिक राशिफल एवं ग्रह गोचर फल')),
                      DropdownMenuItem(value: 'मंगल दोष एवं विवाह बाधा निवारण', child: Text('मंगल दोष एवं विवाह बाधा निवारण')),
                      DropdownMenuItem(value: 'शनि की साढ़े साती शांति महा उपाय', child: Text('शनि की साढ़े साती शांति महा उपाय')),
                      DropdownMenuItem(value: 'संतान सुख एवं व्यापार वृद्धि उपाय', child: Text('संतान सुख एवं व्यापार वृद्धि उपाय')),
                    ],
                    onChanged: (val) {
                      if (val != null) setSheetState(() => selectedTopic = val);
                    },
                  ),
                  const SizedBox(height: 20),
                  ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: PartnerTheme.gold,
                      foregroundColor: Colors.black,
                      minimumSize: const Size(double.infinity, 44),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    onPressed: () => _generateCreativeNow(selectedTopic, selectedType),
                    child: const Text('Generate Now with AI Brain', style: TextStyle(fontWeight: FontWeight.w900)),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }
}
