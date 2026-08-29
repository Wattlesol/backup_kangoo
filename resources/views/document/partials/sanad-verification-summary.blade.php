@php
    $summary = $sanadVerificationSummary ?? [];
@endphp

@if(auth()->user()->hasAnyRole(['admin', 'demo_admin']))
    <div class="col-lg-12">
        <div class="card sanad-verification-summary">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    @php $isAr = app()->getLocale() === 'ar'; @endphp<h4 class="font-weight-bold mb-1">{{ $isAr ? 'التحقق الحكومي لمنصة كويك' : 'Quick Government Verification' }}</h4>
                    <span class="text-muted">{{ $isAr ? 'شروط المستندات المطلوبة، ملفات الشركاء، وجاهزية التحقق' : 'Required document rules, provider submissions, and verification readiness' }}</span>
                </div>
                @if($auth_user->can('document add'))
                    <a href="{{ route('document.create') }}" class="btn btn-sm btn-primary"><i class="fa fa-plus-circle"></i> {{ $isAr ? 'إضافة نوع مستند' : 'Add Document Type' }}</a>
                @endif
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-3">
                        <a class="sanad-verification-kpi" href="{{ route('document.index') }}">
                            <span>{{ $isAr ? 'أنواع المستندات' : 'Document Types' }}</span>
                            <strong>{{ $summary['active_document_types'] ?? 0 }}/{{ $summary['document_types'] ?? 0 }}</strong>
                        </a>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <a class="sanad-verification-kpi" href="{{ route('document.index') }}">
                            <span>{{ $isAr ? 'المستندات الإلزامية' : 'Required Types' }}</span>
                            <strong>{{ $summary['required_document_types'] ?? 0 }}</strong>
                        </a>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <a class="sanad-verification-kpi" href="{{ route('provider.index') }}">
                            <span>{{ $isAr ? 'الشركاء المعتمدون' : 'Verified Partners' }}</span>
                            <strong>{{ $summary['verified_partners'] ?? 0 }}/{{ $summary['partners'] ?? 0 }}</strong>
                        </a>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <a class="sanad-verification-kpi" href="{{ route('provider.index') }}">
                            <span>{{ $isAr ? 'وثائق شركاء قيد المراجعة' : 'Pending Provider Docs' }}</span>
                            <strong>{{ $summary['pending_provider_documents'] ?? 0 }}</strong>
                        </a>
                    </div>
                    <div class="col-xl-6 col-md-6 mb-3 mb-xl-0">
                        <div class="sanad-verification-note">
                            <span>{{ $isAr ? 'وثائق شركاء معتمدة' : 'Verified Provider Documents' }}</span>
                            <strong>{{ $summary['verified_provider_documents'] ?? 0 }}/{{ $summary['provider_documents'] ?? 0 }}</strong>
                        </div>
                    </div>
                    <div class="col-xl-6 col-md-6 mb-0">
                        <div class="sanad-verification-note">
                            <span>{{ $isAr ? 'التحكم بالتحقق' : 'Verification Control' }}</span>
                            <strong>{{ $isAr ? 'مراجعة الإدارة ومفاتيح المستندات الإلزامية مفعلة' : 'Admin review and required document toggles enabled' }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

@once
    <style>
        .sanad-verification-summary .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }

        .sanad-verification-kpi,
        .sanad-verification-note {
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
        }

        .sanad-verification-kpi:hover {
            color: inherit;
            border-color: rgba(255, 111, 0, 0.35);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
            text-decoration: none;
        }

        .sanad-verification-kpi span,
        .sanad-verification-note span {
            color: #6c757d;
            font-size: 13px;
        }

        .sanad-verification-kpi strong,
        .sanad-verification-note strong {
            font-size: 20px;
            line-height: 1.1;
            text-align: right;
            overflow-wrap: anywhere;
        }
    </style>
@endonce
