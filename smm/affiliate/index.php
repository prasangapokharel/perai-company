<?php
include '../config/dbconfig.php';
include '../include/functions.php';
include '../include/auth.php';

// Require login
requireLogin();

$pageTitle = 'Affiliate Program';
$userId = $_SESSION['user_id'];

// Get user data
$userData = $conn->query("SELECT * FROM users WHERE id = $userId")->fetch_assoc();
$affiliateCode = $userData['affiliate_code'];
$affiliateBalance = $userData['affiliate_balance'] ?? 0;

// Get affiliate statistics
$totalReferrals = $conn->query("SELECT COUNT(*) as count FROM users WHERE referred_by = $userId")->fetch_assoc()['count'];
$totalEarnings = $conn->query("SELECT SUM(amount) as total FROM affiliate_earnings WHERE user_id = $userId")->fetch_assoc()['total'] ?: 0;

// Get recent earnings
$recentEarnings = $conn->query("
    SELECT ae.*, u.username as referred_username 
    FROM affiliate_earnings ae 
    JOIN users u ON ae.referred_user_id = u.id 
    WHERE ae.user_id = $userId 
    ORDER BY ae.created_at DESC 
    LIMIT 10
");

include '../include/user-layout-start.php';

?>

<style>
    .glass-card {
        background: rgba(255, 255, 255, 0.6);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.8);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    }
    
    .stat-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.8);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        position: relative;
        overflow: hidden;
    }
    
    .stat-card::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(90deg, #22c55e, #16a34a);
        opacity: 0.7;
    }
</style>

