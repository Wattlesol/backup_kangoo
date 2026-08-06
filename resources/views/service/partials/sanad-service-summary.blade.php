@php
    $summary = $sanadServiceSummary ?? [];
    $isCustomerCatalog = auth()->user()->hasAnyRole(['customer', 'user']);
    $serviceCatalogRoute = $isCustomerCatalog ? route('service.user-service-list') : route('service.index');
    $packageCatalogRoute = $isCustomerCatalog ? route('service.user-service-list') : route('servicepackage.index');
    $addonCatalogRoute = $isCustomerCatalog ? route('service.user-service-list') : route('serviceaddon.index');
@endphp

@if(auth()->user()->hasAnyRole(['provider', 'admin', 'demo_admin', 'customer', 'user']))
    <div class="col-lg-12">
        <div class="card sanad-service-summary">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="font-weight-bold mb-1">Sanad Service Catalog</h4>
                    <span class="text-muted">Service availability, packages, add-ons, and partner catalog readiness</span>
                </div>
                @if(auth()->user()->can('service add'))
                    <a href="{{ route('service.create') }}" class="btn btn-sm btn-primary"><i class="fa fa-plus-circle"></i> Add Service</a>
                @endif
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-3">
                        <a class="sanad-service-kpi" href="{{ $serviceCatalogRoute }}">
                            <span>Total Services</span>
                            <strong>{{ $summary['total_services'] ?? 0 }}</strong>
                        </a>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <a class="sanad-service-kpi" href="{{ $isCustomerCatalog ? route('service.user-service-list', ['status' => 1]) : route('service.index', ['status' => 1]) }}">
                            <span>Active Services</span>
                            <strong>{{ $summary['active_services'] ?? 0 }}</strong>
                        </a>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <a class="sanad-service-kpi" href="{{ $isCustomerCatalog ? route('service.user-service-list', ['status' => 0]) : route('service.index', ['status' => 0]) }}">
                            <span>Inactive Services</span>
                            <strong>{{ $summary['inactive_services'] ?? 0 }}</strong>
                        </a>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <a class="sanad-service-kpi" href="{{ $packageCatalogRoute }}">
                            <span>Packages</span>
                            <strong>{{ $summary['active_packages'] ?? 0 }}/{{ $summary['packages'] ?? 0 }}</strong>
                        </a>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <a class="sanad-service-kpi" href="{{ $addonCatalogRoute }}">
                            <span>Add-ons</span>
                            <strong>{{ $summary['active_addons'] ?? 0 }}/{{ $summary['addons'] ?? 0 }}</strong>
                        </a>
                    </div>
                    <div class="col-xl-9 mb-0">
                        <div class="sanad-service-note">
                            <span>Catalog Readiness</span>
                            <strong>{{ ($summary['active_services'] ?? 0) > 0 ? 'Ready for customer booking' : 'No active services available' }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

@once
    <style>
        .sanad-service-summary .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }

        .sanad-service-kpi,
        .sanad-service-note {
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            background: #fff;
        }

        .sanad-service-kpi {
            min-height: 82px;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            color: inherit;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .sanad-service-kpi:hover {
            color: inherit;
            border-color: rgba(255, 111, 0, 0.35);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
            text-decoration: none;
        }

        .sanad-service-note {
            min-height: 82px;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .sanad-service-kpi span,
        .sanad-service-note span {
            color: #6c757d;
            font-size: 13px;
        }

        .sanad-service-kpi strong,
        .sanad-service-note strong {
            font-size: 20px;
            line-height: 1.1;
            text-align: right;
            overflow-wrap: anywhere;
        }
    </style>
@endonce
