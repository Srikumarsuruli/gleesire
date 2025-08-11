<?php
require_once "config/database.php";

$sql = "ALTER TABLE lead_status ADD COLUMN status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active'";

if (mysqli_query($conn, $sql)) {
    echo "Status column added to lead_status table successfully";
} else {
    echo "Error adding column: " . mysqli_error($conn);
}
?>