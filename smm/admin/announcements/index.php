<?php
include '../../config/dbconfig.php';
include '../../include/functions.php';
include '../../include/auth.php';

// Require admin
requireAdmin();

$pageTitle = 'Manage Announcements';

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
$sql = "SELECT a.*, u.username as author 
        FROM announcements a 
        LEFT JOIN users u ON a.created_by = u.id 
        ORDER BY a.created_at DESC";
$result = $conn->query($sql);

include '../../include/admin-layout-start.php';
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Announcements</h1>
        <p class="text-gray-500 text-sm mt-1">Manage platform news and system updates.</p>
    </div>
    <a href="create.php" class="inline-flex items-center px-6 py-3 bg-primary-500 text-white font-semibold rounded-xl hover:bg-primary-600 transition-none shadow-sm shadow-primary-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        New Announcement
    </a>
</div>

<?php if (isset($success_message)): ?>
    <div class="mb-6 p-4 rounded bg-primary-100 text-primary-800">
        <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="mb-6 p-4 rounded bg-red-100 text-red-800">
        <?php echo $error_message; ?>
    </div>
<?php endif; ?>

<div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm glass-card">
    <?php if ($result && $result->num_rows > 0): ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-gray-400 text-xs font-semibold uppercase tracking-wider border-b border-gray-50">
                        <th class="pb-4 font-medium">ID</th>
                        <th class="pb-4 font-medium">Title</th>
                        <th class="pb-4 font-medium">Status</th>
                        <th class="pb-4 font-medium">Author</th>
                        <th class="pb-4 font-medium">Created</th>
                        <th class="pb-4 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="hover:bg-primary-50/30 transition-none">
                            <td class="py-4 text-sm text-gray-500 font-mono">#<?php echo $row['id']; ?></td>
                            <td class="py-4 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['title']); ?></td>
                            <td class="py-4 text-sm">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $row['status'] == 'published' ? 'bg-primary-100 text-primary-700' : 'bg-gray-100 text-gray-600'; ?>">
                                    <?php echo ucfirst($row['status'] ?? 'Draft'); ?>
                                </span>
                            </td>
                            <td class="py-4 text-sm text-gray-600">
                                <div class="flex items-center">
                                    <div class="h-6 w-6 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center text-[10px] font-bold mr-2">
                                        <?php echo strtoupper(substr($row['author'] ?? 'A', 0, 1)); ?>
                                    </div>
                                    <?php echo htmlspecialchars($row['author'] ?? 'Admin'); ?>
                                </div>
                            </td>
                            <td class="py-4 text-sm text-gray-500"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td class="py-4 text-right">
                                <div class="flex justify-end space-x-1">
                                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="p-2 text-primary-600 hover:bg-primary-50 rounded-xl" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <a href="../../announcements/view.php?slug=<?php echo $row['slug']; ?>" class="p-2 text-primary-600 hover:bg-primary-50 rounded-xl" target="_blank" title="View">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <a href="?delete=<?php echo $row['id']; ?>" class="p-2 text-red-600 hover:bg-red-50 rounded-xl" onclick="return confirm('Are you sure you want to delete this?')" title="Delete">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-gray-500 text-center py-4">No announcements found</p>
    <?php endif; ?>
</div>

<?php include '../../include/admin-layout-end.php'; ?>
