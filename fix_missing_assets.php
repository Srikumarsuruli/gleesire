<?php
// Quick fix for missing assets - add CDN links to header
$header_file = 'includes/header.php';

if (file_exists($header_file)) {
    $header_content = file_get_contents($header_file);
    
    // Check if CDN links already exist
    if (strpos($header_content, 'bootstrap.min.css') === false) {
        // Add Bootstrap CSS CDN
        $bootstrap_css = '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">';
        
        // Add jQuery and Bootstrap JS CDN
        $jquery_js = '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
        $bootstrap_js = '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>';
        
        // Insert CSS in head section
        $header_content = str_replace('</head>', $bootstrap_css . "\n</head>", $header_content);
        
        // Insert JS before closing body tag
        $header_content = str_replace('</body>', $jquery_js . "\n" . $bootstrap_js . "\n</body>", $header_content);
        
        // Write back to file
        file_put_contents($header_file, $header_content);
        
        echo "✅ CDN links added to header.php successfully!<br>";
        echo "Added:<br>";
        echo "- Bootstrap CSS CDN<br>";
        echo "- jQuery CDN<br>";
        echo "- Bootstrap JS CDN<br>";
    } else {
        echo "✅ CDN links already exist in header.php<br>";
    }
} else {
    echo "❌ Header file not found: $header_file<br>";
}

// Also check if we need to create missing directories
$directories_to_create = [
    'vendors/styles',
    'assets/css',
    'assets/js'
];

foreach ($directories_to_create as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "✅ Created directory: $dir<br>";
    } else {
        echo "✅ Directory exists: $dir<br>";
    }
}

echo "<br><strong>Next steps:</strong><br>";
echo "1. Upload this fix to your server<br>";
echo "2. Run this file on your server<br>";
echo "3. Check view_leads.php again<br>";
?>