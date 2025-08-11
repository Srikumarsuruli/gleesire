<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lead_management";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if column exists
$check_sql = "SHOW COLUMNS FROM converted_leads LIKE 'children_age_details'";
$result = $conn->query($check_sql);

if ($result->num_rows == 0) {
    // Column doesn't exist, add it
    $sql = "ALTER TABLE converted_leads ADD COLUMN children_age_details VARCHAR(255) NULL AFTER infants_count";
    
    if ($conn->query($sql) === TRUE) {
        echo "SUCCESS: Column 'children_age_details' has been added to the converted_leads table.";
    } else {
        echo "ERROR: " . $conn->error;
    }
} else {
    echo "Column 'children_age_details' already exists in the converted_leads table.";
}

$conn->close();
?>