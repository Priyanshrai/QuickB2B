<?php

namespace App\Services;

use Gnikyt\BasicShopifyAPI\ResponseAccess;
use Illuminate\Support\Facades\Log;

class ShopifyGraphQL
{
    /**
     * Execute a GraphQL query and return the unwrapped 'data' array.
     */
    public static function query($shop, string $query, array $variables = []): array
    {
        $response = $shop->api()->graph($query, $variables);
        $body = $response['body'] ?? [];

        // Log top-level GraphQL errors (partial failures, deprecations, cost warnings)
        if (!empty($body['errors'])) {
            Log::warning('ShopifyGraphQL: query returned errors', [
                'errors' => $body['errors'],
            ]);
        }

        $data = static::unwrap($body['data'] ?? []);

        return $data;
    }

    /**
     * Get the raw response (with errors key) for error checking.
     */
    public static function raw($shop, string $query, array $variables = []): array
    {
        return $shop->api()->graph($query, $variables);
    }

    // ─── Page Operations ───────────────────────────────────────────

    /**
     * Create an online store page. Returns page data or throws on error.
     */
    public static function createPage($shop, string $title, string $bodyHtml = ''): array
    {
        $mutation = <<<'GQL'
            mutation pageCreate($page: PageCreateInput!) {
                pageCreate(page: $page) {
                    page { id title handle }
                    userErrors { field message }
                }
            }
        GQL;

        $data = static::query($shop, $mutation, [
            'page' => [
                'title'       => $title,
                'body'        => $bodyHtml,
                'isPublished' => true,
            ],
        ]);

        return $data['pageCreate'] ?? [];
    }

    /**
     * Update a page title. Returns updated page data.
     */
    public static function updatePageTitle($shop, string $pageId, string $title): array
    {
        $mutation = <<<'GQL'
            mutation pageUpdate($id: ID!, $page: PageUpdateInput!) {
                pageUpdate(id: $id, page: $page) {
                    page { id title handle }
                    userErrors { field message }
                }
            }
        GQL;

        $data = static::query($shop, $mutation, [
            'id'   => $pageId,
            'page' => ['title' => $title],
        ]);

        return $data['pageUpdate'] ?? [];
    }

    /**
     * Update a page body. Returns updated page data.
     */
    public static function updatePageBody($shop, string $pageId, string $body): array
    {
        $mutation = <<<'GQL'
            mutation pageUpdate($id: ID!, $page: PageUpdateInput!) {
                pageUpdate(id: $id, page: $page) {
                    page { id title handle }
                    userErrors { field message }
                }
            }
        GQL;

        $data = static::query($shop, $mutation, [
            'id'   => $pageId,
            'page' => ['body' => $body],
        ]);

        return $data['pageUpdate'] ?? [];
    }

    /**
     * Delete a page from the online store.
     */
    public static function deletePage($shop, string $pageId): array
    {
        $mutation = <<<'GQL'
            mutation pageDelete($id: ID!) {
                pageDelete(id: $id) {
                    deletedPageId
                    userErrors { field message }
                }
            }
        GQL;

        $data = static::query($shop, $mutation, ['id' => $pageId]);

        return $data['pageDelete'] ?? [];
    }

    // ─── Menu / Navigation Operations ──────────────────────────────

    /**
     * Fetch all navigation menus from the store.
     */
    public static function fetchMenus($shop): array
    {
        $query = <<<'GQL'
            query {
                menus(first: 10) {
                    edges {
                        node { id title handle }
                    }
                }
            }
        GQL;

        $data = static::query($shop, $query);
        $edges = $data['menus']['edges'] ?? [];

        return array_map(fn ($e) => $e['node'], $edges);
    }

