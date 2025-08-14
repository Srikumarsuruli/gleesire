<?php
// Test script to verify master search fixes
require_once "config/database.php";

echo "<h2>Testing Master Search Fixes</h2>";

// Test 1: Check if night_day column exists
echo "<h3>Test 1: Checking night_day column</h3>";
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM converted_leads LIKE 'night_day'");
if(mysqli_num_rows($check_column) > 0) {
    echo "✓ night_day column exists<br>";
} else {
    echo "✗ night_day column missing<br>";
}

// Test 2: Check travel_month column type
echo "<h3>Test 2: Checking travel_month column type</h3>";
$check_travel_month = mysqli_query($conn, "SHOW COLUMNS FROM converted_leads LIKE 'travel_month'");
if($check_travel_month && $row = mysqli_fetch_assoc($check_travel_month)) {
    echo "travel_month column type: " . $row['Type'] . "<br>";
    if(strpos(strtolower($row['Type']), 'varchar') !== false) {
        echo "✓ travel_month is VARCHAR (correct)<br>";
    } else {
        echo "⚠ travel_month is not VARCHAR<br>";
    }
}

// Test 3: Test the get_record_details.php functionality
echo "<h3>Test 3: Testing get_record_details.php</h3>";
$test_enquiry = mysqli_query($conn, "SELECT e.id FROM enquiries e JOIN converted_leads cl ON e.id = cl.enquiry_id LIMIT 1");
if($test_enquiry && $test_row = mysqli_fetch_assoc($test_enquiry)) {
    $enquiry_id = $test_row['id'];
    echo "Testing with enquiry ID: $enquiry_id<br>";
    
    // Simulate the get_record_details.php query
    $lead_sql = "SELECT cl.*, d.name as destination_name, u.full_name as file_manager_name, nd.name as night_day_name 
                FROM converted_leads cl
                LEFT JOIN destinations d ON cl.destination_id = d.id
                LEFT JOIN users u ON cl.file_manager_id = u.id
                LEFT JOIN night_day nd ON cl.night_day = nd.id
                WHERE cl.enquiry_id = $enquiry_id";
    
    $lead_result = mysqli_query($conn, $lead_sql);
    if($lead_result && $lead_row = mysqli_fetch_assoc($lead_result)) {
        echo "✓ Query executed successfully<br>";
        echo "Night/Day value: " . ($lead_row['night_day'] ?: 'NULL') . "<br>";
        echo "Night/Day name: " . ($lead_row['night_day_name'] ?: 'NULL') . "<br>";
        echo "Travel Month: " . ($lead_row['travel_month'] ?: 'NULL') . "<br>";
    } else {
        echo "✗ Query failed or no results<br>";
    }
} else {
    echo "⚠ No test data available<br>";
}

// Test 4: Show sample data
echo "<h3>Test 4: Sample converted_leads data</h3>";
$sample_query = "SELECT cl.enquiry_id, cl.night_day, cl.travel_month, e.lead_number, e.customer_name 
                 FROM converted_leads cl 
                 JOIN enquiries e ON cl.enquiry_id = e.id 
                 LIMIT 5";
$sample_result = mysqli_query($conn, $sample_query);

if($sample_result && mysqli_num_rows($sample_result) > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Lead Number</th><th>Customer</th><th>Night/Day</th><th>Travel Month</th></tr>";
    while($row = mysqli_fetch_assoc($sample_result)) {
        echo "<tr>";
        echo "<td>" . $row['lead_number'] . "</td>";
        echo "<td>" . $row['customer_name'] . "</td>";
        echo "<td>" . ($row['night_day'] ?: 'NULL') . "</td>";
        echo "<td>" . ($row['travel_month'] ?: 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No sample data found<br>";
}

echo "<h3>Summary</h3>";
echo "<p>If all tests pass, the master search should now correctly display Night/Day and Travel Month fields.</p>";

mysqli_close($conn);
?>