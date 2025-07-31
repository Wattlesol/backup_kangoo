<x-master-layout>
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <div class="header-title">
                        <h4 class="card-title">Role-Based Theming Demo</h4>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Current Theme Info -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <h5>Current Theme Information</h5>
                                <p><strong>Role:</strong> {{ $userTheme['role'] ?? 'guest' }}</p>
                                <p><strong>Primary Color (Light):</strong> <span style="color: {{ $userTheme['primary_light'] ?? '#4A75FB' }};">{{ $userTheme['primary_light'] ?? '#4A75FB' }}</span></p>
                                <p><strong>Primary Color (Dark):</strong> <span style="color: {{ $userTheme['primary_dark'] ?? '#004CB2' }};">{{ $userTheme['primary_dark'] ?? '#004CB2' }}</span></p>
                                <p><strong>Theme Class:</strong> {{ $userTheme['theme_class'] ?? 'theme-customer' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Rotating Card Colors Demo -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5>Rotating Card Colors Demo</h5>
                            <p>These cards demonstrate the rotating brand color system:</p>
                        </div>
                    </div>

                    <!-- Method 1: Using Blade Component -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6>Method 1: Using Blade Component</h6>
                        </div>
                        @for($i = 0; $i < 8; $i++)
                            <div class="col-lg-3 col-md-6 mb-3">
                                <x-rotating-card :index="$i" class="h-100">
                                    <h6 class="card-title">Card {{ $i + 1 }}</h6>
                                    <p class="card-text">This card uses index {{ $i }} and shows the rotating color system.</p>
                                    <small class="text-muted">Color: {{ ['Yellow', 'Red', 'Green', 'Blue'][$i % 4] }}</small>
                                </x-rotating-card>
                            </div>
                        @endfor
                    </div>

                    <!-- Method 2: Manual Implementation -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6>Method 2: Manual Implementation with data-card-index</h6>
                        </div>
                        @for($i = 0; $i < 4; $i++)
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="card h-100" data-card-index="{{ $i }}">
                                    <div class="card-color-element p-3 text-white">
                                        <h6 class="card-title mb-0">Manual Card {{ $i + 1 }}</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">This card uses manual implementation with data-card-index="{{ $i }}".</p>
                                        <small class="text-muted">Auto-colored header</small>
                                    </div>
                                </div>
                            </div>
                        @endfor
                    </div>

                    <!-- Method 3: CSS Classes -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6>Method 3: Using CSS Classes</h6>
                        </div>
                        @foreach(['yellow', 'red', 'green', 'blue'] as $color)
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="brand-color-{{ $color }} p-3 text-white">
                                        <h6 class="card-title mb-0">{{ ucfirst($color) }} Card</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">This card uses the brand-color-{{ $color }} CSS class.</p>
                                        <small class="text-muted">Static {{ $color }} color</small>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Brand Colors Reference -->
                    <div class="row">
                        <div class="col-12">
                            <h5>Brand Colors Reference</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Color</th>
                                            <th>Light Mode</th>
                                            <th>Dark Mode</th>
                                            <th>CSS Class</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($brandColors as $name => $colors)
                                            <tr>
                                                <td><strong>{{ ucfirst($name) }}</strong></td>
                                                <td>
                                                    <span class="d-inline-block" style="width: 20px; height: 20px; background-color: {{ $colors['light'] }}; border: 1px solid #ccc;"></span>
                                                    {{ $colors['light'] }}
                                                </td>
                                                <td>
                                                    <span class="d-inline-block" style="width: 20px; height: 20px; background-color: {{ $colors['dark'] }}; border: 1px solid #ccc;"></span>
                                                    {{ $colors['dark'] }}
                                                </td>
                                                <td><code>.brand-color-{{ $name }}</code></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- JavaScript Demo -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>JavaScript Integration Demo</h5>
                            <button type="button" class="btn btn-primary" onclick="showThemeInfo()">Show Current Theme Info</button>
                            <button type="button" class="btn btn-secondary" onclick="reapplyColors()">Reapply Card Colors</button>
                            <div id="theme-info" class="mt-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showThemeInfo() {
    const colors = window.RoleTheming.getCurrentRoleColors();
    const info = document.getElementById('theme-info');
    info.innerHTML = `
        <div class="alert alert-success">
            <h6>JavaScript Theme Info:</h6>
            <p><strong>Role:</strong> ${colors.role}</p>
            <p><strong>Primary Color:</strong> <span style="color: ${colors.primary};">${colors.primary}</span></p>
            <p><strong>Dark Mode:</strong> ${colors.isDark ? 'Yes' : 'No'}</p>
        </div>
    `;
}

function reapplyColors() {
    window.RoleTheming.applyRotatingCardColors();
    alert('Card colors reapplied!');
}
</script>
</x-master-layout>
