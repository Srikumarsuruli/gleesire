<?php
require_once "config/database.php";

$sql = "CREATE TABLE IF NOT EXISTS referral_codes (
    id INT(11) NOT NULL AUTO_INCREMENT,
    referral_code VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if (mysqli_query($conn, $sql)) {
    echo "Referral codes table created successfully";
} else {
    echo "Error creating table: " . mysqli_error($conn);
}
?>