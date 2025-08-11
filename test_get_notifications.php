<?php
session_start();
require_once "config/database.php";

if(!isset($_SESSION["loggedin"])) {
    echo "Not logged in";
    exit;
}

$user_id = $_SESSION['id'];

// Get notifications for current user
$sql = "SELECT n.*, e.customer_name 
        FROM notifications n 
        LEFT JOIN enquiries e ON n.enquiry_id = e.id 
        WHERE n.user_id = ? 
        ORDER BY n.created_at DESC LIMIT 10";

$notifications = [];
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while($row = mysqli_fetch_assoc($result)) {
        $notifications[] = [
            'id' => $row['id'],
            'enquiry_id' => $row['enquiry_id'],
            'enquiry_number' => $row['enquiry_number'],
            'lead_number' => $row['lead_number'],
            'customer_name' => $row['customer_name'] ?: 'Lead Assignment',
            'message' => $row['message'],
            'is_read' => $row['is_read'],
            'created_at' => date('M d, H:i', strtotime($row['created_at']))
        ];
    }
    mysqli_stmt_close($stmt);
}

// Count unread notifications
$unread_sql = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
$unread_count = 0;
if($stmt = mysqli_prepare($conn, $unread_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $unread_count = $row['unread_count'];
    mysqli_stmt_close($stmt);
}

echo json_encode([
    'success' => true,
    'notifications' => $notifications,
    'unread_count' => $unread_count
]);
?>