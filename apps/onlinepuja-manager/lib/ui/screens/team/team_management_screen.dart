import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../providers/language_provider.dart';
import '../../../services/manager_api_service.dart';
import '../../../services/manager_auth_session.dart';
import '../../../theme/manager_theme.dart';

class TeamManagementScreen extends StatefulWidget {
  const TeamManagementScreen({super.key});

  @override
  State<TeamManagementScreen> createState() => _TeamManagementScreenState();
}

class _TeamManagementScreenState extends State<TeamManagementScreen> {
  final ManagerApiService _api = ManagerApiService();
  bool _isLoading = true;
  String _searchQuery = '';
  String _statusFilter = 'all'; // all, active, suspended

  List<Map<String, dynamic>> _teamMembers = [];
  List<Map<String, dynamic>> _roles = [];
  List<Map<String, dynamic>> _availableModules = [];

  int _totalCount = 0;
  int _activeCount = 0;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    try {
      final teamRes = await _api.fetchTeamList();
      final rolesRes = await _api.fetchTeamRolesAndModules();

      if (teamRes != null && teamRes['ok'] == true) {
        _totalCount = (teamRes['total'] as num?)?.toInt() ?? 0;
        _activeCount = (teamRes['active'] as num?)?.toInt() ?? 0;
        final list = teamRes['team'];
        if (list is List) {
          _teamMembers = List<Map<String, dynamic>>.from(list);
        }
      }

      if (rolesRes != null && rolesRes['ok'] == true) {
        final rolesList = rolesRes['roles'];
        if (rolesList is List) {
          _roles = List<Map<String, dynamic>>.from(rolesList);
        }
        final modulesList = rolesRes['modules'];
        if (modulesList is List) {
          _availableModules = List<Map<String, dynamic>>.from(modulesList);
        }
      }
    } catch (e) {
      debugPrint('Error loading team data: $e');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _toggleStatus(Map<String, dynamic> member, bool newStatus) async {
    final int id = (member['id'] as num).toInt();
    final oldStatus = member['isActive'] == true;

    // Optimistic update
    setState(() {
      member['isActive'] = newStatus;
      if (newStatus) {
        _activeCount++;
      } else {
        _activeCount = (_activeCount - 1).clamp(0, 9999);
      }
    });

    final res = await _api.toggleTeamMemberStatus(id: id, isActive: newStatus);
    if (!mounted) return;

    if (res['ok'] == true) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(res['message']?.toString() ?? 'Status updated'),
          backgroundColor: newStatus ? const Color(0xFF064E3B) : const Color(0xFF7F1D1D),
          behavior: SnackBarBehavior.floating,
        ),
      );
    } else {
      // Revert
      setState(() {
        member['isActive'] = oldStatus;
        if (oldStatus) {
          _activeCount++;
        } else {
          _activeCount = (_activeCount - 1).clamp(0, 9999);
        }
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(res['error']?.toString() ?? 'Failed to update status'),
          backgroundColor: const Color(0xFF7F1D1D),
          behavior: SnackBarBehavior.floating,
        ),
      );
    }
  }

  Future<void> _confirmDelete(Map<String, dynamic> member) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: const Color(0xFF1E293B),
        title: const Text('Remove Team Member', style: TextStyle(color: Colors.white)),
        content: Text(
          'Are you sure you want to remove "${member['name']}" (${member['email']})? They will no longer be able to log into OnlinePuja Manager.',
          style: const TextStyle(color: Colors.white70),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel', style: TextStyle(color: Colors.white54)),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFEF4444),
              foregroundColor: Colors.white,
            ),
            child: const Text('Remove Access'),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      final int id = (member['id'] as num).toInt();
      final res = await _api.deleteTeamMember(id);
      if (mounted) {
        if (res['ok'] == true) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Team member removed successfully'),
              backgroundColor: Color(0xFF064E3B),
              behavior: SnackBarBehavior.floating,
            ),
          );
          _loadData();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(res['error']?.toString() ?? 'Failed to remove member'),
              backgroundColor: const Color(0xFF7F1D1D),
              behavior: SnackBarBehavior.floating,
            ),
          );
        }
      }
    }
  }

  void _openMemberModal({Map<String, dynamic>? member}) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _MemberFormSheet(
        member: member,
        roles: _roles,
        availableModules: _availableModules,
        onSaved: () {
          Navigator.pop(ctx);
          _loadData();
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final lang = Provider.of<LanguageProvider>(context);
    final session = Provider.of<ManagerAuthSession>(context);

    final filteredMembers = _teamMembers.where((m) {
      final name = (m['name'] ?? '').toString().toLowerCase();
      final email = (m['email'] ?? '').toString().toLowerCase();
      final phone = (m['contactNo'] ?? '').toString().toLowerCase();
      final q = _searchQuery.toLowerCase();

      final matchesSearch = q.isEmpty || name.contains(q) || email.contains(q) || phone.contains(q);
      if (!matchesSearch) return false;

      if (_statusFilter == 'active') return m['isActive'] == true;
      if (_statusFilter == 'suspended') return m['isActive'] != true;
      return true;
    }).toList();

    return Scaffold(
      backgroundColor: const Color(0xFF0B0F19),
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: _loadData,
          color: ManagerTheme.brandGold,
          backgroundColor: const Color(0xFF1E293B),
          child: CustomScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            slivers: [
              // Header Sliver
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(16, 16, 16, 12),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(10),
                            decoration: BoxDecoration(
                              gradient: const LinearGradient(
                                colors: [Color(0xFFEC4899), Color(0xFFBE185D)],
                                begin: Alignment.topLeft,
                                end: Alignment.bottomRight,
                              ),
                              borderRadius: BorderRadius.circular(14),
                              boxShadow: [
                                BoxShadow(
                                  color: const Color(0xFFEC4899).withValues(alpha: 0.35),
                                  blurRadius: 12,
                                  offset: const Offset(0, 4),
                                ),
                              ],
                            ),
                            child: const Icon(Icons.admin_panel_settings_rounded, color: Colors.white, size: 24),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  lang.isHindi ? 'टीम एवं एक्सेस प्रबंधन' : 'Team & Access Control',
                                  style: const TextStyle(
                                    fontSize: 20,
                                    fontWeight: FontWeight.w900,
                                    color: Colors.white,
                                    letterSpacing: 0.3,
                                  ),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  lang.isHindi
                                      ? '1-क्लिक गूगल लॉगिन एवं मॉड्यूल अनुमतियां'
                                      : 'Role-Based Permissions & 1-Click Google Login Audit',
                                  style: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
                                ),
                              ],
                            ),
                          ),
                          ElevatedButton.icon(
                            onPressed: () => _openMemberModal(),
                            icon: const Icon(Icons.person_add_alt_1_rounded, size: 16),
                            label: Text(lang.isHindi ? '+ टीम जोड़ें' : '+ Add Member'),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: ManagerTheme.brandGold,
                              foregroundColor: const Color(0xFF0B0F19),
                              elevation: 2,
                              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                              textStyle: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),

                      // Metrics Row
                      Row(
                        children: [
                          Expanded(
                            child: _buildMetricCard(
                              label: lang.isHindi ? 'कुल टीम' : 'Total Staff',
                              value: '$_totalCount',
                              icon: Icons.people_outline_rounded,
                              color: const Color(0xFF38BDF8),
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: _buildMetricCard(
                              label: lang.isHindi ? 'सक्रिय एक्सेस' : 'Active Members',
                              value: '$_activeCount',
                              icon: Icons.check_circle_outline_rounded,
                              color: const Color(0xFF10B981),
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: _buildMetricCard(
                              label: lang.isHindi ? 'सिस्टम रोल्स' : 'Configured Roles',
                              value: '${_roles.length}',
                              icon: Icons.badge_outlined,
                              color: const Color(0xFFF59E0B),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),

                      // Search & Filter Bar
                      Row(
                        children: [
                          Expanded(
                            child: Container(
                              height: 42,
                              decoration: BoxDecoration(
                                color: const Color(0xFF1E293B),
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: const Color(0xFF334155)),
                              ),
                              child: TextField(
                                onChanged: (v) => setState(() => _searchQuery = v),
                                style: const TextStyle(color: Colors.white, fontSize: 13),
                                decoration: InputDecoration(
                                  hintText: lang.isHindi ? 'नाम, ईमेल या फोन से खोजें...' : 'Search name, email, phone...',
                                  hintStyle: const TextStyle(color: Colors.white38, fontSize: 13),
                                  prefixIcon: const Icon(Icons.search_rounded, color: Colors.white54, size: 18),
                                  border: InputBorder.none,
                                  contentPadding: const EdgeInsets.symmetric(vertical: 10),
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          // Filter toggle
                          Container(
                            height: 42,
                            padding: const EdgeInsets.symmetric(horizontal: 4),
                            decoration: BoxDecoration(
                              color: const Color(0xFF1E293B),
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: const Color(0xFF334155)),
                            ),
                            child: DropdownButtonHideUnderline(
                              child: DropdownButton<String>(
                                value: _statusFilter,
                                dropdownColor: const Color(0xFF1E293B),
                                style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                                items: const [
                                  DropdownMenuItem(value: 'all', child: Text('All Status')),
                                  DropdownMenuItem(value: 'active', child: Text('Active Only')),
                                  DropdownMenuItem(value: 'suspended', child: Text('Suspended')),
                                ],
                                onChanged: (v) => setState(() => _statusFilter = v ?? 'all'),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),

              // Superadmin Google Banner Card
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                  child: Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [
                          Color(0xFF1E293B),
                          Color(0xFF0F172A),
                        ],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.3)),
                    ),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: ManagerTheme.brandGold.withValues(alpha: 0.15),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(Icons.g_mobiledata_rounded, color: ManagerTheme.brandGold, size: 24),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                lang.isHindi ? '1-क्लिक गूगल लॉगिन सक्रिय' : '1-Click Google Sign-In Active',
                                style: const TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.bold),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                lang.isHindi
                                    ? 'जिस टीम सदस्य का ईमेल यहां जोड़ा गया है, वे सीधे "Continue with Google" से लॉग इन कर सकते हैं।'
                                    : 'Staff members added below can instantly log into the PWA via "1-Click Continue with Google". Unlisted emails are strictly rejected.',
                                style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 11),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),

              // Team List or Empty / Loading
              if (_isLoading)
                const SliverFillRemaining(
                  child: Center(
                    child: CircularProgressIndicator(color: ManagerTheme.brandGold),
                  ),
                )
              else if (filteredMembers.isEmpty)
                SliverFillRemaining(
                  child: Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.group_off_rounded, color: Colors.white24, size: 48),
                        const SizedBox(height: 12),
                        Text(
                          lang.isHindi ? 'कोई टीम सदस्य नहीं मिला' : 'No team members found',
                          style: const TextStyle(color: Colors.white54, fontSize: 14),
                        ),
                      ],
                    ),
                  ),
                )
              else
                SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 40),
                  sliver: SliverList(
                    delegate: SliverChildBuilderDelegate(
                      (ctx, i) => _buildTeamCard(filteredMembers[i], lang, session),
                      childCount: filteredMembers.length,
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildMetricCard({
    required String label,
    required String value,
    required IconData icon,
    required Color color,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFF334155)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(label, style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 11, fontWeight: FontWeight.w600)),
              Icon(icon, color: color, size: 16),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: TextStyle(
              color: color,
              fontSize: 18,
              fontWeight: FontWeight.w900,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTeamCard(
    Map<String, dynamic> member,
    LanguageProvider lang,
    ManagerAuthSession session,
  ) {
    final bool isActive = member['isActive'] == true;
    final String name = member['name'] ?? 'Team Member';
    final String email = member['email'] ?? '';
    final String phone = member['contactNo'] ?? '';
    final String roleName = member['roleName'] ?? 'Staff Member';
    final List perms = (member['permissions'] is List) ? member['permissions'] as List : [];

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isActive ? const Color(0xFF334155) : const Color(0xFF7F1D1D).withValues(alpha: 0.6),
          width: 1.2,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.2),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Top Row: Avatar + Name + Active Switch
            Row(
              children: [
                CircleAvatar(
                  radius: 20,
                  backgroundColor: ManagerTheme.brandGold.withValues(alpha: 0.2),
                  child: Text(
                    name.isNotEmpty ? name[0].toUpperCase() : 'U',
                    style: const TextStyle(color: ManagerTheme.brandGold, fontWeight: FontWeight.bold, fontSize: 16),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Flexible(
                            child: Text(
                              name,
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 15,
                                fontWeight: FontWeight.w800,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          const SizedBox(width: 8),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                            decoration: BoxDecoration(
                              color: const Color(0xFF38BDF8).withValues(alpha: 0.15),
                              borderRadius: BorderRadius.circular(6),
                              border: Border.all(color: const Color(0xFF38BDF8).withValues(alpha: 0.4)),
                            ),
                            child: Text(
                              roleName,
                              style: const TextStyle(
                                color: Color(0xFF38BDF8),
                                fontSize: 9.5,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 2),
                      Text(
                        email,
                        style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 12),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      if (phone.isNotEmpty) ...[
                        const SizedBox(height: 2),
                        Row(
                          children: [
                            const Icon(Icons.phone_rounded, color: Color(0xFF25D366), size: 12),
                            const SizedBox(width: 4),
                            Text(phone, style: const TextStyle(color: Colors.white54, fontSize: 11)),
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
                // Active Switch
                Column(
                  children: [
                    Switch(
                      value: isActive,
                      activeThumbColor: const Color(0xFF10B981),
                      activeTrackColor: const Color(0xFF064E3B),
                      inactiveThumbColor: Colors.white38,
                      inactiveTrackColor: const Color(0xFF334155),
                      onChanged: (val) => _toggleStatus(member, val),
                    ),
                    Text(
                      isActive ? 'ACTIVE' : 'SUSPENDED',
                      style: TextStyle(
                        fontSize: 9,
                        fontWeight: FontWeight.w900,
                        color: isActive ? const Color(0xFF10B981) : const Color(0xFFEF4444),
                        letterSpacing: 0.6,
                      ),
                    ),
                  ],
                ),
              ],
            ),

            const SizedBox(height: 12),
            const Divider(color: Color(0xFF334155), height: 1),
            const SizedBox(height: 10),

            // Permission Tags
            const Text(
              'AUTHORIZED MODULES',
              style: TextStyle(
                color: Color(0xFF64748B),
                fontSize: 10,
                fontWeight: FontWeight.w800,
                letterSpacing: 0.6,
              ),
            ),
            const SizedBox(height: 6),
            Wrap(
              spacing: 6,
              runSpacing: 6,
              children: perms.isEmpty
                  ? [
                      const Text('No explicit permissions granted', style: TextStyle(color: Colors.white30, fontSize: 11)),
                    ]
                  : perms.map((p) {
                      final pStr = p.toString();
                      final isStar = pStr == '*';
                      return Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: isStar
                              ? ManagerTheme.brandGold.withValues(alpha: 0.2)
                              : const Color(0xFF0F172A),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(
                            color: isStar
                                ? ManagerTheme.brandGold
                                : const Color(0xFF475569),
                          ),
                        ),
                        child: Text(
                          isStar ? '★ ALL ACCESS' : pStr.toUpperCase(),
                          style: TextStyle(
                            color: isStar ? ManagerTheme.brandGold : const Color(0xFFE2E8F0),
                            fontSize: 10,
                            fontWeight: isStar ? FontWeight.w900 : FontWeight.w600,
                          ),
                        ),
                      );
                    }).toList(),
            ),

            const SizedBox(height: 12),

            // Action Buttons
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                TextButton.icon(
                  onPressed: () => _openMemberModal(member: member),
                  icon: const Icon(Icons.edit_rounded, size: 14, color: Color(0xFF38BDF8)),
                  label: const Text('Edit Permissions', style: TextStyle(color: Color(0xFF38BDF8), fontSize: 12)),
                ),
                const SizedBox(width: 8),
                TextButton.icon(
                  onPressed: () => _confirmDelete(member),
                  icon: const Icon(Icons.delete_outline_rounded, size: 14, color: Color(0xFFEF4444)),
                  label: const Text('Remove', style: TextStyle(color: Color(0xFFEF4444), fontSize: 12)),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

/// Modal Bottom Sheet for Creating or Updating a Team Member
class _MemberFormSheet extends StatefulWidget {
  final Map<String, dynamic>? member;
  final List<Map<String, dynamic>> roles;
  final List<Map<String, dynamic>> availableModules;
  final VoidCallback onSaved;

  const _MemberFormSheet({
    this.member,
    required this.roles,
    required this.availableModules,
    required this.onSaved,
  });

  @override
  State<_MemberFormSheet> createState() => _MemberFormSheetState();
}

class _MemberFormSheetState extends State<_MemberFormSheet> {
  final ManagerApiService _api = ManagerApiService();
  final _nameCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _passwordCtrl = TextEditingController();

  int? _selectedRoleId;
  final Set<String> _selectedPermissions = {};
  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    if (widget.member != null) {
      _nameCtrl.text = widget.member!['name'] ?? '';
      _emailCtrl.text = widget.member!['email'] ?? '';
      _phoneCtrl.text = widget.member!['contactNo'] ?? '';
      _selectedRoleId = (widget.member!['roleId'] as num?)?.toInt();

      final existingPerms = widget.member!['permissions'];
      if (existingPerms is List) {
        _selectedPermissions.addAll(existingPerms.map((e) => e.toString()));
      }
    } else {
      if (widget.roles.isNotEmpty) {
        _selectedRoleId = (widget.roles.first['id'] as num?)?.toInt();
      }
      // Default initial modules
      _selectedPermissions.addAll(['crm', 'astrologers', 'campaigns', 'kundli']);
    }
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _emailCtrl.dispose();
    _phoneCtrl.dispose();
    _passwordCtrl.dispose();
    super.dispose();
  }

  void _togglePermission(String modId) {
    setState(() {
      if (_selectedPermissions.contains(modId)) {
        _selectedPermissions.remove(modId);
      } else {
        _selectedPermissions.add(modId);
      }
    });
  }

  void _selectAllPermissions() {
    setState(() {
      for (final m in widget.availableModules) {
        final id = m['id']?.toString();
        if (id != null) _selectedPermissions.add(id);
      }
    });
  }

  void _clearAllPermissions() {
    setState(() {
      _selectedPermissions.clear();
    });
  }

  Future<void> _handleSave() async {
    final name = _nameCtrl.text.trim();
    final email = _emailCtrl.text.trim();
    final phone = _phoneCtrl.text.trim();
    final password = _passwordCtrl.text;

    if (name.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please enter staff full name'), backgroundColor: Color(0xFF7F1D1D)),
      );
      return;
    }

    if (email.isEmpty || !email.contains('@')) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please enter a valid Google Account email address'), backgroundColor: Color(0xFF7F1D1D)),
      );
      return;
    }

    if (_selectedRoleId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please select a role'), backgroundColor: Color(0xFF7F1D1D)),
      );
      return;
    }

    setState(() => _isSaving = true);

    try {
      final isEdit = widget.member != null;
      Map<String, dynamic> res;

      if (isEdit) {
        final int id = (widget.member!['id'] as num).toInt();
        res = await _api.updateTeamMember(
          id: id,
          name: name,
          email: email,
          contactNo: phone.isNotEmpty ? phone : null,
          password: password.isNotEmpty ? password : null,
          roleId: _selectedRoleId!,
          permissions: _selectedPermissions.toList(),
        );
      } else {
        res = await _api.createTeamMember(
          name: name,
          email: email,
          contactNo: phone.isNotEmpty ? phone : null,
          password: password.isNotEmpty ? password : null,
          roleId: _selectedRoleId!,
          permissions: _selectedPermissions.toList(),
        );
      }

      if (!mounted) return;

      if (res['ok'] == true) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(res['message']?.toString() ?? 'Saved successfully!'),
            backgroundColor: const Color(0xFF064E3B),
            behavior: SnackBarBehavior.floating,
          ),
        );
        widget.onSaved();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(res['error']?.toString() ?? 'Failed to save team member'),
            backgroundColor: const Color(0xFF7F1D1D),
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: const Color(0xFF7F1D1D)),
        );
      }
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isEdit = widget.member != null;

    return Container(
      constraints: BoxConstraints(
        maxHeight: MediaQuery.of(context).size.height * 0.9,
      ),
      padding: EdgeInsets.only(
        top: 20,
        left: 20,
        right: 20,
        bottom: MediaQuery.of(context).viewInsets.bottom + 20,
      ),
      decoration: const BoxDecoration(
        color: Color(0xFF0F172A),
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        border: Border(top: BorderSide(color: Color(0xFF334155), width: 1.5)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Drag handle
          Center(
            child: Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: Colors.white24,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Modal Title
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                isEdit ? 'Edit Team Member & Access' : 'Add New Team Member',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 18,
                  fontWeight: FontWeight.w900,
                ),
              ),
              IconButton(
                icon: const Icon(Icons.close_rounded, color: Colors.white54),
                onPressed: () => Navigator.pop(context),
              ),
            ],
          ),
          const SizedBox(height: 12),

          Expanded(
            child: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Full Name
                  const Text('Full Name *', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 6),
                  TextField(
                    controller: _nameCtrl,
                    style: const TextStyle(color: Colors.white),
                    decoration: InputDecoration(
                      hintText: 'e.g. Ramesh Sharma',
                      hintStyle: const TextStyle(color: Colors.white30),
                      filled: true,
                      fillColor: const Color(0xFF1E293B),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    ),
                  ),
                  const SizedBox(height: 14),

                  // Email (Google Account)
                  const Text('Google Account Email *', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 2),
                  const Text('This email will be permitted to 1-Click login with Google.', style: TextStyle(color: Colors.white38, fontSize: 11)),
                  const SizedBox(height: 6),
                  TextField(
                    controller: _emailCtrl,
                    keyboardType: TextInputType.emailAddress,
                    style: const TextStyle(color: Colors.white),
                    decoration: InputDecoration(
                      hintText: 'staff@gmail.com',
                      hintStyle: const TextStyle(color: Colors.white30),
                      filled: true,
                      fillColor: const Color(0xFF1E293B),
                      prefixIcon: const Icon(Icons.email_outlined, color: ManagerTheme.brandGold, size: 18),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    ),
                  ),
                  const SizedBox(height: 14),

                  // Phone Number
                  const Text('Contact Phone Number (Optional)', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 6),
                  TextField(
                    controller: _phoneCtrl,
                    keyboardType: TextInputType.phone,
                    style: const TextStyle(color: Colors.white),
                    decoration: InputDecoration(
                      hintText: '+91 9876543210',
                      hintStyle: const TextStyle(color: Colors.white30),
                      filled: true,
                      fillColor: const Color(0xFF1E293B),
                      prefixIcon: const Icon(Icons.phone_outlined, color: Color(0xFF25D366), size: 18),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    ),
                  ),
                  const SizedBox(height: 14),

                  // Role Dropdown
                  const Text('System Role *', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 6),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                    decoration: BoxDecoration(
                      color: const Color(0xFF1E293B),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: DropdownButtonHideUnderline(
                      child: DropdownButton<int>(
                        value: _selectedRoleId,
                        dropdownColor: const Color(0xFF1E293B),
                        isExpanded: true,
                        style: const TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w600),
                        items: widget.roles.map((r) {
                          final int id = (r['id'] as num).toInt();
                          final String name = r['name'] ?? 'Role #$id';
                          return DropdownMenuItem<int>(
                            value: id,
                            child: Text(name),
                          );
                        }).toList(),
                        onChanged: (v) => setState(() => _selectedRoleId = v),
                      ),
                    ),
                  ),
                  const SizedBox(height: 14),

                  // Optional Password
                  Text(
                    isEdit ? 'Update Password (Leave blank to keep existing)' : 'Fallback Password (Optional)',
                    style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 12, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 6),
                  TextField(
                    controller: _passwordCtrl,
                    obscureText: true,
                    style: const TextStyle(color: Colors.white),
                    decoration: InputDecoration(
                      hintText: '••••••••',
                      hintStyle: const TextStyle(color: Colors.white30),
                      filled: true,
                      fillColor: const Color(0xFF1E293B),
                      prefixIcon: const Icon(Icons.lock_outline_rounded, color: Colors.white54, size: 18),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    ),
                  ),
                  const SizedBox(height: 20),

                  // Granular Module Permissions
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        'Granular Module Permissions',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 14,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      Row(
                        children: [
                          TextButton(
                            onPressed: _selectAllPermissions,
                            style: TextButton.styleFrom(visualDensity: VisualDensity.compact),
                            child: const Text('Select All', style: TextStyle(color: Color(0xFF38BDF8), fontSize: 11)),
                          ),
                          TextButton(
                            onPressed: _clearAllPermissions,
                            style: TextButton.styleFrom(visualDensity: VisualDensity.compact),
                            child: const Text('Clear All', style: TextStyle(color: Color(0xFFEF4444), fontSize: 11)),
                          ),
                        ],
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),

                  ...widget.availableModules.map((mod) {
                    final String modId = mod['id']?.toString() ?? '';
                    final String modName = mod['name']?.toString() ?? '';
                    final String modDesc = mod['description']?.toString() ?? '';
                    final bool isChecked = _selectedPermissions.contains(modId);

                    return InkWell(
                      onTap: () => _togglePermission(modId),
                      borderRadius: BorderRadius.circular(10),
                      child: Container(
                        margin: const EdgeInsets.only(bottom: 8),
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                        decoration: BoxDecoration(
                          color: isChecked
                              ? const Color(0xFF1E293B)
                              : const Color(0xFF0B132B).withValues(alpha: 0.5),
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(
                            color: isChecked ? ManagerTheme.brandGold : const Color(0xFF334155),
                            width: isChecked ? 1.4 : 1.0,
                          ),
                        ),
                        child: Row(
                          children: [
                            Checkbox(
                              value: isChecked,
                              activeColor: ManagerTheme.brandGold,
                              checkColor: const Color(0xFF0B0F19),
                              onChanged: (_) => _togglePermission(modId),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    modName,
                                    style: TextStyle(
                                      color: isChecked ? Colors.white : Colors.white70,
                                      fontSize: 13,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                  if (modDesc.isNotEmpty) ...[
                                    const SizedBox(height: 2),
                                    Text(
                                      modDesc,
                                      style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 11),
                                    ),
                                  ],
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  }),
                ],
              ),
            ),
          ),

          const SizedBox(height: 16),

          // Submit Button
          ElevatedButton(
            onPressed: _isSaving ? null : _handleSave,
            style: ElevatedButton.styleFrom(
              backgroundColor: ManagerTheme.brandGold,
              foregroundColor: const Color(0xFF0B0F19),
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              textStyle: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800),
            ),
            child: _isSaving
                ? const SizedBox(
                    height: 20,
                    width: 20,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFF0B0F19)),
                  )
                : Text(isEdit ? 'Update Team Member & Permissions' : 'Save & Authorize Google Login'),
          ),
        ],
      ),
    );
  }
}
