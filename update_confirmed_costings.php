<?php
// Include database connection
require_once "config/database.php";

// Add confirmed column if it doesn't exist
$check_column_sql = "SHOW COLUMNS FROM tour_costings LIKE 'confirmed'";
$check_result = mysqli_query($conn, $check_column_sql);
if(mysqli_num_rows($check_result) == 0) {
    $add_column_sql = "ALTER TABLE tour_costings ADD COLUMN confirmed TINYINT(1) DEFAULT 0";
    if(mysqli_query($conn, $add_column_sql)) {
        echo "Added 'confirmed' column to tour_costings table.<br>";
    } else {
        echo "Error adding column: " . mysqli_error($conn) . "<br>";
    }
}

// Update all existing tour_costings records to confirmed = 1
// You can modify this logic based on your business rules
$update_sql = "UPDATE tour_costings SET confirmed = 1 WHERE confirmed = 0 OR confirmed IS NULL";
if(mysqli_query($conn, $update_sql)) {
    $affected_rows = mysqli_affected_rows($conn);
    echo "Updated $affected_rows records to confirmed = 1.<br>";
} else {
    echo "Error updating records: " . mysqli_error($conn) . "<br>";
}

echo "Update completed successfully!";

mysqli_close($conn);
?>