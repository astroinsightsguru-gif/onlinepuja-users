import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:http/http.dart' as http;
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../state/app_session.dart';
import '../../theme/partner_theme.dart';

class MasterAiChatbotScreen extends StatefulWidget {
  final bool showAppBar;
  const MasterAiChatbotScreen({super.key, this.showAppBar = true});

  static const route = '/master-ai-chatbot';

  @override
  State<MasterAiChatbotScreen> createState() => _MasterAiChatbotScreenState();
}

class _ChatMessage {
  final String text;
  final bool isUser;
  final DateTime timestamp;

  _ChatMessage({
    required this.text,
    required this.isUser,
    DateTime? timestamp,
  }) : timestamp = timestamp ?? DateTime.now();
}

class _MasterAiChatbotScreenState extends State<MasterAiChatbotScreen> {
  final TextEditingController _textController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  final List<_ChatMessage> _messages = [];
  bool _isLoading = false;

  final List<String> _quickPrompts = [
    'विवाह बाधा व मांगलिक दोष निवारण के अचूक उपाय बताएं',
    'शनि साढ़े साती एवं ढैय्या शांति हेतु कौन सा रुद्राभिषेक श्रेष्ठ है?',
    'कालसर्प दोष के लक्षण, प्रकार एवं उज्जैन भैरव शांति विधान बताएं',
    'आज के दिन का शुभ अभिजीत मुहूर्त व चौघड़िया विवरण',
    'संतान प्राप्ति व स्वास्थ्य रक्षा हेतु संजीवनी महामृत्युंजय अनुष्ठान',
    'व्यापार वृद्धि व ऋण मुक्ति हेतु महालक्ष्मी कुबेर साधना',
  ];

  @override
  void initState() {
    super.initState();
    _messages.add(
      _ChatMessage(
        text: 'प्रणाम पूज्य पंडित जी! 🙏\n'
            'मैं आपका **मास्टर AI ज्योतिषी सह-पायलट** हूँ।\n\n'
            'आप मुझसे किसी भी यजमान की कुंडली, ग्रह दोष, गोचर, दशा फल, मुहूर्त अथवा वैदिक पूजा अनुष्ठानों के संदर्भ में प्रमाणिक मार्गदर्शन प्राप्त कर सकते हैं।\n\n'
            'कृपया अपना प्रश्न पूछें अथवा नीचे दिए गए त्वरित विषयों का चयन करें:',
        isUser: false,
      ),
    );
  }

