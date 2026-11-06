import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:onlinepuja_manager/models/website_visitor_model.dart';
import 'package:onlinepuja_manager/providers/website_radar_provider.dart';
import 'package:onlinepuja_manager/services/manager_api_service.dart';
import 'package:onlinepuja_manager/services/manager_auth_session.dart';
import 'package:onlinepuja_manager/theme/manager_theme.dart';
import '../blogs/blogs_management_screen.dart';
import '../social/social_management_screen.dart';
import '../bookings/master_bookings_screen.dart';
import '../b2b/b2b_partner_hub_screen.dart';
import '../campaigns/campaign_automation_screen.dart';
import '../agents/master_ai_assistant_sheet.dart';

class LiveRadarScreen extends StatefulWidget {
  const LiveRadarScreen({super.key});

  @override
  State<LiveRadarScreen> createState() => _LiveRadarScreenState();
}

class _LiveRadarScreenState extends State<LiveRadarScreen> {
  final ManagerApiService _apiService = ManagerApiService();

  bool _isLoading = false;
  double _todayGmv = 0.0;
  int _activeAstrologers = 0;
  int _ongoingCalls = 0;
  int _pendingSankalps = 0;
  int _prasadDispatch = 0;
  int _totalOrdersToday = 0;

  List<Map<String, dynamic>> _recentOrders = [];

  @override
  void initState() {
    super.initState();
    _refresh();
  }

