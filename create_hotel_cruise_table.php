<?php
require_once "config/database.php";

// Create hotel_cruise_details table
$sql = "CREATE TABLE IF NOT EXISTS hotel_cruise_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cost_sheet_number VARCHAR(100),
    booking_date DATE,
    checkin_date DATE,
    checkout_date DATE,
    destination VARCHAR(255) NOT NULL,
    cruise_details TEXT NOT NULL,
    name VARCHAR(255) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    department VARCHAR(100) NOT NULL,
    adult_price DECIMAL(10,2) DEFAULT 0.00,
    kids_price DECIMAL(10,2) DEFAULT 0.00,
    kids_price_available ENUM('Available', 'Unavailable') DEFAULT 'Available',
    cancelation_availability ENUM('Available', 'Unavailable') DEFAULT 'Available',
    booking_status ENUM('Booking Confirmed', 'Amendment', 'Cancelation') DEFAULT 'Booking Confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Hotel cruise details table created successfully!";
} else {
    echo "Error creating table: " . mysqli_error($conn);
}

mysqli_close($conn);
?>