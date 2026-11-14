import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../models/puja_quote_model.dart';
import '../services/manager_api_service.dart';

class QuotationProvider extends ChangeNotifier {
  final ManagerApiService apiService;

  List<PujaQuoteModel> _quotes = [];
  List<PujaQuoteModel> get quotes => _quotes;

  QuotationProvider({required this.apiService}) {
    _quotes = [];
  }

  void addQuote(PujaQuoteModel quote) {
    _quotes.insert(0, quote);
    notifyListeners();
  }

  String formatWhatsAppProposal(PujaQuoteModel q) {
    final buffer = StringBuffer();
    buffer.writeln('🕉️ *ॐ नमः शिवाय* 🙏\n');
    buffer.writeln('*OnlinePuja.live — Sanctified Vedic Proposal*');
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
    if (q.notes.isNotEmpty) {
      buffer.writeln('\n_${q.notes}_');
    }
    buffer.writeln('\n_हर हर महादेव 🔱 · OnlinePuja.live Varanasi_');

    return buffer.toString();
  }

  Future<bool> sendProposalViaWhatsApp(PujaQuoteModel quote) async {
    final text = formatWhatsAppProposal(quote);
    String cleanPhone = quote.devoteePhone.replaceAll(RegExp(r'[^0-9]'), '');
    if (cleanPhone.length == 10) cleanPhone = '91$cleanPhone';

    // 1. Direct dispatch via Evolution API v2 gateway
    try {
      await apiService.sendWhatsappMessage(
        jid: '$cleanPhone@s.whatsapp.net',
        phone: cleanPhone,
        message: text,
      );
    } catch (_) {}

    // 2. Also offer browser / app fallback
    final uri = Uri.parse('https://wa.me/$cleanPhone?text=${Uri.encodeComponent(text)}');
    try {
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      }
      return true;
    } catch (_) {
      return false;
    }
  }
}
