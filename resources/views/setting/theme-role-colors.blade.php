{{ Form::open(['method' => 'POST', 'route' => ['theme.update-colors'], 'data-toggle' => 'validator', 'id' => 'role-colors-form']) }}

<div class="row">
    <div class="col-lg-12">
        <h5 class="mb-3">{{ __('messages.role_based_colors') }}</h5>
        <p class="text-muted mb-4">{{ __('messages.role_colors_description') }}</p>
        
        <div class="row">
            @if(isset($roleColorsFormatted) && count($roleColorsFormatted) > 0)
                @foreach($roleColorsFormatted as $roleName => $roleData)
            <div class="col-md-6 col-lg-6 mb-4">
                <div class="card border">
                    <div class="card-header">
                        <h6 class="mb-0 d-flex align-items-center">
                            <i class="fas {{ $roleData['icon'] }} me-2"></i>
                            {{ $roleData['display_name'] }}
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
                                               value="{{ $roleData['light'] }}"
                                               data-group="role_colors"
                                               data-key="{{ $roleName }}_light"
                                               onchange="updateColorPreview(this)">
                                        <input type="text" 
                                               class="form-control" 
                                               value="{{ $roleData['light'] }}"
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
                                               value="{{ $roleData['dark'] }}"
                                               data-group="role_colors"
                                               data-key="{{ $roleName }}_dark"
                                               onchange="updateColorPreview(this)">
                                        <input type="text" 
                                               class="form-control" 
                                               value="{{ $roleData['dark'] }}"
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
                                         style="width: 30px; height: 30px; background-color: {{ $roleData['light'] }}; border: 1px solid #ddd; border-radius: 4px;"
                                         title="Light Theme"></div>
                                    <div class="color-preview-box" 
                                         style="width: 30px; height: 30px; background-color: {{ $roleData['dark'] }}; border: 1px solid #ddd; border-radius: 4px;"
                                         title="Dark Theme"></div>
                                    <small class="text-muted ms-2">{{ __('messages.role_color_usage', ['role' => $roleData['display_name']]) }}</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Role-specific UI Elements Preview -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="role-preview-elements">
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="button" class="btn btn-sm" style="background-color: {{ $roleData['light'] }}; color: white; border: none;">
                                            {{ __('messages.sample_button') }}
                                        </button>
                                        <span class="badge" style="background-color: {{ $roleData['light'] }};">
                                            {{ __('messages.sample_badge') }}
                                        </span>
                                        <div class="progress" style="width: 100px; height: 20px;">
                                            <div class="progress-bar" style="background-color: {{ $roleData['light'] }}; width: 60%;"></div>
                                        </div>
                                    </div>
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
        
        <!-- Role Color Guidelines -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle me-2"></i>{{ __('messages.role_color_guidelines') }}</h6>
                    <ul class="mb-0">
                        <li>{{ __('messages.admin_color_usage') }}</li>
                        <li>{{ __('messages.provider_color_usage') }}</li>
                        <li>{{ __('messages.handyman_color_usage') }}</li>
                        <li>{{ __('messages.customer_color_usage') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{{ Form::close() }}

<script>
// Role colors specific JavaScript
$(document).ready(function() {
    // Initialize the role colors tab
    updateRoleColorsPreview();
});

function updateRoleColorsPreview() {
    // Update role-specific UI elements when colors change
    document.querySelectorAll('.role-color-input').forEach(input => {
        const card = input.closest('.card');
        const previewElements = card.querySelector('.role-preview-elements');
        
        if (previewElements && input.dataset.key.includes('light')) {
            const button = previewElements.querySelector('.btn');
            const badge = previewElements.querySelector('.badge');
            const progressBar = previewElements.querySelector('.progress-bar');
            
            if (button) button.style.backgroundColor = input.value;
            if (badge) badge.style.backgroundColor = input.value;
            if (progressBar) progressBar.style.backgroundColor = input.value;
        }
    });
}
</script>
