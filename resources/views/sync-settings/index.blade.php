@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h2>Google Sheets Sync Settings</h2>
            <hr>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Full Sync Status</h5>
                    <p class="card-text">
                        @if($fullSync && $fullSync->is_running)
                            <span class="badge badge-warning">Running...</span>
                        @elseif($fullSync && $fullSync->last_successful_sync)
                            <span class="badge badge-success">Completed</span>
                        @else
                            <span class="badge badge-secondary">Not Started</span>
                        @endif
                    </p>
                    @if($fullSync && $fullSync->last_successful_sync)
                        <small class="text-muted">Last run: {{ \Carbon\Carbon::parse($fullSync->last_successful_sync)->diffForHumans() }}</small>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Synced Items</h5>
                    <h3 class="card-text text-success">{{ $totalSyncedItems }}</h3>
                    <small class="text-muted">In database</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Failed Orders</h5>
                    <h3 class="card-text {{ $failedOrdersCount > 0 ? 'text-danger' : 'text-success' }}">
                        {{ $failedOrdersCount }}
                    </h3>
                    @if($failedOrdersCount > 0)
                        <a href="{{ route('sync-settings.failed-orders') }}" class="btn btn-sm btn-danger">View Failed Orders</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Incremental Sync</h5>
                    <p class="card-text">
                        @if($incrementalSync && $incrementalSync->is_running)
                            <span class="badge badge-warning">Running...</span>
                        @else
                            <span class="badge badge-info">Idle</span>
                        @endif
                    </p>
                    <small class="text-muted">Runs every 15 minutes</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Sync Details -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Full Sync (One-Time)</h4>
                </div>
                <div class="card-body">
                    @if($fullSync)
                        <table class="table table-sm">
                            <tr>
                                <th>Total Orders:</th>
                                <td>{{ $fullSync->total_orders ?? 0 }}</td>
                            </tr>
                            <tr>
                                <th>Synced:</th>
                                <td class="text-success">{{ $fullSync->synced_count ?? 0 }}</td>
                            </tr>
                            <tr>
                                <th>Updated:</th>
                                <td class="text-info">{{ $fullSync->updated_count ?? 0 }}</td>
                            </tr>
                            <tr>
                                <th>Failed:</th>
                                <td class="text-danger">{{ $fullSync->failed_count ?? 0 }}</td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    @if($fullSync->is_running)
                                        <span class="badge badge-warning">Running...</span>
                                    @elseif($fullSync->last_successful_sync)
                                        <span class="badge badge-success">Completed</span>
                                    @else
                                        <span class="badge badge-secondary">Not Started</span>
                                    @endif
                                </td>
                            </tr>
                            @if($fullSync->error_message)
                            <tr>
                                <th>Last Error:</th>
                                <td class="text-danger"><small>{{ $fullSync->error_message }}</small></td>
                            </tr>
                            @endif
                        </table>

                        @if(!$fullSync->last_successful_sync && !$fullSync->is_running)
                            <button id="triggerFullSync" class="btn btn-primary btn-block">
                                <i class="fas fa-sync"></i> Run Full Sync (One-Time)
                            </button>
                        @elseif($fullSync->is_running)
                            <div class="alert alert-info">
                                <i class="fas fa-spinner fa-spin"></i> Full sync is currently running...
                                @if($fullSync->current_page > 0)
                                    <br><small>Processing page {{ $fullSync->current_page + 1 }}...</small>
                                @endif
                            </div>
                            @php
                                $lastUpdate = \Carbon\Carbon::parse($fullSync->updated_at);
                                $minutesSinceUpdate = $lastUpdate->diffInMinutes(now());
                            @endphp
                            @if($minutesSinceUpdate > 5)
                                <button class="btn btn-warning btn-block force-reset" data-sync-type="full">
                                    <i class="fas fa-stop-circle"></i> Force Reset (Stuck for {{ $minutesSinceUpdate }} min)
                                </button>
                            @endif
                        @else
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> Full sync completed successfully!
                            </div>
                        @endif
                    @else
                        <p>No full sync has been initiated yet.</p>
                        <button id="triggerFullSync" class="btn btn-primary btn-block">
                            <i class="fas fa-sync"></i> Run Full Sync (One-Time)
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Incremental Sync (Every 15 Min)</h4>
                </div>
                <div class="card-body">
                    @if($incrementalSync)
                        <table class="table table-sm">
                            <tr>
                                <th>Total Orders:</th>
                                <td>{{ $incrementalSync->total_orders ?? 0 }}</td>
                            </tr>
                            <tr>
                                <th>Synced:</th>
                                <td class="text-success">{{ $incrementalSync->synced_count ?? 0 }}</td>
                            </tr>
                            <tr>
                                <th>Updated:</th>
                                <td class="text-info">{{ $incrementalSync->updated_count ?? 0 }}</td>
                            </tr>
                            <tr>
                                <th>Failed:</th>
                                <td class="text-danger">{{ $incrementalSync->failed_count ?? 0 }}</td>
                            </tr>
                            <tr>
                                <th>Last Run:</th>
                                <td>
                                    @if($incrementalSync->last_successful_sync)
                                        {{ \Carbon\Carbon::parse($incrementalSync->last_successful_sync)->diffForHumans() }}
                                    @else
                                        Never
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    @if($incrementalSync->is_running)
                                        <span class="badge badge-warning">Running...</span>
                                    @else
                                        <span class="badge badge-info">Idle</span>
                                    @endif
                                </td>
                            </tr>
                            @if($incrementalSync->error_message)
                            <tr>
                                <th>Last Error:</th>
                                <td class="text-danger"><small>{{ $incrementalSync->error_message }}</small></td>
                            </tr>
                            @endif
                        </table>

                        <button id="triggerIncrementalSync" class="btn btn-info btn-block" {{ $incrementalSync->is_running ? 'disabled' : '' }}>
                            <i class="fas fa-sync"></i> Run Incremental Sync Now
                        </button>
                        <small class="text-muted d-block mt-2">
                            Note: This runs automatically every 15 minutes
                            @if($nextIncrementalRun)
                                <br><strong>Next scheduled run: {{ $nextIncrementalRun->diffForHumans() }}</strong>
                            @endif
                        </small>

                        @if($incrementalSync->is_running)
                            @php
                                $lastUpdate = \Carbon\Carbon::parse($incrementalSync->updated_at);
                                $minutesSinceUpdate = $lastUpdate->diffInMinutes(now());
                            @endphp
                            @if($minutesSinceUpdate > 5)
                                <button class="btn btn-warning btn-block mt-2 force-reset" data-sync-type="incremental">
                                    <i class="fas fa-stop-circle"></i> Force Reset (Stuck for {{ $minutesSinceUpdate }} min)
                                </button>
                            @endif
                        @endif
                    @else
                        <p>No incremental sync has run yet.</p>
                        <button id="triggerIncrementalSync" class="btn btn-info btn-block">
                            <i class="fas fa-sync"></i> Run Incremental Sync Now
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
$(document).ready(function() {
    // Auto-refresh every 10 seconds (paused when user is interacting)
    var autoRefreshInterval = null;
    var refreshEnabled = true;

    function startAutoRefresh() {
        if(autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }
        autoRefreshInterval = setInterval(function() {
            if(refreshEnabled) {
                location.reload();
            }
        }, 10000); // Changed to 10 seconds
    }

    function pauseAutoRefresh() {
        refreshEnabled = false;
    }

    function resumeAutoRefresh() {
        refreshEnabled = true;
    }

    // Start auto-refresh
    startAutoRefresh();

    // Trigger Full Sync
    $(document).on('click', '#triggerFullSync', function(e) {
        e.preventDefault();
        pauseAutoRefresh(); // Pause auto-refresh during interaction
        console.log('Full sync button clicked');

        if(confirm('Are you sure you want to run the full sync? This will fetch all orders from the past year.')) {
            var $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Starting...');

            $.ajax({
                url: '{{ route("sync-settings.trigger-full") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    console.log('Success:', response);
                    alert(response.message);
                    resumeAutoRefresh();
                    location.reload();
                },
                error: function(xhr, status, error) {
                    console.log('Error:', xhr, status, error);
                    var errorMsg = 'Unknown error';
                    if(xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if(xhr.responseText) {
                        errorMsg = xhr.responseText;
                    }
                    alert('Error: ' + errorMsg);
                    $btn.prop('disabled', false).html('<i class="fas fa-sync"></i> Run Full Sync (One-Time)');
                    resumeAutoRefresh();
                }
            });
        } else {
            resumeAutoRefresh(); // Resume if user cancels
        }
    });

    // Trigger Incremental Sync
    $(document).on('click', '#triggerIncrementalSync', function(e) {
        e.preventDefault();
        pauseAutoRefresh(); // Pause auto-refresh during interaction
        console.log('Incremental sync button clicked');

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Starting...');

        $.ajax({
            url: '{{ route("sync-settings.trigger-incremental") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                console.log('Success:', response);
                alert(response.message);
                resumeAutoRefresh();
                location.reload();
            },
            error: function(xhr, status, error) {
                console.log('Error:', xhr, status, error);
                var errorMsg = 'Unknown error';
                if(xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if(xhr.responseText) {
                    errorMsg = xhr.responseText;
                }
                alert('Error: ' + errorMsg);
                $btn.prop('disabled', false).html('<i class="fas fa-sync"></i> Run Incremental Sync Now');
                resumeAutoRefresh();
            }
        });
    });

    // Force Reset Stuck Jobs
    $(document).on('click', '.force-reset', function() {
        pauseAutoRefresh(); // Pause auto-refresh during interaction
        var syncType = $(this).data('sync-type');
        var confirmMsg = 'Are you sure you want to force reset the ' + syncType + ' sync? This will stop the current job.';

        if(confirm(confirmMsg)) {
            var $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Resetting...');

            $.ajax({
                url: '{{ url("/sync-settings/force-reset") }}/' + syncType,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    console.log('Force reset success:', response);
                    alert(response.message);
                    resumeAutoRefresh();
                    location.reload();
                },
                error: function(xhr) {
                    console.log('Force reset error:', xhr);
                    alert('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
                    $btn.prop('disabled', false).html('<i class="fas fa-stop-circle"></i> Force Reset');
                    resumeAutoRefresh();
                }
            });
        } else {
            resumeAutoRefresh(); // Resume if user cancels
        }
    });
});
</script>
@endpush
@endsection
