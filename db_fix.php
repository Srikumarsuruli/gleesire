<?php
// Include database connection
require_once "includes/config.php";

// Check the current structure of the travel_month column
$result = mysqli_query($conn, "DESCRIBE converted_leads travel_month");
$row = mysqli_fetch_assoc($result);
echo "Current travel_month column type: " . $row['Type'] . "<br>";

// Alter the travel_month column to VARCHAR if it's currently DATE type
if (strpos(strtolower($row['Type']), 'date') !== false) {
    $sql = "ALTER TABLE converted_leads MODIFY COLUMN travel_month VARCHAR(20)";
    if(mysqli_query($conn, $sql)) {
        echo "Database column travel_month modified successfully to VARCHAR(20)<br>";
    } else {
        echo "Error modifying column: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "Column is already the correct type, no changes needed.<br>";
}

echo "Done!";
?>