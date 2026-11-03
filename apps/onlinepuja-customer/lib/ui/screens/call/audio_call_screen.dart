import 'dart:async';

import 'package:flutter/material.dart';
import 'package:livekit_client/livekit_client.dart' as lk;
import 'package:op_shared/op_shared.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../state/app_session.dart';
import '../profile/wallet_screen.dart';

/// Audio/video call session screen.
///
/// Uses a single **LiveKit** path (free self-hosted WebRTC, docs 04 §4.2)
/// instead of the legacy Agora/Zego/HMS triple stack. The backend mints a
/// LiveKit token via `POST /api/livekit/token`. If the endpoint is not yet
/// deployed this screen degrades gracefully to a REST session with a live
/// billing timer, so the flow stays testable end-to-end.
class AudioCallScreen extends StatefulWidget {
  const AudioCallScreen({
    super.key,
    required this.astrologerId,
    required this.astrologerName,
    this.ratePerMinute = 15.0,
    this.sessionId,
    this.isVideo = false,
  });

  static const route = '/call';

  final int astrologerId;
  final String astrologerName;
  final double ratePerMinute;
  final String? sessionId;
  final bool isVideo;

  @override
  State<AudioCallScreen> createState() => _AudioCallScreenState();
}

class _AudioCallScreenState extends State<AudioCallScreen> {
  Timer? _ticker;
  Duration _elapsed = Duration.zero;
  String? _sessionId;
  String? _liveKitToken;
  LiveKitCallController? _rtc;
  Object? _error;
  bool _active = false;
  bool _ending = false;

  int _waitingSeconds = 0;
  bool _remoteJoined = false;

  @override
  void initState() {
    super.initState();
    _open();
  }

  @override
  void dispose() {
    _ticker?.cancel();
    _rtc?.dispose();
    super.dispose();
  }

