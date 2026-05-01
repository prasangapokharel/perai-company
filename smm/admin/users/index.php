<?php
include '../../config/dbconfig.php';
include '../../include/functions.php';
include '../../include/auth.php';

// Require admin
requireAdmin();

$pageTitle = 'Users Management';

// Get total users count
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];

// Get users registered today
$todayUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];

// Get users registered this week
$thisWeekUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE created_at >= CURDATE() - INTERVAL (DAYOFWEEK(CURDATE())+6) DAY")->fetch_assoc()['count'];

// Get users registered this month
$thisMonthUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetch_assoc()['count'];

// Get all users
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");

include '../../include/admin-layout-start.php';
?>

<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">User Management</h1>
            <p class="text-slate-500 font-medium mt-1">Manage platform accounts, monitor growth, and adjust balances.</p>
        </div>
        <div>
             <a href="#" class="inline-flex items-center px-6 py-3 bg-primary-600 text-white text-sm font-bold rounded-xl hover:bg-primary-700 shadow-lg shadow-primary-200 transition-all">
                Export Users
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Users</p>
            <p class="text-2xl font-black text-slate-900"><?php echo number_format($totalUsers); ?></p>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">New Today</p>
            <p class="text-2xl font-black text-primary-600"><?php echo number_format($todayUsers); ?></p>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">This Week</p>
            <p class="text-2xl font-black text-primary-600"><?php echo number_format($thisWeekUsers); ?></p>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">This Month</p>
            <p class="text-2xl font-black text-rose-600"><?php echo number_format($thisMonthUsers); ?></p>
        </div>
    </div>

    <!-- User Table -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="p-8 border-b border-slate-50">
            <h2 class="text-sm font-black text-slate-900 uppercase tracking-widest">User Directory</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">User Details</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Email Address</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Balance</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Role</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Registered</th>
                        <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if ($users && $users->num_rows > 0): ?>
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center text-xs font-black uppercase">
                                            <?php echo substr($user['username'], 0, 1); ?>
                                        </div>
                                        <div>
                                            <div class="text-sm font-black text-slate-900"><?php echo htmlspecialchars($user['username']); ?></div>
                                            <div class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">ID: #<?php echo $user['id']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5 text-sm font-bold text-slate-600"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="px-8 py-5 text-sm font-black text-slate-900"><?php echo formatCurrency($user['balance']); ?></td>
                                <td class="px-8 py-5">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border <?php echo $user['is_admin'] ? 'bg-indigo-50 text-indigo-700 border-indigo-100' : 'bg-slate-50 text-slate-700 border-slate-100'; ?>">
                                        <?php echo $user['is_admin'] ? 'Administrator' : 'Standard User'; ?>
                                    </span>
                                </td>
                                <td class="px-8 py-5 text-sm font-bold text-slate-400">
                                    <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button class="p-2 text-slate-400 hover:text-primary-600 transition-colors" title="Edit User">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </button>
                                        <button class="p-2 text-slate-400 hover:text-rose-600 transition-colors" title="Restrict Access">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-8 py-12 text-center text-slate-400 font-bold uppercase text-xs tracking-widest italic">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../include/admin-layout-end.php'; ?>
