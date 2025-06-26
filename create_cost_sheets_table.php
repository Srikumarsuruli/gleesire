<?php
// Include database connection
require_once "config/database.php";

// Get the referring page
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'cost_sheet.php';

// Create cost_sheets table
$sql = "CREATE TABLE IF NOT EXISTS cost_sheets (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    enquiry_id INT(11) NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    enquiry_number VARCHAR(50) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'USD',
    total_expense DECIMAL(15,2) NOT NULL DEFAULT 0,
    package_cost DECIMAL(15,2) NOT NULL DEFAULT 0,
    markup_percentage DECIMAL(10,2) NOT NULL DEFAULT 0,
    tax_percentage DECIMAL(10,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    final_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    services_data LONGTEXT,
    payment_data LONGTEXT,
    created_by INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "<div style='text-align: center; margin-top: 50px;'>";
    echo "<h3>Table cost_sheets created successfully!</h3>";
    echo "<p>You will be redirected back in 3 seconds...</p>";
    echo "</div>";
    echo "<script>
        setTimeout(function() {
            window.location.href = '" . $referrer . "';
        }, 3000);
    </script>";
} else {
    echo "<div style='text-align: center; margin-top: 50px;'>";
    echo "<h3>Error creating table:</h3>";
    echo "<p>" . mysqli_error($conn) . "</p>";
    echo "<p><a href='" . $referrer . "'>Go back</a></p>";
    echo "</div>";
}
?>