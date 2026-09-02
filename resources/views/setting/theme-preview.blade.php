<div class="row">
    <div class="col-lg-12">
        <h5 class="mb-3">{{ __('messages.theme_preview') }}</h5>
        <p class="text-muted mb-4">{{ __('messages.preview_description') }}</p>
        
        <!-- Theme Toggle -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="previewThemeToggle" onchange="togglePreviewTheme()">
                <label class="form-check-label" for="previewThemeToggle">
                    <i class="fas fa-moon me-1"></i> {{ __('messages.dark_theme_preview') }}
                </label>
            </div>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="previewRole('admin')">
                    <i class="fas fa-user-shield"></i> {{ __('messages.admin') }}
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="previewRole('provider')">
                    <i class="fas fa-store"></i> {{ __('messages.provider') }}
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="previewRole('handyman')">
                    <i class="fas fa-tools"></i> {{ __('messages.handyman') }}
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm active" onclick="previewRole('customer')">
                    <i class="fas fa-user"></i> {{ __('messages.customer') }}
                </button>
            </div>
        </div>
        
        <!-- Preview Container -->
        <div id="theme-preview-container" class="border rounded p-4" style="background-color: #f8f9fa;">
            
            <!-- Brand Colors Preview -->
            <div class="mb-4">
                <h6>{{ __('messages.brand_colors_preview') }}</h6>
                <div class="row">
                    @foreach($brandColorsFormatted ?? [] as $colorName => $colorData)
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center p-3" style="background: linear-gradient(135deg, {{ $colorData['light'] }}, {{ $colorData['dark'] }});">
                                <h6 class="text-white mb-0 text-capitalize">{{ $colorData['name'] }}</h6>
                                <small class="text-white-50">{{ __('messages.brand_color') }}</small>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            
            <!-- Role-based UI Preview -->
            <div class="mb-4">
                <h6>{{ __('messages.role_interface_preview') }}</h6>
                <div id="role-preview-content">
                    <!-- This will be populated by JavaScript based on selected role -->
                </div>
            </div>
            
            <!-- Sample Components -->
            <div class="mb-4">
                <h6>{{ __('messages.sample_components') }}</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">{{ __('messages.sample_card') }}</h6>
                            </div>
                            <div class="card-body">
                                <p class="card-text">{{ __('messages.sample_card_content') }}</p>
                                <div class="d-flex gap-2 mb-3">
                                    <button type="button" class="btn btn-primary btn-sm preview-btn-primary">
                                        {{ __('messages.primary_button') }}
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-sm preview-btn-outline">
                                        {{ __('messages.outline_button') }}
                                    </button>
                                </div>
                                <div class="d-flex gap-2 mb-3">
                                    <span class="badge preview-badge-primary">{{ __('messages.primary_badge') }}</span>
                                    <span class="badge preview-badge-secondary">{{ __('messages.secondary_badge') }}</span>
                                </div>
                                <div class="progress mb-2" style="height: 8px;">
                                    <div class="progress-bar preview-progress-bar" style="width: 75%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">{{ __('messages.navigation_preview') }}</h6>
                            </div>
                            <div class="card-body">
                                <nav class="nav nav-pills flex-column">
                                    <a class="nav-link preview-nav-link active" href="#">
                                        <i class="fas fa-home me-2"></i> {{ __('messages.dashboard') }}
                                    </a>
                                    <a class="nav-link preview-nav-link" href="#">
                                        <i class="fas fa-users me-2"></i> {{ __('messages.users') }}
                                    </a>
                                    <a class="nav-link preview-nav-link" href="#">
                                        <i class="fas fa-cog me-2"></i> {{ __('messages.settings') }}
                                    </a>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Color Palette Display -->
            <div class="mb-4">
                <h6>{{ __('messages.current_color_palette') }}</h6>
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="small">{{ __('messages.light_theme') }}</h6>
                        <div class="d-flex flex-wrap gap-2" id="light-palette">
                            <!-- Populated by JavaScript -->
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="small">{{ __('messages.dark_theme') }}</h6>
                        <div class="d-flex flex-wrap gap-2" id="dark-palette">
                            <!-- Populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentPreviewRole = 'customer';
