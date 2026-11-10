import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../providers/language_provider.dart';
import '../../../services/manager_api_service.dart';
import '../../../theme/manager_theme.dart';

class EmailCampaignHubScreen extends StatefulWidget {
  const EmailCampaignHubScreen({super.key});

  @override
  State<EmailCampaignHubScreen> createState() => _EmailCampaignHubScreenState();
}

class _EmailCampaignHubScreenState extends State<EmailCampaignHubScreen> {
  final ManagerApiService _api = ManagerApiService();

  bool _isLoading = true;
  bool _isSyncing = false;
  Map<String, dynamic>? _emailData;
  String? _syncStatusMessage;

  @override
  void initState() {
    super.initState();
    _loadEmailOverview();
  }

  Future<void> _loadEmailOverview() async {
    setState(() => _isLoading = true);
    final data = await _api.fetchEmailOverview();
    if (mounted) {
      setState(() {
        _emailData = data;
        _isLoading = false;
      });
    }
  }

  Future<void> _syncAudience(String target) async {
    setState(() {
      _isSyncing = true;
      _syncStatusMessage = null;
    });

    final res = await _api.syncEmailContacts(target: target);
    if (mounted) {
      setState(() {
        _isSyncing = false;
        _syncStatusMessage = res['message'] ?? 'Audience synced successfully.';
      });
      _loadEmailOverview();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(_syncStatusMessage!),
          backgroundColor: res['success'] == true ? ManagerTheme.brandEmerald : Colors.redAccent,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final lang = Provider.of<LanguageProvider>(context);
    final isHindi = lang.isHindi;

    final isConfigured = _emailData?['is_configured'] == true;
    final senderEmail = _emailData?['sender_email'] ?? 'care@onlinepuja.live';
    final senderName = _emailData?['sender_name'] ?? 'Onlinepuja.live';
    final lists = _emailData?['lists'] as List<dynamic>? ?? [];
    final devoteeEmails = _emailData?['available_devotee_emails'] ?? 0;
    final b2bEmails = _emailData?['available_b2b_emails'] ?? 0;

    return Scaffold(
      backgroundColor: ManagerTheme.backgroundDark,
      appBar: AppBar(
        backgroundColor: ManagerTheme.surfaceDark,
        elevation: 0,
        title: Row(
          children: [
            const Text('📧', style: TextStyle(fontSize: 18)),
            const SizedBox(width: 8),
            Text(
              isHindi ? 'ईमेल एवं ब्रेवो ऑडियंस हब' : 'Email & Brevo Audience Hub',
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.white),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: ManagerTheme.brandGold),
            tooltip: 'Refresh Email Hub',
            onPressed: _loadEmailOverview,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: ManagerTheme.brandGold))
          : RefreshIndicator(
              color: ManagerTheme.brandGold,
              backgroundColor: ManagerTheme.surfaceDark,
              onRefresh: _loadEmailOverview,
              child: ListView(
                padding: const EdgeInsets.all(14),
                children: [
                  // Provider Status Master Card
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.3)),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              isHindi ? 'ईमेल प्रदाता स्थिति' : 'EMAIL INFRASTRUCTURE STATUS',
                              style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: ManagerTheme.brandGold, letterSpacing: 0.5),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                              decoration: BoxDecoration(
                                color: isConfigured ? const Color(0xFF25D366).withValues(alpha: 0.15) : Colors.redAccent.withValues(alpha: 0.15),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: Text(
                                isConfigured ? 'BREVO API LIVE' : 'UNCONFIGURED',
                                style: TextStyle(
                                  color: isConfigured ? const Color(0xFF25D366) : Colors.redAccent,
                                  fontSize: 10,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'Sender: $senderName <$senderEmail>',
                          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          isHindi
                              ? 'दैनिक पंचांग, ब्लॉग सूचनाएं एवं बी2बी वाणिज्यिक ईमेल ब्रेवो v3 एपीआई द्वारा प्रेषित होते हैं।'
                              : 'Transactional Sankalp proofs, devotional blogs & B2B pitches dispatched via Brevo REST API v3.',
                          style: const TextStyle(color: Colors.white60, fontSize: 11),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 14),

                  // Audience Sync Metrics Strip
                  Row(
                    children: [
                      Expanded(
                        child: Container(
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: ManagerTheme.surfaceDark,
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: Colors.white10),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(isHindi ? 'यजमान ईमेल' : 'Devotees with Email', style: const TextStyle(color: Colors.white54, fontSize: 11)),
                              const SizedBox(height: 4),
                              Text('$devoteeEmails', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 20)),
                              const SizedBox(height: 8),
                              SizedBox(
                                width: double.infinity,
                                child: OutlinedButton(
                                  style: OutlinedButton.styleFrom(
                                    foregroundColor: ManagerTheme.brandGold,
                                    side: const BorderSide(color: ManagerTheme.brandGold),
                                    visualDensity: VisualDensity.compact,
                                    padding: const EdgeInsets.symmetric(vertical: 4),
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
                                  ),
                                  onPressed: _isSyncing ? null : () => _syncAudience('devotees'),
                                  child: Text(isHindi ? 'सिंक करें' : 'Sync Devotees', style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold)),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Container(
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: ManagerTheme.surfaceDark,
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: Colors.white10),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(isHindi ? 'बी2बी ट्रेवल एजेंसी' : 'B2B Partner Emails', style: const TextStyle(color: Colors.white54, fontSize: 11)),
                              const SizedBox(height: 4),
                              Text('$b2bEmails', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 20)),
                              const SizedBox(height: 8),
                              SizedBox(
                                width: double.infinity,
                                child: ElevatedButton(
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: ManagerTheme.brandGold,
                                    foregroundColor: Colors.black,
                                    visualDensity: VisualDensity.compact,
                                    padding: const EdgeInsets.symmetric(vertical: 4),
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
                                  ),
                                  onPressed: _isSyncing ? null : () => _syncAudience('b2b'),
                                  child: Text(isHindi ? 'बी2बी सिंक' : 'Sync B2B Partners', style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold)),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 18),

                  Text(
                    isHindi ? 'ब्रेवो सक्रिय सूचियाँ (Audience Lists):' : 'Brevo Active Audience Lists:',
                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14),
                  ),
                  const SizedBox(height: 10),

                  if (lists.isEmpty)
                    Container(
                      padding: const EdgeInsets.all(24),
                      alignment: Alignment.center,
                      decoration: BoxDecoration(
                        color: ManagerTheme.surfaceDark,
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: Colors.white10),
                      ),
                      child: const Text('No audience lists configured in Brevo yet.', style: TextStyle(color: Colors.white54, fontSize: 12)),
                    )
                  else
                    ...lists.map((l) {
                      final listMap = l as Map<String, dynamic>;
                      final id = listMap['id'];
                      final name = listMap['name'] ?? 'List';
                      final subscribers = listMap['subscribers'] ?? 0;

                      return Container(
                        margin: const EdgeInsets.only(bottom: 10),
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: ManagerTheme.surfaceDark,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: Colors.white10),
                        ),
                        child: Row(
                          children: [
                            CircleAvatar(
                              radius: 18,
                              backgroundColor: ManagerTheme.brandGold.withValues(alpha: 0.15),
                              child: Text('#$id', style: const TextStyle(color: ManagerTheme.brandGold, fontWeight: FontWeight.bold, fontSize: 11)),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(name, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13)),
                                  Text('Brevo Segment List ID: $id', style: const TextStyle(color: Colors.white54, fontSize: 10.5)),
                                ],
                              ),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                              decoration: BoxDecoration(
                                color: Colors.white10,
                                borderRadius: BorderRadius.circular(4),
                              ),
                              child: Text(
                                '$subscribers ${isHindi ? 'सदस्य' : 'contacts'}',
                                style: const TextStyle(color: Colors.white70, fontSize: 11, fontFamily: 'monospace'),
                              ),
                            ),
                          ],
                        ),
                      );
                    }),
                ],
              ),
            ),
    );
  }
}
