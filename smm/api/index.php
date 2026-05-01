<?php
include '../config/dbconfig.php';
include '../include/functions.php';
include '../include/auth.php';

// Require login
requireLogin();

$pageTitle = 'API Documentation';
$userId = $_SESSION['user_id'];

// Get user's API key
$apiKeyStmt = $conn->prepare("SELECT api_key, expiry_date, is_active FROM api_keys WHERE user_id = ?");
$apiKeyStmt->bind_param("i", $userId);
$apiKeyStmt->execute();
$apiKeyResult = $apiKeyStmt->get_result()->fetch_assoc();

$hasApiKey = !empty($apiKeyResult) && $apiKeyResult['is_active'];
$apiKey = $hasApiKey ? $apiKeyResult['api_key'] : null;
$expiryDate = $hasApiKey ? $apiKeyResult['expiry_date'] : null;

// Mask API key for display
$maskedKey = $hasApiKey ? substr($apiKey, 0, 8) . str_repeat('*', strlen($apiKey) - 16) . substr($apiKey, -8) : null;

include '../include/layout-header.php';
?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumb -->
    <nav class="flex mb-8 text-sm text-gray-500" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="../dashboard/" class="inline-flex items-center hover:text-primary-600 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>
                    Dashboard
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                    <span class="ml-1 md:ml-2 text-gray-900 font-medium">API Documentation</span>
                </div>
            </li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900">API Documentation</h1>
        <p class="text-gray-500 mt-2">Complete reference for integrating with our OkxSmm API</p>
    </div>

    <!-- API Key Section -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8 border-l-4 border-primary-500">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Your API Key</h2>
        
        <?php if ($hasApiKey): ?>
            <div class="bg-gray-50 rounded p-4 mb-4 flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm text-gray-500 mb-1">Active API Key</p>
                    <p class="font-mono text-gray-900 text-lg"><?php echo htmlspecialchars($maskedKey); ?></p>
                    <p class="text-xs text-gray-500 mt-2">Expires: <?php echo htmlspecialchars($expiryDate); ?></p>
                </div>
                <button onclick="copyToClipboard('<?php echo htmlspecialchars($apiKey); ?>', this)" class="ml-4 px-6 py-3 bg-primary-600 text-white rounded hover:bg-primary-700 transition-colors whitespace-nowrap">
                    Copy Key
                </button>
            </div>
            <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded p-3 mb-4">
                <strong>Security Note:</strong> Keep your API key private. Anyone with your key can access your account. Use the Account Settings page to regenerate if compromised.
            </p>
            <a href="../account/" class="text-primary-600 hover:text-primary-800 text-sm font-medium">Manage API Keys</a>
        <?php else: ?>
            <div class="bg-yellow-50 border border-yellow-200 rounded p-4 mb-4">
                <p class="text-sm text-yellow-800">You don't have an active API key yet. Generate one to start using the API.</p>
            </div>
            <a href="../account/" class="inline-block px-6 py-3 bg-primary-600 text-white rounded hover:bg-primary-700 transition-colors">
                Generate API Key
            </a>
        <?php endif; ?>
    </div>

    <!-- Quick Start Section -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Quick Start</h2>
        
        <div class="space-y-4">
            <div>
                <h3 class="font-bold text-gray-900 mb-2">Base URL</h3>
                <div class="bg-gray-900 text-gray-100 p-3 rounded font-mono text-sm overflow-x-auto">
                    https://<?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?>/api/v1/
                </div>
            </div>

            <div>
                <h3 class="font-bold text-gray-900 mb-2">Authentication</h3>
                <p class="text-gray-600 mb-2">Include your API key in the POST request data:</p>
                <div class="bg-gray-900 text-gray-100 p-3 rounded font-mono text-sm overflow-x-auto">
                    api_key=YOUR_API_KEY
                </div>
            </div>

            <div>
                <h3 class="font-bold text-gray-900 mb-2">PHP Example</h3>
                <div class="bg-gray-900 text-gray-100 p-4 rounded overflow-x-auto">
                    <pre class="text-sm"><code>&lt;?php
$apiKey = '<?php echo $hasApiKey ? htmlspecialchars($apiKey) : 'YOUR_API_KEY'; ?>';
$baseUrl = 'https://<?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?>/api/v1/';

// Get list of services
$data = [
    'action' => 'services',
    'api_key' => $apiKey,
];

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query($data)
    ]
]);

