import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:op_shared/op_shared.dart';
import 'package:url_launcher/url_launcher.dart';

import 'vedic_html_renderer.dart';

/// Detailed reader view for astrology articles & Vedic spiritual insights.
class BlogDetailScreen extends StatelessWidget {
  static const route = '/blog-detail';
  const BlogDetailScreen({super.key, required this.blog});

  final Blog blog;

  bool _isHindi(BuildContext context) {
    if (blog.title != null && RegExp(r'[\u0900-\u097F]').hasMatch(blog.title.toString())) {
      return true;
    }
    final code = Localizations.maybeLocaleOf(context)?.languageCode ?? 'en';
    return code == 'hi';
  }

  String _formatPublicationDate(String? rawDate, bool isHi) {
    if (rawDate == null || rawDate.isEmpty) {
      return isHi ? 'हाल ही में प्रकाशित' : 'Recently published';
    }
    final parsed = DateTime.tryParse(rawDate);
    if (parsed == null) return rawDate;

    if (isHi) {
      const hiMonths = [
        'जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून',
        'जुलाई', 'अगस्त', 'सितम्बर', 'अक्टूबर', 'नवम्बर', 'दिसम्बर'
      ];
      final month = hiMonths[parsed.month - 1];
      return '${parsed.day} $month ${parsed.year}';
    } else {
      return DateFormat('dd MMM yyyy').format(parsed);
    }
  }

  String _calculateReadingTime(String? description, bool isHi) {
    final text = description ?? '';
    final words = text.replaceAll(RegExp(r'<[^>]*>'), ' ').split(RegExp(r'\s+')).where((w) => w.isNotEmpty).length;
    final mins = (words / 150).ceil().clamp(2, 20);
    return isHi ? '📖 $mins मिनट पठन' : '📖 $mins min read';
  }

  void _shareArticle(BuildContext context, bool isHi) async {
    final title = blog.title?.toString() ?? 'Online Puja Article';
    final slug = blog.slug?.toString() ?? '';
    final url = slug.isNotEmpty ? 'https://onlinepuja.live/blog/$slug' : 'https://onlinepuja.live';
    final text = '$title\n\n$url\n\n${isHi ? 'सनातन धर्म, व्रत एवं प्रामाणिक वैदिक पूजा अनुष्ठान' : 'Authentic Vedic Pujas and Astrological Guidance - OnlinePuja.live'}';
    final uri = Uri.parse('whatsapp://send?text=${Uri.encodeComponent(text)}');

    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    } else {
      final webUri = Uri.parse('https://api.whatsapp.com/send?text=${Uri.encodeComponent(text)}');
      await launchUrl(webUri, mode: LaunchMode.externalApplication);
    }
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final isHi = _isHindi(context);

