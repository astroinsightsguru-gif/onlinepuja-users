import 'dart:convert';
import 'package:http/http.dart' as http;

import '../env.dart';
import '../l10n/app_strings.dart';

/// Thrown for any non-2xx API response or network failure.
class ApiException implements Exception {
  ApiException(this.message, {this.statusCode, this.errors});

  final String message;
  final int? statusCode;

  /// Field errors returned by the backend (`{"error": {...}}` on 400).
  final Map<String, dynamic>? errors;

  @override
  String toString() => message;
}

/// Uniform API result: mirrors the legacy `getAPIResult` pattern where every
/// endpoint responds with `{"status": 200/400, "recordList": ...}`.
class ApiResult<T> {
  ApiResult({required this.status, this.recordList, this.body});

  final int status;
  final T? recordList;

  /// Raw decoded response for endpoints that return extra keys
  /// (e.g. login: `token`, `token_type`).
  final Map<String, dynamic>? body;

  bool get isOk => status == 200;
}

/// Thin HTTP client on top of package:http with the legacy auth-header
/// convention (`Authorization: <token_type> <token>` from SharedPreferences).
class ApiClient {
  ApiClient._();
  static final ApiClient instance = ApiClient._();

  String? Function() tokenResolver = () => null;
  String? Function() tokenTypeResolver = () => null;

  String _resolveUrl(String path) {
    if (path.startsWith('http://') || path.startsWith('https://')) return path;
    final cleanPath = path.startsWith('/') ? path : '/$path';
    if (cleanPath.startsWith('/api/') && Env.apiBase.endsWith('/api')) {
      final baseWithoutApi = Env.apiBase.substring(0, Env.apiBase.length - 4);
      return '$baseWithoutApi$cleanPath';
    }
    return '${Env.apiBase}$cleanPath';
  }

  Map<String, String> headers({bool auth = true}) {
    final langCode = LocaleManager.instance.currentLanguage.value.code;
    final h = <String, String>{
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Accept-Language': langCode,
      'X-App-Lang': langCode,
    };
    if (auth) {
      final token = tokenResolver();
      if (token != null && token.isNotEmpty) {
        final type = tokenTypeResolver() ?? 'Bearer';
        h['Authorization'] = '$type $token';
      }
    }
    return h;
  }

  /// POST and decode JSON; throws [ApiException] on failure.
  Future<dynamic> post(
    String path, {
    Map<String, dynamic>? body,
    bool auth = true,
  }) async {
    final url = _resolveUrl(path);
    final http.Response response;
    try {
      response = await http
          .post(Uri.parse(url), headers: headers(auth: auth),
              body: body == null ? null : json.encode(body))
          .timeout(const Duration(seconds: 30));
    } catch (e) {
      throw ApiException('Network error, please try again.', errors: {'_': e.toString()});
    }
    return _handle(response);
  }

  /// GET and decode JSON; throws [ApiException] on failure.
  Future<dynamic> get(
    String path, {
    Map<String, dynamic>? query,
    Map<String, dynamic>? queryParameters,
    bool auth = true,
  }) async {
    final base = _resolveUrl(path);
    final q = query ?? queryParameters;
    final uri = q != null && q.isNotEmpty
        ? Uri.parse(base).replace(
            queryParameters: q.map((k, v) => MapEntry(k, v?.toString() ?? '')),
          )
        : Uri.parse(base);
    final http.Response response;
    try {
      response = await http
          .get(uri, headers: headers(auth: auth))
          .timeout(const Duration(seconds: 30));
    } catch (e) {
      throw ApiException('Network error, please try again.', errors: {'_': e.toString()});
    }
    return _handle(response);
  }

  dynamic _handle(http.Response response) {
    dynamic decoded;
    if (response.body.isNotEmpty) {
      try {
        decoded = json.decode(response.body);
      } catch (_) {
        decoded = response.body;
      }
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      if (decoded is Map<String, dynamic>) {
        final status = decoded['status'];
        if (status != null && status != 200 && status != '200' && status != true) {
          final msg = decoded['message'] ?? decoded['error'] ?? 'API error';
          final errors = decoded['error'] is Map<String, dynamic>
              ? decoded['error'] as Map<String, dynamic>
              : null;
          throw ApiException(msg.toString(),
              statusCode: int.tryParse(status.toString()) ?? 400,
              errors: errors);
        }
      }
      return decoded;
    }

    String message = 'Request failed (${response.statusCode})';
    Map<String, dynamic>? errors;
    if (decoded is Map<String, dynamic>) {
      if (decoded['message'] != null) message = decoded['message'].toString();
      if (decoded['error'] is Map<String, dynamic>) {
        errors = decoded['error'] as Map<String, dynamic>;
      } else if (decoded['error'] != null) {
        message = decoded['error'].toString();
      }
    }
    throw ApiException(message, statusCode: response.statusCode, errors: errors);
  }
}
