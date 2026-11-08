import 'package:flutter/material.dart';
import 'package:onlinepuja_manager/services/manager_api_service.dart';
import 'package:onlinepuja_manager/theme/manager_theme.dart';
import '../fulfillment/sankalp_fulfillment_screen.dart';

class MasterBookingsScreen extends StatefulWidget {
  const MasterBookingsScreen({super.key});

  @override
  State<MasterBookingsScreen> createState() => _MasterBookingsScreenState();
}

class _MasterBookingsScreenState extends State<MasterBookingsScreen> {
  final ManagerApiService _api = ManagerApiService();

  bool _isLoading = true;
  List<Map<String, dynamic>> _bookings = [];
  String _selectedType = 'all';
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    _loadBookings();
  }

  Future<void> _loadBookings() async {
    setState(() => _isLoading = true);
    final list = await _api.fetchMasterBookings(type: _selectedType);
    if (mounted) {
      setState(() {
        _bookings = list;
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final filtered = _bookings.where((b) {
      final id = (b['id'] ?? '').toString().toLowerCase();
      final devotee = (b['devotee'] ?? '').toString().toLowerCase();
      final gotra = (b['gotra'] ?? '').toString().toLowerCase();
      final title = (b['title'] ?? '').toString().toLowerCase();
      final q = _searchQuery.toLowerCase();
      return id.contains(q) || devotee.contains(q) || gotra.contains(q) || title.contains(q);
    }).toList();

    final totalRevenue = _bookings.fold<double>(0, (acc, b) {
      final amt = b['amount'];
      final val = (amt is num) ? amt.toDouble() : (double.tryParse(amt?.toString() ?? '') ?? 0.0);
      return acc + val;
    });

    return Scaffold(
      appBar: AppBar(
        title: const Text('Master Bookings & Revenue Ledger'),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            icon: const Icon(Icons.refresh_rounded),
            onPressed: _loadBookings,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: ManagerTheme.brandGold))
          : RefreshIndicator(
              onRefresh: _loadBookings,
              color: ManagerTheme.brandGold,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Financial Summary Banner
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        gradient: ManagerTheme.darkCardGradient,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.35)),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text('TOTAL LEDGER VALUE', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.white70, letterSpacing: 0.8)),
                              const SizedBox(height: 4),
                              Text('₹${totalRevenue.toStringAsFixed(0)}', style: const TextStyle(fontSize: 26, fontWeight: FontWeight.w900, color: Colors.white)),
                            ],
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                            decoration: BoxDecoration(
                              color: ManagerTheme.brandGold.withValues(alpha: 0.2),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Text(
                              '${_bookings.length} Bookings',
                              style: const TextStyle(color: ManagerTheme.brandGold, fontWeight: FontWeight.bold, fontSize: 13),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),

                    // Filter Tabs
                    Row(
                      children: [
                        _buildFilterButton('all', 'All Bookings'),
                        const SizedBox(width: 8),
                        _buildFilterButton('puja', '🛕 Pujas'),
                        const SizedBox(width: 8),
                        _buildFilterButton('astrology', '🔮 Calls & Chats'),
                      ],
                    ),
                    const SizedBox(height: 12),

                    // Search Field
                    TextField(
                      decoration: InputDecoration(
                        hintText: 'Search by Devotee, Gotra or Order ID (#OP-PUJA)...',
                        prefixIcon: const Icon(Icons.search_rounded, color: Colors.white60),
                        filled: true,
                        fillColor: ManagerTheme.surfaceDark,
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Colors.white12)),
                        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Colors.white12)),
                      ),
                      onChanged: (val) => setState(() => _searchQuery = val),
                    ),
                    const SizedBox(height: 18),

                    // Bookings List
                    if (filtered.isEmpty)
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.symmetric(vertical: 24, horizontal: 16),
                        decoration: BoxDecoration(
                          color: ManagerTheme.surfaceDark,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: Colors.white12),
                        ),
                        child: Center(
                          child: Text(
                            _searchQuery.isNotEmpty ? 'No bookings match "$_searchQuery".' : 'No bookings found for the selected filter.',
                            textAlign: TextAlign.center,
                            style: const TextStyle(fontSize: 12, color: Colors.white60),
                          ),
                        ),
                      )
                    else
                      ListView.separated(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount: filtered.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 12),
                      itemBuilder: (context, i) {
                        final b = filtered[i];
                        final id = b['id']?.toString() ?? '#OP-PUJA';
                        final devotee = b['devotee']?.toString() ?? 'Devotee';
                        final gotra = b['gotra']?.toString() ?? 'Kashyap';
                        final title = b['title']?.toString() ?? 'Sacred Puja';
                        final amtRaw = b['amount'];
                        final amount = (amtRaw is num) ? amtRaw.toDouble() : (double.tryParse(amtRaw?.toString() ?? '') ?? 1500.0);
                        final status = b['status']?.toString() ?? 'confirmed';
                        final hasVideo = b['has_video'] == true;
                        final awb = b['awb']?.toString();
                        final date = b['created_at']?.toString() ?? 'Today';
                        final isPuja = b['type'] == 'puja';

                        return Container(
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: ManagerTheme.surfaceDark,
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: Colors.white12),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Row(
                                    children: [
                                      Text(id, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: ManagerTheme.brandGold)),
                                      const SizedBox(width: 6),
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                        decoration: BoxDecoration(
                                          color: isPuja ? Colors.amber.withValues(alpha: 0.15) : Colors.purple.withValues(alpha: 0.15),
                                          borderRadius: BorderRadius.circular(4),
                                        ),
                                        child: Text(
                                          isPuja ? 'PUJA' : 'CONSULT',
                                          style: TextStyle(
                                            color: isPuja ? Colors.amberAccent : Colors.purpleAccent,
                                            fontSize: 9,
                                            fontWeight: FontWeight.bold,
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                  Text(
                                    '₹${amount.toStringAsFixed(0)}',
                                    style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w900, color: Colors.white),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 8),
                              Text(
                                title,
                                style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: Colors.white),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                'Devotee: $devotee · Gotra: $gotra',
                                style: const TextStyle(fontSize: 12, color: Colors.white70),
                              ),
                              const Divider(color: Colors.white10, height: 18),
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        'Status: ${status.toUpperCase()}',
                                        style: TextStyle(
                                          fontSize: 10.5,
                                          fontWeight: FontWeight.bold,
                                          color: status.contains('complete') ? Colors.greenAccent : Colors.amberAccent,
                                        ),
                                      ),
                                      if (awb != null)
                                        Text('Speed Post: $awb', style: const TextStyle(fontSize: 10, color: Colors.blueAccent))
                                      else if (isPuja)
                                        Text(
                                          hasVideo ? '✓ Sanctum Video Ready' : '⏳ Awaiting Video Proof',
                                          style: TextStyle(fontSize: 10, color: hasVideo ? Colors.greenAccent : Colors.orangeAccent),
                                        ),
                                    ],
                                  ),
                                  if (isPuja)
                                    ElevatedButton(
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: ManagerTheme.brandGold.withValues(alpha: 0.2),
                                        foregroundColor: ManagerTheme.brandGold,
                                        elevation: 0,
                                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                        visualDensity: VisualDensity.compact,
                                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8), side: const BorderSide(color: ManagerTheme.brandGold)),
                                      ),
                                      onPressed: () {
                                        Navigator.push(
                                          context,
                                          MaterialPageRoute(builder: (_) => const SankalpFulfillmentScreen()),
                                        );
                                      },
                                      child: const Text('Fulfill Order', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                                    )
                                  else
                                    Text(date, style: const TextStyle(fontSize: 10, color: Colors.white54)),
                                ],
                              ),
                            ],
                          ),
                        );
                      },
                    ),
                    const SizedBox(height: 40),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildFilterButton(String type, String label) {
    final selected = _selectedType == type;
    return Expanded(
      child: GestureDetector(
        onTap: () {
          setState(() => _selectedType = type);
          _loadBookings();
        },
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 8),
          decoration: BoxDecoration(
            color: selected ? ManagerTheme.brandGold : ManagerTheme.surfaceDark,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: selected ? ManagerTheme.brandGold : Colors.white24),
          ),
          child: Center(
            child: Text(
              label,
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.bold,
                color: selected ? Colors.black : Colors.white,
              ),
            ),
          ),
        ),
      ),
    );
  }
}
