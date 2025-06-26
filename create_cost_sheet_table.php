<?php
require_once "includes/db_connect.php";

// Create cost_sheets table
$sql = "CREATE TABLE IF NOT EXISTS cost_sheets (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    enquiry_id INT(11) NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    enquiry_number VARCHAR(50) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'USD',
    total_expense DECIMAL(15,2) NOT NULL DEFAULT 0,
    package_cost DECIMAL(15,2) NOT NULL DEFAULT 0,
    markup_percentage DECIMAL(10,2) NOT NULL DEFAULT 0,
    tax_percentage DECIMAL(10,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    final_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    services_data LONGTEXT,
    payment_data LONGTEXT,
    created_by INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enquiry_id) REFERENCES enquiries(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $sql)) {
    echo "Table cost_sheets created successfully";
} else {
    echo "Error creating table: " . mysqli_error($conn);
}

mysqli_close($conn);
?>