import 'dart:async';

import 'package:flutter/material.dart';
import 'package:op_shared/op_shared.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../state/app_session.dart';
import '../profile/wallet_screen.dart';
import '../puja/puja_list_screen.dart';

/// 1:1 consultation chat with live wallet countdown ticker and quick Kundli intake.
class ChatSessionScreen extends StatefulWidget {
  const ChatSessionScreen({
    super.key,
    required this.astrologerId,
    required this.astrologerName,
    this.ratePerMinute = 15.0,
    this.sessionId,
    this.isFree = false,
  });

  static const route = '/chat';

  final int astrologerId;
  final String astrologerName;
  final double ratePerMinute;
  final String? sessionId;
  final bool isFree;

  @override
  State<ChatSessionScreen> createState() => _ChatSessionScreenState();
}

class _ChatSessionScreenState extends State<ChatSessionScreen> {
  final _composer = TextEditingController();
  final _scroll = ScrollController();
  Timer? _poller;
  Timer? _sessionTimer;
  String? _sessionId;
  List<ChatMessage> _messages = const [];
  bool _sending = false;
  bool _ending = false;
  Object? _error;
  Duration _elapsed = Duration.zero;
  DateTime? _sessionStart;

  static const _quickPrompts = [
    '🙏 Namaste Pandit ji',
    '🔮 Check My Kundli',
    '💼 Career & Job Guidance',
    '❤️ Marriage & Compatibility',
    '🩺 Health & Wellness',
    '🪔 Recommended Remedies',
  ];

  String get _myId => context.read<AppSession>().myId;

  @override
  void initState() {
    super.initState();
    _sessionId = widget.sessionId;
    _openOrReuse();
  }

  @override
  void dispose() {
    _poller?.cancel();
    _sessionTimer?.cancel();
    _composer.dispose();
    _scroll.dispose();
    super.dispose();
  }

