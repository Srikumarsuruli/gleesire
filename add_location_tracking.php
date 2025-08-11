<?php
require_once "config/database.php";

echo "<h2>Adding Location Tracking</h2>";

// Add location columns to user_login_logs table
$sql = "ALTER TABLE user_login_logs 
        ADD COLUMN ip_address VARCHAR(45) NULL,
        ADD COLUMN country VARCHAR(100) NULL,
        ADD COLUMN city VARCHAR(100) NULL,
        ADD COLUMN user_agent TEXT NULL";

if(mysqli_query($conn, $sql)) {
    echo "✅ Location columns added successfully<br>";
} else {
    echo "❌ Error adding columns: " . mysqli_error($conn) . "<br>";
}

echo "Setup completed!";
?>