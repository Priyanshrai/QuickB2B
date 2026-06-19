<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSubscription
{
    /**
     * Block access unless the shop has an active Shopify App Pricing subscription.
     */
    public function handle(Request $request, Closure $next)
    {
        // If billing is disabled in config, allow all traffic
        if (!config('shopify-app.billing_enabled', false)) {
            return $next($request);
        }

        $shop = Auth::user();

        // plan_id stores the plan handle (e.g., 'pro') from Shopify App Pricing
        if (!$shop || !$shop->plan_id) {
            return redirect()->route('plans', [
                'host'  => $request->get('host'),
                'shop'  => $request->get('shop'),
            ]);
        }

        return $next($request);
    }
}
