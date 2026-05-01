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
    error_log("=== SERVICE IMPORT DEBUG ===");
    error_log("API Response Type: " . gettype($services));
    error_log("API Response Count: " . count($services ?? []));
    if (is_array($services) && count($services) > 0) {
        error_log("First Item Type: " . gettype($services[0]));
        if (is_object($services[0])) {
            error_log("First Item Keys: " . implode(", ", array_keys((array)$services[0])));
        } elseif (is_array($services[0])) {
            error_log("First Item Keys: " . implode(", ", array_keys($services[0])));
        }
    }
    error_log("Full Response: " . substr(json_encode($services), 0, 500));
    
    // Check if services were fetched successfully
    if (!$services) {
        error_log("ERROR: Services is empty or false");
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch services from API. Please check your API credentials.'
        ]);
        exit;
    }
    
    // Handle object-based error responses
    if (is_object($services) && isset($services->error)) {
        echo json_encode([
            'success' => false,
            'message' => 'API Error: ' . $services->error
        ]);
        exit;
    }
    
    // Convert to array for processing
    if (is_object($services)) {
        $services = json_decode(json_encode($services), true);
    }
    
    // Ensure we have an array
    if (!is_array($services)) {
        $services = (array) $services;
    }
    
    // Filter and normalize services to array format
    $validServices = [];
    error_log("Starting service filter loop, total items: " . count($services));
    
    foreach ($services as $key => $service) {
        // Services from API should have 'service' field (numeric ID)
        error_log("Item $key: Type=" . gettype($service));
        
        if (is_object($service)) {
            // Convert object to array for consistent processing
            $serviceArray = json_decode(json_encode($service), true);
            error_log("  Object converted to array, has 'service'? " . (isset($serviceArray['service']) ? 'YES' : 'NO'));
            if (isset($serviceArray['service'])) {
                $validServices[] = $serviceArray;
            }
        } elseif (is_array($service) && isset($service['service'])) {
            // Already an array with service field
            error_log("  Array already has 'service', adding");
            $validServices[] = $service;
        } else {
            error_log("  SKIPPED: is_array=" . (is_array($service) ? 'YES' : 'NO') . ", has_service=" . (is_array($service) && isset($service['service']) ? 'YES' : 'NO'));
        }
    }
    
    error_log("Filter complete: valid_services count = " . count($validServices));
    
    if (empty($validServices)) {
        error_log("ERROR: No valid services found after filtering");
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
            // Ensure service is an array for consistent property access
            if (is_object($service)) {
                $service = json_decode(json_encode($service), true);
            }
            
            // Access properties as array keys
            $rawName = $service['name'] ?? '';
            $rawCategory = $service['category'] ?? '';
            $rawDescription = $service['description'] ?? '';
            
            $serviceName = normalizeServiceText($rawName);
            $categoryName = normalizeServiceText($rawCategory);
            $description = normalizeServiceText($rawDescription);
            $apiServiceId = isset($service['service']) ? intval($service['service']) : 0;
            
            error_log("Processing service: id=$apiServiceId");
            error_log("  Raw: name='$rawName', category='$rawCategory'");
            error_log("  Normalized: name='$serviceName' (len=" . strlen($serviceName) . "), category='$categoryName' (len=" . strlen($categoryName) . ")");
            
            if ($serviceName === '' || $categoryName === '') {
                error_log("  SKIPPED: Empty name or category");
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
                        error_log("    Created category: '$categoryName' with id=$categoryId");
                    }
                }
            }
            
            // If still no category ID, create a default one
            if (!$categoryId && $hasCategoryIdColumn) {
                error_log("    WARNING: No category_id found, creating default category");
                if ($conn->query("INSERT INTO service_categories (name) VALUES ('Uncategorized')")) {
                    $categoryId = (int) $conn->insert_id;
                } else {
                    // Try to use existing Uncategorized
                    $uncatResult = $conn->query("SELECT id FROM service_categories WHERE name = 'Uncategorized' LIMIT 1");
                    if ($uncatResult && $uncatResult->num_rows > 0) {
                        $categoryId = (int) $uncatResult->fetch_assoc()['id'];
                    }
                }
            }
            
            error_log("    Final category_id=$categoryId for '$categoryName'");
            
            // Calculate price with markup
            $price = floatval($service['rate'] ?? 0) * (1 + $markup / 100);
            
            $minQty = isset($service['min']) ? intval($service['min']) : 0;
            $maxQty = isset($service['max']) ? intval($service['max']) : 0;
            $apiServiceId = isset($service['service']) ? intval($service['service']) : 0;
            
            error_log("  Final Values: apiServiceId=$apiServiceId, price=$price, minQty=$minQty, maxQty=$maxQty, categoryId=$categoryId");
            
            if ($apiServiceId <= 0) {
                error_log("  SKIPPED: Invalid apiServiceId ($apiServiceId)");
                $errors++;
                continue;
            }
            
            error_log("  Checking if service exists in DB...");
            
            // Check if service exists
            $checkQuery = "SELECT id FROM services 
                 WHERE api_service_id = $apiServiceId 
                 AND api_provider_id = $providerId";
            error_log("  Check Query: $checkQuery");
            $result = $conn->query($checkQuery);
            if ($result === false) {
                error_log("  ✗ QUERY ERROR: " . $conn->error);
                $errors++;
                continue;
            }
            $existingService = $result->fetch_assoc();
            error_log("  Exists in DB? " . ($existingService ? "YES (ID=" . $existingService['id'] . ")" : "NO"));
            
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
                    // category_id is NOT NULL, so always use a valid ID
                    $updates[] = "category_id = " . ((int)$categoryId ? (int)$categoryId : 1);
                }
                $updates[] = "price = $price";
                $updates[] = "min_quantity = $minQty";
                $updates[] = "max_quantity = $maxQty";
                $updates[] = "description = '$description'";
                if ($hasStatusColumn) {
                    $updates[] = "status = 1";
                }

                $sql = "UPDATE services SET " . implode(', ', $updates) . " WHERE id = {$existingService['id']}";
                error_log("  UPDATE SQL: " . substr($sql, 0, 200) . "...");
                
                if ($conn->query($sql)) {
                    $updated++;
                    error_log("  ✓ UPDATED");
                } else {
                    $errors++;
                    $errorDetails[] = "Update failed for service {$apiServiceId}: " . $conn->error;
                    error_log("  ✗ UPDATE ERROR: " . $conn->error);
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
                    // category_id is NOT NULL, so always use a valid ID
                    $values[] = (int)$categoryId ? (int)$categoryId : 1;  // Default to ID 1 or the ID we just created
                }

                if ($hasStatusColumn) {
                    $columns[] = 'status';
                    $values[] = 1;
                }

                $sql = "INSERT INTO services (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";
                error_log("  INSERT SQL: " . substr($sql, 0, 200) . "...");
                
                if ($conn->query($sql)) {
                    $imported++;
                    error_log("  ✓ INSERTED");
                } else {
                    $errors++;
                    $errorDetails[] = "Insert failed for service {$apiServiceId}: " . $conn->error;
                    error_log("  ✗ INSERT ERROR: " . $conn->error);
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
