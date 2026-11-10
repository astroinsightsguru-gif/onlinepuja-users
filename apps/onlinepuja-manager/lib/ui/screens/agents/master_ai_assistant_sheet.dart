import 'package:flutter/material.dart';
import '../../../services/manager_api_service.dart';
import '../../../services/manager_voice_helper.dart';
import '../../../theme/manager_theme.dart';

class MasterAiAssistantSheet extends StatefulWidget {
  const MasterAiAssistantSheet({super.key});

  static Future<void> show(BuildContext context) {
    return showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => const MasterAiAssistantSheet(),
    );
  }

  @override
  State<MasterAiAssistantSheet> createState() => _MasterAiAssistantSheetState();
}

class _MasterAiAssistantSheetState extends State<MasterAiAssistantSheet> {
  final TextEditingController _controller = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  final ManagerApiService _api = ManagerApiService();

  bool _isTyping = false;
  bool _isListening = false;
  bool _voiceOutputEnabled = true;
  String _activeModel = 'OmniRoute (Gemini Flash)';

  @override
  void initState() {
    super.initState();
    ManagerVoiceHelper.init(
      onResult: (text) {
        if (mounted) {
          setState(() {
            _controller.text = text;
            _isListening = false;
          });
          _sendMessage(text);
        }
      },
      onEnd: () {
        if (mounted && _isListening) {
          setState(() {
            _isListening = false;
          });
        }
      },
    );
  }

  void _toggleVoiceRecording() {
    if (_isListening) {
      ManagerVoiceHelper.stopListening();
      setState(() => _isListening = false);
    } else {
      final started = ManagerVoiceHelper.startListening();
      setState(() => _isListening = started);
    }
  }

  final List<Map<String, dynamic>> _messages = [
    {
      'role': 'assistant',
      'text': '🕉️ **Namaste, Executive Manager.**\nI am your **Master AI Ops Copilot** connected to live Varanasi sanctuary operations, live revenue, astrologers, pending sankalps, and central WhatsApp.\n\nHow may I assist you right now?',
      'time': 'Just now',
    }
  ];

  final List<String> _quickPrompts = [
    '📊 What is today\'s GMV & orders?',
    '🪔 How many sankalps are pending?',
    '⚡ Run Autopilot loop now',
    '💬 Check WhatsApp instance status',
    '🧹 Clear system application cache',
  ];

  @override
  void dispose() {
    _controller.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent + 60,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  Future<void> _sendMessage([String? textToSend]) async {
    final text = textToSend ?? _controller.text.trim();
    if (text.isEmpty || _isTyping) return;

    if (textToSend == null) {
      _controller.clear();
    }

    setState(() {
      _messages.add({
        'role': 'user',
        'text': text,
        'time': 'Now',
      });
      _isTyping = true;
    });
    _scrollToBottom();

    final response = await _api.chatWithMasterAi(text);

    if (mounted) {
      setState(() {
        _isTyping = false;
        if (response != null && response['reply'] != null) {
          _activeModel = response['model'] ?? _activeModel;
          final replyText = response['reply'].toString();
          _messages.add({
            'role': 'assistant',
            'text': replyText,
            'time': 'Now',
            'action': response['executed_action'],
          });
          if (_voiceOutputEnabled) {
            ManagerVoiceHelper.speak(replyText);
          }
        } else {
          _messages.add({
            'role': 'assistant',
            'text': '⚠️ Unable to connect to the Master Brain kernel at this moment. Please verify server connectivity.',
            'time': 'Now',
          });
        }
      });
      _scrollToBottom();
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.of(context).viewInsets.bottom;
    final height = MediaQuery.of(context).size.height * 0.88;

    return Container(
      height: height,
      margin: EdgeInsets.only(bottom: bottomInset),
      decoration: const BoxDecoration(
        color: ManagerTheme.surfaceDark,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        border: Border(
          top: BorderSide(color: ManagerTheme.brandGold, width: 1.5),
          left: BorderSide(color: Colors.white12, width: 0.5),
          right: BorderSide(color: Colors.white12, width: 0.5),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black87,
            blurRadius: 30,
            spreadRadius: 10,
          )
        ],
      ),
      child: Column(
        children: [
          // Drag handle & Header
          _buildHeader(context),
          const Divider(height: 1, color: Colors.white10),

          // Chat message list
          Expanded(
            child: ListView.builder(
              controller: _scrollController,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              itemCount: _messages.length,
              itemBuilder: (context, index) {
                final msg = _messages[index];
                final isUser = msg['role'] == 'user';
                return _buildMessageBubble(msg, isUser);
              },
            ),
          ),

          // Typing indicator
          if (_isTyping)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
              alignment: Alignment.centerLeft,
              child: Row(
                children: [
                  const SizedBox(
                    width: 14,
                    height: 14,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: ManagerTheme.brandGold,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Text(
                    'Master AI is thinking & querying operations...',
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.white.withValues(alpha: 0.6),
                      fontStyle: FontStyle.italic,
                    ),
                  ),
                ],
              ),
            ),

          // Quick action prompt chips
          _buildQuickPrompts(),

          // Text input field
          _buildInputField(),
        ],
      ),
    );
  }

