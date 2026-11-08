import 'package:flutter/material.dart';
import 'package:onlinepuja_manager/services/manager_api_service.dart';
import 'package:onlinepuja_manager/theme/manager_theme.dart';

class SankalpFulfillmentScreen extends StatefulWidget {
  const SankalpFulfillmentScreen({super.key});

  @override
  State<SankalpFulfillmentScreen> createState() => _SankalpFulfillmentScreenState();
}

class _SankalpFulfillmentScreenState extends State<SankalpFulfillmentScreen> {
  final ManagerApiService _api = ManagerApiService();
  bool _isLoading = true;
  List<Map<String, dynamic>> _fulfillmentList = [];

  @override
  void initState() {
    super.initState();
    _loadLiveSankalps();
  }

  Future<void> _loadLiveSankalps() async {
    setState(() => _isLoading = true);
    final data = await _api.fetchSankalps();
    if (mounted) {
      setState(() {
        _fulfillmentList = data;
        _isLoading = false;
      });
    }
  }

  void _showAttachVideoDialog(Map<String, dynamic> item) {
    final videoCtrl = TextEditingController(text: item['videoUrl']);
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: ManagerTheme.surfaceDark,
        title: Text('Attach Video for ${item['id']}', style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold)),
        content: TextField(
          controller: videoCtrl,
          decoration: const InputDecoration(
            labelText: 'Sankalp Video URL (MP4 / YouTube)',
            hintText: 'https://...',
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: ManagerTheme.brandGold, foregroundColor: Colors.black),
            onPressed: () async {
              final vid = videoCtrl.text;
              setState(() {
                item['videoUrl'] = vid;
                if (item['status'] == 'Video Pending') {
                  item['status'] = 'Prasad Dispatch Pending';
                }
              });
              Navigator.pop(ctx);
              final rawOrderId = item['orderId'] ?? item['id'];
              final orderId = (rawOrderId is num)
                  ? rawOrderId.toInt()
                  : int.tryParse(rawOrderId.toString().replaceAll(RegExp(r'[^0-9]'), '')) ?? 0;

              if (orderId <= 0) {
                if (mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Invalid Order ID. Cannot fulfill sankalp.'), backgroundColor: Colors.redAccent),
                  );
                }
                return;
              }

              await ManagerApiService().fulfillSankalp(
                orderId: orderId,
                videoUrl: vid,
                awbCode: item['awb']?.toString() ?? '',
              );
              if (mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Video saved! Devotee can now watch in their Sankalp Vault.')),
                );
              }
            },
            child: const Text('Save Video'),
          ),
        ],
      ),
    ).whenComplete(() {
      videoCtrl.dispose();
    });
  }

  void _showAddCourierDialog(Map<String, dynamic> item) {
    final awbCtrl = TextEditingController(text: item['awb']);
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: ManagerTheme.surfaceDark,
        title: Text('Enter Courier Tracking for ${item['id']}', style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold)),
        content: TextField(
          controller: awbCtrl,
          decoration: const InputDecoration(
            labelText: 'Courier AWB / Tracking Number',
            hintText: 'e.g. DTDC-9201948',
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: ManagerTheme.brandEmerald, foregroundColor: Colors.white),
            onPressed: () async {
              final awb = awbCtrl.text;
              setState(() {
                item['awb'] = awb;
                item['status'] = 'Fulfilled';
              });
              Navigator.pop(ctx);
              final rawOrderId = item['orderId'] ?? item['id'];
              final orderId = (rawOrderId is num)
                  ? rawOrderId.toInt()
                  : int.tryParse(rawOrderId.toString().replaceAll(RegExp(r'[^0-9]'), '')) ?? 0;

              if (orderId <= 0) {
                if (mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Invalid Order ID. Cannot fulfill sankalp.'), backgroundColor: Colors.redAccent),
                  );
                }
                return;
              }

              await ManagerApiService().fulfillSankalp(
                orderId: orderId,
                videoUrl: item['videoUrl']?.toString() ?? '',
                awbCode: awb,
              );
              if (mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Courier AWB saved! Devotee notified with live tracking link.')),
                );
              }
            },
            child: const Text('Confirm Dispatch'),
          ),
        ],
      ),
    ).whenComplete(() {
      awbCtrl.dispose();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Sankalp & Prasad Dispatch'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: _loadLiveSankalps,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: ManagerTheme.brandGold))
          : RefreshIndicator(
              onRefresh: _loadLiveSankalps,
              color: ManagerTheme.brandGold,
              child: _fulfillmentList.isEmpty
                  ? Center(
                      child: SingleChildScrollView(
                        physics: const AlwaysScrollableScrollPhysics(),
                        padding: const EdgeInsets.all(24),
                        child: Container(
                          padding: const EdgeInsets.all(24),
                          decoration: BoxDecoration(
                            color: ManagerTheme.surfaceDark,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: Colors.white12),
                          ),
                          child: const Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Text('🛕', style: TextStyle(fontSize: 40)),
                              SizedBox(height: 12),
                              Text(
                                'No Pending Sankalps Found',
                                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
                              ),
                              SizedBox(height: 6),
                              Text(
                                'All current devotee puja orders have been completed or no new sankalp requests are queued.',
                                textAlign: TextAlign.center,
                                style: TextStyle(fontSize: 12, color: Colors.white60),
                              ),
                            ],
                          ),
                        ),
                      ),
                    )
                  : ListView.separated(
                      padding: const EdgeInsets.all(16),
                      itemCount: _fulfillmentList.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 12),
                      itemBuilder: (context, i) {
                        final o = _fulfillmentList[i];
                        final isDone = o['status'] == 'Fulfilled';
                        final hasVideo = (o['videoUrl']?.toString() ?? '').isNotEmpty;
                        final hasAwb = (o['awb']?.toString() ?? '').isNotEmpty;

          return Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: ManagerTheme.surfaceDark,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(
                color: isDone ? ManagerTheme.brandEmerald.withValues(alpha: 0.3) : Colors.white.withValues(alpha: 0.1),
              ),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(o['id'], style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white60)),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: isDone
                            ? ManagerTheme.brandEmerald.withValues(alpha: 0.18)
                            : ManagerTheme.brandGold.withValues(alpha: 0.18),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text(
                        o['status'],
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                          color: isDone ? ManagerTheme.brandEmerald : ManagerTheme.brandGold,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(o['puja'], style: const TextStyle(fontSize: 14.5, fontWeight: FontWeight.w800, color: Colors.white)),
                const SizedBox(height: 2),
                Text("${o['devotee']} · ${o['gotra']}", style: const TextStyle(fontSize: 12, color: Colors.white70)),
                Text(o['temple'], style: const TextStyle(fontSize: 11, color: Colors.white54)),
                const Divider(height: 20, color: Colors.white10),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        style: OutlinedButton.styleFrom(
                          foregroundColor: hasVideo ? Colors.white : ManagerTheme.brandGold,
                          side: BorderSide(color: hasVideo ? Colors.white30 : ManagerTheme.brandGold),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          padding: const EdgeInsets.symmetric(vertical: 8),
                        ),
                        icon: Icon(hasVideo ? Icons.check_circle_rounded : Icons.video_call_rounded, size: 16),
                        label: Text(hasVideo ? 'Video Attached' : 'Attach Video', style: const TextStyle(fontSize: 11)),
                        onPressed: () => _showAttachVideoDialog(o),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: hasAwb ? ManagerTheme.brandEmerald : const Color(0xFF334155),
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          padding: const EdgeInsets.symmetric(vertical: 8),
                        ),
                        icon: Icon(hasAwb ? Icons.local_shipping_rounded : Icons.post_add_rounded, size: 16),
                        label: Text(hasAwb ? 'AWB Added' : 'Add Courier', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                        onPressed: () => _showAddCourierDialog(o),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          );
        },
      ),
    ),
  );
}
}
