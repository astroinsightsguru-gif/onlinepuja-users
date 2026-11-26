import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:op_shared/op_shared.dart';
import 'package:url_launcher/url_launcher.dart';

/// Pure Flutter Vedic Blog HTML Parser & Renderer.
/// Converts raw HTML stored in the database into a gorgeous, high-converting
/// devotional reading experience matching Sri Mandir standards.
class VedicHtmlRenderer extends StatelessWidget {
  const VedicHtmlRenderer({
    super.key,
    required this.rawHtml,
    this.isHindi = false,
  });

  final String rawHtml;
  final bool isHindi;

  @override
  Widget build(BuildContext context) {
    if (rawHtml.trim().isEmpty) {
      return const SizedBox.shrink();
    }

    final blocks = _parseHtmlIntoBlocks(rawHtml);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: blocks.map((block) => block.buildWidget(context, isHindi)).toList(),
    );
  }

  static List<_VedicBlock> _parseHtmlIntoBlocks(String html) {
    final List<_VedicBlock> blocks = [];

    // Normalize newlines and spaces
    String content = html.replaceAll('\r\n', '\n').replaceAll('\r', '\n');

    // 1. Extract special DIV containers: AEO Direct Answer and Puja Booking Card
    final divRegex = RegExp(r'<div\s+class="([^"]+)"[^>]*>([\s\S]*?)<\/div>', caseSensitive: false);

    int lastIndex = 0;
    for (final match in divRegex.allMatches(content)) {
      if (match.start > lastIndex) {
        final preceding = content.substring(lastIndex, match.start);
        _parseStandardHtml(preceding, blocks);
      }

      final className = match.group(1) ?? '';
      final innerHtml = match.group(2) ?? '';

      if (className.contains('op-aeo-direct-answer')) {
        blocks.add(_parseAeoBlock(innerHtml));
      } else if (className.contains('puja-booking-card-wrapper')) {
        blocks.add(_parsePujaBookingBlock(innerHtml));
      } else {
        // Fallback for generic div
        _parseStandardHtml(innerHtml, blocks);
      }

      lastIndex = match.end;
    }

    if (lastIndex < content.length) {
      final trailing = content.substring(lastIndex);
      _parseStandardHtml(trailing, blocks);
    }

    return blocks;
  }

  static void _parseStandardHtml(String html, List<_VedicBlock> blocks) {
    // Regex matching block tags: h2, h3, h4, p, ul, ol, img, or stray text
    final blockRegex = RegExp(
      r'<(h2|h3|h4|p|ul|ol|img)(?:\s+[^>]*)?>([\s\S]*?)<\/\1>|<(img)(?:\s+[^>]*)?\/?>',
      caseSensitive: false,
    );

    int lastIndex = 0;
    final matches = blockRegex.allMatches(html).toList();

    for (final match in matches) {
      if (match.start > lastIndex) {
        final unparsed = html.substring(lastIndex, match.start).trim();
        final cleaned = _stripAllHtml(unparsed);
        if (cleaned.isNotEmpty) {
          blocks.add(_ParagraphBlock(cleaned));
        }
      }

      final tag = (match.group(1) ?? match.group(3) ?? '').toLowerCase();
      final body = match.group(2) ?? '';

      switch (tag) {
        case 'h2':
          final text = _stripAllHtml(body);
          if (text.isNotEmpty) blocks.add(_HeadingBlock(text, level: 2));
          break;
        case 'h3':
          final text = _stripAllHtml(body);
          if (text.isNotEmpty) blocks.add(_HeadingBlock(text, level: 3));
          break;
        case 'h4':
          final text = _stripAllHtml(body);
          if (text.isNotEmpty) blocks.add(_HeadingBlock(text, level: 4));
          break;
        case 'p':
          final spans = _parseInlineSpans(body);
          if (spans.isNotEmpty) blocks.add(_RichParagraphBlock(spans));
          break;
        case 'ul':
          final items = _parseListItems(body, ordered: false);
          if (items.isNotEmpty) blocks.add(_ListBlock(items, ordered: false));
          break;
        case 'ol':
          final items = _parseListItems(body, ordered: true);
          if (items.isNotEmpty) blocks.add(_ListBlock(items, ordered: true));
          break;
        case 'img':
          final srcMatch = RegExp(r'src="([^"]+)"', caseSensitive: false).firstMatch(match.group(0) ?? '');
          if (srcMatch != null) {
            blocks.add(_ImageBlock(srcMatch.group(1)!));
          }
          break;
      }

      lastIndex = match.end;
    }

    if (lastIndex < html.length) {
      final trailing = html.substring(lastIndex).trim();
      final cleaned = _stripAllHtml(trailing);
      if (cleaned.isNotEmpty) {
        blocks.add(_ParagraphBlock(cleaned));
      }
    }
  }

  static _AeoBlock _parseAeoBlock(String inner) {
    // Extract title (usually inside <strong>Divine Summary & Key Insights</strong>)
    final titleMatch = RegExp(r'<strong[^>]*>([\s\S]*?)<\/strong>', caseSensitive: false).firstMatch(inner);
    final title = titleMatch != null ? _stripAllHtml(titleMatch.group(1)!) : 'Divine Summary & Key Insights';

    // Extract summary paragraph
    final pMatch = RegExp(r'<p[^>]*>([\s\S]*?)<\/p>', caseSensitive: false).firstMatch(inner);
    final summary = pMatch != null ? _stripAllHtml(pMatch.group(1)!) : '';

    // Extract takeaways
    final List<String> takeaways = [];
    final liMatches = RegExp(r'<li[^>]*>([\s\S]*?)<\/li>', caseSensitive: false).allMatches(inner);
    for (final li in liMatches) {
      final t = _stripAllHtml(li.group(1)!);
      if (t.isNotEmpty) takeaways.add(t);
    }

    return _AeoBlock(title: title, summary: summary, takeaways: takeaways);
  }

  static _PujaBookingBlock _parsePujaBookingBlock(String inner) {
    // Extract badge
    final badgeMatch = RegExp(r'<span[^>]*class="[^"]*"[^>]*>([\s\S]*?)<\/span>', caseSensitive: false).firstMatch(inner);
    String badge = badgeMatch != null ? _stripAllHtml(badgeMatch.group(1)!) : 'Sanctified Vedic Ritual';

    // Extract subtitle
    final subMatch = RegExp(r'<span[^>]*color:\s*#94a3b8[^>]*>([\s\S]*?)<\/span>', caseSensitive: false).firstMatch(inner);
    String subtitle = subMatch != null ? _stripAllHtml(subMatch.group(1)!) : '';

    // Extract title (h3)
    final titleMatch = RegExp(r'<h3[^>]*>([\s\S]*?)<\/h3>', caseSensitive: false).firstMatch(inner);
    String title = titleMatch != null ? _stripAllHtml(titleMatch.group(1)!) : 'Book Sacred Puja';

    // Extract description (p)
    final pMatch = RegExp(r'<p[^>]*>([\s\S]*?)<\/p>', caseSensitive: false).firstMatch(inner);
    String desc = pMatch != null ? _stripAllHtml(pMatch.group(1)!) : '';

    // Extract price
    final priceMatch = RegExp(r'(₹\s*\d+)', caseSensitive: false).firstMatch(inner);
    String price = priceMatch != null ? priceMatch.group(1)! : '₹501';

    // Extract URL
    final urlMatch = RegExp(r'<a\s+href="([^"]+)"', caseSensitive: false).firstMatch(inner);
    String url = urlMatch != null ? urlMatch.group(1)! : '';

    return _PujaBookingBlock(
      badge: badge,
      subtitle: subtitle,
      title: title,
      description: desc,
      price: price,
      url: url,
    );
  }

  static List<_InlineSpanData> _parseInlineSpans(String text) {
    final List<_InlineSpanData> spans = [];
    final tagRegex = RegExp(r'<(strong|b|em|i|a)(?:\s+[^>]*)?>([\s\S]*?)<\/\1>', caseSensitive: false);

    int lastIndex = 0;
    for (final m in tagRegex.allMatches(text)) {
      if (m.start > lastIndex) {
        final plain = _stripAllHtml(text.substring(lastIndex, m.start));
        if (plain.isNotEmpty) {
          spans.add(_InlineSpanData(text: plain));
        }
      }

      final tag = m.group(1)!.toLowerCase();
      final body = _stripAllHtml(m.group(2)!);
      String? href;

      if (tag == 'a') {
        final hrefMatch = RegExp(r'href="([^"]+)"', caseSensitive: false).firstMatch(m.group(0)!);
        href = hrefMatch?.group(1);
      }

      spans.add(_InlineSpanData(
        text: body,
        isBold: tag == 'strong' || tag == 'b',
        isItalic: tag == 'em' || tag == 'i',
        url: href,
      ));

      lastIndex = m.end;
    }

    if (lastIndex < text.length) {
      final plain = _stripAllHtml(text.substring(lastIndex));
      if (plain.isNotEmpty) {
        spans.add(_InlineSpanData(text: plain));
      }
    }

    return spans;
  }

  static List<_ListItemData> _parseListItems(String listBody, {required bool ordered}) {
    final List<_ListItemData> items = [];
    final liRegex = RegExp(r'<li[^>]*>([\s\S]*?)<\/li>', caseSensitive: false);

    int index = 1;
    for (final m in liRegex.allMatches(listBody)) {
      final rawLi = m.group(1)!;

      // Check if item has bold title: <strong>Title:</strong> Rest
      final boldMatch = RegExp(r'<(?:strong|b)[^>]*>([\s\S]*?)<\/(?:strong|b)>', caseSensitive: false).firstMatch(rawLi);
      if (boldMatch != null) {
        final boldText = _stripAllHtml(boldMatch.group(1)!);
        final restText = _stripAllHtml(rawLi.substring(boldMatch.end)).replaceFirst(RegExp(r'^:\s*'), '');
        items.add(_ListItemData(
          prefix: ordered ? '$index.' : null,
          title: boldText,
          content: restText,
        ));
      } else {
        final clean = _stripAllHtml(rawLi);
        if (clean.isNotEmpty) {
          items.add(_ListItemData(
            prefix: ordered ? '$index.' : null,
            content: clean,
          ));
        }
      }
      index++;
    }

    return items;
  }

  static String _stripAllHtml(String text) {
    return text
        .replaceAll(RegExp(r'<script[\s\S]*?<\/script>', caseSensitive: false), '')
        .replaceAll(RegExp(r'<style[\s\S]*?<\/style>', caseSensitive: false), '')
        .replaceAll(RegExp(r'<[^>]*>'), '')
        .replaceAll('&amp;', '&')
        .replaceAll('&quot;', '"')
        .replaceAll('&#39;', "'")
        .replaceAll('&apos;', "'")
        .replaceAll('&nbsp;', ' ')
        .replaceAll('&lt;', '<')
        .replaceAll('&gt;', '>')
        .replaceAll(RegExp(r'\s+'), ' ')
        .trim();
  }
}

