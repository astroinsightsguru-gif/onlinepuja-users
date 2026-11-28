import 'package:flutter/material.dart';
import 'package:op_shared/op_shared.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../state/app_session.dart';
import '../../theme/customer_theme.dart';
import '../../widgets/customer_widgets.dart';

/// Devotee Dakshina Wallet & Instant Recharge Sanctum
class WalletScreen extends StatefulWidget {
  const WalletScreen({super.key});

  static const route = '/wallet';

  @override
  State<WalletScreen> createState() => _WalletScreenState();
}

class _WalletScreenState extends State<WalletScreen> {
  static const _defaultCuratedPlans = [
    {'amount': 50, 'bonus': '100% Extra', 'bonusHi': '१००% अतिरिक्त'},
    {'amount': 100, 'bonus': 'POPULAR', 'bonusHi': 'सर्वाधिक लोकप्रिय'},
    {'amount': 200, 'bonus': '+₹20 Bonus', 'bonusHi': '+₹२० बोनस'},
    {'amount': 500, 'bonus': '+₹75 Bonus', 'bonusHi': '+₹७५ बोनस'},
    {'amount': 1000, 'bonus': '+₹200 Bonus', 'bonusHi': '+₹२०० बोनस'},
    {'amount': 2000, 'bonus': '+₹500 Bonus', 'bonusHi': '+₹५०० बोनस'},
  ];
  final _customAmountController = TextEditingController();

  late Future<List<Map<String, dynamic>>> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  @override
  void dispose() {
    _customAmountController.dispose();
    super.dispose();
  }

  Future<List<Map<String, dynamic>>> _load() async {
    final session = context.read<AppSession>();
    await session.refreshUser();
    return WalletApi.instance.rechargePlans();
  }

  void _reload() => setState(() => _future = _load());

