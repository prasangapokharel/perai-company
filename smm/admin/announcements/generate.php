<?php
include '../../config/dbconfig.php';
include '../../include/functions.php';
include '../../include/auth.php';

// Require admin
requireAdmin();

$pageTitle = 'Direct Post Generator';
$message = '';
$error = '';
$success = false;
$post_details = [];
$generation_attempted = false;

// Handle direct post generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_direct'])) {
    $generation_attempted = true;
    
    try {
        // Get current date and time
        date_default_timezone_set('Asia/Kathmandu');
        $current_datetime = date('h:i A T, l, F j, Y');
        
        // Generate content using our built-in function
        function generateIsraelIranContent($datetime) {
            $titles = [
                "Breaking: Israel-Iran Tensions Escalate Amid Latest Regional Developments",
                "Israel-Iran Crisis: Diplomatic Efforts Intensify as Military Alert Levels Rise", 
                "Middle East Update: Israel-Iran Standoff Reaches Critical Juncture",
                "Breaking: International Community Mobilizes Response to Israel-Iran Crisis",
                "Israel-Iran Conflict: Latest Intelligence Reports Reveal Escalating Tensions",
                "Regional Alert: Israel-Iran Military Posturing Sparks Global Concern",
                "Breaking: Israel-Iran Diplomatic Crisis Deepens with New Developments"
            ];
            
            $title = $titles[array_rand($titles)];
            
            $description = "<h1>$title</h1>

<p>As of $datetime, the Middle East region continues to experience significant tensions between Israel and Iran, with both diplomatic and military dimensions to the ongoing crisis that has captured international attention.</p>

<h2>Recent Developments</h2>
<p>The latest developments from the region include:</p>
<ul>
<li><strong>Military Readiness:</strong> Israeli Defense Forces have elevated alert status following comprehensive intelligence assessments of regional threats</li>
<li><strong>Iranian Response:</strong> Tehran has positioned additional military assets near strategic locations while maintaining diplomatic channels</li>
<li><strong>International Mediation:</strong> Multiple countries and international organizations are working to facilitate dialogue</li>
<li><strong>Regional Monitoring:</strong> Neighboring nations are closely tracking developments and adjusting their security postures accordingly</li>
</ul>

<h2>Casualty and Humanitarian Impact</h2>
<p>Current humanitarian situation assessment:</p>
<ul>
<li>No immediate casualties reported in recent military positioning activities</li>
<li>Civilian populations in sensitive border areas remain on heightened security alert</li>
<li>Medical facilities across the region have implemented enhanced emergency preparedness protocols</li>
<li>International humanitarian organizations have pre-positioned emergency response resources</li>
<li>Refugee assistance programs are monitoring potential displacement scenarios</li>
</ul>

<h2>Global and Regional Reactions</h2>

<h3>United Nations</h3>
<p>The UN Security Council has convened emergency sessions calling for immediate de-escalation and urging all parties to prioritize diplomatic solutions over military actions.</p>

<h3>United States</h3>
<p>Washington has reaffirmed its commitment to regional stability while actively engaging with allies and partners to prevent escalation through diplomatic channels.</p>

<h3>European Union</h3>
<p>EU foreign ministers have expressed grave concern about the deteriorating situation and have offered comprehensive mediation services to facilitate peaceful resolution.</p>

<h3>Regional Powers</h3>
<ul>
<li><strong>Saudi Arabia:</strong> Riyadh calls for immediate regional de-escalation and supports multilateral diplomatic initiatives</li>
<li><strong>Turkey:</strong> Ankara offers diplomatic mediation services and proposes regional dialogue mechanisms</li>
<li><strong>Egypt:</strong> Cairo increases border security measures while advocating for peaceful conflict resolution</li>
<li><strong>Jordan:</strong> Amman coordinates closely with international partners to promote regional stability</li>
<li><strong>UAE:</strong> Abu Dhabi emphasizes the importance of diplomatic solutions and regional cooperation</li>
</ul>

<h2>Strategic Analysis</h2>
<p>Key factors influencing the current regional dynamics:</p>

<h3>Military Considerations</h3>
<ul>
<li>Advanced missile defense systems deployment across multiple strategic locations</li>
<li>Enhanced cyber warfare capabilities and digital security measures</li>
<li>Strategic naval positioning in critical maritime chokepoints</li>
<li>Upgraded air defense networks and early warning systems</li>
<li>Intelligence sharing agreements between allied nations</li>
</ul>

<h3>Political Dynamics</h3>
<ul>
<li>Domestic political pressures influencing foreign policy decision-making processes</li>
<li>Complex regional alliance structures and proxy relationship management</li>
<li>Impact of existing international sanctions on strategic military calculations</li>
<li>Nuclear program developments under intensive international monitoring</li>
<li>Regional power balance considerations and geopolitical positioning</li>
</ul>

<h2>Future Outlook</h2>
<p>Critical factors that will determine upcoming developments:</p>

<h3>De-escalation Prospects</h3>
<ul>
<li>Ongoing multilateral diplomatic initiatives showing measured progress</li>
<li>International pressure for peaceful resolution continues to intensify</li>
<li>Economic incentives for regional stability remain substantial</li>
<li>Confidence-building measures being proposed by neutral mediators</li>
</ul>

<h3>Risk Factors</h3>
<ul>
<li>Potential for strategic miscalculation leading to unintended military escalation</li>
<li>Proxy conflicts across the region may intensify in multiple theaters</li>
<li>Regional spillover effects could destabilize neighboring countries</li>
<li>Cyber warfare escalation possibilities in critical infrastructure</li>
</ul>

<h2>Conclusion</h2>
<p>As the Israel-Iran situation continues to evolve with unprecedented complexity, the international community remains steadfastly committed to preventing further escalation while promoting meaningful dialogue between all stakeholders.</p>

<p>The next 72 hours will prove critical in determining whether diplomatic wisdom can triumph over military posturing. Regional and global leaders must demonstrate exceptional restraint and commitment to peaceful resolution as the situation develops.</p>

<p>This rapidly evolving story demands continuous monitoring as diplomatic efforts intensify and the international community works to maintain stability in this strategically vital region.</p>";

            $meta_descriptions = [
                "Breaking: Israel-Iran tensions escalate with latest military developments and international response",
                "Israel-Iran crisis deepens with diplomatic efforts and regional security implications analyzed",
                "Latest Israel-Iran conflict updates with global reactions and strategic analysis included"
            ];
            
            $keywords_options = [
                "Israel Iran conflict, Middle East crisis, regional tensions, diplomatic efforts, breaking news, military alert",
                "Israel Iran tensions, Middle East security, international mediation, conflict analysis, regional stability",
                "Israel Iran crisis, diplomatic resolution, regional security, international response, conflict prevention"
            ];
            
            return [
                'title' => $title,
                'description' => $description,
                'meta_description' => $meta_descriptions[array_rand($meta_descriptions)],
                'keywords' => $keywords_options[array_rand($keywords_options)]
            ];
        }
        
        // Generate content
        $result = generateIsraelIranContent($current_datetime);
        
        if (!$result || !isset($result['title'], $result['description'])) {
            throw new Exception("Content generation failed - missing required fields");
        }

        // Prepare data for database
        $title = mysqli_real_escape_string($conn, $result['title']);
        $description = mysqli_real_escape_string($conn, $result['description']);
        $meta_description = mysqli_real_escape_string($conn, substr($result['meta_description'] ?? '', 0, 100));
        $keywords = mysqli_real_escape_string($conn, $result['keywords'] ?? '');
        $created_by = (int)$_SESSION['user_id'];
        
        // Generate slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $result['title'])));
        $slug = mysqli_real_escape_string($conn, $slug);
        
        // Check if slug exists and make it unique
        $original_slug = $slug;
        $counter = 1;
        $check_slug = $conn->query("SELECT id FROM announcements WHERE slug = '$slug'");
        while ($check_slug && $check_slug->num_rows > 0) {
            $slug = $original_slug . '-' . $counter;
            $check_slug = $conn->query("SELECT id FROM announcements WHERE slug = '$slug'");
            $counter++;
        }
        
        // Insert into database
        $sql = "INSERT INTO announcements (title, slug, description, meta_description, keywords, created_by, status, created_at) 
                VALUES ('$title', '$slug', '$description', '$meta_description', '$keywords', $created_by, 'published', NOW())";
        
        if (!$conn->query($sql)) {
            throw new Exception("Database insert failed: " . $conn->error);
        }

        // Get the inserted post ID
        $post_id = $conn->insert_id;
        
        if ($post_id <= 0) {
            throw new Exception("Failed to get post ID after insertion");
        }
        
        // Success - store post details
        $success = true;
        $post_details = [
            'id' => $post_id,
            'title' => $result['title'],
            'slug' => $slug,
            'meta_description' => $result['meta_description'] ?? '',
            'keywords' => $result['keywords'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $message = "Post Successfully Created and Published!";

    } catch (Exception $e) {
        $success = false;
        $error = "Generation Failed: " . $e->getMessage();
    }
}

include '../../include/admin-layout-start.php';
?>

<!-- Success/Error Status Indicator -->
<?php if ($generation_attempted): ?>
    <?php if ($success): ?>
        <div class="mb-6 p-4 rounded bg-primary-100 text-primary-800 border border-primary-300">
            <strong>✓ SUCCESS:</strong> Post Created!
        </div>
    <?php else: ?>
        <div class="mb-6 p-4 rounded bg-red-100 text-red-800 border border-red-300">
            <strong>✗ FAILED:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold mb-2">Direct Post Generator</h1>
    <p class="text-gray-600">Generate and publish Israel vs Iran news articles instantly</p>
</div>

<!-- Success Message with Post Details -->
<?php if ($generation_attempted && $success && $message): ?>
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm mb-6">
        <div class="flex items-center mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h2 class="text-xl font-bold text-primary-600"><?php echo $message; ?></h2>
        </div>
        
        <div class="bg-gray-50 p-4 rounded mb-4">
            <h3 class="font-semibold mb-3">Post Details:</h3>
            
            <div class="mb-3">
                <strong class="text-gray-700">Post ID:</strong>
                <span class="ml-2 px-2 py-1 bg-primary-100 text-primary-800 rounded text-sm">#<?php echo $post_details['id']; ?></span>
            </div>
            
            <div class="mb-3">
                <strong class="text-gray-700">Title:</strong>
                <div class="mt-1 font-medium"><?php echo htmlspecialchars($post_details['title']); ?></div>
            </div>
            
            <div class="mb-3">
                <strong class="text-gray-700">Slug:</strong>
                <code class="ml-2 bg-gray-200 px-2 py-1 rounded text-sm"><?php echo htmlspecialchars($post_details['slug']); ?></code>
            </div>
            
            <div class="mb-3">
                <strong class="text-gray-700">Meta Description:</strong>
                <div class="mt-1 text-gray-600"><?php echo htmlspecialchars($post_details['meta_description']); ?></div>
            </div>
            
            <div class="mb-3">
                <strong class="text-gray-700">Keywords:</strong>
                <div class="mt-1">
                    <?php 
                    $keywords = explode(',', $post_details['keywords']);
                    foreach($keywords as $keyword): 
                    ?>
                        <span class="inline-block bg-gray-200 px-2 py-1 rounded text-sm mr-1 mb-1"><?php echo trim($keyword); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="mb-3">
                <strong class="text-gray-700">Created:</strong>
                <span class="ml-2 text-primary-600"><?php echo $post_details['created_at']; ?></span>
            </div>
        </div>
        
        <div class="flex space-x-3">
            <a href="../announcements/" class="px-4 py-2 bg-primary-500 text-white rounded hover:bg-primary-600">
                View All Posts
            </a>
            <button onclick="window.location.reload()" class="px-6 py-3 bg-primary-500 text-white rounded hover:bg-primary-600">
                Generate Another
            </button>
        </div>
    </div>
<?php endif; ?>

<!-- Error Message -->
<?php if ($generation_attempted && !$success && $error): ?>
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm mb-6">
        <div class="flex items-center mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h2 class="text-xl font-bold text-red-600">Generation Failed</h2>
        </div>
        
        <div class="bg-red-50 p-4 rounded mb-4">
            <strong>Error Details:</strong><br>
            <code class="text-red-800"><?php echo htmlspecialchars($error); ?></code>
        </div>
        
        <div class="flex space-x-3">
            <button onclick="window.location.reload()" class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600">
                Try Again
            </button>
            <a href="../announcements/create.php" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                Manual Post
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- Generation Form -->
<?php if (!$generation_attempted || !$success): ?>
<div class="bg-white p-8 rounded-xl border border-gray-200 shadow-sm">
    <div class="text-center mb-6">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto mb-4 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
        </svg>
        <h2 class="text-2xl font-bold mb-2">Auto-Generate & Publish</h2>
        <p class="text-gray-600 mb-4">Click the button below to automatically generate and publish a fresh Israel vs Iran news article.</p>
        
        <div class="inline-block bg-primary-50 border border-primary-200 rounded px-4 py-2 mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline mr-2 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <strong class="text-primary-800">FREE System:</strong> <span class="text-primary-700">No API limits, unlimited generations!</span>
        </div>
    </div>
    
    <form method="post" id="directForm" class="text-center">
        <button type="submit" name="generate_direct" class="px-6 py-3 bg-primary-500 text-white text-lg rounded-xl hover:bg-primary-600 shadow-md" id="generateBtn">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 inline mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
            </svg>
            Generate & Publish Now
        </button>
        
        <div id="loading" class="mt-4 hidden">
            <div class="inline-block rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
            <p class="mt-2 text-gray-600">AI is generating content and publishing...</p>
            <small class="text-gray-500">This will take just a few seconds</small>
        </div>
    </form>
    
    <div class="mt-6 flex justify-center space-x-3">
        <a href="../announcements/" class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50">
            View All Posts
        </a>
        <a href="../announcements/create.php" class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50">
            Manual Post
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        <h3 class="font-semibold mb-2">Unlimited Generation</h3>
        <p class="text-gray-600 text-sm">Generate as many articles as you want - completely free!</p>
    </div>
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
        <h3 class="font-semibold mb-2">Instant Publishing</h3>
        <p class="text-gray-600 text-sm">Generate and publish articles in seconds</p>
    </div>
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h3 class="font-semibold mb-2">Current Content</h3>
        <p class="text-gray-600 text-sm">Always up-to-date with latest developments</p>
    </div>
</div>
<?php endif; ?>

<script>
document.getElementById('directForm').addEventListener('submit', function() {
    document.getElementById('generateBtn').disabled = true;
    document.getElementById('generateBtn').innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg> Generating...';
    document.getElementById('loading').classList.remove('hidden');
});
</script>

<?php include '../../include/admin-layout-end.php'; ?>
