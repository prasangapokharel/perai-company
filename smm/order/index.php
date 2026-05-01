<?php
include '../config/dbconfig.php';
include '../include/functions.php';
include '../include/auth.php';

// Require login
requireLogin();

$pageTitle = 'New Order';
$userId = $_SESSION['user_id'];
$alertMessage = '';
$alertType = '';

// Get categories
$categories = getAllCategories();

// Get service ID from URL if provided
$preSelectedServiceId = isset($_GET['service']) ? intval($_GET['service']) : 0;
$preSelectedService = null;

if ($preSelectedServiceId > 0) {
    $preSelectedService = getService($preSelectedServiceId);
}

// Get services for selected category
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : ($categories[0]['id'] ?? null);
if (isset($preSelectedService) && $preSelectedService) {
    if (!empty($preSelectedService['category_id'])) {
        $selectedCategory = (int) $preSelectedService['category_id'];
    } elseif (!empty($preSelectedService['category'])) {
        $selectedCategory = $preSelectedService['category'];
    }
}
$services = getServicesByCategory($selectedCategory);

// Get user balance
$userBalance = getUserBalance($userId);

// Process order form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serviceId = intval($_POST['service']);
    $link = sanitize($_POST['link']);
    $quantity = intval($_POST['quantity']);
    
    // Get service details
    $service = getService($serviceId);
    
    if (!$service) {
        $alertMessage = 'Invalid service selected';
        $alertType = 'error';
    } elseif (empty($link)) {
        $alertMessage = 'Please enter a valid link';
        $alertType = 'error';
    } elseif (!preg_match('/^https?:\/\//i', $link)) {
        $alertMessage = 'Link must start with http:// or https://';
        $alertType = 'error';
    } elseif ($quantity < $service['min_quantity'] || $quantity > $service['max_quantity']) {
        $alertMessage = "Quantity must be between {$service['min_quantity']} and {$service['max_quantity']}";
        $alertType = 'error';
    } else {
        // Calculate price
        $price = $service['price'] * $quantity / 1000;
        
        // Collect advanced parameters
        $advancedParams = [];
        
        // Drip-feed parameters
        if (!empty($_POST['runs']) && intval($_POST['runs']) > 0) {
            $advancedParams['runs'] = intval($_POST['runs']);
        }
        if (!empty($_POST['interval']) && intval($_POST['interval']) > 0) {
            $advancedParams['interval'] = intval($_POST['interval']);
        }
        
        // Custom comments
        if (!empty($_POST['comments'])) {
            $comments = array_filter(array_map('trim', explode("\n", $_POST['comments'])));
            if (!empty($comments)) {
                $advancedParams['comments'] = implode("\n", $comments);
            }
        }
        
        // Mentions/Hashtags
        if (!empty($_POST['usernames'])) {
            $usernames = array_filter(array_map('trim', explode("\n", $_POST['usernames'])));
            if (!empty($usernames)) {
                $advancedParams['usernames'] = implode("\n", $usernames);
            }
        }
        if (!empty($_POST['hashtags'])) {
            $hashtags = array_filter(array_map('trim', explode("\n", $_POST['hashtags'])));
            if (!empty($hashtags)) {
                $advancedParams['hashtags'] = implode("\n", $hashtags);
            }
        }
        
        // Media link
        if (!empty($_POST['media'])) {
            $media = sanitize($_POST['media']);
            if (preg_match('/^https?:\/\//i', $media)) {
                $advancedParams['media'] = $media;
            }
        }
        
        // Subscription parameters
        if (!empty($_POST['sub_username'])) {
            $advancedParams['username'] = sanitize($_POST['sub_username']);
        }
        if (!empty($_POST['min_followers'])) {
            $advancedParams['min'] = intval($_POST['min_followers']);
        }
        if (!empty($_POST['max_followers'])) {
            $advancedParams['max'] = intval($_POST['max_followers']);
        }
        if (!empty($_POST['posts_delay'])) {
            $advancedParams['delay'] = intval($_POST['posts_delay']);
        }
        if (!empty($_POST['expiry_date'])) {
            $expiry = sanitize($_POST['expiry_date']);
            // Validate date format (YYYY-MM-DD)
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiry)) {
                $advancedParams['expiry'] = $expiry;
            }
        }
        
        // Poll answer
        if (!empty($_POST['answer_number']) && intval($_POST['answer_number']) > 0) {
            $advancedParams['answer_number'] = intval($_POST['answer_number']);
        }
        
        // Groups/Invites
        if (!empty($_POST['groups'])) {
            $groups = array_filter(array_map('trim', explode("\n", $_POST['groups'])));
            if (!empty($groups)) {
                $advancedParams['groups'] = implode("\n", $groups);
            }
        }
        
        // Check if user has enough balance
        if ($userBalance < $price) {
            $alertMessage = 'Insufficient balance. Please add funds to your account.';
            $alertType = 'error';
        } else {
            // Serialize advanced parameters to JSON for storage
            $advancedParamsJson = !empty($advancedParams) ? json_encode($advancedParams) : null;
            
            // Create order in our database first
            $sql = "INSERT INTO orders (user_id, service_id, link, quantity, price, status, api_provider_id) 
                    VALUES ($userId, $serviceId, '$link', $quantity, $price, 'pending', " . 
                    ($service['api_provider_id'] ? $service['api_provider_id'] : "NULL") . ")";
            
            if ($conn->query($sql)) {
                $orderId = $conn->insert_id;
                
                // Store advanced parameters if they exist
                if ($advancedParamsJson) {
                    $conn->query("UPDATE orders SET parameters = '$advancedParamsJson' WHERE id = $orderId");
                }
                
                // Deduct balance
                if (updateUserBalance($userId, $price, 'subtract')) {
                    // Record transaction
                    recordTransaction($userId, $price, 'order', null, "Payment for order #$orderId");
                    
                    // Process affiliate commission
                    processAffiliateCommission($userId, $orderId, $price);
                    
                    // If service has API provider, place order via API
                    if ($service['api_provider_id']) {
                        $apiResult = placeOrderViaApi($serviceId, $link, $quantity, $advancedParams);
                        
                        if ($apiResult['success']) {
                            // Update order with API order ID
                            $conn->query("UPDATE orders SET 
                                         api_order_id = '{$apiResult['order_id']}', 
                                         api_response = '{$apiResult['response']}', 
                                         status = 'processing' 
                                         WHERE id = $orderId");
                            
                            $alertMessage = 'Order placed successfully! Order ID: ' . $orderId;
                            $alertType = 'success';
                        } else {
                            // Order created in our system but failed at API
                            $conn->query("UPDATE orders SET 
                                         api_response = '{$apiResult['response']}', 
                                         status = 'error' 
                                         WHERE id = $orderId");
                            
                            $alertMessage = 'Order created but API returned an error: ' . $apiResult['message'];
                            $alertType = 'error';
                        }
                    } else {
                        $alertMessage = 'Order placed successfully! Order ID: ' . $orderId;
                        $alertType = 'success';
                    }
                } else {
                    // Rollback order if balance update fails
                    $conn->query("DELETE FROM orders WHERE id = $orderId");
                    
                    $alertMessage = 'Failed to update balance';
                    $alertType = 'error';
                }
            } else {
                $alertMessage = 'Failed to create order: ' . $conn->error;
                $alertType = 'error';
            }
        }
    }
}

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
    
    .card {
        border-radius: 20px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    
    .form-input, select {
        border-radius: 12px;
        border: 2px solid #d1d5db;
        background-color: #ffffff;
        color: #1f2937;
        font-size: 1rem;
        transition: all 0.2s;
    }
    
    .form-input:focus, select:focus {
         border-color: #3b82f6;
         box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
         outline: none;
     }
    
    .btn-primary {
         background: #3b82f6;
         color: white;
         border-radius: 12px;
         padding: 0.875rem 1.5rem;
         font-weight: 600;
         transition: all 0.2s;
     }
     
     .btn-primary:hover {
         background: #2563eb;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    .service-icon {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
         justify-content: center;
         border-radius: 14px;
         background-color: #eff6ff;
         color: #3b82f6;
     }
    
    .service-select option {
        padding: 8px;
        font-size: 16px;
    }

</style>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 md:mb-8 gap-4">
        <div class="w-full md:w-auto">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Place New Order</h1>
            <p class="text-gray-500 text-sm mt-1">Fill out the form below to place your order</p>
        </div>
        <div class="w-full md:w-auto">
            <a href="../services/" class="inline-flex items-center text-primary-600 hover:text-primary-700 font-medium text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                View All Services
            </a>
        </div>
    </div>
    
    <!-- Alert Message -->
    <?php if ($alertMessage): ?>
        <div class="mb-6 md:mb-8 p-4 rounded-xl <?php echo $alertType == 'success' ? 'bg-primary-50 border border-primary-200' : 'bg-rose-50 border border-rose-200'; ?> flex items-start gap-3">
            <div class="flex-shrink-0 mt-0.5">
                <?php if ($alertType == 'success'): ?>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                <?php else: ?>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                <?php endif; ?>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-sm font-semibold <?php echo $alertType == 'success' ? 'text-primary-800' : 'text-rose-800'; ?>">
                    <?php echo $alertType == 'success' ? 'Success!' : 'Error!'; ?>
                </h3>
                <div class="mt-1 text-sm <?php echo $alertType == 'success' ? 'text-primary-700' : 'text-rose-700'; ?> break-words">
                    <p><?php echo $alertMessage; ?></p>
                    <?php if ($alertType == 'success'): ?>
                        <div class="mt-2">
                            <a href="../orders/" class="font-medium underline hover:no-underline">
                                View your orders
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
        <!-- Order Form -->
        <div class="lg:col-span-2 w-full">
            <div class="glass-card p-6 md:p-8">
                <form action="" method="post" id="order-form" class="w-full">
                    <!-- Category Selection -->
                    <div class="mb-5 md:mb-6">
                        <label for="category" class="block text-xs md:text-sm font-semibold text-gray-700 mb-2 uppercase tracking-wide">Category</label>
                        <div class="relative">
                            <select id="category" name="category" class="block w-full px-3 md:px-4 py-2.5 md:py-3 border-2 border-gray-200 focus:border-primary-500 bg-white rounded-xl appearance-none text-sm md:text-base" onchange="window.location.href='?category='+encodeURIComponent(this.value)">
                                <?php if (empty($categories)): ?>
                                    <option value="">No categories available</option>
                                <?php else: ?>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo $selectedCategory == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                                <svg class="h-4 w-4 md:h-5 md:w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Service Selection -->
                    <div class="mb-5 md:mb-6">
                        <label for="service" class="block text-xs md:text-sm font-semibold text-gray-700 mb-2 uppercase tracking-wide">Service</label>
                        <div class="relative">
                            <select id="service" name="service" class="block w-full px-3 md:px-4 py-2.5 md:py-3 border-2 border-gray-200 focus:border-primary-500 bg-white rounded-xl appearance-none text-sm md:text-base service-select" required>
                                <option value="">Select a service</option>
                                <?php foreach ($services as $service): ?>
                                    <option value="<?php echo $service['id']; ?>" 
                                            data-min="<?php echo $service['min_quantity']; ?>" 
                                            data-max="<?php echo $service['max_quantity']; ?>" 
                                            data-price="<?php echo $service['price']; ?>"
                                            data-description="<?php echo htmlspecialchars($service['description'] ?: 'No description available', ENT_QUOTES, 'UTF-8'); ?>"
                                            <?php echo ($preSelectedServiceId == $service['id']) ? 'selected' : ''; ?>>
                                        <?php
                                            $displayId = !empty($service['api_service_id']) ? $service['api_service_id'] : $service['id'];
                                        ?>
                                        <?php echo htmlspecialchars($displayId . ' - ' . $service['name'], ENT_QUOTES, 'UTF-8'); ?> - <?php echo formatCurrency($service['price']); ?> per 1000
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                                <svg class="h-4 w-4 md:h-5 md:w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Link Input -->
                    <div class="mb-5 md:mb-6">
                        <label for="link" class="block text-xs md:text-sm font-semibold text-gray-700 mb-2 uppercase tracking-wide">Link</label>
                        <input type="url" id="link" name="link" class="form-input block w-full px-3 md:px-4 py-2.5 md:py-3 border-2 border-gray-200 focus:border-primary-500 bg-white rounded-xl text-sm md:text-base" placeholder="https://www.instagram.com/your_post" required>
                        <p class="mt-1.5 text-xs text-gray-400">Enter the link to your social media post, profile, or video</p>
                    </div>
                    
                    <!-- Quantity Input -->
                    <div class="mb-6 md:mb-7">
                        <label for="quantity" class="block text-xs md:text-sm font-semibold text-gray-700 mb-2 uppercase tracking-wide">Quantity</label>
                        <input type="number" id="quantity" name="quantity" class="form-input block w-full px-3 md:px-4 py-2.5 md:py-3 border-2 border-gray-200 focus:border-primary-500 bg-white rounded-xl text-sm md:text-base" placeholder="100" required>
                        <p class="mt-1.5 text-xs text-gray-400">Min: <span id="min-quantity" class="font-semibold">100</span> - Max: <span id="max-quantity" class="font-semibold">2147483647</span></p>
                    </div>
                    
                    <!-- ADVANCED FIELDS SECTIONS (Hidden by default) -->
                    
                    <!-- Drip-feed Section -->
                    <div id="drip-feed-section" class="mb-6 md:mb-7 hidden">
                        <div class="border-t border-gray-200 pt-6 mb-6">
                            <h3 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-600" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.5 13a3.5 3.5 0 01-.369-6.98 4 4 0 107.753 1.977A4.5 4.5 0 1113.5 13H11V9.413l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13H5.5z" clip-rule="evenodd" />
                                </svg>
                                Drip-feed Options
                            </h3>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="runs" class="block text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">Runs</label>
                                    <input type="number" id="runs" name="runs" class="form-input block w-full px-3 md:px-4 py-2.5 md:py-3 border-2 border-gray-200 focus:border-primary-500 bg-white rounded-xl text-sm md:text-base" placeholder="1" min="1">
                                    <p class="mt-1.5 text-xs text-gray-400">Number of runs to split delivery</p>
                                </div>
                                
                                <div>
                                    <label for="interval" class="block text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">Interval (hours)</label>
                                    <input type="number" id="interval" name="interval" class="form-input block w-full px-3 md:px-4 py-2.5 md:py-3 border-2 border-gray-200 focus:border-primary-500 bg-white rounded-xl text-sm md:text-base" placeholder="24" min="1">
                                    <p class="mt-1.5 text-xs text-gray-400">Hours between each run</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Custom Comments Section -->
                    <div id="comments-section" class="mb-6 md:mb-7 hidden">
                        <div class="border-t border-gray-200 pt-6 mb-6">
                            <h3 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-600" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5z" />
                                    <path d="M3 7h14M3 11h14M3 15h14" stroke="currentColor" stroke-width="1"/>
                                </svg>
                                Custom Comments
                            </h3>
                            
                            <textarea id="comments" name="comments" class="form-input block w-full px-3 md:px-4 py-2.5 md:py-3 border-2 border-gray-200 focus:border-primary-500 bg-white rounded-xl text-sm md:text-base" placeholder="Enter each comment on a new line" rows="5"></textarea>
                            <p class="mt-1.5 text-xs text-gray-400">One comment per line. Leave empty to use default comments.</p>
                        </div>
                    </div>
                    
                    <!-- Mentions/Hashtags Section -->
                    <div id="mentions-section" class="mb-6 md:mb-7 hidden">
                        <div class="border-t border-gray-200 pt-6 mb-6">
                            <h3 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-600" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5z" />
                                </svg>
                                Mentions & Hashtags
                            </h3>
                            
                            <div class="mb-4">
                                <label for="usernames" class="block text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">Usernames</label>
                                <textarea id="usernames" name="usernames" class="form-input block w-full px-3 md:px-4 py-2.5 md:py-3 border-2 border-gray-200 focus:border-primary-500 bg-white rounded-xl text-sm md:text-base" placeholder="username1&#10;username2&#10;username3" rows="4"></textarea>
                                <p class="mt-1.5 text-xs text-gray-400">One username per line (without @ symbol)</p>
                            </div>
                            
                            <div>
                                <label for="hashtags" class="block text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">Hashtags</label>
                                <textarea id="hashtags" name="hashtags" class="form-input block w-full px-3 md:px-4 py-2.5 md:py-3 border-2 border-gray-200 focus:border-primary-500 bg-white rounded-xl text-sm md:text-base" placeholder="#hashtag1&#10;#hashtag2" rows="3"></textarea>
                                <p class="mt-1.5 text-xs text-gray-400">One hashtag per line (with # symbol)</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Media Link Section -->
                    <div id="media-section" class="mb-6 md:mb-7 hidden">
                        <div class="border-t border-gray-200 pt-6 mb-6">
                            <h3 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-600" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M5.5 13a3.5 3.5 0 01-.369-6.98 4 4 0 107.753 1.977A4.5 4.5 0 1113.5 13H11V9.413l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13H5.5z" clip-rule="evenodd" />
                                </svg>
                                Media Link
                            </h3>
                            
                            <label for="media" class="block text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">Link to Media</label>
                            <input type="url" id="media" name="media" class="form-input block w-full px-3 md:px-4 py-2.5 md:py-3 border-2 border-gray-200 focus:border-primary-500 bg-white rounded-xl text-sm md:text-base" placeholder="https://www.instagram.com/p/XXXXX/">
                            <p class="mt-1.5 text-xs text-gray-400">Enter the link to the media (photo/video)</p>
                        </div>
                    </div>
                    
                    <!-- Subscriptions Section -->
                    <div id="subscriptions-section" class="mb-6 md:mb-7 hidden">
                        <div class="border-t border-gray-200 pt-6 mb-6">
                            <h3 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-600" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M5.5 13a3.5 3.5 0 01-.369-6.98 4 4 0 107.753 1.977A4.5 4.5 0 1113.5 13H11V9.413l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13H5.5z" clip-rule="evenodd" />
                                </svg>
                                Subscription Options
                            </h3>
                            
                            <div class="mb-4">
                                <label for="sub-username" class="block text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">Username to Subscribe to</label>
                                <input type="text" id="sub-username" name="sub_username" class="form-input block w-full px-3 md:px-4 py-2.5 md:py-3 border-2 border-gray-200 focus:border-primary-500 bg-white rounded-xl text-sm md:text-base" placeholder="username">
                                <p class="mt-1.5 text-xs text-gray-400">Username to subscribe to (without @ symbol)</p>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="min-followers" class="block text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">Min Followers</label>
                                    <input type="number" id="min-followers" name="min_followers" class="form-input block w-full px-3 md:px-4 py-2.5 md:py-3 border-2 border-gray-200 focus:border-primary-500 bg-white rounded-xl text-sm md:text-base" placeholder="0" min="0">
                                    <p class="mt-1.5 text-xs text-gray-400">Minimum follower count</p>
                                </div>
                                
                                <div>
                                    <label for="max-followers" class="block text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">Max Followers</label>
                                    <input type="number" id="max-followers" name="max_followers" class="form-input block w-full px-3 md:px-4 py-2.5 md:py-3 border-2 border-gray-200 focus:border-primary-500 bg-white rounded-xl text-sm md:text-base" placeholder="999999999" min="0">
                                    <p class="mt-1.5 text-xs text-gray-400">Maximum follower count</p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="posts-delay" class="block text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">Delay (hours)</label>
                                    <input type="number" id="posts-delay" name="posts_delay" class="form-input block w-full px-3 md:px-4 py-2.5 md:py-3 border-2 border-gray-200 focus:border-primary-500 bg-white rounded-xl text-sm md:text-base" placeholder="0" min="0">
                                    <p class="mt-1.5 text-xs text-gray-400">Delay before subscription</p>
                                </div>
                                
                                <div>
                                    <label for="expiry-date" class="block text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">Expiry Date</label>
                                    <input type="date" id="expiry-date" name="expiry_date" class="form-input block w-full px-3 md:px-4 py-2.5 md:py-3 border-2 border-gray-200 focus:border-primary-500 bg-white rounded-xl text-sm md:text-base">
                                    <p class="mt-1.5 text-xs text-gray-400">Subscription expiry date</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Poll Section -->
                    <div id="poll-section" class="mb-6 md:mb-7 hidden">
                        <div class="border-t border-gray-200 pt-6 mb-6">
                            <h3 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-600" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4z" />
                                    <path d="M3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6z" />
                                    <path d="M14 9a1 1 0 00-1 1v6a1 1 0 001 1h1a1 1 0 001-1v-6a1 1 0 00-1-1h-1z" />
                                </svg>
                                Poll Settings
                            </h3>
                            
                            <label for="answer-number" class="block text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">Answer Number</label>
                            <input type="number" id="answer-number" name="answer_number" class="form-input block w-full px-3 md:px-4 py-2.5 md:py-3 border-2 border-gray-200 focus:border-primary-500 bg-white rounded-xl text-sm md:text-base" placeholder="1" min="1">
                            <p class="mt-1.5 text-xs text-gray-400">Which poll answer to vote for (1, 2, 3, or 4)</p>
                        </div>
                    </div>
                    
                    <!-- Groups/Invites Section -->
                    <div id="groups-section" class="mb-6 md:mb-7 hidden">
                        <div class="border-t border-gray-200 pt-6 mb-6">
                            <h3 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-600" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM9 6a3 3 0 11-6 0 3 3 0 016 0zM9 6a3 3 0 11-6 0 3 3 0 016 0zM9 6a3 3 0 11-6 0 3 3 0 016 0zM9 6a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Group Invites
                            </h3>
                            
                            <label for="groups" class="block text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">Group Links</label>
                            <textarea id="groups" name="groups" class="form-input block w-full px-3 md:px-4 py-2.5 md:py-3 border-2 border-gray-200 focus:border-primary-500 bg-white rounded-xl text-sm md:text-base" placeholder="https://t.me/group1&#10;https://t.me/group2" rows="5"></textarea>
                            <p class="mt-1.5 text-xs text-gray-400">One group link per line</p>
                        </div>
                    </div>
                    
                    <!-- Charge Display -->
                    <div class="mb-7 md:mb-8">
                        <label class="block text-xs md:text-sm font-semibold text-gray-700 mb-2 uppercase tracking-wide">Charge</label>
                        <div class="bg-gradient-to-r from-primary-50 to-primary-50 rounded-2xl p-4 md:p-6 border border-primary-100">
                            <div class="flex items-center gap-4">
                                <div class="p-2.5 md:p-3 bg-white rounded-xl shadow-sm flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 md:h-6 md:w-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-gray-500 font-medium mb-0.5">Total Amount</p>
                                    <span class="text-2xl md:text-3xl font-bold text-gray-800 block" id="charge">$0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" id="submit-button" class="btn-primary w-full flex items-center justify-center shadow-lg shadow-primary-100 text-sm md:text-base py-2.5 md:py-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 md:h-5 md:w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" />
                        </svg>
                        Submit Order
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="w-full lg:w-auto">
            <!-- Service Details Card -->
            <div class="glass-card p-6 md:p-6 lg:sticky lg:top-24">
                <div id="service-details">
                    <div class="flex items-center mb-6 gap-3">
                        <div id="service-icon" class="service-icon flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h2 class="text-lg md:text-xl font-bold text-gray-800">Service Info</h2>
                    </div>
                    
                    <div id="service-description" class="text-gray-600 text-sm leading-relaxed mb-6 bg-gray-50/50 p-4 rounded-xl border border-gray-100 break-words">
                        Select a service to see its description.
                    </div>
                </div>
                
                <!-- Balance Section -->
                <div class="pt-6 border-t border-gray-100">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Your Balance</h3>
                    <div class="flex items-center mb-6 gap-2">
                        <div class="w-10 h-10 bg-primary-50 rounded-full flex items-center justify-center text-primary-600 flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="text-2xl md:text-3xl font-bold text-primary-600"><?php echo formatCurrencyValue($userBalance); ?></span>
                    </div>
                    
                    <?php if ($userBalance < 10): ?>
                        <div class="p-4 bg-amber-50 rounded-xl border border-amber-100 mb-4">
                            <div class="flex gap-3">
                                <div class="flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-sm font-bold text-amber-800">Low Balance</h3>
                                    <p class="text-xs text-amber-700 mt-1">Add funds to continue placing orders.</p>
                                    <a href="../addfund/" class="mt-2 inline-block text-xs font-bold text-amber-800 hover:text-amber-900 underline">Add funds now</a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <a href="../addfund/" class="block w-full text-center py-2.5 md:py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-sm transition-colors">
                        Add Funds
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const serviceSelect = document.getElementById('service');
        const quantityInput = document.getElementById('quantity');
        const linkInput = document.getElementById('link');
        const minQuantitySpan = document.getElementById('min-quantity');
        const maxQuantitySpan = document.getElementById('max-quantity');
        const chargeDiv = document.getElementById('charge');
        const descriptionDiv = document.getElementById('service-description');
        const serviceIconDiv = document.getElementById('service-icon');
        const submitButton = document.getElementById('submit-button');
        const orderForm = document.getElementById('order-form');
        
        // Service type mappings - based on service name keywords
        const serviceTypeKeywords = {
            'comment': ['comment', 'replies'],
            'mentions': ['mention', 'tag', '@'],
            'subscriptions': ['subscription', 'subscriber', 'sub'],
            'dripfeed': ['drip', 'drip-feed', 'drip feed'],
            'poll': ['poll', 'vote'],
            'groups': ['group', 'invite', 'telegram'],
            'media': ['media', 'liker', 'like']
        };
        
        // All advanced field sections
        const advancedSections = {
            'drip-feed': document.getElementById('drip-feed-section'),
            'comments': document.getElementById('comments-section'),
            'mentions': document.getElementById('mentions-section'),
            'media': document.getElementById('media-section'),
            'subscriptions': document.getElementById('subscriptions-section'),
            'poll': document.getElementById('poll-section'),
            'groups': document.getElementById('groups-section')
        };
        
        // Validation rules for advanced parameters
        const validationRules = {
            'runs': { min: 1, max: 100, required: false },
            'interval': { min: 1, max: 999, required: false },
            'comments': { required: false, minLines: 1 },
            'usernames': { required: false, minLines: 1 },
            'hashtags': { required: false, minLines: 1 },
            'media': { required: false, pattern: /^https?:\/\// },
            'sub_username': { required: false },
            'min_followers': { min: 0, max: 999999999, required: false },
            'max_followers': { min: 0, max: 999999999, required: false },
            'posts_delay': { min: 0, max: 999, required: false },
            'expiry_date': { required: false, pattern: /^\d{4}-\d{2}-\d{2}$/ },
            'answer_number': { min: 1, max: 4, required: false },
            'groups': { required: false, minLines: 1 }
        };
        
        serviceSelect.addEventListener('change', updateServiceDetails);
        quantityInput.addEventListener('input', updateCharge);
        linkInput.addEventListener('blur', validateLink);
        orderForm.addEventListener('submit', validateForm);
        
        // Add validation event listeners to advanced fields
        addValidationListeners();
        
        function addValidationListeners() {
            // Drip-feed fields
            const runsFld = document.getElementById('runs');
            const intervalFld = document.getElementById('interval');
            if (runsFld) runsFld.addEventListener('blur', () => validateNumericField(runsFld, 1, 100));
            if (intervalFld) intervalFld.addEventListener('blur', () => validateNumericField(intervalFld, 1, 999));
            
            // Follower range fields
            const minFollowersFld = document.getElementById('min-followers');
            const maxFollowersFld = document.getElementById('max-followers');
            if (minFollowersFld) minFollowersFld.addEventListener('blur', () => validateNumericField(minFollowersFld, 0, 999999999));
            if (maxFollowersFld) maxFollowersFld.addEventListener('blur', validateFollowerRange);
            
            // Delay field
            const delayFld = document.getElementById('posts-delay');
            if (delayFld) delayFld.addEventListener('blur', () => validateNumericField(delayFld, 0, 999));
            
            // Answer number
            const answerFld = document.getElementById('answer-number');
            if (answerFld) answerFld.addEventListener('blur', () => validateNumericField(answerFld, 1, 4));
            
            // Media field
            const mediaFld = document.getElementById('media');
            if (mediaFld) mediaFld.addEventListener('blur', validateMediaUrl);
            
            // Date field
            const expiryFld = document.getElementById('expiry-date');
            if (expiryFld) expiryFld.addEventListener('blur', validateExpiryDate);
        }
        
        function validateNumericField(field, min, max) {
            const value = parseInt(field.value);
            
            if (field.value === '') {
                clearFieldError(field);
                return true;
            }
            
            if (isNaN(value) || value < min || value > max) {
                showFieldError(field, `Value must be between ${min} and ${max}`);
                return false;
            }
            
            clearFieldError(field);
            return true;
        }
        
        function validateFollowerRange() {
            const minField = document.getElementById('min-followers');
            const maxField = document.getElementById('max-followers');
            
            const min = minField.value ? parseInt(minField.value) : 0;
            const max = maxField.value ? parseInt(maxField.value) : 999999999;
            
            if (min > max) {
                showFieldError(maxField, 'Max followers must be greater than min followers');
                return false;
            }
            
            clearFieldError(maxField);
            return true;
        }
        
        function validateMediaUrl() {
            const field = document.getElementById('media');
            
            if (field.value === '') {
                clearFieldError(field);
                return true;
            }
            
            if (!field.value.match(/^https?:\/\//)) {
                showFieldError(field, 'URL must start with http:// or https://');
                return false;
            }
            
            clearFieldError(field);
            return true;
        }
        
        function validateExpiryDate() {
            const field = document.getElementById('expiry-date');
            
            if (field.value === '') {
                clearFieldError(field);
                return true;
            }
            
            // Date input will validate format automatically
            // Just check if it's a valid date
            const date = new Date(field.value);
            if (isNaN(date.getTime())) {
                showFieldError(field, 'Invalid date format');
                return false;
            }
            
            // Check if date is in the future
            if (date < new Date()) {
                showFieldError(field, 'Expiry date must be in the future');
                return false;
            }
            
            clearFieldError(field);
            return true;
        }
        
        function validateLink() {
            const value = linkInput.value.trim();
            
            if (!value) {
                showFieldError(linkInput, 'Link is required');
                return false;
            }
            
            if (!value.match(/^https?:\/\//i)) {
                showFieldError(linkInput, 'Link must start with http:// or https://');
                return false;
            }
            
            clearFieldError(linkInput);
            return true;
        }
        
        function validateForm(e) {
            let isValid = true;
            
            // Validate required fields
            if (!serviceSelect.value) {
                showFieldError(serviceSelect, 'Please select a service');
                isValid = false;
            } else {
                clearFieldError(serviceSelect);
            }
            
            if (!validateLink()) {
                isValid = false;
            }
            
            if (!quantityInput.value) {
                showFieldError(quantityInput, 'Quantity is required');
                isValid = false;
            } else {
                clearFieldError(quantityInput);
            }
            
            // Validate advanced fields if they're visible
            isValid = validateVisibleAdvancedFields() && isValid;
            
            if (!isValid) {
                e.preventDefault();
            }
        }
        
        function validateVisibleAdvancedFields() {
            let isValid = true;
            
            // Check drip-feed fields if visible
            if (!advancedSections['drip-feed'].classList.contains('hidden')) {
                const runs = document.getElementById('runs');
                const interval = document.getElementById('interval');
                
                if (runs.value && !validateNumericField(runs, 1, 100)) {
                    isValid = false;
                }
                if (interval.value && !validateNumericField(interval, 1, 999)) {
                    isValid = false;
                }
            }
            
            // Check subscriptions fields if visible
            if (!advancedSections['subscriptions'].classList.contains('hidden')) {
                const minF = document.getElementById('min-followers');
                const maxF = document.getElementById('max-followers');
                const delay = document.getElementById('posts-delay');
                const expiry = document.getElementById('expiry-date');
                
                if (minF.value && !validateNumericField(minF, 0, 999999999)) {
                    isValid = false;
                }
                if (maxF.value && !validateFollowerRange()) {
                    isValid = false;
                }
                if (delay.value && !validateNumericField(delay, 0, 999)) {
                    isValid = false;
                }
                if (expiry.value && !validateExpiryDate()) {
                    isValid = false;
                }
            }
            
            // Check media field if visible
            if (!advancedSections['media'].classList.contains('hidden')) {
                const media = document.getElementById('media');
                if (media.value && !validateMediaUrl()) {
                    isValid = false;
                }
            }
            
            // Check poll field if visible
            if (!advancedSections['poll'].classList.contains('hidden')) {
                const answer = document.getElementById('answer-number');
                if (answer.value && !validateNumericField(answer, 1, 4)) {
                    isValid = false;
                }
            }
            
            return isValid;
        }
        
        function showFieldError(field, message) {
            // Remove existing error message if present
            clearFieldError(field);
            
            // Add error styling to field
            field.classList.add('border-rose-500', 'focus:border-rose-500');
            field.classList.remove('border-gray-200', 'focus:border-primary-500');
            
            // Create and show error message
            const errorDiv = document.createElement('p');
            errorDiv.className = 'mt-1.5 text-xs text-rose-600 error-message';
            errorDiv.textContent = message;
            field.parentElement.appendChild(errorDiv);
        }
        
        function clearFieldError(field) {
            // Remove error styling
            field.classList.remove('border-rose-500', 'focus:border-rose-500');
            field.classList.add('border-gray-200', 'focus:border-primary-500');
            
            // Remove error message
            const errorMsg = field.parentElement.querySelector('.error-message');
            if (errorMsg) {
                errorMsg.remove();
            }
        }
        
        function detectServiceType(serviceName) {
            const lowerName = serviceName.toLowerCase();
            const detectedTypes = [];
            
            for (const [type, keywords] of Object.entries(serviceTypeKeywords)) {
                if (keywords.some(keyword => lowerName.includes(keyword))) {
                    detectedTypes.push(type);
                }
            }
            
            // Default to basic if no type detected
            return detectedTypes.length > 0 ? detectedTypes : ['basic'];
        }
        
        function showAdvancedFields(serviceTypes) {
            // Hide all sections first
            Object.values(advancedSections).forEach(section => {
                if (section) section.classList.add('hidden');
            });
            
            // Show only relevant sections
            serviceTypes.forEach(type => {
                if (advancedSections[type]) {
                    advancedSections[type].classList.remove('hidden');
                }
            });
        }
        
        function updateServiceDetails() {
            const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
            
            if (selectedOption.value) {
                const minQuantity = selectedOption.dataset.min;
                const maxQuantity = selectedOption.dataset.max;
                const description = selectedOption.dataset.description;
                const serviceName = selectedOption.text;
                
                minQuantitySpan.textContent = minQuantity;
                maxQuantitySpan.textContent = maxQuantity;
                
                quantityInput.min = minQuantity;
                quantityInput.max = maxQuantity;
                quantityInput.value = minQuantity;
                
                descriptionDiv.innerHTML = description;
                updateServiceIcon(serviceName);
                updateCharge();
                
                // Detect and show relevant fields
                const serviceTypes = detectServiceType(serviceName);
                showAdvancedFields(serviceTypes);
            } else {
                descriptionDiv.innerHTML = 'Select a service to see its description.';
                serviceIconDiv.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
                
                // Hide all advanced fields
                Object.values(advancedSections).forEach(section => {
                    if (section) section.classList.add('hidden');
                });
            }
        }
        
        function updateCharge() {
            const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
            
            if (selectedOption.value && quantityInput.value) {
                const price = parseFloat(selectedOption.dataset.price);
                const quantity = parseInt(quantityInput.value);
                
                if (!isNaN(price) && !isNaN(quantity)) {
                    const charge = (price * quantity / 1000).toFixed(2);
                    chargeDiv.textContent = '$' + charge;
                }
            }
        }
        
        function updateServiceIcon(serviceName) {
            serviceName = serviceName.toLowerCase();
            let icon = '';
            
            if (serviceName.includes('instagram')) {
                icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>';
            } else if (serviceName.includes('tiktok')) {
                icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>';
            } else if (serviceName.includes('facebook') || serviceName.includes('fb')) {
                icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>';
            } else if (serviceName.includes('youtube') || serviceName.includes('yt')) {
                icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>';
            } else {
                icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
            }
            serviceIconDiv.innerHTML = icon;
        }
        
        if (serviceSelect.value) {
            updateServiceDetails();
        }
    });
</script>

<?php include '../include/user-layout-end.php'; ?>
