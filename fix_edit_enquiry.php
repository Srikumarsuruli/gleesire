<?php
// Include database connection
require_once "includes/config/database.php";

// Check if the night_day column exists
$check_column_sql = "SHOW COLUMNS FROM converted_leads LIKE 'night_day'";
$result = mysqli_query($conn, $check_column_sql);

if (mysqli_num_rows($result) == 0) {
    // Column doesn't exist, add it
    $alter_table_sql = "ALTER TABLE converted_leads ADD COLUMN night_day VARCHAR(20) NULL";
    if(mysqli_query($conn, $alter_table_sql)) {
        echo "Column 'night_day' added successfully to converted_leads table.<br>";
    } else {
        echo "Error adding column: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "Column 'night_day' already exists in converted_leads table.<br>";
}

// Now let's fix the edit_enquiry.php file
$file_path = "edit_enquiry.php";
$file_content = file_get_contents($file_path);

// Check if the file already has the night_day parameter in the bind_param
if (strpos($file_content, '$night_day, $adults_count') === false) {
    // Add night_day to the first section
    $file_content = str_replace(
        '$travel_month = NULL;
                    $travel_start_date',
        '$travel_month = NULL;
                    $night_day = !empty($_POST["night_day"]) ? trim($_POST["night_day"]) : NULL;
                    $travel_start_date',
        $file_content
    );
    
    // Update the bind_param
    $file_content = str_replace(
        '$travel_start_date, 
                                              $travel_end_date, $adults_count',
        '$travel_start_date, 
                                              $travel_end_date, $night_day, $adults_count',
        $file_content
    );
    
    // Write the updated content back to the file
    if (file_put_contents($file_path, $file_content)) {
        echo "Successfully updated edit_enquiry.php to include night_day parameter.<br>";
    } else {
        echo "Error updating edit_enquiry.php file.<br>";
    }
} else {
    echo "edit_enquiry.php already has the night_day parameter.<br>";
}

echo "<br>Done! You can now <a href='edit_enquiry.php?id=56'>go back to edit_enquiry.php</a>";
?>