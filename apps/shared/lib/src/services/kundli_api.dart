import '../api/api_client.dart';
import '../l10n/app_strings.dart';
import '../models/kundli.dart';

/// Kundli (birth-chart) endpoints mirroring the legacy Customer-app calls:
/// `/getkundali`, `/kundali/addnew`, `/kundali/basic|chart|dasha|dosha|
/// astakvarga|planet-report|ascendant-report`, `/Kundali/show/:id`.
class KundliApi {
  KundliApi._();
  static final KundliApi instance = KundliApi._();

  final _api = ApiClient.instance;

  static List<Map<String, dynamic>> _asMapList(dynamic decoded) {
    if (decoded is Map<String, dynamic>) {
      final rl = decoded['recordList'];
      if (rl is List) {
        return rl.whereType<Map<String, dynamic>>().toList();
      }
    }
    if (decoded is List) {
      return decoded.whereType<Map<String, dynamic>>().toList();
    }
    return const [];
  }

  static Map<String, dynamic> _asMap(dynamic decoded) {
    if (decoded is Map<String, dynamic>) {
      final rl = decoded['recordList'];
      if (rl is Map<String, dynamic>) return rl;
      return decoded;
    }
    return const {};
  }

  /// Saved kundli list for a user (legacy `getkundali`).
  Future<List<Kundli>> list({required int userId}) async {
    final decoded = await _api.post('/getkundali', body: {'userId': userId});
    return _asMapList(decoded).map(Kundli.fromJson).toList();
  }

  /// Create a new kundli record (legacy `kundali/addnew`) → new id.
  Future<int?> add(Kundli kundli) async {
    final decoded = await _api.post('/kundali/addnew', body: kundli.toJson());
    final map = _asMap(decoded);
    final id = map['id'] ?? map['kundaliId'] ?? map['lastInsertedId'];
    return id == null ? null : int.tryParse(id.toString());
  }

  /// Delete a saved kundli.
  Future<void> delete({required int kundaliId}) async {
    await _api.post('/kundali/delete', body: {'id': kundaliId});
  }

  /// Basic details (Tithi/Karan/Yog/Nakshatra/sunrise…) — legacy
  /// `kundali/basic` (astrologyapi basic_panchang passthrough).
  Future<KundliBasic> basic({
    int? id,
    required DateTime date,
    required String time,
    required double lat,
    required double lng,
    required double tz,
  }) async {
    final decoded = await _api.post('/kundali/basic', body: _astroBody(date, time, lat, lng, tz, id: id));
    final map = decoded is Map<String, dynamic> ? decoded : _asMap(decoded);
    return KundliBasic.fromJson(map);
  }

  /// Full chart + planet details — legacy `kundali/chart`.
  Future<Map<String, dynamic>> chart({
    int? id,
    required DateTime date,
    required String time,
    required double lat,
    required double lng,
    required double tz,
  }) async {
    final decoded =
        await _api.post('/kundali/chart', body: _astroBody(date, time, lat, lng, tz, id: id));
    return _asMap(decoded);
  }

  /// Vimshottari dasha tree — legacy `kundali/dasha`.
  Future<Map<String, dynamic>> dasha({
    int? id,
    required DateTime date,
    required String time,
    required double lat,
    required double lng,
    required double tz,
  }) async {
    final decoded =
        await _api.post('/kundali/dasha', body: _astroBody(date, time, lat, lng, tz, id: id));
    return _asMap(decoded);
  }

  /// Dosha report — legacy `kundali/dosha`.
  Future<Map<String, dynamic>> dosha({
    int? id,
    required DateTime date,
    required String time,
    required double lat,
    required double lng,
    required double tz,
  }) async {
    final decoded =
        await _api.post('/kundali/dosha', body: _astroBody(date, time, lat, lng, tz, id: id));
    return _asMap(decoded);
  }

