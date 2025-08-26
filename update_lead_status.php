<?php
session_start();
require_once "config/database.php";

if(!isset($_SESSION["id"])) {
    header("location: view_leads.php");
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enquiry_id']) && isset($_POST['status_name'])) {
    $enquiry_id = intval($_POST['enquiry_id']);
    $status_name = $_POST['status_name'];
    
    if($enquiry_id > 0 && !empty($status_name)) {
        // Check if record exists
        $check_sql = "SELECT id FROM lead_status_map WHERE enquiry_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "i", $enquiry_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        // Add updated_at column if it doesn't exist
        $add_column_sql = "ALTER TABLE lead_status_map ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        @mysqli_query($conn, $add_column_sql); // Suppress error if column exists
        
        if(mysqli_num_rows($check_result) > 0) {
            // Update existing record
            $sql = "UPDATE lead_status_map SET status_name = ?, updated_at = NOW() WHERE enquiry_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $status_name, $enquiry_id);
        } else {
            // Insert new record
            $sql = "INSERT INTO lead_status_map (enquiry_id, status_name, updated_at) VALUES (?, ?, NOW())";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "is", $enquiry_id, $status_name);
        }
        
        if(mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            mysqli_stmt_close($check_stmt);
            
            // Check if status is "Closed – Booked" and redirect to cost file
            if($status_name === 'Closed – Booked') {
                echo json_encode(['success' => true, 'redirect' => "new_cost_file.php?id=$enquiry_id"]);
                exit;
            }
            
            echo json_encode(['success' => true, 'message' => 'Lead status updated successfully']);
            exit;
        } else {
            mysqli_stmt_close($stmt);
            mysqli_stmt_close($check_stmt);
            echo json_encode(['success' => false, 'message' => 'Error updating lead status']);
            exit;
        }
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit;
?>