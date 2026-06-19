<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PartnerApi
{
    protected string $orgId;
    protected string $token;
    protected string $apiVersion;
    protected string $appGid;

    public function __construct()
    {
        $this->orgId      = config('shopify-app.partner_api.org_id', env('PARTNER_API_ORG_ID'));
        $this->token      = config('shopify-app.partner_api.token', env('PARTNER_API_TOKEN'));
        $this->apiVersion = '2026-04';
        $this->appGid     = config('shopify-app.partner_api.app_gid', env('PARTNER_APP_GID'));
    }

    /**
     * Get the active subscription handle (e.g., 'pro') for a shop.
     * Returns null if no active subscription.
     */
    public function getActiveSubscription(string $shopGid): ?string
    {
        if (!$this->token || !$this->orgId || !$this->appGid) {
            Log::warning('PartnerApi: Missing credentials', [
                'org_id' => (bool) $this->orgId,
                'token'  => (bool) $this->token,
                'app_gid'=> (bool) $this->appGid,
            ]);
            return null;
        }

        $query = <<<'GQL'
            query ActiveSubscription($appId: ID!, $shopId: ID!) {
                activeSubscription(appId: $appId, shopId: $shopId) {
                    items {
                        handle
                    }
                }
            }
        GQL;

        try {
            $response = Http::withToken($this->token)
                ->timeout(10)
                ->connectTimeout(5)
                ->post("https://partners.shopify.com/{$this->orgId}/api/{$this->apiVersion}/graphql.json", [
                    'query'     => $query,
                    'variables' => [
                        'appId'  => $this->appGid,
                        'shopId' => $shopGid,
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('PartnerApi: HTTP error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            $subscription = $data['data']['activeSubscription'] ?? null;

            // null = no active subscription (cancelled, frozen, etc.)
            if (!$subscription) {
                return null;
            }

            return $subscription['items'][0]['handle'] ?? null;

        } catch (ConnectionException $e) {
            Log::error('PartnerApi: Connection failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Sync subscription status for a single shop.
     * Updates plan_id in DB. Returns true if still active.
     */
    public function syncShopSubscription($shop): bool
    {
        $shopGid = $this->getShopGid($shop);
        if (!$shopGid) {
            return false;
        }

        $handle = $this->getActiveSubscription($shopGid);

        $shop->update(['plan_id' => $handle]);

        if ($handle) {
            Log::info('PartnerApi: Subscription active', [
                'shop'   => $shop->getDomain()->toNative(),
                'plan'   => $handle,
            ]);
            return true;
        }

        Log::info('PartnerApi: No active subscription', [
            'shop' => $shop->getDomain()->toNative(),
        ]);
        return false;
    }

    /**
     * Build Shopify GID from shop domain.
     * Requires shopify_gid column or falls back to reconstructing.
     */
    protected function getShopGid($shop): ?string
    {
        // If the model has shopify_gid from package
        if (method_exists($shop, 'getId') && $shop->getId()) {
            return 'gid://shopify/Shop/' . $shop->getId()->toNative();
        }

        // Fallback: query via GraphQL (rare)
        Log::warning('PartnerApi: Cannot determine shop GID', [
            'shop' => $shop->getDomain()->toNative(),
        ]);
        return null;
    }
}
