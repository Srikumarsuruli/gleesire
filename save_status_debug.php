<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once "config/database.php";

echo "<h3>Save Status Debug</h3>";
echo "<p>POST Data:</p><pre>";
print_r($_POST);
echo "</pre>";

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<p>Error: Not a POST request</p>";
    exit;
}

// Get and validate input
$enquiry_id = isset($_POST['enquiry_id']) ? intval($_POST['enquiry_id']) : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

echo "<p>Enquiry ID: $enquiry_id</p>";
echo "<p>Status: $status</p>";

// Validate input
if ($enquiry_id <= 0 || empty($status)) {
    echo "<p>Error: Invalid input data</p>";
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

if (mysqli_query($conn, $create_table_sql)) {
    echo "<p>Table check: OK</p>";
} else {
    echo "<p>Table error: " . mysqli_error($conn) . "</p>";
}

// Use prepared statement
$sql = "INSERT INTO lead_status_map (enquiry_id, status_name) VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE status_name = VALUES(status_name)";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    echo "<p>Prepared statement: OK</p>";
    
    mysqli_stmt_bind_param($stmt, "is", $enquiry_id, $status);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<p>Query executed: SUCCESS</p>";
        mysqli_stmt_close($stmt);
        
        // Check if status is "Closed – Booked"
        if ($status == "Closed – Booked") {
            echo "<p>Would redirect to: cost_sheet.php?id=" . $enquiry_id . "</p>";
        } else {
            echo "<p>Would redirect to: view_leads.php?status_updated=1</p>";
        }
    } else {
        echo "<p>Query execution error: " . mysqli_stmt_error($stmt) . "</p>";
    }
} else {
    echo "<p>Prepared statement error: " . mysqli_error($conn) . "</p>";
}

echo "<p><a href='view_leads.php'>Back to Leads</a></p>";
?>