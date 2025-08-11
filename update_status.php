<?php
session_start();
require_once "config/database.php";

// Enable error logging
error_log("Status update attempt - POST data: " . print_r($_POST, true));

if(!isset($_SESSION["id"])) {
    error_log("Session not set, redirecting to view_enquiries.php");
    header("location: view_enquiries.php");
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id']) && isset($_POST['status_id'])) {
    $enquiry_id = intval($_POST['id']);
    $status_id = intval($_POST['status_id']);
    
    error_log("Processing update - Enquiry ID: $enquiry_id, Status ID: $status_id");
    
    if($enquiry_id > 0 && $status_id > 0) {
        $sql = "UPDATE enquiries SET status_id = ?, last_updated = NOW() WHERE id = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $status_id, $enquiry_id);
            
            if(mysqli_stmt_execute($stmt)) {
                $affected_rows = mysqli_stmt_affected_rows($stmt);
                error_log("Update successful - Affected rows: $affected_rows");
                
                // Check if status is "Converted"
                $status_check_sql = "SELECT name FROM lead_status WHERE id = ?";
                if($status_stmt = mysqli_prepare($conn, $status_check_sql)) {
                    mysqli_stmt_bind_param($status_stmt, "i", $status_id);
                    mysqli_stmt_execute($status_stmt);
                    $status_result = mysqli_stmt_get_result($status_stmt);
                    $status_row = mysqli_fetch_assoc($status_result);
                    
                    if($status_row && strtolower($status_row['name']) == 'converted') {
                        header("location: edit_enquiry.php?id=$enquiry_id&converted=1");
                        exit;
                    }
                    mysqli_stmt_close($status_stmt);
                }
            } else {
                error_log("Update failed - MySQL error: " . mysqli_error($conn));
            }
            mysqli_stmt_close($stmt);
        } else {
            error_log("Prepare failed - MySQL error: " . mysqli_error($conn));
        }
    } else {
        error_log("Invalid IDs - Enquiry ID: $enquiry_id, Status ID: $status_id");
    }
} else {
    error_log("Invalid request method or missing parameters");
}

// Force cache refresh with timestamp
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header("location: view_enquiries.php?updated=" . time());
exit;
?>