import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:op_shared/op_shared.dart';
import 'package:provider/provider.dart';

import '../../../state/app_session.dart';
import '../../theme/partner_theme.dart';
import '../../widgets/partner_widgets.dart';
import '../ai_twin/ai_twin_studio_screen.dart';
import '../auth/login_screen.dart';
import '../call/call_session_screen.dart';
import '../chat/chat_session_screen.dart';
import '../chat/master_ai_chatbot_screen.dart';
import '../orders/orders_fulfillment_screen.dart';
import '../profile/profile_screen.dart';
import '../social/astrologer_social_growth_screen.dart';
import '../tools/vedic_toolkit_screen.dart';
import '../wallet/wallet_screen.dart';
import 'availability_screen.dart';
import 'requests_screen.dart';

/// Partner home: Executive Astrologer Command Center.
/// Features a modern Dashboard, Live Requests hub, Order Fulfillment,
/// Wallet & Earnings, and Astrologer Profile, alongside a real-time
/// ringing listener for incoming calls and chats.
class HomeShell extends StatefulWidget {
  const HomeShell({super.key});

  static const route = '/shell';

  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> {
  int _tab = 0;
  Timer? _ringerPoller;
  bool _isRinging = false;
  final Set<String> _handledRequests = {};

  @override
  void initState() {
    super.initState();
    // Poll for incoming calls / chats every 4 seconds when astrologer is Online
    _ringerPoller = Timer.periodic(
        const Duration(seconds: 4), (_) => _pollIncomingConsultations());
  }

  @override
  void dispose() {
    _ringerPoller?.cancel();
    super.dispose();
  }

  Future<void> _pollIncomingConsultations() async {
    if (!mounted || _isRinging) return;
    final session = context.read<PartnerSession>();
    if (!session.isLoggedIn || session.astrologerId <= 0) return;
    if (session.chatStatus != 'Online' && session.callStatus != 'Online') return;

    try {
      // 1. Check incoming calls
      if (session.callStatus == 'Online') {
        final calls = await PartnerApi.instance
            .callRequests(astrologerId: session.astrologerId);
        for (final c in calls) {
          final id = (c['callId'] ?? c['id'] ?? c['_id'])?.toString() ?? '';
          final status = (c['callStatus'] ?? c['status'] ?? '').toString().toLowerCase();
          if (id.isNotEmpty &&
              !_handledRequests.contains('call_$id') &&
              (status.isEmpty || status == 'pending' || status == 'waiting')) {
            _handledRequests.add('call_$id');
            _triggerIncomingDialog(c, isCall: true);
            return;
          }
        }
      }

      // 2. Check incoming chats
      if (session.chatStatus == 'Online') {
        final chats = await PartnerApi.instance
            .chatRequests(astrologerId: session.astrologerId);
        for (final ch in chats) {
          final id = (ch['chatId'] ?? ch['id'] ?? ch['_id'])?.toString() ?? '';
          final status = (ch['chatStatus'] ?? ch['status'] ?? '').toString().toLowerCase();
          if (id.isNotEmpty &&
              !_handledRequests.contains('chat_$id') &&
              (status.isEmpty || status == 'pending' || status == 'waiting')) {
            _handledRequests.add('chat_$id');
            _triggerIncomingDialog(ch, isCall: false);
            return;
          }
        }
      }
    } catch (_) {
      // Silent catch for background polling
    }
  }

  void _triggerIncomingDialog(Map<String, dynamic> req,
      {required bool isCall}) {
    if (!mounted || _isRinging) return;
    setState(() => _isRinging = true);

    HapticFeedback.heavyImpact();

    final rawName = (req['userName'] ??
            req['customerName'] ??
            req['name'] ??
            '')
        .toString()
        .trim();
    final contactNo = (req['contactNo'] ?? req['mobile'] ?? req['phone'])
        ?.toString()
        .trim();
    final name = rawName.isNotEmpty
        ? rawName
        : (contactNo != null && contactNo.isNotEmpty
            ? 'भक्त ($contactNo)'
            : 'देवभक्त');
    final callType = (req['call_type'] ?? req['callType'] ?? '').toString();
    final isVideo =
        isCall && (callType == '11' || callType.toLowerCase().contains('video'));
    final id = int.tryParse(
            (req[isCall ? 'callId' : 'chatId'] ?? req['id'] ?? req['_id'])
                ?.toString() ??
                '') ??
        0;
    final customerId = int.tryParse(req['userId']?.toString() ?? req['id']?.toString() ?? '') ?? 0;
    final sessionId =
        (req[isCall ? 'callId' : 'chatId'] ?? req['id'] ?? req['_id'])
            ?.toString();

    int remainingSeconds = 45;
    Timer? ringTimer;
    Timer? vibrationTimer;

    vibrationTimer = Timer.periodic(const Duration(seconds: 2), (_) {
      HapticFeedback.vibrate();
    });

    showModalBottomSheet<bool>(
      context: context,
      isDismissible: false,
      enableDrag: false,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (sheetCtx) => StatefulBuilder(
        builder: (ctx, setModalState) {
          ringTimer ??= Timer.periodic(const Duration(seconds: 1), (t) {
            if (remainingSeconds <= 1) {
              t.cancel();
              vibrationTimer?.cancel();
              if (sheetCtx.mounted) Navigator.pop(sheetCtx, false);
            } else {
              setModalState(() => remainingSeconds--);
            }
          });

          final progress = remainingSeconds / 45.0;
          final dark = Theme.of(ctx).brightness == Brightness.dark;

          return Container(
            margin: const EdgeInsets.fromLTRB(16, 16, 16, 24),
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: dark ? PartnerTheme.darkSurface : Colors.white,
              borderRadius: BorderRadius.circular(32),
              border: Border.all(
                color: PartnerTheme.gold.withValues(alpha: 0.5),
                width: 1.5,
              ),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.4),
                  blurRadius: 30,
                  offset: const Offset(0, 10),
                ),
              ],
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Top Indicator Pill
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                  decoration: BoxDecoration(
                    color: PartnerTheme.saffron.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(
                      color: PartnerTheme.saffron.withValues(alpha: 0.4),
                    ),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.ring_volume_rounded,
                          color: PartnerTheme.saffron, size: 16),
                      const SizedBox(width: 6),
                      Text(
                        'INCOMING ${isCall ? (isVideo ? "VIDEO CALL" : "AUDIO CALL") : "CHAT REQUEST"}',
                        style: const TextStyle(
                          color: PartnerTheme.saffron,
                          fontWeight: FontWeight.w800,
                          fontSize: 11,
                          letterSpacing: 0.5,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 22),

                // Pulsing Avatar with Timer Progress Ring
                Stack(
                  alignment: Alignment.center,
                  children: [
                    SizedBox(
                      width: 96,
                      height: 96,
                      child: CircularProgressIndicator(
                        value: progress,
                        strokeWidth: 4.5,
                        backgroundColor:
                            dark ? Colors.white12 : Colors.grey.shade200,
                        color: PartnerTheme.saffron,
                      ),
                    ),
                    Container(
                      width: 76,
                      height: 76,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        gradient: isCall
                            ? (isVideo
                                ? const LinearGradient(colors: [
                                    Color(0xFF8B5CF6),
                                    Color(0xFF6D28D9)
                                  ])
                                : PartnerTheme.emeraldGradient)
                            : PartnerTheme.saffronGradient,
                        boxShadow: PartnerTheme.glow(PartnerTheme.saffron,
                            blur: 14),
                      ),
                      child: Icon(
                        isCall
                            ? (isVideo
                                ? Icons.videocam_rounded
                                : Icons.call_rounded)
                            : Icons.chat_bubble_rounded,
                        color: Colors.white,
                        size: 36,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 18),

                // Customer Name
                Text(
                  name,
                  style: const TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Waiting for consultation acceptance... (${remainingSeconds}s)',
                  style: TextStyle(
                    fontSize: 12.5,
                    color: dark ? Colors.white60 : Colors.grey.shade600,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const SizedBox(height: 12),

                // Quick Horoscope details peek button
                GestureDetector(
                  onTap: () => DevoteeKundliSheet.show(ctx,
                      devoteeName: name, userId: customerId),
                  child: Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
                    decoration: BoxDecoration(
                      color: PartnerTheme.amber.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(
                        color: PartnerTheme.amber.withValues(alpha: 0.3),
                      ),
                    ),
                    child: const Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.auto_awesome,
                            size: 14, color: PartnerTheme.amber),
                        SizedBox(width: 5),
                        Text(
                          'View Devotee Horoscope Details',
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            color: PartnerTheme.amber,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 24),

                // Action Buttons: Decline vs Accept
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        style: OutlinedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          side: BorderSide(
                              color:
                                  PartnerTheme.crimson.withValues(alpha: 0.5)),
                          foregroundColor: PartnerTheme.crimson,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        icon: const Icon(Icons.call_end_rounded, size: 20),
                        label: Text(
                          AppStrings.reject,
                          maxLines: 1,
                          softWrap: false,
                          style: const TextStyle(fontWeight: FontWeight.w800),
                        ),
                        onPressed: () async {
                          ringTimer?.cancel();
                          vibrationTimer?.cancel();
                          Navigator.pop(sheetCtx, false);
                          try {
                            if (isCall) {
                              await PartnerApi.instance.rejectCallRequest(id);
                            } else {
                              await PartnerApi.instance.rejectChatRequest(id);
                            }
                          } catch (_) {}
                        },
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      flex: 2,
                      child: Container(
                        height: 50,
                        decoration: BoxDecoration(
                          gradient: PartnerTheme.emeraldGradient,
                          borderRadius: BorderRadius.circular(16),
                          boxShadow: PartnerTheme.glow(PartnerTheme.emerald,
                              blur: 14),
                        ),
                        child: Material(
                          color: Colors.transparent,
                          child: InkWell(
                            borderRadius: BorderRadius.circular(16),
                            onTap: () async {
                              ringTimer?.cancel();
                              vibrationTimer?.cancel();
                              Navigator.pop(sheetCtx, true);

                              try {
                                if (isCall) {
                                  await PartnerApi.instance
                                      .acceptCallRequest(id);
                                  if (mounted) {
                                    Navigator.of(context).pushNamed(
                                      CallSessionScreen.route,
                                      arguments: {
                                        'customerId': customerId,
                                        'customerName': name,
                                        'sessionId': sessionId,
                                        'isVideo': isVideo,
                                      },
                                    );
                                  }
                                } else {
                                  await PartnerApi.instance
                                      .acceptChatRequest(id);
                                  if (mounted) {
                                    Navigator.of(context).pushNamed(
                                      ChatSessionScreen.route,
                                      arguments: {
                                        'customerId': customerId,
                                        'customerName': name,
                                        'sessionId': sessionId,
                                      },
                                    );
                                  }
                                }
                              } catch (e) {
                                if (mounted) {
                                  showSnack(context, 'Accept failed: $e',
                                      error: true);
                                }
                              }
                            },
                            child: Center(
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  const Icon(Icons.check_circle_rounded,
                                      color: Colors.white, size: 22),
                                  const SizedBox(width: 8),
                                  Text(
                                    AppStrings.acceptAndStart,
                                    style: const TextStyle(
                                      color: Colors.white,
                                      fontWeight: FontWeight.w800,
                                      fontSize: 14,
                                      letterSpacing: 0.5,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          );
        },
      ),
    ).then((accepted) {
      ringTimer?.cancel();
      vibrationTimer?.cancel();
      if (mounted) setState(() => _isRinging = false);
    });
  }

  @override
  Widget build(BuildContext context) {
    final session = context.watch<PartnerSession>();
    final dark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      appBar: AppBar(
        titleSpacing: 16,
        title: Row(
          children: [
            AstrologerAvatar(
              name: session.user?.displayName ?? 'Acharya',
              radius: 18,
              isOnline: session.chatStatus == 'Online',
              showBadge: false,
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    session.user?.displayName ?? 'Acharya Ji',
                    style: const TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w800,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  Text(
                    AppStrings.verifiedAstrologer,
                    style: const TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w500,
                      color: PartnerTheme.gold,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
        actions: [
          // Online / Offline Instant Switch Pill
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 10),
            child: StatusPill(
              isOnline: session.chatStatus == 'Online',
              onTap: () async {
                final isOnline = session.chatStatus == 'Online';
                final next = isOnline ? 'Offline' : 'Online';
                await session.setStatus(chat: next, call: next);
                if (context.mounted) {
                  showSnack(
                    context,
                    isOnline
                        ? 'Status set to Offline. Incoming calls paused.'
                        : 'Status set to Online! Ready to accept consultations.',
                  );
                }
              },
            ),
          ),
          const SizedBox(width: 6),

          // Wallet Action
          IconButton(
            tooltip: 'Wallet & Earnings',
            icon: const Icon(Icons.account_balance_wallet_outlined),
            onPressed: () => Navigator.of(context)
                .pushNamed(WalletScreen.route)
                .then((_) => session.refreshUser()),
          ),
          const SizedBox(width: 4),

          // Language Switcher Action (Hindi / English / 15 Indian Languages)
          IconButton(
            tooltip: 'Language / भाषा',
            icon: const Icon(Icons.translate_rounded),
            onPressed: () => LanguagePickerSheet.show(context),
          ),
          const SizedBox(width: 4),
        ],
      ),
      drawer: _drawer(context, session, dark),
      body: IndexedStack(
        index: _tab,
        children: [
          _dashboardView(context, session, dark),
          const RequestsScreen(kind: 'all'),
          const OrdersFulfillmentScreen(),
          const WalletScreen(),
          const ProfileScreen(),
        ],
      ),
      bottomNavigationBar: Container(
        decoration: BoxDecoration(
          color: dark ? PartnerTheme.darkSurface : Colors.white,
          border: Border(
            top: BorderSide(
              color: dark ? PartnerTheme.darkBorder : const Color(0xFFEBE3D7),
              width: 1,
            ),
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.05),
              blurRadius: 10,
              offset: const Offset(0, -3),
            ),
          ],
        ),
        child: NavigationBar(
          selectedIndex: _tab,
          onDestinationSelected: (i) => setState(() => _tab = i),
          destinations: [
            NavigationDestination(
              icon: const Icon(Icons.dashboard_outlined),
              selectedIcon: const Icon(Icons.dashboard_rounded),
              label: AppStrings.overview,
            ),
            NavigationDestination(
              icon: const Icon(Icons.call_outlined),
              selectedIcon: const Icon(Icons.call_rounded),
              label: AppStrings.requests,
            ),
            NavigationDestination(
              icon: const Icon(Icons.temple_hindu_outlined),
              selectedIcon: const Icon(Icons.temple_hindu_rounded),
              label: AppStrings.fulfillment,
            ),
            NavigationDestination(
              icon: const Icon(Icons.account_balance_wallet_outlined),
              selectedIcon: const Icon(Icons.account_balance_wallet_rounded),
              label: AppStrings.earnings,
            ),
            NavigationDestination(
              icon: const Icon(Icons.person_outline_rounded),
              selectedIcon: const Icon(Icons.person_rounded),
              label: AppStrings.profile,
            ),
          ],
        ),
      ),
    );
  }

  Widget _dashboardView(
      BuildContext context, PartnerSession session, bool dark) {
    final balance = session.user?.walletAmount ?? 0;
    final isOnline = session.chatStatus == 'Online';

    return RefreshIndicator(
      onRefresh: () async => session.refreshUser(),
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 28),
        children: [
          // Hero Earnings Card
          PartnerCard(
            gradient: dark
                ? PartnerTheme.darkCardGradient
                : const LinearGradient(
                    colors: [Color(0xFFFFF7ED), Color(0xFFFEF3C7)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
            borderColor: PartnerTheme.gold.withValues(alpha: 0.4),
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Text(
                        AppStrings.availableBalance,
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w800,
                          letterSpacing: 0.8,
                          color: dark ? Colors.white60 : Colors.black54,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    const SizedBox(width: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: PartnerTheme.emerald.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.trending_up_rounded,
                              size: 14, color: PartnerTheme.emerald),
                          const SizedBox(width: 4),
                          Text(
                            AppStrings.activeAccount,
                            style: const TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w700,
                              color: PartnerTheme.emerald,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  '${session.flags.currency}${balance.toStringAsFixed(2)}',
                  style: const TextStyle(
                    fontSize: 32,
                    fontWeight: FontWeight.w900,
                    letterSpacing: -1,
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: Container(
                        height: 44,
                        decoration: BoxDecoration(
                          gradient: PartnerTheme.saffronGradient,
                          borderRadius: BorderRadius.circular(12),
                          boxShadow: PartnerTheme.glow(PartnerTheme.saffron,
                              blur: 8),
                        ),
                        child: Material(
                          color: Colors.transparent,
                          child: InkWell(
                            borderRadius: BorderRadius.circular(12),
                            onTap: () => setState(() => _tab = 3),
                            child: Padding(
                              padding: const EdgeInsets.symmetric(horizontal: 10),
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  const Icon(Icons.account_balance_wallet_rounded,
                                      size: 17, color: Colors.white),
                                  const SizedBox(width: 6),
                                  Flexible(
                                    child: Text(
                                      AppStrings.withdrawFunds,
                                      style: const TextStyle(
                                        fontSize: 13,
                                        fontWeight: FontWeight.w800,
                                        color: Colors.white,
                                      ),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: SizedBox(
                        height: 44,
                        child: OutlinedButton(
                          style: OutlinedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(horizontal: 10),
                            side: BorderSide(
                              color: dark
                                  ? PartnerTheme.darkBorder
                                  : const Color(0xFFDDD3C4),
                              width: 1.2,
                            ),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                          ),
                          onPressed: () => setState(() => _tab = 3),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Icon(Icons.receipt_long_rounded, size: 17),
                              const SizedBox(width: 6),
                              Flexible(
                                child: Text(
                                  AppStrings.statements,
                                  style: const TextStyle(
                                    fontSize: 13,
                                    fontWeight: FontWeight.w700,
                                  ),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // Online Status Radar Banner
          PartnerCard(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            borderColor: isOnline
                ? PartnerTheme.emerald.withValues(alpha: 0.4)
                : Colors.grey.withValues(alpha: 0.3),
            child: Row(
              children: [
                Container(
                  width: 42,
                  height: 42,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: isOnline
                        ? PartnerTheme.emerald.withValues(alpha: 0.15)
                        : Colors.grey.withValues(alpha: 0.15),
                  ),
                  child: Icon(
                    isOnline
                        ? Icons.sensors_rounded
                        : Icons.sensors_off_rounded,
                    color: isOnline ? PartnerTheme.emerald : Colors.grey,
                    size: 22,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        isOnline
                            ? AppStrings.readyForConsultations
                            : AppStrings.currentlyOffline,
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w800,
                          color: isOnline ? PartnerTheme.emerald : Colors.grey,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        isOnline
                            ? AppStrings.ringingListenerActive
                            : AppStrings.toggleOnlinePrompt,
                        style: TextStyle(
                          fontSize: 11.5,
                          color: dark ? Colors.white60 : Colors.black54,
                        ),
                      ),
                    ],
                  ),
                ),
                Switch(
                  value: isOnline,
                  activeThumbColor: PartnerTheme.emerald,
                  onChanged: (v) => session.setStatus(
                    chat: v ? 'Online' : 'Offline',
                    call: v ? 'Online' : 'Offline',
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // 🚀 AI Social Media Growth Hub Card
          PartnerCard(
            padding: const EdgeInsets.all(16),
            gradient: dark
                ? const LinearGradient(
                    colors: [Color(0xFF1E1B4B), Color(0xFF0F172A)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  )
                : const LinearGradient(
                    colors: [Color(0xFFFFF7ED), Color(0xFFFEF3C7)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
            borderColor: PartnerTheme.gold.withValues(alpha: 0.5),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: const BoxDecoration(
                            gradient: PartnerTheme.saffronGradient,
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(Icons.rocket_launch_rounded, color: Colors.white, size: 18),
                        ),
                        const SizedBox(width: 10),
                        const Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'AI सोशल मीडिया ग्रोथ हब',
                              style: TextStyle(fontSize: 14, fontWeight: FontWeight.w900),
                            ),
                            Text(
                              'Auto-Grow FB, Instagram & YouTube',
                              style: TextStyle(fontSize: 10.5, color: Colors.grey),
                            ),
                          ],
                        ),
                      ],
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2.5),
                      decoration: BoxDecoration(
                        color: PartnerTheme.emerald.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: const Text(
                        'AUTOPILOT ON',
                        style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: PartnerTheme.emerald),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                Text(
                  '1-Click Login से अपने Facebook, Instagram एवं YouTube को कनेक्ट करें। AI Brain आपके नाम, फोटो व QR कोड के साथ दैनिक रील्स (Reels) एवं महा उपाय पोस्टर्स बनाकर फॉलोअर्स बढ़ाएगा।',
                  style: TextStyle(fontSize: 11.5, color: dark ? Colors.white70 : Colors.black87, height: 1.35),
                ),
                const SizedBox(height: 12),
                SizedBox(
                  width: double.infinity,
                  height: 40,
                  child: ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: PartnerTheme.gold,
                      foregroundColor: Colors.black,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    icon: const Icon(Icons.auto_awesome_rounded, size: 16),
                    label: const Text(
                      'ग्रोथ स्टूडियो खोलें (Open Studio)',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5),
                    ),
                    onPressed: () => Navigator.pushNamed(context, AstrologerSocialGrowthScreen.route),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // 🕉️ Pandit Vedic Toolkit Card
          PartnerCard(
            padding: const EdgeInsets.all(16),
            gradient: dark
                ? const LinearGradient(
                    colors: [Color(0xFF2D1B36), Color(0xFF191224)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  )
                : const LinearGradient(
                    colors: [Color(0xFFFFFBEB), Color(0xFFFEF3C7)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
            borderColor: PartnerTheme.saffron.withValues(alpha: 0.5),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: const BoxDecoration(
                            gradient: PartnerTheme.saffronGradient,
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(Icons.auto_stories_rounded, color: Colors.white, size: 18),
                        ),
                        const SizedBox(width: 10),
                        const Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'वैदिक टूलकिट (Vedic Toolkit)',
                              style: TextStyle(fontSize: 14, fontWeight: FontWeight.w900),
                            ),
                            Text(
                              'कुंडली, पंचांग व पूजा संस्तुति',
                              style: TextStyle(fontSize: 10.5, color: Colors.grey),
                            ),
                          ],
                        ),
                      ],
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2.5),
                      decoration: BoxDecoration(
                        color: PartnerTheme.saffron.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: const Text(
                        'PANDIT TOOLS',
                        style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: PartnerTheme.saffron),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                Text(
                  'कॉल व परामर्श के समय यजमान की 10-पेज संपूर्ण वैदिक कुंडली PDF बनाएं, दैनिक पंचांग/शुभ मुहूर्त देखें अथवा व्हाट्सएप पर पूजा संस्तुति भेजें।',
                  style: TextStyle(fontSize: 11.5, color: dark ? Colors.white70 : Colors.black87, height: 1.35),
                ),
                const SizedBox(height: 12),
                SizedBox(
                  width: double.infinity,
                  height: 40,
                  child: ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: PartnerTheme.saffron,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    icon: const Icon(Icons.stars_rounded, size: 16),
                    label: const Text(
                      'टूलकिट खोलें (Open Vedic Toolkit)',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5),
                    ),
                    onPressed: () => Navigator.pushNamed(context, VedicToolkitScreen.route),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // 🤖 Master AI Jyotish Assistant Card
          PartnerCard(
            padding: const EdgeInsets.all(16),
            gradient: dark
                ? const LinearGradient(
                    colors: [Color(0xFF0F2027), Color(0xFF203A43), Color(0xFF2C5364)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  )
                : const LinearGradient(
                    colors: [Color(0xFFECFDF5), Color(0xFFD1FAE5)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
            borderColor: const Color(0xFF10B981).withValues(alpha: 0.5),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: const BoxDecoration(
                            color: Color(0xFF059669),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(Icons.auto_awesome_rounded, color: Colors.white, size: 18),
                        ),
                        const SizedBox(width: 10),
                        const Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'मास्टर AI ज्योतिषी (Master Co-Pilot)',
                              style: TextStyle(fontSize: 14, fontWeight: FontWeight.w900),
                            ),
                            Text(
                              'पंडित जी का 24x7 वैदिक AI सहायक',
                              style: TextStyle(fontSize: 10.5, color: Colors.grey),
                            ),
                          ],
                        ),
                      ],
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2.5),
                      decoration: BoxDecoration(
                        color: const Color(0xFF059669).withValues(alpha: 0.2),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: const Text(
                        'AI CO-PILOT',
                        style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: Color(0xFF059669)),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                Text(
                  'जटिल कुंडली दोष (कालसर्प, मांगलिक, साढ़े साती), शुभ मुहूर्त गणना एवं वेदोक्त पूजा संस्तुतियों पर तुरंत AI परामर्श प्राप्त करें और 1-क्लिक में यजमान को व्हाट्सएप भेजें।',
                  style: TextStyle(fontSize: 11.5, color: dark ? Colors.white70 : Colors.black87, height: 1.35),
                ),
                const SizedBox(height: 12),
                SizedBox(
                  width: double.infinity,
                  height: 40,
                  child: ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF059669),
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    icon: const Icon(Icons.chat_bubble_outline_rounded, size: 16),
                    label: const Text(
                      'AI ज्योतिषी से पूछें (Ask Master AI)',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5),
                    ),
                    onPressed: () => Navigator.pushNamed(context, MasterAiChatbotScreen.route),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 14),

          // Vedic AI Twin & Social Media Autopilot Cards
          Row(
            children: [
              Expanded(
                child: InkWell(
                  onTap: () => Navigator.pushNamed(context, AiTwinStudioScreen.route),
                  borderRadius: BorderRadius.circular(14),
                  child: Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [
                          const Color(0xFFF59E0B).withValues(alpha: dark ? 0.2 : 0.12),
                          const Color(0xFFD97706).withValues(alpha: dark ? 0.1 : 0.05),
                        ],
                      ),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: const Color(0xFFF59E0B).withValues(alpha: 0.3)),
                    ),
                    child: const Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Icon(Icons.smart_toy_rounded, color: Color(0xFFF59E0B), size: 18),
                            SizedBox(width: 6),
                            Text('AI ट्विन स्टूडियो', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 13)),
                          ],
                        ),
                        SizedBox(height: 4),
                        Text('२४/७ अवतार व ६०% आय', style: TextStyle(fontSize: 10.5, color: Colors.grey)),
                      ],
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: InkWell(
                  onTap: () => Navigator.pushNamed(context, AstrologerSocialGrowthScreen.route),
                  borderRadius: BorderRadius.circular(14),
                  child: Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [
                          const Color(0xFFEA580C).withValues(alpha: dark ? 0.2 : 0.12),
                          const Color(0xFFC2410C).withValues(alpha: dark ? 0.1 : 0.05),
                        ],
                      ),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: const Color(0xFFEA580C).withValues(alpha: 0.3)),
                    ),
                    child: const Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Icon(Icons.hub_rounded, color: Color(0xFFEA580C), size: 18),
                            SizedBox(width: 6),
                            Text('सोशल ग्रोथ इंजन', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 13)),
                          ],
                        ),
                        SizedBox(height: 4),
                        Text('FB, Insta, YT रील्स', style: TextStyle(fontSize: 10.5, color: Colors.grey)),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),

          // Astrologer Performance Grid
          Text(
            AppStrings.practiceSummary,
            style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 10),
          GridView.count(
            crossAxisCount: 2,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            mainAxisSpacing: 10,
            crossAxisSpacing: 10,
            childAspectRatio: 1.25,
            children: [
              StatTile(
                icon: Icons.phone_in_talk_rounded,
                iconColor: PartnerTheme.emerald,
                label: AppStrings.consultationCalls,
                value: '4 ${AppStrings.completed}',
                subtext: '98% ${AppStrings.responseRate}',
                onTap: () => setState(() => _tab = 1),
              ),
              StatTile(
                icon: Icons.chat_bubble_rounded,
                iconColor: PartnerTheme.saffron,
                label: AppStrings.chatSessions,
                value: '7 ${AppStrings.completed}',
                subtext: '${AppStrings.avgDuration} 12m',
                onTap: () => setState(() => _tab = 1),
              ),
              StatTile(
                icon: Icons.temple_hindu_rounded,
                iconColor: PartnerTheme.amber,
                label: AppStrings.pujaFulfillment,
                value: '2 ${AppStrings.scheduled}',
                subtext: AppStrings.pending,
                onTap: () => setState(() => _tab = 2),
              ),
              StatTile(
                icon: Icons.star_rounded,
                iconColor: PartnerTheme.gold,
                label: AppStrings.devoteeRating,
                value: '4.9 ★',
                subtext: '1,420+ ${AppStrings.reviewsShort}',
                onTap: () => setState(() => _tab = 4),
              ),
            ],
          ),
          const SizedBox(height: 20),

          // Quick Navigation Hub
          Text(
            AppStrings.managementShortcuts,
            style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 10),
          PartnerCard(
            padding: const EdgeInsets.symmetric(vertical: 6),
            child: Column(
              children: [
                ListTile(
                  leading: Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: PartnerTheme.saffron.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(Icons.toggle_on_outlined,
                        color: PartnerTheme.saffron, size: 20),
                  ),
                  title: Text(AppStrings.availabilityHours,
                      style: const TextStyle(fontWeight: FontWeight.w700)),
                  subtitle: Text(AppStrings.consultationStatus),
                  trailing: const Icon(Icons.chevron_right_rounded),
                  onTap: () =>
                      Navigator.pushNamed(context, AvailabilityScreen.route),
                ),
                const Divider(height: 1, indent: 64),
                ListTile(
                  leading: Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: PartnerTheme.amber.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(Icons.assignment_outlined,
                        color: PartnerTheme.amber, size: 20),
                  ),
                  title: Text(AppStrings.orderFulfillmentTitle,
                      style: const TextStyle(fontWeight: FontWeight.w700)),
                  subtitle: Text(AppStrings.pujaFulfillment),
                  trailing: const Icon(Icons.chevron_right_rounded),
                  onTap: () => setState(() => _tab = 2),
                ),
                const Divider(height: 1, indent: 64),
                ListTile(
                  leading: Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: PartnerTheme.purple.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(Icons.auto_awesome,
                        color: PartnerTheme.purple, size: 20),
                  ),
                  title: Text(AppStrings.devoteeKundli,
                      style: const TextStyle(fontWeight: FontWeight.w700)),
                  subtitle: Text(AppStrings.viewKundli),
                  trailing: const Icon(Icons.chevron_right_rounded),
                  onTap: () => _showDevoteeKundliDialog(context),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  void _showDevoteeKundliDialog(BuildContext context) {
    final controller = TextEditingController();
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Row(
          children: [
            Icon(Icons.auto_awesome, color: PartnerTheme.purple),
            SizedBox(width: 8),
            Text('यजमान कुंडली खोज', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'जिस भक्त या यजमान की जन्मपत्रिका देखनी हो, उनका नाम दर्ज करें:',
              style: TextStyle(fontSize: 13),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: controller,
              autofocus: true,
              decoration: InputDecoration(
                hintText: 'उदा. अमित शर्मा (Amit Sharma)',
                prefixIcon: const Icon(Icons.person_outline_rounded),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('रद्द करें'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: PartnerTheme.purple,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            onPressed: () {
              final name = controller.text.trim();
              Navigator.pop(ctx);
              DevoteeKundliSheet.show(
                context,
                devoteeName: name.isNotEmpty ? name : 'यजमान (Devotee)',
              );
            },
            child: const Text('कुंडली खोलें'),
          ),
        ],
      ),
    );
  }

  Widget _drawer(BuildContext context, PartnerSession session, bool dark) {
    final u = session.user;
    return Drawer(
      child: SafeArea(
        child: ListView(
          padding: EdgeInsets.zero,
          children: [
            Container(
              padding: const EdgeInsets.fromLTRB(20, 24, 20, 20),
              decoration: const BoxDecoration(
                gradient: PartnerTheme.saffronGradient,
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  AstrologerAvatar(
                    name: u?.displayName ?? 'Acharya',
                    radius: 32,
                    isOnline: session.chatStatus == 'Online',
                  ),
                  const SizedBox(height: 14),
                  Text(
                    u?.displayName ?? 'Acharya Ji',
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w900,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '${session.chatStatus} · ${session.callStatus}',
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.white.withValues(alpha: 0.85),
                    ),
                  ),
                ],
              ),
            ),

            // --- Section 1: मुख्य सेवाएं (Core Services) ---
            _drawerSectionHeader('मुख्य सेवाएं'),
            ListTile(
              dense: true,
              leading: const Icon(Icons.dashboard_rounded, color: PartnerTheme.gold, size: 22),
              title: Text(AppStrings.overview, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
              onTap: () {
                Navigator.pop(context);
                setState(() => _tab = 0);
              },
            ),
            ListTile(
              dense: true,
              leading: const Icon(Icons.headset_mic_rounded, color: Color(0xFF38BDF8), size: 22),
              title: Text(AppStrings.consultationRequests, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
              onTap: () {
                Navigator.pop(context);
                setState(() => _tab = 1);
              },
            ),
            ListTile(
              dense: true,
              leading: const Icon(Icons.temple_hindu_rounded, color: PartnerTheme.saffron, size: 22),
              title: Text(AppStrings.orderFulfillmentTitle, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
              subtitle: Text(AppStrings.pujaFulfillment, style: const TextStyle(fontSize: 11, color: Colors.white60)),
              onTap: () {
                Navigator.pop(context);
                setState(() => _tab = 2);
              },
            ),
            ListTile(
              dense: true,
              leading: const Icon(Icons.account_balance_wallet_rounded, color: Color(0xFF10B981), size: 22),
              title: Text(AppStrings.earningsWallet, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
              onTap: () {
                Navigator.pop(context);
                setState(() => _tab = 3);
              },
            ),

            const Divider(height: 16, thickness: 0.6, indent: 16, endIndent: 16),

            // --- Section 2: AI एवं डिजिटल ग्रोथ (AI & Growth Hub) ---
            _drawerSectionHeader('AI एवं डिजिटल ग्रोथ'),
            ListTile(
              dense: true,
              leading: const Icon(Icons.rocket_launch_rounded, color: PartnerTheme.gold, size: 22),
              title: const Text('सोशल मीडिया ऑटोपायलट', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
              subtitle: const Text('FB, Instagram व YouTube पर स्वतः रील्स व पोस्ट्स', style: TextStyle(fontSize: 11, color: Colors.white60)),
              trailing: Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: PartnerTheme.gold.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(6),
                  border: Border.all(color: PartnerTheme.gold, width: 0.8),
                ),
                child: const Text('AUTO', style: TextStyle(fontSize: 9, fontWeight: FontWeight.w800, color: PartnerTheme.gold)),
              ),
              onTap: () {
                Navigator.pop(context);
                Navigator.pushNamed(context, AstrologerSocialGrowthScreen.route);
              },
            ),
            ListTile(
              dense: true,
              leading: const Icon(Icons.smart_toy_rounded, color: Color(0xFFF59E0B), size: 22),
              title: const Text('वैदिक AI ट्विन स्टूडियो', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
              subtitle: const Text('24/7 डिजिटल वैदिक अवतार', style: TextStyle(fontSize: 11, color: Colors.white60)),
              onTap: () {
                Navigator.pop(context);
                Navigator.pushNamed(context, AiTwinStudioScreen.route);
              },
            ),
            ListTile(
              dense: true,
              leading: const Icon(Icons.auto_awesome_rounded, color: Color(0xFF10B981), size: 22),
              title: const Text('मास्टर AI ज्योतिषी', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
              subtitle: const Text('पंडित जी का वैदिक AI सह-पायलट', style: TextStyle(fontSize: 11, color: Colors.white60)),
              onTap: () {
                Navigator.pop(context);
                Navigator.pushNamed(context, MasterAiChatbotScreen.route);
              },
            ),

            const Divider(height: 16, thickness: 0.6, indent: 16, endIndent: 16),

            // --- Section 3: वैदिक टूल्स (Vedic Tools) ---
            _drawerSectionHeader('वैदिक टूल्स'),
            ListTile(
              dense: true,
              leading: const Icon(Icons.auto_stories_rounded, color: PartnerTheme.saffron, size: 22),
              title: const Text('वैदिक टूलकिट', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
              subtitle: const Text('कुंडली, पंचांग व पूजा संस्तुति', style: TextStyle(fontSize: 11, color: Colors.white60)),
              onTap: () {
                Navigator.pop(context);
                Navigator.pushNamed(context, VedicToolkitScreen.route);
              },
            ),

            const Divider(height: 16, thickness: 0.6, indent: 16, endIndent: 16),

            // --- Section 4: खाता एवं सेटिंग्स (Account & Settings) ---
            _drawerSectionHeader('खाता एवं सेटिंग्स'),
            ListTile(
              dense: true,
              leading: const Icon(Icons.schedule_rounded, color: Colors.white70, size: 22),
              title: Text(AppStrings.availabilityHours, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
              onTap: () {
                Navigator.pop(context);
                Navigator.pushNamed(context, AvailabilityScreen.route);
              },
            ),
            ListTile(
              dense: true,
              leading: const Icon(Icons.badge_outlined, color: Colors.white70, size: 22),
              title: Text(AppStrings.myProfile, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
              onTap: () {
                Navigator.pop(context);
                setState(() => _tab = 4);
              },
            ),
            ListTile(
              dense: true,
              leading:
                  const Icon(Icons.translate_rounded, color: PartnerTheme.saffron, size: 22),
              title: Text(AppStrings.appLanguage, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
              subtitle: Text(
                '${LocaleManager.instance.currentLanguage.value.label} (${LocaleManager.instance.currentLanguage.value.englishName})',
                style: const TextStyle(fontSize: 11, color: Colors.white60),
              ),
              onTap: () {
                Navigator.pop(context);
                LanguagePickerSheet.show(context);
              },
            ),
            const Divider(height: 16, thickness: 0.6, indent: 16, endIndent: 16),
            ListTile(
              dense: true,
              leading:
                  const Icon(Icons.logout_rounded, color: PartnerTheme.crimson, size: 22),
              title: Text(AppStrings.logout,
                  style: const TextStyle(color: PartnerTheme.crimson, fontWeight: FontWeight.w700, fontSize: 13.5)),
              onTap: () => _logout(context, session),
            ),
            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }

  Widget _drawerSectionHeader(String title) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 14, 16, 4),
      child: Text(
        title.toUpperCase(),
        style: const TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w900,
          color: PartnerTheme.gold,
          letterSpacing: 0.9,
        ),
      ),
    );
  }

  Future<void> _logout(BuildContext context, PartnerSession session) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(AppStrings.logoutPrompt),
        content: Text(AppStrings.logoutSubtitle),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: Text(AppStrings.cancel),
          ),
          FilledButton(
            style:
                FilledButton.styleFrom(backgroundColor: PartnerTheme.crimson),
            onPressed: () => Navigator.of(ctx).pop(true),
            child: Text(AppStrings.logout),
          ),
        ],
      ),
    );
    if (confirmed == true && context.mounted) {
      await session.logout();
      if (context.mounted) {
        Navigator.of(context)
            .pushNamedAndRemoveUntil(LoginScreen.route, (r) => false);
      }
    }
  }
}
