<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: " . $_POST["redirect"] . "&error=not_logged_in");
    exit;
}

require_once "config/database.php";

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["enquiry_id"]) && isset($_FILES["attachment"])) {
    $enquiry_id = $_POST["enquiry_id"];
    $user_id = $_SESSION["id"];
    $redirect = $_POST["redirect"];
    
    $file = $_FILES["attachment"];
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if($file["error"] !== UPLOAD_ERR_OK) {
        header("location: $redirect&error=upload_failed");
        exit;
    }
    
    if(!in_array($file["type"], $allowed_types)) {
        header("location: $redirect&error=invalid_file_type");
        exit;
    }
    
    if($file["size"] > $max_size) {
        header("location: $redirect&error=file_too_large");
        exit;
    }
    
    $upload_dir = "comment_attachment/";
    $file_extension = pathinfo($file["name"], PATHINFO_EXTENSION);
    $new_filename = uniqid() . "_" . time() . "." . $file_extension;
    $file_path = $upload_dir . $new_filename;
    
    if(move_uploaded_file($file["tmp_name"], $file_path)) {
        $file_type = (strpos($file["type"], 'image') !== false) ? 'image' : 'pdf';
        
        $sql = "INSERT INTO comment_attachments (enquiry_id, user_id, original_name, file_path, file_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "iisss", $enquiry_id, $user_id, $file["name"], $file_path, $file_type);
            
            if(mysqli_stmt_execute($stmt)) {
                header("location: $redirect&success=1");
            } else {
                unlink($file_path);
                header("location: $redirect&error=database_error");
            }
            mysqli_stmt_close($stmt);
        } else {
            unlink($file_path);
            header("location: $redirect&error=database_error");
        }
    } else {
        header("location: $redirect&error=upload_failed");
    }
} else {
    header("location: " . $_POST["redirect"] . "&error=invalid_request");
}

mysqli_close($conn);
?>