  Widget _buildHeader(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(18, 12, 14, 12),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFFFF9933), ManagerTheme.brandGold],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(12),
              boxShadow: [
                BoxShadow(
                  color: ManagerTheme.brandGold.withValues(alpha: 0.3),
                  blurRadius: 8,
                )
              ],
            ),
            child: const Icon(Icons.auto_awesome, color: Colors.black, size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Row(
                  children: [
                    Text(
                      'Master AI Copilot',
                      style: TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                    SizedBox(width: 8),
                    Badge(
                      label: Text('EXECUTIVE', style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: Colors.black)),
                      backgroundColor: ManagerTheme.brandGold,
                    ),
                  ],
                ),
                Text(
                  'Connected: $_activeModel',
                  style: TextStyle(
                    fontSize: 11,
                    color: Colors.white.withValues(alpha: 0.6),
                  ),
                ),
              ],
            ),
          ),
          IconButton(
            icon: Icon(
              _voiceOutputEnabled ? Icons.volume_up_rounded : Icons.volume_off_rounded,
              color: _voiceOutputEnabled ? ManagerTheme.brandGold : Colors.white38,
              size: 20,
            ),
            tooltip: _voiceOutputEnabled ? 'Voice Output ON' : 'Voice Output Muted',
            onPressed: () {
              setState(() => _voiceOutputEnabled = !_voiceOutputEnabled);
            },
          ),
          IconButton(
            icon: const Icon(Icons.close_rounded, color: Colors.white70),
            onPressed: () => Navigator.pop(context),
          ),
        ],
      ),
    );
  }

  Widget _buildMessageBubble(Map<String, dynamic> msg, bool isUser) {
    final text = msg['text']?.toString() ?? '';
    final action = msg['action']?.toString();

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        mainAxisAlignment: isUser ? MainAxisAlignment.end : MainAxisAlignment.start,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (!isUser) ...[
            Container(
              margin: const EdgeInsets.only(top: 2, right: 8),
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: ManagerTheme.brandGold.withValues(alpha: 0.15),
                shape: BoxShape.circle,
                border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.4)),
              ),
              child: const Icon(Icons.auto_awesome, size: 14, color: ManagerTheme.brandGold),
            ),
          ],
          Flexible(
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              decoration: BoxDecoration(
                color: isUser
                    ? const Color(0xFF1E3A8A) // Royal Blue for user
                    : const Color(0xFF1E242B), // Dark Slate for assistant
                borderRadius: BorderRadius.circular(16).copyWith(
                  bottomLeft: !isUser ? const Radius.circular(2) : const Radius.circular(16),
                  bottomRight: isUser ? const Radius.circular(2) : const Radius.circular(16),
                ),
                border: Border.all(
                  color: isUser ? Colors.blueAccent.withValues(alpha: 0.4) : Colors.white12,
                  width: 1,
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (action != null) ...[
                    Container(
                      margin: const EdgeInsets.only(bottom: 6),
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: Colors.green.withValues(alpha: 0.2),
                        borderRadius: BorderRadius.circular(6),
                        border: Border.all(color: Colors.greenAccent.withValues(alpha: 0.4)),
                      ),
                      child: Text(
                        '⚡ EXECUTED: $action',
                        style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.greenAccent),
                      ),
                    ),
                  ],
                  SelectableText(
                    text,
                    style: const TextStyle(
                      fontSize: 14,
                      color: Colors.white,
                      height: 1.45,
                    ),
                  ),
                ],
              ),
            ),
          ),
          if (isUser) ...[
            Container(
              margin: const EdgeInsets.only(top: 2, left: 8),
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: Colors.blueAccent.withValues(alpha: 0.2),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.person, size: 14, color: Colors.blueAccent),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildQuickPrompts() {
    return Container(
      height: 38,
      margin: const EdgeInsets.only(bottom: 8),
      child: ListView.separated(
        padding: const EdgeInsets.symmetric(horizontal: 16),
        scrollDirection: Axis.horizontal,
        itemCount: _quickPrompts.length,
        separatorBuilder: (_, __) => const SizedBox(width: 8),
        itemBuilder: (context, i) {
          final p = _quickPrompts[i];
          return ActionChip(
            label: Text(
              p,
              style: const TextStyle(fontSize: 12, color: Colors.white70),
            ),
            backgroundColor: Colors.white.withValues(alpha: 0.05),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(18),
              side: const BorderSide(color: Colors.white12),
            ),
            onPressed: () => _sendMessage(p),
          );
        },
      ),
    );
  }

  Widget _buildInputField() {
    return Container(
      padding: const EdgeInsets.fromLTRB(14, 6, 14, 14),
      decoration: const BoxDecoration(
        color: ManagerTheme.surfaceDark,
        border: Border(top: BorderSide(color: Colors.white10)),
      ),
      child: Row(
        children: [
          Expanded(
            child: TextField(
              controller: _controller,
              style: const TextStyle(color: Colors.white, fontSize: 14),
              minLines: 1,
              maxLines: 4,
              textInputAction: TextInputAction.send,
              onSubmitted: (_) => _sendMessage(),
              decoration: InputDecoration(
                hintText: 'Ask Master AI anything about operations, GMV, sankalps...',
                hintStyle: TextStyle(color: Colors.white.withValues(alpha: 0.4), fontSize: 13),
                filled: true,
                fillColor: const Color(0xFF13181E),
                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(24),
                  borderSide: const BorderSide(color: Colors.white12),
                ),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(24),
                  borderSide: const BorderSide(color: Colors.white12),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(24),
                  borderSide: const BorderSide(color: ManagerTheme.brandGold),
                ),
              ),
            ),
          ),
          const SizedBox(width: 8),
          // 🎙️ Executive Voice Assistant Microphone
          Container(
            decoration: BoxDecoration(
              gradient: _isListening
                  ? const LinearGradient(colors: [Colors.redAccent, Colors.deepOrange])
                  : LinearGradient(colors: [Colors.white.withValues(alpha: 0.12), Colors.white.withValues(alpha: 0.05)]),
              shape: BoxShape.circle,
              border: Border.all(
                color: _isListening ? Colors.redAccent : ManagerTheme.brandGold.withValues(alpha: 0.6),
                width: 1.5,
              ),
            ),
            child: IconButton(
              icon: Icon(
                _isListening ? Icons.mic_rounded : Icons.mic_none_rounded,
                color: _isListening ? Colors.white : ManagerTheme.brandGold,
                size: 20,
              ),
              tooltip: _isListening ? 'Listening... Speak now' : 'Speak to Master AI',
              onPressed: _toggleVoiceRecording,
            ),
          ),
          const SizedBox(width: 8),
          Container(
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                colors: [Color(0xFFFF9933), ManagerTheme.brandGold],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              shape: BoxShape.circle,
            ),
            child: IconButton(
              icon: const Icon(Icons.send_rounded, color: Colors.black, size: 20),
              onPressed: () => _sendMessage(),
            ),
          ),
        ],
      ),
    );
  }
}
