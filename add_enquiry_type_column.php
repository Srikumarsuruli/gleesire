<?php
require_once "includes/header.php";

// Add enquiry_type column to enquiries table
$sql = "ALTER TABLE enquiries ADD COLUMN enquiry_type VARCHAR(255) NULL";
if(mysqli_query($conn, $sql)) {
    echo "enquiry_type column added successfully.";
} else {
    echo "Error: " . mysqli_error($conn);
}

mysqli_close($conn);
?>