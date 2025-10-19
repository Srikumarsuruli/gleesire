<?php
session_start();
require_once "config/database.php";

if(!isset($_SESSION['id']) && !isset($_SESSION['user_id'])) {
    exit;
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="converted_leads.csv"');

echo "id,enquiry_id,enquiry_number,customer_name,mobile_number,email,travel_start_date,travel_end_date,adults_count,children_count,lead_type,booking_confirmed,created_at\n";

$sql = "SELECT cl.id, cl.enquiry_id, cl.enquiry_number, e.customer_name, e.mobile_number, e.email, cl.travel_start_date, cl.travel_end_date, cl.adults_count, cl.children_count, cl.lead_type, cl.booking_confirmed, cl.created_at
        FROM converted_leads cl
        JOIN enquiries e ON cl.enquiry_id = e.id
        ORDER BY cl.id";

$result = mysqli_query($conn, $sql);

if($result) {
    while($row = mysqli_fetch_assoc($result)) {
        echo implode(',', $row) . "\n";
    }
}
?>
