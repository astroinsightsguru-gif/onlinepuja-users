import 'package:flutter/material.dart';
import 'package:op_shared/op_shared.dart';
import 'package:provider/provider.dart';

import '../../../state/app_session.dart';
import 'kundli_detail_screen.dart';

/// Saved kundli list + add-new form (legacy `kundliScreen.dart` /
/// `createNewKundli.dart`).
class KundliListScreen extends StatefulWidget {
  const KundliListScreen({super.key});

  static const route = '/kundli';

  @override
  State<KundliListScreen> createState() => _KundliListScreenState();
}

class _KundliListScreenState extends State<KundliListScreen> {
  List<Kundli>? _items;
  Object? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  int get _userId => context.read<AppSession>().user?.id ?? 0;

  Future<void> _load() async {
    setState(() => _error = null);
    try {
      final effectiveId = _userId > 0 ? _userId : 1;
      final items = await KundliApi.instance.list(userId: effectiveId);
      if (mounted) setState(() => _items = items);
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  Future<void> _openAdd() async {
    final created = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (_) => const _AddKundliSheet(),
    );
    if (created == true && mounted) {
      showSnack(context, 'Kundli saved');
      _load();
    }
  }

  Future<void> _delete(Kundli k) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('Delete ${k.name}?'),
        content:
            const Text('This saved birth chart will be removed permanently.'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Cancel')),
          FilledButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: const Text('Delete')),
        ],
      ),
    );
    if (ok != true) return;
    try {
      await KundliApi.instance.delete(kundaliId: k.id ?? 0);
      if (mounted) {
        showSnack(context, 'Deleted');
        _load();
      }
    } on ApiException catch (e) {
      if (mounted) showSnack(context, e.message, error: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Scaffold(
      appBar: AppBar(title: const Text('Kundli (Janam Patrika)')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _openAdd,
        icon: const Icon(Icons.add_rounded),
        label: const Text('New Kundli'),
      ),
      body: _error != null
          ? StatusViews.error(context, _error!, onRetry: _load)
          : _items == null
              ? StatusViews.loading(context)
              : _items!.isEmpty
                  ? Center(
                      child: Padding(
                        padding: const EdgeInsets.all(24),
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(Icons.auto_graph_rounded, size: 64, color: Color(0xFFD97706)),
                            const SizedBox(height: 16),
                            Text(
                              'Free Vedic Kundli Generator',
                              style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                            ),
                            const SizedBox(height: 8),
                            const Text(
                              'Generate accurate Janam Patrika with planetary positions, Vimshottari dasha, and dosha remedies in seconds.',
                              textAlign: TextAlign.center,
                              style: TextStyle(color: Colors.black54),
                            ),
                            const SizedBox(height: 20),
                            FilledButton.icon(
                              onPressed: _openAdd,
                              icon: const Icon(Icons.add_circle_outline),
                              label: const Text('Generate Kundli Now'),
                            ),
                          ],
                        ),
                      ),
                    )
                  : RefreshIndicator(
                          onRefresh: _load,
                          child: ListView.separated(
                            padding: const EdgeInsets.all(16),
                            itemCount: _items!.length,
                            separatorBuilder: (_, _) =>
                                const SizedBox(height: 10),
                            itemBuilder: (context, i) {
                              final k = _items![i];
                              final isPrimary = i == 0;
                              return Card(
                                margin: EdgeInsets.zero,
                                elevation: isPrimary ? 3 : 1,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(16),
                                  side: BorderSide(
                                    color: isPrimary ? const Color(0xFFF59E0B) : Colors.transparent,
                                    width: isPrimary ? 1.5 : 0,
                                  ),
                                ),
                                child: Column(
                                  children: [
                                    if (isPrimary)
                                      Container(
                                        width: double.infinity,
                                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
                                        decoration: const BoxDecoration(
                                          gradient: LinearGradient(
                                            colors: [Color(0xFFFEF3C7), Color(0xFFFDE68A)],
                                          ),
                                          borderRadius: BorderRadius.vertical(top: Radius.circular(14)),
                                        ),
                                        child: const Row(
                                          children: [
                                            Text('👑', style: TextStyle(fontSize: 12)),
                                            SizedBox(width: 6),
                                            Text(
                                              'आपकी मुख्य जन्म पत्रिका (Primary Janam Patrika)',
                                              style: TextStyle(
                                                fontSize: 10.5,
                                                fontWeight: FontWeight.w900,
                                                color: Color(0xFF92400E),
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ListTile(
                                      leading: CircleAvatar(
                                        backgroundColor: isPrimary ? const Color(0xFFEA580C) : scheme.primaryContainer,
                                        child: Text(
                                          k.name.isNotEmpty
                                              ? k.name[0].toUpperCase()
                                              : '?',
                                          style: TextStyle(
                                              color: isPrimary ? Colors.white : scheme.primary,
                                              fontWeight: FontWeight.w800),
                                        ),
                                      ),
                                      title: Text(
                                        k.name,
                                        style: TextStyle(
                                          fontWeight: isPrimary ? FontWeight.w900 : FontWeight.w600,
                                        ),
                                      ),
                                      subtitle: Text(
                                        '${k.birthDate.toIso8601String().substring(0, 10)} · '
                                        '${k.birthTime} · ${k.birthPlace}',
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                      trailing: IconButton(
                                        icon: const Icon(Icons.delete_outline),
                                        onPressed: () => _delete(k),
                                      ),
                                      onTap: () => Navigator.of(context).push(
                                        MaterialPageRoute(
                                          builder: (_) =>
                                              KundliDetailScreen(kundli: k),
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              );
                            },
                          ),
                        ),
    );
  }
}

/// Bottom-sheet form to create a kundli (legacy `createNewKundli.dart`).
class _AddKundliSheet extends StatefulWidget {
  const _AddKundliSheet();

  @override
  State<_AddKundliSheet> createState() => _AddKundliSheetState();
}

class _AddKundliSheetState extends State<_AddKundliSheet> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _place = TextEditingController();
  final _lat = TextEditingController(text: '28.6139');
  final _lng = TextEditingController(text: '77.2090');
  String _gender = 'Male';
  DateTime _date = DateTime.now();
  TimeOfDay _time = const TimeOfDay(hour: 8, minute: 0);
  bool _saving = false;
  bool _isAutoFilled = false;

  static const _cityPresets = [
    ('Varanasi', '25.3176', '82.9739'),
    ('New Delhi', '28.6139', '77.2090'),
    ('Mumbai', '19.0760', '72.8777'),
    ('Ayodhya', '26.7922', '82.1998'),
    ('Ujjain', '23.1765', '75.7885'),
    ('Haridwar', '29.9457', '78.1642'),
    ('Bengaluru', '12.9716', '77.5946'),
    ('Kolkata', '22.5726', '88.3639'),
  ];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _prefillFromUserProfile());
  }

  void _prefillFromUserProfile() {
    final user = context.read<AppSession>().user;
    if (user == null) return;

    bool filled = false;
    if (user.name != null && user.name!.trim().isNotEmpty && user.name != 'User') {
      _name.text = user.name!.trim();
      filled = true;
    }
    if (user.gender != null && user.gender!.trim().isNotEmpty) {
      final g = user.gender!.trim();
      if (['Male', 'Female', 'Other'].contains(g)) {
        _gender = g;
        filled = true;
      }
    }
    if (user.birthPlace != null && user.birthPlace!.trim().isNotEmpty) {
      _place.text = user.birthPlace!.trim();
      filled = true;
      // Auto-match city coordinates if in presets
      for (final c in _cityPresets) {
        if (c.$1.toLowerCase() == _place.text.toLowerCase()) {
          _lat.text = c.$2;
          _lng.text = c.$3;
          break;
        }
      }
    }
    if (user.birthDate != null) {
      _date = user.birthDate!;
      filled = true;
    }
    if (user.birthTime != null && user.birthTime!.trim().isNotEmpty) {
      try {
        final parts = user.birthTime!.trim().split(':');
        if (parts.length >= 2) {
          final h = int.parse(parts[0]);
          final m = int.parse(parts[1]);
          _time = TimeOfDay(hour: h, minute: m);
          filled = true;
        }
      } catch (_) {}
    }

    if (filled && mounted) {
      setState(() => _isAutoFilled = true);
    }
  }

  @override
  void dispose() {
    _name.dispose();
    _place.dispose();
    _lat.dispose();
    _lng.dispose();
    super.dispose();
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _date,
      firstDate: DateTime(1900),
      lastDate: DateTime.now().add(const Duration(days: 1)),
    );
    if (picked != null) setState(() => _date = picked);
  }

  Future<void> _pickTime() async {
    final picked = await showTimePicker(context: context, initialTime: _time);
    if (picked != null) setState(() => _time = picked);
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    final session = context.read<AppSession>();
    String two(int v) => v.toString().padLeft(2, '0');
    final k = Kundli(
      name: _name.text.trim(),
      gender: _gender,
      birthDate: _date,
      birthTime: '${two(_time.hour)}:${two(_time.minute)}',
      birthPlace: _place.text.trim(),
      latitude: double.tryParse(_lat.text.trim()),
      longitude: double.tryParse(_lng.text.trim()),
      timezone: 5.5,
      lang: 'en',
      isActive: 1,
      isDelete: 0,
      createdBy: (session.user?.id ?? 0) > 0 ? session.user!.id : 1,
      forMatch: 0,
    );
    try {
      await KundliApi.instance.add(k);
      if (mounted) Navigator.pop(context, true);
    } on ApiException catch (e) {
      if (mounted) showSnack(context, e.message, error: true);
    } catch (e) {
      if (mounted) showSnack(context, e.toString(), error: true);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text('New Kundli',
                  style: Theme.of(context)
                      .textTheme
                      .titleLarge
                      ?.copyWith(fontWeight: FontWeight.w800)),
              const SizedBox(height: 8),
              if (_isAutoFilled)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  margin: const EdgeInsets.only(bottom: 12),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFEF3C7),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: const Color(0xFFF59E0B)),
                  ),
                  child: const Row(
                    children: [
                      Icon(Icons.auto_awesome, size: 14, color: Color(0xFFD97706)),
                      SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          'आपकी प्रोफाइल से विवरण स्वतः भर दिए गए हैं (Auto-filled from your profile)',
                          style: TextStyle(
                            fontSize: 10.5,
                            fontWeight: FontWeight.w700,
                            color: Color(0xFF92400E),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              TextFormField(
                controller: _name,
                decoration: const InputDecoration(
                    labelText: 'Full name',
                    prefixIcon: Icon(Icons.person_outline)),
                validator: (v) =>
                    (v == null || v.trim().isEmpty) ? 'Enter a name' : null,
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<String>(
                initialValue: _gender,
                decoration: const InputDecoration(
                    labelText: 'Gender', prefixIcon: Icon(Icons.wc_rounded)),
                items: const [
                  DropdownMenuItem(value: 'Male', child: Text('Male')),
                  DropdownMenuItem(value: 'Female', child: Text('Female')),
                  DropdownMenuItem(value: 'Other', child: Text('Other')),
                ],
                onChanged: (v) => setState(() => _gender = v ?? 'Male'),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _place,
                decoration: const InputDecoration(
                    labelText: 'Birth place',
                    prefixIcon: Icon(Icons.location_city_rounded)),
                validator: (v) => (v == null || v.trim().isEmpty)
                    ? 'Enter birth place'
                    : null,
              ),
              const SizedBox(height: 8),
              // Smart City Quick Selector
              SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: Row(
                  children: _cityPresets.map((city) {
                    return Padding(
                      padding: const EdgeInsets.only(right: 6),
                      child: ActionChip(
                        label: Text(city.$1, style: const TextStyle(fontSize: 10.5)),
                        visualDensity: VisualDensity.compact,
                        onPressed: () {
                          setState(() {
                            _place.text = city.$1;
                            _lat.text = city.$2;
                            _lng.text = city.$3;
                          });
                        },
                      ),
                    );
                  }).toList(),
                ),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: _pickDate,
                      icon: const Icon(Icons.calendar_month_rounded),
                      label: Text('${_date.day}/${_date.month}/${_date.year}'),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: _pickTime,
                      icon: const Icon(Icons.schedule_rounded),
                      label: Text(_time.format(context)),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _lat,
                      keyboardType: const TextInputType.numberWithOptions(
                          decimal: true),
                      decoration: const InputDecoration(labelText: 'Latitude'),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: TextFormField(
                      controller: _lng,
                      keyboardType: const TextInputType.numberWithOptions(
                          decimal: true),
                      decoration:
                          const InputDecoration(labelText: 'Longitude'),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),
              FilledButton(
                style: FilledButton.styleFrom(
                    minimumSize: const Size.fromHeight(50)),
                onPressed: _saving ? null : _save,
                child: _saving
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(strokeWidth: 2))
                    : const Text('Save Kundli'),
              ),
              const SizedBox(height: 8),
            ],
          ),
        ),
      ),
    );
  }
}
