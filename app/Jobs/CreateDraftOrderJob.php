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

    private const BATCH_SIZE = 499;
    private const DELAY_SECONDS = 60;
    private const MAX_RETRIES = 3;

    protected string $shopDomain;
    protected array $lineItems;
    protected ?string $email;
    protected int $chunkIndex;
    protected array $ordersCreated;

    /**
     * @param string      $shopDomain     The shop domain
     * @param array       $lineItems      All line items for the full order
     * @param string|null $email          Customer email for invoice
     * @param int         $chunkIndex     Which chunk to process (0 = first, internal use)
     * @param array       $ordersCreated  Accumulated results from previous chunks
     */
    public function __construct(
        string $shopDomain,
        array $lineItems,
        ?string $email = null,
        int $chunkIndex = 0,
        array $ordersCreated = []
    ) {
        $this->shopDomain = $shopDomain;
        $this->lineItems = $lineItems;
        $this->email = $email;
        $this->chunkIndex = $chunkIndex;
        $this->ordersCreated = $ordersCreated;
    }

    public function handle(): void
    {
        $dir = "quickb2b/{$this->shopDomain}";
        $progressKey = "{$dir}/draft_order_progress.json";

        $total = count($this->lineItems);
        $chunks = array_chunk($this->lineItems, self::BATCH_SIZE);
        $totalOrders = count($chunks);
        $orderNum = $this->chunkIndex + 1;

        // First chunk: initialize progress
        if ($this->chunkIndex === 0) {
            Storage::put($progressKey, json_encode([
                'status' => 'started',
                'total' => $total,
                'orders_total' => $totalOrders,
                'orders_done' => 0,
                'message' => "Creating {$totalOrders} draft order(s)...",
            ]));
        }

        try {
            $shop = \App\Models\User::where('name', $this->shopDomain)->first();
            if (!$shop) {
                Storage::put($progressKey, json_encode(['status' => 'failed', 'error' => 'Shop not found']));
                return;
            }

            $chunk = $chunks[$this->chunkIndex];

            Storage::put($progressKey, json_encode([
                'status' => 'processing',
                'total' => $total,
                'orders_total' => $totalOrders,
                'orders_done' => $this->chunkIndex,
                'message' => $totalOrders > 1
                    ? "Creating draft order {$orderNum} of {$totalOrders}..."
                    : "Creating draft order...",
            ]));

            // Build line items
            $draftLineItems = array_map(fn($item) => [
                'variantId' => $item['id'],
                'quantity' => (int) $item['qty'],
            ], $chunk);

            // Retry up to MAX_RETRIES for transient failures
            $result = null;
            $lastError = null;
            for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
                try {
                    $result = ShopifyGraphQL::createDraftOrder($shop, $draftLineItems, $this->email);
                    break;
                } catch (\Throwable $e) {
                    $lastError = $e->getMessage();
                    Log::warning('QuickB2B: Draft order attempt failed', [
                        'order_num' => $orderNum,
                        'attempt' => $attempt,
                        'error' => $lastError,
                    ]);
                    if ($attempt < self::MAX_RETRIES) {
                        sleep(5);
                    }
                }
            }

            $chunkFailed = false;
            if (!$result) {
                Log::error('QuickB2B: Draft order failed after ' . self::MAX_RETRIES . ' retries', [
                    'order_num' => $orderNum,
                    'error' => $lastError,
                ]);
                $chunkFailed = true;
            } elseif (!empty($result['userErrors'])) {
                Log::error('QuickB2B: Draft order failed', [
                    'order_num' => $orderNum,
                    'errors' => $result['userErrors'],
                ]);
                $chunkFailed = true;
            }

            if (!$chunkFailed) {
                $draftOrder = $result['draftOrder'] ?? [];
                $draftOrderId = $draftOrder['id'] ?? null;

                $invoiceUrl = null;
                if ($draftOrderId && $this->email) {
                    $invoiceUrl = ShopifyGraphQL::sendDraftOrderInvoice($shop, $draftOrderId);
                }

                $this->ordersCreated[] = [
                    'name' => $draftOrder['name'] ?? "Draft #{$orderNum}",
                    'invoice_url' => $invoiceUrl,
                ];
            }

            $completedSoFar = count($this->ordersCreated);

            // ── Chain next chunk or finalize ──────────────────────
            $nextIndex = $this->chunkIndex + 1;
            if ($nextIndex < $totalOrders) {
                // Update progress and dispatch next chunk with delay
                Storage::put($progressKey, json_encode([
                    'status' => 'processing',
                    'total' => $total,
                    'orders_total' => $totalOrders,
                    'orders_done' => $completedSoFar,
                    'message' => "Completed {$completedSoFar} of {$totalOrders}. Next in " . self::DELAY_SECONDS . "s...",
                ]));

                static::dispatch(
                    $this->shopDomain,
                    $this->lineItems,
                    $this->email,
                    $nextIndex,
                    $this->ordersCreated
                )->delay(now()->addSeconds(self::DELAY_SECONDS));
            } else {
                // All chunks done — finalize
                $failed = $totalOrders - $completedSoFar;

                $message = $completedSoFar > 0
                    ? "{$completedSoFar} draft order(s) created."
                    : "No draft orders could be created.";
                if ($completedSoFar > 0 && $this->email) {
                    $message .= " Invoice(s) sent to {$this->email}.";
                }
                if ($failed > 0) {
                    $message .= " {$failed} order(s) failed.";
                }

                Storage::put($progressKey, json_encode([
                    'status' => $completedSoFar > 0 ? 'complete' : 'failed',
                    'total' => $total,
                    'orders_total' => $totalOrders,
                    'orders_done' => $completedSoFar,
                    'orders' => $this->ordersCreated,
                    'message' => $message,
                ]));

                Log::info('QuickB2B: Draft orders completed', [
                    'shop' => $this->shopDomain,
                    'succeeded' => $completedSoFar,
                    'failed' => $failed,
                ]);
            }
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
