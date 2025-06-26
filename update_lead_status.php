<?php
// Include database connection
require_once "includes/config/database.php";

// Log request for debugging
file_put_contents('lead_status_debug.txt', date('Y-m-d H:i:s') . " - Request received\n", FILE_APPEND);
file_put_contents('lead_status_debug.txt', date('Y-m-d H:i:s') . " - POST: " . print_r($_POST, true) . "\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enquiry_id']) && isset($_POST['status'])) {
    $enquiry_id = $_POST['enquiry_id'];
    $status = $_POST['status'];
    
    file_put_contents('lead_status_debug.txt', "ID: $enquiry_id, Status: $status\n", FILE_APPEND);
    
    // Check if record exists
    $check_sql = "SELECT id FROM lead_status_map WHERE enquiry_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "i", $enquiry_id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($result) > 0) {
        // Update existing record
        $sql = "UPDATE lead_status_map SET status_name = ? WHERE enquiry_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $status, $enquiry_id);
        file_put_contents('lead_status_debug.txt', "Updating existing record\n", FILE_APPEND);
    } else {
        // Insert new record
        $sql = "INSERT INTO lead_status_map (enquiry_id, status_name) VALUES (?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "is", $enquiry_id, $status);
        file_put_contents('lead_status_debug.txt', "Inserting new record\n", FILE_APPEND);
    }
    
    // Execute the statement
    $success = mysqli_stmt_execute($stmt);
    file_put_contents('lead_status_debug.txt', "Execute result: " . ($success ? "Success" : "Failed: " . mysqli_error($conn)) . "\n", FILE_APPEND);
    
    // Return response
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
} else {
    file_put_contents('lead_status_debug.txt', "Invalid request\n", FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>