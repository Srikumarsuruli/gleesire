<?php
// Include database connection
require_once "includes/config/database.php";

// Add night_day column to enquiries table if it doesn't exist
$check_column_sql = "SHOW COLUMNS FROM enquiries LIKE 'night_day'";
$check_column_result = mysqli_query($conn, $check_column_sql);

if (mysqli_num_rows($check_column_result) == 0) {
    $alter_table_sql = "ALTER TABLE enquiries ADD COLUMN night_day VARCHAR(20) DEFAULT NULL";
    if (mysqli_query($conn, $alter_table_sql)) {
        echo "Column 'night_day' added successfully to enquiries table.";
    } else {
        echo "Error adding column: " . mysqli_error($conn);
    }
} else {
    echo "Column 'night_day' already exists in enquiries table.";
}

// Close connection
mysqli_close($conn);
?>