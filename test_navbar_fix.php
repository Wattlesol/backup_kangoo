<?php

/**
 * Test Navbar Background Fix
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🎨 TESTING NAVBAR BACKGROUND FIX\n";
echo "================================\n\n";

try {
    echo "🧪 TESTING CSS GENERATION:\n";
    echo "==========================\n";
    
    // Create DynamicCssController instance
    $controller = new \App\Http\Controllers\DynamicCssController();
    
    // Test admin dashboard CSS
    $request = new \Illuminate\Http\Request();
    $request->merge([
        'role' => 'admin',
        'theme' => 'light',
        'v' => time()
    ]);
    
    $response = $controller->generateThemeCss($request);
    $css = $response->getContent();
    
    echo "📊 ADMIN DASHBOARD CSS:\n";
    echo "=======================\n";
    
    // Check for navbar background fix
    if (strpos($css, 'body .iq-navbar') !== false && strpos($css, 'var(--bs-body-bg)') !== false) {
        echo "  ✅ Navbar background fix found\n";
    } else {
        echo "  ❌ Navbar background fix NOT found\n";
    }
    
    if (strpos($css, '.iq-navbar.navs-color') !== false) {
        echo "  ✅ Navbar navs-color override found\n";
    } else {
        echo "  ❌ Navbar navs-color override NOT found\n";
    }
    
    // Test landing page CSS
    $landingResponse = $controller->generateLandingCss($request);
    $landingCss = $landingResponse->getContent();
    
    echo "\n🏠 LANDING PAGE CSS:\n";
    echo "====================\n";
    
    // Check for navbar background fix in landing CSS
    if (strpos($landingCss, 'body .iq-navbar') !== false && strpos($landingCss, 'var(--bs-body-bg)') !== false) {
        echo "  ✅ Landing navbar background fix found\n";
    } else {
        echo "  ❌ Landing navbar background fix NOT found\n";
    }
    
    if (strpos($landingCss, '.iq-navbar.navs-color') !== false) {
        echo "  ✅ Landing navbar navs-color override found\n";
    } else {
        echo "  ❌ Landing navbar navs-color override NOT found\n";
    }
    
    echo "\n📄 NAVBAR CSS RULES GENERATED:\n";
    echo "===============================\n";
    
    // Extract navbar-related CSS rules
    preg_match_all('/\.iq-navbar[^{]*\{[^}]*\}/s', $css, $matches);
    foreach ($matches[0] as $rule) {
        echo "  " . trim($rule) . "\n\n";
    }
    
    echo "\n🎯 EXPECTED BEHAVIOR:\n";
    echo "=====================\n";
    echo "  • Navbar should use var(--bs-body-bg) for background\n";
    echo "  • Light theme: navbar background = white\n";
    echo "  • Dark theme: navbar background = dark\n";
    echo "  • Should override .iq-navbar.navs-color primary color\n";
    echo "  • Should work for all user roles (admin, provider, handyman, customer)\n";
    
    echo "\n🎉 NAVBAR FIX TEST COMPLETED!\n";
    echo "=============================\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
