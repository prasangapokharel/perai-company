<?php
// Admin Layout Header
// This file should be included at the start of all admin pages

// Database & Functions
require_once __DIR__ . '/../config/dbconfig.php';
require_once __DIR__ . '/../include/functions.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: /");
    exit;
}

// Get current page
$currentPage = basename($_SERVER['PHP_SELF']);
$pageTitle = $pageTitle ?? 'Admin Panel';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; }
        .sidebar-link.active { background: #16a34a; color: white; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r">
            <div class="p-4 border-b">
                <h2 class="text-xl font-bold text-primary-600">Admin Panel</h2>
            </div>
            <nav class="p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="/admin/" class="sidebar-link block px-4 py-2 rounded hover:bg-gray-100 <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="/admin/users.php" class="sidebar-link block px-4 py-2 rounded hover:bg-gray-100 <?php echo $currentPage === 'users.php' ? 'active' : ''; ?>">
                            Users
                        </a>
                    </li>
                    <li>
                        <a href="/admin/orders.php" class="sidebar-link block px-4 py-2 rounded hover:bg-gray-100 <?php echo $currentPage === 'orders.php' ? 'active' : ''; ?>">
                            Orders
                        </a>
                    </li>
                    <li>
                        <a href="/admin/payouts.php" class="sidebar-link block px-4 py-2 rounded hover:bg-gray-100 <?php echo $currentPage === 'payouts.php' ? 'active' : ''; ?>">
                            Payouts
                        </a>
                    </li>
                    <li>
                        <a href="/admin/deposits.php" class="sidebar-link block px-4 py-2 rounded hover:bg-gray-100 <?php echo $currentPage === 'deposits.php' ? 'active' : ''; ?>">
                            Deposits
                        </a>
                    </li>
                    <li>
                        <a href="/admin/approvefund.php" class="sidebar-link block px-4 py-2 rounded hover:bg-gray-100 <?php echo $currentPage === 'approvefund.php' ? 'active' : ''; ?>">
                            Approve Funds
                        </a>
                    </li>
                    <li>
                        <a href="/admin/withdrawals.php" class="sidebar-link block px-4 py-2 rounded hover:bg-gray-100 <?php echo $currentPage === 'withdrawals.php' ? 'active' : ''; ?>">
                            Withdrawals
                        </a>
                    </li>
                    <li>
                        <a href="/admin/announcements.php" class="sidebar-link block px-4 py-2 rounded hover:bg-gray-100 <?php echo $currentPage === 'announcements.php' ? 'active' : ''; ?>">
                            Announcements
                        </a>
                    </li>
                    <li>
                        <a href="/admin/import.php" class="sidebar-link block px-4 py-2 rounded hover:bg-gray-100 <?php echo $currentPage === 'import.php' ? 'active' : ''; ?>">
                            Import Services
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="absolute bottom-0 w-64 p-4 border-t">
                <a href="/dashboard" class="block px-4 py-2 text-primary-600 hover:bg-primary-50 rounded-xl">Back to Site</a>
                <a href="/logout" class="block px-4 py-2 text-red-600 hover:bg-red-50 rounded mt-2">Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <header class="bg-white border-b p-4">
                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-bold"><?php echo htmlspecialchars($pageTitle); ?></h1>
                    <div class="text-sm text-gray-600">
                        Admin: <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-6">
