<?php
// Include necessary files
require_once '../config/dbconfig.php';
require_once 'functions.php';
require_once 'auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the request is AJAX
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    
    // Get the action from the request
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Handle different AJAX actions
    switch ($action) {
        case 'search_services':
            searchServices();
            break;
            
        case 'validate_url':
            validateUrl();
            break;
            
        case 'get_service_details':
            getServiceDetails();
            break;
            
        case 'filter_orders':
            filterOrders();
            break;
            
        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            break;
    }
} else {
    // Not an AJAX request
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}

/**
 * Search services based on query and category
 */
function searchServices() {
    global $conn;
    
    $query = isset($_POST['query']) ? sanitize($_POST['query']) : '';
    $category = isset($_POST['category']) ? sanitize($_POST['category']) : '';
    
    // Check schema
    $tableCheck = $conn->query("SHOW TABLES LIKE 'service_categories'");
    $serviceCategoriesTableExists = $tableCheck->num_rows > 0;
    $columnCheck = $conn->query("SHOW COLUMNS FROM services LIKE 'category_id'");
    $hasCategoryIdColumn = $columnCheck->num_rows > 0;
    $columnCheck = $conn->query("SHOW COLUMNS FROM services LIKE 'category'");
    $hasCategoryColumn = $columnCheck->num_rows > 0;
    
    $whereClause = "1=1";
    if (!empty($query)) {
        $whereClause .= " AND (name LIKE '%$query%' OR description LIKE '%$query%')";
    }
    
    if (!empty($category) && $category !== 'All') {
        if ($serviceCategoriesTableExists && $hasCategoryIdColumn) {
            // Use service_categories table
            $categoryId = $conn->real_escape_string($category);
            $whereClause .= " AND s.category_id = '$categoryId'";
        } elseif ($hasCategoryColumn) {
            // Use category column
            $categoryValue = $conn->real_escape_string($category);
            $whereClause .= " AND s.category = '$categoryValue'";
        }
    }
    
    // Build query
    if ($serviceCategoriesTableExists && $hasCategoryIdColumn) {
        $sql = "SELECT s.*, sc.name as category_name FROM services s LEFT JOIN service_categories sc ON s.category_id = sc.id WHERE $whereClause ORDER BY s.category_id, s.name";
    } elseif ($hasCategoryColumn) {
        $sql = "SELECT s.* FROM services s WHERE $whereClause ORDER BY s.category, s.name";
    } else {
        $sql = "SELECT s.* FROM services s WHERE $whereClause ORDER BY s.name";
    }
    
    $result = $conn->query($sql);
    
    $services = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Get service icon
            $row['icon'] = getServiceIcon($row['name']);
            $services[] = $row;
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'services' => $services
    ]);
}

/**
 * Validate a URL for a specific service
 */
function validateUrl() {
    $url = isset($_POST['url']) ? $_POST['url'] : '';
    $serviceId = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    
    if (empty($url)) {
        echo json_encode(['status' => 'error', 'message' => 'URL is required']);
        return;
    }
    
    // Basic URL validation
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid URL format']);
        return;
    }
    
    // Get service details to check platform-specific validation
    global $conn;
    $service = $conn->query("SELECT * FROM services WHERE id = $serviceId")->fetch_assoc();
    
    if (!$service) {
        echo json_encode(['status' => 'error', 'message' => 'Service not found']);
        return;
    }
    
    // Platform-specific validation
    $isValid = true;
    $message = 'URL is valid';
    
    // Check for Instagram
    if (stripos($service['name'], 'instagram') !== false) {
        if (stripos($url, 'instagram.com') === false) {
            $isValid = false;
            $message = 'URL must be from Instagram';
        }
    }
    // Check for TikTok
    else if (stripos($service['name'], 'tiktok') !== false) {
        if (stripos($url, 'tiktok.com') === false) {
            $isValid = false;
            $message = 'URL must be from TikTok';
        }
    }
    // Check for Facebook
    else if (stripos($service['name'], 'facebook') !== false || stripos($service['name'], 'fb') !== false) {
        if (stripos($url, 'facebook.com') === false && stripos($url, 'fb.com') === false) {
            $isValid = false;
            $message = 'URL must be from Facebook';
        }
    }
    // Check for YouTube
    else if (stripos($service['name'], 'youtube') !== false || stripos($service['name'], 'yt') !== false) {
        if (stripos($url, 'youtube.com') === false && stripos($url, 'youtu.be') === false) {
            $isValid = false;
            $message = 'URL must be from YouTube';
        }
    }
    // Check for Twitter/X
    else if (stripos($service['name'], 'twitter') !== false || stripos($service['name'], 'x.com') !== false) {
        if (stripos($url, 'twitter.com') === false && stripos($url, 'x.com') === false) {
            $isValid = false;
            $message = 'URL must be from Twitter/X';
        }
    }
    
    echo json_encode([
        'status' => $isValid ? 'success' : 'error',
        'message' => $message,
        'valid' => $isValid
    ]);
}

