<?php
session_start();
require_once "config/database.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    die("Please login first");
}

echo "<h2>Fix Current Session</h2>";

$user_id = $_SESSION["id"];
$login_time = date('Y-m-d H:i:s');
$login_date = date('Y-m-d');

// Check if session already exists
$check_sql = "SELECT id FROM user_login_logs WHERE user_id = ? AND date = ? AND logout_time IS NULL";
if($check_stmt = mysqli_prepare($conn, $check_sql)) {
    mysqli_stmt_bind_param($check_stmt, "is", $user_id, $login_date);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    
    if(mysqli_num_rows($result) > 0) {
        echo "✅ Session already exists for today<br>";
        $row = mysqli_fetch_assoc($result);
        $_SESSION["current_login_id"] = $row['id'];
    } else {
        // Create new session
        $insert_sql = "INSERT INTO user_login_logs (user_id, login_time, date) VALUES (?, ?, ?)";
        if($insert_stmt = mysqli_prepare($conn, $insert_sql)) {
            mysqli_stmt_bind_param($insert_stmt, "iss", $user_id, $login_time, $login_date);
            if(mysqli_stmt_execute($insert_stmt)) {
                $_SESSION["current_login_id"] = mysqli_insert_id($conn);
                echo "✅ New session created<br>";
            } else {
                echo "❌ Error creating session: " . mysqli_error($conn) . "<br>";
            }
            mysqli_stmt_close($insert_stmt);
        }
    }
    mysqli_stmt_close($check_stmt);
}

$_SESSION["login_start_time"] = time();

echo "User: " . $_SESSION["username"] . "<br>";
echo "Session ID: " . $_SESSION["current_login_id"] . "<br>";
echo "Login time set to: " . $login_time . "<br>";
echo "<br><a href='user_logs.php'>Check User Logs</a>";
?>