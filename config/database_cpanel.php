<?php
// Database configuration for cPanel hosting
// Update these values with your actual cPanel database credentials

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'your_cpanel_db_username');  // Usually format: cpanel_username_dbuser
define('DB_PASSWORD', 'your_cpanel_db_password');
define('DB_NAME', 'your_cpanel_db_name');          // Usually format: cpanel_username_dbname

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($conn === false){
    // Log the error instead of displaying it
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Database connection failed. Please check your configuration.");
}

// Set charset to prevent encoding issues
mysqli_set_charset($conn, "utf8");
?>