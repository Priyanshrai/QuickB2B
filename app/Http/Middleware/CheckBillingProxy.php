<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckBillingProxy
{
    /**
     * Block proxy/storefront access if billing is enabled and shop hasn't paid.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!config('shopify-app.billing_enabled', false)) {
            return $next($request);
        }

        $shop = auth()->user();

        if (!$shop) {
            return $next($request);
        }

        $isPaid = $shop->plan_id
            || (method_exists($shop, 'isFreemium') && $shop->isFreemium())
            || (method_exists($shop, 'isGrandfathered') && $shop->isGrandfathered());

        if ($isPaid) {
            return $next($request);
        }

        // API routes → JSON error
        if ($request->expectsJson() || $request->is('*api/*')) {
            return response()->json([
                'error' => 'QuickB2B subscription is inactive. Please contact the store owner.',
            ], 402);
        }

        // Page route → friendly HTML
        $shopDomain = $shop->getDomain()->toNative();
        return response(
            view('quick-order.inactive', compact('shopDomain'))
        )->header('Content-Type', 'application/liquid');
    }
}
