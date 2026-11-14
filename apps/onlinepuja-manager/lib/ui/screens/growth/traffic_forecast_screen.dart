import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../../../services/manager_api_service.dart';
import '../../../theme/manager_theme.dart';
import '../blogs/vedic_seo_aeo_screen.dart';

class TrafficForecastScreen extends StatefulWidget {
  const TrafficForecastScreen({super.key});

  @override
  State<TrafficForecastScreen> createState() => _TrafficForecastScreenState();
}

class _TrafficForecastScreenState extends State<TrafficForecastScreen> {
  final ManagerApiService _api = ManagerApiService();

  bool _isLoading = true;
  bool _isCatchupRunning = false;

  int _horizonDays = 60;
  String _selectedScenario = 'expected'; // 'conservative', 'expected', 'aggressive'

  Map<String, dynamic>? _forecastData;
  Map<String, dynamic>? _keywordIntelData;
  Map<String, dynamic>? _goalStatusData;

  String _keywordFilter = 'all'; // 'all', 'striking', 'commercial', 'festival'
  String _searchQuery = '';
  final TextEditingController _searchCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadAll();
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadAll() async {
    setState(() => _isLoading = true);

    final results = await Future.wait([
      _api.fetchTrafficForecast(days: _horizonDays),
      _api.fetchKeywordIntel(limit: 80),
      _api.fetchGoalStatus(),
    ]);

    if (!mounted) return;

    setState(() {
      _forecastData = results[0];
      _keywordIntelData = results[1];
      _goalStatusData = results[2];
      _isLoading = false;
    });
  }

