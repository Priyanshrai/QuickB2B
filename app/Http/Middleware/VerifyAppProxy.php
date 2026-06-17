<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Verifies Shopify App Proxy requests using HMAC signature.
 * Shopify forwards storefront requests to our app with a signature param.
 */
class VerifyAppProxy
{
    public function handle(Request $request, Closure $next)
    {
        // App Proxy HMAC verification
        $signature = $request->query('signature', '');

        // Build the validation string from all query params except 'signature'
        $params = $request->query();
        unset($params['signature']);
        ksort($params);

        $message = '';
        foreach ($params as $key => $value) {
            // Replace array syntax for multi-value params
            $key = str_replace('[]', '', $key);
            if (is_array($value)) {
                $value = implode(',', $value);
            }
            $message .= "{$key}={$value}";
        }

        $secret = config('shopify-app.api_secret');
        $calculated = hash_hmac('sha256', $message, $secret);

        if (!hash_equals($calculated, $signature)) {
            // In development with ngrok, we can skip verification
            if (app()->environment('local') && !$request->has('signature')) {
                return $next($request);
            }

            return response('Invalid proxy signature.', 403);
        }

        return $next($request);
    }
}
