<?php
echo "<h2>Setting up Enquiry Type Module</h2>";

echo "<h3>Step 1: Creating enquiry_types table and inserting data</h3>";
include 'create_enquiry_type_table.php';

echo "<br><br><h3>Step 2: Adding enquiry type privileges</h3>";

require_once "config/database.php";

$roles_sql = "SELECT id FROM roles";
$roles_result = mysqli_query($conn, $roles_sql);

if ($roles_result) {
    while ($role = mysqli_fetch_assoc($roles_result)) {
        $role_id = $role['id'];
        
        $check_sql = "SELECT id FROM user_privileges WHERE role_id = ? AND menu_name = 'enquiry_type'";
        if ($check_stmt = mysqli_prepare($conn, $check_sql)) {
            mysqli_stmt_bind_param($check_stmt, "i", $role_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) == 0) {
                $insert_sql = "INSERT INTO user_privileges (role_id, menu_name, can_view, can_add, can_edit, can_delete) VALUES (?, 'enquiry_type', 1, 1, 1, 1)";
                if ($insert_stmt = mysqli_prepare($conn, $insert_sql)) {
                    mysqli_stmt_bind_param($insert_stmt, "i", $role_id);
                    if (mysqli_stmt_execute($insert_stmt)) {
                        echo "Enquiry type privilege added for role ID: $role_id<br>";
                    } else {
                        echo "Error adding privilege for role ID: $role_id - " . mysqli_error($conn) . "<br>";
                    }
                    mysqli_stmt_close($insert_stmt);
                }
            } else {
                echo "Enquiry type privilege already exists for role ID: $role_id<br>";
            }
            mysqli_stmt_close($check_stmt);
        }
    }
} else {
    echo "Error fetching roles: " . mysqli_error($conn);
}

mysqli_close($conn);

echo "<br><br><h3>Setup Complete!</h3>";
echo "<p><a href='EnquiryType.php'>Go to Enquiry Type</a></p>";
?>