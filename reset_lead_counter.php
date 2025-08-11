<?php
require_once "includes/header.php";

// Reset lead counter to 0
$sql = "UPDATE number_sequences SET last_number = 0 WHERE type = 'lead'";
if(mysqli_query($conn, $sql)) {
    echo "Lead counter reset to 0 successfully.";
} else {
    echo "Error: " . mysqli_error($conn);
}

mysqli_close($conn);
?>