@php
    $auth_user = authSession();
@endphp
{{ Form::open(['route' => ['servicepackage.destroy', $servicepackage->id], 'method' => 'delete', 'data--submit'=>'servicepackage'.$servicepackage->id, 'style' => 'margin:0;display:inline;']) }}
<div class="quick-table-actions" style="display: flex; align-items: center; justify-content: flex-end; gap: 6px;">
    @if($auth_user->can('servicepackage edit'))
        <a href="{{ route('servicepackage.create', ['id' => $servicepackage->id]) }}" 
           class="quick-action-btn quick-action-btn-edit" 
           title="{{ __('messages.update_form_title',['form'=> __('messages.servicepackage')]) }}"
           style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--quick-shell-line); color: var(--quick-blue); background: var(--quick-shell-surface); text-decoration: none; transition: all .15s ease;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 14px; height: 14px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </a>
    @endif
    <a href="{{ route('service-package.detail', ['id' => $servicepackage->id]) }}" 
       class="quick-action-btn" 
       title="{{ __('messages.view') }}"
       style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--quick-shell-line); color: #8b5cf6; background: var(--quick-shell-surface); text-decoration: none; transition: all .15s ease;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 14px; height: 14px;"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
    </a>
    <a href="{{ route('servicepackage.action', ['id' => $servicepackage->id, 'type' => 'forcedelete']) }}" 
       title="{{ __('messages.forcedelete_form_title',['form' => __('messages.service_package') ]) }}" 
       data--submit="confirm_form" 
       data--confirmation="true" 
       data--ajax="true" 
       data-title="{{ __('messages.forcedelete_form_title',['form'=>  __('messages.service_package') ]) }}" 
       data-message='{{ __("messages.forcedelete_msg") }}' 
       data-datatable="reload" 
       class="quick-action-btn quick-action-btn-forcedelete"
       style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--quick-shell-line); color: #dc2626; background: var(--quick-shell-surface); text-decoration: none; transition: all .15s ease;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 14px; height: 14px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
    </a>
</div>
{{ Form::close() }}
