<x-master-layout>
    @php $isAr = app()->getLocale() === 'ar'; @endphp
    <div class="container-fluid quick-role-page quick-employee-page">
        <div class="card quick-role-hero">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <span class="text-uppercase small font-weight-bold">{{ $isAr ? 'مساحة عمل الموظف' : 'Employee workspace' }}</span>
                    <h4 class="font-weight-bold mt-2 mb-1">{{ $isAr ? 'المهام المسندة إليك اليوم' : 'Work assigned to you today' }}</h4>
                    <p class="text-muted mb-0">{{ $isAr ? 'تابع التنفيذ، الأدلة المطلوبة، المحادثات، وحالة الدفع المسموح بها.' : 'Track execution, required evidence, conversations, and permitted payment status.' }}</p>
                </div>
                <a href="{{ route('sanad.requests.index') }}" class="btn btn-light">
                    <i class="fas fa-clipboard-check mr-1"></i> {{ $isAr ? 'فتح قائمة العمل' : 'Open Work Queue' }}
                </a>
            </div>
        </div>

        <div class="row">
            @include('dashboard.partials.sanad-employee-overview')
        </div>
    </div>
</x-master-layout>
