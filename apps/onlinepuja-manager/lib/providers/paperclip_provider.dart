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
  List<Map<String, dynamic>> _companies = [];
  List<Map<String, dynamic>> _activities = [];
  List<Map<String, dynamic>> _reports = [];

  String _selectedCompanyId = 'fcb854e2-4cff-4d8f-a0e3-587e5f1cfc8d';
  String _selectedCompanyName = 'OnlinePuja.live';

  bool _isLoading = false;
  bool _isLoadingActivities = false;
  bool _isLoadingReports = false;
  bool _isDispatching = false;
  String? _errorMessage;
  String _statusFilter = 'all';

  PaperclipOverviewModel? get overview => _overview;
  List<PaperclipAgentModel> get agents => _agents;
  List<Map<String, dynamic>> get companies => _companies;
  List<Map<String, dynamic>> get activities => _activities;
  List<Map<String, dynamic>> get reports => _reports;
  String get selectedCompanyId => _selectedCompanyId;
  String get selectedCompanyName => _selectedCompanyName;

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
  bool get isLoadingActivities => _isLoadingActivities;
  bool get isLoadingReports => _isLoadingReports;
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

  Future<void> switchCompany(String companyId, String companyName) async {
    if (_selectedCompanyId == companyId) return;
    _selectedCompanyId = companyId;
    _selectedCompanyName = companyName;
    notifyListeners();
    await fetchAll();
    await fetchActivities();
    await fetchReports();
  }

  Future<void> fetchCompanies() async {
    try {
      final list = await _apiService.fetchPaperclipCompanies();
      if (list.isNotEmpty) {
        _companies = list;
        // Verify selected company exists or default to first
        final found = _companies.any((c) => c['id'] == _selectedCompanyId);
        if (!found && _companies.isNotEmpty) {
          _selectedCompanyId = _companies.first['id'] as String;
          _selectedCompanyName = _companies.first['name'] as String;
        } else if (found) {
          final current = _companies.firstWhere((c) => c['id'] == _selectedCompanyId);
          _selectedCompanyName = current['name'] as String? ?? _selectedCompanyName;
        }
        notifyListeners();
      }
    } catch (_) {}
  }

  Future<bool> createCompany(String name, String description) async {
    final ok = await _apiService.createPaperclipCompany(name, description);
    if (ok) {
      await fetchCompanies();
    }
    return ok;
  }

  Future<void> fetchActivities() async {
    _isLoadingActivities = true;
    notifyListeners();
    try {
      _activities = await _apiService.fetchPaperclipActivity(companyId: _selectedCompanyId);
    } catch (_) {
      _activities = [];
    } finally {
      _isLoadingActivities = false;
      notifyListeners();
    }
  }

  Future<void> fetchReports() async {
    _isLoadingReports = true;
    notifyListeners();
    try {
      _reports = await _apiService.fetchPaperclipReports(companyId: _selectedCompanyId);
    } catch (_) {
      _reports = [];
    } finally {
      _isLoadingReports = false;
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
      // 1. Fetch companies list if empty
      if (_companies.isEmpty) {
        await fetchCompanies();
      }

      // 2. Fetch overview for selected company
      final overviewRes = await _apiService.fetchPaperclipOverview(companyId: _selectedCompanyId);
      if (overviewRes != null && overviewRes['ok'] == true) {
        _overview = PaperclipOverviewModel.fromJson(overviewRes);
        _agents = _overview!.agents;
        _issues = _overview!.recentIssues;
      } else {
        final agentsRaw = await _apiService.fetchPaperclipAgents(companyId: _selectedCompanyId);
        if (agentsRaw != null) {
          _agents = agentsRaw
              .whereType<Map<String, dynamic>>()
              .map((a) => PaperclipAgentModel.fromJson(a))
              .toList();
        }

        final issuesRaw = await _apiService.fetchPaperclipIssues(companyId: _selectedCompanyId);
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
        await fetchActivities();
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

  Future<bool> unblockMission(String issueId) async {
    try {
      final success = await _apiService.unblockPaperclipIssue(issueId);
      if (success) {
        await fetchAll(silent: true);
        await fetchActivities();
        return true;
      }
    } catch (_) {}
    return false;
  }
}
