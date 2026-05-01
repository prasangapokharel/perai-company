<?php
include '../config/dbconfig.php';
include '../include/functions.php';
include '../include/auth.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$pageTitle = "Manage Deposits - Admin Panel";
$alertMessage = '';
$alertType = '';

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $transactionId = intval($_POST['transaction_id']);
    $action = sanitize($_POST['action']); // 'approve' or 'reject'
    $adminNote = sanitize($_POST['admin_note'] ?? '');
    
    // Get transaction details
    $txQuery = $conn->query("SELECT * FROM transactions WHERE id = $transactionId AND type = 'deposit'");
    if (!$txQuery || $txQuery->num_rows === 0) {
        $alertMessage = "Transaction not found";
        $alertType = 'error';
    } else {
        $transaction = $txQuery->fetch_assoc();
        $userId = $transaction['user_id'];
        $amount = $transaction['amount'];
        
        if ($action === 'approve') {
            // Update transaction status
            $conn->query("UPDATE transactions SET status = 'completed', admin_note = '$adminNote', updated_at = NOW() WHERE id = $transactionId");
            
            // Add funds to user balance
            $conn->query("UPDATE users SET balance = balance + $amount WHERE id = $userId");
            
            $alertMessage = "Deposit approved! Amount of $" . formatCurrency($amount) . " added to user balance.";
            $alertType = 'success';
        } else if ($action === 'reject') {
            // Update transaction status
            $conn->query("UPDATE transactions SET status = 'rejected', admin_note = '$adminNote', updated_at = NOW() WHERE id = $transactionId");
            
            $alertMessage = "Deposit rejected.";
            $alertType = 'success';
        }
    }
}

// Get filter
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : 'pending';
$paymentMethodFilter = isset($_GET['method']) ? sanitize($_GET['method']) : 'all';

// Build query
$sql = "SELECT t.*, u.email, u.username FROM transactions t 
        JOIN users u ON t.user_id = u.id
        WHERE t.type = 'deposit'";

if ($statusFilter !== 'all') {
    $sql .= " AND t.status = '$statusFilter'";
}

if ($paymentMethodFilter !== 'all') {
    $sql .= " AND t.payment_method = '$paymentMethodFilter'";
}

$sql .= " ORDER BY t.created_at DESC";

$result = $conn->query($sql);
$transactions = $result->num_rows > 0 ? $result : null;

// Get statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(amount) as total_amount
    FROM transactions 
    WHERE type = 'deposit'
")->fetch_assoc();