  @override
  void dispose() {
    _textController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  Future<void> _sendMessage([String? presetText]) async {
    final query = presetText ?? _textController.text.trim();
    if (query.isEmpty || _isLoading) return;

    if (presetText == null) {
      _textController.clear();
    }

    setState(() {
      _messages.add(_ChatMessage(text: query, isUser: true));
      _isLoading = true;
    });
    _scrollToBottom();

    final session = context.read<PartnerSession>();
    final astroId = session.astrologerId > 0 ? session.astrologerId : 1;

    try {
      final res = await http.post(
        Uri.parse('https://onlinepuja.live/api/master-ai-response'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'message': query,
          'astrologer_id': astroId,
          'astrologerId': astroId,
        }),
      );

      String reply = 'क्षमा करें, सर्वर से संपर्क नहीं हो सका। कृपया पुनः प्रयास करें।';
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        reply = (data['message'] ?? data['recordList']?['message'] ?? '').toString().trim();
        if (reply.isEmpty) {
          reply = 'प्रणाम! ग्रह गोचर का विश्लेषण पूर्ण हुआ। कृपया अपने प्रश्न का पुनः अवलोकन करें।';
        }
      }

      setState(() {
        _messages.add(_ChatMessage(text: reply, isUser: false));
      });
    } catch (e) {
      setState(() {
        _messages.add(
          _ChatMessage(
            text: 'त्रुटि: नेटवर्क कनेक्शन की जांच करें ($e)',
            isUser: false,
          ),
        );
      });
    } finally {
      setState(() => _isLoading = false);
      _scrollToBottom();
    }
  }

  void _copyToClipboard(String text) {
    Clipboard.setData(ClipboardData(text: text));
    HapticFeedback.lightImpact();
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('✓ उत्तर कॉपी हो गया!'),
        duration: Duration(seconds: 1),
      ),
    );
  }

  void _shareToWhatsApp(String text) {
    final cleanText = '🕉️ *ॐ नमः शिवाय* 🙏\n\n'
        'पूज्य पंडित जी द्वारा वेदोक्त ज्योतिषीय परामर्श:\n\n'
        '$text\n\n'
        '🪔 *OnlinePuja.live* — काशी विश्वनाथ धाम से प्रमाणित वैदिक अनुष्ठान';
    final url = 'https://wa.me/?text=${Uri.encodeComponent(cleanText)}';
    launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication);
  }

  @override
  Widget build(BuildContext context) {
    final dark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      appBar: widget.showAppBar
          ? AppBar(
              titleSpacing: 0,
              title: const Row(
                children: [
                  CircleAvatar(
                    radius: 18,
                    backgroundColor: PartnerTheme.gold,
                    child: Icon(Icons.auto_awesome_rounded, color: Colors.black, size: 20),
                  ),
                  SizedBox(width: 10),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'मास्टर AI ज्योतिषी',
                        style: TextStyle(fontWeight: FontWeight.w900, fontSize: 16),
                      ),
                      Text(
                        'पंडित जी का वैदिक AI सह-पायलट',
                        style: TextStyle(fontSize: 11, color: Colors.grey),
                      ),
                    ],
                  ),
                ],
              ),
              actions: [
                IconButton(
                  tooltip: 'नया वार्तालाप (Clear Chat)',
                  icon: const Icon(Icons.refresh_rounded),
                  onPressed: () {
                    setState(() {
                      _messages.clear();
                      _messages.add(
                        _ChatMessage(
                          text: 'नया वार्तालाप प्रारंभ हुआ। कृपया अपना प्रश्न पूछें:',
                          isUser: false,
                        ),
                      );
                    });
                  },
                ),
              ],
            )
          : null,
      body: Column(
        children: [
          // 1. Quick Prompts Horizontal Bar
          Container(
            padding: const EdgeInsets.symmetric(vertical: 8),
            decoration: BoxDecoration(
              color: dark ? PartnerTheme.darkSurface : const Color(0xFFFFFBEB),
              border: Border(
                bottom: BorderSide(
                  color: dark ? PartnerTheme.darkBorder : const Color(0xFFFDE68A),
                ),
              ),
            ),
            child: SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 12),
              child: Row(
                children: _quickPrompts.map((p) {
                  return Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: ActionChip(
                      backgroundColor: dark ? const Color(0xFF1E293B) : Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                        side: BorderSide(
                          color: dark ? PartnerTheme.darkBorder : const Color(0xFFF59E0B),
                        ),
                      ),
                      label: Text(
                        p,
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                          color: dark ? Colors.white : const Color(0xFF92400E),
                        ),
                      ),
                      onPressed: () => _sendMessage(p),
                    ),
                  );
                }).toList(),
              ),
            ),
          ),

          // 2. Chat Messages Stream
          Expanded(
            child: ListView.builder(
              controller: _scrollController,
              padding: const EdgeInsets.fromLTRB(14, 12, 14, 12),
              itemCount: _messages.length,
              itemBuilder: (context, index) {
                final msg = _messages[index];
                return _buildMessageBubble(msg, dark);
              },
            ),
          ),

          // 3. Loading Indicator
          if (_isLoading)
            Container(
              padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 16),
              child: const Row(
                children: [
                  SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(strokeWidth: 2, color: PartnerTheme.gold),
                  ),
                  SizedBox(width: 10),
                  Text(
                    'ग्रह-गोचर एवं वैदिक शास्त्रों का गहन विश्लेषण हो रहा है...',
                    style: TextStyle(fontSize: 12, fontStyle: FontStyle.italic, color: Colors.grey),
                  ),
                ],
              ),
            ),

          // 4. Input Field
          Container(
            padding: EdgeInsets.fromLTRB(12, 8, 12, MediaQuery.of(context).padding.bottom + 8),
            decoration: BoxDecoration(
              color: dark ? PartnerTheme.darkSurface : Colors.white,
              border: Border(
                top: BorderSide(
                  color: dark ? PartnerTheme.darkBorder : const Color(0xFFEBE3D7),
                ),
              ),
            ),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _textController,
                    minLines: 1,
                    maxLines: 4,
                    textInputAction: TextInputAction.send,
                    onSubmitted: (_) => _sendMessage(),
                    decoration: InputDecoration(
                      hintText: 'पूछें: कुंडली दोष, मुहूर्त, उपाय या पूजा...',
                      hintStyle: const TextStyle(fontSize: 13, color: Colors.grey),
                      isDense: true,
                      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(24),
                        borderSide: BorderSide(
                          color: dark ? PartnerTheme.darkBorder : const Color(0xFFCBD5E1),
                        ),
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(24),
                        borderSide: const BorderSide(color: PartnerTheme.gold, width: 1.5),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                CircleAvatar(
                  backgroundColor: PartnerTheme.gold,
                  radius: 20,
                  child: IconButton(
                    icon: const Icon(Icons.send_rounded, color: Colors.black, size: 18),
                    onPressed: () => _sendMessage(),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMessageBubble(_ChatMessage msg, bool dark) {
    if (msg.isUser) {
      return Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.end,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Flexible(
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                decoration: BoxDecoration(
                  color: PartnerTheme.gold,
                  borderRadius: const BorderRadius.only(
                    topLeft: Radius.circular(16),
                    topRight: Radius.circular(16),
                    bottomLeft: Radius.circular(16),
                    bottomRight: Radius.circular(4),
                  ),
                ),
                child: Text(
                  msg.text,
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: Colors.black,
                  ),
                ),
              ),
            ),
            const SizedBox(width: 6),
            const CircleAvatar(
              radius: 12,
              backgroundColor: Color(0xFFF59E0B),
              child: Icon(Icons.person_rounded, size: 14, color: Colors.white),
            ),
          ],
        ),
      );
    }

    // AI Message
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.start,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const CircleAvatar(
            radius: 14,
            backgroundColor: Color(0xFFB45309),
            child: Icon(Icons.auto_awesome_rounded, size: 14, color: Colors.white),
          ),
          const SizedBox(width: 8),
          Flexible(
            child: Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: dark ? PartnerTheme.darkSurface : const Color(0xFFF8FAFC),
                borderRadius: const BorderRadius.only(
                  topLeft: Radius.circular(4),
                  topRight: Radius.circular(16),
                  bottomLeft: Radius.circular(16),
                  bottomRight: Radius.circular(16),
                ),
                border: Border.all(
                  color: dark ? PartnerTheme.darkBorder : const Color(0xFFE2E8F0),
                ),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.03),
                    blurRadius: 4,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  SelectableText(
                    msg.text,
                    style: TextStyle(
                      fontSize: 14,
                      height: 1.45,
                      color: dark ? Colors.white : const Color(0xFF1E293B),
                    ),
                  ),
                  const SizedBox(height: 10),
                  const Divider(height: 1),
                  const SizedBox(height: 6),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      TextButton.icon(
                        style: TextButton.styleFrom(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          minimumSize: Size.zero,
                          tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                        ),
                        icon: const Icon(Icons.copy_rounded, size: 13, color: Colors.grey),
                        label: const Text('कॉपी (Copy)', style: TextStyle(fontSize: 11, color: Colors.grey)),
                        onPressed: () => _copyToClipboard(msg.text),
                      ),
                      const SizedBox(width: 8),
                      ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF25D366),
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          minimumSize: Size.zero,
                          tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        icon: const Icon(Icons.chat_bubble_rounded, size: 13),
                        label: const Text('यजमान को भेजें', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                        onPressed: () => _shareToWhatsApp(msg.text),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
