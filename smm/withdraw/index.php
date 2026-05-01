<?php
include '../config/dbconfig.php';
include '../include/functions.php';
include '../include/auth.php';

// Require login
requireLogin();

$pageTitle = 'Withdraw Funds';
$userId = $_SESSION['user_id'];
$alertMessage = '';
$alertType = '';

// Get user's affiliate balance
$userResult = $conn->query("SELECT affiliate_balance FROM users WHERE id = $userId");
$user = $userResult->fetch_assoc();
$affiliateBalance = $user['affiliate_balance'] ?? 0;

// Cryptocurrency networks configuration
$cryptoNetworks = [
    'tron' => [
        'name' => 'TRON',
        'networks' => [
            'trc10' => 'TRON (TRC10)',
            'trc20' => 'TRON (TRC20)'
        ],
        'addressPlaceholder' => 'T...',
        'minWithdraw' => 10
    ],
    'bitcoin' => [
        'name' => 'Bitcoin',
        'networks' => [
            'bitcoin' => 'Bitcoin (BTC)'
        ],
        'addressPlaceholder' => '1... or 3... or bc1...',
        'minWithdraw' => 0.001
    ],
    'ethereum' => [
        'name' => 'Ethereum',
        'networks' => [
            'eth' => 'Ethereum (ETH)'
        ],
        'addressPlaceholder' => '0x...',
        'minWithdraw' => 0.01
    ]
];

// Process withdrawal request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_withdrawal'])) {
    $amount = floatval($_POST['amount']);
    $cryptoName = sanitize($_POST['crypto_name']);
    $network = sanitize($_POST['network']);
    $address = sanitize($_POST['address']);
    
    // Validation
    $errors = [];
    
    if ($amount <= 0) {
        $errors[] = 'Amount must be greater than 0';
    }
    
    if ($amount > $affiliateBalance) {
        $errors[] = 'Insufficient balance. Your balance: $' . number_format($affiliateBalance, 2);
    }
    
    if (empty($cryptoName) || !isset($cryptoNetworks[$cryptoName])) {
        $errors[] = 'Invalid cryptocurrency selected';
    }
    
    if (empty($network) || !isset($cryptoNetworks[$cryptoName]['networks'][$network])) {
        $errors[] = 'Invalid network selected';
    }
    
    if (empty($address) || strlen($address) < 10) {
        $errors[] = 'Please enter a valid wallet address';
    }
    
    $minWithdraw = $cryptoNetworks[$cryptoName]['minWithdraw'] ?? 10;
    if ($amount < $minWithdraw) {
        $errors[] = "Minimum withdrawal: {$minWithdraw} {$cryptoName}";
    }
    
    if (empty($errors)) {
        // Create withdrawal request
        $sql = "INSERT INTO withdrawals (user_id, amount, address, name, network, status) 
                VALUES ($userId, $amount, '$address', '$cryptoName', '$network', 'pending')";
        
        if ($conn->query($sql)) {
            // Deduct from affiliate balance
            $newBalance = $affiliateBalance - $amount;
            $conn->query("UPDATE users SET affiliate_balance = $newBalance WHERE id = $userId");
            
            $alertMessage = 'Withdrawal request submitted successfully! It will be processed within 24 hours.';
            $alertType = 'success';
            
            // Refresh affiliate balance
            $affiliateBalance = $newBalance;
            
            // Clear form
            $_POST = [];
        } else {
            $alertMessage = 'Failed to create withdrawal request: ' . $conn->error;
            $alertType = 'error';
        }
    } else {
        $alertMessage = implode('<br>', $errors);
        $alertType = 'error';
    }
}

