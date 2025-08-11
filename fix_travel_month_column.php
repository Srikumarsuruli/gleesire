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
    // Column exists, check if it's the right type
    $row = mysqli_fetch_assoc($result);
    if ($row['Type'] != 'varchar(20)') {
        // Modify the column to the right type
        $alter_table_sql = "ALTER TABLE converted_leads MODIFY COLUMN travel_month VARCHAR(20) NULL";
        if(mysqli_query($conn, $alter_table_sql)) {
            echo "Column 'travel_month' modified successfully in converted_leads table.<br>";
        } else {
            echo "Error modifying column: " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "Column 'travel_month' already exists with the correct type in converted_leads table.<br>";
    }
}

echo "<br>Done! You can now <a href='edit_enquiry.php?id=56'>go back to edit_enquiry.php</a>";

// Close connection
mysqli_close($conn);
?>