/// AstroMall (product shop) models mirroring the legacy
/// `productModel.dart` / `astromall_product_model.dart` contracts.
library;

/// Product category from `/getproductCategory`.
class ProductCategory {
  ProductCategory({this.id, this.name, this.image, this.isActive});

  dynamic id, name, image;
  dynamic isActive;

  int get numericId => id is num ? (id as num).toInt() : (int.tryParse(id?.toString() ?? '') ?? 0);

  factory ProductCategory.fromJson(Map<String, dynamic> json) {
    final rawImg = json['categoryImage'] ?? json['image'] ?? json['category_image'];
    return ProductCategory(
      id: json['id'],
      name: json['name'],
      image: rawImg,
      isActive: json['isActive'],
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'image': image,
        'isActive': isActive,
      };
}

/// Product from `/getAstromallProduct` / `/getAstromallProductById`.
class Product {
  Product({
    this.id,
    this.categoryId,
    this.name,
    this.slug,
    this.description,
    this.images,
    this.price,
    this.discountPrice,
    this.stock,
    this.isActive,
    this.createdAt,
  });

  dynamic id, categoryId, name, slug, description;
  List<String>? images;
  dynamic price, discountPrice, stock, isActive;
  dynamic createdAt;

  int get numericId => id is num ? (id as num).toInt() : (int.tryParse(id?.toString() ?? '') ?? 0);
  int get numericCategoryId => categoryId is num ? (categoryId as num).toInt() : (int.tryParse(categoryId?.toString() ?? '') ?? 0);

  factory Product.fromJson(Map<String, dynamic> json) {
    List<String> parsedImages = [];
    if (json['images'] is List) {
      parsedImages = List<String>.from(json['images'].map((x) => x.toString()));
    } else {
      final single = json['productImage'] ?? json['image'] ?? json['product_image'];
      if (single != null && single.toString().isNotEmpty) {
        parsedImages = [single.toString()];
      }
    }

    return Product(
      id: json['id'],
      categoryId: json['categoryId'] ?? json['category_id'] ?? json['productCategoryId'],
      name: json['name'] ?? json['productName'],
      slug: json['slug'],
      description: json['description'] ?? json['longDescription'],
      images: parsedImages,
      price: json['price'] ?? json['amount'],
      discountPrice: json['discountPrice'] ?? json['discount_price'],
      stock: json['stock'],
      isActive: json['isActive'],
      createdAt: json['created_at'] ?? json['createdAt'],
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'categoryId': categoryId,
        'name': name,
        'slug': slug,
        'description': description,
        'images': images,
        'price': price,
        'discountPrice': discountPrice,
        'stock': stock,
        'isActive': isActive,
      };

  /// Effective display price (discounted when available).
  double get displayPrice {
    final v = (discountPrice == null ||
            discountPrice == '' ||
            double.tryParse(discountPrice.toString()) == null)
        ? price
        : discountPrice;
    return double.tryParse(v?.toString() ?? '') ?? 0.0;
  }
}
