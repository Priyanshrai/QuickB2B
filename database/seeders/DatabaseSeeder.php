<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Billing Plan: Pro ($9.99/month)
        if (!\Osiset\ShopifyApp\Storage\Models\Plan::where('name', 'Pro')->exists()) {
            \Osiset\ShopifyApp\Storage\Models\Plan::create([
                'name'          => 'Pro',
                'type'          => 'RECURRING',
                'interval'      => 'EVERY_30_DAYS',
                'price'         => 9.99,
                'capped_amount' => 0,
                'terms'         => 'Bulk order table, CSV upload, draft orders, email invoices, product images, min/max quantities, stock control, auto currency, priority support',
                'test'          => true,
                'on_install'    => true,
                'trial_days'    => 7,
            ]);
        }
    }
}
