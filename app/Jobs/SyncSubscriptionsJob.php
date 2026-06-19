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
        $api = new PartnerApi();

        // Only verify shops that think they have a plan
        $shops = User::whereNotNull('plan_id')->get();

        if ($shops->isEmpty()) {
            Log::info('SyncSubscriptions: No active subscriptions to check');
            return;
        }

        $deactivated = 0;

        foreach ($shops as $shop) {
            $stillActive = $api->syncShopSubscription($shop);
            if (!$stillActive) {
                $deactivated++;
            }
        }

        if ($deactivated > 0) {
            Log::warning("SyncSubscriptions: {$deactivated} shop(s) subscription ended");
        }
    }
}
