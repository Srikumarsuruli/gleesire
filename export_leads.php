<?php
// Set headers for CSV download FIRST
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Leads_' . date('Y-m-d_H-i-s') . '.csv"');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Include database connection only
require_once "config/database.php";
require_once "includes/functions.php";

// Start session for admin check
session_start();

// Check if user is admin
if(!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    exit('Unauthorized');
}

// Get all leads data with last_reason
$sql = "SELECT e.*, u.full_name as attended_by_name, d.name as department_name, 
        s.name as source_name, ac.name as campaign_name, ls.name as status_name,
        cl.enquiry_number, cl.travel_start_date, cl.travel_end_date, cl.booking_confirmed,
        cl.adults_count, cl.children_count, cl.infants_count, cl.children_age_details, cl.lead_type,
        lsm.status_name as lead_status, lsm.last_reason, fm.full_name as file_manager_name,
        dest.name as destination_name
        FROM enquiries e 
        JOIN users u ON e.attended_by = u.id 
        JOIN departments d ON e.department_id = d.id 
        JOIN sources s ON e.source_id = s.id 
        LEFT JOIN ad_campaigns ac ON e.ad_campaign_id = ac.id 
        LEFT JOIN lead_status ls ON (e.status_id = ls.id OR e.status_id = ls.name) 
        LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id
        LEFT JOIN destinations dest ON cl.destination_id = dest.id
        LEFT JOIN lead_status_map lsm ON e.id = lsm.enquiry_id
        LEFT JOIN users fm ON cl.file_manager_id = fm.id
        WHERE e.status_id = 3 AND cl.enquiry_id IS NOT NULL AND (cl.booking_confirmed = 0 OR cl.booking_confirmed IS NULL)
        ORDER BY e.received_datetime DESC";

$result = mysqli_query($conn, $sql);

// Create file pointer
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, array(
    'Lead Date', 'Lead Number', 'Enquiry Number', 'Customer Name', 'Mobile Number', 'Email',
    'Customer Location', 'Secondary Contact', 'Referral Code', 'Social Media Link', 'Enquiry Type',
    'Department', 'Source', 'Campaign', 'Destination', 'Lead Type', 'Adults Count', 'Children Count',
    'Infants Count', 'Children Age Details', 'Travel Start Date', 'Travel End Date', 'Lead Status',
    'Last Reason', 'Received Date', 'Attended By', 'File Manager', 'Enquiry Status', 'Other Details'
));

// Add data rows
while($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, array(
        isset($row['created_at']) ? date('d-m-Y', strtotime($row['created_at'])) : date('d-m-Y', strtotime($row['received_datetime'])),
        $row['enquiry_number'] ?? '',
        $row['lead_number'] ?? '',
        $row['customer_name'] ?? '',
        $row['mobile_number'] ?? '',
        $row['email'] ?? '',
        $row['customer_location'] ?? '',
        $row['secondary_contact'] ?? '',
        $row['referral_code'] ?? '',
        $row['social_media_link'] ?? '',
        $row['enquiry_type'] ?? '',
        $row['department_name'] ?? '',
        $row['source_name'] ?? '',
        $row['campaign_name'] ?? '',
        $row['destination_name'] ?? '',
        $row['lead_type'] ?? '',
        $row['adults_count'] ?? '',
        $row['children_count'] ?? '',
        $row['infants_count'] ?? '',
        $row['children_age_details'] ?? '',
        $row['travel_start_date'] ? date('d-m-Y', strtotime($row['travel_start_date'])) : '',
        $row['travel_end_date'] ? date('d-m-Y', strtotime($row['travel_end_date'])) : '',
        $row['lead_status'] ?? '',
        $row['last_reason'] ?? '',
        date('d-m-Y H:i', strtotime($row['received_datetime'])),
        $row['attended_by_name'] ?? '',
        $row['file_manager_name'] ?? '',
        $row['status_name'] ?? '',
        $row['other_details'] ?? ''
    ));
}

fclose($output);
exit;
?>