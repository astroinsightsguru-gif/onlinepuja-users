import 'package:flutter/foundation.dart';
import '../models/paperclip_model.dart';
import '../services/manager_api_service.dart';

class PaperclipProvider extends ChangeNotifier {
  final ManagerApiService _apiService;

  PaperclipProvider({ManagerApiService? apiService})
      : _apiService = apiService ?? ManagerApiService();

  PaperclipOverviewModel? _overview;
  List<PaperclipAgentModel> _agents = [];
  List<PaperclipIssueModel> _issues = [];
  bool _isLoading = false;
  bool _isDispatching = false;
  String? _errorMessage;
  String _statusFilter = 'all';

  PaperclipOverviewModel? get overview => _overview;
  List<PaperclipAgentModel> get agents => _agents;
  List<PaperclipIssueModel> get issues {
    if (_statusFilter == 'all') {
      return _issues;
    }
    return _issues.where((i) {
      if (_statusFilter == 'active') {
        return i.isTodo || i.isInProgress;
      } else if (_statusFilter == 'done') {
        return i.isDone;
      } else if (_statusFilter == 'blocked') {
        return i.isBlocked;
      }
      return i.status == _statusFilter;
    }).toList();
  }

  bool get isLoading => _isLoading;
  bool get isDispatching => _isDispatching;
  String? get errorMessage => _errorMessage;
  String get statusFilter => _statusFilter;

  PaperclipAgentModel? get chiefOfStaff {
    if (_overview?.chiefOfStaff != null) {
      return _overview!.chiefOfStaff;
    }
    try {
      return _agents.firstWhere((a) => a.isChiefOfStaff);
    } catch (_) {
      return null;
    }
  }

  void setStatusFilter(String filter) {
    if (_statusFilter != filter) {
      _statusFilter = filter;
      notifyListeners();
    }
  }

  Future<void> fetchAll({bool silent = false}) async {
    if (!silent) {
      _isLoading = true;
      _errorMessage = null;
      notifyListeners();
    }

    try {
      final overviewRes = await _apiService.fetchPaperclipOverview();
      if (overviewRes != null && overviewRes['ok'] == true) {
        _overview = PaperclipOverviewModel.fromJson(overviewRes);
        _agents = _overview!.agents;
        _issues = _overview!.recentIssues;
      } else {
        final agentsRaw = await _apiService.fetchPaperclipAgents();
        if (agentsRaw != null) {
          _agents = agentsRaw
              .whereType<Map<String, dynamic>>()
              .map((a) => PaperclipAgentModel.fromJson(a))
              .toList();
        }

        final issuesRaw = await _apiService.fetchPaperclipIssues();
        if (issuesRaw != null) {
          _issues = issuesRaw
              .whereType<Map<String, dynamic>>()
              .map((i) => PaperclipIssueModel.fromJson(i))
              .toList();
        }
      }
      _errorMessage = null;
    } catch (e) {
      _errorMessage = e.toString();
    } finally {
      if (!silent) {
        _isLoading = false;
      }
      notifyListeners();
    }
  }

  Future<bool> dispatchMission({
    required String title,
    String description = '',
    String priority = 'high',
    String? assigneeId,
  }) async {
    _isDispatching = true;
    notifyListeners();

    try {
      final res = await _apiService.dispatchPaperclipMission(
        title: title,
        description: description,
        priority: priority,
        assigneeId: assigneeId,
      );

      if (res != null && res['ok'] == true) {
        await fetchAll(silent: true);
        _isDispatching = false;
        notifyListeners();
        return true;
      }
    } catch (e) {
      _errorMessage = e.toString();
    }

    _isDispatching = false;
    notifyListeners();
    return false;
  }

  Future<bool> toggleAgentPause(String agentId, bool pause, {String? reason}) async {
    try {
      final success = await _apiService.togglePaperclipAgentPause(
        agentId: agentId,
        pause: pause,
        reason: reason,
      );
      if (success) {
        await fetchAll(silent: true);
        return true;
      }
    } catch (_) {}
    return false;
  }
}
