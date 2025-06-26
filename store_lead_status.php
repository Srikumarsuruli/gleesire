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

// Create a table to store lead statuses if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS lead_status_map (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    enquiry_id INT(11) NOT NULL,
    status_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (enquiry_id)
)";

if(!mysqli_query($conn, $create_table_sql)) {
    die("Error creating table: " . mysqli_error($conn));
}

// Check if enquiry ID and status are provided
if(isset($_POST["id"]) && isset($_POST["status_id"])) {
    $enquiry_id = $_POST["id"];
    $status_name = $_POST["status_id"];
    
    // Check if entry exists
    $check_sql = "SELECT * FROM lead_status_map WHERE enquiry_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "i", $enquiry_id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    
    if(mysqli_num_rows($result) > 0) {
        // Update existing entry
        $update_sql = "UPDATE lead_status_map SET status_name = ? WHERE enquiry_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "si", $status_name, $enquiry_id);
        
        if(mysqli_stmt_execute($update_stmt)) {
            $response = array(
                'success' => true,
                'message' => 'Lead status updated successfully'
            );
        } else {
            $response = array(
                'success' => false,
                'message' => 'Failed to update lead status: ' . mysqli_error($conn)
            );
        }
    } else {
        // Insert new entry
        $insert_sql = "INSERT INTO lead_status_map (enquiry_id, status_name) VALUES (?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($insert_stmt, "is", $enquiry_id, $status_name);
        
        if(mysqli_stmt_execute($insert_stmt)) {
            $response = array(
                'success' => true,
                'message' => 'Lead status saved successfully'
            );
        } else {
            $response = array(
                'success' => false,
                'message' => 'Failed to save lead status: ' . mysqli_error($conn)
            );
        }
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