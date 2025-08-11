<?php
// Include database connection
require_once "config/database.php";

// Check if the table already exists
$table_exists = mysqli_query($conn, "SHOW TABLES LIKE 'payment_receipts'");
if(mysqli_num_rows($table_exists) == 0) {
    // Create the payment_receipts table
    $sql = "CREATE TABLE payment_receipts (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        payment_id INT(11) NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        file_name VARCHAR(100) NOT NULL,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
    )";
    
    if(mysqli_query($conn, $sql)) {
        echo "Payment receipts table created successfully";
    } else {
        echo "Error creating payment receipts table: " . mysqli_error($conn);
    }
} else {
    echo "Payment receipts table already exists";
}

// Check if payments table exists
$payments_table_exists = mysqli_query($conn, "SHOW TABLES LIKE 'payments'");
if(mysqli_num_rows($payments_table_exists) == 0) {
    echo "<br>Warning: The payments table does not exist. Please run the <a href='create_payments_table.php'>create_payments_table.php</a> script first.";
}
?>