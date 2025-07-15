<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? trans('messages.add_form_title',['form' => trans('messages.product_category')]) }}</h5>
                            @if($auth_user->can('product_category list'))
                                <a href="{{ route('productcategory.index') }}" class="float-right btn btn-sm btn-primary">
                                    <i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('productcategory.store') }}" method="POST" enctype="multipart/form-data" id="productcategory">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label class="form-control-label" for="name">{{ __('messages.name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control" placeholder="{{ __('messages.name') }}" value="{{ old('name') }}" required>
                                    <small class="help-block with-errors text-danger"></small>
                                </div>

                                <div class="form-group col-md-4">
                                    <label class="form-control-label" for="slug">{{ __('messages.slug') }}</label>
                                    <input type="text" name="slug" id="slug" class="form-control" placeholder="{{ __('messages.slug') }}" value="{{ old('slug') }}">
                                    <small class="text-muted">{{ __('messages.leave_blank_auto_generate') }}</small>
                                </div>

                                <div class="form-group col-md-4">
                                    <label class="form-control-label" for="status">{{ trans('messages.status') }} <span class="text-danger">*</span></label>
                                    <select name="status" id="status" class="form-control select2js" required>
                                        <option value="1" {{ old('status', 1) == 1 ? 'selected' : '' }}>{{ __('messages.active') }}</option>
                                        <option value="0" {{ old('status') == 0 ? 'selected' : '' }}>{{ __('messages.inactive') }}</option>
                                    </select>
                                </div>

                                <div class="form-group col-md-12">
                                    <label class="form-control-label" for="description">{{ __('messages.description') }}</label>
                                    <textarea name="description" id="description" class="form-control" placeholder="{{ __('messages.description') }}" rows="3">{{ old('description') }}</textarea>
                                </div>

                                <div class="form-group col-md-4">
                                    <label class="form-control-label" for="image">{{ __('messages.image') }}</label>
                                    <div class="custom-file">
                                        <input type="file" name="image" id="image" class="custom-file-input" accept="image/*">
                                        <label class="custom-file-label upload-label">{{ __('messages.choose_file',['file' =>  __('messages.image') ]) }}</label>
                                    </div>
                                </div>

                                <div class="form-group col-md-4">
                                    <label class="form-control-label" for="is_featured">{{ __('messages.featured') }}</label>
                                    <select name="is_featured" id="is_featured" class="form-control select2js">
                                        <option value="0" {{ old('is_featured', 0) == 0 ? 'selected' : '' }}>{{ __('messages.no') }}</option>
                                        <option value="1" {{ old('is_featured') == 1 ? 'selected' : '' }}>{{ __('messages.yes') }}</option>
                                    </select>
                                </div>

                                <div class="form-group col-md-4">
                                    <label class="form-control-label" for="sort_order">{{ __('messages.sort_order') }}</label>
                                    <input type="number" name="sort_order" id="sort_order" class="form-control" placeholder="{{ __('messages.sort_order') }}" value="{{ old('sort_order', 0) }}" min="0">
                                </div>

                                <div class="form-group col-md-6">
                                    <label class="form-control-label" for="meta_title">{{ __('messages.meta_title') }}</label>
                                    <input type="text" name="meta_title" id="meta_title" class="form-control" placeholder="{{ __('messages.meta_title') }}" value="{{ old('meta_title') }}">
                                </div>

                                <div class="form-group col-md-6">
                                    <label class="form-control-label" for="meta_description">{{ __('messages.meta_description') }}</label>
                                    <textarea name="meta_description" id="meta_description" class="form-control" placeholder="{{ __('messages.meta_description') }}" rows="2">{{ old('meta_description') }}</textarea>
                                </div>
                            </div>


                            <div class="row">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-md btn-primary float-right">{{ __('messages.save') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @section('bottom_script')
    <script>
        $(document).ready(function() {
            $('.select2js').select2();

            // File input labels
            $('.custom-file-input').on('change', function() {
                let fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').html(fileName);
            });
        });
    </script>
    @endsection
</x-master-layout>
