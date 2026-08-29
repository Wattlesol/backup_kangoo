@php
    $isAr = app()->getLocale() === 'ar';
    $pageTitle = $isAr ? 'طابور الطلبات' : 'Request queue';
@endphp
<x-master-layout>
    <div class="quick-request-queue" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
        <div class="row">
            <div class="col-lg-12">
                <div class="quick-admin-hero quick-queue-hero">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <span class="quick-admin-hero-eyebrow">{{ $isAr ? 'طابور العمليات الموحد' : 'Unified operations queue' }}</span>
                                <h1 class="font-weight-bold mb-1">{{ $isAr ? 'طلبات وعمليات كويك الحكومية' : 'Quick government requests and operations' }}</h1>
                                <p class="text-muted mb-0">{{ $isAr ? 'البحث والفلترة وتوزيع المهام ومتابعة نوافذ الإنجاز لكافة المعاملات.' : 'Search, filter, assign, and monitor completion windows for every request.' }}</p>
                            </div>
                            <a href="{{ route('home') }}" class="quick-table-btn">{{ $isAr ? 'العودة للوحة التحكم' : 'Back to dashboard' }}</a>
                        </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="quick-card quick-queue-panel">
                        <div class="row sanad-summary-grid">
                            <div class="col-lg-2 col-md-4 col-6 mb-3">
                                <a href="{{ route('sanad.requests.index') }}" class="sanad-summary-card">
                                    <span>{{ $isAr ? 'الإجمالي' : 'Total' }}</span>
                                    <strong>{{ $summary['total'] ?? 0 }}</strong>
                                </a>
                            </div>
                            <div class="col-lg-2 col-md-4 col-6 mb-3">
                                <a href="{{ route('sanad.requests.index', ['action_state' => 'needs_action']) }}" class="sanad-summary-card">
                                    <span>{{ $isAr ? 'يتطلب إجراءً' : 'Needs Action' }}</span>
                                    <strong>{{ $summary['needs_action'] ?? 0 }}</strong>
                                </a>
                            </div>
                            <div class="col-lg-2 col-md-4 col-6 mb-3">
                                <a href="{{ route('sanad.requests.index', ['assignment_state' => 'unassigned']) }}" class="sanad-summary-card">
                                    <span>{{ $isAr ? 'غير مسند' : 'Unassigned' }}</span>
                                    <strong>{{ $summary['unassigned'] ?? 0 }}</strong>
                                </a>
                            </div>
                            <div class="col-lg-2 col-md-4 col-6 mb-3">
                                <a href="{{ route('sanad.requests.index', ['sla_state' => 'overdue']) }}" class="sanad-summary-card">
                                    <span>{{ $isAr ? 'متأخر عن SLA' : 'Overdue SLA' }}</span>
                                    <strong>{{ $summary['overdue_sla'] ?? 0 }}</strong>
                                </a>
                            </div>
                            <div class="col-lg-2 col-md-4 col-6 mb-3">
                                <a href="{{ route('sanad.requests.index', ['action_state' => 'pending_documents']) }}" class="sanad-summary-card">
                                    <span>{{ $isAr ? 'مستندات معلقة' : 'Pending Docs' }}</span>
                                    <strong>{{ $summary['pending_documents'] ?? 0 }}</strong>
                                </a>
                            </div>
                        </div>

                        <form method="GET" action="{{ route('sanad.requests.index') }}" class="sanad-filter-form mb-4">
                            <div class="row">
                                <div class="col-lg-6 col-md-6 mb-3">
                                    <label class="form-control-label">{{ $isAr ? "البحث" : "Search" }}</label>
                                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ $isAr ? 'الرقم المرجعي، الخدمة، العميل' : 'Reference, service, customer' }}">
                                </div>
                                <div class="col-lg-2 col-md-6 mb-3">
                                    <label class="form-control-label">{{ $isAr ? "مرحلة دورة الطلب" : "Lifecycle Stage" }}</label>
                                    <select name="sanad_stage" class="form-control">
                                        <option value="">{{ $isAr ? "جميع المراحل" : "All stages" }}</option>
                                        @foreach(config('sanad.request_lifecycle', []) as $stage)
                                            <option value="{{ $stage }}" {{ request('sanad_stage') === $stage ? 'selected' : '' }}>{{ Str::headline($stage) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-6 mb-3">
                                    <label class="form-control-label">{{ $isAr ? "اتفاقية مستوى الخدمة" : "SLA" }}</label>
                                    <select name="sla_state" class="form-control">
                                        <option value="">Any SLA</option>
                                        <option value="overdue" {{ request('sla_state') === 'overdue' ? 'selected' : '' }}>{{ $isAr ? "متأخر" : "Overdue" }}</option>
                                        <option value="due_soon" {{ request('sla_state') === 'due_soon' ? 'selected' : '' }}>{{ $isAr ? "يستحق قريباً" : "Due soon" }}</option>
                                        <option value="none" {{ request('sla_state') === 'none' ? 'selected' : '' }}>No SLA</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-6 mb-3">
                                    <label class="form-control-label">{{ $isAr ? "حالة الإسناد" : "Assignment" }}</label>
                                    <select name="assignment_state" class="form-control">
                                        <option value="">Any assignment</option>
                                        <option value="assigned" {{ request('assignment_state') === 'assigned' ? 'selected' : '' }}>Assigned</option>
                                        <option value="unassigned" {{ request('assignment_state') === 'unassigned' ? 'selected' : '' }}>{{ $isAr ? "غير مسند" : "Unassigned" }}</option>
                                    </select>
                                </div>
                                <div class="col-lg-12">
                                    <details class="quick-advanced-filters" {{ request()->hasAny(['sanad_priority','action_state','payment_state']) ? 'open' : '' }}>
                                        <summary>{{ $isAr ? 'فلاتر متقدمة' : 'Advanced filters' }}</summary>
                                        <div class="row pt-3">
                                            <div class="col-lg-4 col-md-6 mb-3"><label class="form-control-label">{{ $isAr ? 'الأولوية' : 'Priority' }}</label><select name="sanad_priority" class="form-control"><option value="">{{ $isAr ? 'جميع الأولويات' : 'All priorities' }}</option>@foreach(['urgent','high','normal','low'] as $priority)<option value="{{ $priority }}" {{ request('sanad_priority') === $priority ? 'selected' : '' }}>{{ Str::headline($priority) }}</option>@endforeach</select></div>
                                            <div class="col-lg-4 col-md-6 mb-3"><label class="form-control-label">{{ $isAr ? 'حالة الإجراء' : 'Action state' }}</label><select name="action_state" class="form-control"><option value="">{{ $isAr ? 'كل الإجراءات' : 'Any action' }}</option><option value="needs_action" {{ request('action_state') === 'needs_action' ? 'selected' : '' }}>Needs action</option><option value="pending_documents" {{ request('action_state') === 'pending_documents' ? 'selected' : '' }}>Pending documents</option><option value="unread_buzz" {{ request('action_state') === 'unread_buzz' ? 'selected' : '' }}>Unread Buzz</option><option value="open_chat" {{ request('action_state') === 'open_chat' ? 'selected' : '' }}>Open chat</option></select></div>
                                            <div class="col-lg-4 col-md-6 mb-3"><label class="form-control-label">{{ $isAr ? 'حالة الدفع' : 'Payment' }}</label><select name="payment_state" class="form-control"><option value="">{{ $isAr ? 'كل حالات الدفع' : 'Any payment' }}</option>@foreach(['paid','pending','advanced_paid','pending_by_admin','failed','no_payment'] as $payment)<option value="{{ $payment }}" {{ request('payment_state') === $payment ? 'selected' : '' }}>{{ Str::headline($payment) }}</option>@endforeach</select></div>
                                        </div>
                                    </details>
                                </div>
                                <div class="col-lg-2 col-md-6 mb-3 d-flex align-items-end gap-2">
                                    <button type="submit" class="btn btn-primary quick-primary-btn">{{ $isAr ? 'تطبيق الفلاتر' : 'Apply Filters' }}</button>
                                </div>
                                <div class="col-lg-2 col-md-6 mb-3 d-flex align-items-end">
                                    <a href="{{ route('sanad.requests.index') }}" class="btn btn-light quick-secondary-btn">{{ $isAr ? 'إعادة ضبط' : 'Reset' }}</a>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="quick-table sanad-requests-table">
                                <thead>
                                    <tr>
                                        <th>{{ $isAr ? 'الطلب' : 'Request' }}</th>
                                        <th>{{ $isAr ? 'الخدمة' : 'Service' }}</th>
                                        <th>{{ $isAr ? 'العميل' : 'Customer' }}</th>
                                        <th>{{ $isAr ? 'الشريك' : 'Partner' }}</th>
                                        <th>{{ $isAr ? 'المرحلة' : 'Stage' }}</th>
                                        <th>{{ $isAr ? "حالة الدفع" : "Payment" }}</th>
                                        <th>{{ $isAr ? "اتفاقية SLA" : "SLA" }}</th>
                                        <th class="text-right">{{ $isAr ? "الإجراء" : "Action" }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($requests as $requestItem)
                                        @php
                                            $slaClass = '';
                                            $rowFlags = [];
                                            if ($requestItem->sla_due_at && $requestItem->sla_due_at->isPast()) {
                                                $slaClass = 'text-danger';
                                                $rowFlags[] = $isAr ? 'متأخر' : 'Overdue';
                                            } elseif ($requestItem->sla_due_at && $requestItem->sla_due_at->lessThanOrEqualTo(now()->addDay())) {
                                                $slaClass = 'text-warning';
                                                $rowFlags[] = $isAr ? 'يستحق قريباً' : 'Due soon';
                                            }
                                            if ($requestItem->handymanAdded->isEmpty()) {
                                                $rowFlags[] = $isAr ? 'غير مسند' : 'Unassigned';
                                            }
                                            if ($requestItem->sanadDocuments->where('verification_status', 'pending')->count() > 0) {
                                                $rowFlags[] = $isAr ? 'مستندات' : 'Docs';
                                            }
                                            if ($requestItem->sanadBuzzAlerts->where('status', 'unread')->count() > 0) {
                                                $rowFlags[] = $isAr ? 'تنبيه' : 'Buzz';
                                            }
                                            $paymentStatus = optional($requestItem->payment)->payment_status ?: 'no_payment';
                                            if ($paymentStatus !== 'paid') {
                                                $rowFlags[] = $isAr ? 'دفع' : 'Payment';
                                            }
                                        @endphp
                                        @php
                                            $openRoute = request('action_state') === 'open_chat'
                                                ? route('sanad.chat.workspace', ['booking_id' => $requestItem->id, 'action_state' => 'open_chat'])
                                                : route('sanad.requests.show', $requestItem->id);
                                        @endphp
                                        <tr>
                                            <td data-label="{{ $isAr ? 'الطلب' : 'Request' }}">
                                                <a href="{{ $openRoute }}" class="btn-link btn-link-hover">
                                                    {{ $requestItem->quick_reference }}
                                                </a>
                                                <small>{{ __('messages.' . ($requestItem->status ?: 'pending')) ?: Str::headline($requestItem->status ?: 'pending') }}</small>
                                                @if(!empty($rowFlags))
                                                    <div class="sanad-row-flags">
                                                        @foreach($rowFlags as $flag)
                                                            <span>{{ $flag }}</span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </td>
                                            <td data-label="{{ $isAr ? 'الخدمة' : 'Service' }}">{{ optional($requestItem->service)->name ?: '-' }}</td>
                                            <td data-label="{{ $isAr ? 'العميل' : 'Customer' }}">{{ optional($requestItem->customer)->display_name ?: '-' }}</td>
                                            <td data-label="{{ $isAr ? 'الشريك' : 'Partner' }}">{{ optional($requestItem->provider)->display_name ?: '-' }}</td>
                                            <td data-label="{{ $isAr ? 'المرحلة' : 'Stage' }}"><span class="badge badge-primary">{{ __('messages.stage_' . ($requestItem->sanad_stage ?: 'submitted')) ?: Str::headline($requestItem->sanad_stage ?: 'submitted') }}</span></td>
                                            <td data-label="{{ $isAr ? 'الدفع' : 'Payment' }}">
                                                <span class="badge {{ $paymentStatus === 'paid' ? 'badge-success' : 'badge-light' }}">{{ __('messages.' . $paymentStatus) ?: ($isAr && $paymentStatus === 'no_payment' ? 'بدون دفع' : Str::headline($paymentStatus)) }}</span>
                                                <small>{{ $requestItem->total_amount ? getPriceFormat($requestItem->total_amount) : '-' }}</small>
                                            </td>
                                            <td data-label="{{ $isAr ? 'اتفاقية SLA' : 'SLA' }}" class="{{ $slaClass }}">
                                                {{ $requestItem->sla_due_at ? $requestItem->sla_due_at->format('Y-m-d H:i') : '-' }}
                                            </td>
                                            <td data-label="{{ $isAr ? 'الإجراء' : 'Action' }}" class="text-right">
                                                <a href="{{ $openRoute }}" class="quick-table-btn">{{ request('action_state') === 'open_chat' ? ($isAr ? 'فتح المحادثة' : 'Open Chat') : ($isAr ? 'فتح' : 'Open') }}</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8">
                                                <div class="sanad-empty-state">{{ $isAr ? 'لا توجد طلبات كويك مطابقة للفلاتر الحالية' : 'No Quick requests match the current filters' }}</div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-3">
                            <span class="text-muted">Showing {{ $requests->firstItem() ?: 0 }}-{{ $requests->lastItem() ?: 0 }} of {{ $requests->total() }}</span>
                            {{ $requests->links() }}
                        </div>
                </div>
            </div>
        </div>
    </div>

    @once
        <style>
            .quick-request-queue {
                max-width: 1180px;
                margin: 0 auto;
            }

            .quick-request-queue .text-muted {
                color: var(--quick-shell-muted) !important;
            }

            .quick-queue-panel {
                padding: 20px;
                overflow: hidden;
            }

            .sanad-filter-form {
                border: 1px solid var(--quick-shell-line);
                border-radius: 16px;
                padding: 16px;
                background: var(--quick-shell-surface);
            }

            .sanad-summary-card {
                min-height: 64px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
                padding: 14px 18px;
                border: 1px solid var(--quick-shell-line);
                border-radius: 999px;
                background: var(--quick-shell-surface);
                color: var(--quick-shell-ink);
                text-decoration: none;
                transition: all .15s ease;
            }

            .sanad-summary-card:hover {
                color: var(--quick-blue);
                border-color: rgba(31,107,255,.35);
                box-shadow: 0 10px 24px rgba(31,107,255,.08);
            }

            .sanad-summary-card span {
                color: var(--quick-shell-muted);
                font-size: 11px;
                font-weight: 800;
            }

            .sanad-summary-card strong {
                font-size: 18px;
                color: var(--quick-blue);
            }

            .sanad-filter-form .form-control-label {
                font-weight: 600;
                font-size: 13px;
            }

            .quick-request-queue .table-responsive {
                border: 1px solid var(--quick-shell-line);
                border-radius: 16px;
                overflow-x: auto;
                background: var(--quick-shell-surface);
            }

            .sanad-requests-table {
                min-width: 980px;
                margin: 0;
                table-layout: fixed;
            }

            .sanad-requests-table th {
                background: color-mix(in srgb, var(--quick-shell-bg) 72%, var(--quick-shell-surface));
                text-transform: uppercase;
                font-size: 11px;
                letter-spacing: .04em;
            }

            .sanad-requests-table td,
            .sanad-requests-table th {
                vertical-align: middle;
                white-space: normal;
                word-break: normal;
            }

            .sanad-requests-table small {
                display: block;
                color: var(--quick-shell-muted);
                margin-top: 4px;
            }

            .sanad-employee-chip {
                display: inline-block;
                max-width: 160px;
                margin: 2px 4px 2px 0;
                padding: 3px 8px;
                border-radius: 999px;
                background: color-mix(in srgb, var(--quick-shell-bg) 80%, transparent);
                color: var(--quick-shell-muted);
                font-size: 12px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                vertical-align: middle;
            }

            .sanad-empty-state {
                padding: 18px;
                color: var(--quick-shell-muted);
                text-align: center;
            }

            .sanad-row-flags {
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
                margin-top: 6px;
            }

            .sanad-row-flags span {
                display: inline-block;
                padding: 2px 6px;
                border-radius: 999px;
                background: #fff4e5;
                color: #9a5b00;
                font-size: 11px;
            }

            .quick-primary-btn,
            .quick-secondary-btn {
                border-radius: 12px;
                border: 1px solid var(--quick-shell-line);
                font-size: 12px;
                font-weight: 800;
                min-height: 42px;
                padding: 9px 18px;
                text-transform: none;
            }

            .quick-primary-btn {
                border-color: var(--quick-blue);
                background: var(--quick-blue);
                color: #fff;
            }

            .quick-secondary-btn {
                background: color-mix(in srgb, var(--quick-shell-bg) 72%, var(--quick-shell-surface));
                color: var(--quick-shell-ink);
            }

            .quick-advanced-filters summary {
                color: var(--quick-blue);
                cursor: pointer;
                font-size: 12px;
                font-weight: 800;
            }

            @media (max-width: 899px) {
                .quick-request-queue {
                    max-width: none;
                }

                .quick-queue-panel {
                    padding: 14px;
                }

                .sanad-summary-card {
                    border-radius: 14px;
                }

                .quick-request-queue .table-responsive {
                    overflow: visible;
                    border: 0;
                    background: transparent;
                }

                .sanad-requests-table {
                    display: block;
                    min-width: 0;
                    border-collapse: separate;
                }

                .sanad-requests-table thead {
                    display: none;
                }

                .sanad-requests-table tbody,
                .sanad-requests-table tr,
                .sanad-requests-table td {
                    display: block;
                    width: 100%;
                }

                .sanad-requests-table tr {
                    margin-bottom: 12px;
                    border: 1px solid var(--quick-shell-line);
                    border-radius: 16px;
                    background: var(--quick-shell-surface);
                    overflow: hidden;
                }

                .sanad-requests-table td {
                    display: grid;
                    grid-template-columns: minmax(92px, 34%) 1fr;
                    gap: 12px;
                    border-bottom: 1px solid var(--quick-shell-line);
                    padding: 12px 14px;
                    text-align: start !important;
                }

                .sanad-requests-table td:last-child {
                    border-bottom: 0;
                }

                .sanad-requests-table td::before {
                    content: attr(data-label);
                    color: var(--quick-shell-muted);
                    font-size: 11px;
                    font-weight: 900;
                    text-transform: uppercase;
                }
            }
        </style>
    @endonce
</x-master-layout>
