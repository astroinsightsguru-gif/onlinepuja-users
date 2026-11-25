import 'package:flutter/material.dart';

import '../l10n/app_strings.dart';

/// Loading / error / empty states shared by both apps.
class StatusViews {
  StatusViews._();

  static bool _isHindi(BuildContext context) {
    try {
      if (Localizations.maybeLocaleOf(context)?.languageCode == 'hi') return true;
      return LocaleManager.instance.currentLanguage.value != AppLanguage.en;
    } catch (_) {
      return false;
    }
  }

  static Widget loading(BuildContext context, [String? label]) => Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const CircularProgressIndicator(strokeWidth: 2.4),
            if (label != null) ...[
              const SizedBox(height: 14),
              Text(label,
                  style: Theme.of(context).textTheme.bodyMedium),
            ],
          ],
        ),
      );

  static Widget error(BuildContext context, Object error,
      {VoidCallback? onRetry}) {
    final scheme = Theme.of(context).colorScheme;
    final isHi = _isHindi(context);
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.auto_awesome_outlined, size: 44, color: scheme.primary),
            const SizedBox(height: 12),
            Text(
              isHi
                  ? 'नक्षत्र संरेखित हो रहे हैं... कृपया पुनः प्रयास करें।'
                  : 'The stars are aligning… please retry.',
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: 6),
            Text(
              error.toString(),
              textAlign: TextAlign.center,
              maxLines: 3,
              overflow: TextOverflow.ellipsis,
              style: Theme.of(context)
                  .textTheme
                  .bodySmall
                  ?.copyWith(color: scheme.outline),
            ),
            if (onRetry != null) ...[
              const SizedBox(height: 16),
              FilledButton.icon(
                onPressed: onRetry,
                icon: const Icon(Icons.refresh),
                label: Text(isHi ? 'पुनः प्रयास करें' : 'Retry'),
              ),
            ],
          ],
        ),
      ),
    );
  }

  static Widget empty(BuildContext context, {String? message}) {
    final scheme = Theme.of(context).colorScheme;
    final isHi = _isHindi(context);
    final displayMessage = message ?? (isHi ? 'यहाँ अभी कोई जानकारी उपलब्ध नहीं है' : 'Nothing here yet');
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.hourglass_empty, size: 40, color: scheme.outline),
            const SizedBox(height: 10),
            Text(displayMessage, style: Theme.of(context).textTheme.bodyLarge),
          ],
        ),
      ),
    );
  }

  /// Shimmer-less skeleton placeholder (Material 3 style pulse).
  static Widget skeletonList(BuildContext context, {int items = 4}) {
    final scheme = Theme.of(context).colorScheme;
    return SingleChildScrollView(
      physics: const NeverScrollableScrollPhysics(),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          for (int i = 0; i < items; i++) ...[
            Container(
              height: 92,
              margin: const EdgeInsets.only(bottom: 12),
              decoration: BoxDecoration(
                color: scheme.surface,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: scheme.outline.withValues(alpha: 0.35)),
              ),
              child: Row(
                children: [
                  const SizedBox(width: 16),
                  CircleAvatar(radius: 26, backgroundColor: scheme.primaryContainer),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Container(
                          height: 12,
                          width: 140,
                          decoration: BoxDecoration(
                            color: scheme.primaryContainer,
                            borderRadius: BorderRadius.circular(6),
                          ),
                        ),
                        const SizedBox(height: 8),
                        Container(
                          height: 10,
                          width: 200,
                          decoration: BoxDecoration(
                            color: scheme.outline.withValues(alpha: 0.35),
                            borderRadius: BorderRadius.circular(6),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 16),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }
}

/// Small helper to snack a message.
void showSnack(BuildContext context, String message, {bool error = false}) {
  ScaffoldMessenger.of(context)
    ..clearSnackBars()
    ..showSnackBar(SnackBar(
      content: Text(message),
      backgroundColor: error
          ? Theme.of(context).colorScheme.error
          : Theme.of(context).colorScheme.primary,
    ));
}
