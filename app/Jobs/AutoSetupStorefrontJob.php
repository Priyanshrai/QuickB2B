<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Runs when a merchant installs the app.
 * Automatically creates a "Quick Order" page in their store
 * and adds it to the main navigation menu.
 */
class AutoSetupStorefrontJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;

    public function __construct(
        protected int $shopId,
        protected string $shopDomain,
    ) {}

    public function handle(): void
    {
        $shop = \App\Models\User::find($this->shopId);

        if (!$shop) {
            Log::error('AutoSetupStorefrontJob: Shop not found', ['shop_id' => $this->shopId]);
            return;
        }

        try {
            // Step 1: Create the "Quick Order" page
            $pageId = $this->createPage($shop);
            Log::info('AutoSetupStorefrontJob: Page created', [
                'shop' => $this->shopDomain,
                'page_id' => $pageId,
            ]);

            // Step 2: Add the page to the store's main navigation menu
            $this->addToNavigation($shop, $pageId);
            Log::info('AutoSetupStorefrontJob: Navigation item added', [
                'shop' => $this->shopDomain,
                'page_id' => $pageId,
            ]);

        } catch (\Throwable $e) {
            Log::error('AutoSetupStorefrontJob failed', [
                'shop' => $this->shopDomain,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create a page in the Shopify store using the Admin GraphQL API.
     */
    private function createPage($shop): ?string
    {
        $query = <<<GQL
            mutation pageCreate(\$page: PageCreateInput!) {
                pageCreate(page: \$page) {
                    page {
                        id
                        title
                        handle
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        GQL;

        $variables = [
            'page' => [
                'title' => 'Quick Order',
                'body' => '<div id="quickb2b-root" data-shop="' . $this->shopDomain . '"></div>',
                'isPublished' => true,
            ],
        ];

        $response = $shop->api()->graph($query, $variables);
        $body = $response['body'] ?? [];

        if (!empty($body['data']['pageCreate']['userErrors'])) {
            $errors = $body['data']['pageCreate']['userErrors'];
            Log::warning('AutoSetupStorefrontJob: Page creation errors', [
                'errors' => $errors,
            ]);
            // Don't throw — the page might already exist
        }

        return $body['data']['pageCreate']['page']['id'] ?? null;
    }

    /**
     * Add the created page to the store's main navigation menu.
     */
    private function addToNavigation($shop, string $pageId): void
    {
        // First, find the main menu
        $menuId = $this->findMainMenu($shop);

        if (!$menuId) {
            Log::warning('AutoSetupStorefrontJob: No main menu found', [
                'shop' => $this->shopDomain,
            ]);
            return;
        }

        // Add the page as a menu item
        $query = <<<GQL
            mutation navigationItemCreate(\$navigationItem: NavigationItemCreateInput!) {
                navigationItemCreate(navigationItem: \$navigationItem) {
                    navigationItem {
                        id
                        title
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        GQL;

        $variables = [
            'navigationItem' => [
                'menuId' => $menuId,
                'title' => 'Quick Order',
                'type' => 'PAGE',
                'pageId' => $pageId,
            ],
        ];

        $response = $shop->api()->graph($query, $variables);
        $body = $response['body'] ?? [];

        if (!empty($body['data']['navigationItemCreate']['userErrors'])) {
            Log::warning('AutoSetupStorefrontJob: Navigation item creation errors', [
                'errors' => $body['data']['navigationItemCreate']['userErrors'],
            ]);
        }
    }

    /**
     * Find the store's main navigation menu (usually named "Main Menu").
     */
    private function findMainMenu($shop): ?string
    {
        $query = <<<GQL
            {
                menus(first: 10) {
                    edges {
                        node {
                            id
                            title
                            handle
                        }
                    }
                }
            }
        GQL;

        $response = $shop->api()->graph($query);
        $body = $response['body'] ?? [];
        $edges = $body['data']['menus']['edges'] ?? [];

        foreach ($edges as $edge) {
            $menu = $edge['node'];
            // Look for the "main-menu" handle (Shopify's default main menu)
            if ($menu['handle'] === 'main-menu' || stripos($menu['title'], 'main') !== false) {
                return $menu['id'];
            }
        }

        // Fallback: return the first menu
        return $edges[0]['node']['id'] ?? null;
    }
}
