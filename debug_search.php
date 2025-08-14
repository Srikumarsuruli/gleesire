<?php
require_once "config/database.php";

// Test query to find the enquiry with lead number containing '0044'
$sql = "SELECT e.id, e.lead_number, e.customer_name FROM enquiries e WHERE e.lead_number LIKE '%0044%'";
$result = mysqli_query($conn, $sql);

echo "Enquiries matching '0044':\n";
while($row = mysqli_fetch_assoc($result)) {
    echo "ID: " . $row['id'] . ", Lead Number: " . $row['lead_number'] . ", Customer: " . $row['customer_name'] . "\n";
    
    // Check if this enquiry has converted lead data
    $lead_sql = "SELECT * FROM converted_leads WHERE enquiry_id = " . $row['id'];
    $lead_result = mysqli_query($conn, $lead_sql);
    
    if(mysqli_num_rows($lead_result) > 0) {
        echo "  - Has converted lead data\n";
        $lead_row = mysqli_fetch_assoc($lead_result);
        echo "  - Enquiry Number: " . $lead_row['enquiry_number'] . "\n";
        echo "  - Customer Location: " . ($lead_row['customer_location'] ?? 'N/A') . "\n";
    } else {
        echo "  - No converted lead data found\n";
    }
    echo "\n";
}
?>