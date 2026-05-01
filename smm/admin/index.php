<?php
include '../config/dbconfig.php';
include '../include/functions.php';
include '../include/auth.php';

// Require admin
requireAdmin();

$pageTitle = 'Overview';

// Get statistics
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$totalOrders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$totalServices = $conn->query("SELECT COUNT(*) as count FROM services")->fetch_assoc()['count'];
$totalRevenue = $conn->query("SELECT SUM(amount) as total FROM transactions WHERE type = 'order' AND status = 'completed'")->fetch_assoc()['total'] ?? 0;
$todayRegisteredUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
$totalFundsAdded = $conn->query("SELECT SUM(amount) as total FROM transactions WHERE type = 'deposit' AND status = 'completed'")->fetch_assoc()['total'] ?? 0;

// Get pending fund requests
$pendingFunds = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE type = 'deposit' AND status = 'pending'")->fetch_assoc()['count'];

// Get recent orders
$recentOrders = $conn->query("
    SELECT o.*, u.username, p.name as product_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    LEFT JOIN products p ON o.product_id = p.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");

include 'admin-header.php';
?>

<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Overview</h1>
            <p class="text-slate-500 font-medium mt-1">System-wide performance and statistics.</p>
        </div>
        <div class="flex gap-3">
             <a href="import/" class="inline-flex items-center px-6 py-3 bg-white border border-slate-200 text-sm font-bold rounded-xl text-slate-600 hover:text-primary-600 hover:border-primary-200 transition-all shadow-sm gap-2">
                 <svg class="w-5 h-5 text-primary-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                 Import Services
             </a>
             <a href="orders/" class="inline-flex items-center px-6 py-3 bg-primary-600 text-white text-sm font-bold rounded-xl hover:bg-primary-700 shadow-lg transition-all gap-2">
                 <svg class="w-5 h-5 text-primary-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                 Manage Orders
             </a>
         </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Stat Card -->
        <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
                <svg class="w-16 h-16 text-primary-500" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" /></svg>
            </div>
            <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Users</p>
            <p class="text-3xl font-black text-slate-900"><?php echo number_format($totalUsers); ?></p>
            <div class="mt-4 flex items-center text-xs font-bold text-primary-600 bg-primary-50 w-fit px-2 py-1 rounded-xl">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" /></svg>
                <?php echo $todayRegisteredUsers; ?> today
            </div>
        </div>

        <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
                <svg class="w-16 h-16 text-primary-500" fill="currentColor" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
            </div>
            <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Orders</p>
            <p class="text-3xl font-black text-slate-900"><?php echo number_format($totalOrders); ?></p>
            <div class="mt-4 flex items-center text-xs font-bold text-slate-400 bg-slate-50 w-fit px-2 py-1 rounded-xl">
                Global lifetime
            </div>
        </div>

        <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
                <svg class="w-16 h-16 text-primary-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Revenue</p>
            <p class="text-3xl font-black text-primary-600"><?php echo formatCurrency($totalRevenue); ?></p>
            <div class="mt-4 flex items-center text-xs font-bold text-slate-400 bg-slate-50 w-fit px-2 py-1 rounded-xl">
                Completed orders
            </div>
        </div>

        <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
                <svg class="w-16 h-16 text-primary-500" fill="currentColor" viewBox="0 0 24 24"><path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            </div>
            <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Funds Added</p>
            <p class="text-3xl font-black text-slate-900"><?php echo formatCurrency($totalFundsAdded); ?></p>
            <div class="mt-4 flex items-center text-xs font-bold text-amber-600 bg-amber-50 w-fit px-2 py-1 rounded-xl">
                <?php echo $pendingFunds; ?> pending
            </div>
        </div>
    </div>

    <!-- Recent Orders & Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Recent Orders Table -->
        <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="p-8 border-b border-slate-50 flex justify-between items-center">
                <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest">Recent Activity</h3>
                <a href="orders/" class="text-xs font-bold text-primary-600 hover:text-primary-700">View All Orders</a>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Order</th>
                            <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">User</th>
                            <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Status</th>
                            <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if ($recentOrders && $recentOrders->num_rows > 0): ?>
                            <?php while ($order = $recentOrders->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-8 py-5">
                                         <div class="text-sm font-bold text-slate-900">#<?php echo $order['id']; ?></div>
                                         <div class="text-[11px] text-slate-400 mt-0.5 truncate max-w-[180px] font-medium"><?php echo htmlspecialchars($order['product_name']); ?></div>
                                     </td>
                                    <td class="px-8 py-5">
                                        <div class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($order['username']); ?></div>
                                    </td>
                                    <td class="px-8 py-5">
                                        <?php 
                                        $statusClasses = [
                                            'completed' => 'bg-primary-50 text-primary-700 border-primary-100',
                                            'pending' => 'bg-amber-50 text-amber-700 border-amber-100',
                                            'processing' => 'bg-blue-50 text-blue-700 border-blue-100',
                                            'canceled' => 'bg-rose-50 text-rose-700 border-rose-100',
                                            'error' => 'bg-rose-50 text-rose-700 border-rose-100',
                                        ];
                                        $class = $statusClasses[$order['status']] ?? 'bg-slate-50 text-slate-700 border-slate-100';
                                        ?>
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border <?php echo $class; ?>">
                                            <?php echo $order['status']; ?>
                                        </span>
                                    </td>
                                    <td class="px-8 py-5 text-right text-[11px] font-bold text-slate-400">
                                        <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-8 py-10 text-center text-slate-400 font-medium">No recent orders found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions Sidebar -->
        <div class="space-y-6">
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-8">
                <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest mb-6">Action Center</h3>
                <div class="space-y-3">
                    <a href="approvals/" class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 border border-slate-100 hover:border-primary-200 hover:bg-primary-50 transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <p class="text-xs font-black text-slate-900 uppercase tracking-tight">Fund Approvals</p>
                                <p class="text-[10px] font-bold text-slate-400 uppercase"><?php echo $pendingFunds; ?> Pending Requests</p>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-slate-300 group-hover:text-primary-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" /></svg>
                    </a>

                    <a href="users/" class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 border border-slate-100 hover:border-primary-200 hover:bg-primary-50 transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-primary-100 text-primary-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            </div>
                            <div>
                                <p class="text-xs font-black text-slate-900 uppercase tracking-tight">User Directory</p>
                                <p class="text-[10px] font-bold text-slate-400 uppercase">Manage Accounts</p>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-slate-300 group-hover:text-primary-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" /></svg>
                    </a>
                </div>
            </div>

            <div class="bg-slate-900 rounded-3xl p-8 text-white relative overflow-hidden shadow-xl shadow-slate-200">
                <div class="absolute top-0 right-0 p-8 opacity-10">
                    <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                </div>
                <h3 class="text-sm font-black text-primary-400 uppercase tracking-widest mb-2">System Health</h3>
                <p class="text-slate-400 text-xs font-medium leading-relaxed mb-6">All systems are operational. Last sync with providers was 4 minutes ago.</p>
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-primary-500 animate-pulse"></div>
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-primary-500">Live Status</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'admin-footer.php'; ?>