  /// Ashtakvarga points — legacy `kundali/astakvarga`.
  Future<Map<String, dynamic>> astakvarga({
    int? id,
    required DateTime date,
    required String time,
    required double lat,
    required double lng,
    required double tz,
  }) async {
    final decoded = await _api
        .post('/kundali/astakvarga', body: _astroBody(date, time, lat, lng, tz, id: id));
    return _asMap(decoded);
  }

  /// Planet report — legacy `kundali/planet-report`.
  Future<Map<String, dynamic>> planetReport({
    int? id,
    required DateTime date,
    required String time,
    required double lat,
    required double lng,
    required double tz,
  }) async {
    final decoded = await _api
        .post('/kundali/planet-report', body: _astroBody(date, time, lat, lng, tz, id: id));
    return _asMap(decoded);
  }

  /// Ascendant report — legacy `kundali/ascendant-report`.
  Future<Map<String, dynamic>> ascendantReport({
    int? id,
    required DateTime date,
    required String time,
    required double lat,
    required double lng,
    required double tz,
  }) async {
    final decoded = await _api.post('/kundali/ascendant-report',
        body: _astroBody(date, time, lat, lng, tz, id: id));
    return _asMap(decoded);
  }

  /// Saved kundli detail incl. generated PDF link (legacy `Kundali/show/:id`).
  Future<Kundli> byId({required int kundaliId}) async {
    final decoded = await _api.get('/Kundali/show/$kundaliId');
    return Kundli.fromJson(_asMap(decoded));
  }

  /// Guna-milan / horoscope matching (legacy `KundaliMatching/add`).
  ///
  /// The backend fans out to the astrology engine and returns the raw match
  /// payload (percentage, ashtakoota table, manglik status, remarks). The
  /// shape varies by engine version, so the raw map is surfaced and the UI
  /// renders it defensively.
  Future<Map<String, dynamic>> matching({
    required String boyName,
    required String boyBirthDate,
    required String boyBirthTime,
    required String boyBirthPlace,
    required String girlName,
    required String girlBirthDate,
    required String girlBirthTime,
    required String girlBirthPlace,
  }) async {
    final langCode = LocaleManager.instance.currentLanguage.value.code;
    final decoded = await _api.post('/KundaliMatching/add', body: {
      'boyName': boyName,
      'boyBirthDate': boyBirthDate,
      'boyBirthTime': boyBirthTime,
      'boyBirthPlace': boyBirthPlace,
      'girlName': girlName,
      'girlBirthDate': girlBirthDate,
      'girlBirthTime': girlBirthTime,
      'girlBirthPlace': girlBirthPlace,
      'lang': langCode,
      'language': langCode,
    });
    return _asMap(decoded);
  }

  static Map<String, dynamic> _astroBody(DateTime date, String time,
      double lat, double lng, double tz, {int? id}) {
    int hour = 0;
    int min = 0;
    try {
      final isPm = time.toLowerCase().contains('pm');
      final isAm = time.toLowerCase().contains('am');
      final clean = time.replaceAll(RegExp(r'[^\d:]'), '');
      final parts = clean.split(':');
      if (parts.isNotEmpty) {
        hour = int.tryParse(parts[0]) ?? 0;
        if (isPm && hour < 12) hour += 12;
        if (isAm && hour == 12) hour = 0;
      }
      if (parts.length > 1) {
        min = int.tryParse(parts[1]) ?? 0;
      }
    } catch (_) {}

    final body = <String, dynamic>{
      'day': date.day,
      'month': date.month,
      'year': date.year,
      'hour': hour,
      'min': min,
      'lat': lat,
      'lon': lng,
      'tzone': tz,
      'lang': LocaleManager.instance.currentLanguage.value.code,
      'language': LocaleManager.instance.currentLanguage.value.code,
    };
    if (id != null && id > 0) {
      body['id'] = id;
      body['kundaliId'] = id;
    }
    return body;
  }
}
