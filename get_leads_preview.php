<?php
// Include header
require_once "includes/header.php";

// Check if user is admin
if(!isAdmin()) {
    exit('Unauthorized');
}

// Get preview data (first 10 records)
$sql = "SELECT e.*, u.full_name as attended_by_name, d.name as department_name, 
        s.name as source_name, ac.name as campaign_name, ls.name as status_name,
        cl.enquiry_number, lsm.status_name as lead_status, fm.full_name as file_manager_name
        FROM enquiries e 
        JOIN users u ON e.attended_by = u.id 
        JOIN departments d ON e.department_id = d.id 
        JOIN sources s ON e.source_id = s.id 
        LEFT JOIN ad_campaigns ac ON e.ad_campaign_id = ac.id 
        LEFT JOIN lead_status ls ON (e.status_id = ls.id OR e.status_id = ls.name) 
        LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id
        LEFT JOIN lead_status_map lsm ON e.id = lsm.enquiry_id
        LEFT JOIN users fm ON cl.file_manager_id = fm.id
        WHERE e.status_id = 3 AND cl.enquiry_id IS NOT NULL AND (cl.booking_confirmed = 0 OR cl.booking_confirmed IS NULL)
        ORDER BY e.received_datetime DESC
        LIMIT 10";

$result = mysqli_query($conn, $sql);

while($row = mysqli_fetch_assoc($result)) {
    echo '<tr>';
    echo '<td>' . (isset($row['created_at']) ? date('d-m-Y', strtotime($row['created_at'])) : date('d-m-Y', strtotime($row['received_datetime']))) . '</td>';
    echo '<td>' . htmlspecialchars($row['enquiry_number'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($row['customer_name'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($row['mobile_number'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($row['email'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($row['department_name'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($row['source_name'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($row['lead_status'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($row['attended_by_name'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($row['file_manager_name'] ?? 'Not Assigned') . '</td>';
    echo '</tr>';
}

exit;
?>