<div class="max-w-7xl mx-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Affiliate Program</h1>
        <p class="text-gray-500">Share your link and earn 3% commission on all orders</p>
    </div>
    
    <!-- Affiliate Link Card -->
    <div class="glass-card p-6 md:p-8 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-2 md:mb-0">Your Affiliate Link</h2>
            <div class="flex items-center text-primary-500 bg-primary-50 px-4 py-2 rounded-xl">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                <span class="font-medium">3% Commission on All Orders</span>
            </div>
        </div>
        
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row">
                <input type="text" value="<?php echo "https://{$_SERVER['HTTP_HOST']}/register?ref=$affiliateCode"; ?>" class="w-full px-4 py-3 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-gray-700 bg-gray-50 font-medium" readonly id="affiliate-link">
                <button class="mt-2 sm:mt-0 bg-primary-600 text-white px-4 py-3 rounded-r-lg hover:bg-primary-700 font-bold text-sm flex items-center justify-center gap-2 transition-all duration-200" onclick="copyAffiliateLink()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                    </svg>
                    Copy
                </button>
            </div>
            <p class="text-gray-600 text-sm mt-3 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Share this link with your friends and earn 3% commission on their orders!
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="stat-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-gray-500 font-medium">Total Referrals</p>
                    <div class="p-2 bg-primary-50 rounded-xl text-primary-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-800"><?php echo $totalReferrals; ?></p>
            </div>
            
            <div class="stat-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-gray-500 font-medium">Total Earnings</p>
                    <div class="p-2 bg-primary-50 rounded-xl text-primary-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold text-gray-800"><?php echo formatCurrency($totalEarnings); ?></p>
                        <p class="text-xs text-gray-500 mt-1">Available: <span class="font-semibold text-primary-600"><?php echo formatCurrency($affiliateBalance); ?></span></p>
                    </div>
                    <?php if ($affiliateBalance > 0): ?>
                        <a href="../withdraw/" class="ml-2 px-4 py-2 bg-primary-600 text-white rounded-xl hover:bg-primary-700 font-bold text-sm whitespace-nowrap inline-flex items-center gap-2 transition-all duration-200">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                             </svg>
                             Withdraw
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="stat-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-gray-500 font-medium">Commission Rate</p>
                    <div class="p-2 bg-primary-50 rounded-xl text-primary-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 8h6m-5 0a3 3 0 110 6H9l3 3m-3-6h6m6 1a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-800">3%</p>
            </div>
        </div>
    </div>
    
    <!-- Recent Earnings Card -->
    <div class="glass-card p-6 md:p-8 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-2 sm:mb-0">Recent Earnings</h2>
            <div class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-sm font-medium">
                Last 10 transactions
            </div>
        </div>
        
        <?php if ($recentEarnings && $recentEarnings->num_rows > 0): ?>
            <div class="overflow-x-auto -mx-4 sm:mx-0">
                <div class="inline-block min-w-full align-middle">
                    <table class="min-w-full">
                        <thead>
                            <tr class="text-left border-b border-gray-200">
                                <th class="px-4 py-3 font-semibold text-gray-600">Date</th>
                                <th class="px-4 py-3 font-semibold text-gray-600">Referred User</th>
                                <th class="px-4 py-3 font-semibold text-gray-600">Order ID</th>
                                <th class="px-4 py-3 font-semibold text-gray-600 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($earning = $recentEarnings->fetch_assoc()): ?>
                                <tr class="border-b border-gray-100">
                                    <td class="px-4 py-3 text-gray-700">
                                        <div class="flex items-center">
                                            <div class="p-2 bg-primary-100 rounded-full mr-3 hidden sm:block">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <?php echo date('M d, Y', strtotime($earning['created_at'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 font-medium"><?php echo $earning['referred_username']; ?></td>
                                    <td class="px-4 py-3">
                                        <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm">
                                            #<?php echo $earning['order_id']; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-primary-600"><?php echo formatCurrency($earning['amount']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-gray-50 rounded-xl p-8 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="text-gray-600 mb-4">No earnings yet. Start sharing your affiliate link!</p>
                <button onclick="copyAffiliateLink()" class="px-4 py-2 bg-primary-600 text-white rounded-xl hover:bg-primary-700 font-bold text-sm inline-flex items-center gap-2 transition-all duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                    </svg>
                    Copy Affiliate Link
                </button>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- How It Works Section -->
    <div id="how-it-works" class="glass-card p-6 md:p-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">How It Works</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="flex flex-col items-center text-center">
                <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                    </svg>
                </div>
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 h-full">
                    <h3 class="text-lg font-semibold mb-2 text-gray-800">1. Share Your Link</h3>
                    <p class="text-gray-600">Share your unique affiliate link with friends, on social media, or on your website.</p>
                </div>
            </div>
            
            <div class="flex flex-col items-center text-center">
                <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 h-full">
                    <h3 class="text-lg font-semibold mb-2 text-gray-800">2. Friends Sign Up</h3>
                    <p class="text-gray-600">When someone clicks your link and creates an account, they're linked to you as a referral.</p>
                </div>
            </div>
            
            <div class="flex flex-col items-center text-center">
                <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 h-full">
                    <h3 class="text-lg font-semibold mb-2 text-gray-800">3. Earn Commission</h3>
                    <p class="text-gray-600">You earn 3% commission on every order your referrals place. Earnings are added directly to your balance.</p>
                </div>
            </div>
        </div>
        
        <div class="mt-8 bg-primary-50 p-6 rounded-xl border border-primary-100">
            <div class="flex flex-col md:flex-row items-center">
                <div class="md:mr-6 mb-4 md:mb-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-2 text-gray-800">Affiliate Program Terms</h3>
                    <p class="text-gray-600">Commissions are calculated based on the order amount before taxes and fees. Payments are processed automatically and added to your account balance.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Promotional Banner -->
    <div class="mt-6 glass-card p-6">
        <div class="flex flex-col md:flex-row items-center justify-between">
            <div class="mb-4 md:mb-0">
                <h3 class="text-xl font-bold text-gray-800 mb-2">Boost Your Earnings!</h3>
                <p class="text-gray-600">Share your affiliate link on social media to reach more potential customers.</p>
            </div>
            <div class="flex space-x-3">
                <button onclick="shareOnFacebook()" class="p-3 bg-primary-100 text-primary-600 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </button>
                <button onclick="shareOnTwitter()" class="p-3 bg-primary-100 text-primary-600 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                    </svg>
                </button>
                <button onclick="shareOnLinkedIn()" class="p-3 bg-primary-100 text-primary-600 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<?php include '../include/user-layout-end.php'; ?>