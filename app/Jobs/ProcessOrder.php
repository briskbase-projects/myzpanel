<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Http\Traits\ZalandoAPI;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\WooCommerceStockService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ZalandoAPI;

    protected $orderId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::beginTransaction();
        try {
            // Check if the order is already exported
            $alreadyExported = DB::table('exported_orders')->where('order_id', $this->orderId)->exists();
            if ($alreadyExported) {
                Log::info('Order ID ' . $this->orderId . ' has already been exported. Skipping process.');
                DB::rollBack(); // Rollback if order is already exported (no further action needed)
                return;
            }

            $order = $this->getZOrderDetail($this->orderId); // Replace this with your method to get the order by ID
            $items = $order->included ?? [];
            if (count($items) == 0) {
                Log::error('Order Item not found: ' . $order);
                DB::rollBack();
                return 0;
            }

            $stock = [];
            foreach ($items as $item) {
                if ($item->type == "OrderItem") {
                    $sku = $item->attributes->external_id;

                    // Retrieve product with matching SKU
                    $product = DB::table('shopify_products')->whereJsonContains('zalando_sizes', [['sku' => $sku]])->first();
                    if ($product) {
                        $zalandoSizes = json_decode($product->zalando_sizes, true) ?? [];
                        foreach ($zalandoSizes as &$size) {
                            if ($size['sku'] === $sku) {
                                try {
                                    $wooCommerceService = app(WooCommerceStockService::class);
                                    $wooCommerceService->minusWooCommerceStock($sku, 1);
                                } catch (\Exception $e) {
                                    Log::error('Error processing order: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
                                }

                                foreach (config('channels') as $cid => $cdata) {
                                    $pQuantity = $size['quantity_' . $cdata['country']]??0;
                                    $size['quantity_' . $cdata['country']] = max(0, $pQuantity - 1);
                                    if ($cdata['status']) {
                                        $stock[] = '{
                                          "ean": "' . $size['ean'] . '",
                                          "sales_channel_id": "' . $cdata['id'] . '",
                                          "quantity": ' . ($size['quantity_' . $cdata['country']] ?? 0) . '
                                        }';
                                    }
                                }
                            }
                        }

                        // Update the product's zalando_sizes in the database
                        DB::table('shopify_products')
                            ->where('id', $product->id)
                            ->update(['zalando_sizes' => json_encode($zalandoSizes)]);
                    }
                }
            }

            $orderExported = $this->exportZalandoOrder($this->orderId, (time() . "abc"));
            if (isset($orderExported["exported"]) && $orderExported["exported"] === TRUE) {
                Log::error('Order exported: ' . ($orderExported == true ? "Yes" : $orderExported) . " | Order ID:" . $this->orderId);
                if (count($stock)) {
                    // Push stock to Zalando
                    $stockPushed = $this->postProductStock($stock);

                    // Log::error('Stock Pushed: ' . json_encode($stockPushed)." Order ID:".$this->orderId);
                }
                // Log the exported order to the database
                DB::table('exported_orders')->insert([
                    'order_id' => $this->orderId,
                    'exported_at' => now(),
                ]);
                DB::commit();
            } else {
                throw new \Exception('Failed to export order. HTTP Status Code: ' . $orderExported);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing order: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
        }
    }
}
