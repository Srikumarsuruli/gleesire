<?php
// Initialize the session
session_start();

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    if(isset($_POST["redirect"])) {
        header("location: " . $_POST["redirect"] . "&error=not_logged_in");
        exit;
    }
    
    $response = array(
        'success' => false,
        'message' => 'User not logged in'
    );
    echo json_encode($response);
    exit;
}

// Include database connection
require_once "config/database.php";

// Process request
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["enquiry_id"]) && isset($_POST["comment"])) {
    $enquiry_id = $_POST["enquiry_id"];
    $comment = $_POST["comment"];
    $user_id = $_SESSION["id"];
    
    // Validate input
    if(empty(trim($comment))) {
        if(isset($_POST["redirect"])) {
            header("location: " . $_POST["redirect"] . "&error=empty_comment");
            exit;
        }
        
        $response = array(
            'success' => false,
            'message' => 'Comment cannot be empty'
        );
        echo json_encode($response);
        exit;
    }
    
    // Prepare an insert statement
    $sql = "INSERT INTO comments (enquiry_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())";
    
    if($stmt = mysqli_prepare($conn, $sql)) {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "iis", $enquiry_id, $user_id, $comment);
        
        // Attempt to execute the prepared statement
        if(mysqli_stmt_execute($stmt)) {
            // Get the user's name
            $user_sql = "SELECT full_name FROM users WHERE id = ?";
            $user_stmt = mysqli_prepare($conn, $user_sql);
            mysqli_stmt_bind_param($user_stmt, "i", $user_id);
            mysqli_stmt_execute($user_stmt);
            $user_result = mysqli_stmt_get_result($user_stmt);
            $user_row = mysqli_fetch_assoc($user_result);
            $user_name = $user_row['full_name'];
            
            if(isset($_POST["redirect"])) {
                header("location: " . $_POST["redirect"] . "&success=1");
                exit;
            }
            
            // Return success response
            $response = array(
                'success' => true,
                'message' => 'Comment added successfully',
                'user_name' => $user_name,
                'comment' => htmlspecialchars($comment),
                'created_at' => date('d-m-Y H:i') // Already in IST due to timezone config
            );
            echo json_encode($response);
        } else {
            if(isset($_POST["redirect"])) {
                header("location: " . $_POST["redirect"] . "&error=execution_failed");
                exit;
            }
            
            $response = array(
                'success' => false,
                'message' => 'Something went wrong. Please try again later.'
            );
            echo json_encode($response);
        }
        
        // Close statement
        mysqli_stmt_close($stmt);
    } else {
        if(isset($_POST["redirect"])) {
            header("location: " . $_POST["redirect"] . "&error=prepare_failed");
            exit;
        }
        
        $response = array(
            'success' => false,
            'message' => 'Database error'
        );
        echo json_encode($response);
    }
} else {
    if(isset($_POST["redirect"])) {
        header("location: " . $_POST["redirect"] . "&error=invalid_request");
        exit;
    }
    
    $response = array(
        'success' => false,
        'message' => 'Invalid request'
    );
    echo json_encode($response);
}

// Close connection
mysqli_close($conn);
?>