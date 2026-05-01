<?php
include '../config/dbconfig.php';
include '../include/functions.php';
include '../include/auth.php';

// Require login
requireLogin();

$pageTitle = 'Add Funds';
$userId = $_SESSION['user_id'];
$alertMessage = '';
$alertType = '';

// Process add fund form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $paymentMethod = sanitize($_POST['payment_method']);
    
    // Validate input
    if ($amount <= 0) {
        $alertMessage = 'Please enter a valid amount';
        $alertType = 'error';
    } else {
        // Create transaction record
        $details = json_encode([
            'payment_method' => $paymentMethod,
            'amount' => $amount,
            'status' => 'pending'
        ]);
        
        $sql = "INSERT INTO transactions (user_id, amount, type, status, payment_method, payment_details) 
                VALUES ($userId, $amount, 'deposit', 'pending', '$paymentMethod', '$details')";
        
        if ($conn->query($sql)) {
            $transactionId = $conn->insert_id;
            
            // Redirect to the appropriate payment page based on method
            if ($paymentMethod === 'binance') {
                header("Location: ../payment/?id=$transactionId");
            } elseif ($paymentMethod === 'tron') {
                header("Location: ../payment/tron.php?id=$transactionId");
            } elseif ($paymentMethod === 'oxapay') {
                header("Location: ../payment/oxapay/?id=$transactionId");
            }
            exit;
        } else {
            $alertMessage = 'Failed to create transaction: ' . $conn->error;
            $alertType = 'error';
        }
    }
}

