<?php
include '../../config/dbconfig.php';
include '../../include/functions.php';
include '../../include/auth.php';

// Require admin
requireAdmin();

$pageTitle = "Affiliate Payouts";
$alertMessage = '';
$alertType = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $payoutId = intval($_POST['payout_id']);
    $newStatus = $_POST['status'];
    $adminNote = $_POST['admin_note'];
    
    $sql = "UPDATE payout_requests SET status = ?, admin_note = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $newStatus, $adminNote, $payoutId);
    
    if ($stmt->execute()) {
        $alertMessage = "Payout status updated successfully!";
        $alertType = 'success';
        
        // If rejected, refund the amount to user
        if ($newStatus === 'rejected') {
            $sql = "SELECT user_id, amount FROM payout_requests WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $payoutId);
            $stmt->execute();
            $result = $stmt->get_result();
            $payout = $result->fetch_assoc();
            
            $sql = "UPDATE users SET balance = balance + ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("di", $payout['amount'], $payout['user_id']);
            $stmt->execute();
        }
    } else {
        $alertMessage = "Error updating payout status";
        $alertType = 'error';
    }
}

// Get filter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query
$sql = "SELECT pr.*, u.email, u.username FROM payout_requests pr 
        JOIN users u ON pr.user_id = u.id";

if ($statusFilter !== 'all') {
    $sql .= " WHERE pr.status = ?";
}

$sql .= " ORDER BY pr.created_at DESC";

$stmt = $conn->prepare($sql);
if ($statusFilter !== 'all') {
    $stmt->bind_param("s", $statusFilter);
}
$stmt->execute();
$payouts = $stmt->get_result();

// Get statistics
$stats = [
    'total' => 0,
    'pending' => 0,
    'completed' => 0,
    'rejected' => 0,
    'total_amount' => 0
];

$sql = "SELECT status, COUNT(*) as count, SUM(amount) as total FROM payout_requests GROUP BY status";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $stats[$row['status']] = $row['count'];
        $stats['total_amount'] += $row['total'];
    }
}
$stats['total'] = array_sum([$stats['pending'], $stats['completed'], $stats['rejected']]);

include '../../include/admin-layout-start.php';
?>

