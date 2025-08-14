<?php
session_start();
$_SESSION["loggedin"] = true; // Bypass login check for testing

require_once "config/database.php";

// Find the enquiry ID for lead number containing '0044'
$sql = "SELECT id FROM enquiries WHERE lead_number LIKE '%0044%' LIMIT 1";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

if($row) {
    $enquiry_id = $row['id'];
    echo "Testing get_record_details.php with ID: $enquiry_id\n\n";
    
    // Simulate the get_record_details.php logic
    $_GET['id'] = $enquiry_id;
    $_GET['type'] = 'lead';
    
    include 'get_record_details.php';
} else {
    echo "No enquiry found with lead number containing '0044'";
}
?>