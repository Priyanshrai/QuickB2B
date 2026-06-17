<?php

namespace App\Listeners;

use App\Jobs\AutoSetupStorefrontJob;
use Osiset\ShopifyApp\Messaging\Events\AppInstalledEvent;

/**
 * Fires when a merchant installs the app.
 * Triggers auto-setup of the Quick Order page and navigation menu.
 */
class AppInstalledListener
{
    public function handle(AppInstalledEvent $event): void
    {
        $shop = $event->shop;

        AutoSetupStorefrontJob::dispatch(
            $shop->getId()->toNative(),
            $shop->getDomain()->toNative(),
        );
    }
}
