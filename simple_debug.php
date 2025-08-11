<?php
session_start();
require_once "config/database.php";

if(!isset($_SESSION["loggedin"])) {
    echo "Not logged in";
    exit;
}

echo "Current User: " . $_SESSION['username'] . " (ID: " . $_SESSION['id'] . ")<br><br>";

// Check notifications table exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'notifications'");
if(mysqli_num_rows($result) == 0) {
    echo "ERROR: notifications table does not exist!<br>";
    exit;
}

// Check all notifications
$sql = "SELECT id, user_id, message, is_read, created_at FROM notifications ORDER BY created_at DESC LIMIT 5";
$result = mysqli_query($conn, $sql);

echo "Total notifications in database: " . mysqli_num_rows($result) . "<br><br>";

if(mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        echo "ID: " . $row['id'] . " | User ID: " . $row['user_id'] . " | Message: " . $row['message'] . " | Read: " . ($row['is_read'] ? 'Yes' : 'No') . " | Created: " . $row['created_at'] . "<br>";
    }
} else {
    echo "No notifications found in database<br>";
}

echo "<br><a href='test_notification.php'>Back to Test</a>";
?>