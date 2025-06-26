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

// Check if enquiry ID and status are provided
if(isset($_POST["id"]) && isset($_POST["status_id"])) {
    $enquiry_id = $_POST["id"];
    $status_id = $_POST["status_id"];
    
    // Update the status
    $update_sql = "UPDATE enquiries SET status_id = ?, last_updated = NOW() WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "ii", $status_id, $enquiry_id);
    
    if(mysqli_stmt_execute($update_stmt)) {
        // If status is converted (3), add to converted_leads table
        if($status_id == 3) {
            // Check if already in converted_leads
            $check_sql = "SELECT * FROM converted_leads WHERE enquiry_id = ?";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "i", $enquiry_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if(mysqli_num_rows($check_result) == 0) {
                // Get enquiry details
                $enquiry_sql = "SELECT lead_number FROM enquiries WHERE id = ?";
                $enquiry_stmt = mysqli_prepare($conn, $enquiry_sql);
                mysqli_stmt_bind_param($enquiry_stmt, "i", $enquiry_id);
                mysqli_stmt_execute($enquiry_stmt);
                $enquiry_result = mysqli_stmt_get_result($enquiry_stmt);
                $enquiry = mysqli_fetch_assoc($enquiry_result);
                
                // Generate enquiry number
                $enquiry_number = 'GH ' . sprintf('%04d', rand(1, 9999));
                
                // Insert into converted_leads
                $insert_sql = "INSERT INTO converted_leads (enquiry_id, enquiry_number, travel_start_date, travel_end_date, booking_confirmed) 
                              VALUES (?, ?, NULL, NULL, 0)";
                $insert_stmt = mysqli_prepare($conn, $insert_sql);
                mysqli_stmt_bind_param($insert_stmt, "is", $enquiry_id, $enquiry_number);
                mysqli_stmt_execute($insert_stmt);
            }
        }
        
        // Return success response
        $response = array(
            'success' => true,
            'message' => 'Status updated successfully'
        );
    } else {
        // Return error response
        $response = array(
            'success' => false,
            'message' => 'Failed to update status'
        );
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    // Return error response for missing parameters
    $response = array(
        'success' => false,
        'message' => 'Missing required parameters'
    );
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
}

// Close connection
mysqli_close($conn);
?>