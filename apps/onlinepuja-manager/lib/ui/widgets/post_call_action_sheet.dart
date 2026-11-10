import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../models/call_log_model.dart';
import '../../models/puja_quote_model.dart';
import '../../providers/call_provider.dart';
import '../../providers/devotee_lead_provider.dart';
import '../../providers/quotation_provider.dart';
import '../../theme/manager_theme.dart';

class PostCallActionSheet extends StatefulWidget {
  final CallLogModel call;

  const PostCallActionSheet({super.key, required this.call});

  static void show(BuildContext context, CallLogModel call) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => PostCallActionSheet(call: call),
    );
  }

  @override
  State<PostCallActionSheet> createState() => _PostCallActionSheetState();
}

class _PostCallActionSheetState extends State<PostCallActionSheet> {
  final TextEditingController _notesController = TextEditingController();
  String _selectedDisposition = 'follow_up';
  bool _isSaving = false;

  final List<Map<String, String>> _dispositions = [
    {'id': 'sankalp_booked', 'label': '🕉️ Sankalp Booked', 'stage': 'sankalp_booked'},
    {'id': 'follow_up', 'label': '📞 Callback Needed', 'stage': 'consultation'},
    {'id': 'proposal_needed', 'label': '📜 Send Custom Quote', 'stage': 'proposal_sent'},
    {'id': 'not_interested', 'label': '❌ Not Interested', 'stage': 'lost'},
  ];

  @override
  void dispose() {
    _notesController.dispose();
    super.dispose();
  }

  void _saveAndProceed() async {
    setState(() => _isSaving = true);

    final leadProvider = Provider.of<DevoteeLeadProvider>(context, listen: false);
    final callProvider = Provider.of<CallProvider>(context, listen: false);
    final quoteProvider = Provider.of<QuotationProvider>(context, listen: false);

    final note = _notesController.text.trim();

    // Auto-update CRM Devotee Pipeline
    final matchingLeads = leadProvider.leads.where((l) => l.phone.contains(widget.call.phone) || widget.call.phone.contains(l.phone));
    if (matchingLeads.isNotEmpty) {
      final targetLead = matchingLeads.first;
      final selectedMap = _dispositions.firstWhere((d) => d['id'] == _selectedDisposition, orElse: () => _dispositions.first);
      leadProvider.updateLeadStage(targetLead.id, selectedMap['stage']!);
      if (note.isNotEmpty) {
        leadProvider.addLeadNote(targetLead.id, note);
      }
    }

    // Check if quote should be triggered
    if (_selectedDisposition == 'proposal_needed' || _selectedDisposition == 'sankalp_booked') {
      final newQuote = PujaQuoteModel(
        id: 'PQ-${DateTime.now().millisecondsSinceEpoch.toString().substring(7)}',
        devoteeName: widget.call.customerName,
        devoteePhone: widget.call.phone,
        gotra: widget.call.gotra ?? 'Kashyap',
        templeLocation: 'Kashi Vishwanath, Varanasi',
        pujaTitle: widget.call.pujaInterest ?? 'Vedic Puja & Rudrabhishek',
        items: [
          PujaQuoteItem(description: 'Vedic Pandits Honorarium (3 Acharyas)', amount: 3300),
          PujaQuoteItem(description: 'Panchamrit, Bilva Patra & Havan Samagri', amount: 1500),
          PujaQuoteItem(description: 'Speed Post Ganga Jal & Sanctified Prasad', amount: 300),
        ],
        discount: 0,
        notes: note.isNotEmpty ? note : 'Includes live video darshan on WhatsApp.',
        createdAt: DateTime.now(),
        status: 'sent',
      );

      quoteProvider.addQuote(newQuote);
      await quoteProvider.sendProposalViaWhatsApp(newQuote);
    }

    // Clear completed call
    callProvider.clearLastCompletedCall();

    if (mounted) {
      Navigator.of(context).pop();
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('✅ Devotee disposition recorded & WhatsApp synced!'),
          backgroundColor: Color(0xFF059669),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom + 20,
        top: 20,
        left: 20,
        right: 20,
      ),
      decoration: const BoxDecoration(
        color: Color(0xFF131D3B),
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Center(
            child: Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: Colors.white24,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: ManagerTheme.brandGold.withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                ),
                child: const Center(
                  child: Text('ॐ', style: TextStyle(color: ManagerTheme.brandGold, fontSize: 22, fontWeight: FontWeight.bold)),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Call Ended: ${widget.call.customerName}',
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16),
                    ),
                    Text(
                      '${widget.call.phone} · Duration: ${widget.call.formattedDuration}',
                      style: const TextStyle(color: Colors.white70, fontSize: 12),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          const Text(
            'Select Devotee Outcome / Next Step:',
            style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 13),
          ),
          const SizedBox(height: 10),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: _dispositions.map((disp) {
              final isSelected = _selectedDisposition == disp['id'];
              return ChoiceChip(
                label: Text(disp['label']!),
                selected: isSelected,
                selectedColor: ManagerTheme.brandGold,
                backgroundColor: const Color(0xFF0B0F19),
                labelStyle: TextStyle(
                  color: isSelected ? Colors.black : Colors.white70,
                  fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                  fontSize: 12,
                ),
                onSelected: (val) {
                  if (val) setState(() => _selectedDisposition = disp['id']!);
                },
              );
            }).toList(),
          ),
          const SizedBox(height: 16),
          const Text(
            'Consultation Notes / Puja Preferences:',
            style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 13),
          ),
          const SizedBox(height: 8),
          TextField(
            controller: _notesController,
            maxLines: 3,
            style: const TextStyle(color: Colors.white, fontSize: 13),
            decoration: InputDecoration(
              hintText: 'e.g. Devotee requested 5 Pandits for Rudrabhishek, son suffering from Rahu Dosh...',
              hintStyle: const TextStyle(color: Colors.white38, fontSize: 12),
              filled: true,
              fillColor: const Color(0xFF0B0F19),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
            ),
          ),
          const SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            height: 48,
            child: ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: ManagerTheme.brandGold,
                foregroundColor: Colors.black,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              icon: _isSaving
                  ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.black))
                  : const Icon(Icons.send_rounded, size: 18),
              label: Text(
                _selectedDisposition == 'proposal_needed' || _selectedDisposition == 'sankalp_booked'
                    ? 'Save & Send WhatsApp Proposal'
                    : 'Save Devotee Disposition',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
              ),
              onPressed: _isSaving ? null : _saveAndProceed,
            ),
          ),
        ],
      ),
    );
  }
}
