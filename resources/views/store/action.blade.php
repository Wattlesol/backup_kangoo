<?php
    $auth_user = authSession();
?>
{!! Form::open(['route' => ['store.destroy', $row->id], 'method' => 'delete','data--submit'=>'store'.$row->id]) !!}
<div class="d-flex justify-content-end align-items-center">
    @if($auth_user->can('store view'))
        <a class="mr-2" href="{{ route('store.show',$row->id) }}" title="{{ __('messages.view_form_title',['form' =>  __('messages.store') ]) }}"><i class="fas fa-eye text-secondary"></i></a>
    @endif
    
    @if($auth_user->can('store edit'))
        <a class="mr-2" href="{{ route('store.create') }}?id={{$row->id}}" title="{{ __('messages.update_form_title',['form' =>  __('messages.store') ]) }}"><i class="fas fa-pen text-secondary"></i></a>
    @endif
    
    @if($auth_user->can('store delete'))
        @if($row->deleted_at == null)
            <a class="mr-2 text-danger" href="javascript:void(0)" data--submit="store{{$row->id}}" 
               data--confirmation='true' data-title="{{ __('messages.delete_form_title',['form'=>  __('messages.store') ]) }}"
               title="{{ __('messages.delete_form_title',['form'=>  __('messages.store') ]) }}"
               data-message='{{ __("messages.delete_msg") }}'>
                <i class="fas fa-trash-alt"></i>
            </a>
        @endif
    @endif
    
    @if($auth_user->can('store delete') && $row->deleted_at != null)
        <a class="mr-2 text-secondary" href="javascript:void(0)" data--submit="store{{$row->id}}" 
           data--confirmation='true' data--ajax="true" data-title="{{ __('messages.restore_form_title',['form'=>  __('messages.store') ]) }}"
           data-message='{{ __("messages.restore_msg") }}' data-datatable="reload"
           data-submit-url="{{ route('store.action',['id' => $row->id, 'type' => 'restore']) }}" 
           title="{{ __('messages.restore_form_title',['form'=>  __('messages.store') ]) }}">
            <i class="fas fa-redo text-secondary"></i>
        </a>
        
        <a class="mr-2 text-danger" href="javascript:void(0)" data--submit="store{{$row->id}}" 
           data--confirmation='true' data--ajax="true" data-title="{{ __('messages.force_delete_form_title',['form'=>  __('messages.store') ]) }}"
           data-message='{{ __("messages.force_delete_msg") }}' data-datatable="reload"
           data-submit-url="{{ route('store.action',['id' => $row->id, 'type' => 'forcedelete']) }}" 
           title="{{ __('messages.force_delete_form_title',['form'=>  __('messages.store') ]) }}">
            <i class="fas fa-trash-alt"></i>
        </a>
    @endif
</div>
{!! Form::close() !!}
