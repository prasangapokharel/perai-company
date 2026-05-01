<?php
// Prevent direct access
if (php_sapi_name() !== 'cli') {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

include '../config/dbconfig.php';
include '../include/functions.php';

// Log file
$logFile = __DIR__ . '/statuschecker.log';

// Function to log messages
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Fetch all orders with an API order ID
$orders = $conn->query("
    SELECT id, api_order_id 
    FROM orders 
    WHERE api_order_id IS NOT NULL
");

if (!$orders) {
    logMessage("Failed to fetch orders: " . $conn->error);
    exit;
}

if ($orders->num_rows === 0) {
    logMessage("No orders found with API order ID.");
    exit;
}

$updatedCount = 0;
$errors = [];

while ($order = $orders->fetch_assoc()) {
    $orderId = $order['id'];
    $apiOrderId = $order['api_order_id'];
    
    logMessage("Processing order ID $orderId (API order ID: $apiOrderId)");
    
    $result = checkOrderStatusViaApi($orderId);
    
    if ($result['success']) {
        $updatedCount++;
        logMessage("Successfully updated order ID $orderId to status: " . $result['status']);
    } else {
        $errors[] = "Order ID $orderId (API ID: $apiOrderId): " . $result['message'];
        logMessage("Failed to update order ID $orderId: " . $result['message']);
    }
}

if ($updatedCount > 0) {
    logMessage("Updated status for $updatedCount order(s).");
}

if (!empty($errors)) {
    logMessage("Errors encountered: " . implode('; ', $errors));
}

logMessage("Status check completed.");
?>