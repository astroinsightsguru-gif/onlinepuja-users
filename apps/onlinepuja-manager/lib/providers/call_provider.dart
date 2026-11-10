import 'dart:async';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../models/call_log_model.dart';
import '../services/manager_api_service.dart';

enum CallState { idle, ringing, dialing, connected, ended }

class CallProvider extends ChangeNotifier {
  final ManagerApiService apiService;

  CallState _callState = CallState.idle;
  CallState get callState => _callState;

  String _activePhoneNumber = '';
  String get activePhoneNumber => _activePhoneNumber;

  String _activeCallerName = '';
  String get activeCallerName => _activeCallerName;

  String? _activePujaInterest;
  String? get activePujaInterest => _activePujaInterest;

  String? _activeGotra;
  String? get activeGotra => _activeGotra;

  int _callDurationSeconds = 0;
  int get callDurationSeconds => _callDurationSeconds;

  Timer? _durationTimer;

  List<CallLogModel> _callLogs = [];
  List<CallLogModel> get callLogs => _callLogs;

  List<Map<String, dynamic>> _pendingQueue = [];
  List<Map<String, dynamic>> get pendingQueue => _pendingQueue;

  CallLogModel? _lastCompletedCall;
  CallLogModel? get lastCompletedCall => _lastCompletedCall;

  // Power sequential dialer
  List<Map<String, dynamic>> _powerDialerQueue = [];
  int _powerDialerIndex = 0;
  bool _isPowerDialerActive = false;
  bool get isPowerDialerActive => _isPowerDialerActive;
  int get powerDialerIndex => _powerDialerIndex;
  int get powerDialerTotal => _powerDialerQueue.length;

  CallProvider({required this.apiService}) {
    _initSampleData();
  }

  void _initSampleData() {
    _pendingQueue = [
      {
        'id': 'lead_101',
        'name': 'Rajesh Sharma',
        'phone': '9810123456',
        'gotra': 'Bharadwaj',
        'puja_interest': 'Kashi Vishwanath Rudrabhishek',
        'dakshina': 5100,
        'source': 'Meta Ads',
      },
      {
        'id': 'lead_102',
        'name': 'Pooja Agarwal',
        'phone': '9820987654',
        'gotra': 'Garg',
        'puja_interest': 'Mahamrityunjaya Jaap (21,000 Chants)',
        'dakshina': 11000,
        'source': 'WhatsApp',
      },
      {
        'id': 'lead_103',
        'name': 'Amitabh Verma',
        'phone': '9935112233',
        'gotra': 'Vashistha',
        'puja_interest': 'Ujjain Mahakal Bhasma Aarti Sankalp',
        'dakshina': 3500,
        'source': 'Google Search',
      },
    ];

    _callLogs = [
      CallLogModel(
        id: 'c_1',
        phone: '9810123456',
        customerName: 'Rajesh Sharma',
        direction: CallDirection.outbound,
        status: 'completed',
        timestamp: DateTime.now().subtract(const Duration(minutes: 25)),
        durationSeconds: 195,
        pujaInterest: 'Rudrabhishek',
        gotra: 'Bharadwaj',
        notes: 'Requested 5 Brahmins for Shravan Somwar. Quote sent.',
      ),
      CallLogModel(
        id: 'c_2',
        phone: '9876543210',
        customerName: 'Sunita Mishra',
        direction: CallDirection.inbound,
        status: 'completed',
        timestamp: DateTime.now().subtract(const Duration(hours: 2)),
        durationSeconds: 310,
        pujaInterest: 'Navgrah Shanti',
        gotra: 'Kashyap',
        notes: 'Sankalp confirmed. Razorpay payment received ₹7,100.',
      ),
    ];
  }

  Future<void> startCall({
    required String phoneNumber,
    required String customerName,
    String? pujaInterest,
    String? gotra,
  }) async {
    _activePhoneNumber = phoneNumber.replaceAll(RegExp(r'[^0-9+]'), '');
    _activeCallerName = customerName;
    _activePujaInterest = pujaInterest ?? 'Vedic Puja';
    _activeGotra = gotra ?? 'Kashyap';

    _callState = CallState.dialing;
    _callDurationSeconds = 0;
    _lastCompletedCall = null;
    notifyListeners();

    // Trigger phone call intent
    final uri = Uri.parse('tel:$_activePhoneNumber');
    try {
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      }
    } catch (_) {}

    // Transition to connected state after dialer opens
    Future.delayed(const Duration(seconds: 2), () {
      _callState = CallState.connected;
      _startDurationTimer();
      notifyListeners();
    });
  }

  void _startDurationTimer() {
    _durationTimer?.cancel();
    _durationTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      _callDurationSeconds++;
      notifyListeners();
    });
  }

  void endCall({String? notes, String? status = 'completed'}) {
    _durationTimer?.cancel();
    _callState = CallState.ended;

    final completed = CallLogModel(
      id: 'call_${DateTime.now().millisecondsSinceEpoch}',
      phone: _activePhoneNumber,
      customerName: _activeCallerName,
      direction: CallDirection.outbound,
      status: status ?? 'completed',
      timestamp: DateTime.now(),
      durationSeconds: _callDurationSeconds,
      pujaInterest: _activePujaInterest,
      gotra: _activeGotra,
      notes: notes,
    );

    _lastCompletedCall = completed;
    _callLogs.insert(0, completed);

    // Remove from pending queue if present
    _pendingQueue.removeWhere((item) => item['phone'] == _activePhoneNumber);

    notifyListeners();

    // Reset after a brief pause
    Future.delayed(const Duration(milliseconds: 600), () {
      _callState = CallState.idle;
      _callDurationSeconds = 0;
      notifyListeners();
    });
  }

  void clearLastCompletedCall() {
    _lastCompletedCall = null;
    notifyListeners();
  }

  // Power dialer controls
  void startPowerDialer(List<Map<String, dynamic>> queue) {
    if (queue.isEmpty) return;
    _powerDialerQueue = List.from(queue);
    _powerDialerIndex = 0;
    _isPowerDialerActive = true;
    _dialNextInPowerQueue();
  }

  void stopPowerDialer() {
    _isPowerDialerActive = false;
    _powerDialerQueue.clear();
    _powerDialerIndex = 0;
    notifyListeners();
  }

  void _dialNextInPowerQueue() {
    if (!_isPowerDialerActive || _powerDialerIndex >= _powerDialerQueue.length) {
      stopPowerDialer();
      return;
    }

    final lead = _powerDialerQueue[_powerDialerIndex];
    startCall(
      phoneNumber: lead['phone'] ?? '',
      customerName: lead['name'] ?? 'Devotee',
      pujaInterest: lead['puja_interest'],
      gotra: lead['gotra'],
    );
  }

  void nextPowerDialerLead() {
    _powerDialerIndex++;
    if (_powerDialerIndex < _powerDialerQueue.length) {
      _dialNextInPowerQueue();
    } else {
      stopPowerDialer();
    }
  }
}
