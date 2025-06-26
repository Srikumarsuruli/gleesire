<?php
// Include database connection
require_once "includes/header.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Check if user has privilege to delete marketing data
if(!hasPrivilege('delete_marketing_data') && $_SESSION["role_id"] != 1) {
    header("location: index.php");
    exit;
}

// Check if file_id is provided
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["file_id"]) && !empty($_POST["file_id"])) {
    $file_id = $_POST["file_id"];
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Delete marketing data first (foreign key constraint)
        $delete_data_sql = "DELETE FROM marketing_data WHERE file_id = ?";
        $delete_data_stmt = mysqli_prepare($conn, $delete_data_sql);
        mysqli_stmt_bind_param($delete_data_stmt, "i", $file_id);
        mysqli_stmt_execute($delete_data_stmt);
        
        // Delete file record
        $delete_file_sql = "DELETE FROM marketing_files WHERE id = ?";
        $delete_file_stmt = mysqli_prepare($conn, $delete_file_sql);
        mysqli_stmt_bind_param($delete_file_stmt, "i", $file_id);
        mysqli_stmt_execute($delete_file_stmt);
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Redirect with success message
        header("location: upload_marketing_data.php?deleted=1");
        exit;
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        
        // Redirect with error message
        header("location: upload_marketing_data.php?error=1");
        exit;
    }
} else {
    // Invalid request
    header("location: upload_marketing_data.php?error=2");
    exit;
}
?>