<?php

namespace App\Http\Controllers;

use App\Services\ShopifyGraphQL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class QuickOrderController extends Controller
{
    /**
     * GET /api/quick-order/products
     * Return product list for the storefront quick order page.
     */
    public function products(Request $request)
    {
        $shop = Auth::user();
        if (!$shop) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $edges = ShopifyGraphQL::fetchProducts($shop);

            $products = array_map(function ($edge) {
                $node = $edge['node'] ?? [];
                $variants = $node['variants']['edges'] ?? [];
                $firstVariant = $variants[0]['node'] ?? [];

                return [
                    'id'         => $node['id'] ?? '',
                    'variant_id' => $firstVariant['id'] ?? '',  // gid://shopify/ProductVariant/...
                    'title'      => $node['title'] ?? '',
                    'sku'        => $firstVariant['sku'] ?? '',
                    'price'      => $firstVariant['price'] ?? '0.00',
                    'inventory'  => 999, // placeholder — needs read_inventory scope + inventory query
                ];
            }, $edges);

            return response()->json(['products' => $products]);
        } catch (\Throwable $e) {
            Log::error('QuickB2B: fetch products failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Could not load products'], 500);
        }
    }

    /**
     * POST /api/quick-order/add-bulk
     * Create a cart via Storefront API and return the checkout URL.
     */
    public function addBulk(Request $request)
    {
        $shop = Auth::user();
        if (!$shop) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $items = $request->input('items', []);
        if (empty($items)) {
            return response()->json(['error' => 'No items provided'], 400);
        }

        // ── Try Storefront Cart API first (if token configured) ──
        $storefrontToken = env('SHOPIFY_STOREFRONT_ACCESS_TOKEN');
        if ($storefrontToken) {
            $lines = [];
            foreach ($items as $variantId => $qty) {
                $lines[] = ['merchandiseId' => $variantId, 'quantity' => (int) $qty];
            }
            $result = \App\Services\StorefrontGraphQL::createCart(
                $shop->getDomain()->toNative(), $storefrontToken, $lines
            );
            if (!empty($result['cart']['checkoutUrl'])) {
                return response()->json(['redirect' => $result['cart']['checkoutUrl']]);
            }
        }

        // ── Cart permalink (always works, zero config) ──
        $variantParams = [];
        foreach ($items as $variantId => $qty) {
            $variantParams[] = basename($variantId) . ':' . $qty;
        }
        $redirect = 'https://' . $shop->getDomain()->toNative() . '/cart/' . implode(',', $variantParams) . '?storefront=true';
        
        Log::info('QuickB2B: Cart permalink generated', [
            'items_count' => count($items),
            'url' => $redirect,
            'items' => $items,
        ]);
        
        return response()->json(['redirect' => $redirect]);
    }
}