// ----------------- Internal Block Classes -----------------

abstract class _VedicBlock {
  Widget buildWidget(BuildContext context, bool isHindi);
}

class _HeadingBlock extends _VedicBlock {
  _HeadingBlock(this.text, {required this.level});
  final String text;
  final int level;

  @override
  Widget buildWidget(BuildContext context, bool isHindi) {
    final isH2 = level == 2;
    return Padding(
      padding: EdgeInsets.only(top: isH2 ? 26 : 18, bottom: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Container(
            width: isH2 ? 4.5 : 3.5,
            height: isH2 ? 24 : 18,
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFFF59E0B), Color(0xFFEA580C)],
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
              ),
              borderRadius: BorderRadius.circular(4),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              text,
              style: TextStyle(
                fontSize: isH2 ? 20 : 17,
                fontWeight: isH2 ? FontWeight.w800 : FontWeight.w700,
                letterSpacing: -0.2,
                height: 1.35,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ParagraphBlock extends _VedicBlock {
  _ParagraphBlock(this.text);
  final String text;

  @override
  Widget buildWidget(BuildContext context, bool isHindi) {
    final scheme = Theme.of(context).colorScheme;
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Text(
        text,
        style: TextStyle(
          fontSize: 15.5,
          height: 1.65,
          color: scheme.onSurface.withValues(alpha: 0.9),
        ),
      ),
    );
  }
}

class _RichParagraphBlock extends _VedicBlock {
  _RichParagraphBlock(this.spans);
  final List<_InlineSpanData> spans;

  @override
  Widget buildWidget(BuildContext context, bool isHindi) {
    final scheme = Theme.of(context).colorScheme;
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Text.rich(
        TextSpan(
          children: spans.map((s) {
            if (s.url != null && s.url!.isNotEmpty) {
              return TextSpan(
                text: s.text,
                style: TextStyle(
                  color: isDark ? const Color(0xFF60A5FA) : const Color(0xFF2563EB),
                  fontWeight: FontWeight.w600,
                  decoration: TextDecoration.underline,
                ),
                recognizer: TapGestureRecognizer()
                  ..onTap = () async {
                    final uri = Uri.tryParse(s.url!);
                    if (uri != null) {
                      await launchUrl(uri, mode: LaunchMode.externalApplication);
                    }
                  },
              );
            }
            return TextSpan(
              text: s.text,
              style: TextStyle(
                fontWeight: s.isBold ? FontWeight.bold : FontWeight.normal,
                fontStyle: s.isItalic ? FontStyle.italic : FontStyle.normal,
                color: scheme.onSurface.withValues(alpha: s.isBold ? 0.95 : 0.88),
              ),
            );
          }).toList(),
        ),
        style: const TextStyle(fontSize: 15.5, height: 1.65),
      ),
    );
  }
}

class _InlineSpanData {
  _InlineSpanData({
    required this.text,
    this.isBold = false,
    this.isItalic = false,
    this.url,
  });

