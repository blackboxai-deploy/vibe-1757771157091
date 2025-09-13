<?php
/**
 * Test script for Mr.WordPress Tools Plugin
 * 
 * This script demonstrates how to interact with the plugin API
 * and validate its functionality.
 */

// Configuration
$site_url = 'http://localhost'; // Replace with actual site URL
$site_secret = 'your_site_secret_here'; // Replace with actual site secret

/**
 * Calculate HMAC signature
 */
function calculate_hmac_signature($timestamp, $body, $secret) {
    $message = $timestamp . "\n" . $body;
    return hash_hmac('sha256', $message, $secret);
}

/**
 * Make API request with HMAC authentication
 */
function make_api_request($endpoint, $body = '', $method = 'POST') {
    global $site_url, $site_secret;
    
    $timestamp = time();
    $signature = calculate_hmac_signature($timestamp, $body, $site_secret);
    
    $headers = [
        'Content-Type: application/json',
        'x-mrwp-timestamp: ' . $timestamp,
        'x-mrwp-signature: ' . $signature
    ];
    
    $url = rtrim($site_url, '/') . '/wp-json/mrwp/v1/' . ltrim($endpoint, '/');
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false, // For testing only
    ]);
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    
    return [
        'success' => empty($error),
        'http_code' => $http_code,
        'response' => $response,
        'error' => $error
    ];
}

echo "=== Mr.WordPress Tools Plugin Test Suite ===\n\n";

// Test 1: Public ping endpoint
echo "1. Testing ping endpoint (public)...\n";
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $site_url . '/wp-json/mrwp/v1/ping',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($http_code === 200) {
    echo "✅ Ping successful: " . $response . "\n\n";
} else {
    echo "❌ Ping failed: HTTP $http_code\n\n";
}

// Test 2: Status endpoint (authenticated)
echo "2. Testing status endpoint (authenticated)...\n";
$result = make_api_request('status', '{}');

if ($result['success'] && $result['http_code'] === 200) {
    echo "✅ Status request successful:\n";
    $data = json_decode($result['response'], true);
    if ($data) {
        foreach ($data as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            echo "   $key: $value\n";
        }
    }
    echo "\n";
} else {
    echo "❌ Status request failed: HTTP {$result['http_code']}\n";
    echo "   Error: {$result['error']}\n";
    echo "   Response: {$result['response']}\n\n";
}

// Test 3: Toggle maintenance mode
echo "3. Testing toggle maintenance action...\n";
$result = make_api_request('action', '{"action":"toggle_maintenance"}');

if ($result['success'] && $result['http_code'] === 200) {
    echo "✅ Toggle maintenance successful: " . $result['response'] . "\n\n";
} else {
    echo "❌ Toggle maintenance failed: HTTP {$result['http_code']}\n";
    echo "   Response: {$result['response']}\n\n";
}

// Test 4: Reset bypass code
echo "4. Testing reset bypass action...\n";
$result = make_api_request('action', '{"action":"reset_bypass"}');

if ($result['success'] && $result['http_code'] === 200) {
    echo "✅ Reset bypass successful: " . $result['response'] . "\n\n";
} else {
    echo "❌ Reset bypass failed: HTTP {$result['http_code']}\n";
    echo "   Response: {$result['response']}\n\n";
}

// Test 5: Toggle debug mode
echo "5. Testing toggle debug action...\n";
$result = make_api_request('action', '{"action":"toggle_debug"}');

if ($result['success'] && $result['http_code'] === 200) {
    echo "✅ Toggle debug successful: " . $result['response'] . "\n\n";
} else {
    echo "❌ Toggle debug failed: HTTP {$result['http_code']}\n";
    echo "   Response: {$result['response']}\n\n";
}

// Test 6: Send bypass email (if client email is configured)
echo "6. Testing send bypass email action...\n";
$result = make_api_request('action', '{"action":"send_bypass_email"}');

if ($result['success'] && $result['http_code'] === 200) {
    echo "✅ Send bypass email successful: " . $result['response'] . "\n\n";
} else {
    echo "❌ Send bypass email failed: HTTP {$result['http_code']}\n";
    echo "   Response: {$result['response']}\n\n";
}

// Test 7: Invalid action (should fail)
echo "7. Testing invalid action (should fail)...\n";
$result = make_api_request('action', '{"action":"invalid_action"}');

if ($result['http_code'] === 400) {
    echo "✅ Invalid action correctly rejected: " . $result['response'] . "\n\n";
} else {
    echo "❌ Invalid action not handled correctly: HTTP {$result['http_code']}\n";
    echo "   Response: {$result['response']}\n\n";
}

// Test 8: Authentication failure (invalid signature)
echo "8. Testing authentication failure...\n";
$timestamp = time();
$body = '{"action":"toggle_maintenance"}';
$invalid_signature = 'invalid_signature';

$headers = [
    'Content-Type: application/json',
    'x-mrwp-timestamp: ' . $timestamp,
    'x-mrwp-signature: ' . $invalid_signature
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $site_url . '/wp-json/mrwp/v1/action',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($http_code === 401) {
    echo "✅ Authentication correctly rejected invalid signature: " . $response . "\n\n";
} else {
    echo "❌ Authentication failure not handled correctly: HTTP $http_code\n";
    echo "   Response: $response\n\n";
}

echo "=== Test Suite Complete ===\n\n";

echo "Usage Instructions:\n";
echo "1. Replace \$site_url with your WordPress site URL\n";
echo "2. Replace \$site_secret with your actual site secret from the plugin settings\n";
echo "3. Ensure your WordPress site has the Mr.WordPress Tools plugin activated\n";
echo "4. Run this script: php test-plugin.php\n\n";

echo "To get your site secret:\n";
echo "1. Log into your WordPress admin\n";
echo "2. Go to Settings > Mr.WordPress Tools\n";
echo "3. The site secret is displayed in the API Information section\n";
?>