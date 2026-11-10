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
    } catch (_) {
      // Graceful sample fallback for offline / mock testing
      if (_leads.isEmpty) {
        _leads = [
          DevoteeLeadModel(
            id: 'ld_1',
            name: 'Rajesh Sharma',
            phone: '9810123456',
            city: 'Delhi',
            gotra: 'Bharadwaj',
            pujaInterest: 'Kashi Vishwanath Rudrabhishek',
            dakshinaValue: 5100,
            stage: 'inquiry',
            source: 'meta_ads',
            score: 85,
            createdAt: DateTime.now().subtract(const Duration(hours: 3)),
            notes: ['Requested video darshan with family gotra on Shravan Somwar.'],
          ),
          DevoteeLeadModel(
            id: 'ld_2',
            name: 'Pooja Agarwal',
            phone: '9820987654',
            city: 'Mumbai',
            gotra: 'Garg',
            pujaInterest: 'Mahamrityunjaya Jaap (21,000 Chants)',
            dakshinaValue: 11000,
            stage: 'consultation',
            source: 'whatsapp',
            score: 92,
            createdAt: DateTime.now().subtract(const Duration(hours: 8)),
            notes: ['Discussed auspicious muhurat with Head Pandit.'],
          ),
          DevoteeLeadModel(
            id: 'ld_3',
            name: 'Vikramaditya Roy',
            phone: '9748112233',
            city: 'Kolkata',
            gotra: 'Sandilya',
            pujaInterest: 'Kamakhya Devi Special Tantrokta Puja',
            dakshinaValue: 15100,
            stage: 'proposal_sent',
            source: 'google_ads',
            score: 88,
            createdAt: DateTime.now().subtract(const Duration(days: 1)),
            notes: ['Detailed itemized quote sent via WhatsApp.'],
          ),
          DevoteeLeadModel(
            id: 'ld_4',
            name: 'Sunita Mishra',
            phone: '9876543210',
            city: 'Lucknow',
            gotra: 'Kashyap',
            pujaInterest: 'Navgrah Shanti Yagya',
            dakshinaValue: 7100,
            stage: 'sankalp_booked',
            source: 'whatsapp',
            score: 98,
            createdAt: DateTime.now().subtract(const Duration(days: 2)),
            notes: ['Dakshina paid online. Sankalp booked for next Tuesday.'],
          ),
        ];
      }
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
