<?php
include '../../config/dbconfig.php';
include '../../include/functions.php';
include '../../include/auth.php';

// Require admin
requireAdmin();

$pageTitle = 'Import Services';

// Get API providers
$apiProviders = $conn->query("SELECT * FROM api_providers ORDER BY name");

include '../../include/admin-layout-start.php';
?>

<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Import Services</h1>
            <p class="text-slate-500 font-medium mt-1">Connect with API providers and sync your service catalog.</p>
        </div>
    </div>

    <div id="alertContainer" class="space-y-4"></div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Add Provider Card -->
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="p-8 border-b border-slate-50">
                <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest">Add API Provider</h3>
            </div>
            <div class="p-8">
                <form id="addProviderForm" class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1" for="providerName">Provider Name</label>
                        <input type="text" id="providerName" class="block w-full px-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 transition-all" placeholder="e.g. FirstSMM" required>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1" for="providerUrl">API URL</label>
                        <input type="url" id="providerUrl" class="block w-full px-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 transition-all" placeholder="https://api.provider.com/v2" required>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1" for="providerKey">API Key</label>
                        <input type="text" id="providerKey" class="block w-full px-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 transition-all" placeholder="Your secret API key" required>
                    </div>
                    <button type="submit" class="w-full py-4 bg-primary-600 text-white text-xs font-black uppercase tracking-widest rounded-2xl hover:bg-primary-700 shadow-lg shadow-primary-100 transition-all active:scale-[0.98]">
                        Add Provider
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Import Services Card -->
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="p-8 border-b border-slate-50">
                <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest">Import & Sync</h3>
            </div>
            <div class="p-8">
                <form id="importServicesForm" class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1" for="importProvider">Select Provider</label>
                        <select id="importProvider" class="block w-full px-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-900 focus:outline-none focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 transition-all appearance-none" required>
                            <option value="">Choose provider...</option>
                            <?php 
                            $providers = $conn->query("SELECT * FROM api_providers WHERE status = 1 ORDER BY name");
                            while($provider = $providers->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $provider['id']; ?>">
                                    <?php echo htmlspecialchars($provider['name']); ?> 
                                    (Bal: $<?php echo number_format($provider['balance'], 2); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1" for="priceMarkup">Price Markup (%)</label>
                        <input type="number" id="priceMarkup" class="block w-full px-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 transition-all" value="30" min="0" step="0.1" required>
                        <p class="text-[10px] text-slate-400 font-bold uppercase px-1">Added to provider rates (e.g., 30% = 1.30x)</p>
                    </div>
                    <button type="submit" class="w-full py-4 bg-primary-600 text-white text-xs font-black uppercase tracking-widest rounded-2xl hover:bg-primary-700 shadow-lg shadow-primary-100 transition-all active:scale-[0.98]">
                        Sync Services Now
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Providers Table -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="p-8 border-b border-slate-50">
            <h2 class="text-sm font-black text-slate-900 uppercase tracking-widest">Connected Providers</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Provider</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">API Endpoint</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Balance</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Status</th>
                        <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php 
                    $allProviders = $conn->query("SELECT * FROM api_providers ORDER BY name");
                    if ($allProviders && $allProviders->num_rows > 0):
                        while($provider = $allProviders->fetch_assoc()): 
                    ?>
                        <tr class="hover:bg-slate-50/50 transition-colors" data-provider-id="<?php echo $provider['id']; ?>">
                            <td class="px-8 py-5">
                                <div class="text-sm font-black text-slate-900"><?php echo htmlspecialchars($provider['name']); ?></div>
                                <div class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">ID: #<?php echo $provider['id']; ?></div>
                            </td>
                            <td class="px-8 py-5">
                                <div class="text-xs font-bold text-slate-400 truncate max-w-[200px]"><?php echo htmlspecialchars($provider['url']); ?></div>
                            </td>
                            <td class="px-8 py-5">
                                <span class="balance-badge-modern inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary-50 text-primary-700 text-xs font-black rounded-xl border border-primary-100 balance-<?php echo $provider['id']; ?>">
                                    $<?php echo number_format($provider['balance'], 2); ?>
                                </span>
                            </td>
                            <td class="px-8 py-5">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border <?php echo $provider['status'] ? 'bg-primary-50 text-primary-700 border-primary-100' : 'bg-slate-50 text-slate-700 border-slate-100'; ?>">
                                    <?php echo $provider['status'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="px-8 py-5 text-right">
                                <div class="flex justify-end gap-2">
                                    <button onclick="syncProvider(<?php echo $provider['id']; ?>)" class="p-2 text-slate-400 hover:text-primary-600 transition-colors" title="Sync Services">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                    </button>
                                    <button onclick="checkBalance(<?php echo $provider['id']; ?>)" class="p-2 text-slate-400 hover:text-primary-600 transition-colors" title="Check Balance">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </button>
                                    <button onclick="deleteProvider(<?php echo $provider['id']; ?>)" class="p-2 text-slate-400 hover:text-rose-600 transition-colors" title="Delete Provider">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                        <tr>
                            <td colspan="5" class="px-8 py-12 text-center text-slate-400 font-bold uppercase text-xs tracking-widest italic">No providers connected.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="loadingOverlay" class="fixed inset-0 bg-slate-900/60 z-[100] hidden items-center justify-center p-6 backdrop-blur-sm transition-all">
    <div class="bg-white rounded-3xl p-10 max-w-sm w-full text-center shadow-2xl">
        <div class="w-16 h-16 border-4 border-slate-100 border-t-primary-500 rounded-full animate-spin mx-auto mb-6"></div>
        <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight mb-2" id="loadingTitle">Processing</h3>
        <p class="text-sm font-bold text-slate-400 leading-relaxed" id="loadingMessage">Connecting to provider API...</p>
    </div>
</div>

<script>
function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    const bg = type === 'success' ? 'bg-primary-50 border-primary-100 text-primary-700' : 'bg-rose-50 border-rose-100 text-rose-700';
    alertDiv.className = `p-4 rounded-2xl border ${bg} shadow-sm transition-all duration-300 opacity-0 translate-y-2 flex items-center gap-3`;
    
    const icon = type === 'success' 
        ? '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>'
        : '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>';
    
    alertDiv.innerHTML = icon + '<span class="text-xs font-black uppercase tracking-tight">' + message + '</span>';
    
    const container = document.getElementById('alertContainer');
    container.appendChild(alertDiv);
    
    requestAnimationFrame(() => {
        alertDiv.classList.remove('opacity-0', 'translate-y-2');
    });
    
    setTimeout(() => {
        alertDiv.classList.add('opacity-0', 'translate-y-[-8px]');
        setTimeout(() => alertDiv.remove(), 300);
    }, 5000);
}

function showLoading(title, message) {
    document.getElementById('loadingTitle').textContent = title;
    document.getElementById('loadingMessage').textContent = message;
    document.getElementById('loadingOverlay').classList.remove('hidden');
    document.getElementById('loadingOverlay').classList.add('flex');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.add('hidden');
    document.getElementById('loadingOverlay').classList.remove('flex');
}

// Add Provider
document.getElementById('addProviderForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const name = document.getElementById('providerName').value;
    const url = document.getElementById('providerUrl').value;
    const apiKey = document.getElementById('providerKey').value;
    
    showLoading('Adding Provider', 'Connecting to API provider...');
    
    try {
        const response = await fetch('api/add_provider.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ name, url, api_key: apiKey })
        });
        
        const data = await response.json();
        hideLoading();
        
        if (data.success) {
            showAlert('Provider added successfully!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.message || 'Failed to add provider', 'error');
        }
    } catch (error) {
        hideLoading();
        showAlert('Error: ' + error.message, 'error');
    }
});

// Import Services
document.getElementById('importServicesForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const providerId = document.getElementById('importProvider').value;
    const markup = document.getElementById('priceMarkup').value;
    
    if (!providerId) {
        showAlert('Please select a provider', 'error');
        return;
    }
    
    showLoading('Importing Services', 'Fetching services from API provider...');
    
    try {
        const response = await fetch('api/import_services.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ provider_id: providerId, markup: parseFloat(markup) })
        });
        
        const data = await response.json();
        hideLoading();
        
        if (data.success) {
            showAlert(`Successfully imported ${data.imported} services and updated ${data.updated} existing services!`, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showAlert(data.message || 'Failed to import services', 'error');
        }
    } catch (error) {
        hideLoading();
        showAlert('Error: ' + error.message, 'error');
    }
});

