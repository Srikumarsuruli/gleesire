<?php
require_once "config/database.php";

// Create transport_details table
$sql = "CREATE TABLE IF NOT EXISTS transport_details (
    id INT(11) NOT NULL AUTO_INCREMENT,
    destination VARCHAR(255) NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255) NOT NULL,
    mobile VARCHAR(20) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    vehicle VARCHAR(255) NOT NULL,
    daily_rent DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    rate_per_km DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if (mysqli_query($conn, $sql)) {
    echo "Transport details table created successfully";
} else {
    echo "Error creating table: " . mysqli_error($conn);
}
?>