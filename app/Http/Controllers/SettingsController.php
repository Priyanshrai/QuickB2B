<?php

namespace App\Http\Controllers;

use App\Models\QuickOrderSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    /**
     * GET /settings — Show settings form.
     */
    public function index()
    {
        $settings = QuickOrderSetting::forShop(Auth::id());

        return view('settings.index', [
            'settings' => $settings,
        ]);
    }

    /**
     * POST /settings — Save settings.
     */
    public function save(Request $request)
    {
        $validated = $request->validate([
            'min_qty'    => 'nullable|integer|min:1',
            'max_qty'    => 'nullable|integer|min:1',
            'hide_oos'   => 'boolean',
            'hide_sku'   => 'boolean',
            'hide_stock' => 'boolean',
            'hide_tags'  => 'nullable|string|max:500',
            'image_size' => 'nullable|in:,100x100,200x200,300x300',
        ]);

        // Normalize hide_tags: comma-separated string → array
        $tags = $validated['hide_tags'] ?? '';
        $tagsArray = $tags ? array_map('trim', explode(',', $tags)) : [];
        $tagsArray = array_filter($tagsArray);

        $settings = [
            'min_qty'    => $validated['min_qty'] ? (int) $validated['min_qty'] : null,
            'max_qty'    => $validated['max_qty'] ? (int) $validated['max_qty'] : null,
            'hide_oos'   => (bool) ($validated['hide_oos'] ?? false),
            'hide_sku'   => (bool) ($validated['hide_sku'] ?? false),
            'hide_stock' => (bool) ($validated['hide_stock'] ?? false),
            'hide_tags'  => array_values($tagsArray),
            'image_size' => $validated['image_size'] ?: null,
        ];

        QuickOrderSetting::updateOrCreate(
            ['user_id' => Auth::id()],
            ['settings' => $settings]
        );

        Log::info('QuickB2B: Settings saved', [
            'shop'     => Auth::user()->getDomain()->toNative(),
            'settings' => $settings,
        ]);

        return back()->with('success', '✅ Settings saved! Changes are live on your Quick Order page.');
    }
}
