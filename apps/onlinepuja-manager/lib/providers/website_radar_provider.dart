import 'dart:async';
import 'package:flutter/material.dart';
import '../models/website_visitor_model.dart';
import '../services/manager_api_service.dart';

class WebsiteRadarProvider extends ChangeNotifier {
  final ManagerApiService _apiService;
  final List<WebsiteVisitor> _visitors = [];
  Timer? _ticker;
  Timer? _remotePoller;
  bool _isLoading = false;
  bool _isLiveSynced = false;
  bool _disposed = false;

  WebsiteRadarProvider({ManagerApiService? apiService})
      : _apiService = apiService ?? ManagerApiService() {
    _initDefaultDevotees();
    _startRemotePolling();
    _startTicker();
  }

  @override
  void dispose() {
    _disposed = true;
    _ticker?.cancel();
    _remotePoller?.cancel();
    super.dispose();
  }

  List<WebsiteVisitor> get visitors => List.unmodifiable(_visitors);
  bool get isLoading => _isLoading;
  bool get isLiveSynced => _isLiveSynced;
  int get activeCount => _visitors.where((v) => v.isOnline).length;
  int get hotCount => _visitors.where((v) => v.intentScore >= 75).length;

  void _startRemotePolling() {
    fetchLiveVisitors();
    _remotePoller = Timer.periodic(const Duration(seconds: 10), (_) {
      fetchLiveVisitors(silent: true);
    });
  }

  void _startTicker() {
    // Ticker to increment visit duration every 3 seconds for active devotees
    _ticker = Timer.periodic(const Duration(seconds: 3), (_) {
      if (_disposed || _visitors.isEmpty) return;

      bool changed = false;
      for (int i = 0; i < _visitors.length; i++) {
        if (_visitors[i].isOnline) {
          _visitors[i] = _visitors[i].copyWith(
            durationSeconds: _visitors[i].durationSeconds + 3,
            scrollDepth: (_visitors[i].scrollDepth + 1).clamp(0, 100),
          );
          changed = true;
        }
      }

      if (changed) {
        notifyListeners();
      }
    });
  }

  Future<void> fetchLiveVisitors({bool silent = false}) async {
    if (!silent) {
      _isLoading = true;
      notifyListeners();
    }

    try {
      final remoteList = await _apiService.fetchLiveRadarVisitors();
      if (remoteList.isNotEmpty) {
        _isLiveSynced = true;
        _visitors.clear();
        for (final r in remoteList) {
          _visitors.add(WebsiteVisitor.fromJson(r));
        }
        if (!_disposed) {
          notifyListeners();
        }
      }
    } catch (_) {}

    if (!silent) {
      _isLoading = false;
      if (!_disposed) notifyListeners();
    }
  }

  void _initDefaultDevotees() {
    final now = DateTime.now();
    _visitors.addAll([
      WebsiteVisitor(
        id: 'dev_mum_01',
        ip: '103.21.x.x',
        city: 'Mumbai',
        country: 'India',
        flag: '🇮🇳',
        currentPage: '/puja/kashi-vishwanath-rudrabhishek',
        pageTitle: 'Kashi Vishwanath Rudrabhishek with Ganga Aarti',
        referrer: 'Google Search / "rudrabhishek varanasi"',
        durationSeconds: 185,
        scrollDepth: 88,
        intentScore: 92,
        intentLabel: 'High Intent (Checkout)',
        device: 'Android Mobile',
        isOnline: true,
        lastActive: now,
        devoteeName: 'Rajesh Sharma',
        devoteePhone: '+91 98201 44521',
        gotra: 'Kashyap',
      ),
      WebsiteVisitor(
        id: 'dev_del_02',
        ip: '49.36.x.x',
        city: 'New Delhi',
        country: 'India',
        flag: '🇮🇳',
        currentPage: '/kundli',
        pageTitle: 'Free Kundli Analysis & Manglik Dosha Shanti',
        referrer: 'Meta Instagram Ad / Devotional Reel',
        durationSeconds: 140,
        scrollDepth: 74,
        intentScore: 84,
        intentLabel: 'Consultation Seeker',
        device: 'iOS Mobile',
        isOnline: true,
        lastActive: now,
        devoteeName: 'Ananya Verma',
        devoteePhone: '+91 99102 88310',
        gotra: 'Bhardwaj',
      ),
      WebsiteVisitor(
        id: 'dev_blr_03',
        ip: '106.51.x.x',
        city: 'Bengaluru',
        country: 'India',
        flag: '🇮🇳',
        currentPage: '/astromall/siddh-rudraksha',
        pageTitle: 'Certified Siddh 5-Mukhi Rudraksha & Temple Chadhawa',
        referrer: 'WhatsApp Broadcast Link',
        durationSeconds: 95,
        scrollDepth: 62,
        intentScore: 78,
        intentLabel: 'Prasadam Buyer',
        device: 'Desktop Chrome',
        isOnline: true,
        lastActive: now,
        devoteeName: 'Venkatesh Iyer',
        devoteePhone: '+91 98450 12890',
        gotra: 'Vatsa',
      ),
      WebsiteVisitor(
        id: 'dev_vns_04',
        ip: '14.139.x.x',
        city: 'Varanasi',
        country: 'India',
        flag: '🇮🇳',
        currentPage: '/panchang/dev-deepawali',
        pageTitle: 'Dev Deepawali 2026 Shubh Muhurat & Ghat Deepdan',
        referrer: 'Direct / Organic',
        durationSeconds: 65,
        scrollDepth: 50,
        intentScore: 70,
        intentLabel: 'Festival Explorer',
        device: 'Mobile Browser',
        isOnline: true,
        lastActive: now,
      ),
    ]);
  }
}
