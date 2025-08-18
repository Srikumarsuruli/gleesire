<?php
session_start();
require_once "config/database.php";

// Enable error logging
error_log("Availability update attempt - POST data: " . print_r($_POST, true));


if(!isset($_SESSION["id"])) {
    error_log("Session not set, redirecting to hotel_resorts.php");
    header("location: hotel_resorts.php");
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id']) && isset($_POST['availability']) && isset($_POST['idx'])) {
    $enquiry_id = intval($_POST['id']);
    $availability = $_POST['availability'];
    $idx = intval($_POST['idx']);
    $json_path = '$[' . $idx . '].availability';

    error_log("Processing update - Enquiry ID: $enquiry_id, Availability: $availability");
    
    if($enquiry_id > 0) {
        $sql = "UPDATE tour_costings
        SET accommodation_data = JSON_SET(accommodation_data, ?, ?),
            updated_at = NOW()
        WHERE id = ?;";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssi", $json_path, $availability, $enquiry_id);
            
            if(mysqli_stmt_execute($stmt)) {
                $affected_rows = mysqli_stmt_affected_rows($stmt);
                error_log("Update successful - Affected rows: $affected_rows");
                echo "Update successful - Affected rows: $affected_rows";
                
            } else {
                error_log("Update failed - MySQL error: " . mysqli_error($conn));
            }
            mysqli_stmt_close($stmt);
        } else {
            error_log("Prepare failed - MySQL error: " . mysqli_error($conn));
            echo "Prepare failed - MySQL error: " . mysqli_error($conn);
        }
    } else {
        error_log("Invalid IDs - Enquiry ID: $enquiry_id, Hotel ID: $idx");
    }
} else {
    error_log("Invalid request method or missing parameters");
}

// Force cache refresh with timestamp
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header("location: hotel_resorts.php?updated=" . time());
exit;
?>