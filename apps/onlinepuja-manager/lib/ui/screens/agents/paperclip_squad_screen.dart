import 'dart:async';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../models/paperclip_model.dart';
import '../../../providers/paperclip_provider.dart';
import '../../../theme/manager_theme.dart';

class PaperclipSquadScreen extends StatefulWidget {
  final bool embedded;

  const PaperclipSquadScreen({super.key, this.embedded = false});

  @override
  State<PaperclipSquadScreen> createState() => _PaperclipSquadScreenState();
}

class _PaperclipSquadScreenState extends State<PaperclipSquadScreen> {
  final TextEditingController _missionTitleCtrl = TextEditingController();
  final TextEditingController _missionDescCtrl = TextEditingController();
  String _selectedPriority = 'high';
  String? _selectedAssigneeId;
  int _activeTab = 0; // 0 = Squad, 1 = Missions & Backlog
  Timer? _pollTimer;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<PaperclipProvider>().fetchAll();
    });
    _pollTimer = Timer.periodic(const Duration(seconds: 15), (_) {
      if (mounted) {
        context.read<PaperclipProvider>().fetchAll(silent: true);
      }
    });
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    _missionTitleCtrl.dispose();
    _missionDescCtrl.dispose();
    super.dispose();
  }

  Future<void> _openPortal() async {
    final uri = Uri.parse('https://agents.vmstudio.digital/ONL/dashboard');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  void _showDispatchMissionSheet() {
    final provider = context.read<PaperclipProvider>();
    final agents = provider.agents;

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
                            Icon(Icons.rocket_launch_rounded, color: ManagerTheme.brandGold, size: 22),
                            SizedBox(width: 8),
                            Text(
                              'Dispatch Autonomous Mission',
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
                    TextField(
                      controller: _missionTitleCtrl,
                      style: const TextStyle(color: Colors.white),
                      decoration: InputDecoration(
                        labelText: 'Mission Objective',
                        labelStyle: const TextStyle(color: ManagerTheme.brandGold),
                        hintText: 'e.g. Generate 5 viral reels for upcoming Pradosh Vrat',
                        hintStyle: const TextStyle(color: Colors.white38),
                        filled: true,
                        fillColor: const Color(0xFF1E293B),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _missionDescCtrl,
                      maxLines: 3,
                      style: const TextStyle(color: Colors.white),
                      decoration: InputDecoration(
                        labelText: 'Instructions & Strategy Context',
                        labelStyle: const TextStyle(color: ManagerTheme.brandGold),
                        hintText: 'Include target hashtags, Gotra Sankalp focus, or temple guidelines...',
                        hintStyle: const TextStyle(color: Colors.white38),
                        filled: true,
                        fillColor: const Color(0xFF1E293B),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: DropdownButtonFormField<String>(
                            value: _selectedAssigneeId ?? (agents.isNotEmpty ? agents.first.id : null),
                            dropdownColor: const Color(0xFF1E293B),
                            style: const TextStyle(color: Colors.white, fontSize: 13),
                            decoration: InputDecoration(
                              labelText: 'Assignee Agent',
                              labelStyle: const TextStyle(color: Colors.white70),
                              filled: true,
                              fillColor: const Color(0xFF1E293B),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                            items: agents.map((a) {
                              return DropdownMenuItem(
                                value: a.id,
                                child: Text(a.name, overflow: TextOverflow.ellipsis),
                              );
                            }).toList(),
                            onChanged: (val) => setSheetState(() => _selectedAssigneeId = val),
                          ),
                        ),
                        const SizedBox(width: 12),
                        SizedBox(
                          width: 120,
                          child: DropdownButtonFormField<String>(
                            value: _selectedPriority,
                            dropdownColor: const Color(0xFF1E293B),
                            style: const TextStyle(color: Colors.white, fontSize: 13),
                            decoration: InputDecoration(
                              labelText: 'Priority',
                              labelStyle: const TextStyle(color: Colors.white70),
                              filled: true,
                              fillColor: const Color(0xFF1E293B),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                            items: const [
                              DropdownMenuItem(value: 'low', child: Text('Low')),
                              DropdownMenuItem(value: 'medium', child: Text('Medium')),
                              DropdownMenuItem(value: 'high', child: Text('High')),
                              DropdownMenuItem(value: 'urgent', child: Text('Urgent')),
                            ],
                            onChanged: (val) => setSheetState(() => _selectedPriority = val ?? 'high'),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),
                    SizedBox(
                      width: double.infinity,
                      height: 48,
                      child: ElevatedButton.icon(
                        icon: const Icon(Icons.send_rounded, color: Colors.black),
                        label: const Text(
                          'Launch Mission into Paperclip',
                          style: TextStyle(color: Colors.black, fontWeight: FontWeight.bold, fontSize: 15),
                        ),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: ManagerTheme.brandGold,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        onPressed: () async {
                          final title = _missionTitleCtrl.text.trim();
                          if (title.isEmpty) return;

                          Navigator.pop(ctx);
                          final ok = await provider.dispatchMission(
                            title: title,
                            description: _missionDescCtrl.text.trim(),
                            priority: _selectedPriority,
                            assigneeId: _selectedAssigneeId,
                          );

                          if (mounted) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(
                                content: Text(ok ? '🚀 Mission successfully dispatched!' : 'Failed to dispatch mission.'),
                                backgroundColor: ok ? ManagerTheme.brandEmerald : Colors.red,
                              ),
                            );
                            _missionTitleCtrl.clear();
                            _missionDescCtrl.clear();
                          }
                        },
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

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<PaperclipProvider>();
    final metrics = provider.overview?.metrics;
    final agents = provider.agents;
    final issues = provider.issues;

    return Scaffold(
      backgroundColor: ManagerTheme.bgDark,
      appBar: widget.embedded
          ? null
          : AppBar(
              backgroundColor: const Color(0xFF0F172A),
              title: const Row(
                children: [
                  Icon(Icons.hub_rounded, color: ManagerTheme.brandGold, size: 24),
                  SizedBox(width: 8),
                  Text('Paperclip AI Operations'),
                ],
              ),
              actions: [
                IconButton(
                  icon: const Icon(Icons.open_in_new_rounded, color: ManagerTheme.brandGold),
                  tooltip: 'Open Web Portal',
                  onPressed: _openPortal,
                ),
                IconButton(
                  icon: const Icon(Icons.refresh_rounded, color: Colors.white70),
                  onPressed: () => provider.fetchAll(),
                ),
              ],
            ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: ManagerTheme.brandGold,
        foregroundColor: Colors.black,
        icon: const Icon(Icons.add_task_rounded),
        label: const Text('Dispatch Mission', style: TextStyle(fontWeight: FontWeight.bold)),
        onPressed: _showDispatchMissionSheet,
      ),
      body: provider.isLoading && provider.overview == null
          ? const Center(child: CircularProgressIndicator(color: ManagerTheme.brandGold))
          : RefreshIndicator(
              color: ManagerTheme.brandGold,
              onRefresh: () => provider.fetchAll(),
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  // Portal banner card
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFF1E293B), Color(0xFF0F172A)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: ManagerTheme.brandGold.withOpacity(0.3)),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Row(
                              children: [
                                Text('⚡ OnlinePuja.live Autonomous Squad', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white, fontSize: 15)),
                              ],
                            ),
                            const SizedBox(height: 4),
                            Text(
                              'Company ID: ONL · 7 AI Agents Reporting to Chief Growth Officer',
                              style: TextStyle(color: Colors.white.withOpacity(0.6), fontSize: 11),
                            ),
                          ],
                        ),
                        InkWell(
                          onTap: _openPortal,
                          borderRadius: BorderRadius.circular(8),
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                            decoration: BoxDecoration(
                              color: ManagerTheme.brandGold.withOpacity(0.15),
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(color: ManagerTheme.brandGold),
                            ),
                            child: const Row(
                              children: [
                                Text('Portal', style: TextStyle(color: ManagerTheme.brandGold, fontSize: 12, fontWeight: FontWeight.bold)),
                                SizedBox(width: 4),
                                Icon(Icons.arrow_outward_rounded, color: ManagerTheme.brandGold, size: 14),
                              ],
                            ),
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
                        'Total Agents',
                        '${metrics?.totalAgents ?? agents.length}',
                        Icons.smart_toy_rounded,
                        const Color(0xFF60A5FA),
                      ),
                      const SizedBox(width: 8),
                      _buildMetricCard(
                        'Active',
                        '${metrics?.activeMissions ?? 0}',
                        Icons.pending_actions_rounded,
                        ManagerTheme.brandGold,
                      ),
                      const SizedBox(width: 8),
                      _buildMetricCard(
                        'Completed',
                        '${metrics?.completedMissions ?? 0}',
                        Icons.task_alt_rounded,
                        ManagerTheme.brandEmerald,
                      ),
                      const SizedBox(width: 8),
                      _buildMetricCard(
                        'Backlog',
                        '${metrics?.totalIssues ?? issues.length}',
                        Icons.layers_rounded,
                        const Color(0xFFA78BFA),
                      ),
                    ],
                  ),

                  const SizedBox(height: 20),

                  // Segmented Tab Selector
                  Container(
                    decoration: BoxDecoration(
                      color: const Color(0xFF1E293B),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    padding: const EdgeInsets.all(4),
                    child: Row(
                      children: [
                        Expanded(
                          child: _buildTabButton('Autonomous Squad (7)', 0),
                        ),
                        Expanded(
                          child: _buildTabButton('Missions & Backlog (${issues.length})', 1),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 16),

                  if (_activeTab == 0) ...[
                    // Squad List
                    if (agents.isEmpty)
                      const Center(
                        child: Padding(
                          padding: EdgeInsets.all(32),
                          child: Text('Connecting to Paperclip Squad...', style: TextStyle(color: Colors.white54)),
                        ),
                      )
                    else
                      ...agents.map((agent) => _buildAgentCard(agent, provider)),
                  ] else ...[
                    // Issues & Missions List
                    if (issues.isEmpty)
                      const Center(
                        child: Padding(
                          padding: EdgeInsets.all(32),
                          child: Text('No active missions found. Dispatch one above!', style: TextStyle(color: Colors.white54)),
                        ),
                      )
                    else
                      ...issues.map((issue) => _buildIssueCard(issue)),
                  ],

                  const SizedBox(height: 80),
                ],
              ),
            ),
    );
  }

  Widget _buildMetricCard(String label, String value, IconData icon, Color color) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
        decoration: BoxDecoration(
          color: const Color(0xFF1E293B),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: color.withOpacity(0.3)),
        ),
        child: Column(
          children: [
            Icon(icon, color: color, size: 20),
            const SizedBox(height: 4),
            Text(value, style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: color)),
            const SizedBox(height: 2),
            Text(label, style: const TextStyle(fontSize: 10, color: Colors.white60), textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }

  Widget _buildTabButton(String label, int index) {
    final isSelected = _activeTab == index;
    return InkWell(
      onTap: () => setState(() => _activeTab = index),
      borderRadius: BorderRadius.circular(10),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 10),
        decoration: BoxDecoration(
          color: isSelected ? ManagerTheme.brandGold : Colors.transparent,
          borderRadius: BorderRadius.circular(10),
        ),
        alignment: Alignment.center,
        child: Text(
          label,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.bold,
            color: isSelected ? Colors.black : Colors.white70,
          ),
        ),
      ),
    );
  }

  Widget _buildAgentCard(PaperclipAgentModel agent, PaperclipProvider provider) {
    final isPaused = agent.isPaused;
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: agent.isChiefOfStaff ? ManagerTheme.brandGold : const Color(0xFF334155),
          width: agent.isChiefOfStaff ? 1.5 : 1,
        ),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CircleAvatar(
            backgroundColor: agent.isChiefOfStaff ? ManagerTheme.brandGold.withOpacity(0.2) : const Color(0xFF334155),
            radius: 22,
            child: Text(
              agent.name.isNotEmpty ? agent.name[0] : '🤖',
              style: TextStyle(fontWeight: FontWeight.bold, color: agent.isChiefOfStaff ? ManagerTheme.brandGold : Colors.white),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        agent.name,
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Colors.white),
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: isPaused ? Colors.red.withOpacity(0.2) : ManagerTheme.brandEmerald.withOpacity(0.2),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        isPaused ? 'PAUSED' : 'READY',
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                          color: isPaused ? Colors.redAccent : ManagerTheme.brandEmerald,
                        ),
                      ),
                    ),
                  ],
                ),
                if (agent.title != null) ...[
                  const SizedBox(height: 2),
                  Text(
                    agent.title!,
                    style: TextStyle(fontSize: 11, color: ManagerTheme.brandGold.withOpacity(0.9)),
                  ),
                ],
                const SizedBox(height: 6),
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: const Color(0xFF334155),
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: Text(
                        agent.role.toUpperCase(),
                        style: const TextStyle(fontSize: 9, color: Colors.white70, fontWeight: FontWeight.bold),
                      ),
                    ),
                    const SizedBox(width: 8),
                    if (agent.model != null)
                      Text(
                        agent.model!,
                        style: const TextStyle(fontSize: 10, color: Colors.white38),
                      ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          IconButton(
            icon: Icon(
              isPaused ? Icons.play_arrow_rounded : Icons.pause_rounded,
              color: isPaused ? ManagerTheme.brandEmerald : Colors.orangeAccent,
              size: 24,
            ),
            tooltip: isPaused ? 'Resume Agent' : 'Pause Agent',
            onPressed: () {
              provider.toggleAgentPause(agent.id, !isPaused);
            },
          ),
        ],
      ),
    );
  }

  Widget _buildIssueCard(PaperclipIssueModel issue) {
    Color statusColor = ManagerTheme.brandGold;
    if (issue.isDone) statusColor = ManagerTheme.brandEmerald;
    if (issue.isBlocked) statusColor = Colors.redAccent;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF334155)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: const Color(0xFF0F172A),
                  borderRadius: BorderRadius.circular(6),
                  border: Border.all(color: statusColor.withOpacity(0.5)),
                ),
                child: Text(
                  issue.identifier,
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: statusColor),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: statusColor.withOpacity(0.15),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  issue.status.toUpperCase(),
                  style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: statusColor),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            issue.title,
            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Colors.white),
          ),
          if (issue.description.isNotEmpty) ...[
            const SizedBox(height: 4),
            Text(
              issue.description,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(fontSize: 12, color: Colors.white60),
            ),
          ],
          if (issue.assigneeAgentId != null && issue.assigneeAgentId!.isNotEmpty) ...[
            const SizedBox(height: 8),
            Row(
              children: [
                const Icon(Icons.person_outline_rounded, size: 14, color: Colors.white38),
                const SizedBox(width: 4),
                Text(
                  'Assigned to AI Agent (${issue.assigneeAgentId!.substring(0, 8)}...)',
                  style: const TextStyle(fontSize: 11, color: Colors.white54),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}
