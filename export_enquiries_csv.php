<?php
require_once "includes/header.php";

// Check if user is admin
if(!isAdmin()) {
    header("HTTP/1.1 403 Forbidden");
    exit("Access denied. Admin privileges required.");
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="enquiries_export_' . date('Y-m-d_H-i-s') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// CSV headers
$headers = [
    'Enquiry Number',
    'Customer Name', 
    'Mobile Number',
    'Email',
    'Customer Location',
    'Secondary Contact',
    'Referral Code',
    'Social Media Link',
    'Enquiry Type',
    'Department',
    'Source/Channel',
    'Status',
    'Attended By',
    'Received Date',
    'Last Updated',
    'Other Details'
];

fputcsv($output, $headers);

// Get all enquiries data
$sql = "SELECT e.*, u.full_name as attended_by_name, d.name as department_name, 
        s.name as source_name, ls.name as status_name
        FROM enquiries e 
        JOIN users u ON e.attended_by = u.id 
        JOIN departments d ON e.department_id = d.id 
        JOIN sources s ON e.source_id = s.id 
        LEFT JOIN lead_status ls ON e.status_id = ls.id 
        WHERE e.branch_id = ? 
        ORDER BY e.id DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['branch_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Output data rows
while($row = mysqli_fetch_assoc($result)) {
    $data = [
        $row['lead_number'],
        $row['customer_name'],
        $row['mobile_number'],
        $row['email'] ?? '',
        $row['customer_location'] ?? '',
        $row['secondary_contact'] ?? '',
        $row['referral_code'] ?? '',
        $row['social_media_link'] ?? '',
        $row['enquiry_type'] ?? '',
        $row['department_name'],
        $row['source_name'],
        $row['status_name'] ?? '',
        $row['attended_by_name'],
        date('d-m-Y H:i', strtotime($row['received_datetime'])),
        date('d-m-Y H:i', strtotime($row['last_updated'])),
        $row['other_details'] ?? ''
    ];
    
    fputcsv($output, $data);
}

fclose($output);
exit;
?>