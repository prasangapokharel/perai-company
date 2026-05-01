<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard/");
    exit;
}
require_once __DIR__ . '/config/dbconfig.php';

// Fetch dynamic stats from database with error handling
$total_users = 0;
$total_orders = 0;
$total_tickets = 0;

try {
    $total_users_result = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($total_users_result) {
        $total_users = $total_users_result->fetch_assoc()['count'];
    }
} catch (Exception $e) {
    $total_users = 0;
}

try {
    $total_orders_result = $conn->query("SELECT COUNT(*) as count FROM orders");
    if ($total_orders_result) {
        $total_orders = $total_orders_result->fetch_assoc()['count'];
    }
} catch (Exception $e) {
    $total_orders = 0;
}

try {
    $total_tickets_result = $conn->query("SELECT COUNT(*) as count FROM support_tickets");
    if ($total_tickets_result) {
        $total_tickets = $total_tickets_result->fetch_assoc()['count'];
    }
} catch (Exception $e) {
    $total_tickets = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OkxSmm - World's Most Affordable Social Media Marketing Services</title>
    <!-- Helvetica World Font -->
    <link href="https://fonts.cdnfonts.com/css/helvetica-world" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6',
                        'background-light': '#eff6ff',
                        'background-dark': '#f8fafc',
                        green: {
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
                    },
                    fontFamily: {
                        sans: ['"Helvetica World"', 'sans-serif'],
                        display: ['"Helvetica World"', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        .glass {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .text-gradient {
            background: linear-gradient(to right, #3b82f6, #2563eb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .radial-glow {
            background: radial-gradient(circle at 50% 50%, rgba(59, 130, 246, 0.1) 0%, transparent 70%);
        }
        .orb-glow {
            box-shadow: 0 0 40px 10px rgba(59, 130, 246, 0.3);
        }
        .diamond-card {
            aspect-ratio: 1/1;
            transform: rotate(45deg);
            transition: all 0.3s ease;
        }
        .diamond-content {
            transform: rotate(-45deg);
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>
<body class="font-display bg-white text-slate-900 min-h-screen overflow-x-hidden">

<!-- Header / Navbar -->
<header class="fixed top-0 w-full z-50 px-6 py-4">
    <nav class="max-w-7xl mx-auto flex items-center justify-between glass px-6 py-3 rounded-2xl shadow-lg">
        <div class="flex items-center">
            <a href="/">
                <img src="/assets/logo.png" alt="OkxSmm Logo" class="h-12 w-auto">
            </a>
        </div>
        <div class="hidden md:flex items-center gap-8 font-medium text-sm text-slate-600">
            <a class="hover:text-primary transition-colors" href="#home">Home</a>
            <a class="hover:text-primary transition-colors" href="#features">Features</a>
            <a class="hover:text-primary transition-colors" href="#services">Services</a>
            <a class="hover:text-primary transition-colors" href="#blog">Blog</a>
            <a class="hover:text-primary transition-colors" href="/api">API</a>
        </div>
        <div class="flex items-center gap-4">
            <a href="/login/" class="px-5 py-2 text-sm font-semibold hover:text-primary transition-colors">Sign In</a>
            <a href="/register/" class="bg-primary hover:bg-primary-600 text-white px-6 py-2.5 rounded-xl text-sm font-bold transition-all shadow-lg shadow-primary-200">
                Sign Up
            </a>
        </div>
    </nav>
</header>

<!-- Hero Section -->
<main id="home" class="relative pt-32 pb-20 px-6 overflow-hidden min-h-screen flex items-center" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(37, 99, 235, 0.05) 100%), url('/assets/images/hero-section.jpg'); background-size: cover; background-position: center; background-attachment: fixed;">
    <!-- Dark Overlay for text readability -->
    <div class="absolute inset-0 bg-black/30 -z-10"></div>
    
    <!-- Background Decorations -->
    <div class="absolute top-1/4 right-0 w-[600px] h-[600px] radial-glow -z-10"></div>
    <div class="absolute bottom-0 left-0 w-[400px] h-[400px] radial-glow opacity-50 -z-10"></div>
    
    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
        <!-- Left Side: Content -->
        <div class="space-y-8 relative z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/90 border border-primary-100 text-primary-600 text-xs font-bold uppercase tracking-widest">
                <span class="flex h-2 w-2 rounded-full bg-primary-500 animate-pulse"></span>
                Number 1 Rated SMM Service
            </div>
            <h1 class="text-5xl lg:text-7xl font-black leading-[1.1] text-white drop-shadow-2xl tracking-tight">
                World's Most <br/>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-300 to-blue-400">Professional</span> OkxSmm
            </h1>
            <p class="text-lg text-white/90 max-w-lg leading-relaxed font-medium drop-shadow-lg">
                Boost your social presence instantly with our automated services. Trusted by 10k+ influencers and agencies worldwide for lightning-fast delivery and premium quality.
            </p>
            <div class="flex flex-wrap gap-4">
                <a href="/register/" class="bg-primary-600 hover:bg-primary-700 hover:scale-105 transition-all text-white px-8 py-4 rounded-2xl text-lg font-bold shadow-2xl shadow-primary-500/50 flex items-center gap-2 drop-shadow-lg">
                    Get Started Now
                    <span class="material-icons">arrow_forward</span>
                </a>
                <a href="/services/" class="bg-white/95 hover:bg-white transition-all text-slate-900 px-8 py-4 rounded-2xl text-lg font-bold shadow-xl drop-shadow-lg hover:shadow-2xl">
                    View Services
                </a>
            </div>
            <!-- Social Media Row -->
            <div class="pt-8">
                <p class="text-[11px] font-bold text-white/80 uppercase tracking-widest mb-4">Supported Platforms</p>
                <div class="flex gap-4">
                    <div class="glass/80 w-14 h-14 rounded-2xl flex items-center justify-center group cursor-pointer hover:border-primary-200 transition-all shadow-xl">
                        <svg class="w-7 h-7 text-white opacity-60 group-hover:opacity-100 transition-all" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    </div>
                    <div class="glass/80 w-14 h-14 rounded-2xl flex items-center justify-center group cursor-pointer hover:border-primary-200 transition-all shadow-xl">
                        <svg class="w-7 h-7 text-white opacity-60 group-hover:opacity-100 transition-all" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 2.71 3.5 2.38 1.18-.23 2.04-1.17 2.06-2.37.03-2.23.04-4.45.04-6.69 0-.5-.01-1 0-1.5.08-1.6.41-3.19 1.06-4.67.69-1.6 1.66-3.07 2.89-4.33z"/></svg>
                    </div>
                    <div class="glass/80 w-14 h-14 rounded-2xl flex items-center justify-center group cursor-pointer hover:border-primary-200 transition-all shadow-xl">
                        <svg class="w-7 h-7 text-white opacity-60 group-hover:opacity-100 transition-all" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </div>
                    <div class="glass/80 w-14 h-14 rounded-2xl flex items-center justify-center group cursor-pointer hover:border-primary-200 transition-all shadow-xl">
                        <svg class="w-7 h-7 text-white opacity-60 group-hover:opacity-100 transition-all" fill="currentColor" viewBox="0 0 24 24"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Side: Mystical Illustration -->
        <div class="relative flex justify-center items-center">
            <!-- Large Background Glow -->
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-primary-500/10 rounded-full blur-[100px] -z-10"></div>
            
            <!-- Image Container -->
            <div class="relative w-full max-w-lg">
                <img src="/assets/images/hero-section.jpg" alt="OkxSmm Professional Services" class="w-full h-auto rounded-[2.5rem] shadow-2xl border-4 border-white/80 object-cover">
                
                <!-- Glass Stats Card (Floating) -->
                <div class="absolute bottom-10 -left-6 glass p-5 rounded-2xl shadow-2xl flex items-center gap-4 max-w-[220px] animate-float">
                    <div class="w-12 h-12 rounded-xl bg-primary-500/10 flex items-center justify-center">
                        <span class="material-icons text-primary-500">check_circle</span>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Orders Done</p>
                        <p class="text-xl font-black text-slate-900"><?php echo number_format($total_orders); ?>+</p>
                    </div>
                </div>
                
                <!-- Floating Badge -->
                <div class="absolute top-10 right-0 glass px-4 py-3 rounded-2xl border-primary-100 flex items-center gap-3 shadow-lg">
                    <div class="flex -space-x-2">
                        <div class="w-8 h-8 rounded-full border-2 border-white bg-primary-50 flex items-center justify-center text-[10px] font-bold text-primary-600">JD</div>
                        <div class="w-8 h-8 rounded-full border-2 border-white bg-primary-50 flex items-center justify-center text-[10px] font-bold text-primary-600">AS</div>
                    </div>
                    <p class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">Joined <span class="text-primary-500">Now</span></p>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Bottom Stat Bar -->
<div class="max-w-7xl mx-auto px-6 pb-12">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-8 glass rounded-3xl shadow-xl">
        <div class="text-center space-y-1">
            <p class="text-slate-500 text-sm font-medium uppercase">Active Users</p>
            <h3 class="text-2xl font-bold text-slate-900"><?php echo number_format($total_users); ?>+</h3>
        </div>
        <div class="text-center space-y-1 border-l border-gray-200">
            <p class="text-slate-500 text-sm font-medium uppercase">Total Orders</p>
            <h3 class="text-2xl font-bold text-slate-900"><?php echo number_format($total_orders); ?>+</h3>
        </div>
        <div class="text-center space-y-1 border-l border-gray-200">
            <p class="text-slate-500 text-sm font-medium uppercase">Average Time</p>
            <h3 class="text-2xl font-bold text-slate-900">0.4s</h3>
        </div>
        <div class="text-center space-y-1 border-l border-gray-200">
            <p class="text-slate-500 text-sm font-medium uppercase">Satisfaction</p>
            <h3 class="text-2xl font-bold text-primary">99.9%</h3>
        </div>
    </div>
</div>

<!-- Statistics Section -->
<section class="py-24 relative bg-gradient-to-b from-white to-gray-50">
    <div class="container mx-auto px-6">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <!-- Left: Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="glass p-8 rounded-2xl border-t border-l border-primary-200 transform hover:-translate-y-2 transition-transform">
                    <div class="w-12 h-12 bg-primary/20 rounded-xl flex items-center justify-center mb-6">
                        <span class="material-icons text-primary">groups</span>
                    </div>
                    <h3 class="text-4xl font-bold mb-2"><?php echo number_format($total_users); ?>+</h3>
                    <p class="text-slate-600 uppercase text-sm font-semibold tracking-wider">Total Users</p>
                </div>
                <div class="glass p-8 rounded-2xl border-t border-l border-primary-200 transform hover:-translate-y-2 transition-transform md:mt-12">
                    <div class="w-12 h-12 bg-primary/20 rounded-xl flex items-center justify-center mb-6">
                        <span class="material-icons text-primary">confirmation_number</span>
                    </div>
                    <h3 class="text-4xl font-bold mb-2"><?php echo number_format($total_tickets); ?>+</h3>
                    <p class="text-slate-600 uppercase text-sm font-semibold tracking-wider">Total Tickets</p>
                </div>
                <div class="glass p-8 rounded-2xl border-t border-l border-primary-200 transform hover:-translate-y-2 transition-transform">
                    <div class="w-12 h-12 bg-primary/20 rounded-xl flex items-center justify-center mb-6">
                        <span class="material-icons text-primary">shopping_cart</span>
                    </div>
                    <h3 class="text-4xl font-bold mb-2"><?php echo number_format($total_orders); ?>+</h3>
                    <p class="text-slate-600 uppercase text-sm font-semibold tracking-wider">Orders Completed</p>
                </div>
                <div class="glass p-8 rounded-2xl border-t border-l border-primary-200 transform hover:-translate-y-2 transition-transform md:mt-12">
                    <div class="w-12 h-12 bg-primary/20 rounded-xl flex items-center justify-center mb-6">
                        <span class="material-icons text-primary">speed</span>
                    </div>
                    <h3 class="text-4xl font-bold mb-2">0.2s</h3>
                    <p class="text-slate-600 uppercase text-sm font-semibold tracking-wider">Average Response</p>
                </div>
            </div>
            <!-- Right: Content -->
            <div class="space-y-8">
                <h2 class="text-4xl font-bold leading-tight text-slate-900">
                    Built on <span class="text-primary">Reliability</span> &<br/>Trusted Excellence
                </h2>
                <p class="text-slate-600 text-lg leading-relaxed">
                    OkxSmm isn't just another SMM service. We've engineered a platform that balances speed with reliability, ensuring your growth never skips a beat.
                </p>
                <ul class="space-y-4">
                    <li class="flex items-center gap-4">
                        <div class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-500/20 flex items-center justify-center">
                            <span class="material-icons text-primary-500 text-sm">check</span>
                        </div>
                        <span class="text-slate-700 font-medium">99.9% Platform Uptime Guaranteed</span>
                    </li>
                    <li class="flex items-center gap-4">
                        <div class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-500/20 flex items-center justify-center">
                            <span class="material-icons text-primary-500 text-sm">check</span>
                        </div>
                        <span class="text-slate-700 font-medium">Advanced API Support for Resellers</span>
                    </li>
                    <li class="flex items-center gap-4">
                        <div class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-500/20 flex items-center justify-center">
                            <span class="material-icons text-primary-500 text-sm">check</span>
                        </div>
                        <span class="text-slate-700 font-medium">Secure Encrypted Payment Gateways</span>
                    </li>
                </ul>
                <div class="pt-4">
                    <a class="inline-flex items-center gap-2 text-primary font-bold hover:gap-4 transition-all uppercase tracking-widest text-sm" href="/services/">
                        View all services <span class="material-icons text-sm">arrow_forward</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features & Advantages Section -->
<section id="features" class="py-24 bg-white overflow-hidden">
    <div class="container mx-auto px-6">
        <div class="text-center mb-24">
            <h2 class="text-4xl font-bold mb-4 text-slate-900">Our Advantages</h2>
            <div class="h-1 w-20 bg-primary mx-auto rounded-full"></div>
        </div>
        <!-- Diamond Grid -->
        <div class="max-w-5xl mx-auto px-10">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-16 md:gap-24 lg:gap-12 pb-20">
                <!-- Adv 1: Secure -->
                <div class="diamond-card glass flex items-center justify-center p-4 border-primary/20 hover:border-primary/60 hover:bg-primary/5 cursor-default group">
                    <div class="diamond-content text-center">
                        <span class="material-icons text-primary text-4xl mb-3 group-hover:scale-110 transition-transform">lock</span>
                        <h4 class="font-bold text-lg mb-1 text-slate-900">Secure</h4>
                        <p class="text-xs text-slate-600 px-2">Encrypted data protection</p>
                    </div>
                </div>
                <!-- Adv 2: Instant -->
                <div class="diamond-card glass flex items-center justify-center p-4 border-primary/20 hover:border-primary/60 hover:bg-primary/5 cursor-default group lg:translate-y-12">
                    <div class="diamond-content text-center">
                        <span class="material-icons text-primary text-4xl mb-3 group-hover:scale-110 transition-transform">bolt</span>
                        <h4 class="font-bold text-lg mb-1 text-slate-900">Instant</h4>
                        <p class="text-xs text-slate-600 px-2">Rapid delivery systems</p>
                    </div>
                </div>
                <!-- Adv 3: 24/7 Support -->
                <div class="diamond-card glass flex items-center justify-center p-4 border-primary/20 hover:border-primary/60 hover:bg-primary/5 cursor-default group">
                    <div class="diamond-content text-center">
                        <span class="material-icons text-primary text-4xl mb-3 group-hover:scale-110 transition-transform">support_agent</span>
                        <h4 class="font-bold text-lg mb-1 text-slate-900">24/7 Support</h4>
                        <p class="text-xs text-slate-600 px-2">Human help anytime</p>
                    </div>
                </div>
                <!-- Adv 4: Best Prices -->
                <div class="diamond-card glass flex items-center justify-center p-4 border-primary/20 hover:border-primary/60 hover:bg-primary/5 cursor-default group">
                    <div class="diamond-content text-center">
                        <span class="material-icons text-primary text-4xl mb-3 group-hover:scale-110 transition-transform">local_offer</span>
                        <h4 class="font-bold text-lg mb-1 text-slate-900">Best Prices</h4>
                        <p class="text-xs text-slate-600 px-2">Most competitive rates</p>
                    </div>
                </div>
                <!-- Adv 5: Quality Services -->
                <div class="diamond-card glass flex items-center justify-center p-4 border-primary/20 hover:border-primary/60 hover:bg-primary/5 cursor-default group lg:translate-y-12">
                    <div class="diamond-content text-center">
                        <span class="material-icons text-primary text-4xl mb-3 group-hover:scale-110 transition-transform">stars</span>
                        <h4 class="font-bold text-lg mb-1 text-slate-900">Quality</h4>
                        <p class="text-xs text-slate-600 px-2">Premium non-drop sets</p>
                    </div>
                </div>
                <!-- Adv 6: Easy Dashboard -->
                <div class="diamond-card glass flex items-center justify-center p-4 border-primary/20 hover:border-primary/60 hover:bg-primary/5 cursor-default group">
                    <div class="diamond-content text-center">
                        <span class="material-icons text-primary text-4xl mb-3 group-hover:scale-110 transition-transform">dashboard</span>
                        <h4 class="font-bold text-lg mb-1 text-slate-900">Easy UI</h4>
                        <p class="text-xs text-slate-600 px-2">Simple tracking panel</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

    <!-- Services Preview (Static for now to be fast, or minimal PHP loop if preferred, sticking to static for speed and design control) -->
    <section id="services" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-end mb-12">
                <div class="max-w-2xl">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Popular Services</h2>
                    <p class="text-gray-600">Explore our best-selling services loved by thousands of influencers and agencies.</p>
                </div>
                <a href="/services" class="hidden md:inline-flex items-center font-semibold text-primary-600 hover:text-primary-700 transition">
                    View All Services <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </a>
            </div>

            <div class="overflow-hidden border border-slate-200 rounded-3xl shadow-sm bg-white">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-5 text-left text-[11px] font-bold text-slate-400 uppercase tracking-widest">Service</th>
                            <th scope="col" class="px-6 py-5 text-left text-[11px] font-bold text-slate-400 uppercase tracking-widest">Price per 1k</th>
                            <th scope="col" class="px-6 py-5 text-left text-[11px] font-bold text-slate-400 uppercase tracking-widest">Min / Max</th>
                            <th scope="col" class="px-6 py-5 text-left text-[11px] font-bold text-slate-400 uppercase tracking-widest">Time</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-50">
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-5 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="flex-shrink-0 h-10 w-10 rounded-xl bg-primary-50 flex items-center justify-center text-primary-600 mr-4 font-bold text-lg">
                                        📸
                                    </span>
                                    <span class="font-bold text-slate-900">Instagram Followers [Real]</span>
                                </div>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap text-sm font-black text-primary-600">$0.85</td>
                            <td class="px-6 py-5 whitespace-nowrap text-sm font-bold text-slate-500">100 / 100K</td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                <span class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-full bg-primary-50 text-primary-700 border border-primary-100">Instant</span>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-5 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="flex-shrink-0 h-10 w-10 rounded-xl bg-primary-50 flex items-center justify-center text-primary-600 mr-4 font-bold text-lg">
                                        ▶️
                                    </span>
                                    <span class="font-bold text-slate-900">YouTube Views [Non-Drop]</span>
                                </div>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap text-sm font-black text-primary-600">$1.20</td>
                            <td class="px-6 py-5 whitespace-nowrap text-sm font-bold text-slate-500">500 / 1M</td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                <span class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-full bg-primary-50 text-primary-700 border border-primary-100">Fast</span>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-5 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="flex-shrink-0 h-10 w-10 rounded-xl bg-primary-50 flex items-center justify-center text-primary-600 mr-4 font-bold text-lg">
                                        🎵
                                    </span>
                                    <span class="font-bold text-slate-900">TikTok Likes</span>
                                </div>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap text-sm font-black text-primary-600">$0.50</td>
                            <td class="px-6 py-5 whitespace-nowrap text-sm font-bold text-slate-500">50 / 50K</td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                <span class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-full bg-primary-50 text-primary-700 border border-primary-100">Instant</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-8 text-center md:hidden">
                 <a href="/services" class="inline-flex items-center font-semibold text-primary-600 hover:text-primary-700 transition">
                    View All Services <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </a>
            </div>
        </div>
    </section>

    <!-- Blog Section -->
<section id="blog" class="relative py-24 overflow-hidden bg-gradient-to-b from-gray-50 to-white">
    <!-- Mystical Background Elements -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full pointer-events-none">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-primary/20 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-primary/10 rounded-full blur-[120px]"></div>
    </div>
    <div class="container mx-auto px-6 relative z-10">
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-bold mb-4 tracking-tight text-slate-900">
                Read Our <span class="text-primary">Blog</span>
            </h2>
            <div class="w-24 h-1 bg-primary mx-auto rounded-full"></div>
            <p class="mt-6 text-slate-600 max-w-2xl mx-auto">
                Stay updated with the latest trends in social media growth, gaming influences, and digital marketing strategies.
            </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Blog Card 1 -->
            <article class="glass rounded-xl overflow-hidden shadow-lg group hover:border-primary/40 transition-all duration-300">
                <div class="relative h-56 overflow-hidden bg-gradient-to-br from-green-400 to-green-600">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 to-transparent"></div>
                    <span class="absolute top-4 left-4 bg-primary text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">Growth</span>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="material-icons text-white opacity-50" style="font-size: 80px;">trending_up</span>
                    </div>
                </div>
                <div class="p-6 bg-white">
                    <h3 class="text-xl font-bold mb-3 group-hover:text-primary transition-colors text-slate-900">Mastering Instagram Growth</h3>
                    <p class="text-slate-600 text-sm mb-6 line-clamp-3">
                        Unlock the secrets of the algorithm and learn how to use engagement strategies to skyrocket your follower count overnight.
                    </p>
                    <a class="inline-flex items-center text-primary font-semibold hover:gap-2 transition-all" href="#">
                        Read More <span class="material-icons text-sm ml-1">arrow_forward</span>
                    </a>
                </div>
            </article>
            <!-- Blog Card 2 -->
            <article class="glass rounded-xl overflow-hidden shadow-lg group hover:border-primary/40 transition-all duration-300">
                <div class="relative h-56 overflow-hidden bg-gradient-to-br from-green-500 to-green-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 to-transparent"></div>
                    <span class="absolute top-4 left-4 bg-primary text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">Marketing</span>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="material-icons text-white opacity-50" style="font-size: 80px;">campaign</span>
                    </div>
                </div>
                <div class="p-6 bg-white">
                    <h3 class="text-xl font-bold mb-3 group-hover:text-primary transition-colors text-slate-900">Social Media & Business</h3>
                    <p class="text-slate-600 text-sm mb-6 line-clamp-3">
                        The intersection of business and social presence. How top brands leverage SMM panels to maintain their influence.
                    </p>
                    <a class="inline-flex items-center text-primary font-semibold hover:gap-2 transition-all" href="#">
                        Read More <span class="material-icons text-sm ml-1">arrow_forward</span>
                    </a>
                </div>
            </article>
            <!-- Blog Card 3 -->
            <article class="glass rounded-xl overflow-hidden shadow-lg group hover:border-primary/40 transition-all duration-300">
                <div class="relative h-56 overflow-hidden bg-gradient-to-br from-green-600 to-green-800">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 to-transparent"></div>
                    <span class="absolute top-4 left-4 bg-primary text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">Future</span>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="material-icons text-white opacity-50" style="font-size: 80px;">auto_awesome</span>
                    </div>
                </div>
                <div class="p-6 bg-white">
                    <h3 class="text-xl font-bold mb-3 group-hover:text-primary transition-colors text-slate-900">The Future of OkxSmms</h3>
                    <p class="text-slate-600 text-sm mb-6 line-clamp-3">
                        Discover the next evolution of Social Media Marketing tools and how AI is changing the landscape for influencers worldwide.
                    </p>
                    <a class="inline-flex items-center text-primary font-semibold hover:gap-2 transition-all" href="#">
                        Read More <span class="material-icons text-sm ml-1">arrow_forward</span>
                    </a>
                </div>
            </article>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-20 relative bg-white">
    <div class="container mx-auto px-6 text-center">
        <div class="glass p-12 rounded-3xl border border-primary/20 relative overflow-hidden shadow-2xl">
            <div class="absolute top-0 right-0 -translate-y-1/2 translate-x-1/2 w-64 h-64 bg-primary/20 rounded-full blur-3xl"></div>
            <h2 class="text-3xl md:text-4xl font-bold mb-6 text-slate-900">Ready to expand your reach?</h2>
            <p class="text-slate-600 max-w-xl mx-auto mb-10">
                Join thousands of users who have already unlocked the power of professional social media growth.
            </p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="/register/" class="bg-primary hover:bg-primary-600 text-white px-10 py-4 rounded-full font-bold transition-all shadow-lg shadow-primary-200 hover:scale-105">
                    Create Your Free Account
                </a>
                <a href="/login/" class="border border-gray-300 hover:bg-gray-50 text-slate-900 px-10 py-4 rounded-full font-bold transition-all">
                    Sign In Now
                </a>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/include/footer.php'; ?>

</body>
</html>
