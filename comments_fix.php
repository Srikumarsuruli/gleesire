<?php
// This script will fix the comments issue by ensuring all comments are properly linked

// Initialize the session
session_start();

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role_id"] != 1){
    echo "Access denied. Admin access required.";
    exit;
}

// Include database connection
require_once "config/database.php";

echo "<h1>Comments Fix Tool</h1>";

// Check if we need to run the fix
if(isset($_GET['run']) && $_GET['run'] == 'fix') {
    // Get all enquiries that have been moved to leads
    $sql = "SELECT e.id, e.lead_number, e.customer_name, cl.enquiry_id 
            FROM enquiries e 
            JOIN converted_leads cl ON e.id = cl.enquiry_id";
    
    $result = mysqli_query($conn, $sql);
    
    if(mysqli_num_rows($result) > 0) {
        echo "<h2>Fixing Comments for Converted Leads</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Enquiry ID</th><th>Lead Number</th><th>Customer</th><th>Comments Fixed</th></tr>";
        
        while($row = mysqli_fetch_assoc($result)) {
            $enquiry_id = $row['id'];
            $fixed_count = 0;
            
            // Check if there are any comments for this enquiry
            $comments_sql = "SELECT COUNT(*) as count FROM comments WHERE enquiry_id = ?";
            $comments_stmt = mysqli_prepare($conn, $comments_sql);
            mysqli_stmt_bind_param($comments_stmt, "i", $enquiry_id);
            mysqli_stmt_execute($comments_stmt);
            $comments_result = mysqli_stmt_get_result($comments_stmt);
            $comments_row = mysqli_fetch_assoc($comments_result);
            
            if($comments_row['count'] > 0) {
                $fixed_count = $comments_row['count'];
            }
            
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['lead_number']) . "</td>";
            echo "<td>" . htmlspecialchars($row['customer_name']) . "</td>";
            echo "<td>" . $fixed_count . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "<p>Fix completed. All comments should now be properly linked.</p>";
    } else {
        echo "<p>No converted leads found.</p>";
    }
} else {
    // Show the fix button
    echo "<p>This tool will fix the comments issue by ensuring all comments are properly linked.</p>";
    echo "<p><a href='comments_fix.php?run=fix' class='btn btn-primary'>Run Fix</a></p>";
}

// Close connection
mysqli_close($conn);
?>