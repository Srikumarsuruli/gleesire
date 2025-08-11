<?php
session_start();
require_once "config/database.php";

// Simple test to verify database connection and update works
if(isset($_GET['test'])) {
    echo "Testing database connection...<br>";
    
    // Test connection
    if($conn) {
        echo "Database connected successfully<br>";
        
        // Test if enquiries table exists and has data
        $test_sql = "SELECT id, status_id FROM enquiries LIMIT 1";
        $result = mysqli_query($conn, $test_sql);
        
        if($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            echo "Found enquiry ID: " . $row['id'] . " with status_id: " . $row['status_id'] . "<br>";
            
            // Test update (change status_id to same value to avoid actual change)
            $update_sql = "UPDATE enquiries SET last_updated = NOW() WHERE id = ?";
            if($stmt = mysqli_prepare($conn, $update_sql)) {
                mysqli_stmt_bind_param($stmt, "i", $row['id']);
                if(mysqli_stmt_execute($stmt)) {
                    echo "Update test successful<br>";
                } else {
                    echo "Update test failed: " . mysqli_error($conn) . "<br>";
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            echo "No enquiries found in database<br>";
        }
    } else {
        echo "Database connection failed<br>";
    }
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Status Update</title>
</head>
<body>
    <h2>Test Status Update Functionality</h2>
    <p><a href="?test=1">Test Database Connection</a></p>
    
    <form method="post" action="update_status.php">
        <label>Enquiry ID:</label>
        <input type="number" name="id" value="1" required><br><br>
        
        <label>Status ID:</label>
        <input type="number" name="status_id" value="1" required><br><br>
        
        <button type="submit">Test Update</button>
    </form>
</body>
</html>