<?php
/**
 * OxaPay Webhook Handler
 * 
 * Receives and processes payment notifications from OxaPay
 * Prevents direct browser access - CLI only
 */

// Prevent direct browser access
if (php_sapi_name() !== 'cli' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    die('Forbidden');
}

// Include dependencies
require_once __DIR__ . '/../../config/dbconfig.php';
require_once __DIR__ . '/../../config/oxapay.php';
require_once __DIR__ . '/../../include/functions.php';

// Load Composer autoloader
$autoloadPath = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    http_response_code(500);
    die('Dependencies not installed');
}
require_once $autoloadPath;

use OxaPay\PHP\Support\Facades\OxaPay;
use OxaPay\PHP\Exceptions\WebhookSignatureException;

// Verify OxaPay is configured
if (!isOxaPayConfigured()) {
    logWebhook('Error: OxaPay is not properly configured', 'ERROR');
    http_response_code(500);
    die('Configuration error');
}

// Get API key from configuration
$oxapayApiKey = OXAPAY_API_KEY;

// Logging function
function logWebhook($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message\n";
    
    $logDir = __DIR__ . '/../../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/oxapay_webhook.log';
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    echo $logMessage;
}

try {
    logWebhook('Webhook received');
    
    // Verify webhook signature
    $webhookData = OxaPay::webhook(merchantApiKey: $oxapayApiKey)->getData();
    
    logWebhook('Webhook signature verified');
    logWebhook('Webhook data: ' . json_encode($webhookData));
    
    // Extract payment information
    $trackId = $webhookData['trackId'] ?? null;
    $status = $webhookData['status'] ?? null;
    $orderId = $webhookData['orderId'] ?? null;
    $amount = $webhookData['amount'] ?? null;
    $currency = $webhookData['currency'] ?? null;
    
    // Parse order ID to get transaction ID
    if (!$orderId || strpos($orderId, 'TXN-') !== 0) {
        throw new Exception('Invalid or missing order ID');
    }
    
    $transactionId = intval(str_replace('TXN-', '', $orderId));
    
    // Get transaction details
    $txQuery = $conn->query("SELECT * FROM transactions WHERE id = $transactionId AND type = 'deposit'");
    if (!$txQuery || $txQuery->num_rows === 0) {
        throw new Exception("Transaction not found: ID $transactionId");
    }
    
    $transaction = $txQuery->fetch_assoc();
    $userId = $transaction['user_id'];
    $txAmount = $transaction['amount'];
    
    // Handle payment status
    switch ($status) {
        case 'Completed':
            logWebhook("Processing completed payment for transaction #$transactionId");
            
            // Update transaction status
            $updateSql = "UPDATE transactions SET 
                          status = 'completed',
                          payment_details = '" . $conn->real_escape_string(json_encode([
                              'gateway' => 'oxapay',
                              'invoice_id' => $trackId,
                              'currency' => $currency,
                              'amount_received' => $amount,
                              'status' => 'completed'
                          ])) . "'
                          WHERE id = $transactionId";
            
            if (!$conn->query($updateSql)) {
                throw new Exception('Failed to update transaction: ' . $conn->error);
            }
            
            // Add funds to user account
            $fundSql = "UPDATE users SET balance = balance + $txAmount WHERE id = $userId";
            if (!$conn->query($fundSql)) {
                throw new Exception('Failed to add funds: ' . $conn->error);
            }
            
            logWebhook("✓ Payment completed and funds added for user #$userId (Transaction #$transactionId)", 'SUCCESS');
            
            break;
            
        case 'Waiting':
            logWebhook("Payment pending for transaction #$transactionId", 'INFO');
            
            // Update to processing
            $conn->query("UPDATE transactions SET status = 'processing' WHERE id = $transactionId");
            
            break;
            
        case 'Cancelled':
        case 'Expired':
            logWebhook("Payment cancelled/expired for transaction #$transactionId");
            
            // Update transaction status
            $conn->query("UPDATE transactions SET status = 'failed' WHERE id = $transactionId");
            
            break;
            
        default:
            logWebhook("Unknown payment status: $status", 'WARNING');
    }
    
    http_response_code(200);
    echo json_encode(['status' => 'received']);
    
} catch (WebhookSignatureException $e) {
    logWebhook('Webhook signature verification failed: ' . $e->getMessage(), 'ERROR');
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
    
} catch (Exception $e) {
    logWebhook('Webhook processing error: ' . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
