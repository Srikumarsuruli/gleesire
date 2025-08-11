<?php
require_once "config/database.php";

// Reset enquiry counter to 0 for current year/month
$year = date('Y');
$month = date('m');

$sql = "UPDATE number_sequences SET last_number = 0 WHERE type = 'enquiry' AND year = ? AND month = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $year, $month);

if(mysqli_stmt_execute($stmt)) {
    echo "Enquiry counter reset to 0 for $year/$month. Next enquiry will be 001.";
} else {
    echo "Error resetting counter: " . mysqli_error($conn);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>