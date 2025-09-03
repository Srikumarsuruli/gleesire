<?php
require_once "config/database.php";

$enquiry_id = 565;

// Check enquiry status
$sql = "SELECT status_id FROM enquiries WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $enquiry_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$enquiry = mysqli_fetch_assoc($result);
echo "Enquiry Status ID: " . $enquiry['status_id'] . "<br>";

// Check converted_leads record
$sql = "SELECT * FROM converted_leads WHERE enquiry_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $enquiry_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) > 0) {
    $converted_lead = mysqli_fetch_assoc($result);
    echo "Converted Lead Found:<br>";
    echo "other_details: '" . $converted_lead['other_details'] . "'<br>";
    echo "Full data: <pre>" . print_r($converted_lead, true) . "</pre>";
} else {
    echo "No converted_leads record found for enquiry ID " . $enquiry_id . "<br>";
}

// Check packages
$sql = "SELECT * FROM packages WHERE status = 'Active'";
$result = mysqli_query($conn, $sql);
echo "<br>Available Packages:<br>";
while($package = mysqli_fetch_assoc($result)) {
    echo "- " . $package['package_name'] . "<br>";
}
?>