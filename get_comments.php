<?php
// Initialize the session
session_start();

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    $response = array(
        'success' => false,
        'message' => 'User not logged in'
    );
    echo json_encode($response);
    exit;
}

// Include database connection
require_once "config/database.php";

// Process GET request
if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["enquiry_id"])) {
    $enquiry_id = $_GET["enquiry_id"];
    
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
        
        $comments = array();
        while($comment = mysqli_fetch_assoc($comments_result)) {
            $comments[] = array(
                'id' => $comment['id'],
                'user_name' => $comment['user_name'],
                'comment' => htmlspecialchars($comment['comment']),
                'created_at' => date('d-m-Y H:i', strtotime($comment['created_at']))
            );
        }
        
        $response = array(
            'success' => true,
            'comments' => $comments
        );
        
        echo json_encode($response);
        mysqli_stmt_close($comments_stmt);
    } else {
        $response = array(
            'success' => false,
            'message' => 'Database error'
        );
        echo json_encode($response);
    }
} else {
    $response = array(
        'success' => false,
        'message' => 'Invalid request'
    );
    echo json_encode($response);
}

// Close connection
mysqli_close($conn);
?>