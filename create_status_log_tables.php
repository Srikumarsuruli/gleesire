<?php
require_once "includes/config.php";

// Create status change log table for enquiries
$create_enquiry_log_sql = "CREATE TABLE IF NOT EXISTS status_change_log (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    enquiry_id INT(11) NOT NULL,
    old_status_id INT(11),
    new_status_id INT(11) NOT NULL,
    changed_by INT(11) NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (enquiry_id) REFERENCES enquiries(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE CASCADE
)";

// Create lead status change log table
$create_lead_log_sql = "CREATE TABLE IF NOT EXISTS lead_status_change_log (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    enquiry_id INT(11) NOT NULL,
    old_status VARCHAR(100),
    new_status VARCHAR(100) NOT NULL,
    changed_by INT(11) NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (enquiry_id) REFERENCES enquiries(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE CASCADE
)";

// Execute table creation
if(mysqli_query($conn, $create_enquiry_log_sql)) {
    echo "Enquiry status change log table created successfully.<br>";
} else {
    echo "Error creating enquiry status change log table: " . mysqli_error($conn) . "<br>";
}

if(mysqli_query($conn, $create_lead_log_sql)) {
    echo "Lead status change log table created successfully.<br>";
} else {
    echo "Error creating lead status change log table: " . mysqli_error($conn) . "<br>";
}

echo "Database setup complete. You can now delete this file.";
?>