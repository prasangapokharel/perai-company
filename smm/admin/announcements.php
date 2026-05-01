<?php
include '../config/dbconfig.php';
include '../include/functions.php';
include '../include/auth.php';

// Require admin
requireAdmin();

$pageTitle = 'Announcements';

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $delete_sql = "DELETE FROM announcements WHERE id = $id";
    if ($conn->query($delete_sql)) {
        $success_message = "Announcement deleted successfully!";
    } else {
        $error_message = "Error deleting announcement: " . $conn->error;
    }
}

// Get all announcements
$sql = "SELECT a.* 
        FROM announcements a 
        ORDER BY a.created_at DESC";
$result = $conn->query($sql);

include 'admin-header.php';
?>

<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Announcements</h1>
            <p class="text-slate-500 font-medium mt-1">Manage platform news and system updates.</p>
        </div>
        <a href="postevent.php" class="inline-flex items-center px-6 py-3 bg-primary-600 text-white text-sm font-bold rounded-xl hover:bg-primary-700 shadow-lg shadow-primary-200 transition-all gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
            New Announcement
        </a>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="bg-primary-50 border border-primary-100 text-primary-700 px-6 py-4 rounded-2xl text-sm font-bold">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="bg-rose-50 border border-rose-100 text-rose-700 px-6 py-4 rounded-2xl text-sm font-bold">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <!-- Announcements List -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">ID</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Title</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Status</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Author</th>
                        <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Created</th>
                        <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-8 py-5">
                                    <span class="text-xs font-black text-slate-400 uppercase tracking-widest">#<?php echo $row['id']; ?></span>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($row['title']); ?></div>
                                </td>
                                <td class="px-8 py-5">
                                    <?php if ($row['status'] == 'published' || $row['status'] == '1'): ?>
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border bg-primary-50 text-primary-700 border-primary-100">Published</span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border bg-amber-50 text-amber-700 border-amber-100">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center text-xs font-black uppercase tracking-widest">
                                            <?php echo strtoupper(substr($row['author'] ?? 'A', 0, 1)); ?>
                                        </div>
                                        <span class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($row['author'] ?? 'Admin'); ?></span>
                                    </div>
                                </td>
                                <td class="px-8 py-5 text-right text-[11px] font-bold text-slate-400">
                                    <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="flex justify-end gap-2">
                                        <a href="../announcements/view.php?slug=<?php echo $row['slug']; ?>" target="_blank" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:text-primary-600 hover:bg-primary-50 transition-all" title="View">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        </a>
                                        <a href="edit_announcement.php?id=<?php echo $row['id']; ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:text-primary-600 hover:bg-primary-50 transition-all" title="Edit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </a>
                                        <a href="?delete=<?php echo $row['id']; ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-all" title="Delete" onclick="return confirm('Are you sure?')">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-8 py-12 text-center text-slate-400 font-medium">No announcements found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'admin-footer.php'; ?>
