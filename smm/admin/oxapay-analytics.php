<?php
include '../config/dbconfig.php';
include '../config/oxapay.php';
include '../include/functions.php';
include '../include/auth.php';

// Require admin
requireAdmin();

$pageTitle = 'OxaPay Analytics';

// Get time period filter
$period = $_GET['period'] ?? 'month';
$validPeriods = ['today', 'week', 'month', 'year', 'all'];
if (!in_array($period, $validPeriods)) {
    $period = 'month';
}

// Calculate date range
switch ($period) {
    case 'today':
        $dateFrom = date('Y-m-d 00:00:00');
        $dateTo = date('Y-m-d 23:59:59');
        $label = 'Today';
        break;
    case 'week':
        $dateFrom = date('Y-m-d 00:00:00', strtotime('-7 days'));
        $dateTo = date('Y-m-d 23:59:59');
        $label = 'Last 7 Days';
        break;
    case 'month':
        $dateFrom = date('Y-m-01 00:00:00');
        $dateTo = date('Y-m-t 23:59:59');
        $label = 'This Month';
        break;
    case 'year':
        $dateFrom = date('Y-01-01 00:00:00');
        $dateTo = date('Y-12-31 23:59:59');
        $label = 'This Year';
        break;
    case 'all':
        $dateFrom = '2020-01-01 00:00:00';
        $dateTo = date('Y-m-d 23:59:59');
        $label = 'All Time';
        break;
}

// ============================================================================
// PAYMENT STATISTICS
// ============================================================================

