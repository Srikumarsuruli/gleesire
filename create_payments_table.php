<?php
// Include database connection
require_once "config/database.php";

// Check if the table already exists
$table_exists = mysqli_query($conn, "SHOW TABLES LIKE 'payments'");
if(mysqli_num_rows($table_exists) == 0) {
    // Create the payments table
    $sql = "CREATE TABLE payments (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        cost_file_id INT(11) NOT NULL,
        payment_date DATE NOT NULL,
        payment_bank VARCHAR(100) NOT NULL,
        payment_amount DECIMAL(10,2) NOT NULL,
        payment_receipt VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (cost_file_id) REFERENCES tour_costings(id) ON DELETE CASCADE
    )";
    
    if(mysqli_query($conn, $sql)) {
        echo "Payments table created successfully";
    } else {
        echo "Error creating payments table: " . mysqli_error($conn);
    }
} else {
    echo "Payments table already exists";
}
?>