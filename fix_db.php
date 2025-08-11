<?php
// Basic error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once "includes/config.php";

// Check connection
if (!$conn) {
    die("Connection failed");
}

// Try to alter the table
$sql = "ALTER TABLE converted_leads MODIFY COLUMN travel_month VARCHAR(20)";
$result = mysqli_query($conn, $sql);

if ($result) {
    echo "Success: travel_month column modified to VARCHAR(20)";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>