<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Affiliate Payouts</h1>
            <p class="text-slate-500 font-medium mt-1">Manage and process user affiliate withdrawal requests.</p>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($alertMessage): ?>
    <div class="<?php echo $alertType === 'success' ? 'bg-primary-50 text-primary-700 border-primary-100' : 'bg-rose-50 text-rose-700 border-rose-100'; ?> border px-6 py-4 rounded-2xl text-sm font-bold shadow-sm">
        <?php echo htmlspecialchars($alertMessage); ?>
    </div>
    <?php endif; ?>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm">
            <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Requests</p>
            <p class="text-3xl font-black text-slate-900 font-display"><?php echo $stats['total']; ?></p>
        </div>
        <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm">
            <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Pending</p>
            <p class="text-3xl font-black text-amber-500 font-display"><?php echo $stats['pending']; ?></p>
        </div>
        <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm">
            <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Completed</p>
            <p class="text-3xl font-black text-primary-600 font-display"><?php echo $stats['completed']; ?></p>
        </div>
        <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm">
            <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Paid</p>
            <p class="text-3xl font-black text-slate-900 font-display">$<?php echo number_format($stats['total_amount'], 2); ?></p>
        </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-2">
        <a href="?status=all" class="px-6 py-3 rounded-xl text-xs font-bold uppercase tracking-widest transition-all <?php echo $statusFilter === 'all' ? 'bg-primary-600 text-white shadow-lg shadow-primary-200' : 'bg-white text-slate-500 border border-slate-200 hover:border-primary-200'; ?>">All</a>
        <a href="?status=pending" class="px-6 py-3 rounded-xl text-xs font-bold uppercase tracking-widest transition-all <?php echo $statusFilter === 'pending' ? 'bg-amber-500 text-white shadow-lg shadow-amber-200' : 'bg-white text-slate-500 border border-slate-200 hover:border-amber-200'; ?>">Pending</a>
        <a href="?status=completed" class="px-6 py-3 rounded-xl text-xs font-bold uppercase tracking-widest transition-all <?php echo $statusFilter === 'completed' ? 'bg-primary-600 text-white shadow-lg shadow-primary-200' : 'bg-white text-slate-500 border border-slate-200 hover:border-primary-200'; ?>">Completed</a>
        <a href="?status=rejected" class="px-6 py-3 rounded-xl text-xs font-bold uppercase tracking-widest transition-all <?php echo $statusFilter === 'rejected' ? 'bg-rose-500 text-white shadow-lg shadow-rose-200' : 'bg-white text-slate-500 border border-slate-200 hover:border-rose-200'; ?>">Rejected</a>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">ID</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">User</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Amount</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Method</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Status</th>
                        <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Date</th>
                        <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if ($payouts->num_rows > 0): ?>
                        <?php while ($payout = $payouts->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-8 py-5 text-xs font-black text-slate-400 uppercase tracking-widest">#<?php echo $payout['id']; ?></td>
                            <td class="px-8 py-5">
                                <div class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($payout['username']); ?></div>
                                <div class="text-[10px] text-slate-400 font-bold uppercase tracking-tight"><?php echo htmlspecialchars($payout['email']); ?></div>
                            </td>
                            <td class="px-8 py-5 text-sm font-black text-slate-900">$<?php echo number_format($payout['amount'], 2); ?></td>
                            <td class="px-8 py-5 text-xs font-bold text-slate-500 uppercase tracking-tight"><?php echo str_replace('_', ' ', $payout['payment_method']); ?></td>
                            <td class="px-8 py-5">
                                <?php 
                                $statusClasses = [
                                    'pending' => 'bg-amber-50 text-amber-700 border-amber-100',
                                    'completed' => 'bg-primary-50 text-primary-700 border-primary-100',
                                    'rejected' => 'bg-rose-50 text-rose-700 border-rose-100',
                                    'processing' => 'bg-primary-50 text-primary-700 border-primary-100',
                                ];
                                $class = $statusClasses[$payout['status']] ?? 'bg-slate-50 text-slate-700 border-slate-100';
                                ?>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border <?php echo $class; ?>">
                                    <?php echo $payout['status']; ?>
                                </span>
                            </td>
                            <td class="px-8 py-5 text-right text-[11px] font-bold text-slate-400"><?php echo date('M d, Y', strtotime($payout['created_at'])); ?></td>
                            <td class="px-8 py-5 text-right">
                                <button onclick="openModal(<?php echo htmlspecialchars(json_encode($payout)); ?>)" class="inline-flex items-center px-4 py-2 bg-slate-900 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-primary-600 transition-all shadow-sm">Details</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-8 py-12 text-center text-slate-400 font-medium">No payout requests found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="payoutModal" class="hidden fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-[100] p-4">
    <div class="bg-white rounded-[2rem] max-w-lg w-full shadow-2xl overflow-hidden border border-slate-100">
        <div class="p-8 border-b border-slate-50 flex justify-between items-center">
            <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight">Payout Details</h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div class="p-8 space-y-6">
            <div id="modalContent"></div>

            <form method="POST" action="" class="space-y-6 pt-4 border-t border-slate-50">
                <input type="hidden" name="payout_id" id="modalPayoutId">
                <input type="hidden" name="update_status" value="1">
                
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Update Status</label>
                    <select name="status" id="modalStatus" class="w-full px-5 py-3 bg-slate-50 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-primary-500 appearance-none transition-all">
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Admin Note</label>
                    <textarea name="admin_note" id="modalNote" rows="3" class="w-full px-5 py-3 bg-slate-50 border-none rounded-xl text-sm font-medium focus:ring-2 focus:ring-primary-500 transition-all placeholder:text-slate-300" placeholder="e.g. Transaction ID or reason for rejection..."></textarea>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="flex-1 py-3 bg-primary-600 text-white text-xs font-black uppercase tracking-widest rounded-xl hover:bg-primary-700 transition-all shadow-lg shadow-primary-200">Save Changes</button>
                    <button type="button" onclick="closeModal()" class="flex-1 py-3 bg-white border border-slate-200 text-slate-500 text-xs font-black uppercase tracking-widest rounded-xl hover:bg-slate-50 transition-all">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openModal(payout) {
    const modal = document.getElementById('payoutModal');
    const content = document.getElementById('modalContent');
    const statusSelect = document.getElementById('modalStatus');
    const noteTextarea = document.getElementById('modalNote');
    const payoutIdInput = document.getElementById('modalPayoutId');
    
    payoutIdInput.value = payout.id;
    statusSelect.value = payout.status;
    noteTextarea.value = payout.admin_note || '';
    
    content.innerHTML = `
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Amount</p>
                <p class="text-2xl font-black text-primary-600 font-display">$${parseFloat(payout.amount).toFixed(2)}</p>
            </div>
            <div class="flex justify-between items-center">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Method</p>
                <p class="text-sm font-bold text-slate-900 uppercase">${payout.payment_method.replace(/_/g, ' ')}</p>
            </div>
            <div class="space-y-1">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Details</p>
                <p class="text-sm font-medium bg-slate-50 p-4 rounded-xl text-slate-700 leading-relaxed">${payout.payment_details}</p>
            </div>
        </div>
    `;
    
    modal.classList.remove('hidden');
}

function closeModal() {
    document.getElementById('payoutModal').classList.add('hidden');
}

document.getElementById('payoutModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php include '../../include/admin-layout-end.php'; ?>
