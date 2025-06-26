<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Lead Management System Diagnostics</h1>";

// Check PHP version
echo "<h2>PHP Version</h2>";
echo "<p>Current PHP version: " . phpversion() . "</p>";
if (version_compare(phpversion(), '7.0.0', '<')) {
    echo "<p style='color: red;'>Warning: This application requires PHP 7.0 or higher.</p>";
} else {
    echo "<p style='color: green;'>PHP version is compatible.</p>";
}

// Check required extensions
echo "<h2>Required PHP Extensions</h2>";
$required_extensions = ['mysqli', 'session', 'json', 'mbstring'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p style='color: green;'>$ext extension is loaded.</p>";
    } else {
        echo "<p style='color: red;'>Warning: $ext extension is not loaded.</p>";
    }
}

// Check database connection
echo "<h2>Database Connection</h2>";
try {
    require_once "includes/config/database.php";
    
    if (isset($conn) && $conn) {
        echo "<p style='color: green;'>Database connection successful.</p>";
        
        // Check required tables
        echo "<h2>Database Tables</h2>";
        $required_tables = ['enquiries', 'users', 'departments', 'sources', 'lead_status', 'converted_leads', 'lead_status_map'];
        
        foreach ($required_tables as $table) {
            $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
            if ($result && mysqli_num_rows($result) > 0) {
                echo "<p style='color: green;'>Table '$table' exists.</p>";
            } else {
                echo "<p style='color: red;'>Warning: Table '$table' does not exist.</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>Failed to connect to the database.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Database connection error: " . $e->getMessage() . "</p>";
}

// Check file permissions
echo "<h2>File Permissions</h2>";
$important_files = [
    'includes/config/database.php',
    'includes/header.php',
    'includes/footer.php',
    'view_enquiries.php',
    'index.php',
    'login.php'
];

foreach ($important_files as $file) {
    if (file_exists($file)) {
        if (is_readable($file)) {
            echo "<p style='color: green;'>File '$file' exists and is readable.</p>";
        } else {
            echo "<p style='color: red;'>Warning: File '$file' exists but is not readable.</p>";
        }
    } else {
        echo "<p style='color: red;'>Warning: File '$file' does not exist.</p>";
    }
}

// Check session functionality
echo "<h2>Session Functionality</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p style='color: green;'>Session is active.</p>";
} else {
    echo "<p style='color: red;'>Warning: Session is not active.</p>";
}

// Server information
echo "<h2>Server Information</h2>";
echo "<p>Server software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Document root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Script filename: " . $_SERVER['SCRIPT_FILENAME'] . "</p>";

// Check for common cPanel issues
echo "<h2>cPanel Specific Checks</h2>";
if (function_exists('posix_getpwuid')) {
    $user_info = posix_getpwuid(posix_geteuid());
    echo "<p>Script is running as user: " . $user_info['name'] . "</p>";
} else {
    echo "<p>Unable to determine script user (posix functions not available).</p>";
}

// Memory limit
echo "<p>Memory limit: " . ini_get('memory_limit') . "</p>";
// Max execution time
echo "<p>Max execution time: " . ini_get('max_execution_time') . " seconds</p>";
// Upload max filesize
echo "<p>Upload max filesize: " . ini_get('upload_max_filesize') . "</p>";
// Post max size
echo "<p>Post max size: " . ini_get('post_max_size') . "</p>";

echo "<h2>Recommendations</h2>";
echo "<ol>";
echo "<li>Check the error log in your cPanel (Error Log in cPanel or /home/username/logs/error_log)</li>";
echo "<li>Make sure your database credentials in includes/config/database.php are correct for the cPanel environment</li>";
echo "<li>Verify that all required tables exist in your database</li>";
echo "<li>Check file permissions - files should be 644 and directories 755</li>";
echo "<li>If using .htaccess, make sure it's properly configured</li>";
echo "</ol>";
?>