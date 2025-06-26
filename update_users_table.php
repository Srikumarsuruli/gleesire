<?php
// Include database connection
require_once "config/database.php";

// Add profile_image column to users table if it doesn't exist
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'profile_image'");
if(mysqli_num_rows($check_column) == 0) {
    $sql = "ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL";
    if(mysqli_query($conn, $sql)) {
        echo "Profile image column added successfully.";
    } else {
        echo "Error adding profile image column: " . mysqli_error($conn);
    }
} else {
    echo "Profile image column already exists.";
}
?>