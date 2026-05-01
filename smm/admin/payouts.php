<?php
include '../config/dbconfig.php';
include '../include/functions.php';
include '../include/auth.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$pageTitle = "Manage Payouts - Admin Panel";
$alertMessage = '';
$alertType = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $payoutId = intval($_POST['payout_id']);
    $newStatus = sanitize($_POST['status']);
    $adminNote = sanitize($_POST['admin_note']);
    
    $sql = "UPDATE payout_requests SET status = ?, admin_note = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $newStatus, $adminNote, $payoutId);
    
    if ($stmt->execute()) {
        $alertMessage = "Payout status updated successfully!";
        $alertType = 'success';
        
        // If rejected, refund the amount to user
        if ($newStatus === 'rejected') {
            $sql = "SELECT user_id, amount FROM payout_requests WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $payoutId);
            $stmt->execute();
            $result = $stmt->get_result();
            $payout = $result->fetch_assoc();
            
            $sql = "UPDATE users SET balance = balance + ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("di", $payout['amount'], $payout['user_id']);
            $stmt->execute();
        }
    } else {
        $alertMessage = "Error updating payout status";
        $alertType = 'error';
    }
}

// Get filter
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';

// Build query
$sql = "SELECT pr.*, u.email, u.username FROM payout_requests pr 
        JOIN users u ON pr.user_id = u.id";

if ($statusFilter !== 'all') {
    $sql .= " WHERE pr.status = ?";
}

$sql .= " ORDER BY pr.created_at DESC";

$stmt = $conn->prepare($sql);
if ($statusFilter !== 'all') {
    $stmt->bind_param("s", $statusFilter);
}
$stmt->execute();
$payouts = $stmt->get_result();

// Get statistics
$stats = [
    'total' => 0,
    'pending' => 0,
    'completed' => 0,
    'rejected' => 0,
    'total_amount' => 0
];

$sql = "SELECT status, COUNT(*) as count, SUM(amount) as total FROM payout_requests GROUP BY status";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $stats[$row['status']] = $row['count'];
    $stats['total_amount'] += $row['total'];
}
$stats['total'] = array_sum([$stats['pending'], $stats['completed'], $stats['rejected']]);

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
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        
        .stat-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .status-processing {
            background-color: #dbeafe;
            color: #1e40af;
         }
         
         .status-completed {
             background-color: #dbeafe;
             color: #1e40af;
         }
         
         .status-rejected {
             background-color: #fee2e2;
             color: #991b1b;
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Payout Management</h1>
            <p class="text-gray-600 mt-2">Manage and process user payout requests</p>
        </div>

        <!-- Alert Messages -->
        <?php if ($alertMessage): ?>
        <div class="mb-6">
            <div class="<?php echo $alertType === 'success' ? 'bg-primary-50 text-primary-800 border-primary-200' : 'bg-red-50 text-red-800 border-red-200'; ?> border rounded-xl p-4">
                <?php echo htmlspecialchars($alertMessage); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="stat-card bg-white rounded-xl shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Requests</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total']; ?></p>
                    </div>
                    <div class="bg-primary-100 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-white rounded-xl shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Pending</p>
                        <p class="text-2xl font-bold text-yellow-600"><?php echo $stats['pending']; ?></p>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-white rounded-xl shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Completed</p>
                        <p class="text-2xl font-bold text-primary-600"><?php echo $stats['completed']; ?></p>
                    </div>
                    <div class="bg-primary-100 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-white rounded-xl shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Amount</p>
                        <p class="text-2xl font-bold text-gray-900">$<?php echo number_format($stats['total_amount'], 2); ?></p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow mb-6 p-4">
            <div class="flex flex-wrap gap-2">
                <a href="?status=all" class="px-4 py-2 rounded-xl <?php echo $statusFilter === 'all' ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    All
                </a>
                <a href="?status=pending" class="px-4 py-2 rounded-xl <?php echo $statusFilter === 'pending' ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    Pending
                </a>
                <a href="?status=processing" class="px-4 py-2 rounded-xl <?php echo $statusFilter === 'processing' ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    Processing
                </a>
                <a href="?status=completed" class="px-4 py-2 rounded-xl <?php echo $statusFilter === 'completed' ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    Completed
                </a>
                <a href="?status=rejected" class="px-4 py-2 rounded-xl <?php echo $statusFilter === 'rejected' ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    Rejected
                </a>
            </div>
        </div>

        <!-- Payout Requests Table -->
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <?php if ($payouts->num_rows > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while ($payout = $payouts->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #<?php echo $payout['id']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($payout['username']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($payout['email']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    $<?php echo number_format($payout['amount'], 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?php echo ucwords(str_replace('_', ' ', $payout['payment_method'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="status-badge status-<?php echo $payout['status']; ?>">
                                        <?php echo ucfirst($payout['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M d, Y H:i', strtotime($payout['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <button 
                                        onclick="openModal(<?php echo htmlspecialchars(json_encode($payout)); ?>)"
                                        class="text-primary-600 hover:text-primary-900 font-medium">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-gray-500">No payout requests found</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal -->
    <div id="payoutModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-start mb-6">
                    <h3 class="text-2xl font-bold text-gray-900">Payout Details</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div id="modalContent" class="mb-6">
                    <!-- Content will be loaded by JavaScript -->
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="payout_id" id="modalPayoutId">
                    <input type="hidden" name="update_status" value="1">
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-2">Update Status</label>
                        <select name="status" id="modalStatus" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:border-primary-500">
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-2">Admin Note</label>
                        <textarea name="admin_note" id="modalNote" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:border-primary-500" placeholder="Add a note (optional)"></textarea>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="closeModal()" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-6 py-2 bg-primary-500 text-white rounded-xl hover:bg-primary-600">
                            Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openModal(payout) {
            const modal = document.getElementById('payoutModal');
            const content = document.getElementById('modalContent');
            const statusSelect = document.getElementById('modalStatus');
            const noteTextarea = document.getElementById('modalNote');
            const payoutIdInput = document.getElementById('modalPayoutId');
            
            // Set payout ID
            payoutIdInput.value = payout.id;
            
            // Set current status
            statusSelect.value = payout.status;
            
            // Set admin note if exists
            noteTextarea.value = payout.admin_note || '';
            
            // Build content HTML
            content.innerHTML = `
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Payout ID</p>
                            <p class="font-medium">#${payout.id}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Amount</p>
                            <p class="font-medium text-primary-600">$${parseFloat(payout.amount).toFixed(2)}</p>
                        </div>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500">User</p>
                        <p class="font-medium">${payout.username} (${payout.email})</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500">Payment Method</p>
                        <p class="font-medium">${payout.payment_method.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500">Payment Details</p>
                        <p class="font-medium bg-gray-50 p-3 rounded-xl">${payout.payment_details}</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Created</p>
                            <p class="font-medium">${new Date(payout.created_at).toLocaleString()}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Updated</p>
                            <p class="font-medium">${payout.updated_at ? new Date(payout.updated_at).toLocaleString() : '-'}</p>
                        </div>
                    </div>
                    
                    ${payout.admin_note ? `
                    <div>
                        <p class="text-sm text-gray-500">Previous Admin Note</p>
                        <p class="font-medium bg-yellow-50 p-3 rounded-xl">${payout.admin_note}</p>
                    </div>
                    ` : ''}
                </div>
            `;
            
            modal.classList.remove('hidden');
        }
        
        function closeModal() {
            document.getElementById('payoutModal').classList.add('hidden');
        }
        
        // Close modal when clicking outside
        document.getElementById('payoutModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>

    <?php include 'admin-footer.php'; ?>
</body>
</html>
