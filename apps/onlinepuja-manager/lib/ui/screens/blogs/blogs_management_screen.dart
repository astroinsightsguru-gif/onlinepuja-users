import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:onlinepuja_manager/services/manager_api_service.dart';
import 'package:onlinepuja_manager/theme/manager_theme.dart';
import 'package:url_launcher/url_launcher.dart';

class BlogsManagementScreen extends StatefulWidget {
  const BlogsManagementScreen({super.key});

  @override
  State<BlogsManagementScreen> createState() => _BlogsManagementScreenState();
}

class _BlogsManagementScreenState extends State<BlogsManagementScreen> {
  final ManagerApiService _api = ManagerApiService();

  bool _isLoading = true;
  List<Map<String, dynamic>> _blogs = [];
  String _searchQuery = '';
  bool _isGeneratingAi = false;

  @override
  void initState() {
    super.initState();
    _loadBlogs();
  }

  Future<void> _loadBlogs() async {
    setState(() => _isLoading = true);
    final list = await _api.fetchBlogs();
    if (mounted) {
      setState(() {
        _blogs = list;
        _isLoading = false;
      });
    }
  }

  Future<void> _toggleBlogStatus(int id) async {
    HapticFeedback.lightImpact();
    final realIndex = _blogs.indexWhere((b) => b['id'] == id);
    if (realIndex == -1) return;

    final current = _blogs[realIndex]['is_active'] as bool? ?? true;
    setState(() {
      _blogs[realIndex]['is_active'] = !current;
    });

    final ok = await _api.toggleBlog(id);
    if (!ok && mounted) {
      final revertIndex = _blogs.indexWhere((b) => b['id'] == id);
      if (revertIndex != -1) {
        setState(() {
          _blogs[revertIndex]['is_active'] = current;
        });
      }
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Failed to update blog status on server.')),
      );
    }
  }

  Future<void> _draftAiBlog() async {
    setState(() => _isGeneratingAi = true);
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('🤖 AI SEO Blog Agent launched! Researching keywords & drafting 1,800+ words...'),
        backgroundColor: ManagerTheme.brandAmber,
      ),
    );

    final res = await _api.triggerGrowthAction('generate_content');

    if (mounted) {
      setState(() => _isGeneratingAi = false);
      _loadBlogs();
      showDialog(
        context: context,
        builder: (ctx) => AlertDialog(
          backgroundColor: ManagerTheme.surfaceDark,
          title: const Row(
            children: [
              Icon(Icons.check_circle_rounded, color: ManagerTheme.brandEmerald, size: 22),
              SizedBox(width: 8),
              Text('Blog Drafted Successfully', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            ],
          ),
          content: Text(res, style: const TextStyle(fontSize: 12, color: Colors.white70)),
          actions: [
            ElevatedButton(
              style: ElevatedButton.styleFrom(backgroundColor: ManagerTheme.brandGold, foregroundColor: Colors.black),
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Great!'),
            ),
          ],
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final filtered = _blogs.where((b) {
      final t = (b['title'] ?? '').toString().toLowerCase();
      final a = (b['author'] ?? '').toString().toLowerCase();
      return t.contains(_searchQuery.toLowerCase()) || a.contains(_searchQuery.toLowerCase());
    }).toList();

    final totalViews = _blogs.fold<int>(0, (acc, b) => acc + ((b['views'] as num?)?.toInt() ?? 0));

    return Scaffold(
      appBar: AppBar(
        title: const Text('Blogs & SEO Publishing Hub'),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            icon: const Icon(Icons.refresh_rounded),
            onPressed: _loadBlogs,
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: ManagerTheme.brandGold,
        foregroundColor: Colors.black,
        icon: _isGeneratingAi
            ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.black))
            : const Icon(Icons.auto_stories_rounded),
        label: Text(
          _isGeneratingAi ? 'Drafting...' : 'AI Draft New Blog',
          style: const TextStyle(fontWeight: FontWeight.bold),
        ),
        onPressed: _isGeneratingAi ? null : _draftAiBlog,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: ManagerTheme.brandGold))
          : RefreshIndicator(
              onRefresh: _loadBlogs,
              color: ManagerTheme.brandGold,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Overview Metric Cards
                    Row(
                      children: [
                        Expanded(
                          child: Container(
                            padding: const EdgeInsets.all(14),
                            decoration: BoxDecoration(
                              color: ManagerTheme.surfaceDark,
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(color: Colors.white12),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('${_blogs.length}', style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w900, color: Colors.white)),
                                const SizedBox(height: 2),
                                const Text('Total Articles', style: TextStyle(fontSize: 11, color: Colors.white60)),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Container(
                            padding: const EdgeInsets.all(14),
                            decoration: BoxDecoration(
                              color: ManagerTheme.surfaceDark,
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(color: Colors.white12),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('$totalViews', style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w900, color: ManagerTheme.brandGold)),
                                const SizedBox(height: 2),
                                const Text('Devotee Reads', style: TextStyle(fontSize: 11, color: Colors.white60)),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),

                    // Search Field
                    TextField(
                      decoration: InputDecoration(
                        hintText: 'Search blogs by title or author...',
                        prefixIcon: const Icon(Icons.search_rounded, color: Colors.white60),
                        filled: true,
                        fillColor: ManagerTheme.surfaceDark,
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Colors.white12)),
                        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Colors.white12)),
                      ),
                      onChanged: (val) => setState(() => _searchQuery = val),
                    ),
                    const SizedBox(height: 20),

                    // Blog List
                    if (filtered.isEmpty)
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.symmetric(vertical: 24, horizontal: 16),
                        decoration: BoxDecoration(
                          color: ManagerTheme.surfaceDark,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: Colors.white12),
                        ),
                        child: Center(
                          child: Text(
                            _searchQuery.isNotEmpty ? 'No blogs matching "$_searchQuery".' : 'No devotional articles published yet. Tap "Draft AI Blog" to generate one.',
                            textAlign: TextAlign.center,
                            style: const TextStyle(fontSize: 12, color: Colors.white60),
                          ),
                        ),
                      )
                    else
                      ListView.separated(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        itemCount: filtered.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 12),
                        itemBuilder: (context, i) {
                        final b = filtered[i];
                        final id = (b['id'] as num?)?.toInt() ?? 0;
                        final title = b['title']?.toString() ?? 'Devotional Article';
                        final slug = b['slug']?.toString() ?? '';
                        final author = b['author']?.toString() ?? 'Vedic Desk';
                        final views = b['views'] ?? 0;
                        final date = b['created_at']?.toString() ?? 'Recent';
                        final isActive = b['is_active'] == true;
                        final imageUrl = b['image_url']?.toString() ?? b['image']?.toString();

                        return Container(
                          decoration: BoxDecoration(
                            color: ManagerTheme.surfaceDark,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: isActive ? Colors.white12 : Colors.redAccent.withValues(alpha: 0.3)),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withValues(alpha: 0.2),
                                blurRadius: 8,
                                offset: const Offset(0, 3),
                              ),
                            ],
                          ),
                          clipBehavior: Clip.antiAlias,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              // 1. Cover Image Banner
                              if (imageUrl != null && imageUrl.isNotEmpty)
                                Stack(
                                  children: [
                                    AspectRatio(
                                      aspectRatio: 16 / 9,
                                      child: Image.network(
                                        imageUrl,
                                        fit: BoxFit.cover,
                                        loadingBuilder: (_, child, progress) => progress == null
                                            ? child
                                            : Container(
                                                color: Colors.black26,
                                                child: const Center(
                                                  child: CircularProgressIndicator(strokeWidth: 2, color: ManagerTheme.brandGold),
                                                ),
                                              ),
                                        errorBuilder: (_, __, ___) => Container(
                                          color: Colors.black26,
                                          child: const Center(
                                            child: Icon(Icons.temple_hindu_rounded, size: 36, color: Colors.white24),
                                          ),
                                        ),
                                      ),
                                    ),
                                    Positioned(
                                      top: 8,
                                      right: 8,
                                      child: Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                        decoration: BoxDecoration(
                                          color: (isActive ? ManagerTheme.brandEmerald : Colors.redAccent).withValues(alpha: 0.85),
                                          borderRadius: BorderRadius.circular(6),
                                        ),
                                        child: Text(
                                          isActive ? 'LIVE & INDEXED' : 'DRAFT',
                                          style: const TextStyle(color: Colors.white, fontSize: 9.5, fontWeight: FontWeight.w800),
                                        ),
                                      ),
                                    ),
                                  ],
                                ),

                              Padding(
                                padding: const EdgeInsets.all(14),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Expanded(
                                          child: Text(
                                            title,
                                            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: Colors.white, height: 1.3),
                                          ),
                                        ),
                                        const SizedBox(width: 8),
                                        Switch(
                                          value: isActive,
                                          activeThumbColor: ManagerTheme.brandGold,
                                          onChanged: (val) => _toggleBlogStatus(id),
                                        ),
                                      ],
                                    ),
                                    const SizedBox(height: 8),
                                    Row(
                                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                      children: [
                                        Text(
                                          'By $author · $date',
                                          style: const TextStyle(fontSize: 11, color: Colors.white60),
                                        ),
                                        Row(
                                          children: [
                                            const Icon(Icons.remove_red_eye_rounded, size: 14, color: ManagerTheme.brandGold),
                                            const SizedBox(width: 4),
                                            Text('$views reads', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.white70)),
                                          ],
                                        ),
                                      ],
                                    ),
                                    const SizedBox(height: 10),
                                    const Divider(color: Colors.white10, height: 1),
                                    const SizedBox(height: 8),
                                    Row(
                                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                      children: [
                                        if (slug.isNotEmpty)
                                          TextButton.icon(
                                            style: TextButton.styleFrom(
                                              visualDensity: VisualDensity.compact,
                                              padding: EdgeInsets.zero,
                                              foregroundColor: const Color(0xFF38BDF8),
                                            ),
                                            icon: const Icon(Icons.open_in_new_rounded, size: 13),
                                            label: const Text('View Live Article', style: TextStyle(fontSize: 11)),
                                            onPressed: () async {
                                              final uri = Uri.parse('https://onlinepuja.live/blog/$slug');
                                              if (await canLaunchUrl(uri)) launchUrl(uri);
                                            },
                                          )
                                        else
                                          const SizedBox.shrink(),
                                        IconButton(
                                          icon: const Icon(Icons.copy_rounded, size: 15, color: Colors.white60),
                                          tooltip: 'Copy Article URL',
                                          padding: EdgeInsets.zero,
                                          constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
                                          onPressed: () {
                                            Clipboard.setData(ClipboardData(text: 'https://onlinepuja.live/blog/$slug'));
                                            ScaffoldMessenger.of(context).showSnackBar(
                                              const SnackBar(content: Text('✓ Article URL copied to clipboard!'), duration: Duration(seconds: 2)),
                                            );
                                          },
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        );
                      },
                    ),
                    const SizedBox(height: 60),
                  ],
                ),
              ),
            ),
    );
  }
}
