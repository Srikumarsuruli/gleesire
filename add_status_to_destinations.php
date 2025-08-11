<?php
require_once "config/database.php";

$sql = "ALTER TABLE destinations ADD COLUMN status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active'";

if (mysqli_query($conn, $sql)) {
    echo "Status column added to destinations table successfully";
} else {
    echo "Error adding column: " . mysqli_error($conn);
}
?>