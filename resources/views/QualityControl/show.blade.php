@php
    $locale = app()->getLocale();
    $isAr = in_array($locale, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']) || session('dir') === 'rtl';
    $pageTitle = $isAr ? 'تفاصيل حالة الجودة' : 'Quality Case Details';

    $enumKey = AppEnumsComplaintEnums::GetById($data->status);
    $isFinished = ($data->status == AppEnumsComplaintEnums::finished);
@endphp

<x-master-layout>
    <div class="quick-qc-show-page" dir="{{ $isAr ? 'rtl' : 'ltr' }}">

        <!-- Hero Header -->
        <div class="quick-admin-hero">
            <div class="quick-admin-hero-content">
                <div class="quick-admin-hero-eyebrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                    <span>{{ $isAr ? 'متابعة وتدقيق حالة الجودة' : 'Quality Audit & Resolution Stream' }}</span>
                </div>
                <h1>{{ $data->title ?: ($isAr ? 'حالة جودة #' . $data->id : 'Quality Case #' . $data->id) }}</h1>
                <p>{{ $isAr ? 'متابعة سجل الملاحظات والردود المتبادلة بين إدارة الجودة والشركاء ومزودي الخدمة.' : 'Review case comments, audit history, and exchange resolution messages with the partner.' }}</p>
            </div>

            <div class="quick-admin-hero-actions">
                <a href="{{ route('complaint.index_data') }}" class="quick-admin-hero-btn quick-admin-hero-btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    <span>{{ $isAr ? 'العودة لقائمة الحالات' : 'Back to Cases List' }}</span>
                </a>
            </div>
        </div>

        <!-- Case Metadata Overview Card -->
        <div class="quick-card mb-4">
            <div class="quick-card-header">
                <div>
                    <h3 class="quick-card-title">{{ $isAr ? 'بيانات الحالة الأساسية' : 'Case Overview' }}</h3>
                    <div class="quick-card-sub">{{ $isAr ? 'المعلومات التعريفية والحالة الراهنة لهذا السجل' : 'Key metadata, status, and partner assignment' }}</div>
                </div>
                <div>
                    <span class="quick-badge {{ $isFinished ? 'quick-badge-success' : 'quick-badge-blue' }}" style="font-size: 13px; padding: 6px 14px;">
                        {{ trans('messages.'.$enumKey) }}
                    </span>
                </div>
            </div>

            <div class="quick-qc-meta-grid">
                <div class="quick-qc-meta-item">
                    <span class="quick-qc-meta-label">{{ $isAr ? 'مزود الخدمة / الشريك' : 'Partner / Provider' }}</span>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <div class="quick-customer-avatar" style="width:30px;height:30px;font-size:12px;">
                            {{ mb_substr(optional($data->provider)->display_name ?: optional($data->provider)->first_name ?: 'P', 0, 1) }}
                        </div>
                        <strong style="color: var(--quick-shell-ink);">{{ optional($data->provider)->display_name ?: optional($data->provider)->first_name ?: ('Partner #' . $data->provider_id) }}</strong>
                    </div>
                </div>

                <div class="quick-qc-meta-item">
                    <span class="quick-qc-meta-label">{{ $isAr ? 'نوع المشكلة' : 'Issue Type' }}</span>
                    <strong style="color: var(--quick-shell-ink); font-size: 14px; margin-top: 4px; display: block;">
                        {{ str_replace('_', ' ', ucfirst($data->issue_type ?: 'customer_complaint')) }}
                    </strong>
                </div>

                <div class="quick-qc-meta-item">
                    <span class="quick-qc-meta-label">{{ $isAr ? 'أنشئ بواسطة' : 'Created By' }}</span>
                    <strong style="color: var(--quick-shell-ink); font-size: 14px; margin-top: 4px; display: block;">
                        {{ optional($data->createdby)->display_name ?: optional($data->createdby)->first_name ?: '-' }}
                    </strong>
                </div>

                <div class="quick-qc-meta-item">
                    <span class="quick-qc-meta-label">{{ $isAr ? 'تاريخ التسجيل' : 'Date Created' }}</span>
                    <span style="color: var(--quick-shell-muted); font-size: 13px; font-weight: 600; margin-top: 4px; display: block;">
                        {{ optional($data->created_at)->format('Y-m-d H:i') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Comments & Conversation Stream -->
        <div class="quick-card mb-4">
            <div class="quick-card-header">
                <div>
                    <h3 class="quick-card-title">{{ $isAr ? 'سجل الملاحظات والردود' : 'Comments & Activity Stream' }}</h3>
                    <div class="quick-card-sub">{{ $isAr ? 'الرسائل والمرفقات المتبادلة حول هذه الحالة' : 'Timeline of notes, replies, and attachments' }}</div>
                </div>
                <span class="quick-badge quick-badge-neutral">
                    {{ $data->complaints_comment->count() }} {{ $isAr ? 'ردود' : 'Replies' }}
                </span>
            </div>

            <div class="quick-qc-comments-stream">
                @forelse($data->complaints_comment as $package)
                    @php
                        $isCurrentUser = auth()->check() && $package->created_by == auth()->user()->id;
                    @endphp
                    <div class="quick-qc-comment-item {{ $isCurrentUser ? 'quick-qc-comment-mine' : '' }}">
                        <div class="quick-qc-comment-avatar">
                            {{ mb_substr(optional($package->user)->first_name ?: optional($package->user)->display_name ?: 'U', 0, 1) }}
                        </div>
                        <div class="quick-qc-comment-bubble">
                            <div class="quick-qc-comment-header">
                                <strong>{{ optional($package->user)->first_name }} {{ optional($package->user)->last_name }}</strong>
                                <span class="quick-qc-comment-time">{{ optional($package->created_at)->format('Y-m-d H:i') }}</span>
                            </div>
                            <div class="quick-qc-comment-body">
                                {{ $package->comment }}
                            </div>
                            @if($package->file != "")
                                <div class="quick-qc-comment-attachment">
                                    <a href="{{ asset($package->file) }}" target="_blank" class="quick-table-btn quick-table-btn-outline">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                        <span>{{ $isAr ? 'عرض الملف المرفق' : 'View Attachment' }}</span>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="quick-table-empty-state py-4">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:36px;height:36px;color:var(--quick-shell-muted);"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        <p>{{ $isAr ? 'لا توجد ردود مسجلة بعد' : 'No comments or replies recorded yet.' }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Add Reply Card -->
        <div class="quick-card">
            <div class="quick-card-header mb-3">
                <div>
                    <h3 class="quick-card-title">{{ $isAr ? 'إضافة رد أو توجيه جديد' : 'Submit Reply / Note' }}</h3>
                    <div class="quick-card-sub">{{ $isAr ? 'كتابة رد موجه وتحديث حالة السجل تلقائياً' : 'Add follow-up notes or instructions for this quality case' }}</div>
                </div>
            </div>

            <form action="{{ route('complaint.reply_submitComplaint', $data->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group mb-3">
                    <label for="reply_text" class="quick-form-label">{{ $isAr ? 'نص الرد / الملاحظة' : 'Reply / Note Content' }} <span class="text-danger">*</span></label>
                    <textarea id="reply_text" name="reply" class="quick-form-textarea" rows="3" required placeholder="{{ $isAr ? 'اكتب ملاحظتك أو توجيهك هنا...' : 'Write your response or feedback here...' }}"></textarea>
                </div>

                <div class="form-group mb-4">
                    <label for="reply_file" class="quick-form-label">{{ $isAr ? 'مرفق توضيحي (اختياري)' : 'Attachment (Optional)' }}</label>
                    <input type="file" name="file" id="reply_file" class="quick-form-input">
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" class="quick-filter-btn quick-filter-btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        <span>{{ $isAr ? 'إرسال الرد' : 'Send Reply' }}</span>
                    </button>
                </div>
            </form>
        </div>

    </div>

    @once
    <style>
        .quick-qc-show-page {
            width: 100%;
        }

        .quick-qc-meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            padding: 16px;
            background: color-mix(in srgb, var(--quick-shell-bg) 60%, var(--quick-shell-surface));
            border-radius: 14px;
            border: 1px solid var(--quick-shell-line);
        }

        .quick-qc-meta-item {
            display: flex;
            flex-direction: column;
        }

        .quick-qc-meta-label {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: var(--quick-shell-muted);
        }

        .quick-qc-comments-stream {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .quick-qc-comment-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .quick-qc-comment-avatar {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(31,107,255,.1);
            color: var(--quick-blue);
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 13px;
            flex-shrink: 0;
        }

        .quick-qc-comment-bubble {
            flex: 1;
            padding: 16px;
            border-radius: 14px;
            background: color-mix(in srgb, var(--quick-shell-bg) 50%, var(--quick-shell-surface));
            border: 1px solid var(--quick-shell-line);
        }

        .quick-qc-comment-mine .quick-qc-comment-bubble {
            background: rgba(31,107,255,.04);
            border-color: rgba(31,107,255,.18);
        }

        .quick-qc-comment-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .quick-qc-comment-header strong {
            font-size: 13px;
            color: var(--quick-shell-ink);
        }

        .quick-qc-comment-time {
            font-size: 11px;
            color: var(--quick-shell-muted);
        }

        .quick-qc-comment-body {
            font-size: 13px;
            color: var(--quick-shell-ink);
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .quick-qc-comment-attachment {
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px dashed var(--quick-shell-line);
        }

        .quick-form-label {
            font-size: 12px;
            font-weight: 800;
            color: var(--quick-shell-ink);
            margin-bottom: 6px;
            display: block;
        }

        .quick-form-input,
        .quick-form-textarea {
            width: 100%;
            border-radius: 12px;
            border: 1px solid var(--quick-shell-line);
            background: var(--quick-shell-surface);
            color: var(--quick-shell-ink);
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 600;
            outline: none;
            transition: all .15s ease;
        }

        .quick-form-input:focus,
        .quick-form-textarea:focus {
            border-color: var(--quick-blue);
            box-shadow: 0 0 0 3px rgba(31,107,255,.12);
        }
    </style>
    @endonce
</x-master-layout>

