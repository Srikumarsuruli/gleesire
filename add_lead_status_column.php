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

// Check if lead_status column exists in converted_leads table
$check_column_sql = "SHOW COLUMNS FROM converted_leads LIKE 'lead_status'";
$check_column_result = mysqli_query($conn, $check_column_sql);

if(mysqli_num_rows($check_column_result) == 0) {
    // Column doesn't exist, add it
    $add_column_sql = "ALTER TABLE converted_leads ADD COLUMN lead_status VARCHAR(100) AFTER enquiry_id";
    if(mysqli_query($conn, $add_column_sql)) {
        echo "Lead status column added successfully to converted_leads table.";
        
        // Initialize the lead_status column with values from lead_status table
        $update_sql = "UPDATE converted_leads cl 
                      JOIN enquiries e ON cl.enquiry_id = e.id 
                      JOIN lead_status ls ON e.status_id = ls.id 
                      SET cl.lead_status = ls.name";
        
        if(mysqli_query($conn, $update_sql)) {
            echo "<br>Lead status values initialized successfully.";
        } else {
            echo "<br>Error initializing lead status values: " . mysqli_error($conn);
        }
    } else {
        echo "Error adding lead_status column: " . mysqli_error($conn);
    }
} else {
    echo "Lead status column already exists in converted_leads table.";
}

// Close connection
mysqli_close($conn);
?>