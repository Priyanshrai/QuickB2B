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
