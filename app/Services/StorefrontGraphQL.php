<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Calls the Shopify Storefront API (public, unauthenticated).
 * Requires a storefront access token (different from Admin API token).
 */
class StorefrontGraphQL
{
    /**
     * Execute a Storefront GraphQL query.
     */
    public static function query(string $shopDomain, string $storefrontToken, string $query, array $variables = []): array
    {
        $url = "https://{$shopDomain}/api/2026-04/graphql.json";

        $response = Http::withHeaders([
            'X-Shopify-Storefront-Access-Token' => $storefrontToken,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'query' => $query,
            'variables' => $variables,
        ]);

        if (!$response->successful()) {
            Log::error('StorefrontGraphQL: HTTP error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return ['errors' => [['message' => 'Storefront API request failed']]];
        }

        $json = $response->json();
        if (!empty($json['errors'])) {
            Log::error('StorefrontGraphQL: GraphQL errors', ['errors' => $json['errors']]);
        }

        return $json;
    }

    /**
     * Create a cart with multiple line items.
     * Returns ['cart' => [...]] or ['errors' => [...]].
     */
    public static function createCart(string $shopDomain, string $storefrontToken, array $items): array
    {
        // $items = [['merchandiseId' => 'gid://...', 'quantity' => 5], ...]
        $lines = array_map(fn($item) => [
            'merchandiseId' => $item['merchandiseId'],
            'quantity' => (int) ($item['quantity'] ?? 1),
        ], $items);

        $mutation = <<<'GQL'
            mutation cartCreate($input: CartInput!) {
                cartCreate(input: $input) {
                    cart {
                        id
                        checkoutUrl
                        totalQuantity
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        GQL;

        $result = static::query($shopDomain, $storefrontToken, $mutation, [
            'input' => ['lines' => $lines],
        ]);

        return $result['data']['cartCreate'] ?? ['errors' => [['message' => 'No cartCreate in response']]];
    }
}
