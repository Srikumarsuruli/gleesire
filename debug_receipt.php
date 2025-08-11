<?php
// Include database connection
require_once "config/database.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get cost file ID from URL
$cost_file_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

echo "<h2>Receipt Debug Tool</h2>";

if ($cost_file_id > 0) {
    echo "<h3>Cost File ID: $cost_file_id</h3>";
    
    // Get cost file data
    $sql = "SELECT * FROM tour_costings WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $cost_file_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        echo "<h4>Cost Sheet: " . htmlspecialchars($row['cost_sheet_number']) . "</h4>";
        
        // Check payment_data
        $payment_data = json_decode($row['payment_data'], true) ?: [];
        echo "<h4>Payment Data:</h4>";
        echo "<pre>" . htmlspecialchars(print_r($payment_data, true)) . "</pre>";
        
        // Check if receipt exists in payment_data
        $receipt_path = $payment_data['receipt'] ?? null;
        echo "<p>Receipt path in payment_data: " . ($receipt_path ? htmlspecialchars($receipt_path) : "Not found") . "</p>";
        
        if ($receipt_path) {
            echo "<p>File exists: " . (file_exists($receipt_path) ? "Yes" : "No") . "</p>";
            echo "<p>File readable: " . (is_readable($receipt_path) ? "Yes" : "No") . "</p>";
            
            if (file_exists($receipt_path)) {
                echo "<p>File size: " . filesize($receipt_path) . " bytes</p>";
                echo "<p>File type: " . mime_content_type($receipt_path) . "</p>";
                echo "<p><a href='view_receipt.php?id=$cost_file_id' target='_blank'>View Receipt</a></p>";
            }
        }
        
        // Check payments table
        $sql = "SELECT * FROM payments WHERE cost_file_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $cost_file_id);
        mysqli_stmt_execute($stmt);
        $payment_result = mysqli_stmt_get_result($stmt);
        
        if ($payment_row = mysqli_fetch_assoc($payment_result)) {
            echo "<h4>Payment Record:</h4>";
            echo "<pre>" . htmlspecialchars(print_r($payment_row, true)) . "</pre>";
            
            // Check if receipt exists in payments table
            $payment_receipt = $payment_row['payment_receipt'] ?? null;
            echo "<p>Receipt path in payments table: " . ($payment_receipt ? htmlspecialchars($payment_receipt) : "Not found") . "</p>";
            
            if ($payment_receipt) {
                echo "<p>File exists: " . (file_exists($payment_receipt) ? "Yes" : "No") . "</p>";
                echo "<p>File readable: " . (is_readable($payment_receipt) ? "Yes" : "No") . "</p>";
                
                if (file_exists($payment_receipt)) {
                    echo "<p>File size: " . filesize($payment_receipt) . " bytes</p>";
                    echo "<p>File type: " . mime_content_type($payment_receipt) . "</p>";
                }
            }
        } else {
            echo "<p>No payment record found in payments table.</p>";
        }
        
        // Fix receipt path if needed
        if (isset($_GET['fix']) && $_GET['fix'] == 1) {
            if (!empty($payment_receipt) && empty($receipt_path)) {
                // Update payment_data with receipt path from payments table
                $payment_data['receipt'] = $payment_receipt;
                $updated_payment_data = json_encode($payment_data);
                
                $update_sql = "UPDATE tour_costings SET payment_data = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "si", $updated_payment_data, $cost_file_id);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    echo "<p style='color:green'>Successfully updated payment_data with receipt path from payments table.</p>";
                } else {
                    echo "<p style='color:red'>Error updating payment_data: " . mysqli_error($conn) . "</p>";
                }
            } elseif (!empty($receipt_path) && empty($payment_receipt)) {
                // Update payments table with receipt path from payment_data
                $update_sql = "UPDATE payments SET payment_receipt = ? WHERE cost_file_id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "si", $receipt_path, $cost_file_id);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    echo "<p style='color:green'>Successfully updated payments table with receipt path from payment_data.</p>";
                } else {
                    echo "<p style='color:red'>Error updating payments table: " . mysqli_error($conn) . "</p>";
                }
            } else {
                echo "<p>No fix needed or both receipt paths are empty.</p>";
            }
        }
        
        echo "<p><a href='debug_receipt.php?id=$cost_file_id&fix=1'>Fix Receipt Path</a> | <a href='view_payment_receipts.php?id=$cost_file_id'>View Payment Details</a></p>";
    } else {
        echo "<p>Cost file not found.</p>";
    }
} else {
    echo "<p>Please provide a cost file ID in the URL: debug_receipt.php?id=X</p>";
}
?>