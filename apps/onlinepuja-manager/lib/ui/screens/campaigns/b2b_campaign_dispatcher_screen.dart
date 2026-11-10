import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../providers/call_provider.dart';
import '../../../services/manager_api_service.dart';
import '../../../theme/manager_theme.dart';

class B2bCampaignDispatcherScreen extends StatefulWidget {
  const B2bCampaignDispatcherScreen({super.key});

  @override
  State<B2bCampaignDispatcherScreen> createState() => _B2bCampaignDispatcherScreenState();
}

class _B2bCampaignDispatcherScreenState extends State<B2bCampaignDispatcherScreen> {
  final ManagerApiService _api = ManagerApiService();

  bool _isLoading = true;
  int _totalLeads = 1350;
  int _travelAgencies = 1322;
  int _contactedCount = 16;
  int _uncontactedCount = 1334;
  String _whatsappStatus = 'close';
  String? _qrBase64;

  List<Map<String, dynamic>> _templates = [];
  int _selectedTemplateIdx = 0;
  List<Map<String, dynamic>> _runnerQueue = [];
  int _currentRunnerIdx = 0;

  bool _isMarkingContacted = false;

  @override
  void initState() {
    super.initState();
    _loadCockpitData();
  }

  Future<void> _loadCockpitData() async {
    setState(() => _isLoading = true);
    final data = await _api.fetchB2bCampaignCockpit();
    if (mounted) {
      if (data != null) {
        setState(() {
          _totalLeads = (data['total_leads'] as num?)?.toInt() ?? _totalLeads;
          _travelAgencies = (data['travel_agencies'] as num?)?.toInt() ?? _travelAgencies;
          _contactedCount = (data['contacted_count'] as num?)?.toInt() ?? _contactedCount;
          _uncontactedCount = (data['uncontacted_count'] as num?)?.toInt() ?? _uncontactedCount;
          _whatsappStatus = data['whatsapp_status']?.toString() ?? 'close';
          _qrBase64 = data['qr_base64']?.toString();

          if (data['templates'] is List) {
            _templates = List<Map<String, dynamic>>.from(data['templates']);
          }

          if (data['runner_queue'] is List) {
            _runnerQueue = List<Map<String, dynamic>>.from(data['runner_queue']);
            _currentRunnerIdx = 0;
          }

          _isLoading = false;
        });
      } else {
        setState(() => _isLoading = false);
      }
    }
  }

  Map<String, dynamic>? get _currentLead {
    if (_runnerQueue.isEmpty || _currentRunnerIdx >= _runnerQueue.length) {
      return null;
    }
    return _runnerQueue[_currentRunnerIdx];
  }

