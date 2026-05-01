<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

// Require login for protected pages
function requireLogin() {
    if (!isLoggedIn()) {
        // Store the requested URL for redirection after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // Redirect to login page
        header("Location: ../login/");
        exit;
    }
}

// Require admin for admin pages
function requireAdmin() {
    if (!isLoggedIn()) {
        // Store the requested URL for redirection after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // Redirect to login page
        header("Location: ../../login/");
        exit;
    }
    
    if (!isAdmin()) {
        // Redirect to dashboard if not admin
        header("Location: ../../dashboard/");
        exit;
    }
}

// Login user
function loginUser($email, $password) {
    global $conn;
    
    $email = sanitize($email);
    
    $query = $conn->query("SELECT id, username, password, is_admin FROM users WHERE email = '$email'");
    
    if ($query && $query->num_rows > 0) {
        $user = $query->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            // Redirect to dashboard or requested page
            $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : '../dashboard/';
            unset($_SESSION['redirect_after_login']);
            
            return [
                'success' => true,
                'redirect' => $redirect
            ];
        }
    }
    
    return [
        'success' => false,
        'message' => 'Invalid email or password'
    ];
}

// Register user
function registerUser($username, $email, $password, $referralCode = null) {
    global $conn;
    
    $username = sanitize($username);
    $email = sanitize($email);
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if username or email already exists
    $checkQuery = $conn->query("SELECT id FROM users WHERE username = '$username' OR email = '$email'");
    
    if ($checkQuery && $checkQuery->num_rows > 0) {
        return [
            'success' => false,
            'message' => 'Username or email already exists'
        ];
    }
    
    // Generate affiliate code
    $affiliateCode = strtoupper(substr(md5(uniqid()), 0, 8));
    
    // Get referrer ID if referral code provided
    $referrerId = null;
    if ($referralCode) {
        $referralCode = sanitize($referralCode);
        $referrerQuery = $conn->query("SELECT id FROM users WHERE affiliate_code = '$referralCode'");
        
        if ($referrerQuery && $referrerQuery->num_rows > 0) {
            $referrer = $referrerQuery->fetch_assoc();
            $referrerId = $referrer['id'];
        }
    }
    
    // Insert new user
    $sql = "INSERT INTO users (username, email, password, affiliate_code, referred_by) 
            VALUES ('$username', '$email', '$hashedPassword', '$affiliateCode', " . ($referrerId ? $referrerId : "NULL") . ")";
    
    if ($conn->query($sql)) {
        // Set session variables
        $_SESSION['user_id'] = $conn->insert_id;
        $_SESSION['username'] = $username;
        $_SESSION['is_admin'] = 0;
        
        return [
            'success' => true,
            'redirect' => '../dashboard/',
            'message' => 'Registration successful'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Registration failed: ' . $conn->error
    ];
}

// Logout user
function logoutUser() {
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header("Location: ../login/");
    exit;
}
?>