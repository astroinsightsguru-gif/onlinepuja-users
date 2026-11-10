import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../../services/manager_auth_session.dart';
import '../../../theme/manager_theme.dart';
import 'manager_pin_screen.dart';

class ManagerLoginScreen extends StatefulWidget {
  const ManagerLoginScreen({super.key});

  @override
  State<ManagerLoginScreen> createState() => _ManagerLoginScreenState();
}

class _ManagerLoginScreenState extends State<ManagerLoginScreen> {
  final _emailCtrl = TextEditingController(text: 'admin@onlinepuja.live');
  final _passCtrl = TextEditingController();
  final _pinCtrl = TextEditingController();
  bool _obscurePassword = true;
  bool _isLoading = false;
  bool _isGoogleLoading = false;
  bool _enableQuickPin = true;
  bool _rememberPassword = true;

  @override
  void initState() {
    super.initState();
    _loadSavedCredentials();
  }

  Future<void> _loadSavedCredentials() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final savedEmail = prefs.getString('op_saved_email');
      final savedPass = prefs.getString('op_saved_pass');
      final remember = prefs.getBool('op_remember_pass') ?? true;
      if (mounted) {
        setState(() {
          _rememberPassword = remember;
          if (savedEmail != null && savedEmail.isNotEmpty) {
            _emailCtrl.text = savedEmail;
          }
          if (remember && savedPass != null && savedPass.isNotEmpty) {
            _passCtrl.text = savedPass;
          }
        });
      }
    } catch (_) {}
  }

  @override
  void dispose() {
    _emailCtrl.dispose();
    _passCtrl.dispose();
    _pinCtrl.dispose();
    super.dispose();
  }

  Future<void> _handleGoogleLogin() async {
    setState(() => _isGoogleLoading = true);
    try {
      final session = context.read<ManagerAuthSession>();
      final result = await session.loginWithGoogle();
      if (!mounted) return;
      if (result.success) {
        _showSnack(result.message, isError: false);
      } else {
        _showSnack(result.message, isError: true);
      }
    } catch (e) {
      if (mounted) {
        _showSnack('Google sign-in error: $e', isError: true);
      }
    } finally {
      if (mounted) setState(() => _isGoogleLoading = false);
    }
  }

  Future<void> _handleLogin() async {
    final email = _emailCtrl.text.trim();
    final password = _passCtrl.text;

    if (email.isEmpty) {
      _showSnack('Please enter your Superadmin email', isError: true);
      return;
    }

    if (password.isEmpty) {
      _showSnack('Please enter your password', isError: true);
      return;
    }

    if (_enableQuickPin && _pinCtrl.text.isNotEmpty && _pinCtrl.text.length != 4) {
      _showSnack('Master Quick PIN must be exactly 4 digits', isError: true);
      return;
    }

    setState(() => _isLoading = true);

    final session = context.read<ManagerAuthSession>();
    final result = await session.loginWithPassword(
      email: email,
      password: password,
      setPin: _enableQuickPin && _pinCtrl.text.length == 4 ? _pinCtrl.text : null,
    );

    if (!mounted) return;
    setState(() => _isLoading = false);

    if (result.success) {
      try {
        final prefs = await SharedPreferences.getInstance();
        if (_rememberPassword) {
          await prefs.setString('op_saved_email', email);
          await prefs.setString('op_saved_pass', password);
          await prefs.setBool('op_remember_pass', true);
        } else {
          await prefs.remove('op_saved_pass');
          await prefs.setBool('op_remember_pass', false);
        }
      } catch (_) {}

      _showSnack('Superadmin session authorized 🙏', isError: false);
    } else {
      _showSnack(result.message, isError: true);
    }
  }

  void _showSnack(String message, {required bool isError}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(
          children: [
            Icon(
              isError ? Icons.error_outline_rounded : Icons.check_circle_rounded,
              color: isError ? Colors.redAccent : const Color(0xFF10B981),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                message,
                style: const TextStyle(fontWeight: FontWeight.w600, color: Colors.white),
              ),
            ),
          ],
        ),
        backgroundColor: isError ? const Color(0xFF7F1D1D) : const Color(0xFF064E3B),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final session = context.watch<ManagerAuthSession>();

    return Scaffold(
      backgroundColor: const Color(0xFF0B0F19),
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 420),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // App Icon & Header
                  Center(
                    child: Container(
                      width: 80,
                      height: 80,
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFFF59E0B), Color(0xFFD97706)],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(22),
                        boxShadow: [
                          BoxShadow(
                            color: const Color(0xFFF59E0B).withValues(alpha: 0.35),
                            blurRadius: 20,
                            offset: const Offset(0, 8),
                          ),
                        ],
                      ),
                      child: const Center(
                        child: Text(
                          'ॐ',
                          style: TextStyle(
                            fontSize: 42,
                            fontWeight: FontWeight.w900,
                            color: Color(0xFF0B0F19),
                          ),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 20),

                  const Text(
                    'OnlinePuja.live',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 26,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 0.5,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: 4),

                  Center(
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: ManagerTheme.brandGold.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: ManagerTheme.brandGold.withValues(alpha: 0.4)),
                      ),
                      child: const Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.shield_rounded, color: ManagerTheme.brandGold, size: 14),
                          SizedBox(width: 6),
                          Text(
                            'SUPERADMIN & TEAM COMMAND TOWER',
                            style: TextStyle(
                              color: ManagerTheme.brandGold,
                              fontSize: 10,
                              fontWeight: FontWeight.w900,
                              letterSpacing: 0.8,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 28),

                  // 1-Click Google Sign-In Primary Button
                  Container(
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(14),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.3),
                          blurRadius: 12,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: ElevatedButton(
                      onPressed: _isGoogleLoading || _isLoading ? null : _handleGoogleLogin,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.white,
                        foregroundColor: const Color(0xFF1E293B),
                        padding: const EdgeInsets.symmetric(vertical: 15, horizontal: 16),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(14),
                        ),
                        elevation: 0,
                      ),
                      child: _isGoogleLoading
                          ? const SizedBox(
                              height: 22,
                              width: 22,
                              child: CircularProgressIndicator(strokeWidth: 2.5, color: Color(0xFF1E293B)),
                            )
                          : Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Container(
                                  width: 24,
                                  height: 24,
                                  decoration: const BoxDecoration(
                                    shape: BoxShape.circle,
                                    color: Colors.white,
                                  ),
                                  child: const Center(
                                    child: Text(
                                      'G',
                                      style: TextStyle(
                                        color: Color(0xFF4285F4),
                                        fontSize: 20,
                                        fontWeight: FontWeight.w900,
                                      ),
                                    ),
                                  ),
                                ),
                                const SizedBox(width: 12),
                                const Text(
                                  '1-Click Continue with Google',
                                  style: TextStyle(
                                    fontSize: 15,
                                    fontWeight: FontWeight.w700,
                                    letterSpacing: 0.2,
                                    color: Color(0xFF1F2937),
                                  ),
                                ),
                              ],
                            ),
                    ),
                  ),

                  const SizedBox(height: 22),

                  // OR Divider
                  Row(
                    children: [
                      Expanded(
                        child: Container(
                          height: 1,
                          color: const Color(0xFF334155),
                        ),
                      ),
                      const Padding(
                        padding: EdgeInsets.symmetric(horizontal: 14),
                        child: Text(
                          'OR SUPERADMIN PASSWORD',
                          style: TextStyle(
                            color: Color(0xFF64748B),
                            fontSize: 10,
                            fontWeight: FontWeight.w800,
                            letterSpacing: 1.0,
                          ),
                        ),
                      ),
                      Expanded(
                        child: Container(
                          height: 1,
                          color: const Color(0xFF334155),
                        ),
                      ),
                    ],
                  ),

                  const SizedBox(height: 22),

                  // Email Input
                  const Text(
                    'Superadmin Email',
                    style: TextStyle(color: Color(0xFF94A3B8), fontSize: 13, fontWeight: FontWeight.w600),
                  ),
                  const SizedBox(height: 8),
                  TextField(
                    controller: _emailCtrl,
                    keyboardType: TextInputType.emailAddress,
                    autofillHints: const [AutofillHints.email, AutofillHints.username],
                    style: const TextStyle(color: Colors.white),
                    decoration: InputDecoration(
                      filled: true,
                      fillColor: const Color(0xFF1E293B),
                      hintText: 'admin@onlinepuja.live',
                      hintStyle: const TextStyle(color: Colors.white30),
                      prefixIcon: const Icon(Icons.email_outlined, color: ManagerTheme.brandGold, size: 20),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(14),
                        borderSide: BorderSide.none,
                      ),
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(14),
                        borderSide: const BorderSide(color: Color(0xFF334155), width: 1),
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(14),
                        borderSide: const BorderSide(color: ManagerTheme.brandGold, width: 1.5),
                      ),
                    ),
                  ),
                  const SizedBox(height: 18),

                  // Password Input
                  const Text(
                    'Superadmin Password',
                    style: TextStyle(color: Color(0xFF94A3B8), fontSize: 13, fontWeight: FontWeight.w600),
                  ),
                  const SizedBox(height: 8),
                  TextField(
                    controller: _passCtrl,
                    obscureText: _obscurePassword,
                    autofillHints: const [AutofillHints.password],
                    style: const TextStyle(color: Colors.white),
                    decoration: InputDecoration(
                      filled: true,
                      fillColor: const Color(0xFF1E293B),
                      hintText: '••••••••',
                      hintStyle: const TextStyle(color: Colors.white30),
                      prefixIcon: const Icon(Icons.lock_outline_rounded, color: ManagerTheme.brandGold, size: 20),
                      suffixIcon: IconButton(
                        icon: Icon(
                          _obscurePassword ? Icons.visibility_outlined : Icons.visibility_off_outlined,
                          color: Colors.white54,
                          size: 20,
                        ),
                        onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
                      ),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(14),
                        borderSide: BorderSide.none,
                      ),
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(14),
                        borderSide: const BorderSide(color: Color(0xFF334155), width: 1),
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(14),
                        borderSide: const BorderSide(color: ManagerTheme.brandGold, width: 1.5),
                      ),
                    ),
                  ),
                  const SizedBox(height: 8),

                  // Remember Password Option
                  InkWell(
                    onTap: () => setState(() => _rememberPassword = !_rememberPassword),
                    borderRadius: BorderRadius.circular(8),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(vertical: 4),
                      child: Row(
                        children: [
                          SizedBox(
                            height: 20,
                            width: 20,
                            child: Checkbox(
                              value: _rememberPassword,
                              activeColor: ManagerTheme.brandGold,
                              checkColor: const Color(0xFF0B0F19),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(4)),
                              onChanged: (val) => setState(() => _rememberPassword = val ?? true),
                            ),
                          ),
                          const SizedBox(width: 8),
                          const Text(
                            'Save password on this device for instant login',
                            style: TextStyle(
                              color: Color(0xFF94A3B8),
                              fontSize: 12,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 14),

                  // Master Quick PIN Option
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: const Color(0xFF1E293B).withValues(alpha: 0.6),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: const Color(0xFF334155)),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            const Icon(Icons.dialpad_rounded, color: ManagerTheme.brandGold, size: 18),
                            const SizedBox(width: 8),
                            const Expanded(
                              child: Text(
                                'Set 4-Digit Quick Unlock PIN',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 13,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ),
                            Switch(
                              value: _enableQuickPin,
                              activeThumbColor: ManagerTheme.brandGold,
                              onChanged: (val) => setState(() => _enableQuickPin = val),
                            ),
                          ],
                        ),
                        if (_enableQuickPin) ...[
                          const SizedBox(height: 6),
                          const Text(
                            'Allows fast 1-tap PIN unlock on mobile without re-typing password.',
                            style: TextStyle(color: Colors.white54, fontSize: 11),
                          ),
                          const SizedBox(height: 10),
                          TextField(
                            controller: _pinCtrl,
                            keyboardType: TextInputType.number,
                            maxLength: 4,
                            obscureText: true,
                            style: const TextStyle(
                              color: Colors.white,
                              letterSpacing: 8,
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                            ),
                            decoration: InputDecoration(
                              counterText: '',
                              filled: true,
                              fillColor: const Color(0xFF0F172A),
                              hintText: '••••',
                              hintStyle: const TextStyle(color: Colors.white24, letterSpacing: 8),
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(10),
                                borderSide: BorderSide.none,
                              ),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),

                  // Login Button
                  ElevatedButton(
                    onPressed: _isLoading || _isGoogleLoading ? null : _handleLogin,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: ManagerTheme.brandGold,
                      foregroundColor: const Color(0xFF0B0F19),
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      elevation: 4,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    ),
                    child: _isLoading
                        ? const SizedBox(
                            height: 22,
                            width: 22,
                            child: CircularProgressIndicator(strokeWidth: 2.5, color: Color(0xFF0B0F19)),
                          )
                        : const Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(Icons.login_rounded, size: 20),
                              SizedBox(width: 8),
                              Text(
                                'Authorize & Open Command Center',
                                style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800),
                              ),
                            ],
                          ),
                  ),

                  // Quick Switch to PIN Unlock if PIN already configured
                  if (session.hasPin) ...[
                    const SizedBox(height: 16),
                    TextButton.icon(
                      onPressed: () {
                        Navigator.of(context).push(
                          MaterialPageRoute(builder: (_) => const ManagerPinScreen()),
                        );
                      },
                      icon: const Icon(Icons.dialpad_rounded, color: ManagerTheme.brandGold, size: 18),
                      label: const Text(
                        'Unlock with 4-Digit Quick PIN',
                        style: TextStyle(
                          color: ManagerTheme.brandGold,
                          fontWeight: FontWeight.w700,
                          fontSize: 13,
                        ),
                      ),
                    ),
                  ],

                  const SizedBox(height: 28),
                  const Center(
                    child: Text(
                      'Protected by 256-bit TLS · End-to-End Encrypted\nOnlinePuja Technologies Private Limited',
                      textAlign: TextAlign.center,
                      style: TextStyle(color: Colors.white24, fontSize: 11, height: 1.4),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
