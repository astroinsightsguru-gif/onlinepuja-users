import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/call_provider.dart';
import '../../theme/manager_theme.dart';

class ActiveCallHud extends StatelessWidget {
  final VoidCallback? onOpenLeadCrm;

  const ActiveCallHud({super.key, this.onOpenLeadCrm});

  @override
  Widget build(BuildContext context) {
    final callProvider = Provider.of<CallProvider>(context);

    if (callProvider.callState == CallState.idle) {
      return const SizedBox.shrink();
    }

    final isConnecting = callProvider.callState == CallState.dialing || callProvider.callState == CallState.ringing;
    final minutes = (callProvider.callDurationSeconds ~/ 60).toString().padLeft(2, '0');
    final seconds = (callProvider.callDurationSeconds % 60).toString().padLeft(2, '0');

    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        child: Material(
          elevation: 12,
          borderRadius: BorderRadius.circular(16),
          color: Colors.transparent,
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF131D3B), Color(0xFF0B0F19)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(
                color: isConnecting ? ManagerTheme.brandGold : const Color(0xFF25D366),
                width: 1.5,
              ),
              boxShadow: [
                BoxShadow(
                  color: (isConnecting ? ManagerTheme.brandGold : const Color(0xFF25D366)).withValues(alpha: 0.25),
                  blurRadius: 16,
                  spreadRadius: 2,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Row(
                  children: [
                    Container(
                      width: 40,
                      height: 40,
                      decoration: BoxDecoration(
                        color: (isConnecting ? ManagerTheme.brandGold : const Color(0xFF25D366)).withValues(alpha: 0.15),
                        shape: BoxShape.circle,
                      ),
                      child: Icon(
                        isConnecting ? Icons.ring_volume : Icons.phone_in_talk,
                        color: isConnecting ? ManagerTheme.brandGold : const Color(0xFF25D366),
                        size: 20,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Text(
                                callProvider.activeCallerName,
                                style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14),
                              ),
                              const SizedBox(width: 6),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                decoration: BoxDecoration(
                                  color: ManagerTheme.brandGold.withValues(alpha: 0.2),
                                  borderRadius: BorderRadius.circular(4),
                                ),
                                child: Text(
                                  'Gotra: ${callProvider.activeGotra ?? "Kashyap"}',
                                  style: const TextStyle(color: ManagerTheme.brandGold, fontSize: 10, fontWeight: FontWeight.bold),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 2),
                          Text(
                            callProvider.activePujaInterest ?? 'Vedic Consultation',
                            style: const TextStyle(color: Colors.white70, fontSize: 11),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ),
                    ),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text(
                          isConnecting ? 'Calling…' : '$minutes:$seconds',
                          style: TextStyle(
                            color: isConnecting ? ManagerTheme.brandGold : Colors.white,
                            fontFamily: 'monospace',
                            fontWeight: FontWeight.bold,
                            fontSize: 14,
                          ),
                        ),
                        const SizedBox(height: 4),
                        GestureDetector(
                          onTap: () => callProvider.endCall(),
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: Colors.redAccent.withValues(alpha: 0.2),
                              borderRadius: BorderRadius.circular(6),
                              border: Border.all(color: Colors.redAccent.withValues(alpha: 0.4)),
                            ),
                            child: const Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(Icons.call_end, color: Colors.redAccent, size: 12),
                                SizedBox(width: 4),
                                Text('Hangup', style: TextStyle(color: Colors.redAccent, fontSize: 11, fontWeight: FontWeight.bold)),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
