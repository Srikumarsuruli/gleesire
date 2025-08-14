<?php
require_once "config/database.php";

$id = 183;

$sql = "SELECT cl.*, 
        COALESCE(d.name, 'N/A') as destination_name,
        COALESCE(u.full_name, 'N/A') as file_manager_name,
        COALESCE(nd.name, cl.night_day, 'N/A') as night_day_name
        FROM converted_leads cl
        LEFT JOIN destinations d ON cl.destination_id = d.id
        LEFT JOIN users u ON cl.file_manager_id = u.id
        LEFT JOIN night_day nd ON cl.night_day_id = nd.id
        WHERE cl.enquiry_id = $id";

echo "Query: $sql\n\n";

$result = mysqli_query($conn, $sql);
if($result) {
    echo "Rows found: " . mysqli_num_rows($result) . "\n\n";
    if($row = mysqli_fetch_assoc($result)) {
        echo "Lead data:\n";
        print_r($row);
    }
} else {
    echo "Query error: " . mysqli_error($conn) . "\n";
}
?>