<?php
function createLeadAssignmentNotification($enquiry_id, $file_manager_id, $conn) {
    // Get enquiry details
    $enquiry_sql = "SELECT e.*, cl.enquiry_number 
                    FROM enquiries e 
                    LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id 
                    WHERE e.id = ?";
    
    if($stmt = mysqli_prepare($conn, $enquiry_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $enquiry_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if($enquiry = mysqli_fetch_assoc($result)) {
            $enquiry_number = $enquiry['enquiry_number'] ?: $enquiry['lead_number'];
            $lead_number = $enquiry['lead_number'];
            
            $message = "New lead is assigned to you with enquiry number {$enquiry_number} and lead number {$lead_number}";
            
            // Insert notification
            $notification_sql = "INSERT INTO notifications (user_id, enquiry_id, enquiry_number, lead_number, message, is_read, created_at) 
                                VALUES (?, ?, ?, ?, ?, 0, NOW())";
            
            if($notification_stmt = mysqli_prepare($conn, $notification_sql)) {
                mysqli_stmt_bind_param($notification_stmt, "iisss", 
                    $file_manager_id, $enquiry_id, $enquiry_number, $lead_number, $message);
                
                if(mysqli_stmt_execute($notification_stmt)) {
                    return true;
                }
                mysqli_stmt_close($notification_stmt);
            }
        }
        mysqli_stmt_close($stmt);
    }
    return false;
}
?>