<?php
// Check if CSS and JS files exist on the server
$files_to_check = [
    'assets/css/filter-styles.css',
    'assets/css/bootstrap.min.css',
    'assets/js/bootstrap.min.js',
    'assets/js/jquery.min.js',
    'vendors/styles/core.css',
    'vendors/styles/icon-font.min.css',
    'vendors/styles/style.css'
];

echo "<h2>File Check Results:</h2>";
foreach ($files_to_check as $file) {
    $exists = file_exists($file);
    $status = $exists ? "✅ EXISTS" : "❌ MISSING";
    echo "<p>{$file} - {$status}</p>";
}

echo "<h2>Current Directory:</h2>";
echo "<p>" . getcwd() . "</p>";

echo "<h2>Directory Contents:</h2>";
$files = scandir('.');
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo "<p>{$file}</p>";
    }
}
?>