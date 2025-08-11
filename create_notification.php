<?php
function createNotification($user_id, $enquiry_id, $enquiry_number, $lead_number) {
    global $conn;
    
    $message = "You have new leads assigned from the enquiry number: $enquiry_number and lead number: $lead_number";
    
    $sql = "INSERT INTO notifications (user_id, enquiry_id, enquiry_number, lead_number, message) VALUES (?, ?, ?, ?, ?)";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "iisss", $user_id, $enquiry_id, $enquiry_number, $lead_number, $message);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
?>