    final imageUrl = blog.image?.toString();
    final author = blog.author?.toString() ?? (isHi ? 'वैदिक शोध एवं संपादकीय मंडल' : 'Online Puja Editorial');
    final dateStr = _formatPublicationDate(blog.createdAt?.toString(), isHi);
    final readTimeStr = _calculateReadingTime(blog.description?.toString(), isHi);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          isHi ? 'वैदिक ज्ञान एवं आलेख' : 'Spiritual Article',
          style: const TextStyle(fontWeight: FontWeight.bold),
        ),
        actions: [
          IconButton(
            tooltip: isHi ? 'शेयर करें' : 'Share Article',
            icon: const Icon(Icons.share_rounded),
            onPressed: () => _shareArticle(context, isHi),
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 40),
        children: [
          // 1. Hero Image
          if (imageUrl != null && imageUrl.isNotEmpty) ...[
            ClipRRect(
              borderRadius: BorderRadius.circular(20),
              child: AspectRatio(
                aspectRatio: 16 / 9,
                child: CachedNetworkImage(
                  imageUrl: MiscApi.imageUrl(imageUrl),
                  fit: BoxFit.cover,
                  placeholder: (context, url) => Container(
                    color: scheme.surfaceContainerHighest,
                    child: const Center(child: CircularProgressIndicator(strokeWidth: 2)),
                  ),
                  errorWidget: (context, url, error) => Container(
                    color: scheme.primaryContainer,
                    child: Icon(Icons.auto_stories_rounded, color: scheme.primary, size: 48),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 18),
          ],

          // 2. Title
          Text(
            blog.title?.toString() ?? (isHi ? 'आध्यात्मिक एवं वैदिक ज्ञान' : 'Astrology Insights'),
            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.w900,
                  height: 1.35,
                  letterSpacing: -0.3,
                ),
          ),
          const SizedBox(height: 14),

          // 3. Author & Publication Meta
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(
              color: isDark ? scheme.surfaceContainer : Colors.amber.shade50.withValues(alpha: 0.6),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(
                color: const Color(0xFFF59E0B).withValues(alpha: 0.25),
              ),
            ),
            child: Row(
              children: [
                CircleAvatar(
                  radius: 18,
                  backgroundColor: const Color(0xFFF59E0B).withValues(alpha: 0.15),
                  child: const Icon(Icons.person, size: 20, color: Color(0xFFD97706)),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        author,
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 2),
                      Row(
                        children: [
                          Text(
                            dateStr,
                            style: TextStyle(
                              color: scheme.onSurface.withValues(alpha: 0.65),
                              fontSize: 11.5,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                          const SizedBox(width: 8),
                          Text('•', style: TextStyle(color: scheme.onSurface.withValues(alpha: 0.4))),
                          const SizedBox(width: 8),
                          Text(
                            readTimeStr,
                            style: const TextStyle(
                              color: Color(0xFFD97706),
                              fontSize: 11.5,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          const Divider(height: 1),
          const SizedBox(height: 8),

          // 4. Pure Devotional Rich HTML Content Parser
          VedicHtmlRenderer(
            rawHtml: blog.description?.toString() ?? '',
            isHindi: isHi,
          ),

          const SizedBox(height: 32),

          // 5. Astrologer Consultation & Puja Sankalp Banner
          Container(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: isDark
                    ? [const Color(0xFF1E293B), const Color(0xFF0F172A)]
                    : [const Color(0xFFFEF3C7), const Color(0xFFFFFBEB)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(20),
              border: Border.all(
                color: const Color(0xFFF59E0B).withValues(alpha: 0.6),
                width: 1.5,
              ),
              boxShadow: [
                BoxShadow(
                  color: const Color(0xFFF59E0B).withValues(alpha: 0.1),
                  blurRadius: 18,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            padding: const EdgeInsets.all(18),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF59E0B).withValues(alpha: 0.2),
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.self_improvement_rounded, color: Color(0xFFD97706), size: 28),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            isHi ? 'ज्योतिषीय समाधान एवं परामर्श' : 'Personal Astrology Consultation',
                            style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15),
                          ),
                          Text(
                            isHi
                                ? 'कुंडली विश्लेषण अथवा ग्रह दोष निवारण हेतु'
                                : 'Talk to verified Vedic Astrologers',
                            style: TextStyle(
                              fontSize: 12,
                              color: scheme.onSurface.withValues(alpha: 0.7),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Text(
                  isHi
                      ? 'क्या आपकी कुंडली में कालसर्प, मांगलिक अथवा शनि की साढ़े साती चल रही है? काशी के प्रतिष्ठित आचार्यों से फोन या चैट पर व्यक्तिगत मार्गदर्शन प्राप्त करें।'
                      : 'Have personal questions about your horoscope, Mahadasha, or planetary transits? Talk to an expert astrologer now.',
                  style: TextStyle(
                    fontSize: 13,
                    height: 1.5,
                    color: scheme.onSurface.withValues(alpha: 0.85),
                  ),
                ),
                const SizedBox(height: 16),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    style: FilledButton.styleFrom(
                      backgroundColor: const Color(0xFFF59E0B),
                      foregroundColor: const Color(0xFF020617),
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    onPressed: () => Navigator.of(context).pop(),
                    icon: const Icon(Icons.support_agent_rounded, size: 20),
                    label: Text(
                      isHi ? 'विद्वान ज्योतिषी से परामर्श करें' : 'Consult Astrologer',
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
