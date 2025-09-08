<?php
echo "PHP Version: " . phpversion() . "\n";

// Check MySQL version
$mysqli = new mysqli("localhost", "root", "", "");
if (!$mysqli->connect_error) {
    $result = $mysqli->query("SELECT VERSION() as version");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "MySQL Version: " . $row['version'] . "\n";
    }
    $mysqli->close();
} else {
    echo "MySQL connection failed\n";
}
?>