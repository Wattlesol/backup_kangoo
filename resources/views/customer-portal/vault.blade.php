<x-master-layout>
@php $isAr = app()->getLocale() === 'ar'; @endphp<div class="container-fluid sanad-page">
    <div class="sanad-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="sanad-title">{{ app()->getLocale() === "ar" ? "خزينة المستندات" : "Document Vault" }}</h1>
            <div class="sanad-muted">{{ app()->getLocale() === "ar" ? "احفظ مستنداتك الشخصية والتجارية الأساسية هنا لسهولة إرفاقها ومشاركتها في الطلبات والمحادثات." : "Store your essential personal & commercial documents here to easily re-attach them to messages and requests." }}</div>
        </div>
        <span class="badge badge-info p-2" style="font-size: 14px;"><i class="fas fa-folder-open mr-1"></i> {{ $documents->count() }} {{ $isAr ? 'مستندات محفوظة' : 'Saved Documents' }}</span>
    </div>

    <div class="sanad-card mb-4 shadow-sm">
        <div class="sanad-card-header font-weight-bold"><i class="fas fa-cloud-upload-alt mr-2 text-primary"></i> {{ $isAr ? "إضافة مستند جديد إلى الخزينة" : "Add New Document to Vault" }}</div>
        <div class="sanad-card-body">
            <form method="post" action="{{ route('customer-portal.vault.analyze') }}" enctype="multipart/form-data" class="vault-upload-form">
                @csrf
                <div class="row align-items-end">
                    <div class="col-md-5 mb-2">
                        <label class="small font-weight-bold text-muted">{{ $isAr ? "اسم / نوع المستند:" : "Document Name / Type:" }}</label>
                        <input class="sanad-form-control form-control" name="document_type" placeholder="{{ $isAr ? 'مثال: الهوية الوطنية، السجل التجاري، جواز السفر' : 'e.g., National ID, Commercial Register, Passport' }}" value="{{ old('document_type') }}" required>
                    </div>
                    <div class="col-md-5 mb-2">
                        <label class="small font-weight-bold text-muted">{{ $isAr ? "اختر الملف:" : "Select File:" }}</label>
                        <div class="vault-file-picker">
                            <label class="vault-file-icon" for="vault-file-input" title="{{ $isAr ? 'إرفاق مستند' : 'Attach document' }}" aria-label="{{ $isAr ? 'إرفاق مستند' : 'Attach document' }}">
                                <i class="fas fa-paperclip"></i>
                            </label>
                            <span class="vault-file-name" id="vault-file-name">{{ $isAr ? "لم يتم اختيار ملف" : "No file selected" }}</span>
                            <input id="vault-file-input" type="file" name="file" accept=".jpg,.jpeg,.png,.pdf,.doc,.dox,.docx,.docs,image/jpeg,image/png,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required onchange="document.getElementById('vault-file-name').textContent = this.files && this.files[0] ? this.files[0].name : @js($isAr ? 'لم يتم اختيار ملف' : 'No file selected');">
                        </div>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button class="sanad-btn btn btn-primary w-100 vault-upload-button" type="submit">
                            <span class="vault-upload-ready"><i class="fas fa-upload mr-1"></i> {{ $isAr ? "رفع" : "Upload" }}</span>
                            <span class="vault-upload-busy" aria-label="{{ $isAr ? 'جارٍ معالجة المستند' : 'Processing document' }}">
                                <span class="vault-progress-ring" aria-hidden="true"></span>
                            </span>
                        </button>
                    </div>
                </div>
                <div class="alert alert-warning mt-3 mb-0 vault-upload-error" hidden></div>
                @error('file')
                    <div class="alert alert-warning mt-3 mb-0">{{ $message }}</div>
                @enderror
            </form>
        </div>
    </div>

    <div class="sanad-card shadow-sm">
        <div class="sanad-card-header font-weight-bold"><i class="fas fa-folder mr-2 text-warning"></i> {{ $isAr ? "مستنداتك المحفوظة في الخزينة" : "Your Saved Vault Documents" }}</div>
        <div class="sanad-card-body table-responsive">
            <table class="sanad-table table align-middle">
                <thead>
                    <tr>
                        <th>{{ $isAr ? "نوع المستند" : "Document Type" }}</th>
                        <th>{{ $isAr ? "الحالة" : "Status" }}</th>
                        <th>{{ $isAr ? "اسم الملف" : "File Name" }}</th>
                        <th>{{ $isAr ? "تاريخ الانتهاء" : "Expiry Date" }}</th>
                        <th>{{ $isAr ? "التذكير" : "Reminder" }}</th>
                        <th>{{ $isAr ? "الإجراءات" : "Actions" }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $document)
                        <tr>
                            <td><strong>{{ $document->document_type }}</strong></td>
                            <td>
                                <span class="sanad-badge ok badge badge-success">{{ quick_status_label($document->verification_status ?: 'Stored') }}</span>
                                <div class="small text-muted mt-1">
                                    {{ $isAr ? 'التعرّف الضوئي:' : 'OCR:' }}
                                    @if(($document->ocr_status ?: 'pending') === 'pending')
                                        <span class="vault-ocr-inline"><span class="vault-progress-ring mini" aria-hidden="true"></span> {{ $isAr ? 'قيد المعالجة' : 'Pending' }}</span>
                                    @else
                                        {{ quick_status_label($document->ocr_status) }}
                                    @endif
                                </div>
                            </td>
                            <td><i class="far fa-file-alt text-muted mr-1"></i> {{ $document->file_name }}</td>
                            <td>
                                @if($document->expiry_date)
                                    <strong>{{ $document->expiry_date->format('Y-m-d') }}</strong>
                                    @if($document->expiry_date->isPast())
                                        <div class="small text-danger">{{ $isAr ? "منتهي الصلاحية" : "Expired" }}</div>
                                    @else
                                        <div class="small text-muted">{{ $document->expiry_date->diffForHumans() }}</div>
                                    @endif
                                @else
                                    <span class="text-muted small">{{ $isAr ? "غير محدد" : "Not detected" }}</span>
                                @endif
                            </td>
                            <td>
                                @if($document->expiry_reminder_at && $document->expiry_reminder_enabled !== false)
                                    <strong>{{ $document->expiry_reminder_at->format('Y-m-d') }}</strong>
                                    <div class="small text-muted">{{ $isAr ? "تنبيه عبر ذكاء كويك" : "Buzz by Quick AI" }}</div>
                                @elseif($document->expiry_reminder_enabled === false)
                                    <span class="text-muted small">{{ $isAr ? 'متوقف' : 'Off' }}</span>
                                @else
                                    <span class="text-muted small">{{ $isAr ? "غير معين" : "Not set" }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="vault-row-actions">
                                    @if($document->getFirstMediaUrl('sanad_document'))
                                        <a href="{{ $document->getFirstMediaUrl('sanad_document') }}" target="_blank" class="btn btn-sm btn-outline-primary" title="{{ $isAr ? 'عرض المستند' : 'View document' }}"><i class="fas fa-eye"></i></a>
                                        <a href="{{ $document->getFirstMediaUrl('sanad_document') }}" download class="btn btn-sm btn-outline-secondary" title="{{ $isAr ? 'تحميل المستند' : 'Download document' }}"><i class="fas fa-download"></i></a>
                                    @else
                                        <button class="btn btn-sm btn-outline-secondary" type="button" disabled title="{{ $isAr ? 'رابط الملف غير متاح' : 'No file link' }}"><i class="fas fa-eye-slash"></i></button>
                                    @endif
                                    <button class="btn btn-sm btn-outline-primary" type="button" title="{{ $isAr ? 'تعديل المستند' : 'Edit document' }}" onclick="document.getElementById('vault-edit-{{ $document->id }}').classList.toggle('show')"><i class="fas fa-pen"></i></button>
                                    <form method="post" action="{{ route('customer-portal.vault.delete', $document->id) }}" onsubmit="return confirm(@js($isAr ? 'هل أنت متأكد من حذف هذا المستند من الخزينة؟' : 'Are you sure you want to delete this document from your vault?'))">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="{{ $isAr ? 'حذف المستند' : 'Delete document' }}"><i class="fas fa-trash-alt"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr class="vault-edit-row" id="vault-edit-{{ $document->id }}">
                            <td colspan="6">
                                <form method="post" enctype="multipart/form-data" action="{{ route('customer-portal.vault.update', $document->id) }}" class="vault-edit-form">
                                    @csrf
                                    <label>
                                        <span>{{ $isAr ? "نوع المستند" : "Document Type" }}</span>
                                        <input class="form-control form-control-sm" name="document_type" value="{{ $document->document_type }}" required>
                                    </label>
                                    <label>
                                        <span>{{ $isAr ? "استبدال الملف" : "Replace File" }}</span>
                                        <input class="form-control form-control-sm" type="file" name="file" accept=".jpg,.jpeg,.png,.pdf,.doc,.dox,.docx,.docs,image/jpeg,image/png,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                                    </label>
                                    <label>
                                        <span>{{ $isAr ? "الصلاحية" : "Expiry" }}</span>
                                        <input type="date" name="expiry_date" class="form-control form-control-sm" value="{{ optional($document->expiry_date)->format('Y-m-d') }}">
                                    </label>
                                    <label>
                                        <span>{{ $isAr ? "تذكير في" : "Remind On" }}</span>
                                        <input type="date" name="expiry_reminder_at" class="form-control form-control-sm" value="{{ optional($document->expiry_reminder_at)->format('Y-m-d') }}">
                                    </label>
                                    <label class="vault-toggle">
                                        <input type="checkbox" name="expiry_reminder_enabled" value="1" {{ $document->expiry_reminder_enabled !== false ? 'checked' : '' }}>
                                        <span>{{ $isAr ? "مفعل في" : "Reminder on" }}</span>
                                    </label>
                                    <button class="btn btn-sm btn-primary" type="submit"><i class="fas fa-save mr-1"></i> {{ $isAr ? 'حفظ التغييرات' : 'Save Changes' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="fas fa-folder-open fa-2x mb-2 d-block"></i>
                                {{ $isAr ? 'لا توجد مستندات في خزنتك بعد. ارفع مستنداً للبدء.' : 'No documents in your vault yet. Upload one above to get started!' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="vault-modal-backdrop" id="vault-ocr-modal" hidden>
    <div class="vault-modal" role="dialog" aria-modal="true" aria-labelledby="vault-ocr-title">
        <form method="post" action="{{ route('customer-portal.vault.confirm') }}" class="vault-confirm-form">
            @csrf
            <input type="hidden" name="upload_token" id="vault-upload-token">
            <input type="hidden" name="expiry_reminder_enabled" value="1">
            <div class="vault-modal-header">
                <div>
                    <h3 id="vault-ocr-title">{{ $isAr ? "تأكيد تذكير المستند" : "Confirm Document Reminder" }}</h3>
                    <p id="vault-ocr-message">{{ $isAr ? 'راجع ما تعرّف عليه مساعد كويك قبل حفظ المستند.' : 'Review what Quick AI found before saving this document.' }}</p>
                </div>
                <button type="button" class="vault-modal-close" data-vault-cancel aria-label="{{ $isAr ? 'إغلاق' : 'Close' }}">&times;</button>
            </div>
            <div class="vault-modal-body">
                <div class="vault-modal-summary">
                    <span id="vault-confirm-file-name"></span>
                    <span id="vault-confirm-ocr-status"></span>
                </div>
                <label>
                    <span>{{ $isAr ? "تاريخ الانتهاء" : "Expiry Date" }}</span>
                    <input type="date" class="form-control" name="expiry_date" id="vault-confirm-expiry">
                </label>
                <label>
                    <span>{{ $isAr ? "تاريخ التذكير" : "Reminder Date" }}</span>
                    <input type="date" class="form-control" name="expiry_reminder_at" id="vault-confirm-reminder" required>
                </label>
                <div class="alert alert-warning vault-confirm-error" hidden></div>
            </div>
            <div class="vault-modal-actions">
                <button type="button" class="btn btn-outline-secondary" data-vault-cancel>{{ $isAr ? "إلغاء" : "Cancel" }}</button>
                <button type="submit" class="btn btn-primary vault-confirm-button">
                    <span class="vault-confirm-ready"><i class="fas fa-save mr-1"></i> {{ $isAr ? 'تأكيد وحفظ' : 'Confirm & Save' }}</span>
                    <span class="vault-confirm-busy"><span class="vault-progress-ring" aria-hidden="true"></span> {{ $isAr ? 'جارٍ الحفظ' : 'Saving' }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
<style>
    .vault-file-picker { display: flex; align-items: center; gap: 10px; border: 1px solid #dce3ee; border-radius: 8px; min-height: 48px; padding: 6px 10px; background: #fff; }
    .vault-file-icon { width: 38px; height: 36px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; margin: 0; background: #f2f4f7; color: #4f46e5; cursor: pointer; flex-shrink: 0; }
    .vault-file-name { min-width: 0; flex: 1; color: #667085; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .vault-file-picker input[type="file"] { display: none; }
    .vault-upload-button { min-height: 48px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
    .vault-upload-button .vault-upload-busy { display: none; align-items: center; justify-content: center; }
    .vault-upload-form.is-processing .vault-upload-ready { display: none; }
    .vault-upload-form.is-processing .vault-upload-busy { display: inline-flex; }
    .vault-upload-form.is-processing .vault-upload-button { cursor: wait; opacity: .95; }
    .vault-progress-ring { width: 22px; height: 22px; border-radius: 50%; border: 3px solid rgba(255, 255, 255, .45); border-top-color: #fff; display: inline-block; animation: vault-spin .8s linear infinite; flex-shrink: 0; }
    .vault-progress-ring.mini { width: 13px; height: 13px; border-width: 2px; border-color: rgba(79, 70, 229, .2); border-top-color: #4f46e5; }
    .vault-ocr-inline { display: inline-flex; align-items: center; gap: 5px; color: #4f46e5; }
    .vault-modal-backdrop { position: fixed; inset: 0; z-index: 1050; background: rgba(15, 23, 42, .38); display: flex; align-items: center; justify-content: center; padding: 20px; }
    .vault-modal-backdrop[hidden] { display: none; }
    .vault-modal { width: min(560px, 100%); background: #fff; border-radius: 8px; box-shadow: 0 24px 60px rgba(15, 23, 42, .22); overflow: hidden; }
    .vault-modal-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding: 20px 22px; border-bottom: 1px solid #e5e7eb; }
    .vault-modal-header h3 { margin: 0 0 4px; font-size: 22px; color: #111827; }
    .vault-modal-header p { margin: 0; color: #667085; }
    .vault-modal-close { border: 0; background: #f2f4f7; width: 34px; height: 34px; border-radius: 50%; font-size: 24px; line-height: 1; color: #475467; cursor: pointer; }
    .vault-modal-body { padding: 20px 22px; display: grid; gap: 14px; }
    .vault-modal-body label { display: grid; gap: 6px; margin: 0; font-weight: 700; color: #475467; }
    .vault-modal-summary { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 12px; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px; color: #475467; }
    .vault-modal-actions { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 22px; border-top: 1px solid #e5e7eb; }
    .vault-confirm-button { min-width: 148px; display: inline-flex; align-items: center; justify-content: center; }
    .vault-confirm-busy { display: none; align-items: center; gap: 8px; }
    .vault-confirm-form.is-saving .vault-confirm-ready { display: none; }
    .vault-confirm-form.is-saving .vault-confirm-busy { display: inline-flex; }
    .vault-row-actions { display: flex; align-items: center; gap: 7px; white-space: nowrap; }
    .vault-row-actions form { margin: 0; }
    .vault-row-actions .btn { width: 34px; height: 32px; display: inline-flex; align-items: center; justify-content: center; }
    .vault-edit-row { display: none; }
    .vault-edit-row.show { display: table-row; }
    .vault-edit-row td { background: #f8fafc; border-top: 0; }
    .vault-edit-form { display: grid; grid-template-columns: minmax(150px, 1.2fr) minmax(150px, 1.3fr) minmax(130px, .8fr) minmax(130px, .8fr) auto auto; gap: 10px; align-items: end; }
    .vault-edit-form label { margin: 0; display: flex; flex-direction: column; gap: 4px; min-width: 0; }
    .vault-edit-form label span { font-size: 12px; color: #667085; font-weight: 700; }
    .vault-edit-form .vault-toggle { flex-direction: row; align-items: center; gap: 6px; padding-bottom: 7px; }
    @media (max-width: 900px) {
        .vault-edit-form { grid-template-columns: 1fr; }
        .vault-edit-form .vault-toggle { padding-bottom: 0; }
    }
    @keyframes vault-spin {
        to { transform: rotate(360deg); }
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var csrf = document.querySelector('.vault-upload-form input[name="_token"]').value;
        var isArabic = @json($isAr);
        var uploadToken = null;
        var modal = document.getElementById('vault-ocr-modal');
        var uploadForm = document.querySelector('.vault-upload-form');
        var confirmForm = document.querySelector('.vault-confirm-form');
        var uploadError = document.querySelector('.vault-upload-error');
        var confirmError = document.querySelector('.vault-confirm-error');
        var confirmButton = document.querySelector('.vault-confirm-button');

        function showError(element, message) {
            if (!element) return;
            element.textContent = message || 'Something went wrong. Please try again.';
            element.hidden = false;
        }

        function hideError(element) {
            if (!element) return;
            element.textContent = '';
            element.hidden = true;
        }

        function setUploadProcessing(isProcessing) {
            var button = uploadForm ? uploadForm.querySelector('.vault-upload-button') : null;
            if (!uploadForm || !button) return;
            uploadForm.classList.toggle('is-processing', isProcessing);
            button.disabled = isProcessing;
        }

        function openModal(payload) {
            uploadToken = payload.token;
            document.getElementById('vault-upload-token').value = payload.token;
            document.getElementById('vault-confirm-file-name').textContent = payload.file_name || (isArabic ? 'مستند' : 'Document');
            document.getElementById('vault-confirm-ocr-status').textContent = payload.ocr_status ? ((isArabic ? 'التعرّف الضوئي: ' : 'OCR: ') + payload.ocr_status.replace('_', ' ')) : (isArabic ? 'اكتمل التعرّف الضوئي' : 'OCR complete');
            document.getElementById('vault-confirm-expiry').value = payload.expiry_date || '';
            document.getElementById('vault-confirm-reminder').value = payload.expiry_reminder_at || '';
            document.getElementById('vault-ocr-title').textContent = payload.expiry_date ? (isArabic ? 'اكتشف مساعد كويك تاريخ انتهاء' : 'Quick AI Found an Expiry Date') : (isArabic ? 'تعيين تذكير يدوي' : 'Set a Manual Reminder');
            document.getElementById('vault-ocr-message').textContent = payload.message || (isArabic ? 'راجع البيانات قبل حفظ المستند.' : 'Review before saving this document.');
            hideError(confirmError);
            modal.hidden = false;
        }

        function closeModal() {
            modal.hidden = true;
            confirmForm.classList.remove('is-saving');
            if (confirmButton) confirmButton.disabled = false;
        }

        document.querySelectorAll('.vault-upload-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                hideError(uploadError);
                setUploadProcessing(true);

                fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: new FormData(form)
                }).then(function (response) {
                    return response.json().then(function (data) {
                        if (!response.ok || !data.status) {
                            throw new Error(data.message || Object.values(data.errors || {})[0] || 'The file could not be analyzed.');
                        }
                        return data;
                    });
                }).then(function (data) {
                    openModal(data);
                }).catch(function (error) {
                    showError(uploadError, error.message);
                }).finally(function () {
                    setUploadProcessing(false);
                });
            });
        });

        if (confirmForm) {
            confirmForm.addEventListener('submit', function (event) {
                event.preventDefault();
                hideError(confirmError);
                confirmForm.classList.add('is-saving');
                if (confirmButton) confirmButton.disabled = true;

                fetch(confirmForm.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: new FormData(confirmForm)
                }).then(function (response) {
                    return response.json().then(function (data) {
                        if (!response.ok || !data.status) {
                            throw new Error(data.message || Object.values(data.errors || {})[0] || (isArabic ? 'تعذر حفظ المستند.' : 'The document could not be saved.'));
                        }
                        return data;
                    });
                }).then(function (data) {
                    window.location.href = data.redirect_url || '{{ route('customer-portal.vault') }}';
                }).catch(function (error) {
                    showError(confirmError, error.message);
                    confirmForm.classList.remove('is-saving');
                    if (confirmButton) confirmButton.disabled = false;
                });
            });
        }

        document.querySelectorAll('[data-vault-cancel]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (uploadToken) {
                    fetch('{{ route('customer-portal.vault.cancel-upload') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ upload_token: uploadToken })
                    });
                }
                uploadToken = null;
                closeModal();
            });
        });
    });
</script>
</x-master-layout>
