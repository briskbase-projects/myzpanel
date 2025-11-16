<?php

namespace App\Services;

use App\Http\Traits\ZalandoAPI;
use Automattic\WooCommerce\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WooCommerceStockService
{
    use ZalandoAPI;
    protected $woocommerce;

    public function __construct()
    {
        $this->woocommerce = new Client(
            config('woocommerce.store_url'),
            config('woocommerce.consumer_key'),
            config('woocommerce.consumer_secret'),
            [
                'version' => 'wc/v3',
            ]
        );
    }

    /**
     * Handle WooCommerce Webhook - Update stock in our database.
     */
    public function handleWebhookStockUpdate($lineItems, $action)
    {
        foreach ($lineItems as $item) {
            $sku = $item['sku'] ?? null;
            $quantity = $item['quantity'] ?? 0;

            if (!$sku) {
                Log::warning("Webhook received but SKU is missing.");
                continue;
            }

            // Find product in our database with matching SKU
            $product = DB::table('shopify_products')->whereJsonContains('zalando_sizes', [['sku' => $sku]])->first();

            if ($product) {
                $zalandoSizes = json_decode($product->zalando_sizes, true) ?? [];
                foreach ($zalandoSizes as &$size) {
                    if (strtolower($size['sku']) === strtolower($sku)) {
                        foreach (config('channels') as $cid => $cdata) {
                            if ($action == 'decrease') {
                                $size['quantity_' . $cdata['country']] = max(0, $size['quantity_' . $cdata['country']] - $quantity);
                            } else {
                                $size['quantity_' . $cdata['country']] = max(0, $size['quantity_' . $cdata['country']] + $quantity);
                            }
                        }
                    }
                }

                // Update database with new stock value
                DB::table('shopify_products')
                    ->where('id', $product->id)
                    ->update(['zalando_sizes' => json_encode($zalandoSizes)]);

                // Prepare stock data to push to Zalando
                $stock = [];
                foreach ($zalandoSizes as $size) {
                    foreach (config('channels') as $cid => $cdata) {
                        if ($cdata['status'] === true) {
                            $stock[] = [
                                'ean' => $size['ean'],
                                'sales_channel_id' => $cdata['id'],
                                'quantity' => $size['quantity_' . $cdata['country']] ?? 0,
                            ];
                        }
                    }
                }

                // Push stock to Zalando
                if (count($$stock)) {
                    $this->postProductStock($stock);
                }

                Log::info("Stock updated for SKU: {$sku} after WooCommerce order.");
            }
        }
    }

    /**
     * Update WooCommerce stock when order is placed on our site.
     */
    public function addWooCommerceStock($sku, $quantity)
    {
        try {
            // Search for the product by SKU
            $products = $this->woocommerce->get('products', ['sku' => $sku]);

            if (!empty($products) && isset($products[0])) {
                $product = $products[0];

                $newStock = max(0, $product->stock_quantity + $quantity);
                if ($product->type === 'variation') {

                    $this->woocommerce->put("products/{$product->parent_id}/variations/{$product->id}", [
                        'stock_quantity' => $newStock
                    ]);

                    Log::info("Stock updated for Variation SKU: {$sku}");
                    return;
                }

                $this->woocommerce->put("products/{$product->id}", [
                    'stock_quantity' => $newStock
                ]);

                Log::info("Stock updated for Product SKU: {$sku}");
                return;
            }

            Log::warning("No product or variation found for SKU: {$sku}");
        } catch (\Exception $e) {
            Log::error("Failed to update WooCommerce stock for SKU: {$sku}. Error: " . $e->getMessage());
        }
    }

    /**
     * Update WooCommerce stock when order is placed on our site.
     */
    public function minusWooCommerceStock($sku, $quantity)
    {
        try {
            $products = $this->woocommerce->get('products', ['sku' => $sku]);

            if (!empty($products) && isset($products[0])) {
                $product = $products[0];
                $productId = $product->id;
                $newStock = max(0, $product->stock_quantity - $quantity);

                if ($product->type === 'variation') {
                    $this->woocommerce->put("products/{$product->parent_id}/variations/{$product->id}", [
                        'stock_quantity' => $newStock
                    ]);
                    Log::info("Stock updated for Variation SKU: {$sku}");
                    return;
                }

                $this->woocommerce->put("products/{$productId}", [
                    'stock_quantity' => $newStock
                ]);

                Log::info("WooCommerce stock updated for SKU: {$sku}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to update WooCommerce stock for SKU: {$sku}. Error: " . $e->getMessage());
        }
    }
}
