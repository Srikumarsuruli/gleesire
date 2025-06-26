<?php
// Include database connection
require_once "config/database.php";

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Fixing Lead Management Bugs</h2>";

// Fix 1: Add lead_type to edit_enquiry.php update section
echo "<h3>Fix 1: Adding lead_type to edit_enquiry.php</h3>";

// Fix 2: Add error handling to converted_leads operations
echo "<h3>Fix 2: Adding error handling to converted_leads operations</h3>";

// Fix 1: Update edit_enquiry.php
$edit_enquiry_file = file_get_contents('edit_enquiry.php');

// Add lead_type to the update section
$pattern1 = '/\/\/ Check if status is still "Converted" and we need to update converted_lead details\s*else if\(\$status_id == 3 && \$enquiry\[\'status_id\'\] == 3 && \$converted_lead\) {\s*\/\/ Get converted lead details\s*/';
$replacement1 = '// Check if status is still "Converted" and we need to update converted_lead details
                else if($status_id == 3 && $enquiry[\'status_id\'] == 3 && $converted_lead) {
                    // Get converted lead details
                    $lead_type = !empty($_POST["lead_type"]) ? trim($_POST["lead_type"]) : NULL;
                    ';

$edit_enquiry_file = preg_replace($pattern1, $replacement1, $edit_enquiry_file);

// Add error handling to the update statement
$pattern2 = '/mysqli_stmt_execute\(\$stmt2\);/';
$replacement2 = 'if(!mysqli_stmt_execute($stmt2)) {
                            $error = "Error updating converted lead details: " . mysqli_error($conn);
                        }';

$edit_enquiry_file = preg_replace($pattern2, $replacement2, $edit_enquiry_file);

// Write the updated file
if(file_put_contents('edit_enquiry.php', $edit_enquiry_file)) {
    echo "<p>Successfully updated edit_enquiry.php</p>";
} else {
    echo "<p>Error updating edit_enquiry.php</p>";
}

// Fix 2: Update upload_enquiries.php
$upload_enquiries_file = file_get_contents('upload_enquiries.php');

// Add error handling to the insert statement
$pattern3 = '/\/\/ Attempt to execute the prepared statement\s*mysqli_stmt_execute\(\$stmt2\);/';
$replacement3 = '// Attempt to execute the prepared statement
                                if(!mysqli_stmt_execute($stmt2)) {
                                    $error = "Error saving converted lead details: " . mysqli_error($conn);
                                }';

$upload_enquiries_file = preg_replace($pattern3, $replacement3, $upload_enquiries_file);

// Write the updated file
if(file_put_contents('upload_enquiries.php', $upload_enquiries_file)) {
    echo "<p>Successfully updated upload_enquiries.php</p>";
} else {
    echo "<p>Error updating upload_enquiries.php</p>";
}

echo "<p>Bug fixes completed. Please check the system now.</p>";
?>