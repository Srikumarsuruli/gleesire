<?php
// Include database connection
require_once "config/database.php";

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Debug Converted Leads</h2>";

// Check if the converted_leads table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'converted_leads'");
if(mysqli_num_rows($table_check) == 0) {
    echo "<p>Error: The converted_leads table does not exist!</p>";
} else {
    echo "<p>The converted_leads table exists.</p>";
    
    // Check the structure of the converted_leads table
    $structure = mysqli_query($conn, "DESCRIBE converted_leads");
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while($row = mysqli_fetch_assoc($structure)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if there are any records in the converted_leads table
    $count = mysqli_query($conn, "SELECT COUNT(*) as total FROM converted_leads");
    $count_row = mysqli_fetch_assoc($count);
    echo "<p>Total records in converted_leads: " . $count_row['total'] . "</p>";
    
    // Check if there are any enquiries with status_id = 3
    $converted = mysqli_query($conn, "SELECT COUNT(*) as total FROM enquiries WHERE status_id = 3");
    $converted_row = mysqli_fetch_assoc($converted);
    echo "<p>Total enquiries with status_id = 3 (Converted): " . $converted_row['total'] . "</p>";
    
    // List all enquiries with status_id = 3
    $converted_list = mysqli_query($conn, "SELECT e.*, ls.name as status_name FROM enquiries e JOIN lead_status ls ON e.status_id = ls.id WHERE e.status_id = 3");
    if(mysqli_num_rows($converted_list) > 0) {
        echo "<h3>Enquiries with status 'Converted':</h3>";
        echo "<table border='1'><tr><th>ID</th><th>Lead Number</th><th>Customer Name</th><th>Status</th></tr>";
        while($row = mysqli_fetch_assoc($converted_list)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['lead_number'] . "</td>";
            echo "<td>" . $row['customer_name'] . "</td>";
            echo "<td>" . $row['status_name'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test inserting a record into converted_leads
    echo "<h3>Test Insert:</h3>";
    
    // First check if there's at least one enquiry with status_id = 3
    $test_enquiry = mysqli_query($conn, "SELECT id FROM enquiries WHERE status_id = 3 LIMIT 1");
    if(mysqli_num_rows($test_enquiry) > 0) {
        $enquiry = mysqli_fetch_assoc($test_enquiry);
        $enquiry_id = $enquiry['id'];
        
        // Check if this enquiry is already in converted_leads
        $exists = mysqli_query($conn, "SELECT * FROM converted_leads WHERE enquiry_id = $enquiry_id");
        if(mysqli_num_rows($exists) == 0) {
            // Generate enquiry number
            $enquiry_number = 'GH ' . sprintf('%04d', rand(1, 9999));
            
            // Try to insert
            $insert = "INSERT INTO converted_leads (enquiry_id, enquiry_number, lead_type, travel_start_date, travel_end_date, booking_confirmed) 
                      VALUES ($enquiry_id, '$enquiry_number', 'Hot', NULL, NULL, 0)";
            
            if(mysqli_query($conn, $insert)) {
                echo "<p>Test insert successful! Added enquiry ID $enquiry_id to converted_leads.</p>";
            } else {
                echo "<p>Error inserting test record: " . mysqli_error($conn) . "</p>";
            }
        } else {
            echo "<p>Enquiry ID $enquiry_id is already in converted_leads table.</p>";
        }
    } else {
        echo "<p>No enquiries with status_id = 3 found for testing.</p>";
    }
}

// Close connection
mysqli_close($conn);
?>