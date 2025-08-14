{{ Form::model($themeColors, ['method' => 'POST', 'route' => ['theme.update-colors'], 'data-toggle' => 'validator', 'id' => 'theme-colors-form']) }}

@csrf
{{ Form::hidden('page', $page, array('placeholder' => 'id','class' => 'form-control')) }}

                        <div class="row">
                            <!-- Brand Colors Section -->
                            <div class="col-lg-12">
                                <div class="form-group">
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
                                                                               name="brand_colors[{{ $colorName }}_light]"
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
                                                                               name="brand_colors[{{ $colorName }}_dark]"
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

                            <!-- Role Colors Section -->
                            <div class="col-lg-12 mt-4">
                                <div class="form-group">
                                    <h5 class="mb-3">{{ __('messages.role_based_colors') }}</h5>
                                    <p class="text-muted mb-4">{{ __('messages.role_colors_description') }}</p>

                                    <div class="row">
                                        @if(isset($roleColorsFormatted) && count($roleColorsFormatted) > 0)
                                            @foreach($roleColorsFormatted as $roleName => $roleData)
                                            <div class="col-md-6 col-lg-6 mb-4">
                                                <div class="card border">
                                                    <div class="card-header">
                                                        <h6 class="mb-0 d-flex align-items-center">
                                                            <i class="fas {{ $roleData['icon'] ?? 'fa-user' }} me-2"></i>
                                                            {{ $roleData['display_name'] ?? ucfirst($roleName) }}
                                                        </h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">{{ __('messages.light_theme') }}</label>
                                                                    <div class="input-group">
                                                                        <input type="color"
                                                                               class="form-control form-control-color role-color-input"
                                                                               name="role_colors[{{ $roleName }}_light]"
                                                                               value="{{ $roleData['light'] ?? '#000000' }}"
                                                                               data-group="role_colors"
                                                                               data-key="{{ $roleName }}_light"
                                                                               onchange="updateColorPreview(this)">
                                                                        <input type="text"
                                                                               class="form-control"
                                                                               value="{{ $roleData['light'] ?? '#000000' }}"
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
                                                                               class="form-control form-control-color role-color-input"
                                                                               name="role_colors[{{ $roleName }}_dark]"
                                                                               value="{{ $roleData['dark'] ?? '#000000' }}"
                                                                               data-group="role_colors"
                                                                               data-key="{{ $roleName }}_dark"
                                                                               onchange="updateColorPreview(this)">
                                                                        <input type="text"
                                                                               class="form-control"
                                                                               value="{{ $roleData['dark'] ?? '#000000' }}"
                                                                               onchange="updateColorFromText(this)"
                                                                               style="max-width: 80px; font-size: 12px;">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Color Preview -->
                                                        <div class="row mt-2">
                                                            <div class="col-12">
                                                                <div class="d-flex gap-2 align-items-center">
                                                                    <div class="color-preview-box"
                                                                         style="width: 30px; height: 30px; background-color: {{ $roleData['light'] ?? '#000000' }}; border: 1px solid #ddd; border-radius: 4px;"
                                                                         title="Light Theme"></div>
                                                                    <div class="color-preview-box"
                                                                         style="width: 30px; height: 30px; background-color: {{ $roleData['dark'] ?? '#000000' }}; border: 1px solid #ddd; border-radius: 4px;"
                                                                         title="Dark Theme"></div>
                                                                    <small class="text-muted ms-2">{{ __('messages.role_color_usage', ['role' => $roleData['display_name'] ?? ucfirst($roleName)]) }}</small>
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
                                                    {{ __('messages.no_role_colors_found') }}.
                                                    <button type="button" class="btn btn-sm btn-primary ms-2" onclick="createDefaultColors()">
                                                        {{ __('messages.create_default_colors') }}
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Save Button -->
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <div class="col-md-offset-3 col-sm-12">
                                        <button type="submit" class="btn btn-md btn-primary float-md-right">
                                            <i class="fas fa-save me-2"></i>{{ __('messages.save') }}
                                        </button>
                                    </div>
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

