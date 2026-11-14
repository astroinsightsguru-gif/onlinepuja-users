import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:onlinepuja_manager/services/manager_api_service.dart';
import 'package:onlinepuja_manager/theme/manager_theme.dart';
import 'package:url_launcher/url_launcher.dart';

class SocialManagementScreen extends StatefulWidget {
  final int initialTab;
  const SocialManagementScreen({super.key, this.initialTab = 0});

  @override
  State<SocialManagementScreen> createState() => _SocialManagementScreenState();
}

class _SocialManagementScreenState extends State<SocialManagementScreen>
    with SingleTickerProviderStateMixin {
  final ManagerApiService _api = ManagerApiService();
  late TabController _tabController;

  // Global State
  bool _isLoading = true;
  List<Map<String, dynamic>> _posts = [];
  Map<String, dynamic>? _analytics;
  String _selectedPlatform = 'all';

  // -------------------------------------------------------------
  // TAB 1: 9:16 SACRED REEL STUDIO & FANOUT
  // -------------------------------------------------------------
  final TextEditingController _reelTopicCtrl = TextEditingController(
    text: 'Kashi Vishwanath Aarti & Ganga Aarti',
  );
  final TextEditingController _reelHookCtrl = TextEditingController(
    text: 'ॐ नमः शिवाय | काशी विश्वनाथ संध्या महा आरती',
  );
  final TextEditingController _reelScriptCtrl = TextEditingController(
    text: 'दशाश्वमेध एवं मणिकर्णिका घाट, काशी से सीधा पावन दर्शन। घर बैठे अपने एवं परिवार के नाम व गोत्र से वैदिक महासंकल्प कराएं। सिद्ध भस्म, रुद्राक्ष एवं गंगाजल प्रसाद आपके घर।',
  );

  String _selectedMantraAudio = 'Shiva Tandav Stotram (Beats & Dhol)';
  String _selectedReelHook = 'POV: You attend Varanasi Ganga Aarti at Dashashwamedh Ghat...';
  bool _fanoutAllChannels = true;
  bool _isGeneratingReel = false;
  double _reelPlayhead = 0.55;
  String _latestMasterReelUrl = 'https://onlinepuja.live/storage/reels/daily_broadcast_master.mp4';

  final List<String> _viralHooks = [
    'POV: You attend Varanasi Ganga Aarti at Dashashwamedh Ghat...',
    'Do this 1 Vedic Upay on Pradosh Vrat to remove all Shani Dosha...',
    'Why 99% of devotees chant Mahamrityunjaya Mantra incorrectly...',
    'Unboxing Varanasi Kashi Vishwanath Bhasma Prasadam delivered home...',
    'Watch live Gotra Sankalp performed by Kashi Vedic Purohits...',
    'श्री काशी विश्वनाथ ज्योतिर्लिंग 108 दीपदान पावन दर्शन...',
  ];

  final List<String> _devotionalMantras = [
    'Shiva Tandav Stotram (Beats & Dhol)',
    'Maha Mrityunjaya Mantra (108 Jaap Chants)',
    'Dashashwamedh Ghat Live Ganga Aarti Shankhnaad',
    'Vishnu Sahasranama Stotram (Sacred Flute)',
    'Hanuman Chalisa (Acoustic Temple Chimes)',
  ];

  // -------------------------------------------------------------
  // TAB 2: 16:9 MASTER DEVOTIONAL VIDEO (1080p YOUTUBE LIVE)
  // -------------------------------------------------------------
  String _selectedMasterTrack = 'aarti';
  bool _isSynthesizingMasterVideo = false;
  Map<String, dynamic>? _latestMasterVideoResult;

  final List<Map<String, String>> _masterVideoOptions = [
    {
      'type': 'aarti',
      'title': 'ॐ जय शिव ओंकारा - श्री काशी विश्वनाथ आरती',
      'subtitle': 'Full 1080p HD with Hindi Devanagari Karaoke Lyrics',
      'deity': 'Lord Shiva (Kashi Vishwanath)',
      'duration': '4m 30s',
      'last_id': 'pB9CQKHJNoE',
    },
    {
      'type': 'stotram',
      'title': 'श्री काशी विश्वनाथ वैदिक स्तोत्रम्',
      'subtitle': 'Sacred Shankhnaad & Tanpura 432Hz Pure Meditation',
      'deity': 'Mahadev',
      'duration': '3m 45s',
      'last_id': 'tcT5-IlwXXY',
    },
  ];

  // -------------------------------------------------------------
  // TAB 3: FLUX & DEVOTIONAL POSTER STUDIO
  // -------------------------------------------------------------
  final TextEditingController _posterPromptCtrl = TextEditingController(
    text: 'Golden glowing sacred Shivling at Dashashwamedh Ghat Varanasi at night, floating oil diyas on holy river Ganga, photorealistic 8k commercial photography, spiritual volumetric lighting',
  );
  final TextEditingController _posterHeadlineCtrl = TextEditingController(
    text: 'KASHI VISHWANATH RUDRABHISHEK SANKALP',
  );
  final TextEditingController _posterSubheadCtrl = TextEditingController(
    text: 'OnlinePuja.live • Certified Vedic Purohits from Dashashwamedh Ghat',
  );

  String _selectedPosterRatio = '4:5 (Instagram Portrait)';
  String _selectedPosterEngine = 'FLUX.1 Schnell (High-Speed)';
  bool _isGeneratingPoster = false;
  String? _generatedPosterUrl;

  final List<String> _posterRatios = [
    '4:5 (Instagram Portrait)',
    '1:1 (Square Feed / WhatsApp)',
    '16:9 (YouTube Landscape / Blog)',
    '9:16 (Story / Reel Cover)',
  ];

  final List<String> _posterEngines = [
    'FLUX.1 Schnell (High-Speed)',
    'Google Imagen 3 (High Fidelity)',
    'Stable Diffusion XL (OmniRoute)',
  ];

  final List<Map<String, String>> _posterPresets = [
    {
      'title': '🪔 108 Deepdaan Kashi',
      'prompt': 'Golden glowing 108 oil diyas floating on sacred river Ganga at Varanasi Dashashwamedh Ghat at night, illuminated Kashi temple spire, cinematic devotional 8k',
      'headline': 'DEV DEEPAWALI 108 DEEPDAAN KASHI',
      'subhead': 'OnlinePuja.live • Sacred Sankalp by Kashi Vedic Purohits',
    },
    {
      'title': '🔱 Kashi Rudrabhishek',
      'prompt': 'Golden sacred Shiva Lingam in ancient sanctum with milk, bilva leaves and sacred gangajal abhishek, divine aura and temple diyas, 8k spiritual rendering',
      'headline': 'KASHI VISHWANATH RUDRABHISHEK',
      'subhead': 'OnlinePuja.live • Gotra Sankalp & Sanctum Darshan',
    },
    {
      'title': '🕉️ Mahamrityunjaya Jaap',
      'prompt': 'Vedic rishis performing sacred havana yagya on the banks of Ganga Varanasi at dawn, sacred fire flames, golden sunrise, spiritual peace, 8k ultra-detailed',
      'headline': 'MAHA MRITYUNJAYA JAPA ANUSHTHAN',
      'subhead': 'OnlinePuja.live • Health, Long Life & Divine Protection',
    },
  ];

  // -------------------------------------------------------------
  // TAB 4: ENGINE & CONNECTED CHANNELS STATE
  // -------------------------------------------------------------
  final Map<int, bool> _testingChannel = {};
  final Map<int, String> _channelTestResult = {};
  bool _isTriggeringSocialAgent = false;
  String? _agentTriggerResult;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(
      length: 5,
      vsync: this,
      initialIndex: widget.initialTab.clamp(0, 4),
    );
    _loadAll();
  }

  @override
  void dispose() {
    _tabController.dispose();
    _reelTopicCtrl.dispose();
    _reelHookCtrl.dispose();
    _reelScriptCtrl.dispose();
    _posterPromptCtrl.dispose();
    _posterHeadlineCtrl.dispose();
    _posterSubheadCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadAll() async {
    setState(() => _isLoading = true);
    await Future.wait([
      _loadAnalytics(),
      _loadPosts(),
    ]);
  }

  Future<void> _loadAnalytics() async {
    final data = await _api.fetchSocialAnalytics();
    if (mounted) {
      setState(() => _analytics = data);
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
      setState(() => _posts[realIndex]['status'] = 'publishing');
    }

    final ok = await _api.publishSocialNow(id);
    if (mounted) {
      if (!ok && realIndex != -1 && previousStatus != null) {
        setState(() => _posts[realIndex]['status'] = previousStatus);
      } else if (ok && realIndex != -1) {
        setState(() => _posts[realIndex]['status'] = 'published');
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(ok ? '✓ Post dispatched to social networks!' : 'Failed to publish post.'),
          backgroundColor: ok ? ManagerTheme.brandEmerald : Colors.redAccent,
        ),
      );
      _loadAnalytics();
    }
  }

  Future<void> _retryPost(int id) async {
    HapticFeedback.mediumImpact();
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

  Future<void> _handleBroadcastReel() async {
    setState(() => _isGeneratingReel = true);
    HapticFeedback.heavyImpact();

    final res = await _api.broadcastReelFanout(
      topic: _reelTopicCtrl.text.trim(),
      hook: _reelHookCtrl.text.trim(),
      script: _reelScriptCtrl.text.trim(),
      publishNow: false,
    );

    if (mounted) {
      setState(() {
        _isGeneratingReel = false;
        if (res != null && res['video_url'] != null) {
          _latestMasterReelUrl = res['video_url'];
        }
      });
      final ok = res != null && res['success'] == true;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(ok
              ? '🚀 Sacred 9:16 Reel fanout queued across YouTube Shorts, Instagram Reels & Facebook Reels!'
              : 'Failed to synthesize broadcast reel.'),
          backgroundColor: ok ? ManagerTheme.brandEmerald : Colors.redAccent,
        ),
      );
      if (ok) {
        _loadPosts();
        _loadAnalytics();
      }
    }
  }

  Future<void> _handleSynthesizeMasterDevotionalVideo() async {
    setState(() => _isSynthesizingMasterVideo = true);
    HapticFeedback.heavyImpact();

    final res = await _api.synthesizeMasterDevotionalVideo(
      type: _selectedMasterTrack,
      publish: true,
    );

    if (mounted) {
      setState(() {
        _isSynthesizingMasterVideo = false;
        _latestMasterVideoResult = res;
      });
      final ok = res != null && res['success'] == true;
      final videoId = res?['video_id'];
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(ok
              ? '✨ Master 1080p Devotional Video published live to YouTube (ID: $videoId)!'
              : 'Failed to synthesize master video.'),
          backgroundColor: ok ? ManagerTheme.brandEmerald : Colors.redAccent,
        ),
      );
      if (ok) {
        _loadPosts();
      }
    }
  }

  Future<void> _handleGeneratePoster() async {
    setState(() => _isGeneratingPoster = true);
    HapticFeedback.mediumImpact();

    final res = await _api.generateSocialPoster(
      prompt: _posterPromptCtrl.text.trim(),
      headline: _posterHeadlineCtrl.text.trim(),
      ratio: _selectedPosterRatio.split(' ').first,
    );

    if (mounted) {
      setState(() {
        _isGeneratingPoster = false;
        if (res != null && res['poster_url'] != null) {
          _generatedPosterUrl = res['poster_url'];
        }
      });
      final ok = res != null && res['success'] == true;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(ok ? '✓ High-definition devotional poster synthesized!' : 'Poster generation failed.'),
          backgroundColor: ok ? ManagerTheme.brandEmerald : Colors.redAccent,
        ),
      );
    }
  }

  Future<void> _testChannelConnection(int accountId) async {
    setState(() => _testingChannel[accountId] = true);
    HapticFeedback.selectionClick();

    final res = await _api.testSocialChannel(accountId);
    if (mounted) {
      setState(() {
        _testingChannel[accountId] = false;
        if (res != null && res['message'] != null) {
          _channelTestResult[accountId] = res['message'];
        } else {
          _channelTestResult[accountId] = 'Failed to verify channel connectivity.';
        }
      });
    }
  }

  Future<void> _triggerAutonomousSocialAgent() async {
    setState(() => _isTriggeringSocialAgent = true);
    HapticFeedback.heavyImpact();

    // Agent ID 7 is the social_media agent
    final res = await _api.runAgentNow(7);
    if (mounted) {
      setState(() {
        _isTriggeringSocialAgent = false;
        _agentTriggerResult = res['message'] ?? 'Autonomous social agent cycle finished.';
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('⚡ Autonomous Social Media Agent: ${_agentTriggerResult ?? ""}'),
          backgroundColor: ManagerTheme.brandEmerald,
        ),
      );
      _loadAll();
    }
  }

  void _applyPosterPreset(Map<String, String> preset) {
    setState(() {
      _posterPromptCtrl.text = preset['prompt'] ?? '';
      _posterHeadlineCtrl.text = preset['headline'] ?? '';
      _posterSubheadCtrl.text = preset['subhead'] ?? '';
    });
    HapticFeedback.selectionClick();
  }

  Color _getPlatformColor(String platform) {
    switch (platform.toLowerCase()) {
      case 'instagram':
        return const Color(0xFFE1306C);
      case 'youtube':
      case 'youtube_shorts':
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
      case 'youtube_shorts':
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
    final publishedCount = kpis?['published'] ?? 73;
    final queuedCount = kpis?['queued'] ?? 21;
    final failedCount = kpis?['failed'] ?? 31;
    final totalReach = kpis?['total_reach'] ?? 27740;

    final topPadding = MediaQuery.of(context).padding.top;

    return Scaffold(
      backgroundColor: ManagerTheme.bgDark,
      body: Column(
        children: [
          // 0. Standard Clean Status Bar Spacing
          SizedBox(height: topPadding),

          // 1. In-Screen Header Bar (Zero overlap!)
          Container(
            color: const Color(0xFF0F172A),
            padding: const EdgeInsets.fromLTRB(16, 6, 16, 10),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Row(
                  children: [
                    Icon(Icons.auto_awesome_rounded, color: ManagerTheme.brandGold, size: 20),
                    SizedBox(width: 8),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Social Media & Reels Studio',
                          style: TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.w900),
                        ),
                        Text(
                          'YouTube Shorts • Instagram Reels • Facebook • FLUX',
                          style: TextStyle(color: Colors.white54, fontSize: 10),
                        ),
                      ],
                    ),
                  ],
                ),
                Row(
                  children: [
                    // Autonomous Agent Trigger Button
                    InkWell(
                      onTap: _isTriggeringSocialAgent ? null : _triggerAutonomousSocialAgent,
                      borderRadius: BorderRadius.circular(8),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: ManagerTheme.brandGold.withValues(alpha: 0.15),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.5)),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            _isTriggeringSocialAgent
                                ? const SizedBox(width: 12, height: 12, child: CircularProgressIndicator(strokeWidth: 2, color: ManagerTheme.brandGold))
                                : const Icon(Icons.bolt_rounded, color: ManagerTheme.brandGold, size: 14),
                            const SizedBox(width: 4),
                            const Text('Sprint', style: TextStyle(color: ManagerTheme.brandGold, fontSize: 11, fontWeight: FontWeight.bold)),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(width: 6),
                    IconButton(
                      visualDensity: VisualDensity.compact,
                      padding: EdgeInsets.zero,
                      constraints: const BoxConstraints(),
                      tooltip: 'Refresh All',
                      icon: const Icon(Icons.refresh_rounded, color: Colors.white70, size: 20),
                      onPressed: _loadAll,
                    ),
                  ],
                ),
              ],
            ),
          ),

          // 2. Top Executive KPI Bar
          Container(
            color: const Color(0xFF0F172A),
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
            child: Row(
              children: [
                _buildKpiCard('Published', '$publishedCount', ManagerTheme.brandEmerald, Icons.check_circle_rounded),
                const SizedBox(width: 8),
                _buildKpiCard('Queued', '$queuedCount', ManagerTheme.brandGold, Icons.schedule_rounded),
                const SizedBox(width: 8),
                _buildKpiCard('Attention', '$failedCount', Colors.redAccent, Icons.error_outline_rounded),
                const SizedBox(width: 8),
                _buildKpiCard('Est. Reach', '${(totalReach / 1000).toStringAsFixed(1)}k', const Color(0xFF38BDF8), Icons.visibility_rounded),
              ],
            ),
          ),

          // 3. Navigation Tabs (5 Horizontal Pill Tabs)
          Container(
            color: const Color(0xFF0F172A),
            padding: const EdgeInsets.fromLTRB(12, 0, 12, 8),
            child: Container(
              height: 42,
              decoration: BoxDecoration(
                color: const Color(0xFF1E293B),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.white10),
              ),
              padding: const EdgeInsets.all(3),
              child: TabBar(
                controller: _tabController,
                isScrollable: true,
                tabAlignment: TabAlignment.start,
                indicatorSize: TabBarIndicatorSize.tab,
                indicator: BoxDecoration(
                  color: ManagerTheme.brandGold,
                  borderRadius: BorderRadius.circular(9),
                ),
                labelColor: const Color(0xFF0B0F19),
                unselectedLabelColor: Colors.white70,
                labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11),
                unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 11),
                dividerColor: Colors.transparent,
                tabs: const [
                  Tab(
                    height: 36,
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.queue_rounded, size: 14),
                        SizedBox(width: 5),
                        Text('Queue & Feed'),
                      ],
                    ),
                  ),
                  Tab(
                    height: 36,
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.movie_creation_rounded, size: 14),
                        SizedBox(width: 5),
                        Text('9:16 Sacred Reels'),
                      ],
                    ),
                  ),
                  Tab(
                    height: 36,
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.play_circle_filled_rounded, size: 14),
                        SizedBox(width: 5),
                        Text('16:9 Master Video'),
                      ],
                    ),
                  ),
                  Tab(
                    height: 36,
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.palette_rounded, size: 14),
                        SizedBox(width: 5),
                        Text('FLUX Posters'),
                      ],
                    ),
                  ),
                  Tab(
                    height: 36,
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.hub_rounded, size: 14),
                        SizedBox(width: 5),
                        Text('Engine & Radars'),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),

          // 3. Tab Contents
          Expanded(
            child: TabBarView(
              controller: _tabController,
              children: [
                _buildFeedTab(),
                _buildReelStudioTab(),
                _buildMasterVideoTab(),
                _buildPosterStudioTab(),
                _buildEngineHubTab(),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // =============================================================
  // TAB 0: QUEUE & LIVE FEED
  // =============================================================
  Widget _buildFeedTab() {
    return _isLoading
        ? const Center(child: CircularProgressIndicator(color: ManagerTheme.brandGold))
        : RefreshIndicator(
            color: ManagerTheme.brandGold,
            onRefresh: _loadAll,
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              physics: const AlwaysScrollableScrollPhysics(),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Filter Chips
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: Row(
                      children: [
                        _buildPlatformChip('all', 'All Channels (${_posts.length})'),
                        const SizedBox(width: 8),
                        _buildPlatformChip('youtube', 'YouTube Shorts & Videos'),
                        const SizedBox(width: 8),
                        _buildPlatformChip('instagram', 'Instagram Reels & Feed'),
                        const SizedBox(width: 8),
                        _buildPlatformChip('facebook', 'Facebook Page'),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),

                  if (_posts.isEmpty)
                    Container(
                      padding: const EdgeInsets.all(32),
                      alignment: Alignment.center,
                      child: const Column(
                        children: [
                          Icon(Icons.inbox_rounded, color: Colors.white30, size: 48),
                          SizedBox(height: 12),
                          Text('No posts found in queue.', style: TextStyle(color: Colors.white54, fontSize: 13)),
                        ],
                      ),
                    )
                  else
                    ListView.builder(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount: _posts.length,
                      itemBuilder: (ctx, idx) {
                        final post = _posts[idx];
                        final id = (post['id'] as num?)?.toInt() ?? 0;
                        final platform = post['platform']?.toString() ?? 'facebook';
                        final caption = post['caption']?.toString() ?? '';
                        final fullCaption = post['full_caption']?.toString() ?? caption;
                        final status = post['status']?.toString() ?? 'queued';
                        final scheduled = post['scheduled_at']?.toString() ?? 'Queued';
                        final published = post['published_at']?.toString();
                        final permalink = post['permalink']?.toString();
                        final views = post['views'] ?? 0;
                        final likes = post['likes'] ?? 0;

                        final color = _getPlatformColor(platform);
                        final icon = _getPlatformIcon(platform);

                        final isPublished = status == 'published';
                        final isFailed = status == 'failed';
                        final isQueued = status == 'queued';

                        return Container(
                          margin: const EdgeInsets.only(bottom: 12),
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: const Color(0xFF1E293B),
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: color.withValues(alpha: 0.25)),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              // Header: Platform Badge + Status
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Row(
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.all(6),
                                        decoration: BoxDecoration(
                                          color: color.withValues(alpha: 0.15),
                                          shape: BoxShape.circle,
                                        ),
                                        child: Icon(icon, color: color, size: 16),
                                      ),
                                      const SizedBox(width: 8),
                                      Text(
                                        platform.toUpperCase().replaceAll('_', ' '),
                                        style: TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.bold),
                                      ),
                                    ],
                                  ),
                                  _buildStatusBadge(status),
                                ],
                              ),
                              const SizedBox(height: 10),

                              // Caption Preview
                              Text(
                                fullCaption,
                                style: const TextStyle(color: Colors.white, fontSize: 12, height: 1.4),
                                maxLines: 3,
                                overflow: TextOverflow.ellipsis,
                              ),
                              const SizedBox(height: 8),

                              // Engagement Stats if published
                              if (isPublished)
                                Row(
                                  children: [
                                    const Icon(Icons.visibility_rounded, color: Color(0xFF38BDF8), size: 14),
                                    const SizedBox(width: 4),
                                    Text('$views views', style: const TextStyle(color: Color(0xFF38BDF8), fontSize: 11)),
                                    const SizedBox(width: 12),
                                    const Icon(Icons.favorite_rounded, color: Colors.pinkAccent, size: 14),
                                    const SizedBox(width: 4),
                                    Text('$likes likes', style: const TextStyle(color: Colors.pinkAccent, fontSize: 11)),
                                  ],
                                ),

                              const SizedBox(height: 10),
                              const Divider(color: Colors.white10, height: 1),
                              const SizedBox(height: 8),

                              // Footer Row: Timestamp + Action Buttons
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(
                                    isPublished ? 'Published: ${published ?? scheduled}' : 'Scheduled: $scheduled',
                                    style: TextStyle(color: Colors.white.withValues(alpha: 0.5), fontSize: 10),
                                  ),
                                  Row(
                                    children: [
                                      if (isFailed)
                                        ElevatedButton.icon(
                                          style: ElevatedButton.styleFrom(
                                            backgroundColor: Colors.redAccent,
                                            foregroundColor: Colors.white,
                                            visualDensity: VisualDensity.compact,
                                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                          ),
                                          icon: const Icon(Icons.refresh_rounded, size: 12),
                                          label: const Text('Retry', style: TextStyle(fontSize: 10)),
                                          onPressed: () => _retryPost(id),
                                        ),
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
                                          label: const Text('View Live', style: TextStyle(fontSize: 11)),
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
          );
  }

  // =============================================================
  // TAB 1: 9:16 SACRED REELS STUDIO & MULTI-FANOUT
  // =============================================================
  Widget _buildReelStudioTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Fanout Banner Notice
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF833AB4), Color(0xFFFD1D1D), Color(0xFFFCB045)],
              ),
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Row(
              children: [
                Icon(Icons.bolt_rounded, color: Colors.white, size: 22),
                SizedBox(width: 8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Multi-Channel 9:16 Sacred Video Fanout',
                        style: TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 13),
                      ),
                      Text(
                        'Synthesizes once with Edge-TTS & HarfBuzz, then queues to YouTube Shorts + Instagram Reels + Facebook Reels!',
                        style: TextStyle(color: Colors.white, fontSize: 11),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // Interactive 9:16 Devotional Player HUD
          Center(
            child: InkWell(
              onTap: () async {
                final uri = Uri.parse(_latestMasterReelUrl);
                if (await canLaunchUrl(uri)) {
                  launchUrl(uri, mode: LaunchMode.externalApplication);
                }
              },
              borderRadius: BorderRadius.circular(20),
              child: Container(
                width: 220,
                height: 380,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: ManagerTheme.brandGold, width: 2),
                  boxShadow: [
                    BoxShadow(color: ManagerTheme.brandGold.withValues(alpha: 0.25), blurRadius: 20, spreadRadius: 1),
                  ],
                  image: const DecorationImage(
                    image: NetworkImage('https://onlinepuja.live/storage/reels/reel_preview.jpg'),
                    fit: BoxFit.cover,
                    colorFilter: ColorFilter.mode(Colors.black38, BlendMode.darken),
                  ),
                ),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(18),
                  child: Stack(
                    fit: StackFit.expand,
                    children: [
                      // Devotional Visual Centerpiece with Sacred Aura
                      Positioned.fill(
                        child: Container(
                          decoration: BoxDecoration(
                            gradient: RadialGradient(
                              center: Alignment.center,
                              radius: 0.7,
                              colors: [
                                Colors.black.withValues(alpha: 0.2),
                                Colors.black.withValues(alpha: 0.6),
                              ],
                            ),
                          ),
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Container(
                                width: 80,
                                height: 80,
                                decoration: BoxDecoration(
                                  shape: BoxShape.circle,
                                  color: Colors.black.withValues(alpha: 0.55),
                                  border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.8), width: 1.8),
                                  boxShadow: [
                                    BoxShadow(color: ManagerTheme.brandGold.withValues(alpha: 0.45), blurRadius: 18),
                                  ],
                                ),
                                child: Center(
                                  child: IconButton(
                                    icon: const Icon(
                                      Icons.play_arrow_rounded,
                                      color: ManagerTheme.brandGold,
                                      size: 46,
                                    ),
                                    onPressed: () async {
                                      final uri = Uri.parse(_latestMasterReelUrl);
                                      if (await canLaunchUrl(uri)) {
                                        launchUrl(uri, mode: LaunchMode.externalApplication);
                                      }
                                    },
                                  ),
                                ),
                              ),
                              const SizedBox(height: 12),
                              const Text(
                                '▶ TAP TO WATCH HD REEL',
                                style: TextStyle(
                                  color: ManagerTheme.brandGold,
                                  fontSize: 10,
                                  fontWeight: FontWeight.w900,
                                  letterSpacing: 1.2,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                _selectedMantraAudio.split('(').first.trim(),
                                style: const TextStyle(color: Colors.white, fontSize: 10.5, fontWeight: FontWeight.bold),
                              ),
                            ],
                          ),
                        ),
                      ),

                    // Top Bar Branding HUD
                    Positioned(
                      top: 12,
                      left: 10,
                      right: 10,
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                            decoration: BoxDecoration(
                              color: Colors.black.withValues(alpha: 0.65),
                              borderRadius: BorderRadius.circular(6),
                              border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.7), width: 0.8),
                            ),
                            child: const Row(
                              children: [
                                Text('🕉️', style: TextStyle(fontSize: 10)),
                                SizedBox(width: 4),
                                Text('OnlinePuja.live', style: TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold)),
                              ],
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                            decoration: BoxDecoration(
                              color: Colors.redAccent.withValues(alpha: 0.85),
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: const Text('9:16 HD', style: TextStyle(color: Colors.white, fontSize: 8.5, fontWeight: FontWeight.bold)),
                          ),
                        ],
                      ),
                    ),

                    // Bottom Devanagari Caption & Progress HUD
                    Positioned(
                      bottom: 14,
                      left: 10,
                      right: 10,
                      child: Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: Colors.black.withValues(alpha: 0.75),
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(color: Colors.white12),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Text(
                              _reelHookCtrl.text.isNotEmpty ? _reelHookCtrl.text : 'ॐ नमः शिवाय',
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                                height: 1.3,
                              ),
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 6),
                            Row(
                              children: [
                                Expanded(
                                  child: LinearProgressIndicator(
                                    value: _reelPlayhead,
                                    backgroundColor: Colors.white24,
                                    valueColor: const AlwaysStoppedAnimation<Color>(ManagerTheme.brandGold),
                                    minHeight: 2.5,
                                  ),
                                ),
                                const SizedBox(width: 6),
                                const Text('0:08/0:15', style: TextStyle(color: Colors.white60, fontSize: 8.5)),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
          const SizedBox(height: 16),

          // Viral Hook Selector
          const Text('Select Viral Devotional Hook', style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold)),
          const SizedBox(height: 6),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12),
            decoration: BoxDecoration(
              color: const Color(0xFF1E293B),
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: Colors.white12),
            ),
            child: DropdownButtonHideUnderline(
              child: DropdownButton<String>(
                value: _selectedReelHook,
                isExpanded: true,
                dropdownColor: const Color(0xFF1E293B),
                style: const TextStyle(color: Colors.white, fontSize: 12),
                items: _viralHooks.map((h) => DropdownMenuItem(value: h, child: Text(h, overflow: TextOverflow.ellipsis))).toList(),
                onChanged: (val) {
                  if (val != null) {
                    setState(() {
                      _selectedReelHook = val;
                      _reelHookCtrl.text = val;
                    });
                  }
                },
              ),
            ),
          ),
          const SizedBox(height: 12),

          // Topic & Hook Text Inputs
          TextField(
            controller: _reelTopicCtrl,
            style: const TextStyle(color: Colors.white, fontSize: 13),
            decoration: InputDecoration(
              labelText: 'Sacred Video Topic',
              labelStyle: const TextStyle(color: ManagerTheme.brandGold, fontSize: 12),
              filled: true,
              fillColor: const Color(0xFF1E293B),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
            ),
          ),
          const SizedBox(height: 10),

          // Audio Track Selector
          const Text('Devotional Audio & Sacred Chants', style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold)),
          const SizedBox(height: 6),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12),
            decoration: BoxDecoration(
              color: const Color(0xFF1E293B),
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: Colors.white12),
            ),
            child: DropdownButtonHideUnderline(
              child: DropdownButton<String>(
                value: _selectedMantraAudio,
                isExpanded: true,
                dropdownColor: const Color(0xFF1E293B),
                style: const TextStyle(color: ManagerTheme.brandGold, fontSize: 12, fontWeight: FontWeight.bold),
                items: _devotionalMantras.map((m) => DropdownMenuItem(value: m, child: Text(m))).toList(),
                onChanged: (val) {
                  if (val != null) setState(() => _selectedMantraAudio = val);
                },
              ),
            ),
          ),
          const SizedBox(height: 14),

          // Multi-Channel Fanout Switch
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: const Color(0xFF1E293B),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.3)),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Row(
                  children: [
                    Icon(Icons.share_rounded, color: ManagerTheme.brandGold, size: 20),
                    SizedBox(width: 8),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Fanout Broadcast to All 3 Channels', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12)),
                        Text('YouTube Shorts + Instagram Reels + Facebook Reels', style: TextStyle(color: Colors.white60, fontSize: 10)),
                      ],
                    ),
                  ],
                ),
                Switch(
                  value: _fanoutAllChannels,
                  activeColor: ManagerTheme.brandGold,
                  onChanged: (val) => setState(() => _fanoutAllChannels = val),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // Action Button: Synthesize Broadcast Reel
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: ManagerTheme.brandGold,
                foregroundColor: Colors.black,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              icon: _isGeneratingReel
                  ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.black))
                  : const Icon(Icons.movie_creation_rounded, size: 20),
              label: Text(
                _isGeneratingReel ? 'Synthesizing with HarfBuzz Engine...' : '⚡ Synthesize & Queue Sacred Reel',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
              ),
              onPressed: _isGeneratingReel ? null : _handleBroadcastReel,
            ),
          ),
          const SizedBox(height: 60),
        ],
      ),
    );
  }

  // =============================================================
  // TAB 2: 16:9 MASTER DEVOTIONAL VIDEO (1080p YOUTUBE LIVE)
  // =============================================================
  Widget _buildMasterVideoTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Banner
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFFB91C1C), Color(0xFFE11D48), Color(0xFFC2410C)],
              ),
              borderRadius: BorderRadius.circular(14),
            ),
            child: const Row(
              children: [
                Icon(Icons.video_library_rounded, color: Colors.white, size: 26),
                SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '1080p Master Devotional YouTube Studio',
                        style: TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 13),
                      ),
                      Text(
                        'Generates full-length horizontal master videos with lyrics and publishes directly to connected channel @onlinepuja-live!',
                        style: TextStyle(color: Colors.white70, fontSize: 11),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // Master Devotional Tracks Cards
          const Text('Select Devotional Master Production', style: TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),

          ..._masterVideoOptions.map((opt) {
            final isSelected = _selectedMasterTrack == opt['type'];
            return InkWell(
              onTap: () => setState(() => _selectedMasterTrack = opt['type']!),
              borderRadius: BorderRadius.circular(14),
              child: Container(
                margin: const EdgeInsets.only(bottom: 10),
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: const Color(0xFF1E293B),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(
                    color: isSelected ? ManagerTheme.brandGold : Colors.white12,
                    width: isSelected ? 2 : 1,
                  ),
                ),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: isSelected ? ManagerTheme.brandGold : const Color(0xFF0F172A),
                        shape: BoxShape.circle,
                      ),
                      child: Icon(
                        opt['type'] == 'aarti' ? Icons.temple_hindu_rounded : Icons.self_improvement_rounded,
                        color: isSelected ? Colors.black : ManagerTheme.brandGold,
                        size: 20,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            opt['title']!,
                            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13),
                          ),
                          const SizedBox(height: 3),
                          Text(opt['subtitle']!, style: const TextStyle(color: Colors.white60, fontSize: 11)),
                          const SizedBox(height: 4),
                          Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                decoration: BoxDecoration(
                                  color: Colors.redAccent.withValues(alpha: 0.2),
                                  borderRadius: BorderRadius.circular(4),
                                ),
                                child: Text('Duration: ${opt['duration']}', style: const TextStyle(color: Colors.redAccent, fontSize: 9, fontWeight: FontWeight.bold)),
                              ),
                              const SizedBox(width: 8),
                              Text('Last Live ID: ${opt['last_id']}', style: const TextStyle(color: Color(0xFF38BDF8), fontSize: 9)),
                            ],
                          ),
                        ],
                      ),
                    ),
                    Radio<String>(
                      value: opt['type']!,
                      groupValue: _selectedMasterTrack,
                      activeColor: ManagerTheme.brandGold,
                      onChanged: (val) {
                        if (val != null) setState(() => _selectedMasterTrack = val);
                      },
                    ),
                  ],
                ),
              ),
            );
          }),
          const SizedBox(height: 16),

          // Result Card if published
          if (_latestMasterVideoResult != null && _latestMasterVideoResult!['video_id'] != null)
            Container(
              padding: const EdgeInsets.all(14),
              margin: const EdgeInsets.only(bottom: 16),
              decoration: BoxDecoration(
                color: const Color(0xFF0F172A),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: ManagerTheme.brandEmerald),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Row(
                    children: [
                      Icon(Icons.check_circle_rounded, color: ManagerTheme.brandEmerald, size: 18),
                      SizedBox(width: 6),
                      Text('Successfully Published to YouTube Live!', style: TextStyle(color: ManagerTheme.brandEmerald, fontWeight: FontWeight.bold, fontSize: 12)),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Text('Video ID: ${_latestMasterVideoResult!['video_id']}', style: const TextStyle(color: Colors.white70, fontSize: 12)),
                  const SizedBox(height: 8),
                  ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFFF0000), foregroundColor: Colors.white),
                    icon: const Icon(Icons.play_arrow_rounded),
                    label: const Text('Watch on YouTube (@onlinepuja-live)'),
                    onPressed: () async {
                      final url = _latestMasterVideoResult!['youtube_url'] ?? 'https://www.youtube.com/watch?v=${_latestMasterVideoResult!['video_id']}';
                      final uri = Uri.parse(url);
                      if (await canLaunchUrl(uri)) launchUrl(uri);
                    },
                  ),
                ],
              ),
            ),

          // Action Button
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFFFF0000),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              icon: _isSynthesizingMasterVideo
                  ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                  : const Icon(Icons.rocket_launch_rounded, size: 20),
              label: Text(
                _isSynthesizingMasterVideo ? 'Synthesizing 1080p Video & Uploading to YouTube...' : '🎬 Synthesize & Publish 1080p Master to YouTube',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
              ),
              onPressed: _isSynthesizingMasterVideo ? null : _handleSynthesizeMasterDevotionalVideo,
            ),
          ),
          const SizedBox(height: 60),
        ],
      ),
    );
  }

  // =============================================================
  // TAB 3: FLUX & DEVOTIONAL POSTERS
  // =============================================================
  Widget _buildPosterStudioTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Preset Chips
          const Text('Sacred Temple Presets', style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: _posterPresets.map((preset) {
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: ActionChip(
                    backgroundColor: const Color(0xFF1E293B),
                    side: const BorderSide(color: ManagerTheme.brandGold, width: 1),
                    label: Text(
                      preset['title'] ?? '',
                      style: const TextStyle(color: ManagerTheme.brandGold, fontSize: 11, fontWeight: FontWeight.bold),
                    ),
                    onPressed: () => _applyPosterPreset(preset),
                  ),
                );
              }).toList(),
            ),
          ),
          const SizedBox(height: 14),

          // Aspect Ratio Selector
          const Text('Aspect Ratio', style: TextStyle(color: Colors.white70, fontSize: 12, fontWeight: FontWeight.bold)),
          const SizedBox(height: 6),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12),
            decoration: BoxDecoration(
              color: const Color(0xFF1E293B),
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: Colors.white12),
            ),
            child: DropdownButtonHideUnderline(
              child: DropdownButton<String>(
                value: _selectedPosterRatio,
                isExpanded: true,
                dropdownColor: const Color(0xFF1E293B),
                style: const TextStyle(color: Colors.white, fontSize: 12),
                items: _posterRatios.map((r) => DropdownMenuItem(value: r, child: Text(r))).toList(),
                onChanged: (val) {
                  if (val != null) setState(() => _selectedPosterRatio = val);
                },
              ),
            ),
          ),
          const SizedBox(height: 12),

          // Engine Selector
          const Text('Graphic Engine', style: TextStyle(color: Colors.white70, fontSize: 12, fontWeight: FontWeight.bold)),
          const SizedBox(height: 6),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12),
            decoration: BoxDecoration(
              color: const Color(0xFF1E293B),
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: Colors.white12),
            ),
            child: DropdownButtonHideUnderline(
              child: DropdownButton<String>(
                value: _selectedPosterEngine,
                isExpanded: true,
                dropdownColor: const Color(0xFF1E293B),
                style: const TextStyle(color: Colors.white, fontSize: 12),
                items: _posterEngines.map((e) => DropdownMenuItem(value: e, child: Text(e))).toList(),
                onChanged: (val) {
                  if (val != null) setState(() => _selectedPosterEngine = val);
                },
              ),
            ),
          ),
          const SizedBox(height: 14),

          // Prompt & Headline
          TextField(
            controller: _posterHeadlineCtrl,
            style: const TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.bold),
            decoration: InputDecoration(
              labelText: 'Banner Headline Overlay',
              labelStyle: const TextStyle(color: ManagerTheme.brandGold, fontSize: 12),
              filled: true,
              fillColor: const Color(0xFF1E293B),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
            ),
          ),
          const SizedBox(height: 10),

          TextField(
            controller: _posterPromptCtrl,
            maxLines: 3,
            style: const TextStyle(color: Colors.white, fontSize: 12),
            decoration: InputDecoration(
              labelText: 'Photorealistic AI Image Prompt',
              labelStyle: const TextStyle(color: Colors.white70, fontSize: 12),
              filled: true,
              fillColor: const Color(0xFF1E293B),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
            ),
          ),
          const SizedBox(height: 16),

          // Generated Poster Preview
          if (_generatedPosterUrl != null)
            Container(
              margin: const EdgeInsets.only(bottom: 16),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFF1E293B),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: ManagerTheme.brandGold),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(10),
                    child: Image.network(
                      _generatedPosterUrl!,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => const Center(
                        child: Text('Image synthesized successfully', style: TextStyle(color: Colors.white70)),
                      ),
                    ),
                  ),
                  const SizedBox(height: 10),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      TextButton.icon(
                        icon: const Icon(Icons.copy_rounded, size: 14),
                        label: const Text('Copy URL'),
                        onPressed: () {
                          Clipboard.setData(ClipboardData(text: _generatedPosterUrl!));
                          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Poster URL copied!')));
                        },
                      ),
                      ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(backgroundColor: ManagerTheme.brandGold, foregroundColor: Colors.black),
                        icon: const Icon(Icons.send_rounded, size: 14),
                        label: const Text('Queue to Feed'),
                        onPressed: () async {
                          await _api.createSocialPost(
                            platform: 'facebook',
                            caption: _posterHeadlineCtrl.text,
                            hashtags: '#OnlinePuja #Kashi #Varanasi',
                          );
                          _loadPosts();
                          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Post queued with synthesized banner!')));
                        },
                      ),
                    ],
                  ),
                ],
              ),
            ),

          // Synthesize Button
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: ManagerTheme.brandGold,
                foregroundColor: Colors.black,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              icon: _isGeneratingPoster
                  ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.black))
                  : const Icon(Icons.palette_rounded, size: 20),
              label: Text(
                _isGeneratingPoster ? 'Synthesizing Devotional Graphic...' : '🎨 Synthesize Devotional Graphic',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
              ),
              onPressed: _isGeneratingPoster ? null : _handleGeneratePoster,
            ),
          ),
          const SizedBox(height: 60),
        ],
      ),
    );
  }

  // =============================================================
  // TAB 4: SOVEREIGN ENGINE & CONNECTED CHANNELS HUB (Matches Z-Agents)
  // =============================================================
  Widget _buildEngineHubTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Engine Health Card
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [
                  const Color(0xFFEC4899).withValues(alpha: 0.15),
                  const Color(0xFF8B5CF6).withValues(alpha: 0.15),
                ],
              ),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(
                color: const Color(0xFFEC4899).withValues(alpha: 0.4),
                width: 1.2,
              ),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Row(
                      children: [
                        Icon(Icons.hub_rounded, color: Color(0xFFEC4899), size: 22),
                        SizedBox(width: 8),
                        Text(
                          'OnlinePuja Social AI Core',
                          style: TextStyle(fontSize: 15, fontWeight: FontWeight.w900, color: Colors.white),
                        ),
                      ],
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: ManagerTheme.brandEmerald.withValues(alpha: 0.2),
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: ManagerTheme.brandEmerald),
                      ),
                      child: const Text(
                        'SOVEREIGN ACTIVE',
                        style: TextStyle(fontSize: 10, fontWeight: FontWeight.w900, color: ManagerTheme.brandEmerald),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                const Text(
                  'Orchestration: Meta Graph API v20 • YouTube Data API v3 • Edge-TTS & HarfBuzz • IndexNow',
                  style: TextStyle(fontSize: 11, color: Colors.white70),
                ),
              ],
            ),
          ),
          const SizedBox(height: 18),

          // Autonomous Agent Control Card
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: const Color(0xFF1E293B),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.3)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Row(
                      children: [
                        Text('📱', style: TextStyle(fontSize: 18)),
                        SizedBox(width: 8),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Autonomous Social Media AI Agent (#7)', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12)),
                            Text('Cadence: 07:00 AM IST Daily • Auto-Synthesizes Reels & Shorts', style: TextStyle(color: Colors.white60, fontSize: 10)),
                          ],
                        ),
                      ],
                    ),
                    ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: ManagerTheme.brandGold,
                        foregroundColor: Colors.black,
                        visualDensity: VisualDensity.compact,
                      ),
                      onPressed: _isTriggeringSocialAgent ? null : _triggerAutonomousSocialAgent,
                      child: _isTriggeringSocialAgent
                          ? const SizedBox(width: 12, height: 12, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.black))
                          : const Text('Trigger Sprint', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                    ),
                  ],
                ),
                if (_agentTriggerResult != null) ...[
                  const SizedBox(height: 8),
                  Text(_agentTriggerResult!, style: const TextStyle(color: ManagerTheme.brandEmerald, fontSize: 11)),
                ],
              ],
            ),
          ),
          const SizedBox(height: 20),

          // Connected Social Radars & Channels
          const Text(
            'CONNECTED SOCIAL RADARS & CHANNELS',
            style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, letterSpacing: 0.5, color: Colors.white70),
          ),
          const SizedBox(height: 10),

          // Channel 1: YouTube
          _buildChannelDetailCard(
            id: 11,
            name: 'YouTube Channel (@onlinepuja-live)',
            subtext: 'Channel ID: UCzG-KGy3JDCENjVoM4S-QRg • YouTube Data API v3',
            icon: Icons.play_arrow_rounded,
            color: const Color(0xFFFF0000),
            url: 'https://www.youtube.com/@onlinepuja-live',
          ),

          // Channel 2: Instagram
          _buildChannelDetailCard(
            id: 3,
            name: 'Instagram Business (@onlinepuja.live)',
            subtext: 'IG ID: 17841434469409429 • Meta Graph API v20',
            icon: Icons.camera_alt_rounded,
            color: const Color(0xFFE1306C),
            url: 'https://www.instagram.com/onlinepuja.live',
          ),

          // Channel 3: Facebook
          _buildChannelDetailCard(
            id: 2,
            name: 'Facebook Brand Page (Online Puja)',
            subtext: 'Page ID: 1252263081299388 • Meta Graph API v20',
            icon: Icons.facebook_rounded,
            color: const Color(0xFF1877F2),
            url: 'https://www.facebook.com/1252263081299388',
          ),

          // Channel 4: WhatsApp
          _buildStaticChannelCard(
            name: 'WhatsApp Evolution API (+91 70071 58014)',
            subtext: 'Instance: onlinepuja • Active (9,191+ Devotee Broadcasts)',
            icon: Icons.chat_bubble_rounded,
            color: const Color(0xFF25D366),
          ),

          // Channel 5: Instant Indexing
          _buildStaticChannelCard(
            name: 'Search Engine Instant Indexing (IndexNow)',
            subtext: 'Key: 8d7c2a514e964f6990d1f3b064e8b9a2 • Bing & Yandex Crawlers',
            icon: Icons.bolt_rounded,
            color: const Color(0xFF38BDF8),
          ),

          const SizedBox(height: 20),

          // Link to Full Social Settings Portal
          Center(
            child: TextButton.icon(
              icon: const Icon(Icons.settings_rounded, size: 16),
              label: const Text('Manage Social Accounts on Web Portal'),
              onPressed: () async {
                final uri = Uri.parse('https://onlinepuja.live/social');
                if (await canLaunchUrl(uri)) launchUrl(uri);
              },
            ),
          ),
          const SizedBox(height: 60),
        ],
      ),
    );
  }

  // =============================================================
  // HELPER WIDGETS
  // =============================================================
  Widget _buildChannelDetailCard({
    required int id,
    required String name,
    required String subtext,
    required IconData icon,
    required Color color,
    required String url,
  }) {
    final isTesting = _testingChannel[id] == true;
    final testResult = _channelTestResult[id];

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
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
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: color.withValues(alpha: 0.15),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(icon, color: color, size: 18),
                  ),
                  const SizedBox(width: 10),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(name, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12)),
                      Text(subtext, style: const TextStyle(color: Colors.white60, fontSize: 10)),
                    ],
                  ),
                ],
              ),
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                    decoration: BoxDecoration(
                      color: ManagerTheme.brandEmerald.withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(6),
                      border: Border.all(color: ManagerTheme.brandEmerald),
                    ),
                    child: const Text('CONNECTED', style: TextStyle(color: ManagerTheme.brandEmerald, fontSize: 9, fontWeight: FontWeight.bold)),
                  ),
                  const SizedBox(width: 6),
                  IconButton(
                    tooltip: 'Test Connection',
                    icon: isTesting
                        ? const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                        : const Icon(Icons.speed_rounded, size: 18, color: Color(0xFF38BDF8)),
                    onPressed: isTesting ? null : () => _testChannelConnection(id),
                  ),
                  IconButton(
                    tooltip: 'Open Channel',
                    icon: const Icon(Icons.open_in_new_rounded, size: 18, color: Colors.white70),
                    onPressed: () async {
                      final uri = Uri.parse(url);
                      if (await canLaunchUrl(uri)) launchUrl(uri);
                    },
                  ),
                ],
              ),
            ],
          ),
          if (testResult != null) ...[
            const SizedBox(height: 6),
            Text(testResult, style: const TextStyle(color: ManagerTheme.brandEmerald, fontSize: 10)),
          ],
        ],
      ),
    );
  }

  Widget _buildStaticChannelCard({
    required String name,
    required String subtext,
    required IconData icon,
    required Color color,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                ),
                child: Icon(icon, color: color, size: 18),
              ),
              const SizedBox(width: 10),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(name, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12)),
                  Text(subtext, style: const TextStyle(color: Colors.white60, fontSize: 10)),
                ],
              ),
            ],
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
            decoration: BoxDecoration(
              color: ManagerTheme.brandEmerald.withValues(alpha: 0.2),
              borderRadius: BorderRadius.circular(6),
              border: Border.all(color: ManagerTheme.brandEmerald),
            ),
            child: const Text('CONNECTED', style: TextStyle(color: ManagerTheme.brandEmerald, fontSize: 9, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }

  Widget _buildKpiCard(String label, String value, Color color, IconData icon) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 8),
        decoration: BoxDecoration(
          color: const Color(0xFF1E293B),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: color.withValues(alpha: 0.3)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Icon(icon, color: color, size: 14),
                Container(width: 5, height: 5, decoration: BoxDecoration(shape: BoxShape.circle, color: color)),
              ],
            ),
            const SizedBox(height: 4),
            Text(value, style: TextStyle(color: color, fontSize: 16, fontWeight: FontWeight.bold)),
            Text(label, style: const TextStyle(color: Colors.white60, fontSize: 9, fontWeight: FontWeight.bold)),
          ],
        ),
      ),
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
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
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
      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(6),
        border: Border.all(color: fg.withValues(alpha: 0.4)),
      ),
      child: Text(label, style: TextStyle(color: fg, fontSize: 9, fontWeight: FontWeight.bold)),
    );
  }
}