  final String text;
  final bool isBold;
  final bool isItalic;
  final String? url;
}

class _ListBlock extends _VedicBlock {
  _ListBlock(this.items, {required this.ordered});
  final List<_ListItemData> items;
  final bool ordered;

  @override
  Widget buildWidget(BuildContext context, bool isHindi) {
    final scheme = Theme.of(context).colorScheme;
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: items.map((item) {
          return Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (ordered && item.prefix != null)
                  Container(
                    width: 24,
                    padding: const EdgeInsets.only(top: 2),
                    child: Text(
                      item.prefix!,
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        color: Color(0xFFF59E0B),
                        fontSize: 14,
                      ),
                    ),
                  )
                else
                  Padding(
                    padding: const EdgeInsets.only(top: 6, right: 10),
                    child: Container(
                      width: 6,
                      height: 6,
                      decoration: const BoxDecoration(
                        color: Color(0xFFF59E0B),
                        shape: BoxShape.circle,
                      ),
                    ),
                  ),
                Expanded(
                  child: Text.rich(
                    TextSpan(
                      children: [
                        if (item.title != null) ...[
                          TextSpan(
                            text: '${item.title}: ',
                            style: TextStyle(
                              fontWeight: FontWeight.bold,
                              color: isDark ? Colors.amber.shade200 : const Color(0xFF92400E),
                              fontSize: 15,
                            ),
                          ),
                        ],
                        TextSpan(
                          text: item.content,
                          style: TextStyle(
                            fontSize: 15,
                            height: 1.5,
                            color: scheme.onSurface.withValues(alpha: 0.9),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          );
        }).toList(),
      ),
    );
  }
}

class _ListItemData {
  _ListItemData({
    this.prefix,
    this.title,
    required this.content,
  });

  final String? prefix;
  final String? title;
  final String content;
}

class _ImageBlock extends _VedicBlock {
  _ImageBlock(this.imageUrl);
  final String imageUrl;

  @override
  Widget buildWidget(BuildContext context, bool isHindi) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 16),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(16),
        child: CachedNetworkImage(
          imageUrl: MiscApi.imageUrl(imageUrl),
          fit: BoxFit.cover,
          errorWidget: (context, url, error) => const SizedBox.shrink(),
        ),
      ),
    );
  }
}

