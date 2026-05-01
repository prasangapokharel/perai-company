<?php
include '../config/dbconfig.php';
include '../include/functions.php';
include '../include/auth.php';

// Require admin
requireAdmin();

$pageTitle = 'Import Services';
$alertMessage = '';
$alertType = '';
$syncStats = null;

$serviceCategoriesTableExists = false;
$hasCategoryColumn = false;
$hasCategoryIdColumn = false;
$hasStatusColumn = false;
$hasSlugColumn = false;

$tableCheck = $conn->query("SHOW TABLES LIKE 'service_categories'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $serviceCategoriesTableExists = true;
}

$columnCheck = $conn->query("SHOW COLUMNS FROM services LIKE 'category'");
if ($columnCheck && $columnCheck->num_rows > 0) {
    $hasCategoryColumn = true;
}

$columnCheck = $conn->query("SHOW COLUMNS FROM services LIKE 'category_id'");
if ($columnCheck && $columnCheck->num_rows > 0) {
    $hasCategoryIdColumn = true;
}

$columnCheck = $conn->query("SHOW COLUMNS FROM services LIKE 'status'");
if ($columnCheck && $columnCheck->num_rows > 0) {
    $hasStatusColumn = true;
}

$columnCheck = $conn->query("SHOW COLUMNS FROM services LIKE 'slug'");
if ($columnCheck && $columnCheck->num_rows > 0) {
    $hasSlugColumn = true;
}

// Get API providers
$apiProviders = $conn->query("SELECT * FROM api_providers ORDER BY name");

// Process API provider form
if (isset($_POST['add_provider'])) {
    $name = sanitize($_POST['name']);
    $url = sanitize($_POST['url']);
    $apiKey = sanitize($_POST['api_key']);
    
    // Validate input
    if (empty($name) || empty($url) || empty($apiKey)) {
        $alertMessage = 'All fields are required';
        $alertType = 'error';
    } else {
        // Add API provider
        $sql = "INSERT INTO api_providers (name, url, api_key) VALUES ('$name', '$url', '$apiKey')";
        
        if ($conn->query($sql)) {
            $alertMessage = 'API provider added successfully';
            $alertType = 'success';
            
            // Refresh page to show new provider
            header("Location: import.php");
            exit;
        } else {
            $alertMessage = 'Failed to add API provider: ' . $conn->error;
            $alertType = 'error';
        }
    }
}