// Check Balance
async function checkBalance(providerId) {
    showLoading('Checking Balance', 'Fetching balance from API...');
    
    try {
        const response = await fetch('api/check_balance.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ provider_id: providerId })
        });
        
        const data = await response.json();
        hideLoading();
        
        if (data.success) {
            document.querySelector('.balance-' + providerId).textContent = '$' + parseFloat(data.balance).toFixed(2);
            showAlert('Balance updated: $' + parseFloat(data.balance).toFixed(2), 'success');
        } else {
            showAlert(data.message || 'Failed to check balance', 'error');
        }
    } catch (error) {
        hideLoading();
        showAlert('Error: ' + error.message, 'error');
    }
}

// Sync Provider
async function syncProvider(providerId) {
    if (!confirm('Sync services from this provider? This will update existing services and add new ones.')) {
        return;
    }
    
    const markup = prompt('Enter price markup percentage:', '30');
    if (markup === null) return;
    
    showLoading('Syncing Services', 'This may take a few moments...');
    
    try {
        const response = await fetch('api/import_services.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ provider_id: providerId, markup: parseFloat(markup) })
        });
        
        const data = await response.json();
        hideLoading();
        
        if (data.success) {
            showAlert(`Synced successfully! Imported: ${data.imported}, Updated: ${data.updated}`, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showAlert(data.message || 'Failed to sync services', 'error');
        }
    } catch (error) {
        hideLoading();
        showAlert('Error: ' + error.message, 'error');
    }
}

// Delete Provider
async function deleteProvider(providerId) {
    if (!confirm('Delete this provider? This will remove all associated services!')) {
        return;
    }
    
    showLoading('Deleting Provider', 'Removing provider and services...');
    
    try {
        const response = await fetch('api/delete_provider.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ provider_id: providerId })
        });
        
        const data = await response.json();
        hideLoading();
        
        if (data.success) {
            showAlert('Provider deleted successfully!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.message || 'Failed to delete provider', 'error');
        }
    } catch (error) {
        hideLoading();
        showAlert('Error: ' + error.message, 'error');
    }
}
</script>

<?php include '../../include/admin-layout-end.php'; ?>
