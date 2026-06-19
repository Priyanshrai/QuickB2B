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

        Log::debug('[PartnerApi::construct] Credentials loaded', [
            'org_id'      => $this->orgId ?: '(empty)',
            'token_len'   => strlen($this->token ?: ''),
            'token_prefix'=> substr($this->token ?: '', 0, 10) . '...',
            'app_gid'     => $this->appGid ?: '(empty)',
            'source'      => [
                'config_org'  => config('shopify-app.partner_api.org_id'),
                'config_token_len' => strlen(config('shopify-app.partner_api.token') ?: ''),
                'env_org'     => env('PARTNER_API_ORG_ID'),
                'env_token_len' => strlen(env('PARTNER_API_TOKEN') ?: ''),
            ],
        ]);
    }

    /**
     * Get the active subscription handle (e.g., 'pro') for a shop.
     * Returns null if no active subscription.
     */
    public function getActiveSubscription(string $shopGid): ?string
    {
        Log::debug('[PartnerApi::getActiveSubscription] STEP 1: Starting', [
            'shop_gid' => $shopGid,
        ]);

        if (!$this->token || !$this->orgId || !$this->appGid) {
            Log::warning('PartnerApi: Missing credentials', [
                'org_id' => (bool) $this->orgId,
                'token'  => (bool) $this->token,
                'app_gid'=> (bool) $this->appGid,
            ]);
            return null;
        }

        Log::debug('[PartnerApi::getActiveSubscription] STEP 2: Credentials OK, building API call');

        $query = <<<'GQL'
            query ActiveSubscription($appId: ID!, $shopId: ID!) {
                activeSubscription(appId: $appId, shopId: $shopId) {
                    items {
                        handle
                    }
                }
            }
        GQL;

        $url = "https://partners.shopify.com/{$this->orgId}/api/{$this->apiVersion}/graphql.json";
        $variables = [
            'appId'  => $this->appGid,
            'shopId' => $shopGid,
        ];

        Log::debug('[PartnerApi::getActiveSubscription] STEP 3: Making HTTP request', [
            'url'          => $url,
            'app_gid'      => $this->appGid,
            'shop_gid'     => $shopGid,
            'token_prefix' => substr($this->token, 0, 8) . '...',
        ]);

        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->token,
                'Content-Type' => 'application/json',
            ])
                ->timeout(10)
                ->connectTimeout(5)
                ->post($url, [
                    'query'     => $query,
                    'variables' => $variables,
                ]);

            Log::debug('[PartnerApi::getActiveSubscription] STEP 4: Response received', [
                'status'  => $response->status(),
                'body'    => $response->body(),
            ]);

            if (!$response->successful()) {
                Log::error('PartnerApi: HTTP error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();

            Log::debug('[PartnerApi::getActiveSubscription] STEP 5: Parsed JSON', [
                'data' => $data,
            ]);

            $subscription = $data['data']['activeSubscription'] ?? null;

            Log::debug('[PartnerApi::getActiveSubscription] STEP 6: Subscription object', [
                'subscription' => $subscription,
            ]);

            // null = no active subscription (cancelled, frozen, etc.)
            if (!$subscription) {
                Log::info('[PartnerApi::getActiveSubscription] RESULT: No active subscription (null)');
                return null;
            }

            $handle = $subscription['items'][0]['handle'] ?? null;

            Log::info('[PartnerApi::getActiveSubscription] RESULT: Found handle', [
                'handle' => $handle,
            ]);

            return $handle;

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
        Log::debug('[PartnerApi::syncShopSubscription] STEP 1: Starting sync', [
            'shop'    => $shop->getDomain()->toNative(),
            'plan_id' => $shop->plan_id,
        ]);

        $shopGid = $this->getShopGid($shop);
        if (!$shopGid) {
            Log::warning('[PartnerApi::syncShopSubscription] FAIL: No shop GID');
            return false;
        }

        Log::debug('[PartnerApi::syncShopSubscription] STEP 2: Shop GID = ' . $shopGid);

        $handle = $this->getActiveSubscription($shopGid);

        Log::debug('[PartnerApi::syncShopSubscription] STEP 3: API returned', [
            'handle' => $handle,
        ]);

        $shop->update(['plan_id' => $handle]);

        Log::debug('[PartnerApi::syncShopSubscription] STEP 4: DB updated', [
            'new_plan_id' => $handle,
        ]);

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
        Log::debug('[PartnerApi::getShopGid] Building GID', [
            'shop' => $shop->getDomain()->toNative(),
        ]);

        try {
            // Use shop's own API to query its real Shopify ID
            $response = $shop->api()->rest('GET', '/admin/api/shop.json');

            Log::debug('[PartnerApi::getShopGid] Shopify /admin/api/shop.json response', [
                'status' => $response['status'] ?? 'unknown',
            ]);

            $shopId = $response['body']['shop']['id'] ?? null;

            if ($shopId) {
                $gid = "gid://shopify/Shop/{$shopId}";
                Log::debug('[PartnerApi::getShopGid] GID built from Admin API', [
                    'shopify_id' => $shopId,
                    'gid'        => $gid,
                ]);
                return $gid;
            }

            Log::warning('[PartnerApi::getShopGid] No ID in shop API response', [
                'response' => $response,
            ]);
        } catch (\Exception $e) {
            Log::error('[PartnerApi::getShopGid] Admin API call failed', [
                'error' => $e->getMessage(),
            ]);
        }

        Log::warning('PartnerApi: Cannot determine shop GID', [
            'shop' => $shop->getDomain()->toNative(),
        ]);
        return null;
    }
}
