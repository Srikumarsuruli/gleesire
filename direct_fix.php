<?php
// Direct fix for edit_enquiry.php to handle travel_month

// Connect to database
$conn = mysqli_connect("localhost", "root", "", "lead_management");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Add travel_month column if it doesn't exist
$alter_sql = "ALTER TABLE converted_leads ADD COLUMN IF NOT EXISTS travel_month VARCHAR(20) NULL";
mysqli_query($conn, $alter_sql);

// Update the edit_enquiry.php file
$file = file_get_contents('edit_enquiry.php');

// Add debug code to print POST data
$debug_code = "
// Debug travel_month
if(isset(\$_POST['travel_month'])) {
    error_log('POST travel_month: ' . \$_POST['travel_month']);
}
";

// Insert debug code after the POST check
$file = str_replace("if(\$_SERVER[\"REQUEST_METHOD\"] == \"POST\") {", "if(\$_SERVER[\"REQUEST_METHOD\"] == \"POST\") {
    $debug_code", $file);

// Make sure travel_month is properly assigned
$file = str_replace('$travel_month = NULL;', '$travel_month = !empty($_POST["travel_month"]) ? trim($_POST["travel_month"]) : NULL;', $file);

// Make sure travel_month is included in the bind_param
$file = str_replace('$travel_start_date, $travel_end_date, $night_day', '$travel_start_date, $travel_end_date, $night_day', $file);

// Write the file back
file_put_contents('edit_enquiry.php', $file);

// Create a direct update script
$update_script = "<?php
// Connect to database
\$conn = mysqli_connect('localhost', 'root', '', 'lead_management');
if (!\$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}

// Update travel_month for a specific record
\$id = isset(\$_GET['id']) ? intval(\$_GET['id']) : 56;
\$month = isset(\$_GET['month']) ? \$_GET['month'] : 'January';

\$update_sql = \"UPDATE converted_leads SET travel_month = '\$month' WHERE enquiry_id = \$id\";
if(mysqli_query(\$conn, \$update_sql)) {
    echo \"Successfully updated travel_month to '\$month' for enquiry_id \$id.<br>\";
} else {
    echo \"Error updating travel_month: \" . mysqli_error(\$conn) . \"<br>\";
}

// Check if the update worked
\$check_sql = \"SELECT travel_month FROM converted_leads WHERE enquiry_id = \$id\";
\$check_result = mysqli_query(\$conn, \$check_sql);
if(\$check_result && mysqli_num_rows(\$check_result) > 0) {
    \$row = mysqli_fetch_assoc(\$check_result);
    echo \"Current travel_month value for enquiry_id \$id: \" . \$row['travel_month'] . \"<br>\";
} else {
    echo \"No record found for enquiry_id \$id or error checking value.<br>\";
}

echo \"<br>Done! <a href='edit_enquiry.php?id=\$id'>Go back to edit_enquiry.php</a>\";
?>";

file_put_contents('update_travel_month_direct.php', $update_script);

echo "Files updated. You can now:<br>";
echo "1. <a href='update_travel_month_direct.php'>Update travel_month directly</a><br>";
echo "2. <a href='edit_enquiry.php?id=56'>Go to edit_enquiry.php</a>";
?>