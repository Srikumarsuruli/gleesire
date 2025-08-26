<?php
header('Content-Type: application/json');

require_once "config/database.php";
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

if($_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$lead_id = isset($_POST['lead_id']) ? intval($_POST['lead_id']) : 0;
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

if($lead_id <= 0 || $user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Add assigned_to column if it doesn't exist
$check_column = "SHOW COLUMNS FROM converted_leads LIKE 'assigned_to'";
$column_exists = mysqli_query($conn, $check_column);
if(mysqli_num_rows($column_exists) == 0) {
    mysqli_query($conn, "ALTER TABLE converted_leads ADD COLUMN assigned_to INT(11) NULL");
}

$sql = "UPDATE converted_leads SET assigned_to = ? WHERE enquiry_id = ?";
$stmt = mysqli_prepare($conn, $sql);

if($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $lead_id);
    mysqli_stmt_execute($stmt);
    
    if(mysqli_stmt_affected_rows($stmt) > 0) {
        echo json_encode(['success' => true, 'message' => 'Lead assigned successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lead not found']);
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

mysqli_close($conn);
?>