    /**
     * Fetch a single menu with its items.
     */
    public static function fetchMenuWithItems($shop, string $menuId): array
    {
        $query = <<<'GQL'
            query menu($id: ID!) {
                menu(id: $id) {
                    id
                    title
                    items {
                        id
                        title
                        type
                        resourceId
                    }
                }
            }
        GQL;

        $data = static::query($shop, $query, ['id' => $menuId]);

        return $data['menu'] ?? [];
    }

    /**
     * Update a menu — replace all items with the given list.
     * Pass items as array of { title, type, resourceId?, id? }.
     * Include `id` on existing items to update them; omit `id` to create new.
     */
    public static function updateMenu($shop, string $menuId, string $menuTitle, array $items): array
    {
        $mutation = <<<'GQL'
            mutation menuUpdate($id: ID!, $title: String!, $items: [MenuItemUpdateInput!]!) {
                menuUpdate(id: $id, title: $title, items: $items) {
                    menu { id title }
                    userErrors { field message }
                }
            }
        GQL;

        $data = static::query($shop, $mutation, [
            'id'    => $menuId,
            'title' => $menuTitle,
            'items' => $items,
        ]);

        return $data['menuUpdate'] ?? [];
    }

    /**
     * Add a single page link to a menu (appends without removing existing items).
     */
    public static function addPageToMenu($shop, string $menuId, string $menuTitle, string $pageId, string $linkTitle): array
    {
        // Get current menu items first
        $menu = static::fetchMenuWithItems($shop, $menuId);
        $existingItems = $menu['items'] ?? [];

        // Map existing items to update format (include id)
        $items = array_map(fn ($item) => [
            'id'         => $item['id'],
            'title'      => $item['title'],
            'type'       => $item['type'],
            'resourceId' => $item['resourceId'] ?? null,
        ], $existingItems);

        // Append the new page link
        $items[] = [
            'title'      => $linkTitle,
            'type'       => 'PAGE',
            'resourceId' => $pageId,
        ];

        return static::updateMenu($shop, $menuId, $menuTitle, $items);
    }

    /**
     * Remove a page link from a menu while keeping all other items.
     */
    public static function removePageFromMenu($shop, string $menuId, string $pageId): array
    {
        $menu = static::fetchMenuWithItems($shop, $menuId);
        $existingItems = $menu['items'] ?? [];
        $menuTitle = $menu['title'] ?? '';

        // Filter out the page to remove
        $filtered = array_filter($existingItems, fn ($item) =>
            ($item['resourceId'] ?? '') !== $pageId
        );

        // Rebuild items list
        $items = array_map(fn ($item) => [
            'id'         => $item['id'],
            'title'      => $item['title'],
            'type'       => $item['type'],
            'resourceId' => $item['resourceId'] ?? null,
        ], array_values($filtered));

        return static::updateMenu($shop, $menuId, $menuTitle, $items);
    }

    // ─── Page Sync ─────────────────────────────────────────────────

    /**
     * Find our page on Shopify by searching title + filtering by marker.
     * Catches quick-order, quick-order-1, quick-order-2, etc.
     */
    public static function fetchQuickOrderPage($shop): ?array
    {
        $query = <<<'GQL'
            query findPage($query: String!) {
                pages(first: 10, query: $query) {
                    edges {
                        node { id title handle body isPublished }
                    }
                }
            }
        GQL;

        $data = static::query($shop, $query, ['query' => "title:Quick Order"]);

        foreach ($data['pages']['edges'] ?? [] as $edge) {
            $node = $edge['node'] ?? [];
            $body = $node['body'] ?? '';
            $h    = $node['handle'] ?? '';
            // Match by marker OR handle prefix
            if (str_contains($body, 'quickb2b-page') || str_starts_with($h, 'quick-order')) {
                // Clean up any extra duplicate pages on Shopify (keep only this one)
                static::cleanupDuplicatePages($shop, $data['pages']['edges'] ?? [], $node['id']);
                return $node;
            }
        }

        return null;
    }

