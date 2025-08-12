<?php
require_once "config/database.php";

$sql = "CREATE TABLE IF NOT EXISTS hotel_resorts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cost_sheet_number VARCHAR(100),
    booking_date DATE,
    checkin_date DATE,
    checkout_date DATE,
    destination VARCHAR(255),
    hotel_name VARCHAR(255),
    room_category VARCHAR(255),
    cp DECIMAL(10,2) DEFAULT 0,
    map_price DECIMAL(10,2) DEFAULT 0,
    eb_adult_cp DECIMAL(10,2) DEFAULT 0,
    eb_adult_map DECIMAL(10,2) DEFAULT 0,
    child_with_bed_cp DECIMAL(10,2) DEFAULT 0,
    child_with_bed_map DECIMAL(10,2) DEFAULT 0,
    child_without_bed_cp DECIMAL(10,2) DEFAULT 0,
    child_without_bed_map DECIMAL(10,2) DEFAULT 0,
    xmas_newyear_charges DECIMAL(10,2) DEFAULT 0,
    meal_type VARCHAR(100),
    adult_meal_charges DECIMAL(10,2) DEFAULT 0,
    kids_meal_charges DECIMAL(10,2) DEFAULT 0,
    availability_status VARCHAR(20) DEFAULT 'Available',
    booking_status VARCHAR(50) DEFAULT 'Booking Confirmed',
    status VARCHAR(20) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Hotel/Resorts table created successfully";
} else {
    echo "Error creating table: " . mysqli_error($conn);
}
?>