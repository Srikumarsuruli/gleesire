<?php
// Include database connection
require_once "includes/config/database.php";

// Create lead_status_map table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS lead_status_map (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    enquiry_id INT(11) NOT NULL,
    status_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (enquiry_id)
)";

if(mysqli_query($conn, $create_table_sql)) {
    echo "Table created successfully or already exists.";
} else {
    echo "Error creating table: " . mysqli_error($conn);
}

// Close connection
mysqli_close($conn);
?>