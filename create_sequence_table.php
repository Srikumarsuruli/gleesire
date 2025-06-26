<?php
// Include database connection
require_once "includes/config/database.php";

// Create lead_sequence table
$create_table_sql = "CREATE TABLE IF NOT EXISTS lead_sequence (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    year INT(4) NOT NULL,
    month INT(2) NOT NULL,
    last_sequence INT(11) NOT NULL DEFAULT 0,
    UNIQUE KEY (year, month)
)";

if (mysqli_query($conn, $create_table_sql)) {
    echo "Table lead_sequence created successfully.<br>";
    
    // Check if current month exists
    $year = date('Y');
    $month = date('m');
    
    $check_sql = "SELECT * FROM lead_sequence WHERE year = $year AND month = $month";
    $result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($result) == 0) {
        // Insert current month with sequence 0
        $insert_sql = "INSERT INTO lead_sequence (year, month, last_sequence) VALUES ($year, $month, 0)";
        if (mysqli_query($conn, $insert_sql)) {
            echo "Current month sequence initialized.";
        } else {
            echo "Error initializing sequence: " . mysqli_error($conn);
        }
    } else {
        echo "Current month sequence already exists.";
    }
} else {
    echo "Error creating table: " . mysqli_error($conn);
}
?>