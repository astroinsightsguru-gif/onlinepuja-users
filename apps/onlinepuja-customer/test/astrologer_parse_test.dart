import 'package:flutter_test/flutter_test.dart';
import 'package:op_shared/op_shared.dart';

void main() {
  test('AstrologerApi.instance.list fetches and parses all live astrologers', () async {
    final list = await AstrologerApi.instance.list(sortBy: 'rating');
    expect(list, isNotEmpty);
    expect(list.length, greaterThanOrEqualTo(5));
    for (final a in list) {
      expect(a.id, isNotNull);
      expect(a.name, isNotEmpty);
    }
  }, timeout: const Timeout(Duration(minutes: 2)));
}
