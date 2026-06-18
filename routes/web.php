<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Osiset\ShopifyApp\Util;

// Privacy Policy (public, no auth required)
Route::get('/privacy', function () {
    return response()->file(public_path('privacy-policy.html'));
});

// App Proxy — storefront Quick Order page + API (signed by Shopify, public)
Route::middleware(['auth.proxy', 'throttle:120,1'])->group(function () {
    Route::get('/apps/quick-order/sample-csv', function () {
        return response()->download(public_path('sample-order.csv'));
    });

    Route::get('/apps/quick-order', function () {
        $shop = Auth::user();
        if (!$shop) {
            return response('Unauthorized', 401);
        }
        return response(
            view('quick-order.proxy', ['shopDomain' => $shop->getDomain()->toNative()])
        )->header('Content-Type', 'application/liquid');
    })->name('proxy.quick-order');

    Route::get('/apps/quick-order/api/products', [\App\Http\Controllers\QuickOrderController::class, 'products']);
    Route::get('/apps/quick-order/api/products/status', [\App\Http\Controllers\QuickOrderController::class, 'productsStatus']);
    Route::post('/apps/quick-order/api/products/refresh', [\App\Http\Controllers\QuickOrderController::class, 'refreshProducts']);
    Route::post('/apps/quick-order/api/add-all', [\App\Http\Controllers\QuickOrderController::class, 'addAll']);
    Route::post('/apps/quick-order/api/add-bulk', [\App\Http\Controllers\QuickOrderController::class, 'addBulk']);
    Route::post('/apps/quick-order/api/draft-order', [\App\Http\Controllers\QuickOrderController::class, 'draftOrder']);
    Route::get('/apps/quick-order/api/draft-order/status', [\App\Http\Controllers\QuickOrderController::class, 'draftOrderStatus']);
});

// Home — Dashboard
Route::get('/', function () {
    return view('welcome');
})->middleware(['verify.shopify'])->name('home');

// Billing — Plans page
Route::middleware(['verify.shopify'])->group(function () {
    Route::get('/plans', function () {
        return view('billing.plans');
    })->name('plans');

    Route::get('/plans/subscribe', function (Request $request) {
        $shop = Auth::user();
        try {
            $charge = app(\Osiset\ShopifyApp\Actions\CreateRecurringCharge::class);
            $charge($shop->getId(), [
                'name' => 'Pro Plan',
                'price' => 9.99,
                'trial_days' => 7,
                'test' => config('shopify-app.billing.test', true),
            ]);
            return redirect()->to($charge->getConfirmationUrl());
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Plan subscribe failed', [
                'shop' => $shop->getDomain()->toNative(),
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Could not create subscription. Please try again.');
        }
    })->name('plans.subscribe');

    Route::get('/plans/cancel', function (Request $request) {
        $shop = Auth::user();
        try {
            $cancelPlan = app(\Osiset\ShopifyApp\Actions\CancelCurrentPlan::class);
            $cancelPlan($shop->getId());
            $shop->plan_id = null;
            $shop->save();
            return redirect()->route('plans', ['host' => $request->get('host')])
                ->with('success', 'Subscription cancelled. You are now on the Free plan.');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Plan cancel failed', [
                'shop' => $shop->getDomain()->toNative(),
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Could not cancel subscription. Please try again.');
        }
    })->name('plans.cancel');

    // One-click setup: create Quick Order page + add to navigation menu
    Route::post('/setup/create-page', [\App\Http\Controllers\SetupController::class, 'createPageAndMenu'])
        ->name('setup.create-page');

    // Page management
    Route::post('/page/update-title', [\App\Http\Controllers\PageController::class, 'updateTitle'])
        ->name('page.update-title');
    Route::post('/page/delete', [\App\Http\Controllers\PageController::class, 'deletePage'])
        ->name('page.delete');
    Route::post('/page/link-menu', [\App\Http\Controllers\PageController::class, 'linkToMenu'])
        ->name('page.link-menu');
    Route::post('/page/sync', [\App\Http\Controllers\PageController::class, 'syncPage'])
        ->name('page.sync');
});

/*
|--------------------------------------------------------------------------
| GDPR Compliance Webhooks (Mandatory for App Store)
|--------------------------------------------------------------------------
| Uses the package's auth.webhook middleware for HMAC verification.
| Kyon147 package /webhook/{type} route cannot handle slashes in topic
| names (shop/redact, customers/redact, etc.), so we define explicit routes.
| Using /webhook/gdpr/ prefix to avoid conflict with package's /webhook/{type} route.
*/
Route::prefix('webhook/gdpr')->middleware(['auth.webhook.gdpr'])->group(function () {
    Route::post('/shop-redact', function (Request $request) {
        \App\Jobs\GdprShopRedactJob::dispatch(
            $request->header('x-shopify-shop-domain'),
            json_decode($request->getContent())
        );
        return response('', 201);
    });

    Route::post('/customers-redact', function (Request $request) {
        \App\Jobs\GdprCustomerRedactJob::dispatch(
            $request->header('x-shopify-shop-domain'),
            json_decode($request->getContent())
        );
        return response('', 201);
    });

    Route::post('/customers-data-request', function (Request $request) {
        \App\Jobs\GdprCustomerDataRequestJob::dispatch(
            $request->header('x-shopify-shop-domain'),
            json_decode($request->getContent())
        );
        return response('', 201);
    });
});

// Single GDPR compliance endpoint (TOML format: all 3 topics → one URL)
Route::post('/webhooks', function (Request $request) {
    $topic = $request->header('X-Shopify-Topic', '');
    $domain = $request->header('x-shopify-shop-domain');
    $data = json_decode($request->getContent());

    $job = match ($topic) {
        'shop/redact' => \App\Jobs\GdprShopRedactJob::class,
        'customers/redact' => \App\Jobs\GdprCustomerRedactJob::class,
        'customers/data_request' => \App\Jobs\GdprCustomerDataRequestJob::class,
        default => null,
    };

    if (!$job) {
        return response('Unknown topic', 400);
    }

    $job::dispatch($domain, $data);
    return response('', 201);
})->middleware('auth.webhook.gdpr');