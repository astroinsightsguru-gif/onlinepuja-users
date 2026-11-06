import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/call_provider.dart';
import '../../theme/manager_theme.dart';
import 'crm/leads_pipeline_screen.dart';
import 'dashboard/live_radar_screen.dart';
import 'dialer/dialer_screen.dart';
import 'finance/finance_hub_screen.dart';
import 'fulfillment/sankalp_fulfillment_screen.dart';
import 'growth/growth_ai_cockpit_screen.dart';
import 'agents/paperclip_squad_screen.dart';
import 'quotes/quotation_desk_screen.dart';
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

  late final List<Widget> _screens;

  final List<Map<String, dynamic>> _destinations = [
    {'label': 'Radar', 'icon': Icons.radar_rounded, 'color': ManagerTheme.brandGold},
    {'label': 'Dialer', 'icon': Icons.phone_rounded, 'color': const Color(0xFF25D366)},
    {'label': 'WhatsApp', 'icon': Icons.chat_bubble_rounded, 'color': const Color(0xFF25D366)},
    {'label': 'Devotees', 'icon': Icons.people_alt_rounded, 'color': ManagerTheme.brandGold},
    {'label': 'Quotes', 'icon': Icons.description_rounded, 'color': ManagerTheme.brandGold},
    {'label': 'Sankalp', 'icon': Icons.local_shipping_rounded, 'color': const Color(0xFF60A5FA)},
    {'label': 'AI Squad', 'icon': Icons.hub_rounded, 'color': const Color(0xFFA78BFA)},
    {'label': 'Growth AI', 'icon': Icons.auto_awesome_rounded, 'color': ManagerTheme.brandGold},
    {'label': 'Finance', 'icon': Icons.account_balance_wallet_rounded, 'color': const Color(0xFF10B981)},
  ];

  @override
  void initState() {
    super.initState();
    _screens = [
      const LiveRadarScreen(),
      const DialerScreen(),
      const WhatsAppInboxScreen(),
      const LeadsPipelineScreen(),
      const QuotationDeskScreen(),
      const SankalpFulfillmentScreen(),
      const PaperclipSquadScreen(embedded: true),
      const GrowthAiCockpitScreen(),
      const FinanceHubScreen(),
    ];
  }

  @override
  void dispose() {
    _navScrollController.dispose();
    super.dispose();
  }

  void _onTabSelected(int index) {
    setState(() => _currentIndex = index);

    if (_navScrollController.hasClients) {
      final screenWidth = MediaQuery.of(context).size.width;
      const itemWidth = 76.0;
      final targetOffset = (index * itemWidth) - (screenWidth / 2) + (itemWidth / 2);
      _navScrollController.animateTo(
        targetOffset.clamp(0.0, _navScrollController.position.maxScrollExtent),
        duration: const Duration(milliseconds: 250),
        curve: Curves.easeOutCubic,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final callProvider = Provider.of<CallProvider>(context);

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
            children: _screens,
          ),

          // Active In-Call Floating HUD
          const Positioned(
            top: 0,
            left: 0,
            right: 0,
            child: ActiveCallHud(),
          ),
        ],
      ),
      bottomNavigationBar: Container(
        height: 64,
        decoration: BoxDecoration(
          color: ManagerTheme.surfaceDark,
          border: const Border(top: BorderSide(color: Colors.white10, width: 0.8)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.3),
              blurRadius: 10,
              offset: const Offset(0, -2),
            ),
          ],
        ),
        child: ListView.builder(
          controller: _navScrollController,
          scrollDirection: Axis.horizontal,
          itemCount: _destinations.length,
          itemBuilder: (ctx, i) {
            final dest = _destinations[i];
            final isSelected = _currentIndex == i;
            final badgeCount = (i == 1)
                ? callProvider.pendingQueue.length
                : 0;

            return InkWell(
              onTap: () => _onTabSelected(i),
              child: Container(
                width: 76,
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
                          size: isSelected ? 24 : 20,
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
                    const SizedBox(height: 4),
                    Text(
                      dest['label'] as String,
                      style: TextStyle(
                        color: isSelected ? dest['color'] as Color : Colors.white54,
                        fontSize: 10,
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
