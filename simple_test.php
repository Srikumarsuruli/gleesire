<?php
require_once "config/database.php";

$enquiry_id = 183;

echo "Testing converted_leads query for enquiry_id: $enquiry_id\n\n";

$sql = "SELECT * FROM converted_leads WHERE enquiry_id = $enquiry_id";
$result = mysqli_query($conn, $sql);

if($result) {
    echo "Query executed successfully\n";
    echo "Number of rows: " . mysqli_num_rows($result) . "\n\n";
    
    if($row = mysqli_fetch_assoc($result)) {
        echo "Converted lead data found:\n";
        foreach($row as $key => $value) {
            echo "$key: " . ($value ?? 'NULL') . "\n";
        }
    } else {
        echo "No converted lead data found\n";
    }
} else {
    echo "Query failed: " . mysqli_error($conn) . "\n";
}
?>