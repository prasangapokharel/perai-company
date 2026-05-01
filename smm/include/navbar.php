<?php
// Get current page
$currentPage = basename($_SERVER['PHP_SELF']);

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Get user data if logged in
$userData = null;
$userBalance = 0;
$unreadTickets = 0;
$latestAnnouncements = [];

if ($isLoggedIn) {
    $userId = $_SESSION['user_id'];
    $userQuery = $conn->query("SELECT * FROM users WHERE id = $userId");
    
    if ($userQuery && $userQuery->num_rows > 0) {
        $userData = $userQuery->fetch_assoc();
        $userBalance = $userData['balance'];
    } else {
        // If user data can't be found, log them out
        header("Location: logout/");
        exit;
    }
    
    // Get unread support tickets count
    $ticketsQuery = $conn->query("SELECT COUNT(*) as count FROM support_tickets WHERE user_id = $userId AND status != 'closed'");
    if ($ticketsQuery && $ticketsQuery->num_rows > 0) {
        $ticketData = $ticketsQuery->fetch_assoc();
        $unreadTickets = $ticketData['count'];
    }
    
    // Get latest announcements (max 3)
    $announcementsQuery = $conn->query("SELECT id, title, slug, created_at FROM announcements WHERE status = 'published' ORDER BY created_at DESC LIMIT 3");
    if ($announcementsQuery && $announcementsQuery->num_rows > 0) {
        while ($announcement = $announcementsQuery->fetch_assoc()) {
            $latestAnnouncements[] = $announcement;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>SMMVite</title>

    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
      <script src="https://cdn.tailwindcss.com"></script>

    <!-- Tailwind CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            padding-bottom: 60px; /* Add padding for mobile bottom nav */
        }
        
        .green-gradient {
            background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
        }
        
        .nav-link {
            position: relative;
        }
        
        .nav-link.active {
            color: #16a34a;
            font-weight: 600;
        }
        
        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #16a34a;
            border-radius: 2px;
        }
        
        .nav-link:hover {
            color: #16a34a;
        }
        
        .dropdown-menu {
            transform-origin: top right;
        }
        
        @media (max-width: 768px) {
            .mobile-nav {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .mobile-nav::-webkit-scrollbar {
                display: none;
            }
            
            /* Bottom navigation styles */
            .bottom-nav {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                z-index: 50;
            }
            
            .bottom-nav.hidden {
                transform: translateY(100%);
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <header class=" border-b border-gray-100 shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4 py-2">
            <div class="flex justify-between items-center">
                <div>
                    <a href="/dashboard/" class="flex items-center">
                       <img src="/assets/logo.png" class="h-12 w-auto">
                    </a>
                </div>
                
                <?php if ($isLoggedIn): ?>
                <div class="hidden md:flex items-center space-x-6">
                    <a href="/dashboard/" class="nav-link text-gray-700 hover:text-primary-600 text-sm font-medium <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
                    <a href="/services/" class="nav-link text-gray-700 hover:text-primary-600 text-sm font-medium <?php echo $currentPage === 'services' ? 'active' : ''; ?>">Services</a>
                    <a href="/order/" class="nav-link text-gray-700 hover:text-primary-600 text-sm font-medium <?php echo $currentPage === 'order' ? 'active' : ''; ?>">New Order</a>
                    <a href="/orders/" class="nav-link text-gray-700 hover:text-primary-600 text-sm font-medium <?php echo $currentPage === 'orders' ? 'active' : ''; ?>">Orders</a>
                    <a href="/tickets/" class="nav-link text-gray-700 hover:text-primary-600 text-sm font-medium relative <?php echo $currentPage === 'tickets' ? 'active' : ''; ?>">
                        Support
                        <?php if ($unreadTickets > 0): ?>
                            <span class="absolute -top-2 -right-4 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center"><?php echo $unreadTickets; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="/affiliate/" class="nav-link text-gray-700 hover:text-primary-600 text-sm font-medium <?php echo $currentPage === 'affiliate' ? 'active' : ''; ?>">Affiliate</a>
                </div>
                <?php endif; ?>
                
                <div class="flex items-center space-x-4">
                    <?php if ($isLoggedIn): ?>
                        <!-- Announcements Dropdown -->
                        <?php if (!empty($latestAnnouncements)): ?>
                        <div class="relative group hidden sm:block">
                            <button class="flex items-center justify-center w-10 h-10 rounded-full border border-gray-200 hover:border-primary-500 hover:bg-primary-50 focus:outline-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                            </button>
                            
                            <div class="dropdown-menu absolute right-0 mt-2 w-80 bg-white border border-gray-200 rounded-xl shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible z-10 max-h-96 overflow-y-auto">
                                <div class="p-4 border-b border-gray-100">
                                    <h3 class="text-sm font-semibold text-gray-900">Updates & Announcements</h3>
                                </div>
                                <div class="py-2">
                                    <?php foreach ($latestAnnouncements as $announcement): ?>
                                        <a href="/announcements/view/<?php echo $announcement['slug']; ?>" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100 last:border-b-0">
                                            <h4 class="text-sm font-medium text-gray-900 line-clamp-2"><?php echo htmlspecialchars($announcement['title']); ?></h4>
                                            <p class="text-xs text-gray-500 mt-1"><?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></p>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                                <div class="p-3 border-t border-gray-100 text-center">
                                    <a href="/announcements/" class="text-sm text-primary-600 font-medium hover:text-primary-700">View All Updates</a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="hidden sm:flex text-gray-700 bg-gray-100 px-3 py-1.5 rounded-full text-sm">
                            Balance: <span class="font-semibold ml-1 text-primary-600"><?php echo formatCurrency($userBalance); ?></span>
                        </div>
                        
                        <a href="/addfund/" class="px-4 py-2 green-gradient text-white rounded-full text-sm font-medium shadow-sm hover:shadow-md">
                            <span class="hidden sm:inline">Add Funds</span>
                            <span class="sm:hidden">+</span>
                        </a>
                        
                        <div class="relative group">
                            <button class="flex items-center space-x-2 focus:outline-none bg-white border border-gray-200 rounded-full px-3 py-1.5 hover:border-primary-500">
                                <div class="w-6 h-6 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center text-xs font-bold">
                                    <?php echo substr($userData['username'], 0, 1); ?>
                                </div>
                                <span class="text-sm font-medium hidden sm:block"><?php echo $userData['username']; ?></span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            
                            <div class="dropdown-menu absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-xl shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible z-10">
                                <div class="p-3 border-b border-gray-100">
                                    <p class="text-sm font-medium text-gray-900"><?php echo $userData['username']; ?></p>
                                    <p class="text-xs text-gray-500 mt-1 truncate"><?php echo $userData['email']; ?></p>
                                </div>
                                <div class="py-1">
                                    <a href="/dashboard/" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                        Dashboard
                                    </a>
                                    <a href="/services" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        Services
                                    </a>
                                    <a href="/order/" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        New Order
                                    </a>
                                    <a href="/orders" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        My Orders
                                    </a>
                                    <a href="/affiliate/" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary-600">
                                         <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                         </svg>
                                         Affiliate
                                     </a>
                                     <a href="/tickets/" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary-600">
                                         <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                                         </svg>
                                         Support Tickets
                                         <?php if ($unreadTickets > 0): ?>
                                             <span class="ml-2 bg-red-500 text-white text-xs font-bold rounded-full px-2"><?php echo $unreadTickets; ?></span>
                                         <?php endif; ?>
                                     </a>
                                </div>
                                
                                <?php if ($userData['is_admin']): ?>
                                    <div class="border-t border-gray-100 my-1"></div>
                                        <a href="/admin/dashboard/" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        Admin Panel
                                    </a>
                                <?php endif; ?>
                                
                                <div class="border-t border-gray-100 my-1"></div>
                                <a href="/logout" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center space-x-2">
                            <div class="hidden md:flex items-center space-x-2">
                                <a href="/faq/" class="px-4 py-2 border border-gray-200 rounded-full text-sm font-medium text-gray-700 hover:border-primary-500 hover:text-primary-600" title="Frequently Asked Questions" rel="nofollow">FAQ</a>
                                <a href="/terms/" class="px-4 py-2 border border-gray-200 rounded-full text-sm font-medium text-gray-700 hover:border-primary-500 hover:text-primary-600" title="Terms of Service" rel="nofollow">Terms</a>
                            </div>
                            <a href="/login/" class="px-4 py-2 green-gradient text-white rounded-full text-sm font-medium shadow-sm hover:shadow-md">Login</a>
                            <a href="/register/" class="px-4 py-2 border border-gray-200 rounded-full text-sm font-medium text-gray-700 hover:border-primary-500 hover:text-primary-600">Register</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Mobile Navigation Menu (only for logged in users) -->
    <?php if ($isLoggedIn): ?>
    <div class="md:hidden bg-white border-b border-gray-100 shadow-sm">
        <div class="container mx-auto px-2">
            <div class="mobile-nav flex justify-between overflow-x-auto whitespace-nowrap py-1">
                <a href="/dashboard/" class="px-4 py-2 text-sm font-medium <?php echo $currentPage === 'dashboard' ? 'text-primary-600 border-b-2 border-primary-500' : 'text-gray-700'; ?>">Dashboard</a>
                <a href="/services/" class="px-4 py-2 text-sm font-medium <?php echo $currentPage === 'services' ? 'text-primary-600 border-b-2 border-primary-500' : 'text-gray-700'; ?>">Services</a>
                <a href="/order/" class="px-4 py-2 text-sm font-medium <?php echo $currentPage === 'order' ? 'text-primary-600 border-b-2 border-primary-500' : 'text-gray-700'; ?>">Order</a>
                <a href="/orders/" class="px-4 py-2 text-sm font-medium <?php echo $currentPage === 'orders' ? 'text-primary-600 border-b-2 border-primary-500' : 'text-gray-700'; ?>">Orders</a>
                <a href="/tickets/" class="px-4 py-2 text-sm font-medium relative <?php echo $currentPage === 'tickets' ? 'text-primary-600 border-b-2 border-primary-500' : 'text-gray-700'; ?>">
                    Support
                    <?php if ($unreadTickets > 0): ?>
                        <span class="absolute top-0 right-2 bg-red-500 text-white text-xs font-bold rounded-full h-4 w-4 flex items-center justify-center"><?php echo $unreadTickets; ?></span>
                    <?php endif; ?>
                </a>
                <a href="/affiliate/" class="px-4 py-2 text-sm font-medium <?php echo $currentPage === 'affiliate' || $currentPage === 'affilate' ? 'text-primary-600 border-b-2 border-primary-500' : 'text-gray-700'; ?>">Affiliate</a>
            </div>
        </div>
        
        <!-- Mobile Balance Display -->
        <div class="container mx-auto px-4 py-2 flex justify-center">
            <div class="text-sm text-gray-700 bg-gray-100 px-3 py-1.5 rounded-full">
                Balance: <span class="font-semibold ml-1 text-primary-600"><?php echo formatCurrency($userBalance); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Mobile Bottom Navigation -->
    <div class="bottom-nav md:hidden bg-white border-t border-gray-200 shadow-lg">
        <div class="grid grid-cols-5 h-14">
            <a href="/dashboard/" class="flex flex-col items-center justify-center text-xs font-medium <?php echo $currentPage === 'dashboard' ? 'text-primary-600' : 'text-gray-600'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Home
            </a>
            <a href="/services/" class="flex flex-col items-center justify-center text-xs font-medium <?php echo $currentPage === 'services' ? 'text-primary-600' : 'text-gray-600'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                Services
            </a>
            <a href="/order/" class="flex flex-col items-center justify-center text-xs font-medium <?php echo $currentPage === 'order' ? 'text-primary-600' : 'text-gray-600'; ?>">
                <div class="bg-primary-500 rounded-full p-2 -mt-5 shadow-lg border-4 border-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                </div>
                <span class="mt-1">Order</span>
            </a>
            <a href="/orders/" class="flex flex-col items-center justify-center text-xs font-medium <?php echo $currentPage === 'orders' ? 'text-primary-600' : 'text-gray-600'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                Orders
            </a>
            <a href="/logout/" class="flex flex-col items-center justify-center text-xs font-medium text-red-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Logout
            </a>
        </div>
    </div>
    <?php endif; ?>
    
    <main class="flex-grow container mx-auto px-4 py-8">
        <!-- Page content goes here -->
    </main>
</body>
</html>