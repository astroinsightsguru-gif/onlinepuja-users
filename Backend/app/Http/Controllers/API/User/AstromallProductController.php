<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Models\UserModel\AstromallProduct;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AstromallProductController extends Controller
{

    //Get all astromall product
    public function getAstromallProduct(Request $req)
    {
        try {
            $astromallProduct = AstromallProduct::query();
            if ($req->productCategoryId) {
                $astromallProduct->where('productCategoryId', '=', $req->productCategoryId);
            }

            $astromallProduct = $astromallProduct->orderBy('id', 'DESC');
            if ($req->startIndex >= 0 && $req->fetchRecord) {
                $astromallProduct->skip($req->startIndex);
                $astromallProduct->take($req->fetchRecord);
            }

            $astromallProductCount = DB::table('astromall_products')->count();


            return response()->json([
                'recordList' => $astromallProduct->get(),
                'status' => 200,
                'totalRecords' => $astromallProductCount,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function getAstromallProductForApp(Request $req)
    {
        try {
            $astromallProduct = AstromallProduct::query();

            if ($req->productCategoryId) {
                $astromallProduct->where('productCategoryId', '=', $req->productCategoryId);
            }

            $astromallProduct->where('isActive', '=', true);
            $astromallProduct->where('isDelete', '=', false);

            if ($s = $req->input('s')) {
                $astromallProduct->whereRaw("name LIKE '%" . $s . "%' ");
            }

            if ($req->startIndex >= 0 && $req->fetchRecord) {
                $astromallProduct->skip($req->startIndex);
                $astromallProduct->take($req->fetchRecord);
            }

            $astromallProductCount = AstromallProduct::query();
            if ($req->productCategoryId) {
                $astromallProductCount->where('productCategoryId', '=', $req->productCategoryId);
            }
            $astromallProductCount->where('isActive', '=', true);
            $astromallProductCount->where('isDelete', '=', false);

            $recordList = $astromallProduct->get();

            // Convert productImage path to full asset URL or external link
            foreach ($recordList as $product) {
                if (!empty($product->productImage)) {
                    if (Str::startsWith($product->productImage, ['http://', 'https://'])) {
                        // already full URL (external storage)
                        $product->productImage = $product->productImage;
                    } else {
                        // local image path, convert using asset()
                        $product->productImage = asset($product->productImage);
                    }
                } else {
                    // default fallback image (verified asset)
                    $product->productImage = asset('frontend/homeimage/shareproduct.png');
                }
            }

            return response()->json([
                'recordList' => $recordList,
                'status' => 200,
                'totalRecords' => $astromallProductCount->count(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }


    public function getAstromallProductById(Request $req)
    {
        try {
            if (!$req->id) {
                return response()->json([
                    'error' => false,
                    'message' => 'Product is required',
                    'status' => 404,
                ], 404);
            }
            $productDetail = AstromallProduct::join('product_categories', 'product_categories.id', '=', 'astromall_products.productCategoryId')
                ->where('astromall_products.id', '=', $req->id)
                ->select('astromall_products.*', 'product_categories.name as productCategory')
                ->get();
            // :star: Image Path Conversion Function
            $convertToAsset = function ($value) {
                if (empty($value)) return null;
                if (Str::startsWith($value, ['http://', 'https://'])) {
                    return $value;
                }
                return asset($value);
            };
            // Convert astrologer image fields for each astrologer
            $fieldsToConvert = ['profileImage', 'aadhar_card', 'pan_card', 'certificate', 'astro_video'];
          
            $productDetail[0]->productImage = $convertToAsset($productDetail[0]->productImage);
            $questionAnswer = DB::Table('product_details')
                ->where('astromallProductId', '=', $req->id)
                ->where('isActive', '=', 1)
                ->select('question', 'answer', 'id')
                ->get();
            $productDetail[0]->questionAnswer = $questionAnswer;

            $productReview = DB::table('user_reviews')
                ->join('users', 'users.id', '=', 'user_reviews.userId')
                ->where('astromallProductId', '=', $req->id)
                ->select('user_reviews.*', 'users.name as userName', 'users.profile')
                ->get();
            $productDetail[0]->productReview = $productReview;
            return response()->json([
                'recordList' => $productDetail,
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function searchInProductCategory(Request $req)
    {
        try {

            $product = AstromallProduct::query();
            if ($req->productCategoryId) {
                $product = $product->where('productCategoryId', '=', $req->productCategoryId);
                if ($req->searchString) {
                    $product = $product->whereRaw(sql: "name LIKE '%" . $req->searchString . "%' ");
                }
            }
            $product = $product->get();
            return response()->json([
                'recordList' => $product,
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }
}
