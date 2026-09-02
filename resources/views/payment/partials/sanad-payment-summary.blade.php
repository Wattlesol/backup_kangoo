@php
    $summary = $sanadPaymentSummary ?? [];
    $isAr = in_array(app()->getLocale(), ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']) || session('dir') === 'rtl';
    $roleScope = $summary['role_scope'] ?? [
        'label' => 'Quick finance scope',
        'description' => 'Financial information is scoped by the signed-in user role.',
        'can_bulk_manage' => false,
    ];
@endphp

<div class="col-lg-12">
    <!-- Hero Banner Card -->
    <div class="quick-admin-hero">
        <div class="quick-admin-hero-content">
            <div class="quick-admin-hero-eyebrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                <span>{{ $isAr ? 'المدفوعات والتسويات الآمنة' : 'Secure Payments & Settlements' }}</span>
            </div>
            <h1>{{ $isAr ? 'المركز المالي لمنصة كويك' : 'Quick Financial Center' }}</h1>
            <p>{{ $isAr ? 'مدفوعات العملاء، التسويات، العمولة، ضريبة القيمة المضافة، المرتجعات، الفواتير، والمعاملات المالية.' : 'Customer payments, partner settlements, platform commissions, VAT, refunds, invoices, transactions, and digital wallets.' }}</p>
        </div>

        <div class="quick-admin-hero-actions">
            <a href="{{ route('sanad.requests.index', ['payment_state' => 'pending']) }}" class="quick-admin-hero-btn quick-admin-hero-btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span>{{ $isAr ? 'مدفوعات معلقة' : 'Pending Payments' }}</span>
            </a>
            @if(Route::has('providerpayout.index'))
                <a href="{{ route('providerpayout.index') }}" class="quick-admin-hero-btn quick-admin-hero-btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>{{ $isAr ? 'التسويات' : 'Settlements' }}</span>
                </a>
            @endif
            @if(Route::has('wallet.index'))
                <a href="{{ route('wallet.index') }}" class="quick-admin-hero-btn quick-admin-hero-btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M20 7H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M16 12h4"/></svg>
                    <span>{{ $isAr ? 'المحفظة' : 'Wallet' }}</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Scope Indicator -->
    <div class="sanad-finance-scope mb-4">
        <div>
            <strong>{{ $roleScope['label'] }}</strong>
            <span>{{ $roleScope['description'] }}</span>
        </div>
        <span class="quick-pill {{ ($roleScope['can_bulk_manage'] ?? false) ? 'quick-pill-success' : 'quick-pill-neutral' }}">
            {{ ($roleScope['can_bulk_manage'] ?? false) ? ($isAr ? 'الإدارة الجماعية مفعّلة' : 'Bulk management enabled') : ($isAr ? 'عرض مقيّد حسب الدور' : 'Scoped view only') }}
        </span>
    </div>

    <!-- KPI Grid 1: Revenue & Core Stats -->
    <div class="quick-kpi-grid mb-4">
        <div class="quick-kpi-card">
            <div class="quick-kpi-header">
                <span>{{ $isAr ? 'مدفوعات العملاء' : 'Customer Payments' }}</span>
                <div class="quick-kpi-icon" style="background: rgba(31,107,255,.1); color: #1f6bff;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                </div>
            </div>
            <div class="quick-kpi-value">{{ $summary['customer_payments'] ?? 0 }}</div>
            <div class="quick-kpi-sub">
                <b class="quick-trend-up">{{ $summary['paid_payments'] ?? 0 }}</b>
                <span>{{ $isAr ? 'عملية مدفوعة' : 'paid transactions' }}</span>
            </div>
        </div>

        <div class="quick-kpi-card">
            <div class="quick-kpi-header">
                <span>{{ $isAr ? 'مدفوعات معلقة' : 'Pending Payments' }}</span>
                <div class="quick-kpi-icon" style="background: rgba(245,158,11,.1); color: #f59e0b;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
            </div>
            <div class="quick-kpi-value">{{ getPriceFormat($summary['pending_amount'] ?? 0) }}</div>
            <div class="quick-kpi-sub">
                <b style="color: #f59e0b;">{{ $isAr ? 'بانتظار التحصيل' : 'awaiting capture' }}</b>
            </div>
        </div>

        <div class="quick-kpi-card">
            <div class="quick-kpi-header">
                <span>{{ $isAr ? 'التسويات والمصروفات' : 'Settlements' }}</span>
                <div class="quick-kpi-icon" style="background: rgba(16,185,129,.1); color: #10b981;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
            </div>
            <div class="quick-kpi-value">{{ getPriceFormat($summary['settled_amount'] ?? 0) }}</div>
            <div class="quick-kpi-sub">
                <b class="quick-trend-up">{{ $summary['settlements_count'] ?? 0 }}</b>
                <span>{{ $isAr ? 'تسوية منجزة' : 'settlements done' }}</span>
            </div>
        </div>

        <div class="quick-kpi-card">
            <div class="quick-kpi-header">
                <span>{{ $isAr ? 'عمولة المنصة' : 'Platform Commission' }}</span>
                <div class="quick-kpi-icon" style="background: rgba(139,92,246,.1); color: #8b5cf6;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 18V6"/></svg>
                </div>
            </div>
            <div class="quick-kpi-value">{{ getPriceFormat($summary['platform_commission'] ?? 0) }}</div>
            <div class="quick-kpi-sub">
                <b class="quick-trend-up">{{ getPriceFormat($summary['vat_amount'] ?? 0) }}</b>
                <span>{{ $isAr ? 'ضريبة القيمة المضافة' : 'VAT inclusive' }}</span>
            </div>
        </div>
    </div>

    <!-- KPI Grid 2: Digital Wallet Balances -->
    <div class="quick-kpi-grid mb-4">
        <div class="quick-kpi-card">
            <div class="quick-kpi-header">
                <span>{{ $isAr ? 'رصيد المحفظة الحالي' : 'Current Balance' }}</span>
                <div class="quick-kpi-icon" style="background: rgba(31,107,255,.1); color: #1f6bff;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M16 12h4"/></svg>
                </div>
            </div>
            <div class="quick-kpi-value">{{ getPriceFormat($summary['wallet_current_balance'] ?? 0) }}</div>
            <div class="quick-kpi-sub">
                <span>{{ $isAr ? 'رصيد متاح في المحفظة' : 'available wallet balance' }}</span>
            </div>
        </div>

        <div class="quick-kpi-card">
            <div class="quick-kpi-header">
                <span>{{ $isAr ? 'الرصيد المعلّق' : 'Pending Balance' }}</span>
                <div class="quick-kpi-icon" style="background: rgba(245,158,11,.1); color: #f59e0b;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
            </div>
            <div class="quick-kpi-value">{{ getPriceFormat($summary['wallet_pending_balance'] ?? 0) }}</div>
            <div class="quick-kpi-sub">
                <b style="color: #f59e0b;">{{ $isAr ? 'تحت إجراءات التسوية' : 'under clearance' }}</b>
            </div>
        </div>

        <div class="quick-kpi-card">
            <div class="quick-kpi-header">
                <span>{{ $isAr ? 'الرصيد المحرر' : 'Released Balance' }}</span>
                <div class="quick-kpi-icon" style="background: rgba(16,185,129,.1); color: #10b981;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
            </div>
            <div class="quick-kpi-value">{{ getPriceFormat($summary['wallet_released_balance'] ?? 0) }}</div>
            <div class="quick-kpi-sub">
                <b class="quick-trend-up">{{ $isAr ? 'جاهز للتحويل' : 'ready for payout' }}</b>
            </div>
        </div>

        <div class="quick-kpi-card">
            <div class="quick-kpi-header">
                <span>{{ $isAr ? 'التسوية القادمة' : 'Upcoming Settlement' }}</span>
                <div class="quick-kpi-icon" style="background: rgba(139,92,246,.1); color: #8b5cf6;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
            </div>
            <div class="quick-kpi-value">{{ getPriceFormat($summary['upcoming_settlement'] ?? 0) }}</div>
            <div class="quick-kpi-sub">
                <b class="quick-trend-up">{{ $isAr ? 'الدورة القادمة' : 'next scheduled cycle' }}</b>
            </div>
        </div>
    </div>

    <!-- Settlement History & Transaction History Split Cards -->
    <div class="row mb-4">
        <!-- Settlement History -->
        <div class="col-xl-6 mb-3 mb-xl-0">
            <div class="quick-card h-100">
                <div class="quick-card-header mb-3">
                    <div>
                        <h3 class="quick-card-title">{{ $isAr ? 'سجل التسويات' : 'Settlement History' }}</h3>
                        <div class="quick-card-sub">{{ $isAr ? 'آخر التحويلات المصرفية والتسويات المعتمدة' : 'Recent bank transfers and settled payouts' }}</div>
                    </div>
                    <span class="quick-pill quick-pill-neutral">
                        {{ $summary['settlements_count'] ?? 0 }} {{ $isAr ? 'سجل' : 'records' }}
                    </span>
                </div>

                <div class="quick-table-responsive">
                    <table class="quick-table">
                        <thead>
                            <tr>
                                <th>{{ $isAr ? 'رقم التسوية' : 'Settlement No.' }}</th>
                                <th>{{ $isAr ? 'تاريخ التحويل' : 'Transfer Date' }}</th>
                                <th>{{ $isAr ? 'المبلغ' : 'Amount' }}</th>
                                <th>{{ $isAr ? 'المرجع / الطريقة' : 'Reference' }}</th>
                                <th style="text-align: center;">{{ $isAr ? 'الحالة' : 'Status' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($summary['recent_settlements'] ?? []) as $settlement)
                                <tr>
                                    <td>
                                        <span class="font-weight-bold" style="color: var(--quick-blue);">#{{ $settlement->id }}</span>
                                    </td>
                                    <td>{{ $settlement->paid_date ? date('Y-m-d', strtotime($settlement->paid_date)) : '-' }}</td>
                                    <td>
                                        <strong>{{ getPriceFormat($settlement->amount ?? 0) }}</strong>
                                    </td>
                                    <td>{{ $settlement->payment_method ?? '-' }}</td>
                                    <td style="text-align: center;">
                                        @php
                                            $stStatus = strtolower($settlement->status ?? 'pending');
                                        @endphp
                                        <span class="quick-badge {{ in_array($stStatus, ['paid', 'completed', 'settled', 'success'], true) ? 'quick-badge-success' : 'quick-badge-warning' }}">
                                            {{ ucfirst(str_replace('_', ' ', $settlement->status ?? 'pending')) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="quick-table-empty">
                                        <div class="quick-table-empty-state">
                                            <p>{{ $isAr ? 'لا توجد تسويات مسجلة حالياً.' : 'No settlement history available.' }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Transaction History -->
        <div class="col-xl-6">
            <div class="quick-card h-100">
                <div class="quick-card-header mb-3">
                    <div>
                        <h3 class="quick-card-title">{{ $isAr ? 'سجل المعاملات والمدفوعات' : 'Transaction History' }}</h3>
                        <div class="quick-card-sub">{{ $isAr ? 'آخر الدفعات والفواتير الصادرة عبر المنصة' : 'Recent customer payments and issued invoices' }}</div>
                    </div>
                    <span class="quick-pill quick-pill-neutral">
                        {{ $summary['total_payments'] ?? 0 }} {{ $isAr ? 'دفعة' : 'payments' }}
                    </span>
                </div>

                <div class="quick-table-responsive">
                    <table class="quick-table">
                        <thead>
                            <tr>
                                <th>{{ $isAr ? 'الفاتورة' : 'Invoice' }}</th>
                                <th>{{ $isAr ? 'العميل' : 'Customer' }}</th>
                                <th>{{ $isAr ? 'الخدمة' : 'Service' }}</th>
                                <th>{{ $isAr ? 'المبلغ' : 'Amount' }}</th>
                                <th style="text-align: center;">{{ $isAr ? 'الحالة' : 'Status' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($summary['recent_transactions'] ?? []) as $transaction)
                                <tr>
                                    <td>
                                        @if($transaction->booking_id && Route::has('invoice_pdf'))
                                            <a href="{{ route('invoice_pdf', $transaction->booking_id) }}" class="quick-table-ref-badge">
                                                #{{ $transaction->booking_id }}
                                            </a>
                                        @else
                                            <span class="font-weight-bold" style="color: var(--quick-blue);">#{{ $transaction->booking_id ?? $transaction->id }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ optional($transaction->customer)->display_name ?? '-' }}</strong>
                                    </td>
                                    <td>
                                        <span class="quick-badge quick-badge-neutral">{{ optional(optional($transaction->booking)->service)->name ?? '-' }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ getPriceFormat($transaction->total_amount ?? 0) }}</strong>
                                    </td>
                                    <td style="text-align: center;">
                                        @php
                                            $txStatus = strtolower($transaction->payment_status ?? 'pending');
                                        @endphp
                                        <span class="quick-badge {{ in_array($txStatus, ['paid', 'completed', 'success'], true) ? 'quick-badge-success' : 'quick-badge-warning' }}">
                                            {{ ucfirst(str_replace('_', ' ', $transaction->payment_status ?? 'pending')) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="quick-table-empty">
                                        <div class="quick-table-empty-state">
                                            <p>{{ $isAr ? 'لا توجد دفعات أو فواتير مسجلة.' : 'No transaction history available.' }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    <style>
        .sanad-finance-scope {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            border: 1px solid var(--quick-shell-line);
            border-radius: 16px;
            background: color-mix(in srgb, var(--quick-shell-bg) 60%, var(--quick-shell-surface));
        }

        .sanad-finance-scope strong {
            display: block;
            font-size: 13px;
            color: var(--quick-shell-ink);
        }

        .sanad-finance-scope span {
            color: var(--quick-shell-muted);
            font-size: 12px;
        }

        .quick-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 99px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid transparent;
        }

        .quick-pill-neutral {
            background: color-mix(in srgb, var(--quick-shell-bg) 80%, var(--quick-shell-surface));
            border-color: var(--quick-shell-line);
            color: var(--quick-shell-ink);
        }

        .quick-pill-success {
            background: rgba(16,185,129,.12);
            color: #059669;
            border-color: rgba(16,185,129,.25);
        }

        .quick-pill-warning {
            background: rgba(245,158,11,.12);
            color: #d97706;
            border-color: rgba(245,158,11,.25);
        }
    </style>
@endonce