// Process import services form
if (isset($_POST['import_services'])) {
    $providerId = intval($_POST['provider_id']);
    $priceAdjustment = floatval($_POST['price_adjustment']);
    
    // Get provider details
    $provider = $conn->query("SELECT * FROM api_providers WHERE id = $providerId")->fetch_assoc();
    
    if (!$provider) {
        $alertMessage = 'Invalid API provider';
        $alertType = 'error';
    } else {
        // Create API instance
        $api = new Api($provider['url'], $provider['api_key']);
        
        // Get services from API
        $services = $api->services();
        
        if ($services && !isset($services->error)) {
            $importCount = 0;
            $updateCount = 0;
            $apiServiceIds = [];

            if ($hasStatusColumn) {
                $conn->query("UPDATE services SET status = 0 WHERE api_provider_id = $providerId");
            }

            foreach ($services as $service) {
                $serviceName = normalizeServiceText($service->name);
                $serviceCategory = normalizeServiceText($service->category);
                $serviceDescription = normalizeServiceText($service->description ?? '');
                
                if ($serviceName === '' || $serviceCategory === '') {
                    continue;
                }

                $apiServiceId = (int) $service->service;
                if ($apiServiceId <= 0) {
                    continue;
                }

                $minQty = isset($service->min) ? (int) $service->min : 0;
                $maxQty = isset($service->max) ? (int) $service->max : 0;

                $categoryId = null;
                if ($serviceCategoriesTableExists && $hasCategoryIdColumn) {
                    $escapedCategory = $conn->real_escape_string($serviceCategory);
                    $catResult = $conn->query("SELECT id FROM service_categories WHERE name = '$escapedCategory'");
                    if ($catResult && $catResult->num_rows > 0) {
                        $categoryId = (int) $catResult->fetch_assoc()['id'];
                    } else {
                        if ($conn->query("INSERT INTO service_categories (name) VALUES ('$escapedCategory')")) {
                            $categoryId = (int) $conn->insert_id;
                        }
                    }
                }
                
                // Adjust price
                $price = $service->rate * (1 + $priceAdjustment / 100);
                
                // Check if service exists
                $apiServiceIds[] = $apiServiceId;

                $existingService = $conn->query("SELECT id FROM services WHERE api_service_id = $apiServiceId AND api_provider_id = $providerId")->fetch_assoc();
                
                if ($existingService) {
                    // Update existing service
                    $updates = [];
                    $updates[] = "name = '{$conn->real_escape_string($serviceName)}'";
                    if ($hasSlugColumn) {
                        $baseSlug = slugify($serviceName);
                        if ($baseSlug === '') {
                            $baseSlug = 'service-' . $apiServiceId;
                        }
                        $uniqueSlug = $baseSlug;
                        $suffix = 1;
                        while ($conn->query("SELECT id FROM services WHERE slug = '$uniqueSlug' AND id != {$existingService['id']} LIMIT 1")->num_rows > 0) {
                            $uniqueSlug = $baseSlug . '-' . $suffix;
                            $suffix++;
                        }
                        $updates[] = "slug = '{$conn->real_escape_string($uniqueSlug)}'";
                    }
                    if ($hasCategoryColumn) {
                        $updates[] = "category = '{$conn->real_escape_string($serviceCategory)}'";
                    }
                    if ($hasCategoryIdColumn) {
                        $updates[] = "category_id = " . ($categoryId ? $categoryId : "NULL");
                    }
                    $updates[] = "price = $price";
                    $updates[] = "min_quantity = $minQty";
                    $updates[] = "max_quantity = $maxQty";
                    $updates[] = "description = '{$conn->real_escape_string($serviceDescription)}'";
                    if ($hasStatusColumn) {
                        $updates[] = "status = 1";
                    }

                    $sql = "UPDATE services SET " . implode(', ', $updates) . " WHERE id = {$existingService['id']}";
                    
                    if ($conn->query($sql)) {
                        $updateCount++;
                    }
                } else {
                    // Add new service
                    $columns = ['name', 'price', 'min_quantity', 'max_quantity', 'description', 'api_service_id', 'api_provider_id'];
                    $values = [
                        "'{$conn->real_escape_string($serviceName)}'",
                        $price,
                        $minQty,
                        $maxQty,
                        "'{$conn->real_escape_string($serviceDescription)}'",
                        $apiServiceId,
                        $providerId,
                    ];

                    // Always generate and include slug
                    $baseSlug = slugify($serviceName);
                    if ($baseSlug === '') {
                        $baseSlug = 'service-' . $apiServiceId;
                    }
                    $uniqueSlug = $baseSlug;
                    $suffix = 1;
                    while ($conn->query("SELECT id FROM services WHERE slug = '$uniqueSlug' LIMIT 1")->num_rows > 0) {
                        $uniqueSlug = $baseSlug . '-' . $suffix;
                        $suffix++;
                    }
                    $columns[] = 'slug';
                    $values[] = "'{$conn->real_escape_string($uniqueSlug)}'";


                    if ($hasCategoryColumn) {
                        $columns[] = 'category';
                        $values[] = "'{$conn->real_escape_string($serviceCategory)}'";
                    }

                    if ($hasCategoryIdColumn) {
                        $columns[] = 'category_id';
                        $values[] = $categoryId ? $categoryId : "NULL";
                    }

                    if ($hasStatusColumn) {
                        $columns[] = 'status';
                        $values[] = 1;
                    }

                    $sql = "INSERT INTO services (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";
                    
                    if ($conn->query($sql)) {
                        $importCount++;
                    }
                }
            }

            if ($hasStatusColumn) {
                $conn->query("DELETE FROM services WHERE api_provider_id = $providerId AND status = 0");
            }
            
            $syncStats = [
                'total' => count($services),
                'imported' => $importCount,
                'updated' => $updateCount,
            ];
            $alertMessage = "Successfully imported $importCount new services and updated $updateCount existing services";
            $alertType = 'success';
            
            // Update provider balance
            $balanceResponse = $api->balance();
            if ($balanceResponse && isset($balanceResponse->balance)) {
                $conn->query("UPDATE api_providers SET balance = {$balanceResponse->balance} WHERE id = $providerId");
            }
        } else {
            $alertMessage = 'Failed to fetch services from API: ' . ($services->error ?? 'Unknown error');
            $alertType = 'error';
        }
    }
}

