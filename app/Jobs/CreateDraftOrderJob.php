<?php

namespace App\Jobs;

use App\Services\ShopifyGraphQL;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CreateDraftOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $shopDomain;
    protected array $lineItems;
    protected ?string $email;

    public function __construct(string $shopDomain, array $lineItems, ?string $email = null)
    {
        $this->shopDomain = $shopDomain;
        $this->lineItems = $lineItems;
        $this->email = $email;
    }

    public function handle(): void
    {
        $dir = "quickb2b/{$this->shopDomain}";
        $progressKey = "{$dir}/draft_order_progress.json";

        $total = count($this->lineItems);
        $batchSize = 499;
        $totalOrders = (int) ceil($total / $batchSize);

        Storage::put($progressKey, json_encode([
            'status' => 'started',
            'total' => $total,
            'orders_total' => $totalOrders,
            'orders_done' => 0,
            'message' => "Creating {$totalOrders} draft order(s)...",
        ]));

        try {
            $shop = \App\Models\User::where('name', $this->shopDomain)->first();
            if (!$shop) {
                Storage::put($progressKey, json_encode(['status' => 'failed', 'error' => 'Shop not found']));
                return;
            }

            $ordersCreated = [];
            $chunks = array_chunk($this->lineItems, $batchSize);

            foreach ($chunks as $index => $chunk) {
                $orderNum = $index + 1;
                $msg = $totalOrders > 1
                    ? "Creating draft order {$orderNum} of {$totalOrders}..."
                    : "Creating draft order...";

                Storage::put($progressKey, json_encode([
                    'status' => 'processing',
                    'total' => $total,
                    'orders_total' => $totalOrders,
                    'orders_done' => $index,
                    'message' => $msg,
                ]));

                // Build line items
                $draftLineItems = array_map(fn($item) => [
                    'variantId' => $item['id'],
                    'quantity' => (int) $item['qty'],
                ], $chunk);

                // Retry up to 3 times for transient failures (timeout, etc.)
                $result = null;
                $lastError = null;
                for ($attempt = 1; $attempt <= 3; $attempt++) {
                    try {
                        $result = ShopifyGraphQL::createDraftOrder($shop, $draftLineItems, $this->email);
                        break; // succeeded
                    } catch (\Throwable $e) {
                        $lastError = $e->getMessage();
                        Log::warning('QuickB2B: Draft order attempt failed', [
                            'order_num' => $orderNum,
                            'attempt' => $attempt,
                            'error' => $lastError,
                        ]);
                        if ($attempt < 3) {
                            sleep(5); // wait 5 sec before retry
                        }
                    }
                }

                if (!$result) {
                    Log::error('QuickB2B: Draft order failed after 3 retries', [
                        'order_num' => $orderNum,
                        'error' => $lastError,
                    ]);
                    continue;
                }

                if (!empty($result['userErrors'])) {
                    Log::error('QuickB2B: Draft order failed', [
                        'order_num' => $orderNum,
                        'errors' => $result['userErrors'],
                    ]);
                    continue;
                }

                $draftOrder = $result['draftOrder'] ?? [];
                $draftOrderId = $draftOrder['id'] ?? null;

                // Send invoice
                $invoiceUrl = null;
                if ($draftOrderId && $this->email) {
                    $invoiceUrl = ShopifyGraphQL::sendDraftOrderInvoice($shop, $draftOrderId);
                }

                $ordersCreated[] = [
                    'name' => $draftOrder['name'] ?? "Draft #{$orderNum}",
                    'invoice_url' => $invoiceUrl,
                ];

                Storage::put($progressKey, json_encode([
                    'status' => 'processing',
                    'total' => $total,
                    'orders_total' => $totalOrders,
                    'orders_done' => $orderNum,
                    'message' => "Completed {$orderNum} of {$totalOrders}",
                ]));

                // 1 minute gap between orders (except last)
                if ($orderNum < $totalOrders) {
                    sleep(60);
                }
            }

            Storage::put($progressKey, json_encode([
                'status' => 'complete',
                'total' => $total,
                'orders_total' => $totalOrders,
                'orders_done' => $totalOrders,
                'orders' => $ordersCreated,
                'message' => $this->email
                    ? "{$totalOrders} draft order(s) created. Invoice(s) sent to {$this->email}."
                    : "{$totalOrders} draft order(s) created.",
            ]));

            Log::info('QuickB2B: Draft orders created', [
                'shop' => $this->shopDomain,
                'count' => $totalOrders,
            ]);
        } catch (\Throwable $e) {
            Log::error('QuickB2B: Draft order job failed', [
                'shop' => $this->shopDomain,
                'error' => $e->getMessage(),
            ]);
            Storage::put($progressKey, json_encode([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]));
        }
    }
}
