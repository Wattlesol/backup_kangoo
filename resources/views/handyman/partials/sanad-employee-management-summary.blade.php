@php
    $summary = $sanadEmployeeSummary ?? [];
@endphp

@if(auth()->user()->hasAnyRole(['admin', 'demo_admin', 'provider']))
    <div class="col-lg-12">
        <div class="card sanad-employee-management-summary">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    @php $isAr = app()->getLocale() === 'ar'; @endphp<h4 class="font-weight-bold mb-1">{{ $isAr ? 'إدارة موظفي كويك' : 'Quick Employee Management' }}</h4>
                    <span class="text-muted">{{ $isAr ? 'طاقة الموظفين التشغيلية، أعباء العمل، الأدلة، التواصل، وجاهزية الصرف' : 'Employee capacity, workload, evidence, communication, and payment readiness' }}</span>
                </div>
                @if($auth_user->can('handyman add') && $list_status != 'unassigned' && $list_status != 'request')
                    <a href="{{ route('handyman.create') }}" class="btn btn-sm btn-primary"><i class="fa fa-plus-circle"></i> {{ $isAr ? 'إضافة موظف' : 'Add Employee' }}</a>
                @endif
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-3">
                        <a class="sanad-employee-admin-kpi" href="{{ route('handyman.index') }}">
                            <span>{{ $isAr ? 'إجمالي الموظفين' : 'Total Employees' }}</span>
                            <strong>{{ $summary['total_employees'] ?? 0 }}</strong>
                        </a>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <a class="sanad-employee-admin-kpi" href="{{ route('handyman.index', ['status' => 1]) }}">
                            <span>{{ $isAr ? 'الموظفون النشطون' : 'Active Employees' }}</span>
                            <strong>{{ $summary['active_employees'] ?? 0 }}</strong>
                        </a>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <a class="sanad-employee-admin-kpi" href="{{ route('handyman.pending', ['status' => 'pending']) }}">
                            <span>{{ $isAr ? 'موظفون معلقون' : 'Pending Employees' }}</span>
                            <strong>{{ $summary['pending_employees'] ?? 0 }}</strong>
                        </a>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <a class="sanad-employee-admin-kpi" href="{{ route('sanad.requests.index', ['assignment_state' => 'assigned']) }}">
                            <span>{{ $isAr ? 'المهام المسندة' : 'Assigned Tasks' }}</span>
                            <strong>{{ $summary['assigned_tasks'] ?? 0 }}</strong>
                        </a>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <a class="sanad-employee-admin-kpi" href="{{ route('sanad.requests.index', ['assignment_state' => 'unassigned']) }}">
                            <span>{{ $isAr ? 'بحاجة إلى موظف' : 'Needs Employee' }}</span>
                            <strong>{{ $summary['unassigned_tasks'] ?? 0 }}</strong>
                        </a>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <a class="sanad-employee-admin-kpi" href="{{ route('sanad.requests.index', ['sanad_stage' => 'awaiting_quality_review']) }}">
                            <span>{{ $isAr ? 'قائمة المراجعة' : 'Review Queue' }}</span>
                            <strong>{{ $summary['review_tasks'] ?? 0 }}</strong>
                        </a>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <a class="sanad-employee-admin-kpi" href="{{ route('sanad.requests.index', ['action_state' => 'pending_documents']) }}">
                            <span>{{ $isAr ? 'أدلة معلقة' : 'Pending Evidence' }}</span>
                            <strong>{{ $summary['pending_evidence'] ?? 0 }}</strong>
                        </a>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-0">
                        <a class="sanad-employee-admin-kpi" href="{{ route('sanad.requests.index', ['payment_state' => 'pending']) }}">
                            <span>{{ $isAr ? 'مستحقات معلقة' : 'Pending Payment' }}</span>
                            <strong>{{ $summary['pending_payment_tasks'] ?? 0 }}</strong>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

@once
    <style>
        .sanad-employee-management-summary .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }

        .sanad-employee-admin-kpi {
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

        .sanad-employee-admin-kpi:hover {
            color: inherit;
            border-color: rgba(255, 111, 0, 0.35);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
            text-decoration: none;
        }

        .sanad-employee-admin-kpi span {
            color: #6c757d;
            font-size: 13px;
        }

        .sanad-employee-admin-kpi strong {
            font-size: 22px;
            line-height: 1.1;
            text-align: right;
            overflow-wrap: anywhere;
        }
    </style>
@endonce
