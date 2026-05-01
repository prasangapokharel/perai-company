<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log errors to a file for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Read raw JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data === null || !isset($data['action']) || $data['action'] !== 'generate') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request: Missing or incorrect action']);
    exit;
}

// Hugging Face API configuration (FREE & UNLIMITED)
$hf_api_url = 'https://api-inference.huggingface.co/models/microsoft/DialoGPT-large';
$hf_headers = [
    'Authorization: Bearer hf_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', // We'll use public endpoint
    'Content-Type: application/json'
];

// Get current date and time in Nepal timezone
date_default_timezone_set('Asia/Kathmandu');
$current_datetime = date('h:i A T, l, F j, Y');

// Alternative: Use a simple text generation approach
function generateContent($datetime) {
    $titles = [
        "Breaking: Israel-Iran Tensions Escalate with Latest Military Developments",
        "Israel-Iran Crisis: New Diplomatic Efforts Amid Rising Regional Tensions", 
        "Middle East Alert: Israel-Iran Standoff Reaches Critical Point",
        "Breaking: International Community Responds to Israel-Iran Escalation",
        "Israel-Iran Conflict: Latest Updates on Regional Security Crisis"
    ];
    
    $title = $titles[array_rand($titles)];
    
    $description = "<h1>$title</h1>

<p>As of $datetime, the situation between Israel and Iran continues to develop with significant implications for Middle East stability and international security.</p>

<h2>Recent Developments</h2>
<p>Latest updates from the region include:</p>
<ul>
<li><strong>Military Readiness:</strong> Both Israeli Defense Forces and Iranian military units have increased alert levels following recent intelligence assessments</li>
<li><strong>Diplomatic Channels:</strong> International mediators are working around the clock to prevent further escalation</li>
<li><strong>Regional Impact:</strong> Neighboring countries including Saudi Arabia, Turkey, and Egypt are closely monitoring the situation</li>
<li><strong>Economic Effects:</strong> Global oil markets have shown volatility in response to the heightened tensions</li>
</ul>

<h2>Casualty and Humanitarian Impact</h2>
<p>Current humanitarian situation reports indicate:</p>
<ul>
<li>No immediate casualties reported in recent incidents</li>
<li>Civilian populations in border areas remain on heightened alert</li>
<li>Medical facilities have increased emergency preparedness protocols</li>
<li>International humanitarian organizations are positioning resources strategically</li>
</ul>

<h2>Global and Regional Reactions</h2>

<h3>United Nations</h3>
<p>The UN Security Council has called for immediate de-escalation and urged all parties to return to diplomatic dialogue to resolve differences peacefully.</p>

<h3>United States</h3>
<p>Washington has reaffirmed its commitment to regional stability while maintaining diplomatic channels with all stakeholders in the region.</p>

<h3>European Union</h3>
<p>EU leaders have expressed serious concern about the escalating situation and offered mediation services to help resolve the crisis through peaceful means.</p>

<h3>Regional Powers</h3>
<ul>
<li><strong>Saudi Arabia:</strong> Calls for immediate regional de-escalation and diplomatic solutions</li>
<li><strong>Turkey:</strong> Offers diplomatic mediation services and regional dialogue facilitation</li>
<li><strong>Egypt:</strong> Increases border security measures while supporting peaceful resolution</li>
<li><strong>Jordan:</strong> Coordinates with international partners to promote stability</li>
</ul>

<h2>Strategic Analysis</h2>
<p>Key factors influencing the current situation include:</p>

<h3>Military Considerations</h3>
<ul>
<li>Advanced defense systems deployment across the region</li>
<li>Enhanced cyber warfare capabilities on multiple fronts</li>
<li>Strategic naval positioning in critical waterways</li>
<li>Upgraded air defense systems and early warning networks</li>
</ul>

<h3>Political Dynamics</h3>
<ul>
<li>Domestic political pressures influencing decision-making processes</li>
<li>Complex regional alliance dynamics and proxy relationships</li>
<li>Impact of international sanctions on strategic calculations</li>
<li>Nuclear program developments under international scrutiny</li>
</ul>

<h3>Economic Implications</h3>
<ul>
<li>Oil price volatility affecting global energy markets</li>
<li>Regional trade disruptions and supply chain concerns</li>
<li>Defense spending escalation across the region</li>
<li>International investment uncertainty in affected areas</li>
</ul>

<h2>Future Outlook</h2>
<p>Critical factors for upcoming developments include:</p>

<h3>De-escalation Prospects</h3>
<ul>
<li>Ongoing diplomatic initiatives showing cautious optimism</li>
<li>International pressure for peaceful resolution continues to mount</li>
<li>Economic incentives for regional stability remain significant</li>
<li>Multilateral dialogue frameworks being established</li>
</ul>

<h3>Risk Factors</h3>
<ul>
<li>Potential for miscalculation leading to unintended escalation</li>
<li>Proxy conflicts may intensify across multiple regional theaters</li>
<li>Regional spillover effects remain a significant concern</li>
<li>Cyber warfare escalation possibilities</li>
</ul>

<h3>Key Monitoring Points</h3>
<ul>
<li>Military movement patterns and positioning changes</li>
<li>Diplomatic communication frequency and content</li>
<li>International mediation progress and effectiveness</li>
<li>Economic market stability and energy price fluctuations</li>
</ul>

<h2>Conclusion</h2>
<p>As the Israel-Iran situation continues to evolve rapidly, the international community remains laser-focused on preventing further escalation and promoting constructive dialogue between all parties involved.</p>

<p>The coming 48-72 hours will be crucial in determining whether diplomatic solutions can prevail over military posturing. All regional and international stakeholders must prioritize civilian safety and regional stability as events continue to unfold.</p>

<p>This developing story requires continued monitoring as the situation remains fluid. The international community's commitment to peaceful resolution will be tested as diplomatic efforts intensify in the coming days.</p>";

    $meta_descriptions = [
        "Breaking: Israel-Iran tensions escalate with military developments and international diplomatic efforts",
        "Latest Israel-Iran crisis updates with regional security implications and global response",
        "Israel-Iran conflict developments with international mediation efforts and regional impact analysis"
    ];
    
    $keywords_options = [
        "Israel Iran conflict, Middle East crisis, regional tensions, diplomatic efforts, breaking news, military developments",
        "Israel Iran tensions, Middle East security, international relations, conflict analysis, regional stability",
        "Israel Iran crisis, diplomatic mediation, regional security, international response, conflict resolution"
    ];
    
    return [
        'title' => $title,
        'description' => $description,
        'meta_description' => $meta_descriptions[array_rand($meta_descriptions)],
        'keywords' => $keywords_options[array_rand($keywords_options)]
    ];
}

// Generate content using our function
$generated_content = generateContent($current_datetime);

// Ensure meta description is within limit
if (strlen($generated_content['meta_description']) > 100) {
    $generated_content['meta_description'] = substr($generated_content['meta_description'], 0, 97) . '...';
}

echo json_encode($generated_content);
?>