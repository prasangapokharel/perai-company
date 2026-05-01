<?php
function includeFavicon() {
    $faviconDir = '../favicon/'; // Adjust path based on your project structure
    $faviconFiles = [
        'android-chrome-192x192.png' => '192x192',
        'android-chrome-512x512.png' => '512x512',
        'apple-touch-icon.png' => '180x180',
        'favicon-16x16.png' => '16x16',
        'favicon-32x32.png' => '32x32',
        'favicon.ico' => ''
    ];

    $output = '';
    foreach ($faviconFiles as $file => $size) {
        $filePath = $faviconDir . $file;
        if (file_exists($filePath)) {
            if ($file === 'favicon.ico') {
                $output .= '<link rel="icon" type="image/x-icon" href="' . htmlspecialchars($filePath) . '">' . PHP_EOL;
            } elseif ($file === 'apple-touch-icon.png') {
                $output .= '<link rel="apple-touch-icon" sizes="' . htmlspecialchars($size) . '" href="' . htmlspecialchars($filePath) . '">' . PHP_EOL;
            } else {
                $output .= '<link rel="icon" type="image/png" sizes="' . htmlspecialchars($size) . '" href="' . htmlspecialchars($filePath) . '">' . PHP_EOL;
            }
        }
    }

    // Include site.webmanifest if it exists
    $manifestPath = $faviconDir . 'site.webmanifest';
    if (file_exists($manifestPath)) {
        $output .= '<link rel="manifest" href="' . htmlspecialchars($manifestPath) . '">' . PHP_EOL;
    }

    return $output;
}
?>