<?php
// Include database connection
require_once "includes/config/database.php";

// Check if the travel_month column exists
$check_column_sql = "SHOW COLUMNS FROM converted_leads LIKE 'travel_month'";
$result = mysqli_query($conn, $check_column_sql);

if (mysqli_num_rows($result) == 0) {
    // Column doesn't exist, add it
    $alter_table_sql = "ALTER TABLE converted_leads ADD COLUMN travel_month VARCHAR(20) NULL";
    if(mysqli_query($conn, $alter_table_sql)) {
        echo "Column 'travel_month' added successfully to converted_leads table.<br>";
    } else {
        echo "Error adding column: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "Column 'travel_month' already exists in converted_leads table.<br>";
}

// Test updating a record
$test_sql = "UPDATE converted_leads SET travel_month = 'January' WHERE enquiry_id = 56";
if(mysqli_query($conn, $test_sql)) {
    echo "Successfully updated travel_month for enquiry_id 56.<br>";
} else {
    echo "Error updating travel_month: " . mysqli_error($conn) . "<br>";
}

// Check if the update worked
$check_sql = "SELECT travel_month FROM converted_leads WHERE enquiry_id = 56";
$check_result = mysqli_query($conn, $check_sql);
if($check_result && mysqli_num_rows($check_result) > 0) {
    $row = mysqli_fetch_assoc($check_result);
    echo "Current travel_month value for enquiry_id 56: " . $row['travel_month'] . "<br>";
} else {
    echo "No record found for enquiry_id 56 or error checking value.<br>";
}

echo "<br>Done! You can now <a href='edit_enquiry.php?id=56'>go back to edit_enquiry.php</a>";

// Close connection
mysqli_close($conn);
?>