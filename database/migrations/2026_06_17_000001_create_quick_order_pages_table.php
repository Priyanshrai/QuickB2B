<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quick_order_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('shopify_page_id');   // gid://shopify/Page/123456
            $table->string('title');              // "Quick Order"
            $table->string('handle');             // "quick-order"
            $table->string('shopify_menu_id')->nullable(); // gid://shopify/Menu/789
            $table->boolean('is_published')->default(true);
            $table->boolean('menu_linked')->default(false);
            $table->string('page_url')->nullable(); // https://store.com/pages/quick-order
            $table->timestamps();

            $table->unique('user_id'); // One page per shop
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quick_order_pages');
    }
};
