<?php
// Include timezone configuration
require_once __DIR__ . '/../config/timezone.php';

// Function to check if user is admin
function isAdmin() {
    return $_SESSION["role_id"] == 1;
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

function getRoleAccess($role_name) {
    global $conn;

    $role_id = $_SESSION["role_id"];
    
    // if($role_id == 1) {
    //     return true;
    // }
    

    $sql = "SELECT role_name FROM roles WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $role_id);

        if(mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            

            if(mysqli_num_rows($result) == 1) {
                $row = mysqli_fetch_assoc($result);
                error_log("Role name: " . $row['role_name']);
                return $row['role_name'] == $role_name;
            }
        }
        mysqli_stmt_close($stmt);
    }

    return "Unknown";
}

?>