<?php
// Include database connection
require_once "config/database.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Receipt Path Fix Tool</h2>";

// Check if uploads directory exists
$upload_dir = 'uploads/receipts';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
    echo "<p>Created uploads/receipts directory</p>";
}

// Get all payment records
$sql = "SELECT p.*, tc.cost_sheet_number 
        FROM payments p
        JOIN tour_costings tc ON p.cost_file_id = tc.id
        WHERE p.payment_receipt IS NOT NULL";
$result = mysqli_query($conn, $sql);

echo "<h3>Payment Records with Receipts</h3>";
echo "<table border='1'><tr><th>ID</th><th>Cost Sheet</th><th>Receipt Path</th><th>File Exists</th><th>Status</th></tr>";

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $receipt_path = $row['payment_receipt'];
        $file_exists = file_exists($receipt_path) ? "Yes" : "No";
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['cost_sheet_number']) . "</td>";
        echo "<td>" . htmlspecialchars($receipt_path) . "</td>";
        echo "<td>" . $file_exists . "</td>";
        
        // Update tour_costings payment_data to include receipt path
        $update_sql = "SELECT id, payment_data FROM tour_costings WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "i", $row['cost_file_id']);
        mysqli_stmt_execute($stmt);
        $tc_result = mysqli_stmt_get_result($stmt);
        
        if ($tc_row = mysqli_fetch_assoc($tc_result)) {
            $payment_data = json_decode($tc_row['payment_data'], true) ?: [];
            $payment_data['receipt'] = $receipt_path;
            $updated_payment_data = json_encode($payment_data);
            
            $update_sql = "UPDATE tour_costings SET payment_data = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "si", $updated_payment_data, $row['cost_file_id']);
            
            if (mysqli_stmt_execute($update_stmt)) {
                echo "<td>Updated payment_data with receipt path</td>";
            } else {
                echo "<td>Error updating: " . mysqli_error($conn) . "</td>";
            }
            mysqli_stmt_close($update_stmt);
        } else {
            echo "<td>Cost file not found</td>";
        }
        
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5'>No payment records with receipts found</td></tr>";
}
echo "</table>";

// Check tour_costings with payment_data that has receipt field
$sql = "SELECT id, cost_sheet_number, payment_data FROM tour_costings 
        WHERE payment_data IS NOT NULL AND payment_data != '{}'";
$result = mysqli_query($conn, $sql);

echo "<h3>Tour Costings with Payment Data</h3>";
echo "<table border='1'><tr><th>ID</th><th>Cost Sheet</th><th>Has Receipt Field</th><th>Receipt Path</th><th>File Exists</th></tr>";

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $payment_data = json_decode($row['payment_data'], true) ?: [];
        $has_receipt = isset($payment_data['receipt']) ? "Yes" : "No";
        $receipt_path = $payment_data['receipt'] ?? '';
        $file_exists = !empty($receipt_path) && file_exists($receipt_path) ? "Yes" : "No";
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['cost_sheet_number']) . "</td>";
        echo "<td>" . $has_receipt . "</td>";
        echo "<td>" . htmlspecialchars($receipt_path) . "</td>";
        echo "<td>" . $file_exists . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5'>No tour costings with payment data found</td></tr>";
}
echo "</table>";

echo "<p>Fix completed. <a href='view_payment_receipts.php'>Return to Payment Receipts</a></p>";
?>