// Get transaction history
$transactions = $conn->query("
    SELECT * FROM transactions 
    WHERE user_id = $userId AND type = 'deposit' 
    ORDER BY created_at DESC 
    LIMIT 10
");

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
         padding: 0.5rem 1rem;
         font-weight: 600;
         font-size: 0.875rem;
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
         box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
         outline: none;
     }
    
    .payment-method {
        background: #f8fafc;
        border: 2px solid #f1f5f9;
        border-radius: 16px;
        padding: 1.25rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .payment-method:hover {
        border-color: #cbd5e1;
    }
    
    .payment-method.selected {
         background: #eff6ff;
         border-color: #3b82f6;
     }
    
    .payment-method input {
        position: absolute;
        opacity: 0;
    }
    
    .tab-button {
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        border-radius: 12px;
        transition: all 0.2s;
    }
    
    .tab-button.active {
         background: #3b82f6;
         color: white;
     }
    
    .tab-button:not(.active) {
        background: white;
        color: #64748b;
        border: 1px solid #f1f5f9;
    }
</style>

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-display text-slate-900 tracking-tight uppercase">Add Funds</h1>
        <p class="text-xs md:text-sm text-slate-500 mt-1 uppercase tracking-widest font-bold">Add funds to your account to place orders</p>
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
    
    <!-- Tabs -->
    <div class="flex flex-wrap gap-3 mb-8">
        <button id="add-fund-btn" class="tab-button active flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
            Add Funds
        </button>
        <button id="transaction-history-btn" class="tab-button flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            History
        </button>
    </div>
    
    <!-- Add Fund Section -->
    <div id="add-fund-section" class="glass-card p-8">
        <form action="" method="post">
            <div class="mb-8">
                <label class="block text-sm font-bold text-slate-700 mb-4">Payment Method</label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="payment-method selected">
                        <label class="flex items-center cursor-pointer w-full">
                            <input type="radio" name="payment_method" value="binance" checked>
                            <div class="flex items-center w-full">
                                <img src="../assets/images/payment/binance.jpg" alt="Binance Pay" class="h-12 w-12 object-contain mr-3">
                                <div>
                                    <p class="font-bold text-slate-800">Binance Pay</p>
                                    <p class="text-xs text-primary-600 font-semibold">Fast & Secure</p>
                                </div>
                            </div>
                        </label>
                    </div>
                    <div class="payment-method">
                        <label class="flex items-center cursor-pointer w-full">
                            <input type="radio" name="payment_method" value="tron">
                            <div class="flex items-center w-full">
                                <img src="../assets/images/payment/tron.png" alt="TRON TRC20" class="h-12 w-12 object-contain mr-3">
                                <div>
                                    <p class="font-bold text-slate-800">TRON (TRC20)</p>
                                    <p class="text-xs text-red-600 font-semibold">Crypto Payment</p>
                                </div>
                            </div>
                        </label>
                    </div>
                    <div class="payment-method">
                        <label class="flex items-center cursor-pointer w-full">
                            <input type="radio" name="payment_method" value="oxapay">
                            <div class="flex items-center w-full">
                                <img src="../assets/images/payment/oxapay.png" alt="OxaPay" class="h-12 w-12 object-contain mr-3">
                                <div>
                                    <p class="font-bold text-slate-800">OxaPay</p>
                                    <p class="text-xs text-purple-600 font-semibold">50+ Cryptos</p>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="mb-8">
                <label for="amount" class="block text-sm font-bold text-slate-700 mb-2">Amount (USD)</label>
                <input type="number" id="amount" name="amount" min="1" step="0.01" value="1" class="form-input block w-full text-lg font-bold text-slate-800" required>
                <p class="mt-3 text-xs text-slate-400">Min deposit: <span class="font-bold">$1.00</span></p>
            </div>
            
            <div class="bg-slate-50 p-5 rounded-2xl mb-8 border border-slate-100">
                <div class="flex">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-500 mr-3 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <p class="text-sm text-slate-600 leading-relaxed">
                        Funds are added instantly after confirmation. Binance Pay usually takes 1-15 minutes to reflect in your balance.
                    </p>
                </div>
            </div>
            
            <button type="submit" class="btn-primary w-full flex items-center justify-center py-4 text-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                Proceed to Payment
            </button>
        </form>
    </div>
    
    <!-- Transaction History Section -->
    <div id="transaction-history-section" class="glass-card hidden overflow-hidden">
        <div class="p-6 border-b border-slate-50">
            <h2 class="text-xl font-bold text-slate-900">Deposit History</h2>
        </div>
        
        <?php if ($transactions && $transactions->num_rows > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left bg-slate-50/50">
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Method</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php while ($transaction = $transactions->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50/30 transition-colors">
                                <td class="px-6 py-4 text-sm font-medium text-slate-900">#<?php echo $transaction['id']; ?></td>
                                <td class="px-6 py-4 text-sm font-bold text-primary-600"><?php echo formatCurrency($transaction['amount']); ?></td>
                                <td class="px-6 py-4 text-sm text-slate-600"><?php echo ucfirst($transaction['payment_method']); ?></td>
                                <td class="px-6 py-4">
                                    <?php 
                                    $statusClasses = [
                                        'completed' => 'bg-primary-100 text-primary-700',
                                        'pending' => 'bg-amber-100 text-amber-700',
                                        'canceled' => 'bg-red-100 text-red-700'
                                    ];
                                    $class = $statusClasses[$transaction['status']] ?? 'bg-slate-100 text-slate-700';
                                    ?>
                                    <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?php echo $class; ?>">
                                        <?php echo $transaction['status']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500"><?php echo date('M d, Y', strtotime($transaction['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-16">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900">No deposits found</h3>
                <p class="text-slate-500">Add funds to see your transaction history here.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    const addFundBtn = document.getElementById('add-fund-btn');
    const transactionHistoryBtn = document.getElementById('transaction-history-btn');
    const addFundSection = document.getElementById('add-fund-section');
    const transactionHistorySection = document.getElementById('transaction-history-section');
    
    addFundBtn.addEventListener('click', function() {
        addFundSection.classList.remove('hidden');
        transactionHistorySection.classList.add('hidden');
        addFundBtn.classList.add('active');
        transactionHistoryBtn.classList.remove('active');
    });
    
    transactionHistoryBtn.addEventListener('click', function() {
        addFundSection.classList.add('hidden');
        transactionHistorySection.classList.remove('hidden');
        addFundBtn.classList.remove('active');
        transactionHistoryBtn.classList.add('active');
    });
    
    const paymentMethods = document.querySelectorAll('.payment-method');
    paymentMethods.forEach(method => {
        method.addEventListener('click', function() {
            paymentMethods.forEach(m => m.classList.remove('selected'));
            this.classList.add('selected');
            this.querySelector('input[type="radio"]').checked = true;
        });
    });
</script>

<?php include '../include/user-layout-end.php'; ?>