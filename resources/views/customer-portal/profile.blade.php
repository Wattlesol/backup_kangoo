<x-master-layout>
@php $isAr = app()->getLocale() === 'ar'; @endphp
<div class="container-fluid sanad-page">
    <div class="sanad-header">
        <div><h1 class="sanad-title">{{ $isAr ? 'الملف الشخصي للعميل' : 'Customer Profile' }}</h1><div class="sanad-muted">{{ $isAr ? 'حدّث معلومات حسابك وتفضيلات اللغة وإعدادات الأمان.' : 'Manage your account details, language preference, and security.' }}</div></div>
        <span class="badge"><i class="fas fa-shield-alt"></i>{{ $isAr ? 'حساب عميل محمي' : 'Protected customer account' }}</span>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><strong>{{ $isAr ? 'يرجى تصحيح الأخطاء التالية:' : 'Please correct the following:' }}</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="row">
        <div class="col-xl-7 mb-3">
            <form class="sanad-card h-100" method="post" action="{{ route('customer-portal.profile.update') }}">
                @csrf
                <div class="sanad-card-header"><i class="far fa-user text-primary mr-2"></i>{{ $isAr ? 'المعلومات الشخصية' : 'Personal information' }}</div>
                <div class="sanad-card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label>{{ $isAr ? 'الاسم الأول' : 'First name' }}</label><input class="sanad-form-control" name="first_name" value="{{ old('first_name', $user->first_name) }}" required></div>
                        <div class="col-md-6 mb-3"><label>{{ $isAr ? 'اسم العائلة' : 'Last name' }}</label><input class="sanad-form-control" name="last_name" value="{{ old('last_name', $user->last_name) }}"></div>
                        <div class="col-md-6 mb-3"><label>{{ $isAr ? 'البريد الإلكتروني' : 'Email address' }}</label><input class="sanad-form-control" value="{{ $user->email }}" disabled><small>{{ $isAr ? 'لا يمكن تغيير البريد الإلكتروني من هذه الصفحة.' : 'Email cannot be changed here.' }}</small></div>
                        <div class="col-md-6 mb-3"><label>{{ $isAr ? 'رقم الهاتف' : 'Phone number' }}</label><input class="sanad-form-control" name="contact_number" value="{{ old('contact_number', $user->contact_number) }}"></div>
                        <div class="col-md-8 mb-3"><label>{{ $isAr ? 'العنوان' : 'Address' }}</label><textarea class="sanad-form-control" name="address" rows="3">{{ old('address', $user->address) }}</textarea></div>
                        <div class="col-md-4 mb-3"><label>{{ $isAr ? 'لغة الواجهة' : 'Interface language' }}</label><select class="sanad-form-control" name="language_option"><option value="ar" {{ old('language_option', $user->language_option ?: app()->getLocale()) === 'ar' ? 'selected' : '' }}>العربية</option><option value="en" {{ old('language_option', $user->language_option ?: app()->getLocale()) === 'en' ? 'selected' : '' }}>English</option></select></div>
                    </div>
                    <button class="sanad-btn" type="submit"><i class="fas fa-save"></i>{{ $isAr ? 'حفظ التغييرات' : 'Save changes' }}</button>
                </div>
            </form>
        </div>
        <div class="col-xl-5 mb-3">
            <form class="sanad-card h-100" method="post" action="{{ route('customer-portal.profile.password') }}">
                @csrf
                <div class="sanad-card-header"><i class="fas fa-lock text-primary mr-2"></i>{{ $isAr ? 'كلمة المرور والأمان' : 'Password and security' }}</div>
                <div class="sanad-card-body">
                    <div class="mb-3"><label>{{ $isAr ? 'كلمة المرور الحالية' : 'Current password' }}</label><input class="sanad-form-control" type="password" name="current_password" autocomplete="current-password" required></div>
                    <div class="mb-3"><label>{{ $isAr ? 'كلمة المرور الجديدة' : 'New password' }}</label><input class="sanad-form-control" type="password" name="password" minlength="8" autocomplete="new-password" required></div>
                    <div class="mb-3"><label>{{ $isAr ? 'تأكيد كلمة المرور الجديدة' : 'Confirm new password' }}</label><input class="sanad-form-control" type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required></div>
                    <p class="sanad-muted small">{{ $isAr ? 'استخدم ثمانية أحرف على الأقل ولا تشارك كلمة المرور مع أي شخص.' : 'Use at least eight characters and never share your password.' }}</p>
                    <button class="sanad-btn secondary" type="submit"><i class="fas fa-key"></i>{{ $isAr ? 'تغيير كلمة المرور' : 'Change password' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
</x-master-layout>
