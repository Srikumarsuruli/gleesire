<?php
require_once "config/database.php";

$sql = "CREATE TABLE IF NOT EXISTS accommodation_details (
    id INT(11) NOT NULL AUTO_INCREMENT,
    destination VARCHAR(255) NOT NULL,
    hotel_name VARCHAR(255) NOT NULL,
    room_category VARCHAR(255) NOT NULL,
    cp DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    map_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    eb_adult_cp DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    eb_adult_map DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    child_with_bed_cp DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    child_with_bed_map DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    child_without_bed_cp DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    child_without_bed_map DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    xmas_newyear_charges DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    meal_type VARCHAR(255) DEFAULT NULL,
    meal_charges DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    validity_from DATE NOT NULL,
    validity_to DATE NOT NULL,
    remark TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if (mysqli_query($conn, $sql)) {
    echo "Accommodation details table created successfully";
} else {
    echo "Error creating table: " . mysqli_error($conn);
}
?>