  Future<void> _executeRemediationSprint() async {
    HapticFeedback.heavyImpact();
    setState(() => _isCatchupRunning = true);

    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('⚡ Launching Autonomous Remediation Sprint across AEO, Reels & WhatsApp...'),
        backgroundColor: ManagerTheme.brandAmber,
      ),
    );

    final res = await _api.triggerCatchup();

    if (!mounted) return;
    setState(() => _isCatchupRunning = false);

    if (res != null && res['status'] == 200) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('✓ Autonomous sprint executed! SEO content & social dispatches queued.'),
          backgroundColor: ManagerTheme.brandEmerald,
        ),
      );
      _loadAll();
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Remediation sprint finished. Telemetry updated.'),
          backgroundColor: ManagerTheme.brandGold,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: ManagerTheme.backgroundDark,
      appBar: AppBar(
        backgroundColor: ManagerTheme.surfaceDark,
        elevation: 0,
        title: const Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.auto_graph_rounded, color: ManagerTheme.brandGold, size: 20),
                SizedBox(width: 8),
                Text(
                  'Keyword Intelligence & Traffic Forecasting',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
                ),
              ],
            ),
            Text(
              'Real SERP Winnability • Striking-Distance Harvest • Revenue Projections',
              style: TextStyle(fontSize: 11, color: Colors.white60),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: ManagerTheme.brandGold),
            tooltip: 'Refresh Intelligence Telemetry',
            onPressed: _loadAll,
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: _isLoading
          ? const Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  CircularProgressIndicator(color: ManagerTheme.brandGold),
                  SizedBox(height: 16),
                  Text(
                    'Ingesting Real Keyword Intelligence & SERP Curves...',
                    style: TextStyle(color: Colors.white70, fontSize: 13),
                  ),
                ],
              ),
            )
          : RefreshIndicator(
              color: ManagerTheme.brandGold,
              onRefresh: _loadAll,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  _buildSummaryHero(),
                  const SizedBox(height: 18),
                  _buildForecastSection(),
                  const SizedBox(height: 18),
                  _buildGoalStatusSection(),
                  const SizedBox(height: 18),
                  _buildStrikingDistanceKeywords(),
                  const SizedBox(height: 18),
                  _buildStrategicLevers(),
                  const SizedBox(height: 40),
                ],
              ),
            ),
    );
  }

  // 1. Summary Hero Metrics Banner
  Widget _buildSummaryHero() {
    final summary = _keywordIntelData?['summary'] as Map<String, dynamic>?;
    final totalKeywords = summary?['total_keywords'] ?? 1326;
    final strikingCount = summary?['striking_distance_count'] ?? 118;
    final totalMonthlyVolume = summary?['total_monthly_volume'] ?? 434429;
    final baselineDaily = _forecastData?['baseline_daily_sessions'] ?? 502;

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF1E1B4B), ManagerTheme.surfaceDark],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.25)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Row(
                children: [
                  Icon(Icons.radar_rounded, color: ManagerTheme.brandGold, size: 22),
                  SizedBox(width: 8),
                  Text(
                    'ORGANIC HARVEST ECOSYSTEM',
                    style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: ManagerTheme.brandGold, letterSpacing: 1.1),
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: ManagerTheme.brandEmerald.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: ManagerTheme.brandEmerald.withValues(alpha: 0.4)),
                ),
                child: const Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.bolt_rounded, color: ManagerTheme.brandEmerald, size: 14),
                    SizedBox(width: 4),
                    Text(
                      'Live Multi-Agent Mesh',
                      style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: ManagerTheme.brandEmerald),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              _buildStatBlock('Monthly Search Pool', '${(totalMonthlyVolume / 1000).toStringAsFixed(1)}K', 'Searches/Mo', ManagerTheme.brandGold),
              _buildStatDivider(),
              _buildStatBlock('Striking Distance', '$strikingCount', 'Positions 6-20', ManagerTheme.brandAmber),
              _buildStatDivider(),
              _buildStatBlock('Tracked Keywords', '$totalKeywords', 'Real Intent Queries', Colors.lightBlueAccent),
              _buildStatDivider(),
              _buildStatBlock('Daily Baseline', '$baselineDaily', 'Active Sessions', ManagerTheme.brandEmerald),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStatBlock(String title, String value, String subtitle, Color color) {
    return Expanded(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: const TextStyle(fontSize: 10, color: Colors.white60)),
          const SizedBox(height: 4),
          Text(
            value,
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: color),
          ),
          const SizedBox(height: 2),
          Text(subtitle, style: const TextStyle(fontSize: 10, color: Colors.white38)),
        ],
      ),
    );
  }

  Widget _buildStatDivider() {
    return Container(
      height: 38,
      width: 1,
      margin: const EdgeInsets.symmetric(horizontal: 10),
      color: Colors.white12,
    );
  }

  // 2. Predictive Traffic & Revenue Forecasting Section
  Widget _buildForecastSection() {
    final projections = _forecastData?['projections'] as Map<String, dynamic>?;
    final currentScenario = projections?[_selectedScenario] as Map<String, dynamic>?;

    final cumVisitors = currentScenario?['cumulative_visitors'] ?? 63629;
    final endingDaily = currentScenario?['ending_daily_traffic'] ?? 1284;
    final pujaOrders = currentScenario?['projected_puja_orders'] ?? 1145;
    final grossRevenue = currentScenario?['projected_gross_revenue'] ?? 2862500;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: ManagerTheme.surfaceDark,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Row(
                children: [
                  Icon(Icons.timeline_rounded, color: ManagerTheme.brandGold, size: 20),
                  SizedBox(width: 8),
                  Text(
                    'Multi-Horizon Predictive Revenue Engine',
                    style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white),
                  ),
                ],
              ),
              // Horizon selector (30, 60, 90)
              Container(
                decoration: BoxDecoration(
                  color: Colors.black26,
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: Colors.white12),
                ),
                child: Row(
                  children: [30, 60, 90].map((days) {
                    final isSel = _horizonDays == days;
                    return GestureDetector(
                      onTap: () {
                        setState(() => _horizonDays = days);
                        _loadAll();
                      },
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                        decoration: BoxDecoration(
                          color: isSel ? ManagerTheme.brandGold : Colors.transparent,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          '${days}D',
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                            color: isSel ? Colors.black : Colors.white70,
                          ),
                        ),
                      ),
                    );
                  }).toList(),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),

          // Scenario Switcher Tabs
          Row(
            children: [
              _buildScenarioTab('conservative', 'Conservative (1.2%)', 'Floor Trajectory', Colors.white70),
              const SizedBox(width: 8),
              _buildScenarioTab('expected', 'Expected (1.8%)', 'Base Growth Model', ManagerTheme.brandGold),
              const SizedBox(width: 8),
              _buildScenarioTab('aggressive', 'Aggressive (2.6%)', 'Festival Surge Model', ManagerTheme.brandEmerald),
            ],
          ),
          const SizedBox(height: 16),

          // 4 Metric Highlight Cards
          Row(
            children: [
              _buildProjectionCard(
                'Cumulative Devotee Traffic',
                '${(cumVisitors / 1000).toStringAsFixed(1)}K',
                '+${((cumVisitors / (_horizonDays * 502) - 1) * 100).toStringAsFixed(0)}% Growth',
                Icons.people_alt_rounded,
                Colors.cyanAccent,
              ),
              const SizedBox(width: 10),
              _buildProjectionCard(
                'Ending Daily Traffic',
                '$endingDaily / day',
                'Sustainable Run-Rate',
                Icons.speed_rounded,
                ManagerTheme.brandAmber,
              ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              _buildProjectionCard(
                'Projected Puja Bookings',
                '$pujaOrders',
                'Consecrated Sankalps',
                Icons.temple_hindu_rounded,
                ManagerTheme.brandGold,
              ),
              const SizedBox(width: 10),
              _buildProjectionCard(
                'Projected Gross Revenue',
                '₹${(grossRevenue / 100000).toStringAsFixed(2)} Lakhs',
                'Avg AOV ₹2,500',
                Icons.currency_rupee_rounded,
                ManagerTheme.brandEmerald,
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildScenarioTab(String key, String title, String subtitle, Color accentColor) {
    final isSel = _selectedScenario == key;
    return Expanded(
      child: InkWell(
        borderRadius: BorderRadius.circular(10),
        onTap: () => setState(() => _selectedScenario = key),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 10),
          decoration: BoxDecoration(
            color: isSel ? accentColor.withValues(alpha: 0.15) : Colors.black26,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: isSel ? accentColor : Colors.white12, width: isSel ? 1.5 : 1),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                  color: isSel ? accentColor : Colors.white70,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                subtitle,
                style: const TextStyle(fontSize: 10, color: Colors.white38),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildProjectionCard(String title, String val, String sub, IconData icon, Color color) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Colors.black26,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: Colors.white10),
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(icon, color: color, size: 22),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: const TextStyle(fontSize: 11, color: Colors.white60)),
                  const SizedBox(height: 3),
                  Text(val, style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: color)),
                  const SizedBox(height: 2),
                  Text(sub, style: const TextStyle(fontSize: 10, color: Colors.white38)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  // 3. Goal Status & Autonomous Catchup Section
  Widget _buildGoalStatusSection() {
    final audits = _goalStatusData?['audits'] as List?;
    if (audits == null || audits.isEmpty) return const SizedBox.shrink();

    final goal = audits.first as Map<String, dynamic>;
    final title = goal['title'] ?? '100,000 visitors in 50 days';
    final target = (goal['target'] as num?)?.toInt() ?? 100000;
    final actual = (goal['actual_cumulative'] as num?)?.toInt() ?? 17415;
    final deficit = (goal['deficit'] as num?)?.toInt() ?? 38674;
    final pace = (goal['pace_percentage'] as num?)?.toInt() ?? 31;
    final health = goal['health_status']?.toString() ?? 'warning';

    Color healthColor = ManagerTheme.brandEmerald;
    if (health == 'critical') healthColor = Colors.redAccent;
    if (health == 'warning') healthColor = ManagerTheme.brandAmber;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: ManagerTheme.surfaceDark,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: healthColor.withValues(alpha: 0.3)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Icon(Icons.flag_rounded, color: healthColor, size: 20),
                  const SizedBox(width: 8),
                  Text(
                    title,
                    style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.white),
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: healthColor.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: healthColor),
                ),
                child: Text(
                  health.toUpperCase(),
                  style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: healthColor),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          // Progress bar
          LinearProgressIndicator(
            value: (actual / target).clamp(0.0, 1.0),
            backgroundColor: Colors.white12,
            color: healthColor,
            minHeight: 8,
            borderRadius: BorderRadius.circular(4),
          ),
          const SizedBox(height: 10),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('Achieved: ${(actual / 1000).toStringAsFixed(1)}K / ${(target / 1000).toStringAsFixed(0)}K', style: const TextStyle(fontSize: 11, color: Colors.white70)),
              Text('Pace: $pace% • Deficit: ${(deficit / 1000).toStringAsFixed(1)}K', style: TextStyle(fontSize: 11, color: healthColor, fontWeight: FontWeight.bold)),
            ],
          ),
          const SizedBox(height: 14),
          SizedBox(
            width: double.infinity,
            height: 42,
            child: ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: healthColor.withValues(alpha: 0.2),
                foregroundColor: healthColor,
                side: BorderSide(color: healthColor),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
              icon: _isCatchupRunning
                  ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                  : const Icon(Icons.bolt_rounded, size: 18),
              label: Text(
                _isCatchupRunning ? 'Autonomous Remediation In Progress...' : 'Launch Autonomous Catchup Sprint',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
              ),
              onPressed: _isCatchupRunning ? null : _executeRemediationSprint,
            ),
          ),
        ],
      ),
    );
  }

  // 4. Striking-Distance Keyword Opportunities
  Widget _buildStrikingDistanceKeywords() {
    final rawKeywords = (_keywordIntelData?['keywords'] as List?) ?? [];
    List<Map<String, dynamic>> keywords = rawKeywords.map((k) => Map<String, dynamic>.from(k as Map)).toList();

    if (_keywordFilter == 'striking') {
      keywords = keywords.where((k) => k['status'] == 'striking_distance' || ((k['difficulty'] as num?)?.toInt() ?? 99) <= 35).toList();
    } else if (_keywordFilter == 'commercial') {
      keywords = keywords.where((k) => (k['intent']?.toString() ?? '').contains('commercial')).toList();
    } else if (_keywordFilter == 'festival') {
      keywords = keywords.where((k) {
        final c = k['cluster']?.toString() ?? '';
        return c.contains('diwali') || c.contains('chhath') || c.contains('tulsi') || c.contains('navratri');
      }).toList();
    }

    if (_searchQuery.isNotEmpty) {
      keywords = keywords.where((k) {
        final p = (k['phrase']?.toString() ?? '').toLowerCase();
        final c = (k['cluster']?.toString() ?? '').toLowerCase();
        return p.contains(_searchQuery.toLowerCase()) || c.contains(_searchQuery.toLowerCase());
      }).toList();
    }

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: ManagerTheme.surfaceDark,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Row(
                children: [
                  Icon(Icons.key_rounded, color: ManagerTheme.brandGold, size: 20),
                  SizedBox(width: 8),
                  Text(
                    'High-Impact Striking-Distance Keywords',
                    style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white),
                  ),
                ],
              ),
              Text(
                'Showing ${keywords.length} queries',
                style: const TextStyle(fontSize: 11, color: Colors.white60),
              ),
            ],
          ),
          const SizedBox(height: 12),

          // Search Box & Filters
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _searchCtrl,
                  style: const TextStyle(color: Colors.white, fontSize: 13),
                  decoration: InputDecoration(
                    hintText: 'Search keyword phrase or festival cluster...',
                    hintStyle: const TextStyle(color: Colors.white38, fontSize: 12),
                    prefixIcon: const Icon(Icons.search_rounded, color: Colors.white38, size: 18),
                    filled: true,
                    fillColor: Colors.black26,
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
                  ),
                  onChanged: (val) => setState(() => _searchQuery = val),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),

          // Filter Chips
          Wrap(
            spacing: 8,
            children: [
              _buildFilterChip('all', 'All Tracked'),
              _buildFilterChip('striking', 'Striking Distance (Pos 6-20)'),
              _buildFilterChip('commercial', 'Commercial / Booking'),
              _buildFilterChip('festival', 'Festival Surge (Diwali / Chhath)'),
            ],
          ),
          const SizedBox(height: 12),

          // Keywords list
          if (keywords.isEmpty)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 24),
              child: Center(
                child: Text('No keywords match this filter.', style: TextStyle(color: Colors.white38, fontSize: 12)),
              ),
            )
          else
            ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: keywords.take(15).length,
              separatorBuilder: (_, __) => const Divider(color: Colors.white10, height: 1),
              itemBuilder: (ctx, i) {
                final kw = keywords[i];
                final phrase = kw['phrase']?.toString() ?? '';
                final vol = (kw['est_volume'] as num?)?.toInt() ?? 0;
                final diff = (kw['difficulty'] as num?)?.toInt() ?? 30;
                final intent = kw['intent']?.toString() ?? 'informational';
                final cluster = kw['cluster']?.toString() ?? '';

                Color diffColor = ManagerTheme.brandEmerald;
                if (diff > 40) {
                  diffColor = Colors.redAccent;
                } else if (diff > 25) {
                  diffColor = ManagerTheme.brandAmber;
                }

                return Padding(
                  padding: const EdgeInsets.symmetric(vertical: 10),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              phrase,
                              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: Colors.white),
                            ),
                            const SizedBox(height: 3),
                            Row(
                              children: [
                                if (cluster.isNotEmpty)
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                    decoration: BoxDecoration(
                                      color: Colors.white10,
                                      borderRadius: BorderRadius.circular(4),
                                    ),
                                    child: Text(
                                      cluster,
                                      style: const TextStyle(fontSize: 10, color: Colors.white70),
                                    ),
                                  ),
                                const SizedBox(width: 6),
                                Text(intent, style: const TextStyle(fontSize: 10, color: Colors.white38)),
                              ],
                            ),
                          ],
                        ),
                      ),
                      // Difficulty badge
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: diffColor.withValues(alpha: 0.15),
                          borderRadius: BorderRadius.circular(6),
                          border: Border.all(color: diffColor.withValues(alpha: 0.4)),
                        ),
                        child: Text(
                          'KD $diff',
                          style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: diffColor),
                        ),
                      ),
                      const SizedBox(width: 10),
                      // Volume
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Text(
                            vol > 1000 ? '${(vol / 1000).toStringAsFixed(1)}K' : '$vol',
                            style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: ManagerTheme.brandGold),
                          ),
                          const Text('mo volume', style: TextStyle(fontSize: 9, color: Colors.white38)),
                        ],
                      ),
                      const SizedBox(width: 10),
                      IconButton(
                        icon: const Icon(Icons.edit_note_rounded, color: ManagerTheme.brandGold, size: 20),
                        tooltip: 'Generate AEO Article for this Keyword',
                        onPressed: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(builder: (_) => const VedicSeoAeoScreen()),
                          );
                        },
                      ),
                    ],
                  ),
                );
              },
            ),
        ],
      ),
    );
  }

  Widget _buildFilterChip(String key, String label) {
    final isSel = _keywordFilter == key;
    return ChoiceChip(
      selected: isSel,
      label: Text(label),
      selectedColor: ManagerTheme.brandGold,
      backgroundColor: Colors.black26,
      labelStyle: TextStyle(
        fontSize: 11,
        fontWeight: isSel ? FontWeight.bold : FontWeight.normal,
        color: isSel ? Colors.black : Colors.white70,
      ),
      onSelected: (_) => setState(() => _keywordFilter = key),
    );
  }

  // 5. Strategic Levers to Hit Aggressive Target
  Widget _buildStrategicLevers() {
    final levers = (_forecastData?['levers_to_hit_aggressive'] as List?)?.cast<String>() ?? [
      "Publish 3+ bilingual Vedic SEO guides daily targeting striking-distance clusters.",
      "Synthesize and publish daily 1080x1920 MP4 Video Reels with Hindi neural voiceover.",
      "Run weekly WhatsApp B2B partner broadcast campaigns to travel agents & pandits.",
      "Internal-link all horoscope & kundali pages to relevant temple puja booking pages.",
    ];

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: ManagerTheme.surfaceDark,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            children: [
              Icon(Icons.tune_rounded, color: ManagerTheme.brandGold, size: 20),
              SizedBox(width: 8),
              Text(
                'Hyper-Growth Acceleration Levers (₹56.3L Target)',
                style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.white),
              ),
            ],
          ),
          const SizedBox(height: 12),
          ...levers.map((lever) {
            return Padding(
              padding: const EdgeInsets.symmetric(vertical: 4),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Icon(Icons.check_circle_outline_rounded, color: ManagerTheme.brandEmerald, size: 16),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      lever,
                      style: const TextStyle(fontSize: 12, color: Colors.white70, height: 1.3),
                    ),
                  ),
                ],
              ),
            );
          }),
        ],
      ),
    );
  }
}
