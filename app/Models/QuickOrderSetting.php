<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuickOrderSetting extends Model
{
    protected $fillable = ['user_id', 'settings'];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Default settings — used when no row exists yet.
     */
    public static function defaults(): array
    {
        return [
            'min_qty'     => null,
            'max_qty'     => null,
            'hide_oos'    => true,
            'hide_sku'    => false,
            'hide_stock'  => false,
            'hide_tags'   => [],
            'show_images' => false,
        ];
    }

    /**
     * Get effective settings for a shop (merged with defaults).
     */
    public function effective(): array
    {
        return array_merge(static::defaults(), $this->settings ?? []);
    }

    /**
     * Get effective settings for a shop by user_id.
     */
    public static function forShop(int $userId): array
    {
        $row = static::where('user_id', $userId)->first();

        return $row ? $row->effective() : static::defaults();
    }
}