  Future<void> _openOrReuse() async {
    final session = context.read<AppSession>();
    if (!session.isAuthenticated || session.user == null) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Please log in to start a consultation.')),
        );
        Navigator.of(context).pop();
      }
      return;
    }
    final balance = session.user?.walletAmount ?? 0;
    final rate = widget.ratePerMinute > 0 ? widget.ratePerMinute : 15.0;
    if (!widget.isFree && balance < rate) {
      if (mounted) {
        final recharge = await showDialog<bool>(
          context: context,
          builder: (ctx) => AlertDialog(
            title: const Text('Insufficient Wallet Balance'),
            content: Text(
              'Your wallet balance is ₹${balance.toStringAsFixed(0)}. You need at least ₹${rate.toStringAsFixed(0)} to chat with ${widget.astrologerName}.',
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.of(ctx).pop(false),
                child: const Text('Cancel'),
              ),
              FilledButton(
                onPressed: () => Navigator.of(ctx).pop(true),
                child: const Text('Recharge Wallet'),
              ),
            ],
          ),
        );
        if (recharge == true && mounted) {
          Navigator.of(context).pushReplacementNamed(WalletScreen.route);
        } else if (mounted) {
          Navigator.of(context).pop();
        }
      }
      return;
    }
    final userId = session.user?.id ?? 0;
    try {
      _sessionId ??= await AstrologerApi.instance
          .existingChatSession(widget.astrologerId);
      _sessionId ??= await AstrologerApi.instance.addChatRequest(
        astrologerId: widget.astrologerId,
        userId: userId,
        isFree: widget.isFree,
      );
      _sessionStart ??= DateTime.now();
      if (!mounted) return;
      await _fetch(forceScroll: true);
      _poller = Timer.periodic(const Duration(seconds: 3), (_) {
        if (!mounted) return;
        _fetch();
      });
      _sessionTimer = Timer.periodic(const Duration(seconds: 1), (_) {
        if (!mounted) return;
        setState(() {
          if (_sessionStart != null) {
            _elapsed = DateTime.now().difference(_sessionStart!);
          } else {
            _elapsed += const Duration(seconds: 1);
          }
        });
        _checkWalletExpiry();
      });
      setState(() {});

      // Auto-send Kundli Intake details on first session message
      if (_messages.isEmpty) {
        final u = session.user;
        if (u != null) {
          final dob = u.birthDate != null ? u.birthDate.toString().split(' ').first : 'Not specified';
          final tob = (u.birthTime != null && u.birthTime!.isNotEmpty) ? u.birthTime! : 'Not specified';
          final pob = (u.birthPlace != null && u.birthPlace!.isNotEmpty) ? u.birthPlace! : 'India';
          final intake = '🕉️ [Kundli Intake Details]\n'
              '• Name: ${u.name ?? "Devotee"}\n'
              '• Gender: ${u.gender ?? "Not specified"}\n'
              '• DOB: $dob\n'
              '• Time: $tob\n'
              '• Place: $pob\n'
              '• Query: Astrological consultation & remedy guidance.';
          _sendCustom(intake);
        }
      }
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  void _checkWalletExpiry() {
    if (_ending || _sessionId == null) return;
    final session = context.read<AppSession>();
    final balance = session.user?.walletAmount ?? 0;
    final rate = widget.ratePerMinute > 0 ? widget.ratePerMinute : 15.0;
    final totalAllowedSecs = widget.isFree ? 300 : ((balance / rate) * 60).floor();
    final remainingSecs = totalAllowedSecs - _elapsed.inSeconds;

    if (remainingSecs <= 0) {
      _autoEndChat();
    }
  }

  Future<void> _autoEndChat() async {
    if (_ending) return;
    _ending = true;
    _poller?.cancel();
    _sessionTimer?.cancel();
    try {
      if (_sessionId != null) {
        await AstrologerApi.instance.endChat(sessionId: _sessionId!);
      }
    } catch (_) {}
    if (mounted) {
      showSnack(context, 'Chat consultation ended: Wallet balance exhausted.', error: true);
      await showConsultationFeedbackDialog(
        context: context,
        astrologerId: widget.astrologerId,
        astrologerName: widget.astrologerName,
      );
      if (mounted) {
        Navigator.of(context).pop();
      }
    }
  }

  Future<void> _fetch({bool forceScroll = false}) async {
    if (_sessionId == null) return;
    try {
      final fresh = await AstrologerApi.instance
          .chatHistory(sessionId: _sessionId!, myId: _myId);
      if (!mounted) return;
      final hasNewMessages = fresh.length != _messages.length;
      setState(() {
        _messages = fresh;
        if (_sessionStart != null) {
          _elapsed = DateTime.now().difference(_sessionStart!);
        }
      });
      if (forceScroll || hasNewMessages) {
        _jumpToBottom();
      }
    } catch (_) {}
  }

  void _jumpToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scroll.hasClients) {
        _scroll.animateTo(_scroll.position.maxScrollExtent, duration: const Duration(milliseconds: 250), curve: Curves.easeOut);
      }
    });
  }

  Future<void> _sendCustom(String text) async {
    if (text.isEmpty || _sessionId == null || _sending) return;
    setState(() => _sending = true);
    try {
      await AstrologerApi.instance.sendMessage(
        sessionId: _sessionId!,
        fromUserId: _myId,
        text: text,
      );
      await _fetch(forceScroll: true);
    } on ApiException catch (e) {
      if (mounted) showSnack(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  Future<void> _send() async {
    final text = _composer.text.trim();
    if (text.isEmpty || _sessionId == null || _sending) return;
    setState(() => _sending = true);
    _composer.clear();
    try {
      await AstrologerApi.instance.sendMessage(
        sessionId: _sessionId!,
        fromUserId: _myId,
        text: text,
      );
      await _fetch(forceScroll: true);
    } on ApiException catch (e) {
      if (mounted) showSnack(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  Future<void> _endChat() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('End chat?'),
        content: const Text(
            'Your consultation minutes will be settled and the session closed.'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Cancel')),
          FilledButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: const Text('End chat')),
        ],
      ),
    );
    if (confirmed != true || _sessionId == null) return;
    try {
      await AstrologerApi.instance.endChat(sessionId: _sessionId!);
    } catch (_) {}
    if (mounted) {
      await showConsultationFeedbackDialog(
        context: context,
        astrologerId: widget.astrologerId,
        astrologerName: widget.astrologerName,
      );
      if (mounted) {
        Navigator.of(context).pop();
      }
    }
  }

  String get _timerLabel {
    final m = _elapsed.inMinutes.remainder(60).toString().padLeft(2, '0');
    final s = _elapsed.inSeconds.remainder(60).toString().padLeft(2, '0');
    return '${_elapsed.inHours}:$m:$s';
  }

  Future<void> _quickRecharge(double amount) async {
    try {
      final payUrl = await WalletApi.instance.addPayment(amount: amount);
      if (payUrl != null && payUrl.isNotEmpty) {
        final uri = Uri.parse(payUrl);
        final launched = await launchUrl(uri, mode: LaunchMode.inAppBrowserView);
        if (!launched) {
          await launchUrl(uri, mode: LaunchMode.externalApplication);
        }
      }
      if (!mounted) return;
      await context.read<AppSession>().refreshUser();
      if (!mounted) return;
      showSnack(context, 'Wallet refreshed! Consultation extended.');
      setState(() {});
    } on ApiException catch (e) {
      if (mounted) showSnack(context, e.message, error: true);
    } catch (_) {}
  }

  void _showQuickRechargeSheet(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final session = context.read<AppSession>();
    final balance = session.user?.walletAmount ?? 0;

    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: Colors.grey.shade400,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  const Icon(Icons.bolt_rounded, color: Colors.amber, size: 28),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Quick Talktime Top-Up',
                            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                        Text('Current balance: ₹${balance.toStringAsFixed(0)} • Keep talking without disconnect',
                            style: TextStyle(fontSize: 12, color: scheme.outline)),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: _rechargeOptionButton(
                      ctx,
                      amount: 50,
                      label: '₹50',
                      sublabel: 'Basic',
                      isPopular: false,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: _rechargeOptionButton(
                      ctx,
                      amount: 100,
                      label: '₹100',
                      sublabel: 'Most Popular',
                      isPopular: true,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: _rechargeOptionButton(
                      ctx,
                      amount: 200,
                      label: '₹200',
                      sublabel: 'Best Value',
                      isPopular: false,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              SizedBox(
                width: double.infinity,
                child: TextButton.icon(
                  onPressed: () {
                    Navigator.pop(ctx);
                    Navigator.of(context).pushNamed(WalletScreen.route);
                  },
                  icon: const Icon(Icons.account_balance_wallet_outlined, size: 18),
                  label: const Text('View All Recharge Packs'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _rechargeOptionButton(
    BuildContext ctx, {
    required double amount,
    required String label,
    required String sublabel,
    required bool isPopular,
  }) {
    return InkWell(
      onTap: () {
        Navigator.pop(ctx);
        _quickRecharge(amount);
      },
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
        decoration: BoxDecoration(
          color: isPopular ? Colors.amber.shade50 : Theme.of(ctx).colorScheme.surface,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isPopular ? Colors.amber.shade700 : Colors.grey.shade300,
            width: isPopular ? 1.8 : 1,
          ),
        ),
        child: Column(
          children: [
            Text(label,
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: isPopular ? Colors.amber.shade900 : null,
                )),
            const SizedBox(height: 4),
            Text(sublabel,
                style: TextStyle(
                  fontSize: 10.5,
                  fontWeight: FontWeight.w600,
                  color: isPopular ? Colors.amber.shade800 : Colors.grey.shade600,
                )),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final session = context.watch<AppSession>();
    final balance = session.user?.walletAmount ?? 0;
    final rate = widget.ratePerMinute > 0 ? widget.ratePerMinute : 15.0;
    final totalAllowedSecs = widget.isFree ? 300 : ((balance / rate) * 60).floor();
    final remainingSecs = (totalAllowedSecs - _elapsed.inSeconds).clamp(0, 99999);
    final remMin = (remainingSecs / 60).floor();
    final remSec = (remainingSecs % 60).toString().padLeft(2, '0');
    final isLowBalance = !widget.isFree && remainingSecs <= 120 && remainingSecs > 0;

    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(widget.astrologerName,
                style: Theme.of(context).textTheme.titleMedium),
            Row(
              children: [
                Text('⏱️ $_timerLabel',
                    style: Theme.of(context)
                        .textTheme
                        .labelSmall
                        ?.copyWith(color: scheme.outline)),
                const SizedBox(width: 8),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1.5),
                  decoration: BoxDecoration(
                    color: remainingSecs < 120 ? Colors.red.shade100 : Colors.green.shade100,
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    widget.isFree ? 'FREE ($remMin:$remSec)' : '⏳ $remMin:$remSec left (₹${balance.toStringAsFixed(0)})',
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.bold,
                      color: remainingSecs < 120 ? Colors.red.shade800 : Colors.green.shade800,
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
        actions: [
          IconButton(
            tooltip: 'End chat',
            icon: const Icon(Icons.call_end_rounded),
            onPressed: _endChat,
          ),
        ],
      ),
      body: _error != null
          ? StatusViews.error(context, _error!, onRetry: _openOrReuse)
          : Column(
              children: [
                Expanded(
                  child: _messages.isEmpty
                      ? StatusViews.empty(context,
                          message: 'Say 🙏 namaste to begin…')
                      : ListView.builder(
                          controller: _scroll,
                          padding: const EdgeInsets.all(14),
                          itemCount: _messages.length,
                          itemBuilder: (context, i) =>
                              _bubble(context, _messages[i]),
                        ),
                ),
                if (isLowBalance)
                  Container(
                    margin: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    decoration: BoxDecoration(
                      color: Colors.amber.shade50,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: Colors.amber.shade600, width: 1.2),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.hourglass_bottom_rounded, color: Colors.amber.shade800, size: 20),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            '⚠️ Talktime ends in $remMin:$remSec. Top up to stay connected.',
                            style: TextStyle(
                              fontSize: 11.5,
                              fontWeight: FontWeight.bold,
                              color: Colors.amber.shade900,
                            ),
                          ),
                        ),
                        const SizedBox(width: 6),
                        FilledButton.tonal(
                          style: FilledButton.styleFrom(
                            backgroundColor: Colors.amber.shade800,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                            minimumSize: Size.zero,
                            tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                          ),
                          onPressed: () => _showQuickRechargeSheet(context),
                          child: const Text('⚡ Add ₹100', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                        ),
                      ],
                    ),
                  ),
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  child: Row(
                    children: _quickPrompts.map((p) => Padding(
                      padding: const EdgeInsets.only(right: 6),
                      child: ActionChip(
                        label: Text(p, style: const TextStyle(fontSize: 12)),
                        onPressed: () => _sendCustom(p),
                        padding: const EdgeInsets.symmetric(horizontal: 4),
                        materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                      ),
                    )).toList(),
                  ),
                ),
                SafeArea(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(12, 4, 12, 10),
                    child: Row(
                      children: [
                        Expanded(
                          child: TextField(
                            controller: _composer,
                            minLines: 1,
                            maxLines: 4,
                            textInputAction: TextInputAction.send,
                            onSubmitted: (_) => _send(),
                            decoration:
                                const InputDecoration(hintText: 'Type your question…'),
                          ),
                        ),
                        const SizedBox(width: 8),
                        CircleAvatar(
                          radius: 24,
                          backgroundColor: scheme.primary,
                          child: IconButton(
                            icon: const Icon(Icons.send_rounded,
                                color: Colors.white, size: 20),
                            onPressed: _send,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
    );
  }

  Widget _bubble(BuildContext context, ChatMessage m) {
    final scheme = Theme.of(context).colorScheme;
    final mine = m.isMine;

    // Check if this message is a Vedic Remedy / Puja prescription
    final isRemedy = m.text.contains('[Vedic Remedy Recommendation]') ||
        m.text.contains('[Puja Prescription]') ||
        m.text.startsWith('🌸 [Vedic Remedy');

    if (isRemedy) {
      return _remedyBubble(context, m);
    }

    return Align(
      alignment: mine ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 4),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        constraints: BoxConstraints(
            maxWidth: MediaQuery.of(context).size.width * 0.75),
        decoration: BoxDecoration(
          color: mine ? scheme.primary : scheme.surface,
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(16),
            topRight: const Radius.circular(16),
            bottomLeft: Radius.circular(mine ? 16 : 4),
            bottomRight: Radius.circular(mine ? 4 : 16),
          ),
          border:
              mine ? null : Border.all(color: scheme.outline.withValues(alpha: 0.5)),
        ),
        child: Text(
          m.text,
          style: TextStyle(
              color: mine ? Colors.white : scheme.onSurface, height: 1.3),
        ),
      ),
    );
  }

  Widget _remedyBubble(BuildContext context, ChatMessage m) {
    final cleanText = m.text
        .replaceAll('🌸 [Vedic Remedy Recommendation]:', '')
        .replaceAll('[Vedic Remedy Recommendation]:', '')
        .replaceAll('[Puja Prescription]:', '')
        .trim();

    return Align(
      alignment: Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 8),
        constraints: BoxConstraints(
            maxWidth: MediaQuery.of(context).size.width * 0.85),
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: [
              Colors.amber.shade50,
              Colors.orange.shade50,
            ],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: const Color(0xFFD97706), width: 1.5),
          boxShadow: [
            BoxShadow(
              color: Colors.amber.withValues(alpha: 0.15),
              blurRadius: 8,
              offset: const Offset(0, 3),
            ),
          ],
        ),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(6),
                    decoration: BoxDecoration(
                      color: const Color(0xFFD97706),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Icon(Icons.temple_hindu_rounded,
                        color: Colors.white, size: 16),
                  ),
                  const SizedBox(width: 8),
                  const Expanded(
                    child: Text(
                      'VEDIC REMEDY PRESCRIBED',
                      style: TextStyle(
                        fontSize: 11.5,
                        fontWeight: FontWeight.w900,
                        letterSpacing: 0.6,
                        color: Color(0xFFB45309),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Text(
                cleanText,
                style: const TextStyle(
                  fontSize: 13.5,
                  fontWeight: FontWeight.w600,
                  height: 1.35,
                  color: Color(0xFF451A03),
                ),
              ),
              const SizedBox(height: 12),
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  style: FilledButton.styleFrom(
                    backgroundColor: const Color(0xFFD97706),
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                    padding: const EdgeInsets.symmetric(vertical: 8),
                  ),
                  icon: const Icon(Icons.auto_awesome, size: 16),
                  label: const Text(
                    'Book Recommended Ritual',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
                  ),
                  onPressed: () {
                    Navigator.pushNamed(context, PujaListScreen.route);
                  },
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
