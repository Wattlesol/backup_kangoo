@php
    $locale = app()->getLocale();
    $isAr = in_array($locale, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']) || session('dir') === 'rtl';
    $isEdit = !empty($subcategory->id);
    $pageTitle = $isEdit 
        ? ($isAr ? 'تعديل التصنيف الفرعي: ' . ($subcategory->name_ar ?: $subcategory->name_en ?: $subcategory->name) : 'Edit Subcategory: ' . ($subcategory->name_en ?: $subcategory->name))
        : ($isAr ? 'إضافة تصنيف فرعي جديد' : 'Add New Government Subcategory');

    $catName = optional($subcategory->category)->name;
    if ($isAr && !empty(optional($subcategory->category)->name_ar)) {
        $catName = optional($subcategory->category)->name_ar;
    } elseif (!empty(optional($subcategory->category)->name_en)) {
        $catName = optional($subcategory->category)->name_en;
    }
@endphp

<x-master-layout>
    <div class="quick-subcategory-create-page" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
        <!-- 1. Header Banner -->
        <div class="quick-admin-hero">
            <div class="quick-admin-hero-content">
                <div class="quick-admin-hero-eyebrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><rect x="16" y="16" width="6" height="6" rx="1"/><rect x="2" y="16" width="6" height="6" rx="1"/><rect x="9" y="2" width="6" height="6" rx="1"/><path d="M5 16v-3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3"/><path d="M12 12V8"/></svg>
                    <span>{{ $isAr ? 'إدارة وتفرع الخدمات' : 'Subcategory Taxonomy' }}</span>
                </div>
                <h1>{{ $pageTitle }}</h1>
                <p>{{ $isAr ? 'تحديد اسم التصنيف الفرعي وربطه بالقطاع الرئيسي وضبط الصورة وحالة التمييز.' : 'Configure subcategory details, attach to parent sector taxonomy, upload imagery, and manage visibility.' }}</p>
            </div>

            <div class="quick-admin-hero-actions">
                <a href="{{ route('subcategory.index') }}" class="quick-admin-hero-btn quick-admin-hero-btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><polyline points="{{ $isAr ? '9 18 15 12 9 6' : '15 18 9 12 15 6' }}"/></svg>
                    <span>{{ $isAr ? 'العودة للتصنيفات الفرعية' : 'Back to Subcategories' }}</span>
                </a>
            </div>
        </div>

        <!-- 2. Form Card -->
        <div class="quick-card">
            {{ Form::model($subcategory, ['method' => 'POST', 'route' => 'subcategory.store', 'enctype' => 'multipart/form-data', 'data-toggle' => 'validator', 'id' => 'subcategory-form']) }}
            {{ Form::hidden('id') }}

            <div class="quick-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px;">
                <!-- English Name -->
                <div class="quick-form-group">
                    <label class="quick-form-label">{{ __('messages.english_name') }} <span style="color: #ef4444;">*</span></label>
                    {{ Form::text('name_en', old('name_en', $subcategory->name_en ?: $subcategory->name), ['placeholder' => 'e.g. Vehicle Registration Renewal', 'class' => 'form-control quick-input', 'required']) }}
                    <small class="help-block with-errors text-danger"></small>
                </div>

                <!-- Arabic Name -->
                <div class="quick-form-group">
                    <label class="quick-form-label">{{ __('messages.arabic_name') }} <span style="color: #ef4444;">*</span></label>
                    {{ Form::text('name_ar', old('name_ar', $subcategory->name_ar), ['placeholder' => 'مثال: تجديد رخص المركبات', 'class' => 'form-control quick-input', 'required', 'dir' => 'rtl']) }}
                    <small class="help-block with-errors text-danger"></small>
                </div>

                <!-- Parent Category -->
                <div class="quick-form-group">
                    <label class="quick-form-label">{{ __('messages.category') }} <span style="color: #ef4444;">*</span></label>
                    {{ Form::select('category_id', [optional($subcategory->category)->id => $catName], optional($subcategory->category)->id, [
                        'class' => 'select2js form-control quick-input category',
                        'required',
                        'data-placeholder' => __('messages.select_name',[ 'select' => __('messages.category') ]),
                        'data-ajax--url' => route('ajax-list', ['type' => 'category']),
                    ]) }}
                    <small class="help-block with-errors text-danger"></small>
                </div>

                <!-- Status -->
                <div class="quick-form-group">
                    <label class="quick-form-label">{{ trans('messages.status') }} <span style="color: #ef4444;">*</span></label>
                    {{ Form::select('status', ['1' => __('messages.active'), '0' => __('messages.inactive')], old('status', $subcategory->status ?? 1), ['class' => 'form-control quick-input', 'required']) }}
                </div>

                <!-- Image Upload -->
                <div class="quick-form-group" style="grid-column: 1 / -1;">
                    <label class="quick-form-label">{{ __('messages.image') }} @if(!$isEdit)<span style="color: #ef4444;">*</span>@endif</label>
                    <div class="quick-file-upload-box">
                        <input type="file" name="subcategory_image" id="subcategory_image_input" class="quick-file-input" onchange="previewSubCategoryImage(event)" accept="image/*" {{ !$isEdit ? 'required' : '' }}>
                        <label for="subcategory_image_input" class="quick-file-label">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;color:var(--quick-blue);"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/><path d="m14 14 1.586-1.586a2 2 0 0 1 2.828 0L21 15"/></svg>
                            <span id="subcategory_image_text">{{ $subcategory && getMediaFileExit($subcategory, 'subcategory_image') ? $subcategory->getFirstMedia('subcategory_image')->file_name : ($isAr ? 'اختر صورة التصنيف الفرعي' : 'Choose subcategory image') }}</span>
                        </label>
                    </div>

                    @if(getMediaFileExit($subcategory, 'subcategory_image'))
                        <div class="quick-preview-chip mt-2" id="subcategory_existing_preview">
                            <img src="{{ getSingleMedia($subcategory, 'subcategory_image') }}" alt="image" style="width:70px;height:46px;object-fit:cover;border-radius:8px;">
                            <a class="text-danger remove-file" href="{{ route('remove.file', ['id' => $subcategory->id, 'type' => 'subcategory_image']) }}" data--submit="confirm_form" data--confirmation="true" data--ajax="true" title="{{ __('messages.remove_file_title', ['name' => __('messages.image')]) }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                            </a>
                        </div>
                    @else
                        <div class="mt-2 d-none" id="subcategory_preview_container">
                            <img id="subcategory_image_preview" src="" style="width:70px;height:46px;object-fit:cover;border-radius:8px;">
                        </div>
                    @endif
                </div>

                <!-- Description -->
                <div class="quick-form-group" style="grid-column: 1 / -1;">
                    <label class="quick-form-label">{{ trans('messages.description') }}</label>
                    {{ Form::textarea('description', null, ['class' => 'form-control quick-input', 'rows' => 3, 'placeholder' => __('messages.description')]) }}
                </div>
            </div>

            <!-- Featured Toggle -->
            <div style="padding: 16px 20px; border-radius: 14px; background: color-mix(in srgb, var(--quick-shell-bg) 60%, var(--quick-shell-surface)); border: 1px solid var(--quick-shell-line); margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <strong style="display: block; font-size: 13px; color: var(--quick-shell-ink);">{{ __('messages.set_as_featured') }}</strong>
                    <span style="font-size: 11px; color: var(--quick-shell-muted);">{{ $isAr ? 'إبراز هذا التصنيف الفرعي في الصفحة الرئيسية وتطبيق المستفيدين' : 'Highlight this subcategory in search and portal home' }}</span>
                </div>
                <div class="custom-control custom-switch">
                    {{ Form::checkbox('is_featured', 1, $subcategory->is_featured ? true : false, ['class' => 'custom-control-input', 'id' => 'is_featured']) }}
                    <label class="custom-control-label" for="is_featured"></label>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--quick-shell-line); padding-top: 20px;">
                <a href="{{ route('subcategory.index') }}" class="quick-admin-hero-btn quick-admin-hero-btn-secondary" style="min-height: 44px;">
                    {{ __('messages.cancel') }}
                </a>
                <button type="submit" class="quick-admin-hero-btn quick-admin-hero-btn-primary" style="min-height: 44px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>{{ trans('messages.save') }}</span>
                </button>
            </div>
            {{ Form::close() }}
        </div>
    </div>

    @once
    <style>
        .quick-form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .quick-form-label {
            font-size: 12px;
            font-weight: 800;
            color: var(--quick-shell-ink);
            margin: 0;
        }

        .quick-input {
            min-height: 44px !important;
            border-radius: 11px !important;
            border: 1px solid var(--quick-shell-line) !important;
            background: var(--quick-shell-surface) !important;
            color: var(--quick-shell-ink) !important;
            font-size: 13px !important;
            padding: 10px 14px !important;
        }

        .quick-input:focus {
            border-color: var(--quick-blue) !important;
            box-shadow: 0 0 0 3px rgba(31,107,255,.15) !important;
        }

        .quick-file-upload-box {
            position: relative;
            width: 100%;
        }

        .quick-file-input {
            position: absolute;
            inset: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
            z-index: 2;
        }

        .quick-file-label {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 44px;
            padding: 10px 14px;
            border-radius: 11px;
            border: 1px dashed var(--quick-shell-line);
            background: var(--quick-shell-surface);
            color: var(--quick-shell-muted);
            font-size: 12px;
            font-weight: 700;
            margin: 0;
            cursor: pointer;
            transition: all .15s ease;
        }

        .quick-file-upload-box:hover .quick-file-label {
            border-color: var(--quick-blue);
            color: var(--quick-blue);
        }

        .quick-preview-chip {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 6px 10px;
            border-radius: 10px;
            border: 1px solid var(--quick-shell-line);
            background: color-mix(in srgb, var(--quick-shell-bg) 50%, var(--quick-shell-surface));
        }
    </style>
    @endonce

    <script>
        function previewSubCategoryImage(event) {
            if (event.target.files && event.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('subcategory_image_preview');
                    if (preview) {
                        preview.src = e.target.result;
                        const container = document.getElementById('subcategory_preview_container');
                        if (container) container.classList.remove('d-none');
                    }
                    const text = document.getElementById('subcategory_image_text');
                    if (text) text.textContent = event.target.files[0].name;
                };
                reader.readAsDataURL(event.target.files[0]);
            }
        }
    </script>
</x-master-layout>
