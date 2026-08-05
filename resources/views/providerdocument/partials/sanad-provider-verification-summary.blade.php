@php
    $summary = $sanadProviderVerificationSummary ?? [];
@endphp

<div class="row">
    <div class="col-lg-12">
        <div class="card sanad-provider-verification-summary">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="font-weight-bold mb-1">Sanad Verification Review</h4>
                    <span class="text-muted">Provider government document completion and approval status</span>
                </div>
                <span class="badge badge-light">{{ $summary['verification_status'] ?? 'Pending Review' }}</span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <div class="sanad-provider-verification-kpi">
                            <span>Submitted Documents</span>
                            <strong>{{ $summary['total_documents'] ?? 0 }}</strong>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <div class="sanad-provider-verification-kpi">
                            <span>Verified</span>
                            <strong>{{ $summary['verified_documents'] ?? 0 }}</strong>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <div class="sanad-provider-verification-kpi">
                            <span>Pending</span>
                            <strong>{{ $summary['pending_documents'] ?? 0 }}</strong>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-0">
                        <div class="sanad-provider-verification-kpi">
                            <span>Required Types</span>
                            <strong>{{ $summary['required_document_types'] ?? 0 }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    <style>
        .sanad-provider-verification-summary .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }

        .sanad-provider-verification-kpi {
            min-height: 82px;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            background: #fff;
        }

        .sanad-provider-verification-kpi span {
            color: #6c757d;
            font-size: 13px;
        }

        .sanad-provider-verification-kpi strong {
            font-size: 22px;
            line-height: 1.1;
            text-align: right;
        }
    </style>
@endonce
