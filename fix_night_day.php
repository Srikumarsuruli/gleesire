<?php
// Include database connection
require_once "includes/config/database.php";

// Check if the night_day column exists
$check_column_sql = "SHOW COLUMNS FROM converted_leads LIKE 'night_day'";
$result = mysqli_query($conn, $check_column_sql);

if (mysqli_num_rows($result) == 0) {
    // Column doesn't exist, add it
    $alter_table_sql = "ALTER TABLE converted_leads ADD COLUMN night_day VARCHAR(20) NULL";
    if(mysqli_query($conn, $alter_table_sql)) {
        echo "Column 'night_day' added successfully to converted_leads table.";
    } else {
        echo "Error adding column: " . mysqli_error($conn);
    }
} else {
    echo "Column 'night_day' already exists in converted_leads table.";
}

// Close connection
mysqli_close($conn);
?>