// Process provider status toggle
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $providerId = intval($_GET['toggle_status']);
    
    // Get current status
    $provider = $conn->query("SELECT status FROM api_providers WHERE id = $providerId")->fetch_assoc();
    
    if ($provider) {
        $newStatus = $provider['status'] ? 0 : 1;
        
        if ($conn->query("UPDATE api_providers SET status = $newStatus WHERE id = $providerId")) {
            $alertMessage = 'Provider status updated successfully';
            $alertType = 'success';
        } else {
            $alertMessage = 'Failed to update provider status';
            $alertType = 'error';
        }
    }
}

// Process provider deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $providerId = intval($_GET['delete']);
    
    // Check if provider exists
    $provider = $conn->query("SELECT id FROM api_providers WHERE id = $providerId")->fetch_assoc();
    
    if ($provider) {
        // Delete provider and associated services
        $conn->query("DELETE FROM services WHERE api_provider_id = $providerId");
        
        if ($conn->query("DELETE FROM api_providers WHERE id = $providerId")) {
            if ($serviceCategoriesTableExists && $hasCategoryIdColumn) {
                $conn->query("DELETE FROM service_categories WHERE id NOT IN (SELECT DISTINCT category_id FROM services WHERE category_id IS NOT NULL)");
            }
            $alertMessage = 'Provider and associated services deleted successfully';
            $alertType = 'success';
        } else {
            $alertMessage = 'Failed to delete provider';
            $alertType = 'error';
        }
    }
}

// Process delete all services and categories
if (isset($_GET['delete_all_services']) && $_GET['delete_all_services'] === '1') {
    $conn->query("DELETE FROM services");
    $tableCheck = $conn->query("SHOW TABLES LIKE 'service_categories'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $conn->query("DELETE FROM service_categories");
    }

    $alertMessage = 'All services and categories deleted successfully';
    $alertType = 'success';
}

include 'admin-header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Import Services</h1>
    <a href="import.php?delete_all_services=1" class="px-3 py-2 bg-red-500 text-white rounded hover:bg-red-600 text-sm" onclick="return confirm('Are you sure you want to delete ALL services and categories? This cannot be undone.')">Delete All Services</a>
</div>

<?php if ($syncStats): ?>
    <div class="mb-6 p-4 rounded bg-primary-50 text-primary-800 border border-primary-100">
        Sync completed: Total <?php echo (int) $syncStats['total']; ?> services. Imported <?php echo (int) $syncStats['imported']; ?>, Updated <?php echo (int) $syncStats['updated']; ?>.
    </div>
<?php endif; ?>

