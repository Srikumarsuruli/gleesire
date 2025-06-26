<?php
// Include database connection
require_once "config/database.php";

// Add lead_type column to converted_leads table if it doesn't exist
$check_column = "SHOW COLUMNS FROM converted_leads LIKE 'lead_type'";
$result = mysqli_query($conn, $check_column);

if(mysqli_num_rows($result) == 0) {
    $add_column = "ALTER TABLE converted_leads ADD COLUMN lead_type ENUM('Hot', 'Warm', 'Cold') DEFAULT NULL AFTER enquiry_number";
    if(mysqli_query($conn, $add_column)) {
        echo "Lead Type column added successfully.";
    } else {
        echo "Error adding Lead Type column: " . mysqli_error($conn);
    }
} else {
    echo "Lead Type column already exists.";
}

// Close connection
mysqli_close($conn);
?>