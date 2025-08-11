<?php
// Test search functionality
session_start();

// Include database connection
require_once "config/database.php";

// Test database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

echo "Database connection: OK<br>";

// Test search query
$search_query = "test";
$search_param = "%" . $search_query . "%";

// Test enquiries table
$sql = "SELECT COUNT(*) as count FROM enquiries";
$result = mysqli_query($conn, $sql);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "Total enquiries: " . $row['count'] . "<br>";
} else {
    echo "Error querying enquiries: " . mysqli_error($conn) . "<br>";
}

// Test search in enquiries
$sql = "SELECT id, lead_number, customer_name FROM enquiries LIMIT 5";
$result = mysqli_query($conn, $sql);
if ($result) {
    echo "Sample enquiries:<br>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- ID: " . $row['id'] . ", Lead: " . $row['lead_number'] . ", Name: " . $row['customer_name'] . "<br>";
    }
} else {
    echo "Error fetching sample enquiries: " . mysqli_error($conn) . "<br>";
}

// Test JavaScript inclusion
echo "<br>Testing JavaScript:<br>";
echo '<script>console.log("Search test script loaded");</script>';
echo '<div id="test-search">Search functionality test</div>';
?>