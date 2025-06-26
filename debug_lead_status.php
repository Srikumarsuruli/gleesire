<?php
// Initialize the session
session_start();

// Include database connection
require_once "config/database.php";

// Get the structure of the enquiries table
echo "<h3>Enquiries Table Structure:</h3>";
$result = mysqli_query($conn, "DESCRIBE enquiries");
echo "<pre>";
while($row = mysqli_fetch_assoc($result)) {
    print_r($row);
}
echo "</pre>";

// Get the structure of the lead_status table
echo "<h3>Lead Status Table Structure:</h3>";
$result = mysqli_query($conn, "DESCRIBE lead_status");
echo "<pre>";
while($row = mysqli_fetch_assoc($result)) {
    print_r($row);
}
echo "</pre>";

// Get all lead statuses
echo "<h3>Available Lead Statuses:</h3>";
$result = mysqli_query($conn, "SELECT * FROM lead_status");
echo "<pre>";
while($row = mysqli_fetch_assoc($result)) {
    print_r($row);
}
echo "</pre>";

// Get a sample lead record
echo "<h3>Sample Lead Record:</h3>";
$result = mysqli_query($conn, "SELECT e.*, ls.name as status_name FROM enquiries e JOIN lead_status ls ON e.status_id = ls.id LIMIT 1");
echo "<pre>";
while($row = mysqli_fetch_assoc($result)) {
    print_r($row);
}
echo "</pre>";

// Close connection
mysqli_close($conn);
?>