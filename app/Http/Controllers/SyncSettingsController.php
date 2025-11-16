<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Jobs\SyncAllOrdersToGoogleSheet;
use App\Jobs\SyncOrdersToGoogleSheet;

class SyncSettingsController extends Controller
{
    /**
     * Show sync settings page
     */
    public function index()
    {
        // Get sync tracking data
        $fullSync = DB::table('sync_tracking')->where('sync_type', 'full')->first();
        $incrementalSync = DB::table('sync_tracking')->where('sync_type', 'incremental')->first();

        // Auto-reset stuck jobs (running for more than 30 minutes without update)
        $this->resetStuckJobs($fullSync, 'full');
        $this->resetStuckJobs($incrementalSync, 'incremental');

        // Refresh data after reset
        $fullSync = DB::table('sync_tracking')->where('sync_type', 'full')->first();
        $incrementalSync = DB::table('sync_tracking')->where('sync_type', 'incremental')->first();

        // Get failed orders count
        $failedOrdersCount = DB::table('failed_order_syncs')
            ->whereNull('resolved_at')
            ->count();

        // Get total synced items
        $totalSyncedItems = DB::table('synced_order_items')->count();

        // Calculate next incremental sync run time (every 15 minutes)
        $nextIncrementalRun = null;
        if ($incrementalSync && $incrementalSync->last_successful_sync) {
            $lastRun = \Carbon\Carbon::parse($incrementalSync->last_successful_sync);
            $nextIncrementalRun = $lastRun->copy()->addMinutes(15);
        }

        return view('sync-settings.index', compact(
            'fullSync',
            'incrementalSync',
            'failedOrdersCount',
            'totalSyncedItems',
            'nextIncrementalRun'
        ));
    }

    /**
     * Reset stuck jobs (running for more than 30 minutes)
     */
    private function resetStuckJobs($syncRecord, $syncType)
    {
        if ($syncRecord && $syncRecord->is_running) {
            $lastUpdate = \Carbon\Carbon::parse($syncRecord->updated_at);
            $minutesSinceUpdate = $lastUpdate->diffInMinutes(now());

            // If running for more than 30 minutes without update, mark as stuck
            if ($minutesSinceUpdate > 30) {
                DB::table('sync_tracking')
                    ->where('sync_type', $syncType)
                    ->update([
                        'is_running' => false,
                        'error_message' => 'Job stuck - Auto-reset after 30 minutes of inactivity',
                        'updated_at' => now(),
                    ]);

                \Log::warning("Sync job '{$syncType}' was stuck for {$minutesSinceUpdate} minutes and has been auto-reset");
            }
        }
    }

    /**
     * Trigger one-time full sync
     */
    public function triggerFullSync()
    {
        // Check if already running
        $fullSync = DB::table('sync_tracking')->where('sync_type', 'full')->first();

        if ($fullSync && $fullSync->is_running) {
            return response()->json([
                'success' => false,
                'message' => 'Full sync is already running. Please wait for it to complete.'
            ]);
        }

        // Check if already completed
        if ($fullSync && $fullSync->last_successful_sync) {
            return response()->json([
                'success' => false,
                'message' => 'Full sync has already been completed. It should only run once.'
            ]);
        }

        // Dispatch the job
        SyncAllOrdersToGoogleSheet::dispatch();

        return response()->json([
            'success' => true,
            'message' => 'Full sync job has been queued. Please check status for progress.'
        ]);
    }

    /**
     * Trigger incremental sync manually
     */
    public function triggerIncrementalSync()
    {
        // Check if already running
        $incrementalSync = DB::table('sync_tracking')->where('sync_type', 'incremental')->first();

        if ($incrementalSync && $incrementalSync->is_running) {
            return response()->json([
                'success' => false,
                'message' => 'Incremental sync is already running. Please wait for it to complete.'
            ]);
        }

        // Dispatch the job
        SyncOrdersToGoogleSheet::dispatch();

        return response()->json([
            'success' => true,
            'message' => 'Incremental sync job has been queued.'
        ]);
    }

    /**
     * Get sync status (AJAX)
     */
    public function getSyncStatus()
    {
        $fullSync = DB::table('sync_tracking')->where('sync_type', 'full')->first();
        $incrementalSync = DB::table('sync_tracking')->where('sync_type', 'incremental')->first();

        $failedOrdersCount = DB::table('failed_order_syncs')
            ->whereNull('resolved_at')
            ->count();

        return response()->json([
            'fullSync' => $fullSync,
            'incrementalSync' => $incrementalSync,
            'failedOrdersCount' => $failedOrdersCount,
            'totalSyncedItems' => DB::table('synced_order_items')->count()
        ]);
    }

    /**
     * Show failed orders
     */
    public function failedOrders()
    {
        $failedOrders = DB::table('failed_order_syncs')
            ->whereNull('resolved_at')
            ->orderBy('last_attempt_at', 'desc')
            ->paginate(50);

        return view('sync-settings.failed-orders', compact('failedOrders'));
    }

    /**
     * Mark failed order as resolved
     */
    public function resolveFailedOrder(Request $request, $id)
    {
        $notes = $request->input('notes', '');

        DB::table('failed_order_syncs')
            ->where('id', $id)
            ->update([
                'resolved_at' => now(),
                'resolved_by' => auth()->user()->name ?? 'Admin',
                'resolution_notes' => $notes,
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Order marked as resolved.'
        ]);
    }

    /**
     * Retry failed order
     */
    public function retryFailedOrder($id)
    {
        $failedOrder = DB::table('failed_order_syncs')->where('id', $id)->first();

        if (!$failedOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Failed order not found.'
            ]);
        }

        // Reset retry count
        DB::table('failed_order_syncs')
            ->where('id', $id)
            ->update([
                'retry_count' => 0,
                'updated_at' => now(),
            ]);

        // Dispatch appropriate sync job
        if ($failedOrder->sync_type === 'full') {
            SyncAllOrdersToGoogleSheet::dispatch();
        } else {
            SyncOrdersToGoogleSheet::dispatch();
        }

        return response()->json([
            'success' => true,
            'message' => 'Retry has been queued.'
        ]);
    }

    /**
     * Force reset a stuck sync job
     */
    public function forceReset($syncType)
    {
        DB::table('sync_tracking')
            ->where('sync_type', $syncType)
            ->update([
                'is_running' => false,
                'error_message' => 'Manually reset by admin',
                'updated_at' => now(),
            ]);

        \Log::info("Sync job '{$syncType}' was manually reset by admin");

        return response()->json([
            'success' => true,
            'message' => 'Sync job has been reset. You can now trigger a new sync.'
        ]);
    }
}
