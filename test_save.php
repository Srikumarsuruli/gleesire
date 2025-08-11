<?php
session_start();
require_once "config/database.php";

$enquiry_id = intval($_POST['enquiry_id']);
$last_reason = mysqli_real_escape_string($conn, $_POST['last_reason']);

// Ensure table exists with correct structure
$create_table = "CREATE TABLE IF NOT EXISTS lead_status_map (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enquiry_id INT NOT NULL,
    status_name VARCHAR(100),
    last_reason VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_enquiry (enquiry_id)
)";
mysqli_query($conn, $create_table);

// Check if record exists
$check = mysqli_query($conn, "SELECT id FROM lead_status_map WHERE enquiry_id = $enquiry_id");

if(mysqli_num_rows($check) > 0) {
    $sql = "UPDATE lead_status_map SET last_reason = '$last_reason' WHERE enquiry_id = $enquiry_id";
} else {
    $sql = "INSERT INTO lead_status_map (enquiry_id, status_name, last_reason) VALUES ($enquiry_id, '', '$last_reason')";
}

if(mysqli_query($conn, $sql)) {
    echo "saved";
} else {
    echo "error: " . mysqli_error($conn);
}
?>