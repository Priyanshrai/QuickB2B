<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuickOrderPage extends Model
{
    /** Hidden marker in page body to identify pages created by this app. */
    const PAGE_MARKER = '<!-- quickb2b-page -->';

    protected $fillable = [
        'user_id',
        'shopify_page_id',
        'title',
        'handle',
        'shopify_menu_id',
        'is_published',
        'menu_linked',
        'page_url',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'menu_linked'  => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function storeUrl(): string
    {
        return $this->page_url ?: '/pages/' . $this->handle;
    }

    public function isLive(): bool
    {
        return $this->is_published && $this->shopify_page_id !== null;
    }

    public function menuStatusLabel(): string
    {
        if ($this->menu_linked && $this->shopify_menu_id) {
            return '🔗 Linked in navigation';
        }
        return '⚠️ Not in menu';
    }
}
