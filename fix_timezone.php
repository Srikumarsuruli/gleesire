<?php
require_once "config/database.php";

echo "<h2>Setting India Timezone for Entire Project</h2>";

// Set PHP timezone
date_default_timezone_set('Asia/Kolkata');
echo "✅ PHP timezone set to Asia/Kolkata<br>";

// Set MySQL timezone
$sql = "SET time_zone = '+05:30'";
if(mysqli_query($conn, $sql)) {
    echo "✅ MySQL timezone set to +05:30 (India)<br>";
} else {
    echo "❌ Error setting MySQL timezone: " . mysqli_error($conn) . "<br>";
}

// Show current times
echo "<br><strong>Current Times:</strong><br>";
echo "PHP time: " . date('Y-m-d H:i:s') . "<br>";

$sql = "SELECT NOW() as mysql_time";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
echo "MySQL time: " . $row['mysql_time'] . "<br>";

echo "<br>✅ Timezone configuration completed!";
?>