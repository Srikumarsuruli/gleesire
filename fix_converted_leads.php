<?php
// Include database connection
require_once "config/database.php";

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Fix Converted Leads</h2>";

// Check if the converted_leads table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'converted_leads'");
if(mysqli_num_rows($table_check) == 0) {
    // Create the table if it doesn't exist
    $create_table = "CREATE TABLE IF NOT EXISTS converted_leads (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        enquiry_id INT NOT NULL,
        enquiry_number VARCHAR(20) NOT NULL,
        lead_type ENUM('Hot', 'Warm', 'Cold') DEFAULT NULL,
        customer_location VARCHAR(255),
        secondary_contact VARCHAR(20),
        destination_id INT,
        other_details TEXT,
        travel_month VARCHAR(20),
        travel_start_date DATE,
        travel_end_date DATE,
        adults_count INT,
        children_count INT,
        infants_count INT,
        customer_available_timing VARCHAR(100),
        file_manager_id INT,
        booking_confirmed BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (enquiry_id) REFERENCES enquiries(id),
        FOREIGN KEY (destination_id) REFERENCES destinations(id),
        FOREIGN KEY (file_manager_id) REFERENCES users(id)
    )";
    
    if(mysqli_query($conn, $create_table)) {
        echo "<p>Successfully created the converted_leads table.</p>";
    } else {
        echo "<p>Error creating table: " . mysqli_error($conn) . "</p>";
    }
} else {
    // Check if lead_type column exists
    $column_check = mysqli_query($conn, "SHOW COLUMNS FROM converted_leads LIKE 'lead_type'");
    if(mysqli_num_rows($column_check) == 0) {
        // Add lead_type column
        $add_column = "ALTER TABLE converted_leads ADD COLUMN lead_type ENUM('Hot', 'Warm', 'Cold') DEFAULT NULL AFTER enquiry_number";
        if(mysqli_query($conn, $add_column)) {
            echo "<p>Successfully added lead_type column to the converted_leads table.</p>";
        } else {
            echo "<p>Error adding lead_type column: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p>The lead_type column already exists in the converted_leads table.</p>";
    }
}

// Find all enquiries with status_id = 3 (Converted) that are not in converted_leads
$sql = "SELECT e.* FROM enquiries e 
        LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id 
        WHERE e.status_id = 3 AND cl.enquiry_id IS NULL";

$result = mysqli_query($conn, $sql);
$count = mysqli_num_rows($result);

echo "<p>Found $count enquiries with status 'Converted' that are not in the converted_leads table.</p>";

if($count > 0) {
    echo "<h3>Adding missing converted leads:</h3>";
    
    while($enquiry = mysqli_fetch_assoc($result)) {
        // Generate enquiry number
        $enquiry_number = 'GH ' . sprintf('%04d', rand(1, 9999));
        
        // Insert into converted_leads
        $insert_sql = "INSERT INTO converted_leads (enquiry_id, enquiry_number, lead_type, booking_confirmed) 
                      VALUES (?, ?, 'Hot', 0)";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($insert_stmt, "is", $enquiry['id'], $enquiry_number);
        
        if(mysqli_stmt_execute($insert_stmt)) {
            echo "<p>Added enquiry ID " . $enquiry['id'] . " (" . $enquiry['customer_name'] . ") to converted_leads.</p>";
        } else {
            echo "<p>Error adding enquiry ID " . $enquiry['id'] . ": " . mysqli_error($conn) . "</p>";
        }
    }
}

echo "<p>Fix completed.</p>";

// Close connection
mysqli_close($conn);
?>