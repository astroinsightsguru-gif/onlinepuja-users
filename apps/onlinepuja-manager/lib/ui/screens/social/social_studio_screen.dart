import 'package:flutter/material.dart';
import 'social_management_screen.dart';

/// Sovereign Social Studio & Reel Creator
/// Unified with SocialManagementScreen featuring 5 Sovereign Tabs:
/// 1. Queue & Live Feed
/// 2. 9:16 Sacred Reels (Shorts & Multi-Fanout)
/// 3. 16:9 Master Devotional Video (1080p YouTube Live)
/// 4. FLUX & Devotional Poster Studio
/// 5. Sovereign Engine & Connected Channels Hub
class SocialStudioScreen extends StatelessWidget {
  final int initialTab;
  final bool embedded;

  const SocialStudioScreen({
    super.key,
    this.initialTab = 0,
    this.embedded = false,
  });

  static void open(BuildContext context, {int initialTab = 0}) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => SocialManagementScreen(initialTab: initialTab),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return SocialManagementScreen(initialTab: initialTab);
  }
}
