<?php
session_start();
require_once "config/database.php";

if(!isset($_SESSION['id']) && !isset($_SESSION['user_id'])) {
    exit('Not authorized');
}

$filename = "converted_leads_" . date('Y-m-d_H-i-s') . ".csv";

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

echo "id,enquiry_id,enquiry_number,travel_start_date,travel_end_date,travel_month,night_day,adults_count,children_count,infants_count,children_age_details,lead_type,destination_id,file_manager_id,booking_confirmed,created_at\n";

$sql = "SELECT id, enquiry_id, enquiry_number, travel_start_date, travel_end_date, travel_month, night_day, adults_count, children_count, infants_count, children_age_details, lead_type, destination_id, file_manager_id, booking_confirmed, 
        COALESCE(created_at, NOW()) as created_at
        FROM converted_leads ORDER BY id";

$result = mysqli_query($conn, $sql);

if($result) {
    while($row = mysqli_fetch_assoc($result)) {
        foreach($row as $key => $value) {
            if($value === null) {
                $row[$key] = '';
            }
        }
        echo '"' . implode('","', array_map('addslashes', $row)) . '"' . "\n";
    }
}
?>