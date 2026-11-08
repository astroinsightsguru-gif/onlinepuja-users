import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/call_provider.dart';
import '../../providers/language_provider.dart';
import '../../services/manager_auth_session.dart';
import '../../theme/manager_theme.dart';
import 'astrologers/astrologers_hub_screen.dart';
import 'b2b/b2b_partner_hub_screen.dart';
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
      'screen': const LeadsPipelineScreen(),
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

          // Top Floating Bar (Language Switcher + User Profile Pill)
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

      // Scrollable Bottom Bar
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
    );
  }
}
