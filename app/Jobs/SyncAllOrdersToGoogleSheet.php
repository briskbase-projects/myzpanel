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

class SyncAllOrdersToGoogleSheet implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ZalandoAPI, GoogleSheetsAPI, OrderSyncErrorHandler;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300; // 5 minutes per page

    /**
     * Current page to process (0-indexed)
     *
     * @var int
     */
    protected $pageNumber;

    /**
     * Create a new job instance.
     *
     * @param int $pageNumber
     * @return void
     */
    public function __construct($pageNumber = 0)
    {
        $this->pageNumber = $pageNumber;
    }

    /**
     * Execute the job.
     * This job processes ONE PAGE at a time and chains itself for the next page
     *
     * @return void
     */
    public function handle()
    {
        try {
            Log::info("SyncAllOrdersToGoogleSheet: Processing page {$this->pageNumber}");

            // Check if Google Sheets is configured
            $config = $this->getSheetConfig();
            if (!$config) {
                Log::warning('Google Sheets not configured. Skipping sync.');
                $this->markAsCompleted('Google Sheets not configured');
                return;
            }

            // Get or create tracking record
            $syncTracking = DB::table('sync_tracking')
                ->where('sync_type', 'full')
                ->first();

            // Check if already completed
            if ($syncTracking && $syncTracking->last_successful_sync) {
                Log::warning('Full sync has already been completed. Use incremental sync instead.');
                return;
            }

            // Initialize tracking on first page
            if ($this->pageNumber === 0) {
                if (!$syncTracking) {
                    DB::table('sync_tracking')->insert([
                        'sync_type' => 'full',
                        'is_running' => true,
                        'last_sync_time' => now(),
                        'current_page' => 0,
                        'total_pages' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('sync_tracking')
                        ->where('sync_type', 'full')
                        ->update([
                            'is_running' => true,
                            'last_sync_time' => now(),
                            'current_page' => 0,
                            'total_pages' => 0,
                            'error_message' => null,
                            'updated_at' => now(),
                        ]);
                }

                // Reset counts for new sync run
                $this->resetSyncCounts('full');
            }

            // Fetch orders from last 365 days
            $startDate = Carbon::now()->subDays(365)->format('Y-m-d');
            $pageSize = 100; // Max per page

            $filters = [
                'created_after' => $startDate . 'T00:00:00Z',
                'page' => [
                    'number' => $this->pageNumber,
                    'size' => $pageSize
                ]
            ];

            Log::info("Fetching page {$this->pageNumber} (page size: {$pageSize})");

            $ordersResponse = $this->getZalandoOrders($filters);

            // Handle empty response
            if (!$ordersResponse || !isset($ordersResponse->data)) {
                if ($this->pageNumber === 0) {
                    Log::warning('No orders returned from Zalando API on first page');
                    $this->markAsCompleted('No orders found', true);
                } else {
                    // No more pages - mark as completed
                    Log::info("No more orders on page {$this->pageNumber}. Sync completed successfully!");
                    $this->markAsCompleted();
                }
                return;
            }

            $orders = $ordersResponse->data;
            $ordersCount = count($orders);

            // If no orders on this page, we're done
            if ($ordersCount === 0) {
                Log::info("No orders on page {$this->pageNumber}. Sync completed successfully!");
                $this->markAsCompleted();
                return;
            }

            Log::info("Found {$ordersCount} orders on page {$this->pageNumber}");

            // Update current page in tracking
            DB::table('sync_tracking')
                ->where('sync_type', 'full')
                ->update([
                    'current_page' => $this->pageNumber,
                    'updated_at' => now(),
                ]);

            // Process orders on this page
            $syncedCount = 0;
            $updatedCount = 0;
            $failedCount = 0;

            foreach ($orders as $order) {
                $retryCount = 0;
                $maxRetries = 3;
                $orderProcessed = false;

                while ($retryCount < $maxRetries && !$orderProcessed) {
                    try {
                        $orderDetail = $this->getZOrderDetail($order->id);

                        if (!$orderDetail || !isset($orderDetail->data)) {
                            throw new \Exception("Could not fetch details for order: {$order->id}");
                        }

                        $orderData = $orderDetail->data;
                        $orderStatus = $orderData->attributes->status ?? '';

                        if ($orderStatus !== 'fulfilled') {
                            $orderProcessed = true; // Skip non-fulfilled orders
                            break;
                        }

                        $this->processOrderItems($orderData, $orderDetail->included ?? [], $syncedCount, $updatedCount, $order->id);

                        // Mark as successful
                        $this->markOrderSyncSuccessful($order->id, 'full');
                        $orderProcessed = true;

                    } catch (\Exception $e) {
                        $retryCount++;

                        $orderInfo = [
                            'order_number' => $orderData->attributes->order_number ?? null,
                        ];

                        if ($retryCount >= $maxRetries) {
                            // Failed after max retries
                            $this->handleFailedOrderSync($order->id, 'full', $e, $orderInfo);
                            $failedCount++;
                            Log::error("Order {$order->id} failed permanently: " . $e->getMessage());
                        } else {
                            Log::warning("Order {$order->id} retry attempt {$retryCount}/{$maxRetries}");
                            sleep(2);
                        }
                    }
                }
            }

            // Update counts for this page
            $this->updateSyncCounts('full', $syncedCount, $updatedCount, $failedCount, $ordersCount);

            Log::info("Page {$this->pageNumber} completed: Synced={$syncedCount}, Updated={$updatedCount}, Failed={$failedCount}");

            // If we got a full page, there might be more pages
            if ($ordersCount === $pageSize) {
                // Dispatch next page
                $nextPage = $this->pageNumber + 1;
                Log::info("Dispatching job for next page: {$nextPage}");
                dispatch(new self($nextPage))->delay(now()->addSeconds(2)); // 2 second delay
            } else {
                // This was the last page - mark as completed
                Log::info("Last page processed. Sync completed successfully!");
                $this->markAsCompleted();
            }

        } catch (\Exception $e) {
            Log::error("SyncAllOrdersToGoogleSheet page {$this->pageNumber} failed: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());

            // Mark as failed
            DB::table('sync_tracking')
                ->where('sync_type', 'full')
                ->update([
                    'is_running' => false,
                    'error_message' => "Failed on page {$this->pageNumber}: " . $e->getMessage(),
                    'updated_at' => now(),
                ]);

            throw $e;
        }
    }

    /**
     * Mark sync as completed
     */
    private function markAsCompleted($message = null, $isError = false)
    {
        $tracking = DB::table('sync_tracking')->where('sync_type', 'full')->first();

        $updateData = [
            'is_running' => false,
            'updated_at' => now(),
        ];

        if ($isError) {
            $updateData['error_message'] = $message;
        } else {
            $updateData['last_successful_sync'] = now();
            $updateData['error_message'] = null;
            $updateData['total_pages'] = $this->pageNumber + 1;
        }

        DB::table('sync_tracking')
            ->where('sync_type', 'full')
            ->update($updateData);

        if (!$isError) {
            Log::info("Full sync completed! Total pages: {$updateData['total_pages']}, Synced: {$tracking->synced_count}, Updated: {$tracking->updated_count}, Failed: {$tracking->failed_count}");
        }
    }

    /**
     * Process all items in an order
     * (Same as in SyncOrdersToGoogleSheet)
     */
    private function processOrderItems($orderData, $included, &$syncedCount, &$updatedCount, $orderId)
    {
        // Call the shared method from SyncOrdersToGoogleSheet
        $sharedJob = new SyncOrdersToGoogleSheet();
        return $sharedJob->processOrderItemsPublic($orderData, $included, $syncedCount, $updatedCount, $orderId);
    }
}
