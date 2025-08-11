<?php
// Include database connection
require_once "config/database.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Payment Data Fix Tool</h2>";

// Check if tour_costings table exists
$table_exists = mysqli_query($conn, "SHOW TABLES LIKE 'tour_costings'");
if (mysqli_num_rows($table_exists) == 0) {
    die("Error: tour_costings table does not exist.");
}

// Get all cost sheets with payment data
$sql = "SELECT id, cost_sheet_number, payment_data, package_cost FROM tour_costings WHERE payment_data IS NOT NULL AND payment_data != '{}'";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Error querying database: " . mysqli_error($conn));
}

echo "<h3>Processing Payment Data</h3>";
echo "<table border='1'><tr><th>ID</th><th>Cost Sheet</th><th>Original Data</th><th>Fixed Data</th><th>Status</th></tr>";

$fixed_count = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $id = $row['id'];
    $cost_sheet_number = $row['cost_sheet_number'];
    $payment_data = json_decode($row['payment_data'], true);
    $package_cost = $row['package_cost'];
    
    $original_data = $row['payment_data'];
    $needs_fixing = false;
    
    // Check if payment data needs fixing
    if (!isset($payment_data['receipt']) && isset($_GET['add_receipt_field'])) {
        $payment_data['receipt'] = null;
        $needs_fixing = true;
    }
    
    if (empty($payment_data['total_received']) && !empty($payment_data['amount'])) {
        $payment_data['total_received'] = $payment_data['amount'];
        $needs_fixing = true;
    }
    
    if (empty($payment_data['balance_amount'])) {
        $payment_data['balance_amount'] = $package_cost - ($payment_data['total_received'] ?? $payment_data['amount'] ?? 0);
        $needs_fixing = true;
    }
    
    // Update the database if needed
    if ($needs_fixing) {
        $fixed_data = json_encode($payment_data);
        $update_sql = "UPDATE tour_costings SET payment_data = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "si", $fixed_data, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $status = "Fixed";
            $fixed_count++;
        } else {
            $status = "Error: " . mysqli_error($conn);
        }
    } else {
        $fixed_data = $original_data;
        $status = "No changes needed";
    }
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($id) . "</td>";
    echo "<td>" . htmlspecialchars($cost_sheet_number) . "</td>";
    echo "<td><pre>" . htmlspecialchars($original_data) . "</pre></td>";
    echo "<td><pre>" . htmlspecialchars($fixed_data) . "</pre></td>";
    echo "<td>" . htmlspecialchars($status) . "</td>";
    echo "</tr>";
}

echo "</table>";
echo "<p>Fixed $fixed_count records.</p>";

// Check payments table
$table_exists = mysqli_query($conn, "SHOW TABLES LIKE 'payments'");
$payments_table_exists = (mysqli_num_rows($table_exists) > 0);

if ($payments_table_exists) {
    echo "<h3>Checking Payments Table</h3>";
    
    // Get all payments
    $sql = "SELECT p.*, tc.cost_sheet_number, tc.payment_data 
            FROM payments p
            LEFT JOIN tour_costings tc ON p.cost_file_id = tc.id";
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        echo "Error querying payments: " . mysqli_error($conn);
    } else {
        echo "<table border='1'><tr><th>ID</th><th>Cost Sheet</th><th>Payment Date</th><th>Bank</th><th>Amount</th><th>Receipt</th></tr>";
        
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['cost_sheet_number']) . "</td>";
                echo "<td>" . htmlspecialchars($row['payment_date']) . "</td>";
                echo "<td>" . htmlspecialchars($row['payment_bank']) . "</td>";
                echo "<td>" . htmlspecialchars($row['payment_amount']) . "</td>";
                echo "<td>" . htmlspecialchars($row['payment_receipt'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='6'>No payment records found</td></tr>";
        }
        
        echo "</table>";
    }
}

echo "<p>Fix completed.</p>";
echo "<p><a href='view_payment_receipts.php'>Return to Payment Receipts</a></p>";
?>