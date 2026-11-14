import 'package:flutter/material.dart';
import '../../../theme/manager_theme.dart';
import '../campaigns/campaign_automation_screen.dart';
import '../studio/banner_puja_studio_screen.dart';
import '../agents/ai_agents_hub_screen.dart';
import 'growth_broadcast_screen.dart';
import 'traffic_forecast_screen.dart';
import '../bookings/master_bookings_screen.dart';
import '../social/social_management_screen.dart';
import '../blogs/blogs_management_screen.dart';
import '../b2b/b2b_partner_hub_screen.dart';

class GrowthHubScreen extends StatefulWidget {
  const GrowthHubScreen({super.key});

  @override
  State<GrowthHubScreen> createState() => _GrowthHubScreenState();
}

class _GrowthHubScreenState extends State<GrowthHubScreen> {
  int _selectedModule = 0;

  final List<Map<String, dynamic>> _modules = [
    {'title': 'Forecast & Keywords', 'icon': Icons.auto_graph_rounded, 'screen': const TrafficForecastScreen()},
    {'title': 'Campaigns', 'icon': Icons.campaign_rounded, 'screen': const CampaignAutomationScreen()},
    {'title': 'AI Studio', 'icon': Icons.auto_fix_high_rounded, 'screen': const BannerPujaStudioScreen()},
    {'title': 'AI Swarm', 'icon': Icons.psychology_rounded, 'screen': const AiAgentsHubScreen()},
    {'title': 'Broadcast', 'icon': Icons.cell_tower_rounded, 'screen': const GrowthBroadcastScreen()},
    {'title': 'Bookings', 'icon': Icons.receipt_long_rounded, 'screen': const MasterBookingsScreen()},
    {'title': 'Social', 'icon': Icons.share_rounded, 'screen': const SocialManagementScreen()},
    {'title': 'Blogs SEO', 'icon': Icons.article_rounded, 'screen': const BlogsManagementScreen()},
    {'title': 'B2B Hub', 'icon': Icons.business_rounded, 'screen': const B2bPartnerHubScreen()},
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: ManagerTheme.backgroundDark,
      body: Column(
        children: [
          // Top horizontal switcher
          Container(
            padding: const EdgeInsets.fromLTRB(12, 40, 12, 10),
            color: ManagerTheme.surfaceDark,
            child: SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: List.generate(_modules.length, (i) {
                  final m = _modules[i];
                  final isSel = _selectedModule == i;
                  return Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: ChoiceChip(
                      selected: isSel,
                      avatar: Icon(
                        m['icon'] as IconData,
                        size: 16,
                        color: isSel ? Colors.black : Colors.white70,
                      ),
                      label: Text(m['title'] as String),
                      selectedColor: ManagerTheme.brandGold,
                      backgroundColor: ManagerTheme.surfaceBorder.withValues(alpha: 0.3),
                      labelStyle: TextStyle(
                        fontSize: 12,
                        fontWeight: isSel ? FontWeight.bold : FontWeight.normal,
                        color: isSel ? Colors.black : Colors.white70,
                      ),
                      onSelected: (_) => setState(() => _selectedModule = i),
                    ),
                  );
                }),
              ),
            ),
          ),
          const Divider(height: 1, color: ManagerTheme.surfaceBorder),
          // Selected Sub-Screen
          Expanded(
            child: _modules[_selectedModule]['screen'] as Widget,
          ),
        ],
      ),
    );
  }
}