let isDarkPreview = false;

$(document).ready(function() {
    initializePreview();
    updateColorPalette();
});

function initializePreview() {
    previewRole('customer');
    updatePreviewColors();
}

function togglePreviewTheme() {
    isDarkPreview = document.getElementById('previewThemeToggle').checked;
    const container = document.getElementById('theme-preview-container');
    
    if (isDarkPreview) {
        container.style.backgroundColor = '#2c3e50';
        container.style.color = '#ecf0f1';
    } else {
        container.style.backgroundColor = '#f8f9fa';
        container.style.color = '#212529';
    }
    
    updatePreviewColors();
}

function previewRole(role) {
    currentPreviewRole = role;
    
    // Update active button
    document.querySelectorAll('.btn-group .btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Update role preview content
    const roleContent = document.getElementById('role-preview-content');
    const roleColors = @json($roleColorsFormatted ?? []);
    const roleData = roleColors[role];
    
    if (roleData) {
        const color = isDarkPreview ? roleData.dark : roleData.light;
        roleContent.innerHTML = `
            <div class="alert" style="background-color: ${color}20; border-color: ${color}; color: ${color};">
                <i class="${roleData.icon} me-2"></i>
                <strong>${roleData.display_name} Interface Preview</strong>
                <p class="mb-0 mt-2">This shows how the interface would look for ${roleData.display_name.toLowerCase()} users with the selected color scheme.</p>
            </div>
        `;
    }
    
    updatePreviewColors();
}

function updatePreviewColors() {
    const roleColors = @json($roleColorsFormatted ?? []);
    const roleData = roleColors[currentPreviewRole];
    
    if (roleData) {
        const color = isDarkPreview ? roleData.dark : roleData.light;
        
        // Update buttons
        document.querySelectorAll('.preview-btn-primary').forEach(btn => {
            btn.style.backgroundColor = color;
            btn.style.borderColor = color;
        });
        
        document.querySelectorAll('.preview-btn-outline').forEach(btn => {
            btn.style.color = color;
            btn.style.borderColor = color;
        });
        
        // Update badges
        document.querySelectorAll('.preview-badge-primary').forEach(badge => {
            badge.style.backgroundColor = color;
        });
        
        // Update progress bars
        document.querySelectorAll('.preview-progress-bar').forEach(bar => {
            bar.style.backgroundColor = color;
        });
        
        // Update navigation links
        document.querySelectorAll('.preview-nav-link.active').forEach(link => {
            link.style.backgroundColor = color;
            link.style.borderColor = color;
        });
    }
}

function updateColorPalette() {
    const brandColors = @json($brandColorsFormatted ?? []);
    const roleColors = @json($roleColorsFormatted ?? []);
    
    const lightPalette = document.getElementById('light-palette');
    const darkPalette = document.getElementById('dark-palette');
    
    // Clear existing
    lightPalette.innerHTML = '';
    darkPalette.innerHTML = '';
    
    // Add brand colors
    Object.entries(brandColors).forEach(([name, data]) => {
        lightPalette.innerHTML += `
            <div class="text-center">
                <div style="width: 40px; height: 40px; background-color: ${data.light}; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 4px;"></div>
                <small class="text-muted">${data.name}</small>
            </div>
        `;
        
        darkPalette.innerHTML += `
            <div class="text-center">
                <div style="width: 40px; height: 40px; background-color: ${data.dark}; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 4px;"></div>
                <small class="text-muted">${data.name}</small>
            </div>
        `;
    });
    
    // Add role colors
    Object.entries(roleColors).forEach(([name, data]) => {
        lightPalette.innerHTML += `
            <div class="text-center">
                <div style="width: 40px; height: 40px; background-color: ${data.light}; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 4px;"></div>
                <small class="text-muted">${data.display_name}</small>
            </div>
        `;
        
        darkPalette.innerHTML += `
            <div class="text-center">
                <div style="width: 40px; height: 40px; background-color: ${data.dark}; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 4px;"></div>
                <small class="text-muted">${data.display_name}</small>
            </div>
        `;
    });
}
</script>
