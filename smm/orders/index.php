<?php
include '../config/dbconfig.php';
include '../include/functions.php';
include '../include/auth.php';

// Require login
requireLogin();

$pageTitle = 'My Orders';
$userId = $_SESSION['user_id'];

// Get status filter from URL
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build the WHERE clause for status filtering
$whereClause = "o.user_id = $userId";
if ($statusFilter) {
    $whereClause .= " AND o.status = '$statusFilter'";
}

// Get total orders count for pagination
$totalOrdersQuery = $conn->query("
    SELECT COUNT(*) as count 
    FROM orders o 
    WHERE $whereClause
");
$totalOrders = $totalOrdersQuery->fetch_assoc()['count'];
$totalPages = ceil($totalOrders / $limit);

// Get orders with pagination
$ordersQuery = $conn->query("
    SELECT o.*, 
           COALESCE(p.name, 'Unknown Product') as product_name,
           COALESCE(p.price, 0) as product_price
    FROM orders o 
    LEFT JOIN products p ON o.product_id = p.id 
    WHERE $whereClause 
    ORDER BY o.created_at DESC 
    LIMIT $offset, $limit
");

// Store orders in array to avoid consuming result set twice
$orders = [];
if ($ordersQuery && $ordersQuery->num_rows > 0) {
    while ($order = $ordersQuery->fetch_assoc()) {
        $orders[] = $order;
    }
}

include '../include/user-layout-start.php';
?>

<div class="space-y-4 md:space-y-6 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="w-full sm:w-auto">
            <h1 class="text-3xl md:text-4xl font-display text-gray-900 tracking-tight uppercase">Orders</h1>
            <p class="text-xs md:text-sm text-gray-500 mt-1 uppercase tracking-widest font-bold">Track and manage your service orders</p>
        </div>
        <a href="../order/" class="inline-flex items-center justify-center px-4 py-2 text-sm font-bold rounded-xl text-white bg-primary-600 hover:bg-primary-700 shadow-md hover:shadow-lg transition-all duration-200 whitespace-nowrap">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            New Order
        </a>
    </div>

    <!-- Status Filters - Horizontal Scroll on Mobile -->
    <div class="overflow-x-auto scrollbar-hide -mx-4 sm:mx-0 px-4 sm:px-0">
        <nav class="flex gap-2 sm:gap-3 whitespace-nowrap">
            <?php
            $tabs = [
                '' => 'All Orders',
                'pending' => 'Pending',
                'processing' => 'Processing',
                'completed' => 'Completed',
                'canceled' => 'Canceled',
                'error' => 'Error'
            ];
            foreach ($tabs as $key => $label):
                $isActive = $statusFilter === $key;
            ?>
            <a href="?<?php echo $key ? "status=$key" : ''; ?>" 
               class="px-4 sm:px-5 py-2 sm:py-2.5 text-xs sm:text-sm font-bold rounded-xl transition-all duration-200 flex-shrink-0 <?php echo $isActive ? 'bg-primary-600 text-white shadow-md shadow-primary-200' : 'bg-white text-slate-600 border border-slate-200 hover:border-slate-300 hover:bg-slate-50'; ?>">
                <?php echo $label; ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <!-- Orders Table - Responsive -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <!-- Mobile Card View -->
        <div class="block sm:hidden">
            <?php if (!empty($orders)): ?>
                <?php foreach ($orders as $order): ?>
                    <div class="border-b border-slate-100 p-4 hover:bg-slate-50/50 transition-colors">
                        <!-- ID and Status -->
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Order ID</p>
                                <p class="text-sm font-bold text-slate-900">#<?php echo $order['id']; ?></p>
                            </div>
                            <?php 
                            $statusClasses = [
                                'completed' => 'bg-primary-50 text-primary-700 border-primary-100',
                                'pending' => 'bg-amber-50 text-amber-700 border-amber-100',
                                'processing' => 'bg-blue-50 text-blue-700 border-blue-100',
                                'canceled' => 'bg-rose-50 text-rose-700 border-rose-100',
                                'error' => 'bg-rose-50 text-rose-700 border-rose-100'
                            ];
                            $statusClass = $statusClasses[$order['status']] ?? 'bg-slate-50 text-slate-700 border-slate-100';
                            ?>
                            <span class="px-2.5 py-1 text-[10px] leading-4 font-black uppercase tracking-widest rounded-full border <?php echo $statusClass; ?>">
                                <?php echo $order['status']; ?>
                            </span>
                        </div>
                        
                        <!-- Service -->
                         <div class="mb-3">
                             <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Product</p>
                             <p class="text-sm font-bold text-slate-900 break-words"><?php echo htmlspecialchars(substr($order['product_name'], 0, 50)); ?></p>
                         </div>
                        
                        <!-- Link -->
                        <div class="mb-3">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Link</p>
                            <a href="<?php echo htmlspecialchars($order['link']); ?>" target="_blank" class="text-xs font-medium text-primary-600 hover:text-primary-700 break-all hover:underline">
                                <?php echo htmlspecialchars(substr($order['link'], 0, 40) . '...'); ?>
                            </a>
                        </div>
                        
                        <!-- Quantity & Price -->
                        <div class="flex gap-4 mb-3">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-0.5">Quantity</p>
                                <p class="text-sm font-bold text-slate-700"><?php echo number_format($order['quantity']); ?></p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-0.5">Price</p>
                                <p class="text-sm font-black text-slate-900"><?php echo formatCurrency($order['price']); ?></p>
                            </div>
                        </div>
                        
                        <!-- Date -->
                        <div class="mb-3">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Date</p>
                            <p class="text-xs font-medium text-slate-500"><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
                        </div>
                        
                        <!-- Action -->
                        <a href="../order-details/?id=<?php echo $order['id']; ?>" class="w-full inline-flex items-center justify-center px-4 py-2 bg-slate-100 text-slate-600 text-xs font-bold rounded-xl hover:bg-primary-600 hover:text-white transition-all duration-200">
                            View Details
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="px-4 py-12 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <h3 class="text-sm font-medium text-gray-900">No orders found</h3>
                    <p class="text-xs text-gray-500 mt-1">Get started by creating a new order.</p>
                    <div class="mt-4">
                        <a href="../order/" class="inline-flex items-center px-3 py-2 text-xs font-bold text-white bg-primary-600 hover:bg-primary-700 rounded-xl transition-colors">
                            Create New Order
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Desktop Table View -->
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-4 lg:px-6 py-3 lg:py-4 text-left text-[10px] lg:text-[11px] font-bold text-slate-400 uppercase tracking-widest">ID</th>
                        <th scope="col" class="px-4 lg:px-6 py-3 lg:py-4 text-left text-[10px] lg:text-[11px] font-bold text-slate-400 uppercase tracking-widest">Service</th>
                        <th scope="col" class="px-4 lg:px-6 py-3 lg:py-4 text-left text-[10px] lg:text-[11px] font-bold text-slate-400 uppercase tracking-widest hidden lg:table-cell">Link</th>
                        <th scope="col" class="px-4 lg:px-6 py-3 lg:py-4 text-left text-[10px] lg:text-[11px] font-bold text-slate-400 uppercase tracking-widest">Qty</th>
                        <th scope="col" class="px-4 lg:px-6 py-3 lg:py-4 text-left text-[10px] lg:text-[11px] font-bold text-slate-400 uppercase tracking-widest">Price</th>
                        <th scope="col" class="px-4 lg:px-6 py-3 lg:py-4 text-left text-[10px] lg:text-[11px] font-bold text-slate-400 uppercase tracking-widest">Status</th>
                        <th scope="col" class="px-4 lg:px-6 py-3 lg:py-4 text-left text-[10px] lg:text-[11px] font-bold text-slate-400 uppercase tracking-widest hidden lg:table-cell">Date</th>
                        <th scope="col" class="px-4 lg:px-6 py-3 lg:py-4 text-right text-[10px] lg:text-[11px] font-bold text-slate-400 uppercase tracking-widest">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-50">
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-xs lg:text-sm font-semibold text-slate-400">
                                    #<?php echo $order['id']; ?>
                                </td>
                                <td class="px-4 lg:px-6 py-4 text-xs lg:text-sm">
                                     <div class="font-bold text-slate-900 truncate max-w-[120px] lg:max-w-xs" title="<?php echo htmlspecialchars($order['product_name']); ?>">
                                         <?php echo htmlspecialchars($order['product_name']); ?>
                                     </div>
                                 </td>
                                <td class="px-4 lg:px-6 py-4 hidden lg:table-cell">
                                    <a href="<?php echo htmlspecialchars($order['link']); ?>" target="_blank" class="text-xs font-medium text-primary-600 hover:text-primary-700 hover:underline max-w-xs truncate block" title="<?php echo htmlspecialchars($order['link']); ?>">
                                        <?php echo htmlspecialchars(substr($order['link'], 0, 40)); ?>...
                                    </a>
                                </td>
                                <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-xs lg:text-sm font-bold text-slate-700">
                                    <?php echo number_format($order['quantity']); ?>
                                </td>
                                <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-xs lg:text-sm font-black text-slate-900">
                                    <?php echo formatCurrency($order['price']); ?>
                                </td>
                                <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                    <?php 
                                    $statusClasses = [
                                        'completed' => 'bg-primary-50 text-primary-700 border-primary-100',
                                        'pending' => 'bg-amber-50 text-amber-700 border-amber-100',
                                        'processing' => 'bg-blue-50 text-blue-700 border-blue-100',
                                        'canceled' => 'bg-rose-50 text-rose-700 border-rose-100',
                                        'error' => 'bg-rose-50 text-rose-700 border-rose-100'
                                    ];
                                    $statusClass = $statusClasses[$order['status']] ?? 'bg-slate-50 text-slate-700 border-slate-100';
                                    ?>
                                    <span class="px-2.5 lg:px-3 py-1 inline-flex text-[9px] lg:text-[10px] leading-4 font-black uppercase tracking-widest rounded-full border <?php echo $statusClass; ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                                <td class="px-4 lg:px-6 py-4 whitespace-nowrap hidden lg:table-cell text-[11px] font-medium text-slate-500">
                                    <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                </td>
                                <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-right text-xs font-bold">
                                    <a href="../order-details/?id=<?php echo $order['id']; ?>" class="inline-flex items-center px-3 py-1.5 bg-slate-100 text-slate-600 rounded-xl hover:bg-primary-600 hover:text-white transition-all duration-200 font-bold">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="px-4 lg:px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <h3 class="text-sm font-medium text-gray-900">No orders found</h3>
                                <p class="text-xs text-gray-500 mt-1">Get started by creating a new order.</p>
                                <div class="mt-4">
                                    <a href="../order/" class="inline-flex items-center px-4 py-2 text-xs font-bold text-white bg-primary-600 hover:bg-primary-700 rounded-xl transition-colors">
                                        Create New Order
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="bg-white px-4 sm:px-6 py-4 flex flex-col sm:flex-row items-center justify-between border-t border-slate-200 gap-4">
            <div class="text-xs sm:text-sm text-slate-600 text-center sm:text-left">
                Showing <span class="font-bold"><?php echo $offset + 1; ?></span> to <span class="font-bold"><?php echo min($offset + $limit, $totalOrders); ?></span> of <span class="font-bold"><?php echo $totalOrders; ?></span> results
            </div>
            <div>
                <nav class="flex gap-1 sm:gap-2" aria-label="Pagination">
                    <!-- Previous -->
                    <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo $statusFilter ? "&status=$statusFilter" : ''; ?>" class="px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-xl border border-slate-300 bg-white text-slate-600 text-xs sm:text-sm font-bold hover:bg-slate-50 transition-colors">
                        Prev
                    </a>
                    <?php endif; ?>

                    <!-- Numbers -->
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo $statusFilter ? "&status=$statusFilter" : ''; ?>" class="px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-xl text-xs sm:text-sm font-bold transition-colors <?php echo $i === $page ? 'bg-primary-600 text-white border border-primary-600' : 'bg-white text-slate-600 border border-slate-300 hover:bg-slate-50'; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>

                    <!-- Next -->
                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo $statusFilter ? "&status=$statusFilter" : ''; ?>" class="px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-xl border border-slate-300 bg-white text-slate-600 text-xs sm:text-sm font-bold hover:bg-slate-50 transition-colors">
                        Next
                    </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../include/user-layout-end.php'; ?>
