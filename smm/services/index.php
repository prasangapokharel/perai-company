<?php
include '../config/dbconfig.php';
include '../include/functions.php';

// Set page title
$pageTitle = 'Services';

// Check if user is logged in
$isLoggedIn = false;
if (isset($_SESSION['user_id'])) {
    $isLoggedIn = true;
}

// Get categories using flexible function
$categories = getAllCategories();

// Add "All" as the first category
array_unshift($categories, ['id' => 'All', 'name' => 'All']);

// Get selected category from URL or default to "All"
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : 'All';

// Get services based on selected category
$services = getServicesByCategory($selectedCategory === 'All' ? null : $selectedCategory);


include '../include/user-layout-start.php';
?>

<div class="space-y-6 md:space-y-8 px-0 sm:px-0">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 md:gap-6">
        <div class="w-full sm:w-auto">
            <h1 class="text-3xl md:text-4xl font-display text-gray-900 tracking-tight uppercase">Services</h1>
            <p class="text-xs md:text-sm text-gray-500 mt-1 uppercase tracking-widest font-bold">Browse our comprehensive list of social media services.</p>
        </div>
        <div class="w-full sm:w-auto">
            <?php if ($isLoggedIn): ?>
                <a href="../order/" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 border border-transparent text-sm font-bold rounded-xl text-white bg-primary-600 hover:bg-primary-700 shadow-md transition-all duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    New Order
                </a>
            <?php else: ?>
                <a href="../login/" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 border border-transparent text-sm font-bold rounded-xl text-white bg-primary-600 hover:bg-primary-700 shadow-md transition-all duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    Login to Order
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Search and Filter -->
    <div class="flex flex-col sm:flex-row gap-3 md:gap-4">
        <div class="relative flex-grow">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input type="text" id="search-input" placeholder="Search services..." class="block w-full pl-11 pr-4 py-3 md:py-4 bg-white border border-slate-200 rounded-2xl text-sm font-medium text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 transition-all duration-200 shadow-sm">
        </div>
        <button id="refresh-services" class="inline-flex items-center justify-center px-6 py-3 md:py-4 bg-white border border-slate-200 text-sm font-bold rounded-2xl text-slate-600 hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-4 focus:ring-slate-500/10 transition-all duration-200 shadow-sm">
            Reset
        </button>
    </div>
    
    <!-- Category Pills -->
    <div class="pb-2 overflow-x-auto scrollbar-hide -mx-4 sm:mx-0 px-4 sm:px-0">
        <nav class="flex space-x-2 md:space-x-3 whitespace-nowrap" aria-label="Tabs">
            <?php foreach ($categories as $category): 
                $isActive = $selectedCategory === $category['id'];
            ?>
                <a href="?category=<?php echo urlencode($category['id']); ?>" 
                   class="<?php echo $isActive ? 'bg-primary-600 text-white shadow-md shadow-primary-200' : 'bg-white text-slate-500 hover:text-slate-900 border border-slate-200 hover:border-slate-300'; ?> px-4 md:px-5 py-2 md:py-2.5 font-bold text-[11px] md:text-xs rounded-xl transition-all duration-200">
                    <?php echo htmlspecialchars($category['name']); ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
    
    <!-- Services Table - Responsive -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <!-- Desktop Table View -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full divide-y divide-slate-100" id="services-table">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-[11px] font-black text-slate-400 uppercase tracking-[0.2em]">ID</th>
                        <th scope="col" class="px-6 py-4 text-left text-[11px] font-black text-slate-400 uppercase tracking-[0.2em]">Service</th>
                        <th scope="col" class="px-6 py-4 text-left text-[11px] font-black text-slate-400 uppercase tracking-[0.2em]">Min/Max</th>
                        <th scope="col" class="px-6 py-4 text-left text-[11px] font-black text-slate-400 uppercase tracking-[0.2em]">Rate (1k)</th>
                        <th scope="col" class="px-6 py-4 text-right text-[11px] font-black text-slate-400 uppercase tracking-[0.2em]">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-50">
                    <?php if (count($services) > 0): ?>
                        <?php foreach ($services as $service): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                <td class="px-6 py-5 whitespace-nowrap text-xs font-bold text-slate-400">
                                    #<?php echo $service['id']; ?>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center mr-4 group-hover:bg-primary-100 transition-colors">
                                            <?php echo getServiceIconHtml($service['name']); ?>
                                        </div>
                                        <div>
                                            <a href="/services/<?php echo $service['slug'] ?: $service['id']; ?>" class="text-sm font-bold text-slate-900 hover:text-primary-600 transition-colors service-name"><?php echo htmlspecialchars($service['name']); ?></a>
                                            <div class="text-xs text-slate-500 mt-1 max-w-md line-clamp-2 leading-relaxed"><?php echo htmlspecialchars($service['description'] ?: 'No description available'); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap">
                                    <div class="text-sm text-slate-900 font-bold"><?php echo number_format($service['min_quantity']); ?></div>
                                    <div class="text-[10px] font-black text-slate-400 uppercase mt-0.5 tracking-wider"><?php echo number_format($service['max_quantity']); ?> Max</div>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap">
                                    <div class="text-sm font-black text-primary-600">
                                        <?php echo formatCurrency($service['price']); ?>
                                    </div>
                                </td>
                                 <td class="px-6 py-5 whitespace-nowrap text-right">
                                    <?php if ($isLoggedIn): ?>
                                        <a href="../order/?service=<?php echo $service['id']; ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-xs font-bold rounded-xl hover:bg-primary-700 transition-all duration-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                            Order
                                        </a>
                                    <?php else: ?>
                                        <a href="../login/" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 text-slate-600 text-xs font-bold rounded-xl hover:bg-slate-200 transition-all duration-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                            </svg>
                                            Login
                                        </a>
                                    <?php endif; ?>
                                 </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <p class="text-sm font-medium">No services found.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile View (Card Style) -->
        <div class="md:hidden divide-y divide-slate-100">
            <?php if (count($services) > 0): ?>
                <?php foreach ($services as $service): ?>
                    <div class="p-4 hover:bg-slate-50/50 transition-colors group">
                        <div class="flex items-start gap-4 mb-3">
                            <div class="flex-shrink-0 h-10 w-10 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center group-hover:bg-primary-100 transition-colors">
                                <?php echo getServiceIconHtml($service['name']); ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-0.5">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">#<?php echo $service['id']; ?></span>
                                    <span class="text-sm font-black text-primary-600"><?php echo formatCurrency($service['price']); ?></span>
                                </div>
                                <a href="/services/<?php echo $service['slug'] ?: $service['id']; ?>" class="text-sm font-bold text-slate-900 block truncate service-name"><?php echo htmlspecialchars($service['name']); ?></a>
                            </div>
                        </div>
                        <div class="text-xs text-slate-500 mb-4 line-clamp-2"><?php echo htmlspecialchars($service['description'] ?: 'No description available'); ?></div>
                         <div class="flex items-center justify-between">
                            <div class="flex gap-4">
                                <div>
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Min</p>
                                    <p class="text-xs font-bold text-slate-700"><?php echo number_format($service['min_quantity']); ?></p>
                                </div>
                                <div>
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Max</p>
                                    <p class="text-xs font-bold text-slate-700"><?php echo number_format($service['max_quantity']); ?></p>
                                </div>
                            </div>
                            <?php if ($isLoggedIn): ?>
                                <a href="../order/?service=<?php echo $service['id']; ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-xs font-bold rounded-xl hover:bg-primary-700 shadow-sm transition-all duration-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Order
                                </a>
                            <?php else: ?>
                                <a href="../login/" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 text-slate-600 text-xs font-bold rounded-xl hover:bg-slate-200 transition-all duration-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                    </svg>
                                    Login
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-8 text-center text-gray-500">
                    <p class="text-sm font-medium">No services found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search-input');
        const refreshButton = document.getElementById('refresh-services');
        const servicesTable = document.getElementById('services-table');
        
        if (searchInput && servicesTable) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = servicesTable.querySelectorAll('tbody tr');
                let hasResults = false;
                
                rows.forEach(row => {
                    const nameEl = row.querySelector('.service-name');
                    if (!nameEl) return;
                    
                    const serviceName = nameEl.textContent.toLowerCase();
                    const descEl = row.querySelector('.text-xs');
                    const serviceDescription = descEl ? descEl.textContent.toLowerCase() : '';
                    
                    if (serviceName.includes(searchTerm) || serviceDescription.includes(searchTerm)) {
                        row.style.display = '';
                        hasResults = true;
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
        
        if (refreshButton) {
            refreshButton.addEventListener('click', function() {
                window.location.href = 'index.php';
            });
        }
    });
</script>

<?php include '../include/user-layout-end.php'; ?>

