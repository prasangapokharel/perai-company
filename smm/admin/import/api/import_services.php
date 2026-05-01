<?php
header('Content-Type: application/json');
include '../../../config/dbconfig.php';
include '../../../include/functions.php';
include '../../../include/auth.php';

// Require admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['provider_id'])) {
    echo json_encode(['success' => false, 'message' => 'Provider ID required']);
    exit;
}

$providerId = intval($input['provider_id']);
$markup = isset($input['markup']) ? floatval($input['markup']) : 30;

// Get provider
$provider = $conn->query("SELECT * FROM api_providers WHERE id = $providerId")->fetch_assoc();

if (!$provider) {
    echo json_encode(['success' => false, 'message' => 'Provider not found']);
    exit;
}

try {
    // Initialize API with provider credentials
    $api = new Api($provider['url'], $provider['api_key']);
    
    // Fetch services
    $services = $api->services();
    
    // Debug: log what we got
    error_log("API Response Type: " . gettype($services));
    error_log("API Response: " . json_encode($services));
    
    // Check if services were fetched successfully
    if (!$services) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch services from API. Please check your API credentials.'
        ]);
        exit;
    }
    
    if (isset($services->error)) {
        echo json_encode([
            'success' => false,
            'message' => 'API Error: ' . $services->error
        ]);
        exit;
    }
    
    // Convert to array if needed
    if (!is_array($services)) {
        $services = (array) $services;
    }
    
    // Filter out error responses
    $validServices = [];
    foreach ($services as $key => $service) {
        // Services from API should have 'service' field (numeric ID)
        if (is_object($service)) {
            if (isset($service->service)) {
                $validServices[] = $service;
            }
        } elseif (is_array($service) && isset($service['service'])) {
            $validServices[] = (object) $service;
        }
    }
    
    if (empty($validServices)) {
        echo json_encode([
            'success' => false,
            'message' => 'No valid services found. Check API response format.',
            'debug' => [
                'total_items' => count($services),
                'first_item_keys' => isset($services[0]) ? (is_object($services[0]) ? array_keys((array)$services[0]) : array_keys($services[0])) : []
            ]
        ]);
        exit;
    }
    
    $services = $validServices;
    
    $imported = 0;
    $updated = 0;
    $errors = 0;
    $errorDetails = [];

    // Check schema
    $serviceCategoriesTableExists = false;
    $hasCategoryIdColumn = false;
    $hasCategoryColumn = false;
    $hasStatusColumn = false;
    $hasSlugColumn = false;

    $tableCheck = $conn->query("SHOW TABLES LIKE 'service_categories'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $serviceCategoriesTableExists = true;
    }

    $columnCheck = $conn->query("SHOW COLUMNS FROM services LIKE 'category_id'");
    if ($columnCheck && $columnCheck->num_rows > 0) {
        $hasCategoryIdColumn = true;
    }

    $columnCheck = $conn->query("SHOW COLUMNS FROM services LIKE 'category'");
    if ($columnCheck && $columnCheck->num_rows > 0) {
        $hasCategoryColumn = true;
    }

    $columnCheck = $conn->query("SHOW COLUMNS FROM services LIKE 'status'");
    if ($columnCheck && $columnCheck->num_rows > 0) {
        $hasStatusColumn = true;
    }

    $columnCheck = $conn->query("SHOW COLUMNS FROM services LIKE 'slug'");
    if ($columnCheck && $columnCheck->num_rows > 0) {
        $hasSlugColumn = true;
    }

    if ($hasStatusColumn) {
        $conn->query("UPDATE services SET status = 0 WHERE api_provider_id = $providerId");
    }
    
    // Import services
    foreach ($services as $service) {
        try {
            // Ensure service is an object
            if (is_array($service)) {
                $service = (object) $service;
            }
            
            $serviceName = normalizeServiceText($service->name ?? '');
            $categoryName = normalizeServiceText($service->category ?? '');
            $description = normalizeServiceText($service->description ?? '');
            
            if ($serviceName === '' || $categoryName === '') {
                $errors++;
                continue;
            }
            
            $serviceName = $conn->real_escape_string($serviceName);
            $categoryName = $conn->real_escape_string($categoryName);
            $description = $conn->real_escape_string($description);
            
            $categoryId = null;
            if ($serviceCategoriesTableExists && $hasCategoryIdColumn) {
                $catResult = $conn->query("SELECT id FROM service_categories WHERE name = '$categoryName'");
                if ($catResult && $catResult->num_rows > 0) {
                    $categoryId = (int) $catResult->fetch_assoc()['id'];
                } else {
                    if ($conn->query("INSERT INTO service_categories (name) VALUES ('$categoryName')")) {
                        $categoryId = (int) $conn->insert_id;
                    }
                }
            }
            
            // Calculate price with markup
            $price = floatval($service->rate ?? 0) * (1 + $markup / 100);
            
            $minQty = isset($service->min) ? intval($service->min) : 0;
            $maxQty = isset($service->max) ? intval($service->max) : 0;
            $apiServiceId = isset($service->service) ? intval($service->service) : 0;
            
            if ($apiServiceId <= 0) {
                $errors++;
                continue;
            }
            
            // Check if service exists
            $existingService = $conn->query(
                "SELECT id FROM services 
                 WHERE api_service_id = $apiServiceId 
                 AND api_provider_id = $providerId"
            )->fetch_assoc();
            
            if ($existingService) {
                // Update existing service
                $updates = [];
                $updates[] = "name = '$serviceName'";
                
                // Generate and update slug
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
                $updates[] = "slug = '" . $conn->real_escape_string($uniqueSlug) . "'";
                
                if ($hasCategoryColumn) {
                    $updates[] = "category = '$categoryName'";
                }
                if ($hasCategoryIdColumn) {
                    $updates[] = "category_id = " . ($categoryId ? $categoryId : "NULL");
                }
                $updates[] = "price = $price";
                $updates[] = "min_quantity = $minQty";
                $updates[] = "max_quantity = $maxQty";
                $updates[] = "description = '$description'";
                if ($hasStatusColumn) {
                    $updates[] = "status = 1";
                }

                $sql = "UPDATE services SET " . implode(', ', $updates) . " WHERE id = {$existingService['id']}";
                
                if ($conn->query($sql)) {
                    $updated++;
                } else {
                    $errors++;
                    $errorDetails[] = "Update failed for service {$apiServiceId}: " . $conn->error;
                }
            } else {
                // Insert new service
                $columns = ['name', 'price', 'min_quantity', 'max_quantity', 'description', 'api_service_id', 'api_provider_id', 'slug'];
                
                // Generate unique slug
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
                
                $values = [
                    "'$serviceName'",
                    $price,
                    $minQty,
                    $maxQty,
                    "'$description'",
                    $apiServiceId,
                    $providerId,
                    "'" . $conn->real_escape_string($uniqueSlug) . "'",
                ];

                if ($hasCategoryColumn) {
                    $columns[] = 'category';
                    $values[] = "'$categoryName'";
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
                    $imported++;
                } else {
                    $errors++;
                    $errorDetails[] = "Insert failed for service {$apiServiceId}: " . $conn->error;
                }
            }
        } catch (Exception $e) {
            $errors++;
            $errorDetails[] = "Exception: " . $e->getMessage();
        }
    }

    if ($hasStatusColumn) {
        $conn->query("DELETE FROM services WHERE api_provider_id = $providerId AND status = 0");
    }
    
    // Update provider balance
    $balanceResponse = $api->balance();
    if ($balanceResponse && isset($balanceResponse->balance)) {
        $balance = floatval($balanceResponse->balance);
        $conn->query("UPDATE api_providers SET balance = $balance WHERE id = $providerId");
    }
    
    $response = [
        'success' => true,
        'imported' => $imported,
        'updated' => $updated,
        'errors' => $errors,
        'message' => "Imported $imported new services and updated $updated existing services"
    ];
    
    if (!empty($errorDetails)) {
        $response['errors_details'] = $errorDetails;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
