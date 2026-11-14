import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/call_provider.dart';
import '../../providers/language_provider.dart';
import '../../services/manager_auth_session.dart';
import '../../theme/manager_theme.dart';
import 'astrologers/astrologers_hub_screen.dart';
import 'b2b/b2b_partner_hub_screen.dart';
import 'blogs/blogs_management_screen.dart';
import 'blogs/vedic_seo_aeo_screen.dart';
import 'bookings/master_bookings_screen.dart';
import 'campaigns/campaign_automation_screen.dart';
import 'crm/crm_hub_screen.dart';
import 'crm/leads_pipeline_screen.dart';
import 'dashboard/live_radar_screen.dart';
import 'dialer/dialer_screen.dart';
import 'email/email_campaign_hub_screen.dart';
import 'finance/finance_hub_screen.dart';
import 'fulfillment/sankalp_fulfillment_screen.dart';
import 'growth/growth_ai_cockpit_screen.dart';
import 'kundli/kundli_studio_screen.dart';
import 'agents/paperclip_squad_screen.dart';
import 'quotes/quotation_desk_screen.dart';
import 'social/social_management_screen.dart';
import 'social/social_studio_screen.dart';
import 'studio/banner_puja_studio_screen.dart';
import 'team/team_management_screen.dart';
import 'whatsapp/whatsapp_inbox_screen.dart';
import '../widgets/active_call_hud.dart';
import '../widgets/post_call_action_sheet.dart';

class ManagerShell extends StatefulWidget {
  const ManagerShell({super.key});

  @override
  State<ManagerShell> createState() => _ManagerShellState();
}

class _ManagerShellState extends State<ManagerShell> {
  int _currentIndex = 0;
  bool _isPostCallSheetOpen = false;
  final ScrollController _navScrollController = ScrollController();

  final List<Map<String, dynamic>> _rawDestinations = [
    {
      'key': 'radar',
      'label': 'Radar',
      'icon': Icons.radar_rounded,
      'color': ManagerTheme.brandGold,
      'module': null,
      'screen': const LiveRadarScreen(),
    },
    {
      'key': 'dialer',
      'label': 'Dialer',
      'icon': Icons.phone_rounded,
      'color': const Color(0xFF25D366),
      'module': 'crm',
      'screen': const DialerScreen(),
    },
    {
      'key': 'whatsapp',
      'label': 'WhatsApp',
      'icon': Icons.chat_bubble_rounded,
      'color': const Color(0xFF25D366),
      'module': 'campaigns',
      'screen': const WhatsAppInboxScreen(),
    },
    {
      'key': 'devotees',
      'label': 'Devotees',
      'icon': Icons.people_alt_rounded,
      'color': ManagerTheme.brandGold,
      'module': 'crm',
      'screen': const CrmHubScreen(),
    },
    {
      'key': 'astrologers',
      'label': 'Astrologers',
      'icon': Icons.self_improvement_rounded,
      'color': const Color(0xFFF59E0B),
      'module': 'astrologers',
      'screen': const AstrologersHubScreen(),
    },
    {
      'key': 'b2b_network',
      'label': 'B2B Network',
      'icon': Icons.business_center_rounded,
      'color': const Color(0xFF38BDF8),
      'module': 'b2b',
      'screen': const B2bPartnerHubScreen(),
    },
    {
      'key': 'quotes',
      'label': 'Quotes',
      'icon': Icons.description_rounded,
      'color': ManagerTheme.brandGold,
      'module': 'finance',
      'screen': const QuotationDeskScreen(),
    },
    {
      'key': 'sankalp',
      'label': 'Sankalp',
      'icon': Icons.local_shipping_rounded,
      'color': const Color(0xFF60A5FA),
      'module': 'crm',
      'screen': const SankalpFulfillmentScreen(),
    },
    {
      'key': 'ai_squad',
      'label': 'AI Squad',
      'icon': Icons.hub_rounded,
      'color': const Color(0xFFA78BFA),
      'module': 'paperclip',
      'screen': const PaperclipSquadScreen(embedded: true),
    },
    {
      'key': 'growth_ai',
      'label': 'Growth AI',
      'icon': Icons.auto_awesome_rounded,
      'color': ManagerTheme.brandGold,
      'module': 'campaigns',
      'screen': const GrowthAiCockpitScreen(),
    },
    {
      'key': 'social_hub',
      'label': 'Social Hub',
      'icon': Icons.share_rounded,
      'color': const Color(0xFFF43F5E),
      'module': 'campaigns',
      'screen': const SocialManagementScreen(),
    },
    {
      'key': 'blogs_hub',
      'label': 'Blogs Hub',
      'icon': Icons.menu_book_rounded,
      'color': const Color(0xFF34D399),
      'module': 'seo',
      'screen': const BlogsManagementScreen(),
    },
    {
      'key': 'finance',
      'label': 'Finance',
      'icon': Icons.account_balance_wallet_rounded,
      'color': const Color(0xFF10B981),
      'module': 'finance',
      'screen': const FinanceHubScreen(),
    },
    {
      'key': 'email_hub',
      'label': 'Brevo Email',
      'icon': Icons.mail_rounded,
      'color': const Color(0xFFFB923C),
      'module': 'campaigns',
      'screen': const EmailCampaignHubScreen(),
    },
    {
      'key': 'kundli',
      'label': 'Kundli PDF',
      'icon': Icons.picture_as_pdf_rounded,
      'color': const Color(0xFFFBBF24),
      'module': 'kundli',
      'screen': const KundliStudioScreen(),
    },
    {
      'key': 'team_access',
      'label': 'Team & Access',
      'icon': Icons.admin_panel_settings_rounded,
      'color': const Color(0xFFEC4899),
      'module': 'team',
      'screen': const TeamManagementScreen(),
    },
  ];

