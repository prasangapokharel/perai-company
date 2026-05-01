<?php
/**
 * OxaPay Payment Gateway Integration
 * 
 * Handles cryptocurrency payments via OxaPay API
 * Supported: Bitcoin, Ethereum, USDT, USDC, Tron, etc.
 */

// Prevent direct access without proper headers
if (php_sapi_name() === 'cli') {
    die('This script cannot be run from CLI');
}

// Include dependencies
require_once __DIR__ . '/../../config/dbconfig.php';
require_once __DIR__ . '/../../config/oxapay.php';
require_once __DIR__ . '/../../include/functions.php';
require_once __DIR__ . '/../../include/auth.php';

// Load Composer autoloader with shared hosting compatibility
$autoloadPath = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die('Error: Composer dependencies not installed. Run: composer install');
}
require_once $autoloadPath;

use OxaPay\PHP\OxaPayManager;
use OxaPay\PHP\Exceptions\OxaPayException;

// Require login
requireLogin();

$pageTitle = OXAPAY_PAGE_TITLE;
$userId = $_SESSION['user_id'];
$alertMessage = '';
$alertType = '';

// Get merchant settings
$merchantSettings = getOxaPayMerchantSettings();
$paymentMessages = getOxaPayMessages();

// Verify OxaPay is configured
if (!isOxaPayConfigured()) {
    die('Error: OxaPay is not properly configured. Please contact support.');
}

// Get transaction ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../../addfund/");
    exit;
}

$transactionId = intval($_GET['id']);

// Get transaction details
$sql = "SELECT * FROM transactions WHERE id = $transactionId AND user_id = $userId AND type = 'deposit' AND status = 'pending'";
$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
    header("Location: ../../addfund/");
    exit;
}

$transaction = $result->fetch_assoc();
$amount = floatval($transaction['amount']);

// Handle payment generation
$paymentUrl = null;
$invoiceId = null;
$errorMessage = '';

// Auto-generate invoice with USDT as default currency
try {
    // Initialize OxaPay manager
    $oxapay = new OxaPayManager(timeout: OXAPAY_TIMEOUT);
    
    // Generate invoice with configured currency
    $response = $oxapay->payment(OXAPAY_API_KEY)->generateInvoice([
        'amount' => $amount,
        'currency' => OXAPAY_DEFAULT_CURRENCY,
        'order_id' => "TXN-{$transactionId}",
        'description' => "Deposit - Transaction #{$transactionId}",
        'callback_url' => getOxaPayWebhookUrl(),
        'return_url' => getOxaPaySuccessUrl()
    ]);
    
    // Check response - SDK returns array directly with track_id and payment_url
    if ($response && is_array($response) && isset($response['track_id']) && isset($response['payment_url'])) {
        $invoiceId = $response['track_id'];
        $paymentUrl = $response['payment_url'];
        
        // Store invoice ID in transaction
        $updateSql = "UPDATE transactions SET 
                      payment_details = '" . $conn->real_escape_string(json_encode([
                          'gateway' => 'oxapay',
                          'invoice_id' => $invoiceId,
                          'currency' => 'USDT',
                          'status' => 'pending_payment',
                          'created_at' => date('Y-m-d H:i:s')
                      ])) . "'
                      WHERE id = $transactionId";
        
        if (!$conn->query($updateSql)) {
            $errorMessage = 'Failed to save invoice details: ' . $conn->error;
        }
    } else {
        $errorMessage = 'Failed to generate invoice. Please try again.';
    }
    
} catch (OxaPayException $e) {
    $errorMessage = 'OxaPay Error: ' . $e->getMessage();
} catch (Exception $e) {
    $errorMessage = 'Error: ' . $e->getMessage();
}

include '../../include/user-layout-start.php';
?>

