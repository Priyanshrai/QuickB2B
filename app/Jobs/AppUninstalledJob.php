<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Storage;

class AppUninstalledJob extends \Osiset\ShopifyApp\Messaging\Jobs\AppUninstalledJob
{
    /**
     * Override to clean up app-specific storage after uninstall.
     */
    public function handle(): void
    {
        // Clean up cached product data and progress files
        // $this->domain is a raw string at this point (before parent converts it to ShopDomain VO)
        $domain = is_string($this->domain) ? $this->domain : $this->domain->toNative();
        if ($domain) {
            Storage::deleteDirectory("quickb2b/{$domain}");
        }

        // Call parent to handle DB cleanup (soft-delete shop)
        parent::handle();
    }
}
