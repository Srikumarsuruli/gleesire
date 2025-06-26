<?php
// Include database connection
require_once "includes/config/database.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log all POST data
file_put_contents('debug_lead_status.txt', date('Y-m-d H:i:s') . " - POST: " . print_r($_POST, true) . "\n", FILE_APPEND);

// Check if required parameters are set
if (isset($_POST['enquiry_id']) && isset($_POST['status'])) {
    $enquiry_id = $_POST['enquiry_id'];
    $status = $_POST['status'];
    
    // Log the values
    file_put_contents('debug_lead_status.txt', "Processing: ID=$enquiry_id, Status=$status\n", FILE_APPEND);
    
    // Direct database query - check if record exists
    $check_query = "SELECT id FROM lead_status_map WHERE enquiry_id = $enquiry_id";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Update existing record
        $query = "UPDATE lead_status_map SET status_name = '$status' WHERE enquiry_id = $enquiry_id";
        file_put_contents('debug_lead_status.txt', "Updating: $query\n", FILE_APPEND);
    } else {
        // Insert new record
        $query = "INSERT INTO lead_status_map (enquiry_id, status_name) VALUES ($enquiry_id, '$status')";
        file_put_contents('debug_lead_status.txt', "Inserting: $query\n", FILE_APPEND);
    }
    
    // Execute query
    $result = mysqli_query($conn, $query);
    
    // Log result
    file_put_contents('debug_lead_status.txt', "Result: " . ($result ? "Success" : "Failed: " . mysqli_error($conn)) . "\n", FILE_APPEND);
    
    // Return result
    echo $result ? "success" : "error: " . mysqli_error($conn);
} else {
    file_put_contents('debug_lead_status.txt', "Missing parameters\n", FILE_APPEND);
    echo "error: Missing parameters";
}
?>