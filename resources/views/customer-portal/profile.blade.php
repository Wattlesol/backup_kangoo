<x-master-layout>
@include('customer-portal.partials.styles')
<div class="container-fluid sanad-page">
    <div class="sanad-header"><div><h1 class="sanad-title">Customer Profile</h1><div class="sanad-muted">Personal information, company information, language, notification preferences, password, devices, and security.</div></div><a class="sanad-btn" href="{{ route('setting.index', ['page' => 'profile-form']) }}">Update Profile</a></div>
    <div class="sanad-grid">
        <div class="sanad-card"><div class="sanad-card-header">Personal Information</div><div class="sanad-card-body"><p><strong>Name:</strong> {{ $user->display_name }}</p><p><strong>Email:</strong> {{ $user->email }}</p><p><strong>Phone:</strong> {{ $user->contact_number ?? '-' }}</p></div></div>
        <div class="sanad-card"><div class="sanad-card-header">Company Information</div><div class="sanad-card-body"><p>{{ $user->company_name ?? 'No company information configured.' }}</p></div></div>
        <div class="sanad-card"><div class="sanad-card-header">Preferences</div><div class="sanad-card-body"><p><strong>Preferred Language:</strong> {{ app()->getLocale() }}</p><p><strong>Notifications:</strong> In-app enabled</p></div></div>
        <div class="sanad-card"><div class="sanad-card-header">Security Settings</div><div class="sanad-card-body"><p><strong>Password:</strong> Managed in settings</p><p><strong>Registered Devices:</strong> Current session tracked by platform.</p><a class="sanad-btn secondary" href="{{ route('setting.index', ['page' => 'change-password']) }}">Change Password</a></div></div>
    </div>
</div>
</x-master-layout>
