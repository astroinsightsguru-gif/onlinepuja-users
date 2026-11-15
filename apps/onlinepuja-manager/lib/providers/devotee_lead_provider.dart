import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import '../models/devotee_lead_model.dart';
import '../services/manager_api_service.dart';

class DevoteeLeadProvider extends ChangeNotifier {
  final ManagerApiService apiService;

  List<DevoteeLeadModel> _leads = [];
  List<DevoteeLeadModel> get leads => _leads;

  String _selectedStage = 'all';
  String get selectedStage => _selectedStage;

  bool _isLoading = false;
  bool get isLoading => _isLoading;

  String? _errorMessage;
  String? get errorMessage => _errorMessage;

  DevoteeLeadProvider({required this.apiService}) {
    fetchLeads();
  }

  List<DevoteeLeadModel> get filteredLeads {
    if (_selectedStage == 'all') return _leads;
    return _leads.where((l) => l.stage == _selectedStage).toList();
  }

  int get totalInquiries => _leads.where((l) => l.stage == 'inquiry').length;
  int get totalConsultations => _leads.where((l) => l.stage == 'consultation').length;
  int get totalProposals => _leads.where((l) => l.stage == 'proposal_sent').length;
  int get totalBooked => _leads.where((l) => l.stage == 'sankalp_booked').length;

  double get totalBookedValue => _leads
      .where((l) => l.stage == 'sankalp_booked')
      .fold(0.0, (sum, l) => sum + l.dakshinaValue);

  void setFilterStage(String stage) {
    _selectedStage = stage;
    notifyListeners();
  }

  Future<void> fetchLeads() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final res = await http.get(Uri.parse('${apiService.baseUrl}/leads')).timeout(const Duration(seconds: 8));
      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        if (decoded['leads'] is List) {
          _leads = (decoded['leads'] as List).map((i) => DevoteeLeadModel.fromJson(Map<String, dynamic>.from(i))).toList();
        }
      }
    } catch (e) {
      _errorMessage = 'Could not sync leads from server ($e)';
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<bool> updateLeadStage(String leadId, String newStage) async {
    final idx = _leads.indexWhere((l) => l.id == leadId);
    if (idx != -1) {
      final old = _leads[idx];
      _leads[idx] = DevoteeLeadModel(
        id: old.id,
        name: old.name,
        phone: old.phone,
        email: old.email,
        city: old.city,
        gotra: old.gotra,
        rashi: old.rashi,
        pujaInterest: old.pujaInterest,
        dakshinaValue: old.dakshinaValue,
        stage: newStage,
        source: old.source,
        assignedManager: old.assignedManager,
        score: old.score,
        createdAt: old.createdAt,
        lastContactAt: DateTime.now(),
        nextFollowUpAt: old.nextFollowUpAt,
        tags: old.tags,
        notes: old.notes,
        lastChatSummary: old.lastChatSummary,
      );
      notifyListeners();

      try {
        await http.post(
          Uri.parse('${apiService.baseUrl}/leads/$leadId/stage'),
          headers: {'Content-Type': 'application/json'},
          body: jsonEncode({'stage': newStage}),
        ).timeout(const Duration(seconds: 6));
        return true;
      } catch (_) {
        return false;
      }
    }
    return false;
  }

  Future<bool> addLeadNote(String leadId, String note) async {
    final idx = _leads.indexWhere((l) => l.id == leadId);
    if (idx != -1) {
      final old = _leads[idx];
      final updatedNotes = List<String>.from(old.notes)..insert(0, note);
      _leads[idx] = DevoteeLeadModel(
        id: old.id,
        name: old.name,
        phone: old.phone,
        email: old.email,
        city: old.city,
        gotra: old.gotra,
        rashi: old.rashi,
        pujaInterest: old.pujaInterest,
        dakshinaValue: old.dakshinaValue,
        stage: old.stage,
        source: old.source,
        assignedManager: old.assignedManager,
        score: old.score,
        createdAt: old.createdAt,
        lastContactAt: DateTime.now(),
        nextFollowUpAt: old.nextFollowUpAt,
        tags: old.tags,
        notes: updatedNotes,
        lastChatSummary: old.lastChatSummary,
      );
      notifyListeners();

      try {
        await http.post(
          Uri.parse('${apiService.baseUrl}/leads/$leadId/note'),
          headers: {'Content-Type': 'application/json'},
          body: jsonEncode({'note': note}),
        ).timeout(const Duration(seconds: 6));
        return true;
      } catch (_) {
        return false;
      }
    }
    return false;
  }
}
