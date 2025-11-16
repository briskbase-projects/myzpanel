@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h2>Failed Orders</h2>
            <p class="text-muted">These orders failed to sync after 3 retry attempts. Please review and handle manually.</p>
            <a href="{{ route('sync-settings.index') }}" class="btn btn-secondary mb-3">
                <i class="fas fa-arrow-left"></i> Back to Sync Settings
            </a>
            <hr>
        </div>
    </div>

    @if($failedOrders->count() > 0)
    <div class="row">
        <div class="col-md-12">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>Order ID</th>
                            <th>Order Number</th>
                            <th>SKU</th>
                            <th>Sync Type</th>
                            <th>Retry Count</th>
                            <th>Error Message</th>
                            <th>Last Attempt</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($failedOrders as $order)
                        <tr>
                            <td>{{ $order->order_id }}</td>
                            <td>{{ $order->order_number ?? 'N/A' }}</td>
                            <td>{{ $order->sku ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-{{ $order->sync_type === 'full' ? 'primary' : 'info' }}">
                                    {{ ucfirst($order->sync_type) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-danger">
                                    {{ $order->retry_count }}/{{ $order->max_retries }}
                                </span>
                            </td>
                            <td>
                                <small class="text-danger">{{ Str::limit($order->error_message, 100) }}</small>
                                @if(strlen($order->error_message) > 100)
                                    <button class="btn btn-sm btn-link" onclick="alert('{{ addslashes($order->error_message) }}')">
                                        View Full Error
                                    </button>
                                @endif
                            </td>
                            <td>{{ \Carbon\Carbon::parse($order->last_attempt_at)->format('Y-m-d H:i:s') }}</td>
                            <td>
                                <button class="btn btn-sm btn-warning retry-order" data-id="{{ $order->id }}">
                                    <i class="fas fa-redo"></i> Retry
                                </button>
                                <button class="btn btn-sm btn-success resolve-order" data-id="{{ $order->id }}">
                                    <i class="fas fa-check"></i> Mark Resolved
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $failedOrders->links() }}
            </div>
        </div>
    </div>
    @else
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> No failed orders! All orders have been synced successfully.
            </div>
        </div>
    </div>
    @endif
</div>

@push('script')
<script>
$(document).ready(function() {
    // Retry Order
    $('.retry-order').click(function() {
        var orderId = $(this).data('id');
        if(confirm('Are you sure you want to retry syncing this order?')) {
            $.post('/sync-settings/failed-orders/' + orderId + '/retry', {
                _token: '{{ csrf_token() }}'
            })
            .done(function(response) {
                alert(response.message);
                location.reload();
            })
            .fail(function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
            });
        }
    });

    // Resolve Order
    $('.resolve-order').click(function() {
        var orderId = $(this).data('id');
        var notes = prompt('Enter resolution notes (optional):');

        if(notes !== null) { // User didn't click cancel
            $.post('/sync-settings/failed-orders/' + orderId + '/resolve', {
                _token: '{{ csrf_token() }}',
                notes: notes
            })
            .done(function(response) {
                alert(response.message);
                location.reload();
            })
            .fail(function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
            });
        }
    });
});
</script>
@endpush
@endsection
