<?php
// Include database connection
require_once "config/database.php";

// Add cost_sheet_number column to cost_sheets table
$sql = "ALTER TABLE cost_sheets ADD COLUMN cost_sheet_number VARCHAR(50) AFTER enquiry_number";

if (mysqli_query($conn, $sql)) {
    echo "Column cost_sheet_number added successfully!";
} else {
    // Check if column already exists
    if (strpos(mysqli_error($conn), 'Duplicate column name') !== false) {
        echo "Column cost_sheet_number already exists.";
    } else {
        echo "Error adding column: " . mysqli_error($conn);
    }
}
?>