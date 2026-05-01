<?php
include '../config/dbconfig.php';
include '../include/functions.php';
include '../include/auth.php';

// Require login
requireLogin();

$pageTitle = 'Complete Payment';
$userId = $_SESSION['user_id'];
$alertMessage = '';
$alertType = '';

// Get transaction ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../addfund/");
    exit;
}

$transactionId = intval($_GET['id']);

// Get transaction details
$sql = "SELECT * FROM transactions WHERE id = $transactionId AND user_id = $userId AND type = 'deposit' AND status = 'pending'";
$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
    header("Location: ../addfund/");
    exit;
}

$transaction = $result->fetch_assoc();
$amount = $transaction['amount'];
$paymentMethod = $transaction['payment_method'];

// Process payment completion
if (isset($_POST['complete_payment'])) {
    $binanceOrderId = sanitize($_POST['binance_order_id']);
    
    // Handle screenshot upload if provided
    $screenshotPath = null;
    if (isset($_FILES['payment_screenshot']) && $_FILES['payment_screenshot']['error'] === 0) {
        $uploadDir = __DIR__ . '/../uploads/payment_screenshots/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = time() . '_' . $_FILES['payment_screenshot']['name'];
        $targetFile = $uploadDir . $fileName;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['payment_screenshot']['tmp_name'], $targetFile)) {
            // Store relative path for web access
            $screenshotPath = 'uploads/payment_screenshots/' . $fileName;
        }
    }
    
    // Update transaction with Binance order ID and screenshot
    $sql = "UPDATE transactions SET 
            binance_order_id = '$binanceOrderId', 
            binance_txn_screenshot = " . ($screenshotPath ? "'$screenshotPath'" : "NULL") . " 
            WHERE id = $transactionId";
    
    if ($conn->query($sql)) {
        $alertMessage = 'Payment submitted successfully. Funds will be added to your account after admin approval.';
        $alertType = 'success';
    } else {
        $alertMessage = 'Failed to update transaction: ' . $conn->error;
        $alertType = 'error';
    }
}

include '../include/user-layout-start.php';
?>

