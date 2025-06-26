<?php
// Include database connection
require_once "config/database.php";

// First, truncate the departments table to remove all existing entries
$truncate_sql = "TRUNCATE TABLE departments";
if(mysqli_query($conn, $truncate_sql)) {
    echo "Existing departments removed successfully.<br>";
} else {
    echo "Error removing existing departments: " . mysqli_error($conn) . "<br>";
    exit;
}

// New department list
$departments = [
    "Domestic",
    "GCC Inbound",
    "GCC Medical",
    "GCC Outbound",
    "Inound",
    "Not Provided",
    "Outbound"
];

// Insert new departments
$success_count = 0;
foreach($departments as $dept) {
    $sql = "INSERT INTO departments (name) VALUES (?)";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $dept);
        if(mysqli_stmt_execute($stmt)) {
            $success_count++;
        }
        mysqli_stmt_close($stmt);
    }
}

echo "$success_count departments added successfully.<br>";
echo "<a href='index.php'>Return to Dashboard</a>";
?>