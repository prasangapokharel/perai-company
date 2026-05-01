<?php
include '../config/dbconfig.php';
include '../include/functions.php';
include '../include/auth.php';

// Require admin
requireAdmin();

$ticketId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'reply' && !empty($_POST['message'])) {
        $message = $conn->real_escape_string($_POST['message']);
        $stmt_msg = $conn->prepare("INSERT INTO ticket_messages (ticket_id, user_id, message, is_admin) VALUES (?, ?, ?, 1)");
        $stmt_msg->bind_param("iis", $ticketId, $_SESSION['user_id'], $message);
        
        if ($stmt_msg->execute()) {
            $conn->query("UPDATE support_tickets SET status = 'processing', updated_at = CURRENT_TIMESTAMP WHERE id = $ticketId");
            header("Location: ticket-view.php?id=$ticketId");
            exit;
        }
    } elseif ($_POST['action'] === 'close') {
        $conn->query("UPDATE support_tickets SET status = 'closed', updated_at = CURRENT_TIMESTAMP WHERE id = $ticketId");
        header("Location: ticket-view.php?id=$ticketId");
        exit;
    } elseif ($_POST['action'] === 'solved') {
        $conn->query("UPDATE support_tickets SET status = 'solved', updated_at = CURRENT_TIMESTAMP WHERE id = $ticketId");
        header("Location: ticket-view.php?id=$ticketId");
        exit;
    }
}

// Fetch ticket
$stmt = $conn->prepare("
    SELECT t.*, u.username, u.email 
    FROM support_tickets t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.id = ?
");
$stmt->bind_param("i", $ticketId);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();

if (!$ticket) {
    header("Location: tickets.php");
    exit;
}

$messages = $conn->query("
    SELECT m.*, u.username 
    FROM ticket_messages m 
    JOIN users u ON m.user_id = u.id 
    WHERE m.ticket_id = $ticketId 
    ORDER BY m.created_at ASC
");

$pageTitle = 'Ticket #' . $ticketId;
include 'admin-header.php';
?>

<div class="max-w-4xl mx-auto space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex items-center gap-4">
            <a href="tickets.php" class="p-2 bg-white border border-slate-100 rounded-xl text-slate-400 hover:text-primary-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight uppercase">Ticket #<?php echo $ticketId; ?></h1>
                <p class="text-xs font-black text-slate-400 uppercase tracking-widest"><?php echo htmlspecialchars($ticket['subject']); ?></p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-3 py-1 bg-white border border-slate-100 rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-500">
                User: <?php echo htmlspecialchars($ticket['username']); ?>
            </span>
            <?php 
            $statusColors = [
                'pending' => 'bg-amber-50 text-amber-600 border-amber-100',
                'replied' => 'bg-primary-50 text-primary-600 border-primary-100',
                'closed' => 'bg-slate-100 text-slate-500 border-slate-200',
            ];
            $statusClass = $statusColors[$ticket['status']] ?? 'bg-slate-50 text-slate-500';
            ?>
            <span class="px-3 py-1 rounded-xl text-[10px] font-black uppercase tracking-widest border <?php echo $statusClass; ?>">
                <?php echo $ticket['status']; ?>
            </span>
        </div>
    </div>

    <!-- Message Feed -->
    <div class="space-y-6">
        <?php if ($messages && $messages->num_rows > 0): ?>
            <?php while ($msg = $messages->fetch_assoc()): ?>
                <div class="flex <?php echo $msg['is_admin'] ? 'justify-end' : 'justify-start'; ?>">
                    <div class="max-w-[80%] <?php echo $msg['is_admin'] ? 'bg-slate-900 text-white' : 'bg-white text-slate-900 border border-slate-100 shadow-sm'; ?> rounded-2xl p-6 relative">
                        <div class="flex items-center justify-between mb-2 gap-8">
                            <span class="text-[10px] font-black uppercase tracking-widest <?php echo $msg['is_admin'] ? 'text-primary-400' : 'text-slate-400'; ?>">
                                <?php echo $msg['is_admin'] ? 'Admin Support' : htmlspecialchars($msg['username']); ?>
                            </span>
                            <span class="text-[9px] font-bold uppercase tracking-tight <?php echo $msg['is_admin'] ? 'text-slate-500' : 'text-slate-300'; ?>">
                                <?php echo date('M d, H:i', strtotime($msg['created_at'])); ?>
                            </span>
                        </div>
                        <div class="text-sm font-medium leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($msg['message']); ?></div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <!-- Quick Action / Reply -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden mt-8">
        <?php if ($ticket['status'] !== 'closed'): ?>
            <form method="POST" class="p-8">
                <input type="hidden" name="action" value="reply">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Post a Reply</p>
                <textarea name="message" rows="4" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl text-sm font-medium focus:ring-2 focus:ring-primary-500 transition-all placeholder:text-slate-300 mb-6" placeholder="Type your response to the user..." required></textarea>
                
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="flex gap-2">
                        <button type="submit" class="px-6 py-3 bg-primary-600 text-white text-xs font-black uppercase tracking-widest rounded-xl hover:bg-primary-700 transition-all shadow-lg shadow-primary-200">
                            Send Response
                        </button>
                        <button type="submit" name="action" value="close" onclick="return confirm('Are you sure you want to close this ticket?')" class="px-6 py-3 bg-white border border-slate-200 text-slate-500 text-xs font-black uppercase tracking-widest rounded-xl hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all">
                            Close Ticket
                        </button>
                    </div>
                    <?php if ($ticket['order_id']): ?>
                        <a href="view_order.php?id=<?php echo $ticket['order_id']; ?>" class="text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-primary-600 transition-colors">
                            View Referenced Order #<?php echo $ticket['order_id']; ?> →
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        <?php else: ?>
            <div class="p-12 text-center">
                <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 01-2 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                </div>
                <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight">Ticket Closed</h3>
                <p class="text-slate-500 font-medium text-sm mt-1 mb-6">This ticket has been marked as resolved and is currently read-only.</p>
                <form method="POST">
                    <button type="submit" name="action" value="reopen" class="px-6 py-3 bg-slate-900 text-white text-xs font-black uppercase tracking-widest rounded-xl hover:bg-primary-600 transition-all shadow-lg shadow-primary-200">
                        Re-open Ticket
                    </button>
                    <input type="hidden" name="action" value="reply"> <!-- dummy for logic or I should handle reopen separately -->
                </form>
            </div>
            <?php
            // Add reopen logic
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reopen') {
                $conn->query("UPDATE support_tickets SET status = 'pending', updated_at = CURRENT_TIMESTAMP WHERE id = $ticketId");
                echo "<script>window.location.href='ticket-view.php?id=$ticketId';</script>";
                exit;
            }
            ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'admin-footer.php'; ?>
