<?php
require_once "config/database.php";

echo "<h2>Updating Existing Records with Location</h2>";

// Get current IP and location
$ip_address = $_SERVER['REMOTE_ADDR'];
$location_data = @json_decode(file_get_contents("http://ip-api.com/json/{$ip_address}"), true);

if($location_data && $location_data['status'] == 'success') {
    $country = $location_data['country'];
    $city = $location_data['city'];
} else {
    $country = 'Office Location';
    $city = 'Local Network';
}

echo "Detected location: $city, $country<br>";

// Update all records that have NULL location
$sql = "UPDATE user_login_logs SET 
        ip_address = ?, 
        country = ?, 
        city = ? 
        WHERE country IS NULL OR country = 'Unknown'";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "sss", $ip_address, $country, $city);
    if(mysqli_stmt_execute($stmt)) {
        $affected = mysqli_affected_rows($conn);
        echo "✅ Updated $affected records with location data<br>";
    } else {
        echo "❌ Error updating records: " . mysqli_error($conn) . "<br>";
    }
    mysqli_stmt_close($stmt);
}

echo "<br><a href='user_logs.php'>Check User Logs</a>";
?>