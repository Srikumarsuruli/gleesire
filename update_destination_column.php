<?php
require_once "config/database.php";

// Get all indexes on the table
$result = mysqli_query($conn, "SHOW INDEX FROM converted_leads WHERE Column_name = 'destination_id'");
while ($row = mysqli_fetch_assoc($result)) {
    $index_name = $row['Key_name'];
    if ($index_name != 'PRIMARY') {
        mysqli_query($conn, "ALTER TABLE converted_leads DROP INDEX `$index_name`");
    }
}

// Now modify the column
if (mysqli_query($conn, "ALTER TABLE converted_leads MODIFY COLUMN destination_id VARCHAR(500) NULL")) {
    echo "SUCCESS: Database updated to support multiple destinations";
} else {
    echo "ERROR: " . mysqli_error($conn);
}
?>