  Future<void> _refresh() async {
    setState(() => _isLoading = true);
    final data = await _apiService.fetchLiveRadar();
    if (mounted) {
      if (data != null) {
        setState(() {
          _todayGmv = (data['todayGmv'] is num)
              ? (data['todayGmv'] as num).toDouble()
              : (double.tryParse(data['todayGmv']?.toString() ?? '') ?? 0.0);
          _totalOrdersToday = (data['totalOrdersToday'] is num)
              ? (data['totalOrdersToday'] as num).toInt()
              : (int.tryParse(data['totalOrdersToday']?.toString() ?? '') ?? 0);
          _activeAstrologers = (data['activeAstrologers'] is num)
              ? (data['activeAstrologers'] as num).toInt()
              : (int.tryParse(data['activeAstrologers']?.toString() ?? '') ?? 0);
          _ongoingCalls = (data['ongoingCalls'] is num)
              ? (data['ongoingCalls'] as num).toInt()
              : (int.tryParse(data['ongoingCalls']?.toString() ?? '') ?? 0);
          _pendingSankalps = (data['pendingSankalps'] is num)
              ? (data['pendingSankalps'] as num).toInt()
              : (int.tryParse(data['pendingSankalps']?.toString() ?? '') ?? 0);
          _prasadDispatch = int.tryParse(data['prasadDispatchCount']?.toString() ?? data['prasadDispatch']?.toString() ?? '') ?? 0;
          _recentOrders = data['recentOrders'] is List
              ? List<Map<String, dynamic>>.from(data['recentOrders'])
              : [];
          _isLoading = false;
        });
      } else {
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(6),
              decoration: const BoxDecoration(
                gradient: ManagerTheme.executiveGold,
                shape: BoxShape.circle,
              ),
              child: const Text('ॐ', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.black)),
            ),
            const SizedBox(width: 10),
            const Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('OnlinePuja Manager', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w900)),
                Text('Varanasi Live Operations Hub', style: TextStyle(fontSize: 10, color: Colors.white70)),
              ],
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.auto_awesome, color: ManagerTheme.brandGold, size: 20),
            tooltip: 'AI Ops Copilot',
            onPressed: () => MasterAiAssistantSheet.show(context),
          ),
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: Colors.white70),
            tooltip: 'Refresh Radar',
            onPressed: _refresh,
          ),
          PopupMenuButton<String>(
            icon: const Icon(Icons.shield_rounded, color: Colors.white70),
            tooltip: 'Executive Security',
            color: const Color(0xFF1E293B),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            onSelected: (val) {
              final session = context.read<ManagerAuthSession>();
              if (val == 'lock') {
                session.lockApp();
              } else if (val == 'logout') {
                session.logout();
              }
            },
            itemBuilder: (ctx) => [
              const PopupMenuItem(
                value: 'lock',
                child: Row(
                  children: [
                    Icon(Icons.lock_outline_rounded, color: ManagerTheme.brandGold, size: 18),
                    SizedBox(width: 10),
                    Text('Lock Command Center', style: TextStyle(color: Colors.white, fontSize: 13)),
                  ],
                ),
              ),
              const PopupMenuItem(
                value: 'logout',
                child: Row(
                  children: [
                    Icon(Icons.logout_rounded, color: Colors.redAccent, size: 18),
                    SizedBox(width: 10),
                    Text('Superadmin Logout', style: TextStyle(color: Colors.redAccent, fontSize: 13)),
                  ],
                ),
              ),
            ],
          ),
        ],
        bottom: _isLoading
            ? const PreferredSize(
                preferredSize: Size.fromHeight(2),
                child: LinearProgressIndicator(
                  minHeight: 2,
                  color: ManagerTheme.brandGold,
                  backgroundColor: Colors.transparent,
                ),
              )
            : null,
      ),
      body: RefreshIndicator(
        onRefresh: _refresh,
        color: ManagerTheme.brandGold,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // 1. Executive GMV Card
              _buildGmvHeroCard(),
              const SizedBox(height: 16),

              // 2. 4-Grid Operations Radar
              _buildOperationsRadarGrid(),
              const SizedBox(height: 20),

              // 3. Business Control Tower (Blogs, Social, Bookings, B2B)
              _buildBusinessControlTower(),
              const SizedBox(height: 24),

              // 3b. Real-Time Devotee Live Radar
              _buildLiveDevoteeRadarStream(context),
              const SizedBox(height: 28),

              // 4. Live Order Stream
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    'Live Bookings Stream',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Colors.white),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: ManagerTheme.brandEmerald.withValues(alpha: 0.18),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        CircleAvatar(radius: 3, backgroundColor: ManagerTheme.brandEmerald),
                        SizedBox(width: 4),
                        Text('LIVE', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: ManagerTheme.brandEmerald)),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),

              if (_recentOrders.isEmpty)
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(vertical: 24, horizontal: 16),
                  decoration: BoxDecoration(
                    color: ManagerTheme.surfaceDark,
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: Colors.white12),
                  ),
                  child: const Center(
                    child: Text(
                      'No recent orders today yet. Live transactions will stream here in real time.',
                      textAlign: TextAlign.center,
                      style: TextStyle(fontSize: 12, color: Colors.white60),
                    ),
                  ),
                )
              else
                ListView.separated(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: _recentOrders.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 10),
                  itemBuilder: (context, index) {
                    final order = _recentOrders[index];
                    return _buildOrderStreamCard(order);
                  },
                ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildGmvHeroCard() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: ManagerTheme.darkCardGradient,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.35), width: 1.2),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.4),
            blurRadius: 16,
            offset: const Offset(0, 6),
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
                children: [
                  Container(
                    padding: const EdgeInsets.all(6),
                    decoration: BoxDecoration(
                      color: ManagerTheme.brandGold.withValues(alpha: 0.15),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.currency_rupee_rounded, color: ManagerTheme.brandGold, size: 18),
                  ),
                  const SizedBox(width: 8),
                  const Text(
                    "TODAY'S ESTIMATED GMV",
                    style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, letterSpacing: 0.8, color: Colors.white70),
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: ManagerTheme.brandEmerald.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Text('+18.4% vs yday', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: ManagerTheme.brandEmerald)),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            '₹${_todayGmv.toStringAsFixed(0)}',
            style: const TextStyle(fontSize: 34, fontWeight: FontWeight.w900, color: Colors.white, letterSpacing: -0.5),
          ),
          const SizedBox(height: 8),
          Text(
            '$_totalOrdersToday orders booked today across Pujas, Calls & AstroMall',
            style: const TextStyle(fontSize: 12, color: Colors.white60),
          ),
        ],
      ),
    );
  }

  Widget _buildOperationsRadarGrid() {
    return GridView.count(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisCount: 2,
      crossAxisSpacing: 12,
      mainAxisSpacing: 12,
      childAspectRatio: 1.45,
      children: [
        _buildMetricTile(
          icon: Icons.person_pin_rounded,
          iconColor: ManagerTheme.brandGold,
          title: 'Active Pandits',
          value: '$_activeAstrologers Online',
          subtitle: 'Ready for Sankalp/Calls',
        ),
        _buildMetricTile(
          icon: Icons.phone_in_talk_rounded,
          iconColor: ManagerTheme.brandEmerald,
          title: 'Ongoing Calls',
          value: '$_ongoingCalls Active',
          subtitle: 'Live devotee consultations',
        ),
        _buildMetricTile(
          icon: Icons.videocam_rounded,
          iconColor: ManagerTheme.brandSapphire,
          title: 'Pending Sankalps',
          value: '$_pendingSankalps Awaiting',
          subtitle: 'Video upload needed',
        ),
        _buildMetricTile(
          icon: Icons.local_shipping_rounded,
          iconColor: ManagerTheme.brandRuby,
          title: 'Prasad Dispatch',
          value: '$_prasadDispatch Ready',
          subtitle: 'AWB labels queued',
        ),
      ],
    );
  }

  Widget _buildMetricTile({
    required IconData icon,
    required Color iconColor,
    required String title,
    required String value,
    required String subtitle,
  }) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: ManagerTheme.surfaceDark,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white.withValues(alpha: 0.08)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            children: [
              Icon(icon, color: iconColor, size: 20),
              const SizedBox(width: 6),
              Text(title, style: const TextStyle(fontSize: 12, color: Colors.white70, fontWeight: FontWeight.w600)),
            ],
          ),
          Text(value, style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: Colors.white)),
          Text(subtitle, style: const TextStyle(fontSize: 10, color: Colors.white54)),
        ],
      ),
    );
  }

  Widget _buildBusinessControlTower() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Row(
          children: [
            Icon(Icons.hub_rounded, color: ManagerTheme.brandGold, size: 20),
            SizedBox(width: 8),
            Text(
              'OnlinePuja Command Center',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Colors.white),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: _buildControlCard(
                title: 'Blogs & SEO',
                subtitle: '46 Live Guides',
                icon: Icons.auto_stories_rounded,
                color: const Color(0xFFF59E0B),
                onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const BlogsManagementScreen())),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _buildControlCard(
                title: 'Social Hub',
                subtitle: '71 Active Posts',
                icon: Icons.share_rounded,
                color: const Color(0xFFE11D48),
                onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const SocialManagementScreen())),
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: _buildControlCard(
                title: 'Bookings Ledger',
                subtitle: 'Pujas & Consults',
                icon: Icons.receipt_long_rounded,
                color: const Color(0xFF10B981),
                onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const MasterBookingsScreen())),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _buildControlCard(
                title: 'B2B Partners',
                subtitle: '10+ Travel Hubs',
                icon: Icons.handshake_rounded,
                color: const Color(0xFF6366F1),
                onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const B2bPartnerHubScreen())),
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        _buildControlCard(
          title: 'Marketing & Campaign Automation',
          subtitle: 'Automated WhatsApp, Brevo Email & Push Drops',
          icon: Icons.cell_tower_rounded,
          color: const Color(0xFF38BDF8),
          onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const CampaignAutomationScreen())),
        ),
      ],
    );
  }

  Widget _buildControlCard({
    required String title,
    required String subtitle,
    required IconData icon,
    required Color color,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: ManagerTheme.surfaceDark,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: color.withValues(alpha: 0.3)),
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
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    title,
                    style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Colors.white),
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 2),
                  Text(
                    subtitle,
                    style: const TextStyle(fontSize: 10.5, color: Colors.white60),
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildOrderStreamCard(Map<String, dynamic> order) {
    final isAwaitingVideo = order['status'] == 'Awaiting Video';

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: ManagerTheme.surfaceDark,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: isAwaitingVideo
              ? ManagerTheme.brandGold.withValues(alpha: 0.4)
              : Colors.white.withValues(alpha: 0.06),
        ),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: isAwaitingVideo
                  ? ManagerTheme.brandGold.withValues(alpha: 0.15)
                  : ManagerTheme.brandEmerald.withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(
              isAwaitingVideo ? Icons.video_camera_back_rounded : Icons.check_circle_rounded,
              color: isAwaitingVideo ? ManagerTheme.brandGold : ManagerTheme.brandEmerald,
              size: 22,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(order['id']!, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: ManagerTheme.brandGold)),
                    Text(order['time']!, style: const TextStyle(fontSize: 10, color: Colors.white54)),
                  ],
                ),
                const SizedBox(height: 4),
                Text(order['puja']!, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: Colors.white)),
                const SizedBox(height: 2),
                Text("Devotee: ${order['devotee']} (${order['gotra']} Gotra)", style: const TextStyle(fontSize: 11, color: Colors.white70)),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(order['amount']!, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: Colors.white)),
              const SizedBox(height: 4),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: isAwaitingVideo
                      ? ManagerTheme.brandGold.withValues(alpha: 0.15)
                      : ManagerTheme.brandEmerald.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  order['status']!,
                  style: TextStyle(
                    fontSize: 9,
                    fontWeight: FontWeight.bold,
                    color: isAwaitingVideo ? ManagerTheme.brandGold : ManagerTheme.brandEmerald,
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildLiveDevoteeRadarStream(BuildContext context) {
    final radar = context.watch<WebsiteRadarProvider>();
    final visitors = radar.visitors;

    return Column(
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
                    color: const Color(0xFF10B981).withValues(alpha: 0.15),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.radar_rounded, color: Color(0xFF10B981), size: 18),
                ),
                const SizedBox(width: 8),
                const Text(
                  'Live Devotee Radar',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Colors.white),
                ),
              ],
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: const Color(0xFF10B981).withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFF10B981).withValues(alpha: 0.4)),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const CircleAvatar(radius: 3.5, backgroundColor: Color(0xFF10B981)),
                  const SizedBox(width: 6),
                  Text(
                    '${radar.activeCount} Online · ${radar.hotCount} High Intent',
                    style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF10B981)),
                  ),
                ],
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),

        if (visitors.isEmpty)
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: ManagerTheme.surfaceDark,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: Colors.white12),
            ),
            child: const Center(
              child: Text(
                'Scanning for active on-site devotees...',
                style: TextStyle(color: Colors.white54, fontSize: 12),
              ),
            ),
          )
        else
          ListView.separated(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: visitors.length,
            separatorBuilder: (_, __) => const SizedBox(height: 10),
            itemBuilder: (context, index) {
              return _buildDevoteeVisitorCard(visitors[index]);
            },
          ),
      ],
    );
  }

  Widget _buildDevoteeVisitorCard(WebsiteVisitor visitor) {
    final isHot = visitor.intentScore >= 80;
    final minutes = visitor.durationSeconds ~/ 60;
    final seconds = visitor.durationSeconds % 60;

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: ManagerTheme.surfaceDark,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: isHot ? ManagerTheme.brandGold.withValues(alpha: 0.4) : Colors.white12,
          width: isHot ? 1.2 : 0.8,
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
                  Text(visitor.flag, style: const TextStyle(fontSize: 16)),
                  const SizedBox(width: 6),
                  Text(
                    visitor.city,
                    style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Colors.white),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.08),
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: Text(
                      '${minutes}m ${seconds}s',
                      style: const TextStyle(fontSize: 10, color: Colors.white70),
                    ),
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: isHot
                      ? ManagerTheme.brandGold.withValues(alpha: 0.15)
                      : ManagerTheme.brandEmerald.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  '${visitor.intentScore}% ${visitor.intentLabel}',
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.bold,
                    color: isHot ? ManagerTheme.brandGold : ManagerTheme.brandEmerald,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('🕉️ ', style: TextStyle(fontSize: 13)),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      visitor.pageTitle,
                      style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: Colors.white),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      'Via: ${visitor.referrer} · Device: ${visitor.device}',
                      style: const TextStyle(fontSize: 10, color: Colors.white54),
                    ),
                  ],
                ),
              ),
            ],
          ),
          if (visitor.devoteeName != null && visitor.devoteeName!.isNotEmpty) ...[
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                color: const Color(0xFF0F172A),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(
                children: [
                  const Icon(Icons.person_outline_rounded, size: 14, color: ManagerTheme.brandGold),
                  const SizedBox(width: 6),
                  Text(
                    '${visitor.devoteeName} (${visitor.gotra ?? "Vedic"} Gotra)',
                    style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Colors.white),
                  ),
                  const Spacer(),
                  if (visitor.devoteePhone != null)
                    Text(
                      visitor.devoteePhone!,
                      style: const TextStyle(fontSize: 11, color: ManagerTheme.brandGold, fontFamily: 'monospace'),
                    ),
                ],
              ),
            ),
          ],
          const SizedBox(height: 10),
          Row(
            children: [
              if (visitor.devoteePhone != null && visitor.devoteePhone!.isNotEmpty) ...[
                Expanded(
                  child: OutlinedButton.icon(
                    icon: const Icon(Icons.phone_in_talk_rounded, size: 14, color: Color(0xFF10B981)),
                    label: const Text('Call Devotee', style: TextStyle(fontSize: 11, color: Colors.white)),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 8),
                      side: const BorderSide(color: Color(0xFF10B981)),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                    onPressed: () async {
                      final url = Uri.parse('tel:${visitor.devoteePhone}');
                      if (await canLaunchUrl(url)) {
                        await launchUrl(url);
                      }
                    },
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: ElevatedButton.icon(
                    icon: const Icon(Icons.chat_bubble_rounded, size: 14, color: Colors.black),
                    label: const Text('WhatsApp', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.black)),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF25D366),
                      padding: const EdgeInsets.symmetric(vertical: 8),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                    onPressed: () async {
                      final cleanPhone = visitor.devoteePhone!.replaceAll(RegExp(r'[^0-9]'), '');
                      final msg = Uri.encodeComponent(
                        'Pranam ${visitor.devoteeName ?? "Ji"}, OnlinePuja Varanasi sanctum here. We noticed you were viewing ${visitor.pageTitle}. How may our Purohit team assist with your Sankalp?',
                      );
                      final waUrl = Uri.parse('https://wa.me/$cleanPhone?text=$msg');
                      if (await canLaunchUrl(waUrl)) {
                        await launchUrl(waUrl, mode: LaunchMode.externalApplication);
                      }
                    },
                  ),
                ),
                const SizedBox(width: 8),
              ],
              IconButton(
                icon: const Icon(Icons.open_in_new_rounded, size: 18, color: Colors.white60),
                tooltip: 'Open Page',
                onPressed: () async {
                  final url = Uri.parse('https://onlinepuja.live${visitor.currentPage}');
                  if (await canLaunchUrl(url)) {
                    await launchUrl(url, mode: LaunchMode.externalApplication);
                  }
                },
              ),
            ],
          ),
        ],
      ),
    );
  }
}
