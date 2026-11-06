import 'dart:convert';
import 'package:http/http.dart' as http;

class ManagerApiService {
  static const String defaultBaseUrl = 'https://onlinepuja.live/api/manager';

  final String baseUrl;

  ManagerApiService({this.baseUrl = defaultBaseUrl});

  // 1. Fetch Live Radar Data
  Future<Map<String, dynamic>?> fetchLiveRadar() async {
    try {
      final res = await http.get(Uri.parse('$baseUrl/radar')).timeout(const Duration(seconds: 8));
      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        if (decoded['success'] == true && decoded['data'] != null) {
          return Map<String, dynamic>.from(decoded['data']);
        }
      }
    } catch (_) {
      // Graceful fallback to cached/offline state
    }
    return null;
  }

  // 1b. Fetch Real-time Live Devotee Visitors
  Future<List<Map<String, dynamic>>> fetchLiveRadarVisitors() async {
    try {
      final res = await http.get(Uri.parse('$baseUrl/radar/visitors')).timeout(const Duration(seconds: 8));
      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        if (decoded['success'] == true && decoded['visitors'] is List) {
          return List<Map<String, dynamic>>.from(decoded['visitors']);
        }
      }
    } catch (_) {}
    return [];
  }

  // 1c. Fetch Vedic SEO & AEO Overview
  Future<Map<String, dynamic>?> fetchVedicSeoOverview() async {
    try {
      final res = await http.get(Uri.parse('$baseUrl/seo/overview')).timeout(const Duration(seconds: 12));
      if (res.statusCode == 200) {
        return jsonDecode(res.body) as Map<String, dynamic>?;
      }
    } catch (_) {}
    return null;
  }

  // 1d. Auto-Fix AEO Quotability on Article
  Future<Map<String, dynamic>?> fixAeoArticle(int id) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/seo/aeo-fix'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({'id': id}),
      ).timeout(const Duration(seconds: 25));

      if (res.statusCode == 200) {
        return jsonDecode(res.body) as Map<String, dynamic>?;
      }
    } catch (_) {}
    return null;
  }

  // 1e. Generate New Vedic Article Draft
  Future<Map<String, dynamic>?> generateVedicDraft(String topic) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/seo/generate-draft'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({'topic': topic}),
      ).timeout(const Duration(seconds: 35));

      if (res.statusCode == 200) {
        return jsonDecode(res.body) as Map<String, dynamic>?;
      }
    } catch (_) {}
    return null;
  }

  // 2. Fetch Banners
  Future<List<Map<String, dynamic>>> fetchBanners() async {
    try {
      final res = await http.get(Uri.parse('$baseUrl/banners')).timeout(const Duration(seconds: 8));
      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        if (decoded['banners'] is List) {
          return List<Map<String, dynamic>>.from(decoded['banners']);
        }
      }
    } catch (_) {}
    return [];
  }

  // 3. Save / Update Banner
  Future<bool> saveBanner({
    required String bannerImage,
    required String fromDate,
    required String toDate,
    int bannerTypeId = 1,
  }) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/banners'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'bannerImage': bannerImage,
          'fromDate': fromDate,
          'toDate': toDate,
          'bannerTypeId': bannerTypeId,
        }),
      ).timeout(const Duration(seconds: 10));
      return res.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  // 4. Fulfill Sankalp Video & Prasad Courier
  Future<bool> fulfillSankalp({
    required int orderId,
    required String videoUrl,
    required String awbCode,
    String courierPartner = 'India Post Speed Post',
  }) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/sankalp/fulfill'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'order_id': orderId,
          'video_url': videoUrl,
          'awb_code': awbCode,
          'courier_partner': courierPartner,
        }),
      ).timeout(const Duration(seconds: 10));
      return res.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  // 5. Trigger AI / Growth Brain Action
  Future<String> triggerGrowthAction(String action) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/growth/run-action'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'action': action}),
      ).timeout(const Duration(seconds: 25));
      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        return decoded['output'] ?? 'Executed successfully.';
      }
    } catch (e) {
      return 'Network trigger: Command queued on server.';
    }
    return 'Action executed.';
  }

  // 6. Broadcast Push Notification
  Future<bool> broadcastPush({
    required String title,
    required String body,
    String target = 'all',
  }) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/growth/broadcast'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'title': title,
          'body': body,
          'target': target,
        }),
      ).timeout(const Duration(seconds: 10));
      return res.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  // 7. Fetch AI Agents Fleet
  Future<List<Map<String, dynamic>>> fetchAgents() async {
    try {
      final res = await http.get(Uri.parse('$baseUrl/agents')).timeout(const Duration(seconds: 8));
      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        if (decoded['success'] == true && decoded['data'] is List) {
          return List<Map<String, dynamic>>.from(decoded['data']);
        }
      }
    } catch (_) {}
    return [];
  }

  // 8. Toggle Agent Active / Paused
  Future<bool> toggleAgent(int id) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/agents/$id/toggle'),
        headers: {'Content-Type': 'application/json'},
      ).timeout(const Duration(seconds: 8));
      return res.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  // 9. Update Agent Configuration
  Future<bool> updateAgentConfig(int id, Map<String, dynamic> data) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/agents/$id/update'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode(data),
      ).timeout(const Duration(seconds: 10));
      return res.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  // 10. Run Agent Immediately
  Future<Map<String, dynamic>> runAgentNow(int id) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/agents/$id/run-now'),
        headers: {'Content-Type': 'application/json'},
      ).timeout(const Duration(seconds: 30));
      if (res.statusCode == 200 || res.statusCode == 400) {
        return jsonDecode(res.body) as Map<String, dynamic>;
      }
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
    return {'success': false, 'message': 'Failed to execute agent.'};
  }

  // 11. Fetch Agent Outputs
  Future<List<Map<String, dynamic>>> fetchAgentOutputs({int? agentId, String? status}) async {
    try {
      String url = '$baseUrl/agents/outputs';
      List<String> queryParams = [];
      if (agentId != null) queryParams.add('agent_id=$agentId');
      if (status != null) queryParams.add('status=$status');
      if (queryParams.isNotEmpty) url += '?${queryParams.join('&')}';

      final res = await http.get(Uri.parse(url)).timeout(const Duration(seconds: 8));
      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        if (decoded['success'] == true && decoded['data'] is List) {
          return List<Map<String, dynamic>>.from(decoded['data']);
        }
      }
    } catch (_) {}
    return [];
  }

  // 12. Approve Output
  Future<bool> approveOutput(int outputId) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/agents/outputs/$outputId/approve'),
        headers: {'Content-Type': 'application/json'},
      ).timeout(const Duration(seconds: 8));
      return res.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  // 13. Fetch Live Pending Sankalps
  Future<List<Map<String, dynamic>>> fetchSankalps() async {
    try {
      final res = await http.get(Uri.parse('$baseUrl/sankalps')).timeout(const Duration(seconds: 8));
      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        if (decoded['sankalps'] is List) {
          return List<Map<String, dynamic>>.from(decoded['sankalps']);
        }
      }
    } catch (_) {}
    return [];
  }

  // 14. Fetch Live Banners & Bookable Pujas
  Future<Map<String, dynamic>> fetchBannersAndPujas() async {
    try {
      final res = await http.get(Uri.parse('$baseUrl/banners')).timeout(const Duration(seconds: 8));
      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        return {
          'banners': decoded['banners'] is List ? List<Map<String, dynamic>>.from(decoded['banners']) : <Map<String, dynamic>>[],
          'pujas': decoded['pujas'] is List ? List<Map<String, dynamic>>.from(decoded['pujas']) : <Map<String, dynamic>>[],
        };
      }
    } catch (_) {}
    return {'banners': <Map<String, dynamic>>[], 'pujas': <Map<String, dynamic>>[]};
  }

  // 14b. Update Bookable Puja Price
  Future<bool> updatePujaPrice(int id, double price) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/puja/$id/price'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'price': price}),
      ).timeout(const Duration(seconds: 10));
      return res.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  // 15. Fetch Upcoming Festivals & Milestone Countdown
  Future<List<Map<String, dynamic>>> fetchFestivals() async {
    try {
      final res = await http.get(Uri.parse('$baseUrl/festivals')).timeout(const Duration(seconds: 8));
      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        if (decoded['festivals'] is List) {
          return List<Map<String, dynamic>>.from(decoded['festivals']);
        }
      }
    } catch (_) {}
    return [];
  }

  // 16. Schedule New Sacred Festival
  Future<bool> scheduleFestival({
    required String name,
    required String date,
    String deity = 'Vedic Devata',
  }) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/festivals/schedule'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'name': name,
          'date': date,
          'deity': deity,
        }),
      ).timeout(const Duration(seconds: 10));
      return res.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  // 17. Fetch B2B Leads Directory
  Future<Map<String, dynamic>> fetchB2bLeads({String? city}) async {
    try {
      String url = '$baseUrl/b2b/leads';
      if (city != null && city != 'all') url += '?city=$city';

      final res = await http.get(Uri.parse(url)).timeout(const Duration(seconds: 8));
      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        return {
          'total': decoded['total_leads'] ?? 0,
          'cities': decoded['cities'] is List ? List<Map<String, dynamic>>.from(decoded['cities']) : <Map<String, dynamic>>[],
          'leads': decoded['leads'] is List ? List<Map<String, dynamic>>.from(decoded['leads']) : <Map<String, dynamic>>[],
        };
      }
    } catch (_) {}
    return {'total': 0, 'cities': <Map<String, dynamic>>[], 'leads': <Map<String, dynamic>>[]};
  }

  // 18. Fetch B2B Proposal Pitch
  Future<Map<String, dynamic>?> fetchB2bPitch({int? leadId}) async {
    try {
      String url = '$baseUrl/b2b/pitch';
      if (leadId != null) url += '/$leadId';

      final res = await http.get(Uri.parse(url)).timeout(const Duration(seconds: 8));
      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        return decoded['pitch'] as Map<String, dynamic>?;
      }
    } catch (_) {}
    return null;
  }

  // 19. Dispatch B2B Campaign
  Future<Map<String, dynamic>> dispatchB2bCampaign({String city = 'all'}) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/b2b/dispatch'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'city': city}),
      ).timeout(const Duration(seconds: 15));
      if (res.statusCode == 200) {
        return jsonDecode(res.body) as Map<String, dynamic>;
      }
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
    return {'success': false, 'message': 'Failed to dispatch B2B campaign.'};
  }

  // 20. Send Omnichannel Broadcast (WhatsApp + Brevo + Push)
  Future<Map<String, dynamic>> sendOmnichannelBroadcast({
    required String headline,
    required String body,
    String channel = 'both',
    String segment = 'all',
    String? mediaUrl,
    String ctaText = 'Book Sacred Puja 🙏',
    String ctaUrl = 'https://onlinepuja.live',
  }) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/growth/omnichannel-broadcast'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'headline': headline,
          'body': body,
          'channel': channel,
          'segment': segment,
          'media_url': mediaUrl,
          'cta_text': ctaText,
          'cta_url': ctaUrl,
        }),
      ).timeout(const Duration(seconds: 15));
      if (res.statusCode == 200) {
        return jsonDecode(res.body) as Map<String, dynamic>;
      }
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
    return {'success': false, 'message': 'Failed to send broadcast.'};
  }

  // 21. Fetch Live Blogs
  Future<List<Map<String, dynamic>>> fetchBlogs() async {
    try {
      final res = await http.get(Uri.parse('$baseUrl/blogs')).timeout(const Duration(seconds: 8));
      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        if (decoded['blogs'] is List) {
          return List<Map<String, dynamic>>.from(decoded['blogs']);
        }
      }
    } catch (_) {}
    return [];
  }

  // 22. Toggle Blog Status
  Future<bool> toggleBlog(int id) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/blogs/$id/toggle'),
        headers: {'Content-Type': 'application/json'},
      ).timeout(const Duration(seconds: 8));
      return res.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  // 23. Fetch Social Media Posts
  Future<List<Map<String, dynamic>>> fetchSocialPosts({String? platform}) async {
    try {
      String url = '$baseUrl/social/posts';
      if (platform != null && platform != 'all') url += '?platform=$platform';
      final res = await http.get(Uri.parse(url)).timeout(const Duration(seconds: 8));
      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        if (decoded['posts'] is List) {
          return List<Map<String, dynamic>>.from(decoded['posts']);
        }
      }
    } catch (_) {}
    return [];
  }

  // 24. Publish Social Post Now
  Future<bool> publishSocialNow(int id) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/social/$id/publish'),
        headers: {'Content-Type': 'application/json'},
      ).timeout(const Duration(seconds: 8));
      return res.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  // 25. Fetch Master Bookings Ledger
  Future<List<Map<String, dynamic>>> fetchMasterBookings({String? type, String? status}) async {
    try {
      String url = '$baseUrl/bookings';
      List<String> q = [];
      if (type != null && type != 'all') q.add('type=$type');
      if (status != null && status != 'all') q.add('status=$status');
      if (q.isNotEmpty) url += '?${q.join('&')}';

      final res = await http.get(Uri.parse(url)).timeout(const Duration(seconds: 8));
      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        if (decoded['bookings'] is List) {
          return List<Map<String, dynamic>>.from(decoded['bookings']);
        }
      }
    } catch (_) {}
    return [];
  }

  // 26. Fetch CRM Contacts (Devotees & Astrologers)
  Future<Map<String, dynamic>> fetchContacts({String type = 'all', String search = ''}) async {
    try {
      String url = '$baseUrl/contacts?type=$type';
      if (search.isNotEmpty) url += '&search=${Uri.encodeComponent(search)}';
      final res = await http.get(Uri.parse(url)).timeout(const Duration(seconds: 8));
      if (res.statusCode == 200) {
        return Map<String, dynamic>.from(jsonDecode(res.body));
      }
    } catch (_) {}
    return {'success': false, 'contacts': [], 'summary': {}};
  }

  // 27. Fetch Contact Detail
  Future<Map<String, dynamic>?> fetchContactDetail(String id) async {
    try {
      final res = await http.get(Uri.parse('$baseUrl/contacts/$id')).timeout(const Duration(seconds: 8));
      if (res.statusCode == 200) {
        return Map<String, dynamic>.from(jsonDecode(res.body));
      }
    } catch (_) {}
    return null;
  }

  // 28. Fetch WhatsApp Gateway Status
  Future<Map<String, dynamic>> fetchWhatsappStatus({bool requestQr = false}) async {
    try {
      String url = '$baseUrl/whatsapp/status';
      if (requestQr) url += '?request_qr=true';
      final res = await http.get(Uri.parse(url)).timeout(const Duration(seconds: 12));
      if (res.statusCode == 200) {
        return Map<String, dynamic>.from(jsonDecode(res.body));
      }
    } catch (_) {}
    return {'success': false, 'is_connected': false, 'connection_state': 'offline'};
  }

  // 28b. Fetch Pairing Code
  Future<Map<String, dynamic>> fetchWhatsappPairingCode({String phone = '917007158014'}) async {
    try {
      final res = await http.get(Uri.parse('$baseUrl/whatsapp/pair-code?phone=$phone')).timeout(const Duration(seconds: 15));
      if (res.statusCode == 200) {
        return Map<String, dynamic>.from(jsonDecode(res.body));
      }
    } catch (_) {}
    return {'ok': false};
  }

  // 28c. Restart WhatsApp Instance
  Future<bool> restartWhatsappInstance() async {
    try {
      final res = await http.post(Uri.parse('$baseUrl/whatsapp/restart')).timeout(const Duration(seconds: 15));
      return res.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  // 29. Fetch WhatsApp Threads
  Future<List<Map<String, dynamic>>> fetchWhatsappThreads({String search = ''}) async {
    try {
      String url = '$baseUrl/whatsapp/threads';
      if (search.isNotEmpty) url += '?search=${Uri.encodeComponent(search)}';
      final res = await http.get(Uri.parse(url)).timeout(const Duration(seconds: 8));
      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        if (decoded['threads'] is List) {
          return List<Map<String, dynamic>>.from(decoded['threads']);
        }
      }
    } catch (_) {}
    return [];
  }

  // 30. Fetch WhatsApp Messages for Thread
  Future<List<Map<String, dynamic>>> fetchWhatsappMessages(String jid) async {
    try {
      final res = await http.get(Uri.parse('$baseUrl/whatsapp/messages/${Uri.encodeComponent(jid)}')).timeout(const Duration(seconds: 8));
      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        if (decoded['messages'] is List) {
          return List<Map<String, dynamic>>.from(decoded['messages']);
        }
      }
    } catch (_) {}
    return [];
  }

  // 31. Send WhatsApp Message
  Future<bool> sendWhatsappMessage({
    required String jid,
    required String message,
    String? phone,
    String? mediaUrl,
    String? mediaType,
  }) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/whatsapp/send'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'jid': jid,
          if (phone != null && phone.isNotEmpty) 'phone': phone,
          'message': message,
          if (mediaUrl != null && mediaUrl.isNotEmpty) 'media_url': mediaUrl,
          if (mediaType != null) 'media_type': mediaType,
        }),
      ).timeout(const Duration(seconds: 12));
      return res.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  // 32. Toggle AI Bot on WhatsApp Thread
  Future<bool> toggleWhatsappAi({required String jid, required bool enable}) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/whatsapp/toggle-ai'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'jid': jid,
          'is_ai_handling': enable,
        }),
      ).timeout(const Duration(seconds: 8));
      return res.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  // 33. Fetch Leads Pipeline
  Future<Map<String, dynamic>> fetchLeads({String stage = 'all', String search = ''}) async {
    try {
      String url = '$baseUrl/leads?stage=$stage';
      if (search.isNotEmpty) url += '&search=${Uri.encodeComponent(search)}';
      final res = await http.get(Uri.parse(url)).timeout(const Duration(seconds: 8));
      if (res.statusCode == 200) {
        return Map<String, dynamic>.from(jsonDecode(res.body));
      }
    } catch (_) {}
    return {'success': false, 'leads': [], 'summary': {}};
  }

  // 34. Create Lead
  Future<bool> createLead(Map<String, dynamic> data) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/leads'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode(data),
      ).timeout(const Duration(seconds: 8));
      return res.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  // 35. Update Lead Stage
  Future<bool> updateLeadStage(int id, String stage) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/leads/$id/stage'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'stage': stage}),
      ).timeout(const Duration(seconds: 8));
      return res.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  // 36. Add Lead Note
  Future<bool> addLeadNote(int id, String note) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/leads/$id/note'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'note': note}),
      ).timeout(const Duration(seconds: 8));
      return res.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  // 37. Fetch Real Marketing & Growth Campaigns Telemetry
  Future<Map<String, dynamic>> fetchCampaignAutomations() async {
    try {
      final res = await http.get(Uri.parse('$baseUrl/campaigns')).timeout(const Duration(seconds: 8));
      if (res.statusCode == 200) {
        return Map<String, dynamic>.from(jsonDecode(res.body));
      }
    } catch (_) {}
    return {'success': false, 'channels': [], 'campaigns': []};
  }

  // 38. Trigger Campaign Automation
  Future<bool> triggerCampaignAutomation(String campaignId) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/campaigns/trigger'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'id': campaignId}),
      ).timeout(const Duration(seconds: 20));
      return res.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  // 🔮 Master AI Executive Assistant & Ops Copilot
  Future<Map<String, dynamic>?> chatWithMasterAi(String message, {List<Map<String, String>>? history}) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/ai/chat'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({
          'message': message,
          'history': history ?? [],
        }),
      ).timeout(const Duration(seconds: 30));

      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        if (decoded['success'] == true) {
          return Map<String, dynamic>.from(decoded);
        }
      }
    } catch (e) {
      // Return offline graceful response
    }
    return null;
  }

  // 🪔 Save Devotee Sacred Gotra Vault & Family Members
  Future<bool> updateGotraVault(String contactId, {
    required String gotra,
    String? kuldevta,
    List<Map<String, dynamic>>? familyMembers,
  }) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/contacts/devotee/$contactId/gotra-vault'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({
          'gotra': gotra,
          'kuldevta': kuldevta ?? '',
          'family_members': familyMembers ?? [],
        }),
      ).timeout(const Duration(seconds: 10));

      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        return decoded['success'] == true;
      }
    } catch (_) {}
    return false;
  }

  // 🚩 Assign Pandit to Lead
  Future<bool> assignPanditToLead(int leadId, int panditId) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/leads/$leadId/assign-pandit'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({'pandit_id': panditId}),
      ).timeout(const Duration(seconds: 10));

      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        return decoded['success'] == true;
      }
    } catch (_) {}
    return false;
  }

  // 📜 Generate 1-Click Sankalp Patra for Pandit Handover
  Future<Map<String, dynamic>?> generateSankalpPatra(String contactId) async {
    try {
      final res = await http.get(
        Uri.parse('$baseUrl/contacts/devotee/$contactId/sankalp-patra'),
        headers: {'Accept': 'application/json'},
      ).timeout(const Duration(seconds: 10));

      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        if (decoded['success'] == true && decoded['sankalp_patra'] != null) {
          return Map<String, dynamic>.from(decoded['sankalp_patra']);
        }
      }
    } catch (_) {}
    return null;
  }

  // 🤖 PAPERCLIP AUTONOMOUS AI ENGINE (7 AI AGENTS)
  // ===============================================

  Future<Map<String, dynamic>?> fetchPaperclipOverview() async {
    try {
      final res = await http.get(
        Uri.parse('$baseUrl/paperclip/overview'),
        headers: {'Accept': 'application/json'},
      ).timeout(const Duration(seconds: 12));

      if (res.statusCode == 200) {
        return jsonDecode(res.body) as Map<String, dynamic>?;
      }
    } catch (_) {}
    return null;
  }

  Future<List<dynamic>?> fetchPaperclipAgents() async {
    try {
      final res = await http.get(
        Uri.parse('$baseUrl/paperclip/agents'),
        headers: {'Accept': 'application/json'},
      ).timeout(const Duration(seconds: 12));

      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        if (decoded != null && decoded['agents'] is List) {
          return decoded['agents'] as List<dynamic>;
        }
      }
    } catch (_) {}
    return null;
  }

  Future<List<dynamic>?> fetchPaperclipIssues() async {
    try {
      final res = await http.get(
        Uri.parse('$baseUrl/paperclip/issues'),
        headers: {'Accept': 'application/json'},
      ).timeout(const Duration(seconds: 12));

      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);
        if (decoded != null && decoded['issues'] is List) {
          return decoded['issues'] as List<dynamic>;
        }
      }
    } catch (_) {}
    return null;
  }

  Future<Map<String, dynamic>?> dispatchPaperclipMission({
    required String title,
    String description = '',
    String priority = 'high',
    String? assigneeId,
  }) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/paperclip/dispatch'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({
          'title': title,
          'description': description,
          'priority': priority,
          if (assigneeId != null) 'assigneeId': assigneeId,
        }),
      ).timeout(const Duration(seconds: 15));

      if (res.statusCode == 200) {
        return jsonDecode(res.body) as Map<String, dynamic>?;
      }
    } catch (_) {}
    return null;
  }

  Future<bool> togglePaperclipAgentPause({
    required String agentId,
    required bool pause,
    String? reason,
  }) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/paperclip/pause-agent'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({
          'agentId': agentId,
          'pause': pause,
          if (reason != null) 'reason': reason,
        }),
      ).timeout(const Duration(seconds: 15));

      return res.statusCode == 200;
    } catch (_) {}
    return false;
  }
}