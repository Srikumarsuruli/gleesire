<?php
// Initialize the session
session_start();

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo "User not logged in";
    exit;
}

// Include database connection
require_once "config/database.php";

// Check if enquiry ID is provided
if(isset($_GET["id"])) {
    $enquiry_id = $_GET["id"];
    
    // Get comments for this enquiry
    $comments_sql = "SELECT c.*, u.full_name as user_name 
                    FROM comments c 
                    JOIN users u ON c.user_id = u.id 
                    WHERE c.enquiry_id = ? 
                    ORDER BY c.created_at DESC";
    
    if($comments_stmt = mysqli_prepare($conn, $comments_sql)) {
        mysqli_stmt_bind_param($comments_stmt, "i", $enquiry_id);
        mysqli_stmt_execute($comments_stmt);
        $comments_result = mysqli_stmt_get_result($comments_stmt);
        
        echo "<h4>Comments for Enquiry ID: $enquiry_id</h4>";
        
        if(mysqli_num_rows($comments_result) > 0) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>User</th><th>Comment</th><th>Date</th></tr>";
            
            while($comment = mysqli_fetch_assoc($comments_result)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($comment['user_name']) . "</td>";
                echo "<td>" . nl2br(htmlspecialchars($comment['comment'])) . "</td>";
                echo "<td>" . date('d-m-Y H:i', strtotime($comment['created_at'])) . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>No comments found for this enquiry.</p>";
        }
        
        mysqli_stmt_close($comments_stmt);
    } else {
        echo "Error preparing statement";
    }
} else {
    echo "No enquiry ID provided";
}

// Close connection
mysqli_close($conn);
?>