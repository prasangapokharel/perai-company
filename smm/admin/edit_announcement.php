<?php
include '../config/dbconfig.php';
include '../include/functions.php';
include '../include/auth.php';

// Require admin
requireAdmin();

$pageTitle = 'Edit Announcement';
$success_message = '';
$error_message = '';

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: announcements.php");
    exit();
}

$id = (int)$_GET['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $_POST['description']; // Not escaping to allow HTML
    $feature_image_url = $conn->real_escape_string($_POST['feature_image_url']);
    $meta_description = $conn->real_escape_string($_POST['meta_description']);
    $keywords = $conn->real_escape_string($_POST['keywords']);
    $status = $conn->real_escape_string($_POST['status']);
    
    // Generate new slug only if title has changed
    $check_title = $conn->query("SELECT title FROM announcements WHERE id = $id");
    $current_title = $check_title->fetch_assoc()['title'];
    
    if ($current_title != $title) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        
        // Check if slug already exists
        $check_slug = $conn->query("SELECT id FROM announcements WHERE slug = '$slug' AND id != $id");
        if ($check_slug && $check_slug->num_rows > 0) {
            $slug .= '-' . substr(md5(time()), 0, 5);
        }
        
        $update_sql = "UPDATE announcements SET 
                      title = '$title', 
                      slug = '$slug', 
                      description = '$description', 
                      feature_image_url = '$feature_image_url', 
                      meta_description = '$meta_description', 
                      keywords = '$keywords', 
                      status = '$status' 
                      WHERE id = $id";
    } else {
        $update_sql = "UPDATE announcements SET 
                      title = '$title', 
                      description = '$description', 
                      feature_image_url = '$feature_image_url', 
                      meta_description = '$meta_description', 
                      keywords = '$keywords', 
                      status = '$status' 
                      WHERE id = $id";
    }
    
    if ($conn->query($update_sql)) {
        $success_message = "Announcement updated successfully!";
    } else {
        $error_message = "Error updating announcement: " . $conn->error;
    }
}

// Get announcement data
$sql = "SELECT * FROM announcements WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header("Location: announcements.php");
    exit();
}

$announcement = $result->fetch_assoc();

include 'admin-header.php';
?>

<div class="max-w-4xl mx-auto space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex items-center gap-4">
            <a href="announcements.php" class="p-2 bg-white border border-slate-100 rounded-xl text-slate-400 hover:text-primary-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <div>
                <h1 class="text-3xl font-black text-slate-900 tracking-tight">Edit Announcement</h1>
                <p class="text-slate-500 font-medium mt-1">Update platform news and system updates.</p>
            </div>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="bg-primary-50 border border-primary-100 text-primary-700 px-6 py-4 rounded-2xl text-sm font-bold">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="bg-rose-50 border border-rose-100 text-rose-700 px-6 py-4 rounded-2xl text-sm font-bold">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-8">
        <form method="post" action="" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label for="title" class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Announcement Title</label>
                    <input type="text" class="w-full px-5 py-3 bg-slate-50 border-none rounded-xl text-sm font-medium focus:ring-2 focus:ring-primary-500 transition-all placeholder:text-slate-300" id="title" name="title" value="<?php echo htmlspecialchars($announcement['title']); ?>" required>
                </div>
                
                <div class="md:col-span-2">
                    <label for="description" class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Main Content</label>
                    <textarea id="description" name="description" rows="12" class="w-full px-5 py-3 bg-slate-50 border-none rounded-xl text-sm font-medium focus:ring-2 focus:ring-primary-500 transition-all" required><?php echo htmlspecialchars($announcement['description']); ?></textarea>
                </div>
                
                <div>
                    <label for="feature_image_url" class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Feature Image URL</label>
                    <input type="url" class="w-full px-5 py-3 bg-slate-50 border-none rounded-xl text-sm font-medium focus:ring-2 focus:ring-primary-500 transition-all placeholder:text-slate-300" id="feature_image_url" name="feature_image_url" value="<?php echo htmlspecialchars($announcement['feature_image_url']); ?>">
                </div>

                <div>
                    <label for="status" class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Post Status</label>
                    <select class="w-full px-5 py-3 bg-slate-50 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-primary-500 transition-all appearance-none" id="status" name="status">
                        <option value="published" <?php echo $announcement['status'] == 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="draft" <?php echo $announcement['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="meta_description" class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Meta Description (SEO)</label>
                    <textarea class="w-full px-5 py-3 bg-slate-50 border-none rounded-xl text-sm font-medium focus:ring-2 focus:ring-primary-500 transition-all placeholder:text-slate-300" id="meta_description" name="meta_description" rows="2"><?php echo htmlspecialchars($announcement['meta_description']); ?></textarea>
                </div>

                <div class="md:col-span-2">
                    <label for="keywords" class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">SEO Keywords</label>
                    <input type="text" class="w-full px-5 py-3 bg-slate-50 border-none rounded-xl text-sm font-medium focus:ring-2 focus:ring-primary-500 transition-all placeholder:text-slate-300" id="keywords" name="keywords" value="<?php echo htmlspecialchars($announcement['keywords']); ?>">
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-4 pt-4">
                <button type="submit" class="px-6 py-3 bg-primary-600 text-white text-xs font-black uppercase tracking-widest rounded-xl hover:bg-primary-700 transition-all shadow-lg shadow-primary-200">
                    Update Announcement
                </button>
                <a href="announcements.php" class="px-6 py-3 bg-white border border-slate-200 text-slate-500 text-xs font-black uppercase tracking-widest rounded-xl hover:bg-slate-50 transition-all text-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.tiny.cloud/1/d1thuv95ujdzj1v2pq3hcfjsd8r0gyzcwvkwsaybnexru0nl/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    tinymce.init({
        selector: '#description',
        plugins: 'link image code table lists',
        toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image | code',
        height: 400,
        content_style: 'body { font-family: "Ubuntu", sans-serif; font-size: 14px; }',
        setup: function(editor) {
            editor.on('change', function() {
                tinymce.triggerSave();
            });
        }
    });
});
</script>

<?php include 'admin-footer.php'; ?>
