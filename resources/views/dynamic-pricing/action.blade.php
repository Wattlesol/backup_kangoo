@if($product)
    <div class="d-flex justify-content-end align-items-center">
        <button type="button" class="btn btn-sm btn-primary me-2" onclick="openPricingModal({{ $product->id }})" title="Manage Pricing">
            <i class="fas fa-dollar-sign"></i>
        </button>
        
        @if($product->admin_price_active)
            <span class="badge badge-success" title="Dynamic Pricing Active">
                <i class="fas fa-toggle-on"></i>
            </span>
        @else
            <span class="badge badge-secondary" title="Dynamic Pricing Inactive">
                <i class="fas fa-toggle-off"></i>
            </span>
        @endif
    </div>
@endif
