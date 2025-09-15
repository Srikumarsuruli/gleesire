<?php
session_start();
require_once "config/database.php";

// Check if user is admin
if($_SESSION["role_id"] != 1) {
    header("location: index.php");
    exit;
}

echo "<h2>Debug User Privileges</h2>";

// Get all roles
$roles_sql = "SELECT * FROM roles ORDER BY id";
$roles_result = mysqli_query($conn, $roles_sql);

echo "<h3>All Roles:</h3>";
while($role = mysqli_fetch_assoc($roles_result)) {
    echo "ID: {$role['id']}, Name: {$role['role_name']}<br>";
}

// Get privileges for each role
mysqli_data_seek($roles_result, 0);
while($role = mysqli_fetch_assoc($roles_result)) {
    echo "<h3>Privileges for Role: {$role['role_name']} (ID: {$role['id']})</h3>";
    
    $priv_sql = "SELECT * FROM user_privileges WHERE role_id = ? ORDER BY menu_name";
    if($priv_stmt = mysqli_prepare($conn, $priv_sql)) {
        mysqli_stmt_bind_param($priv_stmt, "i", $role['id']);
        mysqli_stmt_execute($priv_stmt);
        $priv_result = mysqli_stmt_get_result($priv_stmt);
        
        if(mysqli_num_rows($priv_result) > 0) {
            echo "<table border='1'>";
            echo "<tr><th>Menu Name</th><th>Can View</th></tr>";
            while($priv = mysqli_fetch_assoc($priv_result)) {
                echo "<tr>";
                echo "<td>{$priv['menu_name']}</td>";
                echo "<td>" . ($priv['can_view'] ? 'Yes' : 'No') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "No privileges set for this role.<br>";
        }
        mysqli_stmt_close($priv_stmt);
    }
    echo "<br>";
}
?>