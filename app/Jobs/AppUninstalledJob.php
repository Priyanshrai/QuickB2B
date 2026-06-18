<?php

namespace App\Jobs;

use App\Models\QuickOrderPage;
use App\Services\ShopifyGraphQL;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AppUninstalledJob extends \Osiset\ShopifyApp\Messaging\Jobs\AppUninstalledJob
{
    /**
     * Override to clean up ALL app data on uninstall:
     *  - Storage files (products.json, progress.json, draft_order_progress.json)
     *  - Shopify page (the "Quick Order" page on the store)
     *  - Navigation menu link (if any)
     *  - DB record (QuickOrderPage)
     * Then calls parent for billing/token/soft-delete cleanup.
     */
    public function handle(): void
    {
        // $this->domain is a raw string at this point
        $domain = is_string($this->domain) ? $this->domain : $this->domain->toNative();

        if ($domain) {
            // 1. Clean up Shopify page + menu (API token still valid before parent runs)
            $page = QuickOrderPage::whereHas('user', fn($q) => $q->where('name', $domain))->first();
            $shop = \App\Models\User::where('name', $domain)->first();

            if ($page && $shop) {
                // Remove from navigation menu first (if linked)
                if ($page->menu_linked && $page->shopify_menu_id && $page->shopify_page_id) {
                    try {
                        ShopifyGraphQL::removePageFromMenu($shop, $page->shopify_menu_id, $page->shopify_page_id);
                        Log::info('QuickB2B: Menu link removed on uninstall', ['domain' => $domain]);
                    } catch (\Throwable $e) {
                        Log::warning('QuickB2B: Could not remove menu on uninstall', [
                            'domain' => $domain, 'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Delete the page from Shopify store
                if ($page->shopify_page_id) {
                    try {
                        ShopifyGraphQL::deletePage($shop, $page->shopify_page_id);
                        Log::info('QuickB2B: Shopify page deleted on uninstall', ['domain' => $domain]);
                    } catch (\Throwable $e) {
                        Log::warning('QuickB2B: Could not delete Shopify page on uninstall', [
                            'domain' => $domain, 'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Delete our DB record
                $page->delete();
            }

            // 2. Clean up all cached storage files
            Storage::deleteDirectory("quickb2b/{$domain}");
        }

        // 3. Call parent to handle billing cancel, token clean, soft-delete
        parent::handle();
    }
}
