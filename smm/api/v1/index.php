<?php
/**
 * OkxSmm API v1 Endpoint
 * 
 * HTTP METHOD: POST
 * API URL: http://localhost:8000/api/v1/
 * RESPONSE FORMAT: JSON
 * 
 * Required Parameters:
 * - key: API key (from account settings)
 * - action: API action (add, status, services, balance, refill, cancel, etc.)
 */

header('Content-Type: application/json; charset=utf-8');

// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed. Use POST.']);
    exit;
}

try {
    // Database connection
    require_once __DIR__ . '/../config/dbconfig.php';
    require_once __DIR__ . '/../include/functions.php';

    // Get POST data
    $input = file_get_contents('php://input');
    parse_str($input, $_POST);

    // Validate API key
    $apiKey = $_POST['key'] ?? null;
    $action = $_POST['action'] ?? null;

    if (!$apiKey || !$action) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required parameters: key, action']);
        exit;
    }

    // Get user from API key
    $stmt = $conn->prepare("SELECT u.*, a.id as api_id, a.is_active, a.expiry_date FROM api_keys a 
                           JOIN users u ON a.user_id = u.id 
                           WHERE a.api_key = ? AND a.is_active = 1");
    $stmt->bind_param("s", $apiKey);
    $stmt->execute();
    $result = $stmt->get_result();
    $apiUser = $result->fetch_assoc();

    if (!$apiUser) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid or inactive API key']);
        exit;
    }

    // Check if API key has expired
    if ($apiUser['expiry_date'] && strtotime($apiUser['expiry_date']) < time()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'API key has expired']);
        exit;
    }

    $userId = $apiUser['id'];

    // Update last used timestamp
    $conn->query("UPDATE api_keys SET last_used = NOW() WHERE api_key = '$apiKey'");

    // Route to appropriate action
    switch (strtolower($action)) {
        
        case 'services':
            handleServices($conn, $userId);
            break;

        case 'add':
            handleAddOrder($conn, $userId, $_POST);
            break;

        case 'status':
            handleStatus($conn, $userId, $_POST);
            break;

        case 'balance':
            handleBalance($conn, $userId);
            break;

        case 'refill':
            handleRefill($conn, $userId, $_POST);
            break;

        case 'refill_status':
            handleRefillStatus($conn, $userId, $_POST);
            break;

        case 'cancel':
            handleCancel($conn, $userId, $_POST);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
            exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

// ==== API Action Handlers ====

function handleServices($conn, $userId) {
    $sql = "SELECT id, name, price, min_quantity, max_quantity, category_id FROM services WHERE status = 1 ORDER BY name";
    $result = $conn->query($sql);
    $services = [];

    while ($row = $result->fetch_assoc()) {
        $services[] = [
            'service' => (int)$row['id'],
            'name' => $row['name'],
            'rate' => (float)$row['price'],
            'min' => (int)$row['min_quantity'],
            'max' => (int)$row['max_quantity']
        ];
    }

    echo json_encode(['success' => true, 'services' => $services]);
}

function handleAddOrder($conn, $userId, $post) {
    $serviceId = (int)($post['service'] ?? 0);
    $link = trim($post['link'] ?? '');
    $quantity = (int)($post['quantity'] ?? 0);

    // Validate inputs
    if (!$serviceId || !$link || !$quantity) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields: service, link, quantity']);
        exit;
    }

    // Validate URL
    if (!filter_var($link, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid link URL']);
        exit;
    }

    // Get service
    $stmt = $conn->prepare("SELECT * FROM services WHERE id = ? AND status = 1");
    $stmt->bind_param("i", $serviceId);
    $stmt->execute();
    $service = $stmt->get_result()->fetch_assoc();

    if (!$service) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Service not found']);
        exit;
    }

    // Validate quantity
    if ($quantity < $service['min_quantity'] || $quantity > $service['max_quantity']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => "Quantity must be between {$service['min_quantity']} and {$service['max_quantity']}"
        ]);
        exit;
    }

    // Calculate price
    $price = ($service['price'] * $quantity) / 1000;

    // Check balance
    $userBalance = getUserBalance($userId);
    if ($userBalance < $price) {
        http_response_code(402);
        echo json_encode(['success' => false, 'error' => 'Insufficient balance']);
        exit;
    }

    // Create order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, service_id, link, quantity, price, status) 
                           VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iisid", $userId, $serviceId, $link, $quantity, $price);

    if ($stmt->execute()) {
        $orderId = $conn->insert_id;

        // Deduct balance
        updateUserBalance($userId, $price, 'subtract');
        recordTransaction($userId, $price, 'api_order', $orderId, "API Order #$orderId");

        // Process affiliate commission
        processAffiliateCommission($userId, $orderId, $price);

        echo json_encode([
            'success' => true,
            'order' => (int)$orderId,
            'charge' => (float)$price,
            'currency' => 'USD'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create order']);
    }
}

function handleStatus($conn, $userId, $post) {
    $orderId = (int)($post['order'] ?? 0);
    $orderIds = isset($post['orders']) ? array_map('intval', explode(',', $post['orders'])) : [];

    if ($orderId) {
        // Single order status
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $orderId, $userId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();

        if (!$order) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Order not found']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'order' => (int)$order['id'],
            'status' => $order['status'],
            'charge' => (float)$order['price'],
            'remains' => max(0, $order['quantity'] - ($order['completed_quantity'] ?? 0)),
            'start_count' => (int)$order['completed_quantity'],
            'currency' => 'USD'
        ]);
    } elseif (!empty($orderIds)) {
        // Multiple orders status
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $types = str_repeat('i', count($orderIds)) . 'i';
        $params = array_merge($orderIds, [$userId]);

        $sql = "SELECT id, status, price, quantity, completed_quantity FROM orders 
                WHERE id IN ($placeholders) AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $statuses = [];
        while ($order = $result->fetch_assoc()) {
            $statuses[] = [
                'order' => (int)$order['id'],
                'status' => $order['status'],
                'charge' => (float)$order['price'],
                'remains' => max(0, $order['quantity'] - ($order['completed_quantity'] ?? 0)),
                'start_count' => (int)$order['completed_quantity'],
                'currency' => 'USD'
            ];
        }

        echo json_encode(['success' => true, 'statuses' => $statuses]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Provide order or orders parameter']);
    }
}

