<?php
require_once "config/database.php";

// Create visa_ticket_booking table
$sql = "CREATE TABLE IF NOT EXISTS visa_ticket_booking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cost_sheet_number VARCHAR(100),
    booking_date DATE,
    checkin_date DATE,
    checkout_date DATE,
    destination VARCHAR(255) NOT NULL,
    agent_type ENUM('Domestic', 'Outbound') DEFAULT 'Domestic',
    supplier VARCHAR(255) NOT NULL,
    supplier_name VARCHAR(255) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    availability_status ENUM('Available', 'Unavailable') DEFAULT 'Available',
    booking_status ENUM('Booking Confirmed', 'Amendment', 'Cancelation') DEFAULT 'Booking Confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Visa & ticket booking table created successfully!";
} else {
    echo "Error creating table: " . mysqli_error($conn);
}

mysqli_close($conn);
?>