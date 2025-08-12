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

// Check if ID is provided
if(isset($_GET['id']) && !empty($_GET['id']) && isset($_GET['type']) && !empty($_GET['type'])) {
    $id = $_GET['id'];
    $type = $_GET['type'];
    $response = array();
    
    // Get detailed information based on type
    if($type == 'enquiry' || $type == 'lead' || $type == 'booking') {
        // Get basic enquiry information
        $sql = "SELECT 
                e.*,
                d.name as department_name,
                s.name as source_name,
                ls.name as status_name,
                u.full_name as attended_by_name,
                ac.name as campaign_name
                FROM enquiries e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN sources s ON e.source_id = s.id
                LEFT JOIN lead_status ls ON e.status_id = ls.id
                LEFT JOIN users u ON e.attended_by = u.id
                LEFT JOIN ad_campaigns ac ON e.ad_campaign_id = ac.id
                WHERE e.id = ?";
                
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            
            if(mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                
                if($row = mysqli_fetch_assoc($result)) {
                    $response['enquiry'] = $row;
                }
            }
            mysqli_stmt_close($stmt);
        }
        
        // If lead or booking, get additional information
        if($type == 'lead' || $type == 'booking') {
            $sql = "SELECT 
                    cl.*,
                    d.name as destination_name,
                    u.full_name as file_manager_name,
                    COALESCE(nd.name, cl.night_day) as night_day_name
                    FROM converted_leads cl
                    LEFT JOIN destinations d ON cl.destination_id = d.id
                    LEFT JOIN users u ON cl.file_manager_id = u.id
                    LEFT JOIN night_day nd ON cl.night_day_id = nd.id
                    WHERE cl.enquiry_id = ?";
                    
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $id);
                
                if(mysqli_stmt_execute($stmt)) {
                    $result = mysqli_stmt_get_result($stmt);
                    
                    if($row = mysqli_fetch_assoc($result)) {
                        $response['lead'] = $row;
                    }
                }
                mysqli_stmt_close($stmt);
            }
        }
        
        // Get comments
        $sql = "SELECT 
                c.*,
                u.full_name as user_name
                FROM comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.enquiry_id = ?
                ORDER BY c.created_at DESC
                LIMIT 5";
                
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            
            if(mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                
                $comments = array();
                while($row = mysqli_fetch_assoc($result)) {
                    $comments[] = $row;
                }
                
                $response['comments'] = $comments;
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Return results as JSON
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    // No ID or type provided
    header('Content-Type: application/json');
    echo json_encode(array('error' => 'Missing ID or type parameter'));
}
?>