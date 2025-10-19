<?php
session_start();
require_once "config/database.php";

if(!isset($_SESSION['id']) && !isset($_SESSION['user_id'])) {
    exit;
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="converted_leads.csv"');

echo "id,enquiry_id,enquiry_number,customer_name,mobile_number,email\n";

$sql = "SELECT cl.id, cl.enquiry_id, cl.enquiry_number, e.customer_name, e.mobile_number, e.email
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
