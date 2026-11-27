import 'dart:async';

import 'package:flutter/material.dart';
import 'package:livekit_client/livekit_client.dart' as lk;
import 'package:op_shared/op_shared.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../../state/app_session.dart';
import '../../theme/partner_theme.dart';
import '../../widgets/partner_widgets.dart';

/// Partner-side audio/video consultation cockpit.
/// Powered by LiveKit WebRTC with fallback REST billing timer,
/// real-time revenue accumulation, devotee Kundli overlay, and
/// glassmorphic call controls.
class CallSessionScreen extends StatefulWidget {
  const CallSessionScreen({
    super.key,
    required this.customerId,
    required this.customerName,
    this.sessionId,
    this.isVideo = false,
  });

  static const route = '/call';

  final int customerId;
  final String customerName;
  final String? sessionId;
  final bool isVideo;

  @override
  State<CallSessionScreen> createState() => _CallSessionScreenState();
}

class _CallSessionScreenState extends State<CallSessionScreen> {
  Timer? _ticker;
  Duration _elapsed = Duration.zero;
  String? _sessionId;
  LiveKitCallController? _rtc;
  Object? _error;
  bool _ending = false;
  bool _remoteJoined = false;
  int _waitingSeconds = 0;

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
      final session = context.read<PartnerSession>();
      if (!session.isLoggedIn || session.astrologerId == 0) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
                content: Text('Partner session expired. Please log in.')),
          );
          Navigator.of(context).pop();
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
        astrologerId: session.astrologerId,
        userId: widget.customerId,
        isVideo: widget.isVideo,
      );
      if (_sessionId != null) {
        final info = await AstrologerApi.instance.liveKitSession(
          sessionId: _sessionId!,
          identity: 'astro_${session.astrologerId}',
        );
        if (info != null) {
          final rtc = LiveKitCallController();
          rtc.addListener(_onRtc);
          _rtc = rtc;
          await rtc.connect(
              token: info.token, wsUrl: info.wsUrl, video: widget.isVideo);
        }
      }
      if (!mounted) return;
      setState(() {});
      _ticker = Timer.periodic(const Duration(seconds: 1), (_) {
        if (!mounted) return;
        if (!_remoteJoined) {
          _waitingSeconds++;
          if (_waitingSeconds >= 45 && !_ending) {
            _endInternal(message: 'Devotee did not connect. Call ended.');
            return;
          }
          setState(() {});
          return;
        }
        setState(() => _elapsed += const Duration(seconds: 1));
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
      _endInternal(message: 'Devotee ended the consultation.');
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

  int get _estimatedEarnings {
    if (!_remoteJoined) return 0;
    final session = context.read<PartnerSession>();
    final u = session.user;
    final double configuredRate = widget.isVideo
        ? (u?.videoCallRate != null && u!.videoCallRate > 0 ? u.videoCallRate : 50.0)
        : (u?.charge != null && u!.charge > 0 ? u.charge : 35.0);
    final mins = _elapsed.inMinutes + 1;
    return (mins * configuredRate).round();
  }

  Future<void> _endInternal({String? message}) async {
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
    if (message != null && message.isNotEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(message)),
      );
    }
    Navigator.of(context).pop();
  }

  Future<void> _end() async {
    if (_ending) return;

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('End Consultation Call?'),
        content: Text(
          'Total duration: $_clock\nEstimated Earnings: ₹ $_estimatedEarnings\n\nConclude consultation with ${widget.customerName}?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text('Continue Call'),
          ),
          FilledButton(
            style:
                FilledButton.styleFrom(backgroundColor: PartnerTheme.crimson),
            onPressed: () => Navigator.of(ctx).pop(true),
            child: const Text('End & Bill'),
          ),
        ],
      ),
    );

    if (confirmed == true && mounted) {
      await _endInternal();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0F0D17),
      body: SafeArea(
        child: _error != null ? _errorView() : _callView(),
      ),
    );
  }

  Widget _errorView() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(28),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.error_outline_rounded,
                color: PartnerTheme.crimson, size: 48),
            const SizedBox(height: 16),
            const Text(
              'Call Connection Failed',
              style: TextStyle(
                  color: Colors.white,
                  fontSize: 18,
                  fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 8),
            Text(
              '$_error',
              textAlign: TextAlign.center,
              style: const TextStyle(color: Colors.white60, fontSize: 13),
            ),
            const SizedBox(height: 24),
            FilledButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Close'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _callView() {
    final rtc = _rtc;
    final showRemoteVideo = widget.isVideo && rtc?.remoteVideoTrack != null;
    final showLocalVideo = widget.isVideo &&
        rtc?.camEnabled == true &&
        rtc?.localVideoTrack != null;

    if (widget.isVideo) {
      return Stack(
        children: [
          // 1. Remote Client Video
          Positioned.fill(
            child: showRemoteVideo
                ? lk.VideoTrackRenderer(rtc!.remoteVideoTrack!)
                : Container(
                    decoration: const BoxDecoration(
                      gradient: RadialGradient(
                        colors: [Color(0xFF2A1B44), Color(0xFF0E0B16)],
                        radius: 1.2,
                      ),
                    ),
                    child: Center(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Container(
                            padding: const EdgeInsets.all(4),
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              gradient: PartnerTheme.luxuryGold,
                            ),
                            child: CircleAvatar(
                              radius: 46,
                              backgroundColor: const Color(0xFF2E243A),
                              child: Text(
                                widget.customerName.isNotEmpty
                                    ? widget.customerName
                                        .substring(0, 1)
                                        .toUpperCase()
                                    : 'D',
                                style: const TextStyle(
                                  color: PartnerTheme.gold,
                                  fontSize: 38,
                                  fontWeight: FontWeight.w900,
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(height: 18),
                          Text(
                            rtc?.remoteName ?? widget.customerName,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 22,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                          const SizedBox(height: 6),
                          Text(
                            rtc?.connected == true
                                ? 'Waiting for devotee video feed…'
                                : 'Connecting to high-speed LiveKit WebRTC…',
                            style: const TextStyle(
                              color: Colors.white70,
                              fontSize: 13,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
          ),

          // 2. Top Header Bar
          Positioned(
            top: 12,
            left: 16,
            right: 16,
            child: Row(
              children: [
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: 0.65),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(
                      color: PartnerTheme.gold.withValues(alpha: 0.4),
                    ),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Container(
                        width: 8,
                        height: 8,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: _remoteJoined
                              ? PartnerTheme.emerald
                              : PartnerTheme.amber,
                        ),
                      ),
                      const SizedBox(width: 8),
                      Text(
                        '$_clock · ₹ $_estimatedEarnings',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 13,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ],
                  ),
                ),
                const Spacer(),
                IconButton(
                  style: IconButton.styleFrom(
                    backgroundColor: Colors.black.withValues(alpha: 0.65),
                    foregroundColor: Colors.white,
                  ),
                  tooltip: 'Devotee Kundli',
                  icon: const Icon(Icons.auto_awesome,
                      color: PartnerTheme.gold, size: 20),
                  onPressed: _showClientInfoSheet,
                ),
              ],
            ),
          ),

          // 3. Floating Astrologer PiP Camera
          Positioned(
            top: 68,
            right: 16,
            child: Container(
              width: 105,
              height: 145,
              decoration: BoxDecoration(
                color: Colors.black87,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(
                  color: PartnerTheme.gold.withValues(alpha: 0.5),
                  width: 1.5,
                ),
                boxShadow: const [
                  BoxShadow(color: Colors.black54, blurRadius: 12)
                ],
              ),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(14),
                child: showLocalVideo
                    ? lk.VideoTrackRenderer(rtc!.localVideoTrack!)
                    : const Center(
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.videocam_off_rounded,
                                color: Colors.white54, size: 28),
                            SizedBox(height: 4),
                            Text(
                              'Cam Off',
                              style: TextStyle(
                                  color: Colors.white60, fontSize: 10),
                            ),
                          ],
                        ),
                      ),
              ),
            ),
          ),

          // 4. Glassmorphic Bottom Controls
          Positioned(
            bottom: 24,
            left: 0,
            right: 0,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 18, vertical: 12),
                  margin: const EdgeInsets.symmetric(horizontal: 20),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: 0.75),
                    borderRadius: BorderRadius.circular(32),
                    border: Border.all(
                      color: Colors.white.withValues(alpha: 0.15),
                    ),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                    children: [
                      _roundAction(
                        icon: rtc?.micMuted == true
                            ? Icons.mic_off_rounded
                            : Icons.mic_rounded,
                        color: rtc?.micMuted == true
                            ? PartnerTheme.crimson
                            : Colors.white24,
                        onTap: rtc?.connected == true
                            ? () => rtc!.toggleMic()
                            : null,
                      ),
                      _roundAction(
                        icon: rtc?.camEnabled == true
                            ? Icons.videocam_rounded
                            : Icons.videocam_off_rounded,
                        color: rtc?.camEnabled == true
                            ? Colors.white24
                            : PartnerTheme.crimson,
                        onTap: rtc?.connected == true
                            ? () => rtc!.toggleCam()
                            : null,
                      ),
                      _roundAction(
                        icon: Icons.flip_camera_ios_rounded,
                        color: Colors.white24,
                        onTap:
                            (rtc?.connected == true && rtc?.camEnabled == true)
                                ? () => rtc!.switchCamera()
                                : null,
                      ),
                      _roundAction(
                        icon: Icons.temple_hindu_rounded,
                        color: PartnerTheme.saffron.withValues(alpha: 0.4),
                        onTap: _showPrescribeRemedySheet,
                      ),
                      _roundAction(
                        icon: Icons.auto_awesome,
                        color: PartnerTheme.gold.withValues(alpha: 0.3),
                        onTap: _showClientInfoSheet,
                      ),
                      IconButton(
                        style: IconButton.styleFrom(
                          backgroundColor: PartnerTheme.crimson,
                          padding: const EdgeInsets.all(14),
                        ),
                        onPressed: _ending ? null : _end,
                        icon: const Icon(Icons.call_end_rounded,
                            color: Colors.white, size: 26),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      );
    }

    // Audio Call View
    return Container(
      decoration: const BoxDecoration(
        gradient: RadialGradient(
          colors: [Color(0xFF26193E), Color(0xFF0F0D17)],
          radius: 1.1,
        ),
      ),
      child: Column(
        children: [
          const SizedBox(height: 18),
          // Top Status Strip
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
            decoration: BoxDecoration(
              color: Colors.black.withValues(alpha: 0.4),
              borderRadius: BorderRadius.circular(20),
              border: Border.all(
                color: PartnerTheme.gold.withValues(alpha: 0.3),
              ),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 8,
                  height: 8,
                  decoration: const BoxDecoration(
                    shape: BoxShape.circle,
                    color: PartnerTheme.emerald,
                  ),
                ),
                const SizedBox(width: 8),
                Text(
                  '1:1 AUDIO CONSULTATION · ₹35/MIN',
                  style: TextStyle(
                    color: PartnerTheme.goldLight,
                    fontSize: 11,
                    fontWeight: FontWeight.w800,
                    letterSpacing: 0.5,
                  ),
                ),
              ],
            ),
          ),

          const Spacer(),

          // Pulsing Avatar with Sacred Aura
          Stack(
            alignment: Alignment.center,
            children: [
              Container(
                width: 140,
                height: 140,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: PartnerTheme.saffron.withValues(alpha: 0.08),
                ),
              ),
              Container(
                width: 116,
                height: 116,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: PartnerTheme.saffron.withValues(alpha: 0.16),
                ),
              ),
              Container(
                width: 92,
                height: 92,
                padding: const EdgeInsets.all(3),
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: PartnerTheme.luxuryGold,
                  boxShadow: PartnerTheme.glow(PartnerTheme.saffron, blur: 20),
                ),
                child: CircleAvatar(
                  backgroundColor: const Color(0xFF2A1C38),
                  child: Text(
                    widget.customerName.isNotEmpty
                        ? widget.customerName.substring(0, 1).toUpperCase()
                        : 'D',
                    style: const TextStyle(
                      color: PartnerTheme.gold,
                      fontSize: 38,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 22),

          // Devotee Name & Coordinates
          Text(
            rtc?.remoteName ?? widget.customerName,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 24,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            _remoteJoined
                ? 'High-Fidelity Audio Connected'
                : 'Connecting to Devotee...',
            style: TextStyle(
              color: _remoteJoined
                  ? PartnerTheme.emeraldLight
                  : Colors.white60,
              fontSize: 13,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 26),

          // Clock & Real-time Earnings
          Text(
            _clock,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 38,
              fontWeight: FontWeight.w900,
              letterSpacing: 2,
            ),
          ),
          const SizedBox(height: 6),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
            decoration: BoxDecoration(
              color: PartnerTheme.emerald.withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(
                color: PartnerTheme.emerald.withValues(alpha: 0.4),
              ),
            ),
            child: Text(
              'Earnings: ₹ $_estimatedEarnings',
              style: const TextStyle(
                color: PartnerTheme.emeraldLight,
                fontSize: 13,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),

          const Spacer(),

          // Audio Controls Bar
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            margin: const EdgeInsets.symmetric(horizontal: 16),
            decoration: BoxDecoration(
              color: Colors.black.withValues(alpha: 0.65),
              borderRadius: BorderRadius.circular(32),
              border: Border.all(
                color: Colors.white.withValues(alpha: 0.15),
              ),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                _roundAction(
                  icon: rtc?.micMuted == true
                      ? Icons.mic_off_rounded
                      : Icons.mic_rounded,
                  label: rtc?.micMuted == true ? 'अनम्यूट' : 'म्यूट',
                  color: rtc?.micMuted == true
                      ? PartnerTheme.crimson
                      : Colors.white24,
                  onTap: rtc?.connected == true ? () => rtc!.toggleMic() : null,
                ),
                _roundAction(
                  icon: Icons.temple_hindu_rounded,
                  label: 'पूजा संस्तुति',
                  color: PartnerTheme.saffron.withValues(alpha: 0.45),
                  onTap: _showPrescribeRemedySheet,
                ),
                _roundAction(
                  icon: Icons.auto_awesome,
                  label: 'यजमान कुंडली',
                  color: PartnerTheme.gold.withValues(alpha: 0.35),
                  onTap: _showClientInfoSheet,
                ),
                InkWell(
                  onTap: _ending ? null : _end,
                  borderRadius: BorderRadius.circular(16),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: const BoxDecoration(
                            color: PartnerTheme.crimson,
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(Icons.call_end_rounded,
                              color: Colors.white, size: 22),
                        ),
                        const SizedBox(height: 4),
                        const Text(
                          'समाप्त',
                          style: TextStyle(
                            color: Colors.white70,
                            fontSize: 10,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  Widget _roundAction({
    required IconData icon,
    required VoidCallback? onTap,
    String? label,
    Color? color,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: color ?? Colors.white.withValues(alpha: 0.15),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, size: 22, color: Colors.white),
            ),
            if (label != null) ...[
              const SizedBox(height: 4),
              Text(
                label,
                style: const TextStyle(
                  color: Colors.white70,
                  fontSize: 10,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  void _showPrescribeRemedySheet() {
    final session = context.read<PartnerSession>();
    final astroId = session.astrologerId > 0 ? session.astrologerId : 1;
    final reasonCtrl = TextEditingController(
      text: 'Post-Call Planetary Dosha Nivaran & Graha Shanti',
    );
    final mantraCtrl = TextEditingController(
      text: 'Om Namah Shivaya (108x daily at Brahma Muhurat)',
    );
    final gemstoneCtrl = TextEditingController(
      text: '5-Mukhi Nepal Rudraksha',
    );

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        constraints: BoxConstraints(
          maxHeight: MediaQuery.of(ctx).size.height * 0.85,
        ),
        padding: EdgeInsets.only(
          left: 20,
          right: 20,
          top: 16,
          bottom: MediaQuery.of(ctx).viewInsets.bottom + 24,
        ),
        decoration: const BoxDecoration(
          color: Color(0xFF171324),
          borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    gradient: PartnerTheme.saffronGradient,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.temple_hindu_rounded,
                      color: Colors.white, size: 20),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Prescribe Call Remedy & Puja',
                        style: TextStyle(
                            color: Colors.white,
                            fontSize: 16,
                            fontWeight: FontWeight.w800),
                      ),
                      Text(
                        'Instant Sankalp prescription for ${widget.customerName}',
                        style: const TextStyle(
                            color: Colors.white60, fontSize: 11.5),
                      ),
                    ],
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.close, color: Colors.white70),
                  onPressed: () => Navigator.pop(ctx),
                ),
              ],
            ),
            const SizedBox(height: 12),
            TextField(
              controller: reasonCtrl,
              style: const TextStyle(color: Colors.white, fontSize: 12.5),
              decoration: const InputDecoration(
                labelText: 'Dosha / Consultation Diagnosis',
                labelStyle: TextStyle(color: Colors.white60),
                isDense: true,
              ),
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: mantraCtrl,
                    style: const TextStyle(color: Colors.white, fontSize: 12),
                    decoration: const InputDecoration(
                      labelText: 'Prescribed Mantra',
                      labelStyle: TextStyle(color: Colors.white60),
                      isDense: true,
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: TextField(
                    controller: gemstoneCtrl,
                    style: const TextStyle(color: Colors.white, fontSize: 12),
                    decoration: const InputDecoration(
                      labelText: 'Gemstone / Rudraksha',
                      labelStyle: TextStyle(color: Colors.white60),
                      isDense: true,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            const Text(
              'Select Sacred Temple Puja to Prescribe:',
              style: TextStyle(
                  color: PartnerTheme.gold,
                  fontSize: 12.5,
                  fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 8),
            Expanded(
              child: FutureBuilder<List<Map<String, dynamic>>>(
                future: AiTwinApi.instance.fetchAvailablePujas(),
                builder: (context, snap) {
                  if (snap.connectionState == ConnectionState.waiting) {
                    return const Center(child: CircularProgressIndicator());
                  }
                  final pujas = snap.data ?? const [];
                  if (pujas.isEmpty) {
                    return const Center(
                      child: Text('No pujas available',
                          style: TextStyle(color: Colors.white60)),
                    );
                  }
                  return ListView.separated(
                    itemCount: pujas.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 8),
                    itemBuilder: (context, index) {
                      final p = pujas[index];
                      final title = p['puja_title']?.toString() ?? 'Vedic Puja';
                      final place = p['puja_place']?.toString() ?? 'Kashi';
                      final price = p['puja_price']?.toString() ?? '1100';
                      final pid = p['id'] is int
                          ? p['id'] as int
                          : int.tryParse(p['id'].toString()) ?? 1;
                      return Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.06),
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: Colors.white12),
                        ),
                        child: Row(
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    title,
                                    style: const TextStyle(
                                        color: Colors.white,
                                        fontWeight: FontWeight.w700,
                                        fontSize: 13.5),
                                  ),
                                  Text(
                                    '$place • ₹$price',
                                    style: const TextStyle(
                                        color: Colors.white60, fontSize: 11.5),
                                  ),
                                ],
                              ),
                            ),
                            FilledButton(
                              style: FilledButton.styleFrom(
                                backgroundColor: PartnerTheme.saffron,
                              ),
                              onPressed: () async {
                                await AiTwinApi.instance.prescribeRemedy(
                                  astrologerId: astroId,
                                  userId: widget.customerId,
                                  pujaId: pid,
                                  remedyTitle: title,
                                  reason: reasonCtrl.text.trim(),
                                  mantra: mantraCtrl.text.trim(),
                                  gemstone: gemstoneCtrl.text.trim(),
                                );
                                if (ctx.mounted) {
                                  Navigator.pop(ctx);
                                }
                                if (mounted) {
                                  ScaffoldMessenger.of(this.context)
                                      .showSnackBar(
                                    SnackBar(
                                      backgroundColor: PartnerTheme.emerald,
                                      content: Text(
                                          'Prescribed "$title" to ${widget.customerName}!'),
                                    ),
                                  );
                                }
                              },
                              child: const Text('Prescribe'),
                            ),
                          ],
                        ),
                      );
                    },
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showClientInfoSheet() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Padding(
        padding: const EdgeInsets.all(20),
        child: FutureBuilder<Map<String, dynamic>?>(
          future:
              AstrologerApi.instance.getIntakeForm(userId: widget.customerId),
          builder: (context, snapshot) {
            final intake = snapshot.data;
            final dob = intake?['birthDate']?.toString() ?? '15 Aug 1994';
            final tob = intake?['birthTime']?.toString() ?? '08:45 AM';
            final pob =
                intake?['birthPlace']?.toString() ?? 'Varanasi, UP';
            final topic = intake?['topicOfConcern']?.toString() ??
                'Career promotion & marriage timing';

            return DevoteeKundliSheet(
              devoteeName: widget.customerName,
              birthDate: dob,
              birthTime: tob,
              birthPlace: pob,
              concern: topic,
            );
          },
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
                  color: PartnerTheme.saffron.withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  isVideo ? Icons.videocam_rounded : Icons.mic_rounded,
                  color: PartnerTheme.saffron,
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
                'OnlinePuja.astro requires access to your device ${isVideo ? 'Camera and Microphone' : 'Microphone'} to enable real-time consultation with devotees.',
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
                backgroundColor: PartnerTheme.saffron,
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
