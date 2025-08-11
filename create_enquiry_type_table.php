<?php
require_once "config/database.php";

$sql = "CREATE TABLE IF NOT EXISTS enquiry_types (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if (mysqli_query($conn, $sql)) {
    echo "Enquiry types table created successfully<br>";
    
    // Insert the predefined enquiry types
    $enquiry_types = [
        'Advertisement Enquiry',
        'Budget Travel Request',
        'Collaboration',
        'Corporate Tour Request',
        'Cruise Enquiry',
        'Cruise Plan (Lakshadweep)',
        'DMCs',
        'Early Bird Offer Enquiry',
        'Family Tour Package',
        'Flight + Hotel Combo Request',
        'Group Tour Enquiry',
        'Honeymoon Package Enquiry',
        'Job Enquiry',
        'Just Hotel Booking Enquiry',
        'Luxury Travel Enquiry',
        'Medical Tourism Enquiry',
        'Need Train + Bus Tickets',
        'Only Tickets',
        'Religious Tour Enquiry',
        'School / College Tour Enquiry',
        'Sightseeing Only Request',
        'Solo Travel Enquiry',
        'Sponsorship',
        'Ticket Enquiry',
        'Travel Insurance Required',
        'Visa Assistance Enquiry',
        'Vloggers / Influencers',
        'Weekend Getaway Enquiry'
    ];
    
    $insert_sql = "INSERT INTO enquiry_types (name) VALUES (?)";
    if ($stmt = mysqli_prepare($conn, $insert_sql)) {
        foreach ($enquiry_types as $type) {
            mysqli_stmt_bind_param($stmt, "s", $type);
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);
        echo "Enquiry types data inserted successfully";
    }
} else {
    echo "Error creating table: " . mysqli_error($conn);
}
?>