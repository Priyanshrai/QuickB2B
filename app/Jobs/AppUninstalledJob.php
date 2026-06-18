<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Osiset\ShopifyApp\Actions\CancelCurrentPlan;
use Osiset\ShopifyApp\Contracts\Commands\Shop as IShopCommand;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;

class AppUninstalledJob extends \Osiset\ShopifyApp\Messaging\Jobs\AppUninstalledJob
{
    /**
     * Cleanup on uninstall.
     *
     * NOTE: Shopify revokes the access token BEFORE sending this webhook.
     * GraphQL API calls (page delete, menu unlink) return 401.
     * Use the Dashboard "Delete Page" button BEFORE uninstalling to remove
     * the Shopify page + menu link while the token is still valid.
     *
     * Here we only clean local data: storage files + DB cleanup.
     * Package handles: plan cancel, token purge, soft-delete.
     */
    public function handle(
        IShopCommand $shopCommand,
        IShopQuery $shopQuery,
        CancelCurrentPlan $cancelCurrentPlanAction
    ): bool {
        $domain = is_string($this->domain) ? $this->domain : $this->domain->toNative();

        if ($domain) {
            // Wipe all cached storage files
            Storage::deleteDirectory("quickb2b/{$domain}");
            Log::info('QuickB2B: Storage wiped on uninstall', ['domain' => $domain]);
        }

        // Package handles: plan cancel, token purge, soft-delete
        return parent::handle($shopCommand, $shopQuery, $cancelCurrentPlanAction);
    }
}
