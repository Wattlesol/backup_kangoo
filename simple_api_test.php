<?php

/**
 * Simple API Test
 *
 * This script tests basic API functionality
 */

$baseUrl = 'http://localhost:8000';

function apiCall($method, $endpoint, $data = []) {
    global $baseUrl;

    $url = $baseUrl . $endpoint;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);

    if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "🔗 $method $endpoint - HTTP $httpCode\n";
    echo "📄 Response: " . substr($response, 0, 200) . (strlen($response) > 200 ? '...' : '') . "\n\n";

    return json_decode($response, true);
}

echo "🧪 Simple API Test\n";
echo "=" . str_repeat("=", 30) . "\n\n";

// Test 1: Login with admin user
echo "📋 Test 1: Admin Login\n";
$loginResponse = apiCall('POST', '/api/login', [
    'email' => 'admin@test.com',
    'password' => 'password123'
]);

if (isset($loginResponse['data']['api_token'])) {
    $adminToken = $loginResponse['data']['api_token'];
    echo "✅ Admin login successful!\n\n";

    // Test 2: Get product categories
    echo "📋 Test 2: Get Product Categories\n";
    $categories = apiCall('GET', '/api/product-categories');

    // Test 3: Create a product category
    echo "📋 Test 3: Create Product Category\n";
    $categoryData = [
        'name' => 'Test Electronics',
        'description' => 'Test category for electronics',
        'is_featured' => true,
        'status' => 1
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/ecommerce/product-categories');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $adminToken
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($categoryData));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "🔗 POST /api/ecommerce/product-categories - HTTP $httpCode\n";
    echo "📄 Response: " . substr($response, 0, 200) . (strlen($response) > 200 ? '...' : '') . "\n\n";

    if ($httpCode == 200 || $httpCode == 201) {
        echo "✅ Category creation successful!\n";
    } else {
        echo "❌ Category creation failed!\n";
    }

} else {
    echo "❌ Admin login failed!\n";
    echo "Response: " . json_encode($loginResponse) . "\n";
}

echo "\n🎉 Simple API test completed!\n";
