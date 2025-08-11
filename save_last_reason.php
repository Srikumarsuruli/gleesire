<?php
$conn = mysqli_connect('localhost', 'root', '', 'lead_management');

if(!$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}

$enquiry_id = $_POST['enquiry_id'];
$last_reason = $_POST['last_reason'];

// Add column if not exists
mysqli_query($conn, "ALTER TABLE lead_status_map ADD COLUMN last_reason VARCHAR(100) NULL");

// Check if record exists
$check = mysqli_query($conn, "SELECT id FROM lead_status_map WHERE enquiry_id = $enquiry_id");

if(mysqli_num_rows($check) > 0) {
    $result = mysqli_query($conn, "UPDATE lead_status_map SET last_reason = '$last_reason' WHERE enquiry_id = $enquiry_id");
} else {
    $result = mysqli_query($conn, "INSERT INTO lead_status_map (enquiry_id, last_reason) VALUES ($enquiry_id, '$last_reason')");
}

echo $result ? "saved" : "error: " . mysqli_error($conn);
?>