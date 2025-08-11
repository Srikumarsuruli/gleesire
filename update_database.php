<?php
// Include database configuration
require_once "config/database.php";

// Add children_age_details column to converted_leads table
$sql = "ALTER TABLE `converted_leads` ADD COLUMN `children_age_details` VARCHAR(255) NULL AFTER `infants_count`";

if(mysqli_query($conn, $sql)) {
    echo "Column 'children_age_details' added successfully to converted_leads table.";
} else {
    // Check if column already exists
    if(mysqli_errno($conn) == 1060) {
        echo "Column 'children_age_details' already exists in converted_leads table.";
    } else {
        echo "Error adding column: " . mysqli_error($conn);
    }
}

mysqli_close($conn);
?>