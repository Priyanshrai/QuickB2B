<?php

namespace App\Http\Controllers;

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
        $api = $shop->api();

        $results = [
            'page' => null,
            'menu' => null,
        ];

        // ─── Step 1: Create the page ─────────────────────────────────
        $pageMutation = <<<'GQL'
            mutation pageCreate($page: PageCreateInput!) {
                pageCreate(page: $page) {
                    page {
                        id
                        title
                        handle
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        GQL;

        $pageResponse = $api->graph($pageMutation, [
            'page' => [
                'title'      => 'Quick Order',
                'bodyHtml'   => '<p>Loading QuickB2B order form...</p>',
                'isPublished'=> true,
            ],
        ]);

        // Check for HTTP/network errors
        if (!empty($pageResponse['errors'])) {
            Log::error('QuickB2B pageCreate API error', ['errors' => $pageResponse['errors']]);
            return back()->with('error', 'Page creation failed due to API error.');
        }

        $body = $pageResponse['body'];
        $userErrors = $body['data']['pageCreate']['userErrors'] ?? [];

        if (!empty($userErrors)) {
            Log::error('QuickB2B pageCreate failed', ['errors' => $userErrors]);
            return back()->with('error', 'Page creation failed: ' . $userErrors[0]['message']);
        }

        $page = $body['data']['pageCreate']['page'];
        $results['page'] = $page;
        Log::info('QuickB2B page created', ['page' => $page]);

        // ─── Step 2: Find the main navigation menu ──────────────────
        $menuQuery = <<<'GQL'
            query {
                menus(first: 10) {
                    edges {
                        node {
                            id
                            title
                            handle
                        }
                    }
                }
            }
        GQL;

        $menuResponse = $api->graph($menuQuery);

        if (!empty($menuResponse['errors'])) {
            Log::error('QuickB2B menus query API error', ['errors' => $menuResponse['errors']]);
            return back()->with('error', 'Could not fetch navigation menus due to API error.');
        }

        $menuBody = $menuResponse['body'];
        $menus = $menuBody['data']['menus']['edges'] ?? [];

        // Prefer "main-menu", fallback to the first available menu
        $targetMenu = null;
        foreach ($menus as $edge) {
            $menu = $edge['node'];
            if ($menu['handle'] === 'main-menu') {
                $targetMenu = $menu;
                break;
            }
        }
        if (!$targetMenu && !empty($menus)) {
            $targetMenu = $menus[0]['node'];
        }

        if (!$targetMenu) {
            Log::warning('QuickB2B: No navigation menu found', ['menus' => $menus]);
            return back()->with('success', '✅ Page "Quick Order" created! But no menu was found to link it. You may need to add it manually.');
        }

        // ─── Step 3: Add the page to the menu ──────────────────────
        $menuMutation = <<<'GQL'
            mutation menuUpdate($id: ID!, $items: [MenuItemCreateInput!]!) {
                menuUpdate(id: $id, items: $items) {
                    menu {
                        id
                        title
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        GQL;

        $menuUpdateResponse = $api->graph($menuMutation, [
            'id'    => $targetMenu['id'],
            'items' => [
                [
                    'title'      => 'Quick Order',
                    'resourceId' => $page['id'],
                    'type'       => 'PAGE',
                ],
            ],
        ]);

        if (!empty($menuUpdateResponse['errors'])) {
            Log::error('QuickB2B menuUpdate API error', ['errors' => $menuUpdateResponse['errors']]);
            return back()->with('success', '✅ Page "Quick Order" created! But adding to menu failed due to API error.');
        }

        $menuBody = $menuUpdateResponse['body'];
        $menuErrors = $menuBody['data']['menuUpdate']['userErrors'] ?? [];

        if (!empty($menuErrors)) {
            Log::error('QuickB2B menuUpdate failed', ['errors' => $menuErrors]);
            return back()->with('success', '✅ Page "Quick Order" created! But adding to menu failed: ' . $menuErrors[0]['message']);
        }

        $results['menu'] = $menuBody['data']['menuUpdate']['menu'];
        Log::info('QuickB2B page added to menu', $results);

        return back()->with('success', '✅ All done! The "Quick Order" page is live and linked in your store\'s navigation menu.');
    }
}
