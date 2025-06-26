<?php
// This file helps fix common issues when migrating to cPanel

// 1. Check if we're on a cPanel server
$on_cpanel = (strpos($_SERVER['SERVER_SOFTWARE'], 'cPanel') !== false || 
              file_exists('/usr/local/cpanel/version') || 
              getenv('CPANEL') !== false);

echo "<h1>cPanel Migration Helper</h1>";

if ($on_cpanel) {
    echo "<p>cPanel environment detected.</p>";
} else {
    echo "<p>This doesn't appear to be a cPanel server.</p>";
}

// 2. Check database connection
echo "<h2>Database Connection Test</h2>";

// Try to include the database config
if (file_exists('config/database.php')) {
    echo "<p>Found database.php in config/ directory.</p>";
    
    // Test the connection
    try {
        require_once 'config/database.php';
        if (isset($conn) && $conn) {
            echo "<p style='color: green;'>Database connection successful using current configuration.</p>";
            
            // Check if we can query the database
            $test_query = mysqli_query($conn, "SELECT 1");
            if ($test_query) {
                echo "<p style='color: green;'>Database query test successful.</p>";
            } else {
                echo "<p style='color: red;'>Database query test failed: " . mysqli_error($conn) . "</p>";
            }
        } else {
            echo "<p style='color: red;'>Database connection failed.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error testing database connection: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>Database configuration file not found at config/database.php</p>";
}

// 3. Check for includes path issues
echo "<h2>Include Path Test</h2>";
echo "<p>Current include_path: " . ini_get('include_path') . "</p>";

// 4. Check for file path issues
echo "<h2>File Path Test</h2>";
echo "<p>Document root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Script filename: " . $_SERVER['SCRIPT_FILENAME'] . "</p>";
echo "<p>Current directory: " . getcwd() . "</p>";

// 5. Check for header.php
echo "<h2>Header Include Test</h2>";
if (file_exists('includes/header.php')) {
    echo "<p style='color: green;'>Found header.php in includes/ directory.</p>";
} else {
    echo "<p style='color: red;'>header.php not found in includes/ directory.</p>";
    
    // Check alternative locations
    if (file_exists('header.php')) {
        echo "<p>Found header.php in root directory.</p>";
    }
    
    if (file_exists('../includes/header.php')) {
        echo "<p>Found header.php in ../includes/ directory.</p>";
    }
}

// 6. Fix instructions
echo "<h2>Recommended Fixes</h2>";
echo "<ol>";
echo "<li>Update database credentials in config/database.php to match your cPanel database</li>";
echo "<li>Check file paths in includes - they may need to be adjusted for your cPanel directory structure</li>";
echo "<li>Make sure all files have proper permissions (644 for files, 755 for directories)</li>";
echo "<li>Check for any hardcoded paths that might need to be updated</li>";
echo "<li>If using .htaccess, make sure it's properly configured for cPanel</li>";
echo "</ol>";

// 7. Create a sample database config for cPanel
echo "<h2>Sample Database Configuration for cPanel</h2>";
echo "<pre>";
echo htmlspecialchars('<?php
// Database configuration for cPanel
define(\'DB_SERVER\', \'localhost\');
define(\'DB_USERNAME\', \'your_cpanel_db_username\');  // Change this
define(\'DB_PASSWORD\', \'your_cpanel_db_password\');  // Change this
define(\'DB_NAME\', \'your_cpanel_db_name\');          // Change this

// Attempt to connect to MySQL database with database name directly
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($conn === false){
    // Log the error
    error_log("Database connection failed: " . mysqli_connect_error(), 3, "db_error_log.txt");
    
    // Display error message
    die("ERROR: Could not connect to database. Please check your database credentials.");
}

// Set charset to ensure proper encoding
mysqli_set_charset($conn, "utf8");
?>');
echo "</pre>";

// 8. Check for common include issues
echo "<h2>Include Path Fix</h2>";
echo "<p>If you're having issues with includes, try adding this at the top of your main PHP files:</p>";
echo "<pre>";
echo htmlspecialchars('<?php
// Fix for include path issues
$base_path = realpath(dirname(__FILE__));
set_include_path(get_include_path() . PATH_SEPARATOR . $base_path);

// Then use relative paths for includes
require_once "config/database.php";
require_once "includes/header.php";
?>');
echo "</pre>";
?>