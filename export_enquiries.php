<?php
// Start session
session_start();

// Include database connection and functions
require_once "config/database.php";
require_once "includes/functions.php";

// Check if user is logged in
if(!isset($_SESSION['id']) && !isset($_SESSION['user_id'])) {
    header("location: index.php");
    exit;
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="enquiries_export_' . date('Y-m-d_H-i-s') . '.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV header with table name and comments
fputcsv($output, ['# TABLE: enquiries']);
fputcsv($output, ['# Export Date: ' . date('Y-m-d H:i:s')]);
fputcsv($output, ['# Description: Enquiries data export for database migration']);
fputcsv($output, ['# Note: Import this data into enquiries table on new server']);
fputcsv($output, []);

// Write column headers
$headers = [
    'id',
    'lead_number',
    'customer_name',
    'mobile_number',
    'email',
    'customer_location',
    'secondary_contact',
    'referral_code',
    'social_media_link',
    'enquiry_type',
    'other_details',
    'department_id',
    'source_id',
    'ad_campaign_id',
    'attended_by',
    'status_id',
    'received_datetime',
    'last_updated'
];
fputcsv($output, $headers);

// Build SQL query to get all enquiries data
$sql = "SELECT e.id, e.lead_number, e.customer_name, e.mobile_number, e.email, 
        e.customer_location, e.secondary_contact, e.referral_code, e.social_media_link,
        e.enquiry_type, e.other_details, e.department_id, e.source_id, e.ad_campaign_id,
        e.attended_by, e.status_id, e.received_datetime, e.last_updated
        FROM enquiries e
        ORDER BY e.id";

$result = mysqli_query($conn, $sql);

if($result && mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $row['id'],
            $row['lead_number'],
            $row['customer_name'],
            $row['mobile_number'],
            $row['email'],
            $row['customer_location'],
            $row['secondary_contact'],
            $row['referral_code'],
            $row['social_media_link'],
            $row['enquiry_type'],
            $row['other_details'],
            $row['department_id'],
            $row['source_id'],
            $row['ad_campaign_id'],
            $row['attended_by'],
            $row['status_id'],
            $row['received_datetime'],
            $row['last_updated']
        ]);
    }
}

fclose($output);
exit;
?>
