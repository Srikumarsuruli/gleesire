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

// Check if enquiry ID is provided
if(isset($_POST["enquiry_id"])) {
    $enquiry_id = $_POST["enquiry_id"];
    
    // Update converted_lead to mark as booking confirmed
    $update_sql = "UPDATE converted_leads SET booking_confirmed = 1 WHERE enquiry_id = ?";
    if($update_stmt = mysqli_prepare($conn, $update_sql)) {
        mysqli_stmt_bind_param($update_stmt, "i", $enquiry_id);
        
        if(mysqli_stmt_execute($update_stmt)) {
            // Redirect to refresh the page
            header("location: view_leads.php?confirmed=1");
            exit;
        } else {
            header("location: view_leads.php?error=1");
            exit;
        }
        
        mysqli_stmt_close($update_stmt);
    } else {
        header("location: view_leads.php?error=1");
        exit;
    }
} else {
    header("location: view_leads.php?error=2");
    exit;
}

// Close connection
mysqli_close($conn);
?>