  Future<void> _recharge(double amount, bool isHi) async {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor:
            isDark ? CustomerTheme.cosmicCardDark : Colors.white,
        title: Row(
          children: [
            const Icon(
              Icons.account_balance_wallet_rounded,
              color: CustomerTheme.sacredEmerald,
            ),
            const SizedBox(width: 8),
            Text(isHi
                ? 'वॉलेट रिचार्ज: ₹${amount.toStringAsFixed(0)}'
                : 'Recharge ₹${amount.toStringAsFixed(0)}'),
          ],
        ),
        content: Text(
          isHi
              ? 'सुरक्षित भुगतान (UPI, गूगल पे, फोनपे, कार्ड्स या नेटबैंकिंग) हेतु आपको पेमेंट गेटवे पर ले जाया जाएगा।'
              : 'You will be taken to the secure payment gateway (UPI, NetBanking, Cards) to complete this sacred recharge.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: Text(isHi ? 'रद्द करें' : 'Cancel'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(
              backgroundColor: CustomerTheme.brandSaffron,
            ),
            onPressed: () => Navigator.of(ctx).pop(true),
            child: Text(isHi ? 'भुगतान करें' : 'Proceed to Pay'),
          ),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    try {
      final payUrl = await WalletApi.instance.addPayment(amount: amount);
      if (payUrl != null && payUrl.isNotEmpty) {
        final uri = Uri.parse(payUrl);
        final launched =
            await launchUrl(uri, mode: LaunchMode.externalApplication);
        if (!launched && mounted) {
          await launchUrl(uri, mode: LaunchMode.inAppBrowserView);
        }
      }
      if (mounted) {
        showSnack(
          context,
          isHi
              ? 'शेष राशि अपडेट करने हेतु चेकआउट विंडो में भुगतान पूर्ण करें।'
              : 'Complete payment in checkout window to update balance.',
        );
        _reload();
      }
    } on ApiException catch (e) {
      if (mounted) showSnack(context, e.message, error: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    final session = context.watch<AppSession>();
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final balance = session.user?.walletAmount ?? 0;
    final currency = session.flags.currency.isNotEmpty ? session.flags.currency : '₹';

    return ValueListenableBuilder<AppLanguage>(
      valueListenable: LocaleManager.instance.currentLanguage,
      builder: (context, lang, _) {
        final isHi = lang != AppLanguage.en;

        return Scaffold(
          appBar: AppBar(
            title: Text(isHi ? 'दक्षिणा वॉलेट' : 'Dakshina Wallet'),
          ),
          body: Container(
            decoration: BoxDecoration(
              gradient: isDark
                  ? CustomerTheme.cosmicDarkGradient
                  : const LinearGradient(
                      colors: [
                        Color(0xFFFFFBEB),
                        Color(0xFFFAF7F2),
                      ],
                      begin: Alignment.topCenter,
                      end: Alignment.bottomCenter,
                    ),
            ),
            child: FutureBuilder<List<Map<String, dynamic>>>(
              future: _future,
              builder: (context, snap) {
                if (snap.connectionState == ConnectionState.waiting &&
                    !snap.hasData) {
                  return StatusViews.loading(context);
                }
                if (snap.hasError) {
                  return StatusViews.error(context, snap.error!, onRetry: _reload);
                }
                final plans = snap.data ?? const <Map<String, dynamic>>[];

                return RefreshIndicator(
                  onRefresh: () async => _reload(),
                  child: ListView(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                    children: [
                      // 1. Balance Hero Card
                      _walletHeroCard(context, balance, currency, isDark, isHi),
                      const SizedBox(height: 20),

                      // 2. Recharge Plans Grid
                      SectionHeader(
                        title: isHi ? 'रिचार्ज दक्षिणा पैक चुनें' : 'Select Recharge Pack',
                        subtitle: isHi
                            ? 'प्रत्येक पैक पर विशेष अतिरिक्त दक्षिणा बोनस उपलब्ध'
                            : 'Extra dakshina bonus added to all packs',
                        icon: Icons.card_giftcard_rounded,
                      ),
                      const SizedBox(height: 8),
                      _plansGrid(
                        context,
                        plans.isNotEmpty ? plans : _defaultCuratedPlans,
                        isDark,
                        isHi,
                      ),
                      const SizedBox(height: 20),

                      // 3. Custom Amount Card
                      _customAmountCard(context, isDark, isHi),
                      const SizedBox(height: 20),

                      // 4. Trust Badges
                      _trustAssuranceCard(context, isDark, isHi),
                      const SizedBox(height: 32),
                    ],
                  ),
                );
              },
            ),
          ),
        );
      },
    );
  }

  Widget _walletHeroCard(
    BuildContext context,
    double balance,
    String currency,
    bool isDark,
    bool isHi,
  ) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: CustomerTheme.heroCosmicGradient,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: CustomerTheme.brandGold.withValues(alpha: 0.35),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.25),
            blurRadius: 14,
            offset: const Offset(0, 5),
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
                  color: CustomerTheme.sacredEmerald.withValues(alpha: 0.2),
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.account_balance_wallet_rounded,
                  color: CustomerTheme.sacredEmerald,
                  size: 20,
                ),
              ),
              const SizedBox(width: 10),
              Text(
                isHi ? 'उपलब्ध दक्षिणा शेष' : 'Available Dakshina Balance',
                style: const TextStyle(
                  color: Colors.white70,
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const Spacer(),
              SacredBadge(
                label: isHi ? '१००% सुरक्षित' : 'SECURE',
                color: CustomerTheme.sacredEmerald,
                fontSize: 8.5,
              ),
            ],
          ),
          const SizedBox(height: 14),
          Text(
            '$currency${balance.toStringAsFixed(2)}',
            style: const TextStyle(
              fontSize: 34,
              fontWeight: FontWeight.w900,
              color: Colors.white,
              letterSpacing: -0.5,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            isHi
                ? 'इस शेष राशि का उपयोग शीर्ष आचार्यों से तुरंत चैट, ऑडियो कॉल, वीडियो परामर्श एवं पावन पूजा अनुष्ठान संकल्प हेतु करें।'
                : 'Use this balance for instant Astrologer chat, audio calls, video consultations, and holy pujas.',
            style: const TextStyle(
              color: Colors.white60,
              fontSize: 12,
              height: 1.35,
            ),
          ),
        ],
      ),
    );
  }

  Widget _plansGrid(
    BuildContext context,
    List<Map<String, dynamic>> plans,
    bool isDark,
    bool isHi,
  ) {
    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 3,
        crossAxisSpacing: 10,
        mainAxisSpacing: 10,
        childAspectRatio: 1.05,
      ),
      itemCount: plans.length,
      itemBuilder: (context, i) {
        final plan = plans[i];
        final amount =
            double.tryParse(plan['amount']?.toString() ?? '') ?? 0;
        final bonus = isHi
            ? (plan['bonusHi']?.toString() ?? plan['bonus']?.toString())
            : plan['bonus']?.toString();

        return SacredCard(
          padding: const EdgeInsets.all(8),
          elevation: 1,
          onTap: () => _recharge(amount, isHi),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                '₹${amount.toStringAsFixed(0)}',
                style: const TextStyle(
                  fontSize: 16.5,
                  fontWeight: FontWeight.w900,
                  color: CustomerTheme.brandSaffron,
                ),
              ),
              if (bonus != null) ...[
                const SizedBox(height: 4),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: CustomerTheme.sacredEmerald.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    bonus,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 9.5,
                      fontWeight: FontWeight.w800,
                      color: CustomerTheme.sacredEmerald,
                    ),
                  ),
                ),
              ],
            ],
          ),
        );
      },
    );
  }

  Widget _customAmountCard(BuildContext context, bool isDark, bool isHi) {
    return SacredCard(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            isHi ? 'अन्य राशि दर्ज करें' : 'Add Custom Amount',
            style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 14),
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _customAmountController,
                  keyboardType: TextInputType.number,
                  decoration: InputDecoration(
                    prefixText: '₹ ',
                    hintText: isHi ? 'राशि दर्ज करें (उदा. २५०)' : 'Enter amount (e.g. 250)',
                    contentPadding:
                        const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              SacredButton(
                text: isHi ? 'रिचार्ज करें' : 'Recharge',
                isFullWidth: false,
                height: 48,
                onPressed: () {
                  final val = double.tryParse(
                          _customAmountController.text.trim()) ??
                      0;
                  if (val > 0) {
                    _recharge(val, isHi);
                  } else {
                    showSnack(
                      context,
                      isHi ? 'कृपया एक वैध राशि दर्ज करें' : 'Please enter a valid amount',
                      error: true,
                    );
                  }
                },
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _trustAssuranceCard(BuildContext context, bool isDark, bool isHi) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDark
            ? CustomerTheme.cosmicCardDark
            : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isDark
              ? CustomerTheme.brandGold.withValues(alpha: 0.15)
              : CustomerTheme.lightBorder,
        ),
      ),
      child: Column(
        children: [
          _trustRow(
            Icons.verified_user_rounded,
            isHi ? '१००% सुरक्षित एवं एन्क्रिप्टेड भुगतान' : '100% Safe & Encrypted Payments',
            isHi ? 'UPI, RuPay, Visa एवं MasterCard द्वारा अधिकृत भुगतान प्रणाली' : 'Authorized payment channels via UPI, Rupay, Visa & Mastercard',
            CustomerTheme.sacredEmerald,
          ),
          const SizedBox(height: 12),
          _trustRow(
            Icons.restart_alt_rounded,
            isHi ? 'अव्ययित शेष राशि की तत्काल वापसी' : 'Instant Unused Balance Refund',
            isHi ? 'परामर्श न जुड़ने की स्थिति में पूर्ण राशि वापसी की गारंटी' : 'Full refund guarantee if consultation fails to connect',
            CustomerTheme.brandGold,
          ),
        ],
      ),
    );
  }

  Widget _trustRow(
    IconData icon,
    String title,
    String subtitle,
    Color iconColor,
  ) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 20, color: iconColor),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style:
                    const TextStyle(fontWeight: FontWeight.w700, fontSize: 12.5),
              ),
              const SizedBox(height: 2),
              Text(
                subtitle,
                style: const TextStyle(fontSize: 11, color: Colors.grey),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
