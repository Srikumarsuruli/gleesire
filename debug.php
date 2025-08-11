<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once "config/database.php";

echo "<h1>Database Connection Test</h1>";
if ($conn) {
    echo "<p>Database connection successful!</p>";
    
    // Test if tables exist
    $tables = array('tour_costings', 'enquiries', 'payment_receipts', 'user_privileges');
    echo "<h2>Table Check</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
        $exists = mysqli_num_rows($result) > 0;
        echo "<li>$table: " . ($exists ? "Exists" : "Missing") . "</li>";
    }
    echo "</ul>";
    
    // Check if tour_costings table has data
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM tour_costings");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "<p>Number of records in tour_costings: " . $row['count'] . "</p>";
    } else {
        echo "<p>Error checking tour_costings: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>Database connection failed!</p>";
}

// Check if required files exist
$files = array(
    'pdf_template.php',
    'simple_pdf_template.php',
    'includes/functions.php',
    'includes/header.php',
    'includes/footer.php'
);

echo "<h2>File Check</h2>";
echo "<ul>";
foreach ($files as $file) {
    echo "<li>$file: " . (file_exists($file) ? "Exists" : "Missing") . "</li>";
}
echo "</ul>";

// Check PHP version and extensions
echo "<h2>PHP Environment</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Extensions loaded: " . implode(', ', get_loaded_extensions()) . "</p>";

// Check session data
echo "<h2>Session Data</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>