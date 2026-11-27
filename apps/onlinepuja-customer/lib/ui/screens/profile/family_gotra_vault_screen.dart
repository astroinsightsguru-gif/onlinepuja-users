import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:op_shared/op_shared.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../theme/customer_theme.dart';

class FamilyGotraVaultScreen extends StatefulWidget {
  const FamilyGotraVaultScreen({super.key});

  static const route = '/family-gotra-vault';

  @override
  State<FamilyGotraVaultScreen> createState() => _FamilyGotraVaultScreenState();
}

class _FamilyGotraVaultScreenState extends State<FamilyGotraVaultScreen> {
  static const _kPrefGotraKey = 'user_family_gotra_vault';

  final _gotraCtrl = TextEditingController();
  final _kuldevtaCtrl = TextEditingController();

  List<Map<String, String>> _familyMembers = [];
  List<Map<String, String>> _pitruMembers = [];

  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _loadFromStorage();
  }

  Future<void> _loadFromStorage() async {
    try {
      final user = SessionStore.instance.user;
      final userId = user?.id ?? 0;

      // 1. First attempt loading from live backend API
      if (userId > 0) {
        final remote = await MiscApi.instance.getGotraVault(userId: userId);
        if (remote.isNotEmpty && mounted) {
          setState(() {
            if (remote['gotra'] != null) _gotraCtrl.text = remote['gotra'].toString();
            if (remote['kuldevta'] != null) _kuldevtaCtrl.text = remote['kuldevta'].toString();
            if (remote['family_members'] is List) {
              _familyMembers = (remote['family_members'] as List)
                  .map((e) => Map<String, String>.from(e as Map))
                  .toList();
            }
          });
          return;
        }
      }

      // 2. Fallback to local storage
      final sp = await SharedPreferences.getInstance();
      final raw = sp.getString(_kPrefGotraKey);
      if (raw != null) {
        final data = json.decode(raw) as Map<String, dynamic>;
        setState(() {
          _gotraCtrl.text = data['gotra'] ?? '';
          _kuldevtaCtrl.text = data['kuldevta'] ?? '';
          if (data['members'] is List) {
            _familyMembers = (data['members'] as List)
                .map((e) => Map<String, String>.from(e as Map))
                .toList();
          }
          if (data['pitrus'] is List) {
            _pitruMembers = (data['pitrus'] as List)
                .map((e) => Map<String, String>.from(e as Map))
                .toList();
          }
        });
      } else if (user != null && (user.name ?? '').isNotEmpty && user.name != 'User') {
        setState(() {
          _familyMembers = [
            {
              'name': user.name!,
              'relation': 'Self (Karta)',
              'rashi': '',
              'nakshatra': '',
            }
          ];
        });
      }
    } catch (_) {}
  }

  Future<void> _saveToStorage() async {
    setState(() => _saving = true);
    try {
      final user = SessionStore.instance.user;
      final userId = user?.id ?? 0;

      // 1. Sync directly to live backend API
      if (userId > 0) {
        await MiscApi.instance.saveGotraVault(
          userId: userId,
          gotra: _gotraCtrl.text.trim(),
          kuldevta: _kuldevtaCtrl.text.trim(),
          familyMembers: _familyMembers,
        );
      }

      // 2. Persist locally
      final sp = await SharedPreferences.getInstance();
      final data = {
        'gotra': _gotraCtrl.text.trim(),
        'kuldevta': _kuldevtaCtrl.text.trim(),
        'members': _familyMembers,
        'pitrus': _pitruMembers,
      };
      await sp.setString(_kPrefGotraKey, json.encode(data));

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            backgroundColor: Color(0xFF10B981),
            content: Text('🕉️ Family Gotra Vault saved to cloud! Auto-applied to all future Sankalpas.'),
          ),
        );
      }
    } catch (_) {
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  void _addFamilyMemberDialog() {
    final nameCtrl = TextEditingController();
    String relation = 'Son';
    String rashi = 'Simha (Leo)';
    String nakshatra = 'Magha';

    showDialog(
      context: context,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setDlgState) {
            return AlertDialog(
              title: const Row(
                children: [
                  Icon(Icons.person_add_alt_1, color: CustomerTheme.brandSaffron),
                  SizedBox(width: 8),
                  Text('Add Family Member', style: TextStyle(fontSize: 16)),
                ],
              ),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    TextField(
                      controller: nameCtrl,
                      decoration: const InputDecoration(
                        labelText: 'Full Name *',
                        hintText: 'e.g. Aarav Sharma',
                      ),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<String>(
                      initialValue: relation,
                      decoration: const InputDecoration(labelText: 'Relationship'),
                      items: const [
                        DropdownMenuItem(value: 'Spouse', child: Text('Spouse')),
                        DropdownMenuItem(value: 'Son', child: Text('Son')),
                        DropdownMenuItem(value: 'Daughter', child: Text('Daughter')),
                        DropdownMenuItem(value: 'Father', child: Text('Father')),
                        DropdownMenuItem(value: 'Mother', child: Text('Mother')),
                        DropdownMenuItem(value: 'Brother', child: Text('Brother')),
                      ],
                      onChanged: (v) => setDlgState(() => relation = v!),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<String>(
                      initialValue: rashi,
                      decoration: const InputDecoration(labelText: 'Janma Rashi'),
                      items: const [
                        DropdownMenuItem(value: 'Mesh (Aries)', child: Text('Mesh (Aries)')),
                        DropdownMenuItem(value: 'Vrishabha (Taurus)', child: Text('Vrishabha (Taurus)')),
                        DropdownMenuItem(value: 'Mithun (Gemini)', child: Text('Mithun (Gemini)')),
                        DropdownMenuItem(value: 'Karka (Cancer)', child: Text('Karka (Cancer)')),
                        DropdownMenuItem(value: 'Simha (Leo)', child: Text('Simha (Leo)')),
                        DropdownMenuItem(value: 'Kanya (Virgo)', child: Text('Kanya (Virgo)')),
                        DropdownMenuItem(value: 'Tula (Libra)', child: Text('Tula (Libra)')),
                        DropdownMenuItem(value: 'Vrishchika (Scorpio)', child: Text('Vrishchika (Scorpio)')),
                        DropdownMenuItem(value: 'Dhanu (Sagittarius)', child: Text('Dhanu (Sagittarius)')),
                        DropdownMenuItem(value: 'Makara (Capricorn)', child: Text('Makara (Capricorn)')),
                        DropdownMenuItem(value: 'Kumbha (Aquarius)', child: Text('Kumbha (Aquarius)')),
                        DropdownMenuItem(value: 'Meena (Pisces)', child: Text('Meena (Pisces)')),
                      ],
                      onChanged: (v) => setDlgState(() => rashi = v!),
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(ctx),
                  child: const Text('Cancel'),
                ),
                ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: CustomerTheme.brandSaffron,
                    foregroundColor: Colors.white,
                  ),
                  onPressed: () {
                    if (nameCtrl.text.trim().isNotEmpty) {
                      setState(() {
                        _familyMembers.add({
                          'name': nameCtrl.text.trim(),
                          'relation': relation,
                          'rashi': rashi,
                          'nakshatra': nakshatra,
                        });
                      });
                      _saveToStorage();
                      Navigator.pop(ctx);
                    }
                  },
                  child: const Text('Add Member'),
                ),
              ],
            );
          },
        );
      },
    );
  }

  void _addPitruMemberDialog() {
    final nameCtrl = TextEditingController();
    String relation = 'Grandfather (दादाजी)';
    String tithi = 'सर्वपितृ अमावस्या (Sarvapitru Amavasya)';

    showDialog(
      context: context,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setDlgState) {
            return AlertDialog(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              title: const Row(
                children: [
                  Icon(Icons.self_improvement_rounded, color: Color(0xFF78350F)),
                  SizedBox(width: 8),
                  Text('Add Ancestor / पूर्वज', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                ],
              ),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    TextField(
                      controller: nameCtrl,
                      decoration: const InputDecoration(
                        labelText: 'Name (स्व. पूर्वज का नाम) *',
                        hintText: 'e.g. स्वर्गीय रामनारायण शर्मा',
                      ),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<String>(
                      initialValue: relation,
                      decoration: const InputDecoration(labelText: 'Relationship (सम्बन्ध)'),
                      items: const [
                        DropdownMenuItem(value: 'Father (पिताजी)', child: Text('Father (पिताजी)')),
                        DropdownMenuItem(value: 'Mother (माताजी)', child: Text('Mother (माताजी)')),
                        DropdownMenuItem(value: 'Grandfather (दादाजी)', child: Text('Grandfather (दादाजी)')),
                        DropdownMenuItem(value: 'Grandmother (दादीजी)', child: Text('Grandmother (दादीजी)')),
                        DropdownMenuItem(value: 'Maternal Grandfather (नानाजी)', child: Text('Maternal Grandfather (नानाजी)')),
                        DropdownMenuItem(value: 'Maternal Grandmother (नानीजी)', child: Text('Maternal Grandmother (नानीजी)')),
                        DropdownMenuItem(value: 'Ancestor (कुल पूर्वज)', child: Text('Ancestor (कुल पूर्वज)')),
                      ],
                      onChanged: (v) => setDlgState(() => relation = v!),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<String>(
                      initialValue: tithi,
                      decoration: const InputDecoration(labelText: 'Shradh / Death Tithi (श्राद्ध तिथि)'),
                      items: const [
                        DropdownMenuItem(value: 'सर्वपितृ अमावस्या (Sarvapitru Amavasya)', child: Text('सर्वपितृ अमावस्या (Amavasya)')),
                        DropdownMenuItem(value: 'प्रतिपदा (Pratipada)', child: Text('प्रतिपदा (Pratipada)')),
                        DropdownMenuItem(value: 'द्वितीया (Dwitiya)', child: Text('द्वितीया (Dwitiya)')),
                        DropdownMenuItem(value: 'तृतीया (Tritiya)', child: Text('तृतीया (Tritiya)')),
                        DropdownMenuItem(value: 'चतुर्थी (Chaturthi)', child: Text('चतुर्थी (Chaturthi)')),
                        DropdownMenuItem(value: 'पंचमी (Panchami)', child: Text('पंचमी (Panchami)')),
                        DropdownMenuItem(value: 'षष्ठी (Shashthi)', child: Text('षष्ठी (Shashthi)')),
                        DropdownMenuItem(value: 'सप्तमी (Saptami)', child: Text('सप्तमी (Saptami)')),
                        DropdownMenuItem(value: 'अष्टमी (Ashtami)', child: Text('अष्टमी (Ashtami)')),
                        DropdownMenuItem(value: 'नवमी (Navami - मातृ नवमी)', child: Text('नवमी (Matru Navami)')),
                        DropdownMenuItem(value: 'दशमी (Dashami)', child: Text('दशमी (Dashami)')),
                        DropdownMenuItem(value: 'एकादशी (Ekadashi)', child: Text('एकादशी (Ekadashi)')),
                        DropdownMenuItem(value: 'द्वादशी (Dwadashi)', child: Text('द्वादशी (Dwadashi)')),
                        DropdownMenuItem(value: 'त्रयोदशी (Trayodashi)', child: Text('त्रयोदशी (Trayodashi)')),
                        DropdownMenuItem(value: 'चतुर्दशी (Chaturdashi)', child: Text('चतुर्दशी (Chaturdashi)')),
                        DropdownMenuItem(value: 'पूर्णिमा (Purnima)', child: Text('पूर्णिमा (Purnima)')),
                      ],
                      onChanged: (v) => setDlgState(() => tithi = v!),
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(ctx),
                  child: const Text('Cancel'),
                ),
                ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF78350F),
                    foregroundColor: Colors.white,
                  ),
                  onPressed: () {
                    if (nameCtrl.text.trim().isNotEmpty) {
                      setState(() {
                        _pitruMembers.add({
                          'name': nameCtrl.text.trim(),
                          'relation': relation,
                          'tithi': tithi,
                        });
                      });
                      _saveToStorage();
                      Navigator.pop(ctx);
                    }
                  },
                  child: const Text('Add Ancestor'),
                ),
              ],
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF9F7F2),
      appBar: AppBar(
        title: const Text('Family Gotra & Lineage Vault',
            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17)),
        backgroundColor: const Color(0xFF1A132F),
        foregroundColor: Colors.white,
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _buildInfoBanner(),
          const SizedBox(height: 16),
          _buildGotraCard(),
          const SizedBox(height: 20),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'Family Members for Sankalp:',
                style: TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF1A132F),
                ),
              ),
              TextButton.icon(
                icon: const Icon(Icons.add, size: 16, color: CustomerTheme.brandSaffron),
                label: const Text('Add Member',
                    style: TextStyle(color: CustomerTheme.brandSaffron, fontWeight: FontWeight.bold)),
                onPressed: _addFamilyMemberDialog,
              ),
            ],
          ),
          const SizedBox(height: 8),
          if (_familyMembers.isEmpty)
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Colors.black.withValues(alpha: 0.06)),
              ),
              child: const Column(
                children: [
                  Icon(Icons.people_outline_rounded, size: 36, color: CustomerTheme.brandSaffron),
                  SizedBox(height: 8),
                  Text(
                    'No Family Members Added Yet',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                  ),
                  SizedBox(height: 4),
                  Text(
                    'Add your family members to automatically include their names, rashis and nakshatras in future temple puja sankalps.',
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 12, color: Colors.black54),
                  ),
                ],
              ),
            )
          else
            ..._familyMembers.asMap().entries.map((entry) {
              final idx = entry.key;
              final m = entry.value;
              return _buildMemberCard(idx, m);
            }),
          const SizedBox(height: 24),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Row(
                children: [
                  Icon(Icons.spa_rounded, color: Color(0xFF78350F), size: 18),
                  SizedBox(width: 8),
                  Text(
                    'Ancestral Vault (पितृ मोक्ष स्मृति):',
                    style: TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF1A132F),
                    ),
                  ),
                ],
              ),
              TextButton.icon(
                icon: const Icon(Icons.add, size: 16, color: Color(0xFF78350F)),
                label: const Text('Add Ancestor',
                    style: TextStyle(color: Color(0xFF78350F), fontWeight: FontWeight.bold)),
                onPressed: _addPitruMemberDialog,
              ),
            ],
          ),
          const SizedBox(height: 8),
          if (_pitruMembers.isEmpty)
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Colors.black.withValues(alpha: 0.06)),
              ),
              child: const Column(
                children: [
                  Icon(Icons.self_improvement_rounded, size: 32, color: Color(0xFF78350F)),
                  SizedBox(height: 6),
                  Text(
                    'No Ancestral Records Added',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5),
                  ),
                  SizedBox(height: 4),
                  Text(
                    'Add deceased ancestors to receive automated Shradh Tithi reminders and 1-tap Gaya/Kashi Pind Daan bookings.',
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 11.5, color: Colors.black54),
                  ),
                ],
              ),
            )
          else
            ..._pitruMembers.asMap().entries.map((entry) {
              final idx = entry.key;
              final p = entry.value;
              return _buildPitruCard(idx, p);
            }),
          const SizedBox(height: 24),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: CustomerTheme.brandSaffron,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              ),
              icon: _saving
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                    )
                  : const Icon(Icons.save_rounded),
              label: const Text(
                'Save Gotra Vault 🕉️',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
              ),
              onPressed: _saving ? null : _saveToStorage,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoBanner() {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFFFFBEB),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: CustomerTheme.brandGold.withValues(alpha: 0.4)),
      ),
      child: const Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('🕉️', style: TextStyle(fontSize: 22)),
          SizedBox(width: 12),
          Expanded(
            child: Text(
              'Save your family lineage once. During Puja Sankalpa, Pandit Ji will chant your exact Gotra, Kuldevi & family names automatically.',
              style: TextStyle(fontSize: 12, height: 1.4, color: Color(0xFF78350F)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildGotraCard() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Ancestral Lineage Details',
            style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF1A132F)),
          ),
          const SizedBox(height: 14),
          TextField(
            controller: _gotraCtrl,
            decoration: InputDecoration(
              labelText: 'Family Gotra (गोत्र) *',
              hintText: 'e.g. Kashyap, Bharadwaj, Vashistha',
              prefixIcon: const Icon(Icons.temple_hindu, color: CustomerTheme.brandSaffron),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
          const SizedBox(height: 14),
          TextField(
            controller: _kuldevtaCtrl,
            decoration: InputDecoration(
              labelText: 'Kuldevi / Kuldevta (कुलदेवता)',
              hintText: 'e.g. Kuldevi Vindhyavasini, Mahadev',
              prefixIcon: const Icon(Icons.shield_moon, color: CustomerTheme.brandSaffron),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMemberCard(int index, Map<String, String> m) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Colors.black.withValues(alpha: 0.08)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            children: [
              CircleAvatar(
                backgroundColor: CustomerTheme.brandSaffron.withValues(alpha: 0.12),
                child: Text(
                  m['name']?.isNotEmpty == true ? m['name']![0] : 'ॐ',
                  style: const TextStyle(color: CustomerTheme.brandSaffron, fontWeight: FontWeight.bold),
                ),
              ),
              const SizedBox(width: 12),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    m['name'] ?? '',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    '${m['relation']} • ${m['rashi']}',
                    style: const TextStyle(fontSize: 11, color: Colors.black54),
                  ),
                ],
              ),
            ],
          ),
          if (index > 0)
            IconButton(
              icon: const Icon(Icons.delete_outline, color: Colors.redAccent, size: 18),
              onPressed: () {
                setState(() => _familyMembers.removeAt(index));
                _saveToStorage();
              },
            ),
        ],
      ),
    );
  }

  Widget _buildPitruCard(int index, Map<String, String> p) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFF78350F).withValues(alpha: 0.15)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            children: [
              CircleAvatar(
                backgroundColor: const Color(0xFF78350F).withValues(alpha: 0.12),
                child: const Text('🪷', style: TextStyle(fontSize: 16)),
              ),
              const SizedBox(width: 12),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    p['name'] ?? '',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    '${p['relation']} • ${p['tithi']}',
                    style: const TextStyle(fontSize: 11, color: Color(0xFF78350F), fontWeight: FontWeight.w600),
                  ),
                ],
              ),
            ],
          ),
          IconButton(
            icon: const Icon(Icons.delete_outline, color: Colors.redAccent, size: 18),
            onPressed: () {
              setState(() => _pitruMembers.removeAt(index));
              _saveToStorage();
            },
          ),
        ],
      ),
    );
  }
}