/**
 * Get service details by ID
 */
function getServiceDetails() {
    global $conn;
    
    $serviceId = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    
    if ($serviceId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid service ID']);
        return;
    }
    
    $sql = "SELECT * FROM services WHERE id = $serviceId";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $service = $result->fetch_assoc();
        $service['icon'] = getServiceIcon($service['name']);
        
        echo json_encode([
            'status' => 'success',
            'service' => $service
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Service not found']);
    }
}

/**
 * Filter orders by status and search query
 */
function filterOrders() {
    global $conn;
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $status = isset($_POST['status']) ? sanitize($_POST['status']) : '';
    $query = isset($_POST['query']) ? sanitize($_POST['query']) : '';
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Build WHERE clause
    $whereClause = "o.user_id = $userId";
    if (!empty($status)) {
        $whereClause .= " AND o.status = '$status'";
    }
    
    if (!empty($query)) {
        $whereClause .= " AND (o.id LIKE '%$query%' OR s.name LIKE '%$query%' OR o.link LIKE '%$query%')";
    }
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as count FROM orders o 
                JOIN services s ON o.service_id = s.id 
                WHERE $whereClause";
    $countResult = $conn->query($countSql);
    $totalCount = $countResult->fetch_assoc()['count'];
    $totalPages = ceil($totalCount / $limit);
    
    // Get orders
    $sql = "SELECT o.*, s.name as service_name, s.category 
            FROM orders o 
            JOIN services s ON o.service_id = s.id 
            WHERE $whereClause 
            ORDER BY o.created_at DESC 
            LIMIT $offset, $limit";
    
    $result = $conn->query($sql);
    
    $orders = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['icon'] = getServiceIcon($row['service_name']);
            $row['formatted_date'] = date('M d, Y H:i', strtotime($row['created_at']));
            $row['formatted_price'] = formatCurrency($row['price']);
            $orders[] = $row;
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'orders' => $orders,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_count' => $totalCount
        ]
    ]);
}

/**
 * Get appropriate icon for a service based on its name
 */
function getServiceIcon($serviceName) {
    $serviceName = strtolower($serviceName);
    
    if (stripos($serviceName, 'instagram') !== false) {
        return 'instagram';
    } else if (stripos($serviceName, 'tiktok') !== false) {
        return 'tiktok';
    } else if (stripos($serviceName, 'facebook') !== false || stripos($serviceName, 'fb') !== false) {
        return 'facebook';
    } else if (stripos($serviceName, 'youtube') !== false || stripos($serviceName, 'yt') !== false) {
        return 'youtube';
    } else if (stripos($serviceName, 'twitter') !== false || stripos($serviceName, 'x.com') !== false) {
        return 'twitter';
    } else if (stripos($serviceName, 'telegram') !== false) {
        return 'telegram';
    } else if (stripos($serviceName, 'pinterest') !== false) {
        return 'pinterest';
    } else if (stripos($serviceName, 'linkedin') !== false) {
        return 'linkedin';
    } else if (stripos($serviceName, 'snapchat') !== false) {
        return 'snapchat';
    } else if (stripos($serviceName, 'twitch') !== false) {
        return 'twitch';
    } else if (stripos($serviceName, 'spotify') !== false) {
        return 'spotify';
    } else if (stripos($serviceName, 'soundcloud') !== false) {
        return 'soundcloud';
    } else if (stripos($serviceName, 'reddit') !== false) {
        return 'reddit';
    } else {
        return 'globe';
    }
}