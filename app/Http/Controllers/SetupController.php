<?php

namespace App\Http\Controllers;

use App\Models\QuickOrderPage;
use App\Services\ShopifyGraphQL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SetupController extends Controller
{
    /**
     * One-click setup: creates the Quick Order page and adds it to the store's main navigation menu.
     *
     * POST /setup/create-page
     */
    public function createPageAndMenu(Request $request)
    {
        $shop = Auth::user();

        // Prevent duplicate — one page per shop
        if (QuickOrderPage::where('user_id', Auth::id())->exists()) {
            return back()->with('error', 'A Quick Order page already exists. Delete it first to create a new one.');
        }

        // ─── Step 1: Create the page ─────────────────────────────────
        $result = ShopifyGraphQL::createPage($shop, 'Quick Order',
            QuickOrderPage::PAGE_MARKER
            . '<p>Redirecting to Quick Order...</p>'
            . '<script>window.location.href="/apps/quick-order"</script>'
        );

        $userErrors = $result['userErrors'] ?? [];
        if (!empty($userErrors)) {
            Log::error('QuickB2B pageCreate failed', ['errors' => $userErrors]);
            return back()->with('error', 'Page creation failed: ' . $userErrors[0]['message']);
        }

        $page = $result['page'];
        Log::info('QuickB2B page created', ['page' => $page]);

        // Save to DB (menu not linked yet)
        $quickPage = QuickOrderPage::create([
            'user_id'        => Auth::id(),
            'shopify_page_id'=> $page['id'],
            'title'          => $page['title'],
            'handle'         => $page['handle'],
            'is_published'   => true,
            'menu_linked'    => false,
            'page_url'       => $shop->getDomain()->toNative() . '/pages/' . $page['handle'],
        ]);

        // ─── Step 2: Find the main navigation menu ──────────────────
        $menus = ShopifyGraphQL::fetchMenus($shop);

        $targetMenu = null;
        foreach ($menus as $menu) {
            if ($menu['handle'] === 'main-menu') {
                $targetMenu = $menu;
                break;
            }
        }
        if (!$targetMenu && !empty($menus)) {
            $targetMenu = $menus[0];
        }

        if (!$targetMenu) {
            Log::warning('QuickB2B: No navigation menu found');
            return back()->with('success', '✅ Page "Quick Order" created! But no menu was found to link it. You may need to add it manually.');
        }

        // ─── Step 3: Add the page to the menu ──────────────────────
        $menuResult = ShopifyGraphQL::addPageToMenu(
            $shop,
            $targetMenu['id'],
            $targetMenu['title'],
            $page['id'],
            'Quick Order'
        );

        $menuErrors = $menuResult['userErrors'] ?? [];
        if (!empty($menuErrors)) {
            Log::error('QuickB2B menuUpdate failed', ['errors' => $menuErrors]);
            return back()->with('success', '✅ Page "Quick Order" created! But adding to menu failed: ' . $menuErrors[0]['message']);
        }

        // Update DB with menu info
        $quickPage->update([
            'shopify_menu_id' => $targetMenu['id'],
            'menu_linked'     => true,
        ]);

        Log::info('QuickB2B full setup complete', [
            'page' => $page,
            'menu' => $menuResult['menu'] ?? null,
        ]);

        return back()->with('success', '✅ All done! The "Quick Order" page is live and linked in your store\'s navigation menu.');
    }
}
