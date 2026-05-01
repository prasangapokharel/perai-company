<?php
header('Content-Type: application/json');
include '../../../config/dbconfig.php';
include '../../../include/functions.php';
include '../../../include/auth.php';

// Require admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['name']) || !isset($input['url']) || !isset($input['api_key'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$name = $conn->real_escape_string(trim($input['name']));
$url = $conn->real_escape_string(trim($input['url']));
$apiKey = $conn->real_escape_string(trim($input['api_key']));

// Validate URL
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid API URL']);
    exit;
}

// Check if provider already exists
$check = $conn->query("SELECT id FROM api_providers WHERE url = '$url'");
if ($check && $check->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Provider with this URL already exists']);
    exit;
}

// Add provider
$sql = "INSERT INTO api_providers (name, url, api_key, status, balance) 
        VALUES ('$name', '$url', '$apiKey', 1, 0)";

if ($conn->query($sql)) {
    $providerId = $conn->insert_id;
    
    // Try to get balance
    try {
        $api = new Api($url, $apiKey);
        $balanceResponse = $api->balance();
        
        if ($balanceResponse && isset($balanceResponse->balance)) {
            $balance = floatval($balanceResponse->balance);
            $conn->query("UPDATE api_providers SET balance = $balance WHERE id = $providerId");
        }
    } catch (Exception $e) {
        // Balance check failed, but provider was added
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Provider added successfully',
        'provider_id' => $providerId
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}
