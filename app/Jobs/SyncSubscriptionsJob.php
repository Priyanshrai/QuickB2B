<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\PartnerApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncSubscriptionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Log::debug('[SyncSubscriptionsJob] ========== JOB STARTED ==========');

        Log::debug('[SyncSubscriptionsJob] STEP 1: Creating PartnerApi instance');
        $api = new PartnerApi();

        Log::debug('[SyncSubscriptionsJob] STEP 2: Querying shops with plan_id NOT NULL');
        $shops = User::whereNotNull('plan_id')->get();

        Log::debug('[SyncSubscriptionsJob] STEP 3: Found shops', [
            'count' => $shops->count(),
            'shops' => $shops->map(fn($s) => [
                'domain'  => $s->getDomain()->toNative(),
                'plan_id' => $s->plan_id,
            ])->toArray(),
        ]);

        if ($shops->isEmpty()) {
            Log::info('SyncSubscriptions: No active subscriptions to check');
            Log::debug('[SyncSubscriptionsJob] ========== JOB ENDED (no shops) ==========');
            return;
        }

        $deactivated = 0;

        foreach ($shops as $shop) {
            Log::debug('[SyncSubscriptionsJob] STEP 4: Processing shop', [
                'shop'    => $shop->getDomain()->toNative(),
                'plan_id' => $shop->plan_id,
            ]);

            $stillActive = $api->syncShopSubscription($shop);

            Log::debug('[SyncSubscriptionsJob] STEP 5: syncShopSubscription result', [
                'shop'         => $shop->getDomain()->toNative(),
                'still_active' => $stillActive,
            ]);

            if (!$stillActive) {
                $deactivated++;
            }
        }

        Log::debug('[SyncSubscriptionsJob] STEP 6: Summary', [
            'total_checked' => $shops->count(),
            'deactivated'   => $deactivated,
        ]);

        if ($deactivated > 0) {
            Log::warning("SyncSubscriptions: {$deactivated} shop(s) subscription ended");
        }

        Log::debug('[SyncSubscriptionsJob] ========== JOB ENDED ==========');
    }
}