$response = file_get_contents($baseUrl, false, $context);
$result = json_decode($response, true);

if ($result['success']) {
    foreach ($result['services'] as $service) {
        echo $service['name'] . ' - Rate: $' . $service['rate'] . '&lt;br&gt;';
    }
} else {
    echo 'Error: ' . $result['message'];
}
?&gt;</code></pre>
                </div>
            </div>

            <div>
                <h3 class="font-bold text-gray-900 mb-2">cURL Example</h3>
                <div class="bg-gray-900 text-gray-100 p-3 rounded font-mono text-sm overflow-x-auto">
                    <pre><code>curl -X POST https://<?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?>/api/v1/ \
  -d "action=services&api_key=<?php echo $hasApiKey ? htmlspecialchars($apiKey) : 'YOUR_API_KEY'; ?>"</code></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Endpoints Section -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Available Endpoints</h2>
        
        <!-- Services Endpoint -->
        <div class="mb-8 pb-8 border-b border-gray-200">
            <h3 class="text-xl font-bold text-gray-900 mb-3 flex items-center">
                <span class="inline-block bg-primary-100 text-primary-800 px-3 py-1 rounded text-sm font-semibold mr-2">GET</span>
                <span class="font-mono">services</span>
            </h3>
            <p class="text-gray-600 mb-4">Get a list of all available services with pricing and details.</p>
            
            <div class="space-y-3">
                <div>
                    <h4 class="font-bold text-gray-900 mb-1">Parameters</h4>
                    <ul class="list-disc list-inside text-gray-700 text-sm space-y-1">
                        <li><code class="bg-gray-100 px-2 py-1 rounded-xl">api_key</code> - Your API key (required)</li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold text-gray-900 mb-1">Response Example</h4>
                    <div class="bg-gray-900 text-gray-100 p-3 rounded font-mono text-sm overflow-x-auto">
                        <pre><code>{
  "success": true,
  "message": "Services retrieved successfully",
  "services": [
    {
      "id": 1,
      "name": "Instagram Followers",
      "rate": 0.50,
      "category": "📱 Social Media",
      "min": 10,
      "max": 500000,
      "dripfeed": true,
      "refill": true,
      "cancel": true
    }
  ]
}</code></pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Order Endpoint -->
        <div class="mb-8 pb-8 border-b border-gray-200">
            <h3 class="text-xl font-bold text-gray-900 mb-3 flex items-center">
                <span class="inline-block bg-primary-100 text-primary-800 px-3 py-1 rounded text-sm font-semibold mr-2">POST</span>
                <span class="font-mono">add</span>
            </h3>
            <p class="text-gray-600 mb-4">Place a new order for a service.</p>
            
            <div class="space-y-3">
                <div>
                    <h4 class="font-bold text-gray-900 mb-1">Parameters</h4>
                    <ul class="list-disc list-inside text-gray-700 text-sm space-y-1">
                        <li><code class="bg-gray-100 px-2 py-1 rounded-xl">api_key</code> - Your API key (required)</li>
                        <li><code class="bg-gray-100 px-2 py-1 rounded-xl">service_id</code> - ID of the service (required)</li>
                        <li><code class="bg-gray-100 px-2 py-1 rounded-xl">link</code> - Target link (required)</li>
                        <li><code class="bg-gray-100 px-2 py-1 rounded-xl">quantity</code> - Quantity to order (required)</li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold text-gray-900 mb-1">Response Example</h4>
                    <div class="bg-gray-900 text-gray-100 p-3 rounded font-mono text-sm overflow-x-auto">
                        <pre><code>{
  "success": true,
  "message": "Order placed successfully",
  "order_id": 12345,
  "charge": 25.50,
  "new_balance": 474.50
}</code></pre>
                    </div>
                </div>

                <div>
                    <h4 class="font-bold text-gray-900 mb-1">cURL Example</h4>
                    <div class="bg-gray-900 text-gray-100 p-3 rounded font-mono text-sm overflow-x-auto">
                        <pre><code>curl -X POST https://<?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?>/api/v1/ \
  -d "action=add&api_key=<?php echo $hasApiKey ? htmlspecialchars($apiKey) : 'YOUR_API_KEY'; ?>&service_id=1&link=https://instagram.com/user&quantity=100"</code></pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Endpoint -->
        <div class="mb-8 pb-8 border-b border-gray-200">
            <h3 class="text-xl font-bold text-gray-900 mb-3 flex items-center">
                <span class="inline-block bg-yellow-100 text-yellow-800 px-3 py-1 rounded text-sm font-semibold mr-2">GET</span>
                <span class="font-mono">status</span>
            </h3>
            <p class="text-gray-600 mb-4">Get the current status of an order.</p>
            
            <div class="space-y-3">
                <div>
                    <h4 class="font-bold text-gray-900 mb-1">Parameters</h4>
                    <ul class="list-disc list-inside text-gray-700 text-sm space-y-1">
                        <li><code class="bg-gray-100 px-2 py-1 rounded-xl">api_key</code> - Your API key (required)</li>
                        <li><code class="bg-gray-100 px-2 py-1 rounded-xl">order_id</code> - Order ID (required)</li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold text-gray-900 mb-1">Response Example</h4>
                    <div class="bg-gray-900 text-gray-100 p-3 rounded font-mono text-sm overflow-x-auto">
                        <pre><code>{
  "success": true,
  "message": "Order status retrieved",
  "order": {
    "id": 12345,
    "status": "completed",
    "charge": 25.50,
    "quantity": 100
  }
}</code></pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- Balance Endpoint -->
        <div class="mb-8 pb-8 border-b border-gray-200">
            <h3 class="text-xl font-bold text-gray-900 mb-3 flex items-center">
                <span class="inline-block bg-purple-100 text-purple-800 px-3 py-1 rounded text-sm font-semibold mr-2">GET</span>
                <span class="font-mono">balance</span>
            </h3>
            <p class="text-gray-600 mb-4">Get your current account balance.</p>
            
            <div class="space-y-3">
                <div>
                    <h4 class="font-bold text-gray-900 mb-1">Parameters</h4>
                    <ul class="list-disc list-inside text-gray-700 text-sm space-y-1">
                        <li><code class="bg-gray-100 px-2 py-1 rounded-xl">api_key</code> - Your API key (required)</li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold text-gray-900 mb-1">Response Example</h4>
                    <div class="bg-gray-900 text-gray-100 p-3 rounded font-mono text-sm overflow-x-auto">
                        <pre><code>{
  "success": true,
  "message": "Balance retrieved successfully",
  "balance": 500.00
}</code></pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- Refill Endpoint -->
        <div class="mb-8 pb-8 border-b border-gray-200">
            <h3 class="text-xl font-bold text-gray-900 mb-3 flex items-center">
                <span class="inline-block bg-primary-100 text-primary-800 px-3 py-1 rounded text-sm font-semibold mr-2">POST</span>
                <span class="font-mono">refill</span>
            </h3>
            <p class="text-gray-600 mb-4">Request a refill for an order.</p>
            
            <div class="space-y-3">
                <div>
                    <h4 class="font-bold text-gray-900 mb-1">Parameters</h4>
                    <ul class="list-disc list-inside text-gray-700 text-sm space-y-1">
                        <li><code class="bg-gray-100 px-2 py-1 rounded-xl">api_key</code> - Your API key (required)</li>
                        <li><code class="bg-gray-100 px-2 py-1 rounded-xl">order_id</code> - Order ID (required)</li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold text-gray-900 mb-1">Response Example</h4>
                    <div class="bg-gray-900 text-gray-100 p-3 rounded font-mono text-sm overflow-x-auto">
                        <pre><code>{
  "success": true,
  "message": "Refill request submitted successfully",
  "refill_id": 54321
}</code></pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancel Endpoint -->
        <div class="mb-8 pb-8 border-b border-gray-200">
            <h3 class="text-xl font-bold text-gray-900 mb-3 flex items-center">
                <span class="inline-block bg-red-100 text-red-800 px-3 py-1 rounded text-sm font-semibold mr-2">POST</span>
                <span class="font-mono">cancel</span>
            </h3>
            <p class="text-gray-600 mb-4">Cancel an order (if service supports cancellation).</p>
            
            <div class="space-y-3">
                <div>
                    <h4 class="font-bold text-gray-900 mb-1">Parameters</h4>
                    <ul class="list-disc list-inside text-gray-700 text-sm space-y-1">
                        <li><code class="bg-gray-100 px-2 py-1 rounded-xl">api_key</code> - Your API key (required)</li>
                        <li><code class="bg-gray-100 px-2 py-1 rounded-xl">order_id</code> - Order ID (required)</li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold text-gray-900 mb-1">Response Example</h4>
                    <div class="bg-gray-900 text-gray-100 p-3 rounded font-mono text-sm overflow-x-auto">
                        <pre><code>{
  "success": true,
  "message": "Order cancelled successfully"
}</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Codes Section -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Error Codes</h2>
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="text-left px-4 py-2 font-bold">HTTP Status</th>
                        <th class="text-left px-4 py-2 font-bold">Error Code</th>
                        <th class="text-left px-4 py-2 font-bold">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr>
                        <td class="px-4 py-2 font-mono text-primary-600">200</td>
                        <td class="px-4 py-2">N/A</td>
                        <td class="px-4 py-2">Request successful</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono text-yellow-600">400</td>
                        <td class="px-4 py-2">MISSING_PARAMETER</td>
                        <td class="px-4 py-2">Missing required parameter</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono text-yellow-600">400</td>
                        <td class="px-4 py-2">INVALID_PARAMETER</td>
                        <td class="px-4 py-2">Parameter value is invalid</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono text-red-600">401</td>
                        <td class="px-4 py-2">INVALID_API_KEY</td>
                        <td class="px-4 py-2">API key is invalid or expired</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono text-red-600">402</td>
                        <td class="px-4 py-2">INSUFFICIENT_BALANCE</td>
                        <td class="px-4 py-2">Account balance is insufficient</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono text-red-600">404</td>
                        <td class="px-4 py-2">NOT_FOUND</td>
                        <td class="px-4 py-2">Order or service not found</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-mono text-red-600">500</td>
                        <td class="px-4 py-2">SERVER_ERROR</td>
                        <td class="px-4 py-2">Internal server error</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Best Practices Section -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Best Practices</h2>
        
        <div class="space-y-4">
            <div class="flex gap-4">
                <div class="flex-shrink-0 text-primary-600 text-xl">✓</div>
                <div>
                    <h3 class="font-bold text-gray-900">Keep API Key Secure</h3>
                    <p class="text-gray-600 text-sm">Never share your API key or commit it to version control. Use environment variables instead.</p>
                </div>
            </div>
            <div class="flex gap-4">
                <div class="flex-shrink-0 text-primary-600 text-xl">✓</div>
                <div>
                    <h3 class="font-bold text-gray-900">Validate Responses</h3>
                    <p class="text-gray-600 text-sm">Always check the success field and handle errors appropriately in your application.</p>
                </div>
            </div>
            <div class="flex gap-4">
                <div class="flex-shrink-0 text-primary-600 text-xl">✓</div>
                <div>
                    <h3 class="font-bold text-gray-900">Check Balance Before Orders</h3>
                    <p class="text-gray-600 text-sm">Call the balance endpoint to verify sufficient funds before placing bulk orders.</p>
                </div>
            </div>
            <div class="flex gap-4">
                <div class="flex-shrink-0 text-primary-600 text-xl">✓</div>
                <div>
                    <h3 class="font-bold text-gray-900">Handle Rate Limiting</h3>
                    <p class="text-gray-600 text-sm">Implement exponential backoff when making rapid requests to avoid service disruption.</p>
                </div>
            </div>
            <div class="flex gap-4">
                <div class="flex-shrink-0 text-primary-600 text-xl">✓</div>
                <div>
                    <h3 class="font-bold text-gray-900">Monitor Order Status</h3>
                    <p class="text-gray-600 text-sm">Periodically check order status using the status endpoint to track delivery progress.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Support Section -->
    <div class="bg-primary-50 border border-primary-200 rounded-xl p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Need Help?</h2>
        <p class="text-gray-600 mb-4">Have questions about the API or need technical support?</p>
        <div class="flex flex-col sm:flex-row gap-4">
            <a href="../account/" class="inline-block px-6 py-2 bg-primary-600 text-white rounded hover:bg-primary-700 transition-colors text-center">
                Account Settings
            </a>
            <a href="../tickets/" class="inline-block px-6 py-2 bg-gray-300 text-gray-900 rounded hover:bg-gray-400 transition-colors text-center">
                Contact Support
            </a>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text, button) {
    navigator.clipboard.writeText(text).then(() => {
        const originalText = button.textContent;
        button.textContent = 'Copied!';
        button.style.backgroundColor = '#3b82f6';
        
        setTimeout(() => {
            button.textContent = originalText;
            button.style.backgroundColor = '';
        }, 2000);
    }).catch(err => {
        console.error('Failed to copy:', err);
        alert('Failed to copy API key. Please copy manually.');
    });
}
</script>

<?php include '../include/layout-footer.php'; ?>