  Future<void> _sendWhatsAppAndNext(Map<String, dynamic> lead) async {
    final urlStr = lead['whatsapp_url']?.toString() ?? '';
    final leadId = (lead['id'] as num?)?.toInt() ?? 0;

    if (urlStr.isNotEmpty) {
      final uri = Uri.parse(urlStr);
      try {
        if (await canLaunchUrl(uri)) {
          await launchUrl(uri, mode: LaunchMode.externalApplication);
        }
      } catch (_) {}
    }

    // Call API to mark as contacted and fetch next lead
    setState(() => _isMarkingContacted = true);
    final currentTplBody = _templates.isNotEmpty ? _templates[_selectedTemplateIdx]['body']?.toString() : null;
    final res = await _api.markB2bLeadContacted(
      id: leadId,
      templateBody: currentTplBody,
    );

    if (mounted) {
      setState(() {
        _isMarkingContacted = false;
        _contactedCount++;
        if (_uncontactedCount > 0) _uncontactedCount--;

        if (res != null && res['next_lead'] != null) {
          final nextLead = Map<String, dynamic>.from(res['next_lead']);
          _runnerQueue.add(nextLead);
        }
        _currentRunnerIdx++;
      });

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('✓ ${lead['business_name']} marked as Contacted!'),
          duration: const Duration(seconds: 2),
          backgroundColor: ManagerTheme.brandEmerald,
        ),
      );
    }
  }

  void _skipLead() {
    if (_currentRunnerIdx < _runnerQueue.length - 1) {
      setState(() => _currentRunnerIdx++);
    } else {
      _loadCockpitData();
    }
  }

  void _callCurrentLead(Map<String, dynamic> lead) {
    final callProvider = Provider.of<CallProvider>(context, listen: false);
    callProvider.startCall(
      phoneNumber: lead['phone']?.toString() ?? '',
      customerName: lead['business_name']?.toString() ?? 'Travel Agency Partner',
      pujaInterest: 'Dev Deepawali 2026 Commercial Tie-Up',
      gotra: 'Commercial Partner',
    );
  }

  void _showQrDialog() {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: ManagerTheme.surfaceDark,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Row(
          children: const [
            Icon(Icons.qr_code_2_rounded, color: ManagerTheme.brandGold),
            SizedBox(width: 10),
            Text('Scan WhatsApp QR Code', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text(
              'Open WhatsApp on your phone ➔ Linked Devices ➔ Link a Device ➔ Scan the QR below:',
              style: TextStyle(fontSize: 12, color: Colors.white70),
            ),
            const SizedBox(height: 16),
            if (_qrBase64 != null && _qrBase64!.isNotEmpty)
              ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: Image.memory(
                  base64Decode(_qrBase64!.replaceFirst(RegExp(r'^data:image\/[a-z]+;base64,'), '')),
                  width: 230,
                  height: 230,
                  fit: BoxFit.cover,
                ),
              )
            else
              Container(
                width: 200,
                height: 200,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: Colors.white10,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Text('Fetching fresh QR code...', style: TextStyle(fontSize: 12, color: Colors.white54)),
              ),
            const SizedBox(height: 14),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.shield_outlined, size: 14, color: ManagerTheme.brandEmerald),
                const SizedBox(width: 6),
                const Text('Evolution API v2 Socket Encrypted', style: TextStyle(fontSize: 11, color: Colors.white54)),
              ],
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Close')),
          ElevatedButton.icon(
            style: ElevatedButton.styleFrom(
              backgroundColor: ManagerTheme.brandGold,
              foregroundColor: Colors.black,
            ),
            icon: const Icon(Icons.refresh_rounded, size: 16),
            label: const Text('Refresh QR', style: TextStyle(fontWeight: FontWeight.bold)),
            onPressed: () async {
              final qrData = await _api.fetchWhatsAppQr();
              if (ctx.mounted && qrData != null) {
                setState(() {
                  _qrBase64 = qrData['base64']?.toString();
                  _whatsappStatus = qrData['state']?.toString() ?? _whatsappStatus;
                });
                Navigator.pop(ctx);
                _showQrDialog();
              }
            },
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final progressPct = _totalLeads > 0 ? (_contactedCount / _totalLeads).clamp(0.0, 1.0) : 0.0;
    final lead = _currentLead;

    return Scaffold(
      backgroundColor: ManagerTheme.bgDark,
      appBar: AppBar(
        title: Row(
          children: const [
            Text('🚀', style: TextStyle(fontSize: 20)),
            SizedBox(width: 8),
            Text('Anti-Ban WhatsApp Dispatcher', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          ],
        ),
        backgroundColor: ManagerTheme.surfaceDark,
        elevation: 0,
        actions: [
          // WhatsApp status pill
          GestureDetector(
            onTap: _showQrDialog,
            child: Container(
              margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: _whatsappStatus == 'open'
                    ? ManagerTheme.brandEmerald.withOpacity(0.2)
                    : Colors.amber.withOpacity(0.2),
                border: Border.all(
                  color: _whatsappStatus == 'open' ? ManagerTheme.brandEmerald : Colors.amber,
                  width: 1.2,
                ),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(
                    _whatsappStatus == 'open' ? Icons.check_circle_rounded : Icons.qr_code_scanner_rounded,
                    size: 14,
                    color: _whatsappStatus == 'open' ? ManagerTheme.brandEmerald : Colors.amber,
                  ),
                  const SizedBox(width: 6),
                  Text(
                    _whatsappStatus == 'open' ? 'WhatsApp Linked' : 'Scan QR',
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.bold,
                      color: _whatsappStatus == 'open' ? ManagerTheme.brandEmerald : Colors.amber,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: ManagerTheme.brandGold))
          : RefreshIndicator(
              onRefresh: _loadCockpitData,
              color: ManagerTheme.brandGold,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // 1. Campaign Metrics & Progress Bar
                    _buildCampaignProgressCard(progressPct),
                    const SizedBox(height: 16),

                    // 2. Template Selector with Spintax Preview
                    _buildTemplateSelector(),
                    const SizedBox(height: 16),

                    // 3. Speed-Dial Fast Runner Card
                    _buildSpeedDialRunnerCard(lead),
                    const SizedBox(height: 24),

                    // 4. Anti-Ban Security Architecture Guidelines
                    _buildSecurityGuidelineCard(),
                    const SizedBox(height: 30),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildCampaignProgressCard(double progressPct) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: ManagerTheme.surfaceDark,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('OUTREACH TELEMETRY', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.white54, letterSpacing: 1.2)),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      Text('$_contactedCount', style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w900, color: ManagerTheme.brandEmerald)),
                      const Text(' / ', style: TextStyle(fontSize: 18, color: Colors.white38)),
                      Text('$_totalLeads Leads', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white70)),
                    ],
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: ManagerTheme.brandGold.withOpacity(0.15),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: ManagerTheme.brandGold.withOpacity(0.4)),
                ),
                child: Text(
                  '${(progressPct * 100).toStringAsFixed(1)}% REACHED',
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: ManagerTheme.brandGold),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: LinearProgressIndicator(
              value: progressPct,
              minHeight: 8,
              backgroundColor: Colors.white10,
              valueColor: const AlwaysStoppedAnimation<Color>(ManagerTheme.brandEmerald),
            ),
          ),
          const SizedBox(height: 14),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              _buildMiniStat('✈️ Travel Agencies', '$_travelAgencies'),
              _buildMiniStat('⏳ Uncontacted', '$_uncontactedCount'),
              _buildMiniStat('🛡️ Anti-Ban Jitter', '15s - 35s'),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildMiniStat(String label, String value) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(fontSize: 11, color: Colors.white54)),
        const SizedBox(height: 2),
        Text(value, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.white)),
      ],
    );
  }

  Widget _buildTemplateSelector() {
    if (_templates.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('CAMPAIGN PITCH TEMPLATE', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white54, letterSpacing: 1.1)),
        const SizedBox(height: 8),
        SizedBox(
          height: 40,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            itemCount: _templates.length,
            separatorBuilder: (_, __) => const SizedBox(width: 8),
            itemBuilder: (ctx, i) {
              final tpl = _templates[i];
              final isSelected = _selectedTemplateIdx == i;
              return ChoiceChip(
                label: Text(tpl['title'] ?? 'Template #${i + 1}', style: TextStyle(fontSize: 12, fontWeight: isSelected ? FontWeight.bold : FontWeight.normal)),
                selected: isSelected,
                selectedColor: ManagerTheme.brandGold,
                backgroundColor: ManagerTheme.surfaceDark,
                labelStyle: TextStyle(color: isSelected ? Colors.black : Colors.white70),
                onSelected: (val) {
                  if (val) setState(() => _selectedTemplateIdx = i);
                },
              );
            },
          ),
        ),
      ],
    );
  }

  Widget _buildSpeedDialRunnerCard(Map<String, dynamic>? lead) {
    if (lead == null) {
      return Container(
        padding: const EdgeInsets.all(24),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: ManagerTheme.surfaceDark,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Colors.white10),
        ),
        child: Column(
          children: const [
            Icon(Icons.celebration_rounded, color: ManagerTheme.brandGold, size: 48),
            SizedBox(height: 12),
            Text('All Targeted Leads Contacted! 🎉', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            SizedBox(height: 6),
            Text('No pending leads in the current runner batch.', style: TextStyle(fontSize: 12, color: Colors.white54)),
          ],
        ),
      );
    }

    final businessName = lead['business_name'] ?? 'Travel Agency Partner';
    final contactPerson = lead['contact_person'] ?? '';
    final city = lead['city'] ?? 'Varanasi';
    final phone = lead['phone'] ?? '';
    final renderedMsg = lead['rendered_message'] ?? '';

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: ManagerTheme.surfaceDark,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: ManagerTheme.brandGold.withOpacity(0.3), width: 1.2),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.35),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header info
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: Colors.white10,
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            'LEAD #${_currentRunnerIdx + 1}',
                            style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: ManagerTheme.brandGold),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          '📍 $city',
                          style: const TextStyle(fontSize: 12, color: Colors.white70),
                        ),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Text(
                      businessName,
                      style: const TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: Colors.white),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    if (contactPerson.isNotEmpty)
                      Text(
                        '👤 $contactPerson • 📞 $phone',
                        style: const TextStyle(fontSize: 12, color: Colors.white60),
                      ),
                  ],
                ),
              ),
              IconButton(
                icon: const Icon(Icons.phone_in_talk_rounded, color: ManagerTheme.brandGold),
                tooltip: 'Direct Telephony Consultation',
                onPressed: () => _callCurrentLead(lead),
              ),
            ],
          ),
          const Divider(color: Colors.white12, height: 24),

          // Message Preview Container
          const Text('RESOLVED SPINTAX COPY (Anti-Ban Randomized)', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.white54)),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.black45,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: Colors.white10),
            ),
            child: Text(
              renderedMsg,
              style: const TextStyle(fontSize: 12, height: 1.45, color: Colors.white70, fontFamily: 'monospace'),
            ),
          ),
          const SizedBox(height: 16),

          // Action Buttons
          Row(
            children: [
              OutlinedButton.icon(
                style: OutlinedButton.styleFrom(
                  foregroundColor: Colors.white60,
                  side: const BorderSide(color: Colors.white24),
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
                icon: const Icon(Icons.skip_next_rounded, size: 16),
                label: const Text('Skip', style: TextStyle(fontSize: 12)),
                onPressed: _skipLead,
              ),
              const SizedBox(width: 10),
              Expanded(
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF25D366),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    elevation: 4,
                  ),
                  icon: _isMarkingContacted
                      ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                      : const Icon(Icons.send_rounded, size: 18),
                  label: Text(
                    _isMarkingContacted ? 'Updating CRM...' : '🚀 Send WhatsApp & Next',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                  ),
                  onPressed: _isMarkingContacted ? null : () => _sendWhatsAppAndNext(lead),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSecurityGuidelineCard() {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.blueGrey.withOpacity(0.12),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.blueGrey.withOpacity(0.3)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: const [
          Icon(Icons.verified_user_rounded, color: ManagerTheme.brandGold, size: 20),
          SizedBox(width: 10),
          Expanded(
            child: Text(
              'Meta Anti-Ban Guard Active: Outgoing WhatsApp payloads rotate greetings & closures via Spintax. Each message carries a distinct hash preventing automated spam heuristics.',
              style: TextStyle(fontSize: 11, color: Colors.white60, height: 1.4),
            ),
          ),
        ],
      ),
    );
  }
}
