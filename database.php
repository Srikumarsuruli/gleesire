<?php
// Include timezone configuration
require_once __DIR__ . '/timezone.php';


// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'gleesire_leads_user');
define('DB_PASSWORD', '{*7(hNG}aV&C8{lc');
define('DB_NAME', 'gleesire_leads');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

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