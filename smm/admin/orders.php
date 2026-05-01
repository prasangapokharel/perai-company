<?php
include '../config/dbconfig.php';
include '../include/functions.php';
include '../include/auth.php';

// Require admin
requireAdmin();

$pageTitle = 'Manage Orders';
$alertMessage = '';
$alertType = '';

// Handle manual status check request
if (isset($_GET['check_status']) && $_GET['check_status'] == 1) {
    // Handle server-side status updates for pending orders
    $pendingOrders = $conn->query("
        SELECT id, api_order_id 
        FROM orders 
        WHERE status = 'pending' AND api_order_id IS NOT NULL
    ");
    $updatedCount = 0;
    $errors = [];
    
    if ($pendingOrders && $pendingOrders->num_rows > 0) {
        while ($order = $pendingOrders->fetch_assoc()) {
            $result = checkOrderStatusViaApi($order['id']);
            if ($result['success']) {
                $updatedCount++;
            } else {
                $errors[] = "Order {$order['id']}: {$result['message']}";
            }
        }
    }
    
    if ($updatedCount > 0) {
        $alertMessage = "✓ Updated status for $updatedCount pending order(s).";
        $alertType = 'success';
    } elseif (!empty($errors)) {
        $alertMessage = "Some orders failed to update: " . implode(', ', $errors);
        $alertType = 'error';
    } else {
        $alertMessage = "No pending orders found to check.";
        $alertType = 'info';
    }
}


// Get orders with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$statusWhere = $statusFilter ? "AND o.status = '$statusFilter'" : '';

$totalOrders = $conn->query("
    SELECT COUNT(*) as count 
    FROM orders o 
    WHERE 1=1 $statusWhere
")->fetch_assoc()['count'];

$totalPages = ceil($totalOrders / $limit);

$orders = $conn->query("
    SELECT o.*, u.username, s.name as service_name, p.name as provider_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    JOIN services s ON o.service_id = s.id 
    LEFT JOIN api_providers p ON o.api_provider_id = p.id 
    WHERE 1=1 $statusWhere
    ORDER BY o.created_at DESC 
    LIMIT $offset, $limit
");

include 'admin-header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold">Manage Orders</h1>
    </div>
    
    <div class="flex space-x-2">
        <a href="orders.php?check_status=1" class="px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded font-semibold transition flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 1119.4 4.65.75.75 0 11-1.46.3A5.5 5.5 0 117.5 9V4a1 1 0 011-1h-2a1 1 0 00-1 1v.01a1 1 0 001 1h1V4a1 1 0 00-1-1H4a1 1 0 00-1 1v2.101A1 1 0 014 4.1V3a1 1 0 00-1-1H2a1 1 0 00-1 1v1a1 1 0 001 1h1v.101A7.002 7.002 0 0119.4 6.65a.75.75 0 111.46-.3A5.5 5.5 0 110.5 11V9a1 1 0 01-1-1V7a1 1 0 011-1h2a1 1 0 001 1v.01a1 1 0 00-1 1H4z" clip-rule="evenodd" />
            </svg>
            Check All Orders
        </a>
    </div>
</div>

<div class="flex space-x-2 mb-6">
    <a href="orders.php" class="px-3 py-1 <?php echo !$statusFilter ? 'bg-teal-500 text-white' : 'bg-gray-200 text-gray-700'; ?> rounded-xl">All</a>
    <a href="orders.php?status=pending" class="px-3 py-1 <?php echo $statusFilter === 'pending' ? 'bg-teal-500 text-white' : 'bg-gray-200 text-gray-700'; ?> rounded-xl">Pending</a>
    <a href="orders.php?status=processing" class="px-3 py-1 <?php echo $statusFilter === 'processing' ? 'bg-teal-500 text-white' : 'bg-gray-200 text-gray-700'; ?> rounded-xl">Processing</a>
    <a href="orders.php?status=completed" class="px-3 py-1 <?php echo $statusFilter === 'completed' ? 'bg-teal-500 text-white' : 'bg-gray-200 text-gray-700'; ?> rounded-xl">Completed</a>
    <a href="orders.php?status=canceled" class="px-3 py-1 <?php echo $statusFilter === 'canceled' ? 'bg-teal-500 text-white' : 'bg-gray-200 text-gray-700'; ?> rounded-xl">Canceled</a>
    <a href="orders.php?status=error" class="px-3 py-1 <?php echo $statusFilter === 'error' ? 'bg-teal-500 text-white' : 'bg-gray-200 text-gray-700'; ?> rounded-xl">Error</a>
</div>

<?php if ($alertMessage): ?>
    <div id="alertMessage" class="mb-6 p-4 rounded <?php echo $alertType == 'success' ? 'bg-primary-100 text-primary-800' : 'bg-red-100 text-red-800'; ?>">
        <?php echo $alertMessage; ?>
    </div>
<?php endif; ?>

<div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
    <?php if ($orders && $orders->num_rows > 0): ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-gray">
                        <th class="pb-3">ID</th>
                        <th class="pb-3">User</th>
                        <th class="pb-3">Service</th>
                        <th class="pb-3">Link</th>
                        <th class="pb-3">Quantity</th>
                        <th class="pb-3">Price</th>
                        <th class="pb-3">API Provider</th>
                        <th class="pb-3">API Order ID</th>
                        <th class="pb-3">Status</th>
                        <th class="pb-3">Date</th>
                        <th class="pb-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $orders->fetch_assoc()): ?>
                        <tr class="border-t border-gray-100">
                            <td class="py-3"><?php echo $order['id']; ?></td>
                            <td class="py-3"><?php echo $order['username']; ?></td>
                            <td class="py-3"><?php echo $order['service_name']; ?></td>
                            <td class="py-3">
                                <a href="<?php echo $order['link']; ?>" target="_blank" class="text-primary-500 hover:underline truncate block max-w-[150px]">
                                    <?php echo $order['link']; ?>
                                </a>
                            </td>
                            <td class="py-3"><?php echo $order['quantity']; ?></td>
                            <td class="py-3"><?php echo formatCurrency($order['price']); ?></td>
                            <td class="py-3"><?php echo $order['provider_name'] ?: 'N/A'; ?></td>
                            <td class="py-3"><?php echo $order['api_order_id'] ?: 'N/A'; ?></td>
                            <td class="py-3">
                                <span class="px-2 py-1 rounded text-xs 
                                    <?php 
                                    switch ($order['status']) {
                                        case 'completed':
                                            echo 'bg-primary-100 text-primary-800';
                                            break;
                                        case 'pending':
                                            echo 'bg-yellow-100 text-yellow-800';
                                            break;
                                        case 'processing':
                                            echo 'bg-primary-100 text-primary-800';
                                            break;
                                        case 'canceled':
                                        case 'error':
                                            echo 'bg-red-100 text-red-800';
                                            break;
                                        default:
                                            echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td class="py-3"><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                            <td class="py-3">
                                <div class="flex space-x-2">
                                    <a href="view_order.php?id=<?php echo $order['id']; ?>" class="px-2 py-1 bg-teal-500 text-white rounded hover:bg-teal-600 text-xs">
                                        View
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="flex justify-center mt-6">
                <div class="flex space-x-1">
                    <?php if ($page > 1): ?>
                        <a href="orders.php?page=<?php echo $page - 1; ?><?php echo $statusFilter ? "&status=$statusFilter" : ''; ?>" class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                            « Prev
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="orders.php?page=<?php echo $i; ?><?php echo $statusFilter ? "&status=$statusFilter" : ''; ?>" class="px-3 py-1 <?php echo $i === $page ? 'bg-teal-500 text-white' : 'bg-gray-200 text-gray-700'; ?> rounded hover:bg-gray-300">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="orders.php?page=<?php echo $page + 1; ?><?php echo $statusFilter ? "&status=$statusFilter" : ''; ?>" class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                            Next »
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <p class="text-gray text-center py-4">No orders found</p>
    <?php endif; ?>
</div>

<?php include 'admin-footer.php'; ?>