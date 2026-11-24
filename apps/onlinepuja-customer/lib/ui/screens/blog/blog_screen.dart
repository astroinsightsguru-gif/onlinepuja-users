import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:op_shared/op_shared.dart';

import 'blog_detail_screen.dart';

/// Astrology blogs, articles, and Vedic knowledge stream.
class BlogScreen extends StatefulWidget {
  const BlogScreen({super.key});

  static const route = '/blogs';

  @override
  State<BlogScreen> createState() => _BlogScreenState();
}

class _BlogScreenState extends State<BlogScreen> {
  late Future<List<Blog>> _future;
  String _search = '';

  @override
  void initState() {
    super.initState();
    _future = MiscApi.instance.blogs();
  }

  void _reload() => setState(() => _future = MiscApi.instance.blogs());

  static String _cleanSnippet(String? rawHtml) {
    if (rawHtml == null || rawHtml.isEmpty) return '';
    return rawHtml
        .replaceAll(RegExp(r'<script[\s\S]*?<\/script>', caseSensitive: false), '')
        .replaceAll(RegExp(r'<style[\s\S]*?<\/style>', caseSensitive: false), '')
        .replaceAll(RegExp(r'<div[^>]*class="op-aeo-direct-answer"[\s\S]*?<\/div>', caseSensitive: false), '')
        .replaceAll(RegExp(r'<div[^>]*class="puja-booking-card-wrapper"[\s\S]*?<\/div>', caseSensitive: false), '')
        .replaceAll(RegExp(r'<[^>]*>'), ' ')
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

  static String _formatDate(String? rawDate, bool isHi) {
    if (rawDate == null || rawDate.isEmpty) return '';
    final parsed = DateTime.tryParse(rawDate);
    if (parsed == null) return '';
    if (isHi) {
      const hiMonths = [
        'जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून',
        'जुलाई', 'अगस्त', 'सितम्बर', 'अक्टूबर', 'नवम्बर', 'दिसम्बर'
      ];
      return '${parsed.day} ${hiMonths[parsed.month - 1]} ${parsed.year}';
    } else {
      return DateFormat('dd MMM yyyy').format(parsed);
    }
  }

  static String _calculateReadTime(String? description, bool isHi) {
    final text = description ?? '';
    final words = text.replaceAll(RegExp(r'<[^>]*>'), ' ').split(RegExp(r'\s+')).where((w) => w.isNotEmpty).length;
    final mins = (words / 150).ceil().clamp(2, 20);
    return isHi ? '$mins मिनट' : '$mins min';
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final isHi = Localizations.maybeLocaleOf(context)?.languageCode == 'hi';

    return Scaffold(
      appBar: AppBar(
        title: Text(
          isHi ? 'सनातन ज्ञान एवं वैदिक आलेख' : 'Astrology & Spiritual Blogs',
          style: const TextStyle(fontWeight: FontWeight.bold),
        ),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
            child: TextField(
              decoration: InputDecoration(
                hintText: isHi
                    ? 'आलेख, व्रत-त्योहार या ज्योतिषीय उपाय खोजें…'
                    : 'Search articles, rituals, horoscopes…',
                prefixIcon: const Icon(Icons.search_rounded),
                contentPadding: const EdgeInsets.symmetric(horizontal: 16),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(16)),
              ),
              onChanged: (v) => setState(() => _search = v.trim().toLowerCase()),
            ),
          ),
          Expanded(
            child: FutureBuilder<List<Blog>>(
              future: _future,
              builder: (ctx, snap) {
                if (snap.connectionState == ConnectionState.waiting) {
                  return const Center(child: CircularProgressIndicator());
                }
                if (snap.hasError) {
                  return StatusViews.error(
                    context,
                    snap.error!,
                    onRetry: _reload,
                  );
                }
                final list = (snap.data ?? []).where((b) {
                  if (_search.isEmpty) return true;
                  final title = (b.title ?? '').toString().toLowerCase();
                  final desc = _cleanSnippet(b.description?.toString()).toLowerCase();
                  return title.contains(_search) || desc.contains(_search);
                }).toList();

                if (list.isEmpty) {
                  return StatusViews.empty(
                    context,
                    message: _search.isNotEmpty
                        ? (isHi ? 'कोई आलेख नहीं मिला' : 'No articles match your search')
                        : (isHi ? 'शीघ्र ही नए वैदिक आलेख उपलब्ध होंगे' : 'Articles will appear here shortly'),
                  );
                }

                return RefreshIndicator(
                  onRefresh: () async => _reload(),
                  child: ListView.separated(
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                    itemCount: list.length,
                    separatorBuilder: (_, index) => const SizedBox(height: 16),
                    itemBuilder: (context, i) {
                      final blog = list[i];
                      final imageUrl = blog.image?.toString();
                      final snippet = _cleanSnippet(blog.description?.toString());
                      final bool blogIsHi = (blog.title != null &&
                              RegExp(r'[\u0900-\u097F]').hasMatch(blog.title.toString())) ||
                          isHi;
                      final dateStr = _formatDate(blog.createdAt?.toString(), blogIsHi);
                      final readTime = _calculateReadTime(blog.description?.toString(), blogIsHi);

                      return Card(
                        clipBehavior: Clip.antiAlias,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
                        elevation: 1.5,
                        child: InkWell(
                          onTap: () {
                            if (blog.id != null) {
                              MiscApi.instance.addBlogReader(blogId: blog.id).catchError((_) {});
                            }
                            Navigator.of(context).push(
                              MaterialPageRoute(builder: (_) => BlogDetailScreen(blog: blog)),
                            );
                          },
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              if (imageUrl != null && imageUrl.isNotEmpty)
                                AspectRatio(
                                  aspectRatio: 16 / 9,
                                  child: CachedNetworkImage(
                                    imageUrl: MiscApi.imageUrl(imageUrl),
                                    fit: BoxFit.cover,
                                    errorWidget: (context, url, error) => Container(
                                      color: scheme.primaryContainer,
                                      child: Icon(Icons.auto_stories_rounded, color: scheme.primary, size: 40),
                                    ),
                                  ),
                                ),
                              Padding(
                                padding: const EdgeInsets.all(16),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      blog.title?.toString() ?? (blogIsHi ? 'वैदिक आलेख' : 'Article'),
                                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 17, height: 1.3),
                                      maxLines: 2,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                    if (snippet.isNotEmpty) ...[
                                      const SizedBox(height: 8),
                                      Text(
                                        snippet,
                                        style: TextStyle(
                                          color: scheme.onSurface.withValues(alpha: 0.75),
                                          fontSize: 13,
                                          height: 1.45,
                                        ),
                                        maxLines: 2,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ],
                                    const SizedBox(height: 12),
                                    Row(
                                      children: [
                                        Icon(Icons.person_outline, size: 14, color: scheme.onSurface.withValues(alpha: 0.5)),
                                        const SizedBox(width: 4),
                                        Expanded(
                                          child: Text(
                                            blog.author?.toString() ?? (blogIsHi ? 'ऑनलाइन पूजा शोध मंडल' : 'Online Puja Astrologer'),
                                            style: TextStyle(fontSize: 12, color: scheme.onSurface.withValues(alpha: 0.6)),
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                        ),
                                        if (dateStr.isNotEmpty) ...[
                                          Container(
                                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                            decoration: BoxDecoration(
                                              color: isDark ? scheme.surfaceContainerHighest : Colors.amber.shade50,
                                              borderRadius: BorderRadius.circular(6),
                                            ),
                                            child: Text(
                                              '📖 $readTime',
                                              style: TextStyle(
                                                fontSize: 11,
                                                fontWeight: FontWeight.w600,
                                                color: isDark ? Colors.amber.shade200 : const Color(0xFFB45309),
                                              ),
                                            ),
                                          ),
                                          const SizedBox(width: 8),
                                        ],
                                        Text(
                                          blogIsHi ? 'पढ़ें →' : 'Read →',
                                          style: TextStyle(
                                            color: scheme.primary,
                                            fontWeight: FontWeight.bold,
                                            fontSize: 13,
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
                      );
                    },
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}