include 'admin-header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9fafb;
        }
        
        .card {
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .status-completed {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .status-rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .method-badge {
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .method-binance {
            background-color: #fef08a;
            color: #78350f;
        }
        
         .method-tron {
             background-color: #dbeafe;
             color: #1e40af;
         }
         
         .btn-approve {
             background-color: #3b82f6;
             color: white;
             padding: 0.5rem 1rem;
             border-radius: 8px;
             border: none;
             cursor: pointer;
             font-weight: 600;
             transition: all 0.2s;
         }
         
         .btn-approve:hover {
             background-color: #2563eb;
         }
        
        .btn-reject {
            background-color: #ef4444;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-reject:hover {
            background-color: #dc2626;
        }
    </style>
</head>
<body>
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Manage Deposits</h1>
            <p class="text-gray-600 mt-1">Review and approve pending deposit requests</p>
        </div>
        
        <!-- Alert Message -->
        <?php if ($alertMessage): ?>
            <div class="mb-8 p-4 rounded-xl card <?php echo $alertType == 'success' ? 'bg-primary-50 border border-primary-200' : 'bg-red-50 border border-red-200'; ?> flex items-start">
                <div class="flex-shrink-0">
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
                    <p class="<?php echo $alertType == 'success' ? 'text-primary-800' : 'text-red-800'; ?>"><?php echo $alertMessage; ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="card bg-white p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-gray-500 text-sm font-medium">Total Deposits</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1"><?php echo $stats['total']; ?></p>
                    </div>
                    <div class="p-3 bg-primary-50 rounded-xl">
                        <span class="material-icons text-primary-600">trending_up</span>
                    </div>
                </div>
            </div>
            
            <div class="card bg-white p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-gray-500 text-sm font-medium">Pending</p>
                        <p class="text-2xl font-bold text-yellow-600 mt-1"><?php echo $stats['pending']; ?></p>
                    </div>
                    <div class="p-3 bg-yellow-50 rounded-xl">
                        <span class="material-icons text-yellow-600">schedule</span>
                    </div>
                </div>
            </div>
            
            <div class="card bg-white p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-gray-500 text-sm font-medium">Completed</p>
                        <p class="text-2xl font-bold text-primary-600 mt-1"><?php echo $stats['completed']; ?></p>
                    </div>
                    <div class="p-3 bg-primary-50 rounded-xl">
                        <span class="material-icons text-primary-600">check_circle</span>
                    </div>
                </div>
            </div>
            
            <div class="card bg-white p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-gray-500 text-sm font-medium">Total Amount</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">$<?php echo formatCurrency($stats['total_amount']); ?></p>
                    </div>
                    <div class="p-3 bg-purple-50 rounded-xl">
                        <span class="material-icons text-purple-600">attach_money</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="card bg-white p-6 mb-8">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select onchange="location.href='?status=' + this.value + '&method=<?php echo $paymentMethodFilter; ?>'" class="block w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                    <select onchange="location.href='?status=<?php echo $statusFilter; ?>&method=' + this.value" class="block w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                        <option value="all" <?php echo $paymentMethodFilter === 'all' ? 'selected' : ''; ?>>All Methods</option>
                        <option value="binance" <?php echo $paymentMethodFilter === 'binance' ? 'selected' : ''; ?>>Binance Pay</option>
                        <option value="tron" <?php echo $paymentMethodFilter === 'tron' ? 'selected' : ''; ?>>TRON</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Transactions Table -->
        <div class="card bg-white overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($transactions): ?>
                            <?php while ($tx = $transactions->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($tx['username']); ?></p>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($tx['email']); ?></p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-lg font-bold text-gray-900">
                                        $<?php echo formatCurrency($tx['amount']); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="method-badge method-<?php echo strtolower($tx['payment_method']); ?>">
                                            <?php echo strtoupper($tx['payment_method']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php if ($tx['payment_method'] === 'binance'): ?>
                                            <div>
                                                <p><strong>Order ID:</strong> <?php echo $tx['binance_order_id'] ?? 'N/A'; ?></p>
                                                <?php if ($tx['binance_txn_screenshot']): ?>
                                                    <a href="../<?php echo $tx['binance_txn_screenshot']; ?>" target="_blank" class="text-primary-600 hover:text-primary-800 text-xs">View Screenshot</a>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($tx['payment_method'] === 'tron'): ?>
                                            <div>
                                                <?php 
                                                $details = json_decode($tx['payment_details'], true);
                                                if ($details && isset($details['tron_txid'])):
                                                ?>
                                                    <p><strong>TXID:</strong> <code style="background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-size: 11px;"><?php echo substr($details['tron_txid'], 0, 20) . '...'; ?></code></p>
                                                    <a href="https://tronscan.org/#/transaction/<?php echo $details['tron_txid']; ?>" target="_blank" class="text-primary-600 hover:text-primary-800 text-xs">View on TronScan</a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="status-badge status-<?php echo strtolower($tx['status']); ?>">
                                            <?php echo ucfirst($tx['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php echo date('M d, Y', strtotime($tx['created_at'])); ?><br>
                                        <span class="text-xs text-gray-400"><?php echo date('H:i A', strtotime($tx['created_at'])); ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($tx['status'] === 'pending'): ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="transaction_id" value="<?php echo $tx['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn-approve text-xs" onclick="return confirm('Approve this deposit?')">Approve</button>
                                            </form>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="transaction_id" value="<?php echo $tx['id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn-reject text-xs" onclick="return confirm('Reject this deposit?')">Reject</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-sm">No action</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                    No deposits found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

<?php include 'admin-footer.php'; ?>
