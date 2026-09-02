<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? trans('messages.list') }}</h5>
                            @if($auth_user->can('store edit'))
                                <a href="{{ route('store.create') }}?id={{$store->id}}" class="float-right btn btn-sm btn-primary">
                                    <i class="fa fa-plus mr-2"></i>{{ __('messages.update_form_title',['form' => __('messages.store')]) }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary">{{ __('messages.store') }} {{ __('messages.information') }}</h6>
                                <hr>
                                <div class="form-group">
                                    <label class="form-control-label">{{ __('messages.name') }}: </label>
                                    <span class="text-muted">{{ $store->name ?? '-' }}</span>
                                </div>
                                <div class="form-group">
                                    <label class="form-control-label">{{ __('messages.description') }}: </label>
                                    <span class="text-muted">{{ $store->description ?? '-' }}</span>
                                </div>
                                <div class="form-group">
                                    <label class="form-control-label">{{ __('messages.email') }}: </label>
                                    <span class="text-muted">{{ $store->email ?? '-' }}</span>
                                </div>
                                <div class="form-group">
                                    <label class="form-control-label">{{ __('messages.phone') }}: </label>
                                    <span class="text-muted">{{ $store->phone ?? '-' }}</span>
                                </div>
                                <div class="form-group">
                                    <label class="form-control-label">{{ __('messages.address') }}: </label>
                                    <span class="text-muted">{{ $store->address ?? '-' }}</span>
                                </div>
                                <div class="form-group">
                                    <label class="form-control-label">Store Type: </label>
                                    <span class="badge badge-{{ $store->store_type == 'main' ? 'primary' : 'secondary' }}">
                                        {{ ucfirst($store->store_type) }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary">{{ __('messages.status') }} & {{ __('messages.settings') }}</h6>
                                <hr>
                                <div class="form-group">
                                    <label class="form-control-label">{{ __('messages.status') }}: </label>
                                    <span class="badge badge-{{ $store->status == 'approved' ? 'success' : ($store->status == 'pending' ? 'warning' : 'danger') }}">
                                        {{ ucfirst($store->status) }}
                                    </span>
                                </div>
                                <div class="form-group">
                                    <label class="form-control-label">Active: </label>
                                    <span class="badge badge-{{ $store->is_active ? 'success' : 'danger' }}">
                                        {{ $store->is_active ? 'Yes' : 'No' }}
                                    </span>
                                </div>
                                @if($store->country)
                                <div class="form-group">
                                    <label class="form-control-label">{{ __('messages.country') }}: </label>
                                    <span class="text-muted">{{ $store->country->name ?? '-' }}</span>
                                </div>
                                @endif
                                @if($store->state)
                                <div class="form-group">
                                    <label class="form-control-label">{{ __('messages.state') }}: </label>
                                    <span class="text-muted">{{ $store->state->name ?? '-' }}</span>
                                </div>
                                @endif
                                @if($store->city)
                                <div class="form-group">
                                    <label class="form-control-label">{{ __('messages.city') }}: </label>
                                    <span class="text-muted">{{ $store->city->name ?? '-' }}</span>
                                </div>
                                @endif
                                <div class="form-group">
                                    <label class="form-control-label">{{ __('messages.created_at') }}: </label>
                                    <span class="text-muted">{{ dateAgoFormate($store->created_at, true) ?? '-' }}</span>
                                </div>
                                @if($store->createdBy)
                                <div class="form-group">
                                    <label class="form-control-label">{{ __('messages.created_by') }}: </label>
                                    <span class="text-muted">{{ $store->createdBy->display_name ?? '-' }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                        
                        @if($store->business_hours)
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6 class="text-primary">Business Hours</h6>
                                <hr>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Day</th>
                                                <th>Status</th>
                                                <th>Opening Time</th>
                                                <th>Closing Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                                                @php
                                                    $hours = $store->business_hours[$day] ?? null;
                                                @endphp
                                                <tr>
                                                    <td>{{ ucfirst($day) }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ ($hours['is_open'] ?? false) ? 'success' : 'danger' }}">
                                                            {{ ($hours['is_open'] ?? false) ? 'Open' : 'Closed' }}
                                                        </span>
                                                    </td>
                                                    <td>{{ ($hours['is_open'] ?? false) ? ($hours['open'] ?? '-') : '-' }}</td>
                                                    <td>{{ ($hours['is_open'] ?? false) ? ($hours['close'] ?? '-') : '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>
