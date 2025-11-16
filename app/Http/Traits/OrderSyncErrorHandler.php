<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait OrderSyncErrorHandler
{
    /**
     * Handle failed order sync with retry mechanism
     *
     * @param string $orderId
     * @param string $syncType 'full' or 'incremental'
     * @param \Exception $exception
     * @param array $orderData Additional order data
     * @return bool Returns true if should retry, false if max retries reached
     */
    protected function handleFailedOrderSync($orderId, $syncType, $exception, $orderData = [])
    {
        $maxRetries = 3;

        // Check if this order already has a failed sync record
        $failedSync = DB::table('failed_order_syncs')
            ->where('order_id', $orderId)
            ->where('sync_type', $syncType)
            ->whereNull('resolved_at')
            ->first();

        if ($failedSync) {
            // Update existing record
            $newRetryCount = $failedSync->retry_count + 1;

            DB::table('failed_order_syncs')
                ->where('id', $failedSync->id)
                ->update([
                    'retry_count' => $newRetryCount,
                    'error_message' => $exception->getMessage(),
                    'error_trace' => $exception->getTraceAsString(),
                    'last_attempt_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($newRetryCount >= $maxRetries) {
                Log::error("Order {$orderId} failed after {$newRetryCount} attempts. Marking as permanently failed.");
                return false; // Don't retry
            }

            Log::warning("Order {$orderId} failed. Retry {$newRetryCount}/{$maxRetries}");
            return true; // Retry
        } else {
            // Create new failed sync record
            DB::table('failed_order_syncs')->insert([
                'order_id' => $orderId,
                'order_number' => $orderData['order_number'] ?? null,
                'order_item_id' => $orderData['order_item_id'] ?? null,
                'sku' => $orderData['sku'] ?? null,
                'retry_count' => 1,
                'max_retries' => $maxRetries,
                'sync_type' => $syncType,
                'error_message' => $exception->getMessage(),
                'error_trace' => $exception->getTraceAsString(),
                'last_attempt_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::warning("Order {$orderId} failed. Retry 1/{$maxRetries}");
            return true; // Retry
        }
    }

    /**
     * Mark order sync as successful (remove from failed syncs if exists)
     *
     * @param string $orderId
     * @param string $syncType
     */
    protected function markOrderSyncSuccessful($orderId, $syncType)
    {
        // Mark any existing failed sync as resolved
        DB::table('failed_order_syncs')
            ->where('order_id', $orderId)
            ->where('sync_type', $syncType)
            ->whereNull('resolved_at')
            ->update([
                'resolved_at' => now(),
                'resolution_notes' => 'Auto-resolved: Order synced successfully',
                'updated_at' => now(),
            ]);
    }

    /**
     * Update sync tracking counts
     *
     * @param string $syncType
     * @param int $syncedCount
     * @param int $updatedCount
     * @param int $failedCount
     * @param int $totalOrders
     */
    protected function updateSyncCounts($syncType, $syncedCount, $updatedCount, $failedCount = 0, $totalOrders = 0)
    {
        DB::table('sync_tracking')
            ->where('sync_type', $syncType)
            ->update([
                'synced_count' => DB::raw("synced_count + {$syncedCount}"),
                'updated_count' => DB::raw("updated_count + {$updatedCount}"),
                'failed_count' => DB::raw("failed_count + {$failedCount}"),
                'total_orders' => DB::raw("total_orders + {$totalOrders}"),
                'updated_at' => now(),
            ]);
    }

    /**
     * Reset sync counts for a new sync run
     *
     * @param string $syncType
     */
    protected function resetSyncCounts($syncType)
    {
        DB::table('sync_tracking')
            ->where('sync_type', $syncType)
            ->update([
                'synced_count' => 0,
                'updated_count' => 0,
                'failed_count' => 0,
                'total_orders' => 0,
                'updated_at' => now(),
            ]);
    }
}
