<?php
include '../../config/dbconfig.php';
include '../../include/functions.php';
include '../../include/auth.php';

// Require admin
requireAdmin();

$pageTitle = 'Edit Announcement';
$success_message = '';
$error_message = '';

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../announcements/");
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
        if ($check_slug->num_rows > 0) {
            // Append a random string to make slug unique
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
    header("Location: ../announcements/");
    exit();
}

$announcement = $result->fetch_assoc();

include '../../include/admin-layout-start.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Edit Announcement</h1>
</div>

<?php if ($success_message): ?>
    <div class="mb-6 p-4 rounded bg-primary-100 text-primary-800">
        <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="mb-6 p-4 rounded bg-red-100 text-red-800">
        <?php echo $error_message; ?>
    </div>
<?php endif; ?>

<div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
    <form method="post" action="">
        <div class="mb-4">
            <label for="title" class="block text-gray-700 font-medium mb-2">Announcement Title</label>
            <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary-500" id="title" name="title" value="<?php echo htmlspecialchars($announcement['title']); ?>" required>
            <small class="text-gray-500">This will be used to generate a SEO-friendly URL slug if changed.</small>
        </div>
        
        <div class="mb-4">
            <label for="description" class="block text-gray-700 font-medium mb-2">Description</label>
            <textarea class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary-500" id="description" name="description" rows="10" required><?php echo $announcement['description']; ?></textarea>
            <small class="text-gray-500">HTML formatting is supported.</small>
        </div>
        
        <div class="mb-4">
            <label for="feature_image_url" class="block text-gray-700 font-medium mb-2">Feature Image URL</label>
            <input type="url" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary-500" id="feature_image_url" name="feature_image_url" value="<?php echo htmlspecialchars($announcement['feature_image_url']); ?>">
            <small class="text-gray-500">Enter a URL for the feature image.</small>
        </div>
        
        <div class="mb-4">
            <label for="meta_description" class="block text-gray-700 font-medium mb-2">Meta Description</label>
            <textarea class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary-500" id="meta_description" name="meta_description" rows="3"><?php echo htmlspecialchars($announcement['meta_description']); ?></textarea>
            <small class="text-gray-500">A brief description for SEO purposes.</small>
        </div>
        
        <div class="mb-4">
            <label for="keywords" class="block text-gray-700 font-medium mb-2">Keywords</label>
            <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary-500" id="keywords" name="keywords" value="<?php echo htmlspecialchars($announcement['keywords']); ?>">
            <small class="text-gray-500">Enter keywords separated by commas.</small>
        </div>
        
        <div class="mb-4">
            <label for="status" class="block text-gray-700 font-medium mb-2">Status</label>
            <select class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary-500" id="status" name="status">
                <option value="published" <?php echo $announcement['status'] == 'published' ? 'selected' : ''; ?>>Published</option>
                <option value="draft" <?php echo $announcement['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
            </select>
        </div>
        
        <div class="flex space-x-3">
            <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded hover:bg-primary-600">Update Announcement</button>
            <a href="../announcements/" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Back to Announcements</a>
        </div>
    </form>
</div>

<script src="https://cdn.tiny.cloud/1/d1thuv95ujdzj1v2pq3hcfjsd8r0gyzcwvkwsaybnexru0nl/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#description',
        plugins: 'link image code table lists',
        toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image | code',
        height: 400
    });
</script>

<?php include '../../include/admin-layout-end.php'; ?>
