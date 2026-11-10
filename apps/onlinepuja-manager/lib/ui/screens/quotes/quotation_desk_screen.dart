import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../models/puja_quote_model.dart';
import '../../../providers/quotation_provider.dart';
import '../../../theme/manager_theme.dart';

class QuotationDeskScreen extends StatefulWidget {
  const QuotationDeskScreen({super.key});

  @override
  State<QuotationDeskScreen> createState() => _QuotationDeskScreenState();
}

class _QuotationDeskScreenState extends State<QuotationDeskScreen> {
  void _openCreateQuoteSheet(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => const _CreateQuoteSheet(),
    );
  }

  @override
  Widget build(BuildContext context) {
    final quoteProvider = Provider.of<QuotationProvider>(context);

    return Scaffold(
      backgroundColor: ManagerTheme.backgroundDark,
      appBar: AppBar(
        backgroundColor: ManagerTheme.surfaceDark,
        elevation: 0,
        title: const Row(
          children: [
            Text('📜', style: TextStyle(fontSize: 18)),
            SizedBox(width: 8),
            Text('Puja Dakshina Desk', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.add_circle_outline_rounded, color: ManagerTheme.brandGold),
            tooltip: 'Create New Proposal',
            onPressed: () => _openCreateQuoteSheet(context),
          ),
        ],
      ),
      body: quoteProvider.quotes.isEmpty
          ? Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Text('📜', style: TextStyle(fontSize: 48)),
                  const SizedBox(height: 12),
                  const Text('No custom proposals created yet.', style: TextStyle(color: Colors.white60)),
                  const SizedBox(height: 14),
                  ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(backgroundColor: ManagerTheme.brandGold, foregroundColor: Colors.black),
                    icon: const Icon(Icons.add),
                    label: const Text('Create Devotee Proposal'),
                    onPressed: () => _openCreateQuoteSheet(context),
                  ),
                ],
              ),
            )
          : ListView.separated(
              padding: const EdgeInsets.all(14),
              itemCount: quoteProvider.quotes.length,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (ctx, i) {
                final q = quoteProvider.quotes[i];
                return _buildQuoteCard(context, q, quoteProvider);
              },
            ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: ManagerTheme.brandGold,
        foregroundColor: Colors.black,
        icon: const Icon(Icons.note_add_rounded),
        label: const Text('New Proposal', style: TextStyle(fontWeight: FontWeight.bold)),
        onPressed: () => _openCreateQuoteSheet(context),
      ),
    );
  }

  Widget _buildQuoteCard(BuildContext context, PujaQuoteModel q, QuotationProvider provider) {
    final isPaid = q.status == 'paid';

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: ManagerTheme.surfaceDark,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: isPaid ? const Color(0xFF25D366).withValues(alpha: 0.4) : Colors.white10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('#${q.id}', style: const TextStyle(color: ManagerTheme.brandGold, fontSize: 11, fontFamily: 'monospace', fontWeight: FontWeight.bold)),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: isPaid ? const Color(0xFF25D366).withValues(alpha: 0.15) : Colors.amber.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  isPaid ? 'PAID & CONFIRMED' : 'PROPOSAL SENT',
                  style: TextStyle(
                    color: isPaid ? const Color(0xFF25D366) : Colors.amber,
                    fontSize: 9,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(q.pujaTitle, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14)),
          const SizedBox(height: 3),
          Text(
            'Devotee: ${q.devoteeName} (${q.devoteePhone}) · Gotra: ${q.gotra}',
            style: const TextStyle(color: Colors.white70, fontSize: 12),
          ),
          Text('Location: ${q.templeLocation}', style: const TextStyle(color: Colors.white38, fontSize: 11)),
          const Divider(color: Colors.white10, height: 20),
          // Line items preview
          Column(
            children: q.items.map((item) => Padding(
              padding: const EdgeInsets.only(bottom: 4),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(child: Text('• ${item.description}', style: const TextStyle(color: Colors.white60, fontSize: 11))),
                  Text('₹${item.total.toStringAsFixed(0)}', style: const TextStyle(color: Colors.white70, fontSize: 11, fontFamily: 'monospace')),
                ],
              ),
            )).toList(),
          ),
          const Divider(color: Colors.white10, height: 16),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Total Dakshina', style: TextStyle(color: Colors.white38, fontSize: 10)),
                  Text('₹${q.grandTotal.toStringAsFixed(0)}', style: const TextStyle(color: ManagerTheme.brandGold, fontSize: 18, fontWeight: FontWeight.bold)),
                ],
              ),
              ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF25D366),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
                icon: const Icon(Icons.share_rounded, size: 16),
                label: const Text('Send WhatsApp', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                onPressed: () => provider.sendProposalViaWhatsApp(q),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _CreateQuoteSheet extends StatefulWidget {
  const _CreateQuoteSheet();

  @override
  State<_CreateQuoteSheet> createState() => _CreateQuoteSheetState();
}

class _CreateQuoteSheetState extends State<_CreateQuoteSheet> {
  final _nameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _gotraController = TextEditingController(text: 'Kashyap');
  final _pujaTitleController = TextEditingController(text: 'Maha Rudrabhishek with 5 Brahmins');
  final _templeController = TextEditingController(text: 'Kashi Vishwanath Temple, Varanasi');

  final List<PujaQuoteItem> _items = [
    PujaQuoteItem(description: '5 Vedic Acharyas Honorarium (Rudri Path)', amount: 5500),
    PujaQuoteItem(description: 'Pure Desi Ghee, Belpatra, Bhasma & Panchamrit', amount: 2100),
    PujaQuoteItem(description: 'Speed Post Ganga Jal & Sanctified Prasad', amount: 300),
  ];

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _gotraController.dispose();
    _pujaTitleController.dispose();
    _templeController.dispose();
    super.dispose();
  }

  void _saveQuote() {
    if (_nameController.text.trim().isEmpty || _phoneController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Please enter devotee name and phone')));
      return;
    }

    final newQuote = PujaQuoteModel(
      id: 'PQ-${DateTime.now().millisecondsSinceEpoch.toString().substring(7)}',
      devoteeName: _nameController.text.trim(),
      devoteePhone: _phoneController.text.trim(),
      gotra: _gotraController.text.trim(),
      templeLocation: _templeController.text.trim(),
      pujaTitle: _pujaTitleController.text.trim(),
      items: _items,
      createdAt: DateTime.now(),
      status: 'sent',
    );

    final provider = Provider.of<QuotationProvider>(context, listen: false);
    provider.addQuote(newQuote);
    provider.sendProposalViaWhatsApp(newQuote);

    Navigator.of(context).pop();
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
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(width: 40, height: 4, decoration: BoxDecoration(color: Colors.white24, borderRadius: BorderRadius.circular(2))),
            ),
            const SizedBox(height: 16),
            const Text('Create Custom Devotee Proposal', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
            const SizedBox(height: 14),
            TextField(
              controller: _nameController,
              style: const TextStyle(color: Colors.white, fontSize: 13),
              decoration: InputDecoration(
                labelText: 'Devotee Full Name',
                labelStyle: const TextStyle(color: Colors.white60, fontSize: 12),
                filled: true,
                fillColor: const Color(0xFF0B0F19),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
              ),
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _phoneController,
                    keyboardType: TextInputType.phone,
                    style: const TextStyle(color: Colors.white, fontSize: 13),
                    decoration: InputDecoration(
                      labelText: 'Phone Number',
                      labelStyle: const TextStyle(color: Colors.white60, fontSize: 12),
                      filled: true,
                      fillColor: const Color(0xFF0B0F19),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: TextField(
                    controller: _gotraController,
                    style: const TextStyle(color: Colors.white, fontSize: 13),
                    decoration: InputDecoration(
                      labelText: 'Gotra',
                      labelStyle: const TextStyle(color: Colors.white60, fontSize: 12),
                      filled: true,
                      fillColor: const Color(0xFF0B0F19),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _pujaTitleController,
              style: const TextStyle(color: Colors.white, fontSize: 13),
              decoration: InputDecoration(
                labelText: 'Puja / Yagya Title',
                labelStyle: const TextStyle(color: Colors.white60, fontSize: 12),
                filled: true,
                fillColor: const Color(0xFF0B0F19),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
              ),
            ),
            const SizedBox(height: 16),
            const Text('Itemized Ceremony Cost Breakdown:', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12)),
            const SizedBox(height: 8),
            ..._items.map((it) => Padding(
              padding: const EdgeInsets.only(bottom: 6),
              child: Row(
                children: [
                  const Icon(Icons.check_circle_outline, color: ManagerTheme.brandGold, size: 14),
                  const SizedBox(width: 8),
                  Expanded(child: Text(it.description, style: const TextStyle(color: Colors.white70, fontSize: 11))),
                  Text('₹${it.total.toStringAsFixed(0)}', style: const TextStyle(color: ManagerTheme.brandGold, fontWeight: FontWeight.bold, fontSize: 12)),
                ],
              ),
            )),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: ManagerTheme.brandGold,
                  foregroundColor: Colors.black,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                icon: const Icon(Icons.send_rounded, size: 18),
                label: const Text('Generate & Send WhatsApp Proposal', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                onPressed: _saveQuote,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
