<?php
// Fix the edit_enquiry.php file to properly handle travel_month

$file_path = "edit_enquiry.php";
$file_content = file_get_contents($file_path);

// Replace the first occurrence of $travel_month = NULL;
$file_content = preg_replace(
    '/\$travel_month = NULL;/',
    '$travel_month = !empty($_POST["travel_month"]) ? trim($_POST["travel_month"]) : NULL;',
    $file_content,
    1
);

// Replace the second occurrence of $travel_month = NULL;
$file_content = preg_replace(
    '/\$travel_month = NULL;/',
    '$travel_month = !empty($_POST["travel_month"]) ? trim($_POST["travel_month"]) : NULL;',
    $file_content,
    1
);

// Make sure the bind_param includes travel_month
if (strpos($file_content, '$travel_month, $travel_start_date') === false) {
    $file_content = str_replace(
        '$destination_id, $other_details, $travel_month, $travel_start_date',
        '$destination_id, $other_details, $travel_month, $travel_start_date',
        $file_content
    );
}

// Write the updated content back to the file
if (file_put_contents($file_path, $file_content)) {
    echo "Successfully updated edit_enquiry.php to properly handle travel_month.<br>";
} else {
    echo "Error updating edit_enquiry.php file.<br>";
}

echo "<br>Done! You can now <a href='edit_enquiry.php?id=56'>go back to edit_enquiry.php</a>";
?>