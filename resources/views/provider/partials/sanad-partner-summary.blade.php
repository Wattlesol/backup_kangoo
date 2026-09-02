@php
    $summary = $sanadPartnerSummary ?? [];
@endphp

@if(auth()->user()->hasAnyRole(['admin', 'demo_admin']))
    <div class="col-lg-12">
        <div class="card sanad-admin-partner-summary">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    @php $isAr = app()->getLocale() === 'ar'; @endphp<h4 class="font-weight-bold mb-1">{{ $isAr ? 'إدارة شركاء كويك' : 'Quick Partner Management' }}</h4>
                    <span class="text-muted">{{ $isAr ? 'اعتماد الشركاء، التفعيل، إسناد أعباء العمل، الوثائق، ومتابعة المستحقات' : 'Partner approval, activation, workload assignment, documents, and payout visibility' }}</span>
                </div>
                @if($auth_user->can('provider add'))
                    <a href="{{ route('provider.create') }}" class="btn btn-sm btn-primary"><i class="fa fa-plus-circle"></i> {{ $isAr ? 'إضافة شريك' : 'Add Partner' }}</a>
                @endif
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-3">
                        <a class="sanad-partner-admin-kpi" href="{{ route('provider.index') }}">
                            <span>{{ $isAr ? 'إجمالي الشركاء' : 'Total Partners' }}</span>
                            <strong>{{ $summary['total_partners'] ?? 0 }}</strong>
                        </a>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <a class="sanad-partner-admin-kpi" href="{{ route('provider.index', ['status' => 1]) }}">
                            <span>{{ $isAr ? 'الشركاء النشطون' : 'Active Partners' }}</span>
                            <strong>{{ $summary['active_partners'] ?? 0 }}</strong>
                        </a>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <a class="sanad-partner-admin-kpi" href="{{ route('provider.pending', ['status' => 'pending']) }}">
                            <span>{{ $isAr ? 'بانتظار الاعتماد' : 'Pending Approval' }}</span>
                            <strong>{{ $summary['pending_partners'] ?? 0 }}</strong>
                        </a>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <a class="sanad-partner-admin-kpi" href="{{ route('provider.pending', ['status' => 'subscribe']) }}">
                            <span>{{ $isAr ? 'الشركاء المشتركون' : 'Subscribed Partners' }}</span>
                            <strong>{{ $summary['subscribed_partners'] ?? 0 }}</strong>
                        </a>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <a class="sanad-partner-admin-kpi" href="{{ route('sanad.requests.index', ['assignment_state' => 'assigned']) }}">
                            <span>{{ $isAr ? 'الطلبات المُسندة' : 'Assigned Requests' }}</span>
                            <strong>{{ $summary['assigned_requests'] ?? 0 }}</strong>
                        </a>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <a class="sanad-partner-admin-kpi" href="{{ route('sanad.requests.index', ['assignment_state' => 'unassigned']) }}">
                            <span>{{ $isAr ? 'الطلبات غير المُسندة' : 'Unassigned Requests' }}</span>
                            <strong>{{ $summary['unassigned_requests'] ?? 0 }}</strong>
                        </a>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <a class="sanad-partner-admin-kpi" href="{{ route('providerdocument.index') }}">
                            <span>{{ $isAr ? 'المستندات المعلقة' : 'Pending Documents' }}</span>
                            <strong>{{ $summary['pending_documents'] ?? 0 }}</strong>
                        </a>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-0">
                        <a class="sanad-partner-admin-kpi" href="{{ route('providerpayout.index') }}">
                            <span>{{ $isAr ? 'إيرادات الشركاء' : 'Partner Revenue' }}</span>
                            <strong>{{ getPriceFormat($summary['provider_revenue'] ?? 0) }}</strong>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

@once
    <style>
        .sanad-admin-partner-summary .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }

        .sanad-partner-admin-kpi {
            min-height: 84px;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            background: #fff;
            color: inherit;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .sanad-partner-admin-kpi:hover {
            color: inherit;
            border-color: rgba(255, 111, 0, 0.35);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
            text-decoration: none;
        }

        .sanad-partner-admin-kpi span {
            color: #6c757d;
            font-size: 13px;
        }

        .sanad-partner-admin-kpi strong {
            font-size: 22px;
            line-height: 1.1;
            text-align: right;
            overflow-wrap: anywhere;
        }
    </style>
@endonce
