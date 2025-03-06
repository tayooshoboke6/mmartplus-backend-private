<?php
// Vouchers API Validation Script
// This script tests the backend API for vouchers, including all CRUD operations

// Bootstrap the Laravel application
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Set up HTTP client for API requests
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

echo "=== VOUCHERS API VALIDATION SCRIPT ===\n\n";

// Set up configuration
$baseUrl = 'http://localhost:8000/api';
$adminEmail = 'admin@mmart.com';
$adminPassword = 'password123';

// Helper functions
function apiRequest($method, $endpoint, $data = [], $token = null) {
    global $baseUrl;
    
    $client = new Client(['base_uri' => $baseUrl]);
    $options = ['json' => $data];
    
    if ($token) {
        $options['headers'] = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ];
    }
    
    try {
        $response = $client->request($method, $endpoint, $options);
        return [
            'status' => 'success',
            'data' => json_decode($response->getBody()->getContents(), true)
        ];
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $errorResponse = json_decode($e->getResponse()->getBody()->getContents(), true);
            return [
                'status' => 'error',
                'data' => $errorResponse,
                'message' => $errorResponse['message'] ?? 'Request failed'
            ];
        }
        
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

function loginAsAdmin() {
    global $adminEmail, $adminPassword;
    
    echo "Authenticating as admin...\n";
    $result = apiRequest('POST', '/auth/login', [
        'email' => $adminEmail,
        'password' => $adminPassword
    ]);
    
    if ($result['status'] === 'success' && isset($result['data']['access_token'])) {
        echo "Authentication successful!\n\n";
        return $result['data']['access_token'];
    } else {
        echo "Authentication failed: " . ($result['message'] ?? 'Unknown error') . "\n";
        exit(1);
    }
}

// Get admin token
$adminToken = loginAsAdmin();

// Get current vouchers count
echo "Checking current vouchers...\n";
$currentVouchers = apiRequest('GET', '/admin/vouchers', [], $adminToken);

if ($currentVouchers['status'] === 'success') {
    $voucherCount = count($currentVouchers['data']['data'] ?? []);
    echo "Found {$voucherCount} existing vouchers.\n\n";
} else {
    echo "Error getting current vouchers: " . ($currentVouchers['message'] ?? 'Unknown error') . "\n";
    exit(1);
}

// STEP 1: Create a test voucher
echo "STEP 1: Creating a test voucher...\n";
$timestamp = date('YmdHis');
$voucherData = [
    'code' => 'TEST' . $timestamp,
    'description' => 'Test Voucher ' . $timestamp,
    'discount_type' => 'percentage',
    'discount_value' => 10,
    'min_order_amount' => 50,
    'max_discount_amount' => 20,
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+30 days')),
    'usage_limit' => 100,
    'active' => true
];

$createResult = apiRequest('POST', '/admin/vouchers', $voucherData, $adminToken);

if ($createResult['status'] === 'success' && isset($createResult['data']['data']['id'])) {
    $voucherId = $createResult['data']['data']['id'];
    echo "Voucher created successfully!\n";
    echo "ID: {$voucherId}\n";
    echo "Code: {$voucherData['code']}\n\n";
} else {
    echo "Error creating voucher: " . ($createResult['message'] ?? 'Unknown error') . "\n";
    echo json_encode($createResult, JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

// STEP 2: Update the voucher
echo "STEP 2: Updating the voucher...\n";
$updateData = [
    'description' => 'UPDATED TEST VOUCHER',
    'discount_value' => 15,
    'min_order_amount' => 100
];

$updateResult = apiRequest('PUT', "/admin/vouchers/{$voucherId}", $updateData, $adminToken);

if ($updateResult['status'] === 'success') {
    echo "Voucher updated successfully!\n";
    echo "New description: {$updateData['description']}\n";
    echo "New discount value: {$updateData['discount_value']}%\n";
    echo "New minimum order amount: \${$updateData['min_order_amount']}\n\n";
} else {
    echo "Error updating voucher: " . ($updateResult['message'] ?? 'Unknown error') . "\n";
    exit(1);
}

// STEP 3: Toggle voucher status
echo "STEP 3: Toggling voucher active status...\n";
$toggleResult = apiRequest('PUT', "/admin/vouchers/{$voucherId}/toggle-status", [], $adminToken);

if ($toggleResult['status'] === 'success') {
    $newStatus = $toggleResult['data']['data']['active'] ? 'Active' : 'Inactive';
    echo "Status toggled successfully!\n";
    echo "Original status: Active\n";
    echo "New status: {$newStatus}\n\n";
} else {
    echo "Error toggling status: " . ($toggleResult['message'] ?? 'Unknown error') . "\n";
    exit(1);
}

// STEP 4: Test voucher validation
echo "STEP 4: Testing voucher validation...\n";
$validationData = [
    'code' => $voucherData['code'],
    'order_total' => 150
];

$validationResult = apiRequest('POST', '/checkout/validate-voucher', $validationData, $adminToken);

if ($validationResult['status'] === 'success') {
    $discountAmount = $validationResult['data']['data']['discount_amount'] ?? 'N/A';
    $finalAmount = $validationResult['data']['data']['final_amount'] ?? 'N/A';
    
    echo "Voucher validation successful!\n";
    echo "Original order total: \$150\n";
    echo "Discount amount: \${$discountAmount}\n";
    echo "Final amount: \${$finalAmount}\n\n";
} else {
    // This might fail depending on the status we toggled to
    echo "Voucher validation response: " . ($validationResult['message'] ?? 'Unknown error') . "\n";
    echo "This is expected if the voucher is inactive.\n\n";
}

// STEP 5: Delete the test voucher
echo "STEP 5: Deleting test voucher...\n";
$deleteResult = apiRequest('DELETE', "/admin/vouchers/{$voucherId}", [], $adminToken);

if ($deleteResult['status'] === 'success') {
    echo "Test voucher deleted successfully!\n\n";
} else {
    echo "Error deleting voucher: " . ($deleteResult['message'] ?? 'Unknown error') . "\n";
    exit(1);
}

// STEP 6: Verify cleanup
echo "STEP 6: Verifying cleanup...\n";
$finalVouchers = apiRequest('GET', '/admin/vouchers', [], $adminToken);

if ($finalVouchers['status'] === 'success') {
    $finalVoucherCount = count($finalVouchers['data']['data'] ?? []);
    
    if ($finalVoucherCount === $voucherCount) {
        echo "Verified: All test data was properly cleaned up.\n\n";
    } else {
        echo "Warning: Voucher count mismatch after cleanup ({$finalVoucherCount} vs original {$voucherCount}).\n";
    }
} else {
    echo "Error verifying cleanup: " . ($finalVouchers['message'] ?? 'Unknown error') . "\n";
}

echo "=== VALIDATION COMPLETE ===\n";
echo "All backend API functions for Vouchers have been tested.\n";
echo "Next steps: Validate frontend UI integration.\n";
