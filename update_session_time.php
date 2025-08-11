<?php
session_start();
require_once "config/database.php";

if(isset($_SESSION["current_login_id"]) && isset($_SESSION["login_start_time"])) {
    $current_time = time();
    $session_duration = $current_time - $_SESSION["login_start_time"];
    
    // Update current session duration
    $sql = "UPDATE user_login_logs SET session_duration = ? WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $session_duration, $_SESSION["current_login_id"]);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    echo json_encode(['status' => 'success', 'duration' => $session_duration]);
} else {
    echo json_encode(['status' => 'error']);
}
?>