<script>
// Initialize on page load
$(document).ready(function() {
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Initialize color previews
    updateAllPreviews();

    // Handle form submission with AJAX
    $('#theme-colors-form').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();

        // Debug: Log form data
        const formData = form.serialize();
        console.log('Form data being sent:', formData);

        // Show loading state
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                console.log('Success response:', response);
                if (response.success) {
                    // Show success notification
                    if (typeof Snackbar !== 'undefined') {
                        Snackbar.show({
                            text: response.message,
                            pos: 'bottom-center',
                            backgroundColor: '#28a745'
                        });
                    } else {
                        alert('Colors updated successfully!');
                    }
                } else {
                    // Show error notification
                    const errorMsg = response.message || 'An error occurred';
                    if (typeof Snackbar !== 'undefined') {
                        Snackbar.show({
                            text: errorMsg,
                            pos: 'bottom-center',
                            backgroundColor: '#dc3545',
                            actionTextColor: 'white'
                        });
                    } else {
                        alert('Error: ' + errorMsg);
                    }
                }
            },
            error: function(xhr) {
                console.log('Error response:', xhr);
                // Show error notification
                const errorMessage = xhr.responseJSON?.message || 'An error occurred while saving';
                if (typeof Snackbar !== 'undefined') {
                    Snackbar.show({
                        text: errorMessage,
                        pos: 'bottom-center',
                        backgroundColor: '#dc3545',
                        actionTextColor: 'white'
                    });
                } else {
                    alert('Error: ' + errorMessage);
                }
            },
            complete: function() {
                // Restore button state
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});

// Global functions for color management
function updateColorPreview(input) {
    const value = input.value;

    // Update the corresponding text input
    const textInput = input.parentElement.querySelector('input[type="text"]');
    if (textInput) {
        textInput.value = value;
    }

    // Update preview boxes
    updatePreviewBoxes(input);
}

function updateColorFromText(input) {
    const colorInput = input.parentElement.querySelector('input[type="color"]');
    if (colorInput && isValidHexColor(input.value)) {
        colorInput.value = input.value;
        updatePreviewBoxes(colorInput);
    }
}

function updatePreviewBoxes(input) {
    const card = input.closest('.card');
    const previewBoxes = card.querySelectorAll('.color-preview-box');
    const isLight = input.dataset.key.includes('light');

    if (previewBoxes.length >= 2) {
        const targetBox = isLight ? previewBoxes[0] : previewBoxes[1];
        targetBox.style.backgroundColor = input.value;
    }
}

function isValidHexColor(hex) {
    return /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(hex);
}

function updateAllPreviews() {
    document.querySelectorAll('.brand-color-input, .role-color-input').forEach(input => {
        updatePreviewBoxes(input);
    });
}

function resetToDefaults() {
    if (confirm('{{ __("messages.confirm_reset_colors") }}')) {
        window.location.href = '{{ route("theme.reset-colors") }}';
    }
}

function showAddBrandColorModal() {
    $('#addBrandColorModal').modal('show');
}

function addBrandColor() {
    const form = document.getElementById('addBrandColorForm');
    const formData = new FormData(form);

    $.post('{{ route("theme.add-brand-color") }}', {
        _token: '{{ csrf_token() }}',
        color_name: formData.get('color_name'),
        light_color: formData.get('light_color'),
        dark_color: formData.get('dark_color')
    }).done(function(response) {
        if (response.success) {
            $('#addBrandColorModal').modal('hide');
            form.reset();
            showMessage(response.message || 'Brand color added successfully');
            setTimeout(() => location.reload(), 1500);
        } else {
            showErrorMessage(response.message || '{{ __("messages.error_adding_color") }}');
        }
    }).fail(function() {
        showErrorMessage('{{ __("messages.error_adding_color") }}');
    });
}

function deleteBrandColor(colorName) {
    if (confirm('{{ __("messages.confirm_delete_color") }}')) {
        $.post('{{ route("theme.delete-brand-color") }}', {
            _token: '{{ csrf_token() }}',
            color_name: colorName
        }).done(function(response) {
            if (response.success) {
                showMessage(response.message || 'Brand color deleted successfully');
                setTimeout(() => location.reload(), 1500);
            } else {
                showErrorMessage(response.message || '{{ __("messages.error_deleting_color") }}');
            }
        }).fail(function() {
            showErrorMessage('{{ __("messages.error_deleting_color") }}');
        });
    }
}

function createDefaultColors() {
    if (confirm('{{ __("messages.confirm_create_default_colors") }}')) {
        $.post('{{ route("theme.create-defaults") }}', {
            _token: '{{ csrf_token() }}'
        }).done(function(response) {
            if (response.success) {
                showMessage(response.message || 'Default colors created successfully');
                setTimeout(() => location.reload(), 1500);
            } else {
                showErrorMessage(response.message || '{{ __("messages.error_creating_defaults") }}');
            }
        }).fail(function() {
            showErrorMessage('{{ __("messages.error_creating_defaults") }}');
        });
    }
}

// Helper functions for consistent messaging
function showMessage(message) {
    Snackbar.show({
        text: message,
        pos: 'bottom-center',
        backgroundColor: '#28a745'
    });
}

function showErrorMessage(message) {
    Snackbar.show({
        text: message,
        pos: 'bottom-center',
        backgroundColor: '#dc3545',
        actionTextColor: 'white'
    });
}
</script>
