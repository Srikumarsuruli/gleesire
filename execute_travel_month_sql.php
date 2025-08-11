<?php
// Include database connection
require_once "config/database.php";

// Add travel_month column if it doesn't exist
$alter_sql = "ALTER TABLE converted_leads ADD COLUMN travel_month VARCHAR(20) NULL";
try {
    mysqli_query($conn, $alter_sql);
    echo "Column added or already exists.<br>";
} catch (Exception $e) {
    echo "Column already exists or error: " . $e->getMessage() . "<br>";
}

// Update a specific record for testing
$update_sql = "UPDATE converted_leads SET travel_month = 'January' WHERE enquiry_id = 56";
if(mysqli_query($conn, $update_sql)) {
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