// Total OxaPay deposits in period
$totalDeposits = $conn->query("
    SELECT COUNT(*) as count, SUM(amount) as total
    FROM transactions 
    WHERE payment_method = 'oxapay' 
    AND type = 'deposit' 
    AND status = 'completed'
    AND created_at >= '$dateFrom' 
    AND created_at <= '$dateTo'
")->fetch_assoc();

// Pending OxaPay deposits
$pendingDeposits = $conn->query("
    SELECT COUNT(*) as count, SUM(amount) as total
    FROM transactions 
    WHERE payment_method = 'oxapay' 
    AND type = 'deposit' 
    AND status = 'pending'
    AND created_at >= '$dateFrom' 
    AND created_at <= '$dateTo'
")->fetch_assoc();

// Failed OxaPay deposits
$failedDeposits = $conn->query("
    SELECT COUNT(*) as count, SUM(amount) as total
    FROM transactions 
    WHERE payment_method = 'oxapay' 
    AND type = 'deposit' 
    AND status = 'canceled'
    AND created_at >= '$dateFrom' 
    AND created_at <= '$dateTo'
")->fetch_assoc();

// Total transactions (all payment methods for comparison)
$totalAllDeposits = $conn->query("
    SELECT COUNT(*) as count, SUM(amount) as total
    FROM transactions 
    WHERE type = 'deposit' 
    AND status = 'completed'
    AND created_at >= '$dateFrom' 
    AND created_at <= '$dateTo'
")->fetch_assoc();

// Calculate success rate
$completedCount = (int)$totalDeposits['count'];
$pendingCount = (int)$pendingDeposits['count'];
$failedCount = (int)$failedDeposits['count'];
$totalCount = $completedCount + $pendingCount + $failedCount;
$successRate = $totalCount > 0 ? ($completedCount / $totalCount) * 100 : 0;
$failureRate = $totalCount > 0 ? ($failedCount / $totalCount) * 100 : 0;
$pendingRate = $totalCount > 0 ? ($pendingCount / $totalCount) * 100 : 0;

// ============================================================================
// PAYMENT METHOD BREAKDOWN
// ============================================================================

$paymentMethods = $conn->query("
    SELECT payment_method, COUNT(*) as count, SUM(amount) as total
    FROM transactions 
    WHERE type = 'deposit' 
    AND status = 'completed'
    AND created_at >= '$dateFrom' 
    AND created_at <= '$dateTo'
    GROUP BY payment_method
    ORDER BY total DESC
");

// ============================================================================
// DAILY REVENUE CHART DATA
// ============================================================================

if ($period === 'today') {
    // Hourly breakdown for today
    $revenueData = $conn->query("
        SELECT HOUR(created_at) as hour, COUNT(*) as transactions, SUM(amount) as total
        FROM transactions 
        WHERE payment_method = 'oxapay' 
        AND type = 'deposit' 
        AND status = 'completed'
        AND DATE(created_at) = CURDATE()
        GROUP BY HOUR(created_at)
        ORDER BY hour ASC
    ");
    $chartLabel = 'Hourly Revenue';
    $chartUnit = 'Hour';
} else {
    // Daily breakdown for other periods
    $revenueData = $conn->query("
        SELECT DATE(created_at) as date, COUNT(*) as transactions, SUM(amount) as total
        FROM transactions 
        WHERE payment_method = 'oxapay' 
        AND type = 'deposit' 
        AND status = 'completed'
        AND created_at >= '$dateFrom' 
        AND created_at <= '$dateTo'
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $chartLabel = 'Daily Revenue';
    $chartUnit = 'Day';
}

// Format data for chart
$chartDates = [];
$chartRevenue = [];
$chartTransactions = [];
while ($row = $revenueData->fetch_assoc()) {
    if ($period === 'today') {
        $chartDates[] = str_pad($row['hour'], 2, '0', STR_PAD_LEFT) . ':00';
    } else {
        $chartDates[] = date('M d', strtotime($row['date']));
    }
    $chartRevenue[] = (float)$row['total'] ?? 0;
    $chartTransactions[] = (int)$row['transactions'];
}

// ============================================================================
// RECENT TRANSACTIONS
// ============================================================================

$recentTransactions = $conn->query("
    SELECT t.*, u.username, u.email
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    WHERE t.payment_method = 'oxapay' 
    AND t.type = 'deposit'
    AND t.created_at >= '$dateFrom' 
    AND t.created_at <= '$dateTo'
    ORDER BY t.created_at DESC 
    LIMIT 20
");

// ============================================================================
// TOP USERS BY DEPOSITS
// ============================================================================

$topUsers = $conn->query("
    SELECT u.id, u.username, u.email, COUNT(t.id) as transactions, SUM(t.amount) as total
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    WHERE t.payment_method = 'oxapay' 
    AND t.type = 'deposit'
    AND t.status = 'completed'
    AND t.created_at >= '$dateFrom' 
    AND t.created_at <= '$dateTo'
    GROUP BY t.user_id
    ORDER BY total DESC
    LIMIT 10
");

// ============================================================================
// PERCENTAGE COMPARISON
// ============================================================================

// OxaPay percentage of total deposits
$oxapayPercentage = ($totalAllDeposits['total'] > 0) 
    ? (($totalDeposits['total'] ?? 0) / ($totalAllDeposits['total'] ?? 1)) * 100 
    : 0;

include 'admin-header.php';
?>

<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">OxaPay Analytics</h1>
            <p class="text-slate-500 font-medium mt-1">Cryptocurrency payment performance and statistics.</p>
        </div>
    </div>

    <!-- Period Selector -->
    <div class="flex flex-wrap gap-2">
        <?php foreach ($validPeriods as $p): ?>
            <a href="?period=<?php echo $p; ?>" class="px-4 py-2 rounded-xl font-semibold text-sm transition-all <?php echo $period === $p ? 'bg-primary-600 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:border-primary-300'; ?>">
                <?php echo ucfirst($p); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Completed Deposits -->
        <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm">
            <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Completed Deposits</p>
            <p class="text-3xl font-black text-primary-600"><?php echo formatCurrency($totalDeposits['total'] ?? 0); ?></p>
            <div class="mt-4 flex items-center text-xs font-bold text-primary-600 bg-primary-50 w-fit px-2 py-1 rounded-xl">
                <?php echo $completedCount; ?> transactions
            </div>
        </div>

        <!-- Pending Deposits -->
        <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm">
            <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Pending Deposits</p>
            <p class="text-3xl font-black text-amber-600"><?php echo formatCurrency($pendingDeposits['total'] ?? 0); ?></p>
            <div class="mt-4 flex items-center text-xs font-bold text-amber-600 bg-amber-50 w-fit px-2 py-1 rounded-xl">
                <?php echo $pendingCount; ?> transactions
            </div>
        </div>

        <!-- Success Rate -->
        <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm">
            <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Success Rate</p>
            <p class="text-3xl font-black text-slate-900"><?php echo number_format($successRate, 1); ?>%</p>
            <div class="mt-4 w-full bg-slate-100 rounded-full h-2">
                <div class="bg-primary-500 h-2 rounded-full" style="width: <?php echo $successRate; ?>%;"></div>
            </div>
        </div>

        <!-- Failed Deposits -->
        <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm">
            <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Failed Deposits</p>
            <p class="text-3xl font-black text-red-600"><?php echo formatCurrency($failedDeposits['total'] ?? 0); ?></p>
            <div class="mt-4 flex items-center text-xs font-bold text-red-600 bg-red-50 w-fit px-2 py-1 rounded-xl">
                <?php echo $failedCount; ?> transactions
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Revenue Chart -->
        <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900 mb-6"><?php echo $chartLabel; ?> - <?php echo $label; ?></h3>
            <div style="height: 300px; position: relative;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Status Distribution -->
        <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900 mb-6">Payment Status Distribution</h3>
            <div style="height: 300px; position: relative;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Payment Methods Breakdown -->
    <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm">
        <h3 class="text-lg font-bold text-slate-900 mb-6">Payment Methods Breakdown</h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-slate-200">
                    <tr>
                        <th class="text-left py-3 px-4 font-bold text-slate-900 text-sm">Payment Method</th>
                        <th class="text-right py-3 px-4 font-bold text-slate-900 text-sm">Transactions</th>
                        <th class="text-right py-3 px-4 font-bold text-slate-900 text-sm">Total Amount</th>
                        <th class="text-right py-3 px-4 font-bold text-slate-900 text-sm">Percentage</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php while ($row = $paymentMethods->fetch_assoc()): ?>
                        <?php $percentage = ($totalAllDeposits['total'] > 0) ? (($row['total'] / $totalAllDeposits['total']) * 100) : 0; ?>
                        <tr class="hover:bg-slate-50">
                            <td class="py-4 px-4">
                                <span class="font-bold text-slate-900"><?php echo htmlspecialchars($row['payment_method'] ?: 'Unknown'); ?></span>
                            </td>
                            <td class="py-4 px-4 text-right text-slate-700"><?php echo $row['count']; ?></td>
                            <td class="py-4 px-4 text-right font-bold text-primary-600"><?php echo formatCurrency($row['total']); ?></td>
                            <td class="py-4 px-4 text-right text-slate-700"><?php echo number_format($percentage, 1); ?>%</td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Users -->
    <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm">
        <h3 class="text-lg font-bold text-slate-900 mb-6">Top Users - <?php echo $label; ?></h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-slate-200">
                    <tr>
                        <th class="text-left py-3 px-4 font-bold text-slate-900 text-sm">User</th>
                        <th class="text-right py-3 px-4 font-bold text-slate-900 text-sm">Transactions</th>
                        <th class="text-right py-3 px-4 font-bold text-slate-900 text-sm">Total Deposited</th>
                        <th class="text-right py-3 px-4 font-bold text-slate-900 text-sm">Avg per Transaction</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php while ($row = $topUsers->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="py-4 px-4">
                                <div>
                                    <p class="font-bold text-slate-900"><?php echo htmlspecialchars($row['username']); ?></p>
                                    <p class="text-xs text-slate-500"><?php echo htmlspecialchars($row['email']); ?></p>
                                </div>
                            </td>
                            <td class="py-4 px-4 text-right text-slate-700"><?php echo $row['transactions']; ?></td>
                            <td class="py-4 px-4 text-right font-bold text-primary-600"><?php echo formatCurrency($row['total']); ?></td>
                            <td class="py-4 px-4 text-right text-slate-700"><?php echo formatCurrency($row['total'] / $row['transactions']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm">
        <h3 class="text-lg font-bold text-slate-900 mb-6">Recent Transactions - <?php echo $label; ?></h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-slate-200">
                    <tr>
                        <th class="text-left py-3 px-4 font-bold text-slate-900 text-sm">User</th>
                        <th class="text-left py-3 px-4 font-bold text-slate-900 text-sm">Amount</th>
                        <th class="text-left py-3 px-4 font-bold text-slate-900 text-sm">Status</th>
                        <th class="text-left py-3 px-4 font-bold text-slate-900 text-sm">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php while ($row = $recentTransactions->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="py-4 px-4">
                                <div>
                                    <p class="font-bold text-slate-900"><?php echo htmlspecialchars($row['username']); ?></p>
                                    <p class="text-xs text-slate-500"><?php echo htmlspecialchars($row['email']); ?></p>
                                </div>
                            </td>
                            <td class="py-4 px-4 font-bold text-primary-600"><?php echo formatCurrency($row['amount']); ?></td>
                            <td class="py-4 px-4">
                                <?php 
                                $statusColors = [
                                    'completed' => 'bg-primary-100 text-primary-800',
                                    'pending' => 'bg-amber-100 text-amber-800',
                                    'canceled' => 'bg-red-100 text-red-800',
                                ];
                                $statusClass = $statusColors[$row['status']] ?? 'bg-slate-100 text-slate-800';
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-bold <?php echo $statusClass; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td class="py-4 px-4 text-slate-700 text-sm"><?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
// Revenue Chart
const revenueCtx = document.getElementById('revenueChart');
if (revenueCtx) {
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chartDates); ?>,
            datasets: [
                {
                    label: 'Revenue',
                    data: <?php echo json_encode($chartRevenue); ?>,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                },
                {
                    label: 'Transactions',
                    data: <?php echo json_encode($chartTransactions); ?>,
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4,
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Revenue (USD)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Transactions'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });
}

// Status Distribution Chart
const statusCtx = document.getElementById('statusChart');
if (statusCtx) {
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'Pending', 'Canceled'],
            datasets: [{
                data: [
                    <?php echo $completedCount; ?>,
                    <?php echo $pendingCount; ?>,
                    <?php echo $failedCount; ?>
                ],
                backgroundColor: [
                    '#10b981',
                    '#f59e0b',
                    '#ef4444'
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
}
</script>

<?php include 'admin-footer.php'; ?>
