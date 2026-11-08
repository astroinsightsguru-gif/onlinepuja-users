<?php

namespace App\Http\Controllers\frontend;

use App\Http\Controllers\Controller;
use App\Models\AdminModel\SystemFlag;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Session\Session;

class ProductController extends Controller
{
    public function getproducts(Request $request)
    {
        try {
            $categories = \App\Models\AdminModel\ProductCategory::where('isActive', 1)->get();
            $getproductCategory = ['status' => 200, 'recordList' => $categories];
        } catch (\Throwable $e) {
            $getproductCategory = ['status' => 200, 'recordList' => []];
        }

        // Determine the current page from the request
        $productCategoryId = (int) $request->productCategoryId;
        $searchTerm = $request->input('s');

        $getsystemflag = SystemFlag::all();
        $currency = $getsystemflag->where('name', 'currencySymbol')->first();

        $productlist = Product::query();
        if ($request->productCategoryId) {
            $productlist->where('productCategoryId', '=', $request->productCategoryId);
        }
        $productlist = $productlist
            ->where('isActive', 1)
            ->orderBy('created_at', 'desc')
            ->paginate(8);

        return view('frontend.pages.products', [
            'getproductCategory' => $getproductCategory,
            'productCategoryId' => $productCategoryId,
            'searchTerm' => $searchTerm,
            'currency' => $currency,
            'productlist' => $productlist,
        ]);
    }

    // -----------------------------------------------------------------------------------------------------------------------------------
    public function getproductDetails(Request $request)
    {
        try {
            $res = app(\App\Http\Controllers\API\User\AstromallProductController::class)->getAstromallProduct(new Request());
            $getAstromallProduct = $res->getData(true);
        } catch (\Throwable $e) {
            $getAstromallProduct = ['status' => 200, 'recordList' => []];
        }

        // if(isset($request->ref))
        //     $cookie = cookie('productref_'.$request->id, $request->ref, 1440);
        $productdet = Product::where('slug', $request->slug)->first();
        if (isset($request->ref)) {
            setcookie('productref_' . $productdet->id, $request->ref, time() + (1440 * 60), '/');
        }

        $getproductdetails = Product::join('product_categories', 'product_categories.id', '=', 'astromall_products.productCategoryId')
            ->where('astromall_products.slug', '=', $request->slug)
            ->select('astromall_products.*', 'product_categories.name as productCategory')
            ->first();
        // dd($getproductdetails);
        $productfaq = DB::table('product_details')->where('astromallProductId', $getproductdetails->id)->get();
        $getsystemflag = SystemFlag::all();
        $currency = $getsystemflag->where('name', 'currencySymbol')->first();

        $productlist = Product::query()->where('isActive', 1)->where('id', '!=', $request->id)->orderBy('created_at', 'desc')->take(4)->get();

        return view('frontend.pages.product-details', [
            'getproductdetails' => $getproductdetails,
            'getAstromallProduct' => $getAstromallProduct,
            'currency' => $currency,
            'productlist' => $productlist,
            'productfaq' => $productfaq,
        ]);
    }

    public function checkout(Request $request)
    {
        if (!authcheck())
            return redirect()->route('front.home');

        $userId = authcheck()['id'];

        try {
            $res = app(\App\Http\Controllers\API\User\AstromallProductController::class)->getAstromallProduct(new Request());
            $getAstromallProduct = $res->getData(true);
        } catch (\Throwable $e) {
            $getAstromallProduct = ['status' => 200, 'recordList' => []];
        }

        try {
            $res = app(\App\Http\Controllers\API\User\OrderController::class)->getOrderAddress(new Request(['userId' => $userId]));
            $getOrderAddress = $res->getData(true);
        } catch (\Throwable $e) {
            $getOrderAddress = ['status' => 200, 'recordList' => []];
        }

        $getproductdetails = Product::join('product_categories', 'product_categories.id', '=', 'astromall_products.productCategoryId')
            ->where('astromall_products.id', '=', $request->id)
            ->select('astromall_products.*', 'product_categories.name as productCategory')
            ->first();

        $getsystemflag = SystemFlag::all();
        $gstvalue = $getsystemflag->where('name', 'Gst')->first();

        $currency = $getsystemflag->where('name', 'currencySymbol')->first();

        return view('frontend.pages.checkout', [
            'getproductdetails' => $getproductdetails,
            'getAstromallProduct' => $getAstromallProduct,
            'getOrderAddress' => $getOrderAddress,
            'gstvalue' => $gstvalue,
            'currency' => $currency,
        ]);
    }

    public function myOrders()
    {
        if (!authcheck())
            return redirect()->route('front.home');

        $userId = authcheck()['id'];

        try {
            $res = app(\App\Http\Controllers\API\Astrologer\AstrologerController::class)->getUserById(new Request(['userId' => $userId]));
            $getUserById = $res->getData(true);
        } catch (\Throwable $e) {
            $getUserById = ['status' => 200, 'recordList' => []];
        }

        $currency = SystemFlag::where('name', 'currencySymbol')->first();

        return view('frontend.pages.my-orders', [
            'getUserById' => $getUserById,
            'currency' => $currency
        ]);
    }
}
