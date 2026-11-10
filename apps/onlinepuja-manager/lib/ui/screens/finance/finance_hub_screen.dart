import 'package:flutter/material.dart';
import '../../../services/manager_api_service.dart';
import '../../../theme/manager_theme.dart';

class FinanceHubScreen extends StatefulWidget {
  const FinanceHubScreen({super.key});

  @override
  State<FinanceHubScreen> createState() => _FinanceHubScreenState();
}

class _FinanceHubScreenState extends State<FinanceHubScreen> {
  final ManagerApiService _apiService = ManagerApiService();
  bool _isLoading = true;
  String? _errorMessage;
  Map<String, dynamic>? _financeData;

  @override
  void initState() {
    super.initState();
    _loadFinanceData();
  }

  Future<void> _loadFinanceData() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await _apiService.fetchFinanceOverview();
      if (res != null) {
        setState(() {
          _financeData = res;
          _isLoading = false;
        });
      } else {
        setState(() {
          _errorMessage = 'Unable to fetch financial ledger. Check connection.';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = 'Error loading finance data: $e';
        _isLoading = false;
      });
    }
  }

  String _formatCurrency(dynamic amount) {
    if (amount == null) return '₹0';
    final numVal = num.tryParse(amount.toString()) ?? 0;
    return '₹${numVal.toStringAsFixed(numVal % 1 == 0 ? 0 : 2)}';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: ManagerTheme.backgroundDark,
      appBar: AppBar(
        backgroundColor: ManagerTheme.surfaceDark,
        elevation: 0,
        title: const Row(
          children: [
            Text('💰', style: TextStyle(fontSize: 18)),
            SizedBox(width: 8),
            Text('Puja Finance & Ledger', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
          ],
        ),
        actions: [
          IconButton(
            tooltip: 'Refresh Ledger',
            icon: const Icon(Icons.refresh_rounded, color: ManagerTheme.brandGold),
            onPressed: _loadFinanceData,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  CircularProgressIndicator(color: ManagerTheme.brandGold),
                  SizedBox(height: 14),
                  Text('Aggregating temple dakshina & gateway ledgers...', style: TextStyle(color: Colors.white60, fontSize: 12)),
                ],
              ),
            )
          : RefreshIndicator(
              color: ManagerTheme.brandGold,
              backgroundColor: ManagerTheme.surfaceDark,
              onRefresh: _loadFinanceData,
              child: ListView(
                padding: const EdgeInsets.all(14),
                children: [
                  if (_errorMessage != null)
                    Container(
                      margin: const EdgeInsets.only(bottom: 14),
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.redAccent.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: Colors.redAccent.withValues(alpha: 0.3)),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.error_outline, color: Colors.redAccent, size: 20),
                          const SizedBox(width: 8),
                          Expanded(child: Text(_errorMessage!, style: const TextStyle(color: Colors.redAccent, fontSize: 12))),
                          TextButton(onPressed: _loadFinanceData, child: const Text('Retry', style: TextStyle(color: Colors.white))),
                        ],
                      ),
                    ),

                  // Revenue & Payouts Master Card
                  _buildMasterRevenueCard(),
                  const SizedBox(height: 14),

                  // Secondary Metrics
                  _buildSecondaryMetricsGrid(),
                  const SizedBox(height: 18),

                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        'Recent Transactions & Disbursals:',
                        style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: ManagerTheme.brandGold.withValues(alpha: 0.15),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          '${(_financeData?['transactions'] as List?)?.length ?? 0} Recorded',
                          style: const TextStyle(color: ManagerTheme.brandGold, fontSize: 11, fontWeight: FontWeight.bold),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),

                  // Transactions Ledger List
                  _buildTransactionsList(),
                ],
              ),
            ),
    );
  }

  Widget _buildMasterRevenueCard() {
    final netRev = _financeData?['net_revenue_month'] ?? 0;
    final gross = _financeData?['gross_revenue'] ?? 0;
    final panditFee = _financeData?['pandit_honorarium'] ?? 0;
    final samagri = _financeData?['samagri_logistics'] ?? 0;
    final marginPct = _financeData?['net_margin_pct'] ?? '0.0%';

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF131D3B), Color(0xFF0F172A)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.3)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'NET REVENUE THIS MONTH',
                style: TextStyle(color: Colors.white38, fontSize: 11, fontWeight: FontWeight.bold, letterSpacing: 0.5),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                decoration: BoxDecoration(
                  color: const Color(0xFF25D366).withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(4),
                ),
                child: const Text('Live Reconciled', style: TextStyle(color: Color(0xFF25D366), fontSize: 10, fontWeight: FontWeight.bold)),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            _formatCurrency(netRev),
            style: const TextStyle(color: ManagerTheme.brandGold, fontSize: 30, fontWeight: FontWeight.bold, fontFamily: 'monospace'),
          ),
          const SizedBox(height: 14),
          const Divider(color: Colors.white10),
          const SizedBox(height: 10),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              _financialCol('Gross Dakshina', _formatCurrency(gross), Colors.white),
              _financialCol('Pandit Honorarium', _formatCurrency(panditFee), const Color(0xFFF59E0B)),
              _financialCol('Samagri & Logistics', _formatCurrency(samagri), const Color(0xFF60A5FA)),
              _financialCol('Net Margin', marginPct.toString(), const Color(0xFF25D366)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSecondaryMetricsGrid() {
    final walletBal = _financeData?['wallet_balance'] ?? 0;
    final totalOrders = _financeData?['total_puja_orders'] ?? 0;
    final completedPuja = _financeData?['puja_completed'] ?? 0;

    return Row(
      children: [
        Expanded(
          child: Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: ManagerTheme.surfaceDark,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: Colors.white10),
            ),
            child: Row(
              children: [
                CircleAvatar(
                  radius: 18,
                  backgroundColor: ManagerTheme.brandGold.withValues(alpha: 0.15),
                  child: const Icon(Icons.account_balance_wallet_rounded, color: ManagerTheme.brandGold, size: 18),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Gateway Float / Bal', style: TextStyle(color: Colors.white54, fontSize: 10)),
                      Text(
                        _formatCurrency(walletBal),
                        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14, fontFamily: 'monospace'),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: ManagerTheme.surfaceDark,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: Colors.white10),
            ),
            child: Row(
              children: [
                CircleAvatar(
                  radius: 18,
                  backgroundColor: const Color(0xFF25D366).withValues(alpha: 0.15),
                  child: const Icon(Icons.check_circle_outline_rounded, color: Color(0xFF25D366), size: 18),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Puja Fulfilled', style: TextStyle(color: Colors.white54, fontSize: 10)),
                      Text(
                        '$completedPuja / $totalOrders orders',
                        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildTransactionsList() {
    final transactions = _financeData?['transactions'] as List<dynamic>? ?? [];

    if (transactions.isEmpty) {
      return Container(
        padding: const EdgeInsets.all(24),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: ManagerTheme.surfaceDark,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: Colors.white10),
        ),
        child: const Text('No transactions recorded yet in temple ledger.', style: TextStyle(color: Colors.white54, fontSize: 12)),
      );
    }

    return Column(
      children: transactions.map((t) {
        final tx = t as Map<String, dynamic>;
        final status = (tx['status'] ?? 'pending').toString().toLowerCase();
        final isSuccess = status == 'success' || status == 'paid' || status == 'completed';
        final isFailed = status == 'failed' || status == 'cancelled';

        final statusColor = isSuccess
            ? const Color(0xFF25D366)
            : isFailed
                ? Colors.redAccent
                : Colors.amber;

        final purpose = tx['purpose'] ?? 'Temple Disbursal';
        final userName = tx['user_name'] ?? 'Devotee';
        final phone = tx['user_phone'];
        final mode = tx['mode'] ?? 'Razorpay UPI';
        final createdAt = tx['created_at'] ?? '';
        final amount = _formatCurrency(tx['amount']);

        return Container(
          margin: const EdgeInsets.only(bottom: 10),
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: ManagerTheme.surfaceDark,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: Colors.white10),
          ),
          child: Row(
            children: [
              CircleAvatar(
                radius: 18,
                backgroundColor: statusColor.withValues(alpha: 0.15),
                child: Icon(
                  isSuccess
                      ? Icons.check_circle_rounded
                      : isFailed
                          ? Icons.highlight_off_rounded
                          : Icons.access_time_rounded,
                  color: statusColor,
                  size: 18,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Flexible(
                          child: Text(
                            userName,
                            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        if (phone != null && phone.toString().isNotEmpty) ...[
                          const SizedBox(width: 6),
                          Text('($phone)', style: const TextStyle(color: Colors.white54, fontSize: 10)),
                        ],
                      ],
                    ),
                    const SizedBox(height: 2),
                    Text('$purpose · $mode', style: const TextStyle(color: Colors.white60, fontSize: 11)),
                    if (createdAt.isNotEmpty)
                      Text(createdAt, style: const TextStyle(color: Colors.white38, fontSize: 10)),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    amount,
                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13, fontFamily: 'monospace'),
                  ),
                  const SizedBox(height: 3),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                    decoration: BoxDecoration(
                      color: statusColor.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: Text(
                      status.toUpperCase(),
                      style: TextStyle(color: statusColor, fontSize: 9, fontWeight: FontWeight.bold),
                    ),
                  ),
                ],
              ),
            ],
          ),
        );
      }).toList(),
    );
  }

  Widget _financialCol(String label, String value, Color color) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(value, style: TextStyle(color: color, fontWeight: FontWeight.bold, fontSize: 13, fontFamily: 'monospace')),
        const SizedBox(height: 2),
        Text(label, style: const TextStyle(color: Colors.white38, fontSize: 9)),
      ],
    );
  }
}
