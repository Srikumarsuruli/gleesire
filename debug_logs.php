<?php
require_once "config/database.php";

echo "<h2>Debug User Logs System</h2>";

// Check if table exists
$check_table = "SHOW TABLES LIKE 'user_login_logs'";
$result = mysqli_query($conn, $check_table);

if(mysqli_num_rows($result) == 0) {
    echo "❌ Table 'user_login_logs' does not exist<br>";
    echo "Creating table now...<br>";
    
    $create_sql = "CREATE TABLE user_login_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        login_time DATETIME NOT NULL,
        logout_time DATETIME NULL,
        session_duration INT DEFAULT 0,
        date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if(mysqli_query($conn, $create_sql)) {
        echo "✅ Table created successfully<br>";
    } else {
        echo "❌ Error creating table: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "✅ Table 'user_login_logs' exists<br>";
}

// Check current data
$data_sql = "SELECT COUNT(*) as count FROM user_login_logs";
$data_result = mysqli_query($conn, $data_sql);
$data_row = mysqli_fetch_assoc($data_result);
echo "📊 Records in table: " . $data_row['count'] . "<br>";

// Check active sessions
$active_sql = "SELECT l.*, u.username FROM user_login_logs l JOIN users u ON l.user_id = u.id WHERE l.logout_time IS NULL";
$active_result = mysqli_query($conn, $active_sql);
echo "🟢 Active sessions: " . mysqli_num_rows($active_result) . "<br>";

if(mysqli_num_rows($active_result) > 0) {
    echo "<h3>Active Sessions:</h3>";
    while($row = mysqli_fetch_assoc($active_result)) {
        echo "User: " . $row['username'] . " - Login: " . $row['login_time'] . "<br>";
    }
}

echo "<br><a href='user_logs.php'>Go to User Logs</a>";
?>