import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:onlinepuja_manager/services/manager_api_service.dart';
import 'package:onlinepuja_manager/theme/manager_theme.dart';
import 'package:url_launcher/url_launcher.dart';

class SankalpFulfillmentScreen extends StatefulWidget {
  const SankalpFulfillmentScreen({super.key});

  @override
  State<SankalpFulfillmentScreen> createState() => _SankalpFulfillmentScreenState();
}

class _SankalpFulfillmentScreenState extends State<SankalpFulfillmentScreen> {
  final ManagerApiService _api = ManagerApiService();
  bool _isLoading = true;
  List<Map<String, dynamic>> _fulfillmentList = [];
  String _selectedFilter = 'all'; // all, pending_video, pending_courier, fulfilled
  String _searchQuery = '';

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

  String _cleanPhone(dynamic rawPhone) {
    if (rawPhone == null) return '';
    final digits = rawPhone.toString().replaceAll(RegExp(r'[^0-9]'), '');
    if (digits.length >= 10) {
      return digits.substring(digits.length - 10);
    }
    return digits;
  }

  Future<void> _launchCall(String phone) async {
    final clean = _cleanPhone(phone);
    if (clean.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Devotee phone number not available')),
      );
      return;
    }
    final uri = Uri.parse('tel:+91$clean');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }

  Future<void> _sendWhatsAppVideoProof(Map<String, dynamic> item) async {
    final phone = _cleanPhone(item['phone']);
    if (phone.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Devotee phone number is missing from this order.')),
      );
      return;
    }

    final devotee = item['devotee'] ?? 'श्रद्धालु';
    final puja = item['puja'] ?? 'पावन वैदिक पूजा';
    final temple = item['temple'] ?? 'काशी विश्वनाथ धाम';
    final gotra = item['gotra'] ?? 'कश्यप गोत्र';
    final video = (item['videoUrl']?.toString() ?? '').trim();

    if (video.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please attach the Sanctum Video URL first before sending WhatsApp proof.'),
          backgroundColor: Colors.orangeAccent,
        ),
      );
      _showAttachVideoDialog(item);
      return;
    }

    final message = '''🌸 *हर हर महादेव! जय श्री काशी विश्वनाथ* 🌸

आदरणीय *${devotee}* जी,

आपके पावन *${gotra}* के नाम पर *${puja}* का पावन वैदिक संकल्प *${temple}* से आचार्यों द्वारा विधि-विधान से संपन्न करा दिया गया है। 🛕✨

📹 *आपका पावन संकल्प एवं पूजा दर्शन वीडियो:*
${video}

📦 आपका अभिमंत्रित महाप्रसाद एवं रक्षा सूत्र भी शीघ्र प्रेषित किया जाएगा। 

बाबा विश्वनाथ एवं माँ गंगा की कृपा आपके समस्त परिवार पर सदैव बनी रहे! 🙏🌺

— *OnlinePuja.live | वैदिक पूजा एवं संकल्प सेवा*''';

    final uri = Uri.parse('https://wa.me/91$phone?text=${Uri.encodeComponent(message)}');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else {
      Clipboard.setData(ClipboardData(text: message));
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('WhatsApp message copied to clipboard!')),
        );
      }
    }
  }

  Future<void> _sendWhatsAppCourierTracking(Map<String, dynamic> item) async {
    final phone = _cleanPhone(item['phone']);
    if (phone.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Devotee phone number is missing.')),
      );
      return;
    }

    final devotee = item['devotee'] ?? 'श्रद्धालु';
    final puja = item['puja'] ?? 'वैदिक पूजा';
    final awb = (item['awb']?.toString() ?? '').trim();
    final courier = item['courier_name'] ?? 'SpeedPost';

    if (awb.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please add the Courier AWB / Tracking number first.'),
          backgroundColor: Colors.orangeAccent,
        ),
      );
      _showAddCourierDialog(item);
      return;
    }

    final message = '''📦 *जय श्री राम! महाप्रसाद प्रेषण सूचना* 🌸🙏

आदरणीय *${devotee}* जी,

आपके *${puja}* का सिद्ध अभिमंत्रित महाप्रसाद, भस्म, एवं रक्षा सूत्र पवित्र धाम से आपके पते पर स्पीड पोस्ट/कूरियर द्वारा प्रेषित कर दिया गया है। 🚚🚩

🏷️ *कूरियर पार्टनर:* ${courier}
📋 *ट्रैकिंग AWB नंबर:* ${awb}
🔗 *लाइव ट्रैकिंग:* https://www.indiapost.gov.in/_layouts/15/dpt.ptc.track/trackconsignment.aspx

प्रसाद प्राप्त होने पर कृपया श्रद्धापूर्वक परिवार सहित ग्रहण करें। 🌺

— *OnlinePuja.live | संकल्प डेस्क*''';

    final uri = Uri.parse('https://wa.me/91$phone?text=${Uri.encodeComponent(message)}');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else {
      Clipboard.setData(ClipboardData(text: message));
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Courier tracking message copied to clipboard!')),
        );
      }
    }
  }

  void _showAttachVideoDialog(Map<String, dynamic> item) {
    final videoCtrl = TextEditingController(text: item['videoUrl']);
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: ManagerTheme.surfaceDark,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(
          children: [
            const Icon(Icons.video_camera_front_rounded, color: ManagerTheme.brandGold, size: 22),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                'Sanctum Video for ${item['id']}',
                style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white),
              ),
            ),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              '${item['devotee']} · ${item['puja']}',
              style: const TextStyle(fontSize: 12, color: Colors.white70),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: videoCtrl,
              autofocus: true,
              style: const TextStyle(color: Colors.white, fontSize: 13),
              decoration: InputDecoration(
                labelText: 'Sanctum Video URL (YouTube, MP4, Google Drive)',
                labelStyle: const TextStyle(color: Colors.white60, fontSize: 12),
                hintText: 'https://...',
                hintStyle: const TextStyle(color: Colors.white30, fontSize: 12),
                filled: true,
                fillColor: Colors.black26,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Cancel', style: TextStyle(color: Colors.white60)),
          ),
          ElevatedButton.icon(
            style: ElevatedButton.styleFrom(
              backgroundColor: ManagerTheme.brandGold,
              foregroundColor: Colors.black,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            icon: const Icon(Icons.save_rounded, size: 16),
            label: const Text('Save & Notify', style: TextStyle(fontWeight: FontWeight.bold)),
            onPressed: () async {
              final vid = videoCtrl.text.trim();
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
                    const SnackBar(content: Text('Invalid Order ID.'), backgroundColor: Colors.redAccent),
                  );
                }
                return;
              }

              await _api.fulfillSankalp(
                orderId: orderId,
                videoUrl: vid,
                awbCode: item['awb']?.toString() ?? '',
              );

              if (mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('✓ Video saved! Devotee can now watch in their Sankalp Vault.'),
                    backgroundColor: ManagerTheme.brandEmerald,
                  ),
                );
              }
            },
          ),
        ],
      ),
    ).whenComplete(() {
      videoCtrl.dispose();
    });
  }

  void _showAddCourierDialog(Map<String, dynamic> item) {
    final awbCtrl = TextEditingController(text: item['awb']);
    final courierCtrl = TextEditingController(text: item['courier_name'] ?? 'SpeedPost');

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: ManagerTheme.surfaceDark,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(
          children: [
            const Icon(Icons.local_shipping_rounded, color: ManagerTheme.brandEmerald, size: 22),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                'Prasad Courier for ${item['id']}',
                style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white),
              ),
            ),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'To: ${item['devotee']}\n${item['city']}, ${item['state']} - ${item['pincode']}',
              style: const TextStyle(fontSize: 12, color: Colors.white70),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: courierCtrl,
              style: const TextStyle(color: Colors.white, fontSize: 13),
              decoration: InputDecoration(
                labelText: 'Courier Service',
                labelStyle: const TextStyle(color: Colors.white60, fontSize: 12),
                hintText: 'e.g. SpeedPost, BlueDart, DTDC',
                filled: true,
                fillColor: Colors.black26,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
              ),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: awbCtrl,
              autofocus: true,
              style: const TextStyle(color: Colors.white, fontSize: 13),
              decoration: InputDecoration(
                labelText: 'AWB / Tracking Number',
                labelStyle: const TextStyle(color: Colors.white60, fontSize: 12),
                hintText: 'e.g. EK920194821IN',
                filled: true,
                fillColor: Colors.black26,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Cancel', style: TextStyle(color: Colors.white60)),
          ),
          ElevatedButton.icon(
            style: ElevatedButton.styleFrom(
              backgroundColor: ManagerTheme.brandEmerald,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            icon: const Icon(Icons.check_circle_rounded, size: 16),
            label: const Text('Confirm Dispatch', style: TextStyle(fontWeight: FontWeight.bold)),
            onPressed: () async {
              final awb = awbCtrl.text.trim();
              final courier = courierCtrl.text.trim();
              setState(() {
                item['awb'] = awb;
                item['courier_name'] = courier;
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
                    const SnackBar(content: Text('Invalid Order ID.'), backgroundColor: Colors.redAccent),
                  );
                }
                return;
              }

              await _api.fulfillSankalp(
                orderId: orderId,
                videoUrl: item['videoUrl']?.toString() ?? '',
                awbCode: awb,
              );

              if (mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('✓ Courier AWB saved! Devotee notified.'),
                    backgroundColor: ManagerTheme.brandEmerald,
                  ),
                );
              }
            },
          ),
        ],
      ),
    ).whenComplete(() {
      awbCtrl.dispose();
      courierCtrl.dispose();
    });
  }

  @override
  Widget build(BuildContext context) {
    final filtered = _fulfillmentList.where((item) {
      final status = (item['status'] ?? '').toString().toLowerCase();
      final hasVid = (item['videoUrl']?.toString() ?? '').trim().isNotEmpty;
      final hasAwb = (item['awb']?.toString() ?? '').trim().isNotEmpty;

      if (_selectedFilter == 'pending_video' && hasVid) return false;
      if (_selectedFilter == 'pending_courier' && (!hasVid || hasAwb)) return false;
      if (_selectedFilter == 'fulfilled' && !hasAwb) return false;

      if (_searchQuery.isNotEmpty) {
        final q = _searchQuery.toLowerCase();
        final dev = (item['devotee'] ?? '').toString().toLowerCase();
        final id = (item['id'] ?? '').toString().toLowerCase();
        final puja = (item['puja'] ?? '').toString().toLowerCase();
        final phone = (item['phone'] ?? '').toString().toLowerCase();
        final gotra = (item['gotra'] ?? '').toString().toLowerCase();
        return dev.contains(q) || id.contains(q) || puja.contains(q) || phone.contains(q) || gotra.contains(q);
      }

      return true;
    }).toList();

    return Scaffold(
      appBar: AppBar(
        title: const Text('Sankalp & Prasad Dispatch Desk'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            tooltip: 'Refresh Sankalps',
            onPressed: _loadLiveSankalps,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: ManagerTheme.brandGold))
          : RefreshIndicator(
              onRefresh: _loadLiveSankalps,
              color: ManagerTheme.brandGold,
              child: Column(
                children: [
                  // Search & Quick Status Filter Bar
                  Container(
                    color: ManagerTheme.surfaceDark,
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                    child: Column(
                      children: [
                        TextField(
                          decoration: InputDecoration(
                            hintText: 'Search devotee, order #OP-PUJA, or phone...',
                            hintStyle: const TextStyle(fontSize: 12, color: Colors.white38),
                            prefixIcon: const Icon(Icons.search_rounded, size: 20, color: Colors.white60),
                            filled: true,
                            fillColor: Colors.black26,
                            isDense: true,
                            contentPadding: const EdgeInsets.symmetric(vertical: 10),
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
                          ),
                          onChanged: (val) => setState(() => _searchQuery = val),
                        ),
                        const SizedBox(height: 8),
                        SingleChildScrollView(
                          scrollDirection: Axis.horizontal,
                          child: Row(
                            children: [
                              _buildFilterChip('all', 'All (${_fulfillmentList.length})'),
                              const SizedBox(width: 6),
                              _buildFilterChip('pending_video', '⏳ Video Pending'),
                              const SizedBox(width: 6),
                              _buildFilterChip('pending_courier', '📦 Courier Pending'),
                              const SizedBox(width: 6),
                              _buildFilterChip('fulfilled', '✓ Completed'),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),

                  // Sankalps Feed
                  Expanded(
                    child: filtered.isEmpty
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
                                      'No Sankalps In This Queue',
                                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
                                    ),
                                    SizedBox(height: 6),
                                    Text(
                                      'All sankalp recordings and courier dispatches in this category are completed.',
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
                            itemCount: filtered.length,
                            separatorBuilder: (_, __) => const SizedBox(height: 14),
                            itemBuilder: (context, i) {
                              final o = filtered[i];
                              final isDone = o['status'] == 'Fulfilled';
                              final hasVideo = (o['videoUrl']?.toString() ?? '').trim().isNotEmpty;
                              final hasAwb = (o['awb']?.toString() ?? '').trim().isNotEmpty;
                              final imgUrl = o['image_url']?.toString();
                              final phone = _cleanPhone(o['phone']);
                              final notes = (o['sankalp_notes']?.toString() ?? '').trim();

                              return Container(
                                decoration: BoxDecoration(
                                  color: ManagerTheme.surfaceDark,
                                  borderRadius: BorderRadius.circular(16),
                                  border: Border.all(
                                    color: isDone
                                        ? ManagerTheme.brandEmerald.withValues(alpha: 0.35)
                                        : ManagerTheme.brandGold.withValues(alpha: 0.25),
                                  ),
                                  boxShadow: [
                                    BoxShadow(
                                      color: Colors.black.withValues(alpha: 0.25),
                                      blurRadius: 8,
                                      offset: const Offset(0, 3),
                                    ),
                                  ],
                                ),
                                clipBehavior: Clip.antiAlias,
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    // 1. Temple Cover / Header Image Banner (if available)
                                    if (imgUrl != null && imgUrl.isNotEmpty)
                                      Stack(
                                        children: [
                                          AspectRatio(
                                            aspectRatio: 16 / 7,
                                            child: Image.network(
                                              imgUrl,
                                              fit: BoxFit.cover,
                                              loadingBuilder: (_, child, prog) => prog == null
                                                  ? child
                                                  : Container(
                                                      color: Colors.black26,
                                                      child: const Center(
                                                        child: CircularProgressIndicator(strokeWidth: 2, color: ManagerTheme.brandGold),
                                                      ),
                                                    ),
                                              errorBuilder: (_, __, ___) => Container(
                                                color: Colors.black38,
                                                child: const Center(
                                                  child: Icon(Icons.temple_hindu_rounded, size: 36, color: Colors.white24),
                                                ),
                                              ),
                                            ),
                                          ),
                                          Positioned(
                                            top: 8,
                                            left: 10,
                                            child: Container(
                                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                              decoration: BoxDecoration(
                                                color: Colors.black.withValues(alpha: 0.75),
                                                borderRadius: BorderRadius.circular(6),
                                                border: Border.all(color: Colors.white24),
                                              ),
                                              child: Text(
                                                o['id'],
                                                style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: ManagerTheme.brandGold),
                                              ),
                                            ),
                                          ),
                                          Positioned(
                                            top: 8,
                                            right: 10,
                                            child: Container(
                                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                              decoration: BoxDecoration(
                                                color: (isDone ? ManagerTheme.brandEmerald : ManagerTheme.brandGold).withValues(alpha: 0.9),
                                                borderRadius: BorderRadius.circular(6),
                                              ),
                                              child: Text(
                                                o['status'],
                                                style: TextStyle(
                                                  fontSize: 10,
                                                  fontWeight: FontWeight.bold,
                                                  color: isDone ? Colors.white : Colors.black,
                                                ),
                                              ),
                                            ),
                                          ),
                                          Positioned(
                                            bottom: 8,
                                            left: 10,
                                            child: Container(
                                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                              decoration: BoxDecoration(
                                                color: Colors.black.withValues(alpha: 0.8),
                                                borderRadius: BorderRadius.circular(6),
                                              ),
                                              child: Row(
                                                mainAxisSize: MainAxisSize.min,
                                                children: [
                                                  const Icon(Icons.place_rounded, size: 12, color: Colors.redAccent),
                                                  const SizedBox(width: 4),
                                                  Text(
                                                    o['temple'],
                                                    style: const TextStyle(fontSize: 10, color: Colors.white),
                                                  ),
                                                ],
                                              ),
                                            ),
                                          ),
                                        ],
                                      )
                                    else
                                      Padding(
                                        padding: const EdgeInsets.fromLTRB(14, 12, 14, 0),
                                        child: Row(
                                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                          children: [
                                            Text(o['id'], style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: ManagerTheme.brandGold)),
                                            Container(
                                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                              decoration: BoxDecoration(
                                                color: (isDone ? ManagerTheme.brandEmerald : ManagerTheme.brandGold).withValues(alpha: 0.18),
                                                borderRadius: BorderRadius.circular(8),
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
                                      ),

                                    // 2. Devotee & Puja Body
                                    Padding(
                                      padding: const EdgeInsets.all(14),
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Row(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              Expanded(
                                                child: Column(
                                                  crossAxisAlignment: CrossAxisAlignment.start,
                                                  children: [
                                                    Text(
                                                      o['puja'],
                                                      style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: Colors.white, height: 1.2),
                                                    ),
                                                    const SizedBox(height: 4),
                                                    Text(
                                                      "${o['devotee']} · ${o['gotra']}",
                                                      style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: ManagerTheme.brandGold),
                                                    ),
                                                  ],
                                                ),
                                              ),
                                              Text(
                                                o['amount'] ?? '',
                                                style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w900, color: Colors.white),
                                              ),
                                            ],
                                          ),

                                          // Devotee Address / City
                                          if ((o['city']?.toString() ?? '').isNotEmpty)
                                            Padding(
                                              padding: const EdgeInsets.only(top: 6),
                                              child: Row(
                                                children: [
                                                  const Icon(Icons.location_city_rounded, size: 13, color: Colors.white38),
                                                  const SizedBox(width: 4),
                                                  Text(
                                                    "${o['city']}, ${o['state']} (${o['pincode']})",
                                                    style: const TextStyle(fontSize: 11, color: Colors.white60),
                                                  ),
                                                ],
                                              ),
                                            ),

                                          // Sankalp Notes / Special Wishes
                                          if (notes.isNotEmpty)
                                            Container(
                                              margin: const EdgeInsets.only(top: 8),
                                              padding: const EdgeInsets.all(8),
                                              decoration: BoxDecoration(
                                                color: Colors.black26,
                                                borderRadius: BorderRadius.circular(8),
                                                border: Border.all(color: Colors.white12),
                                              ),
                                              child: Row(
                                                crossAxisAlignment: CrossAxisAlignment.start,
                                                children: [
                                                  const Text('🙏 ', style: TextStyle(fontSize: 12)),
                                                  Expanded(
                                                    child: Text(
                                                      notes,
                                                      style: const TextStyle(fontSize: 11, fontStyle: FontStyle.italic, color: Colors.white70),
                                                    ),
                                                  ),
                                                ],
                                              ),
                                            ),

                                          const Divider(height: 18, color: Colors.white10),

                                          // 3. Status Action Buttons (Attach Video & Add Courier)
                                          Row(
                                            children: [
                                              Expanded(
                                                child: OutlinedButton.icon(
                                                  style: OutlinedButton.styleFrom(
                                                    foregroundColor: hasVideo ? Colors.white : ManagerTheme.brandGold,
                                                    side: BorderSide(color: hasVideo ? ManagerTheme.brandEmerald : ManagerTheme.brandGold),
                                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                                    padding: const EdgeInsets.symmetric(vertical: 8),
                                                  ),
                                                  icon: Icon(
                                                    hasVideo ? Icons.check_circle_rounded : Icons.video_call_rounded,
                                                    size: 16,
                                                    color: hasVideo ? ManagerTheme.brandEmerald : ManagerTheme.brandGold,
                                                  ),
                                                  label: Text(
                                                    hasVideo ? 'Video Attached' : 'Attach Video',
                                                    style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold),
                                                  ),
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
                                                  label: Text(
                                                    hasAwb ? 'AWB Added' : 'Add Courier',
                                                    style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold),
                                                  ),
                                                  onPressed: () => _showAddCourierDialog(o),
                                                ),
                                              ),
                                            ],
                                          ),

                                          const SizedBox(height: 10),

                                          // 4. Fast Superadmin 1-Tap Customer Connect Row (WhatsApp & Call)
                                          Container(
                                            padding: const EdgeInsets.all(8),
                                            decoration: BoxDecoration(
                                              color: Colors.black26,
                                              borderRadius: BorderRadius.circular(10),
                                              border: Border.all(color: Colors.white10),
                                            ),
                                            child: Row(
                                              children: [
                                                // 1-Tap WhatsApp Video Proof
                                                Expanded(
                                                  child: InkWell(
                                                    borderRadius: BorderRadius.circular(6),
                                                    onTap: () => _sendWhatsAppVideoProof(o),
                                                    child: Container(
                                                      padding: const EdgeInsets.symmetric(vertical: 6),
                                                      decoration: BoxDecoration(
                                                        color: const Color(0xFF25D366).withValues(alpha: 0.18),
                                                        borderRadius: BorderRadius.circular(6),
                                                        border: Border.all(color: const Color(0xFF25D366).withValues(alpha: 0.4)),
                                                      ),
                                                      child: const Row(
                                                        mainAxisAlignment: MainAxisAlignment.center,
                                                        children: [
                                                          Icon(Icons.video_collection_rounded, size: 14, color: Color(0xFF25D366)),
                                                          SizedBox(width: 4),
                                                          Text(
                                                            'WA Video Proof',
                                                            style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: Color(0xFF25D366)),
                                                          ),
                                                        ],
                                                      ),
                                                    ),
                                                  ),
                                                ),
                                                const SizedBox(width: 6),

                                                // 1-Tap WhatsApp Courier Tracking
                                                Expanded(
                                                  child: InkWell(
                                                    borderRadius: BorderRadius.circular(6),
                                                    onTap: () => _sendWhatsAppCourierTracking(o),
                                                    child: Container(
                                                      padding: const EdgeInsets.symmetric(vertical: 6),
                                                      decoration: BoxDecoration(
                                                        color: const Color(0xFF38BDF8).withValues(alpha: 0.18),
                                                        borderRadius: BorderRadius.circular(6),
                                                        border: Border.all(color: const Color(0xFF38BDF8).withValues(alpha: 0.4)),
                                                      ),
                                                      child: const Row(
                                                        mainAxisAlignment: MainAxisAlignment.center,
                                                        children: [
                                                          Icon(Icons.markunread_mailbox_rounded, size: 14, color: Color(0xFF38BDF8)),
                                                          SizedBox(width: 4),
                                                          Text(
                                                            'WA Tracking',
                                                            style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: Color(0xFF38BDF8)),
                                                          ),
                                                        ],
                                                      ),
                                                    ),
                                                  ),
                                                ),

                                                // Direct Phone Call
                                                if (phone.isNotEmpty) ...[
                                                  const SizedBox(width: 6),
                                                  InkWell(
                                                    borderRadius: BorderRadius.circular(6),
                                                    onTap: () => _launchCall(phone),
                                                    child: Container(
                                                      padding: const EdgeInsets.all(6),
                                                      decoration: BoxDecoration(
                                                        color: Colors.white10,
                                                        borderRadius: BorderRadius.circular(6),
                                                      ),
                                                      child: const Icon(Icons.phone_in_talk_rounded, size: 16, color: Colors.greenAccent),
                                                    ),
                                                  ),
                                                ],
                                              ],
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ],
                                ),
                              );
                            },
                          ),
                  ),
                ],
              ),
            ),
    );
  }

  Widget _buildFilterChip(String key, String label) {
    final isSel = _selectedFilter == key;
    return GestureDetector(
      onTap: () => setState(() => _selectedFilter = key),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
        decoration: BoxDecoration(
          color: isSel ? ManagerTheme.brandGold : Colors.black26,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: isSel ? ManagerTheme.brandGold : Colors.white24),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.bold,
            color: isSel ? Colors.black : Colors.white70,
          ),
        ),
      ),
    );
  }
}
