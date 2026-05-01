<?php
include '../config/dbconfig.php';
include '../include/functions.php';
include '../include/auth.php';

// Require admin login
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../");
    exit;
}

$pageTitle = 'Manage Withdrawals';
$alertMessage = '';
$alertType = '';

// Process withdrawal status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_withdrawal'])) {
    $withdrawalId = intval($_POST['withdrawal_id']);
    $status = sanitize($_POST['status']);
    $txid = sanitize($_POST['txid']);
    
    // Validate status
    $validStatuses = ['pending', 'confirming', 'confirmed', 'cancelled'];
    if (!in_array($status, $validStatuses)) {
        $alertMessage = 'Invalid status';
        $alertType = 'error';
    } else {
        $txidPart = !empty($txid) ? ", txid = '$txid'" : '';
        $sql = "UPDATE withdrawals SET status = '$status' $txidPart WHERE id = $withdrawalId";
        
        if ($conn->query($sql)) {
            $alertMessage = 'Withdrawal status updated successfully';
            $alertType = 'success';
        } else {
            $alertMessage = 'Failed to update withdrawal: ' . $conn->error;
            $alertType = 'error';
        }
    }
}

// Get filter
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$whereClause = '';
if (!empty($statusFilter)) {
    $whereClause = " WHERE status = '$statusFilter'";
}

// Get all withdrawals
$sql = "SELECT w.*, u.username, u.email FROM withdrawals w 
        JOIN users u ON w.user_id = u.id 
        $whereClause
        ORDER BY w.created_at DESC";
$withdrawals = $conn->query($sql);

