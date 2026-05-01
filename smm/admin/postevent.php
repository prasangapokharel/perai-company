<?php
include '../config/dbconfig.php';
include '../include/functions.php';
include '../include/auth.php';

// Require admin
requireAdmin();

$pageTitle = 'Post New Announcement';
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'announcement') {
    // Validate required fields
    if (empty($_POST['title']) || empty($_POST['description'])) {
        $error_message = "Title and description are required fields.";
    } else {
        // Sanitize inputs
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $feature_image_url = trim($_POST['feature_image_url'] ?? '');
        $meta_description = trim($_POST['meta_description'] ?? '');
        $keywords = trim($_POST['keywords'] ?? '');
        $status = trim($_POST['status'] ?? 'published');
        $created_by = (int)$_SESSION['user_id'];

        // Generate slug from title
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        
        // Check if slug already exists
        $check_slug = $conn->query("SELECT id FROM announcements WHERE slug = '$slug'");
        if ($check_slug && $check_slug->num_rows > 0) {
            $slug .= '-' . substr(md5(time()), 0, 5);
        }
        
        // Use prepared statement to prevent SQL injection
        $stmt = $conn->prepare("INSERT INTO announcements (title, slug, description, feature_image_url, meta_description, keywords, created_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt === false) {
            $error_message = "Error preparing statement: " . $conn->error;
        } else {
            $stmt->bind_param("ssssssis", $title, $slug, $description, $feature_image_url, $meta_description, $keywords, $created_by, $status);
            if ($stmt->execute()) {
                $success_message = "Announcement created successfully!";
                header("Location: announcements.php");
                exit;
            } else {
                $error_message = "Error creating announcement: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

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
                <h1 class="text-3xl font-black text-slate-900 tracking-tight">Post New Announcement</h1>
                <p class="text-slate-500 font-medium mt-1">Create a new platform update or news post.</p>
            </div>
        </div>
    </div>

    <?php if ($error_message): ?>
        <div class="bg-rose-50 border border-rose-100 text-rose-700 px-6 py-4 rounded-2xl text-sm font-bold">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-8">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="announcementForm" class="space-y-6">
            <input type="hidden" name="form_type" value="announcement">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label for="title" class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Announcement Title</label>
                    <input type="text" class="w-full px-5 py-3 bg-slate-50 border-none rounded-xl text-sm font-medium focus:ring-2 focus:ring-primary-500 transition-all placeholder:text-slate-300" id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" placeholder="Enter a catchy title..." required>
                </div>
                
                <div class="md:col-span-2">
                    <label for="description" class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Main Content</label>
                    <textarea id="description" name="description" rows="12" class="w-full px-5 py-3 bg-slate-50 border-none rounded-xl text-sm font-medium focus:ring-2 focus:ring-primary-500 transition-all" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
                
                <div>
                    <label for="feature_image_url" class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Feature Image URL</label>
                    <input type="url" class="w-full px-5 py-3 bg-slate-50 border-none rounded-xl text-sm font-medium focus:ring-2 focus:ring-primary-500 transition-all placeholder:text-slate-300" id="feature_image_url" name="feature_image_url" value="<?php echo htmlspecialchars($_POST['feature_image_url'] ?? ''); ?>" placeholder="https://...">
                </div>

                <div>
                    <label for="status" class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Post Status</label>
                    <select class="w-full px-5 py-3 bg-slate-50 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-primary-500 transition-all appearance-none" id="status" name="status">
                        <option value="published" <?php echo (isset($_POST['status']) && $_POST['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
                        <option value="draft" <?php echo (isset($_POST['status']) && $_POST['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="meta_description" class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Meta Description (SEO)</label>
                    <textarea class="w-full px-5 py-3 bg-slate-50 border-none rounded-xl text-sm font-medium focus:ring-2 focus:ring-primary-500 transition-all placeholder:text-slate-300" id="meta_description" name="meta_description" rows="2" placeholder="Brief summary for search engines..."><?php echo htmlspecialchars($_POST['meta_description'] ?? ''); ?></textarea>
                </div>

                <div class="md:col-span-2">
                    <label for="keywords" class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">SEO Keywords</label>
                    <input type="text" class="w-full px-5 py-3 bg-slate-50 border-none rounded-xl text-sm font-medium focus:ring-2 focus:ring-primary-500 transition-all placeholder:text-slate-300" id="keywords" name="keywords" value="<?php echo htmlspecialchars($_POST['keywords'] ?? ''); ?>" placeholder="smm, update, panel, ...">
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-4 pt-4">
                <button type="submit" class="px-6 py-3 bg-primary-600 text-white text-xs font-black uppercase tracking-widest rounded-xl hover:bg-primary-700 transition-all shadow-lg shadow-primary-200">
                    Publish Announcement
                </button>
                <button type="button" id="generateBtn" class="px-6 py-3 bg-slate-900 text-white text-xs font-black uppercase tracking-widest rounded-xl hover:bg-slate-800 transition-all shadow-lg shadow-slate-200">
                    AI Auto-Generate
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
    
    // Auto-generate button
    document.getElementById('generateBtn').addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        btn.innerText = 'GENERATING...';

        fetch('generate_blog.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'generate' })
        })
        .then(response => response.json())
        .then(data => {
            if (data.title) {
                document.getElementById('title').value = data.title;
                tinymce.get('description').setContent(data.description);
                document.getElementById('meta_description').value = data.meta_description;
                document.getElementById('keywords').value = data.keywords;
            } else {
                alert('Generation failed: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => alert('Error: ' + error.message))
        .finally(() => {
            btn.disabled = false;
            btn.innerText = 'AI AUTO-GENERATE';
        });
    });
});
</script>

<?php include 'admin-footer.php'; ?>
