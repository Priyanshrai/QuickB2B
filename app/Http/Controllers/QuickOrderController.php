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
                    'id'        => $node['id'] ?? '',
                    'title'     => $node['title'] ?? '',
                    'sku'       => $firstVariant['sku'] ?? '',
                    'price'     => $firstVariant['price'] ?? '0.00',
                    'inventory' => 999, // placeholder — needs read_inventory scope + inventory query
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
     * Add items to cart via Storefront API.
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

        // TODO: Implement cart creation via Storefront API
        // For now, build a cart permalink redirect
        $variantParams = [];
        foreach ($items as $variantId => $qty) {
            $variantParams[] = $variantId . ':' . $qty;
        }

        $storeUrl = 'https://' . $shop->getDomain()->toNative();
        $redirect = $storeUrl . '/cart/' . implode(',', $variantParams);

        return response()->json(['redirect' => $redirect]);
    }
}
