<?php
session_start();
require_once "config/database.php";

if(!isset($_SESSION["id"])) {
    header("location: view_leads.php");
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enquiry_id']) && isset($_POST['last_reason'])) {
    $enquiry_id = intval($_POST['enquiry_id']);
    $last_reason = $_POST['last_reason'];
    
    if($enquiry_id > 0 && !empty($last_reason)) {
        // Add last_reason_updated_at column if it doesn't exist
        $add_column_sql = "ALTER TABLE lead_status_map ADD COLUMN last_reason_updated_at TIMESTAMP NULL";
        @mysqli_query($conn, $add_column_sql); // Suppress error if column exists
        
        // Check if record exists
        $check_sql = "SELECT id FROM lead_status_map WHERE enquiry_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "i", $enquiry_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        // Get current IST datetime
        $current_ist_time = date('Y-m-d H:i:s');
        
        if(mysqli_num_rows($check_result) > 0) {
            // Update existing record
            $sql = "UPDATE lead_status_map SET last_reason = ?, last_reason_updated_at = ? WHERE enquiry_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssi", $last_reason, $current_ist_time, $enquiry_id);
        } else {
            // Insert new record with empty status_name
            $sql = "INSERT INTO lead_status_map (enquiry_id, status_name, last_reason, last_reason_updated_at) VALUES (?, '', ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iss", $enquiry_id, $last_reason, $current_ist_time);
        }
        
        if(mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            mysqli_stmt_close($check_stmt);
            echo json_encode(['success' => true, 'message' => 'Last reason updated successfully']);
            exit;
        } else {
            mysqli_stmt_close($stmt);
            mysqli_stmt_close($check_stmt);
            echo json_encode(['success' => false, 'message' => 'Error updating last reason']);
            exit;
        }
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit;
?>