<?php
// Initialize the session
session_start();

// Include database connection
require_once "config/database.php";

// Check if there are any enquiries
$check_sql = "SELECT * FROM enquiries";
$check_result = mysqli_query($conn, $check_sql);
$enquiry_count = mysqli_num_rows($check_result);

echo "<h2>Test Conversion Tool</h2>";
echo "<p>Total enquiries in system: " . $enquiry_count . "</p>";

// If there are enquiries, show a form to convert one
if($enquiry_count > 0) {
    echo "<form method='post'>";
    echo "<h3>Convert an Enquiry to Lead</h3>";
    echo "<select name='enquiry_id'>";
    
    while($row = mysqli_fetch_assoc($check_result)) {
        echo "<option value='" . $row['id'] . "'>" . $row['lead_number'] . " - " . $row['customer_name'] . " (Status: " . $row['status_id'] . ")</option>";
    }
    
    echo "</select>";
    echo "<input type='submit' name='convert' value='Convert to Lead'>";
    echo "</form>";
    
    // Process conversion
    if(isset($_POST['convert']) && !empty($_POST['enquiry_id'])) {
        $enquiry_id = $_POST['enquiry_id'];
        
        // Update status to 3 (Converted)
        $update_sql = "UPDATE enquiries SET status_id = 3, last_updated = NOW() WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "i", $enquiry_id);
        
        if(mysqli_stmt_execute($update_stmt)) {
            // Generate enquiry number
            $enquiry_number = 'GH ' . sprintf('%04d', rand(1, 9999));
            
            // Insert into converted_leads
            $insert_sql = "INSERT INTO converted_leads (enquiry_id, enquiry_number, travel_start_date, travel_end_date, booking_confirmed) 
                          VALUES (?, ?, NULL, NULL, 0)";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "is", $enquiry_id, $enquiry_number);
            
            if(mysqli_stmt_execute($insert_stmt)) {
                echo "<p style='color:green'>Successfully converted enquiry #" . $enquiry_id . " to lead!</p>";
                echo "<p><a href='view_leads.php'>View in Leads</a></p>";
            } else {
                echo "<p style='color:red'>Error adding to converted_leads table: " . mysqli_error($conn) . "</p>";
            }
        } else {
            echo "<p style='color:red'>Error updating status: " . mysqli_error($conn) . "</p>";
        }
    }
} else {
    echo "<p>No enquiries found in the system.</p>";
}

// Close connection
mysqli_close($conn);
?>