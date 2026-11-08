import 'package:flutter/material.dart';
import 'package:onlinepuja_manager/services/manager_api_service.dart';
import 'package:onlinepuja_manager/theme/manager_theme.dart';

class BannerPujaStudioScreen extends StatefulWidget {
  const BannerPujaStudioScreen({super.key});

  @override
  State<BannerPujaStudioScreen> createState() => _BannerPujaStudioScreenState();
}

class _BannerPujaStudioScreenState extends State<BannerPujaStudioScreen> {
  final ManagerApiService _api = ManagerApiService();

  bool _isLoading = true;
  List<Map<String, dynamic>> _liveBanners = [];
  List<Map<String, dynamic>> _livePujas = [];
  List<Map<String, dynamic>> _festivals = [];

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);

    try {
      final bannerPujaData = await _api.fetchBannersAndPujas();
      final festList = await _api.fetchFestivals();

      if (mounted) {
        setState(() {
          _liveBanners = bannerPujaData['banners'] as List<Map<String, dynamic>>? ?? [];
          _livePujas = bannerPujaData['pujas'] as List<Map<String, dynamic>>? ?? [];
          _festivals = festList;
          _isLoading = false;
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  void _showAddBannerDialog() {
    final titleCtrl = TextEditingController(text: 'Shri Kashi Vishwanath Somwar Rudrabhishek');
    final imgCtrl = TextEditingController(text: 'https://images.unsplash.com/photo-1544816155-12df9643f363?auto=format&fit=crop&w=600&q=80');

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: ManagerTheme.surfaceDark,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) => Padding(
        padding: EdgeInsets.fromLTRB(16, 20, 16, MediaQuery.of(ctx).viewInsets.bottom + 20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Upload New Hero Banner', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Colors.white)),
            const SizedBox(height: 12),
            TextField(
              controller: titleCtrl,
              decoration: InputDecoration(
                labelText: 'Banner Title / Campaign Name',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: imgCtrl,
              decoration: InputDecoration(
                labelText: 'Image URL or Cloud Asset',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                suffixIcon: IconButton(
                  icon: const Icon(Icons.photo_library_rounded, color: ManagerTheme.brandGold),
                  tooltip: 'Load Dev Deepawali Preset',
                  onPressed: () {
                    imgCtrl.text = 'https://images.unsplash.com/photo-1544816155-12df9643f363?auto=format&fit=crop&w=1200&q=80';
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('✓ Sacred Dev Deepawali high-res preset loaded!')),
                    );
                  },
                ),
              ),
            ),
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
                icon: const Icon(Icons.cloud_upload_rounded),
                label: const Text('Publish Banner to Live App', style: TextStyle(fontWeight: FontWeight.bold)),
                onPressed: () async {
                  final bannerTitle = titleCtrl.text;
                  final bannerImg = imgCtrl.text;
                  setState(() {
                    _liveBanners.insert(0, {
                      'title': bannerTitle,
                      'dates': 'Active Now',
                      'active': true,
                      'image': bannerImg,
                    });
                  });
                  Navigator.pop(ctx);
                  await _api.saveBanner(
                    bannerImage: bannerImg,
                    fromDate: DateTime.now().toIso8601String().split('T').first,
                    toDate: DateTime.now().add(const Duration(days: 30)).toIso8601String().split('T').first,
                  );
                  if (mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('✓ Banner saved and published to live app!')),
                    );
                  }
                },
              ),
            ),
          ],
        ),
      ),
    ).whenComplete(() {
      titleCtrl.dispose();
      imgCtrl.dispose();
    });
  }

  void _editPujaPrice(Map<String, dynamic> p) {
    final priceCtrl = TextEditingController(text: (p['price'] ?? 0).toString());

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: ManagerTheme.surfaceDark,
        title: Text('Edit Price for ${p['title'] ?? 'Puja'}', style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold)),
        content: TextField(
          controller: priceCtrl,
          keyboardType: TextInputType.number,
          decoration: const InputDecoration(labelText: 'Price in INR (₹)'),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: ManagerTheme.brandGold, foregroundColor: Colors.black),
            onPressed: () async {
              final newPrice = double.tryParse(priceCtrl.text) ?? ((p['price'] as num?)?.toDouble() ?? 0.0);
              final oldPrice = p['price'];
              setState(() {
                p['price'] = newPrice.toInt();
              });
              Navigator.pop(ctx);

              final pujaId = (p['id'] as num?)?.toInt() ?? int.tryParse(p['id']?.toString() ?? '') ?? 0;
              bool success = false;
              if (pujaId > 0) {
                success = await _api.updatePujaPrice(pujaId, newPrice);
              }

              if (mounted) {
                if (success) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text('✓ Price updated to ₹${newPrice.toInt()} and published live!'),
                      backgroundColor: ManagerTheme.brandEmerald,
                    ),
                  );
                } else {
                  setState(() {
                    p['price'] = oldPrice;
                  });
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Failed to update puja price on server. Reverted.'),
                      backgroundColor: Colors.redAccent,
                    ),
                  );
                }
              }
            },
            child: const Text('Save Price'),
          ),
        ],
      ),
    ).whenComplete(() {
      priceCtrl.dispose();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Banner & Puja Studio'),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            icon: const Icon(Icons.refresh_rounded),
            onPressed: _loadData,
          ),
          IconButton(
            tooltip: 'Add Banner',
            icon: const Icon(Icons.add_photo_alternate_rounded, color: ManagerTheme.brandGold),
            onPressed: _showAddBannerDialog,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: ManagerTheme.brandGold))
          : RefreshIndicator(
              onRefresh: _loadData,
              color: ManagerTheme.brandGold,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // 1. Live Hero Banners Section
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Live App Banners',
                          style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Colors.white),
                        ),
                        TextButton.icon(
                          icon: const Icon(Icons.add_rounded, size: 16, color: ManagerTheme.brandGold),
                          label: const Text('New Banner', style: TextStyle(fontSize: 12, color: ManagerTheme.brandGold)),
                          onPressed: _showAddBannerDialog,
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    if (_liveBanners.isEmpty)
                      Container(
                        height: 120,
                        width: double.infinity,
                        decoration: BoxDecoration(
                          color: ManagerTheme.surfaceDark,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: Colors.white12),
                        ),
                        child: const Center(
                          child: Text(
                            'No hero banners published yet. Tap "+ Banner" to upload one.',
                            style: TextStyle(color: Colors.white60, fontSize: 12),
                          ),
                        ),
                      )
                    else
                      SizedBox(
                        height: 160,
                        child: ListView.separated(
                        scrollDirection: Axis.horizontal,
                        itemCount: _liveBanners.length,
                        separatorBuilder: (_, __) => const SizedBox(width: 12),
                        itemBuilder: (context, i) {
                          final b = _liveBanners[i];
                          final imgUrl = (b['image'] ?? b['image_url'] ?? '').toString();
                          final title = (b['title'] ?? 'Featured Campaign').toString();
                          final dates = (b['dates'] ?? 'Active Now').toString();

                          return Container(
                            width: 270,
                            decoration: BoxDecoration(
                              borderRadius: BorderRadius.circular(16),
                              color: ManagerTheme.surfaceDark,
                              border: Border.all(color: Colors.white12),
                              image: imgUrl.isNotEmpty
                                  ? DecorationImage(
                                      image: NetworkImage(imgUrl),
                                      fit: BoxFit.cover,
                                    )
                                  : null,
                            ),
                            child: Stack(
                              children: [
                                Container(
                                  decoration: BoxDecoration(
                                    borderRadius: BorderRadius.circular(16),
                                    gradient: LinearGradient(
                                      colors: [
                                        Colors.black.withValues(alpha: 0.1),
                                        Colors.black.withValues(alpha: 0.85),
                                      ],
                                      begin: Alignment.topCenter,
                                      end: Alignment.bottomCenter,
                                    ),
                                  ),
                                ),
                                Padding(
                                  padding: const EdgeInsets.all(12.0),
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    mainAxisAlignment: MainAxisAlignment.end,
                                    children: [
                                      Text(
                                        title,
                                        style: const TextStyle(
                                          color: Colors.white,
                                          fontWeight: FontWeight.w800,
                                          fontSize: 13,
                                        ),
                                        maxLines: 2,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                      const SizedBox(height: 2),
                                      Text(
                                        dates,
                                        style: const TextStyle(color: Colors.white70, fontSize: 10),
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
                    const SizedBox(height: 24),

                    // 2. Upcoming Festival Campaigns Milestone Card
                    if (_festivals.isNotEmpty) ...[
                      const Row(
                        children: [
                          Icon(Icons.stars_rounded, color: ManagerTheme.brandGold, size: 20),
                          SizedBox(width: 8),
                          Text(
                            'Festival Campaign Countdown',
                            style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Colors.white),
                          ),
                        ],
                      ),
                      const SizedBox(height: 10),
                      ..._festivals.take(2).map((fest) {
                        final name = fest['name'] ?? 'Festival';
                        final date = fest['date'] ?? 'Upcoming';
                        final daysLeft = fest['days_left'] ?? 0;
                        final status = fest['status'] ?? 'Scheduled';

                        return Container(
                          margin: const EdgeInsets.only(bottom: 10),
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            gradient: const LinearGradient(
                              colors: [
                                Color(0xFF1E293B),
                                ManagerTheme.surfaceDark,
                              ],
                            ),
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.3)),
                          ),
                          child: Row(
                            children: [
                              Container(
                                width: 44,
                                height: 44,
                                decoration: BoxDecoration(
                                  color: ManagerTheme.brandGold.withValues(alpha: 0.15),
                                  shape: BoxShape.circle,
                                ),
                                child: const Center(
                                  child: Text('🪔', style: TextStyle(fontSize: 20)),
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      name,
                                      style: const TextStyle(
                                        color: Colors.white,
                                        fontWeight: FontWeight.w700,
                                        fontSize: 14,
                                      ),
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      'Date: $date · $status',
                                      style: const TextStyle(color: Colors.white60, fontSize: 11),
                                    ),
                                  ],
                                ),
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                                decoration: BoxDecoration(
                                  color: ManagerTheme.brandAmber.withValues(alpha: 0.2),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: Text(
                                  '$daysLeft Days Left',
                                  style: const TextStyle(
                                    color: ManagerTheme.brandAmber,
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        );
                      }),
                      const SizedBox(height: 16),
                    ],

                    // 3. Active Festive Pujas & Prices Section
                    const Text(
                      'Manage Live Seasonal Pujas',
                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Colors.white),
                    ),
                    const SizedBox(height: 8),
                    if (_livePujas.isEmpty)
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(24),
                        decoration: BoxDecoration(
                          color: ManagerTheme.surfaceDark,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: Colors.white12),
                        ),
                        child: const Center(
                          child: Text(
                            'No bookable pujas found in catalogue.',
                            style: TextStyle(color: Colors.white60, fontSize: 12),
                          ),
                        ),
                      )
                    else
                      ListView.separated(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount: _livePujas.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 10),
                      itemBuilder: (context, i) {
                        final p = _livePujas[i];
                        final title = (p['title'] ?? p['name'] ?? 'Sacred Puja').toString();
                        final place = (p['place'] ?? p['venue'] ?? 'Holy Sanctum').toString();
                        final slots = (p['slots'] ?? 'Available').toString();
                        final price = p['price'] ?? 1500;

                        return Container(
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: ManagerTheme.surfaceDark,
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: Colors.white.withValues(alpha: 0.08)),
                          ),
                          child: Row(
                            children: [
                              CircleAvatar(
                                radius: 20,
                                backgroundColor: ManagerTheme.brandAmber.withValues(alpha: 0.2),
                                child: const Text('🛕', style: TextStyle(fontSize: 16)),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      title,
                                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 13),
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      "$place · $slots",
                                      style: const TextStyle(color: Colors.white60, fontSize: 11),
                                    ),
                                  ],
                                ),
                              ),
                              const SizedBox(width: 8),
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  Text(
                                    '₹$price',
                                    style: const TextStyle(
                                      color: ManagerTheme.brandGold,
                                      fontWeight: FontWeight.w900,
                                      fontSize: 15,
                                    ),
                                  ),
                                  GestureDetector(
                                    onTap: () => _editPujaPrice(p),
                                    child: const Padding(
                                      padding: EdgeInsets.only(top: 2),
                                      child: Text(
                                        'Edit Price',
                                        style: TextStyle(color: Colors.blueAccent, fontSize: 11, fontWeight: FontWeight.bold),
                                      ),
                                    ),
                                  ),
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
}
