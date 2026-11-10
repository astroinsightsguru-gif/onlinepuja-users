import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../models/devotee_lead_model.dart';
import '../../../providers/call_provider.dart';
import '../../../providers/devotee_lead_provider.dart';
import '../../../theme/manager_theme.dart';

class LeadsPipelineScreen extends StatefulWidget {
  const LeadsPipelineScreen({super.key});

  @override
  State<LeadsPipelineScreen> createState() => _LeadsPipelineScreenState();
}

class _LeadsPipelineScreenState extends State<LeadsPipelineScreen> {
  final List<Map<String, String>> _stages = [
    {'id': 'all', 'label': 'All Devotees'},
    {'id': 'inquiry', 'label': 'New Inquiries'},
    {'id': 'consultation', 'label': 'Pandit Call'},
    {'id': 'proposal_sent', 'label': 'Quote Sent'},
    {'id': 'sankalp_booked', 'label': 'Sankalp Booked'},
  ];

  void _showAddNoteDialog(BuildContext context, DevoteeLeadModel lead, DevoteeLeadProvider provider) {
    final noteCtrl = TextEditingController();
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: const Color(0xFF131D3B),
        title: Text('Add Note for ${lead.name}', style: const TextStyle(color: Colors.white, fontSize: 15)),
        content: TextField(
          controller: noteCtrl,
          maxLines: 3,
          style: const TextStyle(color: Colors.white, fontSize: 13),
          decoration: const InputDecoration(
            hintText: 'e.g. Devotee requested 5 Brahmins for Rudrabhishek...',
            hintStyle: TextStyle(color: Colors.white38, fontSize: 12),
          ),
        ),
        actions: [
          TextButton(
            child: const Text('Cancel', style: TextStyle(color: Colors.white60)),
            onPressed: () => Navigator.of(ctx).pop(),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: ManagerTheme.brandGold, foregroundColor: Colors.black),
            child: const Text('Save Note'),
            onPressed: () {
              if (noteCtrl.text.trim().isNotEmpty) {
                provider.addLeadNote(lead.id, noteCtrl.text.trim());
              }
              Navigator.of(ctx).pop();
            },
          ),
        ],
      ),
    );
  }

  void _showAdvanceStageDialog(BuildContext context, DevoteeLeadModel lead, DevoteeLeadProvider provider) {
    showModalBottomSheet(
      context: context,
      backgroundColor: const Color(0xFF131D3B),
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) => Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Update Pipeline Stage for ${lead.name}', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 15)),
            const SizedBox(height: 14),
            ...[
              {'stage': 'inquiry', 'label': '1. New Inquiry (Inbound Lead)'},
              {'stage': 'consultation', 'label': '2. Pandit Consultation Scheduled'},
              {'stage': 'proposal_sent', 'label': '3. Custom Dakshina Proposal Sent'},
              {'stage': 'sankalp_booked', 'label': '4. Sankalp Confirmed & Paid'},
              {'stage': 'completed', 'label': '5. Puja Performed & Prasad Sent'},
            ].map((s) => ListTile(
              leading: Icon(
                lead.stage == s['stage'] ? Icons.radio_button_checked : Icons.radio_button_off,
                color: lead.stage == s['stage'] ? ManagerTheme.brandGold : Colors.white38,
              ),
              title: Text(s['label']!, style: const TextStyle(color: Colors.white, fontSize: 13)),
              onTap: () {
                provider.updateLeadStage(lead.id, s['stage']!);
                Navigator.of(ctx).pop();
              },
            )),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final leadProvider = Provider.of<DevoteeLeadProvider>(context);
    final callProvider = Provider.of<CallProvider>(context);

    return Scaffold(
      backgroundColor: ManagerTheme.backgroundDark,
      appBar: AppBar(
        backgroundColor: ManagerTheme.surfaceDark,
        elevation: 0,
        title: const Row(
          children: [
            Text('🙏', style: TextStyle(fontSize: 18)),
            SizedBox(width: 8),
            Text('Devotee Life-Cycle CRM', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: ManagerTheme.brandGold),
            onPressed: () => leadProvider.fetchLeads(),
          ),
        ],
      ),
      body: Column(
        children: [
          // Revenue & Metrics Strip
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            color: const Color(0xFF0B0F19),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _metricItem('Total Devotees', '${leadProvider.leads.length}', Colors.white),
                _metricItem('Inquiries', '${leadProvider.totalInquiries}', Colors.amber),
                _metricItem('Consultations', '${leadProvider.totalConsultations}', const Color(0xFF60A5FA)),
                _metricItem('Booked', '₹${leadProvider.totalBookedValue.toStringAsFixed(0)}', ManagerTheme.brandGold),
              ],
            ),
          ),

          // Stage Filter Pills
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            child: Row(
              children: _stages.map((st) {
                final isSelected = leadProvider.selectedStage == st['id'];
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: FilterChip(
                    label: Text(st['label']!),
                    selected: isSelected,
                    selectedColor: ManagerTheme.brandGold,
                    backgroundColor: ManagerTheme.surfaceDark,
                    labelStyle: TextStyle(
                      color: isSelected ? Colors.black : Colors.white70,
                      fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                      fontSize: 11,
                    ),
                    onSelected: (_) => leadProvider.setFilterStage(st['id']!),
                  ),
                );
              }).toList(),
            ),
          ),

          // Devotees List
          Expanded(
            child: leadProvider.isLoading
                ? const Center(child: CircularProgressIndicator(color: ManagerTheme.brandGold))
                : leadProvider.filteredLeads.isEmpty
                    ? const Center(child: Text('No devotees in this stage.', style: TextStyle(color: Colors.white54)))
                    : ListView.separated(
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                        itemCount: leadProvider.filteredLeads.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 10),
                        itemBuilder: (ctx, i) {
                          final l = leadProvider.filteredLeads[i];
                          return _buildDevoteeCard(context, l, callProvider, leadProvider);
                        },
                      ),
          ),
        ],
      ),
    );
  }

  Widget _metricItem(String label, String value, Color color) {
    return Column(
      children: [
        Text(value, style: TextStyle(color: color, fontWeight: FontWeight.bold, fontSize: 15, fontFamily: 'monospace')),
        Text(label, style: const TextStyle(color: Colors.white38, fontSize: 9)),
      ],
    );
  }

  Widget _buildDevoteeCard(
    BuildContext context,
    DevoteeLeadModel lead,
    CallProvider callProvider,
    DevoteeLeadProvider leadProvider,
  ) {
    final isBooked = lead.stage == 'sankalp_booked';

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: ManagerTheme.surfaceDark,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: isBooked ? const Color(0xFF25D366).withValues(alpha: 0.3) : Colors.white10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 20,
                backgroundColor: ManagerTheme.brandGold.withValues(alpha: 0.15),
                child: Text(lead.initials, style: const TextStyle(color: ManagerTheme.brandGold, fontWeight: FontWeight.bold, fontSize: 12)),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Text(lead.name, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14)),
                        const SizedBox(width: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(color: Colors.white10, borderRadius: BorderRadius.circular(4)),
                          child: Text('Gotra: ${lead.gotra}', style: const TextStyle(color: Colors.white70, fontSize: 9)),
                        ),
                      ],
                    ),
                    const SizedBox(height: 2),
                    Text(
                      '${lead.city} · Source: ${lead.source.replaceAll("_", " ").toUpperCase()}',
                      style: const TextStyle(color: Colors.white38, fontSize: 10),
                    ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: const Color(0xFF25D366).withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text('Score ${lead.score}', style: const TextStyle(color: Color(0xFF25D366), fontWeight: FontWeight.bold, fontSize: 10)),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
            decoration: BoxDecoration(
              color: const Color(0xFF0B0F19),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Text(
                    '🙏 ${lead.pujaInterest}',
                    style: const TextStyle(color: ManagerTheme.brandGold, fontSize: 11, fontWeight: FontWeight.w600),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                Text(
                  '₹${lead.dakshinaValue.toStringAsFixed(0)}',
                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12, fontFamily: 'monospace'),
                ),
              ],
            ),
          ),
          if (lead.notes.isNotEmpty) ...[
            const SizedBox(height: 8),
            Text('• ${lead.notes.first}', style: const TextStyle(color: Colors.white60, fontSize: 10, fontStyle: FontStyle.italic)),
          ],
          const Divider(color: Colors.white10, height: 20),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              OutlinedButton.icon(
                style: OutlinedButton.styleFrom(
                  foregroundColor: Colors.white70,
                  side: const BorderSide(color: Colors.white24),
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                ),
                icon: const Icon(Icons.sync_alt_rounded, size: 14),
                label: Text(lead.stage.replaceAll('_', ' ').toUpperCase(), style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold)),
                onPressed: () => _showAdvanceStageDialog(context, lead, leadProvider),
              ),
              Row(
                children: [
                  IconButton(
                    icon: const Icon(Icons.note_add_outlined, color: Colors.white60, size: 18),
                    tooltip: 'Add Note',
                    onPressed: () => _showAddNoteDialog(context, lead, leadProvider),
                  ),
                  IconButton(
                    icon: const Icon(Icons.chat_bubble_outline, color: Color(0xFF25D366), size: 18),
                    tooltip: 'WhatsApp',
                    onPressed: () {
                      final uri = Uri.parse('whatsapp://send?phone=91${lead.phone}&text=Pranam%20${Uri.encodeComponent(lead.name)}!');
                      launchUrl(uri, mode: LaunchMode.externalApplication);
                    },
                  ),
                  IconButton(
                    icon: const Icon(Icons.phone, color: Color(0xFF25D366), size: 18),
                    tooltip: 'Call Devotee',
                    onPressed: () {
                      callProvider.startCall(
                        phoneNumber: lead.phone,
                        customerName: lead.name,
                        pujaInterest: lead.pujaInterest,
                        gotra: lead.gotra,
                      );
                    },
                  ),
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }
}
