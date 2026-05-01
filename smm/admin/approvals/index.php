<?php
include '../../config/dbconfig.php';
include '../../include/functions.php';
include '../../include/auth.php';

// Require admin
requireAdmin();

$pageTitle = 'Fund Approvals';
$alertMessage = '';
$alertType = '';

// Process approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transactionId = intval($_POST['transaction_id']);
    $action = sanitize($_POST['action']);
    
    // Get transaction details
    $transaction = $conn->query("SELECT * FROM transactions WHERE id = $transactionId AND type = 'deposit' AND status = 'pending'")->fetch_assoc();
    
    if (!$transaction) {
        $alertMessage = 'Invalid transaction or already processed';
        $alertType = 'error';
    } else {
        $userId = $transaction['user_id'];
        $amount = $transaction['amount'];
        
        if ($action === 'approve') {
            // Update transaction status
            $conn->query("UPDATE transactions SET status = 'completed' WHERE id = $transactionId");
            
            // Add funds to user balance
            if (updateUserBalance($userId, $amount)) {
                $alertMessage = 'Fund request approved successfully';
                $alertType = 'success';
            } else {
                $alertMessage = 'Failed to update user balance';
                $alertType = 'error';
            }
        } elseif ($action === 'reject') {
            // Update transaction status
            $conn->query("UPDATE transactions SET status = 'canceled' WHERE id = $transactionId");
            
            $alertMessage = 'Fund request rejected';
            $alertType = 'success';
        }
    }
}

// View transaction details
$viewTransaction = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $transactionId = intval($_GET['view']);
    $result = $conn->query("
        SELECT t.*, u.username 
        FROM transactions t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.id = $transactionId AND t.type = 'deposit'
    ");
    
    if ($result && $result->num_rows > 0) {
        $viewTransaction = $result->fetch_assoc();
    }
}

// Get pending fund requests
$pendingFundRequests = $conn->query("
    SELECT t.*, u.username 
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.type = 'deposit' AND t.status = 'pending' 
    ORDER BY t.created_at DESC
");

include '../../include/admin-layout-start.php';
?>

<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Fund Approvals</h1>
            <p class="text-slate-500 font-medium mt-1">Review and process manual fund addition requests.</p>
        </div>
    </div>

    <?php if ($alertMessage): ?>
        <div class="p-4 rounded-2xl flex items-center shadow-sm border <?php echo $alertType == 'success' ? 'bg-primary-50 text-primary-700 border-primary-100' : 'bg-rose-50 text-rose-700 border-rose-100'; ?>">
            <p class="text-sm font-bold uppercase tracking-tight"><?php echo $alertMessage; ?></p>
        </div>
    <?php endif; ?>

    <?php if ($viewTransaction): ?>
        <!-- Transaction Detail View -->
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="p-8 border-b border-slate-50 flex justify-between items-center">
                <h2 class="text-sm font-black text-slate-900 uppercase tracking-widest">Transaction Details #<?php echo $viewTransaction['id']; ?></h2>
                <a href="index.php" class="text-xs font-bold text-slate-400 hover:text-slate-600 uppercase tracking-widest">Close Details</a>
            </div>
            
            <div class="p-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Customer</p>
                                <p class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($viewTransaction['username']); ?></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Amount</p>
                                <p class="text-xl font-black text-primary-600"><?php echo formatCurrency($viewTransaction['amount']); ?></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6 pt-6 border-t border-slate-50">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Method</p>
                                <p class="text-sm font-bold text-slate-900"><?php echo ucfirst($viewTransaction['payment_method']); ?></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Date</p>
                                <p class="text-sm font-bold text-slate-900"><?php echo date('M d, Y H:i', strtotime($viewTransaction['created_at'])); ?></p>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-slate-50">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Transaction ID / Order ID</p>
                            <p class="text-sm font-mono font-bold text-slate-900"><?php echo $viewTransaction['binance_order_id'] ?: 'None provided'; ?></p>
                        </div>

                        <?php if ($viewTransaction['status'] === 'pending'): ?>
                            <div class="pt-8 flex gap-3">
                                <form action="index.php" method="post" class="flex-1">
                                    <input type="hidden" name="transaction_id" value="<?php echo $viewTransaction['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="w-full py-4 bg-primary-600 text-white text-xs font-black uppercase tracking-widest rounded-2xl hover:bg-primary-700 shadow-lg shadow-primary-100 transition-all active:scale-[0.98]" onclick="return confirm('Approve this payment?')">
                                        Approve Payment
                                    </button>
                                </form>
                                <form action="index.php" method="post" class="flex-1">
                                    <input type="hidden" name="transaction_id" value="<?php echo $viewTransaction['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="w-full py-4 bg-rose-50 text-rose-600 text-xs font-black uppercase tracking-widest rounded-2xl hover:bg-rose-600 hover:text-white transition-all active:scale-[0.98]" onclick="return confirm('Reject this payment?')">
                                        Reject
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Verification Proof</p>
                        <?php if ($viewTransaction['binance_txn_screenshot']): ?>
                            <div class="rounded-2xl border border-slate-100 overflow-hidden shadow-sm bg-slate-50">
                                <img src="<?php echo '../../' . $viewTransaction['binance_txn_screenshot']; ?>" alt="Payment Screenshot" class="w-full h-auto cursor-zoom-in" onclick="window.open(this.src)">
                            </div>
                        <?php else: ?>
                            <div class="h-64 rounded-2xl border border-slate-100 bg-slate-50 flex items-center justify-center text-slate-400 font-bold uppercase text-[10px] tracking-widest">
                                No screenshot provided
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Pending Requests Table -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="p-8 border-b border-slate-50">
            <h2 class="text-sm font-black text-slate-900 uppercase tracking-widest">Awaiting Approval</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">ID</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">User</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Amount</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Method</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Reference</th>
                        <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if ($pendingFundRequests && $pendingFundRequests->num_rows > 0): ?>
                        <?php while ($transaction = $pendingFundRequests->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-8 py-5 text-sm font-black text-slate-400">#<?php echo $transaction['id']; ?></td>
                                <td class="px-8 py-5 text-sm font-bold text-slate-900"><?php echo htmlspecialchars($transaction['username']); ?></td>
                                <td class="px-8 py-5 text-sm font-black text-primary-600"><?php echo formatCurrency($transaction['amount']); ?></td>
                                <td class="px-8 py-5 text-xs font-bold text-slate-600 uppercase tracking-tighter"><?php echo ucfirst($transaction['payment_method']); ?></td>
                                <td class="px-8 py-5 text-[10px] font-mono font-bold text-slate-400"><?php echo $transaction['binance_order_id'] ?: '--'; ?></td>
                                <td class="px-8 py-5 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="index.php?view=<?php echo $transaction['id']; ?>" class="px-4 py-2 bg-slate-50 text-slate-600 text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-primary-600 hover:text-white transition-all">
                                            Review
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-8 py-12 text-center text-slate-400 font-bold uppercase text-xs tracking-widest italic">No pending requests.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../include/admin-layout-end.php'; ?>
