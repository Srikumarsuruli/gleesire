<?php
require_once "includes/header.php";

$id = 57; // Test with ID 57

// Fetch converted lead data
$sql = "SELECT * FROM converted_leads WHERE enquiry_id = ?";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) >= 1) {
            $converted_lead = mysqli_fetch_assoc($result);
            echo "<h3>Converted Lead Data for ID $id:</h3>";
            echo "<pre>";
            print_r($converted_lead);
            echo "</pre>";
        } else {
            echo "<p>No converted lead data found for ID $id</p>";
        }
    }
    
    mysqli_stmt_close($stmt);
}
?>