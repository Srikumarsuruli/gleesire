<?php
require_once "config/database.php";

$sql = "ALTER TABLE sources ADD COLUMN status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active'";

if (mysqli_query($conn, $sql)) {
    echo "Status column added to sources table successfully";
} else {
    echo "Error adding column: " . mysqli_error($conn);
}
?>