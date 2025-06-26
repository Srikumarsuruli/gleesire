<?php
// Include database connection
require_once "includes/config/database.php";

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log all data
file_put_contents('direct_save_log.txt', date('Y-m-d H:i:s') . " - Script started\n", FILE_APPEND);
file_put_contents('direct_save_log.txt', date('Y-m-d H:i:s') . " - GET: " . print_r($_GET, true) . "\n", FILE_APPEND);

// Check if required parameters are set
if (isset($_GET['id']) && isset($_GET['status'])) {
    $enquiry_id = $_GET['id'];
    $status = $_GET['status'];
    
    // Log the values
    file_put_contents('direct_save_log.txt', "Processing: ID=$enquiry_id, Status=$status\n", FILE_APPEND);
    
    // Direct database query
    $query = "REPLACE INTO lead_status_map (enquiry_id, status_name) VALUES ($enquiry_id, '$status')";
    file_put_contents('direct_save_log.txt', "Query: $query\n", FILE_APPEND);
    
    // Execute query
    $result = mysqli_query($conn, $query);
    
    // Log result
    file_put_contents('direct_save_log.txt', "Result: " . ($result ? "Success" : "Failed: " . mysqli_error($conn)) . "\n", FILE_APPEND);
    
    // Redirect back to view_leads.php
    header("Location: view_leads.php?status_saved=1");
    exit;
} else {
    file_put_contents('direct_save_log.txt', "Missing parameters\n", FILE_APPEND);
    echo "Error: Missing parameters";
}
?>