// Get withdrawal history
$withdrawals = $conn->query("
    SELECT * FROM withdrawals 
    WHERE user_id = $userId 
    ORDER BY created_at DESC 
    LIMIT 20
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
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        transition: all 0.2s;
    }
    
    .btn-primary:hover {
        background: #2563eb;
    }
    
    .btn-primary:disabled {
        background: #cbd5e1;
        cursor: not-allowed;
    }
    
    .form-input, .form-select {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 0.75rem 1rem;
        font-size: 1rem;
    }
    
    .form-input:focus, .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        outline: none;
    }
    
    .balance-card {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    .balance-label {
        opacity: 0.9;
        font-size: 0.875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .balance-amount {
        font-size: 2.5rem;
        font-weight: 700;
        margin-top: 0.5rem;
    }
    
    .tab-button {
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        border-radius: 12px;
        transition: all 0.2s;
        cursor: pointer;
        border: none;
        background: none;
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
    
    .status-badge {
        display: inline-block;
        padding: 0.35rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .status-pending {
        background: #fef3c7;
        color: #92400e;
    }
    
    .status-confirming {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .status-confirmed {
        background: #dcfce7;
        color: #166534;
    }
    
    .status-cancelled {
        background: #fee2e2;
        color: #991b1b;
    }
</style>

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-display text-slate-900 tracking-tight uppercase">Withdraw Funds</h1>
        <p class="text-xs md:text-sm text-slate-500 mt-1 uppercase tracking-widest font-bold">Withdraw your affiliate earnings</p>
    </div>
    
    <!-- Balance Card -->
    <div class="balance-card">
        <div class="balance-label">Available Affiliate Balance</div>
        <div class="balance-amount">$<?php echo number_format($affiliateBalance, 2); ?></div>
        <div class="text-sm opacity-75 mt-2">Ready to withdraw to your crypto wallet</div>
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
        <button class="tab-button active" onclick="showTab('withdraw-form')">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
            New Withdrawal
        </button>
        <button class="tab-button" onclick="showTab('withdrawal-history')">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            History
        </button>
    </div>
    
    <!-- Withdrawal Form Section -->
    <div id="withdraw-form" class="glass-card p-8">
        <form action="" method="post">
            <div class="mb-8">
                <label for="crypto_name" class="block text-sm font-bold text-slate-700 mb-2">Select Cryptocurrency</label>
                <select id="crypto_name" name="crypto_name" class="form-select block w-full" required onchange="updateNetworks()">
                    <option value="">-- Choose a cryptocurrency --</option>
                    <?php foreach ($cryptoNetworks as $key => $crypto): ?>
                        <option value="<?php echo $key; ?>"><?php echo $crypto['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-8">
                <label for="network" class="block text-sm font-bold text-slate-700 mb-2">Select Network</label>
                <select id="network" name="network" class="form-select block w-full" required>
                    <option value="">-- First select a cryptocurrency --</option>
                </select>
                <p class="mt-2 text-xs text-slate-500">Choose the blockchain network for your withdrawal</p>
            </div>
            
            <div class="mb-8">
                <label for="address" class="block text-sm font-bold text-slate-700 mb-2">Wallet Address</label>
                <input type="text" id="address" name="address" class="form-input block w-full" placeholder="Enter your wallet address" required>
                <p class="mt-2 text-xs text-slate-500">Make sure the address is correct. We cannot recover funds sent to wrong addresses.</p>
            </div>
            
            <div class="mb-8">
                <label for="amount" class="block text-sm font-bold text-slate-700 mb-2">Withdrawal Amount (USD)</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <span class="text-slate-400 font-bold">$</span>
                    </div>
                    <input type="number" id="amount" name="amount" min="0" step="0.01" value="" class="form-input block w-full pl-10 text-lg font-bold text-slate-800" placeholder="0.00" required>
                </div>
                <p class="mt-2 text-xs text-slate-500">Available balance: <span class="font-bold text-primary-600">$<?php echo number_format($affiliateBalance, 2); ?></span></p>
            </div>
            
            <div class="bg-amber-50 p-5 rounded-2xl mb-8 border border-amber-100">
                <div class="flex">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-500 mr-3 mt-0.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <p class="text-sm text-amber-700 leading-relaxed">
                        Withdrawals are processed within 24 hours. Please double-check your wallet address before submitting. We cannot recover funds sent to wrong addresses.
                    </p>
                </div>
            </div>
            
            <button type="submit" name="submit_withdrawal" class="btn-primary w-full flex items-center justify-center py-4 text-lg" <?php echo $affiliateBalance <= 0 ? 'disabled' : ''; ?>>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                Withdraw Funds
            </button>
        </form>
    </div>
    
    <!-- Withdrawal History Section -->
    <div id="withdrawal-history" class="glass-card hidden overflow-hidden">
        <div class="p-6 border-b border-slate-50">
            <h2 class="text-xl font-bold text-slate-900">Withdrawal History</h2>
        </div>
        
        <?php if ($withdrawals && $withdrawals->num_rows > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left bg-slate-50/50">
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Crypto</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Network</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php while ($withdrawal = $withdrawals->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50/30 transition-colors">
                                <td class="px-6 py-4 text-sm font-medium text-slate-900">#<?php echo $withdrawal['id']; ?></td>
                                <td class="px-6 py-4 text-sm font-bold text-primary-600">$<?php echo number_format($withdrawal['amount'], 2); ?></td>
                                <td class="px-6 py-4 text-sm text-slate-600"><?php echo ucfirst($withdrawal['name']); ?></td>
                                <td class="px-6 py-4 text-sm text-slate-600"><?php echo strtoupper($withdrawal['network']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="status-badge status-<?php echo $withdrawal['status']; ?>">
                                        <?php echo ucfirst($withdrawal['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500"><?php echo date('M d, Y H:i', strtotime($withdrawal['created_at'])); ?></td>
                                <td class="px-6 py-4 text-sm">
                                    <button type="button" class="text-primary-600 hover:text-primary-700 font-medium" onclick="showWithdrawalDetails(<?php echo htmlspecialchars(json_encode($withdrawal)); ?>)">
                                        View
                                    </button>
                                </td>
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
                <h3 class="text-lg font-bold text-slate-900">No withdrawals yet</h3>
                <p class="text-slate-500">Start by submitting a withdrawal request above</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Withdrawal Details Modal -->
<div id="detailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="glass-card p-8 max-w-md w-full">
        <h2 class="text-xl font-bold text-slate-900 mb-4">Withdrawal Details</h2>
        <div id="detailsContent" class="space-y-4"></div>
        <button type="button" class="btn-primary w-full mt-6" onclick="closeModal()">Close</button>
    </div>
</div>

<script>
    const cryptoNetworks = <?php echo json_encode($cryptoNetworks); ?>;
    
    function updateNetworks() {
        const cryptoName = document.getElementById('crypto_name').value;
        const networkSelect = document.getElementById('network');
        
        networkSelect.innerHTML = '<option value="">-- Select a network --</option>';
        
        if (cryptoName && cryptoNetworks[cryptoName]) {
            const networks = cryptoNetworks[cryptoName].networks;
            for (const [key, label] of Object.entries(networks)) {
                const option = document.createElement('option');
                option.value = key;
                option.textContent = label;
                networkSelect.appendChild(option);
            }
        }
    }
    
    function showTab(tabName) {
        // Hide all tabs
        document.getElementById('withdraw-form').classList.add('hidden');
        document.getElementById('withdrawal-history').classList.add('hidden');
        
        // Show selected tab
        document.getElementById(tabName).classList.remove('hidden');
        
        // Update button states
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');
    }
    
    function showWithdrawalDetails(withdrawal) {
        const modal = document.getElementById('detailsModal');
        const content = document.getElementById('detailsContent');
        
        const txidDisplay = withdrawal.txid ? `
            <div>
                <p class="text-xs text-slate-500 uppercase font-bold">Transaction ID</p>
                <p class="text-sm font-mono text-slate-800 break-all">${withdrawal.txid}</p>
            </div>
        ` : '';
        
        content.innerHTML = `
            <div>
                <p class="text-xs text-slate-500 uppercase font-bold">Amount</p>
                <p class="text-lg font-bold text-primary-600">$${parseFloat(withdrawal.amount).toFixed(2)}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500 uppercase font-bold">Cryptocurrency</p>
                <p class="text-sm text-slate-800">${withdrawal.name.toUpperCase()} (${withdrawal.network.toUpperCase()})</p>
            </div>
            <div>
                <p class="text-xs text-slate-500 uppercase font-bold">Wallet Address</p>
                <p class="text-sm font-mono text-slate-800 break-all">${withdrawal.address}</p>
            </div>
            ${txidDisplay}
            <div>
                <p class="text-xs text-slate-500 uppercase font-bold">Status</p>
                <span class="status-badge status-${withdrawal.status}">${withdrawal.status.charAt(0).toUpperCase() + withdrawal.status.slice(1)}</span>
            </div>
            <div>
                <p class="text-xs text-slate-500 uppercase font-bold">Requested</p>
                <p class="text-sm text-slate-600">${new Date(withdrawal.created_at).toLocaleString()}</p>
            </div>
        `;
        
        modal.classList.remove('hidden');
    }
    
    function closeModal() {
        document.getElementById('detailsModal').classList.add('hidden');
    }
    
    // Close modal when clicking outside
    document.getElementById('detailsModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
</script>

<?php include '../include/user-layout-end.php'; ?>
