<?php
require_once "config/database.php";

$sql = "CREATE TABLE IF NOT EXISTS notifications (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    enquiry_id INT(11) NOT NULL,
    enquiry_number VARCHAR(50) NOT NULL,
    lead_number VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (enquiry_id) REFERENCES enquiries(id)
)";

if(mysqli_query($conn, $sql)) {
    echo "Notifications table created successfully";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>