<div class="max-w-4xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-slate-900 mb-2">Complete Your Payment</h1>
        <p class="text-slate-600">Follow the instructions below to add funds to your account</p>
    </div>
    
    <!-- Alert Message -->
    <?php if ($alertMessage): ?>
        <div class="mb-8 p-4 rounded-xl <?php echo $alertType == 'success' ? 'bg-primary-50 border border-primary-200' : 'bg-red-50 border border-red-200'; ?> flex items-start">
            <div class="flex-shrink-0 mt-0.5">
                <?php if ($alertType == 'success'): ?>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                <?php else: ?>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                <?php endif; ?>
            </div>
            <div class="ml-4 flex-1">
                <h3 class="text-lg font-semibold <?php echo $alertType == 'success' ? 'text-primary-800' : 'text-red-800'; ?>">
                    <?php echo $alertType == 'success' ? 'Success!' : 'Error!'; ?>
                </h3>
                <p class="mt-1 <?php echo $alertType == 'success' ? 'text-primary-700' : 'text-red-700'; ?>">
                    <?php echo $alertMessage; ?>
                </p>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Payment Method Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 pb-8 border-b border-slate-100">
            <!-- Left: Payment Details -->
            <div>
                <p class="text-sm text-slate-500 font-bold uppercase tracking-wider mb-2">Payment Method</p>
                <div class="flex items-center gap-3">
                    <?php if ($paymentMethod === 'binance'): ?>
                        <img src="../assets/images/payment/binance.jpg" alt="Binance Pay" class="h-10 w-10 object-contain">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900">Binance Pay</h3>
                            <p class="text-sm text-slate-500">Scan QR code to pay</p>
                        </div>
                    <?php else: ?>
                        <img src="../assets/images/payment/tron.png" alt="TRON" class="h-10 w-10 object-contain">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900">TRON (TRC20)</h3>
                            <p class="text-sm text-slate-500">Send USDT or TRX</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Right: Amount -->
            <div class="flex items-end justify-end">
                <div class="text-right">
                    <p class="text-sm text-slate-500 font-bold uppercase tracking-wider mb-2">Amount to Pay</p>
                    <p class="text-4xl font-bold text-primary-600">$<?php echo formatCurrency($amount); ?></p>
                    <p class="text-xs text-slate-400 mt-2">Transaction ID: #<?php echo $transactionId; ?></p>
                </div>
            </div>
        </div>
        
        <?php if ($paymentMethod === 'binance'): ?>
            <!-- BINANCE PAYMENT SECTION -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-slate-900 mb-6">Binance Payment Instructions</h2>
                
                <!-- QR Code -->
                <div class="bg-slate-50 rounded-xl p-8 mb-8 text-center border border-slate-200">
                    <p class="text-sm font-bold text-slate-600 uppercase tracking-wider mb-4">Scan with Binance App</p>
                    <div class="inline-block bg-white p-6 rounded-xl shadow-sm border border-slate-100">
                        <img src="../assets/images/pay.jpg" alt="Binance Pay QR Code" class="w-64 h-64 object-cover">
                    </div>
                    <div class="mt-4">
                        <p class="text-sm text-slate-600">Binance ID: <strong>743126097</strong></p>
                        <p class="text-xs text-slate-400 mt-2">Complete the payment for <?php echo formatCurrency($amount); ?></p>
                    </div>
                </div>
                
                <!-- Steps -->
                <div class="bg-primary-50 border border-primary-200 rounded-xl p-6 mb-8">
                    <h3 class="font-bold text-primary-900 mb-4">Payment Steps:</h3>
                    <ol class="space-y-3 text-primary-800 text-sm">
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-500 text-white flex items-center justify-center font-bold text-xs">1</span>
                            <span>Open your <strong>Binance app</strong> or website</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-500 text-white flex items-center justify-center font-bold text-xs">2</span>
                            <span>Scan the QR code above with Binance Pay</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-500 text-white flex items-center justify-center font-bold text-xs">3</span>
                            <span>Complete the payment for <strong><?php echo formatCurrency($amount); ?></strong></span>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-500 text-white flex items-center justify-center font-bold text-xs">4</span>
                            <span>Copy your <strong>Binance Order ID</strong> from payment confirmation</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-500 text-white flex items-center justify-center font-bold text-xs">5</span>
                            <span>Fill the form below and submit</span>
                        </li>
                    </ol>
                </div>
                
                <!-- Form -->
                <form action="?id=<?php echo $transactionId; ?>" method="post" enctype="multipart/form-data" class="space-y-6">
                    <div>
                        <label for="binance_order_id" class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wider">Binance Order ID *</label>
                        <input 
                            type="text" 
                            id="binance_order_id" 
                            name="binance_order_id" 
                            class="block w-full px-4 py-3 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition" 
                            placeholder="e.g. 123456789" 
                            required>
                        <p class="mt-2 text-xs text-slate-500">Enter the 9-digit Order ID from your Binance payment confirmation</p>
                    </div>
                    
                    <div>
                        <label for="payment_screenshot" class="block text-sm font-bold text-slate-700 mb-3 uppercase tracking-wider">Payment Screenshot (Optional)</label>
                        <div class="border-2 border-dashed border-slate-300 rounded-xl p-8 text-center cursor-pointer hover:border-primary-500 hover:bg-primary-50/30 transition" onclick="document.getElementById('payment_screenshot').click()">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-slate-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="text-sm font-medium text-slate-700">Click to upload or drag and drop</p>
                            <p class="text-xs text-slate-500 mt-1">PNG, JPG, JPEG (max 5MB)</p>
                        </div>
                        <input id="payment_screenshot" name="payment_screenshot" type="file" class="hidden" accept="image/*" onchange="previewScreenshot(this)">
                        <div id="screenshot-preview" class="mt-4 hidden">
                            <img id="preview-img" src="" alt="Preview" class="max-h-40 rounded-xl">
                        </div>
                    </div>
                    
                    <div class="flex gap-4">
                        <button type="submit" name="complete_payment" class="flex-1 bg-primary-600 hover:bg-primary-700 text-white font-bold py-3 px-6 rounded-xl transition flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Submit Payment Details
                        </button>
                        <a href="../addfund/" class="flex-1 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold py-3 px-6 rounded-xl transition text-center">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
            
        <?php else: ?>
            <!-- TRON PAYMENT SECTION -->
            <div>
                <h2 class="text-2xl font-bold text-slate-900 mb-6">TRON Payment Instructions</h2>
                
                <!-- Wallet Address Section -->
                <div class="bg-primary-50 border border-primary-200 rounded-xl p-6 mb-8">
                    <p class="text-sm font-bold text-primary-900 uppercase tracking-wider mb-3">TRON Wallet Address</p>
                    <div class="flex gap-2 items-center">
                        <input type="text" value="TjbeaNsQ8sBaEsVGtfVV9DQYwnwL6q1dw" readonly class="flex-1 px-4 py-3 bg-white border border-primary-300 rounded-xl font-mono text-sm">
                        <button type="button" class="px-4 py-3 bg-primary-500 hover:bg-primary-600 text-white font-bold rounded-xl transition" onclick="copyToClipboard('TjbeaNsQ8sBaEsVGtfVV9DQYwnwL6q1dw')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- QR Code -->
                <div class="bg-slate-50 rounded-xl p-8 mb-8 text-center border border-slate-200">
                    <p class="text-sm font-bold text-slate-600 uppercase tracking-wider mb-4">Scan to Send TRON</p>
                    <div class="inline-block bg-white p-4 rounded-xl shadow-sm border border-slate-100">
                        <img id="qr-code" src="" alt="TRON Address QR Code" style="width: 220px; height: 220px;">
                    </div>
                </div>
                
                <!-- Steps -->
                <div class="bg-primary-50 border border-primary-200 rounded-xl p-6 mb-8">
                    <h3 class="font-bold text-primary-900 mb-4">Payment Steps:</h3>
                    <ol class="space-y-3 text-primary-800 text-sm">
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-500 text-white flex items-center justify-center font-bold text-xs">1</span>
                            <span>Open your <strong>TRON wallet</strong> (TronLink, Ledger, etc.)</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-500 text-white flex items-center justify-center font-bold text-xs">2</span>
                            <span>Send exactly <strong><?php echo formatCurrency($amount); ?></strong> in USDT (TRC20) or TRX to the address above</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-500 text-white flex items-center justify-center font-bold text-xs">3</span>
                            <span>Wait for blockchain confirmation (usually 1-2 minutes)</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-500 text-white flex items-center justify-center font-bold text-xs">4</span>
                            <span>Copy your <strong>Transaction ID (TXID)</strong> from your wallet</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-500 text-white flex items-center justify-center font-bold text-xs">5</span>
                            <span>Paste the TXID in the form below and submit</span>
                        </li>
                    </ol>
                </div>
                
                <!-- TXID Form -->
                <form action="../payment/tron.php?id=<?php echo $transactionId; ?>" method="post" class="space-y-6">
                    <div>
                        <label for="tron_txid" class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wider">Transaction ID (TXID) *</label>
                        <input 
                            type="text" 
                            id="tron_txid" 
                            name="tron_txid" 
                            class="block w-full px-4 py-3 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition font-mono text-sm" 
                            placeholder="Paste your 64-character TXID here" 
                            pattern="[a-fA-F0-9]{64}"
                            title="TXID must be 64 hexadecimal characters"
                            required>
                        <p class="mt-2 text-xs text-slate-500">Your TXID is a 64-character hexadecimal string (numbers and letters a-f)</p>
                    </div>
                    
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                        <p class="text-sm text-amber-800">
                            <strong>⚠️ Important:</strong> Make sure you've sent the correct amount to the wallet address above. Double-check the amount and wallet address before sending.
                        </p>
                    </div>
                    
                    <div class="flex gap-4">
                        <button type="submit" class="flex-1 bg-primary-600 hover:bg-primary-700 text-white font-bold py-3 px-6 rounded-xl transition flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Submit Transaction ID
                        </button>
                        <a href="../addfund/" class="flex-1 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold py-3 px-6 rounded-xl transition text-center">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Screenshot preview
    function previewScreenshot(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview-img').src = e.target.result;
                document.getElementById('screenshot-preview').classList.remove('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Copy to clipboard
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('TRON address copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy:', err);
        });
    }
    
    // Generate QR code for TRON address
    function generateQRCode() {
        const tronAddress = 'TjbeaNsQ8sBaEsVGtfVV9DQYwnwL6q1dw';
        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(tronAddress)}`;
        document.getElementById('qr-code').src = qrUrl;
    }
    
    // Generate QR code on page load
    window.addEventListener('DOMContentLoaded', generateQRCode);
</script>

<?php include '../include/user-layout-end.php'; ?>
