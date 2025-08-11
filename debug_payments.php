<?php
// Include database connection
require_once "config/database.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Payment System Debug</h2>";

// Check if payments table exists
$table_exists = mysqli_query($conn, "SHOW TABLES LIKE 'payments'");
$payments_table_exists = (mysqli_num_rows($table_exists) > 0);

echo "<h3>Database Tables</h3>";
echo "Payments table exists: " . ($payments_table_exists ? "Yes" : "No") . "<br>";

// Check tour_costings table
$table_exists = mysqli_query($conn, "SHOW TABLES LIKE 'tour_costings'");
$tour_costings_exists = (mysqli_num_rows($table_exists) > 0);
echo "Tour costings table exists: " . ($tour_costings_exists ? "Yes" : "No") . "<br>";

// Check payments table structure
if ($payments_table_exists) {
    echo "<h3>Payments Table Structure</h3>";
    $result = mysqli_query($conn, "DESCRIBE payments");
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        foreach ($row as $key => $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // Check payment records
    echo "<h3>Payment Records</h3>";
    $result = mysqli_query($conn, "SELECT * FROM payments");
    if ($result && mysqli_num_rows($result) > 0) {
        echo "<table border='1'><tr>";
        $fields = mysqli_fetch_fields($result);
        foreach ($fields as $field) {
            echo "<th>" . htmlspecialchars($field->name) . "</th>";
        }
        echo "</tr>";
        
        mysqli_data_seek($result, 0);
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No payment records found in payments table.<br>";
    }
}

// Check tour_costings with payment data
echo "<h3>Tour Costings with Payment Data</h3>";
$sql = "SELECT id, cost_sheet_number, payment_data FROM tour_costings WHERE payment_data IS NOT NULL AND payment_data != '{}'";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Cost Sheet Number</th><th>Payment Data</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['cost_sheet_number']) . "</td>";
        echo "<td><pre>" . htmlspecialchars($row['payment_data']) . "</pre></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No tour costings with payment data found.<br>";
}

// Check for file upload directory
echo "<h3>File Upload Directory</h3>";
$upload_dir = 'uploads/receipts';
if (file_exists($upload_dir)) {
    echo "Directory exists: $upload_dir<br>";
    echo "Directory is writable: " . (is_writable($upload_dir) ? "Yes" : "No") . "<br>";
    
    // List files in directory
    echo "<h4>Files in directory:</h4>";
    $files = scandir($upload_dir);
    if (count($files) > 2) { // More than . and ..
        echo "<ul>";
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                echo "<li>" . htmlspecialchars($file) . " - " . filesize("$upload_dir/$file") . " bytes</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "No files found in directory.<br>";
    }
} else {
    echo "Directory does not exist: $upload_dir<br>";
}

// Check form enctype in edit_cost_file.php
echo "<h3>Form Configuration</h3>";
$edit_file = file_get_contents('edit_cost_file.php');
$new_file = file_get_contents('new_cost_file.php');

echo "edit_cost_file.php has enctype=\"multipart/form-data\": " . 
    (strpos($edit_file, 'enctype="multipart/form-data"') !== false ? "Yes" : "No") . "<br>";
echo "new_cost_file.php has enctype=\"multipart/form-data\": " . 
    (strpos($new_file, 'enctype="multipart/form-data"') !== false ? "Yes" : "No") . "<br>";

// Check if payment_data includes receipt field
echo "<h3>Payment Data Structure</h3>";
$sql = "SELECT payment_data FROM tour_costings WHERE payment_data IS NOT NULL AND payment_data != '{}' LIMIT 1";
$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $payment_data = json_decode($row['payment_data'], true);
    echo "Payment data keys: " . implode(", ", array_keys($payment_data)) . "<br>";
    echo "Has 'receipt' key: " . (isset($payment_data['receipt']) ? "Yes" : "No") . "<br>";
}

echo "<p>Debug completed.</p>";
?>