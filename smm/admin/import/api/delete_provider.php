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

// Check if provider exists
$provider = $conn->query("SELECT id FROM api_providers WHERE id = $providerId")->fetch_assoc();

if (!$provider) {
    echo json_encode(['success' => false, 'message' => 'Provider not found']);
    exit;
}

// Delete associated services
$conn->query("DELETE FROM services WHERE api_provider_id = $providerId");

// Delete provider
if ($conn->query("DELETE FROM api_providers WHERE id = $providerId")) {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'service_categories'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $columnCheck = $conn->query("SHOW COLUMNS FROM services LIKE 'category_id'");
        if ($columnCheck && $columnCheck->num_rows > 0) {
            $conn->query("DELETE FROM service_categories WHERE id NOT IN (SELECT DISTINCT category_id FROM services WHERE category_id IS NOT NULL)");
        }
    }
    echo json_encode([
        'success' => true,
        'message' => 'Provider and associated services deleted successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete provider: ' . $conn->error
    ]);
}
