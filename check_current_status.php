<?php
require_once "config/database.php";

$sql = "SELECT e.*, ls.name as status_name FROM enquiries e 
        LEFT JOIN lead_status ls ON e.status_id = ls.id 
        WHERE e.id = 66";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

echo "Current Status ID: " . $row['status_id'] . "<br>";
echo "Current Status Name: " . $row['status_name'] . "<br>";
echo "Last Updated: " . $row['last_updated'] . "<br>";
?>