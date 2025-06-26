<?php
// Include database connection
require_once "config/database.php";

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Test Converted Lead Insert</h2>";

// Create a test enquiry
$lead_number = 'LGH-TEST-' . date('Ymd-His');
$customer_name = 'Test Customer';
$mobile_number = '1234567890';
$received_datetime = date('Y-m-d H:i:s');
$attended_by = 1; // Assuming user ID 1 exists
$department_id = 1; // Assuming department ID 1 exists
$source_id = 1; // Assuming source ID 1 exists
$status_id = 3; // Converted status

// Insert test enquiry
$sql = "INSERT INTO enquiries (lead_number, received_datetime, attended_by, department_id, source_id, 
        customer_name, mobile_number, status_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ssiissi", $lead_number, $received_datetime, $attended_by, 
                          $department_id, $source_id, $customer_name, $mobile_number, $status_id);
    
    if(mysqli_stmt_execute($stmt)) {
        $enquiry_id = mysqli_insert_id($conn);
        echo "<p>Test enquiry created with ID: $enquiry_id</p>";
        
        // Now insert into converted_leads
        $enquiry_number = 'GH-TEST-' . date('Ymd-His');
        $lead_type = 'Hot';
        
        // First try with minimal fields
        $sql = "INSERT INTO converted_leads (enquiry_id, enquiry_number, lead_type, booking_confirmed) 
                VALUES (?, ?, ?, 0)";
        
        if($stmt2 = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt2, "iss", $enquiry_id, $enquiry_number, $lead_type);
            
            if(mysqli_stmt_execute($stmt2)) {
                echo "<p>Successfully inserted into converted_leads table!</p>";
                
                // Verify the record was inserted
                $check_sql = "SELECT * FROM converted_leads WHERE enquiry_id = ?";
                $check_stmt = mysqli_prepare($conn, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "i", $enquiry_id);
                mysqli_stmt_execute($check_stmt);
                $result = mysqli_stmt_get_result($check_stmt);
                
                if(mysqli_num_rows($result) > 0) {
                    $row = mysqli_fetch_assoc($result);
                    echo "<h3>Inserted Record:</h3>";
                    echo "<pre>";
                    print_r($row);
                    echo "</pre>";
                } else {
                    echo "<p>Error: Record not found after insert!</p>";
                }
            } else {
                echo "<p>Error inserting into converted_leads: " . mysqli_error($conn) . "</p>";
            }
        } else {
            echo "<p>Error preparing statement: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p>Error creating test enquiry: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>Error preparing statement: " . mysqli_error($conn) . "</p>";
}

// Close connection
mysqli_close($conn);
?>