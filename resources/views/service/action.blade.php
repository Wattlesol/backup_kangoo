@php
    $auth_user = authSession();
@endphp
{{ Form::open(['route' => ['service.destroy', $data->id], 'method' => 'delete', 'data--submit'=>'service'.$data->id, 'style' => 'margin:0;display:inline;']) }}
<div class="quick-table-actions" style="display: flex; align-items: center; justify-content: flex-end; gap: 6px;">
    @if(auth()->user()->can('service edit'))
        <a href="{{ route('service.create', ['id' => $data->id]) }}" 
           class="quick-action-btn quick-action-btn-edit" 
           title="{{ __('messages.update_form_title',['form'=> __('messages.service')]) }}"
           style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--quick-shell-line); color: var(--quick-blue); background: var(--quick-shell-surface); text-decoration: none; transition: all .15s ease;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 14px; height: 14px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </a>
    @endif
    @if(!$data->trashed())
        @if(auth()->user()->hasAnyRole(['admin','provider']))
            <a href="{{ route('servicefaq.index',['id' => $data->id]) }}" 
               class="quick-action-btn" 
               title="{{ __('messages.add_form_title',['form' => __('messages.servicefaq') ]) }}"
               style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--quick-shell-line); color: #8b5cf6; background: var(--quick-shell-surface); text-decoration: none; transition: all .15s ease;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 14px; height: 14px;"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </a>
        @endif
        @if($auth_user->can('service delete'))
            <a class="quick-action-btn quick-action-btn-delete delete-service" 
               href="{{ route('service.destroy', $data->id) }}" 
               data--submit="service{{$data->id}}" 
               data--confirmation="true" 
               data--ajax="true" 
               data-datatable="reload" 
               data-title="{{ __('messages.delete_form_title',['form'=>  __('messages.service') ]) }}" 
               title="{{ __('messages.delete_form_title',['form'=>  __('messages.service') ]) }}" 
               data--message='{{ __("messages.delete_msg") }}'
               style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--quick-shell-line); color: #ef4444; background: var(--quick-shell-surface); text-decoration: none; transition: all .15s ease;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 14px; height: 14px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
            </a>
        @endif
    @endif
    @if(auth()->user()->hasAnyRole(['admin']) && $data->trashed())
        <a href="{{ route('service.action',['id' => $data->id, 'type' => 'restore']) }}"
            title="{{ __('messages.restore_form_title',['form' => __('messages.service') ]) }}"
            data--submit="confirm_form"
            data--confirmation="true"
            data--ajax="true"
            data-title="{{ __('messages.restore_form_title',['form'=>  __('messages.service') ]) }}"
            data-message='{{ __("messages.restore_msg") }}'
            data-datatable="reload"
            class="quick-action-btn quick-action-btn-restore"
            style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--quick-shell-line); color: #10b981; background: var(--quick-shell-surface); text-decoration: none;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 14px; height: 14px;"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
        </a>
        <a href="{{ route('service.action',['id' => $data->id, 'type' => 'forcedelete']) }}"
            title="{{ __('messages.forcedelete_form_title',['form' => __('messages.service') ]) }}"
            data--submit="confirm_form"
            data--confirmation="true"
            data--ajax="true"
            data-title="{{ __('messages.forcedelete_form_title',['form'=>  __('messages.service') ]) }}"
            data-message='{{ __("messages.forcedelete_msg") }}'
            data-datatable="reload"
            class="quick-action-btn quick-action-btn-forcedelete"
            style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--quick-shell-line); color: #dc2626; background: var(--quick-shell-surface); text-decoration: none;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 14px; height: 14px;"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </a>
    @endif
</div>
{{ Form::close() }}
