<?php
// Include database connection
require_once "includes/config/database.php";

// Add last_reason column to lead_status_map table if it doesn't exist
$alter_table_sql = "ALTER TABLE lead_status_map ADD COLUMN last_reason VARCHAR(100) NULL";

// Check if column exists first
$check_column_sql = "SHOW COLUMNS FROM lead_status_map LIKE 'last_reason'";
$result = mysqli_query($conn, $check_column_sql);

if (mysqli_num_rows($result) == 0) {
    // Column doesn't exist, add it
    if(mysqli_query($conn, $alter_table_sql)) {
        echo "Column 'last_reason' added successfully to lead_status_map table.";
    } else {
        echo "Error adding column: " . mysqli_error($conn);
    }
} else {
    echo "Column 'last_reason' already exists in lead_status_map table.";
}

// Close connection
mysqli_close($conn);
?>