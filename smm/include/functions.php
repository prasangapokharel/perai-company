<?php
// Generate a random string
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

// Format currency
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

// Format currency without $ symbol (for use with SVG icons)
function formatCurrencyValue($amount) {
    return number_format($amount, 2);
}

// Get user balance
function getUserBalance($userId) {
    global $conn;
    $query = $conn->query("SELECT balance FROM users WHERE id = $userId");
    if ($query && $query->num_rows > 0) {
        $row = $query->fetch_assoc();
        return $row['balance'];
    }
    return 0;
}

// Update user balance
function updateUserBalance($userId, $amount, $type = 'add') {
    global $conn;
    if ($type == 'add') {
        return $conn->query("UPDATE users SET balance = balance + $amount WHERE id = $userId");
    } else {
        return $conn->query("UPDATE users SET balance = balance - $amount WHERE id = $userId");
    }
}

// Record transaction
function recordTransaction($userId, $amount, $type, $paymentMethod = null, $details = null) {
    global $conn;
    $paymentMethod = $paymentMethod ? "'$paymentMethod'" : "NULL";
    $details = $details ? "'" . $conn->real_escape_string($details) . "'" : "NULL";
    
    return $conn->query("INSERT INTO transactions (user_id, amount, type, payment_method, payment_details) 
                         VALUES ($userId, $amount, '$type', $paymentMethod, $details)");
}

// Process affiliate commission
function processAffiliateCommission($userId, $orderId, $orderAmount) {
    global $conn;
    
    // Get referrer ID
    $query = $conn->query("SELECT referred_by FROM users WHERE id = $userId AND referred_by IS NOT NULL");
    if ($query && $query->num_rows > 0) {
        $row = $query->fetch_assoc();
        $referrerId = $row['referred_by'];
        
        // Calculate commission (3%)
        $commission = $orderAmount * 0.03;
        
        // Add commission to referrer's balance
        if (updateUserBalance($referrerId, $commission)) {
            // Record affiliate earning
            $conn->query("INSERT INTO affiliate_earnings (user_id, referred_user_id, order_id, amount) 
                         VALUES ($referrerId, $userId, $orderId, $commission)");
            
            // Record transaction
            recordTransaction($referrerId, $commission, 'affiliate', null, "Commission from order #$orderId");
            
            return true;
        }
    }
    
    return false;
}

// Get service details
function getService($serviceId) {
    global $conn;
    $query = $conn->query("SELECT s.*, p.url as api_url, p.api_key 
                          FROM services s 
                          LEFT JOIN api_providers p ON s.api_provider_id = p.id 
                          WHERE s.id = $serviceId");
    if ($query && $query->num_rows > 0) {
        return $query->fetch_assoc();
    }
    return null;
}

// Get services by category
function getServicesByCategory($category = null) {
    global $conn;

    $services = [];

    $categoryTableExists = false;
    $hasCategoryId = false;
    $hasCategoryColumn = false;

    $tableCheck = $conn->query("SHOW TABLES LIKE 'service_categories'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $categoryTableExists = true;
    }

    $columnCheck = $conn->query("SHOW COLUMNS FROM services LIKE 'category_id'");
    if ($columnCheck && $columnCheck->num_rows > 0) {
        $hasCategoryId = true;
    }

    $columnCheck = $conn->query("SHOW COLUMNS FROM services LIKE 'category'");
    if ($columnCheck && $columnCheck->num_rows > 0) {
        $hasCategoryColumn = true;
    }

    if ($categoryTableExists && $hasCategoryId && is_numeric($category)) {
        $categoryId = (int) $category;
        $query = $conn->query("SELECT s.*, sc.name as category_name 
                              FROM services s 
                              LEFT JOIN service_categories sc ON s.category_id = sc.id 
                              WHERE s.category_id = $categoryId 
                              ORDER BY s.price ASC");
    } elseif ($categoryTableExists && $hasCategoryId && $category === null) {
        $query = $conn->query("SELECT s.*, sc.name as category_name 
                              FROM services s 
                              LEFT JOIN service_categories sc ON s.category_id = sc.id 
                              ORDER BY sc.name, s.price ASC");
    } elseif ($hasCategoryColumn) {
        if ($category !== null && $category !== '') {
            $categoryValue = $conn->real_escape_string((string) $category);
            $query = $conn->query("SELECT s.* FROM services s WHERE s.category = '$categoryValue' ORDER BY s.price ASC");
        } else {
            $query = $conn->query("SELECT s.* FROM services s ORDER BY s.category, s.price ASC");
        }
    } else {
        $query = $conn->query("SELECT s.* FROM services s ORDER BY s.price ASC");
    }

    if ($query) {
        while ($row = $query->fetch_assoc()) {
            $services[] = $row;
        }
    }

    return $services;
}

// Get all categories
function getAllCategories() {
    global $conn;

    $categories = [];

    $tableCheck = $conn->query("SHOW TABLES LIKE 'service_categories'");
    $categoryTableExists = $tableCheck && $tableCheck->num_rows > 0;

    $columnCheck = $conn->query("SHOW COLUMNS FROM services LIKE 'category_id'");
    $hasCategoryId = $columnCheck && $columnCheck->num_rows > 0;

    if ($categoryTableExists && $hasCategoryId) {
        $query = $conn->query("SELECT id, name FROM service_categories ORDER BY name");
        if ($query) {
            while ($row = $query->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        return $categories;
    }

    $columnCheck = $conn->query("SHOW COLUMNS FROM services LIKE 'category'");
    $hasCategoryColumn = $columnCheck && $columnCheck->num_rows > 0;

    if ($hasCategoryColumn) {
        $query = $conn->query("SELECT DISTINCT category FROM services WHERE category IS NOT NULL AND category != '' ORDER BY category");
        if ($query) {
            while ($row = $query->fetch_assoc()) {
                $categoryName = (string) $row['category'];
                $categories[] = ['id' => $categoryName, 'name' => $categoryName];
            }
        }
    }

    return $categories;
}

// Get user orders
function getUserOrders($userId) {
    global $conn;
    $query = $conn->query("SELECT o.*, s.name as service_name 
                          FROM orders o 
                          JOIN services s ON o.service_id = s.id 
                          WHERE o.user_id = $userId 
                          ORDER BY o.created_at DESC");
    
    $orders = [];
    if ($query) {
        while ($row = $query->fetch_assoc()) {
            $orders[] = $row;
        }
    }
    
    return $orders;
}

// Get user transactions
function getUserTransactions($userId) {
    global $conn;
    $query = $conn->query("SELECT * FROM transactions WHERE user_id = $userId ORDER BY created_at DESC");
    
    $transactions = [];
    if ($query) {
        while ($row = $query->fetch_assoc()) {
            $transactions[] = $row;
        }
    }
    
    return $transactions;
}

// Get affiliate earnings
function getAffiliateEarnings($userId) {
    global $conn;
    $query = $conn->query("SELECT ae.*, u.username as referred_username 
                          FROM affiliate_earnings ae 
                          JOIN users u ON ae.referred_user_id = u.id 
                          WHERE ae.user_id = $userId 
                          ORDER BY ae.created_at DESC");
    
    $earnings = [];
    if ($query) {
        while ($row = $query->fetch_assoc()) {
            $earnings[] = $row;
        }
    }
    
    return $earnings;
}

// Place order via API
function placeOrderViaApi($serviceId, $link, $quantity, $advancedParams = []) {
    global $conn;
    
    // Get service details with API provider info
    $service = getService($serviceId);
    
    if (!$service || !$service['api_provider_id'] || !$service['api_service_id']) {
        return [
            'success' => false,
            'message' => 'Service not configured for API ordering'
        ];
    }
    
    // Create API instance
    $api = new Api($service['api_url'], $service['api_key']);
    
    // Prepare order data with basic parameters
    $orderData = [
        'service' => $service['api_service_id'],
        'link' => $link,
        'quantity' => $quantity
    ];
    
    // Merge advanced parameters if provided
    if (!empty($advancedParams)) {
        // Handle parameters that need special formatting or processing
        foreach ($advancedParams as $key => $value) {
            if (is_string($value) && !empty($value)) {
                // For multiline parameters (comments, usernames, groups), keep as-is
                $orderData[$key] = $value;
            } elseif (is_numeric($value) && $value > 0) {
                // For numeric parameters, only add if greater than 0
                $orderData[$key] = $value;
            }
        }
    }
    
    // Place order via API
    $response = $api->order($orderData);
    
    if (isset($response->order)) {
        return [
            'success' => true,
            'order_id' => $response->order,
            'response' => json_encode($response)
        ];
    } else {
        return [
            'success' => false,
            'message' => isset($response->error) ? $response->error : 'Unknown API error',
            'response' => json_encode($response)
        ];
    }
}

// Check order status via API
function checkOrderStatusViaApi($orderId) {
    global $conn;
    
    // Fetch order details with API provider info
    $stmt = $conn->prepare("
        SELECT o.api_order_id, p.url AS api_url, p.api_key, o.api_provider_id
        FROM orders o 
        JOIN api_providers p ON o.api_provider_id = p.id 
        WHERE o.id = ?
    ");
    if (!$stmt) {
        error_log("[" . date('Y-m-d H:i:s') . "] Database error for order $orderId: Failed to prepare statement - " . $conn->error);
        return [
            'success' => false,
            'message' => 'Database error: Failed to prepare statement'
        ];
    }
    
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    if (!$order || !$order['api_order_id']) {
        error_log("[" . date('Y-m-d H:i:s') . "] Invalid order or no API order ID for order $orderId - Data: " . json_encode($order));
        return [
            'success' => false,
            'message' => 'Invalid order or no API order ID'
        ];
    }
    
    error_log("[" . date('Y-m-d H:i:s') . "] Checking status for order $orderId with API order ID: " . $order['api_order_id']);
    
    // Make API request (matching test.php)
    $ch = curl_init($order['api_url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // Match test.php (disable for local)
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // Match test.php (disable for local)
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'key' => $order['api_key'],
        'action' => 'status',
        'order' => $order['api_order_id']
    ]));
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        error_log("[" . date('Y-m-d H:i:s') . "] cURL error for order $orderId (API order ID: " . $order['api_order_id'] . "): $curlError - Response: $response");
        return [
            'success' => false,
            'message' => 'Network error: ' . $curlError
        ];
    }
    
    if ($httpCode !== 200) {
        error_log("[" . date('Y-m-d H:i:s') . "] HTTP error $httpCode for order $orderId (API order ID: " . $order['api_order_id'] . "): $response");
        return [
            'success' => false,
            'message' => "API request failed with HTTP code $httpCode"
        ];
    }
    
    // Decode JSON response
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("[" . date('Y-m-d H:i:s') . "] JSON decode error for order $orderId (API order ID: " . $order['api_order_id'] . "): " . json_last_error_msg() . " - Raw response: $response");
        return [
            'success' => false,
            'message' => 'Invalid API response: Failed to decode JSON'
        ];
    }
    
    // Check for API-specific errors
    if (isset($data['error'])) {
        error_log("[" . date('Y-m-d H:i:s') . "] API error for order $orderId (API order ID: " . $order['api_order_id'] . "): " . $data['error']);
        return [
            'success' => false,
            'message' => 'API error: ' . $data['error']
        ];
    }
    
    if (!isset($data['status'])) {
        error_log("[" . date('Y-m-d H:i:s') . "] Missing status in API response for order $orderId (API order ID: " . $order['api_order_id'] . "): " . json_encode($data));
        return [
            'success' => false,
            'message' => 'Invalid API response: Missing status field'
        ];
    }
    
    // Map API status to database status
    $statusMap = [
        'Pending' => 'pending',
        'Processing' => 'processing',
        'Completed' => 'completed',
        'Canceled' => 'canceled',
        'Error' => 'error',
        'Partial' => 'error',
        'In progress' => 'processing'
    ];
    
    $newStatus = isset($statusMap[$data['status']]) ? $statusMap[$data['status']] : 'error';
    error_log("[" . date('Y-m-d H:i:s') . "] Mapped status for order $orderId (API order ID: " . $order['api_order_id'] . "): API {$data['status']} -> DB $newStatus - Full response: " . json_encode($data));
    
    // Update order status in database with forced commit
    $stmt = $conn->prepare("UPDATE orders SET status = ?, api_response = ? WHERE id = ?");
    if (!$stmt) {
        error_log("[" . date('Y-m-d H:i:s') . "] Database error for order $orderId: Failed to prepare update statement - " . $conn->error);
        return [
            'success' => false,
            'message' => 'Database error: Failed to prepare update statement'
        ];
    }
    
    $apiResponse = json_encode($data);
    $stmt->bind_param("ssi", $newStatus, $apiResponse, $orderId);
    $success = $stmt->execute();
    if (!$success) {
        $error = $conn->error;
        error_log("[" . date('Y-m-d H:i:s') . "] Database update failed for order $orderId (API order ID: " . $order['api_order_id'] . ") - Error: $error - Query: UPDATE orders SET status = '$newStatus', api_response = '$apiResponse' WHERE id = $orderId");
        return [
            'success' => false,
            'message' => 'Failed to update order status in database: ' . $error
        ];
    }
    $stmt->close();
    
    // Verify the update
    $verifyStmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
    $verifyStmt->bind_param("i", $orderId);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result()->fetch_assoc();
    $verifyStmt->close();
    
    if ($verifyResult && $verifyResult['status'] !== $newStatus) {
        error_log("[" . date('Y-m-d H:i:s') . "] Verification failed for order $orderId: Expected $newStatus, got " . $verifyResult['status']);
        return [
            'success' => false,
            'message' => 'Database verification failed: Status not updated'
        ];
    }
    
    error_log("[" . date('Y-m-d H:i:s') . "] Successfully updated order $orderId (API order ID: " . $order['api_order_id'] . ") to status $newStatus - Verified: " . $verifyResult['status']);
    return [
        'success' => true,
        'status' => $newStatus,
        'response' => $data
    ];
}

// API class for provider integration
class Api {
    public $api_url;
    public $api_key;
    
    public function __construct($url, $key) {
        $this->api_url = $url;
        $this->api_key = $key;
    }
    
    public function services() {
        return json_decode(
            $this->connect([
                'key' => $this->api_key,
                'action' => 'services',
            ])
        );
    }
    
    public function order($data) {
        $post = array_merge(['key' => $this->api_key, 'action' => 'add'], $data);
        return json_decode($this->connect($post));
    }
    
    public function status($order_id) {
        return json_decode(
            $this->connect([
                'key' => $this->api_key,
                'action' => 'status',
                'order' => $order_id
            ])
        );
    }
    
    public function multiStatus($order_ids) {
        return json_decode(
            $this->connect([
                'key' => $this->api_key,
                'action' => 'status',
                'orders' => implode(",", (array)$order_ids)
            ])
        );
    }
    
    public function balance() {
        return json_decode(
            $this->connect([
                'key' => $this->api_key,
                'action' => 'balance',
            ])
        );
    }
    
    public function refill($orderId) {
        return json_decode(
            $this->connect([
                'key' => $this->api_key,
                'action' => 'refill',
                'order' => $orderId,
            ])
        );
    }
    
    /** Refill orders */
    public function multiRefill($orderIds) {
        return json_decode(
            $this->connect([
                'key' => $this->api_key,
                'action' => 'refill',
                'orders' => implode(',', (array)$orderIds),
            ]),
            true
        );
    }
    
    /** Get refill status */
    public function refillStatus($refillId) {
        return json_decode(
            $this->connect([
                'key' => $this->api_key,
                'action' => 'refill_status',
                'refill' => $refillId,
            ])
        );
    }
    
    /** Get refill statuses */
    public function multiRefillStatus($refillIds) {
        return json_decode(
            $this->connect([
                'key' => $this->api_key,
                'action' => 'refill_status',
                'refills' => implode(',', (array)$refillIds),
            ]),
            true
        );
    }
    
    /** Cancel orders */
    public function cancel($orderIds) {
        return json_decode(
            $this->connect([
                'key' => $this->api_key,
                'action' => 'cancel',
                'orders' => implode(',', (array)$orderIds),
            ]),
            true
        );
    }
    
    private function connect($post) {
        $_post = [];
        if (is_array($post)) {
            foreach ($post as $name => $value) {
                $_post[] = $name . '=' . urlencode($value);
            }
        }

        $ch = curl_init($this->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        if (is_array($post)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, join('&', $_post));
        }
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        $result = curl_exec($ch);
        if (curl_errno($ch) != 0 && empty($result)) {
            $result = false;
        }
        curl_close($ch);
        return $result;
    }
}

// Alert message function
function showAlert($message, $type = 'success') {
    $alertClass = $type === 'success' ? 'bg-primary-100 text-primary-800' : 'bg-red-100 text-red-800';
    return '<div class="mb-6 p-4 rounded ' . $alertClass . '">' . $message . '</div>';
}

function normalizeServiceText($text) {
    if ($text === null) {
        return '';
    }

    $value = (string) $text;

    if (function_exists('mb_convert_encoding')) {
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }

    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $value = str_replace("\xEF\xBF\xBD", '', $value);
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

    return trim($value);
}

function slugify($text) {
    $value = normalizeServiceText($text);
    if ($value === '') {
        return '';
    }

    if (function_exists('iconv')) {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    }

    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    $value = trim($value, '-');

    return $value;
}

/**
 * Get service icon HTML based on service name
 * Centralized for consistent emoji-free professional UI
 */
function getServiceIconHtml($serviceName) {
    $serviceName = strtolower($serviceName);
    
    if (strpos($serviceName, 'instagram') !== false) {
        return '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>';
    } else if (strpos($serviceName, 'tiktok') !== false) {
        return '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>';
    } else if (strpos($serviceName, 'facebook') !== false || strpos($serviceName, 'fb') !== false) {
        return '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>';
    } else if (strpos($serviceName, 'youtube') !== false || strpos($serviceName, 'yt') !== false) {
        return '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>';
    } else if (strpos($serviceName, 'twitter') !== false || strpos($serviceName, 'x.com') !== false) {
        return '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>';
    } else {
        return '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
    }
}
?>
