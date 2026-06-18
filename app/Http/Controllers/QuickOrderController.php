<?php

namespace App\Http\Controllers;

use App\Jobs\CreateDraftOrderJob;
use App\Jobs\RefreshProductCacheJob;
use App\Services\ShopifyGraphQL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

        if (!Storage::exists($filePath)) {
            RefreshProductCacheJob::dispatch($shopDomain);
            return response()->json(['products' => [], 'hasMore' => false, 'source' => 'waiting']);
        }

        // Read all products from file
        $allProducts = json_decode(Storage::get($filePath), true) ?: [];

        // Server-side search with optional filter type
        $q = trim($request->query('q', ''));
        $filter = $request->query('filter', 'all');
        $allProducts = $this->filterProductsBySearch($allProducts, $q, $filter);

        $total = count($allProducts);

        // Paginate: user-selectable per_page (10-500, default 50)
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(500, max(10, (int) $request->query('per_page', 50)));
        $offset = ($page - 1) * $perPage;
        $totalPages = (int) ceil($total / $perPage);
        $products = array_slice($allProducts, $offset, $perPage);

        return response()->json([
            'products'   => $products,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => $totalPages,
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

        if (!Storage::exists($filePath)) {
            return response()->json(['error' => 'No products cached yet'], 503);
        }

        $allProducts = json_decode(Storage::get($filePath), true) ?: [];

        // Optional search filter
        $q = trim($request->input('q', ''));
        $filter = $request->input('filter', 'all');
        $allProducts = $this->filterProductsBySearch($allProducts, $q, $filter);

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
     * POST /api/quick-order/draft-order
     * Create a draft order (works on password-protected stores, B2B ready).
     */
    public function draftOrder(Request $request)
    {
        $shop = Auth::user();
        if (!$shop) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $items = $request->input('items', []);
        $email = filter_var($request->input('email'), FILTER_VALIDATE_EMAIL) ?: null;

        if ($email === null && $request->input('email')) {
            return response()->json(['error' => 'Invalid email address provided.'], 422);
        }

        // Normalize boolean: accept JSON bool, string "true"/"false", or 1/0
        $filterOos = filter_var($request->input('filter_oos', true), FILTER_VALIDATE_BOOL);
        $shopDomain = $shop->getDomain()->toNative();

        if (empty($items)) {
            return response()->json(['error' => 'No items provided'], 400);
        }

        // ── Server-side OOS filter against JSON catalog ──────────
        $filePath = "quickb2b/{$shopDomain}/products.json";
        $catalog = Storage::exists($filePath)
            ? json_decode(Storage::get($filePath), true) ?: []
            : [];

        // Build lookup: variant_id (last segment) → inventory data
        $stockMap = [];
        foreach ($catalog as $p) {
            if (!empty($p['variant_id'])) {
                $stockMap[basename($p['variant_id'])] = [
                    'tracked' => !empty($p['inventory_tracked']),
                    'qty'     => (int) ($p['inventory'] ?? 0),
                ];
            }
        }

        // ── Server-side OOS filter (only if user chose in-stock only) ──
        $oosCount = 0;
        if ($filterOos) {
            // Filter: keep only in-stock (tracked+qty>0) or unlimited (untracked)
            $filtered = [];
            foreach ($items as $item) {
                $vid = basename($item['id'] ?? '');
                $stock = $stockMap[$vid] ?? null;

                if (!$stock) {
                    $filtered[] = $item;
                } elseif ($stock['tracked'] && $stock['qty'] <= 0) {
                    $oosCount++;
                } else {
                    $filtered[] = $item;
                }
            }

            if (empty($filtered)) {
                return response()->json([
                    'error' => 'All items were out of stock or unavailable.',
                    'oos_skipped' => $oosCount,
                ], 422);
            }

            $items = $filtered;
        }
        // ── End OOS filter ────────────────────────────────────────

        // Large orders → background job (Shopify limit: 500 line items per draft order)
        $draftLimit = 499;
        if (count($items) > $draftLimit) {
            $orders = (int) ceil(count($items) / $draftLimit);
            CreateDraftOrderJob::dispatch($shopDomain, $items, $email);

            return response()->json([
                'queued' => true,
                'orders' => $orders,
                'oos_skipped' => $oosCount,
                'filter_oos' => $filterOos,
                'message' => "Large order: {$orders} draft orders processing. "
                    . ($filterOos && $oosCount ? "{$oosCount} OOS items skipped. " : "")
                    . (!$filterOos ? "Includes backorder items. " : "")
                    . "Invoice(s) will be sent to {$email}.",
            ]);
        }

        // Small orders → inline (fast)
        try {
            $lineItems = array_map(fn($item) => [
                'variantId' => $item['id'],
                'quantity'  => (int) $item['qty'],
            ], $items);

            $result = ShopifyGraphQL::createDraftOrder($shop, $lineItems, $email);

            if (!$result || !empty($result['userErrors'])) {
                Log::error('QuickB2B: Draft order errors', ['errors' => $result['userErrors'] ?? 'GraphQL returned null']);
                return response()->json(['error' => 'Could not create order'], 500);
            }

            $draftOrder = $result['draftOrder'] ?? [];
            $invoiceUrl = null;
            if (!empty($draftOrder['id'])) {
                $invoiceUrl = ShopifyGraphQL::sendDraftOrderInvoice($shop, $draftOrder['id']);
            }

            return response()->json([
                'draft_order' => $draftOrder['name'] ?? 'Draft',
                'invoice_url' => $invoiceUrl,
                'oos_skipped' => $oosCount,
                'filter_oos' => $filterOos,
                'message' => ($filterOos && $oosCount ? "{$oosCount} OOS skipped. " : "")
                           . (!$filterOos ? "Includes backorder. " : "")
                           . ($invoiceUrl ? "Invoice sent to {$email}!" : 'Order created.'),
            ]);
        } catch (\Throwable $e) {
            Log::error('QuickB2B: Draft order failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Could not create draft order'], 500);
        }
    }

    /**
     * GET /api/quick-order/draft-order/status
     * Progress of background draft order creation.
     */
    public function draftOrderStatus()
    {
        $shop = Auth::user();
        if (!$shop) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $path = "quickb2b/{$shop->getDomain()->toNative()}/draft_order_progress.json";

        if (!Storage::exists($path)) {
            return response()->json(['status' => 'idle']);
        }

        return response()->json(
            json_decode(Storage::get($path), true)
        );
    }

    /**
     * GET /apps/quick-order/api/products/status
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

        if (!Storage::exists($progressPath)) {
            return response()->json(['status' => 'idle', 'percent' => 0]);
        }

        return response()->json(
            json_decode(Storage::get($progressPath), true)
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

    /**
     * POST /apps/quick-order/api/products/refresh
     * Manually trigger product catalog refresh.
     */
    public function refreshProducts()
    {
        $shop = Auth::user();
        if (!$shop) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $shopDomain = $shop->getDomain()->toNative();
        $progressPath = "quickb2b/{$shopDomain}/progress.json";

        // Guard: prevent overlapping bulk operations (Shopify allows only one at a time)
        if (Storage::exists($progressPath)) {
            $current = json_decode(Storage::get($progressPath), true) ?: [];
            $active = $current['status'] ?? 'idle';
            if (in_array($active, ['starting', 'querying', 'processing', 'downloading'])) {
                return response()->json([
                    'status' => 'already_running',
                    'percent' => $current['percent'] ?? 0,
                    'message' => 'Catalog refresh is already in progress. Please wait.',
                ]);
            }
        }

        // Delete old cache so it's a clean rebuild
        Storage::delete("quickb2b/{$shopDomain}/products.json");
        Storage::delete($progressPath);

        RefreshProductCacheJob::dispatch($shopDomain);

        return response()->json(['status' => 'started', 'message' => 'Catalog refresh started']);
    }

    // ─── Private helpers ──────────────────────────────────────────

    /**
     * Filter products array by search query and optional field type.
     * $filter: 'all' | 'title' | 'sku' | 'tag' | 'collection'
     */
    private function filterProductsBySearch(array $products, string $query, string $filter = 'all'): array
    {
        if ($query === '') {
            return $products;
        }

        $qLower = mb_strtolower($query);
        return array_values(array_filter($products, function ($p) use ($qLower, $filter) {
            $collectionsStr = is_array($p['collections'] ?? null)
                ? mb_strtolower(implode(' ', $p['collections']))
                : '';

            return match ($filter) {
                'title'      => str_contains(mb_strtolower($p['title'] ?? ''), $qLower),
                'sku'        => str_contains(mb_strtolower($p['sku'] ?? ''), $qLower) || str_contains(mb_strtolower($p['variant_title'] ?? ''), $qLower),
                'tag'        => str_contains(mb_strtolower($p['tags'] ?? ''), $qLower),
                'collection' => str_contains($collectionsStr, $qLower),
                default      => str_contains(mb_strtolower($p['title'] ?? ''), $qLower)
                             || str_contains(mb_strtolower($p['sku'] ?? ''), $qLower)
                             || str_contains(mb_strtolower($p['variant_title'] ?? ''), $qLower)
                             || str_contains(mb_strtolower($p['tags'] ?? ''), $qLower)
                             || str_contains($collectionsStr, $qLower),
            };
        }));
    }
}
