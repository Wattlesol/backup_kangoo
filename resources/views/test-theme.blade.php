<x-master-layout>
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">🎨 Theme Testing Page</h4>
                </div>
                <div class="card-body">
                    <!-- Current Theme Info -->
                    <div class="alert alert-info">
                        <h5>Current User & Theme Information</h5>
                        <p><strong>User Role:</strong> {{ $userTheme['role'] ?? 'guest' }}</p>
                        <p><strong>Theme Class:</strong> {{ $userTheme['theme_class'] ?? 'theme-customer' }}</p>
                        <p><strong>Primary Color (Light):</strong> 
                            <span style="color: {{ $userTheme['primary_light'] ?? '#4A75FB' }}; font-weight: bold;">
                                {{ $userTheme['primary_light'] ?? '#4A75FB' }}
                            </span>
                        </p>
                        <p><strong>Primary Color (Dark):</strong> 
                            <span style="color: {{ $userTheme['primary_dark'] ?? '#004CB2' }}; font-weight: bold;">
                                {{ $userTheme['primary_dark'] ?? '#004CB2' }}
                            </span>
                        </p>
                    </div>

                    <!-- Test Elements -->
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Primary Color Test Elements</h5>
                            
                            <!-- Button using primary color -->
                            <button class="btn btn-primary mb-2">Primary Button</button><br>
                            
                            <!-- Link using primary color -->
                            <a href="#" class="text-primary mb-2 d-block">Primary Link</a>
                            
                            <!-- Badge using primary color -->
                            <span class="badge bg-primary mb-2">Primary Badge</span><br>
                            
                            <!-- Custom element using CSS variable -->
                            <div style="background-color: var(--c1); color: white; padding: 10px; margin: 10px 0; border-radius: 5px;">
                                Element using --c1 CSS variable
                            </div>
                            
                            <!-- Progress bar -->
                            <div class="progress mb-3">
                                <div class="progress-bar" role="progressbar" style="width: 75%; background-color: var(--c1);" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100">75%</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h5>Debug Information</h5>
                            <div id="debug-info" class="alert alert-secondary">
                                <p>Loading debug information...</p>
                            </div>
                            
                            <button onclick="forceApplyTheme()" class="btn btn-warning">Force Apply Theme</button>
                            <button onclick="refreshDebugInfo()" class="btn btn-info">Refresh Debug Info</button>
                        </div>
                    </div>

                    <!-- Expected Colors Reference -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Expected Colors by Role</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Role</th>
                                            <th>Light Color</th>
                                            <th>Dark Color</th>
                                            <th>Theme Class</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>Customer</strong></td>
                                            <td><span style="color: #4A75FB; font-weight: bold;">#4A75FB</span></td>
                                            <td><span style="color: #004CB2; font-weight: bold;">#004CB2</span></td>
                                            <td>theme-customer</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Admin</strong></td>
                                            <td><span style="color: #5F60B9; font-weight: bold;">#5F60B9</span></td>
                                            <td><span style="color: #4153b3; font-weight: bold;">#4153b3</span></td>
                                            <td>theme-admin</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Handyman</strong></td>
                                            <td><span style="color: #2DB665; font-weight: bold;">#2DB665</span></td>
                                            <td><span style="color: #005F2D; font-weight: bold;">#005F2D</span></td>
                                            <td>theme-handyman</td>
                                        </tr>
                                        <tr style="background-color: #fff5f5;">
                                            <td><strong>Provider</strong></td>
                                            <td><span style="color: #EF5535; font-weight: bold;">#EF5535</span></td>
                                            <td><span style="color: #9B1F0B; font-weight: bold;">#9B1F0B</span></td>
                                            <td>theme-provider</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function refreshDebugInfo() {
    const debugDiv = document.getElementById('debug-info');
    const computedStyle = getComputedStyle(document.documentElement);
    const bodyComputedStyle = getComputedStyle(document.body);
    
    const c1Value = computedStyle.getPropertyValue('--c1').trim();
    const bodyC1Value = bodyComputedStyle.getPropertyValue('--c1').trim();
    
    debugDiv.innerHTML = `
        <h6>CSS Variables:</h6>
        <p><strong>HTML --c1:</strong> ${c1Value || 'Not set'}</p>
        <p><strong>Body --c1:</strong> ${bodyC1Value || 'Not set'}</p>
        
        <h6>Classes:</h6>
        <p><strong>Body classes:</strong> ${document.body.className}</p>
        <p><strong>HTML classes:</strong> ${document.documentElement.className}</p>
        
        <h6>JavaScript Variables:</h6>
        <p><strong>window.userRole:</strong> ${window.userRole || 'Not set'}</p>
        
        <h6>Theme Detection:</h6>
        <p><strong>Expected for {{ $userTheme['role'] ?? 'guest' }}:</strong> {{ $userTheme['primary_light'] ?? '#4A75FB' }}</p>
        <p><strong>Current --c1:</strong> ${c1Value}</p>
        <p><strong>Match:</strong> ${c1Value.includes('{{ $userTheme['primary_light'] ?? '#4A75FB' }}') ? '✅ YES' : '❌ NO'}</p>
    `;
}

function forceApplyTheme() {
    const expectedColor = '{{ $userTheme['primary_light'] ?? '#4A75FB' }}';
    const themeClass = '{{ $userTheme['theme_class'] ?? 'theme-customer' }}';
    
    // Force apply CSS variables
    document.documentElement.style.setProperty('--c1', expectedColor, 'important');
    document.body.style.setProperty('--c1', expectedColor, 'important');
    
    // Force apply theme class
    document.body.className = document.body.className.replace(/theme-\w+/g, '') + ' ' + themeClass;
    document.documentElement.className = document.documentElement.className.replace(/theme-\w+/g, '') + ' ' + themeClass;
    
    console.log('🔧 Force applied theme:', themeClass, 'with color:', expectedColor);
    
    // Refresh debug info
    setTimeout(refreshDebugInfo, 100);
    
    alert('Theme force-applied! Check the debug info and page elements.');
}

// Initialize debug info when page loads
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(refreshDebugInfo, 500);
});
</script>
</x-master-layout>
