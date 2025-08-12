<?php
require_once "includes/config.php";

// Create lead_status_change_log table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS lead_status_change_log (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    enquiry_id INT(11) NOT NULL,
    old_status VARCHAR(100),
    new_status VARCHAR(100) NOT NULL,
    changed_by INT(11) NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_table_sql);

// Add last_reason column if it doesn't exist
$check_column_sql = "SHOW COLUMNS FROM lead_status_map LIKE 'last_reason'";
$column_result = mysqli_query($conn, $check_column_sql);
if (mysqli_num_rows($column_result) == 0) {
    $add_column_sql = "ALTER TABLE lead_status_map ADD COLUMN last_reason VARCHAR(100) NULL";
    mysqli_query($conn, $add_column_sql);
}

// Check if user is logged in
if(!isset($_SESSION["id"])) {
    header("location: view_leads.php?error=2");
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $enquiry_id = intval($_POST['enquiry_id']);
    $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : null;
    $last_reason = isset($_POST['last_reason']) ? mysqli_real_escape_string($conn, $_POST['last_reason']) : null;
    
    if($enquiry_id > 0) {
        // Handle case where only last_reason is being updated
        if(!empty($last_reason) && empty($status)) {
            // Check if record exists
            $check_sql = "SELECT id FROM lead_status_map WHERE enquiry_id = ?";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "i", $enquiry_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if(mysqli_num_rows($check_result) > 0) {
                // Update existing record
                $update_sql = "UPDATE lead_status_map SET last_reason = ? WHERE enquiry_id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "si", $last_reason, $enquiry_id);
                mysqli_stmt_execute($update_stmt);
            } else {
                // Insert new record with just last_reason
                $insert_sql = "INSERT INTO lead_status_map (enquiry_id, last_reason) VALUES (?, ?)";
                $insert_stmt = mysqli_prepare($conn, $insert_sql);
                mysqli_stmt_bind_param($insert_stmt, "is", $enquiry_id, $last_reason);
                mysqli_stmt_execute($insert_stmt);
            }
            echo "success";
            exit;
        }
        // Handle case where status is being updated (with or without last_reason)
        else if(!empty($status)) {
            // Get old status for logging
            $old_status_sql = "SELECT status_name FROM lead_status_map WHERE enquiry_id = ?";
            $old_status_stmt = mysqli_prepare($conn, $old_status_sql);
            mysqli_stmt_bind_param($old_status_stmt, "i", $enquiry_id);
            mysqli_stmt_execute($old_status_stmt);
            $old_status_result = mysqli_stmt_get_result($old_status_stmt);
            $old_status = '';
            if($old_status_row = mysqli_fetch_assoc($old_status_result)) {
                $old_status = $old_status_row['status_name'];
            }
            
            // Get current IST datetime
            $current_ist_time = date('Y-m-d H:i:s');
            
            // Update or insert lead status
            $upsert_sql = "INSERT INTO lead_status_map (enquiry_id, status_name, last_reason, created_at) 
                          VALUES (?, ?, ?, ?) 
                          ON DUPLICATE KEY UPDATE 
                          status_name = VALUES(status_name), 
                          last_reason = VALUES(last_reason), 
                          created_at = VALUES(created_at)";
            $upsert_stmt = mysqli_prepare($conn, $upsert_sql);
            mysqli_stmt_bind_param($upsert_stmt, "isss", $enquiry_id, $status, $last_reason, $current_ist_time);
            
            if(mysqli_stmt_execute($upsert_stmt)) {
                // Log lead status change
                $log_sql = "INSERT INTO lead_status_change_log (enquiry_id, old_status, new_status, changed_by, changed_at) 
                           VALUES (?, ?, ?, ?, ?)";
                $log_stmt = mysqli_prepare($conn, $log_sql);
                mysqli_stmt_bind_param($log_stmt, "issis", $enquiry_id, $old_status, $status, $_SESSION["id"], $current_ist_time);
                mysqli_stmt_execute($log_stmt);
                
                header("location: view_leads.php?status_updated=1");
            } else {
                header("location: view_leads.php?error=1");
            }
        } else {
            echo "error: missing required data";
            exit;
        }
    } else {
        echo "error: invalid enquiry id";
        exit;
    }
} else {
    header("location: view_leads.php?error=2");
}
?>