<?php
require_once "config/database.php";

echo "<h3>Database Structure Check</h3>";

// Check enquiries table structure
echo "<h4>Enquiries Table Structure:</h4>";
$result = mysqli_query($conn, "DESCRIBE enquiries");
while($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . " - " . $row['Type'] . " - Key: " . $row['Key'] . "<br>";
}

echo "<br><h4>Current Enquiry Data (ID 66):</h4>";
$sql = "SELECT * FROM enquiries WHERE id = 66";
$result = mysqli_query($conn, $sql);
if($row = mysqli_fetch_assoc($result)) {
    foreach($row as $key => $value) {
        echo "$key: $value<br>";
    }
} else {
    echo "No enquiry found with ID 66<br>";
}

echo "<br><h4>Manual Update Test:</h4>";
$test_sql = "UPDATE enquiries SET status_id = 13 WHERE id = 66";
if(mysqli_query($conn, $test_sql)) {
    echo "Manual update successful. Affected rows: " . mysqli_affected_rows($conn) . "<br>";
    
    // Check the result
    $check_sql = "SELECT status_id FROM enquiries WHERE id = 66";
    $check_result = mysqli_query($conn, $check_sql);
    $check_row = mysqli_fetch_assoc($check_result);
    echo "Current status_id after manual update: " . $check_row['status_id'] . "<br>";
} else {
    echo "Manual update failed: " . mysqli_error($conn) . "<br>";
}
?>