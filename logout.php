<?php
// Initialize the session
session_start();

require_once "config/database.php";

// Log logout time if user was logged in
if(isset($_SESSION["current_login_id"]) && isset($_SESSION["id"])) {
    $logout_time = date('Y-m-d H:i:s');
    $login_id = $_SESSION["current_login_id"];
    $login_start = $_SESSION["login_start_time"] ?? time();
    $session_duration = time() - $login_start;
    
    $sql = "UPDATE user_login_logs SET logout_time = ?, session_duration = ? WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "sii", $logout_time, $session_duration, $login_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
 
// Unset all of the session variables
$_SESSION = array();
 
// Destroy the session.
session_destroy();
 
// Redirect to login page
header("location: login.php");
exit;
?>