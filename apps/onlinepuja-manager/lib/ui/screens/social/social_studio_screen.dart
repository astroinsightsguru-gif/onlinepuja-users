import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../services/manager_api_service.dart';
import '../../../theme/manager_theme.dart';

class SocialStudioScreen extends StatefulWidget {
  final int initialTab;
  final bool embedded;

  const SocialStudioScreen({
    super.key,
    this.initialTab = 0,
    this.embedded = false,
  });

  @override
  State<SocialStudioScreen> createState() => _SocialStudioScreenState();
}

class _SocialStudioScreenState extends State<SocialStudioScreen>
    with SingleTickerProviderStateMixin {
  final ManagerApiService _api = ManagerApiService();
  late TabController _tabController;

  // -------------------------------------------------------------
  // REELS & VIDEO MAKER STATE (9:16)
  // -------------------------------------------------------------
  final TextEditingController _reelTopicCtrl = TextEditingController(
    text: 'Kashi Vishwanath Live Ganga Aarti & Rudrabhishek Sankalp',
  );
  final TextEditingController _reelScriptCtrl = TextEditingController(
    text: 'POV: You attend Varanasi Ganga Aarti during Pradosh Vrat. Chanting Mahamrityunjaya Mantra at holy Manikarnika and Dashashwamedh Ghats with personal family Gotra Sankalp. 🕉️🪔 #OnlinePuja #Kashi #Varanasi #ShivJi',
  );

  String _selectedHook = 'POV: You attend Varanasi Ganga Aarti during Pradosh Vrat...';
  String _selectedMantra = 'Shiva Tandav Stotram (Beats & Dhol)';
  String _selectedPlatform = 'youtube_shorts';
  bool _isGeneratingReel = false;
  bool _isPlayingReel = true;
  double _reelPlayhead = 0.55;

  final List<String> _viralHooks = [
    'POV: You attend Varanasi Ganga Aarti during Pradosh Vrat...',
    'Do this 1 Vedic Upay on Pradosh Vrat to remove Shani Sade Sati...',
    'Why 99% of devotees chant Mahamrityunjaya Mantra incorrectly...',
    'Unboxing Varanasi Kashi Vishwanath Bhasma Prasadam delivered home...',
    'Watch live Gotra Sankalp performed by Kashi Vedic Purohits...',
  ];

  final List<String> _devotionalMantras = [
    'Shiva Tandav Stotram (Beats & Dhol)',
    'Maha Mrityunjaya Mantra (108 Jaap Chants)',
    'Dashashwamedh Ghat Live Ganga Aarti Shankhnaad',
    'Vishnu Sahasranama Stotram (Sacred Flute)',
    'Hanuman Chalisa (Acoustic Temple Chimes)',
  ];

  // -------------------------------------------------------------
  // POSTER & GRAPHIC MAKER STATE
  // -------------------------------------------------------------
  final TextEditingController _posterPromptCtrl = TextEditingController(
    text: 'Golden glowing sacred Shivling at Dashashwamedh Ghat Varanasi at night, floating oil diyas on holy river Ganga, photorealistic 8k commercial photography, spiritual volumetric lighting',
  );
  final TextEditingController _posterHeadlineCtrl = TextEditingController(
    text: 'KASHI VISHWANATH RUDRABHISHEK SANKALP',
  );

  String _selectedRatio = '4:5';
  String _selectedEngine = 'FLUX.1 Schnell (High-Speed Free)';
  bool _isGeneratingPoster = false;
  String? _generatedPosterUrl;

  // -------------------------------------------------------------
  // SOCIAL QUEUE STATE
  // -------------------------------------------------------------
  bool _isLoadingQueue = true;
  List<Map<String, dynamic>> _posts = [];
  String _queuePlatformFilter = 'all';

  @override
  void initState() {
    super.initState();
    _tabController = TabController(
      length: 3,
      vsync: this,
      initialIndex: widget.initialTab.clamp(0, 2),
    );
    _loadQueue();
  }

  @override
  void dispose() {
    _tabController.dispose();
    _reelTopicCtrl.dispose();
    _reelScriptCtrl.dispose();
    _posterPromptCtrl.dispose();
    _posterHeadlineCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadQueue() async {
    setState(() => _isLoadingQueue = true);
    final list = await _api.fetchSocialPosts(platform: _queuePlatformFilter);
    if (mounted) {
      setState(() {
        _posts = list;
        _isLoadingQueue = false;
      });
    }
  }

  Future<void> _handleGenerateReel() async {
    setState(() => _isGeneratingReel = true);
    HapticFeedback.mediumImpact();

    final res = await _api.generateSocialReel(
      topic: _reelTopicCtrl.text.trim(),
      hook: _selectedHook,
      script: _reelScriptCtrl.text.trim(),
      platform: _selectedPlatform,
    );

    if (mounted) {
      setState(() => _isGeneratingReel = false);
      final ok = res != null && res['success'] == true;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(ok ? '🚀 ${res["message"]}' : 'Failed to generate reel.'),
          backgroundColor: ok ? ManagerTheme.brandEmerald : Colors.redAccent,
        ),
      );
      if (ok) {
        _loadQueue();
      }
    }
  }

  Future<void> _handleGeneratePoster() async {
    setState(() => _isGeneratingPoster = true);
    HapticFeedback.mediumImpact();

    final res = await _api.generateSocialPoster(
      prompt: _posterPromptCtrl.text.trim(),
      headline: _posterHeadlineCtrl.text.trim(),
      ratio: _selectedRatio,
    );

    if (mounted) {
      setState(() {
        _isGeneratingPoster = false;
        if (res != null && res['image_url'] != null) {
          _generatedPosterUrl = res['image_url'];
        }
      });
      final ok = res != null && res['success'] == true;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(ok ? '✨ Devotional Poster generated successfully!' : 'Failed to generate poster.'),
          backgroundColor: ok ? ManagerTheme.brandEmerald : Colors.redAccent,
        ),
      );
      if (ok) {
        _loadQueue();
      }
    }
  }

  Future<void> _publishPostNow(int id) async {
    HapticFeedback.mediumImpact();
    final ok = await _api.publishSocialNow(id);
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(ok ? '✓ Post dispatched to social networks!' : 'Failed to publish post.'),
          backgroundColor: ok ? ManagerTheme.brandEmerald : Colors.redAccent,
        ),
      );
      if (ok) {
        _loadQueue();
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: ManagerTheme.bgDark,
      appBar: widget.embedded
          ? null
          : AppBar(
              backgroundColor: const Color(0xFF0F172A),
              title: const Row(
                children: [
                  Icon(Icons.video_library_rounded, color: ManagerTheme.brandGold, size: 22),
                  SizedBox(width: 8),
                  Text('Social AI Devotional Video Studio'),
                ],
              ),
              actions: [
                IconButton(
                  icon: const Icon(Icons.refresh_rounded, color: Colors.white70),
                  onPressed: _loadQueue,
                ),
              ],
            ),
      body: Column(
        children: [
          // Sub-navigation Tabs
          Container(
            color: const Color(0xFF0F172A),
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: Container(
              decoration: BoxDecoration(
                color: const Color(0xFF1E293B),
                borderRadius: BorderRadius.circular(12),
              ),
              padding: const EdgeInsets.all(4),
              child: TabBar(
                controller: _tabController,
                indicator: BoxDecoration(
                  color: ManagerTheme.brandGold,
                  borderRadius: BorderRadius.circular(10),
                ),
                labelColor: Colors.black,
                unselectedLabelColor: Colors.white70,
                labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
                tabs: const [
                  Tab(text: '9:16 Reel Studio'),
                  Tab(text: 'Devotional Poster'),
                  Tab(text: 'Dispatched Feed'),
                ],
              ),
            ),
          ),

          // Tab content
          Expanded(
            child: TabBarView(
              controller: _tabController,
              children: [
                _buildReelStudioTab(),
                _buildPosterMakerTab(),
                _buildQueueTab(),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // =============================================================
  // 1. 9:16 REEL STUDIO
  // =============================================================
  Widget _buildReelStudioTab() {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Left: 9:16 Mobile Viewport Simulation
            Container(
              width: 170,
              height: 302,
              decoration: BoxDecoration(
                color: const Color(0xFF000000),
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.5), width: 2),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.6),
                    blurRadius: 16,
                    offset: const Offset(0, 6),
                  ),
                ],
              ),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(18),
                child: Stack(
                  children: [
                    // Background Devotional Frame
                    Positioned.fill(
                      child: Image.network(
                        'https://onlinepuja.live/frontend/images/kashi_vishwanath.jpg',
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => Container(
                          color: const Color(0xFF1E293B),
                          child: const Center(child: Text('🕉️', style: TextStyle(fontSize: 48))),
                        ),
                      ),
                    ),

                    // Top Bar
                    Positioned(
                      top: 10,
                      left: 10,
                      right: 10,
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                            decoration: BoxDecoration(
                              color: Colors.black.withValues(alpha: 0.6),
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: const Text('9:16 SHORTS', style: TextStyle(color: Colors.white, fontSize: 8, fontWeight: FontWeight.bold)),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                            decoration: BoxDecoration(
                              color: Colors.red.withValues(alpha: 0.8),
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: const Text('HD', style: TextStyle(color: Colors.white, fontSize: 8, fontWeight: FontWeight.bold)),
                          ),
                        ],
                      ),
                    ),

                    // Center Play/Pause button
                    Center(
                      child: InkWell(
                        onTap: () => setState(() => _isPlayingReel = !_isPlayingReel),
                        child: CircleAvatar(
                          backgroundColor: Colors.black.withValues(alpha: 0.6),
                          radius: 20,
                          child: Icon(
                            _isPlayingReel ? Icons.pause_rounded : Icons.play_arrow_rounded,
                            color: ManagerTheme.brandGold,
                            size: 24,
                          ),
                        ),
                      ),
                    ),

                    // Bottom Overlay with Hook & Audio
                    Positioned(
                      bottom: 0,
                      left: 0,
                      right: 0,
                      child: Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          gradient: LinearGradient(
                            colors: [Colors.transparent, Colors.black.withValues(alpha: 0.9)],
                            begin: Alignment.topCenter,
                            end: Alignment.bottomCenter,
                          ),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              _selectedHook,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(color: Colors.white, fontSize: 9.5, fontWeight: FontWeight.bold),
                            ),
                            const SizedBox(height: 4),
                            Row(
                              children: [
                                const Icon(Icons.music_note_rounded, size: 10, color: ManagerTheme.brandGold),
                                const SizedBox(width: 4),
                                Expanded(
                                  child: Text(
                                    _selectedMantra,
                                    overflow: TextOverflow.ellipsis,
                                    style: const TextStyle(color: ManagerTheme.brandGold, fontSize: 8),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 6),
                            LinearProgressIndicator(
                              value: _reelPlayhead,
                              backgroundColor: Colors.white24,
                              valueColor: const AlwaysStoppedAnimation(ManagerTheme.brandGold),
                              minHeight: 2,
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),

            const SizedBox(width: 16),

            // Right: Controls & Parameters
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Viral Devotional Hook Matrix', style: TextStyle(color: ManagerTheme.brandGold, fontSize: 12, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 6),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10),
                    decoration: BoxDecoration(
                      color: const Color(0xFF1E293B),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: DropdownButton<String>(
                      value: _selectedHook,
                      isExpanded: true,
                      underline: const SizedBox(),
                      dropdownColor: const Color(0xFF1E293B),
                      style: const TextStyle(color: Colors.white, fontSize: 11),
                      items: _viralHooks.map((h) {
                        return DropdownMenuItem(value: h, child: Text(h, overflow: TextOverflow.ellipsis));
                      }).toList(),
                      onChanged: (val) {
                        if (val != null) setState(() => _selectedHook = val);
                      },
                    ),
                  ),

                  const SizedBox(height: 12),

                  const Text('Devotional Soundtrack & Audio', style: TextStyle(color: ManagerTheme.brandGold, fontSize: 12, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 6),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10),
                    decoration: BoxDecoration(
                      color: const Color(0xFF1E293B),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: DropdownButton<String>(
                      value: _selectedMantra,
                      isExpanded: true,
                      underline: const SizedBox(),
                      dropdownColor: const Color(0xFF1E293B),
                      style: const TextStyle(color: Colors.white, fontSize: 11),
                      items: _devotionalMantras.map((m) {
                        return DropdownMenuItem(value: m, child: Text(m, overflow: TextOverflow.ellipsis));
                      }).toList(),
                      onChanged: (val) {
                        if (val != null) setState(() => _selectedMantra = val);
                      },
                    ),
                  ),

                  const SizedBox(height: 12),

                  const Text('Publish Destination', style: TextStyle(color: ManagerTheme.brandGold, fontSize: 12, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 6),
                  Row(
                    children: [
                      _buildPlatformChip('YouTube Shorts', 'youtube_shorts', const Color(0xFFFF0000)),
                      const SizedBox(width: 8),
                      _buildPlatformChip('Instagram Reels', 'instagram', const Color(0xFFE1306C)),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),

        const SizedBox(height: 16),

        TextField(
          controller: _reelScriptCtrl,
          maxLines: 3,
          style: const TextStyle(color: Colors.white, fontSize: 12),
          decoration: InputDecoration(
            labelText: 'Devotional Voiceover & Sankalp Caption',
            labelStyle: const TextStyle(color: ManagerTheme.brandGold, fontSize: 12),
            filled: true,
            fillColor: const Color(0xFF1E293B),
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
          ),
        ),

        const SizedBox(height: 16),

        SizedBox(
          width: double.infinity,
          height: 48,
          child: ElevatedButton.icon(
            icon: _isGeneratingReel
                ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.black, strokeWidth: 2))
                : const Icon(Icons.rocket_launch_rounded, color: Colors.black),
            label: Text(
              _isGeneratingReel ? 'Generating 9:16 Reel...' : 'Generate & Schedule Devotional Reel',
              style: const TextStyle(color: Colors.black, fontWeight: FontWeight.bold, fontSize: 14),
            ),
            style: ElevatedButton.styleFrom(
              backgroundColor: ManagerTheme.brandGold,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
            onPressed: _isGeneratingReel ? null : _handleGenerateReel,
          ),
        ),
      ],
    );
  }

  Widget _buildPlatformChip(String label, String value, Color color) {
    final isSelected = _selectedPlatform == value;
    return InkWell(
      onTap: () => setState(() => _selectedPlatform = value),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: isSelected ? color.withValues(alpha: 0.25) : const Color(0xFF1E293B),
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: isSelected ? color : Colors.white24),
        ),
        child: Text(
          label,
          style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: isSelected ? Colors.white : Colors.white60),
        ),
      ),
    );
  }

  // =============================================================
  // 2. DEVOTIONAL POSTER MAKER
  // =============================================================
  Widget _buildPosterMakerTab() {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        if (_generatedPosterUrl != null) ...[
          Container(
            height: 280,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.5)),
            ),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(16),
              child: Image.network(
                _generatedPosterUrl!,
                fit: BoxFit.cover,
                loadingBuilder: (_, child, p) => p == null ? child : const Center(child: CircularProgressIndicator(color: ManagerTheme.brandGold)),
              ),
            ),
          ),
          const SizedBox(height: 16),
        ],

        TextField(
          controller: _posterHeadlineCtrl,
          style: const TextStyle(color: Colors.white, fontSize: 13),
          decoration: InputDecoration(
            labelText: 'Poster Headline',
            labelStyle: const TextStyle(color: ManagerTheme.brandGold),
            filled: true,
            fillColor: const Color(0xFF1E293B),
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
          ),
        ),

        const SizedBox(height: 12),

        TextField(
          controller: _posterPromptCtrl,
          maxLines: 3,
          style: const TextStyle(color: Colors.white, fontSize: 12),
          decoration: InputDecoration(
            labelText: 'Photorealistic Devotional Prompt',
            labelStyle: const TextStyle(color: ManagerTheme.brandGold),
            filled: true,
            fillColor: const Color(0xFF1E293B),
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
          ),
        ),

        const SizedBox(height: 12),

        Row(
          children: [
            Expanded(
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 10),
                decoration: BoxDecoration(
                  color: const Color(0xFF1E293B),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: DropdownButton<String>(
                  value: _selectedRatio,
                  isExpanded: true,
                  underline: const SizedBox(),
                  dropdownColor: const Color(0xFF1E293B),
                  style: const TextStyle(color: Colors.white, fontSize: 11),
                  items: const [
                    DropdownMenuItem(value: '4:5', child: Text('4:5 Instagram Portrait')),
                    DropdownMenuItem(value: '9:16', child: Text('9:16 Story / Reel')),
                    DropdownMenuItem(value: '16:9', child: Text('16:9 Landscape')),
                  ],
                  onChanged: (val) {
                    if (val != null) setState(() => _selectedRatio = val);
                  },
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 10),
                decoration: BoxDecoration(
                  color: const Color(0xFF1E293B),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: DropdownButton<String>(
                  value: _selectedEngine,
                  isExpanded: true,
                  underline: const SizedBox(),
                  dropdownColor: const Color(0xFF1E293B),
                  style: const TextStyle(color: Colors.white, fontSize: 11),
                  items: const [
                    DropdownMenuItem(value: 'FLUX.1 Schnell (High-Speed Free)', child: Text('FLUX.1 Schnell (Free)')),
                    DropdownMenuItem(value: 'Google Imagen 3', child: Text('Google Imagen 3')),
                  ],
                  onChanged: (val) {
                    if (val != null) setState(() => _selectedEngine = val);
                  },
                ),
              ),
            ),
          ],
        ),

        const SizedBox(height: 16),

        SizedBox(
          width: double.infinity,
          height: 48,
          child: ElevatedButton.icon(
            icon: _isGeneratingPoster
                ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.black, strokeWidth: 2))
                : const Icon(Icons.auto_awesome, color: Colors.black),
            label: Text(
              _isGeneratingPoster ? 'Synthesizing Devotional Art...' : 'Generate 8K Devotional Poster',
              style: const TextStyle(color: Colors.black, fontWeight: FontWeight.bold, fontSize: 14),
            ),
            style: ElevatedButton.styleFrom(
              backgroundColor: ManagerTheme.brandGold,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
            onPressed: _isGeneratingPoster ? null : _handleGeneratePoster,
          ),
        ),
      ],
    );
  }

  // =============================================================
  // 3. OMNICHANNEL SOCIAL QUEUE
  // =============================================================
  Widget _buildQueueTab() {
    if (_isLoadingQueue) {
      return const Center(child: CircularProgressIndicator(color: ManagerTheme.brandGold));
    }

    if (_posts.isEmpty) {
      return const Center(
        child: Text('No scheduled posts found. Generate a reel or poster above!', style: TextStyle(color: Colors.white54)),
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.all(16),
      itemCount: _posts.length,
      separatorBuilder: (_, __) => const SizedBox(height: 12),
      itemBuilder: (context, index) {
        final post = _posts[index];
        final isPublished = post['status'] == 'published';

        return Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: const Color(0xFF1E293B),
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: isPublished ? ManagerTheme.brandEmerald.withValues(alpha: 0.3) : const Color(0xFF334155)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: const Color(0xFF0F172A),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      (post['platform'] ?? 'SOCIAL').toString().toUpperCase(),
                      style: const TextStyle(color: ManagerTheme.brandGold, fontSize: 10, fontWeight: FontWeight.bold),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: isPublished ? ManagerTheme.brandEmerald.withValues(alpha: 0.2) : Colors.orangeAccent.withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      isPublished ? 'PUBLISHED' : 'QUEUED',
                      style: TextStyle(
                        fontSize: 9,
                        fontWeight: FontWeight.bold,
                        color: isPublished ? ManagerTheme.brandEmerald : Colors.orangeAccent,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Text(
                post['caption'] ?? '',
                style: const TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 10),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      const Icon(Icons.visibility_rounded, size: 14, color: Colors.white54),
                      const SizedBox(width: 4),
                      Text('${post["views"] ?? 0}', style: const TextStyle(fontSize: 11, color: Colors.white70)),
                      const SizedBox(width: 12),
                      const Icon(Icons.favorite_rounded, size: 14, color: Colors.redAccent),
                      const SizedBox(width: 4),
                      Text('${post["likes"] ?? 0}', style: const TextStyle(fontSize: 11, color: Colors.white70)),
                    ],
                  ),
                  if (!isPublished)
                    ElevatedButton.icon(
                      icon: const Icon(Icons.send_rounded, size: 12, color: Colors.black),
                      label: const Text('Publish Now', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.black)),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: ManagerTheme.brandGold,
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                      onPressed: () => _publishPostNow(post['id']),
                    ),
                ],
              ),
            ],
          ),
        );
      },
    );
  }
}
