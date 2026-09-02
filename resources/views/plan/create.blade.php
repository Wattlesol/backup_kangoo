@php
    $locale = app()->getLocale();
    $isAr = in_array($locale, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']) || session('dir') === 'rtl';
    $pageTitle = $pageTitle ?? ($isAr ? 'إضافة / تعديل خطة اشتراك' : 'Create Subscription Plan');
@endphp

<x-master-layout>
    <div class="quick-plan-create-page" dir="{{ $isAr ? 'rtl' : 'ltr' }}">

        <!-- 1. Hero Banner Card -->
        <div class="quick-admin-hero">
            <div class="quick-admin-hero-content">
                <div class="quick-admin-hero-eyebrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><rect width="18" height="18" x="3" y="3" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                    <span>{{ $isAr ? 'خطط وباقات اشتراك الشركاء' : 'Partner Subscription Configuration' }}</span>
                </div>
                <h1>{{ $pageTitle }}</h1>
                <p>{{ $isAr ? 'إنشاء باقات اشتراك دورية (أسبوعية، شهرية، سنوية). يختار الشركاء بحرية الخدمات التي يقدمونها، ويطابق نظام الإسناد الطلبات مع الشركاء المصرحين.' : 'Configure recurring subscription plans (Weekly, Monthly, Yearly). Partners choose which services they offer, and the assignment engine respects their selections.' }}</p>
            </div>

            <div class="quick-admin-hero-actions">
                <a href="{{ route('plans.index') }}" class="quick-admin-hero-btn quick-admin-hero-btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    <span>{{ __('messages.back') }}</span>
                </a>
            </div>
        </div>

        <!-- 2. Form Card -->
        <div class="quick-card">
            <div class="quick-card-header mb-3">
                <div>
                    <h3 class="quick-card-title">{{ $isAr ? 'بيانات الخطة والفوترة' : 'Plan Details & Billing Cycle' }}</h3>
                    <div class="quick-card-sub">{{ $isAr ? 'حدد مسمى الخطة، دورة التجديد (أسبوعي/شهري/سنوي)، والرسوم المقررة' : 'Set up plan name, billing interval (Weekly/Monthly/Yearly), duration, and pricing' }}</div>
                </div>
            </div>

            {{ Form::model($plan, ['method' => 'POST', 'route' => 'plans.store', 'enctype' => 'multipart/form-data', 'data-toggle' => 'validator', 'id' => 'plan']) }}
                {{ Form::hidden('id') }}
                {{ Form::hidden('plan_type', 'unlimited') }}

                <div class="row">
                    <!-- Title -->
                    <div class="form-group col-md-6 mb-3">
                        <label for="title" class="quick-form-label">{{ trans('messages.title') }} <span class="text-danger">*</span></label>
                        {{ Form::text('title', old('title'), ['placeholder' => $isAr ? 'مثال: باقة الشريك المميز' : 'e.g. Premium Partner Plan', 'class' => 'quick-form-input', 'required']) }}
                        <small class="help-block with-errors text-danger"></small>
                    </div>

                    <!-- Billing Cycle Type (Weekly / Monthly / Yearly) -->
                    <div class="form-group col-md-6 mb-3">
                        <label for="type" class="quick-form-label">{{ $isAr ? 'دورة الاشتراك (النوع)' : 'Billing Cycle (Type)' }} <span class="text-danger">*</span></label>
                        {{ Form::select('type', [
                            'weekly'  => $isAr ? 'أسبوعي (Weekly)' : 'Weekly',
                            'monthly' => $isAr ? 'شهري (Monthly)' : 'Monthly',
                            'yearly'  => $isAr ? 'سنوي (Yearly)' : 'Yearly'
                        ], old('type', $plan->type ?? 'monthly'), ['id' => 'type', 'class' => 'quick-form-select', 'required']) }}
                    </div>

                    <!-- Duration Multiplier -->
                    <div class="form-group col-md-4 mb-3">
                        <label for="duration" class="quick-form-label">{{ trans('messages.duration') }} <span class="text-danger">*</span></label>
                        {{ Form::select('duration', ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6', '7' => '7', '8' => '8', '9' => '9', '10' => '10', '11' => '11', '12' => '12'], old('duration', $plan->duration ?? '1'), ['id' => 'duration', 'class' => 'quick-form-select', 'required']) }}
                        <small class="text-muted d-block mt-1" style="font-size: 11px;">
                            {{ $isAr ? 'مضاعف الدورة (مثال: 1 أسبوع، 1 شهر، 1 سنة)' : 'Multiplier for cycle interval (e.g. 1 week, 1 month, 1 year)' }}
                        </small>
                    </div>

                    <!-- Amount -->
                    <div class="form-group col-md-4 mb-3">
                        <label for="amount" class="quick-form-label">{{ __('messages.amount') }} <span class="text-danger">*</span></label>
                        {{ Form::number('amount', old('amount'), ['placeholder' => '0.00', 'class' => 'quick-form-input', 'required', 'step' => 'any', 'min' => 0]) }}
                    </div>

                    <!-- Status -->
                    <div class="form-group col-md-4 mb-3">
                        <label for="status" class="quick-form-label">{{ trans('messages.status') }} <span class="text-danger">*</span></label>
                        {{ Form::select('status', ['1' => __('messages.active'), '0' => __('messages.inactive')], old('status', $plan->status ?? '1'), ['id' => 'status', 'class' => 'quick-form-select', 'required']) }}
                    </div>
                </div>

                <!-- Info Callout: Services Selection & Assignment Policy -->
                <div class="quick-info-callout mb-4">
                    <div class="d-flex align-items-start gap-3">
                        <div class="quick-callout-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                        </div>
                        <div>
                            <strong style="font-size: 13px; color: var(--quick-shell-ink);">
                                {{ $isAr ? 'سياسة اختيار الخدمات وإسناد المعاملات' : 'Service Selection & Assignment Policy' }}
                            </strong>
                            <p class="mb-0 mt-1" style="font-size: 12px; color: var(--quick-shell-muted); line-height: 1.6;">
                                {{ $isAr 
                                    ? 'تحدد خطة الاشتراك دورة الفوترة والتجديد فقط (أسبوعية، شهرية، أو سنوية). يمتلك كل شريك الحرية الكاملة في تفعيل واختيار الخدمات الحكومية التي يرغب بتقديمها من لوحة تحكمه، ويلتزم محرك الإسناد الذكي بتوجيه المعاملات إلى الشركاء الذين اختاروا تقديم تلك الخدمة فقط.' 
                                    : 'Subscription plans govern billing intervals and renewal pricing only (Weekly, Monthly, or Yearly). Subscribed partners freely select and manage the government services they offer from their portal, and the automated assignment engine strictly routes service requests to partners who have authorized and enabled that specific service.' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="row">
                    <div class="form-group col-md-12 mb-4">
                        <label for="description" class="quick-form-label">{{ __('messages.description') }}</label>
                        {{ Form::textarea('description', null, ['class' => 'quick-form-textarea', 'rows' => 3, 'placeholder' => $isAr ? 'وصف تفاصيل ومزايا الخطة...' : 'Optional details or perks of this subscription plan...']) }}
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('plans.index') }}" class="quick-filter-btn quick-filter-btn-secondary">
                        {{ $isAr ? 'إلغاء' : 'Cancel' }}
                    </a>
                    <button type="submit" class="quick-filter-btn quick-filter-btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>{{ trans('messages.save') }}</span>
                    </button>
                </div>
            {{ Form::close() }}
        </div>
    </div>

    @once
    <style>
        .quick-plan-create-page {
            width: 100%;
        }

        .quick-info-callout {
            padding: 16px 20px;
            border-radius: 14px;
            background: color-mix(in srgb, var(--quick-blue) 6%, var(--quick-shell-surface));
            border: 1px solid color-mix(in srgb, var(--quick-blue) 22%, transparent);
        }

        .quick-callout-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(31,107,255,.12);
            color: var(--quick-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
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
</x-master-layout>