<div class="max-w-4xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-slate-900 mb-2">Pay with <?php echo htmlspecialchars(OXAPAY_MERCHANT_NAME); ?></h1>
        <p class="text-slate-600"><?php echo htmlspecialchars($paymentMessages['description']); ?></p>
    </div>
    
    <!-- Payment Method Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-8 border-l-4" style="border-left-color: <?php echo OXAPAY_PRIMARY_COLOR; ?>;">
        <div class="flex items-center">
            <img src="../../assets/images/payment/oxapay.png" alt="OxaPay" class="h-12 w-12 object-contain mr-4">
            <div>
                <p class="text-sm text-slate-500 font-bold uppercase tracking-wider">Payment Method</p>
                <h2 class="text-2xl font-bold text-slate-900">OxaPay</h2>
            </div>
        </div>
    </div>
    
    <!-- Alert Messages -->
    <?php if ($errorMessage): ?>
        <div class="mb-8 p-4 rounded-xl bg-red-50 border border-red-200 flex items-start">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <div class="ml-4 flex-1">
                <h3 class="text-lg font-semibold text-red-800">Error</h3>
                <p class="mt-1 text-red-700"><?php echo htmlspecialchars($errorMessage); ?></p>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($paymentUrl): ?>
        <!-- Payment Generated Successfully -->
        <div class="mb-8 p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-start">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-500 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <div class="ml-4 flex-1">
                <h3 class="text-lg font-semibold text-emerald-800">Invoice Ready</h3>
                <p class="mt-1 text-emerald-700">Your payment invoice is ready. Click below to proceed to payment.</p>
            </div>
        </div>
        
        <!-- Payment Details Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 pb-8 border-b border-slate-100">
                <div>
                    <p class="text-sm text-slate-500 font-bold uppercase tracking-wider mb-2">Payment Gateway</p>
                    <div class="flex items-center gap-3">
                        <img src="../../assets/images/payment/oxapay.png" alt="OxaPay" class="h-10 w-10 object-contain">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900">OxaPay</h3>
                            <p class="text-sm text-slate-500">Cryptocurrency Payment</p>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-end justify-end">
                    <div class="text-right">
                        <p class="text-sm text-slate-500 font-bold uppercase tracking-wider mb-2">Amount to Pay</p>
                        <p class="text-4xl font-bold text-emerald-600">$<?php echo formatCurrency($amount); ?></p>
                        <p class="text-xs text-slate-400 mt-2">Currency: <?php echo OXAPAY_DEFAULT_CURRENCY; ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Payment Button -->
             <div class="flex flex-col gap-4">
                 <a href="<?php echo htmlspecialchars($paymentUrl); ?>" target="_blank" class="w-full text-white font-bold py-4 px-6 rounded-lg transition flex items-center justify-center gap-2 text-lg" style="background-color: <?php echo OXAPAY_PRIMARY_COLOR; ?>; ">
                     <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                     </svg>
                     <?php echo htmlspecialchars($paymentMessages['pay_button']); ?>
                 </a>
                 <p class="text-center text-xs text-slate-500"><?php echo htmlspecialchars($paymentMessages['info']); ?></p>
             </div>
         </div>
        
        <!-- Payment Info -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
            <h3 class="font-bold text-blue-900 mb-4">Payment Information</h3>
            <ul class="space-y-2 text-blue-800 text-sm">
                <li>✓ Secure payment processing</li>
                <li>✓ Multiple cryptocurrency options available</li>
                <li>✓ Fast transaction confirmation</li>
                <li>✓ Funds added to account after verification</li>
                <li>✓ 24/7 customer support</li>
            </ul>
        </div>
        
    <?php else: ?>
        <!-- Generating Invoice -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 pb-8 border-b border-slate-100">
                <div>
                    <p class="text-sm text-slate-500 font-bold uppercase tracking-wider mb-2">Payment Gateway</p>
                    <div class="flex items-center gap-3">
                        <img src="../../assets/images/payment/oxapay.png" alt="OxaPay" class="h-10 w-10 object-contain">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900">OxaPay</h3>
                            <p class="text-sm text-slate-500">Cryptocurrency Payment</p>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-end justify-end">
                    <div class="text-right">
                        <p class="text-sm text-slate-500 font-bold uppercase tracking-wider mb-2">Amount to Pay</p>
                        <p class="text-4xl font-bold text-emerald-600">$<?php echo formatCurrency($amount); ?></p>
                        <p class="text-xs text-slate-400 mt-2">Currency: <?php echo OXAPAY_DEFAULT_CURRENCY; ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Generating Message -->
            <div class="text-center py-8">
                <div class="inline-block">
                    <svg class="animate-spin h-12 w-12 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mt-4">Generating Invoice...</h3>
                <p class="text-slate-600 mt-2">Please wait while we prepare your payment</p>
            </div>
        </div>
        
        <!-- Info Section -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
            <h3 class="font-bold text-blue-900 mb-4">Why Choose OxaPay?</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 bg-white rounded-lg">
                    <div class="text-2xl mb-2">🔒</div>
                    <p class="font-semibold text-slate-900">Secure</p>
                    <p class="text-sm text-slate-600">Enterprise-grade security</p>
                </div>
                <div class="p-4 bg-white rounded-lg">
                    <div class="text-2xl mb-2">⚡</div>
                    <p class="font-semibold text-slate-900">Fast</p>
                    <p class="text-sm text-slate-600">Instant payment processing</p>
                </div>
                <div class="p-4 bg-white rounded-lg">
                    <div class="text-2xl mb-2">🌐</div>
                    <p class="font-semibold text-slate-900">Global</p>
                    <p class="text-sm text-slate-600">50+ cryptocurrencies</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../../include/user-layout-end.php'; ?>