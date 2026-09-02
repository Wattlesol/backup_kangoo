@php
    $locale = app()->getLocale();
    $isAr = in_array($locale, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']) || session('dir') === 'rtl';
    $pageTitle = $pageTitle ?? ($isAr ? 'إعدادات الصورة البارزة لتطبيق الجوال' : 'Mobile App Hero Settings');
    $isAdmin = $auth_user->hasAnyRole(['admin', 'demo_admin']);
@endphp

<x-master-layout>
    <div class="quick-setting-page" dir="{{ $isAr ? 'rtl' : 'ltr' }}">

        @if($isAdmin)
            <!-- 1. Modern Admin Hero Header -->
            <div class="quick-admin-hero">
                <div class="quick-admin-hero-content">
                    <div class="quick-admin-hero-eyebrow">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><rect width="18" height="18" x="3" y="3" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                        <span>{{ $isAr ? 'واجهة تطبيق الجوال للعملاء' : 'Customer Mobile App Experience' }}</span>
                    </div>
                    <h1>{{ $isAr ? 'الصورة البارزة والبانر الرئيسي لتطبيق الجوال' : 'Mobile App Hero Banner Settings' }}</h1>
                    <p>{{ $isAr ? 'تغيير وتحديث الصورة البارزة، العنوان الرئيسي، والخدمة المرتبطة التي تظهر في أعلى الشاشة الرئيسية لتطبيق الجوال.' : 'Update the primary hero visual, promotional headline, and linked service displayed prominently at the top of the mobile app home screen.' }}</p>
                </div>
            </div>

            <!-- 2. Hero Image Setting Card -->
            <div class="quick-card">
                <div class="quick-card-header mb-4">
                    <div>
                        <h3 class="quick-card-title">{{ $isAr ? 'تخصيص الصورة البارزة للواجهة' : 'Mobile Hero Visual & Content' }}</h3>
                        <div class="quick-card-sub">{{ $isAr ? 'ارفع صورة عالية الدقة واضبط العنوان التسويقي للظهور الفوري في التطبيق' : 'Upload high-resolution artwork and set your active mobile promotional banner' }}</div>
                    </div>
                </div>

                {{ Form::model($heroSlider, ['method' => 'POST', 'route' => 'setting.mobile-hero.save', 'enctype' => 'multipart/form-data', 'id' => 'mobileHeroForm']) }}
                    {{ Form::hidden('id', $heroSlider->id ?? null) }}

                    <div class="row">
                        <!-- Left Column: Hero Image Upload & Live Preview -->
                        <div class="col-lg-5 mb-4">
                            <label class="quick-form-label mb-2">
                                {{ $isAr ? 'صورة البانر البارزة (Hero Image)' : 'Hero Banner Image' }} <span class="text-danger">*</span>
                            </label>

                            <div class="quick-hero-upload-card text-center" id="heroDropZone">
                                <div class="quick-hero-preview-wrapper mb-3">
                                    @php
                                        $currentImage = !empty($heroSlider) ? getSingleMedia($heroSlider, 'slider_image', null) : null;
                                    @endphp
                                    <img id="hero_preview_img" 
                                         src="{{ $currentImage ?: asset('images/logo.png') }}" 
                                         alt="Mobile Hero Preview" 
                                         class="quick-hero-preview-img {{ $currentImage ? '' : 'placeholder-mode' }}">
                                </div>

                                <div class="quick-upload-action-box">
                                    <label for="hero_image_input" class="quick-filter-btn quick-filter-btn-secondary" style="cursor:pointer; width:100%;">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                        <span>{{ $isAr ? 'اختر صورة جديدة' : 'Choose New Hero Image' }}</span>
                                    </label>
                                    <input type="file" name="hero_image" id="hero_image_input" class="d-none" accept="image/*" onchange="previewHeroImage(this)">
                                    <small class="text-muted d-block mt-2" style="font-size: 11px;">
                                        {{ $isAr ? 'الصيغ المعتمدة: JPG, PNG, WEBP (الحجم الموصى به: 1200 × 600 بكسل)' : 'Supported: JPG, PNG, WEBP (Recommended: 1200 x 600 px)' }}
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Content & Metadata Fields -->
                        <div class="col-lg-7 mb-4">
                            <div class="row">
                                <!-- Banner Title -->
                                <div class="form-group col-md-12 mb-3">
                                    <label for="title" class="quick-form-label">{{ $isAr ? 'عنوان البانر الرئيسي' : 'Hero Headline / Title' }} <span class="text-danger">*</span></label>
                                    {{ Form::text('title', old('title', $heroSlider->title ?? ($isAr ? 'إنجاز المعاملات الحكومية بكل سهولة وسرعة' : 'Government Services Simplified')), [
                                        'placeholder' => $isAr ? 'أدخل عنوان البانر...' : 'Enter banner headline...',
                                        'class' => 'quick-form-input',
                                        'required'
                                    ]) }}
                                </div>

                                <!-- Linked Service -->
                                <div class="form-group col-md-8 mb-3">
                                    <label for="type_id" class="quick-form-label">{{ $isAr ? 'الخدمة المرتبطة بالبانر' : 'Linked Service (Action on Tap)' }}</label>
                                    {{ Form::select('type_id', $services, old('type_id', $heroSlider->type_id ?? null), [
                                        'class' => 'quick-form-select',
                                        'placeholder' => $isAr ? 'اختر خدمة مرتبطة...' : 'Select a featured service...'
                                    ]) }}
                                    <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                        {{ $isAr ? 'عند ضغط العميل على البانر في التطبيق، سيتم نقله مباشرة إلى هذه الخدمة.' : 'Tapping the banner in the mobile app directly opens this service.' }}
                                    </small>
                                </div>

                                <!-- Status Toggle -->
                                <div class="form-group col-md-4 mb-3">
                                    <label for="status" class="quick-form-label">{{ trans('messages.status') }} <span class="text-danger">*</span></label>
                                    {{ Form::select('status', ['1' => __('messages.active'), '0' => __('messages.inactive')], old('status', $heroSlider->status ?? '1'), ['id' => 'status', 'class' => 'quick-form-select', 'required']) }}
                                </div>

                                <!-- Description / Subtitle -->
                                <div class="form-group col-md-12 mb-3">
                                    <label for="description" class="quick-form-label">{{ $isAr ? 'الوصف الفرعي / النص الترويجي' : 'Promotional Subtitle / Description' }}</label>
                                    {{ Form::textarea('description', old('description', $heroSlider->description ?? null), [
                                        'class' => 'quick-form-textarea',
                                        'rows' => 3,
                                        'placeholder' => $isAr ? 'نص تعريفي موجز يظهر أسفل العنوان الرئيسي...' : 'Brief promotional message shown on the hero card...'
                                    ]) }}
                                </div>
                            </div>

                            <!-- Mobile App Preview Live Callout -->
                            <div class="quick-info-callout">
                                <div class="d-flex align-items-start gap-2">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;color:var(--quick-blue);flex-shrink:0;margin-top:2px;"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                                    <div>
                                        <strong style="font-size: 13px; color: var(--quick-shell-ink);">
                                            {{ $isAr ? 'تحديث فوري على تطبيق الجوال' : 'Instant Mobile App Synchronization' }}
                                        </strong>
                                        <p class="mb-0 mt-1" style="font-size: 12px; color: var(--quick-shell-muted); line-height: 1.5;">
                                            {{ $isAr ? 'بمجرد حفظ التغييرات، يتم توفير الصورة البارزة والعنوان مباشرة لواجهة تطبيق الجوال لجميع المستخدمين عبر واجهة برمجة التطبيقات (API).' : 'Changes take effect immediately across all customer mobile devices via the real-time dashboard API.' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Actions -->
                    <div class="d-flex justify-content-end gap-2 pt-3" style="border-top: 1px solid var(--quick-shell-line);">
                        <button type="submit" class="quick-filter-btn quick-filter-btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>{{ $isAr ? 'حفظ إعدادات الصورة البارزة' : 'Save Hero Banner' }}</span>
                        </button>
                    </div>
                {{ Form::close() }}
            </div>
        @else
            <!-- Non-admin fallback (Provider Profile / Documents tabs) -->
            <div class="row">
                <div class="col-lg-3">
                    <div class="quick-card">
                        <ul class="nav flex-column nav-pills" id="tabs-text">
                            <li class="nav-item mb-2">
                                <a href="javascript:void(0)" data-href="{{ route('layout_page') }}?page=profile_form" data-target=".paste_here" class="nav-link {{ $page == 'profile_form' ? 'active' : '' }}" data-toggle="tabajax"> {{ __('messages.profile')}} </a>
                            </li>
                            <li class="nav-item mb-2">
                                <a href="javascript:void(0)" data-href="{{ route('layout_page') }}?page=password_form" data-target=".paste_here" class="nav-link {{ $page == 'password_form' ? 'active' : '' }}" data-toggle="tabajax"> {{ __('messages.change_password') }} </a>
                            </li>
                            @role('provider')
                                <li class="nav-item mb-2">
                                    <a href="javascript:void(0)" data-href="{{ route('layout_page') }}?page=time_slot" data-target=".paste_here" class="nav-link {{ $page == 'time_slot' ? 'active' : '' }}" data-toggle="tabajax"> {{ __('messages.slot') }} </a>
                                </li>
                                <li class="nav-item mb-2">
                                    <a href="javascript:void(0)" data-href="{{ route('layout_page') }}?page=partner_documents" data-target=".paste_here" class="nav-link {{ $page == 'partner_documents' ? 'active' : '' }}" data-toggle="tabajax"> Documents </a>
                                </li>
                            @endrole
                        </ul>
                    </div>
                </div>
                <div class="col-lg-9">
                    <div class="quick-card">
                        <div class="paste_here"></div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @once
    <style>
        .quick-setting-page {
            width: 100%;
        }

        .quick-hero-upload-card {
            background: color-mix(in srgb, var(--quick-shell-bg) 60%, var(--quick-shell-surface));
            border: 2px dashed var(--quick-shell-line);
            border-radius: 16px;
            padding: 24px;
            transition: all .2s ease;
        }

        .quick-hero-upload-card:hover {
            border-color: var(--quick-blue);
            background: color-mix(in srgb, var(--quick-blue) 4%, var(--quick-shell-surface));
        }

        .quick-hero-preview-wrapper {
            width: 100%;
            height: 200px;
            border-radius: 12px;
            overflow: hidden;
            background: var(--quick-shell-surface);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--quick-shell-line);
            box-shadow: 0 4px 14px rgba(0,0,0,.06);
        }

        .quick-hero-preview-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .3s ease;
        }

        .quick-hero-preview-img.placeholder-mode {
            width: auto;
            height: 70px;
            object-fit: contain;
            opacity: .6;
        }

        .quick-form-label {
            font-size: 12px;
            font-weight: 800;
            color: var(--quick-shell-ink);
            margin-bottom: 6px;
            display: block;
        }

        .quick-form-input,
        .quick-form-select,
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
        .quick-form-select:focus,
        .quick-form-textarea:focus {
            border-color: var(--quick-blue);
            box-shadow: 0 0 0 3px rgba(31,107,255,.12);
        }

        .quick-info-callout {
            padding: 14px 18px;
            border-radius: 12px;
            background: color-mix(in srgb, var(--quick-blue) 6%, var(--quick-shell-surface));
            border: 1px solid color-mix(in srgb, var(--quick-blue) 20%, transparent);
        }

        .quick-filter-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            height: 42px;
            padding: 0 22px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
            transition: all .2s ease;
            white-space: nowrap;
            border: 1px solid transparent;
        }

        .quick-filter-btn-primary {
            background: var(--quick-blue);
            color: #ffffff;
            border-color: var(--quick-blue);
        }

        .quick-filter-btn-primary:hover {
            background: #1455d9;
            border-color: #1455d9;
            color: #ffffff;
        }

        .quick-filter-btn-secondary {
            background: var(--quick-shell-surface);
            border-color: var(--quick-shell-line);
            color: var(--quick-shell-ink);
        }

        .quick-filter-btn-secondary:hover {
            background: color-mix(in srgb, var(--quick-shell-bg) 70%, var(--quick-shell-surface));
            border-color: var(--quick-shell-muted);
            color: var(--quick-shell-ink);
        }
    </style>
    @endonce

    @section('bottom_script')
        <script>
            function previewHeroImage(input) {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        var preview = document.getElementById('hero_preview_img');
                        preview.src = e.target.result;
                        preview.classList.remove('placeholder-mode');
                    };
                    reader.readAsDataURL(input.files[0]);
                }
            }

            @if(!$isAdmin)
                $(document).ready(function() {
                    var $this = $('.nav-item').find('a.active');
                    var loadurl = '{{ route("layout_page") }}?page={{ $page }}';
                    var targ = $this.attr('data-target') || '.paste_here';
                    $.post(loadurl, { '_token': $('meta[name=csrf-token]').attr('content') }, function(data) {
                        $(targ).html(data);
                    });
                    $this.tab('show');
                });
            @endif
        </script>
    @endsection
</x-master-layout>

