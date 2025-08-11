<?php
// Debug new_cost_file.php issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to prevent header issues
ob_start();

echo "<h2>Debug New Cost File Page</h2>";

// Check if enquiry_id is passed
$enquiry_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
echo "<p>Enquiry ID: " . $enquiry_id . "</p>";

// Check database connection
require_once "config/database.php";
if ($conn) {
    echo "<p>✅ Database connection: OK</p>";
} else {
    echo "<p>❌ Database connection: FAILED</p>";
    die();
}

// Check if enquiry exists
if ($enquiry_id > 0) {
    $check_sql = "SELECT * FROM enquiries WHERE id = ?";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "i", $enquiry_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $enquiry = mysqli_fetch_assoc($result);
        echo "<p>✅ Enquiry found: " . htmlspecialchars($enquiry['customer_name']) . "</p>";
    } else {
        echo "<p>❌ Enquiry not found</p>";
    }
}

// Check if destinations table exists
$dest_check = mysqli_query($conn, "SELECT COUNT(*) as count FROM destinations");
if ($dest_check) {
    $dest_count = mysqli_fetch_assoc($dest_check)['count'];
    echo "<p>✅ Destinations table: {$dest_count} records</p>";
} else {
    echo "<p>❌ Destinations table: ERROR - " . mysqli_error($conn) . "</p>";
}

// Check if tour_costings table exists
$cost_check = mysqli_query($conn, "SHOW TABLES LIKE 'tour_costings'");
if (mysqli_num_rows($cost_check) > 0) {
    echo "<p>✅ tour_costings table: EXISTS</p>";
} else {
    echo "<p>❌ tour_costings table: MISSING</p>";
    // Create the table
    $create_sql = "CREATE TABLE tour_costings (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        enquiry_id INT(11) NOT NULL,
        cost_sheet_number VARCHAR(50),
        guest_name VARCHAR(255),
        guest_address TEXT,
        whatsapp_number VARCHAR(20),
        tour_package VARCHAR(100),
        currency VARCHAR(10),
        nationality VARCHAR(10),
        selected_services TEXT,
        visa_data TEXT,
        accommodation_data TEXT,
        transportation_data TEXT,
        cruise_data TEXT,
        extras_data TEXT,
        payment_data TEXT,
        total_expense DECIMAL(10,2) DEFAULT 0,
        markup_percentage DECIMAL(5,2) DEFAULT 0,
        markup_amount DECIMAL(10,2) DEFAULT 0,
        tax_percentage DECIMAL(5,2) DEFAULT 18,
        tax_amount DECIMAL(10,2) DEFAULT 0,
        package_cost DECIMAL(10,2) DEFAULT 0,
        currency_rate DECIMAL(10,4) DEFAULT 1,
        converted_amount DECIMAL(10,2) DEFAULT 0,
        adults_count INT DEFAULT 0,
        children_count INT DEFAULT 0,
        infants_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_enquiry_id (enquiry_id)
    )";
    
    if (mysqli_query($conn, $create_sql)) {
        echo "<p>✅ tour_costings table: CREATED</p>";
    } else {
        echo "<p>❌ tour_costings table creation: FAILED - " . mysqli_error($conn) . "</p>";
    }
}

// Check PHP errors
echo "<h3>PHP Error Check:</h3>";
ob_start();
include 'new_cost_file.php';
$output = ob_get_contents();
ob_end_clean();

if (empty($output)) {
    echo "<p>❌ new_cost_file.php produces no output</p>";
} else {
    echo "<p>✅ new_cost_file.php produces output (" . strlen($output) . " characters)</p>";
}

echo "<h3>Test Link:</h3>";
echo "<a href='new_cost_file.php?id={$enquiry_id}' target='_blank'>Test new_cost_file.php</a>";
echo "<br><a href='new_cost_file_simple.php?id={$enquiry_id}' target='_blank'>Test new_cost_file_simple.php</a>";

// End output buffering
ob_end_flush();
?>