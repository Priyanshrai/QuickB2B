<?php

namespace App\Http\Controllers;

use App\Models\QuickOrderPage;
use App\Services\ShopifyGraphQL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PageController extends Controller
{
    /**
     * POST /page/sync
     * Manual refresh — sync DB with Shopify.
     */
    public function syncPage(Request $request)
    {
        $shop = Auth::user();
        $existing = QuickOrderPage::where('user_id', Auth::id())->first();
        $shopifyPage = ShopifyGraphQL::fetchQuickOrderPage($shop);

        if ($shopifyPage) {
            // Page exists on Shopify → upsert
            if ($existing) {
                $existing->update([
                    'shopify_page_id'=> $shopifyPage['id'],
                    'title'          => $shopifyPage['title'],
                    'handle'         => $shopifyPage['handle'],
                    'is_published'   => $shopifyPage['isPublished'] ?? true,
                    'page_url'       => 'https://' . $shop->getDomain()->toNative() . '/pages/' . $shopifyPage['handle'],
                ]);
                // Update body to include redirect if it's old
                $body = $shopifyPage['body'] ?? '';
                if (!str_contains($body, '/apps/quick-order')) {
                    ShopifyGraphQL::updatePageBody($shop, $shopifyPage['id'],
                        \App\Models\QuickOrderPage::PAGE_MARKER
                        . '<p>Redirecting to Quick Order...</p>'
                        . '<script>window.location.href="/apps/quick-order"</script>'
                    );
                }
                return back()->with('success', '🔄 Synced! Page is live.');
            }
            QuickOrderPage::create([
                'user_id'        => Auth::id(),
                'shopify_page_id'=> $shopifyPage['id'],
                'title'          => $shopifyPage['title'],
                'handle'         => $shopifyPage['handle'],
                'is_published'   => $shopifyPage['isPublished'] ?? true,
                'menu_linked'    => false,
                'page_url'       => 'https://' . $shop->getDomain()->toNative() . '/pages/' . $shopifyPage['handle'],
            ]);
            return back()->with('success', '🔄 Page found and synced!');
        }

        // Page NOT on Shopify — remove from DB if exists
        if ($existing) {
            $existing->delete();
            return back()->with('success', '🔄 Page no longer exists on Shopify. Removed from dashboard.');
        }

        return back()->with('error', 'No Quick Order page found on your store.');
    }

    /**
     * POST /page/update-title
     * Update the page title on Shopify and in DB.
     */
    public function updateTitle(Request $request)
    {
        $shop = Auth::user();
        $page = QuickOrderPage::where('user_id', Auth::id())->firstOrFail();

        $validated = $request->validate([
            'title' => 'required|string|min:1|max:255',
        ]);

        $result = ShopifyGraphQL::updatePageTitle($shop, $page->shopify_page_id, $validated['title']);

        $userErrors = $result['userErrors'] ?? [];
        if (!empty($userErrors)) {
            return back()->with('error', 'Title update failed: ' . $userErrors[0]['message']);
        }

        $page->update(['title' => $result['page']['title'] ?? $validated['title']]);

        return back()->with('success', '✅ Page title updated to "' . $page->title . '".');
    }

    /**
     * POST /page/delete
     * Delete page from Shopify, remove from menu, delete DB record.
     */
    public function deletePage(Request $request)
    {
        $shop = Auth::user();
        $page = QuickOrderPage::where('user_id', Auth::id())->firstOrFail();

        // ── Step 1: Remove from menu (if linked) ────────
        if ($page->menu_linked && $page->shopify_menu_id) {
            try {
                ShopifyGraphQL::removePageFromMenu($shop, $page->shopify_menu_id, $page->shopify_page_id);
            } catch (\Throwable $e) {
                Log::warning('QuickB2B: could not remove from menu during delete', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // ── Step 2: Delete from Shopify ──────────────────
        $result = ShopifyGraphQL::deletePage($shop, $page->shopify_page_id);
        $userErrors = $result['userErrors'] ?? [];

        if (!empty($userErrors)) {
            Log::warning('QuickB2B pageDelete userErrors', ['errors' => $userErrors]);
            // Still clean up DB — page might have been deleted manually
        }

        // ── Step 3: Delete from DB ──────────────────────
        $pageTitle = $page->title;
        $page->delete();

        return back()->with('success', '🗑️ Page "' . $pageTitle . '" deleted from store and navigation.');
    }

    /**
     * POST /page/link-menu
     * Add page to the main navigation menu.
     */
    public function linkToMenu(Request $request)
    {
        $shop = Auth::user();
        $page = QuickOrderPage::where('user_id', Auth::id())->firstOrFail();

        if ($page->menu_linked) {
            return back()->with('error', 'Page is already linked in navigation.');
        }

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
            return back()->with('error', 'No navigation menu found in your store.');
        }

        $result = ShopifyGraphQL::addPageToMenu(
            $shop,
            $targetMenu['id'],
            $targetMenu['title'],
            $page->shopify_page_id,
            $page->title
        );

        $menuErrors = $result['userErrors'] ?? [];
        if (!empty($menuErrors)) {
            return back()->with('error', 'Menu link failed: ' . $menuErrors[0]['message']);
        }

        $page->update([
            'shopify_menu_id' => $targetMenu['id'],
            'menu_linked'     => true,
        ]);

        return back()->with('success', '🔗 Page linked in "' . $targetMenu['title'] . '" menu.');
    }
}
