<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Osiset\ShopifyApp\Util;

// Privacy Policy (public, no auth required)
Route::get('/privacy', function () {
    return response()->file(public_path('privacy-policy.html'));
});

// App Proxy — storefront Quick Order page + API (signed by Shopify, public)
Route::middleware(['auth.proxy', 'throttle:120,1', 'check.subscription'])->group(function () {
    Route::get('/apps/quick-order/sample-csv', function () {
        return response()->download(public_path('sample-order.csv'));
    });

    Route::get('/apps/quick-order', function () {
        $shop = Auth::user();
        if (!$shop) {
            return response('Unauthorized', 401);
        }
        $settings = \App\Models\QuickOrderSetting::forShop(Auth::id());

        // Check if cache actually has images (not just setting enabled)
        $hasImages = false;
        if ($settings['show_images'] ?? false) {
            $cachePath = 'quickb2b/' . $shop->getDomain()->toNative() . '/products.json';
            if (Storage::exists($cachePath)) {
                $sample = json_decode(Storage::get($cachePath), true) ?: [];
                // Check first 50 products for any image_url
                foreach (array_slice($sample, 0, 50) as $p) {
                    if (!empty($p['image_url'])) {
                        $hasImages = true;
                        break;
                    }
                }
            }
        }

        // Get shop currency (cached in settings, fetched once on first load)
        $currency = $settings['_currency'] ?? null;
        if (!$currency) {
            $currency = \App\Services\ShopifyGraphQL::shopCurrency($shop);
            if ($currency) {
                // Persist in settings so we don't fetch every time
                $row = \App\Models\QuickOrderSetting::where('user_id', Auth::id())->first();
                if ($row) {
                    $s = $row->settings ?? [];
                    $s['_currency'] = $currency;
                    $row->update(['settings' => $s]);
                }
            }
        }

        return response(
            view('quick-order.proxy', [
                'shopDomain' => $shop->getDomain()->toNative(),
                'settings'   => $settings,
                'currency'   => $currency ?: 'USD',
                'hasImages'  => $hasImages,
            ])
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

// Home — Dashboard (saves plan_handle from Shopify redirect)
Route::get('/', function () {
    $shop = Auth::user();
    if ($shop && $planHandle = request('plan_handle')) {
        $shop->update(['plan_id' => $planHandle]);
    }
    return view('welcome');
})->middleware(['verify.shopify'])->name('home');

// Billing — Plans page + cleanup routes (always accessible, no subscription check)
Route::middleware(['verify.shopify'])->group(function () {
    Route::get('/subscription', function () {
        return view('billing.plans');
    })->name('plans');

    // Allow page deletion & menu unlink without an active plan
    // (so users can clean up before uninstalling the app)
    Route::post('/page/delete', [\App\Http\Controllers\PageController::class, 'deletePage'])
        ->name('page.delete');
    Route::post('/page/unlink-menu', [\App\Http\Controllers\PageController::class, 'unlinkFromMenu'])
        ->name('page.unlink-menu');

    // ── TEMP: Test Partner API — dispatches SyncSubscriptionsJob ──
    Route::post('/subscription/test-partner-api', function () {
        \App\Jobs\SyncSubscriptionsJob::dispatch();
        return response()->json(['ok' => true, 'message' => 'SyncSubscriptionsJob dispatched. Check laravel.log']);
    })->name('test.partner-api');
});

// ── All other Admin routes (require active subscription) ────────
Route::middleware(['verify.shopify', 'check.subscription'])->group(function () {

    // One-click setup: create Quick Order page + add to navigation menu
    Route::post('/setup/create-page', [\App\Http\Controllers\SetupController::class, 'createPageAndMenu'])
        ->name('setup.create-page');

    // Page management
    Route::post('/page/update-title', [\App\Http\Controllers\PageController::class, 'updateTitle'])
        ->name('page.update-title');
    Route::post('/page/link-menu', [\App\Http\Controllers\PageController::class, 'linkToMenu'])
        ->name('page.link-menu');
    Route::post('/page/sync', [\App\Http\Controllers\PageController::class, 'syncPage'])
        ->name('page.sync');

    // Settings
    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'index'])
        ->name('settings.index');
    Route::post('/settings', [\App\Http\Controllers\SettingsController::class, 'save'])
        ->name('settings.save');

    // Help / FAQ
    Route::get('/help', function () {
        return view('help.index');
    })->name('help');

    // Catalog refresh (admin-side)
    Route::post('/catalog/refresh', [\App\Http\Controllers\QuickOrderController::class, 'refreshProducts'])
        ->name('catalog.refresh');
    Route::get('/catalog/status', [\App\Http\Controllers\QuickOrderController::class, 'productsStatus'])
        ->name('catalog.status');
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