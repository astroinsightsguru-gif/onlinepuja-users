import 'package:flutter_test/flutter_test.dart';
import 'package:op_shared/op_shared.dart';

void main() {
  test('PujaApi.instance.list fetches and parses all live pujas', () async {
    final list = await PujaApi.instance.list();
    expect(list, isNotEmpty);
    for (final p in list) {
      expect(p.id, isNotNull);
      expect(p.title, isNotEmpty);
      expect(p.packages, isNotNull);
      if (p.packages!.isNotEmpty) {
        expect(p.packages!.first.name, isNotEmpty);
        expect(p.packages!.first.priceValue, greaterThan(0));
      }
    }
  }, timeout: const Timeout(Duration(minutes: 2)));

  test('PujaApi.instance.categories fetches and parses categories', () async {
    final cats = await PujaApi.instance.categories();
    expect(cats, isNotEmpty);
    for (final c in cats) {
      expect(c.id, isNotNull);
      expect(c.name, isNotEmpty);
    }
  }, timeout: const Timeout(Duration(minutes: 2)));
}
