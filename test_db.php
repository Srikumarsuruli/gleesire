<?php
// Include database connection
require_once "includes/db_connect.php";

// Check connection
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}

echo "<h1>Database Connection Test</h1>";
echo "<p>Database connection successful!</p>";

// Check lead_status_map table structure
$table_sql = "DESCRIBE lead_status_map";
$table_result = mysqli_query($conn, $table_sql);

if (!$table_result) {
    echo "<p>Error checking table structure: " . mysqli_error($conn) . "</p>";
} else {
    echo "<h2>lead_status_map Table Structure</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = mysqli_fetch_assoc($table_result)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Check for any existing records
$records_sql = "SELECT * FROM lead_status_map LIMIT 10";
$records_result = mysqli_query($conn, $records_sql);

if (!$records_result) {
    echo "<p>Error checking records: " . mysqli_error($conn) . "</p>";
} else {
    echo "<h2>Sample Records</h2>";
    
    if (mysqli_num_rows($records_result) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Enquiry ID</th><th>Status Name</th><th>Created At</th></tr>";
        
        while ($row = mysqli_fetch_assoc($records_result)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['enquiry_id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['status_name']) . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No records found in lead_status_map table.</p>";
    }
}
?>