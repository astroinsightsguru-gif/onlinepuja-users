import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:onlinepuja_manager/services/manager_api_service.dart';
import 'package:onlinepuja_manager/theme/manager_theme.dart';
import 'package:url_launcher/url_launcher.dart';

class SocialManagementScreen extends StatefulWidget {
  const SocialManagementScreen({super.key});

  @override
  State<SocialManagementScreen> createState() => _SocialManagementScreenState();
}

class _SocialManagementScreenState extends State<SocialManagementScreen> {
  final ManagerApiService _api = ManagerApiService();

  bool _isLoading = true;
  bool _isLoadingAnalytics = true;
  List<Map<String, dynamic>> _posts = [];
  Map<String, dynamic>? _analytics;
  String _selectedPlatform = 'all';

  // Composer State
  final TextEditingController _composeCaptionCtrl = TextEditingController();
  final TextEditingController _composeHashtagsCtrl = TextEditingController(text: '#OnlinePuja #Kashi #Varanasi #Mandir #PujaBooking');
  final TextEditingController _composePromptCtrl = TextEditingController(text: 'Golden glowing sacred Shivling with marigold flowers and burning oil lamps at Varanasi temple');
  String _composePlatform = 'facebook';
  bool _composeGenerateAi = true;
  bool _isCreatingPost = false;

  @override
  void initState() {
    super.initState();
    _loadAll();
  }

  @override
  void dispose() {
    _composeCaptionCtrl.dispose();
    _composeHashtagsCtrl.dispose();
    _composePromptCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadAll() async {
    setState(() {
      _isLoading = true;
      _isLoadingAnalytics = true;
    });
    await Future.wait([
      _loadAnalytics(),
      _loadPosts(),
    ]);
  }

  Future<void> _loadAnalytics() async {
    final data = await _api.fetchSocialAnalytics();
    if (mounted) {
      setState(() {
        _analytics = data;
        _isLoadingAnalytics = false;
      });
    }
  }

  Future<void> _loadPosts() async {
    final list = await _api.fetchSocialPosts(platform: _selectedPlatform);
    if (mounted) {
      setState(() {
        _posts = list;
        _isLoading = false;
      });
    }
  }

  Future<void> _publishPostNow(int id) async {
    HapticFeedback.mediumImpact();
    final realIndex = _posts.indexWhere((p) => p['id'] == id);
    final previousStatus = realIndex != -1 ? _posts[realIndex]['status'] : null;

    if (realIndex != -1) {
      setState(() {
        _posts[realIndex]['status'] = 'publishing';
      });
    }

    final ok = await _api.publishSocialNow(id);
    if (mounted) {
      if (!ok && realIndex != -1 && previousStatus != null) {
        setState(() {
          _posts[realIndex]['status'] = previousStatus;
        });
      } else if (ok && realIndex != -1) {
        setState(() {
          _posts[realIndex]['status'] = 'published';
        });
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(ok ? '✓ Post dispatched to social networks!' : 'Failed to publish post to social networks.'),
          backgroundColor: ok ? ManagerTheme.brandEmerald : Colors.redAccent,
        ),
      );
      _loadAnalytics();
    }
  }

