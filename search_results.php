<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Include database connection
require_once "config/database.php";

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

// Check if search query is provided
if(isset($_GET['query']) && !empty($_GET['query'])) {
    $search_query = $_GET['query'];
    $results = array();
    
    // Search in enquiries table with lead information
    if(hasPrivilege('view_enquiries')) {
        $sql = "SELECT 
                CASE 
                    WHEN cl.booking_confirmed = 1 THEN 'booking'
                    WHEN cl.id IS NOT NULL THEN 'lead'
                    ELSE 'enquiry'
                END as type,
                e.id,
                e.lead_number,
                cl.enquiry_number,
                e.customer_name,
                e.mobile_number,
                e.email,
                e.referral_code,
                e.received_datetime,
                CASE 
                    WHEN cl.booking_confirmed = 1 THEN 'booking_confirmed.php'
                    WHEN cl.id IS NOT NULL THEN 'view_leads.php'
                    ELSE 'view_enquiries.php'
                END as redirect_url,
                CASE 
                    WHEN cl.id IS NOT NULL THEN 1
                    ELSE 0
                END as has_lead_data
                FROM enquiries e
                LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id
                WHERE e.lead_number LIKE ? 
                OR e.customer_name LIKE ? 
                OR e.mobile_number LIKE ? 
                OR e.email LIKE ?
                OR e.referral_code LIKE ?
                OR cl.enquiry_number LIKE ?
                LIMIT 5";
                
        if($stmt = mysqli_prepare($conn, $sql)) {
            $search_param = "%" . $search_query . "%";
            mysqli_stmt_bind_param($stmt, "ssssss", $search_param, $search_param, $search_param, $search_param, $search_param, $search_param);
            
            if(mysqli_stmt_execute($stmt)) {
                $enquiry_result = mysqli_stmt_get_result($stmt);
                
                while($row = mysqli_fetch_assoc($enquiry_result)) {
                    $results[] = $row;
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
    

    
    // Search in comments table
    $sql = "SELECT 
            'comment' as type,
            e.id,
            e.lead_number,
            cl.enquiry_number,
            e.customer_name,
            e.mobile_number,
            e.email,
            e.referral_code,
            c.comment,
            c.created_at as comment_date,
            u.full_name as comment_user,
            e.received_datetime,
            CASE 
                WHEN cl.booking_confirmed = 1 THEN 'booking_confirmed.php'
                WHEN e.status_id = 3 THEN 'view_leads.php'
                ELSE 'view_enquiries.php'
            END as redirect_url
            FROM comments c
            JOIN enquiries e ON c.enquiry_id = e.id
            JOIN users u ON c.user_id = u.id
            LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id
            WHERE c.comment LIKE ?
            ORDER BY c.created_at DESC
            LIMIT 5";
            
    if($stmt = mysqli_prepare($conn, $sql)) {
        $search_param = "%" . $search_query . "%";
        mysqli_stmt_bind_param($stmt, "s", $search_param);
        
        if(mysqli_stmt_execute($stmt)) {
            $comment_result = mysqli_stmt_get_result($stmt);
            
            while($row = mysqli_fetch_assoc($comment_result)) {
                $results[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    // Return results as JSON
    header('Content-Type: application/json');
    echo json_encode($results);
} else {
    // No search query provided
    header('Content-Type: application/json');
    echo json_encode(array());
}
?>