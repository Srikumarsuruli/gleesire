<?php
session_start();
require_once "config/database.php";

if(!isset($_SESSION['id']) && !isset($_SESSION['user_id'])) {
    exit;
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="converted_leads.csv"');

echo "id,enquiry_id,enquiry_number,customer_name,mobile_number,email,customer_location,secondary_contact,other_details,travel_start_date,travel_end_date,travel_month,night_day,adults_count,children_count,infants_count,children_age_details,lead_type,destination_id,file_manager_id,booking_confirmed,created_at\n";

$sql = "SELECT cl.id, cl.enquiry_id, cl.enquiry_number, e.customer_name, e.mobile_number, e.email, e.customer_location, e.secondary_contact, e.other_details, cl.travel_start_date, cl.travel_end_date, cl.travel_month, cl.night_day, cl.adults_count, cl.children_count, cl.infants_count, cl.children_age_details, cl.lead_type, cl.destination_id, cl.file_manager_id, cl.booking_confirmed, cl.created_at
        FROM converted_leads cl
        JOIN enquiries e ON cl.enquiry_id = e.id
        ORDER BY cl.id";

$result = mysqli_query($conn, $sql);

if($result) {
    while($row = mysqli_fetch_assoc($result)) {
        foreach($row as $key => $value) {
            if($value === null) {
                $row[$key] = '';
            }
            $row[$key] = str_replace('"', '""', $row[$key]);
        }
        echo '"' . implode('","', $row) . '"' . "\n";
    }
}
?>