    /**
     * Delete any duplicate pages with our marker that aren't the one we're keeping.
     */
    private static function cleanupDuplicatePages($shop, array $edges, string $keepId): void
    {
        foreach ($edges as $edge) {
            $node = $edge['node'] ?? [];
            $body = $node['body'] ?? '';
            $id   = $node['id'] ?? '';
            if ($id !== $keepId && str_contains($body, 'quickb2b-page')) {
                try {
                    static::deletePage($shop, $id);
                    Log::info('QuickB2B: cleaned up duplicate page', [
                        'deleted_id' => $id,
                        'kept_id'    => $keepId,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('QuickB2B: failed to cleanup duplicate', [
                        'id' => $id, 'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    // ─── Product Data ──────────────────────────────────────────────

    /**
     * Fetch products with cursor pagination for infinite scroll.
     * Returns ['edges' => [...], 'nextCursor' => '...', 'hasMore' => bool]
     */
    public static function fetchProducts($shop, ?string $cursor = null, int $first = 250): array
    {
        $allEdges = [];
        $nextCursor = null;
        $hasMore = false;

        $afterArg = $cursor ? ', after: "' . $cursor . '"' : '';

        $query = <<<GQL
            {
                products(first: {$first}{$afterArg}) {
                    edges {
                        node {
                            id
                            title
                            variants(first: 100) {
                                edges {
                                    node { id sku price inventoryQuantity inventoryPolicy }
                                }
                            }
                        }
                        cursor
                    }
                    pageInfo { hasNextPage }
                }
            }
        GQL;

        $data = static::query($shop, $query);
        $products = $data['products'] ?? [];
        $edges = $products['edges'] ?? [];
        $pageInfo = $products['pageInfo'] ?? [];
        $hasMore = !empty($pageInfo['hasNextPage']);

        if ($hasMore && !empty($edges)) {
            $lastEdge = end($edges);
            $nextCursor = $lastEdge['cursor'] ?? null;
        }

        return ['edges' => $edges, 'nextCursor' => $nextCursor, 'hasMore' => $hasMore];
    }

    // ─── Draft Orders ─────────────────────────────────────────────

    /**
     * Create a draft order with line items.
     * $items = [['variantId' => 'gid://...', 'quantity' => 5], ...]
     * Returns ['draftOrder' => [...], 'userErrors' => [...]] or null.
     */
    public static function createDraftOrder($shop, array $lineItems, ?string $email = null): ?array
    {
        $input = ['lineItems' => $lineItems];
        if ($email) {
            $input['email'] = $email;
        }

        $mutation = <<<'GQL'
            mutation draftOrderCreate($input: DraftOrderInput!) {
                draftOrderCreate(input: $input) {
                    draftOrder { id name invoiceUrl status }
                    userErrors { field message }
                }
            }
        GQL;

        $data = static::query($shop, $mutation, ['input' => $input]);
        return $data['draftOrderCreate'] ?? null;
    }

    /**
     * Send invoice for a draft order. Returns invoiceUrl or null.
     */
    public static function sendDraftOrderInvoice($shop, string $draftOrderId): ?string
    {
        $mutation = <<<'GQL'
            mutation draftOrderInvoiceSend($id: ID!) {
                draftOrderInvoiceSend(id: $id) {
                    draftOrder { invoiceUrl }
                    userErrors { field message }
                }
            }
        GQL;

        $data = static::query($shop, $mutation, ['id' => $draftOrderId]);
        return $data['draftOrderInvoiceSend']['draftOrder']['invoiceUrl'] ?? null;
    }

    // ─── Internal Helpers ──────────────────────────────────────────

    /**
     * Deep-convert ResponseAccess to plain arrays.
     */
    private static function unwrap(mixed $data): array
    {
        if ($data instanceof ResponseAccess) {
            return json_decode(json_encode($data->toArray()), true);
        }
        if (is_array($data)) {
            return $data;
        }
        return [];
    }
}