class _AeoBlock extends _VedicBlock {
  _AeoBlock({
    required this.title,
    required this.summary,
    required this.takeaways,
  });

  final String title;
  final String summary;
  final List<String> takeaways;

  @override
  Widget buildWidget(BuildContext context, bool isHindi) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Container(
      margin: const EdgeInsets.symmetric(vertical: 20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: isDark
              ? [const Color(0xFF261D0C), const Color(0xFF1C160B)]
              : [const Color(0xFFFFFBEB), const Color(0xFFFEF3C7)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: const Color(0xFFF59E0B).withValues(alpha: isDark ? 0.5 : 0.7),
          width: 1.5,
        ),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFFF59E0B).withValues(alpha: 0.08),
            blurRadius: 16,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(16),
        child: IntrinsicHeight(
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Container(
                width: 5,
                color: const Color(0xFFF59E0B),
              ),
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          const Text('🕉️', style: TextStyle(fontSize: 18)),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              isHindi ? 'दिव्य सारांश एवं मुख्य सूत्र' : title,
                              style: TextStyle(
                                color: isDark ? const Color(0xFFFCD34D) : const Color(0xFF92400E),
                                fontWeight: FontWeight.w800,
                                fontSize: 13.5,
                                letterSpacing: 0.5,
                              ),
                            ),
                          ),
                        ],
                      ),
                      if (summary.isNotEmpty) ...[
                        const SizedBox(height: 10),
                        Text(
                          summary,
                          style: TextStyle(
                            fontSize: 14.5,
                            height: 1.55,
                            fontWeight: FontWeight.w500,
                            color: isDark ? const Color(0xFFF1F5F9) : const Color(0xFF1E293B),
                          ),
                        ),
                      ],
                      if (takeaways.isNotEmpty) ...[
                        const SizedBox(height: 12),
                        ...takeaways.map((point) => Padding(
                              padding: const EdgeInsets.only(bottom: 6),
                              child: Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Padding(
                                    padding: EdgeInsets.only(top: 2, right: 8),
                                    child: Icon(
                                      Icons.check_circle_rounded,
                                      size: 15,
                                      color: Color(0xFFF59E0B),
                                    ),
                                  ),
                                  Expanded(
                                    child: Text(
                                      point,
                                      style: TextStyle(
                                        fontSize: 13.5,
                                        height: 1.45,
                                        color: isDark ? const Color(0xFFCBD5E1) : const Color(0xFF475569),
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            )),
                      ],
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _PujaBookingBlock extends _VedicBlock {
  _PujaBookingBlock({
    required this.badge,
    required this.subtitle,
    required this.title,
    required this.description,
    required this.price,
    required this.url,
  });

  final String badge;
  final String subtitle;
  final String title;
  final String description;
  final String price;
  final String url;

  @override
  Widget buildWidget(BuildContext context, bool isHindi) {
    return Container(
      margin: const EdgeInsets.symmetric(vertical: 24),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF090D16), Color(0xFF0F172A)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFF59E0B), width: 1.8),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFFF59E0B).withValues(alpha: 0.18),
            blurRadius: 20,
            offset: const Offset(0, 6),
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
                padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 3.5),
                decoration: BoxDecoration(
                  color: const Color(0xFFF59E0B),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  isHindi ? 'सिद्ध वैदिक अनुष्ठान' : badge,
                  style: const TextStyle(
                    fontSize: 10.5,
                    fontWeight: FontWeight.w900,
                    color: Color(0xFF020617),
                    letterSpacing: 0.4,
                  ),
                ),
              ),
              const SizedBox(width: 8),
              if (subtitle.isNotEmpty)
                Expanded(
                  child: Text(
                    subtitle,
                    style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 11, fontWeight: FontWeight.w600),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            title,
            style: const TextStyle(
              fontSize: 17,
              fontWeight: FontWeight.w800,
              color: Color(0xFFFBBF24),
              height: 1.35,
            ),
          ),
          if (description.isNotEmpty) ...[
            const SizedBox(height: 8),
            Text(
              description,
              style: const TextStyle(
                fontSize: 13,
                color: Color(0xFFCBD5E1),
                height: 1.45,
              ),
            ),
          ],
          const SizedBox(height: 16),
          const Divider(color: Color(0xFF1E293B), height: 1),
          const SizedBox(height: 14),
          Row(
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    price,
                    style: const TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.w900,
                      color: Color(0xFFFBBF24),
                      fontFamily: 'monospace',
                    ),
                  ),
                  Text(
                    isHindi ? 'दक्षिणा एवं निःशुल्क प्रसाद' : 'Dakshina + Free Prasad Delivery',
                    style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8)),
                  ),
                ],
              ),
              const Spacer(),
              FilledButton(
                style: FilledButton.styleFrom(
                  backgroundColor: const Color(0xFFF59E0B),
                  foregroundColor: const Color(0xFF020617),
                  padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 12),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  elevation: 4,
                ),
                onPressed: () async {
                  if (url.isNotEmpty) {
                    final uri = Uri.tryParse(url);
                    if (uri != null) {
                      await launchUrl(uri, mode: LaunchMode.externalApplication);
                    }
                  } else {
                    Navigator.of(context).pop();
                  }
                },
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      isHindi ? 'संकल्प लें' : 'Book Sacred Puja',
                      style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 13.5),
                    ),
                    const SizedBox(width: 6),
                    const Icon(Icons.arrow_forward_rounded, size: 16),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
