<?php
include '../config/dbconfig.php';
include '../include/functions.php';
include '../include/auth.php';

// Require login
requireLogin();

$pageTitle = 'TRON Payment';
$userId = $_SESSION['user_id'];
$alertMessage = '';
$alertType = '';

// Tron wallet address
$tronAddress = 'TjbeaNsQ8sBaEsVGtfVV9DQYwnwL6q1dw';

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

// Process TRON payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tron_txid'])) {
    $tronTxid = sanitize($_POST['tron_txid']);
    
    // Validate TXID format (TRON txids are 64 character hex strings)
    if (empty($tronTxid) || strlen($tronTxid) !== 64) {
        $alertMessage = 'Invalid TRON transaction ID. TXID must be 64 characters.';
        $alertType = 'error';
    } else {
        // Update transaction with TRON TXID
        $sql = "UPDATE transactions SET 
                payment_details = '" . $conn->real_escape_string(json_encode([
                    'payment_method' => 'tron',
                    'tron_txid' => $tronTxid,
                    'tron_address' => $tronAddress,
                    'amount' => $amount,
                    'status' => 'pending_confirmation'
                ])) . "' 
                WHERE id = $transactionId";
        
        if ($conn->query($sql)) {
            $alertMessage = 'TRON transaction ID submitted successfully. Please wait for admin confirmation. Your funds will be added after verification.';
            $alertType = 'success';
        } else {
            $alertMessage = 'Failed to submit payment: ' . $conn->error;
            $alertType = 'error';
        }
    }
}

include '../include/user-layout-start.php';
?>

<style>
    .glass-card {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    
    .btn-primary {
        background: #3b82f6;
        color: white;
        border-radius: 12px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        transition: all 0.2s;
    }
    
    .btn-primary:hover {
        background: #2563eb;
    }
    
    .form-input {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 0.75rem 1rem;
    }
    
    .form-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        outline: none;
    }
    
    .qr-code-box {
        background: #f8fafc;
        border-radius: 20px;
        padding: 2rem;
        text-align: center;
        border: 2px dashed #cbd5e1;
    }
    
    .tron-address-box {
        background: #eff6ff;
        border: 1px solid #dcfce7;
        border-radius: 12px;
        padding: 1rem;
        word-break: break-all;
        font-family: monospace;
        font-size: 0.875rem;
    }
    
    .copy-btn {
        background: #dbeafe;
        border: 1px solid #bbf7d0;
        color: #3b82f6;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
    }
    
    .copy-btn:hover {
        background: #bfdbfe;
    }
</style>

