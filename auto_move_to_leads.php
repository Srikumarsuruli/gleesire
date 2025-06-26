<?php
// Include database connection
require_once "config/database.php";

// Function to automatically move converted enquiries to leads
function moveConvertedEnquiriesToLeads() {
    global $conn;
    
    // Find all converted enquiries (status_id = 3) that are not yet in the converted_leads table
    $sql = "SELECT e.* FROM enquiries e 
            LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id 
            WHERE e.status_id = 3 AND cl.enquiry_id IS NULL";
    
    $result = mysqli_query($conn, $sql);
    
    if(mysqli_num_rows($result) > 0) {
        while($enquiry = mysqli_fetch_assoc($result)) {
            // Generate enquiry number
            $enquiry_number = 'GH ' . sprintf('%04d', rand(1, 9999));
            
            // Insert into converted_leads table
            $insert_sql = "INSERT INTO converted_leads (enquiry_id, enquiry_number, travel_start_date, travel_end_date, booking_confirmed) 
                          VALUES (?, ?, NULL, NULL, 0)";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "is", $enquiry['id'], $enquiry_number);
            mysqli_stmt_execute($insert_stmt);
        }
        return true;
    }
    return false;
}

// Call the function
moveConvertedEnquiriesToLeads();
?>