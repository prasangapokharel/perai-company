<?php
include '../config/dbconfig.php';
include '../include/functions.php';

// Log file for debugging
$logFile = __DIR__ . '/webhook.log';

// Function to log messages
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Prevent direct access
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logMessage("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    exit('Method Not Allowed');
}

// Get webhook data
$webhookData = json_decode(file_get_contents('php://input'), true);

if (!$webhookData) {
    logMessage("Invalid webhook data received");
    http_response_code(400);
    exit('Invalid Data');
}

// Log the received webhook data
logMessage("Webhook received: " . json_encode($webhookData));

// Expected webhook fields (adjust based on MaxelPay documentation)
$orderId = $webhookData['orderID'] ?? null;
$status = $webhookData['status'] ?? null;
$amount = $webhookData['amount'] ?? null;

if (!$orderId || !$status || !$amount) {
    logMessage("Missing required fields: orderID, status, or amount");
    http_response_code(400);
    exit('Missing Required Fields');
}

// Validate transaction
$transactionId = intval($orderId);
$sql = "SELECT * FROM transactions WHERE id = $transactionId AND type = 'deposit' AND payment_method = 'maxelpay'";
$transaction = $conn->query($sql)->fetch_assoc();

if (!$transaction) {
    logMessage("Transaction not found: $transactionId");
    http_response_code(404);
    exit('Transaction Not Found');
}

if ($transaction['status'] !== 'pending') {
    logMessage("Transaction already processed: $transactionId, status: " . $transaction['status']);
    http_response_code(200);
    exit('Transaction Already Processed');
}

// Validate amount
if (floatval($amount) !== floatval($transaction['amount'])) {
    logMessage("Amount mismatch: Expected {$transaction['amount']}, Received $amount");
    http_response_code(400);
    exit('Amount Mismatch');
}

// Process based on status
if ($status === 'success') {
    // Update transaction status to completed
    $conn->query("UPDATE transactions SET status = 'completed', payment_details = '" . json_encode($webhookData) . "' WHERE id = $transactionId");

    // Add funds to user's balance
    $userId = $transaction['user_id'];
    $amount = $transaction['amount'];
    $conn->query("UPDATE users SET balance = balance + $amount WHERE id = $userId");

    // Log success
    logMessage("Transaction $transactionId completed successfully. Added $amount to user $userId balance.");
} elseif ($status === 'failed' || $status === 'canceled') {
    // Update transaction status to canceled
    $conn->query("UPDATE transactions SET status = 'canceled', payment_details = '" . json_encode($webhookData) . "' WHERE id = $transactionId");
    logMessage("Transaction $transactionId canceled.");
} else {
    logMessage("Unknown status received: $status for transaction $transactionId");
    http_response_code(400);
    exit('Unknown Status');
}

http_response_code(200);
echo "Webhook processed successfully";
?>