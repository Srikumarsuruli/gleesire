<?php
// Test IST timezone configuration
require_once "config/timezone.php";

echo "<h2>IST Time Test</h2>";
echo "<p><strong>Current IST Date & Time:</strong> " . getCurrentISTDateTime() . "</p>";
echo "<p><strong>Current IST Date:</strong> " . getCurrentISTDate() . "</p>";
echo "<p><strong>Current IST Time:</strong> " . getCurrentISTTime() . "</p>";
echo "<p><strong>Formatted IST DateTime:</strong> " . getCurrentISTDateTime('d-m-Y H:i:s') . "</p>";
echo "<p><strong>PHP Timezone:</strong> " . date_default_timezone_get() . "</p>";
echo "<p><strong>Server Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Test database timezone
require_once "config/database.php";
$result = mysqli_query($conn, "SELECT NOW() as server_time, @@session.time_zone as mysql_timezone");
if ($row = mysqli_fetch_assoc($result)) {
    echo "<p><strong>MySQL Server Time:</strong> " . $row['server_time'] . "</p>";
    echo "<p><strong>MySQL Timezone:</strong> " . $row['mysql_timezone'] . "</p>";
}
?>