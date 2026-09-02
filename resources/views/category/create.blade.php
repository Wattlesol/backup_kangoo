@php
    $locale = app()->getLocale();
    $isAr = in_array($locale, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']) || session('dir') === 'rtl';
    $isEdit = !empty($categorydata->id);
    $pageTitle = $isEdit 
        ? ($isAr ? 'تعديل التصنيف: ' . ($categorydata->name_ar ?: $categorydata->name_en ?: $categorydata->name) : 'Edit Category: ' . ($categorydata->name_en ?: $categorydata->name))
        : ($isAr ? 'إضافة تصنيف حكومي جديد' : 'Add New Government Category');
@endphp

<x-master-layout>
    <div class="quick-category-create-page" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
        <!-- 1. Header Banner -->
        <div class="quick-admin-hero">
            <div class="quick-admin-hero-content">
                <div class="quick-admin-hero-eyebrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/></svg>
                    <span>{{ $isAr ? 'إدارة القطاعات والتصنيفات' : 'Category Management' }}</span>
                </div>
                <h1>{{ $pageTitle }}</h1>
                <p>{{ $isAr ? 'تحديد اسم التصنيف باللغتين العربية والإنجليزية، وتعيين الأيقونة والصورة التعريفية وترتيب الظهور.' : 'Set English and Arabic category titles, upload icon and cover imagery, and configure display priorities.' }}</p>
            </div>

            <div class="quick-admin-hero-actions">
                <a href="{{ route('category.index') }}" class="quick-admin-hero-btn quick-admin-hero-btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><polyline points="{{ $isAr ? '9 18 15 12 9 6' : '15 18 9 12 15 6' }}"/></svg>
                    <span>{{ $isAr ? 'العودة لقائمة التصنيفات' : 'Back to Categories' }}</span>
                </a>
            </div>
        </div>

        <!-- 2. Form Card -->
        <div class="quick-card">
            {{ Form::model($categorydata, ['method' => 'POST', 'route' => 'category.store', 'enctype' => 'multipart/form-data', 'data-toggle' => 'validator', 'id' => 'category-form']) }}
            {{ Form::hidden('id') }}

            <div class="quick-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px;">
                <!-- English Name -->
                <div class="quick-form-group">
                    <label class="quick-form-label">{{ __('messages.english_name') }} <span style="color: #ef4444;">*</span></label>
                    {{ Form::text('name_en', old('name_en', $categorydata->name_en ?: $categorydata->name), ['placeholder' => 'e.g. Commercial & Business Services', 'class' => 'form-control quick-input', 'required']) }}
                    <small class="help-block with-errors text-danger"></small>
                </div>

                <!-- Arabic Name -->
                <div class="quick-form-group">
                    <label class="quick-form-label">{{ __('messages.arabic_name') }} <span style="color: #ef4444;">*</span></label>
                    {{ Form::text('name_ar', old('name_ar'), ['placeholder' => 'مثال: خدمات التجارة والأعمال', 'class' => 'form-control quick-input', 'required', 'dir' => 'rtl']) }}
                    <small class="help-block with-errors text-danger"></small>
                </div>

                <!-- Display Order -->
                <div class="quick-form-group">
                    <label class="quick-form-label">{{ __('messages.display_order') }}</label>
                    {{ Form::number('display_order', old('display_order', $categorydata->display_order ?? 0), ['placeholder' => '0', 'class' => 'form-control quick-input', 'min' => 0]) }}
                </div>

                <!-- Status -->
                <div class="quick-form-group">
                    <label class="quick-form-label">{{ trans('messages.status') }} <span style="color: #ef4444;">*</span></label>
                    {{ Form::select('status', ['1' => __('messages.active'), '0' => __('messages.inactive')], old('status', $categorydata->status ?? 1), ['class' => 'form-control quick-input', 'required']) }}
                </div>

                <!-- Icon Upload -->
                <div class="quick-form-group">
                    <label class="quick-form-label">{{ __('messages.icon') }}</label>
                    <div class="quick-file-upload-box">
                        <input type="file" name="category_icon" id="category_icon_input" class="quick-file-input" onchange="previewCategoryIcon(event)" accept="image/*">
                        <label for="category_icon_input" class="quick-file-label">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;color:var(--quick-blue);"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            <span id="category_icon_text">{{ $categorydata && getMediaFileExit($categorydata, 'category_icon') ? $categorydata->getFirstMedia('category_icon')->file_name : ($isAr ? 'اختر أيقونة التصنيف' : 'Choose category icon') }}</span>
                        </label>
                    </div>
                    
                    @if(getMediaFileExit($categorydata, 'category_icon'))
                        <div class="quick-preview-chip mt-2" id="icon_existing_preview">
                            <img src="{{ getSingleMedia($categorydata, 'category_icon') }}" alt="icon" style="width:40px;height:40px;object-fit:contain;border-radius:8px;">
                            <a class="text-danger remove-file" href="{{ route('remove.file', ['id' => $categorydata->id, 'type' => 'category_icon']) }}" data--submit="confirm_form" data--confirmation="true" data--ajax="true" title="{{ __('messages.remove_file_title', ['name' => 'Icon']) }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                            </a>
                        </div>
                    @else
                        <div class="mt-2 d-none" id="icon_preview_container">
                            <img id="category_icon_preview" src="" style="width:40px;height:40px;object-fit:contain;border-radius:8px;">
                        </div>
                    @endif
                </div>

                <!-- Image / Cover Upload -->
                <div class="quick-form-group">
                    <label class="quick-form-label">{{ __('messages.image') }} @if(!$isEdit)<span style="color: #ef4444;">*</span>@endif</label>
                    <div class="quick-file-upload-box">
                        <input type="file" name="category_image" id="category_image_input" class="quick-file-input" onchange="previewCategoryImage(event)" accept="image/*" {{ !$isEdit ? 'required' : '' }}>
                        <label for="category_image_input" class="quick-file-label">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;color:var(--quick-blue);"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/><path d="m14 14 1.586-1.586a2 2 0 0 1 2.828 0L21 15"/></svg>
                            <span id="category_image_text">{{ $categorydata && getMediaFileExit($categorydata, 'category_image') ? $categorydata->getFirstMedia('category_image')->file_name : ($isAr ? 'اختر صورة الغلاف' : 'Choose cover image') }}</span>
                        </label>
                    </div>

                    @if(getMediaFileExit($categorydata, 'category_image'))
                        <div class="quick-preview-chip mt-2" id="image_existing_preview">
                            <img src="{{ getSingleMedia($categorydata, 'category_image') }}" alt="image" style="width:70px;height:46px;object-fit:cover;border-radius:8px;">
                            <a class="text-danger remove-file" href="{{ route('remove.file', ['id' => $categorydata->id, 'type' => 'category_image']) }}" data--submit="confirm_form" data--confirmation="true" data--ajax="true" title="{{ __('messages.remove_file_title', ['name' => __('messages.image')]) }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                            </a>
                        </div>
                    @else
                        <div class="mt-2 d-none" id="image_preview_container">
                            <img id="category_image_preview" src="" style="width:70px;height:46px;object-fit:cover;border-radius:8px;">
                        </div>
                    @endif
                </div>
            </div>

            <!-- Featured Toggle -->
            <div style="padding: 16px 20px; border-radius: 14px; background: color-mix(in srgb, var(--quick-shell-bg) 60%, var(--quick-shell-surface)); border: 1px solid var(--quick-shell-line); margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <strong style="display: block; font-size: 13px; color: var(--quick-shell-ink);">{{ __('messages.set_as_featured') }}</strong>
                    <span style="font-size: 11px; color: var(--quick-shell-muted);">{{ $isAr ? 'إبراز التصنيف في الصفحة الرئيسية وتطبيق الجوال للمستفيدين' : 'Highlight this category on homepage and customer apps' }}</span>
                </div>
                <div class="custom-control custom-switch">
                    {{ Form::checkbox('is_featured', 1, $categorydata->is_featured ? true : false, ['class' => 'custom-control-input', 'id' => 'is_featured']) }}
                    <label class="custom-control-label" for="is_featured"></label>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--quick-shell-line); padding-top: 20px;">
                <a href="{{ route('category.index') }}" class="quick-admin-hero-btn quick-admin-hero-btn-secondary" style="min-height: 44px;">
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
        function previewCategoryImage(event) {
            if (event.target.files && event.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('category_image_preview');
                    if (preview) {
                        preview.src = e.target.result;
                        const container = document.getElementById('image_preview_container');
                        if (container) container.classList.remove('d-none');
                    }
                    const text = document.getElementById('category_image_text');
                    if (text) text.textContent = event.target.files[0].name;
                };
                reader.readAsDataURL(event.target.files[0]);
            }
        }

        function previewCategoryIcon(event) {
            if (event.target.files && event.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('category_icon_preview');
                    if (preview) {
                        preview.src = e.target.result;
                        const container = document.getElementById('icon_preview_container');
                        if (container) container.classList.remove('d-none');
                    }
                    const text = document.getElementById('category_icon_text');
                    if (text) text.textContent = event.target.files[0].name;
                };
                reader.readAsDataURL(event.target.files[0]);
            }
        }
    </script>
</x-master-layout>
