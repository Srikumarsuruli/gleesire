<?php
require_once "config/database.php";

$sql = "ALTER TABLE `converted_leads` ADD COLUMN `children_age_details` VARCHAR(255) NULL AFTER `infants_count`";

if(mysqli_query($conn, $sql)) {
    echo "Column added successfully!";
} else {
    if(mysqli_errno($conn) == 1060) {
        echo "Column already exists.";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

mysqli_close($conn);
?>