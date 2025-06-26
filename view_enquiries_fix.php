<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start output buffering
ob_start();

try {
    // Fix for include path issues
    $base_path = realpath(dirname(__FILE__));
    set_include_path(get_include_path() . PATH_SEPARATOR . $base_path);
    
    // Check if the database config file exists
    if (!file_exists('config/database.php')) {
        throw new Exception("Database configuration file not found at config/database.php");
    }
    
    // Include database connection
    require_once "config/database.php";
    
    // Check if connection was successful
    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection failed. Please check your database credentials.");
    }
    
    // Check if header.php exists
    if (!file_exists('includes/header.php')) {
        throw new Exception("Header file not found at includes/header.php");
    }
    
    // Include header
    require_once "includes/header.php";
    
    // Simple query to test database connection
    $test_query = "SELECT COUNT(*) as count FROM enquiries";
    $result = mysqli_query($conn, $test_query);
    
    if (!$result) {
        throw new Exception("Error executing query: " . mysqli_error($conn));
    }
    
    $row = mysqli_fetch_assoc($result);
    echo "<div class='alert alert-success'>Database connection successful. Found " . $row['count'] . " enquiries.</div>";
    
    // Include the original view_enquiries.php content
    // require_once "view_enquiries_original.php";
    
    // Include footer
    require_once "includes/footer.php";
    
} catch (Exception $e) {
    // Get the output buffer content and clean the buffer
    ob_end_clean();
    
    // Display error message
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Error</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .error-box { border: 1px solid #f44336; padding: 20px; margin-bottom: 20px; }
            .fix-box { border: 1px solid #2196F3; padding: 20px; }
            pre { background-color: #f5f5f5; padding: 10px; overflow: auto; }
        </style>
    </head>
    <body>
        <h1>Error Loading Page</h1>
        <div class='error-box'>
            <h2>Error Details</h2>
            <p>" . htmlspecialchars($e->getMessage()) . "</p>
        </div>
        
        <div class='fix-box'>
            <h2>Common Solutions for cPanel Deployment</h2>
            <ol>
                <li>Update database credentials in config/database.php to match your cPanel database</li>
                <li>Check file paths in includes - they may need to be adjusted for your cPanel directory structure</li>
                <li>Make sure all files have proper permissions (644 for files, 755 for directories)</li>
                <li>Check for any hardcoded paths that might need to be updated</li>
            </ol>
            
            <h3>Sample Database Configuration for cPanel</h3>
            <pre>
&lt;?php
// Database configuration for cPanel
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'your_cpanel_db_username');  // Change this
define('DB_PASSWORD', 'your_cpanel_db_password');  // Change this
define('DB_NAME', 'your_cpanel_db_name');          // Change this

// Attempt to connect to MySQL database with database name directly
\$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if(\$conn === false){
    die(\"ERROR: Could not connect to database. \" . mysqli_connect_error());
}

// Set charset to ensure proper encoding
mysqli_set_charset(\$conn, \"utf8\");
?&gt;
            </pre>
            
            <p>For more detailed diagnostics, upload and run the <strong>cpanel_fix.php</strong> and <strong>diagnostics.php</strong> files.</p>
        </div>
    </body>
    </html>";
}
?>