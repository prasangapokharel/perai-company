<?php
// User Layout Header
// This file should be included at the start of all user pages

// Database & Functions
require_once __DIR__ . '/../config/dbconfig.php';
require_once __DIR__ . '/../include/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

// Get user data
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    session_destroy();
    header("Location: /");
    exit;
}

$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - OkxSmm</title>
    <!-- Ubuntu & Bebas Neue Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: {
        sans: ['"Ubuntu"', 'sans-serif'],
        display: ['"Bebas Neue"', 'cursive'],
      },
      colors: {
        primary: {
          50: '#eff6ff',
          100: '#dbeafe',
          200: '#bfdbfe',
          300: '#93c5fd',
          400: '#60a5fa',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
          800: '#1e40af',
          900: '#1e3a8a',
        }
      }
    }
  }
}
</script>
    <style>
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1; 
        }
        ::-webkit-scrollbar-thumb {
            background: #d1d5db; 
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #9ca3af; 
        }
        /* Style adjustments to match image */
        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.5rem 0.875rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            color: #475569;
            transition: all 0.15s ease-in-out;
        }
        
         .nav-item:hover {
            color: #3b82f6;
            background: #eff6ff;
        }
        
        .nav-item.active {
            color: #3b82f6;
            background: #dbeafe;
            font-weight: 600;
        }
    </style>
</head>
<body class="h-full font-sans antialiased text-slate-900 bg-slate-50 flex flex-col min-h-screen">
    <!-- Header -->
    <header class="bg-white border-b border-slate-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="/dashboard" class="flex items-center gap-2">
                        <img src="/assets/logo.png" alt="OkxSmm Logo" class="h-10 w-auto">
                        <span class="text-xl font-bold text-slate-900 tracking-tight">OkxSmm</span>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <nav class="hidden lg:flex items-center space-x-1">
                    <?php
                    $navLinks = [
                        ['url' => '/dashboard', 'label' => 'Dashboard', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />'],
                        ['url' => '/order', 'label' => 'New Order', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />'],
                        ['url' => '/orders', 'label' => 'Orders', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />'],
                        ['url' => '/services', 'label' => 'Services', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />'],
                        ['url' => '/addfund', 'label' => 'Add Funds', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />'],
                        ['url' => '/tickets.php', 'label' => 'Support', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />'],
                    ];
                    
                    $currentUri = $_SERVER['REQUEST_URI'];
                    
                    foreach ($navLinks as $link): 
                        $isActive = (strpos($currentUri, $link['url']) !== false && $link['url'] !== '/dashboard') || ($currentUri === '/dashboard' && $link['url'] === '/dashboard') || ($currentUri === '/' && $link['url'] === '/dashboard');
                        $activeClass = $isActive ? 'active' : '';
                    ?>
                        <a href="<?php echo $link['url']; ?>" class="nav-item <?php echo $activeClass; ?>">
                            <?php echo $link['label']; ?>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <!-- User Menu -->
                <div class="flex items-center gap-4">
                    <div class="hidden xl:flex flex-col items-end mr-2">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Balance</span>
                        <span class="text-base font-bold text-slate-900">$<?php echo number_format($user['balance'], 2); ?></span>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <?php if ($user['is_admin']): ?>
                        <a href="/admin/" class="hidden sm:inline-flex items-center justify-center px-4 py-2 bg-slate-800 text-white text-sm font-semibold rounded-xl hover:bg-slate-900 transition-colors">
                            Admin
                        </a>
                        <?php endif; ?>
                        
                        <div class="relative group">
                            <button class="flex items-center focus:outline-none">
                                <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center text-primary-600 font-bold border-2 border-white shadow-sm overflow-hidden">
                                    <?php if ($user['profile_pic'] ?? false): ?>
                                        <img src="<?php echo $user['profile_pic']; ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span class="text-sm uppercase"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></span>
                                    <?php endif; ?>
                                </div>
                            </button>
                            <!-- Dropdown -->
                            <div class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-xl py-2 border border-slate-100 hidden group-hover:block transition-all z-[100]">
                                <div class="px-4 py-3 border-b border-slate-50">
                                    <p class="text-sm text-slate-900 font-bold truncate"><?php echo htmlspecialchars($user['username']); ?></p>
                                    <p class="text-xs text-slate-400 truncate"><?php echo htmlspecialchars($user['email']); ?></p>
                                </div>
                                <a href="/affiliate" class="block px-4 py-2 text-sm text-slate-600 hover:bg-primary-50 hover:text-primary-600">Affiliates</a>
                                <a href="/account" class="block px-4 py-2 text-sm text-slate-600 hover:bg-primary-50 hover:text-primary-600">Account Settings</a>
                                <a href="/api" class="block px-4 py-2 text-sm text-slate-600 hover:bg-primary-50 hover:text-primary-600">API Documentation</a>
                                <div class="border-t border-slate-50 my-1"></div>
                                <a href="/logout" class="block px-4 py-2 text-sm text-red-500 hover:bg-red-50 font-semibold">Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <div class="md:hidden border-t border-gray-200 bg-white">
            <div class="grid grid-cols-5 divide-x divide-gray-100">
                <?php foreach ($navLinks as $link): 
                     $isActive = (strpos($currentUri, $link['url']) !== false && $link['url'] !== '/dashboard') || ($currentUri === '/dashboard' && $link['url'] === '/dashboard') || ($currentUri === '/' && $link['url'] === '/dashboard');
                     $activeClass = $isActive ? 'text-primary-600 bg-primary-50' : 'text-gray-500 hover:text-gray-900';
                ?>
                <a href="<?php echo $link['url']; ?>" class="<?php echo $activeClass; ?> flex flex-col items-center justify-center py-4 text-xs font-bold uppercase tracking-tight">
                    <?php echo str_replace('New Order', 'Order', $link['label']); ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
