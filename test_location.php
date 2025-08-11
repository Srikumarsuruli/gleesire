<?php
echo "<h2>Test Location Detection</h2>";

$ip_address = $_SERVER['REMOTE_ADDR'];
echo "Your IP: $ip_address<br>";

// Test location API
$location_data = @json_decode(file_get_contents("http://ip-api.com/json/{$ip_address}"), true);

if($location_data) {
    echo "API Response: <pre>" . print_r($location_data, true) . "</pre>";
    $country = $location_data['country'] ?? 'Unknown';
    $city = $location_data['city'] ?? 'Unknown';
    echo "Location: $city, $country<br>";
} else {
    echo "❌ Location API failed<br>";
    // Fallback - just show IP location
    $country = "Server Location";
    $city = "Local Network";
    echo "Using fallback: $city, $country<br>";
}
?>