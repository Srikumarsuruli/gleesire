<?php
require_once "config/database.php";

$sql = "CREATE TABLE IF NOT EXISTS enquiry_status (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if (mysqli_query($conn, $sql)) {
    echo "Enquiry status table created successfully<br>";
    
    // Insert the predefined enquiry status values
    $enquiry_status_values = [
        "Hot Prospect - Pipeline",
        "Prospect - Quote given",
        "Prospect - Attended",
        "Prospect - Awaiting Rate from Agent",
        "Neutral Prospect - In Discussion",
        "Future Hot Prospect - Quote Given (with delay)",
        "Future Prospect - Postponed",
        "Call Back - Call Back Scheduled",
        "Re-Opened - Re-Engaged Lead",
        "Re-Assigned - Transferred Lead",
        "Not Connected - No Response",
        "Not Interested - Cancelled",
        "Junk - Junk",
        "Duplicate - Duplicate",
        "Closed – Booked",
        "Change Request – Active Amendment",
        "Booking Value - Sale Amount"
    ];
    
    $insert_sql = "INSERT INTO enquiry_status (name) VALUES (?)";
    if ($stmt = mysqli_prepare($conn, $insert_sql)) {
        foreach ($enquiry_status_values as $value) {
            mysqli_stmt_bind_param($stmt, "s", $value);
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);
        echo "Enquiry status data inserted successfully";
    }
} else {
    echo "Error creating table: " . mysqli_error($conn);
}
?>