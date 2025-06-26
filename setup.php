<?php
// Include database configuration
require_once "config/database.php";

// Include database initialization script
require_once "config/init_tables.php";

// Redirect to login page
header("location: login.php");
exit;
?>