function handleBalance($conn, $userId) {
    $balance = getUserBalance($userId);
    echo json_encode(['success' => true, 'balance' => (float)$balance]);
}

function handleRefill($conn, $userId, $post) {
    $orderIds = isset($post['orders']) ? array_map('intval', explode(',', $post['orders'])) : [];
    $orderId = (int)($post['order'] ?? 0);

    if ($orderId) {
        $orderIds = [$orderId];
    }

    if (empty($orderIds)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No orders provided']);
        exit;
    }

    $refills = [];
    foreach ($orderIds as $id) {
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $userId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();

        if ($order && in_array($order['status'], ['completed', 'partial'])) {
            // Create refill request
            $stmt2 = $conn->prepare("INSERT INTO refills (order_id, user_id, quantity, status) 
                                    VALUES (?, ?, ?, 'pending')");
            $stmt2->bind_param("iii", $id, $userId, $order['quantity']);
            if ($stmt2->execute()) {
                $refills[] = ['refill' => (int)$conn->insert_id, 'order' => (int)$id];
            }
        }
    }

    echo json_encode(['success' => true, 'refills' => $refills]);
}

function handleRefillStatus($conn, $userId, $post) {
    $refillId = (int)($post['refill'] ?? 0);
    $refillIds = isset($post['refills']) ? array_map('intval', explode(',', $post['refills'])) : [];

    if ($refillId) {
        $stmt = $conn->prepare("SELECT * FROM refills WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $refillId, $userId);
        $stmt->execute();
        $refill = $stmt->get_result()->fetch_assoc();

        if (!$refill) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Refill not found']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'refill' => (int)$refill['id'],
            'status' => $refill['status']
        ]);
    } elseif (!empty($refillIds)) {
        $placeholders = implode(',', array_fill(0, count($refillIds), '?'));
        $types = str_repeat('i', count($refillIds)) . 'i';
        $params = array_merge($refillIds, [$userId]);

        $sql = "SELECT id, status FROM refills WHERE id IN ($placeholders) AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $statuses = [];
        while ($refill = $result->fetch_assoc()) {
            $statuses[] = ['refill' => (int)$refill['id'], 'status' => $refill['status']];
        }

        echo json_encode(['success' => true, 'statuses' => $statuses]);
    }
}

function handleCancel($conn, $userId, $post) {
    $orderIds = isset($post['orders']) ? array_map('intval', explode(',', $post['orders'])) : [];

    if (empty($orderIds)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No orders provided']);
        exit;
    }

    $canceled = [];
    foreach ($orderIds as $id) {
        $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status IN ('pending', 'processing')");
        $stmt->bind_param("ii", $id, $userId);
        if ($stmt->execute() && $conn->affected_rows > 0) {
            $canceled[] = ['order' => (int)$id, 'success' => true];
        }
    }

    echo json_encode(['success' => true, 'canceled' => $canceled]);
}

?>
