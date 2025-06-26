<?php
// Debug version of save_status.php to identify production issues
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h3>Debug Information</h3>";
echo "<p>Server: " . $_SERVER['HTTP_HOST'] . "</p>";
echo "<p>Request Method: " . $_SERVER['REQUEST_METHOD'] . "</p>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h4>POST Data:</h4>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    if (isset($_POST['enquiry_id']) && isset($_POST['status'])) {
        $enquiry_id = intval($_POST['enquiry_id']);
        $status = trim($_POST['status']);
        
        echo "<p>Enquiry ID: " . $enquiry_id . "</p>";
        echo "<p>Status: " . htmlspecialchars($status) . "</p>";
        
        // Test database connection
        try {
            if (file_exists("config/database_cpanel.php") && $_SERVER['HTTP_HOST'] !== 'localhost') {
                echo "<p>Using cPanel database config</p>";
                require_once "config/database_cpanel.php";
            } else {
                echo "<p>Using local database config</p>";
                require_once "config/database.php";
            }
            
            echo "<p>Database connection: SUCCESS</p>";
            
            // Test table creation
            $create_table_sql = "CREATE TABLE IF NOT EXISTS lead_status_map (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                enquiry_id INT(11) NOT NULL,
                status_name VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_enquiry (enquiry_id)
            )";
            
            if (mysqli_query($conn, $create_table_sql)) {
                echo "<p>Table creation/check: SUCCESS</p>";
            } else {
                echo "<p>Table creation error: " . mysqli_error($conn) . "</p>";
            }
            
            // Test prepared statement
            $sql = "INSERT INTO lead_status_map (enquiry_id, status_name) VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE status_name = VALUES(status_name), updated_at = CURRENT_TIMESTAMP";
            
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                echo "<p>Prepared statement: SUCCESS</p>";
                
                mysqli_stmt_bind_param($stmt, "is", $enquiry_id, $status);
                
                if (mysqli_stmt_execute($stmt)) {
                    echo "<p>Query execution: SUCCESS</p>";
                    echo "<p>Status saved successfully!</p>";
                    
                    if ($status == "Closed – Booked") {
                        echo "<p>Would redirect to: cost_sheet.php?id=" . $enquiry_id . "</p>";
                    } else {
                        echo "<p>Would redirect to: view_leads.php?status_updated=1</p>";
                    }
                } else {
                    echo "<p>Query execution error: " . mysqli_stmt_error($stmt) . "</p>";
                }
                
                mysqli_stmt_close($stmt);
            } else {
                echo "<p>Prepared statement error: " . mysqli_error($conn) . "</p>";
            }
            
        } catch (Exception $e) {
            echo "<p>Exception: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>ERROR: Missing enquiry_id or status in POST data</p>";
    }
} else {
    echo "<p>This script expects POST data</p>";
}

echo "<p><a href='view_leads.php'>Back to Leads</a></p>";
?>