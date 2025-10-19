<?php
// Include database connection
require_once "config/database.php";
require_once "includes/functions.php";

// Check if user has privilege to access this page
if(!hasPrivilege('view_leads')) {
    header("location: index.php");
    exit;
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="leads_export_' . date('Y-m-d_H-i-s') . '.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV header with table name and comments
fputcsv($output, ['# TABLE: converted_leads']);
fputcsv($output, ['# Export Date: ' . date('Y-m-d H:i:s')]);
fputcsv($output, ['# Description: Converted leads data export for database migration']);
fputcsv($output, ['# Note: Import this data into converted_leads table on new server']);
fputcsv($output, ['# Related Tables: enquiries, lead_status_map']);
fputcsv($output, []);

// Write column headers for converted_leads table
$headers = [
    'id',
    'enquiry_id',
    'enquiry_number',
    'travel_start_date',
    'travel_end_date',
    'travel_month',
    'night_day',
    'adults_count',
    'children_count',
    'infants_count',
    'children_age_details',
    'lead_type',
    'destination_id',
    'file_manager_id',
    'booking_confirmed',
    'created_at'
];
fputcsv($output, $headers);

// Build SQL query to get all converted leads data
$sql = "SELECT cl.id, cl.enquiry_id, cl.enquiry_number, cl.travel_start_date, 
        cl.travel_end_date, cl.travel_month, cl.night_day, cl.adults_count,
        cl.children_count, cl.infants_count, cl.children_age_details, cl.lead_type,
        cl.destination_id, cl.file_manager_id, cl.booking_confirmed, cl.created_at
        FROM converted_leads cl
        ORDER BY cl.id";

$result = mysqli_query($conn, $sql);

if($result && mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $row['id'],
            $row['enquiry_id'],
            $row['enquiry_number'],
            $row['travel_start_date'],
            $row['travel_end_date'],
            $row['travel_month'],
            $row['night_day'],
            $row['adults_count'],
            $row['children_count'],
            $row['infants_count'],
            $row['children_age_details'],
            $row['lead_type'],
            $row['destination_id'],
            $row['file_manager_id'],
            $row['booking_confirmed'],
            $row['created_at']
        ]);
    }
}

// Add separator for lead status map data
fputcsv($output, []);
fputcsv($output, ['# TABLE: lead_status_map']);
fputcsv($output, ['# Description: Lead status mapping data']);
fputcsv($output, []);

// Write headers for lead_status_map
$status_headers = ['id', 'enquiry_id', 'status_name', 'last_reason', 'created_at'];
fputcsv($output, $status_headers);

// Get lead status map data
$status_sql = "SELECT id, enquiry_id, status_name, 
               COALESCE(last_reason, '') as last_reason, created_at 
               FROM lead_status_map ORDER BY id";
$status_result = mysqli_query($conn, $status_sql);

if($status_result && mysqli_num_rows($status_result) > 0) {
    while($row = mysqli_fetch_assoc($status_result)) {
        fputcsv($output, [
            $row['id'],
            $row['enquiry_id'],
            $row['status_name'],
            $row['last_reason'],
            $row['created_at']
        ]);
    }
}

fclose($output);
exit;
?>