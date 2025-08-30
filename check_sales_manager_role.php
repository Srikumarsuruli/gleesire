<?php
require_once 'config/database.php';

echo "<h2>Sales Manager Role Check</h2>";

// Find Sales Manager role
$role_sql = "SELECT * FROM roles WHERE role_name LIKE '%Sales Manager%'";
$role_result = mysqli_query($conn, $role_sql);

if(mysqli_num_rows($role_result) > 0) {
    while($role = mysqli_fetch_assoc($role_result)) {
        echo "<p><strong>Role:</strong> {$role['role_name']} (ID: {$role['id']})</p>";
        
        // Check privileges for this role
        $priv_sql = "SELECT * FROM user_privileges WHERE role_id = {$role['id']} AND menu_name = 'view_cost_sheets'";
        $priv_result = mysqli_query($conn, $priv_sql);
        
        if(mysqli_num_rows($priv_result) > 0) {
            $priv = mysqli_fetch_assoc($priv_result);
            echo "<p><strong>view_cost_sheets privilege:</strong> View={$priv['can_view']}, Add={$priv['can_add']}, Edit={$priv['can_edit']}, Delete={$priv['can_delete']}</p>";
        } else {
            echo "<p><strong>view_cost_sheets privilege:</strong> NOT FOUND</p>";
        }
    }
} else {
    echo "<p>Sales Manager role not found</p>";
}

mysqli_close($conn);
?>