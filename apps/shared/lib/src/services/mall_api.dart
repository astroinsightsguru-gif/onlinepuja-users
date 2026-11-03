import '../api/api_client.dart';
import '../models/astromall.dart';
import '../models/misc.dart';

/// AstroMall (e-commerce shop) endpoints mirroring the legacy Customer-app:
/// `/getproductCategory`, `/getAstromallProduct`, `/getAstromallProductById`,
/// `/getProductRecommend`, `/getOrderAddress`, `/userOrder/add`.
class MallApi {
  MallApi._();
  static final MallApi instance = MallApi._();

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

  /// Product categories.
  Future<List<ProductCategory>> categories() async {
    final decoded = await _api.post('/getproductCategory');
    return _asMapList(decoded).map(ProductCategory.fromJson).toList();
  }

  /// Product list, optionally filtered by category.
  Future<List<Product>> list({dynamic categoryId}) async {
    final decoded = await _api.post('/getAstromallProduct', body: {
      'productCategoryId': ?categoryId,
      'categoryId': ?categoryId,
    });
    return _asMapList(decoded).map(Product.fromJson).toList();
  }

  /// Single product detail.
  Future<Product> byId({required dynamic productId}) async {
    final decoded = await _api
        .post('/getAstromallProductById', body: {'productId': productId});
    final maps = _asMapList(decoded);
    if (maps.isEmpty) throw ApiException('Product not found.');
    return Product.fromJson(maps.first);
  }

  /// Recommended products.
  Future<List<Product>> recommended() async {
    final decoded = await _api.post('/getProductRecommend');
    return _asMapList(decoded).map(Product.fromJson).toList();
  }

  /// Saved delivery addresses.
  Future<List<OrderAddress>> addresses() async {
    final decoded = await _api.post('/getOrderAddress');
    return _asMapList(decoded).map(OrderAddress.fromJson).toList();
  }

  /// Place a mall product order (legacy `userOrder/add`). The backend deducts
  /// the wallet balance, so the wallet must cover [totalPayable].
  Future<Map<String, dynamic>> placeOrder({
    required int userId,
    required dynamic productId,
    required dynamic categoryId,
    required dynamic addressId,
    required double payableAmount,
    required double totalPayable,
    double gstPercent = 0,
    String paymentMethod = 'wallet',
  }) async {
    final pId = productId is num
        ? productId.toInt()
        : (int.tryParse(productId.toString()) ?? productId);
    final cId = categoryId is num
        ? categoryId.toInt()
        : (int.tryParse(categoryId.toString()) ?? categoryId);
    final aId = addressId is num
        ? addressId.toInt()
        : (int.tryParse(addressId.toString()) ?? addressId);

    final decoded = await _api.post('/userOrder/add', body: {
      'userId': userId,
      'productId': pId,
      'productCategoryId': cId,
      'orderAddressId': aId,
      'payableAmount': payableAmount,
      'gstPercent': gstPercent,
      'totalPayable': totalPayable,
      'paymentMethod': paymentMethod,
    });
    if (decoded is Map<String, dynamic>) {
      final rl = decoded['recordList'];
      if (rl is Map<String, dynamic>) return rl;
      return decoded;
    }
    return const {};
  }
}
