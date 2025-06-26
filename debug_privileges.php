<?php
// Initialize the session
session_start();

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Include database connection
require_once "config/database.php";

echo "<h1>User Privilege Debug</h1>";
echo "<p>Username: " . $_SESSION["username"] . "</p>";
echo "<p>Role ID: " . $_SESSION["role_id"] . "</p>";

// Check if user_privileges table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'user_privileges'");
echo "<p>user_privileges table exists: " . (mysqli_num_rows($table_check) > 0 ? "Yes" : "No") . "</p>";

// Get all privileges for this role
$role_id = $_SESSION["role_id"];
$sql = "SELECT * FROM user_privileges WHERE role_id = ?";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $role_id);
    
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        echo "<h2>Privileges for Role ID: $role_id</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Menu</th><th>View</th><th>Add</th><th>Edit</th><th>Delete</th></tr>";
        
        if(mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td>" . $row['menu_name'] . "</td>";
                echo "<td>" . ($row['can_view'] ? "Yes" : "No") . "</td>";
                echo "<td>" . ($row['can_add'] ? "Yes" : "No") . "</td>";
                echo "<td>" . ($row['can_edit'] ? "Yes" : "No") . "</td>";
                echo "<td>" . ($row['can_delete'] ? "Yes" : "No") . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='5'>No privileges found for this role</td></tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>Error executing query: " . mysqli_error($conn) . "</p>";
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo "<p>Error preparing query: " . mysqli_error($conn) . "</p>";
}

// Test hasPrivilege function
echo "<h2>Testing hasPrivilege Function</h2>";

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
            mysqli_stmt_store_result($stmt);
            
            if(mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result($stmt, $has_privilege);
                mysqli_stmt_fetch($stmt);
                return $has_privilege;
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    return false;
}

$menus = [
    'dashboard' => 'Dashboard',
    'upload_enquiries' => 'Upload Enquiries',
    'view_enquiries' => 'View Enquiries',
    'view_leads' => 'View Leads',
    'booking_confirmed' => 'Booking Confirmed',
    'ad_campaign' => 'Ad Campaign Management'
];

echo "<table border='1'>";
echo "<tr><th>Menu</th><th>hasPrivilege Result</th></tr>";

foreach($menus as $menu_key => $menu_name) {
    echo "<tr>";
    echo "<td>" . $menu_name . "</td>";
    echo "<td>" . (hasPrivilege($menu_key) ? "Yes" : "No") . "</td>";
    echo "</tr>";
}

echo "</table>";

// Show SQL query that would be executed
$role_id = $_SESSION["role_id"];
$menu = "upload_enquiries";
$column = "can_view";
$sql = "SELECT $column FROM user_privileges WHERE role_id = $role_id AND menu_name = '$menu'";
echo "<p>Example SQL query: $sql</p>";

// Execute the query directly
$result = mysqli_query($conn, $sql);
if($result) {
    if(mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo "<p>Result: " . ($row[$column] ? "Yes" : "No") . "</p>";
    } else {
        echo "<p>No rows returned</p>";
    }
} else {
    echo "<p>Error executing query: " . mysqli_error($conn) . "</p>";
}
?>