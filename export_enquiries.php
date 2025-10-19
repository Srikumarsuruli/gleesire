<?php
session_start();
require_once "config/database.php";

if(!isset($_SESSION['id']) && !isset($_SESSION['user_id'])) {
    exit('Not authorized');
}

$filename = "enquiries_export_" . date('Y-m-d_H-i-s') . ".csv";

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

echo "# TABLE: enquiries\n";
echo "# Export Date: " . date('Y-m-d H:i:s') . "\n";
echo "# Description: Enquiries data export\n";
echo "\n";

echo "id,lead_number,customer_name,mobile_number,email,customer_location,secondary_contact,referral_code,social_media_link,enquiry_type,other_details,department_id,source_id,ad_campaign_id,attended_by,status_id,received_datetime,last_updated\n";

$sql = "SELECT * FROM enquiries ORDER BY id";
$result = mysqli_query($conn, $sql);

if($result) {
    while($row = mysqli_fetch_assoc($result)) {
        echo '"' . implode('","', array_map('addslashes', $row)) . '"' . "\n";
    }
}
?>
