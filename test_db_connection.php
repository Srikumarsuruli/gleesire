<?php
// Simple database connection test
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h3>Database Connection Test</h3>";

try {
    // Test local config
    if (file_exists("config/database.php")) {
        echo "<p>Local database config found</p>";
        require_once "config/database.php";
        echo "<p>Local database connection: SUCCESS</p>";
        echo "<p>Database name: " . mysqli_get_server_info($conn) . "</p>";
    }
} catch (Exception $e) {
    echo "<p>Local database error: " . $e->getMessage() . "</p>";
}

try {
    // Test cPanel config
    if (file_exists("config/database_cpanel.php")) {
        echo "<p>cPanel database config found</p>";
        // Reset connection
        if (isset($conn)) mysqli_close($conn);
        require_once "config/database_cpanel.php";
        echo "<p>cPanel database connection: SUCCESS</p>";
        echo "<p>Database name: " . mysqli_get_server_info($conn) . "</p>";
    } else {
        echo "<p>cPanel database config NOT found</p>";
    }
} catch (Exception $e) {
    echo "<p>cPanel database error: " . $e->getMessage() . "</p>";
}

// Test table existence
if (isset($conn)) {
    $tables = ['enquiries', 'lead_status_map', 'users'];
    foreach ($tables as $table) {
        $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
        if (mysqli_num_rows($result) > 0) {
            echo "<p>Table '$table': EXISTS</p>";
        } else {
            echo "<p>Table '$table': NOT FOUND</p>";
        }
    }
}

echo "<p><a href='view_leads.php'>Back to Leads</a></p>";
?>