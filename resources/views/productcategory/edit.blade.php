<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? trans('messages.edit_form_title',['form' => trans('messages.product_category')]) }}</h5>
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
                        {{ Form::model($productCategory,['method' => 'POST','route'=>['productcategory.update', $productCategory->id], 'enctype'=>'multipart/form-data', 'data-toggle'=>"validator" ,'id'=>'productcategory'] ) }}
                        @method('PUT')
                        {{ Form::hidden('id') }}

                        <div class="row">
                            <div class="col-md-8">
                                <!-- Basic Information -->
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">{{ __('messages.basic_information') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="name" class="form-label">{{ __('messages.name') }} <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $productCategory->name) }}" required>
                                                    @error('name')
                                                        <div class="text-danger">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="slug" class="form-label">{{ __('messages.slug') }}</label>
                                                    <input type="text" class="form-control" id="slug" name="slug" value="{{ old('slug', $productCategory->slug) }}">
                                                    <small class="text-muted">{{ __('messages.slug_auto_generated') }}</small>
                                                    @error('slug')
                                                        <div class="text-danger">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="description" class="form-label">{{ __('messages.description') }}</label>
                                            <textarea class="form-control" id="description" name="description" rows="4">{{ old('description', $productCategory->description) }}</textarea>
                                            @error('description')
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="parent_id" class="form-label">{{ __('messages.parent_category') }}</label>
                                                    <select class="form-control" id="parent_id" name="parent_id">
                                                        <option value="">{{ __('messages.select_parent_category') }}</option>
                                                        @foreach($categories as $category)
                                                            @if($category->id != $productCategory->id)
                                                                <option value="{{ $category->id }}" {{ old('parent_id', $productCategory->parent_id) == $category->id ? 'selected' : '' }}>
                                                                    {{ $category->name }}
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                    @error('parent_id')
                                                        <div class="text-danger">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="sort_order" class="form-label">{{ __('messages.sort_order') }}</label>
                                                    <input type="number" class="form-control" id="sort_order" name="sort_order" value="{{ old('sort_order', $productCategory->sort_order) }}" min="0">
                                                    @error('sort_order')
                                                        <div class="text-danger">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- SEO Information -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5 class="card-title">{{ __('messages.seo_information') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="meta_title" class="form-label">{{ __('messages.meta_title') }}</label>
                                            <input type="text" class="form-control" id="meta_title" name="meta_title" value="{{ old('meta_title', $productCategory->meta_title) }}">
                                            @error('meta_title')
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="meta_description" class="form-label">{{ __('messages.meta_description') }}</label>
                                            <textarea class="form-control" id="meta_description" name="meta_description" rows="3">{{ old('meta_description', $productCategory->meta_description) }}</textarea>
                                            @error('meta_description')
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <!-- Category Image -->
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">{{ __('messages.category_image') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="image" class="form-label">{{ __('messages.upload_image') }}</label>
                                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                            <small class="text-muted">{{ __('messages.recommended_size') }}: 300x300px</small>
                                            @error('image')
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Current Image -->
                                        @if($productCategory->image)
                                            <div class="current-image mt-3">
                                                <label class="form-label">{{ __('messages.current_image') }}</label>
                                                <div>
                                                    <img src="{{ $productCategory->image_url }}" alt="{{ $productCategory->name }}" class="img-fluid rounded" style="max-height: 200px;">
                                                </div>
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1">
                                                    <label class="form-check-label" for="remove_image">
                                                        {{ __('messages.remove_current_image') }}
                                                    </label>
                                                </div>
                                            </div>
                                        @endif

                                        <div id="image-preview" class="mt-3" style="display: none;">
                                            <label class="form-label">{{ __('messages.new_image_preview') }}</label>
                                            <div>
                                                <img id="preview-img" src="" alt="Preview" class="img-fluid rounded" style="max-height: 200px;">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Settings -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5 class="card-title">{{ __('messages.settings') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="status" name="status" value="1" {{ old('status', $productCategory->status) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="status">
                                                    {{ __('messages.active') }}
                                                </label>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" {{ old('is_featured', $productCategory->is_featured) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_featured">
                                                    {{ __('messages.featured') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Statistics -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5 class="card-title">{{ __('messages.statistics') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-6">
                                                <div class="border-end">
                                                    <h4 class="text-primary">{{ $productCategory->products_count ?? 0 }}</h4>
                                                    <small class="text-muted">{{ __('messages.products') }}</small>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <h4 class="text-info">{{ $productCategory->subcategories_count ?? 0 }}</h4>
                                                <small class="text-muted">{{ __('messages.subcategories') }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="card mt-3">
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> {{ __('messages.update') }}
                                            </button>
                                            <a href="{{ route('productcategory.index') }}" class="btn btn-secondary">
                                                <i class="fas fa-times"></i> {{ __('messages.cancel') }}
                                            </a>
                                            @if(auth()->user()->can('product_category delete'))
                                                <button type="button" class="btn btn-danger" onclick="deleteCategory()">
                                                    <i class="fas fa-trash"></i> {{ __('messages.delete') }}
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('bottom_script')
<script>
$(document).ready(function() {
    // Auto-generate slug from name (only if slug is empty)
    $('#name').on('input', function() {
        const currentSlug = $('#slug').val();
        if (!currentSlug || currentSlug === '') {
            const name = $(this).val();
            const slug = name.toLowerCase()
                .replace(/[^a-z0-9 -]/g, '') // Remove invalid chars
                .replace(/\s+/g, '-') // Replace spaces with -
                .replace(/-+/g, '-') // Replace multiple - with single -
                .trim('-'); // Trim - from start and end
            $('#slug').val(slug);
        }
    });

    // Image preview
    $('#image').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#preview-img').attr('src', e.target.result);
                $('#image-preview').show();
            };
            reader.readAsDataURL(file);
        } else {
            $('#image-preview').hide();
        }
    });

    // Form validation
    $('#categoryForm').on('submit', function(e) {
        let isValid = true;

        // Check required fields
        if (!$('#name').val().trim()) {
            isValid = false;
            showAlert('error', '{{ __("messages.name_required") }}');
        }

        if (!isValid) {
            e.preventDefault();
        }
    });
});

function deleteCategory() {
    if (confirm('{{ __("messages.confirm_delete_category") }}')) {
        $.ajax({
            url: '{{ route("productcategory.destroy", $productCategory->id) }}',
            type: 'POST',
            data: {
                _method: 'DELETE',
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.status) {
                    window.location.href = '{{ route("productcategory.index") }}';
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function() {
                showAlert('error', '{{ __("messages.something_went_wrong") }}');
            }
        });
    }
}

function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

    $('body').prepend(alertHtml);

    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}
</script>
@endsection
