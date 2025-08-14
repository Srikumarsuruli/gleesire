<?php
session_start();
require_once "config/database.php";

$id = 183;
$response = array();

// Get enquiry data
$sql = "SELECT e.*, d.name as department_name, s.name as source_name, ls.name as status_name, u.full_name as attended_by_name
        FROM enquiries e
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN sources s ON e.source_id = s.id
        LEFT JOIN lead_status ls ON e.status_id = ls.id
        LEFT JOIN users u ON e.attended_by = u.id
        WHERE e.id = $id";

$result = mysqli_query($conn, $sql);
if($result && $row = mysqli_fetch_assoc($result)) {
    $response['enquiry'] = $row;
}

// Get lead data
$lead_sql = "SELECT * FROM converted_leads WHERE enquiry_id = $id";
$lead_result = mysqli_query($conn, $lead_sql);
if($lead_result && $lead_row = mysqli_fetch_assoc($lead_result)) {
    $response['lead'] = $lead_row;
}

header('Content-Type: application/json');
echo json_encode($response);
?>