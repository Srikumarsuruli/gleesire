<?php
// Read the file
$file = file_get_contents('edit_enquiry.php');

// Replace the first occurrence of $travel_month = NULL;
$pattern = '/\$travel_month = NULL;/';
$replacement = '$travel_month = !empty($_POST["travel_month"]) ? trim($_POST["travel_month"]) : NULL;';
$file = preg_replace($pattern, $replacement, $file, 1);

// Replace the second occurrence of $travel_month = NULL;
$file = preg_replace($pattern, $replacement, $file, 1);

// Write the file back
file_put_contents('edit_enquiry.php', $file);

echo "File updated successfully!";
?>