include '../include/admin-layout-start.php';
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
         transition: all 0.2s;
         border: none;
         cursor: pointer;
     }
     
     .btn-primary:hover {
         background: #2563eb;
     }
     
     .btn-secondary {
         background: #6b7280;
         color: white;
         border-radius: 12px;
         padding: 0.5rem 1rem;
         font-weight: 600;
         transition: all 0.2s;
         border: none;
         cursor: pointer;
     }
     
     .btn-secondary:hover {
         background: #4b5563;
     }
     
     .form-input, .form-select {
         background: #ffffff;
         border: 1px solid #e2e8f0;
         border-radius: 12px;
         padding: 0.5rem 0.75rem;
         font-size: 0.875rem;
     }
     
     .form-input:focus, .form-select:focus {
         border-color: #3b82f6;
         box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
         outline: none;
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
     
     .stat-card {
         background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
         color: white;
         border-radius: 16px;
         padding: 1.5rem;
         margin-bottom: 2rem;
     }
     
    .stat-label {
        opacity: 0.9;
        font-size: 0.875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .stat-value {
        font-size: 1.875rem;
        font-weight: 700;
        margin-top: 0.5rem;
    }
</style>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-display text-slate-900 tracking-tight uppercase">Manage Withdrawals</h1>
        <p class="text-xs md:text-sm text-slate-500 mt-1 uppercase tracking-widest font-bold">Process and track user withdrawal requests</p>
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
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <?php
        $stats = [
            'pending' => $conn->query("SELECT COUNT(*) as count FROM withdrawals WHERE status = 'pending'")->fetch_assoc()['count'],
            'confirming' => $conn->query("SELECT COUNT(*) as count FROM withdrawals WHERE status = 'confirming'")->fetch_assoc()['count'],
            'confirmed' => $conn->query("SELECT COUNT(*) as count FROM withdrawals WHERE status = 'confirmed'")->fetch_assoc()['count'],
            'total' => $conn->query("SELECT SUM(amount) as total FROM withdrawals")->fetch_assoc()['total'] ?? 0
        ];
        ?>
        <div class="stat-card">
            <div class="stat-label">Pending</div>
            <div class="stat-value"><?php echo $stats['pending']; ?></div>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);">
            <div class="stat-label">Confirming</div>
            <div class="stat-value"><?php echo $stats['confirming']; ?></div>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);">
            <div class="stat-label">Confirmed</div>
            <div class="stat-value"><?php echo $stats['confirmed']; ?></div>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
            <div class="stat-label">Total Amount</div>
            <div class="stat-value">$<?php echo number_format($stats['total'], 0); ?></div>
        </div>
    </div>
    
    <!-- Filter -->
    <div class="glass-card p-6 mb-8">
        <form method="get" class="flex flex-col md:flex-row gap-4 items-end">
            <div class="flex-1">
                <label class="block text-sm font-bold text-slate-700 mb-2">Filter by Status</label>
                <select name="status" class="form-select block w-full">
                    <option value="">All Withdrawals</option>
                    <option value="pending" <?php echo $statusFilter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="confirming" <?php echo $statusFilter == 'confirming' ? 'selected' : ''; ?>>Confirming</option>
                    <option value="confirmed" <?php echo $statusFilter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="cancelled" <?php echo $statusFilter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <button type="submit" class="btn-primary">Filter</button>
            <a href="?status=" class="btn-secondary">Clear</a>
        </form>
    </div>
    
    <!-- Withdrawals Table -->
    <div class="glass-card overflow-hidden">
        <div class="p-6 border-b border-slate-50">
            <h2 class="text-xl font-bold text-slate-900">Withdrawal Requests</h2>
        </div>
        
        <?php if ($withdrawals && $withdrawals->num_rows > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left bg-slate-50/50">
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">User</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Crypto</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Network</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php while ($withdrawal = $withdrawals->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50/30 transition-colors">
                                <td class="px-6 py-4 text-sm font-medium text-slate-900">#<?php echo $withdrawal['id']; ?></td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    <div class="font-medium text-slate-800"><?php echo $withdrawal['username']; ?></div>
                                    <div class="text-xs text-slate-500"><?php echo $withdrawal['email']; ?></div>
                                </td>
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
                                    <button type="button" class="text-primary-600 hover:text-primary-700 font-medium" onclick="showUpdateModal(<?php echo htmlspecialchars(json_encode($withdrawal)); ?>)">
                                        Update
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
                <h3 class="text-lg font-bold text-slate-900">No withdrawals found</h3>
                <p class="text-slate-500">There are no withdrawal requests at the moment.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Update Status Modal -->
<div id="updateModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="glass-card p-8 max-w-md w-full">
        <h2 class="text-xl font-bold text-slate-900 mb-6">Update Withdrawal Status</h2>
        <form method="post" action="">
            <input type="hidden" name="withdrawal_id" id="modal_withdrawal_id">
            
            <div class="mb-6">
                <label class="block text-sm font-bold text-slate-700 mb-2">Withdrawal Details</label>
                <div class="bg-slate-50 p-4 rounded-xl text-sm space-y-2">
                    <p><span class="text-slate-500">Amount:</span> <span class="font-bold text-primary-600" id="modal_amount"></span></p>
                    <p><span class="text-slate-500">Crypto:</span> <span class="font-bold text-slate-800" id="modal_crypto"></span></p>
                    <p><span class="text-slate-500">Network:</span> <span class="font-bold text-slate-800" id="modal_network"></span></p>
                    <p><span class="text-slate-500">Address:</span> <span class="font-mono text-xs text-slate-600 break-all" id="modal_address"></span></p>
                </div>
            </div>
            
            <div class="mb-6">
                <label for="status" class="block text-sm font-bold text-slate-700 mb-2">New Status</label>
                <select id="status" name="status" class="form-select block w-full" required>
                    <option value="pending">Pending</option>
                    <option value="confirming">Confirming</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            
            <div class="mb-6">
                <label for="txid" class="block text-sm font-bold text-slate-700 mb-2">Transaction ID (Optional)</label>
                <input type="text" id="txid" name="txid" class="form-input block w-full" placeholder="Enter blockchain transaction ID">
                <p class="mt-2 text-xs text-slate-500">Add transaction ID when marking as confirmed</p>
            </div>
            
            <div class="flex gap-3">
                <button type="submit" name="update_withdrawal" class="btn-primary flex-1">Update</button>
                <button type="button" class="btn-secondary flex-1" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function showUpdateModal(withdrawal) {
        document.getElementById('modal_withdrawal_id').value = withdrawal.id;
        document.getElementById('modal_amount').textContent = '$' + parseFloat(withdrawal.amount).toFixed(2);
        document.getElementById('modal_crypto').textContent = withdrawal.name.toUpperCase();
        document.getElementById('modal_network').textContent = withdrawal.network.toUpperCase();
        document.getElementById('modal_address').textContent = withdrawal.address;
        document.getElementById('status').value = withdrawal.status;
        document.getElementById('txid').value = withdrawal.txid || '';
        document.getElementById('updateModal').classList.remove('hidden');
    }
    
    function closeModal() {
        document.getElementById('updateModal').classList.add('hidden');
    }
    
    // Close modal when clicking outside
    document.getElementById('updateModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
</script>

<?php include '../include/admin-layout-end.php'; ?>
