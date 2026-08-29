<x-master-layout>
@php $isAr = app()->getLocale() === 'ar'; @endphp
<style>
    .partner-queue-page{max-width:1480px;margin-inline:auto;padding:28px 4px 56px;color:var(--quick-shell-ink,#0a1626)}.partner-queue-title{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:20px}.partner-queue-title h1{margin:0;font-size:30px;font-weight:900;letter-spacing:-.03em}.partner-queue-title p{margin:4px 0 0;color:var(--quick-shell-muted,#6a7c93);font-size:12px}.partner-queue-title-icon{display:inline-grid;width:36px;height:36px;place-items:center;border-radius:11px;background:#edf4ff;color:#1769ff}.partner-refresh{display:inline-flex;align-items:center;gap:8px;min-height:42px;padding:0 14px;border:1px solid var(--quick-shell-line,#d8e4f2);border-radius:11px;background:var(--quick-shell-surface,#fff);color:var(--quick-shell-ink,#0a1626);font-size:11px;font-weight:800;cursor:pointer}.partner-queue-card{border:1px solid var(--quick-shell-line,#d8e4f2);border-radius:18px;background:var(--quick-shell-surface,#fff);box-shadow:0 7px 22px rgba(15,41,51,.04);overflow:hidden}.partner-filter-bar{display:grid;grid-template-columns:minmax(250px,1fr) repeat(3,minmax(170px,.35fr));gap:12px;padding:18px;border-bottom:1px solid var(--quick-shell-line,#d8e4f2)}.partner-filter-search{position:relative}.partner-filter-search i{position:absolute;inset-inline-start:14px;top:50%;color:#8a9ab0;transform:translateY(-50%)}.partner-filter-search input{padding-inline-start:42px!important}.partner-filter-bar .form-control{height:44px!important;border-radius:11px!important}.partner-queue-table{padding:4px 18px 18px}.partner-queue-page .dataTables_wrapper{padding-top:12px}.partner-queue-page .dataTables_filter{display:none}.partner-queue-page table{margin-top:0!important}.partner-queue-page table thead th{border-top:0!important;border-bottom:1px solid var(--quick-shell-line,#d8e4f2)!important;background:#f5f8fc!important;color:#60728a!important;font-size:10px!important;font-weight:900!important;text-transform:uppercase}.partner-queue-page table tbody td{border-color:var(--quick-shell-line,#e5edf7)!important;vertical-align:middle!important;font-size:11px}.partner-queue-page .badge{padding:7px 10px;border-radius:999px;font-size:10px}.partner-queue-page .btn-primary{min-width:54px;border-radius:10px!important}.quick-theme-dark .partner-queue-page table thead th{background:#10283f!important;color:#b9c9dc!important}.quick-theme-dark .partner-filter-search input{background:#0a1c2e!important;color:#fff!important}
    @media(max-width:1100px){.partner-filter-bar{grid-template-columns:1fr 1fr}.partner-filter-search{grid-column:1/-1}}@media(max-width:700px){.partner-queue-page{padding-top:16px}.partner-queue-title{align-items:flex-start;flex-direction:column}.partner-queue-title h1{font-size:24px}.partner-refresh{width:100%;justify-content:center}.partner-filter-bar{grid-template-columns:1fr}.partner-filter-search{grid-column:auto}.partner-queue-table{padding-inline:12px}}
</style>
<div class="partner-queue-page" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
    <header class="partner-queue-title">
        <div><h1><span class="partner-queue-title-icon"><i class="fas fa-clipboard-list"></i></span> {{ $isAr ? 'طابور المعاملات المسندة للمكتب' : 'Assigned transaction queue' }}</h1><p>{{ $isAr ? 'متابعة مراحل التنفيذ، توجيه الموظفين، رفع المستندات المنجزة، والالتزام بمهلة SLA.' : 'Track execution stages, employee assignments, completed documents, and SLA commitments.' }}</p></div>
        <button class="partner-refresh" type="button" onclick="window.renderedDataTable && window.renderedDataTable.ajax.reload(null, false)"><i class="fas fa-sync-alt"></i>{{ $isAr ? 'تحديث الطابور' : 'Refresh queue' }}</button>
    </header>

    <section class="partner-queue-card">
        <div class="partner-filter-bar">
            <label class="partner-filter-search mb-0"><i class="fas fa-search"></i><input class="form-control" id="partner_queue_search" placeholder="{{ $isAr ? 'بحث برقم المعاملة أو العميل أو نوع الخدمة...' : 'Search by transaction, customer, or service...' }}"></label>
            <select class="form-control" id="column_stage"><option value="">{{ $isAr ? 'جميع مراحل المعاملة' : 'All transaction stages' }}</option>@foreach(config('sanad.request_lifecycle', []) as $stage)<option value="{{ $stage }}" {{ $filter['sanad_stage'] === $stage ? 'selected' : '' }}>{{ quick_status_label($stage) }}</option>@endforeach</select>
            <select class="form-control" id="column_priority"><option value="">{{ $isAr ? 'جميع الأولويات' : 'All priorities' }}</option>@foreach(['low','normal','high','urgent'] as $priority)<option value="{{ $priority }}" {{ $filter['sanad_priority'] === $priority ? 'selected' : '' }}>{{ quick_status_label($priority) }}</option>@endforeach</select>
            <select class="form-control" id="column_sla"><option value="">{{ $isAr ? 'جميع حالات SLA' : 'All SLA states' }}</option><option value="overdue" {{ $filter['sla_state'] === 'overdue' ? 'selected' : '' }}>{{ $isAr ? 'متأخر' : 'Overdue' }}</option></select>
        </div>
        <div class="partner-queue-table table-responsive">
            <table id="datatable" class="table" data-toggle="data-table">
                <thead><tr><th>#</th><th>{{ $isAr ? 'المعاملة' : 'Transaction' }}</th><th>{{ $isAr ? 'العميل' : 'Customer' }}</th><th>{{ $isAr ? 'الخدمة' : 'Service' }}</th><th>{{ $isAr ? 'الأولوية' : 'Priority' }}</th><th>{{ $isAr ? 'المرحلة الحالية' : 'Current stage' }}</th><th>{{ $isAr ? 'الموظفون المكلفون' : 'Assigned employees' }}</th><th>SLA</th><th>{{ $isAr ? 'آخر تحديث' : 'Updated' }}</th><th>{{ $isAr ? 'إجراء' : 'Action' }}</th></tr></thead>
            </table>
        </div>
    </section>
</div>

@section('bottom_script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    window.renderedDataTable = $('#datatable').DataTable({
        processing:true, serverSide:true,
        ajax:{url:"{{ route('provider.order.index_data') }}",data:function(d){d.filter={sanad_stage:$('#column_stage').val(),sanad_priority:$('#column_priority').val(),sla_state:$('#column_sla').val()};}},
        columns:[
            {data:'DT_RowIndex',name:'DT_RowIndex',orderable:false,searchable:false},{data:'sanad_reference',name:'sanad_reference'},{data:'customer',name:'customer',orderable:false},{data:'service',name:'service',orderable:false},{data:'sanad_priority',name:'sanad_priority'},{data:'sanad_stage',name:'sanad_stage'},{data:'assigned_employees',name:'assigned_employees',orderable:false},{data:'sla',name:'sla_due_at'},{data:'updated_at',name:'updated_at'},{data:'action',name:'action',orderable:false,searchable:false}
        ]
    });
    $('#column_stage,#column_priority,#column_sla').on('change',function(){window.renderedDataTable.ajax.reload();});
    $('#partner_queue_search').on('input',function(){window.renderedDataTable.search(this.value).draw();});
});
</script>
@endsection
</x-master-layout>
