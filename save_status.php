<?php
// Include database connection
require_once "config/database.php";

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: view_leads.php?error=2");
    exit;
}

// Get and validate input
$enquiry_id = isset($_POST['enquiry_id']) ? intval($_POST['enquiry_id']) : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

// Validate input
if ($enquiry_id <= 0 || empty($status)) {
    header("Location: view_leads.php?error=2");
    exit;
}

// Create table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS lead_status_map (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    enquiry_id INT(11) NOT NULL,
    status_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_enquiry (enquiry_id)
)";
mysqli_query($conn, $create_table_sql);

// Use prepared statement
$sql = "INSERT INTO lead_status_map (enquiry_id, status_name) VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE status_name = VALUES(status_name)";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "is", $enquiry_id, $status);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    
    // Check if status is "Closed – Booked"
    if ($status == "Closed – Booked") {
        header("Location: cost_sheet.php?id=" . $enquiry_id);
    } else {
        header("Location: view_leads.php?status_updated=1");
    }
} else {
    header("Location: view_leads.php?error=1");
}

exit;
?>