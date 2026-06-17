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

        $shopDomain = $shop->getDomain()->toNative();
        $filePath = "quickb2b/{$shopDomain}/products.json";

        if (!\Illuminate\Support\Facades\Storage::exists($filePath)) {
            \App\Jobs\RefreshProductCacheJob::dispatch($shopDomain);
            return response()->json(['products' => [], 'hasMore' => false, 'source' => 'waiting']);
        }

        // Read all products from file
        $allProducts = json_decode(\Illuminate\Support\Facades\Storage::get($filePath), true) ?: [];

        // Server-side search
        $q = trim($request->query('q', ''));
        if ($q !== '') {
            $qLower = mb_strtolower($q);
            $allProducts = array_values(array_filter($allProducts, function ($p) use ($qLower) {
                return str_contains(mb_strtolower($p['title'] ?? ''), $qLower)
                    || str_contains(mb_strtolower($p['sku'] ?? ''), $qLower)
                    || str_contains(mb_strtolower($p['variant_title'] ?? ''), $qLower);
            }));
        }

        $total = count($allProducts);

        // Paginate: 100 per page
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 100;
        $offset = ($page - 1) * $perPage;
        $products = array_slice($allProducts, $offset, $perPage);

        return response()->json([
            'products'   => $products,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'hasMore'    => $offset + $perPage < $total,
            'source'     => 'bulk',
        ]);
    }

    /**
     * POST /api/quick-order/add-all
     * Add ALL products (or matching search) to cart directly.
     */
    public function addAll(Request $request)
    {
        $shop = Auth::user();
        if (!$shop) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $shopDomain = $shop->getDomain()->toNative();
        $filePath = "quickb2b/{$shopDomain}/products.json";

        if (!\Illuminate\Support\Facades\Storage::exists($filePath)) {
            return response()->json(['error' => 'No products cached yet'], 503);
        }

        $allProducts = json_decode(\Illuminate\Support\Facades\Storage::get($filePath), true) ?: [];

        // Optional search filter
        $q = trim($request->input('q', ''));
        if ($q !== '') {
            $qLower = mb_strtolower($q);
            $allProducts = array_values(array_filter($allProducts, function ($p) use ($qLower) {
                return str_contains(mb_strtolower($p['title'] ?? ''), $qLower)
                    || str_contains(mb_strtolower($p['sku'] ?? ''), $qLower);
            }));
        }

        // Return variant IDs for client-side AJAX cart batching
        $variantIds = [];
        foreach ($allProducts as $p) {
            if (!empty($p['variant_id'])) {
                $variantIds[] = basename($p['variant_id']);
            }
        }

        return response()->json(['variants' => $variantIds, 'count' => count($variantIds)]);
    }

    /**
     * GET /api/quick-order/products/status
     * Progress of background product cache refresh.
     */
    public function productsStatus()
    {
        $shop = Auth::user();
        if (!$shop) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $shopDomain = $shop->getDomain()->toNative();
        $progressPath = "quickb2b/{$shopDomain}/progress.json";

        if (!\Illuminate\Support\Facades\Storage::exists($progressPath)) {
            return response()->json(['status' => 'idle', 'percent' => 0]);
        }

        return response()->json(
            json_decode(\Illuminate\Support\Facades\Storage::get($progressPath), true)
        );
    }

    /**
     * POST /api/quick-order/add-bulk
     * Build a cart permalink and redirect the customer.
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

        // Return variants with quantities for client-side AJAX cart
        $variants = [];
        foreach ($items as $variantId => $qty) {
            $variants[] = [
                'id'  => basename($variantId),
                'qty' => (int) $qty,
            ];
        }

        return response()->json(['variants' => $variants]);
    }
}
