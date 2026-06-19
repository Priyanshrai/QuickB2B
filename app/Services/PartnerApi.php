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

        // Partner API uses "partners" GID namespace, not "shopify"
        if ($this->appGid && str_starts_with($this->appGid, 'gid://shopify/')) {
            $this->appGid = str_replace('gid://shopify/', 'gid://partners/', $this->appGid);
        }
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
            query AppEvents($appId: ID!, $shopId: ID!) {
                app(id: $appId) {
                    events(shopId: $shopId, first: 1, types: [SUBSCRIPTION_CHARGE_ACTIVATED]) {
                        edges {
                            node {
                                ... on SubscriptionChargeActivated {
                                    charge {
                                        id
                                        name
                                    }
                                }
                            }
                        }
                    }
                }
            }
        GQL;

        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->token,
                'Content-Type' => 'application/json',
            ])
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

            $events = $data['data']['app']['events']['edges'] ?? [];

            // No SUBSCRIPTION_CHARGE_ACTIVATED events = no active subscription
            if (empty($events)) {
                return null;
            }

            return $events[0]['node']['charge']['name'] ?? null;

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
     * Build Shopify GID from shop.
     * Uses Shopify Admin API to get the real shop ID.
     */
    protected function getShopGid($shop): ?string
    {
        try {
            $response = $shop->api()->rest('GET', '/admin/api/shop.json');

            $shopId = $response['body']['shop']['id'] ?? null;

            if ($shopId) {
                return "gid://partners/Shop/{$shopId}";
            }
        } catch (\Exception $e) {
            Log::error('PartnerApi: Admin API shop query failed', [
                'shop'  => $shop->getDomain()->toNative(),
                'error' => $e->getMessage(),
            ]);
        }

        Log::warning('PartnerApi: Cannot determine shop GID', [
            'shop' => $shop->getDomain()->toNative(),
        ]);
        return null;
    }
}
