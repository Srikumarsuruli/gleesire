<?php
// Include timezone configuration
require_once __DIR__ . '/timezone.php';

// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'lead_management');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Set MySQL timezone to India
if($conn) {
    mysqli_query($conn, "SET time_zone = '+05:30'");
}

// Check connection
if($conn === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if(mysqli_query($conn, $sql)){
    // Select the database
    mysqli_select_db($conn, DB_NAME);
} else {
    echo "ERROR: Could not create database " . mysqli_error($conn);
}
?>