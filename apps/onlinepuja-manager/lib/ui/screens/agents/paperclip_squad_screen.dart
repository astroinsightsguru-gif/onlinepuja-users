import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_markdown/flutter_markdown.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../models/paperclip_model.dart';
import '../../../providers/paperclip_provider.dart';
import '../../../services/manager_api_service.dart';
import '../../../theme/manager_theme.dart';

class PaperclipSquadScreen extends StatefulWidget {
  final bool embedded;

  const PaperclipSquadScreen({super.key, this.embedded = false});

  @override
  State<PaperclipSquadScreen> createState() => _PaperclipSquadScreenState();
}

class _PaperclipSquadScreenState extends State<PaperclipSquadScreen> {
  final ManagerApiService _api = ManagerApiService();
  final TextEditingController _missionTitleCtrl = TextEditingController();
  final TextEditingController _missionDescCtrl = TextEditingController();
  String _selectedPriority = 'high';
  String? _selectedAssigneeId;
  int _activeTab = 0; // 0 = Squad, 1 = Missions & Backlog
  Timer? _pollTimer;

  final TextEditingController _businessNameCtrl = TextEditingController();
  final TextEditingController _businessDescCtrl = TextEditingController();
  bool _isCreatingBusiness = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final p = context.read<PaperclipProvider>();
      p.fetchAll();
      p.fetchActivities();
      p.fetchReports();
    });
    _pollTimer = Timer.periodic(const Duration(seconds: 15), (_) {
      if (mounted) {
        final p = context.read<PaperclipProvider>();
        p.fetchAll(silent: true);
        p.fetchActivities();
      }
    });
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    _missionTitleCtrl.dispose();
    _missionDescCtrl.dispose();
    _businessNameCtrl.dispose();
    _businessDescCtrl.dispose();
    super.dispose();
  }

  void _showBusinessSwitcherSheet(PaperclipProvider provider) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return Container(
          padding: const EdgeInsets.all(20),
          decoration: const BoxDecoration(
            color: Color(0xFF0F172A),
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
            border: Border(top: BorderSide(color: ManagerTheme.brandGold, width: 2)),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Row(
                    children: [
                      Icon(Icons.business_rounded, color: ManagerTheme.brandGold, size: 22),
                      SizedBox(width: 8),
                      Text(
                        'Select Paperclip Organization',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
                      ),
                    ],
                  ),
                  TextButton.icon(
                    style: TextButton.styleFrom(foregroundColor: ManagerTheme.brandGold),
                    icon: const Icon(Icons.add_rounded, size: 16),
                    label: const Text('+ New Business', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                    onPressed: () {
                      Navigator.pop(ctx);
                      _showCreateBusinessSheet(provider);
                    },
                  ),
                ],
              ),
              const SizedBox(height: 12),
              if (provider.companies.isEmpty)
                const Center(child: Padding(padding: EdgeInsets.all(20), child: Text('No companies registered.', style: TextStyle(color: Colors.white60))))
              else
                ...provider.companies.map((c) {
                  final id = c['id']?.toString() ?? '';
                  final name = c['name']?.toString() ?? 'Organization';
                  final prefix = c['issuePrefix']?.toString() ?? '';
                  final desc = c['description']?.toString() ?? '';
                  final isSelected = provider.selectedCompanyId == id;

                  return Container(
                    margin: const EdgeInsets.only(bottom: 8),
                    decoration: BoxDecoration(
                      color: isSelected ? ManagerTheme.brandGold.withOpacity(0.12) : const Color(0xFF1E293B),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: isSelected ? ManagerTheme.brandGold : Colors.white12, width: isSelected ? 1.5 : 1),
                    ),
                    child: ListTile(
                      dense: true,
                      leading: CircleAvatar(
                        backgroundColor: isSelected ? ManagerTheme.brandGold : const Color(0xFF334155),
                        radius: 16,
                        child: Text(
                          prefix.isNotEmpty ? prefix.substring(0, prefix.length.clamp(1, 2)) : name[0],
                          style: TextStyle(color: isSelected ? Colors.black : Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                        ),
                      ),
                      title: Text(name, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14)),
                      subtitle: desc.isNotEmpty
                          ? Text(desc, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white54, fontSize: 11))
                          : null,
                      trailing: isSelected
                          ? const Icon(Icons.check_circle_rounded, color: ManagerTheme.brandGold, size: 20)
                          : const Icon(Icons.chevron_right_rounded, color: Colors.white38),
                      onTap: () {
                        provider.switchCompany(id, name);
                        Navigator.pop(ctx);
                      },
                    ),
                  );
                }),
              const SizedBox(height: 10),
            ],
          ),
        );
      },
    );
  }

  void _showCreateBusinessSheet(PaperclipProvider provider) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setModalState) {
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
                            Icon(Icons.domain_add_rounded, color: ManagerTheme.brandGold, size: 22),
                            SizedBox(width: 8),
                            Text(
                              'Register New Business Organization',
                              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
                            ),
                          ],
                        ),
                        IconButton(
                          icon: const Icon(Icons.close_rounded, color: Colors.white70),
                          onPressed: () => Navigator.pop(ctx),
                        ),
                      ],
                    ),
                    const SizedBox(height: 14),
                    TextField(
                      controller: _businessNameCtrl,
                      style: const TextStyle(color: Colors.white),
                      decoration: InputDecoration(
                        labelText: 'Business / Company Name',
                        labelStyle: const TextStyle(color: ManagerTheme.brandGold),
                        hintText: 'e.g. AstroGurukul.com or VedMart Hyperlocal',
                        hintStyle: const TextStyle(color: Colors.white38),
                        filled: true,
                        fillColor: const Color(0xFF1E293B),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _businessDescCtrl,
                      maxLines: 2,
                      style: const TextStyle(color: Colors.white),
                      decoration: InputDecoration(
                        labelText: 'Business Purpose & Domain',
                        labelStyle: const TextStyle(color: Colors.white70),
                        hintText: 'Describe industry, target audience, and primary automation requirements...',
                        hintStyle: const TextStyle(color: Colors.white38),
                        filled: true,
                        fillColor: const Color(0xFF1E293B),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
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
                        onPressed: _isCreatingBusiness
                            ? null
                            : () async {
                                final name = _businessNameCtrl.text.trim();
                                if (name.isEmpty) return;
                                setModalState(() => _isCreatingBusiness = true);
                                final ok = await provider.createCompany(name, _businessDescCtrl.text.trim());
                                setModalState(() => _isCreatingBusiness = false);
                                if (mounted) {
                                  Navigator.pop(ctx);
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(
                                      content: Text(ok ? '✓ Business $name registered in Paperclip!' : 'Failed to register business.'),
                                      backgroundColor: ok ? ManagerTheme.brandEmerald : Colors.redAccent,
                                    ),
                                  );
                                  _businessNameCtrl.clear();
                                  _businessDescCtrl.clear();
                                }
                              },
                        child: _isCreatingBusiness
                            ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.black))
                            : const Text('Provision Organization & Squad', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
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

  Future<void> _showEmergencyHaltDialog(bool isCurrentlyHalted) async {
    final willHalt = !isCurrentlyHalted;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: const Color(0xFF0F172A),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: BorderSide(color: willHalt ? Colors.redAccent : ManagerTheme.brandEmerald, width: 1.5),
        ),
        title: Row(
          children: [
            Icon(
              willHalt ? Icons.warning_amber_rounded : Icons.play_circle_outline_rounded,
              color: willHalt ? Colors.redAccent : ManagerTheme.brandEmerald,
              size: 26,
            ),
            const SizedBox(width: 10),
            Text(
              willHalt ? 'Swarm Emergency Halt' : 'Resume Autonomous Swarm',
              style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold),
            ),
          ],
        ),
        content: Text(
          willHalt
              ? 'Are you sure you want to freeze all 8 autonomous background agents immediately? Active tasks, scheduled SEO drafts, and automated follow-ups will halt.'
              : 'Resume all autonomous background agents? Operations, SEO content publishing, and Panchang radar will resume standard execution.',
          style: const TextStyle(color: Colors.white70, fontSize: 13),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel', style: TextStyle(color: Colors.white60)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: willHalt ? Colors.redAccent : ManagerTheme.brandEmerald,
              foregroundColor: Colors.white,
            ),
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(willHalt ? 'Freeze All Agents' : 'Resume Operations'),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      final ok = await _api.emergencyHaltSwarm(halt: willHalt);
      if (mounted) {
        context.read<PaperclipProvider>().fetchAll();
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(ok
                ? (willHalt ? '🛑 Swarm frozen: all 8 agents paused.' : '✓ Autonomous swarm resumed successfully.')
                : 'Failed to update swarm state.'),
            backgroundColor: willHalt ? Colors.redAccent : ManagerTheme.brandEmerald,
          ),
        );
      }
    }
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

    final isSwarmHalted = agents.isNotEmpty && agents.every((a) => a.isPaused);

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
                  icon: Icon(
                    isSwarmHalted ? Icons.play_circle_fill_rounded : Icons.shield_rounded,
                    color: isSwarmHalted ? ManagerTheme.brandEmerald : Colors.redAccent,
                    size: 22,
                  ),
                  tooltip: isSwarmHalted ? 'Resume Swarm' : 'Emergency Swarm Halt',
                  onPressed: () => _showEmergencyHaltDialog(isSwarmHalted),
                ),
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
                  // 🛑 Emergency Swarm Halt Banner
                  if (isSwarmHalted)
                    Container(
                      margin: const EdgeInsets.only(bottom: 14),
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFF7F1D1D), Color(0xFF450A0A)],
                        ),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.redAccent, width: 1.2),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.shield_rounded, color: Colors.redAccent, size: 22),
                          const SizedBox(width: 10),
                          const Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('SWARM EMERGENCY HALT ACTIVE', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12)),
                                Text('All 8 background autonomous agents are frozen.', style: TextStyle(color: Colors.white70, fontSize: 11)),
                              ],
                            ),
                          ),
                          ElevatedButton(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: ManagerTheme.brandEmerald,
                              foregroundColor: Colors.white,
                              visualDensity: VisualDensity.compact,
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                            ),
                            onPressed: () => _showEmergencyHaltDialog(true),
                            child: const Text('Resume', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11)),
                          ),
                        ],
                      ),
                    ),
                  // Multi-Business Selector Header Card
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFF1E293B), Color(0xFF0F172A)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: ManagerTheme.brandGold.withOpacity(0.4)),
                    ),
                    child: Column(
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Row(
                              children: [
                                Container(
                                  padding: const EdgeInsets.all(8),
                                  decoration: BoxDecoration(
                                    color: ManagerTheme.brandGold.withOpacity(0.15),
                                    shape: BoxShape.circle,
                                  ),
                                  child: const Icon(Icons.business_center_rounded, color: ManagerTheme.brandGold, size: 20),
                                ),
                                const SizedBox(width: 10),
                                Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      provider.selectedCompanyName,
                                      style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.white, fontSize: 16),
                                    ),
                                    Text(
                                      'Autonomous AI Squad & Operations Hub',
                                      style: TextStyle(color: Colors.white.withOpacity(0.6), fontSize: 11),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                            ElevatedButton.icon(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: ManagerTheme.brandGold,
                                foregroundColor: Colors.black,
                                visualDensity: VisualDensity.compact,
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                              ),
                              icon: const Icon(Icons.swap_horiz_rounded, size: 16),
                              label: const Text('Switch Business', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11)),
                              onPressed: () => _showBusinessSwitcherSheet(provider),
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        const Divider(color: Colors.white12, height: 1),
                        const SizedBox(height: 8),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              '${provider.companies.length} Organizations Registered in Cluster',
                              style: TextStyle(color: Colors.white.withOpacity(0.5), fontSize: 11),
                            ),
                            InkWell(
                              onTap: () => _showCreateBusinessSheet(provider),
                              child: const Row(
                                children: [
                                  Icon(Icons.add_circle_outline_rounded, color: ManagerTheme.brandGold, size: 14),
                                  SizedBox(width: 4),
                                  Text('+ Register Business', style: TextStyle(color: ManagerTheme.brandGold, fontSize: 11, fontWeight: FontWeight.bold)),
                                ],
                              ),
                            ),
                          ],
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
                        'Active Missions',
                        '${metrics?.activeMissions ?? 0}',
                        Icons.pending_actions_rounded,
                        ManagerTheme.brandGold,
                      ),
                      const SizedBox(width: 8),
                      _buildMetricCard(
                        'Agent Feed',
                        '${provider.activities.length}',
                        Icons.notifications_active_rounded,
                        ManagerTheme.brandEmerald,
                      ),
                      const SizedBox(width: 8),
                      _buildMetricCard(
                        'Reports Vault',
                        '${provider.reports.length}',
                        Icons.description_rounded,
                        const Color(0xFFA78BFA),
                      ),
                    ],
                  ),

                  const SizedBox(height: 18),

                  // 4-Tab Segmented Selector
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: Row(
                      children: [
                        _buildTabButton('Squad & Agents (${agents.length})', 0),
                        const SizedBox(width: 8),
                        _buildTabButton('⚡ What Agents Are Doing (${provider.activities.length})', 1),
                        const SizedBox(width: 8),
                        _buildTabButton('Missions & Kanban (${issues.length})', 2),
                        const SizedBox(width: 8),
                        _buildTabButton('📑 Reports & Deliverables (${provider.reports.length})', 3),
                      ],
                    ),
                  ),

                  const SizedBox(height: 16),

                  if (_activeTab == 0) ...[
                    // Tab 0: Squad List
                    if (agents.isEmpty)
                      const Center(
                        child: Padding(
                          padding: EdgeInsets.all(32),
                          child: Text('Connecting to Paperclip Squad...', style: TextStyle(color: Colors.white54)),
                        ),
                      )
                    else
                      ...agents.map((agent) => _buildAgentCard(agent, provider)),
                  ] else if (_activeTab == 1) ...[
                    // Tab 1: What Agents Are Doing Right Now (Live Activity Stream & Reminders)
                    if (provider.isLoadingActivities)
                      const Center(child: Padding(padding: EdgeInsets.all(32), child: CircularProgressIndicator(color: ManagerTheme.brandGold)))
                    else if (provider.activities.isEmpty)
                      Container(
                        padding: const EdgeInsets.all(24),
                        decoration: BoxDecoration(color: ManagerTheme.surfaceDark, borderRadius: BorderRadius.circular(14)),
                        child: const Center(
                          child: Text('No recent background agent activity for this organization.', style: TextStyle(color: Colors.white60)),
                        ),
                      )
                    else
                      ...provider.activities.map((act) => _buildActivityItem(act)),
                  ] else if (_activeTab == 2) ...[
                    // Tab 2: Issues & Missions List
                    if (issues.isEmpty)
                      const Center(
                        child: Padding(
                          padding: EdgeInsets.all(32),
                          child: Text('No active missions found. Dispatch one above!', style: TextStyle(color: Colors.white54)),
                        ),
                      )
                    else
                      ...issues.map((issue) => _buildIssueCard(issue)),
                  ] else if (_activeTab == 3) ...[
                    // Tab 3: Reports & Deliverables Vault
                    if (provider.isLoadingReports)
                      const Center(child: Padding(padding: EdgeInsets.all(32), child: CircularProgressIndicator(color: ManagerTheme.brandGold)))
                    else if (provider.reports.isEmpty)
                      Container(
                        padding: const EdgeInsets.all(24),
                        decoration: BoxDecoration(color: ManagerTheme.surfaceDark, borderRadius: BorderRadius.circular(14)),
                        child: const Center(
                          child: Text('No completed deliverables or reports generated yet.', style: TextStyle(color: Colors.white60)),
                        ),
                      )
                    else
                      ...provider.reports.map((rep) => _buildReportItem(rep)),
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

  Widget _buildActivityItem(Map<String, dynamic> act) {
    final action = act['action']?.toString() ?? 'activity';
    final details = act['details'] as Map<String, dynamic>? ?? {};
    final createdAt = act['createdAt']?.toString() ?? '';
    final bodySnippet = details['bodySnippet']?.toString() ?? '';
    final issueTitle = details['issueTitle']?.toString() ?? details['identifier']?.toString() ?? '';
    final failureReason = details['failureReason']?.toString() ?? '';
    final isFailure = failureReason.isNotEmpty || details['status'] == 'failed';

    Color badgeColor = ManagerTheme.brandGold;
    IconData badgeIcon = Icons.bolt_rounded;

    if (action.contains('comment')) {
      badgeColor = const Color(0xFF38BDF8);
      badgeIcon = Icons.chat_bubble_outline_rounded;
    } else if (action.contains('lease') || action.contains('environment')) {
      badgeColor = isFailure ? Colors.redAccent : ManagerTheme.brandEmerald;
      badgeIcon = isFailure ? Icons.error_outline_rounded : Icons.memory_rounded;
    } else if (action.contains('cost')) {
      badgeColor = const Color(0xFFA78BFA);
      badgeIcon = Icons.paid_rounded;
    }

    String formattedTime = createdAt.length > 16 ? createdAt.substring(11, 16) + ' UTC' : createdAt;

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: isFailure ? Colors.redAccent.withOpacity(0.4) : Colors.white12),
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
                    padding: const EdgeInsets.all(6),
                    decoration: BoxDecoration(
                      color: badgeColor.withOpacity(0.15),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(badgeIcon, color: badgeColor, size: 14),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    action.toUpperCase().replaceAll('.', ' · '),
                    style: TextStyle(color: badgeColor, fontSize: 10, fontWeight: FontWeight.bold),
                  ),
                ],
              ),
              Text(formattedTime, style: TextStyle(color: Colors.white.withOpacity(0.4), fontSize: 10)),
            ],
          ),
          if (issueTitle.isNotEmpty) ...[
            const SizedBox(height: 8),
            Text(issueTitle, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13)),
          ],
          if (bodySnippet.isNotEmpty) ...[
            const SizedBox(height: 6),
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: const Color(0xFF0F172A),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                bodySnippet.replaceAll(RegExp(r'[#*`]'), ''),
                maxLines: 3,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(color: Colors.white70, fontSize: 11, height: 1.4),
              ),
            ),
          ],
          if (isFailure && failureReason.isNotEmpty) ...[
            const SizedBox(height: 6),
            Text(
              'Alert: $failureReason',
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(color: Colors.redAccent, fontSize: 11),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildReportItem(Map<String, dynamic> rep) {
    final title = rep['title']?.toString() ?? 'Report';
    final desc = rep['description']?.toString() ?? '';
    final identifier = rep['identifier']?.toString() ?? '';
    final status = rep['status']?.toString() ?? 'done';
    final isDone = status.toLowerCase() == 'done' || status.toLowerCase() == 'completed';

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: isDone ? ManagerTheme.brandEmerald.withOpacity(0.3) : ManagerTheme.brandGold.withOpacity(0.3)),
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
                  color: (isDone ? ManagerTheme.brandEmerald : ManagerTheme.brandGold).withOpacity(0.15),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  identifier.isNotEmpty ? identifier : status.toUpperCase(),
                  style: TextStyle(color: isDone ? ManagerTheme.brandEmerald : ManagerTheme.brandGold, fontSize: 10, fontWeight: FontWeight.bold),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(color: Colors.white.withOpacity(0.08), borderRadius: BorderRadius.circular(4)),
                child: Text(status.toUpperCase(), style: const TextStyle(color: Colors.white70, fontSize: 9, fontWeight: FontWeight.bold)),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(title, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14)),
          if (desc.isNotEmpty) ...[
            const SizedBox(height: 6),
            Text(desc, maxLines: 2, overflow: TextOverflow.ellipsis, style: TextStyle(color: Colors.white.withOpacity(0.7), fontSize: 12)),
          ],
          const SizedBox(height: 10),
          Row(
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: ManagerTheme.brandGold,
                  foregroundColor: Colors.black,
                  visualDensity: VisualDensity.compact,
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                ),
                icon: const Icon(Icons.menu_book_rounded, size: 14),
                label: const Text('Read Deliverable', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11)),
                onPressed: () {
                  _showReportDetailSheet(title, desc, identifier);
                },
              ),
            ],
          ),
        ],
      ),
    );
  }

  void _showReportDetailSheet(String title, String desc, String identifier) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return Container(
          padding: const EdgeInsets.all(20),
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
                    Text(identifier, style: const TextStyle(color: ManagerTheme.brandGold, fontWeight: FontWeight.bold, fontSize: 13)),
                    IconButton(icon: const Icon(Icons.close_rounded, color: Colors.white70), onPressed: () => Navigator.pop(ctx)),
                  ],
                ),
                Text(title, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 18)),
                const SizedBox(height: 14),
                const Divider(color: Colors.white12),
                const SizedBox(height: 10),
                Text(desc, style: const TextStyle(color: Colors.white70, fontSize: 14, height: 1.5)),
                const SizedBox(height: 24),
              ],
            ),
          ),
        );
      },
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
            icon: const Icon(
              Icons.chat_bubble_outline_rounded,
              color: ManagerTheme.brandGold,
              size: 20,
            ),
            tooltip: '1-on-1 Chat with Agent',
            onPressed: () => _openAgentChatModal(agent),
          ),
          IconButton(
            icon: Icon(
              isPaused ? Icons.play_arrow_rounded : Icons.pause_rounded,
              color: isPaused ? ManagerTheme.brandEmerald : Colors.orangeAccent,
              size: 22,
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
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: issue.isBlocked ? Colors.redAccent.withOpacity(0.5) : const Color(0xFF334155),
          width: issue.isBlocked ? 1.5 : 1,
        ),
      ),
      child: InkWell(
        onTap: () => _showIssueDetailSheet(issue),
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
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
                          border: Border.all(color: statusColor.withOpacity(0.5)),
                        ),
                        child: Text(
                          issue.identifier,
                          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: statusColor),
                        ),
                      ),
                      const SizedBox(width: 8),
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
                  const Icon(Icons.arrow_forward_ios_rounded, size: 14, color: Colors.white38),
                ],
              ),
              const SizedBox(height: 10),
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
              if (issue.isBlocked) ...[
                const SizedBox(height: 10),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                  decoration: BoxDecoration(
                    color: Colors.redAccent.withOpacity(0.12),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: Colors.redAccent.withOpacity(0.3)),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.error_outline_rounded, size: 16, color: Colors.redAccent),
                      const SizedBox(width: 8),
                      const Expanded(
                        child: Text(
                          'Mission blocked by LLM router. Tap to inspect & 1-tap unblock.',
                          style: TextStyle(fontSize: 11, color: Colors.redAccent, fontWeight: FontWeight.w500),
                        ),
                      ),
                      const SizedBox(width: 6),
                      TextButton(
                        style: TextButton.styleFrom(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          backgroundColor: ManagerTheme.brandEmerald,
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
                        ),
                        onPressed: () async {
                          final ok = await context.read<PaperclipProvider>().unblockMission(issue.id);
                          if (mounted && ok) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content: Text('Mission unblocked and queued for execution!'),
                                backgroundColor: ManagerTheme.brandEmerald,
                              ),
                            );
                          }
                        },
                        child: const Text('Unblock', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                      ),
                    ],
                  ),
                ),
              ],
              if (issue.isDone) ...[
                const SizedBox(height: 8),
                Row(
                  children: [
                    const Icon(Icons.check_circle_outline_rounded, size: 14, color: ManagerTheme.brandEmerald),
                    const SizedBox(width: 4),
                    const Text('Deliverable ready · Tap to inspect', style: TextStyle(fontSize: 11, color: ManagerTheme.brandEmerald, fontWeight: FontWeight.w600)),
                  ],
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  // ==========================================
  // 🔍 MISSION DETAIL & DELIVERABLE INSPECTOR
  // ==========================================
  void _showIssueDetailSheet(PaperclipIssueModel issue) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return DraggableScrollableSheet(
          initialChildSize: 0.85,
          minChildSize: 0.5,
          maxChildSize: 0.95,
          builder: (_, scrollCtrl) {
            return Container(
              decoration: const BoxDecoration(
                color: Color(0xFF0F172A),
                borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
              ),
              child: FutureBuilder<Map<String, dynamic>?>(
                future: _api.fetchPaperclipIssueDetails(issue.id),
                builder: (context, snapshot) {
                  final details = snapshot.data;
                  final comments = (details?['comments'] as List<dynamic>?) ?? [];
                  final blockerReason = details?['blockerReason'] as String?;

                  return ListView(
                    controller: scrollCtrl,
                    padding: const EdgeInsets.all(20),
                    children: [
                      Center(
                        child: Container(
                          width: 40,
                          height: 4,
                          decoration: BoxDecoration(color: Colors.white24, borderRadius: BorderRadius.circular(2)),
                        ),
                      ),
                      const SizedBox(height: 16),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: const Color(0xFF1E293B),
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(color: ManagerTheme.brandGold),
                            ),
                            child: Text(
                              issue.identifier,
                              style: const TextStyle(fontWeight: FontWeight.bold, color: ManagerTheme.brandGold),
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: issue.isDone
                                  ? ManagerTheme.brandEmerald.withOpacity(0.2)
                                  : (issue.isBlocked ? Colors.redAccent.withOpacity(0.2) : Colors.orangeAccent.withOpacity(0.2)),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Text(
                              issue.status.toUpperCase(),
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                color: issue.isDone
                                    ? ManagerTheme.brandEmerald
                                    : (issue.isBlocked ? Colors.redAccent : Colors.orangeAccent),
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 14),
                      Text(
                        issue.title,
                        style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white),
                      ),
                      if (issue.description.isNotEmpty) ...[
                        const SizedBox(height: 10),
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: const Color(0xFF1E293B),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Text(
                            issue.description,
                            style: const TextStyle(color: Colors.white70, fontSize: 13, height: 1.4),
                          ),
                        ),
                      ],
                      if (issue.isBlocked || blockerReason != null) ...[
                        const SizedBox(height: 16),
                        Container(
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: Colors.redAccent.withOpacity(0.1),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: Colors.redAccent.withOpacity(0.4)),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Row(
                                children: [
                                  Icon(Icons.warning_amber_rounded, color: Colors.redAccent, size: 20),
                                  SizedBox(width: 8),
                                  Text(
                                    'Blocker Diagnostic Telemetry',
                                    style: TextStyle(fontWeight: FontWeight.bold, color: Colors.redAccent, fontSize: 14),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 8),
                              Text(
                                blockerReason ?? 'Adapter error: Context window limit or missing model catalog entry.',
                                style: const TextStyle(fontSize: 12, color: Colors.white70),
                              ),
                              const SizedBox(height: 12),
                              ElevatedButton.icon(
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: ManagerTheme.brandEmerald,
                                  foregroundColor: Colors.white,
                                  minimumSize: const Size.fromHeight(40),
                                ),
                                icon: const Icon(Icons.refresh_rounded, size: 18),
                                label: const Text('Unblock & Auto-Retry Mission Now', style: TextStyle(fontWeight: FontWeight.bold)),
                                onPressed: () async {
                                  Navigator.pop(ctx);
                                  final ok = await context.read<PaperclipProvider>().unblockMission(issue.id);
                                  if (mounted && ok) {
                                    ScaffoldMessenger.of(context).showSnackBar(
                                      const SnackBar(
                                        content: Text('Mission unblocked successfully! Routed to Gemini 3.1 Flash.'),
                                        backgroundColor: ManagerTheme.brandEmerald,
                                      ),
                                    );
                                  }
                                },
                              ),
                            ],
                          ),
                        ),
                      ],
                      const SizedBox(height: 20),
                      const Text(
                        'Agent Deliverables & Commentary',
                        style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white, fontSize: 15),
                      ),
                      const SizedBox(height: 10),
                      if (snapshot.connectionState == ConnectionState.waiting)
                        const Center(child: Padding(padding: EdgeInsets.all(24), child: CircularProgressIndicator(color: ManagerTheme.brandGold)))
                      else if (comments.isEmpty)
                        Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: const Color(0xFF1E293B),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: const Text('No deliverables posted yet. Agent is actively executing in queue.', style: TextStyle(color: Colors.white54, fontSize: 12)),
                        )
                      else
                        ...comments.map((c) {
                          final body = (c['body'] as String?) ?? '';
                          final isArtifact = body.contains('/api/attachments/');
                          String? attachmentId;
                          if (isArtifact) {
                            final match = RegExp(r'/api/attachments/([a-f0-9\-]+)/content').firstMatch(body);
                            if (match != null) attachmentId = match.group(1);
                          }

                          return Container(
                            margin: const EdgeInsets.only(bottom: 12),
                            padding: const EdgeInsets.all(14),
                            decoration: BoxDecoration(
                              color: const Color(0xFF1E293B),
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: const Color(0xFF334155)),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(body, style: const TextStyle(color: Colors.white, fontSize: 13, height: 1.4)),
                                if (attachmentId != null) ...[
                                  const SizedBox(height: 12),
                                  Row(
                                    children: [
                                      Expanded(
                                        child: ElevatedButton.icon(
                                          style: ElevatedButton.styleFrom(
                                            backgroundColor: ManagerTheme.brandGold,
                                            foregroundColor: Colors.black,
                                          ),
                                          icon: const Icon(Icons.description_rounded, size: 16),
                                          label: const Text('Read Full Deliverable', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                                          onPressed: () async {
                                            final content = await _api.fetchPaperclipAttachment(attachmentId!);
                                            if (content != null && mounted) {
                                              _showDeliverableModal(issue.title, content);
                                            }
                                          },
                                        ),
                                      ),
                                      const SizedBox(width: 8),
                                      IconButton(
                                        style: IconButton.styleFrom(backgroundColor: const Color(0xFF0F172A)),
                                        icon: const Icon(Icons.cloud_upload_rounded, color: ManagerTheme.brandEmerald, size: 20),
                                        tooltip: 'Sync Deliverable to App CMS',
                                        onPressed: () async {
                                          final content = await _api.fetchPaperclipAttachment(attachmentId!);
                                          if (content != null) {
                                            final res = await _api.syncDeliverableToApp(
                                              title: issue.title,
                                              content: content,
                                              type: 'blog',
                                            );
                                            if (mounted) {
                                              ScaffoldMessenger.of(context).showSnackBar(
                                                SnackBar(
                                                  content: Text(res?['message'] ?? 'Deliverable synced to CMS!'),
                                                  backgroundColor: ManagerTheme.brandEmerald,
                                                ),
                                              );
                                            }
                                          }
                                        },
                                      ),
                                    ],
                                  ),
                                ],
                              ],
                            ),
                          );
                        }),
                    ],
                  );
                },
              ),
            );
          },
        );
      },
    );
  }

  // ==========================================
  // 📄 SACRED DELIVERABLE MARKDOWN VIEWER
  // ==========================================
  void _showDeliverableModal(String title, String content) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return Container(
          height: MediaQuery.of(context).size.height * 0.9,
          decoration: const BoxDecoration(
            color: Color(0xFF0F172A),
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: Column(
            children: [
              Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Text(
                        title,
                        style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.white, fontSize: 16),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.copy_rounded, color: ManagerTheme.brandGold),
                      tooltip: 'Copy Markdown',
                      onPressed: () {
                        Clipboard.setData(ClipboardData(text: content));
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Deliverable copied to clipboard!')),
                        );
                      },
                    ),
                    IconButton(
                      icon: const Icon(Icons.close_rounded, color: Colors.white70),
                      onPressed: () => Navigator.pop(ctx),
                    ),
                  ],
                ),
              ),
              const Divider(color: Color(0xFF334155), height: 1),
              Expanded(
                child: Markdown(
                  data: content,
                  styleSheet: MarkdownStyleSheet.fromTheme(Theme.of(context)).copyWith(
                    p: const TextStyle(color: Colors.white70, fontSize: 13, height: 1.5),
                    h1: const TextStyle(color: ManagerTheme.brandGold, fontWeight: FontWeight.bold, fontSize: 20),
                    h2: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16),
                    h3: const TextStyle(color: ManagerTheme.brandEmerald, fontWeight: FontWeight.bold, fontSize: 14),
                    code: const TextStyle(color: ManagerTheme.brandGold, backgroundColor: Color(0xFF1E293B)),
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  // ==========================================
  // 💬 1-ON-1 DIRECT AGENT CHAT MODAL
  // ==========================================
  void _openAgentChatModal(PaperclipAgentModel agent) {
    final List<Map<String, String>> messages = [];
    final TextEditingController chatCtrl = TextEditingController();
    bool isThinking = false;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (sheetContext, setModalState) {
            return Padding(
              padding: EdgeInsets.only(bottom: MediaQuery.of(sheetContext).viewInsets.bottom),
              child: Container(
                height: MediaQuery.of(sheetContext).size.height * 0.85,
                decoration: const BoxDecoration(
                  color: Color(0xFF0F172A),
                  borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
                ),
                child: Column(
                  children: [
                    // Header
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                      decoration: const BoxDecoration(
                        border: Border(bottom: BorderSide(color: Color(0xFF334155))),
                      ),
                      child: Row(
                        children: [
                          CircleAvatar(
                            backgroundColor: ManagerTheme.brandGold.withOpacity(0.2),
                            child: Text(agent.name[0], style: const TextStyle(color: ManagerTheme.brandGold, fontWeight: FontWeight.bold)),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(agent.name, style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.white, fontSize: 15)),
                                Row(
                                  children: [
                                    Container(
                                      width: 8,
                                      height: 8,
                                      decoration: const BoxDecoration(color: ManagerTheme.brandEmerald, shape: BoxShape.circle),
                                    ),
                                    const SizedBox(width: 6),
                                    Text(
                                      '${agent.role.toUpperCase()} · Live OmniRoute AI Bridge',
                                      style: const TextStyle(color: Colors.white54, fontSize: 11),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                          IconButton(
                            icon: const Icon(Icons.close_rounded, color: Colors.white70),
                            onPressed: () => Navigator.pop(ctx),
                          ),
                        ],
                      ),
                    ),

                    // Chat messages
                    Expanded(
                      child: messages.isEmpty
                          ? Center(
                              child: Padding(
                                padding: const EdgeInsets.all(24),
                                child: Column(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    const Icon(Icons.psychology_rounded, size: 48, color: ManagerTheme.brandGold),
                                    const SizedBox(height: 12),
                                    Text('Ask ${agent.name}', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
                                    const SizedBox(height: 6),
                                    Text(
                                      'Direct consultation with autonomous agent persona for strategic execution, content scripts, and campaign telemetry.',
                                      textAlign: TextAlign.center,
                                      style: TextStyle(color: Colors.white.withOpacity(0.6), fontSize: 12),
                                    ),
                                  ],
                                ),
                              ),
                            )
                          : ListView.builder(
                              padding: const EdgeInsets.all(16),
                              itemCount: messages.length,
                              itemBuilder: (_, idx) {
                                final m = messages[idx];
                                final isUser = m['role'] == 'user';
                                return Align(
                                  alignment: isUser ? Alignment.centerRight : Alignment.centerLeft,
                                  child: Container(
                                    margin: const EdgeInsets.only(bottom: 10),
                                    constraints: BoxConstraints(maxWidth: MediaQuery.of(sheetContext).size.width * 0.8),
                                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                                    decoration: BoxDecoration(
                                      color: isUser ? const Color(0xFF3B82F6) : const Color(0xFF1E293B),
                                      borderRadius: BorderRadius.circular(14),
                                      border: isUser ? null : Border.all(color: ManagerTheme.brandGold.withOpacity(0.3)),
                                    ),
                                    child: Text(
                                      m['content'] ?? '',
                                      style: const TextStyle(color: Colors.white, fontSize: 13, height: 1.4),
                                    ),
                                  ),
                                );
                              },
                            ),
                    ),

                    if (isThinking)
                      const Padding(
                        padding: EdgeInsets.symmetric(vertical: 8),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: ManagerTheme.brandGold)),
                            SizedBox(width: 10),
                            Text('Agent is contemplating...', style: TextStyle(color: Colors.white54, fontSize: 12)),
                          ],
                        ),
                      ),

                    // Input Bar
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: const BoxDecoration(
                        color: Color(0xFF1E293B),
                        border: Border(top: BorderSide(color: Color(0xFF334155))),
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: TextField(
                              controller: chatCtrl,
                              style: const TextStyle(color: Colors.white, fontSize: 13),
                              decoration: InputDecoration(
                                hintText: 'Ask ${agent.name}...',
                                hintStyle: const TextStyle(color: Colors.white38),
                                filled: true,
                                fillColor: const Color(0xFF0F172A),
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(20), borderSide: BorderSide.none),
                                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                              ),
                              onSubmitted: (val) {
                                if (val.trim().isNotEmpty && !isThinking) {
                                  _sendMessage(sheetContext, setModalState, agent, chatCtrl, messages, () => isThinking, (v) => isThinking = v);
                                }
                              },
                            ),
                          ),
                          const SizedBox(width: 8),
                          IconButton(
                            style: IconButton.styleFrom(backgroundColor: ManagerTheme.brandGold),
                            icon: const Icon(Icons.send_rounded, color: Colors.black, size: 20),
                            onPressed: () {
                              if (chatCtrl.text.trim().isNotEmpty && !isThinking) {
                                _sendMessage(sheetContext, setModalState, agent, chatCtrl, messages, () => isThinking, (v) => isThinking = v);
                              }
                            },
                          ),
                        ],
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

  Future<void> _sendMessage(
    BuildContext ctx,
    StateSetter setModalState,
    PaperclipAgentModel agent,
    TextEditingController chatCtrl,
    List<Map<String, String>> messages,
    bool Function() getThinking,
    void Function(bool) setThinking,
  ) async {
    final text = chatCtrl.text.trim();
    if (text.isEmpty) return;

    chatCtrl.clear();
    setModalState(() {
      messages.add({'role': 'user', 'content': text});
      setThinking(true);
    });

    final res = await _api.sendAgentChatMessage(
      agentId: agent.id,
      agentName: agent.name,
      message: text,
      history: messages,
    );

    setModalState(() {
      setThinking(false);
      if (res != null && res['ok'] == true && res['reply'] != null) {
        messages.add({'role': 'assistant', 'content': res['reply'] as String});
      } else {
        messages.add({'role': 'assistant', 'content': 'Error communicating with agent: ${res?['error'] ?? "Unknown network error"}'});
      }
    });
  }
}
