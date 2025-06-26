<?php
// Include database connection
require_once "includes/config/database.php";

// Check if the reports privilege already exists
$check_sql = "SELECT * FROM user_privileges WHERE menu_name = 'reports'";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) == 0) {
    // Get all roles
    $roles_sql = "SELECT id FROM roles";
    $roles_result = mysqli_query($conn, $roles_sql);
    
    while ($role = mysqli_fetch_assoc($roles_result)) {
        $role_id = $role['id'];
        
        // Insert reports privilege for each role
        // By default, only admin (role_id = 1) has access
        $can_view = ($role_id == 1) ? 1 : 0;
        
        $insert_sql = "INSERT INTO user_privileges (role_id, menu_name, can_view, can_add, can_edit, can_delete) 
                      VALUES (?, 'reports', ?, 0, 0, 0)";
        
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, "ii", $role_id, $can_view);
        mysqli_stmt_execute($stmt);
    }
    
    echo "Reports privilege added successfully!";
} else {
    echo "Reports privilege already exists.";
}

// Close connection
mysqli_close($conn);
?>