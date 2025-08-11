<?php
// Include database connection
require_once "config/database.php";

// Function to execute SQL queries
function executeQuery($conn, $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "Query executed successfully: " . substr($sql, 0, 50) . "...<br>";
        return true;
    } else {
        echo "Error executing query: " . mysqli_error($conn) . "<br>";
        return false;
    }
}

// Check if tour_costings table exists
$table_exists = mysqli_query($conn, "SHOW TABLES LIKE 'tour_costings'");
if (mysqli_num_rows($table_exists) == 0) {
    // Create tour_costings table
    $sql = "CREATE TABLE `tour_costings` (
        `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `enquiry_id` INT(11) NOT NULL,
        `cost_sheet_number` VARCHAR(50),
        `guest_name` VARCHAR(255),
        `guest_address` TEXT,
        `whatsapp_number` VARCHAR(20),
        `tour_package` VARCHAR(100),
        `currency` VARCHAR(10),
        `nationality` VARCHAR(10),
        `adults_count` INT DEFAULT 0,
        `children_count` INT DEFAULT 0,
        `infants_count` INT DEFAULT 0,
        `selected_services` TEXT,
        `visa_data` TEXT,
        `accommodation_data` TEXT,
        `transportation_data` TEXT,
        `cruise_data` TEXT,
        `extras_data` TEXT,
        `payment_data` TEXT,
        `total_expense` DECIMAL(10,2) DEFAULT 0,
        `markup_percentage` DECIMAL(5,2) DEFAULT 0,
        `markup_amount` DECIMAL(10,2) DEFAULT 0,
        `tax_percentage` DECIMAL(5,2) DEFAULT 18,
        `tax_amount` DECIMAL(10,2) DEFAULT 0,
        `package_cost` DECIMAL(10,2) DEFAULT 0,
        `currency_rate` DECIMAL(10,4) DEFAULT 1,
        `converted_amount` DECIMAL(10,2) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_enquiry_id` (`enquiry_id`)
    )";
    executeQuery($conn, $sql);
    echo "tour_costings table created.<br>";
} else {
    // Check if columns exist and add them if they don't
    $columns_to_check = [
        'adults_count' => "ALTER TABLE `tour_costings` ADD COLUMN `adults_count` INT DEFAULT 0 AFTER `nationality`",
        'children_count' => "ALTER TABLE `tour_costings` ADD COLUMN `children_count` INT DEFAULT 0 AFTER `adults_count`",
        'infants_count' => "ALTER TABLE `tour_costings` ADD COLUMN `infants_count` INT DEFAULT 0 AFTER `children_count`"
    ];
    
    foreach ($columns_to_check as $column => $alter_sql) {
        $column_exists = mysqli_query($conn, "SHOW COLUMNS FROM `tour_costings` LIKE '$column'");
        if (mysqli_num_rows($column_exists) == 0) {
            executeQuery($conn, $alter_sql);
            echo "$column column added to tour_costings table.<br>";
        }
    }
    
    echo "tour_costings table already exists and has been updated if needed.<br>";
}

// Check if payments table exists
$table_exists = mysqli_query($conn, "SHOW TABLES LIKE 'payments'");
if (mysqli_num_rows($table_exists) == 0) {
    // Create payments table
    $sql = "CREATE TABLE `payments` (
        `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `cost_file_id` INT(11) NOT NULL,
        `payment_date` DATE NOT NULL,
        `payment_bank` VARCHAR(100) NOT NULL,
        `payment_amount` DECIMAL(10,2) NOT NULL,
        `payment_receipt` VARCHAR(255) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT `fk_payments_cost_file` FOREIGN KEY (`cost_file_id`) REFERENCES `tour_costings` (`id`) ON DELETE CASCADE
    )";
    executeQuery($conn, $sql);
    echo "payments table created.<br>";
} else {
    echo "payments table already exists.<br>";
}

// Create uploads directory if it doesn't exist
$uploads_dir = 'uploads/receipts';
if (!file_exists($uploads_dir)) {
    if (mkdir($uploads_dir, 0777, true)) {
        echo "Created directory: $uploads_dir<br>";
    } else {
        echo "Failed to create directory: $uploads_dir<br>";
        echo "Please create this directory manually and ensure it has write permissions.<br>";
    }
} else {
    echo "Directory already exists: $uploads_dir<br>";
}

echo "<br>Database setup completed!";
?>