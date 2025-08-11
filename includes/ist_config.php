<?php
/**
 * Simple IST Configuration Include
 * Add this to any PHP file that needs IST timezone
 */

// Ensure IST timezone is set
if (date_default_timezone_get() !== 'Asia/Kolkata') {
    date_default_timezone_set('Asia/Kolkata');
    ini_set('date.timezone', 'Asia/Kolkata');
}

// Set MySQL timezone to IST if database connection exists
if (isset($conn) && $conn) {
    mysqli_query($conn, "SET time_zone = '+05:30'");
}
?>