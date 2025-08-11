<?php
// Include database connection
require_once "includes/config.php";

// Check if the connection is successful
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Alter the travel_month column to VARCHAR
$alter_sql = "ALTER TABLE converted_leads MODIFY COLUMN travel_month VARCHAR(20)";
if (mysqli_query($conn, $alter_sql)) {
    echo "Table structure updated successfully. The travel_month column is now VARCHAR(20).<br>";
} else {
    echo "Error updating table structure: " . mysqli_error($conn) . "<br>";
}

// Close the connection
mysqli_close($conn);

echo "Done.";
?>