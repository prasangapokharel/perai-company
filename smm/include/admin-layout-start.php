<?php
// Admin Layout Start
// This file ensures consistent modernization across all admin sub-modules

// Database & Functions
require_once __DIR__ . '/../config/dbconfig.php';
require_once __DIR__ . '/../include/functions.php';

// Ensure user is admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../../login/");
    exit;
}

// Get current page and detect depth level
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

// Adjust baseUrl logic more robustly
$pathParts = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
$adminIndex = array_search('admin', $pathParts);
if ($adminIndex === false) {
    // Fallback if 'admin' is not in path (e.g. local dev with different structure)
    $depth = count(explode('/', trim(dirname($_SERVER['PHP_SELF']), '/'))) - count(explode('/', trim(__DIR__, '/'))) + 1;
} else {
    $depth = count($pathParts) - $adminIndex - 1;
}
$baseUrl = str_repeat('../', $depth);
?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Admin Control Panel</title>
    
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
                        emerald: {
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
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

        .admin-nav-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
            color: #64748b;
            transition: all 0.2s ease;
            margin-bottom: 0.25rem;
        }
        
        .admin-nav-item:hover {
            color: #3b82f6;
            background: #eff6ff;
        }
        
        .admin-nav-item.active {
            color: #ffffff;
            background: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
        }

        .sidebar {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @media (max-width: 767px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.mobile-open { transform: translateX(0); }
        }
    </style>
</head>
<body class="h-full font-sans antialiased text-slate-900 bg-slate-50 flex flex-col min-h-screen">
    <!-- Top Navbar -->
    <header class="bg-white border-b border-slate-100 sticky top-0 z-50">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-4">
                    <button id="sidebarToggle" class="p-2 rounded-xl text-slate-500 hover:bg-slate-50 md:hidden">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                    <a href="<?php echo $baseUrl; ?>" class="flex items-center gap-2">
                        <div class="bg-primary-500 text-white p-1.5 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" /></svg>
                        </div>
                        <span class="text-xl font-black text-slate-900 tracking-tight uppercase">Admin<span class="text-primary-500">Core</span></span>
                    </a>
                </div>

                <div class="flex items-center gap-4">
                    <a href="/" class="hidden sm:flex items-center gap-2 px-4 py-2 text-xs font-bold text-slate-500 hover:text-primary-600 transition-colors uppercase tracking-widest">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                        View Website
                    </a>
                    <div class="h-8 w-px bg-slate-100 hidden sm:block"></div>
                    <a href="/logout" class="px-4 py-2 bg-rose-50 text-rose-600 text-xs font-black rounded-xl hover:bg-rose-600 hover:text-white transition-all uppercase tracking-widest">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden">
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar fixed md:static inset-y-0 left-0 w-64 bg-white border-r border-slate-100 z-40 overflow-y-auto">
            <div class="p-6">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4 px-4">Management</p>
                <nav class="space-y-1">
                    <?php
                    $navItems = [
                        ['url' => 'dashboard/', 'label' => 'Dashboard', 'icon' => '<path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />'],
                        ['url' => 'orders/', 'label' => 'Orders', 'icon' => '<path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />'],
                        ['url' => 'users/', 'label' => 'Users', 'icon' => '<path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />'],
                        ['url' => 'tickets.php', 'label' => 'Support', 'icon' => '<path d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />'],
                        ['url' => 'approvals/', 'label' => 'Fund Approvals', 'icon' => '<path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'],
                    ];

                    foreach ($navItems as $item):
                        $isActive = ($currentDir === str_replace('/', '', $item['url'])) || ($currentPage === 'index.php' && $item['url'] === '');
                        // If baseUrl is empty, we are at admin root, so item url works as is.
                        // If baseUrl is ../, we need to go up to reach admin root.
                        $href = $baseUrl . $item['url'];
                    ?>
                        <a href="<?php echo $href; ?>" class="admin-nav-item <?php echo $isActive ? 'active' : ''; ?>">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" <?php echo $item['icon']; ?>></path>
                            </svg>
                            <?php echo $item['label']; ?>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mt-10 mb-4 px-4">Services & Content</p>
                <nav class="space-y-1">
                    <?php
                    $contentItems = [
                        ['url' => 'import/', 'label' => 'Import Services', 'icon' => '<path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />'],
                        ['url' => 'announcements.php', 'label' => 'Announcements', 'icon' => '<path d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />'],
                        ['url' => 'payouts/', 'label' => 'Affiliate Payouts', 'icon' => '<path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />'],
                    ];

                    foreach ($contentItems as $item):
                        $isActive = ($currentPage === $item['url'] || $currentDir === str_replace('/', '', $item['url']));
                    ?>
                        <a href="<?php echo $baseUrl . $item['url']; ?>" class="admin-nav-item <?php echo $isActive ? 'active' : ''; ?>">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" <?php echo $item['icon']; ?>></path>
                            </svg>
                            <?php echo $item['label']; ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto bg-slate-50/50">
            <div class="p-8 max-w-[1600px] mx-auto min-h-full">