<div class="max-w-2xl mx-auto px-4">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-slate-900 tracking-tight">TRON Payment</h1>
        <p class="text-sm text-slate-500 mt-1 uppercase tracking-widest font-bold">Complete your deposit with TRON TRC20</p>
    </div>
    
    <!-- Payment Method Card -->
    <div class="glass-card p-6 mb-8 border-l-4 border-red-500">
        <div class="flex items-center">
            <img src="../assets/images/payment/tron.png" alt="TRON" class="h-12 w-12 object-contain mr-4">
            <div>
                <p class="text-sm text-slate-500 font-bold uppercase tracking-wider">Payment Method</p>
                <h2 class="text-2xl font-bold text-slate-900">TRON (TRC20)</h2>
            </div>
        </div>
    </div>
    
    <!-- Alert Message -->
    <?php if ($alertMessage): ?>
        <div class="mb-8 p-4 rounded-xl <?php echo $alertType == 'success' ? 'bg-primary-50 border border-primary-100' : 'bg-red-50 border border-red-100'; ?> flex items-start">
            <div class="flex-shrink-0 mt-0.5">
                <?php if ($alertType == 'success'): ?>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                <?php else: ?>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                <?php endif; ?>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-bold <?php echo $alertType == 'success' ? 'text-primary-800' : 'text-red-800'; ?>">
                    <?php echo $alertType == 'success' ? 'Success!' : 'Error!'; ?>
                </h3>
                <div class="mt-1 text-sm <?php echo $alertType == 'success' ? 'text-primary-700' : 'text-red-700'; ?>">
                    <p><?php echo $alertMessage; ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Payment Details Card -->
    <div class="glass-card p-8 mb-8">
        <div class="flex items-center justify-between mb-8 pb-8 border-b border-slate-100">
            <div>
                <p class="text-sm text-slate-500 uppercase tracking-wider font-bold mb-1">Amount to Send</p>
                <p class="text-3xl md:text-4xl font-bold text-slate-900"><?php echo formatCurrency($amount); ?></p>
            </div>
            <div class="text-right">
                <p class="text-sm text-slate-500 uppercase tracking-wider font-bold mb-1">Transaction ID</p>
                <p class="text-xl font-bold text-slate-700">#<?php echo $transactionId; ?></p>
            </div>
        </div>
        
        <!-- QR Code Section -->
        <div class="mb-8">
            <p class="text-sm font-bold text-slate-700 mb-4 uppercase tracking-wider">TRON Address QR Code</p>
            <div class="qr-code-box">
                <img id="qr-code" src="" alt="TRON Address QR Code" style="width: 250px; height: 250px; margin: 0 auto;">
            </div>
            <p class="text-xs text-slate-500 mt-4 text-center">Scan this QR code with your TRON wallet to send payment</p>
        </div>
        
        <!-- TRON Address Section -->
        <div class="mb-8">
            <p class="text-sm font-bold text-slate-700 mb-3 uppercase tracking-wider">TRON Wallet Address</p>
            <div class="flex items-center gap-2">
                <div class="flex-1 tron-address-box">
                    <?php echo htmlspecialchars($tronAddress); ?>
                </div>
                <button type="button" class="copy-btn" onclick="copyToClipboard('<?php echo $tronAddress; ?>')">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    Copy
                </button>
            </div>
        </div>
        
        <!-- Instructions -->
        <div class="bg-primary-50 border border-primary-100 rounded-12px p-4 mb-8">
            <p class="text-sm text-primary-800 font-semibold mb-2">Instructions:</p>
            <ol class="text-sm text-primary-700 list-decimal list-inside space-y-1">
                <li>Send exactly <?php echo formatCurrency($amount); ?> worth of TRON (USDT TRC20) to the address above</li>
                <li>Wait for the transaction to be confirmed on the blockchain</li>
                <li>Copy your transaction ID (TXID) from your wallet</li>
                <li>Paste the TXID in the form below and submit</li>
                <li>Wait for admin confirmation. Your funds will be added after verification</li>
            </ol>
        </div>
    </div>
    
    <!-- TXID Submission Form -->
    <div class="glass-card p-8 mb-8">
        <h2 class="text-xl font-bold text-slate-900 mb-6">Submit TRON Transaction ID</h2>
        
        <form method="post" action="">
            <div class="mb-6">
                <label for="tron_txid" class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wider">Transaction ID (TXID)</label>
                <input 
                    type="text" 
                    id="tron_txid" 
                    name="tron_txid" 
                    class="form-input w-full" 
                    placeholder="Paste your TRON transaction ID here (64 character hex string)" 
                    required
                    pattern="[a-fA-F0-9]{64}"
                    title="TXID must be 64 hexadecimal characters">
                <p class="mt-2 text-xs text-slate-500">
                    Your TXID is a 64-character hexadecimal string that you can find in your wallet transaction history.
                </p>
            </div>
            
            <div class="bg-amber-50 border border-amber-100 rounded-12px p-4 mb-6">
                <p class="text-sm text-amber-800">
                    <strong>Important:</strong> Make sure you've sent the correct amount to the wallet address above. Payments with incorrect amounts may not be processed.
                </p>
            </div>
            
            <button type="submit" class="btn-primary w-full py-3 text-lg flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Submit Transaction ID
            </button>
        </form>
    </div>
    
    <!-- Back to Funds -->
    <div class="text-center mb-8">
        <a href="../addfund/" class="text-primary-600 hover:text-primary-700 font-semibold text-sm">
            ← Back to Add Funds
        </a>
    </div>
</div>

<script>
    // Generate QR code using QR Server API
    function generateQRCode() {
        const tronAddress = '<?php echo $tronAddress; ?>';
        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(tronAddress)}`;
        document.getElementById('qr-code').src = qrUrl;
    }
    
    // Copy to clipboard function
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('TRON address copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy:', err);
        });
    }
    
    // Generate QR code on page load
    window.addEventListener('DOMContentLoaded', generateQRCode);
</script>

<?php include '../include/user-layout-end.php'; ?>
