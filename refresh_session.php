<?php
session_start();

// Check if user is logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    // Update last activity time
    $_SESSION['last_activity'] = time();
    echo "success";
} else {
    echo "error";
}
?>