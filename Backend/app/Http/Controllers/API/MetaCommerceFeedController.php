<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

/**
 * Meta / Facebook & Instagram Commerce Catalogue Feed Generator.
 * Serves real-time XML/RSS 2.0 and CSV feeds adhering to Meta Commerce Specification.
 * Used by Meta Commerce Manager to sync Pujas and AstroMall products for
 * Facebook Shop, Instagram Shopping, and Dynamic Product Ads.
 */
class MetaCommerceFeedController extends Controller
{
    /**
     * XML Feed for Meta Commerce Manager / Google Merchant.
     * GET /api/feeds/meta-catalog.xml
     */
    public function xmlFeed(Request $request)
    {
        $baseUrl = 'https://onlinepuja.live';

        // 1. Fetch active Pujas
        $pujas = DB::table('pujas')
            ->where('puja_status', 1)
            ->where('isAdminApproved', 'Approved')
            ->get();

        // 2. Fetch active AstroMall products
        $products = DB::table('astromall_products')
            ->where('isActive', 1)
            ->where('isDelete', 0)
            ->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">' . "\n";
        $xml .= "  <channel>\n";
        $xml .= "    <title><![CDATA[OnlinePuja.live - Sacred Vedic Pujas & AstroMall]]></title>\n";
        $xml .= "    <link><![CDATA[" . $baseUrl . "]]></link>\n";
        $xml .= "    <description><![CDATA[Authentic Vedic Pujas from Varanasi, Kashi Vishwanath, Ujjain, Haridwar and Energized Spiritual Items.]]></description>\n";

        // Add Pujas to feed
        foreach ($pujas as $puja) {
            $desc = strip_tags($puja->long_description ?: ($puja->puja_subtitle ?: $puja->puja_title));
            $desc = mb_substr($desc, 0, 950);
            $slug = $puja->slug ?: 'puja-' . $puja->id;
            $link = $baseUrl . '/puja/' . $slug;
            $imgUrl = $this->resolveImageUrl($puja->puja_images);
            $price = number_format($puja->puja_price ?: 501.0, 2, '.', '') . ' INR';
            $place = $puja->puja_place ?: 'Varanasi';

            $xml .= "    <item>\n";
            $xml .= "      <g:id>puja_" . $puja->id . "</g:id>\n";
            $xml .= "      <g:title><![CDATA[" . $puja->puja_title . "]]></g:title>\n";
            $xml .= "      <g:description><![CDATA[" . $desc . "]]></g:description>\n";
            $xml .= "      <g:link><![CDATA[" . $link . "]]></g:link>\n";
            $xml .= "      <g:image_link><![CDATA[" . $imgUrl . "]]></g:image_link>\n";
            $xml .= "      <g:price>" . $price . "</g:price>\n";
            $xml .= "      <g:availability>in_stock</g:availability>\n";
            $xml .= "      <g:condition>new</g:condition>\n";
            $xml .= "      <g:brand>OnlinePuja.live</g:brand>\n";
            $xml .= "      <g:google_product_category><![CDATA[Religious & Ceremonial]]></g:google_product_category>\n";
            $xml .= "      <g:custom_label_0><![CDATA[Vedic Puja Ritual]]></g:custom_label_0>\n";
            $xml .= "      <g:custom_label_1><![CDATA[" . $place . "]]></g:custom_label_1>\n";
            $xml .= "    </item>\n";
        }

        // Add AstroMall Products to feed
        foreach ($products as $prod) {
            $desc = strip_tags($prod->description ?: ($prod->features ?: $prod->name));
            $desc = mb_substr($desc, 0, 950);
            $slug = $prod->slug ?: 'product-' . $prod->id;
            $link = $baseUrl . '/product/' . $slug;
            $imgUrl = $this->resolveImageUrl($prod->productImage);
            $price = number_format($prod->amount ?: 500.0, 2, '.', '') . ' INR';

            $xml .= "    <item>\n";
            $xml .= "      <g:id>prod_" . $prod->id . "</g:id>\n";
            $xml .= "      <g:title><![CDATA[" . $prod->name . "]]></g:title>\n";
            $xml .= "      <g:description><![CDATA[" . $desc . "]]></g:description>\n";
            $xml .= "      <g:link><![CDATA[" . $link . "]]></g:link>\n";
            $xml .= "      <g:image_link><![CDATA[" . $imgUrl . "]]></g:image_link>\n";
            $xml .= "      <g:price>" . $price . "</g:price>\n";
            $xml .= "      <g:availability>in_stock</g:availability>\n";
            $xml .= "      <g:condition>new</g:condition>\n";
            $xml .= "      <g:brand>OnlinePuja.live</g:brand>\n";
            $xml .= "      <g:google_product_category><![CDATA[Religious & Ceremonial]]></g:google_product_category>\n";
            $xml .= "      <g:custom_label_0><![CDATA[AstroMall Sacred Item]]></g:custom_label_0>\n";
            $xml .= "    </item>\n";
        }

        $xml .= "  </channel>\n";
        $xml .= "</rss>";

        return Response::make($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * CSV Feed for Meta Commerce Manager direct spreadsheet upload.
     * GET /api/feeds/meta-catalog.csv
     */
    public function csvFeed(Request $request)
    {
        $baseUrl = 'https://onlinepuja.live';
        $pujas = DB::table('pujas')->where('puja_status', 1)->where('isAdminApproved', 'Approved')->get();
        $products = DB::table('astromall_products')->where('isActive', 1)->where('isDelete', 0)->get();

        $headers = [
            'id', 'title', 'description', 'availability', 'condition',
            'price', 'link', 'image_link', 'brand', 'google_product_category', 'custom_label_0'
        ];

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);

        foreach ($pujas as $p) {
            $desc = mb_substr(strip_tags($p->long_description ?: ($p->puja_subtitle ?: $p->puja_title)), 0, 950);
            $slug = $p->slug ?: 'puja-' . $p->id;
            fputcsv($handle, [
                'puja_' . $p->id,
                $p->puja_title,
                $desc,
                'in stock',
                'new',
                number_format($p->puja_price ?: 501.0, 2, '.', '') . ' INR',
                $baseUrl . '/puja/' . $slug,
                $this->resolveImageUrl($p->puja_images),
                'OnlinePuja.live',
                'Religious & Ceremonial',
                'Vedic Puja Ritual'
            ]);
        }

        foreach ($products as $pr) {
            $desc = mb_substr(strip_tags($pr->description ?: ($pr->features ?: $pr->name)), 0, 950);
            $slug = $pr->slug ?: 'product-' . $pr->id;
            fputcsv($handle, [
                'prod_' . $pr->id,
                $pr->name,
                $desc,
                'in stock',
                'new',
                number_format($pr->amount ?: 500.0, 2, '.', '') . ' INR',
                $baseUrl . '/product/' . $slug,
                $this->resolveImageUrl($pr->productImage),
                'OnlinePuja.live',
                'Religious & Ceremonial',
                'AstroMall Sacred Item'
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="meta-catalog-onlinepuja.csv"',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    private function resolveImageUrl($raw): string
    {
        $baseUrl = 'https://onlinepuja.live';
        $fallback = $baseUrl . '/storage/brain_media/brain_0tjJgnxtW92MnqLF8b_1200x624.png';

        if (empty($raw)) {
            return $fallback;
        }

        $path = $raw;
        if (str_starts_with($raw, '[') && str_ends_with($raw, ']')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && !empty($decoded[0])) {
                $path = $decoded[0];
            }
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return str_replace('http://', 'https://', $path);
        }

        $clean = preg_replace('/^public\//', '', $path);
        $clean = ltrim($clean, '/');

        return $baseUrl . '/' . $clean;
    }
}
