<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Include database connection
require_once "config/database.php";

// Check if ID is provided
if(isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // First check if it's in enquiries table
    $sql = "SELECT 
            'enquiry' as type,
            e.id,
            e.lead_number,
            e.customer_name,
            e.mobile_number,
            e.email,
            e.received_datetime,
            'view_enquiries.php' as redirect_url
            FROM enquiries e
            WHERE e.id = ?";
            
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if(mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            
            if(mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                header("Location: view_enquiries.php?highlight=" . $id);
                exit;
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    // Check if it's in converted_leads table
    $sql = "SELECT 
            'lead' as type,
            e.id,
            cl.enquiry_number,
            e.lead_number,
            e.customer_name,
            e.mobile_number,
            e.email,
            e.received_datetime,
            cl.booking_confirmed
            FROM converted_leads cl
            JOIN enquiries e ON cl.enquiry_id = e.id
            WHERE e.id = ?";
            
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if(mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            
            if(mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                
                if($row['booking_confirmed'] == 1) {
                    header("Location: booking_confirmed.php?highlight=" . $id);
                } else {
                    header("Location: view_leads.php?highlight=" . $id);
                }
                exit;
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// If we get here, the ID wasn't found or there was an error
header("Location: index.php");
exit;
?>