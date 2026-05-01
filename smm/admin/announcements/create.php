<?php
include '../../config/dbconfig.php';
include '../../include/functions.php';
include '../../include/auth.php';

// Require admin
requireAdmin();

$pageTitle = 'Post New Announcement';
$success_message = '';
$error_message = '';
$form_submitted = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'announcement') {
    $form_submitted = true;
    
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
        if ($check_slug->num_rows > 0) {
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
                $_POST = array(); // Clear form data
            } else {
                $error_message = "Error creating announcement: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

include '../../include/admin-layout-start.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Post New Announcement</h1>
    <a href="../announcements/" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
        Back to Announcements
    </a>
</div>

<?php if ($form_submitted): ?>
    <div class="mb-6 p-4 rounded bg-primary-100 text-primary-800">
        Form was submitted. Check for errors below.
    </div>
<?php endif; ?>

<?php if ($success_message): ?>
    <div class="mb-6 p-4 rounded bg-primary-100 text-primary-800">
        <?php echo htmlspecialchars($success_message); ?>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="mb-6 p-4 rounded bg-red-100 text-red-800">
        <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="announcementForm">
        <input type="hidden" name="form_type" value="announcement">
        
        <div class="mb-4">
            <label for="title" class="block text-gray-700 font-medium mb-2">Announcement Title</label>
            <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary-500" id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
            <small class="text-gray-500">This will be used to generate a SEO-friendly URL slug.</small>
        </div>
        
        <div class="mb-4">
            <label for="description" class="block text-gray-700 font-medium mb-2">Description</label>
            <textarea class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary-500" id="description" name="description" rows="10" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            <small class="text-gray-500">HTML formatting is supported.</small>
        </div>
        
        <div class="mb-4">
            <label for="feature_image_url" class="block text-gray-700 font-medium mb-2">Feature Image URL</label>
            <input type="url" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary-500" id="feature_image_url" name="feature_image_url" value="<?php echo htmlspecialchars($_POST['feature_image_url'] ?? ''); ?>">
            <small class="text-gray-500">Enter a URL for the feature image.</small>
        </div>
        
        <div class="mb-4">
            <label for="meta_description" class="block text-gray-700 font-medium mb-2">Meta Description</label>
            <textarea class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary-500" id="meta_description" name="meta_description" rows="3"><?php echo htmlspecialchars($_POST['meta_description'] ?? ''); ?></textarea>
            <small class="text-gray-500">A brief description for SEO purposes.</small>
        </div>
        
        <div class="mb-4">
            <label for="keywords" class="block text-gray-700 font-medium mb-2">Keywords</label>
            <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary-500" id="keywords" name="keywords" value="<?php echo htmlspecialchars($_POST['keywords'] ?? ''); ?>">
            <small class="text-gray-500">Enter keywords separated by commas.</small>
        </div>
        
        <div class="mb-4">
            <label for="status" class="block text-gray-700 font-medium mb-2">Status</label>
            <select class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary-500" id="status" name="status">
                <option value="published" <?php echo (isset($_POST['status']) && $_POST['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
                <option value="draft" <?php echo (isset($_POST['status']) && $_POST['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
            </select>
        </div>
        
        <div class="flex space-x-3">
            <button type="submit" class="px-6 py-2 bg-primary-500 text-white rounded hover:bg-primary-600" id="submitBtn">Post Announcement</button>
            <button type="button" class="px-6 py-2 bg-primary-500 text-white rounded hover:bg-primary-600" id="generateBtn">Generate Auto</button>
            <a href="../announcements/" class="px-6 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 inline-block">Cancel</a>
        </div>
    </form>
</div>

<script src="https://cdn.tiny.cloud/1/d1thuv95ujdzj1v2pq3hcfjsd8r0gyzcwvkwsaybnexru0nl/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
// Initialize TinyMCE
document.addEventListener('DOMContentLoaded', function() {
    tinymce.init({
        selector: '#description',
        plugins: 'link image code table lists',
        toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image | code',
        height: 400,
        setup: function(editor) {
            editor.on('change', function() {
                tinymce.triggerSave();
            });
        }
    });
    
    // Form validation
    document.getElementById('announcementForm').addEventListener('submit', function(e) {
        tinymce.triggerSave();
        let title = document.getElementById('title').value.trim();
        let description = tinymce.get('description').getContent().trim();
        if (!title || !description) {
            e.preventDefault();
            alert('Title and description are required fields.');
            return false;
        }
    });

    // Auto-generate button
    document.getElementById('generateBtn').addEventListener('click', function() {
        document.getElementById('generateBtn').disabled = true;
        document.getElementById('generateBtn').innerText = 'Generating...';

        fetch('https://smmv.shop/admin/generate_blog.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ action: 'generate' })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status + ' ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            // If there's an error, log it but still use the data
            if (data.error) {
                console.warn('API error:', data.error);
            }
            // Populate form fields with a slight delay for TinyMCE
            setTimeout(() => {
                document.getElementById('title').value = data.title;
                tinymce.get('description').setContent(data.description);
                document.getElementById('meta_description').value = data.meta_description;
                document.getElementById('keywords').value = data.keywords;
                tinymce.triggerSave();
            }, 100); // 100ms delay to ensure TinyMCE is ready
        })
        .catch(error => {
            alert('Error generating content: ' + error.message);
        })
        .finally(() => {
            document.getElementById('generateBtn').disabled = false;
            document.getElementById('generateBtn').innerText = 'Generate Auto';
        });
    });
});
</script>

<?php include '../../include/admin-layout-end.php'; ?>