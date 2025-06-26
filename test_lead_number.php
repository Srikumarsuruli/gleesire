<?php
// Include database connection
require_once "includes/config/database.php";
require_once "includes/lead_number_generator.php";

// Test the lead number generator
echo "<h2>Lead Number Test</h2>";

// Generate a lead number
$lead_number = generateLeadNumber($conn);
echo "Generated Lead Number: $lead_number<br>";

// Insert a test record to see if the next number increments
$sql = "INSERT INTO enquiries (lead_number, received_datetime, attended_by, department_id, source_id, customer_name, mobile_number, status_id) 
        VALUES ('$lead_number', NOW(), 1, 1, 1, 'Test Customer', '1234567890', 1)";

if (mysqli_query($conn, $sql)) {
    echo "Test record inserted with lead number: $lead_number<br>";
    
    // Generate another lead number to see if it increments
    $next_lead_number = generateLeadNumber($conn);
    echo "Next Lead Number: $next_lead_number<br>";
    
    // Delete the test record
    $delete_sql = "DELETE FROM enquiries WHERE lead_number = '$lead_number'";
    if (mysqli_query($conn, $delete_sql)) {
        echo "Test record deleted successfully.";
    } else {
        echo "Error deleting test record: " . mysqli_error($conn);
    }
} else {
    echo "Error inserting test record: " . mysqli_error($conn);
}
?>