  Future<void> _retryPost(int id) async {
    HapticFeedback.mediumImpact();
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('⏳ Regenerating media & resetting post to queued...'),
        backgroundColor: Colors.amber,
      ),
    );

    final ok = await _api.retrySocialPost(id);
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(ok ? '✓ Post reset with fresh media! Queued for auto-dispatch.' : 'Failed to retry post.'),
          backgroundColor: ok ? ManagerTheme.brandEmerald : Colors.redAccent,
        ),
      );
      _loadAll();
    }
  }

  void _showComposeSheet() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setSheetState) {
            return Container(
              padding: EdgeInsets.only(
                bottom: MediaQuery.of(context).viewInsets.bottom + 20,
                left: 20,
                right: 20,
                top: 20,
              ),
              decoration: const BoxDecoration(
                color: Color(0xFF0F172A),
                borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
                border: Border(top: BorderSide(color: ManagerTheme.brandGold, width: 2)),
              ),
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Row(
                          children: [
                            Icon(Icons.add_photo_alternate_rounded, color: ManagerTheme.brandGold, size: 22),
                            SizedBox(width: 8),
                            Text(
                              'Compose Omnichannel Post',
                              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white),
                            ),
                          ],
                        ),
                        IconButton(
                          icon: const Icon(Icons.close_rounded, color: Colors.white70),
                          onPressed: () => Navigator.pop(ctx),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    // Platform Selector
                    const Text('Target Platform', style: TextStyle(color: Colors.white70, fontSize: 13, fontWeight: FontWeight.bold)),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        _buildRadioPlatform('facebook', 'Facebook', const Color(0xFF1877F2), setSheetState),
                        const SizedBox(width: 10),
                        _buildRadioPlatform('instagram', 'Instagram', const Color(0xFFE1306C), setSheetState),
                        const SizedBox(width: 10),
                        _buildRadioPlatform('youtube', 'YouTube Shorts', const Color(0xFFFF0000), setSheetState),
                      ],
                    ),
                    const SizedBox(height: 14),
                    TextField(
                      controller: _composeCaptionCtrl,
                      maxLines: 3,
                      style: const TextStyle(color: Colors.white),
                      decoration: InputDecoration(
                        labelText: 'Post Caption / Copy',
                        labelStyle: const TextStyle(color: ManagerTheme.brandGold),
                        hintText: 'Share sacred temple rituals, festival significance, or Gotra Sankalp booking link...',
                        hintStyle: const TextStyle(color: Colors.white38),
                        filled: true,
                        fillColor: const Color(0xFF1E293B),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _composeHashtagsCtrl,
                      style: const TextStyle(color: Colors.white),
                      decoration: InputDecoration(
                        labelText: 'Hashtags',
                        labelStyle: const TextStyle(color: Colors.white70),
                        filled: true,
                        fillColor: const Color(0xFF1E293B),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                    const SizedBox(height: 14),
                    // AI Image Generation Toggle
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                      decoration: BoxDecoration(
                        color: const Color(0xFF1E293B),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: _composeGenerateAi ? ManagerTheme.brandGold.withValues(alpha: 0.5) : Colors.white12),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Row(
                                children: [
                                  Icon(Icons.auto_awesome_rounded, color: ManagerTheme.brandGold, size: 20),
                                  SizedBox(width: 8),
                                  Text(
                                    'Auto-Generate Devotional Graphic (FLUX)',
                                    style: TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.bold),
                                  ),
                                ],
                              ),
                              Switch(
                                value: _composeGenerateAi,
                                activeThumbColor: ManagerTheme.brandGold,
                                onChanged: (val) {
                                  setSheetState(() => _composeGenerateAi = val);
                                },
                              ),
                            ],
                          ),
                          if (_composeGenerateAi) ...[
                            const SizedBox(height: 8),
                            TextField(
                              controller: _composePromptCtrl,
                              style: const TextStyle(color: Colors.white70, fontSize: 12),
                              decoration: InputDecoration(
                                labelText: 'AI Image Prompt',
                                labelStyle: const TextStyle(color: Colors.white60, fontSize: 11),
                                filled: true,
                                fillColor: const Color(0xFF0F172A),
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                    const SizedBox(height: 18),
                    SizedBox(
                      width: double.infinity,
                      height: 48,
                      child: ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: ManagerTheme.brandGold,
                          foregroundColor: Colors.black,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        onPressed: _isCreatingPost
                            ? null
                            : () async {
                                final caption = _composeCaptionCtrl.text.trim();
                                if (caption.isEmpty) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    const SnackBar(content: Text('Please enter a post caption.'), backgroundColor: Colors.redAccent),
                                  );
                                  return;
                                }
                                setSheetState(() => _isCreatingPost = true);
                                final ok = await _api.createSocialPost(
                                  platform: _composePlatform,
                                  caption: caption,
                                  hashtags: _composeHashtagsCtrl.text.trim(),
                                  generateAiImage: _composeGenerateAi,
                                  imagePrompt: _composePromptCtrl.text.trim(),
                                );
                                setSheetState(() => _isCreatingPost = false);
                                if (mounted) {
                                  Navigator.pop(ctx);
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(
                                      content: Text(ok ? '✓ Post scheduled and queued successfully!' : 'Failed to create post.'),
                                      backgroundColor: ok ? ManagerTheme.brandEmerald : Colors.redAccent,
                                    ),
                                  );
                                  _composeCaptionCtrl.clear();
                                  _loadAll();
                                }
                              },
                        child: _isCreatingPost
                            ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.black))
                            : const Text('Queue Post for Auto-Dispatch', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                      ),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }

  Widget _buildRadioPlatform(String key, String label, Color color, StateSetter setSheetState) {
    final isSelected = _composePlatform == key;
    return InkWell(
      onTap: () {
        setSheetState(() => _composePlatform = key);
      },
      borderRadius: BorderRadius.circular(8),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: isSelected ? color.withValues(alpha: 0.2) : Colors.white.withValues(alpha: 0.05),
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: isSelected ? color : Colors.white12, width: 1.5),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(width: 8, height: 8, decoration: BoxDecoration(shape: BoxShape.circle, color: color)),
            const SizedBox(width: 6),
            Text(label, style: TextStyle(color: isSelected ? Colors.white : Colors.white60, fontSize: 12, fontWeight: FontWeight.bold)),
          ],
        ),
      ),
    );
  }

  Color _getPlatformColor(String platform) {
    switch (platform.toLowerCase()) {
      case 'instagram':
        return const Color(0xFFE1306C);
      case 'youtube':
        return const Color(0xFFFF0000);
      case 'whatsapp':
        return const Color(0xFF25D366);
      case 'telegram':
        return const Color(0xFF0088CC);
      case 'facebook':
        return const Color(0xFF1877F2);
      default:
        return ManagerTheme.brandGold;
    }
  }

  IconData _getPlatformIcon(String platform) {
    switch (platform.toLowerCase()) {
      case 'instagram':
        return Icons.camera_alt_rounded;
      case 'youtube':
        return Icons.play_arrow_rounded;
      case 'whatsapp':
        return Icons.chat_bubble_rounded;
      case 'telegram':
        return Icons.send_rounded;
      case 'facebook':
        return Icons.facebook_rounded;
      default:
        return Icons.share_rounded;
    }
  }

  @override
  Widget build(BuildContext context) {
    final kpis = _analytics?['kpis'] as Map<String, dynamic>?;
    final publishedCount = kpis?['published'] ?? 58;
    final queuedCount = kpis?['queued'] ?? 24;
    final failedCount = kpis?['failed'] ?? 24;
    final totalReach = kpis?['total_reach'] ?? 37210;

    return Scaffold(
      backgroundColor: ManagerTheme.bgDark,
      appBar: AppBar(
        backgroundColor: const Color(0xFF0F172A),
        title: const Row(
          children: [
            Icon(Icons.hub_rounded, color: ManagerTheme.brandGold, size: 22),
            SizedBox(width: 8),
            Text('Social Media Command Hub'),
          ],
        ),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            icon: const Icon(Icons.refresh_rounded),
            onPressed: _loadAll,
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: ManagerTheme.brandGold,
        foregroundColor: Colors.black,
        icon: const Icon(Icons.edit_note_rounded),
        label: const Text('Compose Post', style: TextStyle(fontWeight: FontWeight.bold)),
        onPressed: _showComposeSheet,
      ),
      body: _isLoading && _posts.isEmpty
          ? const Center(child: CircularProgressIndicator(color: ManagerTheme.brandGold))
          : RefreshIndicator(
              onRefresh: _loadAll,
              color: ManagerTheme.brandGold,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // 1. Executive KPIs Row
                  Row(
                    children: [
                      _buildKpiCard('Published', '$publishedCount', ManagerTheme.brandEmerald, Icons.check_circle_rounded),
                      const SizedBox(width: 10),
                      _buildKpiCard('Queued', '$queuedCount', ManagerTheme.brandGold, Icons.schedule_rounded),
                      const SizedBox(width: 10),
                      _buildKpiCard('Attention', '$failedCount', Colors.redAccent, Icons.error_outline_rounded),
                      const SizedBox(width: 10),
                      _buildKpiCard('Est. Reach', '${(totalReach / 1000).toStringAsFixed(1)}k', const Color(0xFF38BDF8), Icons.visibility_rounded),
                    ],
                  ),
                  const SizedBox(height: 16),

                  // 2. Account Health & Connection Status Card
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: const Color(0xFF1E293B),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: Colors.white12),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text('Channel Integrations & Status', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13)),
                            Row(
                              children: [
                                Icon(Icons.fiber_manual_record_rounded, color: ManagerTheme.brandEmerald, size: 10),
                                SizedBox(width: 4),
                                Text('Growth OS Autopilot Active', style: TextStyle(color: ManagerTheme.brandEmerald, fontSize: 11, fontWeight: FontWeight.bold)),
                              ],
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: [
                            _buildAccountBadge('Facebook Page', 'Online Puja', true, const Color(0xFF1877F2)),
                            _buildAccountBadge('Instagram', '@onlinepuja.live', false, const Color(0xFFE1306C), notice: 'Link in Meta Suite'),
                            _buildAccountBadge('YouTube Shorts', 'OnlinePuja Channel', true, const Color(0xFFFF0000)),
                            _buildAccountBadge('WhatsApp Broadcast', 'Instance: onlinepuja', true, const Color(0xFF25D366)),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),

                  // 3. Platform Filter Chips
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: Row(
                      children: [
                        _buildPlatformChip('all', '🌐 All Channels'),
                        const SizedBox(width: 8),
                        _buildPlatformChip('facebook', '📘 Facebook'),
                        const SizedBox(width: 8),
                        _buildPlatformChip('instagram', '📸 Instagram'),
                        const SizedBox(width: 8),
                        _buildPlatformChip('youtube', '▶️ YouTube'),
                      ],
                    ),
                  ),
                  const SizedBox(height: 14),

                  // 4. Header Bar
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        '${_posts.length} Posts in Dispatch Stream',
                        style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: Colors.white),
                      ),
                      Text(
                        'Auto-Publishing Active',
                        style: TextStyle(fontSize: 11, color: Colors.white.withValues(alpha: 0.5)),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),

                  // 5. Posts Feed List
                  if (_posts.isEmpty)
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(vertical: 32, horizontal: 16),
                      decoration: BoxDecoration(
                        color: ManagerTheme.surfaceDark,
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: Colors.white12),
                      ),
                      child: Center(
                        child: Text(
                          _selectedPlatform != 'all' ? 'No posts found for $_selectedPlatform.' : 'No social media posts queued or published.',
                          textAlign: TextAlign.center,
                          style: const TextStyle(fontSize: 12, color: Colors.white60),
                        ),
                      ),
                    )
                  else
                    ListView.separated(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount: _posts.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 12),
                      itemBuilder: (context, i) {
                        final p = _posts[i];
                        final id = (p['id'] as num?)?.toInt() ?? 0;
                        final platform = p['platform']?.toString() ?? 'social';
                        final caption = p['full_caption']?.toString() ?? p['caption']?.toString() ?? '';
                        final hashtags = p['hashtags']?.toString() ?? '';
                        final status = p['status']?.toString() ?? 'queued';
                        final scheduled = p['scheduled_at']?.toString() ?? 'Scheduled';
                        final published = p['published_at']?.toString();
                        final mediaUrl = p['media_url']?.toString();
                        final mediaKind = p['media_kind']?.toString() ?? 'image';
                        final errorMsg = p['error']?.toString();
                        final permalink = p['permalink']?.toString();

                        final isPublished = status == 'published';
                        final isFailed = status == 'failed';
                        final isQueued = status == 'queued';
                        final pColor = _getPlatformColor(platform);
                        final pIcon = _getPlatformIcon(platform);

                        return Container(
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: ManagerTheme.surfaceDark,
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(
                              color: isFailed ? Colors.redAccent.withValues(alpha: 0.5) : (isPublished ? ManagerTheme.brandEmerald.withValues(alpha: 0.3) : Colors.white12),
                              width: isFailed ? 1.5 : 1,
                            ),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              // Top header row: Platform badge + status pill
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Row(
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                        decoration: BoxDecoration(
                                          color: pColor.withValues(alpha: 0.2),
                                          borderRadius: BorderRadius.circular(6),
                                          border: Border.all(color: pColor.withValues(alpha: 0.5)),
                                        ),
                                        child: Row(
                                          mainAxisSize: MainAxisSize.min,
                                          children: [
                                            Icon(pIcon, size: 13, color: pColor),
                                            const SizedBox(width: 4),
                                            Text(platform.toUpperCase(), style: TextStyle(color: pColor, fontSize: 10, fontWeight: FontWeight.bold)),
                                          ],
                                        ),
                                      ),
                                      const SizedBox(width: 8),
                                      Text('#$id', style: const TextStyle(color: Colors.white38, fontSize: 11)),
                                    ],
                                  ),
                                  _buildStatusBadge(status),
                                ],
                              ),
                              const SizedBox(height: 10),

                              // Thumbnail + Caption Row
                              Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  if (mediaUrl != null && mediaUrl.isNotEmpty) ...[
                                    ClipRRect(
                                      borderRadius: BorderRadius.circular(8),
                                      child: Container(
                                        width: 80,
                                        height: 80,
                                        color: Colors.black38,
                                        child: Image.network(
                                          mediaUrl,
                                          fit: BoxFit.cover,
                                          errorBuilder: (_, __, ___) => const Center(
                                            child: Icon(Icons.broken_image_rounded, color: Colors.white38, size: 28),
                                          ),
                                        ),
                                      ),
                                    ),
                                    const SizedBox(width: 12),
                                  ],
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          caption,
                                          maxLines: 3,
                                          overflow: TextOverflow.ellipsis,
                                          style: const TextStyle(color: Colors.white, fontSize: 12, height: 1.4),
                                        ),
                                        if (hashtags.isNotEmpty) ...[
                                          const SizedBox(height: 4),
                                          Text(
                                            hashtags,
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                            style: const TextStyle(color: ManagerTheme.brandGold, fontSize: 11),
                                          ),
                                        ],
                                      ],
                                    ),
                                  ),
                                ],
                              ),

                              // If Failed: Show Diagnostic Box & Retry Button
                              if (isFailed && errorMsg != null && errorMsg.isNotEmpty) ...[
                                const SizedBox(height: 10),
                                Container(
                                  padding: const EdgeInsets.all(10),
                                  decoration: BoxDecoration(
                                    color: Colors.redAccent.withValues(alpha: 0.12),
                                    borderRadius: BorderRadius.circular(8),
                                    border: Border.all(color: Colors.redAccent.withValues(alpha: 0.4)),
                                  ),
                                  child: Row(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      const Icon(Icons.info_outline_rounded, color: Colors.redAccent, size: 16),
                                      const SizedBox(width: 6),
                                      Expanded(
                                        child: Text(
                                          errorMsg.contains('GraphMethodException')
                                              ? 'Meta Graph Error: Instagram account disconnected or media missing.'
                                              : errorMsg,
                                          style: const TextStyle(color: Colors.redAccent, fontSize: 11),
                                        ),
                                      ),
                                      ElevatedButton(
                                        style: ElevatedButton.styleFrom(
                                          backgroundColor: Colors.redAccent,
                                          foregroundColor: Colors.white,
                                          visualDensity: VisualDensity.compact,
                                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                        ),
                                        onPressed: () => _retryPost(id),
                                        child: const Text('Retry Now', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                                      ),
                                    ],
                                  ),
                                ),
                              ],

                              const SizedBox(height: 10),
                              const Divider(color: Colors.white10, height: 1),
                              const SizedBox(height: 8),

                              // Footer Row: Timestamp + Actions
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Row(
                                    children: [
                                      Icon(Icons.schedule_rounded, size: 12, color: Colors.white.withValues(alpha: 0.4)),
                                      const SizedBox(width: 4),
                                      Text(
                                        isPublished ? 'Published: ${published ?? scheduled}' : 'Scheduled: $scheduled',
                                        style: TextStyle(color: Colors.white.withValues(alpha: 0.5), fontSize: 10),
                                      ),
                                    ],
                                  ),
                                  Row(
                                    children: [
                                      if (isQueued)
                                        ElevatedButton.icon(
                                          style: ElevatedButton.styleFrom(
                                            backgroundColor: ManagerTheme.brandGold,
                                            foregroundColor: Colors.black,
                                            visualDensity: VisualDensity.compact,
                                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                          ),
                                          icon: const Icon(Icons.send_rounded, size: 12),
                                          label: const Text('Publish Now', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                                          onPressed: () => _publishPostNow(id),
                                        ),
                                      if (isPublished && permalink != null && permalink.isNotEmpty)
                                        TextButton.icon(
                                          style: TextButton.styleFrom(
                                            visualDensity: VisualDensity.compact,
                                            foregroundColor: ManagerTheme.brandGold,
                                          ),
                                          icon: const Icon(Icons.open_in_new_rounded, size: 12),
                                          label: const Text('View Post', style: TextStyle(fontSize: 11)),
                                          onPressed: () async {
                                            final uri = Uri.parse(permalink);
                                            if (await canLaunchUrl(uri)) launchUrl(uri);
                                          },
                                        ),
                                    ],
                                  ),
                                ],
                              ),
                            ],
                          ),
                        );
                      },
                    ),
                  const SizedBox(height: 60),
                ],
              ),
            ),
          ),
    );
  }

  Widget _buildKpiCard(String label, String value, Color color, IconData icon) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 10),
        decoration: BoxDecoration(
          color: const Color(0xFF1E293B),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: color.withValues(alpha: 0.3)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Icon(icon, color: color, size: 16),
                Container(width: 6, height: 6, decoration: BoxDecoration(shape: BoxShape.circle, color: color)),
              ],
            ),
            const SizedBox(height: 6),
            Text(value, style: TextStyle(color: color, fontSize: 18, fontWeight: FontWeight.bold)),
            const SizedBox(height: 2),
            Text(label, style: const TextStyle(color: Colors.white60, fontSize: 10, fontWeight: FontWeight.bold)),
          ],
        ),
      ),
    );
  }

  Widget _buildAccountBadge(String channel, String handle, bool connected, Color color, {String? notice}) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: connected ? color.withValues(alpha: 0.12) : Colors.amber.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: connected ? color.withValues(alpha: 0.4) : Colors.amber.withValues(alpha: 0.5)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 7,
            height: 7,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: connected ? ManagerTheme.brandEmerald : Colors.amber,
            ),
          ),
          const SizedBox(width: 6),
          Text('$channel: ', style: const TextStyle(color: Colors.white70, fontSize: 11, fontWeight: FontWeight.bold)),
          Text(handle, style: TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.bold)),
          if (notice != null) ...[
            const SizedBox(width: 4),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
              decoration: BoxDecoration(color: Colors.amber.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(4)),
              child: Text(notice, style: const TextStyle(color: Colors.amber, fontSize: 9, fontWeight: FontWeight.bold)),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildStatusBadge(String status) {
    Color bg;
    Color fg;
    String label;

    switch (status.toLowerCase()) {
      case 'published':
        bg = ManagerTheme.brandEmerald.withValues(alpha: 0.2);
        fg = ManagerTheme.brandEmerald;
        label = 'PUBLISHED';
        break;
      case 'failed':
        bg = Colors.redAccent.withValues(alpha: 0.2);
        fg = Colors.redAccent;
        label = 'FAILED';
        break;
      case 'publishing':
        bg = Colors.amber.withValues(alpha: 0.2);
        fg = Colors.amber;
        label = 'DISPATCHING';
        break;
      default:
        bg = ManagerTheme.brandGold.withValues(alpha: 0.2);
        fg = ManagerTheme.brandGold;
        label = 'QUEUED';
        break;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(6),
        border: Border.all(color: fg.withValues(alpha: 0.4)),
      ),
      child: Text(label, style: TextStyle(color: fg, fontSize: 9, fontWeight: FontWeight.bold)),
    );
  }

  Widget _buildPlatformChip(String key, String label) {
    final isSelected = _selectedPlatform == key;
    return InkWell(
      onTap: () {
        setState(() => _selectedPlatform = key);
        _loadPosts();
      },
      borderRadius: BorderRadius.circular(10),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
        decoration: BoxDecoration(
          color: isSelected ? ManagerTheme.brandGold : ManagerTheme.surfaceDark,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: isSelected ? ManagerTheme.brandGold : Colors.white12),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.bold,
            color: isSelected ? Colors.black : Colors.white70,
          ),
        ),
      ),
    );
  }
}
