<?php
include '../config/dbconfig.php';
include '../include/functions.php';
include '../include/auth.php';

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

include 'admin-header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Users Management</h1>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6 md:mb-8">
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <h2 class="text-gray-500 text-sm mb-2">Total Users</h2>
        <p class="text-2xl font-bold"><?php echo $totalUsers; ?></p>
    </div>

    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <h2 class="text-gray-500 text-sm mb-2">Users Registered Today</h2>
        <p class="text-2xl font-bold"><?php echo $todayUsers; ?></p>
    </div>

    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <h2 class="text-gray-500 text-sm mb-2">Users Registered This Week</h2>
        <p class="text-2xl font-bold"><?php echo $thisWeekUsers; ?></p>
    </div>

    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <h2 class="text-gray-500 text-sm mb-2">Users Registered This Month</h2>
        <p class="text-2xl font-bold"><?php echo $thisMonthUsers; ?></p>
    </div>
</div>

<div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
    <h2 class="text-lg font-semibold mb-4">Users List</h2>

    <?php if ($users && $users->num_rows > 0): ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-gray-500">
                        <th class="pb-3">ID</th>
                        <th class="pb-3">Username</th>
                        <th class="pb-3">Email</th>
                        <th class="pb-3">Balance</th>
                        <th class="pb-3">Admin</th>
                        <th class="pb-3">Affiliate Code</th>
                        <th class="pb-3">Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <tr class="border-t border-gray-100">
                            <td class="py-3"><?php echo $user['id']; ?></td>
                            <td class="py-3"><?php echo $user['username']; ?></td>
                            <td class="py-3"><?php echo $user['email']; ?></td>
                            <td class="py-3"><?php echo formatCurrency($user['balance']); ?></td>
                            <td class="py-3"><?php echo $user['is_admin'] ? 'Yes' : 'No'; ?></td>
                            <td class="py-3"><?php echo $user['affiliate_code']; ?></td>
                            <td class="py-3"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-gray-500 text-center py-4">No users found</p>
    <?php endif; ?>
</div>

<?php include 'admin-footer.php'; ?>