<?php if ($alertMessage): ?>
    <div class="mb-6 p-4 rounded <?php echo $alertType == 'success' ? 'bg-primary-100 text-primary-800' : 'bg-red-100 text-red-800'; ?>">
        <?php echo $alertMessage; ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <h2 class="text-lg font-semibold mb-4">Add API Provider</h2>
        
        <form action="import.php" method="post">
            <div class="mb-4">
                <label for="name" class="block text-gray-600 mb-2">Provider Name</label>
                <input type="text" id="name" name="name" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:border-primary-500" required>
            </div>
            
            <div class="mb-4">
                <label for="url" class="block text-gray-600 mb-2">API URL</label>
                <input type="url" id="url" name="url" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:border-primary-500" required>
            </div>
            
            <div class="mb-4">
                <label for="api_key" class="block text-gray-600 mb-2">API Key</label>
                <input type="text" id="api_key" name="api_key" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:border-primary-500" required>
            </div>
            
            <button type="submit" name="add_provider" class="w-full bg-primary-500 text-white py-2 rounded hover:bg-primary-600 transition duration-200">Add Provider</button>
        </form>
    </div>
    
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <h2 class="text-lg font-semibold mb-4">Import Services</h2>
        
        <?php 
        // Reset the result pointer
        $apiProviders = $conn->query("SELECT * FROM api_providers WHERE status = 1 ORDER BY name");
        ?>
        
        <?php if ($apiProviders && $apiProviders->num_rows > 0): ?>
            <form action="import.php" method="post" onsubmit="return confirm('Sync services now? This will update all services from the provider.');">
                <div class="mb-4">
                    <label for="provider_id" class="block text-gray-600 mb-2">Select API Provider</label>
                    <select id="provider_id" name="provider_id" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:border-primary-500" required>
                        <?php while ($provider = $apiProviders->fetch_assoc()): ?>
                            <option value="<?php echo $provider['id']; ?>"><?php echo $provider['name']; ?> (Balance: <?php echo formatCurrency($provider['balance']); ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="price_adjustment" class="block text-gray-600 mb-2">Price Adjustment (%)</label>
                    <input type="number" id="price_adjustment" name="price_adjustment" value="30" min="0" step="0.1" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:border-primary-500" required>
                    <p class="text-gray-500 text-sm mt-1">Increase provider prices by this percentage. For example, 30% means provider price + 30%.</p>
                </div>
                
                <button type="submit" name="import_services" class="w-full bg-primary-500 text-white py-2 rounded hover:bg-primary-600 transition duration-200">Sync Services</button>
            </form>
        <?php else: ?>
            <div class="bg-gray-50 rounded-xl p-6 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <p class="text-gray-600">No active API providers found. Please add a provider first.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="mt-6 bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
    <h2 class="text-lg font-semibold mb-4">API Providers</h2>
    
    <?php 
    // Reset the result pointer
    $apiProviders = $conn->query("SELECT * FROM api_providers ORDER BY name");
    ?>
    
    <?php if ($apiProviders && $apiProviders->num_rows > 0): ?>
        <div class="overflow-x-auto -mx-4 sm:mx-0">
            <div class="inline-block min-w-full align-middle">
                <table class="min-w-full">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th class="px-4 py-3 font-medium">ID</th>
                            <th class="px-4 py-3 font-medium">Name</th>
                            <th class="px-4 py-3 font-medium">URL</th>
                            <th class="px-4 py-3 font-medium">API Key</th>
                            <th class="px-4 py-3 font-medium">Balance</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($provider = $apiProviders->fetch_assoc()): ?>
                            <tr class="border-t border-gray-100 hover:bg-gray-50">
                                <td class="px-4 py-3"><?php echo $provider['id']; ?></td>
                                <td class="px-4 py-3"><?php echo $provider['name']; ?></td>
                                <td class="px-4 py-3 max-w-xs truncate"><?php echo $provider['url']; ?></td>
                                <td class="px-4 py-3">
                                    <span class="text-gray-500">
                                        <?php echo substr($provider['api_key'], 0, 10) . '...'; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3"><?php echo formatCurrency($provider['balance']); ?></td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded text-xs <?php echo $provider['status'] ? 'bg-primary-100 text-primary-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $provider['status'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="edit_provider.php?id=<?php echo $provider['id']; ?>" class="px-2 py-1 bg-primary-500 text-white rounded hover:bg-primary-600 text-xs">Edit</a>
                                        <a href="import.php?toggle_status=<?php echo $provider['id']; ?>" class="px-2 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600 text-xs">
                                            <?php echo $provider['status'] ? 'Disable' : 'Enable'; ?>
                                        </a>
                                        <a href="import.php?delete=<?php echo $provider['id']; ?>" class="px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-xs" onclick="return confirm('Are you sure you want to delete this provider? This will also delete all associated services.')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-gray-50 rounded-xl p-6 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <p class="text-gray-600">No API providers found</p>
        </div>
    <?php endif; ?>
</div>

<div class="mt-6 bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
    <h2 class="text-lg font-semibold mb-4">Check API Provider Balance</h2>
    
    <?php 
    // Reset the result pointer
    $apiProviders = $conn->query("SELECT * FROM api_providers WHERE status = 1 ORDER BY name");
    ?>
    
    <?php if ($apiProviders && $apiProviders->num_rows > 0): ?>
        <form action="check_balance.php" method="post">
            <div class="mb-4">
                <label for="provider_id_balance" class="block text-gray-600 mb-2">Select API Provider</label>
                <select id="provider_id_balance" name="provider_id" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:border-primary-500" required>
                    <?php while ($provider = $apiProviders->fetch_assoc()): ?>
                        <option value="<?php echo $provider['id']; ?>"><?php echo $provider['name']; ?> (Current Balance: <?php echo formatCurrency($provider['balance']); ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <button type="submit" name="check_balance" class="w-full bg-primary-500 text-white py-2 rounded hover:bg-primary-600 transition duration-200">Check Balance</button>
        </form>
    <?php else: ?>
        <div class="bg-gray-50 rounded-xl p-6 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-gray-600">No active API providers found</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'admin-footer.php'; ?>
