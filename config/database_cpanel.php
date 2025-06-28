<?php
// Database configuration for cPanel hosting
// Update these values with your actual cPanel database credentials

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'M45t3rM!nd');
define('DB_NAME', 'lead_management');
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