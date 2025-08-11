<?php
session_start();
require_once "config/database.php";

echo "<h2>Test Login Tracking</h2>";

if(!isset($_SESSION["loggedin"])) {
    echo "❌ Not logged in<br>";
    exit;
}

echo "✅ User logged in: " . $_SESSION["username"] . "<br>";
echo "User ID: " . $_SESSION["id"] . "<br>";

// Check if login tracking variables exist
if(isset($_SESSION["current_login_id"])) {
    echo "✅ Current login ID: " . $_SESSION["current_login_id"] . "<br>";
} else {
    echo "❌ No current_login_id in session<br>";
    
    // Manually create login record
    $user_id = $_SESSION["id"];
    $login_time = date('Y-m-d H:i:s');
    $login_date = date('Y-m-d');
    
    $sql = "INSERT INTO user_login_logs (user_id, login_time, date) VALUES (?, ?, ?)";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "iss", $user_id, $login_time, $login_date);
        if(mysqli_stmt_execute($stmt)) {
            $_SESSION["current_login_id"] = mysqli_insert_id($conn);
            $_SESSION["login_start_time"] = time();
            echo "✅ Created login record: " . $_SESSION["current_login_id"] . "<br>";
        } else {
            echo "❌ Error creating login record: " . mysqli_error($conn) . "<br>";
        }
        mysqli_stmt_close($stmt);
    }
}

if(isset($_SESSION["login_start_time"])) {
    echo "✅ Login start time: " . date('Y-m-d H:i:s', $_SESSION["login_start_time"]) . "<br>";
} else {
    echo "❌ No login_start_time in session<br>";
    $_SESSION["login_start_time"] = time();
    echo "✅ Set login_start_time to now<br>";
}

// Check database records
$sql = "SELECT * FROM user_login_logs WHERE user_id = ? ORDER BY login_time DESC LIMIT 5";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    echo "<h3>Recent login records:</h3>";
    while($row = mysqli_fetch_assoc($result)) {
        echo "ID: " . $row['id'] . " - Login: " . $row['login_time'] . " - Logout: " . ($row['logout_time'] ?? 'Active') . "<br>";
    }
    mysqli_stmt_close($stmt);
}

echo "<br><a href='user_logs.php'>Check User Logs</a>";
?>