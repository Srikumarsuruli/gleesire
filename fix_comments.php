<?php
// This script displays and fixes the comments display issue

// Initialize the session
session_start();

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo "User not logged in";
    exit;
}

// Include database connection
require_once "config/database.php";

echo "<h1>Comments Diagnostic Tool</h1>";

// Get all enquiries with comments
$sql = "SELECT e.id, e.lead_number, e.customer_name, COUNT(c.id) as comment_count 
        FROM enquiries e 
        LEFT JOIN comments c ON e.id = c.enquiry_id 
        GROUP BY e.id 
        HAVING comment_count > 0 
        ORDER BY comment_count DESC";

$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    echo "<h2>Enquiries with Comments</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Enquiry ID</th><th>Lead Number</th><th>Customer</th><th>Comment Count</th><th>Action</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['lead_number']) . "</td>";
        echo "<td>" . htmlspecialchars($row['customer_name']) . "</td>";
        echo "<td>" . $row['comment_count'] . "</td>";
        echo "<td><a href='direct_comments.php?id=" . $row['id'] . "' target='_blank'>View Comments</a></td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No enquiries with comments found.</p>";
}

// Close connection
mysqli_close($conn);
?>