  @override
  void dispose() {
    _navScrollController.dispose();
    super.dispose();
  }

  void _onTabSelected(int index) {
    setState(() => _currentIndex = index);

    if (_navScrollController.hasClients) {
      final screenWidth = MediaQuery.of(context).size.width;
      const itemWidth = 80.0;
      final targetOffset = (index * itemWidth) - (screenWidth / 2) + (itemWidth / 2);
      _navScrollController.animateTo(
        targetOffset.clamp(0.0, _navScrollController.position.maxScrollExtent),
        duration: const Duration(milliseconds: 250),
        curve: Curves.easeOutCubic,
      );
    }
  }

  void _showUserMenu(BuildContext context, ManagerAuthSession session) {
    showModalBottomSheet(
      context: context,
      backgroundColor: const Color(0xFF1E293B),
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Row(
                children: [
                  CircleAvatar(
                    radius: 24,
                    backgroundColor: ManagerTheme.brandGold.withValues(alpha: 0.2),
                    child: Text(
                      session.name.isNotEmpty ? session.name[0].toUpperCase() : 'A',
                      style: const TextStyle(color: ManagerTheme.brandGold, fontSize: 20, fontWeight: FontWeight.bold),
                    ),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          session.name,
                          style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold),
                        ),
                        Text(
                          session.email,
                          style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 12),
                        ),
                        const SizedBox(height: 4),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                          decoration: BoxDecoration(
                            color: session.isSuperAdmin
                                ? ManagerTheme.brandGold.withValues(alpha: 0.2)
                                : const Color(0xFF38BDF8).withValues(alpha: 0.2),
                            borderRadius: BorderRadius.circular(6),
                            border: Border.all(
                              color: session.isSuperAdmin ? ManagerTheme.brandGold : const Color(0xFF38BDF8),
                            ),
                          ),
                          child: Text(
                            session.role.toUpperCase(),
                            style: TextStyle(
                              color: session.isSuperAdmin ? ManagerTheme.brandGold : const Color(0xFF38BDF8),
                              fontSize: 10,
                              fontWeight: FontWeight.w900,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),
              const Divider(color: Color(0xFF334155)),
              const SizedBox(height: 12),
              OutlinedButton.icon(
                onPressed: () {
                  Navigator.pop(ctx);
                  session.lockApp();
                },
                icon: const Icon(Icons.lock_rounded, size: 18, color: Colors.white70),
                label: const Text('Lock App with PIN', style: TextStyle(color: Colors.white70)),
                style: OutlinedButton.styleFrom(
                  side: const BorderSide(color: Color(0xFF475569)),
                  padding: const EdgeInsets.symmetric(vertical: 12),
                ),
              ),
              const SizedBox(height: 10),
              ElevatedButton.icon(
                onPressed: () async {
                  Navigator.pop(ctx);
                  await session.logout();
                },
                icon: const Icon(Icons.logout_rounded, size: 18),
                label: const Text('Log Out'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFFEF4444),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 12),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showMasterHubLauncher(BuildContext context, ManagerAuthSession session) {
    final clusters = [
      {
        'title': 'Sales & Devotee CRM',
        'icon': Icons.point_of_sale_rounded,
        'color': ManagerTheme.brandGold,
        'items': [
          {'name': 'Live Radar', 'icon': Icons.radar_rounded, 'color': ManagerTheme.brandGold, 'module': null, 'target': const LiveRadarScreen()},
          {'name': 'Devotees & Gotra Vault', 'icon': Icons.people_alt_rounded, 'color': const Color(0xFFF59E0B), 'module': 'crm', 'target': const CrmHubScreen()},
          {'name': 'Devotee Leads Pipeline', 'icon': Icons.view_kanban_rounded, 'color': const Color(0xFFFBBF24), 'module': 'crm', 'target': const LeadsPipelineScreen()},
          {'name': 'Smart Dialer', 'icon': Icons.phone_in_talk_rounded, 'color': const Color(0xFF25D366), 'module': 'crm', 'target': const DialerScreen()},
          {'name': 'Master Bookings & Revenue', 'icon': Icons.account_balance_wallet_rounded, 'color': const Color(0xFF34D399), 'module': 'finance', 'target': const MasterBookingsScreen()},
          {'name': 'Dynamic Quotations Desk', 'icon': Icons.description_rounded, 'color': const Color(0xFF60A5FA), 'module': 'finance', 'target': const QuotationDeskScreen()},
          {'name': 'WhatsApp Devotee Inbox', 'icon': Icons.chat_bubble_rounded, 'color': const Color(0xFF25D366), 'module': 'campaigns', 'target': const WhatsAppInboxScreen()},
        ],
      },
      {
        'title': 'Sacred Puja Operations',
        'icon': Icons.temple_hindu_rounded,
        'color': const Color(0xFFF59E0B),
        'items': [
          {'name': 'Sankalp Fulfillment & Video', 'icon': Icons.local_shipping_rounded, 'color': const Color(0xFF60A5FA), 'module': 'crm', 'target': const SankalpFulfillmentScreen()},
          {'name': 'Astrologers & Pandits Hub', 'icon': Icons.self_improvement_rounded, 'color': const Color(0xFFF59E0B), 'module': 'astrologers', 'target': const AstrologersHubScreen()},
          {'name': 'Vedic Janampatri PDF Studio', 'icon': Icons.picture_as_pdf_rounded, 'color': const Color(0xFFFBBF24), 'module': 'kundli', 'target': const KundliStudioScreen()},
          {'name': 'Puja Pricing & Banners Studio', 'icon': Icons.storefront_rounded, 'color': const Color(0xFFEC4899), 'module': 'crm', 'target': const BannerPujaStudioScreen()},
        ],
      },
      {
        'title': 'Autonomous Growth AI',
        'icon': Icons.auto_awesome_rounded,
        'color': const Color(0xFFA78BFA),
        'items': [
          {'name': 'Paperclip 8-Agent Squad', 'icon': Icons.hub_rounded, 'color': const Color(0xFFA78BFA), 'module': 'paperclip', 'target': const PaperclipSquadScreen(embedded: true)},
          {'name': 'Growth AI Cockpit', 'icon': Icons.rocket_launch_rounded, 'color': ManagerTheme.brandGold, 'module': 'campaigns', 'target': const GrowthAiCockpitScreen()},
          {'name': 'Social Autopilot & Publisher', 'icon': Icons.share_rounded, 'color': const Color(0xFFF43F5E), 'module': 'campaigns', 'target': const SocialManagementScreen()},
          {'name': 'Vedic Blogs Studio & Publishing', 'icon': Icons.menu_book_rounded, 'color': const Color(0xFF34D399), 'module': 'seo', 'target': const BlogsManagementScreen()},
          {'name': 'B2B Partner & Corporate Radar', 'icon': Icons.business_center_rounded, 'color': const Color(0xFF38BDF8), 'module': 'b2b', 'target': const B2bPartnerHubScreen()},
          {'name': 'Vedic SEO & AEO Studio', 'icon': Icons.manage_search_rounded, 'color': const Color(0xFF34D399), 'module': 'seo', 'target': const VedicSeoAeoScreen()},
          {'name': 'Social Studio & Reel Creator', 'icon': Icons.video_camera_back_rounded, 'color': const Color(0xFFF43F5E), 'module': 'campaigns', 'target': const SocialStudioScreen()},
          {'name': 'Brevo Email Campaign Engine', 'icon': Icons.mail_rounded, 'color': const Color(0xFFFB923C), 'module': 'campaigns', 'target': const EmailCampaignHubScreen()},
          {'name': 'Festival & Muhurat Automations', 'icon': Icons.celebration_rounded, 'color': const Color(0xFFE879F9), 'module': 'campaigns', 'target': const CampaignAutomationScreen()},
        ],
      },
      {
        'title': 'Platform & Team Governance',
        'icon': Icons.admin_panel_settings_rounded,
        'color': const Color(0xFFEC4899),
        'items': [
          {'name': 'Financial Ledger & Margins', 'icon': Icons.account_balance_wallet_rounded, 'color': const Color(0xFF10B981), 'module': 'finance', 'target': const FinanceHubScreen()},
          {'name': 'Team Members & Role Access', 'icon': Icons.admin_panel_settings_rounded, 'color': const Color(0xFFEC4899), 'module': 'team', 'target': const TeamManagementScreen()},
        ],
      },
    ];

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: const Color(0xFF0F172A),
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => DraggableScrollableSheet(
        initialChildSize: 0.85,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        expand: false,
        builder: (_, scrollController) => Column(
          children: [
            Container(
              margin: const EdgeInsets.only(top: 12, bottom: 8),
              width: 44,
              height: 4,
              decoration: BoxDecoration(
                color: Colors.white24,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: ManagerTheme.brandGold.withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(Icons.dashboard_customize_rounded, color: ManagerTheme.brandGold, size: 20),
                  ),
                  const SizedBox(width: 12),
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'OnlinePuja Command Hubs',
                          style: TextStyle(color: Colors.white, fontSize: 17, fontWeight: FontWeight.bold),
                        ),
                        Text(
                          'Unified 4-Cluster Access to all 31 Sacred Operations',
                          style: TextStyle(color: Color(0xFF94A3B8), fontSize: 11),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    onPressed: () => Navigator.pop(ctx),
                    icon: const Icon(Icons.close_rounded, color: Colors.white60),
                  ),
                ],
              ),
            ),
            const Divider(color: Color(0xFF1E293B), height: 1),
            Expanded(
              child: ListView.builder(
                controller: scrollController,
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                itemCount: clusters.length,
                itemBuilder: (context, idx) {
                  final cluster = clusters[idx];
                  final items = (cluster['items'] as List).where((item) {
                    final mod = item['module'] as String?;
                    if (mod == null) return true;
                    return session.hasPermission(mod);
                  }).toList();

                  if (items.isEmpty) return const SizedBox.shrink();

                  return Container(
                    margin: const EdgeInsets.only(bottom: 16),
                    decoration: BoxDecoration(
                      color: const Color(0xFF1E293B).withValues(alpha: 0.7),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: (cluster['color'] as Color).withValues(alpha: 0.3)),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Padding(
                          padding: const EdgeInsets.fromLTRB(16, 14, 16, 10),
                          child: Row(
                            children: [
                              Icon(cluster['icon'] as IconData, color: cluster['color'] as Color, size: 18),
                              const SizedBox(width: 8),
                              Text(
                                cluster['title'] as String,
                                style: TextStyle(
                                  color: cluster['color'] as Color,
                                  fontSize: 13,
                                  fontWeight: FontWeight.bold,
                                  letterSpacing: 0.4,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const Divider(color: Color(0xFF334155), height: 1),
                        GridView.builder(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          padding: const EdgeInsets.all(10),
                          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                            crossAxisCount: 2,
                            childAspectRatio: 2.3,
                            crossAxisSpacing: 8,
                            mainAxisSpacing: 8,
                          ),
                          itemCount: items.length,
                          itemBuilder: (context, itemIdx) {
                            final it = items[itemIdx];
                            return Material(
                              color: const Color(0xFF0F172A),
                              borderRadius: BorderRadius.circular(10),
                              child: InkWell(
                                onTap: () {
                                  Navigator.pop(ctx);
                                  Navigator.push(
                                    context,
                                    MaterialPageRoute(builder: (_) => it['target'] as Widget),
                                  );
                                },
                                borderRadius: BorderRadius.circular(10),
                                child: Padding(
                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                                  child: Row(
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.all(6),
                                        decoration: BoxDecoration(
                                          color: (it['color'] as Color).withValues(alpha: 0.15),
                                          borderRadius: BorderRadius.circular(8),
                                        ),
                                        child: Icon(it['icon'] as IconData, color: it['color'] as Color, size: 16),
                                      ),
                                      const SizedBox(width: 8),
                                      Expanded(
                                        child: Text(
                                          it['name'] as String,
                                          style: const TextStyle(
                                            color: Colors.white,
                                            fontSize: 11,
                                            fontWeight: FontWeight.w600,
                                          ),
                                          maxLines: 2,
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            );
                          },
                        ),
                      ],
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final callProvider = Provider.of<CallProvider>(context);
    final lang = Provider.of<LanguageProvider>(context);
    final session = Provider.of<ManagerAuthSession>(context);

    // Permission-filtered destinations and screens
    final allowedDestinations = _rawDestinations.where((d) {
      final mod = d['module'] as String?;
      if (mod == null) return true;
      return session.hasPermission(mod);
    }).toList();

    if (_currentIndex >= allowedDestinations.length) {
      _currentIndex = 0;
    }

    final screens = allowedDestinations.map((d) => d['screen'] as Widget).toList();

    // Auto-trigger post call action sheet when call completes
    if (callProvider.lastCompletedCall != null && !_isPostCallSheetOpen) {
      final completedCall = callProvider.lastCompletedCall!;
      _isPostCallSheetOpen = true;
      WidgetsBinding.instance.addPostFrameCallback((_) {
        PostCallActionSheet.show(context, completedCall);
        _isPostCallSheetOpen = false;
      });
    }

    return Scaffold(
      body: Stack(
        children: [
          IndexedStack(
            index: _currentIndex,
            children: screens,
          ),

          // Global Active In-Call Floating HUD
          if (callProvider.callState != CallState.idle)
            const Positioned(
              top: 0,
              left: 0,
              right: 0,
              child: SafeArea(
                child: Padding(
                  padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  child: ActiveCallHud(),
                ),
              ),
            ),

          // Top Left Command Launcher Pill (⌘ 31 HUBS)
          if (allowedDestinations[_currentIndex]['key'] != 'whatsapp')
            Positioned(
              top: MediaQuery.of(context).padding.top + 6,
              left: 12,
              child: InkWell(
                onTap: () => _showMasterHubLauncher(context, session),
                borderRadius: BorderRadius.circular(20),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      colors: [Color(0xFFF59E0B), Color(0xFFD97706)],
                    ),
                    borderRadius: BorderRadius.circular(20),
                    boxShadow: [
                      BoxShadow(
                        color: const Color(0xFFF59E0B).withValues(alpha: 0.35),
                        blurRadius: 10,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: const Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.dashboard_customize_rounded, color: Color(0xFF0B0F19), size: 14),
                      SizedBox(width: 5),
                      Text(
                        '31 HUBS',
                        style: TextStyle(
                          color: Color(0xFF0B0F19),
                          fontSize: 10.5,
                          fontWeight: FontWeight.w900,
                          letterSpacing: 0.6,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),

          // Top Floating Bar (Language Switcher + User Profile Pill)
          if (allowedDestinations[_currentIndex]['key'] != 'whatsapp')
            Positioned(
              top: MediaQuery.of(context).padding.top + 6,
              right: 12,
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                // User Profile & Role Pill
                InkWell(
                  onTap: () => _showUserMenu(context, session),
                  borderRadius: BorderRadius.circular(20),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: const Color(0xFF0F172A).withValues(alpha: 0.9),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(
                        color: session.isSuperAdmin
                            ? ManagerTheme.brandGold.withValues(alpha: 0.6)
                            : const Color(0xFF38BDF8).withValues(alpha: 0.6),
                        width: 1.2,
                      ),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.35),
                          blurRadius: 8,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        CircleAvatar(
                          radius: 8,
                          backgroundColor: session.isSuperAdmin ? ManagerTheme.brandGold : const Color(0xFF38BDF8),
                          child: Text(
                            session.name.isNotEmpty ? session.name[0].toUpperCase() : 'U',
                            style: const TextStyle(color: Color(0xFF0B0F19), fontSize: 8, fontWeight: FontWeight.bold),
                          ),
                        ),
                        const SizedBox(width: 5),
                        Text(
                          session.name.split(' ').first,
                          style: const TextStyle(color: Colors.white, fontSize: 10.5, fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(width: 4),
                        const Icon(Icons.arrow_drop_down, color: Colors.white54, size: 14),
                      ],
                    ),
                  ),
                ),

                const SizedBox(width: 8),

                // 🇮🇳 Floating Language Switcher (हिन्दी ⇄ English)
                InkWell(
                  onTap: () => lang.toggleLanguage(),
                  borderRadius: BorderRadius.circular(20),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4.5),
                    decoration: BoxDecoration(
                      color: const Color(0xFF0F172A).withValues(alpha: 0.9),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.6), width: 1.2),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.35),
                          blurRadius: 8,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          lang.isHindi ? '🇮🇳 हिन्दी' : '🇬🇧 EN',
                          style: const TextStyle(color: ManagerTheme.brandGold, fontSize: 11, fontWeight: FontWeight.bold),
                        ),
                        const SizedBox(width: 4),
                        const Icon(Icons.swap_horiz_rounded, color: ManagerTheme.brandGold, size: 14),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),

      // Scrollable Bottom Bar with Pinned 31 HUBS Launcher Dock
      bottomNavigationBar: Container(
        height: 64,
        decoration: BoxDecoration(
          color: const Color(0xFF0B141A),
          border: const Border(
            top: BorderSide(color: Color(0xFF1E293B), width: 1),
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.4),
              blurRadius: 10,
              offset: const Offset(0, -2),
            ),
          ],
        ),
        child: Row(
          children: [
            // Pinned 31 HUBS Dock Button
            InkWell(
              onTap: () => _showMasterHubLauncher(context, session),
              child: Container(
                margin: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFFF59E0B), Color(0xFFD97706)],
                  ),
                  borderRadius: BorderRadius.circular(10),
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFFF59E0B).withValues(alpha: 0.3),
                      blurRadius: 6,
                    ),
                  ],
                ),
                child: const Column(
                  mainAxisSize: MainAxisSize.min,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(Icons.dashboard_customize_rounded, color: Color(0xFF0B0F19), size: 18),
                    SizedBox(height: 2),
                    Text(
                      '31 HUBS',
                      style: TextStyle(
                        color: Color(0xFF0B0F19),
                        fontSize: 9,
                        fontWeight: FontWeight.w900,
                        letterSpacing: 0.4,
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const VerticalDivider(width: 1, color: Color(0xFF1E293B)),
            Expanded(
              child: ListView.builder(
                controller: _navScrollController,
                scrollDirection: Axis.horizontal,
                itemCount: allowedDestinations.length,
                itemBuilder: (ctx, i) {
                  final dest = allowedDestinations[i];
                  final isSelected = _currentIndex == i;
                  final badgeCount = (dest['key'] == 'dialer') ? callProvider.pendingQueue.length : 0;
                  final labelKey = dest['key'] as String? ?? '';
                  final label = lang.t(labelKey);

                  return InkWell(
                    onTap: () => _onTabSelected(i),
                    child: Container(
                      width: 80,
                      padding: const EdgeInsets.symmetric(vertical: 8),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Stack(
                            clipBehavior: Clip.none,
                            children: [
                              Icon(
                                dest['icon'] as IconData,
                                color: isSelected ? dest['color'] as Color : Colors.white54,
                                size: isSelected ? 22 : 19,
                              ),
                              if (badgeCount > 0)
                                Positioned(
                                  right: -6,
                                  top: -4,
                                  child: Container(
                                    padding: const EdgeInsets.all(3),
                                    decoration: const BoxDecoration(
                                      color: Colors.redAccent,
                                      shape: BoxShape.circle,
                                    ),
                                    constraints: const BoxConstraints(minWidth: 14, minHeight: 14),
                                    child: Text(
                                      '$badgeCount',
                                      style: const TextStyle(color: Colors.white, fontSize: 8, fontWeight: FontWeight.bold),
                                      textAlign: TextAlign.center,
                                    ),
                                  ),
                                ),
                            ],
                          ),
                          const SizedBox(height: 3),
                          Text(
                            label,
                            style: TextStyle(
                              color: isSelected ? dest['color'] as Color : Colors.white54,
                              fontSize: 9.5,
                              fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}
