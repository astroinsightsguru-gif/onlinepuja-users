import 'dart:async';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../models/call_log_model.dart';
import '../../../providers/call_provider.dart';
import '../../../services/manager_api_service.dart';
import '../../../theme/manager_theme.dart';

class DialerScreen extends StatefulWidget {
  const DialerScreen({super.key});

  @override
  State<DialerScreen> createState() => _DialerScreenState();
}

class _DialerScreenState extends State<DialerScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final TextEditingController _numberController = TextEditingController();
  final ManagerApiService _apiService = ManagerApiService();

  Map<String, dynamic>? _matchedDevotee;
  bool _isSearchingDevotee = false;
  Timer? _searchDebounce;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
  }

  @override
  void dispose() {
    _searchDebounce?.cancel();
    _tabController.dispose();
    _numberController.dispose();
    super.dispose();
  }

  void _onDigitPress(String digit) {
    setState(() {
      _numberController.text += digit;
    });
    _lookupCallerId();
  }

  void _onBackspace() {
    final text = _numberController.text;
    if (text.isNotEmpty) {
      setState(() {
        _numberController.text = text.substring(0, text.length - 1);
      });
      _lookupCallerId();
    }
  }

  void _lookupCallerId() {
    final callProvider = Provider.of<CallProvider>(context, listen: false);
    final raw = _numberController.text.trim();
    final clean = raw.replaceAll(RegExp(r'[^0-9]'), '');

    if (clean.length < 3) {
      setState(() {
        _matchedDevotee = null;
        _isSearchingDevotee = false;
      });
      _searchDebounce?.cancel();
      return;
    }

    // 1. Instant Synchronous Match from Local Pending Queue & Call Logs
    Map<String, dynamic>? localMatch;
    for (final item in callProvider.pendingQueue) {
      final itemPhone = (item['phone'] ?? '').toString().replaceAll(RegExp(r'[^0-9]'), '');
      if (itemPhone.endsWith(clean) || clean.endsWith(itemPhone) || itemPhone.contains(clean)) {
        localMatch = {
          'name': item['name'] ?? 'Devotee',
          'phone': item['phone'] ?? clean,
          'gotra': item['gotra'] ?? 'Kashyap',
          'puja_interest': item['puja_interest'] ?? 'Vedic Puja Inquiry',
          'source': item['source'] ?? 'Lead Queue',
          'dakshina': item['dakshina'] != null ? '₹${item['dakshina']}' : '₹2,100',
        };
        break;
      }
    }

    if (localMatch == null) {
      for (final log in callProvider.callLogs) {
        final logPhone = log.phone.replaceAll(RegExp(r'[^0-9]'), '');
        if (logPhone.endsWith(clean) || clean.endsWith(logPhone)) {
          localMatch = {
            'name': log.customerName,
            'phone': log.phone,
            'gotra': log.gotra ?? 'Kashyap',
            'puja_interest': log.pujaInterest ?? 'Temple Consultation',
            'source': 'Past Call History',
            'dakshina': 'Repeat Devotee',
          };
          break;
        }
      }
    }

    if (localMatch != null) {
      setState(() {
        _matchedDevotee = localMatch;
        _isSearchingDevotee = false;
      });
      return;
    }

    // 2. Debounced Live CRM Search if length >= 4
    if (clean.length >= 4) {
      _searchDebounce?.cancel();
      setState(() => _isSearchingDevotee = true);

      _searchDebounce = Timer(const Duration(milliseconds: 350), () async {
        final devotee = await _apiService.lookupDevoteeByPhone(clean);
        if (mounted && _numberController.text.replaceAll(RegExp(r'[^0-9]'), '') == clean) {
          setState(() {
            _isSearchingDevotee = false;
            if (devotee != null) {
              _matchedDevotee = {
                'name': devotee['name'] ?? 'Devotee',
                'phone': devotee['phone'] ?? clean,
                'gotra': devotee['gotra'] ?? 'Kashyap',
                'puja_interest': 'OnlinePuja Patron',
                'source': devotee['city'] ?? 'CRM Devotee',
                'dakshina': devotee['total_gmv'] != null && devotee['total_gmv'] > 0
                    ? '₹${devotee['total_gmv']} GMV'
                    : '${devotee['orders_count'] ?? 0} Pujas',
              };
            } else {
              _matchedDevotee = null;
            }
          });
        }
      });
    } else {
      setState(() {
        _matchedDevotee = null;
        _isSearchingDevotee = false;
      });
    }
  }

  Future<void> _openDevoteeWhatsApp(String phone, String name) async {
    final cleanPhone = phone.replaceAll(RegExp(r'[^0-9]'), '');
    final uri = Uri.parse('https://wa.me/$cleanPhone?text=${Uri.encodeComponent('Pranam $name ji! 🙏 Greetings from OnlinePuja Temple Services.')}');
    try {
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      }
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    final callProvider = Provider.of<CallProvider>(context);

    return Scaffold(
      backgroundColor: ManagerTheme.backgroundDark,
      appBar: AppBar(
        backgroundColor: ManagerTheme.surfaceDark,
        elevation: 0,
        title: const Row(
          children: [
            Text('ॐ', style: TextStyle(color: ManagerTheme.brandGold, fontSize: 20, fontWeight: FontWeight.bold)),
            SizedBox(width: 8),
            Text('Devotee Smart Dialer', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
          ],
        ),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: ManagerTheme.brandGold,
          labelColor: ManagerTheme.brandGold,
          unselectedLabelColor: Colors.white60,
          labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
          tabs: [
            Tab(text: 'Pending Queue (${callProvider.pendingQueue.length})'),
            const Tab(text: 'Keypad'),
            Tab(text: 'History (${callProvider.callLogs.length})'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          // TAB 1: Pending Queue & Power Dialer
          _buildPendingQueue(callProvider),

          // TAB 2: Numpad & Live Caller ID Card
          _buildNumpad(callProvider),

          // TAB 3: Call History
          _buildHistory(callProvider),
        ],
      ),
    );
  }

  Widget _buildPendingQueue(CallProvider callProvider) {
    final queue = callProvider.pendingQueue;

    return Column(
      children: [
        // Power Sequential Calling Bar
        Container(
          margin: const EdgeInsets.all(14),
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xFF1E293B), Color(0xFF0F172A)],
            ),
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.3)),
          ),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: ManagerTheme.brandGold.withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.flash_on_rounded, color: ManagerTheme.brandGold, size: 20),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Power Auto-Dialer', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13)),
                    Text(
                      callProvider.isPowerDialerActive
                          ? 'Calling ${callProvider.powerDialerIndex + 1} of ${callProvider.powerDialerTotal}...'
                          : '${queue.length} inquiries waiting for pandit consultation',
                      style: const TextStyle(color: Colors.white60, fontSize: 11),
                    ),
                  ],
                ),
              ),
              ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: callProvider.isPowerDialerActive ? Colors.redAccent : ManagerTheme.brandGold,
                  foregroundColor: Colors.black,
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
                icon: Icon(callProvider.isPowerDialerActive ? Icons.stop_rounded : Icons.play_arrow_rounded, size: 16),
                label: Text(
                  callProvider.isPowerDialerActive ? 'Stop' : 'Start Calling',
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
                ),
                onPressed: () {
                  if (callProvider.isPowerDialerActive) {
                    callProvider.stopPowerDialer();
                  } else {
                    callProvider.startPowerDialer(queue);
                  }
                },
              ),
            ],
          ),
        ),

        // List of Pending Devotees
        Expanded(
          child: queue.isEmpty
              ? const Center(
                  child: Text('🎉 No pending callback inquiries! All devotees contacted.', style: TextStyle(color: Colors.white54)),
                )
              : ListView.separated(
                  padding: const EdgeInsets.symmetric(horizontal: 14),
                  itemCount: queue.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 10),
                  itemBuilder: (ctx, i) {
                    final item = queue[i];
                    return Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: ManagerTheme.surfaceDark,
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: Colors.white10),
                      ),
                      child: Row(
                        children: [
                          CircleAvatar(
                            radius: 20,
                            backgroundColor: const Color(0xFF25D366).withValues(alpha: 0.15),
                            child: const Text('🕉️', style: TextStyle(fontSize: 14)),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Text(
                                      item['name'] ?? 'Devotee',
                                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14),
                                    ),
                                    const SizedBox(width: 6),
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                      decoration: BoxDecoration(
                                        color: Colors.white10,
                                        borderRadius: BorderRadius.circular(4),
                                      ),
                                      child: Text(
                                        'Gotra: ${item['gotra'] ?? 'Kashyap'}',
                                        style: const TextStyle(color: Colors.white70, fontSize: 10),
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 3),
                                Text(
                                  item['puja_interest'] ?? 'Vedic Puja',
                                  style: const TextStyle(color: ManagerTheme.brandGold, fontSize: 12),
                                ),
                                Text(
                                  'Source: ${item['source']} · Budget: ₹${item['dakshina']}',
                                  style: const TextStyle(color: Colors.white38, fontSize: 10),
                                ),
                              ],
                            ),
                          ),
                          IconButton(
                            icon: const Icon(Icons.phone_rounded, color: Color(0xFF25D366)),
                            onPressed: () {
                              callProvider.startCall(
                                phoneNumber: item['phone'],
                                customerName: item['name'],
                                pujaInterest: item['puja_interest'],
                                gotra: item['gotra'],
                              );
                            },
                          ),
                        ],
                      ),
                    );
                  },
                ),
        ),
      ],
    );
  }

  Widget _buildNumpad(CallProvider callProvider) {
    return SingleChildScrollView(
      child: Column(
        children: [
          const SizedBox(height: 16),
          // Number Display
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    _numberController.text.isEmpty ? 'Enter devotee phone...' : _numberController.text,
                    style: TextStyle(
                      color: _numberController.text.isEmpty ? Colors.white38 : Colors.white,
                      fontSize: 26,
                      fontFamily: 'monospace',
                      fontWeight: FontWeight.bold,
                    ),
                    textAlign: TextAlign.center,
                  ),
                ),
                if (_numberController.text.isNotEmpty)
                  IconButton(
                    icon: const Icon(Icons.backspace_outlined, color: Colors.white54),
                    onPressed: _onBackspace,
                  ),
              ],
            ),
          ),
          const SizedBox(height: 8),

          // Live Searching Micro Bar
          if (_isSearchingDevotee)
            Container(
              margin: const EdgeInsets.symmetric(horizontal: 30, vertical: 4),
              child: const Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  SizedBox(width: 12, height: 12, child: CircularProgressIndicator(strokeWidth: 1.5, color: ManagerTheme.brandGold)),
                  SizedBox(width: 8),
                  Text('Searching devotee sacred dossier...', style: TextStyle(color: Colors.white54, fontSize: 11)),
                ],
              ),
            ),

          // 🪔 Devotee Caller ID Card (Ported from Z-Agents Caller ID Card)
          if (_matchedDevotee != null)
            _buildCallerIdCard(callProvider),

          const SizedBox(height: 10),
          _numpadGrid(),
          const SizedBox(height: 16),
          FloatingActionButton.large(
            backgroundColor: const Color(0xFF25D366),
            foregroundColor: Colors.white,
            elevation: 6,
            child: const Icon(Icons.phone, size: 36),
            onPressed: () {
              if (_numberController.text.trim().isNotEmpty) {
                final devotee = _matchedDevotee;
                callProvider.startCall(
                  phoneNumber: _numberController.text.trim(),
                  customerName: devotee != null ? devotee['name'] : 'Devotee (${_numberController.text.trim()})',
                  pujaInterest: devotee?['puja_interest'] ?? 'Direct Outbound Call',
                  gotra: devotee?['gotra'] ?? 'Kashyap',
                );
              }
            },
          ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  Widget _buildCallerIdCard(CallProvider callProvider) {
    final d = _matchedDevotee!;
    final name = d['name'] ?? 'Devotee';
    final gotra = d['gotra'] ?? 'Kashyap';
    final interest = d['puja_interest'] ?? 'Vedic Puja Consultation';
    final dakshina = d['dakshina'] ?? '';
    final phone = d['phone'] ?? _numberController.text.trim();

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 20, vertical: 6),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF1E293B), Color(0xFF0F172A)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.5), width: 1.2),
        boxShadow: [
          BoxShadow(
            color: ManagerTheme.brandGold.withValues(alpha: 0.1),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: ManagerTheme.brandGold.withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                ),
                child: const Text('🪔', style: TextStyle(fontSize: 16)),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Flexible(
                          child: Text(
                            name,
                            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        const SizedBox(width: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1.5),
                          decoration: BoxDecoration(
                            color: ManagerTheme.brandGold.withValues(alpha: 0.2),
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: Text(
                            'Gotra: $gotra',
                            style: const TextStyle(color: ManagerTheme.brandGold, fontSize: 9.5, fontWeight: FontWeight.bold),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 2),
                    Text(
                      '$interest · $dakshina',
                      style: const TextStyle(color: Colors.white70, fontSize: 11),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              ),
              IconButton(
                tooltip: 'WhatsApp Devotee',
                icon: const Icon(Icons.chat_bubble_outline_rounded, color: Color(0xFF25D366), size: 20),
                onPressed: () => _openDevoteeWhatsApp(phone, name),
              ),
              IconButton(
                tooltip: 'Call Now',
                icon: const Icon(Icons.phone_in_talk_rounded, color: ManagerTheme.brandGold, size: 20),
                onPressed: () {
                  callProvider.startCall(
                    phoneNumber: phone,
                    customerName: name,
                    pujaInterest: interest,
                    gotra: gotra,
                  );
                },
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _numpadGrid() {
    final keys = [
      ['1', ''], ['2', 'ABC'], ['3', 'DEF'],
      ['4', 'GHI'], ['5', 'JKL'], ['6', 'MNO'],
      ['7', 'PQRS'], ['8', 'TUV'], ['9', 'WXYZ'],
      ['*', ''], ['0', '+'], ['#', ''],
    ];

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 40),
      child: GridView.builder(
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 3,
          childAspectRatio: 1.4,
          crossAxisSpacing: 20,
          mainAxisSpacing: 10,
        ),
        itemCount: keys.length,
        itemBuilder: (ctx, i) {
          final digit = keys[i][0];
          final letters = keys[i][1];
          return InkWell(
            borderRadius: BorderRadius.circular(16),
            onTap: () => _onDigitPress(digit),
            child: Container(
              decoration: BoxDecoration(
                color: ManagerTheme.surfaceDark,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Colors.white10),
              ),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(digit, style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold)),
                  if (letters.isNotEmpty)
                    Text(letters, style: const TextStyle(color: Colors.white38, fontSize: 8)),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildHistory(CallProvider callProvider) {
    final logs = callProvider.callLogs;
    if (logs.isEmpty) {
      return const Center(child: Text('No past call logs yet.', style: TextStyle(color: Colors.white54)));
    }

    return ListView.separated(
      padding: const EdgeInsets.all(14),
      itemCount: logs.length,
      separatorBuilder: (_, __) => const SizedBox(height: 10),
      itemBuilder: (ctx, i) {
        final l = logs[i];
        return Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: ManagerTheme.surfaceDark,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: Colors.white10),
          ),
          child: Row(
            children: [
              Icon(
                l.direction == CallDirection.inbound
                    ? Icons.call_received_rounded
                    : l.direction == CallDirection.missed
                        ? Icons.call_missed_rounded
                        : Icons.call_made_rounded,
                color: l.direction == CallDirection.missed ? Colors.redAccent : const Color(0xFF25D366),
                size: 20,
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(l.customerName, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13)),
                    Text('${l.phone} · ${l.pujaInterest ?? "Puja"}', style: const TextStyle(color: Colors.white60, fontSize: 11)),
                    if (l.notes != null)
                      Text('Note: ${l.notes}', style: const TextStyle(color: ManagerTheme.brandGold, fontSize: 10)),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(l.formattedDuration, style: const TextStyle(color: Colors.white70, fontSize: 11, fontFamily: 'monospace')),
                  const SizedBox(height: 4),
                  IconButton(
                    padding: EdgeInsets.zero,
                    constraints: const BoxConstraints(),
                    icon: const Icon(Icons.replay_rounded, color: ManagerTheme.brandGold, size: 18),
                    onPressed: () {
                      callProvider.startCall(phoneNumber: l.phone, customerName: l.customerName, pujaInterest: l.pujaInterest, gotra: l.gotra);
                    },
                  ),
                ],
              ),
            ],
          ),
        );
      },
    );
  }
}
