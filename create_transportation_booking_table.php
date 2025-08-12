<?php
require_once "config/database.php";

// Create transportation_booking table
$sql = "CREATE TABLE IF NOT EXISTS transportation_booking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cost_sheet_number VARCHAR(100),
    booking_date DATE,
    checkin_date DATE,
    checkout_date DATE,
    destination VARCHAR(255) NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255) NOT NULL,
    mobile VARCHAR(20) NOT NULL,
    vehicle VARCHAR(255) NOT NULL,
    daily_rent DECIMAL(10,2) DEFAULT 0.00,
    rate_per_km DECIMAL(10,2) DEFAULT 0.00,
    availability_status ENUM('Available', 'Unavailable') DEFAULT 'Available',
    booking_status ENUM('Booking Confirmed', 'Amendment', 'Cancelation') DEFAULT 'Booking Confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Transportation booking table created successfully!";
} else {
    echo "Error creating table: " . mysqli_error($conn);
}

mysqli_close($conn);
?>