  Future<void> _open() async {
    try {
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
      if (balance < rate) {
        if (mounted) {
          final recharge = await showDialog<bool>(
            context: context,
            builder: (ctx) => AlertDialog(
              title: const Text('Insufficient Wallet Balance'),
              content: Text(
                'Your wallet balance is ₹${balance.toStringAsFixed(0)}. You need at least ₹${rate.toStringAsFixed(0)} to consult with ${widget.astrologerName}.',
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

      // Google Play Prominent Disclosure for Microphone & Camera permissions
      final agreed = await _checkAndPromptMediaDisclosure(context, isVideo: widget.isVideo);
      if (!agreed) {
        if (mounted) Navigator.of(context).pop();
        return;
      }

      _sessionId = widget.sessionId;
      _sessionId ??= await AstrologerApi.instance.addCallRequest(
        astrologerId: widget.astrologerId,
        userId: session.userId,
        isVideo: widget.isVideo,
      );
      if (_sessionId != null) {
        final info = await AstrologerApi.instance.liveKitSession(
          sessionId: _sessionId!,
          identity: 'user_${session.user?.id ?? 0}',
          displayName: session.user?.name,
        );
        _liveKitToken = info?.token;
        if (info != null) {
          final rtc = LiveKitCallController();
          rtc.addListener(_onRtc);
          _rtc = rtc;
          await rtc.connect(
              token: info.token, wsUrl: info.wsUrl, video: widget.isVideo);
        }
      }
      if (!mounted) return;
      setState(() => _active = true);
      _ticker = Timer.periodic(const Duration(seconds: 1), (_) async {
        if (!mounted) return;

        // 1. While waiting for the astrologer to answer
        if (!_remoteJoined) {
          _waitingSeconds++;
          if (_waitingSeconds % 3 == 0 && _sessionId != null) {
            final callInfo = await AstrologerApi.instance.getCallById(callId: _sessionId!);
            final st = (callInfo?['callStatus'] ?? '').toString().toLowerCase();
            if (st == 'rejected' || st == 'declined') {
              if (!_ending && mounted) {
                _end(suppressFeedback: true);
                showSnack(context, '${widget.astrologerName} is currently unavailable.', error: true);
                return;
              }
            } else if (st == 'accepted') {
              _remoteJoined = true;
            }
          }

          if (_waitingSeconds >= 45 && !_ending) {
            _end(suppressFeedback: true);
            if (mounted) {
              showSnack(context, '${widget.astrologerName} did not answer. Please try again later.');
            }
            return;
          }

          setState(() {});
          return;
        }

        // 2. Active connected consultation timer & wallet billing
        setState(() => _elapsed += const Duration(seconds: 1));

        final session = context.read<AppSession>();
        final balance = session.user?.walletAmount ?? 0;
        final rate = widget.ratePerMinute > 0 ? widget.ratePerMinute : 15.0;
        final totalAllowedSecs = ((balance / rate) * 60).floor();
        final remainingSecs = totalAllowedSecs - _elapsed.inSeconds;

        if (remainingSecs <= 0 && !_ending) {
          _end();
          showSnack(context, 'Consultation ended: Wallet balance exhausted.', error: true);
        }
      });
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  void _onRtc() {
    if (!mounted) return;
    final remoteName = _rtc?.remoteName;
    if (remoteName != null && !_remoteJoined) {
      _remoteJoined = true;
    } else if (_remoteJoined && remoteName == null && !_ending) {
      _end();
      showSnack(context, 'Consultation ended by ${widget.astrologerName}.');
      return;
    }
    setState(() {});
  }

  String get _clock {
    if (!_remoteJoined) {
      final rem = (45 - _waitingSeconds).clamp(0, 45);
      return 'Calling… (${rem}s)';
    }
    final m = _elapsed.inMinutes.remainder(60).toString().padLeft(2, '0');
    final s = _elapsed.inSeconds.remainder(60).toString().padLeft(2, '0');
    return '${_elapsed.inHours.toString().padLeft(2, '0')}:$m:$s';
  }

  Future<void> _end({bool suppressFeedback = false}) async {
    if (_ending) return;
    setState(() => _ending = true);
    _ticker?.cancel();
    try {
      await _rtc?.leave();
    } catch (_) {/* best effort */}
    try {
      if (_sessionId != null) {
        await AstrologerApi.instance.endCall(callId: _sessionId!);
      }
    } catch (_) {/* server best-effort */}
    if (!mounted) return;
    if (!suppressFeedback && _remoteJoined) {
      await showConsultationFeedbackDialog(
        context: context,
        astrologerId: widget.astrologerId,
        astrologerName: widget.astrologerName,
      );
    }
    if (!mounted) return;
    Navigator.of(context).pop();
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
      showSnack(context, 'Wallet recharged! Consultation extended.');
      setState(() {});
    } on ApiException catch (e) {
      if (mounted) showSnack(context, e.message, error: true);
    } catch (_) {}
  }

  void _showQuickRechargeSheet(BuildContext context) {
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
                        const Text('In-Call Talktime Top-Up',
                            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                        Text('Current balance: ₹${balance.toStringAsFixed(0)} • Keep call active without disconnect',
                            style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: _callRechargeOptionButton(
                      ctx,
                      amount: 50,
                      label: '₹50',
                      sublabel: 'Basic',
                      isPopular: false,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: _callRechargeOptionButton(
                      ctx,
                      amount: 100,
                      label: '₹100',
                      sublabel: 'Most Popular',
                      isPopular: true,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: _callRechargeOptionButton(
                      ctx,
                      amount: 200,
                      label: '₹200',
                      sublabel: 'Best Value',
                      isPopular: false,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _callRechargeOptionButton(
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
    return Scaffold(
      backgroundColor: AppTheme.brandDeep,
      body: SafeArea(
        child: _error != null ? _errorView() : _callView(),
      ),
    );
  }

  Widget _callView() {
    final rtc = _rtc;
    final showRemoteVideo = widget.isVideo && rtc?.remoteVideoTrack != null;
    final showLocalVideo = widget.isVideo && rtc?.camEnabled == true && rtc?.localVideoTrack != null;

    if (widget.isVideo) {
      return Stack(
        children: [
          // 1. Full-bleed remote video or dark placeholder
          Positioned.fill(
            child: showRemoteVideo
                ? lk.VideoTrackRenderer(rtc!.remoteVideoTrack!)
                : Container(
                    decoration: BoxDecoration(
                      gradient: RadialGradient(
                        colors: [AppTheme.brandSaffron.withValues(alpha: 0.2), AppTheme.brandDeep],
                        radius: 1.2,
                      ),
                    ),
                    child: Center(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          CircleAvatar(
                            radius: 46,
                            backgroundColor: Colors.white.withValues(alpha: 0.15),
                            child: const Icon(Icons.self_improvement, color: Colors.white, size: 52),
                          ),
                          const SizedBox(height: 16),
                          Text(
                            rtc?.remoteName ?? widget.astrologerName,
                            style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w700),
                          ),
                          const SizedBox(height: 6),
                          Text(
                            rtc?.connected == true ? 'Waiting for astrologer video…' : 'Connecting to LiveKit…',
                            style: TextStyle(color: Colors.white.withValues(alpha: 0.7), fontSize: 13),
                          ),
                        ],
                      ),
                    ),
                  ),
          ),

          // 2. Top Bar (Overlay)
          Positioned(
            top: 12,
            left: 16,
            right: 16,
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: 0.5),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: Colors.white24),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Container(
                        width: 8,
                        height: 8,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: _remoteJoined ? Colors.greenAccent : Colors.amberAccent,
                        ),
                      ),
                      const SizedBox(width: 8),
                      Text(
                        _clock,
                        style: const TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.w700),
                      ),
                    ],
                  ),
                ),
                const Spacer(),
                if (showRemoteVideo)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: Colors.black.withValues(alpha: 0.45),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Text(
                      widget.astrologerName,
                      style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600),
                    ),
                  ),
              ],
            ),
          ),

          // 3. Floating Picture-in-Picture Local Camera Preview
          Positioned(
            top: 60,
            right: 16,
            child: Container(
              width: 105,
              height: 150,
              decoration: BoxDecoration(
                color: Colors.black87,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: Colors.white30, width: 1.5),
                boxShadow: const [BoxShadow(color: Colors.black54, blurRadius: 10)],
              ),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: showLocalVideo
                    ? lk.VideoTrackRenderer(rtc!.localVideoTrack!)
                    : Center(
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.videocam_off_rounded, color: Colors.white54, size: 28),
                            const SizedBox(height: 4),
                            Text(
                              'Camera off',
                              style: TextStyle(color: Colors.white.withValues(alpha: 0.6), fontSize: 10),
                            ),
                          ],
                        ),
                      ),
              ),
            ),
          ),

          // 4. Bottom Controls
          Positioned(
            bottom: 24,
            left: 0,
            right: 0,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    _roundAction(
                      icon: rtc?.micMuted == true ? Icons.mic_off_rounded : Icons.mic_rounded,
                      onTap: rtc?.connected == true ? () => rtc!.toggleMic() : null,
                    ),
                    const SizedBox(width: 14),
                    _roundAction(
                      icon: rtc?.camEnabled == true ? Icons.videocam_rounded : Icons.videocam_off_rounded,
                      onTap: rtc?.connected == true ? () => rtc!.toggleCam() : null,
                    ),
                    const SizedBox(width: 14),
                    _roundAction(
                      icon: Icons.flip_camera_ios_rounded,
                      onTap: (rtc?.connected == true && rtc?.camEnabled == true)
                          ? () => rtc!.switchCamera()
                          : null,
                    ),
                    const SizedBox(width: 18),
                    IconButton(
                      style: IconButton.styleFrom(
                        backgroundColor: Colors.redAccent,
                        padding: const EdgeInsets.all(16),
                      ),
                      onPressed: _ending ? null : _end,
                      icon: const Icon(Icons.call_end_rounded, color: Colors.white, size: 30),
                    ),
                  ],
                ),
                if (_liveKitToken == null) ...[
                  const SizedBox(height: 12),
                  Text(
                    'LiveKit session pending · billing timer active',
                    style: TextStyle(color: Colors.white.withValues(alpha: 0.6), fontSize: 11),
                  ),
                ],
              ],
            ),
          ),
        ],
      );
    }

    // Audio Call View
    return Column(
      children: [
        const Spacer(),
        CircleAvatar(
          radius: 48,
          backgroundColor: Colors.white.withValues(alpha: 0.12),
          child: const Icon(Icons.self_improvement, color: Colors.white, size: 56),
        ),
        const SizedBox(height: 18),
        Text(
          rtc?.remoteName ?? widget.astrologerName,
          style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w700),
        ),
        const SizedBox(height: 8),
        Text(
          _remoteJoined
              ? 'Astrologer connected · HD Audio'
              : rtc?.connected == true
                  ? 'Ringing ${widget.astrologerName}…'
                  : rtc?.connecting == true
                      ? 'Connecting to LiveKit…'
                      : _active
                          ? 'Call in progress'
                          : 'Connecting…',
          style: TextStyle(color: Colors.white.withValues(alpha: 0.7), fontSize: 13),
        ),
        const SizedBox(height: 22),
        Text(
          _clock,
          style: const TextStyle(
              color: Colors.white, fontSize: 34, fontWeight: FontWeight.w800, letterSpacing: 1.5),
        ),
        const SizedBox(height: 10),
        Builder(
          builder: (ctx) {
            final session = ctx.watch<AppSession>();
            final balance = session.user?.walletAmount ?? 0;
            final rate = widget.ratePerMinute > 0 ? widget.ratePerMinute : 15.0;
            final totalAllowedSecs = ((balance / rate) * 60).floor();
            final remainingSecs = (totalAllowedSecs - _elapsed.inSeconds).clamp(0, 99999);
            final remMin = (remainingSecs / 60).floor();
            final remSec = (remainingSecs % 60).toString().padLeft(2, '0');
            final isLow = remainingSecs < 120;

            return Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                GestureDetector(
                  onTap: () => _showQuickRechargeSheet(context),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                    decoration: BoxDecoration(
                      color: isLow ? Colors.red.withValues(alpha: 0.25) : Colors.black.withValues(alpha: 0.35),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: isLow ? Colors.redAccent : Colors.white24),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          isLow ? Icons.warning_amber_rounded : Icons.account_balance_wallet_outlined,
                          color: isLow ? Colors.redAccent : Colors.amberAccent,
                          size: 16,
                        ),
                        const SizedBox(width: 6),
                        Text(
                          '⏳ $remMin:$remSec remaining (₹${balance.toStringAsFixed(0)})',
                          style: TextStyle(
                            color: isLow ? Colors.redAccent : Colors.white,
                            fontSize: 12,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        if (isLow) ...[
                          const SizedBox(width: 8),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                            decoration: BoxDecoration(
                              color: Colors.amberAccent,
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: const Text('⚡ Top-Up',
                                style: TextStyle(
                                    color: Colors.black, fontSize: 10, fontWeight: FontWeight.bold)),
                          ),
                        ],
                      ],
                    ),
                  ),
                ),
                if (isLow) ...[
                  const SizedBox(height: 12),
                  FilledButton.tonalIcon(
                    style: FilledButton.styleFrom(
                      backgroundColor: Colors.amber.shade700,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    onPressed: () => _showQuickRechargeSheet(context),
                    icon: const Icon(Icons.bolt_rounded, size: 18),
                    label: const Text('Add ₹100 Talktime (Keep Talking)',
                        style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold)),
                  ),
                ],
              ],
            );
          },
        ),
        const Spacer(),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            _roundAction(
              icon: rtc?.micMuted == true ? Icons.mic_off_rounded : Icons.mic_rounded,
              onTap: rtc?.connected == true ? () => rtc!.toggleMic() : null,
            ),
            const SizedBox(width: 20),
            IconButton(
              style: IconButton.styleFrom(
                backgroundColor: Colors.redAccent,
                padding: const EdgeInsets.all(18),
              ),
              onPressed: _ending ? null : _end,
              icon: const Icon(Icons.call_end_rounded, color: Colors.white, size: 30),
            ),
          ],
        ),
        const SizedBox(height: 28),
        Text(
          _liveKitToken == null
              ? 'LiveKit endpoint pending on server (docs 05 §5.3).\n'
                  'Session + billing timer already live.'
              : '',
          textAlign: TextAlign.center,
          style: TextStyle(color: Colors.white.withValues(alpha: 0.55), fontSize: 11),
        ),
        const SizedBox(height: 16),
      ],
    );
  }

  Widget _roundAction({required IconData icon, VoidCallback? onTap}) {
    return IconButton(
      style: IconButton.styleFrom(
        backgroundColor: Colors.white.withValues(alpha: 0.12),
        foregroundColor: Colors.white,
        padding: const EdgeInsets.all(14),
      ),
      onPressed: onTap,
      icon: Icon(icon, size: 24),
    );
  }

  Widget _errorView() {
    final isLowBalance = _error != null &&
        (_error.toString().toLowerCase().contains('balance') ||
            _error.toString().toLowerCase().contains('recharge'));

    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              isLowBalance ? Icons.account_balance_wallet_outlined : Icons.error_outline,
              color: isLowBalance ? Colors.amberAccent : Colors.white,
              size: 54,
            ),
            const SizedBox(height: 16),
            Text(
              _error is ApiException
                  ? (_error as ApiException).message
                  : _error.toString(),
              textAlign: TextAlign.center,
              style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 20),
            if (isLowBalance)
              FilledButton.icon(
                style: FilledButton.styleFrom(backgroundColor: AppTheme.brandSaffron),
                icon: const Icon(Icons.add_card),
                label: const Text('Recharge Wallet'),
                onPressed: () => Navigator.of(context)
                    .pushNamed('/wallet')
                    .then((_) => _open()),
              )
            else
              OutlinedButton(
                style: OutlinedButton.styleFrom(
                  foregroundColor: Colors.white,
                  side: const BorderSide(color: Colors.white),
                ),
                onPressed: () => Navigator.of(context).pop(),
                child: const Text('Back'),
              ),
          ],
        ),
      ),
    );
  }

  /// Google Play Prominent In-App Disclosure for Camera & Microphone permissions.
  Future<bool> _checkAndPromptMediaDisclosure(BuildContext context, {required bool isVideo}) async {
    final prefs = await SharedPreferences.getInstance();
    final alreadyAccepted = prefs.getBool('accepted_media_disclosure') ?? false;
    if (alreadyAccepted) return true;

    if (!context.mounted) return false;

    final accepted = await showDialog<bool>(
      context: context,
      barrierDismissible: false,
      builder: (ctx) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          titlePadding: const EdgeInsets.fromLTRB(24, 24, 24, 12),
          contentPadding: const EdgeInsets.symmetric(horizontal: 24),
          actionsPadding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
          title: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: AppTheme.brandSaffron.withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  isVideo ? Icons.videocam_rounded : Icons.mic_rounded,
                  color: AppTheme.brandSaffron,
                  size: 26,
                ),
              ),
              const SizedBox(width: 12),
              const Expanded(
                child: Text(
                  'Media Permission',
                  style: TextStyle(fontWeight: FontWeight.w800, fontSize: 18),
                ),
              ),
            ],
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'OnlinePuja requires access to your device ${isVideo ? 'Camera and Microphone' : 'Microphone'} to enable real-time consultation with astrologers.',
                style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5),
              ),
              const SizedBox(height: 12),
              const Text(
                '• Real-time Transmission: Audio and video are streamed in real time via secure encrypted peer connections.\n'
                '• Privacy Guaranteed: Media streams are not recorded or saved on our servers.\n'
                '• Consultation Only: Media access is utilized strictly during active consultation sessions.',
                style: TextStyle(fontSize: 12, height: 1.45, color: Colors.grey),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(ctx).pop(false),
              child: const Text('Deny', style: TextStyle(color: Colors.grey, fontWeight: FontWeight.w600)),
            ),
            FilledButton(
              style: FilledButton.styleFrom(
                backgroundColor: AppTheme.brandSaffron,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              onPressed: () => Navigator.of(ctx).pop(true),
              child: const Text('Agree & Continue', style: TextStyle(fontWeight: FontWeight.w700)),
            ),
          ],
        );
      },
    );

    if (accepted == true) {
      await prefs.setBool('accepted_media_disclosure', true);
      return true;
    }
    return false;
  }
}
