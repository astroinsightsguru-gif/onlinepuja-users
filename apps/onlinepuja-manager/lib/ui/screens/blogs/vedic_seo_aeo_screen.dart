import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../services/manager_api_service.dart';
import '../../../theme/manager_theme.dart';

class VedicSeoAeoScreen extends StatefulWidget {
  final bool embedded;

  const VedicSeoAeoScreen({super.key, this.embedded = false});

  @override
  State<VedicSeoAeoScreen> createState() => _VedicSeoAeoScreenState();
}

class _VedicSeoAeoScreenState extends State<VedicSeoAeoScreen> with SingleTickerProviderStateMixin {
  final ManagerApiService _apiService = ManagerApiService();
  late TabController _tabController;

  bool _isLoading = true;
  int _totalPublished = 56;
  int _totalDrafts = 20;
  int _avgAeoScore = 87;
  String _aiEngine = 'OmniRoute / Gemini 2.0 Flash';
  List<Map<String, dynamic>> _silos = [];
  List<Map<String, dynamic>> _articles = [];
  String _searchQuery = '';
  final TextEditingController _searchCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadOverview();
  }

  @override
  void dispose() {
    _tabController.dispose();
    _searchCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadOverview() async {
    setState(() => _isLoading = true);
    final data = await _apiService.fetchVedicSeoOverview();
    if (!mounted) return;

    if (data != null && data['ok'] == true) {
      setState(() {
        _totalPublished = data['total_published'] ?? _totalPublished;
        _totalDrafts = data['total_drafts'] ?? _totalDrafts;
        _avgAeoScore = data['avg_aeo_score'] ?? _avgAeoScore;
        _aiEngine = data['ai_primary_engine'] ?? _aiEngine;
        _silos = data['silos'] is List ? List<Map<String, dynamic>>.from(data['silos']) : [];
        _articles = data['articles'] is List ? List<Map<String, dynamic>>.from(data['articles']) : [];
        _isLoading = false;
      });
    } else {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _fixArticle(int id) async {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('⚡ Synthesizing citable AEO block for Article #$id...')),
    );

    final res = await _apiService.fixAeoArticle(id);
    if (!mounted) return;

    if (res != null && res['ok'] == true) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: ManagerTheme.brandEmerald,
          content: Text('✨ ${res["message"]} New Quotability Score: ${res["new_score"]}/100'),
        ),
      );
      _loadOverview();
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: Colors.red,
          content: Text('Failed: ${res?["error"] ?? "AEO generation error"}'),
        ),
      );
    }
  }

  void _showDraftDialog() {
    final topicCtrl = TextEditingController();

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: const Color(0xFF0F172A),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Row(
          children: [
            Icon(Icons.auto_awesome, color: ManagerTheme.brandGold, size: 22),
            SizedBox(width: 8),
            Text('Draft New Vedic Article', style: TextStyle(color: Colors.white, fontSize: 16)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'The AI Content Pipeline will draft an article with citable AEO Direct Answers, topic silo pillar links, and FAQ JSON-LD schemas.',
              style: TextStyle(color: Colors.white70, fontSize: 12),
            ),
            const SizedBox(height: 14),
            TextField(
              controller: topicCtrl,
              style: const TextStyle(color: Colors.white),
              decoration: InputDecoration(
                labelText: 'Target Topic or Ritual',
                labelStyle: const TextStyle(color: ManagerTheme.brandGold),
                hintText: 'e.g. Pradosh Vrat Puja Vidhi Varanasi',
                hintStyle: const TextStyle(color: Colors.white38),
                filled: true,
                fillColor: const Color(0xFF1E293B),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Cancel', style: TextStyle(color: Colors.white60)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: ManagerTheme.brandGold,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            onPressed: () async {
              final topic = topicCtrl.text.trim();
              if (topic.isEmpty) return;

              Navigator.pop(ctx);
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text('AI Drafting article for: "$topic"...')),
              );

              final res = await _apiService.generateVedicDraft(topic);
              if (!mounted) return;

              if (res != null && res['ok'] == true) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    backgroundColor: ManagerTheme.brandEmerald,
                    content: Text('✓ ${res["message"]}'),
                  ),
                );
                _loadOverview();
              } else {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    backgroundColor: Colors.red,
                    content: Text('Drafting error: ${res?["error"] ?? "Failed"}'),
                  ),
                );
              }
            },
            child: const Text('Generate Draft', style: TextStyle(color: Colors.black, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }

  Color _getScoreColor(int score) {
    if (score >= 85) return ManagerTheme.brandEmerald;
    if (score >= 70) return ManagerTheme.brandGold;
    return Colors.redAccent;
  }

  List<Map<String, dynamic>> get _filteredArticles {
    if (_searchQuery.isEmpty) return _articles;
    return _articles.where((a) {
      final title = (a['title'] ?? '').toString().toLowerCase();
      final silo = (a['silo_name'] ?? '').toString().toLowerCase();
      return title.contains(_searchQuery.toLowerCase()) || silo.contains(_searchQuery.toLowerCase());
    }).toList();
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
                  Icon(Icons.menu_book_rounded, color: ManagerTheme.brandGold, size: 22),
                  SizedBox(width: 8),
                  Text('Vedic SEO & AEO Command Center'),
                ],
              ),
              actions: [
                IconButton(
                  icon: const Icon(Icons.add_circle_outline_rounded, color: ManagerTheme.brandGold),
                  tooltip: 'Draft Article',
                  onPressed: _showDraftDialog,
                ),
                IconButton(
                  icon: const Icon(Icons.refresh_rounded, color: Colors.white70),
                  onPressed: _loadOverview,
                ),
              ],
            ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: ManagerTheme.brandGold,
        foregroundColor: Colors.black,
        icon: const Icon(Icons.auto_awesome),
        label: const Text('Draft Article', style: TextStyle(fontWeight: FontWeight.bold)),
        onPressed: _showDraftDialog,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: ManagerTheme.brandGold))
          : RefreshIndicator(
              color: ManagerTheme.brandGold,
              onRefresh: _loadOverview,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  // Engine banner card
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFF1E293B), Color(0xFF0F172A)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.3)),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Row(
                              children: [
                                Text('🕉️ Answer Engine Optimization (AEO)', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white, fontSize: 14)),
                              ],
                            ),
                            const SizedBox(height: 4),
                            Text(
                              'Optimized for ChatGPT, Perplexity, Gemini Citations · Engine: $_aiEngine',
                              style: TextStyle(color: Colors.white.withValues(alpha: 0.6), fontSize: 11),
                            ),
                          ],
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                          decoration: BoxDecoration(
                            color: ManagerTheme.brandEmerald.withValues(alpha: 0.15),
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: ManagerTheme.brandEmerald),
                          ),
                          child: const Row(
                            children: [
                              CircleAvatar(radius: 3, backgroundColor: ManagerTheme.brandEmerald),
                              SizedBox(width: 4),
                              Text('Active', style: TextStyle(color: ManagerTheme.brandEmerald, fontSize: 11, fontWeight: FontWeight.bold)),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 16),

                  // Metrics Row
                  Row(
                    children: [
                      _buildMetricCard(
                        'Published',
                        '$_totalPublished',
                        Icons.article_rounded,
                        const Color(0xFF60A5FA),
                      ),
                      const SizedBox(width: 8),
                      _buildMetricCard(
                        'Avg AEO Score',
                        '$_avgAeoScore/100',
                        Icons.query_stats_rounded,
                        _getScoreColor(_avgAeoScore),
                      ),
                      const SizedBox(width: 8),
                      _buildMetricCard(
                        'Sacred Silos',
                        '${_silos.length}',
                        Icons.account_tree_rounded,
                        ManagerTheme.brandGold,
                      ),
                      const SizedBox(width: 8),
                      _buildMetricCard(
                        'AI Drafts',
                        '$_totalDrafts',
                        Icons.pending_actions_rounded,
                        const Color(0xFFA78BFA),
                      ),
                    ],
                  ),

                  const SizedBox(height: 20),

                  // Tab selector
                  Container(
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
                      labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                      tabs: [
                        Tab(text: 'AEO Quotability (${_articles.length})'),
                        Tab(text: '5 Sacred Silos (${_silos.length})'),
                      ],
                    ),
                  ),

                  const SizedBox(height: 16),

                  SizedBox(
                    height: 580,
                    child: TabBarView(
                      controller: _tabController,
                      children: [
                        // Tab 1: Articles list
                        _buildArticlesTab(),

                        // Tab 2: Silos list
                        _buildSilosTab(),
                      ],
                    ),
                  ),
                ],
              ),
            ),
    );
  }

  Widget _buildMetricCard(String label, String value, IconData icon, Color color) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 6),
        decoration: BoxDecoration(
          color: const Color(0xFF1E293B),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: color.withValues(alpha: 0.3)),
        ),
        child: Column(
          children: [
            Icon(icon, color: color, size: 20),
            const SizedBox(height: 4),
            Text(value, style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: color)),
            const SizedBox(height: 2),
            Text(label, style: const TextStyle(fontSize: 10, color: Colors.white60), textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }

  Widget _buildArticlesTab() {
    final list = _filteredArticles;
    return Column(
      children: [
        // Search bar
        TextField(
          controller: _searchCtrl,
          style: const TextStyle(color: Colors.white, fontSize: 13),
          decoration: InputDecoration(
            hintText: 'Search articles or topic silos...',
            hintStyle: const TextStyle(color: Colors.white38),
            prefixIcon: const Icon(Icons.search, color: Colors.white54, size: 18),
            filled: true,
            fillColor: const Color(0xFF1E293B),
            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
          ),
          onChanged: (val) => setState(() => _searchQuery = val.trim()),
        ),
        const SizedBox(height: 10),

        Expanded(
          child: list.isEmpty
              ? const Center(child: Text('No articles found matching query.', style: TextStyle(color: Colors.white54)))
              : ListView.separated(
                  itemCount: list.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 10),
                  itemBuilder: (context, index) {
                    final a = list[index];
                    final score = a['aeo_score'] as int? ?? 85;
                    final scoreColor = _getScoreColor(score);
                    final hasDirect = a['has_direct_answer'] == true;
                    final issues = a['issues'] is List ? List<String>.from(a['issues']) : <String>[];

                    return Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: const Color(0xFF1E293B),
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: const Color(0xFF334155)),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                decoration: BoxDecoration(
                                  color: scoreColor.withValues(alpha: 0.15),
                                  borderRadius: BorderRadius.circular(8),
                                  border: Border.all(color: scoreColor.withValues(alpha: 0.4)),
                                ),
                                child: Text(
                                  '$score/100',
                                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: scoreColor),
                                ),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      a['title'] ?? 'Vedic Article',
                                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Colors.white),
                                    ),
                                    const SizedBox(height: 4),
                                    Row(
                                      children: [
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                          decoration: BoxDecoration(
                                            color: const Color(0xFF334155),
                                            borderRadius: BorderRadius.circular(4),
                                          ),
                                          child: Text(
                                            a['silo_name'] ?? 'Vedic Silo',
                                            style: const TextStyle(fontSize: 10, color: Colors.white70),
                                          ),
                                        ),
                                        const SizedBox(width: 8),
                                        Text(
                                          '${a['word_count'] ?? 0} words',
                                          style: const TextStyle(fontSize: 10, color: Colors.white38),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),

                          if (issues.isNotEmpty) ...[
                            const SizedBox(height: 8),
                            ...issues.take(2).map(
                                  (iss) => Padding(
                                    padding: const EdgeInsets.only(bottom: 2),
                                    child: Row(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        const Text('⚠️ ', style: TextStyle(fontSize: 10)),
                                        Expanded(
                                          child: Text(
                                            iss,
                                            style: const TextStyle(fontSize: 11, color: Colors.orangeAccent),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                          ],

                          const SizedBox(height: 8),

                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Row(
                                children: [
                                  Icon(
                                    hasDirect ? Icons.check_circle_rounded : Icons.info_outline_rounded,
                                    size: 14,
                                    color: hasDirect ? ManagerTheme.brandEmerald : Colors.white38,
                                  ),
                                  const SizedBox(width: 4),
                                  Text(
                                    hasDirect ? 'Citable AEO Block Active' : 'Needs Direct Answer',
                                    style: TextStyle(
                                      fontSize: 10,
                                      color: hasDirect ? ManagerTheme.brandEmerald : Colors.white54,
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                ],
                              ),
                              Row(
                                children: [
                                  if (!hasDirect)
                                    ElevatedButton.icon(
                                      icon: const Icon(Icons.bolt_rounded, size: 14, color: Colors.black),
                                      label: const Text('Auto-Fix AEO', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.black)),
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: ManagerTheme.brandGold,
                                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                      ),
                                      onPressed: () => _fixArticle(a['id']),
                                    ),
                                  const SizedBox(width: 6),
                                  IconButton(
                                    icon: const Icon(Icons.open_in_new_rounded, size: 16, color: Colors.white60),
                                    tooltip: 'Open Live Article',
                                    onPressed: () async {
                                      final url = Uri.parse('https://onlinepuja.live/blog/${a['slug']}');
                                      if (await canLaunchUrl(url)) {
                                        await launchUrl(url, mode: LaunchMode.externalApplication);
                                      }
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
        ),
      ],
    );
  }

  Widget _buildSilosTab() {
    return ListView.separated(
      itemCount: _silos.length,
      separatorBuilder: (_, __) => const SizedBox(height: 12),
      itemBuilder: (context, index) {
        final silo = _silos[index];
        final health = silo['health']?.toString() ?? 'Healthy';
        final isHealthy = health == 'Healthy';
        final links = silo['pillar_links'] as int? ?? 0;
        final articles = silo['articles_count'] as int? ?? 0;

        return Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: const Color(0xFF1E293B),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: isHealthy ? ManagerTheme.brandEmerald.withValues(alpha: 0.3) : ManagerTheme.brandGold.withValues(alpha: 0.3),
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      const Text('🏛️ ', style: TextStyle(fontSize: 16)),
                      Text(
                        silo['name'] ?? 'Topic Silo',
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Colors.white),
                      ),
                    ],
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: isHealthy ? ManagerTheme.brandEmerald.withValues(alpha: 0.15) : ManagerTheme.brandGold.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      health.toUpperCase(),
                      style: TextStyle(
                        fontSize: 9,
                        fontWeight: FontWeight.bold,
                        color: isHealthy ? ManagerTheme.brandEmerald : ManagerTheme.brandGold,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 6),
              Text(
                'Pillar: ${silo['pillar_title'] ?? ""}',
                style: const TextStyle(fontSize: 11, color: Colors.white60),
              ),
              const SizedBox(height: 10),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: const Color(0xFF0F172A),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          '$articles Cluster Articles',
                          style: const TextStyle(fontSize: 11, color: Colors.white70),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: const Color(0xFF0F172A),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          '$links Pillar Inlinks',
                          style: TextStyle(
                            fontSize: 11,
                            color: links > 0 ? ManagerTheme.brandEmerald : Colors.orangeAccent,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ],
                  ),
                  InkWell(
                    onTap: () async {
                      final url = Uri.parse('https://onlinepuja.live${silo['pillar_url']}');
                      if (await canLaunchUrl(url)) {
                        await launchUrl(url, mode: LaunchMode.externalApplication);
                      }
                    },
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                      decoration: BoxDecoration(
                        color: ManagerTheme.brandGold.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: ManagerTheme.brandGold),
                      ),
                      child: Row(
                        children: [
                          Text(silo['pillar_url'] ?? '', style: const TextStyle(color: ManagerTheme.brandGold, fontSize: 11, fontWeight: FontWeight.bold)),
                          const SizedBox(width: 4),
                          const Icon(Icons.arrow_outward_rounded, color: ManagerTheme.brandGold, size: 13),
                        ],
                      ),
                    ),
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
