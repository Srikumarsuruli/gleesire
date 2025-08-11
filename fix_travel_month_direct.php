<?php
// Include database connection
require_once "config/database.php";

// Add travel_month column to converted_leads table if it doesn't exist
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

// Now update the edit_enquiry.php file
$file_path = "edit_enquiry.php";
$file_content = file_get_contents($file_path);

// Replace both occurrences of $travel_month = NULL;
$file_content = str_replace(
    '$travel_month = NULL;',
    '$travel_month = !empty($_POST["travel_month"]) ? trim($_POST["travel_month"]) : NULL;',
    $file_content
);

// Write the updated content back to the file
if (file_put_contents($file_path, $file_content)) {
    echo "Successfully updated edit_enquiry.php to properly handle travel_month.<br>";
} else {
    echo "Error updating edit_enquiry.php file.<br>";
}

echo "<br>Done! You can now <a href='edit_enquiry.php?id=56'>go back to edit_enquiry.php</a>";
?>