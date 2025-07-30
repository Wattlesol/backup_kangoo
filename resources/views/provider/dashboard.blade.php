<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? __('messages.provider_dashboard') }}</h5>
                            <div class="alert alert-info alert-sm mb-0">
                                <i class="fas fa-store"></i> {{ __('messages.single_store_provider_info') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-primary text-white rounded-circle me-3">
                            <i class="fas fa-box"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">{{ $stats['total_products'] ?? 0 }}</h3>
                            <p class="text-muted mb-0">{{ __('messages.total_products') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-success text-white rounded-circle me-3">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">{{ $stats['approved_products'] ?? 0 }}</h3>
                            <p class="text-muted mb-0">{{ __('messages.approved_products') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-warning text-white rounded-circle me-3">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">{{ $stats['pending_products'] ?? 0 }}</h3>
                            <p class="text-muted mb-0">{{ __('messages.pending_approval') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-info text-white rounded-circle me-3">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">{{ $stats['total_orders'] ?? 0 }}</h3>
                            <p class="text-muted mb-0">{{ __('messages.total_orders') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">{{ __('messages.quick_actions') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('provider.product.create') }}" class="btn btn-primary btn-block">
                                <i class="fas fa-plus-circle"></i> {{ __('messages.add_product') }}
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('provider.product.index') }}" class="btn btn-secondary btn-block">
                                <i class="fas fa-list"></i> {{ __('messages.my_products') }}
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('provider.order.index') }}" class="btn btn-info btn-block">
                                <i class="fas fa-shopping-cart"></i> {{ __('messages.my_orders') }}
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('provider.product.index', ['approval_status' => 'pending']) }}" class="btn btn-warning btn-block">
                                <i class="fas fa-clock"></i> {{ __('messages.pending_approval') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">{{ __('messages.order_statistics') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 text-center">
                            <h4 class="text-warning">{{ $stats['pending_orders'] ?? 0 }}</h4>
                            <p class="text-muted">{{ __('messages.pending') }}</p>
                        </div>
                        <div class="col-6 text-center">
                            <h4 class="text-success">{{ $stats['total_orders'] ?? 0 }}</h4>
                            <p class="text-muted">{{ __('messages.total') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    @if($recentOrders && $recentOrders->count() > 0)
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title">{{ __('messages.recent_orders') }}</h5>
                    <a href="{{ route('provider.order.index') }}" class="btn btn-sm btn-primary">
                        {{ __('messages.view_all') }}
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.order_number') }}</th>
                                    <th>{{ __('messages.customer') }}</th>
                                    <th>{{ __('messages.status') }}</th>
                                    <th>{{ __('messages.total') }}</th>
                                    <th>{{ __('messages.date') }}</th>
                                    <th>{{ __('messages.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentOrders as $order)
                                <tr>
                                    <td>{{ $order->formatted_order_number }}</td>
                                    <td>{{ $order->customer->display_name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $order->status_color }}">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td>{{ getPriceFormat($order->total_amount) }}</td>
                                    <td>{{ $order->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <a href="{{ route('provider.order.show', $order->id) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

@section('bottom_script')
<style>
.icon-box {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}
</style>
@endsection
</x-master-layout>
