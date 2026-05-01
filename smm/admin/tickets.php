<?php
include '../config/dbconfig.php';
include '../include/functions.php';
include '../include/auth.php';

// Require admin
requireAdmin();

$pageTitle = 'Support Tickets';

// Filter by status if provided
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$whereClause = "";
if ($statusFilter) {
    $whereClause = "WHERE t.status = '" . $conn->real_escape_string($statusFilter) . "'";
}

// Get tickets with user info
$query = "
    SELECT t.*, u.username 
    FROM support_tickets t 
    JOIN users u ON t.user_id = u.id 
    $whereClause
    ORDER BY t.updated_at DESC
";
$tickets = $conn->query($query);

include 'admin-header.php';
?>

<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Support Tickets</h1>
            <p class="text-slate-500 font-medium mt-1">Manage user inquiries and support requests.</p>
        </div>
        <div class="flex gap-2">
            <a href="?status=" class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest transition-all <?php echo !$statusFilter ? 'bg-primary-600 text-white shadow-lg shadow-primary-200' : 'bg-white text-slate-500 border border-slate-200 hover:border-primary-200'; ?>">All</a>
            <a href="?status=pending" class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest transition-all <?php echo $statusFilter === 'pending' ? 'bg-amber-500 text-white shadow-lg shadow-amber-200' : 'bg-white text-slate-500 border border-slate-200 hover:border-amber-200'; ?>">Pending</a>
            <a href="?status=replied" class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest transition-all <?php echo $statusFilter === 'replied' ? 'bg-primary-500 text-white shadow-lg shadow-blue-200' : 'bg-white text-slate-500 border border-slate-200 hover:border-primary-200'; ?>">Replied</a>
            <a href="?status=closed" class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest transition-all <?php echo $statusFilter === 'closed' ? 'bg-slate-800 text-white shadow-lg shadow-slate-200' : 'bg-white text-slate-500 border border-slate-200 hover:border-slate-300'; ?>">Closed</a>
        </div>
    </div>

    <!-- Tickets Table -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Ticket</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">User</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Subject</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Status</th>
                        <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Last Update</th>
                        <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if ($tickets && $tickets->num_rows > 0): ?>
                        <?php while ($ticket = $tickets->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-8 py-5">
                                    <span class="text-xs font-black text-slate-400 uppercase tracking-widest">#<?php echo $ticket['id']; ?></span>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($ticket['username']); ?></div>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($ticket['subject']); ?></div>
                                    <div class="text-[10px] text-slate-400 uppercase font-black tracking-tight mt-0.5"><?php echo $ticket['type']; ?> <?php echo $ticket['order_id'] ? '• Order #'.$ticket['order_id'] : ''; ?></div>
                                </td>
                                <td class="px-8 py-5">
                                    <?php 
                                    $statusClasses = [
                                        'pending' => 'bg-amber-50 text-amber-700 border-amber-100',
                                        'replied' => 'bg-primary-50 text-primary-700 border-primary-100',
                                        'closed' => 'bg-slate-100 text-slate-600 border-slate-200',
                                    ];
                                    $class = $statusClasses[$ticket['status']] ?? 'bg-slate-50 text-slate-700 border-slate-100';
                                    ?>
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border <?php echo $class; ?>">
                                        <?php echo $ticket['status']; ?>
                                    </span>
                                </td>
                                <td class="px-8 py-5 text-right text-[11px] font-bold text-slate-400">
                                    <?php echo date('M d, H:i', strtotime($ticket['updated_at'])); ?>
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <a href="ticket-view.php?id=<?php echo $ticket['id']; ?>" class="inline-flex items-center px-4 py-2 bg-slate-900 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-primary-600 transition-all shadow-sm">
                                        Open Ticket
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-8 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 mb-4">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                                    </div>
                                    <p class="text-slate-400 font-medium">No tickets found matching your criteria.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'admin-footer.php'; ?>
