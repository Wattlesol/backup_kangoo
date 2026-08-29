@php
    $locale = app()->getLocale();
    $isAr = in_array($locale, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']);
    $dir = $isAr ? 'rtl' : 'ltr';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('sanad.brand.name', 'Quick') }} | {{ $isAr ? 'تسجيل الدخول' : 'Sign In' }}</title>
    <link rel="shortcut icon" href="{{ asset('brand/quick-mark.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --auth-ink: #0a1626;
            --auth-muted: #667184;
            --auth-card: #ffffff;
            --auth-soft: #f4f1de;
            --auth-line: rgba(15,41,51,.12);
        }
        .auth-dark {
            --auth-ink: #ffffff;
            --auth-muted: #92a0b2;
            --auth-card: #0a1626;
            --auth-soft: #0f2933;
            --auth-line: rgba(255,255,255,.12);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body {
            min-height: 100vh;
            font-family: 'Cairo', sans-serif;
            background: var(--auth-card);
            color: var(--auth-ink);
            -webkit-font-smoothing: antialiased;
        }
        a { text-decoration: none; color: inherit; }
        button, input { font-family: inherit; }

        .auth-prototype {
            min-height: 100vh;
            background: var(--auth-card);
            color: var(--auth-ink);
            transition: background .25s ease, color .25s ease;
            position: relative;
        }
        .auth-layout {
            display: grid;
            min-height: 100vh;
            grid-template-columns: minmax(380px, .9fr) minmax(520px, 1.1fr);
        }
        .auth-brand-panel {
            position: relative;
            overflow: hidden;
            display: flex;
            min-height: 100vh;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px clamp(36px, 5vw, 76px);
            color: #ffffff;
            background: radial-gradient(circle at 30% 20%, rgba(31,107,255,.32), transparent 35%), linear-gradient(145deg, #0f2933, #0a1626 72%);
        }
        .auth-brand-panel::after {
            content: "";
            position: absolute;
            inset: 0;
            opacity: .18;
            background-image: radial-gradient(rgba(255,255,255,.8) 1px, transparent 1px);
            background-size: 25px 25px;
            -webkit-mask-image: linear-gradient(to bottom, transparent, black 22%, transparent 90%);
            mask-image: linear-gradient(to bottom, transparent, black 22%, transparent 90%);
        }
        .auth-brand-panel > * { position: relative; z-index: 2; }
        .auth-brand-copy { max-width: 610px; padding-block: 90px; }
        .auth-brand-copy > span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(255,255,255,.16);
            border-radius: 99px;
            padding: 8px 14px;
            color: #dce8ff;
            background: rgba(255,255,255,.06);
            font-size: 12px;
            font-weight: 700;
        }
        .auth-brand-copy > span svg { width: 15px; height: 15px; flex-shrink: 0; }
        .auth-brand-copy h1 {
            max-width: 640px;
            margin: 24px 0 0;
            font-size: clamp(2.4rem, 4.4vw, 4.4rem);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -.035em;
        }
        .auth-brand-copy p {
            max-width: 560px;
            margin: 24px 0 0;
            color: #aebdd0;
            font-size: 15px;
            line-height: 1.9;
        }
        .auth-benefits { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 34px; }
        .auth-benefits div {
            display: flex;
            align-items: center;
            gap: 9px;
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 14px;
            padding: 11px 14px;
            background: rgba(255,255,255,.05);
            font-size: 12px;
        }
        .auth-benefits svg { width: 16px; height: 16px; color: #6fa0ff; flex-shrink: 0; }
        .auth-orbit {
            position: absolute;
            z-index: 1;
            width: 620px;
            height: 620px;
            inset-inline-end: -260px;
            bottom: -270px;
            border: 1px solid rgba(31,107,255,.18);
            border-radius: 50%;
            box-shadow: 0 0 0 70px rgba(31,107,255,.035), 0 0 0 140px rgba(31,107,255,.02);
            pointer-events: none;
        }
        .auth-orbit i {
            position: absolute;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #1f6bff;
            box-shadow: 0 0 22px #1f6bff;
        }
        .auth-orbit i:nth-child(1) { top: 18%; left: 7%; }
        .auth-orbit i:nth-child(2) { top: 2%; right: 27%; }
        .auth-orbit i:nth-child(3) { bottom: 28%; left: -1%; }

        .auth-form-panel {
            position: relative;
            display: grid;
            min-height: 100vh;
            place-items: center;
            padding: 64px clamp(24px, 6vw, 96px) 60px;
            background: var(--auth-card);
        }
        .auth-inline-controls {
            position: absolute;
            top: 24px;
            inset-inline-end: 28px;
            display: flex;
            gap: 8px;
            z-index: 10;
        }
        .auth-inline-controls a,
        .auth-inline-controls button {
            display: inline-flex;
            min-width: 44px;
            height: 44px;
            align-items: center;
            justify-content: center;
            gap: 7px;
            border: 1px solid var(--auth-line);
            border-radius: 11px;
            padding: 0 13px;
            color: var(--auth-muted);
            background: var(--auth-card);
            cursor: pointer;
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
            transition: all .2s ease;
        }
        .auth-inline-controls svg { width: 17px; height: 17px; }
        .auth-inline-controls a:hover,
        .auth-inline-controls button:hover {
            border-color: #1f6bff;
            color: var(--auth-ink);
        }

        .auth-form-wrap { width: min(100%, 520px); }
        .auth-mobile-logo { display: none; }

        .auth-audience-tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px;
            padding: 4px;
            border-radius: 15px;
            background: var(--auth-soft);
        }
        .auth-audience-tabs button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 0;
            border-radius: 11px;
            padding: 12px;
            color: var(--auth-muted);
            background: transparent;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            transition: all .2s;
        }
        .auth-audience-tabs button.active {
            color: #fff;
            background: linear-gradient(135deg, #1f6bff, #0f2933);
            box-shadow: 0 8px 22px rgba(31,107,255,.18);
        }
        .auth-audience-tabs svg { width: 16px; height: 16px; }

        .auth-heading { margin-top: 28px; }
        .auth-heading h2 {
            margin: 0;
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -.03em;
            color: var(--auth-ink);
        }
        .auth-heading p {
            margin: 8px 0 0;
            color: var(--auth-muted);
            font-size: 13px;
        }

        .auth-role-block { margin-top: 20px; }
        .auth-role-block small {
            display: block;
            color: var(--auth-muted);
            font-weight: 700;
            font-size: 11px;
            margin-bottom: 8px;
        }
        .auth-role-block > div { display: grid; grid-template-columns: repeat(3, 1fr); gap: 7px; }
        .auth-role-block button {
            border: 1px solid var(--auth-line);
            border-radius: 11px;
            padding: 10px;
            color: var(--auth-muted);
            background: transparent;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            transition: all .2s;
        }
        .auth-role-block button.active {
            border-color: rgba(31,107,255,.45);
            color: #1f6bff;
            background: rgba(31,107,255,.08);
        }

        .auth-alert {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-top: 16px;
            border-radius: 12px;
            padding: 11px 14px;
            font-size: 12px;
            font-weight: 700;
        }
        .auth-alert.error { color: #a93636; background: #fff0ef; }
        .auth-alert.success { color: #187650; background: #eaf8f2; }
        .auth-dark .auth-alert.error { color: #ffb2ac; background: rgba(208,62,50,.18); }
        .auth-dark .auth-alert.success { color: #8ee0bd; background: rgba(42,163,113,.18); }
        .auth-alert svg { width: 16px; height: 16px; flex-shrink: 0; }

        .auth-form-wrap form { margin-top: 20px; }
        .auth-field { margin-top: 16px; }
        .auth-field > label {
            display: block;
            margin-bottom: 7px;
            color: var(--auth-muted);
            font-size: 12px;
            font-weight: 700;
        }
        .auth-field > div {
            display: flex;
            height: 50px;
            align-items: center;
            gap: 10px;
            border: 1px solid var(--auth-line);
            border-radius: 13px;
            padding: 0 14px;
            background: color-mix(in srgb, var(--auth-card) 88%, var(--auth-soft));
            transition: border-color .2s, box-shadow .2s;
        }
        .auth-field > div:focus-within {
            border-color: #1f6bff;
            box-shadow: 0 0 0 3px rgba(31,107,255,.1);
        }
        .auth-field > div.invalid {
            border-color: #d34b42;
            box-shadow: 0 0 0 3px rgba(211,75,66,.08);
        }
        .auth-field svg { width: 16px; height: 16px; flex-shrink: 0; color: #8090a4; }
        .auth-field input {
            min-width: 0;
            flex: 1;
            border: 0;
            outline: 0;
            color: var(--auth-ink);
            background: transparent;
            font-size: 14px;
        }
        .auth-pw-toggle {
            display: grid;
            place-items: center;
            border: 0;
            background: transparent;
            color: var(--auth-muted);
            cursor: pointer;
            padding: 4px;
        }
        .auth-form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 16px;
            color: var(--auth-muted);
            font-size: 12px;
        }
        .auth-form-options label {
            display: flex;
            align-items: center;
            gap: 7px;
            cursor: pointer;
        }
        .auth-form-options input { accent-color: #1f6bff; }
        .auth-form-options a,
        .auth-register-link a {
            border: 0;
            color: #1f6bff;
            background: transparent;
            cursor: pointer;
            font-size: inherit;
            font-weight: 700;
        }
        .auth-form-options a:hover,
        .auth-register-link a:hover {
            text-decoration: underline;
        }
        .auth-submit {
            display: flex;
            width: 100%;
            height: 52px;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 22px;
            border: 0;
            border-radius: 13px;
            color: #fff;
            background: linear-gradient(135deg, #1f6bff, #0f2933);
            box-shadow: 0 14px 30px rgba(31,107,255,.2);
            cursor: pointer;
            font-size: 13px;
            font-weight: 800;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .auth-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 36px rgba(31,107,255,.28);
        }
        .auth-submit:active { transform: translateY(0); }
        .auth-submit svg { width: 16px; height: 16px; }
        [dir="rtl"] .auth-submit svg { transform: rotate(180deg); }

        .auth-register-link {
            margin: 20px 0 0;
            color: var(--auth-muted);
            text-align: center;
            font-size: 12px;
        }
        .auth-policy-note {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin: 24px 0 0;
            border-top: 1px solid var(--auth-line);
            padding-top: 18px;
            color: var(--auth-muted);
            font-size: 11px;
            line-height: 1.7;
        }
        .auth-policy-note svg { width: 15px; height: 15px; flex-shrink: 0; color: #1f6bff; margin-top: 2px; }

        @media (max-width: 900px) {
            .auth-layout { display: block; }
            .auth-brand-panel { display: none; }
            .auth-form-panel { min-height: 100vh; padding: 48px 20px 60px; }
            .auth-mobile-logo { display: block; margin-bottom: 28px; }
            .auth-form-wrap { max-width: 520px; }
        }
        @media (max-width: 520px) {
            .auth-heading h2 { font-size: 27px; }
        }
    </style>
</head>
<body>
    <div id="auth-root" class="auth-prototype auth-light" dir="{{ $dir }}">
        <main class="auth-layout">
            <!-- Left Brand Panel -->
            <section class="auth-brand-panel">
                <a href="{{ route('frontend.index') }}" aria-label="Quick home">
                    <x-quick-logo :dark="true" />
                </a>

                <div class="auth-brand-copy">
                    <span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                        {{ $isAr ? 'بداية آمنة' : 'A secure start' }}
                    </span>
                    <h1>{{ $isAr ? 'دخول موثوق لكل رحلة مع كويك.' : 'One trusted entry for every Quick journey.' }}</h1>
                    <p>{{ $isAr ? 'يدير العملاء معاملاتهم الرسمية، بينما تعمل الفرق المصرّح لها عبر بوابات محمية حسب الدور.' : 'Customers manage official requests while authorized teams work through role-protected portals.' }}</p>

                    <div class="auth-benefits">
                        <div>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="16" r="1"/><rect x="3" y="10" width="18" height="12" rx="2"/><path d="M7 10V7a5 5 0 0 1 10 0v3"/></svg>
                            <strong>{{ $isAr ? 'جلسات مشفّرة' : 'Encrypted sessions' }}</strong>
                        </div>
                        <div>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 12 2 2 4-4"/><path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/></svg>
                            <strong>{{ $isAr ? 'صلاحيات حسب الدور' : 'Role-aware access' }}</strong>
                        </div>
                        <div>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 8 6 6"/><path d="m4 14 6-6 2-3"/><path d="M2 5h12"/><path d="M7 2h1"/><path d="m22 22-5-10-5 10"/><path d="M14 18h6"/></svg>
                            <strong>{{ $isAr ? 'تجربة عربية أولاً' : 'Arabic-first experience' }}</strong>
                        </div>
                    </div>
                </div>

                <div class="auth-orbit" aria-hidden="true">
                    <i></i><i></i><i></i>
                </div>
            </section>

            <!-- Right Form Panel -->
            <section class="auth-form-panel">
                <div class="auth-inline-controls">
                    <a href="{{ route('switch-language', ['locale' => $isAr ? 'en' : 'ar']) }}" aria-label="{{ $isAr ? 'Switch to English' : 'التغيير إلى العربية' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 8 6 6"/><path d="m4 14 6-6 2-3"/><path d="M2 5h12"/><path d="M7 2h1"/><path d="m22 22-5-10-5 10"/><path d="M14 18h6"/></svg>
                        <span>{{ $isAr ? 'English' : 'العربية' }}</span>
                    </a>
                    <button type="button" id="theme-toggle" aria-label="Toggle theme" onclick="toggleTheme()">
                        <svg id="theme-icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                        <svg id="theme-icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                    </button>
                </div>

                <div class="auth-form-wrap">
                    <div class="auth-mobile-logo">
                        <a href="{{ route('frontend.index') }}">
                            <x-quick-logo />
                        </a>
                    </div>

                    <div class="auth-audience-tabs" role="tablist" aria-label="{{ $isAr ? 'نوع الحساب' : 'Account type' }}">
                        <button type="button" id="tab-customer" class="active" onclick="switchAudience('customer')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>
                            <span>{{ $isAr ? 'عميل' : 'Customer' }}</span>
                        </button>
                        <button type="button" id="tab-staff" onclick="switchAudience('staff')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                            <span>{{ $isAr ? 'عضو فريق' : 'Team member' }}</span>
                        </button>
                    </div>

                    <div class="auth-heading">
                        <h2>{{ $isAr ? 'مرحباً بعودتك' : 'Welcome back' }}</h2>
                        <p>{{ $isAr ? 'سجّل الدخول للمتابعة بأمان.' : 'Sign in to continue securely.' }}</p>
                    </div>

                    <div id="staff-role-block" class="auth-role-block" style="display: none;">
                        <small>{{ $isAr ? 'اختر البوابة المصرّح بها' : 'Choose your authorized portal' }}</small>
                        <div>
                            <button type="button" class="portal-btn active" data-role="admin" onclick="selectRole('admin')">{{ $isAr ? 'الإدارة' : 'Admin' }}</button>
                            <button type="button" class="portal-btn" data-role="partner" onclick="selectRole('partner')">{{ $isAr ? 'الشريك' : 'Partner' }}</button>
                            <button type="button" class="portal-btn" data-role="employee" onclick="selectRole('employee')">{{ $isAr ? 'الموظف' : 'Employee' }}</button>
                        </div>
                    </div>

                    @if (session('status'))
                        <div class="auth-alert success" role="status">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="auth-alert error" role="alert">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ request()->is('auth/login') ? route('auth.login.post') : route('login') }}" id="login-form">
                        {{ csrf_field() }}

                        <div class="auth-field">
                            <label for="email">{{ $isAr ? 'البريد الإلكتروني' : 'Email address' }}</label>
                            <div class="{{ $errors->has('email') ? 'invalid' : '' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                <input id="email" name="email" value="{{ old('email', 'demo@quick.sa') }}" type="email" placeholder="{{ $isAr ? 'name@example.com' : 'name@example.com' }}" required autofocus autocomplete="username">
                            </div>
                        </div>

                        <div class="auth-field">
                            <label for="password">{{ $isAr ? 'كلمة المرور' : 'Password' }}</label>
                            <div class="{{ $errors->has('password') ? 'invalid' : '' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                <input id="password" name="password" value="Quick@2026" type="password" placeholder="••••••••" required autocomplete="current-password">
                                <button type="button" class="auth-pw-toggle" onclick="togglePasswordVisibility()" aria-label="{{ $isAr ? 'إظهار كلمة المرور' : 'Toggle password' }}">
                                    <svg id="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg id="eye-off-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="auth-form-options">
                            <label>
                                <input type="checkbox" name="remember" id="remember" value="1" checked>
                                {{ $isAr ? 'تذكرني' : 'Remember me' }}
                            </label>
                            <a href="{{ route('auth.recover-password') }}">{{ $isAr ? 'نسيت كلمة المرور؟' : 'Forgot password?' }}</a>
                        </div>

                        <button class="auth-submit" type="submit" id="submit-btn">
                            <span>{{ $isAr ? 'تسجيل الدخول بأمان' : 'Sign in securely' }}</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                        </button>
                    </form>

                    <p id="register-row" class="auth-register-link">
                        {{ $isAr ? 'جديد في كويك؟' : 'New to Quick?' }}
                        <a href="{{ route('auth.register') }}">{{ $isAr ? 'إنشاء حساب' : 'Create an account' }}</a>
                    </p>

                    <p class="auth-policy-note">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                        <span>{{ $isAr ? 'يمكن للعملاء التسجيل هنا. ينشئ المسؤول حسابات الفريق ويحدد صلاحياتها.' : 'Customer accounts can register here. Team accounts are created and permissioned by an administrator.' }}</span>
                    </p>
                </div>
            </section>
        </main>
    </div>

    <script>
        let currentAudience = 'customer';
        let currentRole = 'admin';

        const demoAccounts = {
            customer: { email: 'demo@customer.com', pass: '12345678' },
            admin:    { email: 'demo@admin.com',    pass: '12345678' },
            partner:  { email: 'demo@provider.com', pass: '12345678' },
            employee: { email: 'demo@handyman.com', pass: '12345678' }
        };

        function switchAudience(aud) {
            currentAudience = aud;
            const btnCustomer = document.getElementById('tab-customer');
            const btnStaff = document.getElementById('tab-staff');
            const roleBlock = document.getElementById('staff-role-block');
            const registerRow = document.getElementById('register-row');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');

            if (aud === 'customer') {
                btnCustomer.classList.add('active');
                btnStaff.classList.remove('active');
                roleBlock.style.display = 'none';
                registerRow.style.display = 'block';
                if (emailInput && (!emailInput.value || Object.values(demoAccounts).some(d => d.email === emailInput.value) || emailInput.value === 'demo@quick.sa')) {
                    emailInput.value = demoAccounts.customer.email;
                    passwordInput.value = demoAccounts.customer.pass;
                }
            } else {
                btnStaff.classList.add('active');
                btnCustomer.classList.remove('active');
                roleBlock.style.display = 'block';
                registerRow.style.display = 'none';
                selectRole(currentRole);
            }
        }

        function selectRole(role) {
            currentRole = role;
            document.querySelectorAll('.portal-btn').forEach(btn => {
                if (btn.dataset.role === role) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            if (emailInput && (!emailInput.value || Object.values(demoAccounts).some(d => d.email === emailInput.value) || emailInput.value === 'demo@quick.sa')) {
                emailInput.value = demoAccounts[role].email;
                passwordInput.value = demoAccounts[role].pass;
            }
        }

        function togglePasswordVisibility() {
            const pass = document.getElementById('password');
            const eye = document.getElementById('eye-icon');
            const eyeOff = document.getElementById('eye-off-icon');
            if (pass.type === 'password') {
                pass.type = 'text';
                eye.style.display = 'none';
                eyeOff.style.display = 'block';
            } else {
                pass.type = 'password';
                eye.style.display = 'block';
                eyeOff.style.display = 'none';
            }
        }

        function applyTheme(theme) {
            const root = document.getElementById('auth-root');
            const moon = document.getElementById('theme-icon-moon');
            const sun = document.getElementById('theme-icon-sun');
            if (theme === 'dark') {
                root.classList.remove('auth-light');
                root.classList.add('auth-dark');
                if (moon && sun) {
                    moon.style.display = 'none';
                    sun.style.display = 'block';
                }
            } else {
                root.classList.remove('auth-dark');
                root.classList.add('auth-light');
                if (moon && sun) {
                    moon.style.display = 'block';
                    sun.style.display = 'none';
                }
            }
            localStorage.setItem('quick_theme', theme);
        }

        function toggleTheme() {
            const root = document.getElementById('auth-root');
            const isDark = root.classList.contains('auth-dark');
            applyTheme(isDark ? 'light' : 'dark');
        }

        const savedTheme = localStorage.getItem('quick_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        applyTheme(savedTheme);
    </script>
</body>
</html>
