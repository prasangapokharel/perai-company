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

if (!$input || !isset($input['provider_id'])) {
    echo json_encode(['success' => false, 'message' => 'Provider ID required']);
    exit;
}

$providerId = intval($input['provider_id']);

// Get provider
$provider = $conn->query("SELECT * FROM api_providers WHERE id = $providerId")->fetch_assoc();

if (!$provider) {
    echo json_encode(['success' => false, 'message' => 'Provider not found']);
    exit;
}

try {
    $api = new Api($provider['url'], $provider['api_key']);
    $balanceResponse = $api->balance();
    
    if ($balanceResponse && isset($balanceResponse->balance)) {
        $balance = floatval($balanceResponse->balance);
        
        // Update balance in database
        $conn->query("UPDATE api_providers SET balance = $balance WHERE id = $providerId");
        
        echo json_encode([
            'success' => true,
            'balance' => $balance,
            'message' => 'Balance updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to get balance from API'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'API Error: ' . $e->getMessage()
    ]);
}
