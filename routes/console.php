<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sync Shopify App Pricing subscriptions daily
// If plan_id is set but subscription was cancelled on Shopify, this deactivates it
Schedule::job(\App\Jobs\SyncSubscriptionsJob::class)->dailyAt('03:00');
