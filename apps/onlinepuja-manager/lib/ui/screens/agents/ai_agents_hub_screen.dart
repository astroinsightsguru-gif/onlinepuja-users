import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:onlinepuja_manager/services/manager_api_service.dart';
import 'package:onlinepuja_manager/theme/manager_theme.dart';

class AiAgentsHubScreen extends StatefulWidget {
  const AiAgentsHubScreen({super.key});

  @override
  State<AiAgentsHubScreen> createState() => _AiAgentsHubScreenState();
}

class _AiAgentsHubScreenState extends State<AiAgentsHubScreen> with SingleTickerProviderStateMixin {
  final ManagerApiService _api = ManagerApiService();

  late TabController _tabController;
  bool _isLoading = true;
  List<Map<String, dynamic>> _agents = [];
  List<Map<String, dynamic>> _outputs = [];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadData();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    final agents = await _api.fetchAgents();
    final outputs = await _api.fetchAgentOutputs();
    if (mounted) {
      setState(() {
        _agents = agents;
        _outputs = outputs;
        _isLoading = false;
      });
    }
  }

  Future<void> _toggleAgent(Map<String, dynamic> agent) async {
    final id = (agent['id'] as num?)?.toInt() ?? int.tryParse(agent['id']?.toString() ?? '') ?? 0;
    final currentStatus = agent['is_active'] == true;
    setState(() {
      agent['is_active'] = !currentStatus;
    });

    final success = await _api.toggleAgent(id);
    if (!success && mounted) {
      setState(() {
        agent['is_active'] = currentStatus;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Failed to update agent status.')),
      );
    } else if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('${agent['name']} is now ${!currentStatus ? 'Active' : 'Paused'}'),
          backgroundColor: ManagerTheme.brandEmerald,
        ),
      );
    }
  }

  Future<void> _runAgentNow(Map<String, dynamic> agent) async {
    final id = (agent['id'] as num?)?.toInt() ?? int.tryParse(agent['id']?.toString() ?? '') ?? 0;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('⚡ Triggering ${agent['name']}...'),
        duration: const Duration(seconds: 2),
      ),
    );

    final res = await _api.runAgentNow(id);
    if (mounted) {
      final msg = res['message'] ?? 'Execution completed.';
      showDialog(
        context: context,
        builder: (ctx) => AlertDialog(
          backgroundColor: ManagerTheme.surfaceDark,
          title: Row(
            children: [
              Text(agent['icon'] ?? '🤖', style: const TextStyle(fontSize: 24)),
              const SizedBox(width: 8),
              Expanded(child: Text(agent['name'] ?? 'Agent Output', style: const TextStyle(fontSize: 15))),
            ],
          ),
          content: SingleChildScrollView(
            child: Text(msg, style: const TextStyle(color: Colors.white70, fontSize: 13, height: 1.4)),
          ),
          actions: [
            ElevatedButton(
              style: ElevatedButton.styleFrom(backgroundColor: ManagerTheme.brandGold, foregroundColor: Colors.black),
              onPressed: () {
                Navigator.pop(ctx);
                _loadData();
              },
              child: const Text('OK'),
            ),
          ],
        ),
      );
    }
  }

  void _openAgentTuningSheet(Map<String, dynamic> agent) {
    final promptCtrl = TextEditingController(text: agent['system_prompt'] ?? '');
    String selectedTone = agent['tone'] ?? 'vedic_devotional';
    String selectedMode = agent['mode'] ?? 'supervised';
    String selectedLang = agent['language'] ?? 'hi';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: ManagerTheme.surfaceDark,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheetState) => Padding(
          padding: EdgeInsets.only(
            left: 20,
            right: 20,
            top: 20,
            bottom: MediaQuery.of(ctx).viewInsets.bottom + 24,
          ),
          child: SingleChildScrollView(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        Text(agent['icon'] ?? '🤖', style: const TextStyle(fontSize: 26)),
                        const SizedBox(width: 10),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(agent['name'] ?? 'Agent', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                            Text('Agent Key: ${agent['agent_key']}', style: const TextStyle(fontSize: 11, color: Colors.white54)),
                          ],
                        ),
                      ],
                    ),
                    IconButton(onPressed: () => Navigator.pop(ctx), icon: const Icon(Icons.close)),
                  ],
                ),
                const Divider(height: 24, color: Colors.white12),

                // Execution Mode
                const Text('Execution Mode', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white70)),
                const SizedBox(height: 6),
                Row(
                  children: [
                    Expanded(
                      child: ChoiceChip(
                        label: const Text('Supervised (Review First)'),
                        selected: selectedMode == 'supervised',
                        selectedColor: ManagerTheme.brandGold,
                        labelStyle: TextStyle(
                          color: selectedMode == 'supervised' ? Colors.black : Colors.white,
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                        ),
                        onSelected: (val) => setSheetState(() => selectedMode = 'supervised'),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: ChoiceChip(
                        label: const Text('Autonomous (Auto-Blast)'),
                        selected: selectedMode == 'autonomous',
                        selectedColor: ManagerTheme.brandEmerald,
                        labelStyle: TextStyle(
                          color: selectedMode == 'autonomous' ? Colors.black : Colors.white,
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                        ),
                        onSelected: (val) => setSheetState(() => selectedMode = 'autonomous'),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),

                // Tone & Language
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Persona Tone', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white70)),
                          const SizedBox(height: 4),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10),
                            decoration: BoxDecoration(
                              color: Colors.black26,
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(color: Colors.white12),
                            ),
                            child: DropdownButton<String>(
                              value: selectedTone,
                              isExpanded: true,
                              underline: const SizedBox(),
                              dropdownColor: ManagerTheme.surfaceDark,
                              items: const [
                                DropdownMenuItem(value: 'vedic_devotional', child: Text('Vedic Devotional (शुद्ध)', style: TextStyle(fontSize: 12))),
                                DropdownMenuItem(value: 'modern_inspirational', child: Text('Modern Inspirational', style: TextStyle(fontSize: 12))),
                                DropdownMenuItem(value: 'high_conversion', child: Text('High Conversion', style: TextStyle(fontSize: 12))),
                              ],
                              onChanged: (val) => setSheetState(() => selectedTone = val!),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Language', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white70)),
                          const SizedBox(height: 4),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10),
                            decoration: BoxDecoration(
                              color: Colors.black26,
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(color: Colors.white12),
                            ),
                            child: DropdownButton<String>(
                              value: selectedLang,
                              isExpanded: true,
                              underline: const SizedBox(),
                              dropdownColor: ManagerTheme.surfaceDark,
                              items: const [
                                DropdownMenuItem(value: 'hi', child: Text('हिन्दी (Hindi)', style: TextStyle(fontSize: 12))),
                                DropdownMenuItem(value: 'en', child: Text('English', style: TextStyle(fontSize: 12))),
                                DropdownMenuItem(value: 'sa', child: Text('संस्कृत (Sanskrit)', style: TextStyle(fontSize: 12))),
                              ],
                              onChanged: (val) => setSheetState(() => selectedLang = val!),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),

                // System Prompt Editor
                const Text('Custom System Prompt / Instruction', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white70)),
                const SizedBox(height: 6),
                TextField(
                  controller: promptCtrl,
                  maxLines: 4,
                  style: const TextStyle(fontSize: 12, color: Colors.white),
                  decoration: InputDecoration(
                    filled: true,
                    fillColor: Colors.black26,
                    hintText: 'Enter specific guidelines for this agent...',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Colors.white12)),
                  ),
                ),
                const SizedBox(height: 20),

                // Save Action
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: ManagerTheme.brandGold,
                      foregroundColor: Colors.black,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    onPressed: () async {
                      Navigator.pop(ctx);
                      final agentId = (agent['id'] as num?)?.toInt() ?? int.tryParse(agent['id']?.toString() ?? '') ?? 0;
                      final success = await _api.updateAgentConfig(agentId, {
                        'system_prompt': promptCtrl.text,
                        'tone': selectedTone,
                        'mode': selectedMode,
                        'language': selectedLang,
                      });
                      if (success) {
                        _loadData();
                        if (mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(content: Text('✓ ${agent['name']} tuned successfully!'), backgroundColor: ManagerTheme.brandEmerald),
                          );
                        }
                      }
                    },
                    child: const Text('Save Agent Configuration', style: TextStyle(fontWeight: FontWeight.bold)),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    ).whenComplete(() {
      promptCtrl.dispose();
    });
  }

  Future<void> _approveOutput(Map<String, dynamic> output) async {
    final id = (output['id'] as num?)?.toInt() ?? int.tryParse(output['id']?.toString() ?? '') ?? 0;
    final success = await _api.approveOutput(id);
    if (success) {
      setState(() {
        output['status'] = 'approved';
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('✓ "${output['title']}" approved and broadcast!'), backgroundColor: ManagerTheme.brandEmerald),
        );
      }
    }
  }

  void _showMediaPreview(String mediaUrl, String title) {
    showModalBottomSheet(
      context: context,
      backgroundColor: ManagerTheme.surfaceDark,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) => Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15), overflow: TextOverflow.ellipsis),
                ),
                IconButton(icon: const Icon(Icons.close_rounded), onPressed: () => Navigator.pop(ctx)),
              ],
            ),
            const SizedBox(height: 12),
            ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: Image.network(
                mediaUrl,
                height: 200,
                width: double.infinity,
                fit: BoxFit.cover,
                errorBuilder: (_, __, ___) => Container(
                  height: 120,
                  color: Colors.black26,
                  child: const Center(
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.video_collection_rounded, color: ManagerTheme.brandGold),
                        SizedBox(width: 8),
                        Text('Reel / Video Asset Link', style: TextStyle(color: Colors.white70)),
                      ],
                    ),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 14),
            Text(mediaUrl, style: const TextStyle(fontSize: 11, color: Colors.blueAccent)),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                style: ElevatedButton.styleFrom(backgroundColor: ManagerTheme.brandGold, foregroundColor: Colors.black),
                icon: const Icon(Icons.copy_rounded, size: 16),
                label: const Text('Copy Asset URL'),
                onPressed: () {
                  Clipboard.setData(ClipboardData(text: mediaUrl));
                  Navigator.pop(ctx);
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('✓ Media URL copied to clipboard!')),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final pendingCount = _outputs.where((o) => o['status'] == 'pending_review').length;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Online Puja AI — Swarm'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: _loadData,
          ),
        ],
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: ManagerTheme.brandGold,
          labelColor: ManagerTheme.brandGold,
          unselectedLabelColor: Colors.white54,
          tabs: [
            const Tab(text: 'Agents Fleet (6)', icon: Icon(Icons.smart_toy_rounded, size: 20)),
            Tab(
              icon: const Icon(Icons.rate_review_rounded, size: 20),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Text('Review Queue'),
                  if (pendingCount > 0) ...[
                    const SizedBox(width: 6),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: ManagerTheme.brandRuby,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text('$pendingCount', style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.white)),
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : TabBarView(
              controller: _tabController,
              children: [
                _buildAgentsTab(),
                _buildOutputsTab(),
              ],
            ),
    );
  }

  Widget _buildAgentsTab() {
    if (_agents.isEmpty) {
      return const Center(
        child: Text('No AI agents registered in swarm.', style: TextStyle(color: Colors.white54)),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _agents.length,
      itemBuilder: (ctx, i) {
        final ag = _agents[i];
        final isActive = ag['is_active'] == true;

        return Container(
          margin: const EdgeInsets.only(bottom: 14),
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: ManagerTheme.surfaceDark,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: isActive ? ManagerTheme.brandGold.withValues(alpha: 0.3) : Colors.white10,
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: Colors.black26,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.white12),
                    ),
                    alignment: Alignment.center,
                    child: Text(ag['icon'] ?? '🤖', style: const TextStyle(fontSize: 22)),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          ag['name'] ?? 'Agent',
                          style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                        ),
                        const SizedBox(height: 2),
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                              decoration: BoxDecoration(
                                color: (ag['mode'] == 'autonomous')
                                    ? ManagerTheme.brandEmerald.withValues(alpha: 0.2)
                                    : ManagerTheme.brandGold.withValues(alpha: 0.2),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: Text(
                                (ag['mode'] == 'autonomous') ? 'AUTONOMOUS' : 'SUPERVISED',
                                style: TextStyle(
                                  fontSize: 9.5,
                                  fontWeight: FontWeight.bold,
                                  color: (ag['mode'] == 'autonomous') ? ManagerTheme.brandEmerald : ManagerTheme.brandGold,
                                ),
                              ),
                            ),
                            const SizedBox(width: 6),
                            Text('Cadence: ${ag['cron_cadence']}', style: const TextStyle(fontSize: 10, color: Colors.white54)),
                          ],
                        ),
                      ],
                    ),
                  ),
                  Switch(
                    value: isActive,
                    activeThumbColor: ManagerTheme.brandGold,
                    onChanged: (val) => _toggleAgent(ag),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Text(
                ag['role_description'] ?? '',
                style: const TextStyle(fontSize: 12, color: Colors.white70, height: 1.4),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      style: OutlinedButton.styleFrom(
                        foregroundColor: Colors.white70,
                        side: const BorderSide(color: Colors.white24),
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      onPressed: () => _openAgentTuningSheet(ag),
                      icon: const Icon(Icons.tune_rounded, size: 14),
                      label: const Text('Tune Prompt', style: TextStyle(fontSize: 11)),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: ManagerTheme.brandGold,
                        foregroundColor: Colors.black,
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      onPressed: () => _runAgentNow(ag),
                      icon: const Icon(Icons.flash_on_rounded, size: 14),
                      label: const Text('Run Agent', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
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

  Widget _buildOutputsTab() {
    if (_outputs.isEmpty) {
      return const Center(
        child: Text('No synthesized outputs yet. Run an agent to generate content!', style: TextStyle(color: Colors.white54)),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _outputs.length,
      itemBuilder: (ctx, i) {
        final out = _outputs[i];
        final isPending = out['status'] == 'pending_review';

        return Container(
          margin: const EdgeInsets.only(bottom: 14),
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: ManagerTheme.surfaceDark,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: isPending ? ManagerTheme.brandGold.withValues(alpha: 0.4) : Colors.white10,
            ),
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
                      color: isPending ? ManagerTheme.brandGold.withValues(alpha: 0.2) : ManagerTheme.brandEmerald.withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      isPending ? 'PENDING REVIEW' : 'PUBLISHED',
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.bold,
                        color: isPending ? ManagerTheme.brandGold : ManagerTheme.brandEmerald,
                      ),
                    ),
                  ),
                  Text(
                    out['agent']?['name'] ?? 'Agent Output',
                    style: const TextStyle(fontSize: 11, color: Colors.white54),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Text(
                out['title'] ?? 'Synthesized Asset',
                style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 6),
              Text(
                out['content_payload'] ?? '',
                maxLines: 3,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(fontSize: 12, color: Colors.white70, height: 1.4),
              ),
              const SizedBox(height: 12),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  if (out['media_url'] != null)
                    TextButton.icon(
                      style: TextButton.styleFrom(foregroundColor: Colors.blueAccent),
                      onPressed: () => _showMediaPreview(out['media_url'].toString(), out['title']?.toString() ?? 'Media Asset'),
                      icon: const Icon(Icons.open_in_new_rounded, size: 14),
                      label: const Text('View 9:16 Reel / Image', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                    )
                  else
                    const SizedBox(),
                  if (isPending)
                    ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: ManagerTheme.brandEmerald,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      onPressed: () => _approveOutput(out),
                      icon: const Icon(Icons.check_rounded, size: 14),
                      label: const Text('1-Tap Approve & Broadcast', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
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
