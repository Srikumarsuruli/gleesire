<?php
require_once "config/database.php";

$sql = "CREATE TABLE IF NOT EXISTS user_login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    login_time DATETIME NOT NULL,
    logout_time DATETIME NULL,
    session_duration INT DEFAULT 0,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if(mysqli_query($conn, $sql)) {
    echo "User login logs table created successfully.<br>";
} else {
    echo "Error: " . mysqli_error($conn) . "<br>";
}

echo "Setup completed!";
?>