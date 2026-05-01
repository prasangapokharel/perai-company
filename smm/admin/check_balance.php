<?php
include '../config/dbconfig.php';
include '../include/functions.php';
include '../include/auth.php';

// Require admin
requireAdmin();

$pageTitle = 'Check API Balance';
$alertMessage = '';
$alertType = '';

// Process check balance request
if (isset($_POST['check_balance'])) {
    $providerId = intval($_POST['provider_id']);
    
    // Get provider details
    $provider = $conn->query("SELECT * FROM api_providers WHERE id = $providerId")->fetch_assoc();
    
    if (!$provider) {
        $alertMessage = 'Invalid API provider';
        $alertType = 'error';
    } else {
        // Create API instance
        $api = new Api($provider['url'], $provider['api_key']);
        
        // Get balance from API
        $response = $api->balance();
        
        if ($response && isset($response->balance)) {
            // Update provider balance in database
            $conn->query("UPDATE api_providers SET balance = {$response->balance} WHERE id = $providerId");
            
            $alertMessage = "Balance updated successfully. Current balance: " . formatCurrency($response->balance);
            $alertType = 'success';
        } else {
            $alertMessage = 'Failed to fetch balance from API: ' . ($response->error ?? 'Unknown error');
            $alertType = 'error';
        }
    }
}

include 'admin-header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">API Balance Check</h1>
    <a href="import.php" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Back to Import</a>
</div>

<?php if ($alertMessage): ?>
    <div class="mb-6 p-4 rounded <?php echo $alertType == 'success' ? 'bg-primary-100 text-primary-800' : 'bg-red-100 text-red-800'; ?>">
        <?php echo $alertMessage; ?>
    </div>
<?php endif; ?>

<div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
    <p class="mb-4">Balance check completed. You can now return to the import page or check another provider's balance.</p>
    
    <?php 
    // Get API providers
    $apiProviders = $conn->query("SELECT * FROM api_providers WHERE status = 1 ORDER BY name");
    ?>
    
    <?php if ($apiProviders && $apiProviders->num_rows > 0): ?>
        <form action="check_balance.php" method="post">
            <div class="mb-4">
                <label for="provider_id" class="block text-gray mb-2">Select API Provider</label>
                <select id="provider_id" name="provider_id" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:border-teal-500" required>
                    <?php while ($provider = $apiProviders->fetch_assoc()): ?>
                        <option value="<?php echo $provider['id']; ?>"><?php echo $provider['name']; ?> (Current Balance: <?php echo formatCurrency($provider['balance']); ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <button type="submit" name="check_balance" class="w-full bg-teal-500 text-white py-2 rounded hover:bg-teal-600">Check Balance</button>
        </form>
    <?php else: ?>
        <p class="text-gray text-center py-4">No active API providers found</p>
    <?php endif; ?>
</div>

<?php include 'admin-footer.php'; ?>