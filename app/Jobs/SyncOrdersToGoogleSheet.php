<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Traits\ZalandoAPI;
use App\Http\Traits\GoogleSheetsAPI;
use App\Http\Traits\OrderSyncErrorHandler;
use Carbon\Carbon;

class SyncOrdersToGoogleSheet implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ZalandoAPI, GoogleSheetsAPI, OrderSyncErrorHandler;

    /**
     * Execute the job.
     * This job runs incrementally using last_updated_after (every 15 minutes)
     * With failsafe: if job fails, adds 15 minutes buffer to prevent missing orders
     *
     * @return void
     */
    public function handle()
    {
        try {
            Log::info('SyncOrdersToGoogleSheet (INCREMENTAL) job started');

            // Check if Google Sheets is configured
            $config = $this->getSheetConfig();
            if (!$config) {
                Log::warning('Google Sheets not configured. Skipping sync.');
                return;
            }

            // Get or create tracking record for incremental sync
            $syncTracking = DB::table('sync_tracking')
                ->where('sync_type', 'incremental')
                ->first();

            // Determine the start time for this sync
            // If last sync failed or no successful sync, add 15 min buffer (failsafe)
            $lastSyncTime = null;
            if ($syncTracking && $syncTracking->last_successful_sync) {
                $lastSyncTime = Carbon::parse($syncTracking->last_successful_sync);
            } elseif ($syncTracking && $syncTracking->last_sync_time) {
                // Job failed last time, add 15 min buffer
                $lastSyncTime = Carbon::parse($syncTracking->last_sync_time)->subMinutes(15);
                Log::warning('Previous sync failed. Adding 15 min buffer to prevent missing orders.');
            } else {
                // First time running incremental sync, start from 30 minutes ago
                $lastSyncTime = Carbon::now()->subMinutes(30);
            }

            // Create or update tracking record - mark as running
            $currentSyncTime = now();
            if (!$syncTracking) {
                DB::table('sync_tracking')->insert([
                    'sync_type' => 'incremental',
                    'is_running' => true,
                    'last_sync_time' => $currentSyncTime,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('sync_tracking')
                    ->where('sync_type', 'incremental')
                    ->update([
                        'is_running' => true,
                        'last_sync_time' => $currentSyncTime,
                        'updated_at' => now(),
                    ]);
            }

            // Use last_updated_after for incremental sync
            $filters = [
                'last_updated_after' => $lastSyncTime->format('Y-m-d\TH:i:s\Z'),
            ];

            Log::info("Fetching Zalando orders updated after {$lastSyncTime->format('Y-m-d H:i:s')} (last_updated_after)");

            // Get all orders (approved and exported)
            $ordersResponse = $this->getZalandoOrders($filters);

            if (!$ordersResponse || !isset($ordersResponse->data)) {
                Log::warning('No orders returned from Zalando API');

                // Still mark as successful since API responded (just no data)
                DB::table('sync_tracking')
                    ->where('sync_type', 'incremental')
                    ->update([
                        'is_running' => false,
                        'last_successful_sync' => $currentSyncTime,
                        'error_message' => null,
                        'updated_at' => now(),
                    ]);
                return;
            }

            $orders = $ordersResponse->data;
            Log::info('Found ' . count($orders) . ' orders to process in incremental sync');

            // Reset counts for this sync run
            $this->resetSyncCounts('incremental');

            $syncedCount = 0;
            $updatedCount = 0;
            $failedCount = 0;

            foreach ($orders as $order) {
                $retryCount = 0;
                $maxRetries = 3;
                $orderProcessed = false;

                while ($retryCount < $maxRetries && !$orderProcessed) {
                    try {
                        // Get detailed order information
                        $orderDetail = $this->getZOrderDetail($order->id);

                        if (!$orderDetail || !isset($orderDetail->data)) {
                            throw new \Exception("Could not fetch details for order: {$order->id}");
                        }

                        $orderData = $orderDetail->data;

                        // Only process orders with "fulfilled" status
                        $orderStatus = $orderData->attributes->status ?? '';
                        if ($orderStatus !== 'fulfilled') {
                            $orderProcessed = true; // Skip non-fulfilled orders without retry
                            break;
                        }

                        $this->processOrderItems($orderData, $orderDetail->included ?? [], $syncedCount, $updatedCount, $order->id);

                        // Mark as successful
                        $this->markOrderSyncSuccessful($order->id, 'incremental');
                        $orderProcessed = true;

                    } catch (\Exception $e) {
                        $retryCount++;

                        $orderInfo = [
                            'order_number' => $orderData->attributes->order_number ?? null,
                        ];

                        if ($retryCount >= $maxRetries) {
                            // Failed after max retries
                            $this->handleFailedOrderSync($order->id, 'incremental', $e, $orderInfo);
                            $failedCount++;
                            Log::error("Order {$order->id} failed permanently after {$maxRetries} attempts: " . $e->getMessage());
                        } else {
                            // Log retry attempt
                            Log::warning("Order {$order->id} failed, retrying... Attempt {$retryCount}/{$maxRetries}");
                            sleep(2); // Wait before retry
                        }
                    }
                }
            }

            // Update final counts
            $this->updateSyncCounts('incremental', $syncedCount, $updatedCount, $failedCount, count($orders));

            // Mark as successfully completed
            DB::table('sync_tracking')
                ->where('sync_type', 'incremental')
                ->update([
                    'is_running' => false,
                    'last_successful_sync' => $currentSyncTime,
                    'error_message' => null,
                    'updated_at' => now(),
                ]);

            Log::info("SyncOrdersToGoogleSheet (INCREMENTAL) job completed. Synced: {$syncedCount}, Updated: {$updatedCount}");

        } catch (\Exception $e) {
            Log::error('SyncOrdersToGoogleSheet job failed: ' . $e->getMessage());

            // Mark as failed (keep last_sync_time so next run will add 15 min buffer)
            DB::table('sync_tracking')
                ->where('sync_type', 'incremental')
                ->update([
                    'is_running' => false,
                    'error_message' => $e->getMessage(),
                    'updated_at' => now(),
                ]);

            throw $e;
        }
    }

    /**
     * Process all items in an order
     *
     * @param object $orderData
     * @param array $included
     * @param int &$syncedCount
     * @param int &$updatedCount
     * @param string $orderId
     * @return void
     */
    public function processOrderItemsPublic($orderData, $included, &$syncedCount, &$updatedCount, $orderId)
    {
        return $this->processOrderItems($orderData, $included, $syncedCount, $updatedCount, $orderId);
    }

    /**
     * Process all items in an order (internal)
     *
     * @param object $orderData
     * @param array $included
     * @param int &$syncedCount
     * @param int &$updatedCount
     * @param string $orderId
     * @return void
     */
    private function processOrderItems($orderData, $included, &$syncedCount, &$updatedCount, $orderId)
    {
        $orderNumber = $orderData->attributes->order_number ?? '';
        $orderDate = $orderData->attributes->order_date ?? '';

        // Get customer name from shipping address (not customer object)
        $customerName = trim(
            ($orderData->attributes->shipping_address->first_name ?? '') . ' ' .
            ($orderData->attributes->shipping_address->last_name ?? '')
        );

        // Get country directly from shipping address
        $country = $orderData->attributes->shipping_address->country_code ?? '';

        // Process each order item
        $orderItems = $orderData->relationships->order_items->data ?? [];

        foreach ($orderItems as $itemRef) {
            try {
                // Find the full item data in included resources
                $itemData = $this->findIncludedResource($included, 'OrderItem', $itemRef->id);

                if (!$itemData) {
                    Log::warning("Could not find item data for item ID: {$itemRef->id} in order {$orderNumber}");
                    continue;
                }

                $this->processOrderItem($included, $itemData, $orderNumber, $orderDate, $customerName, $country, $syncedCount, $updatedCount, $orderId);

            } catch (\Exception $e) {
                Log::error("Error processing order item: " . $e->getMessage());
                continue;
            }
        }
    }

    /**
     * Process a single order item
     *
     * @param array $included
     * @param object $itemData
     * @param string $orderNumber
     * @param string $orderDate
     * @param string $customerName
     * @param string $country
     * @param int &$syncedCount
     * @param int &$updatedCount
     * @param string $orderId
     * @return void
     */
    private function processOrderItem($included, $itemData, $orderNumber, $orderDate, $customerName, $country, &$syncedCount, &$updatedCount, $orderId)
    {
        $itemId = $itemData->id;
        $sku = $itemData->attributes->external_id ?? '';

        // Determine status, price, and currency from order lines
        $status = 'Sold'; // Default status
        $returnReason = '';
        $price = 0;
        $currency = 'EUR';

        // Check order lines for status and price
        $orderLines = $itemData->relationships->order_lines->data ?? [];
        foreach ($orderLines as $lineRef) {
            $lineData = $this->findIncludedResource($included, 'OrderLine', $lineRef->id);

            if ($lineData) {
                // Get price from OrderLine (not OrderItem)
                if (!$price && isset($lineData->attributes->price->amount)) {
                    $price = $lineData->attributes->price->amount ?? 0;
                    $currency = $lineData->attributes->price->currency ?? 'EUR';
                }

                // Get status
                $lineStatus = $lineData->attributes->status ?? '';

                // Only process items with: returned, refunded, canceled, or fulfilled status
                // All other statuses (initial, approved, exported) will be skipped
                if ($lineStatus === 'returned' || $lineStatus === 'refunded') {
                    $status = 'zurück';

                    // Fetch transitions from Zalando API to get the actual return/refund reason
                    try {
                        $transitions = $this->getOrderLineTransitions($orderId, $itemId, $lineRef->id);
                        $reasonData = $this->extractTransitionReason($transitions, $lineStatus);

                        // Use description if available, otherwise use code, otherwise use default
                        if (!empty($reasonData['reason_description'])) {
                            $returnReason = $reasonData['reason_description'];
                        } elseif (!empty($reasonData['reason_code'])) {
                            $returnReason = $reasonData['reason_code'];
                        } else {
                            $returnReason = $lineStatus === 'refunded' ? 'Refunded' : 'Artikel gefällt nicht';
                        }
                    } catch (\Exception $e) {
                        Log::warning("Could not fetch transitions for line {$lineRef->id}: " . $e->getMessage());
                        $returnReason = $lineStatus === 'refunded' ? 'Refunded' : 'Artikel gefällt nicht';
                    }

                } elseif ($lineStatus === 'canceled') {
                    $status = 'canceled';

                    // Fetch transitions from Zalando API to get the actual cancellation reason
                    try {
                        $transitions = $this->getOrderLineTransitions($orderId, $itemId, $lineRef->id);
                        $reasonData = $this->extractTransitionReason($transitions, 'canceled');

                        // Use description if available, otherwise use code
                        if (!empty($reasonData['reason_description'])) {
                            $returnReason = $reasonData['reason_description'];
                        } elseif (!empty($reasonData['reason_code'])) {
                            $returnReason = $reasonData['reason_code'];
                        }
                    } catch (\Exception $e) {
                        Log::warning("Could not fetch transitions for line {$lineRef->id}: " . $e->getMessage());
                        // returnReason remains empty string
                    }

                } elseif ($lineStatus === 'fulfilled') {
                    $status = 'Sold';
                } else {
                    // Skip this item - status is not one of: returned, refunded, canceled, fulfilled
                    continue;
                }
            }
        }

        // Fetch product title from shopify_products table using SKU
        $product = DB::table('shopify_products')
            ->whereJsonContains('zalando_sizes', [['sku' => $sku]])
            ->first();

        // Use product title if found, otherwise extract from SKU as fallback
        $productTitle = $product ? $product->title : $this->extractMainProduct($sku);

        // Format price with currency symbol
        $priceFormatted = $this->formatPrice($price, $currency);

        // Format date
        $date = $this->formatDate($orderDate);
        $month = $this->getMonth($orderDate);

        // Prepare row data for Google Sheet
        // Ensure all values are strings
        $rowData = [
            (string)($month ?? ''),              // Month
            (string)($date ?? ''),               // Date
            (string)($customerName ?? ''),       // Name
            (string)($orderNumber ?? ''),        // Order Number
            (string)($country ?? ''),            // Country
            (string)($sku ?? ''),                // Product (SKU)
            (string)($priceFormatted ?? ''),     // Price
            (string)($status ?? ''),             // Status
            (string)($returnReason ?? ''),       // Reason for Return
            (string)($productTitle ?? '')        // Product Title from database
        ];

        // Check if status requires red text (canceled, refunded, returned, zurück)
        $applyRedText = in_array(strtolower($status), ['canceled', 'refunded', 'returned', 'zurück']);

        // Check if this item is already synced
        $syncedItem = DB::table('synced_order_items')
            ->where('order_number', $orderNumber)
            ->where('order_item_id', $itemId)
            ->first();

        if ($syncedItem) {
            // Check if status has changed
            if ($syncedItem->status !== $status) {
                // Update the row in Google Sheet
                if ($syncedItem->sheet_row_number) {
                    if ($this->updateSheetRow($syncedItem->sheet_row_number, $rowData, $applyRedText)) {
                        // Update database record
                        DB::table('synced_order_items')
                            ->where('id', $syncedItem->id)
                            ->update([
                                'status' => $status,
                                'product_title' => $productTitle,
                                'updated_at' => now(),
                            ]);

                        $updatedCount++;
                        Log::info("Updated order item {$orderNumber} - {$sku} from {$syncedItem->status} to {$status}");
                    }
                }
            }
        } else {
            // New item - append to sheet
            if ($this->appendSheetRow($rowData, $applyRedText)) {
                // Get the current row count to determine row number
                $allRows = $this->readSheetData();
                $rowNumber = count($allRows) + 2; // +2 because: +1 for 0-index, +1 for header row

                // Record in database
                DB::table('synced_order_items')->insert([
                    'order_number' => $orderNumber,
                    'order_item_id' => $itemId,
                    'sku' => $sku,
                    'product_title' => $productTitle,
                    'status' => $status,
                    'sheet_row_number' => $rowNumber,
                    'synced_at' => now(),
                    'updated_at' => now(),
                ]);

                $syncedCount++;
                Log::info("Synced new order item: {$orderNumber} - {$sku} with status {$status}");
            } else {
                Log::error("Failed to append row for: {$orderNumber} - {$sku}");
            }
        }
    }

    /**
     * Find a resource in the included array
     *
     * @param array $included
     * @param string $type
     * @param string $id
     * @return object|null
     */
    private function findIncludedResource($included, $type, $id)
    {
        foreach ($included as $resource) {
            if ($resource->type === $type && $resource->id === $id) {
                return $resource;
            }
        }

        return null;
    }

    /**
     * Extract main product name from SKU
     *
     * @param string $sku
     * @return string
     */
    private function extractMainProduct($sku)
    {
        // Extract product name before the first hyphen
        // e.g., "serena-white-38" -> "serena"
        $parts = explode('-', $sku);
        return $parts[0] ?? $sku;
    }

    /**
     * Format price with currency symbol
     *
     * @param float $amount
     * @param string $currency
     * @return string
     */
    private function formatPrice($amount, $currency)
    {
        $symbols = [
            'EUR' => '€',
            'PLN' => 'zł',
            'GBP' => '£',
            'SEK' => 'kr',
            'DKK' => 'kr',
            'CZK' => 'Kč',
            'RON' => 'lei',
            'HUF' => 'Ft',
        ];

        $symbol = $symbols[$currency] ?? $currency;
        // OrderLine price is already in correct format (not in cents)
        $formattedAmount = number_format($amount, 0, ',', '');

        return $symbol . ' ' . $formattedAmount;
    }

    /**
     * Format date
     *
     * @param string $dateString
     * @return string
     */
    private function formatDate($dateString)
    {
        try {
            $date = Carbon::parse($dateString);
            // Format as DD/MM/YYYY to match user's expected format
            return $date->format('d/m/Y');
        } catch (\Exception $e) {
            return $dateString;
        }
    }

    /**
     * Get month abbreviation
     *
     * @param string $dateString
     * @return string
     */
    private function getMonth($dateString)
    {
        try {
            $date = Carbon::parse($dateString);
            return $date->format('M'); // Jan, Feb, Mar, etc.
        } catch (\Exception $e) {
            return '';
        }
    }
}
