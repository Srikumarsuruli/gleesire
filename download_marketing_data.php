<?php
// Start session
session_start();

// Include database connection
require_once "config/database.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Function to check user privileges
function hasPrivilege($menu, $action = 'view') {
    global $conn;
    $role_id = $_SESSION["role_id"];
    
    // Admin has all privileges
    if($role_id == 1) {
        return true;
    }
    
    // Check if the table exists
    $table_exists = mysqli_query($conn, "SHOW TABLES LIKE 'user_privileges'");
    if(mysqli_num_rows($table_exists) == 0) {
        return false;
    }
    
    $column = "can_" . $action;
    $sql = "SELECT $column FROM user_privileges WHERE role_id = ? AND menu_name = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "is", $role_id, $menu);
        
        if(mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            
            if(mysqli_num_rows($result) == 1) {
                $row = mysqli_fetch_assoc($result);
                return $row[$column] == 1;
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    return false;
}

// Check if user has privilege to download marketing data
if(!hasPrivilege('view_marketing_data') && $_SESSION["role_id"] != 1) {
    header("location: index.php");
    exit;
}

// Check if file_id is provided
if(isset($_GET['file_id']) && !empty($_GET['file_id'])) {
    $file_id = $_GET['file_id'];
    
    // Get file details
    $file_sql = "SELECT * FROM marketing_files WHERE id = ?";
    $file_stmt = mysqli_prepare($conn, $file_sql);
    mysqli_stmt_bind_param($file_stmt, "i", $file_id);
    mysqli_stmt_execute($file_stmt);
    $file_result = mysqli_stmt_get_result($file_stmt);
    
    if(mysqli_num_rows($file_result) == 0) {
        header("location: upload_marketing_data.php");
        exit;
    }
    
    $file = mysqli_fetch_assoc($file_result);
    
    // Get marketing data
    $data_sql = "SELECT * FROM marketing_data WHERE file_id = ? ORDER BY campaign_date DESC";
    $data_stmt = mysqli_prepare($conn, $data_sql);
    mysqli_stmt_bind_param($data_stmt, "i", $file_id);
    mysqli_stmt_execute($data_stmt);
    $data_result = mysqli_stmt_get_result($data_stmt);
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $file['file_name'] . '"');
    
    // Create a file pointer connected to the output stream
    $output = fopen('php://output', 'w');
    
    // Output the column headings
    fputcsv($output, array(
        'Date', 
        'Campaign name', 
        'Amount spent (INR)', 
        'Impressions', 
        'CPM (cost per 1,000 impressions) (INR)', 
        'Reach', 
        'Link clicks', 
        'CPC (all) (INR)', 
        'Results', 
        'Cost per results'
    ));
    
    // Output each row of the data
    while($row = mysqli_fetch_assoc($data_result)) {
        // Format date from YYYY-MM-DD to DD/MM/YYYY
        $campaign_date = !empty($row['campaign_date']) ? 
            date('d/m/Y', strtotime($row['campaign_date'])) : '';
            
        // Format amount values with "Rs: " prefix
        $amount_spent = 'Rs: ' . $row['amount_spent'];
        $cpc = 'Rs: ' . $row['cpc'];
        $cost_per_result = 'Rs: ' . $row['cost_per_result'];
        
        fputcsv($output, array(
            $campaign_date,
            $row['campaign_name'],
            $amount_spent,
            $row['impressions'],
            $row['cpm'],
            $row['reach'],
            $row['link_clicks'],
            $cpc,
            $row['results'],
            $cost_per_result
        ));
    }
    
    // Close the file pointer
    fclose($output);
    exit;
} else {
    header("location: upload_marketing_data.php");
    exit;
}
?>