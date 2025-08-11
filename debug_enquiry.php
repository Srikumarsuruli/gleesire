<?php
require_once "config/database.php";

// Find the enquiry with lead number GHE/2025/07/0040
$sql = "SELECT e.*, ls.name as status_name FROM enquiries e 
        LEFT JOIN lead_status ls ON e.status_id = ls.id 
        WHERE e.lead_number = 'GHE/2025/07/0040'";
$result = mysqli_query($conn, $sql);

if($row = mysqli_fetch_assoc($result)) {
    echo "Enquiry ID: " . $row['id'] . "<br>";
    echo "Lead Number: " . $row['lead_number'] . "<br>";
    echo "Current Status ID: " . $row['status_id'] . "<br>";
    echo "Current Status Name: " . $row['status_name'] . "<br>";
} else {
    echo "Enquiry not found";
}

// Show all available statuses
echo "<br><br>Available Statuses:<br>";
$statuses_sql = "SELECT * FROM lead_status ORDER BY id";
$statuses = mysqli_query($conn, $statuses_sql);
while($status = mysqli_fetch_assoc($statuses)) {
    echo "ID: " . $status['id'] . " - " . $status['name'] . "<br>";
}
?>