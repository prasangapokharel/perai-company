<?php
include '../config/dbconfig.php';
include '../include/functions.php';
include '../include/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: ../dashboard/");
    exit;
}

$pageTitle = 'Register';
$alertMessage = '';
$alertType = '';

// Get referral code from URL if present
$referralCode = isset($_GET['ref']) ? sanitize($_GET['ref']) : '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $referralCode = isset($_POST['referral_code']) ? sanitize($_POST['referral_code']) : null;
    
    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $alertMessage = 'All fields are required';
        $alertType = 'error';
    } elseif ($password !== $confirmPassword) {
        $alertMessage = 'Passwords do not match';
        $alertType = 'error';
    } elseif (strlen($password) < 6) {
        $alertMessage = 'Password must be at least 6 characters long';
        $alertType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $alertMessage = 'Invalid email format';
        $alertType = 'error';
    } else {
        // Register user
        $result = registerUser($username, $email, $password, $referralCode);
        
        if ($result['success']) {
            // Redirect to dashboard
            header("Location: " . $result['redirect']);
            exit;
        } else {
            $alertMessage = $result['message'];
            $alertType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - OkxSmm</title>
    <?php include '../include/meta.php'; ?>

    <!-- Helvetica World Font -->
    <link href="https://fonts.cdnfonts.com/css/helvetica-world" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Helvetica World"', 'sans-serif'],
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
        /* No Animations */
        *, *::before, *::after {
            transition: none !important;
            animation: none !important;
        }
    </style>
</head>
<body class="h-full font-sans antialiased text-gray-900">
    <div class="min-h-screen flex p-10 rounded-2xl overflow-hidden shadow-lg bg-white">
        <!-- Left Section: Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 relative">
            
            <div class="max-w-md w-full relative z-10 p-6">
                <!-- Logo -->
                <div class="text-center mb-10">
                    <a href="../" class="inline-flex items-center gap-2 mb-8">
                        <img src="../assets/logo.png" alt="OkxSmm Logo" class="h-10 w-auto">
                        <span class="text-2xl font-bold text-gray-900 tracking-tight">SMM<span class="text-primary-600">Panel</span></span>
                    </a>
                    <h1 class="text-3xl font-bold text-gray-900 mb-3">Create an Account</h1>
                    <p class="text-gray-500">Buy TikTok, FB, Insta, YouTube followers & more.</p>
                </div>
                
                <!-- Alert Message -->
                <?php if ($alertMessage): ?>
                    <div class="mb-6 p-4 rounded-xl <?php echo $alertType == 'success' ? 'bg-primary-50 border border-primary-200 text-primary-700' : 'bg-red-50 border border-red-200 text-red-700'; ?> flex items-start">
                        <div class="flex-shrink-0 mt-0.5">
                            <?php if ($alertType == 'success'): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            <?php else: ?>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="ml-3 text-sm font-medium">
                            <?php echo $alertMessage; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Registration Form -->
                <form action="" method="post" id="register-form" class="space-y-4">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                        <input type="text" id="username" name="username" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-base" placeholder="SmmUser" required>
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <input type="email" id="email" name="email" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-base" placeholder="your@email.com" required>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                            <input type="password" id="password" name="password" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-base" placeholder="••••••••" required>
                        </div>
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-base" placeholder="••••••••" required>
                        </div>
                    </div>
                    
                    <div>
                        <label for="referral_code" class="block text-sm font-medium text-gray-700 mb-2">Referral Code (Optional)</label>
                        <input type="text" id="referral_code" name="referral_code" value="<?php echo $referralCode; ?>" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-base" placeholder="Enter referral code">
                    </div>
                    
                    <div class="flex items-center mt-2">
                        <input id="terms" name="terms" type="checkbox" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded-xl" required>
                        <label for="terms" class="ml-2 block text-sm text-gray-700">
                            I agree to the <a href="../terms/" class="text-primary-600 hover:text-primary-500 font-semibold">Terms of Service</a> and <a href="privacy.php" class="text-primary-600 hover:text-primary-500 font-semibold">Privacy Policy</a>
                        </label>
                    </div>
                    
                    <button type="submit" class="w-full flex justify-center py-3.5 px-4 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 mt-2">
                        Create Account
                    </button>
                </form>
                
                <p class="mt-8 text-center text-sm text-gray-600">
                    Already have an account? 
                    <a href="../login/" class="font-bold text-primary-600 hover:text-primary-500">Sign in</a>
                </p>
            </div>
        </div>
        
        <!-- Right Section: Video Background -->
        <div class="hidden lg:block relative w-1/2 bg-gray-900 overflow-hidden rounded-2xl">
            <!-- Video Background -->
            <video autoplay muted loop playsinline class="absolute inset-0 w-full h-full object-cover">
                <source src="/assets/video/regiter.mp4" type="video/mp4">
                Your browser does not support HTML5 video.
            </video>
            <!-- Minimal overlay for contrast -->
            <div class="absolute inset-0 bg-black/20 z-10"></div>
            <!-- Content moved to bottom to avoid top text over video -->
            <div class="absolute inset-0 z-20 flex flex-col justify-end px-12 pb-12 text-white">
                <div class="max-w-lg">
                    <p class="text-lg text-blue-100 leading-relaxed">Create your account and get instant access to our SMM services. Fast, reliable, and secure.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password validation
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const form = document.getElementById('register-form');
        
        // Form validation
        form.addEventListener('submit', function(e) {
            if (passwordInput.value !== confirmPasswordInput.value) {
                e.preventDefault();
                alert('Passwords do not match');
            }
            
            if (passwordInput.value.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long');
            }
        });
    </script>
</body>
</html>
