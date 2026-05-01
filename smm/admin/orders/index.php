<?php
include '../../config/dbconfig.php';
include '../../include/functions.php';
include '../../include/auth.php';

// Require admin
requireAdmin();

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

$pageTitle = 'Manage Orders';
$alertMessage = '';
$alertType = '';

if ($updatedCount > 0) {
    $alertMessage = "Updated status for $updatedCount pending order(s).";
    $alertType = 'success';
} elseif (!empty($errors)) {
    $alertMessage = "Some orders failed to update: " . implode(', ', $errors);
    $alertType = 'error';
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
    SELECT o.*, u.username, p.name as product_name, pr.name as provider_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    JOIN products p ON o.product_id = p.id 
    LEFT JOIN api_providers pr ON o.api_provider_id = pr.id 
    WHERE 1=1 $statusWhere
    ORDER BY o.created_at DESC 
    LIMIT $offset, $limit
");

include '../../include/admin-layout-start.php';
?>

<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Order Management</h1>
            <p class="text-slate-500 font-medium mt-1">Monitor and track all customer orders across the platform.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <?php 
            $filters = [
                ['label' => 'All', 'val' => ''],
                ['label' => 'Pending', 'val' => 'pending'],
                ['label' => 'Processing', 'val' => 'processing'],
                ['label' => 'Completed', 'val' => 'completed'],
                ['label' => 'Canceled', 'val' => 'canceled'],
            ];
            foreach ($filters as $f):
                $active = ($statusFilter === $f['val']);
            ?>
                <a href="index.php?status=<?php echo $f['val']; ?>" 
                   class="px-6 py-3 text-xs font-black uppercase tracking-widest rounded-xl transition-all <?php echo $active ? 'bg-primary-600 text-white shadow-lg shadow-primary-200' : 'bg-white text-slate-500 border border-slate-200 hover:border-slate-300'; ?>">
                    <?php echo $f['label']; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($alertMessage): ?>
        <div class="p-4 rounded-2xl flex items-center shadow-sm border <?php echo $alertType == 'success' ? 'bg-primary-50 text-primary-700 border-primary-100' : 'bg-rose-50 text-rose-700 border-rose-100'; ?>">
            <p class="text-sm font-bold uppercase tracking-tight"><?php echo $alertMessage; ?></p>
        </div>
    <?php endif; ?>

    <!-- Orders Table -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Order</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Customer</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Service Details</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Price</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Status</th>
                        <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if ($orders && $orders->num_rows > 0): ?>
                        <?php while ($order = $orders->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-8 py-5">
                                    <div class="text-sm font-black text-slate-900">#<?php echo $order['id']; ?></div>
                                    <div class="text-[10px] text-slate-400 mt-1 font-bold"><?php echo date('M d, H:i', strtotime($order['created_at'])); ?></div>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="h-8 w-8 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center text-xs font-black uppercase">
                                            <?php echo substr($order['username'], 0, 1); ?>
                                        </div>
                                        <span class="text-sm font-bold text-slate-700"><?php echo htmlspecialchars($order['username']); ?></span>
                                    </div>
                                 </td>
                                 <td class="px-8 py-5">
                                     <div class="text-sm font-bold text-slate-900 truncate max-w-[250px]"><?php echo htmlspecialchars($order['product_name']); ?></div>
                                    <div class="flex items-center mt-1.5 gap-2">
                                        <svg class="h-3 w-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                        <a href="<?php echo $order['link']; ?>" target="_blank" class="text-[10px] text-primary-500 hover:text-primary-600 font-black truncate max-w-[180px] uppercase tracking-tighter">
                                            View Target
                                        </a>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="text-sm font-black text-slate-900"><?php echo formatCurrency($order['price']); ?></div>
                                    <div class="text-[10px] font-bold text-slate-400 mt-0.5"><?php echo number_format($order['quantity']); ?> units</div>
                                </td>
                                <td class="px-8 py-5">
                                    <?php 
                                    $statusClasses = [
                                        'completed' => 'bg-primary-50 text-primary-700 border-primary-100',
                                        'pending' => 'bg-amber-50 text-amber-700 border-amber-100',
                                        'processing' => 'bg-primary-50 text-primary-700 border-primary-100',
                                        'canceled' => 'bg-rose-50 text-rose-700 border-rose-100',
                                        'error' => 'bg-rose-50 text-rose-700 border-rose-100',
                                    ];
                                    $class = $statusClasses[$order['status']] ?? 'bg-slate-50 text-slate-700 border-slate-100';
                                    ?>
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border <?php echo $class; ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <a href="view.php?id=<?php echo $order['id']; ?>" class="inline-flex items-center px-4 py-2 bg-slate-50 text-slate-600 text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-primary-600 hover:text-white transition-all">
                                        Details
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-8 py-12 text-center text-slate-400 font-bold uppercase text-xs tracking-widest italic">No orders found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="flex justify-center mt-12">
            <nav class="flex items-center gap-2">
                <?php if ($page > 1): ?>
                    <a href="index.php?page=<?php echo $page - 1; ?><?php echo $statusFilter ? "&status=$statusFilter" : ''; ?>" class="p-3 bg-white border border-slate-200 text-slate-400 rounded-xl hover:text-primary-600 hover:border-primary-200 transition-all shadow-sm">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                    </a>
                <?php endif; ?>
                
                <?php 
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);
                for ($i = $start; $i <= $end; $i++): 
                ?>
                    <a href="index.php?page=<?php echo $i; ?><?php echo $statusFilter ? "&status=$statusFilter" : ''; ?>" class="w-11 h-11 flex items-center justify-center rounded-xl text-sm font-black transition-all <?php echo $i === $page ? 'bg-primary-600 text-white shadow-lg shadow-primary-200' : 'bg-white text-slate-400 border border-slate-200 hover:border-primary-200 hover:text-primary-600 shadow-sm'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="index.php?page=<?php echo $page + 1; ?><?php echo $statusFilter ? "&status=$statusFilter" : ''; ?>" class="p-3 bg-white border border-slate-200 text-slate-400 rounded-xl hover:text-primary-600 hover:border-primary-200 transition-all shadow-sm">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php include '../../include/admin-layout-end.php'; ?>
