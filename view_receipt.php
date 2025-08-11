<?php
// Include database connection
require_once "config/database.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get receipt path from query parameter
$receipt_path = isset($_GET['path']) ? $_GET['path'] : '';
$cost_sheet_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// If no direct path but we have a cost sheet ID
if (empty($receipt_path) && $cost_sheet_id > 0) {
    // First check payments table
    $sql = "SELECT payment_receipt FROM payments WHERE cost_file_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $cost_sheet_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $receipt_path = $row['payment_receipt'];
    } else {
        // Check payment_data in tour_costings
        $sql = "SELECT payment_data FROM tour_costings WHERE id = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $cost_sheet_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $payment_data = json_decode($row['payment_data'], true);
            $receipt_path = $payment_data['receipt'] ?? '';
        }
    }
}

// Clean the path to prevent directory traversal
$receipt_path = str_replace('../', '', $receipt_path);

// Check if file exists
if (!empty($receipt_path) && file_exists($receipt_path)) {
    // Get file extension
    $extension = strtolower(pathinfo($receipt_path, PATHINFO_EXTENSION));
    
    // Set appropriate content type
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            header('Content-Type: image/jpeg');
            break;
        case 'png':
            header('Content-Type: image/png');
            break;
        case 'gif':
            header('Content-Type: image/gif');
            break;
        case 'pdf':
            header('Content-Type: application/pdf');
            break;
        default:
            header('Content-Type: application/octet-stream');
    }
    
    // Output the file
    readfile($receipt_path);
    exit;
} else {
    // Display error message
    echo "<div style='text-align:center; margin-top:50px;'>";
    echo "<h3>Receipt Not Found</h3>";
    echo "<p>The requested receipt could not be found. The file may have been moved or deleted.</p>";
    echo "<p>Path: " . htmlspecialchars($receipt_path) . "</p>";
    echo "<p><a href='javascript:history.back()'>Go Back</a></p>";
    echo "</div>";
}
?>