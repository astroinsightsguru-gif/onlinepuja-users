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
}
