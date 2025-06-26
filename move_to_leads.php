<?php
// Initialize the session
session_start();

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Include database connection
require_once "config/database.php";

// Check if enquiry ID is provided
if(isset($_GET["id"]) && !empty($_GET["id"])) {
    $enquiry_id = $_GET["id"];
    
    // First, check if the status is "converted" (status_id = 3)
    $check_sql = "SELECT * FROM enquiries WHERE id = ? AND status_id = 3";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "i", $enquiry_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if(mysqli_num_rows($check_result) > 0) {
        $enquiry = mysqli_fetch_assoc($check_result);
        
        // Check if this enquiry is already in converted_leads table
        $exists_sql = "SELECT * FROM converted_leads WHERE enquiry_id = ?";
        $exists_stmt = mysqli_prepare($conn, $exists_sql);
        mysqli_stmt_bind_param($exists_stmt, "i", $enquiry_id);
        mysqli_stmt_execute($exists_stmt);
        $exists_result = mysqli_stmt_get_result($exists_stmt);
        
        if(mysqli_num_rows($exists_result) == 0) {
            // Insert into converted_leads table
            $insert_sql = "INSERT INTO converted_leads (enquiry_id, enquiry_number, travel_start_date, travel_end_date, booking_confirmed) 
                          VALUES (?, ?, NULL, NULL, 0)";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "is", $enquiry_id, $enquiry['lead_number']);
            
            if(mysqli_stmt_execute($insert_stmt)) {
                // Redirect back to view_enquiries.php with success message
                header("location: view_enquiries.php?moved=1");
                exit;
            } else {
                // Redirect back with error
                header("location: view_enquiries.php?error=1");
                exit;
            }
        } else {
            // Already moved to leads
            header("location: view_enquiries.php?already=1");
            exit;
        }
    } else {
        // Not a converted lead
        header("location: view_enquiries.php?notconverted=1");
        exit;
    }
} else {
    // No ID provided
    header("location: view_enquiries.php");
    exit;
}

// Close connection
mysqli_close($conn);
?>