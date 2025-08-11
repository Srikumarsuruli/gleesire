<?php
// Connect to database
$conn = mysqli_connect("localhost", "root", "", "lead_management");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get parameters
$id = isset($_GET['id']) ? intval($_GET['id']) : 56;
$month = isset($_GET['month']) ? $_GET['month'] : 'January';

// Update travel_month for the specified record
$update_sql = "UPDATE converted_leads SET travel_month = '$month' WHERE enquiry_id = $id";
if(mysqli_query($conn, $update_sql)) {
    echo "Successfully updated travel_month to '$month' for enquiry_id $id.<br>";
} else {
    echo "Error updating travel_month: " . mysqli_error($conn) . "<br>";
}

// Check if the update worked
$check_sql = "SELECT travel_month FROM converted_leads WHERE enquiry_id = $id";
$check_result = mysqli_query($conn, $check_sql);
if($check_result && mysqli_num_rows($check_result) > 0) {
    $row = mysqli_fetch_assoc($check_result);
    echo "Current travel_month value for enquiry_id $id: " . $row['travel_month'] . "<br>";
} else {
    echo "No record found for enquiry_id $id or error checking value.<br>";
}

// Show links to update with different months
echo "<h3>Update to a specific month:</h3>";
$months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
foreach ($months as $m) {
    echo "<a href='update_month.php?id=$id&month=$m'>Update to $m</a><br>";
}

echo "<br><a href='edit_enquiry.php?id=$id'>Go back to edit_enquiry.php</a>";
?>