{{ Form::open(['method' => 'POST', 'route' => ['theme.update-colors'], 'data-toggle' => 'validator', 'id' => 'brand-colors-form']) }}

<div class="row">
    <div class="col-lg-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5>{{ __('messages.brand_colors_for_landing_pages') }}</h5>
            <button type="button" class="btn btn-sm btn-success" onclick="showAddBrandColorModal()">
                <i class="fas fa-plus"></i> {{ __('messages.add_brand_color') }}
            </button>
        </div>
        
        <div class="row" id="brand-colors-container">
            @if(isset($brandColorsFormatted) && count($brandColorsFormatted) > 0)
                @foreach($brandColorsFormatted as $colorName => $colorData)
                <div class="col-md-6 col-lg-4 mb-4" data-color-name="{{ $colorName }}">
                    <div class="card border">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 text-capitalize">{{ $colorData['name'] ?? ucfirst($colorName) }}</h6>
                            @if(!in_array($colorName, ['yellow', 'red', 'green', 'blue']))
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteBrandColor('{{ $colorName }}')">
                                <i class="fas fa-trash"></i>
                            </button>
                            @endif
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="form-label">{{ __('messages.light_theme') }}</label>
                                        <div class="input-group">
                                            <input type="color"
                                                   class="form-control form-control-color brand-color-input"
                                                   value="{{ $colorData['light'] ?? '#000000' }}"
                                                   data-group="brand_colors"
                                                   data-key="{{ $colorName }}_light"
                                                   onchange="updateColorPreview(this)">
                                            <input type="text"
                                                   class="form-control"
                                                   value="{{ $colorData['light'] ?? '#000000' }}"
                                                   onchange="updateColorFromText(this)"
                                                   style="max-width: 80px; font-size: 12px;">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="form-label">{{ __('messages.dark_theme') }}</label>
                                        <div class="input-group">
                                            <input type="color"
                                                   class="form-control form-control-color brand-color-input"
                                                   value="{{ $colorData['dark'] ?? '#000000' }}"
                                                   data-group="brand_colors"
                                                   data-key="{{ $colorName }}_dark"
                                                   onchange="updateColorPreview(this)">
                                            <input type="text"
                                                   class="form-control"
                                                   value="{{ $colorData['dark'] ?? '#000000' }}"
                                                   onchange="updateColorFromText(this)"
                                                   style="max-width: 80px; font-size: 12px;">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Color Preview -->
                            <div class="row mt-2">
                                <div class="col-12">
                                    <div class="d-flex gap-2">
                                        <div class="color-preview-box"
                                             style="width: 30px; height: 30px; background-color: {{ $colorData['light'] ?? '#000000' }}; border: 1px solid #ddd; border-radius: 4px;"
                                             title="Light Theme"></div>
                                        <div class="color-preview-box"
                                             style="width: 30px; height: 30px; background-color: {{ $colorData['dark'] ?? '#000000' }}; border: 1px solid #ddd; border-radius: 4px;"
                                             title="Dark Theme"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            @else
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        {{ __('messages.no_brand_colors_found') }}.
                        <button type="button" class="btn btn-sm btn-primary ms-2" onclick="createDefaultColors()">
                            {{ __('messages.create_default_colors') }}
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{ Form::close() }}

<!-- Add Brand Color Modal -->
<div class="modal fade" id="addBrandColorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('messages.add_brand_color') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addBrandColorForm">
                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('messages.color_name') }}</label>
                        <input type="text" class="form-control" name="color_name" placeholder="e.g., purple, orange" required>
                        <small class="form-text text-muted">Use lowercase letters only, no spaces or special characters.</small>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">{{ __('messages.light_theme_color') }}</label>
                                <input type="color" class="form-control form-control-color" name="light_color" value="#000000" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">{{ __('messages.dark_theme_color') }}</label>
                                <input type="color" class="form-control form-control-color" name="dark_color" value="#000000" required>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                <button type="button" class="btn btn-primary" onclick="addBrandColor()">{{ __('messages.add_color') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
// Brand colors specific JavaScript
$(document).ready(function() {
    // Initialize the brand colors tab
    updateBrandColorsPreview();
});

function updateBrandColorsPreview() {
    // Update color preview boxes when colors change
    document.querySelectorAll('.brand-color-input').forEach(input => {
        const colorPreviewBox = input.closest('.card-body').querySelector('.color-preview-box');
        if (colorPreviewBox && input.dataset.key.includes('light')) {
            colorPreviewBox.style.backgroundColor = input.value;
        }
    });
}

function createDefaultColors() {
    if (confirm('{{ __("messages.confirm_create_default_colors") }}')) {
        $.post('{{ route("theme.create-defaults") }}', {
            _token: '{{ csrf_token() }}'
        }).done(function(response) {
            if (response.success) {
                // Reload the brand colors tab
                loadTabContent('{{ route("theme.brand-colors") }}');
            } else {
                alert(response.message || '{{ __("messages.error_creating_defaults") }}');
            }
        }).fail(function() {
            alert('{{ __("messages.error_creating_defaults") }}');
        });
    }
}
</script>
