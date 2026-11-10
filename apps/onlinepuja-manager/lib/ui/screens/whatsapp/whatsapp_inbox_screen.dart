import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../../../providers/call_provider.dart';
import '../../../services/manager_api_service.dart';
import '../../../theme/manager_theme.dart';

class WhatsAppInboxScreen extends StatefulWidget {
  const WhatsAppInboxScreen({super.key});

  @override
  State<WhatsAppInboxScreen> createState() => _WhatsAppInboxScreenState();
}

class _WhatsAppInboxScreenState extends State<WhatsAppInboxScreen> {
  final ManagerApiService _api = ManagerApiService();

  bool _isLoadingThreads = true;
  List<Map<String, dynamic>> _threads = [];
  Map<String, dynamic>? _selectedThread;

  bool _isLoadingMessages = false;
  List<Map<String, dynamic>> _messages = [];
  final TextEditingController _messageController = TextEditingController();
  final ScrollController _scrollController = ScrollController();

  Map<String, dynamic> _gatewayStatus = {};
  String _searchQuery = '';
  String _activeFilter = 'all'; // 'all', 'unread', 'gotra', 'ai'
  bool _isSending = false;

  @override
  void initState() {
    super.initState();
    _loadGatewayStatus();
    _loadThreads();
  }

  @override
  void dispose() {
    _messageController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _loadGatewayStatus() async {
    final status = await _api.fetchWhatsappStatus();
    if (mounted) {
      setState(() {
        _gatewayStatus = status;
      });
    }
  }

  Future<void> _loadThreads() async {
    setState(() => _isLoadingThreads = true);
    final threads = await _api.fetchWhatsappThreads(search: _searchQuery);
    if (mounted) {
      setState(() {
        _threads = threads;
        _isLoadingThreads = false;
        if (_selectedThread != null) {
          final found = threads.firstWhere(
            (t) => t['remote_jid'] == _selectedThread!['remote_jid'],
            orElse: () => _selectedThread!,
          );
          _selectedThread = found;
        }
      });
    }
  }

  Future<void> _selectThread(Map<String, dynamic> thread) async {
    setState(() {
      _selectedThread = thread;
      _isLoadingMessages = true;
    });

    final jid = thread['remote_jid']?.toString() ?? '';
    final msgs = await _api.fetchWhatsappMessages(jid);

    if (mounted) {
      setState(() {
        _messages = msgs;
        _isLoadingMessages = false;
      });
      _scrollToBottom();
    }
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 250),
          curve: Curves.easeOut,
        );
      }
    });
  }

  Future<void> _sendMessage([String? presetText]) async {
    final text = (presetText ?? _messageController.text).trim();
    if (text.isEmpty || _selectedThread == null || _isSending) return;

    HapticFeedback.lightImpact();
    final jid = _selectedThread!['remote_jid']?.toString() ?? '';
    final phone = _selectedThread!['phone']?.toString() ?? '';
    final wasAiHandling = _selectedThread!['is_ai_handling'] == true;

    setState(() {
      _isSending = true;
      if (wasAiHandling) {
        // Automatically activate Human Takeover when manager manually speaks
        _selectedThread!['is_ai_handling'] = false;
      }
      _messages.add({
        'direction': 'outbound',
        'message': text,
        'status': 'sent',
        'is_ai_generated': false,
        'created_at': DateTime.now().toIso8601String(),
      });
    });
    if (presetText == null) {
      _messageController.clear();
    }
    _scrollToBottom();

    if (wasAiHandling) {
      // Notify backend that human has taken over this thread
      _api.toggleWhatsappAi(jid: jid, enable: false);
    }

    final success = await _api.sendWhatsappMessage(
      jid: jid,
      phone: phone,
      message: text,
    );

    if (mounted) {
      setState(() => _isSending = false);
      if (!success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Failed to dispatch via Evolution API. Message saved locally.'),
            duration: Duration(seconds: 2),
          ),
        );
      }
      _loadThreads();
    }
  }

  Future<void> _toggleAi(bool val) async {
    if (_selectedThread == null) return;
    HapticFeedback.selectionClick();
    final jid = _selectedThread!['remote_jid']?.toString() ?? '';

    setState(() {
      _selectedThread!['is_ai_handling'] = val;
    });

    final ok = await _api.toggleWhatsappAi(jid: jid, enable: val);
    if (!mounted) return;
    if (!ok) {
      setState(() {
        _selectedThread!['is_ai_handling'] = !val;
      });
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(val ? '🤖 AI Bot handling this devotee.' : '👤 Manual Ops: Human Manager took control.'),
          duration: const Duration(seconds: 2),
          backgroundColor: val ? const Color(0xFF0F766E) : const Color(0xFF1E293B),
        ),
      );
    }
  }

  void _polishMessage() {
    final currentText = _messageController.text.trim();
    if (currentText.isEmpty) return;

    final devoteeName = _selectedThread?['name']?.toString() ?? 'Devotee';
    String polished = currentText;

    if (!polished.toLowerCase().contains('pranam') && !polished.toLowerCase().contains('namaste')) {
      polished = 'Pranam $devoteeName ji! 🙏 $polished';
    }
    if (!polished.toLowerCase().contains('har har mahadev') && !polished.toLowerCase().contains('shubh')) {
      polished = '$polished\nHar Har Mahadev 🔱';
    }

    _messageController.text = polished;
    HapticFeedback.lightImpact();
  }

  List<Map<String, dynamic>> get _filteredThreads {
    return _threads.where((t) {
      if (_activeFilter == 'unread') {
        final unread = (t['unread_count'] is num) ? (t['unread_count'] as num).toInt() : 0;
        if (unread <= 0) return false;
      } else if (_activeFilter == 'gotra') {
        final gotra = t['gotra']?.toString() ?? '';
        if (gotra.isEmpty || gotra.toLowerCase() == 'unknown') return false;
      } else if (_activeFilter == 'ai') {
        if (t['is_ai_handling'] != true) return false;
      }
      return true;
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    final isDesktop = MediaQuery.of(context).size.width >= 900;

    return Scaffold(
      backgroundColor: const Color(0xFF0B141A),
      // Only render top AppBar on Desktop OR when on mobile thread list (never duplicate inside mobile chat)
      appBar: (isDesktop || _selectedThread == null) ? _buildMainAppBar() : null,
      body: isDesktop ? _buildDesktopLayout() : _buildMobileLayout(),
    );
  }

  PreferredSizeWidget _buildMainAppBar() {
    return AppBar(
      backgroundColor: const Color(0xFF111B21),
      elevation: 0,
      title: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              color: const Color(0xFF25D366).withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(8),
            ),
            child: const Icon(Icons.chat_bubble_rounded, color: Color(0xFF25D366), size: 18),
          ),
          const SizedBox(width: 10),
          const Text(
            'WhatsApp CRM',
            style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16, color: Colors.white),
          ),
          const SizedBox(width: 12),
          _buildGatewayStatusBadge(),
        ],
      ),
      actions: [
        IconButton(
          icon: const Icon(Icons.refresh_rounded, color: Colors.white70, size: 20),
          tooltip: 'Refresh Threads',
          onPressed: () {
            _loadGatewayStatus();
            _loadThreads();
            if (_selectedThread != null) {
              _selectThread(_selectedThread!);
            }
          },
        ),
      ],
    );
  }

  Widget _buildGatewayStatusBadge() {
    final isConnected = _gatewayStatus['is_connected'] == true;
    final state = _gatewayStatus['connection_state']?.toString().toUpperCase() ?? 'OFFLINE';

    return InkWell(
      onTap: _showDeviceConnectionModal,
      borderRadius: BorderRadius.circular(20),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        decoration: BoxDecoration(
          color: isConnected ? const Color(0xFF064E3B) : const Color(0xFF78350F),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isConnected ? const Color(0xFF10B981) : const Color(0xFFF59E0B),
            width: 0.8,
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 6,
              height: 6,
              decoration: BoxDecoration(
                color: isConnected ? const Color(0xFF34D399) : const Color(0xFFFBBF24),
                shape: BoxShape.circle,
              ),
            ),
            const SizedBox(width: 6),
            Text(
              isConnected ? 'ONLINE' : state,
              style: TextStyle(
                fontSize: 10,
                fontWeight: FontWeight.w700,
                color: isConnected ? const Color(0xFF6EE7B7) : const Color(0xFFFDE68A),
                letterSpacing: 0.4,
              ),
            ),
            const SizedBox(width: 5),
            Icon(
              isConnected ? Icons.verified_rounded : Icons.qr_code_2_rounded,
              size: 13,
              color: isConnected ? const Color(0xFF6EE7B7) : const Color(0xFFFDE68A),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMobileLayout() {
    if (_selectedThread != null) {
      return PopScope(
        canPop: false,
        onPopInvokedWithResult: (didPop, _) {
          if (didPop) return;
          setState(() => _selectedThread = null);
        },
        child: _buildChatPane(isMobile: true),
      );
    }
    return _buildThreadsPane();
  }

  Widget _buildDesktopLayout() {
    return Row(
      children: [
        SizedBox(
          width: 340,
          child: _buildThreadsPane(),
        ),
        const VerticalDivider(width: 1, color: Color(0xFF222D34)),
        Expanded(
          flex: 6,
          child: _selectedThread == null
              ? _buildEmptyState()
              : _buildChatPane(isMobile: false),
        ),
        if (_selectedThread != null) ...[
          const VerticalDivider(width: 1, color: Color(0xFF222D34)),
          SizedBox(
            width: 300,
            child: _buildDevoteeInspectorPane(),
          ),
        ],
      ],
    );
  }

  Widget _buildEmptyState() {
    return Container(
      color: const Color(0xFF0B141A),
      child: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: const Color(0xFF111B21),
                shape: BoxShape.circle,
                border: Border.all(color: const Color(0xFF222D34)),
              ),
              child: const Icon(Icons.chat_bubble_outline_rounded, color: Color(0xFF25D366), size: 40),
            ),
            const SizedBox(height: 16),
            const Text(
              'OnlinePuja Devotee Desk',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Colors.white),
            ),
            const SizedBox(height: 6),
            const Text(
              'Select a conversation to view chat, sacred Gotra & live darshan.',
              style: TextStyle(color: Colors.white54, fontSize: 13),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildThreadsPane() {
    return Container(
      color: const Color(0xFF111B21),
      child: Column(
        children: [
          // Search Bar
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 10, 12, 6),
            child: TextField(
              decoration: InputDecoration(
                hintText: 'Search Devotees or WhatsApp...',
                hintStyle: const TextStyle(color: Colors.white38, fontSize: 13),
                prefixIcon: const Icon(Icons.search, color: Colors.white38, size: 18),
                contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                filled: true,
                fillColor: const Color(0xFF202C33),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
              ),
              style: const TextStyle(color: Colors.white, fontSize: 13),
              onChanged: (val) {
                _searchQuery = val;
                _loadThreads();
              },
            ),
          ),

          // Filter Chips
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            child: Row(
              children: [
                _buildFilterChip('all', 'All'),
                const SizedBox(width: 6),
                _buildFilterChip('unread', 'Unread'),
                const SizedBox(width: 6),
                _buildFilterChip('gotra', 'With Gotra 🕉️'),
                const SizedBox(width: 6),
                _buildFilterChip('ai', 'AI Bot 🤖'),
              ],
            ),
          ),
          const Divider(height: 1, color: Color(0xFF222D34)),

          // Thread List
          Expanded(
            child: _isLoadingThreads
                ? const Center(child: CircularProgressIndicator(color: ManagerTheme.brandGold, strokeWidth: 2))
                : _filteredThreads.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.inbox_rounded, color: Colors.white.withValues(alpha: 0.2), size: 36),
                            const SizedBox(height: 8),
                            const Text('No conversations match filter', style: TextStyle(color: Colors.white38, fontSize: 13)),
                          ],
                        ),
                      )
                    : ListView.separated(
                        itemCount: _filteredThreads.length,
                        separatorBuilder: (_, __) => const Divider(height: 1, color: Color(0xFF222D34)),
                        itemBuilder: (ctx, i) {
                          final th = _filteredThreads[i];
                          final isSelected = _selectedThread != null && _selectedThread!['remote_jid'] == th['remote_jid'];
                          final hasGotra = th['gotra'] != null && th['gotra'].toString().isNotEmpty && th['gotra'].toString().toLowerCase() != 'unknown';
                          final isAi = th['is_ai_handling'] == true;
                          final unread = (th['unread_count'] is num) ? (th['unread_count'] as num).toInt() : 0;
                          final name = th['name']?.toString() ?? 'Devotee';

                          return ListTile(
                            selected: isSelected,
                            selectedTileColor: const Color(0xFF202C33),
                            dense: true,
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
                            leading: Stack(
                              children: [
                                CircleAvatar(
                                  radius: 20,
                                  backgroundColor: const Color(0xFF2A3942),
                                  child: Text(
                                    name.isNotEmpty ? name[0].toUpperCase() : 'D',
                                    style: const TextStyle(color: ManagerTheme.brandGold, fontWeight: FontWeight.bold, fontSize: 14),
                                  ),
                                ),
                                if (isAi)
                                  Positioned(
                                    right: 0,
                                    bottom: 0,
                                    child: Container(
                                      padding: const EdgeInsets.all(2.5),
                                      decoration: const BoxDecoration(
                                        color: Color(0xFF0F766E),
                                        shape: BoxShape.circle,
                                      ),
                                      child: const Icon(Icons.smart_toy_rounded, size: 9, color: Colors.white),
                                    ),
                                  ),
                              ],
                            ),
                            title: Row(
                              children: [
                                Expanded(
                                  child: Text(
                                    name,
                                    style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5, color: Colors.white),
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                                if (hasGotra)
                                  Container(
                                    margin: const EdgeInsets.only(left: 4),
                                    padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                                    decoration: BoxDecoration(
                                      color: ManagerTheme.brandGold.withValues(alpha: 0.15),
                                      borderRadius: BorderRadius.circular(4),
                                      border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.3), width: 0.6),
                                    ),
                                    child: Text(
                                      th['gotra'],
                                      style: const TextStyle(fontSize: 9.5, color: ManagerTheme.brandGold, fontWeight: FontWeight.w600),
                                    ),
                                  ),
                              ],
                            ),
                            subtitle: Row(
                              children: [
                                Expanded(
                                  child: Text(
                                    th['last_message']?.toString() ?? '',
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: TextStyle(
                                      fontSize: 12,
                                      color: unread > 0 ? Colors.white : Colors.white54,
                                      fontWeight: unread > 0 ? FontWeight.w600 : FontWeight.normal,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            trailing: unread > 0
                                ? Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                    decoration: BoxDecoration(
                                      color: const Color(0xFF25D366),
                                      borderRadius: BorderRadius.circular(10),
                                    ),
                                    child: Text(
                                      '$unread',
                                      style: const TextStyle(color: Colors.black, fontSize: 10, fontWeight: FontWeight.bold),
                                    ),
                                  )
                                : null,
                            onTap: () => _selectThread(th),
                          );
                        },
                      ),
          ),
        ],
      ),
    );
  }

  Widget _buildFilterChip(String id, String label) {
    final isSelected = _activeFilter == id;
    return InkWell(
      onTap: () => setState(() => _activeFilter = id),
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFF005C4B) : const Color(0xFF202C33),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: isSelected ? const Color(0xFF25D366) : const Color(0xFF2A3942),
            width: 0.8,
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 11,
            fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
            color: isSelected ? Colors.white : Colors.white70,
          ),
        ),
      ),
    );
  }

  Widget _buildChatPane({required bool isMobile}) {
    final t = _selectedThread!;
    final isAi = t['is_ai_handling'] == true;

    return Column(
      children: [
        // Premium Single Unified Chat Header
        _buildSingleChatHeader(isMobile: isMobile),
        const Divider(height: 1, color: Color(0xFF222D34)),

        // 🛑 Emergency AI Kill-Switch / Human Takeover Alert Banner
        if (!isAi)
          Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                colors: [Color(0xFF451A03), Color(0xFF1E1B4B)],
                begin: Alignment.centerLeft,
                end: Alignment.centerRight,
              ),
              border: Border(bottom: BorderSide(color: Color(0xFFF59E0B), width: 0.8)),
            ),
            child: Row(
              children: [
                const Icon(Icons.shield_rounded, color: Color(0xFFFBBF24), size: 16),
                const SizedBox(width: 8),
                const Expanded(
                  child: Text(
                    '🛡️ Human Takeover Active: AI bot auto-replies muted for this devotee.',
                    style: TextStyle(color: Color(0xFFFDE68A), fontSize: 11.5, fontWeight: FontWeight.w600),
                  ),
                ),
                InkWell(
                  onTap: () => _toggleAi(true),
                  borderRadius: BorderRadius.circular(6),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: const Color(0xFF0F766E),
                      borderRadius: BorderRadius.circular(6),
                      border: Border.all(color: const Color(0xFF2DD4BF), width: 0.8),
                    ),
                    child: const Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.smart_toy_rounded, size: 12, color: Colors.white),
                        SizedBox(width: 4),
                        Text('Resume AI', style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),

        // Message Canvas
        Expanded(
          child: _isLoadingMessages
              ? const Center(child: CircularProgressIndicator(color: ManagerTheme.brandGold, strokeWidth: 2))
              : _messages.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.forum_outlined, color: Colors.white.withValues(alpha: 0.2), size: 40),
                          const SizedBox(height: 10),
                          const Text('No messages exchanged yet.', style: TextStyle(color: Colors.white54, fontSize: 13)),
                          const SizedBox(height: 4),
                          const Text('Choose an intelligent template below or type a message.', style: TextStyle(color: Colors.white38, fontSize: 11)),
                        ],
                      ),
                    )
                  : ListView.builder(
                      controller: _scrollController,
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                      itemCount: _messages.length,
                      itemBuilder: (ctx, i) {
                        final msg = _messages[i];
                        final isOutbound = msg['direction'] == 'outbound';
                        final isAi = msg['is_ai_generated'] == true;

                        return Align(
                          alignment: isOutbound ? Alignment.centerRight : Alignment.centerLeft,
                          child: Container(
                            margin: const EdgeInsets.only(bottom: 8),
                            constraints: BoxConstraints(
                              maxWidth: MediaQuery.of(context).size.width * (isMobile ? 0.78 : 0.65),
                            ),
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                            decoration: BoxDecoration(
                              color: isOutbound ? const Color(0xFF005C4B) : const Color(0xFF202C33),
                              borderRadius: BorderRadius.only(
                                topLeft: const Radius.circular(10),
                                topRight: const Radius.circular(10),
                                bottomLeft: isOutbound ? const Radius.circular(10) : Radius.zero,
                                bottomRight: isOutbound ? Radius.zero : const Radius.circular(10),
                              ),
                              border: Border.all(
                                color: isOutbound ? const Color(0xFF0B6E59) : const Color(0xFF2A3942),
                                width: 0.6,
                              ),
                            ),
                            child: Column(
                              crossAxisAlignment: isOutbound ? CrossAxisAlignment.end : CrossAxisAlignment.start,
                              children: [
                                if (isAi) ...[
                                  Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      const Icon(Icons.smart_toy_rounded, size: 10, color: Color(0xFF5EEAD4)),
                                      const SizedBox(width: 4),
                                      Text(
                                        'AI Auto-Response',
                                        style: TextStyle(fontSize: 9.5, color: Colors.teal.shade200, fontWeight: FontWeight.bold),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 3),
                                ],
                                SelectableText(
                                  msg['message']?.toString() ?? '',
                                  style: const TextStyle(color: Colors.white, fontSize: 13.5, height: 1.3),
                                ),
                                const SizedBox(height: 3),
                                Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Text(
                                      _formatTime(msg['timestamp'] ?? msg['created_at']),
                                      style: const TextStyle(fontSize: 9.5, color: Colors.white54),
                                    ),
                                    if (isOutbound) ...[
                                      const SizedBox(width: 4),
                                      const Icon(Icons.done_all, size: 13, color: Color(0xFF53BDEB)),
                                    ],
                                  ],
                                ),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
        ),

        // Intelligent Vedic Suggestion Pill Strip
        _buildIntelligentPillStrip(),

        // Clean Modern Message Input Bar
        _buildComposerBar(),
      ],
    );
  }

  Widget _buildSingleChatHeader({required bool isMobile}) {
    final t = _selectedThread!;
    final name = t['name']?.toString() ?? 'Devotee';
    final phone = t['phone']?.toString() ?? '';
    final gotra = t['gotra']?.toString() ?? '';
    final hasGotra = gotra.isNotEmpty && gotra.toLowerCase() != 'unknown';
    final isAi = t['is_ai_handling'] == true;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      color: const Color(0xFF111B21),
      child: SafeArea(
        bottom: false,
        child: Row(
          children: [
            if (isMobile)
              IconButton(
                icon: const Icon(Icons.arrow_back_rounded, color: Colors.white, size: 22),
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(),
                onPressed: () => setState(() => _selectedThread = null),
              ),
            if (isMobile) const SizedBox(width: 8),

            // Devotee Avatar & Presence
            InkWell(
              onTap: () => _showDevoteeDossierSheet(context),
              borderRadius: BorderRadius.circular(20),
              child: Stack(
                children: [
                  CircleAvatar(
                    radius: 19,
                    backgroundColor: const Color(0xFF2A3942),
                    child: Text(
                      name.isNotEmpty ? name[0].toUpperCase() : 'D',
                      style: const TextStyle(color: ManagerTheme.brandGold, fontWeight: FontWeight.bold, fontSize: 14),
                    ),
                  ),
                  Positioned(
                    right: 0,
                    bottom: 0,
                    child: Container(
                      width: 9,
                      height: 9,
                      decoration: BoxDecoration(
                        color: const Color(0xFF25D366),
                        shape: BoxShape.circle,
                        border: Border.all(color: const Color(0xFF111B21), width: 1.5),
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 10),

            // Devotee Name & Sacred Metadata
            Expanded(
              child: InkWell(
                onTap: () => _showDevoteeDossierSheet(context),
                borderRadius: BorderRadius.circular(6),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Row(
                      children: [
                        Flexible(
                          child: Text(
                            name,
                            style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14, color: Colors.white),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        if (hasGotra) ...[
                          const SizedBox(width: 6),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                            decoration: BoxDecoration(
                              color: ManagerTheme.brandGold.withValues(alpha: 0.15),
                              borderRadius: BorderRadius.circular(4),
                              border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.3), width: 0.6),
                            ),
                            child: Text(
                              gotra,
                              style: const TextStyle(fontSize: 9.5, color: ManagerTheme.brandGold, fontWeight: FontWeight.bold),
                            ),
                          ),
                        ],
                      ],
                    ),
                    const SizedBox(height: 1),
                    Text(
                      '+$phone',
                      style: const TextStyle(fontSize: 11, color: Colors.white54),
                    ),
                  ],
                ),
              ),
            ),

            // Call Devotee Button
            IconButton(
              icon: const Icon(Icons.phone_rounded, color: Color(0xFF25D366), size: 19),
              tooltip: 'Call Devotee',
              onPressed: () {
                final callProvider = Provider.of<CallProvider>(context, listen: false);
                callProvider.startCall(
                  phoneNumber: phone,
                  customerName: name,
                  gotra: gotra,
                  pujaInterest: 'WhatsApp Inquiry',
                );
              },
            ),

            // Sleek Micro AI Toggle (No giant buttons)
            _buildMicroAiChip(isAi),

            // Devotee Dossier Info Button (Mobile only)
            if (isMobile)
              IconButton(
                icon: const Icon(Icons.info_outline_rounded, color: Colors.white70, size: 19),
                tooltip: 'Devotee Vedic Dossier',
                onPressed: () => _showDevoteeDossierSheet(context),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildMicroAiChip(bool isAi) {
    return InkWell(
      onTap: () => _toggleAi(!isAi),
      borderRadius: BorderRadius.circular(16),
      child: Container(
        margin: const EdgeInsets.symmetric(horizontal: 4),
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        decoration: BoxDecoration(
          color: isAi ? const Color(0xFFDC2626).withValues(alpha: 0.2) : const Color(0xFFF59E0B).withValues(alpha: 0.2),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: isAi ? const Color(0xFFEF4444) : const Color(0xFFFBBF24),
            width: 0.8,
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              isAi ? Icons.power_settings_new_rounded : Icons.person_rounded,
              size: 13,
              color: isAi ? const Color(0xFFF87171) : const Color(0xFFFDE68A),
            ),
            const SizedBox(width: 4),
            Text(
              isAi ? 'Kill AI' : 'Human Mode',
              style: TextStyle(
                fontSize: 10.5,
                fontWeight: FontWeight.bold,
                color: isAi ? const Color(0xFFF87171) : const Color(0xFFFDE68A),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildIntelligentPillStrip() {
    final t = _selectedThread!;
    final name = t['name']?.toString() ?? 'Devotee';
    final gotra = t['gotra']?.toString() ?? 'Kashyap';

    final templates = [
      {
        'label': '🪔 Sankalp Darshan',
        'text': 'Pranam $name ji! 🙏 Panditji will perform your Sankalp in $gotra Gotra with Vedic rituals at Kashi Vishwanath Dham. We will send you the live sanctum darshan video.',
      },
      {
        'label': '🔱 Astrological Guidance',
        'text': 'Har Har Mahadev $name ji! Planetary transits indicate auspicious alignment for your Gotra ($gotra). Performing Navagraha Shanti brings immense peace.',
      },
      {
        'label': '💳 Dakshina Proposal',
        'text': 'Pranam $name ji! You can review your customized Puja Dakshina and complete your sacred booking securely here: https://onlinepuja.live/puja/category/list',
      },
      {
        'label': '📦 Prasad Consignment',
        'text': 'Pranam $name ji! Your sanctified Prasad (Bhasma, Belpatra & Raksha Sutra) has been dispatched via India Post Speed Post. Blessed regards from Varanasi!',
      },
      {
        'label': '🕉️ Gotra Verification',
        'text': 'Namaste $name ji! Kindly share your sacred Gotra and the full names of family members to be chanted during the Sankalp path.',
      },
    ];

    return Container(
      color: const Color(0xFF111B21),
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      child: SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: Row(
          children: templates.map((tmpl) {
            return Container(
              margin: const EdgeInsets.only(right: 6),
              child: ActionChip(
                backgroundColor: const Color(0xFF202C33),
                side: const BorderSide(color: Color(0xFF2A3942), width: 0.8),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                label: Text(
                  tmpl['label']!,
                  style: const TextStyle(color: Colors.white70, fontSize: 11, fontWeight: FontWeight.w500),
                ),
                onPressed: () {
                  _messageController.text = tmpl['text']!;
                  HapticFeedback.lightImpact();
                },
              ),
            );
          }).toList(),
        ),
      ),
    );
  }

  Widget _buildComposerBar() {
    final devoteeName = _selectedThread?['name']?.toString() ?? 'Devotee';

    return Container(
      padding: const EdgeInsets.fromLTRB(8, 6, 8, 10),
      color: const Color(0xFF111B21),
      child: SafeArea(
        top: false,
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            // Quick Attachment / Actions Menu
            IconButton(
              icon: const Icon(Icons.add_circle_outline_rounded, color: Colors.white70, size: 22),
              tooltip: 'Attach Puja / Payment Link',
              onPressed: _showQuickAttachmentsSheet,
            ),

            // Message TextField with Polish Icon
            Expanded(
              child: Container(
                decoration: BoxDecoration(
                  color: const Color(0xFF202C33),
                  borderRadius: BorderRadius.circular(22),
                  border: Border.all(color: const Color(0xFF2A3942), width: 0.8),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _messageController,
                        minLines: 1,
                        maxLines: 4,
                        decoration: InputDecoration(
                          hintText: 'Message $devoteeName...',
                          hintStyle: const TextStyle(color: Colors.white38, fontSize: 13.5),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                          border: InputBorder.none,
                          isDense: true,
                        ),
                        style: const TextStyle(color: Colors.white, fontSize: 13.5),
                        onSubmitted: (_) => _sendMessage(),
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.auto_awesome, color: ManagerTheme.brandGold, size: 16),
                      tooltip: 'Vedic Tone Polish',
                      padding: const EdgeInsets.all(8),
                      constraints: const BoxConstraints(),
                      onPressed: _polishMessage,
                    ),
                    const SizedBox(width: 4),
                  ],
                ),
              ),
            ),
            const SizedBox(width: 8),

            // WhatsApp Send Button (Always unobstructed)
            CircleAvatar(
              radius: 20,
              backgroundColor: const Color(0xFF25D366),
              child: IconButton(
                icon: _isSending
                    ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.black))
                    : const Icon(Icons.send_rounded, color: Colors.black, size: 18),
                padding: EdgeInsets.zero,
                onPressed: _isSending ? null : () => _sendMessage(),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showQuickAttachmentsSheet() {
    final t = _selectedThread!;
    final name = t['name']?.toString() ?? 'Devotee';

    showModalBottomSheet(
      context: context,
      backgroundColor: const Color(0xFF1E293B),
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(16))),
      builder: (ctx) {
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 12),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(width: 40, height: 4, decoration: BoxDecoration(color: Colors.white24, borderRadius: BorderRadius.circular(2))),
                const SizedBox(height: 12),
                const Text('Vedic Quick Inserts', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Colors.white)),
                const SizedBox(height: 10),
                ListTile(
                  leading: const CircleAvatar(backgroundColor: Color(0xFF0F766E), child: Icon(Icons.description_rounded, color: Colors.white, size: 18)),
                  title: const Text('Custom Puja Dakshina Quote', style: TextStyle(color: Colors.white, fontSize: 13.5)),
                  subtitle: const Text('Insert customized Dakshina quote & Razorpay link', style: TextStyle(color: Colors.white54, fontSize: 11)),
                  onTap: () {
                    Navigator.pop(ctx);
                    _messageController.text = 'Pranam $name ji! 🙏 Panditji has prepared your sacred Puja Dakshina proposal:\n• Dakshina: ₹5,100\n• 5 Vedic Pandits\n• Kashi Sanctum Samagri Included\nComplete booking: https://onlinepuja.live/pujas';
                  },
                ),
                ListTile(
                  leading: const CircleAvatar(backgroundColor: Color(0xFF1D4ED8), child: Icon(Icons.videocam_rounded, color: Colors.white, size: 18)),
                  title: const Text('Sanctum Live Darshan Video Link', style: TextStyle(color: Colors.white, fontSize: 13.5)),
                  subtitle: const Text('Send devotee exclusive high-definition Sankalp video', style: TextStyle(color: Colors.white54, fontSize: 11)),
                  onTap: () {
                    Navigator.pop(ctx);
                    _messageController.text = 'Har Har Mahadev $name ji! 🙏 Here is your sacred Sankalp Darshan recorded live at the sanctum sanctorum: https://onlinepuja.live/darshan/live-archive';
                  },
                ),
                ListTile(
                  leading: const CircleAvatar(backgroundColor: Color(0xFFB45309), child: Icon(Icons.local_shipping_rounded, color: Colors.white, size: 18)),
                  title: const Text('Prasad Tracking Consignment AWB', style: TextStyle(color: Colors.white, fontSize: 13.5)),
                  subtitle: const Text('Send India Post Speed Post consignment tracking', style: TextStyle(color: Colors.white54, fontSize: 11)),
                  onTap: () {
                    Navigator.pop(ctx);
                    _messageController.text = 'Pranam $name ji! Your consecrated Prasad has been dispatched via India Post Speed Post. AWB Consignment: IN847291038IN. Track here: https://www.indiapost.gov.in';
                  },
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  void _showDevoteeDossierSheet(BuildContext context) {
    if (_selectedThread == null) return;
    final t = _selectedThread!;
    final name = t['name']?.toString() ?? 'Devotee';
    final phone = t['phone']?.toString() ?? '';
    final gotra = t['gotra']?.toString() ?? 'Kashyap';
    final city = t['city']?.toString() ?? 'Varanasi';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: const Color(0xFF111B21),
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) {
        return DraggableScrollableSheet(
          initialChildSize: 0.65,
          maxChildSize: 0.9,
          minChildSize: 0.4,
          expand: false,
          builder: (_, scrollController) {
            return SingleChildScrollView(
              controller: scrollController,
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Center(child: Container(width: 40, height: 4, decoration: BoxDecoration(color: Colors.white24, borderRadius: BorderRadius.circular(2)))),
                  const SizedBox(height: 16),

                  // Devotee Header
                  Center(
                    child: CircleAvatar(
                      radius: 32,
                      backgroundColor: ManagerTheme.brandGold.withValues(alpha: 0.2),
                      child: Text(
                        name.isNotEmpty ? name[0].toUpperCase() : 'D',
                        style: const TextStyle(fontSize: 26, fontWeight: FontWeight.bold, color: ManagerTheme.brandGold),
                      ),
                    ),
                  ),
                  const SizedBox(height: 10),
                  Center(
                    child: Text(name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Colors.white)),
                  ),
                  Center(
                    child: Text('+$phone', style: const TextStyle(fontSize: 13, color: Colors.white54)),
                  ),
                  const SizedBox(height: 16),

                  // Quick Action Buttons
                  Row(
                    children: [
                      Expanded(
                        child: ElevatedButton.icon(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFF25D366),
                            foregroundColor: Colors.black,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                          icon: const Icon(Icons.phone_rounded, size: 16),
                          label: const Text('Call Devotee'),
                          onPressed: () {
                            Navigator.pop(ctx);
                            final callProvider = Provider.of<CallProvider>(context, listen: false);
                            callProvider.startCall(
                              phoneNumber: phone,
                              customerName: name,
                              gotra: gotra,
                            );
                          },
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: OutlinedButton.icon(
                          style: OutlinedButton.styleFrom(
                            foregroundColor: ManagerTheme.brandGold,
                            side: const BorderSide(color: ManagerTheme.brandGold),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                          icon: const Icon(Icons.copy_rounded, size: 16),
                          label: const Text('Copy Phone'),
                          onPressed: () {
                            Clipboard.setData(ClipboardData(text: phone));
                            Navigator.pop(ctx);
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Phone copied to clipboard')),
                            );
                          },
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 20),
                  const Divider(color: Color(0xFF222D34)),

                  // Sacred Kundali Details
                  const Text('Sacred Kundali & Gotra Record', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: ManagerTheme.brandGold)),
                  const SizedBox(height: 10),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFF202C33),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: const Color(0xFF2A3942)),
                    ),
                    child: Column(
                      children: [
                        _buildDossierRow('Gotra', gotra),
                        const Divider(height: 12, color: Color(0xFF2A3942)),
                        _buildDossierRow('City', city),
                        const Divider(height: 12, color: Color(0xFF2A3942)),
                        _buildDossierRow('Sankalp Status', 'Verified with Varanasi Sanctum'),
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),

                  // Recommended Pujas
                  const Text('Recommended Festival Pujas', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.white)),
                  const SizedBox(height: 8),
                  _buildPujaRecommendationTile(
                    ctx,
                    title: '🪔 Dev Deepawali 108 Deepdaan',
                    pitch: 'Offer 108 sacred earthen lamps at Varanasi Dashashwamedh Ghat for ancestral blessings: https://onlinepuja.live/pujas',
                  ),
                  const SizedBox(height: 6),
                  _buildPujaRecommendationTile(
                    ctx,
                    title: '🔱 Kashi Vishwanath Rudrabhishek',
                    pitch: 'Perform sacred Maha Rudrabhishek at Kashi Vishwanath Dham by certified Vedic Pandits: https://onlinepuja.live/pujas',
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  Widget _buildDossierRow(String label, String value) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(fontSize: 12, color: Colors.white54)),
        Text(value, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: Colors.white)),
      ],
    );
  }

  Widget _buildPujaRecommendationTile(BuildContext sheetCtx, {required String title, required String pitch}) {
    return InkWell(
      onTap: () {
        Navigator.pop(sheetCtx);
        _messageController.text = pitch;
      },
      borderRadius: BorderRadius.circular(8),
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        decoration: BoxDecoration(
          color: const Color(0xFF202C33),
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: const Color(0xFF2A3942)),
        ),
        child: Row(
          children: [
            Expanded(
              child: Text(title, style: const TextStyle(fontSize: 12, color: Colors.white, fontWeight: FontWeight.w500)),
            ),
            const Icon(Icons.arrow_forward_ios_rounded, size: 12, color: Colors.white38),
          ],
        ),
      ),
    );
  }

  Widget _buildDevoteeInspectorPane() {
    final t = _selectedThread!;
    final name = t['name']?.toString() ?? 'Devotee';
    final phone = t['phone']?.toString() ?? '';
    final gotra = t['gotra']?.toString() ?? 'Kashyap';
    final city = t['city']?.toString() ?? 'Varanasi';

    return Container(
      color: const Color(0xFF111B21),
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Center(
            child: CircleAvatar(
              radius: 28,
              backgroundColor: ManagerTheme.brandGold.withValues(alpha: 0.2),
              child: Text(
                name.isNotEmpty ? name[0].toUpperCase() : 'D',
                style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: ManagerTheme.brandGold),
              ),
            ),
          ),
          const SizedBox(height: 10),
          Center(
            child: Text(name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.white)),
          ),
          Center(
            child: Text('+$phone', style: const TextStyle(fontSize: 12, color: Colors.white54)),
          ),
          const SizedBox(height: 16),
          const Divider(color: Color(0xFF222D34)),
          const SizedBox(height: 10),
          const Text('Devotee Kundali & Gotra', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: ManagerTheme.brandGold)),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: const Color(0xFF202C33),
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: const Color(0xFF2A3942)),
            ),
            child: Column(
              children: [
                _buildDossierRow('Gotra', gotra),
                const SizedBox(height: 6),
                _buildDossierRow('City', city),
              ],
            ),
          ),
          const SizedBox(height: 16),
          const Text('Recommended Festival Pujas', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white70)),
          const SizedBox(height: 8),
          _buildQuickPujaChip('🪔 Dev Deepawali 108 Deepdaan', 'Offer 108 sacred earthen lamps at Varanasi Dashashwamedh Ghat: https://onlinepuja.live/pujas'),
          const SizedBox(height: 6),
          _buildQuickPujaChip('🔱 Kashi Rudrabhishek', 'Sacred Rudrabhishek at Kashi Vishwanath Dham by Vedic Pandits: https://onlinepuja.live/pujas'),
          const SizedBox(height: 6),
          _buildQuickPujaChip('🪐 Shani Shanti Daan', 'Navagraha Puja & Shani Dosha Nivaran Sankalp: https://onlinepuja.live/pujas'),
          const Spacer(),
          ElevatedButton.icon(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF25D366),
              foregroundColor: Colors.black,
              minimumSize: const Size(double.infinity, 42),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            icon: const Icon(Icons.phone_rounded, size: 16),
            label: const Text('Voice Call Devotee', style: TextStyle(fontWeight: FontWeight.bold)),
            onPressed: () {
              final callProvider = Provider.of<CallProvider>(context, listen: false);
              callProvider.startCall(phoneNumber: phone, customerName: name, gotra: gotra);
            },
          ),
        ],
      ),
    );
  }

  Widget _buildQuickPujaChip(String label, String message) {
    return InkWell(
      onTap: () {
        _messageController.text = message;
      },
      borderRadius: BorderRadius.circular(6),
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
        decoration: BoxDecoration(
          color: const Color(0xFF202C33),
          borderRadius: BorderRadius.circular(6),
          border: Border.all(color: const Color(0xFF2A3942)),
        ),
        child: Text(
          label,
          style: const TextStyle(fontSize: 11, color: Colors.white70),
        ),
      ),
    );
  }

  void _showDeviceConnectionModal() async {
    showDialog(
      context: context,
      builder: (ctx) {
        bool isLoading = false;
        String? qrBase64 = _gatewayStatus['qr_code'];
        String? pairingCode = _gatewayStatus['pairing_code'];
        String connectionState = _gatewayStatus['connection_state'] ?? 'unknown';

        return StatefulBuilder(
          builder: (dialogCtx, setDialogState) {
            final isConnected = connectionState.toLowerCase() == 'open';

            Future<void> refreshQr() async {
              setDialogState(() => isLoading = true);
              final status = await _api.fetchWhatsappStatus(requestQr: true);
              if (mounted) {
                setState(() => _gatewayStatus = status);
              }
              setDialogState(() {
                isLoading = false;
                qrBase64 = status['qr_code'];
                pairingCode = status['pairing_code'];
                connectionState = status['connection_state'] ?? 'unknown';
              });
            }

            Future<void> requestPairCode() async {
              setDialogState(() => isLoading = true);
              final res = await _api.fetchWhatsappPairingCode(phone: '917007158014');
              setDialogState(() {
                isLoading = false;
                if (res['pairingCode'] != null) {
                  pairingCode = res['pairingCode'].toString();
                }
              });
            }

            Widget qrWidget;
            if (qrBase64 != null && qrBase64!.isNotEmpty) {
              try {
                final cleanB64 = qrBase64!.contains(',') ? qrBase64!.split(',').last : qrBase64!;
                final bytes = base64Decode(cleanB64.trim());
                qrWidget = Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Image.memory(bytes, width: 200, height: 200, fit: BoxFit.contain),
                );
              } catch (_) {
                qrWidget = const Text('Error rendering QR Code. Please refresh.', style: TextStyle(color: Colors.redAccent));
              }
            } else if (isLoading) {
              qrWidget = const Padding(
                padding: EdgeInsets.all(32),
                child: CircularProgressIndicator(color: ManagerTheme.brandGold),
              );
            } else {
              qrWidget = Container(
                width: 200,
                height: 200,
                decoration: BoxDecoration(
                  color: Colors.black26,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.white12),
                ),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.qr_code_2_rounded, size: 48, color: Colors.white38),
                    const SizedBox(height: 8),
                    const Text('No QR Code cached', style: TextStyle(color: Colors.white54, fontSize: 12)),
                    const SizedBox(height: 8),
                    TextButton(
                      onPressed: refreshQr,
                      child: const Text('Fetch Live QR', style: TextStyle(color: ManagerTheme.brandGold)),
                    ),
                  ],
                ),
              );
            }

            return AlertDialog(
              backgroundColor: const Color(0xFF1E293B),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              title: const Row(
                children: [
                  Icon(Icons.phone_android_rounded, color: Color(0xFF25D366), size: 20),
                  SizedBox(width: 8),
                  Text('WhatsApp Evolution Gateway', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white)),
                ],
              ),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    if (isConnected) ...[
                      const Icon(Icons.check_circle_rounded, color: Color(0xFF10B981), size: 56),
                      const SizedBox(height: 8),
                      const Text('Connected & Live!', style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF10B981), fontSize: 16)),
                      const SizedBox(height: 4),
                      const Text(
                        'Device +91 7007158014 is receiving devotee inquiries with full AI Co-pilot support.',
                        textAlign: TextAlign.center,
                        style: TextStyle(color: Colors.white70, fontSize: 12),
                      ),
                    ] else ...[
                      qrWidget,
                      const SizedBox(height: 12),
                      const Text(
                        '1. Open WhatsApp on phone (+91 7007158014)\n2. Tap Linked Devices > Link a Device\n3. Scan the QR code above',
                        style: TextStyle(color: Colors.white70, fontSize: 12, height: 1.4),
                      ),
                      if (pairingCode != null && pairingCode!.isNotEmpty) ...[
                        const SizedBox(height: 12),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                          decoration: BoxDecoration(color: Colors.black45, borderRadius: BorderRadius.circular(8)),
                          child: SelectableText(
                            'Pairing Code: $pairingCode',
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: ManagerTheme.brandGold, letterSpacing: 2),
                          ),
                        ),
                      ],
                    ],
                  ],
                ),
              ),
              actions: [
                if (!isConnected)
                  TextButton.icon(
                    icon: const Icon(Icons.dialpad_rounded, size: 16),
                    label: const Text('Pairing Code'),
                    onPressed: isLoading ? null : requestPairCode,
                  ),
                ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(backgroundColor: ManagerTheme.brandGold, foregroundColor: Colors.black),
                  icon: const Icon(Icons.refresh_rounded, size: 16),
                  label: Text(isConnected ? 'Refresh' : 'Refresh QR'),
                  onPressed: isLoading ? null : refreshQr,
                ),
                TextButton(
                  onPressed: () => Navigator.pop(ctx),
                  child: const Text('Close', style: TextStyle(color: Colors.white54)),
                ),
              ],
            );
          },
        );
      },
    );
  }

  String _formatTime(dynamic val) {
    if (val == null) return '';
    try {
      final dt = DateTime.parse(val.toString());
      final hour = dt.hour.toString().padLeft(2, '0');
      final minute = dt.minute.toString().padLeft(2, '0');
      return '$hour:$minute';
    } catch (_) {
      return val.toString();
    }
  }
}
