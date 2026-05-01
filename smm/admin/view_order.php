<?php
include '../config/dbconfig.php';
include '../include/functions.php';
include '../include/auth.php';

// Require admin
requireAdmin();

$pageTitle = 'View Order';
$alertMessage = '';
$alertType = '';

// Get order ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: orders.php");
    exit;
}

$orderId = intval($_GET['id']);

// Process status update request
if (isset($_POST['update_status'])) {
    $newStatus = sanitize($_POST['status']);
    
    if ($conn->query("UPDATE orders SET status = '$newStatus' WHERE id = $orderId")) {
        $alertMessage = 'Order status updated successfully';
        $alertType = 'success';
    } else {
        $alertMessage = 'Failed to update order status: ' . $conn->error;
        $alertType = 'error';
    }
}

// Process check status request
if (isset($_POST['check_status'])) {
    $result = checkOrderStatusViaApi($orderId);
    
    if ($result['success']) {
        $alertMessage = "Order status updated successfully to: " . ucfirst($result['status']);
        $alertType = 'success';
    } else {
        $alertMessage = 'Failed to check order status: ' . $result['message'];
        $alertType = 'error';
    }
}

// Get order details
$order = $conn->query("
    SELECT o.*, u.username, s.name as service_name, s.description as service_description, p.name as provider_name, p.url as provider_url 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    JOIN services s ON o.service_id = s.id 
    LEFT JOIN api_providers p ON o.api_provider_id = p.id 
    WHERE o.id = $orderId
")->fetch_assoc();

if (!$order) {
    header("Location: orders.php");
    exit;
}

include 'admin-header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Order #<?php echo $order['id']; ?></h1>
    <a href="orders.php" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Back to Orders</a>
</div>

<?php if ($alertMessage): ?>
    <div class="mb-6 p-4 rounded <?php echo $alertType == 'success' ? 'bg-primary-100 text-primary-800' : 'bg-red-100 text-red-800'; ?>">
        <?php echo $alertMessage; ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="md:col-span-2">
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm mb-6">
            <h2 class="text-lg font-semibold mb-4">Order Details</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="mb-2"><strong>Order ID:</strong> <?php echo $order['id']; ?></p>
                    <p class="mb-2"><strong>User:</strong> <?php echo $order['username']; ?></p>
                    <p class="mb-2"><strong>Service:</strong> <?php echo $order['service_name']; ?></p>
                    <p class="mb-2"><strong>Quantity:</strong> <?php echo $order['quantity']; ?></p>
                    <p class="mb-2"><strong>Price:</strong> <?php echo formatCurrency($order['price']); ?></p>
                </div>
                
                <div>
                    <p class="mb-2"><strong>Status:</strong> 
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
                    </p>
                    <p class="mb-2"><strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
                    <p class="mb-2"><strong>API Provider:</strong> <?php echo $order['provider_name'] ?: 'N/A'; ?></p>
                    <p class="mb-2"><strong>API Order ID:</strong> <?php echo $order['api_order_id'] ?: 'N/A'; ?></p>
                    <p class="mb-2">
                        <strong>Link:</strong> 
                        <a href="<?php echo $order['link']; ?>" target="_blank" class="text-primary-500 hover:underline break-all">
                            <?php echo $order['link']; ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <h2 class="text-lg font-semibold mb-4">Service Description</h2>
            <div class="p-4 bg-gray-50 rounded-xl">
                <?php echo $order['service_description'] ?: 'No description available'; ?>
            </div>
        </div>
    </div>
    
    <div>
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm mb-6">
            <h2 class="text-lg font-semibold mb-4">Actions</h2>
            
            <form action="view_order.php?id=<?php echo $orderId; ?>" method="post" class="mb-4">
                <div class="mb-4">
                    <label for="status" class="block text-gray mb-2">Update Status</label>
                    <select id="status" name="status" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:border-teal-500">
                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="canceled" <?php echo $order['status'] === 'canceled' ? 'selected' : ''; ?>>Canceled</option>
                        <option value="error" <?php echo $order['status'] === 'error' ? 'selected' : ''; ?>>Error</option>
                    </select>
                </div>
                
                <button type="submit" name="update_status" class="w-full bg-teal-500 text-white py-2 rounded hover:bg-teal-600">Update Status</button>
            </form>
            
            <?php if ($order['api_order_id']): ?>
                <form action="view_order.php?id=<?php echo $orderId; ?>" method="post">
                    <button type="submit" name="check_status" class="w-full bg-primary-500 text-white py-2 rounded hover:bg-primary-600">Check API Status</button>
                </form>
            <?php endif; ?>
        </div>
        
        <?php if ($order['api_response']): ?>
            <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                <h2 class="text-lg font-semibold mb-4">API Response</h2>
                <div class="p-4 bg-gray-50 rounded overflow-x-auto">
                    <pre class="text-xs"><?php echo json_encode(json_decode($order['api_response']), JSON_PRETTY_PRINT); ?></pre>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'admin-footer.php'; ?>