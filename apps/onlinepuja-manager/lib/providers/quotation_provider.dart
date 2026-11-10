import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../models/puja_quote_model.dart';
import '../services/manager_api_service.dart';

class QuotationProvider extends ChangeNotifier {
  final ManagerApiService apiService;

  List<PujaQuoteModel> _quotes = [];
  List<PujaQuoteModel> get quotes => _quotes;

  QuotationProvider({required this.apiService}) {
    _initSampleQuotes();
  }

  void _initSampleQuotes() {
    _quotes = [
      PujaQuoteModel(
        id: 'PQ-2026-081',
        devoteeName: 'Rajesh Sharma',
        devoteePhone: '9810123456',
        gotra: 'Bharadwaj',
        templeLocation: 'Kashi Vishwanath, Varanasi',
        pujaTitle: 'Shravan Somwar Mahamrityunjaya Rudrabhishek',
        items: [
          PujaQuoteItem(description: '5 Vedic Acharyas Honorarium (Rudri Path)', amount: 5500),
          PujaQuoteItem(description: 'Pure Desi Ghee, Belpatra, Bhasma & Sugarcane Juice', amount: 2100),
          PujaQuoteItem(description: 'Temple Trust Sankalp Registration', amount: 1100),
          PujaQuoteItem(description: 'Speed Post Ganga Jal & Kashi Prasad AWB', amount: 250),
        ],
        discount: 500,
        createdAt: DateTime.now().subtract(const Duration(hours: 4)),
        status: 'sent',
      ),
      PujaQuoteModel(
        id: 'PQ-2026-082',
        devoteeName: 'Vikramaditya Roy',
        devoteePhone: '9748112233',
        gotra: 'Sandilya',
        templeLocation: 'Maa Kamakhya Temple, Guwahati',
        pujaTitle: 'Kamakhya Tantrokta Vighna Vinashak Yagya',
        items: [
          PujaQuoteItem(description: '3 Senior Tantra Acharyas Dakshina', amount: 7500),
          PujaQuoteItem(description: 'Special Raktavastra, Sindoor & Yagya Dravya', amount: 3500),
          PujaQuoteItem(description: 'Silver Kamakhya Yantra & Sanctified Prasad', amount: 1500),
        ],
        discount: 0,
        createdAt: DateTime.now().subtract(const Duration(days: 1)),
        status: 'paid',
      ),
    ];
  }

  void addQuote(PujaQuoteModel quote) {
    _quotes.insert(0, quote);
    notifyListeners();
  }

  String formatWhatsAppProposal(PujaQuoteModel q) {
    final buffer = StringBuffer();
    buffer.writeln('🕉️ *OnlinePuja.live — Sanctified Vedic Proposal*');
    buffer.writeln('Ref: #${q.id}');
    buffer.writeln('Devotee: *${q.devoteeName}* (Gotra: ${q.gotra})');
    buffer.writeln('Sacred Kshetra: *${q.templeLocation}*');
    buffer.writeln('Ceremony: *${q.pujaTitle}*\n');
    buffer.writeln('📋 *Itemized Dakshina Breakdown:*');

    for (var i = 0; i < q.items.length; i++) {
      final it = q.items[i];
      buffer.writeln('${i + 1}. ${it.description} — ₹${it.total.toStringAsFixed(0)}');
    }

    if (q.discount > 0) {
      buffer.writeln('\nSubtotal: ₹${q.subtotal.toStringAsFixed(0)}');
      buffer.writeln('Yagya Seva Subsidy: -₹${q.discount.toStringAsFixed(0)}');
    }

    buffer.writeln('\n🙏 *Total Dakshina:* ₹${q.grandTotal.toStringAsFixed(0)}');
    buffer.writeln('👉 *Instant Payment & Sankalp Booking:* ${q.paymentLink ?? "https://onlinepuja.live/pay?ref=${q.id}"}');
    buffer.writeln('\n_${q.notes}_');
    buffer.writeln('\n_Har Har Mahadev · OnlinePuja.live Varanasi_');

    return buffer.toString();
  }

  Future<bool> sendProposalViaWhatsApp(PujaQuoteModel quote) async {
    final text = formatWhatsAppProposal(quote);
    String cleanPhone = quote.devoteePhone.replaceAll(RegExp(r'[^0-9]'), '');
    if (cleanPhone.length == 10) cleanPhone = '91$cleanPhone';

    final uri = Uri.parse('whatsapp://send?phone=$cleanPhone&text=${Uri.encodeComponent(text)}');
    try {
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
        return true;
      } else {
        final webUri = Uri.parse('https://wa.me/$cleanPhone?text=${Uri.encodeComponent(text)}');
        await launchUrl(webUri, mode: LaunchMode.externalApplication);
        return true;
      }
    } catch (_) {
      return false;
    }
  }
}
