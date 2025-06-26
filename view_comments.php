<?php
// Initialize the session
session_start();

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Redirect to the new comments page
if(isset($_GET["id"])) {
    $id = $_GET["id"];
    $type = isset($_GET["type"]) ? $_GET["type"] : "enquiry";
    header("location: comments.php?id=$id&type=$type");
    exit;
} else {
    header("location: index.php");
    exit;
}
?>