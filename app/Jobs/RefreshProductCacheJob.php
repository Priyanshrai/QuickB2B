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

class RefreshProductCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $shopDomain;

    public function __construct(string $shopDomain)
    {
        $this->shopDomain = $shopDomain;
    }

    public function handle(): void
    {
        $dir = "quickb2b/{$this->shopDomain}";
        Storage::makeDirectory($dir);

        $this->setProgress('starting', 0);

        try {
            $shop = \App\Models\User::where('name', $this->shopDomain)->first();
            if (!$shop) {
                $this->setProgress('failed', 0, 'Shop not found');
                return;
            }

            // Step 1: Start bulk operation — query variants with product info nested
            $this->setProgress('querying', 10);

            $productQuery = <<<'GQL'
                {
                    productVariants {
                        edges {
                            node {
                                id
                                title
                                sku
                                price
                                inventoryQuantity
                                inventoryPolicy
                                product { id title }
                            }
                        }
                    }
                }
            GQL;

            $data = ShopifyGraphQL::query($shop, '
                mutation bulkOperationRunQuery($query: String!) {
                    bulkOperationRunQuery(query: $query) {
                        bulkOperation { id status }
                        userErrors { field message }
                    }
                }
            ', ['query' => $productQuery]);

            if (empty($data['bulkOperationRunQuery']['bulkOperation'])) {
                $this->setProgress('failed', 0, 'Could not start bulk operation');
                return;
            }

            // Step 2: Poll until complete
            $pollQuery = '
                query {
                    currentBulkOperation { id status url errorCode objectCount }
                }
            ';

            $url = null;
            for ($i = 0; $i < 60; $i++) {
                $pollData = ShopifyGraphQL::query($shop, $pollQuery);
                $status = $pollData['currentBulkOperation']['status'] ?? '';

                if ($status === 'COMPLETED') {
                    $url = $pollData['currentBulkOperation']['url'] ?? '';
                    break;
                }
                if ($status === 'FAILED') {
                    $this->setProgress('failed', 0, 'Bulk operation failed');
                    return;
                }

                if ($i % 3 === 0) {
                    $count = $pollData['currentBulkOperation']['objectCount'] ?? '0';
                    $this->setProgress('processing', min(20 + ($i * 2), 80), null, (int) $count);
                }

                sleep(1);
            }

            if (!$url) {
                $this->setProgress('failed', 0, 'Operation timed out');
                return;
            }

            // Step 3: Download & parse JSONL
            $this->setProgress('downloading', 85);

            $jsonl = file_get_contents($url);
            $products = [];

            foreach (explode("\n", $jsonl) as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                $obj = json_decode($line, true);
                // Each line is a variant with nested product
                $product = $obj['product'] ?? [];

                $products[] = [
                    'id'            => $product['id'] ?? '',
                    'title'         => $product['title'] ?? '',
                    'variant_id'    => $obj['id'] ?? '',
                    'variant_title' => $obj['title'] ?? '',
                    'sku'           => $obj['sku'] ?? '',
                    'price'         => $obj['price'] ?? '0.00',
                    'inventory'         => $obj['inventoryQuantity'] ?? 0,
                    'inventory_tracked' => ($obj['inventoryPolicy'] ?? '') === 'DENY',
                ];
            }

            // Step 4: Save to file
            Storage::put("{$dir}/products.json", json_encode($products));
            $this->setProgress('complete', 100, null, count($products));

            Log::info('QuickB2B: Product cache refreshed', [
                'shop'  => $this->shopDomain,
                'count' => count($products),
            ]);
        } catch (\Throwable $e) {
            Log::error('QuickB2B: Product cache job failed', [
                'shop'  => $this->shopDomain,
                'error' => $e->getMessage(),
            ]);
            $this->setProgress('failed', 0, $e->getMessage());
        }
    }

    private function setProgress(string $status, int $percent, ?string $error = null, ?int $records = null): void
    {
        Storage::put("quickb2b/{$this->shopDomain}/progress.json", json_encode([
            'status'    => $status,
            'percent'   => $percent,
            'error'     => $error,
            'records'   => $records,
            'timestamp' => now()->toIso8601String(),
        ]));
    }
}
