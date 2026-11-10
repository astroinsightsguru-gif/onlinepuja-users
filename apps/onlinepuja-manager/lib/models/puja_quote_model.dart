class PujaQuoteItem {
  final String description;
  final double amount;
  final int quantity;

  PujaQuoteItem({
    required this.description,
    required this.amount,
    this.quantity = 1,
  });

  double get total => amount * quantity;

  factory PujaQuoteItem.fromJson(Map<String, dynamic> json) => PujaQuoteItem(
    description: json['description'] ?? 'Vedic Service',
    amount: double.tryParse(json['amount']?.toString() ?? '0') ?? 0.0,
    quantity: int.tryParse(json['quantity']?.toString() ?? '1') ?? 1,
  );

  Map<String, dynamic> toJson() => {
    'description': description,
    'amount': amount,
    'quantity': quantity,
  };
}

class PujaQuoteModel {
  final String id;
  final String devoteeName;
  final String devoteePhone;
  final String gotra;
  final String templeLocation;
  final String pujaTitle;
  final List<PujaQuoteItem> items;
  final double discount;
  final String notes;
  final DateTime createdAt;
  final String status; // 'draft', 'sent', 'paid', 'expired'
  final String? paymentLink;

  PujaQuoteModel({
    required this.id,
    required this.devoteeName,
    required this.devoteePhone,
    this.gotra = 'Kashyap',
    this.templeLocation = 'Kashi Vishwanath, Varanasi',
    required this.pujaTitle,
    required this.items,
    this.discount = 0.0,
    this.notes = 'Includes live video darshan recording and sanctified Mahaprasad via Speed Post.',
    required this.createdAt,
    this.status = 'sent',
    this.paymentLink,
  });

  double get subtotal => items.fold(0.0, (sum, item) => sum + item.total);
  double get grandTotal => (subtotal - discount).clamp(0.0, double.infinity);

  factory PujaQuoteModel.fromJson(Map<String, dynamic> json) {
    List<PujaQuoteItem> parsedItems = [];
    if (json['items'] is List) {
      parsedItems = (json['items'] as List).map((i) => PujaQuoteItem.fromJson(Map<String, dynamic>.from(i))).toList();
    } else {
      parsedItems = [
        PujaQuoteItem(description: 'Vedic Pandits Dakshina (3 Acharyas)', amount: 3300),
        PujaQuoteItem(description: 'Ganga Jal, Belpatra & Pure Havan Samagri', amount: 1500),
        PujaQuoteItem(description: 'Temple Trust Sankalp & Speed Post Prasad', amount: 500),
      ];
    }

    return PujaQuoteModel(
      id: json['id']?.toString() ?? 'quote_${DateTime.now().millisecondsSinceEpoch}',
      devoteeName: json['devotee_name'] ?? 'Devotee',
      devoteePhone: json['devotee_phone'] ?? '',
      gotra: json['gotra'] ?? 'Kashyap',
      templeLocation: json['temple_location'] ?? 'Kashi Vishwanath, Varanasi',
      pujaTitle: json['puja_title'] ?? 'Sacred Vedic Mahapuja',
      items: parsedItems,
      discount: double.tryParse(json['discount']?.toString() ?? '0') ?? 0.0,
      notes: json['notes'] ?? 'Live Sankalp video link will be sent on WhatsApp.',
      createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) ?? DateTime.now() : DateTime.now(),
      status: json['status'] ?? 'sent',
      paymentLink: json['payment_link'] ?? 'https://onlinepuja.live/pay?ref=${json['id']}',
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'devotee_name': devoteeName,
    'devotee_phone': devoteePhone,
    'gotra': gotra,
    'temple_location': templeLocation,
    'puja_title': pujaTitle,
    'items': items.map((i) => i.toJson()).toList(),
    'discount': discount,
    'notes': notes,
    'created_at': createdAt.toIso8601String(),
    'status': status,
    'payment_link': paymentLink,
    'grand_total': grandTotal,
  };
}
